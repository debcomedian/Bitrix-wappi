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

        $siteIds = [];
        if (is_array($lid)) {
            $siteIds = array_values(array_filter($lid));
        } elseif (is_string($lid) && $lid !== '') {
            $siteIds = [$lid];
        } elseif (!empty($arFields['SITE_ID'])) {
            $siteIds = [(string)$arFields['SITE_ID']];
        }

        if (!empty($message_id)) {
            $msgId = (int)$message_id;

            $msgSites = [];
            if (method_exists('CEventMessage', 'GetSite')) {
                $rsSites = CEventMessage::GetSite($msgId);
                if ($rsSites) {
                    while ($ar = $rsSites->Fetch()) {
                        if (!empty($ar['SITE_ID'])) {
                            $msgSites[] = $ar['SITE_ID'];
                        }
                    }
                }
            }

            if (empty($msgSites)) {
                $em = CEventMessage::GetByID($msgId);
                $emRow = $em ? $em->Fetch() : null;
                if (!$emRow) {
                    return true;
                }
                if (!empty($emRow['LID'])) {
                    $msgSites[] = $emRow['LID'];
                }
            }

            if (!empty($siteIds) && !empty($msgSites)) {
                $intersect = array_intersect($siteIds, $msgSites);
                if (empty($intersect)) {
                    return true;
                }
            }
        }

        $arFilter = [
            'EVENT_TYPE' => $event,
            'ACTIVE'     => 'Y'
        ];
        if (!empty($message_id)) {
            $arFilter['EVENT_MESSAGE_ID'] = $message_id;
        }
        $dbTemplates = WappiTemplate::GetList([], $arFilter);

        while ($template = $dbTemplates->Fetch()) {
            $phones = '';
            $text = $template['MESSAGE'];

            if (!empty($template['SITE_ID']) && (empty($siteIds) || !in_array($template['SITE_ID'], $siteIds, true))) {
                continue;
            }


            if (!empty($template['EVENT_MESSAGE_ID'])) {
                $tplMsgId = (int)$template['EVENT_MESSAGE_ID'];
                $tplMsgSites = [];

                if (method_exists('CEventMessage', 'GetSite')) {
                    $rsTplSites = CEventMessage::GetSite($tplMsgId);
                    if ($rsTplSites) {
                        while ($ar = $rsTplSites->Fetch()) {
                            if (!empty($ar['SITE_ID'])) {
                                $tplMsgSites[] = $ar['SITE_ID'];
                            }
                        }
                    }
                }

                if (empty($tplMsgSites)) {
                    $emTpl = CEventMessage::GetByID($tplMsgId);
                    $emTplRow = $emTpl ? $emTpl->Fetch() : null;
                    if (!$emTplRow) {
                        continue;
                    }
                    if (!empty($emTplRow['LID'])) {
                        $tplMsgSites[] = $emTplRow['LID'];
                    }
                }

                if (!empty($siteIds) && !empty($tplMsgSites)) {
                    $intersect = array_intersect($siteIds, $tplMsgSites);
                    if (empty($intersect)) {
                        continue;
                    }
                }
            }

            switch ($template['PHONE_TYPE']) {
                case 1:
                    if (WappiSender::CheckPhoneNumber($template['PHONE'])) {
                        $phones = $template['PHONE'];
                    } else {
                        $code = $template['PHONE'];
                        if (isset($arFields[trim($code, '#')]) && !empty($arFields[trim($code, '#')])) {
                            $phones = $arFields[trim($code, '#')];
                            $text = str_replace($code, '', $text);
                        }
                    }
                    break;

                case 2:
                    if (!empty($arFields['USER_ID'])) {
                        $user = CUser::GetByID($arFields['USER_ID'])->Fetch();
                        if ($user) {
                            if (!empty($user['PERSONAL_PHONE'])) {
                                $phones = $user['PERSONAL_PHONE'];
                            } elseif (!empty($user['PERSONAL_MOBILE'])) {
                                $phones = $user['PERSONAL_MOBILE'];
                            } elseif (!empty($user['WORK_PHONE'])) {
                                $phones = $user['WORK_PHONE'];
                            } else {
                                $phones = $template['PHONE'];
                            }
                        } else {
                            $phones = $template['PHONE'];
                        }
                    } elseif (!empty($arFields['EMAIL'])) {
                        $user = CUser::GetList($by = "id", $order = "desc", ['EMAIL' => $arFields['EMAIL']])->Fetch();
                        if ($user) {
                            if (!empty($user['PERSONAL_PHONE'])) {
                                $phones = $user['PERSONAL_PHONE'];
                            } elseif (!empty($user['PERSONAL_MOBILE'])) {
                                $phones = $user['PERSONAL_MOBILE'];
                            } elseif (!empty($user['WORK_PHONE'])) {
                                $phones = $user['WORK_PHONE'];
                            } else {
                                $phones = $template['PHONE'];
                            }
                        } else {
                            $phones = $template['PHONE'];
                        }
                    } elseif (!empty($arFields['LOGIN'])) {
                        $user = CUser::GetList($by = "id", $order = "desc", ['LOGIN' => $arFields['LOGIN']])->Fetch();
                        if ($user) {
                            if (!empty($user['PERSONAL_PHONE'])) {
                                $phones = $user['PERSONAL_PHONE'];
                            } elseif (!empty($user['PERSONAL_MOBILE'])) {
                                $phones = $user['PERSONAL_MOBILE'];
                            } elseif (!empty($user['WORK_PHONE'])) {
                                $phones = $user['WORK_PHONE'];
                            } else {
                                $phones = $template['PHONE'];
                            }
                        } else {
                            $phones = $template['PHONE'];
                        }
                    } else {
						$phones = $template['PHONE'];
					}
                    break;

                case 3:
                    $orderId = isset($arFields['ORDER_REAL_ID']) ? $arFields['ORDER_REAL_ID'] : $arFields['ORDER_ID'];
                    $dbOrderProps = CSaleOrderPropsValue::GetList(
                        array(),
                        array("ORDER_ID" => $orderId, "CODE" => $template['PHONE']),
                        false,
                        false
                    );
                    while ($arOrderProps = $dbOrderProps->GetNext()) {
                        if (!empty($arOrderProps['VALUE'])) {
                            $template['PHONE'] = $arOrderProps['VALUE'];
                            break;
                        }
                    }
                    $phones = $template['PHONE'];
            }

            if ($phones) {
                $phoneArray = explode(',', $phones);
                $cleanPhones = [];
                foreach ($phoneArray as $phone) {
                    $phone = trim($phone);
                    $phone = preg_replace("/[^0-9]/", "", $phone);
                    if (WappiSender::CheckPhoneNumber($phone)) {
                        $cleanPhones[] = $phone;
                    }
                }
                $phones = implode(',', $cleanPhones);
            }

            if ($phones) {
                foreach ($arFields as $key => $value) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $text = str_replace("#{$key}#", $value, $text);
                }
                WappiSender::SendSMS($phones, $text);
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
      $arFilter = [];
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
