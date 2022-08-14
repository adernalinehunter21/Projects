<?php

namespace App\Models;

use PDO;
use App\s3;

/**
 * Presentations model
 *
 * PHP version 5.4
 */
class Presentations extends \Core\Model {

    /**
     * Get all the posts as an associative array
     *
     * @return array
     */
    public static function getNextPresentationSlideOfSession($subjectId, $subjectVersion, $sessionId, $topicId, $currentImageOrder, $direction) {
        $orderDirection = ($direction == "Next")? "ASC": "DESC";
        if($orderDirection == "ASC"){
            $orderBasedFilterCriteria = "  `subject_topic_presentation_images`.`order` > $currentImageOrder ";
            $topicBasedFilterCriteria = " `course_session_to_topic_mapping`.`topic_id` > $topicId ";
        }else{
            $orderBasedFilterCriteria = " `subject_topic_presentation_images`.`order` < $currentImageOrder ";
            $topicBasedFilterCriteria = " `course_session_to_topic_mapping`.`topic_id` < $topicId ";
        }
        try {
            // this query will give session_name and session_id 
            $db = static::getDB();

            $stmt = $db->prepare("SELECT
                                    `course_session_to_topic_mapping`.`topic_id`,
                                    `subject_topics`.`module_id`,
                                    `module_index`,
                                    `name` AS topic_name,
                                    `objective`,
                                    `images_bucket`,
                                    `subject_topic_presentation_images`.`order`,
                                    `image_link`,
                                    `note`
                                FROM
                                    `course_session_to_topic_mapping`
                                JOIN `subject_topics` ON(
                                        `course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`
                                    )
                                JOIN `subject_modules` ON(
                                        `subject_topics`.`module_id` = `subject_modules`.`module_id`
                                    )
                                JOIN `subject_topic_presentation_images` ON(
                                        `course_session_to_topic_mapping`.`topic_id` = `subject_topic_presentation_images`.`topic_id`
                                    )
                                LEFT JOIN `learner_slide_notes` ON(`subject_topic_presentation_images`.`id`=`learner_slide_notes`.`slide_id`)
                                WHERE
                                    `course_session_to_topic_mapping`.`session_id` = :session_id 
                                    AND `subject_modules`.`subject_id` = :subject_id 
                                    AND `course_session_to_topic_mapping`.`topic_id` = :topic_id 
                                    AND $orderBasedFilterCriteria 
                                    AND `course_session_to_topic_mapping`.`status` = :status 
                                    AND `subject_topics`.`status` = :status 
                                    AND `subject_topic_presentation_images`.`status` = :status
                                ORDER BY
                                    `subject_topic_presentation_images`.`order` $orderDirection
                                LIMIT 0, 1");
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
            $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
            $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) == 0) {
                $stmt = $db->prepare("SELECT `course_session_to_topic_mapping`.`topic_id`, 
                                        `subject_topics`.`module_id`, `module_index`,
                                        `name` AS topic_name, `objective`, `images_bucket`, 
                                        `subject_topic_presentation_images`.`order`, `image_link` ,
                                        `note`
                                    FROM `course_session_to_topic_mapping` 
                                        JOIN `subject_topics` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`)
                                        JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                        JOIN `subject_topic_presentation_images` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_topic_presentation_images`.`topic_id`)
                                        LEFT JOIN `learner_slide_notes` ON(`subject_topic_presentation_images`.`id`=`learner_slide_notes`.`slide_id`)
                                    WHERE `course_session_to_topic_mapping`.`session_id` = :session_id
                                        AND `subject_modules`.`subject_id` = :subject_id
                                        AND $topicBasedFilterCriteria
                                        AND `course_session_to_topic_mapping`.`status` = :status
                                        AND `subject_topics`.`status` = :status
                                        AND `subject_topic_presentation_images`.`status` = :status
                                    ORDER BY `subject_topics`.`id` $orderDirection, `subject_topic_presentation_images`.`order` $orderDirection LIMIT 0,1 ");
                $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
                $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($results) == 0) {
                    return array(
                        "status" => "Error",
                        "message" => "There seem to be no more slides in $direction direction. Please raise a support request to Technical Support Team"
                    );
                }
            }
            $imageDetails = $results[0];
            $subjectModuleIndex = $imageDetails['module_index'];
            $subjectTopicName = $imageDetails['topic_name'];
            $fileName = $imageDetails['image_link'];
            $filePath = s3::getSignedTempUrl('ap-southeast-1', $_SESSION['content_s3_bucket'], "Module$subjectModuleIndex/v$subjectVersion/$subjectTopicName/$fileName");

