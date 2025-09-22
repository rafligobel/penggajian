<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Kita akan menonaktifkan seluruh test ini karena fitur registrasi tidak digunakan.
// Cukup berikan komentar pada semua fungsi test di dalamnya.

// test('registration screen can be rendered', function () {
//     $response = $this->get('/register');
//
//     $response->assertStatus(200);
// });
//
// test('new users can register', function () {
//     $response = $this->post('/register', [
//         'name' => 'Test User',
//         'email' => 'test@example.com',
//         'password' => 'password',
//         'password_confirmation' => 'password',
//     ]);
//
//     $this->assertAuthenticated();
//     $response->assertRedirect(route('dashboard', absolute: false));
// });