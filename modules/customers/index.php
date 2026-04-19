<section class="module-screen" data-module="customers">
    <div class="module-toolbar">
        <div>
            <h3>Customers</h3>
            <p>Manage customer profiles so names can be suggested quickly when recording new sales.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="customerModal">
                <i data-lucide="plus"></i>
                Add Customer
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>Customer Directory</h4>
            <span>Used as suggestions for the Record New Sale form</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="customersTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="6" class="empty-cell">Loading customers...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="customerModal" aria-hidden="true">
        <div class="modal-card modal-wide">
            <div class="modal-head">
                <h4 id="customerModalTitle">Add Customer</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="customerForm" class="stack-form" data-validate>
                <input type="hidden" id="customerId" name="id">

                <div class="form-grid two-col">
                    <div>
                        <label for="customerName">Customer Name</label>
                        <input id="customerName" name="name" type="text" placeholder="Sari-sari Store" required>
                    </div>
                    <div>
                        <label for="customerPhone">Phone Number</label>
                        <input id="customerPhone" name="phone" type="text" placeholder="09171234567">
                    </div>
                </div>

                <label for="customerAddress">Address</label>
                <textarea id="customerAddress" name="address" rows="3" placeholder="Complete customer address"></textarea>

                <label for="customerNotes">Notes</label>
                <textarea id="customerNotes" name="notes" rows="3" placeholder="Optional notes for this customer"></textarea>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
