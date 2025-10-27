<?php

namespace Database\Factories;

use App\Models\ImportedEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportedEmailFactory extends Factory
{
    protected $model = ImportedEmail::class;

    public function definition(): array
    {
        return [
            'gmail_message_id' => $this->faker->uuid(),
            'gmail_thread_id' => $this->faker->uuid(),
            'gmail_group_id' => null,
            'subject' => $this->faker->sentence(),
            'from_email' => $this->faker->email(),
            'from_name' => $this->faker->name(),
            'to_email' => $this->faker->email(),
            'cc_emails' => null,
            'bcc_emails' => null,
            'body_html' => $this->faker->randomHtml(),
            'body_text' => $this->faker->text(),
            'received_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'imported_at' => now(),
            'has_attachments' => $this->faker->boolean(20),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
            'reference_code_id' => null,
            'rut_empleador' => null,
            'dv_empleador' => null,
            'assigned_to' => null,
            'assigned_by' => null,
            'assigned_at' => null,
            'assignment_notes' => null,
            'case_status' => $this->faker->randomElement(['pending', 'assigned', 'in_progress', 'resolved', 'closed']),
            'marked_resolved_at' => null,
            'auto_resolved_at' => null,
            'spam_marked_by' => null,
            'spam_marked_at' => null,
            'derived_to_supervisor' => false,
            'derivation_notes' => null,
            'derived_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_status' => 'pending',
            'assigned_to' => null,
            'assigned_at' => null,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_status' => 'assigned',
            'assigned_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_status' => 'resolved',
            'assigned_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
            'marked_resolved_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'case_status' => 'closed',
            'assigned_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
            'marked_resolved_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}