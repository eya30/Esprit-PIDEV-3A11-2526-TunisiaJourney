<?php

namespace App\Controller;

use App\Repository\ForumRepository;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        ForumRepository $forumRepo,
        PublicationRepository $publicationRepo,
        CommentaireRepository $commentaireRepo
    ): Response {
        $forums        = $forumRepo->findAll();
        $publications  = $publicationRepo->findAll();
        $commentaires  = $commentaireRepo->findAll();

        $totalForums        = count($forums);
        $totalPublications  = count($publications);
        $totalCommentaires  = count($commentaires);

        $avgPublicationsParForum = $totalForums > 0 ? round($totalPublications / $totalForums, 1) : 0;
        $avgCommentairesParForum = $totalForums > 0 ? round($totalCommentaires / $totalForums, 1) : 0;

        // Forum le plus actif
        $forumPlusActif = null;
        $maxActivite    = 0;
        foreach ($forums as $forum) {
            $nbPublications = count($forum->getPublications());
            $nbCommentaires = 0;
            foreach ($forum->getPublications() as $pub) {
                $nbCommentaires += count($pub->getCommentaires());
            }
            $totalActivite = $nbPublications + $nbCommentaires;
            if ($totalActivite > $maxActivite) {
                $maxActivite    = $totalActivite;
                $forumPlusActif = [
                    'nom'          => $forum->getNom(),
                    'publications' => $nbPublications,
                    'commentaires' => $nbCommentaires,
                ];
            }
        }

        // Top 5 forums par publications (trié, utilisé aussi pour le graphique)
        $topForumsPublications = [];
        foreach ($forums as $forum) {
            $topForumsPublications[] = [
                'nom'   => $forum->getNom(),
                'count' => count($forum->getPublications()),
            ];
        }
        usort($topForumsPublications, fn($a, $b) => $b['count'] <=> $a['count']);
        $topForumsPublications = array_slice($topForumsPublications, 0, 5);

        // Top 10 publications les plus commentées
        $topPublications = [];
        foreach ($publications as $pub) {
            $topPublications[] = [
                'id'           => $pub->getIdP(),
                'titre'        => $pub->getNom(),
                'commentaires' => count($pub->getCommentaires()),
                'forum'        => $pub->getForum()->getNom(),
                'date'         => $pub->getDateCreation(),
            ];
        }
        usort($topPublications, fn($a, $b) => $b['commentaires'] <=> $a['commentaires']);
        $topPublications = array_slice($topPublications, 0, 10);

        $evolution = $this->getEvolutionData($publications);

        $evolutionForums       = ['trend' => 'up', 'percentage' => 12];
        $evolutionPublications = ['trend' => 'up', 'percentage' => 23];
        $evolutionCommentaires = ['trend' => 'up', 'percentage' => 34];

        $dernieres_publications = $publicationRepo->findBy([], ['dateCreation' => 'DESC'], 5);
        $derniers_commentaires  = $commentaireRepo->findBy([], ['dateCreation' => 'DESC'], 5);

        $evolution_week  = [4, 6, 8, 5, 7, 9, 12];
        $evolution_month = [15, 18, 22, 25, 28, 30, 35, 38, 42, 45, 48, 52, 55, 58, 62, 65, 68, 72, 75, 78, 82, 85, 88, 92, 95, 98, 102, 105, 108, 112];
        $evolution_year  = [45, 52, 68, 85, 102, 125, 148, 172, 195, 218, 245, 278];

        return $this->render('admin/dashboard/index.html.twig', [
            'forums'                  => $forums,
            'publications'            => $publications,
            'commentaires'            => $commentaires,
            'totalForums'             => $totalForums,
            'totalPublications'       => $totalPublications,
            'totalCommentaires'       => $totalCommentaires,
            'avgPublicationsParForum' => $avgPublicationsParForum,
            'avgCommentairesParForum' => $avgCommentairesParForum,
            'forumPlusActif'          => $forumPlusActif,
            'repartition'             => [
                'publications' => $totalPublications,
                'commentaires' => $totalCommentaires,
            ],
            'evolution'               => $evolution,
            'topForumsPublications'   => $topForumsPublications,
            'topPublications'         => $topPublications,
            'evolutionForums'         => $evolutionForums,
            'evolutionPublications'   => $evolutionPublications,
            'evolutionCommentaires'   => $evolutionCommentaires,
            'dernieres_publications'  => $dernieres_publications,
            'derniers_commentaires'   => $derniers_commentaires,
            'evolution_week'          => $evolution_week,
            'evolution_month'         => $evolution_month,
            'evolution_year'          => $evolution_year,
            'avgParForum'             => $avgPublicationsParForum,
        ]);
    }

    private function getEvolutionData($publications): array
    {
        $evolution   = [];
        $date30Jours = new \DateTime('-30 days');

        for ($i = 29; $i >= 0; $i--) {
            $date = new \DateTime('-' . $i . ' days');
            $evolution[$date->format('Y-m-d')] = 0;
        }

        foreach ($publications as $pub) {
            $dateCreation = $pub->getDateCreation();
            if ($dateCreation >= $date30Jours) {
                $dateKey = $dateCreation->format('Y-m-d');
                if (isset($evolution[$dateKey])) {
                    $evolution[$dateKey]++;
                }
            }
        }

        return $evolution;
    }
}