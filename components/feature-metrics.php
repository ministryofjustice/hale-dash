<?php
    // Next site to go live details
    $next_site_name = ""; // match site name on Hale
    $next_site_abbr = "";
    $next_site_url = "";

    // Current environment
    $this_url = get_bloginfo('url');
    $this_env = ucfirst(getenv('WP_ENVIRONMENT_TYPE'));

    // How many live sites
    $live_site_count = 0;
    foreach ($sites as $site) {
        if (!is_plugin_active_on_site('wp-force-login/wp-force-login.php', $site->blog_id)) $live_site_count++;
    }
?>
<div class="hale-dash-metrics">
    <div class="hale-dash-metric">
        <h2 class="govuk-heading-s govuk-!-margin-bottom-1">Current Environment</h2>
        <span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo $this_env; ?></span>
    </div>
    <div class="hale-dash-metric">
        <h2 class="govuk-heading-s govuk-!-margin-bottom-1">Sites hosted</h2>
        <span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo get_site_count(); ?></span>
    </div>
    <div class="hale-dash-metric">
        <h2 class="govuk-heading-s govuk-!-margin-bottom-1">Public sites</h2>
        <span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo $live_site_count; ?></span>
    </div>
    <div class="hale-dash-metric">
        <h2 class="govuk-heading-s govuk-!-margin-bottom-1">WordPress</h2>
        <span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo $wp_version; ?></span>
    </div>
    <div class="hale-dash-metric">
        <h2 class="govuk-heading-s govuk-!-margin-bottom-1">GDS</h2>
        <span class="govuk-heading-l govuk-!-margin-bottom-0 gds-version"></span>
    </div>
    <div class="hale-dash-metric">
        <h2 class="govuk-heading-s govuk-!-margin-bottom-1">PHP</h2>
        <span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo phpversion(); ?></span>
    </div>

    <div class="hale-dash-metric hale-dash-metric--wide">
        <h2 class="govuk-heading-s">Site monitoring and performance</h2>
        <ul class="govuk-list govuk-body-s">
            <li>
                <a href="https://github.com/ministryofjustice/hale-platform">Hale Platform GitHub repository</a>
            </li>
            <li>
                <a href="https://grafana.live.cloud-platform.service.justice.gov.uk/d/85a562078cdf77779eaa1add43ccec1e/kubernetes-compute-resources-namespace-pods?orgId=1&refresh=10s&var-datasource=default&var-cluster=&var-namespace=hale-platform-prod">Grafana dashboard (prod)</a>
            </li>
            <li>
                <a href="https://logs.cloud-platform.service.justice.gov.uk/_dashboards/app/discover#/?_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now-15m,to:now))&_a=(columns:!(_source),filters:!(),index:b95d8900-dd15-11ed-87c8-170407f57c9c,interval:auto,query:(language:kuery,query:''),sort:!())">Ingress logs</a>
            </li>
            <li>
                <a href="https://cloud-platform-e218f50a4812967ba1215eaecede923f.s3.amazonaws.com/feed-parser/feeds.json">Job Feed Parser JSON</a>
            </li>
        </ul>
    </div>
</div>
