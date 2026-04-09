<?php

namespace App\Controller;

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

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Appelé en AJAX depuis le JS après affichage des erreurs
     * pour nettoyer la session (évite que les erreurs restent au prochain chargement).
     */
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
        ValidatorInterface          $validator
    ): Response {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $session = $request->getSession();

        // ── CSRF ─────────────────────────────────────────────────
        if (!$this->isCsrfTokenValid('profile_edit', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_accueil', ['openProfile' => '1']);
        }

        // ── Lecture des champs ────────────────────────────────────
        $nom           = trim($request->request->get('nom', ''));
        $prenom        = trim($request->request->get('prenom', ''));
        $telephone     = trim($request->request->get('telephone', ''));
        $adresse       = trim($request->request->get('adresse', ''));
        $email         = trim($request->request->get('email', ''));
        $dateNaissance = trim($request->request->get('dateNaissance', ''));

        // Données à renvoyer dans le formulaire en cas d'erreur
        $profileData = compact('nom', 'prenom', 'telephone', 'adresse', 'email', 'dateNaissance');

        // ── Hydratation temporaire (pour validation) ─────────────
        // CORRECTIF Bug 1 : on affecte toujours la valeur soumise, même vide,
        // sinon le validateur ne détecte jamais les champs manquants.
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setTelephone($telephone !== '' ? $telephone : null);
        $user->setAdresse($adresse !== '' ? $adresse : null);

        if ($email !== '') {
            $user->setEmail($email);
        }

        if ($dateNaissance !== '') {
            try {
                $user->setDateNaissance(new \DateTime($dateNaissance));
            } catch (\Exception) {
                $user->setDateNaissance(null);
            }
        } else {
            $user->setDateNaissance(null);
        }

        // ── Validation Symfony via les Assert de l'entité ────────
        $violations = $validator->validate($user);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }
            // Stockage en session → récupéré dans base.html.twig via app.session.get()
            $session->set('_profile_errors', $errors);
            $session->set('_profile_data',   $profileData);

            // CORRECTIF Bug 3 : on annule les modifications en mémoire pour éviter
            // toute persistance accidentelle des données invalides.
            $em->refresh($user);

            // Redirection : le JS détecte ?openEditModal=1 et ouvre le modal edit
            return $this->redirectToRoute('app_accueil', ['openEditModal' => '1']);
        }

        // ── Mot de passe ─────────────────────────────────────────
        $currentPassword = $request->request->get('current_password', '');
        $newPassword     = $request->request->get('new_password', '');
        $confirmPassword = $request->request->get('confirm_password', '');

        if ($newPassword !== '') {
            $pwdErrors = [];

            if (strlen($newPassword) < 8) {
                $pwdErrors['new_password'][] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
            if (!$hasher->isPasswordValid($user, $currentPassword)) {
                $pwdErrors['current_password'][] = 'Mot de passe actuel incorrect.';
            }
            if ($newPassword !== $confirmPassword) {
                $pwdErrors['confirm_password'][] = 'Les mots de passe ne correspondent pas.';
            }

            if (!empty($pwdErrors)) {
                $session->set('_profile_errors', $pwdErrors);
                $session->set('_profile_data',   $profileData);
                $em->refresh($user);
                return $this->redirectToRoute('app_accueil', ['openEditModal' => '1']);
            }

            $user->setMotDePasse($hasher->hashPassword($user, $newPassword));
        }

        // ── Photo via ImgBB ──────────────────────────────────────
        $photoFile = $request->files->get('photo');
        if ($photoFile && $photoFile->isValid()) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . $params->get('imgbb_api_key'),
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => [
                    'image' => new \CURLFile(
                        $photoFile->getPathname(),
                        $photoFile->getMimeType(),
                        $photoFile->getClientOriginalName()
                    ),
                ],
            ]);
            $json = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($json['data']['url'])) {
                $user->setProfileImageUrl($json['data']['url']);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
            }
        }

        // ── Sauvegarde ───────────────────────────────────────────
        $em->flush();
        $em->refresh($user);
        $tokenStorage->getToken()->setUser($user);

        // Nettoyage session
        $session->remove('_profile_errors');
        $session->remove('_profile_data');

        $this->addFlash('success', 'Profil mis à jour avec succès.');
        return $this->redirectToRoute('app_accueil', ['openProfile' => '1']);
    }
}