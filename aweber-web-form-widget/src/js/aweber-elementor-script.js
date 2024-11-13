var shortcodes = [];
var lists = [];
var model_fields = [];
var custom_fields = [];
var get_aweber_shortcodes_ajax = null;
var get_aweber_lists_ajax = null;
var elementor_control_aweber_form_submit_added = false;
var elementor_control_aweber_form_submit_observer = null;
var elementor_control_aweber_widget_observer = null;

function update_message(panel, message, selector) {
    cls_selector = 'important_note';
    if (typeof selector !== 'undefined') {
        cls_selector = selector;
    }
    panel.$el.find('.elementor-control-' + cls_selector)
                    .find('.elementor-control-raw-html').html(message);
}

function load_select_option(panel, data, cls, key, val) {
    for (let i =0; i < data.length; i++) {
        panel.$el.find(cls +' select')
                .append(jQuery("<option></option>")
                        .attr("value", data[i][key]).text(data[i][val]));
    }
}

function load_signup_dropdown(panel, selected_list_id) {
	if(selected_list_id == 0) {
		panel.$el.find('.elementor-control-aweber_form select')
                .append(jQuery("<option></option>")
                        .attr("value", 0)
                        .text('Please select sign Up form & Split Tests'));
    }

    let list = shortcodes.filter(obj => obj.list_id == selected_list_id);
    if (list.length) {
        load_select_option(panel, list[0].options,
            '.elementor-control-aweber_form', 'value', 'name')
    } else {
        panel.$el.find('.elementor-control-aweber_form select')
                .append(jQuery("<option></option>")
                        .attr("value", 0).text('No signup form found'));
    }
}

function attach_signup_form(panel, model, shortcodes) {
    let settings = model.get('settings');
    panel.$el.find('.elementor-control-important_note').hide();

    load_select_option(panel, shortcodes, '.elementor-control-aweber_list',
                 'list_id', 'list_name')
    if ('aweber_list' in settings.attributes) {
        panel.$el.find('.elementor-control-aweber_list select')
                .val(settings.attributes['aweber_list']);

        load_signup_dropdown(panel, settings.attributes['aweber_list']);
        if ('aweber_form' in settings.attributes) {
            let webform_val = 0;
            if (settings.attributes['aweber_form'].includes(settings.attributes['aweber_list'])) {
                webform_val = settings.attributes['aweber_form'];
            }
            panel.$el.find('.elementor-control-aweber_form select').val(webform_val);
        }
    }

    panel.$el.find('.elementor-control-aweber_list select').change(function(){
        panel.$el.find('.elementor-control-aweber_form select').empty();
        panel.$el.find('.elementor-control-aweber_form select').val(0);
        load_signup_dropdown(panel, jQuery(this).val());
    });

    panel.$el.find('.elementor-control-aweber_form select').change(function(){
        // Remove the scripts loaded for the forms, so that the next time same form is selected it should load.
        jQuery('script').each(function () {
            let $this = jQuery(this);
            let script_id = jQuery(this).attr('id');
            if (typeof script_id !== 'undefined' &&
                    script_id.includes('aweber-wjs')) {
                $this.remove();
            }
        });
    });
}

function parse_aweber_shortcodes(result) {
    shortcodes.push({
        list_id: 0,
        list_name: 'Please select list',
        options: []
    });

    let options = [];
    options.push({
        'name': 'Please select sign Up form & Split Tests',
        'value': 0
    })
    let currentListId = result.short_codes[0].list_id;
    let currentListName = result.short_codes[0].list_name;
    for(let i=0; i < result.short_codes.length; i++) {
        if (currentListId != result.short_codes[i].list_id) {
            shortcodes.push({
                list_id: currentListId,
                list_name: currentListName,
                options: options
            });
            options = [];
            options.push({
                'name': 'Please select signup form',
                'value': 0
            })
            currentListId = result.short_codes[i].list_id;
            currentListName = result.short_codes[i].list_name;
        }
        option_name = 'Form - ' + result.short_codes[i].text;
        if(result.short_codes[i].value.includes('split_tests')) {
            option_name = 'Split - ' + result.short_codes[i].text;
        }
        options.push({
            'name': option_name,
            'value': result.short_codes[i].value
        });
    }
    if (options.length > 0) {
        shortcodes.push({
            'list_id': currentListId,
            'list_name': currentListName,
            'options': options
        });
    }
}

