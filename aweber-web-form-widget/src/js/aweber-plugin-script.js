jQuery(document).ready(function(){
	// Trigger fetching the AWeber lists, only if the lists dropdown exists.
	if (jQuery('.AWeberWebformPluginWidgetOptions-list').length > 0) {
		// Load the AWeber lists from the Backend.
		getAweberLists();
	}

	// Javascript to Remove the hide button in the Block-Based Widget section
	setTimeout(function() {
		let div = jQuery('input[name="widget-id"][value="'+ php_vars.aweber_widget_id +'"]').parent();
		if (div.find('.aweber-legacy-message').length > 0) {
			div.find('.button').hide();
		}
	}, 3000);
});

function handleAPIError(response, selector) {
	if ('error_code' in response) {
		jQuery('.aweber-forms-error-message').find('p').html(response.message);
		jQuery('.aweber-forms-error-message').removeClass('aweber-hide');
		jQuery('.aweber-body').remove();
		// if error is because of the OAuth token.
		if (response.error_code == '401') {
			// Remove the Forms Sub menu.
			jQuery('#toplevel_page_aweber .wp-submenu > li :contains(Form)').parent().remove();
			jQuery('#toplevel_page_aweber .wp-submenu > li :contains(Landing)').parent().remove();

			// After 5 seconds the user will be redirected to the Settins page.
			setTimeout(function(){
				window.location.href = response.redirect;
			}, 5000);
		}
	} else {
		jQuery(selector).html('<p>' + response.message + '</p>');
	}
}

function getAweberLists() {
	let data = {action: "get_aweber_lists", nonce: php_vars.aweber_nonce.get_aweber_lists};
	jQuery.getJSON(ajaxurl, data, function(response){
		if (response.status == 'error') {
			handleAPIError(response);
			return;
		}
		let lists = response.lists;
		let options = '<option value="FALSE">Select a list</option>';
		for (let i=0; i<lists.length; i++) {
			options += '<option value="' + lists[i].list_id+ '">' + lists[i].list_name+ '</option>';
		}
		// Append the options to the list dropdown.
		jQuery('.AWeberWebformPluginWidgetOptions-list').html(options);
		// select the previous selected list-id.
		jQuery('.AWeberWebformPluginWidgetOptions-list').each(function(key, value) {
			let list_id = jQuery(this).attr('data-selected');
			if (list_id != '') {
				jQuery(this).val(list_id);
			}
		});
		// Trigger the dropdown to do ajax call.
		if(jQuery('.AWeberWebformPluginWidgetOptions-list').length) {
			jQuery('.AWeberWebformPluginWidgetOptions-list').change();
		}
	});
}

