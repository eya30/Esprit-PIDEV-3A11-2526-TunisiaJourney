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

<<<<<<< HEAD
    /**
     * @param array{
     *     noteConfort: int,
     *     noteServices: int,
     *     noteEquipements: int,
     *     noteProprete: int,
     *     notePersonnel: int,
     *     noteEmplacement: int,
     *     noteRestauration: int,
     *     notePrixQualite: int,
     *     noteCalme: int,
     *     commentaire?: string|null
     * } $data
     */
    public function enregistrerAvis(ReservationChambre $reservation, User $user, array $data): AvisChambre
    {
        $avis = new AvisChambre();
        // Si vous voulez garder la relation, décommentez la ligne suivante :
        // $avis->setReservationChambre($reservation);
=======
    public function enregistrerAvis(ReservationChambre $reservation, User $user, array $data): AvisChambre
    {
        $avis = new AvisChambre();
        // $avis->setReservationChambre($reservation);  // ← COMMENTÉ (plus nécessaire)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
        // Si la relation existe, décommentez la ligne suivante :
        // $reservation->setAvisChambre($avis);
=======
        // $reservation->setAvisChambre($avis);  // ← COMMENTÉ aussi
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $this->em->flush();

        return $avis;
    }
}