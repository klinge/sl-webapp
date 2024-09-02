//Code to toggle dark mode
document.getElementById('darkModeIcon').addEventListener('click', function () {
  this.classList.toggle('bi-moon');
  this.classList.toggle('bi-sun');
  let theme = document.documentElement.getAttribute('data-bs-theme');
  let nav = document.getElementById('slnav');
  if (theme === 'dark') {
    document.documentElement.removeAttribute('data-bs-theme');
    nav.style.color = "white";
  } else {
    document.documentElement.setAttribute('data-bs-theme', 'dark');
    nav.style.removeProperty('color');
  }
});