<? require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
CModule::IncludeModule('maxyss.ozon');
CModule::IncludeModule('iblock');
use \Bitrix\Main\Config\Option;
if($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_MODULE_NAME) < "R") die();
$right_module_maxyss_ozon = ($GLOBALS['APPLICATION']->GetGroupRight(MAXYSS_MODULE_NAME) == "W")? true : false;
if($_REQUEST['action'] == 'get_attr'){
    $attr = CCustomTypeOzonCat::GetAttrOzon($ClientId = OZON_ID, $ApiKey = OZON_API_KEY, $base_url = OZON_BASE_URL, $category_id = htmlspecialcharsbx($_REQUEST['category']));

    if(!isset($attr['error'])) {
        echo '<table>';
        foreach ($attr as $at) {
            $multiple = '';
            if ($at['id'] == '8292')
                continue;
            if ($at['id'] == '10289')
                continue;
            $star = ($at['is_required']) ? '*' : '';
            if ($at['type'] == 'text') {
                echo '<tr class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><input class="ozon_atr" type="text" value="" data-ozon-attrid="' . $at['id'] . '" /></td></tr>';
            }
            if ($at['type'] == 'bool') {
                echo '<tr class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><input class="ozon_atr" type="checkbox" value="" data-ozon-attrid="' . $at['id'] . '" /></td></tr>';
            }
            if ($at['type'] == 'child') {
                echo '<tr class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td>';
                foreach ($at['child'] as $child) {
                    if ($child['type'] == 'text') {
                        echo '<label title="' . htmlspecialcharsbx($child['description']) . '">' . $child['name'] . '  ' . $star . '</label><input class="ozon_atr" type="text" value="" data-ozon-attrid="' . $at['id'] . '" data-ozon-child-attrid="' . $child['id'] . '" /><br />';
                    }
                }
                echo '</td></tr>';
            }
            if ($at['type'] == 'option') {
                if ($at['is_collection'] == 1)
                    $multiple = 'multiple="multiple"';

                echo '<tr class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><select style="max-width: 300px;" ' . $multiple . ' data-ozon-attrid="' . $at['id'] . '" class="ozon_atr">';
                if($multiple == '')
                    echo '<option value=""></option>';
                $i_selected = 0;
                foreach ($at['option'] as $option) {
                    $selected_one = '';
//                    if ($i_selected == 0 && $multiple == '' && $at['is_required'])
//                        $selected_one = 'selected="selected"';

                    echo '<option ' . $selected_one . ' value="' . $option['id'] . '">' . $option['value'] . '</option>';
                    $i_selected++;
                }
                echo '</select></td></tr>';
            }
            // v2
            if ($at['type'] != 'ImageURL' && is_array($at['values'])) {
                if ($at['is_collection'] == 1)
                    $multiple = 'multiple="multiple"';

                echo '<tr class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><select style="max-width: 300px;" ' . $multiple . ' data-ozon-attrid="' . $at['id'] . '" class="ozon_atr">';
                if($multiple == '')
                    echo '<option value=""></option>';
                $i_selected = 0;
                foreach ($at['values'] as $option) {
                    $selected_one = '';
//                    if ($i_selected == 0 && $multiple == '' && $at['is_required'])
//                        $selected_one = 'selected="selected"';

                    echo '<option ' . $selected_one . ' value="' . $option['id'] . '">' . $option['value'] . '</option>';
                    $i_selected++;
                }
                echo '</select></td></tr>';
            }
            if ($at['type'] == 'String' && !is_array($at['values'])) {
                echo '<tr class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><input class="ozon_atr" type="text" value="" data-ozon-attrid="' . $at['id'] . '" /></td></tr>';
            }

        }
        echo '</table>';
    }else{
        echo $attr['error'];
    }
}
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

if($_REQUEST['action'] == 'get_attr_from_bd'){
    $bdIblock = CIBlock::GetList(
        Array(),
        Array(
            "CODE"=>'ozon'
        ), true
    );
    if($arIblock = $bdIblock->Fetch())
    {
        $iblock_id = $arIblock['ID'];
    }

    $attr = array();

    $arFilter = Array("IBLOCK_ID"=>$iblock_id, 'SECTION_CODE'=>htmlspecialcharsbx($_REQUEST['category']));

    if(Option::get(MAXYSS_MODULE_NAME, "REQURED_MORE", "") != 'Y'){
        $arFilter['PROPERTY_is_required'] = 1;
    }
    $res_el = CIBlockElement::GetList(Array('PROPERTY_is_required'=>'desc'), $arFilter, false, false, array('ID','IBLOCK_ID','DETAIL_TEXT','PREVIEW_TEXT','NAME','PROPERTY_*'));
    while($ob = $res_el->GetNextElement())
    {
        $arFields = $ob->GetFields();

        $arProps = $ob->GetProperties();
//        echo '<pre>', print_r($arProps), '</pre>' ;

        $arVals = unserialize($arFields['~DETAIL_TEXT']);
        $arSinc = unserialize($arFields['~PREVIEW_TEXT']);

        $one_attr['id'] = $arProps['id']['VALUE'];
        $one_attr['name'] = $arFields['NAME'];
        $one_attr['description'] = $arProps['description']['VALUE'];
        $one_attr['type'] = $arProps['type']['VALUE'];
        $one_attr['is_collection'] = $arProps['is_collection']['VALUE'];
        $one_attr['is_required'] = $arProps['is_required']['VALUE'];
        $one_attr['option'] = $arVals;
        $one_attr['sinc'] = unserialize($arFields['~PREVIEW_TEXT']);
        $attr[]=$one_attr;
    }
    if(!empty($attr)) {
        ?>
        <style>
            .ozon_attr .add_prop_sinc{
                margin-left: 5px;
                line-height: 1.6;
                text-align: center;
                width: 22px;
                height: 22px;
                cursor: pointer;
                border: #c5c5c5 solid 1px;
                display: inline-block;
                background-color: darkgrey;
            }
            .ozon_attr .add_prop_sinc.green{
                background-color: green;
            }
        </style>
        <?
        $array_all = array(8229,);

        echo '<table>';
        foreach ($attr as &$at) {

            if(!empty($at['option'])) {
                usort($at['option'], 'cmp_ms');
            }
            $multiple = '';
            $hide = '';
            if ($at['id'] == '8292' || $at['id'] == '10289'/*|| $at['id'] == '31' || $at['id'] == '85'*/)
                continue;

//            if($at['id'] == '31' || $at['id'] == '85'){
//                $hide = 'style="display: none"';
//            }
            $span = '';
            $sinc_class = '';
            if(
                (!in_array($at['id'], $array_all))
//                ($at['type'] == 'URL' && $at['dictionary_id'] == 0) ||
//                ($at['type'] == 'ImageURL' && $at['dictionary_id'] == 0) ||
//                ($at['type'] == 'Decimal') ||
//                ($at['type'] == 'decimal') ||
//                ($at['type'] == 'Integer') ||
//                ($at['type'] == 'integer') ||
//                ($at['id'] == 85) ||
//                ($at['id'] == 31) ||
//                ($at['type'] == 'multiline')
            ) {
//                $arFilter = Array("IBLOCK_ID" => $iblock_id, 'PROPERTY_id' => $atribute['id']);
//                $flag_all_section = true;

                if(!empty($at['sinc'][intval($_REQUEST["iblock_id"])])) $sinc_class = ' green';

                    $span = '<span class="add_prop_sinc'.$sinc_class.'" id="span_'.$at['id'].'" onclick="add_prop_sinc(' . $at['id'] . ',' . intval($_REQUEST["iblock_id"]) .',\'' . $at['name'] . '\')">+</span>';
            }
            $star = ($at['is_required']) ? '*' : '';
//            echo '<pre>', var_dump($at['option']), '</pre>' ;
            if ($at['type'] == 'String' && !$at['option']) {
                echo '<tr '.$hide.' class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><input class="ozon_atr" type="text" value="" data-ozon-attrid="' . $at['id'] . '" />'.$span.'</td></tr>';
            }
            if ($at['type'] == 'multiline' && !$at['option']) {
                echo '<tr '.$hide.' class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><textarea class="ozon_atr"  data-ozon-attrid="' . $at['id'] . '" ></textarea>'.$span.'</td></tr>';
            }
            if (($at['type'] == 'Decimal' || $at['type'] == 'decimal' || $at['type'] == 'Integer'|| $at['type'] == 'integer') && !$at['option']) {
                echo '<tr '.$hide.' class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><input class="ozon_atr" type="number" step="any" value="" data-ozon-attrid="' . $at['id'] . '" />'.$span.'</td></tr>';
            }
            if ($at['type'] == 'Boolean') {
                echo '<tr '.$hide.' class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><input class="ozon_atr" type="checkbox" value="" data-ozon-attrid="' . $at['id'] . '" /></td></tr>';
            }
            if ($at['type'] == 'child') {
                echo '<tr '.$hide.' class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td>';
                foreach ($at['child'] as $child) {
                    if ($child['type'] == 'text') {
                        echo '<label title="' . htmlspecialcharsbx($child['description']) . '">' . $child['name'] . '  ' . $star . '</label><input class="ozon_atr" type="text" value="" data-ozon-attrid="' . $at['id'] . '" data-ozon-child-attrid="' . $child['id'] . '" /><br />';
                    }
                }
                echo '</td></tr>';
            }
            if (/*$at['type'] == 'String' && */ is_array($at['option']) && $at['id'] != 85 && $at['id'] != 31) {
                if ($at['is_collection'] == 1)
                    $multiple = 'multiple="multiple"';

                echo '<tr  '.$hide.'  class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><select style="max-width: 300px;" ' . $multiple . ' data-ozon-attrid="' . $at['id'] . '" class="ozon_atr">';
                if($multiple == '')
                    if($at['id'] !=31 && $at['id'] !=85)
                        echo '<option value=""></option>';
                $i_selected = 0;
                foreach ($at['option'] as $option) {
                    $selected_one = '';
//                    if ($i_selected == 0 && $multiple == '' && $at['is_required'])
//                        $selected_one = 'selected="selected"';

                    echo '<option ' . $selected_one . ' value="' . $option['id'] . '">' . $option['value'] . '</option>';
                    $i_selected++;
                }
                echo '</select>'.$span.'</td></tr>';
            }
            elseif (is_array($at['option']) && ($at['id'] != 85 || $at['id'] != 31)){
                echo '<tr '.$hide.' class="bx-in-group"><td><label title="' . htmlspecialcharsbx($at['description']) . '">' . $at['name'] . $star . '</label></td><td><input class="ozon_atr autocomplete_prop" type="text" value="" data-ozon-attrid="' . $at['id'] . '" data-ozon-attr-valueid="" />'.$span.'</td></tr><script>$(".autocomplete_prop").autocomplete({ source: "/bitrix/tools/maxyss.ozon/ozon_ajax.php?id='.$at['id'].'", minLength: 2, delay: 1000,search: function( event, ui ) { var ui_result_ozon_attr_id = BX.showWait("ozon_attr_id");}, response: function( event, ui ) { if(ui.content.length == 0){
    ui.content.push({"label":BX.message("MAXYSS_OZON_NOT_FOUND_BREND")});} BX.closeWait("ozon_attr_id"); }, select: function (event, ui) {
    $(this).data("ozon-attr-valueid", ui.item.id);
}});</script>';
            }

        }
        echo '</table>';
    }else{
        echo $attr['error'];
    }
}
if($_REQUEST['term']){
    $arList = array();
    if(LANG_CHARSET == 'windows-1251') {
        header('Content-Type: json; charset=utf-8');
        $term = CMaxyssOzonAgent::deepIconv($_REQUEST['term']);
    }else{
        $term = $_REQUEST['term'];
    }
    if(isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0 && $term != '') {
        $bdIblock = CIBlock::GetList(
            Array(),
            Array(
                "CODE" => 'ozon'
            ), true
        );
        if ($arIblock = $bdIblock->Fetch()) {
            $iblock_id = $arIblock['ID'];
        }

        $arVals = array();
        $arFilter = Array("IBLOCK_ID" => $iblock_id, 'PROPERTY_id' => intval($_REQUEST['id']));
        $res_el = CIBlockElement::GetList(Array('id' => 'desc'), $arFilter, false, array('navTopCount'=>10), array('ID', 'IBLOCK_ID', 'DETAIL_TEXT', 'PREVIEW_TEXT', 'NAME', 'PROPERTY_*'));
        if ($ob = $res_el->GetNextElement()) {
            $arFields = $ob->GetFields();
            $arVals = unserialize($arFields['~DETAIL_TEXT']);
            if(!empty($arVals)){
                foreach ($arVals as $value){
                    if(mb_stripos($value['value'],$term) !== false){
                        $arList[] = array('label'=>$value['value'], 'value'=>$value['value'], 'id'=>$value['id']);
                    }
                }
            }
        }
    }
//    if(empty($arList)) $arList[] = array('label'=>GetMessage('MAXYSS_OZON_NOT_FOUND_BREND'));
    echo \Bitrix\Main\Web\Json::encode($arList);
}

