<?php
/**
 * Created by PhpStorm.
 * User: liuhuazheng
 * Date: 2016/12/23
 * Time: 8:50
 */

/*
    'application.extensions.aliyun-oss-php-sdk.OSS.Http*',
    'application.extensions.aliyun-oss-php-sdk.OSS.Model*',
    'application.extensions.aliyun-oss-php-sdk.OSS.Result.*',
*/


require_once'autoload.php';
use OSS\Core\OssUtil;
use OSS\OssClient;
class OssClientSingleton
{
    private static $instance = null;

    /** @var OssClient */
    private $oss = null;

    public  $bucket         = '';
    private $maxRetries     = 0; // 最大重试次数
    private $enableStsInUrl = false;
    private $timeout        = 0;
    private $connectTimeout = 0;

    private $useSSL = false; //是否使用ssl

    private function __construct($accessKeyId, $accessKeySecret, $endpoint, $isCName = false, $securityToken = null,$bucket)
    {
        $accessKeyId     = trim($accessKeyId);
        $accessKeySecret = trim($accessKeySecret);
        $endpoint        = trim(trim($endpoint), "/");
        try {
            $this->oss    = new OssClient($accessKeyId, $accessKeySecret, $endpoint, $isCName, $securityToken);
            $this->bucket = $bucket;
        } catch (OssException $e) {
            $detial['code']       = $e->getCode();
            $detial['message']    = $e->getMessage();
            $detial['request-id'] = $e->getRequestId();
            throw new OssException($detial);
        }
    }

    /**
     * 获取OssClient单例
     * @param array $config
     * @return null|OssClientSingleton
     */
    public static function getInstance($config = array())
    {


        if (!(self::$instance instanceof self)) {
            if (!empty($config)) {
                $accessKeyId     = $config['access_id'];    // 从OSS获得的AccessKeyId
                $accessKeySecret = $config['access_key'];   // 从OSS获得的AccessKeySecret
                $endpoint        = $config['endpoint'];     // 您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
                $isCName         = isset($config['cname'])? (bool)$config['cname']: false; // 是否对Bucket做了域名绑定，并且Endpoint参数填写的是自己的域名
                $securityToken   = isset($config['security_token'])? $config['security_token']: null;
                $bucket          = isset($config['bucket'])? $config['bucket']: '';
            } else {
                $ini_array = parse_ini_file("aliyun-oss.ini");

                $accessKeyId     = $ini_array["accessid"];
                $accessKeySecret = $ini_array["accesskey"];
                $endpoint        = $ini_array["endpoint"];
                $isCName         = $ini_array["OSS_CNAME"]? (bool)$ini_array["OSS_CNAME"]: false;
                $securityToken   = $ini_array["OSS_SECURITY_TOKEN"]? $ini_array["OSS_SECURITY_TOKEN"]: null;
                $bucket          = $ini_array["OSS_BUCKET"];
            }

            self::$instance  = new self($accessKeyId, $accessKeySecret, $endpoint, $isCName, $securityToken, $bucket);

        }
        return self::$instance;
    }

