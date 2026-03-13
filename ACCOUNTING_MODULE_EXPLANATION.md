# FrogmenDash Accounting Module - Complete Explanation

## Overview

The Accounting Module in FrogmenDash is a comprehensive financial management system built on the Laravel framework with Filament admin panel. It handles invoicing, billing, payments, journal entries, and financial reporting for a multi-tenant ERP system.

---

## Core Concepts

### 1. **Chart of Accounts (CoA)**
The Chart of Accounts is the foundation of the accounting system. It's a list of all accounts used to record financial transactions.

#### Account Types:
- **Assets** - Items of value owned by the company (e.g., cash, inventory, equipment)
- **Liability** - Debts owed by the company (e.g., loans, accounts payable)
- **Equity** - Owner's stake in the company
- **Income/Revenue** - Money earned from sales or services
- **Expenses** - Costs incurred to run the business
- **Other Income** - Miscellaneous revenue sources

#### Example from your system:
```
400000 - Product Sales (Income account)
441000 - Foreign Exchange Gain (Income account)
450000 - Other Income (Other Income account)
643000 - Cash Discount Gain (Income account)
```

#### Key Fields:
- **Code** - Unique identifier for the account
- **Account Name** - Descriptive name
- **Account Type** - Category (Income, Expense, etc.)
- **Allow Reconcile** - Whether the account can be reconciled against bank statements
- **Currency** - The currency used for this account

---

### 2. **Journals**
Journals are the chronological record of all financial transactions. Each journal entry creates debits and credits to balance the accounting equation.

#### Journal Types:
- **Sales Journal** - Records customer invoices and sales transactions
- **Purchase Journal** - Records vendor bills and purchase transactions
- **Cash/Bank Journal** - Records cash deposits and withdrawals
- **General Journal** - Records all other adjusting or miscellaneous entries

#### Key Concept - Double Entry Bookkeeping:
Every transaction affects TWO accounts:
- One account is **DEBITED** (increases that account)
- One account is **CREDITED** (decreases that account)
- **Total Debits = Total Credits** (the fundamental accounting equation)

**Example:**
When you sell a product for $100:
- DEBIT: Cash/Bank Account (+$100)
- CREDIT: Product Sales Revenue (+$100)

---

### 3. **Currencies**
Manage different currencies for international transactions.

#### Functions:
- Define currency codes (USD, EUR, etc.)
- Set exchange rates for multi-currency accounting
- Configure default currency for company
- Handle currency conversions for reporting

---

### 4. **Fiscal Positions**
Fiscal positions define tax rules and account mappings for different types of transactions or business relationships.

#### Use Cases:
- **B2B Transactions** - Different tax treatment for business-to-business sales
- **B2C Transactions** - Different tax treatment for business-to-consumer sales
- **Domestic vs. Export** - Different tax rules for exports
- **Exemptions** - Tax-exempt customers or transactions

#### Purpose:
Automatically determine which accounts to use and which taxes to apply based on the fiscal position selected on an invoice or bill.

---

### 5. **Tax Groups & Taxes**
Organize and manage tax configurations.

#### Structure:
- **Tax Groups** - Collections of related taxes (e.g., "Sales Tax Group")
- **Taxes** - Individual tax calculations within a group

#### Information:
- Tax rate percentage
- Account mappings (where tax is recorded)
- Applicability (sales, purchases, or both)
- Computation method (percentage, fixed amount, etc.)

#### Example Flow:
```
Invoice Created
  ↓
Fiscal Position Selected
  ↓
Tax Group Assigned (e.g., "Sales Tax")
  ↓
Applicable Taxes Applied (e.g., 10% Sales Tax)
  ↓
Tax Amount Calculated and Posted to Tax Account
```

---

### 6. **Invoices (Customer Invoices)**
Records of sales transactions sent to customers.

#### Key Fields:
- **Customer** - Who you sold to
- **Invoice Date** - When the sale occurred
- **Due Date** - Payment deadline
- **Items** - Products/services sold with quantities and prices
- **Company** - Which company issued the invoice
- **Currency** - Currency of the transaction
- **Payment Terms** - When payment is expected
- **Fiscal Position** - Tax rules to apply

#### Accounting Impact:
When an invoice is created:
1. **Income Account** is CREDITED (revenue recognized)
2. **Accounts Receivable** is DEBITED (customer owes us money)
3. Appropriate **Tax Accounts** are credited based on tax group

