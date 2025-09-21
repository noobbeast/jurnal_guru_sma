<?php
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $nip = $_POST['nip'] ?? null;

        // Insert ke users dulu
        $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'guru')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nama, $email, $password]);
        $user_id = $conn->lastInsertId();

        // Insert ke guru
        if ($nip) {
            $sql = "INSERT INTO guru (user_id, nip) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $nip]);
        }

        $_SESSION['success'] = "Guru berhasil ditambahkan!";
        header("Location: data_guru.php");
        exit;
    }

    if (isset($_POST['edit'])) {
        $user_id = $_POST['user_id'];
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $nip = $_POST['nip'] ?? null;

        // Update users
        $sql = "UPDATE users SET nama = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nama, $email, $user_id]);

        // Jika password diisi, update juga
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$password, $user_id]);
        }

        // Cek apakah guru sudah ada di tabel guru
        $sql = "SELECT id FROM guru WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $guru = $stmt->fetch();

        if ($guru) {
            // Update
            $sql = "UPDATE guru SET nip = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nip, $user_id]);
        } else {
            // Insert baru
            if ($nip) {
                $sql = "INSERT INTO guru (user_id, nip) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$user_id, $nip]);
            }
        }

        $_SESSION['success'] = "Data guru berhasil diubah!";
        header("Location: data_guru.php");
        exit;
    }
}

// Hapus
if (isset($_GET['hapus'])) {
    $user_id = $_GET['hapus'];

    // Hapus relasi dulu
    $sql = "DELETE FROM guru WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);

    // Hapus user
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);

    $_SESSION['success'] = "Guru berhasil dihapus!";
    header("Location: data_guru.php");
    exit;
}
?>