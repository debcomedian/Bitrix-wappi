<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Wappi
{
	private $tokenApi = '';
	private $profile_id = '';
  	private $charset = 'utf-8';
  	private $lastInfo = '';
	private $arLastError = array(); 
  	private $arFormats = array(1 => 'flash=1', 'push=1', 'hlr=1', 'bin=1', 'bin=2', 'ping=1');	
	private $isPOST = false;
	private $isHTTPS = false;
	private $isDebug = false;

	public function __construct($tokenApi, $profile_id)
	{
		$this->Wappi($tokenApi, $profile_id);
	}

	public function Wappi($tokenApi, $profile_id)
	{
		if(!$tokenApi)
		{
			$this->SetError(0, Loc::getMessage("WAPPI_EMPTY_TOKEN"));
			return false;
		}
		if(!$profile_id)
		{
			$this->SetError(0, Loc::getMessage("WAPPI_EMPTY_PROFILE"));
      		return false;
		}
		$this->SetTokenApi($tokenApi);
		$this->SetProfile($profile_id);
		return true;
	}

	public function SetTokenApi($val)
	{
		$this->tokenApi = $val;
	}

	public function SetProfile($val)
	{
		$this->profile_id = $val;
	}

	private function SetError($code, $str)
	{
		$this->arLastError = array('CODE' => $code, 'TEXT' => $str);
	}

	public function GetLastError($key)
	{
		if($key)
		{
      		return $this->arLastError[$key];
		}
		else
		{
			return $this->arLastError;
		}
	}

	private function SetInfo($str)
	{
		$this->lastInfo = $str;
	}

	public function GetLastInfo()
	{
		return $this->lastInfo;
	}

	public function IsDebug($val=null)
	{
		if(isset($val))
		{
      $val = (bool)$val;
			$this->isDebug = $val;
		}
		return $this->isDebug;
	}

	public function IsPOST($val=null)
	{
		if(isset($val))
		{
    	$val = (bool)$val;
			$this->isPOST = $val;
		}
		return $this->isPOST;
	}

	public function IsHTTPS($val=null)
	{
		if(isset($val))
		{
    	$val = (bool)$val;
			$this->isHTTPS = $val;
		}
		return $this->isHTTPS;
	}

	public function SetCharset($val)
	{
	    $this->charset = $val;
	}

	public function GetCharset($val=null)
	{
    	return $this->charset;
	}

	public function SendSMS($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $query = '')
	{
		$platform = COption::GetOptionString('wappipro', 'platform');
		$url = 'https://wappi.pro/' . $platform . 'api/sync/message/send?profile_id=' . $this->profile_id;
		$ret = $this->ReadURL($url, $phones, $message);
		return $ret;
	}

	private function ReadURL($url, $phones, $message)
	{
		$ret = '';
		$phone_array = explode(',', $phones); 
		foreach ($phone_array as $phone) {
			$message_json = json_encode(array(
				'recipient' => $phone,
				'body' => $message
			));

			$c = curl_init();
			curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, $message_json);
			curl_setopt($c, CURLOPT_HTTPHEADER, array(
				'Accept: application/json',
				'Authorization: ' . $this->tokenApi,
				'Content-Type: application/json'
			));
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        	$ret = curl_exec($c);
		}
		return $ret;
	}

	public function GetApiStatus()
	{
		$url = 'https://wappi.pro/api/sync/get/status?profile_id=' . $this->profile_id;

		if (!$this->tokenApi) {
			return Loc::getMessage("WAPPI_EMPTY_TOKEN_INFO");
		}
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPGET, true);
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Authorization: ' . $this->tokenApi
		));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	
		$response = curl_exec($c);
		$errno = curl_errno($c);
		$err = curl_error($c);
		curl_close($c);

		if ($errno) {
			$result_string = Loc::getMessage("WAPPI_ERROR_CURL") . $err . '</span>';
		} else {
			$data = json_decode($response, true);

			if (!(json_last_error() === JSON_ERROR_NONE)) {
				$result_string = Loc::getMessage("WAPPI_JSON_ERROR") . json_last_error_msg() . '</span>';
			} else if (sizeof($data) > 2) {
				$result_string = $this->_parse_time($data);
				
				$platform = $data['platform'];
				$platform = ($platform === 'tg')? 't': '';
				COption::SetOptionString('wappipro', 'platform', $platform);
			} else {
				$result_string = Loc::getMessage("WAPPI_INVALID_TOKEN_OR_PROFILE");
			}
		}
		return $result_string;
	}

	private function _parse_time($data) {
		$result_string = '';
		$time_sub = new DateTime($data['payment_expired_at']);
		$time_curr = new DateTime;
		if ($time_sub > $time_curr) {
			$this->_save_info();
			$time_diff = $time_curr->diff($time_sub);
			$days_diff = $time_diff->days;
			$hours_diff = $time_diff->h;
			$result_string .= Loc::getMessage("WAPPI_GREEN_SPAN_AND_FIRST_PART") 
							. $time_sub->format('Y-m-d') . Loc::getMessage("WAPPI_SECOND_PART");

			$days_diff_last_num = $days_diff % 10;
			$hours_diff_last_num = $hours_diff % 10;

			if ($days_diff !== 0) {
				$result_string .= $days_diff;

				if ($days_diff_last_num > 4 || ($days_diff > 10 && $days_diff < 21))
					$result_string .= Loc::getMessage("WAPPI_DAYS");
				else if ($days_diff_last_num === 1 )
					$result_string .= Loc::getMessage("WAPPI_DAY");
				else
					$result_string .= Loc::getMessage("WAPPI_DAY2");
			}
			$result_string .= $hours_diff;

			if ($hours_diff_last_num > 4 || ($hours_diff > 10 && $hours_diff < 20) || $hours_diff_last_num === 0) 
				$result_string .= Loc::getMessage("WAPPI_HOURS");	
			else if ($hours_diff_last_num === 1)
				$result_string .= Loc::getMessage("WAPPI_HOUR");
			else 
				$result_string .= Loc::getMessage("WAPPI_HOUR2");
			$result_string .= '</span>';	
		} else {
			$result_string .= Loc::getMessage("WAPPI_SUBSCRIPITION_PERIOD_EXPIRED");
		}
		return $result_string;		
	}

	private function _save_info() {
		$message_json = json_encode(array(
			'url' => $_SERVER['HTTP_REFERER'],
			'module' => 'bitrix',
			'profile_uuid' => $this->profile_id,
		));

		$url = 'https://dev.wappi.pro/tapi/addInstall?profile_id=' . $this->profile_id;

		$args = array(
			'body' => $message_json,
			'headers' => array(
				'Accept' => 'application/json',
				'Authorization' => $this->tokenApi,
				'Content-Type' => 'application/json',
			),
			'method' => 'POST',
			'data_format' => 'body',
		);

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, $message_json);
		curl_setopt($c, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Authorization: ' . $this->tokenApi,
			'Content-Type: application/json'
		));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true); 

		$ret = curl_exec($c);

		return $ret;
	}
}
?>