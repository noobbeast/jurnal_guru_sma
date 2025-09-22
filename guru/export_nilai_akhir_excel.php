<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$guru_id = $_SESSION['guru_id'];

// Ambil filter dari GET
$filter_kelas = $_GET['kelas_id'] ?? '';
$filter_mapel = $_GET['mapel_id'] ?? '';

// Query kelas & mapel (dengan filter)
$sql_kelas_mapel = "SELECT DISTINCT k.id as kelas_id, k.nama_kelas, m.id as mapel_id, m.nama_mapel
                    FROM guru_mapel_kelas gmk
                    JOIN kelas k ON gmk.kelas_id = k.id
                    JOIN mata_pelajaran m ON gmk.mapel_id = m.id
                    WHERE gmk.guru_id = ?";

$params = [$guru_id];

if ($filter_kelas) {
    $sql_kelas_mapel .= " AND k.id = ?";
    $params[] = $filter_kelas;
}

if ($filter_mapel) {
    $sql_kelas_mapel .= " AND m.id = ?";
    $params[] = $filter_mapel;
}

$sql_kelas_mapel .= " ORDER BY k.nama_kelas, m.nama_mapel";

$stmt = $conn->prepare($sql_kelas_mapel);
$stmt->execute($params);
$kelas_mapel_list = $stmt->fetchAll();

// Inisialisasi data rekap
$rekap_nilai = [];

foreach ($kelas_mapel_list as $km) {
    $kelas_id = $km['kelas_id'];
    $mapel_id = $km['mapel_id'];

    // Ambil semua siswa di kelas ini
    $sql_siswa = "SELECT id, nama FROM siswa WHERE kelas_id = ? ORDER BY nama";
    $stmt_siswa = $conn->prepare($sql_siswa);
    $stmt_siswa->execute([$kelas_id]);
    $siswa_list = $stmt_siswa->fetchAll();

    foreach ($siswa_list as $siswa) {
        // Ambil nilai per jenis penilaian
        $sql_nilai = "SELECT jenis_penilaian, AVG(nilai) as rata_rata
                      FROM nilai_siswa
                      WHERE guru_id = ? AND kelas_id = ? AND mapel_id = ? AND siswa_id = ?
                      GROUP BY jenis_penilaian";
        $stmt_nilai = $conn->prepare($sql_nilai);
        $stmt_nilai->execute([$guru_id, $kelas_id, $mapel_id, $siswa['id']]);
        $nilai_list = $stmt_nilai->fetchAll();

        // Hitung nilai akhir
        $nilai_harian = 0;
        $nilai_pts = 0;
        $nilai_pas = 0;
        $nilai_tugas = 0;

        foreach ($nilai_list as $n) {
            switch ($n['jenis_penilaian']) {
                case 'Harian': $nilai_harian = $n['rata_rata']; break;
                case 'PTS': $nilai_pts = $n['rata_rata']; break;
                case 'PAS': $nilai_pas = $n['rata_rata']; break;
                case 'Tugas': $nilai_tugas = $n['rata_rata']; break;
            }
        }

        // Hitung nilai akhir (bobot)
        $nilai_akhir = ($nilai_harian * 0.4) + ($nilai_pts * 0.2) + ($nilai_pas * 0.3) + ($nilai_tugas * 0.1);

        // Simpan ke array rekap
        $rekap_nilai[] = [
            'kelas' => $km['nama_kelas'],
            'mapel' => $km['nama_mapel'],
            'siswa' => $siswa['nama'],
            'harian' => number_format($nilai_harian, 2, ',', '.'),
            'pts' => number_format($nilai_pts, 2, ',', '.'),
            'pas' => number_format($nilai_pas, 2, ',', '.'),
            'tugas' => number_format($nilai_tugas, 2, ',', '.'),
            'akhir' => number_format($nilai_akhir, 2, ',', '.'),
            'grade' => getGrade($nilai_akhir)
        ];
    }
}

// Fungsi konversi nilai ke grade
function getGrade($nilai) {
    if ($nilai >= 85) return 'A';
    if ($nilai >= 75) return 'B';
    if ($nilai >= 65) return 'C';
    if ($nilai >= 55) return 'D';
    return 'E';
}

// Buat spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Judul
$sheet->setCellValue('A1', 'REKAP NILAI AKHIR SISWA');
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Subjudul
$sheet->setCellValue('A2', 'Guru: ' . ($_SESSION['nama'] ?? ''));
$sheet->setCellValue('A3', 'Tanggal Export: ' . date('d M Y'));

$rowSub = 4;
if ($filter_kelas) {
    $stmt_kelas = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->execute([$filter_kelas]);
    $kelas = $stmt_kelas->fetch();
    $sheet->setCellValue('A' . $rowSub, 'Kelas: ' . ($kelas['nama_kelas'] ?? ''));
    $rowSub++;
}
if ($filter_mapel) {
    $stmt_mapel = $conn->prepare("SELECT nama_mapel FROM mata_pelajaran WHERE id = ?");
    $stmt_mapel->execute([$filter_mapel]);
    $mapel = $stmt_mapel->fetch();
    $sheet->setCellValue('A' . $rowSub, 'Mapel: ' . ($mapel['nama_mapel'] ?? ''));
    $rowSub++;
}
$sheet->setCellValue('A' . $rowSub, ' ');

// Header tabel
$headers = ['Kelas', 'Mapel', 'Siswa', 'Harian (40%)', 'PTS (20%)', 'PAS (30%)', 'Tugas (10%)', 'Nilai Akhir', 'Grade'];
$colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

// Set header
$row = $rowSub + 1;
foreach ($headers as $index => $header) {
    $sheet->setCellValue($colLetters[$index] . $row, $header);
    $sheet->getStyle($colLetters[$index] . $row)->getFont()->setBold(true);
    $sheet->getColumnDimension($colLetters[$index])->setAutoSize(true);
    $sheet->getStyle($colLetters[$index] . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
         ->getStartColor()->setARGB('FFD9EAD3'); // Warna hijau muda
}

// Isi data
$row++;
foreach ($rekap_nilai as $data) {
    $sheet->setCellValue('A' . $row, $data['kelas']);
    $sheet->setCellValue('B' . $row, $data['mapel']);
    $sheet->setCellValue('C' . $row, $data['siswa']);
    $sheet->setCellValue('D' . $row, $data['harian']);
    $sheet->setCellValue('E' . $row, $data['pts']);
    $sheet->setCellValue('F' . $row, $data['pas']);
    $sheet->setCellValue('G' . $row, $data['tugas']);
    $sheet->setCellValue('H' . $row, $data['akhir']);
    $sheet->setCellValue('I' . $row, $data['grade']);
    $row++;
}

// Set alignment
$sheet->getStyle('A' . ($rowSub + 1) . ':I' . ($row - 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$sheet->getStyle('D' . ($rowSub + 1) . ':H' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Response ke browser
$filename = 'Rekap_Nilai_Akhir_' . ($_SESSION['nama'] ?? 'guru') . '_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>