# Credit Management System - Testing Guide

## ✅ System Overview

The complete credit, invoice, and returns management system has been implemented with the following components:

### Backend Components
- **CreditController** - Credit payment recording, history, limit management
- **InvoiceController** - Complete CRUD for invoices
- **ReturnController** - Return workflow with approval/rejection
- **SaleController** - Enhanced to create credit transactions automatically

### Frontend Components
- **SalesPage** - Credit limit validation, real-time credit display
- **AccountsManagementPage** - Credit, invoice, and returns management
- **SideBar** - Navigation to Accounts Management

---

## 🧪 Test Scenarios

### Test 1: Partial Payment with Credit (No Limit)
**Objective:** Verify a customer without a credit limit can accept credit

**Steps:**
1. Go to Sales Page
2. Add products totaling Ksh 10,000
3. Select a customer with NO credit limit set
4. Enter payment of Ksh 6,000
5. System should allow credit application

**Expected Results:**
- ✅ Credit info shows "Available Credit: Unlimited"
- ✅ No warning about limit
- ✅ Confirmation prompt: "Payment short by Ksh 4,000. Add to customer account?"
- ✅ Sale succeeds
- ✅ Receipt shows "Balance Due: Ksh 4,000"
- ✅ Success message: "Sale processed! Ksh 4,000 added to customer credit balance"

**Database Verification:**
```sql
SELECT * FROM credit_transactions WHERE customer_id = X AND type = 'credit' ORDER BY created_at DESC LIMIT 1;
-- Should show: amount = 4000, balance_after = 4000
```

---

### Test 2: Credit Limit - Within Limit
**Objective:** Verify credit can be applied when within limit

**Setup:**
- Customer has credit_limit = 10,000
- Customer current credit_balance = 2,000

**Steps:**
1. Go to Sales Page
2. Select customer with limit Ksh 10,000 and balance Ksh 2,000
3. Add products totaling Ksh 10,000
4. Enter payment of Ksh 5,000
5. System should show:
   - Available Credit: Ksh 8,000 (green)
   - Can apply Ksh 5,000 credit

**Expected Results:**
- ✅ No limit warning
- ✅ Confirmation prompt appears
- ✅ Sale succeeds
- ✅ New balance: Ksh 7,000 (2,000 + 5,000)

---

### Test 3: Credit Limit - Would Exceed Limit
**Objective:** Verify credit is BLOCKED when it would exceed limit

**Setup:**
- Customer has credit_limit = 10,000
- Customer current credit_balance = 8,000

**Steps:**
1. Go to Sales Page
2. Select customer with limit Ksh 10,000 and balance Ksh 8,000
3. Add products totaling Ksh 10,000
4. Enter payment of Ksh 5,000
5. System should show:
   - Available Credit: Ksh 2,000 (red warning)
   - ⚠️ "Customer has exceeded credit limit!" alert

**Expected Results:**
- ✅ Error shows: "⚠️ CREDIT LIMIT EXCEEDED!"
- ✅ Details show would exceed by Ksh 3,000
- ✅ Sale is BLOCKED
- ✅ User must either:
   - Collect full payment, OR
   - Go to Accounts Management to increase limit

---

### Test 4: Credit Limit - At Limit
**Objective:** Verify no additional credit when at limit

**Setup:**
- Customer has credit_limit = 10,000
- Customer current credit_balance = 10,000 (at limit)

**Steps:**
1. Go to Sales Page
2. Select customer with limit Ksh 10,000 and balance Ksh 10,000
3. Add products and try partial payment
4. System should show:
   - Available Credit: Ksh 0 (red)
   - ⚠️ "Customer has exceeded credit limit!" warning

**Expected Results:**
- ✅ Credit blocked
- ✅ Error prevents sale
- ✅ Admin must increase limit first

---

### Test 5: Record Payment
**Objective:** Verify payment recording reduces credit balance

**Setup:**
- Customer with balance Ksh 4,000 and limit Ksh 10,000

**Steps:**
1. Go to Accounts Management → Credit Management
2. Find customer with balance
3. Click payment button (💰 icon)
4. Enter:
   - Amount: Ksh 4,000
   - Payment Method: M-Pesa
   - Transaction Number: ABC123
5. Click "Record Payment"

**Expected Results:**
- ✅ Modal closes
- ✅ Success message appears
- ✅ Customer balance updates to Ksh 0
- ✅ Credit transaction created with type='payment'
- ✅ Transaction shows in credit history

---

### Test 6: View Credit History
**Objective:** Verify all transactions are tracked and visible

**Steps:**
1. Go to Accounts Management → Credit Management
2. Find customer with transactions
3. Click history button (⏱️ icon)

**Expected Results:**
- ✅ Shows all transactions (credit, payment, adjustment)
- ✅ Each shows: date, type, amount, balance, notes
- ✅ Sorted by most recent first

---

### Test 7: Adjust Credit Balance
**Objective:** Verify manual adjustments for corrections

**Steps:**
1. Go to Accounts Management → Credit Management
2. Click adjust balance button (⚖️ icon)
3. Enter adjustment: -500 (to reduce balance)
4. Enter reason: "Returned items"
5. Click confirm

**Expected Results:**
- ✅ Balance decreases by 500
- ✅ Transaction created with type='adjustment'
- ✅ Notes show the reason
- ✅ Appears in history

---

### Test 8: Update Credit Limit
**Objective:** Verify admin can increase customer credit limits

**Steps:**
1. Go to Accounts Management → Credit Management
2. Click "Set Credit Limit" button
3. Select customer
4. Set credit_limit = 15,000
5. Add notes: "Approved by manager"
6. Click "Save"

**Expected Results:**
- ✅ Customer's credit_limit updates to 15,000
- ✅ Available credit adjusts accordingly
- ✅ Adjustment transaction created with details in notes

