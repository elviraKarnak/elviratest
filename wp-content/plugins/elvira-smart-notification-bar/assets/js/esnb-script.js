document.addEventListener("DOMContentLoaded", function () {
  const bars = document.querySelectorAll('#esnb-bar, [id^="esnb-bar-"]');
  bars.forEach((bar) => {
    if (!bar.id) {
      // give bar an id if missing so dismiss memory works per-bar
      bar.id = "esnb-bar-" + Math.random().toString(36).substring(2, 9);
    }
    if (localStorage.getItem(bar.id + "_dismissed")) return;
    bar.style.display = "block";

    const close =
      bar.querySelector(".esnb-close") || document.getElementById("esnb-close");
    if (close) {
      close.addEventListener("click", () => {
        bar.style.display = "none";
        if (bar.id) localStorage.setItem(bar.id + "_dismissed", "1");
      });
    }

    const countdown = bar.dataset.countdown;
    console.log(countdown);
    if (countdown) {
      const timerEl = bar.querySelector(".esnb-timer");
      const endTime = parseInt(countdown, 10);
      if (!timerEl || !endTime) return;
      const update = () => {
        const now = Math.floor(Date.now() / 1000);
        const diff = endTime - now;
        if (diff <= 0) {
          bar.style.display = "none";
          clearInterval(iv);
          return;
        }
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        timerEl.textContent = h + "h " + m + "m " + s + "s";
      };
      update();
      const iv = setInterval(update, 1000);
    }
  });
});
