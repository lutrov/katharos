<?php

/*
Plugin Name: Katharos
Description: Reduces the amount of bandwidth your site and your visitor uses by using sophisticated output buffering techniques to clean and compress your site's webpage content before it gets sent to the user's browser. Why this plugin name? Katharos means "pure" in Greek.
Version: 2.2
Author: Ivan Lutrov
Author URI: http://lutrov.com/
Notes: Remember to escape "\", "^", ".", "$", "|", "(", ")", "[", "]", "*", "+", "?", "{", "}" and "," if you're matching any of those characters.
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
define('KATHAROS_STRING_REPLACEMENTS_FROM', "WordPress|StudioPress|BuddyPress|CustomPress|MarketPress|bbPress|WooThemes|WooCommerce|eNews|LayerSlider|MailPoet|NextGEN|Howdy|E-commerce|e-commerce|eCommerce|Email|email|eBay|PayPal|cPanel|eWAY|EasyCart");
define('KATHAROS_STRING_REPLACEMENTS_TO', "Wordpress|Studiopress|Buddypress|Custompress|Marketpress|Bbpress|Woothemes|Woocommerce|Enews|Layerslider|Mailpoet|Nextgen|G'day|Ecommerce|ecommerce|Ecommerce|Email|email|Ebay|Paypal|Cpanel|Eway|Easycart");

//
// Don't touch these or the sky will fall.
//
define('KATHAROS_IS_MULTISITE', is_multisite());

//
//  Output buffering functions.
//
if (function_exists('katharos_buffer_callback') == false) {
	function katharos_buffer_callback($html) {
		if (strlen(KATHAROS_STRING_REPLACEMENTS_FROM) > 0) {
			$regex = explode('|', KATHAROS_STRING_REPLACEMENTS_FROM);
			for ($i = 0, $c = count($regex); $i < $c; $i++) {
				$regex[$i] = '#\b' . $regex[$i] . '\b#';
			}
			$html = preg_replace($regex, explode('|', KATHAROS_STRING_REPLACEMENTS_TO), $html);
			$html = str_replace(':</label>', '</label>', $html);
		}
		if (is_admin() == false) {
			if (KATHAROS_COMPRESS_OUTPUT_BUFFER == true) {
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
			if (KATHAROS_OBFUSCATE_WORDPRESS_URLS == true) {
				if (KATHAROS_IS_MULTISITE == false) {
					$html = str_replace(array('/wp-includes/', '/wp-content/plugins/', '/wp-content/themes/', '/wp-content/uploads/'), array('/lib/', '/assets/plugins/', '/assets/themes/', '/assets/uploads/'), $html);
				}
			}
			if (KATHAROS_REMOVE_SERVER_NAME_FROM_URLS == true) {
				$scheme = 'http';
				if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
					$scheme = 'https';
				}
				$html = str_replace($scheme . '://' . $_SERVER['SERVER_NAME'], null, $html);
			}
			if (KATHAROS_REMOVE_SCHEME_FROM_URLS == true) {
				$html = preg_replace('#https?://#', '//', $html);
			}
			if (KATHAROS_REMOVE_DUBYA_DUBYA_DUBYA_FROM_URLS == true) {
				$html = str_replace('//www.', '//', $html);
			}
			if (KATHAROS_COMPRESS_OUTPUT_BUFFER == true) {
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
}
function katharos_buffer_start() {
	ob_start('katharos_buffer_callback');
}
function katharos_buffer_stop() {
	ob_end_flush();
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
// Register activation and deactivation hooks.
//
function katharos_activate() {
	katharos_htaccess_file('install');
}
function katharos_deactivate() {
	katharos_htaccess_file('uninstall');
}
if (KATHAROS_OBFUSCATE_WORDPRESS_URLS == true) {
	if (KATHAROS_IS_MULTISITE == false) {
		register_deactivation_hook(__FILE__, 'katharos_deactivate');
		register_activation_hook(__FILE__, 'katharos_activate');
	}
}

?>