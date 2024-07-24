<?

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // Подключение пролога административной части
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappi.whatsapptelegram/include.php"); // Подключение модуля
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappi.whatsapptelegram/prolog.php"); // Подключение пролога модуля

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$module_id = 'wappi.whatsapptelegram';

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$APPLICATION->SetTitle(Loc::getMessage("WAPPI_TEMPL_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<form method="POST" action="<?php echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>" enctype="multipart/form-data">
    <?=bitrix_sessid_post(); ?>
    <p><?=Loc::getMessage("WAPPI_NUMBER_PHONES")?></p>
    <textarea rows="10" cols="45" name="send_sms[phone]"></textarea>
    <p><?=Loc::getMessage("WAPPI_TEKST_SOOBSENIA")?></p>
    <textarea rows="10" cols="45" name="send_sms[text]"></textarea>
    <br/><br/>
    <input type="submit" name="send_sms[submit]" value="<?=Loc::getMessage("WAPPI_SEND_SMS")?>" />
</form>

<?php
if(!empty($_POST['send_sms'])){
	$phone = $_POST['send_sms']['phone'];
	$phone = str_replace("\n", ',', $phone);
	$phone = preg_replace('/\s/', '',$phone);
	$text = $_POST['send_sms']['text'];

	if(IsModuleInstalled($module_id))
	{
    $status_string = WappiSender::GetApiStatus();
    echo "<h3>" . Loc::getMessage("WAPPI_SEND_STATUS") . $status_string . "</h3>";
		WappiSender::SendSMS($phone, $text);
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
