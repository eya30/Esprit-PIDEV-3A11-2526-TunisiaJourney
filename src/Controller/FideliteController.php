<?php

namespace App\Controller;

use App\Service\FideliteService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FideliteController extends AbstractController
{
    #[Route('/api/fidelite', name: 'api_fidelite')]
    public function getFidelite(FideliteService $fideliteService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non connecté']);
        }
        
        $idUtilisateur = $user->getId();
        $infos = $fideliteService->getInfosFidelite($idUtilisateur);
        
        return $this->json($infos);
    }
}