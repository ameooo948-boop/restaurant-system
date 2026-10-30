<?php

namespace App\Modules\Authentication\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewCustomer implements CreatesNewUsers
{
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => trim($input['name']),
                'email' => mb_strtolower(trim($input['email'])),
                'password' => $input['password'],
                'is_active' => true,
            ]);

            $user->customerProfile()->create([
                'full_name' => trim($input['name']),
                'phone' => isset($input['phone']) && trim($input['phone']) !== ''
                    ? trim($input['phone'])
                    : null,
            ]);

            return $user;
        });
    }
}
