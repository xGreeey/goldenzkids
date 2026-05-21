<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

date_default_timezone_set('Asia/Manila');

require_once APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

$servername = $_ENV['DB_HOST'];
$username   = $_ENV['DB_USER'];
$password   = $_ENV['DB_PASS'];
$dbname     = $_ENV['DB_NAME'];

$master_key  = $_ENV['APP_MASTER_KEY'];
$cipher_algo = 'aes-256-cbc';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $servername, $dbname);

try {
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}
<<<<<<< HEAD

$conn->set_charset('utf8mb4');
$GLOBALS['conn'] = $conn;
=======
>>>>>>> eed8e9d3e77bdacb37e57b3a5a0992d3efd5a7dd
