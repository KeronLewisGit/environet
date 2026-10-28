<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A project's document room: shared with the client by an expiring signed link.
        Schema::create('document_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->string('access_key', 40);
            $table->string('name', 160);
            $table->string('client_name', 160);
            $table->string('client_contact', 120)->nullable();
            $table->string('client_email')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('link_expires_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('room_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_room_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('category', 20);
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_documents');
        Schema::dropIfExists('document_rooms');
    }
};
