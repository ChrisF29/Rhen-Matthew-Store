<section class="module-screen" data-module="products">
    <div class="module-toolbar">
        <div>
            <h3>Products</h3>
            <p>Create and maintain your softdrinks catalog with case pack size and piece-level stock.</p>
        </div>
        <div class="toolbar-actions">
            <button class="btn btn-primary" type="button" data-open-modal="productModal">
                <i data-lucide="plus"></i>
                Add Product
            </button>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h4>Product List</h4>
            <span>Live data via API</span>
        </div>
        <div class="table-wrap">
            <table class="data-table" id="productsTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Size</th>
                    <th>Price / Piece</th>
                    <th>Pcs / Case</th>
                    <th>Stock (pcs)</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="8" class="empty-cell">Loading products...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal" id="productModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <h4 id="productModalTitle">Add Product</h4>
                <button class="icon-btn" type="button" data-close-modal aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="productForm" class="stack-form" data-validate>
                <input type="hidden" id="productId" name="id">

                <label for="productName">Product Name</label>
                <input id="productName" name="name" type="text" placeholder="Coke Mismo" required>

                <label for="productCategory">Category</label>
                <input id="productCategory" name="category" type="text" placeholder="Softdrinks" required>

                <label for="productSize">Size</label>
                <input id="productSize" name="size" type="text" placeholder="290ml" required>

                <label for="productPrice">Price (PHP)</label>
                <input id="productPrice" name="price" type="number" min="0" step="0.01" placeholder="18.00" required>

                <label for="productPiecesPerCase">Pieces Per Case</label>
                <input id="productPiecesPerCase" name="pieces_per_case" type="number" min="1" step="1" placeholder="24" required>

                <label for="productStock">Starting Stock (pcs)</label>
                <input id="productStock" name="stock_quantity" type="number" min="0" step="1" placeholder="100" required>

                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="save"></i>
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
