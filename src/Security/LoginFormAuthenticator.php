<?php

namespace App\Security;

use App\Entity\AdminLog;
use App\Service\AdminLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private AdminLogger           $adminLogger,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->removeTargetPath($request->getSession(), $firewallName);

        /** @var \App\Entity\User $user */
        $user = $token->getUser();

        // Compte bloqué
        if ($user->getStatut() === 'BLOQUE') {
            $request->getSession()->invalidate();
            $request->getSession()->set('_blocked_message', "Votre compte a été bloqué. Contactez l'administrateur.");
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        // ── 2FA activé → intercepter et rediriger vers la page de vérification ──
        if ($user->isTotpEnabled() && $user->getTwoFactorSecret()) {
            // On déconnecte le token de la session pour forcer la vérification 2FA
            $request->getSession()->set('_2fa_user_id', $user->getId());
            // On invalide le token de sécurité — l'utilisateur n'est pas encore connecté
            $request->getSession()->remove('_security_main');

            return new RedirectResponse($this->urlGenerator->generate('app_2fa_check'));
        }

        // LOG connexion normale (sans 2FA)
        $this->adminLogger->log($user, AdminLog::ACTION_LOGIN, null, null);

        $roles = $user->getRoles();
        if (in_array('ROLE_SUPER_ADMIN', $roles) || in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_accueil'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}