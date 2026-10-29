<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit;
}

$invoice_terbaru = "";
$total_bayar_terbaru = 0;
$nama_pelanggan_baru = "";
$no_hp_baru = "";
$layanan_baru = "";
$jumlah_baru = 0;
$status_bayar_baru = "";
$kurir_baru = "";
$tampilkan_struk = false;

$q_cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi");
$fields_trx = [];
while($f = mysqli_fetch_assoc($q_cek_kolom)){
    $fields_trx[] = $f['Field'];
}
if (!in_array('kurir', $fields_trx)) {
    mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN kurir VARCHAR(100) DEFAULT NULL");
}

if (isset($_POST['tambah_layanan'])) {
    $nama_layanan = mysqli_real_escape_string($koneksi, $_POST['nama_layanan']);
    $kategori     = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $harga        = floatval($_POST['harga']);
    $satuan       = mysqli_real_escape_string($koneksi, $_POST['satuan']);

    mysqli_query($koneksi, "INSERT INTO layanan (nama_layanan, kategori, harga, satuan) VALUES ('$nama_layanan', '$kategori', '$harga', '$satuan')");
}

if (isset($_GET['hapus_layanan'])) {
    $id_layanan = intval($_GET['hapus_layanan']);
    mysqli_query($koneksi, "DELETE FROM layanan WHERE id_layanan = '$id_layanan'");
    header("location: layanan.php");
    exit;
}

