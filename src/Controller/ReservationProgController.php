<?php
 
namespace App\Controller;
 
use App\Entity\ReservationProg;
use App\Entity\Programme;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
 
#[Route('/reservation')]
class ReservationProgController extends AbstractController
{
    #[Route('/new/{idProg}', name: 'app_reservation_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ?string $idProg = null
    ): Response {
 
        // Récupérer le programme
        $programme = $entityManager->getRepository(Programme::class)->find($idProg);
 
        if (!$programme) {
            $this->addFlash('error', 'Programme non trouvé');
            return $this->redirectToRoute('app_voyage_index');
        }
 
        // Récupérer les données du formulaire
        $nom      = $request->request->get('nom', '');
        $prenom   = $request->request->get('prenom', '');
        $telephone = $request->request->get('telephone', '');
        $email    = $request->request->get('email', '');
        $nbre     = $request->request->get('nbre', '');
 
        // Créer la réservation avec les données brutes
        $reservation = new ReservationProg();
        $reservation->setNom($nom);
        $reservation->setPrenom($prenom);
        $reservation->setTelephone($telephone);
        $reservation->setEmail($email);
 
        // nbre : on passe 0 si vide pour que NotBlank/Positive remonte l'erreur
        $reservation->setNbre(is_numeric($nbre) ? (int)$nbre : 0);
 
        $reservation->setIdP($programme->getIdProg());
        $reservation->setDateProgramme(new \DateTime());
        $reservation->setStatutPaiement('en_attente');
 
        // Validation Symfony (annotations dans l'entité)
        $errors = $validator->validate($reservation);
 
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('app_programme_show', [
                'idProg' => $programme->getIdProg()
            ]);
        }
 
        // Lier l'utilisateur connecté
        $user = $this->getUser();
        if ($user) {
            if (method_exists($user, 'getId')) {
                $reservation->setUserId($user->getId());
            } else {
                $userRepo  = $entityManager->getRepository(\App\Entity\User::class);
                $realUser  = $userRepo->findOneBy(['email' => $user->getUserIdentifier()]);
                if ($realUser) {
                    $reservation->setUserId($realUser->getId());
                }
            }
        }
 
        // Calculer le prix total
        $prixTotal = $programme->getVoyage()->getPrix() * (int)$nbre;
        $reservation->setPrixProg((float)$prixTotal);
 
        // Sauvegarder
        try {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', '✅ Réservation confirmée !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }
 
        return $this->redirectToRoute('app_programme_show', [
            'idProg' => $programme->getIdProg()
        ]);
    }
}