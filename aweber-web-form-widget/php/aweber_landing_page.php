<?php
    use AWeberWebFormPluginNamespace as AWeberWebformPluginAlias;

    $pluginAdminOptions = get_option($this->adminOptionsName);
    $options = get_option($this->widgetOptionsName);
    $oauth2TokensOptions = get_option($this->oauth2TokensOptions);
?>

<?php if($this->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)): ?>
	<div class="aweber-wrapper">
		<div class="aweber-body-wrapper">
			<?php $this->add_alert_message_html('negative', '', 'aweber-hide aweber-forms-error-message'); ?>

			<div class="aweber-body">
				<h1 class="aweber-header-bar">Landing Pages</h1>

				<div class="list-dropdown">
					<label>List: </label>
					<select
						data-selected="<?php echo esc_attr($options['selected_landing_page_list_id']); ?>" 
						class="<?php echo esc_attr($this->widgetOptionsName . '-list'); ?>" 
						data-type="landing_pages" 
						name="<?php echo esc_attr($this->widgetOptionsName . '[list]'); ?>" 
						id="<?php echo esc_attr($this->widgetOptionsName . '-list'); ?>">
                        <option value="False">Loading AWeber lists</option>
					</select>
					<a type="button" href="https://www.aweber.com/users/landing_pages/create" target="_blank" class="aweber-btn aweber-btn-success aweber-marginl10">Create</a>
				</div>

				<div class="landing-pages-list">
                    <p>Please wait while the lists from your AWeber account are retrieved.</p>
                </div>
			</div>
		</div>
	</div>

	<div class="aweber-modal" id="show-wordpress-pages">
		<div class="aweber-modal-content aweber-modal-lg">
			<div class="aweber-modal-header">
				<button type="button" class="close aweber-dismiss-modal">&times;</button>
				<h1>WordPress Pages</h1>
			</div>
			<div class="aweber-modal-body">
				<div class="table-scroll">
					<table class="aweber-forms-table">
						<thead>
							<tr>
								<th>Page Name</th>
								<th>Page Location</th>
								<th></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
				<table id="wordpress-create-page" class="form-table">
                    <tr>
                        <td>
                        	<input type="hidden" value="0" id="landing_page_id">
                            <label>Page Name:</label>
                            <input type="text" name="aweber-page-name" class="aweber-page-name aweber-form-text" />
                        </td>
                        <td>
                            <label>Page Path:</label>
                            <input type="text" name="aweber-page-path" class="aweber-page-path aweber-form-text" />
                        </td>
                        <td>
                        	<br>
                            <button class="aweber-btn aweber-btn-success aweber-create-page" style="padding: 8px 15px; width: 100%">
                            	Create New Page & Link
                        	</button>
                        </td>
                    </tr>
                </table>
			</div>
		</div>
	</div>
<?php else: ?>
    <div class="aweber-wrapper">
        <div class="aweber-body-wrapper">
            <?php $this->add_alert_message_html('negative', 'Please reconnet to AWeber Account'); ?>
        </div>
    </div>
<?php endif; ?>
