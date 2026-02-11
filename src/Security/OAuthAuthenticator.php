<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
        private string $frontendUrl,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'api_auth_oauth_check';
    }

    public function authenticate(Request $request): Passport
    {
        /** @var string $provider */
        $provider = $request->attributes->get('provider');
        $client = $this->clientRegistry->getClient($provider);
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $provider) {
                $oauthUser = $client->fetchUserFromToken($accessToken);

                $email = '';
                $oauthId = '';

                if ($oauthUser instanceof GoogleUser) {
                    $email = $oauthUser->getEmail() ?? '';
                    $oauthId = is_string($oauthUser->getId()) ? $oauthUser->getId() : '';
                } elseif ($oauthUser instanceof FacebookUser) {
                    $email = $oauthUser->getEmail() ?? '';
                    $oauthId = is_string($oauthUser->getId()) ? $oauthUser->getId() : '';
                }

                $user = match ($provider) {
                    'google' => $this->userRepository->findOneBy(['googleId' => $oauthId]),
                    'facebook' => $this->userRepository->findOneBy(['facebookId' => $oauthId]),
                    default => null,
                };

                if (!$user) {
                    $user = $this->userRepository->findOneBy(['email' => $email]);
                }

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);

                    $profile = new Profile();
                    $profile->setUser($user);

                    if ($oauthUser instanceof GoogleUser) {
                        $profile->setDisplayName($oauthUser->getName());
                        $profile->setAvatarUrl($oauthUser->getAvatar());
                    } elseif ($oauthUser instanceof FacebookUser) {
                        $profile->setDisplayName($oauthUser->getName());
                        $pictureUrl = $oauthUser->getPictureUrl();
                        if ($pictureUrl) {
                            $profile->setAvatarUrl($pictureUrl);
                        }
                    }

                    $user->setProfile($profile);
                    $this->entityManager->persist($profile);
                }

                if ($provider === 'google') {
                    $user->setGoogleId($oauthId);
                } elseif ($provider === 'facebook') {
                    $user->setFacebookId($oauthId);
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $jwt = $this->jwtManager->create($user);

        $redirectUrl = sprintf('%s/auth/callback?token=%s', $this->frontendUrl, $jwt);

        return new RedirectResponse($redirectUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $redirectUrl = sprintf('%s/auth/callback?error=%s', $this->frontendUrl, urlencode($exception->getMessage()));

        return new RedirectResponse($redirectUrl);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }
}
