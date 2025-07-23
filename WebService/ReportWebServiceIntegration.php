<?php
/**
 * ReportWebServiceIntegration.php
 * Clase para integrar el sistema de reportes con Web Services SOAP
 */

require_once 'nusoap.php';

class ReportWebServiceIntegration {
    
    private $wsdl_url;
    private $client;
    
    public function __construct($wsdl_url = 'http://localhost/DS7-Final/WebService/serverProy.php?wsdl') {
        $this->wsdl_url = $wsdl_url;
        $this->initializeClient();
    }
    
    /**
     * Inicializar cliente SOAP
     */
    private function initializeClient() {
        try {
            $this->client = new nusoap_client($this->wsdl_url, true);
            
            $err = $this->client->getError();
            if ($err) {
                throw new Exception('Error al inicializar cliente SOAP: ' . $err);
            }
        } catch (Exception $e) {
            throw new Exception('Error de conexión SOAP: ' . $e->getMessage());
        }
    }
    
    /**
     * Generar reporte JSON vía Web Service
     */
    public function generateJSONReport($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
        try {
            $result = $this->client->call('generateReportJSON', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
            
            if ($this->client->fault) {
                throw new Exception('Fallo en llamada SOAP: ' . print_r($result, true));
            }
            
            $err = $this->client->getError();
            if ($err) {
                throw new Exception('Error SOAP: ' . $err);
            }
            
            return json_decode($result, true);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al generar reporte JSON: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar reporte XML vía Web Service
     */
    public function generateXMLReport($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
        try {
            $result = $this->client->call('generateReportXML', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
            
            if ($this->client->fault) {
                throw new Exception('Fallo en llamada SOAP: ' . print_r($result, true));
            }
            
            $err = $this->client->getError();
            if ($err) {
                throw new Exception('Error SOAP: ' . $err);
            }
            
            return json_decode($result, true);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al generar reporte XML: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener datos de ventas vía Web Service
     */
    public function getSalesData($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
        try {
            $result = $this->client->call('getSalesData', array(
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'cliente' => $cliente,
                'producto' => $producto
            ));
            
            if ($this->client->fault) {
                throw new Exception('Fallo en llamada SOAP: ' . print_r($result, true));
            }
            
            $err = $this->client->getError();
            if ($err) {
                throw new Exception('Error SOAP: ' . $err);
            }
            
            return json_decode($result, true);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Exportar y descargar archivo JSON
     */
    public function exportJSONFile($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
        $result = $this->generateJSONReport($fecha_inicio, $fecha_fin, $cliente, $producto);
        
        if ($result['success'] && isset($result['data'])) {
            $filename = 'reporte_ventas_' . date('Ymd_His') . '.json';
            
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $result['data'];
            exit;
        } else {
            throw new Exception($result['message'] ?? 'Error al generar archivo JSON');
        }
    }
    
    /**
     * Exportar y descargar archivo XML
     */
    public function exportXMLFile($fecha_inicio = '', $fecha_fin = '', $cliente = '', $producto = '') {
        $result = $this->generateXMLReport($fecha_inicio, $fecha_fin, $cliente, $producto);
        
        if ($result['success'] && isset($result['data'])) {
            $filename = 'reporte_ventas_' . date('Ymd_His') . '.xml';
            
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $result['data'];
            exit;
        } else {
            throw new Exception($result['message'] ?? 'Error al generar archivo XML');
        }
    }
    
    /**
     * Verificar estado del Web Service
     */
    public function testConnection() {
        try {
            $result = $this->getSalesData('', '', '', '');
            return [
                'success' => true,
                'message' => 'Conexión al Web Service exitosa',
                'service_response' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }
}

// Ejemplo de uso directo del archivo
if (basename($_SERVER['PHP_SELF']) === 'ReportWebServiceIntegration.php') {
    
    // Solo para pruebas directas
    if (isset($_GET['test'])) {
        header('Content-Type: application/json');
        
        try {
            $integration = new ReportWebServiceIntegration();
            
            switch ($_GET['test']) {
                case 'connection':
                    echo json_encode($integration->testConnection());
                    break;
                    
                case 'json':
                    echo json_encode($integration->generateJSONReport(
                        $_GET['fecha_inicio'] ?? '',
                        $_GET['fecha_fin'] ?? '',
                        $_GET['cliente'] ?? '',
                        $_GET['producto'] ?? ''
                    ));
                    break;
                    
                case 'xml':
                    echo json_encode($integration->generateXMLReport(
                        $_GET['fecha_inicio'] ?? '',
                        $_GET['fecha_fin'] ?? '',
                        $_GET['cliente'] ?? '',
                        $_GET['producto'] ?? ''
                    ));
                    break;
                    
                case 'data':
                    echo json_encode($integration->getSalesData(
                        $_GET['fecha_inicio'] ?? '',
                        $_GET['fecha_fin'] ?? '',
                        $_GET['cliente'] ?? '',
                        $_GET['producto'] ?? ''
                    ));
                    break;
                    
                case 'download_json':
                    $integration->exportJSONFile(
                        $_GET['fecha_inicio'] ?? '',
                        $_GET['fecha_fin'] ?? '',
                        $_GET['cliente'] ?? '',
                        $_GET['producto'] ?? ''
                    );
                    break;
                    
                case 'download_xml':
                    $integration->exportXMLFile(
                        $_GET['fecha_inicio'] ?? '',
                        $_GET['fecha_fin'] ?? '',
                        $_GET['cliente'] ?? '',
                        $_GET['producto'] ?? ''
                    );
                    break;
                    
                default:
                    echo json_encode(['error' => 'Prueba no válida']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    // Interfaz de prueba
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Integración Web Service - Reportes</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <h1>Integración Web Service - Reportes</h1>
            <p class="text-muted">Prueba la integración entre el sistema de reportes y los Web Services SOAP</p>
            
            <div class="row">
                <div class="col-md-6">
                    <h3>Pruebas Rápidas</h3>
                    <div class="d-grid gap-2">
                        <a href="?test=connection" class="btn btn-primary">Probar Conexión</a>
                        <a href="?test=data" class="btn btn-info">Obtener Datos</a>
                        <a href="?test=json" class="btn btn-success">Generar JSON</a>
                        <a href="?test=xml" class="btn btn-warning">Generar XML</a>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h3>Descargas Directas</h3>
                    <div class="d-grid gap-2">
                        <a href="?test=download_json" class="btn btn-outline-success">Descargar JSON</a>
                        <a href="?test=download_xml" class="btn btn-outline-warning">Descargar XML</a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h4>Enlaces Relacionados</h4>
                <ul>
                    <li><a href="serverProy.php?wsdl">Ver WSDL</a></li>
                    <li><a href="serviceProy.php">Cliente SOAP Completo</a></li>
                    <li><a href="../views/report/index.php">Sistema de Reportes Original</a></li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
