<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener algunos usuarios para asignar a portfolios
        $users = \App\Models\User::limit(3)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('⚠️  No hay usuarios en la BD. Creando usuario por defecto...');
            $defaultUser = \App\Models\User::create([
                'name' => 'Supervisor Sistema',
                'email' => 'supervisor@omnic.cl',
                'password' => bcrypt('password')
            ]);
            $users = collect([$defaultUser]);
        }

        $portfolios = [
            [
                'portfolio_code' => 'TECH',
                'portfolio_name' => 'Tecnología y Sistemas',
                'assigned_user_id' => $users->first()->id,
                'rut_ranges' => ['76000000-77000000', '96000000-97000000'],
                'campaign_patterns' => ['TECH-*', 'SYS-*', 'REF-TECH*'],
                'is_active' => true,
                'description' => 'Cartera de empresas tecnológicas y sistemas'
            ],
            [
                'portfolio_code' => 'SALES',
                'portfolio_name' => 'Ventas y Comercial',
                'assigned_user_id' => $users->count() > 1 ? $users->skip(1)->first()->id : $users->first()->id,
                'rut_ranges' => ['77000001-78000000', '12000000-15000000'],
                'campaign_patterns' => ['SALES-*', 'CAMP-*', 'ENV-COM*'],
                'is_active' => true,
                'description' => 'Cartera comercial y ventas'
            ],
            [
                'portfolio_code' => 'LEGAL',
                'portfolio_name' => 'Legal y Compliance',
                'assigned_user_id' => $users->count() > 2 ? $users->skip(2)->first()->id : $users->first()->id,
                'rut_ranges' => ['78000001-79000000'],
                'campaign_patterns' => ['LEGAL-*', 'LEG-*', 'ENV-LEG*'],
                'is_active' => true,
                'description' => 'Cartera legal y compliance'
            ]
        ];

        foreach ($portfolios as $portfolio) {
            \App\Models\Portfolio::updateOrCreate(
                ['portfolio_code' => $portfolio['portfolio_code']],
                $portfolio
            );
        }

        $this->command->info('✅ Portfolios creados: ' . count($portfolios));
    }
}
