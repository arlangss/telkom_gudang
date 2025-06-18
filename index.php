<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'koneksi.php';

function query($koneksi, $query, $types = '', ...$params) {
    $stmt = $koneksi->prepare($query);
    if (!$stmt) die("❌ Query error: " . $koneksi->error);
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nama_barang = $_POST['nama_barang'];
    $jumlah = (int)$_POST['jumlah'];
    $jenis = $_POST['jenis'];
    $nama_pengambil = $_POST['nama_pengambil'];

    if ($id) {
        if ($jenis === 'keluar') {
            query($koneksi, "UPDATE pea SET nama_barang=?, jumlah=?, jenis=?, nama_pengambil=?, waktu=NOW() WHERE id=?", "sissi", $nama_barang, $jumlah, $jenis, $nama_pengambil, $id);
        } else {
            query($koneksi, "UPDATE pea SET nama_barang=?, jumlah=?, jenis=?, nama_pengambil=? WHERE id=?", "sissi", $nama_barang, $jumlah, $jenis, $nama_pengambil, $id);
        }
    } else {
        if ($jenis === 'keluar') {
            query($koneksi, "INSERT INTO pea (nama_barang, jumlah, jenis, nama_pengambil, waktu) VALUES (?, ?, ?, ?, NOW())", "siss", $nama_barang, $jumlah, $jenis, $nama_pengambil);
        } else {
            query($koneksi, "INSERT INTO pea (nama_barang, jumlah, jenis, nama_pengambil) VALUES (?, ?, ?, ?)", "siss", $nama_barang, $jumlah, $jenis, $nama_pengambil);
        }
    }

    header("Location: index.php");
    exit;
}

if (isset($_GET['hapus'])) {
    query($koneksi, "DELETE FROM pea WHERE id=?", "i", $_GET['hapus']);
    header("Location: index.php");
    exit;
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $result = query($koneksi, "SELECT * FROM pea WHERE id=?", "i", $_GET['edit'])->get_result();
    $edit_data = $result->fetch_assoc();
}

$result = query($koneksi, "SELECT * FROM pea ORDER BY id DESC")->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

$stok_awal = [
    'Kabel UTP' => 100,
    'Modem ZTE' => 50,
    'Baut Fiber' => 80,
    'Kabel FO' => 70,
    'Switch Hub' => 40
];

$stok_akhir = $stok_awal;
foreach ($data as $row) {
    $nama = $row['nama_barang'];
    $jumlah = (int)$row['jumlah'];
    $jenis = $row['jenis'];
    if (!isset($stok_akhir[$nama])) $stok_akhir[$nama] = 0;
    $stok_akhir[$nama] += ($jenis === 'masuk') ? $jumlah : -$jumlah;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>STOCK GUDANG by ARLANGSS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="malam">
    <h1>STOCK GUDANG by ARLANGSS</h1>
    <div class="mode-switcher">
        <button onclick="document.body.className='malam'">🌙 Malam</button>
        <button onclick="document.body.className='kece'">😎 Kece</button>
        <button onclick="document.body.className='cool'">🧊 Cool</button>
    </div>

    <form method="post">
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
        <select name="nama_barang" required>
            <option value="">-- Pilih Barang --</option>
            <?php foreach ($stok_awal as $nama => $stok): ?>
                <option value="<?= htmlspecialchars($nama) ?>" <?= (isset($edit_data) && $edit_data['nama_barang'] === $nama) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($nama) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="jumlah" placeholder="Jumlah" min="1" required value="<?= $edit_data['jumlah'] ?? '' ?>">
        <select name="jenis" required>
            <option value="masuk" <?= (isset($edit_data) && $edit_data['jenis'] === 'masuk') ? 'selected' : '' ?>>Masuk</option>
            <option value="keluar" <?= (isset($edit_data) && $edit_data['jenis'] === 'keluar') ? 'selected' : '' ?>>Keluar</option>
        </select>
        <input type="text" name="nama_pengambil" placeholder="Nama Pengambil" required value="<?= $edit_data['nama_pengambil'] ?? '' ?>">
        <button type="submit"><?= $edit_data ? 'Update' : 'Simpan' ?></button>
    </form>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Jenis</th>
                <th>Nama Pengambil</th>
                <th>Waktu</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($data as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                    <td><?= $row['jumlah'] ?></td>
                    <td><?= htmlspecialchars($row['jenis']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pengambil']) ?></td>
                    <td><?= ($row['jenis'] === 'keluar') ? $row['waktu'] : '-' ?></td>
                    <td>
                        <a href="?edit=<?= $row['id'] ?>">Edit</a> |
                        <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin hapus?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="grid">
        <?php foreach ($stok_akhir as $barang => $stok): ?>
            <div class="grid-item">
                <h3><?= htmlspecialchars($barang) ?></h3>
                <p><?= $stok ?> unit</p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
