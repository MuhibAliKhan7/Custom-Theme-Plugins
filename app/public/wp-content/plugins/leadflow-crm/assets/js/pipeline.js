
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const board = document.getElementById(
        "wplf-kanban-board"
    );

    if (!board) return;

    const message = document.getElementById(
        "wplf-pipeline-message"
    );

    let draggedCard = null;
    let isSaving = false;

    function showMessage(text, isError = false) {
        if (!message) return;

        message.textContent = text;
        message.classList.add("is-visible");
        message.classList.toggle("is-error", isError);
    }

    function getColumn(status) {
        return Array.from(
            board.querySelectorAll(".wplf-kanban-column")
        ).find(function (column) {
            return column.dataset.status === status;
        });
    }

    function updateCounts() {
        board.querySelectorAll(".wplf-kanban-column")
            .forEach(function (column) {
                const count = column.querySelector(
                    ".wplf-stage-count"
                );

                const cards = column.querySelectorAll(
                    ".wplf-kanban-card"
                );

                if (count) {
                    count.textContent = String(cards.length);
                }
            });
    }

    async function updateLeadStage(card, status) {
        if (isSaving) return;

        const id = card.dataset.leadId;
        const previous = card.dataset.status;

        if (!id || previous === status) {
            return;
        }

        const originalColumn = getColumn(previous);
        const targetColumn = getColumn(status);

        if (!originalColumn || !targetColumn) {
            return;
        }

        const targetZone = targetColumn.querySelector(
            ".wplf-kanban-dropzone"
        );

        const originalZone = originalColumn.querySelector(
            ".wplf-kanban-dropzone"
        );

        const originalNextSibling = card.nextElementSibling;

        isSaving = true;
        card.classList.add("is-saving");

        // Optimistic UI update.
        targetZone.appendChild(card);
        updateCounts();

        try {
            const response = await fetch(
                wplfPipelineSettings.restUrl +
                encodeURIComponent(id) +
                "/status",
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "X-WP-Nonce": wplfPipelineSettings.nonce
                    },
                    body: JSON.stringify({
                        status: status
                    })
                }
            );

            const rawResponse = await response.text();

let result;

try {
    result = JSON.parse(rawResponse);
} catch (error) {
    throw new Error(
        "The server returned an invalid response. " +
        "Check the WordPress debug.log file."
    );
}

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message ||
                    "Unable to save lead stage."
                );
            }

            card.dataset.status = status;

            const select = card.querySelector(
                'select[name="status"]'
            );

            if (select) {
                select.value = status;
            }

            showMessage(
                "Lead moved successfully. Changes saved."
            );

        } catch (error) {
            // Revert the card if the server rejects the update.
            if (
                originalNextSibling &&
                originalNextSibling.parentNode === originalZone
            ) {
                originalZone.insertBefore(
                    card,
                    originalNextSibling
                );
            } else {
                originalZone.appendChild(card);
            }

            updateCounts();

            showMessage(
                error.message || "Could not save changes.",
                true
            );

        } finally {
            card.classList.remove("is-saving");
            isSaving = false;
        }
    }

    board.querySelectorAll(".wplf-kanban-card")
        .forEach(function (card) {

            card.addEventListener("dragstart", function (event) {
                if (isSaving) {
                    event.preventDefault();
                    return;
                }

                draggedCard = card;

                event.dataTransfer.effectAllowed = "move";
                event.dataTransfer.setData(
                    "text/plain",
                    card.dataset.leadId
                );

                card.classList.add("is-dragging");
            });

            card.addEventListener("dragend", function () {
                card.classList.remove("is-dragging");
                draggedCard = null;

                board.querySelectorAll(
                    ".wplf-kanban-dropzone"
                ).forEach(function (zone) {
                    zone.classList.remove("drag-over");
                });
            });
        });

    board.querySelectorAll(".wplf-kanban-dropzone")
        .forEach(function (zone) {

            zone.addEventListener("dragover", function (event) {
                if (!draggedCard || isSaving) return;

                event.preventDefault();
                event.dataTransfer.dropEffect = "move";
                zone.classList.add("drag-over");
            });

            zone.addEventListener("dragleave", function (event) {
                if (!zone.contains(event.relatedTarget)) {
                    zone.classList.remove("drag-over");
                }
            });

            zone.addEventListener("drop", function (event) {
                event.preventDefault();
                zone.classList.remove("drag-over");

                if (!draggedCard || isSaving) return;

                updateLeadStage(
                    draggedCard,
                    zone.dataset.dropzone
                );
            });
        });

    updateCounts();
});
