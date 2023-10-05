<?php
/**
 * Template Name: Dashboard Page
 *
 * @package Hale Dash
 * @copyright Ministry of Justice
 * @version 2.0
 */

get_header();

$marc = false;

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
?>
<h1 class="govuk-heading-l govuk-grid-column-full govuk-!-margin-top-6">Hale site dashboards</h1>
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
            $short_name = getShortName(get_bloginfo('url'),$this_url)
        ?>

        <?php
        foreach ($environments as $env) { 
            
            $env_url = "https://hale-platform-$env.apps.live.cloud-platform.service.justice.gov.uk/$short_name";

            ?>
            <div class="website__environment <?php if ($marc) echo "marc"; ?>">
                <div class="marcTracker" style="background-image:url(https://hale.docker/wp-content/themes/hale-dash/assets/images/marc.png);"></div>
            <?php
                if ($env == "prod") {
                    if (isset($live_urls[trim(get_bloginfo('name'))])) $env_url = $live_urls[trim(get_bloginfo('name'))];
                    $status = ping($env_url, 80, 10);
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
<?php
get_footer();



