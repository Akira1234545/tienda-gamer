console.log("Sistema Gamer cargado correctamente");

document.querySelectorAll(".js-validate").forEach((form) => {
    form.addEventListener("submit", (event) => {
        const invalid = [...form.querySelectorAll("[required]")].find((field) => !field.value.trim());

        if (invalid) {
            event.preventDefault();
            invalid.focus();
            showInlineAlert(form, "Completa todos los campos obligatorios.");
            return;
        }

        const email = form.querySelector('input[type="email"]');

        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            event.preventDefault();
            email.focus();
            showInlineAlert(form, "Ingresa un correo valido.");
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

function showInlineAlert(form, message) {
    const previous = form.querySelector(".client-alert");

    if (previous) {
        previous.remove();
    }

    const alert = document.createElement("div");
    alert.className = "alert alert-warning client-alert";
    alert.textContent = message;
    form.prepend(alert);
}
