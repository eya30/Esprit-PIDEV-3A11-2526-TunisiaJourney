<?php
// src/Controller/Api/WhatsAppController.php

namespace App\Controller\Api;

use App\Repository\CommandeRepository;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller WhatsApp — Déclenche l'envoi d'un message au client
 * quand le livreur est arrivé (statut = "Arrivé").
 *
 * Route : POST /api/commande/{id}/notifier-arrivee
 *
 * Appelé depuis :
 *  - Le controller de tracking quand progression >= 99.5%
 *  - Une action manuelle admin/livreur
 */
#[Route('/api/commande', name: 'api_whatsapp_')]
class WhatsAppController extends AbstractController
{
    public function __construct(
        private WhatsAppService        $whatsApp,
        private CommandeRepository     $commandeRepo,
        private EntityManagerInterface $em,
    ) {}

    // ════════════════════════════════════════════════════
    //  POST /api/commande/{id}/notifier-arrivee
    //
    //  Conditions :
    //   - Utilisateur authentifié
    //   - Commande existante et appartenant à l'utilisateur (ou admin)
    //   - Statut = "Arrivé" (ou passage automatique depuis "En cours")
    // ════════════════════════════════════════════════════
    #[Route('/{id}/notifier-arrivee', name: 'notifier_arrivee', methods: ['POST'])]
    public function notifierArrivee(int $id): JsonResponse
    {
        // ── Authentification ──────────────────────────
        if (!$this->getUser()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        // ── Récupération commande ─────────────────────
        $commande = $this->commandeRepo->find($id);

        if (!$commande) {
            return $this->json(['success' => false, 'error' => 'Commande introuvable'], 404);
        }

        // Seul le propriétaire ou un admin peut déclencher
        $isOwner = $commande->getUser() === $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SUPER_ADMIN');

        if (!$isOwner && !$isAdmin) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        // ── Vérification du statut ────────────────────
        $statut = $commande->getStatut();

        if ($statut !== 'Arrivé') {
            return $this->json([
                'success' => false,
                'error'   => "Notification impossible : statut actuel = \"{$statut}\". Requis : \"Arrivé\".",
            ], 400);
        }

        // ── Récupération du client ────────────────────
        $user = $commande->getUser();

        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Client introuvable'], 404);
        }

        $telephone = $user->getTelephone();

        if (!$telephone) {
            return $this->json([
                'success' => false,
                'error'   => 'Le client n\'a pas de numéro de téléphone enregistré.',
            ], 400);
        }

        // ── Envoi WhatsApp ────────────────────────────
        $result = $this->whatsApp->sendLivreurArrive(
            $telephone,
            $user->getNom(),
            $user->getPrenom(),
            $commande->getId()
        );

        if (!$result['success']) {
            return $this->json([
                'success' => false,
                'error'   => 'Échec d\'envoi WhatsApp : ' . $result['error'],
            ], 500);
        }

        // ── Succès ────────────────────────────────────
        return $this->json([
            'success'    => true,
            'message'    => 'Message WhatsApp envoyé avec succès.',
            'twilio_sid' => $result['sid'],
            'client'     => $user->getPrenom() . ' ' . $user->getNom(),
            'telephone'  => $telephone,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  POST /api/commande/{id}/notifier-livraison
    //  (appelé automatiquement depuis TrackingController
    //   quand progression = 100% et statut passe à "Livrée")
    // ════════════════════════════════════════════════════
    #[Route('/{id}/notifier-livraison', name: 'notifier_livraison', methods: ['POST'])]
    public function notifierLivraison(int $id): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $commande = $this->commandeRepo->find($id);

        if (!$commande || ($commande->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN'))) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $user = $commande->getUser();
        if (!$user?->getTelephone()) {
            return $this->json(['success' => false, 'error' => 'Pas de téléphone'], 400);
        }

        $message = "✅ *TunisiaJourney — Livraison confirmée*\n\n"
            . "Bonjour *{$user->getPrenom()} {$user->getNom()}*,\n\n"
            . "Votre commande *#{$commande->getId()}* a été livrée avec succès ! 🎉\n\n"
            . "Merci de votre confiance.\n"
            . "_L'équipe TunisiaJourney_";

        $result = $this->whatsApp->send($user->getTelephone(), $message);

        return $this->json([
            'success'    => $result['success'],
            'twilio_sid' => $result['sid'] ?? null,
            'error'      => $result['error'] ?? null,
        ]);
    }
}