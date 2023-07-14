<?

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem,
    Bitrix\Highloadblock as HL,
    Bitrix\Main\Entity,
    Bitrix\Main\Web\Json;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Bitrix\Main\Loader::includeModule("main");
Bitrix\Main\Loader::includeModule("sale");
Bitrix\Main\Loader::includeModule("catalog");
Bitrix\Main\Loader::includeModule("iblock");

global $APPLICATION;
global  $USER;
if(!is_object($USER))
    $USER = new CUser;
//CUtil::InitJSCore(array('window'));
if (defined("ADMIN_SECTION") || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || strpos($GLOBALS["APPLICATION"]->GetCurPage(),  "/shop/orders/") !== false)
    CJSCore::Init(array("jquery"));


if (defined("ADMIN_SECTION")) {
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/pdfmake.js");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/vfs.js");

}
if ($GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/"){
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/pdfmake.js");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/vfs.js");
}


IncludeModuleLangFile(__FILE__);
define("MAXYSS_WB_NAME", "maxyss.wb");

CJSCore::RegisterExt('maxyss_wb', array(
    'js' => '/bitrix/tools/'.MAXYSS_WB_NAME.'/script.js',
    'css' => '/bitrix/tools/'.MAXYSS_WB_NAME.'/style.css',
    'lang' => '/bitrix/modules/'.MAXYSS_WB_NAME.'/lang/'.LANGUAGE_ID.'/include.php',
//    'rel' => array('popup', 'ajax', 'fx', 'ls', 'date', 'json')
));
if (defined("ADMIN_SECTION") || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || strpos($GLOBALS["APPLICATION"]->GetCurPage(),  "/shop/orders/") !== false)
    CJSCore::Init(array('maxyss_wb'));

define('WB_BASE_URL', 'https://suppliers-api.wildberries.ru');
define('AUTHORIZATION', unserialize(Option::get(MAXYSS_WB_NAME, "AUTHORIZATION", ""))['DEFAULT']);
define('FILE_TYPE_STIKER', '.svg');


Bitrix\Main\Loader::registerAutoLoadClasses(
    MAXYSS_WB_NAME,
    array(
        "RestClientException" => 'classes/restclientexception.php',
        "RestClient" => 'classes/restclient.php',
        "CMaxyssWbproductTab" => 'classes/CMaxyssWbproductTab.php',
        "CMaxyssWbPrice" => 'classes/CMaxyssWbprice.php',
        "CRestQueryWB" => 'classes/maxyss_wb.php',
        "CHelpMaxyssWB" => 'classes/maxyss_wb.php',
        "CMaxyssWbEvents" => 'classes/maxyss_wb.php',
        "CAddinMaxyssWB" => 'classes/CAddinMaxyssWB.php',
        "FilterCustomWB" => 'classes/CAddinMaxyssWB.php',
        "CMaxyssWbSupplies" => 'classes/CMaxyssWbSupplies.php',
    )
);

function wb_is_curl_installed() {
    if (in_array ('curl', get_loaded_extensions())) {
        return true;
    }
    else {
        return false;
    }
}


