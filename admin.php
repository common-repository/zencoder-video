<?php
// ============================================================================
// add menu options page
// ----------------------------------------------------------------------------
function zcvideo_menu_options() {
	add_options_page( __('Zencoder Video Options', 'zcvideo'), __('Zencoder Video Options', 'zcvideo'), 'manage_options', 'zcvideo', 'zcvideo_admin_menu_options_html');
}
add_action( 'admin_menu', 'zcvideo_menu_options');

function zcvideo_admin_menu_options_html() {
	print '<div class="wrap">';
	printf('<h2>%s</h2>', __('Zencoder Video Options', 'zcvideo'));
	print '<form method="post" action="options.php">';
	settings_fields( 'zcvideo_options' );
	do_settings_sections( 'zcvideo_settings' );
	submit_button();
	print '</form>';
	print '</div>';
}

// ============================================================================
// admin settings api fields
// ----------------------------------------------------------------------------
function zcvideo_admin_init() {
	register_setting( 'zcvideo_options', 'zcvideo_options', 'zcvideo_options_sanitize');

	add_settings_section('zcvideo_zencoder', __('Zencoder API', 'zcvideo'), '', 'zcvideo_settings');
	add_settings_field('zcvideo_zencoder_api_key', 'Full Access API Key', 'zcvideo_zencoder_api_key_html', 'zcvideo_settings', 'zcvideo_zencoder');
	add_settings_field('zcvideo_zencoder_notify_key', 'Notification Key', 'zcvideo_zencoder_notify_key_html', 'zcvideo_settings', 'zcvideo_zencoder');

	add_settings_section('zcvideo_output', __('Output Settings', 'zcvideo'), '', 'zcvideo_settings');
	add_settings_field('zcvideo_output_base', 'Output Base URL', 'zcvideo_output_base_html', 'zcvideo_settings', 'zcvideo_output');
	add_settings_field('zcvideo_output_public', 'Public Base URL', 'zcvideo_output_public_html', 'zcvideo_settings', 'zcvideo_output');
	add_settings_field('zcvideo_output_credentials', 'Credentials Nickname', 'zcvideo_output_credentials_html', 'zcvideo_settings', 'zcvideo_output');
	add_settings_field('zcvideo_output_formats', 'Formats', 'zcvideo_output_formats_html', 'zcvideo_settings', 'zcvideo_output');
	add_settings_field('zcvideo_output_maxres', 'Maximum Resolution', 'zcvideo_output_maxres_html', 'zcvideo_settings', 'zcvideo_output');
	add_settings_field('zcvideo_output_thumbnails', 'Thumbnails', 'zcvideo_output_thumbnails_html', 'zcvideo_settings', 'zcvideo_output');
	
}
add_action( 'admin_init', 'zcvideo_admin_init' );
function zcvideo_settings_zencoder() { }

function zcvideo_zencoder_api_key_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	echo '<input id="zcvideo_zencoder_api_key" name="zcvideo_options[zencoder_api_key]" size="40" type="text" value="'.$options['zencoder_api_key'].'">';
	echo '<br><small>Please supply a "Full Access" API Key from Zencoder. You can find it <a href="https://app.zencoder.com/api">here</a>.';
	echo '<br><strong>';
	try {
		$details = $zencoder->jobs->index();
		print 'API Key is valid.';
	} catch (Services_Zencoder_Exception $e) {
		print 'API Key is invalid.';
	}
	echo '</strong></small>';
}
function zcvideo_zencoder_notify_key_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	$notify_key = $options['zencoder_notify_key'];
	if (empty($notify_key)) $notify_key = md5(time().NONCE_SALT);
	echo '<input id="zcvideo_zencoder_notify_key" name="zcvideo_options[zencoder_notify_key]" size="40" type="text" value="'.$notify_key.'">';
	echo '<br><small>This value is used for API notifications. Leave blank to generate a new value.</small>';
}

