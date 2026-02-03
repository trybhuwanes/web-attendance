<?php

use App\Models\Holiday;
use App\Models\User;
use Livewire\Livewire;

it('lists holidays for the selected year', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Holiday::factory()->create([
        'date' => '2026-01-01',
        'description' => 'New Year',
    ]);

    Holiday::factory()->create([
        'date' => '2025-12-25',
        'description' => 'Christmas',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::holidays.index')
        ->set('year', 2026)
        ->assertSee('01 Jan 2026')
        ->assertDontSee('25 Dec 2025');
});

it('allows admin to add a holiday', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test('pages::holidays.index')
        ->set('date', '2026-01-16')
        ->set('description', 'Holiday')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('holidays', [
        'description' => 'Holiday',
    ]);
});
