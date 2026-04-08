<?php
namespace App\Controller\Admin;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/commande')]
class AdminCommandeController extends AbstractController
{
    // ── Liste des commandes ──────────────────────────────────────────────
    #[Route('/', name: 'admin_commande_index')]
    public function index(CommandeRepository $repo): Response
    {
        return $this->render('admin/commande/index.html.twig', [
            'commandes' => $repo->findBy([], ['id' => 'DESC']),
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

        // Calculer le CA total
        $caTotal = array_reduce($commandes, fn($c, $cmd) => $c + ($cmd->getTotal() ?? 0), 0);

        // ── Générer le HTML du PDF ───────────────────────────────────────
        $html = $this->renderView('admin/commande/pdf.html.twig', [
            'commandes' => $commandes,
            'caTotal'   => $caTotal,
            'date'      => new \DateTime(),
        ]);

        // ── Configurer Dompdf ────────────────────────────────────────────
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // ── Retourner le PDF en téléchargement ───────────────────────────
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
    // ── Modifier une commande ───────────────────────────────────────────
#[Route('/{id}/modifier', name: 'admin_commande_edit', methods: ['GET', 'POST'])]
public function edit(Commande $commande, Request $request, EntityManagerInterface $em): Response
{
    // Si le formulaire est soumis (cas du formulaire HTML que tu as fourni)
    if ($request->isMethod('POST')) {
        $commande->setQuantite((int)$request->request->get('quantite'));
        $commande->setTotal((float)$request->request->get('total'));
        $commande->setStatut($request->request->get('statut'));
        $commande->setAdresseLiv($request->request->get('adresse'));
        $commande->setCodePostal($request->request->get('cp'));
        $commande->setModePaiement($request->request->get('paiement'));

        $em->flush();
        $this->addFlash('success', 'La commande #' . $commande->getId() . ' a été mise à jour.');
        return $this->redirectToRoute('admin_commande_index');
    }

    return $this->render('admin/commande/form.html.twig', [
        'commande' => $commande,
        'action'   => 'Modifier'
    ]);
}
}
