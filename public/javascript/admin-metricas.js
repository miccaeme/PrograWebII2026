document.addEventListener("DOMContentLoaded", () => {
    const parseAttr = (el, attr) => JSON.parse(el.getAttribute(attr) || "[]");

    const ctxPartidas = document.getElementById("chartPartidas");
    if (ctxPartidas) {
        new Chart(ctxPartidas, {
            type: "bar",
            data: {
                labels: parseAttr(ctxPartidas, "data-labels"),
                datasets: [{
                    label: "Partidas finalizadas",
                    data: parseAttr(ctxPartidas, "data-values"),
                    backgroundColor: "#6655d9",
                    borderRadius: 8
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }

    const ctxJugadores = document.getElementById("chartJugadores");
    if (ctxJugadores) {
        new Chart(ctxJugadores, {
            type: "line",
            data: {
                labels: parseAttr(ctxJugadores, "data-labels"),
                datasets: [{
                    label: "Jugadores registrados",
                    data: parseAttr(ctxJugadores, "data-values"),
                    borderColor: "#10b981",
                    backgroundColor: "rgba(16, 185, 129, 0.15)",
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }

    const ctxCategorias = document.getElementById("chartCategorias");
    if (ctxCategorias) {
        new Chart(ctxCategorias, {
            type: "doughnut",
            data: {
                labels: parseAttr(ctxCategorias, "data-labels"),
                datasets: [{
                    data: parseAttr(ctxCategorias, "data-values"),
                    backgroundColor: parseAttr(ctxCategorias, "data-colors")
                }]
            },
            options: { responsive: true }
        });
    }
});