// Scripts Used in the Forms Page.
jQuery(document).on('change', '.AWeberWebformPluginWidgetOptions-list', function(){
	var record_type = jQuery(this).attr('data-type');
	var selector = '.signup-webform-list';
	var action = 'get_signup_webforms';
  var nonce = php_vars.aweber_nonce.get_signup_webforms;
	var message = 'Please wait while the Sign Up Forms for your list are retrieved.';
	var empty_message = 'You do not have any Sign Up Forms for the selected list.';
	if (record_type == 'split-test') {
		selector = '.split-webform-list';
		message = 'Please wait while the split test Forms for your list are retrieved.';
		empty_message = 'You do not have any split test Forms for the selected list.';
	} else if (record_type == 'landing_pages') {
		action  = 'get_landing_pages';
    nonce = php_vars.aweber_nonce.get_landing_pages;
		selector = '.landing-pages-list';
		message = 'Please wait while the landing pages for your list are retrieved.';
		empty_message = 'You do not have any landing pages for the selected list.';
	}

	var list_id = jQuery(this).val();
	if (list_id == null || list_id == 'FALSE') {
		jQuery(selector).html('<p>' + empty_message + '</p>');
		return false;
	}

	var data = {
		'action': action,
		'type'	: record_type,
		'list_id': list_id,
    'nonce' : nonce
	};

	jQuery(selector).html('<p>' + message + '</p>');

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.getJSON(ajaxurl, data, function(response){
		if (response.status == 'error') {
			handleAPIError(response, selector);
			return;
		}

		var web_forms = '<div class="table-scroll">';
		if (record_type == 'sigup-form') {
			web_forms += '<table class="aweber-forms-table">\
					<thead>\
					 	<tr>\
					    	<th>Name</th>\
					    	<th>Shortcode</th>\
					    	<th>Tags</th>\
					    	<th>Type</th>\
					    	<th>Displays</th>\
					    	<th>Submissions</th>\
					    	<th>Conversion Rate</th>\
					    	<th>Location</th>\
					 	</tr>\
					</thead>\
					<tbody>';
			jQuery.each(response.data, function(key, value) {
				web_forms += '<tr>\
			   		<td>' + value.name + ' <a target="_blank" class="aweber-preview-form-link" href="'+value.preview_form+'">(Preview Form)</a></td>\
			   		<td>' + value.shortcode +'</td>\
			   		<td>' + (value.tags || '-') + '</td>\
					<td>' + value.type + '</td>\
			   		<td>' + value.displays + '</td>\
			   		<td>' + value.submissions + '</td>\
			   		<td>' + value.conversion_rate + '</td>\
			   		<td>' + (value.location != '[]' ? "<a href='javascript:void(0)' data-form-name='" + value.name + "' data-locations='"+value.location+"' class='view-all-locations'>View all</a>" : 'None Found') +'</td>\
				</tr>';
			});
			web_forms += '</tbody></table>';
		} else if(record_type == 'split-test') {
			jQuery.each(response.data, function(key, value) {
				web_forms += '<div>\
				    <div class="aweber-fleft">\
				    	<h2><b>'+ value.test_name + '</b></h2>\
				    </div>\
				    <div class="aweber-fright aweber-text-right">\
				        <span><b>Shortcode:</b> ' +  value.shortcode + '</span><br>\
				        <span><b>Location:</b> ' + (value.location != '[]' ? "<a href='javascript:void(0)' data-form-name='" + value.test_name + "' data-locations='"+value.location+"' class='view-all-locations'>View all</a>" : 'None Found') + '</span>\
				    </div>\
				    <div style="clear:both"></div>\
				</div>';
				web_forms += '<table class="aweber-forms-table">\
							<thead>\
							 	<tr>\
							    	<th>Name</th>\
							    	<th>Tags</th>\
							    	<th>Probability</th>\
							    	<th>Displays</th>\
							    	<th>Subscribers</th>\
							    	<th>S/D</th>\
							    	<th>Unique Displays</th>\
							    	<th>S/UD</th>\
							 	</tr>\
							</thead>\
							<tbody>';
				jQuery.each(value.webform_split_tests, function(k, v) {
					web_forms += '<tr><td>' + v.sign_up_form + '</td>\
						<td>' + (v.tags || '-') + '</td>\
						<td>' + v.probability + '</td>\
						<td>' + v.displays + '</td>\
						<td>' + v.subscribers + '</td>\
						<td>' + v.s_d + '</td>\
						<td>' + v.unique_displays + '</td>\
						<td>' + v.s_ud + '</td>\
					</tr>';
				});
				web_forms += '</tbody></table>';
			});
		} else if (record_type == 'landing_pages') {
			web_forms += '<table id="landing-pages" class="aweber-forms-table">\
					<thead>\
					 	<tr>\
					    	<th>Name</th>\
					    	<th>Publish Date</th>\
					    	<th>Link/Resync Date</th>\
					    	<th>Page Title</th>\
					    	<th>Page Path</th>\
					    	<th>Action</th>\
					 	</tr>\
					</thead>\
					<tbody>';
			jQuery.each(response.data, function(key, value) {
				link_btn = '';
				sync_btn = '';
				unlink_btn = '';
				if (value.post_id == 0) {
					link_btn = '<button class="aweber-btn aweber-btn-success aweber-link">Link</button>';
				} else {
					sync_btn = '<button class="aweber-btn aweber-btn-primary aweber-resync">Resync</button>';
					unlink_btn = '<button class="aweber-btn aweber-btn-danger aweber-unlink">Unlink</button>';
				}
				path = value.page_path;
				if (value.page_path != '-') {
					path = '<a target="_blank" href="'+value.page_link+'">' + value.page_path + '</a>';
				}
				web_forms += '<tr data-post_id="' + value.post_id + '" data-landing_page_id="' + value.id + '">\
			   		<td>' + value.name + ' <a target="_blank" class="aweber-preview-form-link" href="'+value.preview+'">(Preview)</a></td>\
			   		<td>' + value.published_date +'</td>\
			   		<td>' + value.link_date + '</td>\
					<td>' + value.page_title + '</td>\
					<td>' + path + '</td>\
					<td class="aw-btn-group">' + link_btn + sync_btn + unlink_btn + '</td>\
				</tr>';
			});
			web_forms += '</tbody></table>';
		}
		web_forms += '</div>';
		jQuery(selector).html(web_forms);
	}).fail(function(data) {
		jQuery(selector).html('<p>An unexpected error occured. Please check your internet connection and try again</p>');
	});
});

