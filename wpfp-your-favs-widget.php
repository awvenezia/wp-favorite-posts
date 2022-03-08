<?php
/**
 * Version: 1.7.6
 * Author: Alto-Palo
 * Author URI: https://github.com/awvenezia
 * 
 * @package NlsnWPFP
 */

echo '<ul>';
if ( $nlsn_favorite_post_ids ) :
	$nlsn_c                 = 0;
	$nlsn_favorite_post_ids = array_reverse( $nlsn_favorite_post_ids );
	foreach ( $nlsn_favorite_post_ids as $nlsn_fav_post_id ) {
		if ( $nlsn_c++ === $limit ) {
			break;
		}
		$nlsn_p = get_post( $nlsn_fav_post_id );
		echo '<li>';
		echo "<a href='" . esc_url( get_permalink( $nlsn_fav_post_id ) ) . "' title='" . esc_attr( $nlsn_p->post_title ) . "'>" . wp_kses_post( $nlsn_p->post_title ) . '</a> ';
		echo '</li>';
	}
else :
	echo '<li>';
	echo 'Your favorites will be here.';
	echo '</li>';
endif;
echo '</ul>';
