<?
use     Bitrix\Main\Application;
class CMaxyssGetOzonInfo{
    public static function downloadIdOzon($lid='', $limit = 300, $last_id = ''){
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

        $ClientId = $arSettings['OZON_ID'];
        $ApiKey = $arSettings['OZON_API_KEY'];

        $data_string = array(
            "filter" => array(
                "visibility"=>"MODERATED"
            ),
            "last_id"=>$last_id,
            "limit"=>$limit
        );

//                $arProductOzon = self::getQueryOzonRecursive($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v2/product/list");
        $arResultOzon = self::getQueryOzon($ClientId, $ApiKey.'', $base_url = OZON_BASE_URL, $data_string, "/v2/product/list");

        if(isset($arResultOzon['count_item'])) {
            $offer_ids = array();
            foreach ($arResultOzon['items'] as $i){
                $offer_ids[] = $i['offer_id'];
            }
            $sku = CMaxyssOrderList::GetProductsInfo($offer_ids, $ClientId, $ApiKey);
            if(!empty($sku['items'])){
                foreach ($sku['items'] as $s){
                    $arSku[$s['id']] = $s["fbs_sku"];
                }
                foreach ($arResultOzon['items'] as &$i){
                    $i['sku'] =  $arSku[$i['product_id']];
                }
            }
        }

        if(isset($arResultOzon['count_item']) && $arResultOzon['count_item'] == $limit){
            return array('go_run'=>true, 'last_id'=>$arResultOzon['last_id'], 'items'=>$arResultOzon['items']);
        }elseif($arResultOzon['message']){
            return array('go_run'=>false, 'mess'=>$arResultOzon['message']);
        }
        else
        {
            return array('go_run'=>false, 'mess'=>'end', 'items'=>$arResultOzon['items']);
        }
    }

    public static function getQueryOzon($ClientId, $ApiKey, $base_url, $data_string, $path){
        $data_string_json = \Bitrix\Main\Web\Json::encode($data_string);
        $arProduct = array();
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string_json,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "Client-Id: " . $ClientId,
                    "Api-Key: " . $ApiKey,
                    'Content-Type: json',
                    'Content-Length: ' . strlen($data_string_json)
                )
            )
        ]);
        $str_result = $api->post($path, []);
        if ($str_result->info->http_code == 200) {
            $arProduct = CUtil::JsObjectToPhp($str_result->response);
        }
        else
        {
            $arProduct = CUtil::JsObjectToPhp($str_result->response);
        }

        if(!empty($arProduct['result']['items'])){
            // �������� ���������� �������� ���������
            return array('count_item'=>count($arProduct['result']['items']), 'last_id'=>$arProduct['result']['last_id'], 'items'=>$arProduct['result']['items']);
        }
        else{
            return array('message'=>$arProduct['message']);
        }
    }

    public static function getQueryOzonRecursive($ClientId, $ApiKey, $base_url, $data_string, $path, $arProductOzon=array()){
        $data_string_json = \Bitrix\Main\Web\Json::encode($data_string);
        $arProduct = array();
        $api = new RestClient([
            'base_url' => $base_url,
            'curl_options' => array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => $data_string_json,
                CURLOPT_HEADER => TRUE,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "Client-Id: " . $ClientId,
                    "Api-Key: " . $ApiKey,
                    'Content-Type: json',
                    'Content-Length: ' . strlen($data_string_json)
                )
            )
        ]);
        $str_result = $api->post($path, []);
        if ($str_result->info->http_code == 200) {
            $arProduct = CUtil::JsObjectToPhp($str_result->response);
        }
        if(!empty($arProduct['result']['items']))
            $arProductOzon = array_merge($arProductOzon, $arProduct['result']["items"]);

        if(count($arProductOzon) < $arProduct['result']['total']){
            $data_string["last_id"] = $arProduct['result']['last_id'];
            $arProductOzon = self::getQueryOzonRecursive($ClientId, $ApiKey, $base_url, $data_string, $path, $arProductOzon);
            return $arProductOzon;
        }
        return $arProductOzon;
    }
}

class CMaxyssMoreOzonFunction{
    public static function OzonArchToProducts($sku = array(), $ClientId, $ApiKey){
        $res_arch_to = '';
        if(!empty($sku)) {
            $data_string = array('product_id' => $sku);;
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $res_arch_to = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v1/product/archive");
        }
        return $res_arch_to;
    }

