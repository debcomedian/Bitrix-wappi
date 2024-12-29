<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$MODULE_ID = 'wappi.whatsapptelegram';
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
		try {
			if ($GLOBALS['APPLICATION']->GetGroupRight("main") < "R")
				return true;
	
			$MODULE_ID = 'wappi.whatsapptelegram';
			$aMenu = array(
				"parent_menu" => "global_menu_services",
				"section" => $MODULE_ID,
				"sort" => 50,
				"text" => Loc::getMessage("MENU_NAME"),
				"title" => '',
				"icon" => "",
				"page_icon" => "",
				"items_id" => $MODULE_ID . "_items",
				"more_url" => array(),
				"items" => array(
					array(
						"text" => Loc::getMessage("MENU_NAME_TEMPL_LIST"),
						"url" => "wappipro_template_list.php?lang=" . LANGUAGE_ID,
						"title" => Loc::getMessage("MENU_TITLE_TEMPL_LIST")
					),
					array(
						"text" => Loc::getMessage("MENU_NAME_TEMPL_LIST2"),
						"url" => "wappipro_cascade_sending.php?lang=" . LANGUAGE_ID,
						"title" => Loc::getMessage("MENU_TITLE_TEMPL_LIST2")
					)
				)
			);
			$aModuleMenu[] = $aMenu;
		} catch (Exception $e) {
		} finally {
			return true;
		}
	}
	
	public static function WappiBeforeEventAddHandler(&$event, &$lid, &$arFields, &$message_id)
	{
		try {
			if (!empty($message_id))
				$arFilter['EVENT_MESSAGE_ID'] = $message_id;

			$arFilter['EVENT_TYPE'] = $event;
			$arFilter['ACTIVE'] = 'Y';
			$dbRes = WappiTemplate::GetList(array(), $arFilter);
			
			if ($dbRes->SelectedRowsCount() > 0) {
				while ($arRes = $dbRes->Fetch()) {
					$phones = '';
					$text = false;

					if ($arRes['PHONE_TYPE'] == 3) {
						$orderId = isset($arFields['ORDER_REAL_ID']) ? $arFields['ORDER_REAL_ID'] : $arFields['ORDER_ID'];
                    	$dbOrderProps = CSaleOrderPropsValue::GetList(
							array(),
							array("ORDER_ID" => $orderId, "CODE" => $arRes['PHONE']),
							false,
							false
						);
						while ($arOrderProps = $dbOrderProps->GetNext()) {
							if (!empty($arOrderProps['VALUE'])) {
								$arRes['PHONE'] = $arOrderProps['VALUE'];
								break;
							}
						}
						$phones = $arRes['PHONE'];
					} elseif ($arRes['PHONE_TYPE'] == 2) {
						if (!empty($arFields['USER_ID'])) {
							$rsUser = CUser::GetByID($arFields['USER_ID']);
							$arUser = $rsUser->Fetch();
							if (!empty($arUser[$arRes['PHONE']])) {
								$phones = $arUser[$arRes['PHONE']];
							}
						}
					} elseif ($arRes['PHONE_TYPE'] == 1) {
						if (WappiSender::CheckPhoneNumber($arRes['PHONE'])) {
							$phones = $arRes['PHONE'];
						} else {
							$keyPhone = $arRes['PHONE'];
							$keyPhone = str_replace('#', '', $keyPhone);
							if (array_key_exists($keyPhone, $arFields)) {
								$phones = $arFields[$keyPhone];
							}
						}
					}

					if ($phones) {
						$phoneArray = explode(',', $phones);
						$cleanPhones = [];

						foreach ($phoneArray as $phone) {
							$phone = trim($phone);
							if ($phone) {
								$phone = htmlspecialchars($phone);
								$phone = preg_replace("/[^0-9]/", "", $phone);
								if ($phone && WappiSender::CheckPhoneNumber($phone)) {
									$cleanPhones[] = $phone;
								}
							}
						}

						$phones = implode(',', $cleanPhones);
					}

					$text = $arRes["MESSAGE"];
					foreach ($arFields as $keyField => $arField) {
						$text = str_replace('#' . $keyField . '#', $arField, $text);
					}

					if ($phones) {
						WappiSender::SendSMS($phones, $text);
					}
				}
			}
		} catch (Exception $e) {
		} finally {
			return true;
		}
	}
	
	public static function WappiEventMessageDeleteHandler($message_id)
	{
		try {
			if (!empty($message_id))
				$arFilter['EVENT_MESSAGE_ID'] = $message_id;
			$dbRes = WappiTemplate::GetList(array(), $arFilter);
			if ($dbRes->SelectedRowsCount() > 0) {
				while ($arRes = $dbRes->Fetch()) {
					WappiTemplate::Delete($arRes['ID']);
				}
			}
		} catch (Exception $e) {
		} finally {
			return true;
		}
	}
}
?>