function init_aweber_elements(panel, model, view) {
    if (panel.$el.find('.elementor-control-aweber_connection_closed_message').length) {
        return;
    }

    var settings = model.get('settings');
    if (shortcodes.length) {
        attach_signup_form(panel, model, shortcodes);
    } else {
        panel.$el.find('.elementor-control-aweber_list').hide();
        panel.$el.find('.elementor-control-aweber_form').hide();

        if (get_aweber_shortcodes_ajax !== null) {
            // One ajax request already in pending state. Dont call one more.
            return;
        }

        get_aweber_shortcodes_ajax = jQuery.getJSON(php_vars.ajax_url + '?action=get_aweber_webform_shortcodes&nonce=' + php_vars.nonces.get_aweber_webform_shortcodes, function(result){
            if (result.status == 'error') {
                message = result.message;
                if (result.message.includes('reconnect')) {
                    message = '<p style="text-align: center">' + result.message + '<br>\
                    <br><a href="' + php_vars.plugin_connect_url + '">Go to Plugin</a></p>';
                }
                update_message(panel, message);
            } else {
                parse_aweber_shortcodes(result);

                panel.$el.find('.elementor-control-aweber_list').show();
                panel.$el.find('.elementor-control-aweber_form').show();

                attach_signup_form(panel, model, shortcodes);
            }
            // Set it to null. Ajax request completed.
            get_aweber_shortcodes_ajax = null;
        }).fail(function(data){
            update_message(panel, 'An unexpected error occurred. \
                Please check your internet connection and try again');
            // Set it to null. Ajax request completed.
            get_aweber_shortcodes_ajax = null;
        });
    }
}

function load_aweber_custom_fields(panel, model, list_id) {
    // Empty the Previous seleted custom fields.
    custom_fields = [];
    // Prepare Ajax request data
    let data = {
        list_id: list_id,
        action: 'get_aweber_custom_fields',
        nonce: php_vars.nonces.get_aweber_custom_fields
    };
    // Show the Custom fields loading Message
    panel.$el.find(".elementor-control-aweber_custom_fields_message").show();
    update_message(panel, 'Loading custom fields', 'aweber_custom_fields_message');

    // Update the value to None for previous selected fields.
    panel.$el.find('[class*=elementor-control-aweber_form_custom_dynamic_field_]:visible')
                    .each(function(index){
        model.setSetting('aweber_form_custom_dynamic_field_' + index, '0');
    });

    // Hide all the prevoius Custom fields
    panel.$el.find('[class*=elementor-control-aweber_form_custom_dynamic_field_]').hide();
    // If the valid list id is not selceted, then return
    if (list_id == '0') {
        panel.$el.find(".elementor-control-aweber_custom_fields_message").hide();
        return;
    }

    // Get the Custom Fields for the selected lists.
    jQuery.getJSON(php_vars.ajax_url, data, function(result){
        if (result.status == 'error') {
            message = result.message;
            if (result.message.includes('reconnect')) {
                message = '<p style="text-align: center">' + result.message + '<br>\
                <br><a href="' + php_vars.plugin_connect_url + '">Go to Plugin</a></p>';
            }
            update_message(panel, message, 'aweber_custom_fields_message');
        } else {
            // Hide the custom fields loading Message
            panel.$el.find(".elementor-control-aweber_custom_fields_message").hide();
            // Get the Fields
            custom_fields = result.custom_fields;
            if (custom_fields.length > 0) {
                // IF their are fields for a list
                show_load_dynamic_custom_fields(panel, model);
            }
        }
    }).fail(function(data){
        update_message(panel, 'An unexpected error occurred. \
            Please check your internet connection and try again', 'aweber_custom_fields_message');
    });
}

