<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Repository\CommandeProduitRepository;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/commande')]
class AdminCommandeController extends AbstractController
{
    #[Route('/', name: 'admin_commande_index')]
    public function index(CommandeRepository $repo, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $repo->createQueryBuilder('c')->orderBy('c.id', 'DESC')->getQuery();
        $pagination = $paginator->paginate($query, $request->query->getInt('page', 1), 10);
        return $this->render('admin/commande/index.html.twig', ['commandes' => $pagination]);
    }

    // ✅ Modifier statut + envoi WhatsApp automatique
    #[Route('/{id}/modifier', name: 'admin_commande_edit', methods: ['GET', 'POST'])]
    public function edit(
        Commande $commande,
        Request $request,
        EntityManagerInterface $em,
        CommandeProduitRepository $cpRepo,
        WhatsAppService $wa
    ): Response {
        $lignes = $cpRepo->findBy(['commande' => $commande, 'isPanier' => false]);

        if ($request->isMethod('POST')) {
            $ancienStatut = $commande->getStatut();
            $newTotal = 0; $newQte = 0;

            foreach ($lignes as $ligne) {
                $pid  = $ligne->getProduit()->getIdPR();
                $nQte = (int) $request->request->get('quantite_' . $pid, $ligne->getQuantite());

                if ($nQte <= 0) {
                    $em->remove($ligne);
                } else {
                    $diff = $nQte - $ligne->getQuantite();
                    $produit = $ligne->getProduit();
                    if ($diff > 0 && ($produit->getStock() ?? 0) < $diff) {
                        $this->addFlash('warning', 'Stock insuffisant pour « ' . $produit->getTitre() . ' ».');
                        $nQte = $ligne->getQuantite() + ($produit->getStock() ?? 0);
                        $diff = $nQte - $ligne->getQuantite();
                    }
                    $produit->setStock(max(0, ($produit->getStock() ?? 0) - $diff));
                    $produit->setDisponibilite($produit->getStock() > 0);
                    $ligne->setQuantite($nQte);
                    $newTotal += $produit->getPrix() * $nQte;
                    $newQte   += $nQte;
                }
            }

            $nouveauStatut = $request->request->get('statut', $commande->getStatut());
            $commande->setStatut($nouveauStatut);
            $commande->setAdresseLiv($request->request->get('adresse', $commande->getAdresseLiv()));
            $commande->setCodePostal($request->request->get('cp', $commande->getCodePostal()));
            $commande->setModePaiement($request->request->get('paiement', $commande->getModePaiement()));
            $commande->setTotal($newTotal);
            $commande->setQuantite($newQte);
            $em->flush();

            // ✅ WhatsApp si statut changé
            if ($ancienStatut !== $nouveauStatut) {
                $user      = $commande->getUser();
                $telephone = $user?->getTelephone() ?? null;

                if ($telephone) {
                    $nomClient = ($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? '');
                    $nomClient = trim($nomClient) ?: 'Client';

                    $result = match($nouveauStatut) {
                        'Livrée'   => $wa->notifierLivraison($telephone, $nomClient, $commande->getId(), $commande->getAdresseLiv() ?? ''),
                        'En cours' => $wa->notifierEnRoute($telephone, $nomClient, $commande->getId()),
                        default    => $this->notifierGenerique($wa, $telephone, $nomClient, $commande->getId(), $nouveauStatut),
                    };

                    $this->addFlash(
                        $result['success'] ? 'success' : 'warning',
                        $result['success']
                            ? "✅ Commande mise à jour + WhatsApp envoyé au {$telephone}"
                            : "⚠️ Commande mise à jour mais WhatsApp échoué : " . ($result['error'] ?? '?')
                    );
                } else {
                    $this->addFlash('info', "✅ Commande #{$commande->getId()} mise à jour. (Pas de téléphone enregistré)");
                }
            } else {
                $this->addFlash('success', "✅ Commande #{$commande->getId()} mise à jour.");
            }

            return $this->redirectToRoute('admin_commande_index');
        }

        return $this->render('admin/commande/form.html.twig', [
            'commande' => $commande, 'lignes' => $lignes, 'action' => 'Modifier',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_commande_delete', methods: ['POST'])]
    public function delete(Commande $commande, EntityManagerInterface $em): Response
    {
        foreach ($commande->getLignes() as $ligne) {
            $p = $ligne->getProduit();
            $p->setStock(($p->getStock() ?? 0) + $ligne->getQuantite());
            if ($p->getStock() > 0) $p->setDisponibilite(true);
        }
        $em->remove($commande); $em->flush();
        $this->addFlash('success', "Commande #{$commande->getId()} supprimée. Stock restauré.");
        return $this->redirectToRoute('admin_commande_index');
    }

    #[Route('/export/pdf', name: 'admin_commande_export_pdf')]
    public function exportPdf(CommandeRepository $repo): Response
    {
        $commandes = $repo->findBy([], ['id' => 'DESC']);
        $caTotal   = array_reduce($commandes, fn($c, $cmd) => $c + ($cmd->getTotal() ?? 0), 0);
        $html = $this->renderView('admin/commande/pdf.html.twig', ['commandes' => $commandes, 'caTotal' => $caTotal, 'date' => new \DateTime()]);
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="commandes_' . date('Y-m-d') . '.pdf"',
        ]);
    }

    private function notifierGenerique(WhatsAppService $wa, string $tel, string $nom, int $id, string $statut): array
    {
        $icons = ['Confirmée' => '✅', 'Expédiée' => '📦', 'Annulée' => '❌'];
        $icon  = $icons[$statut] ?? '🔔';
        $msg   = "{$icon} *TunisiaJourney — Mise à jour commande*\n\nBonjour *{$nom}*,\n\nVotre commande *#{$id}* est maintenant : *{$statut}*.\n\nConnectez-vous sur TunisiaJourney pour plus de détails.\n— L'équipe TunisiaJourney";
        return $wa->sendWhatsApp($tel, $msg);
    }
}