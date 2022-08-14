<?php

namespace App;

use App\Models\User;
use App\Models\RememberedLogin;
use \App\EventLoger;
use App\Models\Messages;

/**
 * Authentication
 *
 * PHP version 7.0
 */
class Auth {

    /**
     * Login the user
     *
     * @param User $user The user model
     * @param boolean $remember_me Remember the login if true
     *
     * @return void
     */
    public static function login($user, $remember_me, $timezone = "Asia/Kolkata") {
        session_regenerate_id(true);
        $_SESSION['role'] = $user->role;
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_last_name'] = $user->last_name;
        $_SESSION['profile_pic_binary'] = $user->profile_pic_binary;
        $_SESSION['email_id'] = $user->email;
        $_SESSION['clienttimezone'] = $timezone;
        $_SESSION['role'] = $user->role;
        if(trim($user->timezone) == ""){
            $_SESSION['user_timezone_configured'] = "UTC";
        }else{
            $_SESSION['user_timezone_configured'] = trim($user->timezone);
        }
        $unread_message_count = Messages::unreadMessages($user->id);
        $_SESSION['unread_messages'] =  $unread_message_count;

        if ($user->role == "PARTICIPANT") {
            $courseDetails = $user->getParticipantCourseDetails($user->id);
            $_SESSION['course_id'] = $courseDetails['course_id'];
            $_SESSION['course_name'] = $courseDetails['course_name'];
            $_SESSION['subject_id'] = $courseDetails['subject_id'];
            $_SESSION['subject'] = $courseDetails['subject'];
            $_SESSION['subjectVersion'] = $courseDetails['version'];
            $_SESSION['content_s3_bucket'] = $courseDetails['s3_bucket'];
            $_SESSION['course_org_details'] = $courseDetails['course_org_details'];
            $_SESSION['content_org_details'] = $courseDetails['content_org_details'];
            $_SESSION['navbar_links'] = $courseDetails['navbar_links'];
            $_SESSION['pending_feedbacks'] = $courseDetails['pending_feedbacks'];
        }
        else if($user->role == "FACILITATOR"){
            $facilitatorOrg = $user->getFacilitatorOrgDetails($user->id);
            if($facilitatorOrg){
                $_SESSION['facilitator_org'] = $facilitatorOrg;
            }

        }

        if ($remember_me) {

            if ($user->rememberLogin()) {

                setcookie('remember_me', $user->remember_token, $user->expiry_timestamp, '/');
            }
        }
    }

    /**
     * Logout the user
     *
     * @return void
     */
    public static function logout() {
        // Unset all of the session variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
            );
        }

        // Finally destroy the session
        session_destroy();

        static::forgetLogin();
    }

    /**
     * Remember the originally-requested page in the session
     *
     * @return void
     */
    public static function rememberRequestedPage() {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
    }

    /**
     * Get the originally-requested page to return to after requiring login, or default to the homepage
     *
     * @return void
     */
    public static function getReturnToPage() {
        return $_SESSION['return_to'] ?? '/';
    }

    /**
     * Get the current logged-in user, from the session or the remember-me cookie
     *
     * @return mixed The user model or null if not logged in
     */
    public static function getUser() {
        if (isset($_SESSION['user_id'])) {

            return User::findByID($_SESSION['user_id']);
        } else {

            return static::loginFromRememberCookie();
        }
    }

    /**
     * Login the user from a remembered login cookie
     *
     * @return mixed The user model if login cookie found; null otherwise
     */
    protected static function loginFromRememberCookie() {
        $cookie = $_COOKIE['remember_me'] ?? false;

        if ($cookie) {

            $remembered_login = RememberedLogin::findByToken($cookie);

            //if ($remembered_login) {
            if ($remembered_login && !$remembered_login->hasExpired()) {

                $user = $remembered_login->getUser();

                //eventlogging for cookies login
                $logDetails = array(
                    "type" => "cookie",
                    "remember_password" => "optedin"
                );
                EventLoger::logEvent('Login success', json_encode($logDetails), $user->id);
                static::login($user, false);

                return $user;
            }
        }
    }

    /**
     * Forget the remembered login, if present
     *
     * @return void
     */
    protected static function forgetLogin() {
        $cookie = $_COOKIE['remember_me'] ?? false;

        if ($cookie) {

            $remembered_login = RememberedLogin::findByToken($cookie);

            if ($remembered_login) {

                $remembered_login->delete();
            }

            setcookie('remember_me', '', time() - 3600);  // set to expire in the past
        }
    }

}
