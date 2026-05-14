<?php

namespace App\Tests\Service;

use App\Entity\Voyage;
use App\Service\VoyageManager;
use PHPUnit\Framework\TestCase;

class VoyageManagerTest extends TestCase
{
    // ─────────────────────────────────────────────
    // ✅ CAS VALIDE
    // ─────────────────────────────────────────────

    public function testVoyageValide(): void
    {
        $voyage = new Voyage();
        $voyage->setNom('Voyage en Italie');
        $voyage->setDescription('Un magnifique voyage à travers la Toscane.');
        $voyage->setCapacite(50);
        $voyage->setPrix(1200.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        $manager = new VoyageManager();

        $this->assertTrue($manager->validate($voyage));
    }

    // ─────────────────────────────────────────────
    // ❌ NOM
    // ─────────────────────────────────────────────

    public function testNomObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du voyage est obligatoire.');

        $voyage = new Voyage();
        $voyage->setNom('');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(30);
        $voyage->setPrix(500.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    public function testNomAvecChiffres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du voyage ne doit pas contenir de chiffres.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage123');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(30);
        $voyage->setPrix(500.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    // ─────────────────────────────────────────────
    // ❌ DESCRIPTION
    // ─────────────────────────────────────────────

    public function testDescriptionObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('');
        $voyage->setCapacite(30);
        $voyage->setPrix(500.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    // ─────────────────────────────────────────────
    // ❌ CAPACITÉ
    // ─────────────────────────────────────────────

    public function testCapaciteNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être un nombre positif.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(-5);
        $voyage->setPrix(500.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    public function testCapaciteZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être un nombre positif.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(0);
        $voyage->setPrix(500.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    public function testCapaciteDepasseLimite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité ne peut pas dépasser 200 personnes.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(201);
        $voyage->setPrix(500.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    // ─────────────────────────────────────────────
    // ❌ PRIX
    // ─────────────────────────────────────────────

    public function testPrixNul(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être supérieur à 0.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(30);
        $voyage->setPrix(0);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    public function testPrixNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être supérieur à 0.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(30);
        $voyage->setPrix(-100.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        $voyage->setHeure(new \DateTime('09:00:00'));

        (new VoyageManager())->validate($voyage);
    }

    // ─────────────────────────────────────────────
    // ❌ DATE & HEURE
    // ─────────────────────────────────────────────

    public function testDateCreationObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de création est obligatoire.');

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(30);
        $voyage->setPrix(500.00);
        $voyage->setHeure(new \DateTime('09:00:00'));
        // dateCreation non définie → null

        (new VoyageManager())->validate($voyage);
    }

    public function testHeureObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'heure est obligatoire.");

        $voyage = new Voyage();
        $voyage->setNom('Voyage Valide');
        $voyage->setDescription('Description valide.');
        $voyage->setCapacite(30);
        $voyage->setPrix(500.00);
        $voyage->setDateCreation(new \DateTime('2025-06-01'));
        // heure non définie → null

        (new VoyageManager())->validate($voyage);
    }
}
