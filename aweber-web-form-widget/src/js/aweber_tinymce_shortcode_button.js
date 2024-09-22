/*!     aweber_tinymce_shortcode_button.js
 *
 *     Custom Plugin to add the short code button to the Wordpress Editor.
 */

(function() {
	var selected_shortcode = null;

 	tinymce.PluginManager.add('aweber_shortcode_button', function( editor, url ) {
		editor.addButton('aweber_shortcode_button', {
			text: '',
			image: url + './../../AWeber_widget_black.png',
			onclick: function() {
				if (jQuery('#toplevel_page_aweber .wp-submenu > li :contains(Form)').length == '0') {
					editor.windowManager.alert('Please connect your AWeber account.');
					return;
				}

				var data = {
					'action': 'get_aweber_webform_shortcodes',
					'nonce': jQuery('#aweber-tinymice-shotcode-nonce').val()
				};

				jQuery.get(ajaxurl, data, function(response){
					editor.windowManager.close();

					response = JSON.parse(response);
					if (response.status == 'error') {
						editor.windowManager.alert(response.message, function(){
							// To support Wordpress 4.7v windowManger.close not working. So manually removed all the windows.
				        	jQuery('.mce-reset').hide();
				        	jQuery('.mce-window').remove();
						});
						return false;
					}

                    var optgrp_html = `<select style="width:100%;border:1px solid #ccc;" id="aweber-opt-group-select">
                                       <option disabled="disabled" selected="selected">Please select a Sign Up Form</option>
                                       <optgroup label="${escape_html_char(response.short_codes[0].list_name)}">`;
					var current_list_id = response.short_codes[0].list_id;
					jQuery.each(response.short_codes, function(key,value){
						if (current_list_id != value.list_id) {
							optgrp_html += "</optgroup>";
							optgrp_html += "<optgroup label='" + escape_html_char(value.list_name) + "'>";
						}
						optgrp_html += "<option value='"+ escape_html_char(value.value) +"'>"+ escape_html_char(value.text) +"</option>";
						current_list_id = value.list_id;
					});
					optgrp_html += '</select>';

					editor.windowManager.open({
	                    title: 'Add an AWeber Sign Up Form',
						body: [{
								type   : 'container',
								id	   : 'aweber-short-codes',
								html   : optgrp_html,
								minWidth: Math.min(window.innerWidth, 450)
				        	}
				        ],
				        onClose: function() {
				        	// To support Wordpress 4.7v windowManger.close not working. So manually removed all the windows.
				        	jQuery('.mce-reset').hide();
				        	jQuery('.mce-window').remove();
				        },
				        onsubmit: function(e) {
				        	if (!selected_shortcode) {
				        		editor.windowManager.alert('Please select the appropriate shortcode.');
				        		return false;
				        	}
				        	var shortcode = selected_shortcode.split('-');
				        	editor.insertContent('[aweber listid="'+shortcode[0]+'" formid="'+shortcode[1]+'" formtype="'+shortcode['2']+'"]');

				        	// To support Wordpress 4.7v windowManger.close not working. So manually removed all the windows.
				        	jQuery('.mce-reset').hide();
				        	jQuery('.mce-window').remove();
				        }
	                });
				}).fail(function(data){
					editor.windowManager.close();

					editor.windowManager.alert('An unexpected error occured. Please check your internet connection and try again', function(){
						// To support Wordpress 4.7v windowManger.close not working. So manually removed all the windows.
			        	jQuery('.mce-reset').hide();
			        	jQuery('.mce-window').remove();
					});
				});

				editor.windowManager.alert('Please wait, fetching the available shortcodes.');
				if (jQuery('.mce-reset').is(':hidden') ) {
					jQuery('.mce-reset').show();
				}
			}
		});
	});

	function escape_html_char(str) {
		var mapObj = {
			"'"	: "&apos;",
			'&'	: "&amp;",
			'"' : "&quot;"
		};
		return str.replace(/'|"|&/gi, function(matched){
			return mapObj[matched];
		});
	}

	jQuery(document).on('change', '#aweber-opt-group-select', function(){
		selected_shortcode = jQuery(this.options[this.selectedIndex]).val();
	});
})();
