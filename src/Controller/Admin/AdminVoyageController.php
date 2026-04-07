<?php

namespace App\Controller\Admin;

use App\Entity\Voyage;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/voyages')]
class AdminVoyageController extends AbstractController
{
    const ITEMS_PER_PAGE = 4;
    
    #[Route('/', name: 'admin_voyage_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search', '');
        $isAjax = $request->query->get('ajax', 0);
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        
        $searchCondition = "";
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = " WHERE (nom LIKE :search OR description LIKE :search) ";
            $params['search'] = "%$search%";
        }
        
        // Compter le nombre total de voyages
        $totalVoyages = $connection->fetchOne("SELECT COUNT(*) FROM voyages $searchCondition", $params);
        $totalPages = max(1, ceil($totalVoyages / self::ITEMS_PER_PAGE));
        
        // Récupérer les voyages paginés
        $voyages = $connection->fetchAllAssociative(
            "SELECT * FROM voyages $searchCondition ORDER BY idV DESC LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)$offset,
            $params
        );
        
        // Si c'est une requête AJAX, retourner JSON
        if ($isAjax) {
            // Générer le token CSRF pour chaque voyage
            $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('delete_voyage')->getValue();
            
            return $this->json([
                'voyages' => $voyages,
                'total_count' => $totalVoyages,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'csrf_token' => $csrfToken
            ]);
        }
        
        return $this->render('admin/voyage/index.html.twig', [
            'voyages' => $voyages,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'total_count' => $totalVoyages,
        ]);
    }

    #[Route('/new', name: 'admin_voyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection, ValidatorInterface $validator): Response
    {
        $errors = [];
        $formData = [
            'nom' => '',
            'description' => '',
            'capacite' => '',
            'prix' => '',
            'dateCreation' => '',
            'heure' => '',
        ];

        if ($request->isMethod('POST')) {
            $formData['nom'] = trim((string) $request->request->get('nom', ''));
            $formData['description'] = trim((string) $request->request->get('description', ''));
            $formData['capacite'] = trim((string) $request->request->get('capacite', ''));
            $formData['prix'] = trim((string) $request->request->get('prix', ''));
            $formData['dateCreation'] = trim((string) $request->request->get('dateCreation', ''));
            $formData['heure'] = trim((string) $request->request->get('heure', ''));

            $voyage = new Voyage();
            $voyage->setNom($formData['nom']);
            $voyage->setDescription($formData['description']);

            if ($formData['capacite'] !== '') {
                if (preg_match('/^[0-9]+$/', $formData['capacite'])) {
                    $voyage->setCapacite((int) $formData['capacite']);
                } else {
                    $errors[] = 'La capacité doit être un chiffre entier.';
                }
            }

            if ($formData['prix'] !== '') {
                $prixFormat = str_replace(',', '.', $formData['prix']);
                if (is_numeric($prixFormat)) {
                    $voyage->setPrix((float) $prixFormat);
                } else {
                    $errors[] = 'Le prix doit être une valeur numérique.';
                }
            }

            if ($formData['dateCreation'] !== '') {
                $date = \DateTime::createFromFormat('Y-m-d', $formData['dateCreation']);
                if ($date instanceof \DateTimeInterface) {
                    $voyage->setDateCreation($date);
                } else {
                    $errors[] = 'La date de création doit être au format YYYY-MM-DD.';
                }
            }

            if ($formData['heure'] !== '') {
                $time = \DateTime::createFromFormat('H:i', $formData['heure']);
                if ($time instanceof \DateTimeInterface) {
                    $voyage->setHeure($time);
                } else {
                    $errors[] = 'L\'heure doit être au format HH:MM.';
                }
            }

            if (count($errors) === 0) {
                $violations = $validator->validate($voyage);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $errors[] = $violation->getMessage();
                    }
                }
            }

            if (count($errors) === 0) {
                $imageFile = $request->files->get('image');
                $imageName = null;

                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_directory'), $imageName);
                }

                $connection->executeStatement(
                    "INSERT INTO voyages (nom, description, capacite, prix, dateCreation, heure, image, id_user) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$formData['nom'], $formData['description'], $voyage->getCapacite(), $voyage->getPrix(), $voyage->getDateCreation()?->format('Y-m-d'), $voyage->getHeure()?->format('H:i:s'), $imageName, 1]
                );

                $this->addFlash('success', 'Voyage créé avec succès !');
                return $this->redirectToRoute('admin_voyage_index');
            }
        }

        return $this->render('admin/voyage/new.html.twig', [
            'errors' => $errors,
            'nom' => $formData['nom'],
            'description' => $formData['description'],
            'capacite' => $formData['capacite'],
            'prix' => $formData['prix'],
            'dateCreation' => $formData['dateCreation'],
            'heure' => $formData['heure'],
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_voyage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, ValidatorInterface $validator, int $id): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$id]);
        
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage non trouvé');
        }

        $errors = [];
        $formData = [
            'nom' => $voyage['nom'],
            'description' => $voyage['description'],
            'capacite' => $voyage['capacite'],
            'prix' => $voyage['prix'],
            'dateCreation' => $voyage['dateCreation'],
            'heure' => $voyage['heure'],
        ];
        
        if ($request->isMethod('POST')) {
            $formData['nom'] = trim((string) $request->request->get('nom', ''));
            $formData['description'] = trim((string) $request->request->get('description', ''));
            $formData['capacite'] = trim((string) $request->request->get('capacite', ''));
            $formData['prix'] = trim((string) $request->request->get('prix', ''));
            $formData['dateCreation'] = trim((string) $request->request->get('dateCreation', ''));
            $formData['heure'] = trim((string) $request->request->get('heure', ''));

            $voyageEntity = new Voyage();
            $voyageEntity->setNom($formData['nom']);
            $voyageEntity->setDescription($formData['description']);

            if ($formData['capacite'] !== '') {
                if (preg_match('/^[0-9]+$/', $formData['capacite'])) {
                    $voyageEntity->setCapacite((int) $formData['capacite']);
                } else {
                    $errors[] = 'La capacité doit être un chiffre entier.';
                }
            }

            if ($formData['prix'] !== '') {
                $prixFormat = str_replace(',', '.', $formData['prix']);
                if (is_numeric($prixFormat)) {
                    $voyageEntity->setPrix((float) $prixFormat);
                } else {
                    $errors[] = 'Le prix doit être une valeur numérique.';
                }
            }

            if ($formData['dateCreation'] !== '') {
                $date = \DateTime::createFromFormat('Y-m-d', $formData['dateCreation']);
                if ($date instanceof \DateTimeInterface) {
                    $voyageEntity->setDateCreation($date);
                } else {
                    $errors[] = 'La date de création doit être au format YYYY-MM-DD.';
                }
            }

            if ($formData['heure'] !== '') {
                $time = \DateTime::createFromFormat('H:i', $formData['heure']);
                if ($time instanceof \DateTimeInterface) {
                    $voyageEntity->setHeure($time);
                } else {
                    $errors[] = 'L\'heure doit être au format HH:MM.';
                }
            }

            if (count($errors) === 0) {
                $violations = $validator->validate($voyageEntity);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $errors[] = $violation->getMessage();
                    }
                }
            }

            if (count($errors) === 0) {
                $imageName = $voyage['image'];
                $imageFile = $request->files->get('image');

                if ($imageFile) {
                    if ($imageName && file_exists($this->getParameter('uploads_directory') . '/' . $imageName)) {
                        unlink($this->getParameter('uploads_directory') . '/' . $imageName);
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_directory'), $imageName);
                }

                $connection->executeStatement(
                    "UPDATE voyages SET nom = ?, description = ?, capacite = ?, prix = ?, dateCreation = ?, heure = ?, image = ? WHERE idV = ?",
                    [
                        $formData['nom'],
                        $formData['description'],
                        $voyageEntity->getCapacite(),
                        $voyageEntity->getPrix(),
                        $voyageEntity->getDateCreation()?->format('Y-m-d'),
                        $voyageEntity->getHeure()?->format('H:i:s'),
                        $imageName,
                        $id,
                    ]
                );

                $this->addFlash('success', 'Voyage modifié avec succès !');
                return $this->redirectToRoute('admin_voyage_index');
            }
        }

        return $this->render('admin/voyage/edit.html.twig', [
            'voyage' => $voyage,
            'errors' => $errors,
            'nom' => $formData['nom'],
            'description' => $formData['description'],
            'capacite' => $formData['capacite'],
            'prix' => $formData['prix'],
            'dateCreation' => $formData['dateCreation'],
            'heure' => $formData['heure'],
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_voyage_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete_voyage_' . $id, $request->request->get('_token'))) {
            $voyage = $connection->fetchAssociative("SELECT image FROM voyages WHERE idV = ?", [$id]);
            
            if ($voyage && $voyage['image'] && file_exists($this->getParameter('uploads_directory') . '/' . $voyage['image'])) {
                unlink($this->getParameter('uploads_directory') . '/' . $voyage['image']);
            }
            
            $connection->executeStatement("DELETE FROM voyages WHERE idV = ?", [$id]);
            $this->addFlash('success', 'Voyage supprimé avec succès !');
        }
        
        return $this->redirectToRoute('admin_voyage_index');
    }

    #[Route('/{id}/programmes', name: 'admin_voyage_programmes')]
    public function programmes(Connection $connection, int $id): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$id]);
        $programmes = $connection->fetchAllAssociative("SELECT * FROM programmes WHERE idV = ? ORDER BY dateDebut ASC", [$id]);
        
        return $this->render('admin/voyage/programmes.html.twig', [
            'voyage' => $voyage,
            'programmes' => $programmes,
        ]);
    }
}