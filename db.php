<?php

function currheap() {
  return isset( $_COOKIE['_alt_heap'] ) && ! empty( $_COOKIE['_alt_heap'] ) ? preg_replace('/[^a-z0-9_]/', '', strtolower( $_COOKIE['_alt_heap'] ) ) : false;
}

defined( 'WP_CONTENT_DIR' ) || define( 'WP_CONTENT_DIR', dirname( __FILE__ ) . '/wp-content' );
defined( 'WP_CONTENT_URL' ) || define( 'WP_CONTENT_URL', '/wp-content' );
if ( false !== currheap() ) {
  defined( 'WP_PLUGIN_DIR' ) || define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins_tmp_' . currheap() );
  defined( 'WP_PLUGIN_URL' ) || define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins_tmp_' . currheap() );
  defined( 'PLUGINDIR' ) || define( 'PLUGINDIR', 'wp-content/plugins_tmp_' . currheap() );
}

/**
 * Extend $wpdb to allow for cookie based alternative db prefixes
 */
class safe_wpdb extends wpdb {
  public function set_prefix( $prefix, $set_table_names = true ) {
    if( function_exists( 'currheap' ) && false !== currheap() ) {
      $alt_db_prefix = 'tmp_' . currheap() . '_';
      $prefix = $prefix . $alt_db_prefix; // wp_tmp_{_alt_heap}_
    }

    // set up the prefix globally and set up all the tables
    parent::set_prefix( $prefix, $set_table_names );

    if( function_exists( 'currheap' ) && false !== currheap() ) {
      // bail out early if wordpress isn't installed

      // check if siteurl is available
      $siteurl = $this->get_var( "SELECT option_value FROM $this->options WHERE option_name='siteurl'" );
      header('X-Siteurl:' . $this->options);
      if( null === $siteurl ) {
        // it's not, let's bail out...
        // clear the alt_heap cookie
        setcookie('_alt_heap', '', 0, '/');

        // reload the page
        // Note: wp_redirect isn't set yet, so we do it manually
        $request_uri = $_SERVER['REQUEST_URI'];
        header('Location:' . $request_uri);
        http_response_code( 302 );
        exit;
      }
    }
  }
}

// use our wpdb as the global one
$wpdb = new safe_wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

