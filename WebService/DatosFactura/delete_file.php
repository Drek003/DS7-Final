<?php
/**
 * delete_file.php
 * Script para eliminar archivos generados por el Web Service
 */

header('Content-Type: application/json');

// Solo permitir métodos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$directory = __DIR__;

// Verificar si es una eliminación masiva
if (isset($input['action']) && $input['action'] === 'clear_all') {
    try {
        $files = glob($directory . DIRECTORY_SEPARATOR . '*');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'index.php' && basename($file) !== 'delete_file.php') {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Se eliminaron $deleted archivos exitosamente"
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al eliminar archivos: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Eliminar archivo individual
if (!isset($input['filename'])) {
    echo json_encode(['success' => false, 'message' => 'Nombre de archivo requerido']);
    exit;
}

$filename = $input['filename'];

// Validar nombre de archivo (solo permitir archivos seguros)
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
    echo json_encode(['success' => false, 'message' => 'Nombre de archivo inválido']);
    exit;
}

// Prevenir acceso a archivos del sistema
$restricted_files = ['index.php', 'delete_file.php', '.htaccess'];
if (in_array($filename, $restricted_files)) {
    echo json_encode(['success' => false, 'message' => 'No se puede eliminar este archivo']);
    exit;
}

$filepath = $directory . DIRECTORY_SEPARATOR . $filename;

// Verificar que el archivo existe y está en el directorio correcto
if (!file_exists($filepath) || !is_file($filepath)) {
    echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
    exit;
}

// Verificar que el archivo está dentro del directorio permitido
$realpath = realpath($filepath);
$realdir = realpath($directory);

if (!$realpath || !$realdir || strpos($realpath, $realdir) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Archivo fuera del directorio permitido']);
    exit;
}

// Intentar eliminar el archivo
try {
    if (unlink($filepath)) {
        echo json_encode([
            'success' => true, 
            'message' => "Archivo '$filename' eliminado exitosamente"
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo eliminar el archivo'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al eliminar archivo: ' . $e->getMessage()
    ]);
}
?>
