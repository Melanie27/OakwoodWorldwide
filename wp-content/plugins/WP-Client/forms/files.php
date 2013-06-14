<?php
global $wpdb;

$filter = '';
$where  = '';
$target = '';

$wpc_settings = get_option( 'wpc_settings' );

if ( isset( $_GET['filter']  ) ) {
    $filter = $_GET['filter'];

    if ( '_wpc_admin' == $filter )
        $where = 'WHERE page_id=0';
    elseif ( '_wpc_for_admin' == $filter )
        $where = 'WHERE page_id!=0';
    elseif ( is_numeric( $filter ) && 0 < $filter )
        $where = 'WHERE user_id=' . $filter;

    $target = '&filter=' . $filter;
}
$t_name             = $wpdb->prefix . "wpc_client_files";
$uploads            = wp_upload_dir();
$download_url       = '?wpc_action=download';
//$count_all_files    = $wpdb->get_var( "SELECT count(id) FROM $t_name" );
//$count_admin_files  = $wpdb->get_var( "SELECT count(id) FROM $t_name WHERE page_id=0 " );

$count_all_files    = $wpdb->get_var("SELECT count(id) FROM $t_name");
$count_admin_files  = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM $t_name WHERE page_id=%d",0));

$count_for_admin    = $count_all_files - $count_admin_files;
$wpnonce            = wp_create_nonce( 'wpc_files_form' );
//$all_authors        = $wpdb->get_col( "SELECT user_id FROM $t_name WHERE user_id != 0 GROUP BY user_id" );
//$temp_cats         = $wpdb->get_results( "SELECT cat_id, cat_name FROM {$wpdb->prefix}wpc_client_file_categories ORDER BY cat_order ", "ARRAY_A" );
$all_authors        = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $t_name WHERE user_id != %d GROUP BY user_id",0));
$temp_cats          = $wpdb->get_results("SELECT cat_id, cat_name FROM {$wpdb->prefix}wpc_client_file_categories ORDER BY cat_order", "ARRAY_A" );

//change structure of array for display cat name in row in table and selectbox
foreach( $temp_cats as $category )
    $categories[$category['cat_id']] = $category['cat_name'];


/*
* Pagination
*/
if ( !class_exists( 'pagination' ) )
    include_once( 'pagination.php' );

$items = $wpdb->get_var("SELECT count(id) FROM $t_name ". $where . "");

$p = new pagination;
$p->items( $items );
$p->limit( 25 );
$p->target( 'admin.php?page=wpclients_files' . $target );
$p->calculate();
$p->parameterName( 'p' );
$p->adjacents( 2 );

if( !isset( $_GET['p'] ) ) {
    $p->page = 1;
} else {
    $p->page = $_GET['p'];
}

$limit = "LIMIT " . ( $p->page - 1 ) * $p->limit . ", " . $p->limit;

$files = $wpdb->get_results("SELECT * FROM $t_name ". $where . " ORDER BY time DESC " . $limit, "ARRAY_A" );



//available filetype icons
$ext_icons = array(
    'acc', 'ai', 'aif', 'app', 'atom', 'avi', 'bmp', 'cdr', 'css', 'doc', 'docx', 'eps', 'exe', 'fla','flv', 'gif', 'gzip', 'html',
    'indd', 'jpg', 'js', 'mov', 'mp3', 'mp4', 'otf', 'pdf','php', 'png', 'ppt', 'pptx', 'psd', 'rar', 'raw', 'rss', 'rtf', 'sql',
    'svg', 'swf', 'tar', 'tiff', 'ttf', 'txt', 'wav', 'wmv', 'xls', 'xlsx', 'xml', 'zip',
);

//available filetype for view
$files_for_view = array(
    'bmp', 'css', 'gif', 'html', 'jpg', 'jpeg', 'pdf', 'png', 'txt', 'xml',
);


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


//Display status message
if ( isset( $_GET['updated'] ) ) {
    ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
}

?>
<style>
    .fancybox-title {
        display: none;
    }
</style>
<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

