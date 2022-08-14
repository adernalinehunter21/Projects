<?php

namespace App\Models;

use PDO;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Courses extends \Core\Model {

    /**
     * Get name of the course
     * @param type $course_id
     * @return string
     */
    public static function getCourseName($course_id) {
        try {

            $db = static::getDB();

            $stmt = $db->prepare("SELECT `course_name` FROM `courses` WHERE `course_id` = :course_id AND `status` = :status ");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return array_pop($results);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return "";
    }

    public static function getCourseSubjectLogoAndWatermark($course_id) {
        try {

            $db = static::getDB();

            $stmt = $db->prepare("SELECT `subjects`.`logo`, `watermark_pdf`
                                FROM `courses`
                                    JOIN `subjects` ON (`courses`.`subject_id` = `subjects`.`id`)
                                WHERE `course_id` = :course_id
                                    AND `courses`.`status` = :status ");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_pop($results);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return "";
    }

    /**
     * Check if the given course ID mapped to given user id of the Facilitator
     * @param type $course_id
     * @param type $facilitator_id
     * @return boolean
     */
    public static function isCourseMappedToFacilitator($course_id, $facilitator_id) {
        try {

            $db = static::getDB();

            $stmt = $db->prepare("SELECT *
                                FROM `user_to_course_mapping`
                                WHERE `user_id` = :user_id
                                    AND `course_id` = :course_id
                                    AND `role` = :role
                                    AND `status` = :status ");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $facilitator_id, PDO::PARAM_INT);
            $stmt->bindValue(':role', 'FACILITATOR', PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) > 0) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function isLearnerMappedToCourse($course_id, $user_id) {

        $db = static::getDB();

        $stmt = $db->prepare("SELECT *
                            FROM `user_to_course_mapping`
                            WHERE `user_id` = :user_id
                                AND `course_id` = :course_id
                                AND `role` = :role
                                AND `status` = :status ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'PARTICIPANT', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($results) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the list of programs of the given subject and Facilitator
     * @param type $subject_id
     * @param type $facilitator_id
     * @return Array of programs
     */
    public static function getSubjectCoursesOfFacilitator($subject_id, $facilitator_id) {
        try {

            $db = static::getDB();

            $stmt = $db->prepare("SELECT `user_to_course_mapping`.`course_id`, `course_name`
                                FROM `user_to_course_mapping`
                                    JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                                WHERE `user_id` = :user_id
                                    AND `role` = :role
                                    AND `courses`.`subject_id` = :subject_id
                                    AND `user_to_course_mapping`.`status` = :status
                                    AND `courses`.`status` = :status ");
            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $facilitator_id, PDO::PARAM_INT);
            $stmt->bindValue(':role', 'FACILITATOR', PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function getRuningCoursesOfTheSubject($subject_id, $facilitator_id) {
        try {

            $db = static::getDB();
            $futureSessionsCourseId = array();
            $currentTime = gmdate('Y-m-d H:i:s');

            $stmt = $db->prepare("SELECT DISTINCT(`user_to_course_mapping`.`course_id`)
                                FROM `user_to_course_mapping`
                                    JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                                    JOIN `course_sessions` ON (`user_to_course_mapping`.`course_id` = `course_sessions`.`course_id`)
                                WHERE `user_id` = :user_id
                                    AND `role` = :role
                                    AND `courses`.`subject_id` = :subject_id
                                    AND `user_to_course_mapping`.`status` = :status
                                    AND `courses`.`status` = :status
                                     ");
            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $facilitator_id, PDO::PARAM_INT);
            $stmt->bindValue(':role', 'FACILITATOR', PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $course_id){
                $scheduleTime = static::getLatestSessionScheduleOfTheCourse($course_id['course_id']);
                if(strtotime($scheduleTime) > strtotime($currentTime)){
                    array_push($futureSessionsCourseId,$course_id);
                }
            }


            return $futureSessionsCourseId;
        } catch (PDOException $e) {
            return null;
        }
    }

    private static function getLatestSessionScheduleOfTheCourse($course_id){
            $db = static::getDB();
            $stmt = $db->prepare("SELECT `start_timestamp`

                                    FROM `course_sessions`
                                    JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)

                                    WHERE `course_id` = :course_id
                                    AND `course_sessions`.`status` = :status
                                    AND `course_session_schedules`.`status` = :status
                                    ORDER BY `start_timestamp` DESC LIMIT 0,1");

            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $latestSessionTimestamp = $results[0];
            return $latestSessionTimestamp['start_timestamp'];

    }

    /**
     * get the details of each course belonging to subject of the facilitator
     * Used for showing courses in Facilitator login
     * @param type $subject_id
     * @param type $facilitator_id
     * @return type
     */
    public static function getSubjectCoursesWithDetails($subject_id, $facilitator_id) {

        $db = static::getDB();

        $stmt = $db->prepare("SELECT `user_to_course_mapping`.`course_id`, `course_name`
                            FROM `user_to_course_mapping`
                                JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                            WHERE `user_id` = :user_id
                                AND `role` = :role
                                AND `courses`.`subject_id` = :subject_id
                                AND `user_to_course_mapping`.`status` = :status
                                AND `courses`.`status` = :status
                            ORDER BY `courses`.`course_id` DESC ");
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $facilitator_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'FACILITATOR', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array();
        foreach ($results as $course) {
            $session_details = static::getSessionDetailsOfTheCourse($course['course_id']);
            if ($session_details['status'] === "Error") {
                return array(
                    "status" => "Error",
                    "error" => "Encountered and error while trying to get the schedules of the course: " + $course['course_name']
                );
            }
            $session_counts = $session_details['data'];
            $facilitators = static::getFacilitatorsOfTheCourse($course['course_id']);

            $social_media_links = static::getSocialMediaLinksOfTheCourse($course['course_id']);

            array_push($data, array(
                "id" => $course['course_id'],
                "name" => $course['course_name'],
                "sessions" => $session_counts,
                "facilitators" => $facilitators,
                "social_media_links" => $social_media_links
            ));
        }
        return array(
            "status" => "Success",
            "data" => $data
        );
    }

    private static function getSessionDetailsOfTheCourse($course_id) {

        $data = array();
        if (isset($_SESSION['user_timezone_configured'])) {
            $userTimezone = $_SESSION['user_timezone_configured'];
        } else {
            $userTimezone = "UTC";
        }
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `course_sessions`.`session_id`,
                                `session_index`,
                                `session_name`,
                                CONVERT_TZ(`start_timestamp`, :tz_utc, :user_tz) as start_timestamp,
                                `duration`,
                                `meeting_link`
                            FROM `course_sessions`
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :course_id
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `course_session_schedules`.`start_timestamp` ASC ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':tz_utc', "UTC", PDO::PARAM_STR);
        $stmt->bindValue(':user_tz', $userTimezone, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => "Encountered and error while trying to get the schedules of the course"
            );
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count_of_sessions_planned = count($results);
        if ($count_of_sessions_planned === 0) {
            $data['planned'] = 0;
            $data['completed'] = 0;
            $data['pending'] = 0;
        } else {
            $now = date('Y-m-d H:i:s');
            $count_of_sessions_completed = 0;
            foreach ($results as $session) {
                if ($session['start_timestamp'] < $now) {
                    $count_of_sessions_completed++;
                }
            }
            $count_of_sessions_pending = $count_of_sessions_planned - $count_of_sessions_completed;
            $data['planned'] = $count_of_sessions_planned;
            $data['completed'] = $count_of_sessions_completed;
            $data['pending'] = $count_of_sessions_pending;
        }
        return array(
            "status" => "Success",
            "data" => $data
        );
    }

    private static function getFacilitatorsOfTheCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `users`.`id`, CONCAT(`name`, ' ', `last_name`) AS name, `email`
                            FROM `user_to_course_mapping`
                                JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                            WHERE `course_id` = :course_id
                                AND `user_to_course_mapping`.`role` = :role
                                AND `user_to_course_mapping`.`status` = :status
                                AND `users`.`status` = :status ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'FACILITATOR', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    private static function getSocialMediaLinksOfTheCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT
                                `course_community_links`.`social_media_platform` AS platform,
                                `link`,
                                `position`,
                                `icon_classes`
                            FROM
                                `course_community_links`
                            JOIN `community_link_platforms` ON(
                                    `course_community_links`.`social_media_platform` = `community_link_platforms`.`social_media_platform`
                                    AND `community_link_platforms`.`status` = :status
                                )
                            WHERE
                                `course_id` = :course_id
                                AND `course_community_links`.`status` = :status ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    /**
     * Serve the ajax request to delete the course
     * @param type $course_id
     * @return type
     */
    public static function deleteCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("UPDATE `courses`
                            SET `status` = :status
                            WHERE `course_id` = :course_id ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'INACTIVE', PDO::PARAM_STR);

        if ($stmt->execute()) {
            return array(
                "status" => "Success"
            );
        } else {
            return array(
                "status" => "Error",
                "error" => "Encountered and error while deactivating the course"
            );
        }
    }

    /**
     * Get all the participants of the course
     * If supplied, exclude the specific user
     */
    public static function getTeamMemberList($course_id, $user_id = "") {
        try {
            // this query will give user name of active participant
            $db = static::getDB();

            $stmt = $db->prepare("SELECT `users`.`name`,`users`.`id`
                                FROM `user_to_course_mapping`
                                    JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                                WHERE `course_id` = :course_id
                                    AND `user_to_course_mapping`.`status` = :status
                                    AND `users`.`role` = :role
                                    AND users.id != :user_id
                                    AND `users`.`status` = :status
                                ORDER BY `users`.`name` ASC ");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':role', "PARTICIPANT", PDO::PARAM_STR);


            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $results;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return "";
    }

    /**
     * Create new course and map it to facilitators
     * @param type $subject_id
     * @param type $course_name
     * @param type $facilitarors
     * @param type $facilitator_org_id
     * @return type
     */
    public static function addNewCourse($subject_id, $course_name, $facilitarors, $facilitator_org_id, $socialMediaLinks) {
        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO `courses`
                                (`course_id`, `course_name`, `subject_id`, `org_id`, `status`)
                            VALUES
                                (
                                null,
                                :course_name,
                                :subject_id,
                                :org_id,
                                :status
                                ) ");
        $stmt->bindValue(':course_name', $course_name, PDO::PARAM_STR);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':org_id', $facilitator_org_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => "Encountered an error during creation of new course"
            );
        }
        $course_id = $db->lastInsertId();

        foreach ($facilitarors as $facilitaror) {
            $stmt = $db->prepare("INSERT INTO `user_to_course_mapping`
                                    (`mapping_id`, `user_id`, `course_id`, `role`, `status`)
                                VALUES
                                    (
                                    null,
                                    :user_id,
                                    :course_id,
                                    :role,
                                    :status
                                    ) ");
            $stmt->bindValue(':user_id', $facilitaror['id'], PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':role', "FACILITATOR", PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "error" => "Encountered an error while mapping course to facilitators"
                );
            }
        }

        if (count($socialMediaLinks) > 0) {
            foreach ($socialMediaLinks as $socialMedia) {
                $platform = $socialMedia['platform'];
                $link = $socialMedia['link'];
                $placement = $socialMedia['placement'];

                $stmt = $db->prepare("INSERT INTO `course_community_links`(
                                        `id`,
                                        `course_id`,
                                        `social_media_platform`,
                                        `link`,
                                        `position`,
                                        `status`
                                    )
                                    VALUES(
                                        NULL,
                                        :course_id,
                                        :platform,
                                        :link,
                                        :position,
                                        :status
                                    ) ");
                $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $stmt->bindValue(':platform', strtoupper($platform), PDO::PARAM_STR);
                $stmt->bindValue(':link', $link, PDO::PARAM_STR);
                $stmt->bindValue(':position', $placement, PDO::PARAM_STR);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "error" => "Encountered an error while mapping given $platform link to course"
                    );
                }
            }
        }

        $db->commit();

        //If facilitators are not mapped to subject, map them now
        foreach ($facilitarors as $facilitaror) {
            $stmt = $db->prepare("SELECT `id`
                                FROM `facilitator_to_subject_mapping`
                                WHERE `facilitator_user_id` = :facilitator_user_id
                                    AND `subject_id` = :subject_id
                                    AND `status` = :status ");
            $stmt->bindValue(':facilitator_user_id', $facilitaror['id'], PDO::PARAM_INT);
            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if ($stmt->execute()) {
                if ($stmt->rowCount() === 0) {
                    $stmt = $db->prepare("INSERT INTO `facilitator_to_subject_mapping`
                                        (`id`, `facilitator_user_id`, `subject_id`, `status`)
                                        VALUES
                                        (
                                        null,
                                        :facilitator_user_id,
                                        :subject_id,
                                        :status
                                        ) ");
                    $stmt->bindValue(':facilitator_user_id', $facilitaror['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->execute();
                }
            }
        }
        return array(
            "status" => "Success"
        );
    }

    public static function getUsersOfTheCourse($course_id) {
        try {

            $db = static::getDB();

            $user_id = $_SESSION['user_id'];

            $stmt = $db->prepare("SELECT  `user_to_course_mapping`.`user_id` ,
                                            `user_to_course_mapping`.`role`,
                                            `users`.`name`,
                                            `users`.`last_name`

                                            FROM `user_to_course_mapping`
                                            JOIN `users` ON (`user_to_course_mapping`.`user_id` =`users`.`id`)

                                            WHERE `user_to_course_mapping`.`course_id` = :course_id
                                            AND `users`.`id` != :user_id
                                            AND  `users`.`status` = :status
                                            AND `user_to_course_mapping`.`status` = :status");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);


            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $roleWiseData = array();
            foreach ($results as $user) {
                $role = $user['role'];
                if (!isset($roleWiseData[$role])) {
                    $roleWiseData[$role] = array();
                }

                array_push($roleWiseData[$role], $user);
            }

            return $roleWiseData;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function getFirstScheduleOfTheCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `course_sessions`.`session_id`,
                                `session_index`,
                                `session_name`,
                                `date`,
                                TIME_FORMAT(`start_timestamp`, '%H %i') as time,
                                `start_timestamp`,
                                `duration`,
                                `meeting_link`
                            FROM `course_sessions`
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :course_id
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `session_index` ASC
                            LIMIT 0, 1 ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($results) > 0) {
            return array_pop($results);
        } else {
            return [];
        }
    }

    public static function getOrgDetails($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `course_name`,
                                `name`,
                                `logo_link`,
                                `short_logo_link`,
                                `website_link`,
                                `custom_domain`,
                                `notification_email_id`
                            FROM `courses`
                                JOIN `organisation` ON (`courses`.`org_id` = `organisation`.`id`)
                            WHERE `course_id` = :course_id
                                AND `courses`.`status` = :status
                                AND `organisation`.`status` = :status ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($results) > 0) {
            return array_pop($results);
        } else {
            return [];
        }
    }

    public static function getLearners($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `users`.`id`,
                                `name`,
                                `last_name`,
                                `email`,
                                `is_active`,
                                `profile_pic_binary`
                            FROM `user_to_course_mapping`
                                JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                            WHERE `user_to_course_mapping`.`course_id` = :course_id
                                AND `user_to_course_mapping`.`role` = 'PARTICIPANT'
                                AND `user_to_course_mapping`.`status` = :status
                                AND `users`.`status` = :status
                            ORDER BY `name` ASC ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $active_users = [];
        $inactive_users = [];
        if (count($results) > 0) {
            foreach ($results as $learner) {
                if ($learner['is_active'] == 1) {
                    unset($learner['is_active']);
                    array_push($active_users, $learner);
                } else {
                    unset($learner['is_active']);
                    unset($learner['profile_pic_binary']);
                    array_push($inactive_users, $learner);
                }
            }
        }
        return array(
            "active" => $active_users,
            "inactive" => $inactive_users
        );
    }

}
