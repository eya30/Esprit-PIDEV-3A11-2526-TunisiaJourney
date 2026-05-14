<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginType;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\AdminLog;
use App\Service\AdminLogger;


class AuthController extends AbstractController
{
    public function __construct(private AdminLogger $adminLogger) {}

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $prefillEmail = $request->getSession()->get('prefill_email');
        if ($prefillEmail) {
            $request->getSession()->remove('prefill_email');
            if (!$lastUsername) {
                $lastUsername = $prefillEmail;
            }
        }

        $loginForm        = $this->createForm(LoginType::class);
        $registrationForm = $this->createForm(RegistrationType::class);

        return $this->render('user/auth.html.twig', [
            'loginForm'        => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
            'mode'             => 'login',
            'last_username'    => $lastUsername,
            'error'            => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ParameterBagInterface $params,
        HttpClientInterface $httpClient
    ): Response {
        $user             = new User();
        $loginForm        = $this->createForm(LoginType::class);
        $registrationForm = $this->createForm(RegistrationType::class, $user);

        $registrationForm->handleRequest($request);

        if ($registrationForm->isSubmitted()) {

            if (!$registrationForm->isValid()) {
                return $this->render('user/auth.html.twig', [
                    'loginForm'        => $loginForm->createView(),
                    'registrationForm' => $registrationForm->createView(),
                    'mode'             => 'register',
                    'last_username'    => '',
                    'error'            => null,
                ]);
            }

            $captchaToken = $request->request->get('g-recaptcha-response', '');

            // Vérification reCAPTCHA — ne bloque pas si réseau indisponible
            if (!empty($captchaToken)) {
                try {
                    $response = $httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                        'body' => [
                            'secret'   => $params->get('recaptcha_secret_key'),
                            'response' => $captchaToken,
                            'remoteip' => $request->getClientIp(),
                        ],
                    ]);
                    $captchaData = $response->toArray(false);
                    if (isset($captchaData['success']) && $captchaData['success'] === false) {
                        $this->addFlash('register_error', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                        return $this->render('user/auth.html.twig', [
                            'loginForm'        => $loginForm->createView(),
                            'registrationForm' => $registrationForm->createView(),
                            'mode'             => 'register',
                            'last_username'    => '',
                            'error'            => null,
                        ]);
                    }
                } catch (\Exception $e) {
                    // reCAPTCHA indisponible → on laisse passer
                }
            }

            $plainPassword = $registrationForm->get('mot_de_passe')->getData();
            $user->setMotDePasse($passwordHasher->hashPassword($user, $plainPassword));
            $user->setRole('MEMBRE');
            $user->setStatut(User::STATUT_ACTIF);
            $user->setNiveau(null);
            $user->setDateInscription(new \DateTime());
            $user->setProfileImageUrl(null);
            $user->setFaceToken(null);

            $em->persist($user);
            $em->flush();

            $request->getSession()->set('prefill_email', $user->getEmail());
            $this->addFlash('success', 'Compte créé avec succès !');
            $this->adminLogger->log($user, AdminLog::ACTION_REGISTER, null, 'via formulaire');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/auth.html.twig', [
            'loginForm'        => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
            'mode'             => 'register',
            'last_username'    => '',
            'error'            => null,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method is intercepted by Symfony logout.');
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect(['email', 'profile'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): Response
    {
        throw new \LogicException('Intercepté par GoogleAuthenticator.');
    }

    #[Route('/face-login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): Response {

        $imageBase64 = $request->request->get('image');

        if (!$imageBase64) {
            return $this->json(['error' => 'Image manquante']);
        }

<<<<<<< HEAD
        // ligne 222 : imageBase64 doit être string
        $imageBase64Str = is_string($imageBase64) ? $imageBase64 : '';

=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $users = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.faceEmbedding IS NOT NULL')
            ->getQuery()
            ->getResult();

        if (empty($users)) {
            return $this->json(['error' => 'Aucun utilisateur avec face ID configuré']);
        }

        // ── Trouver le MEILLEUR match (distance minimale) ──
        $bestMatch    = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            $ch = curl_init('http://127.0.0.1:5001/compare');
<<<<<<< HEAD

            // ligne 181 : CURLOPT_POSTFIELDS doit être string, pas false
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
<<<<<<< HEAD
                CURLOPT_POSTFIELDS     => (string) json_encode([
                    'image_base64' => $imageBase64Str,
                    'embedding'    => json_decode((string) $user->getFaceEmbedding(), true),
=======
                CURLOPT_POSTFIELDS     => json_encode([
                    'image_base64' => $imageBase64,
                    'embedding'    => json_decode($user->getFaceEmbedding(), true),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                ]),
                CURLOPT_TIMEOUT => 30,
            ]);

            $out = curl_exec($ch);
            curl_close($ch);

<<<<<<< HEAD
            // ligne 195 : json_decode attend string
            $result = is_string($out) ? json_decode($out, true) : null;
=======
            $result = $out ? json_decode($out, true) : null;
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

            if (isset($result['distance']) && $result['distance'] < $bestDistance) {
                $bestDistance = $result['distance'];
                $bestMatch    = $user;
            }
        }

        $THRESHOLD = 0.40;

        if ($bestMatch && $bestDistance < $THRESHOLD) {

            if ($bestMatch->getStatut() === User::STATUT_BLOQUE) {
                return $this->json(['error' => 'Compte bloqué.']);
            }

            // ── Authentifier ──
            $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                $bestMatch, 'main', $bestMatch->getRoles()
            );
            $tokenStorage->setToken($token);
            $request->getSession()->set('_security_main', serialize($token));

            $this->adminLogger->log($bestMatch, AdminLog::ACTION_LOGIN_FACE, null,
                'distance: ' . round($bestDistance, 3));

            // ── Appeler /emotion côté PHP (pas JS) ──
<<<<<<< HEAD
            $emotionData = $this->callEmotion($request, $imageBase64Str);
=======
            $emotionData = $this->callEmotion($request, $imageBase64);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $request->getSession()->set('face_emotion', [
                'emoji'   => $emotionData['emoji']   ?? '😐',
                'message' => $emotionData['message'] ?? 'Bonne journée !',
                'prenom'  => $bestMatch->getPrenom(),
            ]);

            $roles    = $bestMatch->getRoles();
            $redirect = (in_array('ROLE_SUPER_ADMIN', $roles) || in_array('ROLE_ADMIN', $roles))
                ? $this->generateUrl('admin_dashboard')
                : $this->generateUrl('app_accueil');

            return $this->json([
                'success'  => true,
                'redirect' => $redirect,
                'prenom'   => $bestMatch->getPrenom(),
            ]);
        }

        return $this->json(['error' => 'Visage non reconnu']);
    }

<<<<<<< HEAD
    /**
     * Appel Flask /emotion côté serveur PHP
     *
     * @return array<string, string>
     */
    private function callEmotion(Request $request, string $imageBase64): array
    {
        $ch = curl_init('http://127.0.0.1:5001/emotion');

        // ligne 248 : CURLOPT_POSTFIELDS doit être string, pas false
=======
    // ── Appel Flask /emotion côté serveur PHP ──
    private function callEmotion(Request $request, string $imageBase64): array
    {
        $ch = curl_init('http://127.0.0.1:5001/emotion');
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
<<<<<<< HEAD
            CURLOPT_POSTFIELDS     => (string) json_encode(['image_base64' => $imageBase64]),
=======
            CURLOPT_POSTFIELDS     => json_encode(['image_base64' => $imageBase64]),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            CURLOPT_TIMEOUT        => 15,
        ]);
        $out = curl_exec($ch);
        curl_close($ch);
<<<<<<< HEAD

