
<?php
/**
 * Title: Asteria / About — Image Right
 * Slug: asteria/about-image-right
 * Categories: about
 * Keywords: about, team, story
 * Description: Editable About section with the visual on the right.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- wp:group {"align":"full","className":"as-section as-about as-about-alt","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull as-section as-about as-about-alt">

<!-- wp:columns {"align":"wide","verticalAlignment":"center","className":"as-split"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center as-split">

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">

<!-- wp:group {"className":"as-copy","layout":{"type":"constrained"}} -->
<div class="wp-block-group as-copy">

<!-- wp:paragraph {"className":"as-kicker"} -->
<p class="as-kicker">02 / OUR PHILOSOPHY</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"className":"as-heading"} -->
<h2 class="wp-block-heading as-heading">Built with purpose. <em>Driven by imagination.</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"as-description"} -->
<p class="as-description">We believe exceptional digital work comes from the intersection of creativity, clarity and engineering.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"as-description"} -->
<p class="as-description">Our approach focuses on real business challenges, meaningful customer experiences and solutions designed to last.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">

<!-- wp:button {"className":"as-button"} -->
<div class="wp-block-button as-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Work With Us ↗</a></div>
<!-- /wp:button -->

</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">

<!-- wp:group {"className":"as-media","layout":{"type":"constrained"}} -->
<div class="wp-block-group as-media">

<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"as-about-image"} -->
<figure class="wp-block-image size-large as-about-image"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/about-placeholder.svg')); ?>" alt="Replace with a photograph representing your team or work"/></figure>
<!-- /wp:image -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
