<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<?php
    // plugin menu pointer
if ( in_array( LaterPay_Migrator_Controller_Admin_Migration::ADMIN_MENU_POINTER, $laterpay['pointers'] ) ) :
    $pointer_content = '<h3>' . __( 'Subscriber Migration plugin activated', 'laterpay' ) . '</h3>';
    $pointer_content .= '<p>' . __( 'The tab "Migration" has been added to the LaterPay plugin backend. You can configure and manage the subscriber migration process from there.', 'laterpay' ) . '</p>';
?>
<script>
	jQuery(document).ready(function($) {
		if (typeof(jQuery().pointer) !== 'undefined') {
			jQuery('#toplevel_page_laterpay-plugin')
			.pointer({
				content : '<?php echo laterpay_sanitize_output( $pointer_content ); ?>',
				position: {
					edge: 'left',
					align: 'middle'
				},
				close: function() {
					jQuery.post( ajaxurl, {
						pointer: '<?php echo laterpay_sanitize_output( LaterPay_Migrator_Controller_Admin_Migration::ADMIN_MENU_POINTER ); ?>',
						action: 'dismiss-wp-pointer'
					});
				}
			})
			.pointer('open');
		}
	});
</script>
<?php endif; ?>
