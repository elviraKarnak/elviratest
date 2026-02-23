jQuery(document).ready(function($){
    var frame;
    $('#wc_pw_select_watermark').on('click', function(e){
        e.preventDefault();
        if (frame) frame.open();
        frame = wp.media({
            title: 'Select Watermark Image',
            button: {text: 'Use as watermark'},
            multiple:false
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#wc_pw_watermark_id').val(attachment.id);
            var thumb = (attachment.sizes && attachment.sizes.thumbnail)?attachment.sizes.thumbnail.url:attachment.url;
            $('#wc_pw_watermark_preview').html('<img src="'+thumb+'" style="max-width:120px;height:auto;">');
        });
        frame.open();
    });
    $('#wc_pw_remove_watermark').on('click', function(e){
        e.preventDefault();
        $('#wc_pw_watermark_id').val('');
        $('#wc_pw_watermark_preview').html('');
    });
});
