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

<<<<<<< HEAD
=======
    // ✅ Modifier statut + envoi WhatsApp automatique
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
            $newTotal = 0;
            $newQte = 0;

            foreach ($lignes as $ligne) {
                // FIX: null-check on getProduit() before calling methods on it
                $produit = $ligne->getProduit();
                if ($produit === null) {
                    continue;
                }

                $pid  = $produit->getIdPR();
=======
            $newTotal = 0; $newQte = 0;

            foreach ($lignes as $ligne) {
                $pid  = $ligne->getProduit()->getIdPR();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
                $nQte = (int) $request->request->get('quantite_' . $pid, $ligne->getQuantite());

                if ($nQte <= 0) {
                    $em->remove($ligne);
                } else {
                    $diff = $nQte - $ligne->getQuantite();
<<<<<<< HEAD
=======
                    $produit = $ligne->getProduit();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
            // FIX: cast request values to string to satisfy setStatut/setAdresseLiv/setCodePostal/setModePaiement type expectations
            $nouveauStatut = (string) $request->request->get('statut', $commande->getStatut());
            $commande->setStatut($nouveauStatut);
            $commande->setAdresseLiv((string) $request->request->get('adresse', $commande->getAdresseLiv()));
            $commande->setCodePostal((string) $request->request->get('cp', $commande->getCodePostal()));
            $commande->setModePaiement((string) $request->request->get('paiement', $commande->getModePaiement()));
=======
            $nouveauStatut = $request->request->get('statut', $commande->getStatut());
            $commande->setStatut($nouveauStatut);
            $commande->setAdresseLiv($request->request->get('adresse', $commande->getAdresseLiv()));
            $commande->setCodePostal($request->request->get('cp', $commande->getCodePostal()));
            $commande->setModePaiement($request->request->get('paiement', $commande->getModePaiement()));
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $commande->setTotal($newTotal);
            $commande->setQuantite($newQte);
            $em->flush();

<<<<<<< HEAD
=======
            // ✅ WhatsApp si statut changé
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            if ($ancienStatut !== $nouveauStatut) {
                $user      = $commande->getUser();
                $telephone = $user?->getTelephone() ?? null;

                if ($telephone) {
<<<<<<< HEAD
                    $nomClient = trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? '')) ?: 'Client';

                    // FIX: cast getId() to int to guarantee non-null int for WhatsApp methods
                    $commandeId = (int) $commande->getId();

                    $result = match($nouveauStatut) {
                        'Livrée'   => $wa->notifierLivraison($telephone, $nomClient, $commandeId, $commande->getAdresseLiv() ?? ''),
                        'En cours' => $wa->notifierEnRoute($telephone, $nomClient, $commandeId),
                        default    => $this->notifierGenerique($wa, $telephone, $nomClient, $commandeId, $nouveauStatut),
=======
                    $nomClient = ($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? '');
                    $nomClient = trim($nomClient) ?: 'Client';

                    $result = match($nouveauStatut) {
                        'Livrée'   => $wa->notifierLivraison($telephone, $nomClient, $commande->getId(), $commande->getAdresseLiv() ?? ''),
                        'En cours' => $wa->notifierEnRoute($telephone, $nomClient, $commande->getId()),
                        default    => $this->notifierGenerique($wa, $telephone, $nomClient, $commande->getId(), $nouveauStatut),
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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
<<<<<<< HEAD
            // FIX: null-check on getProduit() before calling methods on it
            $p = $ligne->getProduit();
            if ($p === null) {
                continue;
            }
            $p->setStock(($p->getStock() ?? 0) + $ligne->getQuantite());
            if ($p->getStock() > 0) {
                $p->setDisponibilite(true);
            }
        }
        $em->remove($commande);
        $em->flush();
=======
            $p = $ligne->getProduit();
            $p->setStock(($p->getStock() ?? 0) + $ligne->getQuantite());
            if ($p->getStock() > 0) $p->setDisponibilite(true);
        }
        $em->remove($commande); $em->flush();
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $this->addFlash('success', "Commande #{$commande->getId()} supprimée. Stock restauré.");
        return $this->redirectToRoute('admin_commande_index');
    }

    #[Route('/export/pdf', name: 'admin_commande_export_pdf')]
    public function exportPdf(CommandeRepository $repo): Response
    {
        $commandes = $repo->findBy([], ['id' => 'DESC']);
        $caTotal   = array_reduce($commandes, fn($c, $cmd) => $c + ($cmd->getTotal() ?? 0), 0);
<<<<<<< HEAD
        $html = $this->renderView('admin/commande/pdf.html.twig', [
            'commandes' => $commandes,
            'caTotal'   => $caTotal,
            'date'      => new \DateTime(),
        ]);
=======
        $html = $this->renderView('admin/commande/pdf.html.twig', ['commandes' => $commandes, 'caTotal' => $caTotal, 'date' => new \DateTime()]);
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
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

<<<<<<< HEAD
    /**
     * @return array<string, mixed>
     */
=======
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    private function notifierGenerique(WhatsAppService $wa, string $tel, string $nom, int $id, string $statut): array
    {
        $icons = ['Confirmée' => '✅', 'Expédiée' => '📦', 'Annulée' => '❌'];
        $icon  = $icons[$statut] ?? '🔔';
        $msg   = "{$icon} *TunisiaJourney — Mise à jour commande*\n\nBonjour *{$nom}*,\n\nVotre commande *#{$id}* est maintenant : *{$statut}*.\n\nConnectez-vous sur TunisiaJourney pour plus de détails.\n— L'équipe TunisiaJourney";
        return $wa->sendWhatsApp($tel, $msg);
    }
}