<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Add Likelihood Calculator tab to the Reports page
 *
 * @since       0.1
 */
function edd_likelihood_calculator_tab() {
	$current_page = admin_url( 'edit.php?post_type=download&page=edd-reports' );
	$active_tab   = isset( $_GET['tab'] ) ? $_GET['tab'] : 'reports';
	?>
	<a href="<?php echo add_query_arg( array(
		'tab'              => 'likelihood_calculator',
		'settings-updated' => false
	), $current_page ); ?>"
	   class="nav-tab <?php echo $active_tab == 'likelihood_calculator' ? 'nav-tab-active' : ''; ?>">
		<?php _e( 'Likelihood Calculator', 'edd-likelihood-calculator' ); ?>
	</a>
	<?php
}

/**
 * Create reports page for likelihood calculator, inside EDD Reports section
 *
 * @since       0.1
 */
function edd_likelihood_calculator_page() {

	if ( ! current_user_can( 'view_shop_reports' ) ) {
		wp_die( __( 'You do not have permission to access this report', 'edd' ), __( 'Error', 'edd' ), array( 'response' => 403 ) );
	}
	// do stuff
	?>
	<div class="postbox">
		<div class="inside">
			<h3 class="alignleft">
				<?php _e( 'Likelihood Calculator', 'edd-likelihood-calculator' ); ?>
			</h3>
			<table class="wp-list-table widefat fixed striped ">
				<thead>
				<tr>
					<th scope="col" id='label' class='manage-column column-label column-primary'>Download</th>
					<th scope="col" id='total_sales' class='manage-column column-total_sales'>Total Sales</th>
					<th scope="col" id='most_related' class='manage-column column-most_related'>Most Related Download
					</th>
					<th scope="col" id='second_most' class='manage-column column-second_most'>2nd Most Related
						Download
					</th>
					<th scope="col" id='tickets' class='manage-column column-tickets'>Tickets</th>
				</tr>
				</thead>

				<tbody id="the-list">
				<?php
				$downloads = new WP_Query( 'post_type=download' );
				//var_dump($downloads->posts);

				if ( $downloads->have_posts() ) {
					foreach ( $downloads->posts as $post ) {
						$sales = edd_get_download_sales_stats( $post->ID );
						if ( $sales == 0 ) {
							continue;
						}
						$customers = eddlc_get_customers( $post->ID );
						$downloads = eddlc_get_all_downloads_for_customers( $customers );
						// Most common
						$most_common         = eddlc_get_most_common_value( $downloads, array( $post->ID ) );
						$count_most_common   = array_count_values( $downloads );
						$most_common_sales   = $count_most_common[ $most_common ];
						$most_common_rate    = $most_common_sales / $sales;
						$most_common_percent = number_format( $most_common_rate * 100, 2 ) . '%';
						// Second most common
						$second_most_common         = eddlc_get_most_common_value( $downloads, array(
							$post->ID,
							$most_common
						) );
						$second_count_most_common   = array_count_values( $downloads );
						$second_most_common_sales   = $count_most_common[ $second_most_common ];
						$second_most_common_rate    = $second_most_common_sales / $sales;
						$second_most_common_percent = number_format( $second_most_common_rate * 100, 2 ) . '%';
						// Tickets
						$ticket_count = eddlc_get_conversation_count( $post->post_name );
						$ticket_rate = number_format( eddlc_get_conversation_rate( $sales, $ticket_count ), 2 ) . '%';
						?>
						<tr>
							<td class='label column-label has-row-actions column-primary' data-colname="Download">
								<a href="<?php echo get_edit_post_link( $post->ID ); ?>"><?php echo $post->post_title; ?></a>
								<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span>
								</button>
							</td>
							<td class='total_sales column-total_sales' data-colname="Total Sales">
								<?php echo $sales; ?>
							</td>
							<td class='most_related column-most_related' data-colname="Most Related Download">
								<?php if ( $most_common_sales > 0 ) { ?>
									<a href="<?php echo get_edit_post_link( $most_common ); ?>">
										<?php echo get_the_title( $most_common ); ?>
									</a><br/>
									Sales: <?php echo $most_common_sales; ?><br/>
									Likelihood: <?php echo $most_common_percent; ?>
								<?php } else { ?>
									No related downloads
								<?php } ?>
							</td>
							<td class='second_most column-second_most' data-colname="2nd Most Related Download">
								<?php if ( $second_most_common_sales > 0 ) { ?>
									<a href="<?php echo get_edit_post_link( $second_most_common ); ?>">
										<?php echo get_the_title( $second_most_common ); ?>
									</a><br/>
									Sales: <?php echo $second_most_common_sales; ?><br/>
									Likelihood: <?php echo $second_most_common_percent; ?>
								<?php } else { ?>
									No related downloads
								<?php } ?>
							</td>
							<td class='tickets column-tickets' data-colname="Tickets">
								Tickets: <?php echo $ticket_count; ?><br/>
								Ticket rate: <?php echo $ticket_rate; ?>
							</td>
						</tr>
						<?php
					}
				}
				?>
				</tbody>
				<tfoot>
				<tr>
					<th scope="col" class='manage-column column-label column-primary'>Download</th>
					<th scope="col" class='manage-column column-total_sales'>Total Sales</th>
					<th scope="col" class='manage-column column-most_related'>Most Related Download</th>
					<th scope="col" class='manage-column column-second_most'>2nd Most Related Download</th>
					<th scope="col" class='manage-column column-tickets'>Tickets</th>
				</tr>
				</tfoot>

			</table>

		</div>
	</div>
	<?php
}

/**
 * Gets customers who bought a download
 *
 * @since       0.1
 */
function eddlc_get_customers( $id ) {
	$args      = array(
		'download' => $id,
	);
	$payments  = edd_get_payments( $args );
	$customers = array();

	foreach ( $payments as $payment ) {
		$customer_id = $payment->post_author;
		if ( ! in_array( $customer_id, $customers ) ) {
			$customers[] = $customer_id;
		}
	}

	return $customers;
}

/**
 * Gets all downloads purchased by specific customers
 *
 * @since       0.1
 */
function eddlc_get_all_downloads_for_customers( $customers ) {

	$downloads = array();

	foreach ( $customers as $customer ) {
		$purchased = edd_get_users_purchased_products( $customer );
		if ( $purchased ) {
			foreach ( $purchased as $purchase ) {
				$downloads[] = $purchase->ID;
			}
		}
	}

	return $downloads;
}

function eddlc_get_most_common_value( $ids, $unset ) {
	foreach ( $ids as $key => $val ) {
		if ( in_array( $val, $unset ) ) {
			unset( $ids[ $key ] );
		}
	}
	$results = array_count_values( $ids );
	$results = array_search( max( $results ), $results );

	return $results;
}