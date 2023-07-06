<?
use \Bitrix\Main\Config\Option;

class CMaxyssWbprice{
	public static $fileExclude = '/home/bitrix/ext_www/tempusshop.ru/upload/wb/items_excluded.txt';// op
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

    public static function setPrices($Authorization = false, $items, $attempt = 1){
		
        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");
		if($attempt > 3) return;
		if($attempt == 1){
			fopen(self::$fileExclude, "w+");
		}
        $event = new \Bitrix\Main\Event(MAXYSS_WB_NAME, "OnPriceUpload", array(&$items, $Authorization));
        $event->send();
		

		
        $arResult = array();
        $items_chunk = array_chunk($items, 1000);

        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/upload/log_items.txt', print_r($items_chunk, true));
        $err = '';
		$arExclude = array();
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
					
					// запоминаем ошибки. при следующем запуске исключаем их
                    foreach ($res_error['errors'] as $val){
                        $err .= $val;
						//Рост более 30 процентов
						if(strripos($val, "Рост более ")){
							preg_match("/\[(.*?)\]/ism", $val, $output);
							if($output[1]){
								$list = explode(" ", $output[1]);
								if(count($list) > 0){
									$arExclude = array_merge($arExclude, $list);
								}
							}
							
						}
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
            }else{
				file_put_contents("/home/bitrix/logs/wb/wb_err.txt", print_r(json_decode($c, true), true) . "\r\n", FILE_APPEND | LOCK_EX);
				file_put_contents("/home/bitrix/logs/wb/wb_err.txt", print_r(json_decode($bck, true), true) . "\r\n", FILE_APPEND | LOCK_EX);
			}
        }
		
		if(count($arExclude) > 0){
			foreach($arExclude as $nmId){
				file_put_contents(self::$fileExclude, $nmId . "\r\n", FILE_APPEND | LOCK_EX);
			}
			CMaxyssWbprice::prepareSetPrice($items);
			$eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'setPrices', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "PRICE", "DESCRIPTION" => "Найдено " . count($arExclude) . " ошибок. Шлем без них. Попытка - {$attempt}." ));
                
			self::setPrices($Authorization, $items, $attempt + 1);
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

	public static function prepareSetPrice(&$items){
		$res = file_get_contents(self::$fileExclude); 

		$arNmId = explode("\r\n", $res);
		
		foreach ($items as $key => $arItem){
			if(in_array($arItem["nmId"], $arNmId)){
				unset($items[$key]);
			}
		}
	}
}