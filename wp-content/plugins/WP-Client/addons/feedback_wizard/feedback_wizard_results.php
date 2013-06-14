<?php
global $wpdb, $wpc_client;

$filter = '';
$where  = '';
$target = '';

if ( isset( $_GET['filter'] ) && 0 < $_GET['filter'] ) {
    $filter = $_GET['filter'];
    $where = ' WHERE client_id=' . $filter;
    $target = '&filter=' . $filter;
}

if ( isset( $_REQUEST['wpc_action'] ) && 'delete_result' == $_REQUEST['wpc_action'] && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpc_feedback_results_form' ) && isset( $_REQUEST['result_id'] ) ) {

    $result_ids = ( is_array( $_REQUEST['result_id'] ) ) ? $_REQUEST['result_id'] : (array) $_REQUEST['result_id'];
    foreach ( $result_ids as $result_id ) {
        //delete result
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}wpc_client_feedback_results WHERE result_id = %d", $result_id ) );
    }

    do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_feedback_wizard&tab=results&msg=d' . $target );
    exit;
}


/*
* Pagination
*/
if ( !class_exists( 'pagination' ) )
    include_once( $wpc_client->plugin_dir . 'forms/pagination.php' );

$items = $wpdb->get_var( $wpdb->prepare( "SELECT count(result_id) FROM {$wpdb->base_prefix}wpc_client_feedback_results " . $where ) );

$p = new pagination;
$p->items( $items );
$p->limit( 25 );
$p->target( 'admin.php?page=wpclients_feedback_wizard&tab=results' . $target );
$p->calculate();
$p->parameterName( 'p' );
$p->adjacents( 2 );

if( !isset( $_GET['p'] ) ) {
    $p->page = 1;
} else {
    $p->page = $_GET['p'];
}

$limit = "LIMIT " . ( $p->page - 1 ) * $p->limit . ", " . $p->limit;

$results        = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_results " . $where . " ORDER BY time DESC " . $limit, ARRAY_A );
$all_clients    = $wpdb->get_col($wpdb->prepare("SELECT client_id FROM {$wpdb->base_prefix}wpc_client_feedback_results GROUP BY client_id" ) );
$wpnonce        = wp_create_nonce( 'wpc_feedback_results_form' );

$msg = '';
if ( isset( $_GET['msg'] ) ) {
  $msg = $_GET['msg'];
}


//Set date format
if ( get_option( 'date_format' ) ) {
    $date_format = get_option( 'date_format' );
} else {
    $date_format = 'm/d/Y';
}
if ( get_option( 'time_format' ) ) {
    $time_format = get_option( 'time_format' );
} else {
    $time_format = 'g:i:s A';
}




?>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>
    <?php
    if ( '' != $msg ) {
    ?>
        <div id="message" class="updated fade">
            <p>
            <?php
                switch( $msg ) {
                    case 'd':
                        echo __( 'Result(s) <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                }
            ?>
            </p>
        </div>
    <?php
    }
    ?>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_feedback_tabs_menu() ?>
        </ul>
        <span class="clear"></span>

        <div class="content23 news">
            <form method="post" name="feedback_results_form" id="feedback_results_form">
                <input type="hidden" value="<?php echo $wpnonce ?>" name="_wpnonce" id="_wpnonce" />
                <input type="hidden" value="" name="wpc_action" id="wpc_action" />

                <div class="tablenav top">

                    <div class="alignright actions">
                        <select name="filter" id="client_filter">
                            <option value="-1" selected="selected"><?php _e( 'Select Client', WPC_CLIENT_TEXT_DOMAIN ) ?>&nbsp;</option>
                            <?php
                            if ( is_array( $all_clients ) && 0 < count( $all_clients ) )
                                foreach( $all_clients as $client_id ) {
                                    $selected = ( isset( $filter ) && $client_id == $filter ) ? 'selected' : '';
                                    echo '<option value="' . $client_id . '" ' . $selected . ' >' . get_userdata( $client_id )->user_login . '</option>';
                                }
                            ?>

                        </select>
                        <input type="button" value="<?php _e( 'Filter', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary" id="client_filter_button" name="" />
                    </div>

                </div>

                <table cellspacing="0" class="wp-list-table widefat media">
                    <thead>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Wizard Name', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Wizard Version', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Client', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Date', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Wizard Name', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Wizard Version', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Client', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Date', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </tfoot>
                    <?php
                    if ( is_array( $results ) && 0 < count( $results ) ):
                        foreach( $results as $result ):
                    ?>
                       <tr valign="top" id="post-11" class="alternate author-other status-inherit">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="result_id[]" value="<?php echo $result['result_id'] ?>">
                            </th>
                            <td class="title column-title">
                                <input type="hidden" id="assign_name_block_<?php echo $result['result_id'] ?>" value="<?php echo $result['wizard_name'] ?>" />
                                <strong>
                                    <a href="admin.php?page=wpclients_feedback_wizard&tab=view_result&result_id= <?php echo '' . $result['result_id'] ?>" title="view '<?php echo $result['wizard_name'] ?>'"><?php echo $result['wizard_name'] ?></a>
                                </strong>
                                <div class="row-actions">
                                    <span class="view"><a href="admin.php?page=wpclients_feedback_wizard&tab=view_result&result_id= <?php echo '' . $result['result_id'] ?>" title="view '<?php echo $result['wizard_name'] ?>'" ><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                    <span class="delete"><a class="submitdelete" onclick="return showNotice.warn();" href="admin.php?page=wpclients_feedback_wizard&tab=results&wpc_action=delete_result&result_id=<?php echo $result['result_id']  ?>&_wpnonce=<?php echo $wpnonce ?><?php echo $target ?>"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></a> </span>
                                </div>
                            </td>
                            <td class="title column-title">
                                <?php echo $result['wizard_version'] ?>
                            </td>
                            <td class="author column-author">
                                <?php echo get_userdata( $result['client_id'] )->get( 'user_login' ) ?>
                            </td>
                            <td class="date column-date">
                                <?php echo $wpc_client->date_timezone( $date_format, $result['time'] ) ?>
                                <br>
                                <?php echo $wpc_client->date_timezone( $time_format, $result['time'] ) ?>
                            </td>
                        </tr>

                    <?php
                        endforeach;
                    endif;
                    ?>
                </table>

                <div class="tablenav bottom">

                    <div class="alignleft actions">
                        <select name="action" id="action">
                            <option selected="selected" value="-1"><?php _e( 'Bulk Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <option value="delete_results"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                        </select>
                        <input type="button" value="<?php _e( 'Apply', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary action" id="doaction" name="" />
                    </div>

                    <div class="alignleft actions"></div>

                    <div class="tablenav-pages one-page">
                        <div class="tablenav">
                            <div class='tablenav-pages'>
                                <?php  echo $p->show(); ?>
                            </div>
                        </div>
                    </div>

                    <br class="clear">
                </div>

            </form>

        </div>
    </div>


</div>

<script type="text/javascript">

    jQuery( document ).ready( function() {

        //delete results from Bulk Actions
        jQuery( '#doaction' ).click( function() {
            if ( 'delete_results' == jQuery( '#action' ).val() ) {
                jQuery( '#wpc_action' ).val( 'delete_result' );
                jQuery( '#feedback_results_form' ).submit();
            }
            return false;
        });

        //filter by client
        jQuery( '#client_filter_button' ).click( function() {
            if ( '-1' != jQuery( '#client_filter' ).val() ) {
                window.location = 'admin.php?page=wpclients_feedback_wizard&tab=results&filter=' + jQuery( '#client_filter' ).val();
            }
            return false;
        });



    });
</script>
