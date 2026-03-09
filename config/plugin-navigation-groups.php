<?php

/**
 * Maps navigation group (label or translation key) to plugin name for multi-tenant plugin filtering.
 * When a company has not enabled a plugin, nav items in these groups are hidden and routes return 403.
 * Labels must match admin.navigation lang (e.g. Sales, Purchase, Invoices, Accounting, etc.).
 */
return [
    'webkul.sales' => ['admin.navigation.sale', 'Sale', 'Sales'],
    'webkul.purchases' => ['admin.navigation.purchase', 'Purchase', 'Purchases'],
    'webkul.invoices' => ['admin.navigation.invoice', 'Invoice', 'Invoices'],
    'webkul.accounts' => ['admin.navigation.accounting', 'Accounting', 'Accounts'],
    'webkul.accounting' => ['admin.navigation.accounting', 'Accounting'],
    'webkul.inventories' => ['admin.navigation.inventory', 'Inventory', 'Inventories'],
    'webkul.projects' => ['admin.navigation.project', 'Project', 'Projects'],
    'webkul.employees' => ['admin.navigation.employee', 'Employee', 'Employees'],
    'webkul.time-off' => ['admin.navigation.time-off', 'Time-off', 'Time Off', 'Time off'],
    'webkul.recruitments' => ['admin.navigation.recruitment', 'Recruitment', 'Recruitments'],
    'webkul.website' => ['admin.navigation.website', 'Website'],
    'webkul.contacts' => ['admin.navigation.contact', 'Contact', 'Contacts'],
    'webkul.rov-inspection' => ['admin.navigation.rov-inspection', 'ROV Inspections', 'Inspections'],
    'webkul.products' => [],
    'webkul.partners' => [],
    'webkul.support' => [],
    'webkul.plugin-manager' => ['admin.navigation.plugin', 'Plugin', 'Plugins'],
];
