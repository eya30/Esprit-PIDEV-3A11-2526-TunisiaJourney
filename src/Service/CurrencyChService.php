<?php

namespace App\Service;

class CurrencyChService
{
    /** @var array<string, float> */
    private array $rates = [];
   
    public function __construct()
    {
        $this->loadRates();
    }
   
    private function loadRates(): void
    {
        $this->rates = [
            'TND' => 1.0,
            'EUR' => 0.30,
            'USD' => 0.32,
            'GBP' => 0.26,
        ];
       
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
            }
        } catch (\Exception $e) {
            // Garder les taux par défaut
        }
    }
   
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
    {
        $symbols = [
            'TND' => 'DT',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];
       
        return $symbols[$currency] ?? $currency;
    }
   
    /**
     * @return array<string, string>
     */
    public function getAvailableCurrencies(): array
    {
        return [
            'TND' => 'Dinar Tunisien (DT)',
            'EUR' => 'Euro (€)',
            'USD' => 'Dollar US ($)',
            'GBP' => 'Livre Sterling (£)',
        ];
    }
}