<?php
// Register shortcode
add_action( 'init', function () {
    add_shortcode( 'home-search-form-two', 'home_search_two_callback' );
});

// Shortcode callback
function home_search_two_callback() {
    ob_start();
    ?>
     <form>
                  <div class="field search">
                    <label>
                      Area
                      <input type="text" placeholder="Enter area or address">
                    </label>
                  </div>
                  <div class="field checkboxes_wrapper">
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" fill="none" viewBox="0 0 24 16"><rect width="6" height="6.857" fill="#007E47" rx="1"></rect><rect width="6" height="6.857" y="9.143" fill="#045D6C" rx="1"></rect><rect width="6" height="6.857" x="9" fill="#66B291" rx="1"></rect><rect width="6" height="6.857" x="9" y="9.143" fill="#898B83" rx="1"></rect><rect width="6" height="6.857" x="18" fill="#67458C" rx="1"></rect><rect width="6" height="6.857" x="18" y="9.143" fill="#BA9F78" rx="1"></rect></svg>
                      All types
                      </div>
                    </label>
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                      <svg xmlns="http://www.w3.org/2000/svg" width="25" height="20" fill="none" viewBox="0 0 25 20"><title>Villa</title><path fill="#007E47" fill-rule="evenodd" d="M1.123 19.968v-9.863H.2l1.555-5.198h4.518L12.15 0l5.322 4.479V1.455h2.909v3.452h2.215l1.603 5.198h-.923v9.863zm9.668-11.142v-3.53h3.53v3.53zm3.53 3.53h-3.53v7.061h3.53v-7.06Zm1.766 3.53v-3.53h3.53v3.53zm-10.591-3.53v3.53h3.53v-3.53z" clip-rule="evenodd"></path></svg>
                      Villas
                      </div>
                    </label>
                    <label>
                        <input type="checkbox" name="house_type">
                        <div class="field_text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="17" fill="none" viewBox="0 0 24 17"><title>Radhus</title><path fill="#66B291" d="M11.7 0v17l-2.875-.001v-4.856H7.388v4.856L.2 17V3.643zM4.513 12.143H3.075v3.643h1.438zM8.825 6.07H7.388V8.5h1.437zm-4.312 0H3.075V8.5h1.438zM23.2 0v17l-2.875-.001v-4.856h-1.438v4.856L11.7 17V3.643zm-7.188 12.143h-1.437v3.643h1.438v-3.643Zm4.313-6.072h-1.438V8.5h1.438zm-4.313 0h-1.437V8.5h1.438V6.071Z"></path></svg>
                      Pair/Chain/Townhouse
                      </div>
                    </label>
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="24" fill="none" viewBox="0 0 20 24"><title>Lägenhet</title><path fill="#67458C" d="M19.2 0v24h-8v-4.8H8V24H0V0zM6.4 17.6H3.2v3.2h3.2zm9.6 0h-3.2v3.2H16zm0-4.8h-3.2V16H16zm-4.8 0H8V16h3.2zm-4.8 0H3.2V16h3.2zM16 8h-3.2v3.2H16zm-4.8 0H8v3.2h3.2zM6.4 8H3.2v3.2h3.2zM16 3.2h-3.2v3.2H16zm-4.8 0H8v3.2h3.2zm-4.8 0H3.2v3.2h3.2z"></path></svg>
                      Apartments
                      </div>
                    </label>
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" fill="currentColor" viewBox="0 0 25 24"><title>Fritidshus</title><path fill="#045D6C" d="m16.7 9 7.5 4.5V24h-3v-7.5h-1.5V24H.2v-1.5h9v-9zm0 7.5h-3V21h3zM3.2 9v4.5H1.7V9zm4.19-2.871 3.18 3.181-1.06 1.06-3.181-3.18 1.06-1.06ZM7.438 0A9.77 9.77 0 0 1 .2 7.239V0h7.239ZM13.7 1.5V3H9.2V1.5z"></path></svg>
                      Holiday home
                      </div>
                    </label>
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="18" fill="none" viewBox="0 0 25 18"><title>Tomt</title><path fill="#BB4E4E" d="M.2 18V0h12v9h12v9z"></path><path fill="#fff" d="m13.292 4.5 1.06 1.06-19.091 19.092-1.061-1.06zm4.5 0 1.06 1.06L-.239 24.653l-1.061-1.06zm4.5 0 1.06 1.06L4.261 24.653 3.2 23.592zm4.5 0 1.06 1.06L8.761 24.653 7.7 23.592zm4.5 0 1.06 1.06-19.091 19.092-1.061-1.06zm-18-4.5 1.06 1.06-19.091 19.093-1.061-1.061zm0-4.5 1.06 1.06-19.091 19.093-1.061-1.061zm0-4.5 1.06 1.06-19.091 19.092-1.061-1.06z"></path></svg>
                      Plots
                      </div>
                    </label>
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22"><title>Gård/Skog</title><path fill="#4BA3B2" d="m7.743 18.168 2.841-2.598h-1.09c-.32 0-.603-.143-.738-.375-.135-.23-.091-.495.116-.69l2.78-2.609h-1.028c-.324 0-.615-.153-.742-.39-.125-.234-.066-.505.15-.692l3.923-3.372c.215-.185.615-.185.83 0l3.923 3.372c.217.187.276.458.15.691-.126.238-.418.391-.742.391H17.09l2.78 2.61c.206.194.25.459.116.69-.136.23-.419.374-.74.374h-1.09l2.843 2.598c.213.194.262.461.126.696-.133.231-.426.38-.747.38H15.51v.702l1.077 1.39c.19.305-.086.664-.509.664h-3.414c-.423 0-.698-.36-.509-.665l1.078-1.389v-.702h-4.87c-.32 0-.614-.149-.747-.38-.135-.235-.087-.502.127-.696"></path><path fill="#4BA3B2" d="M16.752 17.595h-5.639v-.281l3.228-2.95 2.411 2.204zm0-3.674h-4.855l2.444-2.294 2.411 2.264zm-9.532-.003H5.656v3.925H.2V7.494l2.546-4.996L8.386 0l5.638 2.498 2.185 4-.344-.295h-.001a2.34 2.34 0 0 0-1.524-.549c-.46 0-1.04.133-1.523.549l3.935 3.183-3.935-3.183-.002.001-2.611 2.245V6.423H6.566v3.57h1.949c-.452.637-.572 1.495-.15 2.28q.071.134.156.252l-.841.79a2.3 2.3 0 0 0-.46.603"></path><path fill="#4BA3B2" d="M13.214 10.247h2.253l-1.127-.968z"></path></svg>
                        Farms/Forests
                      </div>
                      </label>
                    <label>
                      <input type="checkbox" name="house_type">
                      <div class="field_text">
                          <svg xmlns="http://www.w3.org/2000/svg" width="25" height="14" fill="none" viewBox="0 0 25 14"><title>Övrigt</title><path fill="#BA9F78" d="m21 0 3.2 2.38v10.71H21V8.33h-3.2v4.76h-1.6V8.33H13v4.76H.2V2.38L3.4 0zM6.6 8.33H3.4v2.38h3.2zm4.8 0H8.2v2.38h3.2zM6.6 3.57H3.4v1.19h3.2zm4.8 0H8.2v1.19h3.2zm4.8 0H13v1.19h3.2zm4.8 0h-3.2v1.19H21z"></path></svg>
                        Other
                      </div>
                    </label>
                  </div>
                  <div class="field checkboxes_wrapper without_icon">
                    <p>Sold within the last few</p>
                    <label>
                      <input type="checkbox" name="sold_few_months">
                      <div class="field_text">
                        <div class="circle"></div>
                      3 months
                      </div>
                    </label>
                    <label>
                      <input type="checkbox" name="sold_few_months">
                      <div class="field_text">
                        <div class="circle"></div>
                      6 months
                      </div>
                    </label>
                    <label>
                        <input type="checkbox" name="sold_few_months">
                        <div class="field_text">
                        <div class="circle"></div>
                      12 months
                      </div>
                    </label>
                    <label>
                      <input type="checkbox" name="sold_few_months">
                      <div class="field_text">
                        <div class="circle"></div>
                      Show all
                      </div>
                    </label>
                  </div>
                  <div class="filter_options text-center">
                    <button type="button" class="show_filter_option">Show search filters <i class="fas fa-chevron-right"></i></button>
                    <div class="filter_options_wrapper">
                      <div class="row_group">
                        <div class="field select">
                          <label>
                            Minimum number of rooms
                            <select class="form-select w-100">
                              <option>All</option>
                              <option>1 room</option>
                              <option>2 room</option>
                            </select>
                          </label>
                        </div>
                        <div class="field select">
                          <label>
                            Minimum living area
                            <select class="form-select w-100">
                              <option>All</option>
                              <option>20 m<sup>2</sup></option>
                              <option>25 m<sup>2</sup></option>
                            </select>
                          </label>
                        </div>
                        <div class="field select">
                          <label>
                            Highest price
                            <select class="form-select w-100">
                              <option>Nothing</option>
                              <option>100,000 SEK</option>
                              <option>200,000 SEK</option>
                            </select>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="submit_wrapper">
                    <input type="submit" value="Find homes for sale">
                  </div>
                  </form>

    <?php
    return ob_get_clean();
}
