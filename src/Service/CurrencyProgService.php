<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CurrencyProgService
{
    /**
     * Taux de change (chargés depuis l'API ou fallback)
     *
     * @var array<string, float>
     */
    private array $exchangeRates = [];
    
    /**
     * Taux de change de secours (si l'API est indisponible)
     *
     * @var array<string, float>
     */
    private array $fallbackRates = [
        'TND' => 1,
        'EUR' => 0.30,
        'USD' => 0.33,
        'GBP' => 0.26,
        'CAD' => 0.45,
        'CHF' => 0.29,
        'DZD' => 44.50,
        'LYD' => 1.60,
        'MAD' => 3.25,
        'SAR' => 1.24,
        'AED' => 1.21,
        'QAR' => 1.20,
        'KWD' => 0.10,
        'BHD' => 0.12,
        'OMR' => 0.13,
        'JOD' => 0.23,
        'CNY' => 2.40,
        'JPY' => 50.00,
        'TRY' => 10.70,
        'RUB' => 30.00,
        'INR' => 27.50,
        'AUD' => 0.50,
        'NZD' => 0.54,
        'SEK' => 3.50,
        'NOK' => 3.55,
        'DKK' => 2.25,
        'PLN' => 1.32,
        'CZK' => 7.60,
        'HUF' => 119.00,
    ];

    private ?HttpClientInterface $httpClient;
    private ?LoggerInterface $logger;
    private bool $useLiveRates = true;
    private ?string $lastUpdate = null;

    public function __construct(
        ?HttpClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        
        $this->loadExchangeRates();
    }

    /**
     * Charge les taux de change (depuis l'API ou fallback)
     */
    private function loadExchangeRates(): void
    {
        if ($this->useLiveRates && $this->httpClient !== null) {
            $this->loadLiveExchangeRates();
        } else {
            $this->exchangeRates = $this->fallbackRates;
            if ($this->logger) {
                $this->logger->info('Utilisation des taux de change de secours');
            }
        }
    }

    /**
     * Charge les taux de change en direct depuis l'API Frankfurter (gratuit, sans clé)
     */
    private function loadLiveExchangeRates(): void
    {
        // FIX :92 — guard clause : on ne peut arriver ici que si httpClient est non-null,
        // mais PHPStan ne le sait pas sans cette vérification explicite.
        if ($this->httpClient === null) {
            $this->exchangeRates = $this->fallbackRates;
            return;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.frankfurter.app/latest?from=TND', [
                'timeout' => 5,
            ]);
            
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                if (isset($data['rates']) && is_array($data['rates'])) {
                    $this->exchangeRates = $data['rates'];
                    $this->exchangeRates['TND'] = 1;
                    $this->lastUpdate = $data['date'] ?? date('Y-m-d H:i:s');
                    
                    if ($this->logger) {
                        $this->logger->info('Taux de change chargés avec succès', [
                            'date'       => $this->lastUpdate,
                            'currencies' => array_keys($this->exchangeRates),
                        ]);
                    }
                    return;
                }
            }
            
            $this->exchangeRates = $this->fallbackRates;
            if ($this->logger) {
                $this->logger->warning('Impossible de charger les taux live depuis l\'API, utilisation des taux de secours');
            }
            
        } catch (\Exception $e) {
            $this->exchangeRates = $this->fallbackRates;
            if ($this->logger) {
                $this->logger->error('Erreur lors du chargement des taux: ' . $e->getMessage());
            }
        }
    }

    /**
     * Rafraîchit les taux de change
     *
     * @return bool Succès du rafraîchissement
     */
    public function refreshExchangeRates(): bool
    {
        if ($this->httpClient !== null) {
            $this->loadLiveExchangeRates();
            return $this->useLiveRates && !empty($this->exchangeRates);
        }
        return false;
    }

    /**
     * Obtient le taux de change entre deux devises
     *
     * @param string $from Devise source
     * @param string $to Devise cible
     * @return float Taux de change
     */
    public function getExchangeRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        if ($from === $to) {
            return 1;
        }

        if (empty($this->exchangeRates)) {
            $this->exchangeRates = $this->fallbackRates;
        }

        $rateFrom = $this->exchangeRates[$from] ?? 1;
        $rateTo   = $this->exchangeRates[$to] ?? 1;
        
        return $rateTo / $rateFrom;
    }

    /**
     * Convertit un montant d'une devise à une autre
     *
     * @param float $amount Montant à convertir
     * @param string $from Devise source
     * @param string $to Devise cible
     * @return float Montant converti
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rate = $this->getExchangeRate($from, $to);
        return round($amount * $rate, 2);
    }

    /**
     * Convertit un montant en TND vers toutes les devises disponibles
     *
     * @param float $amountTND Montant en TND
     * @return array<string, array{amount: float, formatted: string, rate: float}>
     */
    public function convertToAllCurrencies(float $amountTND): array
    {
        $currencies = $this->getAvailableCurrencies();
        $results    = [];
        
        foreach (array_keys($currencies) as $currency) {
            if ($currency !== 'TND') {
                $results[$currency] = [
                    'amount'    => $this->convert($amountTND, 'TND', $currency),
                    'formatted' => $this->formatPrice($this->convert($amountTND, 'TND', $currency), $currency),
                    'rate'      => $this->getExchangeRate('TND', $currency),
                ];
            }
        }
        
        $results['TND'] = [
            'amount'    => $amountTND,
            'formatted' => $this->formatPrice($amountTND, 'TND'),
            'rate'      => 1,
        ];
        
        return $results;
    }

    /**
     * Formate un prix avec le symbole de la devise
     *
     * @param float $amount Montant à formater
     * @param string $currency Code devise (TND, EUR, USD, etc.)
     * @param bool $showSymbol Afficher le symbole ou le code
     * @return string Prix formaté
     */
    public function formatPrice(float $amount, string $currency, bool $showSymbol = true): string
    {
        $symbols = [
            'TND' => 'DT',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'DZD' => 'DA',
            'LYD' => 'LD',
            'MAD' => 'DH',
            'SAR' => '﷼',
            'AED' => 'د.إ',
            'QAR' => '﷼',
            'KWD' => 'KD',
            'BHD' => 'BD',
            'OMR' => 'OMR',
            'JOD' => 'JD',
            'CNY' => '¥',
            'JPY' => '¥',
            'TRY' => '₺',
            'RUB' => '₽',
            'INR' => '₹',
            'AUD' => 'A$',
            'NZD' => 'NZ$',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'HUF' => 'Ft',
        ];

        $formatted = number_format($amount, 2, ',', ' ');
        
        if ($showSymbol && isset($symbols[$currency])) {
            $symbolAfter = ['TND', 'DZD', 'MAD', 'LYD', 'CNY', 'JPY', 'INR'];
            if (in_array($currency, $symbolAfter)) {
                return $formatted . ' ' . $symbols[$currency];
            }
            return $symbols[$currency] . ' ' . $formatted;
        }
        
        return $formatted . ' ' . $currency;
    }

    /**
     * Retourne la liste des devises disponibles avec leurs noms
     *
     * @return array<string, string>
     */
    public function getAvailableCurrencies(): array
    {
        if (!empty($this->exchangeRates)) {
            $currencies = [];
            foreach ($this->exchangeRates as $code => $rate) {
                $currencies[$code] = $this->getCurrencyName($code);
            }
            ksort($currencies);
            return $currencies;
        }
        
        return [
            'TND' => 'Dinar Tunisien',
            'EUR' => 'Euro',
            'USD' => 'Dollar US',
            'GBP' => 'Livre Sterling',
            'CAD' => 'Dollar Canadien',
            'CHF' => 'Franc Suisse',
            'DZD' => 'Dinar Algérien',
            'LYD' => 'Dinar Libyen',
            'MAD' => 'Dirham Marocain',
            'SAR' => 'Riyal Saoudien',
            'AED' => 'Dirham Émirati',
            'QAR' => 'Riyal Qatari',
            'KWD' => 'Dinar Koweïtien',
            'BHD' => 'Dinar Bahreïni',
            'OMR' => 'Rial Omanais',
            'JOD' => 'Dinar Jordanien',
            'CNY' => 'Yuan Chinois',
            'JPY' => 'Yen Japonais',
            'TRY' => 'Lire Turque',
            'RUB' => 'Rouble Russe',
            'INR' => 'Roupie Indienne',
            'AUD' => 'Dollar Australien',
            'NZD' => 'Dollar Néo-Zélandais',
            'SEK' => 'Couronne Suédoise',
            'NOK' => 'Couronne Norvégienne',
            'DKK' => 'Couronne Danoise',
            'PLN' => 'Zloty Polonais',
            'CZK' => 'Couronne Tchèque',
            'HUF' => 'Forint Hongrois',
        ];
    }

    /**
     * Retourne le nom d'une devise à partir de son code
     *
     * @param string $code Code devise
     * @return string Nom de la devise
     */
    private function getCurrencyName(string $code): string
    {
        $names = [
            'TND' => 'Dinar Tunisien',
            'EUR' => 'Euro',
            'USD' => 'Dollar US',
            'GBP' => 'Livre Sterling',
            'CAD' => 'Dollar Canadien',
            'CHF' => 'Franc Suisse',
            'DZD' => 'Dinar Algérien',
            'LYD' => 'Dinar Libyen',
            'MAD' => 'Dirham Marocain',
            'SAR' => 'Riyal Saoudien',
            'AED' => 'Dirham Émirati',
            'QAR' => 'Riyal Qatari',
            'KWD' => 'Dinar Koweïtien',
            'BHD' => 'Dinar Bahreïni',
            'OMR' => 'Rial Omanais',
            'JOD' => 'Dinar Jordanien',
            'CNY' => 'Yuan Chinois',
            'JPY' => 'Yen Japonais',
            'TRY' => 'Lire Turque',
            'RUB' => 'Rouble Russe',
            'INR' => 'Roupie Indienne',
            'AUD' => 'Dollar Australien',
            'NZD' => 'Dollar Néo-Zélandais',
            'SEK' => 'Couronne Suédoise',
            'NOK' => 'Couronne Norvégienne',
            'DKK' => 'Couronne Danoise',
            'PLN' => 'Zloty Polonais',
            'CZK' => 'Couronne Tchèque',
            'HUF' => 'Forint Hongrois',
        ];
        
        return $names[$code] ?? $code;
    }

    /**
     * Met à jour les taux de change
     *
     * @return bool Succès de la mise à jour
     */
    public function updateExchangeRates(): bool
    {
        return $this->refreshExchangeRates();
    }

    /**
     * Obtient le taux de change pour une devise spécifique depuis TND
     *
     * @param string $currency Code devise
     * @return float|null Taux de change ou null si non trouvé
     */
    public function getRateForCurrency(string $currency): ?float
    {
        $currency = strtoupper($currency);
        
        if (empty($this->exchangeRates)) {
            $this->exchangeRates = $this->fallbackRates;
        }
        
        return $this->exchangeRates[$currency] ?? null;
    }

    /**
     * Vérifie si une devise est supportée
     *
     * @param string $currency Code devise
     * @return bool
     */
    public function isCurrencySupported(string $currency): bool
    {
        $currency = strtoupper($currency);
        
        if (empty($this->exchangeRates)) {
            $this->exchangeRates = $this->fallbackRates;
        }
        
        return isset($this->exchangeRates[$currency]);
    }

    /**
     * Active/désactive les taux de change en direct
     *
     * @param bool $useLiveRates
     */
    public function setUseLiveRates(bool $useLiveRates): void
    {
        $this->useLiveRates = $useLiveRates;
        if ($useLiveRates) {
            $this->refreshExchangeRates();
        } else {
            $this->exchangeRates = $this->fallbackRates;
        }
    }

    /**
     * Retourne la date du dernier chargement des taux
     *
     * @return string|null
     */
    public function getLastUpdate(): ?string
    {
        return $this->lastUpdate;
    }
}