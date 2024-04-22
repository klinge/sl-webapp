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