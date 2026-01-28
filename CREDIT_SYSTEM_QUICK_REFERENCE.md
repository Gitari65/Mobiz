# Credit Management System - Quick Reference

## 🚀 Quick Start

### For POS Users (Making Sales)
1. **Add products to cart**
2. **Select customer** (if payment is short)
3. **View credit info** (balance, limit, available credit)
4. **Enter payment amount**
5. **System validates credit limit**
   - ✅ If OK → Confirm credit application
   - ❌ If exceeds → Sale blocked, contact admin
6. **Complete sale** → Receipt shows balance due as credit

### For Admin Users (Managing Credit)
1. **Go to Accounts** (sidebar)
2. **Select tab:**
   - **Credit Management** → View balances, record payments, adjust balances
   - **Invoices** → Create and manage invoices
   - **Returns** → Process and approve returns

---

## 📊 Key Metrics

| Metric | Details |
|--------|---------|
| Credit Limit | Max credit allowed per customer |
| Current Balance | How much customer currently owes |
| Available Credit | How much more credit can be extended |
| Transaction Type | credit / payment / adjustment |
| Auto-Generated Numbers | INV-2026-XXXXXX / RET-2026-XXXXXX |

---

## 🔴 Red Flags

| Situation | Action |
|-----------|--------|
| Customer at/over limit | Increase limit or collect full payment |
| Invoice past due date | Send reminder or apply late fee |
| Return status = pending | Approve or reject (don't delay) |
| Missing transaction | Check credit history for details |

---

## 📱 Common Actions

### Record a Payment
```
Accounts → Credit Management
↓
Find customer with balance
↓
Click payment button (💰)
↓
Enter: Amount, Method, Transaction #
↓
Save → Balance updates immediately
```

### Increase Credit Limit
```
Accounts → Credit Management
↓
Click "Set Credit Limit"
↓
Select customer, enter new limit
↓
Save → Customer can now purchase more
```

### Adjust Balance (Corrections)
```
Accounts → Credit Management
↓
Click balance button (⚖️)
↓
Enter: Amount (+ or -), Reason
↓
Save → Creates adjustment transaction
```

### Create Invoice
```
Accounts → Invoices
↓
Click "Create Invoice"
↓
Select customer, add items, set due date
↓
Save → Auto-numbered invoice created
```

### Approve Return
```
Accounts → Returns
↓
Find pending return
↓
Click approve (✓)
↓
Inventory restored, customer receives credit
```

---

## 💡 Tips & Tricks

1. **Set credit limits during customer setup** - Saves time later
2. **Record payments immediately** - Keeps balance accurate
3. **Check credit history** before adjusting balance - Understand what happened
4. **Use transaction numbers** for payments - Easy to reconcile
5. **Add notes to adjustments** - Future reference for corrections
6. **Approve returns promptly** - Keeps customers happy
7. **View receipt** to see exact balance due - For verification

---

## ❓ FAQ

**Q: Can I refund to cash instead of credit?**  
A: Yes! When creating a return, select refund_method = "cash". No credit added.

**Q: What if customer pays in installments?**  
A: Record each payment separately. System tracks it all in history.

**Q: Can I give a customer unlimited credit?**  
A: Yes! Set credit_limit = 0 or leave blank. No limit enforced.

**Q: How do I prevent sales to "bad credit" customers?**  
A: Set their credit_limit to 0. Sales blocked if they exceed limit.

**Q: Can I edit an invoice after creating it?**  
A: Only if status = "draft". Once sent/paid, it's locked.

**Q: How do I know if a return is approved?**  
A: Check status in Returns tab. Approved = credit already added.

**Q: What happens to credit if customer never pays?**  
A: It stays in system. You can adjust it or write it off manually.

---

## 🔐 Permissions

| Action | Required Role |
|--------|---|
| View credit | User |
| Record payment | Admin |
| Set credit limit | Admin |
| Create invoice | Admin |
| Approve return | Admin |
| Adjust balance | Admin |
| Delete invoice | Admin |

---

## 📞 Troubleshooting

### Problem: "CREDIT LIMIT EXCEEDED" error
**Solution:** Customer at limit. Increase limit in Accounts → Credit Mgmt → Edit Limit

### Problem: Balance not updating
**Solution:** Refresh page. If still wrong, check credit history for recent transactions.

### Problem: Can't edit invoice
**Solution:** Invoices locked once sent. Delete and recreate if needed.

### Problem: Return not adding credit
**Solution:** Check refund_method = 'credit' and status = 'approved'

### Problem: Customer disappeared from list
**Solution:** They may be inactive. Check all customers filter.

---

## 📈 Reports to Run

```sql
-- Top 10 customers by credit owed
SELECT name, credit_balance, credit_limit 
FROM customers 
WHERE credit_balance > 0 
ORDER BY credit_balance DESC 
LIMIT 10;

-- All overdue invoices
SELECT invoice_number, customer_name, due_date, balance 
FROM invoices 
WHERE status = 'sent' AND due_date < TODAY()
ORDER BY due_date;

-- Pending returns
SELECT return_number, customer_name, refund_amount, status
FROM returns
WHERE status = 'pending'
ORDER BY created_at;

-- Monthly credit summary
SELECT DATE(created_at) as date, type, COUNT(*) as count, SUM(amount) as total
FROM credit_transactions
WHERE MONTH(created_at) = MONTH(TODAY())
GROUP BY DATE(created_at), type
ORDER BY date DESC;
```

---

## 🎯 Checklist Before Going Live

- [ ] All staff trained on credit limits
- [ ] Credit limits set for all customers
- [ ] Test sale with partial payment
- [ ] Test payment recording
- [ ] Test credit limit blocking
- [ ] Verify receipt displays correctly
- [ ] Check credit transactions in database
- [ ] Confirm all API routes working
- [ ] Monitor system performance

---

## 📋 Daily Tasks

1. **Start of Day**
   - Check for pending returns
   - Review overdue invoices
   - Glance at customers over limit

2. **During Day**
   - Record customer payments as received
   - Approve/reject returns promptly
   - Monitor credit transactions

3. **End of Day**
   - Reconcile cash payments with records
   - Verify all sales processed correctly
   - Archive completed transactions

4. **Weekly**
   - Run credit aging report
   - Send overdue invoice reminders
   - Review high-credit customers

5. **Monthly**
   - Generate credit summary
   - Analyze trends
   - Adjust limits as needed
   - Write off bad debt if applicable

---

**Last Updated:** January 27, 2026  
**System Version:** 1.0  
**Status:** ✅ Ready for Production