class CMaxyssWb{
    public static function settings_wb($cabinet = '')
    {
        $arSettings = array();
        $arSettings['LK_WB_DATA'] = unserialize(Option::get(MAXYSS_WB_NAME, "LK_WB_DATA", ''));
        $arSettings['CALLBACK_BX'] = unserialize(Option::get(MAXYSS_WB_NAME, "CALLBACK_BX", "N"));
        $arSettings['STIKER_WIDTH'] = unserialize(Option::get(MAXYSS_WB_NAME, "STIKER_WIDTH", "N"));
        $arSettings['ACTIVE_ON'] = unserialize(Option::get(MAXYSS_WB_NAME, "ACTIVE_ON", "N"));
        $arSettings['IBLOCK_TYPE'] = unserialize(Option::get(MAXYSS_WB_NAME, "IBLOCK_TYPE", ""));
        $arSettings['IBLOCK_ID'] = unserialize(Option::get(MAXYSS_WB_NAME, "IBLOCK_ID", ""));
        $arSettings['DESCRIPTION'] = unserialize(Option::get(MAXYSS_WB_NAME, "DESCRIPTION", "DETAIL_TEXT"));
        $arSettings['BRAND'] = unserialize(Option::get(MAXYSS_WB_NAME, "BRAND_PROP", ""));
        $arSettings['SHKOD'] = unserialize(Option::get(MAXYSS_WB_NAME, "SHKOD", ""));
        $arSettings['ARTICLE'] = unserialize(Option::get(MAXYSS_WB_NAME, "ARTICLE", ""));
        $arSettings['ARTICLE_LINK'] = unserialize(Option::get(MAXYSS_WB_NAME, "ARTICLE_LINK", ""));
        $arSettings['NAME_PRODUCT'] = unserialize(Option::get(MAXYSS_WB_NAME, "NAME_PRODUCT", ""));
        $arSettings['FILTER_PROP'] = unserialize(Option::get(MAXYSS_WB_NAME, "FILTER_PROP", ""));
        $arSettings['FILTER_PROP_ID'] = unserialize(Option::get(MAXYSS_WB_NAME, "FILTER_PROP_ID", ""));
        $arSettings['BASE_PICTURE'] = unserialize(Option::get(MAXYSS_WB_NAME, "BASE_PICTURE", "DETAIL_PICTURE"));
        $arSettings['MORE_PICTURE'] = unserialize(Option::get(MAXYSS_WB_NAME, "MORE_PICTURE", ""));
        $arSettings['SERVER_NAME'] = unserialize(Option::get(MAXYSS_WB_NAME, "SERVER_NAME", $_SERVER["HTTP_HOST"]));
        $arSettings['SKLAD'] = unserialize(Option::get(MAXYSS_WB_NAME, "SKLAD", ''));
        $arSettings['LAND'] = unserialize(Option::get(MAXYSS_WB_NAME, "LAND",''));

        $arSettings['BRAND_PROP'] = unserialize(Option::get(MAXYSS_WB_NAME, "BRAND_PROP", ""));
        $arSettings['CANCEL_TRIGER'] = unserialize(Option::get(MAXYSS_WB_NAME, "CANCEL_TRIGER", ""));
        $arSettings['CLIENT_RECEIVED_TRIGER'] = unserialize(Option::get(MAXYSS_WB_NAME, "CLIENT_RECEIVED_TRIGER", ""));
        $arSettings['LOG_ON'] = unserialize(Option::get(MAXYSS_WB_NAME, "LOG_ON", ""));
        $arSettings['PERIOD'] = unserialize(Option::get(MAXYSS_WB_NAME, "PERIOD", ""));
        $arSettings['SKLAD_WB_TRIGER'] = unserialize(Option::get(MAXYSS_WB_NAME, "SKLAD_WB_TRIGER", ""));
        $arSettings['UUID'] = unserialize(Option::get(MAXYSS_WB_NAME, "UUID", ""));
        $arSettings['AUTHORIZATION'] = unserialize(Option::get(MAXYSS_WB_NAME, "AUTHORIZATION", ""));


        $arSettings['PRICE_TYPE'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_TYPE", ""));
        $arSettings['PRICE_MAX_MIN'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_MAX_MIN", ""));
        $arSettings['PRICE_PROP'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_PROP", ""));
        $arSettings['PRICE_TYPE_PROP'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_TYPE_PROP", ""));
        $arSettings['PRICE_TYPE_NO_DISCOUNT'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_TYPE_NO_DISCOUNT", ""));
        $arSettings['PROMOCODES_ON'] = unserialize(Option::get(MAXYSS_WB_NAME, "PROMOCODES_ON", "N"));
        $arSettings['DISCOUNTS_ON'] = unserialize(Option::get(MAXYSS_WB_NAME, "DISCOUNTS_ON", "N"));
        $arSettings['TP_AS_PRODUCT'] = unserialize(Option::get(MAXYSS_WB_NAME, "TP_AS_PRODUCT", "N"));
        $arSettings['PRICE_TYPE_FORMULA'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_TYPE_FORMULA", "N"));
        $arSettings['PRICE_TYPE_FORMULA_ACTION'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_TYPE_FORMULA_ACTION", "N"));
        $arSettings['PRICE_ON'] = unserialize(Option::get(MAXYSS_WB_NAME, "PRICE_ON", ""));

        $rsSites = CSite::GetList($by="sort", $order="desc", Array("DEFAULT" => "Y"));
        if ($arSite = $rsSites->Fetch())
        {
            $arSettings['SITE'] = $arSite["LID"];
        }
        $arSettings['SKLAD_ID'] = unserialize(Option::get(MAXYSS_WB_NAME, "SKLAD_ID", ""));
        $arSettings['LIMIT_WAREHOUSE'] = unserialize(Option::get(MAXYSS_WB_NAME, "LIMIT_WAREHOUSE", ""));
        $arSettings['WAREHOUSES'] = unserialize(Option::get(MAXYSS_WB_NAME, "WAREHOUSES", ""));
        $arSettings['LIMIT_WAREHOUSE_DBS'] = unserialize(Option::get(MAXYSS_WB_NAME, "LIMIT_WAREHOUSE_DBS", ""));
        $arSettings['DEACTIVATE_WH'] = unserialize(Option::get(MAXYSS_WB_NAME, "DEACTIVATE_WH", ""));
        $arSettings['KGT_WH'] = unserialize(Option::get(MAXYSS_WB_NAME, "KGT_WH", ""));

        $arSettings['ACTIVE_ORDER_ON'] = unserialize(Option::get(MAXYSS_WB_NAME, "ACTIVE_ORDER_ON", ''));
        $arSettings['PERIOD_ORDER'] = unserialize(Option::get(MAXYSS_WB_NAME, "PERIOD_ORDER", "600"));
        $arSettings['COUNT_ORDER'] = unserialize(Option::get(MAXYSS_WB_NAME, "COUNT_ORDER", "500"));
        $arSettings['VALUTA_ORDER'] = unserialize(Option::get(MAXYSS_WB_NAME, "VALUTA_ORDER", ""));
        $arSettings['PERSON_TYPE'] = unserialize(Option::get(MAXYSS_WB_NAME, "PERSON_TYPE",  ""));
        $arSettings['DELIVERY_SERVICE'] = unserialize(Option::get(MAXYSS_WB_NAME, "DELIVERY_SERVICE", ""));
        $arSettings['PAYSYSTEM'] = unserialize(Option::get(MAXYSS_WB_NAME, "PAYSYSTEM", ""));
        $arSettings['USER_DEFAULTE'] = unserialize(Option::get(MAXYSS_WB_NAME, "USER_DEFAULTE", ""));

        // статусы
        $arSettings['NEW'] = unserialize(Option::get(MAXYSS_WB_NAME, "NEW", "N"));
        $arSettings['CANCEL'] = unserialize(Option::get(MAXYSS_WB_NAME, "CANCEL", "N"));
        $arSettings['CLIENT_RECEIVED'] = unserialize(Option::get(MAXYSS_WB_NAME, "CLIENT_RECEIVED", "N"));
        $arSettings['CLIENT_RETURN'] = unserialize(Option::get(MAXYSS_WB_NAME, "CLIENT_RETURN", "N"));
        $arSettings['SKLAD_WB'] = unserialize(Option::get(MAXYSS_WB_NAME, "SKLAD_WB", "N"));
        $arSettings['TRANSIT'] = unserialize(Option::get(MAXYSS_WB_NAME, "TRANSIT", "N"));
        $arSettings['RETURN_PRODUCT'] = unserialize(Option::get(MAXYSS_WB_NAME, "RETURN_PRODUCT", "N"));
        $arSettings['TRIGGERS'] = unserialize(Option::get(MAXYSS_WB_NAME, 'TRIGGERS', ''));
        $arSettings['CUSTOM_FILTER'] = unserialize(Option::get(MAXYSS_WB_NAME, 'CUSTOM_FILTER', ''));
        if($cabinet != '') {

            foreach ($arSettings as $key => &$sett) {
                if (is_array($sett) && $key != 'LK_WB_DATA') {
                    $sett = $sett[$cabinet];
                }
            }
            $arSettings['LK'] = $cabinet;
        }
        $arSettings['STATUS_BY'] = array(
            '0' => $arSettings['NEW'],
            '1' => $arSettings['CANCEL'],
            '2' => $arSettings['CLIENT_RECEIVED'],
            '3' => $arSettings['CLIENT_RETURN'],
            '4' => $arSettings['SKLAD_WB'],
            '5' => $arSettings['TRANSIT'],
            '6' => $arSettings['RETURN_PRODUCT']
        );


        return $arSettings;
    }
    public static function get_setting_wb($setting = "", $cabinet = ""){
        if($setting !='') {
            $settings = self::settings_wb($cabinet);
            $res = $settings[$setting];
        }
        else
        {
            $res =  false;
        }
        return $res;
    }

    public static function get_setting_wb_for_auth($setting = "", $Authorization = false){
        if($setting !='' && $Authorization !== false) {
            $lk_wb_data = array();
            $lk_wb_data = unserialize(Option::get(MAXYSS_WB_NAME, "LK_WB_DATA"));
            if(!empty($lk_wb_data)){
                foreach ($lk_wb_data as $cabinet => $data){
                    if($data['authorization'] == $Authorization) $res = self::get_setting_wb($setting, $cabinet);
                }
            }
        }
        else
        {
            $res =  false;
        }
        return $res;
    }
    static function bck_wb(){
        $rsSites = CSite::GetList($by="sort", $order="desc", Array("DEFAULT" => "Y"));
        if ($arSite = $rsSites->Fetch())
        {
            $host = $arSite["SERVER_NAME"];
        }
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");
        $arInfo = array();
        $arInfo['key'] = CUpdateClientPartner::GetLicenseKey();
        $arInfo['host'] = $host;
        $data_string = array(
            'info' => $arInfo
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $arResult = CRestQueryWB::rest_back($base_url = 'https://maxyss.ru', $data_string, "/wb/v1/");
        return $arResult;
    }
    public static function OzonOnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu){
        if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) < "R")
            return;

        $aMenu = array(
            "parent_menu" => "global_menu_settings",
            "section" => MAXYSS_WB_NAME,
            "sort" => 100,
            "text" => GetMessage("WB_MAXYSS_MENU"),
            "title" => GetMessage("WB_MAXYSS_TITLE"),
            "url" => '',//MAXYSS_WB_NAME."_ozon_maxyss.php?lang=".LANGUAGE_ID,
            "items_id" => "menu_wb_maxyss",
            "more_url" => array(
                MAXYSS_WB_NAME."_wb_maxyss_general.php?mid=".MAXYSS_WB_NAME."&amp;lang=".LANGUAGE_ID,
                MAXYSS_WB_NAME."_order_wb_maxyss.php?mid=".MAXYSS_WB_NAME."&amp;lang=".LANGUAGE_ID,
                MAXYSS_WB_NAME."_stock_realy_wb_maxyss.php?mid=".MAXYSS_WB_NAME."&amp;lang=".LANGUAGE_ID,
                MAXYSS_WB_NAME."_supplies.php?mid=".MAXYSS_WB_NAME."&amp;lang=".LANGUAGE_ID,
                MAXYSS_WB_NAME."_right.php?mid=".MAXYSS_WB_NAME."&amp;lang=".LANGUAGE_ID,
            ),
            "items" => array(
                array(
                    "text" => GetMessage("WB_MAXYSS_MENU_GENERAL"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_WB_NAME."_wb_maxyss_general.php?lang=".LANGUAGE_ID,
                    "more_url" => array(
                        MAXYSS_WB_NAME."_wb_maxyss_general.php?lang=".LANGUAGE_ID,
                        MAXYSS_WB_NAME."_wb_maxyss_general.php",
                    ),
                    "title" => GetMessage("WB_MAXYSS_MENU_GENERAL"),
                    'module_id' => MAXYSS_WB_NAME,
                    'items_id' => 'general_wb_param',
                ),
                array(
                    "text" => GetMessage("WB_MAXYSS_MENU_ORDER"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_WB_NAME."_order_wb_maxyss.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
                    "title" =>  GetMessage("WB_MAXYSS_MENU_ORDER"),
                    'module_id' => MAXYSS_WB_NAME,
                    'items_id' => 'order_wb_param',
                ),
                array(
                    "text" => GetMessage("WB_MAXYSS_MENU_STOCK"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_WB_NAME."_stock_realy_wb_maxyss.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
                    "title" =>  GetMessage("WB_MAXYSS_MENU_STOCK"),
                    'module_id' => MAXYSS_WB_NAME,
                    'items_id' => 'stock_realy_wb_maxyss',
                ),
                array(
                    "text" => GetMessage("WB_MAXYSS_MENU_SUPPLIES"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_WB_NAME."_supplies.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
                    "title" =>  GetMessage("WB_MAXYSS_MENU_SUPPLIES"),
                    'module_id' => MAXYSS_WB_NAME,
                    'items_id' => 'supplies_wb_maxyss',
                ),
                array(
                    "text" => GetMessage("WB_MAXYSS_MENU_RIGHT"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_WB_NAME."_right.php?lang=".LANGUAGE_ID,
                    "more_url" => array(
                        MAXYSS_WB_NAME."_wb_maxyss_general.php?lang=".LANGUAGE_ID,
                        MAXYSS_WB_NAME."_wb_maxyss_general.php",
                    ),
                    "title" =>  GetMessage("WB_MAXYSS_MENU_RIGHT"),
                    'module_id' => MAXYSS_WB_NAME,
                    'items_id' => 'right_wb_maxyss',
                ),
            )
        );

        foreach($aModuleMenu as $key => $menu) :
            if ($menu["parent_menu"] == "global_menu_settings" && $menu['items_id'] == 'menu_system') :
                foreach ($menu['items'] as $k=>$item){
                    if($item['items_id'] == 'menu_module_settings')
                        foreach ($aModuleMenu[$key]["items"][$k]['items'] as $key_i => $i){
                            if(strpos($i['url'], 'maxyss.wb')){
                                $aModuleMenu[$key]["items"][$k]['items'][$key_i] = $aMenu;
                            }
                        }
                }
            endif;
        endforeach;
    }
    public static function GetLicense(){
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");
        $arInfo = array();
        $arInfo['key'] = CUpdateClientPartner::GetLicenseKey();
        return $arInfo;
    }
    public static function deepIconv($sbj, $in = 'UTF-8', $out='windows-1251//IGNORE'){
        if (is_array($sbj) || is_object($sbj)){
            foreach ($sbj as &$val){
                $val= self::deepIconv($val);
            }
            return $sbj;
        }else{
            return iconv($in, $out, $sbj);
        }
    }
    public static function get_price($type, $prop, $type_prop, $no_discount, $product_id, $lid, $formula, $formula_action){

        $formula = floatval(str_replace(',', '.', $formula));
        if($prop=="Y"){
            // for property
            $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_".$type_prop);
            $arFilter = Array("ID"=>$product_id);
            $res = CIBlockElement::GetList(Array('ID'=>'asc'), $arFilter, false, false, $arSelect);

            if($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();
                if($formula != '' && $formula_action != 'NOT'){
                    switch ($formula_action){

                        case 'ADD':
                            $result = $arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] + $formula;
                            break;

                       case 'MULTIPLY':
                            $result = $arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] * $formula;
                            break;

                       case 'DIVIDE':
                            $result = $arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] / $formula;
                            break;

                       case 'SUBTRACT':
                            $result = $arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] - $formula;
                            break;

                        default:
                            break;
                    }
                }
                else
                {
                    $result = $arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'];
                }

            }else{
                $result = 0;
            }

        }else{
            // for price

            $selectedPriceType = 0;
            if (!empty($type)) {
                $price = (int)$type;
                if ($price > 0) {
                    $priceIterator = Catalog\GroupAccessTable::getList([
                        'select' => ['CATALOG_GROUP_ID'],
                        'filter' => ['=CATALOG_GROUP_ID' => $price]
                    ]);
                    $priceType = $priceIterator->fetch();
                    if (empty($priceType))
                        $arErrors[] = GetMessage('WB_MAXYSS_ERROR_PRICE');
                    else
                        $selectedPriceType = $price;
                    unset($priceType, $priceIterator);
                } else {
                    $arErrors[] = GetMessage('WB_MAXYSS_ERROR_PRICE');
                }
            }


            if($selectedPriceType > 0) {
                $priceFilter = [
                    '@PRODUCT_ID' => $product_id,
                    [
                        'LOGIC' => 'OR',
                        '<=QUANTITY_FROM' => 1,
                        '=QUANTITY_FROM' => null
                    ],
                    [
                        'LOGIC' => 'OR',
                        '>=QUANTITY_TO' => 1,
                        '=QUANTITY_TO' => null
                    ]
                ];
                if ($selectedPriceType > 0)
                    $priceFilter['=CATALOG_GROUP_ID'] = $selectedPriceType;

                $iterator = Catalog\PriceTable::getList([
                    'select' => ['ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY'],
                    'filter' => $priceFilter
                ]);
                $offerLinks = array();
                while ($price = $iterator->fetch()) {
                    $id = (int)$price['PRODUCT_ID'];
                    $priceTypeId = (int)$price['CATALOG_GROUP_ID'];
                    $offerLinks[$id]['PRICES'][$priceTypeId] = $price;
                    unset($priceTypeId, $id);
                }

                foreach ($offerLinks as $key => $row) {
                    $arPrice = CCatalogProduct::GetOptimalPrice(
                        $key,
                        1,
                        array(2),
                        'N',
                        $row['PRICES'],
                        $lid,
                        array()
                    );
                }

                if ($no_discount == "Y") {
                    if($formula != '' && $formula_action != 'NOT'){
                        switch ($formula_action){
                            case 'ADD':
                                $result = round($arPrice['RESULT_PRICE']['BASE_PRICE'] + $formula, 0);
                                break;

                            case 'MULTIPLY':
                                $result = round($arPrice['RESULT_PRICE']['BASE_PRICE'] * $formula, 0);
                                break;

                            case 'DIVIDE':
                                $result = round($arPrice['RESULT_PRICE']['BASE_PRICE'] / $formula, 0);
                                break;

                            case 'SUBTRACT':
                                $result = round($arPrice['RESULT_PRICE']['BASE_PRICE'] - $formula, 0);
                                break;

                            default:
                                break;
                        }
                    }
                    else
                    {
                        $result = round($arPrice['RESULT_PRICE']['BASE_PRICE'], 0);
                    }
                } else {
                    if($formula != '' && $formula_action != 'NOT'){
                        switch ($formula_action){
                            case 'ADD':
                                $result = round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] + $formula, 0);
                                break;

                            case 'MULTIPLY':
                                $result = round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] * $formula, 0);
                                break;

                            case 'DIVIDE':
                                $result = round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] / $formula, 0);
                                break;

                            case 'SUBTRACT':
                                $result = round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] - $formula, 0);
                                break;

                            default:
                                break;
                        }
                    }
                    else
                    {
                        $result = round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'], 0);
                    }
                }
            }else{
                $result = 0;
            }
        }

        return intval($result);
    }

    public static function PrepareItem($id = 0, $cabinet = 'DEFAULT'){
        if(intval($id) <= 0){
            $item = '';
        }else{
            $arSettings = self::settings_wb($cabinet);

            $lid = $arSettings['SITE'];

            $arSelect = Array("ID", "IBLOCK_ID", "NAME", "TAGS", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_PROP_MAXYSS_WB", "PROPERTY_PROP_MAXYSS_CARDID_WB", "PROPERTY_".$arSettings['DESCRIPTION']);
            if($arSettings['BRAND'] != '') $arSelect[] = "PROPERTY_".$arSettings['BRAND'];
            if($arSettings['SHKOD'] != '') $arSelect[] = "PROPERTY_".$arSettings['SHKOD'];
            if($arSettings['ARTICLE'] != '') $arSelect[] = "PROPERTY_".$arSettings['ARTICLE'];
            if($arSettings['LAND'] != '') $arSelect[] = "PROPERTY_".$arSettings['LAND'];
            if($arSettings['KEYWORD'] != '') $arSelect[] = "PROPERTY_".$arSettings['KEYWORD'];
            $arFilter = Array('ID'=>$id, 'IBLOCK_ID'=>$arSettings['IBLOCK_ID']);
            $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, Array("nPageSize"=>10), $arSelect);
            if ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                if (strlen($arFields["~PROPERTY_PROP_MAXYSS_WB_VALUE"]) > 0) {
                    if(LANG_CHARSET == 'windows-1251')
                        $addin_card = iconv('windows-1251', 'UTF-8//IGNORE', $arFields["~PROPERTY_PROP_MAXYSS_WB_VALUE"]);
                    else
                        $addin_card = $arFields["~PROPERTY_PROP_MAXYSS_WB_VALUE"];

                    if(LANG_CHARSET == 'windows-1251') $addin_card = self::deepIconv($addin_card);

                    $addin_card_ = CUtil::JsObjectToPhp($addin_card);

                    // фотки
                    $imgPath = $_SERVER['DOCUMENT_ROOT'];

                    $img = array();
                    if ($arFields[$arSettings['BASE_PICTURE']] > 0) {
                        $img[] = $imgPath.CFile::GetPath($arFields[$arSettings['BASE_PICTURE']]);
                    }
                    if (is_array($arProps[$arSettings['MORE_PICTURE']]['VALUE'])) {
                        foreach ($arProps[$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                            $img[] = $imgPath.CFile::GetPath($photo);
                        }
                    }
                }
            }
            if (!is_array($addin_card_)) return false;

            // цвет, размер, ... для основной карточки
            $dependencies = [];
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt")) {
                $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
                foreach ($arSettings['LK_WB_DATA'] as $key=>$lk){
                    $count_depend[] = $key;
                }
                if(is_array($count_depend)) {
                    $dependencies_cab['WB_CAT_PROP'] = $dependencies['WB_CAT_PROP'][array_search($cabinet, $count_depend)];
                    $dependencies_cab['WB_SCU_PROP'] = $dependencies['WB_SCU_PROP'][array_search($cabinet, $count_depend)];
                    $dependencies = $dependencies_cab;
                }
            }
            if (!empty($dependencies))
                foreach ($dependencies["WB_CAT_PROP"] as $prop){
                    if($prop['propWB'] == 'colors') $key_prop = 'id'; else $key_prop = 'name';
                    foreach ($prop["propsList"] as $pr){
                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = ($prop['propWB']=='colors' || $prop['propWB']=='tech-sizes')? $pr['wbVal']['wb_name'] : $pr['wbVal']['wb_key'];
                    }
                }
            $arVarProp = array();
            if(!empty($prop_change)) {
                foreach ($arProps as &$prop_card) {
                    $value = '';
                    if (array_key_exists($prop_card['ID'], $prop_change)) {
                        $value = $prop_card['VALUE'] ? $prop_card['VALUE'] : $prop_card['VALUE_ENUM_ID'];
                        foreach ($prop_change[$prop_card['ID']] as $key => $wb_directory) {
                            if ($key == 'wbsizes' && $value != '') {
                                $arVarProp['wbsizes'] = $wb_directory[$value];
                            } elseif ($key == 'tech-sizes' && $value != '') {
                                $arVarProp['tech-sizes'] = $wb_directory[$value];
                            } elseif ($key == 'colors' && $value != '') {
//                                $arVarProp['colors'] = $wb_directory[$value];
                                $arVarProp['colors'] = $wb_directory[$prop_card['VALUE']]? $wb_directory[$prop_card['VALUE']] : $wb_directory[$prop_card['VALUE_ENUM_ID']];
                            }
                        }
                    }
                }
            }
            $object = $addin_card_["object"];
            unset($addin_card_["object"]);
            $addin_card = array();
            foreach ($addin_card_ as $addin){
                $addin_card[$addin['type']] = $addin;
            }

            $description = '';
            if($arSettings['DESCRIPTION'] == 'DETAIL_TEXT' ||$arSettings['DESCRIPTION'] == 'PREVIEW_TEXT')
                $description = $arFields[$arSettings['DESCRIPTION']];
            elseif($arSettings['DESCRIPTION'] != '')
                $description = (is_array($arProps[$arSettings['DESCRIPTION']]["~VALUE"]))? $arProps[$arSettings['DESCRIPTION']]["~VALUE"]["TEXT"] : $arProps[$arSettings['DESCRIPTION']]["~VALUE"];

            $name = '';
            if ($arSettings['NAME_PRODUCT'] == 'NAME')
                $name = $arFields['NAME'];
            elseif ($arSettings['NAME_PRODUCT'] != '')
                $name = (is_array($arProps[$arSettings['NAME_PRODUCT']]["~VALUE"])) ? $arProps[$arSettings['NAME_PRODUCT']]["~VALUE"]["TEXT"] : $arProps[$arSettings['NAME_PRODUCT']]["~VALUE"];



            $addin_card[GetMessage('WB_MAXYSS_DESCRIPTION')] = array(
                'type' => GetMessage('WB_MAXYSS_DESCRIPTION'),
                'params' => array(
                    array(
                        "value" => TruncateText(str_replace('&nbsp;', ' ', htmlentities(HTMLToTxt($description, $arSettings['SERVER_NAME']))), 4997)
                    )
                )
            );

//            foreach ($addin_card as $key_a => &$a) {
//                switch ($a['type']){
//                    case GetMessage('MAXYSS_WB_NAME_NAME'):
//                        if($name!='') {
//                            $a['params'] = array(
//                                array(
//                                    "value" => $name
//                                )
//                            );
//                        }
//                        break;
//                }
//            }

            if(!isset($addin_card[GetMessage('MAXYSS_WB_NAME_NAME')])) {
                $addin_card[GetMessage('MAXYSS_WB_NAME_NAME')] = array(
                    'type' => GetMessage('MAXYSS_WB_NAME_NAME'),
                    'params' => array(
                        array(
                            "value" => TruncateText($name, 57)
                        )
                    )
                );
            }

            $addin_card = array_values($addin_card);

            // Ключевые слова
            $arTags = array();
            if($arFields["TAGS"] !='') {
                $arTags = explode(',', $arFields["TAGS"]);
                if(!empty($arTags)) {
                    foreach ($arTags as $tag) {
                        $tags[] = array("value" => TruncateText(str_replace('&nbsp;', ' ', trim($tag)),47));
                    }
                    if (!empty($tags)) {
                        $addin_card[] = array(
                            'type' => GetMessage('WB_MAXYSS_KEYWORD'),
                            'params' => $tags
                        );
                    }
                }
            }


            $land = '';
            if($arSettings['LAND'] != ''){
                if ($arProps[$arSettings['LAND']]['PROPERTY_TYPE'] == 'L' || ($arProps[$arSettings['LAND']]['PROPERTY_TYPE'] == 'S' && empty($arProps[$arSettings['LAND']]['USER_TYPE_SETTINGS']))) {
                    $land = ($arProps[$arSettings['LAND']]['VALUE'] != '') ? $arProps[$arSettings['LAND']]['VALUE'] : '';
                } elseif ($arProps[$arSettings['LAND']]['PROPERTY_TYPE'] == 'S' && !empty($arProps[$arSettings['LAND']]['USER_TYPE_SETTINGS'])) {

                    $hlblock = HL\HighloadBlockTable::getRow([
                        'filter' => [
                            '=TABLE_NAME' => $arProps[$arSettings['LAND']]['USER_TYPE_SETTINGS']['TABLE_NAME']
                        ],
                    ]);

                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $main_query = new Entity\Query($entity);
                    $main_query->setSelect(array('*'));
                    $main_query->setFilter(array('UF_XML_ID' => $arProps[$arSettings['LAND']]['VALUE']));
                    $result = $main_query->exec();
                    $result = new CDBResult($result);

                    if ($row = $result->Fetch()) {
                        $land = $row['UF_NAME'];
                    } else {
                        $land = '';
                    }

                } elseif ($arProps[$arSettings['LAND']]['PROPERTY_TYPE'] == 'E' && $arProps[$arSettings['LAND']]['VALUE'] != '') {
                    if ($arProps[$arSettings['LAND']]['MULTIPLE'] == 'N') {
                        $res_brand = CIBlockElement::GetByID($arProps[$arSettings['LAND']]['VALUE']);
                        if ($ar_brand = $res_brand->GetNext())
                            $land = $ar_brand['NAME'];
                    } else {
                        $land = '';
                    }
                } else {
                    $land = '';
                }
            }

            if($arVarProp['colors'])
                $article_dop = '_'.str_replace(' ', '', $arVarProp['colors']);
            else
                $article_dop = '_0';

            $item = array(
                "supplierID" => $arSettings['UUID'],
                "card" => array(
                    "object" => $object,
                    "supplierVendorCode" => ($arSettings['ARTICLE']=='')? $arFields['ID'] : $arFields["PROPERTY_".strtoupper($arSettings['ARTICLE'])."_VALUE"],
                    "countryProduction" => $land,
                    "nomenclatures" => array(
                        array( // Массив номенклатур товара.
                            "vendorCode" => ($arSettings['ARTICLE']=='')? $arFields['ID'] : $arFields["PROPERTY_".strtoupper($arSettings['ARTICLE'])."_VALUE"].$article_dop, // Артикул товара.
                            "variations" => array(
                                array( // Массив вариаций товара. Одна цена - одна вариация.
                                    "barcode" => $arFields["PROPERTY_".strtoupper($arSettings['SHKOD'])."_VALUE"], // Штрихкод товара.
                                    "barcodes"=>array($arFields["PROPERTY_".strtoupper($arSettings['SHKOD'])."_VALUE"]),
                                    "addin" => array(
//                                        array(
//                                            "type" => GetMessage("WB_MAXYSS_PRICE"),
//                                            "params" => array( // У хар-ик, содержащих одно значение, массив будет содержать только 1 элемент.
//                                                array(
//                                                    "count" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]),
//                                                    "units" => GetMessage("WB_MAXYSS_RUB"),
//                                                )
//                                            )
//                                        ),
                                    )
                                ),
                            ),
                            "addin" => array(
                            )
                        ),
                    ),
                    "addin" => $addin_card,
                )
            );
            if(is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]))
                $key_cabinet = array_search($cabinet, $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
            if($cabinet == "DEFAULT" && $key_cabinet===false && is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"])){
                $key_cabinet = array_search('', $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
            }
            if($key_cabinet !== false) {
                if ($arProps['PROP_MAXYSS_CARDID_WB']['VALUE'][$key_cabinet] > 0) {
                    $item['card']['imtId'] = intval($arProps['PROP_MAXYSS_CARDID_WB']['VALUE'][$key_cabinet]);
                }
                if ($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet] > 0 && $arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet] > 0) {
                    $item['card']['nomenclatures'][0]['nmId'] = intval($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet]);
                    $item['card']['nomenclatures'][0]['variations'][0]['chrtId'] = intval($arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet]);
                }
            }

            if(array_key_exists('colors', $arVarProp)){
                $item['card']['nomenclatures'][0]['addin'][] = array(
                    "type" => GetMessage("WB_MAXYSS_COLOR_BASE"),
                    "params" => array(
                        array(
                            "value" => $arVarProp['colors']
                        )
                    )
                );
            }

            if(array_key_exists('wbsizes', $arVarProp)){
                $item['card']['nomenclatures'][0]['variations'][0]['addin'][] = array(
                    "type" => GetMessage("WB_MAXYSS_ROS_SIZE"),
                    "params" => array(
                        array(
                            "value" => strval($arVarProp['wbsizes'])
                        )
                    )
                );
            }
            if(array_key_exists('tech-sizes', $arVarProp)){
                $item['card']['nomenclatures'][0]['variations'][0]['addin'][] = array(
                    "type" => GetMessage("WB_MAXYSS_SIZE_WB"),
                    "params" => array(
                        array(
                            "value" => strval($arVarProp['tech-sizes'])
                        )
                    )
                );
            }
        }
        return array('item'=>$item, 'img'=>$img, 'props' => $arVarProp);
    }

    public static function uploadPicture($file, $uuid, $auth){
        $result = array();
        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $data_string = array(
                        'file' => $f,
                    );
//                    $arResult = CRestQueryWB::rest_file($base_url = WB_BASE_URL, $data_string, "/card/upload/file");
                    $arResult = CRestQueryWB::rest_file_na($base_url = WB_BASE_URL, $data_string, "/card/upload/file/multipart", $uuid, $auth);
                    if ($arResult != '')
                        $result[] = $arResult;
                }
            }
        }
        return $result;
    }

    public static function elemAsProduct($id_element, $arSettings = array()){
        $arTovar = array();
        $ar_tovar = CCatalogProduct::GetByID($id_element); // item as product

        $amount = 0;
        if($ar_tovar['TYPE']!=3) {
            if (array_key_exists("WAREHOUSES", $arSettings)) { // dbs
                $arTovar = $ar_tovar;
                $arTovar['WAREHOUSES'] = array();
                $allStores = array();
                $rsStore = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $id_element), false, false);
                while ($arStore = $rsStore->Fetch()) {
                    $allStores[$arStore["STORE_ID"]] = $arStore;
                }

                foreach ($arSettings['WAREHOUSES'] as $key => $wh) {
                    if ($arSettings["DEACTIVATE_WH"][$key] != "Y") {
                        $amount = 0;
                        $to_sklad = false;
                        foreach ($wh as $sklad_bx) {
                            if ($sklad_bx != '') $to_sklad = true;
                        }
                        if ($to_sklad) {
                            foreach ($wh as $sklad_bx) {
                                if ($sklad_bx != '')
                                    $amount += $allStores[$sklad_bx]["AMOUNT"];
                            }
                        } else {
                            $amount = ($ar_tovar['QUANTITY'] > 0) ? $ar_tovar['QUANTITY'] : 0;
                        }
                        // KGT NON KGT
                        if(isset($arSettings['KGT_WH']) && is_array($arSettings['KGT_WH'])) {
                            $kgt = CAddinMaxyssWB::KgtSize($arTovar);
                            if ($kgt && $arSettings['KGT_WH'][$key] == 'Y')
                                $arTovar['WAREHOUSES'][$key] = ($amount < $arSettings["LIMIT_WAREHOUSE_DBS"][$key]) ? 0 : $amount;
                            elseif (!$kgt && $arSettings['KGT_WH'][$key] != 'Y')
                                $arTovar['WAREHOUSES'][$key] = ($amount < $arSettings["LIMIT_WAREHOUSE_DBS"][$key]) ? 0 : $amount;
                        }else{
                            $arTovar['WAREHOUSES'][$key] = ($amount < $arSettings["LIMIT_WAREHOUSE_DBS"][$key]) ? 0 : $amount;
                        }

                    }
                }
            }
            else
            {
                if (is_array($arSettings['SKLAD_ID']) && !empty($arSettings['SKLAD_ID'])) {
                    foreach ($arSettings['SKLAD_ID'] as $sklad_id) {
                        $rsStore = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $id_element, 'STORE_ID' => $sklad_id), false, false, array('AMOUNT'));
                        if ($arStore = $rsStore->Fetch()) {
                            $amount += ($arStore['AMOUNT'] > 0) ? $arStore['AMOUNT'] : 0;
                        } else {
                            $amount += 0;
                        }
                    }
                } else {
                    $amount = ($ar_tovar['QUANTITY'] > 0) ? $ar_tovar['QUANTITY'] : 0;
                }

                $arTovar = $ar_tovar;
                $arTovar['QUANTITY'] = intval($amount);
            }
        }
        else
        {
            $arTovar = $ar_tovar;
        }

        return $arTovar;
    }
    public static function elemAsProductEvent($id_element, $arSettings = array(), $quantity){
        $arTovar = array();
        $ar_tovar = CCatalogProduct::GetByID($id_element); // item as product

        $amount = 0;
        $arTovar = $ar_tovar;

        if(array_key_exists("WAREHOUSES", $arSettings)){ // dbs
            $arTovar['WAREHOUSES'] = array();
            $allStores = array();
            $rsStore = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $id_element), false, false);
            while ($arStore = $rsStore->Fetch()) {
                $allStores[$arStore["STORE_ID"]] = $arStore;
            }
            foreach ($arSettings['WAREHOUSES'] as $key => $wh) {
                if($arSettings["DEACTIVATE_WH"][$key] != "Y") {
                    $amount = 0;
                    $to_sklad = false;
                    foreach ($wh as $sklad_bx) {
                        if($sklad_bx !='') $to_sklad = true;
                    }
                    if($to_sklad) {
                        foreach ($wh as $sklad_bx) {
                            if($sklad_bx !='')
                                $amount += $allStores[$sklad_bx]["AMOUNT"];
                        }
                    }
                    else
                    {
                        $amount = ($quantity > 0)? $quantity : 0;
                    }

                    // KGT NON KGT
                    if(isset($arSettings['KGT_WH']) && is_array($arSettings['KGT_WH'])) {
                        $kgt = CAddinMaxyssWB::KgtSize($arTovar);
                        if ($kgt && $arSettings['KGT_WH'][$key] == 'Y')
                            $arTovar['WAREHOUSES'][$key] = ($amount < $arSettings["LIMIT_WAREHOUSE_DBS"][$key]) ? 0 : $amount;
                        elseif (!$kgt && $arSettings['KGT_WH'][$key] != 'Y')
                            $arTovar['WAREHOUSES'][$key] = ($amount < $arSettings["LIMIT_WAREHOUSE_DBS"][$key]) ? 0 : $amount;
                    }else{
                        $arTovar['WAREHOUSES'][$key] = ($amount < $arSettings["LIMIT_WAREHOUSE_DBS"][$key]) ? 0 : $amount;
                    }
                }
            }
        }
        else
        {
            if (is_array($arSettings['SKLAD_ID']) && !empty($arSettings['SKLAD_ID'])) {
                foreach ($arSettings['SKLAD_ID'] as $sklad_id) {
                    $rsStore = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $id_element, 'STORE_ID' => $sklad_id), false, false, array('AMOUNT'));
                    if ($arStore = $rsStore->Fetch()) {
                        $amount += ($arStore['AMOUNT'] > 0)? $arStore['AMOUNT'] : 0 ;
                    } else {
                        $amount += 0;
                    }
                }
            } else {
                $amount = ($quantity > 0)? $quantity : 0;
            }

