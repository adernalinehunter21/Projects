<?php

namespace App\Controllers;

use \App\EventLoger;
use \Core\Model;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ExternalLink extends Authenticated {

    /**
     * Link in the nav bar has been clicked.
     * Redirect the user to configured link and log the event
     * Note: all social media links of navbar have been loaded to session so check there for the configured link
     */
    public function navbarLinkAction() {
        $platform = $this->route_params['token'];
        $navbarLinks = $_SESSION['navbar_links'];
        foreach ($navbarLinks as $navbarLink) {
            if ($navbarLink['social_media_platform'] === $platform) {

                $logDetails = array(
                    "link_visited" => $navbarLink['social_media_platform']
                );
                EventLoger::logEvent('Click slack link', json_encode($logDetails));

                header("location: " . $navbarLink['link']);
                exit;
            }
        }
        echo "Sorry, there seem to be an issue with navbar link configuration. We request you to kindly raise a support request in this regard";
    }

    public function topBannerAction() {
        $tab1 = $this->route_params['token'];
        $course_id = $_SESSION['course_id'];
        $banner_details = Model::getBannerDetails($course_id, $tab1, 'TOP');

        $topBannerDetails = array_pop($banner_details);
        $link = $topBannerDetails['button_link'];
        header("location: {$link}");
        $logDetails = array(
            "button_clicked_details" => $topBannerDetails
        );
        EventLoger::logEvent('Click top banner button', json_encode($logDetails));
    }

    public function bottomBannerAction() {
        $tab1 = $this->route_params['token'];
        $course_id = $_SESSION['course_id'];
        $banner_details = Model::getBannerDetails($course_id, $tab1, 'BOTTOM');

        $buttomBannerDetails = array_pop($banner_details);
        $link = $buttomBannerDetails['button_link'];
        header("location: {$link}");
        $logDetails = array(
            "button_clicked_details" => $buttomBannerDetails
        );
        EventLoger::logEvent('Click bottom banner button', json_encode($logDetails));
    }

    public function footerFacebookAction() {
        header("location: https://www.facebook.com/");
        $logDetails = array(
            "link_visited" => "facebook"
        );
        EventLoger::logEvent('Click footer facebook link', json_encode($logDetails));
    }

    public function footerLinkedInAction() {
        header("location: https://www.linkedin.com/");
        $logDetails = array(
            "link_visited" => "linkedIn"
        );
        EventLoger::logEvent('Click footer linkedIn link', json_encode($logDetails));
    }

    public function footerTwitterAction() {
        header("location: https://www.twitter.com/");
        $logDetails = array(
            "link_visited" => "twitter"
        );
        EventLoger::logEvent('Click footer twitter link', json_encode($logDetails));
    }

    public function footerInstagramAction() {
        header("location: https://www.instagram.com/");
        $logDetails = array(
            "link_visited" => "instagram"
        );
        EventLoger::logEvent('Click footer instagram link', json_encode($logDetails));
    }

    public function footerPrivacyPolicyAction() {
        header("location: /");
        $logDetails = array(
            "link_visited" => "privacy_policy"
        );
        EventLoger::logEvent('Click footer privacy policy', json_encode($logDetails));
    }

    public function footerTermsOfUseAction() {
        header("location: /");
        $logDetails = array(
            "link_visited" => "terms_of_use"
        );
        EventLoger::logEvent('Click footer terms of use', json_encode($logDetails));
    }

    public function contentOrgLogoAction() {
        $logDetails = array(
            "logo_visited" => $_SESSION['content_org_details']['name']
        );
        EventLoger::logEvent('Click logo', json_encode($logDetails));

        header("location: " . $_SESSION['content_org_details']['website_link'], true, 301);
        exit;
    }

    public function courseOrgLogoAction() {
        $logDetails = array(
            "logo_visited" => $_SESSION['course_org_details']['name']
        );
        EventLoger::logEvent('Click logo', json_encode($logDetails));

        header("location: " . $_SESSION['course_org_details']['website_link'], true, 301);
        exit;
    }

}
