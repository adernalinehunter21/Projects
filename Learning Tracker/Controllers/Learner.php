<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\Courses;
use \App\Models\User;
use App\Models\Configurations;
use \App\Models\Subjects;
use \App\Mail;

class Learner extends AuthenticatedFacilitator {

    public function indexAction() {
        $user_id = $_SESSION['user_id'];

        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        $firstSubjectCourses = array();
        if (count($subjectList) > 0) {
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'], $user_id);
        }

        View::renderTemplate('Learners/Index.html',
                [
                    'activeTab' => "Learners",
                    'subjectList' => $subjectList,
                    'firstSubjectProgramList' => $firstSubjectCourses,
                    'Superglobal_session' => $_SESSION
                ]
        );
    }

    public function getAction() {
        $course_id = $_POST['course_id'];
        $facilitator_user_id = $_SESSION['user_id'];

        if (Courses::isCourseMappedToFacilitator($course_id, $facilitator_user_id)) {
            $response = Courses::getLearners($course_id);
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Access denied for the selected course. Please consult our support"
            );
        }
        echo json_encode($response);
    }

    public function addAction() {

        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $full_name = $first_name . " " . $last_name;
        $email = trim($_POST['email']);
        if(isset($_POST['notify_user_flag'])){
            $notify_flag = $_POST['notify_user_flag'];
        }
        else{
            $notify_flag = "on";
        }
        $facilitator_user_id = $_SESSION['user_id'];

        $course_id = $_POST['course_id'];

        if (Courses::isCourseMappedToFacilitator($course_id, $facilitator_user_id)) {
            $password = $this->generateRandomString(10);

            $user_details = array(
                "name" => $first_name,
                "last_name" => $last_name,
                "email" => $email,
                "password" => $password,
                "first_time_password" => $password
            );

            $user = new User($user_details);

            if ($user->save()) {
                $user = User::authenticate($email, $password);
                if ($user->mapUserToCourse($course_id, "PARTICIPANT")) {
                    if($notify_flag === "on"){
                        $this->sendInvitationEmailToParticipant($full_name, $email, $password, $course_id, $user_details);
                    }

                    $response = array(
                        "status" => "Success"
                    );
                } else {
                    $response = array(
                        "status" => "Error",
                        "error" => "Failed to map the course to user"
                    );
                }
            } else {
                $response = array(
                    "status" => "Error",
                    "error" => $user->errors
                );
            }
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Access denied for the selected course. Please consult our support"
            );
        }
        echo json_encode($response);
    }

    public function resendPasswordAction() {

        $facilitator_user_id = $_SESSION['user_id'];
        $course_id = $_POST['course_id'];
        $user_id = $_POST['user_id'];

        if (Courses::isCourseMappedToFacilitator($course_id, $facilitator_user_id)) {
            $user = User::getFirstTimePasswordOfParticipant($course_id, $user_id);


            if ($user) {
                if ($user['first_time_password']) {

                    $this->sendInvitationEmailToParticipant($user['name'], $user['email'], $user['first_time_password'], $course_id, $user);
                    $response = array(
                        "status" => "Success"
                    );
                } else {
                    $response = array(
                        "status" => "Error",
                        "error" => "Technical issue, needs to be reported"
                    );
                }
            } else {
                $response = array(
                    "status" => "Error",
                    "error" => "Couldn't get the first time password of the user. \nPossible reasons\n->User already activated\n->Technical issue, needs to be reported"
                );
            }
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Access denied. Only facilitators of the course are allowed to resnd the password"
            );
        }
        echo json_encode($response);
    }

    public function updateAction() {

        $user_id = $_POST['user_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $full_name = $first_name . " " . $last_name;
        $email = trim($_POST['email']);
        if(isset($_POST['notify_user_flag'])){
            $notify_flag = $_POST['notify_user_flag'];
        }
        else{
            $notify_flag = "on";
        }

        $facilitator_user_id = $_SESSION['user_id'];

        $course_id = $_POST['course_id'];

        if (Courses::isCourseMappedToFacilitator($course_id, $facilitator_user_id)) {
            $user = User::findByID($user_id);

            if (!$user) {
                $response = array(
                    "status" => "Error",
                    "error" => "Encountered and error while finding this user"
                );
            } elseif ($user->is_active != 0) {
                $response = array(
                    "status" => "Error",
                    "error" => "User has already activated therefore update is deferred"
                );
            } else {
                $result = User::updateUser($user_id, $first_name, $last_name, $email);
                if ($result) {
                    if($notify_flag === "on"){
                        $user_details = (array) User::findByID($user_id);
                        $this->sendInvitationEmailToParticipant($user_details['name'], $user_details['email'], $user_details['first_time_password'], $course_id, $user_details);
                    }
                    $response = array(
                        "status" => "Success"
                    );
                } else {
                    $response = array(
                        "status" => "Error",
                        "error" => "Encountered an error while updating user details"
                    );
                }
            }
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Access denied"
            );
        }
        echo json_encode($response);
    }

    public function deleteAction() {
        $user_id = $_POST['user_id'];
        $course_id = $_POST['course_id'];
        $facilitator_user_id = $_SESSION['user_id'];

        if (Courses::isCourseMappedToFacilitator($course_id, $facilitator_user_id)) {
            if (Courses::isLearnerMappedToCourse($course_id, $user_id)) {

                $result = User::deleteUser($user_id);
                if ($result) {
                    $response = array(
                        "status" => "Success"
                    );
                } else {
                    $response = array(
                        "status" => "Error",
                        "error" => "Error happened while deleting the user"
                    );
                }
            } else {
                $response = array(
                    "status" => "Error",
                    "error" => "User doesn't belong to the selected course"
                );
            }
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Access denied for the selected course. Please consult our support"
            );
        }
        echo json_encode($response);
    }

    private function sendInvitationEmailToParticipant($full_name, $email, $password, $course_id, $user_details) {

        $facilitator_email = $_SESSION['email_id'];
        $facilitator_name = $_SESSION['user_name'];
        if (trim($_SESSION['user_last_name']) !== "") {
            $facilitator_name .= $_SESSION['user_last_name'];
        }
        $replyTo = $facilitator_email;
        $cc_array = [
            [
                "name" => $facilitator_name,
                "email_id" => $facilitator_email
            ]
        ];
        $bcc_array = [];

        if (trim($_SESSION['facilitator_org']['notification_email_id']) !== "") {
            $from = [
                "name" => $_SESSION['facilitator_org']['name'],
                "email" => $_SESSION['facilitator_org']['notification_email_id']
            ];
        } else {
            $from = [];
        }

        $variables_for_message = $user_details;
        $variables_for_message['facilitator_name'] = $facilitator_name;
        $variables_for_message['facilitator_email'] = $facilitator_email;

        $first_schedule = Courses::getFirstScheduleOfTheCourse($course_id);
        if (count($first_schedule) > 0) {
            $variables_for_message['first_session_index'] = $first_schedule['session_index'];
            $variables_for_message['first_session_name'] = $first_schedule['session_name'];
            $variables_for_message['first_session_date'] = date('d M Y', strtotime($first_schedule['date']));
            $variables_for_message['first_session_time'] = $first_schedule['time'] . "(UTC)";
            $variables_for_message['first_session_start_time'] = date('d M Y H:i', strtotime($first_schedule['start_timestamp'])) . "(UTC)";
            $variables_for_message['first_session_duration'] = $first_schedule['duration'];
            $variables_for_message['first_session_meeting_link'] = $first_schedule['meeting_link'];
        } else {
            $variables_for_message['first_session_index'] = "";
            $variables_for_message['first_session_name'] = "";
            $variables_for_message['first_session_date'] = "";
            $variables_for_message['first_session_time'] = "";
            $variables_for_message['first_session_start_time'] = "";
            $variables_for_message['first_session_duration'] = "";
            $variables_for_message['first_session_meeting_link'] = "";
        }

        $course_org_details = Courses::getOrgDetails($course_id);
        if (count($course_org_details) > 0) {
            $variables_for_message['course_name'] = $course_org_details['course_name'];
            $variables_for_message['course_org_name'] = $course_org_details['name'];
            $variables_for_message['course_org_logo_link'] = $course_org_details['logo_link'];
            $variables_for_message['course_org_short_logo_link'] = $course_org_details['short_logo_link'];
            $variables_for_message['course_org_website_link'] = $course_org_details['website_link'];
            $variables_for_message['course_org_domain'] = $course_org_details['custom_domain'];
            $variables_for_message['course_org_notification_email_id'] = $course_org_details['notification_email_id'];
        } else {
            $variables_for_message['course_name'] = "";
            $variables_for_message['course_org_name'] = "";
            $variables_for_message['course_org_logo_link'] = "";
            $variables_for_message['course_org_short_logo_link'] = "";
            $variables_for_message['course_org_website_link'] = "";
            $variables_for_message['course_org_domain'] = "";
            $variables_for_message['course_org_notification_email_id'] = "";
        }

        $Configurations = Configurations::getCourseConfigurations($course_id, 'Notify on addition of user');
        $templates = $this->extractSubjectAndBodyTemplates($Configurations);
        if ($templates['subject_template'] === "" || $templates['message_body_template'] === "") {
            $Configurations = Configurations::getCourseConfigurations(0, 'Notify on addition of user');
            $templates = $this->extractSubjectAndBodyTemplates($Configurations);
        }

        $emailSubject = View::returnTemplateFromString($templates['subject_template'], $variables_for_message);

        $emailBody = View::returnTemplateFromString($templates['message_body_template'], $variables_for_message);

        Mail::send(
                array(
                    "name" => $full_name,
                    "email_id" => $email
                ),
                $emailSubject,
                "",
                $emailBody,
                $replyTo,
                $cc_array,
                $bcc_array,
                $from
        );
    }

    private function extractSubjectAndBodyTemplates($Configurations) {
        $subject_template = '';
        $email_body_template = '';
        if ($Configurations && count($Configurations) > 0) {

            foreach ($Configurations as $Configuration) {

                if ($Configuration['parameter'] === "subject_template") {
                    $subject_template = $Configuration['value'];
                } elseif ($Configuration['parameter'] === "message_body_template") {
                    $email_body_template = $Configuration['value'];
                }
            }
        }
        return [
            "subject_template" => $subject_template,
            "message_body_template" => $email_body_template
        ];
    }

    private function generateRandomString($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomString = 'q7'; //lower case character and a number at the begining
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString . "^X"; //special character and a upper case letter at the end to make sure all type of characters are covered by default
    }

}
