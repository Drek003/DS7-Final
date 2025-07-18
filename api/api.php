<?php
// Configura tu conexión
$host = "localhost";
$user = "admin";
$pass = "1234";
$db = "ds6-2"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Conexión fallida"]));
}

if ($_GET['action'] == "get_all") {
    $response = [];

    // Obtener categorías
    $categories = [];
    $res = $conn->query("SELECT * FROM categories");
    while ($row = $res->fetch_assoc()) {
        $categories[] = $row;
    }

    // Obtener productos
    $products = [];
    $res = $conn->query("SELECT * FROM products");
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode([
        "success" => true,
        "categories" => $categories,
        "products" => $products
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Acción no válida"]);
}

$conn->close();
?>
