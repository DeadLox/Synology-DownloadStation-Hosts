<?php
/* 
* @author: mcampbell - Synology Forums
* created 31/10/2013
* credits to metter with rapidgator host file
*/


class SynoFileHostingOteupload {
	private $Url;
	private $Username;
	private $Password;
	private $HostInfo;
	private $OTE_COOKIE_JAR = '/tmp/oteupload.cookie';
	private $LOGIN_URL = "https://www.oteupload.com/login.php";
		
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
		//op=login&rand=y64j472epq6u65q4r3sfkmmspyycoin5idjq6aa&redirect=http%3A%2F%2Foteupload.com%2F&login=&password=
		$PostData = array('op'=>'login',
						'rand'=>'y64j472epq6u65q4r3sfkmmspyycoin5idjq6aa',
						'redirect'=>'http%3A%2F%2Foteupload.com%2F',
						'login'=>$this->Username,
						'password'=>$this->Password
						);
		$queryUrl = $this->LOGIN_URL;
		$PostData = http_build_query($PostData);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $PostData);
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->OTE_COOKIE_JAR);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $queryUrl);
		$LoginInfo = curl_exec($curl);
		curl_close($curl);
		
		//echo $LoginInfo;
		
		//xfss is oteupload logged in cookie value
		if (FALSE != $LoginInfo && file_exists($this->OTE_COOKIE_JAR)) {
			$cookieData = file_get_contents ($this->OTE_COOKIE_JAR);
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
	
	private function getPremiumDownloadLink() {
		
		$ret = false;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_URL, $this->Url);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->OTE_COOKIE_JAR);
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
			//echo $header;
			//echo "error";
			//var_dump(curl_getinfo($curl));
			$DownloadInfo = array();
			$DownloadInfo[DOWNLOAD_ERROR] = ERR_FILE_NO_EXIST;
			return $DownloadInfo;
		}
		curl_close($curl);
	}
}

//$t = new SynoFileHostingOteupload('https://www.oteupload.com/n9mh5rcfb88r/encantadoras.cap.39.mp4.html', '', '', '');
//$t->Verify();
//$t->getPremiumDownloadLink();

?>
