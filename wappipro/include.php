<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Wappipro\Lib\Order;

Loc::loadMessages(__FILE__);
$MODULE_ID = basename(dirname(__FILE__));
CModule::AddAutoloadClasses(
	$MODULE_ID,
	array(
		"WappiTemplate" => "classes/general/template.php"
	)
);
require_once dirname(__FILE__).'/classes/general/wappi_sender.php';


Class WappiProInclude
{
	public static function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
	{
		if($GLOBALS['APPLICATION']->GetGroupRight("main") < "R")
			return;

		$MODULE_ID = basename(dirname(__FILE__));
		$aMenu = array(
			"parent_menu" => "global_menu_services",
			"section" => $MODULE_ID,
			"sort" => 50,
			"text" => Loc::getMessage("MENU_NAME") ,
			"title" => '',
			"icon" => "",
			"page_icon" => "",
			"items_id" => $MODULE_ID."_items",
			"more_url" => array(),
			"items" => array(
				array(
					"text" => Loc::getMessage("MENU_NAME_TEMPL_LIST"),
					"url" => $MODULE_ID."_template_list.php?lang=".LANGUAGE_ID,
					"title" => Loc::getMessage("MENU_TITLE_TEMPL_LIST")
				),
				array(
					"text" => Loc::getMessage("MENU_NAME_TEMPL_LIST2"),
					"url" => $MODULE_ID."_сasсade_sending.php?lang=".LANGUAGE_ID,
					"title" => Loc::getMessage("MENU_TITLE_TEMPL_LIST2")
				)
			)
		);
		$aModuleMenu[] = $aMenu;
	}
	
	public static function SmsisBeforeEventAddHandler(&$event, &$lid, &$arFields, &$message_id)
	{
		if(!empty($message_id))
			$arFilter['EVENT_MESSAGE_ID'] = $message_id;
		$arFilter['EVENT_TYPE'] = $event;
		$arFilter['ACTIVE'] = 'Y';
		$dbRes = WappiTemplate::GetList(array(), $arFilter);
		if($dbRes->SelectedRowsCount() > 0)
		{
			while($arRes = $dbRes->Fetch())
			{
				$phone = false;
				$text = false;
                if($arRes['PHONE_TYPE'] == 3)   //�������� ������
                {
                    $dbOrderProps = CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $arFields['ORDER_ID'], "CODE"=>$arRes['PHONE']), false, false);
                    while($arOrderProps = $dbOrderProps->GetNext())
                    {
                        if(!empty($arOrderProps['VALUE']))
                            $arRes['PHONE'] = $arOrderProps['VALUE'];
                    }
                    $phone = $arRes['PHONE'];
                }
                elseif($arRes['PHONE_TYPE'] == 2)   //���������������� ����
                {
                    if(!empty($arFields['USER_ID']))
                    {
                        $rsUser = CUser::GetByID($arFields['USER_ID']);
                        $arUser = $rsUser->Fetch();
                        if(!empty($arUser[$arRes['PHONE']]))
                            $phone = $arUser[$arRes['PHONE']];
                    }
                }
                elseif($arRes['PHONE_TYPE'] == 1)   //������ ��������� ����������� ��� ��� ����� ��������
                {
                    if(WappiSender::CheckPhoneNumber($arRes['PHONE']))
                        $phone = $arRes['PHONE'];
                    else
                    {
                        $keyPhone = $arRes['PHONE'];
                        $keyPhone = str_replace('#', '', $keyPhone);
                        if(array_key_exists($keyPhone, $arFields))
                            $phone = $arFields[$keyPhone];
                    }
                }
				$text = $arRes["MESSAGE"];
				foreach($arFields as $keyField => $arField)
					$text = str_replace('#'.$keyField.'#', $arField, $text);
				
				if($phone && WappiSender::CheckPhoneNumber($phone))
				{
					$resSend = WappiSender::SendSMS($phone, $text);
				}
			}
		}
	}
	
	public static function SmsisEventMessageDeleteHandler($message_id)
	{
		if(!empty($message_id))
			$arFilter['EVENT_MESSAGE_ID'] = $message_id;
		$dbRes = WappiTemplate::GetList(array(), $arFilter);
		if($dbRes->SelectedRowsCount() > 0)
		{
			while($arRes = $dbRes->Fetch())
			{
				WappiTemplate::Delete($arRes['ID']);
			}
		}
	}
}
?>
