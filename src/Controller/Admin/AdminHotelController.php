<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/hotel')]
class AdminHotelController extends AbstractController
{
    const ITEMS_PER_PAGE = 4;
    
    #[Route('/', name: 'admin_hotel_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $isAjax = $request->query->get('ajax', 0);
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        
        $searchCondition = "";
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = " WHERE (nom LIKE :search OR ville LIKE :search OR adresse LIKE :search) ";
            $params['search'] = "%$search%";
        }
        
        $totalHotels = $connection->fetchOne("SELECT COUNT(*) FROM hotel $searchCondition", $params);
        $totalPages = max(1, ceil($totalHotels / self::ITEMS_PER_PAGE));
        
        $hotels = $connection->fetchAllAssociative(
            "SELECT * FROM hotel $searchCondition ORDER BY idH DESC LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)$offset,
            $params
        );
        
        if ($isAjax) {
            $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('delete_hotel')->getValue();
            
            return $this->json([
                'hotels' => $hotels,
                'total_count' => $totalHotels,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'csrf_token' => $csrfToken
            ]);
        }
        
        return $this->render('admin/admin_hotel/index.html.twig', [
            'hotels' => $hotels,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'total_count' => $totalHotels,
        ]);
    }

    #[Route('/new', name: 'admin_hotel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection): Response
    {
        $errors = [];
        
        // ID utilisateur par défaut (1 = admin par défaut)
        $idUtilisateur = 1;
        
        $formData = [
            'nom' => '',
            'ville' => '',
            'adresse' => '',
            'etoiles' => '',
            'description' => '',
            'promotion' => '',
            'status' => 'disponible',
            'idUtilisateur' => $idUtilisateur,
        ];

        if ($request->isMethod('POST')) {
            $formData['nom'] = trim((string) $request->request->get('nom', ''));
            $formData['ville'] = trim((string) $request->request->get('ville', ''));
            $formData['adresse'] = trim((string) $request->request->get('adresse', ''));
            $formData['etoiles'] = trim((string) $request->request->get('etoiles', ''));
            $formData['description'] = trim((string) $request->request->get('description', ''));
            $formData['promotion'] = trim((string) $request->request->get('promotion', ''));
            $formData['status'] = trim((string) $request->request->get('status', 'disponible'));

            $imageFile = $request->files->get('image');

            // ========== CONTRÔLE DE SAISIE ==========

            // 1. Nom (obligatoire)
            if (empty($formData['nom'])) {
                $errors['nom'] = 'Le nom de l\'hôtel est requis.';
            } elseif (strlen($formData['nom']) < 2) {
                $errors['nom'] = 'Le nom doit contenir au moins 2 caractères.';
            } elseif (strlen($formData['nom']) > 120) {
                $errors['nom'] = 'Le nom ne peut pas dépasser 120 caractères.';
            }

            // 2. Ville (obligatoire)
            if (empty($formData['ville'])) {
                $errors['ville'] = 'La ville est requise.';
            } elseif (strlen($formData['ville']) < 2) {
                $errors['ville'] = 'La ville doit contenir au moins 2 caractères.';
            } elseif (strlen($formData['ville']) > 120) {
                $errors['ville'] = 'La ville ne peut pas dépasser 120 caractères.';
            }

            // 3. Adresse (obligatoire)
            if (empty($formData['adresse'])) {
                $errors['adresse'] = 'L\'adresse est requise.';
            } elseif (strlen($formData['adresse']) > 255) {
                $errors['adresse'] = 'L\'adresse ne peut pas dépasser 255 caractères.';
            }

            // 4. Étoiles (obligatoire)
            if (empty($formData['etoiles'])) {
                $errors['etoiles'] = 'Le nombre d\'étoiles est requis.';
            } elseif (!preg_match('/^[1-5]$/', $formData['etoiles'])) {
                $errors['etoiles'] = 'Les étoiles doivent être un nombre entre 1 et 5.';
            }

            // 5. Description (obligatoire)
            if (empty($formData['description'])) {
                $errors['description'] = 'La description est requise.';
            } elseif (strlen($formData['description']) > 65535) {
                $errors['description'] = 'La description est trop longue.';
            }

            // 6. Promotion (optionnel - peut être null)
            if (!empty($formData['promotion'])) {
                $promotionFormat = str_replace(',', '.', $formData['promotion']);
                if (!is_numeric($promotionFormat)) {
                    $errors['promotion'] = 'La promotion doit être une valeur numérique.';
                } elseif ($promotionFormat < 0 || $promotionFormat > 100) {
                    $errors['promotion'] = 'La promotion doit être comprise entre 0 et 100.';
                }
            }

            // 7. Status (obligatoire)
            if (empty($formData['status'])) {
                $errors['status'] = 'Le status est requis.';
            } else {
                $allowedStatus = ['disponible', 'indisponible', 'maintenance'];
                if (!in_array($formData['status'], $allowedStatus)) {
                    $errors['status'] = 'Le status doit être : disponible, indisponible ou maintenance.';
                }
            }

            // 8. Image (optionnel)
            if ($imageFile && $imageFile->isValid()) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $errors['image'] = 'L\'image doit être au format JPG, JPEG, PNG ou GIF.';
                }
            }

