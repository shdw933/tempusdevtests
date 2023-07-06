<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('WB_MAXYSS_MENU_RIGHT'));
$module_id = MAXYSS_WB_NAME;
$mid = MAXYSS_WB_NAME;
$arTabs[] = array("DIV" => "edit1", "TAB" => GetMessage("WB_MAXYSS_MENU_RIGHT"), "TITLE" => GetMessage("WB_MAXYSS_MENU_RIGHT"),"ICON" => "settings","PAGE_TYPE" => "site_settings");
$tabControl = new CAdminTabControl("tabControl", $arTabs);
?> <form id="right_wb" method="post" action="<?=MAXYSS_WB_NAME?>_right.php?lang=<?=LANGUAGE_ID?>" style="margin-bottom: 10px">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) != "W"){
    ?>
    <div style="color: red"><?=GetMessage("WB_MAXYSS_NOT_RIGHT_EDIT_SETTINGS")?></div><br>
<?}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
    <input type="hidden" name="Update" value="Y">
<?
    $tabControl->Buttons(array(
        "back_url"=>MAXYSS_WB_NAME."_right.php?mid=".htmlspecialchars($mid)."&amp;lang=".LANGUAGE_ID,
    ));
?>
    <?$tabControl->End();?>
    <?=bitrix_sessid_post();?>
</form>
<div><?=GetMessage('WB_MAXYSS_RIGHT_TEXT')?></div>
<?
