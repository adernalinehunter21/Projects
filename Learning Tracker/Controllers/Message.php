<?php

namespace App\Controllers;

use \Core\View;
use App\Models\Messages;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;
use \App\Models\Subjects;
use \App\Models\Courses;
use App\s3;


/**
 * FAQ controller
 *
 * PHP version 7.0
 */
class Message extends Authenticated {

    /**
     * Show the Messages page
     * from this function we are passing active tab and user details to the Messages view page
     * @return void
     */
    public function newAction() {
        $unread_message_count = Messages::unreadMessages($_SESSION['user_id']);
        $_SESSION['unread_messages'] = $unread_message_count;
        $role = $_SESSION['role'];
        if ($role == 'PARTICIPANT') {
            $this->LearnerMessageInbox();
        } elseif ($role == 'FACILITATOR') {
            $this->FacilitatorMessageInbox();
        }
    }

    private function Message($tab_name) {
        $user_id = $_SESSION['user_id'];
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $message_details = Messages::getMessageDetails($user_id, $course_id);
        $banners = Messages::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        View::renderTemplate(
                'Messages/Messages.html',
                ['activeTab' => $tab_name,
                    "user_details" => $this->user_details,
                    "message_details" => $message_details,
                    'resourceTypeList' => $resourceTypeList,
                    'banner_details' => $banners,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
                ]
        );
    }

    private function FacilitatorMessage() {
        $user_id = $_SESSION['user_id'];
        $message_details = Messages::getFacilitatorMessageDetails($user_id);

        View::renderTemplate(
                'Messages/FacilitatorMessage.html',
                [
                    "user_details" => $this->user_details,
                    "message_details" => $message_details,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "Superglobal_session" => $_SESSION
                ]
        );
    }

