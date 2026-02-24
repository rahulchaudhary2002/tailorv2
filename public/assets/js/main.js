document.addEventListener("DOMContentLoaded", function () {
    const outletToggle = document.getElementById("outletToggle");
    const outletDropdown = document.getElementById("outletDropdown");
    const outletSelectorWrapper = document.querySelector(
        ".outlet-selector-wrapper",
    );

    outletToggle.addEventListener("click", function (e) {
        e.stopPropagation();
        outletDropdown.classList.toggle("active");
    });

    document.querySelectorAll(".outlet-item").forEach((item) => {
        item.addEventListener("click", function () {
            document
                .querySelectorAll(".outlet-item")
                .forEach((i) => i.classList.remove("active"));
            this.classList.add("active");
            outletDropdown.classList.remove("active");
        });
    });

    document.addEventListener("click", function (e) {
        if (!outletSelectorWrapper.contains(e.target)) {
            outletDropdown.classList.remove("active");
        }
    });
});
