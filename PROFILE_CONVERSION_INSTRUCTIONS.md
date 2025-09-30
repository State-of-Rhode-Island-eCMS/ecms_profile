# ECMS Profile Conversion Instructions

## Overview
This document provides step-by-step instructions for converting a Drupal ECMS installation from the `ecms_acquia` profile to the `ecms_base` profile using a low-level database approach.

## ⚠️ **CRITICAL PREREQUISITES**

### 1. **MANDATORY BACKUP**
```bash
# Create a complete database backup
mysqldump -u [username] -p [database_name] > backup_before_profile_conversion_$(date +%Y%m%d_%H%M%S).sql

# OR using drush (if available)
drush sql:dump --result-file=backup_before_profile_conversion_$(date +%Y%m%d_%H%M%S).sql
```

### 2. **Test Environment**
- **NEVER run this on production first**
- Test on a development or staging copy of the database
- Verify the conversion works before applying to production

### 3. **Verify File Structure**
Ensure both profiles exist in your codebase:
```bash
ls -la ecms_acquia/ecms_acquia.info.yml
ls -la ecms_base/ecms_base.info.yml
```

## 🚀 **Conversion Process**

### Step 1: Prepare the Environment

1. **Navigate to the project directory:**
   ```bash
   cd /home/oomph/Sites/ecms_profile
   ```

2. **Verify the conversion scripts exist:**
   ```bash
   ls -la convert_profile_ecms_acquia_to_ecms_base.sql
   ls -la drush_profile_convert.php
   ```

### Step 2: Choose Your Conversion Method

You have two options for running the conversion:

## **🎯 RECOMMENDED: Option A - Drush Command (Safer)**

This method uses Drupal's database API and includes built-in safety features:

```bash
# Standard environment
drush scr drush_profile_convert.php

# OR using DDEV
ddev drush scr drush_profile_convert.php
```

**Advantages of the Drush method:**
- ✅ Uses Drupal's database API for safer operations
- ✅ Built-in transaction support with automatic rollback on errors
- ✅ Interactive confirmation prompts
- ✅ Integrated verification and error handling
- ✅ Automatically runs cache rebuild if desired
- ✅ Better integration with Drupal's systems

## **Option B - Direct SQL (Advanced Users)**

### Step 2B: Database Connection Information

Gather your database credentials:
- Database host
- Database name
- Username
- Password

### Step 3B: Execute the SQL Script

#### Using MySQL Command Line
```bash
mysql -h [hostname] -u [username] -p [database_name] < convert_profile_ecms_acquia_to_ecms_base.sql
```

#### Using DDEV (if using DDEV environment)
```bash
ddev mysql < convert_profile_ecms_acquia_to_ecms_base.sql
```

#### Manual MySQL Session
```bash
mysql -h [hostname] -u [username] -p [database_name]
# Then in MySQL prompt:
source convert_profile_ecms_acquia_to_ecms_base.sql;
```

### Step 3A: Post-Conversion Steps (Drush Method)

If you used the **Drush method**, the script will prompt you through the process:

1. **The script will automatically handle most post-conversion steps**
2. **Follow the prompts** for cache rebuilding
3. **Run remaining steps manually:**
   ```bash
   drush updatedb -v
   drush status
   drush config:status
   ```

### Step 4: Post-Conversion Steps (SQL Method)

If you used the **SQL script method**, run these steps manually:

1. **Clear Drupal caches:**
   ```bash
   drush cache:rebuild
   # OR
   ddev drush cache:rebuild
   ```

2. **Run database updates:**
   ```bash
   drush updatedb -v
   # OR
   ddev drush updatedb -v
   ```

3. **Verify the installation:**
   ```bash
   drush status
   # OR
   ddev drush status
   ```

4. **Check configuration status:**
   ```bash
   drush config:status
   # OR
   ddev drush config:status
   ```

## 📋 **Verification Steps**

### 1. Check Profile Status
```bash
drush status | grep "Install profile"
```
**Expected output:** `Install profile : ecms_base`

### 2. Verify No Configuration Issues
```bash
drush config:status
```
**Expected:** No pending configuration changes

### 3. Check for Errors
```bash
drush watchdog:show --severity=Error --count=10
```
**Expected:** No new profile-related errors

### 4. Test Site Functionality
- Access the admin interface: `/admin`
- Check that modules are loading correctly
- Verify no broken functionality

## 🔧 **Troubleshooting**

### Issue: "Profile not found" error
**Solution:**
```bash
# Clear all caches aggressively
drush cache:rebuild
drush php:eval "drupal_flush_all_caches();"
```

### Issue: Configuration import issues
**Solution:**
```bash
# Check if ecms_base profile has required dependencies
drush pm:list --type=profile
drush config:import --partial --source=ecms_base/config/install
```

### Issue: Module conflicts
**Solution:**
```bash
# List enabled modules and check for Acquia-specific modules
drush pm:list --status=enabled | grep acquia
# Disable any acquia-specific modules if needed
drush pm:uninstall [acquia_module_name]
```

## 🔄 **Rollback Procedure**

If the conversion fails or causes issues:

1. **Restore from backup:**
   ```bash
   mysql -u [username] -p [database_name] < backup_before_profile_conversion_[timestamp].sql
   ```

2. **Clear caches:**
   ```bash
   drush cache:rebuild
   ```

3. **Verify restoration:**
   ```bash
   drush status
   ```

## 📊 **What the Script Does**

The conversion script performs these operations:

1. **Updates ACSF Variables** - Changes client_name from ecms_acquia to ecms_base
2. **Updates Core Configuration** - Modifies core.extension configuration
3. **Updates System State** - Changes any cached profile references
4. **Clears Caches** - Removes cached data that might reference the old profile
5. **Provides Verification** - Shows what was changed and what remains

## 📝 **Important Notes**

- **No Drupal Hooks**: This bypasses Drupal's update system entirely
- **Direct Database Changes**: Modifies database tables directly
- **Cache Clearing**: Essential to force Drupal to recognize changes
- **One-Way Process**: Converting back requires another script or restore

## 🆘 **Support**

If you encounter issues:

1. **Check the script output** for any error messages
2. **Review database logs** for constraint violations
3. **Verify file permissions** on the ecms_base profile directory
4. **Check Drupal logs** for any profile-related errors: `drush watchdog:show`

## 🎯 **Success Criteria**

The conversion is successful when:

- ✅ `drush status` shows `Install profile : ecms_base`
- ✅ `drush config:status` shows no pending changes
- ✅ Admin interface loads without errors
- ✅ No profile-related error messages in logs
- ✅ Site functions normally