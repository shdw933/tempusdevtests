<?
use \Bitrix\Main\Config\Option;
class CRestQueryWB{
    public static function rest_query_na($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

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
                    'Content-Length: ' . strlen($data_string),
                    'Authorization: ' . $Authorization,
                )
            )
        ]);


        $str_result = $api->post($path, []);
//        echo '<pre>', print_r($str_result), '</pre>' ;
        if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
        $eventLog = new \CEventLog;
        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "str_result", "DESCRIPTION" => serialize($str_result) ));
        }

        if ($str_result->info->http_code == 200) {
            $res = $str_result->response;
            return $res;
        }
        elseif ($str_result->info->http_code == 502)
        {
            $res = \Bitrix\Main\Web\Json::encode(array('error' =>'HTTP/1.1 502 Bad Gateway'));
//            $res['error'] = 'HTTP/1.1 502 Bad Gateway';
            if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
                if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                    $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                        $str_result->response,
                        'UTF-8',
                        'windows-1251',
                        $errorMessage = ""
                    );
                } else $descr = $str_result->response;
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'REST_QUERY_NA', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "REST_QUERY_NA", "DESCRIPTION" => $descr . '; ' . $str_result->info->http_code));
            }
            return $res;
        }
        elseif ($str_result->info->http_code == 504)
        {
            $res = \Bitrix\Main\Web\Json::encode(array('error' =>'HTTP/1.1 504 Gateway Timeout'));
//            $res['error'] = 'HTTP/1.1 504 Gateway Timeout';
            if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
                if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                    $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                        $str_result->response,
                        'UTF-8',
                        'windows-1251',
                        $errorMessage = ""
                    );
                } else $descr = $str_result->response;

                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'REST_QUERY_NA', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "REST_QUERY_NA", "DESCRIPTION" => $descr . '; ' . $str_result->info->http_code));
            }
            return $res;
        }
        else
        {
            $res = \Bitrix\Main\Web\Json::encode(array('error' =>$str_result->response));
            if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
                if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                    $str_result->response,
                    'UTF-8',
                    'windows-1251',
                    $errorMessage = ""
                );
            } else $descr = $str_result->response;
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'REST_QUERY_NA', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "REST_QUERY_NA", "DESCRIPTION" => $descr . '; ' . $str_result->info->http_code));
            }
            return $res;
        }
    }
    public static function rest_stock_na($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false, $cabinet){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string),
                    'Authorization: ' . $Authorization,
                )
            )
        ]);


        $str_result = $api->post($path, []);

		$Logger = new TsLogger("/wb/" . str_replace("/", "_", $path) . "/");
		$Logger->log("LOG", "data_string - ".print_r($data_string, true));
		$Logger->log("LOG", "str_result - ".print_r($str_result, true));

        if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "str_result", "DESCRIPTION" => serialize($str_result)));
        }

        $res = array();
        if ($str_result->info->http_code == 204) {
            $res['success'] = true;
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPLOAD_STOCK_RESULT', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $cabinet, "DESCRIPTION" => 'success' ));

            if(strlen($str_result->response) > 0){

                if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                    $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                        $str_result->response,
                        'UTF-8',
                        'windows-1251',
                        $errorMessage = ""
                    );
                } else $descr = $str_result->response;

                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPLOAD_STOCK_NOTE', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $cabinet, "DESCRIPTION" => $descr ));
            }

        }else{
            if (strlen($str_result->response)>0) {
                if (strpos($str_result->response, '}')) {
                    $descr = \Bitrix\Main\Web\Json::decode($str_result->response);
                }
            }
            $res['error'] = $descr[0];
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPLOAD_STOCK_RESULT', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $cabinet, "DESCRIPTION" => serialize($descr)));
        }
        return $res;
    }
    public static function rest_stickers($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

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
                    'Content-Length: ' . strlen($data_string),
                    'Authorization: ' . $Authorization,

                )
            )
        ]);


        $str_result = $api->post($path, []);

        if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "str_result", "DESCRIPTION" => serialize($str_result)));
        }
        if ($str_result->info->http_code == 200) {

            if(strlen($str_result->response) > 0){
                $result =  \Bitrix\Main\Web\Json::decode($str_result->response);
            }

        }else{
            $result = array("error"=>true);
        }
        return $result;
    }

    public static function rest_warehouses_get($Authorization = false, $base_url = WB_BASE_URL, $data_string = '', $path='/api/v2/warehouses')
    {

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");
        if(strlen($Authorization) > 0) {

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
                        'Content-Length: 0',
                        'Authorization: ' . $Authorization,
                    )
                )
            ]);


            $str_result = $api->post($path, []);

            if($str_result->info->http_code == 401){
                echo '<b style="color: red">'.$str_result->response.'</b>';
            }
            elseif (strlen($str_result->response) > 0 && $str_result->info->http_code == 200)
                return \Bitrix\Main\Web\Json::decode($str_result->response);
            else
                return '';
