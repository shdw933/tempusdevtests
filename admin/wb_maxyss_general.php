<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('MAXYSS_WB_TITLE'));
if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) < "R") die();
CJSCore::Init( 'jquery' );

global $APPLICATION;
$wb_props = false;
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency,
    Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load("ui.hint");?>
    <script type="text/javascript">
        BX.ready(function() {
            BX.UI.Hint.init(BX('wb_conainer'));
        })
    </script>
<?
if(CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 2)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_OZON_MODULE_TRIAL_2').'</font>';
if(CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 3)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_OZON_MODULE_TRIAL_3').'</font>';

if(Loader::includeModule('catalog') && Loader::includeModule('iblock') && Loader::includeModule(MAXYSS_WB_NAME) && ($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R")){
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/filter_conditions/script.js");
if(($_REQUEST['save'] || $_REQUEST['apply']) && ($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) == "W") ){
    $option = array("ACTIVE_ON"=>"active_on", "LOG_ON"=>"LOG_ON", "AUTHORIZATION"=>"authorization", "UUID"=>"uuid",  "PERIOD"=>"period", "IBLOCK_TYPE"=>"iblock_type", "IBLOCK_ID"=>"iblock_id", "DESCRIPTION"=>"description", "BASE_PICTURE"=>"base_picture", "MORE_PICTURE"=>"more_picture", "NAME_PRODUCT"=>"name_product", "ARTICLE"=>"article", "ARTICLE_LINK"=>"article_link", "SHKOD"=>"shkod", "LAND"=>"land", "PRICE_ON"=>"price_on", "PROMOCODES_ON"=>"promocodes_on", "DISCOUNTS_ON"=>"discounts_on", "PRICE_MAX_MIN"=>"price_max_min", "FILTER_PROP"=>"filter_property", "FILTER_PROP_ID"=>"filter_property_enums", "WAREHOUSES"=>"WAREHOUSES", "DEACTIVATE_WH"=>"DEACTIVATE_WH",  "KGT_WH"=>"KGT_WH", "LIMIT_WAREHOUSE_DBS"=>"LIMIT_WAREHOUSE_DBS", "PRICE_TYPE"=>"price_type", "PRICE_PROP"=>'price_prop', "PRICE_TYPE_PROP"=>"price_type_prop", "PRICE_TYPE_NO_DISCOUNT"=>"price_type_no_discount", "PRICE_TYPE_FORMULA"=>"price_type_formula", "PRICE_TYPE_FORMULA_ACTION"=>"price_type_formula_action","CUSTOM_FILTER"=>'CUSTOM_FILTER');
    CHelpMaxyssWB::saveOption($option);



    Option::set(MAXYSS_WB_NAME, "STOCK_REALY_TIME", ($_REQUEST['stock_realy_time'] == "Y")? "Y" : "N");
    $arLkWb = unserialize(Option::get(MAXYSS_WB_NAME, "LK_WB_DATA", ""));
    if(!empty($arLkWb)) {
        foreach ($arLkWb as $name => $lk){
            if(isset($_REQUEST["authorization"])){
                $arLkWbNew[$name]["authorization"] = $_REQUEST["authorization"][$name];
            }
            if(isset($_REQUEST["uuid"])){
                $arLkWbNew[$name]["uuid"] = $_REQUEST["uuid"][$name];
            }
        }
    }
    else
    {
        foreach ($_REQUEST['uuid'] as $name=>$uuid){
            if (isset($_REQUEST["authorization"])) {
                $arLkWbNew[$name]["authorization"] = $_REQUEST["authorization"][$name];
            }
                $arLkWbNew[$name]['uuid'] = $uuid;
        }
    }
    Option::set(MAXYSS_WB_NAME, "LK_WB_DATA", serialize($arLkWbNew));



    if($_REQUEST['iblock_id'] && is_array($_REQUEST['iblock_id'])){

        foreach ($_REQUEST['iblock_id'] as $iblock_id) {
            $iblock_id = intval($iblock_id);
            if ($iblock_id > 0) {
                $arProp = array(
                    GetMessage('MAXYSS_WB_NAME_PROP') => 'PROP_MAXYSS_WB',
                    'cardId' => 'PROP_MAXYSS_CARDID_WB',
                    'nmId_id' => 'PROP_MAXYSS_NMID_WB',
                    'chrtId_id' => 'PROP_MAXYSS_CHRTID_WB',
                    'nmId' => 'PROP_MAXYSS_NMID_CREATED_WB',
                    'chrtId' => 'PROP_MAXYSS_CHRTID_CREATED_WB',
                    GetMessage('PROP_MAXYSS_DISCOUNTS_WB') => 'PROP_MAXYSS_DISCOUNTS_WB',
                    GetMessage('PROP_MAXYSS_PROMOCODES_WB') => 'PROP_MAXYSS_PROMOCODES_WB',
                );
                $i_prop = 0;
                foreach ($arProp as $key => $prop) {
                    $i_prop++;
                    $arFields = array();
                    $properties = CIBlockProperty::GetList(Array("sort" => "asc", "name" => "asc"), Array("CODE" => $prop, "IBLOCK_ID" => $iblock_id));
                    if ($prop_fields = $properties->GetNext()) {
//                        continue;
                        if ($prop != "PROP_MAXYSS_WB") {
                            if($prop_fields["MULTIPLE"]=='N') {
                                $arFieldsUpdate = Array(
                                    "MULTIPLE" => "Y",
                                    "WITH_DESCRIPTION" => "Y",
                                    "MULTIPLE_CNT" => "1",
                                );
                                $ibp = new CIBlockProperty;
                                if (!$ibp->Update($prop_fields['ID'], $arFieldsUpdate)) {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'uploadAllStocks', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "uploadAllStocks", "DESCRIPTION" => serialize($ibp->LAST_ERROR)));
                                }
                            }
                        }else{
                            $arFieldsUpdate = Array(
                                "MULTIPLE" => "N",
                            );
                            $ibp = new CIBlockProperty;
                            if (!$ibp->Update($prop_fields['ID'], $arFieldsUpdate)) {
                                $eventLog = new \CEventLog;
                                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'uploadAllStocks', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "uploadAllStocks", "DESCRIPTION" => serialize($ibp->LAST_ERROR)));
                            }
                        }
                    }
                    else
                    {
                        $arFields = Array(
                            "NAME" => $key,
                            "ACTIVE" => "Y",
                            "SORT" => "900" . $i_prop,
                            "DEFAULT_VALUE" => "",
                            "MULTIPLE" => "Y",
                            "WITH_DESCRIPTION" => "Y",
                            "MULTIPLE_CNT" => "1",
                            "CODE" => $prop,
                            "PROPERTY_TYPE" => "S",
                            "IBLOCK_ID" => $iblock_id
                        );

                        if ($prop == "PROP_MAXYSS_WB"){
                            $arFields["USER_TYPE"] = "maxyss_wb";
                            $arFields["MULTIPLE"] = "N";
                        }

                        $ib_prop = new CIBlockProperty;
                        $SrcPropID = $ib_prop->Add($arFields);
                        if (IntVal($SrcPropID) <= 0)
                            $strWarning .= $ib_prop->LAST_ERROR . "<br>";
                    }
                }



                $arProp = array(
                    GetMessage('MAXYSS_WB_NAME_PROP_MAXYSS_SIMILAR_WB') => 'PROP_MAXYSS_SIMILAR_WB',
                );
                $i_prop = 5;
                foreach ($arProp as $key => $prop) {
                    $i_prop++;
                    $arFields = array();
                    $properties = CIBlockProperty::GetList(Array("sort" => "asc", "name" => "asc"), Array("CODE" => $prop, "IBLOCK_ID" => $iblock_id));
                    if ($prop_fields = $properties->GetNext()) {
                        continue;
                    } else {
                        $arFields = Array(
                            "NAME" => $key,
                            "ACTIVE" => "Y",
                            "SORT" => "900" . $i_prop,
                            "DEFAULT_VALUE" => "",
                            "CODE" => $prop,
                            "PROPERTY_TYPE" => "E",
                            "MULTIPLE" => "Y",
                            "MULTIPLE_CNT" => "5",
                            "LIST_TYPE" => "L",
                            "IBLOCK_ID" => $iblock_id,
                            "LINK_IBLOCK_ID" => $iblock_id,
                            "USER_TYPE" => 'EAutocomplete',
                            "USER_TYPE_SETTINGS" => array(
                                'VIEW' => 'E',
                                'SHOW_ADD' => 'N',
                                'IBLOCK_MESS' => 'N',
                            ),
                        );
//
                        $ib_prop = new CIBlockProperty;
                        $SrcPropID = $ib_prop->Add($arFields);
                        if (IntVal($SrcPropID) <= 0) {
                            $strWarning = $ib_prop->LAST_ERROR;
                            $eventLog = new \CEventLog;
                            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'LAST_ERROR', "MODULE_ID" => "maxyss.wb", "ITEM_ID" => "PROP_MAXYSS_SIMILAR_WB", "DESCRIPTION" => $strWarning));
                        }
                    }
                }


                // tp
                $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($iblock_id);

                if (is_array($arInfoOff)) {

                    $arPropOff = array(
                        'nmId_id' => 'PROP_MAXYSS_NMID_WB',
                        'chrtId_id' => 'PROP_MAXYSS_CHRTID_WB',
                        'nmId' => 'PROP_MAXYSS_NMID_CREATED_WB',
                        'chrtId' => 'PROP_MAXYSS_CHRTID_CREATED_WB',
                        GetMessage('PROP_MAXYSS_DISCOUNTS_WB') => 'PROP_MAXYSS_DISCOUNTS_WB',
                        GetMessage('PROP_MAXYSS_PROMOCODES_WB') => 'PROP_MAXYSS_PROMOCODES_WB',
                    );
                    foreach ($arPropOff as $key => $prop) {
                        $arFields = array();
                        $properties = CIBlockProperty::GetList(Array("sort" => "asc", "name" => "asc"), Array("CODE" => $prop, "IBLOCK_ID" => $arInfoOff['IBLOCK_ID']));
                        if ($prop_fields = $properties->GetNext()) {
                            if( $prop_fields["MULTIPLE"]=='N') {
                                $arFieldsUpdate = Array(
                                    "MULTIPLE" => "Y",
                                    "WITH_DESCRIPTION" => "Y",
                                    "MULTIPLE_CNT" => "1",
                                );
                                $ibp = new CIBlockProperty;
                                if (!$ibp->Update($prop_fields['ID'], $arFieldsUpdate)) {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'uploadAllStocks', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "uploadAllStocks", "DESCRIPTION" => serialize($ibp->LAST_ERROR)));
                                }
                            }
                        } else {
                            $arFields = Array(
                                "NAME" => $key,
                                "ACTIVE" => "Y",
                                "SORT" => "9000",
                                "DEFAULT_VALUE" => "",
                                "MULTIPLE" => "Y",
                                "WITH_DESCRIPTION" => "Y",
                                "MULTIPLE_CNT" => "1",
                                "CODE" => $prop,
                                "PROPERTY_TYPE" => "S",
                                "IBLOCK_ID" => $arInfoOff['IBLOCK_ID']
                            );
                            $ib_prop = new CIBlockProperty;
                            $SrcPropID = $ib_prop->Add($arFields);
                            if (IntVal($SrcPropID) <= 0)
                                $strWarning .= $ib_prop->LAST_ERROR . "<br>";
                        }
                    }
                }

            }
        }
        $iblock_id = '';
    }


    if($_POST['wb_props']){
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt", $_POST['wb_props']);
    };

    if($_REQUEST['stock_realy_time'] == "Y"){
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("catalog","Bitrix\Catalog\Model\Product::OnBeforeUpdate",MAXYSS_WB_NAME,"CMaxyssWb","uploadStock");
    }else{
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler("catalog","Bitrix\Catalog\Model\Product::OnBeforeUpdate",MAXYSS_WB_NAME,"CMaxyssWb","uploadStock");
    }

    $arActiveAgent = unserialize(Option::get(MAXYSS_WB_NAME, "ACTIVE_ON"));
    $arPeriodAgent = unserialize(Option::get(MAXYSS_WB_NAME, "PERIOD"));
    $arAuthorization = unserialize(Option::get(MAXYSS_WB_NAME, "AUTHORIZATION"));
    if(!empty($arActiveAgent)) {
        foreach ($arActiveAgent as $cabinet=>$value) {
            if ($value == 'Y') {
                $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::uploadAllStocks('".$cabinet."'%"));

                if ($res->Fetch()) {
                    $ro = $res->NavStart(100);
                    while ($r = $res->NavNext(true, "agent_")) {
                        $res_agent = CAgent::GetById($agent_ID);
                        if ($arRes = $res_agent->fetch()) {
                            if (intval($arRes['ID']) > 0 && $arRes['AGENT_INTERVAL'] != $arPeriodAgent[$cabinet]) {
                                $arFieldAgent = array(
                                    "AGENT_INTERVAL" => ($arPeriodAgent[$cabinet] != '')? $arPeriodAgent[$cabinet] : '600',
                                );
                                CAgent::Update(intval($arRes['ID']), $arFieldAgent);
                            }
                        }
                    }
                } elseif (!$res->Fetch()) {
                    CAgent::AddAgent(
                        "CMaxyssWb::uploadAllStocks('".$cabinet."');",
                        "maxyss.wb",
                        "N",
                        ($arPeriodAgent[$cabinet] != '')? $arPeriodAgent[$cabinet] : "600",
                        "",
                        "Y",
                        "",
                        100);
                }

            } else {
                $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::uploadAllStocks('".$cabinet."'%"));
                if ($res->Fetch()) {
                    $ro = $res->NavStart(100);
                    while ($r = $res->NavNext(true, "agent_")) {
                        CAgent::Delete($agent_ID);

                    }
                }
            }
        }
    }

}

    $arSettings = array();
    $arSettings = CMaxyssWb::settings_wb();

?>
    <?if (!wb_is_curl_installed()) {
        ?><span style="color: red"><?=GetMessage("CURL_NOT_INSTALLED")?></span><br><?
        die();
    }
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) != "W"){
    ?>
    <div style="color: red"><?=GetMessage("WB_MAXYSS_NOT_RIGHT_EDIT_SETTINGS")?></div><br>
    <?}?>
    <div style="margin-bottom: 10px;">
        <input class="add_lk" type="button" value="<?=GetMessage('MAXYSS_WB_ADD_LK_WB_BUTTON')?>" onclick="add_lk();">
    </div>
    <?

    $arTabs = array();
    $kab_key = 0;
    if(!empty($arSettings['LK_WB_DATA'])) {
        foreach ($arSettings['LK_WB_DATA'] as $key => $lk) {
            $arTabs[] = array(
                "DIV" => "edit_settings_" . ($kab_key + 1),
                "TAB" => GetMessage("WB_MAXYSS_LK_TITLE_TAB") . $key,
                "ICON" => "settings",
                "CABINET" => $key,
                "PAGE_TYPE" => "tab_settings",
            );
            $kab_key++;
        }
    }
    else
    {
        $arTabs[] = array(
            "DIV" => "edit_settings_1",
            "TAB" => GetMessage("WB_MAXYSS_LK_TITLE_TAB") . 'DEFAULT',
            "ICON" => "settings",
            "CABINET" => 'DEFAULT',
            "PAGE_TYPE" => "tab_settings",
        );
    }
    if(empty($arTabs)) die();

    $tabControl = new CAdminTabControl("tabControl", $arTabs);

    $iblockPropsOb = array();
    $skuPropsOb = array();
    ?>
