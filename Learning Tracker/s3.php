<?php

namespace App;

use App\Config;

use Aws\S3\S3Client;

class s3 {


    function __construct() {
        
    }
    
    public static function getDetailsForFileUpload($s3Bucket, $region, $acl = 'private') {
        $bucket = $s3Bucket;
        $accesskey = Config::AWS_S3_ACCESS_KEY;
        $secret = Config::AWS_S3_ACCESS_SECRET;

        $policy = json_encode(array(
            'expiration' => date('Y-m-d\TG:i:s\Z', strtotime('+12 hours')),
            'conditions' => array(
                array(
                    'bucket' => $bucket
                ),
                array(
                    'acl' => $acl
                ),
                array(
                    'starts-with',
                    '$key',
                    ''
                ),
                array(
                    'success_action_status' => '201'
                )
            )
        ));
        $base64Policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac("sha1", $base64Policy, $secret, $raw_output = true));
        return array(
            "bucket" => $bucket,
            "accesskey" => $accesskey,
            "base64Policy" => $base64Policy,
            "signature" => $signature,
            "acl" => $acl,
            "region" => $region
        );
    }

    function uploadFile($s3Bucket, $filepath, $contentType, $storageDestination = 'aws') {
        if($storageDestination == 'aws'){
            $access_key = Config::AWS_S3_ACCESS_KEY;;
            $access_secret = Config::AWS_S3_ACCESS_SECRET;
        }
        
        $s3 = S3Client::factory([
                'version' => '2006-03-01',
                'region' => 'us-east-1',
                'credentials' => array('key'    => $access_key,
                                    'secret' => $access_secret)
            ]);

        // Upload a file.
        $result = $s3->putObject(array(
            'Bucket'       => $s3Bucket,
            'Key'          => $access_key,
            'SourceFile'   => $filepath,
            'ContentType'  => $contentType,
            'ACL'          => 'public-read',
            'StorageClass' => 'STANDARD'
        ));

        return $result['ObjectURL'];
    }

    public static function getSignedTempUrl($region = 'us-east-1',$bucket, $file, $userFilename = null) {
        $s3Client = new S3Client([
            'region' => $region,
            'version' => '2006-03-01',
            'credentials' => array('key'    => Config::AWS_S3_ACCESS_KEY,
                                'secret' => Config::AWS_S3_ACCESS_SECRET)
        ]);
        
        if($userFilename){
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $file,
                'ResponseContentDisposition' => 'attachment; filename ="'.$userFilename.'"'
            ]);
        }else{
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $file
            ]);
        }
        
        $request = $s3Client->createPresignedRequest($cmd, '+20 minutes');

// Get the actual presigned-url
        $presignedUrl = (string) $request->getUri();

        return $presignedUrl;
//        $s3 = S3Client::factory([
//                'version' => '2006-03-01',
//                'region' => $region,
//                'credentials' => array('key'    => Config::AWS_S3_ACCESS_KEY,
//                                    'secret' => Config::AWS_S3_ACCESS_SECRET)
//            ]);
//        $signedUrl = $s3->getObjectUrl($bucket, $file, '+60 minutes');
//        return $signedUrl;
    }
    
}
