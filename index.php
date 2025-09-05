<?php
// Selalu mulai session di paling atas
session_start(); 
// Hubungkan ke DB untuk mengambil daftar role
include("config/koneksi_mysql.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Sistem Resto</title>

  <!-- CSS digabung dan disederhanakan -->
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
    *{ margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body{ min-height: 100vh; width: 100%; background: #009579; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .container{ max-width: 430px; width: 100%; background: #fff; border-radius: 7px; box-shadow: 0 5px 10px rgba(0,0,0,0.3); }
    .container .form{ padding: 2rem; }
    .form header{ font-size: 2rem; font-weight: 500; text-align: center; margin-bottom: 1.5rem; }
    .form input { height: 50px; width: 100%; padding: 0 15px; font-size: 16px; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 6px; outline: none; }
    .form input:focus { box-shadow: 0 1px 0 rgba(0,0,0,0.2); }
    .form a{ font-size: 16px; color: #009579; text-decoration: none; display: block; margin-bottom: 1rem;}
    .form a:hover{ text-decoration: underline; }
    .form input.button{ color: #fff; background: #009579; font-size: 1.2rem; font-weight: 500; letter-spacing: 1px; margin-top: 1rem; cursor: pointer; transition: 0.4s; }
    .form input.button:hover{ background: #006653; }
    .message { padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 1rem; color: white; }
    .error-message { background-color: #721c24; }
  </style>
</head>
<body>
  <div class="container">
    <!-- Login form -->
    <div class="login form">
      <header>Login</header>
      <form action="login.php" method="POST">
        <?php
          // Bagian ini untuk menampilkan pesan error jika login gagal
          if (isset($_SESSION['error_message'])) {
              echo '<div class="message error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
              unset($_SESSION['error_message']);
          }
        ?>
        <!-- ================== PERUBAHAN UTAMA ================== -->
        <!-- 1. Tipe input diubah menjadi "text" -->
        <!-- 2. Nama input diubah menjadi "username" -->
        <!-- 3. Placeholder diubah -->
        <input type="text" name="username" placeholder="Enter your username" required>
        <!-- ======================================================= -->
        
        <input type="password" name="password" placeholder="Enter your password" required>
        <a href="#">Forgot password?</a>
        <input type="submit" class="button" value="Login">
      </form>
    </div>
    <!-- FORM REGISTRASI DAN PHP UNTUK ROLES DIHAPUS DARI SINI -->
  </div>
</body>
</html>

