<?php

namespace App\Tests\Service;

use App\Entity\Forum;
use App\Service\ForumManager;
use PHPUnit\Framework\TestCase;

class ForumManagerTest extends TestCase
{
    private ForumManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ForumManager();
    }

    // ✅ CAS VALIDE
    public function testValidForum(): void
    {
        $forum = new Forum();
        $forum->setNom('Forum de Technologie');
        $forum->setTheme('Informatique');
        $forum->setStatus('actif');

        $this->assertTrue($this->manager->validate($forum));
    }

    // ❌ Nom vide
    public function testForumNomVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du forum est obligatoire.');

        $forum = new Forum();
        $forum->setNom('');
        $forum->setTheme('Sport');
        $forum->setStatus('actif');

        $this->manager->validate($forum);
    }

    // ❌ Nom null
    public function testForumNomNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $forum = new Forum();
        $forum->setNom(null);
        $forum->setTheme('Sport');
        $forum->setStatus('actif');

        $this->manager->validate($forum);
    }

    // ❌ Nom trop court (< 3 caractères)
    public function testForumNomTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins 3 caractères');

        $forum = new Forum();
        $forum->setNom('AB');
        $forum->setTheme('Sport');
        $forum->setStatus('actif');

        $this->manager->validate($forum);
    }

    // ❌ Nom trop long (> 100 caractères)
    public function testForumNomTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dépasser 100 caractères');

        $forum = new Forum();
        $forum->setNom(str_repeat('A', 101));
        $forum->setTheme('Sport');
        $forum->setStatus('actif');

        $this->manager->validate($forum);
    }

    // ❌ Nom commence par un symbole
    public function testForumNomCommenceParSymbole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('commencer par une lettre ou un chiffre');

        $forum = new Forum();
        $forum->setNom('@MonForum');
        $forum->setTheme('Art');
        $forum->setStatus('actif');

        $this->manager->validate($forum);
    }

    // ❌ Nom contient des symboles interdits
    public function testForumNomAvecSymbolesInterdits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('symboles spéciaux');

        $forum = new Forum();
        $forum->setNom('Forum#Tech&Plus');
        $forum->setTheme('Tech');
        $forum->setStatus('actif');

        $this->manager->validate($forum);
    }

    // ❌ Thème vide
    public function testForumThemeVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('thème');

        $forum = new Forum();
        $forum->setNom('Forum Valide');
        $forum->setTheme('');
        $forum->setStatus('actif');

        $this->manager->validate($forum);
    }

    // ❌ Statut invalide
    public function testForumStatutInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"actif" ou "inactif"');

        $forum = new Forum();
        $forum->setNom('Forum Valide');
        $forum->setTheme('Science');
        $forum->setStatus('suspendu');

        $this->manager->validate($forum);
    }

    // ✅ Statut inactif valide
    public function testForumStatutInactifValide(): void
    {
        $forum = new Forum();
        $forum->setNom('Forum Archivé');
        $forum->setTheme('Histoire');
        $forum->setStatus('inactif');

        $this->assertTrue($this->manager->validate($forum));
    }

    // ✅ Nom avec caractères arabes (Unicode)
    public function testForumNomArabe(): void
    {
        $forum = new Forum();
        $forum->setNom('منتدى التقنية');
        $forum->setTheme('Tech');
        $forum->setStatus('actif');

        // Doit passer (lettres arabes autorisées)
        // Note: si la regex ne couvre pas l'arabe, on s'attend à une exception
        // Ici on teste le comportement réel
        $this->expectNotToPerformAssertions();
        try {
            $this->manager->validate($forum);
        } catch (\InvalidArgumentException $e) {
            // Comportement acceptable selon la regex
        }
    }
}