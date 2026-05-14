<?php

namespace App\Tests\Service;

use App\Entity\Activite;
use App\Entity\Evenement;
use App\Service\ActiviteManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ActiviteManagerTest extends KernelTestCase
{
    private ActiviteManager $manager;
    private Evenement $evenement;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = static::getContainer()->get('validator');
        $this->manager = new ActiviteManager($validator);

        // Création d'un Evenement fictif pour satisfaire la contrainte NotNull
        $this->evenement = new Evenement();
        $this->evenement->setTitre('Evenement Test Valide');
        $this->evenement->setDescription('Description valide pour le test unitaire');
        $this->evenement->setDateDebut((new \DateTime())->modify('+10 days'));
        $this->evenement->setDateFin((new \DateTime())->modify('+15 days'));
        $this->evenement->setLieu('Tunis Lac');
        $this->evenement->setCapaciteMax(100);
        $this->evenement->setOrganisateur('Organisateur Valide');
        $this->evenement->setUserId('user-123');
    }

    // ─── Helper pour créer une activité valide ────────────────────────────────

    private function makeValid(): Activite
    {
        $a = new Activite();
        $a->setTitre('Yoga matinal');
        $a->setDescription('Séance de yoga pour bien commencer la journée');
        $a->setTypeActivite('Sport bien-être');
        $a->setHeureDebut('08:00');
        $a->setDuree('1h30min');
        $a->setNomAnimateur('Sarra Ben Ali');
        $a->setCapaciteM(30);
        $a->setPrix(25.0);
        $a->setEvenement($this->evenement);
        return $a;
    }

    private function getMessages(Activite $a): array
    {
        $violations = $this->manager->validate($a);
        $messages = [];
        foreach ($violations as $v) {
            $path = $v->getPropertyPath();
            if (!isset($messages[$path])) {
                $messages[$path] = $v->getMessage();
            }
        }
        return $messages;
    }

    // ─── Cas valide global ────────────────────────────────────────────────────

    public function testActiviteValideComplète(): void
    {
        $a = $this->makeValid();
        $this->assertTrue($this->manager->isValid($a), 'Une activité complète et valide ne doit pas avoir de violations.');
    }

    // ─── Titre ────────────────────────────────────────────────────────────────

    public function testTitreVide(): void
    {
        $a = $this->makeValid();
        $a->setTitre('');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Titre', $msgs);
        $this->assertStringContainsString('obligatoire', $msgs['Titre']);
    }

    public function testTitreTropCourt(): void
    {
        $a = $this->makeValid();
        $a->setTitre('Yoga'); // 4 chars < 6
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Titre', $msgs);
        $this->assertStringContainsString('6', $msgs['Titre']);
    }

    public function testTitreAvecChiffres(): void
    {
        $a = $this->makeValid();
        $a->setTitre('Yoga 2025');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Titre', $msgs);
        $this->assertStringContainsString('chiffres', $msgs['Titre']);
    }

    public function testTitreValide(): void
    {
        $a = $this->makeValid();
        $a->setTitre('Pilates avancé');
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('Titre', $msgs);
    }

    // ─── Description ─────────────────────────────────────────────────────────

    public function testDescriptionVide(): void
    {
        $a = $this->makeValid();
        $a->setDescription('');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Description', $msgs);
    }

    public function testDescriptionTropCourte(): void
    {
        $a = $this->makeValid();
        $a->setDescription('Trop court'); // < 15 chars
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Description', $msgs);
        $this->assertStringContainsString('15', $msgs['Description']);
    }

    public function testDescriptionSansLettre(): void
    {
        $a = $this->makeValid();
        $a->setDescription('123456789012345'); // 15 chars mais aucune lettre
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Description', $msgs);
        $this->assertStringContainsString('lettre', $msgs['Description']);
    }

    public function testDescriptionValide(): void
    {
        $a = $this->makeValid();
        $a->setDescription('Une description valide et suffisamment longue.');
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('Description', $msgs);
    }

    // ─── TypeActivite ─────────────────────────────────────────────────────────

    public function testTypeActiviteVide(): void
    {
        $a = $this->makeValid();
        $a->setTypeActivite('');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('TypeActivite', $msgs);
    }

    public function testTypeActiviteTropCourt(): void
    {
        $a = $this->makeValid();
        $a->setTypeActivite('Art'); // < 5 chars
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('TypeActivite', $msgs);
    }

    public function testTypeActiviteAvecChiffres(): void
    {
        $a = $this->makeValid();
        $a->setTypeActivite('Sport123');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('TypeActivite', $msgs);
    }

    public function testTypeActiviteValide(): void
    {
        $a = $this->makeValid();
        $a->setTypeActivite('Atelier créatif');
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('TypeActivite', $msgs);
    }

    // ─── HeureDebut ──────────────────────────────────────────────────────────

    public function testHeureDebutVide(): void
    {
        $a = $this->makeValid();
        $a->setHeureDebut('');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('HeureDebut', $msgs);
    }

    public function testHeureDebutMauvaisFormat(): void
    {
        $a = $this->makeValid();
        $a->setHeureDebut('8h30'); // pas HH:MM
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('HeureDebut', $msgs);
        $this->assertStringContainsString('HH:MM', $msgs['HeureDebut']);
    }

    public function testHeureDebutHeureInvalide(): void
    {
        $a = $this->makeValid();
        $a->setHeureDebut('25:00'); // heure > 23
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('HeureDebut', $msgs);
    }

    public function testHeureDebutValide(): void
    {
        $a = $this->makeValid();
        $a->setHeureDebut('09:30');
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('HeureDebut', $msgs);
    }

    // ─── Duree ───────────────────────────────────────────────────────────────

    public function testDureeVide(): void
    {
        $a = $this->makeValid();
        $a->setDuree('');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Duree', $msgs);
    }

    public function testDureeMauvaisFormat(): void
    {
        $a = $this->makeValid();
        $a->setDuree('deux heures'); // texte libre
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Duree', $msgs);
    }

    /**
     * @dataProvider dureeValideProvider
     */
    public function testDureeValide(string $duree): void
    {
        $a = $this->makeValid();
        $a->setDuree($duree);
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('Duree', $msgs, "La durée '$duree' devrait être valide.");
    }

    public static function dureeValideProvider(): array
    {
        return [
            ['1h'],
            ['45min'],
            ['2h30min'],
            ['10h'],
            ['120min'],
        ];
    }

    // ─── NomAnimateur ────────────────────────────────────────────────────────

    public function testNomAnimateurVide(): void
    {
        $a = $this->makeValid();
        $a->setNomAnimateur('');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('NomAnimateur', $msgs);
    }

    public function testNomAnimateurTropCourt(): void
    {
        $a = $this->makeValid();
        $a->setNomAnimateur('Ali'); // < 5 chars
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('NomAnimateur', $msgs);
    }

    public function testNomAnimateurAvecChiffres(): void
    {
        $a = $this->makeValid();
        $a->setNomAnimateur('Ali123Ben');
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('NomAnimateur', $msgs);
    }

    public function testNomAnimateurValide(): void
    {
        $a = $this->makeValid();
        $a->setNomAnimateur('Mohamed Amine');
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('NomAnimateur', $msgs);
    }

    // ─── CapaciteM ───────────────────────────────────────────────────────────

    public function testCapaciteMVide(): void
    {
        $a = $this->makeValid();
        $a->setCapaciteM(null);
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('CapaciteM', $msgs);
    }

    public function testCapaciteMNegative(): void
    {
        $a = $this->makeValid();
        $a->setCapaciteM(-5);
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('CapaciteM', $msgs);
        $this->assertStringContainsString('supérieure', $msgs['CapaciteM']);
    }

    public function testCapaciteMZero(): void
    {
        $a = $this->makeValid();
        $a->setCapaciteM(0);
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('CapaciteM', $msgs);
    }

    public function testCapaciteMValide(): void
    {
        $a = $this->makeValid();
        $a->setCapaciteM(50);
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('CapaciteM', $msgs);
    }

    // ─── Prix ────────────────────────────────────────────────────────────────

    public function testPrixVide(): void
    {
        $a = $this->makeValid();
        $a->setPrix(null);
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Prix', $msgs);
    }

    public function testPrixNegatif(): void
    {
        $a = $this->makeValid();
        $a->setPrix(-10.0);
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('Prix', $msgs);
        $this->assertStringContainsString('0', $msgs['Prix']);
    }

    public function testPrixZeroValide(): void
    {
        $a = $this->makeValid();
        $a->setPrix(0.0);
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('Prix', $msgs);
    }

    public function testPrixValide(): void
    {
        $a = $this->makeValid();
        $a->setPrix(15.50);
        $msgs = $this->getMessages($a);
        $this->assertArrayNotHasKey('Prix', $msgs);
    }

    // ─── Evenement (NotNull) ──────────────────────────────────────────────────

    public function testEvenementNull(): void
    {
        $a = $this->makeValid();
        $a->setEvenement(null);
        $msgs = $this->getMessages($a);
        $this->assertArrayHasKey('evenement', $msgs);
    }
}