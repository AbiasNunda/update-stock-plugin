<?php
/**
 * Plugin Name: 0Stock
 * Description: Sets all products stock to 0 and status to "out of stock".
 * Version: 1.0
 * Author: LONEUS | Abias Nunda
 */

 
 if (!defined('ABSPATH')) {
     exit; // Exit if accessed directly
 }
 
 // Hook to add admin menu
 add_action('admin_menu', 'usp_add_admin_menu');
 
 function usp_add_admin_menu() {
     add_menu_page(
         'Update Stock Plugin',
         '0Stock',
         'manage_options',
         'update-stock-plugin',
         'usp_admin_page',
         'dashicons-update',
         6
     );
 }
 
 function usp_admin_page() {
     if (!current_user_can('manage_options')) {
         return;
     }
 
     ?>
     <div class="wrap">
         <h1>0Stock</h1>
         <form method="post" action="">
             <input type="hidden" name="usp_action" value="update_stock">
             <?php wp_nonce_field('usp_update_stock_action', 'usp_update_stock_nonce'); ?>
             <?php submit_button('Update Stock'); ?>
         </form>
         <?php
         if (isset($_POST['usp_action']) && $_POST['usp_action'] === 'update_stock') {
             if (!check_admin_referer('usp_update_stock_action', 'usp_update_stock_nonce')) {
                 wp_die('Security check failed');
             }
             usp_update_stock();
         }
         ?>
     </div>
     <?php
 }
 
 function usp_update_stock() {
     global $wpdb;
 
     // Set Stock Quantity to 0
     $update_quantity_result = $wpdb->query("
         UPDATE {$wpdb->prefix}postmeta pm
         INNER JOIN {$wpdb->prefix}wc_product_meta_lookup pml
             ON pm.post_id = pml.product_id
         SET pm.meta_value = '0', pml.stock_quantity = 0
         WHERE pm.meta_key = '_stock'
     ");
 
     if ($update_quantity_result === false) {
         echo '<div class="notice notice-error is-dismissible"><p>Failed to update stock quantities.</p></div>';
         return;
     }
 
     // Set Stock Status to "Out of Stock"
     $update_status_result = $wpdb->query("
         UPDATE {$wpdb->prefix}postmeta pm
         INNER JOIN {$wpdb->prefix}wc_product_meta_lookup pml
             ON pm.post_id = pml.product_id
         SET pm.meta_value = 'outofstock', pml.stock_status = 'outofstock'
         WHERE pm.meta_key = '_stock_status'
     ");
 
     if ($update_status_result === false) {
         echo '<div class="notice notice-error is-dismissible"><p>Failed to update stock statuses.</p></div>';
         return;
     }
 
     // Update Term Relationships
     $update_term_relationships_result = $wpdb->query("
         INSERT IGNORE INTO {$wpdb->prefix}term_relationships (object_id, term_taxonomy_id)
         SELECT pml.product_id, tt.term_taxonomy_id
         FROM {$wpdb->prefix}wc_product_meta_lookup pml
         JOIN {$wpdb->prefix}term_taxonomy tt
             ON tt.term_id = (SELECT term_id FROM {$wpdb->prefix}terms WHERE slug = 'outofstock')
         WHERE pml.stock_status = 'outofstock'
     ");
 
     if ($update_term_relationships_result === false) {
         echo '<div class="notice notice-error is-dismissible"><p>Failed to update term relationships.</p></div>';
         return;
     }
 
     echo '<div class="notice notice-success is-dismissible"><p>All products have been updated to out of stock.</p></div>';
 }
 