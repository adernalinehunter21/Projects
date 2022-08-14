<?php

namespace App\Controllers;
use \Core\View;
use App\Models\Sessions;
use App\Models\Assignments;
use App\s3;
use App\Config;
use \App\EventLoger;
use Spatie\CalendarLinks\Link;
use \App\Mail;
use \DateTimeZone;
use \DateTime;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;
use \App\Models\Courses;
use \App\Models\User;

require '../Core/lib/pdfcrowd.php';
/**
 * schedule controller
 *
 * PHP version 7.0
 */
class Schedule extends AuthenticatedParticipant {

    /**
     * Show the schedule page
     * from this function we are passing active tab, user details and sessions to the schedule view
     * @return void
     */
    public function newAction() {

        $this->Schedule("Schedule");
    }

    private function Schedule($tab_name) {

        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION['user_id'];
        $subject_id = $_SESSION['subject_id'];
        $subject_version = $_SESSION['subjectVersion'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $sessions1 = Sessions::getSessionsOfTheCourse($course_id);
        $courseName = Courses::getCourseName($course_id);
        $sessions = Sessions::getSessionDetails($course_id, $user_id, $subject_id, $subject_version);

        $banners = Sessions::getBannerDetails($course_id, $tab_name);
        $teamMembers = Courses::getTeamMemberList($course_id, $user_id);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);

        $time_for_remote_link = array(
            'date' => date('d M y'),
            'time' => date('H:i', strtotime("+10 minutes"))
        );
        $s3Details = Assignments::get_s3_details($user_id);
        $s3Details4AssignmentUpload = $s3Details['s3Details4AssignmentUpload'];
        $attachment_file_prefix = $s3Details['attachment_file_prefix'];
        if($course_id <= 3){
            View::renderTemplate('Schedule/Schedule.html',
                            [
                                'courseName' => $courseName,
                                'activeTab' => $tab_name,
                                "user_details" => $this->user_details,
                                'sessions' => $sessions,
                                'banner_details' => $banners,
                                'time_for_remote_link' => $time_for_remote_link,
                                's3_details_4_assignment_upload' => $s3Details4AssignmentUpload,
                                'resourceTypeList' => $resourceTypeList,
                                'team_members' => $teamMembers,
                                "module_list" => $moduleList,
                                "course_org_details" => $_SESSION['course_org_details'],
                                "content_org_details" => $_SESSION['content_org_details'],
                                "navbar_links" => $_SESSION['navbar_links'],
                                "Superglobal_session" => $_SESSION
                            ]);
        }else{
            View::renderTemplate('Schedule/ScheduleNew.html',
                            [
                                'courseName' => $courseName,
                                'activeTab' => $tab_name,
                                "user_details" => $this->user_details,
                                'sessions' => $sessions,
                                'banner_details' => $banners,
                                'time_for_remote_link' => $time_for_remote_link,
                                's3_details_4_attachment_upload' => $s3Details4AssignmentUpload,
                                'attachment_file_prefix'=> $attachment_file_prefix,
                                'resourceTypeList' => $resourceTypeList,
                                'team_members' => $teamMembers,
                                "module_list" => $moduleList,
                                "course_org_details" => $_SESSION['course_org_details'],
                                "content_org_details" => $_SESSION['content_org_details'],
                                "navbar_links" => $_SESSION['navbar_links'],
                                "Superglobal_session" => $_SESSION
                            ]);
        }

    }

    public function detailedPdfAction() {

        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION['user_id'];
        $courseName = Courses::getCourseName($course_id);
        $sessions = Sessions::getSessionDetails($course_id, $user_id);
        $html = View::returnTemplate('Schedule/Detailed.html',
                        [
                            'courseName' => $courseName,
                            'sessions' => $sessions
        ]);
        $this->generatePdf($html, "DetailedSchedule.pdf");

        //eventlogging for downloading Detailed Schedule Pdf
        $logDetails = array(
            "type"=> "deatiledSchedule"
        );
        EventLoger::logEvent('Download schedule', json_encode($logDetails));

    }

