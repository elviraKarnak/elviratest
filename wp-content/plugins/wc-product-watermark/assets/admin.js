
jQuery(document).ready(function($){
    var frame;
    $('#wcpw_upload_btn').on('click', function(e){
        e.preventDefault();
        if (frame) { frame.open(); return; }
        frame = wp.media({
            title: WCPW_Admin.title,
            button: { text: WCPW_Admin.button },
            multiple: false
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#wcpw_watermark_image').val(attachment.url).trigger('change');
            $('#wcpw_preview').html('<img src="'+attachment.url+'" style="max-width:150px;height:auto;border:1px solid #ddd;padding:4px;background:#fff;border-radius:4px;" />');
        });
        frame.open();
    });
});
