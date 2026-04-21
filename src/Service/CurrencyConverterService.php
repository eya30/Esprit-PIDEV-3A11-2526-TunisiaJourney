<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CurrencyConverterService
{
    private $httpClient;
    private $logger;
    private $cache = [];
    
    // Taux de conversion par défaut (fallback si API indisponible)
    private const DEFAULT_RATES = [
        'EUR' => 3.30,  // 1 EUR = 3.30 TND
        'USD' => 3.05,  // 1 USD = 3.05 TND
        'GBP' => 3.85,  // 1 GBP = 3.85 TND
    ];
    
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }
    
    /**
     * Convertir un montant vers TND
     */
    public function convertToTND(float $amount, string $fromCurrency): float
    {
        $rate = $this->getExchangeRate($fromCurrency, 'TND');
        return round($amount * $rate, 3);
    }
    
    /**
     * Convertir un montant depuis TND vers une devise
     */
    public function convertFromTND(float $amount, string $toCurrency): float
    {
        $rate = $this->getExchangeRate('TND', $toCurrency);
        return round($amount * $rate, 3);
    }
    
    /**
     * Obtenir le taux de change entre deux devises
     */
    public function getExchangeRate(string $from, string $to): float
    {
        $cacheKey = $from . '_' . $to;
        
        // Vérifier le cache
        if (isset($this->cache[$cacheKey]) && isset($this->cache[$cacheKey]['expires']) && $this->cache[$cacheKey]['expires'] > time()) {
            return $this->cache[$cacheKey]['rate'];
        }
        
        // Essayer d'abord l'API gratuite
        $rate = $this->fetchExchangeRateFromAPI($from, $to);
        
        if ($rate === null) {
            // Fallback vers taux par défaut
            $rate = $this->getFallbackRate($from, $to);
        }
        
        // Mettre en cache pour 1 heure
        $this->cache[$cacheKey] = [
            'rate' => $rate,
            'expires' => time() + 3600
        ];
        
        return $rate;
    }
    
    /**
     * Récupérer le taux depuis une API externe
     */
    private function fetchExchangeRateFromAPI(string $from, string $to): ?float
    {
        // API gratuite : ExchangeRate-API (pas besoin de clé pour usage limité)
        $apis = [
            $this->fetchFromExchangeRateAPI($from, $to),
            $this->fetchFromFrankfurterAPI($from, $to),
        ];
        
        foreach ($apis as $rate) {
            if ($rate !== null) {
                return $rate;
            }
        }
        
        return null;
    }
    
    /**
     * API ExchangeRate-API (gratuit, 1500 requêtes/mois sans clé)
     */
    private function fetchFromExchangeRateAPI(string $from, string $to): ?float
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.exchangerate-api.com/v4/latest/' . $from, [
                'timeout' => 5,
            ]);
            
            $data = $response->toArray();
            
            if (isset($data['rates'][$to])) {
                $this->logger->info("Taux de change récupéré depuis ExchangeRate-API: 1 {$from} = {$data['rates'][$to]} {$to}");
                return (float) $data['rates'][$to];
            }
            
        } catch (\Exception $e) {
            $this->logger->warning("Erreur ExchangeRate-API: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * API Frankfurter (gratuit, sans clé)
     */
    private function fetchFromFrankfurterAPI(string $from, string $to): ?float
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.frankfurter.app/latest', [
                'query' => [
                    'from' => $from,
                    'to' => $to,
                ],
                'timeout' => 5,
            ]);
            
            $data = $response->toArray();
            
            if (isset($data['rates'][$to])) {
                $this->logger->info("Taux de change récupéré depuis Frankfurter: 1 {$from} = {$data['rates'][$to]} {$to}");
                return (float) $data['rates'][$to];
            }
            
        } catch (\Exception $e) {
            $this->logger->warning("Erreur Frankfurter API: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Taux de fallback si API indisponible
     */
    private function getFallbackRate(string $from, string $to): float
    {
        // Si c'est vers TND
        if ($to === 'TND') {
            return self::DEFAULT_RATES[$from] ?? 1;
        }
        
        // Si c'est depuis TND
        if ($from === 'TND') {
            return 1 / (self::DEFAULT_RATES[$to] ?? 1);
        }
        
        // Conversion croisée
        $rateToTND = self::DEFAULT_RATES[$from] ?? 1;
        $rateFromTND = 1 / (self::DEFAULT_RATES[$to] ?? 1);
        return $rateToTND * $rateFromTND;
    }
    
    /**
     * Obtenir toutes les devises disponibles
     */
    public function getAvailableCurrencies(): array
    {
        return [
            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'flag' => '🇪🇺'],
            'USD' => ['name' => 'Dollar US', 'symbol' => '$', 'flag' => '🇺🇸'],
            'GBP' => ['name' => 'Livre Sterling', 'symbol' => '£', 'flag' => '🇬🇧'],
            'TND' => ['name' => 'Dinar Tunisien', 'symbol' => 'DT', 'flag' => '🇹🇳'],
        ];
    }
    
    /**
     * Formater un montant dans une devise
     */
    public function formatAmount(float $amount, string $currency): string
    {
        $currencies = $this->getAvailableCurrencies();
        $symbol = $currencies[$currency]['symbol'] ?? $currency;
        
        switch ($currency) {
            case 'TND':
                return number_format($amount, 3, ',', ' ') . ' DT';
            case 'EUR':
                return $symbol . ' ' . number_format($amount, 2, ',', ' ');
            case 'USD':
                return $symbol . ' ' . number_format($amount, 2, ',', ' ');
            default:
                return number_format($amount, 2, ',', ' ') . ' ' . $currency;
        }
    }
}