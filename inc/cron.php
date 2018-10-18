<?php

/**
 * Query for posts with `pmp_guid` -- an indication that the post was pulled from PMP
 *
 * @since 0.1
 */
function pmp_get_pmp_posts() {
	$query = new WP_Query(array(
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'pmp_guid',
				'compare' => 'EXISTS'
			),
			array(
				'key' => 'pmp_last_pushed', // not pushed entries
				'compare' => 'NOT EXISTS',
			)
		),
		'posts_per_page' => -1,
		'post_type' => 'any',
		'post_status' => 'any',
		'post_parent' => 0, // only top-level entries
	));

	return $query->posts;
}

/**
 * For each PMP post in the WP database, fetch the corresponding Doc from PMP and check if
 * the WP post differs from the PMP Doc. If it does differ, update the post in the WP database.
 *
 * @since 0.1
 * @see pmp_hourly_cron
 */
function pmp_get_updates() {
	pmp_debug('========== pmp_get_updates ==========');
	$posts = pmp_get_pmp_posts();
	foreach ($posts as $post) {
		$syncer = PmpPost::fromPost($post);
		$syncer->pull();
	}
}

/**
 * For each saved search query, query the PMP and perform the appropriate action (e.g., auto draft, auto publish or do nothing)
 *
 * @since 0.3
 */
function pmp_import_for_saved_queries() {
	$search_queries = pmp_get_saved_search_queries();

	$options = get_option( 'pmp_settings' );
	if ( pmp_are_settings_valid( $options ) ) {
		$sdk = new SDKWrapper( $options );
	} else {
		pmp_debug( 'Settings array from options table is not valid for initializing the SDK; aborting.' );
		return false;
	}

	foreach ($search_queries as $id => $query_data) {
		if ($query_data->options->query_auto_create == 'off')
			continue;

		$default_opts = array(
			'profile' => 'story',
			'limit' => 25
		);

		$cron_name = 'pmp_last_saved_search_cron_' . sanitize_title($query_data->options->title);
		$last_saved_search_cron = get_option($cron_name, false);
		if (!empty($last_saved_search_cron))
			$default_opts['startcreated'] = $last_saved_search_cron;
		else {
			// First time pulling, honor the initial pull limit
			if (!empty($query_data->options->initial_pull_limit))
				$default_opts['limit'] = $query_data->options->initial_pull_limit;
		}

		$query_args = array_merge($default_opts, (array) $query_data->query);

		pmp_debug("========== saved-searching: {$query_data->options->title} ==========");
		pmp_debug($query_args);

		$result = $sdk->queryDocs($query_args);
		if (empty($result)) {
			pmp_debug('  -- NO RESULTS!');
			continue;
		}
		else {
			pmp_debug("  -- got {$result->items()->count()} of {$result->items()->totalItems()} total");
		}

		// process results, recording the biggest "created" date
		$last_created = null;
		foreach ($result->items() as $item) {
			$syncer = PmpPost::fromDoc($item);
			if ($syncer->post) {
				$syncer->pull();
			}
			else if ($query_data->options->query_auto_create == 'draft') {
				$syncer->pull(false, 'draft');
			}
			else {
				$syncer->pull(false, 'publish');
			}

			// make sure we got a post out of the deal
			$post_id = $syncer->post->ID;
			if (!$post_id)
				continue;

			if (is_null($last_created) || $item->attributes->created > $last_created)
				$last_created = $item->attributes->created;

			// set the category(s)
			if (isset($query_data->options->post_category)) {
				// Make sure "Uncategorized" category doesn't stick around if it
				// wasn't explicitly set as a category for the saved search import.
				$assigned_categories = wp_get_post_categories($post_id);
				$uncategorized = get_category(1);

				// Check for "Uncategorized" in the already-assigned categories
				$in_assigned_cats = array_search($uncategorized->term_id, $assigned_categories);
				// Check for "Uncategorized" in the saved-search categories
				$in_saved_search_cats = array_search($uncategorized->term_id, $query_data->options->post_category);

				// If "Uncategorized" is in assigned categories and NOT in saved-search categories, ditch it.
				if ($in_assigned_cats >= 0 && $in_saved_search_cats === false)
					unset($assigned_categories[array_search($uncategorized->term_id, $assigned_categories)]);

				// Set the newly generated list of categories for the post
				wp_set_post_categories(
					$post_id, array_values(array_unique(array_merge(
						$assigned_categories, $query_data->options->post_category)))
				);
			}
		}

		// only set the last-searched-cron if we got a date
		if ($last_created) {
			update_option($cron_name, $last_created);
		}
	}
}
