<?php

namespace YakNet\YAuth\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class YakNetProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Base URL for the YakNet Auth server.
     */
    protected string $baseUrl = 'https://auth.yakhub.com.tr';

    /**
     * Constructs the provider instance.
     *
     * @param array<string, mixed> $options Configurable options.
     * @param array<string, mixed> $collaborators Optional collaborators.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (isset($options['baseUrl']) && is_string($options['baseUrl'])) {
            $this->baseUrl = rtrim($options['baseUrl'], '/');
        }
    }

    /**
     * Get base authorization URL to begin OAuth flow.
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->baseUrl . '/oauth/authorize';
    }

    /**
     * Get base access token URL to exchange code for token.
     *
     * @param array<string, mixed> $params Additional parameter mapping.
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->baseUrl . '/oauth/token';
    }

    /**
     * Get provider URL to fetch user details.
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->baseUrl . '/api/user';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * @return string[]
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @param ResponseInterface $response HTTP response interface.
     * @param array<string, mixed>|string $data Decoded JSON or raw response body data.
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            $message = '';
            if (is_array($data)) {
                if (isset($data['message']) && is_string($data['message'])) {
                    $message = $data['message'];
                } elseif (isset($data['error']) && is_string($data['error'])) {
                    $message = $data['error'];
                }
            } else {
                $message = $data;
            }

            if (empty($message)) {
                $message = $response->getReasonPhrase();
            }

            throw new IdentityProviderException(
                $message,
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array<string, mixed> $response Raw user endpoint payload response.
     * @param AccessToken $token Token object used to perform auth.
     */
    protected function createResourceOwner(array $response, AccessToken $token): YakNetResourceOwner
    {
        return new YakNetResourceOwner($response);
    }

    /**
     * Get the base URL of the YakNet Auth server.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Generates a fully-functioning login button widget HTML.
     *
     * @param string $state Secure state token to prevent CSRF.
     * @param array<string, mixed> $options Custom options (e.g. 'theme' => 'dark'|'light', 'text' => 'YakNet ile Kayıt Ol').
     */
    public function getLoginButtonHtml(string $state = '', array $options = []): string
    {
        $clientId = htmlspecialchars((string) $this->clientId, ENT_QUOTES, 'UTF-8');
        $redirectUri = htmlspecialchars((string) $this->redirectUri, ENT_QUOTES, 'UTF-8');
        $theme = isset($options['theme']) && is_string($options['theme']) ? $options['theme'] : 'light';
        $text = htmlspecialchars(isset($options['text']) && is_string($options['text']) ? $options['text'] : 'YakNet ile Giriş Yap', ENT_QUOTES, 'UTF-8');
        $baseUrl = rtrim((string) $this->baseUrl, '/');
        $stateAttr = htmlspecialchars($state, ENT_QUOTES, 'UTF-8');

        $authUrl = $baseUrl . '/oauth/authorize?client_id=' . urlencode((string) $this->clientId) .
            '&redirect_uri=' . urlencode((string) $this->redirectUri) .
            '&response_type=code&scope=&state=' . urlencode($state);

        $isDark = ($theme === 'dark');
        $bgColor = $isDark ? '#1e293b' : '#ffffff';
        $textColor = $isDark ? '#ffffff' : '#0f172a';
        $borderColor = $isDark ? '#334155' : '#cbd5e1';

        return <<<HTML
<script src="{$baseUrl}/js/y-auth-button.js?v=1.2.0" async defer></script>
<yaknet-login-button 
    client-id="{$clientId}" 
    redirect-uri="{$redirectUri}" 
    state="{$stateAttr}" 
    theme="{$theme}"
    text="{$text}"
    base-url="{$baseUrl}"
    style="display: block; width: 100%;">
    <a href="{$authUrl}" class="yaknet-btn" style="display: inline-flex; align-items: center; justify-content: center; width: 100%; box-sizing: border-box; background-color: {$bgColor}; color: {$textColor}; border: 1px solid {$borderColor}; padding: 10px 20px; border-radius: 8px; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); user-select: none; white-space: nowrap;">
        <svg style="width: 20px; height: 20px; margin-right: 12px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#3B82F6"/>
            <path d="M2 17L12 22L22 17" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        {$text}
    </a>
</yaknet-login-button>
HTML;
    }
}
