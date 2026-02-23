<?php
/**
 * Template Name: Add property Form
 * Description: Custom property submission form styled like Viabostad registration
 */

?>
<div class="property-form-wrapper">
    <div class="property-form-container">
        <div class="property-form-card">
     
            <div class="property-form-header">
                <h1>Add New Property</h1>
                <p>Create a new property listing for your portfolio</p>
            </div>

            <form method="post" enctype="multipart/form-data" id="addpropertyForm">
                <?php //wp_nonce_field('submit_property_form', 'property_form_nonce'); ?>

                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="property_title">Property Title <span class="required">*</span></label>
                        <input type="text" id="property_title" name="property_title" placeholder="e.g., Luxury Villa in Stockholm" required>
                    </div>

                    <div class="form-group">
                        <label for="property_description">Description <span class="required">*</span></label>
                        <textarea id="property_description" name="property_description" placeholder="Provide a detailed description of the property..." required></textarea>
                        <div class="helper-text">Tell us about the property's features, amenities, and what makes it special</div>
                    </div>
                </div>

                <!-- Property Details -->
                <div class="form-section">
                    <h3 class="section-title">Property Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bedrooms">Bedrooms <span class="required">*</span></label>
                            <input type="number" id="bedrooms" name="bedrooms" min="0" placeholder="2" required>
                        </div>
                        <div class="form-group">
                            <label for="bathrooms">Bathrooms <span class="required">*</span></label>
                            <input type="number" id="bathrooms" name="bathrooms" min="0" placeholder="2" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="area">Area (sqm) <span class="required">*</span></label>
                            <input type="number" id="area" name="area" min="0" placeholder="144" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price ($) <span class="required">*</span></label>
                            <input type="number" id="price" name="price" min="0" placeholder="2250000" required>
                        </div>
                    </div>

                    <!-- <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <input type="text" id="location" name="location" placeholder="e.g., Stockholm, Sweden" required>
                    </div> -->

                   <div class="form-group">
                        <label for="acf_address">Property Location</label>

                        <input type="text" id="acf_address" name="address" placeholder="Search location..." autocomplete="off">

                        <!-- Hidden ACF Google Map Fields -->
                        <input type="hidden" name="acf_map[address]" id="acf_map_address">
                        <input type="hidden" name="acf_map[lat]" id="acf_map_lat">
                        <input type="hidden" name="acf_map[lng]" id="acf_map_lng">
                        <input type="hidden" name="acf_map[zoom]" id="acf_map_zoom" value="14">
                        <input type="hidden" name="acf_map[name]" id="acf_map_name">
                        <input type="hidden" name="acf_map[street_number]" id="acf_map_street_number">
                        <input type="hidden" name="acf_map[street_name]" id="acf_map_street_name">
                        <input type="hidden" name="acf_map[city]" id="acf_map_city">
                        <input type="hidden" name="acf_map[state]" id="acf_map_state">
                        <input type="hidden" name="acf_map[post_code]" id="acf_map_post_code">
                        <input type="hidden" name="acf_map[country]" id="acf_map_country">
                        <input type="hidden" name="acf_map[country_short]" id="acf_map_country_short">
                    </div>
                </div>

                <!-- Property Images -->
                <div class="form-section">
                    <h3 class="section-title">Property Images</h3>
                    
                    <div class="form-group">
                        <label>Upload Images <span class="required">*</span></label>
                        <div class="image-upload-area">
                            <input type="file" id="property_images" name="property_images[]" multiple accept="image/*" required>
                            <div class="upload-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="#2EAADC">
                                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                </svg>
                            </div>
                            <div class="upload-text">Choose Photos</div>
                            <div class="upload-formats">Accepted formats: JPG, PNG, GIF (Max: 5MB)</div>
                        </div>
                        <div class="image-preview" id="imagePreview"></div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="form-section">
                    <h3 class="section-title">Property Type</h3>

                     <?php
                        $terms = get_terms([
                            'taxonomy'   => 'property-type',
                            'hide_empty' => true, // set false if you want empty terms too
                        ]);

                    if (!empty($terms) && !is_wp_error($terms)) {?>
                        <div class="categories-grid">
                            <?php foreach ( $terms as $term ) { ?>
                                <div class="category-item">
                                    <input type="checkbox" id="<?php echo $term->slug; ?>" name="propertytype[]" value="<?php echo $term->term_id; ?>">
                                    <label for="<?php echo $term->slug; ?>"><?php echo esc_html($term->name); ?></label>
                                </div>
                            <?php  } ?> 
                        </div>
                        <?php } ?>
                    </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="submit_property" class="btn btn-primary">
                        <span>Publish Property</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {

            // INIT GOOGLE AUTOCOMPLETE PROPERLY
            function initAutocomplete() {

                if (typeof google === 'undefined' || !google.maps.places) {
                    console.log('Google Places not loaded');
                    return;
                }

                const input = document.getElementById('acf_address');
                if (!input) return;

                const autocomplete = new google.maps.places.Autocomplete(input, {
                    types: ['geocode'], // show full address suggestions
                    fields: ['formatted_address', 'geometry', 'address_components']
                });

                autocomplete.addListener('place_changed', function () {

                    const place = autocomplete.getPlace();
                    if (!place.geometry) return;

                    // Basic data
                    $('#acf_map_address').val(place.formatted_address);
                    $('#acf_map_lat').val(place.geometry.location.lat());
                    $('#acf_map_lng').val(place.geometry.location.lng());
                    $('#acf_map_zoom').val(14);

                    // Clear old values first
                    $('#acf_map_street_number, #acf_map_street_name, #acf_map_city, #acf_map_state, #acf_map_post_code, #acf_map_country').val('');

                    
                    //console.log('Selected place:', place);
                    
                    
                    // Fill address components
                    place.address_components.forEach(function(component) {

                        const types = component.types;

                    
                        if (types.includes('street_number')) {
                            $('#acf_map_street_number').val(component.long_name);
                        }

                        if (types.includes('route')) {
                            $('#acf_map_street_name').val(component.long_name);
                        }

                        if (types.includes('postal_town')) {
                            $('#acf_map_city').val(component.long_name);
                        }

                        if (types.includes('administrative_area_level_1')) {
                            $('#acf_map_state').val(component.long_name);
                        }

                        if (types.includes('postal_code')) {
                            $('#acf_map_post_code').val(component.long_name);
                        }

                        if (types.includes('country')) {
                            $('#acf_map_country').val(component.long_name);
                        }

                    });

                });
            }

            // Run after window fully loads (important!)
            jQuery(window).on('load', function () {
                initAutocomplete();
            });

    
            // Image Upload Handler
            const uploadArea = document.querySelector('.image-upload-area');
            const fileInput = document.getElementById('property_images');
            const imagePreview = document.getElementById('imagePreview');
            let uploadedFiles = [];

            // Drag over
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#2EAADC';
                uploadArea.style.backgroundColor = '#f8f9fa';
            });

            // Drag leave
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '';
                uploadArea.style.backgroundColor = '';
            });

            // Drop
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '';
                uploadArea.style.backgroundColor = '';
                const files = Array.from(e.dataTransfer.files);
                handleFiles(files);
            });

            // File input change
            fileInput.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                handleFiles(files);
            });

            function handleFiles(files) {
                // Clear previous previews if needed (optional)
                // imagePreview.innerHTML = '';
                
                files.forEach(function(file, index) {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'preview-item';
                            previewItem.setAttribute('data-index', uploadedFiles.length);
                            
                            // Add numbering for first image
                            const isFirstImage = uploadedFiles.length === 0;
                            const imageLabel = isFirstImage ? '<span class="featured-badge">Featured</span>' : '';
                            
                            previewItem.innerHTML = `
                                <img src="${e.target.result}" alt="Preview">
                                ${imageLabel}
                                <button type="button" class="remove-image" data-index="${uploadedFiles.length}">×</button>
                            `;
                            imagePreview.appendChild(previewItem);

                            // Add remove event
                            previewItem.querySelector('.remove-image').addEventListener('click', function() {
                                const index = parseInt(this.getAttribute('data-index'));
                                previewItem.remove();
                                // Optional: Remove from uploadedFiles array
                            });
                        };
                        
                        reader.readAsDataURL(file);
                        uploadedFiles.push(file);
                    }
                });
            }


  



        $('#addpropertyForm').on('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);
            formData.append('action', 'submit_property_form');
            //formData.append('nonce', property_ajax_obj.nonce);

            //   const fileInput = document.getElementById('property_images');
            //     if (fileInput.files.length > 0) {
            //         for (let i = 0; i < fileInput.files.length; i++) {
            //             formData.append('property_images[]', fileInput.files[i]);
            //         }
            // }
            // console.log('Form submit triggered');

            $.ajax({
                url: '<?php echo home_url('/wp-admin/admin-ajax.php')?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 60000,  // Increase timeout for file uploads

                beforeSend: function () {
                    $('.btn-primary')
                        .prop('disabled', true)
                        .html('Submitting...');
                },

                success: function (response) {

                    $('.btn-primary')
                        .prop('disabled', false)
                        .html('Publish Property');

                    if (response.success) {

                        $('.success-message').remove();

                        $('#addpropertyForm').before(
                            '<div class="success-message show">' +
                            response.data.message +
                            '</div>'
                        );

                        $('#addpropertyForm')[0].reset();

                    } else {
                        alert(response.data.message);
                    }
                },

                  error: function (xhr, status, error) {
                    $('.btn-primary')
                        .prop('disabled', false)
                        .html('Publish Property');

                    // More detailed error handling
                    let errorMessage = 'Something went wrong.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Upload took too long. Please try again with fewer images.';
                    } else if (xhr.status === 413) {
                        errorMessage = 'File size too large. Please reduce image sizes.';
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.data && response.data.message) {
                                errorMessage = response.data.message;
                            }
                        } catch (e) {
                            errorMessage = 'Server error occurred.';
                        }
                    }

                    $('.error-message').remove();
                    $('#addpropertyForm').before(
                        '<div class="error-message" style="padding: 15px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">' +
                        errorMessage +
                        '</div>'
                    );
                }
            });

        });

    });

</script>