        // ligne 259 : json_decode attend string
        $data = is_string($out) ? json_decode($out, true) : null;
=======
        

        $data = $out ? json_decode($out, true) : null;
       $request->getSession()->set('emotion_debug', $out);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

        // Mapping de secours si Flask échoue
        $messages = [
            'happy'    => ['emoji' => '😊', 'message' => "Vous semblez de bonne humeur aujourd'hui !"],
            'sad'      => ['emoji' => '😢', 'message' => 'Courage, bonne journée quand même !'],
            'angry'    => ['emoji' => '😠', 'message' => 'Respirez, tout va bien se passer !'],
            'surprise' => ['emoji' => '😲', 'message' => 'Quelque chose vous a surpris ?'],
            'fear'     => ['emoji' => '😨', 'message' => "Pas d'inquiétude, vous êtes en sécurité !"],
            'disgust'  => ['emoji' => '😒', 'message' => 'Bonne journée malgré tout !'],
            'neutral'  => ['emoji' => '😐', 'message' => 'Bonne journée !'],
        ];

<<<<<<< HEAD
=======
        // Si Flask a retourné une émotion valide
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        if (isset($data['emotion']) && isset($messages[$data['emotion']])) {
            return $messages[$data['emotion']];
        }

<<<<<<< HEAD
        return ['emoji' => '😊', 'message' => 'Bienvenue !'];
    }
}
=======
        // Fallback
        return ['emoji' => '😊', 'message' => 'Bienvenue !'];
    }
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
