<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit;
}

$q_cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi");
$fields_trx = [];
while($f = mysqli_fetch_assoc($q_cek_kolom)){
    $fields_trx[] = $f['Field'];
}
if (!in_array('kurir', $fields_trx)) {
    mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN kurir VARCHAR(100) DEFAULT NULL");
}

if (isset($_POST['assign_kurir'])) {
    $id_trx = intval($_POST['id_transaksi']);
    $kurir  = mysqli_real_escape_string($koneksi, $_POST['kurir']);
    
    mysqli_query($koneksi, "UPDATE transaksi SET kurir = '$kurir', status_pesanan = 'Proses' WHERE id_transaksi = '$id_trx'");
    header("location: papan.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'proses') {
        mysqli_query($koneksi, "UPDATE transaksi SET status_pesanan='Proses' WHERE id_transaksi='$id'");
    } elseif ($action == 'selesai') {
        mysqli_query($koneksi, "UPDATE transaksi SET status_pesanan='Selesai' WHERE id_transaksi='$id'");
    } elseif ($action == 'diambil') {
        mysqli_query($koneksi, "UPDATE transaksi SET status_pesanan='Diambil', status_bayar='Lunas' WHERE id_transaksi='$id'");
    } elseif ($action == 'lunas') {
        mysqli_query($koneksi, "UPDATE transaksi SET status_bayar='Lunas' WHERE id_transaksi='$id'");
    }
    header("location: papan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kanban Board - R&F Express</title>
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; display: flex; background-color: var(--bg-main); color: var(--text-light); height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background: var(--bg-sidebar); color: var(--text-muted); height: 100vh; padding: 25px 20px; box-sizing: border-box; display: flex; flex-direction: column; flex-shrink: 0; border-right: 1px solid var(--border-color); }
        .sidebar h2 { margin-top: 0; color: #ffffff; font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 10px; margin-bottom: 35px; }
        .sidebar h2 i { color: var(--accent-blue); }
        .sidebar-menu { flex: 1; }
        .sidebar a { color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 13px 18px; margin-bottom: 6px; border-radius: 12px; transition: all 0.2s ease; font-size: 14px; font-weight: 600; }
        .sidebar a:hover, .sidebar a.active { background: linear-gradient(135deg, #3B82F6, #1D4ED8); color: #ffffff; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .sidebar a.logout { background: rgba(239, 68, 68, 0.1); color: #F87171; margin-top: auto; }
        .sidebar a.logout:hover { background: #EF4444; color: #ffffff; }
        .content { flex: 1; padding: 30px 40px; box-sizing: border-box; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-shrink: 0; }
        .header-title { display: flex; flex-direction: column; gap: 5px; }
        .header-title-row { display: flex; align-items: center; gap: 15px; }
        .header-title h1 { margin: 0; font-size: 24px; color: #ffffff; font-weight: 800; letter-spacing: -0.5px; }
        .header-title p { margin: 0; color: var(--text-muted); font-size: 13px; }
        .btn-refresh { background: rgba(59, 130, 246, 0.1); color: #60A5FA; border: 1px solid rgba(59, 130, 246, 0.3); padding: 8px 15px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif; display: inline-flex; align-items: center; gap: 6px; }
        .btn-refresh:hover { background: var(--accent-blue); color: white; }
        .admin-pill { background: var(--bg-card); padding: 6px 16px; border-radius: 50px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 13px; }
        .admin-pill .avatar { width: 30px; height: 30px; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }
        .kanban-board { display: flex; gap: 20px; flex: 1; overflow-x: auto; padding-bottom: 20px; align-items: flex-start; }
        .kanban-col { width: 320px; flex-shrink: 0; background: var(--bg-card); border-radius: 20px; padding: 18px; display: flex; flex-direction: column; gap: 15px; border: 1px solid var(--border-color); max-height: 100%; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .col-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; background: var(--bg-card); z-index: 10; margin-top: -5px; padding-top: 5px; }
        .col-header h3 { margin: 0; font-size: 13px; font-weight: 800; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px; }
        .col-count { background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #60A5FA; border: 1px solid var(--border-color); }
        .kanban-card { background: #0B0F19; padding: 18px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); border: 1px solid var(--border-color); transition: transform 0.2s, box-shadow 0.2s; position: relative; display: flex; flex-direction: column; gap: 8px; }
        .kanban-card:hover { transform: translateY(-3px); border-color: var(--accent-blue); }
        .card-header-info { font-size: 11px; font-weight: 700; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; margin-bottom: 4px; }
        .invoice-tag { color: #60A5FA; background: rgba(59, 130, 246, 0.15); padding: 4px 8px; border-radius: 6px; }
        .card-customer { font-size: 15px; font-weight: 800; color: #ffffff; margin: 0; }
        .card-phone { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .card-service { font-size: 13px; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.03); padding: 8px 10px; border-radius: 8px; margin: 4px 0; border: 1px solid var(--border-color); }
        .card-address { font-size: 11.5px; color: var(--text-muted); background: rgba(139, 92, 246, 0.1); padding: 8px 10px; border-radius: 8px; line-height: 1.4; border-left: 3px solid var(--accent-purple); }
        .note-preview { font-size: 11.5px; color: #FBBF24; background: rgba(245, 158, 11, 0.1); padding: 8px 10px; border-radius: 8px; border-left: 3px solid #F59E0B; line-height: 1.4; }
        .courier-box { background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 8px 10px; border-radius: 10px; margin-top: 4px; font-size: 11.5px; }
        .courier-info { color: var(--text-muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .courier-info b { color: #60A5FA; }
        .courier-select { padding: 6px 10px; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 11px; font-weight: 700; background: #0B0F19; color: #ffffff; outline: none; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; margin-top: 4px; }
        .badge-baru { background: rgba(59, 130, 246, 0.15); color: #60A5FA; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-proses { background: rgba(245, 158, 11, 0.15); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-selesai { background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-diambil { background: rgba(139, 92, 246, 0.15); color: #C084FC; border: 1px solid rgba(139, 92, 246, 0.3); }
        .payment-status { display: flex; justify-content: space-between; align-items: center; margin-top: 5px; }
        .badge-pay { padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px; }
        .pay-lunas { background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .pay-belum { background: rgba(239, 68, 68, 0.15); color: #F87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-set-lunas { font-size: 10px; font-weight: 700; color: #60A5FA; text-decoration: none; padding: 4px 10px; background: rgba(59, 130, 246, 0.15); border-radius: 6px; transition: 0.2s; }
        .btn-set-lunas:hover { background: var(--accent-blue); color: white; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px dashed var(--border-color); margin-top: 4px; }
        .card-price { font-weight: 800; color: #34D399; font-size: 14px; }
        .btn-move { background: linear-gradient(135deg, #3B82F6, #1D4ED8); color: #ffffff; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .btn-move:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-truck-fast"></i> R&F Express</h2>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fa-solid fa-layer-group"></i> Dashboard & Keuangan</a>
            <a href="pelanggan.php"><i class="fa-regular fa-address-book"></i> Customers</a>
            <a href="layanan.php"><i class="fa-solid fa-tags"></i> Services & Orders</a>
            <a href="papan.php" class="active"><i class="fa-solid fa-chart-gantt"></i> Kanban Board</a>
            <a href="pengeluaran.php"><i class="fa-solid fa-wallet"></i> Expense Tracker</a>
        </div>
        <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
    </div>
    
    <div class="content">
        <div class="header">
            <div class="header-title">
                <div class="header-title-row">
                    <h1>Order Tracking Board</h1>
                    <button onclick="window.location.reload();" class="btn-refresh">
                        <i class="fa-solid fa-rotate-right"></i> Muat Ulang
                    </button>
                </div>
                <p>Monitor, move, and track laundry statuses in real-time.</p>
            </div>
            <div class="admin-pill">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <div class="info">
                    <span><?php echo ucfirst($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="kanban-board">
            <?php
            $kolom_kanban = [
                'Baru' => [
                    'judul' => '🆕 NEW ORDERS', 
                    'badge_class' => 'badge-baru',
                    'badge_text' => '⏳ Menunggu Penjemputan / Antrean',
                    'next_action' => 'proses', 
                    'btn_text' => 'Proses',
                    'icon' => 'fa-play'
                ],
                'Proses' => [
                    'judul' => '⚙️ IN PROGRESS', 
                    'badge_class' => 'badge-proses',
                    'badge_text' => '🫧 Sedang Dicuci / Setrika',
                    'next_action' => 'selesai', 
                    'btn_text' => 'Selesai',
                    'icon' => 'fa-check'
                ],
                'Selesai' => [
                    'judul' => '🎒 READY / DELIVERY', 
                    'badge_class' => 'badge-selesai',
                    'badge_text' => '✨ Siap Diantar / Diambil',
                    'next_action' => 'diambil', 
                    'btn_text' => 'Diambil',
                    'icon' => 'fa-box-open'
                ],
                'Diambil' => [
                    'judul' => '✅ COMPLETED', 
                    'badge_class' => 'badge-diambil',
                    'badge_text' => '📦 Selesai & Lunas',
                    'next_action' => '', 
                    'btn_text' => '',
                    'icon' => ''
                ]
            ];
            
            foreach ($kolom_kanban as $status_db => $pengaturan) {
                $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM transaksi WHERE status_pesanan = '$status_db'");
                $d_count = mysqli_fetch_assoc($q_count);
                
                echo "<div class='kanban-col'>";
                echo "<div class='col-header'>";
                echo "<h3>" . $pengaturan['judul'] . "</h3>";
                echo "<span class='col-count'>" . $d_count['jml'] . "</span>";
                echo "</div>";
                
                $q_board = mysqli_query($koneksi, "
                    SELECT transaksi.*, pelanggan.nama_pelanggan, pelanggan.no_hp, pelanggan.alamat, layanan.nama_layanan 
                    FROM transaksi 
                    JOIN pelanggan ON transaksi.id_pelanggan = pelanggan.id_pelanggan 
                    JOIN layanan ON transaksi.id_layanan = layanan.id_layanan
                    WHERE transaksi.status_pesanan = '$status_db'
                    ORDER BY transaksi.id_transaksi DESC
                ");
                
                if (mysqli_num_rows($q_board) > 0) {
                    while ($data = mysqli_fetch_assoc($q_board)) {
                        echo "<div class='kanban-card'>";
                        
                        $kode_inv = isset($data['kode_invoice']) ? $data['kode_invoice'] : (isset($data['invoice']) ? $data['invoice'] : 'TRX');
                        $tgl_masuk = isset($data['tgl_terima']) ? $data['tgl_terima'] : (isset($data['tgl_masuk']) ? $data['tgl_masuk'] : date('Y-m-d'));
                        
                        echo "<div class='card-header-info'>";
                        echo "<span class='invoice-tag'>#" . $kode_inv . "</span>";
                        echo "<span>" . date('d M, H:i', strtotime($tgl_masuk)) . "</span>";
                        echo "</div>";
                        
                        echo "<h4 class='card-customer'>" . htmlspecialchars($data['nama_pelanggan']) . "</h4>";
                        echo "<span class='card-phone'><i class='fa-brands fa-whatsapp' style='color:#25d366;'></i> " . htmlspecialchars($data['no_hp']) . "</span>";

                        $jml_cucian = isset($data['berat']) ? $data['berat'] : '1';
                        echo "<div class='card-service'>
                                <i class='fa-solid fa-shirt'></i> " . htmlspecialchars($data['nama_layanan']) . " (" . $jml_cucian . " Unit)
                              </div>";
                        
                        if (!empty($data['alamat'])) {
                            echo "<div class='card-address'><i class='fa-solid fa-location-dot' style='color: #C084FC; margin-right: 4px;'></i> " . htmlspecialchars($data['alamat']) . "</div>";
                        }

                        $kurir_aktif = isset($data['kurir']) ? $data['kurir'] : '';
                        echo "<div class='courier-box'>
                                <div class='courier-info'>
                                    <i class='fa-solid fa-motorcycle' style='color: #60A5FA;'></i> Kurir: <b>" . ($kurir_aktif ? $kurir_aktif : 'Belum Ditugaskan') . "</b>
                                </div>
                                <form method='POST' action='' style='display: flex; gap: 6px;'>
                                    <input type='hidden' name='id_transaksi' value='" . $data['id_transaksi'] . "'>
                                    <select name='kurir' class='courier-select' style='flex: 1;' required>
                                        <option value='' disabled selected>-- Pilih Kurir --</option>
                                        <option value='Budi (Kurir 1)' " . ($kurir_aktif == 'Budi (Kurir 1)' ? 'selected' : '') . ">Budi (Kurir 1)</option>
                                        <option value='Joko (Kurir 2)' " . ($kurir_aktif == 'Joko (Kurir 2)' ? 'selected' : '') . ">Joko (Kurir 2)</option>
                                        <option value='Rian (Kurir 3)' " . ($kurir_aktif == 'Rian (Kurir 3)' ? 'selected' : '') . ">Rian (Kurir 3)</option>
                                    </select>
                                    <button type='submit' name='assign_kurir' class='courier-select' style='background: #3B82F6; color: white; border: none; cursor: pointer;' title='Simpan Kurir'>
                                        <i class='fa-solid fa-check'></i>
                                    </button>
                                </form>
                              </div>";

                        $catatan = isset($data['catatan']) ? $data['catatan'] : (isset($data['keterangan']) ? $data['keterangan'] : '');
                        if (!empty($catatan)) {
                            echo "<div class='note-preview'><i class='fa-solid fa-note-sticky' style='margin-right: 4px;'></i> " . htmlspecialchars($catatan) . "</div>";
                        }

                        echo "<div class='status-badge " . $pengaturan['badge_class'] . "'>" . $pengaturan['badge_text'] . "</div>";

                        $status_bayar = isset($data['status_bayar']) ? $data['status_bayar'] : 'Belum Lunas';
                        echo "<div class='payment-status'>";
                        if ($status_bayar == 'Lunas') {
                            echo "<span class='badge-pay pay-lunas'><i class='fa-solid fa-check-circle'></i> Lunas</span>";
                        } else {
                            echo "<span class='badge-pay pay-belum'><i class='fa-solid fa-circle-exclamation'></i> Belum Lunas</span>";
                            echo "<a href='papan.php?action=lunas&id=".$data['id_transaksi']."' class='btn-set-lunas'>Set Lunas</a>";
                        }
                        echo "</div>";
                        
                        echo "<div class='card-footer'>";
                        echo "<span class='card-price'>Rp " . number_format($data['total_bayar'], 0, ',', '.') . "</span>";
                        
                        if ($pengaturan['next_action'] != '') {
                            echo "<a href='papan.php?action=" . $pengaturan['next_action'] . "&id=" . $data['id_transaksi'] . "' class='btn-move'>
                                    " . $pengaturan['btn_text'] . " <i class='fa-solid ".$pengaturan['icon']."'></i>
                                  </a>";
                        } else {
                            echo "<span style='font-size:12px; color:#34D399; font-weight:800;'><i class='fa-solid fa-check-double'></i> SELESAI</span>";
                        }
                        
                        echo "</div>"; 
                        echo "</div>";
                    }
                } else {
                    echo "<div style='text-align:center; padding:30px 10px; color:var(--text-muted); font-size:13px; border: 1px dashed var(--border-color); border-radius: 12px;'>Kategori ini kosong.</div>";
                }
                echo "</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>