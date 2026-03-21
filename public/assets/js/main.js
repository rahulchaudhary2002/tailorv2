document.addEventListener("DOMContentLoaded", function () {
    const outletToggle = document.getElementById("outletToggle");
    const outletDropdown = document.getElementById("outletDropdown");
    const outletSelectorWrapper = document.querySelector(
        ".outlet-selector-wrapper",
    );
    const notificationToggle = document.getElementById("notificationToggle");
    const notificationsWrapper = document.querySelector(".notifications");
    const notificationDropdown = document.getElementById("notificationDropdown");

    if (outletToggle && outletDropdown) {
        outletToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            outletDropdown.classList.toggle("active");
        });
    }

    document.querySelectorAll(".outlet-item").forEach((item) => {
        item.addEventListener("click", function () {
            document
                .querySelectorAll(".outlet-item")
                .forEach((i) => i.classList.remove("active"));
            this.classList.add("active");
            if (outletDropdown) {
                outletDropdown.classList.remove("active");
            }
        });
    });

    if (notificationToggle && notificationsWrapper && notificationDropdown) {
        notificationToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            const isOpen = notificationDropdown.classList.toggle("active");
            notificationDropdown.style.display = isOpen ? "block" : "none";
        });

        notificationDropdown.addEventListener("click", function (e) {
            e.stopPropagation();
        });
    }

    document.addEventListener("click", function (e) {
        if (outletSelectorWrapper && !outletSelectorWrapper.contains(e.target)) {
            outletDropdown.classList.remove("active");
        }

        if (notificationsWrapper && !notificationsWrapper.contains(e.target)) {
            notificationDropdown?.classList.remove("active");
            if (notificationDropdown) {
                notificationDropdown.style.display = "none";
            }
        }
    });
});
