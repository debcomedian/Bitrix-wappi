<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class WappiTemplate
{
	static $LAST_ERROR = "";
	
	public static function GetList($aSort=Array(), $arFilter=Array())
	{
		$err_mess = "<br>Class: WappiTemplate<br>File: ".__FILE__."<br>Function: GetList<br>Line: ";
		global $DB, $USER;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$bIsLang = false;
		
		foreach($arFilter as $key=>$val)
		{
			if (!is_array($val) && (strlen($val)<=0 || $val=="NOT_REF"))
				continue;
			switch(strtoupper($key))
			{
				case "ACTIVE":
					$arSqlSearch[] = ($val=="Y") ? "T.ACTIVE = 'Y'" : "T.ACTIVE = 'N'";
					break;
				case "ID":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="N") ? "Y" : "N";
					$arSqlSearch[] = GetFilterQuery("T.ID", $val, $match);
					break;
				case "EVENT_TYPE":
					$arSqlSearch[] = "T.EVENT_TYPE = '".$val."'";
					break;
				case "EVENT_MESSAGE_ID":
					$arSqlSearch[] = "T.EVENT_MESSAGE_ID = '".$val."'";
					break;
				case "PHONE_TYPE":
					$arSqlSearch[] = "T.PHONE_TYPE = '".$val."'";
					break;
			}
		}
		
		$arOrder = array();
		foreach($aSort as $key => $ord)
		{
			$key = strtoupper($key);
			$ord = (strtoupper($ord) <> "ASC"? "DESC": "ASC");
			switch($key)
			{
				case "ID":		$arOrder[$key] = "T.ID ".$ord; break;
				case "TIMESTAMP_CREATE_X":	$arOrder[$key] = "T.TIMESTAMP_CREATE_X ".$ord; break;
				case "TIMESTAMP_CHANGE_X":	$arOrder[$key] = "T.TIMESTAMP_CHANGE_X ".$ord; break;
				case "ACTIVE":	$arOrder[$key] = "T.ACTIVE ".$ord; break;
			}
		}
		if(count($arOrder) <= 0)
		{
			$arOrder["ID"] = "T.ID DESC";
		}
		$strSqlOrder = " ORDER BY ".implode(", ", $arOrder);

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		
		$strSql =
			"SELECT T.ID, T.ACTIVE,  T.EVENT_ID,  T.EVENT_TYPE, T.EVENT_MESSAGE_ID, T.PHONE, T.MESSAGE, T.PHONE_TYPE,
			".$DB->DateToCharFunction("T.TIMESTAMP_CREATE_X").
			" TIMESTAMP_CREATE_X, ".
			$DB->DateToCharFunction("T.TIMESTAMP_CHANGE_X").
			" TIMESTAMP_CHANGE_X ".
			"FROM wappipro_template T ".
			"WHERE ".
			$strSqlSearch.
			$strSqlOrder;

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$res->is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}
	
	public static function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql =
			"SELECT T.ID, T.ACTIVE,  T.EVENT_ID,  T.EVENT_TYPE, T.EVENT_MESSAGE_ID, T.PHONE, T.MESSAGE, T.PHONE_TYPE,
				".$DB->DateToCharFunction("T.TIMESTAMP_CREATE_X").
				" TIMESTAMP_CREATE_X, ".
				$DB->DateToCharFunction("T.TIMESTAMP_CHANGE_X").
				" TIMESTAMP_CHANGE_X ".
				"FROM wappipro_template T ".
				"WHERE T.ID = ".$ID."
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
	
	public static function CheckFields($arFields)
	{
		global $DB;
		self::$LAST_ERROR = "";
		$aMsg = array();
		
        if(array_key_exists("EVENT_TYPE", $arFields))
		{
			if(strlen($arFields["EVENT_TYPE"])<=0)
				$aMsg[] = array("id"=>"EVENT_TYPE", "text"=>Loc::getMessage("CLASS_ERROR_EVENT_TYPE"));
		}
		if(array_key_exists("EVENT_MESSAGE_ID", $arFields))
		{
			if(strlen($arFields["EVENT_MESSAGE_ID"])<=0)
				$aMsg[] = array("id"=>"EVENT_MESSAGE_ID", "text"=>Loc::getMessage("CLASS_ERROR_EVENT_MESSAGE_ID"));
		}
        if(array_key_exists("PHONE_TYPE", $arFields))
		{
			if(strlen($arFields["PHONE_TYPE"])<=0)
				$aMsg[] = array("id"=>"PHONE_TYPE", "text"=>Loc::getMessage("CLASS_ERROR_EVENT_PHONE_TYPE"));
		}
		if(array_key_exists("PHONE", $arFields))
		{
			if(strlen($arFields["PHONE"])<=0)
				$aMsg[] = array("id"=>"PHONE", "text"=>Loc::getMessage("CLASS_ERROR_PHONE"));
		}
		if(array_key_exists("MESSAGE", $arFields))
		{
			if(strlen($arFields["MESSAGE"])<=0)
				$aMsg[] = array("id"=>"MESSAGE", "text"=>Loc::getMessage("CLASS_ERROR_MESSAGE"));
		}
		
		
		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			self::$LAST_ERROR = $e->GetString();
			return false;
		}

		return true;
	}
	
	public static function Update($ID, $arFields)
	{
		global $DB, $USER;
		$ID = intval($ID);
		
		$arFields['TIMESTAMP_CHANGE_X'] = GetTime(time(), "FULL");
		if(!self::CheckFields($arFields, $ID))
			return false;
		$strUpdate = $DB->PrepareUpdate("wappipro_template", $arFields);
		if($strUpdate!="")
		{
			$strSql = "UPDATE wappipro_template SET ".$strUpdate." WHERE ID=".$ID;
			$arBinds = array("MESSAGE" => $arFields["MESSAGE"]);
			if(!$DB->QueryBind($strSql, $arBinds))
				return false;
		}
		return true;
	}
	
	public static function Add($arFields)
	{
		global $DB;
		$arFields['TIMESTAMP_CREATE_X'] = GetTime(time(), "FULL");
		$arFields['TIMESTAMP_CHANGE_X'] = GetTime(time(), "FULL");
		if(!self::CheckFields($arFields))
			return false;

		$ID = $DB->Add("wappipro_template", $arFields, Array("MESSAGE"));
		return $ID;
	}
	
	public static function Delete($ID)
	{
		global $DB;

		if (!empty($ID))
		{
			return $DB->Query("DELETE FROM wappipro_template WHERE ID = ".$ID, true);
		}
		return false;
	}
}
?>