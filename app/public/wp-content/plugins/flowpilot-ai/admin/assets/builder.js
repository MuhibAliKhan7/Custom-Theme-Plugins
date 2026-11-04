
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const app = document.getElementById("fpa-builder-app");
    if (!app) return;

    const workflowId = app.dataset.workflowId;
    const nodesEl = document.getElementById("fpa-nodes");
    const edgesEl = document.getElementById("fpa-edges");
    const canvas = document.getElementById("fpa-canvas");
    const viewport = document.getElementById("fpa-viewport");
    const message = document.getElementById("fpa-message");
    const labelInput = document.getElementById("fpa-node-label");

    const settings = window.FPA_BUILDER;

    let graph = { nodes: [], edges: [] };
    let selected = null;
    let connecting = false;
    let connectionStart = null;
    let dragging = null;
    let saving = false;
    let dirty = false;

    const labels = {
        manual: "Manual Trigger",
        form_submitted: "Form Submitted",
        ai_qualify: "AI Qualification",
        create_crm_lead: "Create CRM Lead",
        send_email: "Send Email",
        notify_admin: "Notify Administrator",
        webhook: "Webhook Action"
    };

    const colors = {
        trigger: "#A491FF",
        action: "#58D5C4"
    };

    function notify(text, error = false) {
        message.textContent = text;
        message.classList.toggle("is-error", error);
    }

    function markDirty() {
        dirty = true;
        notify("Unsaved changes");
    }

    function selectedNode() {
        return graph.nodes.find(n => n.id === selected);
    }

    function nodeElement(id) {
        return [...nodesEl.children]
            .find(el => el.dataset.id === id);
    }

    function positionNode(el, node) {
        el.style.left = node.x + "px";
        el.style.top = node.y + "px";
    }

    function drawEdges() {
        edgesEl.innerHTML = "";

        // Make the SVG cover the active graph.
        const maxX = Math.max(
            1600,
            ...graph.nodes.map(n => n.x + 300)
        );
        const maxY = Math.max(
            900,
            ...graph.nodes.map(n => n.y + 180)
        );

        canvas.style.width = maxX + "px";
        canvas.style.height = maxY + "px";

        edgesEl.setAttribute("width", maxX);
        edgesEl.setAttribute("height", maxY);
        edgesEl.setAttribute(
            "viewBox",
            `0 0 ${maxX} ${maxY}`
        );

        const ns = "http://www.w3.org/2000/svg";

        for (const edge of graph.edges) {
            const from = graph.nodes.find(
                n => n.id === edge.from
            );
            const to = graph.nodes.find(
                n => n.id === edge.to
            );

            if (!from || !to) continue;

            const x1 = from.x + 240;
            const y1 = from.y + 46;
            const x2 = to.x;
            const y2 = to.y + 46;
            const bend = Math.max(
                60,
                Math.abs(x2 - x1) / 2
            );

            const path = document.createElementNS(ns, "path");

            path.setAttribute(
                "d",
                `M ${x1} ${y1}
                 C ${x1 + bend} ${y1},
                   ${x2 - bend} ${y2},
                   ${x2} ${y2}`
            );

            path.setAttribute("fill", "none");
            path.setAttribute("stroke", "#9B8BEE");
            path.setAttribute("stroke-width", "3");
            path.setAttribute("stroke-linecap", "round");

            const title = document.createElementNS(ns, "title");
            title.textContent = "Click to remove connection";
            path.appendChild(title);

            path.style.cursor = "pointer";
            path.style.pointerEvents = "stroke";

            path.addEventListener("click", () => {
                if (!window.confirm("Remove this connection?")) {
                    return;
                }

                graph.edges = graph.edges.filter(
                    e => e !== edge
                );

                markDirty();
                drawEdges();
            });

            edgesEl.appendChild(path);
        }
    }

    function selectNode(id) {
        if (connecting) {
            if (!connectionStart) {
                connectionStart = id;
                notify("Now select the target action node.");
                return;
            }

            connectNodes(connectionStart, id);
            connectionStart = null;
            connecting = false;
            document.getElementById("fpa-connect")
                .classList.remove("is-active");
            return;
        }

        selected = id;

        for (const element of nodesEl.children) {
            element.classList.toggle(
                "selected",
                element.dataset.id === id
            );
        }

        const node = selectedNode();
        labelInput.disabled = !node;
        labelInput.value = node ? node.label : "";

        notify(node
            ? `Selected: ${node.label}`
            : "Select a node");
    }

    function hasPath(start, target) {
        const seen = new Set();
        const stack = [start];

        while (stack.length) {
            const current = stack.pop();

            if (current === target) return true;
            if (seen.has(current)) continue;

            seen.add(current);

            graph.edges
                .filter(e => e.from === current)
                .forEach(e => stack.push(e.to));
        }

        return false;
    }

    function connectNodes(from, to) {
        const source = graph.nodes.find(n => n.id === from);
        const target = graph.nodes.find(n => n.id === to);

        if (!source || !target || from === to) {
            notify("Select two different nodes.", true);
            return;
        }

        if (target.kind === "trigger") {
            notify("A trigger cannot be a connection target.", true);
            return;
        }

        if (graph.edges.some(
            e => e.from === from && e.to === to
        )) {
            notify("These nodes are already connected.", true);
            return;
        }

        if (hasPath(to, from)) {
            notify("This connection would create a cycle.", true);
            return;
        }

        if (graph.edges.length >= 120) {
            notify("Maximum 120 connections.", true);
            return;
        }

        graph.edges.push({ from, to });
        markDirty();
        drawEdges();
        notify("Nodes connected. Save to keep changes.");
    }

    function renderNodes() {
        nodesEl.innerHTML = "";

        for (const node of graph.nodes) {
            const el = document.createElement("div");

            el.className = "fpa-graph-node";
            el.dataset.id = node.id;
            el.tabIndex = 0;
            el.setAttribute("role", "button");
            el.setAttribute("aria-label", node.label);

            if (node.id === selected) {
                el.classList.add("selected");
            }

            const tag = document.createElement("span");
            tag.className = "fpa-graph-tag";
            tag.textContent = node.kind === "trigger"
                ? "TRIGGER"
                : "ACTION";
            tag.style.color = colors[node.kind];

            const title = document.createElement("strong");
            title.textContent = node.label;

            const type = document.createElement("small");
            type.textContent = node.type.replaceAll("_", " ");

            const portIn = document.createElement("span");
            portIn.className = "fpa-port fpa-port-in";

            const portOut = document.createElement("span");
            portOut.className = "fpa-port fpa-port-out";

            el.append(tag, title, type, portIn, portOut);
            positionNode(el, node);

            el.addEventListener("click", () => {
                selectNode(node.id);
            });

            el.addEventListener("keydown", event => {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    selectNode(node.id);
                }
            });

            el.addEventListener("pointerdown", event => {
                if (event.button !== 0 || connecting) return;

                selected = node.id;
                selectNode(node.id);

                const startX = event.clientX;
                const startY = event.clientY;
                const oldX = node.x;
                const oldY = node.y;

                dragging = {
                    id: node.id,
                    startX,
                    startY,
                    oldX,
                    oldY,
                    moved: false
                };

                el.setPointerCapture(event.pointerId);
            });

            el.addEventListener("pointermove", event => {
                if (!dragging || dragging.id !== node.id) {
                    return;
                }

                const dx = event.clientX - dragging.startX;
                const dy = event.clientY - dragging.startY;

                if (Math.abs(dx) + Math.abs(dy) > 3) {
                    dragging.moved = true;
                }

                node.x = Math.max(
                    0,
                    Math.min(12000, dragging.oldX + dx)
                );
                node.y = Math.max(
                    0,
                    Math.min(12000, dragging.oldY + dy)
                );

                positionNode(el, node);
                drawEdges();
            });

            const finishDrag = () => {
                if (dragging && dragging.id === node.id) {
                    if (dragging.moved) markDirty();
                    dragging = null;
                }
            };

            el.addEventListener("pointerup", finishDrag);
            el.addEventListener("pointercancel", finishDrag);

            nodesEl.appendChild(el);
        }

        drawEdges();
    }

    function addNode(kind, type) {
        if (graph.nodes.length >= 60) {
            notify("Maximum 60 nodes.", true);
            return;
        }

        if (kind === "trigger" &&
            graph.nodes.some(n => n.kind === "trigger")) {
            notify("Only one trigger is supported.", true);
            return;
        }

        const id = "node-" +
            Date.now().toString(36) + "-" +
            Math.random().toString(36).slice(2, 10);

        const node = {
            id,
            kind,
            type,
            label: labels[type] || type,
            x: Math.max(20, viewport.scrollLeft + 100),
            y: Math.max(20, viewport.scrollTop + 150)
        };

        graph.nodes.push(node);
        selected = id;

        markDirty();
        renderNodes();
        selectNode(id);
    }

    function deleteSelected() {
        if (!selected) {
            notify("Select a node first.", true);
            return;
        }

        graph.nodes = graph.nodes.filter(
            node => node.id !== selected
        );

        graph.edges = graph.edges.filter(
            edge => edge.from !== selected &&
                    edge.to !== selected
        );

        selected = null;
        labelInput.value = "";
        labelInput.disabled = true;

        markDirty();
        renderNodes();
    }

    async function loadGraph() {
        notify("Loading workflow...");

        try {
            const response = await fetch(
                settings.api + encodeURIComponent(workflowId),
                {
                    credentials: "same-origin",
                    headers: {
                        "X-WP-Nonce": settings.nonce
                    }
                }
            );

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || "Load failed");
            }

            graph = {
                nodes: Array.isArray(result.nodes)
                    ? result.nodes
                    : [],
                edges: Array.isArray(result.edges)
                    ? result.edges
                    : []
            };

            selected = null;
            dirty = false;
            renderNodes();
            notify("Workflow loaded. Ready to edit.");

        } catch (error) {
            notify(error.message || "Could not load graph.", true);
        }
    }

    async function saveGraph() {
        if (saving) return;

        saving = true;
        notify("Saving workflow...");

        try {
            const response = await fetch(
                settings.api + encodeURIComponent(workflowId),
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "X-WP-Nonce": settings.nonce
                    },
                    body: JSON.stringify(graph)
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || "Save failed");
            }

            dirty = false;
            notify("Workflow graph saved successfully.");

        } catch (error) {
            notify(error.message || "Save failed.", true);
        } finally {
            saving = false;
        }
    }

    document.querySelectorAll("[data-add-node]")
        .forEach(button => {
            button.addEventListener("click", () => {
                addNode(
                    button.dataset.addNode,
                    button.dataset.nodeType
                );
            });
        });

    document.getElementById("fpa-connect")
        .addEventListener("click", function () {
            connecting = !connecting;
            connectionStart = null;
            this.classList.toggle("is-active", connecting);

            notify(connecting
                ? "Select the source node, then the target node."
                : "Connection mode cancelled.");
        });

    document.getElementById("fpa-delete-node")
        .addEventListener("click", deleteSelected);

    document.getElementById("fpa-save")
        .addEventListener("click", saveGraph);

    document.getElementById("fpa-reset-view")
        .addEventListener("click", () => {
            viewport.scrollTo({ left: 0, top: 0 });
        });

    labelInput.addEventListener("input", () => {
        const node = selectedNode();

        if (!node) return;

        node.label = labelInput.value.slice(0, 100);

        const el = nodeElement(node.id);

        if (el) {
            el.querySelector("strong").textContent = node.label;
            el.setAttribute("aria-label", node.label);
        }

        markDirty();
    });

    window.addEventListener("beforeunload", event => {
        if (dirty) {
            event.preventDefault();
            event.returnValue = "";
        }
    });

    loadGraph();
});
