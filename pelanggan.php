<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id_pelanggan = intval($_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM pelanggan WHERE id_pelanggan = '$id_pelanggan'");
    header("location: pelanggan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Customers - R&F Express</title>
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
        .card-box { background: var(--bg-card); padding: 25px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 25px; }
        .card-box h3 { margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { background: rgba(255,255,255,0.03); color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.8px; padding: 14px 16px; border-bottom: 1px solid var(--border-color); }
        td { padding: 16px; border-bottom: 1px solid var(--border-color); color: #ffffff; font-weight: 500; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }
        .action-btns { display: flex; gap: 8px; align-items: center; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .action-btn:hover { background: var(--accent-blue); color: white; border-color: var(--accent-blue); }
        .action-btn.delete:hover { background: #EF4444; border-color: #EF4444; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-truck-fast"></i> R&F Express</h2>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fa-solid fa-layer-group"></i> Dashboard & Keuangan</a>
            <a href="pelanggan.php" class="active"><i class="fa-regular fa-address-book"></i> Customers</a>
            <a href="layanan.php"><i class="fa-solid fa-tags"></i> Services & Orders</a>
            <a href="papan.php"><i class="fa-solid fa-chart-gantt"></i> Kanban Board</a>
            <a href="pengeluaran.php"><i class="fa-solid fa-wallet"></i> Expense Tracker</a>
        </div>
        <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
    </div>
    
    <div class="content">
        <div class="top-bar">
            <div class="header-title">
                <h1>Customer Management</h1>
                <p>Daftar seluruh pelanggan yang terdaftar di sistem R&F Express.</p>
            </div>
            <div class="admin-pill">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <span><?php echo ucfirst($_SESSION['username']); ?></span>
            </div>
        </div>

        <div class="card-box">
            <h3><i class="fa-solid fa-users" style="color: #60A5FA;"></i> Data Pelanggan Terdaftar</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Pelanggan</th>
                        <th>No. WhatsApp</th>
                        <th>Alamat</th>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q_plg = mysqli_query($koneksi, "SELECT * FROM pelanggan ORDER BY id_pelanggan DESC");
                    if ($q_plg && mysqli_num_rows($q_plg) > 0) {
                        $no = 1;
                        while ($p = mysqli_fetch_assoc($q_plg)) {
                    ?>
                    <tr>
                        <td><b><?php echo $no++; ?></b></td>
                        <td><b><?php echo htmlspecialchars($p['nama_pelanggan']); ?></b></td>
                        <td><i class='fa-brands fa-whatsapp' style='color:#25d366; margin-right:4px;'></i> <?php echo htmlspecialchars($p['no_hp']); ?></td>
                        <td><?php echo htmlspecialchars($p['alamat']); ?></td>
                        <td>
                            <div class="action-btns" style="justify-content: center;">
                                <a href="pelanggan_edit.php?id=<?php echo $p['id_pelanggan']; ?>" class="action-btn" title="Edit Pelanggan"><i class="fa-solid fa-pen"></i></a>
                                <a href="pelanggan.php?hapus=<?php echo $p['id_pelanggan']; ?>" onclick="return confirm('Yakin ingin menghapus pelanggan ini?')" class="action-btn delete" title="Hapus Pelanggan"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; color: var(--text-muted); padding:30px;'>Belum ada data pelanggan.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>