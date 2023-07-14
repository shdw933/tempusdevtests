<?
class CMaxyssWbproductTab{
    public static $_instance = null;

    public static function getInstance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Инициализация и запуск
     * Если не вернёт специальный массив, то прочие методы не запустятся
     * @param array $params Данные по админке
     * @return boolean
     */

    public static function onInit() {
        if (!$_REQUEST['IBLOCK_ID']) {
            return false;
        }
        $params_new = array(
            "TABSET" => "WB_TAB",
            "GetTabs" => array("CMaxyssWbproductTab", "tabs"),
            "ShowTab" => array("CMaxyssWbproductTab", "showtab"),
            "Action" => array("CMaxyssWbproductTab", "action"),
            "Check" => array("CMaxyssWbproductTab", "check"),
        );

        return $params_new;
    }

    public static function action($params) {
        return true;
    }

    public static function check($params) {
        return true;
    }

    /**
     * Возвращает параметры будущей вкладки
     * @param type $arArgs
     * @return type
     */
    public static function tabs($arArgs) {
//        echo '<pre>', print_r($arArgs),'</pre>';
        return array(
            array(
                "DIV" => "wb_edit1",
                "TAB" => "wildberries.ru",
                "ICON" => "sale",
                "TITLE" => "wildberries.ru",
                "SORT" => 100
            )
        );
    }

    /**
     * Вывод нужной вкладки
     * @param type $divName
     * @param type $params
     * @param type $bVarsFromForm
     */
    public static function showtab($divName, $params, $bVarsFromForm) {

        //return;

        if ($divName == "wb_edit1") {
            ?>

            <tr>
                <td id="MAXYSS_WB_TD">
                    <?
                    $properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$params['IBLOCK']['ID'], "CODE"=>"%_WB"));
                    $propertiesList = array();
                    $propertiesId = array();
                    while ($prop_fields = $properties->GetNext())
                    {
                        $propertiesList[] = $prop_fields;
                    }
                    foreach ($propertiesList as $prop){
                        $propertiesId[] = $prop['ID'];
                    }
                    sort($propertiesId);
                    ?>
                    <script>
                        var ids = <?=CUtil::PhpToJSObject($propertiesId, false, true)?>;
                        var wb_tab_block = document.querySelector('#MAXYSS_WB_TD');
                        var admBlock = document.querySelector('.adm-detail-block');
                        for(var i = 0; i < ids.length; i++){
                            var row = admBlock.querySelector('#tr_PROPERTY_'+ids[i]+'');
                            if(row){
                                wb_tab_block.appendChild(row);
                            }
                        }
                    </script>
                </td>
            </tr>
            <?
        }
    }
}