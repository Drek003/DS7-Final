<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos Generados - Web Service Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .file-icon { font-size: 1.5em; margin-right: 10px; }
        .json-icon { color: #28a745; }
        .xml-icon { color: #ffc107; }
        .file-item {
            transition: background-color 0.2s;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        .file-item:hover {
            background-color: #f8f9fa;
        }
        .file-size {
            color: #6c757d;
            font-size: 0.9em;
        }
        .file-date {
            color: #6c757d;
            font-size: 0.85em;
        }
        .no-files {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-folder-open"></i> Archivos Generados por Web Service</h1>
            <div>
                <a href="../serviceProy.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-cog"></i> Cliente SOAP
                </a>
                <a href="../../views/report/report_with_webservice.php" class="btn btn-info btn-sm">
                    <i class="fas fa-chart-bar"></i> Sistema de Reportes
                </a>
                <button onclick="location.reload()" class="btn btn-secondary btn-sm">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
        </div>

        <?php
        $directory = __DIR__;
        $files = [];
        
        // Obtener todos los archivos del directorio
        if (is_dir($directory)) {
            $scan = scandir($directory);
            foreach ($scan as $file) {
                if ($file !== '.' && $file !== '..' && is_file($directory . DIRECTORY_SEPARATOR . $file)) {
                    $files[] = [
                        'name' => $file,
                        'path' => $directory . DIRECTORY_SEPARATOR . $file,
                        'size' => filesize($directory . DIRECTORY_SEPARATOR . $file),
                        'modified' => filemtime($directory . DIRECTORY_SEPARATOR . $file),
                        'extension' => strtolower(pathinfo($file, PATHINFO_EXTENSION))
                    ];
                }
            }
        }
        
        // Ordenar por fecha de modificación (más reciente primero)
        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        if (empty($files)): ?>
            <div class="no-files">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <h4>No hay archivos generados</h4>
                <p>Los archivos generados por los Web Services aparecerán aquí.</p>
                <a href="../serviceProy.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Generar Primer Reporte
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-list"></i> Archivos Disponibles
                            <span class="badge bg-secondary ms-2"><?= count($files) ?> archivos</span>
                        </div>
                        <div class="card-body">
                            <?php foreach ($files as $file): 
                                $iconClass = '';
                                $typeLabel = '';
                                
                                switch ($file['extension']) {
                                    case 'json':
                                        $iconClass = 'fas fa-file-code json-icon';
                                        $typeLabel = 'JSON';
                                        break;
                                    case 'xml':
                                        $iconClass = 'fas fa-file-code xml-icon';
                                        $typeLabel = 'XML';
                                        break;
                                    default:
                                        $iconClass = 'fas fa-file';
                                        $typeLabel = strtoupper($file['extension']);
                                }
                            ?>
                                <div class="file-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <i class="<?= $iconClass ?> file-icon"></i>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($file['name']) ?></div>
                                                <div class="file-date">
                                                    <i class="fas fa-clock"></i> 
                                                    <?= date('d/m/Y H:i:s', $file['modified']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <span class="badge bg-info me-2"><?= $typeLabel ?></span>
                                            <span class="file-size me-3">
                                                <?= number_format($file['size'] / 1024, 2) ?> KB
                                            </span>
                                            <div class="btn-group" role="group">
                                                <a href="<?= htmlspecialchars($file['name']) ?>" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   target="_blank">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="<?= htmlspecialchars($file['name']) ?>" 
                                                   class="btn btn-outline-success btn-sm" 
                                                   download>
                                                    <i class="fas fa-download"></i> Descargar
                                                </a>
                                                <button onclick="deleteFile('<?= htmlspecialchars($file['name']) ?>')" 
                                                        class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-file-code json-icon"></i> Archivos JSON
                            </h5>
                            <h3 class="text-success">
                                <?= count(array_filter($files, function($f) { return $f['extension'] === 'json'; })) ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-file-code xml-icon"></i> Archivos XML
                            </h5>
                            <h3 class="text-warning">
                                <?= count(array_filter($files, function($f) { return $f['extension'] === 'xml'; })) ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-hdd"></i> Espacio Total
                            </h5>
                            <h3 class="text-info">
                                <?= number_format(array_sum(array_column($files, 'size')) / 1024, 2) ?> KB
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Botón para limpiar todos los archivos -->
        <?php if (!empty($files)): ?>
            <div class="text-center mt-4">
                <button onclick="clearAllFiles()" class="btn btn-outline-danger">
                    <i class="fas fa-trash-alt"></i> Limpiar Todos los Archivos
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteFile(filename) {
            if (confirm('¿Estás seguro de que quieres eliminar el archivo: ' + filename + '?')) {
                // En un entorno real, esto debería ser una llamada AJAX a un script PHP
                // Por ahora, simplemente recarga la página
                fetch('delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({filename: filename})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar el archivo: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el archivo');
                });
            }
        }
        
        function clearAllFiles() {
            if (confirm('¿Estás seguro de que quieres eliminar TODOS los archivos generados?')) {
                fetch('delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({action: 'clear_all'})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al limpiar archivos: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al limpiar archivos');
                });
            }
        }
    </script>
</body>
</html>
