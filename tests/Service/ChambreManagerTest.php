<?php

namespace App\Tests\Service;

use App\Entity\Chambre;
use App\Entity\Hotel;
use App\Service\ChambreManager;
use PHPUnit\Framework\TestCase;

class ChambreManagerTest extends TestCase
{
    private ChambreManager $manager;
    private Hotel $hotel;

    protected function setUp(): void
    {
        $this->manager = new ChambreManager();
        $this->hotel = new Hotel();
        $this->hotel->setNom('Hotel Test');
        $this->hotel->setVille('Paris');
        $this->hotel->setEtoiles(4);
        $this->hotel->setDescription('Une description suffisamment longue pour passer la validation.');
        $this->hotel->setIdUtilisateur(1);
    }

    // ✅ Chambre valide complète
    public function testChambreValide(): void
    {
        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('double');
        $chambre->setPrixNuit(150.0);
        $chambre->setStatus('disponible');
        $chambre->setCapaciteMax(2);
        $chambre->setDescription('Une belle chambre double avec vue sur la mer et tout le confort moderne.');
        $chambre->setModele3DURL('https://example.com/modele3d');
        $chambre->setHotel($this->hotel);

        $this->assertTrue($this->manager->validate($chambre));
    }

    // ✅ Chambre valide minimale (champs optionnels null)
    public function testChambreValideMinimale(): void
    {
        $chambre = new Chambre();
        $chambre->setNum(202);
        $chambre->setType('simple');
        $chambre->setPrixNuit(50.0);
        $chambre->setCapaciteMax(1);
        $chambre->setHotel($this->hotel);

        $this->assertTrue($this->manager->validate($chambre));
    }

    // ============ CHAMP : num ============

    // ❌ Numéro négatif
    public function testNumeroNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de chambre est obligatoire et doit être positif.');

        $chambre = new Chambre();
        $chambre->setNum(-5);
        $chambre->setType('simple');
        $chambre->setPrixNuit(100.0);
        $chambre->setCapaciteMax(2);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ============ CHAMP : type ============

    // ❌ Type valeur non autorisée
    public function testTypeInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de chambre doit être : simple, double, triple, suite, presidentielle ou familiale.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('luxe');
        $chambre->setPrixNuit(100.0);
        $chambre->setCapaciteMax(2);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ❌ Type trop court (hors liste)
    public function testTypeTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de chambre doit être : simple, double, triple, suite, presidentielle ou familiale.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('x');
        $chambre->setPrixNuit(100.0);
        $chambre->setCapaciteMax(2);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ============ CHAMP : prix_nuit ============

    // ❌ Prix négatif
    public function testPrixNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix par nuit est obligatoire et doit être positif.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('simple');
        $chambre->setPrixNuit(-50.0);
        $chambre->setCapaciteMax(2);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ❌ Prix trop bas (< 10)
    public function testPrixTropBas(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix par nuit doit être compris entre 10 et 2000 €.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('simple');
        $chambre->setPrixNuit(5.0);
        $chambre->setCapaciteMax(2);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ❌ Prix trop élevé (> 2000)
    public function testPrixTropEleve(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix par nuit doit être compris entre 10 et 2000 €.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('presidentielle');
        $chambre->setPrixNuit(9999.0);
        $chambre->setCapaciteMax(4);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ============ CHAMP : status ============

    // ❌ Statut valeur non autorisée
    public function testStatusInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le status doit être : disponible, indisponible ou maintenance.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('double');
        $chambre->setPrixNuit(100.0);
        $chambre->setStatus('occupee');
        $chambre->setCapaciteMax(2);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ✅ Statut null accepté (nullable)
    public function testStatusNullAccepte(): void
    {
        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('double');
        $chambre->setPrixNuit(100.0);
        $chambre->setStatus(null);
        $chambre->setCapaciteMax(2);
        $chambre->setHotel($this->hotel);

        $this->assertTrue($this->manager->validate($chambre));
    }

    // ============ CHAMP : capacite_max ============

    // ❌ Capacité = 0 (hors plage basse)
    public function testCapaciteMaxZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité maximale doit être comprise entre 1 et 10 personnes.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('simple');
        $chambre->setPrixNuit(100.0);
        $chambre->setCapaciteMax(0);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ❌ Capacité > 10 (hors plage haute)
    public function testCapaciteMaxTropGrande(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité maximale doit être comprise entre 1 et 10 personnes.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('familiale');
        $chambre->setPrixNuit(200.0);
        $chambre->setCapaciteMax(15);
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ============ CHAMP : description ============

    // ❌ Description trop courte (< 10)
    public function testDescriptionTropCourte(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('simple');
        $chambre->setPrixNuit(100.0);
        $chambre->setCapaciteMax(1);
        $chambre->setDescription('Court');
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ❌ Description trop longue (> 2000)
    public function testDescriptionTropLongue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description ne peut pas dépasser 2000 caractères.');

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('suite');
        $chambre->setPrixNuit(400.0);
        $chambre->setCapaciteMax(3);
        $chambre->setDescription(str_repeat('a', 2001));
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ✅ Description null acceptée (nullable)
    public function testDescriptionNullAcceptee(): void
    {
        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('simple');
        $chambre->setPrixNuit(80.0);
        $chambre->setCapaciteMax(1);
        $chambre->setDescription(null);
        $chambre->setHotel($this->hotel);

        $this->assertTrue($this->manager->validate($chambre));
    }

    // ============ CHAMP : modele3D_URL ============

    // ❌ URL invalide
    public function testModele3DUrlInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'URL du modèle 3D doit être une URL valide.");

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('suite');
        $chambre->setPrixNuit(500.0);
        $chambre->setCapaciteMax(3);
        $chambre->setModele3DURL('pas_une_url');
        $chambre->setHotel($this->hotel);

        $this->manager->validate($chambre);
    }

    // ✅ URL null acceptée (nullable)
    public function testModele3DUrlNullAcceptee(): void
    {
        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('double');
        $chambre->setPrixNuit(120.0);
        $chambre->setCapaciteMax(2);
        $chambre->setModele3DURL(null);
        $chambre->setHotel($this->hotel);

        $this->assertTrue($this->manager->validate($chambre));
    }

    // ============ CHAMP : hotel (relation) ============

    // ❌ Hotel null (obligatoire)
    public function testHotelNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'hôtel associé est obligatoire.");

        $chambre = new Chambre();
        $chambre->setNum(101);
        $chambre->setType('double');
        $chambre->setPrixNuit(100.0);
        $chambre->setCapaciteMax(2);
        $chambre->setHotel(null);

        $this->manager->validate($chambre);
    }
}
