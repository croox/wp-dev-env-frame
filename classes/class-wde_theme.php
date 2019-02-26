<?php
/**
 * Theme init
 *
 * @package wde
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


abstract class Wde_Theme extends Wde_Project {

	protected $parent = '';	// ??? only child theme

    function __construct( $init_args = array() ) {
        parent::__construct( $init_args );


    	// parse init_args, apply defaults
    	$init_args = wp_parse_args( $init_args, array(
		) );

		// ??? is all exist and valid


		$this->dir_basename = basename( dirname( $init_args['FILE_CONST'] ) );		// no trailing slash
		$this->dir_url = get_theme_root_uri() . '/' . $this->dir_basename;			// no trailing slash
		$this->dir_path = trailingslashit( dirname( $init_args['FILE_CONST'] ) );	// trailing slash
		$this->FILE_CONST = $init_args['FILE_CONST'];								// file abs path

		if ( array_key_exists( 'parent', $init_args ) )
			$this->parent = $init_args['parent'];

    }

	public function initialize() {

		// activate
		$this->activate();

		// start
		$this->start();

		// on deactivate
		add_action( 'switch_theme', array( $this, 'on_deactivate' ), 10, 3 );

	}

	public function load_textdomain(){
		load_theme_textdomain(
			$this->textdomain,
			$this->get_dir_path() . 'languages'
		);
		// just a test string to ensure generated pot file will not be empty
		$test = __( 'test', $this->textdomain );
	}

	public function activate() {

		$option_key = $this->slug . '_activated';

		if ( ! $this->check_dependencies() )
			$this->deactivate();

		if ( ! get_option( $option_key ) ) {

			$this->init_options();
			$this->register_post_types_and_taxs();
			$this->add_roles_and_capabilities();

			// hook the register post type functions, because init is to late
			do_action( $this->prefix . '_on_activate_before_flush' );
			flush_rewrite_rules();
			$this->maybe_update();

			update_option( $option_key , 1 );
			do_action( $this->prefix . '_theme_activated' );

		}

	}

	public function start() {
		if ( ! $this->check_dependencies() )
			$this->deactivate();

		$this->auto_include();

		add_action( 'after_setup_theme', array( $this, 'load_textdomain' ) );
		$this->register_post_types_and_taxs();
		$this->add_roles_and_capabilities();
		$this->maybe_update();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_parent_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 100 );	// ??? if enfold 100
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		do_action( $this->prefix . '_theme_loaded' );
	}


	protected function auto_include() {
        parent->auto_include();

		if ( file_exists( $this->get_dir_path() . 'inc/' . $this->prefix . '_include_template_functions.php' ) ) {
			include_once( $this->get_dir_path() . 'inc/' . $this->prefix . '_include_template_functions.php' );
			if ( function_exists( $this->prefix . '_include_template_functions' ) ) {
				$include_function = $this->prefix . 'include_template_functions';
				$include_function();
			}
		}
		if ( file_exists( $this->get_dir_path() . 'inc/' . $this->prefix . '_include_template_tags.php' ) ) {
			include_once( $this->get_dir_path() . 'inc/' . $this->prefix . '_include_template_tags.php' );
			if ( function_exists( $this->prefix . '_include_template_tags' ) ) {
				$include_function = $this->prefix . 'include_template_tags';
				$include_function();
			}
		}

	}

	public function enqueue_scripts(){
		// if ( get_stylesheet_directory_uri() !== get_template_directory_uri() && is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		// 	wp_enqueue_script( 'comment-reply' );
		// }
	}

	public function enqueue_parent_styles(){
		// if theme is childtheme, enqueue parent style and set parent as dependency
		if ( get_stylesheet_directory_uri() !== get_template_directory_uri() ) {
			$parent_style = 'style';
			wp_enqueue_style( 'style', get_template_directory_uri() . '/style.css' );
			array_push( $this->style_deps, $parent_style );
		}
	}


	public function on_deactivate( $new_name, $new_theme, $old_theme ) {

		if ( $old_theme->get_stylesheet() != $this->slug )
			return;

		$option_key = $old_theme->get_stylesheet() . '_activated';

		delete_option( $option_key );

		flush_rewrite_rules();
		do_action( $this->prefix . '_theme_deactivated' );
	}


	public function deactivate() {
		$default = wp_get_theme( WP_DEFAULT_THEME );
		if ( $default->exists() ) {
			add_action( 'admin_notices', array( $this, 'the_deactivate_notice' ) );
			switch_theme( $default->get_stylesheet() );
		} else {
			$last_core = WP_Theme::get_core_default_theme();
			if ( $last_core ) {
				add_action( 'admin_notices', array( $this, 'the_deactivate_notice' ) );
				switch_theme( $last_core->get_stylesheet() );
			} else {
				add_action( 'admin_notices', array( $this, 'the_deactivate_error_notice' ) );
			}
		}
	}

}


?>