<?php
/**
 * Newsletter form: wraps the inner blocks in a <form> that submits through
 * the Interactivity API store, or posts to admin-post.php without JS.
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Derived state mirrors the getters in view.ts so server-side directive
 * processing renders the right hidden/disabled attributes (e.g. after a no-JS
 * submission); on the client the view.ts getters take over.
 */
$pc_flag = static function ( $test ) {
	return static function () use ( $test ) {
		$context = wp_interactivity_get_context( 'profchoice/newsletter' );
		return $test( isset( $context['status'] ) ? $context['status'] : 'idle', ! empty( $context['hasPending'] ) );
	};
};
wp_interactivity_state(
	'profchoice/newsletter',
	array(
		'restUrl'      => rest_url( 'profchoicecore/v1/subscribe' ),
		'isSubmitting' => $pc_flag(
			static function ( $status ) {
				return 'submitting' === $status;
			}
		),
		'isSuccess'    => $pc_flag(
			static function ( $status, $has_pending ) {
				return 'success' === $status || ( 'pending' === $status && ! $has_pending );
			}
		),
		'isPending'    => $pc_flag(
			static function ( $status, $has_pending ) {
				return 'pending' === $status && $has_pending;
			}
		),
		'isDone'       => $pc_flag(
			static function ( $status ) {
				return 'success' === $status || 'pending' === $status;
			}
		),
		'isError'      => $pc_flag(
			static function ( $status ) {
				return 'error' === $status;
			}
		),
	)
);

// Result of a no-JS submission (see ProfChoiceCore\Newsletter\Subscribe::handle_post()).
$pc_result = isset( $_GET['pc-newsletter'] ) ? sanitize_key( wp_unslash( $_GET['pc-newsletter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
// "confirmed" comes back from the email confirmation link.
$pc_result = 'confirmed' === $pc_result ? 'success' : $pc_result;
$pc_status = in_array( $pc_result, array( 'success', 'pending', 'error' ), true ) ? $pc_result : 'idle';
// Forms without a Pending message show the Success one instead.
$pc_has_pending = false !== strpos( $content, 'data-pc-message="pending"' );
$pc_done        = in_array( $pc_status, array( 'success', 'pending' ), true );
$pc_shown       = 'pending' === $pc_status && ! $pc_has_pending ? 'success' : $pc_status;
$pc_anchor = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : 'newsletter';

// Wire the inner markup to the store.
$pc_inner = new WP_HTML_Tag_Processor( $content );
while ( $pc_inner->next_tag() ) {
	$pc_tag = $pc_inner->get_tag();

	if ( 'BUTTON' === $pc_tag && 'submit' === $pc_inner->get_attribute( 'type' ) ) {
		$pc_inner->set_attribute( 'data-wp-bind--disabled', 'state.isSubmitting' );
	} elseif ( 'DIV' === $pc_tag && $pc_inner->has_class( 'wp-block-buttons' ) ) {
		// Hide the whole buttons row once subscribed, not just the button.
		$pc_inner->set_attribute( 'data-wp-bind--hidden', 'state.isDone' );
		if ( $pc_done ) {
			$pc_inner->set_attribute( 'hidden', true );
		}
	} elseif ( 'INPUT' === $pc_tag && 'email' === $pc_inner->get_attribute( 'type' ) ) {
		$pc_inner->set_attribute( 'data-wp-bind--hidden', 'state.isDone' );
		if ( $pc_done ) {
			$pc_inner->set_attribute( 'hidden', true );
		}
	} elseif ( null !== $pc_inner->get_attribute( 'data-pc-message' ) ) {
		$pc_type  = (string) $pc_inner->get_attribute( 'data-pc-message' );
		$pc_type  = in_array( $pc_type, array( 'pending', 'error' ), true ) ? $pc_type : 'success';
		$pc_flags = array(
			'success' => '!state.isSuccess',
			'pending' => '!state.isPending',
			'error'   => '!state.isError',
		);
		$pc_inner->set_attribute( 'data-wp-bind--hidden', $pc_flags[ $pc_type ] );
		if ( $pc_type !== $pc_shown ) {
			$pc_inner->set_attribute( 'hidden', true );
		}
	}
}

$pc_wrapper = get_block_wrapper_attributes(
	array(
		'id'     => $pc_anchor,
		'method' => 'post',
		'action' => admin_url( 'admin-post.php' ),
	)
);
?>
<form
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="profchoice/newsletter"
	<?php echo wp_interactivity_data_wp_context( array(
			'status'     => $pc_status,
			'hasPending' => $pc_has_pending,
		) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-on--submit="actions.submit"
	data-wp-bind--aria-busy="state.isSubmitting"
>
	<input type="hidden" name="action" value="profchoicecore_subscribe" />
	<input type="hidden" name="source" value="<?php echo esc_attr( (string) get_queried_object_id() ); ?>" />
	<input type="hidden" name="anchor" value="<?php echo esc_attr( $pc_anchor ); ?>" />
	<p class="pc-newsletter__hp" aria-hidden="true">
		<label>
			<?php esc_html_e( 'Leave this field empty', 'profchoicecore' ); ?>
			<input type="text" name="website" value="" tabindex="-1" autocomplete="off" />
		</label>
	</p>
	<?php echo $pc_inner->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</form>