if($_REQUEST['action'] == 'get_attr_v2'){
    $res = array();
    $step = htmlspecialcharsbx($_REQUEST['step']);
    $all_count = count($_REQUEST['sections']);
    $offset_array = $step-1;
    if ($all_count > 0) {

        $category_id = $_REQUEST['sections'][key(array_slice($_REQUEST['sections'], $offset_array, 1, true))];

        // get section

        $section_id = '';
        $arFilter = Array('IBLOCK_CODE'=>'ozon', '=NAME'=>$category_id);
        $db_list = CIBlockSection::GetList(Array('name'=>'asc'), $arFilter, false);
        if($ar_result = $db_list->GetNext())
        {
            $section_id = $ar_result['ID'];
            $iblock_id = $ar_result['IBLOCK_ID'];
        }


        $attr = CCustomTypeOzonCat::GetAttrOzonFromSave($ClientId = OZON_ID, $ApiKey = OZON_API_KEY, $base_url = OZON_BASE_URL, $category_id);
        foreach ($attr as $atribute){
            $element_id = false;
            $IBLOCK_SECTION = array();

            $el = new CIBlockElement;

            $PROP = array();
            $PROP['is_collection'] = $atribute['is_collection'];
            $PROP['is_required'] = $atribute['is_required'];
            $PROP['id'] = $atribute['id'];
            $PROP['group_name'] = $atribute['group_name'];
            $PROP['group_id'] = $atribute['group_id'];
            $PROP['dictionary_id'] = $atribute['dictionary_id'];
            $PROP['type'] = $atribute['type'];

//               $arSelect = Array("ID", "NAME", 'IBLOCK_SECTION');
            $arFilter = Array("IBLOCK_ID"=>$iblock_id, 'PROPERTY_id'=>$atribute['id'], 'SECTION_ID'=>$section_id);
            $res_el = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            if($ob = $res_el->GetNextElement())
            {
                $arFields = $ob->GetFields();
                $element_id = $arFields['ID'];

//                   $db_old_groups = CIBlockElement::GetElementGroups($element_id, true);
//                   while($ar_group = $db_old_groups->Fetch())
//                       $IBLOCK_SECTION[] = $ar_group["ID"];
            }

//               $IBLOCK_SECTION[]=$section_id;

            $arLoadProductArray = Array(
                "IBLOCK_SECTION_ID" => $section_id,
                "IBLOCK_ID"      => $iblock_id,
                "PROPERTY_VALUES"=> $PROP,
                "NAME"           => $atribute['name'],
                "ACTIVE"         => "Y",
//                "PREVIEW_TEXT"   => $atribute['description'],
            );

            if($right_module_maxyss_ozon) {
                if ($element_id) {
                    $el->Update($element_id, $arLoadProductArray);
                } else {
                    $element_id = $el->Add($arLoadProductArray);
                }
            }
//               CIBlockElement::SetElementSection($element_id, $IBLOCK_SECTION);
        }
    }
    $res['category_id'] = $category_id;
    $res['all_count'] = $all_count;
    $res['step'] = intval($step);
    $res['attr'] = $attr;
    $res['request'] = $_REQUEST;
    $res['right_module'] = $right_module_maxyss_ozon;
    echo \Bitrix\Main\Web\Json::encode($res);
}

if($_REQUEST['action'] == 'get_section_attr_v2'){
    $res = array();
    if ($_REQUEST['section']) {

        $category_id = $_REQUEST['section'];

        // get section

        $section_id = '';
        $arFilter = Array('IBLOCK_CODE'=>'ozon', '=NAME'=>$category_id);
        $db_list = CIBlockSection::GetList(Array('name'=>'asc'), $arFilter, false);
        if($ar_result = $db_list->GetNext())
        {
            $section_id = $ar_result['ID'];
            $iblock_id = $ar_result['IBLOCK_ID'];
        }

        $ClientId = $_REQUEST['client_id'];
        $ApiKey = CMaxyssOzon::GetApiKey($ClientId);


        $attr = CCustomTypeOzonCat::GetAttrOzonFromSave($ClientId, $ApiKey, $base_url = OZON_BASE_URL, $category_id);

        foreach ($attr as $atribute){
            $flag_all_section = false;
            $element_id = false;
            $IBLOCK_SECTION = array();


            $PROP = array();
            $PROP['is_collection'] = $atribute['is_collection'];
            $PROP['is_required'] = $atribute['is_required'];
            $PROP['id'] = $atribute['id'];
            $PROP['group_name'] = $atribute['group_name'];
            $PROP['group_id'] = $atribute['group_id'];
            $PROP['dictionary_id'] = $atribute['dictionary_id'];
            $PROP['type'] = $atribute['type'];

            $arFilter = Array("IBLOCK_ID"=>$iblock_id, 'PROPERTY_id'=>$atribute['id'], 'SECTION_ID'=>$section_id);
            $array_all = array(8229, /*85, 31*/);
//               $arSelect = Array("ID", "NAME", 'IBLOCK_SECTION');
            if(
                (strtolower($atribute['type']) == 'string' && !in_array($atribute['id'], $array_all)) ||
                ($atribute['type'] == 'URL' && $atribute['dictionary_id'] == 0) ||
                ($atribute['type'] == 'ImageURL' && $atribute['dictionary_id'] == 0) ||
                ($atribute['type'] == 'Decimal') ||
                ($atribute['type'] == 'decimal') ||
                ($atribute['type'] == 'Integer') ||
                ($atribute['type'] == 'integer') ||
                ($atribute['id'] == 85) ||
                ($atribute['id'] == 31) ||
                ($atribute['type'] == 'multiline')
            ){
                $arFilter = Array("IBLOCK_ID"=>$iblock_id, 'PROPERTY_id'=>$atribute['id']);
                $flag_all_section = true;
            }


            $res_el = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            $count_duble_attr = 0;
            while($ob = $res_el->GetNextElement())
            {
                $arFields = $ob->GetFields();
                if($count_duble_attr < 1) {
                    $element_id = $arFields['ID'];
                }

                $IBLOCK_SECTION = array();
                if($flag_all_section) {
                    $db_old_groups = CIBlockElement::GetElementGroups($arFields['ID'], true);

                    while ($ar_group = $db_old_groups->Fetch())
                        $IBLOCK_SECTION[] = $ar_group["ID"];

                    $IBLOCK_SECTION[] = $section_id;
                }
                if($count_duble_attr > 0) {
                    CIBlockElement::Delete($arFields['ID']);
                }
                $count_duble_attr++;
            }

//               $IBLOCK_SECTION[]=$section_id;

            $arLoadProductArray = Array(
//                "IBLOCK_SECTION_ID" => $section_id,
                "IBLOCK_ID"      => $iblock_id,
                "PROPERTY_VALUES"=> $PROP,
                "NAME"           => $atribute['name'],
                "ACTIVE"         => "Y",
//                "PREVIEW_TEXT"   => $atribute['description'],
            );

            if($flag_all_section && !empty($IBLOCK_SECTION)){
                $arLoadProductArray["IBLOCK_SECTION"] = $IBLOCK_SECTION;
            }else{
                $arLoadProductArray["IBLOCK_SECTION_ID"] = $section_id;
            }

            $el = new CIBlockElement;

            if($right_module_maxyss_ozon) {
                if ($element_id) {
                    $el->Update($element_id, $arLoadProductArray);
                } else {
                    $element_id = $el->Add($arLoadProductArray);
                }
            }
//               CIBlockElement::SetElementSection($element_id, $IBLOCK_SECTION);
        }
    }
    $res['category_id'] = $category_id;
//    $res['attr'] = $attr;
//    $res['request'] = $_REQUEST;
    echo \Bitrix\Main\Web\Json::encode($res);
}