    public function highLevelSchedulePdfAction() {

        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION['user_id'];
        if (isset($_SESSION['user_timezone_configured'])) {
            $userTimezone = $_SESSION['user_timezone_configured'];
        } else {
            $userTimezone = "UTC";
        }

        $courseName = Courses::getCourseName($course_id);
        $courseLogoAndWatermark = Courses::getCourseSubjectLogoAndWatermark($course_id);
        $orgLogo = $_SESSION['course_org_details']['logo_link'];
        $sessionsScheduleDetails = Sessions::getHighlevelScheduleForPdf($course_id, $userTimezone);

        if($sessionsScheduleDetails['status'] !== "Success"){
            $logDetails = array(
                "type" => "highlevelSchedule",
                "data" => $sessionsScheduleDetails
            );
            EventLoger::logEvent('Download schedule Error', json_encode($logDetails));
            echo "Sorry an error happened while getting data to generate the schedule pdf.<br>We request you to kindly raise a support request to our technical support";
            exit;
        }

        $html = View::returnTemplate('Schedule/Highlevel.html',
                        [
                            'courseName' => $courseName,
                            'courseLogo' => $courseLogoAndWatermark['logo'],
                            'courseOrgLogo' => $orgLogo,
                            'courseStartDate' => $sessionsScheduleDetails['courseStartDate'],
                            'courseEndDate' => $sessionsScheduleDetails['courseEndDate'],
                            'schedules' => $sessionsScheduleDetails['schedules']
                        ]);
        $footerHtml = '<hr>'
                    . '<div style="float: right;">'
                    . 'Page <span class="pdfcrowd-page-number"></span> of <span class="pdfcrowd-page-count"></span> pages'
                    . '</div>';
//                    . '<p style="color: #01435c; text-align: left;">Copyright © 2017 - 2020 <a style="color: #01435c;" href="https://www.devopsagileskills.org">'
//                    . 'DevOps Agile Skills Association LLC.</a> All rights reserved.'
//                    . '</p>';
        $this->generatePdf($html, $courseLogoAndWatermark['watermark_pdf'], $footerHtml, "HighLevelSchedule.pdf");
        //eventlogging for downloading Highlevel Schedule Pdf
        $logDetails = array(
            "type"=>"highlevelSchedule"
        );
        EventLoger::logEvent('Download schedule', json_encode($logDetails));
    }

    private function generatePdf($html, $waterMark, $footerHtml, $filename) {

        try {
            // create the API client instance
            $client = new \Pdfcrowd\HtmlToPdfClient(Config::PDFCROWD_USERNAME, Config::PDFCROWD_API_KEY);
            $client->setUseHttp(true);
            // run the conversion and write the result to a file
            if($waterMark){
                $client->setPageBackgroundUrl($waterMark);
            }

            $client->setHeaderHeight("10mm");
            $client->setFooterHeight("15mm");
            if($footerHtml){
                $client->setFooterHtml($footerHtml);
            }
            $client->setMarginTop("2mm");
            $client->setMarginBottom("5mm");
            $pdf = $client->convertString($html);
            header('Content-Type: application/pdf');
            header('Cache-Control: no-cache');
            header('Accept-Ranges: none');
            $content_disp = 'attachment';
            header("Content-Disposition: $content_disp; filename=$filename");
            // return the final PDF in the response
            echo $pdf;
            ob_flush();
            flush();
        } catch (\Pdfcrowd\Error $why) {
            // report the error
            error_log("Pdfcrowd Error: {$why}\n");

            // rethrow or handle the exception
            throw $why;
        }

    }

    /***
     * Handle the request to send calender invite for upcoming sessions
     */
    public function calenderInviteAction() {

        //Check if input parameters are fine
        if (isset($_POST['calender'])) {
            $subject = $_SESSION['subject'];
            $course_id = $_SESSION['course_id'];
            $calendar = $_POST['calender'];
            $applicationLink = $_SERVER['HTTP_HOST'];

            $response = Schedule::sendCalInvition($subject, $course_id, $calendar, $applicationLink);
        }
        else{
            $response = array(
                "status" => "Error",
                "message" => "\nInvalid request received at the server. \n\nPlease raise support request with this message"
            );
        }

        echo json_encode($response);
    }