function update_lists(panel, model) {
    panel.$el.find('.elementor-control-aweber_form_list select').empty();
    load_select_option(panel, lists, '.elementor-control-aweber_form_list',
                        'list_id', 'list_name');

    // Load the Forms fields in the AWeber static fields dropdown
    update_field_mapping(panel, model);
    // Get the selected Lists IF any and update in List dropdown.
    var settings = model.get('settings');
    if ( 'aweber_form_list' in settings.attributes &&
            settings.attributes['aweber_form_list'] != '' &&
            settings.attributes['aweber_form_list'] != '0' ) {
        // Update the Selected list id.
        panel.$el.find('.elementor-control-aweber_form_list select')
                        .val(settings.attributes['aweber_form_list']);

        // Load the Custom if there are any for the selected lists.
        if (custom_fields.length) {
            show_load_dynamic_custom_fields(panel, model);
        } else {
            load_aweber_custom_fields(panel, model, settings.attributes['aweber_form_list']);
        }
    }
    // Once the List are loaded, Hide the Loading Message.
    panel.$el.find('.elementor-control-important_note').hide();

    // Once lists are loaded, Show the Lists, tags and Static Fields
    panel.$el.find(".elementor-control-aweber_form_list").show();
    panel.$el.find(".elementor-control-aweber_form_tags").show();
    panel.$el.find(".elementor-control-aweber_form_more_options").show();
    panel.$el.find(".elementor-control-aweber_form_name_static_field").show();
    panel.$el.find(".elementor-control-aweber_form_email_static_field").show();

    // Add the Change Event to the List Dropdown.
    panel.$el.find('.elementor-control-aweber_form_list select').off().on('change', function(){
        let selected_list_id = jQuery(this).val();
        load_aweber_custom_fields(panel, model, selected_list_id);
    });

    // Add the change event to the "Email"
    panel.$el.find('.elementor-control-aweber_form_email_static_field select').off().on('change', function(){
        model.setSetting('aweber_form_email_static_field', jQuery(this).val());
    });

    // Add the change event to the "Name"
    panel.$el.find('.elementor-control-aweber_form_name_static_field select').off().on('change', function(){
        model.setSetting('aweber_form_name_static_field', jQuery(this).val());
    });
}

function show_load_dynamic_custom_fields(panel, model) {
    panel.$el.find('[class*=elementor-control-aweber_form_custom_dynamic_field_]')
                .slice(0, custom_fields.length).each(function(index){
        // Show the Field.
        jQuery(this).show();
        // Show the Custom field name
        jQuery(this).find('.elementor-control-title').html(custom_fields[index]);
        // Load the Fields in the Front end.
        update_field_mapping(panel, model, ['aweber_form_custom_dynamic_field_'+index]);
        // Add an event to update the model custom fields value, when altered
        panel.$el.find('.elementor-control-aweber_form_custom_dynamic_field_'+index+' select').off().on('change', function(){
            model.setSetting('aweber_form_custom_dynamic_field_' + index, jQuery(this).val());
        });
    });
}

function get_fields(model) {
    // Empty the Fields
    model_fields = [];

    let settings = model.get('settings');
    let models = settings.attributes.form_fields['models'];

    model_fields.push({'id': '0', 'label': '- None -'});
    for (let i=0; i<models.length; i++) {
        model_fields.push({
            'id': models[i].attributes.custom_id,
            'label': models[i].attributes.field_label || models[i].attributes.placeholder || 'NA'
        });
    }
}

function update_field_mapping (panel, model, options) {
    let settings = model.get('settings');
    if (typeof options === 'undefined') {
        options = ['aweber_form_name_static_field', 'aweber_form_email_static_field'];
    }
    for (let op=0; op<options.length; op++) {
        let local_fields = JSON.parse(JSON.stringify(model_fields));
        let selector = '.elementor-control-' + options[op];
        let label_name = panel.$el.find(selector)
                                    .find('.elementor-control-title')
                                    .text();
        // Use this only for CustomFields, not the Name and Email Fields.
        if (options[op] != 'aweber_form_name_static_field' && options[op] != 'aweber_form_email_static_field') {
            for (let m=1; m < local_fields.length; m++) {
              local_fields[m]['id'] = local_fields[m]['id']+'-('+label_name+')';
            }
        }
        panel.$el.find(selector + ' select').empty();
        load_select_option(panel, local_fields, selector, 'id', 'label');
        // condition check, what value to set in the custom fields dropdown.
        let option_val = "0";
        if (settings.attributes[options[op]]) {
            if (panel.$el.find(selector + ' option[value="'+settings.attributes[options[op]]+'"]').length != 0) {
                option_val = settings.attributes[options[op]];
            }
        }
        panel.$el.find(selector + ' select').val(option_val);
    }
}

