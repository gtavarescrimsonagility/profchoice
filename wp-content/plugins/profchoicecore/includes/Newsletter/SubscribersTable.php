<?php
/**
 * Subscribers list table: the `subscriber` users' emails, read-only, with
 * search, sorting and pagination, like core's users list.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

use WP_List_Table;
use WP_User;
use WP_User_Query;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The Subscribers screen's table.
 */
final class SubscribersTable extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'subscriber',
				'plural'   => 'subscribers',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'email' => __( 'Email', 'profchoicecore' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	protected function get_sortable_columns() {
		return array(
			'email' => array( 'email', false ),
		);
	}

	/**
	 * Primary column.
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'email';
	}

	/**
	 * Queries the subscribers for the current page, search and order.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list parameters.
		$per_page = $this->get_items_per_page( 'pc_subscribers_per_page', 20 );
		$orderby  = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'registered';
		$order    = isset( $_REQUEST['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_REQUEST['order'] ) ) ) ? 'ASC' : 'DESC';
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		// phpcs:enable

		$args = array(
			'role'    => Subscribers::ROLE,
			'number'  => $per_page,
			'paged'   => $this->get_pagenum(),
			'orderby' => in_array( $orderby, array( 'email', 'login', 'registered' ), true ) ? $orderby : 'registered',
			'order'   => $order,
		);
		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_email', 'user_login' );
		}

		$query       = new WP_User_Query( $args );
		$this->items = $query->get_results();

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'email' );
		$this->set_pagination_args(
			array(
				'total_items' => $query->get_total(),
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Email column.
	 *
	 * @param WP_User $item Subscriber.
	 * @return string
	 */
	protected function column_email( $item ) {
		return esc_html( $item->user_email );
	}

	/**
	 * Empty list message.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No subscribers yet.', 'profchoicecore' );
	}
}
