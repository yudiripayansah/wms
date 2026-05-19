# Allocation Preview Specification

## Form 1 — Group by Location

Example:

LOCATION A01
- barcode A
- barcode B
- barcode C
TOTAL LOCATION A01 = 20

LOCATION A02
- barcode D
- barcode E
TOTAL LOCATION A02 = 14

Rules:
- grouped visually
- subtotal displayed once per group
- no duplicate subtotal rows

---

## Form 2 — Group by Barcode

Example:

BARCODE 123456
- location A01
- location A02
- location B01
TOTAL BARCODE 123456 = 10

BARCODE 999999
- location C01
- location D01
TOTAL BARCODE 999999 = 7

Rules:
- grouped visually
- subtotal displayed once per group
- no duplicate subtotal rows

---

## Header Layout

Top Left:
- customer
- distribution
- release
- brand
- sales associate
- route

Top Right:
- large allocation number

---

## Table Layout

Columns:
- no
- barcode
- article
- color
- size
- qty
- location
- bin

Compact warehouse-print style.