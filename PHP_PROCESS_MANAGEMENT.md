# PHP Process Management Guide

## Finding & Killing PHP Processes on Windows

### Problem
PHP processes may hang or get stuck, requiring manual termination before restarting the application.

---

## Quick Commands

### 1. Find All Running PHP Processes
```powershell
Get-Process | Where-Object {$_.ProcessName -like "*php*"}
```

**Output Example:**
```
 Handles  NPM(K)    PM(K)      WS(K)     CPU(s)     Id  SI ProcessName
 -------  ------    -----      -----     ------     --  -- -----------
    450      25   145600      89450    156.23   5432   1 php
    380      22   102300      76200     98.15   7824   1 php
```

---

### 2. Kill All PHP Processes at Once
```powershell
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Stop-Process -Force
```

**What it does:**
- Finds all PHP processes
- Terminates them forcefully (`-Force` flag)
- No user confirmation needed

---

### 3. Kill a Specific PHP Process by ID
```powershell
Stop-Process -Id <PID> -Force
```

**Example (kill process with ID 5432):**
```powershell
Stop-Process -Id 5432 -Force
```

---

### 4. Graceful Shutdown (Optional, but cleaner)
```powershell
Stop-Process -Id <PID> -ErrorAction SilentlyContinue
```

Waits up to 5 seconds before forcing termination. If process doesn't exit gracefully, won't error.

---

## Common Scenarios

### Scenario 1: Server Won't Start (Stuck from Previous Run)
```powershell
# 1. Kill all PHP processes
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Stop-Process -Force

# 2. Clear caches (fixes many issues)
php artisan config:clear; php artisan cache:clear

# 3. Start fresh
php artisan serve
```

### Scenario 2: Need to See Details Before Killing
```powershell
# List with more details
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Format-Table -Property Name, Id, CPU, Memory

# Then kill specific one
Stop-Process -Id <ID_from_above> -Force
```

### Scenario 3: Long-Running Process Causing Timeout
If a PHP process is using too much CPU:
```powershell
# Find the greedy process
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Sort-Object CPU -Descending | Select-Object -First 1

# Kill it
Stop-Process -Id <highest_CPU_process_ID> -Force
```

---

## Root Causes of PHP Hangs

**Common Issues:**
1. **Database timeout** - MySQL connection hangs
2. **Infinite loops** - Code stuck in while/foreach
3. **File locks** - Process waiting for file access
4. **Memory exhaustion** - Not enough RAM allocated
5. **Previous serve command crashed** - Background process still running

---

## Prevention Best Practices

### 1. Fix the `set_time_limit()` Issue
**File:** `app/Providers/Filament/AdminPanelProvider.php`

Change from:
```php
set_time_limit(300);  // ❌ Causes 300-second timeout
```

To:
```php
set_time_limit(0);    // ✅ Unlimited execution time
```

### 2. Use Proper Session Storage
**File:** `.env`

```env
SESSION_DRIVER=file     # ✅ File-based sessions (fast, no DB hits)
# SESSION_DRIVER=database  # ❌ Slower, requires DB connection on startup
```

### 3. Monitor Database Connection
If MySQL is slow or unresponsive:
```powershell
# Check if MySQL is running
Get-Process | Where-Object {$_.ProcessName -like "*mysql*"}

# Restart XAMPP if needed
# Open XAMPP Control Panel manually and restart MySQL
```

### 4. Increase Allowed Memory
**File:** `.env` (if using PHP config override)

```env
PHP_MEMORY_LIMIT=512M    # Increase if getting out-of-memory errors
```

---

## Workflow: Safe Server Restart

When you need to restart the development server:

```powershell
# Step 1: Kill any stuck PHP processes
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Stop-Process -Force -ErrorAction SilentlyContinue

# Step 2: Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Step 3: Start server
php artisan serve
```

Or create a reusable alias in PowerShell profile:
```powershell
# Add to PowerShell profile (~\Documents\PowerShell\profile.ps1)
function phpkill {
    Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Stop-Process -Force -ErrorAction SilentlyContinue
}

function phpserve {
    phpkill
    php artisan config:clear; php artisan cache:clear
    php artisan serve
}

# Then just run:
phpserve
```

---

## Troubleshooting

### Still Getting 300-Second Timeout?
1. Check `AdminPanelProvider.php` has `set_time_limit(0)`
2. Check `.env` has `SESSION_DRIVER=file`
3. Check MySQL is running (see in XAMPP Control Panel)
4. Clear all caches: `php artisan cache:clear`
5. Kill all PHP processes and restart

### Process Won't Die
```powershell
# Use -Confirm:$false to bypass any prompts
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Stop-Process -Force -Confirm:$false
```

### Seeing "Address already in use" on Port 8000
```powershell
# Find process using port 8000
Get-NetTCPConnection -LocalPort 8000 | Select-Object OwningProcess

# Kill it by PID (e.g., 5432)
Stop-Process -Id 5432 -Force
```

---

## Quick Reference Card

| Task | Command |
|------|---------|
| List PHP processes | `Get-Process \| Where-Object {$_.ProcessName -like "*php*"}` |
| Kill all PHP | `Get-Process \| Where-Object {$_.ProcessName -like "*php*"} \| Stop-Process -Force` |
| Kill by ID | `Stop-Process -Id 5432 -Force` |
| Restart server | `phpkill; php artisan serve` |
| Clear caches | `php artisan config:clear; php artisan cache:clear` |
| Check MySQL | `Get-Process \| Where-Object {$_.ProcessName -like "*mysql*"}` |

---

## Related Files

- [MULTI_TENANCY_IMPLEMENTATION.md](MULTI_TENANCY_IMPLEMENTATION.md) - Multi-tenancy setup guide
- `app/Providers/Filament/AdminPanelProvider.php` - Where `set_time_limit()` is set
- `.env` - Environment configuration
- `storage/logs/laravel.log` - Application error logs
