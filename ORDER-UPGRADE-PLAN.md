# Order System Upgrade - Implementation Plan

## Goal
Transform the order system from manual admin-controlled status changes to a **system-driven**, **action-based** workflow where:
- Order status is automatically determined by system actions (not manual dropdown selection)
- Admin can add a note on every action/update (with full history/timeline)
- Payment state and order lifecycle are independent
- Supports all order types: online stores, POS, COD, prepaid, returns, refunds, warehouses, multi-vendor

---

## Phase 1: Database & Enums

### 1.1 Create OrderStatusEnum
**File:** `app/Enums/OrderStatus.php`
- Define all statuses as enum cases with:
  - `label()` → display name
  - `slug()` → URL-friendly
  - `canTransitionTo()` → allowed next statuses
  - Database value mapping

### 1.2 Create PaymentStatusEnum  
**File:** `app/Enums/PaymentStatus.php`
- pending, paid, partially_refunded, refunded, failed, cancelled

### 1.3 Create OrderNote Migration
**File:** `database/migrations/xxxx_create_order_notes_table.php`
- `id`, `order_id` (FK), `user_id` (nullable FK), `content` (text), `type` (info/warning/danger/success), `source` (admin/system/courier/customer), `metadata` (json nullable), `created_at`, `updated_at`

### 1.4 Add order_type to orders table (optional migration)
**File:** `database/migrations/xxxx_add_order_type_to_orders.php`
- `order_type` enum: online, pos, cod (default: online)

---

## Phase 2: Models

### 2.1 Create OrderNote Model
**File:** `app/Models/OrderNote.php`
- belongsTo Order, belongsTo User
- Scopes: byType, bySource, latest

### 2.2 Update Order Model
**File:** `app/Models/Order.php`
- Add `notes()` → hasMany OrderNote
- Add `addNote($content, $type, $source, $userId = null)` method
- Add `transitionTo(OrderStatus $newStatus, $note = null, $userId = null)` 
- Add status helper methods (isPending, isConfirmed, canShip, etc.)
- Add `getAvailableActions()` → returns list of valid next actions based on current status
- Add `order_type` cast
- Cast order_status using OrderStatusEnum
- Cast payment_status using PaymentStatusEnum

---

## Phase 3: Controller Refactoring

### 3.1 OrderController Updates
**File:** `app/Http/Controllers/Admin/OrderController.php`

**REMOVE/DEPRECATE:**
- `updateSingleStatus()` → replaced by action methods or repurpose as internal

**ADD Action Methods (each auto-transitions + records note):**
- `confirmOrder(Request $request)` → Pending → Confirmed
- `startPicking(Request $request)` → Confirmed → Picking  
- `startPacking(Request $request)` → Picking → Packing
- `markPacked(Request $request)` → Packing → Packed
- `shipOrder(Request $request)` → Packed → Shipped (with courier info)
- `markOutForDelivery(Request $request)` → Shipped → Out for Delivery
- `markDelivered(Request $request)` → Out for Delivery → Delivered
- `markCompleted(Request $request)` → Delivered → Completed
- `requestReturn(Request $request)` → Delivered → Return Requested
- `approveReturn(Request $request)` → Return Requested → Return Approved
- `markReturned(Request $request)` → Return Approved → Returned
- `closeOrder(Request $request)` → Returned → Closed
- `cancelOrder(Request $request)` → Pending/Confirmed/Picking/Packing → Cancelled
- `addOrderNote(Request $request)` → Add note without status change

**KEEP (with updates):**
- `updatePaymentStatus()` → keep, payment is admin-controlled
- `order_process()` → update to use new action methods
- `handleStockChange()` → update to use enum

### 3.2 Routes
**File:** `routes/web.php`
- Add POST routes for each action method
- Keep existing routes for backward compatibility

---

## Phase 4: Views

### 4.1 Invoice View
**File:** `resources/views/backEnd/order/invoice.blade.php`

**REMOVE:**
- Status dropdown (`#order_status_*`) and "Update Status" button
- `updateOrderStatus()` JS function

**ADD:**
- Action buttons section (showing valid next actions based on current status)
- Admin note input field (textarea) + "Add Note" button
- Note history timeline (showing all OrderNotes with timestamp and user)
- Status badge (read-only, system-managed)

### 4.2 Process View (optional updates)
**File:** `resources/views/backEnd/order/process.blade.php`
- Update to use action buttons pattern

---

## Phase 5: Testing Checklist

- [ ] Online prepaid order: Pending → Confirm → Pick → Pack → Ship → Deliver → Complete
- [ ] COD order: Pending → Confirm → Pick → Pack → Ship → Deliver → Complete
- [ ] Payment status independent of order status
- [ ] POS order: directly to Completed (skip fulfillment)
- [ ] Return flow: Delivered → Return Requested → Return Approved → Returned → Closed
- [ ] Cancellation: can cancel only before Shipped
- [ ] Admin notes appear in timeline for each action
- [ ] Stock handling still works correctly
- [ ] Fund transactions still work correctly

---

## Implementation Order
1. ✅ Create Enums (OrderStatusEnum, PaymentStatusEnum)
2. ✅ Create OrderNote migration + model
3. ✅ Update Order model
4. ✅ Create action methods in OrderController
5. ✅ Add routes
6. ✅ Update invoice view
7. ✅ Run migrations & test
