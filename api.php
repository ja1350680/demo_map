<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$host = '127.0.0.1';
$port = '5432';
$dbname = 'mapas_db';
$user = 'postgres';
$password = 'TU_CONTRASEÑA_AQUI';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['lat']) || !isset($input['lng'])) {
            echo json_encode(['error' => 'Datos incompletos']);
            exit;
        }

        $nombre = $input['nombre'] ?? 'Nuevo Punto';
        $desc = $input['descripcion'] ?? 'Sin descripción';
        $cat = $input['categoria'] ?? 'General';
        $lat = $input['lat'];
        $lng = $input['lng'];

        
        $query = "INSERT INTO lugares (nombre, descripcion, categoria, geom) 
                  VALUES (:nombre, :desc, :cat, ST_SetSRID(ST_MakePoint(:lng, :lat), 4326))";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':nombre' => $nombre,
            ':desc' => $desc,
            ':cat' => $cat,
            ':lng' => $lng,
            ':lat' => $lat
        ]);

        echo json_encode(['success' => true, 'mensaje' => 'Punto guardado']);
        exit;
    }

    
    $query = "SELECT id, nombre, descripcion, categoria, ST_AsGeoJSON(geom) as geo FROM lugares";
    $stmt = $pdo->query($query);
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $features = [];
    foreach ($lugares as $lugar) {
        $features[] = [
            'type' => 'Feature',
            'geometry' => json_decode($lugar['geo']),
            'properties' => [
                'id' => $lugar['id'],
                'nombre' => $lugar['nombre'],
                'descripcion' => $lugar['descripcion'],
                'categoria' => $lugar['categoria']
            ]
        ];
    }

    echo json_encode(['type' => 'FeatureCollection', 'features' => $features]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>