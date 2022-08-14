<?php

namespace App\Models;

use \App\EventLoger;
use PDO;

/**
 * Community model
 *
 * PHP version 5.4
 */
class Communities extends \Core\Model {

    /**
     * Get all the posts as an associative array
     *
     * @return array
     */
    public static function getCommunityDetails($course_id) {
        
        try {
            
            $community_details = array();
            //this query will get role, user id from user to course mapping table
            $db = static::getDB();
            $stmt = $db->prepare("SELECT `social_media_platform`, `link` 
                                FROM `course_community_links` 
                                WHERE `course_id` = :course_id 
                                    AND `position` = :position
                                    AND `status` = :status
                                ORDER BY `id` ASC ");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':position', 'COMMUNITY_PAGE', PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare('SELECT `content_html` FROM `course_community_content` WHERE `course_id` = :course_id AND `status` = :status ORDER BY `id` ASC');

            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $community_details['social_links'] = $results;
            $community_details['community_contents'] = $result1;

            //eventlogging for community link
            $logDetails = array(
                "link_visited" => "community_link"
            );
            EventLoger::logEvent('Visit community link', json_encode($logDetails));

            //return an array

            return $community_details;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

}
