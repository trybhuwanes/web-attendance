<?php

use App\Models\User;

it('links users to employees', function () {
    $user = User::factory()->create();

    expect($user->employee)->not->toBeNull()
        ->and($user->employee->user->is($user))->toBeTrue();
});
