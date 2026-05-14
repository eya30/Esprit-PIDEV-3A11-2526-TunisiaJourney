<?php

namespace App\Tests\Entity;

use App\Entity\Commande;
use App\Entity\CommandeProduit;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    // 1️⃣ Valeurs par défaut
    public function testDefaultValues(): void
    {
        $c = new Commande();

        $this->assertSame('En attente', $c->getStatut());
        $this->assertInstanceOf(\DateTime::class, $c->getDateC());
        $this->assertSame(1, $c->getQuantite());
    }

    // 2️⃣ Quantité
    public function testSetAndGetQuantite(): void
    {
        $c = new Commande();
        $c->setQuantite(3);

        $this->assertSame(3, $c->getQuantite());
    }

    // 3️⃣ Total
    public function testSetAndGetTotal(): void
    {
        $c = new Commande();
        $c->setTotal(99.9);

        $this->assertSame(99.9, $c->getTotal());
    }

    // 4️⃣ Statut
    public function testSetAndGetStatut(): void
    {
        $c = new Commande();
        $c->setStatut('Payée');

        $this->assertSame('Payée', $c->getStatut());
    }

    // 5️⃣ Adresse
    public function testSetAndGetAdresse(): void
    {
        $c = new Commande();
        $c->setAdresseLiv('Tunis');

        $this->assertSame('Tunis', $c->getAdresseLiv());
    }

    // 6️⃣ Code postal
    public function testSetAndGetCodePostal(): void
    {
        $c = new Commande();
        $c->setCodePostal('1000');

        $this->assertSame('1000', $c->getCodePostal());
    }

    // 7️⃣ Mode paiement
    public function testSetAndGetModePaiement(): void
    {
        $c = new Commande();
        $c->setModePaiement('Carte');

        $this->assertSame('Carte', $c->getModePaiement());
    }

    // 8️⃣ Ajout ligne
    public function testAddLigne(): void
    {
        $c = new Commande();

        $ligne = $this->createMock(CommandeProduit::class);

        $ligne->expects($this->once())
              ->method('setCommande')
              ->with($c);

        $c->addLigne($ligne);

        $this->assertCount(1, $c->getLignes());
    }
}