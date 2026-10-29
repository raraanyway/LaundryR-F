<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_laundry";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$error_admin = '';
$pencarian = "";
$data_transaksi = null;
$pesan_tracking = "";
$pesan_sukses_order = "";
$invoice_terbaru = "";
$total_bayar_terbaru = 0;
$nama_pelanggan_baru = "";
$no_hp_baru = "";
$layanan_baru = "";
$jumlah_baru = 0;
$tipe_layanan_baru = "";
$active_tab = "order";

// 1. Proses Login Admin
if (isset($_POST['login_admin'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $q_login = mysqli_query($koneksi, "SELECT * FROM admin WHERE username='$username'");
    if ($q_login && mysqli_num_rows($q_login) > 0) {
        $row = mysqli_fetch_assoc($q_login);
        if ($password === $row['password'] || md5($password) === $row['password']) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = isset($row['role']) ? $row['role'] : 'Administrator';
            echo "<script>window.location.href='dashboard.php';</script>";
            exit;
        } else {
            $error_admin = "Password yang Anda masukkan salah!";
        }
    } else {
        $error_admin = "Username '$username' tidak ditemukan di database!";
    }
}

// 2. Proses Tracking Status Pesanan
if (isset($_POST['cek_status'])) {
    $active_tab = "track";
    $pencarian_mentah = trim($_POST['kode_invoice']);
    $pencarian = mysqli_real_escape_string($koneksi, str_replace('#', '', $pencarian_mentah));
    
    $q_cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi");
    $fields = [];
    while($f = mysqli_fetch_assoc($q_cek_kolom)){
        $fields[] = $f['Field'];
    }
    $kolom_invoice = in_array('kode_invoice', $fields) ? 'kode_invoice' : (in_array('invoice', $fields) ? 'invoice' : 'kode_invoice');

    $query = mysqli_query($koneksi, "SELECT transaksi.*, pelanggan.nama_pelanggan, pelanggan.no_hp, layanan.nama_layanan, layanan.satuan 
                                     FROM transaksi 
                                     LEFT JOIN pelanggan ON transaksi.id_pelanggan = pelanggan.id_pelanggan 
                                     LEFT JOIN layanan ON transaksi.id_layanan = layanan.id_layanan 
                                     WHERE transaksi.$kolom_invoice LIKE '%$pencarian%' OR pelanggan.no_hp LIKE '%$pencarian%'");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $data_transaksi = mysqli_fetch_assoc($query);
    } else {
        $pesan_tracking = "Nomor Invoice atau Nomor HP '$pencarian_mentah' tidak ditemukan! Periksa kembali.";
    }
}

// 3. Proses Pesan Mandiri (Online) dengan Estimasi 3 Hari
if (isset($_POST['pesan_mandiri'])) {
    $active_tab = "order";
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    
    $titik_peta     = mysqli_real_escape_string($koneksi, $_POST['titik_peta']);
    $patokan        = mysqli_real_escape_string($koneksi, $_POST['patokan']);
    $catatan_user   = mysqli_real_escape_string($koneksi, $_POST['catatan']);

    $alamat_gabung  = "Lokasi Peta: $titik_peta | Patokan: $patokan | Catatan: $catatan_user";

    $tipe_layanan   = mysqli_real_escape_string($koneksi, $_POST['tipe_layanan']);
    $nama_layanan_fix = mysqli_real_escape_string($koneksi, $_POST['nama_layanan_pilih']); 
    $jumlah         = floatval($_POST['jumlah']);

    $nama_pelanggan_baru = $nama_pelanggan;
    $no_hp_baru = $no_hp;
    $layanan_baru = $nama_layanan_fix;
    $jumlah_baru = $jumlah;
    $tipe_layanan_baru = $tipe_layanan;

    $harga_satuan = 13000; 
    $kategori_pilih = 'Kiloan';
    $satuan_fix = 'Kg';

    if ($nama_layanan_fix == 'Bedcover Kecil') { $harga_satuan = 35000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Bedcover Sedang') { $harga_satuan = 45000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Bedcover Besar') { $harga_satuan = 55000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Sprei Set Kecil') { $harga_satuan = 25000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Sprei Set Sedang') { $harga_satuan = 35000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Sprei Set Besar') { $harga_satuan = 45000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Selimut Tipis') { $harga_satuan = 25000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Selimut Tebal') { $harga_satuan = 35000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Handuk Kecil') { $harga_satuan = 20000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Handuk Besar') { $harga_satuan = 30000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Sepatu') { $harga_satuan = 55000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Topi') { $harga_satuan = 15000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Jas') { $harga_satuan = 50000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Kebaya') { $harga_satuan = 35000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Dress') { $harga_satuan = 65000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Long Dress') { $harga_satuan = 90000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Kaos Pendek') { $harga_satuan = 20000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Kaos Panjang') { $harga_satuan = 30000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Kemeja Pendek') { $harga_satuan = 35000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Kemeja Panjang') { $harga_satuan = 45000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Sweater') { $harga_satuan = 25000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Jaket') { $harga_satuan = 35000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Jaket Kulit') { $harga_satuan = 45000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Blazer') { $harga_satuan = 50000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Celana / Rok Panjang') { $harga_satuan = 25000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Celana / Rok Pendek') { $harga_satuan = 15000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    elseif ($nama_layanan_fix == 'Celana Jeans') { $harga_satuan = 35000; $kategori_pilih = 'Satuan'; $satuan_fix = 'Pcs'; }
    else { $harga_satuan = 13000; $kategori_pilih = 'Kiloan'; $satuan_fix = 'Kg'; }

    $total_bayar  = $harga_satuan * $jumlah;
    $ongkir = ($tipe_layanan == 'Antar-Jemput') ? 5000 : 0;
    $total_bayar += $ongkir;
    $total_bayar_terbaru = $total_bayar;

    $nama_layanan_display = $nama_layanan_fix;
    if ($tipe_layanan == 'Antar-Jemput') {
        $nama_layanan_display .= ' (Antar-Jemput)';
    }

    $q_cek_lyn = mysqli_query($koneksi, "SELECT * FROM layanan WHERE nama_layanan = '$nama_layanan_display'");
    if ($q_cek_lyn && mysqli_num_rows($q_cek_lyn) > 0) {
        $d_lyn = mysqli_fetch_assoc($q_cek_lyn);
        $id_layanan = $d_lyn['id_layanan'];
    } else {
        mysqli_query($koneksi, "INSERT INTO layanan (nama_layanan, kategori, harga, satuan) VALUES ('$nama_layanan_display', '$kategori_pilih', '$harga_satuan', '$satuan_fix')");
        $id_layanan = mysqli_insert_id($koneksi);
    }

    $q_cek_plg = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE no_hp = '$no_hp'");
    if ($q_cek_plg && mysqli_num_rows($q_cek_plg) > 0) {
        $d_plg = mysqli_fetch_assoc($q_cek_plg);
        $id_pelanggan = $d_plg['id_pelanggan'];
    } else {
        mysqli_query($koneksi, "INSERT INTO pelanggan (nama_pelanggan, no_hp, alamat) VALUES ('$nama_pelanggan', '$no_hp', '$alamat_gabung')");
        $id_pelanggan = mysqli_insert_id($koneksi);
    }

    $invoice_baru = "TRX-" . rand(100000000, 999999999);
    $invoice_terbaru = $invoice_baru;
    $tgl_sekarang = date('Y-m-d H:i:s');
    $estimasi_selesai = date('Y-m-d H:i:s', strtotime('+3 days'));

    $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM transaksi");
    $kolom_tabel = [];
    while($k = mysqli_fetch_assoc($cek_kolom)){
        $kolom_tabel[] = $k['Field'];
    }

    $sql_kolom = [];
    $sql_nilai = [];

    $kolom_inv_nama = in_array('kode_invoice', $kolom_tabel) ? 'kode_invoice' : 'invoice';
    if (in_array($kolom_inv_nama, $kolom_tabel)) { $sql_kolom[] = $kolom_inv_nama; $sql_nilai[] = "'$invoice_baru'"; }
    if (in_array('id_pelanggan', $kolom_tabel)) { $sql_kolom[] = 'id_pelanggan'; $sql_nilai[] = "'$id_pelanggan'"; }
    if (in_array('id_layanan', $kolom_tabel)) { $sql_kolom[] = 'id_layanan'; $sql_nilai[] = "'$id_layanan'"; }
    if (in_array('berat', $kolom_tabel)) { $sql_kolom[] = 'berat'; $sql_nilai[] = "'$jumlah'"; }
    if (in_array('total_bayar', $kolom_tabel)) { $sql_kolom[] = 'total_bayar'; $sql_nilai[] = "'$total_bayar'"; }
    if (in_array('status_pesanan', $kolom_tabel)) { $sql_kolom[] = 'status_pesanan'; $sql_nilai[] = "'Baru'"; }
    if (in_array('status', $kolom_tabel) && !in_array('status_pesanan', $kolom_tabel)) { $sql_kolom[] = 'status'; $sql_nilai[] = "'Baru'"; }
    if (in_array('status_bayar', $kolom_tabel)) { $sql_kolom[] = 'status_bayar'; $sql_nilai[] = "'Belum Lunas'"; }
    if (in_array('catatan', $kolom_tabel)) { $sql_kolom[] = 'catatan'; $sql_nilai[] = "'$catatan_user'"; }
    elseif (in_array('keterangan', $kolom_tabel)) { $sql_kolom[] = 'keterangan'; $sql_nilai[] = "'$catatan_user'"; }
    if (in_array('tgl_terima', $kolom_tabel)) { $sql_kolom[] = 'tgl_terima'; $sql_nilai[] = "'$tgl_sekarang'"; }
    elseif (in_array('tgl_masuk', $kolom_tabel)) { $sql_kolom[] = 'tgl_masuk'; $sql_nilai[] = "'$tgl_sekarang'"; }
    elseif (in_array('tanggal', $kolom_tabel)) { $sql_kolom[] = 'tanggal'; $sql_nilai[] = "'$tgl_sekarang'"; }
    if (in_array('batas_waktu', $kolom_tabel)) { $sql_kolom[] = 'batas_waktu'; $sql_nilai[] = "'$estimasi_selesai'"; }

    $str_kolom = implode(", ", $sql_kolom);
    $str_nilai = implode(", ", $sql_nilai);

    if (mysqli_query($koneksi, "INSERT INTO transaksi ($str_kolom) VALUES ($str_nilai)")) {
        $pesan_sukses_order = "Pesanan berhasil dibuat!";
    } else {
        $pesan_tracking = "Gagal memproses pesanan: " . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>R&F Express - Laundry Antar Jemput 24 Jam Profesional</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS & JS untuk Peta Interaktif -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        :root {
            --bg-main: #FFFFFF;
            --text-dark: #0C101E;
            --text-muted: #7A869A;
            --accent-primary: #0066FF; /* Biru default untuk light mode */
            --accent-navy: #050F5F;
            --accent-purple: #8A5478;
            --border-light: rgba(163, 170, 181, 0.2);
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --navbar-bg: rgba(255, 255, 255, 0.95);
            --card-bg: #ffffff;
            --faq-bg: #F8FAFD;
            --glow-shadow: rgba(0, 102, 255, 0.25);
        }

        /* Variabel Tema Gelap (Dark Mode dengan Aksen Ungu Neon) */
        [data-theme="dark"] {
            --bg-main: #0B0F19;
            --text-dark: #F8FAFC;
            --text-muted: #94A3B8;
            --accent-primary: #A855F7; /* Ungu Neon saat dark mode */
            --accent-navy: #1E293B;
            --border-light: rgba(255, 255, 255, 0.08);
            --navbar-bg: rgba(19, 27, 46, 0.95);
            --card-bg: #131B2E;
            --faq-bg: #131B2E;
            --glow-shadow: rgba(168, 85, 247, 0.45); /* Efek cahaya neon ungu */
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            background-color: var(--bg-main); 
            color: var(--text-dark); 
            overflow-x: hidden; 
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* NAVBAR */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 18px 8%; background: var(--navbar-bg); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-light); position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(12, 16, 30, 0.02); transition: background-color 0.3s ease; }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 800; color: var(--text-dark); text-decoration: none; overflow: hidden; position: relative; transition: color 0.3s ease; }
        
        @keyframes driveTruck {
            0% { transform: translateX(-35px); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateX(0); opacity: 1; }
        }
        /* Logo truk otomatis ikut bernuansa ungu neon saat dark mode */
        .nav-brand i { color: var(--accent-primary); font-size: 24px; animation: driveTruck 1.5s cubic-bezier(0.25, 1, 0.5, 1) infinite alternate; transition: color 0.3s ease; }

        .nav-right { display: flex; align-items: center; gap: 15px; }
        
        /* SAKELAR (SLIDER) DARK MODE */
        .theme-switch-wrapper { display: flex; align-items: center; gap: 8px; }
        .theme-switch { position: relative; display: inline-block; width: 48px; height: 24px; }
        .theme-switch input { opacity: 0; width: 0; height: 0; }
        .slider-toggle { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #CBD5E1; transition: .4s; border-radius: 34px; }
        .slider-toggle:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider-toggle { background-color: var(--accent-primary); }
        input:checked + .slider-toggle:before { transform: translateX(24px); }
        .slider-icon { font-size: 13px; }

        .menu-toggle { background: var(--border-light); border: none; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-dark); font-size: 18px; transition: 0.2s; }
        .menu-toggle:hover { background: var(--accent-primary); color: white; }

        .nav-dropdown { display: none; position: absolute; top: 75px; right: 8%; background: var(--card-bg); width: 220px; border-radius: 16px; box-shadow: 0 10px 30px rgba(12,16,30,0.2); border: 1px solid var(--border-light); padding: 10px; z-index: 200; }
        .nav-dropdown.show { display: block; }
        .nav-dropdown a { display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: var(--text-dark); text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 10px; transition: 0.2s; }
        .nav-dropdown a:hover { background: rgba(168, 85, 247, 0.08); color: var(--accent-primary); }

        /* QUICK ACTION / TOMBOL ORDER BERUBAH JADI UNGU NEON SAAT DARK MODE */
        .btn-order-open { 
            background: var(--accent-primary); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            padding: 11px 20px; 
            font-size: 13.5px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            animation: pulseGlow 2s infinite; 
            box-shadow: 0 4px 15px var(--glow-shadow); 
        }
        .btn-order-open:hover { opacity: 0.9; transform: translateY(-2px); }

        @keyframes pulseGlow {
            0% { box-shadow: 0 0 0 0 var(--glow-shadow); }
            70% { box-shadow: 0 0 0 10px rgba(168, 85, 247, 0); }
            100% { box-shadow: 0 0 0 0 rgba(168, 85, 247, 0); }
        }

        .whatsapp-float { position: fixed; bottom: 30px; right: 30px; background: #25d366; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4); z-index: 999; text-decoration: none; transition: 0.3s; animation: floatWp 3s ease-in-out infinite; }
        .whatsapp-float:hover { background: #20ba5a; transform: scale(1.15); }

        @keyframes floatWp {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        /* HERO SECTION */
        .hero-section { 
            padding: 120px 8%; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            text-align: center; 
            background: linear-gradient(rgba(12, 16, 30, 0.75), rgba(12, 16, 30, 0.82)), url('https://images.unsplash.com/photo-1545173168-9f1947eebb7f?q=80&w=1920&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            min-height: 60vh;
            justify-content: center;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-content { max-width: 750px; color: #ffffff; animation: fadeInUp 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .hero-content h1 { font-size: 46px; font-weight: 800; color: #ffffff; line-height: 1.2; margin: 0 0 20px 0; letter-spacing: -1px; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .hero-content p { font-size: 17px; color: rgba(255,255,255,0.9); line-height: 1.6; margin: 0; text-shadow: 0 1px 4px rgba(0,0,0,0.3); }
        
        /* WHY US SECTION */
        .why-us-section { padding: 90px 8%; background: var(--accent-navy); color: white; text-align: center; transition: background-color 0.3s ease; }
        .why-us-section small { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #c084fc; display: block; margin-bottom: 10px; }
        .why-us-section h2 { font-size: 32px; font-weight: 800; color: #ffffff; max-width: 750px; margin: 0 auto 50px auto; line-height: 1.3; }
        
        .why-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; text-align: left; }
        .why-card { background: var(--card-bg); color: var(--text-dark); padding: 35px 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column; align-items: center; text-align: center; border: 1px solid var(--border-light); }
        .why-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.25); }
        .why-icon { width: 55px; height: 55px; background: rgba(168, 85, 247, 0.12); color: var(--accent-primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 20px; transition: 0.3s; }
        .why-card:hover .why-icon { background: var(--accent-primary); color: white; transform: rotate(10deg); }
        .why-card h4 { font-size: 18px; font-weight: 700; margin: 0 0 12px 0; color: var(--text-dark); }
        .why-card p { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; margin: 0; }

        /* ABOUT US SECTION */
        .about-section { padding: 90px 8%; background: var(--bg-main); display: flex; align-items: center; gap: 60px; flex-wrap: wrap; border-bottom: 1px solid var(--border-light); }
        .about-img-wrapper { flex: 1; min-width: 320px; position: relative; }
        .about-img-wrapper img { width: 100%; max-height: 380px; object-fit: cover; border-radius: 24px; box-shadow: 0 20px 40px rgba(12,16,30,0.08); display: block; transition: transform 0.5s; }
        .about-img-wrapper img:hover { transform: scale(1.02); }
        
        .about-content { flex: 1; min-width: 320px; }
        .about-content small { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--accent-primary); display: block; margin-bottom: 10px; }
        .about-content h2 { font-size: 32px; font-weight: 800; color: var(--text-dark); line-height: 1.3; margin: 0 0 20px 0; }
        .about-content p { font-size: 15px; color: var(--text-muted); line-height: 1.7; margin: 0 0 20px 0; }

        /* PRICELIST GRID SECTION */
        .pricelist-section { padding: 90px 8%; background: var(--faq-bg); text-align: center; border-bottom: 1px solid var(--border-light); }
        .pricelist-section h2 { font-size: 32px; font-weight: 800; color: var(--text-dark); margin-bottom: 10px; }
        .pricelist-section p { color: var(--text-muted); font-size: 15px; margin-bottom: 50px; }

        .price-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; text-align: left; }
        
        .price-card { 
            background: var(--card-bg); 
            border: 1px solid var(--border-light); 
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: 0 4px 20px rgba(12,16,30,0.02); 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); 
            position: relative;
            overflow: hidden;
        }
        
        .price-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 20px 40px var(--glow-shadow); 
            border-color: var(--accent-primary); 
        }
        
        .price-card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; }
        
        .price-card-icon { 
            width: 45px; 
            height: 45px; 
            background: rgba(168, 85, 247, 0.1); 
            color: var(--accent-primary); 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 18px; 
            transition: all 0.35s ease; 
        }
        
        .price-card:hover .price-card-icon { 
            background: var(--accent-primary); 
            color: white; 
            transform: scale(1.1) rotate(5deg);
        }
        
        .price-card-header h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text-dark); transition: color 0.3s; }
        .price-card:hover .price-card-header h3 { color: var(--accent-primary); }
        
        .price-list-items { margin-bottom: 25px; }
        .price-item-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px; color: var(--text-muted); font-weight: 500; }
        .price-item-row span:last-child { color: var(--text-dark); font-weight: 700; transition: color 0.3s; }
        .price-card:hover .price-item-row span:last-child { color: var(--accent-primary); }

        .btn-pesan-sekarang { 
            width: 100%; 
            padding: 12px; 
            background: rgba(168, 85, 247, 0.08); 
            color: var(--accent-primary); 
            border: 1px solid rgba(168, 85, 247, 0.2); 
            border-radius: 12px; 
            font-size: 13px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px; 
            text-decoration: none; 
        }
        
        .price-card:hover .btn-pesan-sekarang { 
            background: var(--accent-primary); 
            color: white; 
            box-shadow: 0 8px 20px var(--glow-shadow);
        }

        /* FAQ SECTION */
        .faq-section { padding: 90px 8%; background: var(--bg-main); }
        .faq-header { text-align: center; max-width: 700px; margin: 0 auto 50px auto; }
        .faq-header small { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--accent-primary); display: block; margin-bottom: 10px; }
        .faq-header h2 { font-size: 32px; font-weight: 800; color: var(--text-dark); margin: 0; }

        .faq-container { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px; }
        .faq-item { background: var(--faq-bg); border: 1px solid var(--border-light); border-radius: 14px; overflow: hidden; transition: 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .faq-question { padding: 20px 24px; font-size: 16px; font-weight: 700; color: var(--text-dark); display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
        .faq-question i { color: var(--accent-primary); transition: transform 0.3s; }
        .faq-answer { padding: 0 24px 20px 24px; font-size: 14px; color: var(--text-muted); line-height: 1.6; display: none; }
        .faq-item.active .faq-answer { display: block; }
        .faq-item.active .faq-question i { transform: rotate(180deg); }

        /* MODAL & UI FORM MODERN */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(12, 16, 30, 0.7); backdrop-filter: blur(8px); z-index: 1000; justify-content: center; align-items: center; animation: fadeInModal 0.3s ease; }
        
        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-box { background: var(--card-bg); width: 100%; max-width: 500px; padding: 35px; border-radius: 28px; box-shadow: 0 25px 60px rgba(12,16,30,0.3); position: relative; text-align: left; max-height: 90vh; overflow-y: auto; transform: scale(0.95); animation: scaleUpModal 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; border: 1px solid var(--border-light); color: var(--text-dark); }
        
        @keyframes scaleUpModal {
            to { transform: scale(1); }
        }

        .close-modal { position: absolute; top: 22px; right: 22px; width: 34px; height: 34px; background: var(--border-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; cursor: pointer; color: var(--text-muted); transition: 0.2s; }
        .close-modal:hover { background: var(--accent-primary); color: white; }
        
        .modal-title-area { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
        .modal-title-icon { width: 44px; height: 44px; background: rgba(168, 85, 247, 0.12); color: var(--accent-primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .modal-title-area h3 { margin: 0; font-size: 18px; font-weight: 800; color: var(--text-dark); }
        .modal-title-area p { margin: 2px 0 0 0; font-size: 12.5px; color: var(--text-muted); }

        .modal-tabs { display: flex; gap: 8px; margin-bottom: 24px; background: var(--border-light); padding: 5px; border-radius: 14px; }
        .tab-btn { flex: 1; padding: 11px; border: none; background: transparent; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); border-radius: 10px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .tab-btn.active { background: var(--card-bg); color: var(--accent-primary); box-shadow: 0 4px 15px rgba(12,16,30,0.1); }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Floating Input Modern Style */
        .form-group-modern { margin-bottom: 18px; position: relative; }
        .form-group-modern label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .input-icon-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon-wrapper i { position: absolute; left: 16px; color: var(--text-muted); font-size: 15px; transition: 0.2s; }
        
        .form-control-modern { width: 100%; padding: 13px 16px 13px 46px; border: 1.5px solid var(--border-light); border-radius: 14px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; box-sizing: border-box; background-color: var(--bg-main); color: var(--text-dark); transition: all 0.2s ease; }
        .form-control-modern:focus { outline: none; border-color: var(--accent-primary); box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.15); }
        .form-control-modern:focus ~ i, .input-icon-wrapper:focus-within i { color: var(--accent-primary); }
        
        textarea.form-control-modern { padding-left: 16px !important; resize: none; }

        .estimation-box { background: rgba(168, 85, 247, 0.08); border: 1.5px dashed rgba(168, 85, 247, 0.35); padding: 14px 18px; border-radius: 14px; margin-bottom: 18px; font-size: 13px; font-weight: 700; color: var(--text-dark); }
        
        .btn-submit-modern { width: 100%; padding: 14px; background: var(--accent-primary); color: #ffffff; border: none; border-radius: 14px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.25s ease; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 6px 20px var(--glow-shadow); }
        .btn-submit-modern:hover { opacity: 0.9; transform: translateY(-2px); }
        
        .btn-purple { background: var(--accent-purple); box-shadow: 0 6px 20px rgba(138, 84, 120, 0.25); }
        .btn-purple:hover { opacity: 0.9; }

        /* QRIS & Nota Struk Detail */
        .qris-card { background: var(--card-bg); border: 1px solid var(--border-light); padding: 25px; border-radius: 20px; text-align: center; margin-top: 10px; box-shadow: 0 10px 30px rgba(12,16,30,0.1); }
        .qris-img-box { background: #ffffff; padding: 10px; border-radius: 12px; display: inline-block; margin: 10px 0; border: 1px solid var(--border-light); }
        .qris-img-box img { width: 160px; height: 160px; display: block; margin: 0 auto; }
        .qris-total-tag { font-size: 16px; font-weight: 800; color: var(--text-dark); background: rgba(168, 85, 247, 0.1); padding: 10px 15px; border-radius: 10px; margin: 10px 0; display: inline-block; width: 100%; box-sizing: border-box; border: 1px dashed var(--accent-primary); }
        
        .nota-struk { background: var(--bg-main); border: 1.5px dashed var(--border-light); padding: 14px; border-radius: 12px; text-align: left; margin-bottom: 12px; font-size: 12.5px; }
        .nota-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .nota-row:last-child { margin-bottom: 0; }
        .nota-label { color: var(--text-muted); font-weight: 500; }
        .nota-value { color: var(--text-dark); font-weight: 700; text-align: right; }

        .qris-btn { width: 100%; padding: 14px; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; align-items: center; gap: 8px; text-decoration: none; box-sizing: border-box; margin-bottom: 10px; }
        .qris-btn-green { background: #27ae60; color: #ffffff; }
        .qris-btn-green:hover { background: #219653; }
        .qris-btn-grey { background: #7A869A; color: #ffffff; }
        .qris-btn-grey:hover { background: #626f84; }

        .result-box-inner { background: var(--bg-main); padding: 18px; border-radius: 14px; border: 1px solid var(--border-light); margin-top: 15px; }
        .result-item { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
        .result-item:last-child { margin-bottom: 0; }
        .result-item .label { color: var(--text-muted); font-weight: 500; }
        .result-item .value { color: var(--text-dark); font-weight: 700; text-align: right; }
        
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .status-baru { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        .status-proses { background: rgba(243, 156, 18, 0.15); color: var(--warning); }
        .status-selesai { background: rgba(46, 204, 113, 0.15); color: var(--success); }
        .status-diambil { background: rgba(138, 84, 120, 0.15); color: var(--accent-purple); }
        
        .alert-msg { background: rgba(231, 76, 60, 0.15); color: var(--danger); padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 600; text-align: center; margin-top: 15px; border: 1px solid rgba(231, 76, 60, 0.3); }

        footer { text-align: center; padding: 30px; color: var(--text-muted); font-size: 13px; border-top: 1px solid var(--border-light); background: var(--card-bg); }

        @media print {
            body * { visibility: hidden; }
            .qris-card, .qris-card * { visibility: visible; }
            .qris-card { 
                position: absolute; 
                left: 50%; 
                top: 0; 
                transform: translateX(-50%); 
                width: 100%; 
                max-width: 350px; 
                border: none; 
                box-shadow: none; 
                padding: 10px; 
                background: white !important; 
            }
            .qris-btn { display: none !important; }
            @page { 
                size: 80mm auto; 
                margin: 0mm; 
            }
        }
    </style>
    
    <script>
        function toggleMenu() {
            let menu = document.getElementById('navDropdown');
            menu.classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.closest('.menu-toggle') && !event.target.closest('.nav-dropdown')) {
                let menu = document.getElementById('navDropdown');
                if (menu && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            }
        }

        function toggleFaq(element) {
            let parent = element.parentElement;
            parent.classList.toggle('active');
        }

        function openModal(modalId, namaLayananDefault = '') {
            document.getElementById(modalId).style.display = 'flex';
            if(namaLayananDefault !== '') {
                let selectLayanan = document.getElementById('nama_layanan_pilih');
                if(selectLayanan) {
                    selectLayanan.value = namaLayananDefault;
                    hitungEstimasi();
                }
            }
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            if(tabName === 'order') {
                document.getElementById('tab-order').classList.add('active');
                document.getElementById('btn-tab-order').classList.add('active');
            } else {
                document.getElementById('tab-track').classList.add('active');
                document.getElementById('btn-tab-track').classList.add('active');
            }
        }

        function hitungEstimasi() {
            let tipe = document.getElementById('tipe_layanan').value;
            let layananPilih = document.getElementById('nama_layanan_pilih').value;
            let jumlah = parseFloat(document.getElementById('jumlah_input').value) || 0;
            let hargaDasar = 13000; 

            if (layananPilih === 'Bedcover Kecil') hargaDasar = 35000;
            else if (layananPilih === 'Bedcover Sedang') hargaDasar = 45000;
            else if (layananPilih === 'Bedcover Besar') hargaDasar = 55000;
            else if (layananPilih === 'Sprei Set Kecil') hargaDasar = 25000;
            else if (layananPilih === 'Sprei Set Sedang') hargaDasar = 35000;
            else if (layananPilih === 'Sprei Set Besar') hargaDasar = 45000;
            else if (layananPilih === 'Selimut Tipis') hargaDasar = 25000;
            else if (layananPilih === 'Selimut Tebal') hargaDasar = 35000;
            else if (layananPilih === 'Handuk Kecil') hargaDasar = 20000;
            else if (layananPilih === 'Handuk Besar') hargaDasar = 30000;
            else if (layananPilih === 'Sepatu') hargaDasar = 55000;
            else if (layananPilih === 'Topi') hargaDasar = 15000;
            else if (layananPilih === 'Jas') hargaDasar = 50000;
            else if (layananPilih === 'Kebaya') hargaDasar = 35000;
            else if (layananPilih === 'Dress') hargaDasar = 65000;
            else if (layananPilih === 'Long Dress') hargaDasar = 90000;
            else if (layananPilih === 'Kaos Pendek') hargaDasar = 20000;
            else if (layananPilih === 'Kaos Panjang') hargaDasar = 30000;
            else if (layananPilih === 'Kemeja Pendek') hargaDasar = 35000;
            else if (layananPilih === 'Kemeja Panjang') hargaDasar = 45000;
            else if (layananPilih === 'Sweater') hargaDasar = 25000;
            else if (layananPilih === 'Jaket') hargaDasar = 35000;
            else if (layananPilih === 'Jaket Kulit') hargaDasar = 45000;
            else if (layananPilih === 'Blazer') hargaDasar = 50000;
            else if (layananPilih === 'Celana / Rok Panjang') hargaDasar = 25000;
            else if (layananPilih === 'Celana / Rok Pendek') hargaDasar = 15000;
            else if (layananPilih === 'Celana Jeans') hargaDasar = 35000;
            else if (layananPilih === 'Cuci Kiloan Regular') hargaDasar = 13000;

            let subtotal = hargaDasar * jumlah;
            let ongkir = (tipe === 'Antar-Jemput') ? 5000 : 0;
            let total = subtotal + ongkir;

            let teksEstimasi = 'Harga: Rp ' + hargaDasar.toLocaleString('id-ID') + ' | Subtotal: Rp ' + subtotal.toLocaleString('id-ID');
            if (ongkir > 0) {
                teksEstimasi += ' + Kurir: Rp 5.000';
            }
            teksEstimasi += '<br><b style="color: var(--accent-primary); font-size: 14px;">Total: Rp ' + total.toLocaleString('id-ID') + '</b>';

            document.getElementById('estimasi-harga').innerHTML = teksEstimasi;
        }

        // Script Peta Interaktif Leaflet
        let map = null;
        let marker = null;
        let selectedLat = -6.2088; // Default Jakarta
        let selectedLng = 106.8456;

        function bukaPetaModal() {
            document.getElementById('modal-peta').style.display = 'flex';
            setTimeout(function() {
                if (!map) {
                    map = L.map('leaflet-map').setView([selectedLat, selectedLng], 14);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);

                    marker = L.marker([selectedLat, selectedLng], {draggable: true}).addTo(map);

                    marker.on('dragend', function(e) {
                        let pos = marker.getLatLng();
                        selectedLat = pos.lat;
                        selectedLng = pos.lng;
                    });

                    map.on('click', function(e) {
                        selectedLat = e.latlng.lat;
                        selectedLng = e.latlng.lng;
                        marker.setLatLng([selectedLat, selectedLng]);
                    });
                } else {
                    map.invalidateSize();
                }
            }, 200);
        }

        function tutupPetaModal() {
            document.getElementById('modal-peta').style.display = 'none';
        }

        function simpanLokasiPeta() {
            let url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${selectedLat}&lon=${selectedLng}&zoom=18&addressdetails=1`;
            
            let btn = document.getElementById('btn-simpan-peta');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mendapatkan Nama Lokasi...';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    let namaLokasi = data.display_name ? data.display_name : ("Lat: " + selectedLat.toFixed(5) + ", Lng: " + selectedLng.toFixed(5));
                    
                    document.getElementById('titik_peta_input').value = namaLokasi;
                    
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Gunakan Titik Lokasi Ini';
                    tutupPetaModal();
                })
                .catch(error => {
                    document.getElementById('titik_peta_input').value = "Koordinat: " + selectedLat.toFixed(5) + ", " + selectedLng.toFixed(5);
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Gunakan Titik Lokasi Ini';
                    tutupPetaModal();
                });
        }
    </script>
</head>
<body>
    <!-- TOMBOL WHATSAPP MENGAMBANG -->
    <a href="https://wa.me/628123456789?text=Halo%20R&F%20Express,%20saya%20ingin%20bertanya%20mengenai%20layanan%20laundry%2024%20jam." target="_blank" class="whatsapp-float">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <i class="fa-solid fa-truck-fast"></i> R&F Express
        </a>
        
        <div class="nav-right">
            <!-- SAKELAR SLIDER DARK MODE -->
            <div class="theme-switch-wrapper">
                <i class="fa-solid fa-sun slider-icon" style="color: #F59E0B;"></i>
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" onclick="switchTheme(event)" />
                    <span class="slider-toggle"></span>
                </label>
                <i class="fa-solid fa-moon slider-icon" style="color: #c084fc;"></i>
            </div>

            <!-- TOMBOL QUICK ACTION ORDER / LACAK -->
            <button type="button" onclick="openModal('modal-order')" class="btn-order-open">
                <i class="fa-solid fa-cart-plus"></i> Order / Lacak
            </button>
            
            <button type="button" class="menu-toggle" onclick="toggleMenu()">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Dropdown Menu Tersembunyi -->
            <div id="navDropdown" class="nav-dropdown">
                <a href="index.php"><i class="fa-solid fa-house" style="color: var(--accent-primary);"></i> Beranda</a>
                <a href="#tentang"><i class="fa-solid fa-circle-info" style="color: var(--accent-primary);"></i> Tentang Kami</a>
                <a href="#mengapa-kami"><i class="fa-solid fa-star" style="color: var(--accent-primary);"></i> Keunggulan</a>
                <a href="#pricelist"><i class="fa-solid fa-tags" style="color: var(--accent-primary);"></i> Pricelist & Layanan</a>
                <a href="#faqs"><i class="fa-solid fa-circle-question" style="color: var(--accent-primary);"></i> FAQs</a>
                <a href="#" onclick="openModal('modal-admin'); toggleMenu();"><i class="fa-solid fa-lock" style="color: var(--accent-navy);"></i> Login Admin</a>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="hero-content">
            <h1>Laundry Antar-Jemput 24 Jam Langsung dari Rumah Anda</h1>
            <p>Buka 24 jam penuh! Biarkan kurir profesional kami menjemput, mencuci, dan mengantar kembali pakaian Anda dalam keadaan bersih, harum, dan rapi kapan pun Anda butuhkan.</p>
        </div>
    </section>

    <!-- MENGAPA MEMILIH KAMI -->
    <div id="mengapa-kami" class="why-us-section">
        <small>Kenapa Harus Memilih Kami</small>
        <h2>Mengapa Memilih R&F Profesional Laundry? Solusi Jasa Laundry Profesional 24 Jam</h2>
        <div class="why-grid">
            <div class="why-card">
                <div class="why-icon"><i class="fa-solid fa-clock"></i></div>
                <h4>Layanan 24 Jam</h4>
                <p>Kami siap melayani pesanan, penjemputan, dan pengantaran laundry Anda kapan saja selama 24 jam penuh setiap hari.</p>
            </div>
            <div class="why-card">
                <div class="why-icon"><i class="fa-solid fa-rocket"></i></div>
                <h4>Teknologi Modern</h4>
                <p>Didukung oleh mesin cuci berteknologi terkini, kami memastikan kebersihan maksimal dan hasil yang memuaskan.</p>
            </div>
            <div class="why-card">
                <div class="why-icon"><i class="fa-solid fa-motorcycle"></i></div>
                <h4>Layanan Antar Jemput</h4>
                <p>Nikmati kemudahan dengan layanan kurir antar jemput laundry langsung dari rumah Anda dengan cepat dan praktis.</p>
            </div>
            <div class="why-card">
                <div class="why-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h4>Garansi Kebersihan</h4>
                <p>Dengan sistem manajemen yang efisien, kami menjamin perawatan pakaian khusus agar tetap bersih dan aman.</p>
            </div>
        </div>
    </div>

    <!-- TENTANG KAMI -->
    <div id="tentang" class="about-section">
        <div class="about-img-wrapper">
            <img src="laundry-splash.jpg" alt="R&F Express Laundry">
        </div>
        <div class="about-content">
            <small>Tentang Kami</small>
            <h2>Jasa Laundry Profesional & Buka 24 Jam Terpercaya</h2>
            <p>Selamat datang di <b>R&F Express</b>, solusi terpercaya untuk semua kebutuhan laundry Anda kapan pun dibutuhkan. Kami berkomitmen memberikan pelayanan laundry profesional beroperasi 24 jam dengan standar kualitas terbaik.</p>
            <p>Melayani berbagai area strategis, kami mengutamakan kepuasan pelanggan dengan hasil cucian bersih, wangi, dan rapi. Didukung oleh teknologi modern dan tenaga kerja berpengalaman, kami hadir 24 jam penuh untuk memastikan pakaian Anda mendapatkan perawatan terbaik.</p>
        </div>
    </div>

    <!-- PRICELIST KATALOG -->
    <div id="pricelist" class="pricelist-section">
        <h2>Pricelist & Daftar Layanan R&F Express</h2>
        <p>Pilih kategori layanan terbaik dan nikmati kemudahan pesan langsung secara online 24 jam.</p>

        <div class="price-grid">
            <!-- 1. Cuci Kiloan -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                        <h3>Cuci Kiloan Regular</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Cuci + Setrika Regular (Per Kg)</span><span>Rp 13.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Cuci Kiloan Regular')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 2. Bedcover -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-bed"></i></div>
                        <h3>Bedcover</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Bedcover Kecil</span><span>Rp 35.000</span></div>
                        <div class="price-item-row"><span>Bedcover Sedang</span><span>Rp 45.000</span></div>
                        <div class="price-item-row"><span>Bedcover Besar</span><span>Rp 55.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Bedcover Sedang')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 3. Sprei -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-sheet-plastic"></i></div>
                        <h3>Sprei</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Sprei Set Kecil</span><span>Rp 25.000</span></div>
                        <div class="price-item-row"><span>Sprei Set Sedang</span><span>Rp 35.000</span></div>
                        <div class="price-item-row"><span>Sprei Set Besar</span><span>Rp 45.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Sprei Set Sedang')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 4. Selimut -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-shirt"></i></div>
                        <h3>Selimut</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Selimut Tipis</span><span>Rp 25.000</span></div>
                        <div class="price-item-row"><span>Selimut Tebal</span><span>Rp 35.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Selimut Tebal')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 5. Handuk, Sepatu, Topi -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-mitten"></i></div>
                        <h3>Handuk, Sepatu, Topi</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Handuk Kecil</span><span>Rp 20.000</span></div>
                        <div class="price-item-row"><span>Handuk Besar</span><span>Rp 30.000</span></div>
                        <div class="price-item-row"><span>Sepatu</span><span>Rp 55.000</span></div>
                        <div class="price-item-row"><span>Topi</span><span>Rp 15.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Handuk Besar')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 6. Jas, Kebaya, Dress -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-person-dress"></i></div>
                        <h3>Jas, Kebaya, Dress</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Jas</span><span>Rp 50.000</span></div>
                        <div class="price-item-row"><span>Kebaya</span><span>Rp 35.000</span></div>
                        <div class="price-item-row"><span>Dress</span><span>Rp 65.000</span></div>
                        <div class="price-item-row"><span>Long Dress</span><span>Rp 90.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Jas')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 7. Kaos -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-shirt"></i></div>
                        <h3>Kaos</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Kaos Pendek</span><span>Rp 20.000</span></div>
                        <div class="price-item-row"><span>Kaos Panjang</span><span>Rp 30.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Kaos Pendek')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 8. Kemeja -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-user-tie"></i></div>
                        <h3>Kemeja</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Kemeja Pendek</span><span>Rp 35.000</span></div>
                        <div class="price-item-row"><span>Kemeja Panjang</span><span>Rp 45.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Kemeja Pendek')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 9. Sweater dan Jaket -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-vest"></i></div>
                        <h3>Sweater & Jaket</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Sweater</span><span>Rp 25.000</span></div>
                        <div class="price-item-row"><span>Jaket</span><span>Rp 35.000</span></div>
                        <div class="price-item-row"><span>Jaket Kulit</span><span>Rp 45.000</span></div>
                        <div class="price-item-row"><span>Blazer</span><span>Rp 50.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Jaket')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>

            <!-- 10. Celana -->
            <div class="price-card">
                <div>
                    <div class="price-card-header">
                        <div class="price-card-icon"><i class="fa-solid fa-socks"></i></div>
                        <h3>Celana</h3>
                    </div>
                    <div class="price-list-items">
                        <div class="price-item-row"><span>Celana / Rok Panjang</span><span>Rp 25.000</span></div>
                        <div class="price-item-row"><span>Celana / Rok Pendek</span><span>Rp 15.000</span></div>
                        <div class="price-item-row"><span>Celana Jeans</span><span>Rp 35.000</span></div>
                    </div>
                </div>
                <button type="button" onclick="openModal('modal-order', 'Celana / Rok Panjang')" class="btn-pesan-sekarang">
                    <i class="fa-solid fa-cart-shopping"></i> Pesan Sekarang
                </button>
            </div>
        </div>
    </div>

    <!-- FAQS SECTION -->
    <div id="faqs" class="faq-section">
        <div class="faq-header">
            <small>FAQs</small>
            <h2>Pertanyaan Umum tentang Jasa Laundry Profesional 24 Jam</h2>
        </div>

        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Apa saja layanan yang ditawarkan oleh R&F Express?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Kami menawarkan berbagai layanan lengkap mulai dari Cuci Kiloan Regular hingga Cuci Satuan spesifik seperti Bedcover, Sprei, Selimut, Jas, Jaket, Sepatu, dan banyak lagi dengan perawatan khusus sesuai jenis bahan pakaian Anda.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Apakah R&F Express buka dan melayani 24 jam?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Ya, betul sekali! R&F Express beroperasi melayani pelanggan selama 24 jam penuh. Anda dapat melakukan pemesanan online atau mengatur penjemputan cucian kapan pun Anda butuhkan.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Apakah ada layanan antar jemput laundry?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Ada. Kami menyediakan layanan kurir profesional yang siap menjemput cucian kotor langsung dari rumah Anda dan mengantarkannya kembali setelah bersih dan rapi.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Berapa lama proses laundry selesai?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Proses pengerjaan standar memakan waktu 3 hari dengan hasil cucian yang dijamin bersih, harum, dan disetrika rapi sesuai standar kualitas profesional kami.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Bagaimana cara melakukan pemesanan?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Anda dapat melakukan pemesanan secara instan langsung melalui website ini dengan mengeklik tombol "Order / Lacak" atau mengeklik "Pesan Sekarang" pada daftar pricelist di atas. Anda juga bisa menghubungi kami melalui tombol WhatsApp yang tersedia.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Apakah pakaian saya aman dicuci di R&F Express?</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Sangat aman. Kami menggunakan mesin cuci modern berteknologi terkini serta deterjen berkualitas tinggi yang ramah kain. Selain itu, cucian Anda diproses secara profesional dan terpisah dari pelanggan lain.
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Pemesanan & Tracking -->
    <div id="modal-order" class="modal-overlay" style="<?php echo ($pesan_sukses_order || $data_transaksi || $pesan_tracking || $active_tab == 'track') ? 'display: flex;' : ''; ?>">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('modal-order')"><i class="fa-solid fa-xmark"></i></span>
            
            <div class="modal-title-area">
                <div class="modal-title-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <div>
                    <h3>R&F Express 24 Jam</h3>
                    <p>Layanan antar-jemput laundry profesional & instan</p>
                </div>
            </div>

            <!-- Tab Switcher Modern -->
            <div class="modal-tabs">
                <button type="button" id="btn-tab-order" class="tab-btn <?php echo ($active_tab == 'order') ? 'active' : ''; ?>" onclick="switchTab('order')">
                    <i class="fa-solid fa-cart-plus"></i> Request Penjemputan
                </button>
                <button type="button" id="btn-tab-track" class="tab-btn <?php echo ($active_tab == 'track') ? 'active' : ''; ?>" onclick="switchTab('track')">
                    <i class="fa-solid fa-magnifying-glass"></i> Lacak Pesanan
                </button>
            </div>

            <!-- TAB 1: FORM REQUEST PENJEMPUTAN -->
            <div id="tab-order" class="tab-content <?php echo ($active_tab == 'order') ? 'active' : ''; ?>">
                
                <?php if ($pesan_sukses_order && $invoice_terbaru): ?>
                    <div class="qris-card">
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 2px;">
                            INVOICE: <b style="color: var(--accent-primary);"><?php echo $invoice_terbaru; ?></b>
                        </div>
                        
                        <div class="nota-struk">
                            <div class="nota-row"><span class="nota-label">Nama Pemesan</span><span class="nota-value"><?php echo htmlspecialchars($nama_pelanggan_baru); ?></span></div>
                            <div class="nota-row"><span class="nota-label">No. WhatsApp</span><span class="nota-value"><?php echo htmlspecialchars($no_hp_baru); ?></span></div>
                            <div class="nota-row"><span class="nota-label">Layanan / Item</span><span class="nota-value"><?php echo htmlspecialchars($layanan_baru); ?></span></div>
                            <div class="nota-row"><span class="nota-label">Jumlah</span><span class="nota-value"><?php echo $jumlah_baru; ?> Unit</span></div>
                            <div class="nota-row"><span class="nota-label">Metode</span><span class="nota-value"><?php echo htmlspecialchars($tipe_layanan_baru); ?></span></div>
                            <div class="nota-row"><span class="nota-label">Estimasi Selesai</span><span class="nota-value" style="color: var(--accent-primary);">3 Hari (Otomatis)</span></div>
                        </div>

                        <div class="qris-total-tag">
                            Total Tagihan: Rp <?php echo number_format($total_bayar_terbaru, 0, ',', '.'); ?>
                        </div>

                        <div class="qris-img-box">
                            <img src="R&FQRIS.png" alt="QRIS R&F Laundry" style="width: 160px; height: 160px; object-fit: contain;">
                        </div>

                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 15px; line-height: 1.4;">
                            Scan QRIS di atas menggunakan BCA, Mandiri, BRI, BNI, GoPay, OVO, DANA, atau m-Banking pilihan Anda.
                        </div>

                        <button type="button" onclick="window.print()" class="qris-btn qris-btn-green">
                            <i class="fa-solid fa-print"></i> Cetak / Simpan Nota Invoice
                        </button>

                        <a href="index.php" class="qris-btn qris-btn-grey">
                            <i class="fa-solid fa-check"></i> Selesai & Kembali ke Beranda
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="form-group-modern">
                            <label>Nama Lengkap</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-regular fa-user"></i>
                                <input type="text" name="nama_pelanggan" class="form-control-modern" placeholder="Masukkan nama lengkap Anda" required>
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label>Nomor WhatsApp (Aktif)</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-brands fa-whatsapp"></i>
                                <input type="text" name="no_hp" class="form-control-modern" placeholder="Contoh: 08123456789" required>
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label>Metode Penyerahan Cucian</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-motorcycle"></i>
                                <select name="tipe_layanan" id="tipe_layanan" class="form-control-modern" onchange="hitungEstimasi()" required>
                                    <option value="Antar-Jemput">Kurir Antar-Jemput (+ Rp 5.000)</option>
                                    <option value="Drop-off">Datang Langsung ke Toko (Tanpa Ongkir)</option>
                                </select>
                            </div>
                        </div>

                        <!-- TITIK PETA INTERAKTIF -->
                        <div class="form-group-modern">
                            <label>Titik Lokasi Peta Penjemputan</label>
                            <div style="display: flex; gap: 10px;">
                                <div class="input-icon-wrapper" style="flex: 1;">
                                    <i class="fa-solid fa-map-pin"></i>
                                    <input type="text" name="titik_peta" id="titik_peta_input" class="form-control-modern" placeholder="Klik tombol di samping untuk pilih peta" readonly required>
                                </div>
                                <button type="button" id="btn-pilih-peta" onclick="bukaPetaModal()" style="background: var(--accent-primary); color: white; border: none; padding: 0 16px; border-radius: 14px; font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 6px; transition: background 0.3s;">
                                    <i class="fa-solid fa-map-location-dot"></i> Pilih di Peta
                                </button>
                            </div>
                        </div>

                        <!-- CATATAN / PATOKAN RUMAH -->
                        <div class="form-group-modern">
                            <label>Patokan Rumah / Catatan Kurir</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-signs-post"></i>
                                <input type="text" name="patokan" class="form-control-modern" placeholder="Contoh: Rumah pagar hitam depan masjid / dekat pos ronda">
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label>Pilih Layanan & Ukuran Detail</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-shirt"></i>
                                <select name="nama_layanan_pilih" id="nama_layanan_pilih" class="form-control-modern" onchange="hitungEstimasi()" required>
                                    <optgroup label="Layanan Kiloan">
                                        <option value="Cuci Kiloan Regular">Cuci Kiloan Regular (Rp 13.000 / Kg)</option>
                                    </optgroup>
                                    <optgroup label="Bedcover">
                                        <option value="Bedcover Kecil">Bedcover Kecil (Rp 35.000)</option>
                                        <option value="Bedcover Sedang">Bedcover Sedang (Rp 45.000)</option>
                                        <option value="Bedcover Besar">Bedcover Besar (Rp 55.000)</option>
                                    </optgroup>
                                    <optgroup label="Sprei">
                                        <option value="Sprei Set Kecil">Sprei Set Kecil (Rp 25.000)</option>
                                        <option value="Sprei Set Sedang">Sprei Set Sedang (Rp 35.000)</option>
                                        <option value="Sprei Set Besar">Sprei Set Besar (Rp 45.000)</option>
                                    </optgroup>
                                    <optgroup label="Selimut">
                                        <option value="Selimut Tipis">Selimut Tipis (Rp 25.000)</option>
                                        <option value="Selimut Tebal">Selimut Tebal (Rp 35.000)</option>
                                    </optgroup>
                                    <optgroup label="Handuk, Sepatu & Topi">
                                        <option value="Handuk Kecil">Handuk Kecil (Rp 20.000)</option>
                                        <option value="Handuk Besar">Handuk Besar (Rp 30.000)</option>
                                        <option value="Sepatu">Sepatu (Rp 55.000)</option>
                                        <option value="Topi">Topi (Rp 15.000)</option>
                                    </optgroup>
                                    <optgroup label="Jas, Kebaya & Dress">
                                        <option value="Jas">Jas (Rp 50.000)</option>
                                        <option value="Kebaya">Kebaya (Rp 35.000)</option>
                                        <option value="Dress">Dress (Rp 65.000)</option>
                                        <option value="Long Dress">Long Dress (Rp 90.000)</option>
                                    </optgroup>
                                    <optgroup label="Kaos">
                                        <option value="Kaos Pendek">Kaos Pendek (Rp 20.000)</option>
                                        <option value="Kaos Panjang">Kaos Panjang (Rp 30.000)</option>
                                    </optgroup>
                                    <optgroup label="Kemeja">
                                        <option value="Kemeja Pendek">Kemeja Pendek (Rp 35.000)</option>
                                        <option value="Kemeja Panjang">Kemeja Panjang (Rp 45.000)</option>
                                    </optgroup>
                                    <optgroup label="Sweater & Jaket">
                                        <option value="Sweater">Sweater (Rp 25.000)</option>
                                        <option value="Jaket">Jaket (Rp 35.000)</option>
                                        <option value="Jaket Kulit">Jaket Kulit (Rp 45.000)</option>
                                        <option value="Blazer">Blazer (Rp 50.000)</option>
                                    </optgroup>
                                    <optgroup label="Celana & Rok">
                                        <option value="Celana / Rok Panjang">Celana / Rok Panjang (Rp 25.000)</option>
                                        <option value="Celana / Rok Pendek">Celana / Rok Pendek (Rp 15.000)</option>
                                        <option value="Celana Jeans">Celana Jeans (Rp 35.000)</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label>Estimasi Jumlah (Kg / Pcs)</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-scale-balanced"></i>
                                <input type="number" step="0.1" name="jumlah" id="jumlah_input" class="form-control-modern" placeholder="Contoh: 1" oninput="hitungEstimasi()" required>
                            </div>
                        </div>

                        <div class="estimation-box" id="estimasi-harga">
                            <span><i class="fa-solid fa-calculator"></i> Total: Rp 0 (Estimasi: 3 Hari)</span>
                        </div>

                        <div class="form-group-modern">
                            <label>Catatan Khusus untuk Cucian</label>
                            <textarea name="catatan" class="form-control-modern" placeholder="Contoh: Pisahkan baju putih, jangan disikat terlalu keras." rows="2"></textarea>
                        </div>

                        <button type="submit" name="pesan_mandiri" class="btn-submit-modern">
                            <i class="fa-solid fa-qrcode"></i> Lanjut ke Pembayaran QRIS
                        </button>
                    </form>
                <?php endif; ?>

            </div>

            <!-- TAB 2: LACAK PESANAN -->
            <div id="tab-track" class="tab-content <?php echo ($active_tab == 'track') ? 'active' : ''; ?>">
                <form method="POST" action="">
                    <div class="form-group-modern">
                        <label>Nomor Invoice / Nomor HP</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="kode_invoice" class="form-control-modern" placeholder="Contoh: TRX-179145 or 08123" value="<?php echo htmlspecialchars($pencarian); ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="cek_status" class="btn-submit-modern btn-purple">
                        <i class="fa-solid fa-search"></i> Lacak Status Laundry
                    </button>
                </form>

                <?php if ($data_transaksi): ?>
                <div class="result-box-inner">
                    <div style="font-weight: 700; font-size: 13px; color: var(--text-dark); margin-bottom: 10px; display:flex; justify-content:space-between;">
                        <span>Hasil Tracking:</span>
                        <span><?php echo $data_transaksi['kode_invoice']; ?></span>
                    </div>
                    <div class="result-item"><span class="label">Nama Pemesan</span><span class="value"><?php echo $data_transaksi['nama_pelanggan']; ?></span></div>
                    <div class="result-item"><span class="label">Layanan</span><span class="value"><?php echo $data_transaksi['nama_layanan']; ?></span></div>
                    <div class="result-item"><span class="label">Total Biaya</span><span class="value" style="color: var(--accent-purple);">Rp <?php echo number_format($data_transaksi['total_bayar'], 0, ',', '.'); ?></span></div>
                    
                    <?php 
                        $tgl_masuk = isset($data_transaksi['tgl_terima']) ? $data_transaksi['tgl_terima'] : (isset($data_transaksi['tgl_masuk']) ? $data_transaksi['tgl_masuk'] : null);
                        $batas_waktu = isset($data_transaksi['batas_waktu']) ? $data_transaksi['batas_waktu'] : null;
                    ?>
                    <?php if ($tgl_masuk): ?>
                    <div class="result-item"><span class="label">Tanggal Masuk</span><span class="value"><?php echo date('d M Y', strtotime($tgl_masuk)); ?></span></div>
                    <?php endif; ?>

                    <?php if ($batas_waktu): ?>
                    <div class="result-item"><span class="label">Estimasi Selesai (3 Hari)</span><span class="value" style="color: var(--accent-primary);"><?php echo date('d M Y', strtotime($batas_waktu)); ?></span></div>
                    <?php endif; ?>

                    <div class="result-item" style="align-items: center; margin-top: 10px; border-top: 1px solid var(--border-light); padding-top: 8px;">
                        <span class="label">Status Pesanan</span>
                        <span class="value">
                            <?php 
                                $status = isset($data_transaksi['status_pesanan']) ? $data_transaksi['status_pesanan'] : 'Baru';
                                $cls = 'status-baru';
                                if ($status == 'Proses') $cls = 'status-proses';
                                if ($status == 'Selesai') $cls = 'status-selesai';
                                if ($status == 'Diambil') $cls = 'status-diambil';
                            ?>
                            <span class="badge-status <?php echo $cls; ?>"><?php echo $status; ?></span>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Modal Peta Interaktif Leaflet -->
    <div id="modal-peta" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(12,16,30,0.65); backdrop-filter: blur(6px); z-index: 2000; justify-content: center; align-items: center;">
        <div style="background: var(--card-bg); width: 90%; max-width: 550px; padding: 25px; border-radius: 24px; box-shadow: 0 20px 50px rgba(12,16,30,0.3); border: 1px solid var(--border-light); color: var(--text-dark);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0; font-size: 16px; color: var(--text-dark);"><i class="fa-solid fa-map-location-dot"></i> Klik Titik Lokasi Penjemputan</h4>
                <span onclick="tutupPetaModal()" style="width: 32px; height: 32px; background: var(--border-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted);"><i class="fa-solid fa-xmark"></i></span>
            </div>
            
            <div id="leaflet-map" style="width: 100%; height: 320px; border-radius: 14px; border: 1.5px solid var(--border-light); margin-bottom: 20px;"></div>
            
            <button type="button" id="btn-simpan-peta" onclick="simpanLokasiPeta()" class="btn-submit-modern" style="padding: 12px;">
                <i class="fa-solid fa-check"></i> Gunakan Titik Lokasi Ini
            </button>
        </div>
    </div>

    <!-- Modal Login Admin -->
    <div id="modal-admin" class="modal-overlay" style="<?php echo ($error_admin) ? 'display: flex;' : ''; ?>">
        <div class="modal-box">
            <span class="close-modal" onclick="closeModal('modal-admin')"><i class="fa-solid fa-xmark"></i></span>
            <div class="modal-title-area">
                <div class="modal-title-icon"><i class="fa-solid fa-lock"></i></div>
                <div>
                    <h3>Login Administrator</h3>
                    <p>Masuk untuk mengelola sistem R&F Express</p>
                </div>
            </div>
            <form method="POST" action="" autocomplete="off">
                <div class="form-group-modern">
                    <label>Username</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="username" class="form-control-modern" placeholder="Masukkan username" autocomplete="off" required>
                    </div>
                </div>
                <div class="form-group-modern">
                    <label>Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" name="password" class="form-control-modern" placeholder="Masukkan password" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" name="login_admin" class="btn-submit-modern" style="background: var(--accent-navy);">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk Dashboard
                </button>
            </form>
            <?php if ($error_admin): ?>
            <div class="alert-msg" style="margin-top: 15px;"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_admin; ?></div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 <strong>R&F Express</strong> &bull; Layanan Laundry Antar-Jemput 24 Jam Profesional. All rights reserved.</p>
    </footer>

    <script>
        // Skrip untuk Menangani Sakelar Slider Dark Mode & Aksen Ungu Neon
        const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
        const currentTheme = localStorage.getItem('rf_landing_theme') || 'light';

        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            toggleSwitch.checked = true;
        }

        function switchTheme(e) {
            if (e.target.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('rf_landing_theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('rf_landing_theme', 'light');
            }
        }
    </script>
</body>
</html>