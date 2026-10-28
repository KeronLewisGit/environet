<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JobPosting> */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        $title = 'UXO Technician II '.fake()->unique()->numberBetween(1, 9999);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'category' => JobCategory::Uxo,
            'employment_type' => EmploymentType::Project,
            'status' => JobStatus::Open,
            'location' => 'Hawaiʻi Island',
            'openings' => 1,
            'summary' => 'Field work on a munitions response project.',
            'duties' => "Surface and subsurface clearance\nDaily reports",
            'requirements' => 'Current HAZWOPER 40-hour',
        ];
    }
}
