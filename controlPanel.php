<?php
include 'conexion_db.php'; // Assurez-vous d'inclure votre fichier de connexion à la base de données

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification des champs du formulaire
    if (isset($_POST['editUserId']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['codigoRuta'])) {
        // Récupération des données du formulaire
        $userId = $_POST['editUserId'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $codigoRuta = $_POST['codigoRuta'];
        
        // Vérification de l'état de la case à cocher "Disabled"
        $disabled = isset($_POST['disabled']) ? 1 : 0;

        // // Afficher les données pour débogage
        // echo "UserID: " . $userId . "<br>";
        // echo "Username: " . $username . "<br>";
        // echo "Password: " . $password . "<br>";
        // echo "Codigo Ruta: " . $codigoRuta . "<br>";
        // echo "Disabled: " . $disabled . "<br>";

        // Préparation de la requête SQL d'update
        $sql = "UPDATE users SET username=?, password=?, codigo_ruta=?, disabled=? WHERE id=?";

        // Préparation de la requête
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Liaison des paramètres
            $stmt->bind_param("sssii", $username, $password, $codigoRuta, $disabled, $userId);

            // // Exécution de la requête
            // if ($stmt->execute()) {
            //     echo "Les informations de l'utilisateur ont été mises à jour avec succès.";
            // } else {
            //     echo "Erreur lors de la mise à jour des informations de l'utilisateur : " . $stmt->error;
            // }

            // Fermeture du statement
            $stmt->close();
        } else {
            echo "Erreur de préparation de la requête : " . $conn->error;
        }
    } else {
        echo "Tous les champs du formulaire doivent être définis.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

</head>
<body>
<a href="Main.php" type="button" class="btn btn-success mt-4 ml-4"
        style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;">
  Return
</a>   
<div class="container">
    <h2 class="mb-4">Detalles de usuarios</h2>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Password</th>
            <th>Codigo Ruta</th>
            <th>Role</th>
            <th>Disabled</th>
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
                if($row["role"]==1){
                    echo "<td>Boss</td>";
                }else{
                    echo "<td>User</td>";
                }
                echo "<td>";

                echo "<input type='checkbox' disabled ";
                if ($row['disabled'] == 1) {
                    echo "checked";
                }
                echo ">";
                echo "</td>";
                echo "<td>";
                echo "<button type='button' class='btn btn-sm btn-success editUsersBtn'
                            data-toggle='modal' data-target='#editUserModal'
                            data-user-id='" . $row["id"] . "'
                            data-user-username='" . $row["username"] . "'
                            data-user-password='" . $row["password"] . "'
                            data-user-codigo_ruta='" . $row["codigo_ruta"] . "'
                            data-user-role='" . $row["role"] . "'
                            data-user-disabled='" . $row['disabled'] . "'
                            >Editar</button>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No se encontraron usuarios</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1"  aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="post" autocomplete="off" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" id="editUserId" name="editUserId" value="">

                    <div class="form-group">
                        <label for="username">Nombre de usuario</label>
                        <input type="text" class="form-control" id="username" name="username" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="codigoRuta">Código de Ruta</label>
                        <input type="text" class="form-control" id="codigoRuta" name="codigoRuta" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="disabled">Deshabilitado</label>
                        <input type="checkbox" class="form-control" id="disabled" name="disabled" value="1">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
<!-- Script for loading user data into the form when editing -->
<script>
    $(document).ready(function () {
        $('.editUsersBtn').click(function () {
            var userId = $(this).data('user-id');
            var username = $(this).data('user-username');
            var password = $(this).data('user-password');
            var codigoRuta = $(this).data('user-codigo_ruta');
            var role = $(this).data('user-role');
            var disabled = $(this).data('user-disabled');

            $('#editUserId').val(userId);
            $('#username').val(username);
            $('#password').val(password);
            $('#codigoRuta').val(codigoRuta);
            $('#role').val(role);
            if (disabled == 1) {
                $('#disabled').prop('checked', true);
            } else {
                $('#disabled').prop('checked', false);
            }
        });
    });
</script>

<!-- Script for clearing the fields when adding a new user -->
<script>
    $(document).ready(function () {
        $('.btnAdd').click(function () {
            $('#editUserId').val('');
            $('#username').val('');
            $('#password').val('');
            $('#codigoRuta').val('');
            $('#role').val('');
            $('#disabled').prop('checked', false);
        });
    });
</script>
</body>
</html>
