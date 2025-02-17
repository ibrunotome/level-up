<?php

namespace LevelUp\Experience\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use LevelUp\Experience\LevelUpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'LevelUp\\Experience\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LevelUpServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        app('db')->connection()->getSchemaBuilder()->create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $migration = include __DIR__.'/../database/migrations/create_levels_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_experiences_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/add_level_relationship_to_users_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_experience_audits_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_achievements_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_achievement_user_pivot_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_streak_activities_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_streaks_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_streak_histories_table.php.stub';
        $migration->up();
    }
}
