<?php

namespace App\Controllers;

use \Core\View;
use App\Models\Communities;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;
/**
 * community controller
 *
 * PHP version 7.0
 */
class Community extends AuthenticatedParticipant {

    /*this function shows community page 
     * from this function we are passing user details to view page
     * returns void */
    public function newAction() {

        $this->Community("Community");
    }

    private function Community($tab_name) {

        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $banners = Communities::getBannerDetails($course_id, $tab_name);
        $community_details = Communities::getCommunityDetails($course_id);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        View::renderTemplate('Community/Community.html', 
                            ['activeTab' => $tab_name, 
                                "user_details" => $this->user_details, 
                                'banner_details' => $banners, 
                                'resourceTypeList' => $resourceTypeList, 
                                "community_details" => $community_details, 
                                "module_list" => $moduleList,
                                "course_org_details" => $_SESSION['course_org_details'],
                                "content_org_details" => $_SESSION['content_org_details'],
                                "navbar_links" => $_SESSION['navbar_links'],
                                "Superglobal_session" => $_SESSION
                ]);
        
    }

}