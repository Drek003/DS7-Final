<?php
/**
 * consigueDatos.php
 * Utilidad para obtener datos de ventas para facturación
 */

require_once '../../config/database.php';

class ConseguirDatosFactura {
    
    private $pdo;
    
    public function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
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
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener datos de una venta específica para facturación
     */
    public function obtenerDatosVenta($sale_id) {
        try {
            // Obtener datos principales de la venta
            $sql_venta = "
                SELECT 
                    s.id,
                    s.sale_date,
                    s.customer_name,
                    s.total_amount,
                    s.payment_method,
                    s.status
                FROM sales s 
                WHERE s.id = :sale_id
            ";
            
            $stmt = $this->pdo->prepare($sql_venta);
            $stmt->execute([':sale_id' => $sale_id]);
            $venta = $stmt->fetch();
            
            if (!$venta) {
                throw new Exception("Venta no encontrada con ID: $sale_id");
            }
            
            // Obtener detalles de la venta
            $sql_detalles = "
                SELECT 
                    sd.product_id,
                    sd.product_name,
                    sd.quantity,
                    sd.product_price,
                    sd.subtotal
                FROM sale_details sd 
                WHERE sd.sale_id = :sale_id
                ORDER BY sd.product_name
            ";
            
            $stmt = $this->pdo->prepare($sql_detalles);
            $stmt->execute([':sale_id' => $sale_id]);
            $detalles = $stmt->fetchAll();
            
            return [
                'success' => true,
                'venta' => $venta,
                'detalles' => $detalles,
                'fecha_consulta' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener resumen de ventas por periodo
     */
    public function obtenerResumenVentas($fecha_inicio, $fecha_fin) {
        try {
            $sql = "
                SELECT 
                    COUNT(s.id) as total_ventas,
                    SUM(s.total_amount) as monto_total,
                    AVG(s.total_amount) as promedio_venta,
                    MIN(s.sale_date) as primera_venta,
                    MAX(s.sale_date) as ultima_venta,
                    COUNT(DISTINCT s.customer_name) as clientes_unicos
                FROM sales s 
                WHERE s.sale_date >= :fecha_inicio 
                AND s.sale_date <= :fecha_fin
            ";
            
            $params = [
                ':fecha_inicio' => $fecha_inicio . ' 00:00:00',
                ':fecha_fin' => $fecha_fin . ' 23:59:59'
            ];
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $resumen = $stmt->fetch();
            
            return [
                'success' => true,
                'resumen' => $resumen,
                'periodo' => [
                    'inicio' => $fecha_inicio,
                    'fin' => $fecha_fin
                ],
                'fecha_consulta' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener productos más vendidos
     */
    public function obtenerProductosMasVendidos($limite = 10) {
        try {
            $sql = "
                SELECT 
                    sd.product_id,
                    sd.product_name,
                    SUM(sd.quantity) as cantidad_total,
                    SUM(sd.subtotal) as ingresos_total,
                    COUNT(DISTINCT sd.sale_id) as ventas_count,
                    AVG(sd.product_price) as precio_promedio
                FROM sale_details sd
                GROUP BY sd.product_id, sd.product_name
                ORDER BY cantidad_total DESC, ingresos_total DESC
                LIMIT :limite
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $productos = $stmt->fetchAll();
            
            return [
                'success' => true,
                'productos' => $productos,
                'limite' => $limite,
                'fecha_consulta' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener clientes más frecuentes
     */
    public function obtenerClientesFrecuentes($limite = 10) {
        try {
            $sql = "
                SELECT 
                    s.customer_name,
                    COUNT(s.id) as total_compras,
                    SUM(s.total_amount) as monto_total,
                    AVG(s.total_amount) as promedio_compra,
                    MIN(s.sale_date) as primera_compra,
                    MAX(s.sale_date) as ultima_compra
                FROM sales s
                GROUP BY s.customer_name
                ORDER BY total_compras DESC, monto_total DESC
                LIMIT :limite
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $clientes = $stmt->fetchAll();
            
            return [
                'success' => true,
                'clientes' => $clientes,
                'limite' => $limite,
                'fecha_consulta' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar datos completos para factura en formato JSON
     */
    public function generarFacturaJSON($sale_id) {
        $datos = $this->obtenerDatosVenta($sale_id);
        
        if (!$datos['success']) {
            return $datos;
        }
        
        $factura = [
            'factura' => [
                'numero' => 'FAC-' . str_pad($sale_id, 6, '0', STR_PAD_LEFT),
                'fecha_emision' => date('Y-m-d H:i:s'),
                'venta_id' => $sale_id
            ],
            'cliente' => [
                'nombre' => $datos['venta']['customer_name'],
                'fecha_venta' => $datos['venta']['sale_date']
            ],
            'detalles' => $datos['detalles'],
            'totales' => [
                'subtotal' => array_sum(array_column($datos['detalles'], 'subtotal')),
                'total' => $datos['venta']['total_amount'],
                'metodo_pago' => $datos['venta']['payment_method'] ?? 'No especificado'
            ],
            'metadata' => [
                'generado_en' => date('Y-m-d H:i:s'),
                'sistema' => 'DS7-Final Web Service'
            ]
        ];
        
        return [
            'success' => true,
            'factura' => $factura
        ];
    }
}

// Si se accede directamente al archivo, mostrar interfaz de prueba
if (basename($_SERVER['PHP_SELF']) === 'consigueDatos.php') {
    
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        
        try {
            $datos = new ConseguirDatosFactura();
            $action = $_GET['action'];
            
            switch ($action) {
                case 'venta':
                    $sale_id = $_GET['sale_id'] ?? null;
                    if (!$sale_id) {
                        echo json_encode(['success' => false, 'message' => 'ID de venta requerido']);
                        exit;
                    }
                    echo json_encode($datos->obtenerDatosVenta($sale_id));
                    break;
                    
                case 'resumen':
                    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
                    echo json_encode($datos->obtenerResumenVentas($fecha_inicio, $fecha_fin));
                    break;
                    
                case 'productos':
                    $limite = $_GET['limite'] ?? 10;
                    echo json_encode($datos->obtenerProductosMasVendidos($limite));
                    break;
                    
                case 'clientes':
                    $limite = $_GET['limite'] ?? 10;
                    echo json_encode($datos->obtenerClientesFrecuentes($limite));
                    break;
                    
                case 'factura':
                    $sale_id = $_GET['sale_id'] ?? null;
                    if (!$sale_id) {
                        echo json_encode(['success' => false, 'message' => 'ID de venta requerido']);
                        exit;
                    }
                    echo json_encode($datos->generarFacturaJSON($sale_id));
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Interfaz HTML para pruebas
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Conseguir Datos - Sistema de Facturación</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <h1>Sistema de Obtención de Datos - Facturación</h1>
            <p class="text-muted">Utilidad para obtener datos de ventas y generar información de facturación</p>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Consultas Rápidas</div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="?action=resumen" class="btn btn-primary">Resumen de Ventas (Este Mes)</a>
                                <a href="?action=productos&limite=5" class="btn btn-info">Top 5 Productos</a>
                                <a href="?action=clientes&limite=5" class="btn btn-success">Top 5 Clientes</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Consulta Específica</div>
                        <div class="card-body">
                            <form action="" method="get">
                                <div class="mb-3">
                                    <label for="action" class="form-label">Acción</label>
                                    <select name="action" id="action" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="venta">Datos de Venta</option>
                                        <option value="factura">Generar Factura</option>
                                        <option value="resumen">Resumen por Periodo</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sale_id" class="form-label">ID de Venta (si aplica)</label>
                                    <input type="number" name="sale_id" id="sale_id" class="form-control">
                                </div>
                                
                                <button type="submit" class="btn btn-warning">Consultar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h4>Enlaces Relacionados</h4>
                <ul>
                    <li><a href="../serviceProy.php">Cliente SOAP Completo</a></li>
                    <li><a href="../../views/report/report_with_webservice.php">Sistema de Reportes</a></li>
                    <li><a href="index.php">Ver Archivos Generados</a></li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