#### Invoice States:
- **Draft** - Being prepared, not yet sent
- **Posted** - Officially recorded in accounting system
- **Paid** - Payment has been received
- **Cancelled** - Voided or reversed

---

### 7. **Bills (Vendor Bills)**
Records of purchases from vendors/suppliers.

#### Key Fields:
- **Vendor** - Who you purchased from
- **Bill Date** - When the purchase occurred
- **Due Date** - When payment is due
- **Items** - Products/services purchased with quantities and prices
- **Company** - Which company received the bill
- **Currency** - Currency of the transaction
- **Fiscal Position** - Tax rules to apply
- **Journal** - Which journal records this bill (typically Purchase Journal)

#### Accounting Impact:
When a bill is posted:
1. **Expense Account** is DEBITED (expense recognized)
2. **Accounts Payable** is CREDITED (we owe vendor money)
3. Appropriate **Tax Accounts** are debited based on tax group

#### Bill States:
- **Draft** - Being prepared
- **Posted** - Officially recorded
- **Paid** - Payment has been sent
- **Cancelled** - Voided

---

### 8. **Account Moves (Journal Entries)**
The actual debits and credits posted to the general ledger. Each journal entry consists of multiple "lines" (account moves).

#### Structure:
```
Journal Entry (Header)
├── Move Line 1: Debit Account A for $X
├── Move Line 2: Credit Account B for $X
└── Move Line 3: Credit Account C for additional amounts
```

#### Characteristics:
- Always balanced (total debits = total credits)
- Timestamped and numbered sequentially
- Can be linked to source documents (invoices, bills)
- May be reconciled against bank statements

---

### 9. **Payment Terms**
Define how invoices should be paid and when payment is due.

#### Examples:
- **Net 30** - Full payment due in 30 days
- **2/10 Net 30** - 2% discount if paid within 10 days, otherwise full amount due in 30 days
- **Immediate** - Payment due upon invoice
- **Custom** - Multi-stage payments or specific date

#### Purpose:
- Automatically calculate due dates on invoices
- Track overdue amounts for customer follow-up
- Support early payment discounts

---

### 10. **Incoterms**
International Commercial Terms define responsibility for shipping, insurance, and delivery of goods.

#### Common Incoterms:
- **FOB (Free on Board)** - Seller pays for shipping, buyer pays insurance
- **CIF (Cost, Insurance, and Freight)** - Seller pays for all
- **EXW (Ex Works)** - Buyer arranges and pays for everything
- **DDP (Delivered Duty Paid)** - Seller pays for all including customs

#### Impact:
- Determines when revenue/expense is recognized
- Affects tax calculations
- Influences cash flow analysis

---

## Module Navigation & Features

### Main Navigation Areas:

1. **Overview**
   - Dashboard with key financial metrics
   - Quick access to recent transactions

2. **Customers** → Customer Invoices
   - Create and manage customer invoices
   - Track customer receivables
   - View payment status

3. **Vendors** → Vendor Bills
   - Create and manage vendor bills
   - Track vendor payables
   - Record expenses

4. **Accounting** → Journal Entries
   - Post manual journal entries
   - Reconcile accounts
   - View transaction details

5. **Reporting**
   - Financial reports (P&L, Balance Sheet)
   - Tax reports
   - Cash flow analysis

6. **Configuration**
   - **Chart of Accounts** - Add/edit account structure
   - **Journals** - Define journal types
   - **Currencies** - Manage supported currencies
   - **Fiscal Positions** - Set up tax rules
   - **Tax Groups** - Organize taxes
   - **Taxes** - Define individual tax rates
   - **Payment Terms** - Create payment options
   - **Incoterms** - Setup international terms

---

## Key Workflows

### Workflow 1: Creating a Customer Invoice

```
1. Navigate to: Customers → Invoices → Create
2. Select Customer (from Partners table)
3. Set Invoice Date and Due Date
4. Select Fiscal Position (determines taxes)
5. Add line items:
   - Select Product
   - Enter Quantity
   - Price auto-populates from product master
   - Select Income Account (e.g., "Product Sales")
   - Taxes calculated automatically
6. Review total (Items + Taxes)
7. Save as Draft (can edit later)
8. Post/Validate to record in general ledger
9. Send to customer
10. Track payment status
```

#### Accounting Result:
```
DEBIT:  Accounts Receivable        $100
CREDIT: Product Sales Revenue              $100
        (or Income Account specified)
```

---

### Workflow 2: Recording a Vendor Bill

