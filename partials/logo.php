<?php
/**
 * Logo partial — hale-dash child theme override.
 *
 * Renders the site name unconditionally so the dashboard header is always
 * visible regardless of whether logo_configuration is set on this child theme.
 * Falls back to parent theme mods where available.
 */

$parent_mods      = get_option('theme_mods_' . get_template(), []);
$child_mods       = get_option('theme_mods_' . get_stylesheet(), []);

// Merge: child overrides parent for any key that is explicitly set and non-empty.
$all_mods = is_array($parent_mods) ? $parent_mods : [];
if (is_array($child_mods)) {
    foreach ($child_mods as $key => $value) {
        if ($value !== '' && $value !== false && $value !== null) {
            $all_mods[$key] = $value;
        }
    }
}

$logo_has_link    = isset($all_mods['logo_has_link'])    ? $all_mods['logo_has_link']    : 'yes';
$logo_custom_link = isset($all_mods['logo_custom_link']) ? $all_mods['logo_custom_link'] : '';
$org_name_checkbox = isset($all_mods['org_name_checkbox']) ? $all_mods['org_name_checkbox'] : 'no';
$org_name_field   = isset($all_mods['org_name_field'])   ? $all_mods['org_name_field']   : '';
$logo_aria_label  = isset($all_mods['logo_aria_label'])  ? $all_mods['logo_aria_label']
                    : get_bloginfo('name') . ' ' . __('homepage', 'hale');

$logo_line_1 = ('yes' === $org_name_checkbox && !empty($org_name_field))
    ? $org_name_field
    : get_bloginfo('name');

$logo_link = !empty($logo_custom_link) ? $logo_custom_link : get_home_url();

$show_custom_logo = has_custom_logo();
$logo_configuration = isset($all_mods['logo_configuration']) ? $all_mods['logo_configuration'] : 'name';
$show_sitelogo = in_array($logo_configuration, ['logo', 'both']) ? 'yes' : '';
$show_sitename = in_array($logo_configuration, ['name', 'both']) ? 'yes' : 'yes'; // always show name
?>
<div class="govuk-header__logo">
    <?php if ($logo_has_link === 'yes'): ?>
    <a class="govuk-header__link govuk-header__link--homepage"
       href="<?php echo esc_url($logo_link); ?>"
       aria-label="<?php echo esc_attr($logo_aria_label); ?>">
    <?php endif; ?>

    <div class="govuk-header__logotype <?php echo $show_sitelogo === 'yes' ? '' : 'hale-header__logotype--no-logo'; ?>">
        <?php if ($show_sitelogo === 'yes'): ?>
            <?php if ($show_custom_logo): ?>
                <div class="hale-header__logo--custom">
                    <?php echo wp_get_attachment_image(get_theme_mod('custom_logo'), 'full'); ?>
                </div>
            <?php else: ?>
                <svg role="presentation" focusable="false" class="moj-header__logotype-crest"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 702.47 624.08" height="40" width="47">
                    <?php include get_template_directory() . '/partials/govuk-crest-svg-content.php'; ?>
                    <image src="/assets/images/gov-crest-white.png" xlink:href=""
                           class="govuk-header__logotype-crown-fallback-image"
                           width="702.47" height="624.08"></image>
                </svg>
            <?php endif; ?>
        <?php endif; ?>

        <span class="govuk-header__logotype-text hale-header__logotype-text--custom">
            <?php echo esc_html($logo_line_1); ?>
        </span>
    </div>

    <?php if ($logo_has_link === 'yes'): ?>
    </a>
    <?php endif; ?>
</div>
