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
		$expect = preg_quote( '<input id="pmp_client_secret" name="pmp_settings[pmp_client_secret]" type="password" value="" />' , '/' );
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

		$expect = '/<a href="#" id="pmp_client_secret_reset">Change client secret<\/a>/';
		$this->expectOutputRegex($expect);
		pmp_client_secret_input();

		// reset
		update_option( 'pmp_settings', $preserve );
	}

	function test_pmp_settings_validate() {
		$options = get_option('pmp_settings');

		// Make sure the pmp_api_url is well-formed.
		$invalid_url_input = array(
			'pmp_api_url' => 'NOT_AN_URL'
		);
		$result = pmp_settings_validate($invalid_url_input);
		$this->assertEquals($result['pmp_api_url'], '');

		// If the pmp_client_secret option is set, but the input sent over the wire is blank,
		// don't empty pmp_client_secret.
		$client_secret_blank_input = array(
			'pmp_api_url' => $options['pmp_api_url'],
			'pmp_client_id' => $options['pmp_client_id']
		);
		$result = pmp_settings_validate($client_secret_blank_input);
		$this->assertEquals($result['pmp_client_secret'], $options['pmp_client_secret']);

		// Likewise, if the pmp_client_secret input is not blank, make sure the result
		// includes it.
		$client_secret_new_input = array_merge(array(
			'pmp_client_secret' => 'NEW_CLIENT_SECRET'
		), $client_secret_blank_input);
		$result = pmp_settings_validate($client_secret_new_input);
		$this->assertEquals($client_secret_new_input['pmp_client_secret'], $result['pmp_client_secret']);
	}
}
