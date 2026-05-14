<?php
// src/Controller/Api/WhatsAppController.php

namespace App\Controller\Api;

use App\Repository\CommandeRepository;
use App\Service\WhatsAppService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/commande', name: 'api_whatsapp_')]
class WhatsAppController extends AbstractController
{
    public function __construct(
        private WhatsAppService    $whatsApp,
        private CommandeRepository $commandeRepo,
    ) {}

    #[Route('/{id}/notifier-arrivee', name: 'notifier_arrivee', methods: ['POST'])]
    public function notifierArrivee(int $id): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        $commande = $this->commandeRepo->find($id);

        if (!$commande) {
            return $this->json(['success' => false, 'error' => 'Commande introuvable'], 404);
        }

        $isOwner = $commande->getUser() === $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SUPER_ADMIN');

        if (!$isOwner && !$isAdmin) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $statut = $commande->getStatut();

        if ($statut !== 'Arrivé') {
            return $this->json([
                'success' => false,
                'error'   => "Notification impossible : statut actuel = \"{$statut}\". Requis : \"Arrivé\".",
            ], 400);
        }

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

        $nom      = $user->getNom() ?? '';
        $prenom   = $user->getPrenom() ?? '';
        $commandeId = $commande->getId() ?? 0;

        $result = $this->whatsApp->sendLivreurArrive(
            $telephone,
            $nom,
            $prenom,
            $commandeId
        );

        if (!$result['success']) {
            return $this->json([
                'success' => false,
                'error'   => 'Échec d\'envoi WhatsApp : ' . $result['error'],
            ], 500);
        }

        return $this->json([
            'success'    => true,
            'message'    => 'Message WhatsApp envoyé avec succès.',
            'twilio_sid' => $result['sid'],
            'client'     => $prenom . ' ' . $nom,
            'telephone'  => $telephone,
        ]);
    }

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