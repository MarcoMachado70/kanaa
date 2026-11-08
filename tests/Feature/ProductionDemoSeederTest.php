<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SocialDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ProductionDemoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_production_refuses_default_or_missing_password_before_inserting_data(): void
    {
        $this->assertDatabaseCount('users', 0);
        app()->instance('env', 'production');
        foreach ([null, 'curta', 'KanaaDemo2026!'] as $password) {
            config(['demo.password' => $password]);
            try {
                app(SocialDemoSeeder::class)->run();
                $this->fail('A seeder deve exigir uma senha própria em produção.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('KANAA_DEMO_PASSWORD', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('churches', 0);
    }

    public function test_production_inserts_samples_using_configured_password_without_faker(): void
    {
        $this->freezeTime();
        $this->assertDatabaseCount('users', 0);
        app()->instance('env', 'production');
        config(['demo.password' => 'SenhaPropriaDeTeste2026!']);

        $this->artisan('db:seed', ['--class' => SocialDemoSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 60);
        $this->assertDatabaseCount('communities', 20);
        $this->assertDatabaseCount('churches', 3);
        $this->assertDatabaseCount('community_posts', 365);
        $this->assertTrue(Hash::check('SenhaPropriaDeTeste2026!', User::where('email', 'gestor@kanaa.test')->firstOrFail()->password));
        $this->assertDatabaseHas('post_comments', ['body' => 'Caminhar juntos faz toda a diferença.']);
    }
}
