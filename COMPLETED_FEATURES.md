# Completed Features

This document tracks features that are already implemented in this project.

## Platform And Access
- Authentication flow with login, password reset, and profile management.
- Role and permission management (RBAC) with route-level authorization.
- User management with role/permission assignment.
- Outlet management with current-outlet switching.

## Master Data Modules
- Unit management.
- Product category + product management.
- Product variants (size/color/material support).
- Product media upload support.
- Vendor management (with vendor type support).
- Garment type management with measurement templates.

## Customer Module
- Customer CRUD.
- Customer type support (retail/wholesale/custom).
- Customer measurement capture and update flows.
- Customer garment-type mapping.

## Order Management
- Multi-step order wizard (customer, items, payment, review).
- Mixed order items support: readymade.
- Mixed order items support: fabric.
- Mixed order items support: custom tailoring with measurement payloads.
- Worker assignment and workflow timestamps/status transitions.
- Payment tracking: advance payment.
- Payment tracking: discount amount.
- Payment tracking: remaining payment handling on delivery.
- Bill generation: customer bill.
- Bill generation: worker bill.
- Bill generation: office bill.
- Assigned jobs view for worker-oriented tracking.

## Inventory Management
- Inventory transaction entry: Stock In.
- Inventory transaction entry: Stock Out.
- Inventory transaction entry: Transfer.
- Inventory transaction entry: Adjustment.
- Variant-aware stock operations.
- Location-based inventory model (warehouse/factory/outlet).
- Stock summary, reporting filter, and low-stock alerts.
- Inventory types, transaction items, and reorder-level support.

## Raw Material Purchase
- Raw material purchase create/edit/index flows.
- Multi-item purchase rows with variant support.
- Procurement/process workflow support.
- Bill file workflow support.

## Manufacture Unit
- Manufacture stock board and reporting filter.
- Transfer raw material for production flow.
- Production target product/variant capture.
- Production transfer log and transfer status updates.
- Final goods transfer to destination locations.
- Production output distribution tracking.

## Dashboard And Reporting
- Dashboard KPIs and filterable summaries.
- Reporting filter component reused across modules.
- Tabbed module views where applicable.
