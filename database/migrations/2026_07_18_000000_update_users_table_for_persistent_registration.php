<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'username');
            $table->renameColumn('email', 'gmail');
            $table->renameColumn('email_verified_at', 'gmail_verified_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->unique()->after('gmail');
            $table->unique('gmail');
            $table->unique('username');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['gmail']);
            $table->dropUnique(['phone']);
            $table->dropUnique(['username']);
            $table->dropColumn('phone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('username', 'name');
            $table->renameColumn('gmail', 'email');
            $table->renameColumn('gmail_verified_at', 'email_verified_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