//            $arTovar = $ar_tovar;
            $arTovar['QUANTITY'] = intval($amount);
        }
        return $arTovar;
    }
    public static function UploadCadr($item_info, $id_element, $auth){

        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "onUploadItem", array(&$item_info));
        $event->send();

        $res = array();
        $arInfo = CMaxyssWb::GetLicense();
        $data_string = array(
            "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
            "jsonrpc" => "2.0",
            'params' => array('card' => $item_info),
        );
        $arResult = array();
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/create", $auth);
            $arResult = \Bitrix\Main\Web\Json::decode($result);
            if (!$arResult["error"]) {
                $res = GetMessage("WB_MAXYSS_PRODUCT_UPLOAD");
            } else {
                if (isset($arResult["error"]["data"]["cause"]) && $arResult["error"]["data"]["cause"]["err"])
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["data"]["cause"]["err"] . '.';
                elseif (isset($arResult["error"]["data"]["err"]) && $arResult["error"]["data"]["err"])
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["data"]["err"] . '.';
                elseif (isset($arResult["error"]["message"]) && $arResult["error"]["message"])
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["message"] . '.';
                else
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"] . '. ' . GetMessage("WB_MAXYSS_ERROR_AJAX_TWO");
            }
        }

        return $res;
    }

    public static function getBarcodes($n, $uuid=false, $auth=false){
        $data_string = array(
            'count' => $n,
        );
        $arResult = array();
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/content/v1/barcodes", $auth);
        $arResult = \Bitrix\Main\Web\Json::decode($result);

        return $arResult;
    }

    public static function UpdateCadr($item_info, $id_element, $auth){

        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "onUploadItem", array(&$item_info));
        $event->send();

        $res = array();
        $arInfo = CMaxyssWb::GetLicense();
        $data_string = array(
            "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
            "jsonrpc" => "2.0",
            'params' => array('card' => $item_info)
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/update", $auth);
            $arResult = \Bitrix\Main\Web\Json::decode($result);
            if (!$arResult["error"]) {
                $res = GetMessage("WB_MAXYSS_PRODUCT_UPLOAD");
            } else {
                if (isset($arResult["error"]["data"]["cause"]) && $arResult["error"]["data"]["cause"]["err"])
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["data"]["cause"]["err"] . '.';
                elseif (isset($arResult["error"]["data"]["err"]) && $arResult["error"]["data"]["err"])
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["data"]["err"] . '.';
                elseif (isset($arResult["error"]["message"]) && $arResult["error"]["message"])
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["message"] . '.';
                else
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"] . '. ' . GetMessage("WB_MAXYSS_ERROR_AJAX_TWO");
            }
        }

        echo $res;
    }

    public static function GetCadrByImtID($id, $id_element, $uuid=false, $auth=false){
        $arResult=array();
        if(strlen(strval(intval($id))) < 7){
            $arResult["error"]['message']  ='NOT_VALID_FORMAT_CARD_ID';
        }else {

            $arInfo = CMaxyssWb::GetLicense();
            $data_string = array(
                "id" => md5("BITRIX" . $arInfo['key'] . time() . "LICENCE"),
                "jsonrpc" => "2.0", // Версия протокола. Всегда должна быть "2.0".
                'params' => array(
                    "imtID" => intval($id),
                )
            );

            $Authorization = $auth;
            $supplierID = $uuid;


            $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnGetCadrByImtID", array($id_element, &$Authorization, &$supplierID, $params = array()));
            $event->send();

            if(!$Authorization) $Authorization = AUTHORIZATION;
            if(!$supplierID) $supplierID = Option::get(MAXYSS_WB_NAME, "UUID", "");

            $data_string['params']["supplierID"]= $supplierID;


            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $bck = self::bck_wb();
            if ($bck['BCK'] && $bck['BCK'] != "Y") {
                $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/cardByImtID", $Authorization);
                if (!is_array($result))
                    $arResult = \Bitrix\Main\Web\Json::decode($result);
                else
                    $arResult = $result;
            }
        }
        return $arResult;
    }

    public static function GetCadrList($article = '', $id_element = 0, $uuid=false, $auth=false){
        $arResult = array();

        $arInfo = CMaxyssWb::GetLicense();
        $data_string = array(
            "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
            "jsonrpc" => "2.0",
            'params' => array(
                "query"=>array(
                    "limit"=> 10,
                    "offset"=> 0
                ),
//                "withError"=> true
            )
        );


        if($article != '')
            $data_string['params']["filter"]= array(
                'find'=>array(
                    array(
                        "column"=> (Option::get(MAXYSS_WB_NAME, "COLUMN_FILTER", "") != "")? Option::get(MAXYSS_WB_NAME, "COLUMN_FILTER", "") : "vendorCode", // \Bitrix\Main\Config\Option::set("maxyss.wb", "COLUMN_FILTER", "nomenclatures.vendorCode");
                        "search"=>$article
                    )
                )
            );
        else
            $data_string['params']["query"]['limit']= 10;


        $Authorization = $auth;
        $supplierID = $uuid;


        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnGetCadrList", array($id_element, &$Authorization, &$supplierID, $params = array()));
        $event->send();



        if(!$Authorization) $Authorization = AUTHORIZATION;
        if(!$supplierID) $supplierID = Option::get(MAXYSS_WB_NAME, "UUID", "");

        $data_string['params']["supplierID"]= $supplierID;
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $arResult = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/list", $Authorization);
        return \Bitrix\Main\Web\Json::decode($arResult);
    }

    /// остатки
    public static function prepareItemStock($id, $quantity, $arSettings){
        $lid = $arSettings['SITE'];
        $items = array();
        $iblock_shkod = $arSettings["SHKOD"];
        $limit_warehouse = $arSettings['LIMIT_WAREHOUSE'];
        $barcode = '';

        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_".$iblock_shkod, "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB");
        $arFilter = Array('=ID' => $id/*, 'PROPERTY_'.$arSettings["FILTER_PROP"] => $arSettings["FILTER_PROP_ID"] */);
        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, Array("nPageSize" => 1), $arSelect);
        if ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $barcode = $arFields["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"];
        }
        if($barcode != '') {
            $arTovar = self::elemAsProductEvent($id, $arSettings, $quantity);
            if (!isset($arTovar["WAREHOUSES"])) { // fbs
//                if (is_array($arSettings["SKLAD_ID"]) && !empty($arSettings["SKLAD_ID"])) {
                    if ($limit_warehouse > 0) $quant = ($arTovar["QUANTITY"] < $limit_warehouse) ? 0 : $arTovar["QUANTITY"];
                    else $quant = $arTovar["QUANTITY"];
//                }
            } else { // dbs
                foreach ($arTovar["WAREHOUSES"] as $key => $wh) {
                    $items[intval($key)][] = array(
                        "sku" => $barcode,
                        "amount" => intval(($wh <= 100000)? $wh : 100000),
                    );
                }
            }
        }
        return $items;
    }

    public static function prepareAllItemsStock($arSettings, $arrFilter){
        $IBLOCK_ID = $arSettings["IBLOCK_ID"];
        $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
        $cabinet =  $arSettings['LK'];

        $item = array();
        $item_price = array();
        $item_discounts_revoke = array();
        $item_discounts = array();
        $item_promocodes = array();
        $item_promocodes_revoke = array();

        $iblock_shkod = $arSettings["SHKOD"];
        $limit_warehouse = $arSettings["LIMIT_WAREHOUSE"];

        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_".$iblock_shkod, "PROPERTY_PROP_MAXYSS_CARDID_WB", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB", "PROPERTY_PROP_MAXYSS_PROMOCODES_WB", "PROPERTY_PROP_MAXYSS_DISCOUNTS_WB");
        $arFilter = Array(
            "IBLOCK_ID" => intval($IBLOCK_ID),
            "ACTIVE" => "Y",
            array(
                "LOGIC" => "OR",
                array('!PROPERTY_PROP_MAXYSS_CARDID_WB' => false),
                array('!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB' => false, '!PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB' => false),
                array('ID' => CIBlockElement::SubQuery("PROPERTY_CML2_LINK", array(
                        "IBLOCK_ID" => $arInfoOff['IBLOCK_ID'],
                        "!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false,
                    )
                )
                ),
            )
        );
