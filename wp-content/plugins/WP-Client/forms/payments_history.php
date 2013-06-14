<?php
global $wpdb;

$orders = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpc_client_payments", "ARRAY_A" );

?>

<div style="" class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <?php
    if ( isset( $_GET['msg'] ) ) {
        $msg = $_GET['msg'];
        switch( $msg ) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Manager <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_payments_tabs_menu() ?>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <h2><?php _e( 'Payment History', WPC_CLIENT_TEXT_DOMAIN ) ?></h2>
            <p><?php _e( 'From here, you can see all payment operations.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>

            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e( 'Order ID', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Client', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Status', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Payment Method', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Transaction', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Amount', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php _e( 'Order ID', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Client', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Status', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Transaction', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Payment Method', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Amount', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </tfoot>
                <tbody>
            <?php
            if ( is_array( $orders ) ) {
                $i = 0;
                foreach ( $orders as $order ) {
                    $i++;
                    $class = 'over';
//                    if ( $i % 2 == 0 )
//                        $class .= ' alternate';


            ?>
                <tr class="<?php echo $class ?>" >
                    <td><?php echo $order['order_id'] ?></td>
                    <td>
                    <?php
                    if ( 0 < $order['client_id'] ) {
                        if ( false != $user = get_userdata( $order['client_id'] ) ) {
                             echo $user->get( 'user_login' );
                        } else {
                            echo __( 'Client deleted', WPC_CLIENT_TEXT_DOMAIN ) . '(' . $order['client_id'] . ')';
                        }
                    }
                    ?>
                    </td>
                    <td><?php echo $order['order_status'] ?></td>
                    <td><?php echo $order['payment_method'] ?></td>
                    <td><?php echo $order['transaction_id'] ?></td>
                    <td><?php echo $order['amount'] . ' ' . $order['currency']?> </td>
                </tr>
            <?php
                }
            }
            ?>
                </tbody>
            </table>

        </div>
    </div>


    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery(".over").hover(function(){
                jQuery(this).css("background-color","#bcbcbc");
                },function(){
                jQuery(this).css("background-color","transparent");
            });
        });
    </script>



</div>