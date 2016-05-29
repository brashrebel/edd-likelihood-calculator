<?php
/**
 * User: kylemaurer
 * Date: 5/26/16
 * Time: 7:29 AM
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class EDD_LC_Table extends WP_List_Table {
	/**
	 * Number of items per page
	 *
	 * @var int
	 * @since 0.1
	 */
	public $per_page = 20;

	/**
	 * Are we searching for files?
	 *
	 * @var bool
	 * @since 0.1
	 */
	public $file_search = false;

	/**
	 * Store each unique product's files so they only need to be queried once
	 *
	 * @var array
	 * @since 0.1
	 */
	private $queried_files = array();

	/**
	 * Get things started
	 *
	 * @since 0.1
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular' => edd_get_label_singular(),
			'plural'   => edd_get_label_plural(),
			'ajax'     => false,
		) );

		add_action( 'edd_lc_view_actions', array( $this, 'downloads_filter' ) );
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 0.1
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'ID';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 0.1
	 *
	 * @param array $item Contains all the data of the log item
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'download' :
				return '<a href="' . add_query_arg( 'download', $item[ $column_name ] ) . '" >' . get_the_title( $item[ $column_name ] ) . '</a>';
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 0.1
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'ID'         => __( 'ID', 'edd-likelihood-calculator' ),
			'download'   => edd_get_label_singular(),
			'sales'    => __( 'Sales', 'edd-likelihood-calculator' ),
			'likelihood' => __( 'Likelihood', 'edd-likelihood-calculator' ),
		);
		return $columns;
	}

	/**
	 * Retrieves the ID of the download we're filtering downloads by
	 *
	 * @access public
	 * @since 0.1
	 * @return int Download ID
	 */
	public function get_filtered_download() {
		return ! empty( $_GET['download'] ) ? absint( $_GET['download'] ) : false;
	}

	/**
	 * Retrieve the current page number
	 *
	 * @access public
	 * @since 0.1
	 * @return int Current page number
	 */
	function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Outputs the stuff above the table
	 *
	 * @access public
	 * @since 0.1
	 * @return void
	 */
	public function bulk_actions( $which = '' ) {
		// These aren't really bulk actions but this outputs the markup in the right place
		$this->downloads_filter();
	}

	/**
	 * Sets up the downloads filter
	 *
	 * @access public
	 * @since 0.1
	 * @return void
	 */
	public function downloads_filter() {
		$downloads = get_posts( array(
			'post_type'              => 'download',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );
		?>
		<form id="edd-likelihood-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=download&page=edd-reports&tab=likelihood_calculator' ); ?>">
		<?php
		if ( $downloads ) {
			echo '<select name="download" id="edd-lc-download-filter">';
			echo '<option value="0">' . __( 'All', 'edd-likelihood-calculator' ) . '</option>';
			foreach ( $downloads as $download ) {
				echo '<option value="' . $download . '"' . selected( $download, $this->get_filtered_download() ) . '>' . esc_html( get_the_title( $download ) ) . '</option>';
			}
			echo '</select>';
			echo '<input type="hidden" name="post_type" value="download" />';
			echo '<input type="hidden" name="page" value="edd-reports" />';
			echo '<input type="hidden" name="tab" value="likelihood_calculator" />';
			submit_button( __( 'Apply', 'edd-likelihood-calculator' ), 'secondary', 'submit', false );
		}
		?>
		</form>
		<?php
	}

	/**
	 * Gets all customers who've purchased a specific download
	 *
	 * @param $id
	 *
	 * @return array
	 */
	function get_customers( $id ) {
		$args      = array(
			'download' => $id,
		);
		$payments  = edd_get_payments( $args );
		$customers = array();

		foreach ( $payments as $payment ) {
			$customer_email = edd_get_payment_user_email( $payment->ID );
			if ( ! in_array( $customer_email, $customers ) ) {
				$customers[] = $customer_email;
			}
		}

		return $customers;
	}

	/**
	 * Gets all downloads purchased by specific customers
	 *
	 * @param $customers
	 *
	 * @return array
	 */
	function get_all_downloads_for_customers( $customers ) {

		$downloads = array();

		foreach ( $customers as $customer ) {
			$purchased = edd_get_users_purchased_products( $customer );
			if ( $purchased ) {
				foreach ( $purchased as $purchase ) {
					if ( $purchase->ID !== $this->get_filtered_download() ) {
						$downloads[] = $purchase->ID;
					}
				}
			}
		}
		return $downloads;
	}

	/**
	 * Used for sorting the results of the downloads query by # of related sales
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	function sort_downloads( $a, $b ) {
		return strcmp( $b["sales"], $a["sales"] );
	}

	function total_items() {
		$items = $this->get_all_downloads_for_customers( $this->get_customers( $this->get_filtered_download() ) );
		$total = count( array_unique( $items ) );
		return $total;
	}
	/**
	 * Gets the downloads for the current view
	 *
	 * @access public
	 * @since 0.1
	 * @return array $data Array of all the relevant downloads
	 */
	function get_downloads() {
		$data      = array();
		$selected_download = $this->get_filtered_download();
		if ( $selected_download ) {
			$customers    = $this->get_customers( $selected_download );
			$download_ids = $this->get_all_downloads_for_customers( $customers );
			$selected_sales = edd_get_download_sales_stats( $selected_download );

			if ( $download_ids ) {

				$count = array_count_values( $download_ids );

				$args = array(
					'post_type'      => 'download',
					'post__in'       => $download_ids,
					'posts_per_page'   => $this->per_page,
					'paged'            => $this->get_paged(),
				);
				$downloads = new WP_Query( $args );
				if ( $downloads->have_posts() ) {
					while ( $downloads->have_posts() ) {
						$downloads->the_post();
						$id     = get_the_ID();
						$rate = number_format( 100 * $count[$id] / $selected_sales, 2 ) . '%';
						$data[] = array(
							'ID'         => $id,
							'download'   => $id,
							'sales'      => $count[ $id ],
							'likelihood' => $rate,
						);
					}
				}
			}
		}
		usort( $data, array( $this, 'sort_downloads' ) );
		return $data;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 0.1
	 * @uses EDD_LC_Table::get_columns()
	 * @uses WP_List_Table::get_sortable_columns()
	 * @uses EDD_LC_Table::get_downloads()
	 * @uses EDD_LC_Table::total_items()
	 * @uses WP_List_Table::set_pagination_args()
	 * @return void
	 */
	function prepare_items() {

		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->get_downloads();
		$total_items           = $this->total_items();

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $total_items / $this->per_page ),
			)
		);
	}

	/**
	 * Since our "bulk actions" are navigational, we want them to always show, not just when there's items
	 *
	 * @access public
	 * @since 0.1
	 * @return bool
	 */
	public function has_items() {
		return true;
	}
}