```
1. Navigate to: Vendors → Bills → Create
2. Select Vendor (from Partners table)
3. Set Bill Date and Due Date
4. Select Journal (typically "Purchase")
5. Add line items:
   - Select Product/Expense
   - Enter Quantity
   - Price from vendor invoice
   - Select Expense Account (e.g., "Cost of Goods Sold")
   - Taxes calculated automatically
6. Save as Draft
7. Post to record in system
8. Process payment when ready
9. Reconcile to bank statement
```

#### Accounting Result:
```
DEBIT:  Expense Account (e.g., COGS)       $100
        Tax Account (if applicable)        $10
CREDIT: Accounts Payable                          $110
```

---

### Workflow 3: Creating a Manual Journal Entry

```
1. Navigate to: Accounting → Journal Entries → Create
2. Select Journal (General Journal for misc. entries)
3. Enter Description
4. Add Lines:
   - Line 1: Select Account → Enter Debit Amount
   - Line 2: Select Account → Enter Credit Amount
   - Additional lines as needed
5. Ensure Debits = Credits
6. Save and Post
```

#### Use Cases:
- Adjustments (depreciation, accruals)
- Corrections of posting errors
- Period-end closing entries
- Intercompany transfers

---

## Multi-Tenancy in Accounting

The system is **fully multi-tenant**, meaning:

- Each **Company** has its own:
  - Chart of Accounts
  - Currencies
  - Fiscal Positions
  - Tax configurations
  - Invoices and Bills
  - Journal Entries

- Users can access only:
  - Their assigned companies' data
  - Complete isolation between company data
  - No cross-company visibility without explicit permissions

---

## Key Reports

### 1. **Profit & Loss (P&L) Statement**
Shows revenue minus expenses for a period
```
Revenue:           $50,000
  - Cost of Goods:  $20,000
  = Gross Profit:  $30,000
  - Operating Exp:  $10,000
  = Net Profit:    $20,000
```

### 2. **Balance Sheet**
Snapshot of assets, liabilities, and equity at a point in time
```
Assets:           $100,000
Liabilities:       $40,000
Equity:            $60,000
(Assets = Liabilities + Equity)
```

### 3. **Cash Flow Statement**
Shows movement of cash in/out of business

### 4. **Account Reconciliation**
Matches journal entries against bank statements

---

## Double Entry Bookkeeping Rules

**Remember:**
- Every transaction requires both a DEBIT and a CREDIT
- Total debits must equal total credits
- This ensures the accounting equation stays balanced: **Assets = Liabilities + Equity**

### Account Behavior:
| Account Type | Debit Effect | Credit Effect |
|---|---|---|
| Asset | Increase | Decrease |
| Liability | Decrease | Increase |
| Equity | Decrease | Increase |
| Revenue/Income | Decrease | Increase |
| Expense | Increase | Decrease |

---

## Common Issues & Solutions

### Issue: Invoice not showing in financial reports
**Solution:** Ensure invoice is "Posted" not just "Draft"

### Issue: Tax amount incorrect
**Solution:** Check Fiscal Position is correct and Tax Group is properly configured

### Issue: Accounts not balancing
**Solution:** Review recent journal entries; ensure all entries have matching debits and credits

### Issue: Journal entry won't save
**Solution:** Verify total debits equal total credits

---

## Best Practices

1. **Always use correct account** - Assign revenue to revenue accounts, expenses to expense accounts
2. **Post regularly** - Don't let invoices/bills sit in draft status
3. **Reconcile frequently** - Match accounting records to bank statements monthly
4. **Use fiscal positions** - Ensures tax rules are applied consistently
5. **Document adjustments** - Add clear descriptions to manual journal entries
6. **Review reports** - Run monthly P&L and balance sheet to spot errors
7. **Archive old data** - Keep system lean by archiving completed periods

---

## Related Systems Integration

The Accounting Module integrates with:

- **Partners Module** - Customer and vendor records
- **Products Module** - Product costs and accounts
- **Sales Module** - Customer invoices
- **Purchase Module** - Vendor bills
- **Inventory Module** - Cost of goods accounting
- **Security Module** - Multi-tenancy and permissions

---

## Summary

The FrogmenDash Accounting Module is built on **double-entry bookkeeping principles** where every financial transaction is recorded with equal debits and credits. It supports multi-tenant companies, multiple currencies, complex tax scenarios, and provides comprehensive financial reporting—all within a secure, permission-based framework.

