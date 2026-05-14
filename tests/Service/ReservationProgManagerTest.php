<?php

namespace App\Tests\Service;

use App\Entity\ReservationProg;
use App\Service\ReservationProgManager;
use PHPUnit\Framework\TestCase;

class ReservationProgManagerTest extends TestCase
{
    // ─────────────────────────────────────────────
    // ✅ CAS VALIDE
    // ─────────────────────────────────────────────

    public function testReservationValide(): void
    {
        $reservation = new ReservationProg();
        $reservation->setNom('Ben Ali');
        $reservation->setPrenom('Sami');
        $reservation->setTelephone('22334455');
        $reservation->setNbre(3);
        $reservation->setEmail('sami.benali@gmail.com');
        $reservation->setIdP('PROG-001');

        $manager = new ReservationProgManager();

        $this->assertTrue($manager->validate($reservation));
    }

    // ─────────────────────────────────────────────
    // ❌ NOM
    // ─────────────────────────────────────────────

    public function testNomObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire.');

        $reservation = $this->buildValidReservation();
        $reservation->setNom('');

        (new ReservationProgManager())->validate($reservation);
    }

    public function testNomTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom doit contenir au moins 2 caractères.');

        $reservation = $this->buildValidReservation();
        $reservation->setNom('A');

        (new ReservationProgManager())->validate($reservation);
    }

    public function testNomTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom ne peut pas dépasser 50 caractères.');

        $reservation = $this->buildValidReservation();
        $reservation->setNom(str_repeat('A', 51));

        (new ReservationProgManager())->validate($reservation);
    }

    public function testNomAvecCaracteresInvalides(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom ne doit contenir que des lettres, espaces ou tirets.');

        $reservation = $this->buildValidReservation();
        $reservation->setNom('Ben@Ali123');

        (new ReservationProgManager())->validate($reservation);
    }

    // ─────────────────────────────────────────────
    // ❌ PRÉNOM
    // ─────────────────────────────────────────────

    public function testPrenomObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire.');

        $reservation = $this->buildValidReservation();
        $reservation->setPrenom('');

        (new ReservationProgManager())->validate($reservation);
    }

    public function testPrenomTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom doit contenir au moins 2 caractères.');

        $reservation = $this->buildValidReservation();
        $reservation->setPrenom('S');

        (new ReservationProgManager())->validate($reservation);
    }

    public function testPrenomAvecChiffres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom ne doit contenir que des lettres, espaces ou tirets.');

        $reservation = $this->buildValidReservation();
        $reservation->setPrenom('Sami99');

        (new ReservationProgManager())->validate($reservation);
    }

    // ─────────────────────────────────────────────
    // ❌ TÉLÉPHONE
    // ─────────────────────────────────────────────

    public function testTelephoneObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le téléphone est obligatoire.');

        $reservation = $this->buildValidReservation();
        $reservation->setTelephone('');

        (new ReservationProgManager())->validate($reservation);
    }

    public function testTelephoneTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le téléphone doit contenir exactement 8 chiffres.');

        $reservation = $this->buildValidReservation();
        $reservation->setTelephone('1234567'); // 7 chiffres

        (new ReservationProgManager())->validate($reservation);
    }

    public function testTelephoneTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le téléphone doit contenir exactement 8 chiffres.');

        $reservation = $this->buildValidReservation();
        $reservation->setTelephone('123456789'); // 9 chiffres

        (new ReservationProgManager())->validate($reservation);
    }

    public function testTelephoneAvecLettres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le téléphone doit contenir exactement 8 chiffres.');

        $reservation = $this->buildValidReservation();
        $reservation->setTelephone('1234ABCD');

        (new ReservationProgManager())->validate($reservation);
    }

    // ─────────────────────────────────────────────
    // ❌ NOMBRE DE PERSONNES
    // ─────────────────────────────────────────────

    public function testNbreZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de personnes doit être supérieur à 0.');

        $reservation = $this->buildValidReservation();
        $reservation->setNbre(0);

        (new ReservationProgManager())->validate($reservation);
    }

    public function testNbreNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de personnes doit être supérieur à 0.');

        $reservation = $this->buildValidReservation();
        $reservation->setNbre(-2);

        (new ReservationProgManager())->validate($reservation);
    }

    public function testNbreDepasseLimite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de personnes ne peut pas dépasser 20.');

        $reservation = $this->buildValidReservation();
        $reservation->setNbre(21);

        (new ReservationProgManager())->validate($reservation);
    }

    // ─────────────────────────────────────────────
    // ❌ EMAIL
    // ─────────────────────────────────────────────

    public function testEmailObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'email est obligatoire.");

        $reservation = $this->buildValidReservation();
        $reservation->setEmail('');

        (new ReservationProgManager())->validate($reservation);
    }

    public function testEmailInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Veuillez saisir un email valide.');

        $reservation = $this->buildValidReservation();
        $reservation->setEmail('email_invalide');

        (new ReservationProgManager())->validate($reservation);
    }

    public function testEmailSansDomaine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Veuillez saisir un email valide.');

        $reservation = $this->buildValidReservation();
        $reservation->setEmail('sami@');

        (new ReservationProgManager())->validate($reservation);
    }

    // ─────────────────────────────────────────────
    // ❌ ID PROGRAMME
    // ─────────────────────────────────────────────

    public function testIdProgrammeObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le programme est obligatoire.');

        $reservation = $this->buildValidReservation();
        $reservation->setIdP('');

        (new ReservationProgManager())->validate($reservation);
    }

    // ─────────────────────────────────────────────
    // 🔧 HELPER — réservation valide de base
    // ─────────────────────────────────────────────

    private function buildValidReservation(): ReservationProg
    {
        $reservation = new ReservationProg();
        $reservation->setNom('Ben Ali');
        $reservation->setPrenom('Sami');
        $reservation->setTelephone('22334455');
        $reservation->setNbre(3);
        $reservation->setEmail('sami.benali@gmail.com');
        $reservation->setIdP('PROG-001');
        return $reservation;
    }
}