if($_REQUEST['action'] == 'get_attr_value_v2'){
    $iblock_id = intval($_REQUEST['iblock_id']);
    $el_id = intval($_REQUEST['attr']['element_id']);
    $category_id = intval($_REQUEST['attr']['category_id']);
    $attr_id = intval($_REQUEST['attr']['id']);
    $arAllVals = array();
    $client_id = $_REQUEST['client_id'];

    $arVals = CCustomTypeOzonCat::GetValsOzon($category_id, $attr_id, 0, $client_id);
    $values = '';
    $i=0;
    if(!isset($arVals['error']) && is_array($arVals) && !empty($arVals)) {

        if(is_array($arVals)){
            foreach ($arVals as &$v_info){
                $arValsClear[] = array('id' => $v_info['id'], 'value' => $v_info['value']);
            }
            $arVals = $arValsClear;
        }

        $arAllVals = array_merge($arAllVals, $arVals);
        if (count($arVals) == 5000) {
            $last_el = array_pop($arVals);
            $step_two = 1;
        }
    }else{
        $el = new CIBlockElement;
        $arLoadProductArray = Array(
            "DETAIL_TEXT"    => '',
            "DETAIL_TEXT_TYPE"    => 'text',
        );

        if($right_module_maxyss_ozon)
            $res_add = $el->Update($el_id, $arLoadProductArray);
    }

    if(!empty($arAllVals))
        $values = serialize($arAllVals);

    if(strlen($values)>0){

        $el = new CIBlockElement;
        $arLoadProductArray = Array(
            "DETAIL_TEXT"    => $values,
            "DETAIL_TEXT_TYPE"    => 'text',
        );

        if($right_module_maxyss_ozon)
            $res_add = $el->Update($el_id, $arLoadProductArray);
    }
    $res = array();
    $res['res_add'] = $res_add;
    $res['attr_id'] = $attr_id;
    $res['category_id'] = $category_id;
    $res['values'] = $values;
    $res['el_id'] = $el_id;
    $res['step_two'] = $step_two;
//    $res['request'] = $_REQUEST;
    $res['last_el_id'] = $last_el['id'];
    echo \Bitrix\Main\Web\Json::encode($res);
}

if($_REQUEST['action'] == 'add_next_50'){
    $iblock_id = intval($_REQUEST['iblock_id']);
    $el_id = intval($_REQUEST['el_id']);
    $category_id = intval($_REQUEST['category_id']);
    $attr_id = intval($_REQUEST['attr_id']);
    $last_el_id = intval($_REQUEST['last_el_id']);
    $step_two = intval($_REQUEST['step_two']);
    $step = intval($_REQUEST['step']);
    $client_id = $_REQUEST['client_id'];

    $arAllVals = array();
    $arVals = array();

    $arFilter = Array("IBLOCK_ID"=>$iblock_id, 'ID'=>$el_id);
    $res_el = CIBlockElement::GetList(Array(), $arFilter, false, false, array('DETAIL_TEXT'));
    if($ob = $res_el->GetNextElement())
    {
        $arFields = $ob->GetFields();
        $arAllVals = unserialize($arFields['~DETAIL_TEXT']);
    }
    $arVals = CCustomTypeOzonCat::GetValsOzon($category_id, $attr_id, $last_el_id, $client_id);

    if(is_array($arVals)){
        foreach ($arVals as &$v_info){
            $arValsClear[] = array('id' => $v_info['id'], 'value' => $v_info['value']);
        }
        $arVals = $arValsClear;
    }


    $last_el=array();
    $arAllVals = array_merge($arAllVals, $arVals);
    if(count($arVals) == 5000) {
        $last_el = array_pop($arVals);
        $step_two++;
    }

    $values = '';
    if(!empty($arAllVals))
        $values = serialize($arAllVals);

    if(strlen($values)>0){

        $el = new CIBlockElement;
        $arLoadProductArray = Array(
            "DETAIL_TEXT"    => $values,
            "DETAIL_TEXT_TYPE"    => 'text',
        );

        if($right_module_maxyss_ozon)
            $res_add = $el->Update($el_id, $arLoadProductArray);
    }

    $res = array();
    $res['res_add'] = $res_add;
    $res['attr_id'] = $attr_id;
    $res['category_id'] = $category_id;
    $res['step_two'] = $step_two;
    $res['step'] = $step;
    $res['el_id'] = $el_id;
    $res['request'] = $_REQUEST;
    $res['last_el_id'] = $last_el['id'];
    echo \Bitrix\Main\Web\Json::encode($res);
}

if($_REQUEST['action'] == 'print_label_ozon'){
    if(is_set($_REQUEST['orders'])) {
        $res = CMaxyssOrderList::get_lable($_REQUEST['orders']);
        if($res['error']) {
            $res['success'] = false;
            echo \Bitrix\Main\Web\Json::encode($res);
        }
        else  return $res;
    }
    else
        echo \Bitrix\Main\Web\Json::encode(array('error' => GetMessage('MAXYSS_OZON_NO_ORDER_SELECTED')));
}

if($_REQUEST['action'] == 'order_to_ship'){
    if(!empty($_REQUEST["orders"])){
        $pakages = array();
        if($_REQUEST['pakages']) $pakages = $_REQUEST['pakages'];
        foreach ($_REQUEST["orders"] as $order_pak){
            $order_ship = CMaxyssOrderList::Order_ship($order_pak, $pakages);
            $answer["order"][$order_pak] = $order_ship;
        }
    }else{
        $answer['error'] = GetMessage('MAXYSS_OZON_NO_ORDER_SELECTED');
    }
    echo \Bitrix\Main\Web\Json::encode($answer);
}

