# Inventory System - Implementation Plan

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

## Implementation Scope — 5 Features, 2 Phases

### Phase 1 — Procurement Foundation

#### Feature 1: Supplier Management
- **Problem:** No `supplier` table. Supplier data is free-text in `stockin` records, causing inconsistency.
- **Build:**
  - `supplier` table with code, name, contact person, phone, email, address, payment terms, lead time, status
  - Admin page: list all suppliers, create, edit, delete (soft delete via status)
  - Supplier dropdown in PO creation and stock-in forms

#### Feature 2: Purchase Order (PO) Lifecycle
- **Problem:** No formal PO system. Can record stock-in but can't create/track/approve purchase orders.
- **Build:**
  - **PO Creation** — Select supplier, add line items (product, qty, unit cost)
  - **PO Status Workflow** — DRAFT → APPROVED → PARTIALLY RECEIVED → RECEIVED → CLOSED / CANCELLED
  - **PO Approval** — Admin approves before sending to supplier
  - **Goods Receiving (GRN)** — Receive against a PO, partial receiving supported
  - **Discrepancy Tracking** — Ordered vs received qty mismatch alerts
  - **Auto QOH Update** — `PRODUCTS.qoh` updated automatically on receiving
  - **PO Listing** — Admin page to view/filter/search all POs by status, supplier, date

---

### Phase 2 — Inventory Control

#### Feature 3: Reorder Point & Low Stock Alerts
- **Problem:** No minimum stock levels, no reorder points, no alerts when stock runs low.
- **Build:**
  - Add `min_qty` (reorder point) and `max_qty` fields to `PRODUCTS` table
  - Editable per product in admin
  - Dashboard widget showing all items where `qoh <= min_qty`
  - Auto-suggest PO generation for low-stock items (pre-fill PO with suggested quantities)

#### Feature 4: Stock Take / Physical Inventory Count
- **Problem:** No formal physical count process. Stock adjustments exist but aren't tied to count sessions.
- **Build:**
  - **Stock Take Session** — Create a count session (full inventory or filtered by category/location)
  - **Count Entry** — Enter counted qty per product (manual entry)
  - **Variance Report** — Compare system qty (`qoh`) vs counted qty, show differences
  - **Apply Adjustments** — Auto-generate `stockadj` records from variances to correct `qoh`
  - **Session Status** — OPEN → IN PROGRESS → COMPLETED

#### Feature 5: Stock Loss (Spoilage / Damage / Theft)
- **Problem:** Stock adjustments exist but have no categorization for loss reasons.
- **Build:**
  - Dedicated "Stock Loss" page in admin
  - Loss reason categories: SPOILAGE, DAMAGE, THEFT, EXPIRED, OTHER
  - Record: product, qty lost, reason, remark, date, recorded by
  - Auto-deduct from `PRODUCTS.qoh`
  - Stores in `stockadj` table with reason category field
  - Loss history log with filtering by reason, date range, product

---

## New Database Tables

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

-- Goods Receiving Note Header
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

-- Stock Take Session
CREATE TABLE stock_take (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_code VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(200),
  type ENUM('FULL','PARTIAL') DEFAULT 'FULL',
  filter_cat VARCHAR(50) DEFAULT NULL,
  filter_location VARCHAR(70) DEFAULT NULL,
  status ENUM('OPEN','IN_PROGRESS','COMPLETED') DEFAULT 'OPEN',
  created_by VARCHAR(50),
  completed_by VARCHAR(50),
  completed_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Stock Take Line Items (one row per product counted)
CREATE TABLE stock_take_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stock_take_id INT NOT NULL,
  barcode VARCHAR(50) NOT NULL,
  product_desc VARCHAR(100),
  system_qty DOUBLE(8,2) NOT NULL,
  counted_qty DOUBLE(8,2) DEFAULT NULL,
  variance DOUBLE(8,2) DEFAULT NULL,
  adj_applied TINYINT(1) DEFAULT 0,
  remark VARCHAR(200),
  counted_by VARCHAR(50),
  counted_at DATETIME,
  FOREIGN KEY (stock_take_id) REFERENCES stock_take(id)
);
```

### Modified Existing Table

```sql
-- Add reorder point fields to PRODUCTS
ALTER TABLE PRODUCTS
  ADD COLUMN min_qty DOUBLE(8,2) DEFAULT 0.00,
  ADD COLUMN max_qty DOUBLE(8,2) DEFAULT 0.00;

-- Add loss reason field to stockadj
ALTER TABLE stockadj
  ADD COLUMN LOSS_REASON ENUM('SPOILAGE','DAMAGE','THEFT','EXPIRED','OTHER','ADJUSTMENT') DEFAULT 'ADJUSTMENT';
```

---

## Admin Page Structure

```
/admin/
├── dashboard.php              (existing — add low stock widget)
├── supplier.php               (NEW — supplier list + CRUD)
├── supplier_ajax.php          (NEW — supplier AJAX endpoints)
├── po.php                     (NEW — PO listing page)
├── po_detail.php              (NEW — PO create/edit/view)
├── po_ajax.php                (NEW — PO AJAX endpoints)
├── grn.php                    (NEW — GRN create from PO, receive goods)
├── grn_ajax.php               (NEW — GRN AJAX endpoints)
├── stock_take.php             (NEW — stock take sessions list)
├── stock_take_detail.php      (NEW — count entry + variance report)
├── stock_take_ajax.php        (NEW — stock take AJAX endpoints)
├── stock_loss.php             (NEW — record & view stock losses)
├── stock_loss_ajax.php        (NEW — stock loss AJAX endpoints)
└── ... (existing files unchanged)
```

---

## Implementation Order

```
Phase 1 — Procurement Foundation
  Step 1: Create supplier table + supplier.php + supplier_ajax.php
  Step 2: Create PO tables + po.php + po_detail.php + po_ajax.php
  Step 3: Create GRN tables + grn.php + grn_ajax.php (linked to PO)
  Step 4: Wire up auto QOH update on GRN receive

Phase 2 — Inventory Control
  Step 5: ALTER PRODUCTS table (min_qty, max_qty) + low stock dashboard widget
  Step 6: Create stock_take tables + stock_take.php + stock_take_detail.php + stock_take_ajax.php
  Step 7: ALTER stockadj (LOSS_REASON) + stock_loss.php + stock_loss_ajax.php
```
