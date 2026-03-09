# FrogmenDash Accounting Module - Complete Guide

**Module:** Accounting (Part of Webkul ERP)  
**Purpose:** Manage company finances, accounts, invoices, payments, and financial reporting  
**Date:** February 19, 2026

---

## Table of Contents

1. [Module Overview](#module-overview)
2. [Navigation Menu](#navigation-menu)
3. [Chart of Accounts](#chart-of-accounts)
4. [Account Types & Categories](#account-types--categories)
5. [Account Table Columns](#account-table-columns)
6. [Core Features](#core-features)
7. [Key Concepts](#key-concepts)
8. [Workflow](#workflow)

---

## Module Overview

The **Accounting Module** is the financial hub of FrogmenDash. It handles:
- **Chart of Accounts** - Account hierarchy and structure
- **Invoicing** - Customer and vendor invoices
- **Payments** - Payment tracking and reconciliation
- **Reporting** - Financial statements and analysis
- **Configuration** - Account setup, tax settings, payment terms

### Key Functions:
```
Track Money In & Out
     ↓
Create Invoices & Bills
     ↓
Record Payments
     ↓
Reconcile Accounts
     ↓
Generate Financial Reports
```

---

## Navigation Menu

### Main Accounting Menu (Left Sidebar)

```
📊 ACCOUNTING
├── Overview
│   └── Dashboard view of financial status
│
├── Customers
│   └── Manage customer accounts & receivables
│
├── Vendors
│   └── Manage vendor accounts & payables
│
├── Accounting
│   └── (Sub-menu, see below)
│
├── Reporting
│   └── Financial statements & reports
│
├── Configurations
│   └── System-wide accounting settings
│
└── Settings
    └── User-specific preferences
```

### Accounting Sub-Menu (Expanded)

When you click "Accounting" in the sidebar:

```
ACCOUNTING (Expanded)
├── Chart of Accounts
│   └── View/edit all accounts (shown in current screenshot)
│
├── Journals
│   └── Record daily transactions
│
├── Currencies
│   └── Multi-currency setup
│
├── Fiscal Positions
│   └── Tax compliance rules
│
├── Tax Groups
│   └── Group taxes together
│
├── Taxes
│   └── Define tax rates
│
├── Cash Roundings
│   └── Rounding rules for cash payments
│
└── Products (Related)
    ├── Attributes - Product properties
    └── Categories - Product grouping
```

### Other Key Menus

**INVOICING Section:**
```
INVOICING
├── Payment Terms
│   └── Define payment deadlines (Net 30, 2/10 Net 30, etc.)
│
└── Incoterms
    └── International trade terms (FOB, CIF, DDP, etc.)
```

---

## Chart of Accounts

### What Is the Chart of Accounts?

The **Chart of Accounts (COA)** is a complete list of all accounts your company uses to track money. Think of it as the organizational structure for your finances.

**Analogy:**
```
Company Finances = Kitchen
Chart of Accounts = Kitchen Drawers & Cabinets

Each drawer (account) holds a specific category of items:
  - Drawer 1 (Assets) = Cash, bank accounts, inventory
  - Drawer 2 (Liabilities) = Debts, loans, payables
  - Drawer 3 (Income) = Sales, revenue
  - Drawer 4 (Expenses) = Rent, utilities, salaries
```

### Why It Matters:

- **Organized** - Every financial transaction goes to an account
- **Traceable** - See where money comes from and goes
- **Reportable** - Generate financial statements
- **Tax-Compliant** - Organized for audit & tax filing

---

## Account Types & Categories

In the screenshot, accounts are organized by **TYPE** (shown as a dropdown):

### 1. **Default** 
Basic accounts without special accounting treatment
- Used for miscellaneous accounts
- Example: General expenses

### 2. **Receivable** 
Money customers OWE YOU
- Tracks invoices sent but not yet paid
- Example: Account Receivable (121000)
- When customer pays → Move money out of Receivable to Cash

### 3. **Payable**
Money YOU OWE vendors
- Tracks bills received but not yet paid
- Example: Account Payable (201000)
- When you pay bill → Move money out of Cash to Payable

### 4. **Equity**
Owner's stake in the company
- Capital investments
- Retained earnings
- Example: Opening Balances, Stock Capital

### 5. **Assets**
Things your company OWNS
- Cash, bank accounts
- Inventory
- Equipment, property
- Example: Current Assets (101000)

### 6. **Liability**
Things your company OWES
- Loans, debts
- Tax payables
- Example: Accounts Payable, Tax Payable

### 7. **Income** (Revenue)
Money coming IN
- Sales revenue
- Service income
- Interest income
- Example: Sales Income (400000)

### 8. **Expenses**
Money going OUT
- Rent, utilities
- Salaries, wages
- Office supplies
- Example: Rent Expense, Salary Expense

---

## Account Table Columns Explained

| Column | Meaning | Example |
|--------|---------|---------|
| **Code** | Account number/identifier | `101000`, `121000`, `400100` |
| **Account Name** | What the account tracks | "Current Assets", "Stock Valuation" |
| **Account (Type)** | Parent category | "Current Assets", "Receivable", "Expenses" |
| **Allow Reconcile** | Can transactions be matched? | Checkbox (usually for bank/payment accounts) |
| **Currency** | Base currency for account | USD, EUR, GBP |
| **Actions** | View/Edit/Delete buttons | Links to modify account |

### Sample Accounts from Screenshot:

```
CODE    | ACCOUNT NAME               | TYPE            | CURRENCY
--------|----------------------------|-----------------|----------
101000  | Current Assets             | Current Assets  | USD
110100  | Stock Valuation            | Current Assets  | USD
110200  | Stock Interim (Received)    | Current Assets  | USD
110300  | Stock Interim (Delivered)   | Current Assets  | USD
110400  | Cost of Production          | Current Assets  | USD
110500  | Work in Progress            | Current Assets  | USD
121000  | Account Receivable          | Receivable      | USD
121100  | Products to receive         | Current Assets  | USD
128000  | Prepaid Expenses            | Current Assets  | USD
131000  | Tax Paid                    | Current Assets  | USD
```

---

## Core Features

### 1. Chart of Accounts Management

**Features:**
- ✅ Create new accounts
- ✅ Edit existing accounts
- ✅ Delete unused accounts
- ✅ Organize by type and category
- ✅ Support multiple currencies

**Common Tasks:**
```
Task: Add expense account for office rent
→ Click "New Chart of Account"
→ Code: 501000
→ Name: Office Rent Expense
→ Type: Expenses
→ Save

Task: Track customer payments
→ Chart of Accounts > Receivable
→ Already has "Account Receivable" (121000)
→ Invoices automatically post here
```

### 2. Journals

**Purpose:** Record daily transactions

**Types:**
- Sales Journal - Customer invoices
- Purchase Journal - Vendor bills
- Cash Journal - Money in/out
- General Journal - Manual entries

**Example:**
```
Journal Entry: Sale to Customer X
  Debit: Account Receivable (121000) - $1000
  Credit: Sales Income (400000) - $1000
```

### 3. Currencies

**Purpose:** Support international business

**Features:**
- ✅ Define exchange rates
- ✅ Multi-currency invoices
- ✅ Automatic conversion
- ✅ Local reporting in each currency

**Example:**
```
Company: Frogmen Europe
Primary Currency: EUR
Can invoice customers in: USD, GBP, CHF
System auto-converts for reporting
```

### 4. Fiscal Positions

**Purpose:** Apply tax rules by customer/vendor location

**Example:**
```
Fiscal Position: EU Business
  Rule: No VAT on B2B sales with valid VAT ID
  Rule: 19% VAT on B2C sales

Fiscal Position: US Business
  Rule: No sales tax (already in price)
  Rule: Income tax 21%
```

### 5. Tax Groups

**Purpose:** Bundle related taxes

**Example:**
```
Tax Group: "Standard VAT Bundle"
  ├── VAT 19%
  └── Municipal Tax 0.5%
  = Total 19.5%

Applied to: Most products
```

### 6. Taxes

**Purpose:** Define individual tax rates

**Example:**
```
Tax: VAT 19%
  Rate: 19%
  Applied to: Products
  Deductible: Yes (for VAT purposes)

Tax: Sales Tax 8.5%
  Rate: 8.5%
  Applied to: US Sales
  Deductible: No
```

### 7. Cash Roundings

**Purpose:** Handle rounding in payments

**Example:**
```
Rounding Rule: Round to nearest $0.05
  Invoice Total: $99.96
  Rounded: $100.00
  Rounding Difference: $0.04 (small account)
```

---

## Key Concepts

### Double-Entry Bookkeeping

Every transaction has **two sides**:

```
When you RECEIVE PAYMENT from customer:
  Debit (increase): Cash Account (101000) +$1000
  Credit (decrease): Account Receivable (121000) -$1000
  
Result: Money moved from "customer owes us" to "we have it"
```

### Account Balance Equation

```
ASSETS = LIABILITIES + EQUITY

Example:
  Assets (Cash $10k + Inventory $5k) = $15k
  Liabilities (Loan $8k) = $8k
  Equity (Owner Investment $7k) = $7k
  
  Check: $15k = $8k + $7k ✓ BALANCED
```

### Debit vs Credit

```
ASSET & EXPENSE Accounts:
  Debit = Increase    Credit = Decrease

LIABILITY, EQUITY & INCOME Accounts:
  Debit = Decrease    Credit = Increase

Example:
  Increase Cash (Asset):
    DEBIT Cash 100
    CREDIT Revenue 100
```

---

## Workflow

### Typical Accounting Cycle

```
Step 1: INVOICING
  └─ Create invoice to customer
    └─ Posts to: Accounts Receivable (money customer owes)
    └─ Posts to: Sales Income (revenue generated)

Step 2: PAYMENT RECEIVED
  └─ Customer pays invoice
    └─ Posts to: Cash Account (money received)
    └─ Posts to: Accounts Receivable (reduces what they owe)

Step 3: RECONCILIATION
  └─ Bank statement arrives
    └─ Match actual deposits with invoice payments
    └─ Verify all money accounted for

Step 4: REPORTING
  └─ Month-end close
    └─ Generate Profit & Loss (Income - Expenses)
    └─ Generate Balance Sheet (Assets - Liabilities - Equity)
    └─ Tax filing
```

### Common Daily Tasks

**Task 1: Create a Customer Invoice**
```
1. Go to: Invoicing > Invoices
2. Create new invoice
3. Add customer, products, quantities
4. System automatically:
   - Calculates totals
   - Applies taxes
   - Posts to Accounts Receivable (121000)
5. Send to customer
```

**Task 2: Record Vendor Bill**
```
1. Go to: Invoicing > Bills
2. Create new bill
3. Add vendor, products, amounts
4. System automatically:
   - Calculates totals
   - Applies taxes
   - Posts to Accounts Payable (201000)
5. Schedule for payment
```

**Task 3: Record Payment**
```
1. Go to: Payments
2. Create payment
3. Link to invoice/bill
4. Mark as paid
5. System updates:
   - Reduces Accounts Receivable/Payable
   - Updates Cash balance
   - Matches with bank statement
```

**Task 4: Generate Financial Report**
```
1. Go to: Reporting
2. Choose report type:
   - Profit & Loss (Revenues - Expenses = Net Income)
   - Balance Sheet (Assets - Liabilities - Equity)
   - Trial Balance (All accounts + totals)
   - Aged Receivable (Who owes you)
   - Aged Payable (Who you owe)
3. Select date range
4. View/download report
```

---

## Configuration Checklist

Before using Accounting Module, configure:

- [ ] **Chart of Accounts** - Create/review all accounts
- [ ] **Currencies** - Set up multi-currency if needed
- [ ] **Taxes** - Define tax rates for your country
- [ ] **Tax Groups** - Bundle taxes (e.g., VAT 19%)
- [ ] **Fiscal Positions** - Set rules by customer location
- [ ] **Payment Terms** - Define when invoices are due (Net 30, etc.)
- [ ] **Incoterms** - Define shipping responsibility (FOB, CIF, etc.)
- [ ] **Journals** - Confirm sales/purchase/cash journals exist
- [ ] **Cash Rounding** - Set rounding rules if needed

---

## Account Structure (Example for US Company)

```
ASSETS (1xx)
├── 101000 - Cash
├── 102000 - Bank Accounts
├── 110000 - Accounts Receivable
└── 120000 - Inventory

LIABILITIES (2xx)
├── 201000 - Accounts Payable
├── 205000 - Taxes Payable
└── 210000 - Short-term Loans

EQUITY (3xx)
├── 301000 - Capital Stock
├── 302000 - Retained Earnings
└── 310000 - Owner Drawings

INCOME (4xx) - Revenue
├── 400000 - Sales Revenue
├── 410000 - Service Income
└── 420000 - Interest Income

EXPENSES (5xx-8xx) - Costs
├── 501000 - Cost of Goods Sold
├── 510000 - Salaries & Wages
├── 520000 - Rent Expense
├── 530000 - Utilities
├── 540000 - Office Supplies
└── 550000 - Depreciation
```

---

## Tips & Best Practices

### 1. Account Naming
- ✅ Use clear, descriptive names
- ✅ Use consistent naming convention (Verb + Object)
- ❌ Avoid vague names like "Misc" or "Other"

**Good:** "Office Rent Expense", "Sales Revenue - Products"  
**Bad:** "Rent", "Money In"

### 2. Account Codes
- ✅ Use 6-digit codes: First digit = type, rest = category
- ✅ Leave gaps (101, 102, 110, 120) for future accounts
- ✅ Use accounting standards (if required in your country)

**Structure:**
```
1XX = Assets
2XX = Liabilities  
3XX = Equity
4XX = Income
5-8XX = Expenses
```

### 3. Currency Handling
- ✅ Set default company currency first
- ✅ Create accounts in default currency
- ✅ Use currency conversion for reports
- ❌ Don't mix currencies in same account

### 4. Tax Setup
- ✅ Verify tax rates match your tax authority
- ✅ Test with sample invoice before going live
- ✅ Review tax accounts (Tax Paid, Tax Payable)
- ❌ Don't guess tax rates

### 5. Regular Reconciliation
- ✅ Reconcile bank account weekly
- ✅ Match invoice payments to bank deposits
- ✅ Investigate discrepancies immediately
- ✅ Close month-end officially

---

## Troubleshooting

### Problem: Accounts won't balance
**Solution:**
1. Check for data entry errors
2. Verify double-entry (debit = credit)
3. Review recent transactions
4. Run trial balance report

### Problem: Can't create invoice
**Solution:**
1. Verify customer is created
2. Verify products are active
3. Check Chart of Accounts is complete
4. Verify tax configuration

### Problem: Payment not recording
**Solution:**
1. Verify invoice is marked unpaid
2. Verify customer/vendor exists
3. Verify cash account is set up
4. Check reconciliation isn't preventing update

### Problem: Tax calculations wrong
**Solution:**
1. Verify tax rate in Taxes configuration
2. Verify Tax Group is assigned to product
3. Verify Fiscal Position applies to customer
4. Test with sample calculation

---

## Related Documentation

- **MULTI_TENANCY_IMPLEMENTATION.md** - Company segregation
- **PROJECT_MODULE_INSPECTION_PLAN.md** - Project-based costing
- **ROV_INSPECTION_ARCHITECTURE_DECISION.md** - Service project accounting
- **APP_DOCUMENTATION.md** - Overall system overview

---

## Support & Resources

**Common Tasks:**
- Create Invoice → Invoicing > Invoices > New
- Record Payment → Payments > New
- View Report → Reporting > [Report Type]
- Configure Tax → Configurations > Taxes > New

**User Roles:**
- **Accountant** - Full access to all accounting features
- **Manager** - View reports, approve invoices
- **Sales User** - Create customer invoices only
- **Vendor Manager** - Manage vendor bills

---

**Module Status:** ✅ Active & Configured  
**Last Updated:** February 19, 2026  
**Webkul Version:** 4.5.3.0
