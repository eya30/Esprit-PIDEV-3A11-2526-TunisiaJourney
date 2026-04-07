<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Dompdf\Dompdf;

#[Route('/admin/programmes')]
class AdminProgrammeController extends AbstractController
{
    #[Route('/', name: 'admin_programme_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $search = $request->query->get('search', '');
        
        $searchCondition = "";
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = " WHERE (p.nom LIKE :search OR p.lieu LIKE :search OR p.hotel LIKE :search OR v.nom LIKE :search) ";
            $params['search'] = "%$search%";
        }
        
        $programmes = $connection->fetchAllAssociative("
            SELECT p.*, v.nom as voyage_nom 
            FROM programmes p 
            LEFT JOIN voyages v ON p.idV = v.idV 
            $searchCondition
            ORDER BY p.dateDebut DESC
        ", $params);
        
        return $this->render('admin/programme/index.html.twig', [
            'programmes' => $programmes,
            'search' => $search,
            'total_count' => count($programmes),
        ]);
    }

    #[Route('/new', name: 'admin_programme_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection, ValidatorInterface $validator): Response
    {
        $voyages = $connection->fetchAllAssociative("SELECT idV, nom FROM voyages ORDER BY nom");
        $errors = [];
        $formData = [
            'nom' => '',
            'description' => '',
            'dateDebut' => '',
            'dateFin' => '',
            'lieu' => '',
            'activiteAssociee' => '',
            'hotel' => '',
            'idV' => '',
        ];

        if ($request->isMethod('POST')) {
            $formData['nom'] = trim((string) $request->request->get('nom', ''));
            $formData['description'] = trim((string) $request->request->get('description', ''));
            $formData['dateDebut'] = trim((string) $request->request->get('dateDebut', ''));
            $formData['dateFin'] = trim((string) $request->request->get('dateFin', ''));
            $formData['lieu'] = trim((string) $request->request->get('lieu', ''));
            $formData['activiteAssociee'] = trim((string) $request->request->get('activiteAssociee', ''));
            $formData['hotel'] = trim((string) $request->request->get('hotel', ''));
            $formData['idV'] = trim((string) $request->request->get('idV', ''));

            $programme = new \App\Entity\Programme();
            $programme->setNom($formData['nom']);
            $programme->setDescription($formData['description']);
            $programme->setLieu($formData['lieu']);
            $programme->setActiviteAssociee($formData['activiteAssociee']);
            $programme->setHotel($formData['hotel']);

            if ($formData['dateDebut'] !== '') {
                $dateDebut = \DateTime::createFromFormat('Y-m-d', $formData['dateDebut']);
                if ($dateDebut instanceof \DateTimeInterface) {
                    $programme->setDateDebut($dateDebut);
                } else {
                    $errors[] = 'La date de début doit être au format YYYY-MM-DD.';
                }
            }

            if ($formData['dateFin'] !== '') {
                $dateFin = \DateTime::createFromFormat('Y-m-d', $formData['dateFin']);
                if ($dateFin instanceof \DateTimeInterface) {
                    $programme->setDateFin($dateFin);
                } else {
                    $errors[] = 'La date de fin doit être au format YYYY-MM-DD.';
                }
            }

            if (count($errors) === 0 && isset($dateDebut, $dateFin) && $dateFin < $dateDebut) {
                $errors[] = 'La date de fin doit être postérieure ou égale à la date de début.';
            }

            if ($formData['idV'] === '') {
                $errors[] = 'Veuillez sélectionner un voyage associé.';
            } elseif (!is_numeric($formData['idV'])) {
                $errors[] = 'Le voyage sélectionné est invalide.';
            }

            if (count($errors) === 0) {
                $violations = $validator->validate($programme);
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
                    $imageFile->move($this->getParameter('uploads_directory') . '/programmes', $imageName);
                }

                $idProg = uniqid('PRG_');
                $connection->executeStatement(
                    "INSERT INTO programmes (idProg, nom, description, dateDebut, dateFin, lieu, activiteAssociee, hotel, image, idV) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$idProg, $formData['nom'], $formData['description'], $programme->getDateDebut()?->format('Y-m-d'), $programme->getDateFin()?->format('Y-m-d'), $formData['lieu'], $formData['activiteAssociee'], $formData['hotel'], $imageName, $formData['idV']]
                );

                $this->addFlash('success', 'Programme créé avec succès !');
                return $this->redirectToRoute('admin_programme_index');
            }
        }

        return $this->render('admin/programme/new.html.twig', [
            'voyages' => $voyages,
            'errors' => $errors,
            'nom' => $formData['nom'],
            'description' => $formData['description'],
            'dateDebut' => $formData['dateDebut'],
            'dateFin' => $formData['dateFin'],
            'lieu' => $formData['lieu'],
            'activiteAssociee' => $formData['activiteAssociee'],
            'hotel' => $formData['hotel'],
            'idV' => $formData['idV'],
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_programme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, ValidatorInterface $validator, string $id): Response
    {
        $programme = $connection->fetchAssociative("SELECT * FROM programmes WHERE idProg = ?", [$id]);
        $voyages = $connection->fetchAllAssociative("SELECT idV, nom FROM voyages ORDER BY nom");
        
        if (!$programme) {
            throw $this->createNotFoundException('Programme non trouvé');
        }

        $errors = [];
        $formData = [
            'nom' => $programme['nom'],
            'description' => $programme['description'],
            'dateDebut' => $programme['dateDebut'],
            'dateFin' => $programme['dateFin'],
            'lieu' => $programme['lieu'],
            'activiteAssociee' => $programme['activiteAssociee'],
            'hotel' => $programme['hotel'],
            'idV' => $programme['idV'],
        ];

        if ($request->isMethod('POST')) {
            $formData['nom'] = trim((string) $request->request->get('nom', ''));
            $formData['description'] = trim((string) $request->request->get('description', ''));
            $formData['dateDebut'] = trim((string) $request->request->get('dateDebut', ''));
            $formData['dateFin'] = trim((string) $request->request->get('dateFin', ''));
            $formData['lieu'] = trim((string) $request->request->get('lieu', ''));
            $formData['activiteAssociee'] = trim((string) $request->request->get('activiteAssociee', ''));
            $formData['hotel'] = trim((string) $request->request->get('hotel', ''));
            $formData['idV'] = trim((string) $request->request->get('idV', ''));

            $programmeEntity = new \App\Entity\Programme();
            $programmeEntity->setNom($formData['nom']);
            $programmeEntity->setDescription($formData['description']);
            $programmeEntity->setLieu($formData['lieu']);
            $programmeEntity->setActiviteAssociee($formData['activiteAssociee']);
            $programmeEntity->setHotel($formData['hotel']);

            if ($formData['dateDebut'] !== '') {
                $dateDebut = \DateTime::createFromFormat('Y-m-d', $formData['dateDebut']);
                if ($dateDebut instanceof \DateTimeInterface) {
                    $programmeEntity->setDateDebut($dateDebut);
                } else {
                    $errors[] = 'La date de début doit être au format YYYY-MM-DD.';
                }
            }

            if ($formData['dateFin'] !== '') {
                $dateFin = \DateTime::createFromFormat('Y-m-d', $formData['dateFin']);
                if ($dateFin instanceof \DateTimeInterface) {
                    $programmeEntity->setDateFin($dateFin);
                } else {
                    $errors[] = 'La date de fin doit être au format YYYY-MM-DD.';
                }
            }

            if (count($errors) === 0 && isset($dateDebut, $dateFin) && $dateFin < $dateDebut) {
                $errors[] = 'La date de fin doit être postérieure ou égale à la date de début.';
            }

            if ($formData['idV'] === '') {
                $errors[] = 'Veuillez sélectionner un voyage associé.';
            } elseif (!is_numeric($formData['idV'])) {
                $errors[] = 'Le voyage sélectionné est invalide.';
            }

            if (count($errors) === 0) {
                $violations = $validator->validate($programmeEntity);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $errors[] = $violation->getMessage();
                    }
                }
            }

            if (count($errors) === 0) {
                $imageName = $programme['image'];
                $imageFile = $request->files->get('image');

                if ($imageFile) {
                    if ($imageName && file_exists($this->getParameter('uploads_directory') . '/programmes/' . $imageName)) {
                        unlink($this->getParameter('uploads_directory') . '/programmes/' . $imageName);
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                    $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('uploads_directory') . '/programmes', $imageName);
                }

                $connection->executeStatement(
                    "UPDATE programmes SET nom = ?, description = ?, dateDebut = ?, dateFin = ?, lieu = ?, activiteAssociee = ?, hotel = ?, image = ?, idV = ? WHERE idProg = ?",
                    [
                        $formData['nom'],
                        $formData['description'],
                        $programmeEntity->getDateDebut()?->format('Y-m-d'),
                        $programmeEntity->getDateFin()?->format('Y-m-d'),
                        $formData['lieu'],
                        $formData['activiteAssociee'],
                        $formData['hotel'],
                        $imageName,
                        $formData['idV'],
                        $id,
                    ]
                );

                $this->addFlash('success', 'Programme modifié avec succès !');
                return $this->redirectToRoute('admin_programme_index');
            }
        }

        return $this->render('admin/programme/edit.html.twig', [
            'programme' => $programme,
            'voyages' => $voyages,
            'errors' => $errors,
            'nom' => $formData['nom'],
            'description' => $formData['description'],
            'dateDebut' => $formData['dateDebut'],
            'dateFin' => $formData['dateFin'],
            'lieu' => $formData['lieu'],
            'activiteAssociee' => $formData['activiteAssociee'],
            'hotel' => $formData['hotel'],
            'idV' => $formData['idV'],
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_programme_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, string $id): Response
    {
        if ($this->isCsrfTokenValid('delete_programme_' . $id, $request->request->get('_token'))) {
            $programme = $connection->fetchAssociative("SELECT image FROM programmes WHERE idProg = ?", [$id]);
            
            if ($programme && $programme['image'] && file_exists($this->getParameter('uploads_directory') . '/programmes/' . $programme['image'])) {
                unlink($this->getParameter('uploads_directory') . '/programmes/' . $programme['image']);
            }
            
            $connection->executeStatement("DELETE FROM programmes WHERE idProg = ?", [$id]);
            $this->addFlash('success', 'Programme supprimé avec succès !');
        }
        
        return $this->redirectToRoute('admin_programme_index');
    }

    #[Route('/{id}/reservations', name: 'admin_programme_reservations')]
    public function reservations(Connection $connection, Request $request, string $id): Response
    {
        $programme = $connection->fetchAssociative("SELECT * FROM programmes WHERE idProg = ?", [$id]);
        
        $sort = $request->query->get('sort', 'idRP');
        $allowedSorts = ['idRP', 'nom', 'prenom', 'email', 'telephone', 'nbre', 'prixProg', 'dateProgramme', 'statutPaiement'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'idRP';
        }
        
        $reservations = $connection->fetchAllAssociative("SELECT * FROM reservationprog WHERE idP = ? ORDER BY $sort ASC", [$id]);
        
        return $this->render('admin/programme/reservations.html.twig', [
            'programme' => $programme,
            'reservations' => $reservations,
        ]);
    }

    #[Route('/{id}/reservations/pdf', name: 'admin_programme_reservations_pdf')]
    public function reservationsPdf(Connection $connection, string $id): Response
    {
        $programme = $connection->fetchAssociative("SELECT * FROM programmes WHERE idProg = ?", [$id]);
        $reservations = $connection->fetchAllAssociative("SELECT * FROM reservationprog WHERE idP = ? ORDER BY nom ASC", [$id]);

        // Générer le HTML pour le PDF
        $html = $this->renderView('admin/programme/reservations_pdf.html.twig', [
            'programme' => $programme,
            'reservations' => $reservations,
        ]);

        // Créer le PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Retourner le PDF
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reservations_' . $programme['nom'] . '.pdf"'
            ]
        );
    }
}