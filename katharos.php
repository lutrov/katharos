<?php

/*
Plugin Name: Katharos
Description: Reduces the amount of bandwidth your site and your visitor uses by using sophisticated output buffering techniques to clean and compress your site's webpage content before it gets sent to the user's browser. Why this plugin name? Katharos means "pure" in Greek.
Author: Ivan Lutrov
Author URI: http://lutrov.com/
Version: 3.0
Notes: This plugin provides an API to customise the default constant values. See the "readme.md" file for more.
*/

defined('ABSPATH') || die('Ahem.');

//
// Constants used by this plugin.
//
define('KATHAROS_COMPRESS_OUTPUT_BUFFER', true);
define('KATHAROS_OBFUSCATE_WORDPRESS_URLS', true);
define('KATHAROS_REMOVE_DUBYA_DUBYA_DUBYA_FROM_URLS', true);
define('KATHAROS_REMOVE_SCHEME_FROM_URLS', true);
define('KATHAROS_REMOVE_SERVER_NAME_FROM_URLS', true);
define('KATHAROS_STRING_REPLACEMENTS_FROM', "WordPress|StudioPress|BuddyPress|CustomPress|MarketPress|bbPress|WooCommerce|WooThemes|eNews|LayerSlider|MailPoet|NextGEN|Howdy|E-commerce|e-commerce|eCommerce|E-mail|e-mail|eBay|PayPal|cPanel|eWAY|EasyCart|WooSwipe");
define('KATHAROS_STRING_REPLACEMENTS_TO', "Wordpress|Studiopress|Buddypress|Custompress|Marketpress|Bbpress|Woocommerce|Woothemes|Enews|Layerslider|Mailpoet|Nextgen|G'day|Ecommerce|ecommerce|Ecommerce|Email|email|Ebay|Paypal|Cpanel|Eway|Easycart|Wooswipe");

//
// Don't touch these or the sky will fall.
//
define('KATHAROS_IS_MULTISITE', is_multisite());

//
//  Output buffering functions.
//
function katharos_buffer_callback($html) {
	$from = apply_filters('katharos_string_replacements_from_filter', KATHAROS_STRING_REPLACEMENTS_FROM);
	$to = apply_filters('katharos_string_replacements_to_filter', KATHAROS_STRING_REPLACEMENTS_TO);
	if (strlen($from) > 0 && strlen($to) > 0) {
		$regex = explode('|', $from);
		for ($i = 0, $c = count($regex); $i < $c; $i++) {
			$regex[$i] = '#\b' . $regex[$i] . '\b#';
		}
		$html = preg_replace($regex, explode('|', $to), $html);
		$html = str_replace(':</label>', '</label>', $html);
	}
	if (is_admin() == false) {
		if (apply_filters('katharos_compress_output_buffer_filter', KATHAROS_COMPRESS_OUTPUT_BUFFER) == true) {
			$temp = array();
			if (preg_match_all('#<pre(.*)>(.*)</pre>#is', $html, $matches) > 0) {
				for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
					$code = $matches[0][$i];
					$hash = hash('md5', $code);
					$temp[$hash] = trim($code);
					$html = str_replace($code, '[[' . $hash . ']]', $html);
				}
			}
			if (preg_match_all('#<textarea(.*)>(.*)</textarea>#is', $html, $matches) > 0) {
				for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
					$code = $matches[0][$i];
					$hash = hash('md5', $code);
					$temp[$hash] = trim($code);
					$html = str_replace($code, '[[' . $hash . ']]', $html);
				}
			}
		}
		if (apply_filters('katharos_obfuscate_wordpress_urls_filter', KATHAROS_OBFUSCATE_WORDPRESS_URLS) == true) {
			if (KATHAROS_IS_MULTISITE == false) {
				$html = str_replace(array('/wp-includes/', '/wp-content/plugins/', '/wp-content/themes/', '/wp-content/uploads/'), array('/lib/', '/assets/plugins/', '/assets/themes/', '/assets/uploads/'), $html);
			}
		}
		if (apply_filters('katharos_remove_server_name_from_urls_filter', KATHAROS_REMOVE_SERVER_NAME_FROM_URLS) == true) {
			$scheme = 'http';
			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
				$scheme = 'https';
			}
			$html = str_replace($scheme . '://' . $_SERVER['SERVER_NAME'], null, $html);
		}
		if (apply_filters('katharos_remove_scheme_from_urls_filter', KATHAROS_REMOVE_SCHEME_FROM_URLS) == true) {
			$html = preg_replace('#https?://#', '//', $html);
		}
		if (apply_filters('katharos_remove_dubya_dubya_dubya_from_urls_filter', KATHAROS_REMOVE_DUBYA_DUBYA_DUBYA_FROM_URLS) == true) {
			$html = str_replace('//www.', '//', $html);
		}
		if (apply_filters('katharos_compress_output_buffer_filter', KATHAROS_COMPRESS_OUTPUT_BUFFER) == true) {
			$html = preg_replace(array('#[\x09]#Uis', '#[\x0D]#Uis', '#[\x0A]#Uis', '#<!--[\s]+(.*)[\s]+-->#Uis'), array('<!--TAB-->', '<!--CR-->', '<!--LF-->', null), $html);
			if (count($temp) > 0) {
				foreach ($temp as $hash => $code) {
					$html = str_replace('[[' . $hash . ']]', $code, $html);
				}
			}
			$html = preg_replace('#<!--(.*)-->#Uis', null, $html);
		}
	}
	return trim($html);
}

