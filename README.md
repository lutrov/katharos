# Katharos

__Note: This plugin is probably not compatible with Wordpress 5.__

Reduces the amount of bandwidth your site and your visitor uses by using sophisticated output buffering techniques to clean and compress your site's webpage content before it gets sent to the user's browser. Why this plugin name? Katharos means "pure" in Greek.

## Copyright and License

This project is licensed under the [GNU GPL](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html), version 2 or later.

## Documentation

This plugin provides an API to to customise the default values. See these examples:

	// ---- Change the Katharos plugin compress output buffering value to false.
	add_filter('katharos_compress_output_buffer', '__return_false');

	// ---- Change the Katharos plugin obfuscate Wordpress URLs value to false.
	add_filter('katharos_obfuscate_wordpress_urls', '__return_false');

	// ---- Change the Katharos plugin remove WWW from URLs value to false.
	add_filter('katharos_remove_dubya_dubya_dubya_from_urls', '__return_false');

	// ---- Change the Katharos plugin remove HTTP scheme value to false.
	add_filter('katharos_remove_scheme_from_urls', '__return_false');

	// ---- Change the Katharos plugin remove server name value to false.
	add_filter('katharos_remove_server_name_from_urls', '__return_false');

	// ---- Change the Katharos plugin replacement strings.
	add_filter('katharos_replacement_strings_array', 'lutrov_katharos_replacement_strings_array_filter');
	function lutrov_katharos_replacement_strings_array_filter($array) {
		return array(
			'WooCommerce' => 'Woocommerce',
			'WordPress' => 'Wordpress',
			'Howdy' => 'Hello',
			'AdWords' => 'Adwords'
		);
	}

For the two string replacement filters above, remember to escape "\", "^", ".", "$", "|", "(", ")", "[", "]", "*", "+", "?", "{", "}" and "," if you're matching any of those characters.

## Professional Support

If you need professional plugin support from me, the plugin author, contact me via my website at http://lutrov.com
