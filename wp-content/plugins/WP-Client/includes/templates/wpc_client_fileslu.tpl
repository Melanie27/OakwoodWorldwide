<div class="wpc_client_files">
    {if $show_sort}
        {$sort_by_text}
        {if $sort == 'time'}
            <br />
            {if $dir == 'asc'}
                {$time_added_text} [{$asc_text}] [<a href="{$desc_time_url}">{$desc_text}</a>]
            {else}
                {$time_added_text} [<a href="{$asc_time_url}">{$asc_text}</a>] [{$desc_text}]
            {/if}
        {else}
            <br />
            {$time_added_text} [<a href="{$asc_time_url}">{$asc_text}</a>] [<a href="{$desc_time_url}">{$desc_text}</a>]
        {/if}
        {if $sort == 'name'}
            <br />
            {if $dir == 'asc'}
                {$name_text} [{$asc_text}] [<a href="{$desc_name_url}">{$desc_text}</a>] <br />
            {else}
                {$name_text} [<a href="{$asc_name_url}">{$asc_text}</a>] [{$desc_text}] <br />
            {/if}
        {else}
            <br />
            {$name_text} [<a href="{$asc_name_url}">{$asc_text}</a>] [<a href="{$desc_name_url}">{$desc_text}</a>]
        {/if}
    {/if}
    {if isset($files)}
        {foreach $files as $category}
            {if $category.show && $category.category_name}
                <br />
                <hr />
                <strong>{$category.category_name}</strong><br />
            {/if}
            {if isset($category.file)}
                {foreach $category.file as $file}
                    <br />
                    <a href="{$file.url}">{$file.title}</a>
                    {if $file.description}
                        [{$file.description}]
                    {/if}
                    {if $file.show_date}
                        [{$file.date} {$file.time}]
                    {/if}
                    {if isset($file.last_download.date)}
                        [{$file.last_download.text} {$file.last_download.date} {$file.last_download.time}]
                    {/if}
                    {if $file.show_size}
                        [{$file.size}]
                    {/if}
                    [<a href="{$file.delete_url}">DELETE</a>]
                {/foreach}
            {/if}
        {/foreach}
    {/if}
</div>