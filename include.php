<?

use Bitrix\Main\Loader,
    Bitrix\Main\ModuleManager,
    Bitrix\Iblock,
    Bitrix\Catalog,
    Bitrix\Main\Localization\Loc,
    \Bitrix\Main\Config\Option,
    Bitrix\Currency,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem,
    Bitrix\Highloadblock as HL,
    Bitrix\Main\Entity,
    Bitrix\Main\Application,
    Bitrix\Main\Type,
    \Maxyss\Ozon\CMaxyssOzonLogTable,
    Bitrix\Main\Web\Json;


Bitrix\Main\Loader::includeModule("main");
Bitrix\Main\Loader::includeModule("sale");
Bitrix\Main\Loader::includeModule("catalog");
Bitrix\Main\Loader::includeModule("iblock");

global $APPLICATION;

define("MAXYSS_MODULE_NAME", "maxyss.ozon");
define ("VERSION_OZON_2", true);
define ("VERSION_OZON_3", true);

if (defined("ADMIN_SECTION")) {
    if(Option::get(MAXYSS_MODULE_NAME, "JQUERY_NO_LOAD", "N") != 'Y')
        CJSCore::Init( 'jquery' );
    $APPLICATION->SetAdditionalCSS("/bitrix/tools/maxyss.ozon/jquery-ui.css");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.ozon/jquery-ui.js");
    $APPLICATION->SetAdditionalCSS("/bitrix/tools/maxyss.ozon/print.min.css");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.ozon/print.min.js");
}
if ($GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/"){
    if(Option::get(MAXYSS_MODULE_NAME, "JQUERY_NO_LOAD", "N") != 'Y')
        CJSCore::Init( 'jquery' );
    $APPLICATION->SetAdditionalCSS("/bitrix/tools/maxyss.ozon/print.min.css");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.ozon/print.min.js");
}
if (strpos($GLOBALS["APPLICATION"]->GetCurPage(), 'shop/orders/details/') > 0){
    if(Option::get(MAXYSS_MODULE_NAME, "JQUERY_NO_LOAD", "N") != 'Y')
        CJSCore::Init( 'jquery' );
    $APPLICATION->SetAdditionalCSS("/bitrix/tools/maxyss.ozon/print.min.css");
    $APPLICATION->AddHeadScript("/bitrix/tools/maxyss.ozon/print.min.js");
}


IncludeModuleLangFile(__FILE__);

CJSCore::RegisterExt('maxyss_ozon', array(
    'js' => '/bitrix/tools/maxyss.ozon/script.js',
    'css' => '/bitrix/tools/maxyss.ozon/style.css',
    'lang' => '/bitrix/modules/'.MAXYSS_MODULE_NAME.'/lang/'.LANGUAGE_ID.'/include.php',
//    'rel' => array('popup', 'ajax', 'fx', 'ls', 'date', 'json')
));


define('OZON_ID', Option::get(MAXYSS_MODULE_NAME, "OZON_ID", ""));
define('OZON_API_KEY', Option::get(MAXYSS_MODULE_NAME, "OZON_API_KEY", ""));
define('OZON_BASE_URL', 'https://api-seller.ozon.ru');



CModule::AddAutoloadClasses(
    MAXYSS_MODULE_NAME,
    array(
        "RestClientException" => 'classes/restclientexception.php',
        "RestClient" => 'classes/restclient.php',
        "CRestQuery" => 'classes/maxyss_ozon.php',
        "CHelpMaxyss" => 'classes/maxyss_ozon.php',
        "CMaxyssOzonStatusPut" => 'classes/StatusPut.php',
        "CMaxyssOzonFolder" => 'classes/FolderProperty.php',
        "FilterCustomOzon" => 'classes/maxyss_ozon.php',
        "CMaxyssGetOzonInfo" => 'classes/CMaxyssGetOzonInfo.php',
        "CMaxyssAdminList" => 'classes/CMaxyssGetOzonInfo.php',
        "CMaxyssMoreOzonFunction" => 'classes/CMaxyssGetOzonInfo.php',
        "Maxyss\Ozon\CMaxyssOzonLogTable" => 'lib/CMaxyssTable.php'
    )
);

