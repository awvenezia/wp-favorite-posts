<?php
/**
 * Plugin Name: WP Favorite Posts
 * Plugin URI: https://github.com/awvenezia/wp-favorite-posts
 * Description: Allows users to add favorite posts. This plugin use cookies for saving data so unregistered users can favorite a post. Put <code>&lt;?php nlsn_link(); ?&gt;</code> where ever you want on a single post. Then create a page which includes that text : <code>[wp-favorite-posts]</code> That's it!
 * Version: 1.7.6
 * Author: Alto-Palo
 * Author URI: https://github.com/awvenezia
 * 
 * @package NlsnWPFP
 */

/*
	Copyright (c) 2022 Alto-Palo (awvenezia@gmail.com)

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

define( 'NLSN_JS_VERSION', '1.7.6' );
define( 'NLSN_PATH', plugins_url( '', __FILE__ ) );
define( 'NLSN_META_KEY', 'nlsn_favorites' );
define( 'NLSN_USER_OPTION_KEY', 'nlsn_useroptions' );
define( 'NLSN_COOKIE_KEY', 'wp-favorite-posts' );
define( 'NLSN_PLUGIN_PATH', plugin_basename( __FILE__ ) );
define( 'NLSN_PLUGIN_SLUG', plugin_basename( __DIR__ ) );

// manage default privacy of users favorite post lists by adding this constant to wp-config.php.
if ( ! defined( 'NLSN_DEFAULT_PRIVACY_SETTING' ) ) {
	define( 'NLSN_DEFAULT_PRIVACY_SETTING', false );
}

$nlsn_ajax_mode    = 1;
$nlsn_site_cookies = $GLOBALS['_COOKIE'];

/**
 * Function nlsn_load_translation
 *
 * @return void
 */
function nlsn_load_translation() {
	load_plugin_textdomain(
		'nielsen',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/lang'
	);
}

add_action( 'plugins_loaded', 'nlsn_load_translation' );

/**
 * Function nlsn_wp_favorite_posts
 *
 * @return void
 */
function nlsn_wp_favorite_posts() {
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : null;
	if ( null !== $nonce && ! wp_verify_nonce( $nonce, 'nlsn-update_fav' ) ) {
		return;
	} else {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['nlsnaction'] ) ) :
			global $nlsn_ajax_mode;
			$nlsn_ajax_mode = isset( $_REQUEST['ajax'] ) ? sanitize_key( $_REQUEST['ajax'] ) : false;
			if ( 'add' === $_REQUEST['nlsnaction'] ) {
				nlsn_add_favorite();
			} elseif ( 'remove' === $_REQUEST['nlsnaction'] ) {
				nlsn_remove_favorite();
			} elseif ( 'clear' === $_REQUEST['nlsnaction'] ) {
				if ( nlsn_clear_favorites() ) {
					nlsn_die_or_go( nlsn_get_option( 'cleared' ) );
				} else {
					nlsn_die_or_go( 'ERROR' );
				}
			} elseif ( 'user-favorite-list' === $_REQUEST['nlsnaction'] ) {
				nlsn_user_favorite_list();
			}
		endif;
	}
}
add_action( 'wp_loaded', 'nlsn_wp_favorite_posts' );

/**
 * Function nlsn_add_favorite
 *
 * @param  mixed $post_id Post ID.
 * @return mixed
 */
function nlsn_add_favorite( $post_id = '' ) {
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : null;
	if ( null !== $nonce && ! wp_verify_nonce( $nonce, 'nlsn-update_fav' ) ) {
		die( esc_html( __( 'Security check failes', 'nielsen' ) ) ); 
	} else {
		if ( empty( $post_id ) && isset( $_REQUEST['postid'] ) ) {
			$post_id = sanitize_key( $_REQUEST['postid'] );
		}
		if ( nlsn_get_option( 'opt_only_registered' ) && ! is_user_logged_in() ) {
			nlsn_die_or_go( nlsn_get_option( 'text_only_registered' ) );
			return false;
		}

		if ( nlsn_do_add_to_list( $post_id ) ) {
			do_action( 'nlsn_after_add', $post_id );
			if ( nlsn_get_option( 'statistics' ) ) {
				nlsn_update_post_meta( $post_id, 1 );
			}
			if ( 'show remove link' === nlsn_get_option( 'added' ) ) {
				$str = nlsn_link( 1, 'remove', 0, array( 'post_id' => $post_id ) );
				nlsn_die_or_go( $str );
			} else {
				nlsn_die_or_go( nlsn_get_option( 'added' ) );
			}
		} else {
			nlsn_die_or_go( '' );
		}
	}
}

