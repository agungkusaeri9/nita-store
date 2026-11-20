<?php

namespace Tests\Feature;

use App\Models\AppConfig;
use App\Models\User;
use Database\Seeders\AppConfigSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AppConfigTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_access_app_configs(): void
    {
        $response = $this->get(route('app-configs.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('configurations.rajaongkir'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('configurations.geoapify'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_app_configs_list(): void
    {
        $user = User::factory()->create();
        AppConfig::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('app-configs.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('app-configs/Index')
            ->has('configs.data', 3)
            ->has('filters')
        );

        $responseConfigurations = $this->actingAs($user)->get(route('configurations.index'));
        $responseConfigurations->assertOk();
        $responseConfigurations->assertInertia(fn ($page) => $page
            ->component('app-configs/Index')
            ->has('configs.data', 3)
            ->has('filters')
        );
    }

    public function test_authenticated_users_can_filter_app_configs_by_search(): void
    {
        $user = User::factory()->create();
        AppConfig::factory()->create(['key' => 'unique_special_key', 'value' => 'custom_val']);
        AppConfig::factory()->create(['key' => 'another_standard_key', 'value' => 'other_val']);

        $response = $this->actingAs($user)->get(route('app-configs.index', ['search' => 'special']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('app-configs/Index')
            ->has('configs.data', 1)
            ->where('configs.data.0.key', 'unique_special_key')
        );
    }

    public function test_authenticated_users_can_create_app_config(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('app-configs.store'), [
            'key' => 'custom_setting_key',
            'value' => 'my-secret-key-123',
            'description' => 'Keterangan konfigurasi kustom',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('app_configs', [
            'key' => 'custom_setting_key',
            'value' => 'my-secret-key-123',
            'description' => 'Keterangan konfigurasi kustom',
        ]);
    }

    public function test_cannot_create_app_config_with_duplicate_key(): void
    {
        $user = User::factory()->create();
        AppConfig::factory()->create(['key' => 'existing_key']);

        $response = $this->actingAs($user)->post(route('app-configs.store'), [
            'key' => 'existing_key',
            'value' => 'new value',
        ]);

        $response->assertSessionHasErrors(['key']);
    }

    public function test_authenticated_users_can_update_app_config(): void
    {
        $user = User::factory()->create();
        $config = AppConfig::factory()->create([
            'key' => 'test_key',
            'value' => 'old_value',
            'description' => 'old_description',
        ]);

        $response = $this->actingAs($user)->put(route('app-configs.update', $config), [
            'key' => 'test_key_updated',
            'value' => 'new_value',
            'description' => 'new_description',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('app_configs', [
            'id' => $config->id,
            'key' => 'test_key_updated',
            'value' => 'new_value',
            'description' => 'new_description',
        ]);
    }

    public function test_authenticated_users_can_delete_app_config(): void
    {
        $user = User::factory()->create();
        $config = AppConfig::factory()->create();

        $response = $this->actingAs($user)->delete(route('app-configs.destroy', $config));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('app_configs', [
            'id' => $config->id,
        ]);
    }

    public function test_authenticated_users_can_view_rajaongkir_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('configurations.rajaongkir'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('configurations/RajaOngkir')
            ->has('config')
            ->where('config.key', 'rajaongkir_api_key')
        );
    }

    public function test_authenticated_users_can_update_rajaongkir_config(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('configurations.rajaongkir.update'), [
            'api_key' => 'ro_api_key_sample_12345',
            'description' => 'RajaOngkir API Key Updated',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('app_configs', [
            'key' => 'rajaongkir_api_key',
            'value' => 'ro_api_key_sample_12345',
            'description' => 'RajaOngkir API Key Updated',
        ]);
        $this->assertSame('ro_api_key_sample_12345', AppConfig::getValue('rajaongkir_api_key'));
    }

    public function test_authenticated_users_can_view_geoapify_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('configurations.geoapify'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('configurations/Geoapify')
            ->has('config')
            ->where('config.key', 'geoapify_api_key')
        );
    }

    public function test_authenticated_users_can_update_geoapify_config(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('configurations.geoapify.update'), [
            'api_key' => 'geo_sample_key_998877',
            'description' => 'Geoapify API Key Testing',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('app_configs', [
            'key' => 'geoapify_api_key',
            'value' => 'geo_sample_key_998877',
            'description' => 'Geoapify API Key Testing',
        ]);
        $this->assertSame('geo_sample_key_998877', AppConfig::getValue('geoapify_api_key'));
    }

    public function test_app_config_seeder_seeds_rajaongkir_and_geoapify_keys(): void
    {
        $this->seed(AppConfigSeeder::class);

        $this->assertDatabaseHas('app_configs', [
            'key' => 'rajaongkir_api_key',
            'value' => null,
        ]);

        $this->assertDatabaseHas('app_configs', [
            'key' => 'geoapify_api_key',
            'value' => null,
        ]);
    }

    public function test_app_config_model_helpers(): void
    {
        $this->assertNull(AppConfig::getValue('non_existent_key'));
        $this->assertSame('default_val', AppConfig::getValue('non_existent_key', 'default_val'));

        AppConfig::setValue('custom_key', 'custom_value', 'Custom Description');
        $this->assertSame('custom_value', AppConfig::getValue('custom_key'));

        AppConfig::setValue('custom_key', 'updated_value');
        $this->assertSame('updated_value', AppConfig::getValue('custom_key'));
    }
}
