<?php

/*
Plugin Name: Katharos
Description: Reduces the amount of bandwidth your site and your visitor uses by using sophisticated output buffering techniques to clean and compress your site's webpage content before it gets sent to the user's browser. Why this plugin name? Katharos means "pure" in Greek.
Author: Ivan Lutrov
Author URI: http://lutrov.com/
Version: 4.4
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

//
// Don't touch these or the sky will fall.
//
define('KATHAROS_IS_MULTISITE', is_multisite());

//
// Replacement strings.
//
function katharos_replacement_strings() {
	$strings = array(
		"WordPress" => "Wordpress",
		"WordCamp" => "Wordcamp",
		"WooThemes" => "Woothemes",
		"WooSwipe" => "Wooswipe",
		"WooCommerce" => "Woocommerce",
		"StudioPress" => "Studiopress",
		"SearchWP" => "Search WP",
		"PayPal" => "Paypal",
		"NextGEN" => "Nextgen",
		"MarketPress" => "Marketpress",
		"MailPoet" => "Mailpoet",
		"MailChimp" => "Mailchimp",
		"LearnPress" => "Learnpress",
		"LearnDash" => "Learndash",
		"LayerSlider" => "Layerslider",
		"Howdy" => "G'day",
		"GeneratePress" => "Generatepress",
		"eWAY" => "Eway",
		"eNews" => "Enews",
		"eCommerce" => "Ecommerce",
		"eChecks" => "Echecks",
		"eBay" => "Ebay",
		"EasyCart" => "Easycart",
		"E-mail" => "Email",
		"e-mail" => "email",
		"E-commerce" => "Ecommerce",
		"e-commerce" => "ecommerce",
		"CustomPress" => "Custompress",
		"cPanel" => "Cpanel",
		"BuddyPress" => "Buddypress",
		"bbPress" => "Bbpress",
		"BackUpWordPress" => "Backup Wordpress",
		"AdWords" => "Adwords",
	);
	return $strings;
}

//
//  Output buffering functions.
//
function katharos_buffer_callback($html) {
	if (is_admin() == false) {
		if (apply_filters('katharos_compress_output_buffer_filter', KATHAROS_COMPRESS_OUTPUT_BUFFER) == true) {
			$temp = array();
			// Remove closing slashes for HTML5 documents.
			if (stripos($html, '<!DOCTYPE html>') !== false) {
				$html = str_replace(array('" />', '"/>', "' />", "'/>"), array('">', '">', "'>", "'>"), $html);
			}
			// Handle IE conditional script loading syntax.
			if (preg_match_all('#<!--\[if(.+)endif\]-->#Uis', $html, $matches) > 0) {
				for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
					$code = katharos_replace_config_strings($matches[0][$i]);
					$hash = hash('sha256', $code);
					$temp[$hash] = preg_replace(array('#[\x09]#U', '#[\x0D]#U', '#[\x0A]#U'), array( null), $code);
					$html = str_replace($code, '[[' . $hash . ']]', $html);
				}
			}
			// Don't compress preformatted text.
			if (preg_match_all('#<pre(.*)>(.*)</pre>#is', $html, $matches) > 0) {
				for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
					$code = trim($matches[0][$i]);
					$hash = hash('sha256', $code);
					$temp[$hash] = $code;
					$html = str_replace($code, '[[' . $hash . ']]', $html);
				}
			}
			// Don't compress textarea content.
			if (preg_match_all('#<textarea(.*)>(.*)</textarea>#is', $html, $matches) > 0) {
				for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
					$code = trim($matches[0][$i]);
					$hash = hash('sha256', $code);
					$temp[$hash] = $code;
					$html = str_replace($code, '[[' . $hash . ']]', $html);
				}
			}
			$html = preg_replace(array('#\{[\s]+#', '#[\s]+\}#', '#[\s]+<#', '#>[\s]+#', '#[\s]+#', '#[\x09]#U', '#[\x0D]#U', '#[\x0A]#U', '#<!--[\s]+(.+)[\s]+-->#Us', '#>[\s]<#'), array('{', '}', ' <', '> ', ' ', '<!--TB-->', '<!--CR-->', '<!--LF-->', null, '><'), $html);
			$html = preg_replace('#<!--(TB|CR|LF)-->#U', null, $html);
			if (count($temp) > 0) {
				foreach ($temp as $hash => $code) {
					$html = str_replace('[[' . $hash . ']]', $code, $html);
				}
			}
			$html = katharos_replace_config_strings($html);
		}
	}
	if (is_admin() == true) {
		$html = katharos_hack_woocommerce_headers($html);
	}
	$strings = apply_filters('katharos_replacement_strings_filter', katharos_replacement_strings());
	if (count($strings) > 0) {
		$temp = array();
		$i = 0;
		foreach ($strings as $key => $value) {
			$temp['from'][$i] = sprintf('#\b(%s)\b#', $key);
			$temp['to'][$i] = $value;
			$i++;
		}
		$html = preg_replace($temp['from'], $temp['to'], $html, -1, $count);
		$html = str_replace(array(':</label>', ':</th>'), array('</label>', '</th>'), $html);
	}
	return trim($html);
}

//
// Replace strings based on configuration settings.
//
function katharos_replace_config_strings($html) {
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
	return trim($html);
}

//
// Woocommerce hack to show correct page titles for reports, settings, status and addons pages.
//
function katharos_hack_woocommerce_headers($html) {
	if (strpos($html, '<div class="wrap woocommerce') > 0) {
		$page = isset($_GET['page']) ? $_GET['page'] : null;
		if ($page == 'wc-reports' || $page == 'wc-settings' || $page == 'wc-status' || $page == 'wc-addons') {
			$title = ucwords(get_admin_page_title());
			if (substr(strtolower($title), 0, 11) <> 'woocommerce') {
				$title = sprintf('WooCommerce %s', $title);
			}
			switch ($page) {
				case 'wc-reports':
				case 'wc-settings':
				case 'wc-status':
					$html = str_replace('<div class="wrap woocommerce">', sprintf('<div class="wrap woocommerce"><h1>%s</h1>', $title), $html);
					break;
				case 'wc-addons':
					$html = str_replace('<div class="wrap woocommerce wc_addons_wrap">', sprintf('<div class="wrap woocommerce wc_addons_wrap"><h1>%s</h1>', $title), $html);
					break;
			}
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
	add_action('shutdown', 'katharos_buffer_stop', 8888);
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