if($_REQUEST['action'] == 'gtd_items_get'){
    if(isset($_REQUEST['orders']) && !empty($_REQUEST['orders'])){
        foreach ($_REQUEST["orders"] as $orderId){
            $order = \Bitrix\Sale\Order::load($orderId);
            if(is_object($order)) {
                $basket = $order->getBasket();
                $basketItems = $basket->getBasketItems();
                $arItemValue = array();
                foreach ($basketItems as $basketItem) {
                    $collection_prop_item = $basketItem->getPropertyCollection();
                    foreach ($collection_prop_item as $item_prop) {
                        if ($item_prop->getField('CODE') == 'PRODUCTS_REQUIRING_GTD') {
                            $arItemValue[$basketItem->getField('PRODUCT_ID')]['NAME'] = $basketItem->getField('NAME');
                            $arItemValue[$basketItem->getField('PRODUCT_ID')]['ID'] = $basketItem->getField('ID');
                            $arItemValue[$basketItem->getField('PRODUCT_ID')]['PRODUCTS_REQUIRING_GTD'] = "Y";
                            $arItemValue[$basketItem->getField('PRODUCT_ID')]['ORDER_ID'] = $orderId;
                            $arItems[$orderId] = $arItemValue;
                        }
                    }
                }
            }
        }
    }
    echo \Bitrix\Main\Web\Json::encode($arItems);
}
if($_REQUEST['action'] == 'add_gtd'){
    if(isset($_REQUEST['gtd']) && !empty($_REQUEST['gtd']) && isset($_REQUEST['order_id']) && $_REQUEST['order_id'] > 0 && isset($_REQUEST['item_id']) && $_REQUEST['item_id'] > 0){
        $order_id = intval($_REQUEST['order_id']);
        $item_id = $_REQUEST['item_id'];
        $prop_isset_id = 0;
        $order = \Bitrix\Sale\Order::load($order_id);
        $basket = $order->getBasket();
        $basketItem = $basket->getItemById($item_id);
        if($basketItem) {
            $collection = $basketItem->getPropertyCollection();
            foreach ($collection as $item)
            {
                if( $item->getField('CODE') == "OZON_PRODUCTS_GTD"){
                    $prop_isset_id = $item->getField('ID');
                }
            }
            if($prop_isset_id > 0){
                $item_prop = $collection->getItemById($prop_isset_id);
                $item_prop->setField('VALUE', htmlspecialcharsbx($_REQUEST['gtd']));
            }else {
                $item_prop = $collection->createItem();
                $item_prop->setFields([
                    'NAME' => 'ozon_products_gtd',
                    'CODE' => 'OZON_PRODUCTS_GTD',
                    'XML_ID' => 'OZON_PRODUCTS_GTD',
                    'VALUE' => htmlspecialcharsbx($_REQUEST['gtd']),
                ]);
            }
            $res_save_order = $order->save();
            if ($res_save_order->isSuccess()) {
                echo \Bitrix\Main\Web\Json::encode(array('SUCCESS' => true));
            }
            else
                echo \Bitrix\Main\Web\Json::encode(array('SUCCESS' => false));
        }
        else
            echo \Bitrix\Main\Web\Json::encode(array('SUCCESS' => false));
    }
    else
        echo \Bitrix\Main\Web\Json::encode(array('SUCCESS' => false));
}
if($_REQUEST['action'] == 'get_log'){
    $file_log = htmlspecialcharsbx($_REQUEST['file']);
    $text = '';
    if(file_exists($file_log)) {
        $log_get = CHelpMaxyss::arr_from_file($file_log);
        $text = explode(' ', $log_get['DATE'])[0] . '<br><br>';
        foreach ($log_get as $key => $item) {

            if ($key != 'DATE') {
                $text .= '<b>' . $key . '</b> - ';

                    foreach ($item as $t) {
                        if(is_array($t)) {
                            $text .= implode(', ', $t) . ';   ';
                        }else{
                            $text .= $t;
                        }
                    }

                $text .= '<br>';
            }
        }
    }
    echo $text;
}

if($_REQUEST['action'] == 'get_documents'){
    $task = CMaxyssOrderList::GetDocuments(htmlspecialcharsbx($_REQUEST['ozon_id']), htmlspecialcharsbx($_REQUEST['warehouse_id']));
    echo \Bitrix\Main\Web\Json::encode($task);
}

if($_REQUEST['action'] == 'get_digital_docs'){
    $docs = CMaxyssOrderList::GetDigitalDoc(htmlspecialcharsbx($_REQUEST['task_id']), htmlspecialcharsbx($_REQUEST['ozon_id']));
    echo \Bitrix\Main\Web\Json::encode($docs);
}
if($_REQUEST['action'] == 'get_pdf'){
    $res = CMaxyssOrderList::GetDocPdf(htmlspecialcharsbx($_REQUEST['task_id']), htmlspecialcharsbx($_REQUEST['ozon_id']));
    echo json_encode($res);
}

if($_REQUEST['action'] == 'check_docs'){
    if($_REQUEST['act_type'] == 'ozon_digital')
        $task_status = CMaxyssOrderList::CheckDocumentsDigital(htmlspecialcharsbx($_REQUEST['task_id']), htmlspecialcharsbx($_REQUEST['ozon_id']));
    else
        $task_status = CMaxyssOrderList::CheckDocuments(htmlspecialcharsbx($_REQUEST['task_id']), htmlspecialcharsbx($_REQUEST['ozon_id']));

    echo \Bitrix\Main\Web\Json::encode($task_status);
}

if($_REQUEST['action'] == 'add_prop_sinc_values'){
    if(!empty($_REQUEST['iblock_id']) && !empty($_REQUEST['prop_id'])){
        $prop_id = htmlspecialcharsbx($_REQUEST['prop_id']);
        $iblock_id = intval($_REQUEST['iblock_id']);
        $res_prop = CIBlockProperty::GetByID($prop_id, $iblock_id);
        if($ar_prop = $res_prop->GetNext()) {
            $prop_sinc_values_array = array();
            if (isset($ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']) && $ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']) {
                $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getRow([
                    'filter' => [
                        '=TABLE_NAME' => $ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']
                    ],
                ]);

                $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
                $main_query = new Bitrix\Main\Entity\Query($entity);
                $main_query->setSelect(array('*'));
                $result = $main_query->exec();
                $result = new CDBResult($result);
                while ($row = $result->Fetch()) {
                    $prop_sinc_values_array[] = array('UF_XML_ID'=>$row["UF_XML_ID"], "ID"=>$row["ID"], "UF_NAME"=>$row['UF_NAME']);
                }
                    echo \Bitrix\Main\Web\Json::encode(array('prop_value' => $prop_sinc_values_array));;
            }
            else
            {
                if($ar_prop['PROPERTY_TYPE'] == 'E'){

                    $res_el_e = CIBlockElement::GetList(Array('NAME'=>'asc'), array('IBLOCK_ID'=>$ar_prop['LINK_IBLOCK_ID']), false, false, array('ID','IBLOCK_ID','NAME',));
                    while($ob = $res_el_e->GetNextElement()) {
                        $arFields = $ob->GetFields();
                        $prop_sinc_values_array[] = array("ID" => $arFields["ID"], "VALUE" => $arFields['NAME']);
                    }
                    echo \Bitrix\Main\Web\Json::encode(array('prop_value' => $prop_sinc_values_array));


                }else {
                    $prop_sinc_values = CIBlockPropertyEnum::GetList(Array("DEF" => "DESC", "SORT" => "ASC"), Array("IBLOCK_ID" => $iblock_id, "PROPERTY_ID" => $prop_id));
                    $prop_sinc_values_select = '';
//                $count_enum = 0;
                    while ($enum_fields = $prop_sinc_values->GetNext()) {
                        $prop_sinc_values_array[] = array("ID" => $enum_fields["ID"], "VALUE" => $enum_fields['VALUE']);
                    }
                    echo \Bitrix\Main\Web\Json::encode(array('prop_value' => $prop_sinc_values_array));
                }
            }
        }
    }
}
if($_REQUEST['action'] == 'save_prop_sinc_values'){

    if(intval($_REQUEST['attr_id']) > 0){
        $attr_id =  intval($_REQUEST['attr_id']);
        $bdIblock = CIBlock::GetList(
            Array(),
            Array(
                "CODE"=>'ozon'
            ), true
        );
        if($arIblock = $bdIblock->Fetch()) {
            $attr = array();
            $arFilter = Array("IBLOCK_ID" => $arIblock['ID'], 'PROPERTY_id' => $attr_id);
            $res_el = CIBlockElement::GetList(Array('PROPERTY_is_required'=>'desc'), $arFilter, false, false, array('ID','IBLOCK_ID','DETAIL_TEXT','NAME','PROPERTY_*'));
            while($ob = $res_el->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arAttrBxId[] = $arFields['ID'];
            }
        }

    }
    if(!empty($_REQUEST['prop_bx'])){
        if(is_array($_REQUEST['prop_bx'])) {
        foreach ($_REQUEST['prop_bx'] as $key => $code_prop){
            $sinc = array();
                if(is_array($_REQUEST['prop_value'][$key])) {
            foreach ($_REQUEST['prop_value'][$key] as $k =>$val){
                if($val != '')
                    $sinc[$val] = $_REQUEST['attr_value'][$key][$k];
            }
                }
            $arSinc[$key]['prop_code'] = $code_prop;
            $arSinc[$key]['sinc'] = $sinc;
        }
    }
    }
    if(!empty($arAttrBxId)){
        foreach ($arAttrBxId as $id){
            $elem = new CIBlockElement;
            $arLoadProductArray = Array(
                "PREVIEW_TEXT"    => serialize($arSinc),
                "PREVIEW_TEXT_TYPE"    => 'TEXT',
            );

            if($right_module_maxyss_ozon) {
                if ($elem->Update($id, $arLoadProductArray)) {
                    $res['attr_id'] = $attr_id;
                } else {
                    $res['error'] .= $el->LAST_ERROR;
                }
            }
        }
    }
    echo \Bitrix\Main\Web\Json::encode($res);
}

