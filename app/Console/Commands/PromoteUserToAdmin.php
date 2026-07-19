<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdmin extends Command
{
    protected $signature = 'user:promote-admin {gmail}';

    protected $description = 'Promote an existing user to the administrator role';

    public function handle(): int
    {
        $gmail = (string) $this->argument('gmail');
        $user = User::query()->where('gmail', $gmail)->first();

        if ($user === null) {
            $this->error('No user exists with the provided Gmail address.');

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->info('The user is already an administrator.');

            return self::SUCCESS;
        }

        $user->forceFill(['role' => UserRole::Admin])->save();
        $this->info('The user was promoted to administrator.');

        return self::SUCCESS;
    }
}
