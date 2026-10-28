<?php

namespace App\Contracts;

use App\Enums\Area;
use App\Models\Note;
use App\Models\User;

/** Anything the public sends in: it has a reference, a sender and a summary for the emails. */
interface Submission
{
    public function referenceNumber(): string;

    public function kindLabel(): string;

    public function area(): Area;

    public function senderName(): string;

    public function senderEmail(): ?string;

    /** @return array<string, string> label => value, for the office email */
    public function summary(): array;

    public function adminUrl(): string;

    /** One or two sentences for the confirmation email: what happens next. */
    public function nextSteps(): string;

    /** Adds a timeline entry (HasTimeline). */
    public function log(string $body, string $kind = 'system', ?User $user = null): Note;
}