if($_REQUEST['action'] == 'add_prop_sinc'){
    if(intval($_REQUEST['attr_id']) > 0){
        $attr_id =  intval($_REQUEST['attr_id']);
        $bdIblock = CIBlock::GetList(
            Array(),
            Array(
                "CODE" => 'ozon'
            ), true
        );
        if ($arIblock = $bdIblock->Fetch()) {


                $attr = array();

                $arFilter = Array("IBLOCK_ID" => $arIblock['ID'], 'PROPERTY_id' => $attr_id);

                $res_el = CIBlockElement::GetList(Array('PROPERTY_is_required' => 'desc'), $arFilter, false, false, array('ID', 'IBLOCK_ID', 'DETAIL_TEXT', 'PREVIEW_TEXT', 'NAME', 'PROPERTY_*'));
                if ($ob = $res_el->GetNextElement()) {
                    $arFields = $ob->GetFields();
                    $arProps = $ob->GetProperties();

                    $arAttr = array();
                    $arAttr = unserialize($arFields['~DETAIL_TEXT']);
                    $arSinc = unserialize($arFields['~PREVIEW_TEXT']);
                    if($attr_id == 31 || $attr_id == 85) {
                        $valSincBrend = array();
                        foreach ($arSinc as $s_prop){
                            foreach ($s_prop['sinc'] as $key_s => $s)
                                $valSincBrend[$key_s] = $s;
                        }

                        foreach ($arAttr as $key_a => &$a){
                            if(array_search($a['id'], $valSincBrend) !== false)
                                continue;
                            else
                                unset($arAttr[$key_a]);
                        }
                    }
                    foreach ($arAttr as &$at) {
                        unset($at['picture']);
                        unset($at['info']);
                        $at['value'] = str_replace('"', '', $at['value']);
                        $arAttrSelect[$at['id']] = $at['value'];
                    }

                    // основной иблок
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

                    $iblock_props_select = GetMessage('MAXYSS_OZON_IBLOCK_BASE') . '<br><br><div class="answer_prop_' . $iblock_id . '"><select onchange="get_prop_values($(this),' . $iblock_id . ',' . CUtil::PhpToJSObject($arAttr) . ','.$attr_id.');" name="prop_bx[' . $iblock_id . ']"><option value=""></option>';



                    $filterProp = array();

                    if ($arProps['is_collection']['VALUE'] != 1) $filterProp['MULTIPLE'] = 'N';

                    $res_br = CIBlock::GetProperties($iblock_id, Array('name' => 'asc'), $filterProp);
                    while ($res_arr_br = $res_br->Fetch()) {
                        $selected = '';
                        if ($arSinc[$iblock_id]['prop_code'] == $res_arr_br['CODE'])
                            $selected = 'selected = "selected"';

                        if (!empty($arSinc[$iblock_id]['sinc']) && $selected != '') {
                            // рисуем  синхронизированные значения
                            $custom_values_table = '';

                            $res_prop = CIBlockProperty::GetByID($res_arr_br['ID'], $iblock_id);
                            if ($ar_prop = $res_prop->GetNext()) {
//                                echo '<pre>', print_r($ar_prop), '</pre>' ;
                                if (isset($ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']) && $ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']) {
                                    $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getRow([
                                        'filter' => [
                                            '=TABLE_NAME' => $ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']
                                        ],
                                    ]);

                                    $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
                                    $main_query = new Bitrix\Main\Entity\Query($entity);
                                    $main_query->setSelect(array('*'));
                                    $result = $main_query->exec();
                                    $result = new CDBResult($result);
                                    $count_enum = 0;
                                    while ($row = $result->Fetch()) {
                                        $count_enum++;
                                        $prop_sinc_values_select_array[] = array(
                                            'UF_XML_ID' => $row["UF_XML_ID"],
                                            'ID' => ["ID"],
                                            'UF_NAME' => $row['UF_NAME']
                                        );
                                    }

                                } else {
                                    if($ar_prop['PROPERTY_TYPE'] == 'E'){
                                        $count_enum = 0;
                                        $res_el_e = CIBlockElement::GetList(Array('NAME'=>'asc'), array('IBLOCK_ID'=>$ar_prop['LINK_IBLOCK_ID']), false, false, array('ID','IBLOCK_ID','NAME',));
                                        while($ob = $res_el_e->GetNextElement()) {
                                            $count_enum++;
                                            $arFields = $ob->GetFields();
                                            $prop_sinc_values_select_array[] = array("ID" => $arFields["ID"], "VALUE" => $arFields['NAME']);
                                        }

                                    }else {
                                        $prop_sinc_values = CIBlockPropertyEnum::GetList(Array("DEF" => "DESC", "SORT" => "ASC"), Array("IBLOCK_ID" => $iblock_id, "PROPERTY_ID" => $ar_prop['ID']));
                                        $prop_sinc_values_select = '';
                                        $count_enum = 0;
                                        while ($enum_fields = $prop_sinc_values->GetNext()) {
                                            $count_enum++;
//                                    $prop_sinc_values_select .= '<option value="' . $enum_fields["ID"] . '">' . '[' . $enum_fields["ID"] . '] ' . $enum_fields["VALUE"] . '</option>';
                                            $prop_sinc_values_select_array[] = array(
                                                'ID' => $enum_fields["ID"],
                                                'VALUE' => $enum_fields["VALUE"],
                                            );
                                        }
                                    }
                                }
                            }
                            $index = 0;

                            foreach ($prop_sinc_values_select_array as $prop) {
                                if($attr_id !=31 && $attr_id != 85) {
                                    if (isset($prop['UF_XML_ID']))
                                        $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["UF_XML_ID"] . '" name="prop_value[' . $iblock_id . '][' . $index . ']">' . $prop['UF_NAME'] . '</td><td><select name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']"><option value=""></option>';
                                    else
                                        $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["ID"] . '" name="prop_value[' . $iblock_id . '][' . $index . ']">' . $prop['VALUE'] . '</td><td><select name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']"><option value=""></option>';


                                    foreach ($arAttrSelect as $at_id => $attr_value_id) {
                                        $selected_bx_prop = '';
                                        if ($at_id == $arSinc[$iblock_id]['sinc'][$prop["ID"]] || $at_id == $arSinc[$iblock_id]['sinc'][$prop["UF_XML_ID"]]) $selected_bx_prop = 'selected = "selected"';

                                        $custom_values_table .= '<option ' . $selected_bx_prop . ' value="' . $at_id . '">' . $attr_value_id . '</option>';
                                    }
                                    $custom_values_table .= '</select></td></tr>';
                                }
                                else
                                {
                                    if(!empty($arAttrSelect)) {
                                        if (isset($prop['UF_XML_ID'])) {
                                            $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["UF_XML_ID"] . '" name="prop_value[' . $iblock_id . '][' . $index . ']">' . $prop['UF_NAME'] . '</td><td><input type="text" name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']" value="' . $arAttrSelect[$arSinc[$iblock_id]['sinc'][$prop["UF_XML_ID"]]] . '"  class="autocomplete_prop_popup"><input type="hidden" id="brend_' . $attr_id . '" class="adm-input" name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']" value="' . $arSinc[$iblock_id]['sinc'][$prop["UF_XML_ID"]] . '" >';
                                        }
                                        else
                                        {
                                            $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["ID"] . '" name="prop_value[' . $iblock_id . '][' . $index . ']">' . $prop['VALUE'] . '</td><td><input type="text" name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']" value="' . $arAttrSelect[$arSinc[$iblock_id]['sinc'][$prop["ID"]]] . '"  class="autocomplete_prop_popup"><input type="hidden" id="brend_' . $attr_id . '" class="adm-input" name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']" value="' . $arSinc[$iblock_id]['sinc'][$prop["ID"]] . '" >';
                                        }
                                    }else{
                                        $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["ID"] . '" name="prop_value[' . $iblock_id . '][' . $index . ']">' . $prop['VALUE'] . '</td><td><input type="text" name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']" value="" id="inp_'.$iblock_id.$index.'" class="autocomplete_prop_popup"><input type="hidden" id="brend_' . $attr_id . '" class="adm-input" name="attr_value[' . intval($_REQUEST['iblock_id']) . '][' . $index . ']" value="" >';
                                    }
                                    $custom_values_table .= '</td></tr>';

                                }
                                $index++;
                            }

                        }

                        $iblock_props_select .= '<option ' . $selected . ' value="' . $res_arr_br['CODE'] . '">' . '[' . $res_arr_br['ID'] . '] ' . $res_arr_br['NAME'] . '</option>';
                    }

                    if ($index > 0)
                        $iblock_props_select .= '</select></div><div class="answer_prop_values_' . $iblock_id . '"><br><table id="table_prop_values_' . $iblock_id . '">' . $custom_values_table . '</table></div>';
                    else
                        $iblock_props_select .= '</select><div class="answer_prop_values_' . $iblock_id . '"><br><table id="table_prop_values_' . $iblock_id . '"></table></div>';


                    $custom_values_table = '';
                    $index = 0;
                    $prop_sinc_values_select_array = array();

                    $iblock_offers_props_select = '';
                    if ($iblock_offers_id > 0) {
                        $iblock_offers_props_select = '<br><br>' . GetMessage('MAXYSS_OZON_IBLOCK_OFFERS') . '<br><br><div class="answer_prop_' . $iblock_offers_id . '"><select onchange="get_prop_values($(this),' . $iblock_offers_id . ',' . CUtil::PhpToJSObject($arAttr) . ','.$attr_id.');" name="prop_bx[' . $iblock_offers_id . ']"><option value=""></option>';

                        $res_br = CIBlock::GetProperties($iblock_offers_id, Array('name' => 'asc'), $filterProp);
                        while ($res_arr_br = $res_br->Fetch()) {

                            $selected = '';
                            if ($arSinc[$iblock_offers_id]['prop_code'] == $res_arr_br['CODE'])
                                $selected = 'selected = "selected"';

                            $iblock_offers_props_select .= '<option ' . $selected . ' value="' . $res_arr_br['CODE'] . '">' . '[' . $res_arr_br['ID'] . '] ' . $res_arr_br['NAME'] . '</option>';


                            if (!empty($arSinc[$iblock_offers_id]['sinc']) && $selected != '') {

                                // рисуем  синхронизированные значения
                                $custom_values_table = '';

                                $res_prop = CIBlockProperty::GetByID($res_arr_br['ID'], $iblock_offers_id);
                                if ($ar_prop = $res_prop->GetNext()) {
                                    if ($ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']) {
                                        $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getRow([
                                            'filter' => [
                                                '=TABLE_NAME' => $ar_prop['USER_TYPE_SETTINGS']['TABLE_NAME']
                                            ],
                                        ]);

                                        $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
                                        $main_query = new Bitrix\Main\Entity\Query($entity);
                                        $main_query->setSelect(array('*'));
                                        //                $main_query->setFilter(array('UF_XML_ID' => $arProps[$arSettings['BRAND_PROP']]['VALUE']));
                                        $result = $main_query->exec();
                                        $result = new CDBResult($result);
                                        $count_enum = 0;
                                        while ($row = $result->Fetch()) {
                                            $count_enum++;
                                            //                                        $prop_sinc_values_select .= '<option value="' . $row["UF_XML_ID"] . '">' . '[' . $row["ID"] . '] ' . $row['UF_NAME'] . '</option>';
                                            $prop_sinc_values_select_array[] = array(
                                                'UF_XML_ID' => $row["UF_XML_ID"],
                                                'ID' => $row["ID"],
                                                'UF_NAME' => $row['UF_NAME']
                                            );
                                        }
                                        //                                    if ($count_enum > 0) {
                                        //                                        $custom_values = '<select name="bx_value[' . intval($_REQUEST['iblock_id']) . '][]"><option value=""></option>' . $prop_sinc_values_select . '</select>';
                                        //                                    }

                                    } else {
                                        $prop_sinc_values = CIBlockPropertyEnum::GetList(Array("DEF" => "DESC", "SORT" => "ASC"), Array("IBLOCK_ID" => $iblock_offers_id, "PROPERTY_ID" => $ar_prop['ID']));
                                        $prop_sinc_values_select = '';
                                        $count_enum = 0;
                                        while ($enum_fields = $prop_sinc_values->GetNext()) {
                                            $count_enum++;
                                            //                                    $prop_sinc_values_select .= '<option value="' . $enum_fields["ID"] . '">' . '[' . $enum_fields["ID"] . '] ' . $enum_fields["VALUE"] . '</option>';
                                            $prop_sinc_values_select_array[] = array(
                                                'ID' => $enum_fields["ID"],
                                                'VALUE' => $enum_fields["VALUE"],
                                            );
                                        }
                                    }
                                }
                                $index = 0;
                                foreach ($prop_sinc_values_select_array as $prop) {
                                    if($attr_id !=31 && $attr_id != 85) {
                                        if (isset($prop['UF_XML_ID']))
                                            $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["UF_XML_ID"] . '" name="prop_value[' . $iblock_offers_id . '][' . $index . ']">' . $prop['UF_NAME'] . '</td><td><select name="attr_value[' . $iblock_offers_id . '][' . $index . ']"><option value=""></option>';
                                        else
                                            $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["ID"] . '" name="prop_value[' . $iblock_offers_id . '][' . $index . ']">' . $prop['VALUE'] . '</td><td><select name="attr_value[' . $iblock_offers_id . '][' . $index . ']"><option value=""></option>';

                                        foreach ($arAttrSelect as $at_id => $attr_value_id) {
                                            $selected_bx_prop = '';
                                            if ($at_id == $arSinc[$iblock_offers_id]['sinc'][$prop["ID"]] || $at_id == $arSinc[$iblock_offers_id]['sinc'][$prop["UF_XML_ID"]]) $selected_bx_prop = 'selected = "selected"';

                                            $custom_values_table .= '<option ' . $selected_bx_prop . ' value="' . $at_id . '">' . $attr_value_id . '</option>';
                                        }
                                        $custom_values_table .= '</select></td></tr>';
                                    }else{
                                        if(!empty($arAttrSelect)) {
                                            if (isset($prop['UF_XML_ID'])) {
                                                $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["UF_XML_ID"] . '" name="prop_value[' . $iblock_offers_id . '][' . $index . ']">' . $prop['UF_NAME'] . '</td><td><input type="text" name="attr_value[' . $iblock_offers_id . '][' . $index . ']" value="' . $arAttrSelect[$arSinc[$iblock_offers_id]['sinc'][$prop["UF_XML_ID"]]] . '"  class="autocomplete_prop_popup"><input type="hidden" id="brend_' . $attr_id . '" class="adm-input" name="attr_value[' . $iblock_offers_id . '][' . $index . ']" value="' . $arSinc[$iblock_offers_id]['sinc'][$prop["UF_XML_ID"]] . '" >';
                                            }
                                            else
                                            {
                                                $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["ID"] . '" name="prop_value[' . $iblock_offers_id . '][' . $index . ']">' . $prop['VALUE'] . '</td><td><input type="text" name="attr_value[' . $iblock_offers_id . '][' . $index . ']" value="' . $arAttrSelect[$arSinc[$iblock_offers_id]['sinc'][$prop["ID"]]] . '"  class="autocomplete_prop_popup"><input type="hidden" id="brend_' . $attr_id . '" class="adm-input" name="attr_value[' . $iblock_offers_id . '][' . $index . ']" value="' . $arSinc[$iblock_offers_id]['sinc'][$prop["ID"]] . '" >';

                                            }
                                        }else{
                                            $custom_values_table .= '<tr><td><input type="hidden" value="' . $prop["ID"] . '" name="prop_value[' . $iblock_offers_id . '][' . $index . ']">' . $prop['VALUE'] . '</td><td><input type="text" name="attr_value[' . $iblock_offers_id . '][' . $index . ']" value="" class="autocomplete_prop_popup"><input type="hidden" id="brend_' . $attr_id . '" class="adm-input" name="attr_value[' . $iblock_offers_id . '][' . $index . ']" value="" >';
                                        }
                                        $custom_values_table .= '</td></tr>';
                                    }
                                    $index++;
                                }
                            }
                        }


                        if ($index > 0)
                            $iblock_offers_props_select .= '</select></div><div class="answer_prop_values_' . $iblock_offers_id . '"><br><table id="table_prop_values_' . $iblock_offers_id . '">' . $custom_values_table . '</table></div>';
                        else
                            $iblock_offers_props_select .= '</select></div><div class="answer_prop_values_' . $iblock_offers_id . '"><br><table id="table_prop_values_' . $iblock_offers_id . '"></table></div>';
                    }

                }
            }

        $script_autocomplit = '';
            if($attr_id == 31 || $attr_id == 85) $script_autocomplit = '<script>$(".autocomplete_prop_popup").autocomplete({ source: "/bitrix/tools/maxyss.ozon/ozon_ajax.php?id='.$attr_id.'", minLength: 2, delay: 1000, search: function( event, ui ) { var ui_result = BX.showWait("form_prop_values");}, response: function( event, ui ) {  if(ui.content.length == 0){ui.content.push({"label":BX.message("MAXYSS_OZON_NOT_FOUND_BREND")})}; BX.closeWait("form_prop_values"); },  select: function (event, ui) { $(this).next().val(ui.item.id);    }});</script>';
        echo $iblock_props_select.$iblock_offers_props_select.'<input type="hidden" name="attr_id" value="'.$attr_id.'">'.$script_autocomplit;
    }
}

