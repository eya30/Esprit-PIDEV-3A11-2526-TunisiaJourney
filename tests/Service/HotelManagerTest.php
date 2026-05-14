<?php

namespace App\Tests\Service;

use App\Entity\Hotel;
use App\Service\HotelManager;
use PHPUnit\Framework\TestCase;

class HotelManagerTest extends TestCase
{
    private HotelManager $manager;

    protected function setUp(): void
    {
        $this->manager = new HotelManager();
    }

    // ✅ Hôtel valide complet
    public function testHotelValide(): void
    {
        $hotel = new Hotel();
        $hotel->setNom("Grand Hotel de Paris");
        $hotel->setVille("Paris");
        $hotel->setAdresse("12 rue de la Paix, 75001 Paris");
        $hotel->setEtoiles(5);
        $hotel->setDescription("Un etablissement de luxe au coeur de Paris, offrant des chambres raffinées et un service irreprochable.");
        $hotel->setPromotion(15.0);
        $hotel->setStatus('disponible');
        $hotel->setIdUtilisateur(1);

        $this->assertTrue($this->manager->validate($hotel));
    }

    // ✅ Hôtel valide minimal (champs optionnels null)
    public function testHotelValideMinimal(): void
    {
        $hotel = new Hotel();
        $hotel->setNom("Hotel Test");
        $hotel->setVille("Lyon");
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(5);

        $this->assertTrue($this->manager->validate($hotel));
    }

    // ============ CHAMP : nom ============

    // ❌ Nom vide
    public function testNomVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom de l'hôtel est obligatoire.");

        $hotel = new Hotel();
        $hotel->setNom('');
        $hotel->setVille('Paris');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Nom trop court (< 2 caractères)
    public function testNomTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit contenir entre 2 et 120 caractères.");

        $hotel = new Hotel();
        $hotel->setNom('H');
        $hotel->setVille('Paris');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Nom avec caractères invalides (regex)
    public function testNomAvecCaracteresInvalides(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel@123!');
        $hotel->setVille('Paris');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Nom trop long (> 120 caractères)
    public function testNomTropLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom doit contenir entre 2 et 120 caractères.");

        $hotel = new Hotel();
        $hotel->setNom(str_repeat('A', 121));
        $hotel->setVille('Paris');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ============ CHAMP : ville ============

    // ❌ Ville vide
    public function testVilleVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La ville est obligatoire.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Valide');
        $hotel->setVille('');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Ville trop courte (< 2 caractères)
    public function testVilleTropCourte(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La ville doit contenir entre 2 et 120 caractères.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('P');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Ville avec caractères invalides (regex)
    public function testVilleAvecCaracteresInvalides(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La ville ne doit contenir que des lettres, espaces, apostrophes ou tirets.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Paris123!');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ============ CHAMP : adresse ============

    // ❌ Adresse trop longue (> 255 caractères)
    public function testAdresseTropLongue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'adresse ne peut pas dépasser 255 caractères.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Paris');
        $hotel->setAdresse(str_repeat('a', 256));
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ✅ Adresse null acceptée (nullable)
    public function testAdresseNullAcceptee(): void
    {
        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Paris');
        $hotel->setAdresse(null);
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->assertTrue($this->manager->validate($hotel));
    }

    // ============ CHAMP : etoiles ============

    // ❌ Étoiles null (obligatoire)
    public function testEtoilesNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nombre d'étoiles est obligatoire.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Paris');
        $hotel->setEtoiles(null);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Étoiles = 0 (hors plage basse)
    public function testEtoilesZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Les étoiles doivent être comprises entre 1 et 5.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Paris');
        $hotel->setEtoiles(0);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Étoiles > 5 (hors plage haute)
    public function testEtoilesHorsPlage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Les étoiles doivent être comprises entre 1 et 5.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Marseille');
        $hotel->setEtoiles(6);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ============ CHAMP : description ============

    // ❌ Description vide
    public function testDescriptionVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La description est obligatoire.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Nice');
        $hotel->setEtoiles(4);
        $hotel->setDescription('');
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Description trop courte (< 10 caractères)
    public function testDescriptionTropCourte(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La description doit contenir au moins 10 caractères.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Nice');
        $hotel->setEtoiles(4);
        $hotel->setDescription('Court');
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Description trop longue (> 5000 caractères)
    public function testDescriptionTropLongue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La description ne peut pas dépasser 5000 caractères.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Nice');
        $hotel->setEtoiles(4);
        $hotel->setDescription(str_repeat('a', 5001));
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ============ CHAMP : promotion ============

    // ❌ Promotion négative (< 0)
    public function testPromotionNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La promotion ne peut pas être négative.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Promo');
        $hotel->setVille('Bordeaux');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setPromotion(-5.0);
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ❌ Promotion > 100
    public function testPromotionHorsPlage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La promotion doit être comprise entre 0 et 100%.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Promo');
        $hotel->setVille('Bordeaux');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setPromotion(150.0);
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ✅ Promotion null acceptée (nullable)
    public function testPromotionNullAcceptee(): void
    {
        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Lyon');
        $hotel->setEtoiles(2);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setPromotion(null);
        $hotel->setIdUtilisateur(1);

        $this->assertTrue($this->manager->validate($hotel));
    }

    // ============ CHAMP : status ============

    // ❌ Statut invalide
    public function testStatutInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le status doit être : disponible, indisponible ou maintenance.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Toulouse');
        $hotel->setEtoiles(2);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setStatus('ferme');
        $hotel->setIdUtilisateur(1);

        $this->manager->validate($hotel);
    }

    // ✅ Statut null accepté (nullable)
    public function testStatutNullAccepte(): void
    {
        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Lyon');
        $hotel->setEtoiles(3);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setStatus(null);
        $hotel->setIdUtilisateur(1);

        $this->assertTrue($this->manager->validate($hotel));
    }

    // ============ CHAMP : idUtilisateur ============

    // ❌ ID utilisateur négatif
    public function testIdUtilisateurNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'ID utilisateur est obligatoire et doit être positif.");

        $hotel = new Hotel();
        $hotel->setNom('Hotel Test');
        $hotel->setVille('Strasbourg');
        $hotel->setEtoiles(4);
        $hotel->setDescription("Une description suffisamment longue pour etre valide.");
        $hotel->setIdUtilisateur(-1);

        $this->manager->validate($hotel);
    }
}
