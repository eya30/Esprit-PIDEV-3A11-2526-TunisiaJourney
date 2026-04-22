<?php

namespace App\Service;

use App\Entity\ReservationChambre;
use App\Entity\User;
use App\Entity\AvisChambre;
use Doctrine\ORM\EntityManagerInterface;

class AvisChambreService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function enregistrerAvis(ReservationChambre $reservation, User $user, array $data): AvisChambre
    {
        $avis = new AvisChambre();
        // $avis->setReservationChambre($reservation);  // ← COMMENTÉ (plus nécessaire)
        $avis->setUtilisateur($user);
        $avis->setDateCreation(new \DateTime());
        $avis->setNoteConfort($data['noteConfort']);
        $avis->setNoteServices($data['noteServices']);
        $avis->setNoteEquipements($data['noteEquipements']);
        $avis->setNoteProprete($data['noteProprete']);
        $avis->setNotePersonnel($data['notePersonnel']);
        $avis->setNoteEmplacement($data['noteEmplacement']);
        $avis->setNoteRestauration($data['noteRestauration']);
        $avis->setNotePrixQualite($data['notePrixQualite']);
        $avis->setNoteCalme($data['noteCalme']);
        $avis->setCommentaire($data['commentaire'] ?? null);
        $avis->setEstPublie(true);

        $this->em->persist($avis);
        // $reservation->setAvisChambre($avis);  // ← COMMENTÉ aussi
        $this->em->flush();

        return $avis;
    }
}