if($_REQUEST['action'] == 'get_info_ozon_offer'){
    $res = '';
    $arLog = array();
    $arFieldOzon = array("NAME", "barcode",/* "barcodes",*/ "buybox_price",/* "created_at",*/ "fbo_sku", "fbs_sku", "stocks", "has_discounted_item", "is_discounted", "discounted_stocks", "is_kgt", "marketing_price", "min_ozon_price", "min_price", "offer_id", "old_price", "price", "status",  "visible", "visibility_details");
    if(CModule::IncludeModule('maxyss.ozon')) {
        $ids = array();
        if(isset($_REQUEST['elements']) && is_array($_REQUEST['elements'])) {
            foreach ($_REQUEST['elements'] as $el) {
                $ids[] = intval(str_replace('E', '', $el));
            }
        }elseif(isset($_REQUEST['id'])){
            $ids[] = intval($_REQUEST['id']);
        }
        if (!empty($ids) && CModule::IncludeModule('iblock')) {

            $IBLOCK_ID_PRODUCT = 0;
            $IBLOCK_ID = intval($_REQUEST['iblock_id']);

            $mxResult = CCatalogSKU::GetInfoByOfferIBlock(
                $iblock_id
            );
            if (is_array($mxResult)) {  // это ТП
                $IBLOCK_ID_PRODUCT = $mxResult["PRODUCT_IBLOCK_ID"];
            }
            $arOptions = CMaxyssOzon::getOptions();

            if (!empty($arOptions)) {
                $arOptionsUnique = array();
                foreach ($arOptions as $key => $lid) {
                    if(($lid['OZON_API_KEY'] == '' || $lid['OZON_ID'] == '') || array_search($lid["IBLOCK_ID"].$lid['ARTICLE'].serialize($ids).$lid['OZON_ID'], $arOptionsUnique) !== false) continue;

                    $warehouses = CRestQuery::rest_query($lid['OZON_ID'], $lid['OZON_API_KEY'], $base_url = OZON_BASE_URL, "{}", "/v1/warehouse/list");
                    if (!$warehouses['error'] && !empty($warehouses)) {
                        foreach ($warehouses as $wh_ozon) {
                            $arWarehuose[$wh_ozon['warehouse_id']] = $wh_ozon['name'];
                        }
                    }
                    $arOptionsUnique[] = $lid["IBLOCK_ID"].$lid['ARTICLE'].serialize($ids).$lid['OZON_ID'];

                    $offer_id = array();
                    if ($lid["IBLOCK_ID"] == $IBLOCK_ID || $lid["IBLOCK_ID"] == $IBLOCK_ID_PRODUCT){
                        $filter = array('ID'=>$ids, "IBLOCK_ID" => $IBLOCK_ID);
                        if($lid['ARTICLE'] !='')
                            $select = array('ID', 'IBLOCK_ID', 'PROPERTY_'.$lid['ARTICLE']);
                        else
                            $select = array('ID', 'IBLOCK_ID');
                        $res = CIBlockElement::GetList(Array("ID" => "ASC"), $filter, false, Array("nTopCount"=>1000), $select);
                        while ($ob = $res->GetNextElement()) {
                            $arFields = $ob->GetFields();
                            if ($arFields['PROPERTY_'.$lid['ARTICLE'].'_VALUE']) {
                                $offer_id[] = $arFields['PROPERTY_' . $lid['ARTICLE'] . '_VALUE'];
                            }
                            else{
                                $offer_id[] = $arFields['ID'];
                            }
                        }
                    }
                    if(!empty($offer_id)) {
                        if($lid['LOG_UPLOAD_ON'] == 'Y'){
                            foreach ($offer_id as $offer) {
                                $connection = \Bitrix\Main\Application::getConnection();
                                if ($connection->isTableExists(Maxyss\Ozon\CMaxyssOzonLogTable::getTableName())) {
                                    $obData = new Maxyss\Ozon\CMaxyssOzonLogTable;
                                        $rsData = $obData->getList(['filter' => array('=OFFER_ID' => $offer_id)]);
                                        while ($arData = $rsData->Fetch()) {
                                            if($arData['OZON_ID'] == $lid['OZON_ID'])
                                                $arLog[$lid['OZON_ID']][$arData['OFFER_ID']][] = $arData;
                                        }
                                }
                            }
                        }
                        $res_info = CMaxyssOrderList::GetProductsInfo($offer_id, $lid['OZON_ID'], $lid['OZON_API_KEY']);
                        if(!empty($res_info['items'])) {
                            ?>
                            <style> .bx-core-adm-dialog .adm-list-table  td{ background-color: #ffffff;  }</style>
                            <?
                            foreach ($res_info['items'] as $item) {
                                echo '<table class="adm-list-table"><tbody><thead><tr class="adm-list-table-header"><td style="padding-left: 40px" class="adm-list-table-cell"  colspan="2">'.GetMessage("MAXYSS_OZON_ID_CABINET_OZON").' - '.$lid['OZON_ID'].'</td></tr></thead>';
                                if(!empty($arLog)){
                                    foreach ($arLog[$lid['OZON_ID']][$item['offer_id']] as $upload){
                                    $err_stock = ''; $err_price = '';
                                    if($upload['TYPE_UPLOAD'] == 'stock'){
                                        if($error_item = unserialize($upload['ERROR']))
                                        {
                                            foreach ($error_item as $e) {
                                                $err_stock .= $e['message'].'<br>';
                                            }
                                            $err_stock = '<font style="color: red">'.GetMessage('MAXYSS_OZON_INFO_UPLOAD_ERROR').$err_stock.'</font>';
                                        }
                                        echo '<tr class="adm-list-table-row"><td class="adm-list-table-cell">' . GetMessage('MAXYSS_OZON_INFO_STOCK_UPLOAD_WH'). '<b>' . $arWarehuose[$upload['WAREHOUSE_ID']]. '</b>' .GetMessage('MAXYSS_OZON_INFO_STOCK_UPLOAD') . '<b>' .$upload["STOCK"]  . GetMessage('MAXYSS_OZON_SHT') . '</b> ' .GetMessage('MAXYSS_OZON_INFO_UPLOAD_TIME') .$upload['DATE_UPLOAD']->toString().'</td><td class="adm-list-table-cell">'.$err_stock.'</td></tr>';
                                    }
                                    elseif($upload['TYPE_UPLOAD'] == 'price'){
                                        if($error_item = unserialize($upload['ERROR']))
                                        {
                                            foreach ($error_item as $e) {
                                                $err_price .= $e['message'].'<br>';
                                            }
                                            $err_price = '<font style="color: red">'.GetMessage('MAXYSS_OZON_INFO_UPLOAD_ERROR').$err_price.'</font>';
                                        }
                                        echo '<tr class="adm-list-table-row"><td class="adm-list-table-cell">'. GetMessage('MAXYSS_OZON_INFO_STOCK_UPLOAD_PRICE')  . strtolower(GetMessage('MAXYSS_OZON_PRICE')).  '<b>' .$upload["PRICE"]  .  '</b> '. GetMessage('MAXYSS_OZON_INFO_UPLOAD_TIME') .$upload['DATE_UPLOAD']->toString(). '</td><td class="adm-list-table-cell">'.$err_price.'</td></tr>';
                                    }
                                }
                                }
                                foreach ($arFieldOzon as $key_f => $f) {
                                    if (is_array($item[strtolower($f)])) {
                                        echo '<tr class="adm-list-table-row"><td class="adm-list-table-cell">' . GetMessage($f) . '</td>';
                                        switch ($f) {
                                            case 'barcodes':

                                                break;
                                            case 'status':
                                                echo '<td class="adm-list-table-cell">' . GetMessage('is_created') . ' - ';
                                                echo ($item[$f]["is_created"]) ? GetMessage('da') . '<br>' : GetMessage("net") . '<br>';
                                                echo $item[$f]["state_name"] . '  <br>' . $item[$f]["state_description"].'<br>';
                                                if(is_array($item[$f]['decline_reasons']) && !empty($item[$f]['decline_reasons'])) {
                                                    echo '<font style="color: red">'.GetMessage('MAXYSS_OZON_INFO_PRODUCT_ERROR').'</font>';
                                                    foreach ($item[$f]['decline_reasons'] as $decline_reason_error) {
                                                        echo $decline_reason_error.'<br>';
                                                    }
                                                }
                                                echo '</td>';

                                                break;
                                            case 'discounted_stocks':
                                            case 'stocks':
                                                echo '<td  class="adm-list-table-cell">' . GetMessage('coming') . ' - ' . $item[$f]["coming"] . ' <br> ' . GetMessage('present') . ' - ' . $item[$f]["present"] . ' <br> ' . GetMessage('reserved') . ' - ' . $item[$f]["reserved"] . ' <br> ' . '</td>';
                                                break;
                                            case 'visibility_details':
                                                echo '<td  class="adm-list-table-cell">';
//                                                echo GetMessage('active_product') . ' - ';
//                                                echo ($item[$f]["active_product"]) ? GetMessage('da') . '<br>' : GetMessage("net") . '<br>';
//                                                echo GetMessage('has_price') . ' - ';
//                                                echo ($item[$f]["has_price"]) ? GetMessage('da') . '<br>' : GetMessage("net") . '<br>';
                                                echo GetMessage('has_stock') . ' - ';
                                                echo ($item[$f]["has_stock"]) ? GetMessage('da') . '<br>' : GetMessage("net") . '<br>';
                                                $arReasons = array();
                                                if (isset($item[$f]['reasons']) && is_array($item[$f]['reasons'])) {
                                                    foreach ($item[$f]['reasons'] as $r) {
                                                        foreach ($r['reasons'] as $d) {
                                                            $arReasons[] = $d['description'];
                                                        }
                                                    }
                                                }
                                                if (!empty($arReasons)) {
                                                    echo GetMessage('reasons') . ' - ';
                                                    $arReasons = array_unique($arReasons);
                                                    foreach ($arReasons as $description) {
                                                        echo $description . '<br>';
                                                    }
                                                }
                                                echo '</td>';
                                                break;
                                        }
                                        echo '</tr>';
                                    } else {

                                        echo '<tr class="adm-list-table-row"><td  class="adm-list-table-cell">' . GetMessage($f) . '</td><td  class="adm-list-table-cell">';
                                        if ($item[strtolower($f)] === true) echo GetMessage('da');
                                        elseif ($item[strtolower($f)] === false) echo GetMessage('net');
                                        else echo $item[strtolower($f)];
                                        echo '</td>';
                                    }
                                }

                                echo '</tbody></table>';
                            }
                        }else
                            echo GetMessage('MAXYSS_OZON_INFO_NOT_FOUND');
                    }
                }
            }



        }
    }
}

if($_REQUEST['action'] == 'upload_ozon'){
    $res = '';
    if(CModule::IncludeModule('maxyss.ozon')) {
        $ids = array();
        foreach ($_REQUEST['elements'] as $el){
            $ids[] = str_replace('E','',$el);
        }
        if (!empty($ids)) {
            $IBLOCK_ID = $_REQUEST['iblock_id'];
            $arOptions = CMaxyssOzon::getOptions();
            if (!empty($arOptions)) {
                foreach ($arOptions as $key => $lid) {
                    if ($lid["IBLOCK_ID"] == $IBLOCK_ID){
                        $filter = array('ID'=>$ids);
                        $res = CMaxyssOzonAgent::OzonUploadProduct($key, 1, $filter);
                    }
                }
            }
        }
    }
    if($res)
        echo \Bitrix\Main\Web\Json::encode($res);
    else
        echo \Bitrix\Main\Web\Json::encode(array('success'=>0));
}

if($_REQUEST['action'] == 'arch_to' || $_REQUEST['action'] == 'arch_from'){
    $res = array('error'=>'', 'success'=>'');
    if(CModule::IncludeModule('maxyss.ozon')) {
        $ids = array();
        $flag_tp = false;
        foreach ($_REQUEST['elements'] as $el){
            $ids[] = str_replace('E','',$el);
        }
        if (!empty($ids)) {
            $IBLOCK_ID = intval($_REQUEST['iblock_id']);

            if($_REQUEST['iblock_id_product'] > 0){
                $flag_tp = true;
                $IBLOCK_ID_PRODUCT = intval($_REQUEST['iblock_id_product']);
            }



            $arOptions = CMaxyssOzon::getOptions();
            if (!empty($arOptions)) {
                foreach ($arOptions as $key => $lid) {
                    $sku = array();
                    $offer_id = array();
                    if($flag_tp){
                        if ($lid["IBLOCK_ID"] == $IBLOCK_ID_PRODUCT) {
                            $ClientId = $lid["OZON_ID"];
                            $ApiKey = $lid['OZON_API_KEY'];

                            $prop_flag = '';
                            if (strlen($lid["ARTICLE"]) > 0) $prop_flag = 'PROPERTY_';

                            // запросим товары из битрикса
                            $arFilterProd = array("ID" => $ids);
                            $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "IBLOCK_ID", 'CATALOG_XML_ID');
                            if ($prop_flag != '')
                                $arSelect[] = $prop_flag . $lid["ARTICLE"];

                            $res_obj = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);
                            while ($ob = $res_obj->GetNextElement()) {
                                $arFields = $ob->GetFields();

                                if ($prop_flag != '')
                                    $offer_id[] = $arFields['PROPERTY_' . $lid["ARTICLE"] . '_VALUE'];
                                else
                                    $offer_id[] = $arFields['ID'];

                            }


                            if (!empty($offer_id)) {
                                $sku_ozon = CMaxyssOrderList::GetProductsInfo($offer_id, $ClientId, $ApiKey);
                                if (isset($sku_ozon['items']) && !empty($sku_ozon['items'])) {
                                    foreach ($sku_ozon['items'] as $item) {
                                        $sku[] = intval($item['id']);
                                    }
                                }
                                elseif( isset($sku_ozon['items']) && empty($sku_ozon['items']) ) { // ответ не корректный с Озона
                                    $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_ARCH_TO_NO_FOUND_OFFER') . '<br>';
                                }
                                else { // ответ не корректный с Озона
                                    $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . $sku_ozon['error'] . '<br>';
                                }
                            } else {
                                // не один товар не подходит
                                $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_ARCH_TO_NO_FOUND_OFFER') . '<br>';
                            }
//echo '<pre>', print_r($sku), '</pre>' ;
                            if (!empty($sku)) {
                                if($_REQUEST['action'] == 'arch_to' )
                                    $res_arch = CMaxyssMoreOzonFunction::OzonArchToProducts($sku, $ClientId, $ApiKey);
                                else
                                    $res_arch = CMaxyssMoreOzonFunction::OzonArchFromProducts($sku, $ClientId, $ApiKey);

//                echo '<pre>', print_r($res_arch), '</pre>' ;
                                if ($res_arch) {
                                    $res['success'] = GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_'.strtoupper($_REQUEST['action']).'_SUCCESS') . '<br>';
                                }
                                else{
                                    $res['success'] = GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_'.strtoupper($_REQUEST['action']).'_ERROR') . '<br>';
                                }
                            } else {
                                // нет таких sku на Озоне или нет ответа с Озона
//                                $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_ARCH_TO_NO_FOUND_OFFER') . '<br>';
                                $res['success'] = GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_'.strtoupper($_REQUEST['action']).'_ERROR') . '<br>';

                            }
                        }
                    }
                    else
                    {
                        if ($lid["IBLOCK_ID"] == $IBLOCK_ID) {
                            $ClientId = $lid["OZON_ID"];
                            $ApiKey = $lid['OZON_API_KEY'];

                            $prop_flag = '';
                            if (strlen($lid["ARTICLE"]) > 0) $prop_flag = 'PROPERTY_';

                            // запросим товары из битрикса
                            $arFilterProd = array("ID" => $ids);
                            $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "IBLOCK_ID", 'CATALOG_XML_ID');
                            if ($prop_flag != '')
                                $arSelect[] = $prop_flag . $lid["ARTICLE"];

                            $res_obj = CIBlockElement::GetList(Array("ID" => "ASC"), $arFilterProd, false, false, $arSelect);
                            while ($ob = $res_obj->GetNextElement()) {
                                $arFields = $ob->GetFields();

                                $ar_tovar = CCatalogProduct::GetByID($arFields['ID']); // item as product

                                if ($ar_tovar['TYPE'] == 3) {
                                    $arInfo = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);

                                    if (is_array($arInfo)) {
                                        $rsOffers = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arInfo['IBLOCK_ID'], 'PROPERTY_' . $arInfo['SKU_PROPERTY_ID'] => $arFields['ID']), false, false, $arSelect);

                                        while ($arOffer = $rsOffers->GetNextElement()) {
                                            $arFieldsOff = $arOffer->GetFields();
                                            if ($prop_flag != '')
                                                $offer_id[] = $arFieldsOff['PROPERTY_' . $lid["ARTICLE"] . '_VALUE'];
                                            else
                                                $offer_id[] = $arFieldsOff['ID'];
                                        }
                                    }
                                } elseif ($ar_tovar['TYPE'] == 1) {
                                    if ($prop_flag != '')
                                        $offer_id[] = $arFields['PROPERTY_' . $lid["ARTICLE"] . '_VALUE'];
                                    else
                                        $offer_id[] = $arFields['ID'];
                                }
                            }


                            if (!empty($offer_id)) {
                                $sku_ozon = CMaxyssOrderList::GetProductsInfo($offer_id, $ClientId, $ApiKey);
                                if (isset($sku_ozon['items']) && !empty($sku_ozon['items'])) {
                                    foreach ($sku_ozon['items'] as $item) {
                                        $sku[] = intval($item['id']);
                                    }
                                }
                                elseif( isset($sku_ozon['items']) && empty($sku_ozon['items']) ) { // ответ не корректный с Озона
                                    $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_ARCH_TO_NO_FOUND_OFFER') . '<br>';
                                }
                                else { // ответ не корректный с Озона
                                    $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . $sku_ozon['error'] . '<br>';
                                }
                            } else {
                                // не один товар не подходит
                                $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_ARCH_TO_NO_FOUND_OFFER') . '<br>';
                            }
//echo '<pre>', print_r($sku), '</pre>' ;
                            if (!empty($sku)) {
                                if($_REQUEST['action'] == 'arch_to' )
                                    $res_arch = CMaxyssMoreOzonFunction::OzonArchToProducts($sku, $ClientId, $ApiKey);
                                else
                                    $res_arch = CMaxyssMoreOzonFunction::OzonArchFromProducts($sku, $ClientId, $ApiKey);

                                if ($res_arch) {
                                    $res['success'] = GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_'.strtoupper($_REQUEST['action']).'_SUCCESS') . '<br>';
                                }
                                else
                                {
                                    $res['success'] = GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_'.strtoupper($_REQUEST['action']).'_ERROR') . '<br>';
                                }
                            } else {
                                // нет таких sku на Озоне или нет ответа с Озона
//                                $res['error'] .= GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_ARCH_TO_NO_FOUND_OFFER') . '<br>';
                                $res['success'] = GetMessage('MAXYSS_OZON_ID_CABINET_OZON') . $lid["OZON_ID"] . ' - ' . GetMessage('MAXYSS_OZON_'.strtoupper($_REQUEST['action']).'_ERROR') . '<br>';
                            }
                        }
                    }
                }
            }
            else
            {
                $res['error'] .='NO OPTIONS MODULE'; // нет настроек модуля
            }
        }
        else{
            $res['error'] .= GetMessage('MAXYSS_OZON_NO_ELEMENTS_SELECTED'); // ничего не выбрано
        }
    }
        echo \Bitrix\Main\Web\Json::encode($res);
}

