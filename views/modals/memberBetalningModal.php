<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">Ny betalning för <?= $medlem->fornamn ?> <?= $medlem->efternamn ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add your form for new payment here -->
                <form id="addPaymentForm">
                    <input type="hidden" name="medlem_id" value="<?= $medlem->id ?>">
                    <div class="mb-3">
                        <label for="datum" class="form-label">Datum</label>
                        <input type="date" class="form-control" id="datum" name="datum" required>
                    </div>
                    <div class="mb-3">
                        <label for="belopp" class="form-label">Belopp (kr)</label>
                        <input type="number" class="form-control" id="belopp" name="belopp" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="avser_ar" class="form-label">Avser år</label>
                        <input type="number" class="form-control" id="avser_ar" name="avser_ar" min="2000" max="2100" required>
                    </div>
                    <div class="mb-3">
                        <label for="kommentar" class="form-label">Kommentar</label>
                        <textarea class="form-control" id="kommentar" name="kommentar" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Stäng</button>
                <button type="button" class="btn btn-primary" onclick="submitPayment()">Spara betalning</button>
            </div>
        </div>
    </div>
</div>