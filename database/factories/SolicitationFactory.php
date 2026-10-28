<?php

namespace Database\Factories;

use App\Enums\SolicitationStatus;
use App\Enums\SolicitationType;
use App\Models\Solicitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Solicitation> */
class SolicitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => SolicitationStatus::New,
            'source' => 'federal',
            'organization' => 'Sample Contracting Office',
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '808-555-'.fake()->numerify('####'),
            'title' => 'Repair facility '.fake()->numerify('###').', sample installation',
            'type' => SolicitationType::Rfq,
            'region' => 'Oʻahu',
            'scope' => 'Repair of a sample facility, as described in the statement of work.',
            'response_due_at' => now()->addDays(10)->setTime(14, 0),
        ];
    }

    public function dueIn(int $days): static
    {
        return $this->state(fn () => ['response_due_at' => now()->addDays($days)->setTime(14, 0)]);
    }
}
