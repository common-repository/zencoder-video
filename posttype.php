<?php
function zcvideo_posttype_init() {
	wp_enqueue_style( 'zcvideo_style', plugins_url('css/zcvideo.css', __FILE__) );
}
add_action( 'admin_enqueue_scripts', 'zcvideo_posttype_init' );

function zcvideo_posttype() {
	register_post_type( 'zcvideo_video',
		array(
			'labels' => array(
				'name' => __( 'Videos' ),
				'menu_name' => __( 'Zencoder Videos'),
				'singular_name' => __( 'Video' )
			),
			'public' => true,
			'publicly_queryable' => false,
			'supports' => array(
				'title',
				'thumbnail',
			),
			'menu_icon' => 'dashicons-video-alt2',
			'has_archive' => false,
			'rewrite' => [
				'slug' => 'zencoder'
			]
		)
	);
}
add_action('add_meta_boxes', 'zcvideo_meta_box');

function zcvideo_meta_box() {
	add_meta_box('zcvideo_input', __('Video Source', 'zcvideo'), 'zcvideo_meta_box_input', 'zcvideo_video', 'normal');
	add_meta_box('zcvideo_output', __('Zencoder Output', 'zcvideo'), 'zcvideo_meta_box_output', 'zcvideo_video', 'normal');
}

function zcvideo_meta_box_input($post) {
	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'zcvideo_meta_box_input', 'zcvideo_meta_box_input_nonce' );
	print '<label for="_zcvideo_upload">'.__('Select a video file to upload.', 'zcvideo').'</label><br>';
	print '<input type="file" id="_zcvideo_upload" name="_zcvideo_upload">';
	
	print '<br><strong>&mdash;OR&mdash;</strong><br>';
	
	print '<label for="_zcvideo_url">'.__('Enter a URL for a video source.', 'zcvideo').'</label><br>';
	print '<input type="text" id="_zcvideo_url" name="_zcvideo_url" style="width: 100%;">';
	print '<p>'.__('Uploading a file or providing a URL will start a <em>new</em> Zencoder job.', 'zcvideo').'</p>';
	print '<hr>';
	$value = get_post_meta( $post->ID, '_zcvideo_input', true ) ?: 'No Source File/Location.';
	print '<p><strong>Current Source:</strong> '.$value.'</p>';

}

function zcvideo_meta_box_output($post) {
	$zcvideo = get_post_custom($post->ID);
	$job_id = $zcvideo['_zcvideo_job_id']?$zcvideo['_zcvideo_job_id'][0]:null;
	if ($job_id) {
		printf('<p><strong>Job ID:</strong> <a href="https://app.zencoder.com/jobs/%s">%s</a></p>', $job_id, $job_id);
		$status = $zcvideo['_zcvideo_status']?$zcvideo['_zcvideo_status'][0]:null;
		$formats = $zcvideo['_zcvideo_formats']?maybe_unserialize($zcvideo['_zcvideo_formats'][0]):null;

		if ($status && $status == 'Finished' && !empty($formats)) {
			print '<h4>Formats:</h4>';
			print '<ul style="list-style: none;">';
			foreach ($formats as $format=>$url) {
				printf('<li><strong style="width: 80px; display: inline-block; text-align: right; margin-right: 6px;">%s: </strong>%s</li>', $format, $url);
			}
			print '</ul>';
			print '<hr>';
			print '<h4>Shortcode for <a href="http://wordpress.org/plugins/videojs-html5-video-player-for-wordpress/" target="_blank">Video.js</a> Plugin</h4>';
			print zcvideo_videojs_shortcode($post->ID, $formats);
		}
	} else {
		print '<p><em>First upload or provide a URL to a video above.</em></p>';
	}
}
function zcvideo_videojs_shortcode($post_id, $formats=null) {
	// if formats not already prefetched
	if (!$formats) $formats = get_post_meta($post_id, '_zcvideo_formats', true);

	// only shortcode HQ mp4 if both are there
	if (isset($formats['mp4_low']) && isset($formats['mp4_high'])) unset($formats['mp4_low']);
	$shortcode = '[videojs';
	foreach($formats as $format=>$url) {
		// remove _* suffix if it's there;
		$parts = explode('_', $format);
		$format = $parts[0];
		$shortcode.=sprintf(' %s="%s"', $format, esc_url($url));
	}

	// add screenshot if available.
	if ($thumbnail_id = get_post_thumbnail_id($post_id)) {
		$tn_src = wp_get_attachment_image_src($thumbnail_id, 'full');
		$shortcode.=sprintf(' poster="%s"', esc_url($tn_src[0]));
	}

	$shortcode.=']';
	return $shortcode;
}

// save data from meta box
function zcvideo_update_edit_form() { // accept file uploads in form.
    echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'zcvideo_update_edit_form');
