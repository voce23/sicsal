<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\CentroSalud;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name'            => ['required', 'string', 'max:100'],
            'apellidos'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'centro_salud_id' => ['required', Rule::exists(CentroSalud::class, 'id')],
            'password'        => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name'            => $input['name'],
            'apellidos'       => $input['apellidos'],
            'email'           => $input['email'],
            'centro_salud_id' => $input['centro_salud_id'],
            'password'        => $input['password'],
            'activo'          => false,
        ]);
    }
}
