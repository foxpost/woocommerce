<?php
/*
Plugin Name: WooCommerce Foxpost
Plugin URI: http://www.foxpost.hu
Description: WooCommerce Foxpost plugin
Author: Foxpost
Author URI: http://www.foxpost.hu
Version: 1.6.3

	Copyright: © 2016 FoxPost Zrt. (email: vevoszolgalat@foxpost.hu)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!defined("FOXPOST_TABLE_NAME"))
{
	define("FOXPOST_TABLE_NAME", "foxpost_shipping");
}



//if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function foxpost_shipping_method_init() {
		if ( ! class_exists( 'WC_foxpost_Shipping_Method' ) ) {
			class WC_foxpost_Shipping_Method extends WC_Shipping_Method {

            	public $version = '1.0.0';

				/**
				 * Constructor for foxpost shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {

					$this->id                 = 'foxpost_shipping_method'; // Id for foxpost shipping method. Should be uunique.
					$this->method_title       = __( 'Foxpost - Smartest packages', 'woocommerce-foxpost-shipping' );  // Title shown in admin
					$this->method_description = __( 'Description of foxpost shipping method', 'woocommerce-foxpost-shipping' ); // Description shown in admin

					$this->title              = "Foxpost"; // This can be added as an setting but for this example its forced.

					$this->init();
				}

				/**
				 * Init foxpost settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add foxpost own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
            		$this->load_textdomain();
                    $this->database();

            		$this->fee          = $this->get_option( 'fee' );
            		$this->max_weight   = $this->get_option( 'max_weight' );
            		$this->max_sizea    = $this->get_option( 'max_sizea' );
            		$this->max_sizeb    = $this->get_option( 'max_sizeb' );
            		$this->max_sizec    = $this->get_option( 'max_sizec' );
            		$this->max_sizec    = $this->get_option( 'max_sizec' );
            		$this->max_sizec    = $this->get_option( 'max_sized' );
            		$this->max_sizec    = $this->get_option( 'max_sizee' );

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

				}

            	public function load_textdomain() {

            		// Load textdomain
            		load_plugin_textdomain( 'woocommerce-foxpost-shipping', false, basename( dirname( __FILE__ ) ) . '/languages' );

            	}

                function database() {

                        global $wpdb;
                        $wpdb->show_errors();
                        $table_name = $wpdb->prefix.FOXPOST_TABLE_NAME;
                        $charset_collate = $wpdb->get_charset_collate();

                        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

                          $sql = "CREATE TABLE $table_name (
                              id INT NOT NULL PRIMARY KEY auto_increment,
                              username TEXT NOT NULL,
                              email TEXT NOT NULL,
                              phone TEXT NOT NULL,
                              terminal_id INT NOT NULL,
                              order_id int NOT NULL,
                              products TEXT NOT NULL,
                              record_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                              status INT(1) NOT NULL DEFAULT 0,
                              send_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
                              ) $charset_collate;";

                          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                         $wpdb->query($sql);
                        }
                }

            	/**
            	 * calculate_shipping function.
            	 *
            	 * @access public
            	 * @param mixed $package
            	 * @return void
            	 */
            	public function calculate_shipping( $package=array() )
            	{
					
            		$shipping_total = 0;
            		$fee = ( trim( $this->fee ) == '' ) ? 0 : $this->fee;

            		if ( $this->settings["type"] =='fixed' )
            			
						if($this->get_option( 'free_fee' ) > 0 && WC()->cart->subtotal >= $this->get_option( 'free_fee' )){
							$shipping_total = 0;
						}else{
							$shipping_total 	= $this->fee;
						}
						
						$sum_cart = 0;
            			foreach ( WC()->cart->get_cart() as $item_id => $values ) {
            				$_product = $values['data'];

            				if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
								//echo WC()->cart->total;exit;
            					$sum_cart += $this->fee * $values['quantity'];
                            		}
            			}
					
					
					
					

            		if ( $this->settings["type"] == 'product' )
            		{
            			foreach ( WC()->cart->get_cart() as $item_id => $values ) {
            				$_product = $values['data'];

            				if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
            					$shipping_total += $this->fee * $values['quantity'];
                            		}
            			}
            		}

            		$rate = array(
            			'id'    => $this->id,
            			'label' => $this->title,
            			'cost'  => $shipping_total
            		);

            		// Register the rate
            		$this->add_rate( $rate );
            	}

                function init_form_fields() {

                global $woocommerce;

                    $this->form_fields = array(
                                'enabled' => array(
                                                                'title' 		=> __( 'Enable/Disable', 'woocommerce-foxpost-shipping' ),
                                                                'type' 			=> 'checkbox',
                                                                'label' 		=> __( 'Enable this shipping method', 'woocommerce-foxpost-shipping' ),
                                                                'default' 		=> 'no',
                                                        ),
                                'title' => array(
                                                                'title' 		=> __( 'Method Title', 'woocommerce-foxpost-shipping' ),
                                                                'type' 			=> 'text',
                                                                'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce-foxpost-shipping' ),
                                                                'default'		=> __( 'Foxpost - A legokosabb csomagok', 'woocommerce-foxpost-shipping' ),
                                                        ),
                        		'type' => array(
                        			'title'       => __( 'Fee Type', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'select',
                        			'description' => __( 'How to calculate delivery charges', 'woocommerce-foxpost-shipping' ),
                        			'default'     => 'fixed',
                        			'options'     => array(
                        				'fixed'       => __( 'Fixed amount', 'woocommerce-foxpost-shipping' ),
                        				'product'     => __( 'Fixed amount per product', 'woocommerce-foxpost-shipping' ),
                        				),
                        			'desc_tip'    => true,
                        		                        ),
                        		'fee' => array(
                        			'title'       => __( 'Delivery Fee', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'price',
                        			'description' => __( 'What fee do you want to charge for Foxpost locker delivery, disregarded if you choose free. Leave blank to disable.', 'woocommerce-foxpost-shipping' ),
                        			'default'     => '100',
                        			'desc_tip'    => true,
                        			'placeholder' => wc_format_localized_price( 0 )
                                                		),
                        		'free_fee' => array(
                        			'title'       => __( 'Ingyenes szállítás x Ft felett', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'price',
                        			'description' => __( 'Ingyenes szállítás x Ft felett', 'woocommerce-foxpost-shipping' ),
                        			'default'     => '10000000',
                        			'desc_tip'    => true,
                        			'placeholder' => wc_format_localized_price( 0 )
                                                		),
                        		'max_weight' => array(
                        			'title'       => __( 'Max Weight', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'text',
                        			'description' => __( 'The maximum weight of a parcel is 25kg.', 'woocommerce-foxpost-shipping' ),
                        			'default'     => __( '25', 'woocommerce-foxpost-shipping' ),
                        			'desc_tip'    => true,
                        		                        ),
                        		'max_sizea' => array(
                        			'title'       => __( 'Max dimension - size XS', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'text',
                        			'description' => __( 'The maximum dimension of a Size A parcel is 8x38x64.', 'woocommerce-foxpost-shipping' ),
                        			'default'     => __( '8x38x64', 'woocommerce-foxpost-shipping' ),
                        			'desc_tip'    => true,
                                                		),
                        		'max_sizeb' => array(
                        			'title'       => __( 'Max dimension - size S', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'text',
                        			'description' => __( 'The maximum dimension of a Size B parcel is 19x38x64.', 'woocommerce-foxpost-shipping' ),
                        			'default'     => __( '19x38x64', 'woocommerce-foxpost-shipping' ),
                        			'desc_tip'    => true,
                        		                    ),
                        		'max_sizec' => array(
                        			'title'       => __( 'Max dimension - size M', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'text',
                        			'description' => __( 'The maximum dimension of a Size C parcel is 41x38x64.', 'woocommerce-foxpost-shipping' ),
                        			'default'     => __( '41x38x64', 'woocommerce-foxpost-shipping' ),
                        			'desc_tip'    => true,
                                                		),
                        		'max_sized' => array(
                        			'title'       => __( 'Max dimension - size L', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'text',
                        			'description' => __( 'The maximum dimension of a Size C parcel is 41x38x64.', 'woocommerce-foxpost-shipping' ),
                        			'default'     => __( '51x48x64', 'woocommerce-foxpost-shipping' ),
                        			'desc_tip'    => true,
                                                		),
                        		'max_sizee' => array(
                        			'title'       => __( 'Max dimension - size XL', 'woocommerce-foxpost-shipping' ),
                        			'type'        => 'text',
                        			'description' => __( 'The maximum dimension of a Size C parcel is 64x58x64.', 'woocommerce-foxpost-shipping' ),
                        			'default'     => __( '64x58x64', 'woocommerce-foxpost-shipping' ),
                        			'desc_tip'    => true,
                                                		),


                                );
                }


            	public function admin_options()
            	{
            		global $woocommerce;
                    global $wpdb;
                    $table_name = $wpdb->prefix.FOXPOST_TABLE_NAME;

                    $url = plugins_url()."/woocommerce-foxpost/woocommerce-foxpost-export.php";
                    if ( (isset($_POST["exp_to_csv_pay"]) || isset($_POST["exp_to_csv_nopay"])) && is_array($_POST["post"]) ) {

                     if (isset($_POST["exp_to_csv_pay"])) {
                       $url .= "?pay=1";
                     } else {
                       $url .= "?pay=0";
                     }

                      foreach ($_POST["post"] as $post) {
                        $url .= "&post[]=".$post;
                        $wpdb->query("UPDATE ".$table_name." SET status='1' WHERE order_id='".$post."'");
                      }
                      print '<iframe src="'.$url.'" style="height:1px; width:1px;"></iframe>';
                      unset($_POST);
                    }

                    $_pf = new WC_Product_Factory();

                    $wpdb->show_errors();
                    $fp_show_op = array("ns"=>__( 'Not send', 'woocommerce-foxpost-shipping' ),"a"=>__( 'All', 'woocommerce-foxpost-shipping' ));

                         $args = array(
                            'post_type' => 'shop_order',
                            'post_status' => 'any',
                            'posts_per_page' => '-1'
                          );
                          $my_query = new WP_Query( $args );

                          $customer_orders = $my_query->posts;
                          $row = 1;
                          $tb_content = '';
                          $pays = array();
                          $pays[0] = __( '----- Válassz ----', 'woocommerce-foxpost-shipping' );

                          if (!isset($_POST["fp_show"])) {
                            $_POST["fp_show"] = "a";
                          }


                          foreach ($customer_orders as $customer_order) {
                              $order_id = $customer_order->ID;
                              $order = new WC_Order($order_id);
                              $user_id = $order->user_id;
                              $terminals = getTerminals();
                              $fp_terminals = array();

                              foreach($terminals as $terminal) {
                                $fp_terminals[$terminal["fp_place_id"]] = $terminal["fp_name"];
                              }

                              foreach ($order->get_items( 'shipping' ) as $shipping) {
                               if ($shipping["method_id"]=="foxpost_shipping_method") {

                                $table_name = $wpdb->prefix.FOXPOST_TABLE_NAME;
                                $fp_datas = $wpdb->get_results("SELECT id, status FROM ".$table_name." WHERE order_id='".$order_id."'");

                                 $alternate = "";

                                 if ($row%2 == 0) { $alternate = " alternate "; }
                                  //print $order_id."/".$user_id;
                                  $custom_fields = get_post_custom($customer_order->ID);

                                    if ($custom_fields["_payment_method"][0]!="") {
                                     $pays[$custom_fields["_payment_method"][0]] = $custom_fields["_payment_method_title"][0];
                                    }

                                    //print $custom_fields["_foxpost_terminal"][0]." ".$custom_fields["_billing_last_name"][0]." ".$custom_fields["_billing_first_name"][0]." ".$custom_fields["_billing_first_name"][0]." ".$custom_fields["_billing_email"][0]." ".$custom_fields["_billing_phone"][0]." ".$custom_fields["_payment_method"][0]." ".$custom_fields["_payment_method_title"][0]." ".$custom_fields["_order_total"][0];

                                    $ordered_products = "";
                                    foreach ($order->get_items() as $items) {
                                     $fp_meta = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_weight' AND post_id='".$items["product_id"]."'");

                                      $ordered_products .= $items["qty"]."db ".$items["name"]."<br>";
                                    }
                                    if ( ($_POST["fp_show"]=="ns" && $fp_datas[0]->status==0) || ($_POST["fp_show"]=="a" || !isset($_POST["fp_show"]))) {

                                    if ($_POST["pay_method"]==$custom_fields["_payment_method"][0] || (!isset($_POST["pay_method"]) ||  $_POST["pay_method"]=="0")) {

                                     $tb_content .= '<tr class="hentry'.$alternate.'iedit author-self level-0">';

                                     if ($fp_datas[0]->status==0) {
                                       $tb_content .= '<td><input type="checkbox" value="'.$order_id.'" name="post[]" class="fb_check" id="fp-select-'.$order_id.'"></td>';
                                      } else {
                                        $tb_content .= '<td>&nbsp;</td>';
                                      }

                                      $tb_content .= '
                                      <td><a href="'.get_site_url().'/wp-admin/post.php?post='.$order_id.'&action=edit" target="_blank">'.$custom_fields["_billing_last_name"][0].' '.$custom_fields["_billing_first_name"][0].'</a></td>
                                      <td>'.$custom_fields["_billing_phone"][0].'</td>
                                      <td>'.$custom_fields["_billing_email"][0].'</td>
                                      <td>'.$custom_fields["_payment_method_title"][0].'</td>
                                      <td>'.$ordered_products.'</td>
                                      <td>'.$fp_terminals[$custom_fields["_foxpost_terminal"][0]].'</td>
                                      <td>'.foxpost_status($fp_datas[0]->status).'</td>';

                                     if ($fp_datas[0]->status==1) {
                                       $tb_content .= '<td><a href="'.$_SERVER['REQUEST_URI'].'&react='.$fp_datas[0]->id.'">'.__( 'Reactivation', 'woocommerce-foxpost-shipping' ).'</a></td>';
                                      } else {
                                        $tb_content .= '<td>&nbsp;</td>';
                                      }
                                     }
                                    }
                               }
                            }
                           $row++;
                          }

                    ?>
            		<h3><?php echo $this->method_title; ?></h3>
            		<p><?php _e( 'Foxpost Shipping page.', 'woocommerce-foxpost-shipping' ); ?></p>
            		<table class="form-table">
            			<?php $this->generate_settings_html(); ?>
            		</table>
            		<table class="form-table">
                     <tr>
                      <td>#</td>
                      <td><?php _e( 'show', 'woocommerce-foxpost-shipping' ); ?>:
                        <select name="fp_show" onchange="this.form.submit()">
                         <?php
                         $opt = "a";
                          if (isset($_POST["fp_show"])) { $opt = $_POST["fp_show"]; }
                          foreach ($fp_show_op as $fp_sK=>$fp_sD) {
                            $selected = "";
                            if ($fp_sK==$opt) { $selected = " selected"; }
                            print '<option value="'.$fp_sK.'"'.$selected.'>'.$fp_sD.'</option>';
                          }
                         ?>
                        </select>
                      </td>
                     <td>
                        <select name="pay_method" onchange="this.form.submit()">
                         <?php
                          foreach ($pays as $paysK=>$paysD) {
                            $selected = "";
                            if ($_POST["pay_method"]==$paysK) { $selected = " selected"; }
                            print '<option value="'.$paysK.'"'.$selected.'>'.$paysD.'</option>';
                          }
                         ?>
                        </select>
                        <input type="submit" name="exp_to_csv_pay" value="<?php _e( 'Export cash on delivery', 'woocommerce-foxpost-shipping' ); ?>">
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <input type="submit" name="exp_to_csv_nopay" value="<?php _e( 'Export without cash on delivery', 'woocommerce-foxpost-shipping' ); ?>">
                      </td>
                      <td>
                       <input type="button" name="refresh" value="<?php _e( 'Refresh page', 'woocommerce-foxpost-shipping' ); ?>" OnClick="document.location.reload(true);">
                      </td>
                     </tr>
            		</table>

                        <table class="wp-list-table widefat fixed posts">
                           <thead>
                           <tr>
                            <th id="cb" class="manage-column column-cb check-column" style="" scope="col"><input type="checkbox" class="fb_check" id="cb-select-all-1"></th>
                            <th><?php _e('Name'); ?></th>
                            <th><?php _e('Email', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Phone', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Payment method', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Products', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Target terminal', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Status', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Reactivation', 'woocommerce-foxpost-shipping'); ?></th>
                           </tr>
                           </thead>
                           <tbody id="the-list">
                            <!--<td>Célhelyszín</td>-->
                            <!-- <td>Utánvétösszege</td>-->
                             <?php print $tb_content; ?>
                           </tbody>
                           <tfoot>
                           <tr>
                            <th id="cb" class="manage-column column-cb check-column" style="" scope="col"><input type="checkbox" class="fb_check" id="cb-select-all-2"></th>
                            <th><?php _e('Name'); ?></th>
                            <th><?php _e('Email', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Phone', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Payment method', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Products', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Target terminal', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Status', 'woocommerce-foxpost-shipping'); ?></th>
                            <th><?php _e('Reactivation', 'woocommerce-foxpost-shipping'); ?></th>
                           </tr>
                           </tfoot>
                          </table>

                          <script>
                            var $j = jQuery.noConflict();
                              $j(document).ready(function() {
                                  $j('#cb-select-all-1, #cb-select-all-2').click(function(event) {  //on click
                                      if(this.checked) { // check select status
                                          $j('.fb_check').each(function() { //loop through each checkbox
                                              this.checked = true;  //select all checkboxes with class "checkbox1"
                                          });
                                      }else{
                                          $j('.fb_check').each(function() { //loop through each checkbox
                                              this.checked = false; //deselect all checkboxes with class "checkbox1"
                                          });
                                      }
                                  });

                              });
                          </script>
                          <?php

            	}

			}
		}
	}

    ///
    // Get terminals list
    //
	add_action( 'woocommerce_shipping_init', 'foxpost_shipping_method_init' );
	
	


	function add_foxpost_notice_to_order($order){
		global $wpdb;
		$table_name = $wpdb->prefix.FOXPOST_TABLE_NAME;
		$query = "SELECT terminal_id FROM ".$table_name." WHERE order_id='".$order->id."'";
			$fp_datas = $wpdb->get_results($query);
		$note = '';
		$terminals = file_get_contents('http://cdn.foxpost.hu/foxpost_terminals_extended_v3.json');
		$terminals = json_decode($terminals);
		foreach($terminals as $terminal){
			if(isset($fp_datas[0]->terminal_id) && $fp_datas[0]->terminal_id == $terminal->place_id){
							$note = '<b>Választott terminál</b>: '.$terminal->name;
					echo $note;
					$order->add_order_note( $note );
			}
		}
	}

	add_action( 'woocommerce_order_details_after_order_table', 'add_foxpost_notice_to_order',10,1 );


    function getTerminals () {
      include("settings.php");
      $json_data = json_decode($terminals_url);
      $res = array();
      $i = 0;
       foreach ($json_data as $jd) {
        $res[$i] = array(
                   "fp_place_id" => $jd->place_id,
                   "fp_name" => $jd->name,
                   "fp_address" => $jd->address,
                   "fp_group" => $jd->group,
                   "fp_findme" => $jd->findme,
                   "fp_geolat" => $jd->geolat,
                   "fp_geolng" => $jd->geolng,
                 );

                 //
                 // convert stdclass object to array
                 //

                   $open = array();
                   if (is_object($jd->open)) {
                     foreach ($jd->open as $k=>$v) {
                      $open[] = $k.": ".$v;
                     }
                   }

        $res[$i]["fp_open"] = implode("<br> ", $open);

        $i++;
       }

     return $res;
    }


    ///
    // Custom js & foxpost select
    //
    add_action('wp_footer', 'woocommerce_update_order_review');

    function woocommerce_update_order_review() {
     global $woocommerce;

     $ret = foxpost_check_weight();


      if (is_checkout() && $ret==1) {

        $terminals = getTerminals();

        ?>
         <script>
          var $j = jQuery.noConflict();
            function fp_terminal_info(terminalid) {
                $j("#fp_info").remove();
                var fp_terminal_data = [];
                 <?php
                 foreach ($terminals as $rt) {
                    print "fp_terminal_data[\"".$rt["fp_place_id"]."\"]=\"<span id='fp_info'>".$rt["fp_address"]."<br>".$rt["fp_open"]."<br>".str_replace('"', '&quot;', $rt["fp_findme"])."</span>\";";
                 }
                 ?>
                 if (terminalid>0) {
                    $j('#shipping_method').append(fp_terminal_data[terminalid]);
                 } else {
                   $j("#fp_info").remove();
                 }
            }

          $j( document ).ajaxComplete(function( event,request,settings ) {
           if ($j(".foxpost_terminals").length == 0) {

             if ($j(".shipping_method").val()=="foxpost_shipping_method" || $j('input[type="radio"]:checked').val()=="foxpost_shipping_method") {

                    	html = '<!-- foxpost terminals -->';
                    if($j("#shipping_method").length != 0) {
                    	html += '<li>';
                    } else {
                    	html += '<br>';
                    }
                    	html += '<label for="terminal" class="foxpost_terminals">';
                        html += '<?php  _e( 'Foxpost Terminal:', 'woocommerce-foxpost-shipping' ); ?>';
                        html += '</label><br>';
                        html += '<div align="center">';
                    	html += '<select name="foxpost_terminal" onchange="fp_terminal_info(this.value)">';
                        html += '<option value="">';
                        html += '<?php _e( '----- Válassz ----', 'woocommerce-foxpost-shipping' ); ?>';
                        html += '</option>';
                        <?php
						
						usort($terminals, "cmppp");
                         foreach ($terminals as $rt) {
                          print "html += '<option value=\"".$rt["fp_place_id"]."\">".$rt["fp_group"]." - ".$rt["fp_name"]."</option>';";
                         }
                         ?>
                  	    html += '<input type="hidden" name="address" value="inputeszt">';
                        html += '</select>';
                    if($j("#shipping_method").length != 0) {
                        html += '</li>';
                    }

                    if($j("#shipping_method").length != 0) {
                  	 $j('#shipping_method').append(html);
                    } else {
                  	 $j('.shipping').append(html);
                    }

              }
            }
          });
         </script>
        <?php
      } elseif ((is_checkout() || is_cart()) && $ret==0) {
        //overweight/overize
        ?>
         <script>
          var $j = jQuery.noConflict();
          $j( document ).ajaxComplete(function( event,request,settings ) {
              $j( "#shipping_method_0_foxpost_shipping_method" ).prop( "disabled", true );

              if ($j('input[type="radio"]:checked').val()=="foxpost_shipping_method") {
                $j("#shipping_method_0_foxpost_shipping_method").prop('checked', false);
                $j( "ul#shipping_method li input" ).first().prop('checked', true);
              }
              if ($j(".shipping_method").val()=="foxpost_shipping_method") {
                $j("#shipping_method_0_foxpost_shipping_method").removeAttr("selected");
                $j( "ul#shipping_method li input" ).first().prop('selected', true);
            }

            $j('#shipping_method').append('<span id="fp_info"><?php _e( '<span>The product(s) not to carry with Foxpost', 'woocommerce-foxpost-shipping' ); ?></span>');

          });
         </script>
        <?php

      }
    }


    ///
    // Process the checkout
    //
    add_action('woocommerce_checkout_process', 'foxpost_custom_checkout_field_process');


    function foxpost_custom_checkout_field_process()
    {
    	global $woocommerce;

    	if(pos($_POST['shipping_method']) == 'foxpost_shipping_method')
    	{
    		if (!$_POST['foxpost_terminal']||$_POST['foxpost_terminal']=="")
    		{
    			wc_add_notice( __('<b>Foxpost terminal</b> not selected.'), 'error' );
    		}
    		if (!$_POST['billing_phone'])
    		{
    			wc_add_notice( __('Phone is a required field..'), 'error' );
    		}
    	}
    }

    ///
    // Update the order meta with field value
    //
    add_action('woocommerce_checkout_update_order_meta', 'foxpost_custom_checkout_field_update_order_meta');

    function foxpost_custom_checkout_field_update_order_meta( $order_id )
    {
    	global $wpdb;
    	global $woocommerce;

    	if(pos($_POST['shipping_method']) == 'foxpost_shipping_method')
    	{
    		if ($_POST['foxpost_terminal'])
    		{
    			update_post_meta( $order_id, '_foxpost_terminal',
    				esc_attr($_POST['foxpost_terminal']));
    		}

            $items = $woocommerce->cart->get_cart();
            $fb_products = array();
            foreach($items as $item) {
             $fb_products[] = $item["quantity"]."db ".$item["data"]->post->post_title;
            }


    		$sql_data = array(
                'username'      => $_POST["billing_first_name"]." ".$_POST["billing_last_name"],
                'email'         => $_POST["billing_email"],
                'phone'         => $_POST["billing_phone"],
                'terminal_id'   => $_POST["foxpost_terminal"],
    			'order_id'      => $order_id,
                'products'      => implode(",", $fb_products),
                'record_date'   => date("Y-m-d H:i:s")
    		);
    		$wpdb->insert($wpdb->prefix.FOXPOST_TABLE_NAME, $sql_data);
    	}
    }

    ///
    // foxpost status text
    //
    function foxpost_status($status)
    {
        $res = "";
        if ($status==1) {
            $res = __( 'Sended', 'woocommerce-foxpost-shipping' );
        } else {
            $res = __( 'Not send', 'woocommerce-foxpost-shipping' );
        }
        return $res;
    }

	///
	// foxpost_check_weight function
	//
	// @return true or false.
	//
	function foxpost_check_weight()
	{
		// Defaults used at the end.
		$parcelSize = 'A';
		$is_dimension = 1;

		// Get the shipping method's configuration data.
		$config_data = get_option('woocommerce_foxpost_shipping_method_settings');

		// Read the maximum weight
		$maxWeightFromConfig = (float)strtolower(trim($config_data['max_weight']));

		// Process the various possible product sizes.
		$maxDimensionFromConfigSizeA = explode('x',
			strtolower(trim($config_data['max_sizea'])));
		$maxWidthFromConfigSizeA = (float)trim(@$maxDimensionFromConfigSizeA[0]);
		$maxHeightFromConfigSizeA = (float)trim(@$maxDimensionFromConfigSizeA[1]);
		$maxDepthFromConfigSizeA = (float)trim(@$maxDimensionFromConfigSizeA[2]);

		// flattening to one dimension
		$maxSumDimensionFromConfigSizeA = $maxWidthFromConfigSizeA +
			$maxHeightFromConfigSizeA + $maxDepthFromConfigSizeA;

		$maxDimensionFromConfigSizeB = explode('x', strtolower(trim($config_data['max_sizeb'])));
		$maxWidthFromConfigSizeB = (float)trim(@$maxDimensionFromConfigSizeB[0]);
		$maxHeightFromConfigSizeB = (float)trim(@$maxDimensionFromConfigSizeB[1]);
		$maxDepthFromConfigSizeB = (float)trim(@$maxDimensionFromConfigSizeB[2]);

		// flattening to one dimension
		$maxSumDimensionFromConfigSizeB = $maxWidthFromConfigSizeB +
			$maxHeightFromConfigSizeB + $maxDepthFromConfigSizeB;

		$maxDimensionFromConfigSizeC = explode('x', strtolower(trim($config_data['max_sizec'])));
		$maxWidthFromConfigSizeC = (float)trim(@$maxDimensionFromConfigSizeC[0]);
		$maxHeightFromConfigSizeC = (float)trim(@$maxDimensionFromConfigSizeC[1]);
		$maxDepthFromConfigSizeC = (float)trim(@$maxDimensionFromConfigSizeC[2]);

		// flattening to one dimension
		$maxSumDimensionFromConfigSizeC = $maxWidthFromConfigSizeC +
			$maxHeightFromConfigSizeC + $maxDepthFromConfigSizeC;

		$maxDimensionFromConfigSizeD = explode('x', strtolower(trim($config_data['max_sizec'])));
		$maxWidthFromConfigSizeD = (float)trim(@$maxDimensionFromConfigSizeD[0]);
		$maxHeightFromConfigSizeD = (float)trim(@$maxDimensionFromConfigSizeD[1]);
		$maxDepthFromConfigSizeD = (float)trim(@$maxDimensionFromConfigSizeD[2]);

		// flattening to one dimension
		$maxSumDimensionFromConfigSizeD = $maxWidthFromConfigSizeD +
			$maxHeightFromConfigSizeD + $maxDepthFromConfigSizeD;

		$maxDimensionFromConfigSizeE = explode('x', strtolower(trim($config_data['max_sizec'])));
		$maxWidthFromConfigSizeE = (float)trim(@$maxDimensionFromConfigSizeE[0]);
		$maxHeightFromConfigSizeE = (float)trim(@$maxDimensionFromConfigSizeE[1]);
		$maxDepthFromConfigSizeE = (float)trim(@$maxDimensionFromConfigSizeE[2]);

		// flattening to one dimension
		$maxSumDimensionFromConfigSizeE = $maxWidthFromConfigSizeE +
			$maxHeightFromConfigSizeE + $maxDepthFromConfigSizeE;

		// Check if any of the dimensions are not set up correctly.
		if($maxWidthFromConfigSizeA == 0 ||
			$maxHeightFromConfigSizeA == 0 ||
		       	$maxDepthFromConfigSizeA  == 0 ||
			$maxWidthFromConfigSizeB  == 0 ||
			$maxHeightFromConfigSizeB == 0 ||
			$maxDepthFromConfigSizeB  == 0 ||
			$maxWidthFromConfigSizeC  == 0 ||
			$maxHeightFromConfigSizeC == 0 ||
			$maxDepthFromConfigSizeC  == 0 ||
			$maxWidthFromConfigSizeD  == 0 ||
			$maxHeightFromConfigSizeD == 0 ||
			$maxDepthFromConfigSizeD  == 0 ||
			$maxWidthFromConfigSizeE  == 0 ||
			$maxHeightFromConfigSizeE == 0 ||
			$maxDepthFromConfigSizeE  == 0 )
		{
			// bad format in admin configuration
			$is_dimension = 0;
            print "error: bad format in admin configuration";
		}

		$maxSumDimensionsFromProducts = 0;

		// Go through the products and check their dimensions and
		// weights.
		// size=10 x 20 x 10 cm weight=.32
		foreach ( WC()->cart->get_cart() as $item_id => $values )
		{
			$_product = $values['data'];

			if ( $values['quantity'] > 0 && $_product->needs_shipping() )
			{
				$dimension = explode(' ', $_product->get_dimensions());

				$width  = trim(@$dimension[0]);
				$height = trim(@$dimension[2]);
				$depth  = trim(@$dimension[4]);

				if($width == 0 || $height == 0 || $depth == 0)
				{
					// empty dimension for product
					continue;
				}

				$calc_width  = $width  * $values['quantity'];
				$calc_height = $height * $values['quantity'];
				$calc_depth  = $depth  * $values['quantity'];

				if( $calc_width > $maxWidthFromConfigSizeE ||
					$calc_height > $maxHeightFromConfigSizeE ||
					$calc_depth  > $maxDepthFromConfigSizeE)
				{
					$is_dimension = 0;
				}
				$maxSumDimensionsFromProducts += $width + $height + $depth;
				if($maxSumDimensionsFromProducts > $maxSumDimensionFromConfigSizeE)
				{
					$is_dimension = 0;
				}
				if((float)$_product->get_weight() > $maxWeightFromConfig)
				{
					$is_dimension = 0;
				}
			}
		}

		if($maxSumDimensionsFromProducts <= $maxDimensionFromConfigSizeA)
		{
			$parcelSize = 'A';
		}
		elseif($maxSumDimensionsFromProducts <= $maxDimensionFromConfigSizeB)
		{
			$parcelSize = 'B';
		}
		elseif($maxSumDimensionsFromProducts <= $maxDimensionFromConfigSizeC)
		{
			$parcelSize = 'C';
		}
		elseif($maxSumDimensionsFromProducts <= $maxDimensionFromConfigSizeD)
		{
			$parcelSize = 'D';
		}
		elseif($maxSumDimensionsFromProducts <= $maxDimensionFromConfigSizeE)
		{
			$parcelSize = 'E';
		}

		// Save the parcel size to the session for retreival later
		WC()->session->set('foxpost_parcel_size', $parcelSize);

		//echo json_encode($is_dimension);
		return $is_dimension;
	}

    function do_reactivation() {
        global $wpdb;
        global $woocommerce;
        $table_name = $wpdb->prefix.FOXPOST_TABLE_NAME;
        $wpdb->show_errors();

        if (isset($_GET["react"])) {
            $wpdb->query("UPDATE ".$table_name." SET status='0' WHERE id='".$_GET["react"]."'");
            header("Location:".str_replace("&react=".$_GET["react"], "", $_SERVER['REQUEST_URI']));
        }
    }

    ///
    // Add foxpost shipping to woocommerce
    //
	add_filter( 'woocommerce_shipping_methods', 'add_foxpost_shipping_method' );

	function add_foxpost_shipping_method( $methods ) {
		$methods[] = 'WC_foxpost_Shipping_Method';
		return $methods;
	}
	
	add_action( 'woocommerce_email_after_order_table', 'wdm_add_shipping_method_to_order_email', 10, 2 );
	
	
	///
    // Add foxpost apt to order email
    //
	function wdm_add_shipping_method_to_order_email( $order, $is_admin_email ) {
		global $wpdb;
		$wpdb->show_errors();
		$table_name = $wpdb->prefix.FOXPOST_TABLE_NAME;
		$fp_datas = $wpdb->get_results("SELECT id, terminal_id, status FROM ".$table_name." WHERE order_id='".$order->id."'");
		$apt_id = $fp_datas[0]->terminal_id;

		$apts = getTerminals();
		$apt_str = 'Ismeretlen';
		foreach($apts as $apt){
			if($apt_id == $apt['fp_place_id']){
				$apt_str = $apt['fp_name'];
			}
		}
		
		echo '<p><h4>Választott terminál: <u> ' . $apt_str . '</h4></u></p>';
	}


//}

do_reactivation();

function cmppp($a, $b) {
	setlocale(LC_ALL, 'hu_HU.utf8');
	return strcoll($a['fp_group'], $b['fp_group']);
}

?>