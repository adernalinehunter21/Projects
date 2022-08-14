<?php

namespace App\Controllers;

use \Core\View;
use App\Models\Sessions;
use \App\Models\Courses;
use \App\Models\Subjects;
use App\Models\Messages;
use App\Models\Configurations;

/**
 * module controller
 *
 * PHP version 7.0
 */
class Session extends Authenticated {

    /**
     * Get the json containing the list of sessions of the course 
     * Required to list the sessions for dorp-downs
     */
    public function getSessionsOfTheCourseAction() {

        $course_id = $_POST['data']['course_id'];
        $user_id = $_SESSION['user_id'];

        $session_list = array();
        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {
            $session_list = Sessions::getSessionsOfTheCourse($course_id);
        }

        echo json_encode($session_list);
    }

    /**
     * Get the json containing the list of sessions of the course 
     * Required to list the sessions for dorp-downs
     */
    public function getSessionDetailsForEditingAction() {

        $course_id = $_POST['data']['course_id'];
        $session_index = $_POST['data']['session_index'];
        $user_id = $_SESSION['user_id'];

        $session_list = array();
        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {
            $session_list = Sessions::getDetailsOfTheSession($course_id, $session_index);
        }

        echo json_encode($session_list);
    }

    /**
     * Display empty Schedule page for facilitator to choose program and
     * --Load configured sessions
     * --Create new session(s)
     */
    public function indexAction() {

        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('This page is available only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $user_id = $_SESSION['user_id'];

        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        $firstSubjectCourses = array();
        if (count($subjectList) > 0) {
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'], $user_id);
        }

        View::renderTemplate('Schedule/FacilitatorIndex.html',
                [
                    'activeTab' => "Schedule",
                    'subjectList' => $subjectList,
                    'firstSubjectProgramList' => $firstSubjectCourses,
                    'Superglobal_session' => $_SESSION
                ]
        );
    }

    /**
     * Serve the ajax request with the details of all sessions of the course
     * required to display the sessions under Schedule page of Facilitator
     */
    public function getSessionsWithDetailsAction() {
        $course_id = $_POST['course_id'];
        $subject_id = $_POST['subject_id'];
        $user_id = $_SESSION['user_id'];

        $session_list = array();
        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {
            $session_list = Sessions::getSessionsWithDetails($course_id);
        }

        echo json_encode($session_list);
    }

    /**
     * serve the ajax request to delete one of the session by its course_id and session_index
     */
    public function deleteSessionAction() {
        $course_id = $_POST['course_id'];
        $session_index = $_POST['session_index'];
        $user_id = $_SESSION['user_id'];

        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {
            $result = Sessions::deleteSessionByIndex($course_id, $session_index);
        } else {
            $result = array(
                "status" => "Error",
                "error" => "Not authorised to delete"
            );
        }
        echo json_encode($result);
    }

    /**
     * Serve the Ajax request to create a new Session with the data in the post body
     */
    public function createNewSessionAction() {

        $data = $_POST['data'];

        $course_id = $data['course_id'];
        $subject_id = $data['subject_id'];
        $user_id = $_SESSION['user_id'];
        
        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {
            $session_response = Sessions::createNewSession($data);

            $send_notification = $data['send_notification'];

            if( $send_notification === "true" ){

                $users = Courses::getUsersOfTheCourse($course_id);
                $to =[];

                if (isset($users['FACILITATOR'])) {
                    foreach ($users['FACILITATOR'] as $facilitator) {
                          $user = $facilitator['user_id'];
                          if (!in_array($user, $to)) {
                            array_push($to, $user);
                        }
                    }
                }
                if (isset($users['PARTICIPANT'])) {
                    foreach ($users['PARTICIPANT'] as $participant) {
                        $user = $participant['user_id'] ;
                        if (!in_array($user, $to)) {
                          array_push($to, $user);
                        }
                    }
                }
                array_push($to, $user_id);
                $variables_for_message = array();
                $course_name = Courses::getCourseName($course_id);
                $variables_for_message['course_name'] = $course_name;
                
                $variables_for_message['session_id'] = $data['session_id'];
                $variables_for_message['session_index'] = $data['session_index'];
                $variables_for_message['session_name'] = $data['session_name'];
                if($data['remote_meeting_availability'] === "Yes"){
                    $variables_for_message['meeting_link'] = $data['remote_meeting_link'];
                }
                else{
                    $variables_for_message['meeting_link'] = "";
                }
                $variables_for_message['session_topic_list'] = []; 
                foreach($data['topics'] as $topic){
                    $new_var = $topic['name'];
                    array_push($variables_for_message['session_topic_list'],$new_var);

                }

                  $course_org_details = Courses::getOrgDetails($course_id);
                if (count($course_org_details) > 0) {
                    $variables_for_message['course_org_logo'] = $course_org_details['logo_link'];
                } else {
                    $variables_for_message['course_org_logo'] = "";
                }
                $variables_for_message['start_timestamp_utc'] =  gmdate('Y-m-d H:i', strtotime($data['session_start_date_time']));
                $variables_for_message['duration'] = $data['session_duration'];

                $attachments = [];
                $user_id = $_SESSION['user_id'];
                $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);
                foreach ($subjectList as $subject_details ) {
                    if ($subject_details['subject_id'] = $subject_id) {
                        
                        $subject_name = $subject_details['subject'] ;             
                    }
                }
                $applicationLink = $_SERVER['HTTP_HOST'];

                $cal_response = Schedule::getCalendarTable($subject_name, $course_id, $applicationLink, 
                $variables_for_message['session_id'], $data);
                $variables_for_message['calendar_links'] = $cal_response[0];

                $Configurations = Configurations::getConfigurations($course_id, 4);//4 for Notify on creation of new Session
                if (count($Configurations) == 0) {
                    $Configurations = Configurations::getConfigurations(0, 4);   
                 } 
                $templates = $this->extractSubjectAndBodyTemplates($Configurations);
                $subject_configuration = View::returnTemplateFromString($templates['subject_template'], $variables_for_message);            
                $message_configuration = View::returnTemplateFromString($templates['message_body_template'], $variables_for_message);
                $type = "NOTIFICATION";
                $log_response = Messages::logNewMessage($to, $subject_configuration, $message_configuration, $course_id, 
                    $type, $attachments);
                $message_id = $log_response['message_id'];
                $notificationResponse =  Messages::notify($message_id, $message_configuration, $attachments, $type);
                $response = $notificationResponse;
            }

            if( $send_notification === "false" ){
                $response = $session_response;
            }
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Not authorised to create new session for this course"
            );
        }
  
        echo json_encode($response);
    }

    private function extractSubjectAndBodyTemplates($Configurations) {
        $subject_template = '';
        $email_body_template = '';
        if ($Configurations && count($Configurations) > 0) {

            foreach ($Configurations as $Configuration) {

                if ($Configuration['parameter'] === "subject") {
                    $subject_template = $Configuration['value'];
                } elseif ($Configuration['parameter'] === "message_body") {
                    $email_body_template = $Configuration['value'];
                }
            }
        }
        return [
            "subject_template" => $subject_template,
            "message_body_template" => $email_body_template
        ];
    }



    public function updateSessionAction() {
        $data = $_POST['data'];
        $course_id = $data['course_id'];
        $user_id = $_SESSION['user_id'];

        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {
            $response = Sessions::updateSession($data);
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Not authorised to create new session for this course"
            );
        }

        echo json_encode($response);
    }

}
