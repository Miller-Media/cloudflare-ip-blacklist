jQuery(document).ready(function(){

    // Auth method toggle
    function toggleAuthFields() {
        var authType = jQuery('input[name="cf_auth_type"]:checked').val() || 'global_key';
        jQuery('.auth-group-global_key').toggle(authType === 'global_key');
        jQuery('.auth-group-api_token').toggle(authType === 'api_token');
    }
    jQuery('input[name="cf_auth_type"]').on('change', toggleAuthFields);
    toggleAuthFields();

    // Change Credentials toggle
    jQuery('.cfip-toggle-credentials').on('click', function(e) {
        e.preventDefault();
        var $form = jQuery('.cfip-credentials-form');
        var $link = jQuery(this);
        if ($form.is(':visible')) {
            $form.slideUp(200);
            $link.text(cfip_i18n.change_credentials);
        } else {
            $form.slideDown(200, function() {
                toggleAuthFields();
            });
            $link.text(cfip_i18n.cancel);
        }
    });

    var $ubSelect = jQuery('select[name="blacklisted_ips"]');
    $ubSelect.after("<button id='cfip_ajax_btn' class='cfip_ajax_btn cf-btn cf-btn--primary' name='cfip_ajax_btn'>" + cfip_i18n.remove_from_blacklist + "</button><div id='cfip_ajax_res_unblacklist'></div>");

    // Clear blacklisted IP AJAX request.
    jQuery("#cfip_ajax_btn").on('click', function(e){

        e.preventDefault();

        var ip_address = $ubSelect.val();
        var $response = jQuery("#cfip_ajax_res_unblacklist");

        $response.text(cfip_i18n.removing_ip);

        var data = {
            action: 'cfip_unblacklist_ip',
            ip_address: ip_address
        };
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            success: function( data ){
                data = JSON.parse(data);

                // If the request was successful but the API returned an error.
                if( data.errors && data.errors.length > 0 ) {
                    $response.removeClass().addClass('notice notice-error').text(cfip_i18n.error);
                    console.log(data);
                    return;
                } else if( data.result == 'success' ) {
                    // Remove IP Address from select dropdown.
                    jQuery('select[name="blacklisted_ips"] option[value="'+ip_address+'"]').remove();

                    // Check if this was the last item removed from the list
                    if(jQuery('select[name="blacklisted_ips"] option').length == 0){
                        that = jQuery('select[name="blacklisted_ips"]').closest('td');
                        jQuery('select[name="blacklisted_ips"]').remove();
                        jQuery('button#cfip_ajax_btn').remove();
                        that.prepend('<span id="blacklisted_ips">' + cfip_i18n.no_ips_blacklisted + '</span>');
                        $response.removeClass().addClass('notice notice-success').text(cfip_i18n.ip_removed);
                    }
                } else {
                    $response.removeClass().addClass('notice notice-warning').text(cfip_i18n.response_unclear);
                }

                console.log(data);
            },
            error: function(errorThrown){
                console.log('there was an error');
                console.log(errorThrown);
            }
        });
    });

    // Clear log AJAX request.
    jQuery("#clear_log_button").on('click', function(e){

        e.preventDefault();

        var $response = jQuery("#log_screen").find("pre");
        $response.text(cfip_i18n.clearing_log);

        var data = {
            action: 'cfip_clearlog',
            clear_log: true
        };
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            success: function( data ){
                data = JSON.parse(data);

                // If the request was successful but the API returned an error.
                if( data.errors && data.errors.length > 0 ) {
                    $response.text(cfip_i18n.error);
                    return;
                }

                // Clear log on front end.
                $response.text("");

                $response.text(cfip_i18n.log_cleared);
                setTimeout(function(){
                    $response.text("");
                }, 1500);
            },
            error: function(errorThrown){
                console.log('There was an error');
                console.log(errorThrown);
            }
        });
    });

    // View log AJAX request.
    jQuery(".log_tab").on('click', function(e){
        e.preventDefault();

        var $response = jQuery("#log_screen").find("pre");
        $response.text(cfip_i18n.loading_log);

        var data = {
            action: 'cfip_loadlog',
            load_log: true
        };
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            success: function( data ){
                data = JSON.parse(data);

                // If the request was successful but the API returned an error.
                if( data.errors && data.errors.length > 0 ) {
                    $response.text(cfip_i18n.error);
                    return;
                }

                // Load log on front end.
                $response.text(data.log);
            },
            error: function(errorThrown){
                console.log('There was an error');
                console.log(errorThrown);
            }
        });
    });

    // Tabs
    jQuery('.tab-panel').fadeIn();
    jQuery('.tabgroup > div').hide();
    jQuery('.tabgroup > div:first-of-type').show();
    jQuery('.tabs a').click(function(e){
        e.preventDefault();
        var $this = jQuery(this),
            tabgroup = '#'+$this.parents('.tabs').data('tabgroup'),
            others = $this.closest('li').siblings().children('a'),
            target = $this.attr('href');
        others.removeClass('active');
        $this.addClass('active');
        jQuery(tabgroup).children('div').hide();
        jQuery(target).show();
    });

});
