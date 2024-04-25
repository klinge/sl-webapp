//Code to show a modal
const memberModal = document.getElementById('editMemberModal');
const seglingModal = document.getElementById('editSeglingModal');

if (memberModal) {
  memberModal.addEventListener('show.bs.modal', event => {
    // Button that triggered the modal
    const button = event.relatedTarget;
    // Extract info from data-bs-* attributes
    const id = button.getAttribute('data-member-id');
    // If necessary, you could initiate an Ajax request here
    // and then do the updating in a callback.

    // Update the modal's content.
    const modalTitle = memberModal.querySelector('.modal-title');
    const modalBodyInput = memberModal.querySelector('.modal-body input');

    modalTitle.textContent = `Ändra medlem: ${id}`;
  })
}

if (seglingModal) {
  seglingModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-segling-id');
    // If necessary, you could initiate an Ajax request here
    // and then do the updating in a callback.
    const modalTitle = seglingModal.querySelector('.modal-title');
    const modalBodyInput = seglingModal.querySelector('.modal-body input');

    modalTitle.textContent = `Ändra segling: ${id}`;
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