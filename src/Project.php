<?php
/**
 * project init
 *
 * @package wde
 */

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Project {

	private static $_instances = array();

	public static function get_instance( $init_args = array() ) {
		$class = get_called_class();
		if ( ! isset( self::$_instances[ $class ] ) ) {
			$new_class                  = new $class( $init_args );
			self::$_instances[ $class ] = $new_class;
			$new_class->initialize();
			$new_class->hooks();
		}
		return self::$_instances[ $class ];
	}




	protected $deps       = array();
	protected $version    = '';
	protected $db_version = 0;
	protected $slug       = '';
	protected $name       = '';
	protected $prefix     = '';
	protected $textdomain     = '';

	protected $project_kind      = '';
	protected $deactivate_notice = '';
	protected $dependencies_ok   = false;
	protected $style_deps        = array();

	protected $dir_url      = '';
	protected $dir_path     = '';
	protected $dir_basename = '';
	protected $FILE_CONST   = '';

	function __construct( $init_args = array() ) {

		// parse init_args, apply defaults
		$init_args = wp_parse_args(
			$init_args,
			array(

				'deps' => array(
					'plugins'     => array(
						/*
						'woocommerce' => array(
							'name'              => 'WooCommerce',               // full name
							'link'              => 'https://woocommerce.com/',  // link
							'ver_at_least'      => '3.0.0',                     // min version of required plugin
							'ver_tested_up_to'  => '3.2.1',                     // tested with required plugin up to
							'class'             => 'WooCommerce',               // test by class
							//'function'        => 'WooCommerce',               // test by function
						),
						*/
					),
					'php_version' => 'wde_replace_phpRequiresAtLeast',          // required php version
					'wp_version'  => 'wde_replace_wpRequiresAtLeast',            // required wp version
					'php_ext'     => array(
						/*
						'xml' => array(
							'name'              => 'Xml',                                           // full name
							'link'              => 'http://php.net/manual/en/xml.installation.php', // link
						),
						*/
					),
				),

			)
		);

		// ??? is all exist and valid
		$this->deps       = $init_args['deps'];
		$this->version    = $init_args['version'];
		$this->db_version = $init_args['db_version'];
		$this->slug       = $init_args['slug'];
		$this->name       = $init_args['name'];
		$this->prefix     = $init_args['prefix'];
		$this->textdomain = $init_args['textdomain'];

	}

	public function hooks() {}

	protected function init_options() {
		update_option( $this->prefix . '_version', $this->version );
		add_option( $this->prefix . '_db_version', $this->db_version );
	}

	// check DB_VERSION and require the update class if necessary
	protected function maybe_update() {
		if ( get_option( $this->prefix . '_db_version' ) < $this->db_version ) {
			// require_once( $this->dir_path . 'inc/class-' . $this->prefix . '_update.php' );
			// new Update();
			// class Update is missing ??? !!!
		}
	}

	protected function check_dependencies() {

		if ( ! $this->dependencies_ok ) {

			$error_msgs = array();

			// check php version
			if ( version_compare( PHP_VERSION, $this->deps['php_version'], '<' ) ) {
				$err_msg = sprintf( 'PHP version %s or higher', $this->deps['php_version'] );
				array_push( $error_msgs, $err_msg );
			}

			// check php extensions
			if ( array_key_exists( 'php_ext', $this->deps ) && is_array( $this->deps['php_ext'] ) ) {
				foreach ( $this->deps['php_ext'] as $php_ext_key => $php_ext_val ) {
					if ( ! extension_loaded( $php_ext_key ) ) {
						$err_msg = sprintf(
							'<a href="%s" target="_blank">%s</a> php extension to be installed',
							$php_ext_val['link'],
							$php_ext_val['name']
						);
						array_push( $error_msgs, $err_msg );
					}
				}
			}

			// check wp version
			// include an unmodified $wp_version
			include ABSPATH . WPINC . '/version.php';
			if ( version_compare( $wp_version, $this->deps['wp_version'], '<' ) ) {
				$err_msg = sprintf( 'WordPress version %s or higher', $this->deps['wp_version'] );
				array_push( $error_msgs, $err_msg );
			}

			// check plugin dependencies
			if ( array_key_exists( 'plugins', $this->deps ) && is_array( $this->deps['plugins'] ) ) {
				foreach ( $this->deps['plugins'] as $dep_plugin ) {
					$err_msg = sprintf(
						' <a href="%s" target="_blank">%s</a> Plugin version %s (tested up to %s)',
						$dep_plugin['link'],
						$dep_plugin['name'],
						$dep_plugin['ver_at_least'],
						$dep_plugin['ver_tested_up_to']
					);
					// check by class
					if ( array_key_exists( 'class', $dep_plugin ) && strlen( $dep_plugin['class'] ) > 0 ) {
						if ( ! class_exists( $dep_plugin['class'] ) ) {
							array_push( $error_msgs, $err_msg );
						}
					}
					// check by function
					if ( array_key_exists( 'function', $dep_plugin ) && strlen( $dep_plugin['function'] ) > 0 ) {
						if ( ! function_exists( $dep_plugin['function'] ) ) {
							array_push( $error_msgs, $err_msg );
						}
					}
				}
			}

			// maybe set deactivate_notice and return false
			if ( count( $error_msgs ) > 0 ) {
				$this->deactivate_notice = implode(
					'',
					array(
						'<h3>',
						$this->name,
						' ',
						$this->project_kind,
						' requires:</h3>',
						'<ul style="padding-left: 1em; list-style: inside disc;">',
						'<li>',
						implode( '</li><li>', $error_msgs ),
						'</li>',
						'</ul>',
					)
				);
				return false;
			}

			$this->dependencies_ok = true;
		}

		return true;
	}

	abstract public function initialize();

	/**
	 * Public getter method for retrieving protected/private variables
	 * @param  string  		$field Field to retrieve
	 * @return mixed        Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array(
			'version',
			'slug',
			'name',
			'prefix',
			'textdomain',
			'dir_url',
			'dir_path',
			'dir_basename',
			'FILE_CONST',
		), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

	public function get_dir_url() {
		error_log( 'get_dir_url() is deprecated. Use public getter method ->dir_url instead' );
		return $this->dir_url;                  // no trailing slash
	}

	public function get_dir_path() {
		error_log( 'get_dir_path() is deprecated. Use public getter method ->dir_path instead' );
		return $this->dir_path;                 // trailing slash
	}

	public function get_dir_basename() {
		error_log( 'get_dir_basename() is deprecated. Use public getter method ->dir_basename instead' );
		return $this->dir_basename;             // no trailing slash
	}

	public function get_file() {
		error_log( 'get_file() is deprecated. Use public getter method ->FILE_CONST instead' );
		return $this->FILE_CONST;               // theme file abs path
	}

	public function get_prefix() {
		error_log( 'get_prefix() is deprecated. Use public getter method ->prefix instead' );
		return $this->prefix;
	}

	public function get_textdomain() {
		error_log( 'get_textdomain() is deprecated. Use public getter method ->textdomain instead' );
		return $this->textdomain;
	}
	public function get_version() {
		error_log( 'get_version() is deprecated. Use public getter method ->version instead' );
		return $this->version;
	}

	protected function _include( $key ) {
		if ( file_exists( $this->dir_path . 'inc/' . $this->prefix . '_include_' . $key . '.php' ) ) {
			include_once $this->dir_path . 'inc/' . $this->prefix . '_include_' . $key . '.php';
			if ( function_exists( $this->prefix . '_include_' . $key . '' ) ) {
				$include_function = $this->prefix . '_include_' . $key . '';
				$include_function();
			}
		}
	}

	// include files to register post types and taxonomies
	protected function register_post_types_and_taxs() {
		// include inc/post_types_taxs/*.php
		$this->_include( 'post_types_taxs' );
	}

	// include files to add user roles and capabilities
	protected function add_roles_and_capabilities() {
		// include inc/roles_capabilities/*.php
		$this->_include( 'roles_capabilities' );
	}

	public function auto_include() {
		// include inc/fun/*.php
		$this->_include( 'fun' );
	}

	abstract public function load_textdomain();

	public function the_deactivate_notice() {
		echo implode(
			'',
			array(
				'<div class="notice error">',
				$this->deactivate_notice,
				'<p>The ',
				$this->project_kind,
				' will be deactivated.</p>',
				'</div>',
			)
		);
	}

	public function the_deactivate_error_notice() {
		echo implode(
			'',
			array(
				'<div class="notice error">',
				$this->deactivate_notice,
				'<p>An error occurred when deactivating the ',
				$this->project_kind,
				'. It needs to be deactivated manually.</p>',
				'</div>',
			)
		);
	}

	abstract public function activate();

	abstract public function start();

	abstract public function on_deactivate( $new_name, $new_theme, $old_theme );

	abstract public function deactivate();

}