function populate_pages() {
	jQuery.getJSON(ajaxurl, {'action': 'get_wordpress_pages', 'nonce': php_vars.aweber_nonce.get_wordpress_pages}, function(response){
		pages = '';
		jQuery.each(response.pages, function(key, value) {
			link_btn = 'Already Linked';
			if (value.linked == '0') {
				link_btn = '<button data-post_id="'+value.post_id+'" \
								class="aweber-btn aweber-btn-success aweber-link-page">Link this page</button>';
			}

			pages += '<tr>\
		   		<td>' + value.name + '</td>\
		   		<td>' + value.path +'</td>\
				<td class="aw-btn-group">' + link_btn + '</td>\
			</tr>';
		});
		if (response.pages.length == 0) {
			pages = '<tr><td colspan="3">No Wordpress pages found.</td></tr>';
		}
		jQuery('#show-wordpress-pages .aweber-forms-table').find('tbody').html(pages);
	});
}

jQuery(document).on('click', '.aweber-link', function() {
	jQuery('#show-wordpress-pages').show();
	let landing_page_id = jQuery(this).parents('tr').attr('data-landing_page_id');
	let $td = jQuery(this).parent();
	let $tr = jQuery(this).parents('tr');

	jQuery('.aweber-create-page').removeAttr('disabled');
	jQuery('.aweber-create-page').text('Create New Page and Link');
	jQuery('.aweber-form-text').val('');

	jQuery('#landing_page_id').val(landing_page_id);
	jQuery('#landing_page_id').attr('data-tr', $tr.index());

	pages = '<tr><td colspan="3">Fetching wordpress pages...</td></tr>';
	jQuery('#show-wordpress-pages .aweber-forms-table').find('tbody').html(pages);

	populate_pages();

	jQuery(document).off('click', '.aweber-link-page');
	jQuery(document).on('click', '.aweber-link-page', function() {
		let post_id = jQuery(this).attr('data-post_id');
		let $this = jQuery(this).parent();
		let $btn = jQuery(this);

		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: 'json',
			data: {
				'action': 'aweber_link_page',
				'post_id': post_id,
				'landing_page_id': landing_page_id,
        'nonce': php_vars.aweber_nonce.aweber_link_page
			},
			beforeSend: function() {
				$btn.text('Linking...');
				$btn.attr('disabled', 'disabled');
			},
			success: function(data){
				if (data.status == 'success') {
					jQuery('#show-wordpress-pages').hide();

					link_btn = '\
						<button class="aweber-btn aweber-btn-primary aweber-resync">Resync</button>\
						<button class="aweber-btn aweber-btn-danger aweber-unlink">Unlink</button>';
					$td.html(link_btn);
					path = '<a target="_blank" href="'+data.page.page_link+'">' + data.page.page_path + '</a>';

					$tr.find("td:eq(2)").html(data.page.synced_on);
					$tr.find("td:eq(3)").html(data.page.page_title);
					$tr.find("td:eq(4)").html(path);
					$tr.attr('data-post_id', data.page.post_id);
				} else {
					$td.html(data.message);
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$td.find("td:eq(5)").html("An unexpected error occured. Please check your\
			 		internet connection and try again");
			}
		});
	});
});

jQuery(document).on('click', '.aweber-unlink', function() {
	let $td = jQuery(this).parent();
	let $tr = jQuery(this).parents('tr');
	let $btn = jQuery(this);

	let landing_page_id = $tr.attr('data-landing_page_id');
	let post_id = $tr.attr('data-post_id');

	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: {
			'action': 'aweber_unlink_page',
			'post_id': post_id,
			'landing_page_id': landing_page_id,
      'nonce': php_vars.aweber_nonce.aweber_unlink_page
		},
		beforeSend: function() {
			$btn.attr('disabled', 'disabled');
		},
 		success: function(data){
			link_btn = '<button class="aweber-btn aweber-btn-success aweber-link">Link</button>';
			$td.html(link_btn);
			$tr.find("td:eq(2)").html('NA');
			$tr.find("td:eq(3)").html('-');
			$tr.find("td:eq(4)").html('-');
			$tr.attr('data-post_id', 0);
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			$tr.find("td:eq(5)").html("An unexpected error occured. Please check your\
			 		internet connection and try again");
		}
	});
});

