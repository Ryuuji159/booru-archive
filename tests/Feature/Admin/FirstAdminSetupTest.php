<?php

use App\Livewire\Admin\FirstAdminSetup;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('shows the first admin setup screen while the users table is empty', function () {
    $this->get(route('admin.setup'))
        ->assertOk()
        ->assertSee('Create the first administrator', false);
});

it('redirects the admin panel to the first admin setup screen while empty', function () {
    $this->get('/admin')
        ->assertRedirect(route('admin.setup'));

    $this->get('/admin/login')
        ->assertRedirect(route('admin.setup'));
});

it('creates the first admin and logs them in', function () {
    Livewire::test(FirstAdminSetup::class)
        ->set('data.name', 'Ryuuji')
        ->set('data.email', 'admin@example.com')
        ->set('data.password', 'correct-horse-battery-staple')
        ->call('submit')
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($user->name)->toBe('Ryuuji')
        ->and(Hash::check('correct-horse-battery-staple', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('hides the first admin setup screen after the first user exists', function () {
    User::factory()->create();

    $this->get(route('admin.setup'))
        ->assertNotFound();
});
