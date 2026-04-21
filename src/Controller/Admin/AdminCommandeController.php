<?php
namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/commande')]
class AdminCommandeController extends AbstractController
{
    // ── Liste des commandes ──────────────────────────────────────────────
    #[Route('/', name: 'admin_commande_index')]
    public function index(CommandeRepository $repo, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $repo->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/commande/index.html.twig', [
            'commandes' => $pagination,
        ]);
    }

    // ── Supprimer une commande ───────────────────────────────────────────
    #[Route('/{id}/supprimer', name: 'admin_commande_delete', methods: ['POST'])]
    public function delete(Commande $commande, EntityManagerInterface $em): Response
    {
        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Commande #' . $commande->getId() . ' supprimée.');
        return $this->redirectToRoute('admin_commande_index');
    }

    // ── Export PDF ───────────────────────────────────────────────────────
    #[Route('/export/pdf', name: 'admin_commande_export_pdf')]
    public function exportPdf(CommandeRepository $repo): Response
    {
        $commandes = $repo->findBy([], ['id' => 'DESC']);

        $caTotal = array_reduce($commandes, fn($c, $cmd) => $c + ($cmd->getTotal() ?? 0), 0);

        $html = $this->renderView('admin/commande/pdf.html.twig', [
            'commandes' => $commandes,
            'caTotal'   => $caTotal,
            'date'      => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'commandes_' . date('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    // ── Modifier une commande ────────────────────────────────────────────
    #[Route('/{id}/modifier', name: 'admin_commande_edit', methods: ['GET', 'POST'])]
    public function edit(
        Commande               $commande,
        Request                $request,
        EntityManagerInterface $em,
        WhatsAppService        $whatsApp   // ✅ AJOUT
    ): Response {
        if ($request->isMethod('POST')) {

            // ✅ Sauvegarder l'ancien statut AVANT le flush
            $ancienStatut = $commande->getStatut();

            $ancienneQte  = $commande->getQuantite() ?: 1;
            $nouvelleQte  = (int) $request->request->get('quantite', $ancienneQte);
            $prixUnitaire = $commande->getTotal() / $ancienneQte;

            $commande->setQuantite($nouvelleQte);
            $commande->setTotal($prixUnitaire * $nouvelleQte);
            $commande->setStatut($request->request->get('statut'));
            $commande->setAdresseLiv($request->request->get('adresse'));

            $em->flush();

            // ✅ Envoyer WhatsApp si le statut VIENT DE PASSER à "Livrée"
            $nouveauStatut = $commande->getStatut();
            if ($nouveauStatut === 'Livrée' && $ancienStatut !== 'Livrée') {
                $user = $commande->getUser();
                if ($user && $user->getTelephone()) {
                    $whatsApp->sendLivreurArrive(
                        $user->getTelephone(),
                        $user->getNom(),
                        $user->getPrenom(),
                        $commande->getId()
                    );
                }
            }

            $this->addFlash('success', 'Commande #' . $commande->getId() . ' mise à jour.');
            return $this->redirectToRoute('admin_commande_index');
        }

        return $this->render('admin/commande/form.html.twig', [
            'commande' => $commande,
            'action'   => 'Modifier',
        ]);
    }
}