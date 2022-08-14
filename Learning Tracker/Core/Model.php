<?php

namespace Core;

use PDO;
use App\Config;

/**
 * Base model
 *
 * PHP version 7.0
 */
abstract class Model {

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
    
    public static function getBannerDetails($course_id, $active_tab, $position = null) {

        $banners = array();
        try {
            // this query will give banner details 
            $db = static::getDB();
            if ($position == null) {
                $stmt = $db->prepare('SELECT `name`, `position`, `background_image_link`, `background_position`,  
                                        `min_height`, `heading`, `heading_text_color`, `body`, `body_text_color`, 
                                        `button_required`, `button_text`, `button_color`, `button_link`  
                                    FROM `page_banners`  
                                    WHERE course_id = :course_id  
                                        AND name = :name  
                                        AND `status` = :status 
                                    ORDER BY `id` ASC');
            }
            else{
                $stmt = $db->prepare('SELECT `name`, `position`, `background_image_link`, `background_position`,  
                                        `min_height`, `heading`, `heading_text_color`, `body`, `body_text_color`,  
                                        `button_required`, `button_text`, `button_color`, `button_link`  
                                    FROM `page_banners`  
                                    WHERE course_id = :course_id  
                                        AND name = :name  
                                        AND position = :position  
                                        AND `status` = :status 
                                    ORDER BY `id` ASC');
                $stmt->bindValue(':position', $position, PDO::PARAM_STR);
            }
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':name', $active_tab, PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result12 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $banners = $result12;
            
            return $banners;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
}