<?php

namespace App\Controller;

use App\Entity\AvisChambre;
use App\Entity\User;
use App\Form\AvisChambreType;
use App\Service\SentimentAnalysisService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/hotel')]
class HotelController extends AbstractController
{
    #[Route('/', name: 'app_hotel_index')]
    public function index(Connection $connection): Response
    {
        $hotels = $connection->fetchAllAssociative("SELECT * FROM hotel ORDER BY idH DESC");
        
        return $this->render('hotel/index.html.twig', [
            'hotels' => $hotels,
        ]);
    }
    
    #[Route('/{idH}', name: 'app_hotel_show')]
    public function show(
        int $idH, 
        Request $request, 
        Connection $connection, 
        EntityManagerInterface $em,
        SentimentAnalysisService $sentimentAnalysis
    ): Response {
        $hotel = $connection->fetchAssociative("SELECT * FROM hotel WHERE idH = ?", [$idH]);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé');
        }
        
        $chambres = $connection->fetchAllAssociative("SELECT * FROM chambre WHERE idH = ? ORDER BY idCh ASC", [$idH]);
        
        $user = $this->getUser();
        
        // ========== TRAITEMENT DU FORMULAIRE D'AVIS ==========
        if ($request->isMethod('POST') && $request->get('avis')) {
            
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour donner un avis.');
                return $this->redirectToRoute('app_login');
            }
            
            $data = $request->get('avis');
            
            // Vérification des notes obligatoires
            $requiredNotes = ['noteConfort', 'noteServices', 'noteEquipements', 'noteProprete', 
                              'notePersonnel', 'noteEmplacement', 'noteRestauration', 'notePrixQualite', 'noteCalme'];
            
            $missing = false;
            foreach ($requiredNotes as $note) {
                if (!isset($data[$note]) || empty($data[$note])) {
                    $missing = true;
                    break;
                }
            }
            
            if ($missing) {
                $this->addFlash('error', 'Veuillez donner une note pour chaque critère (1 à 5 étoiles).');
                return $this->redirectToRoute('app_hotel_show', ['idH' => $idH]);
            }
            
            // ========== ANALYSE DE SENTIMENT ==========
            $commentaire = $data['commentaire'] ?? '';
            $sentiment = 'neutre';
            
            if (!empty($commentaire)) {
                try {
                    $analysis = $sentimentAnalysis->analyze($commentaire);
                    $sentiment = $analysis['sentiment']; // 'positif', 'negatif' ou 'neutre'
                } catch (\Exception $e) {
                    $sentiment = 'neutre';
                }
            }
            
            // Création de l'avis
            $avis = new AvisChambre();
<<<<<<< HEAD

            // FIX :89 — getUser() retourne UserInterface, mais setUtilisateur() attend App\Entity\User|null.
            // On vérifie que c'est bien une instance de User avant de l'assigner.
            if (!$user instanceof User) {
                $this->addFlash('error', 'Utilisateur invalide.');
                return $this->redirectToRoute('app_hotel_show', ['idH' => $idH]);
            }
            $avis->setUtilisateur($user);

=======
            $avis->setUtilisateur($user);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $avis->setDateCreation(new \DateTime());
            $avis->setNoteConfort((int)$data['noteConfort']);
            $avis->setNoteServices((int)$data['noteServices']);
            $avis->setNoteEquipements((int)$data['noteEquipements']);
            $avis->setNoteProprete((int)$data['noteProprete']);
            $avis->setNotePersonnel((int)$data['notePersonnel']);
            $avis->setNoteEmplacement((int)$data['noteEmplacement']);
            $avis->setNoteRestauration((int)$data['noteRestauration']);
            $avis->setNotePrixQualite((int)$data['notePrixQualite']);
            $avis->setNoteCalme((int)$data['noteCalme']);
            $avis->setCommentaire($commentaire);
            $avis->setSentiment($sentiment);
            $avis->setEstPublie(true);
            
            // Si le sentiment est négatif, la notification est non lue
            if ($sentiment === 'negatif') {
                $avis->setStatutNotification('non_lue');
            }
            
            $em->persist($avis);
            $em->flush();
            
            $this->addFlash('success', '🎉 Merci pour votre avis !');
            return $this->redirectToRoute('app_hotel_show', ['idH' => $idH]);
        }
        
        // ========== RÉCUPÉRATION DES AVIS ==========
        $avis = $connection->fetchAllAssociative(
            "SELECT a.*, u.prenom, u.nom, u.id as utilisateur_id
             FROM avis_chambre a
             JOIN utilisateur u ON a.utilisateur_id = u.id
             WHERE a.est_publie = 1
             ORDER BY a.date_creation DESC"
        );
        
        return $this->render('hotel/show.html.twig', [
            'hotel' => $hotel,
            'chambres' => $chambres,
            'avis' => $avis
        ]);
    }
    
    // ========== ROUTE POUR LES CHAMBRES ==========
    #[Route('/{idH}/chambres', name: 'app_hotel_chambres')]
    public function chambres(Connection $connection, int $idH): Response
    {
        $hotel = $connection->fetchAssociative("SELECT * FROM hotel WHERE idH = ?", [$idH]);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé');
        }
        
        $chambres = $connection->fetchAllAssociative("SELECT * FROM chambre WHERE idH = ? ORDER BY idCh ASC", [$idH]);
        
        return $this->render('hotel/chambres.html.twig', [
            'hotel' => $hotel,
            'chambres' => $chambres,
        ]);
    }
}