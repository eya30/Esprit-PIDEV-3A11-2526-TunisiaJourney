<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CurrencyConverterService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    /** @var array<string, array{rate: float, expires: int}> */
    private array $cache = [];

    private const DEFAULT_RATES = [
        'EUR' => 3.30,
        'USD' => 3.05,
        'GBP' => 3.85,
    ];

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    public function convertToTND(float $amount, string $fromCurrency): float
    {
        $rate = $this->getExchangeRate($fromCurrency, 'TND');
        return round($amount * $rate, 3);
    }

    public function convertFromTND(float $amount, string $toCurrency): float
    {
        $rate = $this->getExchangeRate('TND', $toCurrency);
        return round($amount * $rate, 3);
    }

    public function getExchangeRate(string $from, string $to): float
    {
        $cacheKey = $from . '_' . $to;

        if (isset($this->cache[$cacheKey]) && $this->cache[$cacheKey]['expires'] > time()) {
            return $this->cache[$cacheKey]['rate'];
        }

        $rate = $this->fetchExchangeRateFromAPI($from, $to) ?? $this->getFallbackRate($from, $to);

        $this->cache[$cacheKey] = [
            'rate'    => $rate,
            'expires' => time() + 3600,
        ];

        return $rate;
    }

    private function fetchExchangeRateFromAPI(string $from, string $to): ?float
    {
        $rates = [
            $this->fetchFromExchangeRateAPI($from, $to),
            $this->fetchFromFrankfurterAPI($from, $to),
        ];

        foreach ($rates as $rate) {
            if ($rate !== null) {
                return $rate;
            }
        }

        return null;
    }

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

    private function fetchFromFrankfurterAPI(string $from, string $to): ?float
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.frankfurter.app/latest', [
                'query'   => ['from' => $from, 'to' => $to],
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

    private function getFallbackRate(string $from, string $to): float
    {
        if ($to === 'TND') {
            return self::DEFAULT_RATES[$from] ?? 1;
        }

        if ($from === 'TND') {
            return 1 / (self::DEFAULT_RATES[$to] ?? 1);
        }

        $rateToTND   = self::DEFAULT_RATES[$from] ?? 1;
        $rateFromTND = 1 / (self::DEFAULT_RATES[$to] ?? 1);

        return $rateToTND * $rateFromTND;
    }

    /**
     * @return array<string, array{name: string, symbol: string, flag: string}>
     */
    public function getAvailableCurrencies(): array
    {
        return [
            'EUR' => ['name' => 'Euro',            'symbol' => '€',  'flag' => '🇪🇺'],
            'USD' => ['name' => 'Dollar US',        'symbol' => '$',  'flag' => '🇺🇸'],
            'GBP' => ['name' => 'Livre Sterling',   'symbol' => '£',  'flag' => '🇬🇧'],
            'TND' => ['name' => 'Dinar Tunisien',   'symbol' => 'DT', 'flag' => '🇹🇳'],
        ];
    }

    public function formatAmount(float $amount, string $currency): string
    {
        $currencies = $this->getAvailableCurrencies();
        $symbol     = $currencies[$currency]['symbol'] ?? $currency;

        return match ($currency) {
            'TND'   => number_format($amount, 3, ',', ' ') . ' DT',
            'EUR',
            'USD'   => $symbol . ' ' . number_format($amount, 2, ',', ' '),
            default => number_format($amount, 2, ',', ' ') . ' ' . $currency,
        };
    }
}