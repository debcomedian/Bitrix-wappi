<?

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // ������ ����� ������
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappipro/include.php"); // ������������� ������
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappipro/prolog.php"); // ������ ������

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


$POST_RIGHT = $APPLICATION->GetGroupRight(ADMIN_MODULE_NAME);
if ($POST_RIGHT == "D")
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$sTableID = "tbl_templ"; // ID �������
$oSort = new CAdminSorting($sTableID, "ID", "desc"); // ������ ����������
$lAdmin = new CAdminList($sTableID, $oSort); // �������� ������ ������

function CheckFilter()
{
  global $FilterArr, $lAdmin;
  foreach ($FilterArr as $f) global $$f;


  return count($lAdmin->arFilterErrors)==0; // ���� ������ ����, ������ false;
}

$FilterArr = Array(
  "find",
  "find_type",
  "find_id",
  "find_active"
  );


$lAdmin->InitFilter($FilterArr);


if (CheckFilter())
{

  $arFilter = Array(
    "ID"    => ($find!="" && $find_type == "id"? $find:$find_id),
    "ACTIVE"  => $find_active
  );
}


if($lAdmin->EditAction() && $POST_RIGHT=="W")
{

  foreach($FIELDS as $ID=>$arFields)
  {
    if(!$lAdmin->IsUpdated($ID))
      continue;
    
   
    $DB->StartTransaction();
    $ID = IntVal($ID);
    $cData = new WappiTemplate;
    if(($rsData = $cData->GetByID($ID)) && ($arData = $rsData->Fetch()))
    {
      foreach($arFields as $key=>$value)
        $arData[$key]=$value;
      if(!$cData->Update($ID, $arData))
      {
        $lAdmin->AddGroupError(Loc::getMessage("rub_save_error")." ".$cData->LAST_ERROR, $ID);
        $DB->Rollback();
      }
    }
    else
    {
      $lAdmin->AddGroupError(Loc::getMessage("rub_save_error")." ".Loc::getMessage("rub_no_rubric"), $ID);
      $DB->Rollback();
    }

    $DB->Commit();
  }
}


if(($arID = $lAdmin->GroupAction()) && $POST_RIGHT=="W")
{
 
  if($_REQUEST['action_target']=='selected')
  {
    $cData = new WappiTemplate;
    $rsData = $cData->GetList(array($by=>$order), $arFilter);
    while($arRes = $rsData->Fetch())
      $arID[] = $arRes['ID'];
  }

  
  foreach($arID as $ID)
  {
    if(strlen($ID)<=0)
      continue;
       $ID = IntVal($ID);
    
   
    switch($_REQUEST['action'])
    {
    
    case "delete":
      @set_time_limit(0);
      $DB->StartTransaction();
      if(!WappiTemplate::Delete($ID))
      {
        $DB->Rollback();
        $lAdmin->AddGroupError(Loc::getMessage("rub_del_err"), $ID);
      }
      $DB->Commit();
      break;
    
   
    case "activate":
    case "deactivate":
      $cData = new WappiTemplate;
      if(($rsData = $cData->GetByID($ID)) && ($arFields = $rsData->Fetch()))
      {
        $arFields["ACTIVE"]=($_REQUEST['action']=="activate"?"Y":"N");
        if(!$cData->Update($ID, $arFields))
          $lAdmin->AddGroupError(Loc::getMessage("rub_save_error").$cData->LAST_ERROR, $ID);
      }
      else
        $lAdmin->AddGroupError(Loc::getMessage("rub_save_error")." ".Loc::getMessage("rub_no_rubric"), $ID);
      break;
    }
  }
}


$cData = new WappiTemplate;

$rsData = $cData->GetList(array($by=>$order), $arFilter);

$rsData = new CAdminResult($rsData, $sTableID);


$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("rub_nav")));

$lAdmin->AddHeaders(array(
  array(  "id"    =>"ID",
    "content"  =>"ID",
    "sort"    =>"id",
    "align"    =>"right",
    "default"  =>true,
  ),
   array( "id"    =>"TIMESTAMP_CREATE_X",
    "content"  =>Loc::getMessage("POST_F_TIMESTAMP_CREATE_X"),
    "sort"    =>"timestamp_create_x",
    "default"  =>true,
  ),
  array(  "id"    =>"TIMESTAMP_CHANGE_X",
    "content"  =>Loc::getMessage("POST_F_TIMESTAMP_CHANGE_X"),
    "sort"    =>"timestamp_change_x",
    "default"  =>true,
  ),
  array(  "id"    =>"ACTIVE",
    "content"  =>Loc::getMessage("POST_F_ACTIVE"),
    "sort"    =>"active",
    "default"  =>true,
  ),
  array(  "id"    =>"EVENT_TYPE",
    "content"  =>Loc::getMessage("POST_F_EVENT_ID"),
    "sort"    => false,
    "default"  => true,
  ),
  array(  "id"    =>"EVENT_MESSAGE_ID",
    "content"  =>Loc::getMessage("POST_F_EVENT_MESSAGE_ID"),
    "sort"    => false,
    "default"  => true,
  ),
  array(  "id"    =>"PHONE_TYPE",
    "content"  =>Loc::getMessage("POST_F_PHONE_TYPE"),
    "sort"    => false,
    "default"  => true,
  ),
  array(  "id"    =>"PHONE",
    "content"  =>Loc::getMessage("POST_F_PHONE"),
    "sort"    => false,
    "default"  => true,
  ),
  array(  "id"    =>"MESSAGE",
    "content"  =>Loc::getMessage("POST_F_MESSAGE"),
    "sort"    => false,
    "default"  => true,
  ),
));




