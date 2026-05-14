<?php

namespace App\Tests\Service;

use App\Entity\Evenement;
use App\Service\EvenementManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EvenementManagerTest extends KernelTestCase
{
    private EvenementManager $manager;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = static::getContainer()->get('validator');
        $this->manager = new EvenementManager($validator);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function makeValid(): Evenement
    {
        $e = new Evenement();
        $e->setTitre('Festival de musique');
        $e->setDescription('Un grand festival annuel de musique moderne en plein air.');
        $e->setDateDebut((new \DateTime())->modify('+10 days'));
        $e->setDateFin((new \DateTime())->modify('+15 days'));
        $e->setLieu('Amphithéâtre de Carthage');
        $e->setCapaciteMax(500);
        $e->setOrganisateur('Association Tunisie Culture');
        $e->setUserId('user-001');
        return $e;
    }

    private function getMessages(Evenement $e): array
    {
        $violations = $this->manager->validate($e);
        $messages = [];
        foreach ($violations as $v) {
            $path = $v->getPropertyPath();
            if (!isset($messages[$path])) {
                $messages[$path] = $v->getMessage();
            }
        }
        return $messages;
    }

    // ─── Cas valide ───────────────────────────────────────────────────────────

    public function testEvenementValideComplet(): void
    {
        $e = $this->makeValid();
        $this->assertTrue($this->manager->isValid($e), 'Un événement complet et valide ne doit avoir aucune violation.');
    }

    // ─── Titre ────────────────────────────────────────────────────────────────

    public function testTitreVide(): void
    {
        $e = $this->makeValid();
        $e->setTitre('');
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Titre', $msgs);
        $this->assertStringContainsString('obligatoire', $msgs['Titre']);
    }

    public function testTitreTropCourt(): void
    {
        $e = $this->makeValid();
        $e->setTitre('Concert'); // 7 chars < 8
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Titre', $msgs);
        $this->assertStringContainsString('8', $msgs['Titre']);
    }

    public function testTitreAvecChiffres(): void
    {
        $e = $this->makeValid();
        $e->setTitre('Festival 2025');
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Titre', $msgs);
        $this->assertStringContainsString('chiffres', $msgs['Titre']);
    }

    public function testTitreValide(): void
    {
        $e = $this->makeValid();
        $e->setTitre('Carnaval de printemps');
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('Titre', $msgs);
    }

    // ─── Description ─────────────────────────────────────────────────────────

    public function testDescriptionVide(): void
    {
        $e = $this->makeValid();
        $e->setDescription('');
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Description', $msgs);
    }

    public function testDescriptionTropCourte(): void
    {
        $e = $this->makeValid();
        $e->setDescription('Trop court ici'); // < 15 chars
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Description', $msgs);
    }

    public function testDescriptionSansLettre(): void
    {
        $e = $this->makeValid();
        $e->setDescription('123456789012345'); // 15 chars, aucune lettre
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Description', $msgs);
    }

    public function testDescriptionValide(): void
    {
        $e = $this->makeValid();
        $e->setDescription('Description suffisamment longue et valide pour le test.');
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('Description', $msgs);
    }

    // ─── DateDebut ───────────────────────────────────────────────────────────

    public function testDateDebutNulle(): void
    {
        $e = $this->makeValid();
        $e->setDateDebut(null);
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('DateDebut', $msgs);
    }

    public function testDateDebutDansLePasse(): void
    {
        $e = $this->makeValid();
        $e->setDateDebut(new \DateTime('2020-01-01'));
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('DateDebut', $msgs);
        $this->assertStringContainsString('passé', $msgs['DateDebut']);
    }

    public function testDateDebutMoinsDecinqJours(): void
    {
        $e = $this->makeValid();
        $e->setDateDebut((new \DateTime())->modify('+2 days')); // < 5 jours
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('DateDebut', $msgs);
        $this->assertStringContainsString('5 jours', $msgs['DateDebut']);
    }

    public function testDateDebutValide(): void
    {
        $e = $this->makeValid();
        $e->setDateDebut((new \DateTime())->modify('+10 days'));
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('DateDebut', $msgs);
    }

    // ─── DateFin ─────────────────────────────────────────────────────────────

    public function testDateFinNulle(): void
    {
        $e = $this->makeValid();
        $e->setDateFin(null);
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('DateFin', $msgs);
    }

    public function testDateFinAvantDateDebut(): void
    {
        $e = $this->makeValid();
        $debut = (new \DateTime())->modify('+10 days');
        $fin   = (new \DateTime())->modify('+8 days'); // avant début
        $e->setDateDebut($debut);
        $e->setDateFin($fin);
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('DateFin', $msgs);
        $this->assertStringContainsString('supérieure', $msgs['DateFin']);
    }

    public function testDateFinEgaleDebut(): void
    {
        $e = $this->makeValid();
        $date = (new \DateTime())->modify('+10 days');
        $e->setDateDebut($date);
        $e->setDateFin(clone $date);
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('DateFin', $msgs);
    }

    public function testDateFinValide(): void
    {
        $e = $this->makeValid();
        $e->setDateDebut((new \DateTime())->modify('+10 days'));
        $e->setDateFin((new \DateTime())->modify('+20 days'));
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('DateFin', $msgs);
    }

    // ─── Lieu ─────────────────────────────────────────────────────────────────

    public function testLieuVide(): void
    {
        $e = $this->makeValid();
        $e->setLieu('');
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Lieu', $msgs);
    }

    public function testLieuUniquementChiffres(): void
    {
        $e = $this->makeValid();
        $e->setLieu('12345');
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Lieu', $msgs);
    }

    public function testLieuValide(): void
    {
        $e = $this->makeValid();
        $e->setLieu('Parc de la Marsa');
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('Lieu', $msgs);
    }

    // ─── CapaciteMax ─────────────────────────────────────────────────────────

    public function testCapaciteMaxNulle(): void
    {
        $e = $this->makeValid();
        $e->setCapaciteMax(null);
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('CapaciteMax', $msgs);
    }

    public function testCapaciteMaxNegative(): void
    {
        $e = $this->makeValid();
        $e->setCapaciteMax(-10);
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('CapaciteMax', $msgs);
    }

    public function testCapaciteMaxZero(): void
    {
        $e = $this->makeValid();
        $e->setCapaciteMax(0);
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('CapaciteMax', $msgs);
    }

    public function testCapaciteMaxValide(): void
    {
        $e = $this->makeValid();
        $e->setCapaciteMax(200);
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('CapaciteMax', $msgs);
    }

    // ─── Organisateur ────────────────────────────────────────────────────────

    public function testOrganisateurVide(): void
    {
        $e = $this->makeValid();
        $e->setOrganisateur('');
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Organisateur', $msgs);
    }

    public function testOrganisateurTropCourt(): void
    {
        $e = $this->makeValid();
        $e->setOrganisateur('Amine'); // < 8 chars
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Organisateur', $msgs);
        $this->assertStringContainsString('8', $msgs['Organisateur']);
    }

    public function testOrganisateurAvecChiffres(): void
    {
        $e = $this->makeValid();
        $e->setOrganisateur('Organis1teur');
        $msgs = $this->getMessages($e);
        $this->assertArrayHasKey('Organisateur', $msgs);
    }

    public function testOrganisateurValide(): void
    {
        $e = $this->makeValid();
        $e->setOrganisateur('Mairie de Tunis');
        $msgs = $this->getMessages($e);
        $this->assertArrayNotHasKey('Organisateur', $msgs);
    }
}