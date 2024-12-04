let cart = [];

function addToCart(id_barang, nama_barang, harga) {
    let item = { id_barang, nama_barang, harga, jumlah: 1 };
    cart.push(item);
    displayCart();
}

function displayCart() {
    let cartContainer = document.getElementById('cart');
    cartContainer.innerHTML = "";
    cart.forEach(item => {
        cartContainer.innerHTML += `
            <p>${item.nama_barang} - Rp ${item.harga} x ${item.jumlah}</p>
        `;
    });
}

function submitSale() {
    let total = cart.reduce((sum, item) => sum + item.harga * item.jumlah, 0);
    let customer_id = 1; // Simulasi customer_id, ini harus diambil dari form atau session

    // Kirim data ke server menggunakan AJAX
    let formData = new FormData();
    formData.append('submit_sale', true);
    formData.append('id_customer', customer_id);
    formData.append('total_bayar', total);
    formData.append('items', JSON.stringify(cart));

    fetch('process/process_kasir.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => alert(data));
}
