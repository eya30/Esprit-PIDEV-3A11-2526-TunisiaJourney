<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PasswordResetToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ): Response {

        if ($request->isMethod('GET')) {
            return $this->render('user/forgot_password.html.twig', [
                'sent'       => false,
                'sent_email' => null,
            ]);
        }

        // Vérification CSRF — (string) cast pour PHPStan
        if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('reset_error', 'Token invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $email = strtolower(trim((string) $request->request->get('email', '')));

        $user = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('LOWER(u.email) = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        if ($user) {
            $oldTokens = $em->getRepository(PasswordResetToken::class)->findBy(['user' => $user]);
            foreach ($oldTokens as $old) {
                $em->remove($old);
            }
            $em->flush();

            $rawToken = bin2hex(random_bytes(32));

            $resetToken = new PasswordResetToken();
            $resetToken->setUser($user);
            $resetToken->setToken($rawToken);
            $resetToken->setExpiresAt(new \DateTime('+1 hour'));

            $em->persist($resetToken);
            $em->flush();

            $resetUrl = $this->generateUrl(
                'app_reset_password',
                ['token' => $rawToken],
                \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
            );

            $emailError = $this->sendResetEmail($httpClient, $params, $user, $resetUrl);

            if ($emailError !== null) {
                $this->addFlash('reset_error', 'Erreur envoi email : ' . $emailError);
                return $this->render('user/forgot_password.html.twig', [
                    'sent'       => false,
                    'sent_email' => null,
                ]);
            }
        }

        return $this->render('user/forgot_password.html.twig', [
            'sent'       => true,
            'sent_email' => $email,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {

        $resetToken = $em->getRepository(PasswordResetToken::class)->findOneBy(['token' => $token]);

        $isInvalid = !$resetToken || $resetToken->getExpiresAt() < new \DateTime();

        if ($isInvalid) {
            if ($resetToken) {
                $em->remove($resetToken);
                $em->flush();
            }
            return $this->render('user/reset_password.html.twig', [
                'invalid' => true,
                'token'   => null,
            ]);
        }

        if ($request->isMethod('GET')) {
            return $this->render('user/reset_password.html.twig', [
                'invalid' => false,
                'token'   => $token,
            ]);
        }

        // (string) cast pour PHPStan
        if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('reset_error', 'Token invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_reset_password', ['token' => $token]);
        }

        $newPassword     = (string) $request->request->get('new_password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');

        // (string) cast pour PHPStan
        if (strlen($newPassword) < 8) {
            $this->addFlash('reset_error', 'Le mot de passe doit contenir au moins 8 caractères.');
            return $this->redirectToRoute('app_reset_password', ['token' => $token]);
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('reset_error', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_reset_password', ['token' => $token]);
        }

        // Vérification que $user n'est pas null (ligne 150)
        $user = $resetToken->getUser();
        if (!$user instanceof User) {
            $this->addFlash('reset_error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $user->setMotDePasse($hasher->hashPassword($user, $newPassword));

        $em->remove($resetToken);
        $em->flush();

        $this->addFlash('success', 'Mot de passe modifié avec succès ! Vous pouvez vous connecter.');
        return $this->redirectToRoute('app_login');
    }

    private function sendResetEmail(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        User $user,
        string $resetUrl
    ): ?string {

        $apiKey      = $params->get('brevo_api_key');
        $senderEmail = $params->get('brevo_sender_email');

        if (empty($apiKey) || $apiKey === 'xkeysib-XXXXXXXX') {
            return 'BREVO_API_KEY manquante ou invalide dans .env.local';
        }
        if (empty($senderEmail)) {
            return 'BREVO_SENDER_EMAIL manquant dans .env.local';
        }

        try {
            $response = $httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'api-key'      => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'sender' => [
                        'name'  => 'TunisiaJourney',
                        'email' => $senderEmail,
                    ],
                    'to' => [
                        [
                            'email' => $user->getEmail(),
                            'name'  => $user->getPrenom() . ' ' . $user->getNom(),
                        ]
                    ],
                    'subject' => 'Réinitialisation de votre mot de passe — TunisiaJourney',
                    'htmlContent' => $this->buildEmailHtml($user, $resetUrl),
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 201 && $statusCode !== 200) {
                $body = $response->getContent(false);
                return 'Brevo a répondu avec le code ' . $statusCode . ' : ' . $body;
            }

            return null;

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function buildEmailHtml(User $user, string $resetUrl): string
    {
        // (string) cast pour PHPStan ligne 225
        $prenom = htmlspecialchars((string) $user->getPrenom());
        $url    = htmlspecialchars($resetUrl);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#F5EDD8;font-family:'DM Sans',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F5EDD8;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
          <tr>
            <td style="background:linear-gradient(135deg,#C94040 0%,#A0231F 50%,#2A1010 100%);padding:40px 40px 30px;text-align:center;">
              <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.15);margin:0 auto 16px;border:2px solid rgba(255,255,255,0.3);display:inline-flex;align-items:center;justify-content:center;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <h1 style="color:white;font-size:28px;font-weight:700;margin:0 0 6px;">Tunisia<span style="opacity:0.7">Journey</span></h1>
              <p style="color:rgba(255,255,255,0.7);font-size:12px;letter-spacing:2px;margin:0;text-transform:uppercase;">Réinitialisation du mot de passe</p>
            </td>
          </tr>
          <tr>
            <td style="padding:40px 44px;">
              <p style="color:#1A2E4A;font-size:18px;font-weight:600;margin:0 0 12px;">Bonjour {$prenom} 👋</p>
              <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 28px;">
                Vous avez demandé la réinitialisation de votre mot de passe.<br>
                Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.<br>
                Ce lien est valable <strong>1 heure</strong>.
              </p>
              <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
                <tr>
                  <td style="border-radius:50px;background:linear-gradient(90deg,#A0231F,#C0392B);box-shadow:0 5px 16px rgba(160,35,31,0.35);">
                    <a href="{$url}" style="display:inline-block;padding:16px 40px;color:white;font-size:16px;font-weight:700;text-decoration:none;border-radius:50px;letter-spacing:0.3px;">
                      Réinitialiser mon mot de passe
                    </a>
                  </td>
                </tr>
              </table>
              <hr style="border:none;border-top:1px solid #F0E8D8;margin:0 0 24px;">
              <p style="color:#888;font-size:12px;line-height:1.6;margin:0 0 6px;">Si le bouton ne fonctionne pas, copiez ce lien :</p>
              <p style="margin:0;">
                <a href="{$url}" style="color:#A0231F;font-size:12px;word-break:break-all;">{$url}</a>
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#FDF6EC;padding:20px 44px;border-top:1px solid #F0E8D8;">
              <p style="color:#888;font-size:12px;line-height:1.6;margin:0;">
                ⚠️ Si vous n'avez pas demandé cette réinitialisation, ignorez cet email — votre mot de passe ne changera pas.<br>
                Ce lien expirera automatiquement dans 1 heure.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#1A2E4A;padding:20px 44px;text-align:center;">
              <p style="color:rgba(255,255,255,0.5);font-size:11px;margin:0;">
                © 2026 TunisiaJourney. Tous droits réservés.<br>
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}