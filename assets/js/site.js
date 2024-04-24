//Code to show a modal
const editModal = document.getElementById('editMemberModal');

if (editModal) {
  editModal.addEventListener('show.bs.modal', event => {
    // Button that triggered the modal
    const button = event.relatedTarget;
    // Extract info from data-bs-* attributes
    const id = button.getAttribute('data-member-id');
    // If necessary, you could initiate an Ajax request here
    // and then do the updating in a callback.

    // Update the modal's content.
    const modalTitle = editModal.querySelector('.modal-title');
    const modalBodyInput = editModal.querySelector('.modal-body input');

    modalTitle.textContent = `Ändra medlem: ${id}`;
  })
}
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