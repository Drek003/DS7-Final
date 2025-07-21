<?php
// xml_generator.php
// Genera un XML y una imagen PNG a partir de los datos de la venta y retorna la ruta del ZIP generado

function generateXMLAndImageZip($data, $items) {
    $empresa = 'Mi Empresa S.A. de C.V.';
    $xmlDir = __DIR__;
    $zipName = 'factura_' . $data['invoice_number'] . '_' . time() . '.zip';
    $xmlFile = 'factura_' . $data['invoice_number'] . '.xml';
    $imgFile = 'factura_' . $data['invoice_number'] . '.png';
    $zipPath = $xmlDir . '/' . $zipName;
    $xmlPath = $xmlDir . '/' . $xmlFile;
    $imgPath = $xmlDir . '/' . $imgFile;

    // Crear XML
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    $root = $xml->createElement('Factura');
    $xml->appendChild($root);

    $empresaNode = $xml->createElement('Empresa', $empresa);
    $root->appendChild($empresaNode);

    $clienteNode = $xml->createElement('Cliente');
    $clienteNode->appendChild($xml->createElement('Nombre', $data['customer_name']));
    $clienteNode->appendChild($xml->createElement('Apellido', $data['customer_lastname'] ?? ''));
    $clienteNode->appendChild($xml->createElement('Correo', $data['customer_email']));
    $clienteNode->appendChild($xml->createElement('Numero', $data['customer_phone']));
    $clienteNode->appendChild($xml->createElement('Direccion', $data['customer_address'] ?? ''));
    $root->appendChild($clienteNode);

    $articulosNode = $xml->createElement('Articulos');
    foreach ($items as $item) {
        $articulo = $xml->createElement('Articulo');
        $articulo->appendChild($xml->createElement('Nombre', $item['product_name']));
        $articulo->appendChild($xml->createElement('Precio', $item['price_at_time']));
        $articulo->appendChild($xml->createElement('Cantidad', $item['quantity']));
        $articulo->appendChild($xml->createElement('Subtotal', $item['subtotal']));
        $articulosNode->appendChild($articulo);
    }
    $root->appendChild($articulosNode);

    $root->appendChild($xml->createElement('Total', $data['total']));

    $xml->save($xmlPath);

    // Crear imagen PNG con GD
    $width = 600;
    $height = 60 + (count($items) * 30) + 120;
    $im = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, $width, $height, $white);
    $y = 20;
    $font = __DIR__ . '/arial.ttf'; // Debe existir la fuente o usar built-in
    $fontSize = 12;
    
    // Empresa
    imagestring($im, 5, 20, $y, 'Empresa: ' . $empresa, $black);
    $y += 25;
    imagestring($im, 4, 20, $y, 'Cliente: ' . $data['customer_name'] . ' ' . ($data['customer_lastname'] ?? ''), $black);
    $y += 20;
    imagestring($im, 4, 20, $y, 'Correo: ' . $data['customer_email'], $black);
    $y += 20;
    imagestring($im, 4, 20, $y, 'Numero: ' . $data['customer_phone'], $black);
    $y += 20;
    imagestring($im, 4, 20, $y, 'Direccion: ' . ($data['customer_address'] ?? ''), $black);
    $y += 30;
    imagestring($im, 5, 20, $y, 'Articulos:', $black);
    $y += 20;
    foreach ($items as $item) {
        $line = $item['product_name'] . ' | Precio: $' . $item['price_at_time'] . ' | Cant: ' . $item['quantity'] . ' | Subtotal: $' . $item['subtotal'];
        imagestring($im, 3, 40, $y, $line, $black);
        $y += 20;
    }
    $y += 10;
    imagestring($im, 5, 20, $y, 'Total: $' . $data['total'], $black);
    imagepng($im, $imgPath);
    imagedestroy($im);

    // Crear ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($xmlPath, $xmlFile);
        $zip->addFile($imgPath, $imgFile);
        $zip->close();
        // Limpiar archivos temporales
        unlink($xmlPath);
        unlink($imgPath);
        return $zipName;
    } else {
        return false;
    }
}
