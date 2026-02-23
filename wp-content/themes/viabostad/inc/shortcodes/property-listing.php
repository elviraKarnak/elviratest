<?php
// Register shortcode
add_action( 'init', function () {
    add_shortcode( 'property-listing', 'property_listing_callback' );
});

// Shortcode callback
function property_listing_callback() {
    ob_start();
    ?>

  <style>
	.woocommerce-ordering,
	.woocommerce-result-count{
		display: none;
	}
    .map-popup {
    width: 220px;
}
.map_wrapper {
    position: sticky;
    top: calc(var(--headerHeight) - 10px);
}
 
#property-map {
    width: 100%;
    height: calc(100vh - 110px);
    border-radius: 12px;
}
.map-popup h4 {
    margin: 0 0 6px;
    font-size: 16px;
}



.map-popup .map-btn {
    display: inline-block;
    background: #1e88e5;
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
}

.custom-marker {
    position: absolute;
    transform: translate(-50%, -100%);
    cursor: pointer;
    z-index: 1;
    transition: transform 0.2s ease;
}

.custom-marker:hover {
    transform: translate(-50%, -105%) scale(1.05);
    z-index: 999;
}

.marker-card {
    width: 60px;
    height: 60px;
    background: #fff;
    padding: 4px;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.18);
    overflow: hidden;
}

.marker-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 10px;
}

