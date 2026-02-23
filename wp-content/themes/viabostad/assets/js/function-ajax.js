jQuery(document).ready(function ($) {


    var ajaxUrl = viabostad_functions.ajax_url; // Access the localized AJAX URL
    var nonce = viabostad_functions.nonce; // Access the localized nonce
    var loginUrl = viabostad_functions.login; // Access the localized login URL
    var is_user_logged_in_flag = viabostad_functions.is_user_logged_in; // Access the localized login status





    // loop wishlists

    jQuery(document).on('click', '.heart_icon', function () {

        if (!is_user_logged_in_flag) {
            alert('Please login first');
             window.location.href = loginUrl;
            return;
        }

        var button = jQuery(this);
        var property_id = button.data('property-id');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle_property_wishlist',
                property_id: property_id,
                nonce: nonce
            },
            success: function (response) {

                if (response.success) {

                    // 🔁 Toggle active class only on clicked heart
                    button.toggleClass('active');

                    console.log('Toggled property ID:', property_id);

                } else {
                    alert('Something went wrong');
                }

            }
        });

    });

        // home page filter tabs

        $('#pills-tab').on('click', '.nav-link', function (e) {
          e.preventDefault();

          const slug = $(this).attr('id'); // apartment-flats, bungalows, etc

          // active state
          $('.nav-link').removeClass('active');
          $(this).addClass('active');

          // loader (optional)
          $('#pills-tabContent').html('<span class="loader-property"></span>');

          $.ajax({
            url: ajaxUrl, // Use the localized AJAX URL
            type: 'POST',
            data: {
              action: 'load_products_by_category',
              category: slug,
              nonce: nonce // Include the nonce for security
            },
            success: function (response) {
              $('#pills-tabContent').html(response);
            }
          });

    });



     $('.wishlist-toggle').on('click', function(e) {
        e.preventDefault();

           if (!is_user_logged_in_flag) {
            alert('Please login first');
             window.location.href = loginUrl;
            return;
        }

        
        var button = $(this);
        var propertyId = button.data('property-id');
        
        // Check if user is logged in (you can set this via PHP)
 
        // If user is logged in, proceed with AJAX
        var heartIcon = button.find('.yith-wcwl-icon');
        var buttonText = button.find('.button_text');
        
        // Disable button during request
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle_property_wishlist',
                property_id: propertyId,
                nonce: nonce
            },
            success: function(response) {
                 if (response.success) {

                if (response.data.action === 'added') {

                    // ✅ Saved state
                    heartIcon.attr('fill', '#3fa9db');
                    heartIcon.attr('stroke', '#3fa9db');
                    buttonText.text('Saved');
                    button.addClass('saved');

                } else {

                    // ✅ Removed state
                    heartIcon.attr('fill', 'none');
                    heartIcon.attr('stroke', 'currentColor');
                    buttonText.text('Save Property');
                    button.removeClass('saved');
                }
                    // Show success message
                   // alert(response.data.message);
                } else {
                    alert('Error occurred');
                }
            },
            error: function() {
                alert('Error updating wishlist');
            },
            complete: function() {
                // Re-enable button
                button.prop('disabled', false);
            }
        });
    });

 
});