//
// Start buffering with output callback.
//
function katharos_buffer_start() {
	ob_start('katharos_buffer_callback');
}

//
// Stop buffering and flush.
//
function katharos_buffer_stop() {
	while (ob_get_level() > 0) {
		ob_end_flush();
	}
}

//
// Hook buffering functions into the WP core system.
//
if (preg_match('#/sitemap\.xml$#', $_SERVER['REQUEST_URI']) == 0) {
	add_action('init', 'katharos_buffer_start', 0);
	add_action('shutdown', 'katharos_buffer_stop', 999);
}

//
// Write .htaccess file.
//
function katharos_htaccess_file($action) {
	if (is_writable(ABSPATH . '.htaccess')) {
		$content = file_get_contents(ABSPATH . '.htaccess');
		$rules = katharos_htaccess_rules();
		if (strlen($rules) > 0) {
			$temp = '[[' . hash('sha1', $rules) . ']]';
			$content = preg_replace('/# BEGIN Katharos Obfuscation(.+)# END Katharos Obfuscation/isU', $temp, $content, -1, $count);
			switch ($action) {
				case 'install':
					if ($count > 0) {
						$content = trim(str_replace($temp, $rules, $content));
					} else {
						$content = $rules . PHP_EOL . trim($content);
					}
					break;
				case 'uninstall':
					if ($count > 0) {
						$content = trim(str_replace($temp, null, $content)) . PHP_EOL;
					}
					break;
			}
			return file_put_contents(ABSPATH . '.htaccess', $content);
		}
	}
}

//
// Build .htaccess rules.
//
function katharos_htaccess_rules() {
	$base = home_url('/');
	if ($x = strpos($base, '//')) {
		$base = str_replace($_SERVER['SERVER_NAME'], null, substr($base, $x + 2));
		$result  = '# BEGIN Katharos Obfuscation' . PHP_EOL;
		$result .= '<IfModule mod_rewrite.c>' . PHP_EOL;
		$result .= 'RewriteEngine On' . PHP_EOL;
		$result .= 'RewriteBase ' . $base . PHP_EOL;
		$result .= 'RewriteRule ^lib/(.+)$ wp-includes/$1 [QSA,L]' . PHP_EOL;
		$result .= 'RewriteRule ^assets/plugins/(.+)$ wp-content/plugins/$1 [QSA,L]' . PHP_EOL;
		$result .= 'RewriteRule ^assets/themes/(.+)$ wp-content/themes/$1 [QSA,L]' . PHP_EOL;
		$result .= 'RewriteRule ^assets/uploads/(.+)$ wp-content/uploads/$1 [QSA,L]' . PHP_EOL;
		$result .= '</IfModule>' . PHP_EOL;
		$result .= '# END Katharos Obfuscation' . PHP_EOL;
		return $result;
	}
}

//
// Activation hook.
//
function katharos_activate() {
	katharos_htaccess_file('install');
}

//
// Deactivation hook.
//
function katharos_deactivate() {
	katharos_htaccess_file('uninstall');
}

//
// Register activation and deactivation hooks.
//
if (apply_filters('katharos_obfuscate_wordpress_urls_filter', KATHAROS_OBFUSCATE_WORDPRESS_URLS) == true) {
	if (KATHAROS_IS_MULTISITE == false) {
		register_deactivation_hook(__FILE__, 'katharos_deactivate');
		register_activation_hook(__FILE__, 'katharos_activate');
	}
}

?>
