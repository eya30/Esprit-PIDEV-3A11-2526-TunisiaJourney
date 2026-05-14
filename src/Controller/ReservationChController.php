<?php

namespace App\Controller;

use App\Service\CurrencyChService;
use App\Service\WeatherService;
use App\Service\QrCodeService;
use App\Service\FideliteService;
use App\Service\SmsService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationChController extends AbstractController
{
    #[Route('/chambre/new/{idCh}', name: 'app_reservation_ch_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection, CurrencyChService $currency, WeatherService $weather, FideliteService $fideliteService, SmsService $smsService, ?int $idCh = null): Response
    {
        if (!$idCh) {
            $this->addFlash('error', 'ID de la chambre manquant');
            return $this->redirectToRoute('app_hotel_index');
        }

        // Récupérer la chambre
        $sqlChambre = "SELECT c.*, h.nom as hotel_nom, h.etoiles as hotel_etoiles, h.ville as hotel_ville, h.idH as hotel_id, h.promotion
                       FROM chambre c
                       JOIN hotel h ON c.idH = h.idH
                       WHERE c.idCh = ?";
        $chambre = $connection->fetchAssociative($sqlChambre, [$idCh]);

        if (!$chambre) {
            $this->addFlash('error', 'Chambre non trouvée');
            return $this->redirectToRoute('app_hotel_index');
        }

        // ========== SI REQUÊTE GET : AFFICHER LE FORMULAIRE ==========
        if ($request->isMethod('GET')) {
            $session = $request->getSession();
            $selectedCurrency = $session->get('selected_currency_ch', 'TND');
           
            $defaultCheckin  = (new \DateTime('+1 day'))->format('Y-m-d');
            $defaultCheckout = (new \DateTime('+2 days'))->format('Y-m-d');
           
            try {
                $dynamicPrice = $weather->calculateDynamicPrice(
                    $chambre['prix_nuit'],
                    $chambre['hotel_ville'],
                    $defaultCheckin,
                    $defaultCheckout
                );
            } catch (\Exception $e) {
                $dynamicPrice = null;
            }
           
            if ($dynamicPrice && isset($dynamicPrice['final_price_per_night'])) {
                $prixFinal = $dynamicPrice['final_price_per_night'];
            } else {
                $prixFinal = $chambre['prix_nuit'];
            }
           
            // === RÉDUCTION FIDÉLITÉ ===
            $user = $this->getUser();
            $reductionFidelite = 0;
            $prixOriginal = $prixFinal;
           
            if ($user && method_exists($user, 'getId')) {
                $reductionFidelite = $fideliteService->getReduction($user->getId());
                if ($reductionFidelite > 0) {
                    $prixFinal = $prixFinal * (1 - $reductionFidelite / 100);
                }
            }
            // =========================
           
            $tauxConversion = 1;
            if ($selectedCurrency !== 'TND') {
                $tauxConversion = $currency->convert(1, $selectedCurrency);
                if (!$tauxConversion) $tauxConversion = 1;
            }
           
            $prixConverti = $currency->convert($prixFinal, $selectedCurrency);
            $symbole      = $currency->getSymbol($selectedCurrency);
            $weatherData  = $weather->getWeather5Days($chambre['hotel_ville']);
           
            return $this->render('chambre/reservationch.html.twig', [
                'chambre'           => $chambre,
                'currencies'        => $currency->getAvailableCurrencies(),
                'selected_currency' => $selectedCurrency,
                'prix_converti'     => $prixConverti,
                'symbole'           => $symbole,
                'taux_conversion'   => $tauxConversion,
                'old'               => [],
                'errors'            => [],
                'weather_data'      => $weatherData,
                'dynamic_price'     => $dynamicPrice,
                'default_checkin'   => $defaultCheckin,
                'default_checkout'  => $defaultCheckout,
                'reduction_fidelite'=> $reductionFidelite,
                'prix_original'     => $prixOriginal,
            ]);
        }

        // ========== SI REQUÊTE POST : TRAITER LE FORMULAIRE ==========
        $data = $request->request->all();
       
        $nom        = trim((string) ($data['nom'] ?? ''));
        $prenom     = trim((string) ($data['prenom'] ?? ''));
        $email      = trim((string) ($data['email'] ?? ''));
        $telephone  = trim((string) ($data['telephone'] ?? ''));
        $dateDebut  = trim((string) ($data['dateDebut'] ?? ''));
        $dateFin    = trim((string) ($data['dateFin'] ?? ''));
        $nbPersonnes = $data['nbPersonnes'] ?? 1;
        $codePromo  = trim(strtoupper((string) ($data['code_promo'] ?? '')));
        $hasTelephone   = $telephone !== '';
        $dateDebutIsValid = false;
        $dateFinIsValid   = false;
        $hasValidDates    = false;

        // ID utilisateur
        $idUtilisateur = $data['idUtilisateur'] ?? null;
        if (is_string($idUtilisateur) && trim($idUtilisateur) === '') {
            $idUtilisateur = null;
        }

        $user = $this->getUser();
        if ($user && method_exists($user, 'getId')) {
            $idUtilisateur = $user->getId();
        }

        if ($idUtilisateur === null) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer une réservation.');
            return $this->redirectToRoute('app_hotel_index');
        }

        $formData = [
            'nom'        => $nom,
            'prenom'     => $prenom,
            'email'      => $email,
            'telephone'  => $telephone,
            'dateDebut'  => $dateDebut,
            'dateFin'    => $dateFin,
            'nbPersonnes'=> $nbPersonnes,
            'code_promo' => $codePromo,
        ];

        $errors = [];

        // Validations champs
        if (empty($nom)) {
            $errors['nom'] = "Le nom est requis.";
        } elseif (strlen($nom) < 2) {
            $errors['nom'] = "Le nom doit contenir au moins 2 caractères.";
        }

        if (empty($prenom)) {
            $errors['prenom'] = "Le prénom est requis.";
        } elseif (strlen($prenom) < 2) {
            $errors['prenom'] = "Le prénom doit contenir au moins 2 caractères.";
        }

        if (empty($email)) {
            $errors['email'] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'email n'est pas valide.";
        }

        if ($telephone === '') {
            $errors['telephone'] = "Le téléphone est requis.";
        } else {
            $telephoneClean = preg_replace('/[^0-9]/', '', $telephone) ?? '';
            if (strlen($telephoneClean) !== 8) {
                $errors['telephone'] = "Le téléphone doit contenir exactement 8 chiffres.";
            }
        }

        if ($dateDebut === '') {
            $errors['dateDebut'] = "La date de début est requise.";
        } else {
            $dateDebutObj = new \DateTime($dateDebut);
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            if ($dateDebutObj < $today) {
                $errors['dateDebut'] = "La date de début ne peut pas être dans le passé.";
            } else {
                $dateDebutIsValid = true;
            }
        }

        if ($dateFin === '') {
            $errors['dateFin'] = "La date de fin est requise.";
        } else {
            $dateFinIsValid = true;
        }

        if ($dateDebutIsValid && $dateFinIsValid) {
            $hasValidDates = true;
        }

        $nbNuit           = 0;
        $prixTotal        = 0;
        $promotion        = $chambre['promotion'] ?? 0;
        $prixBase         = $chambre['prix_nuit'];
        $dynamicPrice     = null;
        $prixFinalParNuit = $prixBase;
       
        if (empty($errors) && $hasValidDates) {
            $dateDebutObj = new \DateTime($dateDebut);
            $dateFinObj   = new \DateTime($dateFin);
           
            if ($dateFinObj <= $dateDebutObj) {
                $errors['dateFin'] = "La date de fin doit être postérieure à la date de début.";
            } else {
                $interval = $dateDebutObj->diff($dateFinObj);
                $nbNuit   = $interval->days;
               
                if ($nbNuit < 1) {
                    $errors['dateFin'] = "Le séjour doit durer au moins 1 nuit.";
                }
                if ($nbNuit > 90) {
                    $errors['dateFin'] = "Le séjour ne peut pas dépasser 90 nuits.";
                }
               
                try {
                    $dynamicPrice = $weather->calculateDynamicPrice(
                        $prixBase,
                        $chambre['hotel_ville'],
                        $dateDebut,
                        $dateFin
                    );
                   
                    if ($dynamicPrice && isset($dynamicPrice['final_price_per_night'])) {
                        $prixFinalParNuit = $dynamicPrice['final_price_per_night'];
                        $prixTotal        = $dynamicPrice['total_price'];
                    } else {
                        if ($promotion > 0) {
                            $prixFinalParNuit = $prixBase * (1 - $promotion / 100);
                        }
                        $prixTotal = $prixFinalParNuit * $nbNuit;
                    }
                } catch (\Exception $e) {
                    if ($promotion > 0) {
                        $prixFinalParNuit = $prixBase * (1 - $promotion / 100);
                    }
                    $prixTotal    = $prixFinalParNuit * $nbNuit;
                    $dynamicPrice = null;
                }
            }
        }

        // === RÉDUCTION FIDÉLITÉ ===
        if ($user && method_exists($user, 'getId') && $prixTotal > 0) {
            $reductionFidelite = $fideliteService->getReduction($user->getId());
            if ($reductionFidelite > 0) {
                $prixTotal = $prixTotal * (1 - $reductionFidelite / 100);
            }
        }
        // =========================

        // ========== VALIDATION & APPLICATION DU CODE PROMO ==========
        $reductionPromo = 0;
        if ($codePromo !== '' && empty($errors) && $prixTotal > 0) {
            $todayStr = (new \DateTime())->format('Y-m-d');
            $promo = $connection->fetchAssociative(
                "SELECT * FROM code_promo
                 WHERE code = ? AND statut = 'actif'
                   AND date_debut <= ? AND date_fin >= ?",
                [$codePromo, $todayStr, $todayStr]
            );

            if ($promo) {
                $reductionPromo = (float) $promo['pourcentage_reduction'];
                $prixTotal      = $prixTotal * (1 - $reductionPromo / 100);
            } else {
                $errors['code_promo'] = "Code promo invalide, expiré ou inactif.";
            }
        }
        // =============================================================

        // Validation nombre de personnes
        if (empty($nbPersonnes)) {
            $errors['nbPersonnes'] = "Le nombre de personnes est requis.";
        } elseif (!is_numeric($nbPersonnes)) {
            $errors['nbPersonnes'] = "Le nombre de personnes doit être un nombre.";
        } elseif ($nbPersonnes <= 0) {
            $errors['nbPersonnes'] = "Le nombre de personnes doit être au moins 1.";
        } elseif ($nbPersonnes > $chambre['capacite_max']) {
            $errors['nbPersonnes'] = "Le nombre de personnes ne peut pas dépasser " . $chambre['capacite_max'] . " personnes.";
        }

        // Vérification des réservations existantes
        if (empty($errors) && $hasValidDates && $nbNuit > 0) {
            $sqlCheck = "SELECT COUNT(*) as count FROM reservation_chambre
                         WHERE idCh = ? AND statut != 'annulé'
                         AND ((dateDebut <= ? AND dateFin >= ?)
                              OR (dateDebut BETWEEN ? AND ?)
                              OR (dateFin BETWEEN ? AND ?))";
           
            $existing = $connection->fetchAssociative($sqlCheck, [
                $idCh, $dateFin, $dateDebut, $dateDebut, $dateFin, $dateDebut, $dateFin
            ]);
           
            if ($existing && $existing['count'] > 0) {
                $errors['dateDebut'] = "Cette chambre est déjà réservée pour les dates sélectionnées.";
            }
        }

        if (count($errors) > 0) {
            $session = $request->getSession();
            $selectedCurrency = $session->get('selected_currency_ch', 'TND');
            $prixConverti = $currency->convert($prixBase, $selectedCurrency);
            $symbole      = $currency->getSymbol($selectedCurrency);
            $weatherData  = $weather->getWeather5Days($chambre['hotel_ville']);
           
            $tauxConversion = 1;
            if ($selectedCurrency !== 'TND') {
                $tauxConversion = $currency->convert(1, $selectedCurrency);
                if (!$tauxConversion) $tauxConversion = 1;
            }
           
            return $this->render('chambre/reservationch.html.twig', [
                'chambre'           => $chambre,
                'errors'            => $errors,
                'old'               => $formData,
                'currencies'        => $currency->getAvailableCurrencies(),
                'selected_currency' => $selectedCurrency,
                'prix_converti'     => $prixConverti,
                'symbole'           => $symbole,
                'taux_conversion'   => $tauxConversion,
                'weather_data'      => $weatherData,
                'dynamic_price'     => $dynamicPrice,
            ]);
        }

        // Construction du détail du prix
        $detailPromo = '';
        if ($reductionPromo > 0) {
            $detailPromo = sprintf(' (code promo -%d%%)', (int)$reductionPromo);
        }

        if ($dynamicPrice && isset($dynamicPrice['season_percent'])) {
            $detailsPrix = sprintf(
                '%d nuit(s) x %.2f DT (base: %.2f DT, saison: %+d%%, météo: -%d%%%s) = %.2f DT',
                $nbNuit,
                $prixFinalParNuit,
                $dynamicPrice['base_price'],
                $dynamicPrice['season_percent'],
                $dynamicPrice['weather_discount'],
                $detailPromo,
                $prixTotal
            );
            $messageFlash = '✅ Réservation confirmée ! Prix dynamique appliqué (saison + météo)';
        } else {
            if ($promotion > 0) {
                $detailsPrix = sprintf('%d nuit(s) x %.2f DT (promotion -%d%%%s) = %.2f DT', $nbNuit, $prixFinalParNuit, $promotion, $detailPromo, $prixTotal);
            } else {
                $detailsPrix = sprintf('%d nuit(s) x %.2f DT%s = %.2f DT', $nbNuit, $prixFinalParNuit, $detailPromo, $prixTotal);
            }
            $messageFlash = '✅ Réservation confirmée !';
        }
       
        $sqlInsert = "INSERT INTO reservation_chambre
                      (idCh, idUtilisateur, dateDebut, dateFin, nbNuit, prixTotal, nbPersonnes, detailsPrix, telephone, statut, nom, prenom, email)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmé', ?, ?, ?)";
       
        $connection->executeStatement($sqlInsert, [
            $idCh,
            $idUtilisateur,
            $dateDebut,
            $dateFin,
            $nbNuit,
            $prixTotal,
            $nbPersonnes,
            $detailsPrix,
            $telephone,
            $nom,
            $prenom,
            $email
        ]);

        // ========== ENVOI DU SMS ==========
        if ($hasTelephone) {
            try {
                $hotelNom            = $chambre['hotel_nom'];
                $dateDebutFormatted  = (new \DateTime($dateDebut))->format('d/m/Y');
                $dateFinFormatted    = (new \DateTime($dateFin))->format('d/m/Y');
                $nbNuitInt           = (int)$nbNuit;
               
                $smsMessage = $smsService->generateConfirmationMessage(
                    $hotelNom,
                    $dateDebutFormatted,
                    $dateFinFormatted,
                    $nbNuitInt,
                    $prixTotal
                );
               
                $smsService->sendSms($telephone, $smsMessage);
                $this->addFlash('info', '📱 Un SMS de confirmation vous a été envoyé.');
            } catch (\Exception $e) {
                $this->addFlash('warning', '⚠️ La réservation est confirmée mais le SMS n\'a pas pu être envoyé.');
            }
        }
        // =================================

        // ========== AJOUT DES POINTS DE FIDÉLITÉ ==========
        if ($user && method_exists($user, 'getId') && $prixTotal > 0) {
            try {
                $fideliteService->ajouterPoints($idUtilisateur, $prixTotal);
                $pointsGagnes = intval($prixTotal / 100);
                $this->addFlash('success', $messageFlash . ' 🎉 Vous avez gagné ' . $pointsGagnes . ' points de fidélité !');
            } catch (\Exception $e) {
                $this->addFlash('success', $messageFlash);
            }
        } else {
            $this->addFlash('success', $messageFlash);
        }

        if ($reductionPromo > 0) {
            $this->addFlash('success', sprintf('🎉 Code promo "%s" appliqué ! Réduction de %d%%.', $codePromo, (int)$reductionPromo));
        }
       
        return $this->redirectToRoute('app_chambre_show', ['idCh' => $idCh]);
    }

    // ========== API ROUTES ==========
   
    #[Route('/api/reservations/all', name: 'api_reservations_all', methods: ['GET'])]
    public function apiGetAll(Connection $connection): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return $this->json([]);
        }

        $idUtilisateur = $user->getId();

        $sqlChambres = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom
            FROM reservation_chambre r
            JOIN chambre c ON r.idCh = c.idCh
            JOIN hotel h ON c.idH = h.idH
            WHERE r.idUtilisateur = ? AND r.statut != 'annulé'
            ORDER BY r.idRes DESC";
        $chambres = $connection->fetchAllAssociative($sqlChambres, [$idUtilisateur]);

        foreach ($chambres as &$res) {
            $res['type'] = 'chambre';
        }

        return $this->json($chambres);
    }

    #[Route('/api/reservations/chambres/{id}', name: 'api_reservations_chambres_get', methods: ['GET'])]
    public function apiGetOne(Connection $connection, int $id): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $idUtilisateur = $user->getId();

        $sql         = "SELECT * FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?";
        $reservation = $connection->fetchAssociative($sql, [$id, $idUtilisateur]);
       
        if (!$reservation) {
            return $this->json(['error' => 'Non trouvé'], 404);
        }
       
        return $this->json($reservation);
    }

    #[Route('/api/reservations/chambres/{id}', name: 'api_reservations_chambres_delete', methods: ['DELETE'])]
    public function apiDelete(Connection $connection, int $id): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $idUtilisateur = $user->getId();

        $sql      = "UPDATE reservation_chambre SET statut = 'annulé' WHERE idRes = ? AND idUtilisateur = ?";
        $affected = $connection->executeStatement($sql, [$id, $idUtilisateur]);

        if ($affected > 0) {
            return $this->json(['success' => true]);
        }

        return $this->json(['error' => 'Non trouvé'], 404);
    }

    #[Route('/api/reservations/chambres/{id}', name: 'api_reservations_chambres_put', methods: ['PUT'])]
    public function apiUpdate(Connection $connection, Request $request, int $id): Response
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
       
        if (!$user || !method_exists($user, 'getId')) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $idUtilisateur = $user->getId();
       
        $checkSql = "SELECT idRes FROM reservation_chambre WHERE idRes = ? AND idUtilisateur = ?";
        $exists   = $connection->fetchOne($checkSql, [$id, $idUtilisateur]);
       
        if (!$exists) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
       
        $sql = "UPDATE reservation_chambre
            SET nom = ?, prenom = ?, telephone = ?, email = ?, nbPersonnes = ?
            WHERE idRes = ? AND idUtilisateur = ?";
       
        $affected = $connection->executeStatement($sql, [
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['telephone'] ?? '',
            $data['email'] ?? '',
            $data['nbre'] ?? 1,
            $id,
            $idUtilisateur
        ]);
       
        if ($affected > 0) {
            return $this->json(['success' => true]);
        }
       
        return $this->json(['error' => 'Aucune modification'], 400);
    }

    #[Route('/api/reservations/chambres/{id}/qrcode', name: 'api_reservations_chambres_qrcode', methods: ['GET'])]
    public function generateQRCode(Connection $connection, QrCodeService $qrCodeService, int $id): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $idUtilisateur = $user->getId();

        $sql = "SELECT r.*, c.num as chambre_num, h.nom as hotel_nom
                FROM reservation_chambre r
                JOIN chambre c ON r.idCh = c.idCh
                JOIN hotel h ON c.idH = h.idH
                WHERE r.idRes = ? AND r.idUtilisateur = ?";
        $reservation = $connection->fetchAssociative($sql, [$id, $idUtilisateur]);
       
        if (!$reservation) {
            return $this->json(['error' => 'Réservation non trouvée'], 404);
        }
       
        $qrCodeDataUri = $qrCodeService->generateReservationQRCode($reservation);
       
        return $this->json([
            'success'     => true,
            'qrCode'      => $qrCodeDataUri,
            'reservation' => $reservation,
        ]);
    }

    // ========== SUPPRESSION AVEC REMBOURSEMENT ==========
   
    #[Route('/chambre/{id}/supprimer', name: 'app_reservation_ch_supprimer', methods: ['GET', 'POST'])]
    public function supprimer(Request $request, Connection $connection, int $id): Response
    {
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getId')) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer une réservation');
            return $this->redirectToRoute('app_login');
        }

        $idUtilisateur = $user->getId();

        $sql = "SELECT r.*, c.num as chambre_num, c.prix_nuit, h.nom as hotel_nom, h.ville as hotel_ville
                FROM reservation_chambre r
                JOIN chambre c ON r.idCh = c.idCh
                JOIN hotel h ON c.idH = h.idH
                WHERE r.idRes = ? AND r.idUtilisateur = ? AND r.statut != 'annulé'";
        $reservation = $connection->fetchAssociative($sql, [$id, $idUtilisateur]);

        if (!$reservation) {
            $this->addFlash('error', 'Réservation non trouvée ou déjà annulée');
            return $this->redirectToRoute('app_profile');
        }

        $dateSuppression = new \DateTime();
        $dateArrivee     = new \DateTime($reservation['dateDebut']);
       
        $interval           = $dateArrivee->diff($dateSuppression);
        $joursAvantArrivee  = (int) $interval->format('%a');
       
        if ($dateSuppression > $dateArrivee) {
            $pourcentageRemboursement = 0;
            $montantRembourse         = 0;
            $texteExplication         = "❌ Suppression après la date d'arrivée : aucun remboursement";
            $cssClass                 = 'danger';
            $icone                    = 'fa-times-circle';
        } elseif ($joursAvantArrivee >= 30) {
            $pourcentageRemboursement = 100;
            $montantRembourse         = $reservation['prixTotal'];
            $texteExplication         = "✅ Suppression {$joursAvantArrivee} jours avant l'arrivée (> 30 jours) → remboursement 100%";
            $cssClass                 = 'success';
            $icone                    = 'fa-check-circle';
        } elseif ($joursAvantArrivee >= 15) {
            $pourcentageRemboursement = 50;
            $montantRembourse         = $reservation['prixTotal'] * 0.5;
            $texteExplication         = "⚠️ Suppression {$joursAvantArrivee} jours avant l'arrivée (15-30 jours) → remboursement 50%";
            $cssClass                 = 'warning';
            $icone                    = 'fa-exclamation-triangle';
        } elseif ($joursAvantArrivee >= 7) {
            $pourcentageRemboursement = 25;
            $montantRembourse         = $reservation['prixTotal'] * 0.25;
            $texteExplication         = "⚠️ Suppression {$joursAvantArrivee} jours avant l'arrivée (7-15 jours) → remboursement 25%";
            $cssClass                 = 'info';
            $icone                    = 'fa-clock';
        } else {
            $pourcentageRemboursement = 0;
            $montantRembourse         = 0;
            $texteExplication         = "❌ Suppression {$joursAvantArrivee} jours avant l'arrivée (- de 7 jours) → aucun remboursement";
            $cssClass                 = 'danger';
            $icone                    = 'fa-times-circle';
        }

        if ($request->isMethod('POST')) {
            $sqlUpdate = "UPDATE reservation_chambre
                          SET statut = 'annulé',
                              dateAnnulation = ?,
                              montantRembourse = ?
                          WHERE idRes = ? AND idUtilisateur = ?";
           
            $connection->executeStatement($sqlUpdate, [
                $dateSuppression->format('Y-m-d H:i:s'),
                $montantRembourse,
                $id,
                $idUtilisateur
            ]);

            if ($montantRembourse > 0) {
                $this->addFlash('success', sprintf(
                    '✅ Réservation supprimée. Remboursement de %.2f DT (%d%%) effectué.',
                    $montantRembourse,
                    $pourcentageRemboursement
                ));
            } else {
                $this->addFlash('warning', sprintf(
                    '⚠️ Réservation supprimée. %s',
                    $texteExplication
                ));
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('reservation_ch/supprimer.html.twig', [
            'reservation'              => $reservation,
            'date_suppression'         => $dateSuppression,
            'jours_avant_arrivee'      => $joursAvantArrivee,
            'pourcentage_remboursement'=> $pourcentageRemboursement,
            'montant_rembourse'        => round($montantRembourse, 2),
            'texte_explication'        => $texteExplication,
            'css_class'                => $cssClass,
            'icone'                    => $icone,
        ]);
    }
}