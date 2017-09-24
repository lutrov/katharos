<?php

/*
Plugin Name: Katharos
Description: Reduces the amount of bandwidth your site and your visitor uses by using sophisticated output buffering techniques to clean and compress your site's webpage content before it gets sent to the user's browser. Why this plugin name? Katharos means "pure" in Greek.
Author: Ivan Lutrov
Author URI: http://lutrov.com/
Version: 5.0
Notes: This plugin provides an API to customise the default constant values. See the "readme.md" file for more.
*/

defined('ABSPATH') || die('Ahem.');

//
// Constants used by this plugin.
//
define('KATHAROS_COMPRESS_OUTPUT_BUFFER', true);
define('KATHAROS_REMOVE_DUBYA_DUBYA_DUBYA_FROM_URLS', true);
define('KATHAROS_REMOVE_SCHEME_FROM_URLS', true);
define('KATHAROS_REMOVE_SERVER_NAME_FROM_URLS', true);

//
// Default global replacement strings. Why are we doing this?
// Because we don't like camelcaps in product or brand names and obviously neither do
// Facebook, Salesforce, Qualcomm, Rackspace, Admeld, Airbnb, Dropbox, Foursquare, Zipcar,
// Wayfair, Redfin, Evernote, Flipboard, Shopkick, and countless others.
// http://xconomy.com/national/2011/12/22/how-not-to-name-a-startup-the-curse-of-the-camel-case/
// http://nytimes.com/2009/11/29/magazine/29FOB-onlanguage-t.html
//
function katharos_replacement_strings() {
	$strings = array(
		"YouTube" => "Youtube",
		"WordPress" => "Wordpress",
		"WordCamp" => "Wordcamp",
		"WooThemes" => "Woothemes",
		"WooSwipe" => "Wooswipe",
		"WooCommerce" => "Woocommerce",
		"WePay" => "Wepay",
		"TriStar" => "Tristar",
		"TripAdvisor" => "Tripadvisor",
		"ThinkPad" => "Thinkpad",
		"StumbleUpon" => "Stumbleupon",
		"StudioPress" => "Studiopress",
		"SiteGround" => "Siteground",
		"SearchWP" => "Search WP",
		"PowerPoint" => "Powerpoint",
		"PayPal" => "Paypal",
		"NextGEN" => "Nextgen",
		"MySpace" => "Myspace",
		"MasterCard" => "Mastercard",
		"MarketPress" => "Marketpress",
		"MailPoet" => "Mailpoet",
		"MailChimp" => "Mailchimp",
		"LinkedIn" => "Linkedin",
		"LifterLMS" => "Lifter",
		"LearnPress" => "Learnpress",
		"LearnDash" => "Learndash",
		"LayerSlider" => "Layerslider",
		"iTunes" => "Itunes",
		"iPod" => "Ipod",
		"iPhone" => "Iphone",
		"iPad" => "Ipad",
		"iMac" => "Imac",
		"Howdy" => "Hello",
		"GeneratePress" => "Generatepress",
		"FedEx" => "Fedex",
		"eWAY" => "Eway",
		"eNews" => "Enews",
		"eCommerce" => "Ecommerce",
		"eChecks" => "Echecks",
		"eBay" => "Ebay",
		"EasyCart" => "Easycart",
		"e-mail" => "email",
		"E-mail" => "Email",
		"e-commerce" => "ecommerce",
		"E-commerce" => "Ecommerce",
		"CustomPress" => "Custompress",
		"cPanel" => "Cpanel",
		"BuddyPress" => "Buddypress",
		"bbPress" => "Bbpress",
		"AdWords" => "Adwords",
	);
	return $strings;
}

//
// Reliably test if a plugin is active.
//
function katharos_is_plugin_active($plugin) {
	if (is_multisite() == true) {
		$plugins = get_site_option('active_sitewide_plugins');
		if (isset($plugins[$plugin]) == true) {
			return true;
		}
	}
	return (in_array($plugin, get_option('active_plugins')) == true);
}

//
//  Output buffering functions.
//
function katharos_buffer_callback($html) {
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
				$temp[$hash] = preg_replace(array('#[\x09]#U', '#[\x0D]#U', '#[\x0A]#U'), array(null), $code);
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
	$strings = katharos_replacement_strings();
	if (function_exists('dermatos_replacement_strings') == true) {
		$strings = array_merge($strings, dermatos_replacement_strings());
	}
	$strings = apply_filters('katharos_replacement_strings_array_filter', $strings);
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
// Start buffering with output callback.
//
add_action('init', 'katharos_buffer_start_action', 1);
function katharos_buffer_start_action() {
	if (is_admin() == false) {
		ob_start('katharos_buffer_callback');
	}
}

//
// Stop buffering and flush.
//
add_action('shutdown', 'katharos_buffer_stop_action', 9999);
function katharos_buffer_stop_action() {
	if (is_admin() == false) {
		while (ob_get_level() > 0) {
			ob_end_flush();
		}
	}
}

?>
