<?php

/**
 * Settings:
 *
 * Add main menu settings page for CardlessPay
 */
function cardless_add_admin_menu()
{

	add_menu_page('CardlessPay Settings', 'CardlessPay', 'manage_options', 'CardlessPay_payment_gateway', 'cardless_options_page');
}

/**
 * Settings:
 *
 * Initialize CardlessPay settings and values
 */
function cardless_settings_init()
{

	register_setting('cardless_register_setting', 'cardless_settings');

	add_settings_section(
		'cardless_settings_main_page',
		__("Settings:", 'woocommerce-gateway-cardless-money'),
		'cardless_settings_section_callback',
		'cardless_register_setting'
	);

	add_settings_field(
		'cardless_is_enabled',
		__('Enable / Disable Gateway', 'woocommerce-gateway-cardless-money'),
		'cardless_is_enabled_render',
		'cardless_register_setting',
		'cardless_settings_main_page'
	);

	add_settings_field(
		'cardless_token',
		__('Token', 'woocommerce-gateway-cardless-money'),
		'cardless_token_render',
		'cardless_register_setting',
		'cardless_settings_main_page'
	);

	add_settings_field(
		'cardless_title',
		__('Title', 'woocommerce-gateway-cardless-money'),
		'cardless_title_render',
		'cardless_register_setting',
		'cardless_settings_main_page'
	);

	add_settings_field(
		'cardless_gateway_description',
		__('Description', 'woocommerce-gateway-cardless-money'),
		'cardless_gateway_description_render',
		'cardless_register_setting',
		'cardless_settings_main_page'
	);

	// add_settings_field(
	// 	'cardless_debug_log',
	// 	__('Debug Log', 'woocommerce-gateway-cardless-money'),
	// 	'cardless_debug_log_render',
	// 	'cardless_register_setting',
	// 	'cardless_settings_main_page'
	// );

	add_settings_field(
		'cardless_api_endpoint',
		__('', 'woocommerce-gateway-cardless-money'),
		'cardless_api_endpoint_render',
		'cardless_register_setting',
		'cardless_settings_main_page'
	);
}

/**
 * Settings:
 *
 * Render the Enable / Disable Gateway checkbox in CardlessPay settings page
 */
function cardless_is_enabled_render()
{

	$options = get_option('cardless_settings');

	//print_r($options);

?>
<input type='checkbox' name='cardless_settings[cardless_is_enabled]' <?php if (isset($options['cardless_is_enabled'])) {
																				checked($options['cardless_is_enabled'], 1);
																			} else {
																				checked(0, 1);
																			}; ?> value='1'>
<?php

}

/**
 * Settings:
 *
 * Render the API select field in CardlessPay settings page
 */
function cardless_api_endpoint_render()
{

	$options = get_option('cardless_settings');

	//print_r($options);

	if ($options['cardless_api_endpoint']) { //Display current choice first in selector
		if ($options['cardless_api_endpoint'] == 'https://www.cardlesspaytech.com/api/v1/create_check') {
	?>
<select name='cardless_settings[cardless_api_endpoint]' style="display:none">
    <option value='https://www.cardlesspaytech.com/api/v1/create_check'
        <?php selected($options['cardless_api_endpoint'], 1); ?>>
        Live</option>
</select>
<?php
		} else {
		?>
<select name='cardless_settings[cardless_api_endpoint]' style="display:none">
    <option value='https://www.cardlesspaytech.com/api/v1/create_check'
        <?php selected($options['cardless_api_endpoint'], 2); ?>>
        Live</option>
</select>
<?php
		}
	} else {

		?>
<select name='cardless_settings[cardless_api_endpoint]' style="display:none">
    <option value='https://www.cardlesspaytech.com/api/v1/create_check'
        <?php selected($options['cardless_api_endpoint'], 1); ?>>
        Live</option>
</select>
<?php

	}
}

/**
 * Settings:
 *
 * Render the Client ID field in CardlessPay settings page
 */
function cardless_token_render()
{

	$options = get_option('cardless_settings');
	?>
<input type='text' name='cardless_settings[cardless_token]' value='<?php echo $options['cardless_token']; ?>'>
<?php

}


/**
 * Settings:
 *
 * Render the Title field in CardlessPay settings page
 */
function cardless_title_render()
{

	$options = get_option('cardless_settings');
?>
<input type='text' name='cardless_settings[cardless_title]' value='<?php echo $options['cardless_title']; ?>'>
<?php

}

/**
 * Settings:
 *
 * Render the Description field in CardlessPay settings page
 */
function cardless_gateway_description_render()
{

	$options = get_option('cardless_settings');
?>
<textarea cols='40' rows='5' name='cardless_settings[cardless_gateway_description]'><?php
																						echo trim($options['cardless_gateway_description']);
																						?></textarea>
<?php

}

/**
 * Settings:
 *
 * Render the Debug log checkbox in CardlessPay settings page
 */
function cardless_debug_log_render()
{

	$options = get_option('cardless_settings');
?>
<input type='checkbox' name='cardless_settings[cardless_debug_log]' <?php if (isset($options['cardless_debug_log'])) {
																			checked($options['cardless_debug_log'], 1);
																		} else {
																			checked(0, 1);
																		}; ?> value='1'>
<?php

}




/**
 * Settings:
 *
 * Main function to inject settings into wordpress admin menu
 */
function cardless_options_page()
{

?>
<form action='options.php' method='post'>

    <h1>CardlessPay by Cardless Paytech</h1>
    <?php
		$options = get_option('cardless_settings');
		$options['cardless_api_endpoint'] = 'https://www.cardlesspaytech.com/api/v1/create_check';

		settings_fields('cardless_register_setting');
		do_settings_sections('cardless_register_setting');
		submit_button('Save Changes', 'primary', 'cardless_submit');
		?>

</form>
<?php

}
add_action('admin_menu', 'cardless_add_admin_menu');
add_action('admin_init', 'cardless_settings_init');

?>
