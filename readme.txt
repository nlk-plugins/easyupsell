=== Easy Upsell ===
Contributors: chousmith
Tags: e-commerce, shop, cart, ecommerce, upsell
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 1.0

Creates an "Upsell" option for WP E-Commerce Products (wpsc-products), to add upsell products to the Checkout page with one line of PHP.

== Description ==

Easy Upsell does just that, making it easy to upsell Products on the Shopping Cart / Checkout page of your WP E-Commerce site (* currently requires WP E Commerce v3.8). An "Upsell" box on the Edit Product back-end lets you check which Products to include. A single line of PHP, added to your "wpsc-shopping_cart_page.php" template file, will display the UPSELL products beneath any other existing products on your Checkout page.

== Installation ==

1. Install the Easy Upsell / easyupsell Plugin and activate it on the Plugins page
2. Edit the Products you want to include as Upsells, and check the box to Upsell them.
3. In your Theme's "wpsc-shopping_cart_page.php" file, before the line "   <?php while (wpsc_have_cart_items()) : wpsc_the_cart_item(); ?>" (~line 24) that starts the loop to display Products in your Cart, add the following: <?php if(function_exists('easyupsell_products')) easyupsell_products(); ?>
4. Try adding a Product to your Cart (on the front-end of your site), and see the Upsells beneath your cart's Products.

== Frequently Asked Questions ==

= Does this plugin work with pre-3.8 versions of WP E Commerce? =

Because WP E-Commerce only started using WordPress's "Custom Post Types" in v3.8, this plugin currently only supports the latest version. Future releases may add backwords compatibility.

= Why are the Upsell products not appearing on my Checkout page? =

If there are no Upsell products appearing on your Checkout page (/products-page/checkout/), chances are either you did not add the line of PHP code in to your theme's template file, or you have not added any Products as Upsells in the back-end. Please see the Installation steps.

== Screenshots ==

1. Example of a WP E Commerce - Checkout page, with 1 Product already in the cart, and 1 Upsell
2. The "Upsell" metabox added to WP E Commerce Products (wpsc-product's)

== Changelog ==

= 1.0 =
* Initial release of the plugin

== Upgrade Notice ==

= 1.0 =
Initial release of the plugin