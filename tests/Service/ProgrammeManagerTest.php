<?php

namespace App\Tests\Service;

use App\Entity\Programme;
use App\Service\ProgrammeManager;
use PHPUnit\Framework\TestCase;

class ProgrammeManagerTest extends TestCase
{
    // ─────────────────────────────────────────────
    // ✅ CAS VALIDE
    // ─────────────────────────────────────────────

    public function testProgrammeValide(): void
    {
        $programme = new Programme();
        $programme->setIdProg('PROG-001');
        $programme->setNom('Safari en Afrique');
        $programme->setDescription('Exploration de la faune africaine.');
        $programme->setDateDebut(new \DateTime('2025-07-10'));
        $programme->setDateFin(new \DateTime('2025-07-20'));
        $programme->setLieu('Kenya');
        $programme->setActiviteAssociee('Safari photo');
        $programme->setHotel('Sarova Lion Hill Lodge');

        $manager = new ProgrammeManager();

        $this->assertTrue($manager->validate($programme));
    }

    // ─────────────────────────────────────────────
    // ❌ NOM
    // ─────────────────────────────────────────────

    public function testNomObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du programme est obligatoire.');

        $programme = $this->buildValidProgramme();
        $programme->setNom('');

        (new ProgrammeManager())->validate($programme);
    }

    public function testNomAvecChiffres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom ne doit pas contenir de chiffres.');

        $programme = $this->buildValidProgramme();
        $programme->setNom('Programme2025');

        (new ProgrammeManager())->validate($programme);
    }

    // ─────────────────────────────────────────────
    // ❌ DESCRIPTION
    // ─────────────────────────────────────────────

    public function testDescriptionObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire.');

        $programme = $this->buildValidProgramme();
        $programme->setDescription('');

        (new ProgrammeManager())->validate($programme);
    }

    // ─────────────────────────────────────────────
    // ❌ DATES
    // ─────────────────────────────────────────────

    public function testDateDebutObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de début est obligatoire.');

        $programme = new Programme();
        $programme->setIdProg('PROG-002');
        $programme->setNom('Programme Valide');
        $programme->setDescription('Description valide.');
        // dateDebut non définie → null
        $programme->setDateFin(new \DateTime('2025-08-20'));
        $programme->setLieu('Paris');
        $programme->setActiviteAssociee('Visite culturelle');
        $programme->setHotel('Hotel Paris');

        (new ProgrammeManager())->validate($programme);
    }

    public function testDateFinObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin est obligatoire.');

        $programme = new Programme();
        $programme->setIdProg('PROG-003');
        $programme->setNom('Programme Valide');
        $programme->setDescription('Description valide.');
        $programme->setDateDebut(new \DateTime('2025-08-01'));
        // dateFin non définie → null
        $programme->setLieu('Paris');
        $programme->setActiviteAssociee('Visite culturelle');
        $programme->setHotel('Hotel Paris');

        (new ProgrammeManager())->validate($programme);
    }

    public function testDateFinAvantDateDebut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $programme = $this->buildValidProgramme();
        $programme->setDateDebut(new \DateTime('2025-08-20'));
        $programme->setDateFin(new \DateTime('2025-08-10')); // antérieure

        (new ProgrammeManager())->validate($programme);
    }

    public function testDateFinEgaleADateDebut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $programme = $this->buildValidProgramme();
        $programme->setDateDebut(new \DateTime('2025-08-10'));
        $programme->setDateFin(new \DateTime('2025-08-10')); // même jour

        (new ProgrammeManager())->validate($programme);
    }

    // ─────────────────────────────────────────────
    // ❌ LIEU
    // ─────────────────────────────────────────────

    public function testLieuObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le lieu est obligatoire.');

        $programme = $this->buildValidProgramme();
        $programme->setLieu('');

        (new ProgrammeManager())->validate($programme);
    }

    // ─────────────────────────────────────────────
    // ❌ ACTIVITÉ ASSOCIÉE
    // ─────────────────────────────────────────────

    public function testActiviteAssocieeObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'activité associée est obligatoire.");

        $programme = $this->buildValidProgramme();
        $programme->setActiviteAssociee('');

        (new ProgrammeManager())->validate($programme);
    }

    // ─────────────────────────────────────────────
    // ❌ HÔTEL
    // ─────────────────────────────────────────────

    public function testHotelObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'hôtel est obligatoire.");

        $programme = $this->buildValidProgramme();
        $programme->setHotel('');

        (new ProgrammeManager())->validate($programme);
    }

    // ─────────────────────────────────────────────
    // 🔧 HELPER — programme valide de base
    // ─────────────────────────────────────────────

    private function buildValidProgramme(): Programme
    {
        $programme = new Programme();
        $programme->setIdProg('PROG-000');
        $programme->setNom('Safari en Afrique');
        $programme->setDescription('Exploration de la faune africaine.');
        $programme->setDateDebut(new \DateTime('2025-07-10'));
        $programme->setDateFin(new \DateTime('2025-07-20'));
        $programme->setLieu('Kenya');
        $programme->setActiviteAssociee('Safari photo');
        $programme->setHotel('Sarova Lion Hill Lodge');
        return $programme;
    }
}
