
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const drawer = document.getElementById("wplf-lead-drawer");
    const overlay = document.getElementById("wplf-drawer-overlay");
    const openButtons = document.querySelectorAll("[data-wplf-open]");
    const closeButtons = document.querySelectorAll("[data-wplf-close]");

    let lastFocusedElement = null;

    function openDrawer() {
        if (!drawer || !overlay) return;

        lastFocusedElement = document.activeElement;

        drawer.inert = false;
        drawer.setAttribute("aria-hidden", "false");

        drawer.classList.add("is-open");
        overlay.classList.add("is-open");

        document.body.classList.add("wplf-drawer-open");

        const firstInput = drawer.querySelector('input[name="name"]');

        if (firstInput) {
            window.setTimeout(function () {
                firstInput.focus();
            }, 200);
        }
    }

    function closeDrawer() {
        if (!drawer || !overlay) return;

        drawer.classList.remove("is-open");
        overlay.classList.remove("is-open");

        document.body.classList.remove("wplf-drawer-open");

        if (lastFocusedElement &&
            lastFocusedElement.isConnected) {
            lastFocusedElement.focus();
        }

        drawer.setAttribute("aria-hidden", "true");
        drawer.inert = true;

        // Clear edit mode so opening Add Lead
        // does not accidentally update an existing record.
        if (drawer.dataset.editing === "1") {
            const url = new URL(window.location.href);
            url.searchParams.delete("edit");

            window.location.assign(url.toString());
        }
    }

    openButtons.forEach(function (button) {
        button.addEventListener("click", openDrawer);
    });

    closeButtons.forEach(function (button) {
        button.addEventListener("click", closeDrawer);
    });

    if (overlay) {
        overlay.addEventListener("click", closeDrawer);
    }

    // Open the editor automatically when editing a lead.
    if (drawer && drawer.dataset.editing === "1") {
        openDrawer();
    }

    // Support opening the form from the dashboard link.
    if (window.location.hash === "#wplf-form") {
        openDrawer();
    }

    // Keyboard support.
    document.addEventListener("keydown", function (event) {
        if (!drawer || !drawer.classList.contains("is-open")) {
            return;
        }

        if (event.key === "Escape") {
            closeDrawer();
            return;
        }

        if (event.key === "Tab") {
            const focusable = Array.from(
                drawer.querySelectorAll(
                    'button:not([disabled]), ' +
                    'input:not([disabled]), ' +
                    'select:not([disabled]), ' +
                    'textarea:not([disabled]), ' +
                    'a[href]'
                )
            ).filter(function (element) {
                return element.getClientRects().length > 0;
            });

            if (!focusable.length) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey &&
                document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey &&
                       document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    // Confirmation before permanently deleting a lead.
    document.querySelectorAll(".wplf-delete-form")
        .forEach(function (form) {
            form.addEventListener("submit", function (event) {
                if (!window.confirm(
                    "Permanently delete this lead? This cannot be undone."
                )) {
                    event.preventDefault();
                }
            });
        });

    // Make the dashboard conversion ring reflect real data.
    const conversionRing = document.querySelector(
        ".wplf-big-circle"
    );

    if (conversionRing) {
        const value = conversionRing.querySelector("strong");
        const percentage = value
            ? Math.max(0, Math.min(100, parseFloat(value.textContent) || 0))
            : 0;

        conversionRing.style.background =
            "radial-gradient(closest-side, white 78%, transparent 80%), " +
            "conic-gradient(#7661e9 0% " + percentage + "%, " +
            "#eeecfa " + percentage + "% 100%)";
    }
});
