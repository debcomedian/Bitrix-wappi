<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); 
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappi.whatsapptelegram/include.php"); 
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappi.whatsapptelegram/prolog.php"); 

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

IncludeModuleLangFile(__FILE__);


$POST_RIGHT = $APPLICATION->GetGroupRight(ADMIN_MODULE_NAME);

if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));


$aTabs = array(
  array("DIV" => "edit1", "TAB" => Loc::getMessage("POST_F_PARAM"), "ICON"=>"main_user_edit", "TITLE"=>Loc::getMessage("POST_F_PARAM_TITLE"))
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$ID = intval($ID);		
$message = null;		
$bVarsFromForm = false; 



if(
	$REQUEST_METHOD == "POST" 
	&&
	($save!="" || $apply!="") 
	&&
	$POST_RIGHT=="W"         
	&&
	check_bitrix_sessid()    
)
{
  $templateSms = new WappiTemplate();


  $arFields = Array(
    "ACTIVE"  => ($ACTIVE <> "Y"? "N":"Y"),
    "EVENT_TYPE" => $EVENT_NAME,
    "EVENT_MESSAGE_ID" => $EVENT_TEMPL,
    "MESSAGE" => $MESSAGE,
    "PHONE" => $PHONE,
    "PHONE_TYPE" => $PHONE_TYPE
  );

  if($ID > 0)
  {
    $res = $templateSms->Update($ID, $arFields);
  }
  else
  {
    $ID = $templateSms->Add($arFields);
    $res = ($ID > 0);
  }
  if($res)
  {

    if ($apply != "")
     
      LocalRedirect("/bitrix/admin/wappipro_template_list_edit.php?ID=".$ID."&mess=ok&lang=".LANG."&".$tabControl->ActiveTabParam());
    else
    
      LocalRedirect("/bitrix/admin/wappipro_template_list.php?lang=".LANG);
  }
  else
  {
   
    if($e = $APPLICATION->GetException())
      $message = new CAdminMessage(Loc::getMessage("rub_save_error"), $e);
    $bVarsFromForm = true;
  }
}
ClearVars();

$str_ACTIVE        = "Y";
$str_TIMESTAMP_CREATE_X = ConvertTimeStamp(false, "FULL");
$str_TIMESTAMP_CHANGE_X = ConvertTimeStamp(false, "FULL");
$str_EVENT_ID = null;
$str_EVENT_MESSAGE_ID = $EVENT_TEMPL;
$str_PHONE = $PHONE;
$str_PHONE_TYPE = $PHONE_TYPE;
$str_EVENT_TYPE = $EVENT_NAME;
$str_MESSAGE = $MESSAGE;



if($ID>0)
{
	$templateSms = WappiTemplate::GetByID($ID);
	if(!$templateSms->ExtractFields("str_"))
		$ID = 0;
}
	
$bEvTpl = false;



if($ID>0)
{
	$em = CEventMessage::GetByID($str_EVENT_MESSAGE_ID);
	if(!$em->ExtractEditFields("evTpl_"))
	{
		$evTpl_MESSAGE = Loc::getMessage('ERROR_EVENT_TEMPL');
		$bEvTpl = true;
	}
}


if (!empty($_REQUEST['EVENT_NAME']))
		$EVENT_NAME = $_REQUEST['EVENT_NAME'];
else
	$EVENT_NAME = $str_EVENT_TYPE;
	
if(!empty($_REQUEST['EVENT_TEMPL']))
	$EVENT_TEMPL_ID = $_REQUEST['EVENT_TEMPL'];
else
	$EVENT_TEMPL_ID = $str_EVENT_MESSAGE_ID;



$event_type_ref = array();
$arEventType = array();

$rsType = CEventType::GetList(array("LID"=>LANGUAGE_ID), array("id"=>"asc"));
while ($arType = $rsType->Fetch())
{
	$arType["NAME"] = $arType["NAME"]." [".$arType["EVENT_NAME"]."]";
	$event_type_ref[$arType["EVENT_NAME"]] = $arType;
	$arEventType[] = $arType;
    //p($arType);
}


if(empty($EVENT_NAME))
	$EVENT_NAME = $arEventType[0]['EVENT_NAME'];


$event_templ_ref = array();
$arEventTempl = array();
$rstempl = CEventMessage::GetList($by="id", $order="asc", array('TYPE_ID' => $EVENT_NAME, 'ACTIVE' => 'Y'));
while ($artempl = $rstempl->Fetch())
{
	$artempl["NAME"] = "[".$artempl['ID']."] ".$artempl['SUBJECT'];
	$event_templ_ref[$artempl['ID']] = $artempl;
	$arEventTempl[] = $artempl;
}


if(empty($EVENT_TEMPL_ID))
	$EVENT_TEMPL_ID = $arEventTempl[0]['ID'];


if($bVarsFromForm)
  $DB->InitTableVarsForEdit("wappipro_template", "", "str_");


$APPLICATION->SetTitle(($ID>0? Loc::getMessage("TITLE_EDIT") : Loc::getMessage("TITLE_ADD")));


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");


$aMenu = array(
  array(
    "TEXT"=>Loc::getMessage("BACK_LIST"),
    "TITLE"=>Loc::getMessage("BACK_LIST_EDIT"),
    "LINK"=>"wappipro_template_list.php?lang=".LANG,
    "ICON"=>"btn_list",
  )
);

if($ID>0)
{
  $aMenu[] = array("SEPARATOR"=>"Y");
  $aMenu[] = array(
    "TEXT"=>Loc::getMessage("BTN_ADD"),
    "TITLE"=>Loc::getMessage("BTN_ADD"),
    "LINK"=>"wappipro_template_list_edit.php?lang=".LANG,
    "ICON"=>"btn_new",
  );
  $aMenu[] = array(
    "TEXT"=>Loc::getMessage("BTN_DEL"),
    "TITLE"=>Loc::getMessage("BTN_DEL_TILE"),
    "LINK"=>"javascript:if(confirm('".Loc::getMessage("rubric_mnu_del_conf")."'))window.location='wappipro_template_list.php?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
    "ICON"=>"btn_delete",
  );
}


$context = new CAdminContextMenu($aMenu);


$context->Show();
?>

<?

if($_REQUEST["mess"] == "ok" && $ID>0)
  CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage("rub_saved"), "TYPE"=>"OK"));

