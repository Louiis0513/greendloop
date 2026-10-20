(function () {
    "use strict";

    var body = document.body;
    var toggle = document.getElementById("nav-toggle");
    var panel = document.getElementById("nav-panel");
    var overlay = document.getElementById("nav-overlay");

    function setNavOpen(open) {
        body.classList.toggle("nav-abierto", open);
        if (toggle) {
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
            toggle.setAttribute("aria-label", open ? "Cerrar menú" : "Abrir menú");
        }
    }

    function closeNav() {
        setNavOpen(false);
    }

    if (toggle && panel) {
        toggle.addEventListener("click", function () {
            setNavOpen(!body.classList.contains("nav-abierto"));
        });

        if (overlay) {
            overlay.addEventListener("click", closeNav);
        }

        panel.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", closeNav);
        });

        window.addEventListener("resize", function () {
            if (window.innerWidth > 760) {
                closeNav();
            }
        });
    }

    // --- Propuesta de trueque: elegir de 1 a 5 objetos propios ---
    var ofrecidoChecks = document.querySelectorAll("[data-ofrecido-check]");
    if (ofrecidoChecks.length > 0) {
        var MAX_OFRECIDOS = 5;
        var cuentaEl = document.querySelector("[data-ofrecidos-cuenta]");
        var resumenEl = document.querySelector("[data-ofrecidos-resumen]");
        var ayudaEl = document.querySelector("[data-ofrecidos-ayuda]");
        var enviarBtn = document.querySelector("[data-ofrecidos-enviar]");
        var ayudaOriginal = ayudaEl ? ayudaEl.textContent : "";

        function actualizarOfrecidos(cambiado) {
            var elegidos = document.querySelectorAll("[data-ofrecido-check]:checked");

            if (elegidos.length > MAX_OFRECIDOS) {
                if (cambiado) {
                    cambiado.checked = false;
                }
                elegidos = document.querySelectorAll("[data-ofrecido-check]:checked");
                if (ayudaEl) {
                    ayudaEl.textContent = "Máximo 5 objetos. Quita uno antes de elegir otro.";
                }
            } else if (ayudaEl) {
                ayudaEl.textContent = ayudaOriginal;
            }

            var n = elegidos.length;
            if (cuentaEl) {
                cuentaEl.textContent = n === 0
                    ? "Elige de 1 a 5 objetos"
                    : "Llevas " + n + " de 5 elegidos";
            }
            if (resumenEl) {
                resumenEl.textContent = n === 0
                    ? "Aún no elegiste ningún objeto."
                    : n === 1
                        ? "Ofreces 1 objeto. La otra persona lo acepta o rechaza."
                        : "Ofreces " + n + " objetos. La otra persona elegirá uno.";
            }
            if (enviarBtn) {
                enviarBtn.disabled = n === 0;
            }
        }

        ofrecidoChecks.forEach(function (check) {
            check.addEventListener("change", function () {
                actualizarOfrecidos(check);
            });
        });
        actualizarOfrecidos(null);
    }

    var modal = document.getElementById("modal-solicitud");
    if (!modal) {
        return;
    }

    var backdrop = modal.querySelector("[data-cerrar-modal]");
    var closeBtn = modal.querySelector(".modal-cerrar");
    var titleEl = document.getElementById("modal-titulo");
    var descEl = document.getElementById("modal-descripcion");
    var metaEl = document.getElementById("modal-meta");
    var autorEl = document.getElementById("modal-autor");
    var imgEl = document.getElementById("modal-imagen");
    var mediaEl = modal.querySelector(".modal-media");
    var idInput = document.getElementById("modal-id-producto");
    var mensajeInput = document.getElementById("modal-mensaje");
    var lastFocus = null;

    function openModal(trigger) {
        lastFocus = trigger;

        var nombre = trigger.getAttribute("data-nombre") || "";
        var descripcion = trigger.getAttribute("data-descripcion") || "";
        var categoria = trigger.getAttribute("data-categoria") || "";
        var tipo = trigger.getAttribute("data-tipo") || "";
        var autor = trigger.getAttribute("data-autor") || "";
        var imagen = trigger.getAttribute("data-imagen") || "";
        var id = trigger.getAttribute("data-id") || "";

        if (titleEl) {
            titleEl.textContent = nombre;
        }
        if (descEl) {
            descEl.textContent = descripcion;
        }
        if (metaEl) {
            metaEl.innerHTML =
                '<span class="badge">' + escapeHtml(categoria) + "</span>" +
                '<span class="badge badge-tipo es-regalo">' + escapeHtml(tipo) + "</span>";
        }
        if (autorEl) {
            autorEl.textContent = autor ? "Publicado por " + autor : "";
        }
        if (idInput) {
            idInput.value = id;
        }
        if (mensajeInput) {
            mensajeInput.value = "";
        }

        if (imgEl && mediaEl) {
            if (imagen) {
                imgEl.src = imagen;
                imgEl.alt = nombre;
                mediaEl.hidden = false;
            } else {
                imgEl.removeAttribute("src");
                mediaEl.hidden = true;
            }
        }

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        body.style.overflow = "hidden";

        window.setTimeout(function () {
            if (mensajeInput) {
                mensajeInput.focus();
            } else if (closeBtn) {
                closeBtn.focus();
            }
        }, 40);
    }

    function closeModal() {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        body.style.overflow = "";
        if (lastFocus) {
            lastFocus.focus();
        }
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    document.querySelectorAll("[data-abrir-solicitud]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            openModal(btn);
        });
    });

    if (backdrop) {
        backdrop.addEventListener("click", closeModal);
    }
    if (closeBtn) {
        closeBtn.addEventListener("click", closeModal);
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            if (modal.classList.contains("is-open")) {
                closeModal();
            }
            if (body.classList.contains("nav-abierto")) {
                closeNav();
            }
        }
    });
})();
