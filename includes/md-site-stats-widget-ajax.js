jQuery(document).ready(function($) {

	$( "#stat-widget" ).replaceWith( '<div id="stat-widget">Updating...</div>' );

    setInterval(function() {
   
        // Fire our ajax request!
        $.ajax({
            method: 'GET',
            url: wpApiSettings.root + 'md-site-stats-widget/v1/stats', 

            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
            },

            success : function( response ) {
            	var res = '<div id="stat-widget">';
            	$.each(response.sites, function(key, item) { 
            		table_general = '<table><tr><td>Users</td><td>Posts</td><td>Comments</td><td>Terms</td><td>Links</td></tr><tr><td>' + item.users + '</td><td>' + item.posts + '</td><td>' + item.comments + '</td><td>' + item.terms + '</td><td>' + item.links + '</td></tr></table>';
                    table_post = '<table><tr><em>Posts Details<\em></th></tr>' +
                                 '<tr><td>publish</td><td>' + item.post_publish + '</td><td>future</td><td>' + item.post_future + '</td></tr>' +
                                 '<tr><td>draft</td><td>' + item.post_draft + '</td><td>pending</td><td>' + item.post_pending + '</td></tr>' +
                                 '<tr><td>private</td><td>' + item.post_private + '</td><td>trash</td><td>' + item.post_trash + '</td></tr>' +
                                 '<tr><td>auto-draft</td><td>' + item.post_auto_draft + '</td><td>inherit</td><td>' + item.post_inherit + '</td><tr></table>';
					res = res + '<strong>' + item.blogname + '</strong><hr>' + table_general + table_post; //<div><span style="font-style: italic">Users:</span> ' + item.users + '</div><br>';
            	});
            	res = res + '</div>';
                //$( "#stat-widget" ).replaceWith( '<div id="stat-widget">Sites ' + response.blog_count + '<hr></div>' );
                $( "#stat-widget" ).replaceWith( res );
                console.log(res);
            },
            
            fail : function( response ) {
                $( "#stat-widget" ).replaceWith( '<div id="stat-widget">Data not available</div>' );
            }
        });

    }, 1000 * 5 * wpApiSettings.refresh_time);

});