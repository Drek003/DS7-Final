<?php

require_once 'nusoap.php';
require_once '../config/database.php';

// crear server
$server = new nusoap_server();

// configurar WSDL
$server->configureWSDL('ReportService', 'urn:ReportService', 'http://localhost/DS7-Final/WebService/serverProy.php', 'rpc', 'http://schemas.xmlsoap.org/soap/', 'http://schemas.xmlsoap.org/soap/');

// registrar el servicio de generación de reporte JSON
$server->register('generateReportJSON',
    array(
        'fecha_inicio' => 'xsd:string',
        'fecha_fin' => 'xsd:string', 
        'cliente' => 'xsd:string',
        'producto' => 'xsd:string'
    ),
    array('return' => 'xsd:string'),
    'urn:ReportService',
    'urn:ReportService#generateReportJSON',
    'rpc',
    'encoded',
    'Genera un reporte de ventas en formato JSON'
);

// registrar el servicio de generación de reporte XML
$server->register('generateReportXML',
    array(
        'fecha_inicio' => 'xsd:string',
        'fecha_fin' => 'xsd:string',
        'cliente' => 'xsd:string', 
        'producto' => 'xsd:string'
    ),
    array('return' => 'xsd:string'),
    'urn:ReportService',
    'urn:ReportService#generateReportXML',
    'rpc',
    'encoded',
    'Genera un reporte de ventas en formato XML'
);

// registrar el servicio de obtener datos de ventas
$server->register('getSalesData',
    array(
        'fecha_inicio' => 'xsd:string',
        'fecha_fin' => 'xsd:string',
        'cliente' => 'xsd:string',
        'producto' => 'xsd:string'
    ),
    array('return' => 'xsd:string'),
    'urn:ReportService', 
    'urn:ReportService#getSalesData',
    'rpc',
    'encoded',
    'Obtiene datos de ventas filtrados en formato JSON'
);

// función para generar reporte JSON
function generateReportJSON($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
    try {
        $results = getSalesDataFromDB($fecha_inicio, $fecha_fin, $cliente, $producto);
        
        $filename = 'reporte_ventas_' . date('Ymd_His') . '.json';
        $jsonData = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Guardar archivo en el servidor
        $filePath = '../WebService/DatosFactura/' . $filename;
        file_put_contents($filePath, $jsonData);
        
        return json_encode([
            'success' => true,
            'message' => 'Reporte JSON generado exitosamente',
            'filename' => $filename,
            'path' => $filePath,
            'records' => count($results),
            'data' => $jsonData
        ]);
        
    } catch (Exception $e) {
        return json_encode([
            'success' => false,
            'message' => 'Error al generar reporte JSON: ' . $e->getMessage()
        ]);
    }
}

// función para generar reporte XML
function generateReportXML($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
    try {
        $results = getSalesDataFromDB($fecha_inicio, $fecha_fin, $cliente, $producto);
        
        $filename = 'reporte_ventas_' . date('Ymd_His') . '.xml';
        
        // Crear XML
        $xml = new SimpleXMLElement('<ventas/>');
        $xml->addAttribute('fecha_generacion', date('Y-m-d H:i:s'));
        $xml->addAttribute('total_registros', count($results));
        
        foreach ($results as $row) {
            $item = $xml->addChild('venta');
            foreach ($row as $key => $value) {
                $item->addChild($key, htmlspecialchars($value));
            }
        }
        
        $xmlData = $xml->asXML();
        
        // Guardar archivo en el servidor
        $filePath = '../WebService/DatosFactura/' . $filename;
        file_put_contents($filePath, $xmlData);
        
        return json_encode([
            'success' => true,
            'message' => 'Reporte XML generado exitosamente',
            'filename' => $filename,
            'path' => $filePath,
            'records' => count($results),
            'data' => $xmlData
        ]);
        
    } catch (Exception $e) {
        return json_encode([
            'success' => false,
            'message' => 'Error al generar reporte XML: ' . $e->getMessage()
        ]);
    }
}

// función para obtener datos de ventas (solo datos, sin generar archivo)
function getSalesData($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
    try {
        $results = getSalesDataFromDB($fecha_inicio, $fecha_fin, $cliente, $producto);
        
        return json_encode([
            'success' => true,
            'message' => 'Datos obtenidos exitosamente',
            'records' => count($results),
            'data' => $results
        ]);
        
    } catch (Exception $e) {
        return json_encode([
            'success' => false,
            'message' => 'Error al obtener datos: ' . $e->getMessage()
        ]);
    }
}

// función auxiliar para obtener datos de la base de datos
function getSalesDataFromDB($fecha_inicio, $fecha_fin, $cliente, $producto) {
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

    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Construir filtros
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

    if ($cliente !== '') {
        $where[]            = 's.customer_name = :cliente';
        $params[':cliente'] = $cliente;
    }

    if ($producto !== '') {
        $where[]            = 'sd.product_id = :pid';
        $params[':pid']     = $producto;
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

    $sql .= ' ORDER BY s.sale_date DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// asegurar que el directorio existe
if (!file_exists('../WebService/DatosFactura/')) {
    mkdir('../WebService/DatosFactura/', 0777, true);
}

$HTTP_RAW_POST_DATA = file_get_contents('php://input');
$server->service($HTTP_RAW_POST_DATA);

?>