# Application Features Documentation

## Admin App (`/admin/`)

1. **Authentication & Session Management** — Login/logout with role-based access (Admin, Staff, Delivery)
2. **Live Order Dashboard** — Real-time order monitoring with 30-second auto-refresh, sound alerts for unacknowledged orders, search, and action buttons (View, Done, Payment Success, Delete)
3. **User Management** — CRUD for user accounts with role assignment (Admin, Staff, Delivery)
4. **Product Management** — Full product catalog with barcode, category, sub-category, UOM, QOH, rack location, status, and images
5. **Product Category Management** — Create/edit/deactivate categories and sub-categories
6. **Unit of Measurement (UOM) Management** — Create/edit/activate/deactivate UOM entries
7. **Supplier Management** — Supplier directory with CRUD, contact info, and status tracking
8. **Purchase Order (PO) Management** — Full PO lifecycle: DRAFT → APPROVED → PARTIALLY_RECEIVED → RECEIVED → CLOSED → CANCELLED
9. **PO Detail Management** — Line items with product selection, quantity, unit price, and delivery date
10. **Goods Receiving Notes (GRN)** — Receive goods against POs, track received vs. ordered quantities, partial receiving support
11. **Rack Management** — Warehouse rack/location CRUD, product-to-rack assignments
12. **Stock Take Management** — Physical inventory count sessions with DRAFT → SUBMITTED → APPROVED workflow
13. **Stock Loss Reporting** — Track losses by reason (Spoilage, Damage, Theft, Expired, Other)
14. **Product Trends & Analytics** — Top sellers, movement analysis, color-coded threshold indicators (green/yellow/red/black)
15. **Delivery Dashboard** — Tabbed view of Order/Assigned/Done/Completed deliveries with date filtering
16. **Delivery Order Management** — View, filter, assign drivers to delivery orders
17. **Driver Assignment** — Assign/transfer orders to delivery drivers
18. **Delivery Customer Management** — Customer directory for delivery tracking
19. **Delivery Location Management** — Manage delivery location points
20. **Delivery Reports** — Analytics and reporting on delivery performance
21. **Order Detail Viewer** — Full order info with print functionality

---

## Staff App (`/staff/`)

1. **Authentication & Role-Based Access** — Login with support for Admin, Staff, and Delivery roles
2. **Product Category Browsing** — Grid display of categories with images
3. **Product Listing & Search** — Full product catalog with trend indicators, QOH, in-stock status, rack locations; real-time AJAX search by name or barcode
4. **Shopping Cart** — Add/remove products, quantity management, batch selection, max quantity warnings
5. **Order Submission** — Review and submit orders (Purchase or Sales type) with JSON data processing
6. **Stock Take** — Create/manage physical inventory count sessions, product-by-product counting, category filtering, DRAFT/SUBMITTED status
7. **Stock Loss Recording** — Barcode lookup, quantity loss entry with descriptions, staff and outlet tracking
8. **My Deliveries Dashboard** — View assigned delivery orders with date filters, WhatsApp integration for customer contact
9. **Delivery History** — View completed deliveries with status badges
10. **Delivery Order Viewer** — Detailed order items, customer contacts, signature verification
11. **Photo/Work Documentation** — Multi-photo upload for proof of delivery
12. **Digital Signature Capture** — Canvas-based signature drawing, validation, and storage
13. **Delivery Reports** — Multi-tab reports with location/date filtering, export to CSV/Excel/PDF/Print
14. **QR Code Scanner** — Real-time camera-based QR detection, URL opening, text copying
15. **Mobile Bottom Navigation** — Tab-based mobile navigation with quick-access modals
16. **Account Management** — User profile display and role information

---

## Driver App (`/delivery/driver/`)

1. **Driver Authentication** — Separate login with cookie-based session (30-day persistence)
2. **Active Orders Dashboard** — View assigned orders filtered by Today/Yesterday/All, with customer name, address, phone, and WhatsApp link
3. **Delivery Work Flow (Photo Capture)** — Capture up to 3 proof-of-delivery photos with image compression (JPEG quality 75)
4. **Job Completion** — "Job Done" confirmation, updates order status to Done with timestamp
5. **Delivery Order View** — Printable delivery order form with company header, item list, customer info, and signature section
6. **Digital Signature Capture** — HTML5 Canvas signature pad with Bezier curves, saved as PNG
7. **Order History** — View completed (Done/Completed) deliveries with date filtering
8. **Driver Detailed Report** — Line-item breakdown of deliveries with date range, location, and type filters; export support
9. **Driver Summary Report** — Aggregate driver performance statistics with filtering and export
10. **Sidebar Navigation** — Dashboard, History, Reports, Logout

---

## Delivery App (`/delivery/` — management/admin side)

1. **Delivery Admin Dashboard** — Live active orders with 5-second auto-refresh, color-coded status badges (Order/Assigned/Done), multiple dashboard views
2. **Order Creation** — Add new delivery orders with date, order number, customer, location, line items (description, qty, UOM), auto-calculated distance and commission
3. **Order Management** — List/filter/search/delete orders by status (All, Pending, Assigned, Done, Completed)
4. **Deliver Order View** — Advanced order filtering by date range and status with quick action buttons
5. **Driver User Management** — Add/edit/delete driver accounts with full profile (name, email, phone, address, credentials)
6. **Driver Assignment** — Assign orders to drivers, transfer between drivers, view available orders per driver
7. **Customer Management** — Full CRUD for delivery customers with name, location, address, email, phone
8. **Location Management** — Manage delivery areas with postcode, distance rates, and commission/retail rates
9. **User Management** — Admin user account management
10. **Image Management** — View/delete delivery proof images associated with orders
11. **Signature Management** — Digital signature capture and storage linked to orders
12. **Reporting** — Driver detailed and summary reports with date range, location, and type filtering; export to PDF/Excel/Print
13. **Data Synchronization** — Sync functionality between systems
