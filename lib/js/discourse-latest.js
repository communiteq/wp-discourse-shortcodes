(function ($) {
    // Todo: use the wpds-topiclist-refresh class.
    $(document).ready(function () {
        var topicURL = wpds.latestURL,
            // Todo: set this as a shortcode arg.
            $topicList = $('.wpds-topiclist-refresh'),
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
            $topicOptions;

        if ($topicList.length) {
            $topicOptions = $('.wpds-topic-shortcode-options');
            $topicList.wrap('<div class="wpds-topic-list-wrapper"></div>');
            maxTopics = parseInt($topicOptions.data('wpds-maxtopics'), 10);
            cacheDuration = parseInt($topicOptions.data('wpds-cache-duration'), 10);
            displayAvatars = $topicOptions.data('wpds-display-avatars');
            source = $topicOptions.data('wpds-source');
            period = $topicOptions.data('wpds-period');
            tile = $topicOptions.data('wpds-tile');
            usernamePosition = $topicOptions.data('wpds-username-position');
            categoryPosition = $topicOptions.data('wpds-category-position');
            datePosition = $topicOptions.data('wpds-date-position');
            ajaxTimeout = parseInt($topicOptions.data('wpds-ajax-timeout'), 10);
            ajaxTimeout = ajaxTimeout < 1 ? 2 : ajaxTimeout;
            excerptLength = $topicOptions.data('wpds-excerpt-length');
            topicParams = '?max_topics=' + maxTopics +
                '&cache_duration=' + cacheDuration +
                '&display_avatars=' + displayAvatars +
                '&source=' + source +
                '&period=' + period +
                '&tile=' + tile +
                '&excerpt_length=' + excerptLength +
                '&username_position=' + usernamePosition +
                '&category_position=' + categoryPosition +
                '&date_position=' + datePosition +
                '&enable_ajax=true' +
                '&ajax_timeout=' + ajaxTimeout;

            (function getTopics() {
                $.ajax({
                    url: topicURL + topicParams,
                    success: function (response) {
                        if (0 !== response) {
                            $('.wpds-topic-list-wrapper').html(response);
                        }
                    },
                    complete: function () {
                        setTimeout(getTopics, ajaxTimeout * 60 * 1000);
                    }
                });
            })();
        }
    });
})(jQuery);
