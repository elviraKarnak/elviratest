document.addEventListener("DOMContentLoaded", function () {
  // console.log(
  //     "%c––––––––––––––––––––––––––\n%cDeisgned by Elvira\n%cDeveloper: Vikas Kumar\n%c––––––––––––––––––––––––––",
  //     "color:#999;",
  //     "color:#2E7DFF; font-size:14px; font-weight:600;",
  //     "color:#444; font-size:12px;",
  //     "color:#999;"
  // );

  document.querySelectorAll(".btn_grid a").forEach((link) => {
    link.addEventListener("click", () => {
      sessionStorage.setItem("selectedVideo", link.dataset.vid);
      sessionStorage.setItem("selectedText", link.dataset.txt);
    });
  });

  const videoSrc = sessionStorage.getItem("selectedVideo");
  const textSrc = sessionStorage.getItem("selectedText");

  if (videoSrc) {
    const video = document.getElementById("videoPlayer");
    if (video) {
      video.src = videoSrc;
      video.load();
      video.play();
    }
  }

  if (textSrc) {
    const textEl = document.getElementById("video_realated_txt");
    if (textEl) {
      textEl.innerHTML = textSrc;
    }
  }
});