if($message)
  echo $message->Show();
elseif($templateSms->LAST_ERROR!="")
  CAdminMessage::ShowMessage($templateSms->LAST_ERROR);
?>

<?
if(empty($str_PHONE_TYPE))
    $str_PHONE_TYPE = 1;
?>
<form method="POST" Action="<?echo $APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data" name="post_form">

<?// �������� �������������� ������ ?>
<?echo bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?echo LANG?>" />
<input type="hidden" name="ID" value="<?echo $ID?>" />
<input type="hidden" name="COPY_ID" value="<?echo $COPY_ID?>" />
<input type="hidden" name="type" value="<?echo htmlspecialcharsbx($_REQUEST["type"])?>" />

<script type="text/javascript" language="JavaScript">
<!--
var t=null;
function PutString(str)
{
	if(!t)return;
	if(t.name=="MESSAGE" || t.name=="PHONE")
	{
		t.value+=str;
		BX.fireEvent(t, 'change');
	}
}
function RadioChangePT(val)
{
    <?if($ID <= 0):?>
	//if(document.getElementById('PHONE').value.length<=0)
    //{
        if(val == 1)
            document.getElementById('PHONE').value = '';
        else if(val == 2)
            document.getElementById('PHONE').value = 'PERSONAL_PHONE';
        else if(val == 3)
            document.getElementById('PHONE').value = 'PHONE';
   // }
    <?endif;?>
}
//-->
</script>
<?

