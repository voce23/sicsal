<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->label('Nombres'),
                        TextInput::make('apellidos')
                            ->required()
                            ->maxLength(100)
                            ->label('Apellidos'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Correo electrónico'),
                    ])
                    ->columns(2),

                Section::make('Acceso al sistema')
                    ->schema([
                        TextInput::make('usuario')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->label('Nombre de usuario'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->minLength(8)
                            ->label(fn (string $operation) => $operation === 'create' ? 'Contraseña' : 'Nueva contraseña (dejar vacío para no cambiar)'),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->preload()
                            ->options(fn () => self::getRolesDisponibles())
                            ->required()
                            ->label('Rol'),
                        Select::make('centro_salud_id')
                            ->relationship('centroSalud', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(fn () => ! auth()->user()?->hasRole('superadmin'))
                            ->disabled(fn () => auth()->user()?->hasRole('admin'))
                            ->default(fn () => auth()->user()?->centro_salud_id)
                            ->label('Centro de Salud'),
                        Toggle::make('activo')
                            ->default(true)
                            ->label('Activo'),
                    ])
                    ->columns(2),
            ]);
    }

    private static function getRolesDisponibles(): array
    {
        $user = auth()->user();

        if ($user?->hasRole('superadmin')) {
            return [
                'superadmin' => 'Superadmin',
                'admin' => 'Admin (Médico)',
                'registrador' => 'Registrador (Enfermero/a)',
            ];
        }

        return [
            'registrador' => 'Registrador (Enfermero/a)',
        ];
    }
}
