<?php
require_once 'aliyun_oss/aliyun.php';
use \Aliyun\OSS\OSSClient;

class Modules_Oss {
	private $bucket = '';
	private $default_path = '';
	private $default_metadata = array();

	private $name = null;
	private $path = null;
	private $http_header = array();
	private $metadata = array();

	public function __construct($server_conf, $bucket=null, $default_path=''){
		$oss_client = OSSClient::factory($server_conf);
		if($bucket){
			$this->set_bucket($bucket);
		}
		if($default_path){
			$this->set_default_path($default_path);
		}
		$this->oss_client = $oss_client;
	}

	public function set_bucket($bucket){
		$this->bucket = $bucket;
	}

	public function set_default_path($path){
		$this->default_path = $path;
	}

	public function set_metadata($metadata=array()){
		$this->default_metadata = $metadata;
	}

	private static function mime_type_table(){
		/*http://webdesign.about.com/od/multimedia/a/mime-types-by-content-type.htm*/
		$map_data = array(
			'ico'  => 'image/x-icon',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'png'  => 'image/png',
			'tif'  => 'image/tiff',
			'tiff' => 'image/tiff',
			'svg'  => 'image/svg+xml',
			'webp' => 'image/webp',

			'avi'  => 'video/x-msvideo',
			'mov'  => 'video/quicktime',

			'mp3'  => 'audio/mpeg',
			'wav'  => 'audio/x-wav',

			'css'  => 'text/css',
			'js'   => 'text/javascript',
			'html' => 'text/html',
			'txt'  => 'text/plain',
			'vcf'  => 'text/x-vcard',

			'__js' => 'application/x-javascript',
			'json' => 'application/json',
			'doc'  => 'application/msword',
			'dvi'  => 'application/x-dvi',
			'gz'   => 'application/x-gzip',
			'pdf'  => 'application/pdf',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'swf'  => 'application/x-shockwave-flash',
			'tar'  => 'application/x-tar',
			'tgz'  => 'application/x-compressed',
			'xls'  => 'application/vnd.ms-excel',
			'zip'  => 'application/zip',
			'exe'  => 'application/x-dosexec',
		);
		return $map_data;
	}

	public static function get_mime_by_ext($ext){
		$map = self::mime_type_table();
		return $map[$ext];
	}

	public static function get_ext_by_mime($mime_give){
		$map = self::mime_type_table();
		foreach ($map as $ext => $mime) {
			if(strtolower($mime_give) === $mime){
				return preg_replace('/^\_+/', '', $ext);
			}
		}
	}

	public static function get_mime_by_content($content){
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		return $finfo->buffer($content);
	}

	/**
	*http_header : 'ContentType', 'CacheControl', 'ContentDisposition', 'ContentEncoding', 'Expires';
	**/
	public function set_file_info($http_header=array(), $path=null, $name=null, $metadata=array()){
		$this->name = $name;
		$this->path = $path == null ? $this->default_path : $path;
		$this->http_header = $http_header;
		$this->metadata =  array_merge($this->default_metadata, $metadata);
	}

	private function put2oss($content){
		$content_type = isset($this->http_header['ContentType']) ? $this->http_header['ContentType'] : self::get_mime_by_content($content);
		if(!$content_type){
			return;
		}
		if(!$this->name){
			$pre = substr(md5($content), 0, 16);
			$ext = self::get_ext_by_mime($content_type);
			$this->name = $pre . ($ext ? '.' . $ext : '');
		}
		$key = ($this->path ? $this->path . '/' : '') . $this->name;

		$default_opt = array(
			'Bucket'        => $this->bucket,
			'Key'           => $key,
			'Content'       => $content,
			'ContentLength' => strlen($content),
			'ContentType'   => $content_type,
			'Expires'       => new \DateTime("+5 years"),
			'CacheControl'  => 'public, max-age=31536000',
			'UserMetadata'  => $this->metadata,
		);
		$opt = array_merge($default_opt, $this->http_header);

		if(is_string($content) && strpos($opt['ContentType'], 'charset=') === false){
			$encode = mb_detect_encoding($content);
			if($encode){
				$opt['ContentType'] = $opt['ContentType']. '; charset=' . $encode;
			}
		}

		$this->name = null;
		$this->path = null;
		$this->http_header = array();
		$this->metadata = array();

		try{
			$this->oss_client->putObject($opt);
			return $key;
		} catch(\Aliyun\OSS\Exceptions\OSSException $e){
			//$e->getMessage();
		}
	}
	
	public function upload($content){
		return $this->put2oss($content);
	}

	public function upload_by_file($file){
		$content = file_get_contents($file);
		if($content){
			return $this->upload($content);
		}
	}
}