<?php

namespace croox\wde\utils;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Wpml utility class
 *
 * Contains static utility methods to work with wpml.
 *
 * @package  wde
 */
class Wpml {

	/**
	 * Wrapper around `update_post_meta` to update the value of a meta key
	 * for all wpml translations for the specified post.
	 *
	 * @since 0.4.0
	 * @param object	$post			Object of post which contains the field you will edit.
	 * @param string	$meta_key		The key of the custom field you will edit.
	 * @param string	$meta_value		The key of the custom field you will edit.
	 * @return mixed       				If post has translations, returns an array of `update_post_meta` return values,
	 *									otherwise returns the return value of `update_post_meta`.
	 */
	public static function update_post_meta( $post, $meta_key, $meta_value ) {
		$element_type = 'post_' . $post->post_type;
		$trid = apply_filters( 'wpml_element_trid', NULL, $post->ID , $element_type );

		if ( null === $trid ) {
			return update_post_meta( $post->ID, $meta_key, $meta_value );
		} else {
			$return = array();
			$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, $element_type );
			foreach( $translations as $lang => $translation ) {
				$return[$lang] = update_post_meta( $translation->element_id, $meta_key, $meta_value );
			}
			return $return;
		}
	}

	/**
	* Switches wpml global language for rest requests from post-edit.
	* Determinates new language based on referer language.
	*
	* Can/Should be used in combination with `call_rest_switch_language_by_tax`
	* to hook into `rest_{$taxonomy}_query` for all taxonomies.
	*
	* @since 0.4.0
	* @link https://developer.wordpress.org/reference/functions/get_terms/
	* @param array             $prepared_args  Array of arguments to be passed to get_terms().
	* @param WP_REST_Request   $request        The current request.
	* @return array            $prepared_args  Returns $prepared_args unchanged.
	*/
	public static function rest_switch_language( $prepared_args, $request ) {

		// get lang from referer url
		$referer = $request->get_headers()['referer'][0];
		$parts = parse_url( $referer );
		$query = array();
		parse_str( $parts['query'], $query );

		// get out if no lang
		if ( ! array_key_exists( 'lang', $query ) )
			return $prepared_args;

		// is referer editor ? switch_language
		if ( array_key_exists( 'action', $query ) && 'edit' === $query['action'] )
			do_action( 'wpml_switch_language', $query['lang'] );

		return $prepared_args;
	}

	/**
	 * Fix WPML global active language variable for all taxonomy REST Requests.
	 *
	 * Hooks `rest_switch_language` method into `rest_{$taxonomy}_query`
	 *
	 * Hook this method into `register_taxonomy_args`.
	 * @example: add_filter( 'register_taxonomy_args', array( 'croox\wde\utils\Wpml', 'call_rest_switch_language_by_tax' ), 10, 3 );
	 *
	 * @link https://developer.wordpress.org/reference/hooks/register_taxonomy_args/
	 * @param array             $args           Array of arguments for registering a taxonomy.
	 * @param string            $taxonomy       Taxonomy key.
	 * @param array             $object_type    Array of names of object types for the taxonomy.
	 * @return array            $args           Returns $args unchanged.
	 */
	public static function call_rest_switch_language_by_tax( $args, $taxonomy, $object_type ) {
		global $wp_filter;

		// hook rest_switch_language function into rest_{$taxonomy}_query
		if ( ! array_key_exists( "rest_{$taxonomy}_query", $wp_filter ) )
			add_filter( "rest_{$taxonomy}_query", array( __CLASS__, 'rest_switch_language' ), 10, 2 );

		return $args;
	}

}