//
//            if (strlen($str_result->response) > 0 && $str_result->info->http_code != '401')
//                return \Bitrix\Main\Web\Json::decode($str_result->response);
//            else
//                return $str_result->response;
        }else{
            return '';
        }
    }

    public static function rest_stock_get($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

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
//                        'Content-Length: 0',
                        'Authorization: ' . $Authorization,

                    )
                )
            ]);


            $str_result = $api->post($path, []);
            if ($str_result->info->http_code == 200 && strlen($str_result->response)>0) {
                $res =  \Bitrix\Main\Web\Json::decode($str_result->response);
            }else{
                if (strlen($str_result->response)>0)   $res = $str_result->response.'; '.$str_result->info->http_code;
                else $res = $str_result->info->http_code;

                if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
                    $eventLog = new \CEventLog;
                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "str_result", "DESCRIPTION" => serialize($str_result)));
                }
            }
            return $res;
        }

    public static function NewGuid() {
        if (function_exists('com_create_guid')){
            /**
             * @var Guid $uuid
             */
            $uuid = com_create_guid();
            return $uuid;
        }
        else {
            mt_srand((double)microtime()*10000);
            $charid = strtolower(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            /**
             * @var Guid $uuid
             */
            $uuid =  substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

    public static function makeCurlFile($file){

        $mime = mime_content_type($file);
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new CURLFile($file, $mime, $name);

        return $output;
    }

    public static function rest_file_na($base_url = WB_BASE_URL, $data_string, $path, $uuid, $Authorization){
        $file_id = self::NewGuid();
        $curl_file = self::makeCurlFile($data_string['file']);
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_POST => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => array('uploadfile'=>$curl_file),
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: multipart/form-data',
                    'Authorization: ' . $Authorization,
                    'X-File-Id: ' . $file_id,
                    'X-Supplier-ID: ' . $uuid
                )
            )
        ]);


        $str_result = $api->post($path, []);
        if ($str_result->info->http_code == 200) {
            $res['value'] = $file_id;
            $res['units'] = $curl_file->mime;
            return $res;
        } else {
            $res = '';
            return $res;
        }
    }

    public static function rest_price_get($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false)
    {
        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

        $res = '';

        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $Authorization,
//                    'X-Supplier-ID: ' . CMaxyssWb::get_setting_wb_for_auth('UUID', $Authorization),
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string),
                )
            )
        ]);


        $str_result = $api->post($path, []);

        if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "str_result", "DESCRIPTION" => serialize($str_result)));
        }

        if ($str_result->info->http_code == 200) {
            $res = $str_result->response;
        }elseif($str_result->info->http_code == 401){
            $res = $str_result->response;// text
        }

        return $res;
    }

    public static function rest_order_na($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

        if($path == '/api/v3/orders/status') $metod = 'POST'; else $metod = 'GET';
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => $metod,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $Authorization,
                    'Content-Type: application/json',
                )
            )
        ]);


        $str_result = $api->post($path, []);

        if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "str_result", "DESCRIPTION" => serialize($str_result)));
        }

        if($str_result->info->http_code == 200)
        {
            $res = \Bitrix\Main\Web\Json::decode($str_result->response);
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'GET_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "ORDER", "DESCRIPTION" => 'success' ));
        }
        elseif ($str_result->info->http_code == 204)
        {
            $res = array('note' => 'no orders');
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'GET_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "ORDER", "DESCRIPTION" => 'no orders' ));
        }
        else
        {
            if($str_result->response !='') {
                $res = \Bitrix\Main\Web\Json::decode($str_result->response);
                $res['http_code'] = $str_result->info->http_code;
            }else {
                $res = array('error' => $str_result->info->http_code);
            }
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'GET_ORDER_WB_ERROR', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "ORDER", "DESCRIPTION" => serialize($res) ));
        }
        return $res;
    }

    public static function putStatus($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false){

            $api = new RestClient([
                'base_url' => $base_url,
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: ' . $Authorization,
                        'Content-Type: application/json',
                    )
                )
            ]);


            $str_result = $api->post($path, []);prent($str_result);
            if($str_result->info->http_code == 200)
            {
//                $res = \Bitrix\Main\Web\Json::decode($str_result->response);
                $res = array('success' => true);

            }
            elseif ($str_result->info->http_code == 400)
            {
                $res = \Bitrix\Main\Web\Json::decode($str_result->response);

            }
            else
            {
                if($str_result->response !='') {
                    $res = \Bitrix\Main\Web\Json::decode($str_result->response);
                    $res['http_code'] = $str_result->info->http_code;
                }else {
                    $res = array(
                        'error' => true,
                        'errorText' => $str_result->info->http_code
                    );
                }
            }
            return $res;
        }

    public static function rest_back($base_url = OZON_BASE_URL, $data_string, $path){
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: json',
                    'Content-Length: ' . strlen($data_string)
                )
            )
        ]);

        $str_result = $api->post($path, []);

        if ($str_result->info->http_code == 200) {
                $arRes = \Bitrix\Main\Web\Json::decode($str_result->response);
                $res = $arRes['result'];
                return $res;
        } else {
                $res['error'] = $str_result->decode_response()->error;
                return $res;
        }
    }

    public static function rest_supplies_get($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

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
                    'Content-Length: 0',
                    'Authorization: ' . $Authorization,

                )
            )
        ]);


        $str_result = $api->post($path, []);
