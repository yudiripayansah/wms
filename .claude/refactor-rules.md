# Refactor Rules

## Naming Rules

Replace globally:
- Product -> Inventory
- Products -> Inventories
- product_id -> inventory_id

But:
- Do NOT rename database tables blindly.
- Check foreign key dependencies first.
- Check exports/imports first.
- Check allocation services first.

## Migration Rules

- Prefer additive migrations.
- Preserve old data.
- Avoid destructive migration unless safe.
- Add indexes for:
  - barcode
  - sku
  - location

## Allocation Rules

Grouping must support:
- subtotal qty per location
- subtotal qty per barcode

Sorting:
- location ASC
- barcode ASC

## PDF Rules

- A4 optimized
- Compact table
- Consistent widths
- Printable layout
- Page number footer
- Avoid overflow rows