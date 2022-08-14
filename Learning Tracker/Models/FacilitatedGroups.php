<?php

namespace App\Models;

use PDO;

/**
 * facilitated model
 *
 * PHP version 5.4
 */
class FacilitatedGroups extends \Core\Model {

    /**
     * Get all the values as an associative array
     *
     * @return array called facilitated group details
     */
    public static function getFacilitatedDetails($course_id) {
        $facilitated_group_details = array();
        try {
            //this query will get role, user id from user to course mapping table
            $db = static::getDB();
            $stmt = $db->prepare('SELECT `role`, `user_id` 
                                FROM `user_to_course_mapping` 
                                WHERE `course_id` = :course_id 
                                    AND `status` = :status 
                                ORDER BY `user_id` ASC');

            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $user_details = array();
            foreach ($results as $user) {
                //this query will get id, name, profile pic link, profile from users table
                $stmt = $db->prepare('SELECT `id`, `name`, `last_name`,
                                        `profile_pic_binary`, `profile`,
                                        `linkedin_link`, `facebook_link`
                                    FROM `users` 
                                    WHERE `id` = :id 
                                    AND `status` = :status
                                    ORDER BY `id` ASC');
                $stmt->bindValue(':id', $user['user_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                array_push($user_details, array(
                    "each_users" => $result1
                ));
            }
            array_push($facilitated_group_details, array(
                "user_details" => $user_details,
                "course_mapping_details" => $results
            ));
            //return an array
            return $facilitated_group_details;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

}
