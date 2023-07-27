<?
if(is_dir(dirname(__FILE__) . '/install/tools'))
    $updater->CopyFiles("install/tools", "tools/maxyss.ozon/");
if(is_dir(dirname(__FILE__) . '/install/admin'))
    $updater->CopyFiles("install/admin", "admin/");

CAdminNotify::DeleteByTag("OZON_UPDATE_CHECK");
?>