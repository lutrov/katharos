# Katharos

Reduces the amount of bandwidth your site and your visitor uses by using sophisticated output buffering techniques to clean and compress your site's webpage content before it gets sent to the user's browser. Why this plugin name? Katharos means "pure" in Greek.

## Professional Support

If you need professional plugin support from me, the plugin author, contact me via my website at http://lutrov.com

## Copyright and License

This project is licensed under the [GNU GPL](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html), version 2 or later.

## Documentation

This plugin provides an API to to customise the default constant values. See these examples:

	// ---- Change the Katharos plugin compress output buffering value to false.
	add_filter('katharos_compress_output_buffer_filter', 'custom_katharos_compress_output_buffer_filter');
	function custom_katharos_compress_output_buffer_filter($value) {
		return false;
	}

	// ---- Change the Katharos plugin obfuscate Wordpress URLs value to false.
	add_filter('katharos_obfuscate_wordpress_urls_filter', 'custom_katharos_obfuscate_wordpress_urls_filter');
	function custom_katharos_obfuscate_wordpress_urls_filter($value) {
		return false;
	}

	// ---- Change the Katharos plugin remove WWW from URLs value to false.
	add_filter('katharos_remove_dubya_dubya_dubya_from_urls_filter', 'custom_katharos_remove_dubya_dubya_dubya_from_urls_filter');
	function custom_katharos_remove_dubya_dubya_dubya_from_urls_filter($value) {
		return false;
	}

	// ---- Change the Katharos plugin remove HTTP scheme value to false.
	add_filter('katharos_remove_scheme_from_urls_filter', 'custom_katharos_remove_scheme_from_urls_filter');
	function custom_katharos_remove_scheme_from_urls_filter($value) {
		return false;
	}

	// ---- Change the Katharos plugin remove server name value to false.
	add_filter('katharos_remove_server_name_from_urls_filter', 'custom_katharos_remove_server_name_from_urls_filter');
	function custom_katharos_remove_server_name_from_urls_filter($value) {
		return false;
	}

	// ---- Change the Katharos plugin replacement strings from value.
	add_filter('katharos_string_replacements_from_filter', 'custom_katharos_string_replacements_from_filter');
	function custom_katharos_string_replacements_from_filter($value) {
		return 'WordPress|WooCommerce';
	}

	// ---- Change the Katharos plugin replacement strings to value.
	add_filter('katharos_string_replacements_to_filter', 'custom_katharos_string_replacements_to_filter');
	function custom_katharos_string_replacements_to_filter($value) {
		return 'WORDPRESS|WOOCOMMERCE';
	}

For the two string replacement filters above, remember to escape "\", "^", ".", "$", "|", "(", ")", "[", "]", "*", "+", "?", "{", "}" and "," if you're matching any of those characters.

Or if you're using a custom site plugin (you should be), do it via the `plugins_loaded` hook instead:

	// ---- Change the Katharos plugin constant values.
	add_action('plugins_loaded', 'custom_katharos_filters');
	function custom_katharos_filters() {
		// Change the compress output buffering value to false.
		add_filter('katharos_compress_output_buffer_filter', 'custom_katharos_compress_output_buffer_filter');
		function custom_katharos_compress_output_buffer_filter($value) {
			return false;
		}
		// Change the obfuscate Wordpress URLs value to false.
		add_filter('katharos_obfuscate_wordpress_urls_filter', 'custom_katharos_obfuscate_wordpress_urls_filter');
		function custom_katharos_obfuscate_wordpress_urls_filter($value) {
			return false;
		}
		// Change the remove WWW from URLs value to false.
		add_filter('katharos_remove_dubya_dubya_dubya_from_urls_filter', 'custom_katharos_remove_dubya_dubya_dubya_from_urls_filter');
		function custom_katharos_remove_dubya_dubya_dubya_from_urls_filter($value) {
			return false;
		}
		// Change the remove HTTP scheme value to false.
		add_filter('katharos_remove_scheme_from_urls_filter', 'custom_katharos_remove_scheme_from_urls_filter');
		function custom_katharos_remove_scheme_from_urls_filter($value) {
			return false;
		}
		// Change the remove server name value to false.
		add_filter('katharos_remove_server_name_from_urls_filter', 'custom_katharos_remove_server_name_from_urls_filter');
		function custom_katharos_remove_server_name_from_urls_filter($value) {
			return false;
		}
		// Change the replacement strings from value.
		add_filter('katharos_string_replacements_from_filter', 'custom_katharos_string_replacements_from_filter');
		function custom_katharos_string_replacements_from_filter($value) {
			return 'WordPress|WooCommerce';
		}
		// Change the replacement strings to value.
		add_filter('katharos_string_replacements_to_filter', 'custom_katharos_string_replacements_to_filter');
		function custom_katharos_string_replacements_to_filter($value) {
			return 'WORDPRESS|WOOCOMMERCE';
		}
	}

Note, this second approach will _not_ work from your theme's `functions.php` file.
