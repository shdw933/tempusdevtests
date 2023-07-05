<?
use \Bitrix\Main\Config\Option;
use \Bitrix\Highloadblock as HL,
    Bitrix\Main\Entity;

class CAddinMaxyssWB{
    public static function getObjectCharacteristics($object='', $cabinet = 'DEFAULT')
    {
        $result = array();
        $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);



        if($object!= ''){

            if(LANG_CHARSET == 'windows-1251') $object = CMaxyssWb::deepIconv($object, 'windows-1251', 'UTF-8//IGNORE');
            $base_url = WB_BASE_URL;
            $path = "/content/v1/object/characteristics/".rawurlencode($object);

//            $data_string = array('name'=>$object);
//            $data_string = \Bitrix\Main\Web\Json::encode($data_string);

            $api = new RestClient([
                'base_url' => $base_url,
//                'headers' => array(
//                    'Authorization: ' . $Authorization,
////                    'Content-Type: application/json',
//                ),
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
//                    CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
//                        'Content-Length: ' . strlen($data_string),
                        'Authorization: ' . $Authorization,
//                    'X-Supplier-ID: ' . Option::get('maxyss.wb', "UUID", "")

                    )
                )
            ]);


            $str_result = $api->post($path, []);
            echo '<pre>', print_r($str_result), '</pre>' ;
        }
        return $result;

    }
    public static function getObjectCharacteristicsFilter($object='', $cabinet = 'DEFAULT')
    {
        $result = '';
        $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);


        if($object!= ''){
//            if(LANG_CHARSET == 'windows-1251') $object = CMaxyssWb::deepIconv($object, 'windows-1251', 'UTF-8//IGNORE');
            $base_url = WB_BASE_URL;
            $path = "/content/v1/object/characteristics/list/filter?name=".rawurlencode($object);
            $api = new RestClient([
                'base_url' => $base_url,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
//                    CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,

                    )
                )
            ]);


            $str_result = $api->post($path, []);
            if ($str_result->info->http_code == 200) {
//                $result = \Bitrix\Main\Web\Json::decode($str_result->response);
                $result = $str_result->response;
            }
        }
        return $result;

    }
    public static function getObjectAll($cabinet = 'DEFAULT')
    {
        $result = array();
        $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);
        $data_string = '';

        if($Authorization!= ''){
            $base_url = WB_BASE_URL;
            $path = "/content/v1/object/parent/all";

            $api = new RestClient([
                'base_url' => $base_url,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data_string),
                        'Authorization: ' . $Authorization,
//                    'X-Supplier-ID: ' . Option::get('maxyss.wb', "UUID", "")

                    )
                )
            ]);


            $str_result = $api->post($path, []);
//                echo '<pre>', print_r($str_result), '</pre>' ;
            if ($str_result->info->http_code == 200) {
                $result = \Bitrix\Main\Web\Json::decode($str_result->response);
            }
        }
        return $result;

    }
    public static function getObjectFilter($name, $cabinet = 'DEFAULT')
    {
        $result = array();
        $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);

        if($Authorization!= '' && $name !=''){

            //if(LANG_CHARSET == 'windows-1251') $name = CMaxyssWb::deepIconv($name, 'windows-1251', 'UTF-8//IGNORE');
            $base_url = WB_BASE_URL;
            $path = "/content/v1/object/all?name=".rawurlencode($name);
            $api = new RestClient([
                'base_url' => $base_url,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
//                            CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,

                    )
                )
            ]);


            $str_result = $api->post($path, []);
            if ($str_result->info->http_code == 200) {
                $result = \Bitrix\Main\Web\Json::decode($str_result->response);
            }
        }
