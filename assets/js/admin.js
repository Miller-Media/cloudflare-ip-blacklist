jQuery(document).ready(function(){

    var $ubSelect = jQuery('select[name="unblacklist_ip"]');
    $ubSelect.after("<button id='cfip_ajax_btn' class='cfip_ajax_btn' name='cfip_ajax_btn'>Remove From Blacklist</button><div id='cfip_ajax_res_unblacklist'></div>");

    jQuery("#cfip_ajax_btn").on('click', function(e){

        e.preventDefault();

        var ip_address = $ubSelect.val();
        var $response = jQuery("#cfip_ajax_res_unblacklist");

        $response.text('Removing IP from blacklist...');

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
                    $response.text("There was an error.");
                    console.log(data);
                    return;
                }

                // Remove IP Address from select dropdown.
                jQuery('select[name="unblacklist_ip"] option[value="'+ip_address+'"]').remove();

                $response.text("IP address successfully removed from blacklist.");
                console.log(data);
            },
            error: function(errorThrown){
                console.log('there was an error');
                console.log(errorThrown);
            }
        });
    });

    jQuery("#clear_log_button").on('click', function(e){

        e.preventDefault();

        var $response = jQuery("#cfip_ajax_res_clearlog");
        $response.fadeIn();
        $response.text('Clearing log...');

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
                    $response.text("There was an error.");
                    return;
                }

                // Clear log on front end.
                jQuery('#log_screen').text("");

                $response.text("Log cleared..");
                setTimeout(function(){
                    $response.fadeOut();
                }, 1500);
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