<?php
// cron_notifikasi_grup_wa.php
include 'koneksi.php';

// Konfigurasi Twilio
$account_sid = 'YOUR_TWILIO_ACCOUNT_SID';
$auth_token = 'YOUR_TWILIO_AUTH_TOKEN';
$twilio_number = 'whatsapp:+14155238886'; // Nomor Twilio Sandbox

// Ganti dengan nomor grup WhatsApp Anda
// Format: whatsapp:+628123456789-1579910777 (untuk grup)
// Cara dapatkan ID grup: https://www.twilio.com/docs/whatsapp/api/groups
$group_wa = 'whatsapp:YOUR_GROUP_ID_HERE';

// URL Twilio API
$url = 'https://api.twilio.com/2010-04-01/Accounts/' . $account_sid . '/Messages.json';

// Ambil daftar guru yang belum isi jurnal hari ini
$sql = "SELECT u.nama
        FROM users u
        JOIN guru g ON u.id = g.user_id
        WHERE u.role = 'guru'
        AND NOT EXISTS (
            SELECT 1 FROM jurnal j 
            WHERE j.guru_id = g.id 
            AND j.tanggal = CURDATE()
        )
        ORDER BY u.nama";

$stmt = $conn->prepare($sql);
$stmt->execute();
$guru_belum_isi = $stmt->fetchAll();

// Buat daftar guru
$daftar_guru = "";
if (count($guru_belum_isi) > 0) {
    $daftar_guru = "\n\n📌 *Daftar Guru yang Belum Isi Jurnal:*\n";
    $no = 1;
    foreach ($guru_belum_isi as $guru) {
        $daftar_guru .= "$no. " . $guru['nama'] . "\n";
        $no++;
    }
} else {
    $daftar_guru = "\n\n✅ Semua guru sudah mengisi jurnal hari ini!";
}

// Pesan lengkap
$pesan = "🔔 *PENGINGAT JURNAL MENGAJAR - " . date('d M Y') . "*\n\nBapak/Ibu Guru yang terhormat.\n\nIni adalah pengingat untuk mengisi jurnal mengajar hari ini sebelum pukul 17.00." . $daftar_guru . "\n\nTerima kasih.\n\n*Bot-Jurnal Mengajar SMA*";

// Kirim via Twilio
$data = [
    'To' => $group_wa,
    'From' => $twilio_number,
    'Body' => $pesan
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Simpan log
$log = [
    'tanggal' => date('Y-m-d H:i:s'),
    'status' => $http_code == 201 ? 'Berhasil' : 'Gagal',
    'jumlah_guru_belum_isi' => count($guru_belum_isi),
    'response' => $response
];

file_put_contents('logs/notifikasi_grup_wa_' . date('Y-m-d') . '.log', json_encode($log, JSON_PRETTY_PRINT));

echo "✅ Notifikasi terkirim ke grup WhatsApp.\n";
?>