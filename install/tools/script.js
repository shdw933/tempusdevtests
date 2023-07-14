if(typeof(window.mxWB) === 'undefined') {
    let DialogLk;
    let DialogLkData;
    let DialogLkPhoto;
    let custom_lk = 'DEFAULT';
    document.addEventListener('DOMContentLoaded', function () {
        var object = '';
        get_object_list = function (pattern) {
            if (pattern.length > 2) {
                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.wb/ajax.php',
                    data: {
                        pattern: pattern,
                        action: 'get_object_filter',
                    },
                    success: function (data) {
                        var IS_JSON = true;
                        try {
                            var obj = $.parseJSON(data);
                            errors = obj.errorText;
                        }
                        catch (err) {
                            IS_JSON = false;
                            errors = 'not json oject';
                        }
                        if (IS_JSON && !obj.error) {
                            var list = '';
                            $.each(obj.data, function (index, value) {
                                // console.log(value);
                                list += '<div style="margin: 5px 10px" onclick="get_object_new_api_content(\'' + value.objectName + '\')" data-predmet="' + index + '">' + value.objectName + '</div>';
                            });
                            $('.predmet_dialog').html('<div class="wb_select-item">' + list + '</div>');
                        } else {
                            alert(obj.errorText);
                        }
                    },
                    error: function (xhr, str) {
                        alert('Error: ' + xhr.responseCode);
                    }
                });
            }
        }
    });

    function blur_input() {
        setTimeout(function () {
            $('.wb_select-item').html('');
        }, 500);
    }

    function get_object(v, addin_set) {
        $('#autocomplete_wb').val(v);
        object = v;
        $('.predmet_dialog').html('');
        $.ajax({
            type: 'GET',
            url: 'https://content-suppliers.wildberries.ru/ns/characteristics-configurator-api/content-configurator/api/v1/config/get/object/translated'/*+param*/,
            data: {
                name: v,
                lang: lang,
            },
            success: function (obj) {
                console.log(obj);
                if (!obj.error) {
                    var list_theme = '<table><tbody>';
                    $.each(obj.data.addin, function (index, value) {
                        var star = '', required = '';
                        if (value.required) {
                            star = '*';
                            required = 'required_wb';
                        }
                        var type = 'text';
                        if (value.isNumber) type = 'number';
                        var units = '';
                        if (value.units) units = value.units[0];

                        // var useOnlyDictionaryValues = ''; if(value.useOnlyDictionaryValues) useOnlyDictionaryValues = 'onkeyup="get_options( \''+value.dictionary+'\', $(this).val(), \''+index+'\', \''+value.type+'\' );"';
                        var useOnlyDictionaryValues = '';
                        var value_set = '';
                        // if(value.required) {

                        var var_input = '';
                        if (value.type) {
                            if (value.maxCount > 1) {
                                var display_input = '';
                                var new_counter = 0;

                                for (var counter = 0; counter < value.maxCount; counter++) {
                                    value_set = '';
                                    if (addin_set) {
                                        if ((value.type in addin_set) && addin_set[value.type][counter]) {
                                            value_set = addin_set[value.type][counter];
                                            new_counter = counter;
                                        }
                                    }
                                    if (counter > new_counter + 1 && value_set === '') display_input = 'style="display: none"';
                                    if (value.dictionary) {
                                        useOnlyDictionaryValues = 'onblur="blur_input();" onkeyup="get_options( \'' + value.dictionary + '\', $(this).val(), \'' + index + '_' + counter + '\', \'' + value.type + '\' );" onfocus="get_options( \'' + value.dictionary + '\', $(this).val(), \'' + index + '_' + counter + '\', \'' + value.type + '\' );"';
                                    }

                                    var_input += '<input ' + display_input + useOnlyDictionaryValues + ' class="adm-input wb_attr" data-dictionary="' + value.dictionary + '" data-index-input = "atr_' + index + '_' + counter + '" data-type = "' + value.type + '" data-units = "' + units + '" value="' + value_set + '" type="' + type + '">' + units;
                                }
                            } else {

                                value_set = '';
                                if (addin_set) {
                                    if (value.type in addin_set)
                                        value_set = addin_set[value.type];
                                }

                                if (value.dictionary) {
                                    if (value.dictionary.indexOf('tnved') !== -1) {
                                        useOnlyDictionaryValues = 'onblur="blur_input();" onfocus="get_options( \'' + value.dictionary + '\', \'' + object + '\', \'' + index + '\', \'' + value.type + '\' );"';
                                    }
                                    else {
                                        useOnlyDictionaryValues = 'onblur="blur_input();" onkeyup="get_options( \'' + value.dictionary + '\', $(this).val(), \'' + index + '\', \'' + value.type + '\' );" onfocus="get_options( \'' + value.dictionary + '\', $(this).val(), \'' + index + '\', \'' + value.type + '\' );"';
                                    }
                                }

                                var_input = '<input ' + useOnlyDictionaryValues + ' class="adm-input wb_attr" data-dictionary="' + value.dictionary + '" data-index-input = "atr_' + index + '" data-type = "' + value.type + '" data-units = "' + units + '" value="' + value_set + '" type="' + type + '">' + units;
                            }
                            // list_theme += '<tr class="bx-in-group"><td><label class="' + required + '" title="">' + value.type + star + '</label></td><td><input ' + useOnlyDictionaryValues + ' class="adm-input wb_attr" data-dictionary="' + value.dictionary + '" data-index-input = "atr_' + index + '" data-type = "' + value.type + '" value="' + value_set + '" type="' + type + '">' + units + '</td></tr>'
                            list_theme += '<tr class="bx-in-group"><td><label class="' + required + '" title="">' + value.type + star + '</label></td><td  style="padding: 5px 0;" class="type_wb">' + var_input + '</td></tr>'
                        }
                        // }
                    });
                    list_theme += '</tbody></table>';
                    $('.predmet_dialog').html(list_theme);
                } else {
                    console.log(obj.errorText);
                }
            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });
    }

    function get_object_new_api_content(v, addin_set ) {
        $('#autocomplete_wb').val(v);
        object = v;
// console.log(addin_set);
        $('.predmet_dialog').html('');
        var errors = '';
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_object_new_api_content',
                name: v,
            },
            success: function (data) {

                var IS_JSON = true;
                try {
                    var obj = $.parseJSON(data);
                    errors = obj.errorText;
                }
                catch (err) {
                    IS_JSON = false;
                    errors = 'not json oject';
                }
                if (IS_JSON && !obj.error) {

                    var list_theme = '<table><tbody>';
                    $.each(obj.data, function (index, value) {
                        // console.log(value);
                        var star = '', required = '';
                        if (value.required !== false) {
                            star = '*';
                            required = 'required_wb';
                        }
                        var type = 'text';
                        var step_number = '';
                        if (value.charcType == 4){ type = 'number'; step_number='0.1'}
                        var units = value.unitName;

                        // var useOnlyDictionaryValues = ''; if(value.useOnlyDictionaryValues) useOnlyDictionaryValues = 'onkeyup="get_options( \''+value.dictionary+'\', $(this).val(), \''+index+'\', \''+value.type+'\' );"';
                        var useOnlyDictionaryValues = '';
                        var value_set = '';
                        var var_input = '';

                        var span_set = '';
                        var sinc_class = '';
                        if (value.name ) {
                            if(value.name == BX.message('MAXYSS_WB_ROS_RAZMER') ||
                                value.name == BX.message('MAXYSS_WB_STRANA') ||
                                value.name == BX.message('MAXYSS_WB_NAME_NAME') ||
                                value.name == BX.message('MAXYSS_WB_RAZMER') ||
                                value.name == BX.message('MAXYSS_WB_TSVET') ||
                                value.name == BX.message('MAXYSS_WB_DESCR_ATR') ||
                                value.name == BX.message('MAXYSS_WB_CARD_WEIGHT_UPAC') ||
                                value.name == BX.message('MAXYSS_WB_CARD_WEIGHT_UPAC_KG') ||
                                value.name == BX.message('MAXYSS_WB_CARD_HEIGHT_UPAC') ||
                                value.name == BX.message('MAXYSS_WB_CARD_HEIGHT_UPAC_MM') ||
                                value.name == BX.message('MAXYSS_WB_CARD_WIDTH_UPAC') ||
                                value.name == BX.message('MAXYSS_WB_CARD_WIDTH_UPAC_MM') ||
                                value.name == BX.message('MAXYSS_WB_CARD_LENGTH_UPAC') ||
                                value.name == BX.message('MAXYSS_WB_CARD_LENGTH_UPAC_MM') ||
                                value.name == 'SKU'){
                                list_theme +='';
                            }
                            else
                            {
                                if (value.maxCount > 1) {
                                    var display_input = '';
                                    var new_counter = 0;

                                    for (var counter = 0; counter < value.maxCount; counter++) {
                                        value_set = '';
                                        if (addin_set) {
                                            if ((value.name in addin_set) && addin_set[value.name][counter]) {
                                                value_set = addin_set[value.name][counter];
                                                new_counter = counter;
                                            }
                                        }
                                        if (counter > new_counter + 1 && value_set === '') display_input = 'style="display: none"';
                                        if (value.name == BX.message('MAXYSS_WB_POL') || value.name == BX.message('MAXYSS_WB_TSVET') || value.name == BX.message('MAXYSS_WB_STRANA') || value.name == BX.message('MAXYSS_WB_KOLLEKCIA') || value.name == BX.message('MAXYSS_WB_SEZON') || value.name == BX.message('MAXYSS_WB_KOMPLECTACIA') || value.name == BX.message('MAXYSS_WB_SOSTAV') || value.name == BX.message('MAXYSS_WB_BREND_WB') || value.name == BX.message('MAXYSS_WB_TNVED_WB')) {
                                            useOnlyDictionaryValues = 'onblur="blur_input();" onkeyup="get_options_new_api_content( \'' + value.name + '\', $(this).val(), \'' + index + '_' + counter + '\', \'' + value.name + '\' );" onfocus="get_options_new_api_content( \'' + value.name + '\', $(this).val(), \'' + index + '_' + counter + '\', \'' + value.objectName + '\' );"';
                                        }
                                        else
                                        {
                                            useOnlyDictionaryValues = 'onchange="showNext(this)"';
                                        }

                                        var_input += '<input ' + display_input + useOnlyDictionaryValues + ' class="adm-input wb_attr"  data-index-input = "atr_' + index + '_' + counter + '" data-type = "' + value.name + '" data-units = "' + units + '" value="' + value_set + '" type="' + type + '" step="' + step_number + '">' + units;
                                    }
                                }
                                else {

                                    value_set = '';
                                    if (addin_set) {
                                        if (value.name in addin_set)
                                            value_set = addin_set[value.name];
                                    }

                                    if (value.name == BX.message('MAXYSS_WB_POL') || value.name == BX.message('MAXYSS_WB_TSVET') || value.name == BX.message('MAXYSS_WB_STRANA') || value.name == BX.message('MAXYSS_WB_KOLLEKCIA') || value.name == BX.message('MAXYSS_WB_SEZON') || value.name == BX.message('MAXYSS_WB_KOMPLECTACIA') || value.name == BX.message('MAXYSS_WB_SOSTAV') || value.name == BX.message('MAXYSS_WB_BREND_WB') || value.name == BX.message('MAXYSS_WB_TNVED_WB')) {
                                        useOnlyDictionaryValues = 'onblur="blur_input();" onkeyup="get_options_new_api_content( \'' + value.name + '\', $(this).val(), \'' + index + '\', \'' + value.objectName + '\' );"';
                                    }

                                    var_input = '<input ' + useOnlyDictionaryValues + ' class="adm-input wb_attr" data-index-input = "atr_' + index + '" data-type = "' + value.name + '" data-units = "' + units + '" value="' + value_set + '" type="' + type + '" step="' + step_number + '">' + units;
                                }
                                if(sinc_set){
                                    if ((value.name in sinc_set)) {
                                        if((sinc_set[value.name][ib_base] && sinc_set[value.name][ib_base].length > 0)  || (sinc_set[value.name][ib_offers] && sinc_set[value.name][ib_offers].length > 0))
                                            span_set = '<span id="span_'+index + '" class="add_prop_sinc_wb green" onclick="add_prop_sinc_wb(\''+value.name +'\', ' + index + ', ' + value.maxCount + ', \'' + type + '\')">+</span>';
                                        else
                                            span_set = '<span id="span_'+index + '" class="add_prop_sinc_wb" onclick="add_prop_sinc_wb(\''+value.name+'\', ' + index + ', ' + value.maxCount + ', \'' + type + '\')">+</span>';
                                    }else{
                                        span_set = '<span id="span_'+index + '" class="add_prop_sinc_wb" onclick="add_prop_sinc_wb(\''+value.name+'\', ' + index + ', ' + value.maxCount + ', \'' + type + '\')">+</span>';
                                    }
                                }
                                else {span_set = '<span id="span_'+index + '" class="add_prop_sinc_wb" onclick="add_prop_sinc_wb(\''+value.name+'\', ' + index + ', ' + value.maxCount + ', \'' + type + '\')">+</span>';}

                                list_theme += '<tr class="bx-in-group"><td><label class="' + required + '" title="">'+ span_set + value.name + star + '</label></td><td  style="padding: 5px 0;" class="type_wb">' + var_input + '</td></tr>';
                            }
                        }
                    });
                    list_theme += '</tbody></table>';
                    $('.predmet_dialog').html(list_theme);
                } else {
                    console.log(errors);
                }
            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });

        if(addin_set == undefined) {
            var attr = {};
            attr['object'] = object;
            atribute_wb.val(JSON.stringify(attr));
        }
    }

    function showNext(el) {
        if(el.value && el.nextElementSibling && el.nextElementSibling.style.display == 'none'){
            el.nextElementSibling.style.display = 'block';
        }
    };
    var get_options_timer;
    function get_options_new_api_content_timer(d, v, i, type) {
        var send_data = {'lang': lang, 'top': 20};
        var errors;
        // if(v.length > 0) {
        //     send_data = {'pattern': v, 'lang': lang, 'top': 20};
        // }
        var dictionari;
        if(d == BX.message('MAXYSS_WB_POL'))
            dictionari = '/content/v1/directory/kinds';
        if(d == BX.message('MAXYSS_WB_TSVET'))
            dictionari = '/content/v1/directory/colors';
        if(d == BX.message('MAXYSS_WB_STRANA'))
            dictionari = '/content/v1/directory/countries';
        if(d == BX.message('MAXYSS_WB_KOLLEKCIA'))
            dictionari = '/content/v1/directory/collections';
        if(d == BX.message('MAXYSS_WB_KOMPLECTACIA'))
            dictionari = '/content/v1/directory/contents';
        if(d == BX.message('MAXYSS_WB_SOSTAV'))
            dictionari = '/content/v1/directory/consists';
        if(d == BX.message('MAXYSS_WB_SEZON'))
            dictionari = '/content/v1/directory/seasons';
        if(d == BX.message('MAXYSS_WB_BREND_WB'))
            dictionari = '/content/v1/directory/brands';
        if(d == BX.message('MAXYSS_WB_TNVED_WB'))
            dictionari = '/content/v1/directory/tnved';

        if (dictionari === '/content/v1/directory/tnved')
            send_data = {'pattern': v, 'option': type, 'action': 'get_directory', 'dictionari': dictionari};
        else
            send_data = {'pattern': v, 'option': type, 'action': 'get_directory', 'dictionari': dictionari};



        var wait_data_card = BX.showWait('wb_data');
        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: send_data,
            success: function (data) {
                BX.closeWait('wb_data', wait_data_card);

                var IS_JSON = true;
                try {
                    var obj = $.parseJSON(data);
                    errors = obj.errorText;
                }
                catch (err) {
                    IS_JSON = false;
                    errors = 'not json oject';
                }
                // console.log(obj);
                if (IS_JSON && !obj.error && obj.data.length > 0) {
                    var list_options = '';
                    $.each(obj.data, function (index, value) {
                        if (dictionari == '/content/v1/directory/tnved') {
                            list_options += '<div style="margin: 5px" onclick="set_option(\'' + index + '\', \'' + value.tnvedName + '\', \'' + i + '\')" data-predmet="' + value.tnvedName + '">' + value.tnvedName + ' ' + value.description + '</div>';
                        }
                        else
                        {
                            list_options += '<div style="margin: 5px" onclick="set_option(\'' + index + '\', \'' + value.name + '\', \'' + i + '\')" data-predmet="' + index + '">' + value.name + '</div>';
                        }

                    });
                    if ($('[data-index-input = "atr_' + i + '"]').parent().find('div').length === 0)
                        $('[data-index-input = "atr_' + i + '"]').after('<div class="wb_select-item">' + list_options + '</div>');
                    else
                        $('[data-index-input = "atr_' + i + '"]').parent().find('div').html(list_options);
                } else {
                    var list_options = '';
                    list_options += '<div style="margin: 5px" >'+ BX.message('MAXYSS_WB_NOT_FOUND_OPTIONS') +'</div>';
                    if ($('[data-index-input = "atr_' + i + '"]').parent().find('div').length === 0)
                        $('[data-index-input = "atr_' + i + '"]').after('<div class="wb_select-item">' + list_options + '</div>');
                    else
                        $('[data-index-input = "atr_' + i + '"]').parent().find('div').html(list_options);
                    console.log(errors);
                }
            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });
    }

    function get_options_new_api_content(d, v, i, type) {
        clearTimeout(get_options_timer);
        get_options_timer = setTimeout(get_options_new_api_content_timer, 500, d,v,i,type);
    }

    if (!PropDialogWb && typeof PropDialogWb !== 'object') {
        var PropDialogWb = '';
    }

    function add_prop_sinc_wb(name, span_id, max_count,  type_input) {
        var iblock_id = new URL(window.location.href).searchParams.get('IBLOCK_ID');
        if(name.length > 0) {
            if (!PropDialogWb) {
                PropDialogWb = new BX.CDialog({
                    title: name,
                    content: '<br><div class="answer_title" style="display: none"></div><form id="form_prop_values"><div class="answer_prop"></div><div class="answer_prop_values"><br><br><br><table id="table_prop_values"></table></div></form><div id="result_sale"></div><br>',
                    icon: 'head-block',
                    resizable: true,
                    draggable: true,
                    height: '500',
                    width: '500',
                    buttons: ['<input type="button" onclick="save_prop_wb();" name="save_prop" value="' + BX.message('MAXYSS_WB_MODULE_SAVE') + '" id="save_prop">', BX.CDialog.btnClose]
                });
            }


            if (iblock_id > 0){
                BX.ajax({
                    method: 'POST',
                    dataType: 'html',
                    timeout: 30,
                    url: '/bitrix/tools/maxyss.wb/ajax.php',
                    data: {
                        action: 'add_prop_sinc',
                        attr_id: name,
                        span_id: span_id,
                        type_input: type_input,
                        max_count: max_count,
                        iblock_id: iblock_id
                    },
                    onsuccess: function (data) {
                        if (data != null) {
                            // if(data.success) {
                            //
                            PropDialogWb.Show();
                            PropDialogWb.SetTitle(name);

                            $('#form_prop_values').html(data);
                            // $('.answer_prop').html(BX.message('MAXYSS_OZON_IBLOCK_BASE') + '<br><br><br>' + data);
                            // $('#table_prop_values').html('');
                            // }

                        }
                    },
                    onfailure: function () {
                        new Error("No document for print");
                    }
                });
            }else{
                $('#table_prop_values').html('');
                $('#save_prop').remove();
                PropDialogWb.Show();
                PropDialogWb.SetTitle(BX.message('MAXYSS_WB_IBLOCK_SINC_TITLE'));
                $('.answer_prop').html(BX.message('MAXYSS_WB_IBLOCK_SINC_TITLE_ERROR') + '<br><br><br>');
            }
        }
    }

    function save_prop_wb() {
        let form = document.querySelector('#form_prop_values');
        let formdata = new FormData(form);
        let data = [];
        formdata.append('action', 'save_prop_sinc_values');

        for (var [key, value] of formdata.entries()) {
            data[key] = value
        }
        BX.ajax({
            method: 'POST',
            dataType: 'json',
            timeout: 30,
            url: '/bitrix/tools/maxyss.wb/ajax.php',
            data: data,
            onsuccess: function (data_res) {
                if (data_res && data_res.error) {
                    alert(data_res.error);
                }else{
                    PropDialogWb.Close();
                    $('#span_' + data.span_id).addClass('green');
                }
            },
            onfailure: function () {
                new Error("Error save prop");
            }
        });
    }

    function get_options(d, v, i, type) {

        var wait_data_card = BX.showWait('wb_data');
        var send_data = {'lang': lang, 'top': 20};
        // if(v.length > 0) {
        //     send_data = {'pattern': v, 'lang': lang, 'top': 20};
        // }

        if (d === '/tnved')
            send_data = {'subject': v};
        else {
            if (v.length > 0)
                send_data = {'pattern': v, 'lang': lang, 'top': 20, 'option': type};
            else
                send_data = {'lang': lang, 'top': 20, 'option': type};
        }

        // console.log(send_data);
        $.ajax({
            type: 'GET',
            url: 'https://content-suppliers.wildberries.ru/ns/characteristics-configurator-api/content-configurator/api/v1/directory' + d + '?'/*+param*/,
            data: send_data,
            success: function (obj) {
                BX.closeWait('wb_data', wait_data_card);

                // console.log(obj);
                if (!obj.error && obj.data !== null) {
                    var list_options = '';
                    $.each(obj.data, function (index, value) {
                        if (index < 20) {
                            if (d === '/tnved') {
                                // console.log(value);
                                list_options += '<div style="margin: 5px" onclick="set_option(\'' + value.tnvedCode + '\', \'' + value.tnvedCode + '\', \'' + i + '\', \'' + type + '\')" data-predmet="' + value.tnvedCode + '">' + value.tnvedCode + ' ' + value.description + '</div>';
                            }
                            else {
                                list_options += '<div style="margin: 5px" onclick="set_option(\'' + value.key + '\', \'' + value.translate + '\', \'' + i + '\', \'' + type + '\')" data-predmet="' + value.key + '">' + value.translate + '</div>';
                            }
                        }
                    });
                    if ($('[data-index-input = "atr_' + i + '"]').parent().find('div').length === 0)
                        $('[data-index-input = "atr_' + i + '"]').after('<div class="wb_select-item">' + list_options + '</div>');
                    else
                        $('[data-index-input = "atr_' + i + '"]').parent().find('div').html(list_options);
                } else {
                    console.log(obj.errorText);
                }
            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });
    }

    function set_option(k, t, i, type) {
// $('[data-index-input = "atr_'+i+'"]').data('key', k);
        $('[data-index-input = "atr_' + i + '"]').parent().find('div').remove();
        $('[data-index-input = "atr_' + i + '"]').val(t).next().css('display', 'block');

        var attr = {};
        $('.type_wb').each(function (i_type, v_type) {
            var index_attr = 0;
            $(v_type).find('.wb_attr').each(function (index_attr, value) {
                if ($(value).val().length > 0) {
                    if (index_attr === 0) {
                        attr[i_type] = {};
                        attr[i_type]["type"] = {};
                        attr[i_type]["type"] = $(value).data('type');
                        attr[i_type].params = [];
                    }
                    var values = {};
                    if ($(value).attr('type') === "number") {
                        values["count"] = parseFloat($(value).val());
                        values["units"] = $(value).data('units');
                    } else {
                        values["value"] = $(value).val();
                    }
                    attr[i_type].params.push(values);
                }
            });
        });

        attr['object'] = object;
        atribute_wb.val(JSON.stringify(attr))
    }

    $(document).on('change', '.wb_attr', function () {
        var attr = {};
        $('.type_wb').each(function (i_type, v_type) {
            var index_attr = 0;
            $(v_type).find('.wb_attr').each(function (index_attr, value) {
                if ($(value).val().length > 0) {
                    if (index_attr === 0) {
                        attr[i_type] = {};
                        attr[i_type]["type"] = {};
                        attr[i_type]["type"] = $(value).data('type');
                        attr[i_type].params = [];
                    }
                    var values = {};
                    if ($(value).attr('type') === "number") {
                        values["count"] = parseFloat($(value).val());
                        values["units"] = $(value).data('units');
                    } else {
                        values["value"] = $(value).val();
                    }
                    attr[i_type].params.push(values);
                }
            });
        });
        attr['object'] = object;
        atribute_wb.val(JSON.stringify(attr))

    });

    function upload_card(id) {

        var btn_save = {
            title: BX.message('MAXYSS_WB_SELECT'),
            id: 'put_lk_btn',
            name: 'put_lk_btn',
            className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
            action: function () {
                custom_lk = $('[name="lk_select"]:checked').val();
                // console.log(this.parentWindow); //
                top.BX.WindowManager.Get().Close();

                var wait_upload_card = BX.showWait('wb_upload');

                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                    data: {
                        action: 'upload_card',
                        product_id: id,
                        lk: custom_lk
                    },
                    success: function (data) {
                        BX.closeWait('wb_upload', wait_upload_card);
                        // alert(data);
                        let DialogC = new BX.CDialog({
                            title: BX.message('WB_MAXYSS_UPLOAD_FINISH'),
                            content: '<div id="download_answer">'+data+'</div>',
                            icon: 'head-block',
                            resizable: true,
                            draggable: true,
                            height: '400',
                            width: '800',
                            buttons: [BX.CDialog.btnClose]
                        });
                        DialogC.Show();
                    },
                    error: function (xhr, str) {
                        alert('Error: ' + xhr.responseCode);
                    }
                });

            }
        };
        var selected_i = '';

        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_all_lk',
            },
            success: function (data) {
                var all_lk_html='';
                var IS_JSON = true;
                try {
                    var obj = $.parseJSON(data);
                }
                catch (err) {
                    IS_JSON = false;
                }
                if (IS_JSON) {
                    $.each(obj, function (index, value) {
                        if(index == "DEFAULT") selected_i = 'checked';
                        else selected_i = '';
                        all_lk_html += '<input name="lk_select" id="lk_'+index+'" type="radio" '+selected_i+' value="'+index+'">  <label for="lk_'+index+'">' + BX.message('MAXYSS_WB_CABINET_LOGOS') + index + '</label><br>';
                    });
                } else {
                    alert('not valid json');
                }


                if(!DialogLk) {
                    DialogLk = new BX.CDialog({
                        title: BX.message('MAXYSS_WB_SELECT_WINDOW_TITLE'),
                        content: '<div id="download_answer">' + all_lk_html + '</div>',
                        icon: 'head-block',
                        resizable: true,
                        draggable: true,
                        height: '400',
                        width: '800',
                        buttons: [btn_save, BX.CDialog.btnClose]
                    });
                }
                DialogLk.Show();

            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });
    }

    function upload_photo(id) {

        var btn_save_photo = {
            title: BX.message('MAXYSS_WB_SELECT'),
            id: 'put_lk_btn_photo',
            name: 'put_lk_btn_photo',
            className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
            action: function () {
                custom_lk = $('[name="lk_select"]:checked').val();
                // console.log(this.parentWindow); //
                top.BX.WindowManager.Get().Close();

                var wait_upload_card = BX.showWait('wb_upload');

                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                    data: {
                        action: 'upload_card',
                        param: 'photo',
                        product_id: id,
                        lk: custom_lk
                    },
                    success: function (data) {
                        BX.closeWait('wb_upload', wait_upload_card);
                        // alert(data);
                        let DialogC = new BX.CDialog({
                            title: BX.message('WB_MAXYSS_UPLOAD_FINISH'),
                            content: '<div id="download_answer">'+data+'</div>',
                            icon: 'head-block',
                            resizable: true,
                            draggable: true,
                            height: '400',
                            width: '800',
                            buttons: [BX.CDialog.btnClose]
                        });
                        DialogC.Show();
                    },
                    error: function (xhr, str) {
                        alert('Error: ' + xhr.responseCode);
                    }
                });

            }
        };
        var selected_i = '';

        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_all_lk',
            },
            success: function (data) {
                var all_lk_html='';
                var IS_JSON = true;
                try {
                    var obj = $.parseJSON(data);
                }
                catch (err) {
                    IS_JSON = false;
                }
                if (IS_JSON) {
                    $.each(obj, function (index, value) {
                        if(index == "DEFAULT") selected_i = 'checked';
                        else selected_i = '';
                        all_lk_html += '<input name="lk_select" id="lk_'+index+'" type="radio" '+selected_i+' value="'+index+'">  <label for="lk_'+index+'">' + BX.message('MAXYSS_WB_CABINET_LOGOS') + index + '</label><br>';
                    });
                } else {
                    alert('not valid json');
                }


                if(!DialogLkPhoto) {
                    DialogLkPhoto = new BX.CDialog({
                        title: BX.message('MAXYSS_WB_SELECT_WINDOW_TITLE'),
                        content: '<div id="download_answer">' + all_lk_html + '</div>',
                        icon: 'head-block',
                        resizable: true,
                        draggable: true,
                        height: '400',
                        width: '800',
                        buttons: [btn_save_photo, BX.CDialog.btnClose]
                    });
                }
                DialogLkPhoto.Show();

            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });
    }
    function data_card(id) {
        var btn_save = {
            title: BX.message('MAXYSS_WB_SELECT'),
            id: 'put_lk_btn',
            name: 'put_lk_btn',
            className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
            action: function () {
                custom_lk = $('[name="lk_select"]:checked').val();
                top.BX.WindowManager.Get().Close();

                var wait_data_card = BX.showWait('wb_data');

                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                    data: {
                        action: 'data_card',
                        product_id: id,
                        lk: custom_lk
                    },
                    success: function (data) {
                        BX.closeWait('wb_data', wait_data_card);

                        var IS_JSON = true;
                        try {
                            var obj = $.parseJSON(data);
                        }
                        catch (err) {
                            IS_JSON = false;
                        }
                        if (IS_JSON) {
                            // var obj=$.parseJSON(data);
                            if (obj.error) {
                                alert(BX.message(obj.error));
                            }

                            if (obj.error_map) {
                                alert(obj.error_map);
                            }
                            if (obj.barcode_not_found) {
                                alert(obj.barcode_not_found);
                            }
                            if (obj.success) {
                                alert(BX.message(obj.success) + BX.message('WB_MAXYSS_GET_DATA_NOT_RELOAD_PAGE'));
                            }
                            //$('.answer').html(data);
                        } else {
                            alert('not valid json');
                        }
                    },
                    error: function (xhr, str) {
                        alert('Error: ' + xhr.responseCode);
                    }
                });

            }
        };
        var selected_i = '';

        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_all_lk',
            },
            success: function (data) {
                var all_lk_html='';
                var IS_JSON = true;
                try {
                    var obj = $.parseJSON(data);
                }
                catch (err) {
                    IS_JSON = false;
                }
                if (IS_JSON) {
                    $.each(obj, function (index, value) {
                        if(index == "DEFAULT") selected_i = 'checked';
                        else selected_i = '';
                        all_lk_html += '<input name="lk_select" id="lk_'+index+'" type="radio" '+selected_i+' value="'+index+'">  <label for="lk_'+index+'">' + BX.message('MAXYSS_WB_CABINET_LOGOS') + index + '</label><br>';
                    });
                } else {
                    alert('not valid json');
                }


                if(!DialogLkData) {
                    DialogLkData = new BX.CDialog({
                        title: BX.message('MAXYSS_WB_SELECT_WINDOW_TITLE_DOWNLOAD'),
                        content: '<div id="download_answer">' + all_lk_html + '</div>',
                        icon: 'head-block',
                        resizable: true,
                        draggable: true,
                        height: '400',
                        width: '800',
                        buttons: [btn_save, BX.CDialog.btnClose]
                    });
                }
                DialogLkData.Show();

            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });

    }

    function CallPrintWb() {
        var WinPrint = window.open('', '', 'left=50,top=50,width=800,height=640,toolbar=0,scrollbars=1,status=0');
        WinPrint.document.write('');
        WinPrint.document.write(htm);
        WinPrint.document.write('');
        WinPrint.document.close();
        WinPrint.focus();
        setTimeout(function () {
            WinPrint.print();
        }, 1000)
        // WinPrint.close();
    }

    function push_orders() {
        var orders = [];
        if ($('#CRM_ORDER_LIST_V12_table')) {
            $('#CRM_ORDER_LIST_V12_table input[name="ID[]"]:checked').each(function (index, value) {
                orders.push(parseInt($(value).val()));
            });
        }
        if ($('#tbl_sale_order')) {
            $('#tbl_sale_order input[name="ID[]"]:checked').each(function (index, value) {
                orders.push(parseInt($(value).val()));
            });
        }
        return orders;
    };

    function showWaitForPrint() {
        var waitBlock = document.createElement('div');
        var count = document.createElement('span');
        count.style.color = '#000';
        count.style.fontSize = '24px';
        count.style.display = 'inline-block';
        count.style.margin = 'auto';
        waitBlock.id = 'pdfWaitId';
        waitBlock.style.background = 'rgba(150,150,150,0.7)';
        waitBlock.style.textAlign = 'center';
        waitBlock.style.position = 'fixed';
        waitBlock.style.top = '0';
        waitBlock.style.bottom = '0';
        waitBlock.style.left = '0';
        waitBlock.style.right = '0';
        waitBlock.style.zIndex = '10000';
        waitBlock.style.display = 'flex';
        document.body.appendChild(waitBlock);
        waitBlock.appendChild(count);
        return waitBlock;
    };
    var waitBlock;
    var allOrders;

    function ListPrintWb() {
        var button = this;
        var orders = [];
        var table = $('#tbl_sale_order');
        orders = push_orders();
        allOrders = orders;
        setTimeout(function () {
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.wb/ajax.php',
                data: {
                    orders: orders,
                    action: 'print_label_wb'
                },
                success: function (data) {
                    var obj = $.parseJSON(data);
                    console.log(obj);
                    if (obj.success) {
                        if (waitBlock && waitBlock.style.display != 'none') {
                            waitBlock.style.display = 'block';
                        } else {
                            waitBlock = showWaitForPrint();
                        }
                        getImgsData(obj.success, 0);
                    }
                    else
                        alert(obj.error);
                },
                error: function (xhr, str) {

                    alert('An error has occurred: ' + xhr.responseCode);
                }
            });
        }, 200);

        return false;
    };

    var stiker_size = {
        w: 580/2.835,
        h: 400/2.835 ,
    };
    var imgSizes = {
        w: 114 * 1.17,
        h: (114 * 1.17) ,
    };

    var getImgsData = function (arr, o, retArr) {
        if (waitBlock) {
            waitBlock.querySelector('span').innerHTML = BX.message('MAXYSS_WB_COMPILING_PDF') + (o + 1) + BX.message('MAXYSS_WB_COMPILING_PDF_IZ') + allOrders.length;
        }
        var pages = retArr || [];
        var page = {};
        var block = document.createElement('div');
        console.log(arr[o].svg);
        block.innerHTML = arr[o].svg;
        var svg = block.querySelector('svg');
        // svg.setAttribute('viewBox', "0 0 400 300");
        // svg.setAttribute('width', 400);
        // svg.setAttribute('height', 300);
        // svg.setAttribute('width', 400 * 1.14285714);
        // svg.setAttribute('height', 300 * 1.1111);
        // var rectsAll = svg.querySelectorAll('rect');
        // rectsAll[rectsAll.length - 1].setAttribute('x', 0);
        // rectsAll[rectsAll.length - 1].setAttribute('width', 400);
        // var texts = svg.querySelectorAll('text');
        // texts[texts.length - 1].setAttribute('y', 255);

        page.svg = svg.outerHTML;
        page.width = svg.getAttribute('width')/2.835;
        // page.width = 114 * 1.17;
        page.height = svg.getAttribute('height')/2.835;
        // page.height = imgSizes.h;
        stiker_size.w = page.width;
        stiker_size.h = page.height;
        pages.push(page);
        var next = o + 1;
        if (arr[next]) {
            getImgsData(arr, next, pages);
        } else {
            init(pages);
            return pages;
        }
    };

    if (typeof pdfMake !== 'undefined') {
        pdfMake.fonts = {
            TNR_pdf: {
                normal: 'TNR_pdf_reg.ttf',
                bold: 'TNR_pdf_bold.ttf',
                italics: 'TNR_pdf_italic.ttf',
                bolditalics: 'TNR_pdf_boldItalic.ttf'
            }
        };
    }

    function init(t) {
        if (waitBlock) {
            waitBlock.style.display = 'none';
        }
        ;
        var marginTop = 0;
        // if(stiker_size.w > 200)
        //     marginTop = 0;
        var docInfo = {
            info: {
                title: '',
                author: '',
                subject: '',
                keywords: ''
            },
            pageSize: {height: stiker_size.h, width: stiker_size.w},
            // pageSize: {height: 85.8, width: 114},
            pageOrientation: 'landscape',//'portrait'
            // pageMargins: [-(imgSizes.w * 0.06), -(imgSizes.h * 0.05), 0, 0],
            pageMargins: [3, marginTop, 3, 5],
            compress: false,
            content: [
                t
            ],
            defaultStyle: {
                font: 'TNR_pdf'
            }
        };
        pdfMake.createPdf(docInfo).open();
    }

    function upload_all_price(cabinet) {

        var isUpload = confirm(BX.message("WB_MAXYSS_PRICE_UPLOAD"));
        if (isUpload) {
            var wait_data_card = BX.showWait('wb_data');
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                data: {
                    action: 'upload_all_price',
                    lk: cabinet
                },
                success: function (data) {
                    BX.closeWait('wb_data', wait_data_card);

                    var IS_JSON = true;
                    try {
                        var obj = $.parseJSON(data);
                    }
                    catch (err) {
                        IS_JSON = false;
                    }
                    if (IS_JSON) {
                        // var obj=$.parseJSON(data);
                        if (obj.error) {
                            alert(obj.error);
                        }
                        if (obj.success) {
                            alert(BX.message("WB_MAXYSS_PRICE_UPLOAD_SUCCESS"));
                        }
                    } else {
                        alert('not valid json');
                    }
                },
                error: function (xhr, str) {
                    alert('Error: ' + xhr.responseCode);
                }
            });
        }
    }

    function upload_stock_null(cabinet) {

        var isUpload = confirm(BX.message("WB_MAXYSS_STOCK_NULL_UPLOAD"));
        if (isUpload) {
            var wait_data_card = BX.showWait('wb_data');
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                data: {
                    action: 'upload_stock_null',
                    lk: cabinet
                },
                success: function (data) {
                    BX.closeWait('wb_data', wait_data_card);
                    var IS_JSON = true;
                    try {
                        var obj = $.parseJSON(data);
                    }
                    catch (err) {
                        IS_JSON = false;
                    }
                    if (IS_JSON) {
                        if (obj.success) {
                            alert(BX.message("WB_MAXYSS_STOCK_NULL_UPLOAD_SUCCESS"));
                        } else alert(BX.message("WB_MAXYSS_STOCK_NULL_UPLOAD_NO_SUCCESS"));
                    } else {
                        alert('not valid json');
                    }
                },
                error: function (xhr, str) {
                    alert('Error: ' + xhr.responseCode);
                }
            });
        }
    }

    function upload_all_discounts(cabinet) {

        var isUpload = confirm(BX.message("WB_MAXYSS_DISCOUNTS_UPLOAD"));
        if (isUpload) {
            var wait_data_card = BX.showWait('wb_data');
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                data: {
                    action: 'upload_discounts',
                    lk: cabinet
                },
                success: function (data) {
                    BX.closeWait('wb_data', wait_data_card);

                    var IS_JSON = true;
                    try {
                        var obj = $.parseJSON(data);
                    }
                    catch (err) {
                        IS_JSON = false;
                    }
                    if (IS_JSON) {
                        // var obj=$.parseJSON(data);
                        if (obj.error) {
                            alert(obj.error);
                        }
                        if (obj.success) {
                            alert(BX.message("WB_MAXYSS_DISCOUNTS_UPLOAD_SUCCESS"));
                        }
                    } else {
                        alert('not valid json');
                    }
                },
                error: function (xhr, str) {
                    alert('Error: ' + xhr.responseCode);
                }
            });
        }
    }

    function upload_all_promocodes(cabinet) {

        var isUpload = confirm(BX.message("WB_MAXYSS_PROMOCODES_UPLOAD"));
        if (isUpload) {
            var wait_data_card = BX.showWait('wb_data');
            $.ajax({
                type: 'GET',
                url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
                data: {
                    action: 'upload_promocodes',
                    lk: cabinet
                },
                success: function (data) {
                    BX.closeWait('wb_data', wait_data_card);

                    var IS_JSON = true;
                    try {
                        var obj = $.parseJSON(data);
                    }
                    catch (err) {
                        IS_JSON = false;
                    }
                    if (IS_JSON) {
                        // var obj=$.parseJSON(data);
                        if (obj.error) {
                            alert(obj.error);
                        }
                        if (obj.success) {
                            alert(BX.message("WB_MAXYSS_PROMOCODES_UPLOAD_SUCCESS"));
                        }
                    } else {
                        alert('not valid json');
                    }
                },
                error: function (xhr, str) {
                    alert('Error: ' + xhr.responseCode);
                }
            });
        }
    }

    let timeOut = 3000;
    let sendItemsObject = {
        errors: 0,
        warnings: 0,
        goods: 0,
    };

    function sendItem(i, arr, dialog, custom_lk) {
        let time = performance.now();
        let realTime = performance.now();
        let infoDiv = dialog.PARAMS.content.querySelector('#upload_wb_items');
        if (i == 0) {
            sendItemsObject.errors = 0;
            sendItemsObject.warnings = 0;
            sendItemsObject.goods = 0;
        }

        BX.ajax({
            method: 'POST',
            dataType: 'html',
            url: '/bitrix/tools/maxyss.wb/ajax.php',
            data: {
                action: 'upload_card',
                product_id: arr[i],
                lk: custom_lk
            },
            onsuccess: function (data) {
                time = performance.now() - time;
                infoDiv.innerHTML += '<span>ID=' + arr[i] + ': ' + data + '</span><br>';

                if (i < arr.length - 1) {
                    i++;
                    setTimeout(function () {
                        realTime = performance.now() - realTime;
                        sendItem(i, arr, dialog, custom_lk);
                    }, timeOut)
                } else {
                    BX.closeWait('wb_upload');

                    infoDiv.innerHTML += '<span style="color: #00a508; font-weight: bold">' + BX.message('WB_MAXYSS_UPLOAD_FINISH') + '</span><br>';
                    return;
                }
            },
            onfailure: function () {
                alert('DO NOT AJAX CALL');
            }
        });
    };

    function getItem(i, arr, dialog, custom_lk) {
        let time = performance.now();
        let realTime = performance.now();
        let infoDiv = dialog.PARAMS.content.querySelector('#download_wb_items');
        let message = '';
        let jsonObjData = {};
        let strinBarcode = '';

        if (i == 0) {
            sendItemsObject.errors = 0;
            sendItemsObject.warnings = 0;
            sendItemsObject.goods = 0;
        }
        BX.ajax({
            method: 'POST',
            dataType: 'html',
            url: '/bitrix/tools/maxyss.wb/ajax.php',
            data: {
                action: 'data_card',
                product_id: arr[i],
                lk: custom_lk
            },
            onsuccess: function (data) {
                // console.log(BX('download_wb_items'));
                message = '';
                time = performance.now() - time;
                jsonObjData = JSON.parse(data);
                if ('success' in jsonObjData) {
                    message = BX.message(jsonObjData.success);

                }
                else {
                    if ('barcode_not_found' in jsonObjData) {
                        message = BX.message('WB_MAXYSS_GET_DATA_BARCODE');
                        strinBarcode = jsonObjData.barcode_not_found.split(',');
                        message += '<br><ul>'
                        for (let i = 0; i < strinBarcode.length; i++)
                            message += '<li>' + strinBarcode[i].replace(BX.message('WB_MAXYSS_GET_DATA_NOT_RELOAD_PAGE'), '') + '</li>';
                        message += '</ul>'
                    }
                    else {
                        if ('error_map' in jsonObjData && jsonObjData.error_map['message'] == 'NOT_VALID_FORMAT_CARD_ID') {
                            message = BX.message('WB_MAXYSS_GET_DATA_NOT_VALID_FORMAT_CARD_ID');

                        }
                        else message = '<span style="color: red">' + data + '</span>';
                    }
                }
                infoDiv.innerHTML += '<span>ID=' + arr[i] + ': ' + message + '</span><br>';

                if (i < arr.length - 1) {
                    i++;
                    setTimeout(function () {
                        realTime = performance.now() - realTime;
                        // console.log('вызов аякса для элемента с ID = '+arr[i]+' задержан на ' + Math.round(Math.round(realTime)/1000)+ ' сек.');
                        getItem(i, arr, dialog, custom_lk);
                    }, timeOut)
                } else {
                    BX.closeWait('wb_upload');

                    infoDiv.innerHTML += '<span style="color: #00a508; font-weight: bold">' + BX.message('WB_MAXYSS_GET_DATA_FINISH') + '</span><br>';
                    // console.log('done');
                    return;
                }
            },
            onfailure: function () {
                alert('DO NOT AJAX CALL');
            }
        });
    };

    function uploadSelectItems(items) {

        var btn_save = {
            title: BX.message('MAXYSS_WB_SELECT'),
            id: 'put_lk_btn',
            name: 'put_lk_btn',
            className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
            action: function () {
                custom_lk = $('[name="lk_select"]:checked').val();
                // console.log(this.parentWindow); //
                top.BX.WindowManager.Get().Close();

        let Dialog = new BX.CDialog({
            title: 'Waiting answer WB',
            content: '<div id="upload_wb_items"></div>',
            // content_post: 'localRedirect',
            icon: 'head-block',
            resizable: true,
            draggable: true,
            height: '400',
            width: '800',
            buttons: [BX.CDialog.btnClose]
        });
        Dialog.Show();
        // console.log(Dialog);
                sendItem(0, items, Dialog, custom_lk);
        let wait_upload_card = BX.showWait('wb_upload');

            }
        };

        var selected_i = '';

        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_all_lk',
            },
            success: function (data) {
                var all_lk_html='';
                var IS_JSON = true;
                try {
                    var obj = $.parseJSON(data);
                }
                catch (err) {
                    IS_JSON = false;
                }
                if (IS_JSON) {
                    $.each(obj, function (index, value) {
                        if(index == "DEFAULT") selected_i = 'checked';
                        else selected_i = '';
                        all_lk_html += '<input name="lk_select" id="lk_'+index+'" type="radio" '+selected_i+' value="'+index+'">  <label for="lk_'+index+'">' + BX.message('MAXYSS_WB_CABINET_LOGOS') + index + '</label><br>';
                    });
                } else {
                    alert('not valid json');
                }


                if(!DialogLk) {
                    DialogLk = new BX.CDialog({
                        title: BX.message('MAXYSS_WB_SELECT_WINDOW_TITLE'),
                        content: '<div id="download_answer">' + all_lk_html + '</div>',
                        icon: 'head-block',
                        resizable: true,
                        draggable: true,
                        height: '400',
                        width: '800',
                        buttons: [btn_save, BX.CDialog.btnClose]
                    });
                }else{
                    DialogLk.ClearButtons();
                    DialogLk.SetButtons([btn_save, BX.CDialog.btnClose]);
                }
                DialogLk.Show();

            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });

    }

    function getWBAttributesSelectItems(items) {
        var btn_save = {
            title: BX.message('MAXYSS_WB_SELECT'),
            id: 'put_lk_btn',
            name: 'put_lk_btn',
            className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
            action: function () {
                custom_lk = $('[name="lk_select"]:checked').val();
                top.BX.WindowManager.Get().Close();

                let Dialog = new BX.CDialog({
                    title: 'Waiting answer WB',
                    content: '<div id="download_wb_items"></div>',
                    // content_post: 'localRedirect',
                    icon: 'head-block',
                    resizable: true,
                    draggable: true,
                    height: '400',
                    width: '800',
                    buttons: [BX.CDialog.btnClose]
                });
                Dialog.Show();

                getItem(0, items, Dialog, custom_lk);
                let wait_upload_card = BX.showWait('wb_upload');

            }
        };

        var selected_i = '';

        $.ajax({
            type: 'GET',
            url: '/bitrix/tools/maxyss.wb/ajax.php'/*+param*/,
            data: {
                action: 'get_all_lk',
            },
            success: function (data) {
                var all_lk_html='';
                var IS_JSON = true;
                try {
                    var obj = $.parseJSON(data);
                }
                catch (err) {
                    IS_JSON = false;
                }
                if (IS_JSON) {
                    $.each(obj, function (index, value) {
                        if(index == "DEFAULT") selected_i = 'checked';
                        else selected_i = '';
                        all_lk_html += '<input name="lk_select" id="lk_'+index+'" type="radio" '+selected_i+' value="'+index+'">  <label for="lk_'+index+'">'+ BX.message('MAXYSS_WB_CABINET_LOGOS') + index + '</label><br>';
                    });
                } else {
                    alert('not valid json');
                }


                if(!DialogLkData) {
                    DialogLkData = new BX.CDialog({
                        title: BX.message('MAXYSS_WB_SELECT_WINDOW_TITLE_DOWNLOAD'),
                        content: '<div id="download_answer">' + all_lk_html + '</div>',
                        icon: 'head-block',
                        resizable: true,
                        draggable: true,
                        height: '400',
                        width: '800',
                        buttons: [btn_save, BX.CDialog.btnClose]
                    });
                }else{
                    DialogLkData.ClearButtons();
                    DialogLkData.SetButtons([btn_save, BX.CDialog.btnClose]);
                }

                DialogLkData.Show();

            },
            error: function (xhr, str) {
                alert('Error: ' + xhr.responseCode);
            }
        });
    }
    window.mxWB = true;

    function add_lk() {
        var btn_save = {
            title: BX.message('JS_CORE_WINDOW_SAVE'),
            id: 'save_lk_btn',
            name: 'save_lk_btn',
            className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
            action: function () {
                $.ajax({
                    type: 'GET',
                    url: '/bitrix/tools/maxyss.wb/ajax.php?action=add_lk',
                    data:{
                        name_lk: document.getElementById('name_lk').value,
                        uuid: $('#uuid_add').val(),
                        authorization: $('#authorization_add').val()
                    },
                    success: function (data) {
                        var IS_JSON = true;
                        try {
                            var obj = $.parseJSON(data);
                        }
                        catch (err) {
                            IS_JSON = false;
                        }
                        if (IS_JSON) {
                            if (obj.TYPE == 'ERROR') {
                                alert(obj.MESSAGE);
                            }
                            if (obj.TYPE == 'SUCCESS') {
                                alert(obj.MESSAGE);
                                top.BX.WindowManager.Get().Close();

                            }
                        } else {
                            alert('not valid json');
                        }
                        // $('#answer_add_lk').html(data);
                    },
                    error: function (xhr, str) {
                        alert('Error: ' + xhr.responseCode);
                    }
                });

            }
        };

        let DialogC = new BX.CDialog({
            title: BX.message('WB_MAXYSS_ADD_LK_WINDOW_TITLE'),
            content: '<div><form id="add_lk_form"><table class="adm-detail-content-table edit-table">\n' +
            '            <tbody>\n' +
            '            <tr class="heading">\n' +
            '                <td colspan="2">'+BX.message('MAXYSS_WB_MODULE_AUTH')+'</td>\n' +
            '            </tr>\n' +
            '            <tr>\n' +
            '                <td class="adm-detail-content-cell-r">'+BX.message('MAXYSS_WB_LK_NAME')+'</td>\n' +
            '                <td class="adm-detail-content-cell-l">\n' +
            '                    <input  type="text" name="name_lk" id="name_lk" value="">\n' +
            '                </td>\n' +
            '            </tr>\n' +
            '            <tr>\n' +
            '                <td class="adm-detail-content-cell-r">'+BX.message('MAXYSS_WB_UUID')+'</td>\n' +
            '                <td class="adm-detail-content-cell-l">\n' +
            '                    <input  type="text" name="uuid" id="uuid_add" value="">\n' +
            '                </td>\n' +
            '            </tr>\n' +
            '            <tr>\n' +
            '                <td class="adm-detail-content-cell-r">'+BX.message('MAXYSS_WB_AUTHORIZATION')+'</td>\n' +
            '                <td class="adm-detail-content-cell-l">\n' +
            '                    <input  type="text" name="authorization" id="authorization_add" value=""><span ></span>\n' +
            '                </td>\n' +
            '            </tr></tbody></table></form></div><div id="answer_add_lk"></div>',
            icon: 'head-block',
            resizable: true,
            draggable: true,
            height: '400',
            width: '800',
            buttons: [btn_save, BX.CDialog.btnClose ]
        });
        DialogC.Show();
    }
}