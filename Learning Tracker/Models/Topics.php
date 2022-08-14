<?php

namespace App\Models;

use PDO;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Topics extends \Core\Model {

    /**
     * Get the list of modules to show in nav-bar drop-down
     * @param type $subject_id
     * @return type array of 
     */
    public static function getTopicsOfTheCourse($module_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `id`, `order`, `name`
                            FROM `subject_topics` 
                            WHERE `module_id` = :module_id
                                AND `status` = :status
                            ORDER BY `order` ASC ");
        $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    /**
     * Get the list of the Topics along with their Module mapped to a specific Session
     * Required to display list of topics under each session
     * Currently being used by Schedule page of the Facilitator
     * @param type $session_id
     * @return type
     */
    public static function getTopicsOfTheSession($session_id) {

        $db = static::getDB();

        $stmt = $db->prepare("SELECT
                                `topic_type`,
                                `topic_id`,
                                `general_topic`,
                                `order`
                            FROM
                                `course_session_to_topic_mapping`
                            WHERE
                                `session_id` = :session_id 
                                AND `status` = :status
                            ORDER BY
                                `order` ASC");

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $topics = [];
        $subject_topic_ids = [];
        foreach ($results as $topic) {
            if ($topic['topic_type'] === 'GENERAL_TOPIC') {
                array_push($topics, array(
                    "id" => $topic['topic_id'],
                    "type" => 'general',
                    "name" => $topic['general_topic'],
                    "module_index" => "",
                    "module_name" => "",
                    "order" => $topic['order']
                ));
            } else {
                $stmt = $db->prepare("SELECT `name`,
                                        `module_index`, 
                                        `module_name`
                                    FROM `subject_topics` 
                                        JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                    WHERE `subject_topics`.`id` = :topic_id
                                        AND `subject_topics`.`status`= :status
                                        AND `subject_modules`.`status` = :status ");

                $stmt->bindValue(':topic_id', $topic['topic_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result1) > 0) {
                    $subject_topic = array_pop($result1);
                    array_push($topics, array(
                        "id" => $topic['topic_id'],
                        "type" => 'subject_topic',
                        "name" => $subject_topic['name'],
                        "module_index" => $subject_topic['module_index'],
                        "module_name" => $subject_topic['module_name'],
                        "order" => $topic['order']
                    ));
                }
            }
        }
        return $topics;
    }

}
