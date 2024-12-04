<?php
require_once('database/database.php');
require_once('functions/function_kasir.php');
$products = getProducts();
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Kasir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            width: 350px;
            background: #fff;
            padding: 20px;
            overflow-y: auto;
            border-left: 1px solid #dee2e6;
            color: black;
        }

        .main-content {
            margin-right: 350px;
            padding: 20px;
        }

        .cart-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            background-color: rgba(255, 255, 255, 0.1);
            margin-bottom: 5px;
            border-radius: 5px;
        }

        .quantity-input {
            width: 60px;
        }

        .product-card {
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .cart-total {
            position: sticky;
            bottom: 0;
            background: #fff;
            padding: 15px;
            border-top: 2px solid #dee2e6;
        }

        .product-image {
            height: 200px;
            object-fit: cover;
        }

        .sidebar {
            color: black;
        }

        .sidebar a,
        .sidebar button {
            color: white;
        }

        .btn {
            background-color: #7000fe;
            color: white;
            border: none;
        }

        .btn:hover {
            background-color: #6000e0;
            /* Adjusted hover color */
            color: #f8f9fa;
            /* Adjusted text color */
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="mb-3 text-black">Data Customer</h4>
        <form id="transaction-form" action="process/process_kasir.php" method="POST" class="mb-4">
            <input type="hidden" id="cart-data" name="cart_data">
            <div class="mb-3">
                <label for="id_customer" class="form-label">Nama Customer</label>
                <select class="form-select" id="id_customer" name="id_customer" required>
                    <option value="">-- Pilih Customer --</option>
                    <?php
                    $customers = getCustomers();
                    foreach ($customers as $row) {
                        echo "<option value='{$row['idcustomer']}'>{$row['namacustomer']}</option>";
                    }
                    ?>
                </select>
            </div>

            <h4 class="mt-4 text-black">Keranjang Belanja</h4>
            <div id="cart-items">
                <!-- Cart items will be dynamically added here -->
            </div>

            <div class="cart-total">
                <h5 class="text-black">Total: <span id="cart-total">Rp. 0</span></h5>
                <button type="submit" class="btn w-100" name="process_transaction">Proses Transaksi</button>
            </div>
        </form>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($msg) { ?>
            <div class="alert alert-info"><?php echo $msg; ?></div>
        <?php } ?>

        <h4 class="mb-4">Toko ATK Bilal</h4>
        <div class="row g-4">
            <?php
            if ($products && count($products) > 0) {
                foreach ($products as $row) {
                    // Convert blob to base64 image  
                    $imageData = $row['gambar'] ? 'data:image/jpeg;base64,' . base64_encode($row['gambar']) : 'path/to/default/image.jpg';
            ?>
                    <div class="col-md-3">
                        <div class="card product-card h-100">
                            <img src="<?php echo $imageData; ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($row['namabarang']); ?>">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['namabarang']); ?></h5>
                                <p class="card-text">Harga: Rp. <?php echo number_format($row['hargajual'], 0, ',', '.'); ?></p>
                                <p class="card-text">Stok: <?php echo $row['stok']; ?></p>
                                <button type="button"
                                    class="btn mt-auto add-to-cart"
                                    data-id="<?php echo $row['idbarang']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['namabarang']); ?>"
                                    data-price="<?php echo $row['hargajual']; ?>"
                                    data-stock="<?php echo $row['stok']; ?>">
                                    tambah pesanan
                                </button>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<p>Produk tidak tersedia.</p>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cart = {};
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            const transactionForm = document.getElementById('transaction-form');
            const cartDataInput = document.getElementById('cart-data');

            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const price = parseFloat(this.dataset.price);
                    const stock = parseInt(this.dataset.stock);

                    if (!cart[id]) {
                        cart[id] = {
                            name: name,
                            price: price,
                            quantity: 1,
                            stock: stock
                        };
                    } else {
                        if (cart[id].quantity < stock) {
                            cart[id].quantity++;
                        } else {
                            alert(`Stok ${name} hanya tersedia ${stock}`);
                            return;
                        }
                    }

                    updateCartDisplay();
                });
            });

            function updateCartDisplay() {
                cartItems.innerHTML = '';
                let total = 0;

                for (const [id, item] of Object.entries(cart)) {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;

                    const cartItemHtml = `  
                        <div class="cart-item">  
                            <div class="d-flex justify-content-between align-items-center">  
                                <div>  
                                    <h6 class="mb-0">${item.name}</h6>  
                                    <small>Rp. ${item.price.toLocaleString()}</small>  
                                </div>  
                                <div class="d-flex align-items-center">  
                                    <button type="button" class="btn btn-sm btn-outline-light me-2"   
                                            onclick="updateQuantity('${id}', -1)">-</button>  
                                    <input type="number" class="form-control form-control-sm quantity-input me-2"   
                                           value="${item.quantity}" min="1" max="${item.stock}"  
                                           onchange="updateQuantity('${id}', this.value)">  
                                    <button type="button" class="btn btn-sm btn-outline-light"   
                                            onclick="updateQuantity('${id}', 1)">+</button>  
                                    <button type="button" class="btn btn-sm btn-danger ms-2"   
                                            onclick="removeFromCart('${id}')">  
                                        <i class="bi bi-trash"></i>  
                                    </button>  
                                </div>  
                            </div>  
                            <div class="text-end mt-2">  
                                <small>Subtotal: Rp. ${itemTotal.toLocaleString()}</small>  
                            </div>  
                        </div>  
                    `;
                    cartItems.insertAdjacentHTML('beforeend', cartItemHtml);
                }

                cartTotal.textContent = `Rp. ${total.toLocaleString()}`;

                // Update hidden input with cart data for form submission  
                cartDataInput.value = JSON.stringify(cart);
            }

            window.updateQuantity = function(id, change) {
                const item = cart[id];

                if (typeof change === 'string') {
                    const newQuantity = parseInt(change);
                    if (newQuantity > 0 && newQuantity <= item.stock) {
                        item.quantity = newQuantity;
                    } else {
                        alert(`Kuantitas harus antara 1 dan ${item.stock}`);
                        return;
                    }
                } else {
                    const newQuantity = item.quantity + change;
                    if (newQuantity > 0 && newQuantity <= item.stock) {
                        item.quantity = newQuantity;
                    } else {
                        alert(`Kuantitas harus antara 1 dan ${item.stock}`);
                        return;
                    }
                }

                updateCartDisplay();
            };

            window.removeFromCart = function(id) {
                delete cart[id];
                updateCartDisplay();
            };

            // Form submission handling  
            transactionForm.addEventListener('submit', function(e) {
                // Validate cart and customer data  
                if (Object.keys(cart).length === 0) {
                    e.preventDefault();
                    alert('Keranjang masih kosong!');
                }
            });
        });
    </script>
</body>

</html>