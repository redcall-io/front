<?php

namespace Bundles\ApiBundle\Security\Authenticator;

use Bundles\ApiBundle\Entity\Token;
use Bundles\ApiBundle\Error\AuthenticationError;
use Bundles\ApiBundle\Exception\ApiAuthenticationException;
use Bundles\ApiBundle\Exception\ApiException;
use Bundles\ApiBundle\Manager\TokenManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var TokenManager
     */
    private $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw new ApiAuthenticationException(
            AuthenticationError::AUTHENTICATION_REQUIRED()
        );
    }

    public function supports(Request $request)
    {
        return preg_match('|^/api/|', $request->getPathInfo());
    }

    public function getCredentials(Request $request)
    {
        if (!$authorization = $request->headers->get('Authorization')) {
            throw new ApiAuthenticationException(
                AuthenticationError::AUTHENTICATION_NO_AUTHORIZATION()
            );
        }

        $matches = [];
        if (!preg_match('|^Bearer (?P<uuid>[0-9a-f]{8}\-[0-9a-f]{4}\-4[0-9a-f]{3}\-[89ab][0-9a-f]{3}\-[0-9a-f]{12})$|', $authorization, $matches)) {
            throw new ApiAuthenticationException(
                AuthenticationError::AUTHENTICATION_NO_TOKEN()
            );
        }

        if (!$signature = $request->headers->get('X-Signature')) {
            throw new ApiAuthenticationException(
                AuthenticationError::AUTHENTICATION_NO_SIGNATURE()
            );
        }

        // Cannot use $request->getUri() because Symfony sorts the query string alphabetically,
        // which may break the request signature.
        $uri = $request->getSchemeAndHttpHost().$request->getRequestUri();

        return [
            'token'     => $this->tokenManager->findToken($matches['uuid']),
            'signature' => $signature,
            'method'    => $request->getMethod(),
            'uri'       => $uri,
            'body'      => $request->getContent(),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var Token $token */
        $token = $credentials['token'];

        if (!$token) {
            return null;
        }

        $this->tokenManager->increaseHitCount($token);

        return $userProvider->loadUserByUsername(
            $token->getUsername()
        );
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $credentials['signature'] === $credentials['token']->sign($credentials['method'], $credentials['uri'], $credentials['body']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($exception instanceof ApiAuthenticationException) {
            throw new ApiException(
                $exception->getError()
            );
        }

        throw new ApiException(
            AuthenticationError::AUTHENTICATION_FAILED()
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // Go ahead buddy!
    }

    public function supportsRememberMe()
    {
        return false;
    }
}