//        if($arSettings["FILTER_PROP"] != '' && $arSettings["FILTER_PROP_ID"] !=''){
//            $arFilter['PROPERTY_'.$arSettings["FILTER_PROP"]] =  $arSettings["FILTER_PROP_ID"];
//        }

        $arCustomFilter = array();
        if($arSettings["CUSTOM_FILTER"]) {
            $filter_custom = new FilterCustomWB();
            $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arSettings["CUSTOM_FILTER"])), array());
        }
        elseif ($arSettings['FILTER_PROP'] != '' && $arSettings['FILTER_PROP_ID'] != '')
            $arFilter['PROPERTY_' . $arSettings['FILTER_PROP']] = $arSettings['FILTER_PROP_ID'];

        if(!empty($arrFilter)){
            $arFilter = array_merge($arFilter, $arrFilter);
        }

        if(!empty($arCustomFilter)){
            $arFilter[] = $arCustomFilter;
        }
//        echo '<pre>', print_r($arFilter), '</pre>' ;
        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, false, $arSelect);

        $arIds = array();
        while ($ob = $res->GetNextElement()) {
            $ar_tovar = array();
            $arFields = $ob->GetFields();
            if(!isset($arIds[$arFields['ID']])) {
                $arIds[$arFields['ID']] = $arFields['ID'];
                $arProps=$ob->GetProperties();
                $ar_tovar = self::elemAsProduct($arFields["ID"], $arSettings); // item as product
                if ($ar_tovar["TYPE"] == 1) {
                    $item_nmId = 0;

                    if(is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]))
                        $key_cabinet = array_search($cabinet, $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
                    if($cabinet == "DEFAULT" && $key_cabinet===false && is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"])){
                        $key_cabinet = array_search('', $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
                    }
                    if($key_cabinet !== false) {
                        if ($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet] > 0 && $arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet] > 0) {
                            $item_nmId = intval($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet]);
                        }
                    }
                    if ($item_nmId > 0) {
                        if ($arFields["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"] != '') {

                            if (!isset($ar_tovar["WAREHOUSES"])) { // fbs
                                if ($limit_warehouse > 0) $stock_res = ($ar_tovar['QUANTITY'] < $limit_warehouse) ? 0 : $ar_tovar['QUANTITY'];
                                else $stock_res = $ar_tovar['QUANTITY'];

                                $item[] = array(
                                    "barcode" => $arFields["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"],
                                    "stock" => intval($stock_res),
                                    "warehouseId" => intval($arSettings["SKLAD"])
                                );
                            } else { // dbs
                                foreach ($ar_tovar["WAREHOUSES"] as $key => $wh) {
                                    $item[intval($key)][] = array(
                                        "sku" => strval($arFields["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"]),
                                        "amount" => intval(($wh <= 100000)? $wh : 100000),
                                    );
                                }
                            }
                            if (intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]) > 0) {
                                $prices = array(
                                    "nmId" => $item_nmId,
                                    "price" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $arSettings["SITE"], $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]), // цена товара - норм
                                );

                                $item_prices[] = $prices;

                                // discounts
                                if (intval($arFields["PROPERTY_PROP_MAXYSS_DISCOUNTS_WB_VALUE"]) > 0) {
                                    // установка скидок
                                    $item_discounts[] = array(
                                        "discount" => intval($arFields["PROPERTY_PROP_MAXYSS_DISCOUNTS_WB_VALUE"]),
                                        "nm" => $item_nmId,
                                    );
                                } else {
                                    // сброс скидок
                                    $item_discounts_revoke[] = $item_nmId;
                                }

//                                // promocodes
//                                if (intval($arFields["PROPERTY_PROP_MAXYSS_PROMOCODES_WB_VALUE"]) > 0) {
//                                    // установка промокодов
//                                    $item_promocodes[] = array(
//                                        "discount" => intval($arFields["PROPERTY_PROP_MAXYSS_PROMOCODES_WB_VALUE"]),
//                                        "nm" => intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]),
//                                    );
//                                } else {
//                                    // сброс промокодов
//                                    $item_promocodes_revoke[] = intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]);
//                                }

                            }
                        }
                    }
                }
                elseif ($ar_tovar["TYPE"] == 3)
                {
                    if (is_array($arInfoOff)) {

                        $arSelectOff = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB", "PROPERTY_" . $iblock_shkod, "PROPERTY_PROP_MAXYSS_PROMOCODES_WB", "PROPERTY_PROP_MAXYSS_DISCOUNTS_WB");
                        $rsOffers = CIBlockElement::GetList(array(), array(
                            'IBLOCK_ID' => $arInfoOff['IBLOCK_ID'],
                            "!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false,
//                        "!PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB" => false,
                            "ACTIVE" => "Y",
                            'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $arFields["ID"]
                        ), false, false, $arSelectOff);
                        $arItems = array();
                        while ($arOffer = $rsOffers->GetNextElement()) {
                            $item_off_nmId = 0; $chrtID = 0;
                            $key_cabinet_prop = false;
                            $arFieldsOff = $arOffer->GetFields();
                            $arPropOff = $arOffer->GetProperties();
                            if(!isset($arIds[$arFieldsOff['ID']])) {
                                $arIds[$arFieldsOff['ID']] = $arFieldsOff['ID'];

                                if (is_array($arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']))
                                    $key_cabinet_prop = array_search($cabinet, $arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                                if($key_cabinet_prop === false && $cabinet == 'DEFAULT')
                                    $key_cabinet_prop = array_search('', $arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                                if ($key_cabinet_prop !== false && $arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet_prop] != '' && $arPropOff['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet_prop] != '') {
                                    $chrtID = (int)$arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet_prop];
                                    $item_off_nmId = (int)$arPropOff['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet_prop];
                                }

                                if ($chrtID > 1 && $item_off_nmId > 1) {
                                    $ar_tovar_off = self::elemAsProduct($arFieldsOff["ID"], $arSettings); // item as product
                                    $arFieldsOff['TOVAR'] = $ar_tovar_off;
                                    $arItems[$item_off_nmId][] = $arFieldsOff;
                                }
                            }
                        }
                        if (!empty($arItems)) {
                            foreach ($arItems as $key => $i) {
                                foreach ($i as $c) {
                                    if ($c["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"] != '') {
                                        if (!isset($c['TOVAR']["WAREHOUSES"])) { // fbs
                                            if ($limit_warehouse > 0) $stock_res = ($c['TOVAR']['QUANTITY'] < $limit_warehouse) ? 0 : $c['TOVAR']['QUANTITY'];
                                            else $stock_res = $c['TOVAR']['QUANTITY'];

                                            $item[] = array(
                                                "barcode" => $c["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"],
                                                "stock" => intval($stock_res),
                                                "warehouseId" => intval($arSettings["SKLAD"])
                                            );
                                        } else { // dbs
                                            foreach ($c['TOVAR']["WAREHOUSES"] as $key_wh => $wh) {
                                                $item[intval($key_wh)][] = array(
                                                    "sku" => strval($c["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"]),
                                                    "amount" => intval(($wh <= 100000)? $wh : 100000),
                                                );
                                            }
                                        }
                                    }

                                    $tp_price[] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $c['ID'], $arSettings["SITE"], $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);
                                    $tp_discounts[] = intval($c["PROPERTY_PROP_MAXYSS_DISCOUNTS_WB_VALUE"]);

                                    $tp_promocodes[] = intval($c["PROPERTY_PROP_MAXYSS_PROMOCODES_WB_VALUE"]);

                                }

                                if ($arSettings['PRICE_MAX_MIN'] == 'MAX') {
                                    $price = max($tp_price);
                                } else {
                                    $price = min($tp_price);
                                }


                                $prices = array(
                                    "nmId" => intval($key),
                                    "price" => $price,
                                );
                                $item_prices[] = $prices;

                                // discounts

                                if ($arSettings['PRICE_MAX_MIN'] == 'MAX') {
                                    $discount = min($tp_discounts);
                                } else {
                                    $discount = max($tp_discounts);
                                }
                                if ($discount > 0) {
                                    // установка скидок
                                    $discounts = array(
                                        "discount" => $discount,
                                        "nm" => intval($key),
                                    );
                                    $item_discounts[] = $discounts;
                                } else {
                                    // сброс скидок
                                    $item_discounts_revoke[] = intval($key);
                                }

                                // promocodes

//                                if ($arSettings['PRICE_MAX_MIN'] == 'MAX') {
//                                    $promocode = min($tp_promocodes);
//                                } else {
//                                    $promocode = max($tp_promocodes);
//                                }
//
//                                if ($promocode > 0) {
//                                    // установка промокодов
//                                    $item_promocodes[] = array(
//                                        "discount" => $promocode,
//                                        "nm" => $key,
//                                    );
//                                } else {
//                                    // сброс промокодов
//                                    $item_promocodes_revoke[] = $key;
//                                }

                                unset($tp_promocodes, $tp_discounts, $tp_price);
                            }
                        }
                    }
                }
            }
        }
        return array("stocks"=>$item, "prices"=>$item_prices, "discounts"=>$item_discounts, "discounts_revoke"=>$item_discounts_revoke, "promocodes"=>$item_promocodes,"promocodes_revoke"=>$item_promocodes_revoke);
    }

    public static function prepareAllItemsPrice($arSettings, $arrFilter = array()){
        $cabinet =  $arSettings['LK'];
        $IBLOCK_ID = $arSettings["IBLOCK_ID"];

        $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

        $item_price = array();
        $item_discounts_revoke = array();
        $item_discounts = array();
        $item_promocodes = array();
        $item_promocodes_revoke = array();

        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_PROP_MAXYSS_CARDID_WB", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB", "PROPERTY_PROP_MAXYSS_PROMOCODES_WB", "PROPERTY_PROP_MAXYSS_DISCOUNTS_WB");
        $arFilter = Array(
            "IBLOCK_ID" => intval($IBLOCK_ID),
            "ACTIVE" => "Y",
            array(
                "LOGIC" => "OR",
                array('!PROPERTY_PROP_MAXYSS_CARDID_WB' => false),
                array('!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB' => false, '!PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB' => false),
                array('ID' => CIBlockElement::SubQuery("PROPERTY_CML2_LINK", array(
                        "IBLOCK_ID" => $arInfoOff['IBLOCK_ID'],
                        "!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false,
                    )
                )
                ),
            )
        );

        $arCustomFilter = array();
        if($arSettings["CUSTOM_FILTER"]) {
            $filter_custom = new FilterCustomWB();
            $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arSettings["CUSTOM_FILTER"])), array());
        }
        elseif ($arSettings['FILTER_PROP'] != '' && $arSettings['FILTER_PROP_ID'] != '')
            $arFilter['PROPERTY_' . $arSettings['FILTER_PROP']] = $arSettings['FILTER_PROP_ID'];

        if(!empty($arrFilter)){
            $arFilter = array_merge($arFilter, $arrFilter);
        }

        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, false, $arSelect);
        $arIds = array();
        while ($ob = $res->GetNextElement()) {
            $ar_tovar = array();
            $arFields = $ob->GetFields();
            $arProps = $ob->GetProperties();
            $key_cabinet = false;

            if(!isset($arIds[$arFields['ID']])) {
                $arIds[$arFields['ID']] = $arFields['ID'];
                $ar_tovar = CCatalogProduct::GetByID($arFields["ID"]); // item as product
                if ($ar_tovar["TYPE"] == 1) {
                    $item_nmId = 0;
                    if(is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]))
                        $key_cabinet = array_search($cabinet, $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
                    if($cabinet == "DEFAULT" && $key_cabinet===false && is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"])){
                        $key_cabinet = array_search('', $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
                    }
                    if($key_cabinet !== false) {
                        if ($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet] > 0 && $arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet] > 0) {
                            $item_nmId = intval($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet]);
                        }
                    }

                    if ($item_nmId > 0) {

                        $price = array(
                            "nmId" => $item_nmId,
                            "price" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $arSettings["SITE"], $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]),
                        );
                        $item_price[] = $price;

                        // discounts
                        if (intval($arFields["PROPERTY_PROP_MAXYSS_DISCOUNTS_WB_VALUE"]) > 0) {
                            // установка скидок
                            $item_discounts[] = array(
                                "discount" => intval($arFields["PROPERTY_PROP_MAXYSS_DISCOUNTS_WB_VALUE"]),
                                "nm" => $item_nmId,
                            );
                        } else {
                            // сброс скидок
                            $item_discounts_revoke[] = $item_nmId;
                        }

