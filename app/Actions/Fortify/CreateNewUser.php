<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /** @param array<string, mixed> $input */
    public function create(array $input): User
    {
        $input['email'] = Str::lower(trim($input['email'] ?? ''));
        $data = Validator::make($input, [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', 'regex:/^[^@]+@(mail\.)?ugm\.ac\.id$/i', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8), 'confirmed', 'max:255'],
            'faculty' => 'nullable|exists:faculties,code',
            'safety_agreement' => 'required|accepted',
        ])->validate();

        return DB::transaction(function () use ($data) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->role = 'student';
            $user->status = 'active';
            $user->faculty_code = $data['faculty'] ?? null;
            $user->safety_agreed_at = now();
            $user->save();
            DB::table('user_preferences')->insert(['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);

            return $user;
        });
    }
}
