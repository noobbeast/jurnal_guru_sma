<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Fungsi auto-detect tahun ajaran & semester
function getTahunAjaranSemester() {
    $bulan = date('n'); // 1-12
    $tahun = date('Y');
    
    if ($bulan >= 7) {
        return [
            'tahun_ajaran' => "$tahun/" . ($tahun + 1),
            'semester' => 'Ganjil'
        ];
    } else {
        return [
            'tahun_ajaran' => ($tahun - 1) . "/$tahun",
            'semester' => 'Genap'
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    [$kelas_id, $mapel_id] = explode('|', $_POST['gmk_id']);
    $materi = $_POST['materi'];
    $catatan = $_POST['catatan'] ?? '';
    $kegiatan = $_POST['kegiatan'] ?? [];
    
    // Ambil tahun ajaran dan semester secara otomatis
    $setting = getTahunAjaranSemester();
    $tahun_ajaran = $_POST['tahun_ajaran'] ?? $setting['tahun_ajaran'];
    $semester = $_POST['semester'] ?? $setting['semester'];

    // Handle upload foto
    $foto_kegiatan = null;
    if (isset($_FILES['foto_kegiatan']) && $_FILES['foto_kegiatan']['error'] == 0) {
        $file = $_FILES['foto_kegiatan'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'jurnal_' . $_SESSION['guru_id'] . '_' . date('Ymd') . '_' . uniqid() . '.' . $ext;
            $target_path = '../uploads/' . $new_name;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $foto_kegiatan = $new_name;
            }
        }
    }

    // Ambil jam_ke (opsional)
    $jam_ke = null;
    if (!empty($_POST['jam_ke']) && is_array($_POST['jam_ke'])) {
        // Urutkan dan gabung jadi string: "3,4,5"
        $jam_list = array_map('intval', $_POST['jam_ke']); // pastikan integer
        sort($jam_list); // urutkan ascending
        $jam_ke = implode(',', $jam_list); // jadi "3,4,5"
    }

    // Inisialisasi variabel kegiatan
    $pendahuluan = $kegiatan['pendahuluan'] ?? '';
    $inti = $kegiatan['inti'] ?? '';
    $penutup = $kegiatan['penutup'] ?? '';

    // Simpan ke tabel jurnal — dengan tambahan tahun_ajaran dan semester
    $sql = "INSERT INTO jurnal (tanggal, jam_ke, guru_id, kelas_id, mapel_id, materi, catatan, 
        kegiatan_pendahuluan, kegiatan_inti, kegiatan_penutup, foto_kegiatan, tahun_ajaran, semester) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Pastikan session guru_id tersedia
    $guru_id = $_SESSION['guru_id'] ?? $_SESSION['user_id'];
    
    $stmt->execute([
        $tanggal, 
        $jam_ke, 
        $guru_id, 
        $kelas_id, 
        $mapel_id, 
        $materi, 
        $catatan, 
        $pendahuluan, 
        $inti, 
        $penutup,
        $foto_kegiatan,
        $tahun_ajaran,
        $semester
    ]);
    $jurnal_id = $conn->lastInsertId();

    // Simpan absensi — versi lengkap dengan status
    $absen_status = $_POST['absen_status'] ?? [];

    // Ambil daftar siswa di kelas yang dipilih
    $sql_siswa = "SELECT id FROM siswa WHERE kelas_id = ?";
    $stmt_siswa = $conn->prepare($sql_siswa);
    $stmt_siswa->execute([$kelas_id]);
    $result_siswa = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result_siswa as $siswa) {
        $siswa_id = $siswa['id'];
        // Jika tidak dipilih, default 'A' (Alfa)
        $status = $absen_status[$siswa_id] ?? 'A';

        // Validasi nilai status
        if (!in_array($status, ['H', 'S', 'I', 'A'])) {
            $status = 'A';
        }

        $sql_absen = "INSERT INTO absensi (jurnal_id, siswa_id, status) VALUES (?, ?, ?)";
        $stmt_absen = $conn->prepare($sql_absen);
        $stmt_absen->execute([$jurnal_id, $siswa_id, $status]);
    }

    $_SESSION['success'] = "Jurnal berhasil disimpan!";
    header("Location: daftar_jurnal.php");
    exit;
}
?>