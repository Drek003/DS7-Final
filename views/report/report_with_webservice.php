<?php
/**
 * report_with_webservice.php
 * Sistema de reportes mejorado con integración de Web Services SOAP
 * Esta versión mantiene la funcionalidad original pero añade opciones de Web Service
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../WebService/ReportWebServiceIntegration.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Verificar que el usuario tenga permisos para acceder a reportes
if (!canAccessReports()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a los reportes';
    redirect('../../index.php');
}

// Recoger parámetros
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin    = $_GET['fecha_fin']    ?? '';
$cliente_id   = $_GET['cliente']      ?? '';
$producto_id  = $_GET['producto']     ?? '';
$export       = $_GET['export']       ?? '';
$use_webservice = $_GET['use_webservice'] ?? '0'; // Nueva opción para usar Web Service

// Si se solicita exportación via Web Service
if ($use_webservice === '1' && in_array($export, ['json', 'xml'])) {
    try {
        $integration = new ReportWebServiceIntegration();
        
        if ($export === 'json') {
            $integration->exportJSONFile($fecha_inicio, $fecha_fin, $cliente_id, $producto_id);
        } elseif ($export === 'xml') {
            $integration->exportXMLFile($fecha_inicio, $fecha_fin, $cliente_id, $producto_id);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error en Web Service: ' . $e->getMessage();
    }
}

// Configuración de conexión a la base de datos (funcionalidad original)
$host = '127.0.0.1';
$db   = 'ds7';
$user = 'admin';
$pass = '1234';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

// Cargar lista de clientes
$clientes = $pdo
    ->query("SELECT DISTINCT customer_name FROM sales ORDER BY customer_name")
    ->fetchAll(PDO::FETCH_COLUMN);

// Cargar lista de productos
if ($cliente_id !== '') {
    $stmt = $pdo->prepare("
      SELECT DISTINCT sd.product_id, sd.product_name
      FROM sale_details sd
      JOIN sales s ON s.id = sd.sale_id
      WHERE s.customer_name = :cliente
      ORDER BY sd.product_name
    ");
    $stmt->execute([':cliente' => $cliente_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $productos = $pdo
      ->query("SELECT DISTINCT product_id, product_name
               FROM sale_details
               ORDER BY product_name")
      ->fetchAll(PDO::FETCH_ASSOC);
}

// Validar producto
$productos_ids = array_column($productos, 'product_id');
if ($producto_id !== '' && !in_array((int)$producto_id, $productos_ids, true)) {
    $producto_id = '';
}

// Construir consulta
$where  = [];
$params = [];

if ($fecha_inicio !== '') {
    $where[]          = 's.sale_date >= :fini';
    $params[':fini']  = $fecha_inicio . ' 00:00:00';
}

if ($fecha_fin !== '') {
    $where[]         = 's.sale_date <= :ffin';
    $params[':ffin'] = $fecha_fin . ' 23:59:59';
}

if ($cliente_id !== '') {
    $where[]            = 's.customer_name = :cliente';
    $params[':cliente'] = $cliente_id;
}

if ($producto_id !== '') {
    $where[]            = 'sd.product_id = :pid';
    $params[':pid']     = $producto_id;
}

$sql = "
  SELECT
    sd.id            AS id,
    s.id             AS sale_id,
    s.sale_date      AS sale_date,
    s.customer_name  AS cliente,
    sd.product_name  AS producto,
    sd.quantity      AS quantity,
    sd.product_price AS unit_price,
    sd.subtotal      AS subtotal
  FROM sales s
  JOIN sale_details sd ON s.id = sd.sale_id
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$stmt    = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Exportar (método tradicional, solo si no se usa Web Service)
if ($use_webservice !== '1' && $export === 'json') {
    $filename = 'reporte_ventas_' . date('Ymd_His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($use_webservice !== '1' && $export === 'xml') {
    $filename = 'reporte_ventas_' . date('Ymd_His') . '.xml';
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $xml = new SimpleXMLElement('<ventas/>');
    foreach ($results as $row) {
        $item = $xml->addChild('venta');
        foreach ($row as $key => $value) {
            $item->addChild($key, htmlspecialchars($value));
        }
    }
    echo $xml->asXML();
    exit;
}

// Probar conexión Web Service para mostrar estado
$webservice_status = 'No probado';
$webservice_class = 'text-muted';

if (isset($_GET['test_ws'])) {
    try {
        $integration = new ReportWebServiceIntegration();
        $test_result = $integration->testConnection();
        
        if ($test_result['success']) {
            $webservice_status = 'Conectado ✓';
            $webservice_class = 'text-success';
        } else {
            $webservice_status = 'Error: ' . $test_result['message'];
            $webservice_class = 'text-danger';
        }
    } catch (Exception $e) {
        $webservice_status = 'Error: ' . $e->getMessage();
        $webservice_class = 'text-danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas - Con Web Services</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-dark.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <style>
        .webservice-indicator {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 0.8em;
        }
        .export-method {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .method-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<?php include '../../includes/nav.php'; ?>

<main class="main-content">
  <div class="page-header position-relative">
    <h1>Reporte de Ventas - Con Web Services</h1>
    <div class="webservice-status <?= $webservice_class ?> webservice-indicator">
        Web Service: <?= $webservice_status ?>
        <a href="?test_ws=1&<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-secondary ms-2">
            <i class="bi bi-arrow-clockwise"></i>
        </a>
    </div>
  </div>
  
  <div class="container-fluid">
    <!-- Card de Filtros -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="bi bi-funnel"></i> Filtros de Búsqueda
      </div>
      <div class="card-body">
        <form id="filter-form" method="get" class="row g-3">
            <div class="col-md-3">
                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio"
                    class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>
            <div class="col-md-3">
                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                <input type="date" id="fecha_fin" name="fecha_fin"
                    class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>
            <div class="col-md-3">
                <label for="cliente" class="form-label">Cliente</label>
                <select id="cliente" name="cliente" class="form-select"
                        onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($clientes as $cli): 
                    $sel = ($cliente_id === $cli) ? ' selected' : ''; ?>
                <option value="<?= htmlspecialchars($cli) ?>"<?= $sel ?>>
                    <?= htmlspecialchars($cli) ?>
                </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="producto" class="form-label">Producto</label>
                <select id="producto" name="producto" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($productos as $prod):
                    $sel = ($producto_id == $prod['product_id']) ? ' selected' : ''; ?>
                <option value="<?= $prod['product_id'] ?>"<?= $sel ?>>
                    <?= htmlspecialchars($prod['product_name']) ?>
                </option>
                <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 mt-3">
              <button type="submit" class="btn btn-primary me-2">Filtrar</button>
              <button type="button" class="btn btn-secondary me-2" onclick="window.location.href=window.location.pathname">Limpiar</button>
            </div>
        </form>
      </div>
    </div>

    <!-- Card de Métodos de Exportación -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="bi bi-download"></i> Opciones de Exportación
      </div>
      <div class="card-body">
        <div class="row">
          <!-- Método Tradicional -->
          <div class="col-md-6">
            <div class="export-method">
              <div class="method-title text-primary">
                <i class="bi bi-file-earmark-arrow-down"></i> Método Tradicional
              </div>
              <p class="small text-muted">Generación directa desde PHP sin Web Services</p>
              <div class="d-grid gap-2">
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export'=>'json', 'use_webservice'=>'0']))) ?>"
                   class="btn btn-success btn-sm">
                   <i class="bi bi-filetype-json"></i> Descargar JSON
                </a>
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export'=>'xml', 'use_webservice'=>'0']))) ?>"
                   class="btn btn-warning btn-sm">
                   <i class="bi bi-filetype-xml"></i> Descargar XML
                </a>
              </div>
            </div>
          </div>
          
          <!-- Método Web Service -->
          <div class="col-md-6">
            <div class="export-method">
              <div class="method-title text-info">
                <i class="bi bi-cloud-arrow-down"></i> Método Web Service SOAP
              </div>
              <p class="small text-muted">Generación a través de servicios SOAP con almacenamiento en servidor</p>
              <div class="d-grid gap-2">
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export'=>'json', 'use_webservice'=>'1']))) ?>"
                   class="btn btn-outline-success btn-sm">
                   <i class="bi bi-cloud"></i> Generar JSON (SOAP)
                </a>
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export'=>'xml', 'use_webservice'=>'1']))) ?>"
                   class="btn btn-outline-warning btn-sm">
                   <i class="bi bi-cloud"></i> Generar XML (SOAP)
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Enlaces adicionales -->
        <div class="mt-3 text-center">
          <small class="text-muted">
            <a href="../report/import.php" class="me-3">
              <i class="bi bi-upload"></i> Importar Datos
            </a>
            <a href="../../WebService/serviceProy.php" class="me-3">
              <i class="bi bi-gear"></i> Cliente SOAP Completo
            </a>
            <a href="../../WebService/DatosFactura/" target="_blank">
              <i class="bi bi-folder"></i> Ver Archivos Generados
            </a>
          </small>
        </div>
      </div>
    </div>

    <!-- Card de Resultados -->
    <?php if ($results): ?>
    <div class="card">
      <div class="card-header">
        <i class="bi bi-table"></i> Resultados de la Consulta
        <span class="badge bg-secondary ms-2"><?= count($results) ?> registros</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-dark">
              <tr>
                <th>ID Venta</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">Precio</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $r): ?>
              <tr>
                <td><?= $r['sale_id'] ?></td>
                <td><?= $r['sale_date'] ?></td>
                <td><?= htmlspecialchars($r['cliente']) ?></td>
                <td><?= htmlspecialchars($r['producto']) ?></td>
                <td class="text-end"><?= $r['quantity'] ?></td>
                <td class="text-end">$<?= number_format($r['unit_price'],2) ?></td>
                <td class="text-end">$<?= number_format($r['subtotal'],2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <td colspan="6"><strong>Total:</strong></td>
                <td class="text-end"><strong>$<?= number_format(array_sum(array_column($results, 'subtotal')), 2) ?></strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No hay registros que coincidan con los filtros aplicados.
      </div>
    <?php endif; ?>

  </div>
</main>

<!-- Mostrar mensajes de error si existen -->
<?php if (isset($_SESSION['error'])): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div class="toast show" role="alert">
    <div class="toast-header bg-danger text-white">
      <strong class="me-auto">Error</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">
      <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
  </div>
</div>
<?php unset($_SESSION['error']); endif; ?>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-ocultar mensajes después de 5 segundos
setTimeout(function() {
    var toasts = document.querySelectorAll('.toast');
    toasts.forEach(function(toast) {
        var bsToast = new bootstrap.Toast(toast);
        bsToast.hide();
    });
}, 5000);
</script>
</body>
</html>
