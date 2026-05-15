console.log("Sistema Gamer cargado correctamente");

document.querySelectorAll(".js-validate").forEach((form) => {
    form.querySelectorAll("input, select, textarea").forEach((field) => {
        field.addEventListener("input", () => validateField(field));
        field.addEventListener("change", () => validateField(field));
    });

    form.addEventListener("submit", (event) => {
        const invalid = [...form.querySelectorAll("input, select, textarea")].find((field) => !validateField(field));

        if (invalid) {
            event.preventDefault();
            invalid.focus();
            showInlineAlert(form, invalid.dataset.error || "Revisa los campos marcados.");
        }
    });
});

document.querySelectorAll(".js-confirm").forEach((form) => {
    form.addEventListener("submit", (event) => {
        const message = form.dataset.confirm || "Confirmar accion?";

        if (!confirm(message)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll(".js-auto-dismiss").forEach((alert) => {
    setTimeout(() => dismissAlert(alert), 5000);
});

function showInlineAlert(form, message) {
    const previous = form.querySelector(".client-alert");

    if (previous) {
        previous.remove();
    }

    const alert = document.createElement("div");
    alert.className = "alert alert-warning client-alert";
    alert.textContent = message;
    form.prepend(alert);
    setTimeout(() => alert.remove(), 5000);
}

function dismissAlert(alert) {
    if (!alert.isConnected) {
        return;
    }

    if (window.bootstrap?.Alert) {
        bootstrap.Alert.getOrCreateInstance(alert).close();
        return;
    }

    alert.classList.remove("show");
    setTimeout(() => alert.remove(), 180);
}

function validateField(field) {
    if (field.disabled || field.type === "hidden" || field.type === "file") {
        return true;
    }

    let valid = true;
    let message = "";
    const value = field.value.trim();

    if (field.required && !value) {
        valid = false;
        message = "Completa este campo.";
    }

    if (valid && field.type === "email" && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        valid = false;
        message = "Ingresa un correo valido.";
    }

    if (valid && field.type === "number" && value) {
        const numberValue = Number(value);
        const min = field.min !== "" ? Number(field.min) : null;
        const max = field.max !== "" ? Number(field.max) : null;

        if (Number.isNaN(numberValue) || (min !== null && numberValue < min)) {
            valid = false;
            message = min !== null ? `El valor minimo es ${field.min}.` : "Ingresa un numero valido.";
        }

        if (valid && max !== null && numberValue > max) {
            valid = false;
            message = `El valor maximo es ${field.max}.`;
        }
    }

    if (valid && field.minLength > 0 && value && value.length < field.minLength) {
        valid = false;
        message = `Debe tener al menos ${field.minLength} caracteres.`;
    }

    if (valid && field.name === "precio" && Number(value) <= 0) {
        valid = false;
        message = "El precio debe ser mayor a 0.";
    }

    if (valid && field.name === "stock" && Number(value) < 0) {
        valid = false;
        message = "El stock no puede ser negativo.";
    }

    field.classList.toggle("is-invalid", !valid);
    field.classList.toggle("is-valid", valid && value !== "");
    field.dataset.error = message;
    showFieldFeedback(field, valid, message);

    return valid;
}

function showFieldFeedback(field, valid, message) {
    const existing = field.parentElement.querySelector(`.invalid-feedback[data-for="${field.name}"]`);

    if (valid) {
        existing?.remove();
        return;
    }

    if (existing) {
        existing.textContent = message;
        return;
    }

    const feedback = document.createElement("div");
    feedback.className = "invalid-feedback d-block";
    feedback.dataset.for = field.name;
    feedback.textContent = message;
    field.insertAdjacentElement("afterend", feedback);
}

document.querySelectorAll("[data-ajax-filter]").forEach((form) => {
    const target = document.querySelector(form.dataset.target);

    if (!target) {
        return;
    }

    let timer = null;
    const runSearch = () => {
        const url = `${form.dataset.ajaxFilter}?${new URLSearchParams(new FormData(form)).toString()}`;
        target.classList.add("opacity-50");

        fetch(url, { headers: { "X-Requested-With": "fetch" } })
            .then((response) => response.text())
            .then((html) => {
                target.innerHTML = html;
            })
            .catch(() => {
                showInlineAlert(form, "No se pudo actualizar la busqueda.");
            })
            .finally(() => {
                target.classList.remove("opacity-50");
            });
    };

    const debouncedSearch = () => {
        clearTimeout(timer);
        timer = setTimeout(runSearch, 350);
    };

    form.addEventListener("input", debouncedSearch);
    form.addEventListener("change", debouncedSearch);
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        runSearch();
    });
});