//        echo '<pre>', print_r($result), '</pre>' ;
        return $result;

    }
    public static function getDirectory($dictionari, $pattern='', $option='', $cabinet = 'DEFAULT')
    {
        $result = '';
        $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);

        if($Authorization!= '' && $dictionari !=''){

//            if(LANG_CHARSET == 'windows-1251') $pattern_d = CMaxyssWb::deepIconv($pattern, 'windows-1251', 'UTF-8//IGNORE');
//            if(LANG_CHARSET == 'windows-1251') $option_d = CMaxyssWb::deepIconv($option, 'windows-1251', 'UTF-8//IGNORE');
            $base_url = WB_BASE_URL;

            if($dictionari == '/content/v1/directory/tnved')
                $path = $dictionari."?tnvedsLike=".rawurlencode($pattern)."&objectName=".rawurlencode($option);
            elseif(
                    $dictionari == '/content/v1/directory/consists' ||
                    $dictionari == '/content/v1/directory/brands'||
                    $dictionari == '/content/v1/directory/collections'||
                    $dictionari == '/content/v1/directory/contents'
                )
                $path = $dictionari."?top=50&pattern=".rawurlencode($pattern);
            else
                $path = $dictionari;
            $api = new RestClient([
                'base_url' => $base_url,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
//                            CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,

                    )
                )
            ]);

            $str_result = $api->post($path, []);
            if ($str_result->info->http_code == 200) {
                $result = \Bitrix\Main\Web\Json::decode($str_result->response);
                if(!empty($result['data'])){
                    foreach ($result['data'] as &$data){
                        if(!isset($data['name']) && !isset($data['tnvedName'])){
                            $new_data['name'] = $data;
                            $data = $new_data;
                        }
                    }
                }
                if($pattern !='' && !empty($result['data']) && $dictionari != '/content/v1/directory/tnved'){

                    if(LANG_CHARSET == 'windows-1251') $pattern = CMaxyssWb::deepIconv($pattern, 'UTF-8', 'windows-1251//IGNORE');
                    $phpFilterArray = array_values(array_filter($result["data"], function ($value) use ($pattern) {
                        if(isset($value["name"])) {
                            return (strripos($value["name"], $pattern) !== false);
                        }
                        elseif(isset($value["tnvedName"])) {
                            return (strripos($value["tnvedName"], $pattern) !== false);
                        }
                        else {
                            return (strripos($value, $pattern) !== false);
                        }
                    }));
                    $result['data'] = $phpFilterArray;
                }
//                $result = $str_result->response;
            }
        }
        return \Bitrix\Main\Web\Json::encode($result);
    }
    public static function GetSyncAddin($addin_card = array(), $sinc_set=array(), $iblock_id=0, $char_all, $property){

        if(!empty($addin_card)){
            foreach ($addin_card as $ad){
                $addin[key($ad)] = $ad;
            }
        }
        if(!empty($char_all) && !empty($sinc_set) && $iblock_id > 0){
            foreach ($char_all as $char){
                if(array_key_exists($char['name'], $sinc_set) && !empty($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']) && !isset($addin[$char['name']])){
                    $val = array();
                    if(is_array($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'])){
                        if($char['maxCount'] > 1){
                            for($i = 0; $i = (intval($char['maxCount'])-1); $i++){
                                $val[] = ($char['charcType'] == 1)? strval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'][$i]) : floatval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'][$i]);
                            }
                        }
                        else
                        {
                            $val = ($char['charcType'] == 1)? strval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'][0]) : floatval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'][0]);
                        }
                    }
                    else
                    {
                        if ($property[$sinc_set[$char['name']][$iblock_id]]['PROPERTY_TYPE'] == 'L' || ($property[$sinc_set[$char['name']][$iblock_id]]['PROPERTY_TYPE'] == 'S' && empty($property[$sinc_set[$char['name']][$iblock_id]]['USER_TYPE_SETTINGS'])))
                        { // строка или число
                            if($char['maxCount'] > 1)
                                $val[] = ($char['charcType'] == 1)? strval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']) : floatval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']);
                            else
                                $val = ($char['charcType'] == 1)? strval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']) : floatval($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']);

                        }
                        elseif ($property[$sinc_set[$char['name']][$iblock_id]]['PROPERTY_TYPE'] == 'E'){
                            if($char['maxCount'] > 1) {
                                if(is_array($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'])){
                                    foreach ($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'] as $val_el_iblock){
                                        if($val_el_iblock > 0) {
                                            $bd_el_iblock = CIBlockElement::GetByID($val_el_iblock);
                                            if ($ar_el_iblock = $bd_el_iblock->GetNext())
                                                $val[] = ($char['charcType'] == 1) ? strval($ar_el_iblock['NAME']) : floatval($ar_el_iblock['NAME']);
                                        }
                                    }
                                }
                                elseif($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'] > 0)
                                {
                                    $bd_el_iblock = CIBlockElement::GetByID($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']);
                                    if ($ar_el_iblock = $bd_el_iblock->GetNext())
                                        $val[] = ($char['charcType'] == 1) ? strval($ar_el_iblock['NAME']) : floatval($ar_el_iblock['NAME']);
                                }
                            }
                            else
                            {
                                if(is_array($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']) && $property[$sinc_set[$char['name']][$iblock_id]]['VALUE'][0] > 0){
                                    $bd_el_iblock = CIBlockElement::GetByID($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'][0]);
                                    if ($ar_el_iblock = $bd_el_iblock->GetNext())
                                        $val = ($char['charcType'] == 1)? strval($ar_el_iblock['NAME']) : floatval($ar_el_iblock['NAME']);

                                }
                                elseif($property[$sinc_set[$char['name']][$iblock_id]]['VALUE'] > 0)
                                {
                                    $bd_el_iblock = CIBlockElement::GetByID($property[$sinc_set[$char['name']][$iblock_id]]['VALUE']);
                                    if ($ar_el_iblock = $bd_el_iblock->GetNext())
                                        $val = ($char['charcType'] == 1)? strval($ar_el_iblock['NAME']) : floatval($ar_el_iblock['NAME']);
                                }
                            }
                        }
                        elseif ($property[$sinc_set[$char['name']][$iblock_id]]['PROPERTY_TYPE'] == 'S' && !empty($property[$sinc_set[$char['name']][$iblock_id]]['USER_TYPE_SETTINGS']))
                        {

                            $hlblock = HL\HighloadBlockTable::getRow([
                                'filter' => [
                                    '=TABLE_NAME' => $property[$sinc_set[$char['name']][$iblock_id]]['USER_TYPE_SETTINGS']['TABLE_NAME']
                                ],
                            ]);

                            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                            $main_query = new Entity\Query($entity);
                            $main_query->setSelect(array('*'));
                            $main_query->setFilter(array('UF_XML_ID' => $property[$sinc_set[$char['name']][$iblock_id]]['VALUE']));
                            $result = $main_query->exec();
                            $result = new CDBResult($result);

                            if ($row = $result->Fetch()) {
                                if($char['maxCount'] > 1)
                                    $val[] = ($char['charcType'] == 1)? strval($row['UF_NAME']) : floatval($row['UF_NAME']);
                                else
                                    $val = ($char['charcType'] == 1)? strval($row['UF_NAME']) : floatval($row['UF_NAME']);
                            }

                        }
                    }
                    $addin[$char['name']] = array($char['name'] => $val);
                }
            }
        }
        return $addin;
    }
    public static function PrepareItemNewApiContent($id = 0, $cabinet = 'DEFAULT'){
        if(intval($id) <= 0){
            $item = '';
        }else{
            $arSettings = CMaxyssWb::settings_wb($cabinet);

            $lid = $arSettings['SITE'];

            $arSelect = Array("ID", "IBLOCK_ID", "NAME", "TAGS", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_PROP_MAXYSS_WB", "PROPERTY_PROP_MAXYSS_CARDID_WB", "PROPERTY_".$arSettings['DESCRIPTION']);
            if($arSettings['BRAND'] != '') $arSelect[] = "PROPERTY_".$arSettings['BRAND'];
            if($arSettings['SHKOD'] != '') $arSelect[] = "PROPERTY_".$arSettings['SHKOD'];
            if($arSettings['ARTICLE'] != '') $arSelect[] = "PROPERTY_".$arSettings['ARTICLE'];
            if($arSettings['ARTICLE_LINK'] != '') $arSelect[] = "PROPERTY_".$arSettings['ARTICLE_LINK'];
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

                    if(LANG_CHARSET == 'windows-1251') $addin_card = CMaxyssWb::deepIconv($addin_card);

                    $addin_card_ = CUtil::JsObjectToPhp($addin_card);


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

            //
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
                $params = array();
                foreach ($addin['params'] as $param){
                    if(isset($param['value'])){
                        if(count($addin['params'])>1)
                            $params[] = strval($param['value']);
                        else
                            $params = strval($param['value']);
                    }
                    elseif (isset($param['count'])) {
                        if(count($addin['params'])>1)
                            $params[] = floatval($param['count']);
                        else
                            $params = floatval($param['count']);
                    }
                }
                $addin_card[] = array($addin['type']=>$params);
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

            $addin_card[] = array(GetMessage('WB_MAXYSS_DESCRIPTION')=> TruncateText(str_replace('&nbsp;', ' ', htmlentities(HTMLToTxt($description, $arSettings['SERVER_NAME']))), 4997) );
            $addin_card[] = array( GetMessage('WB_MAXYSS_PREDMET')=>$object );

            if(!isset($addin_card[GetMessage('MAXYSS_WB_NAME_NAME')])) {
                $addin_card[] = array( GetMessage('MAXYSS_WB_NAME_NAME')=>  TruncateText($name, 57));
            }

//            $addin_card = array_values($addin_card);
//            $arTags = array();
//            if($arFields["TAGS"] !='') {
//                $arTags = explode(',', $arFields["TAGS"]);
//                if(!empty($arTags)) {
//                    foreach ($arTags as $tag) {
//                        $tags[] = array("value" => TruncateText(str_replace('&nbsp;', ' ', trim($tag)),47));
//                    }
//                    if (!empty($tags)) {
//                        $addin_card[] = array(
//                            'type' => GetMessage('WB_MAXYSS_KEYWORD'),
//                            'params' => $tags
//                        );
//                    }
//                }
//            }


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
            $article_dop = '';
//            if($arVarProp['colors']) {
//                $article_dop = '_' . str_replace(' ', '', $arVarProp['colors']);
//            }
//            else
//                $article_dop = '_0';

            if($land != ''){
                $addin_card[] = array( GetMessage('MAXYSS_WB_STRANA') => $land);
            }

            $item = array(
                "VendorCode" => ($arSettings['ARTICLE']=='')? $arFields['ID'] : $arFields["PROPERTY_".strtoupper($arSettings['ARTICLE'])."_VALUE"],
                "sizes" => array(
                    array(
                        "techSize" => '0',
                        "wbSize" => '',
                        "skus"=>array(strval($arFields["PROPERTY_".strtoupper($arSettings['SHKOD'])."_VALUE"])),
                        "price" => 0
                    ),
                ),
                "characteristics" => $addin_card,
            );
            if(is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]))
                $key_cabinet = array_search($cabinet, $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
            if($cabinet == "DEFAULT" && $key_cabinet===false && is_array($arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"])){
                $key_cabinet = array_search('', $arProps['PROP_MAXYSS_CARDID_WB']["DESCRIPTION"]);
            }
            if($key_cabinet !== false) {
//                if ($arProps['PROP_MAXYSS_CARDID_WB']['VALUE'][$key_cabinet] > 0) {
//                    $item['card']['nmID'] = intval($arProps['PROP_MAXYSS_CARDID_WB']['VALUE'][$key_cabinet]);
//                }
                if ($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet] > 0 && $arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet] > 0) {
                    $item['nmID'] = intval($arProps['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet]);
                    $item['sizes'][0]['chrtID'] = intval($arProps['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet]);
                }
            }

            if(array_key_exists('colors', $arVarProp) && !isset($item['characteristics'][GetMessage("WB_MAXYSS_COLOR_NOM")])){
                $item['characteristics'][] = array(GetMessage("WB_MAXYSS_COLOR_NOM")=>  $arVarProp['colors'] );
            }
            if(array_key_exists('wbsizes', $arVarProp) && $item["sizes"][0]["wbSize"] == 0){
                $item["sizes"][0]["wbSize"] = strval($arVarProp['wbsizes']);
            }
            if(array_key_exists('tech-sizes', $arVarProp) && $item["sizes"][0]["techSize"] == 0){
                $item['sizes'][0]['techSize'] = strval($arVarProp['tech-sizes']);
            }

            if(!isset($item['nmID'])) {
                $article_link = '';
                if ($arSettings['ARTICLE_LINK'] != '' && $arProps[$arSettings['ARTICLE_LINK']]['VALUE'] != '') {
                    $article_link = $arProps[$arSettings['ARTICLE_LINK']]['VALUE'];
                }
            }
        }
        if( $arSettings['LOG_ON'] == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "PrepareItemNewApiContent", "DESCRIPTION" => serialize($item) ));
        }
        return array('item'=>$item, 'img'=>$img, 'props' => $arVarProp, 'article_link' => $article_link, 'predmet'=>$object, 'ar_prop_element'=>$arProps);
    }

    public static function UploadCadrNewApiContent($items, $id_element, $auth){

        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "onUploadItem", array(&$items));
        $event->send();

        $res = array();
        if(isset($items['cards']))
            $data_string = $items;
        else
            $data_string = array($items);

        $data_string = \Bitrix\Main\Web\Json::encode($data_string);


        $bck = CMaxyssWb::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            if(isset($items['cards'])) {
                $path = "/content/v1/cards/upload/add";
            }
            else
            {
                $path = "/content/v1/cards/upload";
            }



            $api = new RestClient([
                'base_url' => WB_BASE_URL,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($data_string),
                        'Authorization: ' . $auth,
//                    'X-Supplier-ID: ' . Option::get('maxyss.wb', "UUID", "")

                    )
                )
            ]);


            $str_result = $api->post($path, []);
