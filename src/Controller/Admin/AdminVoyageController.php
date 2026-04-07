<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/voyages')]
class AdminVoyageController extends AbstractController
{
    const ITEMS_PER_PAGE = 10;
    
    #[Route('/', name: 'admin_voyage_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        
        // Compter le nombre total de voyages
        $totalVoyages = $connection->fetchOne("SELECT COUNT(*) FROM voyages");
        $totalPages = max(1, ceil($totalVoyages / self::ITEMS_PER_PAGE));
        
        // Récupérer les voyages paginés - Correction du type des paramètres
        $voyages = $connection->fetchAllAssociative(
            "SELECT * FROM voyages ORDER BY idV DESC LIMIT " . (int)self::ITEMS_PER_PAGE . " OFFSET " . (int)$offset,
            []
        );
        
        return $this->render('admin/voyage/index.html.twig', [
            'voyages' => $voyages,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'admin_voyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $capacite = $request->request->get('capacite');
            $prix = $request->request->get('prix');
            $dateCreation = $request->request->get('dateCreation');
            $heure = $request->request->get('heure');
            
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
                [$nom, $description, $capacite, $prix, $dateCreation, $heure, $imageName, 1]
            );
            
            $this->addFlash('success', 'Voyage créé avec succès !');
            return $this->redirectToRoute('admin_voyage_index');
        }
        
        return $this->render('admin/voyage/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_voyage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, int $id): Response
    {
        $voyage = $connection->fetchAssociative("SELECT * FROM voyages WHERE idV = ?", [$id]);
        
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage non trouvé');
        }
        
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $description = $request->request->get('description');
            $capacite = $request->request->get('capacite');
            $prix = $request->request->get('prix');
            $dateCreation = $request->request->get('dateCreation');
            $heure = $request->request->get('heure');
            
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
                [$nom, $description, $capacite, $prix, $dateCreation, $heure, $imageName, $id]
            );
            
            $this->addFlash('success', 'Voyage modifié avec succès !');
            return $this->redirectToRoute('admin_voyage_index');
        }
        
        return $this->render('admin/voyage/edit.html.twig', [
            'voyage' => $voyage,
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