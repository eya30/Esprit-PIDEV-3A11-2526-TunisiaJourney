<?php

namespace App\Controller;

use App\Service\FideliteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;
use Psr\Log\LoggerInterface;

class FideliteController extends AbstractController
{
    #[Route('/api/fidelite', name: 'api_fidelite')]
    #[IsGranted('ROLE_USER')]
    public function getFidelite(FideliteService $fideliteService, LoggerInterface $logger): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            // Vérification simple de l'existence de l'utilisateur
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non connecté'], 401);
            }
            
            // Récupération de l'ID utilisateur de manière sécurisée
            $idUtilisateur = null;
            
            if (method_exists($user, 'getId')) {
                $id = $user->getId();
                $idUtilisateur = is_numeric($id) ? (int)$id : null;
            } elseif (method_exists($user, 'getIdUser')) {
                $id = $user->getIdUser();
                $idUtilisateur = is_numeric($id) ? (int)$id : null;
            }
            
            if (!$idUtilisateur || $idUtilisateur <= 0) {
                $logger->warning('ID utilisateur invalide ou non trouvé');
                return $this->json(['error' => 'ID utilisateur invalide'], 400);
            }
            
            $infos = $fideliteService->getInfosFidelite($idUtilisateur);
            
            // Pas besoin de vérification car le service retourne toujours un tableau valide
            // avec toutes les clés nécessaires d'après le PHPDoc
            
            return $this->json([
                'success' => true,
                'data' => $infos
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $logger->error('Erreur fidelité: ' . $e->getMessage());
            return $this->json(['error' => 'Une erreur interne est survenue'], 500);
        }
    }
}