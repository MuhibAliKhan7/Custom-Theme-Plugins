
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    // Confirmation for deleting workflow drafts.
    document.querySelectorAll(".fpa-delete-form")
        .forEach(function (form) {
            form.addEventListener("submit", function (event) {
                const confirmed = window.confirm(
                    "Delete this workflow permanently?"
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        });

    // Visually indicate selected workflow actions.
    document.querySelectorAll(".fpa-action-option input")
        .forEach(function (checkbox) {

            function updateAppearance() {
                const label = checkbox.closest(
                    ".fpa-action-option"
                );

                if (!label) return;

                label.style.borderColor = checkbox.checked
                    ? "#a693f4"
                    : "#e8e6f3";

                label.style.background = checkbox.checked
                    ? "#f4f0ff"
                    : "#fcfbff";
            }

            checkbox.addEventListener(
                "change",
                updateAppearance
            );

            updateAppearance();
        });
});
    