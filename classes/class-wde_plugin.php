<?php
/**
 * Emk plugin init
 *
 * @package wde
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Wde_Plugin extends Wde_Project {

    function __construct( $init_args = array() ) {
        parent::__construct( $init_args );


    	// parse init_args, apply defaults
    	$init_args = wp_parse_args( $init_args, array(
		) );

		// ??? is all exist and valid

		$this->dir_basename = basename( dirname( $init_args['FILE_CONST'] ) );		// no trailing slash
		$this->dir_url = plugins_url( '', $init_args['FILE_CONST'] );				// no trailing slash
		$this->dir_path = plugin_dir_path( $init_args['FILE_CONST'] );				// trailing slash
		$this->FILE_CONST = $init_args['FILE_CONST'];								// file abs path
    }

	public function initialize() {

	}

	public function hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'on_uninstall' ) );
		add_action( 'plugins_loaded', array( $this, 'start' ), 9 );
	}

	public function load_textdomain(){
		load_plugin_textdomain(
			$this->textdomain,
			false,
			$this->get_dir_basename() . '/languages'
		);
		// just a test string to ensure generated pot file will not be empty
		$test = __( 'test', $this->textdomain );
	}

	public function activate() {
		if ( $this->check_dependencies() ){
			$this->init_options();
			$this->register_post_types_and_taxs();
			$this->add_roles_and_capabilities();
			// hook the register post type functions, because init is to late
			do_action( $this->prefix . '_on_activate_before_flush' );
			flush_rewrite_rules();
			$this->maybe_update();
			do_action( $this->prefix . '_plugin_activated' );
		} else {
			add_action( 'admin_init', array( $this, 'deactivate' ) );
			wp_die(
				$this->deactivate_notice
				. '<p>The plugin will not be activated.</p>'
				. '<p><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a></p>'
			);
		}
	}

	/*
	 *
	 * Doesn't have any parameters.
	 * The Method inheritates from an abstract parent method, but actually no parameters will be passed.
	 */
	public function on_deactivate( $new_name, $new_theme, $old_theme ) {
		$this->add_roles_and_capabilities();
		do_action( $this->prefix . '_on_deactivate_before_flush' );
		flush_rewrite_rules();
		do_action( $this->prefix . '_plugin_deactivated' );
	}

	public static function on_uninstall() {
		do_action( $this->prefix . '_plugin_uninstalled' );
	}

	public function start() {
		if ( $this->check_dependencies() ){
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			$this->register_post_types_and_taxs();
			$this->maybe_update();	// I think mass a plugin update does not run activation hooks
			add_action( 'plugins_loaded', array( $this, 'auto_include' ) );
			do_action( $this->prefix . '_plugin_loaded' );
		} else {
			add_action( 'admin_init', array( $this, 'deactivate' ) );
		}

	}

	public function deactivate() {
		add_action( 'admin_notices', array( $this, 'the_deactivate_notice' ) );
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	// public function enqueue_scripts(){
	// 	// if ( get_stylesheet_directory_uri() !== get_template_directory_uri() && is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
	// 	// 	wp_enqueue_script( 'comment-reply' );
	// 	// }
	// }

}


?>