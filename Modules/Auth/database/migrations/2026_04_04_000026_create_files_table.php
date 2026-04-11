<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files table — universal upload system for the entire platform.
     *
     * Any user (student, mentor, admin) can upload files.
     * Files can be standalone OR attached to any model via polymorphic relationship:
     *   - Support ticket attachments (fileable_type = SupportTicket)
     *   - Booking documents         (fileable_type = Booking)
     *   - Mentor notes attachments  (fileable_type = MentorNote)
     *   - Profile avatars           (fileable_type = User / Mentor)
     *   - General uploads           (fileable_type = null)
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();

            // Owner — who uploaded this file
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic relationship — attach file to any model (nullable = standalone)
            $table->nullableMorphs('fileable'); // creates: fileable_type (string), fileable_id (bigint)

            // File identity
            $table->string('original_name');            // original filename user uploaded, e.g. "transcript.pdf"
            $table->string('stored_name');              // UUID-based stored name, e.g. "a1b2c3d4.pdf"
            $table->string('path');                     // full storage path, e.g. "uploads/2026/04/a1b2c3d4.pdf"
            $table->string('disk')->default('public');  // storage disk: 'public', 'local', 's3'
            $table->string('extension', 20);            // e.g. "pdf", "jpg", "docx"
            $table->string('mime_type', 100);           // e.g. "application/pdf", "image/jpeg"
            $table->unsignedBigInteger('size');         // file size in bytes

            // File type/purpose — helps filter and apply different rules per type
            $table->enum('type', [
                'avatar',       // profile picture (user or mentor)
                'document',     // transcripts, CVs, application docs
                'attachment',   // support ticket or booking attachments
                'receipt',      // payment receipts / invoices
                'other',        // anything else
            ])->default('other');

            // Visibility — controls whether the file URL is public or signed
            $table->boolean('is_public')->default(false);  // true = public URL, false = private signed URL

            // Soft approach: flag as deleted rather than physically removing immediately
            // Physical deletion is handled by a scheduled cleanup job
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();    // when flagged for deletion

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'type']);             // "all my documents", "all my avatars"
            // Note: nullableMorphs('fileable') already creates index on [fileable_type, fileable_id]
            $table->index('created_at');                    // admin audit / date filtering
            $table->index('is_deleted');                    // cleanup job filter
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
