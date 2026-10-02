<?php
/**
 * PDP breadcrumb.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>
<nav class="pdp-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'profchoice' ); ?>">
	<div class="container wide">
		<ol class="pdp-breadcrumb__list">
			<?php foreach ( profchoice_product_trail( $product ) as $crumb ) : ?>
				<li class="pdp-breadcrumb__item">
					<?php if ( $crumb['url'] ) : ?>
						<a class="pdp-breadcrumb__link" href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['label'] ); ?></a>
					<?php else : ?>
						<span class="pdp-breadcrumb__current" aria-current="page"><?php echo esc_html( $crumb['label'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</nav>
