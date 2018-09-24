<?php
/**
 * Notifications handling code
 */

/**
 * Define some constants used in this plugin for notifications
 */
if ( 1 > version_compare( phpversion(), '7', '<' ) ) {
	/*
	 * The PHP 7 case
	 *
	 * This filters the salt value fed to crypt() to make sure that the
	 * characters passed are what are valid for 
	 *
	 * @see https://github.com/npr/pmp-wordpress-plugin/issues/133
	 */
	$wp_salt_sanitized = preg_replace( '/[^\.\/0-9A-Za-z]/', '', wp_salt('auth') );
	define('PMP_NOTIFICATIONS_SECRET', crypt( get_bloginfo( 'url' ), $wp_salt_sanitized ));
} else {
	/*
	 * php versions less than 7
	 *
	 * This is unchanged from plugin version 0.2.10, allowing PHP 5 sites
	 * to continue using their existing notification secret.
	 */
	define('PMP_NOTIFICATIONS_SECRET', crypt(get_bloginfo('url'), wp_salt('auth')));
}
define('PMP_NOTIFICATIONS_HUB', 'notifications');
define('PMP_NOTIFICATIONS_TOPIC_UPDATED', 'topics/updated');
define('PMP_NOTIFICATIONS_TOPIC_DELETED', 'topics/deleted');

/**
 * Add '?pmp-notifications' as a valid query var
 *
 * @since 0.3
 */
function pmp_bless_notification_query_var() {
	add_rewrite_endpoint('pmp-notifications', EP_ALL);
}
add_action('init', 'pmp_bless_notification_query_var');

/**
 * Template redirect for PubSubHubBub operations
 *
 * If the request is POST, we're dealing with a notification.
 *
 * If the request is GET, we're being asked to verify a subscription.
 *
 * @since 0.3
 */
function pmp_notifications_template_redirect() {
	global $wp_query;

	if (!isset($wp_query->query_vars['pmp-notifications']))
		return false;

	if ($_SERVER['REQUEST_METHOD'] == 'POST')
		pmp_do_notification_callback($_POST);

	if ($_SERVER['REQUEST_METHOD'] == 'GET')
		pmp_subscription_verification($_GET);

	die();
}
add_action('template_redirect', 'pmp_notifications_template_redirect');

/**
 * When a user enables/disables PMP notifications service, send a subscription
 * request to the PMP notifications server.
 *
 * @param $mode string either 'subscribe' or 'unsubscribe'
 * @return boolean|string true on success, or a string error message
 * @since 0.3
 */
function pmp_send_subscription_request($mode='subscribe', $topic_url) {
	$ret = pmp_post_subscription_data($mode, $topic_url);

	if ($ret['response']['code'] == 204) {
		return true;
	}
	else if (!empty($ret['body'])) {
		return $ret['body'];
	}
	else if (!empty($ret['response']['message'])) {
		return $ret['response']['message'];
	}
	else {
		return 'Unknown error - unable to update PMP notifications settings';
	}
}

/**
 * Handle sending the actual subscription data to the hub
 *
 * @since 0.3
 */
function pmp_post_subscription_data($mode, $topic_url) {
	$settings = get_option('pmp_settings');
	$trimmed = rtrim($settings['pmp_api_url'], '/');
	$hub_url =  $trimmed . '/' . PMP_NOTIFICATIONS_HUB;
	$hub_post_url = str_replace('api', 'publish', $trimmed) . '/' . PMP_NOTIFICATIONS_HUB;

	$sdk = new \Pmp\Sdk(
		$settings['pmp_api_url'],
		$settings['pmp_client_id'],
		$settings['pmp_client_secret']
	);

	$verify_token = pmp_store_verification_token($topic_url);

	$ret = wp_remote_post($hub_post_url, array(
		'method' => 'POST',
		'headers' => array(
			'Authorization' => 'Bearer ' . $sdk->home->getAccessToken()
		),
		'body' => array(
			'hub.callback' => get_bloginfo('url') . '/?pmp-notifications',
			'hub.mode' => $mode,
			'hub.topic' => $topic_url,
			'hub.verify' => 'sync',
			'hub.secret' => PMP_NOTIFICATIONS_SECRET,
			'hub.verify_token' => $verify_token
		)
	));

	return $ret;
}

