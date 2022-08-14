<?php

namespace App\Controllers;

use \Core\View;
use \Core\Model;
use \App\Auth;
use \App\Models\SessionVideos;
use \App\Models\Subjects;
use App\Models\Courses;
use App\Models\Sessions;
use App\s3;
use FFMpeg;

/**
 * Session Video controller
 *
 * PHP version 7.0
 */
class SessionVideo extends AuthenticatedFacilitator {

    /**
     * Show the session video page
     * from this function we are passing active tab and user details to Facilitator dashboard view
     * @return void
     */
    public function indexAction() {

        $this->SessionVideo("Session Video");
    }

    private function SessionVideo($tab_name) {
        $user_id = $_SESSION['user_id'];
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);
        $s3Details4VideoUpload = s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1');
        View::renderTemplate('SessionVideo/new.html',
                [
                    'activeTab' => $tab_name,
                    'subjectList' => $subjectList,
                    's3_details_4_video_upload' => $s3Details4VideoUpload,
                    "Superglobal_session" => $_SESSION
        ]);
    }

    public function getProgramListAction() {
        $data = $_POST['data'];
        $response = SessionVideos::getProgramList($data['subjectId']);
        if ($response['status'] == "Error") {
            $response = array(
                "status" => "Error",
                "message" => $response['message']
            );
        }
        echo json_encode($response);
    }

    public function getSessionListAction() {
        $data = $_POST['data'];
        $response = SessionVideos::getSessionList($data['courseId']);
        if ($response['status'] == "Error") {
            $response = array(
                "status" => "Error",
                "message" => $response['message']
            );
        }
        echo json_encode($response);
    }

    public function getTopicListAction() {
        $data = $_POST['data'];
        $response = SessionVideos::getTopicList($data['sessionId']);
        if ($response['status'] == "Error") {
            $response = array(
                "status" => "Error",
                "message" => $response['message']
            );
        }
        echo json_encode($response);
    }

    /**
     * This is a copy of getTopicListAction() with extra functionality of getting duration of the video file
     * To achieve this, ajax post body contains internal file name of the uploaded video
     */
    public function getTopicListWithVideoDuration() {

        $response = SessionVideos::getTopicList($_POST['sessionId']);
        if ($response['status'] == "Error") {
            $response = array(
                "status" => "Error",
                "message" => $response['message']
            );
        }

        //generate the link to uploaded video file
        $s3FilePath4theVideo = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $_POST['internalFileName'], 'sample.mp4');

        $ffprobe = FFMpeg\FFProbe::create();

        $videoDuration = $ffprobe->format($s3FilePath4theVideo)->get('duration');
        $response['video_duration'] = $videoDuration;
        echo json_encode($response);
    }

    public function updateVideoAction() {

        $response = SessionVideos::updateVideoDetails($_POST);

        echo json_encode($response);
    }

    public function deleteAction() {

        $video_id = $_POST['video_id'];
        $session_id = $_POST['session_id'];
        $course_id = $_POST['course_id'];
        $user_id = $_SESSION['user_id'];
        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {

            if (Sessions::isSessionBelongsToCourse($session_id, $course_id)) {
                $response = SessionVideos::removeVideo($video_id, $session_id, $user_id);
            } else {
                $response = [
                    'status' => 'Error',
                    'error' => 'Invalid session'
                ];
            }
        } else {
            $response = [
                'status' => 'Error',
                'error' => 'Access to the course denied'
            ];
        }

        echo json_encode($response);
    }

}
