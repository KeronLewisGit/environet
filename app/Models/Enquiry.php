<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\Area;
use App\Enums\EnquiryStatus;
use App\Enums\EnquiryTopic;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property EnquiryStatus $status
 * @property EnquiryTopic $topic
 * @property string $name
 * @property string|null $organization
 * @property string $email
 * @property string|null $phone
 * @property string $message
 * @property Carbon $created_at
 */
class Enquiry extends Model implements Submission
{
    use HasReference, HasTimeline;

    protected $fillable = ['status', 'topic', 'name', 'organization', 'email', 'phone', 'message', 'source_url'];

    public static function referencePrefix(): string
    {
        return 'EN';
    }

    #[\Override]
    protected function casts(): array
    {
        return ['status' => EnquiryStatus::class, 'topic' => EnquiryTopic::class];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $enquiry): void {
            $enquiry->status ??= EnquiryStatus::New;
        });
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Enquiry';
    }

    public function area(): Area
    {
        return Area::Enquiries;
    }

    public function senderName(): string
    {
        return $this->name;
    }

    public function senderEmail(): ?string
    {
        return $this->email;
    }

    public function summary(): array
    {
        return array_filter([
            'From' => $this->name.($this->organization ? ', '.$this->organization : ''),
            'Topic' => $this->topic->label(),
            'Email' => $this->email,
            'Phone' => $this->phone ? format_phone($this->phone) : '',
            'Message' => mb_strimwidth($this->message, 0, 400, '…'),
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.enquiries.show', $this);
    }

    public function nextSteps(): string
    {
        return 'We answer enquiries within two working days.';
    }
}
