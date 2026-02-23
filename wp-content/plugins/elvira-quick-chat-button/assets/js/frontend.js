(function($){
    'use strict';

    $(function(){
        // click handler on floating button
        $(document).on('click', '.ewqc-button', function(e){
            e.preventDefault();

            // if plugin configured to always use default phone (no round-robin), you can skip AJAX
            // We'll ask server for the next agent (round-robin)
            $.ajax({
                url: ewqcData.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'ewqc_get_agent',
                    nonce: ewqcData.nonce_get_agent
                }
            }).done(function(resp){
                if (resp && resp.success && resp.data && resp.data.url) {
                    // Open WhatsApp URL in new tab/window
                    window.open(resp.data.url, '_blank');

                    // Track the click (fire-and-forget)
                    $.post(ewqcData.ajaxUrl, {
                        action: 'ewqc_track_click',
                        nonce: ewqcData.nonce_track,
                        page_url: window.location.href
                    });
                } else {
                    // fallback: use default_phone + default_message (localized)
                    if ( ewqcData.default_phone ) {
                        var message = encodeURIComponent( ewqcData.default_message || '' );
                        var phone = ewqcData.default_phone;
                        var url = (ewqcData.isMobile ? 'https://api.whatsapp.com/send' : 'https://web.whatsapp.com/send') + '?phone=' + phone + '&text=' + message;
                        window.open(url, '_blank');

                        // track fallback click
                        $.post(ewqcData.ajaxUrl, {
                            action: 'ewqc_track_click',
                            nonce: ewqcData.nonce_track,
                            page_url: window.location.href
                        });
                    } else {
                        // nothing to do
                        console.warn('EWQC: no phone to open');
                        alert('No WhatsApp number configured.');
                    }
                }
            }).fail(function(){
                // network/error fallback
                if ( ewqcData.default_phone ) {
                    var message = encodeURIComponent( ewqcData.default_message || '' );
                    var phone = ewqcData.default_phone;
                    var url = (ewqcData.isMobile ? 'https://api.whatsapp.com/send' : 'https://web.whatsapp.com/send') + '?phone=' + phone + '&text=' + message;
                    window.open(url, '_blank');

                    // track fallback click
                    $.post(ewqcData.ajaxUrl, {
                        action: 'ewqc_track_click',
                        nonce: ewqcData.nonce_track,
                        page_url: window.location.href
                    });
                } else {
                    alert('No WhatsApp number configured.');
                }
            });
        });
    });
})(jQuery);
