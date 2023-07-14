<?
/*patchinstallmutatormark1*/
/*patchinstallmutatormark2*/
IncludeModuleLangFile(__FILE__);
use   \Bitrix\Main\Config\Option;
Class maxyss_wb extends CModule
{
    const MAXYSS_WB_NAME = 'maxyss.wb';
    var $MODULE_ID = 'maxyss.wb';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $strError = '';

    function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__)."/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage("maxyss.wb_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("maxyss.wb_MODULE_DESC");

        $this->PARTNER_NAME = GetMessage("maxyss.wb_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("maxyss.wb_PARTNER_URI");
    }

    function InstallDB($arParams = array())
    {
        return true;
    }

    function UnInstallDB($arParams = array())
    {
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function InstallFiles($arParams = array())
    {
        if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MAXYSS_WB_NAME.'/admin'))
        {
            if ($dir = opendir($p))
            {
                while (false !== $item = readdir($dir))
                {
                    if ($item == '..' || $item == '.' || $item == 'menu.php')
                        continue;
                    file_put_contents($file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.self::MAXYSS_WB_NAME.'_'.$item,
                        '<'.'? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/'.self::MAXYSS_WB_NAME.'/admin/'.$item.'");?'.'>');
                }
                closedir($dir);
            }
        }
//        if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MAXYSS_WB_NAME.'/tools'))
//        {
//            if ($dir = opendir($p))
//            {
//                while (false !== $item = readdir($dir))
//                {
//                    if ($item == '..' || $item == '.')
//                        continue;
//                    CopyDirFiles($p.'/'.$item, $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools/'.self::MAXYSS_WB_NAME.'/'.$item, $ReWrite = True, $Recursive = True);
//                }
//                closedir($dir);
//            }
//        }
        if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MAXYSS_WB_NAME.'/install/tools'))
        {
            if ($dir = opendir($p))
            {
                while (false !== $item = readdir($dir))
                {
                    if ($item == '..' || $item == '.')
                        continue;
                    CopyDirFiles($p.'/'.$item, $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools/'.self::MAXYSS_WB_NAME.'/'.$item, $ReWrite = True, $Recursive = True);
                }
                closedir($dir);
            }
        }
        return true;
    }

    function UnInstallFiles()
    {
        if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.self::MAXYSS_WB_NAME.'/admin'))
        {
            if ($dir = opendir($p))
            {
                while (false !== $item = readdir($dir))
                {
                    if ($item == '..' || $item == '.')
                        continue;
                    unlink($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.self::MAXYSS_WB_NAME.'_'.$item);
                }
                closedir($dir);
            }
        }
        if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools/'.self::MAXYSS_WB_NAME))
        {
            DeleteDirFilesEx('/bitrix/tools/'.self::MAXYSS_WB_NAME);
        }
        return true;
    }

    function DoInstall()
    {
        global $APPLICATION;
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $this->InstallFiles();
        $this->InstallDB();
        RegisterModule(self::MAXYSS_WB_NAME);
        RegisterModuleDependences( "iblock" ,  "OnIBlockPropertyBuildList" ,  self::MAXYSS_WB_NAME ,  "CCustomTypeMaxyssWBProp" ,  "GetUserTypeMaxyssWBProp" );
        RegisterModuleDependences( "main" ,  "OnBuildGlobalMenu" ,  self::MAXYSS_WB_NAME ,  "CMaxyssWb" ,  "OzonOnBuildGlobalMenu" );
//        RegisterModuleDependences( "main" ,  "OnAdminIBlockElementEdit" ,  self::MAXYSS_WB_NAME ,  "CMaxyssWbproductTab" ,  "onInit" );
        RegisterModuleDependences("main", "OnProlog", self::MAXYSS_WB_NAME , "CMaxyssWb", "buttonPackageLabelWb");
        RegisterModuleDependences("main", "OnProlog", self::MAXYSS_WB_NAME , "CMaxyssWb", "buttonPackageLabelWbDetail");
        $eventManager->RegisterEventHandler("catalog","Bitrix\Catalog\Model\Product::OnBeforeUpdate",self::MAXYSS_WB_NAME,"CMaxyssWb","uploadStock");
        $eventManager->RegisterEventHandler("sale", "OnSaleOrderBeforeSaved", self::MAXYSS_WB_NAME, "CMaxyssWbEvents","PutStatus");
        $eventManager->RegisterEventHandler("main", "OnAdminListDisplay", self::MAXYSS_WB_NAME, "CMaxyssWbEvents","WBOnAdminListDisplay");
        RegisterModuleDependences("main", "OnPageStart", self::MAXYSS_WB_NAME, "CMaxyssWbEvents", "GheckAgentRun");
        RegisterModuleDependences("iblock","OnAfterIBlockElementUpdate",self::MAXYSS_WB_NAME,"CMaxyssWbEvents","ElementUpdate");

    }

    function DoUninstall()
    {
        global $APPLICATION;

        $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::loadNewOrders(%"));
        if($res->Fetch()){
            $ro = $res->NavStart(100);
            while($r = $res->NavNext(true, "agent_")){
                CAgent::Delete($agent_ID);

            }

        }

        $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::getStatusOrders(%"));
        if($res->Fetch()){
            $ro = $res->NavStart(100);
            while($r = $res->NavNext(true, "agent_")){
                CAgent::Delete($agent_ID);
            }

        }

        $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::uploadAllStocks(%"));
        if($res->Fetch()){
            $ro = $res->NavStart(100);
            while($r = $res->NavNext(true, "agent_")){
                CAgent::Delete($agent_ID);
            }
        }

        $eventManager = \Bitrix\Main\EventManager::getInstance();

        UnRegisterModuleDependences( "iblock" ,  "OnIBlockPropertyBuildList" ,  self::MAXYSS_WB_NAME ,  "CCustomTypeMaxyssWBProp" ,  "GetUserTypeMaxyssWBProp" );
        UnRegisterModuleDependences( "main" ,  "OnBuildGlobalMenu" ,  self::MAXYSS_WB_NAME ,  "CMaxyssWb" ,  "OzonOnBuildGlobalMenu" );
        UnRegisterModuleDependences( "main" ,  "OnAdminIBlockElementEdit" ,  self::MAXYSS_WB_NAME ,  "CMaxyssWbproductTab" ,  "onInit" );

        UnRegisterModuleDependences("main", "OnProlog", self::MAXYSS_WB_NAME , "CMaxyssWb", "buttonPackageLabelWb");
        UnRegisterModuleDependences("main", "OnProlog", self::MAXYSS_WB_NAME , "CMaxyssWb", "buttonPackageLabelWbDetail");
        $eventManager->unRegisterEventHandler("catalog","Bitrix\Catalog\Model\Product::OnBeforeUpdate",self::MAXYSS_WB_NAME,"CMaxyssWb","uploadStock");
        UnRegisterModuleDependences("iblock","OnAfterIBlockElementUpdate",self::MAXYSS_WB_NAME,"CMaxyssWbEvents","ElementUpdate");
        $eventManager->unRegisterEventHandler("sale", "OnSaleOrderBeforeSaved", self::MAXYSS_WB_NAME, "CMaxyssWbEvents","PutStatus");
        $eventManager->unRegisterEventHandler("main", "OnAdminListDisplay", self::MAXYSS_WB_NAME, "CMaxyssWbEvents","WBOnAdminListDisplay");
        UnRegisterModuleDependences("main", "OnPageStart", self::MAXYSS_WB_NAME, "CMaxyssWbEvents", "GheckAgentRun");


        $this->UnInstallDB();
        $this->UnInstallFiles();
        UnRegisterModule(self::MAXYSS_WB_NAME);
    }
}
?>