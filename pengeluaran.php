<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit;
}

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengeluaran (
    id_pengeluaran INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE,
    kategori VARCHAR(100),
    keterangan TEXT,
    jumlah DECIMAL(12,2)
)");

if (isset($_POST['tambah_pengeluaran'])) {
    $tanggal    = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $kategori   = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    // Bersihkan format titik rupiah sebelum disimpan ke database (misal: "Rp 50.000" jadi "50000")
    $jumlah_raw = str_replace(['Rp', '.', ' '], '', $_POST['jumlah_raw']);
    $jumlah     = floatval($jumlah_raw);

    mysqli_query($koneksi, "INSERT INTO pengeluaran (tanggal, kategori, keterangan, jumlah) VALUES ('$tanggal', '$kategori', '$keterangan', '$jumlah')");
    header("location: pengeluaran.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id_pengeluaran = intval($_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM pengeluaran WHERE id_pengeluaran = '$id_pengeluaran'");
    header("location: pengeluaran.php");
    exit;
}

$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$q_exp = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM pengeluaran WHERE MONTH(tanggal) = '$filter_bulan' AND YEAR(tanggal) = '$filter_tahun'");
$d_exp = mysqli_fetch_assoc($q_exp);
$total_pengeluaran_bulan_ini = $d_exp['total'] ? $d_exp['total'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Expense Tracker - R&F Express</title>
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
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #EF4444, #B91C1C); color: #ffffff; border: none; border-radius: 12px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
        .btn-submit:hover { opacity: 0.9; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; }
        .filter-bar select { padding: 8px 14px; border: 1px solid var(--border-color); border-radius: 10px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12.5px; background: #0B0F19; color: #ffffff; outline: none; }
        .filter-bar button { padding: 8px 14px; background: var(--accent-blue); color: white; border: none; border-radius: 10px; font-size: 12.5px; font-weight: 700; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { background: rgba(255,255,255,0.03); color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.8px; padding: 14px 16px; border-bottom: 1px solid var(--border-color); }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: #ffffff; font-weight: 500; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        .badge-kategori { padding: 4px 10px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; display: inline-block; background: rgba(239, 68, 68, 0.15); color: #F87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .action-btn.delete:hover { background: #EF4444; color: white; border-color: #EF4444; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-truck-fast"></i> R&F Express</h2>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fa-solid fa-layer-group"></i> Dashboard & Keuangan</a>
            <a href="pelanggan.php"><i class="fa-regular fa-address-book"></i> Customers</a>
            <a href="layanan.php"><i class="fa-solid fa-tags"></i> Services & Orders</a>
            <a href="papan.php"><i class="fa-solid fa-chart-gantt"></i> Kanban Board</a>
            <a href="pengeluaran.php" class="active"><i class="fa-solid fa-wallet"></i> Expense Tracker</a>
        </div>
        <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
    </div>
    
    <div class="content">
        <div class="top-bar">
            <div class="header-title">
                <h1>Expense Tracker (Manajemen Pengeluaran)</h1>
                <p>Catat seluruh biaya operasional harian untuk menghitung laba bersih.</p>
            </div>
            <div class="admin-pill">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <span><?php echo ucfirst($_SESSION['username']); ?></span>
            </div>
        </div>

        <div class="management-grid">
            <div class="left-column">
                <div class="card-box">
                    <h3><i class="fa-solid fa-circle-plus" style="color: #EF4444;"></i> Catat Pengeluaran Baru</h3>
                    <p class="subtitle">Masukkan rincian biaya operasional.</p>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori Pengeluaran</label>
                            <select name="kategori" class="form-control" required>
                                <option value="Bahan & Deterjen">Bahan & Deterjen (Pewangi/Sabun)</option>
                                <option value="Listrik & Air">Listrik & Air (Token/PDAM)</option>
                                <option value="Gaji Kurir / Pegawai">Gaji Kurir / Pegawai</option>
                                <option value="Perawatan Mesin">Perawatan Mesin Cuci</option>
                                <option value="Lain-lain">Lain-lain / Operasional</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nominal (Rp)</label>
                            <!-- Input tampilan dengan format Rupiah -->
                            <input type="text" id="input_rupiah" class="form-control" placeholder="Contoh: Rp 50.000" autocomplete="off" required>
                            <!-- Input tersembunyi untuk dikirim ke database -->
                            <input type="hidden" name="jumlah_raw" id="jumlah_raw">
                        </div>
                        <div class="form-group">
                            <label>Keterangan / Catatan</label>
                            <textarea name="keterangan" class="form-control" placeholder="Contoh: Beli 2 jerigen pewangi laundry" rows="2" required></textarea>
                        </div>
                        <button type="submit" name="tambah_pengeluaran" class="btn-submit">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Pengeluaran
                        </button>
                    </form>
                </div>

                <div class="card-box" style="background: linear-gradient(135deg, #131B2E, #1E1B4B); border-color: rgba(239, 68, 68, 0.2);">
                    <h3 style="font-size: 14px; color: var(--text-muted); text-transform: uppercase;">Total Pengeluaran Bulan Ini</h3>
                    <h2 style="margin: 10px 0 0 0; font-size: 24px; color: #F87171;">Rp <?php echo number_format($total_pengeluaran_bulan_ini, 0, ',', '.'); ?></h2>
                </div>
            </div>

            <div class="card-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin: 0;">Riwayat Pengeluaran Operasional</h3>
                        <p class="subtitle" style="margin: 2px 0 0 0;">Daftar pengeluaran berdasarkan periode.</p>
                    </div>

                    <form method="GET" action="" class="filter-bar" style="margin: 0;">
                        <select name="bulan">
                            <option value="01" <?php if($filter_bulan=='01') echo 'selected'; ?>>Januari</option>
                            <option value="02" <?php if($filter_bulan=='02') echo 'selected'; ?>>Februari</option>
                            <option value="03" <?php if($filter_bulan=='03') echo 'selected'; ?>>Maret</option>
                            <option value="04" <?php if($filter_bulan=='04') echo 'selected'; ?>>April</option>
                            <option value="05" <?php if($filter_bulan=='05') echo 'selected'; ?>>Mei</option>
                            <option value="06" <?php if($filter_bulan=='06') echo 'selected'; ?>>Juni</option>
                            <option value="07" <?php if($filter_bulan=='07') echo 'selected'; ?>>Juli</option>
                            <option value="08" <?php if($filter_bulan=='08') echo 'selected'; ?>>Agustus</option>
                            <option value="09" <?php if($filter_bulan=='09') echo 'selected'; ?>>September</option>
                            <option value="10" <?php if($filter_bulan=='10') echo 'selected'; ?>>Oktober</option>
                            <option value="11" <?php if($filter_bulan=='11') echo 'selected'; ?>>November</option>
                            <option value="12" <?php if($filter_bulan=='12') echo 'selected'; ?>>Desember</option>
                        </select>
                        <select name="tahun" onchange="this.form.submit()">
                            <option value="2025" <?php if($filter_tahun=='2025') echo 'selected'; ?>>2025</option>
                            <option value="2026" <?php if($filter_tahun=='2026') echo 'selected'; ?>>2026</option>
                        </select>
                        <button type="submit"><i class="fa-solid fa-filter"></i></button>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th>Nominal</th>
                            <th style="width: 80px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_list = mysqli_query($koneksi, "SELECT * FROM pengeluaran WHERE MONTH(tanggal) = '$filter_bulan' AND YEAR(tanggal) = '$filter_tahun' ORDER BY tanggal DESC, id_pengeluaran DESC");
                        if ($q_list && mysqli_num_rows($q_list) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($q_list)) {
                        ?>
                        <tr>
                            <td><b><?php echo $no++; ?></b></td>
                            <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                            <td><span class="badge-kategori"><?php echo htmlspecialchars($row['kategori']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                            <td><b style="color: #F87171;">Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></b></td>
                            <td style="text-align: center;">
                                <a href="pengeluaran.php?hapus=<?php echo $row['id_pengeluaran']; ?>" onclick="return confirm('Yakin ingin menghapus data pengeluaran ini?')" class="action-btn delete" title="Hapus Pengeluaran" style="margin: 0 auto;"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; color: var(--text-muted); padding:30px;'>Belum ada data pengeluaran pada periode ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Script untuk Format Rupiah Otomatis pada Input Nominal
        const inputRupiah = document.getElementById('input_rupiah');
        const jumlahRaw = document.getElementById('jumlah_raw');

        inputRupiah.addEventListener('keyup', function(e) {
            let angkaString = this.value.replace(/[^,\d]/g, '').toString();
            let split = angkaString.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            
            // Tampilkan format ke input visual
            this.value = rupiah ? 'Rp ' + rupiah : '';
            
            // Simpan angka murni ke hidden input untuk database
            jumlahRaw.value = angkaString;
        });
    </script>
</body>
</html>