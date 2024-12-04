<?php
// Pastikan file database sudah di-include  
require_once(dirname(__DIR__) . '/database/database.php');  

// Fungsi untuk mengambil data produk  
function getProducts()
{
    try {
        $conn = getConnection();
        $query = "SELECT   
                    idbarang,   
                    namabarang,   
                    kategori,   
                    hargabeli,   
                    hargajual,   
                    stok,   
                    gambar   
                  FROM barang   
                  WHERE stok > 0   
                  ORDER BY namabarang";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error  
        error_log("Error getting products: " . $e->getMessage());
        return []; // Kembalikan array kosong jika ada error  
    }
}

// Fungsi untuk mengambil data customer  
function getCustomers()
{
    try {
        $conn = getConnection();
        $query = "SELECT   
                    idcustomer,   
                    namacustomer   
                  FROM customer   
                  ORDER BY namacustomer";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error  
        error_log("Error getting customers: " . $e->getMessage());
        return []; // Kembalikan array kosong jika ada error  
    }
}

// Fungsi untuk menambah data customer  
function addCustomer($nama, $alamat, $telepon)
{
    try {
        // Validasi input  
        if (empty($nama) || empty($alamat) || empty($telepon)) {
            throw new Exception("Semua field harus diisi");
        }

        $conn = getConnection();
        $query = "INSERT INTO customer (  
                    namacustomer,   
                    alamat,   
                    telepon  
                  ) VALUES (  
                    :nama,   
                    :alamat,   
                    :telepon  
                  )";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':nama' => $nama,
            ':alamat' => $alamat,
            ':telepon' => $telepon
        ]);

        return $result;
    } catch (Exception $e) {
        // Log error  
        error_log("Error adding customer: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk memproses transaksi lengkap  
function processTransaction($id_customer, $cart_items)
{
    $conn = getConnection();

    try {
        // Validasi input  
        if (empty($id_customer) || empty($cart_items)) {
            throw new Exception("Data customer atau barang kosong");
        }

        // Mulai transaksi  
        $conn->beginTransaction();

        // Generate kode unik untuk penjualan  
        $kode_penjualan = 'INV-' . date('Ymd') . '-' . uniqid();

        // Hitung total bayar  
        $total_bayar = 0;
        foreach ($cart_items as $item) {
            // Validasi stok  
            $stok_query = "SELECT stok FROM barang WHERE idbarang = :id_barang";
            $stok_stmt = $conn->prepare($stok_query);
            $stok_stmt->execute([':id_barang' => $item['id']]);
            $current_stok = $stok_stmt->fetchColumn();

            if ($current_stok < $item['quantity']) {
                throw new Exception("Stok tidak mencukupi untuk barang {$item['name']}");
            }

            $total_bayar += $item['price'] * $item['quantity'];
        }

        // Insert ke tabel penjualan  
        $query_penjualan = "INSERT INTO penjualan (  
            idcustomer,   
            tanggal,   
            total_bayar,   
            code  
        ) VALUES (  
            :id_customer,   
            NOW(),   
            :total_bayar,   
            :kode_penjualan  
        )";
        $stmt_penjualan = $conn->prepare($query_penjualan);
        $stmt_penjualan->execute([
            ':id_customer' => $id_customer,
            ':total_bayar' => $total_bayar,
            ':kode_penjualan' => $kode_penjualan
        ]);

        // Ambil ID penjualan yang baru saja dibuat  
        $id_penjualan = $conn->lastInsertId();

        // Prepare statement untuk detail penjualan dan update stok  
        $query_detail = "INSERT INTO detail_penjualan (  
            idpenjualan,   
            idbarang,   
            qty,   
            hargajual  
        ) VALUES (  
            :id_penjualan,   
            :id_barang,   
            :qty,   
            :harga_jual  
        )";
        $stmt_detail = $conn->prepare($query_detail);

        $query_update_stok = "UPDATE barang   
                               SET stok = stok - :qty   
                               WHERE idbarang = :id_barang";
        $stmt_update_stok = $conn->prepare($query_update_stok);

        // Proses setiap item dalam keranjang  
        foreach ($cart_items as $item) {
            // Insert detail penjualan  
            $stmt_detail->execute([
                ':id_penjualan' => $id_penjualan,
                ':id_barang' => $item['id'],
                ':qty' => $item['quantity'],
                ':harga_jual' => $item['price']
            ]);

            // Update stok barang  
            $stmt_update_stok->execute([
                ':qty' => $item['quantity'],
                ':id_barang' => $item['id']
            ]);
        }

        // Commit transaksi  
        $conn->commit();

        // Kembalikan kode penjualan untuk invoice  
        return $kode_penjualan;
    } catch (Exception $e) {
        // Rollback transaksi jika ada kesalahan  
        $conn->rollBack();
        error_log('Transaksi gagal: ' . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mendapatkan detail penjualan berdasarkan kode  
function getInvoiceDetails($kode_penjualan)
{
    try {
        $conn = getConnection();

        $query = "SELECT   
                    p.*,   
                    c.namacustomer,   
                    c.alamat,   
                    c.telepon,   
                    dp.idbarang,   
                    b.namabarang,   
                    dp.qty,   
                    dp.hargajual  
                  FROM penjualan p  
                  JOIN customer c ON p.idcustomer = c.idcustomer  
                  JOIN detail_penjualan dp ON p.idpenjualan = dp.idpenjualan  
                  JOIN barang b ON dp.idbarang = b.idbarang  
                  WHERE p.code = :kode_penjualan";

        $stmt = $conn->prepare($query);
        $stmt->execute([':kode_penjualan' => $kode_penjualan]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting invoice details: " . $e->getMessage());
        return [];
    }
}

// Fungsi untuk mendapatkan detail produk berdasarkan ID  
function getProductById($id_barang)
{
    try {
        $conn = getConnection();

        $query = "SELECT * FROM barang WHERE idbarang = :id_barang";

        $stmt = $conn->prepare($query);
        $stmt->execute([':id_barang' => $id_barang]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting product details: " . $e->getMessage());
        return null;
    }
}
