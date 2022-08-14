<?php

namespace App\Models;

use PDO;
use \App\Token;
use \App\Mail;
use \Core\View;
use \App\EventLoger;

//use \App\Models\Feeddbacks;

/**
 * User model
 *
 * PHP version 7.0
 */
class User extends \Core\Model {

    /**
     * Error messages
     *
     * @var array
     */
    public $errors = [];

    /**
     * Class constructor
     *
     * @param array $data  Initial property values (optional)
     *
     * @return void
     */
    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        };
    }

    /**
     * Save the user model with the current property values
     *
     * @return boolean  True if the user was saved, false otherwise
     */
    public function save() {
        $this->validate();

        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

//            $token = new Token();
//            $hashed_token = $token->getHash();
//            $this->activation_token = $token->getValue();

            $sql = 'INSERT INTO users (name, last_name, email, password_hash, is_active, profile, phone_number, linkedin_link, facebook_link, timezone, first_time_password)
                    VALUES (:name, :last_name, :email, :password_hash, :is_active, "", "", "", "", "UTC", :first_time_password)'; //activation_hash

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
            if (!isset($this->last_name)) {
                $this->last_name = "";
            }
            $stmt->bindValue(':last_name', $this->last_name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindValue(':is_active', '0', PDO::PARAM_STR);
            $stmt->bindValue(':first_time_password', $this->password, PDO::PARAM_STR);
//            $stmt->bindValue(':activation_hash', $hashed_token, PDO::PARAM_STR);

            return $stmt->execute();
        }

        return false;
    }

    /**
     * Validate current property values, adding validation error messages to the errors array property
     *
     * @return void
     */
    public function validate() {
        // Name
        if ($this->name == '') {
            $this->errors[] = 'Name is required';
        }

        // email address
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = 'Invalid email';
        }
        if (static::emailExists($this->email, $this->id ?? null)) {
            $this->errors[] = 'email already taken';
        }

        // Password
        if (isset($this->password)) {

            if (strlen($this->password) < 6) {
                $this->errors[] = 'Please enter at least 6 characters for the password';
            }

            if (preg_match('/.*[a-z]+.*/i', $this->password) == 0) {
                $this->errors[] = 'Password needs at least one letter';
            }

            if (preg_match('/.*\d+.*/i', $this->password) == 0) {
                $this->errors[] = 'Password needs at least one number';
            }
        }
    }

    /**
     * See if a user record already exists with the specified email
     *
     * @param string $email email address to search for
     * @param string $ignore_id Return false anyway if the record found has this ID
     *
     * @return boolean  True if a record already exists with the specified email, false otherwise
     */
    public static function emailExists($email, $ignore_id = null) {
        $user = static::findByEmail($email);

        if ($user) {
            if ($user->id != $ignore_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a user model by email address
     *
     * @param string $email email address to search for
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByEmail($email) {
        $sql = 'SELECT * FROM users WHERE email = :email';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
      //* Authenticate a user by email and password.
     * Authenticate a user by email and password. User account has to be active.
     *
     * @param string $email email address
     * @param string $password password
     *
     * @return mixed  The user object or false if authentication fails
     */
    public static function authenticate($email, $password) {
        $user = static::findByEmail($email);
        if (!$user) {
            //Email ID is invalid
            return false;
        }
        if (password_verify($password, $user->password_hash)) {
            return $user;
        }

        return false;
    }

    /**
     * Find a user model by ID
     *
     * @param string $id The user ID
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByID($id) {
        $sql = 'SELECT * FROM users WHERE id = :id AND `status` = :status ';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Remember the login by inserting a new unique token into the remembered_logins table
     * for this user record
     *
     * @return boolean  True if the login was remembered successfully, false otherwise
     */
    public function rememberLogin() {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->remember_token = $token->getValue();

        $this->expiry_timestamp = time() + 60 * 60 * 24 * 30;  // 30 days from now

        $sql = 'DELETE FROM remembered_logins
                WHERE `user_id` = :user_id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            throw new Exception("Error while trying to disable previous cookies");
            exit;
        }

        $sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_at)
                VALUES (:token_hash, :user_id, :expires_at)';

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $this->expiry_timestamp), PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions to the user specified
     *
     * @param string $email The email address
     *
     * @return void
     */
    public static function sendPasswordReset($email) {
        $user = static::findByEmail($email);

        if ($user) {

            if ($user->startPasswordReset()) {

                $user->sendPasswordResetEmail();
            }
        }
    }

    /**
     * Start the password reset process by generating a new token and expiry
     *
     * @return void
     */
    protected function startPasswordReset() {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->password_reset_token = $token->getValue();

        $expiry_timestamp = time() + 60 * 60 * 2;  // 2 hours from now

        $sql = 'UPDATE users
                SET password_reset_hash = :token_hash,
                    password_reset_expires_at = :expires_at
                WHERE id = :id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions in an email to the user
     *
     * @return void
     */
    protected function sendPasswordResetEmail() {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/password/reset/' . $this->password_reset_token;

        $text = View::getTemplate('Password/reset_email.txt', ['url' => $url]);
        $html = View::getTemplate('Password/reset_email.html', ['url' => $url]);

        Mail::send($this->email, 'Password reset', $text, $html);
    }

    /**
     * Find a user model by password reset token and expiry
     *
     * @param string $token Password reset token sent to user
     *
     * @return mixed User object if found and the token hasn't expired, null otherwise
     */
    public static function findByPasswordReset($token) {
        $token = new Token($token);
        $hashed_token = $token->getHash();

        $sql = 'SELECT * FROM users
                WHERE password_reset_hash = :token_hash';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        $user = $stmt->fetch();

        if ($user) {

            // Check password reset token hasn't expired
            if (strtotime($user->password_reset_expires_at) > time()) {

                return $user;
            }
        }
    }

    /**
     * Reset the password
     *
     * @param string $password The new password
     *
     * @return boolean  True if the password was updated successfully, false otherwise
     */
    public function resetPassword($password) {
        $this->password = $password;

        $this->validate();

        //return empty($this->errors);
        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $sql = 'UPDATE users
                    SET password_hash = :password_hash,
                        password_reset_hash = NULL,
                        password_reset_expires_at = NULL
                    WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);

            //eventlogging for Password Reset
            $logDetails = array(
                "new_password" => $password_hash,
            );
            EventLoger::logEvent('Password reset', json_encode($logDetails), $this->id);
            return $stmt->execute();
        }

        return false;
    }

    /**
     * Send an email to the user containing the activation link
     *
     * @return void
     */
    public function sendActivationEmail() {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/signup/activate/' . $this->activation_token;

        $text = View::getTemplate('Signup/activation_email.txt', ['url' => $url]);
        $html = View::getTemplate('Signup/activation_email.html', ['url' => $url]);

        Mail::send($this->email, 'Account activation', $text, $html);
    }

    /**
     * Activate the user account with the specified activation token
     *
     * @param string $value Activation token from the URL
     *
     * @return void
     */
    public static function activate($value) {
        $token = new Token($value);
        $hashed_token = $token->getHash();

        $sql = 'UPDATE users
                SET is_active = 1,
                    activation_hash = null
                WHERE activation_hash = :hashed_token';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':hashed_token', $hashed_token, PDO::PARAM_STR);

        $stmt->execute();
    }

    /**
     * Update the user's profile
     *
     * @param array $data Data from the edit profile form
     *
     * @return boolean  True if the data was updated, false otherwise
     */
    public function updateProfile($data) {
        $this->name = $data['name'];

        // Only validate and update the last name if a value provided
        if ($data['lastName'] != '') {

            $this->lastName = $data['lastName'];
        }

        // Only validate and update the password if a value provided
        if ($data['password'] != '') {
            $this->password = $data['password'];
        }

        // Only validate and update the profile if a value provided
        if ($data['profile'] != '') {
            $this->profile = $data['profile'];
        }

        // Only validate and update the profile_pic if a value provided
        if ($data['profile_pic_binary'] != '') {

            $this->profile_pic_binary = $data['profile_pic_binary'];
        }

        // Only validate and update the timezone if a value provided
        if ($data['timezone'] != '') {

            $this->timezone = $data['timezone'];
        }

        // if linkedin profile is provided, load it
        if ($data['linkedin_link'] != '') {
            $this->linkedin_link = $data['linkedin_link'];
        }

        // if facebook profile is provided, load it
        if ($data['facebook_link'] != '') {
            $this->facebook_link = $data['facebook_link'];
        }

        if ($data['calender'] != '') {
            $this->calender = $data['calender'];
        }

        $this->validate();

        if (empty($this->errors)) {

            $sql = 'UPDATE users
                    SET name = :name
                        ';
            if (isset($this->lastName)) {
                $sql .= ', last_name = :last_name';
            }

            if (isset($this->timezone)) {
                $sql .= ', timezone = :timezone';
            }

            if (isset($this->profile_pic_binary)) {
                $sql .= ', profile_pic_binary = :profile_pic_binary';
            }

            if (isset($this->profile)) {
                $sql .= ', profile = :profile';
            }

            if (isset($this->linkedin_link)) {
                $sql .= ', linkedin_link = :linkedin_link';
            }

            if (isset($this->facebook_link)) {
                $sql .= ', facebook_link = :facebook_link';
            }

            if (isset($this->calender)) {
                $sql .= ', calender = :calender';
            }

            // Add password if it's set
            if (isset($this->password)) {
                $sql .= ', password_hash = :password_hash';
            }

            $sql .= "\nWHERE id = :id";

            try {
                $db = static::getDB();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
                $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

                // Add last name if it's set
                if (isset($this->lastName)) {
                    $stmt->bindValue(':last_name', $this->lastName, PDO::PARAM_STR);
                }

                // Add timezone if it's set
                if (isset($this->timezone)) {
                    $stmt->bindValue(':timezone', $this->timezone, PDO::PARAM_STR);
                }
                // Add profile_pic if it's set
                if (isset($this->profile_pic_binary)) {
                    $stmt->bindValue(':profile_pic_binary', $this->profile_pic_binary, PDO::PARAM_LOB);
                }
                // Add profile if it's set
                if (isset($this->profile)) {
                    $stmt->bindValue(':profile', $this->profile, PDO::PARAM_STR);
                }

                // Add linkedin_link if it's set
                if (isset($this->linkedin_link)) {
                    $stmt->bindValue(':linkedin_link', $this->linkedin_link, PDO::PARAM_STR);
                }
                // Add facebook_link if it's set
                if (isset($this->facebook_link)) {
                    $stmt->bindValue(':facebook_link', $this->facebook_link, PDO::PARAM_STR);
                }

                if (isset($this->calender)) {
                    $stmt->bindValue(':calender', $this->calender, PDO::PARAM_STR);
                }

                // Add password if it's set
                if (isset($this->password)) {

                    $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
                    $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
                }

                return $stmt->execute();
            } catch (PDOException $e) {

                return $e->getMessage();
            }
        }

        return false;
    }

    /*     * *
     * Change the active status of user from 0 to 1
     * This make sure, on next login and there after, user will not be landing into profile update page
     */

    public function activateUser() {
        $sql = "UPDATE users
                    SET `is_active` = 1
                WHERE `id` = :id ";
        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function getParticipantCourseId($id) {
        $sql = "SELECT `course_id`
                FROM `user_to_course_mapping`
                WHERE `user_id` = :id
                    AND `status` = 'ACTIVE'
                    AND `role` = 'PARTICIPANT'
                ORDER BY `mapping_id` ASC ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_pop($result);
    }

    public static function getUsersOfTheRole($role, $course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT *
                              FROM `user_to_course_mapping`
                                JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id` )
                              WHERE `user_to_course_mapping`.`role` = :role
                                AND `user_to_course_mapping`.`course_id` = :course_id
                                AND `user_to_course_mapping`.`status` = :status
                                AND `users`.`status` = :status
                                                        ");

        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }



    /**
     * Get details of the course and the subject that user is going through
     * @param type $userId
     * @return array(course_id, course_name, subject_id, subject, version)
     */
    public function getParticipantCourseDetails($userId) {
        $sql = "SELECT `user_to_course_mapping`.`course_id`, `course_name`,
                    `courses`.`subject_id`, `subject`, `version`,`content_org_id`, `s3_bucket`,
                    `courses`.`org_id` AS course_org_id, `content_org_id`
                FROM `user_to_course_mapping`
                    JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                    JOIN `subjects` ON (`courses`.`subject_id` = `subjects`.`id`)
                WHERE `user_id` = :id
                    AND `user_to_course_mapping`.`status` = 'ACTIVE'
                    AND `courses`.`status` = 'ACTIVE'
                    AND `subjects`.`status` = 'ACTIVE'
                    AND `role` = 'PARTICIPANT'
                ORDER BY `mapping_id` ASC ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $courseDetails = array_pop($result);
        $courseDetails['course_org_details'] = $this->getOrgDetails($courseDetails['course_org_id']);
        if ($courseDetails['course_org_id'] === $courseDetails['content_org_id']) {
            $courseDetails['content_org_details'] = $courseDetails['course_org_details'];
        } else {
            $courseDetails['content_org_details'] = $this->getOrgDetails($courseDetails['content_org_id']);
        }
        $courseDetails['navbar_links'] = $this->getNavbarLinks($courseDetails['course_id']);
        $courseDetails['pending_feedbacks'] = Feedbacks::getPendingFeedbacks($courseDetails['course_id'], $userId);
        return $courseDetails;
    }

    /**
     * Get the details of the Org to which Facilitator belongs to
     * @param type $userId
     * @return array containing org_id, name, short_name, logo_link, short_logo_link, website_link, type, custom_domain
     */
    public function getFacilitatorOrgDetails($userId) {

        //Get the branding details of the org
        $sql = "SELECT `org_id`, `name`, `short_name`,
                    `logo_link`, `short_logo_link`,
                    `website_link`, `type`, `custom_domain`,
                    `notification_email_id`
                FROM `facilitator_to_org_mapping`
                    JOIN `organisation` ON (`facilitator_to_org_mapping`.`org_id` = `organisation`.`id`)
                WHERE `facilitator_user_id` = :user_id
                    AND `facilitator_to_org_mapping`.`status` = :status
                    AND `organisation`.`status` = :status ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) <= 0) {
            return null;
        }
        $org_details = array_pop($result);

        //Get the promotions of the org
        $sql = "SELECT *
                FROM `org_promotions`
                WHERE `org_id` = :org_id
                    AND `expiry_date` > :today
                    AND `status` = :status ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':org_id', $org_details['org_id'], PDO::PARAM_INT);
        $stmt->bindValue(':today', gmdate('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            $org_details['promotions'] = $result;
        } else {
            $org_details['promotions'] = null;
        }
        return $org_details;
    }

    /**
     * Get the details of organisation
     * @param type $orgId
     * @return type key-vale array containing (`id`, `name`, `short_name`, `logo_link`, `short_logo_link`, `website_link`, `type`)
     */
    private function getOrgDetails($orgId) {
        $sql = "SELECT  `id`, `name`, `short_name`, `logo_link`, `short_logo_link`, `website_link`, `type`
                FROM `organisation`
                WHERE `id` = :id
                    AND `status` = :status ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $orgId, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_pop($result);
    }

    /**
     * Get the list community of links to be added to main nav bar
     * @param type $course_id
     * @return type
     */
    private function getNavbarLinks($course_id) {
        $sql = "SELECT `social_media_platform`, `link`
                FROM `course_community_links`
                WHERE `course_id` = :course_id
                    AND `position` = :position
                    AND `status` = :status ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':position', 'NAV_BAR', PDO::PARAM_STR);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUserDetails($userId = null) {
        if ($userId == null) {
            $userId = $_SESSION['user_id'];
        }
        $sql = "SELECT
                    `name`, `last_name`, `email`, `profile`, `phone_number`, `role`, `profile_pic_binary`, `timezone`
                FROM `users`
                WHERE `id` = :id
                    AND `status` = :status ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_pop($result);
    }

    public function mapUserToCourse($course_id, $role) {

        $db = static::getDB();

        $stmt = $db->prepare("INSERT INTO `user_to_course_mapping`
                                (`user_id`, `course_id`, `role`, `status`)
                            VALUES(
                                :user_id,
                                :course_id,
                                :role,
                                :status
                            )"
        );
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function getFirstTimePasswordOfParticipant($course_id, $user_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `name`, `last_name`, `email`, `first_time_password`
                            FROM `users`
                                JOIN `user_to_course_mapping` ON (`users`.`id` = `user_to_course_mapping`.`user_id`)
                            WHERE `users`.`id` = :user_id
                                AND `is_active` = 0
                                AND `users`.`status` = :status
                                AND `user_to_course_mapping`.`course_id` = :course_id
                                AND `user_to_course_mapping`.`role` = :role
                                AND `user_to_course_mapping`.`status` = :status "
        );
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'PARTICIPANT', PDO::PARAM_STR);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return array_pop($result);
        } else {
            return null;
        }
    }

    public static function updateUser($user_id, $first_name, $last_name, $email) {
        $sql = "UPDATE `users`
                SET `name` = :name,
                    `last_name` = :last_name,
                    `email` = :email
                WHERE `id` = :id
                    AND `status` = :status ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $first_name, PDO::PARAM_STR);
        $stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

        return $stmt->execute();
    }

    public static function deleteUser($user_id) {
        $sql = "UPDATE `users`
                SET `status` = :status
                WHERE `id` = :id ";

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "INACTIVE", PDO::PARAM_STR);

        return $stmt->execute();
    }

}
