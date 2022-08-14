<?php

namespace App;

use App\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\s3;

/**
 * Mail
 *
 * PHP version 7.0
 */
class Mail {

    /**
     * Send a message
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $text Text-only content of the message
     * @param string $html HTML content of the message
     *
     * @return mixed
     */
    public static function send($to, $subject, $text, $html, $replyTo = "", $cc_array = [], $bcc_array = [], $from = [], $attachments = []) {

        if (isset($to['email_id'])) {
            $receipient_email_id = $to['email_id'];
            $receipient_name = $to['name'];
        } elseif (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $receipient_email_id = $to;
            $receipient_name = "";
        }



        if (isset($from['name']) && isset($from['email'])) {
            $sender = $from['email'];
            $senderName = $from['name'];
        } else {
            // Email Sender details from the config
            $sender = Config::AWS_SES_SENDER_EMAIL_ID;
            $senderName = Config::AWS_SES_EMAIL_SENDER_NAME;
        }

        if ($replyTo === "") {
            $replyTo = $sender;
        }

        // AWS SES SMTP Credentials.
        $region = Config::AWS_SMTP_REGION;
        $usernameSmtp = Config::AWS_SMTP_USER_NAME;
        $passwordSmtp = Config::AWS_SMTP_PASSWORD;

        // SES SMTP endpoint in the appropriate region.
        $host = 'email-smtp.' . $region . '.amazonaws.com';
        $port = 587;

        try {
            $mail = new PHPMailer(true);
            // Specify the SMTP settings.
            $mail->isSMTP();
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->setFrom($sender, $senderName);
            $mail->Username = $usernameSmtp;
            $mail->Password = $passwordSmtp;
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
//        $mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);
            // Specify the message recipients.
            $mail->addAddress($receipient_email_id, $receipient_name);
            foreach ($cc_array as $cc) {
                $mail->addCC($cc['email_id'], $cc['name']);
            }
            foreach ($bcc_array as $bcc) {
                $mail->addBCC($bcc['email_id'], $bcc['name']);
            }
            $attachedFiles = [];
            $i = 0;
            if (count($attachments) > 0) {
                $user_id = $_SESSION['user_id'];
                foreach ($attachments as $attachment) {
                    $fileName = $attachment['fileName'];
                    $fileInternalName = $attachment['internalFileName'];
                    $tempFileName = $user_id . '_' . $attachment['internalFileName'];
                    $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                    $data = file_get_contents($filePath);
                    $url = $filePath;
                    $attachedFiles[$i] = "./temp/$tempFileName";
                    $handle = curl_init();
                    $fileHandle = fopen($attachedFiles[$i], "w");
                    curl_setopt_array($handle,
                            array(
                                CURLOPT_URL => $url,
                                CURLOPT_FILE => $fileHandle,
                                CURLOPT_HEADER => true
                            )
                    );
                    $data = curl_exec($handle);
                    $responseCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                    $downloadLength = curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                    if (curl_errno($handle)) {
                        return array(
                            "status" => "Error",
                            "error" => "Encountered an issue .Please retry."
                        );
                    } else {
                        curl_close($handle);
                        fclose($fileHandle);
                    }
                    $add = $mail->AddAttachment($attachedFiles[$i], $fileName);
                    $i++;
                }
            }

            // You can also add CC, BCC, and additional To recipients here.
            // Specify the content of the message.
            $mail->isHTML(true);
            $mail->addReplyTo($replyTo);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;

            $mail->Send();
            foreach ($attachedFiles as $attachedFile) {
                unlink($attachedFile);
            }
            return array(
                "status" => "Success",
            );
        } catch (phpmailerException $e) {
            return array(
                "status" => "Error",
                "error" => "Failed to send email" . $e->errorMessage()
            );
        } catch (Exception $e) {
            return array(
                "status" => "Error",
                "error" => "Failed to send email" . $e->errorMessage()
            );
        }
    }

}
