<?php
session_start();
include 'conexion_db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['logout'])) {
    session_destroy();
    header("Location: loginPage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Requête SQL pour vérifier les identifiants de connexion
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if ($row['disabled'] == 1) {
            $_SESSION['username'] = $username;
            $_SESSION['codigo_ruta'] = $row['codigo_ruta'];
            $_SESSION['role'] = $row['role'];
            header("Location: main.php");
            exit();
        } else {
            echo "este usuario está deshabilitado.";
        }
    } else {
        echo "Nombre de usuario y/o contraseña incorrecta(s).";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="Login.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://getbootstrap.com/docs/5.3/assets/css/docs.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <style>
            body {
  background-color: #f5f5f5;
  font-family: Verdana, Geneva, Tahoma, sans-serif;
}

.form-group {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.login-container {
  max-width: 500px;
  margin: 0 auto;
  padding: 30px;
  margin-top: 12.5em;
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0px 0px 20px 0px rgba(0, 0, 0, 0.1);
}

.login-title {
  font-size: 2.5rem;
  margin-bottom: 30px;
  text-align: center;
}

.login-form input[type="text"],
.login-form input[type="password"] {
  margin-bottom: 15px;
}

.login-btn {
  background-color: #96c565;
  color: #fff;
  border: none;
  border-radius: 5px;
  padding: 10px 20px;
  font-size: 1.5rem;
  cursor: pointer;
}

.login-btn:hover {
  background-color: #86af4b;
}

.error-message {
  color: red;
  font-size: 1rem;
  margin-top: 10px;
  font-weight: 600;
}
.password-toggle {
  margin-left: -30px;
  cursor: pointer;
}

form {
  text-align: center;
}

        </style>
    </head>

    <body>
        <div class="container mt-5 pt-5">
            <div class="row justify-content-center mt-5">
                <div class="col-lg-4 align-self-center">
                    <div class="login-container">
                        <h2 class="login-title">LOGIN</h2>
                        <form method="POST" class="login-form">
                            <div class="mb-4">
                                <label for="username" class="form-label">Usuario:</label>
                                <input type="text" id="username" minlength="4" maxlength="32" name="username"
                                    placeholder="Introduzca nombre de usuario" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Contraseña:</label>
                                <input type="password" id="password" minlength="6" maxlength="16" name="password"
                                    placeholder="Introduzca contraseña" class="form-control" required>
                            </div>
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="showPassword">
                                <label class="form-check-label" for="showPassword">Mostrar contraseña</label>
                            </div>
                            <div>
                                <input type="submit" class="login-btn btn btn-primary" value="Login">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function togglePassword() {
                var passwordInput = document.getElementById("password");
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                } else {
                    passwordInput.type = "password";
                }
            }
        </script>
    </body>

</html>