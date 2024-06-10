<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rapport des Ventes</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="style.css">
        <script>
            function openTab(evt, tabName) {
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tabcontent");

                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }

                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.className += " active";
            }
        </script>
    </head>

    <body>
        <nav role="navigation">
            <div id="menuToggle">
                <input type="checkbox" />
                <span></span>
                <span></span>
                <span></span>
                <ul id="menu">
                    <a href="#" onclick="openTab(event, 'Ruta')">
                        <li>Seller</li>
                    </a>
                    <a href="#" onclick="openTab(event, 'Cliente')">
                        <li>Cliente</li>
                    </a>
                    <a href="#" onclick="openTab(event, 'Ruta')">
                        <li>Ruta</li>
                    </a>
                </ul>
            </div>
        </nav>
        <div class="container-fluid">
            <div class="row">
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <h2>Rapport des Ventes</h2>
                    <form method="get" action="" class="row g-3 align-items-end">
                        <div class="col-auto">
                            <label for="mes" class="form-label">Mois :</label>
                            <select class="form-select" id="mes" name="mes">
                                <?php
                                for ($i = 1; $i <= 12; $i++) {
                                    $selected = ($i == $MES) ? 'selected' : '';
                                    echo "<option value=\"$i\" $selected>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="annee" class="form-label">Année :</label>
                            <select class="form-select" id="annee" name="annee">
                                <?php
                                for ($i = date('Y') - 4; $i <= date('Y'); $i++) {
                                    $selected = ($i == $ANNEE) ? 'selected' : '';
                                    echo "<option value=\"$i\" $selected>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>

                    <!-- Tab content for Ruta -->
                    <div id="Ruta" class="tabcontent">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>RUTA</th>
                                    <th>COMERCIAL</th>
                                    <th>FACTURADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results_ruta as $row): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($row['RUTA']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['COMERCIAL']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['FACTURADO']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&download=ruta"
                            class="btn btn-secondary">Download Ruta CSV</a>
                    </div>

                    <!-- Tab content for Cliente -->
                    <div id="Cliente" class="tabcontent">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>RUTA</th>
                                    <th>COMERCIAL</th>

                                    <th>CLIENTE</th>
                                    <th>RAZON SOCIAL</th>
                                    <th>FACTURADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results_cliente as $row): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($row['RUTA']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['COMERCIAL']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['CodigoCliente']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['RazonSocial']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['FACTURADO']); ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&download=cliente"
                            class="btn btn-secondary">Download Cliente CSV</a>
                    </div>
                </main>
                </di v>
            </div>





            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0
                        -alpha1/dist/
                    js/bootstrap.bundle.min.js"></script>
    </body>

</html>