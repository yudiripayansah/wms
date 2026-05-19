# Project Context

## Project Type
Warehouse Management System (WMS)

## Stack
- Laravel
- FilamentPHP
- MySQL
- TailwindCSS
- Livewire

## Main Purpose
System for:
- Inventory management
- Warehouse stock management
- Allocation management
- Picking list generation
- PDF allocation preview/export
- Warehouse operational workflow

## Main Modules
- Inventory
- Stocks
- Allocation
- Users
- Warehouse Locations
- Bin Management
- Export / Print

## Important Notes
- Allocation is critical business flow.
- Stock qty calculations must remain accurate.
- Barcode search must remain fast.
- Grouping logic is important for warehouse picking process.
- PDF print layout must be compact and readable.

## Refactor Goal
Current Product structure should become Inventory structure.
Current stock structure should become warehouse inventory stock structure.

## Allocation Preview Goal
Need grouped warehouse picking preview:
1. Group by location
2. Group by barcode
with subtotal quantities.