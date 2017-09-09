(function ($) {
    $(document).ready(function () {
        // var $tiles = $('.wpds-topic');
        // $tiles.each( function() {
        //     var $this = $(this);
        //     var targetHeight = $this.outerHeight();
        //     var footerHeight = $this.find('footer').outerHeight(true);
        //     console.log('targetheight', targetHeight);
        //     console.log('footer height', footerHeight);
        //     var $variableText = $(this).find('.wpds-topiclist-clamp');
        //     var $footer = $(this).find('footer');
        //     var $text = $(this).find('.wpds-topiclist-content');
        //     while ($variableText.outerHeight(true) + footerHeight > $(this).outerHeight()) {
        //         $text.text(function(index, text){
        //             return text.replace(/\W*\s(\S)*$/, '...');
        //         });
        //     }
        // });

        var topicURL = wpds.latestURL,
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
            $topicOptions,
            $topicListWrapper;

        if ($topicList.length) {
            $topicOptions = $('.wpds-topic-shortcode-options');
            $topicList.wrap('<div class="wpds-topic-list-wrapper"></div>');
            $topicListWrapper = $('.wpds-topic-list-wrapper');
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
                            $topicListWrapper.addClass('wpds-ajax-loading');
                            $topicListWrapper.html(response);
                        }
                    },
                    complete: function () {
                        var $tiles = $('.wpds-topic');
                        $tiles.each(function () {
                            var $this = $(this);
                            var targetHeight = $this.outerHeight();
                            var footerHeight = $this.find('footer').outerHeight(true);
                            var $variableText = $(this).find('.wpds-topiclist-clamp');
                            var $footer = $(this).find('footer');
                            var $text = $(this).find('.wpds-topiclist-content');
                            while ($variableText.outerHeight(true) + footerHeight > $(this).outerHeight()) {
                                $text.text(function (index, text) {
                                    return text.replace(/\W*\s(\S)*$/, '...');
                                });
                            }
                        });
                        setTimeout(function () {
                            $topicListWrapper.removeClass('wpds-ajax-loading');
                        }, 1000);
                        // setTimeout(getTopics, ajaxTimeout * 60 * 1000);
                        setTimeout(getTopics, 30000);
                    }
                });
            })();
        }
    });
})(jQuery);
