<?php
require_once __DIR__ . '/../inc/class-pmppost.php';

/**
 * Abstract class for PMP Syncer utility testing
 *
 * todo: mock out some of these dependencies
 */
abstract class PMP_SyncerTestCase extends WP_UnitTestCase {

  private static $_sdk_wrapper;
  private static $_pmp_story_guid = '753d0442-7133-4507-974e-65639ba6535c';
  private static $_pmp_story;
  private static $_write_itag = 'pmp-wordpress-test-content';

  /**
   * Setup test prerequisites once
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();

    // throw in a test itag, so we can cleanup later
    PmpSyncer::$ITAGS = array_unique(array_merge(PmpSyncer::$ITAGS, array(self::$_write_itag)));

    // must have pmp settings defined
    $settings = get_option('pmp_settings');
    if (empty($settings['pmp_api_url']) || empty($settings['pmp_client_id']) || empty($settings['pmp_client_secret'])) {
      self::$_sdk_wrapper = false;
    }
    else {
      self::$_sdk_wrapper = new SDKWrapper( $settings );
      self::$_pmp_story = self::$_sdk_wrapper->fetchDoc(self::$_pmp_story_guid);
    }
  }

  /**
   * Clean up after ourselves
   */
  public static function tearDownAfterClass() {
    if (self::$_sdk_wrapper) {
      $query = self::$_sdk_wrapper->queryDocs(array('itag' => self::$_write_itag, 'writeable' => true));
      if ($query) {
        foreach ($query->items() as $item) {
          $item->delete();
        }
      }
    }
  }

  /**
   * Assign instance variables for fixtures
   */
  public function setUp() {
    parent::setUp();
    if (self::$_sdk_wrapper) {
      $this->pmp_story = clone self::$_pmp_story;
      $this->wp_post = $this->reset_post(null, $this->pmp_story->attributes->guid, $this->pmp_story->attributes->modified);
      $this->local_post = $this->make_local_post();
    }
    else {
      $this->markTestSkipped('This test requires site options `pmp_api_url`, `pmp_client_id` and `pmp_client_secret`');
    }
  }

  /**
   * Reset a wordpress post back to a "known" state
   */
  public function reset_post($id, $guid, $modified, $parent_id = null) {
    if (is_null($id)) {
      $id = wp_insert_post(array('post_title' => 'foo', 'post_content' => 'bar'));
    }
    $id = is_integer($id) ? $id : $id->ID;

    // update to initial state
    wp_update_post(array(
      'ID' => $id,
      'post_parent' => $parent_id,
      'post_status' => $parent_id ? 'inherit' : 'draft',
      'post_type' => $parent_id ? 'attachment' : 'post',
      'post_title' => 'foo',
      'post_content' => 'bar',
    ));
    update_post_meta($id, 'pmp_guid', $guid);
    update_post_meta($id, 'pmp_modified', $modified);
    return get_post($id);
  }

  /**
   * Create a "local only" fully-featured wordpress post
   */
  public function make_local_post() {
    $id = wp_insert_post(array(
      'post_title'    => 'my post title',
      'post_content'  => 'SEE BELOW',
      'post_excerpt'  => 'my post excerpt',
      'post_status'   => 'publish',
      'post_date_gmt' => '1999-12-31 12:12:12',
      'post_author'   => 1,
    ));

    // attachments
    $attach1_id = $this->make_local_attachment($id, 'image');
    update_post_meta($attach1_id, '_wp_attachment_image_alt', 'real-alt-text');
    $attach2_id = $this->make_local_attachment($id, 'image', 'my-excerpt', 'my-byline');
    set_post_thumbnail($id, $attach1_id);
    $attach3_id = $this->make_local_attachment($id, 'audio');

    // real looking content, with shortcodes and the like
    $real_image = wp_get_attachment_image($attach2_id);
    $real_audio = wp_get_attachment_url($attach3_id);
    $real_content = "here it is with content\n\n" .
      "[caption id=\"attachment_$attach2_id\"]" .
      "<a href=\"http://somewhere.foo\">$real_image</a> my caption" .
      "[/caption]\n\n" .
      "and some more content\n\n" .
      "[audio mp3=\"$real_audio\"][/audio]\n\n" .
      "&nbsp;";
    wp_update_post(array('ID' => $id, 'post_content' => $real_content));

    // some tags (override old tags)
    wp_set_post_tags($id, array('foo', 'bar', 'and another one'), false);

    // update_post_meta($id, 'pmp_byline', 'my byline goes here');
    return get_post($id);
  }

  /**
   * Create a test attachment for a post
   *
   * @param $post_id the parent post
   * @param $type the type of attachment (image/audio)
   * @param $excerpt an optional excerpt
   * @param $byline an optional byline
   * @return $attach_id the created attachment
   */
  public function make_local_attachment($post_id, $type, $excerpt = '', $byline = '') {
    include_once ABSPATH . 'wp-admin/includes/image.php';
    include_once ABSPATH . 'wp-admin/includes/file.php';
    include_once ABSPATH . 'wp-admin/includes/media.php';

    // grab an image from the WP tests
    if ($type == 'image') {
      $filename = __DIR__ . '/data/imagetest.jpg';
      $filetype = 'image/jpeg';
    }
    else if ($type == 'audio') {
      $filename = __DIR__ . '/data/mpthreetest.mp3';
      $filetype = 'audio/mpeg';
    }
    $wp_upload_dir = wp_upload_dir();
    $attachment = array(
      'guid'           => $wp_upload_dir['url'] . '/' . basename($filename),
      'post_mime_type' => $filetype,
      'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
      'post_content'   => '',
      'post_excerpt'   => $excerpt,
      'post_status'    => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $filename, $post_id);

    // generate attachment metadata
    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
    wp_update_attachment_metadata($attach_id, $attach_data);
    if ($byline) {
      update_post_meta($attach_id, 'pmp_byline', $byline);
    }
    return $attach_id;
  }

}
