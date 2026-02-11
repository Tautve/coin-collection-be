<?php

declare(strict_types=1);

namespace App\Controller\Api;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/oauth')]
class OAuthController extends AbstractController
{
    public function __construct(
        private ClientRegistry $clientRegistry,
    ) {
    }

    #[Route('/connect/{provider}', name: 'api_auth_oauth_connect', methods: ['GET'])]
    public function connect(string $provider, Request $request): RedirectResponse
    {
        $scopes = match ($provider) {
            'google' => ['email', 'profile'],
            'facebook' => ['email', 'public_profile'],
            default => throw $this->createNotFoundException('Provider not supported'),
        };

        return $this->clientRegistry
            ->getClient($provider)
            ->redirect($scopes, []);
    }

    #[Route('/check/{provider}', name: 'api_auth_oauth_check', methods: ['GET'])]
    public function check(string $provider): JsonResponse
    {
        return $this->json(['message' => 'OAuth authentication handled by authenticator']);
    }
}
