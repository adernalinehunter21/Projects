<?php

namespace App\Models;

use PDO;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Subjects extends \Core\Model {

    /**
     * Get id and the name of the subjects of the facilitator
     * @param type $facilitator_user_id
     * @return array(subject_id, subject_name)
     */
    public static function getSubjectsOfTheFacilitator($facilitator_user_id) {
        try {
            // this query will give schedule dates details 
            $db = static::getDB();

            $stmt = $db->prepare("SELECT `subject_id`, `subject`, `short_name`, `version`
                                FROM `facilitator_to_subject_mapping` 
                                    JOIN `subjects` ON (`facilitator_to_subject_mapping`.`subject_id` = `subjects`.`id`)
                                WHERE `facilitator_user_id` = :facilitator_user_id
                                    AND `facilitator_to_subject_mapping`.`status` = :status
                                    AND `subjects`.`status` = :status ");
            $stmt->bindValue(':facilitator_user_id', $facilitator_user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
            
        } catch (PDOException $e) {
            return null;
        }

    }
    
    
    /**
     * Check if the given Subject ID is mapped to given user id of the Facilitator 
     * @param type $subject_id
     * @param type $facilitator_id
     * @return boolean
     */
    public static function isSubjectMappedToFacilitator($subject_id, $facilitator_id) {
        try {
            // this query will give schedule dates details 
            $db = static::getDB();

            $stmt = $db->prepare("SELECT * 
                                FROM `facilitator_to_subject_mapping` 
                                WHERE `facilitator_user_id` = :user_id
                                    AND `subject_id` = :subject_id
                                    AND `status` = :status ");
            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $facilitator_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(count($results) > 0){
                return true;
            }else{
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Check if the given Module ID is mapped to subject of the given Facilitator id
     * @param type $module_id
     * @param type $facilitator_id
     * @return boolean
     */
    public static function isModuleMappedToFacilitatorSubject($module_id, $facilitator_id) {
        try {
            // this query will give schedule dates details 
            $db = static::getDB();

            $stmt = $db->prepare("SELECT * 
                                FROM `subject_modules` 
                                    JOIN `facilitator_to_subject_mapping` ON (`subject_modules`.`subject_id` = `facilitator_to_subject_mapping`.`subject_id`)
                                WHERE `module_id` = :module_id
                                    AND `facilitator_to_subject_mapping`.`facilitator_user_id` = :user_id
                                    AND `subject_modules`.`status` = :status
                                    AND `facilitator_to_subject_mapping`.`status` = :status ");
            $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $facilitator_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(count($results) > 0){
                return true;
            }else{
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
}


