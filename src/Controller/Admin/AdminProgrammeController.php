<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/programmes')]
class AdminProgrammeController extends AbstractController
{
    #[Route('/', name: 'admin_programme_index')]
    public function index(Connection $connection): Response
    {
        $programmes = $connection->fetchAllAssociative("
            SELECT p.*, v.nom as voyage_nom 
            FROM programmes p 
            LEFT JOIN voyages v ON p.idV = v.idV 
            ORDER BY p.dateDebut DESC
        ");
        
        return $this->render('admin/programme/index.html.twig', [
            'programmes' => $programmes,
        ]);
    }

    #[Route('/new', name: 'admin_programme_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection): Response
    {
        $voyages = $connection->fetchAllAssociative("SELECT idV, nom FROM voyages ORDER BY nom");
        
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $dateDebut = $request->request->get('dateDebut');
            $dateFin = $request->request->get('dateFin');
            $lieu = $request->request->get('lieu');
            $activiteAssociee = $request->request->get('activiteAssociee');
            $hotel = $request->request->get('hotel');
            $idV = $request->request->get('idV');
            
            $imageFile = $request->files->get('image');
            $imageName = null;
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_programmes_directory'), $imageName);
            }
            
            $idProg = uniqid('PRG_');
            $connection->executeStatement(
                "INSERT INTO programmes (idProg, nom, description, dateDebut, dateFin, lieu, activiteAssociee, hotel, image, idV) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$idProg, $nom, $description, $dateDebut, $dateFin, $lieu, $activiteAssociee, $hotel, $imageName, $idV]
            );
            
            $this->addFlash('success', 'Programme créé avec succès !');
            return $this->redirectToRoute('admin_programme_index');
        }
        
        return $this->render('admin/programme/new.html.twig', [
            'voyages' => $voyages,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_programme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, string $id): Response
    {
        $programme = $connection->fetchAssociative("SELECT * FROM programmes WHERE idProg = ?", [$id]);
        $voyages = $connection->fetchAllAssociative("SELECT idV, nom FROM voyages ORDER BY nom");
        
        if (!$programme) {
            throw $this->createNotFoundException('Programme non trouvé');
        }
        
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $dateDebut = $request->request->get('dateDebut');
            $dateFin = $request->request->get('dateFin');
            $lieu = $request->request->get('lieu');
            $activiteAssociee = $request->request->get('activiteAssociee');
            $hotel = $request->request->get('hotel');
            $idV = $request->request->get('idV');
            
            $imageName = $programme['image'];
            $imageFile = $request->files->get('image');
            
            if ($imageFile) {
                if ($imageName && file_exists($this->getParameter('uploads_programmes_directory') . '/' . $imageName)) {
                    unlink($this->getParameter('uploads_programmes_directory') . '/' . $imageName);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $originalFilename);
                $imageName = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_programmes_directory'), $imageName);
            }
            
            $connection->executeStatement(
                "UPDATE programmes SET nom = ?, description = ?, dateDebut = ?, dateFin = ?, lieu = ?, activiteAssociee = ?, hotel = ?, image = ?, idV = ? WHERE idProg = ?",
                [$nom, $description, $dateDebut, $dateFin, $lieu, $activiteAssociee, $hotel, $imageName, $idV, $id]
            );
            
            $this->addFlash('success', 'Programme modifié avec succès !');
            return $this->redirectToRoute('admin_programme_index');
        }
        
        return $this->render('admin/programme/edit.html.twig', [
            'programme' => $programme,
            'voyages' => $voyages,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_programme_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, string $id): Response
    {
        if ($this->isCsrfTokenValid('delete_programme_' . $id, $request->request->get('_token'))) {
            $programme = $connection->fetchAssociative("SELECT image FROM programmes WHERE idProg = ?", [$id]);
            
            if ($programme && $programme['image'] && file_exists($this->getParameter('uploads_programmes_directory') . '/' . $programme['image'])) {
                unlink($this->getParameter('uploads_programmes_directory') . '/' . $programme['image']);
            }
            
            $connection->executeStatement("DELETE FROM programmes WHERE idProg = ?", [$id]);
            $this->addFlash('success', 'Programme supprimé avec succès !');
        }
        
        return $this->redirectToRoute('admin_programme_index');
    }

    #[Route('/{id}/reservations', name: 'admin_programme_reservations')]
    public function reservations(Connection $connection, string $id): Response
    {
        $programme = $connection->fetchAssociative("SELECT * FROM programmes WHERE idProg = ?", [$id]);
        $reservations = $connection->fetchAllAssociative("SELECT * FROM reservationprog WHERE idP = ? ORDER BY idRP DESC", [$id]);
        
        return $this->render('admin/programme/reservations.html.twig', [
            'programme' => $programme,
            'reservations' => $reservations,
        ]);
    }
}