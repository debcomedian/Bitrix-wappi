<?
require_once dirname(__FILE__).'/classes/general/wappi_sender.php';

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$module_id = "wappipro";
$RIGHT = $APPLICATION->GetGroupRight($module_id);

if($RIGHT >= "R"):
	$aTabs = array(
		array(
			"DIV" => "settings",
			"TAB" => Loc::getMessage("WAPPI_TAB_SETTINGS"),
			"TITLE" => Loc::getMessage("WAPPI_TAB_TITLE_SETTINGS")
		),
		array(
			"DIV" => "rights",
			"TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
			"TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS"),
		),
	);

	$tabControl = new CAdminTabControl("WappiSettings", $aTabs);
	
	
	$tabControl->Begin();
?>
	<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&lang=<?echo LANG?>" name="WappiSettingsForm">
	
		<? $tabControl->BeginNextTab(); ?>
		<tr>
			<td width="25%"><?=Loc::getMessage("WAPPI_TOKEN")?></td>
			<td width="75%">
				<input require type="text" size="40" value="<?=COption::GetOptionString($module_id, 'tokenApi') ?>" name="settings[tokenApi]" />
			</td>
		</tr>
		<tr>
			<td width="25%"><?=Loc::getMessage("WAPPI_PROFILE")?></td>
			<td width="75%">
				<input require type="text" size="10" value="<?=COption::GetOptionString($module_id, 'profile_id') ?>" name="settings[profile_id]" />
            </td>
		</tr>
        <tr>
            <td width="25%" colspan="2" style="display: table-cell; text-align: center;">
                <?echo BeginNote();
                echo Loc::getMessage("WAPPI_PROFILE_WARNING");
                echo EndNote();?>
            </td>
        </tr>
		<tr>
            <td width="25%">
                <strong><?=Loc::getMessage("WAPPI_API_STATUS")?></strong>
            </td>
			<td width="25%" class="balance">
			<?
			if(IsModuleInstalled($module_id))
			{
				$status_string = WappiSender::GetApiStatus();
				echo $status_string;
			}
			if(!empty($_POST['send_sms'])){
				$phone = $_POST['send_sms']['phone'];
				$phone = str_replace("\n", ',', $phone);
				$phone = preg_replace('/\s/', '',$phone);
				$text = "Test message from Wappi";
				WappiSender::SendSMS($phone, $text);
			}
            ?>
			</td>
		</tr>
		<tr>
			<td width="25%"><?=Loc::getMessage("WAPPI_NUMBER_PHONES")?></td>
			<td><input required name="send_sms[phone]" pattern="^[0-9]{11,14}$"></td>
		</tr>
		<? $tabControl->BeginNextTab() ?>
		<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
		
		<?$tabControl->Buttons();?>
	    <input type="submit" name="Apply" value="<?=Loc::getMessage("WAPPI_SAVE_TITLE") ?>" title="<?=Loc::getMessage("WAPPI_SAVE_TITLE") ?>" />
	    <?if(strlen($_REQUEST["back_url_settings"])):?>
		<input type="button" name="Cancel" value="<?=Loc::getMessage("MAIN_OPT_CANCEL") ?>" title="<?=Loc::getMessage("MAIN_OPT_CANCEL_TITLE") ?>" onclick="window.location='<?=htmlspecialchars(CUtil::addslashes($_REQUEST["back_url_settings"])) ?>'" />
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"]) ?>" />
	    <?endif;?>
	    <?=bitrix_sessid_post();?>
	<?$tabControl->End();?>
	</form>
<?endif;
if($REQUEST_METHOD == "POST" && strlen($Update.$Apply.$RestoreDefaults) > 0 && $RIGHT == "W" && check_bitrix_sessid())
{
	if (strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption($module_id);
		$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));

		$redirect_to_url = $APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam();
	} 
	else 
	{
		foreach ($_POST['settings'] as $settingName => $settingValue) {
			if($settingName == 'profile_id' && strlen(trim($settingValue)) == 0)
			{
				continue;
			}
			else
			{
				COption::SetOptionString($module_id, $settingName, $settingValue);
			}
		}	
		if(strlen($_REQUEST["back_url_settings"])>0) $redirect_to_url=$_REQUEST["back_url_settings"]; 

	}
}

?>