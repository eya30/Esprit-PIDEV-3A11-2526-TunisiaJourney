<?php
// src/Service/SNotificationService.php

namespace App\Service;

use App\Entity\SNotification;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

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
        
        // Vérifier que $commentText n'est pas null avant htmlspecialchars
        $safeCommentText = $commentText ?? 'Non disponible';
        $textPreview = htmlspecialchars(substr($safeCommentText, 0, 200), ENT_QUOTES, 'UTF-8');
        
        $reporter = ($userReporterId === 999 || $userReporterId === null)
            ? 'Visiteur (non connecté)'
            : 'User #' . $userReporterId;

        // Vérifier que dateCreation n'est pas null avant format()
        $dateCreation = $signalement->getDateCreation();
        $formattedDate = ($dateCreation instanceof \DateTimeInterface) 
            ? $dateCreation->format('d/m/Y H:i:s') 
            : date('d/m/Y H:i:s');

        // Correction ligne 52: S'assurer que $reasonLabel est une string
        $safeReasonLabel = is_string($reasonLabel) ? $reasonLabel : 'Motif inconnu';
        
        $message = sprintf(
            '<strong>🚩 Nouveau signalement de commentaire</strong><br>' .
            'Commentaire ID : <strong>#%d</strong><br>' .
            'Motif : <strong style="color:#c0392b;">%s</strong><br>' .
            'Texte : <em>"%s"</em><br>' .
            'Signalé par : %s<br>' .
            'Date : %s',
            $signalement->getTargetId(),
            htmlspecialchars($safeReasonLabel, ENT_QUOTES, 'UTF-8'),
            $textPreview,
            htmlspecialchars($reporter, ENT_QUOTES, 'UTF-8'),
            $formattedDate
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

    /**
     * Récupère les notifications non lues
     * 
     * @return array<int, SNotification>
     */
    public function getUnreadNotifications(int $limit = 100): array
    {
        /** @var EntityRepository<SNotification> $repository */
        $repository = $this->em->getRepository(SNotification::class);
        
        /** @var array<int, SNotification> $notifications */
        $notifications = $repository->findBy(
            ['lu' => false], 
            ['dateCreation' => 'DESC'], 
            $limit
        );
        
        // Correction ligne 89: On sait que findBy retourne toujours un tableau
        return $notifications;
    }

    /**
     * Compte les notifications non lues
     */
    public function countUnreadNotifications(): int
    {
        /** @var EntityRepository<SNotification> $repository */
        $repository = $this->em->getRepository(SNotification::class);
        
        /** @var int<0, max> $count */
        $count = $repository->count(['lu' => false]);
        
        // Correction ligne 100: On sait que count retourne toujours un int
        return $count;
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(SNotification $snotification): void
    {
        $snotification->setLu(true);
        $this->em->flush();
    }

    /**
     * Marque toutes les notifications comme lues
     */
    public function markAllAsRead(): void
    {
        $this->em->createQuery(
            'UPDATE App\Entity\SNotification s SET s.lu = true WHERE s.lu = false'
        )->execute();
    }

    /**
     * Supprime les notifications anciennes
     * 
     * @return int Nombre de notifications supprimées
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        $date = new \DateTime("-{$daysOld} days");
        
        /** @var int $result */
        $result = $this->em->createQuery(
            'DELETE FROM App\Entity\SNotification s WHERE s.dateCreation < :date'
        )->setParameter('date', $date)
         ->execute();
        
        return $result;
    }
}