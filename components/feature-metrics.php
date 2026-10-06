<?php
	// Next site to go live details
	$next_site_name = ""; // match site name on Hale
	$next_site_abbr = "";
	$next_site_url = "";

	// Current environment
	$this_url = get_bloginfo('url');
	$this_env = ucfirst(getenv('WP_ENVIRONMENT_TYPE'));

	// How many live sites — use get_blog_option to avoid switch_to_blog per site
	$live_site_count = 0;
	foreach ($sites as $site) {
		$active_plugins = (array) get_blog_option($site->blog_id, 'active_plugins');
		if (!in_array('wp-force-login/wp-force-login.php', $active_plugins)) $live_site_count++;
	}

	// Total users across the network
	global $wpdb;
	$total_network_users = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}");

	// Users with at least one active (non-expired) session; collect IDs for per-site lookup
	$session_rows = $wpdb->get_results(
		$wpdb->prepare("SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", 'session_tokens')
	);
	$now = time();
	$active_user_ids = [];
	$current_user_id = get_current_user_id();
	foreach ($session_rows as $row) {
		$tokens = maybe_unserialize($row->meta_value);
		if (is_array($tokens)) {
			foreach ($tokens as $token) {
				if (isset($token['expiration']) && $token['expiration'] > $now) {
					$active_user_ids[] = (int) $row->user_id;
					break;
				}
			}
		}
	}
	$active_sessions = count($active_user_ids);
?>
<div class="hale-dash-metrics">
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">Current Environment</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo esc_html($this_env); ?></span>
	</div>
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">Sites hosted</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo get_site_count(); ?></span>
	</div>
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">Public sites</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo $live_site_count; ?></span>
	</div>
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">WordPress</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo esc_html($wp_version); ?></span>
	</div>
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">GDS</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0 gds-version"></span>
	</div>
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">PHP</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo phpversion(); ?></span>
	</div>
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">Network users</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo number_format($total_network_users); ?></span>
	</div>
	<div class="hale-dash-metric">
		<h3 class="govuk-heading-s govuk-!-margin-bottom-1">Logged in now</h3>
		<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo $active_sessions; ?></span>
	</div>

	<?php $page_content = hale_dash_page_content_html(); ?>
	<?php if ($page_content) : ?>
		<div class="hale-dash-metric hale-dash-metric--wide hale-dash-page-content">
			<?php echo $page_content; ?>
		</div>
	<?php endif; ?>
</div>
