<?php
// import_from_backup.php
// Script para importar datos del backup SQL a la base de datos de Railway

set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Importación de Backup a Railway</h1>";

// Conexión a Railway
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST');
$dbname = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE');
$user = getenv('DB_USER') ?: getenv('MYSQLUSER');
$pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD');
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<p style='color:green'>✅ Conectado a Railway</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>");
}

// Leer el archivo SQL
$sqlFile = __DIR__ . '/buscador_backup.sql';
if (!file_exists($sqlFile)) {
    die("<p style='color:red'>❌ No se encuentra el archivo buscador_backup.sql</p>");
}

echo "<p>📄 Leyendo archivo SQL...</p>";
$sql = file_get_contents($sqlFile);

// Extraer solo los INSERT de las tablas documents y codes
echo "<p>🔍 Extrayendo datos...</p>";

// Borrar datos actuales (los que creó migrate_uploads.php)
echo "<p>🗑️ Limpiando datos actuales...</p>";
$db->exec("DELETE FROM codes");
$db->exec("DELETE FROM documents");
$db->exec("ALTER TABLE documents AUTO_INCREMENT = 1");
$db->exec("ALTER TABLE codes AUTO_INCREMENT = 1");

// Extraer INSERT de documents
preg_match_all("/INSERT INTO `?documents`? .*?;/is", $sql, $matchesDoc);
$documentsInserts = $matchesDoc[0];

// Extraer INSERT de codes
preg_match_all("/INSERT INTO `?codes`? .*?;/is", $sql, $matchesCode);
$codesInserts = $matchesCode[0];

echo "<p>📊 Encontrados:</p>";
echo "<ul>";
echo "<li>" . count($documentsInserts) . " sentencias INSERT para documents</li>";
echo "<li>" . count($codesInserts) . " sentencias INSERT para codes</li>";
echo "</ul>";

$successDocs = 0;
$errorsDocs = 0;
$successCodes = 0;
$errorsCodes = 0;

// Importar documents
echo "<hr><h2>Importando documentos...</h2>";
foreach ($documentsInserts as $insert) {
    try {
        $db->exec($insert);
        $successDocs++;
    } catch (PDOException $e) {
        echo "<p style='color:orange'>⚠️ Error en documento: " . substr($e->getMessage(), 0, 100) . "</p>";
        $errorsDocs++;
    }
}

// Importar codes
echo "<h2>Importando códigos...</h2>";
foreach ($codesInserts as $insert) {
    try {
        $db->exec($insert);
        $successCodes++;
    } catch (PDOException $e) {
        echo "<p style='color:orange'>⚠️ Error en código: " . substr($e->getMessage(), 0, 100) . "</p>";
        $errorsCodes++;
    }
}

echo "<hr>";
echo "<h2>✅ Importación Completada</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Tabla</th><th>Éxitos</th><th>Errores</th></tr>";
echo "<tr><td>Documents</td><td>$successDocs</td><td>$errorsDocs</td></tr>";
echo "<tr><td>Codes</td><td>$successCodes</td><td>$errorsCodes</td></tr>";
echo "</table>";

echo "<br><p><a href='/'>Ir a la aplicación</a></p>";
?>