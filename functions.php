<?php

/**
 * Hale Dash theme functions and definitions
 *
 * @package   Hale Dash
 * @copyright Ministry Of Justice
 * @version   2.0
 */

/**
 * Sets up theme defaults and registers support for various WordPress features.
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function hale_dash_setup() {}

add_action('after_setup_theme', 'hale_dash_setup');

function hale_dash_enqueue_styles() {
	wp_enqueue_style( 'hale-dash-style',
        hale_dash_mix_asset('/css/hale-dash-style.min.css'),
		array( 'hale-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'hale_dash_enqueue_styles' );

/**
 * Gett asset path function
 * 
 * @param $filename
 * @return string
 */
function hale_dash_mix_asset($filename)
{
    $manifest = file_get_contents(get_theme_file_path() . '/dist/mix-manifest.json');   
    $manifest = json_decode($manifest, true);

    if (!isset($manifest[$filename])) {
        error_log("Mix asset '$filename' does not exist in manifest.");
    }

    return get_theme_file_uri() . '/dist' . $manifest[$filename];
}

function ping($host, $port, $timeout) {
    // Copied from https://stackoverflow.com/questions/1239068/ping-site-and-return-result-in-php
    $tB = microtime(true);
    $fP = fSockOpen($host, $port, $errno, $errstr, $timeout);
    if (!$fP) {
        return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--red">Down</strong></span>';
    }

    $tA = microtime(true);
    $wait = round((($tA - $tB) * 1000), 0);
    if ($wait < 50) {
        return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--green">Good</strong></span>';
    }
    return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--yellow">Okay</strong></span>';
    // below will shew time taken in MS
    return '<span class="website__up-down"><strong class="govuk-tag govuk-tag--blue">Up '.$wait.' ms</strong></span>';
}

function get_live_urls() {
    $url["Criminal Cases Review Commission"] = "ccrc.gov.uk";
    $url["Magistrates Recruitment"] = "magistrates.judiciary.uk";
    $url["Hawlfraint, Recriwtio Ynadon"] = "magistrates.judiciary.uk";
    $url["Recriwtio Ynadon"] = "magistrates.judiciary.uk";
    $url["Victims Commissioner"] = "victimscommissioner.org.uk";
    $url["Independent Monitoring Boards"] = "imb.org.uk";
    $url["Independent Commission for Reconciliation &amp; Information Recovery"] = "icrir.independent-inquiry.uk";
    $url["Public Defender Service"] = "publicdefenderservice.org.uk";
    $url["Prison and Probation Jobs"] = "prisonandprobationjobs.gov.uk";
    $url["Brook House Inquiry"] = "brookhouseinquiry.org.uk";

    return $url;
}

function fav_icon($image) {
    // From https://stackoverflow.com/questions/5701593/how-to-get-a-websites-favicon-with-php
    // Read image path, convert to base64 encoding
    $imageData = base64_encode(file_get_contents($image));

    if (empty($imageData)) return;

    // Format the image SRC:  data:{mime};base64,{data};
    $src = 'data: '.mime_content_type($image).';base64,'.$imageData;

    // Echo out a sample image
    return '<img src="' . $src . '">';
}
