<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit;
}

$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$q_cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi");
$fields_trx = [];
while($f = mysqli_fetch_assoc($q_cek_kolom)){
    $fields_trx[] = $f['Field'];
}
$kolom_status = in_array('status_pesanan', $fields_trx) ? 'status_pesanan' : (in_array('status', $fields_trx) ? 'status' : 'status_pesanan');
$kolom_tgl = in_array('tgl_terima', $fields_trx) ? 'tgl_terima' : (in_array('tgl_masuk', $fields_trx) ? 'tgl_masuk' : 'tanggal');

// Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $filename = "Laporan_Keuangan_RNF_Express_" . $filter_bulan . "_" . $filter_tahun . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('No. Invoice', 'Nama Pelanggan', 'No. WhatsApp', 'Layanan', 'Tanggal', 'Status Bayar', 'Status Pesanan', 'Total Pendapatan (Rp)'));
    
    $q_export = mysqli_query($koneksi, "SELECT transaksi.*, pelanggan.nama_pelanggan, pelanggan.no_hp, layanan.nama_layanan 
                                     FROM transaksi 
                                     LEFT JOIN pelanggan ON transaksi.id_pelanggan = pelanggan.id_pelanggan 
                                     LEFT JOIN layanan ON transaksi.id_layanan = layanan.id_layanan 
                                     WHERE MONTH(transaksi.$kolom_tgl) = '$filter_bulan' AND YEAR(transaksi.$kolom_tgl) = '$filter_tahun'
                                     ORDER BY transaksi.id_transaksi DESC");
    
    while ($row = mysqli_fetch_assoc($q_export)) {
        $inv = isset($row['kode_invoice']) ? $row['kode_invoice'] : (isset($row['invoice']) ? $row['invoice'] : 'TRX-'.$row['id_transaksi']);
        fputcsv($output, array(
            $inv,
            $row['nama_pelanggan'],
            $row['no_hp'],
            $row['nama_layanan'],
            $row[$kolom_tgl],
            isset($row['status_bayar']) ? $row['status_bayar'] : 'Lunas',
            isset($row[$kolom_status]) ? $row[$kolom_status] : 'Baru',
            $row['total_bayar']
        ));
    }
    fclose($output);
    exit;
}

// Statistik Global
$q_pendapatan = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM transaksi");
$d_pendapatan = mysqli_fetch_assoc($q_pendapatan);
$total_omset = $d_pendapatan['total'] ? $d_pendapatan['total'] : 0;

$q_trx = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM transaksi");
$d_trx = mysqli_fetch_assoc($q_trx);
$total_transaksi = $d_trx['jml'] ? $d_trx['jml'] : 0;

$q_plg = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM pelanggan");
$d_plg = mysqli_fetch_assoc($q_plg);
$total_pelanggan = $d_plg['jml'] ? $d_plg['jml'] : 0;

// Pendapatan & Pengeluaran Berdasarkan Filter Bulan/Tahun
$q_omset_filter = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total, COUNT(*) as jml FROM transaksi WHERE MONTH($kolom_tgl) = '$filter_bulan' AND YEAR($kolom_tgl) = '$filter_tahun'");
$d_omset_filter = mysqli_fetch_assoc($q_omset_filter);
$pendapatan_filter = $d_omset_filter['total'] ? $d_omset_filter['total'] : 0;
$jumlah_trx_filter = $d_omset_filter['jml'] ? $d_omset_filter['jml'] : 0;

// Ambil Total Pengeluaran Bulan Ini
$q_exp_filter = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM pengeluaran WHERE MONTH(tanggal) = '$filter_bulan' AND YEAR(tanggal) = '$filter_tahun'");
$d_exp_filter = mysqli_fetch_assoc($q_exp_filter);
$pengeluaran_filter = $d_exp_filter['total'] ? $d_exp_filter['total'] : 0;

// Hitung Laba Bersih (Pendapatan - Pengeluaran)
$laba_bersih_filter = $pendapatan_filter - $pengeluaran_filter;

