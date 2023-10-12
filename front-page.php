<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Template Name: Dashboard Page
 *
 * @package Hale Dash
 * @copyright Ministry of Justice
 * @version 2.0
 */

get_header();

echo festiveGreeting(time());
echo getBrithday();
?>

<h1 class="govuk-heading-l govuk-grid-column-full govuk-!-margin-top-6">Platform metrics</h1>

<main class="govuk-main-wrapper">
    <?php include get_stylesheet_directory() . '/components/feature-metrics.php'; ?>

    <div class="govuk-grid-column-full">
        <?php include get_stylesheet_directory() . '/components/site-status-list.php'; ?>
    </div>
</main>

<?php
get_footer();
