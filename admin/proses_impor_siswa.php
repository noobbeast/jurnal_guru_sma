<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Load PhpSpreadsheet
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] != 0) {
        $_SESSION['success'] = "Error: File tidak diupload!";
        header("Location: impor_siswa.php");
        exit;
    }

    $file = $_FILES['file_excel'];
    $allowed_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['success'] = "Error: Hanya file .xlsx yang diizinkan!";
        header("Location: impor_siswa.php");
        exit;
    }

    try {
        // Load file Excel
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Ambil header (baris pertama)
        $header = array_shift($rows);
        if ($header[0] != 'NIS' || $header[1] != 'Nama' || $header[2] != 'Kelas') {
            $_SESSION['success'] = "Error: Format kolom salah! Harus: NIS, Nama, Kelas";
            header("Location: impor_siswa.php");
            exit;
        }

        // Ambil daftar kelas yang valid
        $stmt_kelas = $conn->prepare("SELECT id, nama_kelas FROM kelas");
        $stmt_kelas->execute();
        $kelas_valid = [];
        while ($row = $stmt_kelas->fetch()) {
            $kelas_valid[$row['nama_kelas']] = $row['id'];
        }

        $sukses = 0;
        $gagal = 0;
        $log_error = [];

        foreach ($rows as $index => $row) {
            if (empty($row[0]) && empty($row[1]) && empty($row[2])) continue; // Skip baris kosong

            $nis = trim($row[0] ?? '');
            $nama = trim($row[1] ?? '');
            $nama_kelas = trim($row[2] ?? '');

            // Validasi
            if (empty($nis) || empty($nama) || empty($nama_kelas)) {
                $gagal++;
                $log_error[] = "Baris " . ($index + 2) . ": Data tidak lengkap";
                continue;
            }

            if (!isset($kelas_valid[$nama_kelas])) {
                $gagal++;
                $log_error[] = "Baris " . ($index + 2) . ": Kelas '$nama_kelas' tidak ditemukan";
                continue;
            }

            $kelas_id = $kelas_valid[$nama_kelas];

            // Cek duplikat NIS
            $stmt = $conn->prepare("SELECT id FROM siswa WHERE nis = ?");
            $stmt->execute([$nis]);
            if ($stmt->fetch()) {
                $gagal++;
                $log_error[] = "Baris " . ($index + 2) . ": NIS '$nis' sudah ada";
                continue;
            }

            // Simpan ke database
            $stmt = $conn->prepare("INSERT INTO siswa (nis, nama, kelas_id) VALUES (?, ?, ?)");
            $stmt->execute([$nis, $nama, $kelas_id]);
            $sukses++;
        }

        $_SESSION['success'] = "✅ Impor selesai! Berhasil: $sukses, Gagal: $gagal";
        if (!empty($log_error)) {
            $_SESSION['success'] .= "<br><small>" . implode('<br>', $log_error) . "</small>";
        }

    } catch (Exception $e) {
        $_SESSION['success'] = "Error: " . $e->getMessage();
    }

    header("Location: impor_siswa.php");
    exit;
}
?>