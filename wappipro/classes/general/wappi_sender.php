<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class WappiSender  {

	const MODULE_ID = 'wappipro';
	
	public function __construct() {
		
	}
	
	
	public static function CheckPhoneNumber($phone) {
		$result = true;
		if(!preg_match("/^[0-9]{11,14}+$/", $phone)) {
			if(isset($this)) $this->error = Loc::getMessage("SMS_WRONG_PHONE");
			$result = false;
		}
		return $result;
	}
	
	
	public static function GetApiStatus() {

		include_once('wappi.php');

		$tokenApi = COption::GetOptionString(self::MODULE_ID, 'tokenApi'); 
		$profile_id = COption::GetOptionString(self::MODULE_ID, 'profile_id');   
			
		$wappi = new Wappi($tokenApi, $profile_id);

		return $wappi->GetApiStatus();
		
	}
	

	public static function SendSMS($phone, $message, $translit = 0)
	{
		include_once("wappi.php");

		if(strlen(trim($message))<=0) return false;

		$tokenApi = COption::GetOptionString(self::MODULE_ID, 'tokenApi'); 
		$profile_id = COption::GetOptionString(self::MODULE_ID, 'profile_id');  
		
		$wappi = new Wappi($tokenApi, $profile_id);
		$wappi->SetCharset(LANG_CHARSET);
		return $wappi->SendSMS($phone, $message, $translit);
	}

    public static function GettokenApi()
    {
        return COption::GetOptionString(self::MODULE_ID, 'tokenApi');
    }
}
