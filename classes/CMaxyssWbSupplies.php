<?

class CMaxyssWbSupplies{
    public $cabinet;
    public $settings;
    public $supplies;
    public $supplie;
    public $error;
    public $data_string;
//    public $status_supplies;

    public function __construct($cabinet = 'DEFAULT', $settings = []){
        $settings_module = CMaxyssWb::settings_wb($cabinet);
//        if($this->settings['AUTHORIZATION'] == '') $this->settings['AUTHORIZATION'] = CMaxyssWb::get_setting_wb("AUTHORIZATION", $cabinet);
        $this->settings = array_merge($settings_module, $settings);
        $this->supplies = array();
        $this->supplie = array();
        $this->data_string = '';
        $this->error = array();
        $this->cabinet = $cabinet;
        $this->warehouseId = false;
        $this->property_warehuosesid = false;
        $this->property_warehuosesid = CHelpMaxyssWB:: chek_propety_order('MAXYSS_WB_WAREHOUSEID', $this->settings['PERSON_TYPE'], $this->settings['SITE']);
    }
    public function getSupplies($limit = 1000, $next = 0){
        if($this->settings['AUTHORIZATION']){
            $res_supl = CRestQueryWB::rest_supplies_get($base_url = 'https://suppliers-api.wildberries.ru', $this->data_string, "/api/v3/supplies?limit=".$limit."&next=".$next, $this->settings['AUTHORIZATION']);
            if(isset($res_supl['supplies']) && !empty($res_supl['supplies']) && count($res_supl['supplies'])>=$limit && isset($res_supl['next'])){
                $this->supplies = array_merge($this->supplies, $res_supl['supplies']);
                $this->getSupplies($limit, $res_supl['next']);
            }
            elseif(isset($res_supl['supplies']) && !empty($res_supl['supplies']))
            {
                $this->supplies = array_merge($this->supplies, $res_supl['supplies']);
            }
            if(isset($res_supl['error'])) $this->error[] = $res_supl['error'];
        }
        return $this->supplies;
    }
    public function ShowErrors(){
        if(!empty($this->error)){
            foreach ($this->error as $error) echo '<font style="color: red">'.$error.'</font>';
        }
    }
    public function addSupplies($name_supplie){
        if($this->settings['AUTHORIZATION']){
//            $this->supplie = CRestQueryWB::rest_supplies_add($base_url = 'https://suppliers-api.wildberries.ru', $this->data_string, "/api/v2/supplies", $this->settings['AUTHORIZATION']);
            $Authorization = $this->settings['AUTHORIZATION'];
            $data_string = array('name'=>$name_supplie);
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $api = new RestClient([
                'base_url' => 'https://suppliers-api.wildberries.ru',
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_POSTFIELDS => $data_string,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'accept: application/json',
                        'Content-Type: application/json',
                        'Content-Length: '.strlen($data_string),
                        'Authorization: ' . $Authorization,
                    )
                )
            ]);


