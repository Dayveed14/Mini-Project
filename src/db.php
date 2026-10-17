<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', '127.0.0.1'),
            env('DB_PORT', '3306'),
            env('DB_NAME', 'uniuyo_dues')
        );
        try {
            $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', '') ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $ex) {
            // Log the real reason; show a hint without exposing credentials.
            error_log('Database connection failed: ' . $ex->getMessage());
            if (PHP_SAPI !== 'cli') {
                http_response_code(503);
                header('Content-Type: text/plain; charset=utf-8');
            }
            exit("Can't connect to the database. Check DB_HOST, DB_NAME, DB_USER and DB_PASS in .env.\n"
                . (env('APP_ENV') === 'production' ? '' : 'Details: ' . $ex->getMessage() . "\n"));
        }
        $pdo->exec("SET time_zone = '+01:00'");
    }
    return $pdo;
}

/** Run a prepared statement and return it. Every query in the app goes through here. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function q_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function q_value(string $sql, array $params = []): mixed
{
    $value = q($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}
