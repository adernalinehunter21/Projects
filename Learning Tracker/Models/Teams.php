<?php

namespace App\Models;

use PDO;

/**
 * Team model
 *
 * PHP version 5.4
 */
class Teams extends \Core\Model {

    /**
     * Get all the posts as an associative array
     *
     * @return array
     */
    public static function getTeamDetails($course_id) {

        try {

            $team_details = array();
            //this query will get role, user id from user to course mapping table
            $db = static::getDB();
            $stmt = $db->prepare("SELECT `user_to_course_mapping`.`role`,
                                    `user_id`,
                                    `name`,
                                     `last_name`,
                                    `profile_pic_binary`,
                                    `profile`,
                                    `linkedin_link`, 
                                    `facebook_link`
                                FROM `user_to_course_mapping`
                                    JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                                WHERE `course_id` = :course_id
                                    AND `user_to_course_mapping`.`role` = 'FACILITATOR'
                                ORDER BY `user_id` ASC");

            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $user_details['facilitators'] = $results;

            $stmt = $db->prepare("SELECT `user_to_course_mapping`.`role`,
                                    `user_id`,
                                    `name`,
                                     `last_name`,
                                    `profile_pic_binary`,
                                    `profile`,
                                    `linkedin_link`, 
                                    `facebook_link`
                                FROM `user_to_course_mapping`
                                    JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                                WHERE `course_id` = :course_id
                                    AND `user_to_course_mapping`.`role` = 'OPERATIONAL_SUPPORT'
                                ORDER BY `user_id` ASC");

            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $user_details['operationalSupport'] = $results;

            $stmt = $db->prepare("SELECT `role`,
                                    `id` AS user_id,
                                    `name`,
                                     `last_name`,
                                    `profile_pic_binary`,
                                    `profile`,
                                    `linkedin_link`, 
                                    `facebook_link`
                                FROM `users`
                                WHERE `role` = 'TECHNICAL_SUPPORT'
                                    AND `status` = 'ACTIVE'
                                ORDER BY `id` ASC");

            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $user_details['technicalSupport'] = $results;

            return $user_details;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

}
