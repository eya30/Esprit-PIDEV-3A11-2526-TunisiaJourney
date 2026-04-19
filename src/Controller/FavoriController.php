<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/favoris')]
class FavoriController extends AbstractController
{
    // ID utilisateur fixe (à remplacer plus tard par $this->getUser()->getId())
    private const USER_ID = 36;

    // ── Lister tous les favoris de l'utilisateur ─────────────────────────────

    #[Route('', name: 'api_favoris_list', methods: ['GET'])]
    public function list(Connection $connection): JsonResponse
    {
        $favoris = $connection->fetchAllAssociative(
            "SELECT f.idfav, f.type, f.IDEv, f.IDAct, f.created_at,
                    e.Titre   AS titre_ev,
                    e.Lieu    AS lieu_ev,
                    e.Image   AS image_ev,
                    a.Titre   AS titre_act,
                    a.TypeActivite,
                    a.Image   AS image_act
             FROM favori f
             LEFT JOIN Evenement e ON e.IDEv  = f.IDEv
             LEFT JOIN Activite  a ON a.IDAct = f.IDAct
             WHERE f.id = ?
             ORDER BY f.created_at DESC",
            [self::USER_ID]
        );

        $result = [];
        foreach ($favoris as $fav) {
            if ($fav['type'] === 'evenement') {
                $result[] = [
                    'idfav'      => $fav['idfav'],
                    'type'       => 'evenement',
                    'id'         => $fav['IDEv'],
                    'titre'      => $fav['titre_ev'] ?? '',
                    'sousTitre'  => $fav['lieu_ev'] ?? '',
                    'image'      => $fav['image_ev']
                        ? '/uploads/evenements/' . basename($fav['image_ev'])
                        : 'https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg?auto=compress&cs=tinysrgb&w=400',
                    'urlDetails'    => '/evenement/' . $fav['IDEv'],
                    'urlActivites'  => '/evenement/' . $fav['IDEv'] . '/activites',
                ];
            } else {
                // On récupère IDEv de l'activité pour construire le lien activités
                $actRow = $connection->fetchAssociative(
                    "SELECT IDEv FROM Activite WHERE IDAct = ?",
                    [$fav['IDAct']]
                );
                $idEvAct = $actRow['IDEv'] ?? null;

                $result[] = [
                    'idfav'      => $fav['idfav'],
                    'type'       => 'activite',
                    'id'         => $fav['IDAct'],
                    'titre'      => $fav['titre_act'] ?? '',
                    'sousTitre'  => $fav['TypeActivite'] ?? '',
                    'image'      => $fav['image_act']
                        ? '/uploads/activites/' . basename($fav['image_act'])
                        : 'https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg?auto=compress&cs=tinysrgb&w=400',
                    'urlDetails'    => '/activite/' . $fav['IDAct'],
                    'urlActivites'  => $idEvAct ? '/evenement/' . $idEvAct . '/activites' : '#',
                ];
            }
        }

        return new JsonResponse($result);
    }

    // ── Vérifier si un élément est en favori ──────────────────────────────────

    #[Route('/check', name: 'api_favoris_check', methods: ['GET'])]
    public function check(Request $request, Connection $connection): JsonResponse
    {
        $type  = $request->query->get('type');   // 'evenement' ou 'activite'
        $refId = (int) $request->query->get('id');

        if (!in_array($type, ['evenement', 'activite']) || $refId <= 0) {
            return new JsonResponse(['isFavori' => false]);
        }

        $col   = $type === 'evenement' ? 'IDEv' : 'IDAct';
        $count = $connection->fetchOne(
            "SELECT COUNT(*) FROM favori WHERE id = ? AND type = ? AND $col = ?",
            [self::USER_ID, $type, $refId]
        );

        return new JsonResponse(['isFavori' => (int)$count > 0]);
    }

    // ── Récupérer les IDs favoris (pour initialisation JS) ───────────────────

