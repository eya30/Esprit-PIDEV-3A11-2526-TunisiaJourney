<?php

namespace App\Tests\Entity;

use App\Entity\Produit;
use PHPUnit\Framework\TestCase;

class ProduitTest extends TestCase
{
    private function makeProduit(int $stock): Produit
    {
        $p = new Produit();
        $p->setStock($stock);
        $p->setDisponibilite($stock > 0);

        return $p;
    }

    public function testSetAndGetTitre(): void
    {
        $p = new Produit();
        $p->setTitre('Poterie artisanale');

        $this->assertSame('Poterie artisanale', $p->getTitre());
    }

    public function testSetAndGetPrix(): void
    {
        $p = new Produit();
        $p->setPrix(29.99);

        $this->assertSame(29.99, $p->getPrix());
    }

    public function testSetAndGetStock(): void
    {
        $p = new Produit();
        $p->setStock(42);

        $this->assertSame(42, $p->getStock());
    }

    public function testIsStockFaibleWhenBelowSeuil(): void
    {
        $p = $this->makeProduit(3);

        $this->assertTrue($p->isStockFaible());
    }

    public function testIsEnRuptureWhenStockZero(): void
    {
        $p = $this->makeProduit(0);

        $this->assertTrue($p->isEnRupture());
    }

    public function testNeedsReapproWhenBelowSeuil(): void
    {
        $p = $this->makeProduit(5);

        $this->assertTrue($p->needsReappro());
    }

    public function testDecrementStockSuccess(): void
    {
        $p = $this->makeProduit(10);

        $result = $p->decrementStock(4);

        $this->assertTrue($result);
        $this->assertSame(6, $p->getStock());
    }

    public function testIsCommandableWhenAvailableAndSufficientStock(): void
    {
        $p = $this->makeProduit(10);

        $this->assertTrue($p->isCommandable(3));
    }
}