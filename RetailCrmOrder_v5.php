<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmOrder
{
    /**
     *
     * Creates order or returns order for mass upload
     *
     * @param array  $arFields
     * @param        $api
     * @param        $arParams
     * @param bool   $send
     * @param null   $site
     * @param string $methodApi
     *
     * @return boolean
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function orderSend($arFields, $api, $arParams, $send = false, $site = null, $methodApi = 'ordersEdit')
    {
        if (!$api || empty($arParams)) { // add cond to check $arParams
            return false;
        }
        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmOrder::orderSend', 'empty($arFields)', 'incorrect order');
            return false;
        }

		$Logger = new TsLogger("/crm/orderSend/");
		$Logger->log("LOG", "methodApi - ".$methodApi);
		$Logger->log("LOG", "arFields который поступил BASKET - ".print_r($arFields["BASKET"], true));

        $dimensionsSetting = RetailcrmConfigProvider::getOrderDimensions();
        $currency = RetailcrmConfigProvider::getCurrencyOrDefault();
        $optionCorpClient = RetailcrmConfigProvider::getCorporateClientStatus();

        $order = array(
            'number'          => $arFields['NUMBER'],
            'externalId'      => $arFields['ID'],
            'createdAt'       => $arFields['DATE_INSERT'],
            'customer'        => isset($arParams['customerCorporate'])
                ? array('id' => $arParams['customerCorporate']['id'])
                : array('externalId' => $arFields['USER_ID']),
            'orderType'       => isset($arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']]) ?
                $arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']] : '',
            'status'          => isset($arParams['optionsPayStatuses'][$arFields['STATUS_ID']]) ?
                $arParams['optionsPayStatuses'][$arFields['STATUS_ID']] : '',
            'customerComment' => $arFields['USER_DESCRIPTION'],
            'managerComment'  => $arFields['COMMENTS'],
            'delivery' => array(
                'cost' => $arFields['PRICE_DELIVERY']
            ),
        );

        if (isset($arParams['contactExId'])) {
            $order['contact']['externalId'] = $arParams['contactExId'];
        }

        if (isset($arParams['orderCompany']) && !empty($arParams['orderCompany'])) {
            $company = $arParams['orderCompany'];

            if (isset($company['id'])) {
                $order['company']['id'] = $company['id'];
            }

            if (isset($company['name'])) {
                $order['contragent']['legalName'] = $company['name'];
            }
        }

        if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
            $order['customer']['browserId'] = $_COOKIE['_rc'];
        }

        $order['contragent']['contragentType'] = $arParams['optionsContragentType'][$arFields['PERSON_TYPE_ID']];

        if ($methodApi == 'ordersEdit') {
            $order['discountManualAmount'] = 0;
            $order['discountManualPercent'] = 0;
        }

        //fields
        foreach ($arFields['PROPS']['properties'] as $prop) {
            if (!empty($arParams['optionsLegalDetails'])
                && $search = array_search($prop['CODE'], (array)$arParams['optionsLegalDetails'][$arFields['PERSON_TYPE_ID']])
            ) {
                $order['contragent'][$search] = $prop['VALUE'][0];//legal order data
            } elseif (!empty($arParams['optionsCustomFields'])
                && $search = array_search($prop['CODE'], (array)$arParams['optionsCustomFields'][$arFields['PERSON_TYPE_ID']])
            ) {
                $order['customFields'][$search] = $prop['VALUE'][0];//custom properties
            } elseif ($search = array_search($prop['CODE'], (array)$arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']])) {//other
                if (in_array($search, array('fio', 'phone', 'email'))) {//fio, phone, email
                    if ($search == 'fio') {
                        $order = array_merge($order, RCrmActions::explodeFIO($prop['VALUE'][0]));//add fio fields
                    } elseif ($search == 'email' && mb_strlen($prop['VALUE'][0]) > 100) {
                        continue;
                    } else {
                        // ignoring a property with a non-set group if the field value is already set
                        if (!empty($order[$search]) && $prop['PROPS_GROUP_ID'] == 0) {
                            continue;
                        }

                        $order[$search] = $prop['VALUE'][0];//phone, email
                    }
                } else {//address
                    if ($prop['TYPE'] == 'LOCATION' && isset($prop['VALUE'][0]) && $prop['VALUE'][0] != '') {
                        $arLoc = \Bitrix\Sale\Location\LocationTable::getByCode($prop['VALUE'][0])->fetch();
                        if ($arLoc) {
                            $server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
                            $countrys = array();

                            if (file_exists($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/country.xml')) {
                                $countrysFile = simplexml_load_file($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/country.xml');
                                foreach ($countrysFile->country as $country) {
                                    $countrys[RCrmActions::fromJSON((string) $country->name)] = (string) $country->alpha;
                                }
                            }

                            $location = \Bitrix\Sale\Location\Name\LocationTable::getList(array(
                                'filter' => array('=LOCATION_ID' => $arLoc['CITY_ID'], 'LANGUAGE_ID' => 'ru')
                            ))->fetch();

                            if (count($countrys) > 0) {
                                $countryOrder = \Bitrix\Sale\Location\Name\LocationTable::getList(array(
                                    'filter' => array('=LOCATION_ID' => $arLoc['COUNTRY_ID'], 'LANGUAGE_ID' => 'ru')
                                ))->fetch();
                                if(isset($countrys[$countryOrder['NAME']])){
                                    $order['countryIso'] = $countrys[$countryOrder['NAME']];
                                }
                            }
                        }
                        $prop['VALUE'][0] = $location['NAME'];
                    }

                    if (!empty($prop['VALUE'][0])) {
                        $order['delivery']['address'][$search] = $prop['VALUE'][0];
                    }
                }
            }
        }



        //deliverys
        if (array_key_exists($arFields['DELIVERYS'][0]['id'], $arParams['optionsDelivTypes'])) {
            $order['delivery']['code'] = $arParams['optionsDelivTypes'][$arFields['DELIVERYS'][0]['id']];
            if (isset($arFields['DELIVERYS'][0]['service']) && $arFields['DELIVERYS'][0]['service'] != '') {
                $order['delivery']['service']['code'] = $arFields['DELIVERYS'][0]['service'];
            }
        }

        $weight = 0;
        $width = 0;
        $height = 0;
        $length = 0;

		$breakPhone = false;

        if ('ordersEdit' == $methodApi) {
            $response = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $order['externalId'], $site);
			//AddMessage2Log($response);
            if (isset($response['order'])) {
                foreach ($response['order']['items'] as $k => $item) {
                    $externalId = $k ."_". $item['offer']['externalId'];
                    $orderItems[$externalId] = $item;
                }

				// op
				if($response["order"]["phone"]) $breakPhone = true;
            }
        }

        //basket
        foreach ($arFields['BASKET'] as $position => &$product) {
            $itemId = null;
            $externalId = $position . "_" . $product['PRODUCT_ID'];

            if (isset($orderItems[$externalId])) { //update
                $externalIds = (array)$orderItems[$externalId]['externalIds'];
                $itemId = $orderItems[$externalId]['id'];

                $key = array_search("bitrix", array_column($externalIds, 'code'));
                if ($externalIds[$key]['code'] == "bitrix") {
                    $externalIds[$key] = array(
                        'code' => 'bitrix',
                        'value' => $externalId,
                    );
                } else {
                    $externalIds[] = array(
                        'code' => 'bitrix',
                        'value' => $externalId,
                    );
                }
            } else { //create
                $externalIds = array(
                    array(
                        'code' => 'bitrix',
                        'value' => $externalId,
                    )
                );
            }

            $item = array(
                'externalIds'      => $externalIds,
                'quantity'        => $product['QUANTITY'],
                'offer'           => array(
                    'externalId' => $product['PRODUCT_ID'],
                    'xmlId' => $product['PRODUCT_XML_ID']
                ),
                'productName'     => $product['NAME']
            );

            if (isset($itemId)) {
                $item['id'] = $itemId;
            }

            $pp = CCatalogProduct::GetByID($product['PRODUCT_ID']);
            if (is_null($pp['PURCHASING_PRICE']) == false) {
                if ($pp['PURCHASING_CURRENCY'] && $currency != $pp['PURCHASING_CURRENCY']) {
                    $purchasePrice = CCurrencyRates::ConvertCurrency(
                        (double) $pp['PURCHASING_PRICE'],
                        $pp['PURCHASING_CURRENCY'],
                        $currency
                    );
                } else {
                    $purchasePrice = $pp['PURCHASING_PRICE'];
                }

                $item['purchasePrice'] = $purchasePrice;
            }

            //$discount = (double) $product['DISCOUNT_PRICE'];

			// op
			//AddMessage2Log($product);
			if($product['CUSTOM_PRICE'] == "Y"){

				if($product["NOTES"] == "Цена RetailCRM"){
					$price = ($product['BASE_PRICE'] > 0 ? $product['BASE_PRICE'] : $product['PRICE']);
				}else{
					$price = $product['PRICE'];
				}
				//
				$discount = 0;
			}else{
				if($product['BASE_PRICE'] != $product['PRICE'] && $product['BASE_PRICE'] > $product['PRICE']){
					$discount = (double) $product['BASE_PRICE'] - $product['PRICE'];
				}else{
					$discount = (double) $product['DISCOUNT_PRICE'];
				}

				$dpItem = $product['BASE_PRICE'] - $product['PRICE'];

				if ( $dpItem > 0 && $discount <= 0) {
					$discount = $dpItem;
				}
				$price = ($product['BASE_PRICE'] > 0 ? $product['BASE_PRICE'] : $product['PRICE']);
			}


//AddMessage2Log($discount);AddMessage2Log($product);



            $item['discountManualPercent'] = 0;
            $item['discountManualAmount'] = $discount;
            $item['initialPrice'] = (double) $price;

            $order['items'][] = $item;

            if ($send && $dimensionsSetting == 'Y') {
                $dimensions = RCrmActions::unserializeArrayRecursive($product['DIMENSIONS']);

                if ($dimensions !== false) {
                    $width += $dimensions['WIDTH'];
                    $height += $dimensions['HEIGHT'];
                    $length += $dimensions['LENGTH'];
                    $weight += $product['WEIGHT'] * $product['QUANTITY'];
                }
            }
        }
		unset($product);
        if ($send && $dimensionsSetting == 'Y') {
            $order['width'] = $width;
            $order['height'] = $height;
            $order['length'] = $length;
            $order['weight'] = $weight;
        }

        //payments
        $payments = array();
        foreach ($arFields['PAYMENTS'] as $payment) {
            if (!empty($payment['PAY_SYSTEM_ID']) && isset($arParams['optionsPayTypes'][$payment['PAY_SYSTEM_ID']])) {
                $pm = array(
                    'type' => $arParams['optionsPayTypes'][$payment['PAY_SYSTEM_ID']]
                );

                if (!empty($payment['ID'])) {
                    $pm['externalId'] = RCrmActions::generatePaymentExternalId($payment['ID']);
                }

                if (!empty($payment['DATE_PAID'])) {
                    $pm['paidAt'] = new \DateTime($payment['DATE_PAID']);
                }

                if (!empty($arParams['optionsPayment'][$payment['PAID']])) {
                    $pm['status'] = $arParams['optionsPayment'][$payment['PAID']];
                }

                if (RetailcrmConfigProvider::shouldSendPaymentAmount()) {
                    $pm['amount'] = $payment['SUM'];
                }

                $payments[] = $pm;
            } else {
                RCrmActions::eventLog(
                    'RetailCrmOrder::orderSend',
                    'payments',
                    'OrderID = ' . $arFields['ID'] . '. Payment not found.'
                );

                continue;
            }
        }
        if (count($payments) > 0) {
            $order['payments'] = $payments;
        }

		$Logger->log("LOG", "pre order - ".print_r($order, true));
        //send
        if (function_exists('retailCrmBeforeOrderSend')) {
            $newResOrder = retailCrmBeforeOrderSend($order, $arFields);
            if (is_array($newResOrder) && !empty($newResOrder)) {
                $order = $newResOrder;
            } elseif ($newResOrder === false) {
                RCrmActions::eventLog(
                    'RetailCrmOrder::orderSend',
                    'retailCrmBeforeOrderSend()',
                    'OrderID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeOrderSend'
                );

                return false;
            }
        }


		$trading_id = getTradingOrderID($arFields['ID']);
		if($trading_id > 0){
			$arTrading = array(
				4 => "yandex-fbs",
				5 => "yandex-dbs",
				6 => "wb",
				7 => "sber",
				8 => "ozon",
			);
			if($arTrading[$trading_id]){
				$order["orderMethod"] = $arTrading[$trading_id];
			}
		}elseif(getOnlinerKeyOrderID($arFields['ID']) !== false){
			$order["orderMethod"] = "onliner";
		}else{
			$order["orderMethod"] = "shopping-cart";
		}


		//AddMessage2Log($arFields);



        if ('ordersEdit' === $methodApi) {
            $order = RetailCrmService::unsetIntegrationDeliveryFields($order, $breakPhone);
        }

		$Logger->log("LOG", "pre order after unsetIntegrationDeliveryFields - ".print_r($order, true));

        $normalizer = new RestNormalizer();
		$normalizer->clear = false;
        $order = $normalizer->normalize($order, 'orders');

        Logger::getInstance()->write($order, 'orderSend');

		$Logger->log("LOG", "order который отправляем в систему - ".print_r($order, true));

        if ($send) {
            if (!RCrmActions::apiMethod($api, $methodApi, __METHOD__, $order, $site)) {
                return false;
            }
        }

        return $order;
    }

    /**
     * Mass order uploading, without repeating; always returns true, but writes error log
     *
     * @param int  $pSize
     * @param bool $failed -- flag to export failed orders
     * @param bool $orderList
     *
     * @return boolean
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function uploadOrders($pSize = 50, $failed = false, $orderList = false)
    {
        if (!RetailcrmDependencyLoader::loadDependencies()) {
            return true;
        }

        $resOrders = array();
        $resCustomers = array();
        $resCustomersAdded = array();
        $resCustomersCorporate = array();
        $orderIds = array();

        $lastUpOrderId = RetailcrmConfigProvider::getLastOrderId();
        $failedIds = RetailcrmConfigProvider::getFailedOrdersIds();

        if ($failed == true && $failedIds !== false && count($failedIds) > 0) {
            $orderIds = $failedIds;
        } elseif ($orderList !== false && count($orderList) > 0) {
            $orderIds = $orderList;
        } else {
            $dbOrder = \Bitrix\Sale\Internals\OrderTable::GetList(array(
                'order'   => array("ID" => "ASC"),
                'filter'  => array('>ID' => $lastUpOrderId),
                'limit'   => $pSize,
                'select'  => array('ID')
            ));

            while ($arOrder = $dbOrder->fetch()) {
                $orderIds[] = $arOrder['ID'];
            }
        }

        if (count($orderIds) <= 0) {
            return false;
        }

        $optionsSitesList = RetailcrmConfigProvider::getSitesList();
        $optionsOrderTypes = RetailcrmConfigProvider::getOrderTypes();
        $optionsDelivTypes = RetailcrmConfigProvider::getDeliveryTypes();
        $optionsPayTypes = RetailcrmConfigProvider::getPaymentTypes();
        $optionsPayStatuses = RetailcrmConfigProvider::getPaymentStatuses(); // --statuses
        $optionsPayment = RetailcrmConfigProvider::getPayment();
        $optionsOrderProps = RetailcrmConfigProvider::getOrderProps();
        $optionsLegalDetails = RetailcrmConfigProvider::getLegalDetails();
        $optionsContragentType = RetailcrmConfigProvider::getContragentTypes();
        $optionsCustomFields = RetailcrmConfigProvider::getCustomFields();

        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());

        $arParams = array(
            'optionsOrderTypes'     => $optionsOrderTypes,
            'optionsDelivTypes'     => $optionsDelivTypes,
            'optionsPayTypes'       => $optionsPayTypes,
            'optionsPayStatuses'    => $optionsPayStatuses,
            'optionsPayment'        => $optionsPayment,
            'optionsOrderProps'     => $optionsOrderProps,
            'optionsLegalDetails'   => $optionsLegalDetails,
            'optionsContragentType' => $optionsContragentType,
            'optionsSitesList'      => $optionsSitesList,
            'optionsCustomFields'   => $optionsCustomFields,
        );

        $recOrders = array();

        foreach ($orderIds as $orderId) {
            $site = null;
            $id = \Bitrix\Sale\Order::load($orderId);

            if (!$id) {
                continue;
            }

            $arCustomer = array();
            $arCustomerCorporate = array();
            $order = self::orderObjToArr($id);
            $user = Bitrix\Main\UserTable::getById($order['USER_ID'])->fetch();
            $site = RetailCrmOrder::getSite($order['LID'], $optionsSitesList);

            if (true === $site) {
                continue;
            }

            if ("Y" == RetailcrmConfigProvider::getCorporateClientStatus()
                && $optionsContragentType[$order['PERSON_TYPE_ID']] == 'legal-entity'
            ) {
                // TODO check if order is corporate, and if it IS - make corporate order
                $arCustomer = RetailCrmUser::customerSend(
                    $user,
                    $api,
                    'individual',
                    false,
                    $site
                );

                $arCustomerCorporate = RetailCrmCorporateClient::clientSend(
                    $order,
                    $api,
                    'legal-entity',
                    false,
                    true,
                    $site
                );

                $arParams['orderCompany'] = isset($arCustomerCorporate['companies'])
                    ? reset($arCustomerCorporate['companies']) : null;

                $arParams['contactExId'] = $user['ID'];
            } else {
                $arCustomer = RetailCrmUser::customerSend(
                    $user,
                    $api,
                    $optionsContragentType[$order['PERSON_TYPE_ID']],
                    false,
                    $site
                );

                if (isset($arParams['contactExId'])) {
                    unset($arParams['contactExId']);
                }
            }

            $arOrders = self::orderSend($order, $api, $arParams, false, $site,'ordersCreate');

            if (!$arCustomer || !$arOrders) {
                continue;
            }

            if (!empty($arCustomerCorporate) && !empty($arCustomerCorporate['nickName'])) {
                $resCustomersCorporate[$arCustomerCorporate['nickName']] = $arCustomerCorporate;
            }

            $email = isset($arCustomer['email']) ? $arCustomer['email'] : '';

            if (!in_array($email, $resCustomersAdded)) {
                $resCustomersAdded[] = $email;
                $resCustomers[$order['LID']][] = $arCustomer;
            }

            $resCustomers[$order['LID']][] = $arCustomer;
            $resOrders[$order['LID']][] = $arOrders;
            $recOrders[] = $orderId;
        }

        if (count($resOrders) > 0) {
            if (false === RetailCrmOrder::uploadCustomersList($resCustomers, $api, $optionsSitesList)) {
                return false;
            }

            if ("Y" == RetailcrmConfigProvider::getCorporateClientStatus()) {
                $cachedCorporateIds = array();

                foreach ($resOrders as $packKey => $pack) {
                    foreach ($pack as $key => $orderData) {
                        if (isset($orderData['contragent']['contragentType'])
                            && $orderData['contragent']['contragentType'] == 'legal-entity'
                            && !empty($orderData['contragent']['legalName'])
                        ) {
                            if (isset($cachedCorporateIds[$orderData['contragent']['legalName']])) {
                                $orderData['customer'] = array(
                                    'id' => $cachedCorporateIds[$orderData['contragent']['legalName']]
                                );
                            } else {
                                $corpData = $api->customersCorporateList(array(
                                    'nickName' => array($orderData['contragent']['legalName'])
                                ));

                                if ($corpData
                                    && $corpData->isSuccessful()
                                    && $corpData->offsetExists('customersCorporate')
                                    && !empty($corpData['customersCorporate'])
                                ) {
                                    $corpData = $corpData['customersCorporate'];
                                    $corpData = reset($corpData);

                                    $orderData['customer'] = array('id' => $corpData['id']);
                                    $cachedCorporateIds[$orderData['contragent']['legalName']] = $corpData['id'];

                                    RetailCrmCorporateClient::addCustomersCorporateAddresses(
                                        $orderData['customer']['id'],
                                        $orderData['contragent']['legalName'],
                                        $orderData['delivery']['address']['text'],
                                        $api,
                                        $site = null
                                    );
                                } elseif (array_key_exists(
                                    $orderData['contragent']['legalName'],
                                    $resCustomersCorporate
                                )) {
                                    $createResponse = $api
                                        ->customersCorporateCreate(
                                            $resCustomersCorporate[$orderData['contragent']['legalName']]
                                        );

                                    if ($createResponse && $createResponse->isSuccessful()) {
                                        $orderData['customer'] = array('id' => $createResponse['id']);
                                        $cachedCorporateIds[$orderData['contragent']['legalName']]
                                            = $createResponse['id'];
                                    }
                                }

                                time_nanosleep(0, 250000000);
                            }

                            $pack[$key] = $orderData;
                        }
                    }

                    $resOrders[$packKey] = $pack;
                }
            }

            if (false === RetailCrmOrder::uploadOrdersList($resOrders, $api, $optionsSitesList)) {
                return false;
            }

            if ($failed == true && $failedIds !== false && count($failedIds) > 0) {
                RetailcrmConfigProvider::setFailedOrdersIds(array_diff($failedIds, $recOrders));
            } elseif ($lastUpOrderId < max($recOrders) && $orderList === false) {
                RetailcrmConfigProvider::setLastOrderId(max($recOrders));
            }
        }

        return true;
    }

    /**
     * @param array $resCustomers
     * @param RetailCrm\ApiClient $api
     * @param array $optionsSitesList
     *
     * @return array|false
     */
    public static function uploadCustomersList($resCustomers, $api, $optionsSitesList)
    {
        return RetailCrmOrder::uploadItems(
            $resCustomers,
            'customersUpload',
            'uploadedCustomers',
            $api,
            $optionsSitesList
        );
    }

    /**
     * @param array $resOrders
     * @param RetailCrm\ApiClient $api
     * @param array $optionsSitesList
     *
     * @return array|false
     */
    public static function uploadOrdersList($resOrders, $api, $optionsSitesList)
    {
        return RetailCrmOrder::uploadItems(
            $resOrders,
            'ordersUpload',
            'uploadedOrders',
            $api,
            $optionsSitesList
        );
    }

    /**
     * @param string $key
     * @param array $optionsSitesList
     *
     * @return false|mixed|null
     */
    public static function getSite($key, $optionsSitesList)
    {
        if ($optionsSitesList) {
            if (array_key_exists($key, $optionsSitesList) && $optionsSitesList[$key] != null) {
                return $optionsSitesList[$key];
            } else {
                return false;
            }
        }

        return null;
    }

    /**
     * @param array $pack
     * @param string $method
     * @param string $keyResponse
     * @param RetailCrm\ApiClient $api
     * @param array $optionsSitesList
     *
     * @return array|false
     */
    public static function uploadItems($pack, $method, $keyResponse, $api, $optionsSitesList)
    {
        $uploaded = array();
        $sizePack = 50;

        foreach ($pack as $key => $itemLoad) {
            $site = RetailCrmOrder::getSite($key, $optionsSitesList);

            if (true === $site) {
                continue;
            }

            $chunkList = array_chunk($itemLoad, $sizePack, true);

            foreach ($chunkList as $chunk) {
                time_nanosleep(0, 250000000);

                /** @var \RetailCrm\Response\ApiResponse|bool $response */
                $response = RCrmActions::apiMethod(
                    $api,
                    $method,
                    __METHOD__,
                    $chunk,
                    $site
                );

                if ($response === false) {
                    return false;
                }

                if ($response instanceof \RetailCrm\Response\ApiResponse) {
                    if ($response->offsetExists($keyResponse)) {
                        $uploaded = array_merge($uploaded, $response[$keyResponse]);
                    }
                }
            }
        }

        return $uploaded;
    }

    /**
     * Returns true if provided order array is corporate order data
     *
     * @param array|\ArrayAccess $order
     *
     * @return bool
     */
    public static function isOrderCorporate($order)
    {
        return (is_array($order) || $order instanceof ArrayAccess)
            && isset($order['customer'])
            && isset($order['customer']['type'])
            && $order['customer']['type'] == 'customer_corporate';
    }

    /**
     * Converts order object to array
     *
     * @param \Bitrix\Sale\Order $obOrder
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function orderObjToArr($obOrder)
    {
        $culture = new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "Y-m-d HH:i:s"));
        $arOrder = array(
            'ID'               => $obOrder->getId(),
            'NUMBER'           => $obOrder->getField('ACCOUNT_NUMBER'),
            'LID'              => $obOrder->getSiteId(),
            'DATE_INSERT'      => $obOrder->getDateInsert()->toString($culture),
            'STATUS_ID'        => $obOrder->getField('STATUS_ID'),
            'USER_ID'          => $obOrder->getUserId(),
            'PERSON_TYPE_ID'   => $obOrder->getPersonTypeId(),
            'CURRENCY'         => $obOrder->getCurrency(),
            'PAYMENTS'         => array(),
            'DELIVERYS'        => array(),
            'PRICE_DELIVERY'   => $obOrder->getDeliveryPrice(),
            'PROPS'            => $obOrder->getPropertyCollection()->getArray(),
            'DISCOUNTS'        => $obOrder->getDiscount()->getApplyResult(),
            'BASKET'           => array(),
            'USER_DESCRIPTION' => $obOrder->getField('USER_DESCRIPTION'),
            'COMMENTS'         => $obOrder->getField('COMMENTS'),
            'REASON_CANCELED'  => $obOrder->getField('REASON_CANCELED')
        );

        $shipmentList = $obOrder->getShipmentCollection();

        foreach ($shipmentList as $shipmentData) {
            if ($shipmentData->isSystem()) {
                continue;
            }

            if ($shipmentData->getDeliveryId()) {
                $delivery = \Bitrix\Sale\Delivery\Services\Manager::getById($shipmentData->getDeliveryId());
                $siteDeliverys = RCrmActions::DeliveryList();
                foreach ($siteDeliverys as $siteDelivery) {
                    if ($siteDelivery['ID'] == $delivery['ID'] && $siteDelivery['PARENT_ID'] == 0) {
                        unset($delivery['PARENT_ID']);
                    }
                }
                if ($delivery['PARENT_ID']) {
                    $servise = explode(':', $delivery['CODE']);
                    $shipment = array('id' => $delivery['PARENT_ID'], 'service' => $servise[1]);
                } else {
                    $shipment = array('id' => $delivery['ID']);
                }
                $arOrder['DELIVERYS'][] = $shipment;
            }
        }

        $paymentList = $obOrder->getPaymentCollection();

        foreach ($paymentList as $paymentData) {
            $arOrder['PAYMENTS'][] = $paymentData->getFields()->getValues();
        }

        $basketItems = $obOrder->getBasket();

        foreach ($basketItems as $item) {
            $arOrder['BASKET'][] = $item->getFields();
        }

        return $arOrder;
    }

	public static function sendFromForm($arFields = array()){

        $optionsSitesList = RetailcrmConfigProvider::getSitesList();
        $optionsOrderTypes = RetailcrmConfigProvider::getOrderTypes();
        $optionsDelivTypes = RetailcrmConfigProvider::getDeliveryTypes();
        $optionsPayTypes = RetailcrmConfigProvider::getPaymentTypes();
        $optionsPayStatuses = RetailcrmConfigProvider::getPaymentStatuses(); // --statuses
        $optionsPayment = RetailcrmConfigProvider::getPayment();
        $optionsOrderProps = RetailcrmConfigProvider::getOrderProps();
        $optionsLegalDetails = RetailcrmConfigProvider::getLegalDetails();
        $optionsContragentType = RetailcrmConfigProvider::getContragentTypes();
        $optionsCustomFields = RetailcrmConfigProvider::getCustomFields();

        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());

		//prent($optionsOrderProps);die;

		$arOrder = array(
			array (
				'status' => 'new',
				'firstName' => $arFields["NAME"],
				'phone' => $arFields["PHONE"],
				'email' => $arFields["EMAIL"],
				'orderMethod'	=> 'feedback',
				"customerComment" => $arFields["COMMENT"],
				"customFields" => array(
					"roistat" => isset($_COOKIE['roistat_visit']) ? $_COOKIE['roistat_visit'] : "nocookie",
					"utmstat" => isset($_COOKIE['utmstat_client_id']) ? $_COOKIE['utmstat_client_id'] : null,
				),
			)
		);
//AddMessage2Log($arOrder);
		$arOrders = RCrmActions::apiMethod($api, 'ordersUpload', __METHOD__, $arOrder, $optionsSitesList[SITE_ID]);
	}
}
