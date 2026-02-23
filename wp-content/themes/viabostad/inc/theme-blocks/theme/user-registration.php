</head>



        <section class="user_registration">

        <?php if (!is_user_logged_in()) : ?>
            <div class="registration-container">
                <div class="form-header">
                <h1>User Registration</h1>
                <p>Join our network of professional real estate brokers</p>
                </div>

                <div class="form-body">
                <form id="brokerRegistrationForm" method="post" enctype="multipart/form-data">

                <div class="form-section">
                    <h2 class="section-title">Select User Type</h2>

                    <select name="user_type" id="user_type" required>
                         <option value="" disabled selected hidden>-- Select User Type --</option>
                         <option value="agency">Agency</option>
                         <option value="broker">Broker</option>
                         <option value="customer">Customer</option>
                    </select>
                    </div>
                    <!-- Personal Information Section -->
                    <div class="form-section">
                    <h2 class="section-title">Personal Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                        <label for="firstName">First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" name="first_name" required>
                        </div>

                        <div class="form-group">
                        <label for="lastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required>
                        <div class="help-text">Choose a unique username for your profile</div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        </div>

                        <div class="form-group">
                        <label for="confirmPassword">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="form-section">
                    <h2 class="section-title">Contact Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required>
                        </div>

                        <div class="form-group">
                        <label for="website">Website URL</label>
                        <input type="url" id="website" name="website" placeholder="https://">
                        </div>
                    </div>
                    </div>

                    <!-- Profile Information Section -->
                    <div class="form-section">
                    <h2 class="section-title">Profile Information</h2>

                    <div class="form-group">
                        <label for="profilePicture">Profile Picture <span class="required">*</span></label>
                        <div class="profile-picture-upload">
                        <div class="profile-preview">
                            <i class="fas fa-user placeholder"></i>
                            <img id="profilePreview" src="" alt="Profile Preview">
                        </div>
                        <div class="upload-controls">
                            <div class="file-input-wrapper">
                            <input type="file" id="profilePicture" name="profile_picture" accept="image/*" required>
                            <label for="profilePicture">
                                <i class="fas fa-upload"></i> Choose Photo
                            </label>
                            </div>
                            <div class="file-info">
                            Accepted formats: JPG, PNG, GIF (Max 5MB)
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Profile Description / Bio <span class="required">*</span></label>
                        <textarea id="bio" name="bio" required placeholder="Tell us about your experience, expertise, and what makes you a great broker..."></textarea>
                        <div class="help-text">Minimum 100 characters</div>
                    </div>
                    </div>

                    <!-- Professional Information Section -->
                    <div class="form-section agency-select-group d-none">
                    <h2 class="section-title">Agency Information</h2>
                        <?php 

                        $agency_users = get_users([
                            'role'    => 'agency',
                            'orderby' => 'display_name',
                            'order'   => 'ASC'
                        ]);

                        if ( ! empty( $agency_users ) ) { ?>

                            <div class="form-group">
                                <label for="agency">Select Agency <span class="required">*</span></label>
                                <select id="agency" name="agency">

                                    <option value="" disabled selected hidden>
                                        -- Select Your Agency --
                                    </option>

                                    <?php foreach ( $agency_users as $user ) : 
                                        
                                        $full_name = trim( $user->first_name . ' ' . $user->last_name );

                                        // fallback if no first/last name
                                        if ( empty( $full_name ) ) {
                                            $full_name = $user->display_name;
                                        }
                                    ?>

                                        <option value="<?php echo esc_attr( $user->ID ); ?>">
                                            <?php echo esc_html( $full_name ); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>
                            </div>

                        <?php } ?>
                                            </div>

                    <!-- Terms and Conditions -->
                    <div class="form-section">
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="#" style="color: #3fa9db;">Terms and Conditions</a> and <a href="#" style="color: #3fa9db;">Privacy Policy</a> <span class="required">*</span></label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="primary_btn icon arrow">
                    Register
                    </button>

                    <div class="already-registered">
                    Already have an account? <a href="#">Sign In</a>
                    </div>
                </form>
                </div>
            </div>
            <?php ?>
        <?php else : ?>
            <div class="registration-container">
                <div class="form-header">
                <h2>Welcome Back!</h2>
                <p>You are already logged in. <a href="<?php echo wp_logout_url( home_url() ); ?>">Logout?</a></p>
            </div>
            </div>
        <?php endif; ?>
        </section>       


<script>

jQuery(document).ready(function($){

       $('.btn-submit').prop('disabled', true);


       $("#user_type").on('change', function(){
            if($(this).val() == 'broker'){
                $('.agency-select-group').removeClass('d-none');
                $('#agency').prop('required', true);
            } else {
                $('.agency-select-group').addClass('d-none');
                $('#agency').removeAttr('required');
            }
       });

    $('#username').on('blur', function(){

        var username = $(this).val().trim();

        if(username.length < 3){
            return;
        }

        $.ajax({
            url:'<?php echo home_url('/wp-admin/admin-ajax.php')?>',
            type: 'POST',
            data: {
                action: 'check_username',
                username: username
            },
            beforeSend: function(){
                $('#username').next('.help-text').text('Checking...');
            },
            success: function(response){

                if(response.success){
                    $('#username').next('.help-text')
                        .css('color','green')
                        .text(response.data.message);
                      $('.btn-submit').prop('disabled', false);
                } else {
                    $('#username').next('.help-text')
                        .css('color','red')
                        .text(response.data.message);
                    $('.btn-submit').prop('disabled', true);
                }

            }
        });

    });

    $('#brokerRegistrationForm').on('submit', function(e){
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'register_broker');

        $.ajax({
            url: '<?php echo home_url('/wp-admin/admin-ajax.php')?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(){
                $('.btn-submit').text('Registering...');
            },
            success: function(response){

                if(response.success){
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data.message);
                    $('.btn-submit').text('Register as Broker');
                }

            },
            error: function(){
                alert('Something went wrong');
                $('.btn-submit').text('Register as Broker');
            }
        });

    });

});

</script>