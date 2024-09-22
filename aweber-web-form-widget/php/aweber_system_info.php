<?php global $wp_version; 
	use AWeberWebFormPluginNamespace as AWeberWebformPluginAlias;
?>

<style type="text/css">
	.btn {
	    font-weight: 400;
	    text-align: center;
	    vertical-align: middle;
	    border: 1px solid transparent;
	    padding: 5px 10px;
	    font-size: 1rem;
	    line-height: 15px;
	    border-radius: .25rem;
	    cursor: pointer;
	    text-decoration: none;
	}
	.btn-gray {
		color: #fff;
	    background-color: #666967;
	    border-color: #666967;
	}
	.btn:hover {
		color: #fff;
	}
</style>

<?php
	$pluginAdminOptions = get_option($this->adminOptionsName);
	$options = get_option($this->widgetOptionsName);
	$account_id = null;
	
	if ($pluginAdminOptions['access_key']):
		$error_code = '';
		try {
            $aweber = $this->_get_aweber_api($pluginAdminOptions['consumer_key'], $pluginAdminOptions['consumer_secret']);
            $account = $aweber->getAccount($pluginAdminOptions['access_key'], $pluginAdminOptions['access_secret']);

            $account_id = $account->id;
        } catch (AWeberWebformPluginAlias\AWeberException $e) {
			$error_ = get_class($e);
			$error_code  = $e->status;
            $description = $e->getMessage();
            if (stripos($description, 'labs.aweber.com') !== false) {
				$description = preg_replace('/http.*$/i', '', $description);  # strip labs.aweber.com documentation url from error message
            }
			$account = null;
        } catch (AWeberWebformPluginAlias\AWeberAPIException $e){
        	$description = $e->getMessage();
        	$error_code  = $e->status;
            if (stripos($description, 'labs.aweber.com') !== false) {
				$description = preg_replace('/http.*$/i', '', $description);  # strip labs.aweber.com documentation url from error message
            }
            $account = null;
        } catch (AWeberWebformPluginAlias\AWeberOAuthDataMissing $e){
			$account = null;
        } catch (\Exception $exc) {
            $description = $exc->getMessage();
            $account = null;
        }

        if (!$account){
	        if($error_ != null && $error_ != 'AWeberWebFormPluginNamespace\AWeberOAuthException' && $error_ != 'AWeberWebFormPluginNamespace\AWeberOAuthDataMissing') {
	            $this->displayCustomErrorMessages($error_code, $description);
	        } else {
	            $error = True;
	            $this->displayCustomErrorMessages($error_code, $description);
	            $this->deauthorize();
	        }
	    }
    endif;

    $plugin_path = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'aweber.php'));
	$plugin_data = get_plugin_data($plugin_path);
	$plugin_version = $plugin_data['Version'];
?>

<div class="wrap">
	<h2>System Info</h2>

	<p><b>Home URL:</b> <?php
		if (function_exists('get_home_url')):
			echo get_home_url();
		else:
			echo '-';
		endif;
		?> 
	</p>

	<p><b>Site URL:</b> <?php
		if (function_exists('get_site_url')):
			echo get_site_url();
		else:
			echo '-';
		endif;
		?>
	</p>

	<p> <b>Wordpress Version:</b> <?php echo isset($wp_version) ? $wp_version : '-' ?> </p>

	<p> <b>PHP Version:</b> <?php
		if (function_exists('phpversion')):
			echo phpversion();
		else:
			echo '-';
		endif;
		?> 
	</p>

	<p> <b>AWeber Account ID:</b> <?php echo isset($account_id) ? $account_id : 'Not connected to AWeber' ?></p>
	<p> <b>AWeber Plugin Version:</b> <?php echo $plugin_version; ?></p>

	<br>

	<button class="btn btn-gray" type="button" id="aweber-webform-reload-cache">Reload Cache</button>
	<span class="plugin-message" style="margin-left: 15px"></span>

	<script type="text/javascript">
		jQuery('#aweber-webform-reload-cache').click(function(){
			jQuery('.plugin-message').html('');

			var $btn = jQuery(this);
			var data = {
				'action': 'reload_aweber_cache',
				'<?php echo $this->widgetOptionsName ?>': ''
			}

			$btn.html('Reloading');
			$btn.attr('disabled', 'disabled');

			jQuery.getJSON(ajaxurl, data, function(response){
				if (response.status == 'success') {
					$btn.removeAttr('disabled');
					$btn.html('Reload Cache');
				}
				jQuery('.plugin-message').html(response.message);
			});
		});
	</script>
</div>

