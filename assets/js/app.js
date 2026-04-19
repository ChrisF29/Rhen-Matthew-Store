(() => {
    const state = {
        productCatalog: [],
        customerCatalog: [],
        toastTimer: null,
    };

    const moduleInitializers = {
        products: initProductsModule,
        inventory: initInventoryModule,
        sales: initSalesModule,
        customers: initCustomersModule,
        deliveries: initDeliveriesModule,
        drivers: initDriversModule,
        users: initUsersModule,
    };

    document.addEventListener('DOMContentLoaded', () => {
        initializeIcons();
        initializeSidebarToggle();
        initializeModalEvents();
        initializeInlineValidation();
        initializeCurrentModule();
    });

    function initializeIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function refreshIcons() {
        initializeIcons();
    }

    function initializeSidebarToggle() {
        const toggle = document.querySelector('#menuToggle');
        const sidebar = document.querySelector('#sidebar');
        const mobileQuery = window.matchMedia('(max-width: 900px)');

        if (!toggle || !sidebar) {
            return;
        }

        const closeSidebar = () => {
            document.body.classList.remove('sidebar-open');
        };

        toggle.addEventListener('click', () => {
            if (!mobileQuery.matches) {
                return;
            }

            document.body.classList.toggle('sidebar-open');
        });

        sidebar.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', closeSidebar);
        });

        document.addEventListener('click', (event) => {
            if (!document.body.classList.contains('sidebar-open')) {
                return;
            }

            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            if (!sidebar.contains(target) && target !== toggle && !toggle.contains(target)) {
                closeSidebar();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (!mobileQuery.matches) {
                closeSidebar();
            }
        });
    }

    function initializeModalEvents() {
        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const openButton = target.closest('[data-open-modal]');
            if (openButton) {
                const modalId = openButton.getAttribute('data-open-modal');
                if (modalId) {
                    openModal(modalId);
                }
                return;
            }

            const closeButton = target.closest('[data-close-modal]');
            if (closeButton) {
                closeModal(closeButton.closest('.modal'));
                return;
            }

            if (target.classList.contains('modal')) {
                closeModal(target);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const activeModal = document.querySelector('.modal.is-open');
            if (activeModal) {
                closeModal(activeModal);
            }
        });
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modalElement) {
        if (!(modalElement instanceof HTMLElement)) {
            return;
        }

        modalElement.classList.remove('is-open');
        modalElement.setAttribute('aria-hidden', 'true');
    }

    function initializeInlineValidation() {
        const forms = document.querySelectorAll('form[data-validate]');

        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                }
            });
        });
    }

    function initializeCurrentModule() {
        const moduleScreen = document.querySelector('[data-module]');
        if (!moduleScreen) {
            return;
        }

        const moduleName = moduleScreen.getAttribute('data-module');
        if (!moduleName || !moduleInitializers[moduleName]) {
            return;
        }

        moduleInitializers[moduleName]();
    }

    async function request(url, options = {}) {
        const { method = 'GET', data = undefined } = options;

        const config = {
            method,
            headers: {
                Accept: 'application/json',
            },
        };

        if (method !== 'GET' && data !== undefined) {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(data);
        }

        const response = await fetch(url, config);

        let payload = {};
        try {
            payload = await response.json();
        } catch (error) {
            payload = {};
        }

        if (!response.ok || payload.success === false) {
            const message = payload.message || `Request failed (${response.status})`;
            throw new Error(message);
        }

        return payload;
    }

    function showToast(message, level = 'info') {
        const toast = document.getElementById('toast');
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.className = `toast show ${level === 'error' ? 'error' : 'success'}`;

        if (state.toastTimer) {
            clearTimeout(state.toastTimer);
        }

        state.toastTimer = setTimeout(() => {
            toast.className = 'toast';
        }, 2600);
    }

    function toCurrency(value) {
        const amount = Number(value || 0);
        return `PHP ${amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function sanitize(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function badgeClass(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9_-]/g, '_');
    }

    function buildStatusChip(value) {
        const cleanValue = String(value || '').trim();
        if (cleanValue === '') {
            return '<span class="status-chip">N/A</span>';
        }

        const label = cleanValue.split('_').join(' ');
        return `<span class="status-chip ${badgeClass(cleanValue)}">${sanitize(label)}</span>`;
    }

    async function loadProductCatalog() {
        const response = await request('api/product_api.php');
        state.productCatalog = Array.isArray(response.data) ? response.data : [];
        return state.productCatalog;
    }

    async function loadCustomerCatalog() {
        const response = await request('api/customer_api.php');
        state.customerCatalog = Array.isArray(response.data) ? response.data : [];
        return state.customerCatalog;
    }

    function renderCustomerNameSuggestions(datalistElement) {
        if (!(datalistElement instanceof HTMLDataListElement)) {
            return;
        }

        const uniqueNames = [...new Set(
            state.customerCatalog
                .map((customer) => String(customer?.name || '').trim())
                .filter((name) => name !== '')
        )];

        datalistElement.innerHTML = uniqueNames
            .map((name) => `<option value="${sanitize(name)}"></option>`)
            .join('');
    }

    function getProductOptionsHtml(selectedProductId = '') {
        if (state.productCatalog.length === 0) {
            return '<option value="">No products available</option>';
        }

        const selectedId = String(selectedProductId);

        return state.productCatalog
            .map((product) => {
                const isSelected = String(product.id) === selectedId;
                const stockPieces = Number(product.stock_quantity || 0);
                const piecesPerCase = Number(product.pieces_per_case || 1);
                const fullCases = piecesPerCase > 0 ? Math.floor(stockPieces / piecesPerCase) : 0;
                const stockText = `${product.name} (${product.size}) - ${stockPieces} pcs (${fullCases} case${fullCases === 1 ? '' : 's'})`;
                return `<option value="${sanitize(product.id)}" ${isSelected ? 'selected' : ''}>${sanitize(stockText)}</option>`;
            })
            .join('');
    }

    function initializeActionButtons(container, handlers) {
        container.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const actionButton = target.closest('[data-action]');
            if (!actionButton) {
                return;
            }

            const action = actionButton.getAttribute('data-action');
            if (!action || !handlers[action]) {
                return;
            }

            handlers[action](actionButton);
        });
    }

    function initProductsModule() {
        const tableBody = document.querySelector('#productsTable tbody');
        const form = document.getElementById('productForm');
        const modal = document.getElementById('productModal');
        const modalTitle = document.getElementById('productModalTitle');

        if (!(tableBody instanceof HTMLElement) || !(form instanceof HTMLFormElement) || !(modal instanceof HTMLElement)) {
            return;
        }

        const openButton = document.querySelector('[data-open-modal="productModal"]');
        openButton?.addEventListener('click', () => {
            form.reset();
            const hiddenId = form.querySelector('#productId');
            if (hiddenId instanceof HTMLInputElement) {
                hiddenId.value = '';
            }
            setFormValue(form, 'pieces_per_case', '24');
            if (modalTitle) {
                modalTitle.textContent = 'Add Product';
            }
        });

        initializeActionButtons(tableBody, {
            'edit-product': (button) => {
                const id = button.getAttribute('data-id') || '';
                const name = button.getAttribute('data-name') || '';
                const category = button.getAttribute('data-category') || '';
                const size = button.getAttribute('data-size') || '';
                const price = button.getAttribute('data-price') || '';
                const piecesPerCase = button.getAttribute('data-pack') || '';
                const stock = button.getAttribute('data-stock') || '';

                setFormValue(form, 'id', id);
                setFormValue(form, 'name', name);
                setFormValue(form, 'category', category);
                setFormValue(form, 'size', size);
                setFormValue(form, 'price', price);
                setFormValue(form, 'pieces_per_case', piecesPerCase);
                setFormValue(form, 'stock_quantity', stock);

                if (modalTitle) {
                    modalTitle.textContent = 'Edit Product';
                }

                openModal('productModal');
            },
            'delete-product': async (button) => {
                const id = button.getAttribute('data-id');
                if (!id) {
                    return;
                }

                const okay = window.confirm('Delete this product? This action cannot be undone.');
                if (!okay) {
                    return;
                }

                try {
                    await request('api/product_api.php', {
                        method: 'DELETE',
                        data: { id: Number(id) },
                    });

                    showToast('Product deleted successfully.');
                    await renderProducts();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const formData = new FormData(form);
            const payload = {
                id: formData.get('id') ? Number(formData.get('id')) : undefined,
                name: String(formData.get('name') || '').trim(),
                category: String(formData.get('category') || '').trim(),
                size: String(formData.get('size') || '').trim(),
                price: Number(formData.get('price') || 0),
                pieces_per_case: Number(formData.get('pieces_per_case') || 0),
                stock_quantity: Number(formData.get('stock_quantity') || 0),
            };

            const method = payload.id ? 'PUT' : 'POST';

            try {
                await request('api/product_api.php', { method, data: payload });
                showToast(`Product ${method === 'POST' ? 'created' : 'updated'} successfully.`);
                closeModal(modal);
                form.reset();
                await renderProducts();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });

        async function renderProducts() {
            try {
                const response = await request('api/product_api.php');
                const products = Array.isArray(response.data) ? response.data : [];

                if (products.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="empty-cell">No products yet.</td></tr>';
                    return;
                }

                tableBody.innerHTML = products
                    .map((product) => {
                        const stockLevel = Number(product.stock_quantity || 0);
                        const stockClass = stockLevel <= 10 ? 'critical' : 'healthy';
                        const stockLabel = stockLevel <= 10 ? 'Low' : 'OK';

                        return `
                            <tr>
                                <td>#${sanitize(product.id)}</td>
                                <td>${sanitize(product.name)}</td>
                                <td>${sanitize(product.category)}</td>
                                <td>${sanitize(product.size)}</td>
                                <td>${toCurrency(product.price)}</td>
                                <td>${sanitize(product.pieces_per_case)}</td>
                                <td><span class="stock-pill ${stockClass}">${stockLabel}</span> <strong>${sanitize(product.stock_quantity)}</strong></td>
                                <td>
                                    <div class="inline-actions">
                                        <button class="btn btn-ghost btn-sm" type="button"
                                            data-action="edit-product"
                                            data-id="${sanitize(product.id)}"
                                            data-name="${sanitize(product.name)}"
                                            data-category="${sanitize(product.category)}"
                                            data-size="${sanitize(product.size)}"
                                            data-price="${sanitize(product.price)}"
                                            data-pack="${sanitize(product.pieces_per_case)}"
                                            data-stock="${sanitize(product.stock_quantity)}"
                                        >
                                            <i data-lucide="pencil"></i>Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" type="button" data-action="delete-product" data-id="${sanitize(product.id)}">
                                            <i data-lucide="trash-2"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');

                refreshIcons();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="8" class="empty-cell">${sanitize(error.message)}</td></tr>`;
            }
        }

        renderProducts();
    }

    function initInventoryModule() {
        const tableBody = document.querySelector('#inventoryTable tbody');
        const form = document.getElementById('inventoryForm');

        if (!(tableBody instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
            return;
        }

        const typeSelect = document.getElementById('inventoryType');
        const directionInput = document.getElementById('inventoryDirection');
        const directionLabel = document.querySelector('label[for="inventoryDirection"]');
        const productSelect = document.getElementById('inventoryProduct');
        const quantityUnitSelect = document.getElementById('inventoryQuantityUnit');
        const quantityInput = document.getElementById('inventoryQuantity');
        const quantityHelper = document.getElementById('inventoryQtyHelper');

        const toggleDirectionVisibility = () => {
            if (!(typeSelect instanceof HTMLSelectElement) || !(directionInput instanceof HTMLSelectElement) || !(directionLabel instanceof HTMLElement)) {
                return;
            }

            const isAdjustment = typeSelect.value === 'adjustment';
            directionLabel.classList.toggle('is-hidden', !isAdjustment);
            directionInput.classList.toggle('is-hidden', !isAdjustment);
            directionInput.required = isAdjustment;
            directionInput.disabled = !isAdjustment;
        };

        typeSelect?.addEventListener('change', toggleDirectionVisibility);
        toggleDirectionVisibility();

        const updateInventoryQuantityHelper = () => {
            if (!(productSelect instanceof HTMLSelectElement) || !(quantityUnitSelect instanceof HTMLSelectElement) || !(quantityInput instanceof HTMLInputElement) || !(quantityHelper instanceof HTMLElement)) {
                return;
            }

            const selectedProductId = Number(productSelect.value || 0);
            const selectedProduct = state.productCatalog.find((product) => Number(product.id) === selectedProductId);
            const piecesPerCase = Math.max(1, Number(selectedProduct?.pieces_per_case || 1));

            if (quantityUnitSelect.value === 'case') {
                quantityInput.placeholder = '1';
                if (!selectedProduct) {
                    quantityHelper.textContent = 'Select a product to see PCS/CASE conversion.';
                    return;
                }
                quantityHelper.textContent = `1 case = ${piecesPerCase} pcs for the selected product.`;
                return;
            }

            quantityInput.placeholder = '10';
            quantityHelper.textContent = 'Enter quantity in pieces.';
        };

        productSelect?.addEventListener('change', updateInventoryQuantityHelper);
        quantityUnitSelect?.addEventListener('change', updateInventoryQuantityHelper);

        initializeActionButtons(tableBody, {
            'delete-movement': async (button) => {
                const id = Number(button.getAttribute('data-id') || 0);
                if (!id) {
                    return;
                }

                const okay = window.confirm('Delete this movement and reverse its stock effect?');
                if (!okay) {
                    return;
                }

                try {
                    await request('api/inventory_api.php', {
                        method: 'DELETE',
                        data: { id },
                    });

                    showToast('Inventory movement deleted.');
                    await Promise.all([renderInventory(), loadProductsSelect()]);
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const formData = new FormData(form);
            const payload = {
                product_id: Number(formData.get('product_id') || 0),
                type: String(formData.get('type') || ''),
                direction: String(formData.get('direction') || 'increase'),
                quantity_unit: String(formData.get('quantity_unit') || 'piece'),
                quantity: Number(formData.get('quantity') || 0),
                notes: String(formData.get('notes') || '').trim(),
            };

            try {
                await request('api/inventory_api.php', {
                    method: 'POST',
                    data: payload,
                });

                showToast('Inventory movement saved.');
                form.reset();
                toggleDirectionVisibility();
                closeModal(document.getElementById('inventoryModal'));
                await Promise.all([renderInventory(), loadProductsSelect()]);
            } catch (error) {
                showToast(error.message, 'error');
            }
        });

        async function loadProductsSelect() {
            const select = document.getElementById('inventoryProduct');
            if (!(select instanceof HTMLSelectElement)) {
                return;
            }

            try {
                await loadProductCatalog();
                select.innerHTML = getProductOptionsHtml();
                updateInventoryQuantityHelper();
            } catch (error) {
                select.innerHTML = '<option value="">Unable to load products</option>';
                if (quantityHelper instanceof HTMLElement) {
                    quantityHelper.textContent = 'Unable to load product case conversion details.';
                }
            }
        }

        async function renderInventory() {
            try {
                const response = await request('api/inventory_api.php');
                const rows = Array.isArray(response.data) ? response.data : [];

                if (rows.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="empty-cell">No inventory entries yet.</td></tr>';
                    return;
                }

                tableBody.innerHTML = rows
                    .map((entry) => {
                        const quantity = Number(entry.quantity || 0);
                        const qtyLabel = quantity > 0 ? `+${quantity}` : `${quantity}`;
                        return `
                            <tr>
                                <td>${sanitize(formatDate(entry.created_at))}</td>
                                <td>${sanitize(entry.product_name)}</td>
                                <td>${buildStatusChip(entry.type)}</td>
                                <td>${sanitize(`${qtyLabel} pcs`)}</td>
                                <td>${sanitize(`${entry.stock_quantity} pcs`)}</td>
                                <td>${sanitize(entry.notes || '-')}</td>
                                <td>
                                    <button class="btn btn-danger btn-sm" type="button" data-action="delete-movement" data-id="${sanitize(entry.id)}">
                                        <i data-lucide="trash-2"></i>Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');

                refreshIcons();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="7" class="empty-cell">${sanitize(error.message)}</td></tr>`;
            }
        }

        Promise.all([renderInventory(), loadProductsSelect()]);
    }

    function initSalesModule() {
        const tableBody = document.querySelector('#salesTable tbody');
        const form = document.getElementById('saleForm');
        const itemsContainer = document.getElementById('saleItemsContainer');
        const addItemButton = document.getElementById('addSaleItemBtn');
        const customerSuggestions = document.getElementById('saleCustomerList');

        if (!(tableBody instanceof HTMLElement) || !(form instanceof HTMLFormElement) || !(itemsContainer instanceof HTMLElement)) {
            return;
        }

        const hydrateSalesLookups = async () => {
            await loadProductCatalog();
            try {
                await loadCustomerCatalog();
            } catch (error) {
                state.customerCatalog = [];
            }
            renderCustomerNameSuggestions(customerSuggestions);
        };

        const resetForm = async () => {
            form.reset();
            itemsContainer.innerHTML = '';
            await hydrateSalesLookups();
            addItemRow();
        };

        document.querySelector('[data-open-modal="saleModal"]')?.addEventListener('click', async () => {
            await resetForm();
        });

        addItemButton?.addEventListener('click', () => {
            addItemRow();
        });

        itemsContainer.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const removeButton = target.closest('[data-action="remove-sale-item"]');
            if (!removeButton) {
                return;
            }

            const row = removeButton.closest('.sale-item-row');
            row?.remove();

            if (itemsContainer.children.length === 0) {
                addItemRow();
            }
        });

        initializeActionButtons(tableBody, {
            'mark-paid': async (button) => {
                const id = Number(button.getAttribute('data-id') || 0);
                if (!id) {
                    return;
                }

                try {
                    await request('api/sales_api.php', {
                        method: 'PUT',
                        data: {
                            id,
                            status: 'paid',
                        },
                    });
                    showToast('Sale marked as paid.');
                    await renderSales();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
            'delete-sale': async (button) => {
                const id = Number(button.getAttribute('data-id') || 0);
                if (!id) {
                    return;
                }

                const okay = window.confirm('Delete this sale and reverse stock?');
                if (!okay) {
                    return;
                }

                try {
                    await request('api/sales_api.php', {
                        method: 'DELETE',
                        data: { id },
                    });
                    showToast('Sale deleted and stock restored.');
                    await renderSales();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const lineItems = [...itemsContainer.querySelectorAll('.sale-item-row')]
                .map((row) => {
                    const productField = row.querySelector('select[name="product_id"]');
                    const quantityField = row.querySelector('input[name="ordered_qty"]');
                    const unitField = row.querySelector('select[name="order_unit"]');

                    const productId = Number(productField?.value || 0);
                    const orderedQty = Number(quantityField?.value || 0);
                    const orderUnit = String(unitField?.value || 'piece');

                    return {
                        product_id: productId,
                        ordered_qty: orderedQty,
                        order_unit: orderUnit,
                    };
                })
                .filter((item) => item.product_id > 0 && item.ordered_qty > 0);

            if (lineItems.length === 0) {
                showToast('Add at least one valid sale item.', 'error');
                return;
            }

            const formData = new FormData(form);
            const payload = {
                customer_name: String(formData.get('customer_name') || '').trim(),
                payment_type: String(formData.get('payment_type') || 'cash'),
                items: lineItems,
            };

            try {
                await request('api/sales_api.php', {
                    method: 'POST',
                    data: payload,
                });

                showToast('Sale recorded successfully.');
                closeModal(document.getElementById('saleModal'));
                await renderSales();
                await resetForm();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });

        function addItemRow(defaultItem = {}) {
            const row = document.createElement('div');
            row.className = 'sale-item-row';

            row.innerHTML = `
                <div>
                    <label>Product</label>
                    <select name="product_id" required>
                        <option value="">Select product</option>
                        ${getProductOptionsHtml(defaultItem.product_id || '')}
                    </select>
                </div>
                <div>
                    <label>Order Unit</label>
                    <select name="order_unit" required>
                        <option value="piece" ${defaultItem.order_unit === 'piece' ? 'selected' : ''}>Piece</option>
                        <option value="case" ${defaultItem.order_unit === 'case' ? 'selected' : ''}>Full Case</option>
                        <option value="half_case" ${defaultItem.order_unit === 'half_case' ? 'selected' : ''}>Half Case</option>
                        <option value="quarter_case" ${defaultItem.order_unit === 'quarter_case' ? 'selected' : ''}>Quarter Case</option>
                    </select>
                </div>
                <div>
                    <label>Order Qty</label>
                    <input name="ordered_qty" type="number" min="1" step="1" value="${sanitize(defaultItem.ordered_qty || 1)}" required>
                </div>
                <button class="btn btn-danger btn-sm" type="button" data-action="remove-sale-item">
                    <i data-lucide="minus"></i>
                    Remove
                </button>
            `;

            itemsContainer.appendChild(row);
            refreshIcons();
        }

        async function renderSales() {
            try {
                const response = await request('api/sales_api.php');
                const sales = Array.isArray(response.data) ? response.data : [];

                if (sales.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="empty-cell">No sales recorded yet.</td></tr>';
                    return;
                }

                tableBody.innerHTML = sales
                    .map((sale) => {
                        return `
                            <tr>
                                <td>#${sanitize(sale.id)}</td>
                                <td>${sanitize(formatDate(sale.created_at))}</td>
                                <td>${sanitize(sale.customer_name)}</td>
                                <td>${sanitize(sale.total_items)} pcs</td>
                                <td>${toCurrency(sale.total_amount)}</td>
                                <td>${buildStatusChip(sale.payment_type)}</td>
                                <td>${buildStatusChip(sale.status)}</td>
                                <td>
                                    <div class="inline-actions">
                                        ${sale.status === 'pending' ? `
                                            <button class="btn btn-ghost btn-sm" type="button" data-action="mark-paid" data-id="${sanitize(sale.id)}">
                                                <i data-lucide="badge-check"></i>Mark Paid
                                            </button>
                                        ` : ''}
                                        <button class="btn btn-danger btn-sm" type="button" data-action="delete-sale" data-id="${sanitize(sale.id)}">
                                            <i data-lucide="trash-2"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');

                refreshIcons();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="8" class="empty-cell">${sanitize(error.message)}</td></tr>`;
            }
        }

        (async () => {
            await hydrateSalesLookups();
            addItemRow();
            await renderSales();
        })();
    }

    function initCustomersModule() {
        const tableBody = document.querySelector('#customersTable tbody');
        const form = document.getElementById('customerForm');
        const modalTitle = document.getElementById('customerModalTitle');

        if (!(tableBody instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
            return;
        }

        document.querySelector('[data-open-modal="customerModal"]')?.addEventListener('click', () => {
            form.reset();
            setFormValue(form, 'id', '');
            if (modalTitle) {
                modalTitle.textContent = 'Add Customer';
            }
        });

        initializeActionButtons(tableBody, {
            'edit-customer': (button) => {
                setFormValue(form, 'id', button.getAttribute('data-id') || '');
                setFormValue(form, 'name', button.getAttribute('data-name') || '');
                setFormValue(form, 'phone', button.getAttribute('data-phone') || '');
                setFormValue(form, 'address', button.getAttribute('data-address') || '');
                setFormValue(form, 'notes', button.getAttribute('data-notes') || '');

                if (modalTitle) {
                    modalTitle.textContent = 'Edit Customer';
                }

                openModal('customerModal');
            },
            'delete-customer': async (button) => {
                const id = Number(button.getAttribute('data-id') || 0);
                if (!id) {
                    return;
                }

                const okay = window.confirm('Delete this customer record?');
                if (!okay) {
                    return;
                }

                try {
                    await request('api/customer_api.php', {
                        method: 'DELETE',
                        data: { id },
                    });
                    showToast('Customer deleted successfully.');
                    await renderCustomers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const formData = new FormData(form);
            const payload = {
                id: formData.get('id') ? Number(formData.get('id')) : undefined,
                name: String(formData.get('name') || '').trim(),
                phone: String(formData.get('phone') || '').trim(),
                address: String(formData.get('address') || '').trim(),
                notes: String(formData.get('notes') || '').trim(),
            };

            try {
                await request('api/customer_api.php', {
                    method: payload.id ? 'PUT' : 'POST',
                    data: payload,
                });

                showToast(`Customer ${payload.id ? 'updated' : 'created'} successfully.`);
                closeModal(document.getElementById('customerModal'));
                form.reset();
                await renderCustomers();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });

        async function renderCustomers() {
            try {
                const response = await request('api/customer_api.php');
                const customers = Array.isArray(response.data) ? response.data : [];

                if (customers.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="empty-cell">No customers found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = customers
                    .map((customer) => {
                        return `
                            <tr>
                                <td>#${sanitize(customer.id)}</td>
                                <td>${sanitize(customer.name)}</td>
                                <td>${sanitize(customer.phone || '-')}</td>
                                <td>${sanitize(customer.address || '-')}</td>
                                <td>${sanitize(formatDate(customer.created_at))}</td>
                                <td>
                                    <div class="inline-actions">
                                        <button class="btn btn-ghost btn-sm" type="button"
                                            data-action="edit-customer"
                                            data-id="${sanitize(customer.id)}"
                                            data-name="${sanitize(customer.name)}"
                                            data-phone="${sanitize(customer.phone || '')}"
                                            data-address="${sanitize(customer.address || '')}"
                                            data-notes="${sanitize(customer.notes || '')}"
                                        >
                                            <i data-lucide="pencil"></i>Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" type="button" data-action="delete-customer" data-id="${sanitize(customer.id)}">
                                            <i data-lucide="trash-2"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');

                refreshIcons();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="6" class="empty-cell">${sanitize(error.message)}</td></tr>`;
            }
        }

        renderCustomers();
    }

    function initDeliveriesModule() {
        const tableBody = document.querySelector('#deliveriesTable tbody');
        const form = document.getElementById('deliveryForm');
        const modalTitle = document.getElementById('deliveryModalTitle');
        const addDeliveryButton = document.querySelector('[data-open-modal="deliveryModal"]');

        if (!(tableBody instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
            return;
        }

        const loadNextDeliveryReference = async () => {
            try {
                const response = await request('api/delivery_api.php?action=next_reference');
                const nextReference = String(response?.data?.reference_no || '').trim();
                if (nextReference !== '') {
                    setFormValue(form, 'reference_no', nextReference);
                }
            } catch (error) {
                // Keep form usable even if auto-reference request fails.
            }
        };

        addDeliveryButton?.addEventListener('click', async () => {
            form.reset();
            setFormValue(form, 'id', '');
            if (modalTitle) {
                modalTitle.textContent = 'Add Delivery';
            }
            await loadNextDeliveryReference();
        });

        initializeActionButtons(tableBody, {
            'edit-delivery': (button) => {
                setFormValue(form, 'id', button.getAttribute('data-id') || '');
                setFormValue(form, 'reference_no', button.getAttribute('data-reference') || '');
                setFormValue(form, 'customer_name', button.getAttribute('data-customer') || '');
                setFormValue(form, 'address', button.getAttribute('data-address') || '');
                setFormValue(form, 'scheduled_date', button.getAttribute('data-date') || '');
                setFormValue(form, 'status', button.getAttribute('data-status') || 'pending');

                if (modalTitle) {
                    modalTitle.textContent = 'Edit Delivery';
                }

                openModal('deliveryModal');
            },
            'delete-delivery': async (button) => {
                const id = Number(button.getAttribute('data-id') || 0);
                if (!id) {
                    return;
                }

                const okay = window.confirm('Delete this delivery record?');
                if (!okay) {
                    return;
                }

                try {
                    await request('api/delivery_api.php', {
                        method: 'DELETE',
                        data: { id },
                    });
                    showToast('Delivery deleted.');
                    await renderDeliveries();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const formData = new FormData(form);
            const payload = {
                id: formData.get('id') ? Number(formData.get('id')) : undefined,
                reference_no: String(formData.get('reference_no') || '').trim(),
                customer_name: String(formData.get('customer_name') || '').trim(),
                address: String(formData.get('address') || '').trim(),
                scheduled_date: String(formData.get('scheduled_date') || ''),
                status: String(formData.get('status') || 'pending'),
            };

            try {
                await request('api/delivery_api.php', {
                    method: payload.id ? 'PUT' : 'POST',
                    data: payload,
                });

                showToast(`Delivery ${payload.id ? 'updated' : 'created'} successfully.`);
                closeModal(document.getElementById('deliveryModal'));
                form.reset();
                await renderDeliveries();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });

        async function renderDeliveries() {
            try {
                const response = await request('api/delivery_api.php');
                const deliveries = Array.isArray(response.data) ? response.data : [];

                if (deliveries.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="empty-cell">No deliveries scheduled yet.</td></tr>';
                    return;
                }

                tableBody.innerHTML = deliveries
                    .map((delivery) => {
                        const scheduleDate = String(delivery.scheduled_date || '').slice(0, 10);
                        return `
                            <tr>
                                <td>${sanitize(delivery.reference_no)}</td>
                                <td>${sanitize(delivery.customer_name)}</td>
                                <td>${sanitize(delivery.address)}</td>
                                <td>${sanitize(scheduleDate || '-')}</td>
                                <td>${buildStatusChip(delivery.status)}</td>
                                <td>
                                    <div class="inline-actions">
                                        <button class="btn btn-ghost btn-sm" type="button"
                                            data-action="edit-delivery"
                                            data-id="${sanitize(delivery.id)}"
                                            data-reference="${sanitize(delivery.reference_no)}"
                                            data-customer="${sanitize(delivery.customer_name)}"
                                            data-address="${sanitize(delivery.address)}"
                                            data-date="${sanitize(scheduleDate)}"
                                            data-status="${sanitize(delivery.status)}"
                                        >
                                            <i data-lucide="pencil"></i>Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" type="button" data-action="delete-delivery" data-id="${sanitize(delivery.id)}">
                                            <i data-lucide="trash-2"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');

                refreshIcons();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="6" class="empty-cell">${sanitize(error.message)}</td></tr>`;
            }
        }

        renderDeliveries();
    }

    function initUsersModule() {
        const tableBody = document.querySelector('#usersTable tbody');
        const form = document.getElementById('userForm');
        const modalTitle = document.getElementById('userModalTitle');

        if (!(tableBody instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
            return;
        }

        document.querySelector('[data-open-modal="userModal"]')?.addEventListener('click', () => {
            form.reset();
            setFormValue(form, 'id', '');
            const passwordField = form.querySelector('#userPassword');
            if (passwordField instanceof HTMLInputElement) {
                passwordField.required = true;
            }
            if (modalTitle) {
                modalTitle.textContent = 'Add User';
            }
        });

        initializeActionButtons(tableBody, {
            'edit-user': (button) => {
                setFormValue(form, 'id', button.getAttribute('data-id') || '');
                setFormValue(form, 'name', button.getAttribute('data-name') || '');
                setFormValue(form, 'email', button.getAttribute('data-email') || '');
                setFormValue(form, 'role', button.getAttribute('data-role') || 'staff');
                setFormValue(form, 'password', '');

                const passwordField = form.querySelector('#userPassword');
                if (passwordField instanceof HTMLInputElement) {
                    passwordField.required = false;
                }

                if (modalTitle) {
                    modalTitle.textContent = 'Edit User';
                }

                openModal('userModal');
            },
            'delete-user': async (button) => {
                const id = Number(button.getAttribute('data-id') || 0);
                if (!id) {
                    return;
                }

                const okay = window.confirm('Delete this user account?');
                if (!okay) {
                    return;
                }

                try {
                    await request('api/user_api.php', {
                        method: 'DELETE',
                        data: { id },
                    });
                    showToast('User deleted successfully.');
                    await renderUsers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const formData = new FormData(form);
            const payload = {
                id: formData.get('id') ? Number(formData.get('id')) : undefined,
                name: String(formData.get('name') || '').trim(),
                email: String(formData.get('email') || '').trim(),
                role: String(formData.get('role') || 'staff'),
                password: String(formData.get('password') || ''),
            };

            if (!payload.password) {
                delete payload.password;
            }

            try {
                await request('api/user_api.php', {
                    method: payload.id ? 'PUT' : 'POST',
                    data: payload,
                });

                showToast(`User ${payload.id ? 'updated' : 'created'} successfully.`);
                closeModal(document.getElementById('userModal'));
                form.reset();
                await renderUsers();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });

        async function renderUsers() {
            try {
                const response = await request('api/user_api.php');
                const users = Array.isArray(response.data) ? response.data : [];

                if (users.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="empty-cell">No users found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = users
                    .map((user) => {
                        return `
                            <tr>
                                <td>#${sanitize(user.id)}</td>
                                <td>${sanitize(user.name)}</td>
                                <td>${sanitize(user.email)}</td>
                                <td>${buildStatusChip(user.role)}</td>
                                <td>${sanitize(formatDate(user.created_at))}</td>
                                <td>
                                    <div class="inline-actions">
                                        <button class="btn btn-ghost btn-sm" type="button"
                                            data-action="edit-user"
                                            data-id="${sanitize(user.id)}"
                                            data-name="${sanitize(user.name)}"
                                            data-email="${sanitize(user.email)}"
                                            data-role="${sanitize(user.role)}"
                                        >
                                            <i data-lucide="pencil"></i>Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" type="button" data-action="delete-user" data-id="${sanitize(user.id)}">
                                            <i data-lucide="trash-2"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');

                refreshIcons();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="6" class="empty-cell">${sanitize(error.message)}</td></tr>`;
            }
        }

        renderUsers();
    }

    function initDriversModule() {
        const tableBody = document.querySelector('#driversTable tbody');
        const form = document.getElementById('driverForm');
        const modalTitle = document.getElementById('driverModalTitle');

        if (!(tableBody instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
            return;
        }

        document.querySelector('[data-open-modal="driverModal"]')?.addEventListener('click', () => {
            form.reset();
            setFormValue(form, 'id', '');
            setFormValue(form, 'status', 'active');
            if (modalTitle) {
                modalTitle.textContent = 'Add Driver';
            }
        });

        initializeActionButtons(tableBody, {
            'edit-driver': (button) => {
                setFormValue(form, 'id', button.getAttribute('data-id') || '');
                setFormValue(form, 'full_name', button.getAttribute('data-name') || '');
                setFormValue(form, 'phone', button.getAttribute('data-phone') || '');
                setFormValue(form, 'license_no', button.getAttribute('data-license') || '');
                setFormValue(form, 'vehicle_assigned', button.getAttribute('data-vehicle') || '');
                setFormValue(form, 'status', button.getAttribute('data-status') || 'active');
                setFormValue(form, 'hired_date', button.getAttribute('data-hired-date') || '');
                setFormValue(form, 'notes', button.getAttribute('data-notes') || '');

                if (modalTitle) {
                    modalTitle.textContent = 'Edit Driver';
                }

                openModal('driverModal');
            },
            'delete-driver': async (button) => {
                const id = Number(button.getAttribute('data-id') || 0);
                if (!id) {
                    return;
                }

                const okay = window.confirm('Delete this driver record?');
                if (!okay) {
                    return;
                }

                try {
                    await request('api/driver_api.php', {
                        method: 'DELETE',
                        data: { id },
                    });
                    showToast('Driver deleted successfully.');
                    await renderDrivers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!form.reportValidity()) {
                return;
            }

            const formData = new FormData(form);
            const payload = {
                id: formData.get('id') ? Number(formData.get('id')) : undefined,
                full_name: String(formData.get('full_name') || '').trim(),
                phone: String(formData.get('phone') || '').trim(),
                license_no: String(formData.get('license_no') || '').trim(),
                vehicle_assigned: String(formData.get('vehicle_assigned') || '').trim(),
                status: String(formData.get('status') || 'active'),
                hired_date: String(formData.get('hired_date') || ''),
                notes: String(formData.get('notes') || '').trim(),
            };

            try {
                await request('api/driver_api.php', {
                    method: payload.id ? 'PUT' : 'POST',
                    data: payload,
                });

                showToast(`Driver ${payload.id ? 'updated' : 'added'} successfully.`);
                closeModal(document.getElementById('driverModal'));
                form.reset();
                await renderDrivers();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });

        async function renderDrivers() {
            try {
                const response = await request('api/driver_api.php');
                const drivers = Array.isArray(response.data) ? response.data : [];

                if (drivers.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="empty-cell">No drivers found.</td></tr>';
                    return;
                }

                tableBody.innerHTML = drivers
                    .map((driver) => {
                        const hiredDate = String(driver.hired_date || '').slice(0, 10);
                        return `
                            <tr>
                                <td>#${sanitize(driver.id)}</td>
                                <td>${sanitize(driver.full_name)}</td>
                                <td>${sanitize(driver.phone)}</td>
                                <td>${sanitize(driver.license_no)}</td>
                                <td>${sanitize(driver.vehicle_assigned || '-')}</td>
                                <td>${sanitize(hiredDate || '-')}</td>
                                <td>${buildStatusChip(driver.status)}</td>
                                <td>
                                    <div class="inline-actions">
                                        <button class="btn btn-ghost btn-sm" type="button"
                                            data-action="edit-driver"
                                            data-id="${sanitize(driver.id)}"
                                            data-name="${sanitize(driver.full_name)}"
                                            data-phone="${sanitize(driver.phone)}"
                                            data-license="${sanitize(driver.license_no)}"
                                            data-vehicle="${sanitize(driver.vehicle_assigned || '')}"
                                            data-status="${sanitize(driver.status)}"
                                            data-hired-date="${sanitize(hiredDate)}"
                                            data-notes="${sanitize(driver.notes || '')}"
                                        >
                                            <i data-lucide="pencil"></i>Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" type="button" data-action="delete-driver" data-id="${sanitize(driver.id)}">
                                            <i data-lucide="trash-2"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');

                refreshIcons();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="8" class="empty-cell">${sanitize(error.message)}</td></tr>`;
            }
        }

        renderDrivers();
    }

    function setFormValue(form, name, value) {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const field = form.querySelector(`[name="${name}"]`);
        if (
            field instanceof HTMLInputElement ||
            field instanceof HTMLTextAreaElement ||
            field instanceof HTMLSelectElement
        ) {
            field.value = String(value ?? '');
        }
    }
})();
