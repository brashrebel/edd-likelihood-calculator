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
	?>
	<h2>EDD Likelihood Calculator</h2>
	<p>Select a download below to view all the other downloads which have purchases in common with it.</p>
	<?php
	include( dirname( __FILE__ ) . '/likelihood-table.php' );

	$table = new EDD_LC_Table();
	$table->prepare_items();
	?>
	<div class="wrap">
			<?php
			echo eddlc_display_filtered_download( $table->get_filtered_download() );
			$table->display();
			?>
	</div>
	<?php
}

/**
 * Displays selected download above table and message if no download is selected
 * 
 * @param $id
 *
 * @return string
 */
function eddlc_display_filtered_download( $id ) {
	if ( edd_get_download_sales_stats( $id ) ) {
		$sales = edd_get_download_sales_stats( $id );
		$title = get_the_title( $id );
		$message = '<strong>Selected download: </strong>' . $title . '<br/><strong>Sales: </strong>' . $sales;
	} elseif ( $id ) {
		$message = 'No valid download selected.';
	} else {
		$message = 'No download selected.';
	}
	$output = '<div class="postbox"><div class="inside">' . $message . '</div></div>';
	return $output;
}