/**
 * Function nlsn_do_add_to_list
 *
 * @param  mixed $post_id Post ID.
 * @return mixed
 */
function nlsn_do_add_to_list( $post_id ) {
	if ( nlsn_check_favorited( $post_id ) ) {
		return false;
	}
	if ( is_user_logged_in() ) {
		return nlsn_add_to_usermeta( $post_id );
	} else {
		return nlsn_set_cookie( $post_id, 'added' );
	}
}

/**
 * Function nlsn_remove_favorite
 *
 * @param  mixed $post_id Post ID.
 * @return mixed
 */
function nlsn_remove_favorite( $post_id = '' ) {
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : null;
	if ( null !== $nonce && ! wp_verify_nonce( $nonce, 'nlsn-update_fav' ) ) {
		die( esc_html( __( 'Security check', 'nielsen' ) ) ); 
	} else {
		if ( empty( $post_id ) && isset( $_REQUEST['postid'] ) ) {
			$post_id = sanitize_key( $_REQUEST['postid'] );
		}
		if ( nlsn_do_remove_favorite( $post_id ) ) {
			do_action( 'nlsn_after_remove', $post_id );
			if ( nlsn_get_option( 'statistics' ) ) {
				nlsn_update_post_meta( $post_id, -1 );
			}
			if ( 'show add link' === nlsn_get_option( 'removed' ) ) {
				if ( isset( $_REQUEST['page'] ) && 1 === $_REQUEST['page'] ) :
					$str = '';
				else :
					$str = nlsn_link( 1, 'add', 0, array( 'post_id' => $post_id ) );
				endif;
				nlsn_die_or_go( $str );
			} else {
				nlsn_die_or_go( nlsn_get_option( 'removed' ) );
			}
		} else {
			return false;
		}
	}
}

/**
 * Function nlsn_die_or_go
 *
 * @param  mixed $str String Response Msg.
 * @return void
 */
function nlsn_die_or_go( $str ) {
	global $nlsn_ajax_mode;
	$res = array();
	$res['data'] = wp_kses_post( $str );
	$user = isset( $_REQUEST['user'] ) ? sanitize_user( $_REQUEST['user'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $args ) ) {
		foreach ( $args as $key => $val ) {
			${$key} = $val;
		}
	}
	$res['selected_report_count'] = get_selected_report_count($str);
	if ( $nlsn_ajax_mode ) :
		echo wp_json_encode($res);
		die();
	else :
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
			exit;
		}
	endif;
}

/**
 * Get count for selected reports get_selected_report_count
 *
 * @param  mixed $str
 * @return void
 */
function get_selected_report_count($str = '') {
	global $nlsn_favorite_post_ids;
	if ( ! empty( $user ) ) {
		if ( nlsn_is_user_favlist_public( $user ) ) {
			$nlsn_favorite_post_ids = nlsn_get_users_favorites( $user );
		}   
	} else {
		$nlsn_favorite_post_ids = nlsn_get_users_favorites();
		if( '' !== $str && isset( $_REQUEST['postid'] ) && isset( $_REQUEST['nlsnaction'] ) ) {
			$nlsn_post_id = sanitize_key( $_REQUEST['postid'] );
			if ( 'add' === $_REQUEST['nlsnaction'] ) {
				$nlsn_favorite_post_ids[] = $nlsn_post_id;
			} elseif ( 'remove' === $_REQUEST['nlsnaction'] ) {
				if (($key = array_search($nlsn_post_id, $nlsn_favorite_post_ids)) !== false) {
					unset($nlsn_favorite_post_ids[$key]);
				}
			} elseif ( 'clear' === $_REQUEST['nlsnaction'] ) {
				$nlsn_favorite_post_ids = array();
			}
		}
	}
	return count($nlsn_favorite_post_ids) > 0 ? count($nlsn_favorite_post_ids) : '';
}

/**
 * Function nlsn_add_to_usermeta
 *
 * @param  mixed $post_id Post ID.
 * @return bool
 */
