const sidebar = document.getElementById("sidebar");
const menuBtn = document.getElementById("menuBtn");
const newAppointment = document.getElementById("newAppointment");
const appointmentForm = document.getElementById("appointmentForm");
const sidebarClose = document.getElementById("sidebarClose");

function openSidebar() {
    sidebar?.classList.add("open");
    document.body.classList.add("sidebar-open");
}

function closeSidebar() {
    sidebar?.classList.remove("open");
    document.body.classList.remove("sidebar-open");
}

menuBtn?.addEventListener("click", openSidebar);
sidebarClose?.addEventListener("click", closeSidebar);

// Fecha ao clicar em qualquer item do menu (mobile) ou navegar
document
    .querySelectorAll(".nav-item")
    .forEach((item) => item.addEventListener("click", closeSidebar));

// Fecha com ESC
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && sidebar?.classList.contains("open")) {
        closeSidebar();
    }
});

// menuBtn?.addEventListener("click", () => sidebar.classList.toggle("open"));
// document
//     .querySelectorAll(".nav-item")
//     .forEach((item) =>
//         item.addEventListener("click", () => sidebar.classList.remove("open")),
//     );

// appointmentForm?.addEventListener("submit", (e) => {
//     e.preventDefault();
//     closeModal();
//     alert(
//         "Agendamento criado. Nesta versão inicial, os dados ainda não são persistidos no servidor.",
//     );
//     appointmentForm.reset();
// });

const currentDateSpan = document.getElementById("currentDate");
if (currentDateSpan) {
    const hoje = new Date();
    const options = {
        weekday: "long",
        day: "2-digit",
        month: "long",
        year: "numeric",
    };
    const dataFormatada = hoje.toLocaleDateString("pt-BR", options);
    currentDateSpan.textContent =
        dataFormatada.charAt(0).toUpperCase() + dataFormatada.slice(1);
}
