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
    Bitrix\Main\Entity;
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
CUtil::InitJSCore(array('window'));
CJSCore::Init(array("jquery"));

if (defined("ADMIN_SECTION")) {
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/pdfmake.js");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/vfs.js");

}
if ($GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/"){
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/pdfmake.js");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.wb/vfs.js");
}
//if (strpos($GLOBALS["APPLICATION"]->GetCurPage(), 'shop/orders/details/') > 0){
//
//}

IncludeModuleLangFile(__FILE__);
define("MAXYSS_WB_NAME", "maxyss.wb");

CJSCore::RegisterExt('maxyss_wb', array(
    'js' => '/bitrix/tools/'.MAXYSS_WB_NAME.'/script.js',
    'css' => '/bitrix/tools/'.MAXYSS_WB_NAME.'/style.css',
    'lang' => '/bitrix/modules/'.MAXYSS_WB_NAME.'/lang/'.LANGUAGE_ID.'/include.php',
//    'rel' => array('popup', 'ajax', 'fx', 'ls', 'date', 'json')
));
CJSCore::Init(array('maxyss_wb'));

define('WB_BASE_URL', 'https://suppliers-api.wildberries.ru');
define('X_AUTH_TOKEN', Option::get(MAXYSS_WB_NAME, "SKLAD_TOKEN", ""));
define('AUTHORIZATION', Option::get(MAXYSS_WB_NAME, "AUTHORIZATION", ""));


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
        "CAddinMaxyssWB" => 'classes/CAddinMaxyssWB.php'
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
    public static function settings_wb()
    {
        $arSettings = array();
        $arSettings['IBLOCK_TYPE'] = Option::get(MAXYSS_WB_NAME, "IBLOCK_TYPE", "");
        $arSettings['IBLOCK_ID'] = Option::get(MAXYSS_WB_NAME, "IBLOCK_ID", "");
        $arSettings['DESCRIPTION'] = Option::get(MAXYSS_WB_NAME, "DESCRIPTION", "DETAIL_TEXT");
        $arSettings['BRAND'] = Option::get(MAXYSS_WB_NAME, "BRAND_PROP", "");
        $arSettings['SHKOD'] = Option::get(MAXYSS_WB_NAME, "SHKOD", "");
        $arSettings['ARTICLE'] = Option::get(MAXYSS_WB_NAME, "ARTICLE", "");
        $arSettings['FILTER_PROP'] = Option::get(MAXYSS_WB_NAME, "FILTER_PROP", "");
        $arSettings['FILTER_PROP_ID'] = Option::get(MAXYSS_WB_NAME, "FILTER_PROP_ID", "");
        $arSettings['BASE_PICTURE'] = Option::get(MAXYSS_WB_NAME, "BASE_PICTURE", "DETAIL_PICTURE");
        $arSettings['MORE_PICTURE'] = Option::get(MAXYSS_WB_NAME, "MORE_PICTURE", "");
        $arSettings['SERVER_NAME'] = Option::get(MAXYSS_WB_NAME, "SERVER_NAME", $_SERVER["HTTP_HOST"]);
        $arSettings['SKLAD'] = Option::get(MAXYSS_WB_NAME, "SKLAD", '');
        $arSettings['SKLAD_TOKEN'] = Option::get(MAXYSS_WB_NAME, "SKLAD_TOKEN",'');
        $arSettings['LAND'] = Option::get(MAXYSS_WB_NAME, "LAND",'');


        $arSettings['PRICE_TYPE'] = Option::get(MAXYSS_WB_NAME, "PRICE_TYPE", "");
        $arSettings['PRICE_MAX_MIN'] = Option::get(MAXYSS_WB_NAME, "PRICE_MAX_MIN", "");
        $arSettings['PRICE_PROP'] = Option::get(MAXYSS_WB_NAME, "PRICE_PROP", "");
        $arSettings['PRICE_TYPE_PROP'] = Option::get(MAXYSS_WB_NAME, "PRICE_TYPE_PROP", "");
        $arSettings['PRICE_TYPE_NO_DISCOUNT'] = Option::get(MAXYSS_WB_NAME, "PRICE_TYPE_NO_DISCOUNT", "");

        $rsSites = CSite::GetList($by="sort", $order="desc", Array("DEFAULT" => "Y"));
        if ($arSite = $rsSites->Fetch())
        {
            $arSettings['SITE'] = $arSite["LID"];
        }
        $arSettings['SKLAD_ID'] = unserialize(Option::get(MAXYSS_WB_NAME, "SKLAD_ID", ""));

        $arSettings['ACTIVE_ORDER_ON'] = Option::get(MAXYSS_WB_NAME, "ACTIVE_ORDER_ON", '');
        $arSettings['PERIOD_ORDER'] = Option::get(MAXYSS_WB_NAME, "PERIOD_ORDER", "600");
        $arSettings['COUNT_ORDER'] = Option::get(MAXYSS_WB_NAME, "COUNT_ORDER", "500");
        $arSettings['VALUTA_ORDER'] = Option::get(MAXYSS_WB_NAME, "VALUTA_ORDER", "");
        $arSettings['PERSON_TYPE'] = Option::get(MAXYSS_WB_NAME, "PERSON_TYPE",  "");
        $arSettings['DELIVERY_SERVICE'] = Option::get(MAXYSS_WB_NAME, "DELIVERY_SERVICE", "");
        $arSettings['PAYSYSTEM'] = Option::get(MAXYSS_WB_NAME, "PAYSYSTEM", "");
        $arSettings['USER_DEFAULTE'] = Option::get(MAXYSS_WB_NAME, "USER_DEFAULTE", "");

        // статусы
        $arSettings['NEW'] = Option::get(MAXYSS_WB_NAME, "NEW", "N");
        $arSettings['CANCEL'] = Option::get(MAXYSS_WB_NAME, "CANCEL", "N");
        $arSettings['CLIENT_RECEIVED'] = Option::get(MAXYSS_WB_NAME, "CLIENT_RECEIVED", "N");
        $arSettings['CLIENT_RETURN'] = Option::get(MAXYSS_WB_NAME, "CLIENT_RETURN", "N");
        $arSettings['SKLAD_WB'] = Option::get(MAXYSS_WB_NAME, "SKLAD_WB", "N");
        $arSettings['TRANSIT'] = Option::get(MAXYSS_WB_NAME, "TRANSIT", "N");
        $arSettings['RETURN_PRODUCT'] = Option::get(MAXYSS_WB_NAME, "RETURN_PRODUCT", "N");
        $arSettings['TRIGGERS'] = unserialize(Option::get(MAXYSS_WB_NAME, 'TRIGGERS', ''));;

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
    function OzonOnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu){

        if($GLOBALS['APPLICATION']->GetGroupRight("main") < "R")
            return;

        $aMenu = array(
            "parent_menu" => "global_menu_settings",
            "section" => MAXYSS_WB_NAME,
            "sort" => 100,
            "text" => GetMessage("WB_MAXYSS_MENU"),
            "title" => GetMessage("WB_MAXYSS_TITLE"),
            "url" => '',//MAXYSS_WB_NAME."_ozon_maxyss.php?lang=".LANGUAGE_ID,
            "items_id" => "menu_wb_maxyss",
            "items" => array(
                array(
                    "text" => GetMessage("WB_MAXYSS_MENU_GENERAL"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_WB_NAME."_wb_maxyss_general.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
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
            )
        );

        foreach($aModuleMenu as $key => $menu) :
            if ($menu["parent_menu"] == "global_menu_settings" && $menu['items_id'] == 'menu_system') :
                foreach ($menu['items'] as $k=>$item){
                    if($item['items_id'] == 'menu_module_settings')
                        $aModuleMenu[$key]["items"][$k]['items'][] = $aMenu;
                }
            endif;
        endforeach;
    }

    public function login_by_phone($phone){
        $data_string = array(
            "phone" => $phone,
            "is_terms_and_conditions_accepted" => true
        );
//            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log.txt", print_r($data_string, true) . PHP_EOL, FILE_APPEND);
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        if (wb_is_curl_installed()) {
            $arLogin = CRestQueryWB::rest_query($base_url = WB_BASE_URL, $data_string, "/passport/api/v2/auth/login_by_phone");
            echo $arLogin;
        } else {
            echo \Bitrix\Main\Web\Json::encode(array('error' => GetMessage("WB_MAXYSS_ERROR_CURL")));
        }
    }
    public function login($token ='', $notify_code = ''){
        $data_string = array(
            "token" => strval($token),
        );
        if($notify_code != '')
            $data_string["options"] = array('notify_code'=>strval($notify_code));

        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        if (wb_is_curl_installed()) {
            $arLogin = CRestQueryWB::rest_query($base_url = WB_BASE_URL, $data_string, "/passport/api/v2/auth/login");
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log.txt", print_r($arLogin, true) . PHP_EOL, FILE_APPEND);
            setcookie("WBToken", $arLogin['wbtoken'], time() + $arLogin['expires'], '/');
            Option::set(MAXYSS_WB_NAME, "WBTOKEN", $arLogin['wbtoken']);

            self::grant();
            self::login_instalation();
            echo \Bitrix\Main\Web\Json::encode($arLogin);
        } else {
            echo "cURL is <span style=\"color:#dc4f49\">not installed</span> on this server";
        }
    }
    public function grant(){
        if (wb_is_curl_installed()) {
            $ResToken = CRestQueryWB::rest_query($base_url = WB_BASE_URL, $data_string='', "/passport/api/v2/auth/grant");
            $arToken = \Bitrix\Main\Web\Json::decode($ResToken);
            Option::set(MAXYSS_WB_NAME, "TOKEN_2", $arToken['token']);
        } else {
            echo "cURL is <span style=\"color:#dc4f49\">not installed</span> on this server";
        }
    }
    public function GetLicense(){
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");
        $arInfo = array();
        $arInfo['key'] = CUpdateClientPartner::GetLicenseKey();
        return $arInfo;
    }
    public function login_instalation(){
        $arInfo = self::GetLicense();
        $data_string = array(
            "token" => strval(Option::get("maxyss.wb", "TOKEN_2", "")),
            "device" => md5("BITRIX".$arInfo['key']."LICENCE"),
            "country" => LANGUAGE_ID
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        if (wb_is_curl_installed()) {
            $arLogin = CRestQueryWB::rest_query($base_url = WB_BASE_URL, $data_string, "/passport/api/v2/auth/login");
        } else {
            echo "cURL is <span style=\"color:#dc4f49\">not installed</span> on this server";
        }
    }
    public function new_cookie(){
        $arInfo = self::GetLicense();
        $data_string = array(
            "token" => strval(Option::get("maxyss.wb", "TOKEN_2", "")),
            "device" => md5("BITRIX".$arInfo['key']."LICENCE"),
            "country" => LANGUAGE_ID
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        if (wb_is_curl_installed()) {
            $arLogin = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/passport/api/v2/auth/login");
            setcookie("WBToken", $arLogin['wbtoken'], time() + $arLogin['expires'], '/');
            Option::set(MAXYSS_WB_NAME, "WBTOKEN", $arLogin['wbtoken']);
            if($arLogin['wbtoken'])
                return $res = true;
            else
                return $res = false;
        } else {
            return $res = false;
        }
    }
    public function deepIconv($sbj){
        if (is_array($sbj) || is_object($sbj)){
            foreach ($sbj as &$val){
                $val= self::deepIconv($val);
            }
            return $sbj;
        }else{
            return iconv('UTF-8', 'windows-1251//IGNORE', $sbj);
        }
    }
    public static function get_price($type, $prop, $type_prop, $no_discount, $product_id, $lid){

        if($prop=="Y"){
            // for property
            $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_".$type_prop);
            $arFilter = Array("ID"=>$product_id);
            $res = CIBlockElement::GetList(Array('ID'=>'asc'), $arFilter, false, false, $arSelect);

            if($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();
                $result = intval($arFields['PROPERTY_'.$type_prop.'_VALUE']);
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
                    $result = intval(round($arPrice['RESULT_PRICE']['BASE_PRICE'], 0));
                } else {
                    $result = intval(round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'], 0));
                }
            }else{
                $result = 0;
            }
        }

        return $result;
    }

    public function PrepareItem($id = 0){
        if(intval($id) <= 0){
            $item = '';
        }else{
            $arSettings = self::settings_wb();

            $lid = $arSettings['SITE'];

            $arSelect = Array("ID", "IBLOCK_ID", "NAME", "TAGS", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_PROP_MAXYSS_WB", "PROPERTY_PROP_MAXYSS_CARDID_WB", "PROPERTY_CML2_ARTICLE");
            if($arSettings['BRAND'] != '') $arSelect[] = "PROPERTY_".$arSettings['BRAND'];
            if($arSettings['SHKOD'] != '') $arSelect[] = "PROPERTY_".$arSettings['SHKOD'];
            if($arSettings['ARTICLE'] != '') $arSelect[] = "PROPERTY_".$arSettings['ARTICLE'];
            if($arSettings['LAND'] != '') $arSelect[] = "PROPERTY_".$arSettings['LAND'];
            if($arSettings['KEYWORD'] != '') $arSelect[] = "PROPERTY_".$arSettings['KEYWORD'];
            $arFilter = Array('ID'=>$id);
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

                    $addin_card = CUtil::JsObjectToPhp($addin_card);

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
            if (!is_array($addin_card)) return false;

            // цвет, размер, ... для основной карточки
            $dependencies = [];
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"))
                $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
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
                                $arVarProp['colors'] = $wb_directory[$value];
                            }
                        }
                    }
                }
            }
            $object = $addin_card["object"];
            unset($addin_card["object"]);
            $addin_card = array_values($addin_card);
            $addin_card[] = array(
                'type' => GetMessage('WB_MAXYSS_DESCRIPTION'),
                'params' => array(
                    array(
                        "value" => str_replace('&nbsp;', ' ', htmlentities(HTMLToTxt($arFields[$arSettings['DESCRIPTION']], $arSettings['SERVER_NAME'])))
                    )
                )
            );

            // Ключевые слова
            $arTags = array();
            if($arFields["TAGS"] !='') {
                $arTags = explode(',', $arFields["TAGS"]);
                if(!empty($arTags)) {
					
					$tags[] = array("value" => trim($arFields["PROPERTY_CML2_ARTICLE_VALUE"]));
					
                    foreach ($arTags as $tag) {
                        $tags[] = array("value" => str_replace('&nbsp;', ' ', trim($tag)));
                    }
                    if (!empty($tags)) {
						// op обрезаем до 3
						$tags = array_slice($tags, 0, 3);
                        $addin_card[] = array(
                            'type' => GetMessage('WB_MAXYSS_KEYWORD'),
                            'params' => $tags
                        );
                    }
                }
            }else{
                $addin_card[] = array(
					'type' => GetMessage('WB_MAXYSS_KEYWORD'),
					'params' => array(array("value" => trim($arFields["PROPERTY_CML2_ARTICLE_VALUE"])))
                );
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
                $article_dop = '_'.$arVarProp['colors'];
            else
                $article_dop = '_0';

			// op
			$article_dop = '';
			$arLand = array(
				"Австрия" => "Австрия",
				"Беларусь" => "Беларусь",
				"Великобритания" => "Великобритания",
				"Китай" => "Китай",
				"Россия" => "Россия",
				"Тайланд" => "Тайланд",
				"Филиппины" => "Филиппины",
				"Швейцария" => "Швейцария",
				"Швеция" => "Швеция",
				"Япония" => "Япония",
				"Южная Корея" => "Корея, Республика",
			);
			AddMessage2Log($land);
			if($arLand[$land]) $land = $arLand[$land];
			AddMessage2Log($land);AddMessage2Log($arLand);
            $item = array(
                "supplierID" => Option::get(MAXYSS_WB_NAME, "UUID", ""),
                "card" => array(
                    "object" => $object,
                    "supplierVendorCode" => ($arSettings['ARTICLE']=='')? $arFields['ID'] : $arFields["PROPERTY_".$arSettings['ARTICLE']."_VALUE"],
                    "countryProduction" => $land,
                    "nomenclatures" => array(
                        array( // Массив номенклатур товара.
                            "vendorCode" => ($arSettings['ARTICLE']=='')? $arFields['ID'] : $arFields["PROPERTY_".$arSettings['ARTICLE']."_VALUE"].$article_dop, // Артикул товара.
                            "variations" => array(
                                array( // Массив вариаций товара. Одна цена - одна вариация.
                                    "barcode" => $arFields["PROPERTY_".$arSettings['SHKOD']."_VALUE"], // Штрихкод товара.
                                    "barcodes"=>array($arFields["PROPERTY_".$arSettings['SHKOD']."_VALUE"]),
                                    "addin" => array(
                                        array(
                                            "type" => GetMessage("WB_MAXYSS_PRICE"),
                                            "params" => array( // У хар-ик, содержащих одно значение, массив будет содержать только 1 элемент.
                                                array(
                                                    "count" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $lid),
                                                    "units" => GetMessage("WB_MAXYSS_RUB"),
                                                )
                                            )
                                        ),
                                    )
                                ),
                            ),
                            "addin" => array(
//                                array(
//                                    "type" => "Основной цвет",
//                                    "params" => array(
//                                        array(
//                                            "value" => "heather"
//                                        )
//                                    )
//                                ),
//                                array(
//                                    "type" => "Sex",
//                                    "params" => array(
//                                        array(
//                                            "value" => "Girls"
//                                        ),
//                                    )
//                                ),
                            )
                        ),
                    ),
                    "addin" => $addin_card,
                )
            );

            if($arProps['PROP_MAXYSS_CARDID_WB']['VALUE'] > 0){
                $item['card']['imtId'] = intval($arProps['PROP_MAXYSS_CARDID_WB']['VALUE']);
            }
            if($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'] > 0 && $arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'] > 0){
                $item['card']['nomenclatures'][0]['nmId'] = intval($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE']);
                $item['card']['nomenclatures'][0]['variations'][0]['chrtId'] = intval($arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE']);
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
		$img = array_slice($img, 0, 30);
        return array('item'=>$item, 'img'=>$img);
    }

    public function uploadPicture($file){
        $result = array();
        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $data_string = array(
                        'file' => $f,
                    );
//                    $arResult = CRestQueryWB::rest_file($base_url = WB_BASE_URL, $data_string, "/card/upload/file");
                    $arResult = CRestQueryWB::rest_file_na($base_url = WB_BASE_URL, $data_string, "/card/upload/file/multipart");
                    if ($arResult != '')
                        $result[] = $arResult;
                }
            }
        }
        return $result;
    }

    public static function elemAsProduct($id_element){
        $arTovar = array();
        $arSettings = self::settings_wb();
        $ar_tovar = CCatalogProduct::GetByID($id_element); // item as product



        $amount = 0;
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
            $amount = ($ar_tovar['QUANTITY'] > 0)? $ar_tovar['QUANTITY'] : 0;
        }

        $arTovar = $ar_tovar;
        $arTovar['QUANTITY'] = intval($amount);
        return $arTovar;
    }
    public function UploadCadr($item_info, $id_element){
        $res = array();
        $arInfo = CMaxyssWb::GetLicense();
        $arSettings = self::settings_wb();
        $lid = $arSettings['SITE'];
        $IBLOCK_ID = $arSettings['IBLOCK_ID'];
        $arTovar = self::elemAsProduct($id_element);

        if ($arTovar['TYPE'] == 3)
        {
            $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

            if (is_array($arInfoOff)) {

                // цвет, размер

                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"))
                    $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
                if (!empty($dependencies))
                    foreach ($dependencies["WB_SCU_PROP"] as $prop){
                        if($prop['propWB'] == 'colors') $key_prop = 'id'; else $key_prop = 'name';
                        foreach ($prop["propsList"] as $pr){
    //                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = $pr['wbVal']['wb_name'];
                            $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = ($prop['propWB']=='colors' || $prop['propWB']=='tech-sizes')? $pr['wbVal']['wb_name'] : $pr['wbVal']['wb_key'];
                        }
                    }



                $arSelect = Array("ID", "IBLOCK_ID", "NAME", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_*");

                $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfoOff['IBLOCK_ID'], "ACTIVE" => "Y", 'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $id_element), false, false, $arSelect);

                while ($arOffer = $rsOffers->GetNextElement())
                {
                    $arOff['FIELD'] = $arOffer->GetFields();
                    $arOff['PROP'] = $arOffer->GetProperties();

                    if(!empty($prop_change)) {
                    foreach ($arOff['PROP'] as &$prop_card){
                        $value = '';
                        if(array_key_exists($prop_card['ID'], $prop_change)) {

                            $value = $prop_card['VALUE']? $prop_card['VALUE'] : $prop_card['VALUE_ENUM_ID'];
                            $value_W = $prop_card['VALUE_ENUM_ID'];

                            foreach($prop_change[$prop_card['ID']] as $key => $wb_directory){
                                if ($key == 'wbsizes' && $value !=''){
                                    $arOff['wbsizes'] = $wb_directory[$value];
                                }
                                elseif($key == 'tech-sizes' && $value !=''){
                                    $arOff['tech-sizes'] = $wb_directory[$value];
                                }
                                elseif($key == 'colors' && $value !=''){
                                    if($wb_directory[$value] == ''){
                                        $arOff['colors'] = $wb_directory[$value_W];
                                    }else{
                                        $arOff['colors'] = $wb_directory[$value];
                                    }
                                }
                            }
                        }
                    }
                }
                    $arOffers[$arOff['colors']][] = $arOff;
                }
            }
            if(!empty($arOffers))
            {
                $noms_service = array();
                $noms = array();


                foreach ($arOffers as $key=>$color){
                    $img = array();
                    $imgPath = $_SERVER['DOCUMENT_ROOT'];
                    $nom = array( // Массив номенклатур товара.
                        "vendorCode" => $item_info['item']['card']['supplierVendorCode'].'-'.$key, // Артикул товара.
                        "addin" => array(
                            array(
                                "type" => GetMessage('WB_MAXYSS_COLOR_BASE'),
                                "params" => array(
                                    array(
                                        "value" => $key
                                    )
                                )
                            )
                        )
                    );

                    $variations_service = array();
                    $variations = array();
                    foreach ($color as $size) {
                        $variations_service[] = $size['FIELD']['ID'];
                        // фотки соберем со всех тп одного цвета
                        if ($size['FIELD'][$arSettings['BASE_PICTURE']]) {
                            $img[] = $imgPath.CFile::GetPath($size['FIELD'][$arSettings['BASE_PICTURE']]);
                        }
                        if (is_array($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'])) {
                            foreach ($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                                $img[] = $imgPath.CFile::GetPath($photo);
                            }
                        }
                        $img = array_merge($img, $item_info['img']);
                        // фотки соберем со всех тп одного цвета
                        $addin = array();
                        if(strlen($size['tech-sizes'])>0){
                            $addin[] = array(
                                "type" => GetMessage('WB_MAXYSS_SIZE_WB'),
                                "params" => array(
                                    array(
                                        "value" => strval($size['tech-sizes'])
                                    )
                                )
                            );
                        }

                        if(strlen($size['wbsizes'])>0){
                            $addin[] = array(
                                "type" => GetMessage('WB_MAXYSS_ROS_SIZE'),
                                "params" => array(
                                    array(
                                        "value" => $size['wbsizes']
                                    )
                                )
                            );
                        }

                        $addin[] = array(// Структура, содержащая характеристики конкретной вариации товара.
                            "type" => GetMessage("WB_MAXYSS_PRICE"), // Название характеристики.
                            "params" => array( // У хар-ик, содержащих одно значение, массив будет содержать только 1 элемент.
                                array(
                                    "count" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $size['FIELD']['ID'], $lid),
                                    "units" => GetMessage('WB_MAXYSS_RUB'),
                                )
                            )
                        );



                        $variations[]=
                            array(
                                "barcode" => $size["PROP"][$arSettings['SHKOD']]["VALUE"],
                                "barcodes"=>array($size["PROP"][$arSettings['SHKOD']]["VALUE"]),
                                "addin" => $addin
                        );
                    }
                    $img = array_unique($img);

//                $arImgIds = array();
                    $arImgIds = self::uploadPicture($img);
                    if(!empty($arImgIds))
                        $nom['addin'][] = array('type'=> GetMessage("WB_MAXYSS_PHOTO"), 'params' => $arImgIds);

                    $noms_service[] = $variations_service;
                    $nom["variations"] = $variations;

                    $noms[] = $nom;
                }

                $item_info['item']['card']['nomenclatures'] = $noms;
//                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_curl.txt", print_r($item_info, true) . PHP_EOL, FILE_APPEND);

                $data_string = array(
                    "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
                    "jsonrpc" => "2.0",
                    'params' => $item_info['item'],
                );
                $arResult = array();
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);

                $bck = self::bck_wb();
                if($bck['BCK'] && $bck['BCK'] != "Y") {
                    $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/create");
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

            }

        }
        elseif($arTovar['TYPE'] == 1)
        {
            $arImgIds = self::uploadPicture($item_info['img']);
            if(!empty($arImgIds))
                $item_info['item']['card']['nomenclatures'][0]['addin'][] = array('type'=> GetMessage('WB_MAXYSS_PHOTO'), 'params' => $arImgIds);

            $data_string = array(
                "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
                "jsonrpc" => "2.0",
                'params' => $item_info['item']
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $bck = self::bck_wb();
            if($bck['BCK'] && $bck['BCK'] != "Y") {
                $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/create");
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

        }
        else
        {
            // не выгружаем
            $res = GetMessage("WB_MAXYSS_ERROR_NOT_PRODUCT");
        }

        echo $res;
    }

    public function UploadCards($items_info, $id_elements){
        $res = array();
        $arInfo = CMaxyssWb::GetLicense();
        $arSettings = self::settings_wb();
        $lid = $arSettings['SITE'];
        $IBLOCK_ID = $arSettings['IBLOCK_ID'];
        $cards = false;

        foreach ($id_elements as $keyId => $id_element){
            $arTovar = self::elemAsProduct($id_element);

            if ($arTovar['TYPE'] == 3)
            {
                $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

                if (is_array($arInfoOff)) {

                    // цвет, размер

                    $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
                    foreach ($dependencies["WB_SCU_PROP"] as $prop){
                        if($prop['propWB'] == 'colors') $key_prop = 'id'; else $key_prop = 'name';
                        foreach ($prop["propsList"] as $pr){
//                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = $pr['wbVal']['wb_name'];
                            $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = ($prop['propWB']=='colors' || $prop['propWB']=='tech-sizes')? $pr['wbVal']['wb_name'] : $pr['wbVal']['wb_key'];
                        }
                    }



                    $arSelect = Array("ID", "IBLOCK_ID", "NAME", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_*");

                    $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfoOff['IBLOCK_ID'], "ACTIVE" => "Y", 'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $id_element), false, false, $arSelect);

                    while ($arOffer = $rsOffers->GetNextElement())
                    {
                        $arOff['FIELD'] = $arOffer->GetFields();
                        $arOff['PROP'] = $arOffer->GetProperties();

                        if(!empty($prop_change)) {
                            foreach ($arOff['PROP'] as &$prop_card){
                                $value = '';
                                if(array_key_exists($prop_card['ID'], $prop_change)) {

                                    $value = $prop_card['VALUE']? $prop_card['VALUE'] : $prop_card['VALUE_ENUM_ID'];
                                    $value_W = $prop_card['VALUE_ENUM_ID'];

                                    foreach($prop_change[$prop_card['ID']] as $key => $wb_directory){
                                        if ($key == 'wbsizes' && $value !=''){
                                            $arOff['wbsizes'] = $wb_directory[$value];
                                        }
                                        elseif($key == 'tech-sizes' && $value !=''){
                                            $arOff['tech-sizes'] = $wb_directory[$value];
                                        }
                                        elseif($key == 'colors' && $value !=''){
                                            if($wb_directory[$value] == ''){
                                                $arOff['colors'] = $wb_directory[$value_W];
                                            }else{
                                                $arOff['colors'] = $wb_directory[$value];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $arOffers[$arOff['colors']][] = $arOff;
                    }
                }
                if(!empty($arOffers))
                {
                    $noms_service = array();
                    $noms = array();


                    foreach ($arOffers as $key=>$color){
                        $img = array();
                        $imgPath = $_SERVER['DOCUMENT_ROOT'];
                        $nom = array( // Массив номенклатур товара.
                            "vendorCode" => $items_info[$keyId]['item']['card']['supplierVendorCode'].'-'.$key, // Артикул товара.
                            "addin" => array(
                                array(
                                    "type" => GetMessage('WB_MAXYSS_COLOR_BASE'),
                                    "params" => array(
                                        array(
                                            "value" => $key
                                        )
                                    )
                                )
                            )
                        );

                        $variations_service = array();
                        $variations = array();
                        foreach ($color as $size) {
                            $variations_service[] = $size['FIELD']['ID'];
                            // фотки соберем со всех тп одного цвета
                            if ($size['FIELD'][$arSettings['BASE_PICTURE']]) {
                                $img[] = $imgPath.CFile::GetPath($size['FIELD'][$arSettings['BASE_PICTURE']]);
                            }
                            if (is_array($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'])) {
                                foreach ($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                                    $img[] = $imgPath.CFile::GetPath($photo);
                                }
                            }
                            $img = array_merge($img, $items_info[$keyId]['img']);
                            // фотки соберем со всех тп одного цвета
                            $addin = array();
                            if(strlen($size['tech-sizes'])>0){
                                $addin[] = array(
                                    "type" => GetMessage('WB_MAXYSS_SIZE_WB'),
                                    "params" => array(
                                        array(
                                            "value" => strval($size['tech-sizes'])
                                        )
                                    )
                                );
                            }

                            if(strlen($size['wbsizes'])>0){
                                $addin[] = array(
                                    "type" => GetMessage('WB_MAXYSS_ROS_SIZE'),
                                    "params" => array(
                                        array(
                                            "value" => $size['wbsizes']
                                        )
                                    )
                                );
                            }

                            $addin[] = array(// Структура, содержащая характеристики конкретной вариации товара.
                                "type" => GetMessage("WB_MAXYSS_PRICE"), // Название характеристики.
                                "params" => array( // У хар-ик, содержащих одно значение, массив будет содержать только 1 элемент.
                                    array(
                                        "count" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $size['FIELD']['ID'], $lid),
                                        "units" => GetMessage('WB_MAXYSS_RUB'),
                                    )
                                )
                            );



                            $variations[]=
                                array(
                                    "barcode" => $size["PROP"][$arSettings['SHKOD']]["VALUE"],
                                    "barcodes"=>array($size["PROP"][$arSettings['SHKOD']]["VALUE"]),
                                    "addin" => $addin
                                );
                        }
                        $img = array_unique($img);

//                $arImgIds = array();
                        $arImgIds = self::uploadPicture($img);
                        if(!empty($arImgIds))
                            $nom['addin'][] = array('type'=> GetMessage("WB_MAXYSS_PHOTO"), 'params' => $arImgIds);

                        $noms_service[] = $variations_service;
                        $nom["variations"] = $variations;

                        $noms[] = $nom;
                    }

                    $items_info[$keyId]['item']['card']['nomenclatures'] = $noms;
//                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_curl.txt", print_r($item_info, true) . PHP_EOL, FILE_APPEND);
                }

            }
            elseif($arTovar['TYPE'] == 1)
            {
                $arImgIds = self::uploadPicture($items_info[$keyId]['img']);
                if(!empty($arImgIds))
                    $items_info[$keyId]['item']['card']['nomenclatures'][0]['addin'][] = array('type'=> GetMessage('WB_MAXYSS_PHOTO'), 'params' => $arImgIds);

            }
            $cards[] =  $items_info[$keyId]['item']['card'];
//            else
//            {
//                // не выгружаем
//                $res = GetMessage("WB_MAXYSS_ERROR_NOT_PRODUCT");
//            }

        }
        if (!$cards) {
            // не выгружаем
            $res = GetMessage("WB_MAXYSS_ERROR_NOT_PRODUCT");
        }
        else{
            $data_string = array(
                "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
                "jsonrpc" => "2.0",
                'params' => ['card' => $cards],
                'supplierID' => Option::get(MAXYSS_WB_NAME, "UUID", "")
            );
            $arResult = array();
//            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_cards.txt", print_r($data_string, true) . PHP_EOL, FILE_APPEND);
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);

            $bck = self::bck_wb();
            if($bck['BCK'] && $bck['BCK'] != "Y") {
                $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/batchCreate");
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
        }
        echo $res;
    }

    public function UpdateCadr($item_info, $id_element){

        $res = array();
        $arInfo = CMaxyssWb::GetLicense();
        $arSettings = self::settings_wb();
        $lid = $arSettings['SITE'];
        $IBLOCK_ID = $arSettings['IBLOCK_ID'];
        $arTovar = self::elemAsProduct($id_element);

        if ($arTovar['TYPE'] == 3)
        {
            $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

            if (is_array($arInfoOff)) {

                // цвет, размер

                $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
                foreach ($dependencies["WB_SCU_PROP"] as $prop){
                    if($prop['propWB'] == 'colors') $key_prop = 'id'; else $key_prop = 'name';
                    foreach ($prop["propsList"] as $pr){
//                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = $pr['wbVal']['wb_name'];
                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = ($prop['propWB']=='colors' || $prop['propWB']=='tech-sizes')? $pr['wbVal']['wb_name'] : $pr['wbVal']['wb_key'];
                    }
                }



                $arSelect = Array("ID", "IBLOCK_ID", "NAME", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_*");

                $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfoOff['IBLOCK_ID'], "ACTIVE" => "Y", 'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $id_element), false, false, $arSelect);

                while ($arOffer = $rsOffers->GetNextElement())
                {
                    $arOff['FIELD'] = $arOffer->GetFields();
                    $arOff['PROP'] = $arOffer->GetProperties();

                    if(!empty($prop_change)) {
                        foreach ($arOff['PROP'] as &$prop_card){
                            $value = '';
                            if(array_key_exists($prop_card['ID'], $prop_change)) {

                                $value = $prop_card['VALUE']? $prop_card['VALUE'] : $prop_card['VALUE_ENUM_ID'];
                                $value_W = $prop_card['VALUE_ENUM_ID'];

                                foreach($prop_change[$prop_card['ID']] as $key => $wb_directory){
                                    if ($key == 'wbsizes' && $value !=''){
                                        $arOff['wbsizes'] = $wb_directory[$value];
                                    }
                                    elseif($key == 'tech-sizes' && $value !=''){
                                        $arOff['tech-sizes'] = $wb_directory[$value];
                                    }
                                    elseif($key == 'colors' && $value !=''){
                                        if($wb_directory[$value] == ''){
                                            $arOff['colors'] = $wb_directory[$value_W];
                                        }else{
                                            $arOff['colors'] = $wb_directory[$value];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $arOffers[$arOff['colors']][] = $arOff;
                }
            }
            if(!empty($arOffers))
            {
                $noms_service = array();
                $noms = array();
                foreach ($arOffers as $key=>$color){
                    $img = array();
                    $imgPath = $_SERVER['DOCUMENT_ROOT'];


                    $nom = array( // Массив номенклатур товара.
                        "vendorCode" => $item_info['item']['card']['nomenclatures'][0]['vendorCode'].'-'.$key, // Артикул товара.
                        "addin" => array(
                            array(
                                "type" => GetMessage('WB_MAXYSS_COLOR_BASE'),
                                "params" => array(
                                    array(
                                        "value" => $key
                                    )
                                )
                            )
                        )
                    );


                    $variations_service = array();
                    $variations = array();
                    foreach ($color as $size) {
                        $variations_service[] = $size['FIELD']['ID'];
                        // фотки соберем со всех тп одного цвета
                        if ($size['FIELD'][$arSettings['BASE_PICTURE']]) {
                            $img[] = $imgPath.CFile::GetPath($size['FIELD'][$arSettings['BASE_PICTURE']]);
                        }
                        if (is_array($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'])) {
                            foreach ($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                                $img[] = $imgPath.CFile::GetPath($photo);
                            }
                        }
                        $img = array_merge($img, $item_info['img']);
                        // фотки соберем со всех тп одного цвета
                        $addin = array();
                        if(strlen($size['tech-sizes'])>0){
                            $addin[] = array(
                                "type" => GetMessage('WB_MAXYSS_SIZE_WB'),
                                "params" => array(
                                    array(
                                        "value" => strval($size['tech-sizes'])
                                    )
                                )
                            );
                        }

                        if(strlen($size['wbsizes'])>0){
                            $addin[] = array(
                                "type" => GetMessage('WB_MAXYSS_ROS_SIZE'),
                                "params" => array(
                                    array(
                                        "value" => $size['wbsizes']
                                    )
                                )
                            );
                        }

                        $addin[] = array(// Структура, содержащая характеристики конкретной вариации товара.
                            "type" => GetMessage("WB_MAXYSS_PRICE"), // Название характеристики.
                            "params" => array( // У хар-ик, содержащих одно значение, массив будет содержать только 1 элемент.
                                array(
                                    "count" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $size['FIELD']['ID'], $lid),
                                    "units" => GetMessage('WB_MAXYSS_RUB'),
                                )
                            )
                        );


                        $variation = array(
                            "barcode" => $size["PROP"][$arSettings['SHKOD']]["VALUE"],
                            "barcodes"=>array($size["PROP"][$arSettings['SHKOD']]["VALUE"]),
                            "addin" => $addin
                        );

                        if($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'] > 0){
                            $variation['chrtId'] = intval($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE']);
                            $variations[]=$variation;
                        }
                    }
                    $img = array_unique($img);

//                $arImgIds = array();
                    $arImgIds = self::uploadPicture($img);
                    if(!empty($arImgIds))
                        $nom['addin'][] = array('type'=> GetMessage("WB_MAXYSS_PHOTO"), 'params' => $arImgIds);

                    $noms_service[] = $variations_service;
                    $nom["variations"] = $variations;

                    if($color[0]['PROP']['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'] > 0 ){
                        $nom['nmId'] = intval($color[0]['PROP']['PROP_MAXYSS_NMID_CREATED_WB']['VALUE']);
                        $noms[] = $nom;
                    }
                }
                $item_info['item']['card']['nomenclatures'] = $noms;

                if(!empty($item_info['item']['card']['nomenclatures'])) {
                    $data_string = array(
                        "id" => md5("BITRIX" . $arInfo['key'] . time() . "LICENCE"),
                        "jsonrpc" => "2.0",
                        'params' => $item_info['item']
                    );
                    $arResult = array();
                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                    $bck = self::bck_wb();
                    if ($bck['BCK'] && $bck['BCK'] != "Y") {
                        $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/update");
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
                }else{
                    $res = GetMessage("WB_MAXYSS_PRODUCT_NOT_UPDATE");
                }

            }

        }
        elseif($arTovar['TYPE'] == 1)
        {
            $arImgIds = self::uploadPicture($item_info['img']);
            if(!empty($arImgIds))
                $item_info['item']['card']['nomenclatures'][0]['addin'][] = array('type'=> GetMessage('WB_MAXYSS_PHOTO'), 'params' => $arImgIds);

            $data_string = array(
                "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
                "jsonrpc" => "2.0",
                'params' => $item_info['item']
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $bck = self::bck_wb();
            if($bck['BCK'] && $bck['BCK'] != "Y") {
                $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/update");
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

        }
        else
        {
            // не выгружаем
            $res = GetMessage("WB_MAXYSS_ERROR_NOT_PRODUCT");
        }

        echo $res;

    }

    public function GetCadrById($id){
        $arResult=array();
        $arInfo = CMaxyssWb::GetLicense();
        $data_string = array(
            "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
            "jsonrpc" => "2.0", // Версия протокола. Всегда должна быть "2.0".
            'params' => array(
                "cardID"=> $id,
                "supplierID"=> Option::get(MAXYSS_WB_NAME, "UUID", "")
            )
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/cardById");
            $arResult = \Bitrix\Main\Web\Json::decode($result);
        }
        return $arResult;
    }

    public function GetCadrByImtID($id, $id_element){
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
//                    "supplierID" => Option::get(MAXYSS_WB_NAME, "UUID", "")
                )
            );

            $Authorization = false;
            $supplierID = false;


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

    public function DeleteCadrById($id){
        $arInfo = CMaxyssWb::GetLicense();
        $data_string = array(
            "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
            "jsonrpc" => "2.0", // Версия протокола. Всегда должна быть "2.0".
            'params' => array(
                "cardID"=> $id,
                "supplierID"=> Option::get(MAXYSS_WB_NAME, "UUID", "")
            )
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/card/delete");

        if(!strpos('error', $result)){
            CIBlockElement::SetPropertyValuesEx($id, false, array(
                    "PROP_MAXYSS_CARDID_WB" => '',
                    "PROP_MAXYSS_NMID_WB" => '',
                    "PROP_MAXYSS_CHRTID_WB" => '',
                    "PROP_MAXYSS_NMID_CREATED_WB" => '',
                    "PROP_MAXYSS_CHRTID_CREATED_WB" => '',
            ));
        }
        return $result;
    }

    public function GetCadrList($article = '', $id_element = 0){
        $arResult = array();

        $arInfo = CMaxyssWb::GetLicense();
        $data_string = array(
            "id" => md5("BITRIX".$arInfo['key'].time()."LICENCE"),
            "jsonrpc" => "2.0", // Версия протокола. Всегда должна быть "2.0".
            'params' => array(
                "query"=>array( // Пагинация.
                    "limit"=> 1,
                    "offset"=> 0
                ),
//                "supplierID"=> Option::get(MAXYSS_WB_NAME, "UUID", ""),
//                "withError"=> true
            )
        );

        if($article != '')
            $data_string['params']["filter"]= array(
                'find'=>array(
                    array(
                        "column"=> (Option::get(MAXYSS_WB_NAME, "COLUMN_FILTER", "") != "")? Option::get(MAXYSS_WB_NAME, "COLUMN_FILTER", "") : "supplierVendorCode",
                        "search"=>$article
                    )
                )
            );
        else
            $data_string['params']["query"]['limit']= 10;


        $Authorization = false;
        $supplierID = false;


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
    public function prepareItemStock($id, $quantity, $arSettings){
        $lid = $arSettings['SITE'];
        $items = array();
        $iblock_shkod = Option::get(MAXYSS_WB_NAME, "SHKOD", "");


        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_".$iblock_shkod, "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB");
        $arFilter = Array('=ID' => $id/*, 'PROPERTY_'.$arSettings["FILTER_PROP"] => $arSettings["FILTER_PROP_ID"] */);
        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, Array("nPageSize" => 1), $arSelect);

        if(is_array($arSettings["SKLAD_ID"]) && !empty($arSettings["SKLAD_ID"])){
            $arTovar = self::elemAsProduct($id);
            $quantity = $arTovar["QUANTITY"];
        }

        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();

            $item = array(
                "barcode" => $arFields["PROPERTY_".strtoupper($iblock_shkod)."_VALUE"],
                "stock" => intval($quantity),
                "warehouseId" => intval($arSettings["SKLAD"])
            );
            $items[] = $item;
        }

        return $items;
    }

    public static function prepareAllItemsStock($arSettings, $arrFilter){
        $IBLOCK_ID = $arSettings["IBLOCK_ID"];
        $items = array();
        $iblock_shkod = Option::get(MAXYSS_WB_NAME, "SHKOD", "");
        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_".$iblock_shkod, "PROPERTY_PROP_MAXYSS_CARDID_WB", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB");
        $arFilter = Array(
            "IBLOCK_ID" => intval($IBLOCK_ID),
            "ACTIVE" => "Y",
        );
        if($arSettings["FILTER_PROP"] != '' && $arSettings["FILTER_PROP_ID"] !=''){
            $arFilter['PROPERTY_'.$arSettings["FILTER_PROP"]] =  $arSettings["FILTER_PROP_ID"];
        }

        if(!empty($arrFilter)){
            $arFilter = array_merge($arFilter, $arrFilter);
        }
        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, false, $arSelect);

        while ($ob = $res->GetNextElement()) {
            $ar_tovar = array();
            $arFields = $ob->GetFields();
            $ar_tovar = self::elemAsProduct($arFields["ID"]); // item as product


            if ($ar_tovar["TYPE"] == 1)
            {
                if ($arFields["PROPERTY_".strtoupper($iblock_shkod)."_VALUE"] != '') {
                    $item[] = array(
                        "barcode" => $arFields["PROPERTY_".strtoupper($iblock_shkod)."_VALUE"],
                        "stock" => $ar_tovar['QUANTITY'],
                        "warehouseId" => intval($arSettings["SKLAD"])
                    );

                    if(intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"])>0) {
                        $prices = array(
                            "nmId" => intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]),
                            "price" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $arSettings["SITE"]), // цена товара - норм
                        );

                        $item_prices[] = $prices;
                    }
                }
            }
            elseif ($ar_tovar["TYPE"] == 3)
            {
                $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

                if (is_array($arInfoOff)) {

                    $arSelectOff = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB", "PROPERTY_".$iblock_shkod );
                    $rsOffers = CIBlockElement::GetList(array(), array(
                        'IBLOCK_ID' => $arInfoOff['IBLOCK_ID'],
                        "!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false,
//                        "!PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB" => false,
                        "ACTIVE" => "Y",
                        'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $arFields["ID"]
                    ), false, false, $arSelectOff);
                    $arItems = array();
                    while ($arOffer = $rsOffers->GetNextElement()) {
                        $arFieldsOff = $arOffer->GetFields();
                        if(intval($arFieldsOff["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"])>0) {
                            $ar_tovar_off = self::elemAsProduct($arFieldsOff["ID"]); // item as product
                            $arFieldsOff['TOVAR'] = $ar_tovar_off;
                            $arItems[$arFieldsOff["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]][] = $arFieldsOff;
                        }
                    }
                    if(!empty($arItems)) {
                        foreach ($arItems as $key => $i) {
                            foreach ($i as $c) {
                                if ($c["PROPERTY_".strtoupper($iblock_shkod)."_VALUE"] != '') {
                                    $item[] = array(
                                        "barcode" => $c["PROPERTY_" . strtoupper($iblock_shkod) . "_VALUE"],
                                        "stock" => $c['TOVAR']['QUANTITY'],
                                        "warehouseId" => intval($arSettings["SKLAD"])
                                    );


                                }

                                $tp_price[] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $c['ID'], $arSettings["SITE"]);

                            }

                            if($arSettings['PRICE_MAX_MIN'] == 'MAX'){
                                $price = max($tp_price);
                            }else{
                                $price = min($tp_price);
                            }


                            $prices = array(
                                "nmId" => intval($key),
                                "price" => $price,
                            );
                            $item_prices[] = $prices;
                        }
                    }
                }
            }
        }
        return array("stocks"=>$item, "prices"=>$item_prices);
    }

    public static function prepareAllItemsPrice($arrFilter = array()){
        $arSettings = self::settings_wb();

        $IBLOCK_ID = $arSettings["IBLOCK_ID"];
        $items = array();

        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_PROP_MAXYSS_CARDID_WB", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB");
        $arFilter = Array(
            "IBLOCK_ID" => intval($IBLOCK_ID),
            "ACTIVE" => "Y",
            array(
                "LOGIC" => "OR",
                array('!PROPERTY_PROP_MAXYSS_CARDID_WB' => false),
                array('!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB' => false, '!PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB' => false)
            )
        );
        if($arSettings["FILTER_PROP"] != '' && $arSettings["FILTER_PROP_ID"] !=''){
            $arFilter['PROPERTY_'.$arSettings["FILTER_PROP"]] =  $arSettings["FILTER_PROP_ID"];
        }

        if(!empty($arrFilter)){
            $arFilter = array_merge($arFilter, $arrFilter);
        }

        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, false, $arSelect);

        while ($ob = $res->GetNextElement()) {
            $ar_tovar = array();
            $arFields = $ob->GetFields();
            $ar_tovar = CCatalogProduct::GetByID($arFields["ID"]); // item as product

            if ($ar_tovar["TYPE"] == 1)
            {
                if (intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]) > 0 && intval($arFields["PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB_VALUE"]) > 0) {

                    $price = array(
                        "nmId" => intval($arFields["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]),
                        "price" => self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $arSettings["SITE"]),
                    );
                    $item_price[] = $price;
                }
            }
            elseif ($ar_tovar["TYPE"] == 3)
            {
                $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

                if (is_array($arInfoOff)) {

                    $arSelectOff = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_PROP_MAXYSS_NMID_CREATED_WB", "PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB");
                    $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfoOff['IBLOCK_ID'], "!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false, "!PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB" => false, "ACTIVE" => "Y", 'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $arFields["ID"]), false, false, $arSelectOff);
                    $arItems = array();
                    while ($arOffer = $rsOffers->GetNextElement()) {
                        $arFieldsOff = $arOffer->GetFields();
                        if(intval($arFieldsOff["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"])>0) {
                            $arItems[$arFieldsOff["PROPERTY_PROP_MAXYSS_NMID_CREATED_WB_VALUE"]][] = $arFieldsOff;
                        }
                    }
                    if(!empty($arItems)) {
                        foreach ($arItems as $key => $i) {
                            foreach ($i as $c) {
                                $tp_price[] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $c['ID'], $arSettings["SITE"]);
                            }

                            if($arSettings['PRICE_MAX_MIN'] == 'MAX'){
                                $price = max($tp_price);
                            }else{
                                $price = min($tp_price);
                            }
                            $price = array(
                                "nmId" => intval($key),
                                "price" => $price,
                            );
                            $item_price[] = $price;
                        }
                    }
                }
            }
        }
        return array("prices"=>$item_price);
    }

    public function uploadStock($event){
        $arSettings = self::settings_wb();
        // остаток по событию изменения полного доступного количества
        $item = $event->getParameters();
        $result = '';
        $ar_tovar = CCatalogProduct::GetByID($item["id"]); // item as product
        if($ar_tovar['QUANTITY'] != $item['fields']['QUANTITY']) {

            $flag_upd = false;

            $iblock_id = CIBlockElement::GetIBlockByID($item['id']);

            $mxResult = CCatalogSKU::GetInfoByOfferIBlock(
                $iblock_id
            );

            if (is_array($mxResult)) {  // это ТП

                if ($mxResult["PRODUCT_IBLOCK_ID"] ==  $arSettings["IBLOCK_ID"]) {

                    $iblock_id_tovar = $mxResult["PRODUCT_IBLOCK_ID"];

                    $tovarResult = CCatalogSku::GetProductInfo(
                        $item['id'] // id TP
                    );
                    if (is_array($tovarResult)) {

                            $arFilter = Array("ID" => $tovarResult['ID'], 'PROPERTY_'.$arSettings["FILTER_PROP"] => $arSettings["FILTER_PROP_ID"]);
                            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, array("ID", "IBLOCK_ID"));
                            if ($ob = $res->GetNextElement()) {
                                $flag_upd = true;
                            }

                    }
                }
            } else { // это товар
                if($iblock_id == $arSettings["IBLOCK_ID"]) {

                    if ($arSettings["FILTER_PROP"] != '' && $arSettings["FILTER_PROP_ID"] != '') {
                        $arFilter = Array("ID" => $item['id'], 'PROPERTY_'.$arSettings["FILTER_PROP"] => $arSettings["FILTER_PROP_ID"], '!PROPERTY_'.$arSettings["SHKOD"] => '');
                        $res = CIBlockElement::GetList(Array(), $arFilter, false, false);
                        if ($ob = $res->GetNextElement()) {
                            $flag_upd = true;
                        }
                    }
                }
            }


            if($flag_upd) {
                $items = self::prepareItemStock($item['id'], $item['fields']['QUANTITY'], $arSettings);
                $result = self::updateStock(false, $items["stocks"]);
            }
        }
    }

    public static function uploadAllStocks($Authorization = false, $SKLAD = '', $arrFilter = array()){
        // отправить все остатки (агент)
        if(!$Authorization) $Authorization = AUTHORIZATION;
        $arSettings = self::settings_wb();
        if($SKLAD != '') $arSettings["SKLAD"] = $SKLAD;
        $items = self::prepareAllItemsStock($arSettings, $arrFilter);

		$itemsAvail = self::getAllItemsAvail();
		foreach($items["stocks"] as $key => &$arItem){
			if($itemsAvail[$arItem["barcode"]]){
				if($itemsAvail[$arItem["barcode"]]["PROPERTY_WBPRICE_VALUE"] > 50){
					$arItem["stock"] = 3;
				}else{
					$arItem["stock"] = 0;
				}
			}else{
				$arItem["stock"] = 0;
			}
		}
		unset($arItem);
		
        if(!empty($items["prices"])) {

            if (Option::get(MAXYSS_WB_NAME, "PRICE_ON", "") == 'Y') {
                $result_price = CMaxyssWbprice::setPrices($Authorization, $items["prices"]);
            }

        }
        if(!empty($items["stocks"])) {
            $result = self::updateStock($Authorization, $items["stocks"]);
        }

        if(empty($arrFilter) && $Authorization == AUTHORIZATION) return "CMaxyssWb::uploadAllStocks();";
        elseif(empty($arrFilter)) return "CMaxyssWb::uploadAllStocks('".$Authorization."', '".$SKLAD."');";
        else return "CMaxyssWb::uploadAllStocks('".$Authorization."', '".$SKLAD."', ".var_export($arrFilter, true).");";
    }

	public static function getAllItemsAvail(){
		$arItems = array();
		if(CModule::IncludeModule("iblock")){
			$arFilter = Array(
				"IBLOCK_ID"	=> 16,
				"PROPERTY_AVAILABILITY_RU" => 512,
				"!PROPERTY_AEN" => false,
			);
			$rs = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID","CODE","PROPERTY_AEN","PROPERTY_WBPRICE"));
			while($ar = $rs->GetNext()){
				$arItems[$ar["PROPERTY_AEN_VALUE"]] = $ar;
			}
		}
		return $arItems;
	}
	
    public function updateStock($Authorization, $items){
        $arResult = '';

        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            if (!empty($items)) {
                $chunkItems = array_chunk($items, 1000);
                foreach ($chunkItems as $chunk_item) {
                    $data_string = $chunk_item;

                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                    $arResult = CRestQueryWB::rest_stock_na($base_url = WB_BASE_URL, $data_string, "/api/v2/stocks", $Authorization);
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPLOAD_STOCK', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "STOCK", "DESCRIPTION" => $data_string ));
                }

            }
        }
        return $arResult;
    }


    public function getStock($Authorization = false){
        $arResult = '';

        $data_string = array();
        $arResult = CRestQueryWB::rest_stock_get($base_url = WB_BASE_URL, $data_string, "/api/v2/stocks?skip=0&take=10000000&sort=article&order=asc", $Authorization);

        return $arResult;
    }

    // заказы

    public static function getStatusOrders($Authorization = false, $step = 0){
        if(!$Authorization) $Authorization = AUTHORIZATION;

        $arSettings = self::settings_wb();
        $limit = $arSettings['COUNT_ORDER'];

        $skip = $step*$limit;

        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $date = date("Y-m-d", (time() - (10 * 86400)));
            $res = array();
            $data_string = array();
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $res = CRestQueryWB::rest_order_na($base_url = WB_BASE_URL, $data_string, "/api/v2/orders?date_start=" . $date . "T00%3A00%3A00%2B03%3A00&take=" . $limit . "&skip=" . $skip, $Authorization);
            if (!empty($res) && count($res['orders']) >= 1) {
                foreach ($res['orders'] as $order_wb) {
//                    echo '<pre>', print_r('dateCreated   ---  ' . $order_wb['dateCreated'] . '   -------     status   ---  ' . $order_wb['status'] . '      -------     userStatus    -------     ' . $order_wb['userStatus']), '</pre>';
                    if (intval($order_wb['orderId']) > 0 && intval($order_wb["chrtId"]) > 0) {
                        $id_bx_order = self::getOrder($order_wb['orderId']);
                        if ($id_bx_order > 0/* && $i <= 8*/) {
                             self::setStatus($order_wb, $id_bx_order, $arSettings);
                        }
                    }
                }
            }

            if (intval($res['total']) > ($skip + $limit)) $step++; else $step = 0;
        }

        if($Authorization != AUTHORIZATION) return "CMaxyssWb::getStatusOrders('".$Authorization."', ".$step.");";
        else return "CMaxyssWb::getStatusOrders(false, ".$step.");";
        }

    public static function  setStatus($order_wb = array(), $id_bx_order = 0, $arSettings){
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
                case '21':
//                    echo '<pre>', print_r("Отмена заказа клиентом"), '</pre>' ;// 3
                    $status_result = 3;
                    break;
                case '22':
//                    echo '<pre>', print_r("Доставлено на склад"), '</pre>' ; // 4  2 - Сборочное задание завершено
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
                $res_status = CSaleOrder::StatusOrder($id_bx_order, $arSettings["STATUS_BY"][$status_result]);
            }
        }
    }

    public static function putStatusOrders($id, $status, $Authorization = false)
    {
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

    public static function loadNewOrders($Authorization = false, $step = 0){

        if(!$Authorization) $Authorization = AUTHORIZATION;

        $file_log_order = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_order.txt";
        $day_old = '';
        $day_new = date ("d");
        if (file_exists($file_log_order)) {
            $day_old = date ("d", filemtime($file_log_order));
        }
        if($day_old != $day_new)
        {
            file_put_contents($file_log_order, print_r("DATA - " . date('Y-m-d H:i:s') , true) . PHP_EOL);
        }


        $limit = Option::get(MAXYSS_WB_NAME, "COUNT_ORDER", "500");

        $skip = $step*$limit;

        $bck = self::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
//            $base_url = 'https://suppliers-orders.wildberries.ru';
            $date = date("Y-m-d", (time() - (1 * 86400)));
            $res = array();
            $data_string = array(//            'date_start' => $date."T00%3A00%3A00%2B03%3A00",
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);

            $res = CRestQueryWB::rest_order_na($base_url = WB_BASE_URL, $data_string, "/api/v2/orders?date_start=" . $date . "T00%3A00%3A00%2B03%3A00&take=".$limit."&skip=".$skip, $Authorization);

            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_order.txt", print_r($res, true) . PHP_EOL);
            if ($res['note'])
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_order.txt", print_r("NOTE - " . date('Y-m-d H:i:s') . $res['note'], true) . PHP_EOL, FILE_APPEND);
            elseif ($res['error'])
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_order.txt", print_r("ERROR - " . date('Y-m-d H:i:s') . $res['errorText'].' http_code '.$res['http_code'], true) . PHP_EOL, FILE_APPEND);
            elseif ($res['errors'])
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/log_order.txt", print_r("ERROR - " . date('Y-m-d H:i:s') . $res['errors'], true) . PHP_EOL, FILE_APPEND);
            else {
                $order_user = self::getUser();
                $i = 0;
                if(!empty($res['orders'])) {
                    foreach ($res['orders'] as $order_wb) {
                    $i++;
                    if (intval($order_wb['orderId']) > 0 && intval($order_wb["chrtId"]) > 0) {
                        $id_bx_order = self::getOrder($order_wb['orderId']);
                        if ($id_bx_order == 0/* && $i <= 8*/) {
                            $id_bx_order_new = self::createOrder($order_wb, $order_user);
                        }
                    }
                }
            }

                if (intval($res['total']) > ($skip + $limit)) $step++; else $step = 0;
        }
    }

        if($Authorization == AUTHORIZATION) return "CMaxyssWb::loadNewOrders(false, ".$step.");";
        else return "CMaxyssWb::loadNewOrders('".$Authorization."', ".$step.");";
    }


    public static function getUser(){
        $user__defaulte = Option::get(MAXYSS_WB_NAME, "USER_DEFAULTE", "");
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

    public static function getProduct($product, $order_wb){
        $prop_flag = '';
        $result = array();
        CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/upload/wb/");

//        foreach ($products as $key=>&$prod){
            $prod['chrt_id'] = $product;
            $arFilterProd = array("PROPERTY_PROP_MAXYSS_CHRTID_CREATED_WB" => $prod['chrt_id']);

            $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "IBLOCK_ID", 'CATALOG_XML_ID');
            $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);
            if($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();
                $result[$prod['chrt_id']] = $arFields;

                $image = base64_decode($order_wb['sticker']["wbStickerSvgBase64"]);
                $FPName = $order_wb["orderId"].'.svg';
                $FPPath = $_SERVER["DOCUMENT_ROOT"].'/upload/wb/'.$FPName;
                file_put_contents($FPPath, $image, LOCK_EX);
            }else{
                if($order_wb['barcode'] != ''){

                    $iblock_shkod = Option::get(MAXYSS_WB_NAME, "SHKOD", "");

                    $arFilterProd = array("PROPERTY_".$iblock_shkod => $order_wb['barcode']);
                    $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "IBLOCK_ID", 'CATALOG_XML_ID');
                    $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);

                    if($ob = $res->GetNextElement()) {
                        $arFields = $ob->GetFields();
                        $result[$prod['chrt_id']] = $arFields;

                        $image = base64_decode($order_wb['sticker']["wbStickerSvgBase64"]);
                        $FPName = $order_wb["orderId"] . '.svg';
                        $FPPath = $_SERVER["DOCUMENT_ROOT"] . '/upload/wb/' . $FPName;
                        file_put_contents($FPPath, $image, LOCK_EX);
                    }
                }
            }

//        }
        return $result;
    }

    public static function createOrder(&$order_wb = array(), $order_user = array()){
        $orderId = 0;
        if(!empty($order_wb) && !empty($order_user)){
            $arSettings = self::settings_wb();

            if($order_wb['chrtId']){
                $product = $order_wb['chrtId'];
                // get info product
                $order_wb["products_bitrix"] = self::getProduct($product, $order_wb);
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

//                    $sum = 0;
                    foreach ($order_wb["products_bitrix"] as $product) {
                        $item = $basket->createItem('catalog', $product['ID']);
                        $item->setFields(array(
                            'QUANTITY' => 1,
                            'CURRENCY' => $arSettings["VALUTA_ORDER"],
                            'LID' => $arSettings["SITE"],
                            'BASE_PRICE' => floatval($order_wb["totalPrice"]/100),
                            'PRICE' => floatval($order_wb["totalPrice"]/100),
                            'CUSTOM_PRICE' => 'Y',
                            'NAME' => $product['NAME'],
                            'DETAIL_PAGE_URL' => $product['DETAIL_PAGE_URL'],
                            'PRODUCT_XML_ID' => $product['EXTERNAL_ID'],
                            'CATALOG_XML_ID' => $product['IBLOCK_EXTERNAL_ID'],
                        ));
                        if(Option::get(MAXYSS_MODULE_NAME, "CALLBACK_BX", "") == 'Y') $item->setFields(array('PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'));
                    }
                    $sum = floatval($order_wb["totalPrice"]/100);

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
                        'SUM' => $sum,
                    ));


                    $propertyCollection = $order_bitrix->getPropertyCollection();
                    foreach ($propertyCollection as $prop) {
                        $value = '';
                        switch ($prop->getField('CODE')) {
                            case "MAXYSS_WB_NUMBER":
                                $value = $order_wb["orderId"];
                                $value = trim($value);
                                break;
                            case "MAXYSS_WB_RID":
                                $value = $order_wb['rid'];
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
                        $svg = $prop['VALUE'].'.svg';
                        if($prop["VALUE"] != '') $flag_button = true;
                    }
                }
                $flag_button = true;
            }
            if($flag_button) {
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
                        $svg = $prop['VALUE'].'.svg';
                        if($prop["VALUE"] != '') $flag_button = true;
                    }
}
            }
            if($flag_button) {
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

    function GetUserTypeMaxyssWBProp() {
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
    function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName) {

        $ID = intval($_REQUEST['ID']); //
        global $APPLICATION;

        if ($APPLICATION->GetCurPage() != '/bitrix/admin/iblock_list_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/iblock_element_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/cat_product_admin.php') {
            if (!wb_is_curl_installed()) {
                ?><div style="color: red"><?=GetMessage("CURL_NOT_INSTALLED")?></div><br><?
            }
            echo '<input type="text" id="autocomplete_wb" onkeyup="get_object_list( $(this).val() );" class="autocomplete_wb"  title="' . GetMessage('CATEGORY_ENTER_TEXT') . '" placeholder="' . GetMessage('WB_MAXYSS_CATEGORY_SEARCH_TEXT') . '" name="' . $strHTMLControlName["DESCRIPTION"] . '" value="' . $value["DESCRIPTION"] . '" data-lang="' . LANGUAGE_ID . '">';
            echo '<input data-category-wb="" id="object_wb" type="text" readonly name="' . $strHTMLControlName['VALUE'] . '" value="' . htmlspecialcharsbx($value['VALUE']) . '">';
            echo '<div class="predmet_dialog"></div>';
            /*if (strlen($value['VALUE']) > 5) echo '<input type="button" class="wb_upload" id="wb_upload" onclick="upload_card(' . $ID . ')" value="' . GetMessage("WB_MAXYSS_UPLOAD_WB") . '"><input type="button" class="wb_delete" id="wb_delete" onclick="delete_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_DELETE_WB') . '"><input type="button" class="wb_data" id="wb_data" onclick="data_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_GET_ID_WB') . '">';*/
            if (strlen($value['VALUE']) > 5) echo '<input type="button" class="wb_upload" id="wb_upload" onclick="upload_card(' . $ID . ')" value="' . GetMessage("WB_MAXYSS_UPLOAD_WB") . '"><input type="button" class="wb_data" id="wb_data" onclick="data_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_GET_ID_WB') . '">';
            else echo '<input type="button" class="wb_data" id="wb_data" onclick="data_card(' . $ID . ')" value="' . GetMessage('WB_MAXYSS_GET_ID_WB') . '">';
            ?>
            <script type="text/javascript">
                var lang = "<?=LANGUAGE_ID?>";
                var name_prop = "<?=$strHTMLControlName['VALUE']?>";
                var atribute_wb = $('[name="' + name_prop + '"]');

            </script>
            <?
            if (strlen($value['VALUE'])) {
                $addin_card = CUtil::JsObjectToPhp($value['VALUE']);
                $object_wb = $addin_card['object'];
                unset($addin_card['object']);
                foreach ($addin_card as $val) {
                    foreach ($val['params'] as $v){
                        $addin_set[$val['type']][] = $v['value']? $v['value'] : $v['count'];
                    }
                }
                ?>
                <script type="text/javascript">
                    get_object('<?=$object_wb?>', <?=CUtil::PhpToJSObject($addin_set)?>);
                </script>
                <?
            }
        } else {
            echo '';
        }

    }
    function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
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
    function ConvertToDB($arProperty, $value){
        return $value;
    }
    function ConvertFromDB($arProperty, $value){
        return $value;
    }

    function GetObjectWb($pattern, $lang){
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