<?php
// src/Controller/SignalementController.php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\SNotification;
use App\Repository\CommentaireRepository;
use App\Service\SNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/signalement')]
class SignalementController extends AbstractController
{
    private const VALID_TYPES = ['video', 'image', 'comment'];

    private const VALID_REASONS_COMMENT = [
        'langage_inapproprié',
        'propos_injurieux',
        'discrimination',
        'spam',
        'contenu_offensant',
        'autre',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private SNotificationService $snotificationService,
        private CommentaireRepository $commentaireRepository
    ) {}

    // ══════════════════════════════════════════════════════
    // POST /signalement/new — Créer un signalement depuis le front
    // ══════════════════════════════════════════════════════
    #[Route('/new', name: 'api_signalement_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['success' => false, 'error' => 'Données invalides.'], 400);
        }

        $type        = trim((string) ($data['type']        ?? ''));
        $targetId    = (int)         ($data['targetId']    ?? 0);
        $reason      = trim((string) ($data['reason']      ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if (!in_array($type, self::VALID_TYPES, true)) {
            return new JsonResponse(['success' => false, 'error' => 'Type de signalement invalide.'], 422);
        }

        if ($targetId <= 0) {
            return new JsonResponse(['success' => false, 'error' => 'Identifiant cible invalide.'], 422);
        }

        if (!in_array($reason, self::VALID_REASONS_COMMENT, true)) {
            return new JsonResponse(['success' => false, 'error' => 'Raison de signalement invalide.'], 422);
        }

        // Récupérer le commentaire
        $commentText = null;
        if ($type === 'comment') {
            $commentaire = $this->commentaireRepository->find($targetId);
            if (!$commentaire) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'Commentaire introuvable (ID: ' . $targetId . ').'
                ], 404);
            }
            $commentText = $commentaire->getDescription();
        }

        // Créer le signalement
        $signalement = new Signalement();
        $signalement->setType($type);
        $signalement->setTargetId($targetId);
        $signalement->setReason($reason);
        $signalement->setDescription($description);
        $signalement->setDateCreation(new \DateTime());
        $signalement->setUserReporterId(999); // visiteur non connecté

        try {
            $this->em->persist($signalement);
            $this->em->flush();

            // Créer la notification pour l'admin
            $snotification = $this->snotificationService->createSignalementNotification(
                $signalement,
                999,
                $commentText
            );

            return new JsonResponse([
                'success'         => true,
                'message'         => 'Signalement enregistré avec succès !',
                'id'              => $signalement->getId(),
                'snotificationId' => $snotification->getId(),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════
    // GET /signalement/snotifications — Liste des notifications (back-office)
    // IMPORTANT : cette route doit être avant /{id}/...
    // ══════════════════════════════════════════════════════
    #[Route('/snotifications', name: 'api_snotifications_list', methods: ['GET'])]
    public function getSNotifications(): JsonResponse
    {
        $snotifications = $this->snotificationService->getUnreadNotifications();

        $result = [];
        foreach ($snotifications as $snotif) {
            // Récupérer le commentaire pour avoir son texte à jour
            $comment     = null;
            $commentText = 'Commentaire introuvable';
            if ($snotif->getCommentId()) {
                $comment = $this->commentaireRepository->find($snotif->getCommentId());
                if ($comment) {
                    $commentText = $comment->getDescription();
                }
            }

            $result[] = [
                'id'             => $snotif->getId(),
                'type'           => $snotif->getType(),
                'message'        => $snotif->getMessage(),
                'signalementId'  => $snotif->getSignalementId(),
                'commentId'      => $snotif->getCommentId(),
                'commentText'    => $commentText,
                'reason'         => $snotif->getReason(),
                'userReporterId' => $snotif->getUserReporterId(),
                'lu'             => $snotif->getLu(),
                'dateCreation'   => $snotif->getDateCreation()->format('d/m/Y H:i'),
            ];
        }

        return new JsonResponse([
            'success'        => true,
            'snotifications' => $result,
            'total'          => count($result),
        ]);
    }

    // ══════════════════════════════════════════════════════
    // POST /signalement/snotifications/{id}/read — Marquer comme lu
    // ══════════════════════════════════════════════════════
    #[Route('/snotifications/{id}/read', name: 'admin_snotification_mark_read', methods: ['POST'])]
    public function markSNotificationAsRead(int $id): JsonResponse
    {
        $snotification = $this->em->getRepository(SNotification::class)->find($id);
        if (!$snotification) {
            return new JsonResponse(['success' => false, 'error' => 'Notification introuvable.'], 404);
        }

        try {
            $this->snotificationService->markAsRead($snotification);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════
    // POST /signalement/{id}/block-comment — Bloquer le commentaire
    // ══════════════════════════════════════════════════════
    #[Route('/{id}/block-comment', name: 'admin_signalement_block_comment', methods: ['POST'])]
    public function blockCommentFromSignalement(int $id): JsonResponse
    {
        $signalement = $this->em->getRepository(Signalement::class)->find($id);
        if (!$signalement) {
            return new JsonResponse(['success' => false, 'error' => 'Signalement introuvable.'], 404);
        }

        if ($signalement->getType() !== 'comment') {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Ce signalement ne concerne pas un commentaire.'
            ], 422);
        }

        $commentaire = $this->commentaireRepository->find($signalement->getTargetId());
        if (!$commentaire) {
            return new JsonResponse(['success' => false, 'error' => 'Commentaire introuvable.'], 404);
        }

        try {
            // Bloquer le commentaire
            $commentaire->setIsCancelled(true);
            $commentaire->setCancelledAt(new \DateTime());

            // Marquer le signalement comme traité
            $signalement->setIsTreated(true);
            $signalement->setTreatedAt(new \DateTime());

            $this->em->flush();

            // Marquer la notification comme lue
            if ($signalement->getSnotificationId()) {
                $snotif = $this->em->getRepository(SNotification::class)
                    ->find($signalement->getSnotificationId());
                if ($snotif) {
                    $this->snotificationService->markAsRead($snotif);
                }
            }

            return new JsonResponse([
                'success'   => true,
                'message'   => 'Commentaire bloqué avec succès !',
                'commentId' => $commentaire->getIdC(),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════
    // GET /signalement/admin/list — Liste signalements non traités
    // ══════════════════════════════════════════════════════
    #[Route('/admin/list', name: 'admin_signalement_list', methods: ['GET'])]
    public function adminList(): JsonResponse
    {
        $signalements = $this->em->getRepository(Signalement::class)->findBy(
            ['isTreated' => false],
            ['dateCreation' => 'DESC']
        );

        $result = [];
        foreach ($signalements as $s) {
            $result[] = [
                'id'           => $s->getId(),
                'type'         => $s->getType(),
                'targetId'     => $s->getTargetId(),
                'reason'       => $s->getReason(),
                'reasonLabel'  => $s->getReasonLabel(),
                'description'  => $s->getDescription() ?? '',
                'dateCreation' => $s->getDateCreation()->format('d/m/Y H:i'),
                'isTreated'    => $s->getIsTreated(),
            ];
        }

        return new JsonResponse([
            'success'      => true,
            'signalements' => $result,
            'total'        => count($result),
        ]);
    }

    // ══════════════════════════════════════════════════════
    // POST /signalement/{id}/treat — Marquer signalement comme traité
    // ══════════════════════════════════════════════════════
    #[Route('/{id}/treat', name: 'admin_signalement_treat', methods: ['POST'])]
    public function treat(int $id): JsonResponse
    {
        $signalement = $this->em->getRepository(Signalement::class)->find($id);
        if (!$signalement) {
            return new JsonResponse(['success' => false, 'error' => 'Signalement introuvable.'], 404);
        }

        $signalement->setIsTreated(true);
        $signalement->setTreatedAt(new \DateTime());

        try {
            $this->em->flush();
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}