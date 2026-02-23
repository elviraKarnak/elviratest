(function( $ ) {
	'use strict';
        $( ".ep-dismissible" ).click(function(){
            var notice_name = $( this ).attr( 'id' );
            var data        = {'action': 'ep_dismissible_notice','notice_name': notice_name,'nonce':ep_ajax_object.nonce};
            $.post(
                ep_ajax_object.ajax_url,
                data,
                function(response) {

                });
        });

        $( document ).on( 'click', '.ep-license-notice .notice-dismiss', function() {
            var noticeWrapper = $( this ).closest( '.ep-license-notice' );
            var noticeType    = noticeWrapper.data( 'notice-type' );
            //console.log(noticeType);
            if ( ! noticeType ) {
                return;
            }

            $.post(
                ep_ajax_object.ajax_url,
                {
                    action: 'ep_dismiss_license_notice',
                    notice_type: noticeType,
                    nonce: ep_ajax_object.nonce
                }
            );
        } );

})( jQuery );
