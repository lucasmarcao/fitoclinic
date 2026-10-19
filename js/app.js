const sidebar = document.getElementById("sidebar");
const menuBtn = document.getElementById("menuBtn");
const newAppointment = document.getElementById("newAppointment");
const appointmentForm = document.getElementById("appointmentForm");

menuBtn?.addEventListener("click", () => sidebar.classList.toggle("open"));
document
  .querySelectorAll(".nav-item")
  .forEach((item) =>
    item.addEventListener("click", () => sidebar.classList.remove("open")),
  );

appointmentForm?.addEventListener("submit", (e) => {
  e.preventDefault();
  closeModal();
  alert(
    "Agendamento criado. Nesta versão inicial, os dados ainda não são persistidos no servidor.",
  );
  appointmentForm.reset();
});

// Seleciona o span pelo novo id
const currentDateSpan = document.getElementById("currentDate");

// Cria uma data atual
const hoje = new Date();

// Formata em pt-BR com dia da semana, dia e mês
const options = {
  weekday: "long",
  day: "2-digit",
  month: "long",
  year: "numeric",
};
const dataFormatada = hoje.toLocaleDateString("pt-BR", options);

// Coloca no span (com primeira letra maiúscula)
currentDateSpan.textContent =
  dataFormatada.charAt(0).toUpperCase() + dataFormatada.slice(1);
