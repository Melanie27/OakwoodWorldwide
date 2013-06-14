{$javascript}
<form action="" method="post" class="wpc_client_message_form">
    {$textarea}
    <div style="clear: both; height:10px;"></div>
    {$submit_form}
</form>

</br>
<p>
    <span style="font-size: medium;">
        {$messages_title}
    </span>
</p>

<table class="wpc_client_messages">
    {if is_array( $messages ) && 0 < count( $messages ) }
        {foreach $messages as $message}
            <tr>
                <td style="width: 20%;" align="left">
                    <span class="wpc_client_message_author">
                        <strong>
                            {$message.sent_from_name}
                        </strong>
                    </span>:
                </td>
                <td style="width: 10px;" align="right"></td>
                <td>
                    <span class="wpc_client_message_time">
                        {$message.date}
                    </span>
                    >>
                    <span class="wpc_client_message">{$message.comment}</span>
                </td>
            </tr>
        {/foreach}

        {if $count_messages > $message_n}
            <tr>
                <td colspan="3">
                    <input type="button" id="wpc_show_more_mess" value="{$more_messages}" />
                </td>
            </tr>
        {/if}
    {/if}
</table>