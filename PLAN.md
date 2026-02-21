# Inventory System - Procurement & Feature Gap Analysis

## Current System Overview

**Tech Stack:** PHP + MySQL (MariaDB), Bootstrap 5, jQuery, SweetAlert2
**Database:** `pw_main` on MariaDB 10.3

---

## What You Currently Have

### Database Tables
| Table | Purpose |
|-------|---------|
| `category` | Product categories & subcategories |
| `PRODUCTS` | Product catalog (barcode, pricing, stock qty, images, rack location) |
| `orderlist` | Customer orders with status tracking |
| `orderlist2` | Order summary (auto-generated from orderlist) |
| `stockin` | Stock-in records (goods received, with SUPPLIER & SUPPNO fields) |
| `stockout` | Stock-out records (goods dispatched, with delivery tracking fields) |
| `stockadj` | Stock adjustment records (manual qty corrections) |
| `parafile` | System parameters (includes STKIN/STKOUT/STKADJ counters) |
| `sysfile` | System users (admin, staff, delivery) |

### Current Features
1. **Product Catalog** - Full product listing with categories, subcategories, images, barcodes, pricing, rack locations
2. **Stock-In** - Record goods received (has supplier name & supplier number fields)
3. **Stock-Out** - Record goods dispatched (has delivery person, vehicle, dates)
4. **Stock Adjustment** - Manual stock quantity corrections
5. **Order Management** - Customer orders with PENDING/DONE/DELETED status workflow
6. **Shopping Cart** - Frontend cart using sessionStorage with qty selection
7. **Admin Dashboard** - Live order view with auto-refresh, notifications, search
8. **User Management** - Admin panel for managing users

---

## Furniture Procurement - What You Have

Furniture is category code `38` with **29 subcategories**:

| Sub Code | Subcategory |
|----------|-------------|
| 1 | DINING |
| 2 | LIVING ROOM |
| 3 | BEDROOM |
| 4 | STUDY ROOM |
| 5 | OTHERS |
| 6 | METAL PRODUCT |
| 7 | BOOKSHELF |
| 8 | CUSHION |
| 9 | SWING |
| 10 | DINNING TABLE |
| 11 | SOFA SET |
| 12 | BEDSHEET |
| 13 | FOLDING TABLE |
| 14 | IRON BOARD |
| 15 | MULTI PURPOSE RACK |
| 16 | ROSTRUM |
| 17 | CABINET |
| 18-29 | CHINA STOCK (GD75-GD84), YUKI FURNITURE, ELK DESA |

### For procurement specifically:
- Products are cataloged with barcode, cost price, original price, discount price
- The `stockin` table records when goods arrive (with SUPPLIER and SUPPNO fields)
- But there is **NO dedicated supplier master table** — supplier info is just free-text in stockin records
- There is **NO formal Purchase Order (PO) system** — no PO creation, no PO approval, no PO-to-receiving matching
- Stock quantities (`qoh` in PRODUCTS table) exist but are not clearly linked to any automated PO trigger

---

## Missing Features for a Complete Inventory System

### Priority 1 — Core Procurement Features (Must Have)

#### 1. Supplier Management
- **What's missing:** No `supplier` table. Supplier data is stored as free-text in `stockin` records, causing inconsistency.
- **What to build:** Supplier master table with contact info, payment terms, lead time, product associations. Supplier CRUD pages in admin.

#### 2. Purchase Order (PO) System
- **What's missing:** No formal PO lifecycle. You can record stock-in but can't create/track/approve purchase orders before goods arrive.
- **What to build:**
  - PO creation (select supplier, add line items with qty & price)
  - PO status workflow: DRAFT → APPROVED → PARTIALLY RECEIVED → FULLY RECEIVED → CLOSED
  - PO approval process (admin approval before sending to supplier)
  - PO printing / PDF export to send to suppliers

#### 3. Goods Receiving Note (GRN)
- **What's missing:** Stock-in records exist but aren't linked to any PO. No partial receiving, no discrepancy tracking.
- **What to build:**
  - GRN creation linked to a PO
  - Partial receiving (receive 50 of 100 ordered, PO stays "PARTIALLY RECEIVED")
  - Quantity discrepancy alerts (ordered 100, received 95)
  - Auto-update `qoh` in PRODUCTS on receiving

### Priority 2 — Inventory Control Features (Should Have)

#### 4. Reorder Point & Low Stock Alerts
- **What's missing:** No minimum stock levels, no reorder points, no alerts when stock is running low.
- **What to build:**
  - Add `min_qty` (reorder point) and `max_qty` fields to PRODUCTS
  - Dashboard widget showing items below reorder point
  - Auto-suggest PO generation for low-stock items

#### 5. Stock Take / Physical Inventory Count
- **What's missing:** No cycle count or full physical inventory feature. Stock adjustments exist but aren't tied to a formal count process.
- **What to build:**
  - Stock take session creation (full or partial/cycle count)
  - Count entry (scanned or manual)
  - Variance report (system qty vs counted qty)
  - Auto-generate stock adjustments from variances

#### 6. Return Management
- **What's missing:** No purchase returns (return to supplier) or customer return tracking.
- **What to build:**
  - Purchase Return / Debit Note (return damaged goods to supplier, update stock)
  - Customer Return / Credit Note (accept returns from customers, update stock)

