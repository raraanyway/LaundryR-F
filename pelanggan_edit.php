<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE id_pelanggan='$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("location: pelanggan.php");
    exit;
}

if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    mysqli_query($koneksi, "UPDATE pelanggan SET nama_pelanggan='$nama', no_hp='$hp', alamat='$alamat' WHERE id_pelanggan='$id'");
    header("location: pelanggan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Customer - R&F Express</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-title h1 { margin: 0; font-size: 24px; color: #ffffff; font-weight: 800; letter-spacing: -0.5px; }
        .header-title p { margin: 4px 0 0 0; color: var(--text-muted); font-size: 13px; }
        .admin-pill { background: var(--bg-card); padding: 6px 16px; border-radius: 50px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 13px; }
        .admin-pill .avatar { width: 30px; height: 30px; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }
        .card-box { background: var(--bg-card); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); border: 1px solid var(--border-color); max-width: 600px; }
        .card-box h3 { margin: 0 0 20px 0; color: #ffffff; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; box-sizing: border-box; background-color: #0B0F19; color: #ffffff; transition: 0.2s; }
        .form-control:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .btn-container { display: flex; gap: 12px; margin-top: 30px; }
        .btn-submit { flex: 1; padding: 12px; background: linear-gradient(135deg, #3B82F6, #1D4ED8); color: #ffffff; border: none; border-radius: 12px; font-size: 13.5px; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .btn-submit:hover { opacity: 0.9; }
        .btn-cancel { padding: 12px 24px; background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid var(--border-color); border-radius: 12px; font-size: 13.5px; font-weight: 700; text-align: center; text-decoration: none; transition: 0.2s; display: flex; align-items: center; }
        .btn-cancel:hover { background: rgba(255, 255, 255, 0.1); color: #ffffff; }
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
        <div class="header">
            <div class="header-title">
                <h1>Edit Customer Information</h1>
                <p>Update client details and save changes.</p>
            </div>
            <div class="admin-pill">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <div class="info">
                    <span><?php echo ucfirst($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="card-box">
            <h3><i class="fa-solid fa-pen-to-square" style="color: #60A5FA;"></i> Edit Form</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="nama_pelanggan" class="form-control" value="<?php echo htmlspecialchars($data['nama_pelanggan']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($data['no_hp']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Home Address</label>
                    <textarea name="alamat" class="form-control" required><?php echo htmlspecialchars($data['alamat']); ?></textarea>
                </div>
                
                <div class="btn-container">
                    <a href="pelanggan.php" class="btn-cancel">Cancel</a>
                    <button type="submit" name="update" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>