jQuery(document).on('click', '.aweber-resync', function() {
	let $td = jQuery(this).parent();
	let $tr = jQuery(this).parents('tr');
	let $btn = jQuery(this);

	let landing_page_id = $tr.attr('data-landing_page_id');
	let post_id = $tr.attr('data-post_id');

	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: 'json',
		data: {
			'action': 'aweber_link_page',
			'post_id': post_id,
			'landing_page_id': landing_page_id,
      'nonce': php_vars.aweber_nonce.aweber_link_page
		},
		beforeSend: function() {
			$btn.text('Resyncing...');
			$btn.attr('disabled', 'disabled');
		},
		success: function(data){
			$btn.removeAttr('disabled');
			$btn.text('Resync');

			$tr.find("td:eq(2)").html(data.page.synced_on);
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			$tr.find("td:eq(5)").html("An unexpected error occured. Please check your\
			 		internet connection and try again");
		}
	});
});

jQuery(document).on('keyup', '.aweber-form-text', function (e) {
	if (e.keyCode === 13) {
		jQuery('.aweber-create-page').click();
	}
});

// Auto-popoulate the Page Path field
// Cast to lowercase and replace spaces w/ dashes
jQuery(document).on('keyup', '.aweber-page-name', function (e) {
	let page_name = jQuery('.aweber-page-name').val();
	let page_path = page_name.toLowerCase().replace(/ /g, '-');
	jQuery('.aweber-page-path').val(page_path);
});

jQuery(document).on('click', '.aweber-create-page', function() {
	let page_name = jQuery('.aweber-page-name').val();
	let page_path = jQuery('.aweber-page-path').val();
	let $btn = jQuery(this);

	if (page_name.trim().length == 0) {
		jQuery('.aweber-page-name').css({'border': '1px solid rgb(228, 29, 29)'});
		return false;
	} else {
		jQuery('.aweber-page-name').removeAttr('style');
	}

	if (page_path.trim().length == 0) {
		jQuery('.aweber-page-path').css({'border': '1px solid rgb(228, 29, 29)'});
		return false;
	} else {
		jQuery('.aweber-page-path').removeAttr('style');
	}

	data = {
		'action': 'aweber_create_page',
		'landing_page_id': jQuery('#landing_page_id').val(),
		'page_name': page_name,
		'page_path': page_path,
    'nonce': php_vars.aweber_nonce.aweber_create_page
	};
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: 'json',
		data: data,
		beforeSend: function() {
			$btn.text('Creating page and linking...');
			$btn.attr('disabled', 'disabled');
		},
		success: function(data){
			if (data.status == 'success') {
				jQuery('#show-wordpress-pages').hide();
				let tr_count = jQuery('#landing_page_id').attr('data-tr');
				$tr = jQuery('#landing-pages tbody tr').eq(tr_count);

				link_btn = '\
					<button class="aweber-btn aweber-btn-primary aweber-resync">Resync</button>\
					<button class="aweber-btn aweber-btn-danger aweber-unlink">Unlink</button>';
				path = '<a target="_blank" href="'+data.page.page_link+'">' + data.page.page_path + '</a>';
				$tr.find("td:eq(5)").html(link_btn);
				$tr.find("td:eq(2)").html(data.page.synced_on);
				$tr.find("td:eq(3)").html(data.page.page_title);
				$tr.find("td:eq(4)").html(path);
				$tr.attr('data-post_id', data.page.post_id);
			} else {
				jQuery('<p>'+data.message+'</p>').insertAfter('#wordpress-create-page');
			}

			$btn.removeAttr('disabled');
			$btn.text('Create New Page and Link');
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			jQuery('<p>An unexpected error occured. Please check your\
			 		internet connection and try again</p>').insertAfter('#wordpress-create-page');

			$btn.removeAttr('disabled');
			$btn.text('Create New Page and Link');
		}
	});
});

