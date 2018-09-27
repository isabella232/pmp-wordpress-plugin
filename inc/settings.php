<?php

/**
 * Register plugin settings
 *
 * @since 0.1
 */
function pmp_admin_init() {
	register_setting( 'pmp_settings_fields', 'pmp_settings', 'pmp_settings_validate' );

	add_settings_section( 'pmp_main', 'API Credentials', null, 'pmp_settings' );

	add_settings_field( 'pmp_user_title', 'Connected As', 'pmp_user_title_input', 'pmp_settings', 'pmp_main' );
	add_settings_field( 'pmp_api_url', 'PMP Environment', 'pmp_api_url_input', 'pmp_settings', 'pmp_main' );
	add_settings_field( 'pmp_client_id', 'Client ID', 'pmp_client_id_input', 'pmp_settings', 'pmp_main' );
	add_settings_field( 'pmp_client_secret', 'Client Secret', 'pmp_client_secret_input', 'pmp_settings', 'pmp_main' );

	add_settings_section( 'pmp_cron', 'Misc. options', null, 'pmp_settings' );

	add_settings_field(
		'pmp_use_api_notifications',
		'Allow PMP API to send content updates?',
		'pmp_use_api_notifications_input',
		'pmp_settings',
		'pmp_cron'
	);
}
add_action( 'admin_init', 'pmp_admin_init' );

/**
 * Input field for PMP API notifications on/off
 *
 * @since 0.3
 */
function pmp_use_api_notifications_input() {
	$options = get_option( 'pmp_settings' );
	$setting = ( isset( $options['pmp_use_api_notifications'] ) ) ? $options['pmp_use_api_notifications'] : false;
	?>
		<input id="pmp_use_api_notifications" type="checkbox"
			name="pmp_settings[pmp_use_api_notifications]"
			<?php echo checked( $setting, 'on' ); ?>>Enable</input>
		<p><em>Enabling this option allows the PMP API to push to your site as new story, audio, image, etc. updates become available.<em></p>
		<p><em>This may help improve performance of your site, especially if you have a large number of imported posts.</em></p>
<?php
}

/**
 * Input field for PMP API URL
 *
 * @since 0.1
 */
function pmp_api_url_input() {
	$options = get_option( 'pmp_settings' );
	$is_sandbox = empty( $options['pmp_api_url'] ) || 'https://api-sandbox.pmp.io' === $options['pmp_api_url'] ;
	$is_production = ! $is_sandbox;
	?>
		<select id="pmp_api_url" name="pmp_settings[pmp_api_url]">
			<option <?php echo $is_production ? 'selected' : '' ; ?> value="https://api.pmp.io">Production</option>
			<option <?php echo $is_sandbox ? 'selected' : '' ; ?> value="https://api-sandbox.pmp.io">Sandbox</option>
		</select>
	<?php
}

/**
 * Input field for client ID
 *
 * @since 0.1
 */
function pmp_client_id_input() {
	$options = get_option( 'pmp_settings' );
	?>
		<input id="pmp_client_id" name="pmp_settings[pmp_client_id]" type="text" value="<?php echo esc_attr( $options['pmp_client_id'] ); ?>" style="width: 25em; max-width: 100%;" />
	<?php
}

/**
 * Input field for client secret
 *
 * @since 0.1
 */
function pmp_client_secret_input() {
	$options = get_option( 'pmp_settings' );

	if (
		! array_key_exists( 'pmp_client_secret', $options )
		|| (
			array_key_exists( 'pmp_client_secret', $options )
			&& empty( $options['pmp_client_secret'] )
		)
	) { ?>
		<input id="pmp_client_secret" name="pmp_settings[pmp_client_secret]" type="password" value="" style="width: 25em; max-width: 100%;" />
	<?php } else { ?>
		<div id="mode-change">
			<button id="pmp_client_secret_reset_button" class="button">Change client secret</button>
		</div>
		<div id="mode-reset" class="hidden">
			<input disabled id="pmp_client_secret_reset" name="pmp_settings[pmp_client_secret_reset]" type="checkbox" value="reset" style="display:none;"/>
			<label for="pmp_client_secret_reset" class="hidden" >
				<?php
					echo wp_kses_post( __( 'Check this box if you are currently changing the client secret.', 'pmp' ) );
				?>
			</label>

			<input disabled id="pmp_client_secret" name="pmp_settings[pmp_client_secret]" type="password" value="" style="width: 25em; max-width: 100%;" />
			<label for="pmp_client_secret" style="display: block; clear: both; margin: 4px 0;">
				<?php
					echo wp_kses_post( __( 'If left blank, this form will unset the saved PMP Client Secret.', 'pmp' ) );
				?>
			</label>
			<button disabled id="pmp_client_secret_reset_reset" class="button">
				<?php
					echo wp_kses_post( __( 'Cancel', 'pmp' ) );
				?>
			</button>
		</div>
	<?php }
}
/**
 * Static field for currently connected user
 *
 * @since 0.3
 */
