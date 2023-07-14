<? require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
CModule::IncludeModule('maxyss.wb');
CModule::IncludeModule('iblock');
use \Bitrix\Main\Config\Option;
// v2

if(!function_exists('cmp_ms')) {
    function cmp_ms($a, $b)
    {
        if ($a['value'] == $b['value']) {
            return 0;
        }
        return ($a['value'] < $b['value']) ? -1 : 1;
    }
}

if($_REQUEST['action'] == 'get_object'){
    $res = '';
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $res = CCustomTypeMaxyssWBProp::GetObjectWb(strval($_REQUEST['pattern']), strval($_REQUEST['lang']));
    }
    return $res;
}

if($_REQUEST['action'] == 'upload_card'){

    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        if (LANG_CHARSET == 'windows-1251') $request = CMaxyssWb::deepIconv($_REQUEST, 'windows-1251', 'UTF-8//IGNORE');
        else $request = $_REQUEST;

        if (isset($request['lk']) && $request['lk'] != '') $cabinet = $request['lk'];
        else $cabinet = 'DEFAULT';

        $res = '';
        $id_element = htmlspecialcharsbx(str_replace('E', '', $_REQUEST['product_id']));
        $item_info = CAddinMaxyssWB::PrepareItemNewApiContent($id_element, $cabinet);
//    $item_info = false;
        $imgPath = $_SERVER['DOCUMENT_ROOT'];

        if ($item_info !== false) {
            $char_all = array();
            $sinc_set = array();
            global $DB;
            $row = $DB->Query("SELECT * FROM b_option WHERE NAME='MAXYSS_SINC_WB_ATTR'")->Fetch();
            if (strlen($row['VALUE']) > 0)
                $sinc_set = unserialize($row['VALUE']);

            if (!empty($sinc_set) && isset($item_info['predmet']) && $item_info['predmet'] != '') {
                $object = $item_info['predmet'];
                if (LANG_CHARSET == 'windows-1251') $object = CMaxyssWb::deepIconv($object, 'windows-1251', 'UTF-8//IGNORE');
                $res_obj_char = CAddinMaxyssWB::getObjectCharacteristicsFilter($object);
                if (!empty($res_obj_char)) {
                    $char_all = \Bitrix\Main\Web\Json::decode($res_obj_char);
                }
            }
            $arSettings = CMaxyssWb::settings_wb($cabinet);
            $lid = $arSettings['SITE'];
            $IBLOCK_ID = $arSettings['IBLOCK_ID'];
            $arTovar = CMaxyssWb::elemAsProduct($id_element, $arSettings);
            $barcode_count = 0;

            if (!empty($char_all['data'])) {
                $item_info['item']['characteristics'] = CAddinMaxyssWB::GetSyncAddin($item_info['item']['characteristics'], $sinc_set, $IBLOCK_ID, $char_all['data'], $item_info['ar_prop_element']);
            }

            if ($arTovar['WEIGHT'] > 0) {
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC') => intval($arTovar['WEIGHT']));
            }
            if ($arTovar['WEIGHT'] > 0) {
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG')] = array(GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG') => floatval($arTovar['WEIGHT'] / 1000));
            }
            if ($arTovar['WIDTH'] > 0) {
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC') => ceil($arTovar['WIDTH'] / 10));
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC_MM')] = array(GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC_MM') => ceil($arTovar['WIDTH'] / 10));
            }
            if ($arTovar['LENGTH'] > 0) {
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC') => ceil($arTovar['LENGTH'] / 10));
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC_MM')] = array(GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC_MM') => ceil($arTovar['LENGTH'] / 10));
            }
            if ($arTovar['HEIGHT'] > 0) {
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC') => ceil($arTovar['HEIGHT'] / 10));
                $item_info['item']["characteristics"][GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC_MM')] = array(GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC_MM') => ceil($arTovar['HEIGHT'] / 10));
            }
            if ($arTovar['TYPE'] == 3) {
                $update_noms = false;
                $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

                if (is_array($arInfoOff)) {

                    // ����, ������

                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt")) {
                        $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
                        foreach ($arSettings['LK_WB_DATA'] as $key => $lk) {
                            $count_depend[] = $key;
                        }
                        if (is_array($count_depend)) {
                            $dependencies_cab['WB_CAT_PROP'] = $dependencies['WB_CAT_PROP'][array_search($cabinet, $count_depend)];
                            $dependencies_cab['WB_SCU_PROP'] = $dependencies['WB_SCU_PROP'][array_search($cabinet, $count_depend)];
                            $dependencies = $dependencies_cab;
                        }
                    }
                    if (!empty($dependencies))
                        foreach ($dependencies["WB_SCU_PROP"] as $prop) {
                            if ($prop['propWB'] == 'colors') $key_prop = 'id'; else $key_prop = 'name';
                            foreach ($prop["propsList"] as $pr) {
//                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = $pr['wbVal']['wb_name'];
                                $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = ($prop['propWB'] == 'colors' || $prop['propWB'] == 'tech-sizes') ? $pr['wbVal']['wb_name'] : $pr['wbVal']['wb_key'];
                            }
                        }


                    $arSelect = Array("ID", "IBLOCK_ID", "NAME", $arSettings['BASE_PICTURE'], $arSettings['DESCRIPTION'], "PROPERTY_*");

                    $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfoOff['IBLOCK_ID'], "ACTIVE" => "Y", 'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $id_element), false, false, $arSelect);

                    while ($arOffer = $rsOffers->GetNextElement()) {
                        $arOff['FIELD'] = $arOffer->GetFields();
                        $arOff['PROP'] = $arOffer->GetProperties();

                        if (!empty($prop_change)) {
                            foreach ($arOff['PROP'] as &$prop_card) {
                                $value = '';
                                if (array_key_exists($prop_card['ID'], $prop_change)) {

                                    $value = $prop_card['VALUE'] ? $prop_card['VALUE'] : $prop_card['VALUE_ENUM_ID'];
                                    $value_W = $prop_card['VALUE_ENUM_ID'];

                                    foreach ($prop_change[$prop_card['ID']] as $key => $wb_directory) {
                                        if ($key == 'wbsizes' && $value != '') {
                                            $arOff['wbsizes'] = $wb_directory[$value];
                                        } elseif ($key == 'tech-sizes' && $value != '') {
                                            $arOff['tech-sizes'] = $wb_directory[$value];
                                        } elseif ($key == 'colors' && ($value != '' || $item_info['props']['colors'] != '')) {
                                            if ($wb_directory[$value] == '') {
                                                $arOff['colors'] = ($wb_directory[$value_W]) ? $wb_directory[$value_W] : $item_info['props']['colors'];
                                            } else {
                                                $arOff['colors'] = ($wb_directory[$value]) ? $wb_directory[$value] : $item_info['props']['colors'];
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($arOff["PROP"][$arSettings['SHKOD']]["VALUE"] == '') $barcode_count++;

                        if ($arOff['colors'] != '')
                            $arOffers[$arOff['colors']][] = $arOff;
                        else
                            $arOffers[1][] = $arOff;

                    }
                }

                if ($barcode_count > 0) $barcode_gen = CMaxyssWb::getBarcodes($barcode_count, $arSettings['UUID'], $arSettings['AUTHORIZATION']);

                if (!empty($arOffers)) {
                    $noms_service = array();
                    $noms = array();
                    $arImgForUpload = array();

                    foreach ($arOffers as $key => $color) {
                        if ($arSettings['TP_AS_PRODUCT'] == 'Y') { // �� ��� �����
                             foreach ($color as $size) {
                                $nom = array(
                                    "vendorCode" => ($arSettings['ARTICLE'] == '') ? $size['FIELD']['ID'] : $size["PROP"][$arSettings['ARTICLE']]["VALUE"],
                                    "characteristics" => $item_info['item']['characteristics'],
                                    "sizes" => $item_info['item']['sizes'],
                                );

                                $nom['sizes'][0]['price'] = CMaxyssWb::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $size['FIELD']['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]
                                );

                                 $description = '';
                                 if ($arSettings['DESCRIPTION'] == 'DETAIL_TEXT' || $arSettings['DESCRIPTION'] == 'PREVIEW_TEXT')
                                     $description = $size['FIELD'][$arSettings['DESCRIPTION']];
                                 elseif ($arSettings['DESCRIPTION'] != '')
                                     $description = (is_array($size["PROP"][$arSettings['DESCRIPTION']]["~VALUE"])) ? $size["PROP"][$arSettings['DESCRIPTION']]["~VALUE"]["TEXT"] : $size["PROP"][$arSettings['DESCRIPTION']]["~VALUE"];

                                 $name = '';
                                 if ($arSettings['NAME_PRODUCT'] == 'NAME')
                                     $name = $size['FIELD']['NAME'];
                                 elseif ($arSettings['NAME_PRODUCT'] != '')
                                     $name = (is_array($size["PROP"][$arSettings['NAME_PRODUCT']]["~VALUE"])) ? $size["PROP"][$arSettings['NAME_PRODUCT']]["~VALUE"]["TEXT"] : $size["PROP"][$arSettings['NAME_PRODUCT']]["~VALUE"];

                                if($description != '')
                                    $addin_card[GetMessage('WB_MAXYSS_DESCRIPTION')] = array(GetMessage('WB_MAXYSS_DESCRIPTION')=> TruncateText(str_replace('&nbsp;', ' ', htmlentities(HTMLToTxt($description, $arSettings['SERVER_NAME']))), 997) );
                                if($name != '')
                                    $addin_card[GetMessage('MAXYSS_WB_NAME_NAME')] = array( GetMessage('MAXYSS_WB_NAME_NAME')=>  TruncateText($name, 57));


                                $arTovarOff = CMaxyssWb::elemAsProduct($size['FIELD']['ID'], $arSettings);
                                if(!empty($char_all['data'])) {
                                    $nom['characteristics'] = CAddinMaxyssWB::GetSyncAddin($nom['characteristics'], $sinc_set, $size['FIELD']['IBLOCK_ID'], $char_all['data'], $size['PROP']);
                                }
                                if($arTovarOff['WEIGHT'] > 0 && !isset( $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC')])){
                                    $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC')=>intval($arTovarOff['WEIGHT']));
                                             }
                                if($arTovarOff['WEIGHT'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG')])){
                                    $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG')] = array(GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG')=>floatval($arTovarOff['WEIGHT']/1000));
                                             }
                                if($arTovarOff['WIDTH'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC')])){
                                    $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC')=>$arTovarOff['WIDTH']/10);
                                     }
                                if($arTovarOff['LENGTH'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC')])){
                                    $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC')=>$arTovarOff['LENGTH']/10);
                                 }
                                if($arTovarOff['HEIGHT'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC')])){
                                    $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC')=>$arTovarOff['HEIGHT']/10);
                                }


                                 $img = array();

                                 if ($size['FIELD'][$arSettings['BASE_PICTURE']]) {
                                     $img[] = $imgPath . CFile::GetPath($size['FIELD'][$arSettings['BASE_PICTURE']]);
                                 }
                                 if (is_array($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'])) {
                                     foreach ($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                                         $img[] = $imgPath . CFile::GetPath($photo);
                                     }
                                 }
                                 $img = array_merge($img, $item_info['img']);


                                if (!empty($barcode_gen['data']) && $size["PROP"][$arSettings['SHKOD']]["VALUE"] == '') {
                                    $barcode = array_shift($barcode_gen['data']);
                                     CIBlockElement::SetPropertyValuesEx($size['FIELD']['ID'], false, array(
                                         $arSettings['SHKOD'] => $barcode,
                                     ));
                                 } else $barcode = $size["PROP"][$arSettings['SHKOD']]["VALUE"];

                                $nom['sizes'][0]['skus'] = array($barcode);
                                if(is_array($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']))
                                    $key_cabinet_prop = array_search($cabinet, $size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                                if($key_cabinet_prop === false && is_array($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']))
                                    $key_cabinet_prop = array_search('', $size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                                 $img = array_unique($img);

                                if(!empty($img))
                                    $arImgForUpload[] = array(
                                        'img' => $img,
                                        'vendorCode'=>$nom['vendorCode'],
                                        'auth'=>$arSettings["AUTHORIZATION"],
                                    );


                                if ($key_cabinet_prop !== false) {
                                    if(
                                        intval($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet_prop]) >0 &&
                                        intval($size['PROP']['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet_prop]) >0 &&
                                        intval($size['PROP']['PROP_MAXYSS_CARDID_WB']['VALUE'][$key_cabinet_prop]) > 0
                                    ) {
                                        $nom['sizes'][0]['chrtID'] = intval($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet_prop]);
                                        $nom['nmID'] = intval($size['PROP']['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet_prop]);
                                        $nom['imtID'] = intval($size['PROP']['PROP_MAXYSS_CARDID_WB']['VALUE'][$key_cabinet_prop]);
                                        $update_noms = true;
                                 }
                                 }
                                $nom['characteristics'] = array_values($nom['characteristics']);
                                $noms[] = $nom;

                             }
                         }
                         else
                         {
                        $img = array();
                        if ($key != 1) {
                            $nom = array(
                                "vendorCode" => $item_info['item']['VendorCode'] . '_' . str_replace(array('-', ' '), '_', $key),
                                "characteristics" => $item_info['item']['characteristics']
                            );
                            $nom['characteristics'][] = array(GetMessage('WB_MAXYSS_COLOR_NOM') => $key);
                        } else {
                            $nom = array(
                                "vendorCode" => $item_info['item']['VendorCode'] /*. '_' . str_replace(array('-', ' '), '_', $key)*/,
                                "characteristics" => $item_info['item']['characteristics']
                            );
                        }

                        $variations = array();
                        $key_cabinet_prop = false;
                        foreach ($color as $size) {

                            $arTovarOff = CMaxyssWb::elemAsProduct($size['FIELD']['ID'], $arSettings);
                            if (!empty($char_all['data'])) {
                                $nom['characteristics'] = CAddinMaxyssWB::GetSyncAddin($nom['characteristics'], $sinc_set, $size['FIELD']['IBLOCK_ID'], $char_all['data'], $size['PROP']);
                            }
                            if ($arTovarOff['WEIGHT'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC')])) {
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC') => intval($arTovarOff['WEIGHT']));
                            }
                            if ($arTovarOff['WEIGHT'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG')])) {
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG')] = array(GetMessage('MAXYSS_WB_CARD_WEIGHT_UPAC_KG') => floatval($arTovarOff['WEIGHT'] / 1000));
                            }
                            if ($arTovarOff['WIDTH'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC')])) {
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC') => ceil($arTovarOff['WIDTH'] / 10));
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC_MM')] = array(GetMessage('MAXYSS_WB_CARD_WIDTH_UPAC_MM') => ceil($arTovarOff['WIDTH'] / 10));
                            }
                            if ($arTovarOff['LENGTH'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC')])) {
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC') => ceil($arTovarOff['LENGTH'] / 10));
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC_MM')] = array(GetMessage('MAXYSS_WB_CARD_LENGTH_UPAC_MM') => ceil($arTovarOff['LENGTH'] / 10));
                            }
                            if ($arTovarOff['HEIGHT'] > 0 && !isset($nom["characteristics"][GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC')])) {
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC')] = array(GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC') => ceil($arTovarOff['HEIGHT'] / 10));
                                $nom["characteristics"][GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC_MM')] = array(GetMessage('MAXYSS_WB_CARD_HEIGHT_UPAC_MM') => ceil($arTovarOff['HEIGHT'] / 10));
                            }


                            // ����� ������� �� ���� �� ������ �����
                            if ($size['FIELD'][$arSettings['BASE_PICTURE']]) {
                                $img[] = $imgPath . CFile::GetPath($size['FIELD'][$arSettings['BASE_PICTURE']]);
                            }
                            if (is_array($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'])) {
                                foreach ($size['PROP'][$arSettings['MORE_PICTURE']]['VALUE'] as $photo) {
                                    $img[] = $imgPath . CFile::GetPath($photo);
                                }
                            }
                            $img = array_merge($img, $item_info['img']);
                            // ����� ������� �� ���� �� ������ �����
                            $size_wb = array();
                            if (strlen($size['tech-sizes']) > 0) {
                                $size_wb['techSize'] = strval($size['tech-sizes']);
                            }

                            if (strlen($size['wbsizes']) > 0) {
                                $size_wb['wbSize'] = strval($size['wbsizes']);
                            }

                            $size_wb['price'] = CMaxyssWb::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $size['FIELD']['ID'], $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]
                            );


                            if (!empty($barcode_gen['data']) && $size["PROP"][$arSettings['SHKOD']]["VALUE"] == '') {
                                $barcode = array_shift($barcode_gen['data']);
                                CIBlockElement::SetPropertyValuesEx($size['FIELD']['ID'], false, array(
                                    $arSettings['SHKOD'] => $barcode,
                                ));
                            } else $barcode = $size["PROP"][$arSettings['SHKOD']]["VALUE"];

                            $size_wb['skus'] = array($barcode);
                            if (is_array($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']))
                                $key_cabinet_prop = array_search($cabinet, $size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                            if ($key_cabinet_prop === false && is_array($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION'])) $key_cabinet_prop = array_search('', $size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['DESCRIPTION']);

                            if ($key_cabinet_prop !== false) {
                                $size_wb['chrtID'] = intval($size['PROP']['PROP_MAXYSS_CHRTID_CREATED_WB']['VALUE'][$key_cabinet_prop]);
                            }
                            $variations[] = $size_wb;
                        }
                        $img = array_unique($img);
                        if (!empty($img))
                            $arImgForUpload[] = array(
                                'img' => $img,
                                'vendorCode' => $nom['vendorCode'],
                                'auth' => $arSettings["AUTHORIZATION"],
                            );

                        $nom["sizes"] = $variations;

                        if ($key_cabinet_prop !== false) {
                            $nom['nmID'] = intval($color[0]['PROP']['PROP_MAXYSS_NMID_CREATED_WB']['VALUE'][$key_cabinet_prop]);
                            $nom['imtID'] = intval($item_info['ar_prop_element']['PROP_MAXYSS_CARDID_WB']['VALUE'][$key_cabinet_prop]);
                            $update_noms = true;
                        }
                        $nom['characteristics'] = array_values($nom['characteristics']);
                        $noms[] = $nom;

                        }
                    }
                    if (!empty($noms)) {
                        $res_upload = '';
                        if ($update_noms) {
                            if ($_REQUEST['param'] != 'photo') {
                                foreach ($noms as $n) {
                                    $res_upload = CAddinMaxyssWB::UpdateCadrNewApiContent($n, $id_element, $arSettings["AUTHORIZATION"]);
                                    $res .= $id_element . ' ' . $n['vendorCode'] . ' ' . $res_upload . '<br>';
                                }
                                if ($res_upload == GetMessage("WB_MAXYSS_PRODUCT_UPLOAD") && !empty($arImgForUpload)) {
                                    foreach ($arImgForUpload as $arImg) {
                                        $res_upload = CAddinMaxyssWB::AddMediaFile($arImg['img'], $arImg['vendorCode'], $arSettings['AUTHORIZATION']);
                                        $res .= $id_element . ' ' . $res_upload . '<br>';
                                    }
                                }
                            }
                            if (($_REQUEST['param'] == 'photo') && !empty($arImgForUpload)) {
                                foreach ($arImgForUpload as $arImg) {
                                    $res_upload = CAddinMaxyssWB::AddMediaFile($arImg['img'], $arImg['vendorCode'], $arSettings['AUTHORIZATION']);
                                    $res .= $id_element . ' ' . $res_upload . '<br>';
                                }
                            }else{
                                $res .= $id_element . ' ' . GetMessage('WB_MAXYSS_PHOTO_NOT_PHOTO') . '<br>';
                            }
                        } else {
                            if ($_REQUEST['param'] != 'photo') {
                            if ($arSettings['TP_AS_PRODUCT'] == 'Y') { // �� ��� �����
                                foreach ($noms as $n) {
                                    $res_upload = CAddinMaxyssWB::UploadCadrNewApiContent(array($n), $id_element, $arSettings["AUTHORIZATION"]);
                                    $res .= $id_element . ' ' . $n['vendorCode'] . ' ' . $res_upload . '<br>';
                                }
                            }else {
                                $res_upload = CAddinMaxyssWB::UploadCadrNewApiContent($noms, $id_element, $arSettings["AUTHORIZATION"]);
                                $res .= $id_element . ' ' . $res_upload . '<br>';
                            }
                        }
                            if (($res_upload == GetMessage("WB_MAXYSS_PRODUCT_UPLOAD") && !empty($arImgForUpload)) || ($_REQUEST['param'] == 'photo') && !empty($arImgForUpload)) {
                                foreach ($arImgForUpload as $arImg) {
                                    $res_upload = CAddinMaxyssWB::AddMediaFile($arImg['img'], $arImg['vendorCode'], $arSettings['AUTHORIZATION']);
                                    $res .= $id_element . ' ' . $res_upload . '<br>';
                                }
                            }else{
                                $res .= $id_element . ' ' . GetMessage('WB_MAXYSS_PHOTO_NOT_PHOTO') . '<br>';
                            }
                        }
                    } else {
                        $res .= $id_element . ' ' . GetMessage("WB_MAXYSS_PRODUCT_NOT_NOM");
                    }
                }
            }
            elseif ($arTovar['TYPE'] == 1 || !isset($arTovar['TYPE'])) {

                $item_info['item']['characteristics'] = array_values($item_info['item']['characteristics']);

                $item_info['item']["sizes"][0]["price"] = CMaxyssWb::get_price($arSettings['PRICE_TYPE'], $arSettings['PRICE_PROP'], $arSettings['PRICE_TYPE_PROP'], $arSettings['PRICE_TYPE_NO_DISCOUNT'], $id_element, $lid, $arSettings["PRICE_TYPE_FORMULA"], $arSettings["PRICE_TYPE_FORMULA_ACTION"]);

                if ($item_info['item']["sizes"][0]["skus"][0] == '') {
                    $barcode_gen = CMaxyssWb::getBarcodes(1, $arSettings['UUID'], $arSettings['AUTHORIZATION']);
                    if (!empty($barcode_gen['data'])) {
//                    $barcode = array_shift($barcode_gen['data']);
                        $barcode = $barcode_gen['data'][0];
                        CIBlockElement::SetPropertyValuesEx($id_element, false, array(
                            $arSettings['SHKOD'] => $barcode,
                        ));
                        $item_info['item']["sizes"][0]["skus"][0] = $barcode;
                    }
                }
                $res_upload = '';
                if ($item_info["item"]['nmID']) {
                    if ($_REQUEST['param'] != 'photo') {
                        $res_upload = CAddinMaxyssWB::UpdateCadrNewApiContent($item_info['item'], $id_element, $arSettings["AUTHORIZATION"]);
                        $res .= $id_element . ' ' . $res_upload . '<br>';
                    }
                    if ($res_upload == GetMessage("WB_MAXYSS_PRODUCT_UPLOAD") || $_REQUEST['param'] == 'photo') {
                        if (!empty($item_info['img'])) {
                            $res_upload = CAddinMaxyssWB::AddMediaFile($item_info['img'], $item_info['item']['VendorCode'], $arSettings['AUTHORIZATION']);
                            $res .= $id_element . ' ' . $res_upload . '<br>';
                        }else{
                            $res .= $id_element . ' ' . GetMessage('WB_MAXYSS_PHOTO_NOT_PHOTO') . '<br>';
                        }
                    }
                } else {
                    if ($_REQUEST['param'] != 'photo') {
                        if ($arSettings['ARTICLE_LINK'] != '' && $item_info['article_link'] != '') {
                            $res_upload = CAddinMaxyssWB::UploadCadrNewApiContent(array('vendorCode' => $item_info['article_link'], 'cards' => array($item_info['item'])), $id_element, $arSettings["AUTHORIZATION"]);
                        } else {
                            $res_upload = CAddinMaxyssWB::UploadCadrNewApiContent(array($item_info['item']), $id_element, $arSettings["AUTHORIZATION"]);
                        }
                        $res .= $id_element . ' ' . $res_upload . '<br>';

                    }
                    if ($res_upload == GetMessage("WB_MAXYSS_PRODUCT_UPLOAD") || $_REQUEST['param'] == 'photo')
                        if (!empty($item_info['img'])) {
                            $res_upload = CAddinMaxyssWB::AddMediaFile($item_info['img'], $item_info['item']['VendorCode'], $arSettings['AUTHORIZATION']);
                            $res .= $id_element . ' ' . $res_upload . '<br>';
                        }
                        else{
                            $res .= $id_element . ' ' . GetMessage('WB_MAXYSS_PHOTO_NOT_PHOTO') . '<br>';
                        }
                }
            } else {
                // �� ���������
                $res .= GetMessage("WB_MAXYSS_ERROR_NOT_PRODUCT");
            }

        } else
            $res = print_r('<span style="color: red">EMPTY WB ATTRIBUTES FOR UPLOAD</span>');
    }else{
        $res = GetMessage("WB_MAXYSS_ERROR_NOT_PRODUCT");
    }
    echo $res;
}

if($_REQUEST['action'] == 'data_card'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $cabinet = '';
        $prop_upd = array(
            "PROP_MAXYSS_NMID_CREATED_WB",
            "PROP_MAXYSS_CHRTID_CREATED_WB",
            "PROP_MAXYSS_NMID_WB",
            "PROP_MAXYSS_CHRTID_WB",
//        "PROP_MAXYSS_CARDID_WB",
        );

        if (LANG_CHARSET == 'windows-1251') $request = CMaxyssWb::deepIconv($_REQUEST, 'windows-1251', 'UTF-8//IGNORE');
        else $request = $_REQUEST;

        if (isset($request['lk']) && $request['lk'] != '') $cabinet = $request['lk'];
        else $cabinet = 'DEFAULT';

        $res = json_encode(array('error' => "MAXYSS_WB_NO_CARD_ID"));
        $id_element = str_replace('E', '', htmlspecialcharsbx($_REQUEST['product_id']));
        $arSettings = CMaxyssWb::settings_wb($cabinet);

        $arTovar = CMaxyssWb::elemAsProduct($id_element, $arSettings);
        $iblockId = \CIBlockElement::getIBlockByID($id_element);
        $mess = '';
        $iblock_shkod = $arSettings["SHKOD"];
        $iblock_article = $arSettings["ARTICLE"];
        $article = array();
        $colors_add = array();

        // ���� �� article
        $arInfoOff = CCatalogSKU::GetInfoByProductIBlock($iblockId);
        //////////////////////////  colors
        if (is_array($arInfoOff)) {
            // color
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt")) {
                $dependencies = CUtil::JsObjectToPhp(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_WB_NAME . "/dependencies.txt"));
                foreach ($arSettings['LK_WB_DATA'] as $key => $lk) {
                    $count_depend[] = $key;
                }
                if (is_array($count_depend)) {
                    $dependencies_cab['WB_CAT_PROP'] = $dependencies['WB_CAT_PROP'][array_search($cabinet, $count_depend)];
                    $dependencies_cab['WB_SCU_PROP'] = $dependencies['WB_SCU_PROP'][array_search($cabinet, $count_depend)];
                    $dependencies = $dependencies_cab;
                }
            }
            if (!empty($dependencies))
                foreach ($dependencies["WB_SCU_PROP"] as $prop) {
                    if ($prop['propWB'] == 'colors') $key_prop = 'id'; else $key_prop = 'name';
                    foreach ($prop["propsList"] as $pr) {
//                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = $pr['wbVal']['wb_name'];
                        $prop_change[$prop['propID']][$prop['propWB']][$pr['bxVal'][$key_prop]] = ($prop['propWB'] == 'colors' || $prop['propWB'] == 'tech-sizes') ? $pr['wbVal']['wb_name'] : $pr['wbVal']['wb_key'];
                    }
                }

            $arSelect = Array("ID", "IBLOCK_ID", "NAME",/* "PROPERTY_*"*/);

            $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfoOff['IBLOCK_ID'], "ACTIVE" => "Y", 'PROPERTY_' . $arInfoOff['SKU_PROPERTY_ID'] => $id_element), false, false, $arSelect);

            while ($arOffer = $rsOffers->GetNextElement()) {
                $arOff['FIELD'] = $arOffer->GetFields();
                $arOff['PROP'] = $arOffer->GetProperties();

                if (!empty($prop_change)) {
                    foreach ($arOff['PROP'] as &$prop_card) {
                        $value = '';
                        if (array_key_exists($prop_card['ID'], $prop_change)) {

                            $value = $prop_card['VALUE'] ? $prop_card['VALUE'] : $prop_card['VALUE_ENUM_ID'];
                            $value_W = $prop_card['VALUE_ENUM_ID'];

                            foreach ($prop_change[$prop_card['ID']] as $key => $wb_directory) {
                                if ($key == 'wbsizes' && $value != '') {
                                    $arOff['wbsizes'] = $wb_directory[$value];
                                } elseif ($key == 'tech-sizes' && $value != '') {
                                    $arOff['tech-sizes'] = $wb_directory[$value];
                                } elseif ($key == 'colors' && ($value != '' || $item_info['props']['colors'] != '')) {
                                    if ($wb_directory[$value] == '') {
                                        $arOff['colors'] = ($wb_directory[$value_W]) ? $wb_directory[$value_W] : $item_info['props']['colors'];
                                    } else {
                                        $arOff['colors'] = ($wb_directory[$value]) ? $wb_directory[$value] : $item_info['props']['colors'];
                                    }
                                }
                            }
                        }
                    }
                }

                if ($arOff["PROP"][$arSettings['SHKOD']]["VALUE"] == '') $barcode_count++;

                if ($arOff['colors'] != '')
                    $arOffers[$arOff['colors']][] = $arOff;
                else
                    $arOffers[1][] = $arOff;

            }
            if (!empty($arOffers)) {
                foreach ($arOffers as $key => $color) {
                if ($arSettings['TP_AS_PRODUCT'] == 'Y') { // �� ��� �����
                    foreach ($color as $size) {
                        $article[] = array('code' => ($arSettings['ARTICLE'] == '') ? $size['FIELD']['ID'] : $size["PROP"][$arSettings['ARTICLE']]["VALUE"], 'id' => $size['FIELD']['ID']);
                    }
                }else{
                    if ($key != 1) {
                        $colors_add[] = '_' . str_replace(array('-', ' '), '_', $key);
                    } else {
                        $colors_add[] = '';
                    }
                }
            }
        }
    }
        //////////////////////////

        if ($iblock_article != '') {
            if ($arTovar['TYPE'] == 1) {
                $dbPropSC = CIBlockElement::GetProperty($iblockId, $id_element, "sort", "asc", array("CODE" => $iblock_article));
                if ($arPropSC = $dbPropSC->GetNext()) {
                    if ($arPropSC['VALUE']) {
                        $article[] = array('code' => $arPropSC['VALUE'], 'id' => $id_element);
                    }
                }
            } elseif ($arTovar['TYPE'] == 3) {
            if ($arSettings['TP_AS_PRODUCT'] == 'Y') { // �� ��� �����

            }
            else
            {
                $dbPropSC = CIBlockElement::GetProperty($iblockId, $id_element, "sort", "asc", array("CODE" => $iblock_article));
                if ($arPropSC = $dbPropSC->GetNext()) {
                    if ($arPropSC['VALUE']) {
                        if (!empty($colors_add)) {
                            foreach ($colors_add as $cl) {
                                $article[] = array('code' => $arPropSC['VALUE'] . $cl, 'id' => $id_element);
                            }
                        } else {
                            $article[] = array('code' => $arPropSC['VALUE'], 'id' => $id_element);
                        }
                    }
                }
            }
        }
    }
    else
    {
            if ($arTovar['TYPE'] == 1) {
                $article[] = array('code' => $id_element, 'id' => $id_element);
            } elseif ($arTovar['TYPE'] == 3) {
            if ($arSettings['TP_AS_PRODUCT'] == 'Y') { // �� ��� �����

            }
            else {
                if (!empty($colors_add)) {
                    foreach ($colors_add as $cl) {
                        $article[] = array('code' => $id_element . $cl, 'id' => $id_element);
                    }
                } else {
                    $article[] = array('code' => $id_element, 'id' => $id_element);
                }
            }
        }
    }
        if (!empty($article)) {
            foreach ($article as $a) {
                $ar_cards = CAddinMaxyssWB::GetCardForArticle($a['code'], $id_element, $arSettings["UUID"], $arSettings["AUTHORIZATION"]);
                if (isset($ar_cards["error"]['message']) && $ar_cards["error"]['message'] == "access denied") {
                    $res = json_encode(array('error' => "access denied"));
                } elseif ($ar_cards["error"]) {
                    $res = json_encode(array('error_map' => $ar_cards["errorText"]));
                } else {
                    if (empty($ar_cards['data']))
                        $res = \Bitrix\Main\Web\Json::encode(array('success' => 'MAXYSS_WB_NO_CARD_CREATE'));
                    else {
                        $count_nm = 0;
                        foreach ($ar_cards['data'] as $card) {
                            if ($card["vendorCode"] == $a['code']) {
                                if ($card['nmID'] == 0) {
                                    $mess = GetMessage("MAXYSS_WB_NO_CARD_CREATE");
                                } else {
                                    $count_nm++;
                                if($count_nm == 1 && $arSettings['TP_AS_PRODUCT'] != 'Y') {
                                        $VALUES = array();
                                        $flag_add_cab = true;
                                        $res = CIBlockElement::GetProperty($iblockId, $a['id'], "id", "asc", array("CODE" => "PROP_MAXYSS_CARDID_WB"));
                                        while ($ob = $res->GetNext()) {
                                            if ($ob['VALUE'] != '') {
                                                $ar_val['VALUE'] = $ob['VALUE'];
                                                $ar_val['DESCRIPTION'] = ($ob['DESCRIPTION'] == '') ? 'DEFAULT' : $ob['DESCRIPTION'];
                                                $VALUES["PROP_MAXYSS_CARDID_WB"][] = $ar_val;
                                            }
                                        }
                                        if (!empty($VALUES)) {
                                            foreach ($VALUES["PROP_MAXYSS_CARDID_WB"] as &$val) {
                                                if ($val['DESCRIPTION'] == $cabinet) {
                                                    $val["VALUE"] = $card['imtID'];
                                                    $flag_add_cab = false;
                                                }
                                            }
                                            if ($flag_add_cab) $VALUES["PROP_MAXYSS_CARDID_WB"][] = array('VALUE' => $card['imtID'], 'DESCRIPTION' => $cabinet);
                                            CIBlockElement::SetPropertyValuesEx($id_element, false, $VALUES);
                                        } else {
                                            $VALUES["PROP_MAXYSS_CARDID_WB"][] = array('VALUE' => $card['imtID'], 'DESCRIPTION' => $cabinet);
                                            CIBlockElement::SetPropertyValuesEx($id_element, false, $VALUES);

                                        }
                                    }

                                    $ar_mess = array();
                                    if ($arTovar['TYPE'] == 1 && count($card['sizes']) == 1) {
                                        $VALUES = array();
                                        $res = CIBlockElement::GetProperty($iblockId, $a['id'], "id", "asc", array("CODE" => "PROP_MAXYSS%"));
                                        while ($ob = $res->GetNext()) {
                                            if (is_array($prop_upd) && array_search($ob['CODE'], $prop_upd) !== false) {
                                                $ar_val['VALUE'] = $ob['VALUE'];
                                                $ar_val['DESCRIPTION'] = ($ob['DESCRIPTION'] == '') ? 'DEFAULT' : $ob['DESCRIPTION'];
                                                $VALUES[$ob['CODE']][] = $ar_val;
                                            }
                                        }
                                        if (!empty($VALUES)) {
                                            foreach ($prop_upd as $code) {
                                                if (is_array($VALUES[$code])) {
                                                    foreach ($VALUES[$code] as $key => &$val) {
                                                        $add = true;
                                                        if ($val['DESCRIPTION'] == $cabinet) {
                                                            $add = false;
                                                            switch ($code) {
                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
                                                                    $val['VALUE'] = $card['nmID'];
                                                                    break;
                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
                                                                    $val['VALUE'] = $card['sizes'][0]['chrtID'];
                                                                    break;
//                                                            case 'PROP_MAXYSS_CARDID_WB':
//                                                                $val['VALUE'] = array("VALUE" => $card['imtID'], "DESCRIPTION" => $cabinet);
//                                                                break;
                                                            }
                                                        }
                                                        if ($add) {
                                                            switch ($code) {
                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
                                                                    $VALUES[$code][] = array("VALUE" => $card['nmID'], "DESCRIPTION" => $cabinet);
                                                                    break;
                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
                                                                    $VALUES[$code][] = array("VALUE" => $card['sizes'][0]['chrtID'], "DESCRIPTION" => $cabinet);
                                                                    break;
//                                                            case 'PROP_MAXYSS_CARDID_WB':
//                                                                $VALUES[$code][] = array("VALUE" => $card['imtID'], "DESCRIPTION" => $cabinet);
//                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            $dbPropBarcode = CIBlockElement::GetProperty($iblockId, $a['id'], "sort", "asc", array("CODE" => $iblock_shkod));
                                            if ($arPropBarcode = $dbPropBarcode->GetNext()) {
                                                if ($arPropBarcode['VALUE'] == '' || $arSettings["SHKOD_UPDATE"] == 'Y') {
                                                    $VALUES[$iblock_shkod] = $card['sizes'][0]['skus'][0];
                                                    CIBlockElement::SetPropertyValuesEx($a['id'], false, $VALUES);
                                                } else {
                                                    CIBlockElement::SetPropertyValuesEx($a['id'], false, $VALUES);
                                                }
                                            }
                                        }

                                }
                                elseif($arTovar['TYPE'] == 3)
                                {
                                    if($arSettings['TP_AS_PRODUCT'] == 'Y'){
                                        $iblockIdTp = 0;
                                        $prop_upd[] = "PROP_MAXYSS_CARDID_WB";
                                        foreach ($card['sizes'] as $sizes) {
                                            if ($iblock_shkod != '' && !empty($sizes['skus'])) {
                                                $flag_found_barcode = false;
                                                foreach ($sizes['skus'] as $barcode) {

//                                                    $arFilterBarcode = Array("PROPERTY_" . $iblock_shkod => $barcode);
//                                                    $dbTp = CIBlockElement::GetList(Array(), $arFilterBarcode, false, Array("nPageSize" => 1), $arSelect);
//                                                    if ($arTp = $dbTp->GetNextElement()) {
//                                                        $arFields = $arTp->GetFields();
                                                        $id_tp = $a['id'];

                                                        if ($iblockIdTp <= 0) {
                                                            $iblockIdTp = \CIBlockElement::getIBlockByID($id_tp);
                                                        }

                                                        ///////////////////
                                                        $VALUES = array();
                                                        $res = CIBlockElement::GetProperty($iblockIdTp, $id_tp, "id", "asc", array("CODE" => "PROP_MAXYSS%"));
                                                        while ($ob = $res->GetNext()) {
                                                            if (array_search($ob['CODE'], $prop_upd) !== false) {

                                                                $ar_val['VALUE'] = $ob['VALUE'];
                                                                $ar_val['DESCRIPTION'] = ($ob['DESCRIPTION'] == '') ? 'DEFAULT' : $ob['DESCRIPTION'];
                                                                $VALUES[$ob['CODE']][] = $ar_val;
                                                            }
                                                        }
                                                        if (!empty($VALUES)) {
                                                            foreach ($prop_upd as $code) {
                                                                if (is_array($VALUES[$code])) {
                                                                    foreach ($VALUES[$code] as $key => &$val) {
                                                                        $add = true;
                                                                        if ($val['DESCRIPTION'] == $cabinet) {
                                                                            $add = false;
                                                                            switch ($code) {
                                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
                                                                                    $val['VALUE'] = $card['nmID'];
                                                                                    break;
                                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
                                                                                    $val['VALUE'] = $sizes['chrtID'];
                                                                                    break;
                                                                                case 'PROP_MAXYSS_CARDID_WB':
                                                                                    $val['VALUE'] = $card['imtID'];
                                                                                    break;
                                                                            }
                                                                        }
                                                                        if ($add) {
                                                                            switch ($code) {
                                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
                                                                                    $VALUES[$code][] = array("VALUE" => $card['nmID'], "DESCRIPTION" => $cabinet);
                                                                                    break;
                                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
                                                                                    $VALUES[$code][] = array("VALUE" => $sizes['chrtID'], "DESCRIPTION" => $cabinet);
                                                                                    break;
                                                                                case 'PROP_MAXYSS_CARDID_WB':
                                                                                    $VALUES[$code][] = array("VALUE" => $card['imtID'], "DESCRIPTION" => $cabinet);
                                                                                    break;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
//                                                        echo '<pre>', print_r($VALUES), '</pre>' ;
                                                        if (intval($id_tp) > 0 && !empty($VALUES)) {
                                                            CIBlockElement::SetPropertyValuesEx($id_tp, false, $VALUES);
                                                        }
                                                        $flag_found_barcode = true;
                                                        break;
//                                                    } else {
//                                                        $ar_mess[] = $barcode . " - " . GetMessage("MAXYSS_WB_CARD_FOUND");
//
//                                                    }

                                                }
                                                if (!$flag_found_barcode) {
                                                    $res = \Bitrix\Main\Web\Json::encode(array('success' => 'MAXYSS_WB_DATA_SUCCESS'));
                                                }
                                            } else $ar_mess[] = 'NOT FOUND PROPERTY BARCODE';
                                        }
                                    }else {
                                        $iblockIdTp = 0;
                                        foreach ($card['sizes'] as $sizes) {

//                                    $id_tp = 0;
//                                    $arFilter = array();
//                                    $arFilter = Array("PROPERTY_PROP_MAXYSS_CHRTID_WB" => $sizes['chrtID']);
//                                    $dbTp = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize" => 1), $arSelect);
//                                    if ($arTp = $dbTp->GetNextElement()) {
//                                        $arFields = $arTp->GetFields();
//                                        $id_tp = $arFields['ID'];
//
//                                        if ($iblockIdTp <= 0) {
//                                            $iblockIdTp = \CIBlockElement::getIBlockByID($id_tp);
//                                        }
//                                        unset($prop_upd["PROP_MAXYSS_CHRTID_WB"]);
//
//                                        ///////////////////
//                                        $VALUES = array();
//                                        $res = CIBlockElement::GetProperty($iblockIdTp, $id_tp, "id", "asc", array("CODE" => "PROP_MAXYSS%"));
//                                        while ($ob = $res->GetNext()) {
//                                            if (array_search($ob['CODE'], $prop_upd) !== false) {
//
//                                                $ar_val['VALUE'] = $ob['VALUE'];
//                                                $ar_val['DESCRIPTION'] = ($ob['DESCRIPTION'] == '') ? 'DEFAULT' : $ob['DESCRIPTION'];
//                                                $VALUES[$ob['CODE']][] = $ar_val;
//                                            }
//                                        }
//                                        if (!empty($VALUES)) {
//                                            foreach ($prop_upd as $code) {
//                                                if (is_array($VALUES[$code])) {
//                                                    foreach ($VALUES[$code] as $key => &$val) {
//                                                        $add = true;
//                                                        if ($val['DESCRIPTION'] == $cabinet) {
//                                                            $add = false;
//                                                            switch ($code) {
//                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
//                                                                    $val['VALUE'] = $card['nmID'];
//                                                                    break;
//                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
//                                                                    $val['VALUE'] = $sizes['chrtID'];
//                                                                    break;
////                                                                                case 'PROP_MAXYSS_NMID_WB':
////                                                                                    $val['VALUE'] = $nomenclature['id'];
////                                                                                    break;
////                                                                                case 'PROP_MAXYSS_CHRTID_WB':
////                                                                                    $val['VALUE'] = $card['nomenclatures'][0]['variations'][0]['id'];
////                                                                                    break;
//                                                            }
//                                                        }
//                                                        if ($add) {
//                                                            switch ($code) {
//                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
//                                                                    $VALUES[$code][] = array("VALUE" => $card['nmID'], "DESCRIPTION" => $cabinet);
//                                                                    break;
//                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
//                                                                    $VALUES[$code][] = array("VALUE" => $sizes['chrtID'], "DESCRIPTION" => $cabinet);
//                                                                    break;
//                                                            }
//                                                        }
//                                                    }
//                                                }
//                                            }
//                                        } else {
//                                            foreach ($prop_upd as $code) {
//                                                switch ($code) {
//                                                    case 'PROP_MAXYSS_NMID_CREATED_WB':
//                                                        $VALUES[$code][] = array("VALUE" => $card['nmID'], "DESCRIPTION" => $cabinet);
//                                                        break;
//                                                    case 'PROP_MAXYSS_CHRTID_CREATED_WB':
//                                                        $VALUES[$code][] = array("VALUE" => $sizes['chrtID'], "DESCRIPTION" => $cabinet);
//                                                        break;
//                                                }
//                                            }
//                                        }
//                                        ////////////////
//                                        if (intval($id_tp) > 0 && !empty($VALUES)) {
//                                            CIBlockElement::SetPropertyValuesEx($id_tp, false, $VALUES);
//                                        }
//                                    }
//                                    else
//                                    {
                                            if ($iblock_shkod != '' && !empty($sizes['skus'])) {
                                                $flag_found_barcode = false;
                                                foreach ($sizes['skus'] as $barcode) {

                                                    $arFilterBarcode = Array("PROPERTY_" . $iblock_shkod => $barcode);
                                                    $dbTp = CIBlockElement::GetList(Array(), $arFilterBarcode, false, Array("nPageSize" => 1), $arSelect);
                                                    if ($arTp = $dbTp->GetNextElement()) {
                                                        $arFields = $arTp->GetFields();
                                                        $id_tp = $arFields['ID'];

                                                        if ($iblockIdTp <= 0) {
                                                            $iblockIdTp = \CIBlockElement::getIBlockByID($id_tp);
                                                        }

                                                        ///////////////////
                                                        $VALUES = array();
                                                        $res = CIBlockElement::GetProperty($iblockIdTp, $id_tp, "id", "asc", array("CODE" => "PROP_MAXYSS%"));
                                                        while ($ob = $res->GetNext()) {
                                                            if (is_array($prop_upd) && array_search($ob['CODE'], $prop_upd) !== false) {
                                                                $ar_val['VALUE'] = $ob['VALUE'];
                                                                $ar_val['DESCRIPTION'] = ($ob['DESCRIPTION'] == '') ? 'DEFAULT' : $ob['DESCRIPTION'];
                                                                $VALUES[$ob['CODE']][] = $ar_val;
                                                            }
                                                        }
                                                        if (!empty($VALUES)) {
                                                            foreach ($prop_upd as $code) {
                                                                if (is_array($VALUES[$code])) {
                                                                    foreach ($VALUES[$code] as $key => &$val) {
                                                                        $add = true;
                                                                        if ($val['DESCRIPTION'] == $cabinet) {
                                                                            $add = false;
                                                                            switch ($code) {
                                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
                                                                                    $val['VALUE'] = $card['nmID'];
                                                                                    break;
                                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
                                                                                    $val['VALUE'] = $sizes['chrtID'];
                                                                                    break;
//                                                                                            case 'PROP_MAXYSS_NMID_WB':
//                                                                                                $val['VALUE'] = $nomenclature['id'];
//                                                                                                break;
//                                                                                            case 'PROP_MAXYSS_CHRTID_WB':
//                                                                                                $val['VALUE'] = $card['nomenclatures'][0]['variations'][0]['id'];
//                                                                                                break;
                                                                                case 'PROP_MAXYSS_CARDID_WB':
                                                                                    $val['VALUE'] = array("VALUE" => $card['imtID'], "DESCRIPTION" => $cabinet);
                                                                                    break;
                                                                            }
                                                                        }
                                                                        if ($add) {
                                                                            switch ($code) {
                                                                                case 'PROP_MAXYSS_NMID_CREATED_WB':
                                                                                    $VALUES[$code][] = array("VALUE" => $card['nmID'], "DESCRIPTION" => $cabinet);
                                                                                    break;
                                                                                case 'PROP_MAXYSS_CHRTID_CREATED_WB':
                                                                                    $VALUES[$code][] = array("VALUE" => $sizes['chrtID'], "DESCRIPTION" => $cabinet);
                                                                                    break;
//                                                                                            case 'PROP_MAXYSS_NMID_WB':
//                                                                                                $VALUES[$code][] = array("VALUE" => $nomenclature['id'], "DESCRIPTION" => $cabinet);
//                                                                                                break;
//                                                                                            case 'PROP_MAXYSS_CHRTID_WB':
//                                                                                                $VALUES[$code][] = array("VALUE" => $card['nomenclatures'][0]['variations'][0]['id'], "DESCRIPTION" => $cabinet);
                                                                                    break;
                                                                                case 'PROP_MAXYSS_CARDID_WB':
                                                                                    $VALUES[$code][] = array("VALUE" => $card['imtID'], "DESCRIPTION" => $cabinet);
                                                                                    break;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        if (intval($id_tp) > 0 && !empty($VALUES)) {
                                                            CIBlockElement::SetPropertyValuesEx($id_tp, false, $VALUES);
                                                        }
                                                        $flag_found_barcode = true;
                                                        break;
                                                    } else {
                                                        $ar_mess[] = $barcode . " - " . GetMessage("MAXYSS_WB_CARD_FOUND");

                                                    }

                                                }
                                                if (!$flag_found_barcode) {
                                                    $res = \Bitrix\Main\Web\Json::encode(array('success' => 'MAXYSS_WB_DATA_SUCCESS'));
                                                }
                                            } else $ar_mess[] = 'NOT FOUND PROPERTY BARCODE';
//                                    }
                                        }
                                    }
                                }
                            }
                        }
                    }

                        if (!empty($ar_mess))
                            $res = \Bitrix\Main\Web\Json::encode(array('barcode_not_found' => implode(",\n", $ar_mess)));
                        else
                            $res = \Bitrix\Main\Web\Json::encode(array('success' => 'MAXYSS_WB_DATA_SUCCESS'));
                    }
                }

            }
        } else {
            $res = \Bitrix\Main\Web\Json::encode(array('success' => 'MAXYSS_WB_NO_CARD_ARTICLE'));
        }
    }else{
        $res = \Bitrix\Main\Web\Json::encode(array('success' => 'MAXYSS_WB_NO_CARD_ARTICLE'));
    }
    echo $res;
}

if($_REQUEST['action'] == 'delete_card'){
    $res = json_encode(array('error' => GetMessage('MAXYSS_WB_NO_CARD_ID')));
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $id_element = htmlspecialcharsbx($_REQUEST['product_id']);
        $iblockId = \CIBlockElement::getIBlockByID($id_element);

        $dbProp = CIBlockElement::GetProperty($iblockId, $id_element, "sort", "asc", array("CODE" => "PROP_MAXYSS_CARDID_WB"));
        if ($arProp = $dbProp->GetNext()) {
            $res = CMaxyssWb::DeleteCadrById(intval($arProp['VALUE']));
        }
    }
    echo $res;
}

if($_REQUEST['action'] == 'upload_stock_null'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        if (LANG_CHARSET == 'windows-1251') $request = CMaxyssWb::deepIconv($_REQUEST, 'windows-1251', 'UTF-8//IGNORE');
        else $request = $_REQUEST;

        if (isset($request['lk']) && $request['lk'] != '') $cabinet = $request['lk'];
        else $cabinet = 'DEFAULT';

        $arSettings = CMaxyssWb::settings_wb($cabinet);
        $items = CMaxyssWb::prepareAllItemsStock($arSettings, array());

        if (!empty($items["stocks"])) {
            foreach ($items["stocks"] as &$wh) {
                foreach ($wh as &$item) {
                    $item['amount'] = 0; // �������� �������
                }
            }
            $flag_error = false;
            foreach ($items["stocks"] as $wh => $skus) {
                $chunkItems = array_chunk($skus, 1000);
                foreach ($chunkItems as $key_chunk => $chunk_item) {
                    $data_string = array(
                        "stocks" => $chunk_item
                    );
                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                    $arResult = CRestQueryWB::rest_stock_na($base_url = WB_BASE_URL, $data_string, "/api/v3/stocks/" . $wh, $arSettings["AUTHORIZATION"], $cabinet);
//                echo '<pre>', print_r($arResult), '</pre>' ;
                    if ($arResult['error']) {
                        $flag_error = true;
                        $data_error = ' ';
                        if (is_array($arResult['error']["data"]) && !empty($arResult['error']["data"])) {
                            foreach ($arResult['error']["data"] as $data) {
                                $data_error .= $data['sku'] . ' ';
                            }
                        } elseif (!empty($arResult['error']["data"])) {
                            $data_error .= $arResult['error']["data"];
                        }
                        $ar_war = Array(
                            "MESSAGE" => GetMessage('MAXYSS_WB_UPLOAD_STOCK_ERROR') . GetMessage('WB_MAXYSS_LK_TITLE_TAB') . $cabinet . ' - ' . $arResult['error']["message"] . '  - ' . $data_error,
                            "TAG" => "MAXYSS_WB_" . Cutil::translit($cabinet, "ru") . '_' . $key_chunk,
                            "MODULE_ID" => "maxyss.wb",
                            'NOTIFY_TYPE' => 'E'
                        );
                        $ID_NOTIFY = CAdminNotify::Add($ar_war);
                    }
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPLOAD_STOCK', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $cabinet, "DESCRIPTION" => $data_string));
                }
            }
            if ($flag_error)
                echo \Bitrix\Main\Web\Json::encode(array('success' => false));
            else
                echo \Bitrix\Main\Web\Json::encode(array('success' => true));
        } else {
            echo \Bitrix\Main\Web\Json::encode(array('success' => true));
        }
    }else{
        echo \Bitrix\Main\Web\Json::encode(array('success' => false, 'error' => 'not right'));
    }
}

if($_REQUEST['action'] == 'upload_all_price'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        if (LANG_CHARSET == 'windows-1251') $request = CMaxyssWb::deepIconv($_REQUEST, 'windows-1251', 'UTF-8//IGNORE');
        else $request = $_REQUEST;
        if (isset($request['lk']) && $request['lk'] != '') $cabinet = $request['lk'];
        else $cabinet = 'DEFAULT';
        $arSettings = CMaxyssWb::settings_wb($cabinet);

        $res = CMaxyssWb::prepareAllItemsPrice($arSettings, $filter = array());
        if ($arSettings['LOG_ON'] == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'uploadAllPrice', "MODULE_ID" => 'maxyss.wb', "ITEM_ID" => $cabinet, "DESCRIPTION" => serialize($res["prices"])));
        }
        if (!empty($res["prices"])) {
            $result_price = CMaxyssWbprice::setPrices($arSettings["AUTHORIZATION"], $res["prices"]);
            if (!is_set($result_price['error'])) {
                echo \Bitrix\Main\Web\Json::encode(array('success' => "Y"));
            } else {
                echo \Bitrix\Main\Web\Json::encode(array('error' => $result_price['error']));
            }
        }
    }else{
        echo \Bitrix\Main\Web\Json::encode(array('error' => 'not right'));
    }
}
if($_REQUEST['action'] == 'upload_discounts'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        if (LANG_CHARSET == 'windows-1251') $request = CMaxyssWb::deepIconv($_REQUEST, 'windows-1251', 'UTF-8//IGNORE');
        else $request = $_REQUEST;

        if (isset($request['lk']) && $request['lk'] != '') $cabinet = $request['lk'];
        else $cabinet = 'DEFAULT';

        $arSettings = CMaxyssWb::settings_wb($cabinet);

        $res = CMaxyssWb::prepareAllItemsPrice($arSettings, $filter = array());
        $result_discounts = array();
        $result_revoke_discounts = array();
        if ($arSettings['LOG_ON'] == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'upload_discounts_and_revoke', "MODULE_ID" => 'maxyss.wb', "ITEM_ID" => $cabinet, "DESCRIPTION" => serialize($res)));
        }
        if (!empty($res["discounts"])) {
            $result_discounts = CMaxyssWbprice::setDiscounts($arSettings["AUTHORIZATION"], $res["discounts"]);
        }
        if (!empty($res["discounts_revoke"])) {
            $result_revoke_discounts = CMaxyssWbprice::revokeDiscounts($arSettings["AUTHORIZATION"], $res["discounts_revoke"]);
        }
        if (!is_set($result_discounts['error']) && !is_set($result_revoke_discounts['error'])) {
            echo \Bitrix\Main\Web\Json::encode(array('success' => "Y"));
        } else {
            echo \Bitrix\Main\Web\Json::encode(array('error' => $result_discounts['error'] . '.   <br>' . $result_revoke_discounts['error']));
        }
    }else{
        echo \Bitrix\Main\Web\Json::encode(array('error' => 'not right'));
    }
}

if($_REQUEST['action'] == 'print_label_wb'){
    if(is_set($_REQUEST['orders']) && $GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {

        $order_ids = $_REQUEST['orders'];
        if(count($order_ids)>0) {
            $arSettings = CMaxyssWb::settings_wb();
            // find order to Bitrix
            $arFilterOrder = array(
                'ID' => $order_ids,
                "!PROPERTY_VAL_BY_CODE_MAXYSS_WB_NUMBER" => false,
            );
            $rsOrders = \CSaleOrder::GetList(
                array('ID' => 'ASC'),
                $arFilterOrder
            );
            $arSvg = array();
            while ($arOrder = $rsOrders->Fetch()) {
                $order = Bitrix\Sale\Order::load($arOrder['ID']);

                $obProps = Bitrix\Sale\Internals\OrderPropsValueTable::getList(array('filter' => array('ORDER_ID' => $arOrder["ID"])));
                while($prop = $obProps->Fetch()){

                    if($prop['CODE'] == "MAXYSS_WB_NUMBER") {
                        $content = '';
                        if($prop["VALUE"] != '') {
                            $FPPath = $_SERVER["DOCUMENT_ROOT"] . '/upload/wb/' . $prop["VALUE"].FILE_TYPE_STIKER;
                            if($prop["VALUE"] != '') $flag_button = true;
                            if(!file_exists($FPPath))
                                $arWbOrders[$arOrder['ID']]["MAXYSS_WB_NUMBER"] = intval($prop["VALUE"]);  /// ���� ���-�� �� ��������� � ���� �� ���������
                            else
                            {
                                $content = file_get_contents($FPPath);
                                preg_match('/<svg.+?[^"]+">.+?[^<]+<\/svg>/s', $content, $matches);
                                $matches[0] = str_replace(array("\r","\n"),"",$matches[0]);
                                if($content != '')
                                    $arSvg[]['svg'] = $matches[0];
                            }
                        }
                    }
//                    if($prop['CODE'] == "MAXYSS_WB_CABINET") {
//                        if($prop["VALUE"] != '') {
//                            $arWbOrders[$arOrder['ID']]["MAXYSS_WB_CABINET"] = ($prop["VALUE"] != '')? $prop["VALUE"] : 'DEFAULT' ;
//                        }
//                    }
                }
            }
            /*
            if(!empty($arWbOrders)){
                foreach ($arWbOrders as $o){
                    $cabinet[$o["MAXYSS_WB_CABINET"]][] = $o["MAXYSS_WB_NUMBER"];
                }

                foreach ($cabinet as $key=>$c){
                    $content = '';
                    $Authorization = $arSettings["AUTHORIZATION"][$key];
                    $data_string = array(
                        "orderIds" => $c
                    );
                    $data_string = \Bitrix\Main\Web\Json::encode($data_string);
                    $arResult = CRestQueryWB::rest_stickers($base_url = WB_BASE_URL, $data_string, "/api/v2/orders/stickers", $Authorization);
                    if(!$arResult["error"] && !empty($arResult["data"])){
                        CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/upload/wb/");
                        foreach ($arResult["data"] as $val){
                            $image = base64_decode($val["sticker"]["wbStickerSvgBase64"]);
                            $FPName = $val["orderId"].'.svg';
                            $FPPath = $_SERVER["DOCUMENT_ROOT"].'/upload/wb/'.$FPName;
                            file_put_contents($FPPath, $image, LOCK_EX);

                            $content = file_get_contents($_SERVER["DOCUMENT_ROOT"].'/upload/wb/' . $val["orderId"] . '.svg');
                            preg_match('/<svg.+?[^"]+">.+?[^<]+<\/svg>/s', $content, $matches);
                            $matches[0] = str_replace(array("\r","\n"),"",$matches[0]);
                            if($content != '')
                                $arSvg[]['svg'] = $matches[0];
                        }
                    }
                }
            }
            */
        }

        if(!empty($arSvg))
            echo \Bitrix\Main\Web\Json::encode(array('success' =>$arSvg));
        else
            echo \Bitrix\Main\Web\Json::encode(array('error' => GetMessage('MAXYSS_OZON_NO_ORDER_SELECTED')));;
    }
    else
        echo \Bitrix\Main\Web\Json::encode(array('error' => GetMessage('MAXYSS_OZON_NO_ORDER_SELECTED')));
}

if($_REQUEST['action'] == 'get_all_lk'){
    $arLkWb=array();
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R")
        $arLkWb = unserialize(Option::get(MAXYSS_WB_NAME, "LK_WB_DATA", ""));
    echo \Bitrix\Main\Web\Json::encode($arLkWb);
}
if($_REQUEST['action'] == 'add_lk'){
    if($_REQUEST['uuid'] !='' && $_REQUEST['authorization'] !=''&& $_REQUEST['name_lk'] !='' && $GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) == "W") {

        $arLkWb = unserialize(Option::get(MAXYSS_WB_NAME, "LK_WB_DATA", ""));
        $arLkUuid = unserialize(Option::get(MAXYSS_WB_NAME, "UUID", ""));
        $arLkAuthorization = unserialize(Option::get(MAXYSS_WB_NAME, "AUTHORIZATION", ""));
        if(LANG_CHARSET == 'windows-1251') $request = CMaxyssWb::deepIconv($_REQUEST, 'windows-1251', 'UTF-8//IGNORE');
        else $request = $_REQUEST;

        if($arLkWb === false){
            $Authorization = Option::get(MAXYSS_WB_NAME, "AUTHORIZATION", "");
            $supplierID = Option::get(MAXYSS_WB_NAME, "UUID", "");

            Option::set(MAXYSS_WB_NAME, "LK_WB_DATA", serialize(
                    array(
                        'DEFAULT'=>array(
                            'uuid'=>$supplierID,
                            'authorization'=>$Authorization
                        ),
                        htmlspecialcharsbx($request['name_lk']) => array(
                            'uuid'=>htmlspecialcharsbx($request['uuid']),
                            'authorization'=>htmlspecialcharsbx($request['authorization'])
                        )
                    )
                )
            );
            echo \Bitrix\Main\Web\Json::encode(array(
                'TYPE'=>"SUCCESS",
                'MESSAGE'=>GetMessage('WB_MAXYSS_ADD_LK_ADD_SUCCESS_ADD'),
            ));
        }
        else
        {
            if(array_key_exists(htmlspecialcharsbx($request['name_lk']), $arLkWb)){
                echo \Bitrix\Main\Web\Json::encode(array(
                    'TYPE'=>"ERROR",
                    'MESSAGE'=>GetMessage('WB_MAXYSS_ADD_LK_ADD_SUCCESS_ADD'),
                ));
            }
            else
            {
                $arLkWb[htmlspecialcharsbx($request['name_lk'])] = array(
                    'uuid' => htmlspecialcharsbx($request['uuid']),
                    'authorization' => htmlspecialcharsbx($request['authorization'])
                );
                $arLkUuid[htmlspecialcharsbx($request['name_lk'])] = htmlspecialcharsbx($request['uuid']);
                $arLkAuthorization[htmlspecialcharsbx($request['name_lk'])] = htmlspecialcharsbx($request['authorization']);
                Option::set(MAXYSS_WB_NAME, "LK_WB_DATA", serialize($arLkWb));
                Option::set(MAXYSS_WB_NAME, "UUID", serialize($arLkUuid));
                Option::set(MAXYSS_WB_NAME, "AUTHORIZATION", serialize($arLkAuthorization));

                echo \Bitrix\Main\Web\Json::encode(array(
                    'TYPE'=>"SUCCESS",
                    'MESSAGE'=>GetMessage('WB_MAXYSS_ADD_LK_ADD_SUCCESS_ADD'),
                ));
            }
        }

    }
    else
    {
        echo \Bitrix\Main\Web\Json::encode(array(
            'TYPE'=>"ERROR",
            'MESSAGE'=>GetMessage('WB_MAXYSS_ADD_LK_ADD_ERROR_FILLED'),
        ));
    }
}

if($_REQUEST['action'] == 'get_object_new_api_content'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $object = $_REQUEST['name'];
        $res = CAddinMaxyssWB::getObjectCharacteristicsFilter($object);
        if (LANG_CHARSET == 'windows-1251') $res = CMaxyssWb::deepIconv($res, 'UTF-8', 'windows-1251//IGNORE');
        echo $res;
    }
}

if($_REQUEST['action'] == 'get_directory'){
    $res = '';
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R")
        $res = CAddinMaxyssWB::getDirectory($_REQUEST['dictionari'], $_REQUEST['pattern'], $_REQUEST['option']);
    if(LANG_CHARSET == 'windows-1251') $res = CMaxyssWb::deepIconv($res, 'UTF-8', 'windows-1251//IGNORE');
    echo $res;
}
if($_REQUEST['action'] == 'add_prop_sinc'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        if (strlen($_REQUEST['attr_id']) > 0) {
            $attr_id = htmlspecialcharsbx($_REQUEST['attr_id']);
            if (LANG_CHARSET == 'windows-1251') $attr_id = CMaxyssWb::deepIconv($_REQUEST['attr_id'], 'UTF-8', 'windows-1251//IGNORE');


            global $DB;
            $row = $DB->Query("SELECT * FROM b_option WHERE NAME='MAXYSS_SINC_WB_ATTR'")->Fetch();
            $arSinc = unserialize($row['VALUE']);

            if ($_REQUEST['iblock_id'] > 0) {

                // �������� �����
                $iblock_info = CCatalog::GetByIDExt(intval($_REQUEST["iblock_id"]));

                if (is_array($iblock_info)) {
                    if ($iblock_info['PRODUCT_IBLOCK_ID'] == intval($_REQUEST["iblock_id"]) || ($iblock_info['PRODUCT_IBLOCK_ID'] == 0 && $iblock_info['OFFERS_IBLOCK_ID'] == 0)) {
                        $iblock_id = intval($_REQUEST["iblock_id"]);
                        $iblock_offers_id = $iblock_info["OFFERS_IBLOCK_ID"];
                    } else {
                        $iblock_id = $iblock_info['PRODUCT_IBLOCK_ID'];
                        $iblock_offers_id = intval($_REQUEST["iblock_id"]);
                    }
                } else {
                    $iblock_id = intval($_REQUEST["iblock_id"]);
                }

                $iblock_props_select = GetMessage('MAXYSS_WB_IBLOCK_BASE') . '<br><br><div class="answer_prop_' . $iblock_id . '"><select name="prop_bx[' . $iblock_id . ']"><option value=""></option>';


                $filterProp = array();

//                if($arProps['is_collection']['VALUE'] != 1) $filterProp['MULTIPLE'] = 'N';

                $res_br = CIBlock::GetProperties($iblock_id, Array('name' => 'asc'), $filterProp);
                while ($res_arr_br = $res_br->Fetch()) {
                    if ($res_arr_br['CODE'] == '' || $res_arr_br['PROPERTY_TYPE'] == 'F') continue;
                    $selected = '';
                    if ($arSinc[$attr_id][$iblock_id] == $res_arr_br['CODE'])
                        $selected = 'selected = "selected"';

                    $iblock_props_select .= '<option ' . $selected . ' value="' . $res_arr_br['CODE'] . '">' . '[' . $res_arr_br['ID'] . '] ' . $res_arr_br['NAME'] . '</option>';
                }

                $iblock_props_select .= '</select><div class="answer_prop_values_' . $iblock_id . '"><br><table id="table_prop_values_' . $iblock_id . '"></table></div>';

                $iblock_offers_props_select = '';
                if ($iblock_offers_id > 0) {
                    $iblock_offers_props_select = '<br><br>' . GetMessage('MAXYSS_WB_IBLOCK_OFFERS') . '<br><br><div class="answer_prop_' . $iblock_offers_id . '"><select name="prop_bx[' . $iblock_offers_id . ']"><option value=""></option>';

                    $res_br = CIBlock::GetProperties($iblock_offers_id, Array('name' => 'asc'), $filterProp);
                    while ($res_arr_br = $res_br->Fetch()) {
                        if ($res_arr_br['CODE'] == '') continue;
                        $selected = '';
                        if ($arSinc[$attr_id][$iblock_offers_id] == $res_arr_br['CODE'])
                            $selected = 'selected = "selected"';

                        $iblock_offers_props_select .= '<option ' . $selected . ' value="' . $res_arr_br['CODE'] . '">' . '[' . $res_arr_br['ID'] . '] ' . $res_arr_br['NAME'] . '</option>';

                    }

                    $iblock_offers_props_select .= '</select></div><div class="answer_prop_values_' . $iblock_offers_id . '"><br><table id="table_prop_values_' . $iblock_offers_id . '"></table></div>';
                }


            }
            echo $iblock_props_select . $iblock_offers_props_select . '<input type="hidden" name="attr_id" value="' . $attr_id . '"><input type="hidden" name="span_id" value="' . intval($_REQUEST['span_id']) . '"><input type="hidden" name="type_input" value="' . htmlspecialcharsbx($_REQUEST['type_input']) . '"><input type="hidden" name="max_count" value="' . intval($_REQUEST['max_count']) . '">';
        }
    }
}

if($_REQUEST['action'] == 'save_prop_sinc_values'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        global $DB;
        $row = $DB->Query("SELECT * FROM b_option WHERE NAME='MAXYSS_SINC_WB_ATTR'")->Fetch();
        $arSinc = unserialize($row['VALUE']);
        $attr = htmlspecialcharsbx($_REQUEST['attr_id']);
        $type_input = $_REQUEST['type_input'];
        if (LANG_CHARSET == 'windows-1251') $attr = CMaxyssWb::deepIconv($_REQUEST['attr_id'], 'UTF-8', 'windows-1251//IGNORE');
        if (is_array($arSinc)) {
            if (isset($arSinc[$attr])) {
                $new_attr = array_replace($arSinc[$attr], $_REQUEST['prop_bx']);
                $arSinc[$attr] = $new_attr;
            } else $arSinc[$attr] = $_REQUEST['prop_bx'];
        } else {
            $arSinc = array();
            $arSinc[$attr] = $_REQUEST['prop_bx'];
        }
        $arSinc[$attr]['t'] = $_REQUEST['type_input'];
        $arSinc[$attr]['c'] = $_REQUEST['max_count'];
        \Bitrix\Main\Config\Option::set(MAXYSS_WB_NAME, 'MAXYSS_SINC_WB_ATTR', serialize($arSinc));
    }
    echo '';
}

if($_REQUEST['action'] == 'get_object_filter' && strlen($_REQUEST['pattern'])>0){
    $list_obj = array();
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $list_obj = CAddinMaxyssWB::getObjectFilter(htmlspecialcharsbx($_REQUEST['pattern']));
    }
    echo \Bitrix\Main\Web\Json::encode($list_obj);
}

if($_REQUEST['action'] == 'add_supplie'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $supplies = new CMaxyssWbSupplies($_REQUEST['cabinet']);
        $name_supplie = htmlspecialcharsbx($_REQUEST['name_supplie']);
        if (LANG_CHARSET == 'windows-1251')
            $name_supplie = \Bitrix\Main\Text\Encoding::convertEncoding(
                $name_supplie,
                'UTF-8',
                'windows-1251',
                $errorMessage = ""
            );
        $arSupplie = $supplies->addSupplies($name_supplie);
        echo \Bitrix\Main\Web\Json::encode($arSupplie);
    }
}

if($_REQUEST['action'] == 'delete_supplie'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $supplies = new CMaxyssWbSupplies($_REQUEST['cabinet']);
        $arSupplie = $supplies->deleteSupplies(htmlspecialcharsbx($_REQUEST['id_supplie']));
        echo \Bitrix\Main\Web\Json::encode($arSupplie);
    }
}
if($_REQUEST['action'] == 'deliver_supplie'){
    $arSupplie = array();
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R") {
        $supplies = new CMaxyssWbSupplies($_REQUEST['cabinet']);
        $arSupplie = $supplies->deliverSupplie(htmlspecialcharsbx($_REQUEST['id_supplie']));
        echo \Bitrix\Main\Web\Json::encode($arSupplie);
    }
}

if($_REQUEST['action'] == 'stock_realy'){
    if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_WB_NAME) >= "R"){
        if($_REQUEST['LK_WB']) $cabinet = $_REQUEST['LK_WB']; else $cabinet = 'DEFAULT';
        if (LANG_CHARSET == 'windows-1251') $cabinet = CMaxyssWb::deepIconv($cabinet, 'UTF-8', 'windows-1251//IGNORE');
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
            $arFilter = Array(
                array(
                    "LOGIC" => "OR",
                    array("!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false),
                    array("!PROPERTY_PROP_MAXYSS_CARDID_WB" => false),
//                    array("!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false),
                ),
                "IBLOCK_ID" => $iblock_id,
                ">ID"=>$_REQUEST['ID']);
        //        $arFilter = Array("!PROPERTY_PROP_MAXYSS_NMID_CREATED_WB" => false,"IBLOCK_ID" => $iblock_id);
            $dbTp = CIBlockElement::GetList(Array("ID"=>"asc"), $arFilter, false, array('nTopCount'=> Option::get(MAXYSS_WB_NAME, "COUNT_STEP_EL", 200)), $arSelect);
            $arSkus = array();
            while ($arTp = $dbTp->GetNextElement()) {
                if(count($arSkus)>99) break;
                $ar_tovar = array();
                $key_cab = false;
                $arFields = $arTp->GetFields();
                $arProps = $arTp->GetProperties();

                $next_id = $arFields['ID'];

                if(is_array($arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']))
                    $key_cab = (is_array($arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) && array_search($cabinet, $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) !== false)? array_search($cabinet, $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']) : array_search($cabinet_, $arProps['PROP_MAXYSS_NMID_CREATED_WB']['DESCRIPTION']);
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
        ?>
        <?
        if(!empty($arElem)) {
            $key_item = htmlspecialcharsbx($_REQUEST['key_item']);
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
                        ?>
                    </td>
                </tr>
                <?
            }?>
            <tr><td style="border-bottom: 0px" colspan="7"><input type="button" onclick="get_next_elements('<?=$next_id?>', '<?=$key_item?>', this)" value="<?=GetMessage('WB_MAXYSS_MORE_REC')?>"></td></tr>
            <?
        }
        else
        {?>
            <tr><td style="border-bottom: 0px" colspan="7"><?echo GetMessage("WB_MAXYSS_NOT_MORE_SINC_PRODUCT");?></td></tr>
        <?}?>
    <?}
}?>
