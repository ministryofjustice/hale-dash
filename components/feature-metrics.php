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

	<div class="hale-dash-metric hale-dash-metric--wide">
		<h2 class="govuk-heading-s">Monitoring resources</h2>
		<ul class="govuk-list govuk-body-s">
			<li>
				<a href="https://github.com/ministryofjustice/hale-platform">Hale Platform GitHub repository</a>
			</li>
			<li>
				<a href="https://grafana.live.cloud-platform.service.justice.gov.uk/d/85a562078cdf77779eaa1add43ccec1e/kubernetes-compute-resources-namespace-pods?orgId=1&refresh=10s&var-datasource=default&var-cluster=&var-namespace=hale-platform-prod">Grafana dashboard (prod)</a>
			</li>
			<li>
				<a href="https://grafana.live.cloud-platform.service.justice.gov.uk/d/k8s-nginx-ingress-prometheus-ng2/b89fb7c?orgId=1&refresh=1m&var-controller_class=$__all&var-pod=$__all&var-datasource=default&from=now-3h&to=now&var-namespace=$__all&var-ingress=hale-platform-ingress&timezone=browser">Grafana Nginx Ingress</a>
			</li>
			<li>
				<a href="https://logs.cloud-platform.service.justice.gov.uk/_dashboards/app/discover#/?_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now-15m,to:now))&_a=(columns:!(_source),filters:!(),index:b95d8900-dd15-11ed-87c8-170407f57c9c,interval:auto,query:(language:kuery,query:''),sort:!())">Ingress logs</a>
			</li>
			<li>
				<a href="https://cloud-platform-e218f50a4812967ba1215eaecede923f.s3.amazonaws.com/feed-parser/feeds.json">Job Feed Parser JSON</a>
			</li>
			<li>
				<a href="https://websitebuilder.service.justice.gov.uk/wp-json/hc-rest/v1/sites/domain">Platform site API</a>
			</li>
		</ul>
	</div>
</div>
