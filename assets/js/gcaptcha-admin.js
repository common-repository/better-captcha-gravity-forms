jQuery(document).ready( function() {
	
	if( jQuery(".subsubsub a").length > 0 ) {
		jQuery(".subsubsub a").each(function() {
			var href = jQuery(this).attr("href");
			href = href.replace( "gf_entries", "gcaptcha_spams", href );
			jQuery(this).attr("href", href);
		});
		
	}
} );