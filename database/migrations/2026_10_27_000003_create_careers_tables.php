<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 160);
            $table->string('category', 20);
            $table->string('employment_type', 20);
            $table->string('status', 20)->default('open')->index();
            $table->string('location', 160);
            $table->unsignedSmallInteger('openings')->default(1);
            $table->text('summary');
            $table->text('duties')->nullable();
            $table->text('requirements')->nullable();
            $table->date('closes_on')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20);
            $table->string('status', 20)->default('new')->index();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('office', 20);
            $table->string('region', 40)->nullable();
            $table->boolean('can_travel')->default(false);
            $table->string('experience', 20);
            $table->text('qualifications')->nullable();
            $table->date('available_from')->nullable();
            $table->text('message')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_name', 200)->nullable();
            $table->string('cv_mime', 120)->nullable();
            $table->unsignedInteger('cv_size')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
        });

        // Self-declared field certifications (HAZWOPER, UXO Technician I-III, OSHA, CQM ...) with expiry dates.
        Schema::create('applicant_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('detail', 120)->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('status', 20)->default('new')->index();
            $table->string('topic', 20);
            $table->string('name', 120);
            $table->string('organization', 160)->nullable();
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->text('message');
            $table->string('source_url', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('applicant_certifications');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
    }
};
