<?php

namespace App\Tests\Service;

use App\Entity\ReservationAct;
use App\Service\ReservationActManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReservationActManagerTest extends KernelTestCase
{
    private ReservationActManager $manager;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = static::getContainer()->get('validator');
        $this->manager = new ReservationActManager($validator);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function makeValid(): ReservationAct
    {
        $r = new ReservationAct();
        $r->setUserId('user-abc');
        $r->setIDAct(1);
        $r->setNom('Ben Salah');
        $r->setPrenom('Amira');
        $r->setDateReservation(new \DateTime());
        $r->setNombrePlaces(3);
        $r->setPrix(75.0);
        $r->setEmail('amira.bensalah@example.com');
        $r->setTelephone('22334455');
        return $r;
    }

    private function getMessages(ReservationAct $r): array
    {
        $violations = $this->manager->validate($r);
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

    public function testReservationValideComplete(): void
    {
        $r = $this->makeValid();
        $this->assertTrue($this->manager->isValid($r), 'Une réservation complète et valide ne doit avoir aucune violation.');
    }

    // ─── Nom ─────────────────────────────────────────────────────────────────

    public function testNomVide(): void
    {
        $r = $this->makeValid();
        $r->setNom('');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('Nom', $msgs);
        $this->assertStringContainsString('requis', $msgs['Nom']);
    }

    public function testNomTropCourt(): void
    {
        $r = $this->makeValid();
        $r->setNom('Ali'); // 3 chars < 4
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('Nom', $msgs);
        $this->assertStringContainsString('4', $msgs['Nom']);
    }

    public function testNomAvecChiffres(): void
    {
        $r = $this->makeValid();
        $r->setNom('Ben2Salah');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('Nom', $msgs);
        $this->assertStringContainsString('lettres', $msgs['Nom']);
    }

    public function testNomAvecCaracteresSpeciaux(): void
    {
        $r = $this->makeValid();
        $r->setNom('Ben@Salah');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('Nom', $msgs);
    }

    public function testNomValide(): void
    {
        $r = $this->makeValid();
        $r->setNom('El-Mansouri');
        $msgs = $this->getMessages($r);
        $this->assertArrayNotHasKey('Nom', $msgs);
    }

    // ─── Prenom ──────────────────────────────────────────────────────────────

    public function testPrenomVide(): void
    {
        $r = $this->makeValid();
        $r->setPrenom('');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('Prenom', $msgs);
    }

    public function testPrenomTropCourt(): void
    {
        $r = $this->makeValid();
        $r->setPrenom('Ana'); // 3 chars < 4
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('Prenom', $msgs);
    }

    public function testPrenomAvecChiffres(): void
    {
        $r = $this->makeValid();
        $r->setPrenom('Am1ra');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('Prenom', $msgs);
    }

    public function testPrenomValide(): void
    {
        $r = $this->makeValid();
        $r->setPrenom('Marie-Claire');
        $msgs = $this->getMessages($r);
        $this->assertArrayNotHasKey('Prenom', $msgs);
    }

    // ─── NombrePlaces ────────────────────────────────────────────────────────

    public function testNombrePlacesVide(): void
    {
        $r = $this->makeValid();
        $r->setNombrePlaces(null);
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('NombrePlaces', $msgs);
    }

    public function testNombrePlacesZero(): void
    {
        $r = $this->makeValid();
        $r->setNombrePlaces(0);
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('NombrePlaces', $msgs);
        $this->assertStringContainsString('supérieur', $msgs['NombrePlaces']);
    }

    public function testNombrePlacesNegatif(): void
    {
        $r = $this->makeValid();
        $r->setNombrePlaces(-3);
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('NombrePlaces', $msgs);
    }

    public function testNombrePlacesDepasseLimite(): void
    {
        $r = $this->makeValid();
        $r->setNombrePlaces(101); // > 100
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('NombrePlaces', $msgs);
        $this->assertStringContainsString('100', $msgs['NombrePlaces']);
    }

    public function testNombrePlacesLimiteExacte(): void
    {
        $r = $this->makeValid();
        $r->setNombrePlaces(100);
        $msgs = $this->getMessages($r);
        $this->assertArrayNotHasKey('NombrePlaces', $msgs);
    }

    public function testNombrePlacesValide(): void
    {
        $r = $this->makeValid();
        $r->setNombrePlaces(5);
        $msgs = $this->getMessages($r);
        $this->assertArrayNotHasKey('NombrePlaces', $msgs);
    }

    // ─── Email ───────────────────────────────────────────────────────────────

    public function testEmailVide(): void
    {
        $r = $this->makeValid();
        $r->setEmail('');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('email', $msgs);
        $this->assertStringContainsString('requis', $msgs['email']);
    }

    public function testEmailSansArobase(): void
    {
        $r = $this->makeValid();
        $r->setEmail('emailsansarobase.com');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('email', $msgs);
    }

    public function testEmailSansDomaine(): void
    {
        $r = $this->makeValid();
        $r->setEmail('test@');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('email', $msgs);
    }

    public function testEmailValide(): void
    {
        $r = $this->makeValid();
        $r->setEmail('contact@tunisie-event.tn');
        $msgs = $this->getMessages($r);
        $this->assertArrayNotHasKey('email', $msgs);
    }

    // ─── Telephone ───────────────────────────────────────────────────────────

    public function testTelephoneVide(): void
    {
        $r = $this->makeValid();
        $r->setTelephone('');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('telephone', $msgs);
    }

    public function testTelephoneTropCourt(): void
    {
        $r = $this->makeValid();
        $r->setTelephone('1234567'); // 7 chiffres < 8
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('telephone', $msgs);
        $this->assertStringContainsString('8', $msgs['telephone']);
    }

    public function testTelephoneTropLong(): void
    {
        $r = $this->makeValid();
        $r->setTelephone('123456789'); // 9 chiffres > 8
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('telephone', $msgs);
    }

    public function testTelephoneAvecLettres(): void
    {
        $r = $this->makeValid();
        $r->setTelephone('2233AA55');
        $msgs = $this->getMessages($r);
        $this->assertArrayHasKey('telephone', $msgs);
        $this->assertStringContainsString('chiffres', $msgs['telephone']);
    }

    public function testTelephoneValide(): void
    {
        $r = $this->makeValid();
        $r->setTelephone('55667788');
        $msgs = $this->getMessages($r);
        $this->assertArrayNotHasKey('telephone', $msgs);
    }

    // ─── Status ──────────────────────────────────────────────────────────────

    public function testStatusParDefautConfirme(): void
    {
        $r = new ReservationAct();
        $this->assertEquals('confirmé', $r->getStatus());
        $this->assertTrue($r->isConfirmed());
        $this->assertFalse($r->isCancelled());
    }

    public function testStatusAnnule(): void
    {
        $r = $this->makeValid();
        $r->setStatus(ReservationAct::STATUS_CANCELLED);
        $this->assertTrue($r->isCancelled());
        $this->assertFalse($r->isConfirmed());
    }

    public function testStatusInvalideLeveException(): void
    {
        $r = $this->makeValid();
        $this->expectException(\InvalidArgumentException::class);
        $r->setStatus('en_attente'); // valeur non autorisée
    }
}