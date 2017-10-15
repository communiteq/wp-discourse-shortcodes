(function ($) {
	$( document ).ready(
		function () {
			var $tiles = $( '.wpds-topic' );
			$tiles.each(
				function() {
					var $this = $( this );
					var targetHeight = $this.outerHeight();
					var footerHeight = $this.find( 'footer' ).outerHeight( true );
					var $variableText = $( this ).find( '.wpds-topiclist-clamp' );
					var $footer = $( this ).find( 'footer' );
					var $text = $( this ).find( '.wpds-topiclist-content' );
					while ($variableText.outerHeight( true ) + footerHeight > $( this ).outerHeight()) {
						$text.text(
							function(index, text){
								return text.replace( /\W*\s(\S)*$/, '...' );
							}
						);
					}
				}
			);
		}
	);
})( jQuery );
