<?php

namespace App\Services\Assignment\Contracts;

use App\Models\Email;

/**
 * ✅ SOLID - Interface Segregation: Interface específica para estrategias de asignación
 * ✅ SOLID - Open/Closed: Permite agregar nuevas estrategias sin modificar código existente
 */
interface AssignmentStrategyInterface
{
    /**
     * Verificar si esta estrategia puede manejar el email
     */
    public function canHandle(Email $email): bool;

    /**
     * Ejecutar la asignación y retornar el user_id asignado
     */
    public function assign(Email $email): ?int;

    /**
     * Obtener la razón de la asignación (para auditoría)
     */
    public function getAssignmentReason(): string;

    /**
     * Obtener la prioridad de esta estrategia (menor número = mayor prioridad)
     */
    public function getPriority(): int;
}