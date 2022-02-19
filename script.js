jQuery(document).ready( function($) {
    $('body').on('click', '.wpfp-link', function() {
        dhis = $(this);
        wpfp_do_js( dhis, 1 );
        // for favorite post listing page
        if (dhis.hasClass('remove-parent')) {
            dhis.parent("li").fadeOut();
        }
        return false;
    });
});

function wpfp_do_js( dhis, doAjax ) {
    console.log("Do ajax");
    loadingImg = dhis.prev();
    loadingImg.show();
    beforeImg = dhis.prev().prev();
    beforeImg.hide();
    url = document.location.href.split('#')[0];
    urlParams = new URL(url+dhis.attr('href'));
    action = urlParams.searchParams.get('wpfpaction');
    postid = urlParams.searchParams.get('postid');
    params = dhis.attr('href').replace('?', '') + '&ajax=1';
    if ( doAjax ) {
        jQuery.get(url, params, function(data) {
                if(action == 'add'){
                    setCookie(WP_FAV_COOKIE+'['+ postid +']', "added", 30);
                } else {
                    setCookie(WP_FAV_COOKIE+'['+ postid +']', "", 30);
                }
                dhis.parent().html(data);
                if(typeof wpfp_after_ajax == 'function') {
                    wpfp_after_ajax( dhis ); // use this like a wp action.
                }
                loadingImg.hide();
            }
        );
    }
}

function setCookie(cname,cvalue,exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function wpfp_after_ajax(dthis) {
    wpfp_user_favorite_list();
}

function wpfp_user_favorite_list() {
    var mylist = jQuery(".user-favorite-list");
    url = document.location.href.split('#')[0];
    params = 'wpfpaction=user-favorite-list&ajax=1';

    jQuery.get(url, params, function(data) {
            mylist.html(data);
        }
    );
}