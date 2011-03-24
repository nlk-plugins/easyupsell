<?php
/*
Plugin Name: Easy Upsell
Description: Creates an "Upsell" option for WP E-Commerce Products (wpsc-products), to add upsell products to the Checkout page with one line of PHP.
Version: 1.0
Author: Alex Chousmith
Author URI: http://www.ninthlink.com/author/alex/
*/

if ( !function_exists( 'easyupsell_init' ) ):
function easyupsell_init() {
	// add Upsell taxonomy for WPSC Products
	register_taxonomy('easyupsell',array('wpsc-product'), array(
			'hierarchical' => true,
			'labels' => array(
			'name' => 'Upsell',
			'singular_name' => 'Upsell',
			'edit_item' => 'Edit',
		),
		'public' => true
	));
	
	$upsellcats = get_terms( 'easyupsell', array (
		'hide_empty' => 0,
		'parent'     => 0
	) );
	if ( count($upsellcats) == 0 ) {
		$cat = wp_insert_term( 'Upsell', 'easyupsell', array(
			'slug' => 'upsell'
		) );
	}
}
add_action( 'init', 'easyupsell_init' );
endif;
if ( !function_exists( 'easyupsell_products' ) ):
function easyupsell_products() {
	$upsells = new WP_Query('easyupsell=upsell');
	$upsellcount = count($upsells);
	if ( $upsellcount == 0 ) return;
	
	global $wpsc_cart;
	// get array of IDs of all items already in the cart
	$cartitemIDs = array();
	$realitems = array();
	foreach ( $wpsc_cart->cart_items as $i ) {
		if ( $i->quantity > 0 ) {
			$realitems[] = $i;
			$cartitemIDs[] = $i->product_id;
		}
	}
	$itemcount = count($cartitemIDs);
	$wpsc_cart->cart_items = $realitems;
//	echo '<pre style="display:none">'. print_r($wpsc_cart->cart_items,true) .'</pre>';
	$upsell_cartItems = array();
	foreach( $upsells->posts as $upsell ) {
		if( in_array($upsell->ID, $cartitemIDs) == false ) {
			$wpsc_cart->set_item( $upsell->ID, array('quantity'=>1) );
			//$wpsc_cart->cart_items[$itemcount]->product_name .= ' : '. $wpsc_cart->cart_items[$itemcount]->unit_price;
			$wpsc_cart->cart_items[$itemcount]->total_price = 0;
			
			$custom = get_post_meta($upsell->ID,'_easyupsell_byline');
			$byline = esc_attr($custom[0]);
			
			$upsell_cartItems[] = array(
				'cID' => $itemcount,
				'pID' => $upsell->ID,
				'price' => $wpsc_cart->cart_items[$itemcount]->unit_price ,
				'txt' => "$byline"
			);
			$itemcount++;
		} else {
			$upsellcount--;
		}
	}
	if ( $upsellcount > 0 ):
?>
<script type="text/javascript">
var easyupsellIDs = [<?php
foreach ( $upsell_cartItems as $k => $c ) {
	if ( $k > 0 ) echo ', ';
	echo '{"cID":'. $c['cID'] .',"pID":'. $c['pID'] .',"price":"'. wpsc_currency_display($c['price'], array('display_as_html'=>false)) .'","txt":"'. $c['txt'] .'"}';
}
?>];
jQuery(function($) {
	for(var i=0; i < easyupsellIDs.length; i++) {
		jQuery('input:text[name=quantity]').eq(easyupsellIDs[i]['cID']).val(0)
		.parents('td').prev().append('<br /><span class="upsellinfo"><span class="upselldesc">'+ easyupsellIDs[i]['txt'] + '</span><br />'+ easyupsellIDs[i]['price'] + '</span>');
	}
});
</script>
<?php
	endif;
}
endif;
if ( !function_exists( 'easyupsell_cart_loop_end' ) ):
function easyupsell_cart_loop_end() {
	global $wpsc_cart;
	$current_items = $wpsc_cart->cart_items;
	$upsells = new WP_Query('easyupsell=upsell');
	// make array of IDs of all UPSELL products
	$upsellIDs = array();
	foreach ( $upsells as $up ) {
		$upsellIDs[] = $up->ID;
	}
	$cleancart = array();
	// loop through existing cart items, and remove if it was an UPSELL
	// meaning if TOTAL PRICE = 0 && quantity = 1
	$realitemcount = 0;
	foreach ( $current_items as $c ) {
		$keepincart = true;
		if ( in_array($c->product_id, $upsellIDs) ) {
			if (  ( $c->quantity == 1 ) && ( $c->total_price == 0 ) ) {
				$keepincart = false;
			}
		}
		
		if ( $keepincart == false ) {
			$c->quantity = 0;
		} else {
			$realitemcount++;
		}
		$cleancart[] = $c;
	}
	$wpsc_cart->cart_items = $cleancart;
	$wpsc_cart->cart_item_count = $realitemcount;
//	echo '<pre style="display:none">'. print_r($wpsc_cart,true) .'</pre>';
}
add_action('wpsc_cart_loop_end', 'easyupsell_cart_loop_end');
endif;
if ( !function_exists( 'easyupsell_metabox_cleanup' ) ):
function easyupsell_metabox_cleanup() {
	global $wp_meta_boxes;
	global $post_type;
	
	switch($post_type) {
		case 'wpsc-product':
			remove_meta_box( 'easyupselldiv', 'wpsc-product', 'side' );
			add_meta_box( 'easyupsell_metabox', 'Upsell', 'easyupsell_metabox', 'wpsc-product', 'side' );
			break;
	}
}
add_action( 'do_meta_boxes', 'easyupsell_metabox_cleanup' );
endif;
if ( !function_exists( 'easyupsell_metabox' ) ):
function easyupsell_metabox() {
	global $post;
	$product_terms = wp_get_object_terms( $post->ID, 'easyupsell', array( 'fields' => 'ids' ) );
	echo '<ul>';
	// Get the terms from variations
	$upsellcats = get_terms( 'easyupsell', array (
			'hide_empty' => 0,
			'parent'     => 0
		) );
	$atleastoneupsell = false;
	// Loop through each variation set
	foreach ( (array)$upsellcats as $up ) :
	$set_checked_state = '';
	
	// If this Product includes this variation, check it
	if ( in_array( $up->term_id, $product_terms ) ) {
		$set_checked_state = "checked='checked'";
		$atleastoneupsell = true;
	} ?>
	<li><label><input type="checkbox" <?php echo $set_checked_state; ?> name="tax_input[easyupsell][]" value="<?php echo $up->term_id; ?>" /> <?php echo $up->name; ?></label></li>
<?php
	endforeach;
	echo '</ul>';
	if($atleastoneupsell) {
        $custom = get_post_meta($post->ID,'_easyupsell_byline');
		$byline = $custom[0];
		echo '<p><label for="easyupsell_byline">Byline for Upsell</label><br /><input type="text" value="'. esc_attr($byline) .'" name="easyupsell_byline" size="32" class="text" /></p>';
	}
}
endif;
if ( !function_exists( 'easyupsell_jq' ) ):
function easyupsell_jq() {
	if ( !is_admin() ) {
		wp_enqueue_script( 'jquery' );
	}
}
add_action('wp_print_scripts', 'easyupsell_jq');
endif;
if ( !function_exists( 'easyupsell_metasave' ) ):
function easyupsell_metasave($post_id) {
	if ( get_post_type( $post_id ) == 'wpsc-product' ) {
		if ( isset($_POST["easyupsell_byline"]) ) {
			$byline = wp_kses( $_POST['easyupsell_byline'], array() );
			update_post_meta($post_id, "_easyupsell_byline", $byline);
		}
	}
	return $post_id;
}
add_action('save_post', 'easyupsell_metasave');
endif;
if ( !function_exists( 'easyupsell_menutweak' ) ):
function easyupsell_menutweak() {
	global $submenu;
	foreach( $submenu['edit.php?post_type=wpsc-product'] as $k => $m) {
		if($m[0] == 'Upsell') {
			unset($submenu['edit.php?post_type=wpsc-product'][$k]);
		}
	}
	add_submenu_page( 'edit.php?post_type=wpsc-product', 'Upsell Products', 'Upsell Products', 'administrator', 'edit.php?post_type=wpsc-product&easyupsell=upsell', '' );
//	global $menu;
//	wp_die('<pre>'. print_r($menu,true) .'</pre>');
}
add_action( 'admin_menu', 'easyupsell_menutweak' );
endif;