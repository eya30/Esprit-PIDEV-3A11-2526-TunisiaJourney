<?php

namespace App\Tests\Service;

use App\Entity\Publication;
use App\Service\PublicationManager;
use PHPUnit\Framework\TestCase;

class PublicationManagerTest extends TestCase
{
    private PublicationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new PublicationManager();
    }

    // ✅ CAS VALIDE — sans média
    public function testValidPublicationSansMedia(): void
    {
        $pub = new Publication();
        $pub->setDescription('Une belle publication sur la technologie.');
        $pub->setId(42);
        $pub->setVues(0);

        $this->assertTrue($this->manager->validate($pub));
    }

    // ✅ CAS VALIDE — avec YouTube
    public function testValidPublicationAvecYoutube(): void
    {
        $pub = new Publication();
        $pub->setDescription('Vidéo intéressante');
        $pub->setId(1);
        $pub->setVideo('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertTrue($this->manager->validate($pub));
    }

    // ❌ Description dépasse 200 caractères
    public function testPublicationDescriptionTropLongue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('200 caractères');

        $pub = new Publication();
        $pub->setDescription(str_repeat('A', 201));
        $pub->setId(1);

        $this->manager->validate($pub);
    }

    // ❌ Description exactement 200 caractères — doit passer
    public function testPublicationDescription200CaracteresExact(): void
    {
        $pub = new Publication();
        $pub->setDescription(str_repeat('A', 200));
        $pub->setId(1);

        $this->assertTrue($this->manager->validate($pub));
    }

    // ❌ Id utilisateur null
    public function testPublicationIdNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('identifiant utilisateur');

        $pub = new Publication();
        $pub->setDescription('Test');
        // id non défini → null par défaut

        $this->manager->validate($pub);
    }

    // ❌ Vues négatives
    public function testPublicationVuesNegatives(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('vues ne peut pas être négatif');

        $pub = new Publication();
        $pub->setId(1);
        $pub->setVues(-5);

        $this->manager->validate($pub);
    }

    // ❌ URL YouTube invalide (sans ID valide)
    public function testPublicationYoutubeUrlInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL YouTube invalide');

        $pub = new Publication();
        $pub->setId(1);
        $pub->setVideo('https://www.youtube.com/watch?v=COURT'); // ID < 11 chars

        $this->manager->validate($pub);
    }

    // ✅ Description null — doit passer (champ nullable)
    public function testPublicationDescriptionNull(): void
    {
        $pub = new Publication();
        $pub->setDescription(null);
        $pub->setId(1);

        $this->assertTrue($this->manager->validate($pub));
    }

    // ✅ Vues = 0 — doit passer
    public function testPublicationVuesZero(): void
    {
        $pub = new Publication();
        $pub->setId(1);
        $pub->setVues(0);

        $this->assertTrue($this->manager->validate($pub));
    }
}