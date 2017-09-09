(function ($) {
    $(document).ready(function () {
        var topicURL = wpds.latestURL,
            $topicList = $('.wpds-topiclist-refresh');

        if ($topicList.length) {
            $topicList.each(function () {

                var $this = $(this),
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

                $topicOptions = $this.find('.wpds-topic-shortcode-options');
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
                shortcodeId = $topicOptions.data('wpds-id');
                $this.wrap('<div id="wpds-topic-list-wrapper-' + shortcodeId + '"></div>');
                $topicListWrapper = $('#wpds-topic-list-wrapper-' + shortcodeId);
                console.log('topiclistwrapper', $topicListWrapper);
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
                    '&id=' + shortcodeId +
                    '&ajax_timeout=' + ajaxTimeout;

                (function getTopics() {
                    console.log('in the get topics function. ID: ', shortcodeId);
                    console.log('topic params', topicParams);
                    $.ajax({
                        url: topicURL + topicParams,
                        success: function (response) {
                            if (0 !== response) {
                                console.log('response', response);
                                $topicListWrapper.addClass('wpds-ajax-loading');
                                $topicListWrapper.html(response);
                            }
                        },
                        complete: function () {
                            var $tiles = $this.find('.wpds-topic');
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

            });
        }
    });
})(jQuery);