<form action="<?=MAXYSS_WB_NAME?>_wb_maxyss_general.php?lang=<?=LANGUAGE_ID?>" method="post" class="wb_module_form">

    <?$tabControl->Begin();?>
    <?
    // get props dependencies if exist
    $dependencies = array();
    if(file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"))
        $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
    $t =  0;
    foreach($arTabs as $key_cab => $arTab)
    {
    $tabControl->BeginNextTab();
        // get a list of info blocks
        $iblock_id = '';
//        $iblock_id = Option::get(MAXYSS_WB_NAME, "IBLOCK_ID", "");
        $iblock_id = $arSettings['IBLOCK_ID'][$arTab['CABINET']];
        $selectedIblock = false;
    if(intval($iblock_id) > 0){?>
        <script type="text/javascript">var iblock_id_g = <?=$iblock_id?>;</script>
    <?}
    $iblock_id_select = '<option value=""></option>';
    if($iblock_type = $arSettings['IBLOCK_TYPE'][$arTab['CABINET']])
    {
        $arIBlock = array();
        $iblockFilter =  array('TYPE' => $iblock_type, 'ACTIVE' => 'Y');
        $rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
        while ($arr = $rsIBlock->Fetch())
        {
            $selected = '';
            $id = (int)$arr['ID'];
            if($iblock_id == $id){
                $selected = 'selected = "selected"';
                $selectedIblock = $id;
            }


            if (isset($offersIblock[$id]))
                continue;
            $arIBlock[$id] = '['.$id.'] '.$arr['NAME'];
            $iblock_id_select .= '<option '.$selected.' value="'.$id.'">'.'['.$id.'] '.$arr['NAME'].'</option>';
        }
    }

    // get the list of infoblock properties if it is written in b_option
    $iblock_prop_select = '<option value=""></option>';
    if(intval($iblock_id)>0)
    {
        $iblock_prop = '';
        $iblock_prop = $arSettings['MORE_PICTURE'][$arTab['CABINET']];
        $res = CIBlock::GetProperties(intval($iblock_id), Array(), Array("PROPERTY_TYPE" => "F"));
        while ($res_arr = $res->Fetch())
        {
            $selected = '';
            if($iblock_prop == $res_arr['CODE'])
                $selected = 'selected = "selected"';
            $iblock_prop_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
        }
    }

    // get the list of infoblock properties for selecting the article if it is written in b_option
    $iblock_art_select = '<option value=""></option>';
    $iblock_price_select = '<option value=""></option>';

    $iblock_land = '';
    $iblock_land_select = '';
    $iblock_land_select .= '<option value=""></option>';

    $iblock_shkod_select = '';
    $iblock_shkod_select .= '<option value=""></option>';

    $selected_name_select = '';
    $iblock_descr_select = '';


    if(intval($iblock_id)>0)
    {
        $iblock_name_product = '';
        $iblock_name_product = $arSettings['NAME_PRODUCT'][$arTab['CABINET']];

        $iblock_art = '';
        $iblock_art = $arSettings['ARTICLE'][$arTab['CABINET']] ; //Option::get(MAXYSS_WB_NAME, "ARTICLE", "");
        $iblock_shkod = $arSettings['SHKOD'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "SHKOD", "");

        $iblock_art_link = '';
        $iblock_art_link = $arSettings['ARTICLE_LINK'][$arTab['CABINET']] ; //Option::get(MAXYSS_WB_NAME, "ARTICLE", "");

        $iblock_descr = '';
        $iblock_descr = $arSettings['DESCRIPTION'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "DESCRIPTION", "");

        $iblock_price = '';
        $iblock_price = $arSettings['PRICE_TYPE_PROP'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "PRICE_TYPE_PROP", "");

        $res = CIBlock::GetProperties(intval($iblock_id), Array('name'=>'asc'), Array("MULTIPLE"	=> "N", "PROPERTY_TYPE" => "S"));
        while ($res_arr = $res->Fetch())
        {
            $selected_name = '';
            if($iblock_name_product !='' && $iblock_name_product == $res_arr['CODE'])
                $selected_name = 'selected = "selected"';
            $selected_name_select .= '<option '.$selected_name.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

            $selected = '';
            if($iblock_art !='' && $iblock_art == $res_arr['CODE'])
                $selected = 'selected = "selected"';
            $iblock_art_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

              $selected = '';
            if($iblock_art_link !='' && $iblock_art_link == $res_arr['CODE'])
                $selected = 'selected = "selected"';
            $iblock_art_link_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

            $selected = '';
            if($iblock_shkod !='' && $iblock_shkod == $res_arr['CODE'])
                $selected = 'selected = "selected"';
            $iblock_shkod_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

            $selected_descr = '';
            if($iblock_descr !='' && $iblock_descr == $res_arr['CODE'])
                $selected_descr = 'selected = "selected"';
            $iblock_descr_select .= '<option '.$selected_descr.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';


//        $selected = '';
//        if($iblock_land !='' && $iblock_land == $res_arr['CODE'])
//            $selected = 'selected = "selected"';
//        $iblock_land_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

            $selected = '';
            if($iblock_price !='' && $iblock_price == $res_arr['CODE'])
                $selected = 'selected = "selected"';
            $iblock_price_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';

        }

        // land
        $iblock_land =  $arSettings['LAND'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "LAND", "");
        $res_land = CIBlock::GetProperties(intval($iblock_id), Array('name'=>'asc'), array('MULTIPLE'=>'N'));
        while ($ar_res_land = $res_land->Fetch())
        {
            $selected = '';
            if ($iblock_land != '' && $iblock_land == $ar_res_land['CODE'])
                $selected = 'selected = "selected"';
            $iblock_land_select .= '<option ' . $selected . ' value="' . $ar_res_land['CODE'] . '">' . '[' . $ar_res_land['ID'] . '] ' . $ar_res_land['NAME'] . '</option>';

        }
    }

    // get the list of infoblock properties for selecting the filtering property of elements for unloading if it is written in b_option
    $iblock_filter_select = '<option value=""></option>';
    $iblock_filter_id_prop = '';
    if(intval($iblock_id)>0)
    {
        $iblock_filter = '';
        $iblock_filter = $arSettings['FILTER_PROP'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "FILTER_PROP", "");
        $res = CIBlock::GetProperties(intval($iblock_id), Array('name'=>'asc'), Array("PROPERTY_TYPE" => "L"));
        while ($res_arr = $res->Fetch())
        {
            $selected = '';
            if($iblock_filter !='' &&  $iblock_filter == $res_arr['CODE']) {
                $selected = 'selected = "selected"';
                $iblock_filter_id_prop = $res_arr['ID'];
            }
            $iblock_filter_select .= '<option '.$selected.' value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
        }
        if($iblock_filter !=''){
            $filter_property_enums_id = $arSettings['FILTER_PROP_ID'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "FILTER_PROP_ID", "");
            $filter_property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>intval($iblock_id), "CODE"=>$iblock_filter));
            $filter_property_enums_select = '<select name="filter_property_enums['.$arTab['CABINET'].']">';
            while($enum_fields = $filter_property_enums->GetNext())
            {
                $selected = '';
                if($filter_property_enums_id == $enum_fields["ID"])
                    $selected = 'selected = "selected"';
                $filter_property_enums_select .= '<option '.$selected.' value="'.$enum_fields["ID"].'">'.'['.$enum_fields["ID"].'] '.$enum_fields["VALUE"].'</option>';
            }
            $filter_property_enums_select .= '</select>';
        }
        else
        {
            $filter_property_enums_select = '';
        }

    }


    // get a list of infoblock types
    $arIBlockType = CIBlockParameters::GetIBlockTypes();
    $arIBlock = array();
    $iblockFilter = (
    !empty($arCurrentValues['IBLOCK_TYPE'])
        ? array('TYPE' => $arCurrentValues['IBLOCK_TYPE'], 'ACTIVE' => 'Y')
        : array('ACTIVE' => 'Y')
    );
    $rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
    $arIBlockInfos = array();
    while ($arr = $rsIBlock->Fetch())
    {
        $id = (int)$arr['ID'];
        if (isset($offersIblock[$id]))
            continue;
        $arIBlock[$id] = '['.$id.'] '.$arr['NAME'];
        $arIBlockInfos[$id] = $arr;
        $iblockCat = CCatalogSku::GetInfoByIBlock($id);
        $catType = '';
        switch($iblockCat['CATALOG_TYPE']){
            case CCatalogSku::TYPE_CATALOG:
                $catType = 'TYPE_CATALOG';
                break;
            case CCatalogSku::TYPE_FULL:
                $catType = 'TYPE_FULL';
                break;
            case CCatalogSku::TYPE_PRODUCT:
                $catType = 'TYPE_PRODUCT';
                break;
            case CCatalogSku::TYPE_OFFERS:
                $catType = 'TYPE_OFFERS';
        };
        if($catType) {
            $arIBlockInfos[$id]['CATALOG_TYPE'] = $catType;
        }
    }


    if($selectedIblock){
        $iblockPropsList = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$selectedIblock));
        $iblockProps = array();

        $HLBLOCK_ID = false;
        while ($prop_fields = $iblockPropsList->GetNext())
        {
            $iblockProps[$prop_fields['ID']] = $prop_fields;
            if(isset($prop_fields['USER_TYPE_SETTINGS']["TABLE_NAME"]) && $prop_fields['USER_TYPE_SETTINGS']["TABLE_NAME"]){
                $result = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('=TABLE_NAME'=>$prop_fields['USER_TYPE_SETTINGS']["TABLE_NAME"])));
                if($row = $result->fetch())
                {
                    $HLBLOCK_ID = $row["ID"];
                }
                if($HLBLOCK_ID){
                    $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($HLBLOCK_ID)->fetch();

                    $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);

                    $entityDataClass = $entity->getDataClass();

                    $rsData = $entityDataClass::getList(array(
                        'order' => array('UF_NAME'=>'ASC'),
                        'select' => array('*'),
                        'filter' => array('!UF_NAME'=>false)
                    ));
                    while($el = $rsData->fetch()){
                        $iblockProps[$prop_fields['ID']]['VALUES'][] = $el;
                    }
                }
            }else{
                if($prop_fields['PROPERTY_TYPE'] == "L"){
                    $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$selectedIblock, "CODE"=>$prop_fields['CODE']));
                    while($enum_fields = $property_enums->GetNext())
                    {
                        $iblockProps[$prop_fields['ID']]['VALUES'][] = $enum_fields;
                    }
                }
            }
        };
        foreach ($iblockProps as $prop){
            if(!empty($prop['VALUES'])){
                $iblockPropsOb[$key_cab][$prop['ID']] = $prop;
            }
        };


        $arSkuIblockId = CCatalogSKU::GetInfoByProductIBlock($selectedIblock);
        if (is_array($arSkuIblockId)) {
            $skuPropsList = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$arSkuIblockId['IBLOCK_ID']));
            $skuProps = array();

            $skuHLBLOCK_ID = false;
            while ($prop_fields = $skuPropsList->GetNext())
            {
                $skuProps[$prop_fields['ID']] = $prop_fields;
                if(isset($prop_fields['USER_TYPE_SETTINGS']["TABLE_NAME"]) && $prop_fields['USER_TYPE_SETTINGS']["TABLE_NAME"]){
                    $result = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('=TABLE_NAME'=>$prop_fields['USER_TYPE_SETTINGS']["TABLE_NAME"])));
                    if($row = $result->fetch())
                    {
                        $skuHLBLOCK_ID = $row["ID"];
                    }
                    if($skuHLBLOCK_ID){
                        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($skuHLBLOCK_ID)->fetch();

                        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);

                        $entityDataClass = $entity->getDataClass();

                        $rsData = $entityDataClass::getList(array(
                            'order' => array('UF_NAME'=>'ASC'),
                            'select' => array('*'),
                            'filter' => array('!UF_NAME'=>false)
                        ));
                        while($el = $rsData->fetch()){
                            $skuProps[$prop_fields['ID']]['VALUES'][] = $el;
                        }
                    }
                }else{
                    if($prop_fields['PROPERTY_TYPE'] == "L"){
                        $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$arSkuIblockId['IBLOCK_ID'], "CODE"=>$prop_fields['CODE']));
                        while($enum_fields = $property_enums->GetNext())
                        {
                            $skuProps[$prop_fields['ID']]['VALUES'][] = $enum_fields;
                        }
                    }
                }
            };
            foreach ($skuProps as $prop){
                if(!empty($prop['VALUES'])){
                    $skuPropsOb[$key_cab][$prop['ID']] = $prop;
                }
            };
        };



    }

    unset($id, $arr, $rsIBlock, $iblockFilter);


    $price_type = '';
    $price_type = $arSettings['PRICE_TYPE'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "PRICE_TYPE", "");

    $dbPriceType = CCatalogGroup::GetList(
        array("SORT" => "ASC")
    );
    $arPriceType = array();
    $price_type_select = '';
    while ($arPriceType = $dbPriceType->Fetch())
    {
        $selected = '';
        if($price_type == $arPriceType['ID'])
            $selected = 'selected = "selected"';
        $price_type_select .= '<option '.$selected.' value="'.$arPriceType['ID'].'">'.'['.$arPriceType['ID'].'] '.$arPriceType['NAME_LANG'].'</option>';

    }

    $warehouses_bd = array();
    $warehouses_bd = $arSettings['WAREHOUSES'][$arTab['CABINET']];//unserialize(Option::get(MAXYSS_WB_NAME, "WAREHOUSES", ""));
    $sklad_id = '';
    $sklad_id = $arSettings['SKLAD_ID'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "SKLAD_ID", "");
    $dbSklad = CCatalogStore::GetList(
        array('TITLE'=>'ASC','ID' => 'ASC'),
        array('ACTIVE' => 'Y'),
        false,
        false,
        array()
    );
    $arSklad = array();
    $bxSklad = array();
    $sklad_select = '';
    while ($arSklad = $dbSklad->Fetch())
    {
        $bxSklad[] = $arSklad;
        $selected = '';
        if($sklad_id == $arSklad['ID'])
            $selected = 'selected = "selected"';
        $sklad_select .= '<option '.$selected.' value="'.$arSklad['ID'].'">'.'['.$arSklad['ID'].'] '.$arSklad['TITLE'].'</option>';
    }
    $warehouses = CRestQueryWB::rest_warehouses_get($arSettings['LK_WB_DATA'][$arTab['CABINET']]['authorization']);
    ?>