    public static function OzonArchFromProducts($sku = array(), $ClientId, $ApiKey){
        $res_arch_to = '';
        if(!empty($sku)) {
            $data_string = array('product_id' => $sku);;
            $data_string = \Bitrix\Main\Web\Json::encode($data_string);
            $res_arch_to = CRestQuery::rest_query($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $data_string, "/v1/product/unarchive");
        }
        return $res_arch_to;
    }
}
class CMaxyssAdminList{
    public static function UploadElementToOzon(&$list){
        if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_MODULE_NAME) >= "R") {
            $request = Application::getInstance()->getContext()->getRequest();
            $IBLOCK_ID = intval($request->getQuery("IBLOCK_ID"));
            $IBLOCK_ID_PRODUCT = 0;
            if (strpos($list->table_id, '_iblock_list_') || strpos($list->table_id, '_product_list_') || strpos($list->table_id, '_iblock_element_')) {
                $flag_upload = false;
                if ($IBLOCK_ID > 0) {
                    $arOptions = CMaxyssOzon::getOptions();
                    if (!empty($arOptions)) {
                        foreach ($arOptions as $key => $lid) {

                            if ($lid["IBLOCK_ID"] == $IBLOCK_ID) {
                                $flag_upload = true;
                            }
                            $mxResult = CCatalogSKU::GetInfoByOfferIBlock(
                                $IBLOCK_ID
                            );
                            if (is_array($mxResult) && $mxResult['PRODUCT_IBLOCK_ID'] == $lid["IBLOCK_ID"]) {  // ��� �������� ��
                                $flag_upload_tp = true;
                                $IBLOCK_ID_PRODUCT = $mxResult['PRODUCT_IBLOCK_ID'];
                            }

                        }
                    }
                    if ($flag_upload) {
                        CJSCore::Init(array('maxyss_ozon'));
                        $list->arActions['upload_to_ozon_maxyss'] = array(
                            'name' => GetMessage("MAXYSS_OZON_UPLOAD"),
                            'type' => 'customJs',
                            'js' => 'upload_ozon_list(BX.Main.gridManager.getById(\'' . $list->table_id . '\').instance.rows.getSelectedIds(), ' . $IBLOCK_ID . ')'
                        );
                        $list->arActions['arch_to_ozon_maxyss'] = array(
                            'name' => GetMessage("MAXYSS_OZON_ARCH_TO"),
                            'type' => 'customJs',
                            'js' => 'arch_to_ozon_list(BX.Main.gridManager.getById(\'' . $list->table_id . '\').instance.rows.getSelectedIds(), ' . $IBLOCK_ID . ')'
                        );
                        $list->arActions['arch_from_ozon_maxyss'] = array(
                            'name' => GetMessage("MAXYSS_OZON_ARCH_FROM"),
                            'type' => 'customJs',
                            'js' => 'arch_from_ozon_list(BX.Main.gridManager.getById(\'' . $list->table_id . '\').instance.rows.getSelectedIds(), ' . $IBLOCK_ID . ')'
                        );
                    } elseif ($flag_upload_tp) {
                        CJSCore::Init(array('maxyss_ozon'));
                        $list->arActions['arch_to_ozon_maxyss'] = array(
                            'name' => GetMessage("MAXYSS_OZON_ARCH_TO"),
                            'type' => 'customJs',
                            'js' => 'arch_to_ozon_list(BX.Main.gridManager.getById(\'' . $list->table_id . '\').instance.rows.getSelectedIds(), ' . $IBLOCK_ID . ', ' . $IBLOCK_ID_PRODUCT . ')'
                        );
                        $list->arActions['arch_from_ozon_maxyss'] = array(
                            'name' => GetMessage("MAXYSS_OZON_ARCH_FROM"),
                            'type' => 'customJs',
                            'js' => 'arch_from_ozon_list(BX.Main.gridManager.getById(\'' . $list->table_id . '\').instance.rows.getSelectedIds(), ' . $IBLOCK_ID . ', ' . $IBLOCK_ID_PRODUCT . ')'
                        );
                    }
                }
            }
        }
    }
}
