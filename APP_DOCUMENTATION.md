# IDURAR ERP/CRM - Complete App Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Features](#features)
4. [Core Modules](#core-modules)
5. [Database Models](#database-models)
6. [API Endpoints](#api-endpoints)
7. [User Authentication](#user-authentication)
8. [Security Features](#security-features)
9. [Tech Stack](#tech-stack)
10. [Getting Started](#getting-started)

---

## 🎯 Overview

**IDURAR** is a free, open-source ERP (Enterprise Resource Planning) / CRM (Customer Relationship Management) application built with the modern **MERN Stack** (MongoDB, Express.js, React.js, Node.js). It provides comprehensive business management tools for small to medium-sized enterprises.

### What You Can Do:
- ✅ Manage customers/clients
- ✅ Create and track invoices
- ✅ Generate quotes for potential sales
- ✅ Process and track payments
- ✅ Apply tax calculations
- ✅ Set payment methods
- ✅ Generate PDF documents (invoices, quotes, etc.)
- ✅ Send emails with documents
- ✅ Multi-language support (20+ languages)
- ✅ Customizable currency and formatting
- ✅ AI-powered features (OpenAI integration)
- ✅ AWS S3 file storage

---

## 🏗️ Architecture

### Frontend Structure
```
frontend/
├── src/
│   ├── apps/              # Main app configurations
│   ├── auth/              # Authentication logic & guards
│   ├── components/        # Reusable React components
│   ├── modules/           # Feature modules (Invoice, Quote, etc.)
│   ├── pages/             # Page components
│   ├── redux/             # State management (Redux)
│   ├── router/            # Route definitions
│   ├── forms/             # Form configurations
│   └── locale/            # i18n translations
```

### Backend Structure
```
backend/
├── src/
│   ├── models/
│   │   ├── appModels/     # Business data models
│   │   └── coreModels/    # Core system models (Admin, etc.)
│   ├── controllers/       # API controllers (CRUD operations)
│   ├── routes/            # API route definitions
│   ├── middlewares/       # Express middlewares
│   ├── helpers/           # Utility functions
│   ├── pdf/               # PDF templates (Pug)
│   ├── emailTemplate/     # Email templates
│   ├── locale/            # i18n support
│   ├── setup/             # Database initialization
│   └── server.js          # Express server entry point
```

---

## ✨ Features

### 1. **Invoice Management**
- Create, read, update, delete invoices
- Track invoice status
- Generate PDF invoices
- Send invoices via email
- Multiple currency support
- Tax calculation per line item

### 2. **Quote Management**
- Create professional quotes
- Convert quotes to invoices
- Track quote status
- PDF generation
- Email distribution
- Quote versioning

### 3. **Payment Management**
- Record and track payments
- Multiple payment methods
- Payment reconciliation
- Payment status tracking
- Automated payment notifications

### 4. **Customer Management (CRM)**
- Maintain customer database
- Track customer details
- Link invoices/quotes to customers
- Customer communication history

### 5. **Accounting & Reporting**
- Tax configuration
- Payment mode setup
- Financial summaries
- Transaction tracking

### 6. **Document Generation**
- **PDF Export**: Invoices, quotes, payments as PDF
- **Email Integration**: Send documents directly via email
- **Customizable Templates**: Pug-based templates for branding

### 7. **Multi-Language Support**
- 20+ languages supported:
  - English, Spanish, French, German, Italian
  - Portuguese (Brazil & Portugal)
  - Russian, Chinese, Japanese, Korean
  - Arabic, Hindi, Turkish, Vietnamese
  - And more...

### 8. **System Settings**
- Customizable currency formatting
- Date format preferences
- Decimal precision settings
- Thousand separator options
- Business branding customization

### 9. **Security**
- JWT (JSON Web Token) authentication
- Password hashing with bcryptjs
- Role-based access control (Owner role)
- Email verification
- Rate limiting on API endpoints
- CORS protection

### 10. **Advanced Features**
- **AI Integration**: OpenAI API support for smart features
- **File Storage**: AWS S3 bucket integration
- **Email Service**: Resend API for transactional emails
- **Real-time Updates**: Redux state management
- **Responsive UI**: Ant Design (AntD) components

---

## 📦 Core Modules

### 1. **Auth Module** (`AuthModule`)
Handles user authentication, login/logout, and session management.
- Login with email
- Logout functionality
- Session persistence
- Protected routes

### 2. **Invoice Module** (`InvoiceModule`)
Complete invoice lifecycle management.
- Create new invoices
- Edit invoices
- View invoice details
- Generate PDF
- Send via email
- Track payment status

### 3. **Quote Module** (`QuoteModule`)
Professional quote generation and management.
- Create quotes
- Edit quotes
- Convert to invoices
- PDF generation
- Email distribution

### 4. **Payment Module** (`PaymentModule`)
Payment recording and tracking.
- Record payments
- Link to invoices
- Track payment status
- Multiple payment methods

### 5. **CRM Module** (`ErpPanelModule`)
Customer relationship and business management.
- Customer database
- Customer details management
- Linked transactions

### 6. **Dashboard Module** (`DashboardModule`)
Overview and analytics dashboard.
- Key metrics
- Quick actions
- Financial summaries

### 7. **Settings Module** (`SettingModule`)
System configuration and preferences.
- Business settings
- Currency configuration
- Date/time formats
- Language preferences

### 8. **CRUD Module** (`CrudModule`)
Generic CRUD operations for data entities.
- Create records
- Read/View records
- Update records
- Delete records
- Search functionality

---

## 🗄️ Database Models

### Core Models (`coreModels`)

#### **Admin**
- Email (unique)
- Name
- Surname
- Role (owner)
- Enabled status
- Contact information

#### **AdminPassword**
- Password (hashed)
- Salt (for hashing)
- User reference
- Email verification status

#### **Setting**
- Configuration key-value pairs
- App language
- Currency settings
- Business preferences

### Application Models (`appModels`)

#### **Client**
```javascript
{
  name: String,
  email: String,
  phone: String,
  address: String,
  city: String,
  country: String,
  zipCode: String,
  companyName: String,
  website: String,
  createdAt: Date,
  updatedAt: Date
}
```

#### **Invoice**
```javascript
{
  invoiceNumber: String (unique),
  clientId: Reference(Client),
  issueDate: Date,
  dueDate: Date,
  items: [{
    description: String,
    quantity: Number,
    unitPrice: Number,
    taxId: Reference(Tax),
    total: Number
  }],
  subtotal: Number,
  totalTax: Number,
  total: Number,
  notes: String,
  status: String (draft|sent|paid|overdue),
  paymentModeId: Reference(PaymentMode),
  createdAt: Date
}
```

#### **Quote**
```javascript
{
  quoteNumber: String (unique),
  clientId: Reference(Client),
  issueDate: Date,
  expiryDate: Date,
  items: [{
    description: String,
    quantity: Number,
    unitPrice: Number,
    taxId: Reference(Tax),
    total: Number
  }],
  subtotal: Number,
  totalTax: Number,
  total: Number,
  notes: String,
  status: String (draft|sent|accepted|rejected|converted),
  createdAt: Date
}
```

#### **Payment**
```javascript
{
  paymentNumber: String (unique),
  invoiceId: Reference(Invoice),
  clientId: Reference(Client),
  amount: Number,
  paymentDate: Date,
  paymentModeId: Reference(PaymentMode),
  notes: String,
  status: String (pending|processed|failed),
  createdAt: Date
}
```

#### **Taxes**
```javascript
{
  taxName: String (e.g., "VAT 20%", "Tax 0%"),
  taxValue: Number,
  isDefault: Boolean,
  createdAt: Date
}
```

#### **PaymentMode**
```javascript
{
  name: String (e.g., "Cash", "Wire Transfer"),
  description: String,
  isDefault: Boolean,
  createdAt: Date
}
```

---

## 🔌 API Endpoints

### Base URL
```
http://localhost:8888/api
```

### Authentication Routes (`/auth`)
- `POST /auth/login` - User login
- `POST /auth/logout` - User logout
- `POST /auth/register` - New user registration
- `GET /auth/me` - Get current user

### Client Routes (`/client`)
- `POST /client/create` - Create new client
- `GET /client/read/:id` - Get client details
- `PATCH /client/update/:id` - Update client
- `DELETE /client/delete/:id` - Delete client
- `GET /client/list` - Get all clients (paginated)
- `GET /client/listAll` - Get all clients
- `GET /client/search` - Search clients
- `GET /client/filter` - Filter clients
- `GET /client/summary` - Client statistics

### Invoice Routes (`/invoice`)
- `POST /invoice/create` - Create invoice
- `GET /invoice/read/:id` - Get invoice details
- `PATCH /invoice/update/:id` - Update invoice
- `DELETE /invoice/delete/:id` - Delete invoice
- `GET /invoice/list` - List invoices
- `GET /invoice/search` - Search invoices
- `GET /invoice/filter` - Filter invoices
- `GET /invoice/summary` - Invoice statistics
- `POST /invoice/mail` - Send invoice via email

### Quote Routes (`/quote`)
- `POST /quote/create` - Create quote
- `GET /quote/read/:id` - Get quote details
- `PATCH /quote/update/:id` - Update quote
- `DELETE /quote/delete/:id` - Delete quote
- `GET /quote/list` - List quotes
- `GET /quote/search` - Search quotes
- `GET /quote/filter` - Filter quotes
- `GET /quote/summary` - Quote statistics
- `POST /quote/mail` - Send quote via email
- `GET /quote/convert/:id` - Convert quote to invoice

### Payment Routes (`/payment`)
- `POST /payment/create` - Record payment
- `GET /payment/read/:id` - Get payment details
- `PATCH /payment/update/:id` - Update payment
- `DELETE /payment/delete/:id` - Delete payment
- `GET /payment/list` - List payments
- `GET /payment/search` - Search payments
- `GET /payment/filter` - Filter payments
- `GET /payment/summary` - Payment statistics
- `POST /payment/mail` - Send payment confirmation

### Settings Routes (`/setting`)
- `GET /setting/list` - Get all settings
- `PATCH /setting/update` - Update settings

### Tax Routes (`/taxes`)
- `POST /taxes/create` - Create tax
- `GET /taxes/list` - List all taxes

### Payment Mode Routes (`/paymentMode`)
- `POST /paymentMode/create` - Create payment mode
- `GET /paymentMode/list` - List payment modes

---

## 🔐 User Authentication

### Login Process
1. User enters email and password on login page
2. Credentials sent to `/auth/login` endpoint
3. Backend verifies credentials against database
4. JWT token generated and returned
5. Token stored in browser (localStorage/cookies)
6. Subsequent requests include token in headers

### Default Admin Credentials
After running setup script:
- **Email**: `admin@admin.com`
- **Password**: `admin123`

### Password Security
- Passwords hashed using bcryptjs
- Unique salt generated per user
- Salt stored separately from password hash

---

## 🛡️ Security Features

### 1. **Authentication**
- JWT token-based authentication
- Session management
- Email verification support

### 2. **Authorization**
- Role-based access control (RBAC)
- Route protection
- Admin-level permissions

### 3. **Data Protection**
- Password hashing (bcryptjs)
- CORS enabled
- Request validation (Joi)

### 4. **Rate Limiting**
- Express rate limiter configured
- Prevents brute force attacks
- API endpoint protection

### 5. **Input Validation**
- Joi schema validation
- Request sanitization
- Error handling

### 6. **File Security**
- AWS S3 integration for safe storage
- File type validation
- Size limitations

---

## 💻 Tech Stack

### Frontend
- **React.js 18.3.1** - UI library
- **Vite 7.3.1** - Build tool (next-gen)
- **Redux 5.0.1** - State management
- **Ant Design (AntD) 5.14.1** - UI components
- **React Router DOM 6.22.0** - Routing
- **Axios 1.6.2** - HTTP client
- **React Quill 2.0.0** - Rich text editor
- **DayJS 1.11.10** - Date handling

### Backend
- **Node.js 20.9.0** - Runtime
- **Express.js 4.18.2** - Web framework
- **MongoDB 8.1.1** (Mongoose) - Database
- **JWT 9.0.2** - Token authentication
- **bcryptjs 2.4.3** - Password hashing
- **Puppeteer 25.x** - PDF generation ✨ (Upgraded from html-pdf)
- **Pug 3.0.2** - Template engine
- **Resend 2.0.0** - Email service
- **OpenAI 4.27.0** - AI integration
- **AWS SDK 3.509.0** - Cloud storage

### Development Tools
- **Nodemon 3.0.1** - Auto-restart server
- **ESLint 8.56.0** - Code linting
- **Prettier 3.1.0** - Code formatting

---

## 🚀 Getting Started

### Prerequisites
- Node.js 20.9.0+
- npm 10.2.4+
- MongoDB Atlas account (or local MongoDB)

### Step 1: Clone Repository
```bash
git clone https://github.com/idurar/idurar-erp-crm.git
cd idurar-erp-crm
```

### Step 2: Setup Backend

#### Install Dependencies
```bash
cd backend
npm install
```

#### Configure Environment
Edit `backend/.env`:
```env
DATABASE = "mongodb+srv://username:password@cluster.mongodb.net/?appName=Clusterforgmen"
JWT_SECRET = "your-secret-key"
NODE_ENV = "development"
PUBLIC_SERVER_FILE = "http://localhost:8888/"
```

#### Initialize Database
```bash
npm run setup
```

#### Start Backend
```bash
node src/server.js
```
Backend runs on: `http://localhost:8888`

### Step 3: Setup Frontend

#### Install Dependencies
```bash
cd frontend
npm install
```

#### Start Frontend
```bash
npm run dev
```
Frontend runs on: `http://localhost:5173`

### Step 4: Login
- **Email**: `admin@admin.com`
- **Password**: `admin123`

---

## 📝 Common Workflows

### Creating an Invoice
1. Go to Invoices module
2. Click "Create New"
3. Select Client
4. Add line items (description, quantity, price, tax)
5. Set due date and notes
6. Save invoice
7. Generate PDF or send via email

### Converting Quote to Invoice
1. Go to Quotes module
2. Select a quote
3. Click "Convert to Invoice"
4. Confirm conversion
5. Quote becomes a new invoice

### Recording Payment
1. Go to Payments module
2. Click "Create New"
3. Select Invoice to pay
4. Enter payment amount and date
5. Select payment method
6. Save payment
7. Invoice status updates automatically

### Generating Reports
1. Use Summary feature in each module
2. Filter by date range
3. View financial metrics
4. Export if needed

---

## 📊 Key Metrics Tracked

- **Total Invoices**: Count and sum of all invoices
- **Total Quotes**: Active and converted quotes
- **Total Payments**: Received payments summary
- **Outstanding**: Amount due from clients
- **Client Count**: Total customers in database
- **Revenue**: Total revenue from paid invoices

---

## 🔧 Configuration Options

### Currency Formatting
```javascript
{
  currency_symbol: "$",
  currency_position: "left", // or "right"
  decimal_sep: ".",
  thousand_sep: ",",
  cent_precision: 2
}
```

### Date Formats
- Multiple format options available
- Customizable per locale

### Languages Available
Arabic, Bengali, Bulgarian, Catalan, Chinese, Czech, Danish, Dutch, English, Estonian, Farsi, Finnish, French, German, Greek, Hindi, Hungarian, Indonesian, Italian, Japanese, Korean, Latvian, Lithuanian, Macedonian, Malaysian, Norwegian, Polish, Portuguese (BR), Portuguese (PT), Romanian, Russian, Serbian, Slovak, Slovenian, Spanish, Turkish, Vietnamese

---

## 📚 Additional Resources

- **Official Website**: https://www.idurarapp.com/
- **Cloud Hosted Version**: https://cloud.idurarapp.com/
- **GitHub Repository**: https://github.com/idurar/idurar-erp-crm
- **Contributing Guide**: See CONTRIBUTING.md
- **License**: GNU Affero General Public License v3.0

---

## 🎓 Support & Community

- Report issues on GitHub
- Contribute improvements
- Join the community
- Star the project ⭐

---

## ✅ Recent Improvements

✨ **Security Updates**:
- Replaced `html-pdf` with **Puppeteer** for better security
- Fixed 40+ npm vulnerabilities
- All packages now at **0 vulnerabilities**

✨ **Setup Complete**:
- MongoDB Atlas configured
- Database initialized with default admin
- Backend and frontend running
- Ready for production use

---

**Happy billing and customer management! 🎉**
