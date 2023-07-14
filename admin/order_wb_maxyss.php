<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('MAXYSS_WB_TITLE_ORDER'));

CJSCore::Init( 'jquery' );

global $APPLICATION;
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency;
\Bitrix\Main\UI\Extension::load("ui.hint");?>
<script type="text/javascript">
    BX.ready(function() {
        BX.UI.Hint.init(BX('wb_conainer'));
    })
</script>
<style>
    .adm-detail-content-cell-l > span:first-child{
        color: red;
        padding-left: 10px;
    }
        .status_list_span{
            width: 130px;
            display: inline-block;
        }
</style>
<?

if(CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 2)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_WB_MODULE_TRIAL_2').'</font>';
if(CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 3)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_WB_MODULE_TRIAL_3').'</font>';


if(Loader::includeModule('sale') && Loader::includeModule('iblock') && CModule::IncludeModule(MAXYSS_WB_NAME) && $GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
    if (($_REQUEST['save'] || $_REQUEST['apply']) && $GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) == "W") {
        $option = array("ACTIVE_ORDER_ON"=>"active_order_on", "PERIOD_ORDER"=>"period_order", "COUNT_ORDER"=>"count_order", "VALUTA_ORDER"=>"valuta",  "PERSON_TYPE"=>"person_type", "DELIVERY_SERVICE"=>"delivery_service", "PAYSYSTEM"=>"paysystem", "USER_DEFAULTE"=>"user_defaulte", "NEW"=>"status_0", "CANCEL"=>"status_1", "CLIENT_RECEIVED"=>"status_2", "CLIENT_RETURN"=>"status_3", "SKLAD_WB"=>"status_4", "TRANSIT"=>"status_5", "RETURN_PRODUCT"=>"status_6", "CALLBACK_BX"=>"callback_bx", "STIKER_WIDTH"=>"STIKER_WIDTH", "FLAG_SHIPMENT_UP"=>"FLAG_SHIPMENT_UP", "FLAG_CANCELLED_UP"=>"FLAG_CANCELLED_UP", "FLAG_PAYMENT_UP"=>"FLAG_PAYMENT_UP");
        CHelpMaxyssWB::saveOption($option);

        if($_REQUEST['SKLAD_WB_TRIGER']) {
            foreach ($_REQUEST['SKLAD_WB_TRIGER'] as $cabinet=>$value) {
                if($value == "Y")
                    $triggers[$cabinet]['SKLAD_WB'] = ($_REQUEST['status_4'][$cabinet]) ? htmlspecialcharsbx($_REQUEST['status_4'][$cabinet]) : "";
            }
        }
        if($_REQUEST['CLIENT_RECEIVED_TRIGER']) {
            foreach ($_REQUEST['CLIENT_RECEIVED_TRIGER'] as $cabinet=>$value) {
                if($value == "Y")
                    $triggers[$cabinet]['CLIENT_RECEIVED'] = ($_REQUEST['status_2'][$cabinet]) ? htmlspecialcharsbx($_REQUEST['status_2'][$cabinet]) : "";
            }
        }
        if($_REQUEST['CANCEL_TRIGER']) {
            foreach ($_REQUEST['CANCEL_TRIGER'] as $cabinet=>$value) {
                if($value == "Y")
                    $triggers[$cabinet]['CANCEL'] = ($_REQUEST['status_1'][$cabinet] ) ? htmlspecialcharsbx($_REQUEST['status_1'][$cabinet]) : "";
            }
        }
        Option::set(MAXYSS_WB_NAME, "TRIGGERS", serialize($triggers));



        $arPersonTypeId = CMaxyssWb::get_setting_wb('PERSON_TYPE');
        if(!empty($arPersonTypeId)){
            foreach ($arPersonTypeId as $person_type_id){
                if($person_type_id > 0){
                    $arFields = array(
                        "PERSON_TYPE_ID" => $person_type_id,
                        "NAME" => GetMessage('MAXYSS_WB_PROPERTY_ORDER_WB'),
                        "TYPE" => "STRING",
                        "REQUIED" => 'N',
                        "MULTIPLE" => "N",
                        "SORT" => '100',
                        "USER_PROPS" => "N",
                        "IS_LOCATION" => "N",
                        "CODE" => "MAXYSS_WB_NUMBER",
                        "IS_FILTERED" => 'Y',
                        'ACTIVE' => "Y",
                        "UTIL" => "Y",
                        "DEFAULT_VALUE"=>'',
                        "IS_EMAIL" => "N",
                        "IS_PROFILE_NAME" => "N",
                        "IS_PAYER" => "N",
                        "IS_ADDRESS" => "N",
                        "IS_PHONE" => "N",
                    );

                    $db_props = CSaleOrderProps::GetList(
                        array("SORT" => "ASC"),
                        array(
                            "PERSON_TYPE_ID" => $person_type_id,
                            "=CODE" => "MAXYSS_WB_NUMBER",
                        ),
                        false,
                        false,
                        array()
                    );

                    if (!$props = $db_props->Fetch()){
                        $db_propsGroup = CSaleOrderPropsGroup::GetList(
                            array("SORT" => "ASC"),
                            array("PERSON_TYPE_ID" => $person_type_id),
                            false,
                            false,
                            array()
                        );

                        if ($propsGroup = $db_propsGroup->Fetch())
                        {
                            $arFields["PROPS_GROUP_ID"] = $propsGroup["ID"];
                        }else{
                            echo GetMessage("MAXYSS_WB_PROPERTY_NO_GROUPE");
                        }
                        $ID = CSaleOrderProps::Add($arFields);
                    }
                    unset($arFields);
                    unset($props);
                    unset($propsGroup);

                    // cabinet
                    $arFields = array(
                        "PERSON_TYPE_ID" => $person_type_id,
                        "NAME" => GetMessage('MAXYSS_WB_CABINET_PROPERTY_TITLE'),
                        "TYPE" => "STRING",
                        "REQUIED" => 'N',
                        "MULTIPLE" => "N",
                        "SORT" => '100',
                        "USER_PROPS" => "N",
                        "IS_LOCATION" => "N",
                        "CODE" => "MAXYSS_WB_CABINET",
                        "IS_FILTERED" => 'Y',
                        'ACTIVE' => "Y",
                        "UTIL" => "Y",
                        "DEFAULT_VALUE"=>'',
                        "IS_EMAIL" => "N",
                        "IS_PROFILE_NAME" => "N",
                        "IS_PAYER" => "N",
                        "IS_ADDRESS" => "N",
                        "IS_PHONE" => "N",
                    );

                    $db_props = CSaleOrderProps::GetList(
                        array("SORT" => "ASC"),
                        array(
                            "PERSON_TYPE_ID" => $person_type_id,
                            "=CODE" => "MAXYSS_WB_CABINET",
                        ),
                        false,
                        false,
                        array()
                    );

                    if (!$props = $db_props->Fetch()){
                        $db_propsGroup = CSaleOrderPropsGroup::GetList(
                            array("SORT" => "ASC"),
                            array("PERSON_TYPE_ID" => $person_type_id),
                            false,
                            false,
                            array()
                        );

                        if ($propsGroup = $db_propsGroup->Fetch())
                        {
                            $arFields["PROPS_GROUP_ID"] = $propsGroup["ID"];
                        }else{
                            echo GetMessage("MAXYSS_WB_PROPERTY_NO_GROUPE");
                        }
                        $ID = CSaleOrderProps::Add($arFields);
                    }
                    unset($arFields);
                    unset($props);
                    unset($propsGroup);

                    // rid

                    $arFields = array(
                        "PERSON_TYPE_ID" => $person_type_id,
                        "NAME" => GetMessage('MAXYSS_WB_PROPERTY_RID_WB'),
                        "TYPE" => "STRING",
                        "REQUIED" => 'N',
                        "MULTIPLE" => "N",
                        "SORT" => '100',
                        "USER_PROPS" => "N",
                        "IS_LOCATION" => "N",
                        "CODE" => "MAXYSS_WB_RID",
                        "IS_FILTERED" => 'Y',
                        'ACTIVE' => "Y",
                        "UTIL" => "Y",
                        "DEFAULT_VALUE"=>'',
                        "IS_EMAIL" => "N",
                        "IS_PROFILE_NAME" => "N",
                        "IS_PAYER" => "N",
                        "IS_ADDRESS" => "N",
                        "IS_PHONE" => "N",
                    );

                    $db_props = CSaleOrderProps::GetList(
                        array("SORT" => "ASC"),
                        array(
                            "PERSON_TYPE_ID" => $person_type_id,
                            "=CODE" => "MAXYSS_WB_RID",
                        ),
                        false,
                        false,
                        array()
                    );

                    if (!$props = $db_props->Fetch()){
                        $db_propsGroup = CSaleOrderPropsGroup::GetList(
                            array("SORT" => "ASC"),
                            array("PERSON_TYPE_ID" => $person_type_id),
                            false,
                            false,
                            array()
                        );

                        if ($propsGroup = $db_propsGroup->Fetch())
                        {
                            $arFields["PROPS_GROUP_ID"] = $propsGroup["ID"];
                        }else{
                            echo GetMessage("MAXYSS_WB_PROPERTY_NO_GROUPE");
                        }
                        $ID = CSaleOrderProps::Add($arFields);
                    }

                    // ��� �������� ��������

                    $arFields = array(
                        "PERSON_TYPE_ID" => $person_type_id,
                        "NAME" => GetMessage('MAXYSS_WB_DELIVERY_TYPE'),
                        "TYPE" => "STRING",
                        "REQUIED" => 'N',
                        "MULTIPLE" => "N",
                        "SORT" => '100',
                        "USER_PROPS" => "N",
                        "IS_LOCATION" => "N",
                        "CODE" => "MAXYSS_WB_DELIVERY_TYPE",
                        "IS_FILTERED" => 'Y',
                        'ACTIVE' => "Y",
                        "UTIL" => "Y",
                        "DEFAULT_VALUE"=>'',
                        "IS_EMAIL" => "N",
                        "IS_PROFILE_NAME" => "N",
                        "IS_PAYER" => "N",
                        "IS_ADDRESS" => "N",
                        "IS_PHONE" => "N",
                    );
                    $db_props = CSaleOrderProps::GetList(
                        array("SORT" => "ASC"),
                        array(
                            "PERSON_TYPE_ID" => $person_type_id,
                            "=CODE" => "MAXYSS_WB_DELIVERY_TYPE",
                        ),
                        false,
                        false,
                        array()
                    );

                    if (!$props = $db_props->Fetch()){
                        $db_propsGroup = CSaleOrderPropsGroup::GetList(
                            array("SORT" => "ASC"),
                            array("PERSON_TYPE_ID" => $person_type_id),
                            false,
                            false,
                            array()
                        );

                        if ($propsGroup = $db_propsGroup->Fetch())
                        {
                            $arFields["PROPS_GROUP_ID"] = $propsGroup["ID"];
                        }else{
                            echo GetMessage("MAXYSS_WB_PROPERTY_NO_GROUPE");
                        }
                        $ID = CSaleOrderProps::Add($arFields);
                    }
                }
            }
        }

        $arActiveOrder = CMaxyssWb::get_setting_wb('ACTIVE_ORDER_ON');
        if(!empty($arActiveOrder)){
            foreach ($arActiveOrder as $cabinet => $value){
                $period_order = (CMaxyssWb::get_setting_wb('PERIOD_ORDER', $cabinet) > 0)? CMaxyssWb::get_setting_wb('PERIOD_ORDER', $cabinet) : '600';
                if ($value == 'Y') {
                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::loadNewOrders('".$cabinet."'%"));
                    if($res->Fetch()){
                        $ro = $res->NavStart(100);
                        while($r = $res->NavNext(true, "agent_")){
                            $res_agent = CAgent::GetById($agent_ID);
                            if($arRes = $res_agent->fetch()) {
                                if (intval($arRes['ID']) > 0 && $arRes['AGENT_INTERVAL'] != $period_order) {
                                    $arFieldAgent = array(
                                        "AGENT_INTERVAL" => $period_order,
                                    );
                                    CAgent::Update(intval($arRes['ID']), $arFieldAgent);
                                }
                            }
                        }

                    }
                    elseif (!$res->Fetch() && intval($period_order) > 0)
                    {
                        CAgent::AddAgent(
                            "CMaxyssWb::loadNewOrders('".$cabinet."');",
                            "maxyss.wb",
                            "N",
                            $period_order,
                            "",
                            "Y",
                            "",
                            0);
                    }


                    //status

                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::getStatusOrders('".$cabinet."'%"));
                    if($res->Fetch()){
                        $ro = $res->NavStart(100);
                        while($r = $res->NavNext(true, "agent_")){
                            $res_agent = CAgent::GetById($agent_ID);
                            if($arRes = $res_agent->fetch()) {
                                if (intval($arRes['ID']) > 0 && $arRes['AGENT_INTERVAL'] != ( $period_order + 40 )) {
                                    $arFieldAgent = array(
                                        "AGENT_INTERVAL"=>( $period_order + 40 ),
                                    );
                                    CAgent::Update(intval($arRes['ID']), $arFieldAgent);
                                }
                            }
                        }

                    }
                    elseif (!$res->Fetch() && intval($period_order) > 0)
                    {
                        CAgent::AddAgent(
                            "CMaxyssWb::getStatusOrders('".$cabinet."');",
                            "maxyss.wb",
                            "N",
                            $period_order + 40,
                            "",
                            "Y",
                            "",
                            0
                        );
                    }


                }else{
                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::loadNewOrders('".$cabinet."'%"));
                    if($res->Fetch()){
                        $ro = $res->NavStart(100);
                        while($r = $res->NavNext(true, "agent_")){
                            CAgent::Delete($agent_ID);

                        }

                    }

                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::getStatusOrders('".$cabinet."'%"));
                    if($res->Fetch()){
                        $ro = $res->NavStart(100);
                        while($r = $res->NavNext(true, "agent_")){
                            CAgent::Delete($agent_ID);
                        }

                    }
                }
            }
        }
    }
    $arSettings = CMaxyssWb::settings_wb();
    $arTabs = array();
    $kab_key = 0;
    if(!empty($arSettings['LK_WB_DATA']))
        foreach($arSettings['LK_WB_DATA'] as $key => $lk){
            $arTabs[] = array(
                "DIV" => "edit_settings_".($kab_key+1),
                "TAB" => GetMessage("WB_MAXYSS_LK_TITLE_TAB").$key,
                "ICON" => "settings",
                "CABINET" => $key,
                "PAGE_TYPE" => "tab_settings",
            );
            $kab_key++;
        }

    if(empty($arTabs)) die();

    $tabControl = new CAdminTabControl("tabControl", $arTabs);

    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) != "W"){
    ?>
    <div style="color: red"><?=GetMessage("WB_MAXYSS_NOT_RIGHT_EDIT_SETTINGS")?></div><br>
<?}?>
    <form action="<?=MAXYSS_WB_NAME?>_order_wb_maxyss.php?lang=<?=LANGUAGE_ID?>" method="post">
        <?$tabControl->Begin();   // ���������� ?>

        <?foreach($arTabs as $key_cab => $arTab)
            {

                $arFlagSipmentBitrixBD = $arSettings["FLAG_SHIPMENT_UP"][$arTab["CABINET"]];
                $arFlagCancelledBitrixBD = $arSettings["FLAG_CANCELLED_UP"][$arTab["CABINET"]];
                $arFlagPaymentBitrixBD = $arSettings["FLAG_PAYMENT_UP"][$arTab["CABINET"]];

            $tabControl->BeginNextTab();
            ?>
                <tr class="heading">
                    <td colspan="2"><?=GetMessage('MAXYSS_WB_MODULE_ACTIVITY')?></td>
                </tr>
                <tr>
                    <td style="width: 50%;" class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_AGENT_ORDER_ACTIVE')?></td>
                    <td class="adm-detail-content-cell-r">
                        <input type="hidden" name="active_order_on[<?=$arTab['CABINET']?>]" value="N">
                        <input type="checkbox" name="active_order_on[<?=$arTab['CABINET']?>]" id="active_order_on_<?=$arTab['CABINET']?>" class="adm-designed-checkbox" <?echo ($arSettings['ACTIVE_ORDER_ON'][$arTab['CABINET']]=="Y")? 'checked = "checked"' : ''?> value="Y">
                        <label class="adm-designed-checkbox-label" for="active_order_on_<?=$arTab['CABINET']?>" title=""></label>
                    </td>
                </tr>

                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_CALLBACK_BX')?></td>
                    <td class="adm-detail-content-cell-r">
                        <input type="hidden" name="callback_bx[<?=$arTab['CABINET']?>]" value="N">
                        <input type="checkbox" name="callback_bx[<?=$arTab['CABINET']?>]" id="callback_bx_<?=$arTab["CABINET"]?>" class="adm-designed-checkbox" <?echo ($arSettings['CALLBACK_BX'][$arTab['CABINET']]=="Y")? 'checked = "checked"' : ''?> value="Y">
                        <label class="adm-designed-checkbox-label" for="callback_bx_<?=$arTab["CABINET"]?>" title=""></label><span data-hint="<?=GetMessage('MAXYSS_WB_CALLBACK_BX_TIP')?>"></span>
                    </td>
                </tr>
                <tr class="heading">
                    <td colspan="2"><?=GetMessage('MAXYSS_WB_STIKERS_PARAM')?></td>
                </tr>
                <tr>
                    <td style="width: 50%;" class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_STIKERS_WIDTH')?></td>
                    <td class="adm-detail-content-cell-r">
                        <input type="radio" name="STIKER_WIDTH[<?=$arTab['CABINET']?>]" id="STIKER_WIDTH_<?=$arTab['CABINET']?>_58" class="adm-designed-checkbox" <?echo ($arSettings['STIKER_WIDTH'][$arTab['CABINET']]=="58" || !isset($arSettings['STIKERS_WIDTH']))? 'checked = "checked"' : ''?> value="58"> 58 x 40 <?=GetMessage('MAXYSS_WB_STIKERS_MM')?>
                        <label style="margin-right: 50px" class="adm-designed-checkbox-label" for="STIKER_WIDTH_<?=$arTab['CABINET']?>_58"></label>
                        <input type="radio" name="STIKER_WIDTH[<?=$arTab['CABINET']?>]" id="STIKER_WIDTH_<?=$arTab['CABINET']?>_40" class="adm-designed-checkbox" <?echo ($arSettings['STIKER_WIDTH'][$arTab['CABINET']]=="40")? 'checked = "checked"' : ''?> value="40"> 40 x 30 <?=GetMessage('MAXYSS_WB_STIKERS_MM')?>
                        <label class="adm-designed-checkbox-label" for="STIKER_WIDTH_<?=$arTab['CABINET']?>_40"></label>
                    </td>
                </tr>
                <tr class="heading">
                    <td colspan="2"><?=GetMessage('MAXYSS_WB_REPLACE')?></td>
                </tr>
                <?$valuta = \Bitrix\Currency\CurrencyManager::getCurrencyList(); ?>
                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_VALUTA_ORDER')?><span>*</span></td>
                    <td class="adm-detail-content-cell-r">
                        <select name="valuta[<?=$arTab['CABINET']?>]">
                            <?foreach ($valuta as $key => $valut){?>
                                <option value="<?=$key?>" <?echo (($arSettings['VALUTA_ORDER'][$arTab['CABINET']] == $key)? 'selected = "selected"' : '')?>><?=$valut?></option>
                            <?}?>
                        </select>
                    </td>
                </tr>

                <?
                $db_ptype = CSalePersonType::GetList(Array("SORT" => "ASC"), Array("LID"=>$arSettings['SITE']));
                while ($ptype = $db_ptype->Fetch())
                {
                    $PersonType[$ptype['ID']] = $ptype['NAME'];
                }
                ?>
                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_PERSON_TYPE')?><span>*</span></td>
                    <td class="adm-detail-content-cell-r">
                        <select name="person_type[<?=$arTab['CABINET']?>]">
                            <?foreach ($PersonType as $key => $type){?>
                                <option value="<?=$key?>" <?echo (($arSettings['PERSON_TYPE'][$arTab['CABINET']] == $key)? 'selected = "selected"' : '')?>><?=$type?></option>
                            <?}?>
                        </select>
                    </td>
                </tr>

                <?
                $arDelivery = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
                foreach ($arDelivery as $key => $deliver){
                    $arDeliverySelect[$key] = $deliver['NAME'];
                }
                ?>
                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_DELIVERY')?><span>*</span></td>
                    <td class="adm-detail-content-cell-r">
                        <select name="delivery_service[<?=$arTab['CABINET']?>]">
                            <?foreach ($arDeliverySelect as $key => $type){?>
                                <option value="<?=$key?>" <?echo (($arSettings['DELIVERY_SERVICE'][$arTab['CABINET']] == $key)? 'selected = "selected"' : '')?>><?=$type?></option>
                            <?}?>
                        </select>
                    </td>
                </tr>

                <?
                $db_paysystem = CSalePaySystem::GetList($arOrder = Array("SORT"=>"ASC", "PSA_NAME"=>"ASC"), Array("ACTIVE"=>"Y"));
                while ($paysystem = $db_paysystem->Fetch())
                {
                    $arPaySystem[$paysystem['ID']] = $paysystem["NAME"];
                }?>
                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_PAYSYSTEM')?><span>*</span></td>
                    <td class="adm-detail-content-cell-r">
                        <select name="paysystem[<?=$arTab['CABINET']?>]">
                            <?foreach ($arPaySystem as $key => $type){?>
                                <option value="<?=$key?>" <?echo (($arSettings['PAYSYSTEM'][$arTab['CABINET']] == $key)? 'selected = "selected"' : '')?>><?=$type?></option>
                            <?}?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_USER_DEFAULTE_OZON')?></td>
                    <td class="adm-detail-content-cell-r">
                        <input name="user_defaulte[<?=$arTab['CABINET']?>]" value="<?echo $arSettings['USER_DEFAULTE'][$arTab['CABINET']]?>"><span data-hint="<?=GetMessage('MAXYSS_WB_USER_DEFAULTE_TIP')?>"></span>
                    </td>
                </tr>
                <tr><td colspan="2">
                        <table style="width: 100%">
                            <tbody>
                            <tr class="heading">
                                <td colspan="6"><?=GetMessage('MAXYSS_WB_STATUS_OZON_HEAD')?></td>
                            </tr>
                            <!--������������ ��������-->
                            <?
                            $db_osatatus = CSaleStatus::GetList( array("SORT"=>"ASC"),array(), false, false, array());
                            while ($osatatus = $db_osatatus->Fetch())
                            {
                                $arStatus[$osatatus['ID']] = $osatatus['NAME'];
                            }
//                            $triggers_options = unserialize(Option::get(MAXYSS_WB_NAME, 'TRIGGERS', ''));
                            $triggers_options = $arSettings['TRIGGERS'][$arTab['CABINET']];
                            ?>
                            <tr style="font-weight: bold">
                                <td style="width: 20%" class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_STATUS_TITLE')?></td>
                                <td style="padding-left: 50px" class="adm-detail-content-cell-center"><?=GetMessage('MAXYSS_WB_STATUS_BITRIX_TITLE')?></td>
                                <td style="text-align: center"><?=GetMessage('MAXYSS_WB_FLAG_CANCELED')?><span data-hint="<?=GetMessage('MAXYSS_WB_FLAG_CANCELED_TIP')?>"></span></td>
                                <td style="text-align: center"><?=GetMessage('MAXYSS_WB_FLAG_SHIP')?><span data-hint="<?=GetMessage('MAXYSS_WB_FLAG_SHIP_TIP')?>"></span></td>
                                <td style="text-align: center"><?=GetMessage('MAXYSS_WB_FLAG_PAYMENT')?><span data-hint="<?=GetMessage('MAXYSS_WB_FLAG_PAYMENT_TIP')?>"></span></td>
                                <td style="text-align: center"><?=GetMessage('MAXYSS_WB_STATUS_TRIGGER_TITLE')?><span data-hint="<?=GetMessage('MAXYSS_WB_STATUS_TRIGGER_TITLE_TIP')?>"></span></td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    <?=GetMessage('MAXYSS_WB_NEW')?>
                                </td>
                                <td class="adm-detail-content-cell-center">
                                    <select name="status_0[<?=$arTab['CABINET']?>]">
                                        <?foreach ($arStatus as $key => $type){?>
                                            <option value="<?=$key?>" <?echo (($arSettings['NEW'][$arTab['CABINET']] == $key)? 'selected = "selected"' : '')?>><?=$type?></option>
                                        <?}?>
                                    </select>
                                </td>
                                <td></td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_0]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_SHIPMENT_UP_status_0_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_0]"
                                           value="Y" <? echo ($arFlagSipmentBitrixBD['status_0'] == 'Y') ? 'checked = "checked"' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_0]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_PAYMENT_UP_status_0_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_0]"
                                           value="Y" <? echo ($arFlagPaymentBitrixBD['status_0'] == 'Y') ? 'checked = "checked"' : '' ?>>
                                </td>
                                <td class="adm-detail-content-cell-r"></td>
                            </tr>

                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    <?=GetMessage('MAXYSS_WB_CANCELLED')?>
                                </td>
                                <td class="adm-detail-content-cell-center">
                                    <select name="status_1[<?=$arTab['CABINET']?>]">
                                        <option value=""></option>

                                        <?foreach ($arStatus as $key => $type){?>
                                            <option value="<?=$key?>" <?echo ($arSettings['CANCEL'][$arTab['CABINET']] == $key)? 'selected = "selected"' : ''?>><?=$type?></option>
                                        <?}?>
                                    </select>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_1]" value="N">
                                    <input type="checkbox"
                                           onchange="control_status_cancel_checkbox($(this));"
                                           id="FLAG_CANCELLED_UP_status_1_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_1]"
                                           value="Y" <? echo ($arFlagCancelledBitrixBD['status_1'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($triggers_options['CANCEL'])? 'disabled="disabled" ': '';?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_1]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_SHIPMENT_UP_status_1_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_1]"
                                           value="Y" <? echo ($arFlagSipmentBitrixBD['status_1'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($triggers_options['CANCEL'])? 'disabled="disabled" ': '';?>
                                        <?echo ($arFlagCancelledBitrixBD['status_1'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_1]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_PAYMENT_UP_status_1_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_1]"
                                           value="Y" <? echo ($arFlagPaymentBitrixBD['status_1'] == 'Y') ? 'checked = "checked"' : '' ?>
                                           <?echo ($triggers_options['CANCEL'])? 'disabled="disabled" ': '';?>
                                        <?echo ($arFlagCancelledBitrixBD['status_1'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center">
                                    <input type="hidden" value="N" name="CANCEL_TRIGER[<?=$arTab['CABINET']?>]">
                                    <input type="checkbox" id="CANCEL_TRIGER_<?= $arTab["CABINET"] ?>" onchange="control_status_checkbox($(this));" value="Y" <?echo ($triggers_options['CANCEL'])? 'checked': '';?> name="CANCEL_TRIGER[<?=$arTab['CABINET']?>]"><span data-hint="<?=GetMessage('MAXYSS_WB_TRIGGER_CANCEL_TIP')?>"></span>
                                </td>
                            </tr>

                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    <?=GetMessage('MAXYSS_WB_CLIENT_RECEIVED')?>
                                </td>
                                <td class="adm-detail-content-cell-center">
                                    <select name="status_2[<?=$arTab['CABINET']?>]">
                                        <option value=""></option>

                                        <?foreach ($arStatus as $key => $type){?>
                                            <option value="<?=$key?>" <?echo ($arSettings['CLIENT_RECEIVED'][$arTab['CABINET']] == $key)? 'selected = "selected"' : ''?>><?=$type?></option>
                                        <?}?>
                                    </select>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_2]" value="N">
                                    <input type="checkbox"
                                           onchange="control_status_cancel_checkbox($(this));"
                                           id="FLAG_CANCELLED_UP_status_2_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_2]"
                                           value="Y" <? echo ($arFlagCancelledBitrixBD['status_2'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($triggers_options['CLIENT_RECEIVED'])? 'disabled="disabled"': '';?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_2]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_SHIPMENT_UP_status_2_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_2]"
                                           value="Y" <? echo ($arFlagSipmentBitrixBD['status_2'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($triggers_options['CLIENT_RECEIVED'])? 'disabled="disabled"': '';?>
                                        <?echo ($arFlagCancelledBitrixBD['status_2'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_2]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_PAYMENT_UP_status_2_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_2]"
                                           value="Y" <? echo ($arFlagPaymentBitrixBD['status_2'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($triggers_options['CLIENT_RECEIVED'])? 'disabled="disabled"': '';?>
                                        <?echo ($arFlagCancelledBitrixBD['status_2'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center">
                                    <input type="hidden" value="N" name="CLIENT_RECEIVED_TRIGER[<?=$arTab['CABINET']?>]">
                                    <input type="checkbox" id="CLIENT_RECEIVED_TRIGER_<?= $arTab["CABINET"] ?>" onchange="control_status_checkbox($(this));" value="Y" <?echo ($triggers_options['CLIENT_RECEIVED'])? 'checked': '';?> name="CLIENT_RECEIVED_TRIGER[<?=$arTab['CABINET']?>]"><span data-hint="<?=GetMessage('MAXYSS_WB_TRIGGER_CLIENT_RECEIVED_TIP')?>">
                                </td>
                            </tr>

                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    <?=GetMessage('MAXYSS_WB_CLIENT_RETURN')?>
                                </td>
                                <td class="adm-detail-content-cell-center">
                                    <select name="status_3[<?=$arTab['CABINET']?>]">
                                        <option value=""></option>

                                        <?foreach ($arStatus as $key => $type){?>
                                            <option value="<?=$key?>" <?echo ($arSettings['CLIENT_RETURN'][$arTab['CABINET']] == $key)? 'selected = "selected"' : ''?>><?=$type?></option>
                                        <?}?>
                                    </select>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_3]" value="N">
                                    <input type="checkbox"
                                           onchange="control_status_cancel_checkbox($(this));"
                                           id="FLAG_CANCELLED_UP_status_3_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_3]"
                                           value="Y" <? echo ($arFlagCancelledBitrixBD['status_3'] == 'Y') ? 'checked = "checked"' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_3]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_SHIPMENT_UP_status_3_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_3]"
                                           value="Y" <? echo ($arFlagSipmentBitrixBD['status_3'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_3'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_3]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_PAYMENT_UP_status_3_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_3]"
                                           value="Y" <? echo ($arFlagPaymentBitrixBD['status_3'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_3'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td class="adm-detail-content-cell-r"></td>
                            </tr>
                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    <?=GetMessage('MAXYSS_WB_SKLAD_WB')?>
                                </td>
                                <td class="adm-detail-content-cell-center">
                                    <select name="status_4[<?=$arTab['CABINET']?>]">
                                        <option value=""></option>
                                        <?foreach ($arStatus as $key => $type){?>
                                            <option value="<?=$key?>" <?echo ($arSettings['SKLAD_WB'][$arTab['CABINET']] == $key)? 'selected = "selected"' : ''?>><?=$type?></option>
                                        <?}?>
                                    </select>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_4]" value="N">
                                    <input type="checkbox"
                                           onchange="control_status_cancel_checkbox($(this));"
                                           id="FLAG_CANCELLED_UP_status_4_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_4]"
                                           value="Y" <? echo ($arFlagCancelledBitrixBD['status_4'] == 'Y') ? 'checked = "checked"' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_4]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_SHIPMENT_UP_status_4_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_4]"
                                           value="Y" <? echo ($arFlagSipmentBitrixBD['status_4'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_4'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_4]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_PAYMENT_UP_status_4_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_4]"
                                           value="Y" <? echo ($arFlagPaymentBitrixBD['status_4'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_4'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td class="adm-detail-content-cell-r">
                                    <input type="hidden" value="N" name="SKLAD_WB_TRIGER[<?=$arTab['CABINET']?>]">
                                    <?/* ��������   ?>
                                    <input type="checkbox" value="Y" <?echo ($triggers_options['SKLAD_WB'])? 'checked': '';?> name="SKLAD_WB_TRIGER[<?=$arTab['CABINET']?>]"><span data-hint="<?=GetMessage('MAXYSS_WB_TRIGGER_SKLAD_WB_TIP')?>"><?*/?>
                                </td>
                            </tr>

                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    <?=GetMessage('MAXYSS_WB_TRANSIT')?>
                                </td>
                                <td class="adm-detail-content-cell-center">
                                    <select name="status_5[<?=$arTab['CABINET']?>]">
                                        <option value=""></option>
                                        <?foreach ($arStatus as $key => $type){?>
                                            <option value="<?=$key?>" <?echo ($arSettings['TRANSIT'][$arTab['CABINET']] == $key)? 'selected = "selected"' : ''?>><?=$type?></option>
                                        <?}?>
                                    </select>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_5]" value="N">
                                    <input type="checkbox"
                                           onchange="control_status_cancel_checkbox($(this));"
                                           id="FLAG_CANCELLED_UP_status_5_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_5]"
                                           value="Y" <? echo ($arFlagCancelledBitrixBD['status_5'] == 'Y') ? 'checked = "checked"' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_5]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_SHIPMENT_UP_status_5_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_5]"
                                           value="Y" <? echo ($arFlagSipmentBitrixBD['status_5'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_5'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_5]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_PAYMENT_UP_status_5_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_5]"
                                           value="Y" <? echo ($arFlagPaymentBitrixBD['status_5'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_5'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td class="adm-detail-content-cell-r"></td>
                            </tr>

                            <tr>
                                <td class="adm-detail-content-cell-l">
                                    <?=GetMessage('MAXYSS_WB_RETURN_PRODUCT')?>
                                </td>
                                <td class="adm-detail-content-cell-center">
                                    <select name="status_6[<?=$arTab['CABINET']?>]">
                                        <option value=""></option>

                                        <?foreach ($arStatus as $key => $type){?>
                                            <option value="<?=$key?>" <?echo ($arSettings['RETURN_PRODUCT'][$arTab['CABINET']] == $key)? 'selected = "selected"' : ''?>><?=$type?></option>
                                        <?}?>
                                    </select>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_6]" value="N">
                                    <input type="checkbox"
                                           onchange="control_status_cancel_checkbox($(this));"
                                           id="FLAG_CANCELLED_UP_status_6_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_CANCELLED_UP[<?= $arTab["CABINET"] ?>][status_6]"
                                           value="Y" <? echo ($arFlagCancelledBitrixBD['status_6'] == 'Y') ? 'checked = "checked"' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_6]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_SHIPMENT_UP_status_6_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_SHIPMENT_UP[<?= $arTab["CABINET"] ?>][status_6]"
                                           value="Y" <? echo ($arFlagSipmentBitrixBD['status_6'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_6'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td style="text-align: center" class="adm-detail-content-cell-r">
                                    <input type="hidden" name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_6]"
                                           value="N">
                                    <input type="checkbox" id="FLAG_PAYMENT_UP_status_6_<?= $arTab["CABINET"] ?>"
                                           name="FLAG_PAYMENT_UP[<?= $arTab["CABINET"] ?>][status_6]"
                                           value="Y" <? echo ($arFlagPaymentBitrixBD['status_6'] == 'Y') ? 'checked = "checked"' : '' ?>
                                        <?echo ($arFlagCancelledBitrixBD['status_6'] == 'Y') ? 'disabled="disabled" ' : '' ?>>
                                </td>
                                <td class="adm-detail-content-cell-r"></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <!--������������ ��������-->
                <tr>
                    <td colspan="2">
                        <table style="width: 100%">
                            <tbody>
                                <tr class="heading">
                                    <td colspan="2"><?=GetMessage('MAXYSS_WB_PERIOD_AGENT')?></td>
                                </tr>
                                <tr>
                                    <td style="width: 50%;" class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_PERIOD_AGENT_TIME')?></td>
                                    <td class="adm-detail-content-cell-r">
                                        <input name="period_order[<?=$arTab['CABINET']?>]" value="<?echo ($arSettings['PERIOD_ORDER'][$arTab['CABINET']])?  $arSettings['PERIOD_ORDER'][$arTab['CABINET']] : 1200;?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="adm-detail-content-cell-l"><?=GetMessage('MAXYSS_WB_COUNT_ORDERS')?></td>
                                    <td class="adm-detail-content-cell-r">
                                        <input name="count_order[<?=$arTab['CABINET']?>]" value="<?echo ($arSettings['COUNT_ORDER'][$arTab['CABINET']])?  $arSettings['COUNT_ORDER'][$arTab['CABINET']] : 500;?>"><span data-hint="<?=GetMessage('MAXYSS_WB_COUNT_ORDERS_TIP')?>"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?}?>
            <?$tabControl->Buttons(array(
                "back_url"=>MAXYSS_WB_NAME."_order_wb_maxyss.php?lang=".LANGUAGE_ID,

            ));?>

            <?$tabControl->End();?>

<!--        <div class="adm-detail-content-btns-wrap" id="editTab_buttons_div" style="left: 0px;">-->
<!--            <div class="adm-detail-content-btns">-->
<!--                <input type="submit" name="save_order" value="--><?//=GetMessage('MAXYSS_WB_MODULE_SAVE')?><!--">-->
<!--            </div>-->
<!--        </div>-->
    </form>
    <script>
        function control_status_checkbox(t){
            let input_chk = $(t);
            let input_chk_parent = input_chk.parent().parent();
            let all_chk = input_chk_parent.find("input[type=checkbox]");
            for( let i=0; i < (all_chk.length - 1); i++ ){
                if(input_chk.prop('checked') === true)
                    $(all_chk[i]).prop('checked', false).prop('disabled', true);
                else
                    $(all_chk[i]).prop('disabled', false);

            }
        }
        function control_status_cancel_checkbox(t){
            let input_chk = $(t);
            let input_chk_parent = input_chk.parent().parent();
            let all_chk = input_chk_parent.find("input[type=checkbox]");
            let count_for = all_chk.length - 1;
            if (count_for == 2) count_for = 3;
                for( let i=1; i < count_for; i++ ){
                if(input_chk.prop('checked') === true)
                    $(all_chk[i]).prop('checked', false).prop('disabled', true);
                else
                    $(all_chk[i]).prop('disabled', false);
            }
        }
    </script>
<?}else
    die();
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>
