<?php

class Database {

    private static $dbName = 'na';
    private static $dbHost = 'localhost';
    private static $dbUsername = 'root';
    private static $dbUserPassword = '';
    private static $cont = null;

    public function __construct() {
        die('Init function is not allowed');
    }

    public static function connect() {

        self::$dbName = 'pruebaempresa';
        self::$dbHost = 'localhost';
        self::$dbUsername = 'root';
        self::$dbUserPassword = '';

        // One connection through whole application
        if (null == self::$cont) {
            try {
                self::$cont = new PDO("mysql:host=" . self::$dbHost . ";" . "dbname=" . self::$dbName, self::$dbUsername, self::$dbUserPassword);
            } catch (PDOException $e) {
                die("*1* Error al intentar conectarse a la base de datos : " . $e->getMessage());
            }
        }
        return self::$cont;
    }

    public static function disconnect() {
        self::$cont = null;
    }

}

?>
