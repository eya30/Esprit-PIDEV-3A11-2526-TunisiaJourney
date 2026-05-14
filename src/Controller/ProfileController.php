<?php

namespace App\Controller;

use App\Entity\AdminLog;
<<<<<<< HEAD
use App\Entity\User;
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
use App\Service\AdminLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(private AdminLogger $adminLogger) {}

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/clear-errors', name: 'app_profile_clear_errors', methods: ['POST'])]
    public function clearErrors(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $session->remove('_profile_errors');
        $session->remove('_profile_data');
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['POST'])]
    public function edit(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher,
        ParameterBagInterface       $params,
        TokenStorageInterface       $tokenStorage,
        ValidatorInterface          $validator,
        HttpClientInterface         $httpClient
    ): Response {

<<<<<<< HEAD
        /** @var User $user */
        $user    = $this->getUser();
        $session = $request->getSession();

        // (string) cast — ligne 57
        if (!$this->isCsrfTokenValid('profile_edit', (string) $request->request->get('_token'))) {
=======
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $session = $request->getSession();

        if (!$this->isCsrfTokenValid('profile_edit', $request->request->get('_token'))) {
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_accueil', ['openProfile' => '1']);
        }

<<<<<<< HEAD
        // (string) cast sur tous les trim — lignes 62-67
        $nom           = trim((string) $request->request->get('nom', ''));
        $prenom        = trim((string) $request->request->get('prenom', ''));
        $telephone     = trim((string) $request->request->get('telephone', ''));
        $adresse       = trim((string) $request->request->get('adresse', ''));
        $email         = trim((string) $request->request->get('email', ''));
        $dateNaissance = trim((string) $request->request->get('dateNaissance', ''));
=======
        $nom           = trim($request->request->get('nom', ''));
        $prenom        = trim($request->request->get('prenom', ''));
        $telephone     = trim($request->request->get('telephone', ''));
        $adresse       = trim($request->request->get('adresse', ''));
        $email         = trim($request->request->get('email', ''));
        $dateNaissance = trim($request->request->get('dateNaissance', ''));
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

        $profileData = compact('nom', 'prenom', 'telephone', 'adresse', 'email', 'dateNaissance');

        // Snapshot AVANT
        $beforeNom       = $user->getNom();
        $beforePrenom    = $user->getPrenom();
        $beforeTelephone = $user->getTelephone();
        $beforeAdresse   = $user->getAdresse();
        $beforeEmail     = $user->getEmail();
        $beforeNaissance = $user->getDateNaissance()?->format('d/m/Y');

        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setTelephone($telephone !== '' ? $telephone : null);
        $user->setAdresse($adresse !== '' ? $adresse : null);
        if ($email !== '') { $user->setEmail($email); }

        if ($dateNaissance !== '') {
            try { $user->setDateNaissance(new \DateTime($dateNaissance)); }
            catch (\Exception) { $user->setDateNaissance(null); }
        } else {
            $user->setDateNaissance(null);
        }

        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) { $errors[$v->getPropertyPath()][] = $v->getMessage(); }
            $session->set('_profile_errors', $errors);
            $session->set('_profile_data', $profileData);
            $em->refresh($user);
            return $this->redirectToRoute('app_accueil', ['openEditModal' => '1']);
        }

<<<<<<< HEAD
        // Mot de passe — (string) cast lignes 110, 111, 119
        $newPassword     = (string) $request->request->get('new_password', '');
        $currentPassword = (string) $request->request->get('current_password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');
=======
        // Mot de passe
        $newPassword     = $request->request->get('new_password', '');
        $currentPassword = $request->request->get('current_password', '');
        $confirmPassword = $request->request->get('confirm_password', '');
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $passwordChanged = false;

        if ($newPassword !== '') {
            $pwdErrors = [];
            if (strlen($newPassword) < 8) $pwdErrors['new_password'][] = 'Le mot de passe doit contenir au moins 8 caractères.';
            if (!$hasher->isPasswordValid($user, $currentPassword)) $pwdErrors['current_password'][] = 'Mot de passe actuel incorrect.';
            if ($newPassword !== $confirmPassword) $pwdErrors['confirm_password'][] = 'Les mots de passe ne correspondent pas.';
            if (!empty($pwdErrors)) {
                $session->set('_profile_errors', $pwdErrors);
                $session->set('_profile_data', $profileData);
                $em->refresh($user);
                return $this->redirectToRoute('app_accueil', ['openEditModal' => '1']);
            }
            $user->setMotDePasse($hasher->hashPassword($user, $newPassword));
            $passwordChanged = true;
        }

        // ── Photo + Face Embedding ──
        $photoChanged = false;
        $photoFile    = $request->files->get('photo');
        if ($photoFile && $photoFile->isValid()) {

            // 1. Générer le face embedding via Flask
            try {
                $ch = curl_init('http://127.0.0.1:5001/embed');
<<<<<<< HEAD

                // ligne 136 : file_get_contents peut retourner false → cast (string)
                $fileContents = file_get_contents($photoFile->getPathname());
                $imageBase64  = base64_encode($fileContents !== false ? $fileContents : '');

                // ligne 131 : CURLOPT_POSTFIELDS doit être string|array, pas false
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
<<<<<<< HEAD
                    CURLOPT_POSTFIELDS     => (string) json_encode(['image_base64' => $imageBase64]),
=======
                    CURLOPT_POSTFIELDS     => json_encode([
                        'image_base64' => base64_encode(file_get_contents($photoFile->getPathname()))
                    ]),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_CONNECTTIMEOUT => 5,
                ]);
                $out       = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);

