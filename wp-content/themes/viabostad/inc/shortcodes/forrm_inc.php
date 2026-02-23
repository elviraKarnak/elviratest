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