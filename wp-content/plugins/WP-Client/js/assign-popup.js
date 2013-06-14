    var total_count = 0;
    var checkbox_session = new Array();

    function array_search( needle, haystack, strict ) {
        var strict = !!strict;
        for(var key in haystack){
            if( (strict && haystack[key] === needle) || (!strict && haystack[key] == needle) ){
                return key;
            }
        }
        return false;
    }

    function change_checked_count(e) {
        var num = e.parents(".postbox").find(".popup_statistic .selected_count").html();
        var key = array_search(e.val(),checkbox_session);
        if( key === false ) {
            checkbox_session[checkbox_session.length] = e.val();
        } else {
            checkbox_session.splice(key, 1);
        }
        if(e.is(":checked")) {
            var new_num = num * 1 + 1;
        } else {
            var new_num = num * 1 - 1;
            e.parents(".postbox").find( ".select_all2" ).removeAttr('checked');
            e.parents(".postbox").find( ".select_all_at_page" ).removeAttr('checked');
        }
        var total = e.parents(".postbox").find(".popup_statistic .total_count").html();
        if(new_num > total) {
            new_num = total;
        } else if(new_num < 0) {
            new_num = 0;
        }
        e.parents(".postbox").find(".popup_statistic .selected_count").html(new_num);

        var input_ref = e.parents(".postbox").find( ".input_ref" );
        var temp = input_ref.val();
        jQuery("#counter_" + temp).html("("+new_num+")");
    }

    jQuery( document ).ready( function() {
        var search_text = '';
        var order = '';
        var display = '';
        var ok_action = 0;
        jQuery(".pagination_links[rel=first]").hide();
        jQuery(".pagination_links[rel=prev]").hide();
        //Upload file form 2
        jQuery( "#upload_2" ).click( function() {
            jQuery( '#wpc_action2' ).val( 'upload_file' );
            jQuery( '#file_cat_id' ).val( jQuery( '#file_cat_id_2' ).val() );
            jQuery( '#file_category_new' ).val( jQuery( '#file_category_new_2' ).val() );
            jQuery( '#upload_file' ).submit();
        });


        // assign Clients to NEW file
        jQuery(".fancybox_link").click(function() {
            checkbox_session = new Array();
            var href = jQuery(this).attr('href');
            var rel = jQuery(this).attr('rel');
            jQuery(href + " .input_ref").val(rel);
            jQuery(".pagination_links[rel=first]").hide();
            jQuery(".pagination_links[rel=prev]").hide();
            jQuery(".page_num").html('1');
            jQuery(href).find(".inside table tr td input[type=checkbox]").removeAttr('checked');
            jQuery( ".select_all_at_page" ).removeAttr('checked');
            jQuery('.show option').removeAttr('selected');
            jQuery('.order option').removeAttr('selected');

            jQuery.ajax({
                type: "POST",
                url: site_url+"/wp-admin/admin-ajax.php",
                data: "action=get_popup_pagination_data&datatype=" + rel + "&page=1&goto=first&search=",
                dataType: "json",
                success: function(data){
                    if(data.html) {
                        jQuery(href).find(".inside table tr td").html(data.html);
                        for(key in data.buttons) {
                            if(data.buttons[key]) {
                                jQuery(href).find(".pagination_links[rel="+key+"]").show();
                            } else {
                                jQuery(href).find(".pagination_links[rel="+key+"]").hide();
                            }
                        }
                        if(data.count > 1) {
                            jQuery(href).find(".page_num").html(data.page);
                        } else {
                            jQuery(href).find(".page_num").html('');
                        }
                        jQuery(href).find(".select_all_at_page").removeAttr('checked');
                        var cur_val = jQuery("#"+rel).val();
                        if(cur_val) {
                            if(cur_val == 'all') {
                                jQuery(href).find(".inside table tr td input[type=checkbox]").attr('checked', 'checked');
                                jQuery(href).find(".popup_statistic .total_count").html(data.count);
                                jQuery(href).find(".popup_statistic .selected_count").html(data.count);
                                jQuery( ".select_all2" ).attr('checked', 'checked');
                            } else {
                                var cur_array = cur_val.split(',');
                                jQuery(href).find(".popup_statistic .total_count").html(data.count);
                                jQuery(href).find(".popup_statistic .selected_count").html(cur_array.length);
                                for(key in cur_array) {
                                    jQuery(href).find(".inside table tr td input[type=checkbox][value="+cur_array[key]+"]").attr('checked', 'checked');
                                }
                                jQuery( ".select_all2" ).removeAttr('checked');
                            }
                        } else {
                            jQuery(href).find(".popup_statistic .total_count").html(data.count);
                            jQuery(href).find(".popup_statistic .selected_count").html('0');
                        }

                    }
                    jQuery('.fancybox-inner').width('auto');
                    jQuery('.fancybox-wrap').width('auto');

                },
                error: function(data) {
                    jQuery(href).find(".inside table tr td").html(data.html);
                }
            });

            jQuery.fancybox({
                'type'           : 'inline',
                'beforeClose'       : (function() {
                    if(!ok_action) {
                        var block = jQuery(this)[0].href;
                        var input_ref = jQuery(block).find(".input_ref").val();
                        if( checkbox_session.length ) {
                            var data = jQuery("#"+input_ref).val();
                            var data_array = data.split(',');
                            var key = '';
                            for(temp_key in checkbox_session) {
                                key = array_search(checkbox_session[temp_key], data_array)
                                if( key !== false ) {
                                    data_array.splice (key, 1);;
                                }
                            }
                            jQuery("#counter_"+input_ref).html("(" + data_array.length + ")");
                            jQuery("#"+input_ref).val(data_array.join(','));
                            jQuery("#"+input_ref).trigger('change');
                        }

                        jQuery(block).find(".input_ref").val('');
                    } else {
                        ok_action = 0;
                    }
                }),
                'width'          : 'auto',
                'height'         : 'auto',
                'titleShow'      : false,
                'titleFormat'    : '',
                'autoDimensions' : false,
                'transitionIn'   : 'none',
                'transitionOut'  : 'none',
                'href'           : href
            });
        });

        jQuery(".change_clients").change(function() {
            var name = jQuery(this).attr('name');
            var value = jQuery(this).val();
            var datatype = 'clients';

            jQuery.ajax({
                type: "POST",
                url: site_url+"/wp-admin/admin-ajax.php",
                data: "action=update_assigned_data&datatype=" + datatype + "&id=" + name + "&data=" + value,
                dataType: "json",
                success: function(data){
                    if(data.status) {
                        console.log('Client assign updated.');
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function(data) {
                    alert('Can not update assign data.');
                }
            });
        });

        jQuery(".change_circles").change(function() {
            var name = jQuery(this).attr('name');
            var value = jQuery(this).val();
            var datatype = 'circles';

            jQuery.ajax({
                type: "POST",
                url: site_url+"/wp-admin/admin-ajax.php",
                data: "action=update_assigned_data&datatype=" + datatype + "&id=" + name + "&data=" + value,
                dataType: "json",
                success: function(data){
                    if(data.status) {
                        console.log('Client assign updated.');
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function(data) {
                    alert('Can not update assign data.');
                }
            });
        });

        jQuery(".show").change(function() {
            display = jQuery(this).val();
            order = jQuery(this).parent().find('.order').val();

            var input_ref = jQuery(this).parent().find(".input_ref").val();
            var obj_str = jQuery("#"+input_ref).val();
            if(obj_str) {
                if(obj_str != 'all') {
                    var temp_array = obj_str.split(',');
                    var obj_array = new Array();
                    for(key in temp_array) {
                        obj_array[temp_array[key]] = 1;
                    }
                }
            } else {
                var obj_array = new Array();
            }
            if(obj_str != 'all') {
                jQuery(this).parent().parent().find('.inside table tr td input[type="checkbox"]').each(function() {
                    if(jQuery(this).attr('checked')) {
                        obj_array[jQuery(this).val()] = 1;
                    } else {
                        delete obj_array[jQuery(this).val()];
                    }
                });

                var res_array = new Array();
                for(key in obj_array) {
                    res_array[res_array.length] = key;
                }
                jQuery("#"+input_ref).val(res_array.join(','));
            } else {
                jQuery("#"+input_ref).val('all');
            }
            jQuery("#"+input_ref).trigger('change');

            var input_ref = jQuery(this).parent().find(".input_ref").val();
            var goto = 'first';
            var page = 1;
            datatype = input_ref;

            var link = jQuery(this);
            jQuery.ajax({
                type: "POST",
                url: site_url+"/wp-admin/admin-ajax.php",
                data: "action=get_popup_pagination_data&datatype=" + datatype + "&page=" + page + "&goto=" + goto + "&display=" + display + "&order=" + order + "&search=" + search_text ,
                dataType: "json",
                success: function(data){
                    if(data.html) {
                        link.parent().parent().find(".inside table tr td").html(data.html);
                        for(key in data.buttons) {
                            if(data.buttons[key]) {
                                link.parent().find(".pagination_links[rel="+key+"]").show();
                            } else {
                                link.parent().find(".pagination_links[rel="+key+"]").hide();
                            }
                        }
                        if(data.count > 1) {
                            link.parent().find(".page_num").html(data.page);
                        } else {
                            link.parent().find(".page_num").html('');
                        }
                        link.parent().parent().find(".select_all_at_page").removeAttr('checked');
                        var cur_val = jQuery("#"+input_ref).val();
                        if(cur_val) {
                            if(cur_val == 'all') {
                                link.parent().parent().find(".inside table tr td input[type=checkbox]").attr('checked', 'checked');
                                link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                                link.parent().parent().find(".popup_statistic .selected_count").html(data.count);
                            } else {
                                var cur_array = cur_val.split(',');
                                link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                                link.parent().parent().find(".popup_statistic .selected_count").html(cur_array.length);
                                for(key in cur_array) {
                                    link.parent().parent().find(".inside table tr td input[type=checkbox][value="+cur_array[key]+"]").attr('checked', 'checked');
                                }
                            }
                        }  else {
                            link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                            link.parent().parent().find(".popup_statistic .selected_count").html('0');
                        }
                    }
                    jQuery('.fancybox-inner').width('auto');
                    jQuery('.fancybox-wrap').width('auto');

                },
                error: function(data) {
                    link.parent().parent().find(".inside table tr td").html(data.html);
                }
            });

        });

        jQuery(".order").change(function() {
            display = jQuery(this).parent().find('.show').val();
            order = jQuery(this).val();

            var input_ref = jQuery(this).parent().find(".input_ref").val();
            var obj_str = jQuery("#"+input_ref).val();
            if(obj_str) {
                if(obj_str != 'all') {
                    var temp_array = obj_str.split(',');
                    var obj_array = new Array();
                    for(key in temp_array) {
                        obj_array[temp_array[key]] = 1;
                    }
                }
            } else {
                var obj_array = new Array();
            }
            if(obj_str != 'all') {
                jQuery(this).parent().parent().find('.inside table tr td input[type="checkbox"]').each(function() {
                    if(jQuery(this).attr('checked')) {
                        obj_array[jQuery(this).val()] = 1;
                    } else {
                        delete obj_array[jQuery(this).val()];
                    }
                });

                var res_array = new Array();
                for(key in obj_array) {
                    res_array[res_array.length] = key;
                }
                jQuery("#"+input_ref).val(res_array.join(','));
            } else {
                jQuery("#"+input_ref).val('all');
            }
            jQuery("#"+input_ref).trigger('change');

            var input_ref = jQuery(this).parent().find(".input_ref").val();
            var goto = 'first';
            var page = 1;
            datatype = input_ref;

            var link = jQuery(this);
            jQuery.ajax({
                type: "POST",
                url: site_url+"/wp-admin/admin-ajax.php",
                data: "action=get_popup_pagination_data&datatype=" + datatype + "&page=" + page + "&goto=" + goto + "&display=" + display + "&order=" + order + "&search=" + search_text ,
                dataType: "json",
                success: function(data){
                    if(data.html) {
                        link.parent().parent().find(".inside table tr td").html(data.html);
                        for(key in data.buttons) {
                            if(data.buttons[key]) {
                                link.parent().find(".pagination_links[rel="+key+"]").show();
                            } else {
                                link.parent().find(".pagination_links[rel="+key+"]").hide();
                            }
                        }
                        if(data.count > 1) {
                            link.parent().find(".page_num").html(data.page);
                        } else {
                            link.parent().find(".page_num").html('');
                        }
                        link.parent().parent().find(".select_all_at_page").removeAttr('checked');
                        var cur_val = jQuery("#"+input_ref).val();
                        if(cur_val) {
                            if(cur_val == 'all') {
                                link.parent().parent().find(".inside table tr td input[type=checkbox]").attr('checked', 'checked');
                                link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                                link.parent().parent().find(".popup_statistic .selected_count").html(data.count);
                            } else {
                                var cur_array = cur_val.split(',');
                                link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                                link.parent().parent().find(".popup_statistic .selected_count").html(cur_array.length);
                                for(key in cur_array) {
                                    link.parent().parent().find(".inside table tr td input[type=checkbox][value="+cur_array[key]+"]").attr('checked', 'checked');
                                }
                            }
                        }  else {
                            link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                            link.parent().parent().find(".popup_statistic .selected_count").html('0');
                        }
                    }
                    jQuery('.fancybox-inner').width('auto');
                    jQuery('.fancybox-wrap').width('auto');

                },
                error: function(data) {
                    link.parent().parent().find(".inside table tr td").html(data.html);
                }
            });
        });

        jQuery(".search_field").keypress(function(e) {
            if(e.which == 13) {
                var input_ref = jQuery(this).parent().find(".input_ref").val();
                var obj_str = jQuery("#"+input_ref).val();
                if(obj_str) {
                    if(obj_str != 'all') {
                        var temp_array = obj_str.split(',');
                        var obj_array = new Array();
                        for(key in temp_array) {
                            obj_array[temp_array[key]] = 1;
                        }
                    }
                } else {
                    var obj_array = new Array();
                }
                if(obj_str != 'all') {
                    jQuery(this).parent().parent().find('.inside table tr td input[type="checkbox"]').each(function() {
                        if(jQuery(this).attr('checked')) {
                            obj_array[jQuery(this).val()] = 1;
                        } else {
                            delete obj_array[jQuery(this).val()];
                        }
                    });

                    var res_array = new Array();
                    for(key in obj_array) {
                        res_array[res_array.length] = key;
                    }
                    jQuery("#"+input_ref).val(res_array.join(','));
                } else {
                    jQuery("#"+input_ref).val('all');
                }
                jQuery("#"+input_ref).trigger('change');

                search_text = jQuery(this).val();
                var goto = 'first';
                var page = 1;
                datatype = input_ref;
                display = jQuery(this).parent().find('.show').val();
                order = jQuery(this).parent().find('.order').val();

                var link = jQuery(this);
                jQuery.ajax({
                    type: "POST",
                    url: site_url+"/wp-admin/admin-ajax.php",
                    data: "action=get_popup_pagination_data&datatype=" + datatype + "&page=" + page + "&goto=" + goto + "&display=" + display + "&order=" + order + "&search=" + search_text,
                    dataType: "json",
                    success: function(data){
                        if(data.html) {
                            link.parent().parent().find(".inside table tr td").html(data.html);
                            for(key in data.buttons) {
                                if(data.buttons[key]) {
                                    link.parent().find(".pagination_links[rel="+key+"]").show();
                                } else {
                                    link.parent().find(".pagination_links[rel="+key+"]").hide();
                                }
                            }
                            if(data.count > 1) {
                                link.parent().find(".page_num").html(data.page);
                            } else {
                                link.parent().find(".page_num").html('');
                            }
                            link.parent().parent().find(".select_all_at_page").removeAttr('checked');
                            var cur_val = jQuery("#"+input_ref).val();
                            if(cur_val) {
                                if(cur_val == 'all') {
                                    link.parent().parent().find(".inside table tr td input[type=checkbox]").attr('checked', 'checked');
                                    link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                                    link.parent().parent().find(".popup_statistic .selected_count").html(data.count);
                                } else {
                                    var cur_array = cur_val.split(',');
                                    link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                                    link.parent().parent().find(".popup_statistic .selected_count").html(cur_array.length);
                                    for(key in cur_array) {
                                        link.parent().parent().find(".inside table tr td input[type=checkbox][value="+cur_array[key]+"]").attr('checked', 'checked');
                                    }
                                }
                            }  else {
                                link.parent().parent().find(".popup_statistic .total_count").html(data.count);
                                link.parent().parent().find(".popup_statistic .selected_count").html('0');
                            }
                        }
                        jQuery('.fancybox-inner').width('auto');
                        jQuery('.fancybox-wrap').width('auto');

                    },
                    error: function(data) {
                        link.parent().parent().find(".inside table tr td").html(data.html);
                    }
                });
            }
        });

        jQuery(".pagination_links").click(function() {
            var input_ref = jQuery(this).parent().parent().find(".input_ref").val();
            var obj_str = jQuery("#"+input_ref).val();

            if(obj_str) {
                if(obj_str != 'all') {
                    var temp_array = obj_str.split(',');
                    var obj_array = new Array();
                    for(key in temp_array) {
                        obj_array[temp_array[key]] = 1;
                    }
                }
            } else {
                var obj_array = new Array();
            }

            if(obj_str != 'all') {
                jQuery(this).parent().parent().find('.inside table tr td input[type="checkbox"]').each(function() {
                    if(jQuery(this).attr('checked')) {
                        obj_array[jQuery(this).val()] = 1;
                    } else {
                        delete obj_array[jQuery(this).val()];
                    }
                });

                var res_array = new Array();
                for(key in obj_array) {
                    res_array[res_array.length] = key;
                }
                jQuery("#"+input_ref).val(res_array.join(','));
            } else {
                jQuery("#"+input_ref).val('all');
            }
            jQuery("#"+input_ref).trigger('change');

            var goto = jQuery(this).attr('rel');
            var page = jQuery(this).parent().children(".page_num").html();
            if( !(typeof(page) == 'number' || !isNaN(page)) )
                page = 1;

            datatype = input_ref;

            var link = jQuery(this);

            jQuery.ajax({
                type: "POST",
                url: site_url+"/wp-admin/admin-ajax.php",
                data: "action=get_popup_pagination_data&datatype=" + datatype + "&page=" + page + "&goto=" + goto + "&display=" + display + "&order=" + order + "&search=" + search_text,
                dataType: "json",
                success: function(data){
                    if(data.html) {
                        link.parent().parent().find(".inside table tr td").html(data.html);
                        for(key in data.buttons) {
                            if(data.buttons[key]) {
                                link.parent().children(".pagination_links[rel="+key+"]").show();
                            } else {
                                link.parent().children(".pagination_links[rel="+key+"]").hide();
                            }
                        }
                        link.parent().children(".page_num").html(data.page);
                        link.parent().parent().find(".select_all_at_page").removeAttr('checked');
                        var cur_val = jQuery("#"+input_ref).val();
                        if(cur_val) {
                            if(cur_val == 'all') {
                                link.parent().parent().find(".inside table tr td input[type=checkbox]").attr('checked', 'checked');
                            } else {
                                var cur_array = cur_val.split(',');
                                for(key in cur_array) {
                                    link.parent().parent().find(".inside table tr td input[type=checkbox][value="+cur_array[key]+"]").attr('checked', 'checked');
                                }
                            }
                        }
                    }
                    jQuery('.fancybox-inner').width('auto');
                    jQuery('.fancybox-wrap').width('auto');
                },
                error: function(data) {
                    link.parent().parent().find(".inside table tr td").html(data.html);
                }
            });
        });

        //Cancel Assign block
        jQuery( ".select_all2" ).click( function() {
            var input_ref = jQuery(this).parent().parent().find(".input_ref").val();
            jQuery(this).parent().parent().find( '.select_all_at_page' ).attr( 'checked', false );

            if(jQuery(this).is(":checked")) {
                jQuery(this).parent().parent().find( '.inside input[type="checkbox"]' ).attr( 'checked', true );
                jQuery("#"+input_ref).val('all');
                var new_num = jQuery( this ).parents(".postbox").find(".popup_statistic .total_count").html();
                jQuery( this ).parents(".postbox").find(".popup_statistic .selected_count").html(new_num);
            } else {
                jQuery(this).parent().parent().find( '.inside input[type="checkbox"]' ).attr( 'checked', false );
                jQuery("#"+input_ref).val('');
                jQuery(this).parent().parent().find( '.inside input[type="checkbox"]' ).trigger('change');
                jQuery( this ).parents(".postbox").find(".popup_statistic .selected_count").html('0');
            }
        });

        //Cancel Assign block
        jQuery( ".cancel_popup2" ).click( function() {
            jQuery(this).parent().parent().find( 'input[type="checkbox"]' ).removeAttr( 'checked');
            jQuery.fancybox.close();
        });

        //Ok Assign block
        jQuery( ".ok_popup2" ).click( function() {
            var input_ref = jQuery(this).parent().find(".input_ref").val();
            var obj_str = jQuery("#"+input_ref).val();
            ok_action = 1;
            if(obj_str) {
                if(obj_str != 'all') {
                    var temp_array = obj_str.split(',');
                    var obj_array = new Array();
                    for(key in temp_array) {
                        obj_array[temp_array[key]] = 1;
                    }
                }
            } else {
                var obj_array = new Array();
            }
            if(obj_str != 'all') {
                jQuery(this).parent().parent().find('.inside table tr td input[type="checkbox"]').each(function() {
                    if(jQuery(this).attr('checked')) {
                        obj_array[jQuery(this).val()] = 1;
                    } else {
                        delete obj_array[jQuery(this).val()];
                    }
                });

                var res_array = new Array();
                for(key in obj_array) {
                    res_array[res_array.length] = key;
                }
                jQuery("#"+input_ref).val(res_array.join(','));
            } else {
                jQuery("#"+input_ref).val('all');
            }
            jQuery("#"+input_ref).trigger('change');
            jQuery(this).parent().find(".input_ref").val('');
            jQuery.fancybox.close();
        });

        //Select/Un-select all clients
        jQuery( ".select_all_at_page" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery(this).parent().parent().find( '.inside input[type="checkbox"]' ).attr( 'checked', true );
                jQuery( this ).attr( 'checked', true );
            } else {
                jQuery(this).parent().parent().find( '.inside input[type="checkbox"]' ).attr( 'checked', false );
                jQuery( this ).attr( 'checked', false );
                //jQuery( this ).parents(".postbox").find(".popup_statistic .selected_count").html('0');
            }
            jQuery(this).parent().parent().find( '.inside input[type="checkbox"]' ).trigger('change');
        });
    });