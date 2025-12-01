<?php
// setup_db.php
// Este script crea las tablas necesarias en la base de datos de Railway.
// Úsalo una vez y luego bórralo.

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Inicializando Base de Datos...</h1>";

// 1. Obtener credenciales de variables de entorno
$host   = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$user   = getenv('DB_USER');
$pass   = getenv('DB_PASS');

if (!$host) {
    die("<p style='color:red'>Error: No se detectaron las variables de entorno (DB_HOST, etc). <br>Asegúrate de haber agregado el servicio MySQL en Railway y configurado las variables en tu proyecto.</p>");
}

echo "<p>Conectando a: $host ...</p>";

try {
    // 2. Conectar
    $dsn = "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<p style='color:green'>¡Conexión exitosa!</p>";

    // 3. SQL para crear tablas
    $sql = "
    CREATE TABLE IF NOT EXISTS documents (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      date DATE NOT NULL,
      path VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;

    CREATE TABLE IF NOT EXISTS codes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      document_id INT NOT NULL,
      code VARCHAR(100) NOT NULL,
      FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
      INDEX idx_code (code),
      INDEX idx_document_id (document_id)
    ) ENGINE=InnoDB;
    ";

    // 4. Ejecutar
    $db->exec($sql);
    
    echo "<h2>¡Tablas creadas correctamente! ✅</h2>";
    echo "<p>Las tablas 'documents' y 'codes' ya existen en tu base de datos.</p>";
    echo "<p><strong>Siguiente paso:</strong> Ya puedes usar tu aplicación. Por seguridad, te recomendamos borrar este archivo del repositorio más adelante.</p>";
    echo "<a href='/'>Ir al Inicio</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error de Base de Datos</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>El servicio MySQL esté activo en Railway.</li>";
    echo "<li>Las variables de entorno (DB_HOST, DB_USER, etc.) sean correctas.</li>";
    echo "<li>No estés intentando conectar a PostgreSQL por error (este script es para MySQL).</li>";
    echo "</ul>";
}
?>
