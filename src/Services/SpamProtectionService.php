<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SpamProtectionService
{
    private EntityManagerInterface $em;
    private RequestStack $requestStack;

    private const MAX_COMMENTS_PER_MINUTE = 5;
    private const MAX_COMMENTS_PER_HOUR = 20;
    private const MAX_COMMENTS_PER_DAY = 50;
    private const COMMENT_COOLDOWN_SECONDS = 15;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
    }

    public function checkSpam(?int $userId, ?string $ipAddress = null): array
    {
        $errors = [];
        $now = new \DateTime();

        if (!$ipAddress && $this->requestStack->getCurrentRequest()) {
            $ipAddress = $this->requestStack->getCurrentRequest()->getClientIp();
        }

        if (!$userId && !$ipAddress) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(c.idC)')
           ->from('App\Entity\Commentaire', 'c')
           ->where('c.createdAt > :since');

        if ($userId) {
            $qb->andWhere('c.id = :userId')
               ->setParameter('userId', $userId);
        } else {
            $qb->andWhere('c.ipAddress = :ip')
               ->setParameter('ip', $ipAddress);
        }

        // Par minute
        $oneMinuteAgo = (clone $now)->modify('-1 minute');
        $countMinute = (clone $qb)->setParameter('since', $oneMinuteAgo)->getQuery()->getSingleScalarResult();
        if ($countMinute >= self::MAX_COMMENTS_PER_MINUTE) {
            $errors[] = 'Trop de commentaires. Veuillez patienter avant de poster.';
        }

        // Par heure
        $oneHourAgo = (clone $now)->modify('-1 hour');
        $countHour = (clone $qb)->setParameter('since', $oneHourAgo)->getQuery()->getSingleScalarResult();
        if ($countHour >= self::MAX_COMMENTS_PER_HOUR) {
            $errors[] = 'Limite horaire atteinte (20 commentaires/heure). Réessayez plus tard.';
        }

        // Par jour
        $oneDayAgo = (clone $now)->modify('-1 day');
        $countDay = (clone $qb)->setParameter('since', $oneDayAgo)->getQuery()->getSingleScalarResult();
        if ($countDay >= self::MAX_COMMENTS_PER_DAY) {
            $errors[] = 'Limite journalière atteinte (50 commentaires/jour). Revenez demain.';
        }

        // Cooldown
        $qbLast = $this->em->createQueryBuilder();
        $qbLast->select('c.createdAt')
               ->from('App\Entity\Commentaire', 'c')
               ->orderBy('c.createdAt', 'DESC')
               ->setMaxResults(1);

        if ($userId) {
            $qbLast->andWhere('c.id = :userId')->setParameter('userId', $userId);
        } else {
            $qbLast->andWhere('c.ipAddress = :ip')->setParameter('ip', $ipAddress);
        }

        $lastComment = $qbLast->getQuery()->getOneOrNullResult();
        if ($lastComment && isset($lastComment['createdAt'])) {
            $secondsSinceLast = $now->getTimestamp() - $lastComment['createdAt']->getTimestamp();
            if ($secondsSinceLast < self::COMMENT_COOLDOWN_SECONDS) {
                $errors[] = sprintf('Veuillez patienter %d secondes avant de poster un nouveau commentaire.', 
                    self::COMMENT_COOLDOWN_SECONDS - $secondsSinceLast);
            }
        }

        return $errors;
    }
}