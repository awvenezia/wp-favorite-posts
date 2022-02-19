<?php
/*
Plugin Name: WP Favorite Posts
Plugin URI: https://github.com/awvenezia/wp-favorite-posts
Description: Allows users to add favorite posts. This plugin use cookies for saving data so unregistered users can favorite a post. Put <code>&lt;?php wpfp_link(); ?&gt;</code> where ever you want on a single post. Then create a page which includes that text : <code>[wp-favorite-posts]</code> That's it!
Version: 1.6.9
Author: Alto-Palo
Author URI: https://github.com/awvenezia

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

define('WPFP_JS_VERSION', "1.6.9");
define('WPFP_PATH', plugins_url() . '/wp-favorite-posts');
define('WPFP_META_KEY', "wpfp_favorites");
define('WPFP_USER_OPTION_KEY', "wpfp_useroptions");
define('WPFP_COOKIE_KEY', "wp-favorite-posts");

// manage default privacy of users favorite post lists by adding this constant to wp-config.php
if ( !defined( 'WPFP_DEFAULT_PRIVACY_SETTING' ) )
    define( 'WPFP_DEFAULT_PRIVACY_SETTING', false );

$ajax_mode = 1;
$site_cookies = $GLOBALS['_COOKIE'];

function wpfp_load_translation() {
    load_plugin_textdomain(
        "wp-favorite-posts",
        false,
        dirname(plugin_basename(__FILE__)).'/lang'
    );
}

add_action( 'plugins_loaded', 'wpfp_load_translation' );

function wp_favorite_posts() {
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_key($_REQUEST['_wpnonce']) : null;
    if ( null == $nonce && ! wp_verify_nonce( $nonce, 'wpfp-update_fav' ) ) {
        return;
    } else {
        if (isset($_REQUEST['_wpnonce']) && isset($_REQUEST['wpfpaction'])):
            global $ajax_mode;
            $ajax_mode = isset($_REQUEST['ajax']) ? sanitize_key($_REQUEST['ajax']) : false;
            if ($_REQUEST['wpfpaction'] == 'add') {
                wpfp_add_favorite();
            } else if ($_REQUEST['wpfpaction'] == 'remove') {
                wpfp_remove_favorite();
            } else if ($_REQUEST['wpfpaction'] == 'clear') {
                if (wpfp_clear_favorites()) wpfp_die_or_go(wpfp_get_option('cleared'));
                else wpfp_die_or_go("ERROR");
            } else if ($_REQUEST['wpfpaction'] == 'user-favorite-list') {
                wpfp_user_favorite_list();
            }
        endif;
    }
}
add_action('wp_loaded', 'wp_favorite_posts');

function wpfp_add_favorite($post_id = "") {
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_key($_REQUEST['_wpnonce']) : null;
    if ( ! wp_verify_nonce( $nonce, 'wpfp-update_fav' ) ) {
        die( esc_html(__( 'Security check failes', 'wp-favorite-posts' )) ); 
    } else {
        if ( empty($post_id) && isset($_REQUEST['postid']) ) $post_id = sanitize_key($_REQUEST['postid']);
        if (wpfp_get_option('opt_only_registered') && !is_user_logged_in() ) {
            wpfp_die_or_go(wpfp_get_option('text_only_registered') );
            return false;
        }

        if (wpfp_do_add_to_list($post_id)) {
            // added, now?
            do_action('wpfp_after_add', $post_id);
            if (wpfp_get_option('statistics')) wpfp_update_post_meta($post_id, 1);
            if (wpfp_get_option('added') == 'show remove link') {
                $str = wpfp_link(1, "remove", 0, array( 'post_id' => $post_id ) );
                wpfp_die_or_go($str);
            } else {
                wpfp_die_or_go(wpfp_get_option('added'));
            }
        }
    }
}
function wpfp_do_add_to_list($post_id) {
    if (wpfp_check_favorited($post_id))
        return false;
    if (is_user_logged_in()) {
        return wpfp_add_to_usermeta($post_id);
    } else {
        return wpfp_set_cookie($post_id, "added");
    }
}

function wpfp_remove_favorite($post_id = "") {
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_key($_REQUEST['_wpnonce']) : null;
    if ( ! wp_verify_nonce( $nonce, 'wpfp-update_fav' ) ) {
        die( esc_html(__( 'Security check', 'textdomain' )) ); 
    } else {
        if (empty($post_id) && isset($_REQUEST['postid'])) $post_id = sanitize_key($_REQUEST['postid']);
        if (wpfp_do_remove_favorite($post_id)) {
            // removed, now?
            do_action('wpfp_after_remove', $post_id);
            if (wpfp_get_option('statistics')) wpfp_update_post_meta($post_id, -1);
            if (wpfp_get_option('removed') == 'show add link') {
                if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 1 ):
                    $str = '';
                else:
                    $str = wpfp_link(1, "add", 0, array( 'post_id' => $post_id ) );
                endif;
                wpfp_die_or_go($str);
            } else {
                wpfp_die_or_go(wpfp_get_option('removed'));
            }
        }
        else return false;
    }
}

function wpfp_die_or_go($str) {
    global $ajax_mode;
    if ($ajax_mode):
        die(wp_kses_data($str));
    else:
        if(isset($_SERVER['HTTP_REFERER'])) wp_redirect($_SERVER['HTTP_REFERER']);
        exit;
    endif;
}

function wpfp_add_to_usermeta($post_id) {
    $wpfp_favorites = wpfp_get_user_meta();
    if(empty($wpfp_favorites) or !is_array($wpfp_favorites)) $wpfp_favorites = array();
    $wpfp_favorites[] = $post_id;
    wpfp_update_user_meta($wpfp_favorites);
    return true;
}

function wpfp_check_favorited($cid) {
    if (is_user_logged_in()) {
        $favorite_post_ids = wpfp_get_user_meta();
        if ($favorite_post_ids)
            foreach ($favorite_post_ids as $fpost_id)
                if ($fpost_id == $cid) return true;
	} else {
	    if (wpfp_get_cookie()):
	        foreach (wpfp_get_cookie() as $fpost_id => $val)
	            if ($fpost_id == $cid && !empty($val)) return true;
	    endif;
	}
    return false;
}

function wpfp_link( $return = 0, $action = "", $show_span = 1, $args = array() ) {
    global $post;
    $post_id = &$post->ID;
    extract($args);
    $str = "";
    if ($show_span)
        $str = "<span class='wpfp-span'>";
    $str .= wpfp_before_link_img();
    $str .= wpfp_loading_img();
    if ($action == "remove"):
        $str .= wpfp_link_html($post_id, wpfp_get_option('remove_favorite'), "remove");
    elseif ($action == "add"):
        $str .= wpfp_link_html($post_id, wpfp_get_option('add_favorite'), "add");
    elseif (wpfp_check_favorited($post_id)):
        $str .= wpfp_link_html($post_id, wpfp_get_option('remove_favorite'), "remove");
    else:
        $str .= wpfp_link_html($post_id, wpfp_get_option('add_favorite'), "add");
    endif;
    if ($show_span)
        $str .= "</span>";
    if ($return) { return $str; } else { echo wp_kses($str); }
}

function wpfp_link_html($post_id, $opt, $action) {
    $act_link = '?wpfpaction='.$action.'&amp;postid='.esc_attr($post_id);
    $act_link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($act_link, 'wpfp-update_fav') : $act_link;
    $link = "<a class='wpfp-link' href='".$act_link."' title='". $opt ."' rel='nofollow'>". $opt ."</a>";
    $link = apply_filters( 'wpfp_link_html', $link );
    return $link;
}

function wpfp_get_users_favorites($user = "") {
    $favorite_post_ids = array();

    if (!empty($user)):
        return wpfp_get_user_meta($user);
    endif;

    # collect favorites from cookie and if user is logged in from database.
    if (is_user_logged_in()):
        $favorite_post_ids = wpfp_get_user_meta();
	else:
	    if (wpfp_get_cookie()):
	        foreach (wpfp_get_cookie() as $post_id => $post_title) {
                if(!empty($post_title)) array_push($favorite_post_ids, $post_id);
	        }
	    endif;
	endif;
    return $favorite_post_ids;
}

function wpfp_list_favorite_posts( $args = array() ) {
    $user = isset($_REQUEST['user']) ? sanitize_user($_REQUEST['user']) : "";
    extract($args);
    global $favorite_post_ids;
    if ( !empty($user) ) {
        if ( wpfp_is_user_favlist_public($user) )
            $favorite_post_ids = wpfp_get_users_favorites($user);

    } else {
        $favorite_post_ids = wpfp_get_users_favorites();
    }

	if ( @file_exists(TEMPLATEPATH.'/wpfp-page-template.php') || @file_exists(STYLESHEETPATH.'/wpfp-page-template.php') ):
        if(@file_exists(TEMPLATEPATH.'/wpfp-page-template.php')) :
            include(TEMPLATEPATH.'/wpfp-page-template.php');
        else :
            include(STYLESHEETPATH.'/wpfp-page-template.php');
        endif;
    else:
        include("wpfp-page-template.php");
    endif;
}

function wpfp_list_most_favorited($limit=5) {
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value, post_status FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id=$wpdb->posts.ID WHERE post_status='publish' AND meta_key=%s AND meta_value > 0 ORDER BY ROUND(meta_value) DESC LIMIT 0, %d", '".WPFP_META_KEY."', $limit));
    if ($results) {
        echo "<ul>";
        foreach ($results as $o):
            $p = get_post($o->post_id);
            echo "<li>";
            echo wp_kses("<a href='".esc_url(get_permalink($o->post_id))."' title='". esc_attr($p->post_title) ."'>" . esc_html($p->post_title) . "</a> (($o->meta_value))");
            echo "</li>";
        endforeach;
        echo "</ul>";
    }
}

include("wpfp-widgets.php");

function wpfp_loading_img() {
    return "<img src='".WPFP_PATH."/img/loading.gif' alt='Loading' title='Loading' class='wpfp-hide wpfp-img' />";
}

function wpfp_before_link_img() {
    $options = wpfp_get_options();
    $option = $options['before_image'];
    if ($option == '') {
        return "";
    } else if ($option == 'custom') {
        return "<img src='" . $options['custom_before_image'] . "' alt='Favorite' title='Favorite' class='wpfp-img' />";
    } else {
        return "<img src='". WPFP_PATH . "/img/" . $option . "' alt='Favorite' title='Favorite' class='wpfp-img' />";
    }
}

function wpfp_clear_favorites() {
    if (wpfp_get_cookie()):
        foreach (wpfp_get_cookie() as $post_id => $val) {
            wpfp_set_cookie($post_id, "");
            wpfp_update_post_meta($post_id, -1);
        }
    endif;
    if (is_user_logged_in()) {
        $favorite_post_ids = wpfp_get_user_meta();
        if ($favorite_post_ids):
            foreach ($favorite_post_ids as $post_id) {
                wpfp_update_post_meta($post_id, -1);
            }
        endif;
        if (!delete_user_meta(wpfp_get_user_id(), WPFP_META_KEY)) {
            return false;
        }
    }
    return true;
}

function wpfp_do_remove_favorite($post_id) {
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_key($_REQUEST['_wpnonce']) : null;
    if ( ! wp_verify_nonce( $nonce, 'wpfp-update_fav' ) ) {
        die( esc_html(__( 'Security check', 'textdomain' )) ); 
    } else {
        if (!wpfp_check_favorited($post_id))
            return true;

        $a = true;
        if (is_user_logged_in()) {
            $user_favorites = wpfp_get_user_meta();
            $user_favorites = array_diff($user_favorites, array($post_id));
            $user_favorites = array_values($user_favorites);
            $a = wpfp_update_user_meta($user_favorites);
        }
        if ($a && isset($_REQUEST['postid'])) $a = wpfp_set_cookie(sanitize_key($_REQUEST['postid']), "");
        return $a;
    }
}

function wpfp_content_filter($content) {
    if (is_page()):
        if (strpos($content,'{{wp-favorite-posts}}')!== false) {
            $content = str_replace('{{wp-favorite-posts}}', wpfp_list_favorite_posts(), $content);
        }
    endif;

    if (strpos($content,'[wpfp-link]')!== false) {
        $content = str_replace('[wpfp-link]', wpfp_link(1), $content);
    }

    if (is_single()) {
        if (wpfp_get_option('autoshow') == 'before') {
            $content = wpfp_link(1) . $content;
        } else if (wpfp_get_option('autoshow') == 'after') {
            $content .= wpfp_link(1);
        }
    }
    return $content;
}
add_filter('the_content','wpfp_content_filter');

function wpfp_shortcode_func() {
    wpfp_list_favorite_posts();
}
add_shortcode('wp-favorite-posts', 'wpfp_shortcode_func');

add_action("wp_ajax_fetch_cookies", "fetch_cookies");
add_action("wp_ajax_nopriv_fetch_cookies", "fetch_cookies");

function fetch_cookies() {
    $result['type'] = "error";
    $result['msg'] = "Something went wrong while updating the globale Cookie variable.";

    if(isset($_REQUEST['cookie_string']) && !empty($_REQUEST['cookie_string'])) {
        global $site_cookies;
        $site_cookies = http_parse_cookie(sanitize_textarea_field($_REQUEST['cookie_string']));
        if($site_cookies){
            $result['type'] = "success";
            $result['msg'] = "Successfully updated Cookies";
        }
    }

    if(!empty(sanitize_textarea_field(($_SERVER['HTTP_X_REQUESTED_WITH']))) && strtolower(sanitize_textarea_field($_SERVER['HTTP_X_REQUESTED_WITH'])) == 'xmlhttprequest') {
        echo wp_json_encode($result);
    }

    die();
}

function http_parse_cookie($cookie_string) {
    $cookie_str_array = explode(";",$cookie_string);
    $cookies = array();
    $cdata = array();
    foreach( $cookie_str_array as $data ) {
            $cinfo = explode( '=', $data );
            $cinfo[0] = trim( $cinfo[0] );
            if( $cinfo[0] == 'expires' ) $cinfo[1] = strtotime( $cinfo[1] );
            if( $cinfo[0] == 'secure' ) $cinfo[1] = "true";
            if( in_array( $cinfo[0], array( 'domain', 'expires', 'path', 'secure', 'comment' ) ) ) {
                    $cdata[trim( $cinfo[0] )] = $cinfo[1];
            }
            else {
                if(!empty($cinfo)){
                    $cdata['value']['key'] = $cinfo[0];
                    $cdata['value']['value'] = $cinfo[1];
                }
            }
    }
    $cookies[] = $cdata;
    return $cookies;
}

function wpfp_add_js_script() {
	if (!wpfp_get_option('dont_load_js_file')) {
		wp_enqueue_script( "wp-favorite-posts", WPFP_PATH . "/script.js", array( 'jquery' ), WPFP_JS_VERSION );
        // Register the JS file with a unique handle, file location, and an array of dependencies
        wp_register_script( "get_cookies", plugin_dir_url(__FILE__).'get_cookies.js', array('jquery') );
        // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
        wp_localize_script( 'get_cookies', 'fetchCookies', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        // enqueue the script you registered above
        wp_enqueue_script( 'get_cookies' );
    }
}
add_action('wp_print_scripts', 'wpfp_add_js_script');

function wpfp_wp_print_styles() {
	if (!wpfp_get_option('dont_load_css_file'))
		echo wp_kses_post("<link rel='stylesheet' id='wpfp-css' href='" . WPFP_PATH . "/wpfp.css' type='text/css' />" . "\n");
}
add_action('wp_print_styles', 'wpfp_wp_print_styles');

function wpfp_init() {
    $wpfp_options = array();
    $wpfp_options['add_favorite'] = "Add to favorites";
    $wpfp_options['added'] = "Added to favorites!";
    $wpfp_options['remove_favorite'] = "Remove from favorites";
    $wpfp_options['removed'] = "Removed from favorites!";
    $wpfp_options['clear'] = "Clear favorites";
    $wpfp_options['cleared'] = "<p>Favorites cleared!</p>";
    $wpfp_options['favorites_empty'] = "Favorite list is empty.";
    $wpfp_options['cookie_warning'] = "Your favorite posts saved to your browsers cookies. If you clear cookies also favorite posts will be deleted.";
    $wpfp_options['rem'] = "remove";
    $wpfp_options['text_only_registered'] = "Only registered users can favorite!";
    $wpfp_options['statistics'] = 1;
    $wpfp_options['widget_title'] = '';
    $wpfp_options['widget_limit'] = 5;
    $wpfp_options['uf_widget_limit'] = 5;
    $wpfp_options['before_image'] = 'star.png';
    $wpfp_options['custom_before_image'] = '';
    $wpfp_options['dont_load_js_file'] = 0;
    $wpfp_options['dont_load_css_file'] = 0;
    $wpfp_options['post_per_page'] = 20;
    $wpfp_options['autoshow'] = '';
    $wpfp_options['opt_only_registered'] = 0;
    add_option('wpfp_options', $wpfp_options);
}
add_action('activate_wp-favorite-posts/wp-favorite-posts.php', 'wpfp_init');

function wpfp_config() { include('wpfp-admin.php'); }

function wpfp_config_page() {
    if ( function_exists('add_submenu_page') )
        add_options_page(__('WP Favorite Posts'), __('WP Favorite Posts'), 'manage_options', 'wp-favorite-posts', 'wpfp_config');
}
add_action('admin_menu', 'wpfp_config_page');

function wpfp_update_user_meta($arr) {
    return update_user_meta(wpfp_get_user_id(),WPFP_META_KEY,$arr);
}

function wpfp_update_post_meta($post_id, $val) {
	$oldval = wpfp_get_post_meta($post_id);
	if ($val == -1 && $oldval == 0) {
    	$val = 0;
	} else {
		$val = $oldval + $val;
	}
    return add_post_meta($post_id, WPFP_META_KEY, $val, true) or update_post_meta($post_id, WPFP_META_KEY, $val);
}

function wpfp_delete_post_meta($post_id) {
    return delete_post_meta($post_id, WPFP_META_KEY);
}

function wpfp_get_cookie() {
    global $site_cookies;
    if (!isset($site_cookies) || !isset($site_cookies[WPFP_COOKIE_KEY])) return;
    return $site_cookies[WPFP_COOKIE_KEY];
}

function wpfp_get_options() {
   return get_option('wpfp_options');
}

function wpfp_get_user_id() {
    $user = wp_get_current_user();
    return $user->ID;
}

function wpfp_get_user_meta($user = "") {
    if (!empty($user)):
        $userdata = get_user_by( 'login', $user );
        $user_id = $userdata->ID;
        return get_user_meta($user_id, WPFP_META_KEY, true);
    else:
        return get_user_meta(wpfp_get_user_id(), WPFP_META_KEY, true);
    endif;
}

function wpfp_get_post_meta($post_id) {
    $val = get_post_meta($post_id, WPFP_META_KEY, true);
    if ($val < 0) $val = 0;
    return $val;
}

function wpfp_set_cookie($post_id, $str) {
    $expire = time()+60*60*24*30;
    // return setcookie("wp-favorite-posts[$post_id]", $str, $expire, "/");
    return true;
}

function wpfp_is_user_favlist_public($user) {
    $user_opts = wpfp_get_user_options($user);
    if (empty($user_opts)) return WPFP_DEFAULT_PRIVACY_SETTING;
    if ($user_opts["is_wpfp_list_public"])
        return true;
    else
        return false;
}

function wpfp_get_user_options($user) {
    $userdata = get_user_by( 'login', $user );
    $user_id = $userdata->ID;
    return get_user_meta($user_id, WPFP_USER_OPTION_KEY, true);
}

function wpfp_is_user_can_edit() {
    if (isset($_REQUEST['user']) && sanitize_key($_REQUEST['user']))
        return false;
    return true;
}

function wpfp_remove_favorite_link($post_id) {
    if (wpfp_is_user_can_edit()) {
        $wpfp_options = wpfp_get_options();
        $class = 'wpfp-link remove-parent';
        $link = "<a id='rem_$post_id' class='$class' href='?wpfpaction=remove&amp;page=1&amp;postid=". $post_id ."' title='".wpfp_get_option('rem')."' rel='nofollow'>".wpfp_get_option('rem')."</a>";
        $link = apply_filters( 'wpfp_remove_favorite_link', $link );
        echo wp_kses_post($link);
    }
}

function wpfp_clear_list_link() {
    if (wpfp_is_user_can_edit()) {
        $wpfp_options = wpfp_get_options();
        echo wp_kses_post(wpfp_before_link_img());
        echo wp_kses_post(wpfp_loading_img());
        echo wp_kses_post("<a class='wpfp-link' href='?wpfpaction=clear' rel='nofollow'>". wpfp_get_option('clear') . "</a>");
    }
}

function wpfp_cookie_warning() {
    if (!is_user_logged_in() && !isset($_GET['user']) ):
        echo wp_kses_post("<p>".wpfp_get_option('cookie_warning')."</p>");
    endif;
}

function wpfp_get_option($opt) {
    $wpfp_options = wpfp_get_options();
    return htmlspecialchars_decode( stripslashes ( $wpfp_options[$opt] ) );
}

// User favorite list loaded using ajax to prevent cache issue
function wpfp_user_favorite_list() {
    $options = wpfp_get_options();
    $limit = 5;
    if (isset($options['uf_widget_limit'])) {
        $limit = $options['uf_widget_limit'];
    }

    $favorite_post_ids = wpfp_get_users_favorites();

    if (@file_exists(TEMPLATEPATH.'/wpfp-your-favs-widget.php')):
        include(TEMPLATEPATH.'/wpfp-your-favs-widget.php');
    else:
        include("wpfp-your-favs-widget.php");
    endif;
    die();
} 

if( ! class_exists( 'customUpdateChecker' ) ) {

	class customUpdateChecker{

		public $plugin_slug;
		public $version;
		public $cache_key;
		public $cache_allowed;

		public function __construct() {

			$this->plugin_slug = plugin_basename( __DIR__ );
			$this->version = '1.0';
			$this->cache_key = 'custom_upd';
			$this->cache_allowed = false;

			add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
			add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
			add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

		}

		public function request(){

			$remote = get_transient( $this->cache_key );

			if( false === $remote || ! $this->cache_allowed ) {

				$remote = vip_safe_wp_remote_get(
					'https://raw.githubusercontent.com/awvenezia/wp-favorite-posts/main/info.json',
					array(
						'timeout' => 3,
						'headers' => array(
							'Accept' => 'application/json'
						)
					)
				);

				if(
					is_wp_error( $remote )
					|| 200 !== wp_remote_retrieve_response_code( $remote )
					|| empty( wp_remote_retrieve_body( $remote ) )
				) {
					return false;
				}

				set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ) );

			return $remote;

		}


		function info( $res, $action, $args ) {

			// do nothing if you're not getting plugin information right now
			if( 'plugin_information' !== $action ) {
				return false;
			}

			// do nothing if it is not our plugin
			if( $this->plugin_slug !== $args->slug ) {
				return false;
			}

			// get updates
			$remote = $this->request();

			if( ! $remote ) {
				return false;
			}

			$res = new stdClass();

			$res->name = $remote->name;
			$res->slug = $remote->slug;
			$res->version = $remote->version;
			$res->tested = $remote->tested;
			$res->requires = $remote->requires;
			$res->author = $remote->author;
			$res->author_profile = $remote->author_profile;
			$res->download_link = $remote->download_url;
			$res->trunk = $remote->download_url;
			$res->requires_php = $remote->requires_php;
			$res->last_updated = $remote->last_updated;

			$res->sections = array(
				'description' => $remote->sections->description,
				'installation' => $remote->sections->installation,
				'changelog' => $remote->sections->changelog
			);

			if( ! empty( $remote->banners ) ) {
				$res->banners = array(
					'low' => $remote->banners->low,
					'high' => $remote->banners->high
				);
			}

			return $res;

		}

		public function update( $transient ) {

			if ( empty($transient->checked ) ) {
				return $transient;
			}

			$remote = $this->request();

			if(
				$remote
				&& version_compare( $this->version, $remote->version, '<' )
				&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
				&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
			) {
				$res = new stdClass();
				$res->slug = $this->plugin_slug;
				$res->plugin = plugin_basename( __FILE__ );
				$res->new_version = $remote->version;
				$res->tested = $remote->tested;
				$res->package = $remote->download_url;

				$transient->response[ $res->plugin ] = $res;

	    }

			return $transient;

		}

		public function purge(){

			if (
				$this->cache_allowed
				&& 'update' === $options['action']
				&& 'plugin' === $options[ 'type' ]
			) {
				// just clean the cache when new plugin version is installed
				delete_transient( $this->cache_key );
			}

		}


	}

	new customUpdateChecker();

}
