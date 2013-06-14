<div class="wpc_client_client_pages">

    {if !empty($message)}
    <span id="message" class="updated fade">{$message}</span><br />
    {/if}

    {if !empty($add_staff_url)}
    <strong><a href="{$add_staff_url}">{$add_staff_text}</a></strong><br />
    {/if}

    {if !empty($staff_directory_url)}
    <strong><a href="{$staff_directory_url}">{$staff_directory_text}</a></strong><br /><br /><br />
    {/if}

    {if !empty($pages)}
        {foreach $pages as $page}
            <a href="{$page.url}">{$page.title}</a>
            {if !empty($page.edit_link)}
                [<a href="{$page.edit_link}" >Edit</a>]
            {/if}
            <br/>
        {/foreach}
    {/if}

    <style type='text/css'>
    {literal}
        .navigation .alignleft, .navigation .alignright {display:none;}
    {/literal}
    </style>

</div>