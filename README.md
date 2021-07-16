## WP Discourse Shortcodes


**Note:** If you are setting the `tile` attribute to 'true' on the `discourse_topics`
or `discourse_groups` shortcode, expect to see some changes to the default styles
over the next couple of releases. As of version 0.23, a fixed height is no longer being
set on the tiles.

The WP Discourse Shortcodes plugin provides a few shortcodes for displaying Discourse content
on your WordPress site. It currently has the following shortcodes:

- `discourse_topics` - displays a Discourse topic list
- `discourse_groups` - displays a selection of Discourse groups
- `discourse_link`   - creates a link to your Discourse forum

### Shortcode Attributes

#### Discourse Topics
#### Available Attributes

- `source` - 'latest' or 'top'. Defaults to 'latest'.
- `period` - if 'top' is the source, gives the period for which you would like the top topics. The options are
'all', 'yearly', 'quarterly', 'monthly', 'weekly', 'daily'. Defaults to 'all'.
- `max_topics` - the maximun number of topics to display. Defaults to 6.
- `category` - the category slug or id to filter topics by.
- `cache_duration` - how long in minutes to cache the topics. Defaults to 10. Overridden for the 'latest'
route if a webhook is enabled.
- `display_avatars` - defaults to 'true'.
- `tile` - adds a `wpds-tile` class to the Discourse topic list item. If the default styles are enabled,
it will create a basic flexbox tile display for the topics.
- `excerpt_length` - defaults to 'null'. Set to either the number of words you would like in the excerpt,
or to 'full' to display the full topic.
- `username_position` - either 'top' or 'bottom'. Defaults to 'top'.
- `category_position` - either 'top' or 'bottom'. Defaults to 'top'.
- `ajax_timeout` - ajax load period in minutes. If you've enabled the Ajax Load option on the plugin's options page, this sets the
period with which topics will be refreshed. Defaults to 2 minutes.
- `id` - the shortcode's ID. The HTML that's generated by the shortcodes is cached. If you have more
than one 'discourse_topics' shortcode on your site, and the shortcodes are unique, you need to give
each 'discourse_topics' shortcode a unique id. Any string will work for the ID. Numbering them is
probably the most sensible approach. Defaults to 'null'.
