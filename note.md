# Complete Order & Payment Status Mapping (POS + Online + COD)
---

# 1. Payment Status

## Status List

| Status             | Description                            |
| ------------------ | -------------------------------------- |
| Pending            | Waiting for payment.                   |
| Paid               | Payment received successfully.         |
| Partially Refunded | Part of the payment has been refunded. |
| Refunded           | Full payment refunded.                 |
| Failed             | Payment attempt failed.                |
| Cancelled          | Payment cancelled or expired.          |

### Payment Flow

```text
Pending
 ├── Failed
 ├── Cancelled
 └── Paid
      ├── Partially Refunded
      └── Refunded
```

---

# 2. Order Status

## Status List

| Status           | Description                                     |
| ---------------- | ----------------------------------------------- |
| Pending          | Order created but not yet confirmed.            |
| Confirmed        | Order accepted and ready for fulfillment.       |
| Picking          | Warehouse picking products.                     |
| Packing          | Warehouse packing products.                     |
| Packed           | Packed and waiting for courier.                 |
| Shipped          | Handed over to courier.                         |
| Out for Delivery | Courier delivering order.                       |
| Delivered        | Customer received order.                        |
| Completed        | Order successfully finished.                    |
| Return Requested | Customer requested a return.                    |
| Return Approved  | Return request approved.                        |
| Returned         | Returned item received.                         |
| Cancelled        | Order cancelled before shipment.                |
| Closed           | Order closed after refund or return completion. |

---

# Order Flow

```text
Pending
    ↓
Confirmed
    ↓
Picking
    ↓
Packing
    ↓
Packed
    ↓
Shipped
    ↓
Out for Delivery
    ↓
Delivered
   ├───────────────► Completed
   │
   └────► Return Requested
              ↓
       Return Approved
              ↓
          Returned
              ↓
            Closed
```

---

# Cancellation Flow

```text
Pending
    ↓
Cancelled
```

or

```text
Confirmed
    ↓
Cancelled
```

or

```text
Picking
    ↓
Cancelled
```

or

```text
Packing
    ↓
Cancelled
```

> Once an order is **Shipped**, it generally should not be cancelled. Instead, it follows the return workflow after delivery.

---

# 3. Online Order (Prepaid)

| Customer Stage        | Payment Status | Order Status     |
| --------------------- | -------------- | ---------------- |
| Customer places order | Pending        | Pending          |
| Payment successful    | Paid           | Confirmed        |
| Warehouse Picking     | Paid           | Picking          |
| Warehouse Packing     | Paid           | Packing          |
| Packed                | Paid           | Packed           |
| Courier Pickup        | Paid           | Shipped          |
| Out for Delivery      | Paid           | Out for Delivery |
| Delivered             | Paid           | Delivered        |
| Return window expires | Paid           | Completed        |
| Return Requested      | Paid           | Return Requested |
| Return Approved       | Paid           | Return Approved  |
| Item Returned         | Paid           | Returned         |
| Refund Completed      | Refunded       | Closed           |

---

# 4. Online Order (Cash on Delivery - COD)

| Customer Stage        | Payment Status | Order Status     |
| --------------------- | -------------- | ---------------- |
| Customer places order | Pending        | Pending          |
| Order confirmed       | Pending        | Confirmed        |
| Warehouse Picking     | Pending        | Picking          |
| Warehouse Packing     | Pending        | Packing          |
| Packed                | Pending        | Packed           |
| Courier Pickup        | Pending        | Shipped          |
| Out for Delivery      | Pending        | Out for Delivery |
| Customer pays courier | Paid           | Delivered        |
| Return window expires | Paid           | Completed        |
| Return Requested      | Paid           | Return Requested |
| Return Approved       | Paid           | Return Approved  |
| Item Returned         | Paid           | Returned         |
| Refund Completed      | Refunded       | Closed           |

> **Key Difference:** For COD orders, the payment remains **Pending** until the customer pays the courier upon delivery.

---

# 5. POS Order (Walk-in Customer)

## Standard POS

| Customer Stage | Payment Status       | Order Status         |
| -------------- | -------------------- | -------------------- |
| Sale Created   | Pending *(optional)* | Pending *(optional)* |
| Customer Pays  | Paid                 | Completed            |

---

## POS Return

| Customer Stage   | Payment Status | Order Status     |
| ---------------- | -------------- | ---------------- |
| Return Requested | Paid           | Return Requested |
| Return Approved  | Paid           | Return Approved  |
| Product Returned | Paid           | Returned         |
| Refund Completed | Refunded       | Closed           |

> In most POS systems, products are handed to the customer immediately after payment, so there is no picking, packing, or shipping.

---

# 6. Database Enums

## payment_status

```text
pending
paid
partially_refunded
refunded
failed
cancelled
```

---

## order_status

```text
pending
confirmed
picking
packing
packed
shipped
out_for_delivery
delivered
completed
return_requested
return_approved
returned
cancelled
closed
```

---

# 7. Summary

| Order Type       | Payment Flow                      | Order Flow                                                                                            |
| ---------------- | --------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Online (Prepaid) | Pending → Paid                    | Pending → Confirmed → Picking → Packing → Packed → Shipped → Out for Delivery → Delivered → Completed |
| Online (COD)     | Pending → Paid *(after delivery)* | Pending → Confirmed → Picking → Packing → Packed → Shipped → Out for Delivery → Delivered → Completed |
| POS              | Paid *(or Pending → Paid)*        | Completed                                                                                             |
| Return           | Paid → Refunded                   | Return Requested → Return Approved → Returned → Closed                                                |
| Cancellation     | Pending/Confirmed → Cancelled     | Pending/Confirmed/Picking/Packing → Cancelled                                                         |

