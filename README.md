### WP-Discourse Shortcodes Plugin

**Note:** This plugin is under development. Use with caution!
**Issues:** Currently, creating a 'staged' user through the Discourse API causes an activation email to be sent to the new
user. As a temporary workaround for this, I am using this plugin on my Discourse forum: https://github.com/scossar/discourse-staged-user-activation

This plugin extends the wp-discourse plugin by adding WordPress shortcodes that can be
used to create links between WordPress and your Discourse forum. The plugin currently
has shortcodes for `[discourse_link]` to create a link to a specific endpoint on your
forum; `[discourse_remote_message]` to send a private message to individuals or groups on the forum;
`[discourse_prefilled_message]` to start a prefilled message in the Discourse composer;
`[discourse_groups]` to display Discourse groups and provide an optional signup form for the groups,
and `[discourse_latest]` to display the latest topics from the forum on your website.

### [discourse_link]

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

**Note:** for the 'login' attribute to work, you must configure and enable 'single sign on' between
your WordPress site and your Discourse forum, through the wp-discourse plugin.

Here is a complete `[discourse_link]` shortcode that links to the forum's hompage and logs
in the user. It adds the class `discourse-button` to the anchor element.

`[discourse_link link_text="Visit Our Forum" classes="discourse-button"]`

###[discourse_remote_message]

The `[discourse_remote_message]` shortcode creates a form for sending private messages to a single recipient, a list of Discourse users, or
a list of Discourse groups, on the Discourse forum. If the message is from an email address that is not yet associated with a Discourse user,
it creates a new 'staged' user on the forum and sends the message through the Discourse API. This user is not able to log
into the forum, but can interact with it through email.

If the message is from an email address that is associated with an existing
and active Discourse user, then the form is not submitted. Instead, if SSO is enabled on the website through the wp-discourse plugin,
a prefilled message link (created with the `[discourse_prefilled_message]` shortcode) is created and displayed to the user.
The user is instructed to click the link to submit their message. 

If the message is from an email address that is associated with an existing and active Discourse user on a website where
SSO is not enabled, the form is not submitted. Instead, the user in instructed to log into Discourse and send their message
from there.

This shortcode is primarily designed to create 'staged' users for a forum.

####The shortcode has the following attributes:

- 'title' - sets the message's title, if not supplied a 'subject' text input will appear in the form
- 'message' - sets the message's body, if not supplied a 'message' textarea will appear in the form
- 'recipients' - a comma separated list of recipients, either individuals or groups
- 'group' - (boolean) indicates whether or not the recipients of the message are the members of a group. This is used because
the fallback for the shortcode, when the form is submitted by an existing and active Discourse user, needs to know if the message
is intended for a Discourse group or a Discourse user. Default to 'true', set to 'false' if the intended recipients of the message
are Discourse users.
- 'button_text' - sets the text for the form's 'submit' button
- 'email_heading' - the text for the email input label, defaults to 'Email: '
- 'subject_heading' - the text for the label of the 'title' text input, defaults to 'Subject: '
- 'message_heading' - the text for the label of the 'message' text area, defaults to 'Message: '
- 'require_name' - (boolean) whether or not to include a 'name' input on the form, defaults to false
- 'user_details' - (boolean) when set to true, the user's full name is appended to the message, defaults to false

####Examples:

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19066122/58c71708-89cc-11e6-84f6-6470be517974.png)
shortcode: `[discourse_remote_message recipients="support"]`

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19066128/601af736-89cc-11e6-85f1-377712ad767d.png)
shortcode: `[discourse_remote_message recipients="support" title="Support request" message="Looking for support with your product"]`

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19066088/3970e032-89cc-11e6-8813-52515f30e7f0.png)
shortcode: `[discourse_remote_message recipients="support" button_text="Translate" email_heading="Your email address: " subject_heading="Languages (to and from):" message_heading="Text to translate:"]`

When submitted, this will create a new staged user on the associated Discourse forum and send a message to all members of the
'support' group.

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19066111/4ec3e38a-89cc-11e6-85e4-bd6f26c639ab.png)

