<?php

namespace App\Controller;

use App\Entity\AdminLog;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TwoFactorController extends AbstractController
{
    public function __construct(private AdminLogger $adminLogger) {}

    #[Route('/2fa/check', name: 'app_2fa_check', methods: ['GET', 'POST'])]
    public function check(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('_2fa_user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(\App\Entity\User::class)->find($userId);
        if (!$user) {
            $request->getSession()->remove('_2fa_user_id');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code', ''));

            $secret = $user->getTwoFactorSecret();
            if ($secret && $this->verifyTotpCode($secret, $code)) {
                $request->getSession()->remove('_2fa_user_id');

                $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                    $user, 'main', $user->getRoles()
                );
                $this->container->get('security.token_storage')->setToken($token);
                $request->getSession()->set('_security_main', serialize($token));

                $this->adminLogger->log($user, AdminLog::ACTION_LOGIN, null, 'via 2FA');

                $roles = $user->getRoles();
                if (in_array('ROLE_SUPER_ADMIN', $roles) || in_array('ROLE_ADMIN', $roles)) {
                    return $this->redirectToRoute('admin_dashboard');
                }
                return $this->redirectToRoute('app_accueil');

            } else {
                $this->addFlash('2fa_error', 'Code incorrect. Réessayez.');
            }
        }

        return $this->render('user/2fa_check.html.twig', ['user' => $user]);
    }

    #[Route('/profile/2fa/enable', name: 'app_2fa_enable', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function enable(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('GET')) {
            $totp = TOTP::generate();

            // ligne 73 : setLabel exige non-empty-string
            $email = $user->getEmail();
            if ($email) {
                $totp->setLabel($email);
            }
            $totp->setIssuer('TunisiaJourney');

            $secret = $totp->getSecret();
            $request->getSession()->set('_2fa_pending_secret', $secret);

            return $this->render('user/2fa_enable.html.twig', [
                'secret'         => $secret,
                'secret_display' => $this->formatSecret($secret),
                'qr_uri'         => $totp->getProvisioningUri(),
            ]);
        }

        // POST — vérification du code
        $secret = $request->getSession()->get('_2fa_pending_secret');
        $code   = trim((string) $request->request->get('code', ''));

        if (!$secret) {
            $this->addFlash('error', 'Session expirée. Recommencez.');
            return $this->redirectToRoute('app_2fa_enable');
        }

        // $secret est non-empty ici car on a vérifié !$secret au-dessus
        if ($this->verifyTotpCode((string) $secret, $code)) {
            $user->setTwoFactorSecret((string) $secret);
            $user->setIsTotpEnabled(true);
            $em->flush();

            $request->getSession()->remove('_2fa_pending_secret');

            $this->adminLogger->log($user, AdminLog::ACTION_EDIT_PROFILE,
                'soi-même (id=' . $user->getId() . ')', '2FA activé');

            $this->addFlash('success', '✅ Double authentification activée avec succès !');
            return $this->redirectToRoute('app_accueil', ['openProfile' => '1']);
        }

        // Code incorrect → réafficher avec même QR
        $totp = TOTP::createFromSecret((string) $secret);

        // ligne 114 : setLabel exige non-empty-string
        $email = $user->getEmail();
        if ($email) {
            $totp->setLabel($email);
        }
        $totp->setIssuer('TunisiaJourney');

        $this->addFlash('2fa_error', 'Code incorrect. Attendez le prochain code (30s) et réessayez.');

        return $this->render('user/2fa_enable.html.twig', [
            'secret'         => $secret,
            'secret_display' => $this->formatSecret((string) $secret),
            'qr_uri'         => $totp->getProvisioningUri(),
        ]);
    }

    #[Route('/profile/2fa/disable', name: 'app_2fa_disable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function disable(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('2fa_disable', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_accueil', ['openProfile' => '1']);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $user->setTwoFactorSecret(null);
        $user->setIsTotpEnabled(false);
        $em->flush();

        $this->adminLogger->log($user, AdminLog::ACTION_EDIT_PROFILE,
            'soi-même (id=' . $user->getId() . ')', '2FA désactivé');

        $this->addFlash('success', '2FA désactivé.');
        return $this->redirectToRoute('app_accueil', ['openProfile' => '1']);
    }

    // lignes 153, 154 : non-empty-string requis → vérification avant appel
    private function verifyTotpCode(string $secret, string $code): bool
    {
        // Vérifier que secret et code sont non-vides
        if ($secret === '' || $code === '') {
            return false;
        }

        try {
            $totp = TOTP::createFromSecret($secret);
            return $totp->verify($code, null, 2);
        } catch (\Exception) {
            return false;
        }
    }

    private function formatSecret(string $secret): string
    {
        $clean = strtoupper(str_replace([' ', '-'], '', $secret));
        return implode(' ', str_split($clean, 4));
    }
}
