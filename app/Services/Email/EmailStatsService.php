<?php

namespace App\Services\Email;

use App\Models\ImportedEmail;
use App\Models\OutboxEmail;
use App\Models\GmailGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmailStatsService
{
    /**
     * Obtener estadísticas completas del sistema de correos
     */
    public function getStats(array $options = []): array
    {
        $period = $options['period'] ?? 'today';
        
        return [
            'inbox' => $this->getInboxStats($period, $options),
            'outbox' => $this->getOutboxStats($period, $options),
            'agents' => $this->getAgentStats($period, $options),
            'groups' => $this->getGroupStats($period, $options),
            'performance' => $this->getPerformanceStats($period, $options),
        ];
    }

    /**
     * Estadísticas de correos recibidos
     */
    private function getInboxStats(string $period, array $options): array
    {
        $query = ImportedEmail::query();
        $this->applyPeriodFilter($query, $period, 'received_at');
        $this->applyFilters($query, $options);

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('case_status', 'pending')->count(),
            'assigned' => (clone $query)->where('case_status', 'assigned')->count(),
            'in_progress' => (clone $query)->where('case_status', 'in_progress')->count(),
            'resolved' => (clone $query)->where('case_status', 'resolved')->count(),
            'closed' => (clone $query)->where('case_status', 'closed')->count(),
            'by_priority' => $this->getByPriority($query),
            'response_time' => $this->getAverageResponseTime($query),
        ];
    }

    /**
     * Estadísticas de correos enviados
     */
    private function getOutboxStats(string $period, array $options): array
    {
        $query = OutboxEmail::query();
        $this->applyPeriodFilter($query, $period, 'created_at');

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('send_status', 'pending')->count(),
            'sent' => (clone $query)->where('send_status', 'sent')->count(),
            'failed' => (clone $query)->where('send_status', 'failed')->count(),
            'scheduled' => (clone $query)->where('send_status', 'pending')
                                      ->where('scheduled_at', '>', now())
                                      ->count(),
            'success_rate' => $this->calculateSuccessRate($query),
        ];
    }

    /**
     * Estadísticas por agente
     */
    private function getAgentStats(string $period, array $options): array
    {
        $query = ImportedEmail::with('assignedUser')
                              ->whereNotNull('assigned_to');
        
        $this->applyPeriodFilter($query, $period, 'assigned_at');
        
        if (!empty($options['agent'])) {
            $query->where('assigned_to', $options['agent']);
        }

        $stats = $query->selectRaw('
                assigned_to,
                COUNT(*) as total_assigned,
                COUNT(CASE WHEN case_status = \'resolved\' THEN 1 END) as total_resolved,
                COUNT(CASE WHEN case_status = \'closed\' THEN 1 END) as total_closed,
                AVG(CASE 
                    WHEN (marked_resolved_at IS NOT NULL OR auto_resolved_at IS NOT NULL) AND assigned_at IS NOT NULL 
                    THEN EXTRACT(EPOCH FROM (COALESCE(marked_resolved_at, auto_resolved_at) - assigned_at))/3600 
                END) as avg_response_time
            ')
            ->groupBy('assigned_to')
            ->get();

        return $stats->map(function ($stat) {
            $user = User::find($stat->assigned_to);
            return [
                'user_id' => $stat->assigned_to,
                'name' => $user ? $user->name : 'Usuario no encontrado',
                'assigned' => $stat->total_assigned,
                'resolved' => $stat->total_resolved,
                'closed' => $stat->total_closed,
                'response_time' => round($stat->avg_response_time ?? 0, 1),
                'resolution_rate' => $stat->total_assigned > 0 
                    ? round(($stat->total_resolved / $stat->total_assigned) * 100, 1) 
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Estadísticas por grupo
     */
    private function getGroupStats(string $period, array $options): array
    {
        $query = GmailGroup::with(['importedEmails' => function ($q) use ($period) {
            $this->applyPeriodFilter($q, $period, 'received_at');
        }]);

        if (!empty($options['group'])) {
            $query->where('email', $options['group']);
        }

        return $query->get()->map(function ($group) {
            $emails = $group->importedEmails;
            
            return [
                'id' => $group->id,
                'name' => $group->name,
                'email' => $group->email,
                'total_emails' => $emails->count(),
                'pending' => $emails->where('case_status', 'pending')->count(),
                'assigned' => $emails->where('case_status', 'assigned')->count(),
                'resolved' => $emails->where('case_status', 'resolved')->count(),
                'avg_response_time' => $this->calculateGroupResponseTime($emails),
            ];
        })->toArray();
    }

    /**
     * Estadísticas de rendimiento
     */
    private function getPerformanceStats(string $period, array $options): array
    {
        $inboxQuery = ImportedEmail::query();
        $outboxQuery = OutboxEmail::query();
        
        $this->applyPeriodFilter($inboxQuery, $period, 'received_at');
        $this->applyPeriodFilter($outboxQuery, $period, 'created_at');

        return [
            'emails_per_day' => $this->getEmailsPerDay($inboxQuery),
            'response_time_trend' => $this->getResponseTimeTrend($inboxQuery),
            'resolution_rate' => $this->getResolutionRate($inboxQuery),
            'peak_hours' => $this->getPeakHours($inboxQuery),
            'busiest_days' => $this->getBusiestDays($inboxQuery),
        ];
    }

    /**
     * Aplicar filtro de período
     */
    private function applyPeriodFilter($query, string $period, string $dateColumn): void
    {
        switch ($period) {
            case 'today':
                $query->whereDate($dateColumn, today());
                break;
            case 'week':
                $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth($dateColumn, now()->month)
                      ->whereYear($dateColumn, now()->year);
                break;
            case 'year':
                $query->whereYear($dateColumn, now()->year);
                break;
            // 'all' no aplica filtro
        }
    }

    /**
     * Aplicar filtros adicionales
     */
    private function applyFilters($query, array $options): void
    {
        if (!empty($options['group'])) {
            $query->whereHas('gmailGroup', function ($q) use ($options) {
                $q->where('email', $options['group']);
            });
        }

        if (!empty($options['agent'])) {
            $query->where('assigned_to', $options['agent']);
        }
    }

    /**
     * Obtener estadísticas por prioridad
     */
    private function getByPriority($query): array
    {
        return (clone $query)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
    }

    /**
     * Calcular tiempo promedio de respuesta
     */
    private function getAverageResponseTime($query): float
    {
        return (clone $query)
            ->whereNotNull('assigned_at')
            ->where(function($q) {
                $q->whereNotNull('marked_resolved_at')
                  ->orWhereNotNull('auto_resolved_at');
            })
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (COALESCE(marked_resolved_at, auto_resolved_at) - assigned_at))/3600) as avg_time')
            ->value('avg_time') ?? 0;
    }

    /**
     * Calcular tasa de éxito
     */
    private function calculateSuccessRate($query): float
    {
        $total = (clone $query)->count();
        $sent = (clone $query)->where('send_status', 'sent')->count();
        
        return $total > 0 ? round(($sent / $total) * 100, 1) : 0;
    }

    /**
     * Calcular tiempo de respuesta por grupo
     */
    private function calculateGroupResponseTime($emails): float
    {
        $resolved = $emails->filter(function ($email) {
            return $email->assigned_at && $email->resolved_at;
        });

        if ($resolved->isEmpty()) {
            return 0;
        }

        $totalHours = $resolved->sum(function ($email) {
            return $email->assigned_at->diffInHours($email->resolved_at);
        });

        return round($totalHours / $resolved->count(), 1);
    }

    /**
     * Obtener correos por día
     */
    private function getEmailsPerDay($query): array
    {
        return (clone $query)
            ->selectRaw('DATE(received_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Obtener tendencia de tiempo de respuesta
     */
    private function getResponseTimeTrend($query): array
    {
        return (clone $query)
            ->whereNotNull('assigned_at')
            ->where(function($q) {
                $q->whereNotNull('marked_resolved_at')
                  ->orWhereNotNull('auto_resolved_at');
            })
            ->selectRaw('
                DATE(assigned_at) as date, 
                AVG(EXTRACT(EPOCH FROM (COALESCE(marked_resolved_at, auto_resolved_at) - assigned_at))/3600) as avg_response_time
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('avg_response_time', 'date')
            ->toArray();
    }

    /**
     * Obtener tasa de resolución
     */
    private function getResolutionRate($query): float
    {
        $total = (clone $query)->count();
        $resolved = (clone $query)->whereIn('case_status', ['resolved', 'closed'])->count();
        
        return $total > 0 ? round(($resolved / $total) * 100, 1) : 0;
    }

    /**
     * Obtener horas pico
     */
    private function getPeakHours($query): array
    {
        return (clone $query)
            ->selectRaw('EXTRACT(HOUR FROM received_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Obtener días más ocupados
     */
    private function getBusiestDays($query): array
    {
        return (clone $query)
            ->selectRaw('TO_CHAR(received_at, \'Day\') as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('count', 'desc')
            ->pluck('count', 'day')
            ->toArray();
    }
}