(function($){
    'use strict';

    // Helpers ---------------------------------------------------------------
    function trimStr(s){
        return (s || '').toString().trim();
    }

    function cleanIdList(val){
        // Keep only digits and commas, normalize spacing, remove empties
        if (!val) return '';
        return val
            .split(',')
            .map(function(x){ return x.replace(/[^\d]/g, '').trim(); })
            .filter(function(x){ return x.length > 0; })
            .join(',');
    }

    function cleanCommaListAlpha(val){
        // alpha list (post types, roles) - lowercase, trim, remove empties
        if (!val) return '';
        var seen = {};
        return val
            .split(',')
            .map(function(x){ return x.toLowerCase().trim(); })
            .filter(function(x){
                if (!x) return false;
                if (seen[x]) return false;
                seen[x] = true;
                return true;
            })
            .join(',');
    }

    // UI toggles ------------------------------------------------------------
    function toggleConditionalFields(){
        var mode = $('input[name="ewqc_settings[conditional_rules][mode]"]:checked').val() || 'all';
        if ( mode === 'all' ){
            $('.ewqc-conditional-field').hide();
        } else {
            $('.ewqc-conditional-field').show();
        }
    }

    function toggleUserRolesField(){
        var state = $('select[name="ewqc_settings[conditional_rules][user_logged_in]"]').val();
        var $rolesInput = $('input[name="ewqc_settings[conditional_rules][user_roles]"]');
        if ( state === 'only' ){
            $rolesInput.prop('disabled', false).closest('label,td').show();
        } else {
            // keep value but disable input so it won't confuse UI; we still save its value
            $rolesInput.prop('disabled', false); // keep enabled so main form may serialize it (some forms ignore disabled inputs)
            // visually hide it unless you prefer to show
            $rolesInput.closest('label,td').show();
        }
    }

    // Before submit sanitization --------------------------------------------
    function sanitizeBeforeSubmit($form){
        // find conditional inputs inside the form
        var $pageIds = $form.find('input[name="ewqc_settings[conditional_rules][page_ids]"]');
        var $productIds = $form.find('input[name="ewqc_settings[conditional_rules][product_ids]"]');
        var $postTypes = $form.find('input[name="ewqc_settings[conditional_rules][post_types]"]');
        var $productCats = $form.find('input[name="ewqc_settings[conditional_rules][product_cats]"]');
        var $userRoles = $form.find('input[name="ewqc_settings[conditional_rules][user_roles]"]');

        if ($pageIds.length) {
            $pageIds.val( cleanIdList( $pageIds.val() ) );
        }
        if ($productIds.length) {
            $productIds.val( cleanIdList( $productIds.val() ) );
        }
        if ($postTypes.length) {
            $postTypes.val( cleanCommaListAlpha( $postTypes.val() ) );
        }
        if ($productCats.length) {
            // product_cats may contain slugs or numeric ids - sanitize whitespace and duplicates
            $productCats.val( cleanCommaListAlpha( $productCats.val() ) );
        }
        if ($userRoles.length) {
            $userRoles.val( cleanCommaListAlpha( $userRoles.val() ) );
        }

        // URL contains - trim only
        var $urlContains = $form.find('input[name="ewqc_settings[conditional_rules][url_contains]"]');
        if ($urlContains.length) {
            $urlContains.val( trimStr( $urlContains.val() ) );
        }
    }

    // Attach handlers -------------------------------------------------------
    $(document).ready(function(){
        // toggle on load
        toggleConditionalFields();
        toggleUserRolesField();

        // when mode changes
        $(document).on('change', 'input[name="ewqc_settings[conditional_rules][mode]"]', function(){
            toggleConditionalFields();
        });

        // when user_logged_in changes
        $(document).on('change', 'select[name="ewqc_settings[conditional_rules][user_logged_in]"]', function(){
            toggleUserRolesField();
        });

        // Basic sanitization on blur for ID and comma fields (helpful UX)
        $(document).on('blur', 'input[name="ewqc_settings[conditional_rules][page_ids]"], input[name="ewqc_settings[conditional_rules][product_ids]"]', function(){
            var v = $(this).val();
            $(this).val( cleanIdList(v) );
        });

        $(document).on('blur', 'input[name="ewqc_settings[conditional_rules][post_types]"], input[name="ewqc_settings[conditional_rules][product_cats]"], input[name="ewqc_settings[conditional_rules][user_roles]"]', function(){
            var v = $(this).val();
            $(this).val( cleanCommaListAlpha(v) );
        });

        // hook into the nearest form for submit sanitization:
        // conditional UI may be inside a larger settings form; find the closest form ancestor.
        var $sampleInput = $('input[name="ewqc_settings[conditional_rules][mode]"]');
        if ($sampleInput.length) {
            var $closestForm = $sampleInput.closest('form');
            if (!$closestForm.length) {
                // fallback - use first form on page
                $closestForm = $('form').first();
            }

            if ($closestForm && $closestForm.length) {
                $closestForm.on('submit', function(e){
                    sanitizeBeforeSubmit( $(this) );
                    // allow submit to continue
                });
            }
        }
    });

})(jQuery);
