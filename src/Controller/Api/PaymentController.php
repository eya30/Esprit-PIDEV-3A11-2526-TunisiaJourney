<?php

namespace App\Controller\Api;

use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Repository\CommandeProduitRepository;
use App\Service\StripeeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    // ── Créer une session Stripe ──────────────────────────────────────────────
    #[Route('/paiement/creer-session', name: 'app_payment_create_session', methods: ['POST'])]
    public function createCheckoutSession(
        Request $request,
        StripeeService $stripeeService,
        CommandeProduitRepository $cpRepo
    ): JsonResponse {
        try {
            if (!$this->getUser()) {
                return $this->json(['error' => 'Non connecté'], 401);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data || empty($data['items'])) {
                return $this->json(['error' => 'Panier vide'], 400);
            }

            // ✅ Construire les items
            $items = [];
            foreach ($data['items'] as $item) {
                if (empty($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                    continue;
                }
                $items[] = [
                    'name'        => $item['name'],
                    'price'       => floatval($item['price']),
                    'quantity'    => intval($item['quantity']),
                    'description' => $item['description'] ?? '',
                ];
            }

            if (empty($items)) {
                return $this->json(['error' => 'Aucun article valide'], 400);
            }

            // ✅ Stocker adresse/CP en session pour les récupérer après paiement
            $session = $request->getSession();
            $session->set('stripe_adresse',   $data['adresse']    ?? '');
            $session->set('stripe_codePostal', $data['codePostal'] ?? '');

            // URLs de retour absolues
            $successUrl = $this->generateUrl(
                'app_payment_success',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $cancelUrl = $this->generateUrl(
                'app_payment_cancel',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $stripeSession = $stripeeService->createCheckoutSession($items, $successUrl, $cancelUrl);

            return $this->json(['url' => $stripeSession->url]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Erreur spécifique Stripe
            return $this->json([
                'error'   => 'Erreur Stripe : ' . $e->getMessage(),
                'details' => $e->getStripeCode(),
            ], 500);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Paiement réussi ───────────────────────────────────────────────────────
    #[Route('/paiement/succes', name: 'app_payment_success')]
    public function paymentSuccess(
        Request $request,
        CommandeProduitRepository $cpRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $session = $request->getSession();

        $adresse    = $session->get('stripe_adresse', '');
        $codePostal = $session->get('stripe_codePostal', '');

        // ✅ Créer la commande après paiement réussi
        $items = $cpRepo->findPanierByUser($user);

        if (!empty($items)) {
            $total    = $cpRepo->getTotalPanier($user);
            $commande = new Commande();
            $commande->setDateC(new \DateTime());
            $commande->setStatut('Confirmée'); // ✅ Déjà payée
            $commande->setTotal($total);
            $commande->setQuantite(array_sum(array_map(fn($i) => $i->getQuantite(), $items)));
            $commande->setUser($user);
            $commande->setAdresseLiv($adresse ?: 'Non précisée');
            $commande->setCodePostal($codePostal ?: '0000');
            $commande->setModePaiement('Carte bancaire (Stripe)');

            $em->persist($commande);
            $em->flush();

<<<<<<< HEAD
           foreach ($items as $item) {
    $produit = $item->getProduit();
    if ($produit !== null) {
        $produit->decrementStock($item->getQuantite());
    }
    $item->setCommande($commande);
    $item->setIsPanier(false);
    $item->setUser(null);
}
=======
            foreach ($items as $item) {
                $item->getProduit()->decrementStock($item->getQuantite());
                $item->setCommande($commande);
                $item->setIsPanier(false);
                $item->setUser(null);
            }
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb

            $em->flush();

            // Vider la session
            $session->remove('stripe_adresse');
            $session->remove('stripe_codePostal');
        }

        $this->addFlash('success', '✅ Paiement réussi ! Votre commande est confirmée.');
        return $this->redirectToRoute('app_commande_index');
    }

    // ── Paiement annulé ───────────────────────────────────────────────────────
    #[Route('/paiement/annule', name: 'app_payment_cancel')]
    public function paymentCancel(): Response
    {
        $this->addFlash('warning', '❌ Paiement annulé. Votre panier est conservé.');
        return $this->redirectToRoute('app_panier_index');
    }
}