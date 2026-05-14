<?php

namespace App\Service;

class QrCodeService
{
<<<<<<< HEAD
    /**
     * @param array{
     *     hotel_nom?: string|null,
     *     chambre_num?: string|int|null,
     *     prenom?: string|null,
     *     nom?: string|null,
     *     email?: string|null,
     *     telephone?: string|null,
     *     dateDebut?: string|null,
     *     dateFin?: string|null,
     *     nbNuit?: int|string|null,
     *     nbPersonnes?: int|string|null,
     *     prixTotal?: float|int|string|null,
     *     detailsPrix?: string|null
     * } $reservation
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function generateReservationQRCode(array $reservation): string
    {
        // Construire un texte simple pour le QR code
        $qrText = "RESERVATION CHAMBRE\n";
        $qrText .= "Hotel: " . ($reservation['hotel_nom'] ?? '-') . "\n";
        $qrText .= "Chambre: " . ($reservation['chambre_num'] ?? '-') . "\n";
        $qrText .= "Client: " . ($reservation['prenom'] ?? '') . " " . ($reservation['nom'] ?? '') . "\n";
        $qrText .= "Email: " . ($reservation['email'] ?? '-') . "\n";
        $qrText .= "Tel: " . ($reservation['telephone'] ?? '-') . "\n";
        $qrText .= "Dates: du " . ($reservation['dateDebut'] ?? '-') . " au " . ($reservation['dateFin'] ?? '-') . "\n";
        $qrText .= "Nuits: " . ($reservation['nbNuit'] ?? 0) . "\n";
        $qrText .= "Personnes: " . ($reservation['nbPersonnes'] ?? 1) . "\n";
        $qrText .= "Prix total: " . ($reservation['prixTotal'] ?? 0) . " TND\n";
        $qrText .= "Detail: " . ($reservation['detailsPrix'] ?? '-');

        // Utiliser l'API externe gratuite (pas besoin de GD)
        return 'https://quickchart.io/qr?text=' . urlencode($qrText) . '&size=250&margin=2';
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