class CMaxyssOzon{
    public static function OzonOnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu){
        if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_MODULE_NAME) < "R")
            return;

        $aMenu = array(
            "parent_menu" => "global_menu_settings",
            "section" => MAXYSS_MODULE_NAME,
            "sort" => 100,
            "text" => GetMessage("OZON_MAXYSS_MENU"),
            "title" => GetMessage("OZON_MAXYSS_TITLE"),
            "url" => '',//MAXYSS_MODULE_NAME."_ozon_maxyss.php?lang=".LANGUAGE_ID,
            "items_id" => "menu_ozon_maxyss",
            "items" => array(
                array(
                    "text" => GetMessage("OZON_MAXYSS_MENU_GENERAL"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_MODULE_NAME."_ozon_maxyss_general.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
                    "title" => GetMessage("OZON_MAXYSS_MENU_GENERAL"),
                    'module_id' => MAXYSS_MODULE_NAME,
                    'items_id' => 'general_ozon_param',
                ),
                array(
                    "text" => GetMessage("OZON_MAXYSS_MENU_ATTRIBUTE"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_MODULE_NAME."_update_attribute.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
                    "title" => GetMessage("OZON_MAXYSS_MENU_ATTRIBUTE"),
                    'module_id' => MAXYSS_MODULE_NAME,
                    'items_id' => 'order_ozon_param',
                ),
                array(
                    "text" => GetMessage("OZON_MAXYSS_MENU_PRODUCT"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_MODULE_NAME."_ozon_maxyss.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
                    "title" => GetMessage("OZON_MAXYSS_MENU_PRODUCT"),
                    'module_id' => MAXYSS_MODULE_NAME,
                    'items_id' => 'product_ozon_param',
                ),
                array(
                    "text" => GetMessage("OZON_MAXYSS_MENU_ORDER"),
                    "icon" => "form_menu_icon",
                    "page_icon" => "form_page_icon",
                    "url" => MAXYSS_MODULE_NAME."_order_ozon_maxyss.php?lang=".LANGUAGE_ID,
                    "more_url" => array(),
                    "title" =>  GetMessage("OZON_MAXYSS_MENU_ORDER"),
                    'module_id' => MAXYSS_MODULE_NAME,
                    'items_id' => 'order_ozon_param',
                ),
            )
        );

        foreach($aModuleMenu as $key => $menu) :
            if ($menu["parent_menu"] == "global_menu_settings" && $menu['items_id'] == 'menu_system') :
                foreach ($menu['items'] as $k=>$item){
                    if($item['items_id'] == 'menu_module_settings') {
                        foreach ($aModuleMenu[$key]["items"][$k]['items'] as $key_i => $i){
                            if($i['text'] == GetMessage("OZON_MAXYSS_MENU")){
                                $aModuleMenu[$key]["items"][$k]['items'][$key_i] = $aMenu;
                            }
                        }
                    }
                }
            endif;
        endforeach;
    }
    public static function GetApiKey($ozon_id = OZON_ID){
        $api_key = OZON_API_KEY;
        $arOptions = self::getOptions($lid = false, $options = array('OZON_ID', 'OZON_API_KEY'));
        foreach ($arOptions as $site){
            if($site['OZON_ID'] == $ozon_id) $api_key = $site['OZON_API_KEY'];
        }
        return $api_key;
    }
    public static function getOptions($lid = false, $options = array()){
        $arOptions = array();
        if(!$lid){
            $by = "id";
            $sort = "desc";

            $arSites = array();
            $db_res = CSite::GetList($by, $sort, array("ACTIVE"=>"Y"));
            while($res = $db_res->Fetch()){
                $arSites[] = $res;
            }
            foreach($arSites as $key => $arSite){
                if(!empty($options)){
                    foreach ($options as $option){
                        $option = strtoupper($option);
                        $arOptions[$arSite["LID"]][strtoupper($option)] = Option::getRealValue(MAXYSS_MODULE_NAME, $option, $arSite["LID"]);
                    }
                }
                else
                {
                    $arOptions[$arSite["LID"]] = Option::getForModule(MAXYSS_MODULE_NAME, $arSite["LID"]);

                    if($arSite["DEF"] == "N" && !Option::getRealValue(MAXYSS_MODULE_NAME, "ACTIVE_ON", $arSite["LID"])){
                        $arOptions[$arSite["LID"]]["ACTIVE_ON"] = "";
                    }
                    if($arSite["DEF"] == "N" && !Option::getRealValue(MAXYSS_MODULE_NAME, "ACTIVE_ORDER_ON", $arSite["LID"])){
                        $arOptions[$arSite["LID"]]["ACTIVE_ORDER_ON"] = "";
                    }
                    if($arSite["DEF"] == "N" && !Option::getRealValue(MAXYSS_MODULE_NAME, "IBLOCK_ID", $arSite["LID"])){
                        $arOptions[$arSite["LID"]]["IBLOCK_ID"] = "";
                    }
                    if($arSite["DEF"] == "N" && !Option::getRealValue(MAXYSS_MODULE_NAME, "IBLOCK_TYPE", $arSite["LID"])){
                        $arOptions[$arSite["LID"]]["IBLOCK_TYPE"] = "";
                    }
                }
            }
        }
        else
        {
            if(!empty($options)){
                foreach ($options as $option){
                    $arOptions[$lid][strtoupper($option)] = Option::getRealValue(MAXYSS_MODULE_NAME, strtoupper($option),  $lid);
                }
            }
            else
            {
                $arOptions[$lid] = Option::getForModule(MAXYSS_MODULE_NAME, $lid);
            }
        }
        return $arOptions;
    }
    public static function saveOptions($name){
        if($name) {
            if($_REQUEST[$name]){
                foreach ($_REQUEST[$name] as $key=>$option){
                    if(is_array($option)){
                        $option_for_db = serialize($option);
                    }
                    else
                    {
                        $option_for_db = htmlspecialcharsbx($option);
                    }
                    Option::set(MAXYSS_MODULE_NAME, strtoupper($name), $option_for_db, $key);
                }
            }
        }
    }
//    public static function GetWarehouse($ozon_id = OZON_ID){
//        $api_key = OZON_API_KEY;
//        $arOptions = self::getOptions($lid = false, $options = array('OZON_ID', 'OZON_API_KEY'));
//        foreach ($arOptions as $site){
//            if($site['OZON_ID'] == $ozon_id) $api_key = $site['OZON_API_KEY'];
//        }
//        return $api_key;
//    }
    public static function checkUpdate($module_id, $tag, $message_id)
    {
        if($module_id != '') {
            $option_name = $tag . "_TIME";
            $arUpdateList = array();
            global $DB;
            $row = $DB->Query("SELECT * FROM b_option WHERE NAME='" . $option_name . "'")->Fetch();
            $time_last_check = $row['VALUE'];
            if (($time_last_check + 86400) < time()) {
                require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/update_client_partner.php");
                require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/update_class.php");

                $stableVersionsOnly = COption::GetOptionString("main", "stable_versions_only", "Y");

                $errorMessage = "";
                $myaddmodule = "";

                $arRequestedModules = CUpdateClientPartner::GetRequestedModules($myaddmodule);
                $arUpdateList = CUpdateClientPartner::GetUpdatesList($errorMessage, LANG, $stableVersionsOnly, $arRequestedModules);
            }
            $flag_update = false;
            if (!empty($arUpdateList) && !empty($arUpdateList['MODULE'])) {
                foreach ($arUpdateList['MODULE'] as $module) {
                    if ($module['@']['ID'] == $module_id) {
                        $flag_update = true;
                        $ar = Array(
                            "MESSAGE" => GetMessage($message_id),
                            "TAG" => $tag,
                            "MODULE_ID" => $module_id,
                            'NOTIFY_TYPE' => 'E'
                        );
                        $ID = CAdminNotify::Add($ar);
                        Bitrix\Main\Config\Option::set($module_id, $tag . "_TIME", time());
                    }
                }
                if(!$flag_update){
                    CAdminNotify::DeleteByTag($tag);
                    Bitrix\Main\Config\Option::set($module_id, $tag . "_TIME", time());
                }
            }
            elseif(empty($arUpdateList['MODULE']))
            {
                Bitrix\Main\Config\Option::set($module_id, $tag . "_TIME", time());
            }
            if($time_last_check <= 0){
                Bitrix\Main\Config\Option::set($module_id, $tag . "_TIME", time());
            }
        }
    }
}

function _is_curl_installed() {
    if (in_array ('curl', get_loaded_extensions())) {
        return true;
    }
    else {
        return false;
    }
}

class CCustomTypeOzonCat{

    // get the attributes from Ozone
    public static function GetAttrOzon($ClientId = OZON_ID, $ApiKey = OZON_API_KEY, $base_url = OZON_BASE_URL, $category_id)
    {
        $attr = array();

        if(Option::get(MAXYSS_MODULE_NAME, "REQURED_MORE", "") == 'Y'){
            $data_string = array(
                'category_id' => array(intval($category_id)),
                'attribute_type' => 'ALL',
                'language'=> 'RU'
            );
        }else{
            $data_string = array(
                'attribute_type' => 'REQUIRED',
//                'attribute_type' => 'required',
                'category_id' => array(intval($category_id)),
                'language'=> 'RU'
            );
        }

        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $attr = CRestQuery::rest_query($ClientId = OZON_ID, $ApiKey = OZON_API_KEY, $base_url = OZON_BASE_URL, $data_string, "/v3/category/attribute");


        return $attr;
    }

    public static function GetAttrOzonFromSave($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $category_id)
    {
        $attr = array();

        if(Option::get(MAXYSS_MODULE_NAME, "REQURED_MORE", "") == 'Y'){
            $data_string = array(
                'category_id' => array(intval($category_id)),
                'attribute_type' => 'ALL',
                'language'=> 'RU'
            );
        }else{
            $data_string = array(
                'attribute_type' => 'REQUIRED',
//                'attribute_type' => 'required',
                'category_id' => array(intval($category_id)),
                'language'=> 'RU'
            );
        }

        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $attr_res = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v3/category/attribute");
        if(!empty($attr_res[0]['attributes']))
            $attr = $attr_res[0]['attributes'];

        return $attr;
    }

    // get values for attribute
    public static  function GetValsOzon($category_id, $attribute_id, $last_value_id, $ClientId){

        $ApiKey = CMaxyssOzon::GetApiKey($ClientId);

        $data_string = array(
            "attribute_id" => intval($attribute_id),
            'category_id' => intval($category_id),
            'language'=> 'RU',
            "last_value_id" => intval($last_value_id),
            "limit" => 5000
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $arvals = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/category/attribute/values");
        return $arvals;
    }

    // get the categories from ozone  OLD-version
    public static function GetCatOzon($ClientId = OZON_ID, $ApiKey = OZON_API_KEY, $base_url = OZON_BASE_URL)
    {
        if (_is_curl_installed()) {
            $arCategorys = array();

            $data_string = array(
                'language' => 'RU'
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);

            $arCategorys = CRestQuery::rest_query($ClientId = OZON_ID, $ApiKey = OZON_API_KEY, $base_url = OZON_BASE_URL, $data_string, "/v1/category/tree");

            if(!isset($arCategorys['error'])) {
                if (!function_exists('array_flatten')) {
                    function array_flatten($array)
                    {
                        $return = array();
                        foreach ($array as $key => $value) {
                            if (count($value['children']) > 0) {
                                $return = $return + array_flatten($value['children']);
                            } else {
                                $return[$value['category_id']] = $value['title'];
                            }
                        }
                        return $return;
                    }
                }

                $arCategorys = array_flatten($arCategorys);
            }else{
                echo $arCategorys['error'];
            }

            return $arCategorys;

        } else {
            echo "cURL is <span style=\"color:#dc4f49\">not installed</span> on this server";
        }
    }

    // get the categories ozone from iblock
    public static function GetCatOzonFromBD(){
        $arCategorys = array();
        $arFilter = Array('IBLOCK_CODE'=>'ozon');
        $db_list = CIBlockSection::GetList(Array('name'=>'asc'), $arFilter, false);
        while($ar_result = $db_list->GetNext())
        {
            $arCategorys[$ar_result['NAME']]= $ar_result['DESCRIPTION'];
        }
        return $arCategorys;
    }

    public static function GetUserTypeOzonCat() {
        return array(
            'PROPERTY_TYPE'           => 'S',
            'USER_TYPE'             	=> 'maxyss_ozon',
            'DESCRIPTION'           	=> GetMessage('CATEGORY_NAME_TEXT'),
            'GetPropertyFieldHtml'  	=> array('CCustomTypeOzonCat', 'GetPropertyFieldHtml'),
            'GetAdminListViewHTML'  	=> array('CCustomTypeOzonCat', 'GetAdminListViewHTML'),
            'ConvertToDB'           	=> array('CCustomTypeOzonCat', 'ConvertToDB'),
            'ConvertFromDB'         	=> array('CCustomTypeOzonCat', 'ConvertToDB')
        );
    }
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName) {
        CJSCore::Init(array('maxyss_ozon'));
        $ID = intval($_REQUEST['ID']); //
        $IBLOCK_ID = intval($_REQUEST['IBLOCK_ID']); //
        $flag_upload = false;

        $arOptions = CMaxyssOzon::getOptions();
        if (!empty($arOptions)) {
            foreach ($arOptions as $key => $lid) {
                if ($lid["IBLOCK_ID"] == $IBLOCK_ID){
                    $flag_upload = true;
                }
            }
        }

        global $APPLICATION;
        if($APPLICATION->GetCurPage() != '/bitrix/admin/iblock_list_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/iblock_element_admin.php' && $APPLICATION->GetCurPage() != '/bitrix/admin/cat_product_admin.php' && !strpos($APPLICATION->GetCurPage(), 'shop/settings/menu_catalog_goods')
        ) {
            echo '<input type="text" id="autocomplete_ozon" class="autocomplete_ozon"  title="' . GetMessage('CATEGORY_ENTER_TEXT') . '" placeholder="' . GetMessage('CATEGORY_SEARCH_TEXT') . '" name="' . $strHTMLControlName["DESCRIPTION"] . '" value="' . $value["DESCRIPTION"] . '">';
            echo '<input data-category-ozon="" id="value_ozon"  type="text" readonly name="' . $strHTMLControlName['VALUE'] . '" value="' . htmlspecialcharsbx($value['VALUE']) . '"><a style="margin-left: 10px" href="javascript:void(0);" onclick=edit_value();>edit</a>';
            if($flag_upload) {
                echo '<a style="margin-left: 10px" href="javascript:void(0);" onclick=upload_ozon(' . $ID . ',' . $IBLOCK_ID . ');>' . GetMessage("MAXYSS_OZON_UPLOAD") . '</a>';
            }
            echo '<a style="margin-left: 10px" href="javascript:void(0);" onclick=get_info_ozon(' . $ID . ',' . $IBLOCK_ID . ');>' . GetMessage("MAXYSS_OZON_INFO") . '</a>';
            echo '<div class="ozon_attr" id="ozon_attr_id"></div>';


        $arCat = self::GetCatOzonFromBD();

        if(is_array($arCat)) {
            if(count($arCat)>0) {
                foreach ($arCat as $key => $Cat):
                    $c['value'] = $key;
                    $c['label'] = $Cat;
                    $arCat_[]=$c;
                endforeach;
                ?>
                <script data-skip-moving="true" type="text/javascript">
                    var name_val=<?=CUtil::PhpToJSObject($strHTMLControlName['VALUE'])?>;
                    var availableTags=<?=CUtil::PhpToJSObject($arCat_)?>;
                </script>
                <?
                if (strlen($value['VALUE']) > 0) {
//                    $arOzonAttrCustom =  CUtil::JsObjectToPhp(htmlspecialchars_decode($value['VALUE']));
                    ?>
                    <script data-skip-moving="true" type="text/javascript">
                        var attr_val =<?=htmlspecialchars_decode($value['VALUE'])?>;
                        function get_custom_html() {
                            let wait_autocomplete_ozon = BX.showWait('autocomplete_ozon');
                            BX.ajax({
                                method: 'POST',
                                dataType: 'html',
                                timeout: 30,
                                url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php',
                                data: {
                                    category: attr_val.category.id,
                                    action: 'get_attr_from_bd',
                                    iblock_id: '<?=$_REQUEST["IBLOCK_ID"]?>',
                                },
                                onsuccess: function (data) {
                                    BX.closeWait('wait_autocomplete_ozon', wait_autocomplete_ozon);
                                    if (data != null) {
                                        document.querySelector('.ozon_attr').innerHTML = data;
                                        setTimeout(function () {
                                            $.each(attr_val, function (index, val) {
                                                if(index !== 'category'){
                                                    $.each(val.values, function (c, dictionary_value) {
                                                        if(dictionary_value.dictionary_value_id > 0) {
                                                            if(index == 31 || index == 85) {
                                                                $('[data-ozon-attrid="' + index + '"]').val(dictionary_value.value).data('ozon-attr-valueid', dictionary_value.dictionary_value_id);
                                                            }
                                                            else
                                                                $('[data-ozon-attrid="' + index + '"] option[value="' + dictionary_value.dictionary_value_id + '"]').prop('selected', true);
                                                        }
                                                        else {
                                                            $('[data-ozon-attrid="' + index + '"]').val(dictionary_value.value);
                                                        }
                                                    })
                                                }
                                                else
                                                {
                                                    $("input[name='" + name_val + "']").data('category-ozon', val.id);
                                                    //$("input[name='" + name_val + '_'  + '<?//=$arUserField["ENTITY_VALUE_ID"]?>//' + "']").val('<?//=$arCat[$arOzonAttrCustom['category']['id']]?>//');
                                                }
                                            })

                                        }, 500);
                                    }
                                },
                                onfailure: function () {
                                    new Error("Request failed");
                                }
                            });
                        }

                        $(document).ready(function () {
                            var html_ok = get_custom_html();
                        });

                    </script>
                    <?
                }
                ?>
                <script data-skip-moving="true" type="text/javascript">
                    $(document).ready(function () {
                        var counte_arCat = '<?=count($arCat)?>';
                        if (name_val) {

                            var input_val = $("input[name='" + name_val + "']");

                            function attr_get() {
                                var attr = {};

                                if('<?=VERSION_OZON_2?>') {
                                    $('.ozon_atr').each(function (index, value) {
                                        var type_elem = '',
                                            type_elem_input = '';
                                        type_elem = $(this).get(0).nodeName;
                                        type_elem_input = $(this).attr('type');
                                        // console.log(type_elem);

                                        if (type_elem == 'INPUT' && type_elem_input == 'checkbox' && $(this).prop('checked')) {
                                            attr[$(this).data('ozon-attrid')] = {};
                                            attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');

                                            attr[$(this).data('ozon-attrid')]['values'] = [];
                                            attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                            attr[$(this).data('ozon-attrid')]['values'][0]['dictionary_value_id'] = 0;
                                            attr[$(this).data('ozon-attrid')]['values'][0]['value'] = "true";
                                            // attr = attr + '{"id": '+$(this).data('ozon-attrid')+',"value": "true"},'

                                        }
                                        if (type_elem == 'INPUT' && type_elem_input == 'text' && $(this).data('ozon-child-attrid') && $(this).val()) {
                                            var complex_value = [];
                                            $('[data-ozon-attrid=' + $(this).data('ozon-attrid') + ']').each(function (index) {
                                                complex_value[index] = {};
                                                complex_value[index].id = $(this).data('ozon-child-attrid');
                                                complex_value[index].value = $(this).val();
                                            });

                                            attr[$(this).data('ozon-attrid')] = {};
                                            attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                            attr[$(this).data('ozon-attrid')].complex_collection = complex_value;
                                        }

                                        if (type_elem == 'INPUT' && type_elem_input == 'text' && !$(this).data('ozon-child-attrid') && $(this).val()) {
                                            attr[$(this).data('ozon-attrid')] = {};
                                            attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                            attr[$(this).data('ozon-attrid')]['values'] = [];
                                            attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                            if($(this).data('ozon-attr-valueid') > 0) {
                                                attr[$(this).data('ozon-attrid')]['values'][0]['dictionary_value_id'] = String($(this).data('ozon-attr-valueid'));
                                                attr[$(this).data('ozon-attrid')]['values'][0]['value'] = $(this).val();
                                            }
                                            else
                                                attr[$(this).data('ozon-attrid')]['values'][0]['value'] = $(this).val();
                                        }

                                        if (type_elem == 'TEXTAREA' && $(this).val()) {
                                            attr[$(this).data('ozon-attrid')] = {};
                                            attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                            attr[$(this).data('ozon-attrid')]['values'] = [];
                                            attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                            attr[$(this).data('ozon-attrid')]['values'][0]['value'] = $(this).val();
                                        }

                                        if (type_elem == 'INPUT' && type_elem_input == 'number' && !$(this).data('ozon-child-attrid') && $(this).val()) {

                                            attr[$(this).data('ozon-attrid')] = {};
                                            attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                            attr[$(this).data('ozon-attrid')]['values'] = [];
                                            attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                            attr[$(this).data('ozon-attrid')]['values'][0]['value'] = $(this).val();
                                        }

                                        if (type_elem == 'SELECT' && $(this).prop('multiple') && $(this).val()) {
                                            var ozon_attrid = $(this).data('ozon-attrid');
                                            attr[ozon_attrid] = {};
                                            attr[ozon_attrid].id = $(this).data('ozon-attrid');
                                            attr[ozon_attrid]['values'] = [];
                                            $.each($(this).val(), function (index, value) {
                                                attr[ozon_attrid]['values'][index] = {};
                                                attr[ozon_attrid]['values'][index]['dictionary_value_id'] = value;
                                            })


                                        }
                                        if (type_elem == 'SELECT' && !$(this).prop('multiple') && $(this).val()) {

                                            attr[$(this).data('ozon-attrid')] = {};
                                            attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                            attr[$(this).data('ozon-attrid')]['values'] = [];
                                            attr[$(this).data('ozon-attrid')]['values'][0] = {};
                                            attr[$(this).data('ozon-attrid')]['values'][0]['dictionary_value_id'] = $(this).val();


                                            // attr[$(this).data('ozon-attrid')].id = $(this).data('ozon-attrid');
                                            // attr[$(this).data('ozon-attrid')].value = $(this).val();
                                        }
                                    });
                                }
                                attr['category'] = {};
                                attr['category'].id = $("input[name='" + name_val + "']").data('category-ozon');

                                var text = JSON.stringify(attr);

                                input_val.val(text);
                            }

                            $("body").delegate(".ozon_atr", "change", function () {
                                attr_get();
                            });
                            $(".autocomplete_ozon").autocomplete({
                                source: availableTags,
                                select: function (event, ui) {
                                    event.preventDefault();
                                    $(this).val(ui.item.label);

                                    if ($(this).next().val() != ui.item.value) {
                                        $("input[name='<?=$strHTMLControlName['VALUE']?>']").data('category-ozon', ui.item.value);
                                        // $(this).next().val(ui.item.value);
                                        $('[name="PROPERTY_DEFAULT_VALUE"]').val(ui.item.value);
                                        var action = 'get_attr';
                                        if ('<?=VERSION_OZON_2?>')
                                            action = 'get_attr_from_bd';

                                        $.ajax({
                                            type: 'GET',
                                            url: '/bitrix/tools/maxyss.ozon/ozon_ajax.php'/*+param*/,
                                            data: {
                                                category: ui.item.value,
                                                action: action,
                                                iblock_id: '<?=$_REQUEST["IBLOCK_ID"]?>',
                                            },
                                            success: function (data) {
                                                $('.ozon_attr').html(data);

                                                setTimeout(function () {
                                                    attr_get();
                                                }, 500);
                                            },
                                            error: function (xhr, str) {
                                                alert('Error get_attr: ' + xhr.responseCode);
                                            }
                                        });

                                    }
                                    $('[data="' + ui.item.value + '"]').click();
                                }
                            });
                        }
                    });
                </script>
                <?
            }else{
                echo '<br />';
                echo GetMessage("OZON_MAXYSS_ERROR");
            }
        }
        }
        else{
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
}

class CMaxyssOzonAgent{

    static public function find_ar($items, $offer_id, $warehouse_id){
        $res = false;
        if(!empty($items) && $offer_id != '' && $warehouse_id !== false) {
            foreach ($items as $i) {
                if ($i["offer_id"] == $offer_id && $i['warehouse_id'] == $warehouse_id) {
                    $res = $i;
                    return $res;
                }
            }
        }
        elseif(!empty($items) && $offer_id != '')
        {
            foreach ($items as $i) {
                if ($i["offer_id"] == $offer_id && $i['warehouse_id'] == $warehouse_id) {
                    $res = $i;
                    return $res;
                }
            }
        }
        else
        {
            return false;
        }
        return $res;
    }
    static public function GheckAgentRun(){
        global $USER;
        if(!is_object($USER))
            $USER = new CUser;

        if (defined("ADMIN_SECTION") && $USER->IsAdmin()) {
            $flag_order = false;
            $flag_product = false;

            $arActiveOrder = CMaxyssOzon::getOptions(false, array('ACTIVE_ORDER_ON', 'PERIOD_ORDER'));
            foreach ($arActiveOrder as $key => $active) {
                if ($active["ACTIVE_ORDER_ON"] == 'Y') {
                    $res_status = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonStatusPut::ListOrderStatus('" . $key . "'%"));
                    $arResStatus = $res_status->GetNext();
                    if (intval($arResStatus['ID']) > 0) {

                        if($arResStatus['RETRY_COUNT'] > 2 && $arResStatus['ACTIVE'] == "N"){
                            $flag_order = true;
                        }

                    }

                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonLoadOrder('" . $key . "'%"));
                    if ($arRes = $res->GetNext()) {
                        if (intval($arRes['ID']) > 0) {
                            if($arRes['RETRY_COUNT'] > 2 && $arRes['ACTIVE'] == "N"){
                                $flag_order = true;
                            }
                        }

                    };

                    $res_uf = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonLoadUnfulfilledOrder('" . $key . "');"));
                    if ($arRes_uf = $res_uf->GetNext()) {
                        if (intval($arRes_uf['ID']) > 0) {
                            if($arRes_uf['RETRY_COUNT'] > 2 && $arRes_uf['ACTIVE'] == "N"){
                                $flag_order = true;
                            }
                        }

                    };
                }
            }


            $arActiveProduct = CMaxyssOzon::getOptions(false, array('ACTIVE_ON', 'PERIOD'));
            foreach ($arActiveProduct as $key => $active) {
                if ($active['ACTIVE_ON'] == 'Y') {
                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssOzonAgent::OzonUploadProduct('" . $key . "'%"));
                    if ($arRes = $res->GetNext()) {
                        if (intval($arRes['ID']) > 0) {
                            if($arRes['RETRY_COUNT'] > 2 && $arRes['ACTIVE'] == "N"){
                                $flag_product = true;
                            }
                        }
                    }
                }
            }

            if($flag_order) {
                $ar = Array(
                    "MESSAGE" => GetMessage("OZON_MAXYSS_TITLE") . ' - ' . GetMessage("MAXYSS_OZON_ORDER_ACTIVE_ERROR"),
                    "TAG" => "OZON_ORDER_ACTIVE_ERROR",
                    "MODULE_ID" => "maxyss.ozon",
                    'NOTIFY_TYPE' => 'E'
                );
                $ID = CAdminNotify::Add($ar);
            }
            else
            {
                CAdminNotify::DeleteByTag("OZON_ORDER_ACTIVE_ERROR");
            }

            if($flag_product){
                $ar = Array(
                    "MESSAGE" => GetMessage("OZON_MAXYSS_TITLE") . ' - ' . GetMessage("MAXYSS_OZON_PRODUCT_ACTIVE_ERROR"),
                    "TAG" => "OZON_PRODUCT_ACTIVE_ERROR",
                    "MODULE_ID" => "maxyss.ozon",
                    'NOTIFY_TYPE' => 'E'
                );
                $ID = CAdminNotify::Add($ar);
            }
            else
            {
                CAdminNotify::DeleteByTag("OZON_PRODUCT_ACTIVE_ERROR");
            }

            CMaxyssOzon::checkUpdate('maxyss.ozon', 'OZON_UPDATE_CHECK', 'MAXYSS_OZON_UPDATE_MESSAGE');

        }
    }
    static function bck(){
        $arInfo = array();

        if (defined("US_LICENSE_KEY"))
            $LICENSE_KEY =  US_LICENSE_KEY;
        elseif (defined("LICENSE_KEY"))
            $LICENSE_KEY =   LICENSE_KEY;
        else
        {
            $LICENSE_KEY = "demo";
            if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php"))
                include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
        }

        $arInfo['key'] = md5("BITRIX".$LICENSE_KEY."LICENCE");
        $arInfo['host'] = Option::get(MAXYSS_MODULE_NAME, "SERVER_NAME", $_SERVER["HTTP_HOST"]);
        $data_string = array(
            'info' => $arInfo
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $arResult = CRestQuery::rest_query($ClientId = '1', $ApiKey = '1', $base_url = 'https://maxyss.ru', $data_string, "/v1/");
        return $arResult;
    }
    public static function import($items, $ClientId, $ApiKey, $base_url, $filename, $lid ){
        $err = '';
        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnUploadItem", array(&$items));
        $event->send();

        foreach ($items as $key=>&$item){
            $err_item = '';
            if($item['offer_id']!='')
                $id_log = $item['offer_id'];
            else
                $id_log = $item['name'];

            $error_user = array();
            if (intval($item['category_id']) <= 0) {
                $error_user[] =  GetMessage('OZON_MAXYSS_ERROR_CAT_OZON');
                $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_CAT_OZON').', ';
            }
            if (intval($item['weight']) <= 0) {
                $error_user[] =  GetMessage('OZON_MAXYSS_ERROR_PROP');
                $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_PROP').', ';
            }
            if (intval($item['height']) <= 0){
                $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_HEIGHT');
                $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_USER_HEIGHT').', ';
            }
            if (intval($item['depth']) <= 0){
                $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_DEPTH');
                $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_USER_DEPTH').', ';
            }
            if (intval($item['width']) <= 0){
                $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_WIDTH');
                $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_USER_WIDTH'.', ');
            }
            if (intval($item['price']) <= 0){
                $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_PRICE');
                $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_USER_PRICE').', ';
            }
            if (count($item['images']) <= 0){
                $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_IMAGE');
                $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_USER_IMAGE').', ';
            }
            if ($item['offer_id'] == ''){
                $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_ARTICLE');
                $err_item = $err_item.GetMessage('OZON_MAXYSS_ERROR_USER_ARTICLE').', ';
            }
            if (VERSION_OZON_2) {
                if ($item['description'] == '') {
                    $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_DESCRIPTION');
                    $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_USER_DESCRIPTION').', ';
                }
            }else{
                if ($item['description'] == '') {
                    $error_user[] = GetMessage('OZON_MAXYSS_ERROR_USER_DESCRIPTION');
                    $err_item = $err_item. GetMessage('OZON_MAXYSS_ERROR_USER_DESCRIPTION').', ';
                }
                unset($item['description']);
            }
            if(!empty($error_user)) {
                $error_log = CHelpMaxyss::arr_from_file($filename);
                $error_log[$id_log.' - '.$item['name']] = array($error_user);
                CHelpMaxyss::arr_to_file($filename, $error_log);

                unset($items[$key]);
            }else{
                $error_log = CHelpMaxyss::arr_from_file($filename);
                if(isset($error_log[$id_log.' - '.$item['name']])) {
                    unset($error_log[$id_log.' - '.$item['name']]);
                    CHelpMaxyss::arr_to_file($filename, $error_log);
                }


                /// ����
                if ($item['price'] > 0) {

                    if($item['old_price'] > 0) {
                        if (
                            ($item['price'] / $item['old_price']) <= 0.95 &&
                            ($item['old_price'] - $item['price']) >= 10 &&
                            ($item['price'] / $item['old_price']) >= 0.1 &&
                            ($item['price'] / $item['old_price']) != 1

                        ) {
                            $item["old_price"] = strval($item['old_price']);
                        }
                        else{
                            $item["old_price"] = '0';
                        }
                    }
                }
                /// ����

            }
            if($err_item) $err = $err . '<b>' .$id_log.' '.$item['name'].'</b> - '.substr($err_item, 0, -2).'<br>';
        }

        if(!empty($items)) {
            $items = array_values($items);
            if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'import_items', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($items)));
            }
            $data_string = array(
                "items" => $items
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);

            $bck = self::bck();
            if($bck['BCK'] && $bck['BCK'] != "Y") {
                if (VERSION_OZON_2)
                    $arProduct = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/product/import");
                else
                    $arProduct = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v1/product/import");

//                if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'import', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($arProduct)));
//                }
            }
        }
    }
    public static function update_stock($items, $ClientId, $ApiKey, $base_url = OZON_BASE_URL, $filename, $lid){
        $bck = self::bck();
        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnUpdateStock", array(&$items));
        $event->send();
        $arOptions = CMaxyssOzon::getOptions($lid, array('LOG_UPLOAD_ON'));
        if(is_array($items)) {
            $arItems = array_chunk($items, 100);
            foreach ($arItems as $item) {
                $data_string = array(
                    'stocks' => $item
                );
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                $arResult = array();

                if ($bck['BCK'] && $bck['BCK'] != "Y")
                    $arResult = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/products/stocks");

                if (\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON", "N") == "Y") {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'update_stock', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($arResult)));
                }
                if (!empty($arResult)) {
                    if (is_object($arResult['error'])) {
                        $error = $arResult['error'];
                        $error_log = CHelpMaxyss::arr_from_file($filename);
                        $error_log["update_stock"][$error->code] = $error->message;
                        CHelpMaxyss::arr_to_file($filename, $error_log);
                    } else {
                        foreach ($arResult as $item_answer) {

                            if(is_array($item_answer))
                                $send_item = self::find_ar($item, $item_answer['offer_id'], $item_answer['warehouse_id']);
                            else
                                $send_item = false;

                            if ($arOptions[$lid]['LOG_UPLOAD_ON'] == 'Y' && $send_item) {
                                $connection = \Bitrix\Main\Application::getConnection();
                                if ($connection->isTableExists(CMaxyssOzonLogTable::getTableName())) {
                                    $ozonLog = CMaxyssOzonLogTable::getRow(
                                        [
                                            'select' => ['ID'],
                                            'filter' => ['=OFFER_ID' => $item_answer['offer_id'], '=TYPE_UPLOAD' => 'stock', '=OZON_ID' => $ClientId, '=WAREHOUSE_ID' => $item_answer["warehouse_id"]]
                                        ]
                                    );
                                    if ($ozonLog['ID']) {
                                        CMaxyssOzonLogTable::update($ozonLog['ID'], array(
                                            'TYPE_UPLOAD' => 'stock',
                                            'ERROR' => serialize($item_answer["errors"]),
                                            'UPDATE_RESULT' => $item_answer["updated"],
                                            'STOCK' => intval($send_item["stock"]),
                                            'DATE_UPLOAD' => new Type\DateTime()
                                        ));
                                    } else {
                                        CMaxyssOzonLogTable::add(array(
                                            'TYPE_UPLOAD' => 'stock',
                                            'OFFER_ID' => $item_answer["offer_id"],
                                            'ERROR' => serialize($item_answer["errors"]),
                                            'UPDATE_RESULT' => $item_answer["updated"],
                                            'WAREHOUSE_ID' => $item_answer["warehouse_id"],
                                            'STOCK' => intval($send_item["stock"]),
                                            'PRODUCT_ID' => $item_answer["product_id"],
                                            'OZON_ID' => $ClientId
                                        ));
                                    }
                                }
                            }

                            if (!empty($item_answer["errors"])) {
                                $error_log = CHelpMaxyss::arr_from_file($filename);
                                foreach ($item_answer["errors"] as $err) {
                                    $error_log[$item_answer["offer_id"]][$err["code"]] = array("code" => $err["code"], "message" => $err["message"]);
                                }
                                CHelpMaxyss::arr_to_file($filename, $error_log);
                            }
                        }
                    }
                }
            }
        }

    }
    public static function update_price($items, $ClientId, $ApiKey, $base_url, $filename, $lid){
        $bck = self::bck();
        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnUpdatePrice", array(&$items));
        $event->send();
        $arOptions = CMaxyssOzon::getOptions($lid, array('LOG_UPLOAD_ON'));
        $data_string = array(
            'prices' => $items
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $arResult = array();
        if($bck['BCK'] && $bck['BCK'] != "Y")
            $arResult = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v1/product/import/prices");

        if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'update_price', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($arResult)));
        }

        if(!empty($arResult)) {
            if (is_object($arResult['error'])) {
                $error = $arResult['error'];
                $error_log = CHelpMaxyss::arr_from_file($filename);
                $error_log["update_stock"][$error->code] = $error->message;
                CHelpMaxyss::arr_to_file($filename, $error_log);
            } else {
                foreach ($arResult as $item_answer) {

                    if(is_array($item_answer))
                        $send_item = self::find_ar($items, $item_answer['offer_id'], false);
                    else
                        $send_item = false;

                    if($arOptions[$lid]['LOG_UPLOAD_ON'] == 'Y' && $send_item ) {
                        $connection = \Bitrix\Main\Application::getConnection();
                        if ($connection->isTableExists(CMaxyssOzonLogTable::getTableName())) {
                            $ozonLog = CMaxyssOzonLogTable::getRow(
                                [
                                    'select' => ['ID'],
                                    'filter' => ['=OFFER_ID' => $item_answer['offer_id'], '=TYPE_UPLOAD' => 'price', '=OZON_ID' => $ClientId]
                                ]
                            );
                            if ($ozonLog['ID']) {
                                CMaxyssOzonLogTable::update($ozonLog['ID'], array(
                                    'ERROR' => serialize($item_answer["errors"]),
                                    'UPDATE_RESULT' => $item_answer["updated"],
                                    'PRICE' => intval($send_item["price"]),
                                    'OLD_PRICE' => intval($send_item["old_price"]),
                                    'MIN_PRICE' => intval($send_item["min_price"]),
                                    'DATE_UPLOAD' => new Type\DateTime()
                                ));
                            } else {
                                CMaxyssOzonLogTable::add(array(
                                    'TYPE_UPLOAD' => 'price',
                                    'OFFER_ID' => $item_answer["offer_id"],
                                    'ERROR' => serialize($item_answer["errors"]),
                                    'UPDATE_RESULT' => $item_answer["updated"],
                                    'PRICE' => intval($send_item["price"]),
                                    'OLD_PRICE' => intval($send_item["old_price"]),
                                    'MIN_PRICE' => intval($send_item["min_price"]),
                                    'PRODUCT_ID' => $item_answer["product_id"],
                                    'OZON_ID' => $ClientId
                                ));
                            }
                        }
                    }

                    if (!empty($item_answer["errors"])) {
                        $error_log = CHelpMaxyss::arr_from_file($filename);
                        foreach ($item_answer["errors"] as $err) {
                            $error_log[$item_answer["offer_id"]][$err["code"]] = array("code"=>$err["code"], "message" => $err["message"]);
                        }
                        CHelpMaxyss::arr_to_file($filename, $error_log);
                    }
                }
            }
        }
    }
    public static function get_products($arItemID, $arItemOzon, $ClientId, $ApiKey, $base_url, $filename, $lid){
        $arOptions = CMaxyssOzon::getOptions($lid, $options = array('NO_UPLOAD_PRODUCT', 'NO_UPLOAD_PRICE', 'DEACTIVATE', 'LIMIT', 'LIMIT_V2', 'LIMIT_PRICE', 'RE_UPLOAD_PRODUCT', 'WEIGHT_MIN', 'WEIGHT_MAX'));
        $arDeactivateWarehouses = unserialize($arOptions[$lid]["DEACTIVATE"]);
        foreach ($arItemID as $key=>$id){
            if($id == '')
                unset($arItemID[$key]);
        }

        $arAdd = $arItemOzon;
        $arUpdatePrice = array();
        $arUpdateStock = array();

        $flags = array();
        if(!empty($arItemID)) {

            $arItemsIdChunk = array_chunk($arItemID, 1000);
            foreach ($arItemsIdChunk as $items) {

                $data_string = array(
                    "filter" => array(
                        "offer_id" => $items
                    ),
                    "visibility" => "ALL",
                    "last_id" =>  "",
                    "limit" =>  1000
                );

                $data_string = \Bitrix\Main\Web\Json::encode($data_string);


                $arProducts = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/product/list");
                if (!isset($arProducts['error'])) {

                    $flags['f_true'] = true;

                    if (!empty($arProducts['items'])) {
                        // select products to update the price and quantity
                        foreach ($arProducts['items'] as $prod) {
                                if(is_array($arItemOzon[$prod['offer_id']]['stock'])){
                                    foreach ($arItemOzon[$prod['offer_id']]['stock'] as $warehouse => $stock) {
                                        $stock_res = self::stock_limits($prod['offer_id'], $stock, $warehouse, $arItemOzon, $arOptions[$lid]);
                                        if($arDeactivateWarehouses[$warehouse] != 'Y') {
                                            $arUpdateStock[] = array(
                                                "offer_id" => $prod['offer_id'],
                                                "stock" => ($stock_res > 0) ? intval($stock_res) : 0,
                                                "warehouse_id" => $warehouse
                                            );
                                        }
                                    }
                                }

                            if ($arItemOzon[$prod['offer_id']]['price'] > 0) {
                                $arPrice = array(
                                    "offer_id" => $prod['offer_id'],
                                    "price" => $arItemOzon[$prod['offer_id']]['price'],
                                    "min_price" => $arItemOzon[$prod['offer_id']]['min_price'],
                                );

                                if($arItemOzon[$prod['offer_id']]['old_price'] > 0) {
                                    if (
                                        (round($arItemOzon[$prod['offer_id']]['price'] / $arItemOzon[$prod['offer_id']]['old_price'], 2)) <= 0.95 &&
                                        ($arItemOzon[$prod['offer_id']]['old_price'] - $arItemOzon[$prod['offer_id']]['price']) >= 10 &&
                                        ($arItemOzon[$prod['offer_id']]['price'] / $arItemOzon[$prod['offer_id']]['old_price']) >= 0.1 &&
                                        ($arItemOzon[$prod['offer_id']]['price'] / $arItemOzon[$prod['offer_id']]['old_price']) != 1 // ���������!

                                    ) {
                                        $arPrice["old_price"] = strval($arItemOzon[$prod['offer_id']]['old_price']);
                                    }
                                    else{
                                        $arPrice["old_price"] = '0';
                                    }
                                }
                                else
                                {
                                    $arPrice["old_price"] = '0';
                                }
                                $arUpdatePrice[] = $arPrice; // array for updating prices
                            }
                            unset($arAdd[$prod['offer_id']]);

                        }

                    }

                } else {
                    $flags['f_false'] = true;
                    $ERROR = 'Did not receive goods from Ozone';
                    if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'get_products', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($arProducts)));
                    }
                    $error_log = CHelpMaxyss::arr_from_file($filename);
                    $error_log['get_products'][0] = $arProducts['error'];
                    CHelpMaxyss::arr_to_file($filename, $error_log);
                }
            }
            if (!empty($arUpdateStock)) {
                self::update_stock($arUpdateStock, $ClientId, $ApiKey, $base_url, $filename, $lid);
            }
            if (!empty($arUpdatePrice) && $arOptions[$lid]['NO_UPLOAD_PRICE'] != "Y") {
                self::update_price($arUpdatePrice, $ClientId, $ApiKey, $base_url, $filename, $lid);
            }


            if($arOptions[$lid]['NO_UPLOAD_PRODUCT'] != "Y" && $arOptions[$lid]['RE_UPLOAD_PRODUCT'] != "Y") {
                if (!empty($arAdd) && !isset($flags['f_false']) && $flags['f_true']) {
                    foreach ($arAdd as &$val) {
                        unset($val['stock']);
                    }
                    $arItemsIdChunk_import = array_chunk($arAdd, 100);
                    foreach ($arItemsIdChunk_import as $items_import) {
                        self::import(array_values($items_import), $ClientId, $ApiKey, $base_url, $filename, $lid);
                    }
                }
            }
            elseif($arOptions[$lid]['RE_UPLOAD_PRODUCT'] == "Y")
            {
                if (!empty($arItemOzon)) {
                    foreach ($arItemOzon as &$val) {
                        unset($val['stock']);
                    }
                    $arItemsIdChunk_import = array_chunk($arItemOzon, 100);
                    foreach ($arItemsIdChunk_import as $items_import) {
                        self::import(array_values($items_import), $ClientId, $ApiKey, $base_url, $filename, $lid);
                    }
                }
            }
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
                            $result = strval($arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] + $formula);
                            break;

                       case 'MULTIPLY':
                            $result = strval($arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] * $formula);
                            break;

                       case 'DIVIDE':
                           if($formula != 0)
                               $result = strval($arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] / $formula);
                           else
                               $result = strval($arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE']);
                           break;

                       case 'SUBTRACT':
                            $result = strval($arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE'] - $formula);
                            break;

                        default:
                            break;
                    }
                }
                else
                {
                    $result = strval($arFields['PROPERTY_'.strtoupper($type_prop).'_VALUE']);
                }

            }else{
                $result = '';
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
                        $arErrors[] = GetMessage('OZON_MAXYSS_ERROR_PRICE');
                    else
                        $selectedPriceType = $price;
                    unset($priceType, $priceIterator);
                } else {
                    $arErrors[] = GetMessage('OZON_MAXYSS_ERROR_PRICE');
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
                                $result = strval(round($arPrice['RESULT_PRICE']['BASE_PRICE'] + $formula, 0));
                                break;

                            case 'MULTIPLY':
                                $result = strval(round($arPrice['RESULT_PRICE']['BASE_PRICE'] * $formula, 0));
                                break;

                            case 'DIVIDE':
                                if($formula != 0)
                                    $result = strval(round($arPrice['RESULT_PRICE']['BASE_PRICE'] / $formula, 0));
                                else
                                    $result = strval(round($arPrice['RESULT_PRICE']['BASE_PRICE'], 0));
                                break;

                            case 'SUBTRACT':
                                $result = strval(round($arPrice['RESULT_PRICE']['BASE_PRICE'] - $formula, 0));
                                break;

                            default:
                                break;
                        }
                    }
                    else
                    {
                        $result = strval(round($arPrice['RESULT_PRICE']['BASE_PRICE'], 0));
                    }
                } else {
                    if($formula != '' && $formula_action != 'NOT'){
                        switch ($formula_action){
                            case 'ADD':
                                $result = strval(round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] + $formula, 0));
                                break;

                            case 'MULTIPLY':
                                $result = strval(round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] * $formula, 0));
                                break;

                            case 'DIVIDE':
                                if($formula != 0)
                                    $result = strval(round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] / $formula, 0));
                                else
                                    $result = strval(round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'], 0));
                                break;

                            case 'SUBTRACT':
                                $result = strval(round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'] - $formula, 0));
                                break;

                            default:
                                break;
                        }
                    }
                    else
                    {
                        $result = strval(round($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'], 0));
                    }
                }
            }else{
                $result = '';
            }
        }
        return $result;
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
    public static function  getSincAttr($iblock_attr_id, $arOzonAttrTovar, $arFields, $arProps){

        if($arOzonAttrTovar['category']['id'] != '' && $iblock_attr_id > 0) {
            $arFilterAttr = Array("IBLOCK_ID" => $iblock_attr_id, 'SECTION_CODE' => $arOzonAttrTovar['category']['id']);

            $res_el = CIBlockElement::GetList(Array('PROPERTY_is_required' => 'desc'), $arFilterAttr, false, false, array('ID', 'IBLOCK_ID', 'DETAIL_TEXT', 'PREVIEW_TEXT', 'NAME', 'PROPERTY_*'));
            while ($ob = $res_el->GetNextElement()) {
                $arFieldsAttr = $ob->GetFields();
                $arPropsAttr = $ob->GetProperties();

                $arVals = unserialize($arFieldsAttr['~DETAIL_TEXT']);
                $arSinc = unserialize($arFieldsAttr['~PREVIEW_TEXT']);

                if ($arSinc[$arFields["IBLOCK_ID"]]["prop_code"] != '' /*&& !isset($arOzonAttrTovar[$arPropsAttr['id']['VALUE']])*/) {

                    if(isset($arOzonAttrTovar[$arPropsAttr['id']['VALUE']]) && $arPropsAttr['id']['VALUE'] == 31 || $arPropsAttr['id']['VALUE'] == 85){
                        if(isset($arOzonAttrTovar[$arPropsAttr['id']['VALUE']]['values'][0]['dictionary_value_id']) && isset($arOzonAttrTovar[$arPropsAttr['id']['VALUE']]['values'][0]['value'])){
                            continue;
                        }
                        else{
                            unset($arOzonAttrTovar[$arPropsAttr['id']['VALUE']]['values']);
                        }
                    }
                    elseif (isset($arOzonAttrTovar[$arPropsAttr['id']['VALUE']]) && ($arPropsAttr['id']['VALUE'] != 31 && $arPropsAttr['id']['VALUE'] != 85)){
                        continue;
                    }

                    $one_attr['id'] = intval($arPropsAttr['id']['VALUE']);
                    $one_attr['name'] = $arFieldsAttr['NAME'];
                    $one_attr['type'] = $arPropsAttr['type']['VALUE'];
                    $one_attr['is_collection'] = $arPropsAttr['is_collection']['VALUE'];
                    $one_attr['option'] = (!empty($arVals)) ? count($arVals) : 0;
                    $one_attr['sinc'] = $arSinc;


                    if ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']) {
                        if ($one_attr['option'] > 0) { // dictionari
                            if ($one_attr['is_collection'] == 1) { //multi_dictionari
                                if (isset($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME']) && $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME'] !='') {  // ���������� ��������
                                    if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� ����������
                                        foreach ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] as $val) {
                                            if($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array('dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val]));
                                            }
                                        }
                                    }
                                    elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] != '') // ��������� �������� ����������
                                    {
                                        if($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']] != '') {
                                            $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                            $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']])
                                            );
                                        }
                                    }
                                }
                                elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['PROPERTY_TYPE'] == 'E')
                                {
                                    if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� ������
                                        foreach ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] as $val) {
                                            if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val]) > 0) {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array('dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val]));
                                            }
                                        }
                                    }
                                    elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] != '') // ��������� �������� ������
                                    {
                                        if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']])>0) {
                                            $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                            $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']])
                                            );
                                        }
                                    }
                                }
                                else // ������ ��������
                                {
                                    if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM'])) { // ������������� �������� ������
                                        foreach ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM_ID'] as $val) {
                                            if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val]) > 0) {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array('dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val]));
                                            }
                                        }
                                    }
                                    elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM'] != '') // ��������� �������� ������
                                    {
                                        if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM_ID']])>0) {
                                            $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                            $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM_ID']])
                                            );
                                        }
                                    }
                                }
                            }
                            else
                            {
                                if (isset($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME']) && $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME'] !='')
                                {  // ���������� ��������
                                    if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� ����������
                                            if($arSinc[$arFields["IBLOCK_ID"]]["sinc"][0] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array('dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][0]));
                                            }

                                    }
                                    elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] != '') // ��������� �������� ����������
                                    {
                                        if($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']] != '') {
                                            $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                            $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']])
                                            );


                                        }
                                    }
                                }
                                elseif (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']))
                                { // ������������� �������� ������
                                    if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM_ID'][0]]) > 0) {
                                        $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                        $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                            'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM_ID'][0]])
                                        );
                                    }
                                    elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['PROPERTY_TYPE'] == 'E')
                                    {
                                        $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                        $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                            'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'][0]])
                                        );
                                    }
                                }
                                else // ��������� �������� ������
                                {
                                    if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM_ID']]) > 0) {
                                        $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                        $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                            'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE_ENUM_ID']])
                                        );
                                    }
                                    elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['PROPERTY_TYPE'] == 'E')
                                    {
                                        $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                        $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                            'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']])
                                        );
                                    }
                                }
                            }
                        }
                        else // value
                        {
                            if (!isset($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME'])) {
                                if ($one_attr['is_collection'] == 1)
                                { //multi_value
                                    if($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['PROPERTY_TYPE'] == 'E'){
                                        if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� ������
                                            foreach ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] as $val) {
                                                if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val]) > 0) {
                                                    $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                    $arOzonAttrTovar[$one_attr['id']]['values'][] = array('dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$val]));
                                                }
                                            }
                                        }
                                        elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] != '') // ��������� �������� ������
                                        {
                                            if(intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']])>0) {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                    'dictionary_value_id' => intval($arSinc[$arFields["IBLOCK_ID"]]["sinc"][$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']])
                                                );
                                            }
                                        }
                                    }else {
                                        if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� �����
                                            foreach ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] as $val) {
                                                if ($val != '') {
                                                    $arOzonAttrTovar[$one_attr['id']]['id'] = intval(intval($one_attr['id']));
                                                    $arOzonAttrTovar[$one_attr['id']]['values'][] = array('value' => $val);
                                                }
                                            }
                                        } else // ��������� �������� �����
                                        {
                                            if ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval(intval($one_attr['id']));
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                    'value' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']
                                                );
                                            }
                                        }
                                    }
                                } else {
                                    if($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['PROPERTY_TYPE'] == 'E'){  // ��������
                                        if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) {
                                            if ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'][0] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                    'value' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'][0]
                                                );
                                            }
                                        }
                                        else
                                        {
                                            if ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                    'value' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']
                                                );
                                            }
                                        }

                                    }else {
                                        if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� ����� ��� ���� ��� HTML
                                            if ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'][0] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval(intval($one_attr['id']));
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                    'value' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'][0]
                                                );
                                            } elseif ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']['TEXT'] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval(intval($one_attr['id']));
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                    'value' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']['TEXT']
                                                );
                                            }
                                        } else {
                                            if ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] != '') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval(intval($one_attr['id']));
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                    'value' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                            elseif(isset($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME']) && $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME'] !='')
                            {
                                // �� ����������� �������� �����
                                $allProp = array();
                                $hlblock = HL\HighloadBlockTable::getRow([
                                    'filter' => [
                                        '=TABLE_NAME' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['USER_TYPE_SETTINGS']['TABLE_NAME']
                                    ],
                                ]);

                                $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                                $main_query = new Entity\Query($entity);
                                $main_query->setSelect(array('*'));
                                $main_query->setFilter(array('UF_XML_ID' => $arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']));
                                $result = $main_query->exec();
                                $result = new CDBResult($result);

                                while($row = $result->Fetch()) {
                                    $allProp[$row['UF_XML_ID']] = $row['UF_NAME'];
                                }

                                if ($one_attr['is_collection'] == 1) { //multi_value
                                    if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� �����
                                        foreach ($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'] as $val) {
                                            if($allProp[$val] !='') {
                                                $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                                $arOzonAttrTovar[$one_attr['id']]['values'][] = array('value' => $allProp[$val]);
                                            }
                                        }
                                    }
                                    else // ��������� �������� �����
                                    {
                                        if($allProp[$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']] !='') {
                                            $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                            $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                'value' => $allProp[$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']]
                                            );
                                        }
                                    }
                                } else {
                                    if (is_array($arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'])) { // ������������� �������� �����
                                        if($allProp[$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'][0]] !='') {
                                            $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                            $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                'value' => $allProp[$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE'][0]]
                                            );
                                        }
                                    } else {
                                        if($allProp[$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']] != '') {
                                            $arOzonAttrTovar[$one_attr['id']]['id'] = intval($one_attr['id']);
                                            $arOzonAttrTovar[$one_attr['id']]['values'][] = array(
                                                'value' => $allProp[$arProps[$arSinc[$arFields["IBLOCK_ID"]]["prop_code"]]['VALUE']]
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $arOzonAttrTovar;
    }

    public static function arStock($ar_prod, $id, $arSettings){
        $arVarehouses = unserialize($arSettings['SKLAD_ID_V3']);
        $arVHstock = array();
        if(is_array($arVarehouses)) {
            foreach ($arVarehouses as $key_vh => $varehouse){
                $amount_vh = 0;
                if(is_array($varehouse)) {
                    foreach ($varehouse as $sklad_id) {
                        $rsStore = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' => $id, 'STORE_ID' => $sklad_id), false, false, array('AMOUNT'));
                        if ($arStore = $rsStore->Fetch()) {
                            $amount_vh += $arStore['AMOUNT'];
                        } else {
                            $amount_vh += 0;
                        }
                    }
                }else{
                    $amount_vh = $ar_prod['QUANTITY'];
                }
                $arVHstock[$key_vh] = $amount_vh;
            }
        }else{
            $arVHstock = $ar_prod['QUANTITY'];
        }
        return $arVHstock;
    }
    public static function vat($ar_prod, $arSettings){
        $vat= '0';
        if(!isset($arSettings["VAT_CUSTOM"]) || $arSettings["VAT_CUSTOM"] == 0) {
            if($ar_prod['VAT_ID'] !='') {
                $dbVat = CCatalogVat::GetByID($ar_prod['VAT_ID']);
                if ($arVat = $dbVat->GetNext()) {
                    $vat = strval(intval($arVat["RATE"])/100);
                }
            }
        }else{
            $dbVat = CCatalogVat::GetByID($arSettings["VAT_CUSTOM"]);
            if ($arVat = $dbVat->GetNext()) {
                $vat = strval(intval($arVat["RATE"]) / 100);
            }
        }
        return $vat;
    }
    public static function stock_limits($offer_id, $stock, $warehouse, $arItemOzon, $arSettings){
        $stock_res = $stock;
        $arLimitWarehouses = unserialize($arSettings["LIMIT"]);
        $arLimitWarehousesPrice = unserialize($arSettings["LIMIT_PRICE"]);
        $arLimitWarehousesWeightMin = unserialize($arSettings["WEIGHT_MIN"]);
        $arLimitWarehousesWeightMax = unserialize($arSettings["WEIGHT_MAX"]);
        if ($arLimitWarehouses[$warehouse] > 0 || $arLimitWarehousesPrice[$warehouse] > 0) {
            if ($stock < $arLimitWarehouses[$warehouse]) $stock_res = 0;
            if ($arItemOzon[$offer_id]['price'] < $arLimitWarehousesPrice[$warehouse]) $stock_res = 0;
        }

        if($arLimitWarehousesWeightMin && $arLimitWarehousesWeightMax) {
            if ($arLimitWarehousesWeightMin[$warehouse] > 0 && $arLimitWarehousesWeightMax[$warehouse] > 0) {
                if (
                    $arItemOzon[$offer_id]['weight'] > $arLimitWarehousesWeightMin[$warehouse] &&
                    $arItemOzon[$offer_id]['weight'] < $arLimitWarehousesWeightMax[$warehouse]
                ) $stock_res = 0;
            }
            elseif ($arLimitWarehousesWeightMin[$warehouse] > 0 && $arLimitWarehousesWeightMax[$warehouse] <= 0) {
                if (
                    $arItemOzon[$offer_id]['weight'] > $arLimitWarehousesWeightMin[$warehouse]
                ) $stock_res = 0;
            }
            elseif ($arLimitWarehousesWeightMin[$warehouse] <= 0 && $arLimitWarehousesWeightMax[$warehouse] > 0) {
                if (
                    $arItemOzon[$offer_id]['weight'] < $arLimitWarehousesWeightMax[$warehouse]
                ) $stock_res = 0;
            }
        }
        return $stock_res;
    }
    public static function get_section_attr( $IBLOCK_SECTION_ID, $arSettings){
        $arSecAttr = array();
        $arOzonAttrTovar = array();
        if($IBLOCK_SECTION_ID > 0) {
            $cache_dir = "/maxyss.ozon";
            $obCache = new CPHPCache();
            if ($obCache->InitCache(3600, serialize(array($IBLOCK_SECTION_ID, $arSettings['IBLOCK_ID'])), $cache_dir))
            {
                $arOzonAttrTovar = $obCache->GetVars();
            }
            elseif ($obCache->StartDataCache())
            {
                if(!isset($arSecAttr[$IBLOCK_SECTION_ID])) {
                    $allSecId = array();
                    $nav = CIBlockSection::GetNavChain(false, $IBLOCK_SECTION_ID);
                    while ($arSectionPath = $nav->GetNext()) {
                        $allSecId[$arSectionPath['ID']] = $arSectionPath['ID'];
                    }
                    $allSecId = array_reverse($allSecId, true);
                    foreach ($allSecId as $sec) {
                        if(!isset($arSecAttr[$sec])) {
                            $SectListGet = array();
                            $SectList = CIBlockSection::GetList(Array("SORT" => "ASC"), array("IBLOCK_ID" => $arSettings["IBLOCK_ID"], "ID" => $sec), false, array("ID", "IBLOCK_ID", "UF_*"));
                            if ($SectListGet = $SectList->GetNext()) {

                                if (strlen($SectListGet["UF_CAT_OZON"]) > 0) {
                                    if (LANG_CHARSET == 'windows-1251')
                                        $arOzonAttrTovar = iconv('windows-1251', 'UTF-8//IGNORE', $SectListGet["UF_CAT_OZON"]);
                                    else
                                        $arOzonAttrTovar = $SectListGet["UF_CAT_OZON"];

                                    $arOzonAttrTovar = CUtil::JsObjectToPhp(htmlspecialchars_decode($arOzonAttrTovar));
                                    $arSecAttr[$SectListGet['ID']] = $arOzonAttrTovar; // ������������� ������

                                    break;
                                }

                            }
                        }
                        else
                        {
                            $arOzonAttrTovar = $arSecAttr[$sec];
                        }
                    }
                }
                else
                {
                    $arOzonAttrTovar = $arSecAttr[$IBLOCK_SECTION_ID];
                }
                global $CACHE_MANAGER;
                $CACHE_MANAGER->StartTagCache($cache_dir);
                $CACHE_MANAGER->RegisterTag("maxyss_ozon_section_id_attr".$IBLOCK_SECTION_ID);
                $CACHE_MANAGER->EndTagCache();
                $obCache->EndDataCache($arOzonAttrTovar);
            }
        }
        return $arOzonAttrTovar;
    }

    public static function OzonUploadProduct($lid='', $id=1, $filter = array()){
        $arSettings = array();
        $arOptions = CMaxyssOzon::getOptions($lid);
        if($lid !='') {
            $arSettings = $arOptions[$lid];
            $arSettings['SITE'] = $lid;
        }
        else {
            $arSettings = $arOptions[key($arOptions)];
            $arSettings['SITE'] = $lid = key($arOptions);
        }
        if( strpos($arSettings['SKLAD_ID'], '}') ){
            $arSettings['SKLAD_ID'] = unserialize($arSettings['SKLAD_ID']);
        }
        if(!isset($arSettings['MAX_COUNT'])) $arSettings['MAX_COUNT'] = 100;

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_MODULE_NAME."/log.txt", print_r(date('d.m.y H.i.s').' - start upload', true).PHP_EOL);

        $filename = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME ."/". $lid. "_log_user_".date('N').".txt";

        $day_old = '';
        if (file_exists($filename)) {
            $error_log = CHelpMaxyss::arr_from_file($filename);
            $day_old = $error_log["DATE"];

            if ($day_old != '')
                $day_old = date("d", MakeTimeStamp($day_old, "DD.MM.YYYY HH:MI:SS"));
        }
        $day_new = date ("d");

        if($day_old != $day_new)
            CHelpMaxyss::arr_to_file($filename, array('DATE'=>date('d.m.y H.i.s')));

        if($arSettings['IBLOCK_TYPE'] && $arSettings['IBLOCK_ID'] && $arSettings['PRICE_TYPE'] && $arSettings['SERVER_NAME'] && $arSettings['SITE']) {

            $IBLOCK_ID = $arSettings['IBLOCK_ID'];

            $bdIblockAttr = CIBlock::GetList(
                Array(),
                Array(
                    "CODE"=>'ozon'
                ), true
            );
            if($arIblockAttr = $bdIblockAttr->Fetch())
            {
                $iblock_attr_id = $arIblockAttr['ID'];
            }


            CModule::IncludeModule('iblock');
            CModule::IncludeModule('catalog');

            if($arSettings['DEACTIVATE_ELEMENT_YES'] == "Y") $active_fltr = ''; else $active_fltr = "Y";
            $arFilter = Array("IBLOCK_ID" => intval($IBLOCK_ID), "ACTIVE" => $active_fltr, '>ID'=>$id);

            $arCustomFilter = array();
            if($arSettings["CUSTOM_FILTER"]) {
                $filter_custom = new FilterCustomOzon();
                $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arSettings["CUSTOM_FILTER"])), array());
            }
            elseif ($arSettings['FILTER_PROP'] != '' && $arSettings['FILTER_PROP_ID'] != '')
                $arFilter['PROPERTY_' . $arSettings['FILTER_PROP']] = $arSettings['FILTER_PROP_ID'];



            if(!empty($filter)){
                $arFilter = array_merge($arFilter, $filter);
            }
            if(!empty($arCustomFilter)){
                $arFilter[] = $arCustomFilter;
            }
            $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false, Array("nTopCount"=>$arSettings['MAX_COUNT'])/*, $arSelect*/); // ������

            $select_counte = $res->SelectedRowsCount();
            if($select_counte < 1) {
                $new_id = 1;
                if($arSettings['RE_UPLOAD_PRODUCT'] == 'Y'){
                    Option::set(MAXYSS_MODULE_NAME, "RE_UPLOAD_PRODUCT", "N", $lid);
                }
            }


            $count = 0;
            $count_all = 0;
            $count_not_success = 0;
            $count_success_off = 0;
            $count_not_success_off = 0;
            $count_success = 0;
            $arFields = array();
            $arProps = array();
            $arSecAttr = array();

            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                $description = '';
                if($arSettings['DESCRIPTION'] == 'DETAIL_TEXT' ||$arSettings['DESCRIPTION'] == 'PREVIEW_TEXT')
                    $description = $arFields[$arSettings['DESCRIPTION']];
                elseif($arSettings['DESCRIPTION'] != '')
                    $description = (is_array($arProps[$arSettings['DESCRIPTION']]["~VALUE"]))? $arProps[$arSettings['DESCRIPTION']]["~VALUE"]["TEXT"] : $arProps[$arSettings['DESCRIPTION']]["~VALUE"];

                $name_prodact = '';
                if($arSettings['NAME_PRODACT'] == 'NAME')
                    $name_prodact = htmlspecialchars_decode($arFields["NAME"]);
                elseif($arSettings['NAME_PRODACT'] != '')
                    $name_prodact = (is_array($arProps[$arSettings['NAME_PRODACT']]["~VALUE"]))? $arProps[$arSettings['NAME_PRODACT']]["~VALUE"]["TEXT"] : $arProps[$arSettings['NAME_PRODACT']]["~VALUE"];

                if($name_prodact == '') $name_prodact = htmlspecialchars_decode($arFields["NAME"]);
                $ID = $arFields['ID'];
                $new_id = $arFields['ID'];

                $brand = GetMessage('OZON_MAXYSS_NO_BRAND');
                if($arProps[$arSettings['BRAND_PROP']] != ''){
                    if($arProps[$arSettings['BRAND_PROP']]['PROPERTY_TYPE'] == 'L' || ( $arProps[$arSettings['BRAND_PROP']]['PROPERTY_TYPE'] == 'S' &&  empty($arProps[$arSettings['BRAND_PROP']]['USER_TYPE_SETTINGS']))){
                        $brand = ($arProps[$arSettings['BRAND_PROP']]['VALUE'] != '')? $arProps[$arSettings['BRAND_PROP']]['VALUE'] : GetMessage('OZON_MAXYSS_NO_BRAND');
                    }elseif ($arProps[$arSettings['BRAND_PROP']]['PROPERTY_TYPE'] == 'S' &&  !empty($arProps[$arSettings['BRAND_PROP']]['USER_TYPE_SETTINGS']) && isset($arProps[$arSettings['BRAND_PROP']]['USER_TYPE_SETTINGS']['TABLE_NAME']) && $arProps[$arSettings['BRAND_PROP']]['USER_TYPE_SETTINGS']['TABLE_NAME'] !=''){


                        $hlblock = HL\HighloadBlockTable::getRow([
                            'filter' => [
                                '=TABLE_NAME' => $arProps[$arSettings['BRAND_PROP']]['USER_TYPE_SETTINGS']['TABLE_NAME']
                            ],
                        ]);

                        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                        $main_query = new Entity\Query($entity);
                        $main_query->setSelect(array('*'));
                        $main_query->setFilter(array('UF_XML_ID' => $arProps[$arSettings['BRAND_PROP']]['VALUE']));
                        $result = $main_query->exec();
                        $result = new CDBResult($result);

                        if ($row = $result->Fetch()) {
                            $brand = $row['UF_NAME'];
                        }else{
                            $brand = GetMessage('OZON_MAXYSS_NO_BRAND');
                        }

                    }elseif ($arProps[$arSettings['BRAND_PROP']]['PROPERTY_TYPE'] == 'E' &&  $arProps[$arSettings['BRAND_PROP']]['VALUE'] != ''){
                        if($arProps[$arSettings['BRAND_PROP']]['MULTIPLE'] == 'N'){
                            $res_brand = CIBlockElement::GetByID($arProps[$arSettings['BRAND_PROP']]['VALUE']);
                            if($ar_brand = $res_brand->GetNext())
                                $brand = $ar_brand['NAME'];
                        }else{
                            $brand = GetMessage('OZON_MAXYSS_NO_BRAND');
                        }
                    }
                    else{
                        $brand = GetMessage('OZON_MAXYSS_NO_BRAND');
                    }
                }

                $arOzonAttrTovar = array();

                if (strlen($arProps['CAT_OZON']['~VALUE']) > 0) {
                    if(LANG_CHARSET == 'windows-1251')
                        $arOzonAttrTovar = iconv('windows-1251', 'UTF-8//IGNORE', $arProps['CAT_OZON']['~VALUE']);
                    else
                        $arOzonAttrTovar = $arProps['CAT_OZON']['~VALUE'];
                    $arOzonAttrTovar = json_decode($arOzonAttrTovar, true);

                    if(LANG_CHARSET == 'windows-1251') $arOzonAttrTovar = self::deepIconv($arOzonAttrTovar);
                }
                else
                {
                    $arOzonAttrTovar = self::get_section_attr( $arFields['IBLOCK_SECTION_ID'], $arSettings);
                }
                $type = '';
                $category = '';

                $type = $arOzonAttrTovar[8229]['values'][0]['dictionary_value_id'];

                $arOzonAttrTovar = self::getSincAttr($iblock_attr_id, $arOzonAttrTovar, $arFields, $arProps);

                $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnItemPrepare", array(&$arOzonAttrTovar, $arFields, $arProps));
                $event->send();

                if(is_array($arOzonAttrTovar)) {
                $category = $arOzonAttrTovar['category']['id'];

                $arOzonAttrTovar[10289]['id'] = 10289;
                $arOzonAttrTovar[10289]['values'][0]['value'] = $ID;
                $arOzonAttrTovar[8292]['id'] = 8292;
                $arOzonAttrTovar[8292]['values'][0]['value'] = $ID;
                }
                $ar_tovar = CCatalogProduct::GetByID($ID); // item as product
                $imgPath = (CMain::IsHTTPS()) ? "https://" : "http://";
                $imgPath .= $arSettings['SERVER_NAME'];

                // we will collect general information for simple products and products with TP

                $img = array();
                if ($arFields[$arSettings['BASE_PICTURE']] > 0) {
                    $img[] = $imgPath . CFile::GetPath($arFields[$arSettings['BASE_PICTURE']]);
                }
                if (is_array($arProps[$arSettings['MORE_PICTURE']]['VALUE'])) {
                    foreach ($arProps[$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                        $img[] = $imgPath . CFile::GetPath($photo);
                    }
                }

                $arPrice = array();
                $lid = $arSettings['SITE'];

                $arFieldsOff = array();
                $arPropsOff = array();


                if ($ar_tovar['TYPE'] == 3)
                {
                    $arInfo = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

                    if (is_array($arInfo)) {
                        $arSelect = Array("ID", "IBLOCK_ID", "NAME", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_*");

                        $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfo['IBLOCK_ID'], "ACTIVE" => $active_fltr, 'PROPERTY_' . $arInfo['SKU_PROPERTY_ID'] => $ID), false, false, $arSelect);

                        while ($arOffer = $rsOffers->GetNextElement()) {

                            $error_user = array();
                            $arSku = array();
                            $arAttr = array();

                            $arFieldsOff = $arOffer->GetFields();
                            $arPropsOff = $arOffer->GetProperties();

                            $description_off = '';
                            if($arSettings['DESCRIPTION'] == 'DETAIL_TEXT' ||$arSettings['DESCRIPTION'] == 'PREVIEW_TEXT')
                                $description_off = $arFieldsOff[$arSettings['DESCRIPTION']];
                            elseif($arSettings['DESCRIPTION'] != '')
                                $description_off = (is_array($arPropsOff[$arSettings['DESCRIPTION']]["~VALUE"]))? $arPropsOff[$arSettings['DESCRIPTION']]["~VALUE"]["TEXT"] : $arPropsOff[$arSettings['DESCRIPTION']]["~VALUE"];

                            $name_prodact_off = '';
                            if($arSettings['NAME_PRODACT'] == 'NAME' )
                                $name_prodact_off = htmlspecialchars_decode($arFieldsOff['NAME']);
                            elseif($arSettings['NAME_PRODACT'] != '')
                                $name_prodact_off = (is_array($arPropsOff[$arSettings['NAME_PRODACT']]["~VALUE"]))? $arPropsOff[$arSettings['NAME_PRODACT']]["~VALUE"]["TEXT"] : $arPropsOff[$arSettings['NAME_PRODACT']]["~VALUE"];



                            $arOzonAttrTP = array();
                            $type = '';
                            $category = '';
                            if (strlen($arPropsOff['CAT_OZON']['~VALUE']) > 0) {

                                if(LANG_CHARSET == 'windows-1251')
                                    $arOzonAttrTP = iconv('windows-1251', 'UTF-8//IGNORE', $arPropsOff['CAT_OZON']['~VALUE']);
                                else
                                    $arOzonAttrTP = $arPropsOff['CAT_OZON']['~VALUE'];

                                $arOzonAttrTP = json_decode($arOzonAttrTP, true);

                                if(LANG_CHARSET == 'windows-1251') $arOzonAttrTP = self::deepIconv($arOzonAttrTP);

                                $arOzonAttr = $arOzonAttrTP;

                                // �������� -> ��������
                                $arOzonAttr = self::getSincAttr($iblock_attr_id, $arOzonAttr, $arFieldsOff, $arPropsOff);
                                // �������� -> ��������

                                // ��������� �� ������ �� ���� ��� � ��
                                if(is_array($arOzonAttrTovar) && is_array($arOzonAttr)) {
                                foreach ($arOzonAttrTovar as $attr_id => $attr_value){
                                    if(!isset($arOzonAttr[$attr_id])){
                                        $arOzonAttr[$attr_id] = $attr_value;
                                    }
                                }
                                }
                                // ��������� �� ������ �� ���� ��� � ��

                                if(is_array($arOzonAttr))
                                $type = $arOzonAttr[8229]['values'][0]['dictionary_value_id'];

                                $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnOfferPrepare", array(&$arOzonAttr, $arFieldsOff, $arPropsOff));
                                $event->send();

                                if(is_array($arOzonAttr))
                                $category = $arOzonAttr['category']['id'];

                            }else{
                                $arOzonAttr = $arOzonAttrTovar;

                                // �������� -> ��������
                                $arOzonAttr = self::getSincAttr($iblock_attr_id, $arOzonAttr, $arFieldsOff, $arPropsOff);
                                // �������� -> ��������

                                $type = $arOzonAttr[8229]['values'][0]['dictionary_value_id'];

                                $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnOfferPrepare", array(&$arOzonAttr, $arFieldsOff, $arPropsOff));
                                $event->send();

                                $category = $arOzonAttr['category']['id'];
                            }


                            if(strlen($category) > 0 || $arOptions[$lid]['NO_UPLOAD_PRODUCT'] == "Y") {
                                // combining TP into one card

                                $arOzonAttr[10289]['id'] = 10289;
                                $arOzonAttr[10289]['values'][0]['value'] = $ID;
                                $arOzonAttr[8292]['id'] = 8292;
                                $arOzonAttr[8292]['values'][0]['value'] = $ID;

                                if(VERSION_OZON_2) {
                                    // brand
//                                    if (isset($arOzonAttr[31])) {
//                                        $arOzonAttr[31]['values'] = array(array('value' => $brand));
////                                        $arOzonAttr[31]['values'] = array(array('dictionary_value_id' => '4705')); // no brand
//                                        unset($arOzonAttr[31][0]['dictionary_value_id']);
//                                    } elseif (isset($arOzonAttr[85])) {
//                                        $arOzonAttr[85]['values'] = array(array('value' => $brand));
////                                        $arOzonAttr[85]['values'] = array(array('dictionary_value_id' => '4705')); // no brand
//                                        unset($arOzonAttr[85][0]['dictionary_value_id']);
//                                    }
                                    if(!isset($arOzonAttr[4191]['values'][0]['value']) || $arOzonAttr[4191]['values'][0]['value'] == '') {
                                        $arOzonAttr[4191]['id'] = 4191;
                                        $arOzonAttr[4191]['values'][0]['value'] = (strlen($description_off) > 0) ? $description_off : $description;
                                    }
                                }

                                $arAttr = $arOzonAttr;
                                unset($arAttr['category']);

                                foreach ($arAttr as $key => &$atr){
                                    if(empty($atr['values'])) {
                                        unset($arAttr[$key]);
                                    }
                                    else
                                    {
                                        foreach ($atr['values'] as &$vals) {
                                            if ($vals['dictionary_value_id']) {
                                                $vals['dictionary_value_id'] = intval($vals['dictionary_value_id']);
                                            }
                                        }
                                    }
                                }
                                $arAttr = array_values($arAttr);

                                $img_off = array();
                                if ($arFieldsOff[$arSettings['BASE_PICTURE']]) {
                                    $img_off[] = $imgPath . CFile::GetPath($arFieldsOff[$arSettings['BASE_PICTURE']]);
                                }
                                if (is_array($arPropsOff[$arSettings['MORE_PICTURE']]['VALUE'])) {
                                    foreach ($arPropsOff[$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                                        $img_off[] = $imgPath . CFile::GetPath($photo);
                                    }
                                }

                                // quantity

                                $ar_off = CCatalogProduct::GetByID($arFieldsOff['ID']);
                                $article_tovar = '';
                                $article_tovar = (strlen($arSettings['ARTICLE']) > 0) ? $arPropsOff[$arSettings['ARTICLE']]['VALUE'] : $arFieldsOff['ID'];

                                $key_ozon = $article_tovar; // key for Ozon

                                $arSku['offer_id'] = $key_ozon;
                                if(strlen($arPropsOff[$arSettings['BARCODE']]['VALUE'])>0)
                                    $arSku['barcode'] = $arPropsOff[$arSettings['BARCODE']]['VALUE'];
                                $arSku['description'] = (strlen($description_off) > 0) ? $description_off : $description;
                                $arSku['category_id'] = intval($category);
                                $arSku['name'] = (strlen($name_prodact_off) > 0) ? $name_prodact_off : $name_prodact;
                                $arSku['price'] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFieldsOff['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);;
                                $arSku['old_price'] = self::get_price($arSettings['PRICE_TYPE_OLD'], $arSettings['PRICE_PROP_OLD'], $arSettings['PRICE_TYPE_OLD_PROP'], $arSettings['PRICE_TYPE_OLD_NO_DISCOUNT'], $arFieldsOff['ID'], $lid, $arSettings["PRICE_TYPE_OLD_FORMULA"], $arSettings["PRICE_TYPE_OLD_FORMULA_ACTION"]);
                                $arSku['auto_action_enabled'] = 'UNKNOWN';
                                $arSku['min_price'] = self::get_price($arSettings['PRICE_TYPE_MIN'], $arSettings['PRICE_PROP_MIN'], $arSettings['PRICE_TYPE_MIN_PROP'], $arSettings['PRICE_TYPE_MIN_NO_DISCOUNT'], $arFieldsOff['ID'], $lid, $arSettings["PRICE_TYPE_MIN_FORMULA"], $arSettings["PRICE_TYPE_MIN_FORMULA_ACTION"]);
                                $arSku['vat'] = self::vat($ar_off, $arSettings);
                                $arSku['height'] = intval($ar_off['HEIGHT']);
                                $arSku['depth'] = intval($ar_off['LENGTH']);
                                $arSku['width'] = intval($ar_off['WIDTH']);
                                $arSku['dimension_unit'] = 'mm';
                                $arSku['weight'] = intval($ar_off['WEIGHT']);
                                $arSku['weight_unit'] = 'g';
                                $arSku['images'] = (count($img_off) > 0) ? $img_off : $img;
                                $arSku['primary_image'] = (count($img_off) > 0) ? $img_off[0] : $img[0];
                                $arSku['attributes'] = $arAttr;
                                $arSku['stock'] = self::arStock($ar_off, $arFieldsOff['ID'], $arSettings);

                                $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "AfterItemPrepare", array(&$arSku, $arFieldsOff, $arPropsOff, $arSettings));
                                $event->send();
                                // ���������

                                if($arSku['offer_id'] != '') {
                                    $arItemOzon[$arSku['offer_id']] = $arSku;
                                    $arItemID[] = $arSku['offer_id'];
                                }else{
                                    $error_log = CHelpMaxyss::arr_from_file($filename);
                                    $error_log[$arFieldsOff['ID'].' - '.$arSku['name']][0] = GetMessage('OZON_MAXYSS_ERROR_USER_ARTICLE');
                                    CHelpMaxyss::arr_to_file($filename, $error_log);
                                }

                            }
                            else
                            {
                                $count_not_success_off++;
                                $arErrors['ozon'][$arFieldsOff['ID']] = 'ID ' . $arFieldsOff['ID'] . GetMessage('OZON_MAXYSS_ERROR_CAT_OZON');

                                $id_error = $arFieldsOff['ID'];
                                $arErrors['ozon'][$id_error] = $id_error.GetMessage("OZON_MAXYSS_ERROR_CAT_OZON_TP");

                            }

                        }
                    }

                }
                elseif ($ar_tovar['TYPE'] == 1 && ( strlen($category) > 0 || $arOptions[$lid]['NO_UPLOAD_PRODUCT'] == "Y")) // simple product
                {
                    $arSku = array();
                    $error_user = array();
                    $article_tovar = '';
                    $article_tovar = (strlen($arSettings['ARTICLE']) > 0) ? $arProps[$arSettings['ARTICLE']]['VALUE'] : $arFields['ID'];
                    $key_ozon = $article_tovar; // key for Ozon
                    $arOzonAttr = $arOzonAttrTovar;

                    // brand
//                    if (isset($arOzonAttr[31])) {
//                        $arOzonAttr[31]['values'] = array(array('value' => $brand));
//                        unset($arOzonAttr[31][0]['dictionary_value_id']);
//                    } elseif (isset($arOzonAttr[85])) {
//                        $arOzonAttr[85]['values'] = array(array('value' => $brand));
//                        unset($arOzonAttr[85][0]['dictionary_value_id']);
//                    }
                    if(!isset($arOzonAttr[4191]['values'][0]['value']) || $arOzonAttr[4191]['values'][0]['value'] == '') {
                        $arOzonAttr[4191]['id'] = 4191;
                        $arOzonAttr[4191]['values'][0]['value'] = $description;
                    }

                    $arAttr = $arOzonAttr;
                    unset($arAttr['category']);

                    foreach ($arAttr as $key => &$atr){
                        if(empty($atr['values'])) {
                            unset($arAttr[$key]);
                        }
                        else
                        {
                            foreach ($atr['values'] as &$vals) {
                                if ($vals['dictionary_value_id']) {
                                    $vals['dictionary_value_id'] = intval($vals['dictionary_value_id']);
                                }
                            }
                        }
                    }
                    $arAttr = array_values($arAttr);

                    $arSku['offer_id'] = $key_ozon;
                    if(strlen($arProps[$arSettings['BARCODE']]['VALUE'])>0)
                        $arSku['barcode'] = $arProps[$arSettings['BARCODE']]['VALUE'];
                    $arSku['description'] = $description;;
                    $arSku['category_id'] = intval($category);
                    $arSku['name'] = $name_prodact;
                    $arSku['price'] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);;
                    $arSku['old_price'] = self::get_price($arSettings['PRICE_TYPE_OLD'], $arSettings['PRICE_PROP_OLD'], $arSettings['PRICE_TYPE_OLD_PROP'], $arSettings['PRICE_TYPE_OLD_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_OLD_FORMULA"], $arSettings["PRICE_TYPE_OLD_FORMULA_ACTION"]);
                    $arSku['auto_action_enabled'] = 'UNKNOWN';
                    $arSku['min_price'] = self::get_price($arSettings['PRICE_TYPE_MIN'], $arSettings['PRICE_PROP_MIN'], $arSettings['PRICE_TYPE_MIN_PROP'], $arSettings['PRICE_TYPE_MIN_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_MIN_FORMULA"], $arSettings["PRICE_TYPE_MIN_FORMULA_ACTION"]);

                    $arSku['vat'] = self::vat($ar_tovar, $arSettings);
//                    $arSku['vendor'] = $brand;
                    $arSku['vendor_code'] = $article_tovar;
                    $arSku['height'] = intval($ar_tovar['HEIGHT']);
                    $arSku['depth'] = intval($ar_tovar['LENGTH']);
                    $arSku['width'] = intval($ar_tovar['WIDTH']);
                    $arSku['dimension_unit'] = 'mm';
                    $arSku['weight'] = intval($ar_tovar['WEIGHT']);
                    $arSku['weight_unit'] = 'g';
                    $arSku['images'] = $img;
                    $arSku['primary_image'] = $img[0];
                    $arSku['attributes'] = $arAttr;
                    $arSku['stock'] = self::arStock($ar_tovar, $arFields['ID'], $arSettings);

                    $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "AfterItemPrepare", array(&$arSku, $arFields, $arProps, $arSettings));
                    $event->send();

                    if($arSku['offer_id'] != '') {
                        $arItemOzon[$arSku['offer_id']] = $arSku;
                        $arItemID[] = $arSku['offer_id'];
                    }else{
                        $error_log = CHelpMaxyss::arr_from_file($filename);
                        $error_log[$arFields['ID'].' - '.$arSku['name']][0] = GetMessage('OZON_MAXYSS_ERROR_USER_ARTICLE');
                        CHelpMaxyss::arr_to_file($filename, $error_log);
                    }

                }
                elseif ($ar_tovar['TYPE'] == 2 && ( strlen($category) > 0 || $arOptions[$lid]['NO_UPLOAD_PRODUCT'] == "Y"))
                {
                    $arSku = array();
$error_user = array();
                    $article_tovar = '';
                    $article_tovar = (strlen($arSettings['ARTICLE']) > 0) ? $arProps[$arSettings['ARTICLE']]['VALUE'] : $arFields['ID'];
                    $key_ozon = $article_tovar; // key for Ozon
                    $arOzonAttr = $arOzonAttrTovar;

                    if(VERSION_OZON_2) {
                        // brand
                        if (isset($arOzonAttr[31])) {
                            $arOzonAttr[31]['values'] = array(array('value' => $brand));
                            unset($arOzonAttr[31][0]['dictionary_value_id']);
                        } elseif (isset($arOzonAttr[85])) {
                            $arOzonAttr[85]['values'] = array(array('value' => $brand));
                            unset($arOzonAttr[85][0]['dictionary_value_id']);
                        }
                        if(!isset($arOzonAttr[4191]['values'][0]['value']) || $arOzonAttr[4191]['values'][0]['value'] == '') {
                            $arOzonAttr[4191]['id'] = 4191;
                            $arOzonAttr[4191]['values'][0]['value'] = $description;
                        }
                    }

                    $arAttr = $arOzonAttr;
                    unset($arAttr['category']);

                    foreach ($arAttr as $key => &$atr){
                        if(empty($atr['values'])) {
                            unset($arAttr[$key]);
                        }
                        else
                        {
                            foreach ($atr['values'] as &$vals) {
                                if ($vals['dictionary_value_id']) {
                                    $vals['dictionary_value_id'] = intval($vals['dictionary_value_id']);
                                }
                            }
                        }
                    }
                    $arAttr = array_values($arAttr);

                    $arSku['offer_id'] = $key_ozon;
                    if(strlen($arProps[$arSettings['BARCODE']]['VALUE'])>0)
                        $arSku['barcode'] = $arProps[$arSettings['BARCODE']]['VALUE'];
                    $arSku['description'] = $description;;
                    $arSku['category_id'] = intval($category);
                    $arSku['name'] = $name_prodact;
                    $arSku['price'] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);;
                    $arSku['old_price'] = self::get_price($arSettings['PRICE_TYPE_OLD'], $arSettings['PRICE_PROP_OLD'], $arSettings['PRICE_TYPE_OLD_PROP'], $arSettings['PRICE_TYPE_OLD_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_OLD_FORMULA"], $arSettings["PRICE_TYPE_OLD_FORMULA_ACTION"]);

                    $arSku['auto_action_enabled'] = 'UNKNOWN';
                    $arSku['min_price'] = self::get_price($arSettings['PRICE_TYPE_MIN'], $arSettings['PRICE_PROP_MIN'], $arSettings['PRICE_TYPE_MIN_PROP'], $arSettings['PRICE_TYPE_MIN_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_MIN_FORMULA"], $arSettings["PRICE_TYPE_MIN_FORMULA_ACTION"]);

                    $arSku['vat'] = self::vat($ar_tovar, $arSettings);
                    $arSku['vendor'] = $brand;
                    $arSku['vendor_code'] = $article_tovar;
                    $arSku['height'] = intval($ar_tovar['HEIGHT']);
                    $arSku['depth'] = intval($ar_tovar['LENGTH']);
                    $arSku['width'] = intval($ar_tovar['WIDTH']);
                    $arSku['dimension_unit'] = 'mm';
                    $arSku['weight'] = intval($ar_tovar['WEIGHT']);
                    $arSku['weight_unit'] = 'g';
                    $arSku['images'] = $img;
                    $arSku['primary_image'] = $img[0];
                    $arSku['attributes'] = $arAttr;
                    $arSku['stock'] = self::arStock($ar_tovar, $arFields['ID'], $arSettings);

                    $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "AfterItemPrepare", array(&$arSku, $arFields, $arProps, $arSettings));
                    $event->send();

                    if($arSku['offer_id'] != '') {
                        $arItemOzon[$arSku['offer_id']] = $arSku;
                        $arItemID[] = $arSku['offer_id'];
                    }else{
                        $error_log = CHelpMaxyss::arr_from_file($filename);
                        $error_log[$arFields['ID'].' - '.$arSku['name']][0] = GetMessage('OZON_MAXYSS_ERROR_USER_ARTICLE');
                        CHelpMaxyss::arr_to_file($filename, $error_log);
                    }
                }
                elseif (strlen($category) == 0)
                {
                    $count_not_success++;
                    $id_error = ($arFieldsOff['ID'])? $arFieldsOff['ID'] : $arFields['ID'];
                    $arErrors['ozon'][$id_error] = $id_error.GetMessage("OZON_MAXYSS_ERROR_CAT_OZON");
                }
                else
                {
                    $count_not_success++;
                    $id_error = ($arFieldsOff['ID'])? $arFieldsOff['ID'] : $arFields['ID'];
                    $arErrors['ozon'][$id_error] = $id_error.GetMessage("OZON_MAXYSS_ERROR_NOT_PRODUCT");
                }

                $count++;
                $count_all++;
                unset($img, $arFields, $arProps, $arPropsOff, $arFieldsOff, $brand, $arSectionPath, $arOzonAttr, $arOzonAttrTovar, $arOzonAttrTP);
            }
            if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'OzonUploadProduct', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($arItemOzon)));
            }
            if (!empty($arItemID) && !empty($arItemOzon)) {
                if(isset($filter["ID"])) {
                    $err = '';

                    foreach ($arItemOzon as &$val) {
                        unset($val['stock']);
                    }
                    $arItemsIdChunk_import = array_chunk($arItemOzon, 100);
                    foreach ($arItemsIdChunk_import as $items_import) {
                        $err = $err . self::import(array_values($items_import), $ClientId, $ApiKey, OZON_BASE_URL, $filename, $lid);
                    }
                }
                else
                {
                    self::get_products($arItemID, $arItemOzon, $ClientId, $ApiKey, OZON_BASE_URL, $filename, $lid);
                }
            }
        }else{
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log.txt", print_r(GetMessage('OZON_MAXYSS_ERROR_SETTINGS'), true) . PHP_EOL, FILE_APPEND);

        }

        if(!empty($arErrors))
        {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log.txt", print_r('ERRORS', true) . PHP_EOL, FILE_APPEND);
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log.txt", print_r($arErrors, true) . PHP_EOL, FILE_APPEND);
        }


        if(isset($filter["ID"])) {
            return array('error'=> $err, 'success'=>true);
        }
        else
        {
            if (!empty($filter)) return "CMaxyssOzonAgent::OzonUploadProduct('" . $lid . "'," . $new_id . ", " . var_export($filter, true) . ");";
            else return "CMaxyssOzonAgent::OzonUploadProduct('" . $lid . "'," . $new_id . ");";
        }
    }

    public static function OzonFid($lid=''){

        $arSettings = array();
        $arOptions = CMaxyssOzon::getOptions($lid);
        if($lid !='') {
            $arSettings = $arOptions[$lid];
            $arSettings['SITE'] = $lid;
        }
        else {
            $arSettings = $arOptions[key($arOptions)];
            $arSettings['SITE'] = $lid = key($arOptions);
        }
        if( strpos($arSettings['SKLAD_ID'], '}') ){
            $arSettings['SKLAD_ID'] = unserialize($arSettings['SKLAD_ID']);
        }

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        $SETUP_FILE_NAME = "/bitrix/catalog_export/" . MAXYSS_MODULE_NAME ."/". $lid. "_fid.php";

        if($arSettings['IBLOCK_TYPE'] && $arSettings['IBLOCK_ID'] && $arSettings['PRICE_TYPE'] && $arSettings['SERVER_NAME'] && $arSettings['SITE']) {
            $ClientId = $arSettings["OZON_ID"];
            $ApiKey = $arSettings["OZON_API_KEY"];

            $arVarehouses = unserialize($arSettings['SKLAD_ID_V3']);
            $warehouses = array();
            $warehouses = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, "{}", "/v1/warehouse/list");
            if(!$warehouses['error'] && !empty($warehouses)){
                foreach ($warehouses as $warehouse)
                    $arWareNames[$warehouse["warehouse_id"]] = $warehouse["name"];
            }

            $IBLOCK_ID = $arSettings['IBLOCK_ID'];

            CModule::IncludeModule('iblock');
            CModule::IncludeModule('catalog');

            if($arSettings['DEACTIVATE_ELEMENT_YES'] == "Y") $active_fltr = ''; else $active_fltr = "Y";
            $arFilter = Array("IBLOCK_ID" => intval($IBLOCK_ID), "ACTIVE" => $active_fltr);

            $arCustomFilter = array();
            if($arSettings["CUSTOM_FILTER"]) {
                $filter_custom = new FilterCustomOzon();
                $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arSettings["CUSTOM_FILTER"])), array());
            }
            elseif ($arSettings['FILTER_PROP'] != '' && $arSettings['FILTER_PROP_ID'] != '')
                $arFilter['PROPERTY_' . $arSettings['FILTER_PROP']] = $arSettings['FILTER_PROP_ID'];

            if(!empty($arCustomFilter)){
                $arFilter[] = $arCustomFilter;
            }
            $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilter, false);
            $arFields = array();
            $arProps = array();
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                $ID = $arFields['ID'];
                $ar_tovar = CCatalogProduct::GetByID($ID); // item as product

                $imgPath = (CMain::IsHTTPS()) ? "https://" : "http://";
                $imgPath .= $arSettings['SERVER_NAME'];

                $arPrice = array();
                $lid = $arSettings['SITE'];


                $arFieldsOff = array();
                $arPropsOff = array();

                if ($ar_tovar['TYPE'] == 3)
                {
                    $arInfo = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

                    if (is_array($arInfo)) {
                        $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfo['IBLOCK_ID'], "ACTIVE" => $active_fltr, 'PROPERTY_' . $arInfo['SKU_PROPERTY_ID'] => $ID), false, false);

                        while ($arOffer = $rsOffers->GetNextElement()) {
                            $arSku = array();
                            $arFieldsOff = $arOffer->GetFields();
                            $arPropsOff = $arOffer->GetProperties();

                            $ar_off = CCatalogProduct::GetByID($arFieldsOff['ID']);
                            $article_tovar = '';
                            $article_tovar = (strlen($arSettings['ARTICLE']) > 0) ? $arPropsOff[$arSettings['ARTICLE']]['VALUE'] : $arFieldsOff['ID'];
                            $key_ozon = $article_tovar; // key for Ozon

                            $arSku['offer_id'] = $key_ozon;
                            $arSku['weight'] = $ar_off['WEIGHT'];
                            $arSku['price'] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFieldsOff['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);;
                            $arSku['old_price'] = self::get_price($arSettings['PRICE_TYPE_OLD'], $arSettings['PRICE_PROP_OLD'], $arSettings['PRICE_TYPE_OLD_PROP'], $arSettings['PRICE_TYPE_OLD_NO_DISCOUNT'], $arFieldsOff['ID'], $lid, $arSettings["PRICE_TYPE_OLD_FORMULA"], $arSettings["PRICE_TYPE_OLD_FORMULA_ACTION"]);
                            $arSku['min_price'] = self::get_price($arSettings['PRICE_TYPE_MIN'], $arSettings['PRICE_PROP_MIN'], $arSettings['PRICE_TYPE_MIN_PROP'], $arSettings['PRICE_TYPE_MIN_NO_DISCOUNT'], $arFieldsOff['ID'], $lid, $arSettings["PRICE_TYPE_MIN_FORMULA"], $arSettings["PRICE_TYPE_MIN_FORMULA_ACTION"]);
                            $arSku['stock'] = self::arStock($ar_off, $arFieldsOff['ID'], $arSettings);

                            $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "AfterItemPrepare", array(&$arSku, $arFieldsOff, $arPropsOff, $arSettings));
                            $event->send();

                            if($arSku['offer_id'] != '') {
                                $arItemOzon[$arSku['offer_id']] = $arSku;
                                $arItemID[] = $arSku['offer_id'];
                            }
                        }
                    }

                }
                elseif ($ar_tovar['TYPE'] == 1 ||$ar_tovar['TYPE'] == 2) // simple product or complect
                {
                    $article_tovar = '';
                    $article_tovar = (strlen($arSettings['ARTICLE']) > 0) ? $arProps[$arSettings['ARTICLE']]['VALUE'] : $arFields['ID'];
                    $key_ozon = $article_tovar; // key for Ozon

                    $arSku['offer_id'] = $key_ozon;
                    $arSku['weight'] = $ar_tovar['WEIGHT'];
                    $arSku['price'] = self::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);;
                    $arSku['old_price'] = self::get_price($arSettings['PRICE_TYPE_OLD'], $arSettings['PRICE_PROP_OLD'], $arSettings['PRICE_TYPE_OLD_PROP'], $arSettings['PRICE_TYPE_OLD_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_OLD_FORMULA"], $arSettings["PRICE_TYPE_OLD_FORMULA_ACTION"]);
                    $arSku['min_price'] = self::get_price($arSettings['PRICE_TYPE_MIN'], $arSettings['PRICE_PROP_MIN'], $arSettings['PRICE_TYPE_MIN_PROP'], $arSettings['PRICE_TYPE_MIN_NO_DISCOUNT'], $arFields['ID'], $lid, $arSettings["PRICE_TYPE_MIN_FORMULA"], $arSettings["PRICE_TYPE_MIN_FORMULA_ACTION"]);
                    $arSku['vendor_code'] = $article_tovar;
                    $arSku['stock'] = self::arStock($ar_tovar, $arFields['ID'], $arSettings);

                    $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "AfterItemPrepare", array(&$arSku, $arFields, $arProps, $arSettings));
                    $event->send();

                    if($arSku['offer_id'] != '') {
                        $arItemOzon[$arSku['offer_id']] = $arSku;
                        $arItemID[] = $arSku['offer_id'];
                    }

                }
                unset($arFields, $arProps, $arPropsOff, $arFieldsOff);
            }

            if (!empty($arItemID) && !empty($arItemOzon)) {
                $arDeactivateWarehouses = unserialize($arSettings["DEACTIVATE"]);
                foreach ($arItemID as $key => $id) {
                    if ($id == '')
                        unset($arItemID[$key]);
                }

                $arUpdateStock = array();

                $flags = array();

                $arItemsIdChunk = array_chunk($arItemID, 1000);
                foreach ($arItemsIdChunk as $items) {

                    $data_string = array(
                        "filter" => array(
                            "offer_id" => $items
                        ),
                        "visibility" => "ALL",
                        "last_id" => "",
                        "limit" => 1000
                    );

                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);


                    $arProducts = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/product/list");
                    if (!isset($arProducts['error'])) {

                        $flags['f_true'] = true;

                        if (!empty($arProducts['items'])) {
                            // select products to update the price and quantity
                            foreach ($arProducts['items'] as $prod) {
                                if (is_array($arItemOzon[$prod['offer_id']]['stock'])) {
                                    foreach ($arItemOzon[$prod['offer_id']]['stock'] as $warehouse => $stock) {

                                        $stock_res = self::stock_limits($prod['offer_id'], $stock, $warehouse, $arItemOzon, $arSettings);

                                        if ($arDeactivateWarehouses[$warehouse] != 'Y') {
                                            $arUpdateStock[$prod['offer_id']]['outlets'][] = array(
                                                "stock" => ($stock_res > 0) ? intval($stock_res) : 0,
                                                "warehouse" => $arWareNames[$warehouse]
                                            );
                                        }
                                    }
                                }

                                if ($arItemOzon[$prod['offer_id']]['price'] > 0) {
                                    $arPrice = array(
                                        "price" => $arItemOzon[$prod['offer_id']]['price'],
                                        "min_price" => $arItemOzon[$prod['offer_id']]['min_price'],
                                    );

                                    if ($arItemOzon[$prod['offer_id']]['old_price'] > 0) {
                                        if (
                                            (round($arItemOzon[$prod['offer_id']]['price'] / $arItemOzon[$prod['offer_id']]['old_price'], 2)) <= 0.95 &&
                                            ($arItemOzon[$prod['offer_id']]['old_price'] - $arItemOzon[$prod['offer_id']]['price']) >= 10 &&
                                            ($arItemOzon[$prod['offer_id']]['price'] / $arItemOzon[$prod['offer_id']]['old_price']) >= 0.1 &&
                                            ($arItemOzon[$prod['offer_id']]['price'] / $arItemOzon[$prod['offer_id']]['old_price']) != 1 // ���������!

                                        ) {
                                            $arPrice["old_price"] = strval($arItemOzon[$prod['offer_id']]['old_price']);
                                        } else {
                                            $arPrice["old_price"] = '0';
                                        }
                                    } else {
                                        $arPrice["old_price"] = '0';
                                    }
                                    $arUpdateStock[$prod['offer_id']]['price'] = $arPrice; // array for updating prices
                                }
                            }
                        }
                    } else {
                        $flags['f_false'] = true;
                        $ERROR = 'Did not receive goods from Ozone';
                        if (\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON", "N") == "Y") {
                            $eventLog = new \CEventLog;
                            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'get_products_fid', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => serialize($arProducts)));
                        }
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'get_products_fid', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $ClientId, "DESCRIPTION" => $lid . ' ' . $ERROR));
                    }
                }
                if (/*!empty($arUpdateStock) && */!isset($flags['f_false']) && $flags['f_true']) {
                    CheckDirPath($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME);
                    $arRunErrors = array();
                    if (!$fp = @fopen($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, "wb"))
                    {
                        $arRunErrors[] = str_replace('#FILE#', $_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
                    }
                    if (empty($arRunErrors)) {
                        fwrite($fp, '<?header("Content-Type: text/xml; charset='.LANG_CHARSET.'");'."\n");
                        fwrite($fp, 'echo "<"."?xml version=\"1.0\" encoding=\"'.LANG_CHARSET.'\"?".">"?>');
                        fwrite($fp, '<yml_catalog date="' . date("Y-m-d H:i") . '">' . "\n");
                        fwrite($fp, '<shop>' . "\n");
                        fwrite($fp, '<offers>' . "\n");

                        $xml_ozon = '';
                        if(!empty($arUpdateStock)) {
                            foreach ($arUpdateStock as $key_offer_id => $iozon) {
                                $xml_ozon .= '<offer id="' . $key_offer_id . '">' . "\n";

                                if( $arSettings['NO_UPLOAD_PRICE'] != "Y")
                                    $xml_ozon .= '<price>' . $iozon['price']['price'] . '</price>' . "\n" . '<oldprice>' . $iozon['price']['old_price'] . '</oldprice>' . "\n" . '<min_price>' . $iozon['price']['min_price'] . '</min_price>' . "\n" ;

                                $xml_ozon .= '<outlets>' . "\n";
                                if (isset($iozon['outlets']) && is_array($iozon['outlets'])) {
                                    foreach ($iozon['outlets'] as $outlet) {
                                        $xml_ozon .= '<outlet instock="' . $outlet['stock'] . '" warehouse_name="' . $outlet['warehouse'] . '"></outlet>' . "\n";
                                    }
                                }
                                $xml_ozon .= '</outlets>' . "\n" . '</offer>' . "\n";
                            }
                        }
                        fwrite($fp, $xml_ozon);
                        fwrite($fp, "</offers>\n");
                        fwrite($fp, "</shop>\n");
                        fwrite($fp, "</yml_catalog>\n");
                        fclose($fp);
                    }
                    else
                    {
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'OzonFid', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $lid, "DESCRIPTION" => serialize($arRunErrors)));
                    }
                }
            }
            else
            {
                CheckDirPath($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME);
                $arRunErrors = array();
                if (!$fp = @fopen($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, "wb"))
                {
                    $arRunErrors[] = str_replace('#FILE#', $_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
                }
                if (empty($arRunErrors)) {
                    fwrite($fp, '<?header("Content-Type: text/xml; charset=' . LANG_CHARSET . '");' . "\n");
                    fwrite($fp, 'echo "<"."?xml version=\"1.0\" encoding=\"' . LANG_CHARSET . '\"?".">"?>');
                    fwrite($fp, '<yml_catalog date="' . date("Y-m-d H:i") . '">' . "\n");
                    fwrite($fp, '<shop>' . "\n");
                    fwrite($fp, '<offers>' . "\n");
                    $xml_ozon = '';
                    fwrite($fp, $xml_ozon);
                    fwrite($fp, "</offers>\n");
                    fwrite($fp, "</shop>\n");
                    fwrite($fp, "</yml_catalog>\n");
                    fclose($fp);
                }
            }
        }else{
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'OzonFid', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $lid, "DESCRIPTION" => GetMessage('OZON_MAXYSS_ERROR_SETTINGS')));
        }

        return "CMaxyssOzonAgent::OzonFid('" . $lid . "');";

    }

    public static function getProduct($products, $lid=''){

        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnGetProduct", array(&$products));
        $event->send();

        $prop_flag = '';
        $arOptions = CMaxyssOzon::getOptions($lid, array('IBLOCK_ID', 'ARTICLE'));
        if(strlen($arOptions[$lid]["ARTICLE"]) > 0) $prop_flag = 'PROPERTY_';
        $result = array();

        $iblock_info = CCatalog::GetByIDExt($arOptions[$lid]["IBLOCK_ID"]);

        foreach ($products as $prod){

            if($prod['offer_id'] !='') {
                if ($prop_flag != '')
                    $arFilterProd = array($prop_flag . $arOptions[$lid]["ARTICLE"] => $prod['offer_id']);
                else
                    $arFilterProd = array("ID" => $prod['offer_id']);


                $arFilterProd["IBLOCK_ID"] = $arOptions[$lid]["IBLOCK_ID"];

                $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "IBLOCK_ID", 'CATALOG_XML_ID', 'AVAILABLE', 'WEIGHT', 'LENGTH', 'WIDTH', 'HEIGHT', 'VAT_ID', 'VAT_INCLUDED');
                $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);
                if ($ob = $res->GetNextElement()) {
                    $arFields = $ob->GetFields();

                    $result[$prod['offer_id']] = $arFields;
                    $result[$prod['offer_id']]["quantity"] = $prod["quantity"];
                    $result[$prod['offer_id']]["price"] = $prod["price"];
                    $result[$prod['offer_id']]["sku"] = $prod["sku"];

                }elseif($iblock_info["OFFERS_IBLOCK_ID"]){
                    $arFilterProd["IBLOCK_ID"] = $iblock_info["OFFERS_IBLOCK_ID"];
                    $res_off = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);
                    if ($ob_off = $res_off->GetNextElement()) {
                        $arFields_off = $ob_off->GetFields();

                        $result[$prod['offer_id']] = $arFields_off;
                        $result[$prod['offer_id']]["quantity"] = $prod["quantity"];
                        $result[$prod['offer_id']]["price"] = $prod["price"];
                        $result[$prod['offer_id']]["sku"] = $prod["sku"];
                    }else{
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array(
                            "SEVERITY" => 'ERROR',
                            "AUDIT_TYPE_ID" => 'NOT_FOUND_PRODUCT',
                            "MODULE_ID" => MAXYSS_MODULE_NAME,
                            "ITEM_ID" => $prod['offer_id'],
                            "DESCRIPTION" => $prod['offer_id'] . ' - product not found',
                        ));
                    }
                }
                else
                {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array(
                        "SEVERITY" => 'ERROR',
                        "AUDIT_TYPE_ID" => 'NOT_FOUND_PRODUCT',
                        "MODULE_ID" => MAXYSS_MODULE_NAME,
                        "ITEM_ID" => $prod['offer_id'],
                        "DESCRIPTION" => $prod['offer_id'].' - product not found',
                    ));
                }
            }

        }
        return $result;
    }

    public static function OzonLoadOrder($lid = '', $step=0, $order_id = 0){
        $arSettings = array();
        $arOptions = CMaxyssOzon::getOptions($lid);
        if($lid !='') {
            $arSettings = $arOptions[$lid];
            $arSettings['SITE'] = $lid;
        }
        else
        {
            $arSettings = $arOptions[key($arOptions)];
            $arSettings['SITE'] = $lid = key($arOptions);
        }

        if( strpos($arSettings['SKLAD_ID'], '}') ){
            $arSettings['SKLAD_ID'] = unserialize($arSettings['SKLAD_ID']);
        }

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];


        $orders = array();
        $arStatusBy = array();
        $arStatusBy['awaiting_packaging'] = $arSettings["AWAITING_PACKAGING"];
        $arStatusBy['awaiting_deliver'] = $arSettings["AWAITING_DELIVER"];
        $arStatusBy['not_accepted'] = $arSettings["NOT_ACCEPTED"];
        $arStatusBy['acceptance_in_progress'] = $arSettings["NOT_ACCEPTED"];
        $arStatusBy['delivering'] = $arSettings["DELIVERING"];
        $arStatusBy['delivered'] = $arSettings["DELIVERED"];
        $arStatusBy['cancelled'] = $arSettings["CANCELLED"];
        $arStatusBy['arbitration'] = $arSettings["ARBITRATION"];
        $arStatusBy['driver_pickup'] = $arSettings["DRIVER_PICKUP"];

        global  $USER;
        if(!is_object($USER))
            $USER = new CUser;
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_MODULE_NAME."/log_order.txt", print_r(date('d.m.y H.i.s').' - start order', true).PHP_EOL);

        $day = $arSettings["PERIOD_ORDER_DAY"];
        $date_to= date("Y-m-d");
        $time_to =  date("H:i:s");
        $date_since= date("Y-m-d", time()-86400*$day);
        $time_since =  date("H:i:s", time()-86400*$day);
        $limit = (isset($arSettings["LIMIT_ORDER"]) && $arSettings["LIMIT_ORDER"] > 0)? $arSettings["LIMIT_ORDER"] : 50;

        $offset = $step*$limit;

        $data_string = array(
            "dir"=>"asc",
            "filter"=>array(
                "since"=> $date_since.'T'.$time_since.'.1Z',
//                "status"=> $arSettings["STATUS_OZON", ""),
                "to"=> $date_to.'T'.$time_to.'.1Z',
//                "order_id"=> 612000040
            ),
            "limit"=> $limit,
            "offset"=> $offset,
            "with"=>array(
                "analytics_data"=>true,
                "barcodes"=> true,
                "translit"=> false,
                "financial_data"=> true
            )
        );
        if($order_id >0){
            $data_string["filter"]["order_id"] = $order_id;
        }
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $bck = self::bck();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $result_orders = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v3/posting/fbs/list");
            file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_MODULE_NAME."/log_order.txt", print_r($result_orders, true).PHP_EOL, FILE_APPEND);
        }
        if(isset($result_orders['error'])) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order.txt", print_r($result_orders['error']->code . ' - ' . $result_orders['error']->message, true) . PHP_EOL, FILE_APPEND);
            return "CMaxyssOzonAgent::OzonLoadOrder('" . $lid . "'," . $step . ");";
        }
        else
        {

            $person_type = $arSettings["PERSON_TYPE"];
            $prop_ozon_code = Option::get(MAXYSS_MODULE_NAME, "PROPERTY_ORDER_OZON", "");
            $prop_ozon_date_code = Option::get(MAXYSS_MODULE_NAME, "PROPERTY_DATE_OZON", "");
             $tpl_integration_type = CHelpMaxyss::chek_propety_order('tpl_integration_type', $person_type, $lid);
            $OZON_FINAL_YES = CHelpMaxyss::chek_propety_order('OZON_FINAL_YES', $person_type, $lid);

            $prop_ozon_code_flag = false;
            $db_props = CSaleOrderProps::GetList(
                array("SORT" => "ASC"),
                array(
                    "CODE" => $prop_ozon_code,
                    "PERSON_TYPE_ID" => $person_type,
                ),
                false,
                false,
                array()
            );
            if ($props = $db_props->Fetch()) {
                $prop_ozon_code_flag = true;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order.txt", print_r(GetMessage('PROPERTY_ORDER_FALSE'), true) . PHP_EOL, FILE_APPEND);
            }



            if(VERSION_OZON_3 && $tpl_integration_type) {
                if ($result_orders['has_next']) $step++; else $step = 0;
                if (!empty($result_orders['postings']) && $prop_ozon_code_flag) {
                    $siteId = '';
                    $siteId = $arSettings["SITE"];
                    if ($siteId == '') {
                        $rsSites = CSite::GetList($by = "def", $order = "desc", Array('DEFAULT' => "Y"));
                        if ($arSite = $rsSites->Fetch()) {
                            $siteId = $arSite['LID'];
                        }
                    }
                    $user__defaulte = $arSettings["USER_DEFAULTE"];
                    $user_is_it = array();
                    $rsUser = CUser::GetById($user__defaulte);
                    if ($arUser = $rsUser->Fetch()) {
                        $user_id = $user__defaulte;
                        $user_is_it = $arUser;
                    } else {
                        $user_id = '';
                    }
                    $currencyCode = $arSettings["VALUTA_ORDER"];

                    $delivery_services = unserialize($arSettings["DELIVERY_SERVICE_V3"]);

                    $paysystem = $arSettings["PAYSYSTEM"];

                    $orders = $result_orders["postings"];

                    if($order_id >0)   echo '<pre>', print_r($orders), '</pre>' ;

                    foreach ($orders as &$order_ozon) {
                        $flag_status_change = false;

                        if($order_ozon['status'] == 'awaiting_approve' || $order_ozon['status'] == 'awaiting_packaging') continue; // ������� �������������

                        $products = $order_ozon["products"];
                        // get info product
                        $order_ozon["products_bitrix"] = self::getProduct($products, $lid);

                        foreach ($order_ozon['products_bitrix'] as $key_prod => $prod){
                            if($prod['AVAILABLE'] == 'N' && $arSettings["CALLBACK_BX"] == 'Y') {
                                $eventLog = new \CEventLog;
                                $eventLog->Add(array(
                                    "SEVERITY" => 'INFO',
                                    "AUDIT_TYPE_ID" => 'PRODUCT_NOT_AVAILABLE',
                                    "MODULE_ID" => MAXYSS_MODULE_NAME,
                                    "ITEM_ID" => $prod['ID'],
                                    "DESCRIPTION" => GetMessage('MAXYSS_OZON_NOT_AVAILABLE_PRODUCT').$prod['ID'],
                                ));
//                                unset($order_ozon['products_bitrix'][$key_prod]);
                            }
                        }

                        // find order to Bitrix
                        $arFilterOrder = array(
                            'PROPERTY_VAL_BY_CODE_' . $prop_ozon_code => $order_ozon['posting_number'],
                        );
                        $rsOrders = \CSaleOrder::GetList(
                            array('DATE_INSERT' => 'DESC'),
                            $arFilterOrder
                        );

                        $flag_order = false;
                        if ($arOrder = $rsOrders->Fetch())
                        {
                            $flag_order = true; // order is it

                            // proverim sostav zakaza


//                            if(count($order_ozon["products_bitrix"]) > 0) {
                                $order_bitrix = Bitrix\Sale\Order::load($arOrder['ID']);
                                $basket = $order_bitrix->getBasket();
                                $flag_save_order = false;
                                $flag_save_basket = false;

                                $propertyCollection = $order_bitrix->getPropertyCollection();
                                foreach ($propertyCollection as $prop) {
                                    $value = '';
                                    switch ($prop->getField('CODE')) {
                                        case $prop_ozon_date_code:
                                            if($prop->getField('VALUE') != CDatabase::FormatDate(substr($order_ozon["shipment_date"], 0, 10), "YYYY-MM-DD", "DD.MM.YYYY")) {
                                                $value = CDatabase::FormatDate(substr($order_ozon["shipment_date"], 0, 10), "YYYY-MM-DD", "DD.MM.YYYY");
                                            }
                                            break;

                                        default:
                                    }
//
                                    if (!empty($value)) {
                                        $prop->setValue($value);
                                        $flag_save_order = true;
                                    }
                                }

                            $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnBasketUpdate", array($order_bitrix, &$order_ozon, $params = array('ozon_id'=>$ClientId)));  $event->send();

                                    if(count($order_ozon["products_bitrix"]) > 0) {
                                        $sum = 0;


                                        // proverim sostav
                                        $prod_tmp = array();
                                        foreach ($order_ozon['products_bitrix'] as $item_tmp) {
                                            $prod_tmp[$item_tmp['ID']] = $item_tmp;
                                        }
                                        foreach ($basket as $basketItem) {
                                            if (
                                                array_key_exists($basketItem->getField('PRODUCT_ID'), $prod_tmp) &&
                                                $basketItem->getQuantity() != $prod_tmp[$basketItem->getField('PRODUCT_ID')]['quantity']
                                            ) {
                                                $flag_save_basket = true;
                                                $basketItem->setField('QUANTITY', $prod_tmp[$basketItem->getField('PRODUCT_ID')]['quantity']);
                                                $sum += floatval($basketItem->getField("PRICE")) * $prod_tmp[$basketItem->getField('PRODUCT_ID')]['quantity'];
                                            } elseif (!array_key_exists($basketItem->getField('PRODUCT_ID'), $prod_tmp)) {
                                                $flag_save_basket=true;
                                                $basketItem->delete();
                                            }
                                            unset($prod_tmp[$basketItem->getField('PRODUCT_ID')]);
                                        }
                                        if(!empty($prod_tmp)){
                                            $flag_save_basket = true;
                                            foreach ($prod_tmp as $product_add) {
                                                $item = $basket->createItem('catalog', $product_add['ID']);
                                                $item->setFields(array(
                                                    'QUANTITY' => $product_add["quantity"],
                                                    'CURRENCY' => $currencyCode,
                                                    'LID' => $siteId,
                                                    'BASE_PRICE' => floatval($product_add["price"]),
                                                    'PRICE' => floatval($product_add["price"]),
                                                    'CUSTOM_PRICE' => 'Y',
                                                    'NAME' => $product_add['NAME'],
                                                    'DETAIL_PAGE_URL' => $product_add['DETAIL_PAGE_URL'],
                                                    'PRODUCT_XML_ID' => $product_add['EXTERNAL_ID'],
                                                    'CATALOG_XML_ID' => $product_add['IBLOCK_EXTERNAL_ID'],
                                                    'WEIGHT' => $product_add['WEIGHT'],
                                                    'VAT_RATE' => self::vatRateConvert($product_add['VAT_ID']),
                                                    'VAT_INCLUDED' => $product_add['VAT_INCLUDED'],
                                                    'DIMENSIONS' => serialize(array("WIDTH" => $product_add["WIDTH"], "HEIGHT" => $product_add["HEIGHT"], "LENGTH"=> $product_add["LENGTH"])),
                                                ));
                                                $sum += floatval($product_add["price"]) * $product_add["quantity"];

                                                if ($arSettings["CALLBACK_BX"] == 'Y') $item->setFields(array('PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'));

                                                if(array_search($product_add['sku'], $order_ozon["requirements"]["products_requiring_gtd"]) !== false) {
                                                    $collection = $item->getPropertyCollection();
                                                    $item_prop = $collection->createItem();
                                                    $item_prop->setFields([
                                                        'NAME' => 'ozon_products_requiring_gtd',
                                                        'CODE' => 'PRODUCTS_REQUIRING_GTD',
                                                        'XML_ID' => 'PRODUCTS_REQUIRING_GTD',
                                                        'VALUE' => 'Y',
                                                    ]);
                                                }
                                            }
                                        }


                                        if($flag_save_basket) {
                                            $rb = $order_bitrix->save();
                                            if ($rb->isSuccess()) {
                                                $eventLog = new \CEventLog;
                                                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_bitrix->getField('ID'), "DESCRIPTION" => "OK SAVE BASKET", ));

                                                $order_bitrix = Bitrix\Sale\Order::load($arOrder['ID']);
                                                $basket_new = $order_bitrix->getBasket();
                                                $shipmentCollection = $order_bitrix->getShipmentCollection()->getNotSystemItems();
                                                foreach ($shipmentCollection as $shipment) {
                                                    if ($shipment->getField('DEDUCTED') != 'Y') {
                                                        $collection = $shipment->getShipmentItemCollection();
                                                        foreach ($collection as $shipmentItem) {
                                                            $r = $shipmentItem->delete();
                                                            if (!$r->isSuccess()) {
                                                                $eventLog = new \CEventLog;
                                                                $eventLog->Add(array("SEVERITY" => 'ERROR', "AUDIT_TYPE_ID" => 'ITEM_SHIPMENT_DELETE', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $arOrder['ID'], "DESCRIPTION" => serialize($r->getErrorMessages())));
                                                            }
                                                        }

                                                        $collection_new = $shipment->getShipmentItemCollection();

                                                        foreach ($basket_new as $basketItem) {
                                                            $shipmentItemNew = $collection_new->createItem($basketItem);
                                                            $shipmentItemNew->setQuantity($basketItem->getQuantity());
                                                        }
                                                    } else {
                                                        $eventLog = new \CEventLog;
                                                        $eventLog->Add(array("SEVERITY" => 'ERROR', "AUDIT_TYPE_ID" => 'SHIPMENT_UPDATE', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $arOrder['ID'], "DESCRIPTION" => 'shipment ' . $shipment->getField('ID') . ' is DEDUCTED'));
                                                    }

                                                }

                                                $flag_save_order = true;
                                            }
                                            else
                                            {
                                                $eventLog = new \CEventLog;
                                                $eventLog->Add(array("SEVERITY" => 'ERROR',"AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_ozon['posting_number'], "DESCRIPTION" => serialize($rb->getErrorMessages())));
                                            }
                                        }

                                        if($sum != 0) {
                                            $paymentCollection = $order_bitrix->getPaymentCollection();
                                            foreach ($paymentCollection as $payment) {
                                                $payment->setFields(array(
                                                    'SUM' => $sum,
                                                ));
                                            }
                                        }
                                    }
//                                }


                            if ($arOrder['STATUS_ID'] == $arStatusBy[$order_ozon['status']]) {
                                // ne izmenilsia
                            } else {
                                self::changeStatusOrder($order_ozon, $arSettings, $order_bitrix);
                                if($order_bitrix->getField("STATUS_ID") == '') $order_bitrix->setField("STATUS_ID", 'N');
                                if($arOrder['STATUS_ID'] != $order_bitrix->getField("STATUS_ID")) $flag_save_order = true;
                            }

                            if($flag_save_order){
                                $ro = $order_bitrix->save();
                                if (!$ro->isSuccess()) {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'ERROR',"AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_ozon['posting_number'], "DESCRIPTION" => serialize($ro->getErrorMessages())));
                                }
                                else
                                {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_bitrix->getField('ID'), "DESCRIPTION" => "OK SAVE", ));
                                }
                            }
                        }

                        if (count($order_ozon["products_bitrix"]) > 0 && !$flag_order) {

                            if (!$user_id && is_array($order_ozon['customer'])) {
                                $rsUser = CUser::GetByLogin($order_ozon['customer']['customer_id']);
                                if ($arUser = $rsUser->Fetch()) {
                                    $user_new_id = $arUser['ID'];
                                } else {
                                    $user = new CUser;
                                    $arFields = Array(
                                        "NAME" => $order_ozon['customer']['name'],
                                        "EMAIL" => ($order_ozon['customer']['customer_email']) ? $order_ozon['customer']['customer_email'] : $order_ozon['customer']['phone'] . '@ozon.ru',
                                        "PERSONAL_PHONE" => $order_ozon['customer']['phone'],
                                        "PERSONAL_COUNTRY" => $order_ozon['customer']['address']['country'],
                                        "PERSONAL_CITY" => $order_ozon['customer']['address']['city'],
                                        "PERSONAL_STATE" => $order_ozon['customer']['address']['region'] . ' ' . $order_ozon['customer']['address']['district'],
                                        "PERSONAL_NOTES" => $order_ozon['customer']['address']['comment'],
                                        "PERSONAL_ZIP" => $order_ozon['customer']['address']['zip_code'],
                                        "LOGIN" => $order_ozon['customer']['customer_id'],
                                        "PASSWORD" => "qwerty123456",
                                        "CONFIRM_PASSWORD" => "qwerty123456",
                                    );

                                    $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnUserNew", array(&$arFields, $order_ozon));
                                    $event->send();

                                    $user_new_id = $user->Add($arFields);
                                    if (!intval($user_new_id) > 0) {
                                        $user_new_id = 1;
                                        $eventLog = new \CEventLog;
                                        $eventLog->Add(array(
                                            "SEVERITY" => 'INFO',
                                            "AUDIT_TYPE_ID" => 'CREATE_USER',
                                            "MODULE_ID" => MAXYSS_MODULE_NAME,
                                            "ITEM_ID" => $order_ozon['posting_number'],
                                            "DESCRIPTION" => $user->LAST_ERROR,
                                        ));
                                    }
                                }
                            }
                            elseif (!$user_id && empty($order_ozon['customer'])) {
                                $user = new CUser;
                                $arFields = Array(
                                    "NAME" => $order_ozon['posting_number'],
                                    "EMAIL" => "ozon" . $order_ozon['posting_number'] . "@" . SITE_SERVER_NAME,
                                    "LOGIN" => $order_ozon['posting_number'],
                                    "PASSWORD" => "qwerty123456",
                                    "CONFIRM_PASSWORD" => "qwerty123456",
                                );

                                $user_new_id = $user->Add($arFields);
                                if (!intval($user_new_id) > 0)
                                    $user_new_id = 1;
                            } else {
                                $user_new_id = $user_id;
                            }

                            $financial_data = '';

                            if(is_array($order_ozon['financial_data']['products'])) {
                                foreach ($order_ozon['financial_data']['products'] as $data) {
                                    $financial_data .= "\n commission_amount - " . $data['commission_amount'] . ",  commission_percent - " . $data['commission_percent']. ",  price - " . $data['price']. ",  product_id - " . $data['product_id']. ",  quantity - " . $data['quantity'];
                                }
                            }


                            $basket = Basket::create($siteId);

                            $sum = 0;
                            foreach ($order_ozon["products_bitrix"] as $product) {
                                $item = $basket->createItem('catalog', $product['ID']);
                                $item->setFields(array(
                                    'QUANTITY' => $product["quantity"],
                                    'CURRENCY' => $currencyCode,
                                    'LID' => $siteId,
                                    'BASE_PRICE' => floatval($product["price"]),
                                    'PRICE' => floatval($product["price"]),
                                    'CUSTOM_PRICE' => 'Y',
                                    'NAME' => $product['NAME'],
                                    'DETAIL_PAGE_URL' => $product['DETAIL_PAGE_URL'],
//                            'XML_ID' => $product['EXTERNAL_ID'],
                                    'PRODUCT_XML_ID' => $product['EXTERNAL_ID'],
                                    'CATALOG_XML_ID' => $product['IBLOCK_EXTERNAL_ID'],
                                    'WEIGHT' => $product['WEIGHT'],
                                    'VAT_RATE' => self::vatRateConvert($product['VAT_ID']),
                                    'VAT_INCLUDED' => $product['VAT_INCLUDED'],
                                    'DIMENSIONS' => serialize(array("WIDTH" => $product["WIDTH"], "HEIGHT" => $product["HEIGHT"], "LENGTH"=> $product["LENGTH"])),
                                ));
                                if ($arSettings["CALLBACK_BX"] == 'Y') $item->setFields(array('PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'));

                                if(array_search($product['sku'], $order_ozon["requirements"]["products_requiring_gtd"]) !== false) {
                                    $collection = $item->getPropertyCollection();
                                    $item_prop = $collection->createItem();
                                    $item_prop->setFields([
                                        'NAME' => 'ozon_products_requiring_gtd',
                                        'CODE' => 'PRODUCTS_REQUIRING_GTD',
                                        'XML_ID' => 'PRODUCTS_REQUIRING_GTD',
                                        'VALUE' => 'Y',
                                    ]);
                                }


                                $sum += floatval($product["price"]) * $product["quantity"];
                            }

                            $order_bitrix = Order::create($siteId, $user_new_id);
                            $order_bitrix->setPersonTypeId($person_type);

                            $order_bitrix->setBasket($basket);

                            $order_bitrix->setField('CURRENCY', $currencyCode);
                            $order_bitrix->setField('USER_DESCRIPTION', GetMessage('OZON_MAXYSS_ORDER_MESSAGE').',   '.$financial_data);

                            if($arSettings['RESPONSIBLE_ID'] !='') {
                                $order_bitrix->setField('EMP_STATUS_ID', $arSettings['RESPONSIBLE_ID']);
                                $order_bitrix->setField('RESPONSIBLE_ID', $arSettings['RESPONSIBLE_ID']);
                            }else{
                                $order_bitrix->setField('EMP_STATUS_ID', 1);
                            }

                            $delivery_service = $delivery_services[ $order_ozon['delivery_method']['warehouse_id'] ][$order_ozon['delivery_method']['id']];

                            $shipmentCollection = $order_bitrix->getShipmentCollection();
                            $shipment = $shipmentCollection->createItem();
                            if ($delivery_service == '') $delivery_service = Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
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

                            $paymentCollection = $order_bitrix->getPaymentCollection();
                            $payment = $paymentCollection->createItem();
                            $paySystemService = PaySystem\Manager::getObjectById($paysystem);
                            $payment->setFields(array(
                                'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
                                'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
                                'SUM' => $sum,
                            ));


                            $propertyCollection = $order_bitrix->getPropertyCollection();

                            if (is_array($order_ozon['customer'])) {
                                if($phoneProp = $propertyCollection->getPhone())
                                    $phoneProp->setValue($order_ozon['customer']['phone']);
                                if($nameProp = $propertyCollection->getPayerName())
                                    $nameProp->setValue($order_ozon['customer']['name']);
                                if($nameProp = $propertyCollection->getAddress())
                                    $nameProp->setValue($order_ozon['customer']['address']['address_tail'].', PVZ '.$order_ozon['customer']['address']['pvz_code'].', '.$order_ozon['customer']['address']['comment']);
//                                    $nameProp->setValue($order_ozon['customer']['address']['country'].', '.$order_ozon['customer']['address']['region'].', '.$order_ozon['customer']['address']['district'].', '.$order_ozon['customer']['address']['city'].', '.$order_ozon['customer']['address']['zip_code'].', PVZ '.$order_ozon['customer']['address']['pvz_code'].', '.$order_ozon['customer']['address']['comment']);
                                if($nameProp = $propertyCollection->getUserEmail())
                                    $nameProp->setValue($order_ozon['customer']['customer_email']);
                            }
                            else
                            {
                                $rsUser = CUser::GetByID($user_new_id);
                                $arUser = $rsUser->Fetch();
                                if(!empty($arUser)){
                                    if($phoneProp = $propertyCollection->getPhone())
                                        $phoneProp->setValue($arUser["PERSONAL_PHONE"]);
                                    if($nameProp = $propertyCollection->getPayerName())
                                        $nameProp->setValue($arUser["NAME"].' '.$arUser["SECOND_NAME"].' '.$arUser["LAST_NAME"]);
                                    if($nameProp = $propertyCollection->getUserEmail())
                                        $nameProp->setValue($arUser["EMAIL"]);
                                }
                            }

//                            tpl_integration_type

                            foreach ($propertyCollection as $prop) {
                                $value = '';
                                switch ($prop->getField('CODE')) {
                                    case $prop_ozon_code:
                                        $value = $order_ozon["posting_number"];
                                        $value = trim($value);
                                        break;

                                    case $tpl_integration_type:
                                        $value = $order_ozon["tpl_integration_type"];
                                        $value = trim($value);
                                        break;

                                    case $prop_ozon_date_code:
                                        $value = CDatabase::FormatDate(substr($order_ozon["shipment_date"], 0, 10), "YYYY-MM-DD", "DD.MM.YYYY");
                                        break;

                                    default:
                                }

                                if (!empty($value)) {
                                    $prop->setValue($value);
                                }
                            }


                            $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnOrderNew", array(&$order_bitrix, $order_ozon, $params = array('ozon_id'=>$ClientId)));
                            $event->send();

                            self::changeStatusOrder($order_ozon, $arSettings, $order_bitrix);
                            if($order_bitrix->getField("STATUS_ID") == '') $order_bitrix->setField("STATUS_ID", 'N');


                            $result = $order_bitrix->save();
                            if ($result->isSuccess()) {
                                $eventLog = new \CEventLog;
                                $eventLog->Add(array(
                                    "SEVERITY" => 'INFO',
                                    "AUDIT_TYPE_ID" => 'CREATE_ORDER',
                                    "MODULE_ID" => MAXYSS_MODULE_NAME,
                                    "ITEM_ID" => $order_bitrix->getId(),
                                    "DESCRIPTION" => $order_bitrix->getId(). ' - order create success',
                                ));
                            } else {
                                $eventLog = new \CEventLog;
                                $eventLog->Add(array(
                                    "SEVERITY" => 'ERROR',
                                    "AUDIT_TYPE_ID" => "CREATE_ORDER",
                                    "MODULE_ID" => MAXYSS_MODULE_NAME,
                                    "ITEM_ID" => $order_bitrix->getId(),
                                    "DESCRIPTION" => serialize($result->getErrorMessages())." - ". $order_ozon['posting_number'],
                                ));
                            }

                            unset($user_new_id);

                            if ($result->isSuccess()) {
                                $orderId = $order_bitrix->getId();
                                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order.txt", print_r($orderId . ' - order create', true) . PHP_EOL, FILE_APPEND);
                            } else {
                                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order.txt", print_r($result->getErrorMessages(), true) . PHP_EOL, FILE_APPEND);
                            }

                        }

                    }

                }
            }

            if($bck['BCK'] && $bck['BCK'] != "Y") {
                self::OzonGetReturns($lid);
            }

            return "CMaxyssOzonAgent::OzonLoadOrder('" . $lid . "'," . $step . ");";
        }
    }

    public static function getPostingRecurs($ClientId, $ApiKey, $base_url = OZON_BASE_URL, &$data, $path, $limit, $result){
        $has_next = false;
        $data_string = \Bitrix\Main\Web\Json::encode($data);
        $result_orders = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, $path);
        if($result_orders['error']){
            $result = $result_orders;
            return $result;
        }
        elseif(isset($result_orders['postings']))
        { // v3
            $result['postings'] = array_merge($result['postings'],$result_orders['postings']);
            if($result_orders["count"] > ($data['offset'] + $limit)) $has_next = true;
        }
        else
        { // v2
            if(count($result_orders) == $data['offset'])  $has_next = true;
            $result['postings'] = array_merge($result['postings'],$result_orders);
        }

        if($has_next) {
            $data['offset'] = $data['offset'] + $limit;
            $result = CMaxyssOzonAgent::getPostingRecurs($ClientId, $ApiKey, $base_url, $data, $path, $limit, $result);
        }

        return $result;
    }

    public static function OzonLoadUnfulfilledOrder($lid = ''){
        $arSettings = array();
        $arOptions = CMaxyssOzon::getOptions($lid);
        if($lid !='') {
            $arSettings = $arOptions[$lid];
            $arSettings['SITE'] = $lid;
        }
        else
        {
            $arSettings = $arOptions[key($arOptions)];
            $arSettings['SITE'] = $lid = key($arOptions);
        }

        if( strpos($arSettings['SKLAD_ID'], '}') ){
            $arSettings['SKLAD_ID'] = unserialize($arSettings['SKLAD_ID']);
        }

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        $orders = array();
        $arStatusBy = array();
        $arStatusBy['awaiting_packaging'] = $arSettings["AWAITING_PACKAGING"];
        $arStatusBy['awaiting_deliver'] = $arSettings["AWAITING_DELIVER"];
        $arStatusBy['not_accepted'] = $arSettings["NOT_ACCEPTED"];
        $arStatusBy['acceptance_in_progress'] = $arSettings["NOT_ACCEPTED"];
        $arStatusBy['delivering'] = $arSettings["DELIVERING"];
        $arStatusBy['delivered'] = $arSettings["DELIVERED"];
        $arStatusBy['cancelled'] = $arSettings["CANCELLED"];
        $arStatusBy['arbitration'] = $arSettings["ARBITRATION"];
        $arStatusBy['driver_pickup'] = $arSettings["DRIVER_PICKUP"];

        global  $USER;
        if(!is_object($USER))
            $USER = new CUser;
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_MODULE_NAME."/log_order_uf.txt", print_r(date('d.m.y H.i.s').' - start order', true).PHP_EOL);

        $day = $arSettings["PERIOD_ORDER_DAY"];
