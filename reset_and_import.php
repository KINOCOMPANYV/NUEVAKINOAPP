<?php
// reset_and_import.php
// Script para LIMPIAR TODO y reimportar desde backup

set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Limpieza Total e Importación desde Backup</h1>";

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

// PASO 1: BORRAR TODO
echo "<h2>🗑️ Paso 1: Limpiando base de datos...</h2>";
try {
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    $db->exec("TRUNCATE TABLE codes");
    $db->exec("TRUNCATE TABLE documents");
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "<p style='color:green'>✅ Base de datos limpia</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>❌ Error limpiando: " . $e->getMessage() . "</p>");
}

// PASO 2: Leer el backup
$sqlFile = __DIR__ . '/buscador_backup.sql';
if (!file_exists($sqlFile)) {
    die("<p style='color:red'>❌ No se encuentra buscador_backup.sql</p>");
}

echo "<h2>📄 Paso 2: Leyendo backup...</h2>";
$sql = file_get_contents($sqlFile);

// PASO 3: Ejecutar el SQL completo
echo "<h2>📥 Paso 3: Importando datos...</h2>";

// Separar comandos SQL
$commands = explode(';', $sql);
$executed = 0;
$errors = 0;

foreach ($commands as $command) {
    $command = trim($command);

    // Saltar comentarios y líneas vacías
    if (
        empty($command) ||
        strpos($command, '/*') === 0 ||
        strpos($command, '--') === 0 ||
        strpos($command, '/*!') === 0
    ) {
        continue;
    }

    // Ejecutar solo INSERTs de documents y codes
    if (
        stripos($command, 'INSERT INTO') !== false &&
        (stripos($command, '`documents`') !== false ||
            stripos($command, '`codes`') !== false ||
            stripos($command, 'documents') !== false ||
            stripos($command, 'codes') !== false)
    ) {

        try {
            $db->exec($command);
            $executed++;

            if ($executed % 100 == 0) {
                echo "<p>✓ Procesados $executed comandos...</p>";
                flush();
            }
        } catch (PDOException $e) {
            $errors++;
            if ($errors < 10) { // Solo mostrar primeros 10 errores
                echo "<p style='color:orange'>⚠️ Error: " . substr($e->getMessage(), 0, 200) . "</p>";
            }
        }
    }
}

// Contar resultados
$countDocs = $db->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$countCodes = $db->query("SELECT COUNT(*) FROM codes")->fetchColumn();

echo "<hr>";
echo "<h2>✅ Importación Completada</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse'>";
echo "<tr style='background:#f0f0f0'><th>Métrica</th><th>Valor</th></tr>";
echo "<tr><td>Comandos ejecutados</td><td><strong>$executed</strong></td></tr>";
echo "<tr><td>Errores</td><td><strong>$errors</strong></td></tr>";
echo "<tr><td>Documentos en BD</td><td><strong style='color:green'>$countDocs</strong></td></tr>";
echo "<tr><td>Códigos en BD</td><td><strong style='color:green'>$countCodes</strong></td></tr>";
echo "</table>";

echo "<br><p><strong><a href='/' style='font-size:18px'>➜ Ir a la aplicación</a></strong></p>";
echo "<p style='color:#666'>Nota: Puedes eliminar este script después de verificar que todo funciona.</p>";
?>