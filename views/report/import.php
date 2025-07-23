<?php
// import.php
// Importar reportes en XML o JSON y mostrarlos en tabla

// 1) Configuración general
require_once __DIR__ . '/../../config/config.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Verificar que el usuario tenga permisos para acceder a reportes (Solo Admin y Consultor)
if (!canAccessReports()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a los reportes';
    redirect('../../index.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Importar Reporte</title>
  <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/css/bootstrap-dark.css">
  <link rel="stylesheet" href="../../assets/css/bootstrap-icons.css">
  <link rel="stylesheet" href="../../assets/css/styles.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="../../assets/css/custom.css" rel="stylesheet">
  <link href="../../assets/css/catg.css" rel="stylesheet">
  <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/../../includes/nav.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Importar Reporte</h1>
    </div>
    <div class="container-fluid">
      <div class="card mb-4">
        <div class="card-header file-import-header">
          <i class="bi"></i> Seleccionar Archivo
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="file" class="form-label">Archivo (.json, .xml)</label>
              <input type="file" id="file" name="file" accept=".json,.xml" class="form-control" required>
            </div>
            <a href="index.php" class="btn btn-primary">Volver</a>
            <button type="submit" class="btn btn-primary">Cargar</button>
          </form>
        </div>
      </div>

      <?php
      // 2) Procesar subida
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file']['tmp_name'])) {
          $tmp  = $_FILES['file']['tmp_name'];
          $name = $_FILES['file']['name'];
          $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
          $data = [];
          try {
              if ($ext === 'json') {
                  $json = file_get_contents($tmp);
                  $data = json_decode($json, true);
                  if (!is_array($data)) {
                      throw new Exception('JSON inválido');
                  }
              } elseif ($ext === 'xml') {
                  $xmlObj = simplexml_load_file($tmp, 'SimpleXMLElement', LIBXML_NOCDATA);
                  if (!$xmlObj) {
                      throw new Exception('XML inválido');
                  }
                  $json = json_encode($xmlObj);
                  $arr  = json_decode($json, true);
                  if (isset($arr['venta'])) {
                      // Si hay varios <venta> será arreglo con índices numéricos
                      if (array_key_exists(0, $arr['venta'])) {
                          $data = $arr['venta'];
                      } else {
                          // Único <venta>
                          $data = [ $arr['venta'] ];
                      }
                  } else {
                      $data = [ $arr ];
                  }
              } else {
                  throw new Exception('Formato no soportado: use .json o .xml');
              }

              // 3) Mostrar datos en tabla si hay registros
              if (!empty($data) && is_array($data[0])) {
                  $cols = array_keys($data[0]);
                  echo "<div class=\"card\"><div class=\"card-body p-0\"><table class=\"table table-striped mb-0\"><thead class=\"table-dark\"><tr>";
                  foreach ($cols as $c) {
                      echo '<th>' . htmlspecialchars($c) . '</th>';
                  }
                  echo '</tr></thead><tbody>';

                  foreach ($data as $row) {
                      echo '<tr>';
                      foreach ($cols as $c) {
                          $val = $row[$c] ?? '';
                          echo '<td>' . htmlspecialchars((string)$val) . '</td>';
                      }
                      echo '</tr>';
                  }
                  echo '</tbody></table></div></div>';
              } else {
                  echo '<div class="alert alert-warning">El archivo no contiene registros válidos.</div>';
              }

          } catch (Exception $e) {
              echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
          }
      }
      ?>

    </div>
  </main>

  <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