###[discourse_prefilled_message]

Creates a link to Discourse that opens the Discourse Composer and initiates a prefilled private message, either to an
individual user or a Discourse group. Both the title and the body of the message of can be prefilled. A message can also
be created with neither the title or the body.

**Note:** this shortcode requires 'single sign on' to be configured and enabled through the wp-discourse plugin.

####The shortcode has the following attributes:

- 'link_text' - the link text that is displayed, defaults to 'Contact Us'
- 'classes' - CSS classes added to the `anchor` element, defaults to ''
- 'title' - the title for the message, defaults to '' - adding a title is optional
- 'message' - the body of the message, defaults to '' -  adding a message body is optional
- 'username' - the Discourse username to send the message to, defaults to '' - **note:** either the 'username' or the
'groupname' must be supplied
- 'groupname' - the Discourse group to send the message to, defaults to '' - **note:** either the 'groupname' or the 'username'
must be supplied

**Note:** if both the 'username' and the 'groupname' are supplied, the shortcode will default to using the groupname.

####Examples:

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19102869/2949b9ba-8a88-11e6-8e67-bf70bb43918c.png)

clicking the link opens the following composer window:
![alt tag](https://cloud.githubusercontent.com/assets/2975917/19102873/30bf5baa-8a88-11e6-874a-e468b2033662.png)
shortcode + text: `Need help? [discourse_prefilled_message username="simon12,tester2" title="Help Required" message="Tell us how we can help..." classes="test-class scratch" link_text="Click Here!!!"]`
###[discourse_latest]

Displays the latest Discourse topics.

####The shortcode has the following attributes:

- 'max_topics' - the maximum number of topics to Display, defaults to 5
- 'cache_duration' - the number of minutes to wait before fetching fresh topics on page load, defaults to 10

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19066936/afedbeca-89d0-11e6-9ee7-06fa68b94229.png)

shortcode: `[discourse_latest max_topics="7"]` (I guess that's a bug :))

###[discourse_groups]

Displays a list of Discourse groups. It will either diplay a list of selected groups, or default to groups that are 'mentionable',
(groups that can be sent a message by the public.) Descriptions can be added to groups by creating a 'group descriptions' category
on the forum. Topics in that category with titles following the pattern of 'About the {group name} group', for example 'About the urban farming group',
will be used for supplying the group description for the shortcode.

Groups can optionally display an invitation link. That link can be either a `discourse_remote_message` type link (create staged users),
or a `discourse_prefilled_message` type link (requires SSO to be enabled.)

####The shortcode has the following attributes:

- 'invite' - whether or not to include an invite form underneath the description, defaults to false
- 'group_list' - a comma separated list of group names to display, defaults to '', by default all 'mentionable' groups are displayed
- 'require_name' - whether or not to include a 'name' field on the signup form, defaults to true
- 'clear_cache' - by default the group information is cached for one day, set this to true to clear the cache, **but don't leave
it set to true!**, it takes a few queries to the forum to put this information together.
- 'button_text' - the text for the form's submit button, defaults to 'Join'
- 'user_details' - whether or not to append the real name of the person who submitted the form to the message, defaults to 'true'
- 'remote_message' - whether or not the 'invite' link should be of the `discourse_remote_message` type, defaults to 'true'

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19066079/32435ac4-89cc-11e6-8cba-51c8a83aec91.png)
shortcode: `[discourse_groups require_name="true" button_text="Join Now" group_list="blues_society,mountain_biking" invite="true"]`

Submitting the form for the 'blues_society' group will create a staged Discourse if a user is not yet associated with
that email address, and notify members of the 'blues_society' group that the user wishes to join. If the email address
is already associated with an account on the forum, the form will not be submitted and a `discourse_prefilled_message` type
link will be displayed, prefilled with the content of the message.

![alt tag](https://cloud.githubusercontent.com/assets/2975917/19103674/1fdcf98c-8a8d-11e6-9ef4-0051c384915b.png)

### Using the shortcodes in a php file

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
