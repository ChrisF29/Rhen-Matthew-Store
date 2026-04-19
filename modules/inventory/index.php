<section class="module-screen" data-module="inventory">
    <div class="module-toolbar">
        <div>
            <h3>Inventory Ledger</h3>
            <p>Record stock in, stock out, and adjustment entries while keeping live balances accurate.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="inventoryModal">
                <i data-lucide="plus"></i>
                Add Movement
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>Stock Movements</h4>
            <span>Most recent first</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="inventoryTable">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Quantity (pcs)</th>
                    <th>Balance (pcs)</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="7" class="empty-cell">Loading inventory records...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="inventoryModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <h4>Add Inventory Movement</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="inventoryForm" class="stack-form" data-validate>
                <label for="inventoryProduct">Product</label>
                <select id="inventoryProduct" name="product_id" required></select>

                <label for="inventoryType">Movement Type</label>
                <select id="inventoryType" name="type" required>
                    <option value="in">Stock In</option>
                    <option value="out">Stock Out</option>
                    <option value="adjustment">Adjustment</option>
                </select>

                <label for="inventoryDirection">Adjustment Direction</label>
                <select id="inventoryDirection" name="direction" required>
                    <option value="increase">Increase</option>
                    <option value="decrease">Decrease</option>
                </select>

                <label for="inventoryQuantityUnit">Quantity Unit</label>
                <select id="inventoryQuantityUnit" name="quantity_unit" required>
                    <option value="piece">Piece</option>
                    <option value="case">Case</option>
                </select>
                <p id="inventoryQtyHelper" class="field-help">Enter quantity in pieces.</p>

                <label for="inventoryQuantity">Quantity</label>
                <input id="inventoryQuantity" name="quantity" type="number" min="1" step="1" placeholder="10" required>

                <label for="inventoryNotes">Notes</label>
                <textarea id="inventoryNotes" name="notes" rows="3" placeholder="Delivery arrival, damaged stock, manual recount..."></textarea>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save Movement
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
