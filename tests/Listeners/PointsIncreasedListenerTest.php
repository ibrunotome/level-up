<?php

use Illuminate\Support\Facades\Event;
use LevelUp\Experience\Enums\AuditType;
use LevelUp\Experience\Events\PointsIncreased;
use LevelUp\Experience\Listeners\PointsIncreasedListener;

beforeEach(closure: function (): void {
    config()->set(key: 'level-up.multiplier.enabled', value: false);
});

uses()->group('experience');

test(description: 'the Event and Listener run when points are added to a User Model', closure: function (): void {
    Event::fakeFor(callable: function (): void {
        // this creates the experience Model
        $this->user->addPoints(amount: 10);
        // so now it will increment the points, instead of creating a new experience Model
        $this->user->addPoints(amount: 10);

        Event::assertDispatched(event: PointsIncreased::class);
        Event::assertListening(expectedEvent: PointsIncreased::class, expectedListener: PointsIncreasedListener::class);
    });
});

test(description: 'the level_id in User\'s table defaults to 1 on Model creation', closure: function () {
    $this->user->addPoints(1);

    expect($this->user->level_id)->toBe(expected: 1);

    $this->assertDatabaseHas(table: 'users', data: [
        'id' => $this->user->id,
        'level_id' => 1,
    ]);
});

test(description: 'adding points creates an audit record', closure: function (): void {
    config()->set(key: 'level-up.audit.enabled', value: true);

    $this->user->addPoints(amount: 10);

    expect($this->user)
        ->experienceHistory()->count()->toBe(expected: 1);

    $this->assertDatabaseHas(table: 'experience_audits', data: [
        'user_id' => $this->user->id,
        'type' => AuditType::Add->value,
        'points' => 10,
    ]);
});

test(description: 'when a User levels up, a record is stored in the audit', closure: function (): void {
    config()->set(key: 'level-up.audit.enabled', value: true);

    $this->user->addPoints(amount: 100);

    expect($this->user)
        ->experienceHistory()->count()->toBe(expected: 2);

    $this->assertDatabaseHas(table: 'experience_audits', data: [
        'user_id' => $this->user->id,
        'points' => 100,
        'levelled_up' => true,
        'level_to' => 2,
        'type' => AuditType::LevelUp->value,
    ]);
});

test(description: 'user levels are correct', closure: function () {
    $this->user->addPoints(amount: 100);

    expect($this->user->getLevel())->toBe(expected: 2)
        ->and($this->user->level_id)->toBe(expected: 2)
        ->and($this->user->nextLevelAt())->toBe(expected: 150)
        ->and($this->user->experience->status->level)->toBe(expected: 2);

    $this->assertDatabaseHas(table: 'experiences', data: [
        'user_id' => $this->user->id,
        'experience_points' => 100,
        'level_id' => 2,
    ]);

    $this->user->addPoints(amount: 150);

    expect($this->user->getLevel())->toBe(expected: 3)
        ->and($this->user->level_id)->toBe(expected: 3)
        ->and($this->user->experience->status->level)->toBe(expected: 3);

    $this->assertDatabaseHas(table: 'users', data: [
        'id' => $this->user->id,
        'level_id' => 3,
    ]);
});