.marker-dot {
    width: 10px;
    height: 10px;
    background: #2ea8ff;
    border-radius: 50%;
    margin: 4px auto 0;
}

  </style>


	<?php 
        $dummyImg =  get_stylesheet_directory_uri() .'/assets/images/contact-banner.webp';
        $thumb_id = get_post_thumbnail_id();
        $img_src  = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'large') : '';
        $img_alt  = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';


        if($innerBanner){
            $imgUrl   = $img_src[0];
            $alt      = $img_alt;
            $height   = $img_src[2];
            $width    = $img_src[1];
        }else{
            $imgUrl   = $dummyImg;
            $alt      = 'Inner banner';
            $height   = '390';
            $width    = '1920';
        }
    ?>
    
 
	  <section class="sticky_map_locations">
        <div class="container-fluid">
          <div class="outer_wrapper">
            <div class="row">       
              <div class="col-lg-8 col-xl-7">
				<div class="left_wrapper">
					<div class="sec_head">
						<form id="filter-property">
							<div class="shop_filter">
								<div class="find_property_wrapper">
									<div class="row align-items-center gy-md-4 gy-3">
										<div class=" col-md-6">
											<div class="form_wrapper">
												<div class="input_wrap">
													<div class="field">
														<input type="text" placeholder="Search for a place" name="property-search">
													</div>
													<a href="javascript:void(0)" class="filter-open-btn"><img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/filter-btn-blue.svg" alt="filter-btn-blue" width="62" height="47"></a>
												</div>
												<div class="filter_fields">
												<!-- <div class="field">
													<label for="location">
														<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/location.svg" alt="location" width="14" height="20"/>
													Location
													</label>

													<?php
														$terms = get_terms([
															'taxonomy'   => 'location',
															'hide_empty' => true, // set false if you want empty terms too
														]);

													if (!empty($terms) && !is_wp_error($terms)) { ?>
					

													<select name="property-location" id="location">
														<option value="all" disabled selected >Choose your location</option>
														<?php //foreach ( $terms as $term ) { ?>
															<!-- <option value="<?php echo $term->slug; ?>"> <?php echo esc_html($term->name); ?></option> -->
														<?php //} ?>
													<!-- </select> -->
												<?php } ?>
											<!-- </div>  -->
											<div class="field">
												<label for="type">
													<img
														src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/home.svg"
														alt="home"
														width="20"
														height="20"
													/>
													What type you looking for
												</label>
												<?php
													$terms = get_terms([
														'taxonomy'   => 'property-type',
														'hide_empty' => true, // set false if you want empty terms too
													]);

													if (!empty($terms) && !is_wp_error($terms)) {?>
														<select name="property-type" id="type">
															<option value="all" disabled selected >Choose your Category</option>
															<?php foreach ( $terms as $term ) { ?>
																	<option value="<?php echo $term->slug; ?>"> <?php echo esc_html($term->name); ?></option>
																<?php } ?>
														</select>
													<?php } ?>
											</div>
											<div class="field">
												<label for="price">
													<img
													src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/price.svg"
													alt="price"
													width="20"
													height="20"
												/>
												Price</label
												>
												<input
												type="text"
												name="property-price"
												id="price"
												placeholder="22,500,000"
												/>
											</div>
											<div class="btn_groups">
												<button class="primary_btn icon search" type="submit">Filter Property</button>
											</div>
											</div>
											</div>
										</div>

                                       
									</div>
								</div>  
							</div>
						</form>
						</div>

						<div id="all_properties">

							 <?php 

                             $map_properties = [];

                                $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
                             
                               $product_query = new WP_Query([
                                'post_type'      => 'property',
                                'posts_per_page' => 10,
                                'paged' => $paged,
                                'orderby' => 'id',
                                'order' => 'ASC',
                                ]);

                            if ( $product_query->have_posts() ) { ?>

							<div class="total_found_products">
								<?php
			
								
									$total        = $product_query->found_posts;
									$per_page     = $product_query->get( 'posts_per_page' );
									$current      = max( 1, get_query_var( 'paged' ) );

									$first = ( $per_page * $current ) - $per_page + 1;
									$last  = min( $total, $per_page * $current );
									?>

									<p class="search_result">
										Showing Newest Results <?php echo esc_html( $first ); ?>–<?php echo esc_html( $last ); ?>
										of <?php echo esc_html( $total ); ?>
									</p>

							</div>
					 
						
                                                                
                                            <div class="row gy-md-4 gy-3 property-slider">
                        
                                                <?php while ( $product_query->have_posts() ) :
                            
                                                    $product_query->the_post();

                                                    get_template_part( 'template-part/poperty-loop' );

                                                    $location = get_field('address_sp', get_the_ID());

                                                     if ($location) {
                                                        $map_properties[] = [
                                                            'title' => get_the_title(),
                                                            'lat'   => $location['lat'],
                                                            'lng'   => $location['lng'],
                                                            'link'  => get_permalink(),
                                                            'price' => get_field('_price'),
                                                            'image' => get_the_post_thumbnail_url()
                                                        ];
                                                    }
                                                
                                                endwhile; ?>

                                            </div>

                                            <?php

                                

                                            // /* PAGINATION */
                                            // $big = 999999999;

                                            // $pagination = paginate_links(array(
                                            //     'base'      => str_replace($big, '%#%', esc_url('?paged=' . $big)),
                                            //     'format'    => '?paged=%#%',
                                            //     'current'   => max(1, $paged),
                                            //     'total'     => $product_query->max_num_pages,
                                            //     'type'      => 'array',
                                            // ));

                                            // if ($pagination) {
                                            //     echo '<div class="ajax-pagination"><ul>';
                                            //     foreach ($pagination as $page) {
                                            //         echo '<li>' . $page . '</li>';
                                            //     }
                                            //     echo '</ul></div>';
                                            // }

                                       
                                        ?>


                                            <?php } else { ?>
                                                <p class="no-result">No properties found.</p>
                                                <?php } wp_reset_postdata(); ?>


                                                <?php
                                                    $total_pages = $product_query->max_num_pages;

                                                    if ( $paged < $total_pages ) : ?>
                                                        
                                                        <div class="col-12 mt-md-5 mt-3">
                                                            <div class="show_more_wrapper text-center">
                                                                <a href="javascript:void(0)" 
                                                                class="primary_btn icon arrow load-more-btn"
                                                                data-page="<?php echo esc_attr($paged + 1); ?>"
                                                                data-max="<?php echo esc_attr($total_pages); ?>">
                                                                Show More
                                                                </a>
                                                            </div>
                                                        </div>

                                                    <?php endif; ?>
										
				</div>
              </div>
			</div> 
              <div class="col-lg-4 col-xl-5">
               <div class="map_wrapper">
                    <div id="property-map"></div>
                </div>
              </div>
            </div>
            </div>
          </div>
      </section>


	


   <script>

        let map;
        let markers = [];
        let infoWindow;

        var propertyData = <?php echo json_encode($map_properties); ?>;

        function initPropertyMap() {

            const mapContainer = document.getElementById('property-map');
            if (!mapContainer) return;

            const defaultCenter = propertyData.length
                ? { lat: parseFloat(propertyData[0].lat), lng: parseFloat(propertyData[0].lng) }
                : { lat: 59.3293, lng: 18.0686 };

            map = new google.maps.Map(mapContainer, {
                zoom: 6,
                center: defaultCenter,

                // ✅ SAFE light gray modern style
                styles: [
                    {
                        featureType: "poi",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "transit",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "road",
                        elementType: "labels.icon",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "water",
                        elementType: "geometry",
                        stylers: [{ color: "#e9ecef" }]
                    },
                    {
                        featureType: "landscape",
                        elementType: "geometry",
                        stylers: [{ color: "#f8f9fa" }]
                    }
                ]
            });

            infoWindow = new google.maps.InfoWindow();
            const bounds = new google.maps.LatLngBounds();

            propertyData.forEach((property) => {

                const position = new google.maps.LatLng(
                    parseFloat(property.lat),
                    parseFloat(property.lng)
                );

                createCustomMarker(position, property);
                bounds.extend(position);

            });

                        if (propertyData.length > 1) {
                map.fitBounds(bounds);
            } else {
                map.setZoom(12);
            }
        }

        function createCustomMarker(position, property) {

            const overlay = new google.maps.OverlayView();

            overlay.onAdd = function () {

                const div = document.createElement("div");
                div.className = "custom-marker";

                div.innerHTML = `
                    <div class="marker-card">
                        <img src="${property.image}" />
                    </div>
                    <div class="marker-dot"></div>
                `;

                div.addEventListener("click", () => {
                    infoWindow.setContent(`
                        <div class="map-popup">
                            <img src="${property.image}" width="100%">
                            <h4>${property.title}</h4>
                            <p>$${property.price}</p>
                        </div>
                    `);
                    infoWindow.setPosition(position);
                    infoWindow.open(map);
                });

                overlay.div = div;
                overlay.getPanes().overlayMouseTarget.appendChild(div);
            };

            overlay.draw = function () {
                const projection = this.getProjection();
                const point = projection.fromLatLngToDivPixel(position);

                if (point) {
                    this.div.style.position = "absolute";
                    this.div.style.left = point.x + "px";
                    this.div.style.top = point.y + "px";
                }
            };

            overlay.setMap(map);
        }


        function updateMapMarkers(newData) {

            // Clear old markers
            if (markers.length) {
                markers.forEach(marker => {
                    if(marker.setMap){
                        marker.setMap(null);
                    }
                });
                markers = [];
            }

            propertyData = newData;

            const bounds = new google.maps.LatLngBounds();

            propertyData.forEach((property) => {

                const position = new google.maps.LatLng(
                    parseFloat(property.lat),
                    parseFloat(property.lng)
                );

                createCustomMarker(position, property);
                bounds.extend(position);
            });

            if(propertyData.length){
                map.fitBounds(bounds);
            }
        }


      jQuery(document).ready(function ($) {

        initPropertyMap();
    
        let currentFilters = {};

        function getFormValues() {
            var search   = $('input[name="property-search"]').val().trim();
            var location = $('select[name="property-location"]').val();
            var type     = $('select[name="property-type"]').val();
            var price    = $('input[name="property-price"]').val().trim();

            price = price.replace(/,/g, '');

            return {
                search: search,
                location: location,
                type: type,
                price: price
            };
        }

        function load_properties(paged = 1, append = false) {

            var formData = new FormData();

            formData.append('action', 'filter_search_property');
            formData.append('paged', paged);

            formData.append('property-search', currentFilters.search);
            formData.append('property-location', currentFilters.location);
            formData.append('property-type', currentFilters.type);
            formData.append('property-price', currentFilters.price);

            // ✅ Only show full loader for fresh filter
            if(!append){
                $('#all_properties').html('<span class="loader-property"></span>');
            } else {
                $('.load-more-btn').text('Loading...');
            }

            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {

                    if(append){
                         var temp = $('<div>').html(response);

                        var newItems  = temp.find('.property-slider').html();
                        var newButton = temp.find('.show_more_wrapper');
                        var newCount  = temp.find('.total_found_products').html();

                        console.log('New Items:', newItems);
                        console.log('New Button:', newButton);  
                            console.log('New Count:', newCount);

                        // Append new property items
                        if(newItems){
                            $('.property-slider').append(newItems);
                        }

                        // Update result counter
                        if(newCount){
                            $('.total_found_products').html(newCount);
                        }

                        // Replace Load More button
                        $('.show_more_wrapper').remove();

                        if(newButton.length){
                            $('#all_properties').append(newButton);
                        }
                         if(mapJson){
                            var newMapData = JSON.parse(mapJson);
                            updateMapMarkers(newMapData);
                        }
                    } else {
                        $('#all_properties').html(response);
                    }
                }
            });
        }
            /* FILTER SUBMIT */
            $('#filter-property').on('submit', function (e) {
                e.preventDefault();

                currentFilters = getFormValues(); // store values

                if (
                    currentFilters.search === '' &&
                    (!currentFilters.location || currentFilters.location === 'all') &&
                    (!currentFilters.type || currentFilters.type === 'all') &&
                    currentFilters.price === ''
                ) {
                    alert('Please select at least one filter option.');
                    return false;
                }

                load_properties(1, false); // load first page with new filters
            });

        
        
            /* LOAD MORE CLICK */
                $(document).on('click', '.load-more-btn', function (e) {
                    e.preventDefault();

                    var button = $(this);
                    var nextPage = button.data('page');
                    var maxPage = button.data('max');

                    load_properties(nextPage, true);

                    if(nextPage >= maxPage){
                        button.closest('.show_more_wrapper').remove();
                    }
                });

        });





    </script>

    <?php
    return ob_get_clean();
}
