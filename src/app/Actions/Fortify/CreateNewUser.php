<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Role; // Import Role model
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Validation should be handled by Fortify/RegisterRequest before this action.

        // Dynamically find the 'user' role ID.
        $userRole = Role::where('name', 'user')->firstOrFail();

        // Use the $input array directly as it's pre-validated.
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role_id' => $userRole->id, // Use the dynamically found ID
        ]);

        return $user;
    }
}