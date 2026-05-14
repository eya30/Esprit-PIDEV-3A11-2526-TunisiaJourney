<?php

namespace App\Service;

class CurrencyChService
{
<<<<<<< HEAD
    /** @var array<string, float> */
    private array $rates = [];
   
=======
    private $rates = [];
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    public function __construct()
    {
        $this->loadRates();
    }
<<<<<<< HEAD
   
    private function loadRates(): void
    {
        $this->rates = [
            'TND' => 1.0,
=======
    
    private function loadRates()
    {
        $this->rates = [
            'TND' => 1,
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            'EUR' => 0.30,
            'USD' => 0.32,
            'GBP' => 0.26,
        ];
<<<<<<< HEAD
       
        try {
            $response = @file_get_contents("https://api.exchangerate.host/latest?base=TND&symbols=EUR,USD,GBP");
           
            // ✅ Correction : suppression de is_string() car $response est déjà un string
            if ($response !== false) {
                $data = json_decode($response, true);
               
                if (isset($data['rates'])) {
                    $this->rates['EUR'] = (float)$data['rates']['EUR'];
                    $this->rates['USD'] = (float)$data['rates']['USD'];
                    $this->rates['GBP'] = (float)$data['rates']['GBP'];
                }
=======
        
        try {
            $response = file_get_contents("https://api.exchangerate.host/latest?base=TND&symbols=EUR,USD,GBP");
            $data = json_decode($response, true);
            
            if (isset($data['rates'])) {
                $this->rates['EUR'] = $data['rates']['EUR'];
                $this->rates['USD'] = $data['rates']['USD'];
                $this->rates['GBP'] = $data['rates']['GBP'];
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            }
        } catch (\Exception $e) {
            // Garder les taux par défaut
        }
    }
<<<<<<< HEAD
   
    /**
     * @param int|float $amountTND
     */
    public function convert(int|float $amountTND, string $currency): float
    {
        if ($currency === 'TND') {
            return round((float)$amountTND, 2);
        }
       
        $rate = $this->rates[$currency] ?? 1.0;
        return round((float)$amountTND * $rate, 2);
    }
   
    public function getSymbol(string $currency): string
=======
    
    public function convert($amountTND, $currency)
    {
        if ($currency === 'TND') {
            return round($amountTND, 2);
        }
        
        $rate = $this->rates[$currency] ?? 1;
        return round($amountTND * $rate, 2);
    }
    
    public function getSymbol($currency)
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    {
        $symbols = [
            'TND' => 'DT',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];
<<<<<<< HEAD
       
        return $symbols[$currency] ?? $currency;
    }
   
    /**
     * @return array<string, string>
     */
    public function getAvailableCurrencies(): array
=======
        
        return $symbols[$currency] ?? $currency;
    }
    
    public function getAvailableCurrencies()
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    {
        return [
            'TND' => 'Dinar Tunisien (DT)',
            'EUR' => 'Euro (€)',
            'USD' => 'Dollar US ($)',
            'GBP' => 'Livre Sterling (£)',
        ];
    }
}