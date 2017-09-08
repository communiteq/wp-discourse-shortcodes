(function ($) {
    // Todo: use the wpds-topiclist-refresh class.
    $(document).ready(function () {
        var topicURL = wpds.latestURL,
            // Todo: set this as a shortcode arg.
            ajaxTimeout = wpds.ajaxTimeout,
            $topicList = $('.wpds-topiclist-refresh'),
            topicParams,
            topicMaxTopics,
            topicSource,
            topicPeriod,
            topicAvatars,
            $topicOptions,
            excerptLength,
            tile;

        if ($topicList.length) {
            $topicOptions = $('.wpds-topic-shortcode-options');
            $topicList.wrap('<div class="wpds-topic-list-wrapper"></div>');
            topicMaxTopics = parseInt($topicOptions.data('wpds-maxtopics'), 10);
            topicAvatars = $topicOptions.data('wpds-display-avatars');
            topicSource = $topicOptions.data('wpds-source');
            topicPeriod = $topicOptions.data('wpds-period');
            excerptLength = $topicOptions.data('wpds-excerpt-length');
            tile = $topicOptions.data('wpds-tile');
            topicParams = '?max_topics=' + topicMaxTopics + '&display_avatars=' + topicAvatars + '&source=' + topicSource + '&period=' + topicPeriod + '&tile=' + tile + '&excerpt_length=' + excerptLength;

            (function getTopics() {
                $.ajax({
                    url: topicURL + topicParams,
                    success: function (response) {
                        console.log('in the getTopics function');
                        if (0 !== response) {
                            $('.wpds-topic-list-wrapper').html(response);
                        }
                    },
                    complete: function () {
                        setTimeout(getTopics, ajaxTimeout * 1000);
                    }
                });
            })();
        }
    });
})(jQuery);
