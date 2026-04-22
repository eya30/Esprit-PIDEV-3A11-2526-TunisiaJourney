<?php

namespace App\Service;

class CurrencyChService
{
    private $rates = [];
    
    public function __construct()
    {
        $this->loadRates();
    }
    
    private function loadRates()
    {
        $this->rates = [
            'TND' => 1,
            'EUR' => 0.30,
            'USD' => 0.32,
            'GBP' => 0.26,
        ];
        
        try {
            $response = file_get_contents("https://api.exchangerate.host/latest?base=TND&symbols=EUR,USD,GBP");
            $data = json_decode($response, true);
            
            if (isset($data['rates'])) {
                $this->rates['EUR'] = $data['rates']['EUR'];
                $this->rates['USD'] = $data['rates']['USD'];
                $this->rates['GBP'] = $data['rates']['GBP'];
            }
        } catch (\Exception $e) {
            // Garder les taux par défaut
        }
    }
    
    public function convert($amountTND, $currency)
    {
        if ($currency === 'TND') {
            return round($amountTND, 2);
        }
        
        $rate = $this->rates[$currency] ?? 1;
        return round($amountTND * $rate, 2);
    }
    
    public function getSymbol($currency)
    {
        $symbols = [
            'TND' => 'DT',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];
        
        return $symbols[$currency] ?? $currency;
    }
    
    public function getAvailableCurrencies()
    {
        return [
            'TND' => 'Dinar Tunisien (DT)',
            'EUR' => 'Euro (€)',
            'USD' => 'Dollar US ($)',
            'GBP' => 'Livre Sterling (£)',
        ];
    }
}