function pmp_user_title_input() {
	$options = get_option( 'pmp_settings' );
	if ( empty( $options['pmp_api_url'] ) || empty( $options['pmp_client_id'] ) || empty( $options['pmp_client_secret'] ) ) {
		echo '<p><em>Not connected</em></p>';
	}
	else {
		try {
			$sdk = new SDKWrapper();
			$me = $sdk->fetchUser( 'me' );
			$title = $me->attributes->title;
			$link = pmp_get_support_link( $me->attributes->guid );
			printf(
				'<p><a target="_blank" href="%1$s">%2$s</a></p>',
				esc_attr( $link ),
				esc_html( $title )
			);
		} catch ( \Pmp\Sdk\Exception\AuthException $e ) {
			echo '<p style="color:#a94442"><b>Unable to connect - invalid Client-Id/Secret. Is the correct environment chosen?</b></p>';
		} catch ( \Pmp\Sdk\Exception\HostException $e ) {
			echo '<p style="color:#a94442"><b>Unable to connect - ' . esc_html( $options['pmp_api_url'] ) . ' is unreachable</b></p>';
		}
		catch(\Guzzle\Common\Exception\RuntimeException $e ) {
			printf(
				'<p style="color:#a94442"><b>%1$s</b></p><pre><code>%2$s</code></pre><p>%3$s</p>',
				wp_kses_post( __( 'Unable to connect, for the following reason:', 'pmp' ) ),
				esc_html( $e->getMessage() ),
				wp_kses_post( __( 'The Public Media Platform plugin will not work correctly until this error is fixed. Please contact your server administrator or hosting provider.', 'pmp' ) )
			);
		}
	}
}

/**
 * Field validations
 *
 * @since 0.1
 * @param Array $input The form input that gets passed to all validation functions
 * @return Array
 */
function pmp_settings_validate( $input ) {
	$errors = false;
	$options = get_option( 'pmp_settings' );

	/*
	 * The logic behind when the value of the secret shall change.
	 *
	 * The value for $options['pmp_client_secret'] has these possible values:
	 * - unset (array key does not exist)
	 * - empty
	 * - not-empty, aka "set"
	 *
	 * $input['pmp_client_secret'] can be:
	 * - unset
	 * - empty
	 * - set
	 *
	 * The checkbox $input['pmp_client_secret_reset'], which indicates that the
	 * value of the option should be overwritten by the value of the input,
	 * can have these possible values from the form pmp_client_secret_input():
	 * - 'reset': checked
	 * - unset: unchecked
	 *
	 * This results in a 3x3x2 matrix of possible setups.
	 * Well, we can simplify the $input['pmp_client_secret'] to set/not, so
	 * it's really a 3x2x2 matrix of 12 possible settings.
	 *
	 * Here's my notes on when we should change, and when changing results in no effective change
	 * - option unset
	 *     - box checked
	 *         - input not set: change results in no change
	 *         - input set: change results in desired change
	 *     - box unchecked
	 *         - input not set: change undesired
	 *         - input set: change undesired
	 * - option empty
	 *     - box checked
	 *         - input not set: change results in no change
	 *         - input set: change results in desired change
	 *     - box unchecked
	 *         - input not set: change undesired
	 *         - input set: change undesired
	 * - option set
	 *     - box checked
	 *         - input not set: change results in desired change of unsetting the set option, for #130
	 *         - input set: change results in desired change
	 *     - box unchecked
	 *         - input not set: change undesired
	 *         - input set: change undesired
	 *
	 * This cannot be reduced to:
	 * - box unchecked
	 *     - keep option
	 * - box checked
	 *     - input empty: unset option
	 *     - input set: update option
	 * because the inital state of the plugin's form is that the value is unset and the box is unchecked.
	 *
	 * This can be reduced to:
	 * - option unset/empty
	 *      - accept input
	 * - option set
	 *      - box checked: accept input
	 * - else: keep old input
	 *
	 * It could be reduced further, but would lose readability and thinkability.
	 */
	if (
		! isset ( $options['pmp_client_secret'] )
		|| empty ( $options['pmp_client_secret'] )
	) {
		$input['pmp_client_secret'] = $input['pmp_client_secret'];
	} elseif (
		isset( $options['pmp_client_secret'] )
		&& ! empty( $options['pmp_client_secret'] )
		&& isset( $input['pmp_client_secret_reset'] )
		&& 'reset' === $input['pmp_client_secret_reset']
	) {
		$input['pmp_client_secret'] = $input['pmp_client_secret'];
	} else {
		$input['pmp_client_secret'] = $options['pmp_client_secret'];
	}

	// cleanup options that are empty.
	if ( empty( $input['pmp_client_secret'] ) ) {
		unset( $input['pmp_client_secret'] );
	}

	// this does not need to be saved, ever.
	unset( $input['pmp_client_secret_reset'] );

	if ( ! empty( $input['pmp_api_url'] ) && false == filter_var( $input['pmp_api_url'], FILTER_VALIDATE_URL ) ) {
		add_settings_error( 'pmp_settings_fields', 'pmp_api_url_error', 'Please enter a valid PMP API URL.', 'error' );
		$input['pmp_api_url'] = '';
		$errors = true;
	} else {
		add_settings_error( 'pmp_settings_fields', 'pmp_settings_updated', 'PMP settings successfully updated!', 'updated' );
		$errors = true;
	}

	if ( ! empty( $input['pmp_use_api_notifications'] ) && ! isset( $options['pmp_use_api_notifications'] ) ) {
		foreach (pmp_get_topic_urls() as $topic_url) {
			$result = pmp_send_subscription_request('subscribe', $topic_url);
			if ( true !== $result ) {
				add_settings_error( 'pmp_settings_fields', 'pmp_notifications_subscribe_error', $result, 'error' );
				$errors = true;
			}
		}
	} else if ( empty( $input['pmp_use_api_notifications'] ) && isset( $options['pmp_use_api_notifications'] ) ) {
		foreach ( pmp_get_topic_urls() as $topic_url ) {
			$result = pmp_send_subscription_request( 'unsubscribe', $topic_url );
			if ( true !== $result ) {
				add_settings_error( 'pmp_settings_fields', 'pmp_notifications_unsubscribe_error', $result, 'error' );
				$errors = true;
			}
		}
	}

	if ( empty( $errors ) ) {
		pmp_update_my_guid_transient();
	}

	return $input;
}
