<?php
/* 
* @author: mcampbell - Synology Forums
* 
* created 01/11/2013
* credits to metter with rapidgator host file
*/

class SynoFileHostingUptobox {
	private $Url;
	private $Username;
	private $Password;
	private $HostInfo;
	private $UPTO_COOKIE_JAR = '/tmp/uptobox.cookie';
	private $LOGIN_URL = "http://uptobox.com/login.html";
		
	public function __construct($Url, $Username, $Password, $HostInfo) {
		$this->Url = $Url;
		$this->Username = $Username;
		$this->Password = $Password;
		$this->HostInfo = $HostInfo;
	}
	
	public function Verify() {
		return $this->performLogin();
	}
	
	public function GetDownloadInfo($ClearCookie) {
		if($this->performLogin()==LOGIN_FAIL) {
			$DownloadInfo = array();
			$DownloadInfo[DOWNLOAD_ERROR] = ERR_REQUIRED_PREMIUM;
			return $DownloadInfo;
		}
		return $this->getPremiumDownloadLink();
	}
	
	private function performLogin() {
		$ret = LOGIN_FAIL;
		//Save cookie file
		//op=login&redirect=http%3A%2F%2Fuptobox.com%2F&login=&password=&x=32&y=11
		$PostData = array('op'=>'login',
						'redirect'=>'http%3A%2F%2Fuptobox.com%2F',
						'login'=>$this->Username,
						'password'=>$this->Password,
						'x'=>'32',
						'y'=>'11'
		);
		$queryUrl = $this->LOGIN_URL;
		$PostData = http_build_query($PostData);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $PostData);
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->UPTO_COOKIE_JAR);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $queryUrl);
		$LoginInfo = curl_exec($curl);
		curl_close($curl);
		
		//echo $LoginInfo;
		
		//xfss is uptobox logged in cookie value
		if (FALSE != $LoginInfo && file_exists($this->UPTO_COOKIE_JAR)) {
			$cookieData = file_get_contents ($this->UPTO_COOKIE_JAR);
			if(strpos($cookieData,'xfss') !== false) {
				$ret = USER_IS_PREMIUM;
				return $ret;
			} else {
				$ret = LOGIN_FAIL;
				return $ret;
			}
		}
		$ret = LOGIN_FAIL;
		return $ret;
	}
	
	public function getPremiumDownloadLink() {
		
		$ret = false;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_URL, $this->Url);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->UPTO_COOKIE_JAR);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		
		curl_setopt($curl, CURLOPT_HEADER, true);
		//curl exec has to be called before getinfo
		$header = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
		$info = curl_getinfo($curl);
		$error_code = $info['http_code'];
		
		//var_dump(curl_getinfo($curl));
		//echo $code;
		//if 302 found in header - file is working and downloadable
		if ($error_code == 301 || $error_code == 302) { 
			//echo $info['redirect_url'];
			$DownloadInfo = array();
			$DownloadInfo[DOWNLOAD_URL] = $info['redirect_url'];
			return $DownloadInfo;
		}else{
			//echo "error";
			$DownloadInfo = array();
			$DownloadInfo[DOWNLOAD_ERROR] = ERR_FILE_NO_EXIST;
			return $DownloadInfo;
		}
		curl_close($curl);
	}
}
?>
