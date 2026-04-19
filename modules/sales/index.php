<section class="module-screen" data-module="sales">
    <div class="module-toolbar">
        <div>
            <h3>Sales</h3>
            <p>Record customer orders, capture payment mode (cash or utang), and update stock in real time.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="saleModal">
                <i data-lucide="plus"></i>
                Record Sale
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>Sales Transactions</h4>
            <span>Auto-deducts stock per line item</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="salesTable">
                <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="8" class="empty-cell">Loading sales...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="saleModal" aria-hidden="true">
        <div class="modal-card modal-wide">
            <div class="modal-head">
                <h4>Record New Sale</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="saleForm" class="stack-form" data-validate>
                <div class="form-grid two-col">
                    <div>
                        <label for="saleCustomer">Customer Name</label>
                        <input id="saleCustomer" name="customer_name" type="text" placeholder="Sari-sari Store" required>
                    </div>
                    <div>
                        <label for="salePaymentType">Payment Type</label>
                        <select id="salePaymentType" name="payment_type" required>
                            <option value="cash">Cash</option>
                            <option value="utang">Utang</option>
                        </select>
                    </div>
                </div>

                <div class="sale-items-head">
                    <h5>Sale Items</h5>
                    <button class="btn btn-ghost btn-sm" type="button" id="addSaleItemBtn">
                        <i data-lucide="plus"></i>
                        Add Item
                    </button>
                </div>

                <div id="saleItemsContainer" class="sale-items-container"></div>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
