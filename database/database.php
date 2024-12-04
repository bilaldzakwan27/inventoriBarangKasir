<?php  
// Pastikan hanya mendefinisikan fungsi sekali  
if (!function_exists('getConnection')) {  
    function getConnection() {  
        $host = 'localhost';   
        $db = 'inventory_app';  
        $user = 'root';   
        $pass = '';  

        try {  
            $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);  
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
            return $conn;  
        } catch(PDOException $e) {  
            // Tangani error koneksi  
            error_log("Database Connection Error: " . $e->getMessage());  
            throw new Exception("Koneksi database gagal: " . $e->getMessage());  
        }  
    }  
}  
?>