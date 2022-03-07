<?php
/**
 * Version: 1.7.5
 * Author: Alto-Palo
 * Author URI: https://github.com/awvenezia
 * 
 * @package NlsnWPFP
 */

$nlsn_before = '';
echo "<div class='nlsn-span'>";
if ( ! empty( $user ) ) {
	if ( nlsn_is_user_favlist_public( $user ) ) {
		$nlsn_before = "$user's Favorite Posts.";
	} else {
		$nlsn_before = "$user's list is not public.";
	}
}

if ( $nlsn_before ) :
	echo wp_kses_post( '<div class="nlsn-page-before">' . $nlsn_before . '</div>' );
	endif;

if ( $nlsn_favorite_post_ids ) {
	$nlsn_favorite_post_ids = array_reverse( $nlsn_favorite_post_ids );
	$nlsnl_post_per_page    = nlsn_get_option( 'post_per_page' );
	$nlsn_page_no           = intval( get_query_var( 'paged' ) );

	$nlsn_qry = array(
		'post__in'       => $nlsn_favorite_post_ids,
		'posts_per_page' => $nlsnl_post_per_page,
		'orderby'        => 'post__in',
		'paged'          => $nlsn_page_no,
	);
	// custom post type support can easily be added with a line of code like below.
	$nlsn_query = new WP_Query( $nlsn_qry );
	if ( $nlsn_query->have_posts() ) :  
		echo '<ul>';
		while ( $nlsn_query->have_posts() ) :
			$nlsn_query->the_post();
			echo "<li><a href='" . esc_url( get_permalink() ) . "' title='" . esc_attr( get_the_title() ) . "'>" . wp_kses_post( get_the_title() ) . '</a> ';
			nlsn_remove_favorite_link( get_the_ID() );
			echo '</li>';
			endwhile;
		echo '</ul>';

		echo '<div class="navigation">';
		if ( function_exists( 'wp_pagenavi' ) ) {
			wp_pagenavi(); 
		} else { ?>
			<div class="alignleft"><?php next_posts_link( __( '&larr; Previous Entries', 'nielsen' ) ); ?></div>
			<div class="alignright"><?php previous_posts_link( __( 'Next Entries &rarr;', 'nielsen' ) ); ?></div>
			<?php 
		}
		echo '</div>';

		wp_reset_postdata();
	endif;
} else {
	$nlsn_options = nlsn_get_options();
	echo '<ul><li>';
	echo wp_kses_post( $nlsn_options['favorites_empty'] );
	echo '</li></ul>';
}

	echo '<p>' . wp_kses_post( nlsn_clear_list_link() ) . '</p>';
	echo '</div>';
	nlsn_cookie_warning();
