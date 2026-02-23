document.addEventListener("DOMContentLoaded", function () {
  // click on filter btn
  let filterBtn = document.querySelector(".filter_btn");
  let filterOpn = document.querySelector(".filter_wrapper .filer_option");

  filterBtn?.addEventListener("click", function (e) {
    e.stopPropagation();
    filterOpn.classList.add("active");
  });

  filterOpn?.addEventListener("click", function (e) {
    e.stopPropagation();
  });

  document.addEventListener("click", function () {
    filterOpn?.classList.remove("active");
  });

  // click on chevron
  let chevrons = document.querySelectorAll(".down_chevron");
  let subLists = document.querySelectorAll(".sub_list_wrap");

  chevrons.forEach((chevron) => {
    chevron.addEventListener("click", function (e) {
      e.stopPropagation();

      const subList = this.nextElementSibling;
      subLists.forEach((list) => {
        if (list !== subList) list.classList.remove("active");
      });

      if (subList && subList.classList.contains("sub_list_wrap")) {
        subList.classList.toggle("active");
      }
    });
  });

  subLists.forEach((list) => {
    list.addEventListener("click", function (e) {
      e.stopPropagation();
    });
  });

  document.addEventListener("click", function () {
    subLists.forEach((list) => list.classList.remove("active"));
  });

  // popup js
  const popup = document.querySelector(".custom_popup");
  const closeBtn = document.querySelectorAll(".cross_btn");
  const openPopup = document.querySelectorAll(".open_popup");

  openPopup.forEach((opem_pop) => {
    opem_pop.addEventListener("click", function () {
      popup.classList.add("active");
    });
  });

  closeBtn.forEach((clbtn) => {
    clbtn.addEventListener("click", function () {
      popup.classList.remove("active");
    });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      const popup = document.querySelector(".custom_popup");
      const advanceFilterOpn = document.querySelector(
        ".filter_wrapper .filer_option"
      );
      if (popup) popup.classList.remove("active");
      if (advanceFilterOpn) advanceFilterOpn.classList.remove("active");
    }
  });

  // table tab js
  const tableTab = document.querySelectorAll(".table_tab .tab_btn");
  tableTab.forEach((elem) => {
    elem.addEventListener("click", function () {
      tableTab.forEach((tab) => tab.classList.remove("active"));

      this.classList.add("active");
      let dataTab = this.getAttribute("data-tab");
      // alert(dataTab);

      let dataContent = document.querySelector("#" + dataTab);
      let dataContentActive = document.querySelectorAll(
        ".record_table .table_wrap"
      );
      dataContentActive.forEach((el) => {
        el.classList.remove("active");
      });
      // alert(dataContent);
      dataContent.classList.add("active");
    });
  });

  // basic image slider js
  const slider = document.querySelector(".basic_slider .slider_wrapper");
  const slides = slider?.querySelectorAll(".basic_slider .slider_wrapper > *");
  const nextBtn = document.querySelector(".next_btn");

  let currentIndex = 0;
  const totalSlides = slides?.length;

  if (totalSlides <= 1) {
    nextBtn.style.display = "none";
  }

  nextBtn?.addEventListener("click", () => {
    if (currentIndex < totalSlides - 1) {
      currentIndex++;
      slider.style.transform = `translateX(-${currentIndex * 100}%)`;
    }

    // Disable or hide button on last slide
    if (currentIndex === totalSlides - 1) {
      nextBtn.classList.add("disabled");
    }
  });

});
