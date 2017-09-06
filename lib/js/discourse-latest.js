// (function ($) {
//     var latestURL = wpds.latestURL,
//         rssURL = wpds.rssURL,
//         ajaxTimeout = wpds.ajaxTimeout;
//
//     console.log('rss url', rssURL);
//
//     $topiclist = $('.wpds-topiclist');
//     $rsslist = $('.wpds-rss-list').css('color', 'red');
//     console.log('list length', $rsslist);
//
//     if ($topiclist.length) {
//         console.log('found a topic list');
//         (function getLatest() {
//             $.ajax({
//                 url: latestURL,
//                 success: function (response) {
//                     $('.wpds-topiclist').html(response);
//                 },
//                 complete: function () {
//                     setTimeout(getLatest, ajaxTimeout * 1000);
//                 }
//             });
//         })();
//     }
//
// })(jQuery);

(function ($) {
    $(document).ready(function () {
        var latestURL = wpds.latestURL,
            rssURL = wpds.rssURL,
            ajaxTimeout = wpds.ajaxTimeout,
            $rssList = $('.wpds-rss-list'),
            url,
            maxTopics,
            displayImages,
            excerptLength,
            wpLink,
            $options;


        if ($rssList.length) {
            $options = $('.wpds-shortcode-options');
            $rssList.wrap('<div class="wpds-rss-list-wrapper"></div>');
            maxTopics = parseInt($options.data('wpds-maxtopics'), 10);
            displayImages = $options.data('wpds-display-images');
            excerptLength = $options.data('wpds-excerpt-length');
            wpLink = $options.data('wpds-wp-link');
            url = rssURL + '?max_topics=' + maxTopics + '&display_images=' + displayImages + '&excerpt_length=' + excerptLength + '&wp_link=' + wpLink;
            console.log(url);

            (function getLatest() {
                $.ajax({
                    url: url,
                    success: function (response) {
                        if (0 !== response) {
                            $('.wpds-rss-list-wrapper').html(response);
                        }
                    },
                    complete: function () {
                        setTimeout(getLatest, ajaxTimeout * 1000);
                    }
                });
            })();
        }
    });

})(jQuery);