//        $date_to= date("Y-m-d");
        $date_to= date("Y-m-d", time()-86400);

        $time_to =  date("H:i:s");
        $date_since= date("Y-m-d", time()+ 86400*10);
        $time_since =  date("H:i:s", time()+ 86400*10);
        $limit = 500;

        $offset = 0;

        $bck = self::bck();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $data_string = array(
                "dir"=>"asc",
                "filter"=>array(
                    "status"=>"awaiting_packaging",
                    "cutoff_from"=> $date_to.'T'.$time_to.'Z',
//                        "cutoff_to"=> $date_since.'T'.$time_since.'.1Z',
                ),
                "limit"=> $limit,
                "offset"=> $offset,
                "with"=>array(
                    "analytics_data"=>true,
                    "barcodes"=> true,
                    "product_exemplars"=> true,
                    "translit"=> false,
                    "financial_data"=> true
                )
            );
            $result_orders = CMaxyssOzonAgent::getPostingRecurs($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v3/posting/fbs/unfulfilled/list", $limit, array('postings'=>array()));
            file_put_contents($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".MAXYSS_MODULE_NAME."/log_order_uf.txt", print_r($result_orders, true).PHP_EOL, FILE_APPEND);
        }
        if(isset($result_orders['error'])) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order_uf.txt", print_r($result_orders['error']->code . ' - ' . $result_orders['error']->message, true) . PHP_EOL, FILE_APPEND);
        }
        else
        {

            $person_type = $arSettings["PERSON_TYPE"];
            $prop_ozon_code = Option::get(MAXYSS_MODULE_NAME, "PROPERTY_ORDER_OZON", "");
            $prop_ozon_date_code = Option::get(MAXYSS_MODULE_NAME, "PROPERTY_DATE_OZON", "");

            $tpl_integration_type = CHelpMaxyss::chek_propety_order('tpl_integration_type', $person_type, $lid);
            $OZON_FINAL_YES = CHelpMaxyss::chek_propety_order('OZON_FINAL_YES', $person_type, $lid);


            $prop_ozon_code_flag = false;
            $db_props = CSaleOrderProps::GetList(
                array("SORT" => "ASC"),
                array(
                    "CODE" => $prop_ozon_code,
                    "PERSON_TYPE_ID" => $person_type,
                ),
                false,
                false,
                array()
            );
            if ($props = $db_props->Fetch()) {
                $prop_ozon_code_flag = true;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order_uf.txt", print_r(GetMessage('PROPERTY_ORDER_FALSE'), true) . PHP_EOL, FILE_APPEND);
            }


            if (!empty($result_orders['postings']) && $prop_ozon_code_flag) {
                $siteId = '';
                $siteId = $arSettings["SITE"];
                if ($siteId == '') {
                    $rsSites = CSite::GetList($by = "def", $order = "desc", Array('DEFAULT' => "Y"));
                    if ($arSite = $rsSites->Fetch()) {
                        $siteId = $arSite['LID'];
                    }
                }
                $user__defaulte = $arSettings["USER_DEFAULTE"];
                $user_is_it = array();
                $rsUser = CUser::GetById($user__defaulte);
                if ($arUser = $rsUser->Fetch()) {
                    $user_id = $user__defaulte;
                    $user_is_it = $arUser;
                } else {
                    $user_id = '';
                }
                $currencyCode = $arSettings["VALUTA_ORDER"];

                $delivery_services = unserialize($arSettings["DELIVERY_SERVICE_V3"]);

                $paysystem = $arSettings["PAYSYSTEM"];

                $orders = $result_orders["postings"];

                foreach ($orders as &$order_ozon) {

                    $flag_status_change = false;

                    if($order_ozon['status'] == 'awaiting_approve') continue; //

                    $products = $order_ozon["products"];
                    // get info product
                    $order_ozon["products_bitrix"] = self::getProduct($products, $lid);

                    foreach ($order_ozon['products_bitrix'] as $key_prod => $prod){
                        if($prod['AVAILABLE'] == 'N' && $arSettings["CALLBACK_BX"] == 'Y') {
                            $eventLog = new \CEventLog;
                            $eventLog->Add(array(
                                "SEVERITY" => 'INFO',
                                "AUDIT_TYPE_ID" => 'PRODUCT_NOT_AVAILABLE',
                                "MODULE_ID" => MAXYSS_MODULE_NAME,
                                "ITEM_ID" => $prod['ID'],
                                "DESCRIPTION" => GetMessage('MAXYSS_OZON_NOT_AVAILABLE_PRODUCT').$prod['ID'],
                            ));
                        }
                    }

                    // find order to Bitrix
                    $arFilterOrder = array(
                        'PROPERTY_VAL_BY_CODE_' . $prop_ozon_code => $order_ozon['posting_number'],
                    );
                    $rsOrders = \CSaleOrder::GetList(
                        array('DATE_INSERT' => 'DESC'),
                        $arFilterOrder
                    );


                    $flag_order = false;
                    if ($arOrder = $rsOrders->Fetch()) {
                        $flag_order = true; // order is it

                        $order_bitrix = Bitrix\Sale\Order::load($arOrder['ID']);
                        $basket = $order_bitrix->getBasket();
                        $flag_save_order = false;
                        $flag_save_basket = false;

                        $propertyCollection = $order_bitrix->getPropertyCollection();
                        foreach ($propertyCollection as $prop) {
                            $value = '';
                            switch ($prop->getField('CODE')) {
                                case $prop_ozon_date_code:
                                    if($prop->getField('VALUE') != CDatabase::FormatDate(substr($order_ozon["shipment_date"], 0, 10), "YYYY-MM-DD", "DD.MM.YYYY")) {
                                        $value = CDatabase::FormatDate(substr($order_ozon["shipment_date"], 0, 10), "YYYY-MM-DD", "DD.MM.YYYY");
                                    }
                                    break;

                                default:
                            }
//
                            if (!empty($value)) {
                                $prop->setValue($value);
                                $flag_save_order = true;
                            }
                        }

                        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnBasketUpdate", array($order_bitrix, &$order_ozon, $params = array('ozon_id'=>$ClientId)));  $event->send();

                        if(count($order_ozon["products_bitrix"]) > 0) {
                            $sum = 0;
                            // proverim sostav
                            $prod_tmp = array();
                            foreach ($order_ozon['products_bitrix'] as $item_tmp) {
                                $prod_tmp[$item_tmp['ID']] = $item_tmp;
                            }
                            foreach ($basket as $basketItem) {
                                if (
                                    array_key_exists($basketItem->getField('PRODUCT_ID'), $prod_tmp) &&
                                    $basketItem->getQuantity() != $prod_tmp[$basketItem->getField('PRODUCT_ID')]['quantity']
                                ) {
                                    $flag_save_basket = true;
                                    $basketItem->setField('QUANTITY', $prod_tmp[$basketItem->getField('PRODUCT_ID')]['quantity']);
                                    $sum += floatval($basketItem->getField("PRICE")) * $prod_tmp[$basketItem->getField('PRODUCT_ID')]['quantity'];
                                } elseif (!array_key_exists($basketItem->getField('PRODUCT_ID'), $prod_tmp)) {
                                    $flag_save_basket=true;
                                    $basketItem->delete();
                                }
                                unset($prod_tmp[$basketItem->getField('PRODUCT_ID')]);
                            }
                            if(!empty($prod_tmp)){
                                $flag_save_basket = true;
                                foreach ($prod_tmp as $product_add) {
                                    $item = $basket->createItem('catalog', $product_add['ID']);
                                    $item->setFields(array(
                                        'QUANTITY' => $product_add["quantity"],
                                        'CURRENCY' => $currencyCode,
                                        'LID' => $siteId,
                                        'BASE_PRICE' => floatval($product_add["price"]),
                                        'PRICE' => floatval($product_add["price"]),
                                        'CUSTOM_PRICE' => 'Y',
                                        'NAME' => $product_add['NAME'],
                                        'DETAIL_PAGE_URL' => $product_add['DETAIL_PAGE_URL'],
                                        'PRODUCT_XML_ID' => $product_add['EXTERNAL_ID'],
                                        'CATALOG_XML_ID' => $product_add['IBLOCK_EXTERNAL_ID'],
                                        'WEIGHT' => $product_add['WEIGHT'],
                                        'VAT_RATE' => self::vatRateConvert($product_add['VAT_ID']),
                                        'VAT_INCLUDED' => $product_add['VAT_INCLUDED'],
                                        'DIMENSIONS' => serialize(array("WIDTH" => $product_add["WIDTH"], "HEIGHT" => $product_add["HEIGHT"], "LENGTH"=> $product_add["LENGTH"])),
                                    ));
                                    $sum += floatval($product_add["price"]) * $product_add["quantity"];

                                    if ($arSettings["CALLBACK_BX"] == 'Y') $item->setFields(array('PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'));
                                    if(array_search($product_add['sku'], $order_ozon["requirements"]["products_requiring_gtd"]) !== false) {
                                        $collection = $item->getPropertyCollection();
                                        $item_prop = $collection->createItem();
                                        $item_prop->setFields([
                                            'NAME' => 'ozon_products_requiring_gtd',
                                            'CODE' => 'PRODUCTS_REQUIRING_GTD',
                                            'XML_ID' => 'PRODUCTS_REQUIRING_GTD',
                                            'VALUE' => 'Y',
                                        ]);
                                    }
                                }
                            }


                            if($flag_save_basket) {
                                $rb = $order_bitrix->save();
                                if ($rb->isSuccess()) {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_bitrix->getField('ID'), "DESCRIPTION" => "OK SAVE BASKET", ));

                                    $order_bitrix = Bitrix\Sale\Order::load($arOrder['ID']);
                                    $basket = $order_bitrix->getBasket();
                                    $shipmentCollection = $order_bitrix->getShipmentCollection()->getNotSystemItems();
                                    foreach ($shipmentCollection as $shipment) {
                                        if ($shipment->getField('DEDUCTED') != 'Y') {
                                            $collection = $shipment->getShipmentItemCollection();
                                            foreach ($collection as $shipmentItem) {
                                                $r = $shipmentItem->delete();
                                                if (!$r->isSuccess()) {
                                                    $eventLog = new \CEventLog;
                                                    $eventLog->Add(array("SEVERITY" => 'ERROR', "AUDIT_TYPE_ID" => 'ITEM_SHIPMENT_DELETE', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $arOrder['ID'], "DESCRIPTION" => serialize($r->getErrorMessages())));
                                                }
                                            }

                                            $collection_new = $shipment->getShipmentItemCollection();

                                            foreach ($basket as $basketItem) {
                                                $shipmentItemNew = $collection_new->createItem($basketItem);
                                                $shipmentItemNew->setQuantity($basketItem->getQuantity());
                                            }
                                        } else {
                                            $eventLog = new \CEventLog;
                                            $eventLog->Add(array("SEVERITY" => 'ERROR', "AUDIT_TYPE_ID" => 'SHIPMENT_UPDATE', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $arOrder['ID'], "DESCRIPTION" => 'shipment ' . $shipment->getField('ID') . ' is DEDUCTED'));
                                        }

                                    }

                                    $flag_save_order = true;
                                }
                                else
                                {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'ERROR',"AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_ozon['posting_number'], "DESCRIPTION" => serialize($rb->getErrorMessages())));
                                }
                            }

                            if($sum != 0) {
                                $paymentCollection = $order_bitrix->getPaymentCollection();
                                foreach ($paymentCollection as $payment) {
                                    $payment->setFields(array(
                                        'SUM' => $sum,
                                    ));
                                }
                            }
                        }
//                                }

                        if($flag_save_order){
                            $ro = $order_bitrix->save();
                            if (!$ro->isSuccess()) {
                                $eventLog = new \CEventLog;
                                $eventLog->Add(array("SEVERITY" => 'ERROR',"AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_ozon['posting_number'], "DESCRIPTION" => serialize($ro->getErrorMessages())));
                            }
                            else
                            {
                                $eventLog = new \CEventLog;
                                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_MODULE_NAME, "ITEM_ID" => $order_bitrix->getField('ID'), "DESCRIPTION" => "OK SAVE", ));
                            }
                        }
                    }

                    if (count($order_ozon["products_bitrix"]) > 0 && !$flag_order) {

                        if (!$user_id && is_array($order_ozon['customer'])) {
                            $rsUser = CUser::GetByLogin($order_ozon['customer']['customer_id']);
                            if ($arUser = $rsUser->Fetch()) {
                                $user_new_id = $arUser['ID'];
                            } else {
                                $user = new CUser;
                                $arFields = Array(
                                    "NAME" => $order_ozon['customer']['name'],
                                    "EMAIL" => ($order_ozon['customer']['customer_email']) ? $order_ozon['customer']['customer_email'] : $order_ozon['customer']['phone'] . '@ozon.ru',
                                    "PERSONAL_PHONE" => $order_ozon['customer']['phone'],
                                    "PERSONAL_COUNTRY" => $order_ozon['customer']['address']['country'],
                                    "PERSONAL_CITY" => $order_ozon['customer']['address']['city'],
                                    "PERSONAL_STATE" => $order_ozon['customer']['address']['region'] . ' ' . $order_ozon['customer']['address']['district'],
                                    "PERSONAL_NOTES" => $order_ozon['customer']['address']['comment'],
                                    "PERSONAL_ZIP" => $order_ozon['customer']['address']['zip_code'],
                                    "LOGIN" => $order_ozon['customer']['customer_id'],
                                    "PASSWORD" => "qwerty123456",
                                    "CONFIRM_PASSWORD" => "qwerty123456",
                                );

                                $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnUserNew", array(&$arFields, $order_ozon));
                                $event->send();

                                $user_new_id = $user->Add($arFields);
                                if (!intval($user_new_id) > 0) {
                                    $user_new_id = 1;
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array(
                                        "SEVERITY" => 'INFO',
                                        "AUDIT_TYPE_ID" => 'CREATE_USER',
                                        "MODULE_ID" => MAXYSS_MODULE_NAME,
                                        "ITEM_ID" => $order_ozon['posting_number'],
                                        "DESCRIPTION" => $user->LAST_ERROR,
                                    ));
                                }
                            }
                        }
                        elseif (!$user_id && empty($order_ozon['customer'])) {
                            $user = new CUser;
                            $arFields = Array(
                                "NAME" => $order_ozon['posting_number'],
                                "EMAIL" => "ozon" . $order_ozon['posting_number'] . "@" . SITE_SERVER_NAME,
                                "LOGIN" => $order_ozon['posting_number'],
                                "PASSWORD" => "qwerty123456",
                                "CONFIRM_PASSWORD" => "qwerty123456",
                            );

                            $user_new_id = $user->Add($arFields);
                            if (!intval($user_new_id) > 0)
                                $user_new_id = 1;
                        } else {
                            $user_new_id = $user_id;
                        }

                        $financial_data = '';

                        if(is_array($order_ozon['financial_data']['products'])) {
                            foreach ($order_ozon['financial_data']['products'] as $data) {
                                $financial_data .= "\n commission_amount - " . $data['commission_amount'] . ",  commission_percent - " . $data['commission_percent']. ",  price - " . $data['price']. ",  product_id - " . $data['product_id']. ",  quantity - " . $data['quantity'];
                            }
                        }


                        $basket = Basket::create($siteId);

                        $sum = 0;
                        foreach ($order_ozon["products_bitrix"] as $product) {
                            $item = $basket->createItem('catalog', $product['ID']);
                            $item->setFields(array(
                                'QUANTITY' => $product["quantity"],
                                'CURRENCY' => $currencyCode,
                                'LID' => $siteId,
                                'BASE_PRICE' => floatval($product["price"]),
                                'PRICE' => floatval($product["price"]),
                                'CUSTOM_PRICE' => 'Y',
                                'NAME' => $product['NAME'],
                                'DETAIL_PAGE_URL' => $product['DETAIL_PAGE_URL'],
//                            'XML_ID' => $product['EXTERNAL_ID'],
                                'PRODUCT_XML_ID' => $product['EXTERNAL_ID'],
                                'CATALOG_XML_ID' => $product['IBLOCK_EXTERNAL_ID'],
                                'WEIGHT' => $product['WEIGHT'],
                                'VAT_RATE' => self::vatRateConvert($product['VAT_ID']),
                                'VAT_INCLUDED' => $product['VAT_INCLUDED'],
                                'DIMENSIONS' => serialize(array("WIDTH" => $product["WIDTH"], "HEIGHT" => $product["HEIGHT"], "LENGTH"=> $product["LENGTH"])),
                            ));
                            if ($arSettings["CALLBACK_BX"] == 'Y') $item->setFields(array('PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'));

                            if(array_search($product['sku'], $order_ozon["requirements"]["products_requiring_gtd"]) !== false) {
                                $collection = $item->getPropertyCollection();
                                $item_prop = $collection->createItem();
                                $item_prop->setFields([
                                    'NAME' => 'ozon_products_requiring_gtd',
                                    'CODE' => 'PRODUCTS_REQUIRING_GTD',
                                    'XML_ID' => 'PRODUCTS_REQUIRING_GTD',
                                    'VALUE' => 'Y',
                                ]);
                            }
                            $sum += floatval($product["price"]) * $product["quantity"];
                        }

                        $order_bitrix = Order::create($siteId, $user_new_id);
                        $order_bitrix->setPersonTypeId($person_type);

                        $order_bitrix->setBasket($basket);

                        $order_bitrix->setField('CURRENCY', $currencyCode);
                        $order_bitrix->setField('USER_DESCRIPTION', GetMessage('OZON_MAXYSS_ORDER_MESSAGE').',   '.$financial_data);

                        if($arSettings['RESPONSIBLE_ID'] !='') {
                            $order_bitrix->setField('EMP_STATUS_ID', $arSettings['RESPONSIBLE_ID']);
                            $order_bitrix->setField('RESPONSIBLE_ID', $arSettings['RESPONSIBLE_ID']);
                        }else{
                            $order_bitrix->setField('EMP_STATUS_ID', 1);
                        }

                        $delivery_service = $delivery_services[ $order_ozon['delivery_method']['warehouse_id'] ][$order_ozon['delivery_method']['id']];

                        $shipmentCollection = $order_bitrix->getShipmentCollection();
                        $shipment = $shipmentCollection->createItem();
                        if ($delivery_service == '') $delivery_service = Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
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

                        $paymentCollection = $order_bitrix->getPaymentCollection();
                        $payment = $paymentCollection->createItem();
                        $paySystemService = PaySystem\Manager::getObjectById($paysystem);
                        $payment->setFields(array(
                            'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
                            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
                            'SUM' => $sum,
                        ));


                        $propertyCollection = $order_bitrix->getPropertyCollection();

                        if (is_array($order_ozon['customer'])) {
                            if($phoneProp = $propertyCollection->getPhone())
                                $phoneProp->setValue($order_ozon['customer']['phone']);
                            if($nameProp = $propertyCollection->getPayerName())
                                $nameProp->setValue($order_ozon['customer']['name']);
                            if($nameProp = $propertyCollection->getAddress())
                                $nameProp->setValue($order_ozon['customer']['address']['address_tail'].', PVZ '.$order_ozon['customer']['address']['pvz_code'].', '.$order_ozon['customer']['address']['comment']);
//                                    $nameProp->setValue($order_ozon['customer']['address']['country'].', '.$order_ozon['customer']['address']['region'].', '.$order_ozon['customer']['address']['district'].', '.$order_ozon['customer']['address']['city'].', '.$order_ozon['customer']['address']['zip_code'].', PVZ '.$order_ozon['customer']['address']['pvz_code'].', '.$order_ozon['customer']['address']['comment']);
                            if($nameProp = $propertyCollection->getUserEmail())
                                $nameProp->setValue($order_ozon['customer']['customer_email']);
                        }
                        else
                        {
                            $rsUser = CUser::GetByID($user_new_id);
                            $arUser = $rsUser->Fetch();
                            if(!empty($arUser)){
                                if($phoneProp = $propertyCollection->getPhone())
                                    $phoneProp->setValue($arUser["PERSONAL_PHONE"]);
                                if($nameProp = $propertyCollection->getPayerName())
                                    $nameProp->setValue($arUser["NAME"].' '.$arUser["SECOND_NAME"].' '.$arUser["LAST_NAME"]);
                                if($nameProp = $propertyCollection->getUserEmail())
                                    $nameProp->setValue($arUser["EMAIL"]);
                            }
                        }

//                            tpl_integration_type
                        foreach ($propertyCollection as $prop) {
                            $value = '';
                            switch ($prop->getField('CODE')) {
                                case $prop_ozon_code:
                                    $value = $order_ozon["posting_number"];
                                    $value = trim($value);
                                    break;

                                case $tpl_integration_type:
                                    $value = $order_ozon["tpl_integration_type"];
                                    $value = trim($value);
                                    break;

                                case $prop_ozon_date_code:
                                    $value = CDatabase::FormatDate(substr($order_ozon["shipment_date"], 0, 10), "YYYY-MM-DD", "DD.MM.YYYY");
                                    break;

                                default:
                            }

                            if (!empty($value)) {
                                $prop->setValue($value);
                            }
                        }


                        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnOrderNew", array(&$order_bitrix, $order_ozon, $params = array('ozon_id'=>$ClientId)));
                        $event->send();

                        self::changeStatusOrder($order_ozon, $arSettings, $order_bitrix);
                        if($order_bitrix->getField("STATUS_ID") == '') $order_bitrix->setField("STATUS_ID", 'N');

                        $result = $order_bitrix->save();

                        if ($result->isSuccess()) {
                            $eventLog = new \CEventLog;
                            $eventLog->Add(array(
                                "SEVERITY" => 'INFO',
                                "AUDIT_TYPE_ID" => 'CREATE_ORDER',
                                "MODULE_ID" => MAXYSS_MODULE_NAME,
                                "ITEM_ID" => $order_bitrix->getId(),
                                "DESCRIPTION" => $order_bitrix->getId(). ' - order create success',
                            ));
                        } else {
                            $eventLog = new \CEventLog;
                            $eventLog->Add(array(
                                "SEVERITY" => 'ERROR',
                                "AUDIT_TYPE_ID" => "CREATE_ORDER",
                                "MODULE_ID" => MAXYSS_MODULE_NAME,
                                "ITEM_ID" => $order_bitrix->getId(),
                                    "DESCRIPTION" => serialize($result->getErrorMessages())." - ". $order_ozon['posting_number'],
                            ));
                        }
                        unset($user_new_id);
                    }

                }

            }
        }
        return "CMaxyssOzonAgent::OzonLoadUnfulfilledOrder('" . $lid . "');";
    }

    public static function vatRateConvert($vat_id = 1){

        $result = 0.00;
        $dbRes = CCatalogVat::GetListEx(array(), array('ID'=>$vat_id));
        if ($arRes = $dbRes->Fetch()) {
            $result = $arRes['RATE']/100;
        }

        return $result;
    }
    public static function changeStatusOrder($order_ozon = array(), $arSettings = array(), $order_bitrix){

        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnBeforeChangeStatusOrder", array(&$order_bitrix, $order_ozon, $arSettings));
        $event->send();

        if(!empty($order_ozon) && !empty($arSettings) && is_object($order_bitrix)){
            $arStatusBy = array();
            $arStatusBy['awaiting_packaging'] = $arSettings["AWAITING_PACKAGING"];
            $arStatusBy['awaiting_deliver'] = $arSettings["AWAITING_DELIVER"];
            $arStatusBy['not_accepted'] = $arSettings["NOT_ACCEPTED"];
            $arStatusBy['acceptance_in_progress'] = $arSettings["NOT_ACCEPTED"];
            $arStatusBy['delivering'] = $arSettings["DELIVERING"];
            $arStatusBy['delivered'] = $arSettings["DELIVERED"];
            $arStatusBy['arbitration'] = $arSettings["ARBITRATION"];
            $arStatusBy['driver_pickup'] = $arSettings["DRIVER_PICKUP"];
            $arStatusBy['cancelled'] = $arSettings["CANCELLED"];


            $arStatusBy['accepted_from_customer'] = $arSettings["ACCEPTED_FROM_CUSTOMER"];
            $arStatusBy['waiting_for_seller'] = $arSettings["WAITING_FOR_SELLER"];
            $arStatusBy['ready_for_shipment'] = $arSettings["READY_FOR_SHIPMENT"];
            $arStatusBy['returned_to_seller'] = $arSettings["RETURNED_TO_SELLER"];
            $arStatusBy['cancelled_with_compensation'] = $arSettings["CANCELLED_WITH_COMPENSATION"];
            $arStatusBy['disposed'] = $arSettings["DISPOSED"];
            $arStatusSort = array(
                    "awaiting_packaging",
                    "awaiting_deliver",
                    "not_accepted",
                    "arbitration",
                    "acceptance_in_progress",
                    "delivering",
                    "driver_pickup",
                    "delivered",
                    "cancelled",

                    "accepted_from_customer",
                    "waiting_for_seller",
                    "ready_for_shipment",
                    "returned_to_seller",
                    "cancelled_with_compensation",
                    "disposed",
            );

            $arStatusSipmentBitrixBD = unserialize($arSettings["STATUS_SHIP_BITRIX"]);
            $arFlagSipmentBitrixBD = unserialize($arSettings["FLAG_SHIPMENT_UP"]);
            $arFlagPaymentBitrixBD = unserialize($arSettings["FLAG_PAYMENT_UP"]);
            $arFlagCancelledBitrixBD = unserialize($arSettings["FLAG_CANCELLED_UP"]);
            $STATUS_OZON = strtoupper($order_ozon['status']);
            $STATUS_OZON_KEY = array_search($order_ozon['status'], $arStatusSort);
//            $STATUS_BITRIX = $order_bitrix->getField("STATUS_ID");
            $STATUS_BITRIX_KEY = array_search(array_search($order_bitrix->getField("STATUS_ID"), $arStatusBy), $arStatusSort);
            if($STATUS_BITRIX_KEY > 8 && $STATUS_OZON_KEY < 9){
                // �� ������ ������
            }
            else
            {
                $collection = $order_bitrix->getShipmentCollection()->getNotSystemItems();
                foreach ($collection as $shipment)
                {

                    if(array_search($shipment->getField('STATUS_ID'), $arStatusSipmentBitrixBD)){
                        if($arStatusSipmentBitrixBD[$STATUS_OZON] !='')
                        { // ������ �������� ��������� � �������������
                            $shipment->setField('STATUS_ID', $arStatusSipmentBitrixBD[$STATUS_OZON]);
                        }
                    }else{
                        if($arSettings['STATUS_NO_CHANGE'] != 'Y'){
                            if($arStatusSipmentBitrixBD[$STATUS_OZON] !='')
                            {// ������ �������� ��������� � �������������
                                $shipment->setField('STATUS_ID', $arStatusSipmentBitrixBD[$STATUS_OZON]);
                            }
                            //else  echo '������ �������� �� ������<br>';

                        }else{
                            // echo '������ �������� �� ��������� � �������������<br>';
                        }
                    }
                    // ���� ��������
                    if(isset($arFlagSipmentBitrixBD[$STATUS_OZON]) && array_search("Y", $arFlagSipmentBitrixBD)) {
                        $shipment->setField('DEDUCTED', $arFlagSipmentBitrixBD[$STATUS_OZON]);
                        $shipment->setField('ALLOW_DELIVERY', $arFlagSipmentBitrixBD[$STATUS_OZON]);
                    }

                }

                if(isset($arFlagPaymentBitrixBD[$STATUS_OZON])  && array_search("Y", $arFlagPaymentBitrixBD)) {

                    $paymentCollection = $order_bitrix->getPaymentCollection();
                    foreach ($paymentCollection as $payment) {
                        $payment->setPaid($arFlagPaymentBitrixBD[$STATUS_OZON]);
                    }
                }

                if($arSettings['STATUS_NO_CHANGE'] != 'Y'){ // ������� �� �����
                    if(array_key_exists($order_ozon['status'], $arStatusBy) && $arStatusBy[$order_ozon['status']] != '') { // ������ ����� ����
                        // ������  �������
                        $order_bitrix->setField("UPDATED_1C", "N");
                        if ($order_ozon["tpl_integration_type"] == "non_integrated") {
                            if ($order_ozon['status'] == 'awaiting_deliver' && $STATUS_BITRIX_KEY == 0) {
                                $order_bitrix->setField("STATUS_ID", $arStatusBy[$order_ozon['status']]);
                            } elseif ($STATUS_OZON_KEY >= 8) {
                                if($arFlagCancelledBitrixBD[$STATUS_OZON] == "Y" || empty($arFlagCancelledBitrixBD))
                                    $order_bitrix->setField("CANCELED", 'Y');
                                $order_bitrix->setField("STATUS_ID", $arStatusBy[$order_ozon['status']]);
                            }
                        } else {
                            $order_bitrix->setField("STATUS_ID", $arStatusBy[$order_ozon['status']]);
                            if ($STATUS_OZON_KEY >= 8) {
                                if($arFlagCancelledBitrixBD[$STATUS_OZON] == "Y" || empty($arFlagCancelledBitrixBD))
                                    $order_bitrix->setField("CANCELED", 'Y');
                            }
                        }

                    }elseif (!array_key_exists($order_ozon['status'], $arStatusBy) && $arStatusBy[$order_ozon['status']] == ''){ // ������ � �� �����
                        // �� ������ �������
                    }
                }
                elseif($arSettings['STATUS_NO_CHANGE'] == 'Y')
                { //  ������� �����
                    if(array_search($order_bitrix->getField('STATUS_ID'), $arStatusBy)) { // ������ ����� ����
                        //  ������
                        $order_bitrix->setField("UPDATED_1C", "N");
                        if ($order_ozon["tpl_integration_type"] == "non_integrated") {
                            if ($order_ozon['status'] == 'awaiting_deliver'  && $STATUS_BITRIX_KEY == 0) {
                                $order_bitrix->setField("STATUS_ID", $arStatusBy[$order_ozon['status']]);
                            } elseif ($STATUS_OZON_KEY >= 8) {
                                if($arFlagCancelledBitrixBD[$STATUS_OZON] == "Y" || empty($arFlagCancelledBitrixBD))
                                    $order_bitrix->setField("CANCELED", 'Y');
                                $order_bitrix->setField("STATUS_ID", $arStatusBy[$order_ozon['status']]);
                            }
                        } else {
                            $order_bitrix->setField("STATUS_ID", $arStatusBy[$order_ozon['status']]);
                            if ($STATUS_OZON_KEY >= 8) {
                                if($arFlagCancelledBitrixBD[$STATUS_OZON] == "Y" || empty($arFlagCancelledBitrixBD))
                                    $order_bitrix->setField("CANCELED", 'Y');
                            };
                        }
                    }
                }

            }
        }

        $event = new \Bitrix\Main\Event(MAXYSS_MODULE_NAME, "OnAfterChangeStatusOrder", array(&$order_bitrix, $order_ozon, $arSettings));
        $event->send();
    }

    public static function OzonGetReturns($lid = ''){
        $arSettings = array();
        $arOptions = CMaxyssOzon::getOptions($lid);
        if($lid !='') {
            $arSettings = $arOptions[$lid];
            $arSettings['SITE'] = $lid;
        }
        else
        {
            $arSettings = $arOptions[key($arOptions)];
            $arSettings['SITE'] = $lid = key($arOptions);
        }

        if( strpos($arSettings['SKLAD_ID'], '}') ){
            $arSettings['SKLAD_ID'] = unserialize($arSettings['SKLAD_ID']);
        }

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];


        $day = $arSettings["PERIOD_ORDER_DAY"];

        $data_string = array(
            "filter"=>array(
                "accepted_from_customer_moment"=>array(
                    "time_from"=> date("Y-m-d", time()-86400*$day*3).'T'. date("H:i:s", time()-86400*$day*3).'.1Z',
                    "time_to"=> date("Y-m-d").'T'.date("H:i:s").'.1Z',
                ),
            ),
            "limit"=> 1000,
            "offset"=> 0,
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $bck = self::bck();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $result_orders = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/returns/company/fbs");
            if(is_array($result_orders['returns']) && !empty($result_orders['returns'])){

                $person_type = $arSettings["PERSON_TYPE"];
                $prop_ozon_code = Option::get(MAXYSS_MODULE_NAME, "PROPERTY_ORDER_OZON", "");

                $prop_ozon_code_flag = false;
                $db_props = CSaleOrderProps::GetList(
                    array("SORT" => "ASC"),
                    array(
                        "CODE" => $prop_ozon_code,
                        "PERSON_TYPE_ID" => $person_type,
                    ),
                    false,
                    false,
                    array()
                );
                if ($props = $db_props->Fetch()) {
                    $prop_ozon_code_flag = true;
                } else {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array(
                        "SEVERITY" => 'ERROR',
                        "AUDIT_TYPE_ID" => "RETURNS_ORDER_GET",
                        "MODULE_ID" => MAXYSS_MODULE_NAME,
                        "ITEM_ID" => $lid,
                        "DESCRIPTION" => GetMessage('PROPERTY_ORDER_FALSE'),
                    ));
                }

                if($prop_ozon_code_flag) {
                    $result_orders_complit = array();
                    foreach ($result_orders['returns'] as $order_ozon) {
                        $result_orders_complit[$order_ozon['posting_number']][$order_ozon['product_id']] =  $result_orders_complit[$order_ozon['posting_number']][$order_ozon['product_id']] + $order_ozon['quantity'];
                        $product_info[$order_ozon['product_id']] = array('product_name'=> $order_ozon['product_name'],);
                        $arOrdersOzon[$order_ozon['posting_number']] = $order_ozon;
                    }

                    foreach ($result_orders_complit as $posting=>$prod_ozon) {

                        $rsOrders = \Bitrix\Sale\Order::getList([
                            'select' => [
                                "ID",
                                "PROPERTY_VAL.VALUE",
                            ],
                            'filter' => [
                                '=PROPERTY_VAL.CODE' => $prop_ozon_code,
                                '=PROPERTY_VAL.VALUE' => $posting,
                            ],
                            'runtime' => [
                                new \Bitrix\Main\Entity\ReferenceField(
                                    'PROPERTY_VAL',
                                    '\Bitrix\sale\Internals\OrderPropsValueTable',
                                    ["=this.ID" => "ref.ORDER_ID"],
                                    ["join_type"=>"left"]
                                )
                            ]
                        ]);


                        if ($arOrder = $rsOrders->Fetch()) {
                            $order_bitrix = Bitrix\Sale\Order::load($arOrder['ID']);

                            $basket = Bitrix\Sale\Basket::loadItemsForOrder($order_bitrix);
                            $basket_quantity = array_sum($basket->getQuantityList());
                            $basket_ozon_quantity = array_sum($prod_ozon);

                            if($basket_quantity == $basket_ozon_quantity) {
                                $old_bx_status = $order_bitrix->getField('STATUS_ID');
                                self::changeStatusOrder($arOrdersOzon[$posting], $arSettings, $order_bitrix);
                                if ($order_bitrix->getField('STATUS_ID') != '' && $old_bx_status != $order_bitrix->getField('STATUS_ID')) {
                                    $result = $order_bitrix->save();
                                    if ($result->isSuccess()) {
                                        $eventLog = new \CEventLog;
                                        $eventLog->Add(array(
                                            "SEVERITY" => 'INFO',
                                            "AUDIT_TYPE_ID" => 'RETURNS_ORDER_GET',
                                            "MODULE_ID" => MAXYSS_MODULE_NAME,
                                            "ITEM_ID" => $order_bitrix->getId(),
                                            "DESCRIPTION" => $order_bitrix->getId() . ' - order status change ' . $order_bitrix->getField('STATUS_ID'),
                                        ));
                                    } else {
                                        $eventLog = new \CEventLog;
                                        $eventLog->Add(array(
                                            "SEVERITY" => 'ERROR',
                                            "AUDIT_TYPE_ID" => 'RETURNS_ORDER_GET',
                                            "MODULE_ID" => MAXYSS_MODULE_NAME,
                                            "ITEM_ID" => $order_bitrix->getId(),
                                            "DESCRIPTION" => serialize($result->getErrorMessages()) . " - " . $order_ozon['status'] . ' - order status no change ',
                                        ));
                                    }
                                }
                            }
                            else
                            {

                                $return_text = '';
                                foreach ($prod_ozon as $key_prod => $prod){
                                    if(strpos($order_bitrix->getField('USER_DESCRIPTION'), $product_info[$key_prod]['product_name']))
                                        continue;
                                    else
                                    {
                                        $return_text .= GetMessage('MAXYSS_OZON_RETURN_PRODUCT').$prod.GetMessage('MAXYSS_OZON_SHT').' '.$product_info[$key_prod]['product_name'].' ';
                                    }
                                }
                                if($return_text !='') {
                                    $order_bitrix->setField('USER_DESCRIPTION', $order_bitrix->getField('USER_DESCRIPTION') . ' ' . $return_text);
                                    $result = $order_bitrix->save();
                                    if ($result->isSuccess()) {
                                        $eventLog = new \CEventLog;
                                        $eventLog->Add(array(
                                            "SEVERITY" => 'INFO',
                                            "AUDIT_TYPE_ID" => 'RETURNS_ORDER_GET',
                                            "MODULE_ID" => MAXYSS_MODULE_NAME,
                                            "ITEM_ID" => $order_bitrix->getId(),
                                            "DESCRIPTION" => $order_bitrix->getId() . ' - order comment change ' . $return_text,
                                        ));
                                    } else {
                                        $eventLog = new \CEventLog;
                                        $eventLog->Add(array(
                                            "SEVERITY" => 'ERROR',
                                            "AUDIT_TYPE_ID" => 'RETURNS_ORDER_GET',
                                            "MODULE_ID" => MAXYSS_MODULE_NAME,
                                            "ITEM_ID" => $order_bitrix->getId(),
                                            "DESCRIPTION" => serialize($result->getErrorMessages()) . " - " . $return_text . ' - order comment no change ',
                                        ));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
class CMaxyssOrderList{
    public static function posting_fbs_get($posting, $lid){
        $arSettings = array();
        $arOptions = CMaxyssOzon::getOptions($lid);
        if($lid !='') {
            $arSettings = $arOptions[$lid];
            $arSettings['SITE'] = $lid;
        }
        else
        {
            $arSettings = $arOptions[key($arOptions)];
            $arSettings['SITE'] = $lid = key($arOptions);
        }

        if( strpos($arSettings['SKLAD_ID'], '}') ){
            $arSettings['SKLAD_ID'] = unserialize($arSettings['SKLAD_ID']);
        }

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        $data_string = array(
            "posting_number" => $posting
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $arResult = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, '/v3/posting/fbs/get');
        echo '<pre>', print_r($arResult), '</pre>' ;
    }

    public static function buttonPackageLabelOzon(){
        $arCabinet = CMaxyssOzon::getOptions(false, array('OZON_ID', 'ACTIVE_ORDER_ON'));
        $flag_button_on = false;
        foreach ($arCabinet as $key=>$ozon_id){
            if($ozon_id["ACTIVE_ORDER_ON"] == "Y") $flag_button_on = true;
        }
        $act_button = '';
        $actButton = array();
        if($flag_button_on) {
            if ($GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/" || $GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order.php") {

                global $DB;
                $row = $DB->Query("SELECT * FROM b_option WHERE NAME='act_button_time'")->Fetch();
                $act_button_time = $row['VALUE'];
                if (($act_button_time + 86400) < time())
                {
                    foreach ($arCabinet as $key=>$ozon_id) {
                        if ($ozon_id["OZON_ID"] != '') {
                            // �� �������
                            $ClientId = $ozon_id["OZON_ID"];
                            $ApiKey = CMaxyssOzon::GetApiKey($ClientId);
                            $warehouses = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, "{}", "/v1/warehouse/list");
                            if (!$warehouses['error'] && !empty($warehouses)) {
                                foreach ($warehouses as $warehouse) {
                                    $delivery_method = array();
                                    $data_string = array(
                                        "filter" => array(
                                            // "provider_id"=> 0,
                                            "status" => "ACTIVE",
                                            "warehouse_id" => $warehouse["warehouse_id"],
                                        ),
                                        "limit" => 50,
                                        "offset" => 0
                                    );
                                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                                    $delivery_method = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v1/delivery-method/list");
                                    if (!$delivery_method['error'] && !empty($delivery_method)) {
                                        foreach ($delivery_method as $key => $delivery) {
                                            $actButton[] = "{\'TEXT\':\'" . GetMessage("MAXYSS_OZON_PRINT_ACT") . " " . $ozon_id["OZON_ID"] . " " . $delivery["name"] . "\',\'ONCLICK\':\'print_act(" . $ozon_id["OZON_ID"] . "," . $delivery["id"] . ")\'}";
                                        }
                                    }


                                }
                            }
                            //$actButton[]="{\'TEXT\':\'".GetMessage("MAXYSS_OZON_PRINT_ACT")." ".$ozon_id["OZON_ID"]."\',\'ONCLICK\':\'print_act(".$ozon_id["OZON_ID"].")\'}";
                        }
                    }
                    $act_button = implode(',', $actButton);

                    Bitrix\Main\Config\Option::set(MAXYSS_MODULE_NAME, "act_button_time", time());
                    Bitrix\Main\Config\Option::set(MAXYSS_MODULE_NAME, "act_button", $act_button);
                }
                else
                {
                    $row = $DB->Query("SELECT * FROM b_option WHERE NAME='act_button'")->Fetch();
                    $act_button =  $row['VALUE'];
                }
            }

            if ($GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/list/" || $GLOBALS["APPLICATION"]->GetCurPage() == "/shop/orders/") {
                CJSCore::Init(array('maxyss_ozon'));
                ob_start();
                ?>
                <script type="text/javascript">
                    var printLabel = false;

                    function initPackageLabel() {
                        // var pathName = window.location.pathname;
                        if (!printLabel) {
                            $(function () {
                                $('#toolbar_order_list').append(
                                    '<a class="ui-btn ui-btn-primary" id="btnPrintOzon" href="javascript:void(0)" hidefocus="true" onclick="this.blur();BX.adminShowMenu(this, [{\'TEXT\':\'<?=GetMessage("OZON_MAXYSS_BUTTON_LABEL_ORDER")?>\',\'ONCLICK\':\'print_label_ozon()\'},<?=$act_button?>,{\'TEXT\':\'<?=GetMessage("MAXYSS_OZON_SHIP_ORDER")?>\',\'ONCLICK\':\'order_to_ship()\'}], {active_class: \'adm-btn-active\', public_frame: \'0\'}); return false;" title="">OZON.RU</a>'
                                );
                                printLabel = true;
                            });
                        }
                    }

                    initPackageLabel();
                </script>
                <?
                $sContent = ob_get_clean();
                $GLOBALS['APPLICATION']->AddHeadString($sContent, true);
            }
            if ($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order.php") {
                CJSCore::Init(array('maxyss_ozon'));

                ob_start();
                ?>
                <script type="text/javascript">
                    BX.addCustomEvent("onAjaxSuccessFinish", function (params) {
                        var pathname = window.location.pathname;
                        if (pathname == "/bitrix/admin/sale_order.php" && !$('a').is('#btnPrintOzon')) {
                            $('#tbl_sale_order_result_div .adm-list-table-top').append(
                                '<a  id="btnPrintOzon" href="javascript:void(0)" hidefocus="true" onclick="this.blur();BX.adminShowMenu(this, [{\'TEXT\':\'<?=GetMessage("OZON_MAXYSS_BUTTON_LABEL_ORDER")?>\',\'ONCLICK\':\'print_label_ozon()\'},<?=$act_button?>,{\'TEXT\':\'<?=GetMessage("MAXYSS_OZON_SHIP_ORDER")?>\',\'ONCLICK\':\'order_to_ship()\'}], {active_class: \'adm-btn-active\', public_frame: \'0\'}); return false;" class="adm-btn adm-btn-menu" title="">OZON.RU</a>'
                            );
                        }
                    });
                    BX.addCustomEvent("onAjaxSuccess", function (params) {
                        var pathname = window.location.pathname;
                        if (pathname == "/bitrix/admin/sale_order.php" && !$('a').is('#btnPrintOzon')) {
                            $('#tbl_sale_order_result_div .adm-list-table-top').append(
                                '<a  id="btnPrintOzon" href="javascript:void(0)" hidefocus="true" onclick="this.blur();BX.adminShowMenu(this, [{\'TEXT\':\'<?=GetMessage("OZON_MAXYSS_BUTTON_LABEL_ORDER")?>\',\'ONCLICK\':\'print_label_ozon()\'},<?=$act_button?>,{\'TEXT\':\'<?=GetMessage("MAXYSS_OZON_SHIP_ORDER")?>\',\'ONCLICK\':\'order_to_ship()\'}], {active_class: \'adm-btn-active\', public_frame: \'0\'}); return false;" class="adm-btn adm-btn-menu" title="">OZON.RU</a>'
                            );
                        }
                    });

                    var printLabel = false;

                    function initPackageLabel() {
                        var pathName = window.location.pathname;
                        if (pathName == "/bitrix/admin/sale_order.php" && !printLabel) {
                            $(function () {
                                $('#tbl_sale_order_result_div .adm-list-table-top').append(
                                    '<a id="btnPrintOzon" href="javascript:void(0)" hidefocus="true" onclick="this.blur();BX.adminShowMenu(this, [{\'TEXT\':\'<?=GetMessage("OZON_MAXYSS_BUTTON_LABEL_ORDER")?>\',\'ONCLICK\':\'print_label_ozon()\'},<?=$act_button?>,{\'TEXT\':\'<?=GetMessage("MAXYSS_OZON_SHIP_ORDER")?>\',\'ONCLICK\':\'order_to_ship()\'}], {active_class: \'adm-btn-active\', public_frame: \'0\'}); return false;" class="adm-btn adm-btn-menu" title="">OZON.RU</a>'
                                );
                                printLabel = true;
                            });
                        }
                    }

                    initPackageLabel();
                </script>
                <?
                $sContent = ob_get_clean();
                $GLOBALS['APPLICATION']->AddHeadString($sContent, true);
            }
        }
    }

    public static function OzonOnAdminContextMenuShow(&$items){
        $request = Application::getInstance()->getContext()->getRequest();
        $orderId = $request->getQuery("ID");
        $IBLOCK_ID = intval($request->getQuery("IBLOCK_ID"));
        if(intval($orderId) > 0 && ($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_view.php" || $GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/sale_order_edit.php")) {

            CJSCore::Init(array('maxyss_ozon'));
            $order = Order::load($orderId);

            if(is_object($order)) {
                $collection = $order->getShipmentCollection()->getNotSystemItems();
                foreach ($collection as $shipment) {
                    $collectionItems = $shipment->getShipmentItemCollection();
                    $quantity = 0;
                    $arItems = array();
                    foreach ($collectionItems as $shipmentItem) {
                        $bascketItem = $shipmentItem->getBasketItem();
                        if($bascketItem){
                            $arItemValue = $bascketItem->getFields()->getValues();
                            $arItemValue['DIMENSIONS'] = (is_string($arItemValue['DIMENSIONS']) && empty($arItemValue['DIMENSIONS'])) ? unserialize($arItemValue['DIMENSIONS']) : array();
                            $arItemValue['QUANTITY'] = intval($arItemValue['QUANTITY']);
                            $quantity += intval($arItemValue['QUANTITY']);


                            //////////
                            $collection_prop_item = $shipmentItem->getBasketItem()->getPropertyCollection();
                            foreach ($collection_prop_item as $item_prop)
                            {
                                if($item_prop->getField('CODE') == 'PRODUCTS_REQUIRING_GTD'){
                                    $arItemValue['PRODUCTS_REQUIRING_GTD'] = "Y";
                                    $arItemValue['ORDER_ID'] = $orderId;
                                }
                            }
                            //////////

                            $arItems[] = $arItemValue;
                        }
                    }
                }

                foreach ($items as &$item) {
                    if ($item['TEXT'] == GetMessage("OZON_MAXYSS_CONTEXT_MENU")) {
                        $item["MENU"][] = array("TEXT" => GetMessage("OZON_MAXYSS_BUTTON_LABEL_ORDER") , "TITLE" => GetMessage("OZON_MAXYSS_BUTTON_LABEL_ORDER"), "LINK" => "javascript:print_label_ozon('" . $orderId . "')");
                        if($quantity > 1)
                            $item["MENU"][] = array("TEXT" => GetMessage("MAXYSS_OZON_SHIP_ORDER") , "TITLE" => GetMessage("MAXYSS_OZON_SHIP_ORDER"), "LINK" => "javascript:order_to_ship_add('" . $orderId . "', ".CUtil::PhpToJSObject($arItems).")");
                        else {
                            $item["MENU"][] = array("TEXT" => GetMessage("MAXYSS_OZON_SHIP_ORDER"), "TITLE" => GetMessage("MAXYSS_OZON_SHIP_ORDER"), "LINK" => "javascript:order_to_ship('" . $orderId . "', ".CUtil::PhpToJSObject($arItems).")");
                        }
                    }
                }
            }
        }

        return $items;
    }

    public static function buttonPackageLabelOzonDetail(){

        $arCabinet = CMaxyssOzon::getOptions(false, array('OZON_ID', 'ACTIVE_ORDER_ON'));
        $flag_button_on = false;

        foreach ($arCabinet as $key=>$ozon_id) {
            if ($ozon_id["ACTIVE_ORDER_ON"] == "Y") $flag_button_on = true;
        }

        if($flag_button_on) {
            if (strpos($GLOBALS["APPLICATION"]->GetCurPage(), 'shop/orders/details/') > 0) {
                $ids = explode('/', $GLOBALS["APPLICATION"]->GetCurPage());
                $orderId = $ids[4];
                if(intval($orderId) > 0) {
                    CJSCore::Init(array('maxyss_ozon'));

                    $order = Order::load($orderId);

                    if(is_object($order)) {
                        $collection = $order->getShipmentCollection()->getNotSystemItems();
                        foreach ($collection as $shipment) {
                            $collectionItems = $shipment->getShipmentItemCollection();
                            $quantity = 0;
                            $arItems = array();
                            foreach ($collectionItems as $shipmentItem) {
                                $arItemValue = $shipmentItem->getBasketItem()->getFields()->getValues();
                                $arItemValue['DIMENSIONS'] = unserialize($arItemValue['DIMENSIONS']);
                                $arItemValue['QUANTITY'] = intval($arItemValue['QUANTITY']);
                                $quantity += intval($arItemValue['QUANTITY']);

                                //////////
                                $collection_prop_item = $shipmentItem->getBasketItem()->getPropertyCollection();
                                foreach ($collection_prop_item as $item_prop)
                                {
                                    if($item_prop->getField('CODE') == 'PRODUCTS_REQUIRING_GTD'){
                                        $arItemValue['PRODUCTS_REQUIRING_GTD'] = "Y";
                                        $arItemValue['ORDER_ID'] = $orderId;
                                    }
                                }
                                //////////

                                $arItems[] = $arItemValue;
                            }
                        }

                        if($quantity > 1) $o_ship_fun = "order_to_ship_add(".$orderId.", ".CUtil::PhpToJSObject($arItems).")";
                        else $o_ship_fun = "order_to_ship(".$orderId.", ".CUtil::PhpToJSObject($arItems).")";


                    }
                }
                ob_start();
                ?>
                <script type="text/javascript">
                    BX.addCustomEvent("onAjaxSuccessFinish", function (params) {

                        var ozonButton = new BX.UI.Button({
                            id: "ozonButton",
                            text: "OZON",
                            noCaps: true,
                            round: false,
                            props: {
                                id: "ozonButtonId"
                            },
                            menu: {
                                items: [
                                    {
                                        text: BX.message('MAXYSS_OZON_SHIP_ORDER'),
                                        onclick: function(event, item) {
                                            <?echo $o_ship_fun;?>
                                        }
                                    },
                                    { delimiter: true },
                                    {
                                        text: BX.message('OZON_MAXYSS_BUTTON_LABEL_ORDER'),
                                        onclick: function(event, item) {
                                            item.getMenuWindow().close();
                                            print_label_ozon(<?=$orderId?> );

                                        }
                                    }
                                ],
                                closeByEsc: true,
                                offsetTop: 5,
                            },
                            size: BX.UI.Button.Size.MEDIUM,
                            color: BX.UI.Button.Color.PRIMARY,
                            tag: BX.UI.Button.Tag.BUTTON,
                            state: BX.UI.Button.State
                        });
                        var container = document.getElementById("toolbar_order_details_<?=$orderId?>");
                        if(document.getElementById("ozonButtonId") === null)
                            ozonButton.renderTo(container);

                    });
                </script>
                <?
                $sContent = ob_get_clean();
                $GLOBALS['APPLICATION']->AddHeadString($sContent, true);
            }
        }

    }

    public static function get_lable($order_ids = array()){
        if(is_array($order_ids) && count($order_ids)>0){
            $arOptions = CMaxyssOzon::getOptions(false, array('OZON_ID', 'PERSON_TYPE'));
            $arPropCodeIds = array();
            foreach ($arOptions as $key=>$options) {

                $person_type = $options["PERSON_TYPE"];
                $prop_ozon_code = Option::get(MAXYSS_MODULE_NAME, "PROPERTY_ORDER_OZON", "");
                $db_props = CSaleOrderProps::GetList(
                    array("SORT" => "ASC"),
                    array(
                        "CODE" => $prop_ozon_code,
                        "PERSON_TYPE_ID" => $person_type,
                    ),
                    false,
                    false,
                    array()
                );
                if ($props = $db_props->Fetch()) {
//                    $prop_ozon_code_id = $props['ID'];
                    $arPropCodeIds[$key] = $props['ID'];
                }
            }
            // find order to Bitrix
            $arFilterOrder = array (
                'ID' => $order_ids,
                "!PROPERTY_VAL_BY_CODE_".$prop_ozon_code => false,
            );
            $rsOrders = \CSaleOrder::GetList(
                array('ID' => 'ASC'),
                $arFilterOrder
            );

            $arShip = array();
            $ozon_id = '';
            $default_lid = '';
            $prop_ozon_code_id = '';
            while($arOrder = $rsOrders->Fetch())
            {
                $order = Order::load($arOrder['ID']);
                $lid = $order->getSiteId();

                if($ozon_id == '') {
                    $ozon_id = $ClientId = $arOptions[$lid]["OZON_ID"];
                    $ApiKey = CMaxyssOzon::GetApiKey($ClientId);
                    $default_lid = $lid;
                    $prop_ozon_code_id = $arPropCodeIds[$default_lid];
                }

                if($ozon_id != '' && $default_lid != '' && $lid == $default_lid) {
                    $propertyCollection = $order->getPropertyCollection();
                    $somePropValue = $propertyCollection->getItemByOrderPropertyId($prop_ozon_code_id);
                    $ship_number = $somePropValue->getValue();
                    if ($ship_number != '')
                        $arShip[] = $ship_number;
                }
            }
            if(!empty($arShip)) {
                $data_string = array(
                    'posting_number' => $arShip
                );
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                $pdf = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/package-label");
                return $pdf;
            }else{
                echo json_encode(array('error' => 'No order selected!'));
            }
        }
        else
        {
            echo json_encode(array('error' => 'No order selected!'));
        }
    }

    static function GetDocuments($ozon_id, $warehouse_id){
        $data_string = array('delivery_method_id'=>$warehouse_id);
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $ClientId = $ozon_id;
        $ApiKey = CMaxyssOzon::GetApiKey($ozon_id);
        $task = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/act/create");
        return $task;
    }

    static function CheckDocuments($task_id, $ozon_id){
        $ClientId = $ozon_id;
        $ApiKey = CMaxyssOzon::GetApiKey($ozon_id);

        $data_string = array('id'=>$task_id);
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $task_status = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/act/check-status");
        return $task_status;
    }
    static function CheckDocumentsDigital($task_id, $ozon_id){
        $ClientId = $ozon_id;
        $ApiKey = CMaxyssOzon::GetApiKey($ozon_id);

        $data_string = array('id'=>$task_id);
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $task_status = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/digital/act/check-status");
        return $task_status;
    }
    static function GetDigitalDoc($task_id, $ozon_id){
        $res = array();
        $ClientId = $ozon_id;
        $ApiKey = CMaxyssOzon::GetApiKey($ozon_id);

        $data_string = array('id'=>$task_id);
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $pdf = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/act/get-pdf");
            $res[] = $pdf['success'];

        $data_string = array('id'=>$task_id, 'doc_type'=>'act_of_acceptance');
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $act_of_acceptance = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/digital/act/get-pdf");
        if(is_array($act_of_acceptance) && $act_of_acceptance['success'])
            $res[] = $act_of_acceptance['success'];

        $data_string = array('id'=>$task_id, 'doc_type'=>'act_of_mismatch');
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $act_of_mismatch = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/digital/act/get-pdf");
        if(is_array($act_of_mismatch) && $act_of_mismatch['success'])
            $res[] = $act_of_mismatch['success'];

        $data_string = array('id'=>$task_id, 'doc_type'=>'act_of_excess');
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $act_od_excess = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/digital/act/get-pdf");
        if(is_array($act_od_excess) && $act_od_excess['success'])
            $res[] = $act_od_excess['success'];

        return $res;
    }
    static function GetDocPdf($task_id, $ozon_id){
        $ClientId = $ozon_id;
        $ApiKey = CMaxyssOzon::GetApiKey($ozon_id);

        $data_string = array('id'=>$task_id);
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $pdf = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/posting/fbs/act/get-pdf");
        return $pdf;
    }

    public static function Order_ship($order_pak = 0, $pakages = array()){

        if($order_pak > 0) {
            $order = Order::load($order_pak);
            $lid = $order->getSiteId();
            $arOptions = CMaxyssOzon::getOptions($lid, array('OZON_ID', 'PERSON_TYPE', "ARTICLE", "AWAITING_DELIVER", "IBLOCK_ID"));

            $ClientId = $arOptions[$lid]["OZON_ID"];
            $ApiKey = CMaxyssOzon::GetApiKey($ClientId);

            $prop_flag = '';
            if(strlen($arOptions[$lid]["ARTICLE"]) > 0) $prop_flag = 'PROPERTY_';
            $person_type = $arOptions[$lid]["PERSON_TYPE"];

            $prop_ozon_code = Option::get(MAXYSS_MODULE_NAME, "PROPERTY_ORDER_OZON", "");
            $prop_ozon_code_flag = false;
            $db_props = CSaleOrderProps::GetList(
                array("SORT" => "ASC"),
                array(
                    "CODE" => $prop_ozon_code,
                    "PERSON_TYPE_ID" => $person_type,
                ),
                false,
                false,
                array()
            );

            if ($props = $db_props->Fetch()) {
                $prop_ozon_code_flag = true;
                $prop_ozon_code_id = $props['ID'];

                // ����� �����������
                $propertyCollection = $order->getPropertyCollection();
                $somePropValue = $propertyCollection->getItemByOrderPropertyId($prop_ozon_code_id);
                $posting_number = $somePropValue->getValue();

                // ������
                if($posting_number)
                {
                    $basket = $order->getBasket();
                    foreach ($basket as $basketItem) {
                        $product[$basketItem->getProductId()]['id'] = $basketItem->getProductId();
                        $product[$basketItem->getProductId()]['quantity'] = $basketItem->getQuantity();
                        $product_ids[$basketItem->getField('ID')] = $basketItem->getProductId();

                        $collection = $basketItem->getPropertyCollection();
                        foreach ($collection as $item_property)
                        {
                            if( $item_property->getField('CODE') == "OZON_PRODUCTS_GTD"){
                                $product[$basketItem->getProductId()]['OZON_PRODUCTS_GTD'] = $item_property->getField('VALUE');
                                $product_gtd[$basketItem->getField('ID')] = $item_property->getField('VALUE');
                            }
                        }
                    }

                    // �������� ������ �� ��������
                    $arFilterProd = array("ID" => $product_ids/*, "IBLOCK_ID"=>$arOptions[$lid]["IBLOCK_ID"]*/);
                    $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "IBLOCK_ID", 'CATALOG_XML_ID');
                    if($prop_flag !='')
                        $arSelect[] = $prop_flag.$arOptions[$lid]["ARTICLE"];

                    $res = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);
                    while($ob = $res->GetNextElement())
                    {
                        $arFields = $ob->GetFields();
                        if($prop_flag !='')
                            $offer_id = $arFields['PROPERTY_'.$arOptions[$lid]["ARTICLE"].'_VALUE'];
                        else
                            $offer_id = $arFields['ID'];

                        $sku_ozon = self::GetProductInfo($offer_id, $ClientId, $ApiKey);

                        if($sources = $sku_ozon["sources"]){
                            foreach ($sources as $source){
                                if($source['source'] == 'fbs') $items[array_search($arFields['ID'], $product_ids)]["product_id"] = $source["sku"];
                            }
                        }
                        $items[array_search($arFields['ID'], $product_ids)]["quantity"] = strval($product[$arFields['ID']]['quantity']);
                    }

                    // �������� ���������� �������  �� ������

                    $ShipmentCollection = $order->getShipmentCollection();
                    foreach ($ShipmentCollection as $shipment)
                    {
                        if ($shipment->isSystem())
                            continue;
                        $ShipmentItemCollection = $shipment->getShipmentItemCollection();
                        foreach ($ShipmentItemCollection as $shipmentItem)
                        {
                            if(isset($product_gtd[$shipmentItem->getField('BASKET_ID')]) && $product_gtd[$shipmentItem->getField('BASKET_ID')] != ''){
                                $items[$shipmentItem->getField("BASKET_ID")]['gtd'] = $product_gtd[$shipmentItem->getField('BASKET_ID')];
                            }
                            $ShipmentItemStore = $shipmentItem->getShipmentItemStoreCollection();
                            foreach ($ShipmentItemStore as $ShipmentItemStoreitem)
                            {
                                $marking_code = $ShipmentItemStoreitem->getField('MARKING_CODE');//����� �������� ������������� ���.
                                $items[$ShipmentItemStoreitem->getField("BASKET_ID")]['mandatory_mark'][] = strval($marking_code);
                            }
                        }
                    }

                    $arrPakages = array();
                    if(!empty($pakages)){
                        foreach ($pakages as $pak){
                            $arPakage = array();
                            if(!empty($pak['items'])) {
                                foreach ($pak['items'] as $i) {
                                    $arPakage[$i['ID']] = array(
                                        'product_id' => $items[$i['ID']]['product_id'],
                                        'quantity' => $i['QUANTITY'],
                                    );
                                    if(!empty($items[$i['ID']]['mandatory_mark'])){
                                        $items[$i['ID']]['mandatory_mark'] = array_values($items[$i['ID']]['mandatory_mark']);
                                        $exemplar_info = array();
                                        for($mark = 0; $mark < $i['QUANTITY']; $mark++){
//                                            $arPakage[$i['ID']]['mandatory_mark'][$mark] = $items[$i['ID']]['mandatory_mark'][$mark];
                                            $exemplar_info[] = array("gtd" => "", "is_gtd_absent" => true, "mandatory_mark" => strval($items[$i['ID']]['mandatory_mark'][$mark]));
                                            unset($items[$i['ID']]['mandatory_mark'][$mark]);
                                        }
                                        $arPakage[$i['ID']]['exemplar_info']=$exemplar_info;
                                    }
                                }
                            }

                            $eventLog = new \CEventLog;
                            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'arPakage', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $order_pak, "DESCRIPTION" => serialize($arPakage)));
                            $arrPakages[]['products'] = array_values($arPakage);
                        }
                    }

                    if(!empty($arrPakages)){
                        $order_post = array(
                            "packages" => $arrPakages,
                            "posting_number" => $posting_number
                        );
                    }else {
                        $arPak = array();
                        foreach ($items as $key_item => $item){
                            $arPak[$key_item] = array(
                                'product_id' => $item['product_id'],
                                'quantity' => $item['quantity'],
                            );
                            if(isset($item['mandatory_mark']) || isset($item['gtd'])) {
                                $exemplar_info = array();
                                if (isset($item['mandatory_mark']) && !empty($item['mandatory_mark'])) {
                                    foreach ($item['mandatory_mark'] as $marks) {
                                        $exemplar_info[] = array(
                                            "gtd" => (isset($item['gtd']) && $item['gtd'] != '')? $item['gtd'] : "",
                                            "is_gtd_absent" => (isset($item['gtd']) && $item['gtd'] != '')? false : true,
                                            "mandatory_mark" => strval($marks)
                                        );
                                    }
                                }
                                else
                                {
                                    $exemplar_info[] = array(
                                        "gtd" => (isset($item['gtd']) && $item['gtd'] != '')? $item['gtd'] : "",
                                        "is_gtd_absent" => (isset($item['gtd']) && $item['gtd'] != '')? false : true,
                                    );
                                }
                                $arPak[$key_item]['exemplar_info'] = $exemplar_info;
                            }
                        }
                        $order_post = array(
                            "packages" => array(
                                array(
                                    "products" => array_values($arPak)
                                )
                            ),
                            "posting_number" => $posting_number
                        );
                    }

                    $posting_number_ozon = array();
                    $data_string = $order_post;
                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                    $posting_number_ozon = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v3/posting/fbs/ship");

                    if($posting_number_ozon['error']){
                        $result["ERROR"][] = $order_pak.' - '.$posting_number_ozon['error'];
                        return $result;
                    }
                    elseif($posting_number == $posting_number_ozon[0])
                    {
                        if(count($posting_number_ozon)>1){
                            unset($posting_number_ozon[0]);
                            $result["SUCCESS"][] = $order_pak.' - '.GetMessage("MAXYSS_OZON_POSTING_ADD_SUCCESS", array("#NUMS#"=>implode(', ', $posting_number_ozon)));
                        }else{
                            $result["SUCCESS"][] = $order_pak.' - '.GetMessage("MAXYSS_OZON_POSTING_SUCCESS");
                        }

                        $arSettings = array();
                        $arOptions = CMaxyssOzon::getOptions($lid);
                        $arSettings = $arOptions[$lid];
                        $arSettings['SITE'] = $lid;
                        $tpl_integration_type = 'ozon';
                        $db_props = CSaleOrderProps::GetList(
                            array("SORT" => "ASC"),
                            array(
                                "CODE" => 'tpl_integration_type',
                                "PERSON_TYPE_ID" => $person_type,
                            ),
                            false,
                            false,
                            array()
                        );

                        if ($props = $db_props->Fetch()) {
                            $somePropValue = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                            $tpl_integration_type = $somePropValue->getValue();
                        }

                        $old_status_order = $order->getField("STATUS_ID");
                        CMaxyssOzonAgent::changeStatusOrder(array("tpl_integration_type" => $tpl_integration_type, 'status' => 'awaiting_deliver'), $arSettings, $order);
                        if($order->getField("STATUS_ID") == '') $order->setField("STATUS_ID", 'N');
                        if($old_status_order != $order->getField("STATUS_ID")){
                            if($result_order = $order->save()){
                                if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'SAVE_ORDER', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $order_pak, "DESCRIPTION" => serialize($result_order)));
                                }
                            }
                        }

                        return $result;
                    }else{
                        $result["ERROR"][] = $order_pak.' - '.GetMessage("MAXYSS_OZON_POSTING_ERROR");
                        return $result;
                    }

                }else{
                    $result["ERROR"][] = $order_pak.' - '.GetMessage("MAXYSS_OZON_POSTING_NOT_OZON");
                    return $result;
                }

            }else{
                if(\Bitrix\Main\Config\Option::get('maxyss.ozon', "LOG_ON",  "N") == "Y") {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'SAVE_ORDER', "MODULE_ID" => 'maxyss.ozon', "ITEM_ID" => $order_pak, "DESCRIPTION" => 'No order property'));
                }
                $result["ERROR"][] = 'No order property';
                return $result;
            }
        }else{
            $result["ERROR"][] = 'No order';
            return $result;
        }
    }

    static function GetProductInfo($offer_id, $ClientId, $ApiKey){
        $data_string = array(
            "offer_id" => $offer_id, // po identifikatoru v sisteme prodavtca, to est` id s sai`ta
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $sku = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/product/info");
        return $sku;

    }
    static function GetProductsInfo($offer_id = array(), $ClientId, $ApiKey){
        $data_string = array(
            "offer_id" => $offer_id, // po identifikatoru v sisteme prodavtca, to est` id s sai`ta
//            "product_id" => array(),
//            "sku" => array(),
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        $sku = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/product/info/list");
        return $sku;

    }
}

class CMaxyssOzonStockUpdate{
    public static function updateStock( $event ) {
        $item = $event->getParameters();
        $arLid = array();
        $ar_tovar = CCatalogProduct::GetByID($item['id']);
        if(isset($item['fields']['QUANTITY']) && $ar_tovar['QUANTITY'] != $item['fields']['QUANTITY']) {
            $flag_upd = false;
            $iblock_id = CIBlockElement::GetIBlockByID($item['id']);
            $mxResult = CCatalogSKU::GetInfoByOfferIBlock(
                $iblock_id
            );

            $lid = '';
            $ClientId = '';
            $ApiKey = '';

            $arOptions = CMaxyssOzon::getOptions();
            foreach ($arOptions as $key=>$option){


                $arIblockIds[$key][$option["IBLOCK_ID"]] = array(
                    "SITE_ID" => $key,
                    "IBLOCK_ID" => $option["IBLOCK_ID"],
                    "FILTER_PROP_ID" => $option["FILTER_PROP_ID"],
                    "ARTICLE" => $option["ARTICLE"],
                    "FILTER_PROP" => $option["FILTER_PROP"],
                    "CUSTOM_FILTER" => $option["CUSTOM_FILTER"]
                );
            }

            if (is_array($mxResult))
            {  // ��� ��

                foreach ($arIblockIds as $key_site =>$site) {
                    $arIblockId = $site;

                    if (isset($arIblockId[$mxResult['PRODUCT_IBLOCK_ID']]["IBLOCK_ID"]) && $mxResult['PRODUCT_IBLOCK_ID'] == $arIblockId[$mxResult['PRODUCT_IBLOCK_ID']]["IBLOCK_ID"]) {

                        $iblock_id_tovar = $arIblockId[$mxResult['PRODUCT_IBLOCK_ID']]["IBLOCK_ID"];

                        $lid = $arIblockId[$iblock_id_tovar]["SITE_ID"];
//                        $arLid[$lid]=array(
//                                'lid'=>$lid,
//                                'ClientId'=>$arOptions[$lid]['OZON_ID'],
//                                'ApiKey'=>$arOptions[$lid]['OZON_API_KEY'],
//                        );

                        $tovarResult = CCatalogSku::GetProductInfo(
                            $item['id'] // id TP
                        );
                        if (is_array($tovarResult)) {

                            $arFilter = array("ID" => $tovarResult['ID'], "IBLOCK_ID"=>$iblock_id_tovar);
                            $arCustomFilter = array();
                            if($arOptions[$lid]["CUSTOM_FILTER"]) {
                                $filter_custom = new FilterCustomOzon();
                                $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arOptions[$lid]["CUSTOM_FILTER"])), array());
                            }
                            elseif ($arOptions[$lid]['FILTER_PROP'] != '' && $arOptions[$lid]['FILTER_PROP_ID'] != '')
                                $arFilter['PROPERTY_' . $arOptions[$lid]['FILTER_PROP']] = $arOptions[$lid]['FILTER_PROP_ID'];

                            if(!empty($arCustomFilter)){
                                $arFilter[] = $arCustomFilter;
                            }
                            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, array("ID", "PROPERTY_CAT_OZON", "IBLOCK_ID",'IBLOCK_SECTION_ID'));
                            if ($ob = $res->GetNextElement()) {
                                $arFields = $ob->GetFields();
                                $arFilterCatOzon = Array("ID" => $item['id'], 'IBLOCK_ID'=>$iblock_id, '!PROPERTY_CAT_OZON' => false);
                                $resCatOzon = CIBlockElement::GetList(Array(), $arFilterCatOzon, false, false, array('IBLOCK_ID', 'ID', 'PROPERTY_CAT_OZON'));
                                $obCatOzon = $resCatOzon->GetNextElement()->fields['PROPERTY_CAT_OZON_VALUE'];
                                $sec_attr = array();
                                if (!$obCatOzon || strlen($arFields["PROPERTY_CAT_OZON_VALUE"]) <=0) {
                                    $sec_attr = CMaxyssOzonAgent::get_section_attr( $arFields['IBLOCK_SECTION_ID'], $arOptions[$lid]);
                                }
                                if ( $obCatOzon || strlen($arFields["PROPERTY_CAT_OZON_VALUE"]) > 5 || !empty($sec_attr)) {
                                    $flag_upd = true;
                                    $arLid[$lid]=array(
                                        'lid'=>$lid,
                                        'ClientId'=>$arOptions[$lid]['OZON_ID'],
                                        'ApiKey'=>$arOptions[$lid]['OZON_API_KEY'],
                                    );
                                }
                            }
                        }
                    }
                }
            }
            else
            { // ��� �����
                foreach ($arIblockIds as $key_site =>$site) {
                    $arIblockId = $site;
                    if (isset($arIblockId[$iblock_id]["IBLOCK_ID"]) && $iblock_id == $arIblockId[$iblock_id]["IBLOCK_ID"]) {

                        $lid = $arIblockId[$iblock_id]["SITE_ID"];
                        $arFilter = array("ID" => $item['id'], /*'!PROPERTY_CAT_OZON' => false,*/ "IBLOCK_ID"=>$iblock_id);
                        $arCustomFilter = array();
                        if($arOptions[$lid]["CUSTOM_FILTER"]) {
                            $filter_custom = new FilterCustomOzon();
                            $arCustomFilter = $filter_custom->parseCondition(Json::decode(htmlspecialchars_decode($arOptions[$lid]["CUSTOM_FILTER"])), array());
                        }
                        elseif ($arOptions[$lid]['FILTER_PROP'] != '' && $arOptions[$lid]['FILTER_PROP_ID'] != '')
                            $arFilter['PROPERTY_' . $arOptions[$lid]['FILTER_PROP']] = $arOptions[$lid]['FILTER_PROP_ID'];

                        if(!empty($arCustomFilter)){
                            $arFilter[] = $arCustomFilter;
                        }
                        if (!CCatalogSKU::IsExistOffers($item['id'], $iblock_id)) {
                            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, array("ID", "PROPERTY_CAT_OZON", "IBLOCK_ID",'IBLOCK_SECTION_ID'));

                            $sec_attr = array();
                            if ($ob = $res->GetNextElement()) {
                                $arFields = $ob->GetFields();
                                if(strlen($arFields['PROPERTY_CAT_OZON_VALUE']) <=0)
                                    $sec_attr = CMaxyssOzonAgent::get_section_attr( $arFields['IBLOCK_SECTION_ID'], $arOptions[$lid]);
                                if(strlen($arFields['PROPERTY_CAT_OZON_VALUE']) > 5 || !empty($sec_attr)) {
                                    $flag_upd = true;
                                    $arLid[$lid] = array(
                                        'lid' => $lid,
                                        'ClientId' => $arOptions[$lid]['OZON_ID'],
                                        'ApiKey' => $arOptions[$lid]['OZON_API_KEY'],
                                    );
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($arLid) ) {

                foreach ($arLid as $lids) {

                    $lid = $lids['lid'];
                    $ClientId = $lids['ClientId'];
                    $ApiKey = $lids['ApiKey'];

                    if($ClientId !='' && $ApiKey !='') {

                        $article = '';
                        $article = $arOptions[$lid]["ARTICLE"];
                        if ($article == '') {
                            $offer_id = $item['id'];
                        } else {
                            $res = CIBlockElement::GetProperty($iblock_id, $item['id'], "sort", "asc", array("CODE" => $article));
                            if ($ob = $res->GetNext()) {
                                $offer_id = $ob['VALUE'];
                            }
                        }
                        if ($offer_id != '') {
                            if (VERSION_OZON_3) { // V3
                                $arVHstock = CMaxyssOzonAgent::arStock(array('QUANTITY'=>$item['fields']['QUANTITY']), $item['id'], $arOptions[$lid]);
                                $items = array();
                                $arDeactivateWarehouses = unserialize($arOptions[$lid]["DEACTIVATE"]);
                                $arLimitWarehouses = unserialize($arOptions[$lid]["LIMIT"]);

                                $arLimitWarehousesPrice = unserialize($arOptions[$lid]["LIMIT_PRICE"]);

                                $arLimitWarehousesWeightMin = unserialize($arOptions[$lid]["WEIGHT_MIN"]);
                                $arLimitWarehousesWeightMax = unserialize($arOptions[$lid]["WEIGHT_MAX"]);

                                $price = CMaxyssOzonAgent::get_price($arOptions[$lid]['PRICE_TYPE'], $arOptions[$lid]['PRICE_PROP'], $arOptions[$lid]['PRICE_TYPE_PROP'], $arOptions[$lid]['PRICE_TYPE_NO_DISCOUNT'], $item['id'], $lid, $arOptions[$lid]["PRICE_TYPE_FORMULA"], $arOptions[$lid]["PRICE_TYPE_FORMULA_ACTION"]);

                                foreach ($arVHstock as $warehouse => $amount) {
                                    $arItemParam = array($offer_id=>array('price'=>$price, 'weight'=>$ar_tovar['WEIGHT']));
                                    $stock_res = CMaxyssOzonAgent::stock_limits($offer_id, $amount, $warehouse, $arItemParam, $arOptions[$lid]);
                                    if($arDeactivateWarehouses[$warehouse] != 'Y') {
                                        $arStock = array(
                                            "offer_id" => strval($offer_id),
                                            "stock" => ($stock_res > 0) ? intval($stock_res) : 0,
                                            "warehouse_id" => $warehouse
                                        );
                                        $items[] = $arStock;
                                    }
                                }
                            }
                        }
                        $filename = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/" . $lid . "_log_user_" . date('N') . ".txt";
                        if(is_array($items) && !empty($items))
                            CMaxyssOzonAgent::update_stock($items, $ClientId, $ApiKey, $base_url = OZON_BASE_URL, $filename, $lid);
                    }
                }
            }
        }
    }
}
?>
