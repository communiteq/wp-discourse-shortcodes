#### WP-Discourse Shortcodes Plugin

This plugin extends the wp-discourse plugin by adding WordPress shortcodes that can be
used to create links between WordPress and your Discourse forum. The plugin currently
has shortcodes for `[discourse_link]` to create a link to a specific endpoint on your
forum, `[discourse_topic]` to link to discourse and begin a post with a pre-filled topic,
and `[discourse_message]` to link to discourse and begin a private message.

##### [discourse_link]

The `[discourse_link]` shortcode links to a page on your Discourse forum. It accepts the
following parameters:
- link_text - the text you wish to see for the link
- return_path - the endpoint you want to link to on your Discourse forum. This defaults to
your forum's homepage. The return path should begin with a '/'. For example, to link to the
`categories` page you would use `return_path='/categories'`
- classes - a list of classes (separated by spaces) that you wish to apply to the anchor
element. For example to add the classes `discourse` and `discourse-button` to a link you
would use `classes="discourse discourse-button"`
- login - whether you with for the link to log the user in to Discourse. Defaults to 
true.

Here is a complete `[discourse_link]` shortcode that links to the forum's hompage and logs
in the user. It adds the class `discourse-button` to the anchor element.

`[discourse_link link_text="Visit Our Forum" classes="discourse-button"]`

##### [discourse_topic]

The `[discourse_topic]` shortcode begins a prefilled topic by the current user, on the
Discourse forum. It accepts the following parameters:

- link_text - the text you wish to see for the link
- classes - a list of classes (separated by spaces) that you wish to apply to the anchor
element. For example to add the classes `discourse` and `discourse-button` to a link you
would use `classes="discourse discourse-button"`
- title - the title of the post
- body - the body of the post
- category - the post's category name. For example to create a post in the `french` category use
`category="french"`, to create a post in the `french food` subcategory use
`category="french/french food"`

Here is a complete `[discourse_topic]` shortcode that creates a link with the text 'What did you do this summer?'.
It begins a Discourse topic with the title 'How I spent my summer vacation' in the 'great trips' category:

`[discourse_topic classes="discourse-button" link_text="What did you do this summer?" title="How I spent my summer vacation" category="great trips"]`


##### [discourse_message]

The `[discourse_message]` shortcode begins a prefilled private message sent from the
current user to a named Discourse user. It accepts the following parameters:

- link_text - the text you wish to see for the link
- classes - a list of classes (separated by spaces) that you wish to apply to the anchor
element. For example to add the classes `discourse` and `discourse-button` to a link you
would use `classes="discourse discourse-button"`
- username - the username of the person the message is being sent to
- title - the title of the post
- message - the body of the post

Here is a complete `[discourse_message]` shortcode that creates a link with the text 'Learn more about gentle yoga'.
It begins a Discourse private message with the title 'Information requested about gentle yoga classes' addressed to
the user 'scossar'.

`[discourse_message classes="discourse-button" link_text="Learn more about gentle yoga" username="scossar" title="Information requested about gentle yoga classes"]`

##### Using the shortcodes in a php file

This can be done with the WordPress function `do_shortcode`. The most likely place
to want to do this is in the theme's menu. Here is some example code that could
be added to a theme's `functions.php` file to add a discourse_link to the theme's
`main-navigation` menu:

    add_filter( 'wp_nav_menu_main-navigation_items', 'wp_discourse_menu_link', 10, 2 );
    function wp_discourse_menu_link( $items, $args ) {
	    $discourse_link = do_shortcode( '[discourse_link link_text="Visit Our Forum" return_path="/top" classes="discourse-button"]' );

	    $items .= '<li class="menu-item">' . $discourse_link . '</li>';

	    return $items;
    }
