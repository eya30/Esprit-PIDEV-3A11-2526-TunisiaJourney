<?php
// src/Service/TranslationService.php

namespace App\Service;

use Stichoza\GoogleTranslate\GoogleTranslate;
use Psr\Log\LoggerInterface;

class TranslationService
{
    private GoogleTranslate $translator;
    private LoggerInterface $logger;

    /** @var array<string, string> */
    private array $cache = [];

    /** @var array<string, string> */
    private array $supportedLanguages = [
        'fr' => 'Français',
        'en' => 'English',
        'ar' => 'العربية',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
        'nl' => 'Nederlands',
        'tr' => 'Türkçe',
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->translator = new GoogleTranslate();
        $this->translator->setOptions([
            'verify'  => false,
            'timeout' => 30,
        ]);
    }

    public function translate(string $text, string $targetLang = 'fr', ?string $sourceLang = null): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        $cacheKey = md5($text . $targetLang . ($sourceLang ?? 'auto'));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            if ($sourceLang && $sourceLang !== 'auto') {
                $this->translator->setSource($sourceLang);
            } else {
                $this->translator->setSource(null);
            }

            $this->translator->setTarget($targetLang);

            // Fix ligne 62 : translate() peut retourner string|null → fallback sur $text
            $result = $this->translator->translate($text) ?? $text;

            $this->cache[$cacheKey] = $result;
            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Translation error: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * @param array<int|string, string> $texts
     * @return array<int|string, string>
     */
    public function translateBatch(array $texts, string $targetLang = 'fr', ?string $sourceLang = null): array
    {
        $results = [];
        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLang, $sourceLang);
        }
        return $results;
    }

    public function detectLanguage(string $text): ?string
    {
        try {
            $this->translator->setSource(null);
            $this->translator->setTarget('en');
            $this->translator->translate(substr($text, 0, 100));
            return $this->translator->getLastDetectedSource();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    public function isLanguageSupported(string $code): bool
    {
        return isset($this->supportedLanguages[$code]);
    }

    public function getFlagEmoji(string $code): string
    {
        $flags = [
            'fr' => '🇫🇷', 'en' => '🇬🇧', 'ar' => '🇸🇦', 'es' => '🇪🇸',
            'de' => '🇩🇪', 'it' => '🇮🇹', 'pt' => '🇵🇹', 'ru' => '🇷🇺',
            'zh' => '🇨🇳', 'ja' => '🇯🇵', 'ko' => '🇰🇷', 'nl' => '🇳🇱',
            'tr' => '🇹🇷',
        ];
        return $flags[$code] ?? '🌐';
    }
}
