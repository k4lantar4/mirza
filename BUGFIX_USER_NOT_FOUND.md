# Bug Fix Report: "Bad Request: user not found" Error

## Problem Summary

The bot was experiencing severe performance degradation due to excessive error logging. Errors were occurring every minute at :01 and :02 past the hour, flooding the error logs with:

```
{"ok":false,"error_code":400,"description":"Bad Request: user not found"}
```

## Root Causes Identified

### 1. **Hardcoded verifyUser Call (CRITICAL)**
**Location:** `botapi.php` lines 25-28

**Issue:** A hardcoded `telegram('verifyUser', ...)` call was executing every time `botapi.php` was included. Since ALL cron jobs require this file, this call was running:
- Every minute (for cron jobs that run `*/1 * * * *`)
- Multiple times per minute (when multiple cron jobs execute simultaneously)
- The `verifyUser` method is not a standard Telegram Bot API method and was failing

**Impact:**
- Generated 2+ error logs per minute
- Caused unnecessary API calls
- Slowed down all cron job executions

### 2. **No Error Filtering**
**Location:** `botapi.php` line 17

**Issue:** The `telegram()` function was logging ALL errors, including expected ones like:
- "user not found" (when users delete accounts or block bot)
- "bot was blocked by the user" (normal user behavior)
- "chat not found" (deleted chats)

**Impact:**
- Log files filled with expected errors
- Made it difficult to identify real issues
- Performance degradation from excessive logging

### 3. **Missing Resource Cleanup**
**Location:** `botapi.php` function `telegram()`

**Issue:** cURL handles were not being closed properly, causing resource leaks.

**Impact:**
- Memory leaks over time
- Potential connection pool exhaustion

### 4. **Insufficient Validation**
**Location:** `sendmessage()` function and `api/users.php`

**Issue:** Functions didn't validate user IDs before making API calls, and didn't handle expected errors gracefully.

## Solutions Implemented

### Fix 1: Removed Hardcoded verifyUser Call ✅
**File:** `botapi.php`

**Change:** Completely removed lines 25-28 that contained the hardcoded `verifyUser` call.

**Result:** Eliminates the primary source of errors.

### Fix 2: Added Error Filtering ✅
**File:** `botapi.php` function `telegram()`

**Changes:**
- Added list of expected errors that shouldn't be logged
- Only logs unexpected errors
- Improved error handling structure

**Expected errors filtered:**
- "Bad Request: user not found"
- "Bad Request: chat not found"
- "Forbidden: bot was blocked by the user"
- "Forbidden: user is deactivated"
- "Bad Request: message to delete not found"
- "Bad Request: message to edit not found"

**Result:** Log files will only contain unexpected errors, making debugging easier.

### Fix 3: Improved Resource Management ✅
**File:** `botapi.php` function `telegram()`

**Changes:**
- Added `curl_close($ch)` to properly close cURL handles
- Improved error handling flow
- Better separation of cURL errors vs API errors

**Result:** Prevents memory leaks and resource exhaustion.

### Fix 4: Enhanced sendmessage Validation ✅
**File:** `botapi.php` function `sendmessage()`

**Changes:**
- Added validation for chat_id before API call
- Returns proper error structure for invalid IDs
- Better error handling

**Result:** Prevents unnecessary API calls with invalid data.

### Fix 5: Improved API Error Handling ✅
**File:** `api/users.php` function `user_add`

**Changes:**
- Added conditional error logging (only logs unexpected errors)
- Better error message handling

**Result:** Reduces log noise from expected API errors.

## Cron Jobs Affected

The following cron jobs were affected (all run every minute):
- `cronbot/croncard.php` - Card payment verification
- `cronbot/NoticationsService.php` - Service notifications
- `cronbot/sendmessage.php` - Bulk messaging
- `cronbot/activeconfig.php` - Activate configs
- `cronbot/disableconfig.php` - Disable configs
- `cronbot/iranpay1.php` - Payment processing

All of these include `botapi.php`, so they were all triggering the hardcoded `verifyUser` call.

## Testing Recommendations

1. **Monitor error logs** for 24-48 hours after deployment
   - Should see dramatic reduction in "user not found" errors
   - Only unexpected errors should appear

2. **Check cron job performance**
   - Cron jobs should execute faster
   - No more duplicate error logs at :01 and :02 past each hour

3. **Verify bot functionality**
   - Test sending messages to valid users
   - Test sending messages to blocked users (should fail silently)
   - Test sending messages to deleted users (should fail silently)

4. **Monitor resource usage**
   - Check for memory leaks (should be reduced)
   - Monitor cURL connection pool

## Expected Results

- ✅ **90%+ reduction in error log entries**
- ✅ **Faster cron job execution** (no unnecessary API calls)
- ✅ **Cleaner logs** (only unexpected errors logged)
- ✅ **Better performance** (no resource leaks)
- ✅ **Improved debugging** (real issues are easier to spot)

## Additional Notes

- The `verifyUser` method appears to be a custom/experimental Telegram API method
- If verification functionality is needed, it should be:
  - Called explicitly when needed (not on every file include)
  - Wrapped in proper error handling
  - Only executed for valid user IDs

## Files Modified

1. `botapi.php` - Main fixes (removed verifyUser, added error filtering, improved resource management)
2. `api/users.php` - Improved error handling for getChat calls

## Date Fixed

November 14, 2025

