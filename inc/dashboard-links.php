<?php

/**
 * Dashboard links — the "Monitoring resources", "Platform value" etc. headings
 * and link lists under the platform metrics come from the dashboard page's own
 * content, so they are edited in the block editor like any other page.
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * The current page's content, with GOV.UK classes added so editor headings,
 * lists and paragraphs match the rest of the metrics column.
 */
function hale_dash_page_content_html() {
	$page = get_queried_object();
	if (!$page instanceof WP_Post || trim($page->post_content) === '') {
		return '';
	}

	$html = apply_filters('the_content', $page->post_content);
	$tags = new WP_HTML_Tag_Processor($html);

	while ($tags->next_tag()) {
		switch ($tags->get_tag()) {
			case 'H2':
			case 'H3':
			case 'H4':
			case 'H5':
			case 'H6':
				$tags->add_class('govuk-heading-s');
				break;
			case 'UL':
			case 'OL':
				$tags->add_class('govuk-list');
				$tags->add_class('govuk-body-s');
				break;
			case 'P':
				$tags->add_class('govuk-body-s');
				break;
		}
	}

	return $tags->get_updated_html();
}
