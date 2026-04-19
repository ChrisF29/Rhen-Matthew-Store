<section class="module-screen" data-module="deliveries">
    <div class="module-toolbar">
        <div>
            <h3>Deliveries</h3>
            <p>Track outgoing delivery schedules and statuses for customer orders.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="deliveryModal">
                <i data-lucide="plus"></i>
                Add Delivery
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>Delivery Queue</h4>
            <span>Status lifecycle: Pending, In Transit, Delivered</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="deliveriesTable">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Scheduled Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="6" class="empty-cell">Loading deliveries...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="deliveryModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <h4 id="deliveryModalTitle">Add Delivery</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="deliveryForm" class="stack-form" data-validate>
                <input type="hidden" name="id" id="deliveryId">

                <label for="deliveryReference">Reference No.</label>
                <input id="deliveryReference" name="reference_no" type="text" placeholder="DLV-2026-001" required>

                <label for="deliveryCustomer">Customer</label>
                <input id="deliveryCustomer" name="customer_name" type="text" placeholder="Store Name" required>

                <label for="deliveryAddress">Address</label>
                <textarea id="deliveryAddress" name="address" rows="3" placeholder="Complete delivery address" required></textarea>

                <label for="deliveryDate">Scheduled Date</label>
                <input id="deliveryDate" name="scheduled_date" type="date" required>

                <label for="deliveryStatus">Status</label>
                <select id="deliveryStatus" name="status" required>
                    <option value="pending">Pending</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
