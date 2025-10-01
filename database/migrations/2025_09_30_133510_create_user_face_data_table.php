<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_face_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('face_id', 64)->unique(); // SHA256 hash for unique face identification
            $table->text('encrypted_descriptor'); // Encrypted face descriptor
            $table->json('face_metadata'); // Additional face data (landmarks, expressions, etc.)
            $table->decimal('confidence_score', 5, 4); // Face detection confidence
            $table->string('registration_ip', 45)->nullable(); // IP address during registration
            $table->string('registration_user_agent')->nullable(); // User agent during registration
            $table->timestamp('last_used_at')->nullable(); // Last time used for login
            $table->integer('usage_count')->default(0); // Number of times used
            $table->boolean('is_active')->default(true); // Enable/disable face login
            $table->boolean('is_verified')->default(false); // Admin verification status
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['face_id']);
            $table->index(['is_active', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_face_data');
    }
};