if($_REQUEST['action'] == 'get_mill_id'){
    if($_REQUEST['site'] != '' && $_REQUEST['prop'] != '' && $right_module_maxyss_ozon){
        $prop_code = $_REQUEST['prop'];
        $lid = htmlspecialcharsbx($_REQUEST['site']);
        $res = CMaxyssGetOzonInfo::downloadIdOzon($lid,100, htmlspecialcharsbx($_REQUEST['last_id']));
        if(is_array($res['items'])){
            $arOptions = CMaxyssOzon::getOptions($lid);
            foreach ($res['items'] as $item){
                $article = $item['offer_id'];

                if ($arOptions[$lid]["ARTICLE"] != '') {
                    $arFilter = Array("PROPERTY_" . $arOptions[$lid]["ARTICLE"] => $article);
                    $resElement = CIBlockElement::GetList(Array(), $arFilter, false, false,  array("ID", "IBLOCK_ID"));
                }else{
                    $arFilter = Array("ID" => $article);
                    $resElement = CIBlockElement::GetList(Array(), $arFilter, false, false, array("ID", "IBLOCK_ID"));
                }

                if ($ob = $resElement->GetNextElement()) {
                    $arFields = $ob->GetFields();
                    CIBlockElement::SetPropertyValuesEx($arFields['ID'], false, array(
                        $prop_code => $item['sku'],
                    ));
                }
            }

        }
        unset($res['items']);
        echo \Bitrix\Main\Web\Json::encode($res);
    }
    else
        echo \Bitrix\Main\Web\Json::encode(array('go_run'=>false, 'mess'=>'NOT RIGHT!'));
}
?>