//            echo '<pre>', print_r($str_result), '</pre>' ;
            if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $auth) == "Y") {
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "UploadCadrNewApiContent", "DESCRIPTION" => serialize($str_result) ));
            }
            if ($str_result->info->http_code == 200) {
                $res = GetMessage("WB_MAXYSS_PRODUCT_UPLOAD");
            } elseif($str_result->info->http_code ==500) {
                $res = ' - HTTP/1.1 500 Internal Server Error';
            } else {
                if (strlen($str_result->response) > 0 && strpos($str_result->response, '{') !== false) {
                    $data_error = \Bitrix\Main\Web\Json::decode($str_result->response);
                    $res = $data_error['errorText'];
                } else {
                    $res = 'http_code ' . $str_result->info->http_code;
                }
            }

//            $arResult = \Bitrix\Main\Web\Json::decode($result);
//            if (!$arResult["error"]) {
//                $res = GetMessage("WB_MAXYSS_PRODUCT_UPLOAD");
//            } else {
//                if (isset($arResult["error"]["message"]) && $arResult["error"]["message"])
//                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["message"] . '.';
//                else
//                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"] . '. ' . GetMessage("WB_MAXYSS_ERROR_AJAX_TWO");
//            }
        }

        return $res;
    }

    public static function AddMediaFile($img=array(), $vendorCode, $auth, $count = 1){

        if(!empty($img)) {
            $c = $count;
            $result = '';
            if (LANG_CHARSET == 'windows-1251') $vendorCodeDecode = CMaxyssWb::deepIconv($vendorCode, 'windows-1251', 'UTF-8//IGNORE');
            else
                $vendorCodeDecode = $vendorCode;
            $result = 'Article ' . $vendorCode . ' ' . GetMessage("WB_MAXYSS_PHOTO_UPLOAD") . ' ';

            foreach ($img as $image) {
                $curl_file = CRestQueryWB::makeCurlFile($image);
                $api = new RestClient([
                    'base_url' => WB_BASE_URL,
                    'curl_options' => array(
                        CURLOPT_POST => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_POSTFIELDS => array('uploadfile' => $curl_file),
                        CURLOPT_RETURNTRANSFER => TRUE,
                        CURLOPT_HEADER => TRUE,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: multipart/form-data',
                            'Authorization: ' . $auth,
                            'X-Vendor-Code: ' . $vendorCodeDecode,
                            'X-Photo-Number: ' . intval($c)
                        )
                    )
                ]);


                $path = '/content/v1/media/file';
                $str_result = $api->post($path, []);
//            echo '<pre>', print_r($str_result), '</pre>' ;

                if ($str_result->info->http_code == 200) {
                    $c++;
                }
                 elseif ($str_result->info->http_code == 400) {
                     $data_error = \Bitrix\Main\Web\Json::decode($str_result->response);
                     $result = $data_error['errorText']. GetMessage('MAXYSS_WB_PHOTO_NO_UPLOAD_400');
                } elseif($str_result->info->http_code ==500) {
                    $result = ' - HTTP/1.1 500 Internal Server Error';
                } else {
                    if (strlen($str_result->response) > 0 && strpos($str_result->response, '{') !== false) {
                        $data_error = \Bitrix\Main\Web\Json::decode($str_result->response);
                        $result = $data_error['errorText'];
                    } else {
                        $result = 'http_code ' . $str_result->info->http_code;
                    }
                }
            }
        }else{
            $result = 'Article ' . $vendorCode . ' ' .'NO UPLOAD PHOTO';
        }
        return $result;
    }

    public static function UpdateCadrNewApiContent($item, $id_element, $auth){

        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "onUploadItem", array(&$item));
        $event->send();
        $res = array();
        $data_string = array($item);
        $arResult = array();
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $bck = CMaxyssWb::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $result = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/content/v1/cards/update", $auth);
            $arResult = \Bitrix\Main\Web\Json::decode($result);
            if (!$arResult["error"]) {
                $res = GetMessage("WB_MAXYSS_PRODUCT_UPLOAD");
            } else {
                if (isset($arResult["error"]["message"]) && $arResult["error"]["message"])
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"]["message"] . '.';
                else
                    $res = GetMessage("WB_MAXYSS_ERROR_AJAX") . ' - ' . $arResult["error"] . '. ' . GetMessage("WB_MAXYSS_ERROR_AJAX_TWO");
            }
        }

        return $res;
    }
    public static function  GetCardForArticle($article = '', $id_element = 0, $uuid, $auth){
        if($article != '')
        {
            $Authorization = $auth;
            $supplierID = $uuid;
            $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnGetCadrList", array($id_element, &$Authorization, &$supplierID, $params = array()));
            $event->send();
//            if(LANG_CHARSET == 'windows-1251') $article = CMaxyssWb::deepIconv($article, 'windows-1251', 'UTF-8//IGNORE');
            $data_string = array(
                "vendorCodes"=>array($article)
            );
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);

            if(!$Authorization) return false;
            else{
                $path = "/content/v1/cards/filter";
                $api = new RestClient([
                    'base_url' => WB_BASE_URL,
                    'curl_options' => array(
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_POSTFIELDS => $data_string,
                        CURLOPT_HEADER => TRUE,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'Authorization: ' . $Authorization,
                        )
                    )
                ]);


                $str_result = $api->post($path, []);
//                echo '<pre>', print_r($str_result), '</pre>' ;
                if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "GetCardForArticle", "DESCRIPTION" => serialize($str_result) ));
                }
                if ($str_result->info->http_code == 200) {
                $result = \Bitrix\Main\Web\Json::decode($str_result->response);
//                    $result = $str_result->response;
                    return $result;
                }else{
                    return false;
                }
            }
        }
        else
            return false;
    }
    public static function  GetCards($article = '', $limit = 1000, $updatedAt =  "", $nmID = '', $cabinet = 'DEFAULT'){

        $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);
        $data_string = array(
            "sort"=>array(
                "cursor" => array(
                    "limit"=> $limit,
//                        "updatedAt"=> $updatedAt,
//                        "nmID" => intval($nmID),
            ),
            "sort"=>array(
                "sortColumn"=>'nmID',
                "ascending"=>false
                )
            )
        );
        if($updatedAt !=''){
            $data_string['sort']['cursor']['updatedAt'] = $updatedAt;
        }
        if($nmID != ''){
            $data_string['sort']['cursor']['nmID'] = $nmID;
        }
        if($article != '') {
                $data_string['sort']['filter']['textSearch'] = $article; // 	 integer ????????????
            $data_string['sort']['filter']['withPhoto'] = -1;
        }else{
            $data_string['sort']['filter']['withPhoto'] = -1;
        }
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);
        if(!$Authorization) return false;
        else{
            $path = "/content/v1/cards/cursor/list";
            $api = new RestClient([
                'base_url' => WB_BASE_URL,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,
                    )
                )
            ]);


            $str_result = $api->post($path, []);
            if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "PrepareItemNewApiContent", "DESCRIPTION" => serialize($str_result->response)));
            }

            if ($str_result->info->http_code == 200) {
            $result = \Bitrix\Main\Web\Json::decode($str_result->response);
//                    $result = $str_result->response;
                return $result;
            }else{
                return false;
            }
        }
    }
    public static function  GetAllCards($limit = 10, $updatedAt = '', $nmID = '', $cabinet = 'DEFAULT', &$result = array()){
        $res = self::GetCards($article = '', $limit, $updatedAt, $nmID, $cabinet);
        if(!empty($res['data']['cards'])){
            foreach ($res['data']['cards'] as $card){
                $result[] = $card;
            }
        }
        if(isset($res['data']) && !empty($res['data']['cards']) && $res['data']['cursor']['total'] >= $limit){
           $res =  self::GetAllCards($limit, $updatedAt = $res['data']['cursor']['updatedAt'], $nmID = $res['data']['cursor']['nmID'], $cabinet, $result);
        }
        return $result;
    }
    public static function GetNoCreateCards($cabinet = 'DEFAULT'){
        $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);
