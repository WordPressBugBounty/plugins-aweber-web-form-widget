<?php
    use AWeberWebFormPluginNamespace as AWeberWebformPluginAlias;

    $pluginAdminOptions = get_option($this->adminOptionsName);
    $oauth2TokensOptions = get_option($this->oauth2TokensOptions);
    $options = get_option($this->widgetOptionsName);
?>

<?php if($this->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)): ?>
	<div class="aweber-wrapper">
		<div class="aweber-body-wrapper">
			<?php $this->add_alert_message_html('negative', '', 'aweber-hide aweber-forms-error-message'); ?>

			<div class="aweber-body">
				<h1 class="aweber-header-bar">Sign Up Forms</h1>

				<div class="list-dropdown">
					<label>List: </label>
					<select data-selected="<?php echo $options['selected_signup_form_list_id']; ?>" class="<?php echo $this->widgetOptionsName; ?>-list" data-type="sigup-form" name="<?php echo $this->widgetOptionsName; ?>[list]" id="<?php echo $this->widgetOptionsName; ?>-list">
                        <option value="False">Loading AWeber lists</option>
					</select>
					<a type="button" href="https://www.aweber.com/users/web_forms/edit/" target="_blank" class="aweber-btn aweber-btn-success aweber-marginl10">Create</a>
				</div>

				<div class="signup-webform-list">
                    <p>Please wait while the lists from your AWeber account are retrieved.</p>
                </div>
			</div>
		</div>

		<div class="aweber-body-wrapper">
			<div class="aweber-body">
				<h1 class="aweber-header-bar">Split Tests</h1>
				<div class="list-dropdown">
					<label>List: </label>
					<select data-selected="<?php echo $options['selected_split_test_form_list_id']; ?>" class="<?php echo $this->widgetOptionsName; ?>-list" data-type="split-test"
					name="<?php echo $this->widgetOptionsName; ?>[list]" id="<?php echo $this->widgetOptionsName; ?>-list">
						<option value="False">Loading AWeber lists</option>
					</select>
					<a type="button" href="https://www.aweber.com/users/web_form_splits/create/" target="_blank" class="aweber-btn aweber-btn-success aweber-marginl10">Create</a>
				</div>

				<div class="split-webform-list">
                    <p>Please wait while the lists from your AWeber account are retrieved.</p>
                </div>
			</div>
		</div>
	</div>

	<div class="aweber-modal" id="show-form-locations">
		<div class="aweber-modal-content aweber-modal-lg">
			<div class="aweber-modal-header">
				<h1>Sign Up Form Locations</h1>
			</div>
			<div class="aweber-modal-body">
				<p>Modal body</p>
			</div>
			<div class="aweber-modal-footer text--xs-right">
				<button class="aweber-btn aweber-btn-primary aweber-dismiss-modal">Done</button>
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
