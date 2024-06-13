<?php
include 'conexion_db.php'; // Make sure to include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['editUserId']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['codigoRuta'])) {
        $userId = $_POST['editUserId'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $codigoRuta = $_POST['codigoRuta'];
        $disabled = isset($_POST['disabled']) ? 1 : 0;

        $sql = "UPDATE users SET username=?, password=?, codigo_ruta=?, disabled=? WHERE id=?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssii", $username, $password, $codigoRuta, $disabled, $userId);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "Error preparing query: " . $conn->error;
        }
    } else {
        echo "All form fields must be defined.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Panel de Control</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://getbootstrap.com/docs/5.3/assets/css/docs.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
        <div class="container my-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="Main.php" class="btn btn-success">
                    <i class="fas fa-arrow-left"></i> Devolver
                </a>
                <h2 class="mb-0">Detalles de los usuarios</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Contraseña</th>
                            <th>Codigo Ruta</th>
                            <th>Rollos</th>
                            <th>Desactivado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM users";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row["id"] . "</td>";
                                echo "<td>" . $row["username"] . "</td>";
                                echo "<td>" . $row["password"] . "</td>";
                                echo "<td>" . $row["codigo_ruta"] . "</td>";
                                echo "<td>" . ($row["role"] == 1 ? 'Boss' : 'User') . "</td>";
                                echo "<td><input type='checkbox' disabled " . ($row['disabled'] == 1 ? 'checked' : '') . "></td>";
                                echo "<td>";
                                echo "<button type='button' class='btn btn-sm btn-primary editUsersBtn'
                            data-bs-toggle='modal' data-bs-target='#editUserModal'
                            data-user-id='" . $row["id"] . "'
                            data-user-username='" . $row["username"] . "'
                            data-user-password='" . $row["password"] . "'
                            data-user-codigo_ruta='" . $row["codigo_ruta"] . "'
                            data-user-role='" . $row["role"] . "'
                            data-user-disabled='" . $row['disabled'] . "'>
                            <i class='fas fa-edit'></i> Editar</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No se encontraron usuarios</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editUserForm" method="post" autocomplete="off"
                            action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <input type="hidden" id="editUserId" name="editUserId" value="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de usuario</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigoRuta" class="form-label">Código de Ruta</label>
                                <input type="text" class="form-control" id="codigoRuta" name="codigoRuta" required>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="disabled" name="disabled" value="1">
                                <label for="disabled" class="form-check-label">Deshabilitado</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script for loading user data into the form when editing -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.editUsersBtn').forEach(button => {
                    button.addEventListener('click', function () {
                        var userId = this.getAttribute('data-user-id');
                        var username = this.getAttribute('data-user-username');
                        var password = this.getAttribute('data-user-password');
                        var codigoRuta = this.getAttribute('data-user-codigo_ruta');
                        var disabled = this.getAttribute('data-user-disabled');

                        document.getElementById('editUserId').value = userId;
                        document.getElementById('username').value = username;
                        document.getElementById('password').value = password;
                        document.getElementById('codigoRuta').value = codigoRuta;
                        document.getElementById('disabled').checked = disabled == '1';
                    });
                });
            });
        </script>

        <!-- Script for clearing the fields when adding a new user -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.btnAdd').forEach(button => {
                    button.addEventListener('click', function () {
                        document.getElementById('editUserId').value = '';
                        document.getElementById('username').value = '';
                        document.getElementById('password').value = '';
                        document.getElementById('codigoRuta').value = '';
                        document.getElementById('disabled').checked = false;
                    });
                });
            });
        </script>
    </body>

</html>