<?php

namespace YakNet\YAuth\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class YakNetResourceOwner implements ResourceOwnerInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $response;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->response['id'] ?? null;
    }

    /**
     * Returns the full name of the authorized resource owner.
     */
    public function getName(): ?string
    {
        $name = $this->response['name'] ?? null;
        return is_string($name) ? $name : null;
    }

    /**
     * Returns the email address of the authorized resource owner.
     */
    public function getEmail(): ?string
    {
        $email = $this->response['email'] ?? null;
        return is_string($email) ? $email : null;
    }

    /**
     * Return all of the owner details as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
