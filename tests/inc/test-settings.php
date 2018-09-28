<?php

class TestSettings extends WP_UnitTestCase {
	function test_pmp_admin_init() {
		pmp_admin_init();

		global $new_whitelist_options;
		$this->assertTrue(isset($new_whitelist_options['pmp_settings_fields']));

		global $wp_settings_sections;
		$sections = array(
			'pmp_main' => array(
				'id' => 'pmp_main',
				'title' => 'API Credentials',
				'callback' => null
			),
			'pmp_cron' => array(
				'id' => 'pmp_cron',
				'title' => 'Misc. options',
				'callback' => null
			)
		);
		$this->assertTrue($wp_settings_sections['pmp_settings'] == $sections);

		global $wp_settings_fields;
		$fields = array(
			'pmp_main' => array(
				array(
					'id' => 'pmp_user_title',
					'title' => 'Connected As',
					'callback' => 'pmp_user_title_input',
					'args' => null
				),
				array(
					'id' => 'pmp_api_url',
					'title' => 'PMP Environment',
					'callback' => 'pmp_api_url_input',
					'args' => null
				),
				array(
					'id' => 'pmp_client_id',
					'title' => 'Client ID',
					'callback' => 'pmp_client_id_input',
					'args' => null
				),
				array(
					'id' => 'pmp_client_secret',
					'title' => 'Client Secret',
					'callback' => 'pmp_client_secret_input',
					'args' => null
				)
			),
			'pmp_cron' => array(
				array(
					'id' => 'pmp_use_api_notifications',
					'title' => 'Allow PMP API to send content updates?',
					'callback' => 'pmp_use_api_notifications_input',
					'args' => null
				)
			)
		);

		foreach (array_keys($sections) as $section_id) {
			$section_fields = $fields[$section_id];
			foreach ($section_fields as $field) {
				$this->assertTrue($wp_settings_fields['pmp_settings'][$section_id][$field['id']] == $field);
			}
		}
	}

	function test_pmp_api_url_input() {
		$expect = '/<select id="pmp_api_url" name="pmp_settings\[pmp_api_url\]"/';
		$this->expectOutputRegex($expect);
		pmp_api_url_input();
	}

	function test_pmp_client_id_input() {
		$expect = '/<input id="pmp_client_id" name="pmp_settings\[pmp_client_id\]" type="text"/';
		$this->expectOutputRegex($expect);
		pmp_client_id_input();
	}

	/**
	 * Expectation is valid in the case that `$options['pmp_client_secret']` is not empty
	 * or is unset, where `$options = get_option('pmp_settings')`.
	 *
	 * @see test_pmp_client_secret_input_option
	 * @see pmp_client_secret_input
	 */
	function test_pmp_client_secret_input_nooption() {
		// save the PMP settings that exist at this point in the test
		$preserve = get_option( 'pmp_settings' );

		delete_option( 'pmp_settings' );
		$expect = preg_quote( '<input id="pmp_client_secret" name="pmp_settings[pmp_client_secret]" type="password" value=""' , '/' );
		$expect = '/' . $expect . '/';
		$this->expectOutputRegex($expect);
		pmp_client_secret_input();

		// reset
		update_option( 'pmp_settings', $preserve );
	}

	/**
	 * Expectation is valid in the case that `$options['pmp_client_secret']` is not empty
	 * where `$options = get_option('pmp_settings')`.
	 *
	 * @see test_pmp_client_secret_input_nooption
	 * @see pmp_client_secret_input
	 */
	function test_pmp_client_secret_input_option() {
		// save the PMP settings that exist at this point in the test
		$preserve = get_option( 'pmp_settings' );

		update_option( 'pmp_settings', array(
			'pmp_client_secret' => 'test string',
		) );

		$expect = '/<button id="pmp_client_secret_reset_button" [^>]+>Change client secret<\/button>/';
		$this->expectOutputRegex($expect);
		pmp_client_secret_input();

		// reset
		update_option( 'pmp_settings', $preserve );
	}

	/**
	 * Make sure the pmp_api_url is well-formed: things not a URL should not be accepted.
	 */
	function test_pmp_settings_validate__invalid_url() {
		$options = get_option('pmp_settings');

		$invalid_url_input = array(
			'pmp_api_url' => 'NOT_AN_URL',
		);
		$result = pmp_settings_validate($invalid_url_input);
		$this->assertEquals($result['pmp_api_url'], '');
	}

	/**
	 * Make sure the pmp_api_url is well-formed: URLS should be accepted.
	 */
	function test_pmp_settings_validate_valid_url() {
		$options = get_option('pmp_settings');

		$valid_url_input = array(
			'pmp_api_url' => 'https://api.npr.org/',
		);
		$result = pmp_settings_validate($valid_url_input);
		$this->assertEquals( $result['pmp_api_url'], $valid_url_input['pmp_api_url'] );

		$valid_url_input = array(
			'pmp_api_url' => 'https://api-s1.npr.org/',
		);
		$result = pmp_settings_validate($valid_url_input);
		$this->assertEquals( $result['pmp_api_url'], $valid_url_input['pmp_api_url'] );
	}

	/**
	 * If the pmp_client_secret option is set,
	 * but the input sent over the wire is blank,
	 * don't empty pmp_client_secret.
	 */
	function test_pmp_settings_validate_empty_secret() {
		$options = get_option( 'pmp_settings' );

		$client_secret_blank_input = array(
		);
		$result = pmp_settings_validate($client_secret_blank_input);
		$this->assertEquals( $result['pmp_client_secret'], $options['pmp_client_secret'] );
	}

	/**
	 * If the pmp_client_secret option is set,
	 * and the input secret is blank,
	 * and the "reset" input is sent,
	 * do empty pmp_client_secret.
	 */
	function test_pmp_settings_validate_secret_noreset() {
		$options = get_option( 'pmp_settings' );

		$client_secret_new_input = array(
			'pmp_client_secret_reset' => 'reset',
		);
		$result = pmp_settings_validate($client_secret_new_input);
		$this->assertFalse( array_key_exists( 'pmp_client_secret', $result ) );
	}

	/**
	 * If the pmp_client_secret option is set,
	 * and the input secret is set,
	 * and the "reset" input is sent,
	 * expect the secret to match the input secret
	 */
	function test_pmp_settings_validate_secret_reset() {
		$options = get_option('pmp_settings');
		$options = get_option( 'pmp_settings' );

		// Likewise, if the pmp_client_secret input is not blank, make sure the result
		// includes it.
		$client_secret_new_input = array(
			'pmp_client_secret' => 'NEW_CLIENT_SECRET',
			'pmp_client_secret_reset' => 'reset',
		);
		$result = pmp_settings_validate($client_secret_new_input);
		$this->assertEquals( $client_secret_new_input['pmp_client_secret'], $result['pmp_client_secret'] );
	}
}
