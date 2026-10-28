<?php

namespace Database\Factories;

use App\Enums\DocumentKind;
use App\Enums\VendorStatus;
use App\Enums\VendorType;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vendor> */
class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => VendorStatus::New,
            'type' => VendorType::Subcontractor,
            'company_name' => fake()->company().' LLC',
            'uei' => strtoupper(fake()->bothify('????########')),
            'address' => '1 Sample Street',
            'city' => 'Honolulu',
            'state' => 'HI',
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '808-555-'.fake()->numerify('####'),
            'trades' => ['concrete'],
            'consented_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::Approved, 'approved_at' => now()]);
    }

    /** Adds current certificates expiring in $days (all required kinds). */
    public function withCertificates(int $days = 200): static
    {
        return $this->afterCreating(function (Vendor $vendor) use ($days) {
            foreach ($vendor->type->requiredDocuments() as $kind) {
                $vendor->documents()->create([
                    'kind' => $kind, 'path' => 'vendors/'.$vendor->id.'/'.$kind->value.'.pdf', 'original_name' => $kind->value.'.pdf',
                    'mime' => 'application/pdf', 'size' => 1024, 'expires_on' => $kind === DocumentKind::Other ? null : today()->addDays($days),
                ]);
            }
        });
    }
}
