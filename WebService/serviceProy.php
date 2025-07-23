<?php

require_once 'nusoap.php';

// URL de SOAP
$wsdl = 'http://localhost/DS7-Final/WebService/serverProy.php?wsdl';

$client = new nusoap_client($wsdl, true);

// verificar errores
$err = $client->getError();
if ($err) {
    echo 'Constructor error de SOAP: ' . $err;
    exit();
}

// Ejemplo de uso de los servicios

echo "<h2>Cliente SOAP - Servicio de Reportes</h2>";

// Verificar si se han enviado parámetros por GET para generar reportes
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $cliente = $_GET['cliente'] ?? '';
    $producto = $_GET['producto'] ?? '';
    
    echo "<h3>Ejecutando acción: " . htmlspecialchars($action) . "</h3>";
    
    switch ($action) {
        case 'json':
            // Llamada al servicio de generación de JSON
            $result = $client->call('generateReportJSON', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
            break;
            
        case 'xml':
            // Llamada al servicio de generación de XML
            $result = $client->call('generateReportXML', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
            break;
            
        case 'data':
            // Llamada al servicio de obtención de datos
            $result = $client->call('getSalesData', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
            break;
            
        default:
            echo "<p>Acción no válida</p>";
            exit;
    }
    
    // verificar errores de llamada
    if ($client->fault) {
        echo "<div style='color: red;'>";
        echo "<h4>Fallo en llamada SOAP:</h4>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        echo "</div>";
    } else {
        $err = $client->getError();
        if ($err) {
            echo "<div style='color: red;'>";
            echo "<h4>Error:</h4>";
            echo "<p>$err</p>";
            echo "</div>";
        } else {
            echo "<div style='color: green;'>";
            echo "<h4>Resultado del servicio:</h4>";
            
            // Decodificar el JSON de respuesta
            $response = json_decode($result, true);
            
            if ($response && isset($response['success'])) {
                if ($response['success']) {
                    echo "<p><strong>✓ " . $response['message'] . "</strong></p>";
                    
                    if (isset($response['filename'])) {
                        echo "<p>Archivo generado: <strong>" . $response['filename'] . "</strong></p>";
                    }
                    
                    if (isset($response['records'])) {
                        echo "<p>Registros procesados: <strong>" . $response['records'] . "</strong></p>";
                    }
                    
                    // Mostrar datos si es una consulta de datos
                    if ($action === 'data' && isset($response['data'])) {
                        echo "<h5>Datos obtenidos:</h5>";
                        echo "<pre>" . json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                    }
                    
                    // Para XML y JSON, mostrar un preview de los datos
                    if (($action === 'xml' || $action === 'json') && isset($response['data'])) {
                        echo "<h5>Preview del archivo generado:</h5>";
                        if ($action === 'xml') {
                            echo "<pre>" . htmlspecialchars($response['data']) . "</pre>";
                        } else {
                            echo "<pre>" . $response['data'] . "</pre>";
                        }
                    }
                } else {
                    echo "<p style='color: red;'><strong>✗ " . $response['message'] . "</strong></p>";
                }
            } else {
                echo "<p>Respuesta: " . htmlspecialchars($result) . "</p>";
            }
            echo "</div>";
        }
    }
    
    echo "<hr>";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente SOAP - Servicio de Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 20px; }
        .service-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cliente SOAP - Servicio de Reportes</h1>
        <p class="text-muted">Este cliente permite probar los servicios SOAP de generación de reportes</p>
        
        <!-- Formulario para generar JSON -->
        <div class="service-form">
            <h3>🗂️ Generar Reporte JSON</h3>
            <form method="get" class="row g-3">
                <input type="hidden" name="action" value="json">
                
                <div class="col-md-3">
                    <label for="fecha_inicio_json" class="form-label">Fecha Inicio</label>
                    <input type="date" id="fecha_inicio_json" name="fecha_inicio" class="form-control" 
                           value="<?= $_GET['fecha_inicio'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_fin_json" class="form-label">Fecha Fin</label>
                    <input type="date" id="fecha_fin_json" name="fecha_fin" class="form-control"
                           value="<?= $_GET['fecha_fin'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="cliente_json" class="form-label">Cliente</label>
                    <input type="text" id="cliente_json" name="cliente" class="form-control" 
                           placeholder="Nombre del cliente" value="<?= $_GET['cliente'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="producto_json" class="form-label">ID Producto</label>
                    <input type="number" id="producto_json" name="producto" class="form-control" 
                           placeholder="ID del producto" value="<?= $_GET['producto'] ?? '' ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Generar Reporte JSON</button>
                </div>
            </form>
        </div>
        
        <!-- Formulario para generar XML -->
        <div class="service-form">
            <h3>📄 Generar Reporte XML</h3>
            <form method="get" class="row g-3">
                <input type="hidden" name="action" value="xml">
                
                <div class="col-md-3">
                    <label for="fecha_inicio_xml" class="form-label">Fecha Inicio</label>
                    <input type="date" id="fecha_inicio_xml" name="fecha_inicio" class="form-control"
                           value="<?= $_GET['fecha_inicio'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_fin_xml" class="form-label">Fecha Fin</label>
                    <input type="date" id="fecha_fin_xml" name="fecha_fin" class="form-control"
                           value="<?= $_GET['fecha_fin'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="cliente_xml" class="form-label">Cliente</label>
                    <input type="text" id="cliente_xml" name="cliente" class="form-control" 
                           placeholder="Nombre del cliente" value="<?= $_GET['cliente'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="producto_xml" class="form-label">ID Producto</label>
                    <input type="number" id="producto_xml" name="producto" class="form-control" 
                           placeholder="ID del producto" value="<?= $_GET['producto'] ?? '' ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-warning">Generar Reporte XML</button>
                </div>
            </form>
        </div>
        
        <!-- Formulario para obtener datos -->
        <div class="service-form">
            <h3>📊 Obtener Datos de Ventas</h3>
            <form method="get" class="row g-3">
                <input type="hidden" name="action" value="data">
                
                <div class="col-md-3">
                    <label for="fecha_inicio_data" class="form-label">Fecha Inicio</label>
                    <input type="date" id="fecha_inicio_data" name="fecha_inicio" class="form-control"
                           value="<?= $_GET['fecha_inicio'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="fecha_fin_data" class="form-label">Fecha Fin</label>
                    <input type="date" id="fecha_fin_data" name="fecha_fin" class="form-control"
                           value="<?= $_GET['fecha_fin'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="cliente_data" class="form-label">Cliente</label>
                    <input type="text" id="cliente_data" name="cliente" class="form-control" 
                           placeholder="Nombre del cliente" value="<?= $_GET['cliente'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="producto_data" class="form-label">ID Producto</label>
                    <input type="number" id="producto_data" name="producto" class="form-control" 
                           placeholder="ID del producto" value="<?= $_GET['producto'] ?? '' ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-info">Obtener Datos</button>
                </div>
            </form>
        </div>
        
        <div class="mt-4">
            <h4>Enlaces útiles:</h4>
            <ul>
                <li><a href="serverProy.php?wsdl" target="_blank">Ver WSDL del servicio</a></li>
                <li><a href="../views/report/index.php" target="_blank">Ver interfaz de reportes original</a></li>
                <li><a href="DatosFactura/" target="_blank">Ver archivos generados</a></li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>