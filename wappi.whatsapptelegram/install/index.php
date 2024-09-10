<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class wappi_whatsapptelegram extends CModule
{
    public $MODULE_ID = 'wappi.whatsapptelegram';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {   
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_ID = 'wappi.whatsapptelegram';
        $this->MODULE_NAME = Loc::getMessage('WAPPI_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('WAPPI_MODULE_DESCRIPTION');
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->PARTNER_NAME = Loc::getMessage('WAPPI_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('WAPPI_MODULE_PARTNER_URI');
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
        $this->InstallDB(); 
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        CopyDirFiles(__DIR__ . "/components", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components", true, true);
        CopyDirFiles(__DIR__ . "/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin", true, true);
        
        file_put_contents($file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/wappipro_template_list_edit.php',
        '<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappi.whatsapptelegram/admin/template_list_edit.php");?>');
        file_put_contents($file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/wappipro_template_list.php',
        '<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappi.whatsapptelegram/admin/template_list.php");?>');
        file_put_contents($file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/wappipro_cascade_sending.php',
        '<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wappi.whatsapptelegram/admin/cascade_sending.php");?>');
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/components/wappipro");
        DeleteDirFilesEx("/bitrix/admin/wappipro_template_list_edit.php");
        DeleteDirFilesEx("/bitrix/admin/wappipro_template_list.php");
        DeleteDirFilesEx("/bitrix/admin/wappipro_cascade_sending.php");
    }

   public function InstallDB($arParams = array())
    {
        global $DB, $DBType, $APPLICATION;
        $this->errors = false;
        
        $this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/" . $this->MODULE_ID . "/install/db/" . $DBType . "/install.sql");

        if ($this->errors !== false) {
            $APPLICATION->ThrowException(implode("<br>", $this->errors));
            return false;
        } else {
            RegisterModuleDependences('main', 'OnBuildGlobalMenu', $this->MODULE_ID, 'WappiProInclude', 'OnBuildGlobalMenu');
            RegisterModuleDependences('main', 'OnBeforeEventAdd', $this->MODULE_ID, 'WappiProInclude', 'WappiBeforeEventAddHandler');
            RegisterModuleDependences('main', 'OnEventMessageDelete', $this->MODULE_ID, 'WappiProInclude', 'WappiEventMessageDeleteHandler');

            $this->SetDefaultRights();
        }
        return true;
    }

    public function SetDefaultRights()
    {
        global $APPLICATION;
        $module_id = $this->MODULE_ID;

        $dbGroups = CGroup::GetList($by = "c_sort", $order = "asc", array("ACTIVE" => "Y"));
        
        while ($arGroup = $dbGroups->Fetch()) {
            $groupId = $arGroup["ID"];
            
            if ($groupId == 1) {
                $APPLICATION->SetGroupRight($module_id, $groupId, "W");
            } else {
                $APPLICATION->SetGroupRight($module_id, $groupId, "R");
            }
        }
    }


    public function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;
		
		$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/".$DBType."/uninstall.sql");
		$strSql = "SELECT ID FROM b_file WHERE MODULE_ID='".$this->MODULE_ID."'";
		$rsFile = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arFile = $rsFile->Fetch()){
			CFile::Delete($arFile["ID"]);
		}
		
		UnRegisterModuleDependences('main', 'OnBuildGlobalMenu', $this->MODULE_ID, 'WappiProInclude', 'OnBuildGlobalMenu');
		UnRegisterModuleDependences('main', 'OnBeforeEventAdd', $this->MODULE_ID, 'WappiProInclude', 'SmsisBeforeEventAddHandler');
		UnRegisterModuleDependences('main', 'OnEventMessageDelete', $this->MODULE_ID, 'WappiProInclude', 'SmsisEventMessageDeleteHandler');
		        
		if($this->errors !== false){
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
        $APPLICATION->DelGroupRight($this->MODULE_ID);
		return true;
    }
}