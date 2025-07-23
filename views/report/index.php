<?php
// report_interface.php
// Interfaz para generar reporte de ventas filtrable y exportable a XML/JSON via Web Services

// 1) Incluye tu configuración general (que ya hace session_start() y define isAdmin(), isLoggedIn(), etc.)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../WebService/nusoap.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Verificar que el usuario tenga permisos para acceder a reportes (Solo Admin y Consultor)
if (!canAccessReports()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a los reportes';
    redirect('../../index.php');
}

// Configuración de conexión a la base de datos
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

// Funciones para Web Service
function callWebServiceExport($action, $fecha_inicio, $fecha_fin, $cliente, $producto) {
    $wsdl_url = 'http://localhost/DS7-Final/WebService/serverProy.php?wsdl';
    
    try {
        $client = new nusoap_client($wsdl_url, true);
        
        $err = $client->getError();
        if ($err) {
            throw new Exception('Error al inicializar cliente SOAP: ' . $err);
        }
        
        if ($action === 'json') {
            $result = $client->call('generateReportJSON', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
        } else {
            $result = $client->call('generateReportXML', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
        }
        
        if ($client->fault) {
            throw new Exception('Fallo en llamada SOAP: ' . print_r($result, true));
        }
        
        $err = $client->getError();
        if ($err) {
            throw new Exception('Error SOAP: ' . $err);
        }
        
        $response = json_decode($result, true);
        
        if ($response && $response['success'] && isset($response['data'])) {
            $filename = 'reporte_ventas_' . date('Ymd_His') . '.' . $action;
            
            if ($action === 'json') {
                header('Content-Type: application/json; charset=utf-8');
            } else {
                header('Content-Type: application/xml; charset=utf-8');
            }
            
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            
            echo $response['data'];
            exit;
        } else {
            throw new Exception($response['message'] ?? 'Error al generar archivo');
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error en Web Service: ' . $e->getMessage();
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 1) Recoger filtros desde $_GET
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin    = $_GET['fecha_fin']    ?? '';
$cliente_id   = $_GET['cliente']      ?? '';
$producto_id  = $_GET['producto']     ?? '';
$export       = $_GET['export']       ?? '';

// Si se solicita exportación, usar Web Service
if ($export === 'json') {
    callWebServiceExport('json', $fecha_inicio, $fecha_fin, $cliente_id, $producto_id);
}

if ($export === 'xml') {
    callWebServiceExport('xml', $fecha_inicio, $fecha_fin, $cliente_id, $producto_id);
}

// 2) Cargar lista de clientes (siempre completa)
$clientes = $pdo
    ->query("SELECT DISTINCT customer_name FROM sales ORDER BY customer_name")
    ->fetchAll(PDO::FETCH_COLUMN);

// 3) Cargar lista de productos, dependiendo de si hay cliente seleccionado
if ($cliente_id !== '') {
    // Sólo productos que ese cliente ha comprado
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
    // Todos los productos
    $productos = $pdo
      ->query("SELECT DISTINCT product_id, product_name
               FROM sale_details
               ORDER BY product_name")
      ->fetchAll(PDO::FETCH_ASSOC);
}

// 4) Si el producto que venía por GET ya no está en esa lista, lo limpiamos
$productos_ids = array_column($productos, 'product_id');
if ($producto_id !== '' && !in_array((int)$producto_id, $productos_ids, true)) {
    $producto_id = '';
}

// 5) Construir el WHERE **una sola vez**
$where  = [];
$params = [];

// Si pusieron Fecha Inicio, filtramos desde medianoche de ese día
if ($fecha_inicio !== '') {
    $where[]          = 's.sale_date >= :fini';
    // Aseguramos comparar desde el inicio del día
    $params[':fini']  = $fecha_inicio . ' 00:00:00';
}

// Si pusieron Fecha Fin, filtramos hasta el final de ese día
if ($fecha_fin !== '') {
    $where[]         = 's.sale_date <= :ffin';
    // Incluimos toda la jornada del día final
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

// Consulta principal
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-dark.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="../../assets/css/catg.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap-dark.css" rel="stylesheet">
    <style>
        /* Asegurar que el fondo sea consistente */
        body {
            background-color: var(--bs-body-bg, #f8f9fa) !important;
        }
        
        .main-content {
            background-color: transparent !important;
            min-height: 100vh;
        }
        
        .container-fluid {
            background-color: transparent !important;
        }
        
        /* Si estás usando tema oscuro, ajusta aquí */
        [data-bs-theme="dark"] body,
        .dark-theme body {
            background-color: #212529 !important;
        }
        
        [data-bs-theme="dark"] .main-content,
        .dark-theme .main-content {
            background-color: transparent !important;
        }
        
        /* Asegurar que las cards mantengan su estilo pero el fondo general sea consistente */
        .card {
            background-color: var(--bs-card-bg);
            border: var(--bs-card-border-width) solid var(--bs-card-border-color);
        }
    </style>
</head>
<body>
<?php include '../../includes/nav.php'; ?>

<main class="main-content">
  <div class="page-header">
    <h1>Reporte de Ventas</h1>
  </div>
  <div class="container-fluid">
    <!-- Card de Filtros -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="bi"></i>Filtros de Búsqueda
      </div>
      <div class="card-body">
        <form id="filter-form" method="get" class="row g-3">
            <!-- Campos de fecha, cliente y producto -->
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

            <!-- Botones -->
            <div class="col-12 mt-3 d-flex align-items-center">
              <button type="submit" class="btn btn-primary me-2">Filtrar</button>
              <button type="button" class="btn btn-primary me-2" onclick="window.location.href=window.location.pathname"> Limpiar </button>
              <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export'=>'json']))) ?>"
                class="btn btn-success me-2">Exportar JSON</a>
              <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export'=>'xml']))) ?>"
                class="btn btn-success me-2">Exportar XML</a>
              <!-- Este ms-auto empuja Importar al extremo derecho -->
              <a href="import.php" class="btn btn-info me-2 ms-auto">Importar</a>
            </div>
        </form>
      </div>
    </div>

    <!-- Card de Resultados -->
    <?php if ($results): ?>
    <div class="card">
      <div class="card-body p-0">
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
              <td class="text-end"><?= number_format($r['unit_price'],2) ?></td>
              <td class="text-end"><?= number_format($r['subtotal'],2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-secondary">No hay registros con esos filtros.</div>
    <?php endif; ?>

  </div>
</main>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
