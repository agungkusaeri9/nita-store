<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use Illuminate\Database\Seeder;

class AppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => 'rajaongkir_api_key',
                'value' => null,
                'description' => 'API Key untuk layanan integrasi RajaOngkir / Komerce',
            ],
            [
                'key' => 'rajaongkir_base_url',
                'value' => 'https://rajaongkir.komerce.id',
                'description' => 'Base URL endpoint API untuk layanan RajaOngkir / Komerce',
            ],
            [
                'key' => 'geoapify_api_key',
                'value' => null,
                'description' => 'API Key untuk layanan Geocoding dan Peta Geoapify',
            ],
            [
                'key' => 'geoapify_base_url',
                'value' => 'https://api.geoapify.com',
                'description' => 'Base URL endpoint API untuk layanan Geocoding dan Peta Geoapify',
            ],
            [
                'key' => 'firebase_service_account_json',
                'value' => json_encode([
                    'type' => 'service_account',
                    'project_id' => 'heritagebatiklaweyan-testing',
                    'private_key_id' => '8b82d84a27d9234bba70847c3c65ca0f4a74e3ee',
                    'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCpgW9Q6p0nESRP\n+TSvtlw+vXUlN3x72gOuw0UuAlIR6vvS7CZVUTsxcAtLj77GiGTQY8NIKpP9rPOd\nICFq07B0T6QSLTsD6XnFP2lDbTBSu2ydppCJdztIqP0LdRvtDRhMxFKG1xKYG39y\nAdXWqBINS49uOgrijYVq5bgeoNw0ahFgrr0zLmBt2peg9PtD51qWDPic3vp6jrn+\nSFO7CN1kH2RAMC32Sz9xvcuhPbHhqH0W5D0/5tot5EadlIpJOmQyd6bjRtDOi7Z4\ny271irqRyoKO02fKI7of+/1Ljub1dL98yUdXx6ac56ZsfSX2BialHED7fsHWnKtG\neBl2T8e7AgMBAAECgf8ibiguR3mkddbqZ7RL/L6//7vL03l1rtMQrJlgvxfsi86b\nCVy/98AOSrQApTIbIZlYuurvvbZR0M+rGtuu11EATy0EhCt4n/QcHMVE7Ojj5S4q\n16qEQbPJDopXyXHitMY60aXxvb4JJ4y7m6PT9k+0Y6S+vwt2+Uggboj3r7UK8Cv3\nHUix3Yv4/CJdYVOwSYdRrjprsXJuLsdZgVbw63BTNhCnMWNxfDOHxeCwpx43FDO4\nlJfOim6ZXFC3ZR9g9L8eeqy3gfdQDqEAW02COm42ySNFwz7KMlwVeSXpMTaeWEkp\nBHD3qrMDaZXcNqez8JIWVxkQou5CG1XNCvMOysECgYEA0JRWIVRmW433hQndC62q\nIVYSo2bL4b7sNjchWG6GLU86WGH7sIX6sjIcNQfSLTyAT0eVQB9WmS/0TuWUowCv\nW2CsBEp4IoGZeXIexQaJgK+JntX8N0wrWfeV6gm/jO5BZs/24jCYLqz65mo94kuX\nXaZQw7JZ/iAlDkQSNI/ksfkCgYEA0Arv/0VuNucdn6NUh90P2LoGaGexSVr/BhSG\nDMs75QfZR5zvXMShEhhflFHinekcR2j+rHOY7Jx/KJIKitvZL9Wba3pfAuCGN9CX\neV2fEFJrdyC3GhRo3sbCeMAibqbp+KZoDJzOpdHWHDR3zextcD87zvuuocUNTrHE\nqVP/tFMCgYEAo5fUvit+ZMzCmHwV/XTmfWMPF04nFxYyGou/AaceYidD8dJYICnV\naTJYYVeTB5vuX0Ab1cE+oMO18LOskvSUlS0LfgoSk63mfBSAAbSQ4HfNjuDzA0Qg\ntF22HWwr6xNsBIGUd+/5TJKr71Dg3L7BJ9ise/FFGEaKCdhmFwfNLbECgYBeLqVp\nth/oIQbrL0DkZPJ4lfayq0a6Q8SyFvZ8skECNWRrJCPEpouYqooSlmN2Q4L23RR2\nM7rmWw7B6iN1q4OOQTCkHnkGz2hTuk3otrZ86CMYgioCV30GZYrCqZCs8Ul4tLTk\nCNUQgodgq2wVQFRm/WlxEcWEHLYiix3R3mIjWwKBgQC7Q5zVrQMm3S3Xs2GUdLiO\nUyTknJBOiJqydUm4sgbBlL8dLamtRLZN3lgP/U+cTld157zAi+XrdF1S7Uj4akMp\nCf7O6LxYfoDbqXRh01P8693P3sWqKW/xv4TWnAL4xgNp3v8pnv+zIgNqFenm70K3\nHHcZQtYZsEBeltNq71fsbg==\n-----END PRIVATE KEY-----\n",
                    'client_email' => 'firebase-adminsdk-fbsvc@heritagebatiklaweyan-testing.iam.gserviceaccount.com',
                    'client_id' => '100682996271128997397',
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                    'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40heritagebatiklaweyan-testing.iam.gserviceaccount.com',
                    'universe_domain' => 'googleapis.com',
                ]),
                'description' => 'Konfigurasi kredensial Service Account JSON untuk Push Notification FCM',
            ],
        ];

        foreach ($configs as $config) {
            AppConfig::firstOrCreate(
                ['key' => $config['key']],
                [
                    'value' => $config['value'],
                    'description' => $config['description'],
                ]
            );
        }
    }
}
