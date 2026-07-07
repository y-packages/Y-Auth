<?php

require_once __DIR__ . '/vendor/autoload.php';

use YakNet\YAuth\Provider\YakNetProvider;

// Initialize the provider
$provider = new YakNetProvider([
    'clientId'     => 'test-client-id-12345',
    'clientSecret' => 'test-client-secret-abcde',
    'redirectUri'  => 'https://my-awesome-app.com/callback',
    'baseUrl'      => 'https://auth.yakhub.com.tr'
]);

// Generate state
$state = bin2hex(random_bytes(16));

// Generate Authorization URL
$authUrl = $provider->getAuthorizationUrl([
    'state' => $state,
    'scope' => ['profile', 'email']
]);

echo "\n======================================================\n";
echo "  YakNet y-auth SDK - Demo & Entegrasyon Testi\n";
echo "======================================================\n\n";

echo "Client ID:      test-client-id-12345\n";
echo "Redirect URI:   https://my-awesome-app.com/callback\n";
echo "Generated State: " . $state . "\n\n";
echo "👉 Yönlendirme Bağlantısı (Authorization URL):\n";
echo $authUrl . "\n\n";
echo "======================================================\n\n";