if (isset($_POST['tambah_pesanan_manual'])) {
    $invoice_baru   = "TRX-" . rand(100000000, 999999999);
    $invoice_terbaru = $invoice_baru;
    
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $id_layanan     = intval($_POST['id_layanan']);
    $jumlah         = floatval($_POST['jumlah']);
    $pake_kurir     = $_POST['pake_kurir']; 
    $kurir          = mysqli_real_escape_string($koneksi, $_POST['kurir']);
    $catatan        = mysqli_real_escape_string($koneksi, $_POST['catatan']);
    $status_bayar   = mysqli_real_escape_string($koneksi, $_POST['status_bayar']);

    $nama_pelanggan_baru = $nama_pelanggan;
    $no_hp_baru = $no_hp;
    $jumlah_baru = $jumlah;
    $status_bayar_baru = $status_bayar;
    $kurir_baru = ($pake_kurir == 'ya') ? $kurir : 'Mandiri (Tanpa Kurir)';
    $tampilkan_struk = true;

    $q_lyn = mysqli_query($koneksi, "SELECT * FROM layanan WHERE id_layanan = '$id_layanan'");
    $d_lyn = mysqli_fetch_assoc($q_lyn);
    $harga_satuan = $d_lyn ? $d_lyn['harga'] : 0;
    $layanan_baru = $d_lyn ? $d_lyn['nama_layanan'] : 'Layanan Laundry';
    
    $subtotal = $harga_satuan * $jumlah;
    $biaya_kurir = ($pake_kurir == 'ya') ? 5000 : 0;
    $total_bayar = $subtotal + $biaya_kurir;
    $total_bayar_terbaru = $total_bayar;

    $q_plg = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE no_hp = '$no_hp'");
    if (mysqli_num_rows($q_plg) > 0) {
        $d_plg = mysqli_fetch_assoc($q_plg);
        $id_pelanggan = $d_plg['id_pelanggan'];
    } else {
        mysqli_query($koneksi, "INSERT INTO pelanggan (nama_pelanggan, no_hp, alamat) VALUES ('$nama_pelanggan', '$no_hp', '$alamat')");
        $id_pelanggan = mysqli_insert_id($koneksi);
    }

    $tgl_sekarang = date('Y-m-d H:i:s');
    $estimasi_selesai = date('Y-m-d H:i:s', strtotime('+3 days'));

    $sql_kolom = [];
    $sql_nilai = [];

    $kolom_inv_nama = in_array('kode_invoice', $fields_trx) ? 'kode_invoice' : 'invoice';
    if (in_array($kolom_inv_nama, $fields_trx)) { $sql_kolom[] = $kolom_inv_nama; $sql_nilai[] = "'$invoice_baru'"; }
    if (in_array('id_pelanggan', $fields_trx)) { $sql_kolom[] = 'id_pelanggan'; $sql_nilai[] = "'$id_pelanggan'"; }
    if (in_array('id_layanan', $fields_trx)) { $sql_kolom[] = 'id_layanan'; $sql_nilai[] = "'$id_layanan'"; }
    if (in_array('berat', $fields_trx)) { $sql_kolom[] = 'berat'; $sql_nilai[] = "'$jumlah'"; }
    if (in_array('total_bayar', $fields_trx)) { $sql_kolom[] = 'total_bayar'; $sql_nilai[] = "'$total_bayar'"; }
    if (in_array('status_pesanan', $fields_trx)) { $sql_kolom[] = 'status_pesanan'; $sql_nilai[] = "'Baru'"; }
    if (in_array('status', $fields_trx) && !in_array('status_pesanan', $fields_trx)) { $sql_kolom[] = 'status'; $sql_nilai[] = "'Baru'"; }
    if (in_array('status_bayar', $fields_trx)) { $sql_kolom[] = 'status_bayar'; $sql_nilai[] = "'$status_bayar'"; }
    if (in_array('kurir', $fields_trx)) { $sql_kolom[] = 'kurir'; $sql_nilai[] = "'$kurir_baru'"; }
    if (in_array('catatan', $fields_trx)) { $sql_kolom[] = 'catatan'; $sql_nilai[] = "'$catatan'"; }
    elseif (in_array('keterangan', $fields_trx)) { $sql_kolom[] = 'keterangan'; $sql_nilai[] = "'$catatan'"; }
    if (in_array('tgl_terima', $fields_trx)) { $sql_kolom[] = 'tgl_terima'; $sql_nilai[] = "'$tgl_sekarang'"; }
    elseif (in_array('tgl_masuk', $fields_trx)) { $sql_kolom[] = 'tgl_masuk'; $sql_nilai[] = "'$tgl_sekarang'"; }
    elseif (in_array('tanggal', $fields_trx)) { $sql_kolom[] = 'tanggal'; $sql_nilai[] = "'$tgl_sekarang'"; }
    if (in_array('batas_waktu', $fields_trx)) { $sql_kolom[] = 'batas_waktu'; $sql_nilai[] = "'$estimasi_selesai'"; }

    $str_kolom = implode(", ", $sql_kolom);
    $str_nilai = implode(", ", $sql_nilai);

    mysqli_query($koneksi, "INSERT INTO transaksi ($str_kolom) VALUES ($str_nilai)");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Services & Orders - R&F Express</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #0B0F19;
            --bg-card: #131B2E;
            --bg-sidebar: #0C101E;
            --text-muted: #94A3B8;
            --text-light: #F8FAFC;
            --accent-blue: #3B82F6;
            --accent-purple: #8B5CF6;
            --accent-pink: #EC4899;
            --accent-success: #10B981;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; display: flex; background-color: var(--bg-main); color: var(--text-light); }
        .sidebar { width: 260px; background: var(--bg-sidebar); color: var(--text-muted); height: 100vh; padding: 25px 20px; box-sizing: border-box; display: flex; flex-direction: column; flex-shrink: 0; position: fixed; border-right: 1px solid var(--border-color); }
        .sidebar h2 { margin-top: 0; color: #ffffff; font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 10px; margin-bottom: 35px; }
        .sidebar h2 i { color: var(--accent-blue); }
        .sidebar-menu { flex: 1; }
        .sidebar a { color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 13px 18px; margin-bottom: 6px; border-radius: 12px; transition: all 0.2s ease; font-size: 14px; font-weight: 600; }
        .sidebar a:hover, .sidebar a.active { background: linear-gradient(135deg, #3B82F6, #1D4ED8); color: #ffffff; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .sidebar a.logout { background: rgba(239, 68, 68, 0.1); color: #F87171; margin-top: auto; }
        .sidebar a.logout:hover { background: #EF4444; color: #ffffff; }
        .content { margin-left: 260px; padding: 30px 40px; flex: 1; box-sizing: border-box; min-height: 100vh; overflow-y: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-title h1 { margin: 0; font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; }
        .header-title p { margin: 4px 0 0 0; font-size: 13px; color: var(--text-muted); }
        .admin-pill { background: var(--bg-card); padding: 6px 16px; border-radius: 50px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 13px; }
        .admin-pill .avatar { width: 30px; height: 30px; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }
        .management-grid { display: grid; grid-template-columns: 380px 1fr; gap: 25px; align-items: flex-start; }
        .left-column { display: flex; flex-direction: column; gap: 20px; }
        .card-box { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-color); padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card-box h3 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px; }
        .card-box p.subtitle { margin: 0 0 20px 0; font-size: 12px; color: var(--text-muted); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; box-sizing: border-box; background-color: #0B0F19; color: #ffffff; transition: 0.2s; }
        .form-control:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #3B82F6, #1D4ED8); color: #ffffff; border: none; border-radius: 12px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .btn-submit:hover { opacity: 0.9; }
        .btn-outline { width: 100%; padding: 12px; background: rgba(59, 130, 246, 0.1); color: #60A5FA; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; }
        .btn-outline:hover { background: var(--accent-blue); color: white; }
        .table-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { background: rgba(255,255,255,0.03); color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.8px; padding: 14px 16px; border-bottom: 1px solid var(--border-color); }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: #ffffff; font-weight: 500; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        .badge-kat { padding: 4px 10px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; display: inline-block; background: rgba(59, 130, 246, 0.15); color: #60A5FA; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-kat.kiloan { background: rgba(139, 92, 246, 0.15); color: #C084FC; border: 1px solid rgba(139, 92, 246, 0.3); }
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; display: inline-block; background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .action-btns { display: flex; gap: 8px; align-items: center; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .action-btn:hover { background: var(--accent-blue); color: white; border-color: var(--accent-blue); }
        .action-btn.delete:hover { background: #EF4444; border-color: #EF4444; }
        .modal-overlay { display: <?php echo $tampilkan_struk ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 15, 25, 0.8); backdrop-filter: blur(6px); z-index: 2000; justify-content: center; align-items: center; }
        .modal-box { background: var(--bg-card); width: 100%; max-width: 440px; padding: 30px; border-radius: 24px; border: 1px solid var(--border-color); box-shadow: 0 20px 50px rgba(0,0,0,0.5); position: relative; text-align: center; }
        .nota-struk-box { background: #0B0F19; border: 1.5px dashed var(--border-color); padding: 18px; border-radius: 16px; text-align: left; margin: 15px 0; font-size: 13px; color: #ffffff; }
        .nota-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .nota-row span:first-child { color: var(--text-muted); font-weight: 500; }
        .nota-row span:last-child { color: #ffffff; font-weight: 700; text-align: right; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-truck-fast"></i> R&F Express</h2>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fa-solid fa-layer-group"></i> Dashboard & Keuangan</a>
            <a href="pelanggan.php"><i class="fa-regular fa-address-book"></i> Customers</a>
            <a href="layanan.php" class="active"><i class="fa-solid fa-tags"></i> Services & Orders</a>
            <a href="papan.php"><i class="fa-solid fa-chart-gantt"></i> Kanban Board</a>
            <a href="pengeluaran.php"><i class="fa-solid fa-wallet"></i> Expense Tracker</a>
        </div>
        <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
    </div>
    
    <div class="content">
        <div class="top-bar">
            <div class="header-title">
                <h1>Services & Manual Orders</h1>
                <p>Kelola daftar layanan dan buat pesanan manual dengan kurir penjemputan.</p>
            </div>
            <div class="admin-pill">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <span><?php echo ucfirst($_SESSION['username']); ?></span>
            </div>
        </div>

        <div class="management-grid">
            <div class="left-column">
                <div class="card-box">
                    <h3><i class="fa-solid fa-cart-plus" style="color: #60A5FA;"></i> Input Pesanan Manual</h3>
                    <p class="subtitle">Masukan data pelanggan, layanan, dan kurir jemput.</p>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Nama Lengkap Pelanggan</label>
                            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="form-group">
                            <label>No. WhatsApp</label>
                            <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 08123456789" required>
                        </div>
                        <div class="form-group">
                            <label>Alamat / Keterangan Penjemputan</label>
                            <textarea name="alamat" class="form-control" placeholder="Contoh: Jl. Melati No. 10" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Pilih Layanan</label>
                            <select name="id_layanan" id="pilih_layanan" class="form-control" onchange="hitungTotalKasir()" required>
                                <option value="">-- Pilih Layanan --</option>
                                <?php
                                $q_opt = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY nama_layanan ASC");
                                while($opt = mysqli_fetch_assoc($q_opt)){
                                    echo "<option value='".$opt['id_layanan']."' data-harga='".$opt['harga']."'>".$opt['nama_layanan']." (Rp ".number_format($opt['harga'],0,',','.')." / ".$opt['satuan'].")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah (KG / PCS / UNIT)</label>
                            <input type="number" step="0.1" name="jumlah" id="jumlah_input" class="form-control" placeholder="Contoh: 2" oninput="hitungTotalKasir()" required>
                        </div>

                        <div class="form-group">
                            <label>Kurir Antar-Jemput (+Rp 5.000)</label>
                            <select name="pake_kurir" id="pake_kurir" class="form-control" onchange="hitungTotalKasir()" required>
                                <option value="tidak">Tidak (Drop-off Langsung)</option>
                                <option value="ya">Ya (Antar-Jemput)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tugaskan Kurir</label>
                            <select name="kurir" class="form-control" required>
                                <option value="Budi (Kurir 1)">Budi (Kurir 1)</option>
                                <option value="Joko (Kurir 2)">Joko (Kurir 2)</option>
                                <option value="Rian (Kurir 3)">Rian (Kurir 3)</option>
                            </select>
                        </div>

                        <div id="kotak-total-kasir" style="background: rgba(59, 130, 246, 0.08); border: 1.5px dashed rgba(59, 130, 246, 0.3); padding: 14px 18px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 700; color: #ffffff; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fa-solid fa-cash-register" style="color: #60A5FA;"></i> Total Kasir:</span>
                            <span id="label-total-harga" style="color: #34D399; font-size: 16px;">Rp 0</span>
                        </div>

                        <div class="form-group">
                            <label>Status Pembayaran</label>
                            <select name="status_bayar" class="form-control" required>
                                <option value="Belum Lunas">Belum Lunas</option>
                                <option value="Lunas">Lunas</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Catatan Khusus</label>
                            <textarea name="catatan" class="form-control" placeholder="Contoh: Pakaian bayi, putih" rows="2"></textarea>
                        </div>
                        <button type="submit" name="tambah_pesanan_manual" class="btn-submit">
                            <i class="fa-solid fa-paper-plane"></i> Buat & Generate Invoice
                        </button>
                    </form>
                </div>

                <div class="card-box">
                    <h3><i class="fa-solid fa-tags" style="color: #C084FC;"></i> Tambah Pricelist</h3>
                    <p class="subtitle">Tambahkan layanan baru ke sistem.</p>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Nama Layanan</label>
                            <input type="text" name="nama_layanan" class="form-control" placeholder="Contoh: Cuci Kilat" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="kategori" class="form-control" required>
                                <option value="SATUAN">SATUAN</option>
                                <option value="KILOAN">KILOAN</option>
                                <option value="EXPRESS">EXPRESS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (Rp)</label>
                            <input type="number" name="harga" class="form-control" placeholder="Contoh: 35000" required>
                        </div>
                        <div class="form-group">
                            <label>Unit</label>
                            <input type="text" name="satuan" class="form-control" placeholder="Contoh: pcs / kg" required>
                        </div>
                        <button type="submit" name="tambah_layanan" class="btn-outline">
                            <i class="fa-solid fa-plus"></i> + Tambah Layanan
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-box" style="padding: 25px;">
                <div class="table-toolbar">
                    <div>
                        <h3 style="margin: 0;">Daftar Layanan & Harga</h3>
                        <p class="subtitle" style="margin: 2px 0 0 0;">Kelola semua layanan laundry yang tersedia.</p>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Layanan</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th style="width: 80px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_lyn = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY id_layanan DESC");
                        if ($q_lyn && mysqli_num_rows($q_lyn) > 0) {
                            $no = 1;
                            while ($l = mysqli_fetch_assoc($q_lyn)) {
                                $kat_class = (strtoupper($l['kategori']) == 'KILOAN') ? 'badge-kat kiloan' : 'badge-kat';
                        ?>
                        <tr>
                            <td><b><?php echo $no++; ?></b></td>
                            <td><b><?php echo htmlspecialchars($l['nama_layanan']); ?></b></td>
                            <td><span class="<?php echo $kat_class; ?>"><?php echo htmlspecialchars($l['kategori']); ?></span></td>
                            <td><b style="color: #34D399;">Rp <?php echo number_format($l['harga'], 0, ',', '.'); ?></b></td>
                            <td><?php echo htmlspecialchars($l['satuan']); ?></td>
                            <td><span class="badge-status"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Aktif</span></td>
                            <td>
                                <div class="action-btns" style="justify-content: center;">
                                    <a href="layanan.php?hapus_layanan=<?php echo $l['id_layanan']; ?>" onclick="return confirm('Yakin ingin menghapus layanan ini?')" class="action-btn delete" title="Hapus Layanan"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; color: var(--text-muted); padding:30px;'>Belum ada layanan terdaftar.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin: 0 0 5px 0; color: #34D399; justify-content: center;"><i class="fa-solid fa-circle-check"></i> Pesanan Berhasil Dibuat!</h3>
            <p style="margin: 0; font-size: 12.5px; color: var(--text-muted);">Invoice diterbitkan dan masuk ke Kanban Board.</p>
            
            <div class="nota-struk-box">
                <div style="font-size: 13.5px; font-weight: 800; color: #60A5FA; text-align: center; margin-bottom: 12px; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px;">
                    INVOICE: <?php echo $invoice_terbaru; ?>
                </div>
                <div class="nota-row"><span>Nama Pelanggan</span><span><?php echo htmlspecialchars($nama_pelanggan_baru); ?></span></div>
                <div class="nota-row"><span>No. WhatsApp</span><span><?php echo htmlspecialchars($no_hp_baru); ?></span></div>
                <div class="nota-row"><span>Layanan</span><span><?php echo htmlspecialchars($layanan_baru); ?></span></div>
                <div class="nota-row"><span>Jumlah</span><span><?php echo $jumlah_baru; ?> Unit</span></div>
                <div class="nota-row"><span>Kurir Bertugas</span><span style="color: #60A5FA;"><?php echo htmlspecialchars($kurir_baru); ?></span></div>
                <div class="nota-row"><span>Status Bayar</span><span style="color: #FBBF24;"><?php echo htmlspecialchars($status_bayar_baru); ?></span></div>
                <div class="nota-row" style="border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 8px;">
                    <span style="font-weight: 700; color: #ffffff;">Total Tagihan</span>
                    <span style="font-size: 15px; color: #34D399;">Rp <?php echo number_format($total_bayar_terbaru, 0, ',', '.'); ?></span>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="window.print()" class="btn-submit" style="background: linear-gradient(135deg, #10B981, #059669);">
                    <i class="fa-solid fa-print"></i> Cetak Struk
                </button>
                <a href="layanan.php" class="btn-submit" style="background: linear-gradient(135deg, #3B82F6, #1D4ED8); text-decoration: none;">
                    <i class="fa-solid fa-check"></i> Selesai
                </a>
            </div>
        </div>
    </div>

    <script>
        function hitungTotalKasir() {
            let selectLayanan = document.getElementById('pilih_layanan');
            let selectedOption = selectLayanan.options[selectLayanan.selectedIndex];
            
            let hargaSatuan = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
            let jumlah = parseFloat(document.getElementById('jumlah_input').value) || 0;
            let pakeKurir = document.getElementById('pake_kurir').value;
            
            let subtotal = hargaSatuan * jumlah;
            let biayaKurir = (pakeKurir === 'ya') ? 5000 : 0;
            let total = subtotal + biayaKurir;
            
            document.getElementById('label-total-harga').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }
    </script>
</body>
</html>