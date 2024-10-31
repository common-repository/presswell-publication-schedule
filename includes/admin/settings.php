<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Presswell_Publication_Schedule_Settings {

  protected static $instance;

	public $file = __FILE__;
	public $plugin;

	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Presswell_Publication_Schedule_Settings ) ) {
			self::$instance = new Presswell_Publication_Schedule_Settings();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->plugin = Presswell_Publication_Schedule::get_instance();

    if ( ! session_id() ) {
      session_start();
    }

    add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

    add_action( 'admin_post_' . $this->plugin->key . '_update_settings', array( $this, 'admin_post_update' ) );

    add_action( 'admin_post_' . $this->plugin->key . '_reset_settings', array( $this, 'admin_post_reset' ) );

    add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

  public function plugin_action_links( $links, $file ) {
    if ( strpos( $file, $this->plugin->slug ) !== false ) {
      $link = '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ) . '">' . __( 'Settings', $this->slug ) . '</a>';
			array_unshift( $links, $link );
		}

    return $links;
  }

  public function admin_menu() {
  	add_submenu_page(
      'options-general.php',
      $this->plugin->name,
      'Publication Schedule',
      'manage_options',
      $this->plugin->slug . '-settings',
      array( $this, 'draw_menu_page' )
    );

    do_action( $this->plugin->key . '_settings_menu' );
  }

  public function draw_menu_page() {
    $settings = $this->plugin->settings;

    include $this->plugin->get_path() . 'includes/admin/templates/settings.php';
  }

  public function draw_fields() {
    do_action( $this->plugin->key . '_settings_before_fields' );

    $this->draw_field( 'days' );
    $this->draw_field( 'times' );

    do_action( $this->plugin->key . '_settings_after_fields' );
  }

  public function draw_field( $type ) {
    include $this->plugin->get_path() . 'includes/admin/templates/fields/' . $type . '.php';
	}

  public function admin_post_update() {
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->plugin->key . '_update_settings' ) ) {
      wp_die( 'Nonce verification failed' );
    }

    $settings = $this->sanitize_post();

    $this->plugin->update_settings( $settings );

    $_SESSION[ $this->plugin->key . '-settings-updated' ] = true;

    wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ), 303 );
  }

  public function admin_post_reset() {
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->plugin->key . '_reset_settings' ) ) {
      wp_die( 'Nonce verification failed' );
    }

    $this->plugin->set_default_settings();

    $_SESSION[ $this->plugin->key . '-settings-reset' ] = true;

    wp_redirect( admin_url( 'options-general.php?page=' . $this->plugin->slug . '-settings' ), 303 );
  }

  public function sanitize_post() {
		$settings = $this->plugin->settings;
    $post = $_POST;

    // Mode

    $settings['mode'] = 'basic';

    // Filter days

    $days = ( isset( $post['pwps_days'] ) ) ? $post['pwps_days'] : array();

    $settings['days'] = array();

    for ( $i = 0; $i < 7; $i++ ) {
      $settings['days'][ $i ] = ( ! empty( $days[ $i ] ) ) ? 'on' : 'off';
    }

    ksort( $settings['days'] );

    // Filter times

    $settings['times'] = array();

    if ( isset( $post['pwps_times'] ) ) {
			$settings['times'] = array_filter( $post['pwps_times'] );
		}

    usort( $settings['times'], function( $a, $b ) {
      $a = strtotime( $a );
      $b = strtotime( $b );
      return $a - $b;
    } );

    // Filter settings

    $settings = apply_filters( $this->plugin->key . '_filter_settings', $settings );

		return $settings;
	}

  public function admin_notices() {
    if ( isset( $_SESSION[ $this->plugin->key . '-settings-updated' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-settings-updated' ] );
      ?>
      <div class="notice updated is-dismissible">
        <p><strong>Settings saved.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }

    if ( isset( $_SESSION[ $this->plugin->key . '-settings-error' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-settings-error' ] );
      ?>
      <div class="notice error is-dismissible">
        <p><strong>Error saving settings.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }

    if ( isset( $_SESSION[ $this->plugin->key . '-settings-reset' ] ) ) {
      unset( $_SESSION[ $this->plugin->key . '-settings-reset' ] );
      ?>
      <div class="notice updated is-dismissible">
        <p><strong>Plugin settings reset.</strong></p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
      </div>
      <?php
    }
  }
}

Presswell_Publication_Schedule_Settings::get_instance();
