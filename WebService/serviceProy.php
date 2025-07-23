<?php

require_once 'nusoap.php';

// URL de soap

$wsdl = 'http://localhost/DS7-Final/WebService/serviceProy.php?wsdl';  // avisar que cualquier cosa cambiar la ruta de wsdl

$client = new nusoap_client($wsdl, true);


// verificar errores


$err = $client->getError();
if ($err) {
    echo 'Constructor error de SOAP: ' . $err;
    exit();
}


// llamada de funciones 



//verificar errores de llamada
if ($client->fault) {
    echo "Fallo en llamada SOAP:";
    print_r($result);
} else {
    $err = $client->getError();
    if ($err) {
        echo "Error: $err";
    } else {
        echo "Resultado del IMC: $result";
    }
}

?>