(function ($) {
	var latestURL = wpds.latestURL,
		ajaxTimeout = wpds.ajaxTimeout;

	(function getLatest() {
		$.ajax({
			url: latestURL,
			success: function (response) {
				$( '.wpds-topiclist' ).html( response );
			},
			complete: function () {
				setTimeout( getLatest, ajaxTimeout * 1000 );
			}
		});
	})();
})(jQuery);
