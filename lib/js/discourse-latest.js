(function ($) {
    $(document).ready(function () {
        var topicURL = wpds.latestURL,
            rssURL = wpds.rssURL,
            ajaxTimeout = wpds.ajaxTimeout,
            $rssList = $('.wpds-rss-list'),
            $topicList = $('.wpds-topiclist'),
            topicParams,
            topicMaxTopics,
            topicSource,
            topicPeriod,
            topicAvatars,
            $topicOptions,
            rssParams,
            rssMaxTopics,
            rssDisplayImages,
            rssExcerptLength,
            wpLink,
            $rssOptions;

        if ($rssList.length) {
            $rssOptions = $('.wpds-rss-shortcode-options');
            $rssList.wrap('<div class="wpds-rss-list-wrapper"></div>');
            rssMaxTopics = parseInt($rssOptions.data('wpds-maxtopics'), 10);
            rssDisplayImages = $rssOptions.data('wpds-display-images');
            rssExcerptLength = $rssOptions.data('wpds-excerpt-length');
            wpLink = $rssOptions.data('wpds-wp-link');
            rssParams = '?max_topics=' + rssMaxTopics + '&display_images=' + rssDisplayImages + '&excerpt_length=' + rssExcerptLength + '&wp_link=' + wpLink;

            (function getRSS() {
                $.ajax({
                    url: rssURL + rssParams,
                    success: function (response) {
                        if (0 !== response) {
                            $('.wpds-rss-list-wrapper').html(response);
                        }
                    },
                    complete: function () {
                        setTimeout(getRSS, ajaxTimeout * 1000);
                    }
                });
            })();
        }

        if ($topicList.length) {
            $topicOptions = $('.wpds-topic-shortcode-options');
            console.log('data', $('.wpds-topic-shortcode-options').data('wpds-display-avatars'));
            console.log('numbertest', $('wpds-topic-shortcode-options').data('wpds-maxtopics'));
            console.log('topic options', $topicOptions);
            $topicList.wrap('<div class="wpds-topic-list-wrapper"></div>');
            topicMaxTopics = parseInt($topicOptions.data('wpds-maxtopics'), 10);
            console.log('max topics', topicMaxTopics);
            topicAvatars = $topicOptions.data('wpds-display-avatars');
            topicSource = $topicOptions.data('wpds-source');
            topicPeriod = $topicOptions.data('wpds-period');
            topicParams = '?max_topics=' + topicMaxTopics + '&display_avatars=' + topicAvatars + '&source=' + topicSource + '&period=' + topicPeriod;
            console.log( 'url', topicURL + topicParams);

            (function getTopics() {
                $.ajax({
                    url: topicURL + topicParams,
                    success: function (response) {
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
