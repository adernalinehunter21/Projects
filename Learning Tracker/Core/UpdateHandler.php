<?php

use \App\Config;

/**
 * Base model
 *
 * PHP version 7.0
 */
abstract class UpdateHandlers {

    /**
     * Get the PDO database connection
     *
     * @return mixed
     */
    protected static function getDB() {
        static $db = null;

        if ($db === null) {
            if (PHP_OS == 'Linux') {
                $config_base = "/etc/config";
            } elseif (PHP_OS == 'Darwin') {
                $config_base = "/etc/config";
            } elseif (PHP_OS == 'Windows' || PHP_OS == 'WINNT') {
                $config_base = "C:/config";
            } else {
                return null;
            }

            $configFileName = $config_base . "/" . $_SERVER['SERVER_NAME'];
            $config_file_json = file_get_contents($configFileName . '.json');
            $config = json_decode($config_file_json, true);

            $dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=utf8';
            $db = new PDO($dsn, $config['db']['user'], $config['db']['password']);

            // Throw an Exception when an error occurs
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);            
        }
        return $db;
    }

}
