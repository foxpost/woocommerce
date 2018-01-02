<?php

if ( is_array($_GET["post"]) ) {
  $parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
  require_once( $parse_uri[0] . 'wp-load.php' );
  require_once( "woocommerce-foxpost.php" );


        global $wpdb;
        global $woocommerce;
        $wpdb->show_errors();
        $terminals = getTerminals();
        iconv_set_encoding("output_encoding", "UTF-8");
        mb_internal_encoding("UTF-8");
        mb_http_output('UTF-8');
        //export check
        //export selected

            $csv = "Vásárló neve;Telefonszáma;Email címe;Cél helyszín;Utánvét összege;Tömeg;Termékek;\n";

            $args = array(
                'post__in' => $_GET["post"],
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'posts_per_page' => '-1'
                );
                $my_query = new WP_Query( $args );
                $customer_orders = $my_query->posts;

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

                                $table_name = $wpdb->prefix.FOXPOST_TABLE_NAME;
                                $fp_datas = $wpdb->get_results("SELECT id, status FROM ".$table_name." WHERE order_id='".$order_id."'");

                                  //print $order_id."/".$user_id;
                                  $custom_fields = get_post_custom($customer_order->ID);

                                    if ($custom_fields["_payment_method"][0]!="") {
                                     $pays[$custom_fields["_payment_method"][0]] = $custom_fields["_payment_method_title"][0];
                                    }

                                    $ordered_products = "";
                                    foreach ($order->get_items() as $items) {
                                     $fp_meta = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->postmeta." WHERE meta_key='_weight' AND post_id='".$items["product_id"]."'");
                                      $ordered_products .= $items["qty"]."db ".$items["name"]."<br>";
                                    }
                                      if ($_GET["pay"]==1) {
                                        $csv .= $custom_fields["_billing_last_name"][0]." ".$custom_fields["_billing_first_name"][0].";".$custom_fields["_billing_phone"][0].";".$custom_fields["_billing_email"][0].";".$custom_fields["_foxpost_terminal"][0].";".$custom_fields["_order_total"][0].";".$fp_meta[0]->meta_value.";".strip_tags($ordered_products).";\n";
                                      } else {
                                        //$csv .= $custom_fields["_billing_last_name"][0]." ".$custom_fields["_billing_first_name"][0].";".$custom_fields["_billing_phone"][0].";".$custom_fields["_billing_email"][0].";".$custom_fields["_foxpost_terminal"][0].";'';".$fp_meta[0]->meta_value.";".strip_tags($ordered_products).";\n";<br />
                                        $csv .= $custom_fields["_billing_last_name"][0]." ".$custom_fields["_billing_first_name"][0].";".$custom_fields["_billing_phone"][0].";".$custom_fields["_billing_email"][0].";".$custom_fields["_foxpost_terminal"][0].";"." ".";".$fp_meta[0]->meta_value.";".strip_tags($ordered_products).";\n";
                                      }
                            }
                           $row++;
                          }

            if (isset($csv)) {
              $name = "foxpost-".date('YmdHis').".csv";
              $csv = "\xEF\xBB\xBF".$csv; // UTF-8 BOM
              header('Content-Description: File Transfer');
              header('Content-Type: application/octet-stream');
              header('Content-Disposition: attachment; filename='.$name);
              header('Content-Transfer-Encoding: binary');
              header('Expires: 0');
              header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
              header('Pragma: public');

              $tmp = fopen('php://temp', 'r+');
              fwrite($tmp, $csv);
              rewind($tmp);
              fpassthru($tmp);
              fclose($tmp);
              //unset($_POST);
              exit;
            }
  }

?>