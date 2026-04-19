USE rhen_matthew_store;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS pieces_per_case INT UNSIGNED NOT NULL DEFAULT 24 AFTER price;

ALTER TABLE sales_items
    ADD COLUMN IF NOT EXISTS ordered_qty INT UNSIGNED NOT NULL DEFAULT 1 AFTER quantity,
    ADD COLUMN IF NOT EXISTS order_unit ENUM('piece', 'case', 'half_case', 'quarter_case') NOT NULL DEFAULT 'piece' AFTER ordered_qty,
    ADD COLUMN IF NOT EXISTS base_units INT UNSIGNED NOT NULL DEFAULT 0 AFTER order_unit;

-- Backfill existing rows so previous sales remain valid in the new model.
UPDATE sales_items
SET ordered_qty = CASE WHEN ordered_qty = 0 THEN quantity ELSE ordered_qty END,
    order_unit = COALESCE(NULLIF(order_unit, ''), 'piece'),
    base_units = CASE WHEN base_units = 0 THEN quantity ELSE base_units END;
