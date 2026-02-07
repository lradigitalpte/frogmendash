# IDURAR ERP/CRM - Inventory Management System

**Date Created**: January 28, 2026  
**Feature Type**: Core Business Module  
**Status**: Ready for Implementation

---

## 📋 Table of Contents
1. [Overview](#overview)
2. [Database Models](#database-models)
3. [Features](#features)
4. [Implementation](#implementation)
5. [API Endpoints](#api-endpoints)
6. [Integration](#integration)
7. [Workflows](#workflows)

---

## 🎯 Overview

**Inventory Management System** is a complete module for managing products, stock levels, warehouses, and inventory transactions. It integrates seamlessly with invoicing and quoting systems.

### Key Benefits:
- ✅ Real-time stock tracking
- ✅ Multi-warehouse support
- ✅ Automatic stock adjustments
- ✅ Low stock alerts
- ✅ Product categorization
- ✅ SKU management
- ✅ Barcode support
- ✅ Stock history/audit trail

---

## 🗄️ Database Models

### 1. Product Model

**File**: `backend/src/models/appModels/Product.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const productSchema = new Schema({
  removed: {
    type: Boolean,
    default: false
  },
  
  // Basic Product Info
  name: {
    type: String,
    required: true,
    trim: true
  },
  
  description: {
    type: String,
    trim: true
  },
  
  // SKU and Barcode
  sku: {
    type: String,
    required: true,
    unique: true,
    uppercase: true,
    trim: true
  },
  
  barcode: {
    type: String,
    unique: true,
    sparse: true,
    trim: true
  },
  
  // Product Classification
  category: {
    type: Schema.Types.ObjectId,
    ref: 'ProductCategory'
  },
  
  unit: {
    type: String,
    enum: ['pcs', 'kg', 'ltr', 'box', 'pack', 'meter', 'unit'],
    default: 'pcs'
  },
  
  // Pricing
  costPrice: {
    type: Number,
    required: true,
    min: 0
  },
  
  sellingPrice: {
    type: Number,
    required: true,
    min: 0
  },
  
  discount: {
    type: Number,
    default: 0,
    min: 0,
    max: 100
  },
  
  // Stock Management
  reorderLevel: {
    type: Number,
    default: 10,
    min: 0
  },
  
  reorderQuantity: {
    type: Number,
    default: 50,
    min: 0
  },
  
  maxStock: {
    type: Number,
    default: 1000
  },
  
  // Status
  status: {
    type: String,
    enum: ['active', 'inactive', 'discontinued'],
    default: 'active'
  },
  
  taxId: {
    type: Schema.Types.ObjectId,
    ref: 'Taxes'
  },
  
  // Image/Media
  image: {
    type: String,
    trim: true
  },
  
  // Supplier info
  supplier: {
    type: Schema.Types.ObjectId,
    ref: 'Client'
  },
  
  // Tracking
  createdBy: {
    type: Schema.Types.ObjectId,
    ref: 'Admin'
  },
  
  updatedBy: {
    type: Schema.Types.ObjectId,
    ref: 'Admin'
  },
  
  createdAt: {
    type: Date,
    default: Date.now
  },
  
  updatedAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('Product', productSchema);
```

---

### 2. Product Category Model

**File**: `backend/src/models/appModels/ProductCategory.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const categorySchema = new Schema({
  removed: {
    type: Boolean,
    default: false
  },
  
  name: {
    type: String,
    required: true,
    unique: true,
    trim: true
  },
  
  description: {
    type: String,
    trim: true
  },
  
  code: {
    type: String,
    unique: true,
    uppercase: true
  },
  
  parentCategory: {
    type: Schema.Types.ObjectId,
    ref: 'ProductCategory',
    default: null
  },
  
  icon: String,
  
  active: {
    type: Boolean,
    default: true
  },
  
  createdAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('ProductCategory', categorySchema);
```

---

### 3. Stock/Inventory Model

**File**: `backend/src/models/appModels/Stock.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const stockSchema = new Schema({
  removed: {
    type: Boolean,
    default: false
  },
  
  product: {
    type: Schema.Types.ObjectId,
    ref: 'Product',
    required: true
  },
  
  warehouse: {
    type: Schema.Types.ObjectId,
    ref: 'Warehouse',
    required: true
  },
  
  // Current stock levels
  quantityInStock: {
    type: Number,
    required: true,
    min: 0,
    default: 0
  },
  
  quantityReserved: {
    type: Number,
    default: 0,
    min: 0
  },
  
  quantityAvailable: {
    type: Number,
    default: 0
    // Calculated: quantityInStock - quantityReserved
  },
  
  // Batch/Lot tracking
  batchNumber: String,
  
  expiryDate: Date,
  
  // Location details
  aisle: String,
  rack: String,
  shelf: String,
  bin: String,
  
  // Last updated
  lastCountDate: Date,
  
  createdAt: {
    type: Date,
    default: Date.now
  },
  
  updatedAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('Stock', stockSchema);
```

---

### 4. Warehouse Model

**File**: `backend/src/models/appModels/Warehouse.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const warehouseSchema = new Schema({
  removed: {
    type: Boolean,
    default: false
  },
  
  name: {
    type: String,
    required: true,
    unique: true,
    trim: true
  },
  
  code: {
    type: String,
    unique: true,
    uppercase: true
  },
  
  description: String,
  
  // Location
  address: String,
  city: String,
  country: String,
  zipCode: String,
  
  // Contact
  manager: {
    type: Schema.Types.ObjectId,
    ref: 'Admin'
  },
  
  phone: String,
  email: String,
  
  // Capacity
  totalCapacity: {
    type: Number,
    default: 0
  },
  
  currentOccupancy: {
    type: Number,
    default: 0
  },
  
  // Status
  status: {
    type: String,
    enum: ['active', 'inactive', 'maintenance'],
    default: 'active'
  },
  
  isDefault: {
    type: Boolean,
    default: false
  },
  
  createdAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('Warehouse', warehouseSchema);
```

---

### 5. Stock Transaction Model (Audit Trail)

**File**: `backend/src/models/appModels/StockTransaction.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const transactionSchema = new Schema({
  removed: {
    type: Boolean,
    default: false
  },
  
  product: {
    type: Schema.Types.ObjectId,
    ref: 'Product',
    required: true
  },
  
  warehouse: {
    type: Schema.Types.ObjectId,
    ref: 'Warehouse',
    required: true
  },
  
  // Transaction type
  type: {
    type: String,
    enum: [
      'purchase',        // Incoming stock from supplier
      'sale',            // Outgoing stock to customer
      'adjustment',      // Manual adjustment
      'damage',          // Damaged/loss
      'transfer',        // Transfer between warehouses
      'return',          // Customer return
      'count'            // Physical count
    ],
    required: true
  },
  
  // Reference
  referenceId: String,  // Invoice ID, Purchase Order ID, etc.
  referenceType: {
    type: String,
    enum: ['invoice', 'purchase_order', 'adjustment', 'transfer']
  },
  
  // Quantity
  quantityBefore: Number,
  quantityChanged: {
    type: Number,
    required: true
  },
  quantityAfter: Number,
  
  // Notes
  notes: String,
  
  // Who did it
  createdBy: {
    type: Schema.Types.ObjectId,
    ref: 'Admin',
    required: true
  },
  
  // Approval (optional)
  approvedBy: {
    type: Schema.Types.ObjectId,
    ref: 'Admin'
  },
  
  approvalDate: Date,
  
  createdAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('StockTransaction', transactionSchema);
```

---

### 6. Stock Adjustment Request Model

**File**: `backend/src/models/appModels/StockAdjustment.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const adjustmentSchema = new Schema({
  removed: {
    type: Boolean,
    default: false
  },
  
  adjustmentNumber: {
    type: String,
    unique: true,
    required: true
  },
  
  warehouse: {
    type: Schema.Types.ObjectId,
    ref: 'Warehouse',
    required: true
  },
  
  // Items being adjusted
  items: [{
    product: {
      type: Schema.Types.ObjectId,
      ref: 'Product'
    },
    quantityAdjustment: Number,
    reason: String,
    notes: String
  }],
  
  // Reason
  reason: {
    type: String,
    enum: ['damage', 'loss', 'manual_count', 'correction', 'other'],
    required: true
  },
  
  reasonDetails: String,
  
  // Status
  status: {
    type: String,
    enum: ['draft', 'pending', 'approved', 'rejected', 'completed'],
    default: 'draft'
  },
  
  // Approval workflow
  requestedBy: {
    type: Schema.Types.ObjectId,
    ref: 'Admin'
  },
  
  approvedBy: {
    type: Schema.Types.ObjectId,
    ref: 'Admin'
  },
  
  rejectionReason: String,
  
  adjustmentDate: Date,
  
  createdAt: {
    type: Date,
    default: Date.now
  },
  
  updatedAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('StockAdjustment', adjustmentSchema);
```

---

## ✨ Features

### 1. Product Management
- ✅ Add/Edit/Delete products
- ✅ SKU generation/management
- ✅ Barcode support
- ✅ Product categories
- ✅ Pricing management (cost + selling price)
- ✅ Tax assignment
- ✅ Supplier linking
- ✅ Product images
- ✅ Reorder settings

### 2. Stock Tracking
- ✅ Real-time stock levels
- ✅ Multi-warehouse support
- ✅ Reserved quantities
- ✅ Available stock calculation
- ✅ Batch/lot tracking
- ✅ Expiry date tracking
- ✅ Physical location tracking

### 3. Inventory Transactions
- ✅ Purchase orders (incoming)
- ✅ Sales/invoices (outgoing)
- ✅ Stock adjustments (damage/loss)
- ✅ Warehouse transfers
- ✅ Customer returns
- ✅ Physical counts
- ✅ Complete audit trail

### 4. Alerts & Reports
- ✅ Low stock warnings
- ✅ Overstock alerts
- ✅ Expiry date warnings
- ✅ Stock valuation reports
- ✅ Movement reports
- ✅ Turnover analysis

### 5. Warehouse Management
- ✅ Multiple warehouses
- ✅ Location tracking (aisle/rack/shelf/bin)
- ✅ Capacity management
- ✅ Warehouse managers
- ✅ Status management

---

## 🛠️ Implementation

### Step 1: Create Product Controller

**File**: `backend/src/controllers/appControllers/productController/index.js`

```javascript
const createCRUDController = require('@/controllers/middlewaresControllers/createCRUDController');
const methods = createCRUDController('Product');

const create = require('./create');
const update = require('./update');
const list = require('./list');

methods.create = create;
methods.update = update;
methods.list = list;

module.exports = methods;
```

**File**: `backend/src/controllers/appControllers/productController/create.js`

```javascript
const mongoose = require('mongoose');
const Product = mongoose.model('Product');
const { generate: uniqueId } = require('shortid');

const create = async (req, res) => {
  try {
    const { name, sku, sellingPrice, costPrice, category } = req.body;

    // Validate SKU uniqueness
    const existingSku = await Product.findOne({ sku: sku.toUpperCase() });
    if (existingSku) {
      return res.status(400).json({
        success: false,
        message: 'SKU already exists'
      });
    }

    const product = new Product({
      ...req.body,
      sku: sku.toUpperCase(),
      createdBy: req.admin._id
    });

    const result = await product.save();

    return res.status(201).json({
      success: true,
      result,
      message: 'Product created successfully'
    });

  } catch (error) {
    return res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

module.exports = create;
```

---

### Step 2: Create Stock Controller

**File**: `backend/src/controllers/appControllers/stockController/index.js`

```javascript
const adjustStock = require('./adjustStock');
const getStockLevel = require('./getStockLevel');
const getWarehouseStock = require('./getWarehouseStock');
const stockHistory = require('./stockHistory');

module.exports = {
  adjustStock,
  getStockLevel,
  getWarehouseStock,
  stockHistory
};
```

**File**: `backend/src/controllers/appControllers/stockController/adjustStock.js`

```javascript
const mongoose = require('mongoose');
const Stock = mongoose.model('Stock');
const StockTransaction = mongoose.model('StockTransaction');
const Product = mongoose.model('Product');

const adjustStock = async (req, res) => {
  try {
    const {
      product,
      warehouse,
      quantityChange,
      type,
      reason,
      referenceId,
      notes
    } = req.body;

    // Validate product exists
    const productDoc = await Product.findById(product);
    if (!productDoc) {
      return res.status(404).json({
        success: false,
        message: 'Product not found'
      });
    }

    // Get current stock
    let stock = await Stock.findOne({ product, warehouse });
    
    if (!stock) {
      stock = new Stock({
        product,
        warehouse,
        quantityInStock: 0,
        quantityReserved: 0,
        quantityAvailable: 0
      });
    }

    const quantityBefore = stock.quantityInStock;
    const quantityAfter = quantityBefore + quantityChange;

    // Prevent negative stock (except for certain transaction types)
    if (quantityAfter < 0 && type !== 'adjustment') {
      return res.status(400).json({
        success: false,
        message: 'Insufficient stock'
      });
    }

    // Update stock
    stock.quantityInStock = Math.max(0, quantityAfter);
    stock.quantityAvailable = stock.quantityInStock - stock.quantityReserved;
    stock.updatedAt = new Date();
    
    await stock.save();

    // Record transaction
    const transaction = new StockTransaction({
      product,
      warehouse,
      type,
      referenceId,
      referenceType: referenceId ? 'invoice' : null,
      quantityBefore,
      quantityChanged: quantityChange,
      quantityAfter: stock.quantityInStock,
      reason,
      notes,
      createdBy: req.admin._id
    });

    await transaction.save();

    // Check for low stock alert
    let lowStockAlert = false;
    if (stock.quantityInStock <= productDoc.reorderLevel) {
      lowStockAlert = true;
    }

    return res.status(200).json({
      success: true,
      result: {
        stock,
        transaction,
        lowStockAlert,
        message: lowStockAlert ? 
          `Low stock alert! Current: ${stock.quantityInStock}, Reorder at: ${productDoc.reorderLevel}` 
          : 'Stock adjusted successfully'
      }
    });

  } catch (error) {
    return res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

module.exports = adjustStock;
```

**File**: `backend/src/controllers/appControllers/stockController/getStockLevel.js`

```javascript
const mongoose = require('mongoose');
const Stock = mongoose.model('Stock');
const Product = mongoose.model('Product');

const getStockLevel = async (req, res) => {
  try {
    const { productId } = req.query;

    const stock = await Stock.find({ product: productId, removed: false })
      .populate('product')
      .populate('warehouse');

    // Calculate totals across all warehouses
    const totalStock = stock.reduce((sum, s) => sum + s.quantityInStock, 0);
    const totalReserved = stock.reduce((sum, s) => sum + s.quantityReserved, 0);
    const totalAvailable = totalStock - totalReserved;

    const product = await Product.findById(productId);

    return res.status(200).json({
      success: true,
      result: {
        product,
        warehouseBreakdown: stock,
        totals: {
          totalInStock: totalStock,
          totalReserved,
          totalAvailable,
          reorderLevel: product?.reorderLevel || 0,
          status: totalAvailable <= product?.reorderLevel ? 'LOW' : 'OK'
        }
      }
    });

  } catch (error) {
    return res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

module.exports = getStockLevel;
```

---

### Step 3: Create Routes

**File**: `backend/src/routes/appRoutes/inventoryApi.js`

```javascript
const express = require('express');
const router = express.Router();
const { catchErrors } = require('@/handlers/errorHandlers');

// Product routes
const productController = require('@/controllers/appControllers/productController');
router.post('/product/create', catchErrors(productController.create));
router.get('/product/read/:id', catchErrors(productController.read));
router.patch('/product/update/:id', catchErrors(productController.update));
router.delete('/product/delete/:id', catchErrors(productController.delete));
router.get('/product/list', catchErrors(productController.list));
router.get('/product/search', catchErrors(productController.search));
router.get('/product/filter', catchErrors(productController.filter));

// Category routes
const categoryController = require('@/controllers/appControllers/categoryController');
router.post('/category/create', catchErrors(categoryController.create));
router.get('/category/list', catchErrors(categoryController.list));

// Stock routes
const stockController = require('@/controllers/appControllers/stockController');
router.post('/stock/adjust', catchErrors(stockController.adjustStock));
router.get('/stock/level', catchErrors(stockController.getStockLevel));
router.get('/stock/warehouse/:warehouseId', catchErrors(stockController.getWarehouseStock));
router.get('/stock/history/:productId', catchErrors(stockController.stockHistory));

// Warehouse routes
const warehouseController = require('@/controllers/appControllers/warehouseController');
router.post('/warehouse/create', catchErrors(warehouseController.create));
router.get('/warehouse/list', catchErrors(warehouseController.list));
router.patch('/warehouse/update/:id', catchErrors(warehouseController.update));

module.exports = router;
```

---

## 🔌 API Endpoints

### Product Management

#### Create Product
```bash
POST /api/product/create
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Laptop Dell XPS 13",
  "sku": "DELL-XPS-13-2026",
  "barcode": "123456789012",
  "description": "High performance laptop",
  "category": "{categoryId}",
  "unit": "pcs",
  "costPrice": 800,
  "sellingPrice": 1200,
  "discount": 5,
  "reorderLevel": 5,
  "reorderQuantity": 20,
  "maxStock": 100,
  "supplier": "{supplierId}",
  "taxId": "{taxId}"
}

Response 201:
{
  "success": true,
  "result": {
    "_id": "507f1f77bcf86cd799439011",
    "name": "Laptop Dell XPS 13",
    "sku": "DELL-XPS-13-2026",
    "status": "active",
    "createdAt": "2026-01-28T10:30:00Z"
  }
}
```

### Stock Management

#### Adjust Stock
```bash
POST /api/stock/adjust
Authorization: Bearer {token}
Content-Type: application/json

{
  "product": "{productId}",
  "warehouse": "{warehouseId}",
  "quantityChange": 50,
  "type": "purchase",
  "reason": "New stock received",
  "referenceId": "PO-001",
  "notes": "Received from supplier"
}

Response 200:
{
  "success": true,
  "result": {
    "stock": {
      "quantityInStock": 50,
      "quantityAvailable": 50,
      "quantityReserved": 0
    },
    "transaction": {
      "_id": "507f1f77bcf86cd799439012",
      "type": "purchase",
      "quantityBefore": 0,
      "quantityChanged": 50,
      "quantityAfter": 50
    },
    "lowStockAlert": false
  }
}
```

#### Get Stock Level
```bash
GET /api/stock/level?productId={productId}
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "result": {
    "product": { ... },
    "warehouseBreakdown": [
      {
        "warehouse": "Main Warehouse",
        "quantityInStock": 50,
        "quantityReserved": 10,
        "quantityAvailable": 40
      }
    ],
    "totals": {
      "totalInStock": 50,
      "totalReserved": 10,
      "totalAvailable": 40,
      "reorderLevel": 10,
      "status": "OK"
    }
  }
}
```

### Warehouse Management

#### Create Warehouse
```bash
POST /api/warehouse/create
Authorization: Bearer {token}

{
  "name": "Main Warehouse",
  "code": "MW-001",
  "address": "123 Storage Lane",
  "city": "New York",
  "country": "USA",
  "manager": "{managerId}",
  "totalCapacity": 5000,
  "status": "active",
  "isDefault": true
}
```

---

## 🔗 Integration with Invoicing

### When Creating an Invoice

```javascript
// 1. Check stock availability
const stock = await Stock.findOne({ 
  product: lineItem.product, 
  warehouse: defaultWarehouse 
});

if (stock.quantityAvailable < lineItem.quantity) {
  return res.status(400).json({
    success: false,
    message: `Insufficient stock. Available: ${stock.quantityAvailable}`
  });
}

// 2. Reserve stock
stock.quantityReserved += lineItem.quantity;
stock.quantityAvailable = stock.quantityInStock - stock.quantityReserved;
await stock.save();

// 3. Record transaction
const transaction = new StockTransaction({
  product: lineItem.product,
  type: 'sale',
  referenceId: invoiceId,
  quantityChanged: -lineItem.quantity,
  createdBy: req.admin._id
});
await transaction.save();
```

---

## 📊 Reports & Dashboards

### Stock Valuation Report
```
┌─────────────────────────────────────┐
│     Stock Valuation Report          │
├─────────────────────────────────────┤
│ Product      │ Qty  │ Unit │ Value │
├──────────────┼──────┼──────┼───────┤
│ Laptop Dell  │ 50   │$800  │$40K   │
│ Monitor LG   │ 120  │$200  │$24K   │
│ USB Cable    │ 500  │$5    │$2.5K  │
├──────────────┼──────┼──────┼───────┤
│ TOTAL        │      │      │$66.5K │
└─────────────────────────────────────┘
```

### Movement Report
```
┌──────────────────────────────────────────┐
│     Monthly Stock Movement               │
├──────────────────────────────────────────┤
│ Product      │ In   │ Out  │ Balance   │
├──────────────┼──────┼──────┼───────────┤
│ Laptop Dell  │ 100  │ 45   │ 55        │
│ Monitor LG   │ 50   │ 30   │ 20        │
│ USB Cable    │ 200  │ 150  │ 50        │
└──────────────────────────────────────────┘
```

### Low Stock Alert
```
⚠️ LOW STOCK ALERTS
├─ Laptop Dell: 5 units (Reorder: 10)
├─ Monitor LG: 8 units (Reorder: 15)
└─ USB Cable: 12 units (Reorder: 50)
```

---

## 🔄 Workflow Examples

### Workflow 1: Receiving New Stock

```
1. Receive Purchase Order
   ↓
2. Create Stock Adjustment (type: purchase)
   ↓
3. Update warehouse stock levels
   ↓
4. Record transaction
   ↓
5. Check reorder alert
   ✅ Stock updated
```

### Workflow 2: Processing Invoice

```
1. Create Invoice with products
   ↓
2. Check stock availability
   ↓
3. If sufficient: Reserve stock
   ↓
4. Record transaction (type: sale)
   ↓
5. Update stock levels
   ↓
6. Generate invoice
   ✅ Invoice created, stock updated
```

### Workflow 3: Stock Transfer Between Warehouses

```
1. Create transfer request
   ↓
2. Deduct from source warehouse
   ↓
3. Add to destination warehouse
   ↓
4. Record transfer transaction
   ✅ Stock transferred
```

---

## 🎨 Frontend Components

### Product List Component
```jsx
<Table
  columns={[
    { title: 'SKU', dataIndex: 'sku' },
    { title: 'Product Name', dataIndex: 'name' },
    { title: 'Category', dataIndex: 'category.name' },
    { title: 'Cost Price', dataIndex: 'costPrice' },
    { title: 'Selling Price', dataIndex: 'sellingPrice' },
    { title: 'Stock Status', render: (_, record) => getStockStatus(record) },
    { title: 'Actions', render: (_, record) => <EditDeleteButtons /> }
  ]}
/>
```

### Stock Level Widget
```jsx
<Card title="Stock Level">
  <Row>
    <Col>
      <Statistic title="In Stock" value={stock.quantityInStock} />
    </Col>
    <Col>
      <Statistic title="Reserved" value={stock.quantityReserved} />
    </Col>
    <Col>
      <Statistic title="Available" value={stock.quantityAvailable} />
    </Col>
  </Row>
</Card>
```

---

## ✅ Quick Setup Checklist

- [ ] Create Product model
- [ ] Create Stock model
- [ ] Create Warehouse model
- [ ] Create StockTransaction model
- [ ] Create Product controller
- [ ] Create Stock controller
- [ ] Create Warehouse controller
- [ ] Create routes
- [ ] Create frontend components
- [ ] Integrate with invoice system
- [ ] Add low stock alerts
- [ ] Create reports

---

## 🎯 Next Features to Consider

1. **Barcode Scanning** - Mobile app scanning
2. **Cycle Counting** - Regular inventory counts
3. **Supplier Management** - PO automation
4. **Serial Number Tracking** - Individual item tracking
5. **Batch Expiry Tracking** - FIFO/LIFO
6. **ABC Analysis** - Stock optimization
7. **Demand Forecasting** - AI-based predictions
8. **Multi-location Transfers** - Complex transfers

---

**Ready to manage your inventory! 📦✨**