//            if(LANG_CHARSET == 'windows-1251') $article = CMaxyssWb::deepIconv($article, 'windows-1251', 'UTF-8//IGNORE');

        if(!$Authorization) return false;
        else{
            $path = "/content/v1/cards/error/list";
            $api = new RestClient([
                'base_url' => WB_BASE_URL,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,
                    )
                )
            ]);

            $str_result = $api->post($path, []);
//            echo '<pre>', print_r($str_result), '</pre>' ;

            if ($str_result->info->http_code == 200) {
                $result = \Bitrix\Main\Web\Json::decode($str_result->response);
//                    $result = $str_result->response;
                return $result;
            }else{
                return $str_result;
            }
        }
    } 
    public static function KgtSize($arTovar){
        $kgt = false;
        if($arTovar['WEIGHT']>25000)
            $kgt = true;
        if($arTovar['WIDTH']>1200)
            $kgt = true;
        if($arTovar['LENGTH']>1200)
            $kgt = true;
        if($arTovar['HEIGHT']>1200)
            $kgt = true;
        if(($arTovar['HEIGHT']+$arTovar['WIDTH']+$arTovar['LENGTH'])>2000)
            $kgt = true;
        return $kgt;
    }
}

class FilterCustomWB
{
    public function __construct($params = array())
    {
        $params = array();
    }

