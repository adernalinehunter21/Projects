<?php

namespace App\Controllers;

use \Core\View;
use App\Models\FacilitatedGroups;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;

/**
 * facilitated group controller
 *
 * PHP version 7.0
 */
class Facilitatedgroup extends AuthenticatedParticipant {

    /**
     * Show the facilitated group  page
     * from this function we are passing active tab, user details and facilitated group details to facilitated view
     * @return void
     */
    public function newAction() {
        $this->Facilitatedgroup("Facilitated Group");
    }

    private function Facilitatedgroup($tab_name) {

        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $facilitated_group_details = FacilitatedGroups::getFacilitatedDetails($course_id);
        $banners = FacilitatedGroups::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        View::renderTemplate('Facilitatedgroup/Facilitatedgroup.html',
                ['activeTab' => $tab_name,
                    "user_details" => $this->user_details,
                    'facilitated_details' => $facilitated_group_details,
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
