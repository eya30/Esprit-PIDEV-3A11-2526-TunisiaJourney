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

    // ── Getters / Setters ─────────────────────────────────────────────────────

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

    public function testSetAndGetDisponibilite(): void
    {
        $p = new Produit();
        $p->setDisponibilite(true);
        $this->assertTrue($p->isDisponibilite());

        $p->setDisponibilite(false);
        $this->assertFalse($p->isDisponibilite());
    }

    public function testSetAndGetCategorie(): void
    {
        $p = new Produit();
        $p->setCategorie('Bijoux');
        $this->assertSame('Bijoux', $p->getCategorie());
    }

    public function testDefaultDisponibiliteIsTrue(): void
    {
        $p = new Produit();
        $this->assertTrue($p->isDisponibilite());
    }

    // ── isStockFaible ─────────────────────────────────────────────────────────

    public function testIsStockFaibleWhenBelowSeuil(): void
    {
        $p = $this->makeProduit(3); // <= SEUIL_STOCK_FAIBLE (5) and > 0
        $this->assertTrue($p->isStockFaible());
    }

    public function testIsStockFaibleAtSeuil(): void
    {
        $p = $this->makeProduit(Produit::SEUIL_STOCK_FAIBLE);
        $this->assertTrue($p->isStockFaible());
    }

    public function testIsStockFaibleWhenAboveSeuil(): void
    {
        $p = $this->makeProduit(20);
        $this->assertFalse($p->isStockFaible());
    }

    public function testIsStockFaibleReturnsFalseWhenZero(): void
    {
        $p = $this->makeProduit(0); // rupture, pas "faible"
        $this->assertFalse($p->isStockFaible());
    }

    // ── isEnRupture ───────────────────────────────────────────────────────────

    public function testIsEnRuptureWhenStockZero(): void
    {
        $p = $this->makeProduit(0);
        $this->assertTrue($p->isEnRupture());
    }

    public function testIsEnRuptureWhenStockNull(): void
    {
        $p = new Produit();
        $p->setStock(null);
        $this->assertTrue($p->isEnRupture());
    }

    public function testIsEnRuptureWhenStockPositive(): void
    {
        $p = $this->makeProduit(1);
        $this->assertFalse($p->isEnRupture());
    }

    // ── needsReappro ──────────────────────────────────────────────────────────

    public function testNeedsReapproWhenBelowSeuil(): void
    {
        $p = $this->makeProduit(5);
        $this->assertTrue($p->needsReappro());
    }

    public function testNeedsReapproAtSeuil(): void
    {
        $p = $this->makeProduit(Produit::SEUIL_REAPPRO);
        $this->assertTrue($p->needsReappro());
    }

    public function testNeedsReapproWhenAboveSeuil(): void
    {
        $p = $this->makeProduit(Produit::SEUIL_REAPPRO + 1);
        $this->assertFalse($p->needsReappro());
    }

    // ── decrementStock ────────────────────────────────────────────────────────

    public function testDecrementStockSuccess(): void
    {
        $p = $this->makeProduit(10);
        $result = $p->decrementStock(4);

        $this->assertTrue($result);
        $this->assertSame(6, $p->getStock());
        $this->assertTrue($p->isDisponibilite());
    }

    public function testDecrementStockExactlyToZero(): void
    {
        $p = $this->makeProduit(5);
        $result = $p->decrementStock(5);

        $this->assertTrue($result);
        $this->assertSame(0, $p->getStock());
        $this->assertFalse($p->isDisponibilite()); // auto-désactivé
    }

    public function testDecrementStockFailsWhenInsufficient(): void
    {
        $p = $this->makeProduit(3);
        $result = $p->decrementStock(10);

        $this->assertFalse($result);
        $this->assertSame(3, $p->getStock()); // stock inchangé
    }

    public function testDecrementStockFailsWhenZero(): void
    {
        $p = $this->makeProduit(0);
        $result = $p->decrementStock(1);

        $this->assertFalse($result);
    }

    public function testDecrementStockDisablesDisponibiliteAtZero(): void
    {
        $p = $this->makeProduit(1);
        $p->decrementStock(1);

        $this->assertSame(0, $p->getStock());
        $this->assertFalse($p->isDisponibilite());
    }

    // ── isCommandable ─────────────────────────────────────────────────────────

    public function testIsCommandableWhenAvailableAndSufficientStock(): void
    {
        $p = $this->makeProduit(10);
        $this->assertTrue($p->isCommandable(3));
    }

    public function testIsCommandableFailsWhenStockInsufficient(): void
    {
        $p = $this->makeProduit(2);
        $this->assertFalse($p->isCommandable(5));
    }

    public function testIsCommandableFailsWhenNotDisponible(): void
    {
        $p = $this->makeProduit(10);
        $p->setDisponibilite(false);
        $this->assertFalse($p->isCommandable(1));
    }

    public function testIsCommandableDefaultQuantiteIsOne(): void
    {
        $p = $this->makeProduit(1);
        $this->assertTrue($p->isCommandable());
    }

    public function testIsCommandableFailsWhenStockZero(): void
    {
        $p = $this->makeProduit(0);
        $this->assertFalse($p->isCommandable());
    }
}