function load_aweber_list(panel, model) {
    // If the AWeber Connection is closed
    if (panel.$el.find('.elementor-control-aweber_connection_closed_message').length) {
        return;
    }
    // Check the AWeber Panel is active.
    if (panel.$el.find('.elementor-control-aweber_form_list').length == 0) {
        return;
    }
    // Show the Loading message.
    panel.$el.find('.elementor-control-important_note').show();

    // Get the Form fields
    get_fields(model);

    // Hide the AWeber forms, untill the lists are loaded.
    panel.$el.find(".elementor-control-aweber_form_list").hide();
    panel.$el.find(".elementor-control-aweber_form_tags").hide();
    panel.$el.find(".elementor-control-aweber_form_more_options").hide();
    panel.$el.find(".elementor-control-aweber_form_name_static_field").hide();
    panel.$el.find(".elementor-control-aweber_form_email_static_field").hide();
    panel.$el.find(".elementor-control-aweber_custom_fields_message").hide();
    // Hide the AWeber Dynamic Custom Fields
    panel.$el.find('[class*=elementor-control-aweber_form_custom_dynamic_field_]').hide();
    if (lists.length > 0) {
        update_lists(panel, model);
    } else {
        if (get_aweber_lists_ajax !== null) {
            // One ajax request already in pending state. Dont call one more.
            return;
        }
        get_aweber_lists_ajax = jQuery.getJSON(php_vars.ajax_url + '?action=get_aweber_lists&nonce=' + php_vars.nonces.get_aweber_lists, function(result){
            if (result.status == 'error') {
                message = result.message;
                if (result.message.includes('reconnect')) {
                    message = '<p style="text-align: center">' + result.message + '<br>\
                    <br><a href="' + php_vars.plugin_connect_url + '">Go to Plugin</a></p>';
                }
                update_message(panel, message);
            } else {
                lists = result.lists;
                lists.unshift({'list_id': 0, 'list_name': 'Please select list'});
                update_lists(panel, model);
            }
            get_aweber_lists_ajax = null;
        }).fail(function(data){
            update_message(panel, 'An unexpected error occurred. \
                Please check your internet connection and try again');
            get_aweber_lists_ajax = null;
        });
    }
}

jQuery( window ).on( 'elementor/frontend/init', () => {
    if (typeof elementor !== 'undefined') {
        elementor.hooks.addAction( 'panel/open_editor/widget/aweber', function(panel, model, view) {
            // Init the AWeber elements.
            init_aweber_elements(panel, model, view);
            if (elementor_control_aweber_widget_observer) {
                // Disconnect the observer if already connected.
                elementor_control_aweber_widget_observer.disconnect();
            }
            elementor_control_aweber_widget_observer = new MutationObserver((mutationList, observer) => {
                if (panel.$el.hasClass('elementor-control-aweber_form') || panel.$el.find('.elementor-control-aweber_form').length) {
                    init_aweber_elements(panel, model, view);
                } else if (panel.$el.find('#elementor-panel-header-title').text().includes('AWeber') === false) {
                    // AWeber Panel got closed. so disconnect the observer.
                    observer.disconnect();
                    elementor_control_aweber_widget_observer = null;
                }
            });
            elementor_control_aweber_widget_observer.observe(panel.$el.find('#elementor-panel-content-wrapper')[0], { attributes: true, childList: true });
        });

        elementor.hooks.addAction( 'panel/open_editor/widget/form', function(panel, model, view) {
            if (elementor_control_aweber_form_submit_observer) {
                elementor_control_aweber_form_submit_observer.disconnect();
            }
            // Create an observer instance linked to the callback function
            elementor_control_aweber_form_submit_observer = new MutationObserver((mutationList, observer) => {
                if (panel.$el.hasClass('elementor-control-aweber_form_list') || panel.$el.find('.elementor-control-aweber_form_list').length) {
                    // Load the lists if its not added.
                    if (elementor_control_aweber_form_submit_added === false) {
                        load_aweber_list(panel, model);
                    }
                    elementor_control_aweber_form_submit_added = true;
                } else {
                    elementor_control_aweber_form_submit_added = false;
                }

                if (panel.$el.find('#elementor-panel-header-title').text().includes('Form') === false) {
                    observer.disconnect();
                    elementor_control_aweber_form_submit_observer = null;
                }
            });
            // Start observing the target node for configured mutations
            elementor_control_aweber_form_submit_observer.observe(panel.$el.find('#elementor-panel-content-wrapper')[0], { attributes: true, childList: true });
        });
    }
});
