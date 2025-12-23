<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Menjalankan Program PHP + SQL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        code, pre {
            background-color: #ecf0f1;
            padding: 5px;
            border-radius: 5px;
            display: block;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        section {
            background-color: #fff;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        ul {
            margin-left: 20px;
        }
    </style>
</head>
<body>

    <h1>Panduan Menjalankan Program PHP + SQL</h1>

    <section>
        <h2>1. Persiapan Lingkungan</h2>
        <p>Sebelum menjalankan program, pastikan lingkungan sudah siap:</p>
        <h3>A. Install Web Server</h3>
        <ul>
            <li>Gunakan XAMPP, Laragon, atau WAMP.</li>
            <li>Pastikan <strong>Apache</strong> dan <strong>MySQL</strong> berjalan.</li>
        </ul>
        <h3>B. Siapkan Folder Project</h3>
        <ul>
            <li>Letakkan folder project di <code>htdocs</code> (XAMPP) atau <code>www</code> (Laragon).</li>
            <li>Contoh: <code>C:\xampp\htdocs\project-php</code></li>
        </ul>
    </section>

    <section>
        <h2>2. Setup Database</h2>
        <h3>A. Buat Database</h3>
        <ul>
            <li>Buka <strong>phpMyAdmin</strong> (<code>http://localhost/phpmyadmin</code>).</li>
            <li>Klik <strong>New</strong>, beri nama database misal <code>db_project</code>, lalu <strong>Create</strong>.</li>
        </ul>
        <h3>B. Buat Tabel</h3>
        <pre>
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);
        </pre>
        <h3>C. Import Data (Opsional)</h3>
        <ul>
            <li>Jika ada file SQL siap pakai, klik <strong>Import</strong> → pilih file `.sql` → klik <strong>Go</strong>.</li>
        </ul>
    </section>

    <section>
        <h2>3. Konfigurasi Koneksi Database</h2>
        <pre>
&lt;?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_project";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?&gt;
        </pre>
    </section>

    <section>
        <h2>4. Menjalankan Backend</h2>
        <ul>
            <li>Pastikan <strong>Apache & MySQL</strong> berjalan.</li>
            <li>Akses project di browser: <code>http://localhost/project-php/</code></li>
        </ul>
    </section>

    <section>
        <h2>5. Menjalankan Frontend</h2>
        <ul>
            <li>Frontend HTML/CSS/JS bisa diakses melalui browser: <code>http://localhost/project-php/index.php</code></li>
            <li>Form akan berinteraksi dengan backend via POST atau AJAX.</li>
        </ul>
        <pre>
&lt;form action="login.php" method="POST"&gt;
    Username: &lt;input type="text" name="username"&gt;&lt;br&gt;
    Password: &lt;input type="password" name="password"&gt;&lt;br&gt;
    &lt;button type="submit"&gt;Login&lt;/button&gt;
&lt;/form&gt;
        </pre>
    </section>

    <section>
        <h2>6. Contoh Query PHP + SQL</h2>
        <pre>
&lt;?php
include 'config.php';

if(isset($_POST['username']) && isset($_POST['password'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) &gt; 0){
        echo "Login berhasil!";
    } else {
        echo "Username atau password salah!";
    }
}
?&gt;
        </pre>
    </section>

    <section>
        <h2>7. Tips Debugging</h2>
        <ul>
            <li>Pastikan Apache & MySQL hidup.</li>
            <li>Cek URL sesuai folder project.</li>
            <li>Aktifkan error reporting di PHP:
                <pre>
ini_set('display_errors', 1);
error_reporting(E_ALL);
                </pre>
            </li>
            <li>Gunakan phpMyAdmin untuk memeriksa data di database.</li>
        </ul>
    </section>

</body>
</html>
