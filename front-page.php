<?php
/**
 * Template Name: Dashboard Page
 *
 * @package Hale Dash
 * @copyright Ministry of Justice
 * @version 2.0
 */

get_header();

function ping($host, $port, $timeout) { 
    // Copied from https://stackoverflow.com/questions/1239068/ping-site-and-return-result-in-php
    $tB = microtime(true); 
    $fP = fSockOpen($host, $port, $errno, $errstr, $timeout); 
    if (!$fP) {
        return '<span><strong class="govuk-tag govuk-tag--red">Down</strong></span>';
    }
    
    $tA = microtime(true); 
    $wait = round((($tA - $tB) * 1000), 0);
    if ($wait < 50) {
        return '<span><strong class="govuk-tag govuk-tag--green">Up '.$wait.' ms</strong></span>';
    }
    return '<span><strong class="govuk-tag govuk-tag--yellow">Up '.$wait.' ms</strong></span>';
}

$environments = [
    'prod',
    'staging',
    'dev',
    'demo'
];

$sites = get_sites();
?>
<h1 class="govuk-heading-l govuk-grid-column-full govuk-!-margin-top-6">Hale site dashboards</h1>
<div class="govuk-grid-column-full">
<?php
foreach ( $sites as $site ) {
    switch_to_blog( $site->blog_id ); ?>
    <div class="website">
        <h2 class="website__heading govuk-heading-s"><?php echo get_bloginfo('name'); ?></h2>

        <?php
        foreach ($environments as $env) { 
            
            $env_url = "https://hale-platform-" . $env . ".apps.live.cloud-platform.service.justice.gov.uk/";

            ?>
            <a href="<?php echo $env_url; ?>" class="website__link website__link--<?php echo $env;?> govuk-link"><?php echo ucfirst($env);?></a>
            <?php if ($env == "prod") $status = ping($env_url, 80, 10); ?>
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



