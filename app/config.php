<?php

class App {

    //Version
    private static $version = '1.0.0';
    //Parametros Zona Horaria
    private static $timezone = 'America/Bogota';

    /**
     * Metodo constructor de la clase App().
     */
    public function __construct() {
        die('Init function is not allowed');
    }

    public static function fecha($format) {
        $tz = self::$timezone;
        $timestamp = time();
        $dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
        //$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
        return $dt->format($format);
    }

    public static function getParam($param) {
        if (isset(self::$$param)) {
            return self::$$param;
        } else {
            return 'Parametro No encontrado';
        }
    }

}

?>
