<?php

namespace App\Controllers;

use \Core\View;
use \Core\Model;
use \App\Models\Organisations;
use \App\Models\Subjects;
use \App\Models\Courses;
use \App\Models\CourseConfigurations;
use App\s3;

/**
 * module controller
 *
 * PHP version 7.0
 */
class CourseConfiguration extends Authenticated {

    /**
     * Load courses page
     */
    public function alertsAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $user_id = $_SESSION['user_id'];
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        $firstSubjectCourses = array();
        if (count($subjectList) > 0) {
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'], $user_id);
        }

        $supportedAlerts = CourseConfigurations::getSupportedAlerts();
        View::renderTemplate('CourseConfiguration/index.html',
                [
                    'activeTab' => "CourseConfiguration",
                    'activeSubTab' => "configureAlerts",
                    'subjectList' => $subjectList,
                    'firstSubjectProgramList' => $firstSubjectCourses,
                    'supportedAlerts' => $supportedAlerts,
                    'Superglobal_session' => $_SESSION
                ]
        );

    }

    /**
     * Serve the ajax request with the list of variables supported for the Alert
     */
    public function getVariablesForAlertConfigurationAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            echo json_encode([
                'status' => "Error",
                'error' => "This data is accessible only to Facilitator"
            ]);
            exit;
        }
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Access to data of the selected course is denied"
            ]);
            exit;
        }

        $alert_id = $_POST['alert_id'];
        $variables = CourseConfigurations::getVariablesOfAlert($course_id, $alert_id);

        echo json_encode($variables);
    }


    public function getVariablesForNotificationAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            echo json_encode([
                'status' => "Error",
                'error' => "This data is accessible only to Facilitator"
            ]);
            exit;
        }
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Access to data of the selected course is denied"
            ]);
            exit;
        }

        $notification_id = $_POST['notification_id'];
        $variables = CourseConfigurations::getVariablesOfNotification($course_id, $notification_id);

        echo json_encode($variables);
    }

    public function addAlertAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            echo json_encode([
                'status' => 'Error',
                'error' => "Login as Facilitator and try again"
            ]);
            exit;
        }
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Action on the selected course is denied"
            ]);
            exit;
        }

        $alert_id = trim($_POST['alert_id']);
        if($alert_id != 1){
            echo json_encode([
                'status' => 'Error',
                'error' => "Alert service not supported"
            ]);
            exit;
        }

        $template = trim($_POST['template']);
        if($template === ""){
            echo json_encode([
                'status' => 'Error',
                'error' => "Template can't be empty"
            ]);
            exit;
        }

        $template_array = explode("\n", $template);
        $template_html = "";
        foreach ($template_array as $template_line){
            $template_line = trim($template_line);
            if($template_line === ""){
                $template_html .= "<br>";
            }
            else {
                $first_occurence_index = strpos($template_line, "<ul>");
                if($first_occurence_index === 0){
                    $template_html .= "<br>".$template_line;
                }
                else{
                    $template_html .= '<p>'.$template_line.'</p>';
                }
            }
        }

        $time_validation = $this->formatTimeDuration($_POST);
        if($time_validation['status'] === "Error"){
            echo json_encode($time_validation);
            exit;
        }

        $time = $time_validation['time'];

        $subject = trim($_POST['subject']);
        if($subject === ""){
            echo json_encode([
                'status' => 'Error',
                'error' => "Subject can't be empty"
            ]);
            exit;
        }

        $response = CourseConfigurations::addNewAlert($course_id, $alert_id, $time, $subject, $template_html);

        if($response['status'] === "Error"){
            echo json_encode($response);
            exit;
        }
        $alert_config_id = $response['alert_config_id'];

        $response = CourseConfigurations::addAlertEventsForFutureSessions($course_id, $time, $alert_config_id);

        if($response['status'] === "Error"){
            //If we fail to create alert events, then lets remove the configuration
            CourseConfigurations::removeAlertConfigurations($alert_config_id);
        }

        echo json_encode($response);
    }

    public function addNotificationAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            echo json_encode([
                'status' => 'Error',
                'error' => "Login as Facilitator and try again"
            ]);
            exit;
        }
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Action on the selected course is denied"
            ]);
            exit;
        }

        $notification_id = trim($_POST['notification_id']);
        $group_id = CourseConfigurations::getGroupId($notification_id);
        
        $template = trim($_POST['template']);
        if($template === ""){
            echo json_encode([
                'status' => 'Error',
                'error' => "Template can't be empty"
            ]);
            exit;
        }

        $template_array = explode("\n", $template);
        $template_html = "";
        foreach ($template_array as $template_line){
            $template_line = trim($template_line);
            if($template_line === ""){
                $template_html .= "<br>";
            }
            else {
                $first_occurence_index = strpos($template_line, "<ul>");
                if($first_occurence_index === 0){
                    $template_html .= "<br>".$template_line;
                }
                else{
                    $template_html .= '<p>'.$template_line.'</p>';
                }
            }
        }


        $subject = trim($_POST['subject']);
        if($subject === ""){
            echo json_encode([
                'status' => 'Error',
                'error' => "Subject can't be empty"
            ]);
            exit;
        }

        $response = CourseConfigurations::addNewNotification($course_id, $notification_id, $subject, $template_html, $group_id);

        if($response['status'] === "Error"){
            echo json_encode($response);
            exit;
        }

         echo json_encode([
                'status' => "Success"
            ]);

    }


    public function getAlertsAction() {
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Data of the selected course is denied"
            ]);
            exit;
        }

        $response = CourseConfigurations::getAlertConfigurations($course_id);
        echo json_encode($response);
    }


    public function getNotificationsAction() {
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Data of the selected course is denied"
            ]);
            exit;
        }

        $response = CourseConfigurations::getNotificationConfigurations($course_id);
        echo json_encode($response);
    }
     public function deleteAlertAction() {
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Action on the selected course is denied"
            ]);
            exit;
        }

        $alert_id = $_POST['alert_id'];
        if(!CourseConfigurations::isAlertMappedToCourse($alert_id, $course_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Action on the selected Alert Configuration is denied"
            ]);
            exit;
        }

        $response = CourseConfigurations::deleteAlertConfiguration($alert_id);
        echo json_encode($response);
    }

    public function deleteNotificationAction() {
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        if(!Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Action on the selected course is denied"
            ]);
            exit;
        }

        $notification_id = $_POST['notification_id'];
        if(!CourseConfigurations::isAlertMappedToCourse($notification_id, $course_id)){
            echo json_encode([
                'status' => "Error",
                'error' => "Action on the selected Notification Configuration is denied"
            ]);
            exit;
        }

        $response = CourseConfigurations::deleteNotificationConfiguration($notification_id);
        echo json_encode($response);
    }

    public function eventNotifications() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }

        $user_id = $_SESSION['user_id'];
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        $firstSubjectCourses = array();
        if (count($subjectList) > 0) {
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'], $user_id);
        }

        $supportedNotifications = CourseConfigurations::getSupportedNotifications();

        View::renderTemplate('CourseConfiguration/eventIndex.html',
            [
                'activeTab' => "CourseConfiguration",
                'activeSubTab' => "configureEventNotifications",
                'subjectList' => $subjectList,
                'firstSubjectProgramList' => $firstSubjectCourses,
                'supportedNotifications' => $supportedNotifications,
                'Superglobal_session' => $_SESSION
            ]
        );
    }

    private function formatTimeDuration($data) {
        $time = "";
        $time_in_days = $_POST['days'];
        if(!is_numeric($time_in_days) || $time_in_days < 0 || $time_in_days > 365){
            return [
                'status' => 'Error',
                'error' => 'Invalid number of days. It should be between 0 and 365'
            ];
        }
        $time_in_days = (int)$time_in_days;
        $time .= $time_in_days;

        $time_in_hours = $_POST['hours'];
        if(!is_numeric($time_in_hours) || $time_in_hours < 0 || $time_in_hours > 23){
            return [
                'status' => 'Error',
                'error' => 'Invalid number of hours. It should be between 0 and 23'
            ];
        }

        $time_in_hours = (int)$time_in_hours;
        if($time_in_hours < 10){
            $time .= " 0".$time_in_hours;
        }else{
            $time .= " ".$time_in_hours;
        }

        $time_in_minutes = $_POST['minutes'];
        if(!is_numeric($time_in_minutes) || $time_in_minutes < 0 || $time_in_minutes > 59){
            return [
                'status' => 'Error',
                'error' => 'Invalid number of minutes. It should be between 0 and 23'
            ];
        }

        $time_in_minutes = (int)$time_in_minutes;
        if($time_in_minutes < 10){
            $time .= ":0".$time_in_minutes;
        }else{
            $time .= ":".$time_in_minutes;
        }

        return [
            'status' => 'Success',
            'time' => $time
        ];
    }


}
