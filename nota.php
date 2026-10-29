<?php
include 'koneksi.php';

// Menangkap ID transaksi dari URL
$id = $_GET['id'];

// Mengambil data lengkap berdasarkan ID
$query = mysqli_query($koneksi, "
    SELECT transaksi.*, pelanggan.nama_pelanggan, pelanggan.no_hp, pelanggan.alamat, layanan.nama_layanan, layanan.harga 
    FROM transaksi 
    JOIN pelanggan ON transaksi.id_pelanggan = pelanggan.id_pelanggan 
    JOIN layanan ON transaksi.id_layanan = layanan.id_layanan 
    WHERE transaksi.id_transaksi = '$id'
");
$data = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nota - <?php echo $data['kode_invoice']; ?></title>
    <style>
        /* Desain khusus agar mirip struk kasir (thermal printer) */
        body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 0 auto; padding: 20px; font-size: 14px;}
        h2, h3 { text-align: center; margin: 5px 0; }
        .garis { border-bottom: 1px dashed #000; margin: 10px 0; }
        .tengah { text-align: center; }
        .kiri { text-align: left; }
        .kanan { text-align: right; }
    </style>
</head>
<body onload="window.print()"> <!-- Otomatis memicu pop-up print saat halaman terbuka -->
    
    <h2>LAUNDRY BERKAH</h2>
    <p class="tengah">Jl. Mawar No. 123<br>Telp/WA: 08123456789</p>
    
    <div class="garis"></div>
    
    <p>
        No. Nota : <?php echo $data['kode_invoice']; ?><br>
        Tanggal  : <?php echo $data['tgl_terima']; ?><br>
        Pelanggan: <?php echo $data['nama_pelanggan']; ?>
    </p>
    
    <div class="garis"></div>
    
    <p>
        Layanan  : <?php echo $data['nama_layanan']; ?><br>
        Berat/Qty: <?php echo $data['qty']; ?><br>
        Harga/Sat: Rp <?php echo number_format($data['harga'], 0, ',', '.'); ?>
    </p>
    
    <div class="garis"></div>
    
    <h3>TOTAL: Rp <?php echo number_format($data['total_bayar'], 0, ',', '.'); ?></h3>
    <h3 class="tengah">Status: <?php echo $data['status_bayar']; ?></h3>
    
    <div class="garis"></div>
    
    <p class="tengah">Terima Kasih<br>Cucian Bersih, Hati Tenang</p>

</body>
</html>