(function ($) {
	$( document ).ready(
		function () {
			var topicURL = wpds.latestURL,
			$topicList = $( '.wpds-topiclist-refresh' );

			if ($topicList.length) {
				$topicList.each(
					function () {

						var $this = $( this ),
						topicParams,
						maxTopics,
						cacheDuration,
						displayAvatars,
						source,
						period,
						tile,
						excerptLength,
						usernamePosition,
						categoryPosition,
						datePosition,
						ajaxTimeout,
						shortcodeId,
						$topicOptions,
						$topicListWrapper;

						$topicOptions = $this.find( '.wpds-topic-shortcode-options' );
						ajaxTimeout = parseInt( $topicOptions.data( 'wpds-ajax-timeout' ), 10 );
						ajaxTimeout = ajaxTimeout < 1 ? 2 : ajaxTimeout;

						$this.wrap( '<div id="wpds-topic-list-wrapper-' + shortcodeId + '"></div>' );
						$topicListWrapper = $( '#wpds-topic-list-wrapper-' + shortcodeId );
						topicParams =  '?max_topics=' + parseInt( $topicOptions.data( 'wpds-max-topics' ), 10 ) +
						'&cache_duration=' + parseInt( $topicOptions.data( 'wpds-cache-duration' ), 10 ) +
						'&display_avatars=' + $topicOptions.data( 'wpds-display-avatars' ) +
						'&source=' + $topicOptions.data( 'wpds-source' ) +
						'&period=' + $topicOptions.data( 'wpds-period' ) +
						'&tile=' + $topicOptions.data( 'wpds-tile' ) +
						'&excerpt_length=' + $topicOptions.data( 'wpds-excerpt-length' ) +
						'&username_position=' + $topicOptions.data( 'wpds-username-position' ) +
						'&category_position=' + $topicOptions.data( 'wpds-category-position' ) +
						'&date_position=' + $topicOptions.data( 'wpds-date-position' ) +
						'&id=' + $topicOptions.data( 'wpds-id' ) +
						'&category=' + $topicOptions.data('wpds-category') +
						'&ajax_timeout=' + ajaxTimeout;

						(function getTopics() {
							$.ajax(
								{
									url: topicURL + topicParams,
									success: function (response) {
										if (0 !== response) {
											$topicListWrapper.addClass( 'wpds-ajax-loading' );
											$topicListWrapper.html( response );
										}
									},
									complete: function () {
										setTimeout(
											function () {
												$topicListWrapper.removeClass( 'wpds-ajax-loading' );
											}, 1000
										);
										setTimeout( getTopics, ajaxTimeout * 60 * 1000 );
									}
								}
							);
						})();

					}
				);
			}
		}
	);
})( jQuery );
