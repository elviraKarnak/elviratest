$(document).ready(function () {
  var quoteSwiper = new Swiper(".quote_swiper", {
    loop: true,
    speed: 1000,
    spaceBetween: 20,
    navigation: {
      nextEl: ".swiper-button-next-custom",
      prevEl: ".swiper-button-prev-custom",
    },
  });
  var imgSwiper = new Swiper(".img_swiper", {
    loop: true,
    speed: 800, // smooth transition speed
    autoplay: {
      delay: 1200,
      disableOnInteraction: false,
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".custom_arrows .swiper-button-next-custom",
      prevEl: ".custom_arrows .swiper-button-prev-custom",
    },
  });
  $(".play").click(function () {
    $(".vid_play")[0].play();
    $(".vid_play").attr("controls", true);
    $(this).fadeOut();
  });
});
