(function($){
    $(document).ready(function(){

        // Initialize the WP color picker on fields
        if ( typeof $.fn.wpColorPicker === 'function' ) {
            $('.ewqc-color-field').wpColorPicker();
        }

        // Media uploader for custom icon
        var frame;
        $('#ewqc_upload_icon_button').on('click', function(e){
            e.preventDefault();

            if ( frame ) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: (typeof ewqcCustom !== 'undefined' && ewqcCustom.uploadTitle) ? ewqcCustom.uploadTitle : 'Select or upload an icon',
                button: { text: (typeof ewqcCustom !== 'undefined' && ewqcCustom.uploadButton) ? ewqcCustom.uploadButton : 'Use this icon' },
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#ewqc_custom_icon_url').val(attachment.url);
            });

            frame.open();
        });

        // Toggle custom icon row visibility (in case the inline handler is removed)
        $('input[name="ewqc_settings[button_icon_type]"]').on('change', function(){
            if ( $(this).val() === 'custom' ) {
                $('.ewqc-custom-icon-row').slideDown();
            } else {
                $('.ewqc-custom-icon-row').slideUp();
            }
        });
    });
})(jQuery);