            $str_result = $api->post('/api/v3/supplies', []);
            if($this->settings['LOG_ON'] == "Y") {
                if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                    $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                        $str_result->response,
                        'UTF-8',
                        'windows-1251',
                        $errorMessage = ""
                    );
                } else $descr = $str_result->response;
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => $descr . '; ' . $str_result->info->http_code));
            }
            $res['supplyId'] = false;
            $res['error'] = false;
            if ($str_result->info->http_code == 201 && strlen($str_result->response)>0) {
                $res['supplyId'] =  \Bitrix\Main\Web\Json::decode($str_result->response)['id'];
            }elseif($str_result->info->http_code == 401){
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '401 unauthorized'));
                $res['error'] = '401 Unauthorized';
            }elseif($str_result->info->http_code == 403){
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '403 Forbidden'));
                $res['error'] = '403 Forbidden';
            }else{
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => $str_result->info->http_code));
                $res['error'] = $str_result->info->http_code;
            }
        }
        $this->error[] = $res['error'];
        $this->supplie = $res;
        return $this->supplie;
    }
    public function getSupplieOrders($id){
        $res = array();
        if($this->settings['AUTHORIZATION']) {
            $Authorization = $this->settings['AUTHORIZATION'];
            $api = new RestClient([
                'base_url' => 'https://suppliers-api.wildberries.ru/api/v3/supplies/',
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'accept: application/json',
//                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,
                    )
                )
            ]);

            $str_result = $api->post($id.'/orders', []);
            if ($str_result->info->http_code == 200 && strlen($str_result->response)>0) {
                $res =  \Bitrix\Main\Web\Json::decode($str_result->response);
            }elseif($str_result->info->http_code == 401){
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '401 unauthorized'));
                $res['error'] = '401 Unauthorized';
            }elseif($str_result->info->http_code == 403){
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '403 Forbidden'));
                $res['error'] = '403 Forbidden';
            }else{
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => $str_result->info->http_code));
                $res['error'] = $str_result->info->http_code;
            }
        }

        return $res;
    }
    public function deleteSupplies($id){
        $res = array();
        if($this->settings['AUTHORIZATION']) {
            $Authorization = $this->settings['AUTHORIZATION'];
            $api = new RestClient([
                'base_url' => 'https://suppliers-api.wildberries.ru',
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_HTTPHEADER => array(
                        'accept: application/json',
//                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,
                    )
                )
            ]);

            $str_result = $api->post('/api/v3/supplies/'.$id, []);
            $res['success'] = false;
            $res['error'] = false;
            if ($str_result->info->http_code == 204) {
                $res['success'] =  true;
            }elseif($str_result->info->http_code == 401){
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '401 unauthorized'));
                $res['error'] = '401 Unauthorized';
            }elseif($str_result->info->http_code == 403){
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '403 Forbidden'));
                $res['error'] = '403 Forbidden';
            }else{
                $eventLog = new \CEventLog;
                $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => $str_result->info->http_code));
                $res['error'] = $str_result->info->http_code;
            }
        }

        return $res;
    }
    public function deliverSupplie($id){
        $res = array();
        if($this->settings['AUTHORIZATION']) {
            $Authorization = $this->settings['AUTHORIZATION'];
            $api = new RestClient([
                'base_url' => 'https://suppliers-api.wildberries.ru',
                'curl_options' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_HEADER => TRUE,
                    CURLOPT_CUSTOMREQUEST => 'PATCH',
                    CURLOPT_HTTPHEADER => array(
                        'accept: application/json',
//                        'Content-Type: application/json',
                        'Authorization: ' . $Authorization,
                    )
                )
            ]);

            $str_result = $api->post('/api/v3/supplies/'.$id.'/deliver', []);
            $res['success'] = false;
            $res['error'] = false;
            if ($str_result->info->http_code == 204) {
                $res['success'] =  true;
            }else{
                if (strlen($str_result->response)>0) {
                    if(strpos($str_result->response, '}')) {
                        $res_response = \Bitrix\Main\Web\Json::decode($str_result->response);
                        $res['error'] = $res_response['message'] . ' / ' . $res_response['code'] . ' / ' . $res_response['data'];
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'CANCEL_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $id, "DESCRIPTION" => serialize($res_response)));
                    }else{
                        if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                            $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                                $str_result->response,
                                'UTF-8',
                                'windows-1251',
                                $errorMessage = ""
                            );
                        } else $descr = $str_result->response;
                        $res['error'] = $descr;
                        $eventLog = new \CEventLog;
                        $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'CANCEL_ORDER_WB', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $id, "DESCRIPTION" => $descr));
                    }
                }
            }
        }
        return $res;
    }
    public function confirmOrderToSupplie($id_order, $warehouseId = false){
        $result = array('success'=>false, 'error'=>false);
        $arSupliesNotDone = array();
        $name_new_supplie = '';
        if($id_order !=''){
            $this->getSupplies();
            if(!empty($this->supplies)){
                foreach ($this->supplies as $key => $supply){
                    if(!$supply['done']) $arSupliesNotDone[] = $supply;
                }
            }
            $this->warehouseId = $warehouseId;

            if(strlen($this->warehouseId) <= 0){
                // ���� ��������� �������� � ������ �������� � ��������� ������ � ������ - ���������� ������
                if($this->property_warehuosesid){
                    $res_orders_new = array();
                    $res_orders_new = CRestQueryWB::rest_order_na($base_url = 'https://suppliers-api.wildberries.ru', '', "/api/v3/orders/new", $this->settings['AUTHORIZATION']);
                    if(isset($res_orders_new['orders']) && !empty($res_orders_new['orders'])){
                        foreach ($res_orders_new['orders'] as $order_wb){
                            if($order_wb['id'] == $id_order){

                                $id_bx_order = CMaxyssWb::getOrder($order_wb['id']);
                                if($id_bx_order > 0){
                                    if(Bitrix\Main\Loader::includeModule("sale")) {
                                        $order_bx = \Bitrix\Sale\Order::Load($id_bx_order);
                                        $propertyCollection = $order_bx->getPropertyCollection();
                                        foreach ($propertyCollection as $prop) {
                                            $value = '';
                                            switch ($prop->getField('CODE')) {
                                                case $this->property_warehuosesid:
                                                    $value = $order_wb['warehouseId'];
                                                    $this->warehouseId = $order_wb['warehouseId'];
                                                    break;
                                                default:
                                                    break;
                                            }
//
                                            if (!empty($value)) {
                                                $prop->setValue($value);
                                                $order_bx_save = $order_bx->save();
                                                if ($order_bx_save->isSuccess()) {
                                                    $eventLog = new \CEventLog;
                                                    $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $order_bx->getField('ID'), "DESCRIPTION" => "OK SAVE PROPERTY_WAREHUOSESID",));
                                                }else{
                                                    $eventLog = new \CEventLog;
                                                    $eventLog->Add(array("SEVERITY" => 'ERROR',"AUDIT_TYPE_ID" => 'UPDATE_ORDER', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $order_bx->getField('ID'), "DESCRIPTION" => serialize($order_bx_save->getErrorMessages())));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        // �� ������ �������� �������� � ������ � ������ ������
                        $result['error'] = GetMessage('WB_MAXYSS_ORDER_GET_ERROR');
                        return $result;
                    }
                }
                else
                {
                    $result['error'] = GetMessage('WB_MAXYSS_PROPERTY_WAREHOUSE_FALSE');
                    return $result;
                }
            }

            if($this->warehouseId) {
                if (empty($arSupliesNotDone)) {
                    // ��� �������� �������� - �������� �����
                    $warehouses = CRestQueryWB::rest_warehouses_get($this->settings['AUTHORIZATION']);
                    if (is_array($warehouses)) {
                        foreach ($warehouses as $wh) {
                            if ($wh['id'] == $this->warehouseId) $name_new_supplie = $wh['name'] . ' / ' . $wh['id'];
                        }
                        if ($name_new_supplie != '') $this->addSupplies($name_new_supplie);
                        if ($this->supplie['supplyId'] != '') {
                            // ��������� ����� �������� - ����� ��������� � ��� �����
                            $result = $this->addOrderToSupplie($id_order, $this->supplie['supplyId']);
                            return $result;
                        } else {
                            // �� ��������� ��������
                            $result['error'] = $this->supplie['error'];
                            return $result;
                        }
                    } else {
                        // �� ������� �������� ������ �������
                        $result['error'] .= GetMessage('WB_MAXYSS_WAREHOUSE_GET_ERROR');
                        return $result;
                    }
                } else {
                    // ���� �������� ��������  - ������ ������
                    $warehouses = CRestQueryWB::rest_warehouses_get($this->settings['AUTHORIZATION']);
                    if (is_array($warehouses)) {
                        if (count($warehouses) == 1) {
                            // ���� ����� - ��� ����� ���� ������� ����� - �������� � ������ ���������� ��������
                            $result = $this->addOrderToSupplie($id_order, $arSupliesNotDone[0]['id']);
                            return $result;
                        } else {
                            // ��������� ������� - ���� ������ ��������
                            $flag_find_supplie = false;
                            foreach ($warehouses as $wh) {
                                if ($wh['id'] == $this->warehouseId) {
                                    foreach ($arSupliesNotDone as $sup) {
                                        if ($sup['name'] == $wh['name'] . ' / ' . $wh['id']) {
                                            // ������� �������� � ������ ������
                                            $result = $this->addOrderToSupplie($id_order, $sup['id']);
                                            $flag_find_supplie = true;
                                        }
                                    }
                                }
                            }
                            if ($flag_find_supplie) {
                                return $result;
                            } else {
                                // ��� �������� � ������ ������ ������ ����� ���������
                                foreach ($warehouses as $wh) {
                                    if ($wh['id'] == $this->warehouseId) $name_new_supplie = $wh['name'] . ' / ' . $wh['id'];
                                }
                                if ($name_new_supplie != '') $this->addSupplies($name_new_supplie);
                                if ($this->supplie['supplyId'] != '') {
                                    // ��������� ����� �������� - ����� ��������� � ��� �����
                                    $result = $this->addOrderToSupplie($id_order, $this->supplie['supplyId']);
                                    return $result;
                                } else {
                                    // �� ��������� ��������
                                    $result['error'] = $this->supplie['error'];
                                    return $result;
                                }
                            }

                        }
                    } else {
                        // �� ������� �������� ������ �������
                        $result['error'] = GetMessage('WB_MAXYSS_WAREHOUSE_GET_ERROR');
                        return $result;
                    }
                }
            }
        }else {
            $result['error'] = GetMessage('WB_MAXYSS_WAREHOUSE_GET_ERROR');
            return $result;
        }
    }
    public function addOrderToSupplie($id_order, $id_supplue){
        $res = array('success'=>false, 'error'=>false);
        $Authorization = $this->settings['AUTHORIZATION'];
        $api = new RestClient([
            'base_url' => 'https://suppliers-api.wildberries.ru',
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: ' . $Authorization,
                )
            )
        ]);

        $str_result = $api->post('/api/v3/supplies/'.$id_supplue.'/orders/'.intval($id_order), []);

        if($this->settings['LOG_ON'] == "Y") {
            if(LANG_CHARSET != 'utf-8' && LANG_CHARSET != 'UTF-8') {
                $descr = \Bitrix\Main\Text\Encoding::convertEncoding(
                    $str_result->response,
                    'UTF-8',
                    'windows-1251',
                    $errorMessage = ""
                );
            } else $descr = $str_result->response;
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'addOrderToSupplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => $descr . '; ' . $str_result->info->http_code));
        }

        if ($str_result->info->http_code == 204) {
            $res['success'] =  true;
        }elseif($str_result->info->http_code == 401){
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '401 unauthorized'));
            $res['error'] = '401 Unauthorized';
        }elseif($str_result->info->http_code == 403){
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '403 Forbidden'));
            $res['error'] = '403 Forbidden';
        }elseif($str_result->info->http_code == 409){
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => '409 error add order to supplie'));
            if (strlen($str_result->response)>0) {
                $res_response = \Bitrix\Main\Web\Json::decode($str_result->response);
                $res['error'] = $res_response['message'] . ' / ' . $res_response['data'] . ' / ' . $res_response['code'];
            }
        } else{
            $eventLog = new \CEventLog;
            $eventLog->Add(array("SEVERITY" => 'INFO', "AUDIT_TYPE_ID" => 'add_sullplie', "MODULE_ID" => MAXYSS_WB_NAME, "ITEM_ID" => $this->cabinet, "DESCRIPTION" => $str_result->info->http_code));
            $res['error'] = $str_result->info->http_code;
        }
        return $res;
    }
}
