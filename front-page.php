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

<div class="govuk-width-container">
  <main class="govuk-main-wrapper">
    <div class="govuk-grid-row">
      <div class="govuk-grid-column-two-thirds">
      <h2 class="gem-c-heading gem-c-heading--font-size-27">Sites hosted on the platform</h2>
        <span class="govuk-heading-xl"><?php echo get_site_count(); ?></span>
      </div>
    </div>
  </main>
</div>

<div class="govuk-grid-column-full">
<?php
foreach ( $sites as $site ) {
    switch_to_blog( $site->blog_id );
    $site_name = trim(get_bloginfo('name'));
    $iconSrc = get_site_icon_url();
    if (!empty($iconSrc)) {
        $icon = fav_icon($iconSrc);
    } else {
        $icon = fav_icon("http://www.google.com/s2/favicons?domain=".$live_urls[$site_name], true);
    }
    ?>
    <div class="website">
        <div class="website__heading">
            <?php
            echo $icon;
            if ($site_name == "Hale Platform" || $site_name == "Root site") {
                echo "<h2 class='website__heading__text govuk-heading-s'>Hale Platform Dashboard</h2>";
                echo "<p class='govuk-body govuk-hint govuk-!-margin-bottom-0'>This site</p>";
            } else {
                echo "<h2 class='website__heading__text govuk-heading-s'>$site_name</h2>";
            }
            ?>
            
            </h2>
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
                    if (isset($live_urls[trim($site_name)])) $env_url = $live_urls[trim($site_name)];
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