    private function FacilitatorMessageInbox() {
        $user_id = $_SESSION['user_id'];
        $message_type = 'MESSAGE';
        $response = Messages::getMessages($user_id, $message_type);
        if ($response['status'] == "Success") {
            $message_details = $response['data'];
        }
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);
        $firstSubjectCourses = array();
        if (count($subjectList) > 0) {
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'], $user_id);
        }
        $resourceTypes = ResourceLibraries::getResourceTypesAcrossTheSystem();
        View::renderTemplate(
                'Messages/FacilitatorMessageInbox.html',
                [
                    "user_details" => $this->user_details,
                    "message_details" => $message_details,
                    'subjectList' => $subjectList,
                    'firstSubjectProgramList' => $firstSubjectCourses,
                    'resourceTypeList' => $resourceTypes,
                    "Superglobal_session" => $_SESSION
                ]
        );
    }

    public function viewAction() {
        $thread_id = $this->route_params['token'];
        $participants = Messages::getUsersOfTheThread($thread_id);
        $user_id = $_SESSION['user_id'];
        $message_type = "MESSAGE";
        $messages = Messages::getMessagesOfTheThread($user_id, $thread_id, $message_type);
        $read_status = Messages::markThreadAsRead($user_id, $thread_id);
        if ($messages['status'] == "Success") {
            $message_data = $messages['messages'];
            $message_subject = $messages['subject'];
        }

        $role = $_SESSION['role'];
        if ($role == 'PARTICIPANT') {
            $tab_name = "Home";
            $user_id = $_SESSION['user_id'];
            $course_id = $_SESSION['course_id'];
            $subject_id = $_SESSION['subject_id'];
            $moduleList = \App\Models\Modules::getModuleList($subject_id);
            $resourceTypeList = ResourceLibraries::getTypeList($course_id);
            $attachment_file_prefix = 'ATTACH'.$user_id.date('y_m_d');
            $s3Details4AttachmentUpload = s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1');

            View::renderTemplate(
                    'Messages/LearnerMessageView.html',
                    ["message_details" => $message_data,
                        "thread_id" => $thread_id,
                        "subject" => $message_subject,
                        "user_id" => $user_id,
                        "Superglobal_session" => $_SESSION,
                        'activeTab' => $tab_name,
                        "user_details" => $this->user_details,
                        'resourceTypeList' => $resourceTypeList,
                        "module_list" => $moduleList,
                        "course_org_details" => $_SESSION['course_org_details'],
                        "content_org_details" => $_SESSION['content_org_details'],
                        "navbar_links" => $_SESSION['navbar_links'],
                        "s3_details_4_attachment_upload" => $s3Details4AttachmentUpload,
                        'attachment_file_prefix' => $attachment_file_prefix,
                        'participants' => $participants,
                        "Superglobal_session" => $_SESSION
                    ]
            );
        } elseif ($role == 'FACILITATOR') {
            $user_id = $_SESSION['user_id'];
            $s3Details4AttachmentUpload = s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1');
            $attachment_file_prefix = 'ATTACH'.$user_id.date('y_m_d');
            View::renderTemplate(
                  'Messages/FacilitatorMessageView.html',
                  ["message_details" => $message_data,
                  "subject" => $message_subject,
                  "user_id" => $user_id,
                  "s3_details_4_attachment_upload" => $s3Details4AttachmentUpload,
                  'attachment_file_prefix' => $attachment_file_prefix,
                  'participants' => $participants,
                  "Superglobal_session" => $_SESSION,
                  ]
            );
        }

    }

    public function newLearnerMessageAction() {
        $to = $_POST['to'];
        $message = $_POST['message'];
        $subject = $_POST['subject'];
        $course_id = $_SESSION['course_id'];
        $message_type = 'MESSAGE';
        if (isset($_POST['attachments'])){
          $attachments =  $_POST['attachments'];
        }
        else {
          $attachments = [];
        }
        $type = "MESSAGE";
        $log_response = Messages::logNewMessage($to, $subject, $message, $course_id,$type,$attachments);
        if (isset($log_response['status']) && $log_response['status'] == "Success") {
            $message_id = $log_response['message_id'];
            $notificationResponse =  Messages::notify($message_id, $message, $attachments, $message_type);
            if (isset($notificationResponse['status']) && $notificationResponse['status'] == "Success")
            {
               $response = $notificationResponse;

            }
            else {
                $response = array(
                    "status" => "Error",
                    "error" => "Encountered an issue while sending the messages.Please retry."
                );
            }
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Encountered an issue while sending the messages.Please retry."
            );

        }
        echo json_encode($response);
    }

    public function newFacilitatorMessageAction() {

        $course_id = $_POST['courseId'];
        $to = $_POST['to'];
        $message = $_POST['message'];
        $type = 'MESSAGE';
        $subject = $_POST['subject'];
        if (isset($_POST['attachments'])){
          $attachments =  $_POST['attachments'];
        }
        else {
          $attachments = [];
        }
        $type = 'MESSAGE';
        $log_response = Messages::logNewMessage($to, $subject, $message, $course_id ,$type, $attachments);
        if (isset($log_response['status']) && $log_response['status'] == "Success") {
            $message_id = $log_response['message_id'];
            $notificationResponse =  Messages::notify($message_id, $message, $attachments, $message_type);
            if (isset($notificationResponse['status']) && $notificationResponse['status'] == "Success")
            {
                $response = $notificationResponse;
            }
            else {
                $response = array(
                    "status" => "Error",
                    "error" => "Encountered an issue while sending the message.Please retry."
                );
            }
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Encountered an issue while sending the message.Please retry."
            );

        }
        echo json_encode($response);
    }

    public function replyAction() {
        $message_id = $_POST['message_id'];

        $user_id = $_SESSION['user_id'];

        $message = $_POST['message'];
        $message_type = 'MESSAGE';

        if (isset($_POST['attachments'])){
          $attachments =  $_POST['attachments'];

        }
        else {
          $attachments = [];
        }
        $response = Messages::replyMessage($message_id, $message, $user_id,  $attachments);

        if (isset($response['status']) && $response['status'] == "Success") {
          $current_message_id = $response['message_id'];
          $notificationResponse =  Messages::notify($current_message_id, $message, $attachments, $message_type);
            if (isset($notificationResponse['status']) && $notificationResponse['status'] == "Success")
            {
              $response = $notificationResponse;
            }
            else {
                $response = array(
                    "status" => "Error",
                    "error" => "Encountered an issue while sending the message.Please retry."
                );
            }

        }
         else {
            $response = array(
                "status" => "Error",
                "error" => "Encountered an issue while sending the message.Please retry."
            );

        }
         echo json_encode($response);

    }

    private function LearnerMessageInbox() {

        $tab_name = "";
        $message_type = 'MESSAGE';
        $user_id = $_SESSION['user_id'];
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $response = Messages::getMessages($user_id, $message_type);
        if ($response['status'] == "Success") {
            $message_details = $response['data'];
        }
        $banners = Messages::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);

        View::renderTemplate(
                'Messages/LearnerMessageInbox.html',
                ['activeTab' => $tab_name,
                    "user_details" => $this->user_details,
                    "message_details" => $message_details,
                    'resourceTypeList' => $resourceTypeList,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
                ]
        );
    }

    public function composeFacilitatorMessageAction() {
        $user_id = $_SESSION['user_id'];

        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);
        $firstSubjectCourses = array();
        if (count($subjectList) > 0) {
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'], $user_id);
        }
        $resourceTypes = ResourceLibraries::getResourceTypesAcrossTheSystem();
        $attachment_file_prefix = 'ATTACH'.$user_id.date('y_m_d');
        $s3Details4AttachmentUpload = s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1');
        View::renderTemplate(
                'Messages/ComposeFacilitatorMessage.html',
                [
                    "user_details" => $this->user_details,
                    'subjectList' => $subjectList,
                    'firstSubjectProgramList' => $firstSubjectCourses,
                    'resourceTypeList' => $resourceTypes,
                    's3_details_4_attachment_upload' => $s3Details4AttachmentUpload,
                    'attachment_file_prefix' => $attachment_file_prefix,
                    "Superglobal_session" => $_SESSION
                ]
        );
    }
    public function composeLearnerMessageAction() {
        $tab_name = "";
        $user_id = $_SESSION['user_id'];
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);


        $banners = Messages::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $attachment_file_prefix = 'ATTACH'.$user_id.date('y_m_d');
        $s3Details4AttachmentUpload = s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1');

        View::renderTemplate(
                'Messages/ComposeLearnerMessage.html',
                [
                  'activeTab' => $tab_name,
                      "user_details" => $this->user_details,
                      'resourceTypeList' => $resourceTypeList,
                      "module_list" => $moduleList,
                      's3_details_4_attachment_upload' => $s3Details4AttachmentUpload,
                      "course_org_details" => $_SESSION['course_org_details'],
                      "content_org_details" => $_SESSION['content_org_details'],
                      "navbar_links" => $_SESSION['navbar_links'],
                      'attachment_file_prefix' => $attachment_file_prefix,
                      "course_id" => $course_id,
                      "Superglobal_session" => $_SESSION
                ]
        );
    }

}
