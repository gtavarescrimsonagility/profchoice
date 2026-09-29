<?php
/**
 * Customizer control for ordering product page elements.
 *
 * Renders one sortable list per area. The whole layout is serialized to JSON
 * in a single hidden setting, so reordering costs no extra options rows.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return;
}

/**
 * Sortable layout control.
 *
 * @since 0.7.0
 */
class CBV_Layout_Control extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'cbv_layout';

	/**
	 * Areas rendered by this control: key => label.
	 *
	 * @var array
	 */
	public $areas = array();

	/**
	 * Which element library this control edits: product|catalog.
	 *
	 * @var string
	 */
	public $library = 'product';

	/**
	 * Enqueue control assets.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script(
			'cbv-layout-control',
			CBV_URI . 'assets/js/layout-control.js',
			array( 'jquery', 'jquery-ui-sortable', 'customize-controls' ),
			CBV_VERSION,
			true
		);

		wp_enqueue_style(
			'cbv-layout-control',
			CBV_URI . 'assets/css/layout-control.css',
			array( 'dashicons' ),
			CBV_VERSION
		);
	}

	/**
	 * Render the control.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function render_content() {
		if ( 'catalog' === $this->library ) {
			$definitions = CBV_Catalog_Elements::definitions();
			$schema      = CBV_Catalog_Elements::option_schema();
			$layout      = CBV_Catalog_Layout::layout();
		} else {
			$definitions = CBV_Product_Elements::definitions();
			$schema      = CBV_Product_Elements::option_schema();
			$layout      = CBV_Product_Layout::layout();
		}
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php if ( $this->description ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>

		<div class="cbv-layout" data-setting="<?php echo esc_attr( $this->id ); ?>">
			<?php foreach ( $this->areas as $area => $area_label ) : ?>
				<div class="cbv-layout__area" data-area="<?php echo esc_attr( $area ); ?>">
					<h4 class="cbv-layout__area-title"><?php echo esc_html( $area_label ); ?></h4>
					<ul class="cbv-layout__list">
						<?php
						$rows = isset( $layout[ $area ] ) ? $layout[ $area ] : array();

						foreach ( $rows as $row ) :
							$id = $row['id'];

							if ( ! isset( $definitions[ $id ] ) ) {
								continue;
							}

							$definition = $definitions[ $id ];
							$options    = isset( $row['options'] ) ? (array) $row['options'] : array();
							$fields     = isset( $schema[ $id ] ) ? $schema[ $id ] : array();
							?>
							<li class="cbv-layout__item" data-id="<?php echo esc_attr( $id ); ?>">
								<div class="cbv-layout__row">
									<span class="cbv-layout__handle dashicons dashicons-menu" aria-hidden="true"></span>
									<label class="cbv-layout__toggle">
										<input type="checkbox" class="cbv-layout__enabled" <?php checked( ! empty( $row['enabled'] ) ); ?>>
										<span><?php echo esc_html( $definition['label'] ); ?></span>
									</label>
									<?php if ( ! empty( $fields ) ) : ?>
										<button type="button" class="cbv-layout__settings-toggle" aria-label="<?php esc_attr_e( 'Element settings', 'commercebuild-velocity' ); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span></button>
									<?php endif; ?>
								</div>
								<?php if ( ! empty( $definition['description'] ) ) : ?>
									<p class="cbv-layout__hint"><?php echo esc_html( $definition['description'] ); ?></p>
								<?php endif; ?>
								<?php if ( ! empty( $fields ) ) : ?>
									<div class="cbv-layout__settings" hidden>
										<?php foreach ( $fields as $key => $field ) : ?>
											<?php
											$value = isset( $options[ $key ] ) ? $options[ $key ] : ( isset( $field['default'] ) ? $field['default'] : '' );
											?>
											<p class="cbv-layout__field">
												<label>
													<span><?php echo esc_html( $field['label'] ); ?></span>
													<?php if ( 'select' === $field['type'] ) : ?>
														<select class="cbv-layout__option" data-option="<?php echo esc_attr( $key ); ?>">
															<?php foreach ( $field['choices'] as $choice_value => $choice_label ) : ?>
																<option value="<?php echo esc_attr( $choice_value ); ?>" <?php selected( (string) $value, (string) $choice_value ); ?>>
																	<?php echo esc_html( $choice_label ); ?>
																</option>
															<?php endforeach; ?>
														</select>
													<?php elseif ( 'checkbox' === $field['type'] ) : ?>
														<input type="checkbox" class="cbv-layout__option" data-option="<?php echo esc_attr( $key ); ?>" data-type="checkbox" <?php checked( ! empty( $value ) ); ?>>
													<?php elseif ( 'textarea' === $field['type'] ) : ?>
														<textarea class="cbv-layout__option" data-option="<?php echo esc_attr( $key ); ?>" rows="3" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"><?php echo esc_textarea( (string) $value ); ?></textarea>
													<?php elseif ( 'number' === $field['type'] ) : ?>
														<input type="number" class="cbv-layout__option" data-option="<?php echo esc_attr( $key ); ?>" data-type="number" value="<?php echo esc_attr( (string) $value ); ?>" min="<?php echo esc_attr( isset( $field['min'] ) ? $field['min'] : 1 ); ?>" max="<?php echo esc_attr( isset( $field['max'] ) ? $field['max'] : 12 ); ?>">
													<?php else : ?>
														<input type="text" class="cbv-layout__option" data-option="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>">
													<?php endif; ?>
												</label>
											</p>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>

			<input type="hidden" <?php $this->link(); ?> class="cbv-layout__value" value="<?php echo esc_attr( $this->value() ); ?>">
		</div>
		<?php
	}
}
