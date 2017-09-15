(function ($) {
    $(document).ready(function () {
        var $tiles = $('.wpds-topic');
        $tiles.each(function () {
            var $this = $(this),
                footerHeight = $this.find('footer').outerHeight(true),
                $variableText = $this.find('.wpds-topiclist-clamp'),
                $text = $this.find('.wpds-topiclist-content');

            while ($variableText.outerHeight(true) + footerHeight > $this.outerHeight()) {
                $text.text(function (index, text) {
                    return text.replace(/\W*\s(\S)*$/, '...');
                });
            }
        });

        var $groups = $('.wpds-group');
        $groups.each(function () {
            var $this = $(this),
                footerHeight = $this.find('footer').outerHeight(true),
                $variableDiv = $this.find('.wpds-group-clamp'),
                $text = $this.find('.wpds-group-description');

            while ($variableDiv.outerHeight() + footerHeight > $this.outerHeight()) {
                $text.text(function (index, text) {
                    return text.replace(/\W*\s(\S)*$/, '...');
                });
            }
        });
    });
})(jQuery);
