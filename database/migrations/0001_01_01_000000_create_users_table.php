<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('gmail')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->timestamp('gmail_verified_at')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('role')->default(UserRole::User->value);
            $table->string('status')->default('pending');
            $table->uuid('registration_attempt_id')->nullable()->unique();
            $table->string('verification_code', 6)->nullable();
            $table->unsignedTinyInteger('verification_attempts')->default(0);
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('resend_available_at')->nullable();
            $table->timestamp('verification_used_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
