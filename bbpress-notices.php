<?php
/*
 * Plugin Name: bbPress Notices
 * Plugin URI:  http://pippinsplugins.com/bbpress-notices
 * Description: Allows site admins to display notices at the top of forums and topics to alert readers of important messages.
 * Author:      Pippin Williamson
 * Author URI:  http://pippinsplugins.com
 * Version:     1.0.1
 * Text Domain: bbpress-notices
 * Domain Path: /languages/
 */

class BBP_Notices {

	/**
	 * Holds the instance
	 *
	 * Ensures that only one instance of bbPress Admin Notes exists in memory at any one
	 * time and it also prevents needing to define globals all over the place.
	 *
	 * @var object
	 * @static
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * Get the instance and store the class inside it. This plugin utilises
	 * the PHP singleton design pattern.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 * @access public
	 * @uses BBP_Notices::actions()
	 * @uses BBP_Notices::filters()
	 * @return object self::$instance Instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new BBP_Notices;
			self::$instance->actions();
			self::$instance->filters();
		}

		return self::$instance;
	}

	/**
	 * Dummy constructor
	 *
	 * @since 1.0
	 * @access protected
	 * @return void
	 */
	private function __construct() { /* nothing here */ }


	/**
	 * Add all actions we need
	 *
	 * @since 1.0
	 * @access private
	 * @return void
	 */
	private function actions() {

		// Load plugin textdomain
		add_action( 'init',                             array( $this, 'load_textdomain' ) );

		// Register our post type
		add_action( 'init',                             array( $this, 'post_type'       ) );

		// Modify the post type save messages
		add_filter( 'post_updated_messages',            array( $this, 'save_messages'   ) );

		// Add Notice type to submit box
		add_action( 'post_submitbox_start',             array( $this, 'submit_box'      ) );

		// Save notice Type
		add_action( 'save_post',                        array( $this, 'save_type'       ) );

		// Add notices to the top of forums and topics
		add_action( 'bbp_template_before_forums_index', array( $this, 'show_notices'    ) );
		add_action( 'bbp_template_before_single_forum', array( $this, 'show_notices'    ) );
		add_action( 'bbp_template_before_single_topic', array( $this, 'show_notices'    ) );
	}


	/**
	 * Add all filters we need
	 *
	 * @since 1.0
	 * @access private
	 * @return void
	 */
	private function filters() {


	}


	/**
	 * Loads the plugin textdomain
	 *
	 * @since 1.0
	 * @access public
	 * @return bool
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'bbp_notices_languages', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'bbpress-notices' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'bbpress-notices', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/bbpress-notices/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/bbpress-notices folder
			load_textdomain( 'bbpress-notices', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/bbpress-notices/languages/ folder
			load_textdomain( 'bbpress-notices', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'bbpress-notices', false, $lang_dir );
		}
	}


	/**
	 * Does the reply have any notes?
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function show_notices() {

		if( $this->have_notices() ) {

			foreach( $this->have_notices() as $notice ) {

				$type = get_post_meta( $notice->ID, '_bbp_notice_type', true );

				echo "<div class='bbp-template-notice $type'>";
					echo wpautop( $notice->post_content );
				echo "</div>";
			}

		}

	}

	/**
	 * Do we have notices? Return them if we do
	 *
	 * @since 1.0
	 * @access private
	 * @return array $notices All published notices
	 */
	private function have_notices() {

		$args = apply_filters( 'bbp_notices_query_args', array(
			'post_type'   => 'bbp_notice',
			'nopaging'    => true,
			'post_status' => 'publish'
		) );

		return get_posts( $args );
	}

	/**
	 * Register our post type
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function post_type() {

		if( ! class_exists( 'bbPress' ) )
			return;

		$labels = array(
			'name'              => _x( 'Notices',                   'post type general name',  'bbpress-notices' ),
			'singular_name'     => _x( 'Notice',                    'post type singular name', 'bbpress-notices' ),
			'add_new'           => __( 'Add New',                   'bbpress-notices' ),
			'add_new_item'      => __( 'Add New Notice',            'bbpress-notices' ),
			'edit_item'         => __( 'Edit Notice',               'bbpress-notices' ),
			'new_item'          => __( 'New Notice',                'bbpress-notices' ),
			'all_items'         => __( 'Notices',                   'bbpress-notices' ),
			'view_item'         => __( 'View Notice',               'bbpress-notices' ),
			'search_items'      => __( 'Search Notices',            'bbpress-notices' ),
			'not_found'         => __( 'No Notices found',          'bbpress-notices' ),
			'not_found_in_trash'=> __( 'No Notices found in Trash', 'bbpress-notices' ),
			'parent_item_colon' => '',
			'menu_name'         => __( 'Notices',                   'bbpress-notices' )
		);

		$args = array(
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => 'edit.php?post_type=' . bbp_get_forum_post_type(),
			'query_var'         => false,
			'rewrite'           => false,
			'capabilities'      => bbp_get_forum_caps(),
			'capability_type'   => array( 'forum', 'forums' ),
			'supports'          => array( 'editor', 'title' ),
			'can_export'        => false
		);

		register_post_type( 'bbp_notice', $args );
	}

	/**
	 * Updated Messages
	 *
	 * Returns an array of with all updated messages.
	 *
	 * @since 1.0
	 * @param array $messages Post updated message
	 * @return array $messages New post updated messages
	 */
	function save_messages( $messages ) {
		global $post, $post_ID;

		$messages['bbp_notice'] = array(
			1 => __( 'Notice updated.',   'bbpress-notices' ),
			4 => __( 'Notice updated.',   'bbpress-notices' ),
			6 => __( 'Notice published.', 'bbpress-notices' ),
			7 => __( 'Notice saved.',     'bbpress-notices' ),
			8 => __( 'Notice submitted.', 'bbpress-notices' )
		);

		return $messages;
	}

	/**
	 * Add "Type" drop down to the submit box
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function submit_box() {
		global $post;

		if ( ! is_object( $post ) )
			return;

		if( 'bbp_notice' != $post->post_type )
			return;

		$type = get_post_meta( $post->ID, '_bbp_notice_type', true );

		echo '<div id="bbp_notice_type_wrap">';
			echo '<label for="bbp_notice_type">' . __( 'Type:', 'bbpress-notices' )   . '</label>&nbsp;';
			echo '<select name="bbp_notice_type" id="bbp_notice_type">';
				echo '<option value="0">'        . __( 'Default', 'bbpress-notices' ) . '</option>';
				echo '<option value="info"'      . selected( 'info', $type, false )   . '>'     . __( 'Info', 'bbpress-notices' )  . '</option>';
				echo '<option value="error"'     . selected( 'error', $type, false )  . '>'     . __( 'Error', 'bbpress-notices' ) . '</option>';
			echo '</select>';
		echo '</div>';
	}

	/**
	 * Saves the Type option
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function save_type( $post_id = 0 ) {

		if( 'bbp_notice' != get_post_type( $post_id ) )
			return;

		if ( ! empty( $_POST['bbp_notice_type'] ) ) {
			update_post_meta( $post_id, '_bbp_notice_type', $_POST['bbp_notice_type'] );
		} else {
			delete_post_meta( $post_id, '_bbp_notice_type' );
		}
	}

}


/**
 * Load our class.
 * Attached to plugins_loaded to ensure bbPress is loaded first
 *
 * @since 1.0
 * @access public
 * @return void
 */
function bbp_notices_load() {
	$bbp_notices = BBP_Notices::instance();
}
add_action( 'plugins_loaded', 'bbp_notices_load', 999 );