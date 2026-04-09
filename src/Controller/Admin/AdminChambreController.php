<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/admin/chambre')]
class AdminChambreController extends AbstractController
{
    #[Route('/', name: 'admin_chambre_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $search = $request->query->get('search', '');
        
        $searchCondition = "";
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = " WHERE (c.num LIKE :search OR c.type LIKE :search OR h.nom LIKE :search) ";
            $params['search'] = "%$search%";
        }
        
        $chambres = $connection->fetchAllAssociative("
            SELECT c.*, h.nom as hotel_nom 
            FROM chambre c 
            LEFT JOIN hotel h ON c.idH = h.idH 
            $searchCondition
            ORDER BY c.idCh DESC
        ", $params);
        
        return $this->render('admin/admin_chambre/index.html.twig', [
            'chambres' => $chambres,
            'search' => $search,
            'total_count' => count($chambres),
        ]);
    }

    #[Route('/new', name: 'admin_chambre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection): Response
    {
        $hotels = $connection->fetchAllAssociative("SELECT idH, nom, ville FROM hotel ORDER BY nom");
        $errors = [];
        $formData = [];

        if ($request->isMethod('POST')) {
            $formData = [
                'num' => trim($request->request->get('num', '')),
                'type' => trim($request->request->get('type', '')),
                'prix_nuit' => trim($request->request->get('prix_nuit', '')),
                'status' => trim($request->request->get('status', 'disponible')),
                'capacite_max' => trim($request->request->get('capacite_max', '')),
                'description' => trim($request->request->get('description', '')),
                'modele3D_URL' => trim($request->request->get('modele3D_URL', '')),
                'idH' => trim($request->request->get('idH', '')),
            ];

            // ========== VALIDATION AVEC SYMFONY ==========
            $validator = Validation::createValidator();
            
            // 1. Validation du numéro
            $numViolations = $validator->validate($formData['num'], [
                new Assert\NotBlank(['message' => 'Le numéro de chambre est requis.']),
                new Assert\Positive(['message' => 'Le numéro doit être un nombre positif.']),
            ]);
            foreach ($numViolations as $violation) {
                $errors['num'] = $violation->getMessage();
            }

            // 2. Validation du type
            $typeViolations = $validator->validate($formData['type'], [
                new Assert\NotBlank(['message' => 'Le type de chambre est requis.']),
                new Assert\Choice([
                    'choices' => ['simple', 'double', 'suite', 'familiale'],
                    'message' => 'Le type doit être : simple, double, suite ou familiale.'
                ]),
            ]);
            foreach ($typeViolations as $violation) {
                $errors['type'] = $violation->getMessage();
            }

            // 3. Validation du prix
            $prixViolations = $validator->validate($formData['prix_nuit'], [
                new Assert\NotBlank(['message' => 'Le prix par nuit est requis.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Le prix doit être un nombre.']),
                new Assert\Positive(['message' => 'Le prix doit être supérieur à 0.']),
            ]);
            foreach ($prixViolations as $violation) {
                $errors['prix_nuit'] = $violation->getMessage();
            }

            // 4. Validation du status
            $statusViolations = $validator->validate($formData['status'], [
                new Assert\NotBlank(['message' => 'Le status est requis.']),
                new Assert\Choice([
                    'choices' => ['disponible', 'indisponible', 'maintenance'],
                    'message' => 'Le status doit être : disponible, indisponible ou maintenance.'
                ]),
            ]);
            foreach ($statusViolations as $violation) {
                $errors['status'] = $violation->getMessage();
            }

            // 5. Validation de la capacité
            $capaciteViolations = $validator->validate($formData['capacite_max'], [
                new Assert\NotBlank(['message' => 'La capacité maximale est requise.']),
                new Assert\Positive(['message' => 'La capacité doit être un nombre positif.']),
            ]);
            foreach ($capaciteViolations as $violation) {
                $errors['capacite_max'] = $violation->getMessage();
            }

            // 6. Validation de l'hôtel associé
            $idHViolations = $validator->validate($formData['idH'], [
                new Assert\NotBlank(['message' => 'L\'hôtel associé est requis.']),
                new Assert\Positive(['message' => 'L\'ID de l\'hôtel doit être un nombre.']),
            ]);
            foreach ($idHViolations as $violation) {
                $errors['idH'] = $violation->getMessage();
            }

            // Vérifier si l'hôtel existe dans la base
            if (empty($errors['idH']) && !empty($formData['idH'])) {
                $hotelExists = $connection->fetchOne("SELECT COUNT(*) FROM hotel WHERE idH = ?", [$formData['idH']]);
                if (!$hotelExists) {
                    $errors['idH'] = 'L\'hôtel sélectionné n\'existe pas.';
                }
            }

            // 7. Validation de l'URL modèle 3D (optionnel)
            if (!empty($formData['modele3D_URL'])) {
                $urlViolations = $validator->validate($formData['modele3D_URL'], [
                    new Assert\Url(['message' => 'L\'URL du modèle 3D doit être une URL valide.']),
                ]);
                foreach ($urlViolations as $violation) {
                    $errors['modele3D_URL'] = $violation->getMessage();
                }
            }

            // 8. Validation de l'image (optionnel)
            $imageFile = $request->files->get('image');
            if ($imageFile && $imageFile->isValid()) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $errors['image'] = 'L\'image doit être au format JPG, JPEG, PNG ou GIF.';
                }
            }

            // ========== FIN VALIDATION ==========

            if (count($errors) === 0) {
                $imageName = null;
                if ($imageFile) {
                    // vérifier les erreurs d'upload
                    if (!$imageFile->isValid()) {
                        $errors['image'] = 'Erreur lors de l\'upload de l\'image (vérifier la taille et le dossier temporaire PHP).';
                    }
                }

                if (count($errors) === 0 && $imageFile && $imageFile->isValid()) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();

                    // s'assurer que le dossier d'uploads existe
                    $targetDir = $this->getParameter('uploads_chambres_directory');
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0777, true);
                    }

                    // déplacer le fichier
                    try {
                        $imageFile->move($targetDir, $imageName);
                    } catch (\Exception $e) {
                        $errors['image'] = 'Impossible de déplacer le fichier uploadé : ' . $e->getMessage();
                        $imageName = null;
                    }
                }

                $connection->executeStatement(
                    "INSERT INTO chambre (num, type, prix_nuit, status, capacite_max, description, image, modele3D_URL, idH) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $formData['num'],
                        $formData['type'],
                        $formData['prix_nuit'],
                        $formData['status'],
                        $formData['capacite_max'],
                        $formData['description'],
                        $imageName,
                        $formData['modele3D_URL'] ?: null,
                        $formData['idH']
                    ]
                );

                $this->addFlash('success', 'Chambre créée avec succès !');
                return $this->redirectToRoute('admin_chambre_index');
            }
        }

        return $this->render('admin/admin_chambre/new.html.twig', [
            'hotels' => $hotels,
            'errors' => $errors,
            'num' => $formData['num'] ?? '',
            'type' => $formData['type'] ?? '',
            'prix_nuit' => $formData['prix_nuit'] ?? '',
            'status' => $formData['status'] ?? 'disponible',
            'capacite_max' => $formData['capacite_max'] ?? '',
            'description' => $formData['description'] ?? '',
            'modele3D_URL' => $formData['modele3D_URL'] ?? '',
            'idH' => $formData['idH'] ?? '',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_chambre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, int $id): Response
    {
        $chambre = $connection->fetchAssociative("SELECT * FROM chambre WHERE idCh = ?", [$id]);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre non trouvée');
        }

        $hotels = $connection->fetchAllAssociative("SELECT idH, nom, ville FROM hotel ORDER BY nom");
        $errors = [];
        $formData = [
            'num' => $chambre['num'],
            'type' => $chambre['type'],
            'prix_nuit' => $chambre['prix_nuit'],
            'status' => $chambre['status'],
            'capacite_max' => $chambre['capacite_max'],
            'description' => $chambre['description'],
            'modele3D_URL' => $chambre['modele3D_URL'],
            'idH' => $chambre['idH'],
        ];

        if ($request->isMethod('POST')) {
            $formData = [
                'num' => trim($request->request->get('num', '')),
                'type' => trim($request->request->get('type', '')),
                'prix_nuit' => trim($request->request->get('prix_nuit', '')),
                'status' => trim($request->request->get('status', 'disponible')),
                'capacite_max' => trim($request->request->get('capacite_max', '')),
                'description' => trim($request->request->get('description', '')),
                'modele3D_URL' => trim($request->request->get('modele3D_URL', '')),
                'idH' => trim($request->request->get('idH', '')),
            ];

            // ========== VALIDATION AVEC SYMFONY ==========
            $validator = Validation::createValidator();
            
            $numViolations = $validator->validate($formData['num'], [
                new Assert\NotBlank(['message' => 'Le numéro de chambre est requis.']),
                new Assert\Positive(['message' => 'Le numéro doit être un nombre positif.']),
            ]);
            foreach ($numViolations as $violation) { $errors['num'] = $violation->getMessage(); }

            $typeViolations = $validator->validate($formData['type'], [
                new Assert\NotBlank(['message' => 'Le type de chambre est requis.']),
                new Assert\Choice([
                    'choices' => ['simple', 'double', 'suite', 'familiale'],
                    'message' => 'Le type doit être : simple, double, suite ou familiale.'
                ]),
            ]);
            foreach ($typeViolations as $violation) { $errors['type'] = $violation->getMessage(); }

            $prixViolations = $validator->validate($formData['prix_nuit'], [
                new Assert\NotBlank(['message' => 'Le prix par nuit est requis.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Le prix doit être un nombre.']),
                new Assert\Positive(['message' => 'Le prix doit être supérieur à 0.']),
            ]);
            foreach ($prixViolations as $violation) { $errors['prix_nuit'] = $violation->getMessage(); }

            $statusViolations = $validator->validate($formData['status'], [
                new Assert\NotBlank(['message' => 'Le status est requis.']),
                new Assert\Choice([
                    'choices' => ['disponible', 'indisponible', 'maintenance'],
                    'message' => 'Le status doit être : disponible, indisponible ou maintenance.'
                ]),
            ]);
            foreach ($statusViolations as $violation) { $errors['status'] = $violation->getMessage(); }

            $capaciteViolations = $validator->validate($formData['capacite_max'], [
                new Assert\NotBlank(['message' => 'La capacité maximale est requise.']),
                new Assert\Positive(['message' => 'La capacité doit être un nombre positif.']),
            ]);
            foreach ($capaciteViolations as $violation) { $errors['capacite_max'] = $violation->getMessage(); }

            $idHViolations = $validator->validate($formData['idH'], [
                new Assert\NotBlank(['message' => 'L\'hôtel associé est requis.']),
                new Assert\Positive(['message' => 'L\'ID de l\'hôtel doit être un nombre.']),
            ]);
            foreach ($idHViolations as $violation) { $errors['idH'] = $violation->getMessage(); }

            if (empty($errors['idH']) && !empty($formData['idH'])) {
                $hotelExists = $connection->fetchOne("SELECT COUNT(*) FROM hotel WHERE idH = ?", [$formData['idH']]);
                if (!$hotelExists) {
                    $errors['idH'] = 'L\'hôtel sélectionné n\'existe pas.';
                }
            }

            if (!empty($formData['modele3D_URL'])) {
                $urlViolations = $validator->validate($formData['modele3D_URL'], [
                    new Assert\Url(['message' => 'L\'URL du modèle 3D doit être une URL valide.']),
                ]);
                foreach ($urlViolations as $violation) { $errors['modele3D_URL'] = $violation->getMessage(); }
            }

            $imageFile = $request->files->get('image');
            if ($imageFile && $imageFile->isValid()) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, $allowedExtensions)) {
                    $errors['image'] = 'L\'image doit être au format JPG, JPEG, PNG ou GIF.';
                }
            }

            // ========== FIN VALIDATION ==========

            if (count($errors) === 0) {
                $imageName = $chambre['image'];
                if ($imageFile) {
                    if (!$imageFile->isValid()) {
                        $errors['image'] = 'Erreur lors de l\'upload de l\'image (vérifier la taille et le dossier temporaire PHP).';
                    }
                }

                if (count($errors) === 0 && $imageFile && $imageFile->isValid()) {
                    $targetDir = $this->getParameter('uploads_chambres_directory');

                    // supprimer l'ancienne image si elle existe
                    if ($imageName) {
                        $oldPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $imageName;
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();

                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0777, true);
                    }

                    try {
                        $imageFile->move($targetDir, $imageName);
                    } catch (\Exception $e) {
                        $errors['image'] = 'Impossible de déplacer le fichier uploadé : ' . $e->getMessage();
                        $imageName = $chambre['image'];
                    }
                }

                $connection->executeStatement(
                    "UPDATE chambre SET num = ?, type = ?, prix_nuit = ?, status = ?, capacite_max = ?, description = ?, image = ?, modele3D_URL = ?, idH = ? WHERE idCh = ?",
                    [
                        $formData['num'],
                        $formData['type'],
                        $formData['prix_nuit'],
                        $formData['status'],
                        $formData['capacite_max'],
                        $formData['description'],
                        $imageName,
                        $formData['modele3D_URL'] ?: null,
                        $formData['idH'],
                        $id
                    ]
                );

                $this->addFlash('success', 'Chambre modifiée avec succès !');
                return $this->redirectToRoute('admin_chambre_index');
            }
        }

        return $this->render('admin/admin_chambre/edit.html.twig', [
            'chambre' => $chambre,
            'hotels' => $hotels,
            'errors' => $errors,
            'num' => $formData['num'],
            'type' => $formData['type'],
            'prix_nuit' => $formData['prix_nuit'],
            'status' => $formData['status'],
            'capacite_max' => $formData['capacite_max'],
            'description' => $formData['description'],
            'modele3D_URL' => $formData['modele3D_URL'],
            'idH' => $formData['idH'],
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_chambre_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete_chambre_' . $id, $request->request->get('_token'))) {
            $chambre = $connection->fetchAssociative("SELECT image FROM chambre WHERE idCh = ?", [$id]);
            if ($chambre && $chambre['image']) {
                $targetDir = $this->getParameter('uploads_chambres_directory');
                $filePath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $chambre['image'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $connection->executeStatement("DELETE FROM chambre WHERE idCh = ?", [$id]);
            $this->addFlash('success', 'Chambre supprimée avec succès !');
        }
        return $this->redirectToRoute('admin_chambre_index');
    }

    #[Route('/{id}/reservations', name: 'admin_chambre_reservations')]
    public function reservations(Connection $connection, int $id): Response
    {
        $chambre = $connection->fetchAssociative("
            SELECT c.*, h.nom as hotel_nom 
            FROM chambre c 
            LEFT JOIN hotel h ON c.idH = h.idH 
            WHERE c.idCh = ?
        ", [$id]);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre non trouvée');
        }
        
        $reservations = $connection->fetchAllAssociative("
            SELECT * FROM reservation_chambre WHERE idCh = ? ORDER BY idRes DESC
        ", [$id]);
        
        return $this->render('admin/admin_chambre/reservations.html.twig', [
            'chambre' => $chambre,
            'reservations' => $reservations,
        ]);
    }
}