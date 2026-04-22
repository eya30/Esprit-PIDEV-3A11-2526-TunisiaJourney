<?php
// src/Service/SNotificationService.php

namespace App\Service;

use App\Entity\SNotification;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;

class SNotificationService
{
    private const REASON_LABELS = [
        'langage_inapproprié' => 'Langage inapproprié',
        'propos_injurieux'    => 'Propos injurieux',
        'discrimination'      => 'Discrimination',
        'spam'                => 'Spam ou publicité',
        'contenu_offensant'   => 'Contenu offensant',
        'autre'               => 'Autre',
    ];

    public function __construct(private EntityManagerInterface $em) {}

    public function createSignalementNotification(
        Signalement $signalement,
        ?int $userReporterId = null,
        ?string $commentText = null
    ): SNotification {
        $reasonLabel = self::REASON_LABELS[$signalement->getReason()] ?? $signalement->getReason();
        $textPreview = htmlspecialchars(substr($commentText ?? 'Non disponible', 0, 200));
        $reporter    = ($userReporterId === 999 || $userReporterId === null)
            ? 'Visiteur (non connecté)'
            : 'User #' . $userReporterId;

        $message = sprintf(
            '<strong>🚩 Nouveau signalement de commentaire</strong><br>' .
            'Commentaire ID : <strong>#%d</strong><br>' .
            'Motif : <strong style="color:#c0392b;">%s</strong><br>' .
            'Texte : <em>"%s"</em><br>' .
            'Signalé par : %s<br>' .
            'Date : %s',
            $signalement->getTargetId(),
            htmlspecialchars($reasonLabel),
            $textPreview,
            htmlspecialchars($reporter),
            $signalement->getDateCreation()->format('d/m/Y H:i:s')
        );

        $snotification = new SNotification();
        $snotification->setType('signalement_commentaire');
        $snotification->setMessage($message);
        $snotification->setSignalementId($signalement->getId());
        $snotification->setCommentId($signalement->getTargetId());
        $snotification->setReason($signalement->getReason());
        $snotification->setUserReporterId($userReporterId ?? 999);
        $snotification->setLu(false);
        $snotification->setDateCreation(new \DateTime());

        $this->em->persist($snotification);
        $this->em->flush();

        // Lier la notification au signalement
        $signalement->setSnotificationId($snotification->getId());
        $this->em->flush();

        return $snotification;
    }

    public function getUnreadNotifications(int $limit = 100): array
    {
        return $this->em->getRepository(SNotification::class)
            ->findBy(['lu' => false], ['dateCreation' => 'DESC'], $limit);
    }

    public function countUnreadNotifications(): int
    {
        return $this->em->getRepository(SNotification::class)
            ->count(['lu' => false]);
    }

    public function markAsRead(SNotification $snotification): void
    {
        $snotification->setLu(true);
        $this->em->flush();
    }

    public function markAllAsRead(): void
    {
        $this->em->createQuery(
            'UPDATE App\Entity\SNotification s SET s.lu = true WHERE s.lu = false'
        )->execute();
    }

    public function deleteOldNotifications(int $daysOld = 30): int
    {
        $date = new \DateTime("-{$daysOld} days");
        return $this->em->createQuery(
            'DELETE FROM App\Entity\SNotification s WHERE s.dateCreation < :date'
        )->setParameter('date', $date)->execute();
    }
}