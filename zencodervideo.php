<?php
/**
 * @package Zencoder_Video
 * @version 0.1.2
 */
/*
Plugin Name: Zencoder Video
Description: This plugin allows you to create Zencoder jobs using v2 of their API. The plugin uses Zencoder's notifications to update the status of jobs. Shortcodes can be used to embed videos into posts.
Author: Norman Yung
Version: 0.1.2
*/

// ============================================================================
// use Zencoder Fetcher 
// ----------------------------------------------------------------------------
// if you're a developer and you want to use Zencoder Fetcher for
// notifications, just change this to 'true' and the notifications URL will be
// "http://zencoderfetcher/" instead of using the value in the plugin 
// configuration. 
// 
// For more info, visit: 
// https://app.zencoder.com/docs/guides/advanced-integration/getting-zencoder-notifications-while-developing-locally
define('ZCVIDEO_USE_FETCHER', false);


// ============================================================================
// loads options from DB
// ----------------------------------------------------------------------------
function zcvideo_get_options($force = false) {
	global $zcvideo_options;
	if ($force || empty($zcvideo_options)) {
		$zcvideo_options = get_option('zcvideo_options', array(
			'zencoder_api_key' => '',
			'zencoder_notify_key' => md5(time().NONCE_SALT),
			'output_thumbnails' => 1
		));
	}
	return $zcvideo_options;
}
$zcvideo_options = zcvideo_get_options();

// ============================================================================
// require zencoder-php library and initialize
// source: https://github.com/zencoder/zencoder-php
// ----------------------------------------------------------------------------
require_once 'Services/Zencoder.php';
$zencoder = new Services_Zencoder($zcvideo_options['zencoder_api_key']);

// ============================================================================
// load files for various parts of this plugin
// ----------------------------------------------------------------------------
require_once __DIR__ . '/admin.php'; // admin settings
require_once __DIR__ . '/posttype.php'; // zcvideo_video post type
require_once __DIR__ . '/notification.php'; // notification listener


// ============================================================================
// load the plugin
// ----------------------------------------------------------------------------
add_action( 'init', 'zcvideo_posttype' ); // in posttype.php

// ============================================================================
// activation hook for this plugin
// ----------------------------------------------------------------------------
function zcvideo_activate() {
	// activate the plugin
}
register_activation_hook( __FILE__, 'zcvideo_activate' );