//                        // promocodes
//                        if (intval($arFields["PROPERTY_PROP_MAXYSS_PROMOCODES_WB_VALUE"]) > 0) {
//                            // установка промокодов
//                            $item_promocodes[] = array(
//                                "discount" => intval($arFields["PROPERTY_PROP_MAXYSS_PROMOCODES_WB_VALUE"]),
//                                "nm" => intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]),
//                            );
//                        } else {
//                            // сброс промокодов
//                            $item_promocodes_revoke[] = intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]);
//                        }

                    }
                }
                elseif ($ar_tovar["TYPE"] == 3)
                {
                    if (is_array($arInfoOff)) {

                        $arSelectOff = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB", "PROPERTY_PROP_MAXYSS_PROMOCODES_WB", "PROPERTY_PROP_MAXYSS_DISCOUNTS_WB");
                        $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfoOff['IBLOCK_ID'], "!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false, "!PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB" => false, "ACTIVE" => "Y", 'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $arFields["ID"]), false, false, $arSelectOff);
                        $arItems = array();
                        while ($arOffer = $rsOffers->GetNextElement()) {
                            $item_off_nmId = 0; $chrtID = 0;
                            $key_cabinet_prop = false;
                            $arFieldsOff = $arOffer->GetFields();
                            $arPropOff = $arOffer->GetProperties();
                            if(!isset($arIds[$arFieldsOff['ID']])) {
                                $arIds[$arFieldsOff['ID']] = $arFieldsOff['ID'];

                                if (is_array($arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']))
                                    $key_cabinet_prop = array_search($cabinet, $arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                                if($key_cabinet_prop === false && $cabinet == 'DEFAULT' && is_array($arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']))
                                    $key_cabinet_prop = array_search('', $arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                                if ($key_cabinet_prop !== false && $arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet_prop] != '' && $arPropOff['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet_prop] != '') {
                                    $chrtID = intval($arPropOff['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet_prop]);
                                    $item_off_nmId = intval($arPropOff['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet_prop]);
                                }
                                if ($item_off_nmId > 1 && $chrtID > 1) {
                                    $arItems[$item_off_nmId][$arFieldsOff['ID']] = $arFieldsOff;
                                }
                            }
                        }
                        if (!empty($arItems)) {
                            foreach ($arItems as $key => $i) {

                                foreach ($i as $c) {

                                    $tp_price[] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $c['ID'], $arSettings["SITE"], $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);

                                    $tp_discounts[] = intval($c["PROPERTY_PROP_MAXYSS_DISCOUNTS_WB_VALUE"]);

//                                    $tp_promocodes[] = intval($c["PROPERTY_PROP_MAXYSS_PROMOCODES_WB_VALUE"]);
                                }
                                if ($arSettings['PRICE_MAX_MIN'] == 'MAX') {
                                    $price = max($tp_price);
                                } else {
                                    $price = min($tp_price);
                                }
                                $price = array(
                                    "nmId" => intval($key),
                                    "price" => $price,
                                );
                                $item_price[] = $price;


                                // discounts

                                if ($arSettings['PRICE_MAX_MIN'] == 'MAX') {
                                    $discount = min($tp_discounts);
                                } else {
                                    $discount = max($tp_discounts);
                                }
                                if ($discount > 0) {
                                    // установка скидок
                                    $discounts = array(
                                        "discount" => $discount,
                                        "nm" => intval($key),
                                    );
                                    $item_discounts[] = $discounts;
                                } else {
                                    // сброс скидок
                                    $item_discounts_revoke[] = intval($key);
                                }

                                // promocodes

//                                if ($arSettings['PRICE_MAX_MIN'] == 'MAX') {
//                                    $promocode = min($tp_promocodes);
//                                } else {
//                                    $promocode = max($tp_promocodes);
//                                }
//
//                                if ($promocode > 0) {
//                                    // установка промокодов
//                                    $item_promocodes[] = array(
//                                        "discount" => $promocode,
//                                        "nm" => $key,
//                                    );
//                                } else {
//                                    // сброс промокодов
//                                    $item_promocodes_revoke[] = $key;
//                                }

                                unset($tp_promocodes, $tp_discounts, $tp_price);
                            }
                        }
                    }
                }
            }


        }
        return array("prices"=>$item_price, "discounts"=>$item_discounts, "discounts_revoke"=>$item_discounts_revoke, "promocodes"=>$item_promocodes,"promocodes_revoke"=>$item_promocodes_revoke );
    }

    public static function what_cabinet($item_id){
        $arSettings = self::settings_wb();
        $iblock_id = CIBlockElement::GetIBlockByID($item_id);
        $mxResult = CCatalogSKU::GetInfoByOfferIBlock(
            $iblock_id
        );
        $arCabinet = false;
        $flag_upd = false;
        if (is_array($mxResult)) {
            // это ТП
            if(is_array($arSettings["IBLOCK_ID"])){
                foreach ($arSettings["IBLOCK_ID"] as $cabinet => $iblock){
                    $flag_upd = false;
                    if($mxResult["PRODUCT_IBLOCK_ID"] == $iblock) {

                        $tovarResult = CCatalogSku::GetProductInfo(
                            $item_id // id TP
                        );
                        if (is_array($tovarResult)) {

                            $db_props_barcode = CIBlockElement::GetProperty($tovarResult['OFFER_IBLOCK_ID'], $item_id, array("sort" => "asc"), Array("CODE"=>$arSettings["SHKOD"][$cabinet]));
                            if($ar_props_barcode = $db_props_barcode->Fetch()){
                                if($ar_props_barcode['VALUE'] != '')
                                    $barcode = true;
                                else
                                    $barcode = false;
                            }
                            else
                                $barcode = false;

//                            $arFilter = Array("ID" => $tovarResult['ID'], 'PROPERTY_'.$arSettings["FILTER_PROP"][$cabinet] => $arSettings["FILTER_PROP_ID"][$cabinet]);
                            $arFilter = Array("ID" => $tovarResult['ID']/*, 'PROPERTY_'.$arSettings["FILTER_PROP"][$cabinet] => $arSettings["FILTER_PROP_ID"][$cabinet]*/);


                            $arCustomFilter = array();
                            if($arSettings["CUSTOM_FILTER"][$cabinet]) {
                                $filter_custom = new FilterCustomWB();
                                $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arSettings["CUSTOM_FILTER"][$cabinet])), array());
                            }
                            elseif ($arSettings['FILTER_PROP'][$cabinet] != '' && $arSettings['FILTER_PROP_ID'][$cabinet] != '')
                                $arFilter['PROPERTY_' . $arSettings['FILTER_PROP'][$cabinet]] = $arSettings['FILTER_PROP_ID'][$cabinet];

                            if(!empty($arCustomFilter)){
                                $arFilter[] = $arCustomFilter;
                            }

                            if($barcode) {
                                $res = CIBlockElement::GetList(Array(), $arFilter, false, false, array("ID", "IBLOCK_ID"));
                                if ($ob = $res->GetNextElement()) {
                                    $arProps = $ob->GetProperties();
                                    if(is_array($arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) && array_search($cabinet, $arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) !== false)
                                        $flag_upd = true;
                                    elseif ($cabinet=='DEFAULT'){
                                        if(is_array($arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) && array_search('', $arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) !== false){
                                            $flag_upd = true;
                                        }
                                    }
                                }
                            }
                        }
                        if($flag_upd) {
                            $arCabinet[$cabinet]['flag_upd'] = $flag_upd;
                            $arCabinet[$cabinet]['settings'] = self::settings_wb($cabinet);
                        }
                    }
                }
                return $arCabinet;
            }
            else
            {
                return false;
            }
        }
        else
        {
            // это товар
            if(is_array($arSettings["IBLOCK_ID"])){
                foreach ($arSettings["IBLOCK_ID"] as $cabinet => $iblock){
                    $flag_upd = false;
                    if($iblock_id == $iblock) {

                        $arFilter = array();
                        $arCustomFilter = array();
                        if($arSettings["CUSTOM_FILTER"][$cabinet]) {
                            $filter_custom = new FilterCustomWB();
                            $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arSettings["CUSTOM_FILTER"][$cabinet])), array());
                        }
                        elseif ($arSettings['FILTER_PROP'][$cabinet] != '' && $arSettings['FILTER_PROP_ID'][$cabinet] != '')
                            $arFilter['PROPERTY_' . $arSettings['FILTER_PROP'][$cabinet]] = $arSettings['FILTER_PROP_ID'][$cabinet];

                        if(!empty($arCustomFilter)){
                            $arFilter[] = $arCustomFilter;
                        }

                        $arFilter["ID"] = $item_id;
                        $arFilter['!PROPERTY_'.$arSettings["SHKOD"][$cabinet]] = false;
                        $res = CIBlockElement::GetList(Array(), $arFilter, false, false);
                        if ($ob = $res->GetNextElement()) {
                            $arProps = $ob->GetProperties();
                            if(
                                    (is_array($arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) && array_search($cabinet, $arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) !== false) ||
                                    (is_array($arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) && array_search($cabinet, $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) !== false) ||
                                    (is_array($arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']) && array_search($cabinet, $arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']) !== false)
                            )
                                $flag_upd = true;
                            elseif ($cabinet=='DEFAULT'){
                                if(
                                        (is_array($arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) && array_search('', $arProps['PROP_MAXYSS_CARDID_WB']['DESCRIPTION']) !== false) ||
                                        (is_array($arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) && array_search('', $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) !== false) ||
                                        (is_array($arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']) && array_search('', $arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']) !== false)
                                ){
                                    $flag_upd = true;
                                }
                            }
                        }

                        if($flag_upd) {
                            $arCabinet[$cabinet]['flag_upd'] = $flag_upd;
                            $arCabinet[$cabinet]['settings'] = self::settings_wb($cabinet);
                        }
                    }
                }
                return $arCabinet;
            }
            else
            {
                return false;
            }
        }
    }

    public static function uploadStock($event){
        // остаток по событию изменения полного доступного количества
        $item = $event->getParameters();
        $result = '';
        $ar_tovar = CCatalogProduct::GetByID($item["id"]); // item as product

        if(isset($item['fields']['QUANTITY']) && $ar_tovar['TYPE'] !=3 && $ar_tovar['QUANTITY'] != $item['fields']['QUANTITY']) {

            $arCabinet = self::what_cabinet($item["id"]);
            if($arCabinet && is_array($arCabinet)) {
                foreach ($arCabinet as $cab=>$cabinet) {
                    $items = self::prepareItemStock($item['id'], $item['fields']['QUANTITY'], $cabinet['settings']);
                    $result = self::updateStock($cabinet['settings']["AUTHORIZATION"], $items, $cab);
                    if($cabinet['settings']["LOG_ON"]=="Y"){
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'uploadStockEvent', "MODULE_ID" => '', "ITEM_ID" => $cab, "DESCRIPTION" => serialize($result)));
                    }
                }
            }
        }
    }

    public static function uploadAllStocks($cabinet = 'DEFAULT', $arrFilter = array()){
        // отправить все остатки (агент)
        $arSettings = self::settings_wb($cabinet);
        $Authorization = $arSettings['AUTHORIZATION'];

        $items = self::prepareAllItemsStock($arSettings, $arrFilter);
        if( $arSettings['LOG_ON'] == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'uploadAllStocks', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "uploadAllStocks", "DESCRIPTION" => serialize($items)));
        }
        if(!empty($items["stocks"])) {
            $result = self::updateStock($Authorization, $items["stocks"], $cabinet);
        }
        if(!empty($items["prices"]) && $arSettings['PRICE_ON'] == 'Y') {
                $result_price = CMaxyssWbprice::setPrices($Authorization, $items["prices"]);
        }
        if(!empty($items["discounts"]) &&  $arSettings["DISCOUNTS_ON"] == 'Y') {
            $result_discounts = CMaxyssWbprice::setDiscounts($Authorization = false, $items["discounts"]);
        }
        if(!empty($items["discounts_revoke"]) && $arSettings["DISCOUNTS_ON"] == 'Y') {
            $result_revoke_discounts = CMaxyssWbprice::revokeDiscounts($Authorization = false, $items["discounts_revoke"]);
        }

        $res_agent = "CMaxyssWb::uploadAllStocks('".$cabinet."');";
        if(!empty($arrFilter))
            $res_agent = "CMaxyssWb::uploadAllStocks('".$cabinet."', ".var_export($arrFilter, true).");";

        return $res_agent;
    }

    public static function updateStock($Authorization, $items, $cabinet){
        $arResult = array();
        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnStockUpload", array(&$items, $Authorization));
        $event->send();
        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            if (!empty($items)) {
                foreach ($items as $wh => $skus){
                    $chunkItems = array_chunk($skus, 1000);
                    foreach ($chunkItems as $key_chunk =>$chunk_item) {
                        $data_string = array(
                                "stocks" => $chunk_item
                        );
                        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                        $arResult = CRestQueryWB::rest_stock_na($base_url = WB_BASE_URL, $data_string, "/api/v3/stocks/".$wh, $Authorization, $cabinet);
                        if($arResult['error']){
                            $data_error = ' ';
                            if(is_array($arResult['error']["data"]) && !empty($arResult['error']["data"])){
                                foreach ($arResult['error']["data"] as $data){
                                    $data_error .= $data['sku'].' ';
                                }
                            }
                            elseif(!empty($arResult['error']["data"]))
                            {
                                $data_error.= $arResult['error']["data"];
                            }
                            $ar_war = Array(
                                "MESSAGE" => GetMessage('MAXYSS_WB_UPLOAD_STOCK_ERROR').GetMessage('WB_MAXYSS_LK_TITLE_TAB').$cabinet.' - '.$arResult['error']["message"].'  - '.$data_error,
                                "TAG" => "MAXYSS_WB_".Cutil::translit($cabinet,"ru").'_'.$key_chunk,
                                "MODULE_ID" => "maxyss.wb",
                                'NOTIFY_TYPE' => 'E'
                            );
                            $ID_NOTIFY = CAdminNotify::Add($ar_war);
                        }
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPLOAD_STOCK', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $cabinet, "DESCRIPTION" => $data_string ));
                    }
                }
            }
        }
        return $arResult;
    }

    public static function getStockV3($Authorization = false, $wh, $barcodes){
        $arResult = array();
        $stocks = array();
        $chunkItems = array_chunk($barcodes, 45);
        foreach ($chunkItems as $key_chunk =>$chunk_item) {
            $data_string = array(
                "skus" => $chunk_item
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $arResult = CRestQueryWB::rest_stock_get($base_url = WB_BASE_URL, $data_string, "/api/v3/stocks/".$wh, $Authorization);
            if(isset($arResult["stocks"]) && !empty($arResult["stocks"])) $stocks = array_merge($stocks, $arResult["stocks"]);
        }
        return $stocks;
    }
    public static function getStock($Authorization = false, $stocks=array(), $skip = 0){
        // deprecated
        $arResult = array();
        $stocks_add = $stocks;
        $data_string = array();
        $arResult = CRestQueryWB::rest_stock_get($base_url = WB_BASE_URL, $data_string, "/api/v2/stocks?skip=".$skip."&take=1000&sort=article&order=asc", $Authorization);
        if(!empty($arResult['stocks'])) {
            $stocks_add = array_merge($stocks, $arResult['stocks']);
            if (count($arResult['stocks']) > 0 && count($arResult['stocks']) >= $skip) {
                $skip = $skip + 1000;
                return self::getStock($Authorization, $stocks_add, $skip);
            } else {
                return array('total' => $arResult['total'], 'stocks' => $stocks_add);
            }
        }else{
            return array('total' => $arResult['total'], 'stocks' => $stocks);
        }
    }

    public static function deleteNom($cabinet = 'DEFAULT', $nomId = 0){
//        /card/deleteNomenclature
        $arSettings = self::settings_wb($cabinet);
        $Authorization = $arSettings['AUTHORIZATION'];
        $supplierID = $arSettings['UUID'];

        $arInfo = CMaxyssWb::GetLicense();
        $data_string = array(
            "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
            "jsonrpc" => "2.0",
            'params' => array(
                "query"=>array(
                    "nomenclatureID"=> $nomId,
                    "supplierID"=> $supplierID
                ),
            )
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $arResult = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/deleteNomenclature", $Authorization);
        return \Bitrix\Main\Web\Json::decode($arResult);
    }
    // заказы

    public static function getStatusOrders($cabinet = 'DEFAULT', $id = 0){
        $arSettings = self::settings_wb($cabinet);
        $arWbOrderIds = array();
        $Authorization = $arSettings['AUTHORIZATION'];
        $limit = $arSettings['COUNT_ORDER'];
        $ids_stiker_get = array();
        CheckDirPath($_SERVER["DOCUMENT_ROOT"] . "/upload/wb/");

        if($limit>1000) $limit = 1000;
        $arFilterOrder = array (
            'PROPERTY_VAL_BY_CODE_MAXYSS_WB_CABINET' => $cabinet,
            '>ID' => $id,
            '!MAXYSS_WB_NUMBER' => false,
            ">=DATE_INSERT" => date($GLOBALS['DB']->DateFormatToPHP(CSite::GetDateFormat("SHORT")), time()-(864000))
        );
        $rsOrders = \CSaleOrder::GetList(
            array('ID' => 'ASC'),
            $arFilterOrder,
            false,
            array('nTopCount'=>$limit)
        );
        $select_counte = $rsOrders->SelectedRowsCount();

        while ($arOrder = $rsOrders->Fetch())
        {
            $arStatusOrder[$arOrder['ID']]=$arOrder['STATUS_ID'];
            $db_vals = CSaleOrderPropsValue::GetList(
                array(),
                array(
                    "ORDER_ID" => $arOrder['ID'],
                    "CODE" => 'MAXYSS_WB_NUMBER'
                )
            );
            if ($arVals = $db_vals->Fetch()){
                if(intval($arVals['VALUE'])>0) {
                    $arWbOrderIds[] = intval($arVals['VALUE']);
                    $arBitrixToWbIds[$arOrder['ID']] = intval($arVals['VALUE']);
                }

                $db_vals = CSaleOrderPropsValue::GetList(
                    array(),
                    array(
                        "ORDER_ID" => $arOrder['ID'],
                        "CODE" => 'MAXYSS_WB_STIKER'
                    )
                );
                if ($arVals = $db_vals->Fetch()){

                }
            }
            $new_id = $arOrder['ID'];
        }
        if(!empty($arWbOrderIds)){
            $data_string = array(
                'orders'=>$arWbOrderIds
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $res = CRestQueryWB::rest_order_na($base_url = WB_BASE_URL, $data_string, "/api/v3/orders/status", $Authorization);
            if(is_array($res) && !empty($res['orders'])){
                foreach ($res['orders'] as $wb_order){
                    if(is_array($arBitrixToWbIds))
                        $id_bx_order = array_search($wb_order['id'], $arBitrixToWbIds);
                    if($wb_order['supplierStatus'] == 'confirm') $ids_stiker_get[] = $wb_order['id'];
                    if($id_bx_order)
                        self::setStatusV3($wb_order, $id_bx_order, $arStatusOrder[$id_bx_order], $arSettings);
                }
            }

            /////////////////////////////
            if(!empty($ids_stiker_get)) {
                $data_string = \Bitrix\Main\Web\Json::encode(array('orders' => $ids_stiker_get));
                $width = 58; $height = 40;
                if($arSettings['STIKER_WIDTH'] == 40)
                {
                    $width = 40;
                    $height = 30;
                }

                $path = '/api/v3/orders/stickers?type='.str_replace('.', '', FILE_TYPE_STIKER).'&width='.$width.'&height='.$height;
                $api = new RestClient([
                    'base_url' => $base_url,
                    'curl_options' => array(
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_POSTFIELDS => $data_string,
                        CURLOPT_HEADER => TRUE,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'Content-Length: '.strlen($data_string),
                            'Authorization: ' . $Authorization,
                        )
                    )
                ]);
                $str_result = $api->post($path, []);
                if ($arSettings['LOG_ON'] == "Y") {
                    if (LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                        $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                            $str_result->response,
                            'UTF-8',
                            'windows-1251',
                            $errorMessage = ""
                        );
                    } else $descr = $str_result->response;
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'rest_supplies_get', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "$Authorization", "DESCRIPTION" => $descr . '; ' . $str_result->info->http_code));
                }
                if ($str_result->info->http_code == 200 && strlen($str_result->response) > 0) {
                    $res_stickers = \Bitrix\Main\Web\Json::decode($str_result->response);
                    if(isset($res_stickers["stickers"]) && !empty($res_stickers["stickers"])) {
                        foreach ($res_stickers["stickers"] as $val_sticker) {
                            $image = base64_decode($val_sticker["file"]);
                            $FPName = $val_sticker["orderId"] . FILE_TYPE_STIKER;
                            $FPPath = $_SERVER["DOCUMENT_ROOT"] . '/upload/wb/' . $FPName;
                            if(!file_exists($FPPath)) {
                                file_put_contents($FPPath, $image, LOCK_EX);

                                $id_bx_order = false;
                                if(is_array($arBitrixToWbIds))
                                    $id_bx_order = array_search($val_sticker['orderId'], $arBitrixToWbIds);
                                if($id_bx_order) {
                                    $order_bx = Order::Load($id_bx_order);
                                    if(is_object($order_bx)) {
                                        $propertyCollection = $order_bx->getPropertyCollection();
                                        foreach ($propertyCollection as $prop) {
                                            $value = '';
                                            switch ($prop->getField('CODE')) {
                                                case "MAXYSS_WB_STIKER":
                                                    $value = $val_sticker["partA"] . $val_sticker["partB"];
                                                    $value = trim($value);
                                                    $old_value = $prop->getValue();
                                                    break;
                                                default:
                                                    break;
                                            }

                                            if (!empty($value) && $old_value == '') {
                                                $prop->setValue($value);

                                                $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnStikerNew", array(&$order_bx, $val_sticker['orderId'], $val_sticker));
                                                $event->send();

                                                $order_bx->save();
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                } else{
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'get_stickers', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "$Authorization", "DESCRIPTION" => $str_result->info->http_code));
//                    $res = array('error' => $str_result->info->http_code);
                }
            }
            /////////////////////////////


        }
        if($select_counte == $limit){
            return "CMaxyssWb::getStatusOrders('".$cabinet."', ".$new_id.");";
        }
        else
        {
            return "CMaxyssWb::getStatusOrders('".$cabinet."', 0);";
        }
    }

    public static function  setStatusV3($order_wb = array(), $id_bx_order = 0, $status_bx, $arSettings){
        if($id_bx_order > 0 && !empty($order_wb)){
            $status_result = '';
            switch ($order_wb['supplierStatus'].$order_wb['wbStatus']){
                case 'new'.'waiting':
//                    echo '<pre>', print_r("Новый"), '</pre>' ; // 0
                    $status_result = 0;
                    break;
                case 'confirm'.'waiting':
//                    echo '<pre>', print_r("На сборке"), '</pre>' ;//  2  1 - Принял заказ
                    if(!$arSettings['TRIGGERS']['CLIENT_RECEIVED']) {
                        $status_result = 2;
                    }
                    break;
                case $order_wb['supplierStatus'].'canceled_by_client':
//                    echo '<pre>', print_r("Отмена заказа клиентом"), '</pre>' ;// 3
                    $status_result = 3;
                    break;
                case 'cancel'.$order_wb['wbStatus']:
//                    echo '<pre>', print_r("Отмена заказа поставщиком"), '</pre>' ;// 3
                    if(!$arSettings['TRIGGERS']['CANCEL_TRIGER']) {
                        $status_result = 1;
                    }
                    break;
                case $order_wb['supplierStatus'].'sold':
//                    echo '<pre>', print_r("Доставлено клиенту"), '</pre>' ; // 4  2 - Сборочное задание завершено
                    if(!$arSettings['TRIGGERS']['SKLAD_WB']) {
                        $status_result = 4;
                    }
                    break;
                case 'reject'.$order_wb['wbStatus']:
//                    echo '<pre>', print_r("Возврат товара"), '</pre>' ; // 6
                    $status_result = 6;
                    break;
                case 'complete'.$order_wb['wbStatus']:
//                    echo '<pre>', print_r("Транзит на ПВЗ"), '</pre>' ; // 5
                    $status_result = 5;
                    break;
                case $order_wb['supplierStatus'].'canceled':
//                    echo '<pre>', print_r("Не подобран / Отменен"), '</pre>' ; // 1  3 - Сборочное задание отклонено
                    if(!$arSettings['TRIGGERS']['CANCEL_TRIGER']) {
                        $status_result = 1;
                    }
                    break;
                default:
                    break;
            }
            if ($status_result != '' && $arSettings["STATUS_BY"][$status_result] && $arSettings["STATUS_BY"][$status_result] != $status_bx) {
                $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnStatusNew", array($id_bx_order, $order_wb));
                $event->send();
                $res_status = CSaleOrder::StatusOrder($id_bx_order, $arSettings["STATUS_BY"][$status_result]);
            }
        }
    }

    public static function getStatusOrdersOld($cabinet = 'DEFAULT', $step = 0){
        // deprecated

        $arSettings = self::settings_wb($cabinet);

        $Authorization = $arSettings['AUTHORIZATION'];
        $limit = $arSettings['COUNT_ORDER'];

        $skip = $step*$limit;

        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $date = date("Y-m-d", (time() - (10 * 86400)));
            $res = array();
            $data_string = array();
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $res = CRestQueryWB::rest_order_na($base_url = WB_BASE_URL, $data_string, "/api/v2/orders?date_start=" . $date . "T00%3A00%3A00%2B03%3A00&take=" . $limit . "&skip=" . $skip, $Authorization);
            foreach ($res['orders'] as $order_wb) {
                $arOrdersWb[] = intval($order_wb['orderId']);
            }

            if(!empty($arOrdersWb)){
                $data_string = array(
                    "orderIds" => $arOrdersWb
                );
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                $arResultStikers = CRestQueryWB::rest_stickers($base_url = WB_BASE_URL, $data_string, "/api/v2/orders/stickers", $Authorization);
                $arStiker = array();
                if(!empty($arResultStikers['data'])){
                    foreach ($arResultStikers['data'] as $stiker){
                           $arStiker[$stiker['orderId']] = $stiker;
                    }
                }
            }
            if (!empty($res) && count($res['orders']) >= 1) {
                foreach ($res['orders'] as $order_wb) {
                    if (intval($order_wb['orderId']) > 0 && intval($order_wb["chrtId"]) > 0) {
                        $id_bx_order = self::getOrder($order_wb['orderId']);
                        if ($id_bx_order > 0/* && $i <= 8*/) {
                            if(!empty($arStiker) && isset($arStiker[$order_wb['orderId']])) $ar_stiker = $arStiker[$order_wb['orderId']]; else $ar_stiker = array();
                            self::setStatus($order_wb, $id_bx_order, $arSettings, $ar_stiker);
                        }
                    }
                }
            }

            if (intval($res['total']) > ($skip + $limit)) $step++; else $step = 0;
        }

        return "CMaxyssWb::getStatusOrders('".$cabinet."', ".$step.");";
    }

    public static function  setStatus($order_wb = array(), $id_bx_order = 0, $arSettings, $ar_stiker = array()){
        if($id_bx_order > 0 && !empty($order_wb)){

            $order_bx = Order::Load($id_bx_order);
            $status_bx = $order_bx->getField("STATUS_ID");
//            $triggers_options = unserialize(Option::get(MAXYSS_WB_NAME, 'TRIGGERS', ''));
            $status_result = '';
            switch ($order_wb['status'].$order_wb['userStatus']){
                case '04':
//                    echo '<pre>', print_r("Новый"), '</pre>' ; // 0
                    $status_result = 0;
                    break;
                case '14':
//                    echo '<pre>', print_r("На сборке"), '</pre>' ;//  2  1 - Принял заказ

                    if(!$arSettings['TRIGGERS']['CLIENT_RECEIVED']) {
                        $status_result = 2;
                    }
                    break;
                case $order_wb['status'].'1':
//                    echo '<pre>', print_r("Отмена заказа клиентом"), '</pre>' ;// 3
                    $status_result = 3;
                    break;
                case '3'.$order_wb['userStatus']:
//                    echo '<pre>', print_r("Отмена заказа поставщиком"), '</pre>' ;// 3
                    if(!$arSettings['TRIGGERS']['CANCEL_TRIGER']) {
                        $status_result = 1;
                    }
                    break;
                case '22':
//                    echo '<pre>', print_r("Доставлено клиенту"), '</pre>' ; // 4  2 - Сборочное задание завершено
                    if(!$arSettings['TRIGGERS']['SKLAD_WB']) {
                        $status_result = 4;
                    }
                    break;
                case '23':
//                    echo '<pre>', print_r("Возврат товара"), '</pre>' ; // 6
                    $status_result = 6;
                    break;
                case '24':
//                    echo '<pre>', print_r("Транзит на ПВЗ"), '</pre>' ; // 5
                    $status_result = 5;
                    break;
                case '31':
//                    echo '<pre>', print_r("Не подобран / Отменен"), '</pre>' ; // 1  3 - Сборочное задание отклонено
                    if(!$arSettings['TRIGGERS']['CANCEL_TRIGER']) {
                        $status_result = 1;
                    }
                    break;
                default:
                    break;
            }
            if ($status_result != '' && $arSettings["STATUS_BY"][$status_result] && $arSettings["STATUS_BY"][$status_result] != $status_bx) {

                $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnStatusNew", array(&$order_bx, $order_wb, $ar_stiker));
                $event->send();

                $res_status = CSaleOrder::StatusOrder($id_bx_order, $arSettings["STATUS_BY"][$status_result]);
            }
            if(!empty($ar_stiker)) {
                $propertyCollection = $order_bx->getPropertyCollection();
                foreach ($propertyCollection as $prop) {
                    $value = '';
                    switch ($prop->getField('CODE')) {
                        case "MAXYSS_WB_STIKER":
                            $value = $ar_stiker['sticker']['wbStickerIdParts']['A'].$ar_stiker['sticker']['wbStickerIdParts']['B'];
                            $value = trim($value);
                            $old_value = $prop->getValue();
                            break;
                        default:
                    }

                    if (!empty($value) && $old_value == '') {
                        $prop->setValue($value);

                        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnStikerNew", array(&$order_bx, $order_wb, $ar_stiker));
                        $event->send();

                        $order_bx->save();
                        break;
                    }
                }
            }
        }
    }

    public static function putStatusOrders($id, $status, $Authorization = false)
    {
        // deprecated

        if($id !='') {
            if (!$Authorization) $Authorization = AUTHORIZATION;
            $res = array();
            $data_string = array(
                array(
                    "orderId" => strval($id),
                    "status" => intval($status)
                )
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $res = CRestQueryWB::putStatus($base_url = WB_BASE_URL, $data_string, "/api/v2/orders", $Authorization);

            if($res['success']){
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'PUT_STATUS_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $id, "DESCRIPTION" => 'success status '.$status ));
            }
            else
            {
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'PUT_STATUS_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $id, "DESCRIPTION" => serialize($res) ));
            }
        }
        else
        {
            $res = false;
        }
        return $res;
    }

    public static function loadNewOrders($cabinet = 'DEFAULT', $print=false){

        $arSettings = self::settings_wb($cabinet);
        $Authorization = $arSettings['AUTHORIZATION'];

        $file_log_order = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_order".$cabinet.".txt";
        file_put_contents($file_log_order, print_r("DATA - " . date('Y-m-d H:i:s') , true) . PHP_EOL);
        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $res = array();
            $res = CRestQueryWB::rest_order_na($base_url = WB_BASE_URL, '', "/api/v3/orders/new", $Authorization);

            file_put_contents($file_log_order, print_r($res, true) . PHP_EOL, FILE_APPEND);
            if ($res['note'])
                file_put_contents($file_log_order, print_r("NOTE - " . date('Y-m-d H:i:s') . $res['note'], true) . PHP_EOL, FILE_APPEND);
            elseif ($res['error'])
                file_put_contents($file_log_order, print_r("ERROR - " . date('Y-m-d H:i:s') . $res['errorText'].' http_code '.$res['http_code'], true) . PHP_EOL, FILE_APPEND);
            elseif ($res['errors'])
                file_put_contents($file_log_order, print_r("ERROR - " . date('Y-m-d H:i:s') . $res['errors'], true) . PHP_EOL, FILE_APPEND);
            else {
                $order_user = self::getUser($cabinet);
                $i = 0;
                if(!empty($res['orders'])) {
                    foreach ($res['orders'] as $order_wb) {
                    $i++;
                    if (intval($order_wb['id']) > 0 && intval($order_wb["chrtId"]) > 0) {
                        $id_bx_order = self::getOrder($order_wb['id']);
                        if ($id_bx_order == 0/* && $i <= 8*/) {
                            $id_bx_order_new = self::createOrder($order_wb, $order_user, $arSettings, $cabinet);
                        }
                    }
                }
            }
        }
    }
        return "CMaxyssWb::loadNewOrders('".$cabinet."');";
    }

    public static function getUser($cabinet = 'DEFAULT'){
        $user__defaulte = self::get_setting_wb("USER_DEFAULTE", $cabinet);
        $user_is_it = array();
        $rsUser = CUser::GetById($user__defaulte);
        if($arUser = $rsUser->Fetch()){
            $user_id = $user__defaulte;
            $user_is_it = $arUser;
        }else{
            $user_id = '';
        }
        return array(
            "USER_ID" =>$user_id,
            "USER" =>$arUser,
        );
    }

    public static function getProduct($product, $order_wb, $arSettings){
        $prop_flag = '';
        $result = array();
        CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/upload/wb/");
            $prod['chrt_id'] = $product;
            $arFilterProd = array("PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB" => $prod['chrt_id']);

            $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "IBLOCK_ID", 'CATALOG_XML_ID', 'AVAILABLE', 'WEIGHT', 'LENGTH', 'WIDTH', 'HEIGHT', 'VAT_ID', 'VAT_INCLUDED');
            $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);
            if($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();
                $result[$prod['chrt_id']] = $arFields;
            }
            else
            {
                if($order_wb['barcode'] != '' && $arSettings["SHKOD"]!=''){

                    $iblock_shkod = $arSettings["SHKOD"];
                    $arFilterProd = array("PROPERTY_" . $iblock_shkod => $order_wb['barcode']);
                    $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);

                    if ($ob = $res->GetNextElement()) {
                        $arFields = $ob->GetFields();
                        $result[$prod['chrt_id']] = $arFields;
                    }
                }
            }

        return $result;
    }

    public static function createOrder(&$order_wb = array(), $order_user = array(), $arSettings, $cabinet){
        $orderId = 0;
        if(!empty($order_wb) && !empty($order_user)){
            if($order_wb['chrtId']){
                $product = $order_wb['chrtId'];
                // get info product
                $order_wb["products_bitrix"] = self::getProduct($product, $order_wb, $arSettings);

                if(!empty($order_wb["products_bitrix"])){
                    if($order_user["USER_ID"]<=0)
                    {
                        $login = ($order_wb['userInfo']['userId'])? $order_wb['userInfo']['userId'] : $order_wb['orderId'];
                        $rsUser = CUser::GetByLogin($login);
                        if ($arUser = $rsUser->Fetch()) {
                            $user_new_id = $arUser['ID'];
                        } else {
                            $user = new CUser;

                            $arFields = Array(
                                "NAME" => ($order_wb['userInfo']['fio'])? $order_wb['userInfo']['fio'] : $order_wb['orderId'],
                                "EMAIL" => ($order_wb['userInfo']['phone'])? $order_wb['userInfo']['phone']. $order_wb['orderId'] . "@emailwb.ru" : "wb" . $order_wb['orderId'] . "@emailwb.ru",
                                "LOGIN" =>  ($order_wb['userInfo']['userId'])? $order_wb['userInfo']['userId'] : $order_wb['orderId'],
                                "PASSWORD" => "qwerty123456",
                                "CONFIRM_PASSWORD" => "qwerty123456",
                                "PHONE" => $order_wb['userInfo']['phone'],
                            );

                            $user_new_id = $user->Add($arFields);
                            if (!intval($user_new_id) > 0)
                                $user_new_id = 1;
                        }
                    }
                    else
                    {
                        $user_new_id = $order_user["USER_ID"];
                    }

                    $order_bitrix = Order::create($arSettings["SITE"], $user_new_id);
                    $order_bitrix->setPersonTypeId($arSettings["PERSON_TYPE"]);
                    $order_bitrix->setField('CURRENCY', $arSettings["VALUTA_ORDER"]);
                    $order_bitrix->setField('USER_DESCRIPTION', $order_wb['officeAddress'].$order_wb['deliveryAddress']);
                    $order_bitrix->setField("STATUS_ID", $arSettings["STATUS_BY"][0]);
                    $order_bitrix->setField('EMP_STATUS_ID', 1);



                    $basket = Basket::create($arSettings["SITE"]);

                    foreach ($order_wb["products_bitrix"] as $product) {
                        $item = $basket->createItem('catalog', $product['ID']);

                        $price = floatval($order_wb["convertedPrice"]/100);

                        $item->setFields(array(
                            'QUANTITY' => 1,
                            'CURRENCY' => $arSettings["VALUTA_ORDER"],
                            'LID' => $arSettings["SITE"],
                            'BASE_PRICE' => $price,
                            'PRICE' => $price,
                            'CUSTOM_PRICE' => 'Y',
                            'NAME' => $product['NAME'],
                            'DETAIL_PAGE_URL' => $product['DETAIL_PAGE_URL'],
                            'PRODUCT_XML_ID' => $product['EXTERNAL_ID'],
                            'CATALOG_XML_ID' => $product['IBLOCK_EXTERNAL_ID'],
                            'WEIGHT' => $product['WEIGHT'],
                            'VAT_RATE' => self::vatRateConvert($product['VAT_ID']),
                            'VAT_INCLUDED' => $product['VAT_INCLUDED'],
                            'DIMENSIONS' => serialize(array("WIDTH" => $product["WIDTH"], "HEIGHT" => $product["HEIGHT"], "LENGTH"=> $product["LENGTH"])),
                        ));
                        if($arSettings['CALLBACK_BX'] == 'Y') $item->setFields(array('PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'));
                    }

                    $order_bitrix->setBasket($basket);

                    $delivery_service = $arSettings['DELIVERY_SERVICE'];

                    $shipmentCollection = $order_bitrix->getShipmentCollection();
                    $shipment = $shipmentCollection->createItem();
                    if($delivery_service == '') $delivery_service = Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
                    $service = Delivery\Services\Manager::getById($delivery_service);
                    $shipment->setFields(array(
                        'DELIVERY_ID' => $service['ID'],
                        'DELIVERY_NAME' => $service['NAME'],
                    ));
                    $shipmentItemCollection = $shipment->getShipmentItemCollection();

                    foreach ($basket as $basketItem) {
                        $shipmentItem = $shipmentItemCollection->createItem($basketItem);
                        $shipmentItem->setQuantity($basketItem->getQuantity());
                    }

                    $paysystem = $arSettings['PAYSYSTEM'];
                    $paymentCollection = $order_bitrix->getPaymentCollection();
                    $payment = $paymentCollection->createItem();
                    $paySystemService = PaySystem\Manager::getObjectById($paysystem);
                    $payment->setFields(array(
                        'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
                        'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
                        'SUM' => $price,
                    ));


                    $propertyCollection = $order_bitrix->getPropertyCollection();
                    foreach ($propertyCollection as $prop) {
                        $value = '';
                        switch ($prop->getField('CODE')) {
                            case "MAXYSS_WB_NUMBER":
                                $value = $order_wb["id"];
                                $value = trim($value);
                                break;
                            case "MAXYSS_WB_RID":
                                $value = $order_wb['rid'];
                                $value = trim($value);
                                break;
//                            case "MAXYSS_WB_STIKER":
//                                $value = $order_wb["products_bitrix"]['sticker']['wbStickerIdParts']['A'].' '.$order_wb['sticker']['wbStickerIdParts']['B'];
//                                $value = trim($value);
//                                break;
                            case "MAXYSS_WB_DELIVERY_TYPE":
                                $value = $order_wb['deliveryType'];
                                $value = trim($value);
                                break;
                            case "MAXYSS_WB_CABINET":
                                $value = $cabinet;
                                $value = trim($value);
                                break;
                            case "MAXYSS_WB_WAREHOUSEID":
                                $value = $order_wb['warehouseId'];
                                $value = trim($value);
                                break;

                            default:
                        }

                        if (!empty($value)) {
                            $prop->setValue($value);
                        }
                    }

                    if(!empty($order_user["USER"])){
                        $nameProp = $propertyCollection->getUserEmail();
                        $nameProp->setValue($order_user["USER"]['EMAIL']);

                    }else {
                        $nameProp = $propertyCollection->getPayerName();
                        $nameProp->setValue(($order_wb['userInfo']['fio'])? $order_wb['userInfo']['fio'] : $order_wb["orderId"]);
                    }

                        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnOrderNew", array(&$order_bitrix, $order_wb, $params = array()));
                        $event->send();

                    if($result = $order_bitrix->save()){
                        file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_WB_NAME."/log_order.txt", print_r('result ok', true).PHP_EOL, FILE_APPEND);

                        if (!$result->isSuccess()) {
                            $eventLog = new \CEventLog;
                            $eventLog->Add(array("SEVERITY" => 'INFO',"AUDIT_TYPE_ID" => 'CREATE_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $order_wb["orderId"], "DESCRIPTION" => $result->getErrorMessages()));
                        }
                        else
                        {
                            $eventLog = new \CEventLog;
                            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'CREATE_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $order_bitrix->getField('ID'), "DESCRIPTION" => "OK SAVE" ));
                        }

                    }
                    else
                    {
                        file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_WB_NAME."/log_order.txt", print_r($result->getErrorMessages(), true).PHP_EOL, FILE_APPEND);
                    }

                    unset($user_new_id);
                    file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_WB_NAME."/log_order.txt", print_r($result, true).PHP_EOL, FILE_APPEND);

                }
                else
                {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'NOT_FOUND_PRODUCT_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $order_wb["orderId"], "DESCRIPTION" => $product ));
                }


        }
    }
        return $orderId;
    }

    public static function vatRateConvert($vat_id = 1){

        $result = 0.00;
        $dbRes = CCatalogVat::GetListEx(array(), array('ID'=>$vat_id));
        if ($arRes = $dbRes->Fetch()) {
            $result = $arRes['RATE']/100;
        }

        return $result;
    }

    public static function getOrder($id_wb_order = 0){
        if($id_wb_order != 0){
            // find order to Bitrix
            $arFilterOrder = array (
                'PROPERTY_VAL_BY_CODE_MAXYSS_WB_NUMBER' => $id_wb_order,
            );
            $rsOrders = \CSaleOrder::GetList(
                array('DATE_INSERT' => 'DESC'),
                $arFilterOrder
            );
            if ($arOrder = $rsOrders->Fetch())
            {
                $id_bx_order = $arOrder['ID'];
            }
            else
            {
                $id_bx_order = 0;
            }
        }else{
            $id_bx_order = -1;
        }
        return $id_bx_order;
    }

    public static function buttonPackageLabelWbDetail(){

        if (strpos($GLOBALS["APPLICATION"]->GetCurPage(), 'shop/orders/details/') > 0){
            $order_id = explode('/',$GLOBALS["APPLICATION"]->GetCurPage());

            $flag_button = false;

            $arFilterOrder = array (
                'ID' => $order_id[4],
            );
            $rsOrders = \CSaleOrder::GetList(
                array('DATE_INSERT' => 'DESC'),
                $arFilterOrder
            );
            if ($arOrder = $rsOrders->Fetch())
            {
                $obProps = Bitrix\Sale\Internals\OrderPropsValueTable::getList(array('filter' => array('ORDER_ID' => $arOrder["ID"])));
                while($prop = $obProps->Fetch()){
                    if($prop['CODE'] == "MAXYSS_WB_NUMBER") {
                        $svg = $prop['VALUE'].FILE_TYPE_STIKER;
                        $FPPath = $_SERVER["DOCUMENT_ROOT"] . '/upload/wb/' . $svg;
                        if($prop["VALUE"] != '') $flag_button = true;
                        if(!file_exists($FPPath))  $arWbOrders = array(intval($prop['VALUE']));  /// типа что-то не сработало и файл не записался
                    }
                }
//                $flag_button = true;
            }
            if($flag_button) {

                $obBasket = \Bitrix\Sale\Basket::getList(array('filter' => array('ORDER_ID' => $order_id[4])));
                if($bItem = $obBasket->Fetch()){
                    $id_element = $bItem["PRODUCT_ID"];
                }
                $Authorization = false;
                $supplierID = false;

                $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnGetCadrList", array($id_element, &$Authorization, &$supplierID, $params = array()));
                $event->send();
                if(!empty($arWbOrders)) {
                    /// позже посмотрим
                }
                ob_start();
                ?>
                <script type="text/javascript">
                    var htm = '<style>body{margin: 0; padding : 0;} @media print {@page{size : 1.58in 1.18in; margin: 0px; padding : 0px;}}</style><img width=185 height=134 src="/upload/wb/<?=$svg?>">';
                    BX.addCustomEvent("onAjaxSuccessFinish", function (params) {
                        var pathname = window.location.pathname;
                        if (!$('a').is('#btnPrintLabelWb')) {
                            $('.pagetitle-align-right-container').append(
                                '<a class="ui-btn ui-btn-primary" href="javascript:void(0)" onclick="CallPrintWb()" ' +
                                'id="btnPrintLabelWb" ' +
                                'title="<?=GetMessage('WB_MAXYSS_BUTTON_LABEL_ORDER')?>" >' +
                                '<?=GetMessage("WB_MAXYSS_BUTTON_LABEL_ORDER")?>' + '</a>'
                            );
                        }
                    });
                </script>

                <?
                $sContent = ob_get_clean();
                $GLOBALS['APPLICATION']->AddHeadString($sContent);
            }
        }

        if (defined("ADMIN_SECTION") && $GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_view.php"){
            $order_id = intval($_REQUEST['ID']);
            $flag_button = false;
            $arFilterOrder = array (
                'ID' => $order_id,
            );
            $rsOrders = \CSaleOrder::GetList(
                array('DATE_INSERT' => 'DESC'),
                $arFilterOrder
            );
            if ($arOrder = $rsOrders->Fetch())
            {
                $obProps = Bitrix\Sale\Internals\OrderPropsValueTable::getList(array('filter' => array('ORDER_ID' => $arOrder["ID"])));
                while($prop = $obProps->Fetch()){
                    if($prop['CODE'] == "MAXYSS_WB_NUMBER") {
                        $svg = $prop['VALUE'].FILE_TYPE_STIKER;
                        $FPPath = $_SERVER["DOCUMENT_ROOT"] . '/upload/wb/' . $svg;
                        if($prop["VALUE"] != '') $flag_button = true;
                        if(!file_exists($FPPath))  $arWbOrders = array(intval($prop['VALUE']));  /// типа что-то не сработало и файл не записался
                    }
}
            }
            if($flag_button) {


                $obBasket = \Bitrix\Sale\Basket::getList(array('filter' => array('ORDER_ID' => $order_id)));
                if($bItem = $obBasket->Fetch()){
                    $id_element = $bItem["PRODUCT_ID"];
                }
                $Authorization = false;
                $supplierID = false;

                $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnGetCadrList", array($id_element, &$Authorization, &$supplierID, $params = array()));
                $event->send();

                if(!empty($arWbOrders)) {
                    /// позже посмотрим
                }
                ob_start();
                ?>

                <script type="text/javascript">
                    var htm = '<style>body{margin: 0; padding : 0;} img{display:block;width:100%;height:auto;} @media print {@page{size : 1.58in 1.18in; margin: 0px; padding : 0px;}}</style><img width=185 height=134 src="/upload/wb/<?=$svg?>">';

                    BX.addCustomEvent("onAjaxSuccessFinish", function (params) {
                        var pathname = window.location.pathname;
                        if (pathname == "/bitrix/admin/sale_order_view.php" && !$('a').is('#btnPrintLabelWb')) {
                            $('#tbl_sale_order_result_div .adm-list-table-top').append(
                                '<a class="adm-btn adm-btn-save" href="javascript:void(0)" onclick = "CallPrintWb()" ' +
                                'id="btnPrintLabelWb" ' +
                                'title="<?=GetMessage('WB_MAXYSS_BUTTON_LABEL_ORDER')?>" >' +
                                '<?=GetMessage("WB_MAXYSS_BUTTON_LABEL_ORDER")?>' + '</a>'
                            );
                        }
                    });


                    var printWbLabelDetail = false;

                    function initPackageWbLabelDetail() {
                        var pathName = window.location.pathname;
                        if (pathName == "/bitrix/admin/sale_order_view.php" && !printWbLabelDetail) {
                            $(function () {
                                $('.adm-detail-toolbar-right').append(
                                    '<a class="adm-btn adm-btn-save" style="margin-right: 12px" href="javascript:void(0)" onclick = "CallPrintWb()" ' +
                                    'id="btnPrintLabelWb" ' +
                                    'title="<?=GetMessage('WB_MAXYSS_BUTTON_LABEL_ORDER')?>" >' +
                                    '<?=GetMessage("WB_MAXYSS_BUTTON_LABEL_ORDER")?>' + '</a>'
                                );

                                printWbLabelDetail = true;
                            });
                        }
                    }

                    initPackageWbLabelDetail();
                </script>
                <?
                $sContent = ob_get_clean();
                $GLOBALS['APPLICATION']->AddHeadString($sContent);
            }
        }

    }

    public static function buttonPackageLabelWb(){
        CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/upload/wb/");
        if ($GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/"){
            CJSCore::Init(array('maxyss_wb'));
            ob_start();
            ?>
            <script type="text/javascript">
                var printLabelWb = false;
                function initWbLabel() {
                    // var pathName = window.location.pathname;
                    if (!printLabelWb) {
                        $(function () {
                            $('#toolbar_order_list').append(
                                '<a class="ui-btn ui-btn-primary" style="margin-right: 12px" href="javascript:void(0)" onclick = "ListPrintWb()" ' +
                                'id="btnPrintLabelListWb" ' +
                                'title="<?=GetMessage('WB_MAXYSS_BUTTON_LABEL_ORDER')?>" >' +
                                '<?=GetMessage("WB_MAXYSS_BUTTON_LABEL_ORDER")?>' + '</a>'
                            );
                            printLabelWb = true;
                        });
                    }
                }
                initWbLabel();
            </script>
            <?
            $sContent = ob_get_clean();
            $GLOBALS['APPLICATION']->AddHeadString($sContent);
        }
        if (defined("ADMIN_SECTION") && $GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order.php"){
            CJSCore::Init(array('maxyss_wb'));

            ob_start();
            ?><script type="text/javascript">
                BX.addCustomEvent("onAjaxSuccessFinish", function(params){
                    var pathname = window.location.pathname;
                    if(pathname == "/bitrix/admin/sale_order.php" && !$('a').is('#btnPrintLabelListWb')){
                        $('#tbl_sale_order_result_div .adm-list-table-top').append(
                            '<a class="adm-btn adm-btn-save" style="margin-right: 12px" href="javascript:void(0)" onclick = "ListPrintWb()" ' +
                            'id="btnPrintLabelListWb" ' +
                            'title="<?=GetMessage('WB_MAXYSS_BUTTON_LABEL_ORDER')?>" >' +
                            '<?=GetMessage("WB_MAXYSS_BUTTON_LABEL_ORDER")?>' + '</a>'
                        );
                    }
                });
                BX.addCustomEvent("onAjaxSuccess", function(params){
                    var pathname = window.location.pathname;
                    if(pathname == "/bitrix/admin/sale_order.php" && !$('a').is('#btnPrintLabelListWb')){
                        $('#tbl_sale_order_result_div .adm-list-table-top').append(
                            '<a class="adm-btn adm-btn-save" style="margin-right: 12px" href="javascript:void(0)" onclick = "ListPrintWb()" ' +
                            'id="btnPrintLabelListWb" ' +
                            'title="<?=GetMessage('WB_MAXYSS_BUTTON_LABEL_ORDER')?>" >' +
                            '<?=GetMessage("WB_MAXYSS_BUTTON_LABEL_ORDER")?>' + '</a>'
                        );
                    }
                });

                var printLabelWb = false;
                function initWbLabel() {
                    var pathName = window.location.pathname;
                    if (pathName == "/bitrix/admin/sale_order.php" && !printLabelWb) {
                        $(function () {
                            $('#tbl_sale_order_result_div .adm-list-table-top').append(
                                '<a class="adm-btn adm-btn-save" style="margin-right: 12px" href="javascript:void(0)" onclick = "ListPrintWb()" ' +
                                'id="btnPrintLabelListWb" ' +
                                'title="<?=GetMessage('WB_MAXYSS_BUTTON_LABEL_ORDER')?>" >' +
                                '<?=GetMessage("WB_MAXYSS_BUTTON_LABEL_ORDER")?>' + '</a>'
                            );
                            printLabelWb = true;
                        });
                    }
                }
                initWbLabel();
            </script>
            <?
            $sContent = ob_get_clean();
            $GLOBALS['APPLICATION']->AddHeadString($sContent);
        }
    }
}


