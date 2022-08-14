<?php

namespace App\Controllers;

use \Core\View;
use \Core\Model;
use \App\Models\Organisations;
use \App\Models\Subjects;
use \App\Models\Courses;
use App\s3;

/**
 * module controller
 *
 * PHP version 7.0
 */
class Course extends Authenticated {

    /**
     * Load courses page
     */
    public function indexAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $user_id = $_SESSION['user_id'];
        $facilitator_org_id = $_SESSION['facilitator_org']['org_id'];

        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        $facilitatorsOfTheOrg = Organisations::getFacilititators($facilitator_org_id);

        View::renderTemplate('Course/index.html',
                [
                    'activeTab' => "Courses",
                    'subjectList' => $subjectList,
                    'facilitators' => $facilitatorsOfTheOrg,
                    'Superglobal_session' => $_SESSION
                ]
        );
    }

    /**
     * Serve an ajax request to create new session
     */
    public function addNewAction() {

        $subject_id = $_POST['subject_id'];
        $course_name = $_POST['course_name'];
        if (isset($_POST['co_facilitators'])) {
            $facilitarors = $_POST['co_facilitators'];
        } else {
            $facilitarors = [];
        }
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'];

        array_push($facilitarors, [
            "id" => $user_id,
            "name" => $user_name
        ]);

        if (isset($_POST['social_media_links'])) {
            $socialMediaLinks = $_POST['social_media_links'];
        } else {
            $socialMediaLinks = [];
        }

        $facilitator_org_id = $_SESSION['facilitator_org']['org_id'];

        $response = Courses::addNewCourse($subject_id, $course_name, $facilitarors, $facilitator_org_id, $socialMediaLinks);

        echo json_encode($response);
    }

    /**
     * Return the list of courses belonging to facilitator user of the specific subject
     */
    public function listForSubjectAction() {

        if ($_SESSION['role'] !== "FACILITATOR") {
            echo json_encode(array());
            exit;
        }
        $subject_id = $_POST['subjectId'];
        $user_id = $_SESSION['user_id'];

        $course_list = Courses::getSubjectCoursesOfFacilitator($subject_id, $user_id);
        if (is_array($course_list)) {
            echo json_encode(array(
                "status" => "Success",
                "course_list" => $course_list
            ));
        } else {
            echo json_encode(array(
                "status" => "Error",
                "message" => "An error happened while trying to load the programs of the selected subject.\nPlease try again"
            ));
        }
    }

    /*
      Serve the ajax request to get the course details to be shown in Facilitator login
     */

    public function getCoursesAction() {

        if ($_SESSION['role'] !== "FACILITATOR") {
            echo json_encode(array());
            exit;
        }
        $subject_id = $_POST['subject_id'];
        $user_id = $_SESSION['user_id'];
        $response = Courses::getSubjectCoursesWithDetails($subject_id, $user_id);
        echo json_encode($response);
    }

    /**
     * Serve the ajax request to delete a course
     * */
    public function deleteAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            echo json_encode(array());
            exit;
        }
        $course_id = $_POST['course_id'];
        $user_id = $_SESSION['user_id'];
        $response = array();
        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {
            $response = Courses::deleteCourse($course_id);
            echo json_encode($response);
        }
    }

    public function usersOfTheCourseAction() {

        $course_id = $_POST['courseId'];
        $user_list = Courses::getUsersOfTheCourse($course_id);
        if (is_array($user_list)) {
            echo json_encode(array(
                "status" => "Success",
                "user_list" => $user_list
            ));
        } else {
            echo json_encode(array(
                "status" => "Error",
                "message" => "An error happened while trying to load the programs of the selected subject.\nPlease try again"
            ));
        }
    }

}
