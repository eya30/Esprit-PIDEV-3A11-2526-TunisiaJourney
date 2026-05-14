<?php
// src/Controller/AvisChambreController.php

namespace App\Controller;

use App\Entity\ReservationChambre;
use App\Entity\User;
use App\Form\AvisChambreType;
use App\Service\AvisChambreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // Add this import
use Symfony\Component\Routing\Annotation\Route;

class AvisChambreController extends AbstractController
{
    #[Route('/avis/chambre/{token}', name: 'app_avis_chambre_form')]
    public function formulaire(string $token, Request $request, EntityManagerInterface $em, AvisChambreService $avisService): Response // Added : Response return type
    {
        // 1. Trouver la réservation avec ce token
        $reservation = $em->getRepository(ReservationChambre::class)->findOneBy(['tokenAvis' => $token]);
        
        if (!$reservation) {
            $this->addFlash('error', '❌ Lien invalide ou expiré.');
            return $this->redirectToRoute('app_home');
        }

        // 2. Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', '🔐 Veuillez vous connecter pour donner votre avis.');
            return $this->redirectToRoute('app_login');
        }

        // 3. Récupérer l'utilisateur complet depuis son email (ou username)
        $userComplete = $em->getRepository(User::class)->findOneBy(['email' => $user->getUserIdentifier()]);
        
        if (!$userComplete) {
            $this->addFlash('error', '❌ Utilisateur non trouvé.');
            return $this->redirectToRoute('app_login');
        }
        
        // 4. Vérifier que c'est bien sa réservation
        // Correction : utilisation de getIdUtilisateur() au lieu de getUtilisateur()
        if ($reservation->getIdUtilisateur() !== $userComplete->getId()) {
            $this->addFlash('error', '⛔ Cette réservation ne vous appartient pas.');
            return $this->redirectToRoute('app_home');
        }

        // 5. Vérifier que le séjour est terminé
        $dateFin = $reservation->getDateFin();
        if (!$dateFin || $dateFin > new \DateTime()) {
            $this->addFlash('warning', '📅 Vous pourrez donner votre avis après la fin de votre séjour.');
            return $this->redirectToRoute('app_home');
        }

        // 6. Vérifier qu'il n'a pas déjà donné son avis
        if ($reservation->getAvisChambre()) {
            $this->addFlash('info', '✅ Vous avez déjà donné votre avis pour ce séjour.');
            return $this->redirectToRoute('app_home');
        }

        // 7. Afficher le formulaire
        $form = $this->createForm(AvisChambreType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $avisService->enregistrerAvis($reservation, $userComplete, $data);
            $this->addFlash('success', '🎉 Merci pour votre avis !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('avis_chambre/formulaire.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation
        ]);
    }
}