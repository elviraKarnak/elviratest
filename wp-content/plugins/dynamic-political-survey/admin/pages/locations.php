<?php
/**
 * Locations Management Page
 * Manage districts, loksabha, and assembly constituencies
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('dps_location_action')) {
    
    if (isset($_POST['add_district'])) {
        $name = sanitize_text_field($_POST['district_name']);
        if (!empty($name)) {
            $table_districts = $wpdb->prefix . 'dps_districts';
            $wpdb->insert($table_districts, ['name' => $name]);
            echo '<div class="notice notice-success is-dismissible"><p>District added successfully!</p></div>';
        }
    }
    
    if (isset($_POST['add_loksabha'])) {
        $district_id = intval($_POST['district_id']);
        $name = sanitize_text_field($_POST['loksabha_name']);
        if (!empty($name) && $district_id > 0) {
            $table_loksabha = $wpdb->prefix . 'dps_loksabha';
            $wpdb->insert($table_loksabha, [
                'district_id' => $district_id,
                'name' => $name
            ]);
            echo '<div class="notice notice-success is-dismissible"><p>Loksabha added successfully!</p></div>';
        }
    }
    
    if (isset($_POST['add_assembly'])) {
        $loksabha_id = intval($_POST['loksabha_id']);
        $name = sanitize_text_field($_POST['assembly_name']);
        if (!empty($name) && $loksabha_id > 0) {
            $table_assembly = $wpdb->prefix . 'dps_assembly';
            $wpdb->insert($table_assembly, [
                'loksabha_id' => $loksabha_id,
                'name' => $name
            ]);
            echo '<div class="notice notice-success is-dismissible"><p>Assembly added successfully!</p></div>';
        }
    }
}

// Get all locations
$table_districts = $wpdb->prefix . 'dps_districts';
$table_loksabha = $wpdb->prefix . 'dps_loksabha';
$table_assembly = $wpdb->prefix . 'dps_assembly';

$districts = $wpdb->get_results("SELECT * FROM $table_districts ORDER BY name");
?>

<div class="wrap dps-locations-page">
    <h1><?php _e('Manage Locations', 'dynamic-survey'); ?></h1>
    <p class="description">
        <?php _e('Manage the location hierarchy: Districts → Loksabha → Assembly constituencies', 'dynamic-survey'); ?>
    </p>
    
    <div class="dps-locations-layout">
        <!-- Add Forms -->
        <div class="dps-location-forms">
            <!-- Add District -->
            <div class="location-form-card">
                <h2><?php _e('Add District', 'dynamic-survey'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('dps_location_action'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="district_name"><?php _e('District Name', 'dynamic-survey'); ?></label></th>
                            <td>
                                <input type="text" 
                                       id="district_name" 
                                       name="district_name" 
                                       class="regular-text" 
                                       required>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="add_district" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add District', 'dynamic-survey'); ?>
                    </button>
                </form>
            </div>
            
            <!-- Add Loksabha -->
            <div class="location-form-card">
                <h2><?php _e('Add Loksabha', 'dynamic-survey'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('dps_location_action'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="loksabha_district"><?php _e('District', 'dynamic-survey'); ?></label></th>
                            <td>
                                <select id="loksabha_district" name="district_id" class="regular-text" required>
                                    <option value=""><?php _e('Select District', 'dynamic-survey'); ?></option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?php echo $district->id; ?>">
                                            <?php echo esc_html($district->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="loksabha_name"><?php _e('Loksabha Name', 'dynamic-survey'); ?></label></th>
                            <td>
                                <input type="text" 
                                       id="loksabha_name" 
                                       name="loksabha_name" 
                                       class="regular-text" 
                                       required>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="add_loksabha" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Loksabha', 'dynamic-survey'); ?>
                    </button>
                </form>
            </div>
            
            <!-- Add Assembly -->
            <div class="location-form-card">
                <h2><?php _e('Add Assembly', 'dynamic-survey'); ?></h2>
                <form method="post" action="" id="add-assembly-form">
                    <?php wp_nonce_field('dps_location_action'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="assembly_district"><?php _e('District', 'dynamic-survey'); ?></label></th>
                            <td>
                                <select id="assembly_district" class="regular-text district-select" required>
                                    <option value=""><?php _e('Select District', 'dynamic-survey'); ?></option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?php echo $district->id; ?>">
                                            <?php echo esc_html($district->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="assembly_loksabha"><?php _e('Loksabha', 'dynamic-survey'); ?></label></th>
                            <td>
                                <select id="assembly_loksabha" name="loksabha_id" class="regular-text" required disabled>
                                    <option value=""><?php _e('Select District first', 'dynamic-survey'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="assembly_name"><?php _e('Assembly Name', 'dynamic-survey'); ?></label></th>
                            <td>
                                <input type="text" 
                                       id="assembly_name" 
                                       name="assembly_name" 
                                       class="regular-text" 
                                       required>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" name="add_assembly" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Assembly', 'dynamic-survey'); ?>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Location Tree View -->
        <div class="dps-location-tree">
            <h2><?php _e('Location Hierarchy', 'dynamic-survey'); ?></h2>
            
            <?php if (empty($districts)): ?>
                <p class="no-locations"><?php _e('No locations added yet. Start by adding a district.', 'dynamic-survey'); ?></p>
            <?php else: ?>
                <div class="location-tree-view">
                    <?php foreach ($districts as $district): 
                        // Get loksabha for this district
                        $loksabhas = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $table_loksabha WHERE district_id = %d ORDER BY name",
                            $district->id
                        ));
                    ?>
                        <div class="tree-district">
                            <div class="tree-item district-item">
                                <span class="dashicons dashicons-location"></span>
                                <strong><?php echo esc_html($district->name); ?></strong>
                                <span class="item-count">(<?php echo count($loksabhas); ?> Loksabha)</span>
                            </div>
                            
                            <?php if (!empty($loksabhas)): ?>
                                <div class="tree-children">
                                    <?php foreach ($loksabhas as $loksabha):
                                        // Get assemblies for this loksabha
                                        $assemblies = $wpdb->get_results($wpdb->prepare(
                                            "SELECT * FROM $table_assembly WHERE loksabha_id = %d ORDER BY name",
                                            $loksabha->id
                                        ));
                                    ?>
                                        <div class="tree-loksabha">
                                            <div class="tree-item loksabha-item">
                                                <span class="dashicons dashicons-admin-site"></span>
                                                <?php echo esc_html($loksabha->name); ?>
                                                <span class="item-count">(<?php echo count($assemblies); ?> Assembly)</span>
                                            </div>
                                            
                                            <?php if (!empty($assemblies)): ?>
                                                <div class="tree-children">
                                                    <?php foreach ($assemblies as $assembly): ?>
                                                        <div class="tree-item assembly-item">
                                                            <span class="dashicons dashicons-building"></span>
                                                            <?php echo esc_html($assembly->name); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dps-locations-page {
    max-width: 1600px;
}

.dps-locations-page > p.description {
    margin: 10px 0 20px;
    font-size: 15px;
}

.dps-locations-layout {
    display: grid;
    grid-template-columns: 600px 1fr;
    gap: 30px;
    margin-top: 20px;
}

.dps-location-forms {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.location-form-card {
    background: #fff;
    padding: 25px;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.location-form-card h2 {
    margin: 0 0 20px;
    font-size: 18px;
    color: #2c3e50;
    padding-bottom: 10px;
    border-bottom: 2px solid #ecf0f1;
}

.dps-location-tree {
    background: #fff;
    padding: 25px;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.dps-location-tree h2 {
    margin: 0 0 20px;
    font-size: 18px;
    color: #2c3e50;
    padding-bottom: 10px;
    border-bottom: 2px solid #ecf0f1;
}

.no-locations {
    text-align: center;
    padding: 60px 20px;
    color: #999;
    font-size: 15px;
}

.location-tree-view {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.tree-district {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.tree-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    font-size: 14px;
}

.district-item {
    background: #667eea;
    color: #fff;
    font-size: 15px;
}

.district-item .dashicons {
    color: #fff;
}

.loksabha-item {
    background: #f8f9fa;
    font-weight: 500;
}

.assembly-item {
    background: #fff;
    padding-left: 30px;
    border-top: 1px solid #f0f0f1;
}

.tree-children {
    padding-left: 0;
}

.tree-loksabha {
    border-top: 1px solid #e9ecef;
}

.item-count {
    margin-left: auto;
    font-size: 13px;
    opacity: 0.8;
}

@media (max-width: 1200px) {
    .dps-locations-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load loksabha when district selected for assembly form
    $('#assembly_district').on('change', function() {
        const districtId = $(this).val();
        const $loksabhaSelect = $('#assembly_loksabha');
        
        if (!districtId) {
            $loksabhaSelect.html('<option value="">Select District first</option>').prop('disabled', true);
            return;
        }
        
        $loksabhaSelect.html('<option value="">Loading...</option>').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dps_get_loksabha',
                district_id: districtId,
                nonce: '<?php echo wp_create_nonce("dps_admin_nonce"); ?>'
            },
            success: function(response) {
                if (response.success && response.data.loksabha) {
                    let options = '<option value="">Select Loksabha</option>';
                    response.data.loksabha.forEach(function(item) {
                        options += '<option value="' + item.id + '">' + item.name + '</option>';
                    });
                    $loksabhaSelect.html(options).prop('disabled', false);
                } else {
                    $loksabhaSelect.html('<option value="">No loksabha found</option>');
                }
            }
        });
    });
});
</script>