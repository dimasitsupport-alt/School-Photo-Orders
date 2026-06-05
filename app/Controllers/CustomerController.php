<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Storage;
use App\Models\Order;
use App\Models\Student;

class CustomerController extends Controller
{
    private const DEFAULT_MAX_STUDENT_UPLOADS = 500;

    public function createOrder(): void
    {
        $this->view('customer/create_order', [
            'title' => 'Buat Pesanan',
        ]);
    }

    public function storeOrder(): void
    {
        if (!verify_csrf($_POST['_csrf_token'] ?? null)) {
            set_flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect('/');
        }

        $orderNo = strtoupper(trim((string) ($_POST['order_no'] ?? '')));
        $schoolName = trim((string) ($_POST['school_name'] ?? ''));
        $schoolAddress = trim((string) ($_POST['school_address'] ?? ''));
        $orderModel = new Order();
        $errors = [];

        if ($orderNo === '' || strlen($orderNo) > 60 || preg_match('/^[A-Z0-9_-]+$/', $orderNo) !== 1) {
            $errors[] = 'Nomor pesanan wajib diisi, maksimal 60 karakter, hanya huruf, angka, underscore, dan tanda minus.';
        }

        if ($schoolName === '' || strlen($schoolName) > 160) {
            $errors[] = 'Nama sekolah wajib diisi dan maksimal 160 karakter.';
        }

        if ($schoolAddress === '') {
            $errors[] = 'Alamat sekolah wajib diisi.';
        }

        if (!isset($_FILES['school_logo']) || (int) ($_FILES['school_logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Logo sekolah wajib diupload.';
        }

        if ($orderNo !== '' && $orderModel->exists($orderNo)) {
            $errors[] = 'Nomor pesanan sudah digunakan.';
        }

        if ($errors !== []) {
            remember_old($_POST);
            set_flash('error', implode(' ', $errors));
            redirect('/');
        }

        $logoPath = null;

        try {
            $logoPath = Storage::storeImage($_FILES['school_logo'], 'logos');

            $orderModel->create([
                'order_no' => $orderNo,
                'school_name' => $schoolName,
                'school_logo' => $logoPath,
                'school_address' => $schoolAddress,
            ]);

            set_flash('success', 'Pesanan berhasil dibuat. Silakan tambahkan data siswa.');
            redirect('/orders/' . rawurlencode($orderNo) . '/students');
        } catch (\Throwable $exception) {
            if ($logoPath !== null) {
                Storage::deletePublicFile($logoPath);
            }

            remember_old($_POST);
            set_flash('error', $exception->getMessage());
            redirect('/');
        }
    }

    public function studentsForm(string $orderNo): void
    {
        $order = (new Order())->findByOrderNoWithCount($orderNo);

        if ($order === null) {
            $this->notFound('Nomor pesanan tidak ditemukan.');
        }

        $this->view('customer/add_students', [
            'title' => 'Tambah Siswa',
            'order' => $order,
        ]);
    }

    public function storeStudents(string $orderNo): void
    {
        if (!verify_csrf($_POST['_csrf_token'] ?? null)) {
            set_flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect('/orders/' . rawurlencode($orderNo) . '/students');
        }

        $order = (new Order())->findByOrderNo($orderNo);

        if ($order === null) {
            $this->notFound('Nomor pesanan tidak ditemukan.');
        }

        try {
            $entries = $this->collectStudentUploads($_POST, $_FILES);
            $removeBackground = ((string) ($_POST['remove_background'] ?? '')) === '1';

            if ($entries === []) {
                throw new \RuntimeException('Minimal upload satu foto siswa.');
            }

            $movedPaths = [];
            $students = [];

            foreach ($entries as $entry) {
                $photoPath = Storage::storeImage($entry['file'], 'photos', [
                    'remove_background' => $removeBackground,
                ]);
                $movedPaths[] = $photoPath;
                $students[] = [
                    'student_name' => $entry['student_name'],
                    'photo_path' => $photoPath,
                ];
            }

            (new Student())->bulkCreate((int) $order['id'], $students);

            set_flash('success', count($students) . ' foto siswa berhasil ditambahkan.');
            redirect('/orders/' . rawurlencode($orderNo) . '/students');
        } catch (\Throwable $exception) {
            foreach ($movedPaths ?? [] as $path) {
                Storage::deletePublicFile($path);
            }

            set_flash('error', $exception->getMessage());
            redirect('/orders/' . rawurlencode($orderNo) . '/students');
        }
    }

    private function collectStudentUploads(array $post, array $files): array
    {
        $entries = [];
        $studentNames = $post['student_names'] ?? [];

        if (isset($files['photos'])) {
            foreach (Storage::normalizeFiles($files['photos']) as $index => $file) {
                if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $entries[] = [
                    'student_name' => $this->normalizeStudentName(
                        (string) ($studentNames[$index] ?? ''),
                        'Nama siswa wajib diisi untuk setiap foto pada baris upload.'
                    ),
                    'file' => $file,
                ];
            }
        }

        $bulkNamesRaw = trim((string) ($post['bulk_student_names'] ?? ''));
        $bulkNames = $bulkNamesRaw === ''
            ? []
            : array_map('trim', preg_split('/\R/u', $bulkNamesRaw) ?: []);

        if (isset($files['bulk_photos'])) {
            foreach (Storage::normalizeFiles($files['bulk_photos']) as $index => $file) {
                if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $studentName = $bulkNames[$index] ?? '';

                if ($studentName === '') {
                    $studentName = pathinfo((string) ($file['name'] ?? 'Siswa'), PATHINFO_FILENAME);
                    $studentName = trim(str_replace(['_', '-'], ' ', $studentName));
                }

                $entries[] = [
                    'student_name' => $this->normalizeStudentName(
                        $studentName,
                        'Nama siswa untuk upload massal tidak valid.'
                    ),
                    'file' => $file,
                ];
            }
        }

        if (count($entries) > $this->maxStudentUploads()) {
            throw new \RuntimeException('Maksimal upload ' . $this->maxStudentUploads() . ' foto siswa per submit.');
        }

        return $entries;
    }

    private function normalizeStudentName(string $name, string $emptyMessage): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new \RuntimeException($emptyMessage);
        }

        return function_exists('mb_substr') ? mb_substr($name, 0, 160) : substr($name, 0, 160);
    }

    private function maxStudentUploads(): int
    {
        $maxUploads = (int) env('MAX_STUDENT_UPLOADS', self::DEFAULT_MAX_STUDENT_UPLOADS);

        return max(1, min($maxUploads, 5000));
    }
}
