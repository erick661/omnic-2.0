<?php

namespace Database\Factories;

use App\Models\OutboxEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutboxEmailFactory extends Factory
{
    protected $model = OutboxEmail::class;

    public function definition(): array
    {
        return [
            'from_email' => $this->faker->email(),
            'from_name' => $this->faker->name(),
            'to_email' => $this->faker->email(),
            'cc_emails' => null,
            'bcc_emails' => null,
            'subject' => $this->faker->sentence(),
            'body_html' => $this->faker->randomHtml(),
            'body_text' => $this->faker->text(),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
            'scheduled_at' => null,
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed', 'scheduled']),
            'attempts' => 0,
            'last_attempt_at' => null,
            'sent_at' => null,
            'error_message' => null,
            'gmail_message_id' => null,
            'reference_type' => null,
            'reference_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'attempts' => 0,
            'sent_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'gmail_message_id' => $this->faker->uuid(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'attempts' => $this->faker->numberBetween(1, 3),
            'last_attempt_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'error_message' => $this->faker->sentence(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
        ]);
    }
}