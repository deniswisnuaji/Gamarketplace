<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateMarketplaceAdmin extends Command
{
    protected $signature = 'marketplace:admin {email : New administrator UGM email} {--name=Marketplace Admin}';

    protected $description = 'Create a new administrator using a hidden password prompt';

    public function handle(): int
    {
        $data = ['email' => Str::lower(trim($this->argument('email'))), 'name' => $this->option('name'), 'password' => $this->secret('Choose a password (at least 12 characters)'), 'password_confirmation' => $this->secret('Confirm password')];
        $validator = Validator::make($data, ['email' => ['required', 'email', 'regex:/^[^@]+@(mail\.)?ugm\.ac\.id$/i', 'unique:users,email'], 'name' => 'required|string|max:255', 'password' => ['required', 'confirmed', Password::min(12)]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->role = 'admin';
        $user->status = 'active';
        $user->save();
        $this->info('Administrator created. Sign in at /gm/admin-login.html.');

        return self::SUCCESS;
    }
}
