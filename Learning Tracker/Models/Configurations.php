<?php

namespace App\Models;

use PDO;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Configurations extends \Core\Model {

    /**
     * Get name of the course
     * @param type $course_id
     * @return string
     */
    public static function getCourseConfigurations($course_id, $configuration_group) {

        $db = static::getDB();

        $stmt = $db->prepare("SELECT  `course_configuration_groups`.`id` AS config_group_id,
                                `course_configurations`.`id` AS config_id,
                                `parameter`,
                                `value`
                            FROM `course_configuration_groups`
                                JOIN `course_configurations` ON (`course_configuration_groups`.`id` = `course_configurations`.`group_id`)
                            WHERE `configuration_group` = :configuration_group
                                AND `course_configurations`.`course_id` = :course_id
                                AND `course_configuration_groups`.`status` = :status
                                AND `course_configurations`.`status` = :status ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':configuration_group', $configuration_group, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;

    }
     public static function getConfigurations($course_id, $configuration_group_id) {

        $db = static::getDB();

        $stmt = $db->prepare("SELECT  `parameter`,
                                `value`
                            FROM `course_configurations`
                            WHERE `course_configurations`.`group_id` = :configuration_group_id
                                AND `course_configurations`.`course_id` = :course_id
                                AND `course_configurations`.`status` = :status ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':configuration_group_id', $configuration_group_id, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;

    }

    public static function extractSubjectAndBodyTemplates($Configurations) {
        $subject_template = '';
        $email_body_template = '';
        if ($Configurations && count($Configurations) > 0) {

            foreach ($Configurations as $Configuration) {

                if ($Configuration['parameter'] === "subject") {
                    $subject_template = $Configuration['value'];
                } elseif ($Configuration['parameter'] === "message_body") {
                    $email_body_template = $Configuration['value'];
                }
            }
        }
        return [
            "subject_template" => $subject_template,
            "message_body_template" => $email_body_template
        ];
    }



}
