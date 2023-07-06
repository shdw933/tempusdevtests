<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('WB_MAXYSS_MENU_SUPPLIES'));
CJSCore::Init('jquery');

global $APPLICATION;
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency;

\Bitrix\Main\UI\Extension::load("ui.hint"); ?>
    <script type="text/javascript">
        BX.ready(function () {
            BX.UI.Hint.init(BX('wb_conainer'));
        })
    </script>
<?
if (CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 2)
    echo '<font style="color:red;">' . GetMessage('MAXYSS_WB_MODULE_TRIAL_2') . '</font>';
if (CModule::IncludeModuleEx(MAXYSS_WB_NAME) == 3)
    echo '<font style="color:red;">' . GetMessage('MAXYSS_WB_MODULE_TRIAL_3') . '</font>';


if (Loader::includeModule('sale') && Loader::includeModule('iblock') && CModule::IncludeModule(MAXYSS_WB_NAME) && ($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R")) {
    if($_REQUEST['LK_WB']) $cabinet = $_REQUEST['LK_WB']; else $cabinet = 'DEFAULT';

    $supplies = new CMaxyssWbSupplies($cabinet);
    $arSupplies = array_reverse($supplies->getSupplies());
    $arLk = CMaxyssWb::get_setting_wb('AUTHORIZATION');
    ?>
    <style>
        .cell_center{
            text-align: center;
            padding: 5px 0 7px 4px;
        }
        .supplie_border{
            border-bottom: 1px solid gray;
        }
    </style>
    <form id="lk_form" method="get" action="<?=MAXYSS_WB_NAME?>_supplies.php?lang=<?=LANGUAGE_ID?>" style="margin-bottom: 10px">
        <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
        <select name="LK_WB" onchange="$('#lk_form').submit();">
            <?foreach ($arLk as $key=>$auth){?><option <?echo ($cabinet == $key)? 'selected="selected"' : '';?> value="<?=$key?>"><?=$key?></option><?}?>
        </select><label style="margin-left: 10px"><?=GetMessage('MAXYSS_WB_CUSTOM_CABINET')?></label><br><br>
        <?echo GetMessage('WB_MAXYSS_SUPPLIE_MESSAGE')?><br><br>
        <div class="adm-detail-content-item-block wb_conainer">
            <?$supplies->ShowErrors();?>
            <table class="adm-detail-content-table edit-table" id="tab1_edit_table">
                <tbody>
                <??><tr>
                    <td colspan="6">
                        <div class="adm-detail-content-btns">
                            <input onclick="add_supplie();" type="button" value="<?=GetMessage('WB_MAXYSS_CREATE_SUPPLIE_BUTTON')?>"/>
                            <label for="name_supplie"><?=GetMessage('WB_MAXYSS_SUPPLIE_NEW_NAME')?></label><input id="name_supplie" type="text" value=""/>
                        </div>
                    </td>
                </tr><??>
                <?
                if (!empty($arSupplies)) {
                    ?><tr class="heading">
                        <td><?=GetMessage('WB_MAXYSS_NAME_COLUMN')?></td>
                        <td><?=GetMessage('WB_MAXYSS_POSTING_COLUMN')?></td>
                        <td><?=GetMessage('WB_MAXYSS_SUPPLIE_ID_COLUMN')?></td>
                        <td><?=GetMessage('WB_MAXYSS_SUPPLIE_DATE_CREATE')?></td>
                        <td><?=GetMessage('WB_MAXYSS_SUPPLIE_DATE_CLOSE')?></td>
                        <td><?=GetMessage('WB_MAXYSS_SUPPLIE_RUNS')?></td>
                    </tr><?
                    $count_sup = 0;
                    $db_props = CSaleOrderProps::GetList(
                        array("SORT" => "ASC"),
                        array(
                            "CODE" => 'MAXYSS_WB_NUMBER',
                            "PERSON_TYPE_ID" => $supplies->settings['PERSON_TYPE'],
                        ),
                        false,
                        false,
                        array()
                    );
                    if ($props = $db_props->Fetch()) {
                        $propid = $props['ID'];
                    }


                    foreach ($arSupplies as $supply) {
                        $orders = array(); $order_wb = array();
                        $count_sup++;
                        if($count_sup > 20) break;
                        $arOrdes = array();
                        $arOrdes = $supplies->getSupplieOrders($supply['id']);

                        if(!empty($arOrdes['orders']) && $propid > 0) {
                            foreach ($arOrdes['orders'] as &$order) {
                                if ($order["id"]) {
                                    $order_wb[] = $order["id"];
                                    $orders[$order["id"]] = $order;
                                }
                            }
                            if(count($order_wb)>0) {
                                $getListParams['filter'] = array('=PROPS.VALUE' => $order_wb, 'PROPS.ORDER_PROPS_ID' => $propid);

                                $getListParams['select'] = array(
                                    'ID',
                                    'PROPS.VALUE'
                                );

                                $getListParams['runtime'] = array(
                                    new \Bitrix\Main\Entity\ReferenceField(
                                        'PROPS',
                                        '\Bitrix\Sale\Internals\OrderPropsValueTable',
                                        array(
                                            '=this.ID' => 'ref.ORDER_ID',
                                        ),
                                        array(
                                            "join_type" => 'inner'
                                        )
                                    )
                                );
                                $getListParams['order'] = array();
                                $res = \Bitrix\Sale\Order::getList($getListParams);
                                $rows = array();
                                while ($row = $res->fetch()) {
                                    $orders[$row['SALE_INTERNALS_ORDER_PROPS_VALUE']]['bx_order'] = $row;
                                }
                                $supply['orders'] = $orders;
                            }

                        }
//                        echo '<pre>', print_r($supply), '</pre>' ;


                        ?>
                        <tr>
                            <td class="adm-detail-content-cell-r supplie_border"><?echo $supply['name']?></td>
                            <td class="cell_center supplie_border"><?=count($arOrdes['orders'])?></td>
                            <td class="cell_center supplie_border"><?echo $supply['id']?></td>
                            <td class="cell_center supplie_border"><?echo str_replace(array('T','Z'), ' ', $supply['createdAt']) ?></td>
                            <td class="cell_center supplie_border"><?echo str_replace(array('T','Z'), ' ', $supply['closedAt']) ?></td>
                            <td class="cell_center supplie_border">
                                <?if(!$supply['done'] && ($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R")){
                                    ?>
                                    <?if(count($arOrdes['orders'])<=0){?>
                                        <input type="button" onclick="delete_supplie('<?=$supply['id']?>')" value="<?echo GetMessage('WB_MAXYSS_DELETE_SUPPLIE_BUTTON')?>"/>
                                    <?}else{?>
                                        <input type="button" onclick="deliver_supplie('<?=$supply['id']?>')" value="<?echo GetMessage('WB_MAXYSS_CLOSE_SUPPLIE_BUTTON')?>"/>
                                    <?}?>
                                <?}?>
                            </td>
                        </tr>
                        <?
//                        echo '<pre>', print_r($arOrdes), '</pre>' ;
//                        if(!empty($arOrdes['orders'])){
//                            ?>
<!--                            <tr>-->
<!--                                <td colspan="2"> <b>Список заказов поставки</b> </td>-->
<!--                            </tr>-->
<!--                            <tr style="background-color: #e0e8ea">-->
<!--                                <td style="padding: 5px">wb.ru</td><td  style="padding: 5px">Битрикс</td>-->
<!--                            </tr>-->
<!--                            --><?//
//                            foreach ($arOrdes['orders'] as $order){
//                                ?>
<!--                                <tr>-->
<!--                                    <td>--><?//echo '№ '.$order["orderId"].' со склада '.$order["storeId"];?><!--</td>-->
<!--                                    <td>--><?//echo '№ '.$order["bx_order"]['ID']?><!--</td>-->
<!--                                </tr>-->
<!--                                --><?//
//                            }
//                        }
                    }
                    ?>
                <?
                }
                ?>
                </tbody>
            </table>
        </div>
    </form>

    <script type="text/javascript">
        // BX.ready(function () {

            //function orderSelect() {
            //    jsUtils.OpenWindow('/bitrix/tools/maxyss.wb/orders.php?lang=<?//=LANG?>//',900,700);
            //};
            //
            //// changeSelectShipment();
            //function popupOrderSelect(evt){
            //    evt.preventDefault();
            //    orderSelect();
            //};

            // document.querySelector('#check_orders').addEventListener('click', popupOrderSelect, false);

            function add_supplie() {
                var add_supplie = confirm(BX.message("WB_MAXYSS_ADD_SUPPLIE"));
                var name = $('#name_supplie').val();
                if (add_supplie) {
                    var wait_data_card = BX.showWait('wb_data');
                    $.ajax({
                        type: 'GET',
                        url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                        data: {
                            cabinet: '<?=$cabinet?>',
                            action: 'add_supplie',
                            name_supplie: name
                        },
                        success: function (data) {
                            BX.closeWait('wb_data', wait_data_card);

                            var IS_JSON = true;
                            var obj;
                            try {
                                obj = $.parseJSON(data);
                            }
                            catch (err) {
                                IS_JSON = false;
                            }
                            if (IS_JSON) {
                                if (obj.error) {
                                    alert(obj.error);
                                }
                                if (obj.supplyId) {
                                    alert(BX.message("WB_MAXYSS_ADD_SUPPLIE_SUCCESS"));
                                    document.location.reload();
                                }
                            } else {
                                alert('not valid json');
                            }
                        },
                        error: function (xhr, str) {
                            alert('Error: ' + xhr.responseCode);
                        }
                    });
                }
            }
            function delete_supplie(id) {
                var delete_supplie = confirm(BX.message("WB_MAXYSS_DELETE_SUPPLIE"));
                if (delete_supplie) {
                    var wait_data_card = BX.showWait('wb_data');
                    $.ajax({
                        type: 'GET',
                        url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                        data: {
                            cabinet: '<?=$cabinet?>',
                            action: 'delete_supplie',
                            id_supplie: id
                        },
                        success: function (data) {
                            BX.closeWait('wb_data', wait_data_card);

                            var IS_JSON = true;
                            var obj;
                            try {
                                obj = $.parseJSON(data);
                            }
                            catch (err) {
                                IS_JSON = false;
                            }
                            if (IS_JSON) {
                                if (obj.error) {
                                    alert(obj.errorText);
                                }
                                if (obj.success) {
                                    alert('Поставка удалена');
                                    document.location.reload();
                                    // window.location.href = "/bitrix/admin/maxyss.wb_supplies.php";
                                }
                            } else {
                                alert('not valid json');
                            }
                        },
                        error: function (xhr, str) {
                            alert('Error: ' + xhr.responseCode);
                        }
                    });
                }
            }
            function deliver_supplie(id) {
            var deliver_supplie = confirm(BX.message("WB_MAXYSS_CLOSE_SUPPLIE_CONFIRM"));
            if (deliver_supplie) {
                var wait_data_card = BX.showWait('wb_data');
                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                    data: {
                        cabinet: '<?=$cabinet?>',
                        action: 'deliver_supplie',
                        id_supplie: id
                    },
                    success: function (data) {
                        BX.closeWait('wb_data', wait_data_card);

                        var IS_JSON = true;
                        var obj;
                        try {
                            obj = $.parseJSON(data);
                        }
                        catch (err) {
                            IS_JSON = false;
                        }
                        if (IS_JSON) {
                            if (obj.error) {
                                alert(obj.error);
                            }
                            if (obj.success) {
                                alert('Поставка передана в доставку');
                                document.location.reload();
                            }
                        } else {
                            alert('not valid json');
                        }
                    },
                    error: function (xhr, str) {
                        alert('Error: ' + xhr.responseCode);
                    }
                });
            }
        }
        // });
    </script>
<?
}