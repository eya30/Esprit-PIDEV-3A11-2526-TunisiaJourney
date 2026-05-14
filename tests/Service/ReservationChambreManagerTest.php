<?php

namespace App\Tests\Service;

use App\Entity\ReservationChambre;
use App\Service\ReservationChambreManager;
use PHPUnit\Framework\TestCase;

class ReservationChambreManagerTest extends TestCase
{
    private ReservationChambreManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ReservationChambreManager();
    }

    // ✅ Réservation valide complète
    public function testReservationValide(): void
    {
        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setNom('Ben Ali');
        $reservation->setPrenom('Mohamed');
        $reservation->setEmail('mohamed.benali@gmail.com');

        $this->assertTrue($this->manager->validate($reservation));
    }

    // ✅ Réservation valide minimale (champs optionnels null)
    public function testReservationValideMinimale(): void
    {
        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(5);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+3 days'));
        $reservation->setNbNuit(2);
        $reservation->setPrixTotal('300.00');
        $reservation->setNbPersonnes(1);
        $reservation->setDetailsPrix('150€/nuit x 2 nuits');
        $reservation->setTelephone('22333444');
        $reservation->setStatut('en_attente');

        $this->assertTrue($this->manager->validate($reservation));
    }

    // ============ CHAMP : idUtilisateur ============

    // ❌ ID utilisateur négatif
    public function testIdUtilisateurNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID utilisateur est obligatoire et doit être positif.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(-1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : idCh ============

    // ❌ ID chambre négatif
    public function testIdChNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID de la chambre est obligatoire et doit être positif.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(-5);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : dateDebut ============

    // ❌ Date de début dans le passé
    public function testDateDebutDansLePasse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de début ne peut pas être dans le passé.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('-1 day'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Date de début null (obligatoire)
    public function testDateDebutNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de début est obligatoire.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : dateFin ============

    // ❌ Date de fin avant date de début
    public function testDateFinAvantDateDebut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de fin doit être postérieure à la date de début.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('+5 days'));
        $reservation->setDateFin(new \DateTime('+2 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Date de fin égale à date de début
    public function testDateFinEgaleADateDebut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de fin doit être postérieure à la date de début.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $date = new \DateTime('+3 days');
        $reservation->setDateDebut($date);
        $reservation->setDateFin(clone $date);
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Date de fin null (obligatoire)
    public function testDateFinNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La date de fin est obligatoire.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : nbNuit ============

    // ❌ Nombre de nuits négatif
    public function testNbNuitNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nombre de nuits est obligatoire et doit être positif.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(-3);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Nombre de nuits dépasse 90
    public function testNbNuitDepasseMaximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le séjour ne peut pas dépasser 90 nuits.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+100 days'));
        $reservation->setNbNuit(91);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 91 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : prixTotal ============

    // ❌ Prix total négatif
    public function testPrixTotalNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le prix total est obligatoire et doit être positif.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('-100.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Prix total dépasse 100 000 €
    public function testPrixTotalDepasseLimite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le prix total ne peut pas dépasser 100 000 €.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('150000.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : nbPersonnes ============

    // ❌ Nombre de personnes = 0 (hors plage basse)
    public function testNbPersonnesZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nombre de personnes doit être compris entre 1 et 10.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(0);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Nombre de personnes > 10 (hors plage haute)
    public function testNbPersonnesDepasseMaximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nombre de personnes doit être compris entre 1 et 10.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(11);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : detailsPrix ============

    // ❌ Détail du prix vide
    public function testDetailsPrixVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le détail du prix est obligatoire.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Détail du prix trop long (> 255 caractères)
    public function testDetailsPrixTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le détail du prix ne peut pas dépasser 255 caractères.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix(str_repeat('a', 256));
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : telephone ============

    // ❌ Téléphone vide
    public function testTelephoneVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone est obligatoire.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Téléphone trop court (< 8 caractères)
    public function testTelephoneTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone doit contenir entre 8 et 20 chiffres.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('123');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ❌ Téléphone avec lettres (format invalide)
    public function testTelephoneAvecLettres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de téléphone ne doit contenir que des chiffres, espaces, tirets ou le signe +.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('abcdefgh');
        $reservation->setStatut('confirmé');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : statut ============

    // ❌ Statut vide
    public function testStatutVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le statut est obligatoire.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('');

        $this->manager->validate($reservation);
    }

    // ❌ Statut valeur non autorisée
    public function testStatutInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le statut doit être : confirmé, en_attente, annulé ou terminé.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('suspendu');

        $this->manager->validate($reservation);
    }

    // ============ CHAMP : nom ============

    // ❌ Nom trop court (< 2 caractères)
    public function testNomTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit contenir entre 2 et 100 caractères.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setNom('A');

        $this->manager->validate($reservation);
    }

    // ❌ Nom avec caractères invalides (chiffres)
    public function testNomAvecCaracteresInvalides(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setNom('Ben123');

        $this->manager->validate($reservation);
    }

    // ✅ Nom null accepté (nullable)
    public function testNomNullAccepte(): void
    {
        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setNom(null);

        $this->assertTrue($this->manager->validate($reservation));
    }

    // ============ CHAMP : prenom ============

    // ❌ Prénom trop court (< 2 caractères)
    public function testPrenomTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le prénom doit contenir entre 2 et 100 caractères.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setPrenom('A');

        $this->manager->validate($reservation);
    }

    // ❌ Prénom avec caractères invalides
    public function testPrenomAvecCaracteresInvalides(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le prénom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setPrenom('Mohamed123!');

        $this->manager->validate($reservation);
    }

    // ✅ Prénom null accepté (nullable)
    public function testPrenomNullAccepte(): void
    {
        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setPrenom(null);

        $this->assertTrue($this->manager->validate($reservation));
    }

    // ============ CHAMP : email ============

    // ❌ Email invalide
    public function testEmailInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'adresse email n'est pas valide.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setEmail('email_invalide');

        $this->manager->validate($reservation);
    }

    // ❌ Email trop long (> 150 caractères)
    // CORRECTION : str_repeat('a', 145) + '@test.com' = 154 chars → valide syntaxiquement mais dépasse 150
    public function testEmailTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email ne peut pas dépasser 150 caractères.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        // 145 'a' + '@test.com' (9 chars) = 154 caractères total → dépasse 150
        $reservation->setEmail(str_repeat('a', 145) . '@test.com');

        $this->manager->validate($reservation);
    }

    // ✅ Email null accepté (nullable)
    public function testEmailNullAccepte(): void
    {
        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setEmail(null);

        $this->assertTrue($this->manager->validate($reservation));
    }

    // ============ CHAMP : montantRembourse ============

    // ❌ Montant remboursé négatif
    public function testMontantRembourseNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le montant remboursé doit être positif ou zéro.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('annulé');
        $reservation->setMontantRembourse('-50.00');

        $this->manager->validate($reservation);
    }

    // ❌ Montant remboursé supérieur au prix total
    public function testMontantRembourseSuperieureAuTotal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le montant remboursé ne peut pas dépasser le prix total.");

        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('200.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('50€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('annulé');
        $reservation->setMontantRembourse('300.00');

        $this->manager->validate($reservation);
    }

    // ✅ Montant remboursé null accepté (nullable)
    public function testMontantRembourseNullAccepte(): void
    {
        $reservation = new ReservationChambre();
        $reservation->setIdUtilisateur(1);
        $reservation->setIdCh(10);
        $reservation->setDateDebut(new \DateTime('tomorrow'));
        $reservation->setDateFin(new \DateTime('+5 days'));
        $reservation->setNbNuit(4);
        $reservation->setPrixTotal('600.00');
        $reservation->setNbPersonnes(2);
        $reservation->setDetailsPrix('150€/nuit x 4 nuits');
        $reservation->setTelephone('+216 22 333 444');
        $reservation->setStatut('confirmé');
        $reservation->setMontantRembourse(null);

        $this->assertTrue($this->manager->validate($reservation));
    }
}