<!--    <div class="adm-detail-content-item-block wb_conainer">-->

<!--            new api-->
            <tr style="display: none!important;"><td colspan="2">
                    <input type="hidden" name="LOG_ON[<?=$arTab["CABINET"]?>]" style="display: none!important;" value="N">
                    <input type="checkbox" name="LOG_ON[<?=$arTab["CABINET"]?>]" value="Y">
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_WB_MODULE_AUTH')?></td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UUID')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="text" name="uuid[<?=$arTab["CABINET"]?>]" id="uuid_<?=$arTab["CABINET"]?>" value="<?echo  $arSettings['UUID'][$arTab['CABINET']];//Option::get(MAXYSS_WB_NAME, "UUID", "");?>">
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_AUTHORIZATION')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="text" name="authorization[<?=$arTab["CABINET"]?>]" id="authorization_<?=$arTab["CABINET"]?>" value="<?echo  $arSettings['AUTHORIZATION'][$arTab['CABINET']];?>"><span data-hint="<?=GetMessage('MAXYSS_WB_AUTHORIZATION_TIP')?>"></span>
                </td>
            </tr>
<!--            new api-->
            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_WB_MODULE_WH_TITLE')?></td>
            </tr>
            <?if(is_array($warehouses)){
                $arDeactivateWarehouses = $arSettings['DEACTIVATE_WH'][$arTab['CABINET']];
                $arKgtWarehouses = $arSettings['KGT_WH'][$arTab['CABINET']];
                $arLimitWarehouseDbs = $arSettings['LIMIT_WAREHOUSE_DBS'][$arTab['CABINET']];
                foreach ($warehouses as $wh){
                    $sklad_select = '';
                    foreach ($bxSklad  as $sklad)
                    {
                        $selected = '';
                        if(is_array($warehouses_bd) && isset($warehouses_bd[$wh['id']]) && array_search($sklad['ID'], $warehouses_bd[$wh['id']]) !== false)   $selected = 'selected = "selected"';

                        $sklad_select .= '<option '.$selected.' value="'.$sklad['ID'].'">'.'['.$sklad['ID'].'] '.$sklad['TITLE'].'</option>';
                    }
                    ?>
                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <?echo $wh['name'].' ('.$wh['id'].')';?>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <table>
                                <tr>
                                    <td rowspan="2">
                                        <input type="hidden" name="WAREHOUSES[<?=$arTab["CABINET"]?>][<?=$wh['id']?>][]">
                                        <select multiple name="WAREHOUSES[<?=$arTab["CABINET"]?>][<?=$wh['id']?>][]">
                                            <?echo $sklad_select;?>
                                        </select>
                                    </td>
                                     <td> <?=GetMessage('MAXYSS_WB_DEACTIVATE_WAREHOUSE')?>
                                         <input  type="hidden" name="DEACTIVATE_WH[<?=$arTab["CABINET"]?>][<?=$wh["id"]?>]" value="N">
                                         <input id="<?=$wh["id"]?>" type="checkbox" name="DEACTIVATE_WH[<?=$arTab["CABINET"]?>][<?=$wh["id"]?>]" <?echo ($arDeactivateWarehouses[$wh["id"]] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                                     </td>
                                     <td> <?=GetMessage('MAXYSS_WB_KGT_WAREHOUSE')?>
                                         <input  type="hidden" name="KGT_WH[<?=$arTab["CABINET"]?>][<?=$wh["id"]?>]" value="N">
                                         <input id="KGT_WH_<?=$wh["id"]?>" type="checkbox" name="KGT_WH[<?=$arTab["CABINET"]?>][<?=$wh["id"]?>]" <?echo ($arKgtWarehouses[$wh["id"]] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                                     </td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <?=GetMessage('MAXYSS_WB_LIMIT_WAREHOUSE')?>
                                        <input type="number" name="LIMIT_WAREHOUSE_DBS[<?=$arTab["CABINET"]?>][<?=$wh["id"]?>]"  value="<?echo $arLimitWarehouseDbs[$wh["id"]];?>"><span data-hint="<?=GetMessage('MAXYSS_WB_LIMIT_WAREHOUSE_TIP')?>"></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                <?}
            }
            else
            {?>
                <tr>
                    <td colspan="2" >
                <?
                if(strlen($warehouses)>0) echo '<font style="color: red">'.$warehouses.'</font><br>';
                echo GetMessage('MAXYSS_WB_WAREHOUSES_EMPTI');
                ?>
                    </td>
            <?}?>
            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_WB_MODULE_ACTIVITY')?></td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_AGENT_ACTIVE')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="active_on[<?=$arTab["CABINET"]?>]" value="N">
                    <input type="checkbox" name="active_on[<?=$arTab["CABINET"]?>]" id="wb_active_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox" <?echo  ($arSettings['ACTIVE_ON'][$arTab['CABINET']] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="wb_active_<?=$arTab["CABINET"]?>" title=""></label>
                    <?
                    $res = CEventLog::GetList(Array("ID" => "DESC"), array('AUDIT_TYPE_ID'=>'UPLOAD_STOCK'), array("nTopCount"=>1));
                    if( $arRes = $res->fetch()){
                        echo '<i>&nbsp;&nbsp;&nbsp;'.GetMessage("WB_MAXYSS_LAST_STOCK_UPLOAD") .$arRes['TIMESTAMP_X']. '</i>';
                    }?>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_PERIOD_AGENT_TIME')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="text" name="period[<?=$arTab["CABINET"]?>]" value="<?echo ($arSettings['PERIOD'][$arTab['CABINET']])? $arSettings['PERIOD'][$arTab['CABINET']] : '600';?>">
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UPLOAD_STOCK_NULL')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="button" value="<?=GetMessage('MAXYSS_WB_UPLOAD_BUTTON')?>" onclick="upload_stock_null('<?=$arTab['CABINET']?>');">
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_WB_MODULE_UPLOAD_PRICE')?></td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_PRICE_TIPE')?></td>
                <td class="adm-detail-content-cell-r">
                    <table>
                        <tr>
                            <td>
                                <?echo GetMessage('MAXYSS_WB_PRICE_FROM_PROP')?>
                            </td>
                            <td>
                                <input type="hidden" name="price_prop[<?=$arTab["CABINET"]?>]" value="N">
                                <input type="checkbox" name="price_prop[<?=$arTab["CABINET"]?>]" id="price_prop_<?=$arTab["CABINET"]?>" <?echo ($arSettings['PRICE_PROP'][$arTab['CABINET']]=="Y")? 'checked' : '' ;?> class="adm-designed-checkbox price_to_prop" value="Y">
                                <label class="adm-designed-checkbox-label" for="price_prop_<?=$arTab["CABINET"]?>" title=""></label>
                            </td>
                            <td <?echo ($arSettings['PRICE_PROP'][$arTab['CABINET']]=="Y")? '' : 'style="display: none"' ;?>>
                                <select name="price_type_prop[<?=$arTab["CABINET"]?>]">
                                    <?echo $iblock_price_select?>
                                </select>
                            </td>
                            <td <?echo ($arSettings['PRICE_PROP'][$arTab['CABINET']]=="Y")? 'style="display: none"' : '' ;?>>
                                <select name="price_type[<?=$arTab["CABINET"]?>]">
                                    <?echo $price_type_select?>
                                </select>
                            </td>
                            <td <?echo ($arSettings['PRICE_PROP'][$arTab['CABINET']]=="Y")? 'style="display: none"' : '' ;?>>
                                <input type="hidden" name="price_type_no_discount[<?=$arTab["CABINET"]?>]" value="N">
                                <input type="checkbox" name="price_type_no_discount[<?=$arTab["CABINET"]?>]" <?echo ($arSettings['PRICE_TYPE_NO_DISCOUNT'][$arTab['CABINET']]=="Y")? 'checked' : '';?> id="price_type_no_discount_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox"  value="Y">
                                <label class="adm-designed-checkbox-label" for="price_type_no_discount_<?=$arTab["CABINET"]?>" title=""></label>
                            </td>
                            <td <?echo ($arSettings['PRICE_PROP'][$arTab['CABINET']]=="Y")? 'style="display: none"' : '' ;?>>
                                <?echo GetMessage('MAXYSS_WB_PRICE_WITHOUT_DISCOUNT')?>
                            </td>
                            <td>
                                <select name="price_max_min[<?=$arTab["CABINET"]?>]">
                                    <option <?echo ($arSettings['PRICE_MAX_MIN'][$arTab['CABINET']]=="MIN")? 'selected' : '' ;?> value="MIN"><?=GetMessage('WB_MAXYSS_PRICE_MIN')?></option>
                                    <option <?echo ($arSettings['PRICE_MAX_MIN'][$arTab['CABINET']]=="MAX")? 'selected' : '' ;?> value="MAX"><?=GetMessage('WB_MAXYSS_PRICE_MAX')?></option>
                                </select><span data-hint="<?=GetMessage('WB_MAXYSS_PRICE_MAX_MAX_TIP')?>"></span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?echo GetMessage('MAXYSS_WB_PRICE_EDIT')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="price_type_formula_action[<?=$arTab["CABINET"]?>]">
                        <option value="NOT" <?echo ($arSettings['PRICE_TYPE_FORMULA_ACTION'][$arTab['CABINET']] == 'NOT')? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_PRICE_TYPE_FORMULA_ACTION_NOT')?></option>
                        <option value="MULTIPLY" <?echo ($arSettings['PRICE_TYPE_FORMULA_ACTION'][$arTab['CABINET']] == 'MULTIPLY')? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_PRICE_TYPE_FORMULA_ACTION_MULTIPLY')?></option>
                        <option value="DIVIDE" <?echo ($arSettings['PRICE_TYPE_FORMULA_ACTION'][$arTab['CABINET']] == 'DIVIDE')? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_PRICE_TYPE_FORMULA_ACTION_DIVIDE')?></option>
                        <option value="ADD" <?echo ($arSettings['PRICE_TYPE_FORMULA_ACTION'][$arTab['CABINET']] == 'ADD')? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_PRICE_TYPE_FORMULA_ACTION_ADD')?></option>
                        <option value="SUBTRACT" <?echo ($arSettings['PRICE_TYPE_FORMULA_ACTION'][$arTab['CABINET']] == 'SUBTRACT')? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_PRICE_TYPE_FORMULA_ACTION_SUBTRACT')?></option>
                    </select>
                    <input type="text" name="price_type_formula[<?=$arTab["CABINET"]?>]" value="<?echo $arSettings['PRICE_TYPE_FORMULA'][$arTab['CABINET']];?>">
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UPLOAD_PRICE_YES')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="price_on[<?=$arTab["CABINET"]?>]" value="N">
                    <input type="checkbox" name="price_on[<?=$arTab["CABINET"]?>]" id="price_on_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox" <?echo  ($arSettings['PRICE_ON'][$arTab['CABINET']] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="price_on_<?=$arTab["CABINET"]?>" title=""></label><span data-hint="<?=GetMessage("MAXYSS_WB_UPLOAD_PRICE_YES_TIP")?>"></span>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UPLOAD_DISCOUNTS_YES')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="discounts_on[<?=$arTab["CABINET"]?>]" value="N">
                    <input type="checkbox" name="discounts_on[<?=$arTab["CABINET"]?>]" id="discounts_on_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox" <?echo  ($arSettings['DISCOUNTS_ON'][$arTab['CABINET']] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="discounts_on_<?=$arTab["CABINET"]?>" title=""></label><span data-hint="<?=GetMessage("MAXYSS_WB_UPLOAD_DISCOUNTS_YES_TIP")?>"></span>
                </td>
            </tr>
            <?/*?><tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UPLOAD_PROMOCODES_YES')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="promocodes_on[<?=$arTab["CABINET"]?>]" value="N">
                    <input type="checkbox" name="promocodes_on[<?=$arTab["CABINET"]?>]" id="promocodes_on_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox" <?echo  ($arSettings['PROMOCODES_ON'][$arTab['CABINET']] == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="promocodes_on_<?=$arTab["CABINET"]?>" title=""></label><span data-hint="<?=GetMessage("MAXYSS_WB_UPLOAD_PROMOCODES_YES_TIP")?>"></span>
                </td>
            </tr><?*/?>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UPLOAD_PRICE_IMMEDIATELY')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="button" value="<?=GetMessage('WB_MAXYSS_PRICE_UPLOAD_BUTTON')?>" onclick="upload_all_price('<?=$arTab['CABINET']?>');">
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UPLOAD_DISCOUNTS_IMMEDIATELY')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="button" value="<?=GetMessage('WB_MAXYSS_DISCOUNTS_UPLOAD_BUTTON')?>" onclick="upload_all_discounts('<?=$arTab['CABINET']?>');">
                </td>
            </tr>
            <?/*?><tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_UPLOAD_PROMOCODES_IMMEDIATELY')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="button" value="<?=GetMessage('WB_MAXYSS_PROMOCODES_UPLOAD_BUTTON')?>" onclick="upload_all_promocodes('<?=$arTab['CABINET']?>');">
                </td>
            </tr><?*/?>
            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_WB_INTEGRATION_SETTINGS')?></td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_IBLOCK_TYPE')?></td>
                <td class="adm-detail-content-cell-r">
                    <select onchange="iblock_update($(this).val(), '<?=$arTab["DIV"]?>');" name="iblock_type[<?=$arTab["CABINET"]?>]">
                        <option value=""></option>
                        <?foreach ($arIBlockType as $key => $type){?>
                        <option value="<?=$key?>" <?echo (($arSettings['IBLOCK_TYPE'][$arTab['CABINET']] == $key)? 'selected = "selected"' : '')?>><?=$type?></option>
                        <?}?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_IBLOCK_ID')?></td>
                <td class="adm-detail-content-cell-r">
                    <select onchange="property_update($(this).val(), '<?=$arTab["DIV"]?>', '<?=$arTab["CABINET"]?>');" name="iblock_id[<?=$arTab["CABINET"]?>]">
                        <?echo $iblock_id_select;?>
                    </select><span data-hint="<?=GetMessage('MAXYSS_WB_IBLOCK_TIP')?>"></span>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_ANONS')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="description[<?=$arTab["CABINET"]?>]">
                        <option value="DETAIL_TEXT" <?echo ( $arSettings['DESCRIPTION'][$arTab['CABINET']] == "DETAIL_TEXT" )? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_ANONS_DETAIL')?></option>
                        <option value="PREVIEW_TEXT" <?echo ( $arSettings['DESCRIPTION'][$arTab['CABINET']]  == "PREVIEW_TEXT" )? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_ANONS_ANONS')?></option>
                        <?if(!empty($iblock_descr_select)){?>
                            <optgroup label="<?=GetMessage('MAXYSS_WB_PROP_TITLE')?>">
                                <?echo $iblock_descr_select?>
                            </optgroup>
                        <?}?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_PICTURE')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="base_picture[<?=$arTab["CABINET"]?>]">
                        <option value="DETAIL_PICTURE" <?echo ( $arSettings['BASE_PICTURE'][$arTab['CABINET']]  == "DETAIL_PICTURE" )? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_PICTURE_DETAIL')?></option>
                        <option value="PREVIEW_PICTURE" <?echo ( $arSettings['BASE_PICTURE'][$arTab['CABINET']]  == "PREVIEW_PICTURE" )? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_PICTURE_ANONS')?></option>
                    </select>
                </td>
            </tr>
            <tr>
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_PICTURE_MORE_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="more_picture[<?=$arTab["CABINET"]?>]">
                        <?echo $iblock_prop_select?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_NAME_PRODUCT')?></td>
                <td class="adm-detail-content-cell-r">

                    <select name="name_product[<?=$arTab["CABINET"]?>]">
                        <option value="NAME" <?echo ( $arSettings['NAME_PRODUCT'][$arTab['CABINET']]  == "NAME" )? 'selected' : '';?>><?=GetMessage('MAXYSS_WB_NAME_NAME')?></option>
                        <?if(!empty($selected_name_select)){?>
                            <optgroup label="<?=GetMessage('MAXYSS_WB_PROP_TITLE')?>">
                                <?echo $selected_name_select?>
                            </optgroup>
                        <?}?>
                    </select><span data-hint="<?=GetMessage('MAXYSS_WB_NAME_PRODUCT_TIP')?>"></span>
                </td>
            </tr>
            <tr>
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_ARTICLE_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="article[<?=$arTab["CABINET"]?>]">
                        <?echo $iblock_art_select?>
                    </select><span data-hint="<?=GetMessage('MAXYSS_WB_ARTICLE_PROP_TIP')?>"></span>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_ARTICLE_LINK_PROP')?></td>
                    <td class="adm-detail-content-cell-r">
                        <select name="article_link[<?=$arTab["CABINET"]?>]">
                            <option value=""></option>
                            <?echo $iblock_art_link_select?>
                        </select><span data-hint="<?=GetMessage('MAXYSS_WB_ARTICLE_LINK_PROP_TIP')?>"></span>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_SHKOD_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="shkod[<?=$arTab["CABINET"]?>]">
                        <?echo $iblock_shkod_select?>
                    </select>
                    <span data-hint="<?=GetMessage('MAXYSS_WB_BARCODE_TIP')?>"></span>
                </td>
            </tr>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_LAND_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="land[<?=$arTab["CABINET"]?>]">
                        <?echo $iblock_land_select?>
                    </select>
                    <span data-hint="<?=GetMessage('MAXYSS_WB_LAND_PROP_TIP')?>"></span>
                </td>
            </tr>
            <tr>
            <?/*
            <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_FILTER_PROP')?></td>
                <td class="adm-detail-content-cell-r">
                    <select name="filter_property[<?=$arTab["CABINET"]?>]" onchange="filter_property_update($(this), '<?=$arTab["DIV"]?>', '<?=$arTab["CABINET"]?>');">
                        <?echo $iblock_filter_select?>
                    </select>
                        <?echo $filter_property_enums_select?>
                </td>
            </tr>
            */?>



        <tr id="custom_filter_td_<?=$arTab["CABINET"]?>">
            <?
                $filterDataValues = array();
                if(intval($iblock_id)>0) {
                    $arCurrentValues['IBLOCK_ID'] = $iblock_id;
                    $filterDataValues['iblockId'] = (int)$arCurrentValues['IBLOCK_ID'];
                    $offers = CCatalogSku::GetInfoByProductIBlock($arCurrentValues['IBLOCK_ID']);
                    if (!empty($offers))
                    {
                        $filterDataValues['offersIblockId'] = $offers['IBLOCK_ID'];
                        $propertyIterator = Iblock\PropertyTable::getList(array(
                            'select' => array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'LINK_IBLOCK_ID', 'USER_TYPE', 'SORT'),
                            'filter' => array('=IBLOCK_ID' => $offers['IBLOCK_ID'], '=ACTIVE' => 'Y', '!=ID' => $offers['SKU_PROPERTY_ID']),
                            'order' => array('SORT' => 'ASC', 'NAME' => 'ASC')
                        ));
                        while ($property = $propertyIterator->fetch())
                        {
                            $propertyCode = (string)$property['CODE'];

                            if ($propertyCode === '')
                            {
                                $propertyCode = $property['ID'];
                            }

                            $propertyName = '['.$propertyCode.'] '.$property['NAME'];
                            $arProperty_Offers[$propertyCode] = $propertyName;

                            if ($property['PROPERTY_TYPE'] != Iblock\PropertyTable::TYPE_FILE)
                            {
                                $arProperty_OffersWithoutFile[$propertyCode] = $propertyName;
                            }
                        }
                        unset($propertyCode, $propertyName, $property, $propertyIterator);
                    }
                }
                if (!empty($filterDataValues)) {
                    $arComponentParameters['CUSTOM_FILTER'] = array(
                        'PARENT' => 'DATA_SOURCE',
                        'NAME' => GetMessage('MAXYSS_WB_FILTER_CUSTOM'),
                        'TYPE' => 'CUSTOM',
                        'JS_FILE' => '/bitrix/tools/maxyss.wb/filter_conditions/script.js?16217988881',//CatalogSectionComponent::getSettingsScript($componentPath, 'filter_conditions'),
                        'JS_EVENT' => 'initFilterConditionsControl',
                        'JS_MESSAGES' => Json::encode(array(
                            'invalid' => GetMessage('MAXYSS_WB_FILTER_CUSTOM_INVALID')
                        )),
                        'JS_DATA' => Json::encode($filterDataValues),
                        'DEFAULT' => ''
                    );

                    $params_['propertyParams'] = $arComponentParameters['CUSTOM_FILTER'];
                    $params_['data'] = $arComponentParameters['CUSTOM_FILTER']['JS_DATA'];
                    $params_['propertyID'] = 'CUSTOM_FILTER_' . $key_cab;
                    $params_['oInput'] = '';
                    $params_['oCont'] = '';
                    if($arSettings["CUSTOM_FILTER"][$arTab["CABINET"]]){
                        $filter_string = $arSettings["CUSTOM_FILTER"][$arTab["CABINET"]];
                    }else if($iblock_filter_id_prop != '' && $filter_property_enums_id != ''){
                        $filter_arr = array(
                            "CLASS_ID" => "CondGroup",
                            "DATA" => array("All" => "AND", "True" => "True"),
                            "CHILDREN" => array(
                                array
                                (
                                    "CLASS_ID" => "CondIBProp:" . $iblock_id . ":" . $iblock_filter_id_prop,
                                    "DATA" => array
                                    (
                                        "logic" => "Equal",
                                        "value" => $filter_property_enums_id
                                    )

                                )
                            )
                        );
                        $filter_string = Json::encode($filter_arr);
                    }else{
                        $filter_string = Json::encode(array(
                            "CLASS_ID" => "CondGroup",
                            "DATA" => array("All" => "AND", "True" => "True"),
                            "CHILDREN" => array(

                            )
                        ));
                    }?>

                    <td class="adm-detail-content-cell-l">
                        <?=GetMessage('MAXYSS_WB_FILTER_CUSTOM')?>
                    </td>
                    <td>
                        <div id = 'CUSTOM_FILTER_DIV_<?=$key_cab?>'>
                            <input name="CUSTOM_FILTER[<?=$arTab["CABINET"]?>]" id = 'CUSTOM_FILTER_<?=$key_cab?>' value='<?echo $filter_string?>' type="hidden">
                        </div>
                        <script>
                            let propertyParamsJs_<?=$key_cab?> = <?=CUtil::PhpToJSObject($params_)?>;
                            propertyParamsJs_<?=$key_cab?>['oCont'] = document.querySelector('#CUSTOM_FILTER_DIV_<?=$key_cab?>');
                            propertyParamsJs_<?=$key_cab?>['oInput'] = document.querySelector('#CUSTOM_FILTER_<?=$key_cab?>');
                            initFilterConditionsControl(propertyParamsJs_<?=$key_cab?>);
                        </script>
                    </td>
                <?}?>
        </tr>


    <?/*?><tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_TP_AS_PRODUCT')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="tp_as_product[<?=$arTab["CABINET"]?>]" value="N">
                    <input type="checkbox" name="tp_as_product[<?=$arTab["CABINET"]?>]" id="tp_as_product_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox" <?echo  ($arSettings['TP_AS_PRODUCT'][$arTab['CABINET']]  == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="tp_as_product_<?=$arTab["CABINET"]?>" title=""></label><span data-hint="<?=GetMessage('MAXYSS_WB_TP_AS_PRODUCT_TIP')?>"></span>
                </td>
            </tr><?*/?>
            <tr>
                <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_STOCK_REALY_TIME')?></td>
                <td class="adm-detail-content-cell-r">
                    <input type="hidden" name="stock_realy_time" value="N">
                    <input type="checkbox" name="stock_realy_time" id="stock_realy_time_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox" <?echo  (Option::get(MAXYSS_WB_NAME, "STOCK_REALY_TIME", "") == 'Y')? 'checked = "checked"' : ''?> value="Y">
                    <label class="adm-designed-checkbox-label" for="stock_realy_time_<?=$arTab["CABINET"]?>" title=""></label><span data-hint="<?=GetMessage('MAXYSS_WB_STOCK_REALY_TIME_TIP')?>"></span>
                </td>
            </tr>
            <?/*
                iblock-catalog porperties start
            */?>
            <tr class="heading">
                <td colspan="2"><?=GetMessage('MAXYSS_WB_CAT_PROPS_DEPENDENCES')?></td>
            </tr>
            <?
            if(!$dependencies || empty($dependencies['WB_CAT_PROP'][$t])){?>
                <tr id="WB_CAT_PROPS_0" class="wb_control-row" data-num="0" data-entity="CAT_PROPS">
                    <?if($iblockPropsOb[$key_cab] && !empty($iblockPropsOb)){?>
                        <td class="adm-detail-content-cell-l">
                            <select name="WB_CAT_PROP_0[<?=$arTab["CABINET"]?>]" id="WB_CAT_PROP_0" data-entity="WB_CAT_PROP" class="wb_cat_props_select">
                                <option value="" selected></option>
                                <?foreach ($iblockPropsOb[$key_cab] as $prop){?>
                                    <option value="<?=$prop['ID']?>"><?=$prop['NAME']?></option>
                                <?}?>
                            </select>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <select name="WB_PROPS_0[<?=$arTab["CABINET"]?>]" id="WB_PROPS_0" data-entity="WB_PROPS" class="wb_props_select">
                                <option value=""></option>
                                <option value="colors" data-value="colors"><?=GetMessage('MAXYSS_WB_COLORS_WB')?></option>
                                <option value="wbsizes" data-value="wbsizes"><?=GetMessage('MAXYSS_WB_SIZE_WB')?></option>
                                <option value="tech-sizes" data-value="tech-sizes"><?=GetMessage('MAXYSS_WB_SIZE_TECH_WB')?></option>
                            </select>
                            <input type="text" onkeyup="getWbTypes(this);" placeholder="<?=GetMessage("MAXYSS_WB_SPRAVKA_PLACEHOLDER")?>" title="<?=GetMessage('MAXYSS_WB_SPRAVKA_TITLE')?>" data-wbaction="wbtypes">
                            <select name="sex" id="" data-wb="sex" style="display: none" title="<?=GetMessage("MAXYSS_WB_SPRAVKA_SELECT_TITLE")?>">
                                <option value="" style="color: #c5c5c5"><?=GetMessage("MAXYSS_WB_POL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_MALE")?>"><?=GetMessage("MAXYSS_WB_MALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_FEMALE")?>"><?=GetMessage("MAXYSS_WB_FEMALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_CHILD")?>"><?=GetMessage("MAXYSS_WB_CHILD")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_GIRL")?>"><?=GetMessage("MAXYSS_WB_GIRL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_BOY")?>"><?=GetMessage("MAXYSS_WB_BOY")?></option>
                            </select>
                            <a href="javascript:void(0);" class="wb_control-add-but" onclick="addNewDep(this);" style="display: none" title="<?=GetMessage('MAXYSS_WB_PROPS_ADD')?>"></a>
                        </td>
                    <?}?>
                </tr>
            <?}elseif($dependencies && !empty($dependencies['WB_CAT_PROP'][$t]) && !empty($iblockPropsOb)){?>
                <?
                $counter = 1;
                foreach ($dependencies['WB_CAT_PROP'][$t] as $key => $propItem){?>
                    <tr id="WB_CAT_PROPS_<?=$key?>" data-num="<?=$key?>" data-entity="CAT_PROPS" class="wb_control-row">
                        <td class="adm-detail-content-cell-l">
                            <select name="WB_CAT_PROP_<?=$key?>" id="WB_CAT_PROP_<?=$key?>" data-entity="WB_CAT_PROP" class="wb_cat_props_select">
                                <option value=""></option>
                                <?foreach ($iblockPropsOb[$key_cab] as $prop){?>
                                    <option value="<?=$prop['ID']?>" <?echo($prop['ID'] == $propItem['propID'])?'selected':''?>><?=$prop['NAME']?></option>
                                <?}?>
                            </select>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <select name="WB_PROPS_<?=$key?>" id="WB_PROPS_<?=$key?>" data-entity="WB_PROPS" class="wb_props_select" style="">
                                <option value=""></option>
                                <option value="colors" <?echo ($propItem['propWB'] == 'colors')?'selected':''?> data-value="colors"><?=GetMessage('MAXYSS_WB_COLORS_WB')?></option>
                                <option value="wbsizes" <?echo ($propItem['propWB'] == 'wbsizes')?'selected':''?> data-value="wbsizes"><?=GetMessage('MAXYSS_WB_SIZE_WB')?></option>
                                <option value="tech-sizes" <?echo ($propItem['propWB'] == 'tech-sizes')?'selected':''?> data-value="tech-sizes"><?=GetMessage('MAXYSS_WB_SIZE_TECH_WB')?></option>
                            </select>
                            <input type="text" onkeyup="getWbTypes(this);" placeholder="<?=GetMessage("MAXYSS_WB_SPRAVKA_PLACEHOLDER")?>" title="<?=GetMessage('MAXYSS_WB_SPRAVKA_TITLE')?>" data-wbaction="wbtypes" style="<?=($propItem['propWB'] == 'wbsizes')?'':'display:none;'?>">
                            <select name="sex" id="" data-wb="sex" style="display: none" title="<?=GetMessage("MAXYSS_WB_SPRAVKA_SELECT_TITLE")?>">
                                <option value="" style="color: #c5c5c5"><?=GetMessage("MAXYSS_WB_POL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_MALE")?>"><?=GetMessage("MAXYSS_WB_MALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_FEMALE")?>"><?=GetMessage("MAXYSS_WB_FEMALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_CHILD")?>"><?=GetMessage("MAXYSS_WB_CHILD")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_GIRL")?>"><?=GetMessage("MAXYSS_WB_GIRL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_BOY")?>"><?=GetMessage("MAXYSS_WB_BOY")?></option>
                            </select>
                            <?if($counter>1){?>
                                <a href="javascript:void(0);" class="wb_control-delete-but" onclick="deleteDep(this);" title="<?=GetMessage('WB_MAXYSS_DELETE_PROP')?>"></a>
                            <?}?>
                            <?if(count($dependencies['WB_CAT_PROP'][$t]) == $counter){?>
                                <a href="javascript:void(0);" class="wb_control-add-but" onclick="addNewDep(this);" title="<?=GetMessage('MAXYSS_WB_PROPS_ADD')?>"></a>
                            <?}?>

                        </td>
                    </tr>
                    <?
                    if(!empty($propItem['propsList'])){
                        foreach ($propItem['propsList'] as $row){?>
                        <tr data-parent="WB_CAT_PROPS_<?=$key?>" class="wb_control-child">
                            <td class="adm-detail-content-cell-l">
                                <label>
                                    <input type="text" id="" name="" value="<?=$row['bxVal']['id']?>" data-name="<?=$row['bxVal']['name']?>" readonly="" style="display:none;" class="wb_control-bx-prop" data-name="">
                                    <span class="wb_cat_porp_name"><?=$row['bxVal']['name']?> (<?=$row['bxVal']['id']?>)</span>
                                </label>
                            </td>
                            <td class="adm-detail-content-cell-r">
                                <input type="text" onkeyup="getWbValues(this);" name="" value="<?=$row['wbVal']['wb_name']?>" data-wbaction="<?=$propItem['propWB']?>" data-wb-key="<?=$row['wbVal']['wb_key']?>" required style="" class="wb_control-wb-prop">
                            </td>
                        </tr>
                        <?}
                    }?>
                <?$counter++;}?>
            <?}?>
            <?/*
                iblock-catalog porperties end
            */?>
            <?/*
                iblock-offers porperties start
            */?>
            <?if(is_array($arSkuIblockId)){?>
                <tr class="heading">
                    <td colspan="2"><?=GetMessage('MAXYSS_WB_OFFERS_PROPS_DEPENDENCES')?></td>
                </tr>
            <?}?>
            <?if(!$dependencies['WB_SCU_PROP'][$t]){?>
                <tr id="WB_SCU_PROPS_0" class="wb_control-row" data-num="0" data-entity="SCU_PROPS">
                    <?if($skuPropsOb[$key_cab] && !empty($iblockPropsOb[$key_cab])){?>
                        <td class="adm-detail-content-cell-l">
                            <select name="WB_SCU_PROP_0[<?=$arTab["CABINET"]?>]" id="WB_SCU_PROP_0" data-entity="WB_SCU_PROP" class="wb_cat_props_select">
                                <option value="" selected></option>
                                <?foreach ($skuPropsOb[$key_cab] as $prop){?>
                                    <option value="<?=$prop['ID']?>"><?=$prop['NAME']?></option>
                                <?}?>
                            </select>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <select name="WB_PROPS_0[<?=$arTab["CABINET"]?>]" id="WB_PROPS_0" data-entity="WB_PROPS" class="wb_props_select" >
                                <option value=""></option>
                                <option value="colors" data-value="colors"><?=GetMessage('MAXYSS_WB_COLORS_WB')?></option>
                                <option value="wbsizes" data-value="wbsizes"><?=GetMessage('MAXYSS_WB_SIZE_WB')?></option>
                                <option value="tech-sizes" data-value="tech-sizes"><?=GetMessage('MAXYSS_WB_SIZE_TECH_WB')?></option>
                            </select>
                            <input type="text" onkeyup="getWbTypes(this);" placeholder="<?=GetMessage("MAXYSS_WB_SPRAVKA_PLACEHOLDER")?>" title="<?=GetMessage('MAXYSS_WB_SPRAVKA_TITLE')?>" data-wbaction="wbtypes">
                            <select name="sex" id="" data-wb="sex" style="display: none" title="<?=GetMessage("MAXYSS_WB_SPRAVKA_SELECT_TITLE")?>">
                                <option value="" style="color: #c5c5c5"><?=GetMessage("MAXYSS_WB_POL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_MALE")?>"><?=GetMessage("MAXYSS_WB_MALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_FEMALE")?>"><?=GetMessage("MAXYSS_WB_FEMALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_CHILD")?>"><?=GetMessage("MAXYSS_WB_CHILD")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_GIRL")?>"><?=GetMessage("MAXYSS_WB_GIRL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_BOY")?>"><?=GetMessage("MAXYSS_WB_BOY")?></option>
                            </select>

                            <a href="javascript:void(0);" style="display: none" class="wb_control-add-but" onclick="addNewDep(this);" title="<?=GetMessage('MAXYSS_WB_PROPS_ADD')?>"></a>
                        </td>
                    <?}?>
                </tr>
            <?}else if($dependencies && !empty($dependencies['WB_SCU_PROP'][$t]) && !empty($skuPropsOb)){?>
                <?
                $counter = 1;
                foreach ($dependencies['WB_SCU_PROP'][$t] as $key => $propItem){?>
                    <tr id="WB_SCU_PROPS_<?=$key?>" data-num="<?=$key?>" data-entity="SCU_PROPS" class="wb_control-row">
                        <td class="adm-detail-content-cell-l">
                            <select name="WB_SCU_PROP_<?=$key?>" id="WB_SCU_PROP_<?=$key?>" data-entity="WB_SCU_PROP" class="wb_cat_props_select">
                                <option value=""></option>
                                <?foreach ($skuPropsOb[$key_cab] as $prop){?>
                                    <option value="<?=$prop['ID']?>" <?echo($prop['ID'] == $propItem['propID'])?'selected':''?>><?=$prop['NAME']?></option>
                                <?}?>
                            </select>
                        </td>
                        <td class="adm-detail-content-cell-r">
                            <select name="WB_PROPS_<?=$key?>" id="WB_PROPS_<?=$key?>" data-entity="WB_PROPS" class="wb_props_select" style="">
                                <option value=""></option>
                                <option value="colors" <?echo ($propItem['propWB'] == 'colors')?'selected':''?> data-value="colors"><?=GetMessage('MAXYSS_WB_COLORS_WB')?></option>
                                <option value="wbsizes" <?echo ($propItem['propWB'] == 'wbsizes')?'selected':''?> data-value="wbsizes"><?=GetMessage('MAXYSS_WB_SIZE_WB')?></option>
                                <option value="tech-sizes" <?echo ($propItem['propWB'] == 'tech-sizes')?'selected':''?> data-value="tech-sizes"><?=GetMessage('MAXYSS_WB_SIZE_TECH_WB')?></option>
                            </select>
                            <input type="text" onkeyup="getWbTypes(this);" placeholder="<?=GetMessage("MAXYSS_WB_SPRAVKA_PLACEHOLDER")?>" title="<?=GetMessage('MAXYSS_WB_SPRAVKA_TITLE')?>" data-wbaction="wbtypes" style="<?=($propItem['propWB'] == 'wbsizes')?'':'display:none;'?>">
                            <select name="sex" id="" data-wb="sex" style="display: none" title="<?=GetMessage("MAXYSS_WB_SPRAVKA_SELECT_TITLE")?>">
                                <option value="" style="color: #c5c5c5"><?=GetMessage("MAXYSS_WB_POL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_MALE")?>"><?=GetMessage("MAXYSS_WB_MALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_FEMALE")?>"><?=GetMessage("MAXYSS_WB_FEMALE")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_CHILD")?>"><?=GetMessage("MAXYSS_WB_CHILD")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_GIRL")?>"><?=GetMessage("MAXYSS_WB_GIRL")?></option>
                                <option value="<?=GetMessage("MAXYSS_WB_BOY")?>"><?=GetMessage("MAXYSS_WB_BOY")?></option>
                            </select>
                            <?if($counter>1){?>
                                <a href="javascript:void(0);" class="wb_control-delete-but" onclick="deleteDep(this);" title="<?=GetMessage('WB_MAXYSS_DELETE_PROP')?>"></a>
                            <?}?>
                            <?if(count($dependencies['WB_SCU_PROP'][$t]) == $counter){?>
                                <a href="javascript:void(0);" class="wb_control-add-but" onclick="addNewDep(this);" title="<?=GetMessage('MAXYSS_WB_PROPS_ADD')?>"></a>
                            <?}?>

                        </td>
                    </tr>
                    <?
                    if(!empty($propItem['propsList'])) {
                        foreach ($propItem['propsList'] as $row) {
                            ?>
                            <tr data-parent="WB_SCU_PROPS_<?= $key ?>" class="wb_control-child">
                                <td class="adm-detail-content-cell-l">
                                    <label>
                                        <input type="text" id="" name="" value="<?= $row['bxVal']['id'] ?>"
                                               data-name="<?= $row['bxVal']['name'] ?>" readonly=""
                                               style="display:none;" class="wb_control-bx-prop"
                                               data-name="black">
                                        <span class="wb_cat_porp_name"><?= $row['bxVal']['name'] ?>
                                            (<?= $row['bxVal']['id'] ?>)</span>
                                    </label>
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input type="text" onkeyup="getWbValues(this);" name=""
                                           value="<?= $row['wbVal']['wb_name'] ?>"
                                           data-wbaction="<?= $propItem['propWB'] ?>"
                                           data-wb-key="<?= $row['wbVal']['wb_key'] ?>" required style=""
                                           class="wb_control-wb-prop">
                                </td>
                            </tr>
                        <?
                        }
                    }?>
                    <?$counter++;}?>
            <?}?>
            <?/*
                iblock-offers porperties end
            */?>
