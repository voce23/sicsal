<?php

use App\Models\CentroSalud;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $centro = CentroSalud::factory()->create();

    $response = $this->post(route('register.store'), [
        'name' => 'John',
        'apellidos' => 'Doe',
        'email' => 'test@example.com',
        'centro_salud_id' => $centro->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('pendiente'));

    $this->assertAuthenticated();
});