    public function parseCondition($condition, $params)
    {
        $result = array();

        if (!empty($condition) && is_array($condition))
        {
            if ($condition['CLASS_ID'] === 'CondGroup')
            {
                if (!empty($condition['CHILDREN']))
                {
                    foreach ($condition['CHILDREN'] as $child)
                    {
                        $childResult = $this->parseCondition($child, $params);

                        // is group
                        if ($child['CLASS_ID'] === 'CondGroup')
                        {
                            $result[] = $childResult;
                        }
                        // same property names not overrides each other
                        elseif (isset($result[key($childResult)]))
                        {
                            $fieldName = key($childResult);

                            if (!isset($result['LOGIC']))
                            {
                                $result = array(
                                    'LOGIC' => $condition['DATA']['All'],
                                    array($fieldName => $result[$fieldName])
                                );
                            }

                            $result[][$fieldName] = $childResult[$fieldName];
                        }
                        else
                        {
//                            $result += $childResult;
                            $result[]= $childResult;
                        }
                    }
                    if (!empty($result))
                    {
                        $this->parsePropertyCondition($result, $condition, $params);

                        if (count($result) > 1)
                        {
                            $result['LOGIC'] = $condition['DATA']['All'];
                        }
                    }
                }
            }
            else
            {
                $result += $this->parseConditionLevel($condition, $params);
            }
        }

        return $result;
    }
    protected function parseConditionOperator($condition)
    {
        $operator = '';

        switch ($condition['DATA']['logic'])
        {
            case 'Equal':
                $operator = '';
                break;
            case 'Not':
                $operator = '!';
                break;
            case 'Contain':
                $operator = '%';
                break;
            case 'NotCont':
                $operator = '!%';
                break;
            case 'Great':
                $operator = '>';
                break;
            case 'Less':
                $operator = '<';
                break;
            case 'EqGr':
                $operator = '>=';
                break;
            case 'EqLs':
                $operator = '<=';
                break;
        }

        return $operator;
    }
    protected function parseConditionValue($condition, $name)
    {
        $value = $condition['DATA']['value'];

        switch ($name)
        {
            case 'DATE_ACTIVE_FROM':
            case 'DATE_ACTIVE_TO':
            case 'DATE_CREATE':
            case 'TIMESTAMP_X':
                $value = ConvertTimeStamp($value, 'FULL');
                break;
        }

        return $value;
    }

