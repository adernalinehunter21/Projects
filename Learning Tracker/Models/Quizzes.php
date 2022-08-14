<?php
namespace App\Models;

use PDO;
use App\s3;
use App\Models\Reflections;
use App\Models\Assignments;
use App\Models\Sessions;
use App\Models\Quizzes;

class Quizzes extends \Core\Model
{
    /**
     * Get list of quiz groups for the specific learner for the specific session
     */
    public static function getSessionQuiz($session_id, $user_id, $subject_id)
    {
        $db = static ::getDB();

        $stmt = $db->prepare('SELECT `quizzes` .`id`,`name` AS `quiz_group_name`,`course_session_to_topic_mapping`.`session_id`,answers.`id` AS answer_id

            FROM `quizzes`
            JOIN `course_session_to_topic_mapping` ON ( `course_session_to_topic_mapping`.`topic_id` = `quizzes`.`associated_id`)
            LEFT JOIN (SELECT `id`, `quiz_id` FROM `quiz_answer_submissions` WHERE `user_id` = :user_id AND `status` = :status) AS answers ON (`quizzes`.`id` = answers.`quiz_id`)

            WHERE `quizzes`.`associated_to`=:associated_to
            AND `quizzes`.`status` = :status
            AND `quizzes`.`subject_id` = :subject_id
            AND `course_session_to_topic_mapping`.`status`=:status
            AND `course_session_to_topic_mapping`.`topic_type`="SUBJECT_TOPIC"
            AND `course_session_to_topic_mapping`.`session_id`= :session_id ');

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->bindValue(':associated_to', "TOPIC", PDO::PARAM_STR);
        $stmt->execute();
        $result9 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $quizGroups = array();
        foreach ($result9 as $value4)
        {
            if ($value4['answer_id'] === null)
            {
                $value4['answer_status'] = "UNANSWERED";
            }
            else
            {
                $value4['answer_status'] = "ANSWERED";
            }
            array_push($quizGroups, $value4);
        }
        return $quizGroups;
    }

    /**
     * Get the quiz associated to module and subject, so that we can show it in myActivities Page
     */

    public static function getModuleAndSubjectMappedQuiz($subject_id, $user_id)
    {
        $db = static ::getDB();

        $stmt = $db->prepare('SELECT `quizzes` .`id`,`name` AS `quiz_group_name`,answers.`id` AS answer_id

                FROM `quizzes`
                LEFT JOIN (SELECT `id`, `quiz_id` FROM `quiz_answer_submissions` WHERE `user_id` = :user_id AND `status` = :status) AS answers ON (`quizzes`.`id` = answers.`quiz_id`)

                WHERE `quizzes`.`associated_to`!=:associated_to
                AND `quizzes`.`status` = :status
                AND `quizzes`.`subject_id` = :subject_id ');

        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->bindValue(':associated_to', "TOPIC", PDO::PARAM_STR);
        $stmt->execute();
        $result9 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $quizGroups = array();
        foreach ($result9 as $value4)
        {
            if ($value4['answer_id'] === null)
            {
                $value4['answer_status'] = "UNANSWERED";
            }
            else
            {
                $value4['answer_status'] = "ANSWERED";
            }
            array_push($quizGroups, $value4);
        }
        return $quizGroups;
    }

    /**
     * Get the quiz associated to module and Topic, so that we can show it in Module section
     */

    public static function getModuleAndTopicMappedQuiz($subject_id, $user_id, $module_id)
    {
        $db = static ::getDB();

        $stmt = $db->prepare('SELECT
                    `quizzes`.`id`,
                    `quizzes`.`name` AS `quiz_group_name`,
                    answers.`id` AS answer_id

                    FROM `subject_topics`
                    JOIN `quizzes` ON (`subject_topics`.`id` = `quizzes`.`associated_id` AND `quizzes`.`associated_to` = "TOPIC")
                    LEFT JOIN(
                        SELECT
                        `id`,
                        `quiz_id`
                        FROM
                        `quiz_answer_submissions`
                        WHERE
                        `user_id` = :user_id AND `status` = :status
                    ) AS answers
                    ON (`quizzes`.`id` = answers.`quiz_id`)
                    WHERE `quizzes`.`status` = :status
                    AND `quizzes`.`subject_id` = :subject_id
                    AND `module_id` = :module_id ');

        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->bindValue(':associated_to', "ENTIRE_SUBJECT", PDO::PARAM_STR);
        $stmt->execute();
        $topic_mapped_quiz = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT `quizzes` .`id`,`name` AS `quiz_group_name`,answers.`id` AS answer_id

                        FROM `quizzes`
                        LEFT JOIN (SELECT `id`, `quiz_id` FROM `quiz_answer_submissions` WHERE `user_id` = :user_id AND `status` = :status) AS answers ON (`quizzes`.`id` = answers.`quiz_id`)

                        WHERE `quizzes`.`associated_to`=:associated_to
                        AND `associated_id` = :module_id
                        AND `quizzes`.`status` = :status
                        AND `quizzes`.`subject_id` = :subject_id ');

        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->bindValue(':associated_to', "MODULE", PDO::PARAM_STR);
        $stmt->execute();
        $module_mapped_quiz = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quizGroups = array();
        foreach (array_merge($topic_mapped_quiz, $module_mapped_quiz) as $quiz)
        {
            if ($quiz['answer_id'] === null)
            {
                $quiz['answer_status'] = "UNANSWERED";
            }
            else
            {
                $quiz['answer_status'] = "ANSWERED";
            }
            array_push($quizGroups, $quiz);
        }
        return $quizGroups;
    }

    /**
     * Get the question of a perticular quizGroup
     */

    public static function getQuizQuestions($quizId, $user_id)
    {
        $db = static ::getDB();
        $stmt = $db->prepare('SELECT `quizzes`.`name` AS `quizGroupName`,
                            `quiz_questions`.`id`,`quiz_questions`.`question`,
                            `quiz_questions`.`type`,
                            `quiz_questions`.`points`,
                            answers.`id` AS answer_submission_id

                            FROM `quiz_questions`
                            JOIN `quizzes` ON (`quizzes`.id = `quiz_questions`.`quiz_id`)
                            LEFT JOIN (SELECT `id`, `quiz_id`
                                FROM `quiz_answer_submissions`
                                WHERE `user_id` = :user_id
                                AND `status` = :status) AS answers ON (`quizzes`.`id` = answers.`quiz_id`)

                                WHERE `quiz_questions`.`status` = :status
                                AND `quizzes`.`status` = :status
                                AND `quiz_questions`.`quiz_id` = :quizId');

        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quizGroupQuestion = array();
        $quizGroupQuestion['questions'] = array();
        $quizAnswerStatus = "";
        foreach ($result as $question)
        {
            $answer_submission_id = $question['answer_submission_id'];
            $quizName = $question['quizGroupName'];
            $questionDetails = array();
            $questionDetails['id'] = $question['id'];
            $questionDetails['question'] = $question['question'];
            $questionDetails['answer_type'] = $question['type'];

            if ($question['answer_submission_id'] === NULL)
            {
                $quizAnswerStatus = "UNANSWERED";
                if ($question['type'] === 'OBJECTIVE' || $question['type'] === 'MULTIPLE_CHOICE')
                {
                    $stmt = $db->prepare("SELECT `id`,`options` AS `option_value`,`correctness`
                                                FROM `quiz_options`
                                                WHERE `quiz_options`.`quiz_question_id`=:questionId
                                                AND `quiz_options`.`status` = :status ");

                    $stmt->bindValue(':questionId', $question['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->execute();

                    $questionOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $questionDetails['quiz_options'] = $questionOptions;

                }
                elseif ($question['type'] === 'DESCRIPTIVE')
                {
                    $questionDetails['quiz_options'] = [];
                }
            }
            else
            {
                $quizAnswerStatus = "ANSWERED";
                $stmt = $db->prepare('SELECT `quiz_answers`.`id`,`answer_submission_id`,
                                                `question_id`,
                                                `descriptive_answer`,
                                                `objective_answer`
                                                ,correct_option.`id` AS correct_option_id

                                        FROM `quiz_answers`
                                        LEFT JOIN (SELECT `id`,
                                            `quiz_question_id`,
                                            `options`
                                            FROM `quiz_options`
                                            WHERE `correctness` = "CORRECT" ) AS correct_option
                                            ON (`quiz_answers`.`question_id` = `correct_option`.`quiz_question_id`)

                                            WHERE `quiz_answers`.`status` = :status
                                            AND `quiz_answers`.`question_id` = :question_id
                                            AND `quiz_answers`.`answer_submission_id` = :answer_submission_id');

                $stmt->bindValue(':answer_submission_id', $answer_submission_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question['id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $answerDetails = $result1[0];

                $descriptiveAnswer = $answerDetails['descriptive_answer'];
                $chosenOptionId = $answerDetails['objective_answer'];
                $correctOptionId = $answerDetails['correct_option_id'];

                if ($question['type'] === 'DESCRIPTIVE')
                {
                    $questionDetails['quiz_options'] = [];
                    $questionDetails['enteredDescriptiveAnswer'] = $descriptiveAnswer;

                }
                elseif ($question['type'] === 'OBJECTIVE' || $question['type'] === 'MULTIPLE_CHOICE')
                {
                    $stmt = $db->prepare("SELECT `id`,`options` AS `option_value`,`correctness`
                                                            FROM `quiz_options`
                                                            WHERE `quiz_options`.`quiz_question_id`=:questionId
                                                            AND `quiz_options`.`status` = :status ");

                    $stmt->bindValue(':questionId', $question['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->execute();
                    $questionOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $questionDetails['quiz_options'] = $questionOptions;

                    if ($question['type'] === 'OBJECTIVE')
                    {
                        $questionDetails['chosenOptionId'] = $chosenOptionId;
                        $questionDetails['correctOptionId'] = $correctOptionId;
                    }
                    elseif ($question['type'] === 'MULTIPLE_CHOICE')
                    {

                        $answer_id = $answerDetails['id'];
                        $stmt = $db->prepare("SELECT `quiz_options`.`id` as option_id,
                                                    `quiz_options`.`correctness`,
                                                    `quiz_multi_select_answers_options`.`selection_status`

                                                FROM `quiz_options`
                                                JOIN `quiz_multi_select_answers_options` ON (`quiz_options`.`id` = `quiz_multi_select_answers_options`.`option_id`)

                                                WHERE `quiz_options`.`quiz_question_id`= :questionId
                                                AND `quiz_multi_select_answers_options`.`answer_id` = :answer_id
                                                AND `quiz_options`.`status` = :status
                                                AND `quiz_multi_select_answers_options`.`status` = :status");

                        $stmt->bindValue(':questionId', $question['id'], PDO::PARAM_INT);
                        $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        $stmt->execute();
                        $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $questionDetails['correctOptionIds'] = array();
                        $questionDetails['chosenOptionIds'] = array();
                        foreach ($result2 as $oneOption)
                        {
                            if ($oneOption['correctness'] === 'CORRECT')
                            {
                                array_push($questionDetails['correctOptionIds'], $oneOption['option_id']);
                            }
                            if ($oneOption['selection_status'] === 'SELECTED')
                            {
                                array_push($questionDetails['chosenOptionIds'], $oneOption['option_id']);
                            }
                        }
                    }
                }
            }
            array_push($quizGroupQuestion['questions'], $questionDetails);

        }
        $quizGroupQuestion['quizGroupName'] = $quizName;
        $quizGroupQuestion['answerStatus'] = $quizAnswerStatus;
        return $quizGroupQuestion;

    }

    /**
     * Get data for summary page of learner
     */
    public static function getSummaryDataOfUser($subject_id, $user_id)
    {
        $db = static ::getDB();

        $stmt = $db->prepare(" SELECT
                                    `course_session_to_topic_mapping`.`session_id`,
                                    `name`,
                                    `course_sessions`.`session_index`,
                                    `course_sessions`.`session_name`,
                                    `reward_point_criterias`.`max_score` AS `points_allocated`,
                                    (SELECT `score`
                                        FROM `reward_points_scored`
                                        WHERE `user_id` = :user_id
                                        AND `criteria_id` = `reward_point_criterias`.`id`
                                        ORDER BY `reward_points_scored`.`id` DESC LIMIT 0,1
                                    ) AS `points_earned`
                                FROM `course_session_to_topic_mapping`
                                    JOIN `quizzes` ON(`course_session_to_topic_mapping`.`topic_id` = `quizzes`.`associated_id`)
                                    JOIN `course_sessions` ON(`course_sessions`.`session_id` = `course_session_to_topic_mapping`.`session_id`)
                                    JOIN `reward_point_criterias` ON(`quizzes`.`id` = `reward_point_criterias`.`reference_id`)
                                WHERE `quizzes`.`associated_to` = 'TOPIC'
                                    AND `reward_point_criterias`.`criteria` = :criteria
                                    AND `course_session_to_topic_mapping`.`status` = :status
                                    AND `quizzes`.`status` = :status
                                    AND `quizzes`.`subject_id` = :subject_id
                                    AND `course_sessions`.`status` = :status
                                ORDER BY `course_sessions`.`session_index` ASC ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->bindValue(':criteria', 'QUIZ', PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignments = array();

        $pendingAssignments = array();
        foreach ($result as $oneAssignment)
        {

            $session_id = $oneAssignment['session_id'];
            if (!isset($assignments[$session_id]))
            {
                $assignments[$session_id] = array(
                    "session_index" => $oneAssignment['session_index'],
                    "session_name" => $oneAssignment['session_name'],
                    "points_earned" => 0,
                    "earnings" => array() ,
                    "points_available" => 0,
                    "available_assignments" => array()
                );
            }

            if ($oneAssignment['points_earned'] == NULL)
            {

                array_push($assignments[$session_id]['available_assignments'], array(
                    "Assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated'],
                ));
                $assignments[$session_id]['points_available'] += $oneAssignment['points_allocated'];

                array_push($pendingAssignments, array(
                    "session_index" => $oneAssignment['session_index'],
                    "assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated']
                ));
            }
            else
            {

                array_push($assignments[$session_id]['earnings'], array(
                    "assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated'],
                    "points_earned" => $oneAssignment['points_earned']
                ));
                $assignments[$session_id]['points_earned'] += $oneAssignment['points_earned'];

            }

        }
        $dataForChart = array();
        foreach ($assignments as $assignment)
        {
            array_push($dataForChart, $assignment);
        }

        $stmt = $db->prepare(" SELECT
                                `name`,
                                `reward_point_criterias`.`max_score` AS `points_allocated`,
                                (SELECT `score`
                                    FROM `reward_points_scored`
                                    WHERE `user_id` = :user_id
                                        AND `criteria_id` = `reward_point_criterias`.`id`
                                    ORDER BY `reward_points_scored`.`id` DESC  LIMIT 0,1
                                ) AS `points_earned`
                            FROM `quizzes`
                                JOIN `reward_point_criterias` ON(`quizzes`.`id` = `reward_point_criterias`.`reference_id`)
                            WHERE `quizzes`.`associated_to` != 'TOPIC'
                                AND `quizzes`.`subject_id` = :subject_id
                                AND `reward_point_criterias`.`criteria` = :criteria
                                AND `quizzes`.`status` = :status");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->bindValue(':criteria', 'QUIZ', PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignments = array();

        $program_quiz = array(
            "session_index" => 'PROGRAM',
            "session_name" => "MODULE AND ENTIRE_SUBJECT",
            "points_earned" => 0,
            "earnings" => array() ,
            "points_available" => 0,
            "available_assignments" => array()
        );
        foreach ($result as $oneAssignment)
        {
            if ($oneAssignment['points_earned'] == NULL)
            {

                array_push($program_quiz['available_assignments'], array(
                    "Assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated'],
                ));
                $program_quiz['points_available'] += $oneAssignment['points_allocated'];

                array_push($pendingAssignments, array(
                    "session_index" => 'PROGRAM',
                    "assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated']
                ));
            }
            else
            {

                array_push($program_quiz['earnings'], array(
                    "assignment" => $oneAssignment['name'],
                    "points_allocated" => $oneAssignment['points_allocated'],
                    "points_earned" => $oneAssignment['points_earned']
                ));
                $program_quiz['points_earned'] += $oneAssignment['points_earned'];

            }

        }
        array_push($dataForChart, $program_quiz);
        return array(

            "dataForChart" => $dataForChart,
            "pendingAssignments" => $pendingAssignments
        );

    }

    /**
     * Insert into the respective tables
     */
    public static function quizSubmission($data, $user_id)
    {
        $quizId = $data[0]['quizId'];
        $db = static ::getDB();
        $db->beginTransaction();
        //insert into resources table
        $stmt = $db->prepare("INSERT INTO `quiz_answer_submissions`
                                (`quiz_id`,`user_id`,`status`)
                                VALUES(
                                    :quizId,
                                    :user_id,
                                    :status
                                )");
        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        if (!$stmt->execute())
        {
            return array(
                "status" => "Error",
                "message" => "Error"
            );
        }

        $answer_submission_id = $db->lastInsertId();

        $stmt = $db->prepare(" SELECT `id`,`max_score`
                                FROM `reward_point_criterias`
                                WHERE `reward_point_criterias`.`reference_id` = :quizId
                                AND `reward_point_criterias`.`status` = :status
                                AND `reward_point_criterias`.`criteria` = 'QUIZ'
                                ");

        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $reward_point_criterias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $quizGroup_criteria_id = $reward_point_criterias[0]['id'];
        $quizGroup_maxScore = $reward_point_criterias[0]['max_score'];

        $total_points_scored = 0;
        foreach ($data as $answer)
        {

            $question_id = $answer['question_id'];
            $question_type = $answer['question_type'];
            $value = $answer['value'];
            $question = $answer['question'];

            $stmt = $db->prepare(" SELECT `points`
                                    FROM `quiz_questions`
                                    WHERE `quiz_questions`.`id` = :question_id
                                    AND `quiz_questions`.`status` = :status
                                    ");
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $points = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $question_points = $points[0]['points'];
            if ($question_type === "OBJECTIVE")
            {
                $stmt = $db->prepare(" SELECT `id`
                                        FROM `quiz_options`
                                        WHERE `quiz_question_id` = :question_id
                                        AND `correctness` = 'CORRECT'
                                        AND `status` = :status
                                        ");
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $correct_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $correct_option = $correct_options[0]['id'];

                if($correct_option != $value){
                    $question_points = 0;
                }
                $stmt = $db->prepare("INSERT INTO `quiz_answers`
                                    (`answer_submission_id`,`question_id`,`objective_answer`,`points_earned`, `status`)
                                    VALUES(
                                        :answer_submission_id,
                                        :question_id,
                                        :objective_answer,
                                        :points_earned,
                                        :status
                                    )");
                $stmt->bindValue(':answer_submission_id', $answer_submission_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                $stmt->bindValue(':points_earned', $question_points, PDO::PARAM_INT);
                $stmt->bindValue(':objective_answer', $value, PDO::PARAM_STR);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                if (!$stmt->execute())
                {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "message" => "Error"
                    );
                }

            }
            elseif ($question_type === "DESCRIPTIVE")
            {
                $stmt = $db->prepare("INSERT INTO `quiz_answers`
                                    (`answer_submission_id`,`question_id`,`descriptive_answer`,`points_earned`, `status`)
                                    VALUES(
                                        :answer_submission_id,
                                        :question_id,
                                        :descriptive_answer,
                                        :points_earned,
                                        :status
                                    )");
                $stmt->bindValue(':answer_submission_id', $answer_submission_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                $stmt->bindValue(':points_earned', $question_points, PDO::PARAM_INT);
                $stmt->bindValue(':descriptive_answer', $value, PDO::PARAM_STR);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                if (!$stmt->execute())
                {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "message" => "Error"
                    );
                }
            }
            elseif ($question_type === "MULTIPLE_CHOICE")
            {
                $stmt = $db->prepare(" SELECT `id`
                                        FROM `quiz_options`
                                        WHERE `quiz_question_id` = :question_id
                                        AND `correctness` = 'CORRECT'
                                        AND `status` = :status
                                        ");
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $correct_options = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $tempValueArray = $value;
                foreach($correct_options as $correct_option){
                    $search_result = array_search($correct_option,$tempValueArray);
                    if($search_result === false){
                        $question_points = 0;
                        break;
                    }
                    else{
                        unset($tempValueArray[$search_result]);
                    }
                }
                if(count($tempValueArray)>0){
                    $question_points = 0;
                }
                $stmt = $db->prepare("INSERT INTO `quiz_answers`
                                    (`answer_submission_id`,`question_id`,`points_earned`, `status`)
                                    VALUES(
                                        :answer_submission_id,
                                        :question_id,
                                        :points_earned,
                                        :status
                                    )");
                $stmt->bindValue(':answer_submission_id', $answer_submission_id, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                $stmt->bindValue(':points_earned', $question_points, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                if (!$stmt->execute())
                {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "message" => "Error"
                    );
                }

                $answer_id = $db->lastInsertId();
                foreach ($value as $oneOption)
                {
                    if ($oneOption['status'] === 'checked')
                    {
                        $selection_status = 'SELECTED';
                    }
                    if ($oneOption['status'] === "not checked")
                    {
                        $selection_status = 'NOT SELECTED';
                    }

                    $stmt = $db->prepare("INSERT INTO `quiz_multi_select_answers_options`
                                        (`answer_id`,`option_id`,`selection_status`, `status`)
                                        VALUES(
                                            :answer_id,
                                            :option_id,
                                            :selection_status,
                                            :status
                                        )");
                    $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);
                    $stmt->bindValue(':option_id', $oneOption['choice_id'], PDO::PARAM_INT);
                    $stmt->bindValue(':selection_status', $selection_status, PDO::PARAM_STR);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                    if (!$stmt->execute())
                    {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Error"
                        );
                    }

                }
            }
            $total_points_scored += $question_points;
        }

        $stmt = $db->prepare("INSERT INTO `reward_points_scored`
                            (`user_id`,`criteria_id`,`score`,`status`)
                            VALUES(
                                :user_id,
                                :criteria_id,
                                :score,
                                :status
                            )");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':criteria_id', $quizGroup_criteria_id, PDO::PARAM_INT);
        $stmt->bindValue(':score', $total_points_scored, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        if (!$stmt->execute())
        {
            $db->rollBack();
            return array(
                "status" => "Error",
                "message" => "Error"
            );
        }

        $db->commit();
        return array(
            "status" => "Success",
            "quizDetails" => Quizzes::getQuizQuestions($quizId, $user_id)
        );
    }

    /**
     * Get Quiz groups of all learners of a course grouped by session.
     * This is used for displaying submitted Quizzes page in facilitator
     */
    public static function getSubmittedQuizzes($session_id, $subjectId,$course_id)
    {
        $db = static ::getDB();
        //Get the list of submissions by the user
        $stmt = $db->prepare(" SELECT `quizzes`.`id`,
                                    `quizzes`.`name` AS quiz_name,
                                    `course_session_to_topic_mapping`.`session_id`,
                                    `course_sessions`.`session_name`,
                                    `course_sessions`.`session_index`,
                                    `reward_point_criterias`.`max_score`AS `Points_allocated`,
                                    `reward_points_scored`.`score` AS `points_earned`,
                                    `users`.`id` AS `user_id`,
                                    `users`.`profile_pic_binary`,
                                    `users`.`name`,
                                    `users`.`last_name`

                                FROM `quizzes`
                                JOIN `reward_point_criterias` ON (`quizzes`.`id` = `reward_point_criterias`.`reference_id`
                                                                    AND `reward_point_criterias`.`criteria` = :criteria)
                                JOIN `reward_points_scored` ON(`reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`)
                                JOIN `users` ON (`reward_points_scored`.`user_id` = `users`.`id`)
                                JOIN `course_session_to_topic_mapping` ON (`quizzes`.`associated_id`=`course_session_to_topic_mapping`.`topic_id`)
                                JOIN `course_sessions` ON (`course_session_to_topic_mapping`.`session_id`=`course_sessions`.`session_id`)

                                WHERE `quizzes`.`associated_to` = 'TOPIC'
                                    AND `course_sessions`.`course_id` = :course_id
                                    AND  `course_session_to_topic_mapping`.`topic_type` = 'SUBJECT_TOPIC'
                                    AND `course_session_to_topic_mapping`.`status` = :status
                                    AND  `course_sessions`.`status` = :status
                                    AND `quizzes`.`status` = :status
                                    AND `quizzes`.`subject_id` = :subjectId
                                    AND `reward_point_criterias`.`status`= :status
                                    AND `reward_points_scored`.`status` = :status ");

        $stmt->bindValue(':subjectId', $subjectId, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':criteria', 'QUIZ', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $participantWiseData = array();
        foreach ($submissions as $submission)
        {
            if($submission['session_id'] == $session_id){
                $userId = $submission['user_id'];
                if (isset($participantWiseData[$userId]))
                {
                    array_push($participantWiseData[$userId]['quiz'], array(
                        "id" => $submission['id'],
                        "name" => $submission['quiz_name'],
                        "points_allocated" => $submission['Points_allocated'],
                        "points_earned" => $submission['points_earned']
                    ));
                }

                else
                {
                    $participantWiseData[$userId] = array(
                        "id" => $submission['user_id'],
                        "name" => $submission['name'] . " " . $submission['last_name'],
                        "profile_pic_binary" => $submission['profile_pic_binary'],
                        "quiz" => array(
                            0 => array(
                                "id" => $submission['id'],
                                "name" => $submission['quiz_name'],
                                "points_allocated" => $submission['Points_allocated'],
                                "points_earned" => $submission['points_earned']
                            )
                        )
                    );
                }
            }
        }
        $data = array();
        foreach ($participantWiseData as $oneParticipantData)
        {
            array_push($data, $oneParticipantData);
        }
        return $data;
    }

    public static function getSubjectAndModuleSubmittedQuizzes($subjectId)
    {
        $db = static ::getDB();

        //Get the list of submissions by the user
        $stmt = $db->prepare(" SELECT `quizzes`.`id`,
                                    `quizzes`.`name` AS quiz_name,
                                    `reward_point_criterias`.`max_score`AS `Points_allocated`,
                                    `reward_points_scored`.`score` AS `points_earned`,
                                    `users`.`id` AS `user_id`,
                                    `users`.`profile_pic_binary`,
                                    `users`.`name`,
                                    `users`.`last_name`

                                FROM `quizzes`
                                JOIN `reward_point_criterias` ON (`reward_point_criterias`.`reference_id` = `quizzes`.`id`
                                                                    AND `reward_point_criterias`.`criteria` = :criteria)
                                JOIN `reward_points_scored` ON(`reward_points_scored`.`criteria_id` = `reward_point_criterias`.`id`)
                                JOIN `users` ON (`reward_points_scored`.`user_id` = `users`.`id`)

                                WHERE `quizzes`.`associated_to` != 'TOPIC'
                                    AND `quizzes`.`status` = :status
                                    AND `quizzes`.`subject_id` = :subjectId
                                    AND `reward_point_criterias`.`status`= :status
                                    AND `reward_points_scored`.`status` = :status ");

        $stmt->bindValue(':subjectId', $subjectId, PDO::PARAM_INT);
        $stmt->bindValue(':criteria', 'QUIZ', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $participantWiseData = array();
        foreach ($submissions as $submission)
        {
            $userId = $submission['user_id'];
            if (isset($participantWiseData[$userId]))
            {
                array_push($participantWiseData[$userId]['quiz'], array(
                    "id" => $submission['id'],
                    "name" => $submission['quiz_name'],
                    "points_allocated" => $submission['Points_allocated'],
                    "points_earned" => $submission['points_earned']
                ));
            }

            else
            {
                $participantWiseData[$userId] = array(
                    "id" => $submission['user_id'],
                    "name" => $submission['name'] . " " . $submission['last_name'],
                    "profile_pic_binary" => $submission['profile_pic_binary'],
                    "quiz" => array(
                        0 => array(
                            "id" => $submission['id'],
                            "name" => $submission['quiz_name'],
                            "points_allocated" => $submission['Points_allocated'],
                            "points_earned" => $submission['points_earned']
                        )
                    )
                );
            }
        }
        $data = array();
        foreach ($participantWiseData as $oneParticipantData)
        {
            array_push($data, $oneParticipantData);
        }
        return $data;
    }

    /**
     *
     */

    public static function quizOfTheSubject($subjectId)
    {
        $db = static ::getDB();
        //this query will give quizGroupName text from quizzes table
        $stmt = $db->prepare("SELECT `quizzes`.`id`, `name`, `associated_to`, `associated_id`,
                                COUNT(`quiz_questions`.`question`) AS `total_question`,
                                SUM(`quiz_questions`.`Points`) AS `total_points`
                            FROM `quizzes`
                            JOIN `quiz_questions` ON (`quizzes`.`id` = `quiz_questions`.`quiz_id`)
                            WHERE `subject_id` = :subject_id
                            AND `quizzes`.`status` = :status
                            AND `quiz_questions`.`status`=:status
                            GROUP BY (`quizzes`.`id`)");
        $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        if (!$stmt->execute())
        {
            return array(
                "status" => "Error",
                "message" => "error while getting data"
            );
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array();
        foreach ($result as $quiz)
        {

            if ($quiz['associated_to'] === "MODULE")
            {
                $stmt = $db->prepare("SELECT `module_id`, `module_index`, `module_name`
                                        FROM `subject_modules`
                                        WHERE `module_id` = :module_id
                                        AND `status` = :status ");
                $stmt->bindValue(':module_id', $quiz['associated_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute() || $stmt->rowCount() === 0)
                {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting association of reflection: " . $quiz['name']
                    );
                }
                $module = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $quiz['module_id'] = $module[0]['module_id'];
                $quiz['module_index'] = $module[0]['module_index'];
                $quiz['module_name'] = $module[0]['module_name'];
            }
            elseif ($quiz['associated_to'] === "TOPIC")
            {
                $stmt = $db->prepare("SELECT `subject_topics`.`id` AS topic_id, `name` AS topic,
                                    `subject_modules`.`module_id`, `module_index`, `module_name`
                                    FROM `subject_topics`
                                    JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                    WHERE `subject_topics`.`id` = :subject_topics
                                    AND `subject_topics`.`status` = :status
                                    AND `subject_modules`.`status` = :status ");
                $stmt->bindValue(':subject_topics', $quiz['associated_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute() || $stmt->rowCount() === 0)
                {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting association of reflection: " . $quiz['name']
                    );
                }
                $topic = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $quiz['module_id'] = $topic[0]['module_id'];
                $quiz['module_index'] = $topic[0]['module_index'];
                $quiz['module_name'] = $topic[0]['module_name'];
                $quiz['topic_id'] = $topic[0]['topic_id'];
                $quiz['topic'] = $topic[0]['topic'];
            }
            array_push($data, $quiz);
        }

        return array(
            "status" => "Success",
            "data" => $data
        );

    }

    public static function addQuiz($data, $user_id)
    {
        $subject_id = $data['subject_id'];
        $quizName = $data['name'];

        if ($data['associated_to'] === "Subject")
        {
            $resource_for = "PROGRAM";
            $assignment_associated_to = "ENTIRE_SUBJECT";
            $reference_id = 0;
        }
        elseif ($data['associated_to'] === "Module")
        {
            $resource_for = "MODULE";
            $assignment_associated_to = "MODULE";
            $reference_id = $data['associated_module_id'];
        }
        else
        {
            $resource_for = "TOPIC";
            $assignment_associated_to = "TOPIC";
            $reference_id = $data['associated_topic_id'];
        }

        $db = static ::getDB();

        $db->beginTransaction();

        //insert into resources table
        $stmt = $db->prepare("INSERT INTO `quizzes`
                            (`name`,`subject_id`, `associated_to`, `associated_id`,`status`)
                            VALUES(
                                :name,
                                :subject_id,
                                :associated_to,
                                :associated_id,
                                :status
                            )");
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $quizName, PDO::PARAM_STR);
        $stmt->bindValue(':associated_to', $assignment_associated_to, PDO::PARAM_STR);
        $stmt->bindValue(':associated_id', $reference_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        if (!$stmt->execute())
        {
            return array(
                "status" => "Error",
                "message" => "Error"
            );
        }

        $quiz_id = $db->lastInsertId();
        $max_score = 0;

        foreach ($data['question'] as $oneQuestion)
        {
            $question = $oneQuestion['name'];
            $question_type = $oneQuestion['type'];
            $question_real_type = $oneQuestion['real_type'];
            $question_points = $oneQuestion['points'];
            $max_score += $oneQuestion['points'];

            $stmt = $db->prepare("INSERT INTO `quiz_questions`
                                (`quiz_id`,`question`, `type`, `points`,`status`)
                                VALUES(
                                    :quiz_id,
                                    :question,
                                    :type,
                                    :points,
                                    :status
                                )");
            $stmt->bindValue(':quiz_id', $quiz_id, PDO::PARAM_INT);
            $stmt->bindValue(':question', $question, PDO::PARAM_STR);
            $stmt->bindValue(':type', $question_real_type, PDO::PARAM_STR);
            $stmt->bindValue(':points', $question_points, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

            if (!$stmt->execute())
            {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Could not insert the quiz question"
                );
            }

            $quiz_question_id = $db->lastInsertId();

            if ($question_type === 'objective')
            {
                foreach ($oneQuestion['correct_option'] as $correctOption)
                {

                    $stmt = $db->prepare("INSERT INTO `quiz_options`
                                        (`quiz_question_id`,`options`, `correctness`, `status`)
                                        VALUES(
                                            :quiz_question_id,
                                            :options,
                                            :correctness,
                                            :status
                                        )");
                    $stmt->bindValue(':quiz_question_id', $quiz_question_id, PDO::PARAM_INT);
                    $stmt->bindValue(':options', $correctOption, PDO::PARAM_STR);
                    $stmt->bindValue(':correctness', 'CORRECT', PDO::PARAM_STR);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                    if (!$stmt->execute())
                    {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Could not insert the quiz options"
                        );
                    }
                }

                foreach ($oneQuestion['wrong_option'] as $WrongOption)
                {

                    $stmt = $db->prepare("INSERT INTO `quiz_options`
                                        (`quiz_question_id`,`options`, `correctness`, `status`)
                                        VALUES(
                                            :quiz_question_id,
                                            :options,
                                            :correctness,
                                            :status
                                        )");
                    $stmt->bindValue(':quiz_question_id', $quiz_question_id, PDO::PARAM_INT);
                    $stmt->bindValue(':options', $WrongOption, PDO::PARAM_STR);
                    $stmt->bindValue(':correctness', 'WRONG', PDO::PARAM_STR);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                    if (!$stmt->execute())
                    {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Could not insert the quiz options"
                        );
                    }
                }

            }

        }

        $stmt = $db->prepare("INSERT INTO `reward_point_criterias`
                            (`course_id`,`criteria`, `reference_id`, `max_score`,`status`)
                            VALUES(
                                :course_id,
                                :criteria,
                                :reference_id,
                                :max_score,
                                :status
                            )");
        $stmt->bindValue(':course_id', 0, PDO::PARAM_INT);
        $stmt->bindValue(':criteria', 'QUIZ', PDO::PARAM_STR);
        $stmt->bindValue(':reference_id', $quiz_id, PDO::PARAM_STR);
        $stmt->bindValue(':max_score', $max_score, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        if (!$stmt->execute())
        {
            return array(
                "status" => "Error",
                "message" => "Error"
            );
        }

        $db->commit();
        return array(
            "status" => "Success",
            "message" => ""
        );
    }

    public static function deleteQuiz($quizId)
    {
        $db = static ::getDB();
        //this query will give reflection text from reflections table
        $stmt = $db->prepare("UPDATE `quizzes`
                                SET `status` = :status
                                WHERE `id` = :id ");
        $stmt->bindValue(':id', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':status', "INACTIVE", PDO::PARAM_STR);
        if (!$stmt->execute())
        {
            return array(
                "status" => "Error",
                "message" => "error while de-activating the Quiz-group"
            );
        }

        return array(
            "status" => "Success"
        );
    }

    public static function getRewardPointsDataOfTheUser($subject_id, $user_id){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT SUM(`reward_point_criterias`.`max_score`) AS pointsAvailable,
                                SUM(`reward_points_scored`.`score`) AS pointsScored
                            FROM
                                `quizzes`
                            JOIN `reward_point_criterias` ON(
                                    `quizzes`.`id` = `reward_point_criterias`.`reference_id`
                                    AND `reward_point_criterias`.`criteria` = 'QUIZ'
                                )
                            LEFT JOIN `reward_points_scored` ON
                                (
                                    `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                    AND `reward_points_scored`.`user_id` = :user_id
                                    AND `reward_points_scored`.`status` = :status
                                )
                            WHERE
                                `quizzes`.`subject_id` = :subject_id
                                AND `quizzes`.`status` = :status
                                AND `reward_point_criterias`.`status` = :status ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quiz_data = array_pop($result);
        return $quiz_data;
    }

    public static function isQuizBelongToCourse($quizId,$courseId){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT *
                                FROM `quizzes`
                                    JOIN `courses` ON(`quizzes`.`subject_id` = `courses`.`subject_id`)
                                WHERE `quizzes`.`id` = :quizId
                                    AND `courses`.`course_id` = :courseId
                                    AND `quizzes`.`status` = :status
                                    AND `courses`.`status` = :status");

        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_STR);
        $stmt->bindValue(':courseId', $courseId, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($result)>0){
            return true;
        }
        return false;
    }

    public static function isQuizBelongToSubject($quizId,$subjectId){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT subject_id
                                FROM `quizzes`
                                WHERE `quizzes`.`id` = :quizId
                                    AND `quizzes`.`status` = :status");

        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($result[0]['subject_id']==$subjectId){
            return true;
        }
        return false;
    }
    public static function getQuizDetails($quizId, $subjectId){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `quizzes`.`name`,
                                    `quizzes`.`associated_to`,
                                    `quizzes`.`associated_id`,
                                    `subject_modules`.`module_name` AS associated_module,
                                    `subject_topics`.`name` AS associated_topic,
                                    `subject_modules`.`module_index`,
                                    COUNT(`quiz_questions`.`question`) AS `total_question`,
                                    SUM(`quiz_questions`.`Points`) AS `total_points`

                                FROM `quizzes`
                                JOIN `quiz_questions` ON (`quizzes`.`id` = `quiz_questions`.`quiz_id`)
                                    LEFT JOIN `subject_modules` ON (`quizzes`.`associated_to` = 'MODULE'
                                                    AND `subject_modules`.`subject_id` = :subjectId
                                                    AND `quizzes`.`associated_id` = `subject_modules`.`module_id`
                                                    AND `subject_modules`.`status` = :status)

                                    LEFT JOIN `subject_topics` ON (`quizzes`.`associated_to` = 'TOPIC'
                                                    AND `quizzes`.`associated_id` = `subject_topics`.`id`
                                                    AND `subject_topics`.`status` = :status)

                                WHERE `quizzes`.`status` =:status
                                    AND `quiz_questions`.`status` = :status
                                    AND `quizzes`.`id` = :quizId");

        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':subjectId', $subjectId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $association_details = $result1[0];
        $data = array();

        $data['name'] = $association_details['name'];
        $data['associated_to'] = $association_details['associated_to'];
        $data['total_question'] = $association_details['total_question'];
        $data['total_points'] = $association_details['total_points'];

        if($association_details['associated_to'] === 'TOPIC'){
            $topic_id = $association_details['associated_id'];

            $stmt = $db->prepare("SELECT `subject_topics`.`module_id`,
                                        `subject_modules`.`module_name`
                                        FROM `subject_topics`
                                        JOIN  `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                    WHERE `subject_topics`.`id`=:topic_id
                                        AND`subject_topics`.`status` = :status
                                        AND`subject_modules`.`status` = :status");

            $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data['associated_topic_id'] = $association_details['associated_id'];
            $data['associated_topic'] = $association_details['associated_topic'];
            $data['associated_module_id'] = $result2[0]['module_id'];
            $data['associated_module'] = $result2[0]['module_name'];

        }
        elseif($association_details['associated_to'] === 'MODULE'){
            $data['associated_module_id'] = $association_details['associated_id'];
            $data['associated_module'] = $association_details['associated_module'];
            $data['associated_topic_id'] = null;
            $data['associated_topic'] = null;
        }
        else{
            $data['associated_module_id'] = null;
            $data['associated_module'] = null;
            $data['associated_topic_id'] = null;
            $data['associated_topic'] = null;
        }
        $questions = array();
        $question = array();

        $stmt = $db->prepare("SELECT `id`,`question`,`type`,`points`

                                FROM `quiz_questions`
                                WHERE `quiz_id` = :quizId
                                    AND `status` = :status");

        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($result2 as $oneQuestion){
            $question = [];
            if($oneQuestion['type'] === 'DESCRIPTIVE'){
                $question['question_id']=$oneQuestion['id'];
                $question['name']=$oneQuestion['question'];
                $question['type']=$oneQuestion['type'];
                $question['points']=$oneQuestion['points'];
            }
            elseif($oneQuestion['type'] === 'OBJECTIVE' || $oneQuestion['type'] === 'MULTIPLE_CHOICE'){
                $stmt = $db->prepare("SELECT `id`,`options`,`correctness`
                                        FROM `quiz_options`
                                        WHERE `quiz_question_id` = :questionId
                                        AND `status` = :status");

                $stmt->bindValue(':questionId', $oneQuestion['id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $question['question_id']=$oneQuestion['id'];
                $question['name']=$oneQuestion['question'];
                $question['type']=$oneQuestion['type'];
                $question['points']=$oneQuestion['points'];
                $question['correct_option'] = array();
                $question['wrong_option'] = array();

                foreach($result3 as $oneOption){
                    if($oneOption['correctness']==="CORRECT"){
                        array_push($question['correct_option'],$oneOption['options']);
                    }
                    elseif($oneOption['correctness']==="WRONG"){
                        array_push($question['wrong_option'],$oneOption['options']);
                    }
                }
            }
            array_push($questions,$question);
        }
        $data['questions'] = $questions;
        return $data;


    }


    public static function updateQuiz($data, $user_id, $quizId)
    {
        $subject_id = $data['subject_id'];
        $quizName = $data['name'];

        if ($data['associated_to'] === "Subject")
        {
            $quiz_for = "PROGRAM";
            $quiz_associated_to = "ENTIRE_SUBJECT";
            $reference_id = 0;
        }
        elseif ($data['associated_to'] === "Module")
        {
            $quiz_for = "MODULE";
            $quiz_associated_to = "MODULE";
            $reference_id = $data['associated_module_id'];
        }
        else
        {
            $quiz_for = "TOPIC";
            $quiz_associated_to = "TOPIC";
            $reference_id = $data['associated_topic_id'];
        }

        $db = static ::getDB();
        //this query will update the quizzes table
        $stmt = $db->prepare("UPDATE `quizzes`
                                SET `name`=:quizName,
                                    `associated_to`=:associated_to,
                                    `associated_id`=:associated_id
                                where `id`=:quizId
        ");
        $stmt->bindValue(':quizName', $quizName, PDO::PARAM_STR);
        $stmt->bindValue(':associated_to', $quiz_associated_to, PDO::PARAM_STR);
        $stmt->bindValue(':associated_id', $reference_id, PDO::PARAM_INT);
        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_INT);
        if (!$stmt->execute())
        {
            return array(
                "status" => "Error",
                "message" => "error while updating the Quiz"
            );
        }

        $stmt = $db->prepare("SELECT `id`
                                FROM `quiz_questions`
                                WHERE `quiz_id` = :quizId
                                    AND `quiz_questions`.`status` = :status");

        $stmt->bindValue(':quizId', $quizId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $questionIds= array();
        $modified_question_array = array();

        foreach ($result1 as $oneQuestionId){
            array_push($questionIds,$oneQuestionId['id']);
        }

        foreach ($data['question'] as $oneQuestion){
            if(isset($oneQuestion['question_id'])){
                array_push($modified_question_array,$oneQuestion['question_id']);
            }
        }
        $max_score = 0;
        foreach ($data['question'] as $oneQuestion)
        {
            if(!isset($oneQuestion['question_id'])){
                $question = $oneQuestion['name'];
                $question_type = $oneQuestion['type'];
                $question_real_type = $oneQuestion['real_type'];
                $question_points = $oneQuestion['points'];
                $max_score += $oneQuestion['points'];

                $stmt = $db->prepare("INSERT INTO `quiz_questions`
                                    (`quiz_id`,`question`, `type`, `points`,`status`)
                                    VALUES(
                                        :quiz_id,
                                        :question,
                                        :type,
                                        :points,
                                        :status
                                    )");
                $stmt->bindValue(':quiz_id', $quizId, PDO::PARAM_INT);
                $stmt->bindValue(':question', $question, PDO::PARAM_STR);
                $stmt->bindValue(':type', $question_real_type, PDO::PARAM_STR);
                $stmt->bindValue(':points', $question_points, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                if (!$stmt->execute())
                {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "message" => "Could not insert the quiz question"
                    );
                }

                $quiz_question_id = $db->lastInsertId();

                if ($question_type === 'objective')
                {
                    foreach ($oneQuestion['correct_option'] as $correctOption)
                    {

                        $stmt = $db->prepare("INSERT INTO `quiz_options`
                                            (`quiz_question_id`,`options`, `correctness`, `status`)
                                            VALUES(
                                                :quiz_question_id,
                                                :options,
                                                :correctness,
                                                :status
                                            )");
                        $stmt->bindValue(':quiz_question_id', $quiz_question_id, PDO::PARAM_INT);
                        $stmt->bindValue(':options', $correctOption, PDO::PARAM_STR);
                        $stmt->bindValue(':correctness', 'CORRECT', PDO::PARAM_STR);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                        if (!$stmt->execute())
                        {
                            $db->rollBack();
                            return array(
                                "status" => "Error",
                                "message" => "Could not insert the quiz options"
                            );
                        }
                    }

                    foreach ($oneQuestion['wrong_option'] as $WrongOption)
                    {

                        $stmt = $db->prepare("INSERT INTO `quiz_options`
                                            (`quiz_question_id`,`options`, `correctness`, `status`)
                                            VALUES(
                                                :quiz_question_id,
                                                :options,
                                                :correctness,
                                                :status
                                            )");
                        $stmt->bindValue(':quiz_question_id', $quiz_question_id, PDO::PARAM_INT);
                        $stmt->bindValue(':options', $WrongOption, PDO::PARAM_STR);
                        $stmt->bindValue(':correctness', 'WRONG', PDO::PARAM_STR);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                        if (!$stmt->execute())
                        {
                            $db->rollBack();
                            return array(
                                "status" => "Error",
                                "message" => "Could not insert the quiz options"
                            );
                        }
                    }

                }
            }

        }

        foreach ($questionIds as $oneQuestionId){
            if(!in_array($oneQuestionId,$modified_question_array)){
                $stmt = $db->prepare("UPDATE `quiz_questions`
                                        SET `status`='INACTIVE'
                                        where `id`=:question_id
                ");
                $stmt->bindValue(':question_id', $oneQuestionId, PDO::PARAM_INT);
                if (!$stmt->execute())
                {
                    return array(
                        "status" => "Error",
                        "message" => "error while updating the Quiz"
                    );
                }
            }
        }


        return array(
            "status" => "Success"
        );
    }

}
?>
