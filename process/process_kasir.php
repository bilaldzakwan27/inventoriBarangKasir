<?php  
require_once(dirname(__DIR__) . '/database/database.php');  

if (isset($_POST['process_transaction'])) {  
    try {  
        // Mulai koneksi database dengan transaksi  
        $conn = getConnection();  
        $conn->beginTransaction();  

        // Generate kode unik untuk penjualan  
        $kode_penjualan = 'INV-' . date('Ymd') . '-' . uniqid();  

        // Ambil data dari form  
        $id_customer = $_POST['id_customer'];  
        $cart_data = json_decode($_POST['cart_data'], true);  

        // Buat transaksi penjualan dengan menambahkan kolom code   
        $query_penjualan = "INSERT INTO penjualan (tanggal, idcustomer, code) VALUES (NOW(), :idcustomer, :code)";  
        $stmt_penjualan = $conn->prepare($query_penjualan);  
        $stmt_penjualan->execute([  
            ':idcustomer' => $id_customer,  
            ':code' => $kode_penjualan  
        ]);  
        $id_penjualan = $conn->lastInsertId();  

        // Proses detail penjualan dan update stok  
        $query_detail = "INSERT INTO detailpenjualan (idpenjualan, idbarang, qty, hargajual) VALUES (:idpenjualan, :idbarang, :qty, :hargajual)";  
        $stmt_detail = $conn->prepare($query_detail);  

        $query_update_stok = "UPDATE barang SET stok = stok - :qty WHERE idbarang = :idbarang";  
        $stmt_update_stok = $conn->prepare($query_update_stok);  

        foreach ($cart_data as $item) {  
            // Tambah detail penjualan  
            $stmt_detail->execute([  
                ':idpenjualan' => $id_penjualan,  
                ':idbarang' => $item['id'],  
                ':qty' => $item['quantity'],  
                ':hargajual' => $item['price']  // Gunakan 'price' sesuai dengan struktur data dari frontend  
            ]);  

            // Update stok barang  
            $stmt_update_stok->execute([  
                ':qty' => $item['quantity'],  
                ':idbarang' => $item['id']  
            ]);  
        }  

        // Commit transaksi  
        $conn->commit();  

        // Redirect dengan pesan sukses dan kode penjualan  
        header("Location: ../index.php?msg=Checkout berhasil&invoice=true&kode=" . urlencode($kode_penjualan));  
        exit();  

    } catch (Exception $e) {  
        // Rollback transaksi jika terjadi kesalahan  
        $conn->rollBack();  
        
        // Redirect dengan pesan error  
        header("Location: ../index.php?msg=Checkout gagal: " . $e->getMessage());  
        exit();  
    }  
}