jQuery(document).on('click', '.view-all-locations', function() {
	jQuery('#show-form-locations').show();

	var location = JSON.parse(jQuery(this).attr('data-locations'));
	var locations = '<p><b>Name:</b> '+jQuery(this).attr('data-form-name') +'</p>';
	locations += '<table class="aweber-forms-table">\
					<thead>\
					 	<tr>\
					    	<th>Type</th>\
					    	<th>Area/Title</th>\
					    	<th>Details</th>\
					 	</tr>\
					</thead>\
					<tbody>';
				jQuery.each(location, function(key, value) {
					if (typeof value.type !== 'undefined') {
						locations += '<tr><td><b>' + value.type + '</b></td>\
							<td>' + value.title + '</td>\
							<td>'+ (value.link ? '<a href="' + value.link + '" target="_blank">' + (value.type == 'Post' ? 'View Post': 'View Page') +'</a>' : 'None') +'</td>\
						</tr>';
					}
				});
	locations += '</tbody></html>';
	jQuery('#show-form-locations').find('.aweber-modal-body').html(locations);
});

jQuery(document).on('click', '.aweber-dismiss-modal', function() {
	jQuery('.aweber-modal').hide();
});

// Scripts used in the Settings page:
jQuery(document).on('click', '.aweber-remove-connection-link', function(){
	jQuery('#show-remove-connection').show();
});

jQuery(document).on('change', '#aweber-create-subscriber-comment-checkbox', function() {
	jQuery('.no-list-selected').addClass('aweber-hide');

    if (jQuery(this).is(':checked')) {
        jQuery('#aweber-settings-hidden-comment-checkbox-value').val('ON');
        jQuery('#aweber-create-subscriber-comment-config').show();
    } else {
        jQuery('#aweber-settings-hidden-comment-checkbox-value').val('OFF');
        jQuery('#aweber-create-subscriber-comment-config').hide();
    }
});

jQuery(document).on('change', '#aweber-create-subscriber-registration-checkbox', function() {
	jQuery('.no-list-selected').addClass('aweber-hide');

    if (jQuery(this).is(':checked')) {
        jQuery('#aweber-settings-hidden-registration-checkbox-value').val('ON');
        jQuery('#aweber-create-subscriber-registration-config').show();
    } else {
        jQuery('#aweber-settings-hidden-registration-checkbox-value').val('OFF');
        jQuery('#aweber-create-subscriber-registration-config').hide();
    }
});

jQuery(document).on('change', '#aweber-add-analytics-checkbox', function() {
    if (jQuery(this).is(':checked')) {
        jQuery('#aweber-settings-hidden-analytics-checkbox-toggle').val('ON');
    } else {
        jQuery('#aweber-settings-hidden-analytics-checkbox-toggle').val('OFF');
    }
});