function zcvideo_output_base_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	$value = $options['output_base'];
	echo '<input id="zcvideo_output_base" name="zcvideo_options[output_base]" size="40" type="text" value="'.$options['output_base'].'">';
	echo '<br><small>Base URL where the output files will be uploaded by Zencoder. This requires the protocol prefix. See <a href="https://app.zencoder.com/docs/api/encoding/general-output-settings/base-url">here</a>';
	echo '<br>You may use <strong>WP_UPLOAD_DIR</strong> as a token for the absolute path (not URL) to the uploads directory.</small>';
}

function zcvideo_output_public_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	$value = $options['output_public'];
	echo '<input id="zcvideo_output_public" name="zcvideo_options[output_public]" size="40" type="text" value="'.$options['output_public'].'">';
	echo '<br><small>Base URL where the output files are publicly accessible.';
	echo '<br>You may use <strong>WP_UPLOAD_URL</strong> as a token for the current upload absolute URL.</small>';
}

function zcvideo_output_credentials_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	echo '<input id="zcvideo_output_credentials" name="zcvideo_options[output_credentials]" size="40" type="text" value="'.$options['output_credentials'].'">';
	echo '<br><small>Nickname of stored credentials for transcoded output. Create them <a href="https://app.zencoder.com/account/credentials">here</a>.';
	echo '<br>Using will allow you to omit username/password/keys/secrets from the base path above and is recommended. See details <a href="http://blog.zencoder.com/2013/02/13/introducing-stored-transfer-credentials/">here</a>.</small>';
}

function zcvideo_output_formats_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	$formats = $options['output_formats'];
	echo '<select id="zcvideo_output_formats" name="zcvideo_options[output_formats][]" multiple="multiple">';
	$opts = array(
		'mp4_high' => 'MP4 High (h.264)',
		'webm' => 'WebM',
		'ogg' => 'OGG',
		'mp4_low' => 'MP4 Low (640x480)',
	);
	foreach ($opts as $val => $text) {
		printf('<option value="%s"%s>%s</option>', $val, array_search($val, $formats)===false?'':' selected="selected"', $text);
	}
	echo '</select>';
	echo '<br><small>Select format(s) to create. These are based on the HTML5 Job Template in <a href="https://app.zencoder.com/request_builder">Zencoder\'s Request Builder</a>.</small>';
}

function zcvideo_output_maxres_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	echo '<input id="zcvideo_output_maxres" name="zcvideo_options[output_maxres]" size="40" type="text" value="'.$options['output_maxres'].'" placeholder="e.g. 1920x1080">';
	echo '<br><small>By default, Zencoder preserves original video resolution. You may specify a maximum resolution restriction here (WxH).</small>';
}

function zcvideo_output_thumbnails_html() {
	global $zencoder;
	$options = zcvideo_get_options();
	echo '<select id="zcvideo_output_thumbnails" name="zcvideo_options[output_thumbnails]">';
	foreach (range(0, 10) as $num) {
		printf('<option value="%s"%s>%s</option>', $num, $num==$options['output_thumbnails']?' selected="selected"':'', $num);
	}
	echo '</select>';
	echo '<br><small>Number of thumbnails evenly spaced out through the video to be automatically be imported to your media library and associated with your video.</small>';
}



function zcvideo_options_sanitize($input) {
	$input = array_intersect_key($input, array_flip(array(
		'zencoder_api_key', 
		'zencoder_notify_key', 
		'output_base',
		'output_public',
		'output_credentials',
		'output_formats',
		'output_maxres',
		'output_thumbnails',
	)));


	if (empty($input['zencoder_notify_key'])) $input['zencoder_notify_key'] = md5(time().NONCE_SALT);

	if (empty($input['output_formats'])) {
		add_settings_error('zcvideo_options', 'output_formats', 'You must select at least one output format.');
		$input['output_formats'] = array();
	}

	if (!empty($input['output_maxres']) && !preg_match('/\d+x\d+/', $input['output_maxres'])) {
		$input['output_maxres'] = '';
		add_settings_error('zcvideo_options', 'output_maxres', 'Maximum Resolution must be in WxH format.');
	}

	// trim trailing slash from output_base and output_public
	$input['output_base'] = trim($input['output_base'], " /\t\n\r\0\x0B");
	$input['output_public'] = trim($input['output_public'], " /\t\n\r\0\x0B");

	return $input;
}