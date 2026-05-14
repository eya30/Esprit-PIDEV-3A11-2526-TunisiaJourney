<?php
namespace App\Controller\Api;

use App\Entity\Commentaire;
use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/commentaire')]
class CommentaireApiController extends AbstractController
{
    #[Route('/new/{idP}', name: 'api_commentaire_new', methods: ['POST'])]
    public function new(
        Request $request,
        Publication $publication,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $description = $data['description'] ?? null;
        $tags = $data['tags'] ?? null;
        
        $errors = [];
        
        // 1. Vérifier que le commentaire n'est pas vide
        if (!$description || trim($description) === '') {
            $errors[] = 'Le commentaire ne peut pas être vide.';
        }
        
        // 2. Vérifier la longueur minimale (3 caractères)
        if ($description && mb_strlen(trim($description)) < 3) {
            $errors[] = 'Le commentaire doit contenir au moins 3 caractères.';
        }
        
        // 3. Compter les mots (max 15 mots)
        if ($description && trim($description) !== '') {
            $text = trim($description);
            $words = preg_split('/\s+/', $text);
            $wordCount = ($words !== false) ? count($words) : 0;
            if ($wordCount > 15) {
                $errors[] = 'Le commentaire ne peut pas dépasser 15 mots. (' . $wordCount . ' mots actuellement)';
            }
        }
        
        // 4. Vérifier la longueur maximale (500 caractères)
        if ($description && mb_strlen(trim($description)) > 500) {
            $errors[] = 'Le commentaire ne peut pas dépasser 500 caractères.';
        }
        
        // 5. Vérifier que le commentaire n'est pas composé uniquement d'espaces
        if ($description && preg_match('/^\s+$/', $description)) {
            $errors[] = 'Le commentaire ne peut pas être composé uniquement d\'espaces.';
        }
        
        // 6. Vérifier les caractères dangereux (XSS et injection SQL)
        $dangerousPatterns = [
            '/<script/i', '/javascript:/i', '/onclick=/i', '/onload=/i', 
            '/onerror=/i', '/alert\(/i', '/confirm\(/i', '/prompt\(/i',
            '/SELECT\s+/i', '/INSERT\s+/i', '/UPDATE\s+/i', '/DELETE\s+/i', 
            '/DROP\s+/i', '/UNION\s+/i', '/ALTER\s+/i', '/CREATE\s+/i',
            '/EXEC\s+/i', '/EXECUTE\s+/i', '/--/', '/\;/'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $description)) {
                $errors[] = 'Le commentaire contient du contenu non autorisé.';
                break;
            }
        }
        
        // 7. Vérifier les URLs
        if ($description && preg_match('/(http:\/\/|https:\/\/|www\.)/i', $description)) {
            $errors[] = 'Les liens URL ne sont pas autorisés dans les commentaires.';
        }
        
        // Si des erreurs existent, les retourner
        if (!empty($errors)) {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], 400);
        }
        
        // Nettoyage du texte
        $description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');
        $tags = htmlspecialchars(trim($tags ?? ''), ENT_QUOTES, 'UTF-8');
        
        // Création du commentaire
        $commentaire = new Commentaire();
        $commentaire->setPublication($publication);
        $commentaire->setDescription($description);
        $commentaire->setTags($tags);
        $commentaire->setDateCreation(new \DateTime());
        
        // Récupération de l'utilisateur connecté - SANS method_exists
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            // Récupérer l'identifiant de l'utilisateur
            // Note: Ajoutez cette méthode dans votre entité Commentaire
            $userId = method_exists($user, 'getId') ? $user->getId() : $user->getUserIdentifier();
            
            // Appel direct - assurez-vous que cette méthode existe dans Commentaire
            // Si elle n'existe pas, commentez cette ligne ou ajoutez la méthode
            // $commentaire->setUserId($userId);
            
            // Alternative: stocker dans une propriété existante
            // $commentaire->setUserReporterId($userId);
        }
        
        // Informations supplémentaires
        $commentaire->setIpAddress($request->getClientIp());
        $commentaire->setUserAgent($request->headers->get('User-Agent'));
        $commentaire->setCreatedAt(new \DateTime());
        
        try {
            $em->persist($commentaire);
            $em->flush();
            
            // S'assurer que la description n'est pas null pour nl2br
            $commentDescription = $commentaire->getDescription();
            $safeDescription = $commentDescription ?? '';
            
            // Vérifier que dateCreation n'est pas null avant format()
            $dateCreation = $commentaire->getDateCreation();
            $formattedDate = ($dateCreation instanceof \DateTimeInterface) ? $dateCreation->format('d/m/Y H:i:s') : date('d/m/Y H:i:s');
            
            // Récupérer le nombre de commentaires
            $commentaires = $publication->getCommentaires();
            $totalCommentaires = count($commentaires);
            
            return new JsonResponse([
                'success' => true,
                'comment' => [
                    'id'          => $commentaire->getIdC(),
                    'description' => nl2br($safeDescription),
                    'tags'        => $commentaire->getTags() ?? '',
                    'date'        => $formattedDate,
                ],
                'total' => $totalCommentaires,
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['Erreur lors de l\'enregistrement en base de données: ' . $e->getMessage()]
            ], 500);
        }
    }

    #[Route('/{idC}', name: 'api_commentaire_delete', methods: ['DELETE'])]
    public function delete(
        Commentaire $commentaire,
        EntityManagerInterface $em
    ): JsonResponse {
        $publication = $commentaire->getPublication();
        
        // Vérifier que publication n'est pas null
        if ($publication === null) {
            $total = 0;
        } else {
            $commentaires = $publication->getCommentaires();
            $total = count($commentaires) - 1;
        }
        
        $em->remove($commentaire);
        $em->flush();
        
        return new JsonResponse([
            'success' => true,
            'total' => max(0, $total),
        ]);
    }

    #[Route('/{idC}/edit', name: 'api_commentaire_edit', methods: ['PUT'])]
    public function edit(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $description = $data['description'] ?? null;
        $tags = $data['tags'] ?? null;
        
        if (!$description || trim($description) === '') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Le commentaire ne peut pas être vide.'
            ], 400);
        }
        
        $words = preg_split('/\s+/', trim($description));
        $wordCount = ($words !== false) ? count($words) : 0;
        
        if ($wordCount > 15) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Le commentaire ne peut pas dépasser 15 mots. (' . $wordCount . ' mots actuellement)'
            ], 400);
        }
        
        $commentaire->setDescription(htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8'));
        if ($tags !== null) {
            $commentaire->setTags(htmlspecialchars(trim($tags), ENT_QUOTES, 'UTF-8'));
        }
        
        $em->flush();
        
        $commentDescription = $commentaire->getDescription();
        $safeDescription = $commentDescription ?? '';
        
        return new JsonResponse([
            'success' => true,
            'description' => nl2br($safeDescription),
            'tags' => $commentaire->getTags() ?? '',
        ]);
    }
}