    public function setBucket($bucket)
    {
        $flag = false;
        if ($this->oss instanceof OssClient) {
            if ($this->oss->doesBucketExist($bucket)) {
                $this->bucket = $bucket;
                $flag         = true;
            }
        }
        return $flag;
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * 列举用户所有的Bucket[GetService], Endpoint类型为cname不能进行此操作
     *
     * @param array $options
     * @throws OssException
     * @return BucketListInfo
     */
    public function listBuckets($options = null)
    {
        return $this->oss->listBuckets($options);
    }

    /**
     * 创建bucket，默认创建的bucket的ACL是OssClient::OSS_ACL_TYPE_PRIVATE
     *
     * @param string $bucket
     * @param string $acl
     * @param array  $options
     * @return null
     */
    public function createBucket($bucket, $acl = self::OSS_ACL_TYPE_PRIVATE, $options = null)
    {
        return $this->oss->createBucket($bucket, $acl, $options);
    }

    /**
     * 删除bucket
     * 如果Bucket不为空（Bucket中有Object，或者有分块上传的碎片），则Bucket无法删除，
     * 必须删除Bucket中的所有Object以及碎片后，Bucket才能成功删除。
     *
     * @param string $bucket
     * @param array  $options
     * @return null
     */
    public function deleteBucket($bucket = '', $options = null)
    {
        return $this->oss->deleteBucket($bucket, $options);
    }

    /**
     * 判断bucket是否存在
     *
     * @param string $bucket
     * @return bool
     * @throws OssException
     */
    public function doesBucketExist($bucket = '')
    {
        return $this->oss->doesBucketExist($bucket);
    }

    /**
     * 获取bucket的ACL配置情况
     *
     * @param string $bucket
     * @param array  $options
     * @throws OssException
     * @return string
     */
    public function getBucketAcl($bucket, $options = null)
    {
        return $this->oss->getBucketAcl($bucket, $options);
    }

    /**
     * 设置bucket的ACL配置情况
     *
     * @param string $bucket  bucket名称
     * @param string $acl     读写权限，可选值 ['private', 'public-read', 'public-read-write']
     * @param array  $options 可以为空
     * @throws OssException
     * @return null
     */
    public function putBucketAcl($bucket, $acl, $options = null)
    {
        return $this->oss->putBucketAcl($bucket, $acl, $options);
    }

    /**
     * 获取object的ACL属性
     *
     * @param string $bucket
     * @param string $object
     * @throws OssException
     * @return string
     */
    public function getObjectAcl($bucket, $object)
    {
        return $this->oss->getObjectAcl($bucket, $object);
    }

    /**
     * 设置object的ACL属性
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param string $acl    读写权限，可选值 ['default', 'private', 'public-read', 'public-read-write']
     * @throws OssException
     * @return null
     */
    public function putObjectAcl($bucket, $object, $acl)
    {
        return $this->oss->putObjectAcl($bucket, $object, $acl);
    }

    /**
     * 获取Bucket的访问日志配置情况
     *
     * @param string $bucket  bucket名称
     * @param array  $options 可以为空
     * @throws OssException
     * @return LoggingConfig
     */
    public function getBucketLogging($bucket, $options = null)
    {
        return $this->oss->getBucketLogging($bucket, $options);
    }

    /**
     * 开启Bucket访问日志记录功能，只有Bucket的所有者才能更改
     *
     * @param string $bucket       bucket名称
     * @param string $targetBucket 日志文件存放的bucket
     * @param string $targetPrefix 日志的文件前缀
     * @param array  $options      可以为空
     * @throws OssException
     * @return null
     */
    public function putBucketLogging($bucket, $targetBucket, $targetPrefix, $options = null)
    {
        return $this->oss->putBucketLogging($bucket, $targetBucket, $targetPrefix, $options);
    }

    /**
     * 关闭bucket访问日志记录功能
     *
     * @param string $bucket  bucket名称
     * @param array  $options 可以为空
     * @throws OssException
     * @return null
     */
    public function deleteBucketLogging($bucket, $options = null)
    {
        return $this->oss->deleteBucketLogging($bucket, $options);
    }

    /**
     * 将bucket设置成静态网站托管模式
     *
     * @param string        $bucket  bucket名称
     * @param WebsiteConfig $websiteConfig
     * @param array         $options 可以为空
     * @throws OssException
     * @return null
     */
    public function putBucketWebsite($bucket, $websiteConfig, $options = null)
    {
        return $this->oss->putBucketWebsite($bucket, $websiteConfig, $options);
    }

    /**
     * 获取bucket的静态网站托管状态
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return WebsiteConfig
     */
    public function getBucketWebsite($bucket, $options = null)
    {
        return $this->oss->getBucketWebsite($bucket, $options);
    }

    /**
     * 关闭bucket的静态网站托管模式
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return null
     */
    public function deleteBucketWebsite($bucket, $options = null)
    {
        return $this->oss->deleteBucketWebsite($bucket, $options);
    }

    /**
     * 在指定的bucket上设定一个跨域资源共享(CORS)的规则，如果原规则存在则覆盖原规则
     *
     * @param string     $bucket     bucket名称
     * @param CorsConfig $corsConfig 跨域资源共享配置，具体规则参见SDK文档
     * @param array      $options    array
     * @throws OssException
     * @return null
     */
    public function putBucketCors($bucket, $corsConfig, $options = null)
    {
        return $this->oss->putBucketCors($bucket, $corsConfig, $options);
    }

    /**
     * 获取Bucket的CORS配置情况
     *
     * @param string $bucket  bucket名称
     * @param array  $options 可以为空
     * @throws OssException
     * @return CorsConfig
     */
    public function getBucketCors($bucket, $options = null)
    {
        return $this->oss->getBucketCors($bucket, $options);
    }

    /**
     * 关闭指定Bucket对应的CORS功能并清空所有规则
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return null
     */
    public function deleteBucketCors($bucket, $options = null)
    {
        return $this->oss->deleteBucketCors($bucket, $options);
    }

    /**
     * 为指定Bucket增加CNAME绑定
     *
     * @param string $bucket bucket名称
     * @param string $cname
     * @param array  $options
     * @throws OssException
     * @return null
     */
    public function addBucketCname($bucket, $cname, $options = null)
    {
        return $this->oss->addBucketCname($bucket, $cname, $options);
    }

    /**
     * 获取指定Bucket已绑定的CNAME列表
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return CnameConfig
     */
    public function getBucketCname($bucket, $options = null)
    {
        return $this->oss->getBucketCname($bucket, $options);
    }

    /**
     * 解除指定Bucket的CNAME绑定
     *
     * @param string      $bucket bucket名称
     * @param CnameConfig $cname
     * @param array       $options
     * @throws OssException
     * @return null
     */
    public function deleteBucketCname($bucket, $cname, $options = null)
    {
        return $this->oss->deleteBucketCname($bucket, $cname, $options);
    }

    /**
     * 为指定Bucket创建LiveChannel
     *
     * @param string            $bucket bucket名称
     * @param string            $channelName
     * @param LiveChannelConfig $channelConfig
     * @param array             $options
     * @throws OssException
     * @return LiveChannelInfo
     */
    public function putBucketLiveChannel($bucket, $channelName, $channelConfig, $options = null)
    {
        return $this->oss->putBucketLiveChannel($bucket, $channelName, $channelConfig, $options);
    }

    /**
     * 设置LiveChannel的status
     *
     * @param string $bucket        bucket名称
     * @param string $channelName
     * @param string $channelStatus 为enabled或disabled
     * @param array  $options
     * @throws OssException
     * @return null
     */
    public function putLiveChannelStatus($bucket, $channelName, $channelStatus, $options = null)
    {
        return $this->oss->putLiveChannelStatus($bucket, $channelName, $channelStatus, $options);
    }

    /**
     * 获取LiveChannel信息
     *
     * @param string $bucket bucket名称
     * @param string $channelName
     * @param array  $options
     * @throws OssException
     * @return GetLiveChannelInfo
     */
    public function getLiveChannelInfo($bucket, $channelName, $options = null)
    {
        return $this->oss->getLiveChannelInfo($bucket, $channelName, $options);

    }

    /**
     * 获取LiveChannel状态信息
     *
     * @param string $bucket bucket名称
     * @param string $channelName
     * @param array  $options
     * @throws OssException
     * @return GetLiveChannelStatus
     */
    public function getLiveChannelStatus($bucket, $channelName, $options = null)
    {
        return $this->oss->getLiveChannelStatus($bucket, $channelName, $options);
    }

    /**
     *获取LiveChannel推流记录
     *
     * @param string $bucket bucket名称
     * @param string $channelName
     * @param array  $options
     * @throws OssException
     * @return GetLiveChannelHistory
     */
    public function getLiveChannelHistory($bucket, $channelName, $options = null)
    {
        return $this->oss->getLiveChannelHistory($bucket, $channelName, $options);
    }

    /**
     *获取指定Bucket下的live channel列表
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return LiveChannelListInfo
     */
    public function listBucketLiveChannels($bucket, $options = null)
    {
        return $this->oss->listBucketLiveChannels($bucket, $options);
    }

    /**
     * 为指定LiveChannel生成播放列表
     *
     * @param string $bucket       bucket名称
     * @param string $channelName
     * @param string $playlistName 指定生成的点播播放列表的名称，必须以“.m3u8”结尾
     * @param array  $setTime      startTime和EndTime以unix时间戳格式给定,跨度不能超过一天
     * @throws OssException
     * @return null
     */
    public function postVodPlaylist($bucket, $channelName, $playlistName, $setTime)
    {
        return $this->oss->postVodPlaylist($bucket, $channelName, $playlistName, $setTime);
    }

    /**
     * 删除指定Bucket的LiveChannel
     *
     * @param string $bucket bucket名称
     * @param string $channelName
     * @param array  $options
     * @throws OssException
     * @return null
     */
    public function deleteBucketLiveChannel($bucket, $channelName, $options = null)
    {
        return $this->oss->deleteBucketLiveChannel($bucket, $channelName, $options);
    }

    /**
     * 生成带签名的推流地址
     *
     * @param string $bucket  bucket名称
     * @param string $channelName
     * @param int    $timeout 设置超时时间，单位为秒
     * @param array  $options
     * @throws OssException
     * @return string 推流地址
     */
    public function signRtmpUrl($bucket, $channelName, $timeout = 60, $options = null)
    {
        return $this->oss->signRtmpUrl($bucket, $channelName, $timeout, $options);
    }

    /**
     * 检验跨域资源请求, 发送跨域请求之前会发送一个preflight请求（OPTIONS）并带上特定的来源域，
     * HTTP方法和header信息等给OSS以决定是否发送真正的请求。 OSS可以通过putBucketCors接口
     * 来开启Bucket的CORS支持，开启CORS功能之后，OSS在收到浏览器preflight请求时会根据设定的
     * 规则评估是否允许本次请求
     *
     * @param string $bucket          bucket名称
     * @param string $object          object名称
     * @param string $origin          请求来源域
     * @param string $request_method  表明实际请求中会使用的HTTP方法
     * @param string $request_headers 表明实际请求中会使用的除了简单头部之外的headers
     * @param array  $options
     * @return array
     * @throws OssException
     * @link http://help.aliyun.com/document_detail/oss/api-reference/cors/OptionObject.html
     */
    public function optionsObject($bucket, $object, $origin, $request_method, $request_headers, $options = null)
    {
        return $this->oss->optionsObject($bucket, $object, $origin, $request_method, $request_headers, $options);
    }

    /**
     * 设置Bucket的Lifecycle配置
     *
     * @param string          $bucket          bucket名称
     * @param LifecycleConfig $lifecycleConfig Lifecycle配置类
     * @param array           $options
     * @throws OssException
     * @return null
     */
    public function putBucketLifecycle($bucket, $lifecycleConfig, $options = null)
    {
        return $this->oss->putBucketLifecycle($bucket, $lifecycleConfig, $options);
    }

    /**
     * 获取Bucket的Lifecycle配置情况
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return LifecycleConfig
     */
    public function getBucketLifecycle($bucket, $options = null)
    {
        return $this->oss->getBucketLifecycle($bucket, $options);
    }

    /**
     * 删除指定Bucket的生命周期配置
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return null
     */
    public function deleteBucketLifecycle($bucket, $options = null)
    {
        return $this->oss->deleteBucketLifecycle($bucket, $options);
    }

    /**
     * 设置一个bucket的referer访问白名单和是否允许referer字段为空的请求访问
     * Bucket Referer防盗链具体见OSS防盗链
     *
     * @param string        $bucket bucket名称
     * @param RefererConfig $refererConfig
     * @param array         $options
     * @return ResponseCore
     * @throws null
     */
    public function putBucketReferer($bucket, $refererConfig, $options = null)
    {
        return $this->oss->putBucketReferer($bucket, $refererConfig, $options);
    }

    /**
     * 获取Bucket的Referer配置情况
     * Bucket Referer防盗链具体见OSS防盗链
     *
     * @param string $bucket bucket名称
     * @param array  $options
     * @throws OssException
     * @return RefererConfig
     */
    public function getBucketReferer($bucket, $options = null)
    {
        return $this->oss->getBucketReferer($bucket, $options);
    }

    /**
     * 获取bucket下的object列表
     *
     * @param string $bucket
     * @param array  $options
     *      其中options中的参数如下
     *      $options = array(
     *      'max-keys'  => max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000。
     *      'prefix'    => 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
     *      'delimiter' => 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
     *      'marker'    => 用户设定结果从marker之后按字母排序的第一个开始返回。
     *      )
     *      其中 prefix，marker用来实现分页显示效果，参数的长度必须小于256字节。
     * @throws OssException
     * @return ObjectListInfo
     */
    public function listObjects($bucket, $options = null)
    {
        return $this->oss->listObjects($bucket, $options);
    }

    /**
     * 创建虚拟目录 (本函数会在object名称后增加'/', 所以创建目录的object名称不需要'/'结尾，否则，目录名称会变成'//')
     *
     * 暂不开放此接口
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param array  $options
     * @return null
     */
    public function createObjectDir($bucket, $object, $options = null)
    {
        return $this->oss->createObjectDir($bucket, $object, $options);
    }

    /**
     * 上传内存中的内容
     * @param string $bucket  bucket名称
     * @param string $object  objcet名称
     * @param string $content 上传的内容
     * @param array  $options
     * @return null
     */
    public function putObject($bucket, $object, $content, $options = null)
    {
        return $this->oss->putObject($bucket, $object, $content, $options);
    }

    /**
     * 上传本地文件
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param string $file   本地文件路径
     * @param array  $options
     * @return null
     * @throws OssException
     */
    public function uploadFile($bucket, $object, $file, $options = null)
    {
        return $this->oss->uploadFile($bucket, $object, $file, $options);
    }

    /**
     * 追加上传内存中的内容
     *
     * @param string $bucket  bucket名称
     * @param string $object  objcet名称
     * @param string $content 本次追加上传的内容
     * @param string $position
     * @param array  $options
     * @return int next append position
     */
    public function appendObject($bucket, $object, $content, $position, $options = null)
    {
        return $this->oss->appendObject($bucket, $object, $content, $position, $options);
    }

    /**
     * 追加上传本地文件
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param string $file   追加上传的本地文件路径
     * @param string $position
     * @param array  $options
     * @return int next append position
     */
    public function appendFile($bucket, $object, $file, $position, $options = null)
    {
        return $this->oss->appendFile($bucket, $object, $file, $position, $options);
    }

    /**
     * 拷贝一个在OSS上已经存在的object成另外一个object
     *
     * @param string $fromBucket 源bucket名称
     * @param string $fromObject 源object名称
     * @param string $toBucket   目标bucket名称
     * @param string $toObject   目标object名称
     * @param array  $options
     * @return null
     * @throws OssException
     */
    public function copyObject($fromBucket, $fromObject, $toBucket, $toObject, $options = null)
    {
        return $this->oss->copyObject($fromBucket, $fromObject, $toBucket, $toObject, $options);
    }

    /**
     * 获取Object的Meta信息
     *
     * @param string $bucket  bucket名称
     * @param string $object  object名称
     * @param string $options 具体参考SDK文档
     * @return array
     */
    public function getObjectMeta($bucket, $object, $options = null)
    {
        return $this->oss->getObjectMeta($bucket, $object, $options);
    }

    /**
     * 删除某个Object
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param array  $options
     * @return null
     */
    public function deleteObject($bucket, $object, $options = null)
    {
        return $this->oss->deleteObject($bucket, $object, $options);
    }

    /**
     * 删除同一个Bucket中的多个Object
     *
     * @param string $bucket  bucket名称
     * @param array  $objects object列表
     * @param array  $options
     * @return ResponseCore
     * @throws null
     */
    public function deleteObjects($bucket, $objects, $options = null)
    {
        return $this->oss->deleteObjects($bucket, $objects, $options);
    }

    /**
     * 获得Object内容
     *
     * @param string $bucket  bucket名称
     * @param string $object  object名称
     * @param array  $options 该参数中必须设置ALIOSS::OSS_FILE_DOWNLOAD，ALIOSS::OSS_RANGE可选，可以根据实际情况设置；如果不设置，默认会下载全部内容
     * @return string
     */
    public function getObject($bucket, $object, $options = null)
    {
        return $this->oss->getObject($bucket, $object, $options);
    }

    /**
     * 检测Object是否存在
     * 通过获取Object的Meta信息来判断Object是否存在， 用户需要自行解析ResponseCore判断object是否存在
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param array  $options
     * @return bool
     */
    public function doesObjectExist($bucket, $object, $options = null)
    {
        return $this->oss->doesObjectExist($bucket, $object, $options);
    }

    /**
     * 计算文件可以分成多少个part，以及每个part的长度以及起始位置
     * 方法必须在 <upload_part()>中调用
     *
     * @param integer $file_size 文件大小
     * @param integer $partSize  part大小,默认5M
     * @return array An array 包含 key-value 键值对. Key 为 `seekTo` 和 `length`.
     */
    public function generateMultiuploadParts($file_size, $partSize = 5242880)
    {
        return $this->oss->generateMultiuploadParts($file_size, $partSize);
    }

    /**
     * 初始化multi-part upload
     *
     * @param string $bucket  Bucket名称
     * @param string $object  Object名称
     * @param array  $options Key-Value数组
     * @throws OssException
     * @return string 返回uploadid
     */
    public function initiateMultipartUpload($bucket, $object, $options = null)
    {
        return $this->oss->initiateMultipartUpload($bucket, $object, $options);
    }

    /**
     * 分片上传的块上传接口
     *
     * @param string $bucket  Bucket名称
     * @param string $object  Object名称
     * @param string $uploadId
     * @param array  $options Key-Value数组
     * @return string eTag
     * @throws OssException
     */
    public function uploadPart($bucket, $object, $uploadId, $options = null)
    {
        return $this->oss->uploadPart($bucket, $object, $uploadId, $options);
    }

    /**
     * 获取已成功上传的part
     *
     * @param string $bucket   Bucket名称
     * @param string $object   Object名称
     * @param string $uploadId uploadId
     * @param array  $options  Key-Value数组
     * @return ListPartsInfo
     * @throws OssException
     */
    public function listParts($bucket, $object, $uploadId, $options = null)
    {
        return $this->oss->listParts($bucket, $object, $uploadId, $options);
    }

    /**
     * 中止进行一半的分片上传操作
     *
     * @param string $bucket   Bucket名称
     * @param string $object   Object名称
     * @param string $uploadId uploadId
     * @param array  $options  Key-Value数组
     * @return null
     * @throws OssException
     */
    public function abortMultipartUpload($bucket, $object, $uploadId, $options = null)
    {
        return $this->oss->abortMultipartUpload($bucket, $object, $uploadId, $options);
    }

    /**
     * 在将所有数据Part都上传完成后，调用此接口完成本次分块上传
     *
     * @param string $bucket    Bucket名称
     * @param string $object    Object名称
     * @param string $uploadId  uploadId
     * @param array  $listParts array( array("PartNumber"=> int, "ETag"=>string))
     * @param array  $options   Key-Value数组
     * @throws OssException
     * @return null
     */
    public function completeMultipartUpload($bucket, $object, $uploadId, $listParts, $options = null)
    {
        return $this->oss->completeMultipartUpload($bucket, $object, $uploadId, $listParts, $options);
    }

    /**
     * 罗列出所有执行中的Multipart Upload事件，即已经被初始化的Multipart Upload但是未被
     * Complete或者Abort的Multipart Upload事件
     *
     * @param string $bucket  bucket
     * @param array  $options 关联数组
     * @throws OssException
     * @return ListMultipartUploadInfo
     */
    public function listMultipartUploads($bucket, $options = null)
    {
        return $this->oss->listMultipartUploads($bucket, $options);
    }

    /**
     * 从一个已存在的Object中拷贝数据来上传一个Part
     *
     * @param string $fromBucket 源bucket名称
     * @param string $fromObject 源object名称
     * @param string $toBucket   目标bucket名称
     * @param string $toObject   目标object名称
     * @param int    $partNumber 分块上传的块id
     * @param string $uploadId   初始化multipart upload返回的uploadid
     * @param array  $options    Key-Value数组
     * @return null
     * @throws OssException
     */
    public function uploadPartCopy($fromBucket, $fromObject, $toBucket, $toObject, $partNumber, $uploadId, $options = null)
    {
        return $this->oss->uploadPartCopy($fromBucket, $fromObject, $toBucket, $toObject, $partNumber, $uploadId, $options);
    }

    /**
     * multipart上传统一封装，从初始化到完成multipart，以及出错后中止动作
     *
     * @param string $bucket  bucket名称
     * @param string $object  object名称
     * @param string $file    需要上传的本地文件的路径
     * @param array  $options Key-Value数组
     * @return null
     * @throws OssException
     */
    public function multiuploadFile($bucket, $object, $file, $options = null)
    {
        return $this->oss->multiuploadFile($bucket, $object, $file, $options);
    }

    /**
     * 上传本地目录内的文件或者目录到指定bucket的指定prefix的object中
     *
     * @param string $bucket         bucket名称
     * @param string $prefix         需要上传到的object的key前缀，可以理解成bucket中的子目录，结尾不能是'/'，接口中会补充'/'
     * @param string $localDirectory 需要上传的本地目录
     * @param string $exclude        需要排除的目录
     * @param bool   $recursive      是否递归的上传localDirectory下的子目录内容
     * @param bool   $checkMd5
     * @return array 返回两个列表 array("succeededList" => array("object"), "failedList" => array("object"=>"errorMessage"))
     * @throws OssException
     */
    public function uploadDir($bucket, $prefix, $localDirectory, $exclude = '.|..|.svn|.git', $recursive = false, $checkMd5 = true)
    {
        return $this->oss->uploadDir($bucket, $prefix, $localDirectory, $exclude, $recursive, $checkMd5);
    }

    /**
     * 支持生成get和put签名, 用户可以生成一个具有一定有效期的
     * 签名过的url
     *
     * @param string $bucket
     * @param string $object
     * @param int    $timeout
     * @param string $method
     * @param array  $options Key-Value数组
     * @return string
     * @throws OssException
     */
    public function signUrl($bucket, $object, $timeout = 60, $method = self::OSS_HTTP_GET, $options = null)
    {
        return $this->oss->signUrl($bucket, $object, $timeout, $method, $options);
    }

    /**
     * 设置最大尝试次数
     *
     * @param int $maxRetries
     * @return void
     */
    public function setMaxTries($maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
        $this->oss->setMaxTries($maxRetries);
    }

    /**
     * 获取最大尝试次数
     *
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->maxRetries;
    }

    /**
     * 打开sts enable标志，使用户构造函数中传入的$sts生效
     *
     * @param boolean $enable
     */
    public function setSignStsInUrl($enable)
    {
        $this->enableStsInUrl = $enable;
        $this->oss->setSignStsInUrl($enable);
    }

    /**
     * @return boolean
     */
    public function isUseSSL()
    {
        return $this->useSSL;
    }

    /**
     * @param boolean $useSSL
     */
    public function setUseSSL($useSSL)
    {
        $this->useSSL = $useSSL;
        $this->oss->setUseSSL($useSSL);
    }

    /**
     * 用来检查sdk所以来的扩展是否打开
     *
     * @throws OssException
     */
    public static function checkEnv()
    {
        if (function_exists('get_loaded_extensions')) {
            //检测curl扩展
            $enabled_extension = array("curl");
            $extensions        = get_loaded_extensions();
            if ($extensions) {
                foreach ($enabled_extension as $item) {
                    if (!in_array($item, $extensions)) {
                        throw new OssException("Extension {".$item."} is not installed or not enabled, please check your php env.");
                    }
                }
            } else {
                throw new OssException("function get_loaded_extensions not found.");
            }
        } else {
            throw new OssException('Function get_loaded_extensions has been disabled, please check php config.');
        }
    }

    /**
     * //* 设置http库的请求超时时间，单位秒
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        $this->oss->setTimeout($timeout);
    }

    /**
     * 设置http库的连接超时时间，单位秒
     *
     * @param int $connectTimeout
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
        $this->oss->setConnectTimeout($connectTimeout);
    }

    // 生命周期相关常量
    const OSS_LIFECYCLE_EXPIRATION  = "Expiration";
    const OSS_LIFECYCLE_TIMING_DAYS = "Days";
    const OSS_LIFECYCLE_TIMING_DATE = "Date";
    //OSS 内部常量
    const OSS_BUCKET                  = 'bucket';
    const OSS_OBJECT                  = 'object';
    const OSS_HEADERS                 = OssUtil::OSS_HEADERS;
    const OSS_METHOD                  = 'method';
    const OSS_QUERY                   = 'query';
    const OSS_BASENAME                = 'basename';
    const OSS_MAX_KEYS                = 'max-keys';
    const OSS_UPLOAD_ID               = 'uploadId';
    const OSS_PART_NUM                = 'partNumber';
    const OSS_COMP                    = 'comp';
    const OSS_LIVE_CHANNEL_STATUS     = 'status';
    const OSS_LIVE_CHANNEL_START_TIME = 'startTime';
    const OSS_LIVE_CHANNEL_END_TIME   = 'endTime';
    const OSS_POSITION                = 'position';
    const OSS_MAX_KEYS_VALUE          = 100;
    const OSS_MAX_OBJECT_GROUP_VALUE  = OssUtil::OSS_MAX_OBJECT_GROUP_VALUE;
    const OSS_MAX_PART_SIZE           = OssUtil::OSS_MAX_PART_SIZE;
    const OSS_MID_PART_SIZE           = OssUtil::OSS_MID_PART_SIZE;
    const OSS_MIN_PART_SIZE           = OssUtil::OSS_MIN_PART_SIZE;
    const OSS_FILE_SLICE_SIZE         = 8192;
    const OSS_PREFIX                  = 'prefix';
    const OSS_DELIMITER               = 'delimiter';
    const OSS_MARKER                  = 'marker';
    const OSS_ACCEPT_ENCODING         = 'Accept-Encoding';
    const OSS_CONTENT_MD5             = 'Content-Md5';
    const OSS_SELF_CONTENT_MD5        = 'x-oss-meta-md5';
    const OSS_CONTENT_TYPE            = 'Content-Type';
    const OSS_CONTENT_LENGTH          = 'Content-Length';
    const OSS_IF_MODIFIED_SINCE       = 'If-Modified-Since';
    const OSS_IF_UNMODIFIED_SINCE     = 'If-Unmodified-Since';
    const OSS_IF_MATCH                = 'If-Match';
    const OSS_IF_NONE_MATCH           = 'If-None-Match';
    const OSS_CACHE_CONTROL           = 'Cache-Control';
    const OSS_EXPIRES                 = 'Expires';
    const OSS_PREAUTH                 = 'preauth';
    const OSS_CONTENT_COING           = 'Content-Coding';
    const OSS_CONTENT_DISPOSTION      = 'Content-Disposition';
    const OSS_RANGE                   = 'range';
    const OSS_ETAG                    = 'etag';
    const OSS_LAST_MODIFIED           = 'lastmodified';
    const OS_CONTENT_RANGE            = 'Content-Range';
    const OSS_CONTENT                 = OssUtil::OSS_CONTENT;
    const OSS_BODY                    = 'body';
    const OSS_LENGTH                  = OssUtil::OSS_LENGTH;
    const OSS_HOST                    = 'Host';
    const OSS_DATE                    = 'Date';
    const OSS_AUTHORIZATION           = 'Authorization';
    const OSS_FILE_DOWNLOAD           = 'fileDownload';
    const OSS_FILE_UPLOAD             = 'fileUpload';
    const OSS_PART_SIZE               = 'partSize';
    const OSS_SEEK_TO                 = 'seekTo';
    const OSS_SIZE                    = 'size';
    const OSS_QUERY_STRING            = 'query_string';
    const OSS_SUB_RESOURCE            = 'sub_resource';
    const OSS_DEFAULT_PREFIX          = 'x-oss-';
    const OSS_CHECK_MD5               = 'checkmd5';
    const DEFAULT_CONTENT_TYPE        = 'application/octet-stream';

    //私有URL变量
    const OSS_URL_ACCESS_KEY_ID = 'OSSAccessKeyId';
    const OSS_URL_EXPIRES       = 'Expires';
    const OSS_URL_SIGNATURE     = 'Signature';
    //HTTP方法
    const OSS_HTTP_GET     = 'GET';
    const OSS_HTTP_PUT     = 'PUT';
    const OSS_HTTP_HEAD    = 'HEAD';
    const OSS_HTTP_POST    = 'POST';
    const OSS_HTTP_DELETE  = 'DELETE';
    const OSS_HTTP_OPTIONS = 'OPTIONS';
    //其他常量
    const OSS_ACL                      = 'x-oss-acl';
    const OSS_OBJECT_ACL               = 'x-oss-object-acl';
    const OSS_OBJECT_GROUP             = 'x-oss-file-group';
    const OSS_MULTI_PART               = 'uploads';
    const OSS_MULTI_DELETE             = 'delete';
    const OSS_OBJECT_COPY_SOURCE       = 'x-oss-copy-source';
    const OSS_OBJECT_COPY_SOURCE_RANGE = "x-oss-copy-source-range";
    const OSS_PROCESS                  = "x-oss-process";
    const OSS_CALLBACK                 = "x-oss-callback";
    const OSS_CALLBACK_VAR             = "x-oss-callback-var";
    //支持STS SecurityToken
    const OSS_SECURITY_TOKEN             = "x-oss-security-token";
    const OSS_ACL_TYPE_PRIVATE           = 'private';
    const OSS_ACL_TYPE_PUBLIC_READ       = 'public-read';
    const OSS_ACL_TYPE_PUBLIC_READ_WRITE = 'public-read-write';
    const OSS_ENCODING_TYPE              = "encoding-type";
    const OSS_ENCODING_TYPE_URL          = "url";

    // 域名类型
    const OSS_HOST_TYPE_NORMAL  = "normal";//http://bucket.oss-cn-hangzhou.aliyuncs.com/object
    const OSS_HOST_TYPE_IP      = "ip";  //http://1.1.1.1/bucket/object
    const OSS_HOST_TYPE_SPECIAL = 'special'; //http://bucket.guizhou.gov/object
    const OSS_HOST_TYPE_CNAME   = "cname";  //http://mydomain.com/object
    //OSS ACL数组
    static $OSS_ACL_TYPES
        = array(
            self::OSS_ACL_TYPE_PRIVATE,
            self::OSS_ACL_TYPE_PUBLIC_READ,
            self::OSS_ACL_TYPE_PUBLIC_READ_WRITE,
        );
    // OssClient版本信息
    const OSS_NAME                    = "aliyun-sdk-php";
    const OSS_VERSION                 = "2.2.1";
    const OSS_BUILD                   = "20161201";
    const OSS_AUTHOR                  = "";
    const OSS_OPTIONS_ORIGIN          = 'Origin';
    const OSS_OPTIONS_REQUEST_METHOD  = 'Access-Control-Request-Method';
    const OSS_OPTIONS_REQUEST_HEADERS = 'Access-Control-Request-Headers';
}