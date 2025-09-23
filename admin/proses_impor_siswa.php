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
        $_SESSION['success'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            ❌ File tidak diupload!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
        header("Location: impor_siswa.php");
        exit;
    }

    $file = $_FILES['file_excel'];
    $allowed_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['success'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            ❌ Hanya file .xlsx yang diizinkan!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
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
            $_SESSION['success'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                ❌ Format kolom salah! Harus: NIS, Nama, Kelas
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
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
            // Skip baris kosong
            if (empty($row[0]) && empty($row[1]) && empty($row[2])) continue;

            $nis = trim($row[0] ?? '');
            $nama = trim($row[1] ?? '');
            $nama_kelas = trim($row[2] ?? '');

            // Validasi
            if (empty($nis) || empty($nama) || empty($nama_kelas)) {
                $gagal++;
                $log_error[] = "Baris " . ($index + 2) . ": Data tidak lengkap (NIS/Nama/Kelas kosong)";
                continue;
            }

            if (!isset($kelas_valid[$nama_kelas])) {
                $gagal++;
                $log_error[] = "Baris " . ($index + 2) . ": Kelas '$nama_kelas' tidak ditemukan di database";
                continue;
            }

            $kelas_id = $kelas_valid[$nama_kelas];

            // Cek duplikat NIS
            $stmt = $conn->prepare("SELECT id FROM siswa WHERE nis = ?");
            $stmt->execute([$nis]);
            if ($stmt->fetch()) {
                $gagal++;
                $log_error[] = "Baris " . ($index + 2) . ": NIS '$nis' sudah terdaftar";
                continue;
            }

            // Simpan ke database
            $stmt = $conn->prepare("INSERT INTO siswa (nis, nama, kelas_id) VALUES (?, ?, ?)");
            $stmt->execute([$nis, $nama, $kelas_id]);
            $sukses++;
        }

        // Set pesan sukses/error
        if ($gagal == 0 && $sukses > 0) {
            $_SESSION['success'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                ✅ ' . $sukses . ' data siswa berhasil diimpor.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        } elseif ($sukses > 0 && $gagal > 0) {
            $pesan_error = !empty($log_error) ? '<ul class="mb-0">' . implode('', array_map(fn($e) => '<li>' . htmlspecialchars($e) . '</li>', $log_error)) . '</ul>' : '';
            $_SESSION['success'] = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5><i class="icon fas fa-exclamation-triangle"></i> Sebagian Berhasil!</h5>
                ✅ Berhasil: ' . $sukses . ' data<br>
                ❌ Gagal: ' . $gagal . ' data<br>
                ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        } else {
            $pesan_error = !empty($log_error) ? '<ul class="mb-0">' . implode('', array_map(fn($e) => '<li>' . htmlspecialchars($e) . '</li>', $log_error)) . '</ul>' : '';
            $_SESSION['success'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5><i class="icon fas fa-ban"></i> Gagal!</h5>
                ❌ Tidak ada data yang berhasil diimpor.<br>
                ' . $pesan_error . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
        }

    } catch (Exception $e) {
        $_SESSION['success'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            ❌ Terjadi kesalahan: ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }

    header("Location: impor_siswa.php");
    exit;
}
?>