<!--            </tbody>-->
<!--        </table>-->
        <div id="ans"></div>
<!--    </div>-->
<!--    <div class="adm-detail-content-btns-wrap" id="editTab_buttons_div" style="left: 0px;">-->
<!--        <div class="adm-detail-content-btns">-->
<!--            <input type="submit" name="save" value="--><?//=GetMessage('MAXYSS_WB_MODULE_SAVE')?><!--">-->
<!--        </div>-->
<!--    </div>-->
    <?
        $t++;
    }?>
    <?$tabControl->Buttons(array(
        "back_url"=>MAXYSS_WB_NAME."_wb_maxyss_general.php?lang=".LANGUAGE_ID,

    ));?>

    <?$tabControl->End();?>
    <script>
        var propsJsOb = <?=CUtil::PHPToJSObject($iblockPropsOb);?>;
        // console.log(propsJsOb);
        var skuPropsJsOb = <?=CUtil::PHPToJSObject($skuPropsOb);?>;
        // console.log(skuPropsJsOb);

    </script>
</form>
    <script type="text/javascript">
        $(document).on("change", "input[name='stock_realy_time']", function () {
            var check = $(this).prop('checked');
            $("input[name='stock_realy_time']").each(function (index, value) {
                $(this).prop('checked', check);
            })
        });
    </script>


    <div id="WB_templates" style="display: none">
        <table>
            <tr data-parent="" class="wb_control-child">
                <td class="adm-detail-content-cell-l">
                    <label>
                        <input type="text" id="" name="" value="" readonly style="display:none;" class="wb_control-bx-prop">
                        <span class="wb_cat_porp_name"></span>
                    </label>
                </td>
                <td class="adm-detail-content-cell-r">
                    <input type="text" onkeyup="getWbValues(this);" name="" value="" data-wbaction="" required style="display:none;" class="wb_control-wb-prop">
                </td>
            </tr>
        </table>
        <ul id="wb_select" class="wb_select-item">
            <li class="option" data-value=""></li>
        </ul>
        <table>
            <tr id="WB_CAT_PROPS_" class="wb_control-row" data-num="" data-entity="">
                <td class="adm-detail-content-cell-l">
                    <select name="WB_CAT_PROP_" id="WB_CAT_PROP_" data-entity="WB_CAT_PROP" class="wb_cat_props_select">
                    </select>
                </td>
                <td class="adm-detail-content-cell-r">
                    <select name="WB_PROPS_0" id="WB_PROPS_0" data-entity="WB_PROPS" class="wb_props_select" style="">
                        <option value="" selected=""></option>
                        <option value="colors" data-value="colors"><?=GetMessage('MAXYSS_WB_COLORS_WB')?></option>
                        <option value="wbsizes" data-value="wbsizes"><?=GetMessage('MAXYSS_WB_SIZE_WB')?></option>
                        <option value="tech-sizes" data-value="tech-sizes"><?=GetMessage('MAXYSS_WB_SIZE_TECH_WB')?></option>
                    </select>
                    <input type="text" onkeyup="getWbTypes(this);" placeholder="<?=GetMessage("MAXYSS_WB_SPRAVKA_PLACEHOLDER")?>" title="<?=GetMessage('MAXYSS_WB_SPRAVKA_TITLE')?>" data-wbaction="wbtypes">
                    <select name="sex" id="" data-wb="sex" style="display: none" title="<?=GetMessage("MAXYSS_WB_SPRAVKA_SELECT_TITLE")?>">
                        <option value="" style="color: #c5c5c5"><?=GetMessage("MAXYSS_WB_POL")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_MALE")?>"><?=GetMessage("MAXYSS_WB_MALE")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_FEMALE")?>"><?=GetMessage("MAXYSS_WB_FEMALE")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_CHILD")?>"><?=GetMessage("MAXYSS_WB_CHILD")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_GIRL")?>"><?=GetMessage("MAXYSS_WB_GIRL")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_BOY")?>"><?=GetMessage("MAXYSS_WB_BOY")?></option>
                    </select>
                    <a href="javascript:void(0);" style="display:none;" class="wb_control-add-but" onclick="addNewDep(this);" title="<?=GetMessage("MAXYSS_WB_PROPS_ADD")?>"></a>
                    <a href="javascript:void(0);" class="wb_control-delete-but" onclick="deleteDep(this);" title="<?=GetMessage('WB_MAXYSS_DELETE_PROP')?>"></a>
                </td>
            </tr>
            <tr id="WB_SCU_PROPS_" class="wb_control-row" data-num="" data-entity="">
                <td class="adm-detail-content-cell-l">
                    <select name="WB_SCU_PROP_" id="WB_SCU_PROP_" data-entity="WB_CAT_PROP" class="wb_cat_props_select">
                    </select>
                </td>
                <td class="adm-detail-content-cell-r">
                    <select name="WB_PROPS_0" id="WB_PROPS_0" data-entity="WB_PROPS" class="wb_props_select" style="">
                        <option value="" selected=""></option>
                        <option value="colors" data-value="colors"><?=GetMessage('MAXYSS_WB_COLORS_WB')?></option>
                        <option value="wbsizes" data-value="wbsizes"><?=GetMessage('MAXYSS_WB_SIZE_WB')?></option>
                        <option value="tech-sizes" data-value="tech-sizes"><?=GetMessage('MAXYSS_WB_SIZE_TECH_WB')?></option>
                    </select>
                    <input type="text" onkeyup="getWbTypes(this);" placeholder="<?=GetMessage("MAXYSS_WB_SPRAVKA_PLACEHOLDER")?>" title="<?=GetMessage('MAXYSS_WB_SPRAVKA_TITLE')?>" data-wbaction="wbtypes">
                    <select name="sex" id="" data-wb="sex" style="display: none" title="<?=GetMessage("MAXYSS_WB_SPRAVKA_SELECT_TITLE")?>">
                        <option value="" style="color: #c5c5c5"><?=GetMessage("MAXYSS_WB_POL")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_MALE")?>"><?=GetMessage("MAXYSS_WB_MALE")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_FEMALE")?>"><?=GetMessage("MAXYSS_WB_FEMALE")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_CHILD")?>"><?=GetMessage("MAXYSS_WB_CHILD")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_GIRL")?>"><?=GetMessage("MAXYSS_WB_GIRL")?></option>
                        <option value="<?=GetMessage("MAXYSS_WB_BOY")?>"><?=GetMessage("MAXYSS_WB_BOY")?></option>
                    </select>
                    <a href="javascript:void(0);" style="display:none;" class="wb_control-add-but" onclick="addNewDep(this);" title="<?=GetMessage("MAXYSS_WB_PROPS_ADD")?>"></a>
                    <a href="javascript:void(0);" class="wb_control-delete-but" onclick="deleteDep(this);" title="<?=GetMessage('WB_MAXYSS_DELETE_PROP')?>"></a>
                </td>
            </tr>
        </table>
