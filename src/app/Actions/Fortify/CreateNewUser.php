<?php

namespace App\Actions\Fortify;

    use App\Models\User;
    use App\Http\Requests\RegisterRequest;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Validator;
    use Laravel\Fortify\Contracts\CreatesNewUsers;
    use App\Models\Role;

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

            $userRole = Role::where('name', 'user')->firstOrFail();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $userRole->id,
            ]);

            return $user;
        }
    }