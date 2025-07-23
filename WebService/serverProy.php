<?php

require_once 'nusoap.php';

// crear server

$server = new nusoap_server();
// configurar WSDL
$server->configureWSDL();


// registrar los servicios
$server->register();


// funciones o servicios a registrar


$HTTP_RAW_POST_DATA = file_get_contents('php://input');
$server->service($HTTP_RAW_POST_DATA);



?>