    protected function parseConditionLevel($condition, $params)
    {
        $result = array();

        if (!empty($condition) && is_array($condition))
        {
            $name = $this->parseConditionName($condition);
            if (!empty($name))
            {
                $operator = $this->parseConditionOperator($condition);
                $value = $this->parseConditionValue($condition, $name);
                $result[$operator.$name] = $value;

                if ($name === 'SECTION_ID')
                {
                    $result['INCLUDE_SUBSECTIONS'] = isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'N' ? 'N' : 'Y';

                    if (isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'A')
                    {
                        $result['SECTION_GLOBAL_ACTIVE'] = 'Y';
                    }

                    $result = array($result);
                }
            }
        }

        return $result;
    }

    protected function parseConditionName(array $condition)
    {
        $name = '';
        $conditionNameMap = array(
            'CondIBXmlID' => 'XML_ID',
            'CondIBSection' => 'SECTION_ID',
            'CondIBDateActiveFrom' => 'DATE_ACTIVE_FROM',
            'CondIBDateActiveTo' => 'DATE_ACTIVE_TO',
            'CondIBSort' => 'SORT',
            'CondIBDateCreate' => 'DATE_CREATE',
            'CondIBCreatedBy' => 'CREATED_BY',
            'CondIBTimestampX' => 'TIMESTAMP_X',
            'CondIBModifiedBy' => 'MODIFIED_BY',
            'CondIBTags' => 'TAGS',
            'CondCatQuantity' => 'QUANTITY',
            'CondCatWeight' => 'WEIGHT'
        );

        if (isset($conditionNameMap[$condition['CLASS_ID']]))
        {
            $name = $conditionNameMap[$condition['CLASS_ID']];
        }
        elseif (mb_strpos($condition['CLASS_ID'], 'CondIBProp') !== false)
        {
            $name = $condition['CLASS_ID'];
        }

        return $name;
    }

