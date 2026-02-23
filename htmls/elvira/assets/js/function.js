jQuery(document).ready(function ($) {
  // for grids
  function resizeGridItems() {
    const $grid = $(".our_works .tab_content");
    // $grid.addClass("active");
    if (!$grid.length) return;

    const rowHeight = parseInt($grid.css("grid-auto-rows"));
    const rowGap = parseInt($grid.css("row-gap"));

    if ($(window).width() <= 767) {
      $grid.find(".grid-item").css("grid-row-end", "auto");
      return;
    }

    $grid.find(".grid-item").each(function () {
      const $item = $(this);
      const $content = $item.find(".inner_wrapper");
      if (!$content.length) return;

      const contentHeight = $content.outerHeight();
      const rowSpan = Math.ceil(
        (contentHeight + rowGap) / (rowHeight + rowGap)
      );

      $item.css("grid-row-end", "span " + rowSpan);
    });
  }

  $(window).on("load", function () {
    resizeGridItems();

    if ("ResizeObserver" in window) {
      const observer = new ResizeObserver(function () {
        resizeGridItems();
      });

      $(".our_works .grid-item .inner_wrapper").each(function () {
        observer.observe(this);
      });
    }
  });

  $(".back_top_top").click(function () {
    $("html, body").animate({ scrollTop: 0 }, 200);
  });
  $(".toggle_menu").click(function () {
    $(this).toggleClass("active");
    $("header .primary_header .inner_wrapper nav").slideToggle();
  });

  var counted = false;
  $(window).on("scroll", function () {
    var $section = $(".happy_clients");
    if (!$section.length) return;

    var sectionOffset =
      $(".happy_clients").offset().top - window.innerHeight + 100;

    if (!counted && $(window).scrollTop() > sectionOffset) {
      counted = true;

      $(".count").each(function () {
        var $this = $(this);
        var target = parseInt($this.data("target"));

        $({ countNum: 0 }).animate(
          { countNum: target },
          {
            duration: 2000,
            easing: "swing",
            step: function () {
              $this.text(Math.floor(this.countNum));
            },
            complete: function () {
              $this.text(target);
            },
          }
        );
      });
    }
  });

  var servicesSlider = new Swiper(".service_swiper", {
    slidesPerView: 3.5,
    spaceBetween: 30,
    loop: true,
    speed: 900,
    grabCursor: true,
    watchOverflow: false,
    autoplay: {
      delay: 900,
      disableOnInteraction: false,
    },
    breakpoints: {
      // Mobile
      0: {
        slidesPerView: 1.2,
        spaceBetween: 16,
      },

      // Small tablets
      576: {
        slidesPerView: 2,
        spaceBetween: 20,
      },

      // Tablets
      768: {
        slidesPerView: 2.5,
        spaceBetween: 24,
      },

      // Desktop
      1299: {
        slidesPerView: 3.5,
        spaceBetween: 30,
      },
    },
  });

  var toolsSlider = new Swiper(".tools_slider_wrapper", {
    slidesPerView: 4.5,
    spaceBetween: 20,
    loop: true,
    speed: 1500,
    grabCursor: true,
    watchOverflow: false,
    autoplay: {
      delay: 900,
      disableOnInteraction: false,
    },
    breakpoints: {
      // Mobile
      0: {
        slidesPerView: 1.2,
        spaceBetween: 16,
      },

      // Small tablets
      576: {
        slidesPerView: 2,
        spaceBetween: 20,
      },

      // Tablets
      768: {
        slidesPerView: 3.5,
        spaceBetween: 24,
      },

      // Desktop
      1299: {
        slidesPerView: 4.5,
        spaceBetween: 30,
      },
    },
  });

  // particle js start
  if (window.matchMedia("(min-width: 768px)").matches) {
    tsParticles.load("tsparticles", {
      fpsLimit: 60,
      particles: {
        number: {
          value: 100,
          density: {
            enable: true,
            value_area: 800,
          },
        },
        color: {
          value: "#22BECA",
        },
        shape: {
          type: "circle",
          stroke: {
            width: 0,
            color: "#000000",
          },
          polygon: {
            nb_sides: 5,
          },
        },
        opacity: {
          value: 0.5,
          random: true,
          anim: {
            enable: true,
            speed: 3,
            opacity_min: 0.1,
            sync: false,
          },
        },
        size: {
          value: 5,
          random: true,
          anim: {
            enable: true,
            speed: 20,
            size_min: 0.1,
            sync: false,
          },
        },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#22BECA",
          opacity: 0.3,
          width: 1,
        },
        move: {
          enable: true,
          speed: 3,
          direction: "none",
          random: false,
          straight: false,
          out_mode: "out",
          attract: {
            enable: false,
            rotateX: 600,
            rotateY: 1200,
          },
        },
        twinkle: {
          particles: {
            enable: true,
            color: "#22BECA",
            frequency: 0.004,
            opacity: 0.6,
          },
        },
      },
      interactivity: {
        events: {
          onhover: {
            enable: true,
            mode: "repulse",
          },
          onclick: {
            enable: true,
            mode: "push",
          },
        },
        modes: {
          repulse: {
            distance: 90,
            duration: 0.4,
          },
        },
      },
      retina_detect: true,
      background: {
        color: "#FFF",
        image: "",
        position: "50% 50%",
        repeat: "no-repeat",
        size: "cover",
      },
    });
  }

  const avatars = document.querySelectorAll(".avatars .avatar");

  var testimonialSwiper = new Swiper(".testimonial_swiper", {
    slidesPerView: 1,
    spaceBetween: 0,
    loop: true,
    speed: 900,
    grabCursor: true,
    dragable: false,
    // autoHeight: true,

    autoplay: {
      delay: 3500,
      disableOnInteraction: false,
      pauseOnMouseEnter: true,
    },

    effect: "slide",
    on: {
      slideChange() {
        const index = this.realIndex;

        avatars.forEach((avatar) => {
          avatar.classList.toggle(
            "active",
            Number(avatar.dataset.slide) === index
          );
        });
      },
    },
  });

  const vidBtn = $(".video_pill");
  const popup = $(".elv_popup");
  const video = $(".elv_popup video")[0];

  vidBtn.on("click", function () {
    popup.addClass("active");
    video && video.play();
  });

  $(".elv_popup .cross").on("click", function () {
    popup.removeClass("active");
    video && video.pause();
  });

  $(document).on("keydown", function (e) {
    if (e.key === "Escape" && popup.hasClass("active")) {
      popup.removeClass("active");
      video && video.pause();
    }
  });

  $(".tab_btn_wrapper li").on("click", function () {
    $(".tab_btn_wrapper li").removeClass("active");
    $(this).addClass("active");

    var filter = $(this).data("filter");

    if (filter === "*") {
      $(".grid-item").fadeIn(200);
    } else {
      $(".grid-item").hide();
      $('.grid-item[data-category="' + filter + '"]').fadeIn(200);
    }
  });

  $(".listing_tabs_btn li").on("click", function () {
    $(".listing_tabs_btn li").removeClass("active");
    $(this).addClass("active");

    var filter = $(this).data("filter");
    // show loader
    $(".filter_loader").fadeIn(150);

    if (filter === "all") {
      $(".tab_cont").fadeIn(200);
    } else {
      $(".tab_cont").fadeOut(150);
      $('.tab_cont[data-category="' + filter + '"]').fadeIn(200);
    }
    
    setTimeout(function () {
      $(".filter_loader").fadeOut(150);
    }, 500);
  });

  // for animation
  var controller = new ScrollMagic.Controller();

  // Trigger for all sections
  $("section").each(function () {
    var scene = new ScrollMagic.Scene({
      triggerElement: this, // section element
      triggerHook: 0.5, // middle of viewport
      duration: $(this).height(), // active while scrolling through section
    })
      .on("enter", function () {
        $(scene.triggerElement()).addClass("section-active");
      })
      // Uncomment if you want to remove on leave
      // .on('leave', function () {
      //   $(scene.triggerElement()).removeClass('section-active');
      // })
      .addTo(controller);
  });

  // Trigger for footer
  var footerScene = new ScrollMagic.Scene({
    triggerElement: ".site_footer", // footer element
    triggerHook: 0.8, // triggers near bottom of viewport
    duration: 0, // triggers once
  })
    .on("enter", function () {
      $(".site_footer").addClass("section-active");
    })
    .addTo(controller);

  $(window).on("scroll", function () {
    var scrollTop = $(window).scrollTop();
    var windowHeight = $(window).height();

    // Bottom-to-top animation
    $(".bottom-to-top-animate").each(function () {
      var elemTop = $(this).offset().top;
      var start = elemTop - windowHeight * 0.9; // similar to GSAP start
      var end = elemTop - windowHeight * 0.3; // similar to GSAP end
      var progress = (scrollTop - start) / (end - start);
      progress = Math.min(Math.max(progress, 0), 1); // clamp 0-1

      $(this).css({
        transform: "translateY(" + 100 * (1 - progress) + "px)",
        opacity: progress,
      });
    });

    // Left-to-right animation
    $(".left-to-right-animate").each(function () {
      var elemTop = $(this).offset().top;
      var start = elemTop - windowHeight * 0.9;
      var end = elemTop - windowHeight * 0.3;
      var progress = (scrollTop - start) / (end - start);
      progress = Math.min(Math.max(progress, 0), 1);

      $(this).css({
        transform: "translateX(" + -200 * (1 - progress) + "px)",
        opacity: progress,
      });
    });

    // Right to left animation
    $(".right-to-left-animate").each(function () {
      const elemTop = $(this).offset().top;
      const start = elemTop - windowHeight * 0.9;
      const end = elemTop - windowHeight * 0.3;

      let progress = (scrollTop - start) / (end - start);
      progress = Math.min(Math.max(progress, 0), 1);

      $(this).css({
        transform: `translateX(${200 * (1 - progress)}px)`, // 👈 positive value
        opacity: progress,
      });
    });
  });

  // Trigger scroll event on page load in case some elements are in view
  $(window).trigger("scroll");

  // megnific popup js
  $(".gallery_wrapper").magnificPopup({
    delegate: "a.img_wrap",
    type: "image",
    gallery: {
      enabled: true, // used as a gallery...
    },
    zoom: {
      enabled: true,
      duration: 300, // duration zoom animation...
    },
  });

  AOS.init({
  // Global settings:
  disable: false, // accepts following values: 'phone', 'tablet', 'mobile', boolean, expression or function
  startEvent: 'DOMContentLoaded', // name of the event dispatched on the document, that AOS should initialize on
  initClassName: 'aos-init', // class applied after initialization
  animatedClassName: 'aos-animate', // class applied on animation
  useClassNames: false, // if true, will add content of `data-aos` as classes on scroll
  disableMutationObserver: false, // disables automatic mutations' detections (advanced)
  debounceDelay: 50, // the delay on debounce used while resizing window (advanced)
  throttleDelay: 99, // the delay on throttle used while scrolling the page (advanced)
  

  // Settings that can be overridden on per-element basis, by `data-aos-*` attributes:
  offset: 300, // offset (in px) from the original trigger point
  delay: 0, // values from 0 to 3000, with step 50ms
  duration: 500, // values from 0 to 3000, with step 50ms
  easing: 'ease', // default easing for AOS animations
  once: false, // whether animation should happen only once - while scrolling down
  mirror: false, // whether elements should animate out while scrolling past them
  anchorPlacement: 'top-bottom', // defines which position of the element regarding to window should trigger the animation

});
});
