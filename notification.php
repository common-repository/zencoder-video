<?php
// this function is called when notification endpoint is POSTed to.
function zcvideo_notification() {
	// get out immediately if the query var isn't even set.
	if (!isset($_GET['zcvideo_notification'])) return;

	// since the query var IS set, we need to get the zencoder object into
	// scope and get the plugin options to compare the notify_key
	global $zencoder;
	$options = zcvideo_get_options();

	// get out if its the wrong key.
	if ($_GET['zcvideo_notification'] != $options['zencoder_notify_key']) return;

	// we're good! let's parse the incoming notification
	$notification = $zencoder->notifications->parseIncoming();
	$job_id = $notification->job->id;

	// print_r($notification); die;

	// find the post with that job id.
	$posts = get_posts(array(
		'post_type' => 'zcvideo_video',
		'meta_key' => '_zcvideo_job_id',
		'meta_value' => $job_id,
	));
	if (count($posts)) {
		$post = $posts[0];
		$formats = array();
		
		$public_base = get_post_meta($post->ID, '_zcvideo_public', true);
		// fallback even though this should never happen.
		if (!$public_base) $public_base = str_replace('WP_UPLOAD_URL', $wp_upload_dir['url'], $options['output_public']);
		
		foreach ($notification->job->outputs as $label=>$output) {
			if ($output->state == 'finished') {
				$formats[$label] = $public_base.'/'.basename($output->url);
				// getting thumbnails.
				if ($output->thumbnails) zcvideo_process_thumbnails($post->ID, $output->thumbnails);
			}
		}
		update_post_meta($post->ID, '_zcvideo_formats', $formats);
		update_post_meta($post->ID, '_zcvideo_status', "Finished");
	}
	die;
}

function zcvideo_process_thumbnails($post_id, $thumbnails) {
	// wrap it in an array if its not.
	if (!is_array($thumbnails)) $thumbnails = array($thumbnails);

	$attach_ids = array();

	foreach ($thumbnails as $group) {
		if (!$group->images) continue;
		foreach ($group->images as $img) {
			$attach_ids[] = zcvideo_add_thumbnail($post_id, $img->url, $img->format);
		}
	}

	if (!empty($attach_ids)) {
		$thumb_id = $attach_ids[array_rand($attach_ids)];
		set_post_thumbnail($post_id, $thumb_id);
	}
}
function zcvideo_add_thumbnail($post_id, $url, $format) {
	$filename = sprintf('zcvideo_tn_%s_%s.%s', $post_id, md5($url.time()), strtolower($format));
	$upload = wp_upload_bits($filename, null, file_get_contents($url));
	
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mimetype = finfo_file($finfo, $upload['file']);
	finfo_close($finfo);

	$attach_id = wp_insert_attachment(array(
		'post_title' => $filename, 
		'post_content' => '',
		'post_status' => 'inherit',
		'post_mime_type' => $mimetype
	), $upload['file'], $post_id );

	// you must first include the image.php file
	// for the function wp_generate_attachment_metadata() to work
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	return $attach_id;
}

add_action( 'init', 'zcvideo_notification');
