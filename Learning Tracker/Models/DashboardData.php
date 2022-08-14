<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use PDO;
use App\Models\Assignments;
use App\Models\Reflections;
use App\Models\Quizzes;

/**
 * Description of DashboardData
 *
 * @author maheshbasapur
 */
class DashboardData extends \Core\Model {

    public static function getSessionProgressData($course_id) {
        try {

            $db = static::getDB();

            $stmt = $db->prepare('SELECT `module_id`, `module_index`, `module_name` FROM `course_modules` WHERE course_id = :course_id AND module_index = :module_index  ORDER BY `module_id` ASC');
            $stmt->bindValue(':module_index', $module_index, PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $value = array_pop($results);


        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     *
     * @param type $course_id
     * @param type $user_id
     * @return type points data required by chart
     */
    public static function getRewardsDetails($subject_id, $course_id, $user_id) {

        $assignments_data = Assignments::getRewardPointsDataOfTheUser($subject_id, $course_id, $user_id);
        $reflections_data = Reflections::getRewardPointsDataOfTheUser($subject_id, $course_id, $user_id);
        $quiz_data = Quizzes::getRewardPointsDataOfTheUser($subject_id, $user_id);
        /**********************************************************************/
        //We will have to add similar function call for Quizes and Exam-preps
        /**********************************************************************/
        $pointsAvailable = $assignments_data['pointsAvailable'] + $quiz_data['pointsAvailable']+ $reflections_data['pointsAvailable'];
        $totalPoints = ($pointsAvailable > 100 ? $pointsAvailable : 100);
        $dataForOuterArc = array(
            "label" => "Available",
            "value" => $pointsAvailable,
            "outOf" => $totalPoints,
            "color" => '#02435C',
            "tooltipText" => "$pointsAvailable / $totalPoints"
        );

        $pointsEarned = $assignments_data['pointsScored'] + $quiz_data['pointsScored'] + $reflections_data['pointsScored'];
        $dataForInnerArc = array(
            "label" => "Earned",
            "value" => $pointsEarned,
            "outOf" => $totalPoints,
            "color" => '#D76F5F',
            "tooltipText" => "$pointsEarned / $totalPoints"
        );

        $data = array(
            $dataForOuterArc,
            $dataForInnerArc
        );
        return array(
            "data" => json_encode($data),
            "numeratorTextElements" => json_encode(array($pointsEarned, $pointsAvailable)),
            "denominatorTextElements" => $totalPoints
        );
    }

    /**
     * @param type $param
     * @return type
     */
    public static function getAssignmentPointsData($subject_id, $course_id, $user_id) {
        try{
            $db = static::getDB();
            //Get the total points available so far to grab
            $stmt = $db->prepare("SELECT
                                    `course_sessions`.`session_id`,
                                    `course_sessions`.`session_index`,
                                    `course_session_assignments`.`name`,
                                    `reward_point_criterias`.`max_score`,
                                    `reward_points_scored`.`score`
                                FROM
                                	`course_session_assignments`
                                JOIN `course_sessions` ON(
                                        `course_session_assignments`.`session_id` = `course_sessions`.`session_id`
                                    )
                                JOIN `reward_point_criterias` ON(
                                        `course_session_assignments`.`id` = `reward_point_criterias`.`reference_id`
                                    AND `reward_point_criterias`.`criteria` = :criteria
                                    )
                                LEFT JOIN `reward_points_scored` ON(
                                        `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                    AND `reward_points_scored`.`user_id` = :user_id
                                    AND `reward_points_scored`.`status` = :status
                                    )
                                WHERE `course_sessions`.`course_id` = :course_id
                                    AND `course_sessions`.`status` = :status
                                    AND `course_session_assignments`.`status` = :status
                                    AND `reward_point_criterias`.`status` = :status");

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':criteria', "ASSIGNMENT", PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $submittedAssignments1 = array();
            $pendingAssignments1 = array();

            foreach($result as $oneAssignment){
                if($oneAssignment['score']==null){
                    array_push($pendingAssignments1, $oneAssignment);
                }
                else{
                    array_push($submittedAssignments1, $oneAssignment);
                }
            }

            $stmt1 = $db->prepare("SELECT
                                        `subject_assignments`.`id`,
                                        `subject_assignments`.`name`,
                                        `reward_point_criterias`.`max_score`,
                                        `reward_points_scored`.`score`
                                    FROM
                                        `subject_assignments`
                                    JOIN `reward_point_criterias` ON
                                        (
                                            `subject_assignments`.`id` = `reward_point_criterias`.`reference_id`
                                            AND `reward_point_criterias`.`criteria` = :criteria
                                        )
                                    LEFT JOIN `reward_points_scored` ON
                                        (
                                            `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                            AND `reward_points_scored`.`user_id` = :user_id
                                            AND `reward_points_scored`.`status` = 'ACTIVE'
                                        )
                                    WHERE    `reward_point_criterias`.`course_id` = :course_id
                                        AND `subject_assignments`.`subject_id` = :subject_id
                                        AND `subject_assignments`.`status` = :status
                                        AND `reward_point_criterias`.`status` = :status
                                    ");

            $stmt1->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt1->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt1->bindValue(':course_id', 0, PDO::PARAM_INT);
            $stmt1->bindValue(':criteria', "SUBJECT_ASSIGNMENT", PDO::PARAM_STR);
            $stmt1->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt1->execute();
            $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

            foreach($result1 as $oneAssignment){
                if($oneAssignment['score']==null){
                    array_push($pendingAssignments1, $oneAssignment);
                }
                else{
                    array_push($submittedAssignments1, $oneAssignment);
                }
            }

            if(count($result) === 0){
                return null;
            }


            $submittedAssignments = array();
            $pendingAssignments = array();
            $score = 0;
            $minOfScores = 0;
            $totalScorePossible = 0;
            foreach (array_merge($submittedAssignments1,$pendingAssignments1) as $value) {
                //get the min of scores for scale
                if($minOfScores == 0){
                    //First time load the value
                    $minOfScores = $value['max_score'];
                }
                elseif ($value['max_score'] < $minOfScores) {
                    //Load if value is less than the current value in $minOfScores
                    $minOfScores = $value['max_score'];
                }

                //Add value to $totalScorePossible
                $totalScorePossible += $value['max_score'];

                if($value['score'] != null){
                    array_push($submittedAssignments, array(
                        "label" => $value['name'],
                        "arcWidth" => $value['max_score'],
                        "value" => $value['max_score']
                    ));
                    //Add the value to score earned
                    $score += $value['max_score'];
                }else{
                    array_push($pendingAssignments, array(
                        "label" => $value['name'],
                        "arcWidth" => $value['max_score'],
                        "value" => $value['max_score']
                    ));
                }
            }
            //Now merge both submitted and pending assignments into one. Submitted ones go first
            $data = array_merge($submittedAssignments, $pendingAssignments);

            $inputDataArray['enablelabel'] = false;//Should the labes be displayed over arc sections or not?
//            $inputDataArray['labelText'] = array( //It is not required when enablelabel is false
//                "fontSize" => "5px",
//                "color" => "#737373"
//            );
            $inputDataArray['colorRange'] = array(//Range of colors to be used for arc sections
                "start" => "#69bbc9",
                "end" => "#02435C"
            );
            $inputDataArray['enableNeedle'] = true;//Needle to be displayed?
            $inputDataArray['needleProperties'] = array(//It is not required when enableNeedle is false
                "needleColor" => "black",
                "needleWidth" => "2px"
            );
            $inputDataArray['enableCenterText'] = true;//Should we be displaying text in the center of chart
            $inputDataArray['centerText'] = array(//Its not required when enableCenterText is false
                "color" => "black",
                "fontSize" => "14"
            );
            $inputDataArray['enableTooltip'] = true;//Should we be displaying enableTooltip for each arc section?
            $inputDataArray['tooltip'] = array(//Its not required when enableTooltip is false
                "dx" => "30",
                "dy" => "30"
            );
            $inputDataArray['scaleStart'] = 0;//Start value of the scale
            $inputDataArray['scaleEnd'] = $totalScorePossible;//end value of the scale
            $inputDataArray['majorScaleSpan'] = $minOfScores;
            $inputDataArray['minorScaleSpan'] = round($minOfScores / 2, 1);
            $inputDataArray['value'] = $score;
            $inputDataArray['data'] = $data;

            return json_encode($inputDataArray);
        } catch (PDOException $e) {
            return null;
        }
    }
}