//        echo '<pre>', print_r($str_result), '</pre>' ;
        if(CMaxyssWb::get_setting_wb_for_auth('LOG_ON', $Authorization) == "Y") {
            if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
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
        if ($str_result->info->http_code == 200 && strlen($str_result->response)>0) {
            $res =  \Bitrix\Main\Web\Json::decode($str_result->response);
        }elseif($str_result->info->http_code == 401){
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'rest_supplies_get', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "$Authorization", "DESCRIPTION" => '401 unauthorized'));
            $res = array('error' => '401 unauthorized');
        }elseif($str_result->info->http_code == 403){
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'rest_supplies_get', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "$Authorization", "DESCRIPTION" => '403 Forbidden'));
            $res = array('error' => 'Forbidden');
        }else{
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'rest_supplies_get', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "$Authorization", "DESCRIPTION" => '500 Internal Server Error'));
            $res = array('error' => '500 Internal Server Error');
        }
        return $res;
    }

    public static function rest_supplies_add($base_url = WB_BASE_URL, $data_string, $path, $Authorization = false){

        if(!$Authorization) $Authorization = CMaxyssWb::get_setting_wb("AUTHORIZATION", "DEFAULT");

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
                    'Content-Length: 0',
                    'Authorization: ' . $Authorization,
                )
            )
        ]);

        $str_result = $api->post($path, []);
        if ($str_result->info->http_code == 201 && strlen($str_result->response)>0) {
            $res =  \Bitrix\Main\Web\Json::decode($str_result->response);
        }elseif($str_result->info->http_code == 401){
            //unauthorized
            $res = array('error' => 'unauthorized');
        }elseif($str_result->info->http_code == 409){
            // ������������ ������ ��������
            $res = array('error' => '��� ���� �������� ��������');
        }else{
            // ���������� ������ ������� 500
            $res = array('error' => '���������� ������ �������');
        }
        return $res;
    }
}
class CHelpMaxyssWB{
    public static  function saveOption($options = array()){
        if(!empty($options)){
            foreach ($options as $key=>$o){
                if(isset($_REQUEST[$o])){
                    \Bitrix\Main\Config\Option::set(MAXYSS_WB_NAME, $key, serialize($_REQUEST[$o]));
                }
            }
        }
    }

    public static  function arr_to_file($filename, $array){
            $arr = serialize($array);
            file_put_contents($filename, $arr);
            return true;
    }
    public static  function arr_from_file($filename){
        if(file_exists($filename)) {
            $arr = file_get_contents($filename);
            $arr = unserialize($arr);
            return $arr;
        }else{
            return false;
        }
    }