<<<<<<< HEAD
            // 9. idUtilisateur — toujours fixé à 1 (hardcodé), le check empty() est inutile
            // FIX :162 — empty($formData['idUtilisateur']) : PHPStan sait que la valeur est 1
            // (int littéral non falsy) => "always exists and is not falsy". On supprime ce check.
=======
            // 9. idUtilisateur déjà rempli automatiquement
            if (empty($formData['idUtilisateur'])) {
                $errors['idUtilisateur'] = 'Erreur : utilisateur non identifié.';
            }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

            if (count($errors) === 0) {
                $imageName = null;
                if ($imageFile && $imageFile->isValid()) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
<<<<<<< HEAD
                    // FIX :binaryOp — getParameter() retourne mixed, on ne peut pas concaténer directement
                    // assert(is_string()) affine le type pour PHPStan sans overhead runtime significatif
                    $uploadDir = $this->getParameter('uploads_hotels_directory');
                    assert(is_string($uploadDir));
                    $imageFile->move($uploadDir, $imageName);
=======
                    $imageFile->move($this->getParameter('uploads_hotels_directory'), $imageName);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                }

                $connection->executeStatement(
                    "INSERT INTO hotel (nom, ville, adresse, etoiles, description, promotion, image, status, idUtilisateur) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $formData['nom'],
                        $formData['ville'],
                        $formData['adresse'],
                        $formData['etoiles'],
                        $formData['description'],
                        $formData['promotion'] ?: null,
                        $imageName,
                        $formData['status'],
                        $formData['idUtilisateur']
                    ]
                );

                $this->addFlash('success', 'Hôtel créé avec succès !');
                return $this->redirectToRoute('admin_hotel_index');
            }
        }

        return $this->render('admin/admin_hotel/new.html.twig', [
            'errors' => $errors,
            'nom' => $formData['nom'],
            'ville' => $formData['ville'],
            'adresse' => $formData['adresse'],
            'etoiles' => $formData['etoiles'],
            'description' => $formData['description'],
            'promotion' => $formData['promotion'],
            'status' => $formData['status'],
            'idUtilisateur' => $formData['idUtilisateur'],
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_hotel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, int $id): Response
    {
        $hotel = $connection->fetchAssociative("SELECT * FROM hotel WHERE idH = ?", [$id]);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé');
        }

        $errors = [];
        $formData = [
            'nom' => $hotel['nom'],
            'ville' => $hotel['ville'],
            'adresse' => $hotel['adresse'],
            'etoiles' => $hotel['etoiles'],
            'description' => $hotel['description'],
            'promotion' => $hotel['promotion'],
            'status' => $hotel['status'],
            'idUtilisateur' => $hotel['idUtilisateur'],
        ];
        
        if ($request->isMethod('POST')) {
            $formData['nom'] = trim((string) $request->request->get('nom', ''));
            $formData['ville'] = trim((string) $request->request->get('ville', ''));
            $formData['adresse'] = trim((string) $request->request->get('adresse', ''));
            $formData['etoiles'] = trim((string) $request->request->get('etoiles', ''));
            $formData['description'] = trim((string) $request->request->get('description', ''));
            $formData['promotion'] = trim((string) $request->request->get('promotion', ''));
            $formData['status'] = trim((string) $request->request->get('status', 'disponible'));

            $imageFile = $request->files->get('image');

            // ========== CONTRÔLE DE SAISIE ==========

            // 1. Nom (obligatoire)
            if (empty($formData['nom'])) {
                $errors['nom'] = 'Le nom de l\'hôtel est requis.';
            } elseif (strlen($formData['nom']) < 2) {
                $errors['nom'] = 'Le nom doit contenir au moins 2 caractères.';
            } elseif (strlen($formData['nom']) > 120) {
                $errors['nom'] = 'Le nom ne peut pas dépasser 120 caractères.';
            }

            // 2. Ville (obligatoire)
            if (empty($formData['ville'])) {
                $errors['ville'] = 'La ville est requise.';
            } elseif (strlen($formData['ville']) < 2) {
                $errors['ville'] = 'La ville doit contenir au moins 2 caractères.';
            } elseif (strlen($formData['ville']) > 120) {
                $errors['ville'] = 'La ville ne peut pas dépasser 120 caractères.';
            }

            // 3. Adresse (obligatoire)
            if (empty($formData['adresse'])) {
                $errors['adresse'] = 'L\'adresse est requise.';
            } elseif (strlen($formData['adresse']) > 255) {
                $errors['adresse'] = 'L\'adresse ne peut pas dépasser 255 caractères.';
            }

            // 4. Étoiles (obligatoire)
            if (empty($formData['etoiles'])) {
                $errors['etoiles'] = 'Le nombre d\'étoiles est requis.';
            } elseif (!preg_match('/^[1-5]$/', $formData['etoiles'])) {
                $errors['etoiles'] = 'Les étoiles doivent être un nombre entre 1 et 5.';
            }

            // 5. Description (obligatoire)
            if (empty($formData['description'])) {
                $errors['description'] = 'La description est requise.';
            } elseif (strlen($formData['description']) > 65535) {
                $errors['description'] = 'La description est trop longue.';
            }

            // 6. Promotion (optionnel - peut être null)
            if (!empty($formData['promotion'])) {
                $promotionFormat = str_replace(',', '.', $formData['promotion']);
                if (!is_numeric($promotionFormat)) {
                    $errors['promotion'] = 'La promotion doit être une valeur numérique.';
                } elseif ($promotionFormat < 0 || $promotionFormat > 100) {
                    $errors['promotion'] = 'La promotion doit être comprise entre 0 et 100.';
                }
            }

            // 7. Status (obligatoire)
            if (empty($formData['status'])) {
                $errors['status'] = 'Le status est requis.';
            } else {
                $allowedStatus = ['disponible', 'indisponible', 'maintenance'];
                if (!in_array($formData['status'], $allowedStatus)) {
                    $errors['status'] = 'Le status doit être : disponible, indisponible ou maintenance.';
                }
            }

            // 8. Image (optionnel)
            if ($imageFile && $imageFile->isValid()) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $errors['image'] = 'L\'image doit être au format JPG, JPEG, PNG ou GIF.';
                }
            }

            if (count($errors) === 0) {
                $imageName = $hotel['image'];
                if ($imageFile && $imageFile->isValid()) {
<<<<<<< HEAD
                    // FIX :314/:315 — getParameter() retourne mixed, concaténation directe invalide
                    // On extrait dans une variable et on assert is_string() pour PHPStan
                    $uploadDir = $this->getParameter('uploads_hotels_directory');
                    assert(is_string($uploadDir));

                    if ($imageName && file_exists($uploadDir . '/' . $imageName)) {
                        unlink($uploadDir . '/' . $imageName);
=======
                    if ($imageName && file_exists($this->getParameter('uploads_hotels_directory') . '/' . $imageName)) {
                        unlink($this->getParameter('uploads_hotels_directory') . '/' . $imageName);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
<<<<<<< HEAD
                    $imageFile->move($uploadDir, $imageName);
=======
                    $imageFile->move($this->getParameter('uploads_hotels_directory'), $imageName);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                }

                $connection->executeStatement(
                    "UPDATE hotel SET nom = ?, ville = ?, adresse = ?, etoiles = ?, description = ?, promotion = ?, image = ?, status = ? WHERE idH = ?",
                    [
                        $formData['nom'],
                        $formData['ville'],
                        $formData['adresse'],
                        $formData['etoiles'],
                        $formData['description'],
                        $formData['promotion'] ?: null,
                        $imageName,
                        $formData['status'],
                        $id
                    ]
                );

                $this->addFlash('success', 'Hôtel modifié avec succès !');
                return $this->redirectToRoute('admin_hotel_index');
            }
        }

        return $this->render('admin/admin_hotel/edit.html.twig', [
            'hotel' => $hotel,
            'errors' => $errors,
            'nom' => $formData['nom'],
            'ville' => $formData['ville'],
            'adresse' => $formData['adresse'],
            'etoiles' => $formData['etoiles'],
            'description' => $formData['description'],
            'promotion' => $formData['promotion'],
            'status' => $formData['status'],
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_hotel_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
<<<<<<< HEAD
        // FIX :360 — isCsrfTokenValid() attend string|null, mais get() retourne mixed
        // On force le type avec is_string() ? $token : null
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_hotel_' . $id, is_string($token) ? $token : null)) {
            $hotel = $connection->fetchAssociative("SELECT image FROM hotel WHERE idH = ?", [$id]);

            if ($hotel && $hotel['image']) {
                // FIX :363/:364 — même problème getParameter() mixed + concaténation
                $uploadDir = $this->getParameter('uploads_hotels_directory');
                assert(is_string($uploadDir));

                if (file_exists($uploadDir . '/' . $hotel['image'])) {
                    unlink($uploadDir . '/' . $hotel['image']);
                }
=======
        if ($this->isCsrfTokenValid('delete_hotel_' . $id, $request->request->get('_token'))) {
            $hotel = $connection->fetchAssociative("SELECT image FROM hotel WHERE idH = ?", [$id]);
            
            if ($hotel && $hotel['image'] && file_exists($this->getParameter('uploads_hotels_directory') . '/' . $hotel['image'])) {
                unlink($this->getParameter('uploads_hotels_directory') . '/' . $hotel['image']);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            }
            
            $connection->executeStatement("DELETE FROM hotel WHERE idH = ?", [$id]);
            $this->addFlash('success', 'Hôtel supprimé avec succès !');
        }
        
        return $this->redirectToRoute('admin_hotel_index');
    }

    #[Route('/{id}/chambres', name: 'admin_hotel_chambres')]
    public function chambres(Connection $connection, int $id): Response
    {
        $hotel = $connection->fetchAssociative("SELECT * FROM hotel WHERE idH = ?", [$id]);
        $chambres = $connection->fetchAllAssociative("SELECT * FROM chambre WHERE idH = ? ORDER BY idCh DESC", [$id]);
        
        return $this->render('admin/admin_hotel/chambres.html.twig', [
            'hotel' => $hotel,
            'chambres' => $chambres,
        ]);
    }
}