    /***
     * Send the Calender invite for upcoming sessions of given $course_id which belongs to subject $subject
     * $calendar indicate the type of calendar: Google/Yahoo/Outlook
     */
    public static function sendCalInvition($subject, $course_id, $calendar, $applicationLink) {

        if (isset($_SESSION['user_timezone_configured'])) {
            $userTimezone = $_SESSION['user_timezone_configured'];
        } else {
            $userTimezone = "UTC";
        }
        //Get the details of all the sessions including their schedules
        $result = Sessions::getHighlevelDetailsOfFutureSessions($course_id, $userTimezone);
        if ($result['status'] == "Success") {
            if (count($result['data']) > 0) {
                $sessionDetails = $result['data'];
                //eventlogging for calender
                $logDetails = array(
                    "calender_details" => $sessionDetails
                );
                EventLoger::logEvent('Click calender', json_encode($logDetails));

            } else {
                //eventlogging for calender
                $logDetails = array(
                    "calender_details" => "error message(All seesions of the program seem to have got over by now.)"
                );
                EventLoger::logEvent('Click calender', json_encode($logDetails));

                return array(
                    "status" => "Error",
                    "message" => "\nAll seesions of the program seem to have got over by now. \n\nPlease check and raise support request if you find an issue here.\n\nThank You"
                );
            }
        } else {
            //eventlogging for calender
                $logDetails = array(
                    "calender_details" => "error message(There was an error while retrieving session schedule.)"
                );
                EventLoger::logEvent('Click calender', json_encode($logDetails));
            return array(
                "status" => "Error",
                "message" => "\nThere was an error while retrieving session schedule. \n\nRequest you to kindly raise a support request with this message"
            );
        }

        $sessionDetailsForEmail = array();
        foreach ($sessionDetails as $oneSessionDetails) {
            $link = Schedule::getLink($subject, $oneSessionDetails, $calendar);
            array_push($sessionDetailsForEmail, array(
                "name" => $oneSessionDetails['session_name'],
                "index" => $oneSessionDetails['session_index'],
                "link" => $link
            ));
        }
        $userName = $_SESSION['user_name'];
        $user = User::getUserDetails($user_id);
        $userName = $user['name'];
        $emailBody = View::returnTemplate('Schedule/ScheduleCalendarInviteEmail.html', [
                    'name' => $userName,
                    'calendar' => $calendar,
                    'timezone' => $userTimezone,
                    'sessions' => $sessionDetailsForEmail,
                    'appLink' => $applicationLink
        ]);

        $userEmailId = $_SESSION['email_id'];
        $emailSubject = $subject . " Schedules";
        try {
            Mail::send($userEmailId, $emailSubject, "", $emailBody);

            return array(
                "status" => "Success",
                "message" => "An email with calender links has been sent\n\nThank You",
            );
        } catch (Exception $e) {
            return array(
                "status" => "Error",
                "message" => "Error:".$e->errorMessage()                
            );
        }

    }
    