<<<<<<< HEAD
                // lignes 149, 150, 154 : json_decode attend string
                $outStr = is_string($out) ? $out : '';

                if ($outStr && !$curlError) {
                    // ligne 149-150 : cast string avant json_decode
                    $result = json_decode($outStr, true);
                    if (isset($result['embedding'])) {
                        // ligne 156 : json_encode peut retourner false → cast string
                        $embedding = json_encode($result['embedding']);
                        $user->setFaceEmbedding($embedding !== false ? $embedding : null);
                    }
                }
            } catch (\Exception $e) {
                // Flask indisponible → on continue
            }

            // 2. Upload photo vers ImgBB
            // ligne 166 : $params->get() peut retourner mixed → cast string
           $imgbbKeyRaw = $params->get('imgbb_api_key');
           $imgbbKey = is_string($imgbbKeyRaw) ? $imgbbKeyRaw : '';
            $ch2 = curl_init();
            curl_setopt_array($ch2, [
                CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . $imgbbKey,
=======
                // ── DEBUG TEMPORAIRE ──
                file_put_contents('C:/xampp/htdocs/debug_embed.txt',
                    "out: "              . var_export($out, true) .
                    "\ncurlError: "      . $curlError .
                    "\nresult: "         . var_export(json_decode($out, true), true) .
                    "\nembedding isset: " . (isset(json_decode($out, true)['embedding']) ? 'OUI' : 'NON')
                );

                if ($out && !$curlError) {
                    $result = json_decode($out, true);
                    if (isset($result['embedding'])) {
                        $user->setFaceEmbedding(json_encode($result['embedding']));
                    }
                }
            } catch (\Exception $e) {
                file_put_contents('C:/xampp/htdocs/debug_embed.txt', 'Exception: ' . $e->getMessage());
            }

            // 2. Upload photo vers ImgBB
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . $params->get('imgbb_api_key'),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => [
                    'image' => new \CURLFile(
                        $photoFile->getPathname(),
                        $photoFile->getMimeType(),
                        $photoFile->getClientOriginalName()
                    )
                ],
            ]);
<<<<<<< HEAD
            $imgOut  = curl_exec($ch2);
            curl_close($ch2);

            // ligne 177 : json_decode attend string
            $imgJson = json_decode(is_string($imgOut) ? $imgOut : '', true);
=======
            $imgJson = json_decode(curl_exec($ch), true);
            curl_close($ch);

>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            if (isset($imgJson['data']['url'])) {
                $user->setProfileImageUrl($imgJson['data']['url']);
                $photoChanged = true;
            }
        }

        $em->flush();
        $em->refresh($user);
<<<<<<< HEAD

        // ligne 188 : getToken() peut être null → vérification
        $token = $tokenStorage->getToken();
        if ($token !== null) {
            $token->setUser($user);
        }
=======
        $tokenStorage->getToken()->setUser($user);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

        // ── LOGS ──
        $cible = 'soi-meme (id=' . $user->getId() . ')';

        if ($passwordChanged) {
            $this->adminLogger->log($user, AdminLog::ACTION_CHANGE_PASSWORD, $cible, null);
        }
        if ($photoChanged) {
            $this->adminLogger->log($user, AdminLog::ACTION_UPLOAD_PHOTO, $cible, null);
        }
        $profileDiff = AdminLogger::buildDetails([
            AdminLogger::diff('nom',       $beforeNom,       $user->getNom()),
            AdminLogger::diff('prenom',    $beforePrenom,    $user->getPrenom()),
            AdminLogger::diff('email',     $beforeEmail,     $user->getEmail()),
            AdminLogger::diff('telephone', $beforeTelephone, $user->getTelephone()),
            AdminLogger::diff('adresse',   $beforeAdresse,   $user->getAdresse()),
            AdminLogger::diff('naissance', $beforeNaissance, $user->getDateNaissance()?->format('d/m/Y')),
        ]);
        if ($profileDiff) {
            $this->adminLogger->log($user, AdminLog::ACTION_EDIT_PROFILE, $cible, $profileDiff);
        }

        $session->remove('_profile_errors');
        $session->remove('_profile_data');
        $this->addFlash('success', 'Profil mis a jour avec succes.');
        return $this->redirectToRoute('app_accueil', ['openProfile' => '1']);
    }
}
