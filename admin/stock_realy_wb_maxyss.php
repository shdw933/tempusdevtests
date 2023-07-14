<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('WB_MAXYSS_MENU_STOCK_TITLE'));

CJSCore::Init( 'jquery' );
set_time_limit(300);
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
    .wb_table td{
        border-bottom: 1px solid #7a7a7a;
    }
</style>
<?

if(CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 2)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_WB_MODULE_TRIAL_2').'</font>';
if(CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 3)
    echo '<font style="color:red;">'.GetMessage('MAXYSS_WB_MODULE_TRIAL_3').'</font>';


if(Loader::includeModule('sale') && Loader::includeModule('iblock') && CModule::IncludeModule(MAXYSS_WB_NAME)) {

    if($_REQUEST['LK_WB']) $cabinet = $_REQUEST['LK_WB']; else $cabinet = 'DEFAULT';

    $arSettings = CMaxyssWb::settings_wb($cabinet);
    $arStock = array();
    $arPrice = array();
    $arBC = array();
    $warehouses = array();

    $arPrice = CMaxyssWbprice::getPrices($arSettings["AUTHORIZATION"]);
    $warehouses = CRestQueryWB::rest_warehouses_get($arSettings["AUTHORIZATION"]);

    if(is_array($arPrice)) {
        if (!empty($arPrice)) {
            foreach ($arPrice as $price) {
                $arNmId[] = $price['nmId'];
                $Prices[$price['nmId']] = $price;
            }
        }
    }else{ echo $arPrice;}
    $iblock_id = $arSettings["IBLOCK_ID"];
    $iblock_shkod = $arSettings["SHKOD"];
    $iblock_article = $arSettings["ARTICLE"];

    if($cabinet=="DEFAULT")
        $cabinet_ = '';
    else
        $cabinet_ = $cabinet;

    $arWNm = array();
    if($iblock_id > 0 && $iblock_shkod != '' /*&& $iblock_article != ''*/) {
        $iblock_info = CCatalog::GetByIDExt($iblock_id);
        $arSelect = Array("ID", "NAME", "IBLOCK_ID", "IBLOCK_TYPE_ID", /*"PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", */"PROPERTY_".$arSettings['SHKOD']);
        $arFilter = Array(array(
            "LOGIC" => "OR",
            array("!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false),
            array("!PROPERTY_PROP_MAXYSS_CARDID_WB" => false),
            array("!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false),
        ),"IBLOCK_ID" => $iblock_id);
//        $arFilter = Array("!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false,"IBLOCK_ID" => $iblock_id);
        $dbTp = CIBlockElement::GetList(Array('ID'=>"asc"), $arFilter, false, array('nTopCount'=>200), $arSelect);
        $arSkus = array();
        while ($arTp = $dbTp->GetNextElement()) {
            if(count($arSkus)>99) break;
            $ar_tovar = array();
            $key_cab = false;
            $arFields = $arTp->GetFields();
            $arProps = $arTp->GetProperties();

            $next_id = $arFields['ID'];

            if(is_array($arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']))
                $key_cab = (array_search($cabinet, $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) !== false)? array_search($cabinet, $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) : array_search($cabinet_, $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']);
            $ar_tovar = CCatalogProduct::GetByID($arFields['ID']); // item as product
            if($key_cab !== false && $ar_tovar['TYPE'] == 1) {
                    if($arProps[$arSettings['SHKOD']]['VALUE'] != '') {
                        $arSkus[] = strval($arProps[$arSettings['SHKOD']]['VALUE']);

                        $arFields['NMID'] = $arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cab];
                        $arElem[$arFields['ID']] = $arFields;

                        if (in_array($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cab], $arWNm)) {
                            $arElem[$arFields['ID']]['DOUBLE'] = true;
                        }
                        $arWNm[$arFields['ID']] = $arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cab];
                    }
            }
            else
            {
                if($ar_tovar['TYPE'] == 3 && is_array($iblock_info)) {
                    $dbTp_ = CIBlockElement::GetList(Array(), array('IBLOCK_ID' => $iblock_info["OFFERS_IBLOCK_ID"], "PROPERTY_" . $iblock_info['SKU_PROPERTY_ID'] => $arFields["ID"]), false, false, $arSelect);
                    while ($arTp_ = $dbTp_->GetNextElement()) {
                        $key_cab = false;
                        $arFieldsTp = $arTp_->GetFields();
                        $arPropsTp = $arTp_->GetProperties();

                        if (is_array($arPropsTp['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']))
                            $key_cab = (array_search($cabinet, $arPropsTp['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) !== false) ? array_search($cabinet, $arPropsTp['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) : array_search($cabinet_, $arPropsTp['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']);

                        if ($key_cab !== false && $arPropsTp[$arSettings['SHKOD']]['VALUE'] != '') {
                            $arSkus[] = strval($arPropsTp[$arSettings['SHKOD']]['VALUE']);


                            $arFieldsTp['NMID'] = $arPropsTp['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cab];
                            $arElem[$arFieldsTp['ID']] = $arFieldsTp;
                            if (in_array($arFieldsTp['PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE'], $arWNm) && array_search($arPropsTp['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cab], $arWNm) != $arFieldsTp["PROPERTY_" . $iblock_info['SKU_PROPERTY_ID'] . "_VALUE"]) {
                                $arElem[$arFieldsTp['ID']]['DOUBLE'] = true;
                            }
                            $arWNm[$arFieldsTp["PROPERTY_" . $iblock_info['SKU_PROPERTY_ID'] . "_VALUE"]] = $arPropsTp['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cab];
                        }
                    }
                }
            }
        }
        if(!empty($arSkus)){
            if(!empty($warehouses)){
                foreach ($warehouses as $wh){
                    $arWarehousesIdKey[$wh["id"]] = $wh;
                    $arStocks[$wh["id"]] = CMaxyssWb::getStockV3($arSettings["AUTHORIZATION"], $wh['id'], $arSkus);
                }
            }
        }
        if(!empty($arStocks)){
            foreach ($arStocks as $wh_id => $st){
                if(is_array($st) && !empty($st)) {
                    foreach ($st as $s) {
                        $arStocksBarcodeKey[$s['sku']][$wh_id]["amount"] = $s["amount"];
                        $arStocksBarcodeKey[$s['sku']][$wh_id]["wh_name"] = $arWarehousesIdKey[$wh_id]["name"];
                    }
                }
            }
        }
    }
$arLk = CMaxyssWb::get_setting_wb('AUTHORIZATION');
    if ($_REQUEST['COUNT_STEP_EL'] && $GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        Option::set(MAXYSS_WB_NAME, "COUNT_STEP_EL", htmlspecialcharsbx($_REQUEST['COUNT_STEP_EL']));
    }
    ?>
    <form id="lk_form" method="get" action="<?=MAXYSS_WB_NAME?>_stock_realy_wb_maxyss.php?lang=<?=LANGUAGE_ID?>" style="margin-bottom: 10px">
        <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
        <? $count_step_el = Option::get(MAXYSS_WB_NAME, "COUNT_STEP_EL", 200);?>
        <input type="hidden" disabled name="COUNT_STEP_EL" value="<?echo  ($count_step_el > 0)? $count_step_el : 200?>">
        <select name="LK_WB" onchange="$('#lk_form').submit();">
            <?foreach ($arLk as $key=>$auth){?><option <?echo ($cabinet == $key)? 'selected="selected"' : '';?> value="<?=$key?>"><?=$key?></option><?}?>
        </select><label style="margin-left: 10px"><?=GetMessage('MAXYSS_WB_CUSTOM_CABINET')?></label><br>
    </form>
        <div class="adm-detail-content-item-block wb_table">
            <?
            $timestamp = '';
            $res = CEventLog::GetList(Array("ID" => "DESC"), array('AUDIT_TYPE_ID'=>'UPLOAD_STOCK', 'ITEM_ID'=>$cabinet), array("nTopCount"=>1));
            if( $arRes = $res->fetch()){
                echo GetMessage("WB_MAXYSS_LAST_STOCK_UPLOAD") .$timestamp = $arRes['TIMESTAMP_X']. '<br><br>';
            }

            if($timestamp !='') {
                $stmp = MakeTimeStamp($arRes['TIMESTAMP_X'], "DD.MM.YYYY HH:MI:SS");

                $startTime = date("d.m.Y h:i:s", $stmp - 3);
                $endTime = date("d.m.Y h:i:s", $stmp+1);
                $res = CEventLog::GetList(Array("ID" => "DESC"), array('AUDIT_TYPE_ID' => 'UPLOAD_STOCK_NOTE'/*, "TIMESTAMP_X_1" => $startTime, "TIMESTAMP_X_2" => $endTime*/), array("nTopCount" => 1));
                if ($arRes = $res->fetch()) {
                    $error = CUtil::JsObjectToPhp($arRes['DESCRIPTION']);

                    if ($error['data']['errors']) {
                        echo GetMessage('WB_MAXYSS_STOCK_LIST_ERROR').'('.$arRes['TIMESTAMP_X'].'):  <br>';
                        foreach ($error['data']['errors'] as $err)
                            echo implode(' - ', $err) . '; ';
                    }
                }
            }
            ?>
            <table style="margin-top: 10px" class="adm-detail-content-table edit-table" id="tab1_edit_table">


                <?
                if(!empty($arElem)) {
                    ?>
                    <thead class="main-grid-header">
                    <tr style="font-weight: bold; height: 40px">
                        <td>n/n</td><td style="text-align: center"><?=GetMessage('WB_MAXYSS_PRODUCT')?></td><td>nmId</td><td><?=GetMessage('WB_MAXYSS_BAR_CODE')?></td><td><?=GetMessage('WB_MAXYSS_PRICE')?></td><td><?=GetMessage('WB_MAXYSS_DISCOUNT')?></td><td><?=GetMessage('WB_MAXYSS_STOCK')?></td>
                    </tr>
                    </thead>
                    <tbody id="result_next_element">
                        <?
                        $key_item = 0;
                        foreach ($arElem as $item) {
                            $key_item++;
                            ?>
                            <tr <?echo ($item['DOUBLE'])? ' title="'.GetMessage('WB_MAXYSS_DOUBLE_NMID').'"' : '';?> class="main-grid-row main-grid-row-body" style="border-bottom: 1px; <?echo ($item['DOUBLE'])? ' background-color: #fddfdf' : '';?>" >
                                <td><?echo $key_item?></td>
                                <td style="width: 30%;" class="adm-detail-content-cell-r"><a target="_blank" href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=<?= $item["IBLOCK_ID"] ?>&type=<?= $item["IBLOCK_TYPE_ID"] ?>&lang=ru&ID=<?= $item["ID"] ?>&find_section_section=-1&WF=Y">ID <?= $item["ID"] ?> <?= $item["NAME"] ?></a>
                                </td>
                                <td class="adm-detail-content-cell-r"><?= $item["NMID"] ?></td>
                                <td class="adm-detail-content-cell-r"><?= $item["PROPERTY_" . strtoupper($arSettings['SHKOD']) . "_VALUE"]; ?></td>
                                <td class="adm-detail-content-cell-r"><?= $Prices[$item["NMID"]]['price'] ?></td>
                                <td class="adm-detail-content-cell-r"><?= $Prices[$item["NMID"]]['discount'] ?></td>
                                <td class="adm-detail-content-cell-r"><?
                                    if(is_array($arStocksBarcodeKey[$item["PROPERTY_" . strtoupper($arSettings['SHKOD']) . "_VALUE"]]))
                                    foreach ( $arStocksBarcodeKey[$item["PROPERTY_" . strtoupper($arSettings['SHKOD']) . "_VALUE"]] as $st){
                                        echo "<b>".$st['amount']."</b> &nbsp; &nbsp; &nbsp;(".$st['wh_name'].")<br>";
                                    }
                                    ?></td>
                            </tr>
                        <?
                        }
                        ?>
                        <tr><td style="border: 0px" colspan="7"><input type="button" onclick="get_next_elements('<?=$next_id?>', '<?=$key_item?>', this)" value="<?=GetMessage('WB_MAXYSS_MORE_REC')?>"></td></tr>
                    </tbody>

                    <?
                }else {
                    echo GetMessage("WB_MAXYSS_NOT_SINC_PRODUCT");
                }?>
            </table>
        </div>
    <script>
        function get_next_elements(id, key_item, but_elem) {
            var but = but_elem;
            var wait_data = BX.showWait(but);
            BX.ajax({
                method: 'POST',
                dataType: 'html',
                timeout: 30,
                url: '/bitrix/tools/maxyss.wb/ajax.php',
                data: {
                    action: 'stock_realy',
                    ID: id,
                    LK_WB: '<?=$cabinet?>',
                    key_item: key_item
                },
                onsuccess: function (data) {
                    // console.log(data);
                    BX.closeWait('wb_data', wait_data);
                    $('#result_next_element').append(data);
                    but_elem.remove();
                },
                onfailure: function () {
                    new Error("Request failed");
                }
            });
        }
    </script>

<?}else
    die();
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');?>
