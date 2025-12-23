# 🚀 Panduan Menjalankan Program PHP + SQL

Selamat datang! 🎉  
Panduan ini akan membantu kamu menjalankan project **PHP murni** dengan **database SQL** secara mudah.

---

## 1️⃣ Persiapan Lingkungan

### 🔹 Install Web Server
- 🖥️ Gunakan **XAMPP**, **Laragon**, atau **WAMP**
- ✅ Pastikan **Apache** dan **MySQL** hidup

### 🔹 Siapkan Folder Project
- 📂 Letakkan project di:
  - `htdocs` (XAMPP)
  - `www` (Laragon)
- Contoh: `C:\xampp\htdocs\project-php`

---

## 2️⃣ Setup Database

### 🔹 Buat Database
1. 🌐 Buka [phpMyAdmin](http://localhost/phpmyadmin)
2. ➕ Klik **New** → beri nama database `db_project` → **Create**

### 🔹 Buat Tabel
```sql
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);
🔹 Import Data (Opsional)
📤 Jika ada file .sql, klik Import → pilih file → Go

3️⃣ Konfigurasi Koneksi Database
Buat file config.php:

php
Copy code
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_project";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
💡 Pastikan nama database, user, dan password sesuai setup lokal

4️⃣ Menjalankan Backend
🚀 Pastikan Apache & MySQL berjalan

🌐 Akses di browser: http://localhost/project-php/

5️⃣ Menjalankan Frontend
🌟 Frontend HTML/CSS/JS: http://localhost/project-php/index.php

Contoh form login:

html
Copy code
<form action="login.php" method="POST">
    Username: <input type="text" name="username"><br>
    Password: <input type="password" name="password"><br>
    <button type="submit">Login</button>
</form>
6️⃣ Contoh Query PHP + SQL
php
Copy code
<?php
include 'config.php';

if(isset($_POST['username']) && isset($_POST['password'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0){
        echo "Login berhasil! 🎉";
    } else {
        echo "Username atau password salah ❌";
    }
}
?>
7️⃣ Tips Debugging
🔧 Pastikan Apache & MySQL hidup

🌐 Cek URL sesuai folder project

⚡ Aktifkan error reporting di PHP:

php
Copy code
ini_set('display_errors', 1);
error_reporting(E_ALL);
🧾 Gunakan phpMyAdmin untuk memeriksa data

