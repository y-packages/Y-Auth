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
}