    #[Route('/ids', name: 'api_favoris_ids', methods: ['GET'])]
    public function ids(Connection $connection): JsonResponse
    {
        $rows = $connection->fetchAllAssociative(
            "SELECT type, IDEv, IDAct FROM favori WHERE id = ?",
            [self::USER_ID]
        );

        $evenements = [];
        $activites  = [];
        foreach ($rows as $row) {
            if ($row['type'] === 'evenement' && $row['IDEv']) {
                $evenements[] = (int)$row['IDEv'];
            } elseif ($row['type'] === 'activite' && $row['IDAct']) {
                $activites[] = (int)$row['IDAct'];
            }
        }

        return new JsonResponse([
            'evenements' => $evenements,
            'activites'  => $activites,
        ]);
    }

    // ── Ajouter un favori ─────────────────────────────────────────────────────

    #[Route('/add', name: 'api_favoris_add', methods: ['POST'])]
    public function add(Request $request, Connection $connection): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $type  = $data['type']  ?? null;
        $refId = (int)($data['id'] ?? 0);

        if (!in_array($type, ['evenement', 'activite']) || $refId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
        }

        try {
            if ($type === 'evenement') {
                $connection->executeStatement(
                    "INSERT IGNORE INTO favori (id, type, IDEv)  VALUES (?, 'evenement', ?)",
                    [self::USER_ID, $refId]
                );
            } else {
                $connection->executeStatement(
                    "INSERT IGNORE INTO favori (id, type, IDAct) VALUES (?, 'activite', ?)",
                    [self::USER_ID, $refId]
                );
            }
            return new JsonResponse(['success' => true, 'action' => 'added']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Supprimer un favori ───────────────────────────────────────────────────

    #[Route('/remove', name: 'api_favoris_remove', methods: ['POST'])]
    public function remove(Request $request, Connection $connection): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $type  = $data['type']  ?? null;
        $refId = (int)($data['id'] ?? 0);

        if (!in_array($type, ['evenement', 'activite']) || $refId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
        }

        try {
            $col = $type === 'evenement' ? 'IDEv' : 'IDAct';
            $connection->executeStatement(
                "DELETE FROM favori WHERE id = ? AND type = ? AND $col = ?",
                [self::USER_ID, $type, $refId]
            );
            return new JsonResponse(['success' => true, 'action' => 'removed']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Toggle (add ou remove selon l'état actuel) ────────────────────────────

    #[Route('/toggle', name: 'api_favoris_toggle', methods: ['POST'])]
    public function toggle(Request $request, Connection $connection): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $type  = $data['type']  ?? null;
        $refId = (int)($data['id'] ?? 0);

        if (!in_array($type, ['evenement', 'activite']) || $refId <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
        }

        $col   = $type === 'evenement' ? 'IDEv' : 'IDAct';
        $count = (int)$connection->fetchOne(
            "SELECT COUNT(*) FROM favori WHERE id = ? AND type = ? AND $col = ?",
            [self::USER_ID, $type, $refId]
        );

        try {
            if ($count > 0) {
                // Supprimer
                $connection->executeStatement(
                    "DELETE FROM favori WHERE id = ? AND type = ? AND $col = ?",
                    [self::USER_ID, $type, $refId]
                );
                return new JsonResponse(['success' => true, 'action' => 'removed', 'isFavori' => false]);
            } else {
                // Ajouter
                if ($type === 'evenement') {
                    $connection->executeStatement(
                        "INSERT IGNORE INTO favori (id, type, IDEv)  VALUES (?, 'evenement', ?)",
                        [self::USER_ID, $refId]
                    );
                } else {
                    $connection->executeStatement(
                        "INSERT IGNORE INTO favori (id, type, IDAct) VALUES (?, 'activite', ?)",
                        [self::USER_ID, $refId]
                    );
                }
                return new JsonResponse(['success' => true, 'action' => 'added', 'isFavori' => true]);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}