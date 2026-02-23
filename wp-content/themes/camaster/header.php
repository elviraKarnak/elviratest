<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?=get_template_directory_uri();?>/theme-assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?=get_template_directory_uri();?>/theme-assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="<?=get_template_directory_uri();?>/theme-assets/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="<?=get_template_directory_uri();?>/theme-assets/css/style.css">
    <link rel="stylesheet" href="<?=get_template_directory_uri();?>/theme-assets/css/responsive.css">
    <?php wp_head(); ?>
    </head>     
<body>
<header>
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="topbar-left-txt">
                        <p><i class="fa-solid fa-phone"></i> <span>09876543210</span></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="topbar-mid-txt">
                        <p>Today's Deal! Sale Off <span>50% Off Shop now!</span></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="topbar-rigt-box">
                        <p>Follow Us:</p>
                        <ul>
                            <li><a href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
                            <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
                            <li><a href="#"><i class="fa-brands fa-youtube"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="nav-menu">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="menubar">
                        <div class="logo-box">
                            <img src="assets/images/logo.png" alt="Logo" class="img-fluid" />
                        </div>
                        <div class="menu">
                            <ul>
                                <li><a href="#">Home</a></li>
                                <li><a href="#">About Us</a></li>
                                <li><a href="#">Shop</a></li>
                                <li><a href="#">Collections</a></li>
                                <li><a href="#">Blog</a></li>
                                <li><a href="#">Contact Us</a></li>
                            </ul>
                            <div class="mobile-toggle" data-bs-toggle="offcanvas" href="#mobile-nav">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                        <div class="menu-icon-box">
                            <ul>
                                <li><a href="#"><i class="fa-solid fa-magnifying-glass"></i></a></li>
                                <li><a href="/my-account"><i class="fa-regular fa-user"></i></a></li>
                                <li><a href="/cart"><i class="fa-solid fa-cart-shopping"></i></a></li>
                                <li><a href="/wishlist"><i class="fa-regular fa-heart"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!------------Start Mobile Navbar-------------> 
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobile-nav" aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasExampleLabel">Menu</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="menu mob-menu">
        <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Shop</a></li>
            <li><a href="#">Collections</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">Contact Us</a></li>
        </ul>
      </div>
    </div>
  </div>
<!------------End Mobile Navbar------------->



