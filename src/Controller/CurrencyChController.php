<?php
// src/Controller/CurrencyChController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyChController extends AbstractController
{
    #[Route('/change-currency-ch', name: 'change_currency_ch', methods: ['POST'])]
    public function changeCurrency(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $currency = $data['currency'] ?? 'TND';
        
        $session = $request->getSession();
        $session->set('selected_currency_ch', $currency);
        
        return $this->json(['success' => true, 'currency' => $currency]);
    }
}