while($arRes = $rsData->NavNext(true, "f_")):
	$rstempl = CEventMessage::GetList($by="id", $order="asc", array('ID' => $f_EVENT_MESSAGE_ID));
	if ($artempl = $rstempl->Fetch())
	{
		$f_EVENT_MESSAGE_NAME = "[".$artempl['ID']."] ".$artempl['SUBJECT'];
	}
	
    
   
    $arRes['PHONE_TYPE'] = Loc::getMessage("POST_F_PHONE_TYPE_".$arRes['PHONE_TYPE']);
	$row =& $lAdmin->AddRow($f_ID, $arRes); 
    $check1 = $check2 = $check3 = '';
    if($f_PHONE_TYPE == 1)
        $check1 = 'checked';
    elseif($f_PHONE_TYPE == 2)
        $check2 = 'checked';
    elseif($f_PHONE_TYPE == 3)
        $check3 = 'checked';

    $s_PHONE_TYPE = '<input type="radio" id="POST_F_PHONE_TYPE_1_'.$f_ID.'" name="FIELDS['.$f_ID.'][PHONE_TYPE]" value="1" '.$check1.' />&nbsp;<label for="POST_F_PHONE_TYPE_1_'.$f_ID.'">'.Loc::getMessage("POST_F_PHONE_TYPE_1").'</label><br>
           <input type="radio" id="POST_F_PHONE_TYPE_2_'.$f_ID.'" name="FIELDS['.$f_ID.'][PHONE_TYPE]" value="2"  '.$check2.' />&nbsp;<label for="POST_F_PHONE_TYPE_2_'.$f_ID.'" >'.Loc::getMessage("POST_F_PHONE_TYPE_2").'</label><br>';
    if(CModule::IncludeModule('sale'))
        $s_PHONE_TYPE .= '<input type="radio" id="POST_F_PHONE_TYPE_3_'.$f_ID.'" name="FIELDS['.$f_ID.'][PHONE_TYPE]" value="3"  '.$check3.' />&nbsp;<label for="POST_F_PHONE_TYPE_3_'.$f_ID.'">'.Loc::getMessage("POST_F_PHONE_TYPE_3").'</label><br>';

	$row->AddViewField('EVENT_MESSAGE_ID', $f_EVENT_MESSAGE_NAME);
	$row->AddEditField('PHONE_TYPE', $s_PHONE_TYPE);
	$row->AddInputField("PHONE", array("size"=>20));
	$sHTML = '<textarea rows="10" cols="20" name="FIELDS['.$f_ID.'][MESSAGE]">'.htmlspecialcharsex($f_MESSAGE).'</textarea>';
	$row->AddEditField("MESSAGE", $sHTML);

	$row->AddCheckField("ACTIVE"); 

	$arActions = Array();

	$arActions[] = array(
	"ICON"=>"edit",
	"DEFAULT"=>true,
	"TEXT"=>Loc::getMessage("EDIT"),
	"ACTION"=>$lAdmin->ActionRedirect("wappipro_template_list_edit.php?ID=".$f_ID)
	);

	if ($POST_RIGHT>="W")
	$arActions[] = array(
	  "ICON"=>"delete",
	  "TEXT"=>Loc::getMessage("DELETE"),
	  "ACTION"=>"if(confirm('".Loc::getMessage('DELETE_CONF')."')) ".$lAdmin->ActionDoGroup($f_ID, "delete")
	);

	$arActions[] = array("SEPARATOR"=>true);

	if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
	unset($arActions[count($arActions)-1]);

	$row->AddActions($arActions);

endwhile;

$lAdmin->AddFooter(
  array(
    array("title"=>Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()), // ���-�� ���������
    array("counter"=>true, "title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"), // ������� ��������� ���������
  )
);

$lAdmin->AddGroupActionTable(Array(
  "delete"=>Loc::getMessage("MAIN_ADMIN_LIST_DELETE"), // ������� ��������� ��������
  "activate"=>Loc::getMessage("MAIN_ADMIN_LIST_ACTIVATE"), // ������������ ��������� ��������
  "deactivate"=>Loc::getMessage("MAIN_ADMIN_LIST_DEACTIVATE"), // �������������� ��������� ��������
  ));

$aContext = array(
  array(
    "TEXT"=>Loc::getMessage("POST_ADD"),
    "LINK"=>"wappipro_template_list_edit.php?lang=".LANG,
    "TITLE"=>Loc::getMessage("POST_ADD_TITLE"),
    "ICON"=>"btn_new",
  ),
);


$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage("WAPPI_TEMPL_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$oFilter = new CAdminFilter(
  $sTableID."_filter",
  array(
    "ID",
    Loc::getMessage("rub_f_site"),
    Loc::getMessage("rub_f_active"),
    Loc::getMessage("rub_f_public"),
    Loc::getMessage("rub_f_auto"),
  )
);

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
