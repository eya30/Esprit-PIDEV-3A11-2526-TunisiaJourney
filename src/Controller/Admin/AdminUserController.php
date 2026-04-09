<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepo): Response
    {
        return $this->render('admin/dashboard/users/index.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    /**
     * Appelé en AJAX depuis le JS après affichage des erreurs
     * pour nettoyer la session.
     */
    #[Route('/clear-edit-errors', name: 'admin_users_clear_edit_errors', methods: ['POST'])]
    public function clearEditErrors(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $session->remove('_admin_edit_errors');
        $session->remove('_admin_edit_data');
        $session->remove('_admin_edit_id');
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['POST'])]
    public function edit(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo,
        ParameterBagInterface  $params,
        ValidatorInterface     $validator
    ): Response {
        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users_index');
        }

        $session = $request->getSession();

        // ── Lecture des champs ────────────────────────────────────
        $nom           = trim($request->request->get('nom', ''));
        $prenom        = trim($request->request->get('prenom', ''));
        $telephone     = trim($request->request->get('telephone', ''));
        $adresse       = trim($request->request->get('adresse', ''));
        $dateNaissance = trim($request->request->get('dateNaissance', ''));

        // Données à renvoyer en cas d'erreur
        $editData = compact('nom', 'prenom', 'telephone', 'adresse', 'dateNaissance');

        // ── Hydratation (valeur soumise, même vide, pour que le validateur détecte les champs manquants) ──
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setTelephone($telephone !== '' ? $telephone : null);
        $user->setAdresse($adresse !== '' ? $adresse : null);

        if ($dateNaissance !== '') {
            try {
                $user->setDateNaissance(new \DateTime($dateNaissance));
            } catch (\Exception) {
                $user->setDateNaissance(null);
            }
        } else {
            $user->setDateNaissance(null);
        }

        // ── Validation Symfony ────────────────────────────────────
        $violations = $validator->validate($user);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }
            $session->set('_admin_edit_errors', $errors);
            $session->set('_admin_edit_data',   $editData);
            $session->set('_admin_edit_id',     $id);

            // Annule les modifications en mémoire
            $em->refresh($user);

            return $this->redirectToRoute('admin_users_index', ['openEditUser' => $id]);
        }

        // ── Rôle / Niveau / Statut ────────────────────────────────
        $role = strtoupper($request->request->get('role', 'MEMBRE'));
        $user->setRole($role);

       if ($role === 'ADMIN') {
    $niveau = strtoupper($request->request->get('niveau', 'ADMIN'));
    $user->setNiveau($niveau);
    $user->setStatut(null);}
     else {
            $statut = strtoupper($request->request->get('statut', 'ACTIF'));
            $user->setStatut($statut);
            $user->setNiveau(null);
        }

        // ── Suppression photo ─────────────────────────────────────
        if ($request->request->get('remove_photo') === '1') {
            $user->setProfileImageUrl(null);
        }

        // ── Upload photo via ImgBB ────────────────────────────────
        $photoFile = $request->files->get('photo');
        if ($photoFile && $photoFile->isValid()) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . $params->get('imgbb_api_key'),
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => [
                    'image' => new \CURLFile(
                        $photoFile->getPathname(),
                        $photoFile->getMimeType(),
                        $photoFile->getClientOriginalName()
                    ),
                ],
            ]);
            $json = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if (isset($json['data']['url'])) {
                $user->setProfileImageUrl($json['data']['url']);
            }
        }

        // ── Sauvegarde ───────────────────────────────────────────
        $em->flush();

        // Nettoyage session
        $session->remove('_admin_edit_errors');
        $session->remove('_admin_edit_data');
        $session->remove('_admin_edit_id');

       
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo
    ): Response {
        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users_index');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users_index');
        }

        $em->remove($user);
        $em->flush();

       
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/toggle-statut', name: 'admin_users_toggle_statut', methods: ['POST'])]
    public function toggleStatut(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        UserRepository         $userRepo
    ): Response {
        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_users_index');
        }

        $newStatut = $user->getStatut() === 'ACTIF' ? 'BLOQUE' : 'ACTIF';
        $user->setStatut($newStatut);
        $em->flush();

       
       
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/export-excel', name: 'admin_users_export_excel', methods: ['GET'])]
    public function exportExcel(UserRepository $userRepo): StreamedResponse
    {
        $users = $userRepo->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Utilisateurs');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF93032E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
        ];
        $evenRowStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF9F0']]];
        $oddRowStyle  = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']]];

        $headers = ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Adresse', 'Date Naissance', 'Date Inscription', 'Rôle', 'Statut'];
        $cols    = ['A',  'B',   'C',      'D',     'E',         'F',       'G',              'H',                'I',    'J'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue($cols[$i] . '1', $header);
        }
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach ($users as $row => $user) {
            $r = $row + 2;
            $sheet->setCellValue('A' . $r, $user->getId());
            $sheet->setCellValue('B' . $r, $user->getNom());
            $sheet->setCellValue('C' . $r, $user->getPrenom());
            $sheet->setCellValue('D' . $r, $user->getEmail());
            $sheet->setCellValue('E' . $r, $user->getTelephone() ?? '—');
            $sheet->setCellValue('F' . $r, $user->getAdresse()   ?? '—');
            $sheet->setCellValue('G' . $r, $user->getDateNaissance()   ? $user->getDateNaissance()->format('d/m/Y')   : '—');
            $sheet->setCellValue('H' . $r, $user->getDateInscription() ? $user->getDateInscription()->format('d/m/Y') : '—');
            $sheet->setCellValue('I' . $r, $user->getRole()   ?? '—');
            $sheet->setCellValue('J' . $r, $user->getStatut() ?? '—');

            $sheet->getStyle("A{$r}:J{$r}")->applyFromArray($row % 2 === 0 ? $evenRowStyle : $oddRowStyle);

            if ($user->getStatut() === 'BLOQUE') {
                $sheet->getStyle("J{$r}")->getFont()->getColor()->setARGB('FFEF4444');
                $sheet->getStyle("J{$r}")->getFont()->setBold(true);
            } else {
                $sheet->getStyle("J{$r}")->getFont()->getColor()->setARGB('FF16A34A');
                $sheet->getStyle("J{$r}")->getFont()->setBold(true);
            }
            $sheet->getRowDimension($r)->setRowHeight(22);
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = count($users) + 1;
        $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFE0E0E0');

        $sheet->freezePane('A2');

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $filename = 'utilisateurs_' . date('Y-m-d') . '.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}