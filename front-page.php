<?php
/**
 * Template Name: Dashboard Page
 *
 * @package Hale Dash
 * @copyright Ministry of Justice
 * @version 2.0
 */

get_header();

$live_urls = get_live_urls();

$environments = [
    'prod',
    'staging',
    'dev',
    'demo'
];

$this_url = get_bloginfo('url');
$sites = get_sites();

echo festiveGreeting(time());
echo getBrithday();
?>
<h1 class="govuk-heading-l govuk-grid-column-full govuk-!-margin-top-6">Platform metrics</h1>
<main class="govuk-main-wrapper">
<div class="govuk-width-container">
    
    <div class="govuk-grid-row">
      <div class="govuk-grid-column-one-third">
      <h2 class="gem-c-heading gem-c-heading--font-size-27">Sites hosted on the platform</h2>
        <span class="govuk-heading-xl"><?php echo get_site_count(); ?></span>
      </div>
      <div class="govuk-grid-column-one-third">
      <h2 class="gem-c-heading gem-c-heading--font-size-27">Site monitoring and performance</h2>
        <a href="https://github.com/ministryofjustice/hale-platform/actions/workflows/cd.yaml">
        <img src="https://github.com/ministryofjustice/hale-platform/actions/workflows/cd.yaml/badge.svg?branch=main" alt="Hale Platform Deployment">
        </a>
        <ul class="govuk-list govuk-!-margin-bottom-9">
        <li>
        <a href="https://github.com/ministryofjustice/hale-platform">Hale Platform GitHub repository</a>
        </li>
            <li>
        <a href="https://grafana.live.cloud-platform.service.justice.gov.uk/d/85a562078cdf77779eaa1add43ccec1e/kubernetes-compute-resources-namespace-pods?orgId=1&refresh=10s&var-datasource=default&var-cluster=&var-namespace=hale-platform-prod">Grafana dashboard (prod)</a>
        </li>
        <li> 
        <a href="https://logs.cloud-platform.service.justice.gov.uk/_dashboards/app/discover#/?_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now-15m,to:now))&_a=(columns:!(_source),filters:!(),index:b95d8900-dd15-11ed-87c8-170407f57c9c,interval:auto,query:(language:kuery,query:''),sort:!())">Ingress logs</a>
    </li> 
    </ul>    
    </div>
    <div class="govuk-grid-column-one-third">
      <h2 class="gem-c-heading gem-c-heading--font-size-27">Next site to go live:</h2>
      <p> Prisons and Probation Ombudsman (PPO) - https://www.ppo.gov.uk/</p>
</div>
    </div>

</div>

<div class="govuk-grid-column-full">
<?php
foreach ( $sites as $site ) {
    switch_to_blog( $site->blog_id ); ?>
    <div class="website">
        <div class="website__heading">
            <?php
                if (isset($live_urls[get_bloginfo('name')])) echo fav_icon("http://www.google.com/s2/favicons?domain=".$live_urls[get_bloginfo('name')]);
            ?>
            <h2 class="website__heading__text govuk-heading-s"><?php echo get_bloginfo('name'); ?></h2>
        </div>
        <?php
        foreach ($environments as $env) { 
            
            $site_path_slug = "";
            
            if ( get_option( 'site_path_slug' ) ) {
                $site_path_slug = get_option('site_path_slug');
            }

            $env_url = "https://hale-platform-$env.apps.live.cloud-platform.service.justice.gov.uk/$site_path_slug";

            ?>
            <div class="website__environment">
            <?php
                if ($env == "prod") {
                    if (isset($live_urls[trim(get_bloginfo('name'))])) $env_url = $live_urls[trim(get_bloginfo('name'))];
                    
                        if (is_plugin_active_on_site('wp-force-login/wp-force-login.php', $site->blog_id)) {
                            // Plugin is active on the specified site.
                            $status = '<span class="website__up-down"><strong class="govuk-tag govuk-tag--grey">Private</strong></span>';
                        } else {
                            // Plugin is inactive on the specified site.
                            $status = '<span class="website__up-down"><strong class="govuk-tag">Public</strong></span>';
                        }

                    if (strpos($env_url, "http") === false) $env_url = "https://".$env_url;
                }
                echo "<a href='$env_url' class='website__environment__link website__environment__link--$env govuk-link'>".ucfirst($env)."</a>";
            ?>
            </div>
        <?php

        }
            echo $status;
        ?>
    </div>
    <?php
    restore_current_blog();
}
?>
</div>
</main>
<?php
get_footer();