function attachErrorMessage(to, message) {
    if (jQuery(to).next('.aweber-error-message').length == 0) {
        jQuery('<span class="aweber-error-message">' + message + '</span>').insertAfter(to);

        setTimeout(function() {
            jQuery(to).next('.aweber-error-message').remove();
        }, 5000);
    }
}
jQuery(document).on('submit', '.aweber-advance-option-form', function(){
    jQuery('.list-changes-saved').hide();

    if(jQuery('#show-remove-connection').is(':visible')) {
    	// Remove the connection.
    	return true;
    }

    if (jQuery('#connect').hasClass('active')) {
        if (jQuery('#aweber-settings-hidden-value').length == 0) {
            if (jQuery.trim(jQuery('[name="aweber_webform_oauth_id"][type="text"]').val()) == '') {
                jQuery('[name="aweber_webform_oauth_id"][type="text"]').next('.aweber-error-message').remove();
                attachErrorMessage('[name="aweber_webform_oauth_id"][type="text"]', 'Please enter the authorization code');
                return false;
            }
        }
    } else if (jQuery('#advance_opt').hasClass('active')) {
        jQuery('#aweber-settings-hidden-value').val('FALSE');

        var comment_list = jQuery('.AWeberWebformPluginWidgetOptions-comment-list').val();
        var register_list = jQuery('.AWeberWebformPluginWidgetOptions-register-list').val();

        var register_checkbox = jQuery('#aweber-create-subscriber-comment-checkbox').is(':checked');
        var comment_checkbox = jQuery('#aweber-create-subscriber-registration-checkbox').is(':checked');

        // Validation.
        if (jQuery('#aweber-create-subscriber-comment-checkbox').is(':checked')) {
            if (comment_list == 'FALSE') {
                attachErrorMessage('.AWeberWebformPluginWidgetOptions-comment-list', 'Please select the subscriber list.');
                return false;
            }

            if (jQuery('#aweber-comment-subscriber-input').val().trim().length < 7) {
                attachErrorMessage('#aweber-comment-subscriber-input', 'The subscriber label was too short. Please make sure it is at least 7 characters.');
                return false;
            }

            jQuery('#aweber-settings-hidden-comment-subscriber-listid').val(comment_list);
            jQuery('#aweber-settings-hidden-comment-tags').val(jQuery('#aweber-comment-subscriber-tags-input').val());
            jQuery('#aweber-settings-hidden-comment-text-value').val(jQuery('#aweber-comment-subscriber-input').val());
        } else {
            jQuery('#aweber-settings-hidden-comment-subscriber-listid').val('FALSE');
            jQuery('#aweber-settings-hidden-comment-tags').val('');
            jQuery('#aweber-settings-hidden-comment-text-value').val('');
        }

        if (jQuery('#aweber-create-subscriber-registration-checkbox').is(':checked')) {
            if (register_list == 'FALSE') {
                attachErrorMessage('.AWeberWebformPluginWidgetOptions-register-list', 'Please select the subscriber list.');
                return false;
            }

            if (jQuery('#aweber-register-subscriber-input').val().trim().length < 7) {
                attachErrorMessage('#aweber-register-subscriber-input', 'The subscriber label was too short. Please make sure it is at least 7 characters.');
                return false;
            }

            jQuery('#aweber-settings-hidden-register-subscriber-listid').val(register_list);
            jQuery('#aweber-settings-hidden-register-tags').val(jQuery('#aweber-register-subscriber-tags-input').val());
            jQuery('#aweber-settings-hidden-register-text-value').val(jQuery('#aweber-register-subscriber-input').val());
        } else {
            jQuery('#aweber-settings-hidden-register-subscriber-listid').val('FALSE');
            jQuery('#aweber-settings-hidden-register-tags').val('');
            jQuery('#aweber-settings-hidden-register-text-value').val('');
        }

        // Set the Web-Push Notification value.
        var wpn_list = jQuery('.aweber-wpn-select-list').val();
        if (typeof wpn_list!== 'undefined') {
        	jQuery('#aweber-settings-hidden-web-push-listid').val(wpn_list);
        }
    }
});

// javascript TAB toggle.
jQuery(document).on('click', '.list-inline a', function(){
    var toggle_element = jQuery(this).attr('data-toggle');

    jQuery('.list-inline').find('.active').removeClass('active');
    jQuery(this).parent().addClass('active');

    jQuery('.tab-content').find('.tab-pane').removeClass('active');
    jQuery('.tab-content').find(toggle_element).addClass('active');

    return false;
});

// Scripts used in the System Info Tab.
jQuery(document).on('click', '#aweber-webform-reload-cache', function(){
	jQuery('.plugin-message').html('');

	var $btn = jQuery(this);
	var widget_name = $btn.attr('data-widget-name');
	var data = {
		'action': 'reload_aweber_cache',
    'nonce': php_vars.aweber_nonce.reload_aweber_cache,
	};
	data[widget_name] = '';

	$btn.html('Reloading');
	$btn.attr('disabled', 'disabled');

	jQuery.getJSON(ajaxurl, data, function(response){
		$btn.removeAttr('disabled');
		$btn.html('Reload Cache');

		if (response.status == 'success') {
			jQuery('.aweber-alert-positive').find('p').html(response.message);
			jQuery('.aweber-alert-positive').removeClass('aweber-hide');
		} else {
			jQuery('.aweber-alert-negative').find('p').html(response.message);
			jQuery('.aweber-alert-negative').removeClass('aweber-hide');
		}
	}).fail(function(data) {
		$btn.removeAttr('disabled');
		$btn.html('Reload Cache');

		jQuery('.aweber-alert-negative').find('p').html('An unexpected error occured. Please check your internet connection and try again');
		jQuery('.aweber-alert-negative').removeClass('aweber-hide');
	});
});
