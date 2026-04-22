<?php

namespace App\Security;

use App\Entity\AdminLog;
use App\Entity\User;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry         $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface        $router,
        private AdminLogger            $adminLogger,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client      = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {

                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email      = strtolower(trim($googleUser->getEmail()));

                $user = $this->em->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->where('LOWER(u.email) = :email')
                    ->setParameter('email', $email)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($user) {
                    if (!$user->getProfileImageUrl() && $googleUser->getAvatar()) {
                        $user->setProfileImageUrl($googleUser->getAvatar());
                        $this->em->flush();
                    }
                    return $user;
                }

                // Nouveau compte Google
                $firstName = $googleUser->getFirstName() ?? '';
                $lastName  = $googleUser->getLastName()  ?? '';
                if (!$firstName && !$lastName) {
                    $parts     = explode(' ', $googleUser->getName() ?? 'Utilisateur Google', 2);
                    $firstName = $parts[0];
                    $lastName  = $parts[1] ?? $parts[0];
                }

                $user = new User();
                $user->setNom($lastName  ?: 'Google');
                $user->setPrenom($firstName ?: 'Utilisateur');
                $user->setEmail($email);
                $user->setMotDePasse('');
                $user->setRole('MEMBRE');
                $user->setStatut(User::STATUT_ACTIF);
                $user->setNiveau(null);
                $user->setDateInscription(new \DateTime());
                $user->setProfileImageUrl($googleUser->getAvatar());
                $user->setFaceToken(null);

                $this->em->persist($user);
                $this->em->flush();

                // LOG inscription Google
                $this->adminLogger->log($user, AdminLog::ACTION_REGISTER, null, 'via Google');

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        /** @var User $user */
        $user = $token->getUser();

        if ($user->getStatut() === User::STATUT_BLOQUE) {
            $request->getSession()->invalidate();
            $request->getSession()->set('_blocked_message', "Votre compte a été bloqué. Contactez l'administrateur.");
            return new RedirectResponse($this->router->generate('app_login'));
        }

        // LOG connexion Google
        $this->adminLogger->log($user, AdminLog::ACTION_LOGIN_GOOGLE, null, null);

        $roles = $user->getRoles();
        if (in_array('ROLE_SUPER_ADMIN', $roles) || in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->router->generate('admin_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_accueil'));
    }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
{
    $session = $request->getSession();
    if ($session instanceof \Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface) {
        $session->getFlashBag()->add('login_error', 'Connexion Google échouée. Veuillez réessayer.');
    }
    return new RedirectResponse($this->router->generate('app_login'));
}
}