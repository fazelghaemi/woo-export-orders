<?php
/*
Plugin Name: خروجی سفارشات
Plugin URI: https://jamilweb.ir
Description: خروجی اطلاعات سفارشات
Version: 1.0.1
Author: Jamil Moradian
Author URI: https://jamilweb.ir
*/

{
    /**
     * Localisation
     **/
    load_plugin_textdomain('woo-export-order', false, dirname(plugin_basename(__FILE__)) . '/');

    /**
     * woo_export class
     **/
    if (!class_exists('woo_export')) {

        class woo_export
        {

            public function __construct()
            {

                $this->order_status = array(
                    'completed'     => __('تکمیل شده', 'woo-export-order'),
                    'cancelled'     => __('لغو شده', 'woo-export-order'),
                    'failed'        => __('ناموفق', 'woo-export-order'),
                    'refunded'      => __('بازپرداخت شده', 'woo-export-order'),
                    'processing'    => __('در حال پردازش', 'woo-export-order'),
                    'pending'       => __('در انتظار', 'woo-export-order'),
                    'on-hold'       => __('در انتظار بررسی', 'woo-export-order'),
                );

                // WordPress Administration Menu
                add_action('admin_menu', array(&$this, 'woo_export_orders_menu'));

                add_action('admin_enqueue_scripts', array(&$this, 'export_enqueue_scripts_css'));
                add_action('admin_enqueue_scripts', array(&$this, 'export_enqueue_scripts_js'));
                add_action('wp_ajax_export_orders_by_date', array($this, 'export_orders_by_date'));
            }

            /**
             * Functions
             */

            function export_enqueue_scripts_css()
            {
                if (isset($_GET['page']) && $_GET['page'] == 'export_orders_page') {
                    wp_enqueue_style('semantic', plugins_url('/css/semantic.min.css', __FILE__), '', '', false);
                    wp_enqueue_style('semanticDataTable', plugins_url('/css/dataTables.semanticui.min.css', __FILE__), '', '', false);
                    wp_enqueue_style('semanticButtons', plugins_url('/css/buttons.semanticui.min.css', __FILE__), '', '', false);
                    wp_enqueue_style('dataTable', plugins_url('/css/data.table.css', __FILE__), '', '', false);
                    wp_enqueue_style('jquery-ui-datepicker', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
                }
            }

            function export_enqueue_scripts_js()
            {
                if (isset($_GET['page']) && $_GET['page'] == 'export_orders_page') {
                    wp_register_script('dataTable', plugins_url('/js/jquery.dataTables.js', __FILE__));
                    wp_enqueue_script('dataTable');

                    wp_register_script('dataTableSemantic', plugins_url('/js/dataTables.semanticui.min.js', __FILE__));
                    wp_enqueue_script('dataTableSemantic');

                    wp_register_script('dataTableButtons', plugins_url('/js/dataTables.buttons.min.js', __FILE__));
                    wp_enqueue_script('dataTableButtons');

                    wp_register_script('buttonsSemantic', plugins_url('/js/buttons.semanticui.min.js', __FILE__));
                    wp_enqueue_script('buttonsSemantic');

                    wp_register_script('woo_pdfmake', plugins_url('/js/pdfmake.min.js', __FILE__));
                    wp_enqueue_script('woo_pdfmake');

                    wp_register_script('jszip', plugins_url('/js/jszip.min.js', __FILE__));
                    wp_enqueue_script('jszip');

                    wp_register_script('vfsfonts', plugins_url('/js/vfs_fonts.js', __FILE__));
                    wp_enqueue_script('vfsfonts');

                    wp_register_script('buttonsHTML5', plugins_url('/js/buttons.html5.min.js', __FILE__));
                    wp_enqueue_script('buttonsHTML5');

                    wp_enqueue_script('jquery-ui-datepicker');

                    // Register custom script for handling date filter
                    wp_register_script('custom-script', plugins_url('/js/custom-script.js', __FILE__), array('jquery'), '1.0', true);
                    wp_enqueue_script('custom-script');

                    // Localize the script with new data
                    $ajax_nonce = wp_create_nonce('export_orders_by_date_nonce');
                    wp_localize_script('custom-script', 'ajax_obj', array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce'    => $ajax_nonce,
                    ));
                }
            }

            function woo_export_orders_menu()
            {
                add_menu_page(
                    'Export Orders',
                    'خروجی سفارشات',
                    'manage_woocommerce',
                    'export_orders_page',
                    array(&$this, 'export_orders_page'),
                    'dashicons-media-spreadsheet',
                    '55.7'
                );
                add_submenu_page('export_orders_page', __('Export Orders Settings', 'woo-export-order'), __('Export Orders Settings', 'woo-export-order'), 'manage_woocommerce', 'export_orders_page', array(&$this, 'export_orders_page'));
            }

            function export_orders_page()
            {
                ?>
                <div class="wrap">
                    <br>
                    <form id="export-orders-form" method="post" action="" style="display: flex; align-items: center;">
                        <div style="margin-right: 20px;">
                            <label for="start_date"><?php _e('از تاریخ', 'woo-export-order'); ?></label>
                            <input type="text" id="start_date" name="start_date" value="" />

                            <label for="start_time"><?php _e('از ساعت', 'woo-export-order'); ?></label>
                            <input type="time" id="start_time" name="start_time" value="" />

                            <label for="end_date"><?php _e('تا تاریخ', 'woo-export-order'); ?></label>
                            <input type="text" id="end_date" name="end_date" value="" />

                            <label for="end_time"><?php _e('تا ساعت', 'woo-export-order'); ?></label>
                            <input type="time" id="end_time" name="end_time" value="" />

                            <button type="button" id="filter_orders"><?php _e('فیلتر', 'woo-export-order'); ?></button>
                        </div>
                        <div style="margin-right: 20px;">
                            <label for="page_length"><?php _e('تعداد ردیف‌های نمایش داده شده در هر صفحه', 'woo-export-order'); ?></label>
                            <select id="page_length" name="page_length">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </form>
                    <br>

                    <table id="order_history" class="ui celled table" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th><?php _e('نام محصولات', 'woo-export-order'); ?></th>
                                <th><?php _e('تعداد محصولات', 'woo-export-order'); ?></th>
                                <th><?php _e('مجموع مبلغ', 'woo-export-order'); ?></th>
                                <th><?php _e('تاریخ سفارش', 'woo-export-order'); ?></th>
                                <th><?php _e('نام خریدار', 'woo-export-order'); ?></th>
                                <th><?php _e('مبلغ تخفیف', 'woo-export-order'); ?></th>
                                <th><?php _e('هزینه ارسال', 'woo-export-order'); ?></th>
                                <th><?php _e('آدرس حمل و نقل', 'woo-export-order'); ?></th>
                                <th><?php _e('شماره تماس', 'woo-export-order'); ?></th>
                                <th><?php _e('روش ارسال', 'woo-export-order'); ?></th> <!-- ستون جدید -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $this->get_order_rows(); ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    jQuery(document).ready(function ($) {
                        $("#start_date, #end_date").datepicker({
                            dateFormat: 'yy-mm-dd'
                        });

                        // Initialize the DataTable
                        var oTable = $("#order_history").DataTable({
                            "bSortClasses": false,
                            "aaSorting": [[0, 'desc']],
                            "bAutoWidth": true,
                            "bInfo": true,
                            "bScrollCollapse": true,
                            "sPaginationType": "full_numbers",
                            "bRetrieve": true,
                            "pageLength": 5, // Default value
                            "oLanguage": {
                                "sSearch": "جستجو:",
                                "sInfo": "نمایش _START_ تا _END_ از _TOTAL_ مورد",
                                "sInfoEmpty": "نمایش 0 تا 0 از 0 مورد",
                                "sZeroRecords": "هیچ موردی یافت نشد",
                                "sInfoFiltered": "(فیلتر شده از مجموع _MAX_ مورد)",
                                "sEmptyTable": "اطلاعاتی موجود نیست",
                                "sLengthMenu": "نمایش _MENU_ مورد",
                                "pageLength": 5, // Default value
                                "oPaginate": {
                                    "sFirst": "ابتدا",
                                    "sPrevious": "قبلی",
                                    "sNext": "بعدی",
                                    "sLast": "انتها"
                                }
                            },
                            dom: 'Bfrtip',
                            buttons: [
                                {
                                    extend: 'excelHtml5',
                                    text: 'دانلود فایل به صورت اکسل',
                                    className: 'ui button',
                                    exportOptions: {
                                        columns: ':visible'
                                    }
                                },
                            ]
                        });

                        // Set the page length based on the dropdown value
                        $('#page_length').on('change', function () {
                            var pageLength = $(this).val();
                            oTable.page.len(pageLength).draw();
                        });

                        // Handle the filter button click event
                        $('#filter_orders').on('click', function () {
                            var startDate = $('#start_date').val();
                            var startTime = $('#start_time').val();
                            var endDate = $('#end_date').val();
                            var endTime = $('#end_time').val();

                            if (startDate && startTime && endDate && endTime) {
                                $.ajax({
                                    url: ajax_obj.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'export_orders_by_date',
                                        start_date: startDate,
                                        start_time: startTime,
                                        end_date: endDate,
                                        end_time: endTime,
                                        security: ajax_obj.nonce
                                    },
                                    success: function (response) {
                                        $('#order_history tbody').html(response);
                                    }
                                });
                            } else {
                                alert('لطفاً تمام فیلدهای تاریخ و زمان را پر کنید.');
                            }
                        });
                    });
                </script>
            <?php
            }

            function get_order_rows()
            {
                $args = array(
                    'limit' => -1,
                );

                $orders = wc_get_orders($args);

                $html = '';

                foreach ($orders as $order) {
                    $order_items = $order->get_items();

                    foreach ($order_items as $items_key => $items_value) {
                        $product_name = $items_value->get_name(); // نام محصول
                        $product_quantity = $items_value->get_quantity(); // تعداد خرید
                        $product_price = $items_value->get_subtotal(); // قیمت محصول
                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); // نام خریدار
                        $order_discount = $order->get_total_discount();
                        $order_shipping = $order->get_shipping_total();
                        $shipping_address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode();
                        $contact_number = $order->get_billing_phone();
                        $shipping_method = $order->get_shipping_method(); // روش ارسال

                        // استخراج ویژگی‌های انتخاب‌شده
                        $product_variation = '';
                        $item_data = $items_value->get_formatted_meta_data('_', true);

                        if (!empty($item_data)) {
                            $product_variation .= ' (';
                            foreach ($item_data as $meta) {
                                $product_variation .= $meta->display_key . ': ' . $meta->display_value . ', ';
                            }
                            $product_variation = rtrim($product_variation, ', ') . ')';
                        }

                        $html .= "<tr>
                            <td>" . $product_name . $product_variation . "</td> <!-- نام محصول به همراه ویژگی‌ها -->
                            <td>" . $product_quantity . "</td>
                            <td>" . wc_price($product_price) . "</td> <!-- قیمت محصول -->
                            <td>" . date_i18n('Y/m/d', strtotime($order->get_date_created())) . "</td>
                            <td>" . $customer_name . "</td>
                            <td>" . wc_price($order_discount) . "</td>
                            <td>" . wc_price($order_shipping) . "</td>
                            <td>" . $shipping_address . "</td>
                            <td>" . $contact_number . "</td>
                            <td>" . $shipping_method . "</td> <!-- داده روش ارسال -->
                        </tr>";
                    }
                }

                return $html;
            }


            function export_orders_by_date()
            {
                check_ajax_referer('export_orders_by_date_nonce', 'security');

                $start_date = sanitize_text_field($_POST['start_date']);
                $start_time = sanitize_text_field($_POST['start_time']);
                $end_date = sanitize_text_field($_POST['end_date']);
                $end_time = sanitize_text_field($_POST['end_time']);

                $start_datetime = new DateTime($start_date . ' ' . $start_time);
                $end_datetime = new DateTime($end_date . ' ' . $end_time);

                $start_datetime_formatted = $start_datetime->format('Y-m-d H:i:s');
                $end_datetime_formatted = $end_datetime->format('Y-m-d H:i:s');

                $args = array(
                    'limit' => -1,
                    'date_query' => array(
                        'after' => $start_datetime_formatted,
                        'before' => $end_datetime_formatted,
                        'inclusive' => true,
                    ),
                );

                $orders = wc_get_orders($args);

                $html = '';

                foreach ($orders as $order) {
                    $order_items = $order->get_items();

                    foreach ($order_items as $items_key => $items_value) {
                        $product_name = $items_value->get_name(); // نام محصول
                        $product_quantity = $items_value->get_quantity(); // تعداد خرید
                        $product_price = $items_value->get_subtotal(); // قیمت محصول
                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); // نام خریدار
                        $order_discount = $order->get_total_discount();
                        $order_shipping = $order->get_shipping_total();
                        $shipping_address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode();
                        $contact_number = $order->get_billing_phone();
                        $shipping_method = $order->get_shipping_method(); // روش ارسال
                    
                        // استخراج ویژگی‌های انتخاب‌شده
                        $product_variation = '';
                        $item_data = $items_value->get_formatted_meta_data('_', true);
                    
                        if (!empty($item_data)) {
                            $product_variation .= ' (';
                            foreach ($item_data as $meta) {
                                $product_variation .= $meta->display_key . ': ' . $meta->display_value . ', ';
                            }
                            $product_variation = rtrim($product_variation, ', ') . ')';
                        }
                    
                        $html .= "<tr>
                            <td>" . $product_name . $product_variation . "</td> <!-- نام محصول به همراه ویژگی‌ها -->
                            <td>" . $product_quantity . "</td>
                            <td>" . wc_price($product_price) . "</td> <!-- قیمت محصول -->
                            <td>" . date_i18n('Y/m/d', strtotime($order->get_date_created())) . "</td>
                            <td>" . $customer_name . "</td>
                            <td>" . wc_price($order_discount) . "</td>
                            <td>" . wc_price($order_shipping) . "</td>
                            <td>" . $shipping_address . "</td>
                            <td>" . $contact_number . "</td>
                            <td>" . $shipping_method . "</td> <!-- داده روش ارسال -->
                        </tr>";
                    }                    
                }

                echo $html;

                wp_die();
            }


        }
    }

    $GLOBALS['woo_export'] = new woo_export();
}
?>
