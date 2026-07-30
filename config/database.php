<?php
/**
 * Marist Brothers and Nyanga High School Portal
 * Database connection (PDO / MySQL)
 *
 * Update the four constants below to match your MySQL setup, then
 * import schema.sql into a database called mbn_portal (or rename it here).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'mbn_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SCHOOL_NAME', 'Marist Brothers and Nyanga High School');
define('SCHOOL_SHORT', 'MBN Portal');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed. Check config/database.php credentials. (' . $e->getMessage() . ')');
        }
    }
    return $pdo;
}
