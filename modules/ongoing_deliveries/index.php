<section class="module-screen" data-module="ongoing_deliveries">
    <div class="module-toolbar">
        <div>
            <h3>Ongoing Deliveries</h3>
            <p>Create proposed orders, dispatch stock, then confirm actual delivered quantities before sales are finalized.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="ongoingDeliveryModal">
                <i data-lucide="plus"></i>
                Create Delivery Order
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>Delivery Orders In Progress</h4>
            <span>Workflow: Pending Dispatch -> In Transit -> Completed or Cancelled</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="ongoingDeliveriesTable">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Scheduled Date</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Loaded (pcs)</th>
                    <th>Delivered (pcs)</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="8" class="empty-cell">Loading ongoing deliveries...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="ongoingDeliveryModal" aria-hidden="true">
        <div class="modal-card modal-wide">
            <div class="modal-head">
                <h4>Create Delivery Order</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="ongoingDeliveryForm" class="stack-form" data-validate>
                <div class="form-grid two-col">
                    <div>
                        <label for="ongoingReference">Reference No.</label>
                        <input id="ongoingReference" name="reference_no" type="text" placeholder="ODL-2026-0001" readonly>
                    </div>
                    <div>
                        <label for="ongoingScheduledDate">Scheduled Date</label>
                        <input id="ongoingScheduledDate" name="scheduled_date" type="date" required>
                    </div>
                </div>

                <div class="form-grid two-col">
                    <div>
                        <label for="ongoingCustomerName">Customer Name</label>
                        <input id="ongoingCustomerName" name="customer_name" type="text" placeholder="Store Name" list="ongoingDeliveryCustomerList" required>
                        <datalist id="ongoingDeliveryCustomerList"></datalist>
                    </div>
                    <div>
                        <label for="ongoingPaymentType">Payment Type</label>
                        <select id="ongoingPaymentType" name="payment_type" required>
                            <option value="cash">Cash</option>
                            <option value="utang">Utang</option>
                        </select>
                    </div>
                </div>

                <label for="ongoingNotes">Notes</label>
                <textarea id="ongoingNotes" name="notes" rows="2" placeholder="Route, expected drop-off conditions, etc."></textarea>

                <div class="sale-items-head">
                    <h5>Order Items</h5>
                    <button class="btn btn-ghost btn-sm" type="button" id="addOngoingItemBtn">
                        <i data-lucide="plus"></i>
                        Add Item
                    </button>
                </div>

                <div id="ongoingItemsContainer" class="sale-items-container"></div>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save Delivery Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="ongoingCompleteModal" aria-hidden="true">
        <div class="modal-card modal-wide">
            <div class="modal-head">
                <h4 id="ongoingCompleteTitle">Finalize Delivery</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="ongoingCompleteForm" class="stack-form" data-validate>
                <input type="hidden" name="id" id="ongoingCompleteId">
                <p class="field-help">Enter delivered quantity for each line item using the same order unit. Remaining quantity is treated as backload/cancelled.</p>
                <div id="ongoingCompleteItems" class="sale-items-container"></div>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="check-check"></i>
                        Confirm Delivery and Finalize Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
