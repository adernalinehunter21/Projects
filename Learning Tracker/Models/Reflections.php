<?php

namespace App\Models;

use PDO;
use App\Models\Subjects;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Reflections extends \Core\Model {

    /**
     * Get some values as an associative array, some as column, some as int
     *
     * @return array called module details
     */
    public static function getReflectionsOfSessions($session_id) {
        try {

            $db = static::getDB();
            $user_id = $_SESSION['user_id'];
            //this query will give reflection_topics details
            $stmt = $db->prepare('SELECT `reflection_topics`.`id`, `topic`, reflections.`id` AS reflection_id
                                FROM `reflection_topics`
                                LEFT JOIN (SELECT `id`, `topic_id` FROM `reflections` WHERE `user_id` = :user_id AND `status` = :status) AS reflections ON (`reflection_topics`.`id` = reflections.`topic_id`)
                                WHERE `session_id` = :session_id
                                AND `reflection_topics`.`status` = :status
                                ORDER BY `reflection_topics`.`id` ASC');
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $oneReflection = array();
            foreach ($results as $value) {
                if ($value['reflection_id'] === null) {
                    $value['answer_status'] = "UNANSWERED";
                } else {
                    $value['answer_status'] = "ANSWERED";
                }
                array_push($oneReflection, $value);
            }
            return $oneReflection;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getReflectionDetails($data) {
        $reflectionId = $data['reflectionId'];
        $user_id = $_SESSION['user_id'];
        try {
            $db = static::getDB();
            //this query will give reflection text from reflections table
            $stmt = $db->prepare('SELECT `reflection` FROM `reflections` WHERE `topic_id` = :reflectionId AND `user_id` = :user_id AND `status` = :status');
            $stmt->bindValue(':reflectionId', $reflectionId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) > 0) {
                $reflectionDetails = array_pop($results);
                $reflectionText = $reflectionDetails['reflection'];
            } else {
                $reflectionText = "";
            }

            return array(
                "status" => "Success",
                "reflectionDetails" => $reflectionText
            );
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    public static function updateReflectionDetails($data) {
        $user_id = $_SESSION['user_id'];
        $course_id = $_SESSION['course_id'];
        $reflectionData = $data['reflection'];
        $reflectionId = $data['reflectionId'];

        try {
            $db = static::getDB();
            //Start the transaction
            $db->beginTransaction();
            $answerStatus = "";
            //this query will give reflection text from reflections table
            $stmt = $db->prepare('SELECT `id`, `reflection` FROM `reflections` WHERE `topic_id` = :reflectionId AND `user_id` = :user_id AND `status` = :status');
            $stmt->bindValue(':reflectionId', $reflectionId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($results) == 0) {
                $answerStatus = "UNANSWERED";
                //Insert the reflection details into reflections table
                $stmt = $db->prepare("INSERT INTO `reflections` "
                        . "(`topic_id`, `user_id`, `reflection`, `status`) "
                        . "VALUES (:topic_id, :user_id, :reflection, :status) ");
                $stmt->bindValue(':topic_id', $reflectionId, PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':reflection', $reflectionData, PDO::PARAM_STR);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "message" => "Error while recording score"
                    );
                } else {
                    $answerStatus = "ANSWERED";

                    $stmt = $db->prepare("SELECT `id`,`max_score`
                                FROM `reward_point_criterias`
                                WHERE `criteria` = :criteria
                                    AND `reference_id` = :reflectionId
                                    AND `status` = :status ");

                    $stmt->bindValue(':criteria', 'SUBJECT_REFLECTION', PDO::PARAM_STR);
                    $stmt->bindValue(':reflectionId', $reflectionId, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    if (!$stmt->execute()) {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Encountered error while computing reward points"
                        );
                    }
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $score = array_pop($results);

                    $scoreCriteriaId = $score['id'];
                    $scoreValue = $score['max_score'];

                    //Insert the score for the user
                    $stmt = $db->prepare("INSERT INTO `reward_points_scored` "
                            . "(`id`, `user_id`, `criteria_id`, `score`, `status`) "
                            . "VALUES (NULL, :userId, :criteria_id, :score, :status) ");
                    $stmt->bindValue(':userId', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':criteria_id', $scoreCriteriaId, PDO::PARAM_INT);
                    $stmt->bindValue(':score', $scoreValue, PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    if (!$stmt->execute()) {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Error while recording score"
                        );
                    }
                }
            } else {
                $sql = 'UPDATE reflections
                    SET reflection = :reflection
                        ';
                $sql .= "\nWHERE topic_id = :reflectionId AND user_id = :user_id  AND status = :status";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':reflection', $reflectionData, PDO::PARAM_STR);
                $stmt->bindValue(':reflectionId', $reflectionId, PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting data"
                    );
                }
            }

            //Finally commit and response success to client
            $db->commit();

            return array(
                "status" => "Success",
                "answeredStatus" => $answerStatus
            );
        } catch (PDOException $e) {
            $db->rollBack();
            $error = $e->getMessage();

            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    public static function getSummaryDataOfUser($subject_id, $course_id, $user_id) {

        $db = static::getDB();

        $stmt = $db->prepare("SELECT `course_sessions`.`session_id`,
                                `session_index`,
                                `session_name`,
                                `name`,
                                `max_score` AS points_allocated,
                                `score` AS points_earned
                            FROM `course_sessions`
                            JOIN `course_session_to_topic_mapping` ON(
                                    `course_session_to_topic_mapping`.`topic_type` = 'SUBJECT_TOPIC'
                                    AND `course_sessions`.`session_id` = `course_session_to_topic_mapping`.`session_id`
                                )
                            JOIN `reflection_topics` ON(
                                    `reflection_topics`.`associated_to` = 'TOPIC'
                                    AND `course_session_to_topic_mapping`.`topic_id` = `reflection_topics`.`associated_id`
                                )
                            JOIN `reward_point_criterias` ON(
                                    `reward_point_criterias`.`criteria` = 'SUBJECT_REFLECTION'
                                    AND `reflection_topics`.`id` = `reward_point_criterias`.`reference_id`
                                )
                            LEFT JOIN `reward_points_scored` ON(
                                    `reward_points_scored`.`user_id` = :user_id
                                    AND `reward_points_scored`.`status` = :status
                                    AND `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                )
                            WHERE `course_sessions`.`course_id` = :course_id
                                AND `course_sessions`.`status` = :status
                                AND `course_session_to_topic_mapping`.`status` = :status
                                AND `reflection_topics`.`status` = :status
                                AND `reward_point_criterias`.`status` = :status
                            ORDER BY `course_sessions`.`session_index` ASC ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignments = array();

        $pendingAssignments = array();
        foreach ($result as $oneAssignment) {

            $session_id = $oneAssignment['session_id'];
            if (!isset($assignments[$session_id])) {
                # code...
                $assignments[$session_id] = array(
                    "session_index" => $oneAssignment['session_index'],
                    "session_name" => $oneAssignment['session_name'],
                    "points_earned" => 0,
                    "earnings" => array(),
                    "points_available" => 0,
                    "available_assignments" => array()
                );
            }

            if ($oneAssignment['points_earned'] == NULL) {

                array_push($assignments[$session_id]['available_assignments'],
                        array(
                            "Assignment" => $oneAssignment['name'],
                            "points_allocated" => $oneAssignment['points_allocated'],
                        )
                );
                $assignments[$session_id]['points_available'] += $oneAssignment['points_allocated'];

                array_push($pendingAssignments, array(
                    "session_index" => $oneAssignment['session_index'],
                    "assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated']
                ));
            } else {

                array_push($assignments[$session_id]['earnings'],
                        array(
                            "assignment" => $oneAssignment['name'],
                            "points_allocated" => $oneAssignment['points_allocated'],
                            "points_earned" => $oneAssignment['points_earned']
                        )
                );
                $assignments[$session_id]['points_earned'] += $oneAssignment['points_earned'];
            }
        }
        $dataForChart = array();
        foreach ($assignments as $assignment) {
            array_push($dataForChart, $assignment);
        }

        $stmt = $db->prepare("SELECT `name`,
                                    `max_score` AS points_allocated,
                                    `score` AS points_earned
                                FROM `reflection_topics`
                                JOIN `reward_point_criterias` ON(
                                        `reward_point_criterias`.`criteria` = 'SUBJECT_REFLECTION'
                                        AND `reflection_topics`.`id` = `reward_point_criterias`.`reference_id`
                                    )
                                LEFT JOIN `reward_points_scored` ON(
                                        `reward_points_scored`.`user_id` = :user_id
                                        AND `reward_points_scored`.`status` = :status
                                        AND `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                    )
                                WHERE `reflection_topics`.`subject_id` = :subject_id
                                    AND `reflection_topics`.`associated_to` != 'TOPIC'
                                    AND `reflection_topics`.`status` = :status
                                    AND `reward_point_criterias`.`status` = :status ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $program_reflections = array(
            "session_index" => "PROGRAM",
            "session_name" => "MODULE AND ENTIRE_SUBJECT",
            "points_earned" => 0,
            "earnings" => array(),
            "points_available" => 0,
            "available_assignments" => array()
        );
        foreach ($result as $oneAssignment) {
            if ($oneAssignment['points_earned'] == NULL) {

                array_push($program_reflections['available_assignments'],
                        array(
                            "Assignment" => $oneAssignment['name'],
                            "points_allocated" => $oneAssignment['points_allocated'],
                        )
                );
                $program_reflections['points_available'] += $oneAssignment['points_allocated'];

                array_push($pendingAssignments, array(
                    "session_index" => "PROGRAM",
                    "assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated']
                ));
            } else {

                array_push($program_reflections['earnings'],
                        array(
                            "assignment" => $oneAssignment['name'],
                            "points_allocated" => $oneAssignment['points_allocated'],
                            "points_earned" => $oneAssignment['points_earned']
                        )
                );
                $program_reflections['points_earned'] += $oneAssignment['points_earned'];
            }
        }
        array_push($dataForChart, $program_reflections);

        return array(
            "dataForChart" => $dataForChart,
            "pendingAssignments" => $pendingAssignments
        );
    }

    public static function getSubmittedReflection($session_id) {
        // echo "string";exit;
        $db = static::getDB();

        //Get the list of submissions by the user

        $stmt = $db->prepare("SELECT `reflection_topics`.`id` AS `id`, `reflection_topics`.`name` AS `reflection_name`,
                                `reward_point_criterias`.`max_score` AS  `Points_allocated`,`reward_points_scored`.`score` AS `points_earned`,
                                `users`.`id` AS `user_id`,
                                `users`.`profile_pic_binary`,
                                `users`.`name`,
                                `last_name`
                            FROM `course_session_to_topic_mapping`
                                JOIN `reflection_topics` ON (`course_session_to_topic_mapping`.`topic_type` = 'SUBJECT_TOPIC' AND `course_session_to_topic_mapping`.`topic_id` = `reflection_topics`.`associated_id` AND `reflection_topics`.`associated_to` = 'TOPIC')
                                JOIN `reward_point_criterias` ON (`reflection_topics`.`id` = `reward_point_criterias`.`reference_id`)
                                JOIN `reward_points_scored` ON (`reward_points_scored`.`criteria_id` = `reward_point_criterias`.`id`)
                                JOIN `users` ON (`reward_points_scored`.`user_id` = `users`.`id`)

                            WHERE `course_session_to_topic_mapping`.`session_id` = :session_id
                              AND `reward_point_criterias`.`criteria` = :criteria
                              AND `reflection_topics`.`status` = :status
                              AND `reward_point_criterias`.`status` =:status
                              AND `reward_points_scored`.`status` = :status
                              AND `users`.`status` = :status ");

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':criteria', 'REFLECTION', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();

        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $participantWiseData = array();
        foreach ($submissions as $submission) {
            // var_dump($submission);continue;
            $userId = $submission['user_id'];
            if (isset($participantWiseData[$userId])) {
                array_push($participantWiseData[$userId]['reflection'], array(
                    "id" => $submission['id'],
                    "name" => $submission['reflection_name'],
                    "points_allocated" => $submission['Points_allocated'],
                    "points_earned" => $submission['points_earned']
                ));
            } else {
                $participantWiseData[$userId] = array(
                    "id" => $submission['user_id'],
                    "name" => $submission['name'] . " " . $submission['last_name'],
                    "profile_pic_binary" => $submission['profile_pic_binary'],
                    "reflection" => array(
                        0 =>
                        array(
                            "id" => $submission['id'],
                            "name" => $submission['reflection_name'],
                            "points_allocated" => $submission['Points_allocated'],
                            "points_earned" => $submission['points_earned']
                        )
                    )
                );
            }
        }
        $data = array();
        foreach ($participantWiseData as $oneParticipantData) {
            array_push($data, $oneParticipantData);
        }
        return $data;
    }

    public static function getReflectionsDetails($reflectionId, $user_id) {
        try {
            $db = static::getDB();
            //this query will give reflection text from reflections table
            $stmt = $db->prepare('SELECT `reflection` FROM `reflections` WHERE `topic_id` = :reflectionId AND `user_id` = :user_id AND `status` = :status');
            $stmt->bindValue(':reflectionId', $reflectionId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $reflectionDetails = array_pop($results);
            $reflectionText = $reflectionDetails['reflection'];
            return array(
                "status" => "Success",
                "reflectionDetails" => $reflectionText
            );
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    public static function reflectionsOfTheSubject($subject_id) {
        $db = static::getDB();
        //this query will give reflection text from reflections table
        $stmt = $db->prepare("SELECT `id`, `name`, `description`,`associated_to`, `associated_id`
                            FROM `reflection_topics`
                            WHERE `subject_id` = :subject_id
                                AND `status` = :status ");
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "message" => "error while getting data"
            );
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array();
        foreach ($result as $reflection) {
            if ($reflection['associated_to'] === "MODULE") {
                $stmt = $db->prepare("SELECT `module_id`, `module_index`, `module_name`
                                    FROM `subject_modules`
                                    WHERE `module_id` = :module_id
                                        AND `status` = :status ");
                $stmt->bindValue(':module_id', $reflection['associated_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute() || $stmt->rowCount() === 0) {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting association of reflection: " . $reflection['name']
                    );
                }
                $module = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $reflection['module_id'] = $module[0]['module_id'];
                $reflection['module_index'] = $module[0]['module_index'];
                $reflection['module_name'] = $module[0]['module_name'];
            } elseif ($reflection['associated_to'] === "TOPIC") {
                $stmt = $db->prepare("SELECT `subject_topics`.`id` AS topic_id, `name` AS topic,
                                        `subject_modules`.`module_id`, `module_index`, `module_name`
                                    FROM `subject_topics`
                                        JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                    WHERE `subject_topics`.`id` = :subject_topics
                                        AND `subject_topics`.`status` = :status
                                        AND `subject_modules`.`status` = :status ");
                $stmt->bindValue(':subject_topics', $reflection['associated_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute() || $stmt->rowCount() === 0) {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting association of reflection: " . $reflection['name']
                    );
                }
                $topic = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $reflection['module_id'] = $topic[0]['module_id'];
                $reflection['module_index'] = $topic[0]['module_index'];
                $reflection['module_name'] = $topic[0]['module_name'];
                $reflection['topic_id'] = $topic[0]['topic_id'];
                $reflection['topic'] = $topic[0]['topic'];
            }
            array_push($data, $reflection);
        }

        return array(
            "status" => "Success",
            "data" => $data
        );
    }

    public static function deleteReflection($reflection_id) {
        $db = static::getDB();
        //this query will give reflection text from reflections table
        $stmt = $db->prepare("UPDATE `reflection_topics`
                            SET `status` = :status
                            WHERE `id` = :id ");
        $stmt->bindValue(':id', $reflection_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "INACTIVE", PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "message" => "error while de-activating the reflection topic"
            );
        }

        return array(
            "status" => "Success"
        );
    }

    public static function addNewReflection($data) {
        $user_id = $_SESSION['user_id'];

        $subject_id = $data['subject_id'];
        $name = $data['name'];
        $description = $data['description'];
        $max_score = $data['max_score'];

        if ($data['associated_to'] === "Subject") {
            $resource_for = "PROGRAM";
            $associated_to = "ENTIRE_SUBJECT";
            $reference_id = 0;
        } elseif ($data['associated_to'] === "Module") {
            $resource_for = "MODULE";
            $associated_to = "MODULE";
            $reference_id = $data['associated_module_id'];
        } else {
            $resource_for = "TOPIC";
            $associated_to = "TOPIC";
            $reference_id = $data['associated_topic_id'];
        }

        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();

        //Create new Reflection topic
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO `reflection_topics`
                                (`id`, `name`, `description`, `subject_id`, `associated_to`,
                                `associated_id`, `facilitator_user_id`, `created_time`, `status`)
                            VALUES
                                (
                                null,
                                :name,
                                :description,
                                :subject_id,
                                :associated_to,
                                :associated_id,
                                :facilitator_user_id,
                                :created_time,
                                :status
                                )");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':associated_to', $associated_to, PDO::PARAM_STR);
        $stmt->bindValue(':associated_id', $reference_id, PDO::PARAM_INT);
        $stmt->bindValue(':facilitator_user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':created_time', $now, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "message" => "error while adding the new reflection topic"
            );
        }
        $reflection_id = $db->lastInsertId();

        //Add reward point criteria
        $stmt = $db->prepare("INSERT INTO `reward_point_criterias`
                                (`id`, `course_id`, `criteria`, `reference_id`, `max_score`, `status`)
                            VALUES
                                (
                                null,
                                0,
                                :criteria,
                                :reference_id,
                                :max_score,
                                :status
                                ) ");
        $stmt->bindValue(':criteria', "SUBJECT_REFLECTION", PDO::PARAM_STR);
        $stmt->bindValue(':reference_id', $reflection_id, PDO::PARAM_INT);
        $stmt->bindValue(':max_score', $max_score, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            //Revert the transaction
            $db->rollBack();
            return array(
                "status" => "Error",
                "message" => "error while adding reward criteria for the reflection topic"
            );
        }
        $db->commit();
        return array(
            "status" => "Success"
        );
    }

    public static function getSessionReflectionsOfTheUser($user_id, $subject_id, $session_id = null) {

        $db = static::getDB();

        if ($session_id === null) {
            $stmt = $db->prepare("SELECT  `reflection_topics`.`id`, `name`, `description`, reflection.id AS submission_id, `reflection`
                                FROM `reflection_topics`
                                    LEFT JOIN (SELECT `id`,`topic_id`,`reflection`
                                               FROM `reflections`
                                               WHERE `user_id` = :user_id
                                               AND `status` = :status ) AS reflection ON (`reflection_topics`.`id` = reflection.topic_id)
                                WHERE `subject_id` = :subject_id
                                    AND `associated_to` != 'TOPIC'
                                    AND `status` = :status ");
        } else {
            $stmt = $db->prepare("SELECT `reflection_topics`.`id`, `name`, `description`, reflection.id AS submission_id, `reflection`
                                FROM `course_session_to_topic_mapping`
                                    JOIN `reflection_topics` ON (`course_session_to_topic_mapping`.`topic_id` = `reflection_topics`.`associated_id`)
                                    LEFT JOIN (SELECT `id`,`topic_id`,`reflection`
                                        FROM `reflections`
                                        WHERE `user_id` = :user_id
                                            AND `status` = :status ) AS reflection ON (`reflection_topics`.`id` = reflection.topic_id)
                                WHERE `course_session_to_topic_mapping`.`session_id` = :session_id
                                    AND `reflection_topics`.`subject_id` = :subject_id
                                    AND `topic_type` = 'SUBJECT_TOPIC'
                                    AND `reflection_topics`.`associated_to` = 'TOPIC'
                                    AND `course_session_to_topic_mapping`.`status` = :status
                                    AND `reflection_topics`.`status` = :status ");

            $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => ""
            );
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array(
            "status" => "Success",
            "data" => $data
        );
    }

    public static function getModuleRefelectionsOfTheUser($user_id, $module_id){
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `reflection_topics`.`id`, `reflection_topics`.`name`, `description`, reflection.id AS submission_id, `reflection`
                            FROM `subject_topics`
                                JOIN `reflection_topics` ON (`subject_topics`.`id` = `reflection_topics`.`associated_id` AND `reflection_topics`.`associated_to` = 'TOPIC')
                                LEFT JOIN (SELECT `id`,`topic_id`,`reflection`
                                           FROM `reflections`
                                           WHERE `user_id` = :user_id
                                           AND `status` = :status ) AS reflection ON (`reflection_topics`.`id` = reflection.topic_id)
                            WHERE `module_id` = :module_id
                                AND `subject_topics`.`status` = :status
                                AND `reflection_topics`.`status` = :status ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => ""
            );
        }
        $topic_mapped_reflections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT  `reflection_topics`.`id`, `name`, `description`, reflection.id AS submission_id, `reflection`
                            FROM `reflection_topics`
                                LEFT JOIN (SELECT `id`,`topic_id`,`reflection`
                                           FROM `reflections`
                                           WHERE `user_id` = :user_id
                                           AND `status` = :status ) AS reflection ON (`reflection_topics`.`id` = reflection.topic_id)
                            WHERE `associated_to` = 'MODULE'
                                    AND `associated_id` = :module_id
                                AND `status` = :status ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => ""
            );
        }
        $module_mapped_reflections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_merge($topic_mapped_reflections, $module_mapped_reflections);

        return array(
            "status" => "Success",
            "data" => $data
        );
    }

    /**
     * Get the reward points of the user for a chart in the learner's dashboard
     * @param type $subject_id
     * @param type $course_id
     * @param type $user_id
     * @return type
     */
    public static function getRewardPointsDataOfTheUser($subject_id, $course_id, $user_id) {

        $db = static::getDB();

        $stmt = $db->prepare("SELECT SUM(`reward_point_criterias`.`max_score`) AS pointsAvailable,
                                SUM(`reward_points_scored`.`score`) AS pointsScored
                            FROM
                                `reflection_topics`
                            JOIN `reward_point_criterias` ON(
                                    `reflection_topics`.`id` = `reward_point_criterias`.`reference_id`
                                    AND `reward_point_criterias`.`criteria` = 'SUBJECT_REFLECTION'
                                )
                            LEFT JOIN `reward_points_scored` ON
                                (
                                    `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                    AND `reward_points_scored`.`user_id` = :user_id
                                    AND `reward_points_scored`.`status` = :status
                                )
                            WHERE
                                `reflection_topics`.`subject_id` = :subject_id
                                AND `reflection_topics`.`status` = :status
                                AND `reward_point_criterias`.`status` = :status ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reflection_data = array_pop($result);
        return $reflection_data;
    }

}
