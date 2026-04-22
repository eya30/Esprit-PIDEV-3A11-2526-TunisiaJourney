<?php

namespace App\Service;

use App\Entity\Activite;
use App\Repository\AvisActRepository;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AISummaryService
{
    private string $mlPath;

    public function __construct(
        private AvisActRepository $avisRepo,
        string $projectDir
    ) {
        $this->mlPath = $projectDir . '/ml';
    }

    public function generateSummary(int $activiteId): array
    {
        $avisList = $this->avisRepo->findByActiviteId($activiteId);
        return $this->runMlAnalysis($avisList);
    }

    public function generateSummaryForActivite(Activite $activite): array
    {
        $avisList = $this->avisRepo->findByActivite($activite);
        return $this->runMlAnalysis($avisList);
    }

    public function generateShortSummary(int $activiteId): string
    {
        $full = $this->generateSummary($activiteId);
        $summary = $full['summary'] ?? '';
        return strlen($summary) > 120
            ? substr($summary, 0, 117) . '...'
            : $summary;
    }

    private function runMlAnalysis(array $avisList): array
    {
        if (empty($avisList)) {
            return $this->emptyResponse();
        }

        $payload = array_map(fn($avis) => [
            'note'        => $avis->getNote(),
            'commentaire' => $avis->getCommentaire() ?? '',
        ], $avisList);

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Chemin complet vers python.exe pour que XAMPP/PHP le trouve
        $process = new Process([
            'C:\\Users\\Maram\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
            $this->mlPath . '/predict.py',
            $jsonPayload,
        ]);

        $process->setEnv(['PYTHONIOENCODING' => 'utf-8']);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return $this->fallbackResponse($avisList, $process->getErrorOutput());
        }

        $output = json_decode($process->getOutput(), true);

        if (!$output || !isset($output['success'])) {
            return $this->fallbackResponse($avisList, 'Reponse Python invalide');
        }

        return [
            'has_avis'   => $output['has_avis'] ?? false,
            'summary'    => $output['summary'] ?? '',
            'sentiment'  => $output['sentiment'] ?? 'neutral',
            'confidence' => $output['confidence'] ?? 0,
            'key_points' => $output['key_points'] ?? [],
            'stats'      => $output['stats'] ?? [],
            'ml_details' => $output['details'] ?? [],
        ];
    }

    private function emptyResponse(): array
    {
        return [
            'has_avis'   => false,
            'summary'    => 'Aucun avis pour le moment. Soyez le premier !',
            'sentiment'  => 'neutral',
            'confidence' => 0,
            'key_points' => [],
            'stats'      => [
                'total'    => 0,
                'average'  => 0,
                'positive' => 0,
                'negative' => 0,
                'neutral'  => 0,
            ],
        ];
    }

    private function fallbackResponse(array $avisList, string $errorMsg = ''): array
    {
        $total = count($avisList);
        $sum   = array_sum(array_map(fn($a) => $a->getNote(), $avisList));
        $avg   = $total > 0 ? round($sum / $total, 1) : 0;

        return [
            'has_avis'   => true,
            'summary'    => "Note moyenne : {$avg}/5 sur {$total} avis. (Analyse ML indisponible)",
            'sentiment'  => $avg >= 4 ? 'positive' : ($avg <= 2 ? 'negative' : 'mixed'),
            'confidence' => 0,
            'key_points' => [],
            'stats'      => [
                'total'   => $total,
                'average' => $avg,
            ],
            'ml_error'   => $errorMsg,
        ];
    }
}