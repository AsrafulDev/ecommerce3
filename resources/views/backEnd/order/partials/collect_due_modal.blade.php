<div class="modal fade" id="collectDueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">💸 Collect Due</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Order: <strong id="collect_invoice">#</strong></p>
                <p class="mb-3">Remaining Due: <strong class="text-danger" id="collect_due">৳0.00</strong></p>
                <input type="hidden" id="collect_order_id">
                <div class="mb-2">
                    <label class="form-label small mb-1">Amount</label>
                    <input type="number" id="collect_amount" class="form-control form-control-sm" step="0.01" min="0.01" placeholder="Enter amount">
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Payment Method</label>
                    <select id="collect_method" class="form-control form-control-sm">
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Bank">Bank Transfer</option>
                        <option value="MFS">MFS (bKash/Nagad/Rocket)</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Transaction Note (optional)</label>
                    <input type="text" id="collect_trx" class="form-control form-control-sm" placeholder="Transaction ID / Reference">
                </div>
                <small id="collect_msg" class="text-muted"></small>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-sm" id="collect_btn">Receive Payment</button>
            </div>
        </div>
    </div>
</div>
