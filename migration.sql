-- ============================================
-- INVENTORY SYSTEM - Clean Database Migration
-- Database: pw_main
-- ============================================
-- Run this file to create fresh inventory tables
-- and migrate existing category/product data.
--
-- Usage:
--   mysql -u pwuser -p pw_main < migration.sql
-- ============================================

-- Drop old tables if re-running (order matters for FK)
DROP TABLE IF EXISTS inv_products;
DROP TABLE IF EXISTS inv_subcategories;
DROP TABLE IF EXISTS inv_categories;

-- ──────────────────────────────────────────────
-- 1. CATEGORIES
-- ──────────────────────────────────────────────
CREATE TABLE inv_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    image       VARCHAR(500) DEFAULT NULL,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- 2. SUBCATEGORIES
-- ──────────────────────────────────────────────
CREATE TABLE inv_subcategories (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    category_id  INT NOT NULL,
    name         VARCHAR(255) NOT NULL,
    sort_order   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inv_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- 3. PRODUCTS (inventory only - no pricing)
-- ──────────────────────────────────────────────
CREATE TABLE inv_products (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    subcategory_id   INT NOT NULL,
    name             VARCHAR(255) NOT NULL,
    sku              VARCHAR(100) NOT NULL,
    barcode          VARCHAR(100) DEFAULT NULL,
    image            VARCHAR(500) DEFAULT NULL,
    rack_location    VARCHAR(50)  DEFAULT NULL,
    quantity         INT DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sku (sku),
    INDEX idx_subcategory (subcategory_id),
    INDEX idx_barcode (barcode),
    FOREIGN KEY (subcategory_id) REFERENCES inv_subcategories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════
-- SEED DATA - Migrated from old database
-- ══════════════════════════════════════════════

-- Categories
INSERT INTO inv_categories (id, name, image, sort_order) VALUES
(1, 'Drawer',                                NULL, 1),
(2, 'Plastic Cup / Mould / Pet Container',   NULL, 2),
(3, 'Glassware',                             NULL, 3),
(4, 'Electrical',                            NULL, 4),
(5, 'Houseware',                             NULL, 5),
(6, 'Storage / Rack',                        NULL, 6),
(7, 'Dessini',                               NULL, 7),
(8, 'Cookware / Pot',                        NULL, 8);

-- Subcategories (under category 2: Plastic Cup / Mould / Pet Container)
INSERT INTO inv_subcategories (id, category_id, name, sort_order) VALUES
(1, 2, 'Plastic Ice Cream Mould', 1),
(2, 2, 'Take Away Box',           2),
(3, 2, 'Plastic Cup',             3),
(4, 2, 'Water Jug',               4),
(5, 2, 'Onesall Container',       5);

-- Products
INSERT INTO inv_products (id, subcategory_id, name, sku, barcode, image, rack_location, quantity) VALUES
(1,  1, 'VT PW 20-9436 227-356 ICE CREAM MOULD (1PCS)',   'PW-20-9436',  NULL, NULL, 'O 06', 0),
(2,  1, 'VT PW 20-9437 227-322-1 ICE CREAM MOULD (1PC)',  'PW-20-9437',  NULL, NULL, 'O 07', 0),
(3,  1, 'VT PW 20-9438 ICE CREAM MOULD SET (3PCS)',       'PW-20-9438',  NULL, NULL, NULL,   12),
(4,  2, 'TAKE AWAY LUNCH BOX 750ML (50PCS/PKT)',          'TAB-750ML',   NULL, NULL, 'A 12', 45),
(5,  2, 'TAKE AWAY ROUND CONTAINER 500ML (25PCS)',        'TAC-500ML',   NULL, NULL, 'A 13', 8),
(6,  3, 'PP CUP 16OZ (50PCS/PKT)',                        'PPC-16OZ',    NULL, NULL, 'B 01', 200),
(7,  3, 'PET CUP 12OZ DOME LID (50PCS)',                  'PEC-12OZ',    NULL, NULL, 'B 02', 0),
(8,  3, 'PP CUP 22OZ WITH LID (25PCS/PKT)',               'PPC-22OZ',    NULL, NULL, 'B 01', 55),
(9,  3, 'PAPER CUP 8OZ HOT DRINK (50PCS)',                'PAC-8OZ',     NULL, NULL, NULL,   30),
(10, 4, 'ELIANWARE WATER JUG 2.5L (1PC)',                 'EWJ-25L',     NULL, NULL, 'C 05', 15),
(11, 4, 'CRYSTAL WATER JUG 1.8L WITH LID',                'CWJ-18L',     NULL, NULL, 'C 06', 3),
(12, 5, 'ONESALL SQUARE CONTAINER 500ML',                 'OSC-500ML',   NULL, NULL, 'D 01', 96),
(13, 5, 'ONESALL ROUND CONTAINER 1L',                     'ORC-1L',      NULL, NULL, 'D 02', 48),
(14, 5, 'ONESALL RECTANGLE CONTAINER 750ML',              'ORC-750ML',   NULL, NULL, 'D 01', 0);

-- ══════════════════════════════════════════════
-- MIGRATION HELPER: If you have old tables to
-- migrate FROM, adapt queries like:
--
-- INSERT INTO inv_categories (name, image, sort_order)
-- SELECT category_name, image_url, display_order
-- FROM old_categories;
--
-- INSERT INTO inv_products (subcategory_id, name, sku, barcode, image, rack_location, quantity)
-- SELECT sc.id, op.product_name, op.sku_code, op.barcode, op.image_url, op.rack, op.stock_qty
-- FROM old_products op
-- JOIN inv_subcategories sc ON sc.name = op.subcategory_name;
-- ══════════════════════════════════════════════
