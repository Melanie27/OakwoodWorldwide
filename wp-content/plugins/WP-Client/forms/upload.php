<?php
ob_start();
global $post, $wpdb;

$categories = $wpdb->get_results( "SELECT cat_id, cat_name FROM {$wpdb->prefix}wpc_client_file_categories ORDER BY cat_order ", "ARRAY_A" );

$wpc_settings   = get_option( 'wpc_settings' );
$code           = md5( 'wpc_client_' . $client_id . '_files_uploading' );
?>


<?php
if ( isset( $wpc_settings['flash_uplader_client'] ) && '1' == $wpc_settings['flash_uplader_client'] ) {
//Flash uploader
?>
<script type="text/javascript">

    var upload1;

    //file upload
    jQuery(document).ready(function() {

        upload1 = new SWFUpload({
            // Backend Settings
            upload_url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
            post_params: {"action" : "wpc_client_upload_files", "post_id" : "<?php echo $post->ID ?>", "client_id" : "<?php echo $client_id ?>", "code" : "<?php echo $code ?>" },

            // File Upload Settings
            file_size_limit : "<?php echo ( isset( $wpc_settings['file_size_limit'] ) && '' != $wpc_settings['file_size_limit'] ) ? $wpc_settings['file_size_limit'] : '102400' ?>",    // 100MB
            file_types : "*.*",
            file_types_description : "All Files",
            file_upload_limit : "20",
            file_queue_limit : "0",

            // Event Handler Settings (all my handlers are in the Handler.js file)
            file_dialog_start_handler : fileDialogStart,
            file_queued_handler : fileQueued,
            file_queue_error_handler : fileQueueError,
            file_dialog_complete_handler : fileDialogComplete,
            upload_start_handler : uploadStart,
            upload_progress_handler : uploadProgress,
            upload_error_handler : uploadError,
            upload_success_handler : uploadSuccess,
            upload_complete_handler : uploadComplete,
            queue_complete_handler : queueComplete,

            // Button Settings
            button_image_url : "<?php echo $wpc_client->plugin_url ?>images/button_addfile.png",
            button_placeholder_id : "spanButtonPlaceholder1",
            button_width: 61,
            button_height: 22,

            // Flash Settings
            flash_url : "<?php echo $wpc_client->plugin_url ?>js/swfupload/swfupload.swf",


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

        jQuery( '#file_cat_id').change(function(){
            upload1.addPostParam( 'file_cat_id', jQuery( '#file_cat_id').val() );
        });





    });
</script>

<div style="float:left;  padding:10px;" >

    <form enctype="multipart/form-data" method="post">
        <div class="fieldset flash" id="fsUploadProgress1">
            <span class="legend"></span>
        </div>
        <br clear="all" />

        <div class="button_addfile">
            <?php

            $wpc_settings = get_option( 'wpc_settings' );

            if ( !isset( $wpc_settings['deny_file_cats'] ) || 1 == $wpc_settings['deny_file_cats'] ) {

            ?>
                <?php _e( 'Select category', WPC_CLIENT_TEXT_DOMAIN ) ?>:

            <br />
                <select name="file_cat_id" id="file_cat_id">
                    <?php
                    if ( is_array( $categories ) && 0 < count( $categories ) ) {
                        foreach( $categories as $category ) {
                            echo '<option value="' . $category['cat_id'] . '">' . $category['cat_name'] . '</option>';
                        }
                    }
                    ?>
                </select>

            <?php } ?>
            <span id="spanButtonPlaceholder1"></span>
            <input id="btnCancel1" type="button" value="<?php _e( 'Cancel Uploads', WPC_CLIENT_TEXT_DOMAIN ) ?>" onclick="cancelQueue(upload1);" disabled style="margin-left: 2px; height: 22px; font-size: 8pt;" >
        </div>

    </form>

    <?php
    if ( isset( $_GET['msg'] ) )
        echo '<br><p>' . $_GET['msg'] . '<p>';
    ?>

    <?php
    if ( isset( $msg ) )
        echo '<br><p>' . $msg . '<p>';
    ?>

</div>


<?php

} else {
//Regular uploader
?>

<script type="text/javascript">
    function checkform(){
        if(document.getElementById('file').value == ""){
            alert("<?php _e( 'Please select file to upload.', WPC_CLIENT_TEXT_DOMAIN ) ?>")
            return false;
        }
        return true;
    }
</script>

<div style="float:left;  padding:10px;" >

    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="file" id="file" />
        <br />

        <?php

        $wpc_settings = get_option( 'wpc_settings' );

        if ( !isset( $wpc_settings['deny_file_cats'] ) || 1 == $wpc_settings['deny_file_cats'] ) {

        ?>
            Select category:

        <br />
            <select name="file_cat_id">
                <?php
                if ( is_array( $categories ) && 0 < count( $categories ) ) {
                    foreach( $categories as $category ) {
                        echo '<option value="' . $category['cat_id'] . '">' . $category['cat_name'] . '</option>';
                    }
                }
                ?>
            </select>

        <?php } ?>

        <input type="submit" value="<?php _e( 'Upload File', WPC_CLIENT_TEXT_DOMAIN ) ?>" name="b[upload]" onclick="return checkform();" />
    </form>

    <?php
    if ( isset( $_GET['msg'] ) )
        echo '<br><p>' . $_GET['msg'] . '<p>';
    ?>

    <?php
    if ( isset( $msg ) )
        echo '<br><p>' . $msg . '<p>';
    ?>

</div>

<?php }


$out2 = ob_get_contents();

ob_end_clean();
return $out2;
?>