class CCustomTypeMaxyssWBProp{

    public static function GetUserTypeMaxyssWBProp() {
        return array(
            'PROPERTY_TYPE'           => 'S',
            'USER_TYPE'             	=> 'maxyss_wb',
            'DESCRIPTION'           	=> GetMessage('WB_MAXYSS_CATEGORY_NAME_TEXT'),
            'GetPropertyFieldHtml'  	=> array('CCustomTypeMaxyssWBProp', 'GetPropertyFieldHtml'),
            'GetAdminListViewHTML'  	=> array('CCustomTypeMaxyssWBProp', 'GetAdminListViewHTML'),
            'ConvertToDB'           	=> array('CCustomTypeMaxyssWBProp', 'ConvertToDB'),
            'ConvertFromDB'         	=> array('CCustomTypeMaxyssWBProp', 'ConvertToDB')
        );
    }
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName) {

        $ID = intval($_REQUEST['ID']); //
        $IBLOCK_ID = intval($_REQUEST['IBLOCK_ID']); //
        global $APPLICATION;

        if ($APPLICATION->GetCurPage() != '/bitrix/admin/iblock_list_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/iblock_element_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/cat_product_admin.php') {
            if (!wb_is_curl_installed()) {
                ?><div style="color: red"><?=GetMessage("CURL_NOT_INSTALLED")?></div><br><?
            }
            if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) < "R")
            {
                echo '<input type="text" readonly title="' . GetMessage('CATEGORY_ENTER_TEXT') . '" placeholder="' . GetMessage('WB_MAXYSS_CATEGORY_SEARCH_TEXT') . '" name="' . $strHTMLControlName["DESCRIPTION"] . '" value="' . $value["DESCRIPTION"] . '" data-lang="' . LANGUAGE_ID . '">';
                echo '<input data-category-wb="" id="object_wb" type="text" readonly name="' . $strHTMLControlName['VALUE'] . '" value="' . htmlspecialcharsbx($value['VALUE']) . '">';
                ?><div style="color: red"><?=GetMessage("WB_MAXYSS_NOT_RIGHT_EDIT_ATTR")?></div><br><?
            }
            else
            {
                echo '<input type="text" id="autocomplete_wb" onkeyup="get_object_list( $(this).val() );" class="autocomplete_wb"  title="' . GetMessage('CATEGORY_ENTER_TEXT') . '" placeholder="' . GetMessage('WB_MAXYSS_CATEGORY_SEARCH_TEXT') . '" name="' . $strHTMLControlName["DESCRIPTION"] . '" value="' . $value["DESCRIPTION"] . '" data-lang="' . LANGUAGE_ID . '">';
                echo '<input data-category-wb="" id="object_wb" type="text" readonly name="' . $strHTMLControlName['VALUE'] . '" value="' . htmlspecialcharsbx($value['VALUE']) . '">';
                echo '<div class="predmet_dialog"></div>';
                /*if (strlen($value['VALUE']) > 5) echo '<input type="button" class="wb_upload" id="wb_upload" onclick="upload_card(' . $ID . ')" value="' . GetMessage("WB_MAXYSS_UPLOAD_WB") . '"><input type="button" class="wb_delete" id="wb_delete" onclick="delete_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_DELETE_WB') . '"><input type="button" class="wb_data" id="wb_data" onclick="data_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_GET_ID_WB') . '">';*/
                if (strlen($value['VALUE']) > 5) echo '<input type="button" class="wb_upload" id="wb_upload" onclick="upload_card(' . $ID . ')" value="' . GetMessage("WB_MAXYSS_UPLOAD_WB") . '"><input type="button" class="wb_data" id="wb_data" onclick="data_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_GET_ID_WB') . '"><input type="button" class="wb_data" id="wb_photo" onclick="upload_photo(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_UPLOAD_WB_PHOTO') . '">';
                else echo '<input type="button" class="wb_data" id="wb_data" onclick="data_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_GET_ID_WB') . '">';
                ?>
                <script type="text/javascript">
                    let lang = "<?=LANGUAGE_ID?>";
                    let name_prop = "<?=$strHTMLControlName['VALUE']?>";
                    let atribute_wb = $('[name="' + name_prop + '"]');
                    let ib_base = 0;
                    let ib_offers = 0;

                </script>
                <?
                $sinc_set = array();
                global $DB;
                $row = $DB->Query("SELECT * FROM b_option WHERE NAME='MAXYSS_SINC_WB_ATTR'")->Fetch();
                if (strlen($row['VALUE']) > 0)
                    $sinc_set = unserialize($row['VALUE']);

                // основной иблок
                $iblock_info = CCatalog::GetByIDExt($IBLOCK_ID);

                if (is_array($iblock_info)) {
                    if ($iblock_info['PRODUCT_IBLOCK_ID'] == $IBLOCK_ID || ($iblock_info['PRODUCT_IBLOCK_ID'] == 0 && $iblock_info['OFFERS_IBLOCK_ID'] == 0)) {
                        $iblock_id = $IBLOCK_ID;
                        $iblock_offers_id = $iblock_info["OFFERS_IBLOCK_ID"];
                        ?>
                        <script>ib_offers = <?=$iblock_offers_id?>;</script><?
                    } else {
                        $iblock_id = $iblock_info['PRODUCT_IBLOCK_ID'];
                        $iblock_offers_id = $IBLOCK_ID;
                        ?>
                        <script>ib_offers = <?=$iblock_offers_id?>;</script><?
                    }
                } else {
                    $iblock_id = $IBLOCK_ID;
                }
                ?>
                <script type="text/javascript">
                    let sinc_set = <?=CUtil::PhpToJSObject($sinc_set)?>;
                    ib_base = <?=$iblock_id?>;
                </script>
                <?
                if (strlen($value['VALUE'])) {
                    $addin_card = CUtil::JsObjectToPhp($value['VALUE']);
                    $object_wb = $addin_card['object'];
                    unset($addin_card['object']);
                    foreach ($addin_card as $val) {
                        foreach ($val['params'] as $v) {
                            $addin_set[$val['type']][] = $v['value'] ? $v['value'] : $v['count'];
                        }
                    }


                    ?>
                    <script type="text/javascript">
                        get_object_new_api_content('<?=$object_wb?>', <?=CUtil::PhpToJSObject($addin_set)?>);
                    </script>
                    <?
                }
            }
        } else {
            echo '';
        }

    }
    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        if(strlen($value["VALUE"])>0)
            return htmlspecialcharsex($value["VALUE"]);
        else
            return '&nbsp;';
    }
//    function GetAdminListEditHTML($arUserField, $arHtmlControl)
//    {
//        if(strlen($value["VALUE"])>0)
//            return htmlspecialcharsex($value["VALUE"]);
//        else
//            return '&nbsp;';
//    }
    static function ConvertToDB($arProperty, $value){
        return $value;
    }
    static function ConvertFromDB($arProperty, $value){
        return $value;
    }

    public static function GetObjectWb($pattern, $lang){
        $data_string = array(
            "pattern" => $pattern,
            "lang" => $lang,
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        if (wb_is_curl_installed()) {
            $arRes = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/ns/characteristics-configurator-api/content-configurator/api/v1/config/get/object/list");
            echo $arRes;
//            echo \Bitrix\Main\Web\Json::encode($arRes);
        } else {
            echo "cURL is <span style=\"color:#dc4f49\">not installed</span> on this server";
        }
    }

}
?>