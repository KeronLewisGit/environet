<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Create (or reset the password of) an administrator account for the back office';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name', 'Administrator');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password (min 10 characters)');

        $validator = Validator::make(compact('email', 'password'), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => strtolower((string) $email)], [
            'name' => $name,
            'password' => Hash::make((string) $password),
            'role' => Role::Admin,
            'active' => true,
        ]);

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated').' staff account '.$user->email);

        return self::SUCCESS;
    }
}
