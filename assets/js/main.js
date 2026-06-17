// assets/js/main.js
function toggleSidebar() {
  document.body.classList.toggle("sb-collapsed");
}
function toggleTheme() {
  const html = document.documentElement;
  const isDark = html.getAttribute("data-theme") === "dark";
  if (isDark) {
    html.removeAttribute("data-theme");
    localStorage.setItem("theme", "light");
  } else {
    html.setAttribute("data-theme", "dark");
    localStorage.setItem("theme", "dark");
  }
  updateThemeIcon();
}
function updateThemeIcon() {
  const icon = document.getElementById("themeToggleIcon");
  if (!icon) return;
  const isDark = document.documentElement.getAttribute("data-theme") === "dark";
  icon.className = isDark ? "fas fa-sun" : "fas fa-moon";
}
document.addEventListener("DOMContentLoaded", updateThemeIcon);
function toggleDropdown() {
  document.getElementById("userDropdown").classList.toggle("open");
}
function closeModal(id) {
  document.getElementById(id).classList.remove("open");
}
// Close dropdown on outside click
document.addEventListener("click", (e) => {
  if (!e.target.closest(".user-dropdown")) {
    document.getElementById("userDropdown")?.classList.remove("open");
  }
});
// Close modal on overlay click
document.querySelectorAll(".modal-overlay").forEach((o) => {
  o.addEventListener("click", (e) => {
    if (e.target === o) o.classList.remove("open");
  });
});