// Data Grafik Bulanan
$grafik_data = [];
for ($m = 1; $m <= 12; $m++) {
    $m_led = str_pad($m, 2, '0', STR_PAD_LEFT);
    $q_g = mysqli_query($koneksi, "SELECT SUM(total_bayar) as total FROM transaksi WHERE MONTH($kolom_tgl) = '$m_led' AND YEAR($kolom_tgl) = '$filter_tahun'");
    $dg = mysqli_fetch_assoc($q_g);
    $grafik_data[] = $dg['total'] ? $dg['total'] : 0;
}
$grafik_json = json_encode($grafik_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard & Laporan Keuangan - R&F Express</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .metrics-grid-global { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .metrics-grid-filter { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .metric-card { background: var(--bg-card); padding: 24px; border-radius: 20px; border: 1px solid var(--border-color); position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); transition: transform 0.3s ease; }
        .metric-card:hover { transform: translateY(-3px); }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; }
        .metric-card.blue::before { background: linear-gradient(90deg, #3B82F6, #60A5FA); }
        .metric-card.purple::before { background: linear-gradient(90deg, #8B5CF6, #C084FC); }
        .metric-card.pink::before { background: linear-gradient(90deg, #EC4899, #F472B6); }
        .metric-card.success::before { background: linear-gradient(90deg, #10B981, #34D399); }
        .metric-card.danger::before { background: linear-gradient(90deg, #EF4444, #F87171); }
        .metric-card .icon-box { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px; }
        .metric-card.blue .icon-box { background: rgba(59, 130, 246, 0.15); color: #60A5FA; }
        .metric-card.purple .icon-box { background: rgba(139, 92, 246, 0.15); color: #C084FC; }
        .metric-card.pink .icon-box { background: rgba(236, 72, 153, 0.15); color: #F472B6; }
        .metric-card.success .icon-box { background: rgba(16, 185, 129, 0.15); color: #34D399; }
        .metric-card.danger .icon-box { background: rgba(239, 68, 68, 0.15); color: #F87171; }
        .metric-card label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
        .metric-card h3 { margin: 0; font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; }
        .card-box { background: var(--bg-card); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 25px; }
        .card-box h3 { margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px; }
        .filter-export-bar { background: var(--bg-card); padding: 18px 22px; border-radius: 16px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .filter-form { display: flex; align-items: center; gap: 12px; }
        .filter-form select { padding: 10px 16px; border: 1px solid var(--border-color); border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 600; background: #0B0F19; color: #ffffff; outline: none; cursor: pointer; }
        .filter-form button { padding: 10px 20px; background: linear-gradient(135deg, #3B82F6, #1D4ED8); color: white; border: none; border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .filter-form button:hover { opacity: 0.9; }
        .btn-export { padding: 10px 20px; background: linear-gradient(135deg, #10B981, #059669); color: white; border: none; border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-export:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { background: rgba(255,255,255,0.03); color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.8px; padding: 14px 16px; border-bottom: 1px solid var(--border-color); }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: #ffffff; font-weight: 500; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        .badge-status { padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-truck-fast"></i> R&F Express</h2>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active"><i class="fa-solid fa-layer-group"></i> Dashboard & Keuangan</a>
            <a href="pelanggan.php"><i class="fa-regular fa-address-book"></i> Customers</a>
            <a href="layanan.php"><i class="fa-solid fa-tags"></i> Services & Orders</a>
            <a href="papan.php"><i class="fa-solid fa-chart-gantt"></i> Kanban Board</a>
            <a href="pengeluaran.php"><i class="fa-solid fa-wallet"></i> Expense Tracker</a>
        </div>
        <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
    </div>
    
    <div class="content">
        <div class="top-bar">
            <div class="header-title">
                <h1>Dashboard & Laporan Keuangan</h1>
                <p>Analisis statistik modern dan rekapitulasi transaksi bisnis laundry.</p>
            </div>
            <div class="admin-pill">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <span><?php echo ucfirst($_SESSION['username']); ?></span>
            </div>
        </div>

        <div class="metrics-grid-global">
            <div class="metric-card blue">
                <div class="icon-box"><i class="fa-solid fa-wallet"></i></div>
                <label>Total Pendapatan Global</label>
                <h3>Rp <?php echo number_format($total_omset, 0, ',', '.'); ?></h3>
            </div>
            <div class="metric-card purple">
                <div class="icon-box"><i class="fa-solid fa-receipt"></i></div>
                <label>Total Transaksi</label>
                <h3><?php echo number_format($total_transaksi, 0, ',', '.'); ?> Pesanan</h3>
            </div>
            <div class="metric-card pink">
                <div class="icon-box"><i class="fa-solid fa-users"></i></div>
                <label>Total Pelanggan</label>
                <h3><?php echo number_format($total_pelanggan, 0, ',', '.'); ?> Orang</h3>
            </div>
        </div>

        <div class="card-box">
            <h3><i class="fa-solid fa-chart-line" style="color: #60A5FA;"></i> Grafik Pendapatan Bulanan (Tahun <?php echo $filter_tahun; ?>)</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="omsetChart"></canvas>
            </div>
        </div>

        <div class="filter-export-bar">
            <form method="GET" action="" class="filter-form">
                <i class="fa-regular fa-calendar" style="color: #60A5FA;"></i>
                <span style="font-size: 13px; font-weight: 700; color: var(--text-muted);">Periode:</span>
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
                <button type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            </form>

            <a href="dashboard.php?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>&export=excel" class="btn-export">
                <i class="fa-solid fa-file-excel"></i> Export Excel
            </a>
        </div>

        <!-- KARTU RINGKASAN KEUANGAN BULAN TERPILIH (TERMASUK LABA BERSIH) -->
        <div class="metrics-grid-filter">
            <div class="metric-card success">
                <div class="icon-box"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <label>Pendapatan Kotor</label>
                <h3>Rp <?php echo number_format($pendapatan_filter, 0, ',', '.'); ?></h3>
            </div>
            <div class="metric-card danger">
                <div class="icon-box"><i class="fa-solid fa-arrow-trend-down"></i></div>
                <label>Total Pengeluaran</label>
                <h3>Rp <?php echo number_format($pengeluaran_filter, 0, ',', '.'); ?></h3>
            </div>
            <div class="metric-card blue">
                <div class="icon-box"><i class="fa-solid fa-wallet"></i></div>
                <label>Laba Bersih (Net Profit)</label>
                <h3 style="color: <?php echo ($laba_bersih_filter < 0) ? '#F87171' : '#34D399'; ?>;">
                    Rp <?php echo number_format($laba_bersih_filter, 0, ',', '.'); ?>
                </h3>
            </div>
        </div>

        <div class="card-box">
            <h3><i class="fa-solid fa-table-list" style="color: #C084FC;"></i> Rincian Transaksi Periode Terpilih</h3>
            <table>
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Pelanggan</th>
                        <th>No. WhatsApp</th>
                        <th>Layanan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q_trx = mysqli_query($koneksi, "SELECT transaksi.*, pelanggan.nama_pelanggan, pelanggan.no_hp, layanan.nama_layanan 
                                                     FROM transaksi 
                                                     LEFT JOIN pelanggan ON transaksi.id_pelanggan = pelanggan.id_pelanggan 
                                                     LEFT JOIN layanan ON transaksi.id_layanan = layanan.id_layanan 
                                                     WHERE MONTH(transaksi.$kolom_tgl) = '$filter_bulan' AND YEAR(transaksi.$kolom_tgl) = '$filter_tahun'
                                                     ORDER BY transaksi.id_transaksi DESC");
                    if ($q_trx && mysqli_num_rows($q_trx) > 0) {
                        while ($t = mysqli_fetch_assoc($q_trx)) {
                            $inv = isset($t['kode_invoice']) ? $t['kode_invoice'] : (isset($t['invoice']) ? $t['invoice'] : 'TRX-'.$t['id_transaksi']);
                            $tgl = isset($t[$kolom_tgl]) ? $t[$kolom_tgl] : date('Y-m-d');
                            $status_byr = isset($t['status_bayar']) ? $t['status_bayar'] : 'Lunas';
                    ?>
                    <tr>
                        <td><b><?php echo $inv; ?></b></td>
                        <td><?php echo htmlspecialchars($t['nama_pelanggan']); ?></td>
                        <td><i class='fa-brands fa-whatsapp' style='color:#25d366; margin-right:4px;'></i> <?php echo htmlspecialchars($t['no_hp']); ?></td>
                        <td><?php echo htmlspecialchars($t['nama_layanan']); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($tgl)); ?></td>
                        <td><span class="badge-status"><?php echo $status_byr; ?></span></td>
                        <td><b style="color: #34D399;">Rp <?php echo number_format($t['total_bayar'], 0, ',', '.'); ?></b></td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; color: var(--text-muted); padding:30px;'>Belum ada data transaksi pada periode ini.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('omsetChart').getContext('2d');
        const omsetChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo $grafik_json; ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderColor: '#3B82F6',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3B82F6',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94A3B8' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94A3B8' }
                    }
                }
            }
        });
    </script>
</body>
</html>