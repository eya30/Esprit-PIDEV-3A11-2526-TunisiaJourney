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

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
   public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
{
    $error = $authenticationUtils->getLastAuthenticationError();

    $lastUsername = $authenticationUtils->getLastUsername();

    $prefillEmail = $request->getSession()->get('prefill_email');
    if ($prefillEmail) {
        $request->getSession()->remove('prefill_email');
        if (!$lastUsername) {
            $lastUsername = $prefillEmail;
        }
    }

    // ← ces deux lignes manquent chez toi
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
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user             = new User();
        $loginForm        = $this->createForm(LoginType::class);
        $registrationForm = $this->createForm(RegistrationType::class, $user);

        $registrationForm->handleRequest($request);

        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {

            /* ── Mot de passe hashé ── */
            $plainPassword = $registrationForm->get('mot_de_passe')->getData();
            $user->setMotDePasse(
                $passwordHasher->hashPassword($user, $plainPassword)
            );

            // ── dateNaissance est maintenant mapped:true (DateType) → déjà injectée par Symfony

            /* ── Valeurs automatiques ── */
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

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/auth.html.twig', [
            'loginForm'        => $loginForm->createView(),
            'registrationForm' => $registrationForm->createView(),
            'mode'             => 'register',
            'last_username'    => '',
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method is intercepted by Symfony logout.');
    }
}