#### 7. Inventory Reports & Analytics
- **What's missing:** No reporting on stock movements, aging, valuation, or turnover.
- **What to build:**
  - Stock Movement Report (all in/out/adj for a product in a date range)
  - Stock Valuation Report (total inventory value by cost price)
  - Slow-Moving / Dead Stock Report (items with no movement in X days)
  - Stock Aging Report (how long items have been sitting)
  - Category-wise Stock Summary

### Priority 3 — Operational Efficiency (Nice to Have)

#### 8. Delivery Order / Dispatch Note
- **What's missing:** The `stockout` table has delivery fields (DNAME, VEHICLE, PDATE, etc.) but no formal DO workflow.
- **What to build:**
  - Delivery Order creation linked to customer orders
  - DO status tracking (PACKED → DISPATCHED → DELIVERED)
  - Driver assignment and vehicle tracking
  - DO printing

#### 9. Stock Reservation
- **What's missing:** No mechanism to reserve stock for pending orders. Two people can order the same last unit.
- **What to build:**
  - Reserve stock when order is placed
  - Release reserved stock if order is cancelled/deleted
  - Show "available qty" (qoh minus reserved) on product pages

#### 10. Batch & Expiry Tracking
- **What's missing:** BATCHNO and EXPDATE fields exist in stockin/stockout/stockadj tables but there's no active management UI.
- **What to build:**
  - Batch tracking per product (FIFO enforcement)
  - Expiry date alerts (items expiring in X days)
  - Dashboard widget for near-expiry items

#### 11. Warehouse Location Management
- **What's missing:** Products have a `rack` field (free-text) but no structured location hierarchy.
- **What to build:**
  - Location master (Zone → Aisle → Rack → Bin)
  - Product-to-location mapping
  - Location-based picking list for orders

#### 12. Barcode / Label Printing
- **What's missing:** Products have barcodes but no label generation/printing feature.
- **What to build:**
  - Barcode label template designer
  - Bulk label printing (for stock-in batches)
  - Price tag printing with barcode

---

## Recommended Implementation Order

```
Phase 1 - Procurement Foundation
├── 1. Supplier Management (supplier table + CRUD)
├── 2. Purchase Order System (PO lifecycle)
└── 3. Goods Receiving Note (GRN linked to PO)

Phase 2 - Inventory Control
├── 4. Reorder Point & Low Stock Alerts
├── 5. Stock Take / Physical Count
└── 6. Return Management

Phase 3 - Reporting & Analytics
└── 7. Inventory Reports (movement, valuation, aging, slow-moving)

Phase 4 - Operational Efficiency
├── 8. Delivery Order System
├── 9. Stock Reservation
├── 10. Batch & Expiry Management
├── 11. Warehouse Location Management
└── 12. Barcode / Label Printing
```

---

## Suggested New Database Tables

```sql
-- Supplier Master
CREATE TABLE supplier (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  contact_person VARCHAR(100),
  phone VARCHAR(50),
  email VARCHAR(100),
  address TEXT,
  payment_terms VARCHAR(50),
  lead_time_days INT DEFAULT 0,
  status ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Purchase Order Header
CREATE TABLE purchase_order (
  id INT AUTO_INCREMENT PRIMARY KEY,
  po_number VARCHAR(50) NOT NULL UNIQUE,
  supplier_id INT NOT NULL,
  order_date DATE NOT NULL,
  expected_date DATE,
  status ENUM('DRAFT','APPROVED','PARTIALLY_RECEIVED','RECEIVED','CLOSED','CANCELLED') DEFAULT 'DRAFT',
  total_amount DOUBLE(15,2) DEFAULT 0.00,
  remark TEXT,
  created_by VARCHAR(50),
  approved_by VARCHAR(50),
  approved_date DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES supplier(id)
);

-- Purchase Order Line Items
CREATE TABLE purchase_order_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  po_id INT NOT NULL,
  barcode VARCHAR(50) NOT NULL,
  product_desc VARCHAR(100),
  qty_ordered DOUBLE(8,2) NOT NULL,
  qty_received DOUBLE(8,2) DEFAULT 0.00,
  unit_cost DOUBLE(10,2) NOT NULL,
  uom VARCHAR(20),
  remark VARCHAR(200),
  FOREIGN KEY (po_id) REFERENCES purchase_order(id)
);

-- Goods Receiving Note
CREATE TABLE grn (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grn_number VARCHAR(50) NOT NULL UNIQUE,
  po_id INT,
  supplier_id INT NOT NULL,
  receive_date DATE NOT NULL,
  received_by VARCHAR(50),
  remark TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (po_id) REFERENCES purchase_order(id),
  FOREIGN KEY (supplier_id) REFERENCES supplier(id)
);

-- GRN Line Items
CREATE TABLE grn_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grn_id INT NOT NULL,
  po_item_id INT,
  barcode VARCHAR(50) NOT NULL,
  product_desc VARCHAR(100),
  qty_received DOUBLE(8,2) NOT NULL,
  qty_rejected DOUBLE(8,2) DEFAULT 0.00,
  unit_cost DOUBLE(10,2),
  batch_no VARCHAR(16),
  exp_date DATE,
  rack_location VARCHAR(70),
  remark VARCHAR(200),
  FOREIGN KEY (grn_id) REFERENCES grn(id),
  FOREIGN KEY (po_item_id) REFERENCES purchase_order_item(id)
);
```
