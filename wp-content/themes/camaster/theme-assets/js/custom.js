$(document).ready(function () {
  $(".mobile-toggle").click(function () {
    $(".mobile-toggle").toggleClass("menu-open");
    $("ul").toggleClass("active");
  });
});

// $(document).ready(function () {
//   $(window).scroll(function () {
//     if ($(this).scrollTop() > 10) {
//       $("#header-section").addClass("sticky");
//     } else {
//       $("#header-section").removeClass("sticky");
//     }
//   });
// });

//active pagination
$(document).ready(function(){
  $('.pagination li a').click(function(event){
    $('.active-tab').removeClass('active-tab');
      $(this).addClass('active-tab');

  });
});