function zcvideo_meta_box_save($post_id) {
	global $zencoder;
	// Check if our nonce is set.
	if (!isset($_POST['zcvideo_meta_box_input_nonce'])) return $post_id;
	$nonce = $_POST['zcvideo_meta_box_input_nonce'];

	// Verify that the nonce is valid.
	if (!wp_verify_nonce($nonce, 'zcvideo_meta_box_input')) return $post_id;

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

	// Check the user's permissions.
	if (!current_user_can('edit_post', $post_id)) return $post_id;

	// =========================================================================
	// OKAY, LET'S SAVE STUFF (if necessary)!
	// -------------------------------------------------------------------------
	$input = ''; // this will be evaluated and saved if !empty().
	// a file was uploaded (this takes precedence over a URL)
	if (!empty($_FILES['_zcvideo_upload']['name'])) {
		$upload = wp_handle_upload($_FILES['_zcvideo_upload'], array('test_form' => false));
		@unlink($_FILES['_zcvideo_upload']['tmp_name']); // remove file after upload
		if (empty($upload['error'])) {
			wp_insert_attachment(array(
				'post_title' => $_FILES['_zcvideo_upload']['name'], 
				'post_content' => '',
				'post_status' => 'inherit',
				'post_mime_type' => $upload['type']
			), $upload['file'], $post_id );
			$input = $upload['url'];
		}
	// a URL was provided
	} elseif (!empty($_POST['_zcvideo_url'])) {
		$input = esc_url($_POST['_zcvideo_url']);
	}

	// if we have an input, let's save it.
	if (!empty($input)) {
		update_post_meta($post_id, '_zcvideo_input', $input);

		// get the request payload as an array
		$request = zcvideo_build_request($input);

		// save the public URL base bc of race condition if something
		// changes between request and notification
		$options = zcvideo_get_options();
		$wp_upload_dir = wp_upload_dir();
		$public_url = str_replace('WP_UPLOAD_URL', $wp_upload_dir['url'], $options['output_public']);
		update_post_meta($post_id, '_zcvideo_public', $public_url);

		try {
			$job = $zencoder->jobs->create($request);
		} catch (Services_Zencoder_Exception $e) {
			// If were here, an error occured
			echo '<pre>';
			echo "Fail :(\n\n";
			echo "Errors:\n";
			foreach ($e->getErrors() as $error) echo $error."\n";
			echo "Full exception dump:\n\n";
			print_r($e);
			echo '</pre>';
			die;
		}

		update_post_meta($post_id, '_zcvideo_job_id', $job->id);
		update_post_meta($post_id, '_zcvideo_status', 'Request Sent');
		delete_post_meta($post_id, '_zcvideo_formats');
	}
}
add_action( 'save_post', 'zcvideo_meta_box_save' );
function zcvideo_build_request($url) {
	// get plugin options
	$options = zcvideo_get_options();

	$request = array();
	$request['input'] = $url;
	$request['notifications'] = array(
		get_site_url().'/?zcvideo_notification='.$options['zencoder_notify_key'],
	);
	if (ZCVIDEO_USE_FETCHER) $request['notifications'] = 'http://zencoderfetcher/';

	$wp_upload_dir = wp_upload_dir();

	// set the base_url from our plugin options.
	$base_url = str_replace('WP_UPLOAD_DIR', $wp_upload_dir['path'], $options['output_base']);
	$base = array('base_url' => $base_url);

	// set a max resolution if set.
	if (!empty($options['output_maxres'])) $base['size'] = $options['output_maxres'];

	// set credential nickname if supplied.
	if (!empty($options['output_credentials'])) $base['credentials'] = $options['output_credentials'];

	$pathinfo = pathinfo($url);
	$presets = array(
		'mp4_high' => array(
			'label' => 'mp4_high',
			'filename' => $pathinfo['filename'].'.mp4',
			'h264_profile' => 'high'
		),
		'webm' => array(
			'label' => 'webm',
			'filename' => $pathinfo['filename'].'.webm',
		),
		'ogg' => array(
			'label' => 'ogg',
			'filename' => $pathinfo['filename'].'.ogg',
		),
		'mp4_low' => array(
			'label' => 'mp4_low',
			'filename' => $pathinfo['filename'].'_low.mp4',
			'size' => '640x480',
		),
	);

	$outputs = array();
	foreach ($options['output_formats'] as $i=>$format) {
		if (isset($presets[$format])) {
			$output = array_merge($base, $presets[$format]);
			if ($i==0 && $options['output_thumbnails']) { // add thumbnails to first output
				$output['thumbnails'] = array(
					'format' => 'jpg',
					'number' => $options['output_thumbnails'],
				);
			}
			$outputs[] = $output;
		}
	}
	$request['outputs'] = $outputs;

	return $request;
}

// ============================================================================
// add status as column to listing
// ----------------------------------------------------------------------------
function zcvideo_video_columns($columns) {
	unset($columns['date']);
	$columns['job_id'] = 'Zencoder Job ID';
	$columns['encoding_status'] = 'Encoding Status';
	return $columns;
}
add_filter('manage_zcvideo_video_posts_columns', 'zcvideo_video_columns');

function zcvideo_video_render_columns($column_name, $id) {
	switch ($column_name) {
	case 'encoding_status':
		$status = get_post_meta( $id, '_zcvideo_status', TRUE);
		echo $status;
		break;
	case 'job_id':
		$job_id = get_post_meta( $id, '_zcvideo_job_id', TRUE);
		if ($job_id) printf('<a href="https://app.zencoder.com/jobs/%s">%s</a>', $job_id, $job_id);
		else print '&mdash;';
		break;
	}
}
add_action('manage_zcvideo_video_posts_custom_column', 'zcvideo_video_render_columns', 10, 2);

//removes quick edit from custom post type list
function zcvideo_remove_quick_edit($actions) {
	global $post;
	if( $post->post_type == 'zcvideo_video' ) {
		unset($actions['view']);
		unset($actions['inline hide-if-no-js']);
	}
	return $actions;
}

if (is_admin()) add_filter('post_row_actions','zcvideo_remove_quick_edit',10,2);