            $response = array(
                "status" => "Success",
                "data" => array(
                    "topic_id" => $imageDetails['topic_id'],
                    "topic_name" => $imageDetails['topic_name'],
                    "topic_objective" => $imageDetails['objective'],
                    "order" => $imageDetails['order'],
                    "note" => $imageDetails['note'],
                    "filePath" => $filePath
                )
            );
        } catch (PDOException $e) {

            $response = array(
                "status" => "Error",
                "message" => "Encountered and error while fetching $orderDirection slide. Please raise a support request to Technical Support Team"
            );
//            echo $e->getMessage();
        }
        return $response;
    }

    /**
     * Get all the posts as an associative array
     *
     * @return array
     */
    public static function getNextPresentationSlideOfModule($subjectId, $subjectVersion, $moduleIndex, $topicId, $currentImageOrder, $direction) {
        $orderDirection = ($direction == "Next")? "ASC": "DESC";
        if($orderDirection == "ASC"){
            $orderBasedFilterCriteria = "  `subject_topic_presentation_images`.`order` > $currentImageOrder ";
            $topicBasedFilterCriteria = " `subject_topics`.`id` > $topicId ";
        }else{
            $orderBasedFilterCriteria = " `subject_topic_presentation_images`.`order` < $currentImageOrder ";
            $topicBasedFilterCriteria = " `subject_topics`.`id` < $topicId ";
        }
        try {
            // this query will give session_name and session_id 
            $db = static::getDB();

            $stmt = $db->prepare("SELECT `topic_id`, 
                                    `subject_topics`.`module_id`, `module_index`,
                                    `name` AS topic_name, `objective`, `images_bucket`, 
                                    `subject_topic_presentation_images`.`order`, `image_link`
                                FROM `subject_modules` 
                                    JOIN `subject_topics` ON(`subject_modules`.`module_id` = `subject_topics`.`module_id`)
                                    JOIN `subject_topic_presentation_images` ON (`subject_topics`.`id` = `subject_topic_presentation_images`.`topic_id`)
                                WHERE `subject_id` = :subject_id
                                    AND `module_index` = :module_index
                                    AND `subject_topics`.`id` = :topic_id
                                    AND $orderBasedFilterCriteria
                                    AND `subject_modules`.`status` = :status
                                    AND `subject_topics`.`status` = :status
                                    AND `subject_topic_presentation_images`.`status` = :status
                                ORDER BY `subject_topic_presentation_images`.`order` $orderDirection LIMIT 0,1 ");
            
            $stmt->bindValue(':module_index', $moduleIndex, PDO::PARAM_INT);
            $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
            $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) == 0) {
                $stmt = $db->prepare("SELECT `topic_id`, 
                                    `subject_topics`.`module_id`, `module_index`,
                                    `name` AS topic_name, `objective`, `images_bucket`, 
                                    `subject_topic_presentation_images`.`order`, `image_link`
                                FROM `subject_modules` 
                                    JOIN `subject_topics` ON(`subject_modules`.`module_id` = `subject_topics`.`module_id`)
                                    JOIN `subject_topic_presentation_images` ON (`subject_topics`.`id` = `subject_topic_presentation_images`.`topic_id`)
                                WHERE `subject_id` = :subject_id
                                    AND `module_index` = :module_index
                                    AND $topicBasedFilterCriteria
                                    AND `subject_modules`.`status` = :status
                                    AND `subject_topics`.`status` = :status
                                    AND `subject_topic_presentation_images`.`status` = :status
                                ORDER BY `subject_topics`.`id` $orderDirection, `subject_topic_presentation_images`.`order` $orderDirection LIMIT 0,1 ");
                $stmt->bindValue(':module_index', $moduleIndex, PDO::PARAM_INT);
                $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($results) == 0) {
                    return array(
                        "status" => "Error",
                        "message" => "There seem to be no more slides in $direction direction. Please raise a support request to Technical Support Team"
                    );
                }
            }
            $imageDetails = $results[0];
            $subjectModuleIndex = $imageDetails['module_index'];
            $subjectTopicName = $imageDetails['topic_name'];
            $fileName = $imageDetails['image_link'];
            $filePath = s3::getSignedTempUrl('ap-southeast-1', $_SESSION['content_s3_bucket'], "Module$subjectModuleIndex/v$subjectVersion/$subjectTopicName/$fileName");

            $response = array(
                "status" => "Success",
                "data" => array(
                    "topic_id" => $imageDetails['topic_id'],
                    "topic_name" => $imageDetails['topic_name'],
                    "topic_objective" => $imageDetails['objective'],
                    "order" => $imageDetails['order'],
                    "filePath" => $filePath
                )
            );
        } catch (PDOException $e) {

            $response = array(
                "status" => "Error",
                "message" => "Encountered and error while fetching $orderDirection slide. Please raise a support request to Technical Support Team"
            );
//            echo $e->getMessage();
        }
        return $response;
    }

    public static function getSlideNumberUnderModule($module_index, $subject_id, $topic_id, $slide_order){

        //add topic order later

        $db = static::getDB();
        
        $stmt = $db->prepare("SELECT `subject_topics`.`order`, `subject_modules`.`module_id`, `subject_topics`.`id`, `subject_topics`.`status`
                            FROM `subject_topics` 
                            JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`) 
                            WHERE `subject_modules`.`module_index` = :module_index
                                AND `subject_modules`.`subject_id`= :subject_id
                                AND `subject_topics`.`id` = :topic_id
                                AND `subject_topics`.`status` = :status"
                        );

        $stmt->bindValue(':module_index', $module_index, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $module_id = $result[0]['module_id'];
        $topic_order = $result[0]['order'];

        $db = static::getDB();

        $stmt = $db->prepare("SELECT COUNT(`subject_topic_presentation_images`.`id`) AS no_of_slides_in_prev_topics
                            FROM `subject_topics` 
                            JOIN `subject_topic_presentation_images` ON (`subject_topics`.`id` = `subject_topic_presentation_images`.`topic_id`)
                            WHERE `module_id` = :module_id
                                AND `subject_topics`.`order` <= :topic_order
                                AND `subject_topics`.`id` < :topic_id
                                AND `subject_topics`.`status` = :status
                                AND `subject_topic_presentation_images`.`status` = :status
                            ");
        
        $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
        $stmt->bindValue(':topic_order', $topic_order, PDO::PARAM_INT);
        $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $count = array_pop($results);

        $slide_number = $count + $slide_order;

        return $slide_number;
    }
}