    public static function unsetMarkedOrder(\Bitrix\Sale\Order $order, $shipment)
    {

        $markers = \Bitrix\Sale\EntityMarker::getList(['filter'=>['=ORDER_ID'=>$order->getId()]])->fetchAll();
        if(count($markers)>0){
            foreach ($markers as $marker) {
                if(strpos($marker["CODE"], 'ILDB_ERROR')) {
                    \Bitrix\Sale\EntityMarker::delete($marker["ID"]);
                }
            }
        }
        $shipment->setField('MARKED', 'N');
        $shipment->setField('REASON_MARKED', '');
        $order->setField('MARKED', 'N');
        $order->setField('REASON_MARKED', '');
    }

    public static function setMarkedOrder(\Bitrix\Sale\Order $order, $shipment, $message, $message_short)
    {
        $markers = \Bitrix\Sale\EntityMarker::getList(['filter'=>['=ORDER_ID'=>$order->getId()]])->fetchAll();
        if(count($markers)>0){
            $count = count($markers) + 1;

            $result = new \Bitrix\Sale\Result();
            $result->addWarning(new \Bitrix\Sale\ResultWarning($message, 'WILDB_ERROR_'.$count));
            \Bitrix\Sale\EntityMarker::addMarker($order, $shipment, $result);
        }
        else
        {
            $result = new \Bitrix\Sale\Result();
            $result->addWarning(new \Bitrix\Sale\ResultWarning($message, 'WILDB_ERROR_1'));
            \Bitrix\Sale\EntityMarker::addMarker($order, $shipment, $result);
        }
        $shipment->setField('REASON_MARKED', $message_short);
    }

    public static function chek_propety_order($prop_code = '', $person_type_id = '', $lid = ''){
        $res = false;
        if($prop_code !== '' && $lid != '' && $person_type_id !=''){

            $arFields = array(
                "PERSON_TYPE_ID" => $person_type_id,
                "NAME" => $prop_code,
                "TYPE" => "STRING",
                "REQUIED" => 'N',
                "MULTIPLE" => "N",
                "SORT" => '100',
                "USER_PROPS" => "N",
                "IS_LOCATION" => "N",
                "CODE" => $prop_code,
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
                    "=CODE" => $prop_code,
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
                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . MAXYSS_MODULE_NAME . "/log_order.txt", 'NO GROUPE PROPS ID FOR ORDER' . PHP_EOL, FILE_APPEND);
                }
                $ID = CSaleOrderProps::Add($arFields);
                if($ID > 0)
                    $res = $prop_code;
            }else{
                $res = $prop_code;
            }

        }
        return $res;
    }
}
class CMaxyssWbEvents{
    public static function ElementUpdate(&$arFields)
    {
        if ($arFields['RESULT'] && ($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/iblock_element_edit.php' || $GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/cat_product_edit.php' )) {
            $PROP_MAXYSS_SIMILAR = false;
            $PROP_MAXYSS_WB = false;
            $arSettings = CMaxyssWb::settings_wb();
            if(is_array($arSettings['IBLOCK_ID']) && array_search($arFields['IBLOCK_ID'], $arSettings['IBLOCK_ID']) !== false){
                $properties = CIBlockProperty::GetList(Array("sort" => "asc", "name" => "asc"), Array("CODE" => "PROP_MAXYSS%", "IBLOCK_ID" =>$arFields['IBLOCK_ID']));
                while ($prop_fields = $properties->GetNext()) {
                    if ($prop_fields['CODE'] == 'PROP_MAXYSS_SIMILAR_WB')
                        $PROP_MAXYSS_SIMILAR = $prop_fields['ID'];
                    if ($prop_fields['CODE'] == 'PROP_MAXYSS_WB')
                        $PROP_MAXYSS_WB = $prop_fields['ID'];
                }

                if ($PROP_MAXYSS_SIMILAR > 0 && $PROP_MAXYSS_WB > 0) {
                    $wb_value = $arFields['PROPERTY_VALUES'][$PROP_MAXYSS_WB][array_key_first($arFields['PROPERTY_VALUES'][$PROP_MAXYSS_WB])]['VALUE'];
                    $wb_description = $arFields['PROPERTY_VALUES'][$PROP_MAXYSS_WB][array_key_first($arFields['PROPERTY_VALUES'][$PROP_MAXYSS_WB])]['DESCRIPTION'];
                    $similar = $arFields['PROPERTY_VALUES'][$PROP_MAXYSS_SIMILAR];
                    if (strpos($wb_value, 'object') && is_array($arFields['PROPERTY_VALUES'][$PROP_MAXYSS_SIMILAR]) && count($arFields['PROPERTY_VALUES'][$PROP_MAXYSS_SIMILAR]) > 0) {
                        $PROPERTY_VALUE = array(
                            0 => array("VALUE" => $wb_value, "DESCRIPTION" => $wb_description)
                        );

                        foreach ($arFields['PROPERTY_VALUES'][$PROP_MAXYSS_SIMILAR] as $key => $value) {
                            if ($value["VALUE"] != $arFields["ID"])
                                CIBlockElement::SetPropertyValuesEx($value['VALUE'], false, array('PROP_MAXYSS_WB' => $PROPERTY_VALUE));
                        }
                    }
                }
            }
        }
    }

