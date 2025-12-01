<?php
// migrate_uploads.php
// Este script sube todos los PDFs de la carpeta local 'uploads' a la base de datos de Railway

set_time_limit(0); // Sin límite de tiempo
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Migración de PDFs Locales a Railway</h1>";

// Configuración de Base de Datos
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'sql200.infinityfree.com';
$dbname = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'if0_39064130_buscador';
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'if0_39064130';
$pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: 'POQ2ODdvhG';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<p style='color:green'>✅ Conectado a la base de datos</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>❌ Error de conexión: " . $e->getMessage() . "</p>");
}

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    die("<p style='color:red'>❌ No se encuentra la carpeta uploads</p>");
}

$files = glob($uploadsDir . '/*.pdf');
echo "<p>📁 Encontrados <strong>" . count($files) . "</strong> archivos PDF</p>";

if (count($files) === 0) {
    die("<p>No hay archivos para migrar.</p>");
}

echo "<hr><h2>Procesando archivos...</h2>";

$success = 0;
$errors = 0;

foreach ($files as $filepath) {
    $filename = basename($filepath);

    // Limpiar nombre: quitar timestamp inicial
    $cleanName = preg_replace('/^\d+_/', '', $filename);
    $nameWithoutExt = pathinfo($cleanName, PATHINFO_FILENAME);

    // Intentar extraer códigos del nombre (números largos)
    preg_match_all('/\b\d{10,}\S*/', $nameWithoutExt, $matches);
    $codes = $matches[0];

    // Fecha: usar la de modificación del archivo
    $date = date('Y-m-d', filemtime($filepath));

    echo "<div style='border:1px solid #ccc; padding:10px; margin:5px;'>";
    echo "<strong>$cleanName</strong><br>";
    echo "Códigos detectados: " . (count($codes) > 0 ? implode(', ', $codes) : 'ninguno') . "<br>";

    try {
        // Insertar documento
        $stmt = $db->prepare('INSERT INTO documents (name, date, path) VALUES (?, ?, ?)');
        $stmt->execute([$nameWithoutExt, $date, $filename]);
        $docId = $db->lastInsertId();

        // Insertar códigos
        if (count($codes) > 0) {
            $insCode = $db->prepare('INSERT INTO codes (document_id, code) VALUES (?, ?)');
            foreach (array_unique($codes) as $code) {
                $insCode->execute([$docId, $code]);
            }
        }

        echo "<span style='color:green'>✅ Migrado correctamente (ID: $docId)</span>";
        $success++;

    } catch (PDOException $e) {
        echo "<span style='color:red'>❌ Error: " . $e->getMessage() . "</span>";
        $errors++;
    }

    echo "</div>";
    flush();
}

echo "<hr>";
echo "<h2>Resumen</h2>";
echo "<p>✅ Exitosos: <strong>$success</strong></p>";
echo "<p>❌ Errores: <strong>$errors</strong></p>";
echo "<p><a href='/'>Ir al inicio</a></p>";
?>