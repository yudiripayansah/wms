# Architecture Notes

## Main Entities

### Inventory
Represents sellable/stored warehouse inventory.

Fields:
- barcode
- brand
- sku
- article
- color
- size

### Stocks
Represents warehouse stock quantities.

Fields:
- barcode
- qty
- bin
- location

### Allocation
Represents picking allocation.

Allocation should support:
- grouped picking preview
- grouped barcode preview
- PDF export
- subtotal calculations

## Important Rules

- Avoid N+1 queries.
- Use eager loading.
- Keep barcode indexed.
- Qty calculations must remain transactional.
- Preserve data integrity.
- Follow Laravel conventions.
- Follow existing project patterns.