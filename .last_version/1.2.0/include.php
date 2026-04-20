<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Data\Cache;

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
    private static $processed = [];

    private static function businessKey($event, $lid, $arFields, $messageId = null)
    {
        $lidStr = is_array($lid) ? implode(',', array_filter($lid)) : (string)$lid;

        $entity = '';
        if (is_array($arFields)) {
            if (!empty($arFields['ORDER_REAL_ID'])) $entity = 'ORD:'.$arFields['ORDER_REAL_ID'];
            elseif (!empty($arFields['ORDER_ID']))  $entity = 'ORD:'.$arFields['ORDER_ID'];
            elseif (!empty($arFields['EMAIL']))     $entity = 'EML:'.$arFields['EMAIL'];
            elseif (!empty($arFields['EMAIL_TO']))  $entity = 'EML:'.(is_array($arFields['EMAIL_TO']) ? reset($arFields['EMAIL_TO']) : (string)$arFields['EMAIL_TO']);
            elseif (!empty($arFields['LOGIN']))     $entity = 'LGN:'.$arFields['LOGIN'];
        }
        if ($entity === '') $entity = 'MID:'.(string)$messageId;

        return md5((string)$event.'|'.$lidStr.'|'.$entity);
    }

    private static function dedupeHit($sig, $ttl = 90)
    {
        if (isset(self::$processed[$sig])) { return true; }
        self::$processed[$sig] = 1;

        if ($ttl > 0) {
            $cache = Cache::createInstance();
            $dir = '/wappi_dedupe';
            if ($cache->initCache($ttl, $sig, $dir)) { return true; }
            if ($cache->startDataCache()) { $cache->endDataCache(['ts'=>time()]); }
        }
        return false;
    }

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
    $sig = self::businessKey($event, $lid, $arFields, $message_id);
    if (self::dedupeHit($sig, 90)) { return true; }

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

        $templates = array();
        $seenIds   = array();

        if (!empty($message_id)) {
            $arFilter1 = array(
                'EVENT_TYPE' => $event,
                'ACTIVE'     => 'Y',
                'EVENT_MESSAGE_ID' => $message_id,
            );
            $db1 = WappiTemplate::GetList(array(), $arFilter1);
            while ($t = $db1->Fetch()) {
                $templates[] = $t;
                if (!empty($t['ID'])) { $seenIds[(int)$t['ID']] = 1; }
            }
        }

        $arFilter2 = array(
            'EVENT_TYPE' => $event,
            'ACTIVE'     => 'Y',
        );
        $db2 = WappiTemplate::GetList(array(), $arFilter2);
        while ($t = $db2->Fetch()) {
            $tid = !empty($t['ID']) ? (int)$t['ID'] : 0;
            if ($tid && isset($seenIds[$tid])) { continue; }
            $templates[] = $t;
        }

        foreach ($templates as $template) {
            $phones = '';
            $text = $template['MESSAGE'];

            if (!empty($template['SITE_ID']) && (empty($siteIds) || !in_array($template['SITE_ID'], $siteIds, true))) {
                continue;
            }

            if (!empty($template['EVENT_MESSAGE_ID']) && !empty($message_id)) {
                if ((int)$template['EVENT_MESSAGE_ID'] !== (int)$message_id) {
                    continue;
                }
            }

            switch ($template['PHONE_TYPE']) {
                case 1:
                    if (WappiSender::CheckPhoneNumber($template['PHONE'])) {
                        $phones = $template['PHONE'];
                    } else {
                        $code = $template['PHONE'];
                        $key  = trim($code, "# \t\n\r\0\x0B");

                        if ($key !== '' && array_key_exists($key, $arFields) && $arFields[$key] !== '') {
                            $val = $arFields[$key];
                            $phones = is_array($val) ? implode(',', $val) : (string)$val;
                        } else {
                            $phones = $template['PHONE'];
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
                    } elseif (!empty($arFields['EMAIL_TO'])) {
                        $emailTo = $arFields['EMAIL_TO'];
                        if (is_array($emailTo)) { $emailTo = reset($emailTo); }
                        if (is_string($emailTo)) {
                            if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $emailTo, $m)) {
                                $emailClean = $m[0];
                                $user = CUser::GetList($by = "id", $order = "desc", array('EMAIL' => $emailClean))->Fetch();
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
                            }
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
                $cleanPhones = array();
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
    } finally {
        return true;
    }
  }

  public static function WappiBeforeEventSendHandler()
  {
    try {
        $args = func_get_args();

        $event = '';
        $lid = '';
        $arFields = [];
        $messageId = null;

        $argIndex = 0;
        foreach ($args as $arg) {
            if (is_array($arg)) {
                if (isset($arg['EVENT_NAME']) && is_string($arg['EVENT_NAME'])) {
                    $event = (string)$arg['EVENT_NAME'];
                }
                if (isset($arg['C_FIELDS']) && is_array($arg['C_FIELDS'])) {
                    $arFields = $arg['C_FIELDS'];
                }
                if (isset($arg['FIELDS']) && is_array($arg['FIELDS']) && empty($arFields)) {
                    $arFields = $arg['FIELDS'];
                }
                if (empty($arFields) && !isset($arg['EVENT_NAME']) && !(isset($arg['ID']) && isset($arg['LID']))) {
                    $arFields = $arg;
                }
                if (isset($arg['LID']) && $arg['LID'] !== '') {
                    $lid = $arg['LID'];
                }
                if (isset($arg['SITE_ID']) && $arg['SITE_ID'] !== '' && $lid === '') {
                    $lid = $arg['SITE_ID'];
                }
                if (isset($arg['MESSAGE_ID']) && $arg['MESSAGE_ID'] !== '') {
                    $messageId = $arg['MESSAGE_ID'];
                }
                if (isset($arg['ID']) && $messageId === null && ctype_digit((string)$arg['ID'])) {
                    $messageId = (int)$arg['ID'];
                }
            } elseif (is_string($arg)) {
                if ($event === '') {
                    $event = $arg;
                } elseif ($lid === '') {
                    $lid = $arg;
                }
            } elseif (is_int($arg) || (is_string($arg) && ctype_digit($arg))) {
                if ($messageId === null) {
                    $messageId = (int)$arg;
                }
            }
            $argIndex++;
        }

        if (empty($event)) {
            return true;
        }

        if (is_array($arFields)) { $arFields['__WAPPI_SRC'] = 'd7'; }

        return self::WappiBeforeEventAddHandler($event, $lid, $arFields, $messageId);
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
