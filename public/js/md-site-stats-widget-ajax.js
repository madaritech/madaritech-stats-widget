jQuery(document).ready(function($) {

	$( "#stats-widget" ).replaceWith( '<div id="stats-widget">Loading...</div>' );

    do_ajax_call();
    setInterval(loop_ajax_call, 1000 * 60 * wpApiSettings.refresh_time);

    var active = 0;

    function loop_ajax_call() {
    	active = $( "#stats-widget" ).accordion( "option", "active" );
		do_ajax_call();
    };

    function do_ajax_call() {

        $.ajax({
            method: 'GET',
            url: wpApiSettings.root + 'md-site-stats-widget/v1/stats', 

            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
            },

            success : function( response ) {
            	var res = '<div id="stats-widget">';

            	$.each(response.sites, function(key, item) { 
            		table_general = '<table><tr><td>Users</td><td>Posts</td><td>Comments</td></tr><tr><td>' + item.users + '</td><td>' + item.posts + '</td><td>' + item.comments + '</td></tr></table>';
                    table_post = '<table><tr><em>Posts Details<\em></th></tr>' +
                                 '<tr><td>publish</td><td>' + item.post_publish + '</td><td>future</td><td>' + item.post_future + '</td></tr>' +
                                 '<tr><td>draft</td><td>' + item.post_draft + '</td><td>pending</td><td>' + item.post_pending + '</td></tr>' +
                                 '<tr><td>private</td><td>' + item.post_private + '</td><td>trash</td><td>' + item.post_trash + '</td></tr>' +
                                 '<tr><td>auto-draft</td><td>' + item.post_auto_draft + '</td><td>inherit</td><td>' + item.post_inherit + '</td><tr></table>';
					res = res + '<h3><a href="">' + item.blogname + '</a></h3><div><hr>' + table_general + table_post + '</div>';
            	});

            	res = res + '</div>';
            	js = '<script id="accordion">jQuery("#stats-widget").accordion({active: ' + active + '})</script>';

            	$( "#stats-widget" ).replaceWith( res );
                $( "#accordion" ).replaceWith( js );
            },
            
            error : function( response ) {
                var res = eval("(" + response.responseText + ")");
                $( "#stats-widget" ).replaceWith( '<div id="stats-widget">' + res.message + '</div>' );
            }
        });
    }
});