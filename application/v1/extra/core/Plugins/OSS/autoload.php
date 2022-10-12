<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/8
 * Time: 19:51
 */

$mapping = array(
    'OSS\OssClient' => __DIR__ . '/OSS/OssClient.php',
    'OSS\Core\OssUtil' => __DIR__ . '/OSS/Core/OssUtil.php',
    'OSS\Core\MimeTypes' => __DIR__ . '/OSS/Core/MimeTypes.php',
    'OSS\Core\OssException' => __DIR__ . '/OSS/Core/OssException.php',
    'OSS\Http\RequestCore' => __DIR__ . '/OSS/Http/RequestCore.php',
    'OSS\Http\RequestCore_Exception' => __DIR__ . '/OSS/Http/RequestCore_Exception.php',
    'OSS\Http\ResponseCore' => __DIR__ . '/OSS/Http/ResponseCore.php',

    'OSS\Result\AclResult' => __DIR__ . '/OSS/Result/AclResult.php',
    'OSS\Result\AppendResult' => __DIR__ . '/OSS/Result/AppendResult.php',
    'OSS\Result\BodyResult' => __DIR__ . '/OSS/Result/BodyResult.php',
    'OSS\Result\CallbackResult' => __DIR__ . '/OSS/Result/CallbackResult.php',
    'OSS\Result\CopyObjectResult' => __DIR__ . '/OSS/Result/CopyObjectResult.php',
    'OSS\Result\DeleteObjectsResult' => __DIR__ . '/OSS/Result/DeleteObjectsResult.php',
    'OSS\Result\ExistResult' => __DIR__ . '/OSS/Result/ExistResult.php',
    'OSS\Result\GetCnameResult' => __DIR__ . '/OSS/Result/GetCnameResult.php',
    'OSS\Result\GetCorsResult' => __DIR__ . '/OSS/Result/GetCorsResult.php',
    'OSS\Result\GetLifecycleResult' => __DIR__ . '/OSS/Result/GetLifecycleResult.php',
    'OSS\Result\GetLiveChannelHistoryResult' => __DIR__ . '/OSS/Result/GetLiveChannelHistoryResult.php',
    'OSS\Result\GetLiveChannelInfoResult' => __DIR__ . '/OSS/Result/GetLiveChannelInfoResult.php',
    'OSS\Result\GetLiveChannelStatusResult' => __DIR__ . '/OSS/Result/GetLiveChannelStatusResult.php',
    'OSS\Result\GetLoggingResult' => __DIR__ . '/OSS/Result/GetLoggingResult.php',
    'OSS\Result\GetRefererResult' => __DIR__ . '/OSS/Result/GetRefererResult.php',
    'OSS\Result\GetWebsiteResult' => __DIR__ . '/OSS/Result/GetWebsiteResult.php',
    'OSS\Result\HeaderResult' => __DIR__ . '/OSS/Result/HeaderResult.php',
    'OSS\Result\InitiateMultipartUploadResult' => __DIR__ . '/OSS/Http/InitiateMultipartUploadResult.php',
    'OSS\Result\ListBucketsResult' => __DIR__ . '/OSS/Result/ListBucketsResult.php',
    'OSS\Result\ListLiveChannelResult' => __DIR__ . '/OSS/Result/ListLiveChannelResult.php',
    'OSS\Result\ListMultipartUploadResult' => __DIR__ . '/OSS/Result/ListMultipartUploadResult.php',
    'OSS\Result\ListObjectsResult' => __DIR__ . '/OSS/Result/ListObjectsResult.php',
    'OSS\Result\ListPartsResult' => __DIR__ . '/OSS/Result/ListPartsResult.php',
    'OSS\Result\PutLiveChannelResult' => __DIR__ . '/OSS/Result/PutLiveChannelResult.php',
    'OSS\Result\PutSetDeleteResult' => __DIR__ . '/OSS/Result/PutSetDeleteResult.php',
    'OSS\Result\Result' => __DIR__ . '/OSS/Result/Result.php',
    'OSS\Result\UploadPartResult' => __DIR__ . '/OSS/Result/UploadPartResult.php',
);

spl_autoload_register(function ($class) use ($mapping) {
    if (isset($mapping[$class])) {
        require $mapping[$class];
    }
}, true);
