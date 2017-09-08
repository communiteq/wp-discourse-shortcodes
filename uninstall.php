<?php
/**
 * Uninstall the plugin.
 *
 * For now, users will have to update the plugin manually, so options will not be deleted on uninstall.
 */

/**
 * Options list:
 *     - wpds_options Plugin options array.
 *     - wpds_update_latest Set in the API response handler, indicates whether Discourse content needs an update.
 *     - wpds_latest_last_sync Saves the time at which the latest topics were updated.
 *     - wpds_top_ (all|yearly|monthly|daily) _last_sync The time at which the top topics were updated.
 *
 * Transient list:
 *     - wpds_latest_topics The formatted Discourse latest topics.
 *     - wpds_top_ (all|yearly|monthly|daily) The formatted Discourse top topics.
 */
