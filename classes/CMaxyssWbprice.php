<?
use \Bitrix\Main\Config\Option;

class CMaxyssWbprice{
    public static function getPrices($Authorization = false){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

        $arResult = array();
        $data_string = array(
            "quantity"=>0
        );
        $data_string = \Bitrix\Main\Web\Json::encode($data_string);

        $bck = CMaxyssWb::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {
            $result = CRestQueryWB::rest_price_get('https://suppliers-api.wildberries.ru', $data_string, "/public/api/v1/info", $Authorization);
            if(strlen($result)>0 && strpos($result, '}') && $result!='invalid token')
                $arResult = \Bitrix\Main\Web\Json::decode($result);
            else
                $arResult = $result;
        }
        return $arResult;
    }

    public static function setPrices($Authorization = false, $items){
        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnPriceUpload", array(&$items, $Authorization));
        $event->send();

        $arResult = array();
        $items_chunk = array_chunk($items, 1000);

        $err = '';
        foreach ($items_chunk as $c) {
            $data_string = $c;
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);

            $bck = CMaxyssWb::bck_wb();

            if($bck['BCK'] && $bck['BCK'] != "Y") {
                $res = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/public/api/v1/prices", $Authorization);
                $result = array();
                if($res !='')
                    $result = \Bitrix\Main\Web\Json::decode($res);

                if(!is_set($result['error'])){
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'setPrices', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "PRICE", "DESCRIPTION" => "upload price success" ));
                }else{
                    $res_error = \Bitrix\Main\Web\Json::decode($result['error']);
                    foreach ($res_error['errors'] as $val){
                        $err .= $val;
                    }
//                    if(LANG_CHARSET != 'utf-8') {
//                        $arResult["error"] = \Bitrix\Main\Text\Encoding::convertEncoding(
//                            $result['errors'],
//                            'utf-8',
//                            'windows-1251',
//                            $errorMessage = ""
//                        );
//                    }else{
                        $arResult["error"] = $err." ";
//                    }
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'setPrices', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "PRICE", "DESCRIPTION" => implode(', ', $res_error['errors']) ));
                }
            }
        }
        return $arResult;
    }

    public static function setDiscounts($Authorization = false, $items){
        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");
        $arResult = array();
        $items_chunk = array_chunk($items, 1000);

        $err = '';
        $bck = CMaxyssWb::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {

            foreach ($items_chunk as $c) {
                $data_string = $c;
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);

                $res = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/public/api/v1/updateDiscounts", $Authorization);
                $result = array();
                if ($res != '')
                    $result = \Bitrix\Main\Web\Json::decode($res);

                if (!is_set($result['error'])) {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'setDiscounts', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "Discounts", "DESCRIPTION" => "upload Discounts success"));
                } else {
                    $res_error = \Bitrix\Main\Web\Json::decode($result['error']);
                    foreach ($res_error['errors'] as $val) {
                        $err .= $val;
                    }
                    if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                        $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                            $res_error['errors'],
                            LANG_CHARSET,
                            'windows-1251',
                            $errorMessage = ""
                        );
                    } else $descr = $res_error['errors'];
                    $arResult["error"] = $err . " ";
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'setDiscounts', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "Discounts", "DESCRIPTION" => implode(', ', $descr)));
                }
            }
        }
        return $arResult;
    }

    public static function revokeDiscounts($Authorization = false, $items){
        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");
        $arResult = array();
        $items_chunk = array_chunk($items, 1000);

        $err = '';
        $bck = CMaxyssWb::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {

            foreach ($items_chunk as $c) {
                $data_string = $c;
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);

                $res = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/public/api/v1/revokeDiscounts", $Authorization);
                $result = array();
                if ($res != '')
                    $result = \Bitrix\Main\Web\Json::decode($res);

                if (!is_set($result['error'])) {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'revokeDiscounts', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "revokeDiscounts", "DESCRIPTION" => "revoke Discounts success"));
                } else {
                    $res_error = \Bitrix\Main\Web\Json::decode($result['error']);
                    foreach ($res_error['errors'] as $val) {
                        $err .= $val;
                    }
                    if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                        $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                            $res_error['errors'],
                            LANG_CHARSET,
                            'windows-1251',
                            $errorMessage = ""
                        );
                    } else $descr = $res_error['errors'];
                    $arResult["error"] = $err . " ";
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'revokeDiscounts', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "revokeDiscounts", "DESCRIPTION" => implode(', ', $descr)));
                }
            }
        }
        return $arResult;
    }

    public static function setPromocodes($Authorization = false, $items){
        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");
        $arResult = array();
        $items_chunk = array_chunk($items, 1000);

        $err = '';
        $bck = CMaxyssWb::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {

            foreach ($items_chunk as $c) {
                $data_string = $c;
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);

                $res = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/public/api/v1/updatePromocodes", $Authorization);
                $result = array();
                if ($res != '')
                    $result = \Bitrix\Main\Web\Json::decode($res);

                if (!is_set($result['error'])) {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'setPromocodes', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "Promocodes", "DESCRIPTION" => "upload Promocodes success"));
                } else {
                    $res_error = \Bitrix\Main\Web\Json::decode($result['error']);
                    foreach ($res_error['errors'] as $val) {
                        $err .= $val;
                    }
                    if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                        $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                            $res_error['errors'],
                            LANG_CHARSET,
                            'windows-1251',
                            $errorMessage = ""
                        );
                    } else $descr = $res_error['errors'];
                    $arResult["error"] = $err . " ";
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'setPromocodes', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "Promocodes", "DESCRIPTION" => implode(', ', $descr)));
                }
            }
        }
        return $arResult;
    }

    public static function revokePromocodes($Authorization = false, $items){
        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");
        $arResult = array();
        $items_chunk = array_chunk($items, 1000);

        $err = '';
        $bck = CMaxyssWb::bck_wb();
        if($bck['BCK'] && $bck['BCK'] != "Y") {

            foreach ($items_chunk as $c) {
                $data_string = $c;
                $data_string = \Bitrix\Main\Web\Json::encode($data_string);

                $res = CRestQueryWB::rest_query_na($base_url = WB_BASE_URL, $data_string, "/public/api/v1/revokePromocodes", $Authorization);
                $result = array();
                if ($res != '')
                    $result = \Bitrix\Main\Web\Json::decode($res);

                if (!is_set($result['error'])) {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'revokePromocodes', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "revokePromocodes", "DESCRIPTION" => "revoke Promocodes success"));
                } else {
                    $res_error = \Bitrix\Main\Web\Json::decode($result['error']);
                    foreach ($res_error['errors'] as $val) {
                        $err .= $val;
                    }
                    if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                        $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                            $res_error['errors'],
                            LANG_CHARSET,
                            'windows-1251',
                            $errorMessage = ""
                        );
                    } else $descr = $res_error['errors'];
                    $arResult["error"] = $err . " ";
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'revokePromocodes', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "revokePromocodes", "DESCRIPTION" => implode(', ', $descr)));
                }
            }
        }
        return $arResult;
    }
}