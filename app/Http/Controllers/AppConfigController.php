<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppConfig\IndexAppConfigRequest;
use App\Http\Requests\AppConfig\StoreAppConfigRequest;
use App\Http\Requests\AppConfig\UpdateAppConfigRequest;
use App\Models\AppConfig;
use App\Services\AppConfigService;
use App\Services\RajaOngkirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AppConfigController extends Controller
{
    public function __construct(
        protected AppConfigService $appConfigService
    ) {}

    /**
     * Display a listing of app configurations.
     */
    public function index(IndexAppConfigRequest $request): Response
    {
        $configs = $this->appConfigService->getPaginatedConfigs(
            (int) $request->input('per_page', 10),
            $request->input('search'),
            (string) $request->input('sort', 'created_at'),
            (string) $request->input('direction', 'desc')
        );

        return Inertia::render('app-configs/Index', [
            'configs' => $configs,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    /**
     * Store a newly created configuration.
     */
    public function store(StoreAppConfigRequest $request): RedirectResponse
    {
        try {
            $this->appConfigService->createConfig($request->validated());

            return redirect()->back()->with('success', 'Konfigurasi berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan konfigurasi: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified configuration.
     */
    public function update(UpdateAppConfigRequest $request, AppConfig $app_config): RedirectResponse
    {
        try {
            $this->appConfigService->updateConfig($app_config->id, $request->validated());

            return redirect()->back()->with('success', 'Konfigurasi berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui konfigurasi: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified configuration.
     */
    public function destroy(AppConfig $app_config): RedirectResponse
    {
        try {
            $this->appConfigService->deleteConfig($app_config->id);

            return redirect()->back()->with('success', 'Konfigurasi berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menghapus konfigurasi: '.$e->getMessage());
        }
    }

    /**
     * Show RajaOngkir configuration page.
     */
    public function rajaongkir(): Response
    {
        $config = AppConfig::firstOrCreate(
            ['key' => 'rajaongkir_api_key'],
            [
                'value' => null,
                'description' => 'API Key untuk layanan integrasi RajaOngkir / Komerce',
            ]
        );

        $baseUrlConfig = AppConfig::firstOrCreate(
            ['key' => 'rajaongkir_base_url'],
            [
                'value' => 'https://rajaongkir.komerce.id',
                'description' => 'Base URL endpoint API untuk layanan RajaOngkir / Komerce',
            ]
        );

        return Inertia::render('configurations/RajaOngkir', [
            'config' => $config,
            'baseUrlConfig' => $baseUrlConfig,
        ]);
    }

    /**
     * Update RajaOngkir configuration.
     */
    public function updateRajaongkir(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:500'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->appConfigService->setOrUpdate(
            'rajaongkir_api_key',
            $validated['api_key'] ?? null,
            $validated['description'] ?? 'API Key untuk layanan integrasi RajaOngkir / Komerce'
        );

        $this->appConfigService->setOrUpdate(
            'rajaongkir_base_url',
            ! empty($validated['base_url']) ? rtrim($validated['base_url'], '/') : 'https://rajaongkir.komerce.id',
            'Base URL endpoint API untuk layanan RajaOngkir / Komerce'
        );

        // Clear and pre-warm RajaOngkir cache
        $rajaOngkir = app(RajaOngkirService::class);
        $rajaOngkir->clearCache();
        $rajaOngkir->getBaseUrl();
        $rajaOngkir->getApiKey();

        $this->appConfigService->setOrUpdate(
            'rajaongkir_base_url',
            ! empty($validated['base_url']) ? rtrim($validated['base_url'], '/') : 'https://rajaongkir.komerce.id',
            'Base URL endpoint API untuk layanan RajaOngkir / Komerce'
        );

        // Clear and pre-warm RajaOngkir cache
        $rajaOngkir = app(RajaOngkirService::class);
        $rajaOngkir->clearCache();
        $rajaOngkir->getBaseUrl();
        $rajaOngkir->getApiKey();

        return redirect()->back()->with('success', 'Konfigurasi RajaOngkir berhasil diperbarui.');
    }

    /**
     * Show Geoapify configuration page.
     */
    public function geoapify(): Response
    {
        $config = AppConfig::firstOrCreate(
            ['key' => 'geoapify_api_key'],
            [
                'value' => env('GEOAPIFY_API_KEY', 'a5eede7836f74f4aa012b9ff68d7b5ec'),
                'description' => 'API Key untuk layanan Geocoding dan Peta Geoapify',
            ]
        );

        $baseUrlConfig = AppConfig::firstOrCreate(
            ['key' => 'geoapify_base_url'],
            [
                'value' => 'https://api.geoapify.com',
                'description' => 'Base URL endpoint API untuk layanan Geocoding dan Peta Geoapify',
            ]
        );

        return Inertia::render('configurations/Geoapify', [
            'config' => $config,
            'baseUrlConfig' => $baseUrlConfig,
        ]);
    }

    /**
     * Show Firebase configuration page.
     */
    public function firebase(): Response
    {
        $config = AppConfig::firstOrCreate(
            ['key' => 'firebase_service_account_json'],
            [
                'value' => null,
                'description' => 'Konfigurasi kredensial Service Account JSON untuk Push Notification FCM',
            ]
        );

        return Inertia::render('configurations/Firebase', [
            'config' => $config,
        ]);
    }

    /**
     * Update Firebase configuration.
     */
    public function updateFirebase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_account_json' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->appConfigService->setOrUpdate(
            'firebase_service_account_json',
            $validated['service_account_json'] ?? null,
            $validated['description'] ?? 'Konfigurasi kredensial Service Account JSON untuk Push Notification FCM'
        );

        Cache::forget('app_config_firebase_service_account_json');

        return redirect()->back()->with('success', 'Konfigurasi Firebase berhasil diperbarui.');
    }
}