    public static function PutStatus($event)
    {
        $order = $event->getParameter("ENTITY");
        $orderStatus = $order->getField('STATUS_ID');

        if($orderStatus != "N"){
            $oldValues = $event->getParameter("VALUES");
            $orderId = $order->getField('ID');

            $arStatusWb = array(
                'CLIENT_RECEIVED' => 1,
                'SKLAD_WB' => 2,
                'CANCEL'=> 3
            );

            $id = ''; // ����� ������ � ������� wb.ru
            $warehouseId = false;
            $propertyCollection = $order->getPropertyCollection();
            foreach ($propertyCollection as $prop) {
                switch ($prop->getField('CODE')) {
                    case "MAXYSS_WB_NUMBER":
                        $id = $prop->getValue();
                        $ids_stiker_get[] = intval($id);
                        break;
                    case "MAXYSS_WB_CABINET":
                        $cabinet_prop = $prop->getValue();
                        break;
                    case "MAXYSS_WB_WAREHOUSEID":
                        $warehouseId = $prop->getValue();
                        break;
                    default:
                }

            }

            $cabinet = ($cabinet_prop == '')? 'DEFAULT' : $cabinet_prop;

            $arSettings = CMaxyssWb::settings_wb($cabinet);
            if( $id != '' && $oldValues["STATUS_ID"] && $oldValues["STATUS_ID"] != $orderStatus && is_array($arSettings['STATUS_BY']) && in_array($orderStatus, $arSettings['STATUS_BY']) && !empty($arSettings['TRIGGERS'])){

                if(is_array($arSettings['TRIGGERS'])) {
                    if ($trigger_status = array_search($orderStatus, $arSettings['TRIGGERS'])) {

                        $status = $arStatusWb[$trigger_status];
                        $oldStatus = $oldValues["STATUS_ID"];
                        $flag_put = false;

                        if (in_array(array_search($oldStatus, $arSettings['STATUS_BY']), array(1, 3, 4, 5, 6,))) $flag_put = false; //echo '�������, �������, �������, ����� ����������, �������� - ������ �� ������������� ������� ����� ������';
                        elseif (array_search($oldStatus, $arSettings['STATUS_BY']) === false) $flag_put = true; // echo '��� ������� ������� - ������������� ����� �����';
                        elseif (array_search($oldStatus, $arSettings['STATUS_BY']) < array_search($orderStatus, $arSettings['STATUS_BY'])) $flag_put = true; // echo ' ������������� ����� ������';
                        elseif (array_search($oldStatus, $arSettings['STATUS_BY']) == 2 && array_search($orderStatus, $arSettings['STATUS_BY']) == 1) $flag_put = true; //echo ' ������������� ����� ������ = ������ ����� ������';
                        else $flag_put = false; //echo '�� ������������� ����� ������, ��� ��� �� ������ �������';
                        if ($flag_put) {
                            if ($trigger_status == 'CLIENT_RECEIVED') {
                                // ���� �������� �������� � ��������� � ��� �����
//                            $result = CMaxyssWb::putStatusOrders($id, $status, $arSettings["AUTHORIZATION"]); // �������

                                $supplies = new CMaxyssWbSupplies($cabinet);
                                $open_supplies = $supplies->confirmOrderToSupplie($id, $warehouseId);

                                if ($open_supplies['success']) {
                                    CHelpMaxyssWB::unsetMarkedOrder($order, $order);

                                    //// ������
                                    if (!empty($ids_stiker_get)) {
                                        $data_string = \Bitrix\Main\Web\Json::encode(array('orders' => $ids_stiker_get));
                                        $width = 58;
                                        $height = 40;
                                        if ($arSettings['STIKER_WIDTH'] == 40) {
                                            $width = 40;
                                            $height = 30;
                                        }

                                        $Authorization = $arSettings['AUTHORIZATION'];
                                        $path = '/api/v3/orders/stickers?type=' . str_replace('.', '', FILE_TYPE_STIKER) . '&width=' . $width . '&height=' . $height;
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
                                            if (isset($res_stickers["stickers"]) && !empty($res_stickers["stickers"])) {
                                                CheckDirPath($_SERVER["DOCUMENT_ROOT"] . "/upload/wb/");
                                                foreach ($res_stickers["stickers"] as $val_sticker) {
                                                    $image = base64_decode($val_sticker["file"]);
                                                    $FPName = $val_sticker["orderId"] . FILE_TYPE_STIKER;
                                                    $FPPath = $_SERVER["DOCUMENT_ROOT"] . '/upload/wb/' . $FPName;
                                                    if (!file_exists($FPPath)) {
                                                        file_put_contents($FPPath, $image, LOCK_EX);
                                                        if (is_object($order)) {
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
                                                                    $order->save();
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            $eventLog = new \CEventLog;
                                            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'get_stickers', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "$Authorization", "DESCRIPTION" => $str_result->info->http_code));
                                        }
                                    }
                                    //// ������
                                } elseif ($open_supplies['error']) {
                                    CHelpMaxyssWB::setMarkedOrder($order, $order, GetMessage('WB_MAXYSS_PUT_STATUS_ERROR') . " " . $open_supplies['error'], $open_supplies['error']);
                                }
                            } elseif ($trigger_status == 'CANCEL') {
                                // �������� ����� https://suppliers-api.wildberries.ru/api/v3/orders/{order}/cancel

                                $Authorization = $arSettings['AUTHORIZATION'];

                                $api = new RestClient([
                                    'base_url' => 'https://suppliers-api.wildberries.ru',
                                    'curl_options' => array(
                                        CURLOPT_SSL_VERIFYPEER => false,
                                        CURLOPT_SSL_VERIFYHOST => false,
                                        CURLOPT_HEADER => TRUE,
                                        CURLOPT_CUSTOMREQUEST => 'PATCH',
                                        CURLOPT_HTTPHEADER => array(
                                            'Authorization: ' . $Authorization,
                                            'Content-Type: application/json',
                                        )
                                    )
                                ]);


                                $str_result = $api->post('api/v3/orders/' . $id . '/cancel', []);

                                if ($arSettings['LOG_ON'] == "Y") {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'str_result', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => "str_result", "DESCRIPTION" => serialize($str_result)));
                                }

                                if ($str_result->info->http_code == 204) {
                                    $eventLog = new \CEventLog;
                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'CANCEL_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $id, "DESCRIPTION" => 'success'));
                                    CHelpMaxyssWB::unsetMarkedOrder($order, $order);
                                } else {
                                    if (strlen($str_result->response) > 0) {
                                        if (strpos($str_result->response, '}')) {
                                            $res_response = \Bitrix\Main\Web\Json::decode($str_result->response);
                                            $res['error'] = $res_response['message'] . ' / ' . $res_response['data'] . ' / ' . $res_response['code'];
                                            CHelpMaxyssWB::setMarkedOrder($order, $order, GetMessage('WB_MAXYSS_PUT_STATUS_ERROR') . " " . $res['error'], $res['error']);
                                            $eventLog = new \CEventLog;
                                            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'CANCEL_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $id, "DESCRIPTION" => serialize($res_response)));
                                        } else {
                                            if (LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                                                $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                                                    $str_result->response,
                                                    'UTF-8',
                                                    'windows-1251',
                                                    $errorMessage = ""
                                                );
                                            } else $descr = $str_result->response;
                                            $res['error'] = $descr;
                                            CHelpMaxyssWB::setMarkedOrder($order, $order, GetMessage('WB_MAXYSS_PUT_STATUS_ERROR') . " " . $descr, $descr);
                                            $eventLog = new \CEventLog;
                                            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'CANCEL_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $id, "DESCRIPTION" => serialize($descr)));
                                        }
                                    }
                                }
                            } else {
                                // ������ �� ������ ��� ��� ��������
                            }

                        }
                    }
                }
            }
        }
    }

    public static function WBOnAdminListDisplay(&$list)
    {
        if (strpos($list->table_id,"tbl_iblock_element") !== false ||
            strpos($list->table_id,"tbl_iblock_list") !== false ||
            strpos($list->table_id,"tbl_product_list") !== false ||
            strpos($list->table_id,"tbl_product_admin") !== false) {
            $list->arActions['wb_upload'] = array(
                'name' => GetMessage("WB_MAXYSS_CONTEXT_MENU"),
                'type' => 'customJs',
                'js' => 'uploadSelectItems(BX.Main.gridManager.getById(\''.$list->table_id.'\').instance.rows.getSelectedIds())'
            );
            $list->arActions['wb_get_id'] = array(
                'name' => GetMessage("WB_MAXYSS_CONTEXT_MENU_GET"),
                'type' => 'customJs',
                'js' => 'getWBAttributesSelectItems(BX.Main.gridManager.getById(\''.$list->table_id.'\').instance.rows.getSelectedIds())'
            );
        }
    }

    public static function GheckAgentRun(){
        global $USER;
        if(!is_object($USER))
            $USER = new CUser;

        if (defined("ADMIN_SECTION") && $USER->IsAdmin()) {
            $flag_order = false;
            $flag_order_status = false;
            $flag_product = false;

            $arSettings = CMaxyssWb::settings_wb();

            $arActiveOrder = $arSettings['ACTIVE_ORDER_ON'];
            foreach ($arActiveOrder as $key => $active) {
                if ($active == 'Y') {
                    $res_status = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::getStatusOrders('%", "ACTIVE"=>"N"));
                    $arResStatus = $res_status->GetNext();
                    if (intval($arResStatus['ID']) > 0) {
                            $flag_order = true;
                    }

                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::loadNewOrders('%", "ACTIVE"=>"N"));
                    if ($arRes = $res->GetNext()) {
                        if (intval($arRes['ID']) > 0) {
                                $flag_order_status = true;
                        }
                    };
                }
            }

            $arActiveProduct = $arSettings['ACTIVE_ON'];
            foreach ($arActiveProduct as $key => $active) {
                if ($active == 'Y') {
                    $res = CAgent::GetList(Array("ID" => "DESC"), array("NAME" => "CMaxyssWb::uploadAllStocks('%"));
                    if ($arRes = $res->GetNext()) {
                        if (intval($arRes['ID']) > 0) {
                            if($arRes['ACTIVE'] == "N"){
                                $flag_product = true;
                            }
                        }
                    }
                }
            }

            if($flag_order || $flag_order_status) {
                $ar = Array(
                    "MESSAGE" => GetMessage("WB_MAXYSS_TITLE") . ' - ' . GetMessage("WB_MAXYSS_ORDER_ACTIVE_ERROR"),
                    "TAG" => "WB_ORDER_ACTIVE_ERROR",
                    "MODULE_ID" => "maxyss.wb",
                    'NOTIFY_TYPE' => 'E'
                );
                $ID = CAdminNotify::Add($ar);
            }
            else
            {
                CAdminNotify::DeleteByTag("WB_ORDER_ACTIVE_ERROR");
            }

            if($flag_product){
                $ar = Array(
                    "MESSAGE" => GetMessage("WB_MAXYSS_TITLE") . ' - ' . GetMessage("WB_MAXYSS_PRODUCT_ACTIVE_ERROR"),
                    "TAG" => "WB_PRODUCT_ACTIVE_ERROR",
                    "MODULE_ID" => "maxyss.wb",
                    'NOTIFY_TYPE' => 'E'
                );
                $ID = CAdminNotify::Add($ar);
            }
            else
            {
                CAdminNotify::DeleteByTag("WB_PRODUCT_ACTIVE_ERROR");
            }
        }
    }
}
?>
