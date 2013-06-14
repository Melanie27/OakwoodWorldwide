<?php


// Widget for Client Login/logout
class wpc_client_widget extends WP_Widget {
    //constructor
    function wpc_client_widget() {

        $widget_ops = array( 'description' => __( 'Allow client to login/Logout.', WPC_CLIENT_TEXT_DOMAIN ) );
        parent::WP_Widget( false, __( 'WP Client: Login/Logout', WPC_CLIENT_TEXT_DOMAIN ), $widget_ops );
    }

    /** @see WP_Widget::widget */
    function widget( $args, $instance ) {
        global $email_newsletter, $current_user, $is_IE;

        extract( $args );

        $title                  = apply_filters( 'widget_title', $instance['title'] );
        $text_login             = apply_filters( 'widget_title', $instance['text_login'] );
        $text_pass              = apply_filters( 'widget_title', $instance['text_pass'] );
        $text_login_button      = apply_filters( 'widget_title', $instance['text_login_button'] );
        $text_welcome           = apply_filters( 'widget_title', $instance['text_welcome'] );
        $text_logout            = apply_filters( 'widget_title', $instance['text_logout'] );
        $logout_redirect        = apply_filters( 'widget_title', $instance['logout_redirect'] );

        echo $before_widget;
        if ( $title )
            echo $before_title . $title . $after_title;
        ?>

    <div class="wpclient_login_block">

        <?php if ( isset( $GLOBALS['wpclient_login_msg'] ) && '' != $GLOBALS['wpclient_login_msg'] )
            echo '<div id="wpclient_message">' . $GLOBALS['wpclient_login_msg'] . '</div>'
        ?>

        <form method="post" name="wpclient_login_form" id="wpclient_login_form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
            <?php
            if ( !is_user_logged_in() ) {
            ?>
            <ul style="list-style: none;">
                <li>
                    <label for="wpclient_login"><?php echo $text_login ?></label>
                </li>
                <li>
                    <input type="text" name="wpclient_login" id="wpclient_login" value="<?php echo ( isset( $_POST['wpclient_login'] ) ) ? $_POST['wpclient_login'] : '' ?>" />
                </li>
                <li>
                    <label for="wpclient_pass"><?php echo $text_pass ?></label>
                </li>
                <li>
                    <input type="password" name="wpclient_pass" id="wpclient_pass" value="" />
                </li>
                <li>
                    <input type="submit" name="wpclient_login_button" id="wpclient_login_button" value="<?php echo $text_login_button ?>" />
                </li>
            </ul>
            <?php
            } else {
                $redirect = '';
                if ( isset( $logout_redirect ) ) {
                    $redirect = $logout_redirect;
                }
            ?>
            <ul style="list-style: none;">
                <li>
                    <span><?php echo do_shortcode( $text_welcome ); ?></span>
                </li>
                <li>
                     <a href="<?php echo wp_logout_url( $redirect ) ?>" ><?php echo $text_logout ?></a>
                </li>
            </ul>
            <?php
            }
            ?>
        </form>
    </div><!--//wpc_client-widget  -->


        <?php echo $after_widget; ?>

    <?php

    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance                       = $old_instance;
        $instance['title']              = strip_tags( $new_instance['title'] );
        $instance['text_login']         = strip_tags( $new_instance['text_login'] );
        $instance['text_pass']          = strip_tags( $new_instance['text_pass'] );
        $instance['text_login_button']  = strip_tags( $new_instance['text_login_button'] );
        $instance['text_welcome']       = strip_tags( $new_instance['text_welcome'] );
        $instance['text_logout']        = strip_tags( $new_instance['text_logout'] );
        $instance['logout_redirect']    = strip_tags( $new_instance['logout_redirect'] );
        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {

        if ( isset( $instance['title'] ) )
            $title = esc_attr( $instance['title'] );
        else
            $title = '';

        if ( isset( $instance['text_login'] ) )
            $text_login = esc_attr( $instance['text_login'] );
        else
            $text_login = __( 'Login:', WPC_CLIENT_TEXT_DOMAIN );

        if ( isset( $instance['text_pass'] ) )
            $text_pass = esc_attr( $instance['text_pass'] );
        else
            $text_pass = __( 'Password:', WPC_CLIENT_TEXT_DOMAIN );

        if ( isset( $instance['text_login_button'] ) )
            $text_login_button = esc_attr( $instance['text_login_button'] );
        else
            $text_login_button = __( 'Login', WPC_CLIENT_TEXT_DOMAIN );

        if ( isset( $instance['text_welcome'] ) )
            $text_welcome = esc_attr( $instance['text_welcome'] );
        else
            $text_welcome = 'Welcome [wpc_client_business_name]';

        if ( isset( $instance['text_logout'] ) )
            $text_logout = esc_attr( $instance['text_logout'] );
        else
            $text_logout = __( 'Logout', WPC_CLIENT_TEXT_DOMAIN );

        if ( isset( $instance['logout_redirect'] ) )
            $logout_redirect = esc_attr( $instance['logout_redirect'] );
        else
            $logout_redirect = '';


        ?>
            <p>
                <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:', WPC_CLIENT_TEXT_DOMAIN) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />

            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'text_login' ); ?>"><?php _e( 'Text for Login field:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'text_login' ); ?>" name="<?php echo $this->get_field_name( 'text_login' ); ?>" type="text" value="<?php echo $text_login; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'text_pass' ); ?>"><?php _e( 'Text for Password field:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'text_pass' ); ?>" name="<?php echo $this->get_field_name( 'text_pass' ); ?>" type="text" value="<?php echo $text_pass; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'text_login_button' ); ?>"><?php _e( 'Text for Login Button:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'text_login_button' ); ?>" name="<?php echo $this->get_field_name( 'text_login_button' ); ?>" type="text" value="<?php echo $text_login_button; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'text_welcome' ); ?>"><?php _e( 'Welcome text:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'text_welcome' ); ?>" name="<?php echo $this->get_field_name( 'text_welcome' ); ?>" type="text" value="<?php echo $text_welcome; ?>" />
                <br>
                <span class="description">
                    [wpc_client_business_name] -
                    <br>
                    <?php _e( 'Display Business Name', WPC_CLIENT_TEXT_DOMAIN ) ?>
                    <br>
                    [wpc_client_contact_name] -
                    <br>
                    <?php _e( 'Display Contact Name', WPC_CLIENT_TEXT_DOMAIN ) ?>
                </span>
            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'text_logout' ); ?>"><?php _e( 'Text for Logout link:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'text_logout' ); ?>" name="<?php echo $this->get_field_name( 'text_logout' ); ?>" type="text" value="<?php echo $text_logout; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'logout_redirect' ); ?>"><?php _e( 'Logout redirect link:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'logout_redirect' ); ?>" name="<?php echo $this->get_field_name( 'logout_redirect' ); ?>" type="text" value="<?php echo $logout_redirect; ?>" />
            </p>

        <?php
    }

} // class wpc_client_widget


add_action( 'widgets_init', create_function( '', 'return register_widget("wpc_client_widget");' ) );

?>