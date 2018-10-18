<?php

include_once __DIR__ . '/class-pmpsyncer.php';
include_once __DIR__ . '/class-pmpattachment.php';

/**
 * A class for syncing PMP-documents to WP-posts
 *
 * @since 0.4
 */
class PmpPost extends PmpSyncer {

  // child attachments (PMP items)
  public $attachment_syncers;
  private $attachment_force = false;

  /**
   * Initialize the sync process for a document-to-post
   *
   * @param $pmp_doc - a CollectionDocJson instance (or null)
   * @param $wp_post - a WP_Post instance (or null)
   */
  public function __construct(\Pmp\Sdk\CollectionDocJson $pmp_doc = null, WP_Post $wp_post = null) {
    parent::__construct($pmp_doc, $wp_post);
    if ($wp_post && $wp_post->post_parent > 0) {
      throw new RuntimeException('PmpPost is only for top-level WP_Posts');
    }
    $this->load_attachment_syncers();
  }

  /**
   * Init when you only know the PMP doc
   *
   * @param $pmp_doc - a CollectionDocJson instance
   * @return a new PmpPost
   */
  public static function fromDoc(\Pmp\Sdk\CollectionDocJson $pmp_doc) {
    $args = array(
      'posts_per_page' => 1,
      'post_parent'    => 0, // only top-level (no attachments)
      'post_type'      => 'any',
      'post_status'    => 'any',
      'meta_key'       => 'pmp_guid',
      'meta_value'     => $pmp_doc->attributes->guid,
    );

    // run search, and return new syncer
    $query = new WP_Query($args);
    $posts = $query->posts;
    return new self($pmp_doc, empty($posts) ? null : $posts[0]);
  }

  /**
   * Init when you only know the WP post
   *
   * This function does not check whether the $options from the options table,
   * used for constructing the SDKWrapper, are valid, because the lone caller
   * of this function, pmp_get_updates() is only called by pmp_hourly_cron(),
   * which checks wether pmp_are_settings_valid().
   *
   * @param $wp_post - a WP_Post instance
   * @return a new PmpSyncer
   * @see pmp_get_updates
   */
  public static function fromPost(WP_Post $wp_post) {
    $guid = get_post_meta($wp_post->ID, 'pmp_guid', true);

    if ( $guid ) {
      $options = get_option( 'pmp_settings' );
      $sdk = new SDKWrapper( $options );

      $doc = $sdk->fetchDoc($guid);

      return new self($doc, $wp_post); // doc might be null
    } else {
      return new self(null, $wp_post);
    }
  }

  /**
   * Does this Post look like the upstream Doc (including attachments)?
   *
   * @return boolean post or doc is modified
   */
  public function is_modified() {
    if (parent::is_modified()) {
      return true;
    }
    foreach ($this->attachment_syncers as $syncer) {
      if ($syncer->is_modified()) {
        return true;
      }
    }
    return false;
  }

