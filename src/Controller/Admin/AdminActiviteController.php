<?php

namespace App\Controller\Admin;

use App\Entity\Activite;
use App\Entity\Evenement;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/activites')]
class AdminActiviteController extends AbstractController
{
    const ITEMS_PER_PAGE = 2;

    /**
     * @param ConstraintViolationListInterface<\Symfony\Component\Validator\ConstraintViolationInterface> $violations
     * @return array<string, string>
     */
    private function buildErrorsArray(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath();
            if (!isset($errors[$path])) {
                $errors[$path] = (string) $violation->getMessage();
            }
        }

        return $errors;
    }

    private function normalizeDurationToMinutes(string $duration): int
    {
        $totalMinutes = 0;

        if (preg_match('/(\d+)h(?:(\d+)min)?/', $duration, $matches)) {
            $totalMinutes += (int) $matches[1] * 60;
            if (isset($matches[2])) {
                $totalMinutes += (int) $matches[2];
            }
        } elseif (preg_match('/(\d+)h/', $duration, $matches)) {
            $totalMinutes += (int) $matches[1] * 60;
        } elseif (preg_match('/(\d+)min/', $duration, $matches)) {
            $totalMinutes += (int) $matches[1];
        }

        return $totalMinutes;
    }

    #[Route('/', name: 'admin_activite_index')]
    public function index(Connection $connection, Request $request): Response
    {
        $page   = max(1, $request->query->getInt('page', 1));
        $search = trim((string) $request->query->get('search', ''));
        $sort   = (string) $request->query->get('sort', 'heure_debut_asc');
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        $where  = '';
        $params = [];

        if ($search !== '') {
            $searchLower = '%' . strtolower($search) . '%';
            $where       = "WHERE (
                          LOWER(a.Titre) LIKE ?
                       OR LOWER(a.TypeActivite) LIKE ?
                       OR LOWER(a.NomAnimateur) LIKE ?
                       OR LOWER(a.Duree) LIKE ?
                       OR CAST(a.Prix AS CHAR) LIKE ?
                       OR CAST(a.CapaciteM AS CHAR) LIKE ?
                    )";
            $params      = [$searchLower, $searchLower, $searchLower, $searchLower, $searchLower, $searchLower];
        }

        $sqlAll = "SELECT a.*, e.Titre as evenement_titre 
                   FROM Activite a 
                   LEFT JOIN Evenement e ON a.IDEv = e.IDEv 
                   $where";

        $allActivites = $connection->fetchAllAssociative($sqlAll, $params);

        usort($allActivites, function ($a, $b) use ($sort) {
            return match ($sort) {
                'prix_asc'         => ($a['Prix'] ?? 0) <=> ($b['Prix'] ?? 0),
                'capacite_asc'     => ($a['CapaciteM'] ?? 0) <=> ($b['CapaciteM'] ?? 0),
                'duree_asc'        => $this->normalizeDurationToMinutes((string) ($a['Duree'] ?? '0min'))
                                        <=> $this->normalizeDurationToMinutes((string) ($b['Duree'] ?? '0min')),
                'titre_az'         => strcasecmp((string) ($a['Titre'] ?? ''), (string) ($b['Titre'] ?? '')),
                'nom_animateur_az' => strcasecmp((string) ($a['NomAnimateur'] ?? ''), (string) ($b['NomAnimateur'] ?? '')),
                default            => strcmp((string) ($a['HeureDebut'] ?? '00:00'), (string) ($b['HeureDebut'] ?? '00:00')),
            };
        });

        $total      = count($allActivites);
        $totalPages = max(1, ceil($total / self::ITEMS_PER_PAGE));

        if ($page > $totalPages && $totalPages > 0) {
            return $this->redirectToRoute('admin_activite_index', [
                'page'   => $totalPages,
                'search' => $search,
                'sort'   => $sort,
            ]);
        }

        $activites = array_slice($allActivites, $offset, self::ITEMS_PER_PAGE);

        return $this->render('admin/activite/admin_activite_index.html.twig', [
            'activites'    => $activites,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'total_items'  => $total,
            'search'       => $search,
            'sort'         => $sort,
        ]);
    }

    #[Route('/new', name: 'admin_activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection, ValidatorInterface $validator): Response
    {
        $evenements = $connection->fetchAllAssociative("SELECT IDEv, Titre FROM Evenement ORDER BY Titre");
        $errors     = [];
        $old        = [];

        $preSelectedEvenement = $request->query->get('evenement');
        // FIX :131 — $old est toujours array{} à ce stade : tout accès à un offset inexistant
        // (isset ou empty) déclenche isset.offset / empty.offset chez PHPStan.
        // La condition est triviale ($old ne peut pas encore avoir 'IDEv') : on affecte directement.
        if ($preSelectedEvenement) {
            $old['IDEv'] = $preSelectedEvenement;
        }

        if ($request->isMethod('POST')) {
            $titreRaw        = trim((string) $request->request->get('Titre', ''));
            $descriptionRaw  = trim((string) $request->request->get('Description', ''));
            $typeActiviteRaw = trim((string) $request->request->get('TypeActivite', ''));
            $heureDebutRaw   = trim((string) $request->request->get('HeureDebut', ''));
            $dureeRaw        = trim((string) $request->request->get('Duree', ''));
            $nomAnimateurRaw = trim((string) $request->request->get('NomAnimateur', ''));
            $capaciteMRaw    = trim((string) $request->request->get('CapaciteM', ''));
            $prixRaw         = trim((string) $request->request->get('Prix', ''));
            $idEvRaw         = trim((string) $request->request->get('IDEv', ''));

            $old = [
                'Titre'        => $titreRaw,
                'Description'  => $descriptionRaw,
                'TypeActivite' => $typeActiviteRaw,
                'HeureDebut'   => $heureDebutRaw,
                'Duree'        => $dureeRaw,
                'NomAnimateur' => $nomAnimateurRaw,
                'CapaciteM'    => $capaciteMRaw,
                'Prix'         => $prixRaw,
                'IDEv'         => $idEvRaw,
            ];

            $capaciteInt = ($capaciteMRaw !== '' && ctype_digit($capaciteMRaw)) ? (int) $capaciteMRaw : null;
            $prixFloat   = ($prixRaw !== '' && is_numeric($prixRaw)) ? (float) $prixRaw : null;

            $activite = new Activite();
            $activite->setTitre($titreRaw);
            $activite->setDescription($descriptionRaw);
            $activite->setTypeActivite($typeActiviteRaw);
            $activite->setHeureDebut($heureDebutRaw);
            $activite->setDuree($dureeRaw);
            $activite->setNomAnimateur($nomAnimateurRaw);
            $activite->setCapaciteM($capaciteInt);
            $activite->setPrix($prixFloat);

            if ($idEvRaw !== '') {
                // FIX :131 — on utilise is_array() + array_key_exists() au lieu de empty($data['IDEv'])
                // pour éviter "Offset 'IDEv' on array{} does not exist"
                $evenementData = $connection->fetchAssociative("SELECT IDEv FROM Evenement WHERE IDEv = ?", [$idEvRaw]);
                if (is_array($evenementData) && array_key_exists('IDEv', $evenementData) && $evenementData['IDEv'] !== null) {
                    $evenement = new Evenement();
                    $evenement->setIDEv((int) $idEvRaw);
                    $activite->setEvenement($evenement);
                }
            }

            $violations = $validator->validate($activite);
            $errors     = $this->buildErrorsArray($violations);

            if ($capaciteMRaw !== '' && !ctype_digit($capaciteMRaw) && !isset($errors['CapaciteM'])) {
                $errors['CapaciteM'] = 'La capacité doit être un entier valide (chiffres uniquement).';
            }
            if ($prixRaw !== '' && !is_numeric($prixRaw) && !isset($errors['Prix'])) {
                $errors['Prix'] = 'Le prix doit être un nombre valide.';
            }
            if ($idEvRaw === '' && !isset($errors['evenement'])) {
                $errors['IDEv'] = 'L\'événement associé est obligatoire.';
            }

            if ($request->isXmlHttpRequest() || $request->request->get('ajax_validation')) {
                return $this->json(['errors' => $errors]);
            }

            if (empty($errors)) {
                $imageFile = $request->files->get('image');
                $imageName = null;

                if ($imageFile && $imageFile->getError() !== UPLOAD_ERR_NO_FILE) {
                    $safeFilename  = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                    $imageName     = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    // FIX :204 — getParameter() retourne mixed ; assert(is_string()) satisfait PHPStan
                    $uploadActDir  = $this->getParameter('uploads_activites_directory');
                    assert(is_string($uploadActDir));
                    $imageFile->move($uploadActDir, $imageName);
                }

                $connection->executeStatement(
                    "INSERT INTO Activite (Titre, Description, TypeActivite, HeureDebut, Duree, NomAnimateur, CapaciteM, Prix, Image, IDEv) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $titreRaw,
                        $descriptionRaw,
                        $typeActiviteRaw,
                        $heureDebutRaw,
                        $dureeRaw,
                        $nomAnimateurRaw,
                        $capaciteInt,
                        $prixFloat,
                        $imageName,
                        $idEvRaw,
                    ]
                );

                $this->addFlash('success', 'Activité créée avec succès !');

                return $this->redirectToRoute('admin_evenement_activites', ['id' => $idEvRaw]);
            }
        }

        return $this->render('admin/activite/newact.html.twig', [
            'evenements' => $evenements,
            'errors'     => $errors,
            'old'        => $old,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Connection $connection, ValidatorInterface $validator, int $id): Response
    {
        $activiteData = $connection->fetchAssociative("SELECT * FROM Activite WHERE IDAct = ?", [$id]);

        if (!$activiteData) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        $evenements = $connection->fetchAllAssociative("SELECT IDEv, Titre FROM Evenement ORDER BY Titre");
        $errors     = [];
        $old        = [];

        if ($request->isMethod('POST')) {
            $titreRaw        = trim((string) $request->request->get('Titre', ''));
            $descriptionRaw  = trim((string) $request->request->get('Description', ''));
            $typeActiviteRaw = trim((string) $request->request->get('TypeActivite', ''));
            $heureDebutRaw   = trim((string) $request->request->get('HeureDebut', ''));
            $dureeRaw        = trim((string) $request->request->get('Duree', ''));
            $nomAnimateurRaw = trim((string) $request->request->get('NomAnimateur', ''));
            $capaciteMRaw    = trim((string) $request->request->get('CapaciteM', ''));
            $prixRaw         = trim((string) $request->request->get('Prix', ''));
            // FIX :207 — la valeur retournée par get() peut être null|array|string|…
            // on force le cast en string pour éviter "Cannot cast … to string"
            $idEvRaw         = trim((string) $request->request->get('IDEv', ''));

            $old = [
                'Titre'        => $titreRaw,
                'Description'  => $descriptionRaw,
                'TypeActivite' => $typeActiviteRaw,
                'HeureDebut'   => $heureDebutRaw,
                'Duree'        => $dureeRaw,
                'NomAnimateur' => $nomAnimateurRaw,
                'CapaciteM'    => $capaciteMRaw,
                'Prix'         => $prixRaw,
                'IDEv'         => $idEvRaw,
            ];

            $capaciteInt = ($capaciteMRaw !== '' && ctype_digit($capaciteMRaw)) ? (int) $capaciteMRaw : null;
            $prixFloat   = ($prixRaw !== '' && is_numeric($prixRaw)) ? (float) $prixRaw : null;

            $activite = new Activite();
            $activite->setTitre($titreRaw);
            $activite->setDescription($descriptionRaw);
            $activite->setTypeActivite($typeActiviteRaw);
            $activite->setHeureDebut($heureDebutRaw);
            $activite->setDuree($dureeRaw);
            $activite->setNomAnimateur($nomAnimateurRaw);
            $activite->setCapaciteM($capaciteInt);
            $activite->setPrix($prixFloat);

            if ($idEvRaw !== '') {
                $evenementData = $connection->fetchAssociative("SELECT IDEv FROM Evenement WHERE IDEv = ?", [$idEvRaw]);
                if (is_array($evenementData) && array_key_exists('IDEv', $evenementData) && $evenementData['IDEv'] !== null) {
                    $evenement = new Evenement();
                    $evenement->setIDEv((int) $idEvRaw);
                    $activite->setEvenement($evenement);
                }
            }

            $violations = $validator->validate($activite);
            $errors     = $this->buildErrorsArray($violations);

            if ($capaciteMRaw !== '' && !ctype_digit($capaciteMRaw) && !isset($errors['CapaciteM'])) {
                $errors['CapaciteM'] = 'La capacité doit être un entier valide (chiffres uniquement).';
            }
            if ($prixRaw !== '' && !is_numeric($prixRaw) && !isset($errors['Prix'])) {
                $errors['Prix'] = 'Le prix doit être un nombre valide.';
            }
            if ($idEvRaw === '' && !isset($errors['evenement'])) {
                $errors['IDEv'] = 'L\'événement associé est obligatoire.';
            }

            if ($request->isXmlHttpRequest() || $request->request->get('ajax_validation')) {
                return $this->json(['errors' => $errors]);
            }

            if (empty($errors)) {
                $imageName = $activiteData['Image'];
                $imageFile = $request->files->get('image');

                if ($imageFile && $imageFile->getError() !== UPLOAD_ERR_NO_FILE) {
                    // FIX :320 — getParameter() retourne mixed ; assert(is_string()) satisfait PHPStan
                    $uploadDir = $this->getParameter('uploads_activites_directory');
                    assert(is_string($uploadDir));
                    if ($imageName && file_exists($uploadDir . '/' . $imageName)) {
                        unlink($uploadDir . '/' . $imageName);
                    }
                    $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                    $imageName    = $safeFilename . '_' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($uploadDir, $imageName);
                }

                $connection->executeStatement(
                    "UPDATE Activite SET Titre=?, Description=?, TypeActivite=?, HeureDebut=?, Duree=?, NomAnimateur=?, CapaciteM=?, Prix=?, Image=?, IDEv=? WHERE IDAct=?",
                    [
                        $titreRaw,
                        $descriptionRaw,
                        $typeActiviteRaw,
                        $heureDebutRaw,
                        $dureeRaw,
                        $nomAnimateurRaw,
                        $capaciteInt,
                        $prixFloat,
                        $imageName,
                        $idEvRaw,
                        $id,
                    ]
                );

                $this->addFlash('success', 'Activité modifiée avec succès !');

                return $this->redirectToRoute('admin_evenement_activites', ['id' => $idEvRaw]);
            }
        }

        return $this->render('admin/activite/editact.html.twig', [
            'activite'   => $activiteData,
            'evenements' => $evenements,
            'errors'     => $errors,
            'old'        => $old,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_activite_delete', methods: ['POST'])]
    public function delete(Request $request, Connection $connection, int $id): Response
    {
        $activite = $connection->fetchAssociative("SELECT IDEv, Image FROM Activite WHERE IDAct = ?", [$id]);
        $eventId  = $activite['IDEv'] ?? null;

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_activite_' . $id, is_string($token) ? $token : null)) {
            // FIX :369 — getParameter() retourne mixed ; assert(is_string()) satisfait PHPStan
            $uploadDir = $this->getParameter('uploads_activites_directory');
            assert(is_string($uploadDir));
            if ($activite && $activite['Image'] && file_exists($uploadDir . '/' . $activite['Image'])) {
                unlink($uploadDir . '/' . $activite['Image']);
            }

            $connection->executeStatement("DELETE FROM Activite WHERE IDAct = ?", [$id]);
            $this->addFlash('success', 'Activité supprimée avec succès !');
        }

        if ($eventId) {
            return $this->redirectToRoute('admin_evenement_activites', ['id' => $eventId]);
        }

        return $this->redirectToRoute('admin_activite_index');
    }
}