function nlsn_add_to_usermeta( $post_id ) {
	$nlsn_favorites = nlsn_get_user_meta();
	if ( empty( $nlsn_favorites ) || ! is_array( $nlsn_favorites ) ) {
		$nlsn_favorites = array();
	}
	$nlsn_favorites[] = $post_id;
	nlsn_update_user_meta( $nlsn_favorites );
	return true;
}

/**
 * Nlsn_check_favorited
 *
 * @param  mixed $cid Post ID for check in favorite posts.
 * @return bool
 */
function nlsn_check_favorited( $cid ) {
	if ( is_user_logged_in() ) {
		$nlsn_favorite_post_ids = nlsn_get_user_meta();
		if ( $nlsn_favorite_post_ids ) {
			foreach ( $nlsn_favorite_post_ids as $fpost_id ) {
				if ( (int) $fpost_id === (int) $cid ) {
					return true;
				}
			}
		}
	} else {
		if ( nlsn_get_cookie() ) :
			foreach ( nlsn_get_cookie() as $fpost_id => $val ) {
				if ( (int) $fpost_id === (int) $cid && ! empty( $val ) ) {
					return true;
				}
			}
		endif;
	}
	return false;
}

/**
 * Function nlsn_link
 *
 * @param  mixed $return Resonse type echo or return.
 * @param  mixed $action String | Mixed variable for action name remove_favorite/add_favorite.
 * @param  mixed $show_span Show span boolean.
 * @param  mixed $args Array | Mixed Arguments.
 * @return array|string
 */
function nlsn_link( $return = 0, $action = '', $show_span = 1, $args = array() ) {
	$nlsn_post_id = get_the_ID();
	if ( empty( $nlsn_post_id ) && isset( $_REQUEST['postid'] ) ) {
		$nlsn_post_id = sanitize_key( $_REQUEST['postid'] );
	}
	if ( ! empty( $args ) ) {
		foreach ( $args as $key => $val ) {
			${$key} = $val; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		}
	}
	$str = '';
	if ( $show_span ) {
		$str = "<span class='nlsn-span'>";
	}
	if ( nlsn_get_option( 'statistics' ) && nlsn_get_option( 'show_stats' ) ) {
		$str .= "<span class='nlsn-span-stats'>" . trim( nlsn_get_post_meta( $nlsn_post_id ) ) . '</span>';
	}
	$str .= nlsn_before_link_img();
	$str .= nlsn_loading_img();
	if ( 'remove' === $action ) :
		$str .= nlsn_link_html( $nlsn_post_id, nlsn_get_option( 'remove_favorite' ), 'remove' );
	elseif ( 'add' === $action ) :
		$str .= nlsn_link_html( $nlsn_post_id, nlsn_get_option( 'add_favorite' ), 'add' );
	elseif ( nlsn_check_favorited( $nlsn_post_id ) ) :
		$str .= nlsn_link_html( $nlsn_post_id, nlsn_get_option( 'remove_favorite' ), 'remove' );
	else :
		$str .= nlsn_link_html( $nlsn_post_id, nlsn_get_option( 'add_favorite' ), 'add' );
	endif;
	if ( $show_span ) {
		$str .= '</span>';
	}
	if ( $return ) {
		return $str;
	} else {
		echo wp_kses_post( $str ); }
}

/**
 * Function nlsn_link_html
 *
 * @param  mixed $post_id Post ID.
 * @param  mixed $opt String | Mixed Option Name.
 * @param  mixed $action String | Mixed variable for action name.
 * @return string
 */
function nlsn_link_html( $post_id, $opt, $action ) {
	$act_link = '?nlsnaction=' . $action . '&amp;postid=' . esc_attr( $post_id );
	$act_link = ( function_exists( 'wp_nonce_url' ) ) ? wp_nonce_url( $act_link, 'nlsn-update_fav' ) : $act_link;
	$link     = "<a class='nlsn-link nlsn-" . $action . "' href='" . $act_link . "' title='" . $opt . "' rel='nofollow'>" . $opt . '</a>';
	$link     = apply_filters( 'nlsn_link_html', $link );
	return $link;
}

/**
 * Function nlsn_get_users_favorites
 *
 * @param  mixed $user User Object.
 * @return array
 */
