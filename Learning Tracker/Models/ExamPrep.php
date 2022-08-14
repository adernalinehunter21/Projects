<?php

namespace App\Models;

use PDO;
use App\s3;

/**
 * modules model
 *
 * PHP version 5.4
 */
class ExamPrep extends \Core\Model {

	public static function getSummaryDataOfUser($subject_id,$user_id){
            $db = static::getDB();

            $stmt = $db->prepare("SELECT `subject_modules`.`module_id` AS `session_id`,`module_index` AS `session_index`,`module_name` AS `session_name`, `exam_prep`.`id` AS `assignment`,`reward_point_criterias`.`max_score` AS `points_allocated`, (SELECT `score` FROM `reward_points_scored` WHERE `user_id` = :user_id AND `criteria_id` = `reward_point_criterias`.`id`) AS `points_earned`

                FROM `subject_modules` 
                JOIN `exam_prep` ON (`exam_prep`.`module_id` = `subject_modules`.`module_id`)
                JOIN `reward_point_criterias` ON (`exam_prep`.`id` = `reward_point_criterias`.`reference_id` )

                WHERE `subject_modules`.`subject_id` = :subject_id
                AND `reward_point_criterias`.`criteria` = 'EXAM_PREP'
                AND `reward_point_criterias`.`status` = :status
                AND `exam_prep`.`status` = :status ");

                $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->bindValue(':criteria', 'EXAM_PREP', PDO::PARAM_STR);
                 
                $stmt->execute();
                
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $assignments = array();

                $pendingAssignments =array();
                foreach($result as $oneAssignment) {

                    $session_id = $oneAssignment['session_id'];
                    if (!isset($assignments[$session_id])) {
                        # code...
                        $assignments[$session_id] = array(
                            "session_index" => $oneAssignment['session_index'],
                            "session_name" => $oneAssignment['session_name'], 
                            "points_earned" => 0,
                            "earnings" => array(),
                            "points_available" => 0,
                            "available_assignments" =>array()
                        );
                    }

                    if($oneAssignment['points_earned']== NULL){
                        
                        array_push($assignments[$session_id]['available_assignments'],
                            array(
                                "Assignment" => $oneAssignment['assignment'],
                                "points_allocated" => $oneAssignment['points_allocated'],
                            )
                        );
                        $assignments[$session_id]['points_available'] += $oneAssignment['points_allocated'];

                        array_push($pendingAssignments, array(
                            "session_index" => $oneAssignment['session_index'],
                            "assignment" => 'Question '.$oneAssignment['assignment'],
                            "points_allocated" => $oneAssignment['points_allocated']
                        ));
                    }
                    else{
                        
                        array_push($assignments[$session_id]['earnings'],
                            array(
                                "assignment" => 'Question '.$oneAssignment['assignment'],
                                "points_allocated" => $oneAssignment['points_allocated'],
                                "points_earned" => $oneAssignment['points_earned']
                            )
                        );
                        $assignments[$session_id]['points_earned'] += $oneAssignment['points_earned'];

                    }

                }
                $dataForChart = array();
                foreach($assignments as $assignment){
                    array_push($dataForChart, $assignment);
                }
 
            return array(

                "dataForChart" => $dataForChart,
                "pendingAssignments" => $pendingAssignments
            );


    }
}