<div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <li id="news" class="active"><a href="admin.php?page=wpclients_files" ><?php _e( 'Files', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <?php echo ( current_user_can( 'administrator' ) ) ? '<li id="tutorials"><a href="admin.php?page=wpclients_files&tab=cat" >' . __( 'File Categories', WPC_CLIENT_TEXT_DOMAIN ) . '</a></li>' : '' ?>
        </ul>
        <span class="clear"></span>

        <div class="content23 news">

            <div class="icon32" id="icon-upload"><br></div>

            <h2><?php _e( 'Files', WPC_CLIENT_TEXT_DOMAIN ) ?><a class="add-new-h2" id="slide_upload_panel" href="javascript:;"><?php _e( 'Add New', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="arrow"></span></a></h2>

            <div id="upload_file_panel">
                <form method="post" name="upload_file" id="upload_file" enctype="multipart/form-data" >
                    <input type="hidden" name="clients" id="clients" value="" />
                    <input type="hidden" name="circles" id="circles" value="" />
                    <table class="">
                        <tr>
                            <td>
                            <?php
                            if ( isset( $wpc_settings['flash_uplader_admin'] ) && '1' == $wpc_settings['flash_uplader_admin'] ) {
                            //Flash uploader
                            ?>
                                <h3><?php _e( 'Upload File(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></h3>
                                <input type="hidden" name="wpc_action" id="wpc_action2" value="" />
                                <table class="">
                                    <tr>
                                        <td>
                                            <label for="file_cat_id"><?php _e( 'Category', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>

                                            <select name="file_cat_id" id="file_cat_id" >
                                                <?php
                                                if ( is_array( $categories ) && 0 < count( $categories ) ) {
                                                    foreach( $categories as $key => $value ) {
                                                ?>
                                                        <option value="<?php echo $key ?>"><?php echo $value ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php if ( current_user_can( 'administrator' ) ): ?>
                                    <tr>
                                        <td>
                                            <label for="file_category_new"><?php _e( 'New Category', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="text"  name="file_category_new" id="file_category_new" value="" />
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <td>
                                            <label><?php _e( 'Client(s)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <span class="edit"><a href="#popup_block2" rel="clients" class="fancybox_link" title="assign clients to file" ><?php _e( 'Assign To Client(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label><?php _e( 'Client Circle(s)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <span class="edit"><a href="#circles_popup_block" rel="circles" class="fancybox_link" title="assign Client Circles to file" ><?php _e( 'Assign To Client Circle(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                        </td>
                                        <td>
                                            <label><input type="checkbox" name="" id="new_file_notify1" value="1" checked /> <?php _e( 'Send notification to the assigned Client(s) and associated Staff', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br><br>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label><?php _e( 'File(s)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <div class="button_addfile">

                                                <span id="spanButtonPlaceholder1"></span>
                                                <input id="btnCancel1" type="button" value="Cancel Uploads" onclick="cancelQueue(upload1);" disabled style="margin-left: 2px; height: 22px; font-size: 8pt;" >
                                            </div>
                                            <br clear="all" />
                                            <div class="fieldset flash" id="fsUploadProgress1">
                                                <span class="legend"></span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <script type="text/javascript">

                                    var upload1;

                                    jQuery( document ).ready( function() {

                                        //file upload
                                        upload1 = new SWFUpload({
                                            // Backend Settings
                                            upload_url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                                            post_params: {"action" : "wpc_client_admin_upload_files"},

                                            // File Upload Settings
                                            file_size_limit : "<?php echo ( isset( $wpc_settings['file_size_limit'] ) && '' != $wpc_settings['file_size_limit'] ) ? $wpc_settings['file_size_limit'] : '' ?>",    // 100MB
                                            file_types : "*.*",
                                            file_types_description : "All Files",
                                            file_upload_limit : "20",
                                            file_queue_limit : "0",

                                            // Event Handler Settings (all my handlers are in the Handler.js file)
                                            file_dialog_start_handler : fileDialogStart,
                                            file_queued_handler : fileQueued,
                                            file_queue_error_handler : fileQueueError,
                                            file_dialog_complete_handler : fileDialogComplete,
                                            upload_start_handler : uploadStart2,
                                            upload_progress_handler : uploadProgress,
                                            upload_error_handler : uploadError,
                                            upload_success_handler : uploadSuccess,
                                            upload_complete_handler : uploadComplete,
                                            queue_complete_handler : queueComplete,

                                            // Button Settings
                                            button_image_url : "<?php echo $this->plugin_url ?>images/button_addfile.png",
                                            button_placeholder_id : "spanButtonPlaceholder1",
                                            button_width: 61,
                                            button_height: 22,

                                            // Flash Settings
                                            flash_url : "<?php echo $this->plugin_url ?>js/swfupload/swfupload.swf",


                                            custom_settings : {
                                                progressTarget : "fsUploadProgress1",
                                                cancelButtonId : "btnCancel1"
                                            },

                                            // Debug Settings
                                            debug: false
                                        });



                                        function queueComplete() {
                                            self.location.href="";
                                            return false;
                                        }


                                        function uploadStart2() {
                                            upload1.addPostParam( 'file_cat_id', jQuery( '#file_cat_id').val() );
                                            upload1.addPostParam( 'file_category_new', jQuery( '#file_category_new').val() );

                                            var client_ids = "";
                                            var group_ids = "";

                                            jQuery( 'input[name="nfile_client_id[]"]' ).each(function () {
                                                if ( this.checked ) {
                                                    client_ids = client_ids + '#' + this.value + ',';
                                                }
                                            });

                                            upload1.addPostParam( 'nfile_client_id', client_ids );

                                            jQuery( 'input[name="nfile_groups_id[]"]' ).each(function () {
                                                if ( this.checked ) {
                                                    group_ids = group_ids + '#' + this.value + ',';
                                                }
                                            });

                                            upload1.addPostParam( 'nfile_groups_id', group_ids );

                                            if ( jQuery( '#new_file_notify1' ).attr( 'checked' ) ) {
                                                upload1.addPostParam( 'new_file_notify', '1' );
                                            }
                                        }

                                    });

                                </script>

                            <?php

                            } else {
                            //Regular uploader
                            ?>

                                <h3><?php _e( 'Upload File', WPC_CLIENT_TEXT_DOMAIN ) ?></h3>
                                <input type="hidden" name="wpc_action" value="upload_file" />
                                <table class="">
                                    <tr>
                                        <td>
                                            <label for="file"><?php _e( 'File', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="file" name="file" id="file" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="file_title"><?php _e( 'File Title', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="text" name="file_title" id="file_title" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="file_description"><?php _e( 'File Description', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <textarea cols="50" rows="2" name="file_description" id="file_description"></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="file_cat_id"><?php _e( 'Category', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>

                                            <select id="file_cat_id" name="file_cat_id">
                                                <?php
                                                if ( is_array( $categories ) && 0 < count( $categories ) ) {
                                                    foreach( $categories as $key => $value ) {
                                                ?>
                                                        <option value="<?php echo $key ?>"><?php echo $value ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php if ( current_user_can( 'administrator' ) ): ?>
                                    <tr>
                                        <td>
                                            <label for="file_category_new"><?php _e( 'New Category', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="text" id="file_category_new" name="file_category_new" />
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <td>
                                            <label><?php _e( 'Client(s)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <span class="edit"><a href="#popup_block2" rel="clients" class="fancybox_link" title="assign clients to file" ><?php _e( 'Assign To Client(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label><?php _e( 'Client Circle(s)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <span class="edit"><a href="#circles_popup_block" rel="circles" class="fancybox_link" title="assign Client Circles to file" ><?php _e( 'Assign To Client Circle(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                        </td>
                                        <td>
                                            <label><input type="checkbox" name="new_file_notify" id="new_file_notify1" value="1" checked /> <?php _e( 'Send notification to the assigned Client(s) and associated Staff', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br><br>
                                        </td>
                                    </tr>

                                </table>
                                <input type="button" class='button-primary' id="upload_1" value="<?php _e( 'Upload File', WPC_CLIENT_TEXT_DOMAIN ) ?>" />

                                <script type="text/javascript">
                                    jQuery( document ).ready( function() {

                                        //Upload file form 1
                                        jQuery( "#upload_1" ).click( function() {
                                            if ( '' == jQuery( '#file' ).val() ) {
                                                alert("<?php _e( 'Please select file to upload.', WPC_CLIENT_TEXT_DOMAIN ) ?>")
                                                return false;
                                            }
                                            jQuery( '#new_file_notify2' ).remove();
                                            jQuery( '#upload_file' ).submit();
                                        });

                                    });

                                </script>

                            <?php } ?>

                            </td>
                            <td>

                                <h3><?php _e( 'Add an external file | From onsite or offsite server location', WPC_CLIENT_TEXT_DOMAIN ) ?></h3>
                                <table class="">
                                    <tr>
                                        <td>
                                            <label id="file_name"><?php _e( 'File Name', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="text" name="file_name" id="file_name" />
                                            <span class="description"><?php _e( 'ex. file.zip', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label id="file_url"><?php _e( 'File URL', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="text" name="file_url" id="file_url" />
                                            <span class="description"><?php _e( 'ex. http://www.site.com/file.zip', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="file_title2"><?php _e( 'File Title', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="text" name="file_title2" id="file_title2" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="file_description2"><?php _e( 'File Description', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <textarea cols="50" rows="2" name="file_description2" id="file_description2"></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="file_cat_id_2"><?php _e( 'Category', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <select id="file_cat_id_2">
                                                <?php
                                                if ( is_array( $categories ) && 0 < count( $categories ) ) {
                                                    foreach( $categories as $key => $value ) {
                                                ?>
                                                        <option value="<?php echo $key ?>"><?php echo $value ?></option>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php if ( current_user_can( 'administrator' ) ): ?>
                                    <tr>
                                        <td>
                                            <label for="file_category_new_2"><?php _e( 'New Category', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <input type="text" id="file_category_new_2" />
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <td>
                                            <label><?php _e( 'Client(s)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <span class="edit"><a href="#popup_block2" rel="clients" class="fancybox_link" title="assign clients to file" ><?php _e( 'Assign To Client(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label><?php _e( 'Client Circle(s)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                        </td>
                                        <td>
                                            <span class="edit"><a href="#circles_popup_block" rel="circles" class="fancybox_link" title="assign Client Circles to file" ><?php _e( 'Assign To Client Circle(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                        </td>
                                        <td>
                                            <label><input type="checkbox" name="new_file_notify" id="new_file_notify2" value="1" checked /> <?php _e( 'Send notification to the assigned Client(s) and associated Staff', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br><br>
                                        </td>
                                    </tr>

                                </table>
                                <input type="button" class='button-primary' id="upload_2" value="<?php _e( 'Add External File', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                            </td>
                        </tr>
                    </table>

                    <?php $this->get_assign_clients_popup() ?>
                    <?php $this->get_assign_circles_popup() ?>

                </form>









            </div>


            <ul class="subsubsub">
                <li class="all"><a class="<?php echo ( '' == $filter ) ? 'current' : '' ?>" href="admin.php?page=wpclients_files"  ><?php _e( 'All', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="count">(<?php echo ( 0 < $count_all_files ) ? $count_all_files : '0' ?>)</span></a> |</li>
                <li class="image"><a class="<?php echo ( '_wpc_admin' == $filter ) ? 'current' : '' ?>" href="admin.php?page=wpclients_files&filter=_wpc_admin"><?php _e( 'Admin files', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="count">(<?php echo ( 0 < $count_admin_files ) ? $count_admin_files : '0' ?>)</span></a> |</li>
                <li class="image"><a class="<?php echo ( '_wpc_for_admin' == $filter ) ? 'current' : '' ?>" href="admin.php?page=wpclients_files&filter=_wpc_for_admin"><?php _e( 'Files for Admin', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="count">(<?php echo ( 0 < $count_for_admin ) ? $count_for_admin : '0' ?>)</span></a></li>
            </ul>

            <form method="get" id="files_form">
                <input type="hidden" value="<?php echo $wpnonce ?>" name="_wpnonce" id="_wpnonce" />
                <input type="hidden" value="" name="wpc_action" id="wpc_action" />

                <p class="search-box">
                    <label for="media-search-input" class="screen-reader-text"><?php _e( 'Search Media', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                    <input type="text" value="" name="s" id="media-search-input" />
                    <input type="submit" value="<?php _e( 'Search Media', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button" id="search-submit" name="" />
                </p>

                <div class="tablenav top">

                    <div class="alignleft actions">
                        <select name="filter" id="author_filter">
                            <option value="-1" selected="selected"><?php _e( 'Select Author', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <?php
                            if ( is_array( $all_authors ) && 0 < count( $all_authors ) )
                                foreach( $all_authors as $author_id ) {
                                    $selected = ( isset( $filter ) && $author_id == $filter ) ? 'selected' : '';
                                    echo '<option value="' . $author_id . '" ' . $selected . ' >' . get_userdata( $author_id )->user_login . '</option>';
                                }
                            ?>

                        </select>
                        <input type="button" value="<?php _e( 'Filter', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary" id="author_filter_button" name="" />
                    </div>

                    <div class="alignleft actions">
                        <select name="action" id="action1">
                            <option selected="selected" value="-1"><?php _e( 'Bulk Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <option value="reassign"><?php _e( 'Reassign Category', WPC_CLIENT_TEXT_DOMAIN ) ?></option>

                            <?php if ( current_user_can( 'manage_options' ) ): ?>
                            <option value="delete"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <?php endif;?>

                        </select>
                        <select name="new_cat_id" id="new_cat_id1" style="display: none;">
                        <?php
                        if ( is_array( $categories ) && 0 < count( $categories ) ) {
                            foreach( $categories as $key => $value ) {
                        ?>
                                <option value="<?php echo $key ?>"><?php echo $value ?></option>
                        <?php
                            }
                        }
                        ?>
                        </select>
                        <input type="button" value="<?php _e( 'Apply', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary action" id="doaction1" name="" />
                    </div>

                    <div class="tablenav-pages one-page">
                        <span class="displaying-num"><?php echo $items ?> item(s)</span>
                    </div>

                    <br class="clear">

                </div>

                <table cellspacing="0" class="wp-list-table widefat media">
                    <thead>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-icon" id="icon" scope="col"></th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'File Title', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-author sortable desc" id="author" scope="col">
                                <span><?php _e( 'Author', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Clients', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column sortable desc" id="comments" scope="col">
                                <span><?php _e( 'Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column sortable desc" id="" scope="col">
                                <span><?php _e( 'Categories', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-date sortable asc" id="date" scope="col">
                                <span><?php _e( 'Date', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-date sortable asc" id="date" scope="col">
                                <span><?php _e( 'Last Download', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-icon" id="icon" scope="col"></th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'File Title', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-author sortable desc" id="author" scope="col">
                                <span><?php _e( 'Author', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Clients', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column sortable desc" id="" scope="col">
                                <span><?php _e( 'Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column sortable desc" id="" scope="col">
                                <span><?php _e( 'Category', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-date sortable asc" id="date" scope="col">
                                <span><?php _e( 'Date', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-date sortable asc" id="date" scope="col">
                                <span><?php _e( 'Last Download', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </tfoot>

                    <tbody id="the-list">
                    <?php
                    if ( is_array( $files ) && 0 < count( $files ) ):
                        foreach( $files as $file ):
                    ?>

                        <tr valign="top" id="post-11" class="alternate author-other status-inherit">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="file_id[]" value="<?php echo $file['id'] ?>">
                            </th>
                            <td class="column-icon media-icon">
                                <?php
                                $file_type = strtolower( end( explode('.', $file['filename'] ) ) );
                                $file_type = ( 4 >= strlen( $file_type ) && in_array( $file_type, $ext_icons ) ) ? $file_type : 'unknown';
                                ?>
                                <img width="40" height="40" src="<?php echo $this->plugin_url . 'images/filetype_icons/' . $file_type . '.png' ?>" class="attachment-80x60" alt="<?php echo $file_typel ?>" title="<?php echo $file_typel ?>">
                            </td>
                            <td class="title column-title">
                                <input type="hidden" id="assign_name_block_<?php echo $file['id'] ?>" value="<?php echo $file['name'] ?>" />
                                <strong>
                                    <?php
                                    if ( $file['size'] ) {
                                        $download_link = $download_url . '&id=' . $file['id'];
                                    } else {
                                        $download_link = $file['filename'];
                                    }
                                    ?>
                                    <span id="file_name_block_<?php echo $file['id'] ?>">
                                        <a href="<?php echo $download_link ?>" title="Description: <?php echo $file['description'] ?>"><?php echo ( isset( $file['title'] ) && '' != $file['title'] ) ? $file['title'] : $file['name'] ?></a>
                                        <br>
                                        <span class="description" style="font-size: 10px;" ><?php echo $file['name'] ?></span>
                                    </span>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit"><a class="various" href="#edit_file" title="" rel="<?php echo $file['id'] ?>" ><?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                    <?php if ( $file['size'] ): ?>
                                        <span class="edit"><a href="<?php echo $download_url . '&id=' . $file['id'] ?>" title="download '<?php echo $file['name'] ?>'" ><?php _e( 'Download', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                    <?php else:?>
                                        <span class="edit"><a href="<?php echo $file['filename'] ?>" title="download '<?php echo $file['name'] ?>'" ><?php _e( 'Download', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                    <?php endif;?>

                                    <?php if ( current_user_can( 'manage_options' ) ): ?>
                                        <span class="delete"><a class="submitdelete" onclick="return showNotice.warn();" href="admin.php?page=wpclients_files&wpc_action=delete_file&file_id=<?php echo $file['id']  ?>&_wpnonce=<?php echo $wpnonce ?>"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></a> </span>
                                    <?php endif;?>

                                    <?php if ( in_array( $file_type, $files_for_view ) ): ?>
                                        <?php if ( $file['size'] ): ?>
                                            <span class="view"> | <a href="<?php echo 'admin.php?wpc_action=download&id='. $file['id'] . '&d=false&t=' . $file_type ?>" target="_blank" title="view" ><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></a> </span>
                                        <?php else:?>
                                            <span class="view"> | <a href="<?php echo $file['filename'] ?>" title="view" ><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></a> </span>
                                        <?php endif;?>
                                    <?php endif;?>

                                </div>
                            </td>
                            <td class="author column-author">
                                <?php echo ( 0 == $file['page_id'] ) ? 'Administrator' : get_userdata( $file['user_id'] )->user_login ?>
                            </td>
                            <td class="parent column-parent">
                                <span class="edit"><a href="#popup_block2" rel="clients<?php echo $file['id'];?>" class="fancybox_link" title="assign clients to '<?php echo $file['name'] ?>'" ><?php _e( 'Assign', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                <?
                                    $id_array = explode( ',', str_replace( '#', '', $file['clients_id'] ) );
                                    unset($id_array[count($id_array)-1]);
                                ?>
                                <input type="hidden" name="<?php echo $file['id'];?>" id="clients<?php echo $file['id'];?>" class="change_clients" value="<?=implode(',',$id_array);?>" />
                            <?php

                            if ( '' == $file['clients_id'] ): ?>
                                <span class="edit" id="counter_clients<?php echo $file['id'];?>">(0)</span>
                            <?php else:
                                    if ( current_user_can( 'wpc_manager' ) && !current_user_can( 'administrator' ) ) {
                                        $args = array(
                                            'role'          => 'wpc_client',
                                            'orderby'       => 'user_login',
                                            'order'         => 'ASC',
                                            'meta_key'      => 'admin_manager',
                                            'meta_value'    => get_current_user_id(),
                                            'fields'        => 'ID',
                                        );
                                        $manager_clients = get_users( $args );
                                    }

                                    $clients_id = explode( ',', str_replace( '#', '', $file['clients_id'] ) );

                                    $i = 0;
                                    $n = ceil( count( $clients_id ) / 4 );

                                    $html = '';
                                    $html .= '<ul class="clients_list">';
                                    $user_count = 0;
                                    foreach ( $clients_id as $client_id ) {
                                        if ( 0 < $client_id ) {

                                            //if manager - skip not manager's clients
                                            if ( isset( $manager_clients ) && !in_array( $client_id, $manager_clients ) )
                                                continue;
                                            if( !empty($client_id) ) {
                                                $user_count++;
                                            }
                                        }
                                    }
                                    ?>
                                    <span class="edit" id="counter_clients<?php echo $file['id'];?>">(<?=$user_count;?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="parent column-parent">
                            <?php if ( current_user_can( 'manage_options' ) ): ?>
                                <span class="edit"><a href="#circles_popup_block" rel="circles<?php echo $file['id'];?>" class="fancybox_link" title="assign Client Circles to '<?php echo $file['name'] ?>'" ><?php _e( 'Assign', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                <?
                                    $id_array = explode( ',', str_replace( '#', '', $file['groups_id'] ) );
                                    unset($id_array[count($id_array)-1]);
                                ?>
                                <input type="hidden" name="<?php echo $file['id'];?>" id="circles<?php echo $file['id'];?>" class="change_circles" value="<?=implode(',',$id_array);?>" />
                            <?php endif;?>

                            <?php  if ( '' == $file['groups_id'] ): ?>
                                <span class="edit" id="counter_circles<?php echo $file['id'];?>">(0)</span>
                            <?php else:
                                    $groups_id = explode( ',', str_replace( '#', '', $file['groups_id'] ) );
                                    $group_count = count( $groups_id ) - 1;
                                ?>
                                    <span class="edit" id="counter_circles<?php echo $file['id'];?>">(<?=$group_count;?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="">
                                <?php echo ( isset( $categories[$file['cat_id']] ) ) ? $categories[$file['cat_id']] : '' ?>
                            </td>
                            <td class="date column-date">
                                <?php echo $this->date_timezone( $date_format, $file['time'] ) ?>
                                <br>
                                <?php echo $this->date_timezone( $time_format, $file['time'] ) ?>
                            </td>
                            <td class="date column-date">
                                <?php if ( isset( $file['last_download'] ) && '' != $file['last_download'] ) { ?>
                                    <?php echo $this->date_timezone( $date_format, $file['last_download'] ) ?>
                                    <br>
                                    <?php echo $this->date_timezone( $time_format, $file['last_download'] ) ?>
                                <?php } ?>
                            </td>
                        </tr>

                    <?php
                        endforeach;
                    endif;
                    ?>
                    </tbody>
                </table>
                <div class="tablenav bottom">

                    <div class="alignleft actions">
                        <select name="action" id="action2">
                            <option selected="selected" value="-1"><?php _e( 'Bulk Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <option value="reassign"><?php _e( 'Reassign Category', WPC_CLIENT_TEXT_DOMAIN ) ?></option>

                            <?php if ( current_user_can( 'manage_options' ) ): ?>
                            <option value="delete"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <?php endif;?>

                        </select>
                        <select name="new_cat_id" id="new_cat_id2" style="display: none;">
                        <?php
                        if ( is_array( $categories ) && 0 < count( $categories ) ) {
                            foreach( $categories as $key => $value ) {
                        ?>
                                <option value="<?php echo $key ?>"><?php echo $value ?></option>
                        <?php
                            }
                        }
                        ?>
                        </select>
                        <input type="button" value="<?php _e( 'Apply', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary action" id="doaction2" name="" />
                    </div>

                    <div class="alignleft actions"></div>

                    <div class="tablenav-pages one-page">
                        <div class="tablenav">
                            <div class='tablenav-pages'>
                                <?php echo $p->show(); ?>
                            </div>
                        </div>
                    </div>

                    <br class="clear">
                </div>

                <div id="ajax-response"></div>

                <br class="clear">

            </form>



            <div id="opaco"></div>
            <div id="opaco2"></div>

            <div id="popup_block">
                <form name="assign_clients" method="post" >
                    <input type="hidden" name="wpc_action" value="save_file_access" />
                    <input type="hidden" name="access_field" id="access_field" value="" />
                    <input type="hidden" name="assign_id" id="assign_id" value="" />

                    <h3 id="assign_name"></h3>

                    <table>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" id="select_all" value="all" />
                                    <?php _e( 'Select All.', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div id="popup_content" >
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="submit" name="save" value="<?php _e( 'Save', WPC_CLIENT_TEXT_DOMAIN ) ?>" id="save_popup" />
                                <input type="button" name="cancel" id="cancel_popup" value="<?php _e( 'Cancel', WPC_CLIENT_TEXT_DOMAIN ) ?>" />

                            </td>
                        </tr>
                    </table>

                </form>
            </div>

        </div>


        <div class="wpc_edit_file_box" id="edit_file" style="display: none;">
            <h2><?php _e( 'Edit File:', WPC_CLIENT_TEXT_DOMAIN ) ?> <span id="edit_file_name"></span></h2>
            <form method="post" name="wpc_regist_email_template" id="wpc_regist_email_template">
                <input type="hidden" name="edit_file_id" id="edit_file_id" value="" />
                <table>
                    <tr>
                        <td>
                            <label>
                                <?php _e( 'File Title:', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                <br />
                                <input type="text" name="edit_file_title" size="70" id="edit_file_title"  value="" />

                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>
                                <?php _e( 'File Description:', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                <br />
                                <textarea name="edit_file_description" cols="67" rows="5" id="edit_file_description" ></textarea>
                            </label>
                        </td>
                    </tr>
                </table>
                <div style="clear: both; text-align: center;">
                    <input type="button" class='button-primary' id="update_file" name="update_file" value="<?php _e( 'Update File Data', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                    <input type="button" class='button' id="close_edit_file" value="<?php _e( 'Close', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                </div>
            </form>
        </div>


<script type="text/javascript">
    var site_url = '<?=site_url();?>';

    jQuery( document ).ready( function() {

        // assign Client Circles to NEW file
        jQuery.fn.AssignGroupNewFile = function ( file_id ) {
            jQuery( '#opaco' ).css( { opacity: 0.5 } );
            jQuery( '#opaco' ).fadeIn( 'slow' );
            jQuery( '#popup_block' ).fadeIn( 'slow' );
        };

        //Cancel Assign block
        jQuery( "#cancel_popup3" ).click( function() {
            jQuery( '#popup_block' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
            jQuery( '#popup_block input[type="checkbox"]' ).attr( 'checked', false );
        });

        //Ok Assign block
        jQuery( "#ok_popup3" ).click( function() {
            jQuery( '#popup_block' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
        });

        //Select/Un-select all Client Circles
        jQuery( "#select_all3" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery( '#popup_block input[type="checkbox"]' ).attr( 'checked', true );
            } else {
                jQuery( '#popup_block input[type="checkbox"]' ).attr( 'checked', false );
            }
        });

        //Show/hide upload form
        jQuery( '#slide_upload_panel' ).click( function() {
            jQuery( '#upload_file_panel' ).slideToggle( 'slow' );
            jQuery( this ).toggleClass( 'active' );
            return false;
        });

        //delete file from Bulk Actions
        jQuery( '#doaction1' ).click( function() {
            if ( 'delete' == jQuery( '#action1' ).val() ) {
                jQuery( '#action2' ).attr( 'name' , '' )
                jQuery( '#new_cat_id2' ).attr( 'name' , '' )
                jQuery( '#wpc_action' ).val( 'delete_file' );
                jQuery( '#files_form' ).submit();
            } else if ( 'reassign' == jQuery( '#action1' ).val() ) {
                jQuery( '#action2' ).attr( 'name' , '' )
                jQuery( '#new_cat_id2' ).attr( 'name' , '' )
                jQuery( '#wpc_action' ).val( 'reassign_files_to_category' );
                jQuery( '#files_form' ).submit();
            }
            return false;
        });


        //delete file from Bulk Actions
        jQuery( '#doaction2' ).click( function() {
            if ( 'delete' == jQuery( '#action2' ).val() ) {
                jQuery( '#action1' ).attr( 'name' , '' )
                jQuery( '#new_cat_id1' ).attr( 'name' , '' )
                jQuery( '#wpc_action' ).val( 'delete_file' );
                jQuery( '#files_form' ).submit();
            } else if ( 'reassign' == jQuery( '#action2' ).val() ) {
                jQuery( '#action1' ).attr( 'name' , '' )
                jQuery( '#new_cat_id1' ).attr( 'name' , '' )
                jQuery( '#wpc_action' ).val( 'reassign_files_to_category' );
                jQuery( '#files_form' ).submit();
            }
            return false;
        });

        //show reassign cats
        jQuery( '#action1' ).change( function() {
            if ( 'reassign' == jQuery( '#action1' ).val() ) {
                jQuery( '#new_cat_id1' ).show();
            } else {
                jQuery( '#new_cat_id1' ).hide();
            }
            return false;
        });

        //show reassign cats
        jQuery( '#action2' ).change( function() {
            if ( 'reassign' == jQuery( '#action2' ).val() ) {
                jQuery( '#new_cat_id2' ).show();
            } else {
                jQuery( '#new_cat_id2' ).hide();
            }
            return false;
        });

        //
        jQuery( '#author_filter_button' ).click( function() {
            if ( '-1' != jQuery( '#author_filter' ).val() ) {
                window.location = 'admin.php?page=wpclients_files&filter=' + jQuery( '#author_filter' ).val();
            }
            return false;
        });


        // AJAX - assign clients to file
        jQuery.fn.getFileClients = function ( file_id ) {
            jQuery( '#popup_content' ).html( '' );
            jQuery( '#access_field' ).val( 'clients_id' );
            jQuery( '#select_all' ).parent().hide();
            jQuery( '#save_popup' ).hide();
            jQuery( '#assign_id' ).val( file_id );
            jQuery( '#assign_name' ).html( '<?php _e( 'Assign Clients to the file', WPC_CLIENT_TEXT_DOMAIN ) ?>: ' + jQuery( '#assign_name_block_' + file_id ).val() );
            jQuery( 'body' ).css( 'cursor', 'wait' );
            jQuery( '#opaco' ).css( { opacity: 0.5 } );
            jQuery( '#opaco' ).fadeIn( 'slow' );
            jQuery( '#popup_block' ).fadeIn( 'slow' );

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                data: 'action=get_file_clients&file_id=' + file_id,
                success: function( html ){
                    jQuery( 'body' ).css( 'cursor', 'default' );
                    if ( 'false' == html ) {
                        jQuery( '#popup_content' ).html( '<p><?php _e( 'No Clients for assign.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>' );
                    } else {
                        jQuery( '#save_popup' ).show();
                        jQuery( '#select_all' ).parent().show();
                        jQuery( '#popup_content' ).html( html );
                    }
                }
             });
        };


        // AJAX - assign Client Circles to file
        jQuery.fn.getFileGroups = function ( file_id ) {
            jQuery( '#popup_content' ).html( '' );
            jQuery( '#access_field' ).val( 'groups_id' );
            jQuery( '#select_all' ).parent().hide();
            jQuery( '#save_popup' ).hide();
            jQuery( '#assign_id' ).val( file_id );
            jQuery( '#assign_name' ).html( '<?php _e( 'Assign Client Circles to the file', WPC_CLIENT_TEXT_DOMAIN ) ?>: ' + jQuery( '#assign_name_block_' + file_id ).val() );
            jQuery( 'body' ).css( 'cursor', 'wait' );
            jQuery( '#opaco' ).fadeIn( 'slow' );
            jQuery( '#popup_block' ).fadeIn( 'slow' );

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                data: 'action=get_file_groups&file_id=' + file_id,
                success: function( html ){
                    jQuery( 'body' ).css( 'cursor', 'default' );
                    if ( 'false' == html ) {
                        jQuery( '#popup_content' ).html( '<p><?php _e( 'No Client Circles for assign.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>' );
                    } else {
                        jQuery( '#save_popup' ).show();
                        jQuery( '#select_all' ).parent().show();
                        jQuery( '#popup_content' ).html( html );
                    }
                }
             });
        };





        //Cancel Assign block
        jQuery( "#cancel_popup" ).click( function() {
            jQuery( '#popup_block' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
        });

        //Select/Un-select all clients
        jQuery( "#select_all" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery( '#popup_content input[type="checkbox"]' ).attr( 'checked', true );
            } else {
                jQuery( '#popup_content input[type="checkbox"]' ).attr( 'checked', false );
            }
        });



        //Display list of clients which have access to file
        jQuery( ".view_clients" ).mousemove( function( kmouse ) {
            jQuery( '#popup_view_block_' + jQuery( this ).attr( 'rel' ) ).css({left: 200, top:kmouse.pageY-70});
            jQuery( '#popup_view_block_' + jQuery( this ).attr( 'rel' ) ).fadeIn( 'fast' );
            jQuery( '#opaco2' ).css({opacity:0});
            jQuery( '#opaco2' ).show();

        });


        //Cancel list of clients which have access to file
        jQuery( "#opaco2" ).mousemove( function() {
            jQuery( '.popup_view_block' ).fadeOut( 'fast' );
            jQuery( '#opaco2' ).fadeOut( 'fast' );
        });

        //Display list of clients which have access to file
        jQuery( ".view_groups" ).mousemove( function( kmouse ) {
            jQuery( '#popup_group_block_' + jQuery( this ).attr( 'rel' ) ).css({left: 200, top:kmouse.pageY-70});
            jQuery( '#popup_group_block_' + jQuery( this ).attr( 'rel' ) ).fadeIn( 'fast' );
            jQuery( '#opaco2' ).css({opacity:0});
            jQuery( '#opaco2' ).show();

        });


        //Cancel list of clients which have access to file
        jQuery( "#opaco2" ).mousemove( function() {
            jQuery( '.popup_view_block' ).fadeOut( 'fast' );
            jQuery( '#opaco2' ).fadeOut( 'fast' );
        });


        //show edit file form
        jQuery( '.various' ).click( function() {
            var id = jQuery(this).attr('rel');

            //show content for edit file
            jQuery( '.various' ).fancybox({
                fitToView   : false,
                autoSize    : true,
                closeClick  : false,
                openEffect  : 'none',
                closeEffect : 'none'
            });


            var file_name = jQuery( '#file_name_block_' + id + ' span' ).html();
            file_name = file_name.replace( /(^\s+)|(\s+$)/g, "" );

            var file_title = jQuery( '#file_name_block_' + id + ' a' ).html();
            file_title = file_title.replace( /(^\s+)|(\s+$)/g, "" );

            var file_description = jQuery( '#file_name_block_' + id + ' a' ).attr( 'title' );
            file_description = file_description.replace( 'Description: ', '' );


            jQuery( '#edit_file_name' ).html( file_name );
            jQuery( '#edit_file_id' ).val( id );
            jQuery( '#edit_file_title' ).val( file_title );
            jQuery( '#edit_file_description' ).html( file_description );

        });

        //close edit file
        jQuery( '#close_edit_file' ).click( function() {
            jQuery( '#edit_file_name' ).html( '' );
            jQuery( '#edit_file_id' ).val( '' );
            jQuery( '#edit_file_title' ).val( '' );
            jQuery( '#edit_file_description' ).html( '' );
            jQuery.fancybox.close();
        });


        // AJAX - update file data
        jQuery( '#update_file' ).click( function() {
            file_id     = jQuery( '#edit_file_id' ).val();
            title       = jQuery( '#edit_file_title' ).val();
            description = jQuery( '#edit_file_description' ).val();

            jQuery( 'body' ).css( 'cursor', 'wait' );

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                data: 'action=wpc_update_file_data&file_id=' + file_id + '&title=' + title +'&description=' + description,
                dataType: "json",
                success: function( data ){
                    jQuery( 'body' ).css( 'cursor', 'default' );
                    if ( data.id ) {
                        jQuery( '#file_name_block_' + data.id + ' a' ).html( data.title );
                        jQuery( '#file_name_block_' + data.id + ' a' ).attr( 'title', 'Description: ' + data.description );
                        jQuery.fancybox.close();
                    }
                }
             });
        });

        //close edit file
        jQuery( '#close_edit_file' ).click( function() {
            jQuery( '#edit_file_name' ).html( '' );
            jQuery( '#edit_file_id' ).val( '' );
            jQuery( '#edit_file_title' ).val( '' );
            jQuery( '#edit_file_description' ).html( '' );
            jQuery.fancybox.close();
        });

    });
</script>
