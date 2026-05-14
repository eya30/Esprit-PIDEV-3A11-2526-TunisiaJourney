<?php

namespace App\Controller\Admin;

use App\Entity\AdminLog;
use App\Service\AdminLogger;
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
    public function __construct(private AdminLogger $adminLogger) {}

    #[Route('', name: 'admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepo, \App\Repository\AdminLogRepository $logRepo): Response
    {
        return $this->render('admin/dashboard/users/index.html.twig', [
            'users' => $userRepo->findAll(),
            'logs'  => $logRepo->findRecent(100),
        ]);
    }

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

        // ── Snapshot AVANT modification ────────────────────────────
        $beforeNom       = $user->getNom();
        $beforePrenom    = $user->getPrenom();
        $beforeTelephone = $user->getTelephone();
        $beforeAdresse   = $user->getAdresse();
        $beforeNaissance = $user->getDateNaissance()?->format('d/m/Y');
        $beforeRole      = $user->getRole();
        $beforeStatut    = $user->getStatut();
        $beforeNiveau    = $user->getNiveau();

        // ── Lecture des champs — (string) cast lignes 77-81 ────────
        $nom           = trim((string) $request->request->get('nom', ''));
        $prenom        = trim((string) $request->request->get('prenom', ''));
        $telephone     = trim((string) $request->request->get('telephone', ''));
        $adresse       = trim((string) $request->request->get('adresse', ''));
        $dateNaissance = trim((string) $request->request->get('dateNaissance', ''));

        $editData = compact('nom', 'prenom', 'telephone', 'adresse', 'dateNaissance');

        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setTelephone($telephone !== '' ? $telephone : null);
        $user->setAdresse($adresse !== '' ? $adresse : null);

        if ($dateNaissance !== '') {
            try { $user->setDateNaissance(new \DateTime($dateNaissance)); }
            catch (\Exception) { $user->setDateNaissance(null); }
        } else {
            $user->setDateNaissance(null);
        }

        // ── Validation ─────────────────────────────────────────────
        $violations = $validator->validate($user, null, ['profile']);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()][] = $v->getMessage();
            }
            $session->set('_admin_edit_errors', $errors);
            $session->set('_admin_edit_data',   $editData);
            $session->set('_admin_edit_id',     $id);
            $em->refresh($user);
            return $this->redirectToRoute('admin_users_index', ['openEditUser' => $id]);
        }

        // ── Rôle / Niveau / Statut — (string) cast lignes 112, 115, 119 ──
        $role = strtoupper((string) $request->request->get('role', 'MEMBRE'));
        $user->setRole($role);
        if ($role === 'ADMIN') {
            $niveau = strtoupper((string) $request->request->get('niveau', 'ADMIN'));
            $user->setNiveau($niveau);
            $user->setStatut(null);
        } else {
            $statut = strtoupper((string) $request->request->get('statut', 'ACTIF'));
            $user->setStatut($statut);
            $user->setNiveau(null);
        }

        // ── Photo ──────────────────────────────────────────────────
        $photoChanged = false;
        if ($request->request->get('remove_photo') === '1') {
            $user->setProfileImageUrl(null);
            $photoChanged = true;
        }
        $photoFile = $request->files->get('photo');
        if ($photoFile && $photoFile->isValid()) {
            // ligne 134 : $params->get() retourne mixed → is_string guard
            $imgbbKeyRaw = $params->get('imgbb_api_key');
            $imgbbKey    = is_string($imgbbKeyRaw) ? $imgbbKeyRaw : '';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . $imgbbKey,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => ['image' => new \CURLFile(
                    $photoFile->getPathname(),
                    $photoFile->getMimeType(),
                    $photoFile->getClientOriginalName()
                )],
            ]);
            // ligne 143 : json_decode attend string → is_string guard
            $out  = curl_exec($ch);
            $json = json_decode(is_string($out) ? $out : '', true);
            curl_close($ch);

            if (isset($json['data']['url'])) {
                $user->setProfileImageUrl($json['data']['url']);
                $photoChanged = true;
            }
        }

        // ── Sauvegarde ─────────────────────────────────────────────
        $em->flush();

        // ── LOG ────────────────────────────────────────────────────
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();
        $cible = $user->getPrenom() . ' ' . $user->getNom() . ' (id=' . $user->getId() . ')';

        $this->adminLogger->log(
            $admin,
            AdminLog::ACTION_EDIT_USER,
            $cible,
            AdminLogger::buildDetails([
                AdminLogger::diff('nom',       $beforeNom,       $user->getNom()),
                AdminLogger::diff('prénom',    $beforePrenom,    $user->getPrenom()),
                AdminLogger::diff('téléphone', $beforeTelephone, $user->getTelephone()),
                AdminLogger::diff('adresse',   $beforeAdresse,   $user->getAdresse()),
                AdminLogger::diff('naissance', $beforeNaissance, $user->getDateNaissance()?->format('d/m/Y')),
                AdminLogger::diff('rôle',      $beforeRole,      $user->getRole()),
                AdminLogger::diff('statut',    $beforeStatut,    $user->getStatut()),
                AdminLogger::diff('niveau',    $beforeNiveau,    $user->getNiveau()),
                $photoChanged ? 'photo modifiée' : null,
            ])
        );

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

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();
        $cible = $user->getPrenom() . ' ' . $user->getNom() . ' (id=' . $user->getId() . ')';

        $this->adminLogger->log($admin, AdminLog::ACTION_DELETE_USER, $cible,
            'email: ' . $user->getEmail()
        );

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

        $oldStatut = $user->getStatut();
        $newStatut = $oldStatut === 'ACTIF' ? 'BLOQUE' : 'ACTIF';
        $user->setStatut($newStatut);
        $em->flush();

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();
        $cible = $user->getPrenom() . ' ' . $user->getNom() . ' (id=' . $user->getId() . ')';

        $this->adminLogger->log($admin, AdminLog::ACTION_TOGGLE_STATUT, $cible,
            'statut: ' . $oldStatut . ' → ' . $newStatut
        );

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
            $sheet->setCellValue('G' . $r, $user->getDateNaissance()   ? $user->getDateNaissance()->format('d/m/Y') : '—');
            $sheet->setCellValue('H' . $r, $user->getDateInscription() ? $user->getDateInscription()->format('d/m/Y') : '—');
            $sheet->setCellValue('I' . $r, $user->getRole()   ?? '—');
            $sheet->setCellValue('J' . $r, $user->getStatut() ?? '—');

            $sheet->getStyle("A{$r}:J{$r}")->applyFromArray($row % 2 === 0 ? $evenRowStyle : $oddRowStyle);
            $color = $user->getStatut() === 'BLOQUE' ? 'FFEF4444' : 'FF16A34A';
            $sheet->getStyle("J{$r}")->getFont()->getColor()->setARGB($color);
            $sheet->getStyle("J{$r}")->getFont()->setBold(true);
            $sheet->getRowDimension($r)->setRowHeight(22);
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = count($users) + 1;
        $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE0E0E0');
        $sheet->freezePane('A2');

        $response = new StreamedResponse(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        });

        $filename = 'utilisateurs_' . date('Y-m-d') . '.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}