---

### Test 9: Create Invoice
**Objective:** Verify invoice creation workflow

**Steps:**
1. Go to Accounts Management → Invoices tab
2. Click "Create Invoice"
3. Select customer
4. Add line items (product, qty, price)
5. Set due date
6. Click "Create"

**Expected Results:**
- ✅ Invoice created with auto-generated number (INV-2026-000001)
- ✅ Status = 'draft'
- ✅ Balance = total (not yet paid)

---

### Test 10: Record Invoice Payment
**Objective:** Verify invoice payments reduce balance

**Steps:**
1. In Invoices tab, find a draft invoice
2. Click on invoice
3. Click "Record Payment"
4. Enter payment amount
5. Optionally check "Apply Customer Credit"
6. Submit

**Expected Results:**
- ✅ Invoice paid_amount increases
- ✅ Invoice balance decreases
- ✅ Status changes from 'draft' to 'sent' then 'paid'
- ✅ If credit applied, customer balance also updates
- ✅ Credit transaction created linking to invoice

---

### Test 11: Create and Approve Return
**Objective:** Verify return workflow with credit application

**Setup:**
- Customer has returned items from a sale

**Steps:**
1. Go to Accounts Management → Returns tab
2. Click "Process Return"
3. Select customer and sale
4. Add return items (product, qty, reason)
5. Set refund_method = "credit"
6. Submit

**Expected Results:**
- ✅ Return created with auto-generated number (RET-2026-000001)
- ✅ Status = 'pending'
- ✅ Return appears in list

**Then Approve:**
1. Click approve button (✓ icon)
2. Confirm approval

**Expected Results:**
- ✅ Return status changes to 'approved'
- ✅ Product inventory is restored
- ✅ Customer credit_balance increases by refund_amount
- ✅ Credit transaction created with type='credit'
- ✅ Notes show return number

---

### Test 12: Reject Return
**Objective:** Verify return rejection workflow

**Steps:**
1. Find pending return
2. Click reject button (✗ icon)
3. Enter rejection reason: "Items in good condition"
4. Submit

**Expected Results:**
- ✅ Return status changes to 'rejected'
- ✅ NO inventory restored
- ✅ NO credit added
- ✅ Rejection reason saved in notes

---

## 🔍 Database Verification Checklist

After testing, verify data integrity:

```sql
-- Check recent sales with credit
SELECT s.id, s.customer_id, s.total, s.amount_paid, s.balance_due, 
       COUNT(ct.id) as credit_transactions
FROM sales s
LEFT JOIN credit_transactions ct ON ct.sale_id = s.id
WHERE s.balance_due > 0
GROUP BY s.id
ORDER BY s.created_at DESC
LIMIT 5;

-- Check credit transactions
SELECT * FROM credit_transactions
ORDER BY created_at DESC
LIMIT 10;

-- Check customer credit status
SELECT id, name, credit_balance, credit_limit, 
       (credit_limit - credit_balance) as available_credit
FROM customers
WHERE credit_balance > 0 OR credit_limit > 0
LIMIT 10;

-- Check invoices
SELECT id, invoice_number, customer_id, total, paid_amount, 
       balance, status
FROM invoices
ORDER BY created_at DESC
LIMIT 5;

-- Check returns
SELECT id, return_number, customer_id, refund_amount, 
       refund_method, status
FROM returns
ORDER BY created_at DESC
LIMIT 5;
```

---

## 🚨 Edge Cases to Test

### Edge Case 1: Multiple Sales for Same Customer
- Create 2-3 partial payments for same customer
- Verify credit balance accumulates correctly
- Verify all credit transactions are created

### Edge Case 2: Customer Exceeds Limit Then Pays Down
1. Customer at limit (Ksh 10,000)
2. Try to add Ksh 2,000 → BLOCKED
3. Go to Accounts Management
4. Record payment of Ksh 5,000
5. Balance now Ksh 5,000, available Ksh 5,000
6. Go back to Sales
7. Try to add Ksh 5,000 → SUCCEEDS

### Edge Case 3: Zero Limit vs No Limit
- Customer A: credit_limit = 0 → Should reject credit
- Customer B: credit_limit = NULL → Should allow unlimited credit
- Test both scenarios

### Edge Case 4: Concurrent Transactions
- Multiple sales in quick succession
- Verify balances are accurate
- No race condition issues

### Edge Case 5: Adjustment Making Balance Negative
- Try to adjust balance by -5000 when only 2000 owed
- Should show error: "Adjustment would result in negative balance"

---

## ✅ Success Criteria

The system is considered complete when:

1. ✅ All credit transactions are automatically logged
2. ✅ Credit limits are enforced (sales blocked if exceeded)
3. ✅ Real-time credit display shows accurate balance and limit
4. ✅ Payments reduce customer balance atomically
5. ✅ Invoices track payment status correctly
6. ✅ Returns integrate with credit system
7. ✅ No orphaned records (all foreign keys intact)
8. ✅ Transaction history is complete and accurate
9. ✅ Admin can manage limits and adjustments
10. ✅ User experience is clear with proper warnings

---

## 📋 Rollout Checklist

Before going to production:

- [ ] All tests pass (above scenarios)
- [ ] Database backups created
- [ ] API response times acceptable
- [ ] Error messages are user-friendly
- [ ] Staff trained on credit management
- [ ] Customer communication sent
- [ ] Accounts Management documented
- [ ] Monitoring alerts set up
- [ ] Rollback plan prepared

---

## 📞 Support

For issues or questions:
1. Check credit transaction history (Accounts → Credit History)
2. Verify customer credit_limit is set
3. Review error messages for specific issues
4. Check database for data integrity
5. Contact development team with transaction IDs

