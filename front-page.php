<?php
/**
 * Template Name: Dashboard Page
 *
 * @package Hale Dash
 * @copyright Ministry of Justice
 * @version 2.0
 */

get_header();

$environments = [
    'prod',
    'staging',
    'dev',
    'demo'
];

$sites = get_sites();
foreach ( $sites as $site ) {
    switch_to_blog( $site->blog_id ); ?>

    <div>
        <?php echo get_bloginfo('name'); ?>

        <?php
        foreach ($environments as $env) { 
            
            $env_url = "https://hale-platform-" . $env . ".apps.live.cloud-platform.service.justice.gov.uk/"
            
            ?>
            <a href="<?php echo $env_url; ?>" class="govuk-button"><?php echo ucfirst($env);?></a>
        <?php

        }

        ?>
    </div>
    <?php
    restore_current_blog();
}


get_footer();



