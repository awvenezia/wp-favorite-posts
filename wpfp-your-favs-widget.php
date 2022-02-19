<?php
echo "<ul>";
if ($favorite_post_ids):
	$c = 0;
	$favorite_post_ids = array_reverse($favorite_post_ids);
    foreach ($favorite_post_ids as $fav_post_id) {
    	if ($c++ == $limit) break;
        $p = get_post($fav_post_id);
        echo "<li>";
        echo "<a href='".esc_url(get_permalink($fav_post_id))."' title='". esc_attr($p->post_title) ."'>" . wp_kses_post($p->post_title) . "</a> ";
        echo "</li>";
    }
else:
    echo "<li>";
    echo "Your favorites will be here.";
    echo "</li>";
endif;
echo "</ul>";