/**
 * Store a verification token for a topic
 *
 * @since 0.3
 */
function pmp_store_verification_token($topic) {
	$verify_token = hash('sha256', REQUEST_TIME);
	set_transient(pmp_get_verify_key($topic), $verify_token, HOUR_IN_SECONDS);
	return $verify_token;
}

/**
 * Retrieve the verification token for a topic
 *
 * @since 0.3
 */
function pmp_get_verification_token($topic) {
	return get_transient(pmp_get_verify_key($topic));
}

/**
 * Get the transient key for a topic (must be 40 characters or less)
 *
 * @since 0.3
 */
function pmp_get_verify_key($topic) {
	return substr('pmp_verify_token_' . hash('sha256', $topic), 0, 40);
}

/**
 * Handle the PMP notification hub sending subscription verification to the callback
 *
 * @since 0.3
 */
function pmp_subscription_verification($data) {
	if (isset($data['pmp-notifications']))
		unset($data['pmp-notifications']);

	$settings = get_option('pmp_settings');

	if (isset($settings['pmp_use_api_notifications']) && $settings['pmp_use_api_notifications'] == 'on')
		$mode = 'unsubscribe';
	else
		$mode = 'subscribe';

	$topic_urls = pmp_get_topic_urls();
	$verify_token = pmp_get_verification_token($data['hub_topic']);

	if ($data['hub_verify_token'] == $verify_token &&
		in_array($data['hub_topic'], $topic_urls) &&
		$data['hub_mode'] == $mode)
	{
		echo $data['hub_challenge'];
	}
}

/**
 * Get the array of topic urls for "updated" and "deleted" documents
 *
 * @since 0.3
 */
function pmp_get_topic_urls() {
	$settings = get_option('pmp_settings');

	return array(
		implode('/', array(
			rtrim($settings['pmp_api_url'], '/'),
			PMP_NOTIFICATIONS_HUB,
			PMP_NOTIFICATIONS_TOPIC_UPDATED
		)),
		implode('/', array(
			rtrim($settings['pmp_api_url'], '/'),
			PMP_NOTIFICATIONS_HUB,
			PMP_NOTIFICATIONS_TOPIC_DELETED
		))
	);
}

/**
 * When the PMP notification hub sends an update, handle it
 *
 * @since 0.3
 */
function pmp_do_notification_callback() {
	global $wpdb;

	pmp_debug('========== pmp_do_notification_callback ==========');

	$body = file_get_contents('php://input');
	$hash = hash_hmac('sha1', $body, PMP_NOTIFICATIONS_SECRET);

	// get a COMPLETE mapping of known-PMP-guids to top-level WP-posts
	$pmp_post_data = $wpdb->get_results(
		"select post_id, meta_value, post_parent " .
		"from {$wpdb->posts} join {$wpdb->postmeta} on (ID = post_id) " .
		"where meta_key = 'pmp_guid'", ARRAY_A);

	// map to the TOP LEVEL post (attachments map to their parent)
	$pmp_guids = array();
	foreach ($pmp_post_data as $row) {
		if ($row['post_parent'] > 0) {
			$pmp_guids[$row['meta_value']] = $row['post_parent'];
		}
		else {
			$pmp_guids[$row['meta_value']] = $row['post_id'];
		}
	}

	// check hub signature
	if ($_SERVER['HTTP_X_HUB_SIGNATURE'] !== "sha1=$hash") {
		var_log('INVALID PMP notifications HTTP_X_HUB_SIGNATURE');
		var_log("  Expected: sha1=$hash");
		var_log("  Got:      " . $_SERVER['HTTP_X_HUB_SIGNATURE']);
		return;
	}

	// parse xml pubsubhubbub body
	$xml = simplexml_load_string($body);
	foreach ($xml->channel->item as $item) {
		$item_json = json_decode(json_encode($item));
		$item_guid = $item_json->guid;

		// look for Posts tied to that guid
		if (isset($pmp_guids[$item_guid])) {
			$post = get_post($pmp_guids[$item_guid]);
			if ($post) {
				$syncer = PmpPost::fromPost($post);
				$syncer->pull();
			}
		}
	}
}
