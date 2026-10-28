<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property JobCategory $category
 * @property EmploymentType $employment_type
 * @property JobStatus $status
 * @property string $location
 * @property int $openings
 * @property string $summary
 * @property string|null $duties
 * @property string|null $requirements
 * @property Carbon|null $closes_on
 * @property bool $is_demo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'title', 'category', 'employment_type', 'status', 'location', 'openings', 'summary', 'duties', 'requirements', 'closes_on', 'is_demo'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'category' => JobCategory::class,
            'employment_type' => EmploymentType::class,
            'status' => JobStatus::class,
            'closes_on' => 'date',
            'openings' => 'integer',
            'is_demo' => 'boolean',
        ];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<JobApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', JobStatus::Open)
            ->where(fn (Builder $q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()));
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Open && ($this->closes_on === null || $this->closes_on->gte(today()));
    }

    /** @return list<string> */
    public function dutyList(): array
    {
        return lines_to_array($this->duties);
    }

    /** @return list<string> */
    public function requirementList(): array
    {
        return lines_to_array($this->requirements);
    }
}
