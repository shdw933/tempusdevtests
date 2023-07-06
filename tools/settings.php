<? require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

//echo '<pre>', print_r($_REQUEST),'</pre>';
CModule::IncludeModule('iblock');

if($_REQUEST['action'] == 'get_iblock_id')
{
    $arIBlock = array();
    $iblockFilter = (
    !empty($_REQUEST['iblock_type'])
        ? array('TYPE' => $_REQUEST['iblock_type'], 'ACTIVE' => 'Y')
        : array('ACTIVE' => 'Y')
    );
    $rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
    echo '<option value=""></option>';
    while ($arr = $rsIBlock->Fetch())
    {
        $id = (int)$arr['ID'];
        if (isset($offersIblock[$id]))
            continue;
        $arIBlock[$id] = '['.$id.'] '.$arr['NAME'];
        echo '<option value="'.$id.'">'.'['.$id.'] '.$arr['NAME'].'</option>';
    }
}
if($_REQUEST['action'] == 'get_prop_foto')
{
    if(!empty($_REQUEST['iblock_id'])){
        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array(), Array("PROPERTY_TYPE" => "F"));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['ID'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
    }

}
if($_REQUEST['action'] == 'get_prop_article')
{
    if(!empty($_REQUEST['iblock_id'])){
        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array('name'=>'asc'), Array("PROPERTY_TYPE" => "S"));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
    }

}
if($_REQUEST['action'] == 'get_prop_brand')
{
    if(!empty($_REQUEST['iblock_id'])){
        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array('name'=>'asc'), array('MULTIPLE'=>'N'));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
    }

}
if($_REQUEST['action'] == 'get_filter_property')
{
    if(!empty($_REQUEST['iblock_id'])){
        echo '<option value=""></option>';
        $res = CIBlock::GetProperties(intval($_REQUEST['iblock_id']), Array('name'=>'asc'), Array("PROPERTY_TYPE" => "L"));
        while ($res_arr = $res->Fetch())
            echo '<option value="'.$res_arr['CODE'].'">'.'['.$res_arr['ID'].'] '.$res_arr['NAME'].'</option>';
    }

}
if($_REQUEST['action'] == 'get_filter_property_enum')
{
    if(!empty($_REQUEST['iblock_id']) && !empty($_REQUEST['filter_property']) && !empty($_REQUEST['cabinet'])){

        if(LANG_CHARSET == 'windows-1251') $request = CMaxyssWb::deepIconv($_REQUEST);
        else $request = $_REQUEST;

        $filter_property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>intval($request['iblock_id']), "CODE"=>$request['filter_property']));
        $filter_property_enums_select = '<select name="filter_property_enums['.htmlspecialcharsbx($request['cabinet']).']">';
        $count_enum=0;

        while($enum_fields = $filter_property_enums->GetNext())
        {
            $count_enum++;
            $filter_property_enums_select .= '<option value="'.$enum_fields["ID"].'">'.'['.$enum_fields["ID"].'] '.$enum_fields["VALUE"].'</option>';
        }
        $filter_property_enums_select .= '</select>';

        if($count_enum !=0)
            echo $filter_property_enums_select;

    }
}
if($_REQUEST['action'] == 'get_props_obj'){
//    echo '123';
    if(!empty($_REQUEST['iblock_id'])){
//        echo $_REQUEST['iblock_id'];
        $selectedIblock = $_REQUEST['iblock_id'];
        $iblockPropsList = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$selectedIblock));
        $iblockProps = array();
        $iblockPropsOb = array();
        $HLBLOCK_ID = false;
        while ($prop_fields = $iblockPropsList->GetNext())
        {
            $iblockProps[$prop_fields['ID']] = $prop_fields;
            if(!empty($prop_fields['USER_TYPE_SETTINGS'])){
                $result = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('=TABLE_NAME'=>$prop_fields['USER_TYPE_SETTINGS']["TABLE_NAME"])));
                if($row = $result->fetch())
                {
                    $HLBLOCK_ID = $row["ID"];
                }
                if($HLBLOCK_ID){
                    $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($HLBLOCK_ID)->fetch();

                    $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);

                    $entityDataClass = $entity->getDataClass();

                    $rsData = $entityDataClass::getList(array(
                        'order' => array('UF_NAME'=>'ASC'),
                        'select' => array('*'),
                        'filter' => array('!UF_NAME'=>false)
                    ));
                    while($el = $rsData->fetch()){
    //                   echo '<pre>', print_r($el),'</pre>';
                        $iblockProps[$prop_fields['ID']]['VALUES'][] = $el;
                    }
                }
            }else{
                if($prop_fields['PROPERTY_TYPE'] == "L"){
                    $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$selectedIblock, "CODE"=>$prop_fields['CODE']));
                    while($enum_fields = $property_enums->GetNext())
                    {
                        $iblockProps[$prop_fields['ID']]['VALUES'][] = $enum_fields;
                    }
                }
            }
        };
        foreach ($iblockProps as $prop){
            if(!empty($prop['VALUES'])){
                $iblockPropsOb[$prop['ID']] = $prop;
            }
        };
        echo CUtil::PHPToJSObject($iblockPropsOb);
    }
};