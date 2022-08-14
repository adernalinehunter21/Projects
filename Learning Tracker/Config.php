<?php

namespace App;

/**
 * Application configuration
 *
 * PHP version 7.0
 */
class Config {

    /**
     * Show or hide error messages on screen
     * @var boolean
     */
    const SHOW_ERRORS = false;

    /**
     * Secret key for hashing
     * @var boolean
     */
    const SECRET_KEY = 'your-secret-key';

    /**
     * Credentials to access AWS SES service via SMTP
     */

    const AWS_SMTP_REGION = 'ap-southeast-1';
    const AWS_SMTP_USER_NAME = 'AKIASB77ICA7MWWCS2ES';
    const AWS_SMTP_PASSWORD = 'BOnusWWG9Jo9iUeXxRnqwVW0Q6DfrmTuJljtDiNrHfK3';

    /**
     * From email id for SES email notifications
     */
    const AWS_SES_SENDER_EMAIL_ID = 'no-reply@yayati.co';
    const AWS_SES_EMAIL_SENDER_NAME = 'Learning Tracker';

    /**
     * Aws Credentials to access S3 services
     */
    const AWS_S3_ACCESS_KEY = 'AKIAJDARPR77H4M5UZLA';
    CONST AWS_S3_ACCESS_SECRET = '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';

    /**
     * credentials of pdfcrowd to generate PDFs
     */
    const PDFCROWD_USERNAME = 'maheshbasapur';
    CONST PDFCROWD_API_KEY = '92ef681920b810165f4716ca2306d9f4';

    const GOOGLE_CLIENT_ID = '898659757766-702gl471n02lq89g5s0m9nnbfm0osspg.apps.googleusercontent.com';
    const GOOGLE_CLIENT_SECRET = 'dJx4YNWqcnbYhGFkYkiy8zU6';
    /**
    *Credentials of users for testing
    */
    const TEST_FACILITATOR_USERNAME = 'akhilesh.chaturvedi@gmail.com';
    const TEST_FACILITATOR_USER_PASSWORD = 'abc123!@#';

    const TEST_LEARNER_USERNAME = 'mahesh.basapur@gmail.com';
    const TEST_LEARNER_USER_PASSWORD = 'abc123!@#';

}