    /***
     * Generate the calendar invite link for given $calender with $subject and $oneSessionDetails
     */
    private static function getLink($subject, $oneSessionDetails, $calender) {

        $startTime = date('Y-m-d H:i', strtotime($oneSessionDetails['start_timestamp']));

        $duration = $oneSessionDetails['duration'];
        $durationArray = explode(':', $duration);
        $durationHours = (int) $durationArray[0];
        $durationMinutes = (int) $durationArray[1];

        $endTime = date('Y-m-d H:i', strtotime("+$durationHours hour +$durationMinutes minutes", strtotime($startTime)));

        $from = DateTime::createFromFormat('Y-m-d H:i', $startTime);
        $to = DateTime::createFromFormat('Y-m-d H:i', $endTime);

        $sessionIndex = $oneSessionDetails['session_index'];
        $sessionName = $oneSessionDetails['session_name'];
        $sessionDescription = $sessionName;
        if (isset($oneSessionDetails['meeting_link'])) {
            $sessionDescription .= '<br><a href="' . $oneSessionDetails['meeting_link'] . '">Click here to join the meeting</a>';
        }

        $linkObject = Link::create("$subject Session $sessionIndex", $from, $to)
                ->description($sessionDescription);

        switch ($calender) {
            case "Google":
                $link = $linkObject->google();

                break;
            case "Yahoo":
                $link = $linkObject->yahoo();

                break;
            case "Outlook":
                $link = $linkObject->webOutlook();

                break;
            case "iCal":
                $link = $linkObject->ics();

                break;
            default:
                $link = null;
        }
        return $link;
    }

     public static function getCalendarTable($subject, $course_id, $applicationLink, $sessionId,
        $data) {

        if (isset($_SESSION['user_timezone_configured'])) {
            $userTimezone = $_SESSION['user_timezone_configured'];
        } else {
            $userTimezone = "UTC";
        }
      
        $sessionDetailsForMessage = array();
        $google_link = Schedule::getAddSessionToCalendarLink($subject, $data, "Google");
        $yahoo_link = Schedule::getAddSessionToCalendarLink($subject, $data, "Yahoo");
        $outlook_link = Schedule::getAddSessionToCalendarLink($subject, $data, "Outlook");
        array_push($sessionDetailsForMessage, array(
        'type' => "Calendar Link Table",
        'source' => "calendar_links",
        'name' => "Calendar Link Table",
        "google_link" => $google_link,
        "yahoo_link" => $yahoo_link,
        "outlook_link" => $outlook_link
        ));

        $userName = $_SESSION['user_name'];
        
        $emailBody = View::returnTemplate('Schedule/CalendarLinksForSession.html', [
                    'name' => $userName,
                    'timezone' => $userTimezone,
                    'sessions' => $sessionDetailsForMessage,
                    'appLink' => $applicationLink
        ]);

        try {
            return $sessionDetailsForMessage;
        } catch (Exception $e) {
            return array(
                "status" => "Error",
                "message" => "Error:".$e->errorMessage()                
            );
        }

    }

    /***
     * Generate the calendar invite link for given $calender with $subject and $oneSessionDetails
     */
    private static function getAddSessionToCalendarLink($subject, $data, $calender) {

        $session_start_time_utc = gmdate('Y-m-d H:i', strtotime($data['session_start_date_time']));
        $duration = $data['session_duration'];
        $durationArray = explode(':', $duration);
        $durationHours = (int) $durationArray[0];
        $durationMinutes = (int) $durationArray[1];

        $endTime = date('Y-m-d H:i', strtotime("+$durationHours hour +$durationMinutes minutes", strtotime($session_start_time_utc)));
        $from = DateTime::createFromFormat('Y-m-d H:i', $session_start_time_utc, new DateTimeZone('UTC'));
        $to = DateTime::createFromFormat('Y-m-d H:i', $endTime, new DateTimeZone('UTC'));
       
        $sessionIndex = $data['session_index'];
        $sessionName = $data['session_name'];
        $sessionDescription = $sessionName;
        if (isset($data['remote_meeting_link'])) {
            $sessionDescription .= '<br><a href="' . $data['remote_meeting_link'] . '">Click here to join the meeting</a>';
        }
        $timezone = 'UTC';
        $linkObject = Link::create("$subject Session $sessionIndex", $from, $to)
                ->description($sessionDescription);

        switch ($calender) {
            case "Google":
                $link = $linkObject->google();

                break;
            case "Yahoo":
                $link = $linkObject->yahoo();

                break;
            case "Outlook":
                $link = $linkObject->webOutlook();

                break;
            case "iCal":
                $link = $linkObject->ics();

                break;
            default:
                $link = null;
        }
        return $link;
    }



}
