document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const outletToggle = document.getElementById("outletToggle");
    const outletDropdown = document.getElementById("outletDropdown");
    const outletSelectorWrapper = document.querySelector(
        ".outlet-selector-wrapper",
    );
    const notificationToggle = document.getElementById("notificationToggle");
    const notificationsWrapper = document.querySelector(".notifications");
    const notificationDropdown = document.getElementById("notificationDropdown");

    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            const isOpen = sidebar.classList.toggle("active");
            sidebarToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    }

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
        if (
            sidebar &&
            sidebarToggle &&
            window.innerWidth <= 1024 &&
            !sidebar.contains(e.target) &&
            !sidebarToggle.contains(e.target)
        ) {
            sidebar.classList.remove("active");
            sidebarToggle.setAttribute("aria-expanded", "false");
        }

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

    window.addEventListener("resize", function () {
        if (sidebar && sidebarToggle && window.innerWidth > 1024) {
            sidebar.classList.remove("active");
            sidebarToggle.setAttribute("aria-expanded", "false");
        }
    });
});
