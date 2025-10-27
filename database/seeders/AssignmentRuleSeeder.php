<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignmentRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            // Regla 1: Códigos de envío masivo
            [
                'rule_type' => 'mass_campaign',
                'pattern_name' => 'Códigos de Envío Masivo',
                'regex_pattern' => '/(?:REF|CAMP|ENV)-([A-Z0-9]{4,8})/i',
                'priority_order' => 1,
                'is_active' => true,
                'description' => 'Detecta códigos como REF-TECH01, CAMP-SALES, ENV-LEGAL01 en subject/body',
                'config' => [
                    'create_case' => true,
                    'assign_by_portfolio' => true
                ]
            ],
            
            // Regla 2: Códigos de caso existente
            [
                'rule_type' => 'case_code',
                'pattern_name' => 'Códigos de Caso Existente',
                'regex_pattern' => '/(?:CASO|CASE|TICKET)-([0-9]{4,8})/i',
                'priority_order' => 2,
                'is_active' => true,
                'description' => 'Detecta códigos como CASO-123456, CASE-789012 para relacionar con casos existentes',
                'config' => [
                    'link_to_existing_case' => true,
                    'auto_assign' => false
                ]
            ],
            
            // Regla 3: RUT en emails
            [
                'rule_type' => 'rut_pattern',
                'pattern_name' => 'RUT Chileno en Email',
                'regex_pattern' => '/(\d{1,2}\.?\d{3}\.?\d{3})-([0-9kK])/i',
                'priority_order' => 4,
                'is_active' => true,
                'description' => 'Detecta RUT chileno en formato 12345678-9 o 12.345.678-9',
                'config' => [
                    'validate_rut' => true,
                    'lookup_reference_code' => true
                ]
            ]
        ];

        foreach ($rules as $rule) {
            \App\Models\AssignmentRule::updateOrCreate(
                ['pattern_name' => $rule['pattern_name']],
                $rule
            );
        }

        $this->command->info('✅ Reglas de asignación creadas: ' . count($rules));
    }
}
