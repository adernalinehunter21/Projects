<?php

namespace App\Models;

use PDO;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Organisations extends \Core\Model {

    /**
     * Get name of the course
     * @param type $course_id
     * @return string
     */
    public static function getBrandDetails($host) {
        try {
            // this query will give schedule dates details 
            $db = static::getDB();

            $stmt = $db->prepare("SELECT *  
                                FROM `organisation` 
                                WHERE `custom_domain` = :host 
                                    AND `status` = :status ");
            $stmt->bindValue(':host', $host, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(count($results) > 0){
                return array_pop($results);
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return null;
    }
    
    /**
     * Get facilitators for the org
     * @param type $org_id
     * @return type
     */
    public static function getFacilititators($org_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `users`.`id`,
                                CONCAT(`name`, ' ', `last_name`) as name, `email`
                            FROM `facilitator_to_org_mapping` 
                                JOIN `users` ON (`facilitator_to_org_mapping`.`facilitator_user_id` = `users`.`id`)
                            WHERE `org_id` = :org_id
                                AND `facilitator_to_org_mapping`.`status` = :status
                                AND `users`.`status` = :status ");
        $stmt->bindValue(':org_id', $org_id, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

}


