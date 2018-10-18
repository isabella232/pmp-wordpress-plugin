<?php

class TestSDKWrapper extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		$settings = get_option('pmp_settings');

		if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret']))
			$this->sdk_wrapper = false;
		else {
			$this->sdk_wrapper = new SDKWrapper( $settings );

			// A test query that's all but guaranteed to return at least one result.
			$this->query = array(
				'text' => 'Obama',
				'limit' => 10,
				'profile' => 'story'
			);
		}
	}

	function test_query2json() {
		if (empty($this->sdk_wrapper)) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		/**
		 * The SDKWrapper proxies calls to \Pmp\Sdk member functions.
		 */
		$results = $this->sdk_wrapper->queryDocs($this->query);
		$this->assertTrue(!empty($results));

		/**
		 * The `query2json` function should return a data structure that produces
		 * no errors when passed to `json_encode`.
		 */
		$json_data = json_encode($this->sdk_wrapper->query2json('queryDocs', $this->query));
		$this->assertEquals(json_last_error(), JSON_ERROR_NONE);
	}

	function test_populateEditLinks() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_getPmpPostIdsAndGuids() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_prepFetchData() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	function test_prepQueryData() {
		$this->markTestSkipped('Functional test of `prepQueryData` performed by `test_query2json`');
	}

	function test_href4guid() {
		if (empty($this->sdk_wrapper)) {
			$this->markTestSkipped(
				'This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
			return;
		}

		$test_guid = 'test-guid-does-not-matter';
		$href = $this->sdk_wrapper->href4guid($test_guid);
		$this->assertTrue((bool) strpos($href, $test_guid));
		$this->assertTrue((bool) filter_var($href, FILTER_VALIDATE_URL));
	}

	function test_guid4href() {
		$test_guid = 'test-guid-does-not-matter';
		$test_href = 'http://testdomain.com/test/path/' . $test_guid;
		$guid = SDKWrapper::guid4href($test_href);
		$this->assertEquals($guid, $test_guid);
	}

	function test_commas2array() {
		$test_string = 'one, two, three, four';
		$test_array = SDKWrapper::commas2array($test_string);
		$this->assertEquals(count($test_array), 4);
	}

	function test_getPlayableUrl() {
		$doc = $this->make_enclosure_audio(null, null);
		$this->assertNull(SDKWrapper::getPlayableUrl($doc));

		// valid file extension
		$doc = $this->make_enclosure_audio('http://foobar.gov/mp3.mp3');
		$this->assertEquals('http://foobar.gov/mp3.mp3', SDKWrapper::getPlayableUrl($doc));

		// invalid file extension
		$doc = $this->make_enclosure_audio('http://foobar.gov/mp3.jpg');
		$this->assertNull(SDKWrapper::getPlayableUrl($doc));

		// invalid extension, but valid type
		$doc = $this->make_enclosure_audio('http://foobar.gov/mp3.jpg', 'audio/mpeg');
		$this->assertEquals('http://foobar.gov/mp3.jpg', SDKWrapper::getPlayableUrl($doc));

		// invalid extension and type
		$doc = $this->make_enclosure_audio('http://foobar.gov/something', 'image/jpeg');
		$this->assertNull(SDKWrapper::getPlayableUrl($doc));

		// dereference m3u
		$doc = $this->make_enclosure_audio('http://api.npr.org/m3u/1450294957-2eb257.m3u');
		$this->assertRegexp('/\.mp3$/', SDKWrapper::getPlayableUrl($doc));
	}

	function test_getImageEnclosure() {
		$doc = $this->make_enclosure_image(array());
		$this->assertNull(SDKWrapper::getImageEnclosure($doc));

		// favors large/primary/standard crops
		$doc = $this->make_enclosure_image(array('foobar', 'something', 'large', 'blah'));
		$this->assertEquals('large', SDKWrapper::getImageEnclosure($doc)->meta->crop);
		$doc = $this->make_enclosure_image(array('foobar', 'primary', 'blah'));
		$this->assertEquals('primary', SDKWrapper::getImageEnclosure($doc)->meta->crop);
		$doc = $this->make_enclosure_image(array('standard', 'foo', 'blah'));
		$this->assertEquals('standard', SDKWrapper::getImageEnclosure($doc)->meta->crop);

		// falls back to first
		$doc = $this->make_enclosure_image(array('foo', 'and', 'bar'));
		$this->assertEquals('foo', SDKWrapper::getImageEnclosure($doc)->meta->crop);
	}

	private function make_enclosure_doc($enclosures) {
		$doc = new \Pmp\Sdk\CollectionDocJson();
		$doc->setDocument(array(
			'attributes' => array(
				'guid' => 'my-guid',
				'title' => 'My Title',
			),
			'links' => array(
				'enclosure' => $enclosures,
			),
		));
		return $doc;
	}

	private function make_enclosure_audio($href = null, $type = null) {
		if (!$href) {
			return $this->make_enclosure_doc(array());
		}
		else {
			$enclosure = array('href' => $href);
			if ($type) {
				$enclosure['type'] = $type;
			}
			return $this->make_enclosure_doc(array($enclosure));
		}
	}

	private function make_enclosure_image($crops) {
		$enclosures = array();
		foreach ($crops as $name) {
			$enclosures[] = array(
				'href' => "http://foobar.gov/$name.jpg",
				'meta' => array('crop' => $name),
			);
		}
		return $this->make_enclosure_doc($enclosures);
	}

}