$tabControl->Begin();
?>
<?
$tabControl->BeginNextTab();
?>
  <tr>
    <td width="40%"><?echo Loc::getMessage("POST_F_ACTIVE")?></td>
    <td width="60%"><input type="checkbox" name="ACTIVE" value="Y"<?if($str_ACTIVE == "Y") echo " checked"?>></td>
  </tr>
  <?if($ID>0):?>
  <tr>
    <td><?echo Loc::getMessage("POST_F_TIMESTAMP_CREATE_X")?></td>
    <td><?=$str_TIMESTAMP_CREATE_X?></td>
  </tr>
   <tr>
    <td><?echo Loc::getMessage("POST_F_TIMESTAMP_CHANGE_X")?></td>
    <td><?=$str_TIMESTAMP_CHANGE_X?></td>
  </tr>
  <?endif;?>
	<tr>
		<td><span class="required">*</span><?echo Loc::getMessage("EVENT_NAME")?></td>
		<td><?
			if($ID>0 && $COPY_ID<=0)
			{
				$arType = $event_type_ref[$EVENT_NAME];
				$type_DESCRIPTION = htmlspecialcharsbx($arType["DESCRIPTION"]);
				$type_NAME = htmlspecialcharsbx($arType["NAME"]);
				?><input type="hidden" name="EVENT_NAME" value="<? echo $EVENT_NAME?>"><b><?echo $type_NAME?></b><?
			}
			else
			{
				$id_1st = false;
				?>
				<select name="EVENT_NAME" style="width:470px" onChange="window.location='wappipro_template_list_edit.php?lang=<?=LANGUAGE_ID?>&EVENT_NAME='+this[this.selectedIndex].value">
				<?
				foreach($event_type_ref as $ev_name=>$arType):
					if($id_1st===false)
						$id_1st = $ev_name;
				?>
					<option value="<?=htmlspecialcharsbx($arType["EVENT_NAME"])?>"<?
					if($EVENT_NAME==$arType["EVENT_NAME"])
					{
						echo " selected";
						$id_1st = $ev_name;
										}
					?>><?=htmlspecialcharsbx($arType["NAME"])?></option>
				<?
				endforeach;
				?>
				</select>
				<?
				$type_DESCRIPTION = htmlspecialcharsbx($event_type_ref[$id_1st]["DESCRIPTION"]);
			}
		?></td>
		<?
		?>
	</tr>
	<tr>
		<td><span class="required">*</span><?echo Loc::getMessage("EVENT_TEMPLATE")?></td>
		<td><?
		
			if($ID>0 && $COPY_ID<=0)
			{
				$artempl = $event_templ_ref[$EVENT_TEMPL_ID];
				$templ_NAME = htmlspecialcharsbx($artempl["NAME"]);
				$messTemplate = $artempl["MESSAGE"];
				?><input type="hidden" name="EVENT_TEMPL" value="<? echo $EVENT_TEMPL_ID?>"><b><?echo $templ_NAME?></b><?
			}
			else
			{
				//$id_1st = false;
				?>
				<select name="EVENT_TEMPL" style="width:470px" onChange="window.location='wappipro_template_list_edit.php?lang=<?=LANGUAGE_ID?>&EVENT_NAME=<?=$EVENT_NAME?>&EVENT_TEMPL='+this[this.selectedIndex].value">
				<?
				foreach($event_templ_ref as $ev_name=>$artempl):
					//if($id_1st===false)
					//	$id_1st = $ev_name;
				?>
					<option value="<?=htmlspecialcharsbx($artempl["ID"])?>"<?
					if($EVENT_TEMPL_ID==$artempl["ID"])
					{
						echo " selected";
						//$id_1st = $ev_name;
						$messTemplate = $artempl["MESSAGE"];
					}
					?>><?=htmlspecialcharsbx($artempl["NAME"])?></option>
				<?
				endforeach;
				?>
				</select>
				<?
				//$templ_DESCRIPTION = htmlspecialcharsbx($event_templ_ref[$id_1st]["DESCRIPTION"]);
			}
		?></td>
	</tr>
    <tr>
        <td><span class="required">*</span><?echo Loc::getMessage("POST_F_PHONE_TYPE")?></td>
        <td>
            <input type="radio" id="POST_F_PHONE_TYPE_1" name="PHONE_TYPE" value="1" onclick="RadioChangePT(this.value);" <?=($str_PHONE_TYPE == 1)?" checked":""?> />&nbsp;<label for="POST_F_PHONE_TYPE_1"><?=Loc::getMessage("POST_F_PHONE_TYPE_1")?></label><br>
           <input type="radio" id="POST_F_PHONE_TYPE_2" name="PHONE_TYPE" value="2" onclick="RadioChangePT(this.value);" <?=($str_PHONE_TYPE == 2)?" checked":""?> />&nbsp;<label for="POST_F_PHONE_TYPE_2" ><?=Loc::getMessage("POST_F_PHONE_TYPE_2")?></label><br><div style='margin-left:25px;'><?=Loc::getMessage("POST_F_PHONE_TYPE_2_DESCR")?></div>
           <?if(CModule::IncludeModule('sale')):?>
           <input type="radio" id="POST_F_PHONE_TYPE_3" name="PHONE_TYPE" value="3" onclick="RadioChangePT(this.value);" <?=($str_PHONE_TYPE == 3)?" checked":""?> />&nbsp;<label for="POST_F_PHONE_TYPE_3"><?=Loc::getMessage("POST_F_PHONE_TYPE_3")?></label><br><div style='margin-left:25px;'><?=Loc::getMessage("POST_F_PHONE_TYPE_3_DESCR")?></div>
           <?endif;?>
        </td>
    </tr>
    <tr>
        <td><span class="required">*</span><?echo Loc::getMessage("POST_F_PHONE")?></td>
        <td><input type="text" id="PHONE" name="PHONE" value="<?=$str_PHONE?>" onfocus="t=this" style="width:470px"></td>
    </tr>
  <tr class="heading">
		<td colspan="2"><?=Loc::getMessage("MSG_BODY")?></td>
	</tr>
	<tr>
		<td style='text-align:center'>
			<p><span class="required">*</span><b><?=Loc::getMessage("TEMPLATE_SMS")?></b></p>
		</td>
		<td style='text-align:center'>
			<p><b><?=Loc::getMessage("TEMPLATE_POST")?></b></p>
		</td>
	</tr>
	<tr>
		<td>
			<?//if(!$bEvTpl):?>
				<textarea style='width:95%;resize:vertical;' rows="20" name='MESSAGE' onfocus="t=this"><?=$str_MESSAGE?></textarea>
			<?//endif;?>
		</td>
		<td>
			<textarea readonly style='width:95%;resize:vertical;' rows="20" ><?=$messTemplate?></textarea>
		</td>
	</tr>
	<?
	$evTpl_def =
		"#DEFAULT_EMAIL_FROM# - ".Loc::getMessage("MAIN_MESS_ED_DEF_EMAIL")."
		#SITE_NAME# - ".Loc::getMessage("MAIN_MESS_ED_SITENAME")."
		#SERVER_NAME# - ".Loc::getMessage("MAIN_MESS_ED_SERVERNAME")."
	";
	function ReplaceVars($str)
	{
		return preg_replace("/(#.+?#)/", "<a title='".Loc::getMessage("MAIN_INSERT")."' href=\"javascript:PutString('\\1')\">\\1</a>", $str);
	}
	?>
	<tr>
		<td align="left" colspan="2"><br><b><?=Loc::getMessage("AVAILABLE_FIELDS")?></b><br><br>
			<?echo ReplaceVars(nl2br(trim($type_DESCRIPTION)."\r\n".$evTpl_def));?></td>
	</tr>
<?

$tabControl->Buttons(
  array(
    "disabled"=>($POST_RIGHT<"W"),
    "back_url"=>"wappipro_template_list.php?lang=".LANG,
    
  )
);
?>
<input type="hidden" name="lang" value="<?=LANG?>">
<?if($ID>0 && !$bCopy):?>
  <input type="hidden" name="ID" value="<?=$ID?>">
<?endif;?>
<?

$tabControl->End();
?>

<?

$tabControl->ShowWarnings("post_form", $message);
?>


<?

echo BeginNote();?>
<span class="required">*</span><?echo Loc::getMessage("REQUIRED_FIELDS")?>
<?echo EndNote();?>

<?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>