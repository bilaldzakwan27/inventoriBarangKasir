<?php  
require_once('database/database.php');  
require_once('functions/function_kasir.php');  

// Ambil kode penjualan dari parameter  
$kode_penjualan = $_GET['kode'] ?? '';  

// Ambil detail invoice  
$invoice_details = getInvoiceDetails($kode_penjualan);  

if (empty($invoice_details)) {  
    die("Invoice tidak ditemukan");  
}  

// Ambil informasi utama dari detail pertama  
$invoice_header = $invoice_details[0];  
?>  

<!DOCTYPE html>  
<html lang="id">  
<head>  
    <meta charset="UTF-8">  
    <title>Invoice - <?php echo htmlspecialchars($kode_penjualan); ?></title>  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">  
    <style>  
        @media print {  
            .no-print {  
                display: none !important;  
            }  
            body {  
                font-size: 12px;  
            }  
            .card {  
                border: none !important;  
                box-shadow: none !important;  
            }  
        }  
        .invoice-header {  
            background-color: #f8f9fa;  
            padding: 20px;  
            border-bottom: 1px solid #e9ecef;  
        }  
    </style>  
</head>  
<body>  
    <div class="container mt-5">  
        <div class="card shadow-sm">  
            <div class="invoice-header text-center">  
                <h2 class="mb-1">INVOICE PENJUALAN</h2>  
                <p class="text-muted">Nomor: <?php echo htmlspecialchars($kode_penjualan); ?></p>  
            </div>  
            <div class="card-body">  
                <div class="row">  
                    <div class="col-md-6">  
                        <h5 class="text-primary">Informasi Customer</h5>  
                        <p class="mb-1">  
                            <strong>Nama:</strong> <?php echo htmlspecialchars($invoice_header['namacustomer']); ?><br>  
                            <strong>Alamat:</strong> <?php echo htmlspecialchars($invoice_header['alamat']); ?><br>  
                            <strong>Telepon:</strong> <?php echo htmlspecialchars($invoice_header['telepon']); ?>  
                        </p>  
                    </div>  
                    <div class="col-md-6 text-end">  
                        <h5 class="text-primary">Detail Transaksi</h5>  
                        <p class="mb-1">  
                            <strong>Tanggal:</strong> <?php echo date('d F Y H:i', strtotime($invoice_header['tanggal'])); ?><br>  
                            <strong>Total Bayar:</strong> Rp. <?php echo number_format($invoice_header['total_bayar'], 0, ',', '.'); ?>  
                        </p>  
                    </div>  
                </div>  

                <table class="table table-striped table-hover mt-4">  
                    <thead class="table-light">  
                        <tr>  
                            <th>No</th>  
                            <th>Nama Barang</th>  
                            <th>Qty</th>  
                            <th>Harga Satuan</th>  
                            <th class="text-end">Subtotal</th>  
                        </tr>  
                    </thead>  
                    <tbody>  
                        <?php   
                        $total = 0;  
                        foreach ($invoice_details as $index => $item):   
                            $subtotal = $item['qty'] * $item['hargajual'];  
                            $total += $subtotal;  
                        ?>  
                            <tr>  
                                <td><?php echo $index + 1; ?></td>  
                                <td><?php echo htmlspecialchars($item['namabarang']); ?></td>  
                                <td><?php echo htmlspecialchars($item['qty']); ?></td>  
                                <td>Rp. <?php echo number_format($item['hargajual'], 0, ',', '.'); ?></td>  
                                <td class="text-end">Rp. <?php echo number_format($subtotal, 0, ',', '.'); ?></td>  
                            </tr>  
                        <?php endforeach; ?>  
                    </tbody>  
                    <tfoot>  
                        <tr class="table-light">  
                            <td colspan="4" class="text-end"><strong>Total Keseluruhan</strong></td>  
                            <td class="text-end"><strong>Rp. <?php echo number_format($total, 0, ',', '.'); ?></strong></td>  
                        </tr>  
                    </tfoot>  
                </table>  

                <div class="row mt-4">  
                    <div class="col-md-6">  
                        <h5 class="text-primary">Catatan</h5>  
                        <p class="text-muted small">  
                            Terima kasih atas pembelian Anda.   
                            Barang yang sudah dibeli tidak dapat dikembalikan.  
                        </p>  
                    </div>  
                    <div class="col-md-6 text-end">  
                        <h5 class="text-primary">Tanda Terima</h5>  
                        <div style="border-bottom: 1px solid #000; width: 200px; margin-left: auto;"></div>  
                        <small>Nama & Tanda Tangan</small>  
                    </div>  
                </div>  
            </div>  
            <div class="card-footer text-center no-print">  
                <button onclick="window.print()" class="btn btn-primary">  
                    <i class="bi bi-printer"></i> Cetak Invoice  
                </button>  
                <a href="index.php" class="btn btn-secondary">  
                    <i class="bi bi-arrow-left"></i> Kembali ke Kasir  
                </a>  
            </div>  
        </div>  
    </div>  

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  
</body>  
</html>