function nlsn_get_users_favorites( $user = '' ) {
	$nlsn_favorite_post_ids = array();

	if ( ! empty( $user ) ) :
		return nlsn_get_user_meta( $user );
	endif;

	// collect favorites from cookie and if user is logged in from database.
	if ( is_user_logged_in() ) :
		$nlsn_favorite_post_ids = nlsn_get_user_meta();
	else :
		if ( nlsn_get_cookie() ) :
			foreach ( nlsn_get_cookie() as $post_id => $post_title ) {
				if ( ! empty( $post_title ) ) {
					array_push( $nlsn_favorite_post_ids, $post_id );
				}
			}
		endif;
	endif;
	return $nlsn_favorite_post_ids;
}

/**
 * Function nlsn_list_favorite_posts
 *
 * @param  mixed $args Array | Mixed Arguments.
 * @return mixed
 */
function nlsn_list_favorite_posts( $args = array() ) {
	$user = isset( $_REQUEST['user'] ) ? sanitize_user( $_REQUEST['user'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $args ) ) {
		foreach ( $args as $key => $val ) {
			${$key} = $val;
		}
	}
	global $nlsn_favorite_post_ids;
	if ( ! empty( $user ) ) {
		if ( nlsn_is_user_favlist_public( $user ) ) {
			$nlsn_favorite_post_ids = nlsn_get_users_favorites( $user );
		}   
	} else {
		$nlsn_favorite_post_ids = nlsn_get_users_favorites();
	}

	if ( file_exists( get_template_directory() . '/wpfp-page-template.php' ) || file_exists( get_stylesheet_directory() . '/wpfp-page-template.php' ) ) :
		if ( file_exists( get_template_directory() . '/wpfp-page-template.php' ) ) :
			include get_template_directory() . '/wpfp-page-template.php';
		else :
			include get_stylesheet_directory() . '/wpfp-page-template.php';
		endif;
	else :
		include plugin_dir_path( __FILE__ ) . '/wpfp-page-template.php';
	endif;
}

/**
 * Function nlsn_list_most_favorited
 *
 * @param  mixed $limit Limit for favorite posts list.
 * @return void
 */
function nlsn_list_most_favorited( $limit = 5 ) {
	global $wpdb;
	$cache_key = 'nlsn_most_favorite_post_list_' . $limit;
	$results   = wp_cache_get( $cache_key );
	if ( empty( $results ) && wp_cache_get( $cache_key ) ) {
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT post_id, meta_value, post_status FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id=$wpdb->posts.ID WHERE post_status='publish' AND meta_key=%s AND meta_value > 0 ORDER BY ROUND(meta_value) DESC LIMIT 0, %d", '".NLSN_META_KEY."', $limit ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		wp_cache_set( $cache_key, $results );
	}
	if ( $results ) {
		echo '<ul>';
		foreach ( $results as $o ) :
			$p = get_post( $o->post_id );
			echo '<li>';
			echo wp_kses_post( "<a href='" . esc_url( get_permalink( $o->post_id ) ) . "' title='" . esc_attr( $p->post_title ) . "'>" . esc_html( $p->post_title ) . "</a> (($o->meta_value))" );
			echo '</li>';
		endforeach;
		echo '</ul>';
	}
}

require plugin_dir_path( __FILE__ ) . '/wpfp-widgets.php';

/**
 * Function nlsn_loading_img
 *
 * @return mixed
 */
function nlsn_loading_img() {
	return "<img src='" . NLSN_PATH . "/img/loading.gif' alt='Loading' title='Loading' class='nlsn-hide nlsn-img' style='display:none;' />";
}

/**
 * Function nlsn_before_link_img
 *
 * @return mixed
 */
function nlsn_before_link_img() {
	$options = nlsn_get_options();
	$option  = $options['before_image'];
	if ( empty( $option ) ) {
		return '';
	} elseif ( 'custom' === $option ) {
		return "<img src='" . $options['custom_before_image'] . "' alt='Favorite' title='Favorite' class='nlsn-img' style='display:none;' />";
	} else {
		return "<img src='" . NLSN_PATH . '/img/' . $option . "' alt='Favorite' title='Favorite' class='nlsn-img' style='display:none;' />";
	}
}

/**
 * Function nlsn_clear_favorites
 *
 * @return bool
 */