    protected function parsePropertyCondition(array &$result, array $condition, $params)
    {
        if (!empty($result))
        {
            $subFilter = array();

            foreach ($result as $name => $value)
            {
                if (!empty($result[$name]) && is_array($result[$name]))
                {
                    $this->parsePropertyCondition($result[$name], $condition, $params);
                }
                else
                {
                    if (($ind = mb_strpos($name, 'CondIBProp')) !== false)
                    {
                        list($prefix, $iblock, $propertyId) = explode(':', $name);
                        $operator = $ind > 0? mb_substr($prefix, 0, $ind) : '';

                        $catalogInfo = \CCatalogSku::GetInfoByIBlock($iblock);
                        if (!empty($catalogInfo))
                        {
                            if (
                                $catalogInfo['CATALOG_TYPE'] != \CCatalogSku::TYPE_CATALOG
                                && $catalogInfo['IBLOCK_ID'] == $iblock
                            )
                            {
                                $subFilter[$operator.'PROPERTY_'.$propertyId] = $value;
                            }
                            else
                            {
                                $result[$operator.'PROPERTY_'.$propertyId] = $value;
                            }
                        }

                        unset($result[$name]);
                    }
                }
            }

            if (!empty($subFilter) && !empty($catalogInfo))
            {
                $offerPropFilter = array(
                    'IBLOCK_ID' => $catalogInfo['IBLOCK_ID'],
                    'ACTIVE_DATE' => 'Y',
                    'ACTIVE' => 'Y'
                );

                if ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'Y')
                {
                    $offerPropFilter['HIDE_NOT_AVAILABLE'] = 'Y';
                }
                elseif ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'L')
                {
                    $offerPropFilter[] = array(
                        'LOGIC' => 'OR',
                        'AVAILABLE' => 'Y',
                        'SUBSCRIBE' => 'Y'
                    );
                }

                if (count($subFilter) > 1)
                {
                    $subFilter['LOGIC'] = $condition['DATA']['All'];
                    $subFilter = array($subFilter);
                }

                $result['=ID'] = \CIBlockElement::SubQuery(
                    'PROPERTY_'.$catalogInfo['SKU_PROPERTY_ID'],
                    $offerPropFilter + $subFilter
                );
            }
        }
    }

    public function onPrepareComponentParams($params)
    {

        if (isset($params['CUSTOM_FILTER']) && is_string($params['CUSTOM_FILTER'])) {
            try {
                $params['CUSTOM_FILTER'] = $this->parseCondition(Json::decode($params['CUSTOM_FILTER']), $params);
            } catch (\Exception $e) {
                $params['CUSTOM_FILTER'] = array();
            }
        } else {
            $params['CUSTOM_FILTER'] = array();
        }
    }
}
