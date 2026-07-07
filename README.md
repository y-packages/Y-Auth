# YakNet Auth Client SDK (`yaknet/y-auth`)

Official PHP integration SDK for the **YakNet Identity Verification Platform** (`auth.yakhub.com.tr`). This library is built on top of the robust and secure `league/oauth2-client` standard.

---

## Installation

You can install this package using Composer:

```bash
composer require yaknet/y-auth
```

---

## Basic Usage

### 1. Initialize the Provider

Create an instance of the provider with your client credentials registered in the YakNet Geliştirici Portalı:

```php
use YakNet\YAuth\Provider\YakNetProvider;

$provider = new YakNetProvider([
    'clientId'     => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri'  => 'https://your-app.com/callback',
    // Optional: Defaults to https://auth.yakhub.com.tr
    'baseUrl'      => 'https://auth.yakhub.com.tr'
]);
```

### 2. Redirect to Authorization URL

Redirect the user to the provider's authorization endpoint. We generate and store a secure state parameter to prevent CSRF attacks:

```php
session_start();

// Get the authorization URL
$authorizationUrl = $provider->getAuthorizationUrl();

// Save the state to the session
$_SESSION['oauth2state'] = $provider->getState();

// Redirect the user
header('Location: ' . $authorizationUrl);
exit;
```

### 3. Handle the Callback & Get Access Token

In your callback page (`redirectUri`), verify the state parameter and request the access token using the code parameter:

```php
session_start();

$state = $_GET['state'] ?? null;
$code = $_GET['code'] ?? null;

// Validate state
if (empty($state) || empty($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']);
    exit('Geçersiz state parametresi (CSRF koruması tetiklendi).');
}

// Clear state from session
unset($_SESSION['oauth2state']);

try {
    // Exchange Authorization Code for Access Token
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $code
    ]);

    // Token details
    $accessToken = $token->getToken();
    $refreshToken = $token->getRefreshToken();
    $expires = $token->getExpires();

    // Store tokens in your database or session
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['refresh_token'] = $refreshToken;

    // Optional: Retrieve the authenticated user's profile
    $user = $provider->getResourceOwner($token);

    // Profile Details
    $userId = $user->getId();
    $name = $user->getName();
    $email = $user->getEmail();
    $rawProfile = $user->toArray();

    echo "Başarıyla Giriş Yapıldı! Hoş geldiniz, " . htmlspecialchars($name);

} catch (Exception $e) {
    exit('Giriş Hatası: ' . $e->getMessage());
}
```

### 4. Refreshing an Expired Access Token

If your access token expires, you can use the stored refresh token to request a new access token:

```php
use League\OAuth2\Client\Grant\RefreshToken;

try {
    $newToken = $provider->getAccessToken(new RefreshToken(), [
        'refresh_token' => $_SESSION['refresh_token']
    ]);

    $_SESSION['access_token'] = $newToken->getToken();
    $_SESSION['refresh_token'] = $newToken->getRefreshToken();
} catch (Exception $e) {
    // Handle refresh token failure (e.g., redirect to login)
}
```

---

## Testing

Run unit tests via PHPUnit:

```bash
vendor/bin/phpunit
```

Run static code analysis via PHPStan:

```bash
vendor/bin/phpstan analyse
```

---

## License

This project is developed by **YakNet Bilişim** and licensed under the **MIT License**.
