# IDURAR ERP/CRM - User & Role Management Guide

**Date Created**: January 28, 2026

---

## 📋 Table of Contents
1. [Current User System](#current-user-system)
2. [Adding New Users](#adding-new-users)
3. [Role Management Enhancement](#role-management-enhancement)
4. [Implementation Steps](#implementation-steps)
5. [API Integration](#api-integration)
6. [Best Practices](#best-practices)

---

## 🔐 Current User System

### Existing Architecture

The system currently has a **single-role model** with only "owner" role:

```javascript
// Current Admin Model
{
  email: String (unique),
  name: String,
  surname: String,
  role: String (enum: ['owner']),  // Only 'owner' allowed
  enabled: Boolean,
  photo: String,
  created: Date
}
```

### Current Limitations:
- ❌ Only one role type (owner)
- ❌ No user creation interface
- ❌ No permission system
- ❌ All users have full access
- ❌ No department/team structure

---

## 🆕 Enhanced Role Management System

### Proposed Role Hierarchy

```
┌─────────────────────────────────────────────┐
│         ADMIN HIERARCHY                      │
├─────────────────────────────────────────────┤
│                                              │
│  1. OWNER (Super Admin)                     │
│     └─ Full system access                   │
│     └─ Manage users & roles                 │
│     └─ View all data                        │
│     └─ Configure settings                   │
│                                              │
│  2. MANAGER (Department Manager)            │
│     └─ Manage staff in department           │
│     └─ Create invoices/quotes               │
│     └─ View reports                         │
│     └─ Cannot modify system settings        │
│                                              │
│  3. ACCOUNTANT (Finance)                    │
│     └─ Create/edit invoices                 │
│     └─ Record payments                      │
│     └─ Generate reports                     │
│     └─ Cannot delete data                   │
│                                              │
│  4. SALES (Sales Rep)                       │
│     └─ Create quotes                        │
│     └─ Manage clients                       │
│     └─ View own invoices                    │
│     └─ Cannot access payments               │
│                                              │
│  5. USER (Basic User)                       │
│     └─ View-only access                     │
│     └─ Generate reports                     │
│     └─ Cannot create/edit                   │
│                                              │
└─────────────────────────────────────────────┘
```

---

## 📝 Adding New Users

### Method 1: Direct Database Insert (Current Method)

**Step 1**: Connect to MongoDB
```javascript
// In MongoDB Compass or CLI
use idurar_database
```

**Step 2**: Insert new user
```javascript
db.admins.insertOne({
  email: "john.doe@company.com",
  name: "John",
  surname: "Doe",
  enabled: true,
  role: "manager",
  photo: null,
  removed: false,
  created: new Date()
})
```

**Step 3**: Add password separately
```javascript
const crypto = require('crypto');
const salt = 'unique_salt_value';
const password = 'temporary_password';
const hash = crypto.createHash('sha256').update(password + salt).digest('hex');

db.adminpasswords.insertOne({
  password: hash,
  salt: salt,
  emailVerified: true,
  user: ObjectId("admin_id_from_previous_insert")
})
```

---

### Method 2: Backend API (Recommended)

#### Create User Endpoint (to be implemented)

```bash
POST /api/admin/create
Content-Type: application/json

{
  "email": "sales@company.com",
  "name": "Sarah",
  "surname": "Johnson",
  "role": "sales",
  "password": "SecurePassword123!",
  "enabled": true
}
```

**Response**:
```json
{
  "success": true,
  "result": {
    "_id": "507f1f77bcf86cd799439011",
    "email": "sales@company.com",
    "name": "Sarah",
    "surname": "Johnson",
    "role": "sales",
    "enabled": true,
    "created": "2026-01-28T10:30:00Z"
  },
  "message": "User created successfully"
}
```

---

### Method 3: Admin Dashboard UI (Best UX)

**Frontend Flow**:

```
Dashboard → Settings → User Management → + Add User

User Creation Form:
├─ Email (text input)
├─ First Name (text input)
├─ Last Name (text input)
├─ Role (dropdown: owner, manager, accountant, sales, user)
├─ Set Password (password input)
├─ Confirm Password (password input)
├─ Enabled (toggle)
└─ [Create User] [Cancel] buttons

Success → User receives login credentials
```

---

## 🛠️ Implementation Steps

### Step 1: Update Admin Model

**File**: `backend/src/models/coreModels/Admin.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const adminSchema = new Schema({
  removed: {
    type: Boolean,
    default: false,
  },
  enabled: {
    type: Boolean,
    default: false,
  },

  email: {
    type: String,
    lowercase: true,
    trim: true,
    required: true,
    unique: true,
  },
  name: { 
    type: String, 
    required: true 
  },
  surname: { 
    type: String 
  },
  photo: {
    type: String,
    trim: true,
  },
  
  // NEW: Role with multiple options
  role: {
    type: String,
    required: true,
    enum: ['owner', 'manager', 'accountant', 'sales', 'user'],
    default: 'user',
  },
  
  // NEW: Permissions array for granular control
  permissions: [{
    type: String,
    enum: [
      'create_invoice',
      'edit_invoice',
      'delete_invoice',
      'create_quote',
      'edit_quote',
      'delete_quote',
      'record_payment',
      'manage_clients',
      'view_reports',
      'manage_settings',
      'manage_users',
      'manage_roles'
    ]
  }],
  
  // NEW: Department assignment
  department: {
    type: String,
    enum: ['sales', 'finance', 'operations', 'admin']
  },
  
  // NEW: Track user management
  createdBy: {
    type: Schema.Types.ObjectId,
    ref: 'Admin'
  },
  
  lastLogin: {
    type: Date
  },
  
  // NEW: Status tracking
  status: {
    type: String,
    enum: ['active', 'inactive', 'suspended'],
    default: 'active'
  },

  created: {
    type: Date,
    default: Date.now,
  },
});

module.exports = mongoose.model('Admin', adminSchema);
```

---

### Step 2: Create Role Model

**File**: `backend/src/models/coreModels/Role.js`

```javascript
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const roleSchema = new Schema({
  name: {
    type: String,
    required: true,
    unique: true,
    enum: ['owner', 'manager', 'accountant', 'sales', 'user']
  },
  
  description: {
    type: String,
    required: true
  },
  
  permissions: [{
    type: String,
    enum: [
      'create_invoice',
      'edit_invoice',
      'delete_invoice',
      'create_quote',
      'edit_quote',
      'delete_quote',
      'record_payment',
      'manage_clients',
      'view_reports',
      'manage_settings',
      'manage_users',
      'manage_roles'
    ]
  }],
  
  active: {
    type: Boolean,
    default: true
  },
  
  created: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('Role', roleSchema);
```

---

### Step 3: Create User Management Controller

**File**: `backend/src/controllers/coreControllers/userManagementController/index.js`

```javascript
const mongoose = require('mongoose');
const Admin = mongoose.model('Admin');
const AdminPassword = mongoose.model('AdminPassword');
const { generate: uniqueId } = require('shortid');

// CREATE NEW USER
exports.createUser = async (req, res) => {
  try {
    const { email, name, surname, role, password, enabled } = req.body;

    // Validate required fields
    if (!email || !name || !password) {
      return res.status(400).json({
        success: false,
        message: 'Email, name, and password are required'
      });
    }

    // Check if user already exists
    const existingUser = await Admin.findOne({ email });
    if (existingUser) {
      return res.status(409).json({
        success: false,
        message: 'User with this email already exists'
      });
    }

    // Create new admin user
    const newAdmin = new Admin({
      email,
      name,
      surname,
      role: role || 'user',
      enabled: enabled || false,
      removed: false,
      createdBy: req.admin._id
    });

    const savedAdmin = await newAdmin.save();

    // Hash password
    const salt = uniqueId();
    const newAdminPassword = new AdminPassword();
    const passwordHash = newAdminPassword.generateHash(salt, password);

    // Create password record
    const passwordRecord = new AdminPassword({
      password: passwordHash,
      salt: salt,
      emailVerified: false,
      user: savedAdmin._id
    });

    await passwordRecord.save();

    return res.status(201).json({
      success: true,
      result: {
        _id: savedAdmin._id,
        email: savedAdmin.email,
        name: savedAdmin.name,
        surname: savedAdmin.surname,
        role: savedAdmin.role,
        enabled: savedAdmin.enabled,
        created: savedAdmin.created
      },
      message: 'User created successfully. They should change password on first login.'
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// GET ALL USERS
exports.getAllUsers = async (req, res) => {
  try {
    const users = await Admin.find({ removed: false })
      .select('-photo')
      .sort({ created: -1 });

    return res.status(200).json({
      success: true,
      result: users,
      message: 'Users retrieved successfully'
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// GET SINGLE USER
exports.getUser = async (req, res) => {
  try {
    const user = await Admin.findById(req.params.id)
      .select('-photo');

    if (!user || user.removed) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    return res.status(200).json({
      success: true,
      result: user,
      message: 'User retrieved successfully'
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// UPDATE USER
exports.updateUser = async (req, res) => {
  try {
    const { name, surname, role, enabled, status } = req.body;

    const user = await Admin.findByIdAndUpdate(
      req.params.id,
      {
        name,
        surname,
        role,
        enabled,
        status
      },
      { new: true }
    ).select('-photo');

    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    return res.status(200).json({
      success: true,
      result: user,
      message: 'User updated successfully'
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// DELETE USER (Soft Delete)
exports.deleteUser = async (req, res) => {
  try {
    const user = await Admin.findByIdAndUpdate(
      req.params.id,
      { removed: true },
      { new: true }
    );

    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    return res.status(200).json({
      success: true,
      message: 'User deleted successfully'
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// CHANGE USER PASSWORD
exports.changeUserPassword = async (req, res) => {
  try {
    const { newPassword } = req.body;

    if (!newPassword) {
      return res.status(400).json({
        success: false,
        message: 'New password is required'
      });
    }

    const salt = uniqueId();
    const adminPassword = new AdminPassword();
    const passwordHash = adminPassword.generateHash(salt, newPassword);

    await AdminPassword.findOneAndUpdate(
      { user: req.params.id },
      {
        password: passwordHash,
        salt: salt
      },
      { new: true }
    );

    return res.status(200).json({
      success: true,
      message: 'Password changed successfully'
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// RESET USER PASSWORD
exports.resetUserPassword = async (req, res) => {
  try {
    const tempPassword = Math.random().toString(36).slice(-8).toUpperCase();
    
    const salt = uniqueId();
    const adminPassword = new AdminPassword();
    const passwordHash = adminPassword.generateHash(salt, tempPassword);

    await AdminPassword.findOneAndUpdate(
      { user: req.params.id },
      {
        password: passwordHash,
        salt: salt,
        emailVerified: false
      },
      { new: true }
    );

    return res.status(200).json({
      success: true,
      result: {
        tempPassword: tempPassword,
        message: 'Share this temporary password with the user'
      },
      message: 'Password reset successfully'
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};

// DISABLE/ENABLE USER
exports.toggleUserStatus = async (req, res) => {
  try {
    const user = await Admin.findById(req.params.id);
    
    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    user.enabled = !user.enabled;
    await user.save();

    return res.status(200).json({
      success: true,
      result: user,
      message: `User ${user.enabled ? 'enabled' : 'disabled'} successfully`
    });

  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
};
```

---

### Step 4: Create Routes

**File**: `backend/src/routes/coreRoutes/userRoutes.js`

```javascript
const express = require('express');
const router = express.Router();
const userController = require('@/controllers/coreControllers/userManagementController');
const { catchErrors } = require('@/handlers/errorHandlers');

// User management routes
router.post('/user/create', catchErrors(userController.createUser));
router.get('/user/list', catchErrors(userController.getAllUsers));
router.get('/user/:id', catchErrors(userController.getUser));
router.patch('/user/:id', catchErrors(userController.updateUser));
router.delete('/user/:id', catchErrors(userController.deleteUser));
router.patch('/user/:id/password', catchErrors(userController.changeUserPassword));
router.patch('/user/:id/reset-password', catchErrors(userController.resetUserPassword));
router.patch('/user/:id/toggle-status', catchErrors(userController.toggleUserStatus));

module.exports = router;
```

---

## 🔌 API Integration

### User Management Endpoints

#### 1. Create New User
```bash
POST /api/user/create
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "email": "accountant@company.com",
  "name": "Maria",
  "surname": "Garcia",
  "role": "accountant",
  "password": "SecurePass@2026",
  "enabled": true
}

Response 201:
{
  "success": true,
  "result": {
    "_id": "507f1f77bcf86cd799439011",
    "email": "accountant@company.com",
    "name": "Maria",
    "surname": "Garcia",
    "role": "accountant",
    "enabled": true,
    "created": "2026-01-28T10:30:00Z"
  },
  "message": "User created successfully"
}
```

#### 2. Get All Users
```bash
GET /api/user/list
Authorization: Bearer {jwt_token}

Response 200:
{
  "success": true,
  "result": [
    {
      "_id": "507f1f77bcf86cd799439011",
      "email": "admin@admin.com",
      "name": "IDURAR",
      "surname": "Admin",
      "role": "owner",
      "enabled": true,
      "created": "2026-01-28T08:00:00Z"
    },
    {
      "_id": "507f1f77bcf86cd799439012",
      "email": "accountant@company.com",
      "name": "Maria",
      "surname": "Garcia",
      "role": "accountant",
      "enabled": true,
      "created": "2026-01-28T10:30:00Z"
    }
  ]
}
```

#### 3. Update User
```bash
PATCH /api/user/{userId}
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "role": "manager",
  "enabled": false,
  "status": "inactive"
}

Response 200:
{
  "success": true,
  "result": {
    "_id": "507f1f77bcf86cd799439012",
    "email": "accountant@company.com",
    "name": "Maria",
    "surname": "Garcia",
    "role": "manager",
    "enabled": false,
    "status": "inactive"
  },
  "message": "User updated successfully"
}
```

#### 4. Reset User Password
```bash
PATCH /api/user/{userId}/reset-password
Authorization: Bearer {jwt_token}

Response 200:
{
  "success": true,
  "result": {
    "tempPassword": "ABC12XYZ",
    "message": "Share this temporary password with the user"
  },
  "message": "Password reset successfully"
}
```

#### 5. Delete User (Soft Delete)
```bash
DELETE /api/user/{userId}
Authorization: Bearer {jwt_token}

Response 200:
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

## 📊 Role Permissions Matrix

```
┌──────────────┬─────────┬────────┬────────────┬───────┬──────┐
│ Permission   │ Owner   │Manager │ Accountant │ Sales │ User │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Create       │   ✅    │   ✅   │    ✅      │   ✅  │  ❌  │
│ Invoice      │         │        │            │       │      │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Edit Invoice │   ✅    │   ✅   │    ✅      │   ✅  │  ❌  │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Delete       │   ✅    │   ✅   │    ❌      │   ❌  │  ❌  │
│ Invoice      │         │        │            │       │      │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Create Quote │   ✅    │   ✅   │    ❌      │   ✅  │  ❌  │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Record       │   ✅    │   ✅   │    ✅      │   ❌  │  ❌  │
│ Payment      │         │        │            │       │      │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Manage       │   ✅    │   ✅   │    ✅      │   ✅  │  ❌  │
│ Clients      │         │        │            │       │      │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ View Reports │   ✅    │   ✅   │    ✅      │   ✅  │  ✅  │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Manage       │   ✅    │   ❌   │    ❌      │   ❌  │  ❌  │
│ Settings     │         │        │            │       │      │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Manage Users │   ✅    │   ❌   │    ❌      │   ❌  │  ❌  │
├──────────────┼─────────┼────────┼────────────┼───────┼──────┤
│ Manage Roles │   ✅    │   ❌   │    ❌      │   ❌  │  ❌  │
└──────────────┴─────────┴────────┴────────────┴───────┴──────┘
```

---

## 🛡️ Best Practices

### 1. **Password Security**
✅ Always hash passwords (using bcryptjs)
✅ Generate unique salt per user
✅ Force password change on first login
✅ Implement password complexity rules
✅ Never store plain text passwords

### 2. **User Management**
✅ Use soft delete (don't hard delete users)
✅ Track who created/modified users
✅ Log all user management activities
✅ Send email notifications for new accounts
✅ Implement account locking after failed attempts

### 3. **Authorization**
✅ Verify role before allowing actions
✅ Implement permission checking middleware
✅ Use JWT for stateless authentication
✅ Refresh tokens periodically
✅ Validate user status (enabled/disabled)

### 4. **Audit Trail**
✅ Log all user creations
✅ Track password changes
✅ Record role modifications
✅ Monitor failed login attempts
✅ Keep audit logs for compliance

---

## 📱 Frontend User Management Component

### Suggested UI Structure

```jsx
// Components/UserManagement/UserList.jsx
import React, { useEffect, useState } from 'react';
import { Table, Button, Modal, Form } from 'antd';
import axios from 'axios';

export default function UserList() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);

  const columns = [
    { title: 'Email', dataIndex: 'email', key: 'email' },
    { title: 'Name', dataIndex: 'name', key: 'name' },
    { title: 'Role', dataIndex: 'role', key: 'role' },
    { title: 'Status', dataIndex: 'enabled', key: 'enabled',
      render: (enabled) => enabled ? '✅ Active' : '❌ Inactive'
    },
    { title: 'Created', dataIndex: 'created', key: 'created' },
    {
      title: 'Actions',
      key: 'actions',
      render: (_, record) => (
        <>
          <Button type="link" onClick={() => editUser(record)}>Edit</Button>
          <Button type="link" danger onClick={() => deleteUser(record._id)}>Delete</Button>
        </>
      )
    }
  ];

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/api/user/list', {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      setUsers(response.data.result);
    } catch (error) {
      console.error('Failed to fetch users:', error);
    }
    setLoading(false);
  };

  return (
    <div>
      <Button type="primary" onClick={() => setModalVisible(true)}>
        + Add New User
      </Button>
      <Table columns={columns} dataSource={users} loading={loading} />
    </div>
  );
}
```

---

## 🎯 Quick Setup Checklist

- [ ] Update Admin model with new roles
- [ ] Create Role model
- [ ] Create user management controller
- [ ] Create user management routes
- [ ] Add routes to main app.js
- [ ] Create permission middleware
- [ ] Create frontend UI component
- [ ] Test API endpoints with Postman
- [ ] Add email notifications for new users
- [ ] Implement audit logging

---

## 📞 Support

For implementing this role management system:
1. Review the code structure
2. Test with Postman API calls
3. Create frontend components
4. Implement permission middleware
5. Add audit logging

---

**Ready to add multi-user support to your IDURAR ERP/CRM! 🚀**