  /**
   * Allow setting post-status
   *
   * @param $force force updates, ignoring local/modified/subscribed flags
   * @param $post_status optionally set a new post status after pulling
   * @return boolean success
   */
  public function pull($force = false, $post_status = null) {
    $this->attachment_force = $force;
    if (!parent::pull($force)) {
      return false;
    }

    // change status, if necessary
    if ($this->post && $post_status && $post_status != $this->post->post_status) {
      $data = array('ID' => $this->post->ID, 'post_status' => $post_status);
      $id_or_error = wp_update_post($data, true);
      if (is_wp_error($id_or_error)) {
        var_log("pull ERROR setting status for [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
        return false;
      }
    }
    return true;
  }

  /**
   * Pull changes for this post
   *
   * @return boolean success
   */
  protected function pull_post_data() {
    if (!parent::pull_post_data()) {
      return false;
    }

    // sync children NOW, so we can embed attachments
    $content = $this->post->post_content;
    foreach ($this->attachment_syncers as $syncer) {
      $success = $syncer->pull($this->attachment_force);
      if ($success) {
        if (isset($syncer->post_meta['pmp_audio_shortcode'])) {
          $content = $syncer->post_meta['pmp_audio_shortcode'] . "\n" . $content;
        }
        elseif (isset($syncer->post_meta['pmp_image_url']) && !has_post_thumbnail($this->post->ID)) {
          update_post_meta($this->post->ID, '_thumbnail_id', $syncer->post->ID);
        }
      }
    }

    // save changes to content
    if ($content != $this->post->post_content) {
      $id_or_error = wp_update_post(array('ID' => $this->post->ID, 'post_content' => $content), true);
      if (is_wp_error($id_or_error)) {
        var_log("pull_post_data ERROR for [{$this->doc->attributes->guid}] - {$id_or_error->get_error_message()}");
        return false;
      }
      $this->post = get_post($id_or_error);
    }
    return true;
  }

  /**
   * Set tags for top-level posts
   *
   * @return boolean success
   */
  protected function pull_post_metadata() {
    if (!parent::pull_post_metadata()) {
      return false;
    }
    if (!empty($this->doc->attributes->tags)) {
      wp_set_post_tags($this->post->ID, $this->doc->attributes->tags, true);
    }
    return true;
  }

  /**
   * Get child items/attachments of this post
   */
  protected function load_attachment_syncers() {
    $this->attachment_syncers = array();

    // first, the pmp items
    $pmp_guids_to_docs = array();
    if ($this->doc) {
      foreach ($this->doc->items() as $item) {
        if (in_array($item->getProfileAlias(), array('audio', 'image', 'video'))) {
          $pmp_guids_to_docs[$item->attributes->guid] = $item;
        }
      }
    }

    // now, query for post attachments
    if ($this->post) {
      $query = new WP_Query(array(
        'posts_per_page' => -1,
        'post_type'      => array('attachment', 'pmp_attachment'),
        'post_status'    => 'any',
        'post_parent'    => $this->post->ID,
      ));
      foreach ($query->posts as $child_post) {
        $guid = get_post_meta($child_post->ID, 'pmp_guid', true);
        if ($guid && isset($pmp_guids_to_docs[$guid])) {
          $this->attachment_syncers[] = new PmpAttachment($pmp_guids_to_docs[$guid], $child_post, $this);
          unset($pmp_guids_to_docs[$guid]);
        }
        else {
          $this->attachment_syncers[] = new PmpAttachment(null, $child_post, $this); // not in the pmp
        }
      }
    }

    // finally, pmp documents that aren't local yet
    foreach ($pmp_guids_to_docs as $guid => $item) {
      $this->attachment_syncers[] = new PmpAttachment($item, null, $this);
    }
  }

  /**
   * Turn a post into a story-doc
   */
  protected function set_doc_data() {
    parent::set_doc_data();

    // story-specific fields
    if (!empty($this->post->post_excerpt)) {
      $this->doc->attributes->teaser = $this->post->post_excerpt;
    }
    if (!empty($this->post->post_content)) {
      $this->doc->attributes->description = pmp_sanitize_content($this->post->post_content, true);
      $this->doc->attributes->contentencoded = pmp_sanitize_content($this->post->post_content);
      $this->doc->attributes->wpcontent = apply_filters('the_content', $this->post->post_content);
    }
    $this->doc->links->profile = $this->get_profile_links('story');
    $this->doc->links->alternate = array((object) array(
      'href' => get_permalink($this->post->ID),
      'type' => 'text/html',
    ));

    // use the custom byline, if it exists - otherwise post author
    if (!empty($this->post_meta['pmp_byline'])) {
      $this->doc->attributes->byline = $this->post_meta['pmp_byline'];
    }
    else {
      $author = get_user_by('id', $this->post->post_author);
      if ($author && !empty($author->display_name)) {
        $this->doc->attributes->byline = $author->display_name;
      }
    }

    // tags!
    $tags = wp_get_post_tags($this->post->ID);
    if (!empty($tags)) {
      $this->doc->attributes->tags = array();
      foreach ($tags as $tagObj) {
        $this->doc->attributes->tags[] = $tagObj->name;
      }
    }

    // items (push them first!)
    $this->doc->links->item = array();
    foreach ($this->attachment_syncers as $syncer) {
      $success = $syncer->push();
      if ($success) {
        $rels = array();
        if (in_array($syncer->doc->getProfileAlias(), array('image', 'audio', 'video'))) {
          $rels[] = 'urn:collectiondoc:' . $syncer->doc->getProfileAlias();
        }
        if ($syncer->doc->getProfileAlias() == 'image' && get_post_thumbnail_id($this->post->ID) == $syncer->post->ID) {
          $rels[] = 'urn:collectiondoc:image:featured';
        }
        $this->doc->links->item[] = (object) array('href' => $syncer->doc->href, 'rels' => $rels);
      }
    }

  }

}
