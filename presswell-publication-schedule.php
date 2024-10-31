<?php
/**
* Plugin Name: Presswell Publication Schedule
* Description: Simple and precise post publishing control for WordPress based on day of the week and time of day.
* Author: Presswell
* Version: 1.0.2
* Plugin URI: https://wordpress.org/plugins/presswell-publication-schedule
* Author URI: https://presswell.co
* License: GNU General Public License v2.0 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html
*
* @package Presswell Publication Schedule
* @author Presswell
*
* Presswell Publication Schedule is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 2 of the License, or
* any later version.
*
* Presswell Publication Schedule is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Presswell Publication Schedule. If not, see <http://www.gnu.org/licenses/>.
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Presswell_Publication_Schedule {

  protected static $instance;

	public $version = '1.0.1';
	public $name = 'Presswell Publication Schedule';
	public $slug = 'presswell-publication-schedule';
  public $key = 'pwps';
	public $file = __FILE__;
  public $mode = 'basic';
  public $settings;

  public static function get_instance() {
		if ( empty( self::$instance ) && ! ( self::$instance instanceof Presswell_Publication_Schedule ) ) {
			self::$instance = new Presswell_Publication_Schedule();
		}

		return self::$instance;
	}

  public function __construct() {
    do_action( $this->key . '_pre_init' );

    add_action( 'init', array( $this, 'init' ) );

    add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

    add_filter( 'wp_insert_post_data', array( $this, 'schedule_post' ), 999, 2 );

    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_resources' ) );

    add_action( 'admin_notices', array( $this, 'admin_notices' ) );

    add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );

    add_action( 'wp_ajax_pwps_force_publish', array( $this, 'ajax_force_publish' ) );
  }

  // Setup

  public function load_plugin_textdomain() {
    load_plugin_textdomain( $this->slug );
  }

  public function init() {
    do_action( $this->key . '_init' );

    $this->load_settings();

    $this->require_pro();

    if ( is_admin() ) {
      $this->require_admin();
    }

    $this->require_global();

    do_action( $this->key . '_loaded' );
  }

  public function admin_enqueue_resources() {
    if ( $this->supported_post_type() || ( isset( $_GET['page'] ) && strpos( $_GET['page'], $this->slug ) > -1 ) ) {
      wp_enqueue_style( $this->slug . '-admin-css', $this->get_url() . 'assets/css/admin.css' );

      wp_enqueue_script( $this->slug . '-admin-js', $this->get_url() . 'assets/js/admin.js', array( 'jquery' ) );

      global $pagenow;

      $data = array(
        'page_now' => $pagenow,
        'post_type' => $this->current_post_type(),
        'can_publish' => current_user_can( 'edit_others_posts' ),
        'translations' => array(
          'publish' => __( 'Publish' , $this->slug ),
          'publishing' => __( 'Publishing' , $this->slug ),
          'schedule' => __( 'On schedule', $this->slug ),
          'override' => __( 'Override Publication Schedule', $this->slug ),
          'confirm' => __( 'Are you sure you want to publish this post now?', $this->slug ),
        ),
      );

      if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
        $data['post_status'] = get_post_status();
      }

      wp_localize_script( $this->slug . '-admin-js', 'PWPS_POST_DATA', $data );

      wp_localize_script( $this->slug . '-admin-js', 'wpApiSettings', array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
    }
  }

  public function get_path() {
    return plugin_dir_path( $this->file );
  }

  public function get_url() {
    return plugin_dir_url( $this->file );
  }

  public function load_settings() {
    if ( ! get_option( $this->key . '_settings', false ) ) {
      $this->set_default_settings();
    }

    $this->settings = get_option( $this->key . '_settings' );
  }

  public function require_pro() {
    $path = $this->get_path();
    $file = $path . 'includes/pro/pro.php';

    if ( file_exists( $file ) ) {
      require $file;
    }
  }

  public function require_admin() {
    $path = $this->get_path();

    require $path . 'includes/admin/settings.php';
  }

  public function require_global() {
  }

  public function post_edit_screen() {
    global $pagenow;

    $pages = array(
      'post.php',
      'post-new.php',
    );

    if ( in_array( $pagenow, $pages ) !== false ) {
      return true;
    }

    return false;
  }

  public function supported_post_type() {
    $post_type = $this->current_post_type();

    if ( in_array( $post_type, $this->settings['post_types'] ) !== false ) {
      return true;
    }

    return false;
  }

  function current_post_type() {
    global $post, $typenow, $current_screen;

    if ( $post && $post->post_type ) {
      return $post->post_type;
    } elseif ( $typenow ) {
      return $typenow;
    } elseif ( $current_screen && $current_screen->post_type ) {
      return $current_screen->post_type;
    } elseif ( isset( $_REQUEST['post_type'] ) ) {
      return sanitize_key( $_REQUEST['post_type'] );
    } elseif ( isset( $_REQUEST['post'] ) ) {
      return get_post_type( $_REQUEST['post'] );
    } elseif ( isset( $_REQUEST['page'] ) && strpos( $_REQUEST['page'], $this->slug ) > -1 ) {
      $parts = explode( '__', $_REQUEST['page'] );
      if ( isset( $parts[1] ) ) {
        return sanitize_key( $parts[1] );
      }
    }

    return null;
  }

  // Functions

  public function schedule_post( $data, $postarr ) {
    global $pagenow;

    if ( ! $this->supported_post_type() ) {
      return $data;
    }

    $skip_statuses = array(
      'auto-draft',
      'draft',
      'pending',
      'trash',
    );

    $draft_statuses = array(
      'draft',
      'pending',
    );

    $force_publish = ( isset($_GET['action']) && $_GET['action'] == 'pwps_force_publish');

    // Return if force publishing
    if ( $force_publish && current_user_can( 'edit_others_posts' ) ) {
      return $data;
    }

    date_default_timezone_set( get_option( 'timezone_string' ) );

    $_now = time();

    $post_date = date_i18n( 'Y-m-d H:i:00', strtotime( $data['post_date'] ) );
		$post_date_gmt = date_i18n( 'Y-m-d H:i:00', strtotime( $data['post_date_gmt'] ) );

    $post_time = strtotime( $post_date );
		$post_time_gmt = strtotime( $post_date_gmt );
		$post_time_diff = $post_time_gmt - $post_time;

    $original_post = get_post( $postarr['ID'] );

    if ( isset( $data['post_status'] ) ) {

      // Return if auto-draft or trash
      if ( in_array( $data['post_status'], $skip_statuses ) !== false ) {
		    return $data;
      }

      // Return if draft or pending
      if ( $pagenow !== 'post-new.php' && in_array( $data['post_status'], $draft_statuses ) !== false ) {
		    return $data;
      }

      // Return if existing and date has not changed
      if ( $original_post && $original_post->post_status !== 'auto-draft' && $original_post->post_date == $data['post_date'] ) {
        return $data;
      }

      // Return if can publish & currently overriding
      if ( current_user_can( 'edit_others_posts' ) && isset( $_POST['pwps_override'] ) && $_POST['pwps_override'] == 'on' ) {
        return $data;
      }

      // Can not publish & No schedule set -> set time
      // Can not publish & Schedule set -> verify time **

      // Can publish & No schedule set -> set time
      // Can publish & Schedule set -> verify time **
		}

    $time_slot = $this->get_timeslot( $postarr['ID'] );

    $publish_time = strtotime( $time_slot );
    $publish_time_gmt = $publish_time + $post_time_diff;

		$data['post_status'] = 'future';
		$data['post_date'] = date_i18n( 'Y-m-d H:i:00', $publish_time );
		$data['post_date_gmt'] = date_i18n( 'Y-m-d H:i:00', $publish_time_gmt, true );

    date_default_timezone_set( 'UTC' );

    return $data;
  }

  public function get_timeslot( $post_id = false ) {
    $args = array(
			'post_type' => 'post',
			'post_status' => 'future',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'ASC',
		);

    if ( ! empty( $post_id ) ) {
      $args['post__not_in'] = array( $post_id );
    }

    $scheduled_posts = get_posts( $args );
    $scheduled_times = array();

    foreach ( $scheduled_posts as $post ) {
      $scheduled_times[] = $post->post_date;
    }

    $_now = time();
    $base_time = $_now;
    $base_date = date_i18n( 'Y-m-d', $base_time );
    $base_dow = date_i18n( 'w', $base_time );
    $current_slot = '';

    $schedule = $this->get_schedule();

    // Loops full days until we have a slot
    while ( empty( $current_slot ) ) {
      // Make sure the day is active
      if ( $this->settings['days'][ $base_dow ] == 'on' ) {
        // Loop each time for day to find slot
        foreach ( $schedule[ $base_dow ] as $time ) {
          $_date = $base_date . ' ' . $time;
          $check_time = strtotime( $_date );
          $check_date = date_i18n( 'Y-m-d H:i:00', $check_time );

          if ( $check_time < $_now ) {
            continue;
          }

          if ( ! in_array( $check_date, $scheduled_times ) ) {
            $current_slot = $check_date;
            break;
          }
        }
      }

      // Time to check the next day
      $base_time += (60 * 60 * 24);
      $base_date = date_i18n( 'Y-m-d', $base_time );
      $base_dow = date_i18n( 'w', $base_time );
    }

    return $current_slot;
  }

  public function get_publish_now_link( $post_id ) {
    $html = '';

    $html .= '<a href="#" data-query="?action=pwps_force_publish&amp;post_id=' . $post_id . '" class="pwps-force-publish">';
    $html .= __( 'Publish now', $this->slug );
    $html .= '</a>';

    return $html;
  }

  public function post_row_actions( $actions, $post ) {
		if ( $this->supported_post_type() && $post->post_status == 'future' && current_user_can( 'edit_others_posts' ) ) {
			$actions['pwps_force_publish'] = $this->get_publish_now_link( $post->ID );
		}

		return $actions;
	}

  function admin_notices() {
    global $pagenow;

    if ( $pagenow == 'post.php' ) {
      $post_id = $_GET['post'];
      $post = get_post( $post_id );

      if ( $post->post_status == 'future' ) {
        ?>
        <div class="notice notice-info">
          <p>
            <?php echo __( 'Post scheduled for: ', $this->slug ); ?>
            <b><?php echo date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) ); ?>.</b>
            <?php
              if ( current_user_can( 'edit_others_posts' ) ) {
                echo $this->get_publish_now_link( $post->ID );
              }
            ?>
          </p>
        </div>
        <?php
      }
    }
  }

  public function ajax_force_publish() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die();
		}

    date_default_timezone_set( get_option( 'timezone_string' ) );

		$time = time();

    $post_id = $_GET['post_id'];
    $post = get_post( $post_id );

    $post_time = strtotime( $post->post_date );
		$post_time_gmt = strtotime( $post->post_date_gmt );
		$post_time_diff = $post_time_gmt - $post_time;

		$args = array(
			'ID' => $post_id,
			'post_date' => date_i18n( 'Y-m-d H:i:00', $time ),
      'post_date_gmt' => date_i18n( 'Y-m-d H:i:00', $time + $post_time_diff, true ),
			'post_status' => 'publish',
		);

    date_default_timezone_set( 'UTC' );

    $success = wp_update_post( $args );

    if ( $success == 0 ) {
			echo json_encode( array( 'error' => __( 'error', $this->slug ) ) );
		} else {
			echo json_encode( array( 'success' => __( 'success', $this->slug ) ) );
		}

    wp_die();
	}

  public function set_default_settings() {
    $settings = array(
      'mode' => 'basic',
      'post_types' => array(
        'post',
      ),
      'times' => array(
        '10:00am',
        '12:00pm',
      ),
      'days' => array(
        '0' => 'on', // S
        '1' => 'on', // M
        '2' => 'on', // T
        '3' => 'on', // W
        '4' => 'on', // T
        '5' => 'on', // F
        '6' => 'on', // S
      ),
    );

    $this->update_settings( $settings );
  }

  public function update_settings( $settings = array() ) {
    update_option( $this->key . '_settings', $settings );
  }

  public function get_schedule() {
    $schedule = array();

    foreach ( $this->settings['days'] as $i => $day ) {
      $schedule[ $i ] = array();

      if ( $day == 'on' ) {
        foreach ( $this->settings['times'] as $time ) {
          $schedule[ $i ][] = $time;
        }
      }
    }

    apply_filters( $this->key . '_schedule', $schedule );

    return $schedule;
  }

}

// Activation

register_activation_hook( __FILE__, 'presswell_publication_schedule_activation_hook' );

function presswell_publication_schedule_activation_hook( $network_wide ) {
  global $wp_version;

  if ( version_compare( $wp_version, '4.0', '<' ) && ! defined( 'PRESSWELL_PUBLICATION_SCHEDULE_FORCE_ACTIVATION' ) ) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( sprintf( __( 'Sorry, but your version of WordPress does not meet Presswell Publication Schedule\'s minimum required version of <strong>4.0</strong> to run properly. The plugin has been deactivated. <a href="%s">Click here to return to the Dashboard</a>.', $this->slug ), get_admin_url() ) );
  }
}

// Instance

PressWell_Publication_Schedule::get_instance();
