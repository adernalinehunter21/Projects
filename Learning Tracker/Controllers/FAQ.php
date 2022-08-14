<?php

namespace App\Controllers;

use \Core\View;
use \Core\Model;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;

/**
 * FAQ controller
 *
 * PHP version 7.0
 */
class FAQ extends AuthenticatedParticipant {

    /**
     * Show the FAQ page
     * from this function we are passing active tab and user details to the FAQ view page
     * @return void
     */
    public function newAction() {

        $this->FAQ("FAQ");
    }

    private function FAQ($tab_name) {

        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $banners = Model::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        View::renderTemplate('FAQ/FAQ.html',
                ['activeTab' => $tab_name,
                    "user_details" => $this->user_details,
                    'resourceTypeList' => $resourceTypeList,
                    'banner_details' => $banners,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
        ]);
    }

}
