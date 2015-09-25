<?php

/**
 * Query for posts with `pmp_guid` -- an indication that the post was pulled from PMP
 *
 * @since 0.1
 */
function pmp_get_pmp_posts() {
	$sdk = new SDKWrapper();
	$me = $sdk->fetchUser('me');

	$query = new WP_Query(array(
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'pmp_guid',
				'compare' => 'EXISTS'
			),
			array(
				'key' => 'pmp_owner',
				'compare' => '!=',
				'value' => pmp_get_my_guid()
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
 */
function pmp_get_updates() {
	pmp_debug('========== pmp_get_updates ==========');
	$posts = pmp_get_pmp_posts();

	$sdk = new SDKWrapper();

	foreach ($posts as $post) {
		$custom_fields = get_post_custom($post->ID);

		if (empty($custom_fields['pmp_subscribe_to_updates']))
			$subscribe_to_updates = 'on';
		else
			$subscribe_to_updates = $custom_fields['pmp_subscribe_to_updates'][0];

		if ($subscribe_to_updates == 'on') {
			$guid = $custom_fields['pmp_guid'][0];
			if (!empty($guid)) {
				$doc = $sdk->fetchDoc($guid);
				if (empty($doc)) {
					pmp_debug("-- deleting wp[{$wp_post->ID}] pmp[{$pmp_doc->attributes->guid}]");
					pmp_delete_post_attachments($post->ID);
					wp_delete_post($post->ID, true);
				}
				else if (pmp_needs_update($post, $doc)) {
					pmp_update_post($post, $doc);
				}
			}
		}
	}
}

/**
 * Compare the timestamps of a Wordpress post to it's upstream PMP doc,
 * including any child-items that may have changed.
 *
 * @since 0.1
 */
function pmp_needs_update($wp_post, $pmp_doc) {
	$log_ident = "wp[{$wp_post->ID}] pmp[{$pmp_doc->attributes->guid}]";

	// pull metadata (and turn meta-arrays into single values)
	$pmp_modified = get_post_meta($wp_post->ID, 'pmp_modified', true);
	$pmp_audio = get_post_meta($wp_post->ID, 'pmp_audio', true);
	if (!$pmp_modified) {
		pmp_debug("-- updating $log_ident due to missing meta-field 'pmp_modified'");
		return true;
	}
	if (!$pmp_audio) {
		pmp_debug("-- updating $log_ident due to missing meta-field 'pmp_audio'");
		return true;
	}

	// map the child-item modified dates
	$guid_to_modified = array();
	foreach ($pmp_doc->items() as $item) {
		$guid_to_modified[$item->attributes->guid] = $item->attributes->modified;
	}

	// check if the top-level document changed
	if ($pmp_modified !== $pmp_doc->attributes->modified) {
		pmp_debug("-- updating $log_ident due to top-level modified timestamp");
		return true;
	}

	// check for changes to audio docs (embedded in the top-level post)
	foreach ($pmp_audio as $audio_guid => $audio_modified) {
		if (!isset($guid_to_modified[$audio_guid]) || $guid_to_modified[$audio_guid] !== $audio_modified) {
			pmp_debug("-- updating $log_ident due to audio changes");
			return true;
		}
	}

	// now check for image changes (which are attachements to this post)
	$wp_attachments = pmp_get_pmp_attachments($wp_post->ID);
	foreach ($wp_attachments as $wp_attach) {
		$attach_guid = get_post_meta($wp_attach->ID, 'pmp_guid', true);
		$attach_modified = get_post_meta($wp_attach->ID, 'pmp_modified', true);
		if (!isset($guid_to_modified[$attach_guid]) || $guid_to_modified[$attach_guid] !== $attach_modified) {
			pmp_debug("-- updating $log_ident due to image-attachment[$attach_guid] changes");
			return true;
		}
	}

	pmp_debug("-- unchanged $log_ident");
	return false;
}

/**
 * Update an existing WP post which was originally pulled from PMP with the Doc data from PMP.
 *
 * @todo refactor to combine this with ajax _pmp_create_post
 * @since 0.1
 */
function pmp_update_post($wp_post, $pmp_doc) {
	$post_data = pmp_get_post_data_from_pmp_doc($pmp_doc);
	$post_data['ID'] = $wp_post->ID;

	// audio shortcodes (hash guid/modified for later)
	$audio_guid_to_modified = array();
	$audio_codes = _pmp_get_audio_shortcodes($pmp_doc);
	foreach ($audio_codes as $audio_data) {
		$post_data['post_content'] = $audio_data['shortcode'] . "\n" . $post_data['post_content'];
		$audio_guid_to_modified[$audio_data['guid']] = $audio_data['modified'];
	}

	// update the post primary data
	$post_or_error = wp_update_post($post_data, true);
	if (is_wp_error($post_or_error)) {
		var_log('pmp_update_post ERROR: ' . $post_or_error->get_error_message());
		return $post_or_error;
	}

	// update post metadata
	$post_meta = pmp_get_post_meta_from_pmp_doc($pmp_doc);
	foreach ($post_meta as $key => $value) {
		update_post_meta($wp_post->ID, $key, $value);
	}
	update_post_meta($wp_post->ID, 'pmp_audio', $audio_guid_to_modified);

	// sync image changes (which are attachements to this post)
	$wp_attachments  = pmp_get_pmp_attachments($wp_post->ID);
	$pmp_image_datas = _pmp_get_image_datas($pmp_doc);
	$possible_featured_images = array();

	// update-or-delete existing attachments
	foreach ($wp_attachments as $wp_attach) {
		$attach_guid     = get_post_meta($wp_attach->ID, 'pmp_guid', true);
		$attach_modified = get_post_meta($wp_attach->ID, 'pmp_modified', true);
		$attach_url      = get_post_meta($wp_attach->ID, 'pmp_image_url', true);
		if (isset($pmp_image_datas[$attach_guid])) {
			if ($attach_modified === $pmp_image_datas[$attach_guid]['post_meta']['pmp_modified']) {
				pmp_debug("  -- unchanged image {$wp_attach->ID} [$attach_guid]");
				unset($pmp_image_datas[$attach_guid]);
				$possible_featured_images[] = $wp_attach->ID;
			}
			else if ($attach_url !== $pmp_image_datas[$attach_guid]['url']) {
				pmp_debug("  -- reloading image {$wp_attach->ID} [$attach_guid]");
				wp_delete_post($wp_attach->ID, true);
			}
			else {
				pmp_debug("  -- updating image {$wp_attach->ID} [$attach_guid]");
				wp_update_post(array(
					'ID' => $wp_attach->ID,
					'post_excerpt' => $pmp_image_datas[$attach_guid]['caption'],
					'post_title' => $pmp_image_datas[$attach_guid]['alt'],
				));
				foreach ($pmp_image_datas[$attach_guid]['post_meta'] as $image_meta_key => $image_meta_value) {
					update_post_meta($wp_attach->ID, $image_meta_key, $image_meta_value);
				}
				unset($pmp_image_datas[$attach_guid]);
				$possible_featured_images[] = $wp_attach->ID;
			}
		}
		else  {
			pmp_debug("  -- deleting image {$wp_attach->ID} [$attach_guid]");
			wp_delete_post($wp_attach->ID, true);
		}
	}

	// create new attachments
	foreach ($pmp_image_datas as $image_guid => $metadata) {
		$new_attachment = _pmp_create_image_attachment($wp_post->ID, $metadata);
		if (is_wp_error($new_attachment)) {
			var_log('pmp_media_sideload_image ERROR: ' . $new_attachment->get_error_message());
		}
		else {
			$possible_featured_images[] = $new_attachment;
		}
	}

	// ensure featured image
	if ($wp_post->post_type == 'post' && !has_post_thumbnail($wp_post->ID)) {
		if (count($possible_featured_images) > 0) {
			update_post_meta($wp_post->ID, '_thumbnail_id', $possible_featured_images[0]);
		}
	}

	return $wp_post;
}

/**
 * For each saved search query, query the PMP and perform the appropriate action (e.g., auto draft, auto publish or do nothing)
 *
 * @since 0.3
 */
function pmp_import_for_saved_queries() {
	$search_queries = pmp_get_saved_search_queries();
	$sdk = new SDKWrapper();

	foreach ($search_queries as $id => $query_data) {
		if ($query_data->options->query_auto_create == 'off')
			continue;

		$default_opts = array(
			'profile' => 'story',
			'limit' => 25
		);

		$last_saved_search_cron = get_option('pmp_last_saved_search_cron_' . sanitize_title($query_data->options->title), false);
		if (!empty($last_saved_search_cron))
			$default_opts['startdate'] = $last_saved_search_cron;
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

		foreach ($result->items() as $item) {
			$query = new WP_Query(array(
				'meta_query' => array(
					array(
						'key' => 'pmp_guid',
						'value' => $item->attributes->guid
					)
				),
				'posts_per_page' => 1,
				'post_type' => 'any',
				'post_status' => 'any',
				'post_parent' => 0, // only top-level entries
			));

			// find or create the post
			if ($query->have_posts()) {
				$post_id = $query->posts[0]->ID;
			}
			else {
				if ($query_data->options->query_auto_create == 'draft') {
					$result = _pmp_create_post(true, $item);
				}
				else if ($query_data->options->query_auto_create == 'publish') {
					$result = _pmp_create_post(false, $item);
				}
				$post_id = $result['data']['post_id'];
			}

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

		update_option('pmp_last_saved_search_cron_' . sanitize_title($query_data->options->title), date('c', time()));
	}
}