function nlsn_clear_favorites() {
	if ( nlsn_get_cookie() ) :
		foreach ( nlsn_get_cookie() as $post_id => $val ) {
			nlsn_set_cookie( $post_id, '' );
			nlsn_update_post_meta( $post_id, -1 );
		}
	endif;
	if ( is_user_logged_in() ) {
		$nlsn_favorite_post_ids = nlsn_get_user_meta();
		if ( $nlsn_favorite_post_ids ) :
			foreach ( $nlsn_favorite_post_ids as $post_id ) {
				nlsn_update_post_meta( $post_id, -1 );
			}
		endif;
		if ( ! delete_user_meta( nlsn_get_user_id(), NLSN_META_KEY ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Function nlsn_do_remove_favorite
 *
 * @param  mixed $post_id Post ID.
 * @return bool
 */
function nlsn_do_remove_favorite( $post_id ) {
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( $_REQUEST['_wpnonce'] ) : null;
	if ( null !== $nonce && ! wp_verify_nonce( $nonce, 'nlsn-update_fav' ) ) {
		die( esc_html( __( 'Security check', 'nielsen' ) ) ); 
	} else {
		if ( ! nlsn_check_favorited( $post_id ) ) {
			return true;
		}

		$a = true;
		if ( is_user_logged_in() ) {
			$user_favorites = nlsn_get_user_meta();
			$user_favorites = array_diff( $user_favorites, array( $post_id ) );
			$user_favorites = array_values( $user_favorites );
			$a              = nlsn_update_user_meta( $user_favorites );
		}
		if ( $a && isset( $_REQUEST['postid'] ) ) {
			$a = nlsn_set_cookie( sanitize_key( $_REQUEST['postid'] ), '' );
		}
		return $a;
	}
}

/**
 * Function nlsn_content_filter
 *
 * @param  mixed $content Content HTML.
 * @return mixed
 */
function nlsn_content_filter( $content ) {
	if ( is_page() ) :
		if ( false !== strpos( $content, '{{wp-favorite-posts}}' ) ) {
			$content = str_replace( '{{wp-favorite-posts}}', nlsn_list_favorite_posts(), $content );
		}
	endif;

	if ( false !== strpos( $content, '[nlsn-link]' ) ) {
		$content = str_replace( '[nlsn-link]', nlsn_link( 1 ), $content );
	}

	if ( is_single() ) {
		if ( 'before' === nlsn_get_option( 'autoshow' ) ) {
			$content = nlsn_link( 1 ) . $content;
		} elseif ( 'after' === nlsn_get_option( 'autoshow' ) ) {
			$content .= nlsn_link( 1 );
		}
	}
	return $content;
}
add_filter( 'the_content', 'nlsn_content_filter' );

/**
 * Function nlsn_shortcode_func
 *
 * @return void
 */
function nlsn_shortcode_func() {
	nlsn_list_favorite_posts();
}
add_shortcode( 'wp-favorite-posts', 'nlsn_shortcode_func' );

/**
 * Add shortcode for showing fav count nlsn_shortcode_stats
 *
 * @param  mixed $atts Attributes for shortcode.
 * @return mixed
 */
function nlsn_shortcode_stats( $atts ) {
	$nlsn_post_id = get_queried_object_id();
	if ( empty( $nlsn_post_id ) && isset( $_REQUEST['postid'] ) ) {
		$nlsn_post_id = sanitize_key( $_REQUEST['postid'] );
	}
	if ( nlsn_get_option( 'statistics' ) ) {
		$atts = shortcode_atts(
			array(
				'post_id' => $nlsn_post_id,
				'print'   => true,
			),
			$atts,
			'nlsn-favorite-stats' 
		);
		if ( $atts['print'] ) {
			echo "<span class='nlsn-span-stats'" . sanitize_key( trim( nlsn_get_post_meta( $atts['post_id'] ) ) ) . '</span>';
		} else {
			return nlsn_get_post_meta( $atts['post_id'] );
		}
	}
}
add_shortcode( 'nlsn-favorite-stats', 'nlsn_shortcode_stats' );

add_action( 'wp_ajax_nlsn_fetch_cookies', 'nlsn_fetch_cookies' );
add_action( 'wp_ajax_nopriv_nlsn_fetch_cookies', 'nlsn_fetch_cookies' );

/**
 * Nlsn_fetch_cookies
 *
 * @return void
 */
function nlsn_fetch_cookies() {
	$result         = array();
	$result['type'] = 'error';
	$result['msg']  = 'Something went wrong while updating the globale Cookie variable.';

	if ( isset( $_REQUEST['cookie_string'] ) && ! empty( $_REQUEST['cookie_string'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		global $nlsn_site_cookies;
		$nlsn_site_cookies = nlsn_http_parse_cookie( sanitize_textarea_field( $_REQUEST['cookie_string'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $nlsn_site_cookies ) {
			$result['type'] = 'success';
			$result['msg']  = 'Successfully updated Cookies';
		}
	}

	if ( ! empty( sanitize_textarea_field( ( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) && 'xmlhttprequest' === strtolower( sanitize_textarea_field( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) {
		echo wp_json_encode( $result );
	}

	die();
}

/**
 * Nlsn_http_parse_cookie
 *
 * @param  mixed $cookie_string Cookie String to be parsed.
 * @return array
 */
function nlsn_http_parse_cookie( $cookie_string ) {
	$cookie_str_array = explode( ';', $cookie_string );
	$cookies          = array();
	$cdata            = array();
	foreach ( $cookie_str_array as $data ) {
			$cinfo    = explode( '=', $data );
			$cinfo[0] = trim( $cinfo[0] );
		if ( 'expires' === $cinfo[0] ) {
			$cinfo[1] = strtotime( $cinfo[1] );
		}
		if ( 'secure' === $cinfo[0] ) {
			$cinfo[1] = 'true';
		}
		if ( in_array( $cinfo[0], array( 'domain', 'expires', 'path', 'secure', 'comment' ), true ) ) {
				$cdata[ trim( $cinfo[0] ) ] = $cinfo[1];
		} else {
			if ( ! empty( $cinfo ) ) {
				$cdata['value']['key']   = $cinfo[0];
				$cdata['value']['value'] = $cinfo[1];
			}
		}
	}
	$cookies[] = $cdata;
	return $cookies;
}

/**
 * Function nlsn_add_js_script
 *
 * @return void
 */
function nlsn_add_js_script() {
	if ( ! nlsn_get_option( 'dont_load_js_file' ) ) {
		wp_enqueue_script( 'purify', plugin_dir_url( __FILE__ ) . 'purify.min.js', array( 'jquery' ), NLSN_JS_VERSION, true );
		wp_register_script( 'get_cookies', plugin_dir_url( __FILE__ ) . 'get_cookies.js', array( 'jquery', 'purify' ), NLSN_JS_VERSION, true );
		wp_localize_script( 'get_cookies', 'fetchCookies', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );        
		wp_register_script( 'wp_favorite_posts', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery', 'purify', 'get_cookies' ), NLSN_JS_VERSION, true );
		// enqueue the script you registered above.
		wp_enqueue_script( 'get_cookies' );
		wp_enqueue_script( 'wp_favorite_posts' );
	}
}
add_action( 'wp_print_scripts', 'nlsn_add_js_script' );

/**
 * Function nlsn_wp_print_styles
 *
 * @return void
 */
function nlsn_wp_print_styles() {
	if ( ! nlsn_get_option( 'dont_load_css_file' ) ) {
		wp_enqueue_style( 'nlsn-css', plugin_dir_url( __FILE__ ) . 'nlsn_wpfp.css', array(), NLSN_JS_VERSION, 'screen' );
	}
}
add_action( 'wp_print_styles', 'nlsn_wp_print_styles' );

/**
 * Function calls after the plugin is activated nlsn_wpfp_activate
 *
 * @return void
 */
function nlsn_wpfp_activate() {
	$nlsn_options = nlsn_get_default_wpfp_options();
	add_option( 'nlsn_options', $nlsn_options );
}

/**
 * Function calls after the plugin is deactivated nlsn_wpfp_deactivate
 *
 * @return void
 */
function nlsn_wpfp_deactivate() {
	delete_option( 'nlsn_options' );
}

register_activation_hook( NLSN_PLUGIN_PATH, 'nlsn_wpfp_activate' );
register_deactivation_hook( NLSN_PLUGIN_PATH, 'nlsn_wpfp_deactivate' );

/**
 * Get default options for plugin nlsn_get_default_wpfp_options
 *
 * @return array
 */
function nlsn_get_default_wpfp_options() {
	$nlsn_options                         = array();
	$nlsn_options['add_favorite']         = 'Add to favorites';
	$nlsn_options['added']                = 'Added to favorites!';
	$nlsn_options['remove_favorite']      = 'Remove from favorites';
	$nlsn_options['removed']              = 'Removed from favorites!';
	$nlsn_options['clear']                = 'Clear favorites';
	$nlsn_options['cleared']              = '<p>Favorites cleared!</p>';
	$nlsn_options['favorites_empty']      = 'Favorite list is empty.';
	$nlsn_options['cookie_warning']       = 'Your favorite posts saved to your browsers cookies. If you clear cookies also favorite posts will be deleted.';
	$nlsn_options['rem']                  = 'remove';
	$nlsn_options['text_only_registered'] = 'Only registered users can favorite!';
	$nlsn_options['statistics']           = 1;
	$nlsn_options['show_stats']           = 0;
	$nlsn_options['widget_title']         = '';
	$nlsn_options['widget_limit']         = 5;
	$nlsn_options['uf_widget_limit']      = 5;
	$nlsn_options['before_image']         = 'star.png';
	$nlsn_options['custom_before_image']  = '';
	$nlsn_options['dont_load_js_file']    = 0;
	$nlsn_options['dont_load_css_file']   = 0;
	$nlsn_options['post_per_page']        = 20;
	$nlsn_options['autoshow']             = '';
	$nlsn_options['opt_only_registered']  = 0;
	return $nlsn_options;
}

/**
 * Function nlsn_config
 *
 * @return void
 */
function nlsn_config() {
	include plugin_dir_path( __FILE__ ) . '/wpfp-admin.php';
}

/**
 * Function nlsn_config_page
 *
 * @return void
 */
function nlsn_config_page() {
	if ( function_exists( 'add_submenu_page' ) ) {
		add_options_page( __( 'WP Favorite Posts', 'nielsen' ), __( 'WP Favorite Posts', 'nielsen' ), 'manage_options', 'wp-favorite-posts', 'nlsn_config' );
	}
}
add_action( 'admin_menu', 'nlsn_config_page' );

/**
 * Function nlsn_update_user_meta
 *
 * @param  mixed $arr Array of favorite post ID's.
 * @return mixed
 */
function nlsn_update_user_meta( $arr ) {
	return update_user_meta( nlsn_get_user_id(), NLSN_META_KEY, $arr );
}

/**
 * Function nlsn_update_post_meta
 *
 * @param  mixed $post_id Post ID.
 * @param  mixed $val Meta value for favorite post.
 * @return mixed
 */
function nlsn_update_post_meta( $post_id, $val ) {
	$oldval = nlsn_get_post_meta( $post_id );
	if ( -1 === (int) $val && 0 === (int) $oldval ) {
		$val = 0;
	} else {
		$val = (int) $oldval + (int) $val;
	}
	return add_post_meta( $post_id, NLSN_META_KEY, $val, true ) || update_post_meta( $post_id, NLSN_META_KEY, $val );
}

/**
 * Function nlsn_delete_post_meta
 *
 * @param  mixed $post_id Post ID.
 * @return mixed
 */
function nlsn_delete_post_meta( $post_id ) {
	return delete_post_meta( $post_id, NLSN_META_KEY );
}

/**
 * Function nlsn_get_cookie
 *
 * @return mixed
 */
function nlsn_get_cookie() {
	global $nlsn_site_cookies;
	if ( ! isset( $nlsn_site_cookies ) || ! isset( $nlsn_site_cookies[ NLSN_COOKIE_KEY ] ) ) {
		return;
	}
	return $nlsn_site_cookies[ NLSN_COOKIE_KEY ];
}

/**
 * Function nlsn_get_options
 *
 * @return mixed
 */
function nlsn_get_options() {
	return get_option( 'nlsn_options' );
}

/**
 * Function nlsn_get_user_id
 *
 * @return mixed
 */
function nlsn_get_user_id() {
	$user = wp_get_current_user();
	return $user->ID;
}

/**
 * Function nlsn_get_user_meta
 *
 * @param  mixed $user User object.
 * @return mixed
 */
function nlsn_get_user_meta( $user = '' ) {
	if ( ! empty( $user ) ) :
		$userdata = get_user_by( 'login', $user );
		$user_id  = $userdata->ID;
		return get_user_meta( $user_id, NLSN_META_KEY, true );
	else :
		return get_user_meta( nlsn_get_user_id(), NLSN_META_KEY, true );
	endif;
}

/**
 * Function nlsn_get_post_meta
 *
 * @param  mixed $post_id Post ID.
 * @return mixed
 */
function nlsn_get_post_meta( $post_id ) {
	$val = get_post_meta( $post_id, NLSN_META_KEY, true );
	if ( (int) $val < 0 ) {
		$val = 0;
	}
	return $val;
}

/**
 * Function nlsn_set_cookie
 *
 * @param  mixed $post_id Post ID.
 * @param  mixed $str String Response Msg.
 * @return boolean
 */
function nlsn_set_cookie( $post_id, $str ) {
	$expire = time() + 60 * 60 * 24 * 30;
	return true;
}

/**
 * Function nlsn_is_user_favlist_public
 *
 * @param  mixed $user User object.
 * @return bool
 */
function nlsn_is_user_favlist_public( $user ) {
	$user_opts = nlsn_get_user_options( $user );
	if ( empty( $user_opts ) ) {
		return NLSN_DEFAULT_PRIVACY_SETTING;
	}
	if ( $user_opts['is_nlsn_list_public'] ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Function nlsn_get_user_options
 *
 * @param  mixed $user User object.
 * @return mixed
 */
function nlsn_get_user_options( $user ) {
	$userdata = get_user_by( 'login', $user );
	$user_id  = $userdata->ID;
	return get_user_meta( $user_id, NLSN_USER_OPTION_KEY, true );
}

/**
 * Function nlsn_is_user_can_edit
 *
 * @return bool
 */
function nlsn_is_user_can_edit() {
	if ( isset( $_REQUEST['user'] ) && sanitize_key( $_REQUEST['user'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}
	return true;
}

/**
 * Function nlsn_remove_favorite_link
 *
 * @param  mixed $post_id Post ID.
 * @return void
 */
function nlsn_remove_favorite_link( $post_id ) {
	if ( nlsn_is_user_can_edit() ) {
		$nlsn_options = nlsn_get_options();
		$class        = 'nlsn-link remove-parent';
		$act_link     = '?nlsnaction=remove&amp;page=1&amp;postid=' . esc_attr( $post_id );
		$act_link     = ( function_exists( 'wp_nonce_url' ) ) ? wp_nonce_url( $act_link, 'nlsn-update_fav' ) : $act_link;
		$link         = "<a id='rem_$post_id' class='$class' href='" . $act_link . "' title='" . nlsn_get_option( 'rem' ) . "' rel='nofollow'>" . nlsn_get_option( 'rem' ) . '</a>';
		$link         = apply_filters( 'nlsn_remove_favorite_link', $link );
		echo wp_kses_post( $link );
	}
}

/**
 * Function nlsn_clear_list_link
 *
 * @return void
 */
function nlsn_clear_list_link() {
	if ( nlsn_is_user_can_edit() ) {
		$nlsn_options = nlsn_get_options();
		echo wp_kses_post( nlsn_before_link_img() );
		echo wp_kses_post( nlsn_loading_img() );
		echo wp_kses_post( "<a class='nlsn-link nlsn-clear' href='?nlsnaction=clear' rel='nofollow'>" . nlsn_get_option( 'clear' ) . '</a>' );
	}
}

/**
 * Function nlsn_cookie_warning
 *
 * @return void
 */
function nlsn_cookie_warning() {
	if ( ! is_user_logged_in() && ! isset( $_GET['user'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo wp_kses_post( '<p>' . nlsn_get_option( 'cookie_warning' ) . '</p>' );
	endif;
}

/**
 * Function nlsn_get_option
 *
 * @param  mixed $opt Option name to get.
 * @return string
 */
function nlsn_get_option( $opt ) {
	$nlsn_options = nlsn_get_options();
	if ( is_array( $nlsn_options ) && array_key_exists( $opt, $nlsn_options ) ) {
		return htmlspecialchars_decode( stripslashes( $nlsn_options[ $opt ] ) );
	} else {
		return '';
	}
}

// User favorite list loaded using ajax to prevent cache issue.
/**
 * Function nlsn_user_favorite_list
 *
 * @return void
 */
function nlsn_user_favorite_list() {
	$options = nlsn_get_options();
	$limit   = 5;
	if ( isset( $options['uf_widget_limit'] ) ) {
		$limit = $options['uf_widget_limit'];
	}

	$nlsn_favorite_post_ids = nlsn_get_users_favorites();

	if ( file_exists( get_template_directory() . '/wpfp-your-favs-widget.php' ) ) :
		include get_template_directory() . '/wpfp-your-favs-widget.php';
	else :
		include plugin_dir_path( __FILE__ ) . '/wpfp-your-favs-widget.php';
	endif;
	die();
}

require_once plugin_dir_path( __FILE__ ) . '/class-nlsn-custom-update-checker.php';
