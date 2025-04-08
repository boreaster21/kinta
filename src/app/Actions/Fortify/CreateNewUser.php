<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Http\Requests\RegisterRequest; // Re-add RegisterRequest import
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator; // Re-add Validator import
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**

     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $request = new RegisterRequest();
        $validator = Validator::make($input, $request->rules(), $request->messages());
        $validated = $validator->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => 2,
        ]);

        return $user;
    }
}