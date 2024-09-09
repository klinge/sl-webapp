<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">Lägg till deltagare:</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add your form for new payment here -->
                <form id="addMemberForm">
                    <input type="hidden" name="segling_id" value="<?= $segling->id ?>">
                    <div class="mb-3">
                        <label for="roll-select" class="form-label">Roll</label>
                        <select class="form-select" id="roll-select" aria-label="Välj en roll">
                            <option value="0" selected>Alla medlemmar</option>
                            <?php foreach ($roller as $roll) : ?>
                                <option value="<?php echo $roll['id'] ?>"><?php echo $roll['roll_namn'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="namn-select" class="form-label">Namn</label>
                        <select class="form-select" id="namn-select" aria-label="Välj person">
                            <option selected>Välj person</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Stäng</button>
                <button type="button" class="btn btn-primary" onclick="submitPayment()">Spara</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('roll-select').addEventListener('change', function() {
        var rollId = this.value;
        if (rollId == 0) {
            getAllMedlemmar();
            return;
        } else {
            getMedlemmarWithRole(rollId);
        }
    });

    function getAllMedlemmar() {
        fetch(`/sl-webapp/medlem/json`)
            .then(response => response.json())
            .then(medlemmar => {
                updateNamnSelect(medlemmar);
            })
            .catch(error => console.error('Error fetching medlemmar:', error));
    }

    function getMedlemmarWithRole(rollId) {
        fetch(`/sl-webapp/roller/${rollId}/medlem`)
            .then(response => response.json())
            .then(medlemmar => {
                updateNamnSelect(medlemmar);
            })
            .catch(error => console.error('Error fetching medlemmar:', error));
    }

    function updateNamnSelect(medlemmar) {
        const namnSelect = document.getElementById('namn-select');
        namnSelect.innerHTML = ''; // Clear existing options

        medlemmar.forEach(medlem => {
            const option = document.createElement('option');
            option.value = medlem.id;
            option.textContent = `${medlem.fornamn} ${medlem.efternamn}`;
            namnSelect.appendChild(option);
        });
    }
</script>