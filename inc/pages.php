<?php

/**
 * Render the plugin's options page
 *
 * @since 0.1
 */
function pmp_options_page() {
	if (!current_user_can('manage_options'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	pmp_render_template('settings.php');
}

/**
 * Render the plugin's search page
 *
 * @since 0.1
 */
function pmp_search_page() {
	if (!current_user_can('edit_posts'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	$context = array(
		'creators' => pmp_get_creators(),
		'profiles' => pmp_get_profiles()
	);

	if (isset($_GET['search_id'])) {
		$query_data = pmp_get_saved_search_query($_GET['search_id']);
		$context['PMP'] = pmp_json_obj(array(
			'search' => $query_data,
			'support_link_base' => pmp_get_support_link_base()
		));
	} else
		$context['PMP'] = pmp_json_obj(array(
			'support_link_base' => pmp_get_support_link_base()
		));

	pmp_render_template('search.php', $context);
}

/**
 * Render the "Manage saved searches" page
 *
 * @since 0.3
 */
function pmp_manage_saved_searches_page() {
	if (!current_user_can('edit_posts'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	$context = array(
		'PMP' => pmp_json_obj(),
		'searches' => pmp_get_saved_search_queries()
	);

	if ( pmp_verify_settings() ) {
		pmp_render_template('saved_searches.php', $context);
	} else {
		pmp_render_template( 'unconfigured.php', array( 'title' => 'Search the Platform' ) );
	}
}

/**
 * Render the plugin's groups and permissions page
 *
 * @since 0.2
 */
function pmp_groups_page() {
	if (!current_user_can('manage_options'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	if (isset($_POST['pmp-unset-default-group']))
		delete_option('pmp_default_group');

	$options = get_option( 'pmp_settings' );
	if ( pmp_are_settings_valid( $options ) ) {
		$sdk = new SDKWrapper( $options );
	} else {
		pmp_debug( 'Settings array from options table is not valid for initializing the SDK; aborting.' );

		pmp_render_template( 'unconfigured.php', array( 'title' => 'PMP Groups &amp; Permissions' ) );

		return false;
	}

	$pmp_users = $sdk->query2json('queryDocs', array(
		'profile' => 'user',
		'limit' => 9999
	));

	$pmp_groups = $sdk->query2json('queryDocs', array(
		'profile' => 'group',
		'writeable' => 'true',
		'limit' => 9999
	));

	$context = array(
		'PMP' => pmp_json_obj(array(
			'default_group' => get_option('pmp_default_group', false),
			'groups' => $pmp_groups,
			'users' => $pmp_users
		))
	);
	pmp_render_template('groups.php', $context);
}

/**
 * Render the plugin's series and properties pages
 *
 * @since 0.2
 */
function pmp_collections_page() {
	if (!current_user_can('manage_options'))
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	if ($_GET['page'] == 'pmp-properties-menu') {
		$name = 'Properties';
		$profile = 'property';
	} else if ($_GET['page'] == 'pmp-series-menu') {
		$name = 'Series';
		$profile = 'series';
	}

	if (isset($_POST['pmp-unset-default-' . $profile]))
		delete_option('pmp_default_' . $profile);

	$options = get_option( 'pmp_settings' );
	if ( pmp_are_settings_valid( $options ) ) {
		$sdk = new SDKWrapper( $options );
	} else {
		pmp_debug( 'Settings array from options table is not valid for initializing the SDK; aborting.' );

		pmp_render_template( 'unconfigured.php', array( 'title' => 'PMP ' . $name ) );

		return false;
	}

	$pmp_collection = $sdk->query2json('queryDocs', array(
		'profile' => $profile,
		'writeable' => 'true',
		'limit' => 9999
	));

	$context = array(
		'PMP' => pmp_json_obj(array(
			'default_collection' => get_option('pmp_default_' . $profile, false),
			'pmp_collection' => $pmp_collection,
			'profile' => $profile,
			'name' => $name
		)),
		'name' => $name,
		'profile' => $profile
	);
	pmp_render_template('collections.php', $context);
}
