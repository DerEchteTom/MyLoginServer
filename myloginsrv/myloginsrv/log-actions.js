document.addEventListener("click", function (event) {
    const btn = event.target.closest(".log-btn");
    if (!btn) return;

    const log = btn.dataset.log;
    const action = btn.dataset.action;

    const form = new FormData();
    form.append("log", log);
    form.append("action", action);

    fetch("admin_tab_logs.php", {
        method: "POST",
        body: form
    }).then(r => r.text())
      .then(msg => {
        const fb = document.getElementById("log-feedback");
        fb.classList.remove("d-none");
        fb.innerText = msg;
        if (typeof loadCurrentTab === "function") loadCurrentTab();
    });
});
