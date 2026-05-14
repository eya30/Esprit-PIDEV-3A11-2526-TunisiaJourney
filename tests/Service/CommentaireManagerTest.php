<?php

namespace App\Tests\Service;

use App\Entity\Commentaire;
use App\Service\CommentaireManager;
use PHPUnit\Framework\TestCase;

class CommentaireManagerTest extends TestCase
{
    private CommentaireManager $manager;

    protected function setUp(): void
    {
        $this->manager = new CommentaireManager();
    }

    // ✅ CAS VALIDE — minimal
    public function testValidCommentaireMinimal(): void
    {
        $c = new Commentaire();
        $c->setDescription('Super publication, merci !');

        $this->assertTrue($this->manager->validate($c));
    }

    // ✅ CAS VALIDE — complet avec image et IP
    public function testValidCommentaireComplet(): void
    {
        $c = new Commentaire();
        $c->setDescription('Très intéressant comme sujet.');
        $c->setImage('photo.jpg');
        $c->setIpAddress('192.168.1.1');
        $c->setUserAgent('Mozilla/5.0');
        $c->setTags('tech,forum');

        $this->assertTrue($this->manager->validate($c));
    }

    // ❌ Description vide
    public function testCommentaireDescriptionVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('description du commentaire est obligatoire');

        $c = new Commentaire();
        $c->setDescription('');

        $this->manager->validate($c);
    }

    // ❌ Description trop longue (> 5000 caractères)
    public function testCommentaireDescriptionTropLongue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('5000 caractères');

        $c = new Commentaire();
        $c->setDescription(str_repeat('x', 5001));

        $this->manager->validate($c);
    }

    // ❌ Extension image invalide
    public function testCommentaireImageExtensionInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('jpg, jpeg, png, gif ou webp');

        $c = new Commentaire();
        $c->setDescription('Commentaire test');
        $c->setImage('script.php');

        $this->manager->validate($c);
    }

    // ✅ Extensions image valides
    public function testCommentaireImageExtensionsValides(): void
    {
        foreach (['photo.jpg', 'image.jpeg', 'avatar.png', 'anim.gif', 'pic.webp'] as $img) {
            $c = new Commentaire();
            $c->setDescription('Test image ' . $img);
            $c->setImage($img);
            $this->assertTrue($this->manager->validate($c), "Extension valide attendue pour : $img");
        }
    }

    // ❌ Adresse IP invalide
    public function testCommentaireIpAddressInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'adresse IP est invalide");

        $c = new Commentaire();
        $c->setDescription('Commentaire');
        $c->setIpAddress('999.999.999.999');

        $this->manager->validate($c);
    }

    // ✅ IP valide IPv6
    public function testCommentaireIpV6Valide(): void
    {
        $c = new Commentaire();
        $c->setDescription('Commentaire');
        $c->setIpAddress('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        $this->assertTrue($this->manager->validate($c));
    }

    // ❌ User agent trop long (> 500 caractères)
    public function testCommentaireUserAgentTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('user agent ne peut pas dépasser 500');

        $c = new Commentaire();
        $c->setDescription('Commentaire');
        $c->setUserAgent(str_repeat('A', 501));

        $this->manager->validate($c);
    }

    // ❌ cancelled_at défini sans is_cancelled = true
    public function testCommentaireCancelledAtSansIsCancelled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("date d'annulation");

        $c = new Commentaire();
        $c->setDescription('Commentaire');
        $c->setIsCancelled(false);
        $c->setCancelledAt(new \DateTime());

        $this->manager->validate($c);
    }

    // ✅ cancelled_at + is_cancelled = true — valide
    public function testCommentaireCancelledAtAvecIsCancelled(): void
    {
        $c = new Commentaire();
        $c->setDescription('Commentaire annulé');
        $c->setIsCancelled(true);
        $c->setCancelledAt(new \DateTime());

        $this->assertTrue($this->manager->validate($c));
    }

    // ✅ Image null — doit passer
    public function testCommentaireImageNull(): void
    {
        $c = new Commentaire();
        $c->setDescription('Sans image');
        $c->setImage(null);

        $this->assertTrue($this->manager->validate($c));
    }
}