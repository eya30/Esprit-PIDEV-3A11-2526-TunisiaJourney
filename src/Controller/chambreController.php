<?php

namespace App\Controller;

use App\Entity\Chambre;
use App\Entity\Hotel;
use App\Form\ChambreFormType;
use App\Repository\ChambreRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chambre')]
class chambreController extends AbstractController
{
    #[Route('/', name: 'app_chambre_index', methods: ['GET'])]
    public function index(ChambreRepository $chambreRepository): Response
    {
        $chambres = $chambreRepository->findAll();
        
        return $this->render('chambre/index.html.twig', [
            'chambres' => $chambres,
        ]);
    }

    #[Route('/new', name: 'app_chambre_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $chambre = new Chambre();
        $form = $this->createForm(ChambreFormType::class, $chambre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('uploads_chambres_directory'),
                    $newFilename
                );
                $chambre->setImage($newFilename);
            }
            
            $entityManager->persist($chambre);
            $entityManager->flush();

            $this->addFlash('success', 'Chambre créée avec succès !');
            return $this->redirectToRoute('app_chambre_index');
        }

        return $this->render('chambre/new.html.twig', [
            'chambre' => $chambre,
            'form' => $form,
        ]);
    }

    // MODIFICATION ICI - Ajout des variables pour la devise
    #[Route('/{idCh}', name: 'app_chambre_show', methods: ['GET'])]
    public function show(Connection $connection, $idCh): Response
    {
        // Requête SQL directe pour récupérer la chambre avec les infos de l'hôtel
        $sql = "SELECT c.*, 
                       h.idH, 
                       h.nom as hotel_nom, 
                       h.etoiles as hotel_etoiles, 
                       h.ville as hotel_ville, 
                       h.adresse as hotel_adresse,
                       h.image as hotel_image
                FROM chambre c 
                JOIN hotel h ON c.idH = h.idH 
                WHERE c.idCh = ?";
        
        $chambre = $connection->fetchAssociative($sql, [$idCh]);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre non trouvée');
        }
        
        // ========== VARIABLES POUR LA DEVISE ==========
        $currencies = [
            'TND' => 'Dinar Tunisien (TND)',
            'EUR' => 'Euro (€)',
            'USD' => 'Dollar US ($)',
        ];
        $selected_currency = 'TND';
        $prix_converti = $chambre['prix_nuit'];
        $symbole = 'TND';
        
        return $this->render('chambre/show.html.twig', [
            'chambre' => $chambre,
            'old' => [],
            'currencies' => $currencies,
            'selected_currency' => $selected_currency,
            'prix_converti' => $prix_converti,
            'symbole' => $symbole,
        ]);
    }

    #[Route('/{idCh}/with-data', name: 'app_chambre_show_with_data', methods: ['GET'])]
    public function showWithData(Connection $connection, $idCh, Request $request): Response
    {
        // Même requête pour récupérer la chambre
        $sql = "SELECT c.*, 
                       h.idH, 
                       h.nom as hotel_nom, 
                       h.etoiles as hotel_etoiles, 
                       h.ville as hotel_ville, 
                       h.adresse as hotel_adresse,
                       h.image as hotel_image
                FROM chambre c 
                JOIN hotel h ON c.idH = h.idH 
                WHERE c.idCh = ?";
        
        $chambre = $connection->fetchAssociative($sql, [$idCh]);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre non trouvée');
        }
        
        $oldData = [
            'idUtilisateur' => $request->query->get('idUtilisateur', ''),
            'dateDebut' => $request->query->get('dateDebut', ''),
            'dateFin' => $request->query->get('dateFin', ''),
            'nbPersonnes' => $request->query->get('nbPersonnes', '1'),
            'telephone' => $request->query->get('telephone', '')
        ];
        
        // ========== VARIABLES POUR LA DEVISE ==========
        $currencies = [
            'TND' => 'Dinar Tunisien (TND)',
            'EUR' => 'Euro (€)',
            'USD' => 'Dollar US ($)',
        ];
        $selected_currency = 'TND';
        $prix_converti = $chambre['prix_nuit'];
        $symbole = 'TND';
        
        return $this->render('chambre/show.html.twig', [
            'chambre' => $chambre,
            'old' => $oldData,
            'currencies' => $currencies,
            'selected_currency' => $selected_currency,
            'prix_converti' => $prix_converti,
            'symbole' => $symbole,
        ]);
    }

    #[Route('/{idCh}/edit', name: 'app_chambre_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Chambre $chambre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChambreFormType::class, $chambre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('uploads_chambres_directory'),
                    $newFilename
                );
                $chambre->setImage($newFilename);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Chambre modifiée avec succès !');
            return $this->redirectToRoute('app_chambre_index');
        }

        return $this->render('chambre/edit.html.twig', [
            'chambre' => $chambre,
            'form' => $form,
        ]);
    }

    #[Route('/{idCh}', name: 'app_chambre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Chambre $chambre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $chambre->getIdCh(), $request->request->get('_token'))) {
            $entityManager->remove($chambre);
            $entityManager->flush();
            $this->addFlash('success', 'Chambre supprimée avec succès !');
        }

        return $this->redirectToRoute('app_chambre_index');
    }
    
    #[Route('/hotel/{idH}', name: 'app_chambre_by_hotel', methods: ['GET'])]
    public function chambresByHotel(Hotel $hotel, ChambreRepository $chambreRepository): Response
    {
        $chambres = $chambreRepository->findBy(['hotel' => $hotel]);
        
        return $this->render('chambre/by_hotel.html.twig', [
            'hotel' => $hotel,
            'chambres' => $chambres,
        ]);
    }
}