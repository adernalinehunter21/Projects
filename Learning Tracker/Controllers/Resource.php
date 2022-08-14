<?php

namespace App\Controllers;

use \Core\View;
use \Core\Model;
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
class Resource extends Authenticated {

    /**
     * Show the ResourceLibrary page
     * from this function we are passing active tab and user details to the ResourceLibrary view page
     * @return void
     */
    public function libraryAction() {

        $this->Resource("ResourceLibrary");
    }

    private function Resource($tab_name) {

        $type = $this->route_params['token'];
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $ModSession_details = ResourceLibraries::getModSessionDetails($course_id, $subject_id);
        $resource_details = ResourceLibraries::getResourceDetails($course_id, $type);
        $banners = Model::getBannerDetails($course_id, $tab_name);
        View::renderTemplate('ResourceLibrary/new.html',
                [
                    'activeTab' => $tab_name,
                    "user_details" => $this->user_details,
                    'resource_details' => $resource_details,
                    'resourceTypeList' => $resourceTypeList,
                    'modSession_details' => $ModSession_details,
                    'active_resource_type' => $type,
                    'banner_details' => $banners,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
        ]);
    }

    public function getResourceAction() {
        $course_id = $_SESSION['course_id'];
        $textEntered = $_POST['data']['textEntered'];
        $type = array();
        if (isset($_POST['data']['type'])) {
            $type = $_POST['data']['type'];
        }
        $moduleSearch = array();
        if (isset($_POST['data']['moduleSearch'])) {
            $moduleSearch = $_POST['data']['moduleSearch'];
        }
        $sessionSearch = array();
        if (isset($_POST['data']['sessionSearch'])) {
            $sessionSearch = $_POST['data']['sessionSearch'];
        }
        $response = ResourceLibraries::getSearchResults($course_id, $textEntered, $type, $moduleSearch, $sessionSearch);
        echo json_encode($response);
    }

    /**
     * Render empty Resources page for facilitator
     * @param type $param
     */
    public function indexAction() {
//      If user is not the facilitator the redirect to home page
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
        $resourceTypes = ResourceLibraries::getResourceTypesAcrossTheSystem();

        View::renderTemplate('ResourceLibrary/index.html',
                [
                    'activeTab' => "Resource",
                    'subjectList' => $subjectList,
                    'firstSubjectProgramList' => $firstSubjectCourses,
                    'resourceTypeList' => $resourceTypes,
                    'Superglobal_session' => $_SESSION
                ]
        );
    }

    /**
     * Get the resouces of the course to be listed in facilitator's Resources page
     */
    public function getConfiguredResourcesOfTheCourseAction() {
        $resources = ResourceLibraries::getResourcesOfTheCourse($_POST['courseId'], $_POST['subjectId']);
        $s3Details4fileUpload = s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1');
        $resourceFilePrefix = "RS".$_POST['courseId'].random_int(100, 999);
        $thumbnailFilePrefix = "RSTh".$_POST['courseId'].random_int(100, 999);
        echo json_encode(array(
            "resourceData" => $resources,
            "s3Details4fileUpload" => $s3Details4fileUpload,
            "resourceFilePrefix" => $resourceFilePrefix,
            "resourceThumbnailPrefix" => $thumbnailFilePrefix
        ));
    }

    public function addAction() {

        $response = ResourceLibraries::addNewResource($_POST['data']);
        echo json_encode($response);
    }

    public function deleteResourceAction(){
        $resource_id = $_POST['resourceId'];
        $resource_is_of = $_POST['resource_is_of'];
        $deleteResource = ResourceLibraries::deleteResource($resource_id,$resource_is_of);
        echo json_encode($deleteResource);
    }
}
