<?php
namespace App\Controller\Api;

use App\Service\CurrencyConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/currency')]
class CurrencyController extends AbstractController
{
    #[Route('/convert', name: 'api_currency_convert', methods: ['GET', 'POST'])]
    public function convert(Request $request, CurrencyConverterService $converter): JsonResponse
    {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $amount = $data['amount'] ?? null;
            $from = strtoupper($data['from'] ?? 'EUR');
            $to = strtoupper($data['to'] ?? 'TND');
        } else {
<<<<<<< HEAD
    $amount = $request->query->get('amount');
    $from = strtoupper($request->query->getString('from', 'EUR'));
    $to = strtoupper($request->query->getString('to', 'TND'));
=======
            $amount = $request->query->get('amount');
            $from = strtoupper($request->query->get('from', 'EUR'));
            $to = strtoupper($request->query->get('to', 'TND'));
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        }
        
        if (!$amount || !is_numeric($amount)) {
            return $this->json(['error' => 'Montant requis'], 400);
        }
        
        try {
            $convertedAmount = $converter->convertToTND((float) $amount, $from);
            $rate = $converter->getExchangeRate($from, $to);
            
            return $this->json([
                'success' => true,
                'from' => [
                    'currency' => $from,
                    'amount' => (float) $amount,
                    'formatted' => $converter->formatAmount((float) $amount, $from),
                ],
                'to' => [
                    'currency' => $to,
                    'amount' => $convertedAmount,
                    'formatted' => $converter->formatAmount($convertedAmount, $to),
                ],
                'rate' => $rate,
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/rates', name: 'api_currency_rates', methods: ['GET'])]
    public function getRates(CurrencyConverterService $converter): JsonResponse
    {
        $currencies = ['EUR', 'USD', 'GBP'];
        $rates = [];
        
        foreach ($currencies as $currency) {
            $rates[$currency] = [
                'to_tnd' => $converter->getExchangeRate($currency, 'TND'),
                'from_tnd' => $converter->getExchangeRate('TND', $currency),
            ];
        }
        
        return $this->json([
            'success' => true,
            'base' => 'TND',
            'rates' => $rates,
            'available_currencies' => $converter->getAvailableCurrencies(),
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
    
    #[Route('/currencies', name: 'api_currency_currencies', methods: ['GET'])]
    public function getCurrencies(CurrencyConverterService $converter): JsonResponse
    {
        return $this->json([
            'success' => true,
            'currencies' => $converter->getAvailableCurrencies(),
        ]);
    }
}