<!--    </div>-->

<script type="text/javascript">
    function findParents(el, cls) {
        if(!el.classList.contains(cls)){
            while ((el = el.parentElement)){
                if(el.classList.contains(cls)){
                    return el;
                }
            }
        } else{
            return el;
        }
    };
    function property_update(iblock_id, div, cabinet){
        var tab = $('#' + div);

        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/settings.php?iblock_id=' + iblock_id,
            data:{action: 'get_prop_foto'},
            success: function(data) {tab.find($('[name*="more_picture"]')).empty().html(data);},
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_WB_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/settings.php?iblock_id=' + iblock_id,
            data:{action: 'get_prop_article'},
            success: function(data) {

                tab.find($('[name*="article"]')).empty().html(data);
                tab.find($('[name*="shkod"]')).empty().html(data);
                tab.find($('[name*="land"]')).empty().html(data);

                },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_WB_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/settings.php?iblock_id=' + iblock_id,
            data:{action: 'get_filter_property'},
            success: function(data) {tab.find($('[name*="filter_property"]')).empty().html(data);},
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_WB_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/settings.php?iblock_id=' + iblock_id,
            data:{action: 'get_prop_brand'},
            success: function(data) {tab.find($('[name*="brands"]')).empty().html(data);},
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_WB_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/settings.php?iblock_id=' + iblock_id,
            // dataType: 'json',
            data:{action: 'get_props_obj'},
            success: function(data) {
                console.log(data);
                // on_change_iblock_item(JSON.parse(data));
            },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_WB_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
        iblock_id_g = iblock_id;
    }
    function iblock_update(iblock_type, div) {
        var tab = $('#' + div);
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/settings.php?iblock_type=' + iblock_type,
            data:{action: 'get_iblock_id'},
            success: function(data) {
                tab.find($('[name*="iblock_id"]')).empty().html(data);
                tab.find($('[name*="more_picture"]')).empty();
                tab.find($('[name*="article"]')).empty();
                tab.find($('[name*="filter_property"]')).empty();
                tab.find($('[name*="filter_property_enums"]')).remove();
                on_change_iblock_type(tab);
                // $('#ans').html(data);
                },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_WB_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
          });
    }
    function filter_property_update(th, div, cabinet){
        var parent_td = th.parent();
        var iblock_id_g = th.parent().parent().parent().find($('[name*="iblock_id"]')).val();
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/settings.php?iblock_id=' + iblock_id_g,
            data:{
                action: 'get_filter_property_enum',
                filter_property: th.val(),
                cabinet: cabinet
            },
            success: function(data) {
                // parent_td.find($('[name*="filter_prop_id"]')).empty().html(data);
                parent_td.find($('[name*="filter_property_enums"]')).remove();
                parent_td.find($('[name*="filter_property"]')).after(data);
            },
            error:  function(xhr, str){alert("<?=GetMessage('MAXYSS_OZON_MODULE_ERROR_AJAX')?>: " + xhr.responseCode);}
        });
    };
    $(document).on('click', '#get_code', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_code',
                phone: $('#phone').val()
            },
            success: function(data) {
                obj = $.parseJSON(data);
                // console.log(obj);

                if(obj.error){
                    alert(obj.error)
                }else {
                    $('#token').val(obj.token);
                    $('#tr_get_wb_token').css({"display": "block"});
                }
            },
            error:  function(xhr, str){
                alert('An error occured: ' + xhr.responseCode);
            }
        });
    });
    $(document).on('click', '#get_wb_token', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_wb_token',
                token: $('#token').val(),
                code: $('#code').val()
            },
            success: function(data) {
                obj = $.parseJSON(data);
                // console.log(obj);
                if(obj.error){
                    alert(obj.error)
                }else {
                    $('#wb_token_result').val(obj.wbtoken);
                    // BX.getCookie('BITRIX_SM_WARNING');
                }
            },
            error:  function(xhr, str){
                alert('An error occured: ' + xhr.responseCode);
            }
        });
    });
    $(document).on('click', '.log_view', function (e) {
        e.preventDefault();
        var text_file = $(this).attr('href');
        $.ajax({
                 type: 'GET',
                 url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                 data: {
                    action: 'get_log',
                    file: text_file
                    },
                 success: function(data) {
                    $('#log_file').html(data);
                 },
                 error:  function(xhr, str){
                    alert('An error occured: ' + xhr.responseCode);
                    }
              });
        // console.log(text_file);
    });
    $(document).on('change', '.price_to_prop', function () {
            // console.log($(this).prop('checked'));
            var price_block = $(this);
            if(price_block.prop('checked')){
                price_block.parent().parent().find('td:eq(2)').css({'display' : 'table-cell'});
                price_block.parent().parent().find('td:eq(3)').css({'display' : 'none'});
                price_block.parent().parent().find('td:eq(4)').css({'display' : 'none'});
                price_block.parent().parent().find('td:eq(5)').css({'display' : 'none'});
            }else{
                price_block.parent().parent().find('td:eq(2)').css({'display' : 'none'});
                price_block.parent().parent().find('td:eq(3)').css({'display' : 'table-cell'});
                price_block.parent().parent().find('td:eq(4)').css({'display' : 'table-cell'});
                price_block.parent().parent().find('td:eq(5)').css({'display' : 'table-cell'});
            }
    });
    function getWbValues(item) {

        deBounceInputs(item);
    };
    function getWbTypes(item) {

        var pattern = item.value;
        if (pattern.length > 2) {
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                data: {
                    pattern: pattern,
                    action: 'get_object_filter',
                },
                success: function (data) {
                    // console.log(obj);
                    var IS_JSON = true;
                    try {
                        var obj = $.parseJSON(data);
                        errors = obj.errorText;
                    }
                    catch (err) {
                        IS_JSON = false;
                        errors = 'not json oject';
                    }
                    if (IS_JSON && !obj.error) {
                        createWbTypesSelect(item, obj.data);
                    }else{
                        console.log(obj.errorText);
                    }
                },
                error: function (xhr, str) {
                    alert('Error: ' + xhr.responseCode);
                }
            });
        }
    };

    var delay = 150, tid;
    function deBounceInputs(item) {
        clearTimeout(tid);
        tid = setTimeout(function() {
            clearTimeout(tid);
            tid = setTimeout(function() {
                var minLength, dictionari = '', option='';
                switch (item.getAttribute('data-wbaction')) {
                    case 'colors':
                        minLength = 3;
                        dictionari = '/content/v1/directory/colors';
                        option = '<?=GetMessage('MAXYSS_WB_TSVET')?>';
                        break;
                    case 'wbsizes':
                        minLength = 1;
                        break;
                    case 'tech-sizes':
                        minLength = 1;
                        break;
                };
                if(item.value.length>=minLength && dictionari.length > 0){
                    var row = document.querySelector('#'+findParents(item, 'wb_control-child').getAttribute('data-parent')+'');

                    var kind = ''
                    if(row.querySelector('[data-wb="sex"]'))
                        kind = row.querySelector('[data-wb="sex"]').value;
                    var filter = row.querySelector('[data-wbaction="wbtypes"]');


                    var type = item.getAttribute('data-wbaction');
                    // if(type=='tech-sizes')type = 'consist';
                    var xhr = new XMLHttpRequest();
                    var baseUrl = '/bitrix/tools/maxyss.wb/ajax.php';

                    // send_data = {'pattern': v, 'option': type, 'action': 'get_directory', 'dictionari': dictionari};

                    var url = baseUrl+'/?';
                    url+='action=get_directory';
                    url+='&pattern='+encodeURIComponent(item.value);
                    url+='&dictionari='+encodeURIComponent(dictionari);
                    url+='&option='+encodeURIComponent(dictionari);
                    // if(filter.value /*&& kind*/){
                    //     url+='subject='+encodeURIComponent(filter.value);
                    //     url+=(kind)?'&kind='+encodeURIComponent(kind):'';
                    //     url+='&pattern='+encodeURIComponent(item.value);
                    // }else{
                    //     url+='pattern='+encodeURIComponent(item.value);
                    // }
                    // url+='&lang=ru&top=100';
                    // console.log(url);
                    xhr.responseType='json';
                    xhr.open('GET', url, true);
                    xhr.send();
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState != 4) return;

                        if (xhr.status != 200) {
                            alert('<?=GetMessage("WB_MAXYSS_NOT_SERVER_PROP")?>');
                        } else {
                            // console.log(xhr.response.data);
                            setSimpleData(item, xhr.response.data);
                            createWbValuesSelect(item, xhr.response.data);
                        }

                    }
                }else{
                    if(item.nextElementSibling){
                        item.nextElementSibling.remove();
                    }
                    item.setAttribute('data-wb-key', item.value);
                }
            }, delay);
        }, delay);
    };
    function onWbValuesSelect(e){
      var select = findParents(e.target, 'wb_select-item');
      if(select){
        e.preventDefault();
        // console.log(e.target);
        select.previousElementSibling.value = e.target.getAttribute('data-value');
        select.previousElementSibling.setAttribute('data-wb-key', e.target.getAttribute('data-key'));
        select.remove();
          window.removeEventListener('click', onWbValuesSelect);
      }else{
        document.querySelector('.wb_select-item').remove();
        window.removeEventListener('click', onWbValuesSelect);
      };
    };

    function maxyss_wb_in_array(str, arr) {
        for (i = 0; i < arr.length; i++) {
            if (arr[i] == str) {
                return true;
            };
        };

        return false;
    };

    function onWbTypesSelect(e) {
        var mess_arr = '<?=GetMessageJS("MAXYSS_WB_OBJ_ARRAY")?>';
        var arr = mess_arr.split(',');
        var select = findParents(e.target, 'wb_select-item');
        // console.log(select);
        if(select){
            var input = document.querySelector('#'+select.getAttribute('data-id')+'').querySelector('[data-wbaction="wbtypes"]');
            e.preventDefault();
            // console.log(input);
            input.value = e.target.getAttribute('data-value');
            if (maxyss_wb_in_array(e.target.getAttribute('data-parent'), arr)){
                if(input.parentNode.querySelector('[data-wb="sex"]')){
                    input.parentNode.querySelector('[data-wb="sex"]').style.display ='';
                }
            }else{
                if(input.parentNode.querySelector('[data-wb="sex"]')){
                    input.parentNode.querySelector('[data-wb="sex"]').style.display ='none';
                }
            }
            select.remove();
            window.removeEventListener('click', onWbTypesSelect);
        }else{
            // console.log('onWbTypesSelect');
            document.querySelector('.wb_select-item.active').remove();
            window.removeEventListener('click', onWbTypesSelect);
        };
    }
    function createWbValuesSelect(el,data){
        var type;
        var dataType = el.getAttribute('data-wbaction');
        switch (dataType) {
            case 'colors':
                type = 'string';
                break;
            case 'wbsizes':
                type = 'num';
                break;
            case 'tech-sizes':
                type = 'num';
                break;
        };
        if(data){
            var select;
            var alreadyExisst = (el.parentNode.querySelector('.wb_select-item'));
            if(!alreadyExisst){
                select = document.querySelector('#wb_select').cloneNode(true);
            }else{
                select = el.parentNode.querySelector('.wb_select-item');
            }
            select.innerHTML = '';
            for (var i = 0; i<data.length; i++){
                var select_item = document.createElement('li');
                select_item.classList.add('option');
                var desc = '';
                // console.log(data);
                if(data[i]['name']){
                    desc = data[i]['name'];
                }else{
                    if(data[i]['detail'].length){
                        for(var n=0;n<data[i]['detail'].length;n++){
                            desc += data[i]['detail'][n]['name'];
                            if(data[i]['detail'][n]['sizeMax']) desc += ' ' + data[i]['detail'][n]['sizeMax'];
                            if(data[i]['detail'][n]['sizeMin']) desc += ' - ' + data[i]['detail'][n]['sizeMin'];
                            if(n<data[i]['detail'].length){
                                desc += '; ';
                            }
                        };
                    }else{
                        desc += 'null';
                    }
                };
                select_item.setAttribute('data-value', desc);
                // select_item.setAttribute('data-value', '('+data[i]['key']+') ' +  desc);
                select_item.setAttribute('data-key', data[i]['name']);
                // select_item.innerHTML = desc + ' ('+data[i]['key']+')';
                select_item.innerHTML =/*'('+data[i]['key']+') ' +  */desc;
                select.appendChild(select_item);
            };
            select.style.minWidth = el.getBoundingClientRect().width+'px';
            if(!alreadyExisst){
                el.parentNode.appendChild(select);
            };
            window.addEventListener('click', onWbValuesSelect);
        };
    };
    function getCoords(elem) {
        var box = elem.getBoundingClientRect();

        return {
            top: box.top + pageYOffset,
            left: box.left + pageXOffset,
            height: box.height,
        };
    }
    function createWbTypesSelect(el,data) {
        var type = 'string';
        var cords = getCoords(el);
        var id = findParents(el, 'wb_control-row').id;
        // console.log(id);
        if(data){
            // console.log(el.getBoundingClientRect());
            var select;
            var alreadyExisst = document.querySelector('[data-id="'+id+'"]');
            if(!alreadyExisst){
                select = document.querySelector('#wb_select').cloneNode(true);
            }else{
                select = alreadyExisst;
            }
            select.innerHTML = '';
            for (var key in data){
                var select_item = document.createElement('li');
                select_item.classList.add('option');
                select_item.setAttribute('data-value', data[key]['objectName']);
                select_item.setAttribute('data-key', data[key]['objectName']);
                select_item.setAttribute('data-parent', data[key]['parentName']);
                select_item.innerHTML =data[key]['objectName'];
                select.appendChild(select_item);
            };
            select.style.minWidth = el.getBoundingClientRect().width+'px';
            select.style.top = cords.top+cords.height+'px';
            select.style.left = cords.left+'px';
            select.setAttribute('data-id', id);
            select.classList.add('active');
            if(!alreadyExisst){
                document.querySelector('body').appendChild(select);
            };
            window.addEventListener('click', onWbTypesSelect);
        };
    };
    function setSimpleData(el,arr) {
        if(el.getAttribute('data-wbaction') == 'colors' && arr !== null){
            for (var i = 0; i<arr.length; i++){
                var wbName = arr[i].name;
                if(wbName.includes(el.value.toLowerCase())){
                  var identicalLengthty = (wbName.length == el.value.length)? true:false;
                  if(identicalLengthty){
                      el.setAttribute('data-wb-key', arr[i].name);
                  }
                };
            }
        }
    };
    function createRows(el, arr){
        var el_parent = el.parentElement;
        var WB_template = document.querySelector('#WB_templates');
        var WB_template_row = WB_template.querySelector('tr');
        var alreadyExist = (el_parent.querySelectorAll('[data-parent="'+el.id+'"]').length>0);
        if(alreadyExist){
            var existRows = el_parent.querySelectorAll('[data-parent="'+el.id+'"]');
            for (var i = 0; i<existRows.length; i++){
              existRows[i].remove();
            };
        }
        for(var i = 0; i<arr.length; i++){
            var new_row = WB_template_row.cloneNode(true);
            var identity = (arr[i]['UF_XML_ID'])?arr[i]['UF_XML_ID']:arr[i]['ID'];
            var name = (arr[i]['UF_NAME'])?arr[i]['UF_NAME']:arr[i]['VALUE'];
            new_row.querySelector('.wb_cat_porp_name').innerHTML =  name +' (id: '+identity+')';
            new_row.querySelector('.wb_control-bx-prop').value = identity;
            new_row.querySelector('.wb_control-bx-prop').setAttribute('data-name', name);
            new_row.setAttribute('data-parent', el.id);
            new_row.id += i;
            if(el.nextElementSibling){
                el_parent.insertBefore(new_row, el.nextElementSibling);
            }else{
                el_parent.appendChild(new_row);
            }
        }
    };
    function removeRows(el) {
        // console.log(el);
        var el_parent = el.parentElement;
        var elemsToDelet = (el_parent.querySelectorAll('[data-parent="'+el.id+'"]'))? el_parent.querySelectorAll('[data-parent="'+el.id+'"]') : false;
        if(elemsToDelet){
            for (var i = 0;i<elemsToDelet.length; i++){
              elemsToDelet[i].remove();
            };
        }
    };
    function showRowsInputs(el, val){
        // console.log(val);
        // console.log(el);
        var el_parent = el.parentElement;
        var row_inputs = el_parent.querySelectorAll('[data-parent="'+el.id+'"] [data-wbaction]');
        // console.log(row_inputs);
        for(var i = 0; i<row_inputs.length; i++){
            row_inputs[i].value = '';
            row_inputs[i].style.display = '';
            row_inputs[i].setAttribute('data-wbaction', val);
        };
        if(val == 'colors' || val == 'tech-sizes'){
            el.querySelector('[data-wbaction="wbtypes"]').style.display = 'none';
            if(el.querySelector('[data-wb="sex"]'))
                el.querySelector('[data-wb="sex"]').style.display = 'none';
        }else{
            el.querySelector('[data-wbaction="wbtypes"]').style.display = '';
            if(el.querySelector('[data-wb="sex"]'))
                el.querySelector('[data-wb="sex"]').style.display = 'none';
        }
    };
    function hideRowsInputs(el, val) {
        var el_parent = el.parentElement;
        var row_inputs = el_parent.querySelectorAll('[data-parent="'+el.id+'"]');
        if(row_inputs && row_inputs.length>0){
            for(var i = 0; i<row_inputs.length; i++) {
                row_inputs[i].style.display = 'none';
                row_inputs[i].value = '';
            }
        }else{
            return;
        }
    };
    function showRowAddBut(el) {
        if(el.querySelector('.wb_control-add-but'))
            el.querySelector('.wb_control-add-but').style.display = 'inline-block';
    };
    function hideRowAddBut() {
        el.querySelector('.wb_control-add-but').style.display = 'none';
    };
    function on_wb_control_change(el){
        var wb_control = el.querySelector('.wb_props_select');
        var cat_Control = el.querySelector('.wb_cat_props_select');
        if(wb_control.value && cat_Control.value){
            // console.log(wb_control.value);
            showRowsInputs(el, wb_control.value);
            showRowAddBut(el);
        }else{
            hideRowsInputs(el);
        };
    };
    function on_cat_control_change(el){
        // console.log(el);
        let tab = +findParents(el, 'adm-detail-content').id.replace(/[^0-9]/g,"");
        // console.log(tab);
        var entity = el.getAttribute('data-entity');
        var wb_control = el.querySelector('.wb_props_select');
        var cat_Control = el.querySelector('.wb_cat_props_select');
        if(cat_Control.value){
            // console.log(cat_Control);
            // console.log(cat_Control.value);
            // console.log(propsJsOb);
            // console.log(tab-1);
            // console.log(propsJsOb[tab-1][cat_Control.value]);
            if(wb_control.value){
                wb_control.value='';
            }
            if(entity == 'SCU_PROPS'){
                createRows(el, skuPropsJsOb[tab-1][cat_Control.value]['VALUES']);
                wb_control.style.display='';
            }else{
                createRows(el, propsJsOb[tab-1][cat_Control.value]['VALUES']);
                wb_control.style.display='';
            }
        }else{
            removeRows(el);
            wb_control.style.display='none';
            if(el.querySelector('.wb_control-add-but'))
                el.querySelector('.wb_control-add-but').style.display = 'none';
        };
    };
    function createOptions(arr, type, cabIndex){
        // console.log(cabIndex);
        // console.log(arr);
        if(arr[cabIndex]){
            var options = [];
            var optionfirst = new Option('', '', true, true);
            options.push(optionfirst);
            for (var k in arr[cabIndex]){
                var option = new Option(arr[cabIndex][k]['NAME'], arr[cabIndex][k]['ID']);
                options.push(option);
            };
            return options;
        }else{
            if(type){
              if(type == 'CAT_PROPS'){
                  alert('<?=GetMessage("WB_MAXYSS_NOT_TYPE_PROP")?>');
              }else{
                  alert('<?=GetMessage("WB_MAXYSS_NOT_TYPE_PROP")?>');
              }
            };

            return false;
        }
    };
    function on_change_iblock_item(data){
        // console.log(data);
        propsJsOb = (data.cat.props && typeof data.cat.props == 'object' && Object.keys(data.cat.props).length>0)? data.cat.props:false;
        skuPropsJsOb = (data.scu.props && typeof data.scu.props == 'object' && Object.keys(data.scu.props).length>0)? data.scu.props:false;
        var cat_props_controls = document.querySelectorAll('[data-entity="CAT_PROPS"]');
        var scu_porps_controls = document.querySelectorAll('[data-entity="SCU_PROPS"]');
        changeEntityesAfterIblockChange(cat_props_controls, propsJsOb);
        changeEntityesAfterIblockChange(scu_porps_controls, skuPropsJsOb);
    };
    function changeEntityesAfterIblockChange(arr, ob){
        if(arr.length>1){
            for(var i = 1;i<arr.length; i++){
                var items = document.querySelectorAll('[data-parent="'+arr[i].id+'"]');
                if(arr[i].querySelector('.wb_control-add-but')){
                    arr[i].querySelector('.wb_control-add-but').style.display = 'none';
                    arr[0].querySelector('.adm-detail-content-cell-r').appendChild(arr[i].querySelector('.wb_control-add-but'));
                }
                if(items && items.length>0){
                    for(var n = 0;n<items.length;n++){
                        items[n].remove();
                    }
                }
                arr[i].remove();
            };
        }
        var firstRow = arr[0];
        var firstRowPropSelect = firstRow.querySelector('.wb_cat_props_select');
        var firstRowWbPropSelect = firstRow.querySelector('.wb_props_select');
        var firstRowItems = document.querySelectorAll('[data-parent="'+firstRow.id+'"]');
        if(firstRowItems && firstRowItems.length>0){
          for(var n = 0; n<firstRowItems.length;n++){
              firstRowItems[n].remove();
          };
        };
        firstRowPropSelect.options.length = 0;
        firstRowWbPropSelect.value = '';
        var options = createOptions(ob, firstRow.getAttribute('data-entity'));
        // console.log(options);
        if(options){
          for(var l = 0; l<options.length; l++){
              firstRowPropSelect.appendChild(options[l]);
          };
        };

    };
    // data-entity="WB_CAT_PROP"
    //data-entity="WB_PROPS"
    document.addEventListener('change', function (e) {
       if(e.target.getAttribute('data-entity')){
           var row = findParents(e.target, 'wb_control-row');
           var wb_control = row.querySelector('.wb_props_select');
           var cat_Control = row.querySelector('.wb_cat_props_select');
           if(e.target == cat_Control){
               on_cat_control_change(row);
           }
           if(e.target == wb_control){
               on_wb_control_change(row);
           }

       }
    });


    function addNewDep(item) {
        var cab = findParents(item, 'adm-detail-content');
        var cabs = document.querySelectorAll('.adm-detail-content');
        var cabindex;
        for(let i = 0;i<cabs.length;i++){
            if(cabs[i] == cab){
                cabindex=i;
            }
        }

        // debugger;
        var row = findParents(item, 'wb_control-row');
        var template = (row.getAttribute('data-entity') == 'SCU_PROPS')?document.querySelector('#WB_SCU_PROPS_'):document.querySelector('#WB_CAT_PROPS_');
        var newControlsRow = template.cloneNode(true);
        var newControlsSelect = newControlsRow.querySelector('.wb_cat_props_select');
        // console.log(row.getAttribute('data-entity'));
        // console.log(row.getAttribute('data-entity'));
        // console.log(row.getAttribute('data-entity'));
        var lastWbChilds = (row.getAttribute('data-entity') == 'SCU_PROPS')? cab.querySelectorAll('[data-parent="WB_SCU_PROPS_'+row.getAttribute('data-num')+'"]'):cab.querySelectorAll('[data-parent="WB_CAT_PROPS_'+row.getAttribute('data-num')+'"]');
        // console.log(lastWbChilds);
        if(lastWbChilds.length>0){
            var childLast = lastWbChilds[lastWbChilds.length-1];
            var options = (row.getAttribute('data-entity') == 'SCU_PROPS')?createOptions(skuPropsJsOb, false, cabindex):createOptions(propsJsOb, false, cabindex);
            for (var m = 0; m<options.length; m++){
                newControlsSelect.append(options[m]);
            };
            newControlsRow.setAttribute('data-entity', row.getAttribute('data-entity'));
            newControlsRow.setAttribute('data-num', +row.getAttribute('data-num')+1);
            newControlsRow.id = newControlsRow.id+(+row.getAttribute('data-num')+1);
            item.style.display = 'none';
            newControlsRow.querySelector('.adm-detail-content-cell-r').appendChild(item);
            if(childLast) {
                if(childLast.nextElementSibling){
                    childLast.parentElement.insertBefore(newControlsRow,childLast.nextElementSibling);
                }else{
                    childLast.parentElement.appendChild(newControlsRow);
                };
            }
        }
    };
    function deleteDep(item) {

        var cab = findParents(item, 'adm-detail-content');
        var cabs = document.querySelectorAll('.adm-detail-content');
        var cabindex;
        for(let i = 0;i<cabs.length;i++){
            if(cabs[i] == cab){
                cabindex=i;
            }
        }

        var row = findParents(item, 'wb_control-row');
        var enti = row.getAttribute('data-entity');

        var rowNum = +row.getAttribute('data-num');
        var rowPrev = rowNum-1;
        var wbChilds = cab.querySelectorAll('[data-parent="'+row.id+'"]');
        if(wbChilds.length>0){
          for (var i = 0; i<wbChilds.length;i++){
              wbChilds[i].remove();
          }
        };
        if(item.nextElementSibling){
            item.nextElementSibling.style.display = 'inline-block';
            document.querySelector('#WB_'+enti+'_'+rowPrev+'').querySelector('.adm-detail-content-cell-r').appendChild(item.nextElementSibling);
        }
        row.remove();
        var entiRows = cab.querySelectorAll('[data-entity="'+enti+'"]');
        if(entiRows.length>1){
            updateRows(entiRows, rowNum, enti, cab, cabindex);
        };
    };
    function updateRows(arr, num, ent, cab, index) {
        // console.log(arr[index]);
        for(var i = 0; i<arr[index].length; i++){
            var curRow = arr[index][i];
            var curRowId = curRow.id;
            if(i != curRow.getAttribute('data-num')){
                curRow.setAttribute('data-num', i);
                curRow.id = curRow.id.replace(/[0-9]/g, '') + i;
                var propSelect = curRow.querySelector('.wb_cat_props_select');
                var wbPropSelect = curRow.querySelector('.wb_props_select');
                propSelect.setAttribute('name', propSelect.getAttribute('name').replace(/[0-9]/g, '') + i);
                propSelect.id = propSelect.id.replace(/[0-9]/g, '') + i;
                wbPropSelect.setAttribute('name', wbPropSelect.getAttribute('name').replace(/[0-9]/g, '') + i);
                wbPropSelect.id = wbPropSelect.id.replace(/[0-9]/g, '') + i;
                var childRows = cab.querySelectorAll('[data-parent="'+curRowId+'"]');
                if(childRows){
                  for (var n = 0; n<childRows.length; n++){
                    childRows[n].setAttribute('data-parent', curRow.id);
                  };
                };
                // console.log(childRows);
            }else{
                continue;
            }
        };
    };
    function checkWbPropsInputs(e) {
        var wbPropsInputs = document.querySelectorAll('.wb_control-wb-prop');
        var emptyData = false;
        var emptyInputs = [];
        for (var i = 0; i<wbPropsInputs.length;i++){
            if(wbPropsInputs[i].getAttribute('data-wb-key') == ''){
                wbPropsInputs[i].value = '';
                emptyInputs.push(wbPropsInputs[i]);
                emptyData = true;
            };
        };
        if(emptyData){
            for(var n = 0; n<emptyInputs.length; n++){
              emptyInputs[n].style.boxShadow = '0 0 1px 1px red';
            };
            return emptyData;
        };
    };
    var wb_form = document.querySelector('.wb_module_form');
    wb_form.addEventListener('submit', function (e) {
        var error = checkWbPropsInputs();
        if(error){
          e.preventDefault();
          alert('<?=GetMessage("WB_MAXYSS_NOT_VALUE_PROP")?>');
          return;
        };
        var wbPropsObj = {};
        wbPropsObj.WB_CAT_PROP = [];
        wbPropsObj.WB_SCU_PROP = [];


        let tabs = document.querySelectorAll('.adm-detail-content');
        for (let t = 0;t<tabs.length;t++){

            var catPropBlocks = tabs[t].querySelectorAll('[data-entity="CAT_PROPS"]');
            var scuPropBlocks = tabs[t].querySelectorAll('[data-entity="SCU_PROPS"]');
            wbPropsObj.WB_CAT_PROP[t] = [];
            if(catPropBlocks && catPropBlocks.length>0){
                for (var i = 0; i<catPropBlocks.length; i++){
                    wbPropsObj.WB_CAT_PROP[t][i] = [];
                    wbPropsObj.WB_CAT_PROP[t][i] = {};
                    wbPropsObj.WB_CAT_PROP[t][i].propID = catPropBlocks[i].querySelector('.wb_cat_props_select').value;
                    wbPropsObj.WB_CAT_PROP[t][i].propWB = catPropBlocks[i].querySelector('.wb_props_select').value;
                    wbPropsObj.WB_CAT_PROP[t][i].propsList = {};
                    var childRows = tabs[t].querySelectorAll('[data-parent="'+catPropBlocks[i].id+'"]');
                    for(var n=0;n<childRows.length;n++){
                        var child_row = childRows[n];
                        wbPropsObj.WB_CAT_PROP[t][i].propsList[n] = {};
                        wbPropsObj.WB_CAT_PROP[t][i].propsList[n].bxVal ={};
                        wbPropsObj.WB_CAT_PROP[t][i].propsList[n].bxVal['id'] = child_row.querySelector('.wb_control-bx-prop').value;
                        wbPropsObj.WB_CAT_PROP[t][i].propsList[n].bxVal['name'] = child_row.querySelector('.wb_control-bx-prop').getAttribute('data-name');
                        wbPropsObj.WB_CAT_PROP[t][i].propsList[n].wbVal = {};
                        wbPropsObj.WB_CAT_PROP[t][i].propsList[n].wbVal['wb_key'] = child_row.querySelector('.wb_control-wb-prop').getAttribute('data-wb-key');
                        wbPropsObj.WB_CAT_PROP[t][i].propsList[n].wbVal['wb_name'] = child_row.querySelector('.wb_control-wb-prop').value;
                    }
                };
            }
            if(scuPropBlocks && scuPropBlocks.length>0) {
                wbPropsObj.WB_SCU_PROP[t] = [];
                for (var i = 0; i<scuPropBlocks.length; i++){
                    if(scuPropBlocks[i].querySelector('.wb_cat_props_select') && scuPropBlocks[i].querySelector('.wb_props_select')){
                        wbPropsObj.WB_SCU_PROP[t][i] = {};
                        wbPropsObj.WB_SCU_PROP[t][i].propID = scuPropBlocks[i].querySelector('.wb_cat_props_select').value;
                        wbPropsObj.WB_SCU_PROP[t][i].propWB = scuPropBlocks[i].querySelector('.wb_props_select').value;
                        wbPropsObj.WB_SCU_PROP[t][i].propsList = {};
                        var childRows = tabs[t].querySelectorAll('[data-parent="'+scuPropBlocks[i].id+'"]');

                        for(var n=0;n<childRows.length;n++){
                            var child_row = childRows[n];
                            wbPropsObj.WB_SCU_PROP[t][i].propsList[n] = {};
                            wbPropsObj.WB_SCU_PROP[t][i].propsList[n].bxVal ={};
                            wbPropsObj.WB_SCU_PROP[t][i].propsList[n].bxVal['id'] = child_row.querySelector('.wb_control-bx-prop').value;
                            wbPropsObj.WB_SCU_PROP[t][i].propsList[n].bxVal['name'] = child_row.querySelector('.wb_control-bx-prop').getAttribute('data-name');
                            wbPropsObj.WB_SCU_PROP[t][i].propsList[n].wbVal = {};
                            wbPropsObj.WB_SCU_PROP[t][i].propsList[n].wbVal['wb_key'] = child_row.querySelector('.wb_control-wb-prop').getAttribute('data-wb-key');
                            wbPropsObj.WB_SCU_PROP[t][i].propsList[n].wbVal['wb_name'] = child_row.querySelector('.wb_control-wb-prop').value;
                        }
                    }
                }
            };
        }
        var propsInput = document.createElement('input');
        propsInput.setAttribute('type', 'hidden');
        propsInput.setAttribute('name', 'wb_props');
        propsInput.value = JSON.stringify(wbPropsObj);
        this.appendChild(propsInput);
        // console.log(catPropBlocks);
        // console.log(wbPropsObj);
    });
    function on_change_iblock_type(tab) {
        var cat_props_controls = tab[0].querySelectorAll('[data-entity="CAT_PROPS"]');
        var scu_porps_controls = tab[0].querySelectorAll('[data-entity="SCU_PROPS"]');
        clearEntityesAfterIblockChange(cat_props_controls);
        clearEntityesAfterIblockChange(scu_porps_controls);

    };
    function clearEntityesAfterIblockChange(arr) {
        var cab = findParents(arr[0], 'adm-detail-content')
        if(arr.length>1){
            for(var i = 1;i<arr.length; i++){
                var items = cab.querySelectorAll('[data-parent="'+arr[i].id+'"]');
                if(arr[i].querySelector('.wb_control-add-but')){
                    arr[i].querySelector('.wb_control-add-but').style.display = 'none';
                    arr[0].querySelector('.adm-detail-content-cell-r').appendChild(arr[i].querySelector('.wb_control-add-but'));
                }
                if(items && items.length>0){
                    for(var n = 0;n<items.length;n++){
                        items[n].remove();
                    }
                }
                arr[i].remove();
            };
        }
        var firstRow = arr[0];
        var firstRowPropSelect = firstRow.querySelector('.wb_cat_props_select');
        var firstRowWbPropSelect = firstRow.querySelector('.wb_props_select');
        var firstRowItems = cab.querySelectorAll('[data-parent="'+firstRow.id+'"]');
        if(firstRowItems && firstRowItems.length>0){
            for(var n = 0; n<firstRowItems.length;n++){
                firstRowItems[n].remove();
            };
        };
        firstRowPropSelect.options.length = 0;
        firstRowWbPropSelect.value = '';
    };
    // console.log(skuPropsJsOb);
    var createNotExistedRow = function (main ,items, obj) {
        var el = main;
        var table = el.parentNode;
        var WB_template = document.querySelector('#WB_templates');
        var WB_template_row = WB_template.querySelector('tr');
        var new_row = WB_template_row.cloneNode(true);
        var identity = (obj['UF_XML_ID'])?obj['UF_XML_ID']:obj['ID'];
        var name = (obj['UF_NAME'])?obj['UF_NAME']:obj['VALUE'];
        new_row.querySelector('.wb_cat_porp_name').innerHTML =  name +' (id: '+identity+')';
        new_row.querySelector('.wb_control-bx-prop').value = identity;
        new_row.querySelector('.wb_control-bx-prop').setAttribute('data-name', name);
        new_row.setAttribute('data-parent', el.id);
        var wbInput = new_row.querySelector('.wb_control-wb-prop');
        wbInput.style.display = '';
        wbInput.setAttribute('data-wbaction', main.querySelector('.wb_props_select').value);
        table.insertBefore(new_row, items[items.length-1].nextElementSibling);
    };
    var checkRefs = function () {
        let cabs = document.querySelectorAll('.adm-detail-content');
        // console.log(skuPropsJsOb);
        for(let c = 0;c<cabs.length;c++){
            var PROPS_SELECTS = cabs[c].querySelectorAll('[data-entity="WB_SCU_PROP"]');
            // console.log(PROPS_SELECTS);
            for(var i = 0;i<PROPS_SELECTS.length; i++){
                if(PROPS_SELECTS[i].value == '')continue;
                var mainRow = findParents(PROPS_SELECTS[i], 'wb_control-row');
                var rowItems = cabs[c].querySelectorAll('[data-parent="'+mainRow.id+'"]');
                if(rowItems.length<skuPropsJsOb[c][PROPS_SELECTS[i].value]['VALUES'].length){
                    var notExist = [];
                    for (var n = 0;n<skuPropsJsOb[c][PROPS_SELECTS[i].value]['VALUES'].length;n++){
                        var value = skuPropsJsOb[c][PROPS_SELECTS[i].value]['VALUES'][n];
                        var notfind = (value['UF_XML_ID'])?value['UF_XML_ID']:value['ID'];
                        for(var m = 0; m<rowItems.length;m++){
                            if(rowItems[m].querySelector('.wb_control-bx-prop').value==notfind){
                                notfind = false;break;
                            }
                        }
                        if(notfind){
                            notExist.push(value);
                        }
                    }
                    if(notExist.length>0){
                        for(var d = 0;d<notExist.length; d++){
                            createNotExistedRow(mainRow ,rowItems, notExist[d]);
                        }
                    }
                }
            }
            // console.log(PROPS_SELECTS);
            // console.log(propsJsOb);

            var CATS_SELECT =  cabs[c].querySelectorAll('[data-entity="WB_CAT_PROP"]');

            for(var i = 0;i<CATS_SELECT.length; i++){
                // console.log(PROPS_SELECTS[i].value);
                if(CATS_SELECT[i].value == '')continue;
                var mainRow = findParents(CATS_SELECT[i], 'wb_control-row');
                // console.log(mainRow.id);
                var rowItems = cabs[c].querySelectorAll('[data-parent="'+mainRow.id+'"]');
                // console.log(CATS_SELECT[i].value);
                if(rowItems.length<propsJsOb[c][CATS_SELECT[i].value]['VALUES'].length){

                    var notExist = [];
                    for (var n = 0;n<propsJsOb[c][CATS_SELECT[i].value]['VALUES'].length;n++){
                        var value = propsJsOb[c][CATS_SELECT[i].value]['VALUES'][n];
                        var notfind = (value['UF_XML_ID'])?value['UF_XML_ID']:value['ID'];
                        for(var m = 0; m<rowItems.length;m++){
                            if(rowItems[m].querySelector('.wb_control-bx-prop').value==notfind){
                                notfind = false;break;
                            }
                        }
                        if(notfind){
                            notExist.push(value);
                        }
                    }
                    if(notExist.length>0){
                        for(var d = 0;d<notExist.length; d++){
                            createNotExistedRow(mainRow ,rowItems, notExist[d]);
                        }
                    }
                }
            }
            // console.log(CATS_SELECT);

        }
    };
    checkRefs();
</script>

    <?}else
        die();
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>
