
<?php
/**
 * Title: Asteria / About — Image Left
 * Slug: asteria/about-image-left
 * Categories: about
 * Keywords: about, image, story
 * Description: Premium split About section with an editable image.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- wp:group {"align":"full","className":"as-section as-about","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull as-section as-about">

<!-- wp:columns {"align":"wide","verticalAlignment":"center","className":"as-split as-about-left"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center as-split as-about-left">

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">

<!-- wp:group {"className":"as-media","layout":{"type":"constrained"}} -->
<div class="wp-block-group as-media">

<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"as-about-image"} -->
<figure class="wp-block-image size-large as-about-image"><img src="<?php echo esc_url(get_theme_file_uri('assets/images/about-placeholder.svg')); ?>" alt="Replace with your studio or team photograph"/></figure>
<!-- /wp:image -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center">

<!-- wp:group {"className":"as-copy","layout":{"type":"constrained"}} -->
<div class="wp-block-group as-copy">

<!-- wp:paragraph {"className":"as-kicker"} -->
<p class="as-kicker">01 / WHO WE ARE</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"className":"as-heading"} -->
<h2 class="wp-block-heading as-heading">Ideas that inspire. <em>Experiences that matter.</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"as-description"} -->
<p class="as-description">We're a creative digital studio bringing strategy, design and technology together. We create thoughtful digital experiences that help ambitious businesses move forward.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"as-description"} -->
<p class="as-description">Every project starts with listening, understanding and finding the opportunities that make a meaningful difference.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">

<!-- wp:button {"className":"as-button"} -->
<div class="wp-block-button as-button"><a class="wp-block-button__link wp-element-button" href="/about-us/">Discover Our Story ↗</a></div>
<!-- /wp:button -->

</div>
<!-- /wp:buttons -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
