<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\PasswordResetToken;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    // ── Helper : valide un utilisateur et lance une exception si invalide ──
    private function validateUser(User $user): bool
    {
        if (!$user->getNom() || trim($user->getNom()) === '') {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        if (!$user->getEmail() || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $user->getEmail())) {
            throw new \InvalidArgumentException('Email invalide');
        }

        $pwd = $user->getMotDePasse() ?? '';
        if (strlen($pwd) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
        }
        if (!preg_match('/[0-9]/', $pwd) || !preg_match('/[a-zA-Z]/', $pwd)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir des lettres ET des chiffres');
        }

        $dateNaissance = $user->getDateNaissance();
        if ($dateNaissance !== null) {
            $age = (new \DateTime())->diff($dateNaissance)->y;
            if ($age < 18) {
                throw new \InvalidArgumentException('Vous devez avoir au moins 18 ans');
            }
        }

        $tel = $user->getTelephone();
        if ($tel !== null && !preg_match('/^\d{8,15}$/', $tel)) {
            throw new \InvalidArgumentException('Le téléphone doit contenir entre 8 et 15 chiffres');
        }

        return true;
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 1 — Utilisateur valide : toutes les règles passent
    // ══════════════════════════════════════════════════════════════
    public function testUtilisateurValide(): void
    {
        $user = new User();
        $user->setNom('Bennour');
        $user->setPrenom('Meriem');
        $user->setEmail('meriem@esprit.tn');
        $user->setMotDePasse('Meriem123');
        $user->setRole('MEMBRE');
        $user->setStatut(User::STATUT_ACTIF);
        $user->setTelephone('12345678');
        $user->setDateNaissance(new \DateTime('2000-01-01'));
        $user->setDateInscription(new \DateTime());

        $this->assertTrue($this->validateUser($user));
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 2 — Nom vide → exception
    // ══════════════════════════════════════════════════════════════
    public function testNomVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $user = new User();
        // Nom non défini
        $user->setPrenom('Meriem');
        $user->setEmail('meriem@esprit.tn');
        $user->setMotDePasse('Meriem123');
        $user->setTelephone('12345678');
        $user->setDateNaissance(new \DateTime('2000-01-01'));

        $this->validateUser($user);
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 3 — Email invalide → exception
    // ══════════════════════════════════════════════════════════════
    public function testEmailInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');

        $user = new User();
        $user->setNom('Bennour');
        $user->setPrenom('Meriem');
        $user->setEmail('meriem-esprit'); // Email sans @
        $user->setMotDePasse('Meriem123');
        $user->setTelephone('12345678');
        $user->setDateNaissance(new \DateTime('2000-01-01'));

        $this->validateUser($user);
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 4 — Mot de passe trop court → exception
    // ══════════════════════════════════════════════════════════════
    public function testMotDePasseTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères');

        $user = new User();
        $user->setNom('Bennour');
        $user->setPrenom('Meriem');
        $user->setEmail('meriem@esprit.tn');
        $user->setMotDePasse('Mer1'); // Seulement 4 caractères
        $user->setTelephone('12345678');
        $user->setDateNaissance(new \DateTime('2000-01-01'));

        $this->validateUser($user);
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 5 — Mot de passe sans chiffres → exception
    // ══════════════════════════════════════════════════════════════
    public function testMotDePasseSansChiffres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir des lettres ET des chiffres');

        $user = new User();
        $user->setNom('Bennour');
        $user->setPrenom('Meriem');
        $user->setEmail('meriem@esprit.tn');
        $user->setMotDePasse('PasswordOnly'); // Pas de chiffres
        $user->setTelephone('12345678');
        $user->setDateNaissance(new \DateTime('2000-01-01'));

        $this->validateUser($user);
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 6 — Date de naissance : moins de 18 ans → exception
    // ══════════════════════════════════════════════════════════════
    public function testDateNaissanceMoinsDe18Ans(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vous devez avoir au moins 18 ans');

        $user = new User();
        $user->setNom('Bennour');
        $user->setPrenom('Meriem');
        $user->setEmail('meriem@esprit.tn');
        $user->setMotDePasse('Meriem123');
        $user->setTelephone('12345678');
        $user->setDateNaissance(new \DateTime('-10 years')); // Seulement 10 ans

        $this->validateUser($user);
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 7 — Téléphone invalide → exception
    // ══════════════════════════════════════════════════════════════
    public function testTelephoneInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le téléphone doit contenir entre 8 et 15 chiffres');

        $user = new User();
        $user->setNom('Bennour');
        $user->setPrenom('Meriem');
        $user->setEmail('meriem@esprit.tn');
        $user->setMotDePasse('Meriem123');
        $user->setTelephone('abc123'); // Contient des lettres
        $user->setDateNaissance(new \DateTime('2000-01-01'));

        $this->validateUser($user);
    }

    // ══════════════════════════════════════════════════════════════
    // TEST 8 — Update du profil
    // Vérifie que les modifications du profil sont bien appliquées
    // ══════════════════════════════════════════════════════════════
    public function testUpdateProfil(): void
    {
        $user = new User();
        $user->setNom('Bennour');
        $user->setPrenom('Meriem');
        $user->setEmail('meriem@esprit.tn');
        $user->setMotDePasse('Meriem123');
        $user->setTelephone('12345678');
        $user->setAdresse('Tunis, Tunisie');
        $user->setDateNaissance(new \DateTime('2000-01-01'));
        $user->setProfileImageUrl('https://imgbb.com/photo.jpg');

        // Valider avant update
        $this->assertTrue($this->validateUser($user));

        // Update du profil
        $user->setNom('Smith');
        $user->setPrenom('John');
        $user->setEmail('john.smith@esprit.tn');
        $user->setTelephone('98765432');
        $user->setAdresse('Sfax, Tunisie');
        $user->setDateNaissance(new \DateTime('1995-06-15'));
        $user->setProfileImageUrl('https://imgbb.com/new-photo.jpg');

        // Valider après update
        $this->assertTrue($this->validateUser($user));

        // Vérification des nouvelles valeurs
        $this->assertEquals('Smith', $user->getNom());
        $this->assertEquals('John', $user->getPrenom());
        $this->assertEquals('john.smith@esprit.tn', $user->getEmail());
        $this->assertEquals('98765432', $user->getTelephone());
        $this->assertEquals('Sfax, Tunisie', $user->getAdresse());
        $this->assertEquals('1995-06-15', $user->getDateNaissance()->format('Y-m-d'));
        $this->assertEquals('https://imgbb.com/new-photo.jpg', $user->getProfileImageUrl());
    }
}