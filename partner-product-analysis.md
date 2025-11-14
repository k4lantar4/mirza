# Partner Product System - Complete Analysis

## Executive Summary

This document provides a complete analysis of the current system and identifies all modification points required to implement partner-specific product tables for n2 agents. Each partner bot will be able to create and manage their own custom products with categories, independent pricing, and volume-based calculations.

---

## 1. Database Schema Analysis

### Current Product Table Structure

```sql
CREATE TABLE product (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code_product varchar(200) NULL,
    name_product varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    price_product varchar(2000) NULL,
    Volume_constraint varchar(2000) NULL,
    Location varchar(200) NULL,
    Service_time varchar(200) NULL,
    agent varchar(100) NULL,  -- Currently: f/n/n2
    note TEXT NULL,
    data_limit_reset varchar(200) NULL,
    one_buy_status varchar(20) NOT NULL,
    inbounds TEXT NULL,
    proxies TEXT NULL,
    category varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    hide_panel TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci
```

### Proposed Partner Product Table Schema

```sql
CREATE TABLE partner_product (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_token VARCHAR(500) NOT NULL,  -- Links to botsaz.bot_token
    code_product varchar(200) NULL,
    name_product varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    price_product varchar(2000) NULL,
    Volume_constraint varchar(2000) NULL,
    Location varchar(200) NULL,
    Service_time varchar(200) NULL,
    note TEXT NULL,
    data_limit_reset varchar(200) NULL DEFAULT 'no_reset',
    one_buy_status varchar(20) NOT NULL DEFAULT '0',
    inbounds TEXT NULL,
    proxies TEXT NULL,
    category varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    hide_panel TEXT NOT NULL DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bot_token (bot_token),
    INDEX idx_code_product (code_product),
    INDEX idx_category (category),
    INDEX idx_location (Location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci
```

**Key Differences:**
- Added `bot_token` field to link products to specific partner bot
- Removed `agent` field (all partner products are implicitly for n2)
- Added `created_at` and `updated_at` for tracking
- Added indexes for performance on bot_token, code_product, category, and Location

---

## 2. Product Query Modification Points

### File: `/root/mirza/vpnbot/Default/index.php`

#### 2.1 Buy Flow - Single Panel (Line 594)
**Current:**
```php
$query = "SELECT * FROM product WHERE (Location = '{$locationproduct['name_panel']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
```

**Required Change:**
- Check if `$userbot['agent'] == 'n2'`
- If n2: Query `partner_product` table with `bot_token = '$ApiToken'`
- If not n2: Keep existing query to `product` table

#### 2.2 Product Count Check - Categories (Line 639)
**Current:**
```php
$nullproduct = select("product", "*", "agent", $userbot['agent'], "count");
```

**Required Change:**
- For n2: Count from `partner_product` WHERE `bot_token = '$ApiToken'`
- For others: Keep existing

#### 2.3 Location Selection - Buy Flow (Line 697)
**Current:**
```php
$query = "SELECT * FROM product WHERE (Location = '{$locationproduct['name_panel']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
```

**Required Change:**
- Same logic as 2.1 - check agent type and route to appropriate table

#### 2.4 Product Count Check - Location Selection (Line 717)
**Current:**
```php
$nullproduct = select("product", "*", "agent", $userbot['agent'], "count");
```

**Required Change:**
- Same logic as 2.2

#### 2.5 Category Products Display (Line 744)
**Current:**
```php
$query = "SELECT * FROM product WHERE (Location = '{$locationproduct['name_panel']}' OR Location = '/all') AND category = '$categorynames' AND agent= '{$userbot['agent']}' ";
```

**Required Change:**
- For n2: Query `partner_product` with `bot_token` and category filter
- For others: Keep existing

#### 2.6 Product Lookup by Code - Buy Confirmation (Line 852)
**Current:**
```php
$product = select("product", "*", "code_product", $code_product);
```

**Required Change:**
- For n2: Check `partner_product` WHERE `code_product = $code_product AND bot_token = '$ApiToken'`
- For others: Keep existing
- **Critical:** This must check both tables if n2, as code_product might exist in either

#### 2.7 Product Lookup by Code - Payment Processing (Line 942)
**Current:**
```php
$product = select("product", "*", "code_product", $product);
```

**Required Change:**
- Same as 2.6

#### 2.8 Extend Flow - Product Query (Line 1551)
**Current:**
```php
$query = "SELECT * FROM product WHERE (Location = '{$nameloc['Service_location']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
```

**Required Change:**
- Same logic as 2.1

#### 2.9 Extend Flow - Product List (Line 1562)
**Current:**
```php
$query = "SELECT * FROM product WHERE (Location = '{$marzban_list_get['name_panel']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
```

**Required Change:**
- Same logic as 2.1

#### 2.10 Product Lookup by Code - Extend Confirmation (Line 1651)
**Current:**
```php
$product = select("product", "*", "code_product", $product);
```

**Required Change:**
- Same as 2.6

#### 2.11 Product Lookup by Code - Extend Processing (Line 1699)
**Current:**
```php
$product = select("product", "*", "code_product", $product);
```

**Required Change:**
- Same as 2.6

### Summary of Query Modification Points

| Line | Context | Query Type | Priority |
|------|---------|------------|----------|
| 594 | Buy flow - single panel | SELECT with agent filter | High |
| 639 | Category check | COUNT with agent filter | High |
| 697 | Location selection | SELECT with agent filter | High |
| 717 | Category check | COUNT with agent filter | High |
| 744 | Category products | SELECT with category + agent | High |
| 852 | Product lookup | SELECT by code_product | Critical |
| 942 | Payment processing | SELECT by code_product | Critical |
| 1551 | Extend flow | SELECT with agent filter | High |
| 1562 | Extend products | SELECT with agent filter | High |
| 1651 | Extend confirmation | SELECT by code_product | Critical |
| 1699 | Extend processing | SELECT by code_product | Critical |

**Total: 11 modification points in index.php**

---

## 3. Keyboard Function Analysis

### File: `/root/mirza/vpnbot/Default/keyboard.php`

#### 3.1 KeyboardProduct() Function (Line 169)

**Current Signature:**
```php
function KeyboardProduct($location, $query, $pricediscount, $datakeyboard, $statuscustom = false, $backuser = "backuser", $valuetow = null, $customvolume = "customsellvolume")
```

**Current Behavior:**
- Receives a pre-built SQL query string
- Executes query and builds inline keyboard with products
- Handles product.json and product_name.json overrides
- Filters by hide_panel

**Required Modifications:**
1. **Add bot identification parameter:**
   - Add `$bot_token` and `$agent_type` parameters
   - Or detect from global `$ApiToken` and `$userbot['agent']`

2. **Query modification:**
   - If agent is n2: Modify query to use `partner_product` table
   - Replace `agent = '{$userbot['agent']}'` with `bot_token = '$ApiToken'`
   - Keep Location and category filters

3. **Function calls to update:**
   - Line 635: `KeyboardProduct($marzban_list_get['name_panel'], $query, 0, $keyboarddata, $statuscustom, "backuser", null, $customvolume = "customvolumebuy")`
   - Line 714: `KeyboardProduct($locationproduct['name_panel'], $query, 0, $keyboarddata, $statuscustom, "backuser", null, $customvolume = "customvolumebuy")`
   - Line 756: `KeyboardProduct($locationproduct['name_panel'], $query, 0, $keyboarddata, $statuscustom, "backuser", null, $customvolume = "customvolumebuy")`
   - Line 1563: `KeyboardProduct($marzban_list_get['name_panel'], $query, 0, "selectproductextends_", $statuscustom, "backuser", null, $customvolume = "customvolumeextend")`

**Recommended Approach:**
- Modify function to accept optional `$table_name` parameter (default: 'product')
- Or detect table based on global variables
- Keep backward compatibility for non-n2 agents

#### 3.2 KeyboardCategory() Function (Line 200)

**Current Signature:**
```php
function KeyboardCategory($location, $agent, $backuser = "backuser")
```

**Current Behavior:**
- Queries category table
- For each category, checks if products exist with that category and agent
- Builds inline keyboard with categories that have products

**Current Query (Line 207):**
```php
$stmts = $pdo->prepare("SELECT * FROM product WHERE (Location = :location OR Location = '/all') AND category = :category AND agent = :agent");
```

**Required Modifications:**
1. **Add bot identification:**
   - Add `$bot_token` parameter or detect from global
   - Modify query to check `partner_product` for n2 agents

2. **Query modification:**
   - For n2: Query `partner_product` WHERE `bot_token = :bot_token AND category = :category`
   - For others: Keep existing query

3. **Function calls to update:**
   - Line 644: `KeyboardCategory($marzban_list_get['name_panel'], $userbot['agent'], "backuser")`
   - Line 722: `KeyboardCategory($locationproduct['name_panel'], $userbot['agent'], "backuser")`

**Recommended Approach:**
- Add `$bot_token` parameter (optional, default null)
- If `$agent == 'n2'` and `$bot_token` provided, use `partner_product` table
- Otherwise use existing `product` table

---

## 4. Admin Product Management Analysis

### File: `/root/mirza/vpnbot/Default/admin.php`

#### 4.1 Product Listing for Admin (Lines 431, 638)

**Current Code (Line 431):**
```php
$getdataproduct = mysqli_query($connect, "SELECT * FROM product WHERE agent = '{$userbot['agent']}'");
```

**Current Behavior:**
- Lists all products for the agent type
- Used for price customization and name customization
- Products are filtered by agent type only

**Required Modifications:**
1. **For n2 agents:**
   - Query `partner_product` WHERE `bot_token = '$ApiToken'`
   - Scope products to current partner bot only

2. **For other agents:**
   - Keep existing behavior

#### 4.2 Product Price Customization (Lines 453-482)

**Current Flow:**
1. Admin selects product from list
2. System loads product by name_product
3. Admin sets custom price
4. Price saved to `product.json` file with `code_product` as key

**Required Modifications:**
1. **Product selection:**
   - For n2: Load from `partner_product` table
   - Ensure product belongs to current bot (`bot_token` check)

2. **Price storage:**
   - Current: `product.json` in bot directory
   - For n2: Could use same file (scoped by bot_token) or separate file
   - **Recommendation:** Use same `product.json` structure, keys are unique per bot

#### 4.3 Product Name Customization (Lines 633-676)

**Current Flow:**
1. Admin selects product from list
2. System loads product by name_product
3. Admin sets custom name
4. Name saved to `product_name.json` file

**Required Modifications:**
- Same as 4.2 - ensure product belongs to current bot

#### 4.4 Missing Admin Functions

**Currently Missing (Need to Add):**
1. **Create Partner Product:**
   - Form to create new product
   - Fields: name, price, volume, time, location, category
   - Save to `partner_product` table with `bot_token`
   - Generate `code_product` using `bin2hex(random_bytes(2))` or `bin2hex(random_bytes(3))`

2. **Edit Partner Product:**
   - Load product by ID/code
   - Verify `bot_token` matches
   - Update product fields
   - Similar flow to price/name customization but for all fields

3. **Delete Partner Product:**
   - Load product by ID/code
   - Verify `bot_token` matches
   - Delete from `partner_product` table
   - Check if product is in use (has active invoices) before deletion

4. **Category Management:**
   - Create/edit categories for partner products
   - Categories should be bot-specific or shared? (Need clarification)
   - Current category table appears to be shared (no bot_token field)
   - **Recommendation:** Use shared categories, but filter products by bot_token when displaying

#### 4.5 Admin Menu Integration

**Current Admin Menu Structure:**
- "💰 تنظیمات فروشگاه" → Shows `$keyboardprice`
- `$keyboardprice` contains:
  - "🔋 قیمت حجم"
  - "⌛️ قیمت زمان"
  - "💰 تنظیم قیمت محصول"
  - "✏️ تنظیم نام محصول"

**Required Additions for n2 Partners:**
- Add new menu items for n2 agents:
  - "➕ ایجاد محصول جدید" (Create new product)
  - "✏️ ویرایش محصول" (Edit product)
  - "❌ حذف محصول" (Delete product)
  - "📁 مدیریت دسته‌بندی" (Category management)

**Implementation:**
- Check `$userbot['agent'] == 'n2'` before showing partner-specific menu items
- Add new keyboard for partner product management
- Integrate with existing admin flow

---

## 5. Integration Points - Bot Identification

### Key Variables Available Globally

1. **`$ApiToken`** (from `config.php`)
   - Unique identifier for each bot instance
   - Used to link partner products to specific bot
   - **Usage:** `bot_token = '$ApiToken'` in partner_product queries

2. **`$userbot`** (from `botsaz.id_user`)
   - Bot owner user record
   - Contains `agent` field (f/n/n2)
   - **Usage:** Determine if bot is partner (n2) type

3. **`$userbot['agent']`**
   - Agent type: 'f' (normal), 'n' (agent), 'n2' (partner)
   - **Usage:** Route queries to correct table (product vs partner_product)

4. **`$userbot['id']`**
   - Bot owner user ID
   - Alternative identifier (but `$ApiToken` is preferred for bot-specific data)

### Detection Logic Pattern

**Recommended pattern for all query modifications:**
```php
if ($userbot['agent'] == 'n2') {
    // Use partner_product table
    $table_name = 'partner_product';
    $agent_filter = "bot_token = '$ApiToken'";
} else {
    // Use product table
    $table_name = 'product';
    $agent_filter = "agent = '{$userbot['agent']}'";
}
```

### Global Availability

- `$ApiToken`: Available in all files (loaded in index.php line 11)
- `$userbot`: Available after line 111 in index.php
- Both available in: `index.php`, `keyboard.php`, `admin.php`, `func.php`

---

## 6. Volume-Based Pricing Analysis

### Current Invoice Structure

**Invoice Table Fields (from line 1011):**
```php
INSERT INTO invoice (
    id_user, id_invoice, username, time_sell,
    Service_location, name_product, price_product,
    Volume, Service_time, Status, bottype, note, notifctions
)
```

**Key Fields for Volume Calculation:**
- `Volume` - Volume purchased (in GB)
- `price_product` - Price paid
- `bottype` - Links to bot (can identify partner bot)
- `time_sell` - Purchase timestamp
- `Status` - Invoice status (active, end_of_time, etc.)

### Volume Calculation Requirements

**User Requirement:**
> "بتونن بر اساس دسته بندی ما مثل استاندارد و پرمیوم، بتونن با تصویه بصورت محاسبه مقدار حجمی که با پلن ها فروختن، پلن های حجمی، زمانی، با دسته بندی و قیمت مجزا و مختص خودشون رو داشته باشند."

**Translation:** Partners should be able to have volume-based, time-based plans with categories (standard/premium), with separate prices, calculated based on the volume sold with plans.

### Implementation Strategy

#### Option 1: Track in Invoice Table (Recommended)
- Use existing `invoice` table
- Query: `SELECT SUM(Volume) FROM invoice WHERE bottype = '$ApiToken' AND Status IN ('active', ...) AND category = 'premium'`
- Calculate total volume sold per category
- Use this to determine pricing tiers

#### Option 2: Separate Volume Tracking Table
```sql
CREATE TABLE partner_volume_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_token VARCHAR(500) NOT NULL,
    category VARCHAR(400) NULL,
    total_volume_sold INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bot_category (bot_token, category)
)
```

**Recommendation:** Use Option 1 (invoice table) as it's simpler and data already exists.

### Volume Calculation Queries Needed

1. **Total Volume by Category:**
```sql
SELECT
    category,
    SUM(Volume) as total_volume
FROM invoice
WHERE bottype = :bot_token
    AND Status IN ('active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold')
    AND category IS NOT NULL
GROUP BY category
```

2. **Total Volume All Categories:**
```sql
SELECT SUM(Volume) as total_volume
FROM invoice
WHERE bottype = :bot_token
    AND Status IN ('active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold')
```

**Note:** Current invoice table doesn't have `category` field. Need to check if this needs to be added or if category is stored elsewhere.

### Missing Invoice Category Field

**Current invoice structure (from table.php lines 468-485):**
```sql
CREATE TABLE invoice (
    id_invoice varchar(200) PRIMARY KEY,
    id_user varchar(200) NULL,
    username varchar(300) NULL,
    Service_location varchar(300) NULL,
    time_sell VARCHAR(200) NULL,
    name_product varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    price_product varchar(200) NULL,
    Volume varchar(200) NULL,
    Service_time varchar(200) NULL,
    uuid TEXT NULL,
    note varchar(500) NULL,
    user_info TEXT NULL,
    bottype varchar(200) NULL,
    refral varchar(100) NULL,
    time_cron varchar(100) NULL,
    notifctions TEXT NOT NULL,
    Status varchar(200) NULL
)
```

**Category Storage Options:**

1. **Add category field to invoice (Recommended):**
```sql
ALTER TABLE invoice ADD COLUMN category VARCHAR(400) NULL AFTER name_product;
```
- Direct storage, easy to query
- Requires updating invoice creation code (lines 437, 1011 in index.php)

2. **Link via product table:**
- When creating invoice, look up product by `code_product`
- Get category from product table
- More complex queries, but no schema change

3. **Store in note field:**
- Not recommended - note is for user notes, not structured data

**Recommended Approach:** Option 1 - Add category field. When creating invoice, copy category from product to invoice.

**Invoice Creation Points to Update:**
- Line 437: Test account creation
- Line 1011: Regular purchase creation
- Need to add: `category` field from product when inserting invoice

---

## 7. Code Product Uniqueness

### Current System
- `code_product` is generated randomly: `bin2hex(random_bytes(2))` or `bin2hex(random_bytes(3))`
- Stored in `product` table
- Used as unique identifier for product selection

### Partner Product Considerations

**Issue:** `code_product` might collide between:
- Main `product` table
- Different partner `partner_product` tables
- Same partner's multiple products

**Solutions:**

#### Option 1: Scope by bot_token (Recommended)
- `code_product` only needs to be unique within a bot
- Query: `SELECT * FROM partner_product WHERE code_product = :code AND bot_token = :bot_token`
- Simpler, no global uniqueness needed

#### Option 2: Global Uniqueness
- Use longer codes or UUIDs
- More complex, but allows cross-bot product sharing (if needed)

**Recommendation:** Option 1 - scope by bot_token. Each partner manages their own product codes independently.

---

## 8. Backward Compatibility

### Current n2 Behavior
- n2 agents currently see products from `product` table where `agent = 'n2'`
- These are shared across all n2 partner bots

### Migration Strategy

**Option 1: Complete Replacement**
- n2 agents only see `partner_product` table
- Existing `product` entries with `agent='n2'` become invisible to partners
- Partners must create their own products

**Option 2: Hybrid Approach**
- n2 agents see both:
  - Their own `partner_product` entries
  - Shared `product` entries where `agent='n2'`
- Requires UNION queries or separate product lists

**Recommendation:** Option 1 (Complete Replacement) - Cleaner separation, partners have full control.

---

## 9. File Modification Summary

### Files Requiring Changes

1. **`/root/mirza/vpnbot/Default/index.php`**
   - 11 query modification points (see Section 2)
   - Add helper function to determine table and filter
   - Modify product lookup logic

2. **`/root/mirza/vpnbot/Default/keyboard.php`**
   - Modify `KeyboardProduct()` function
   - Modify `KeyboardCategory()` function
   - Add bot_token parameter support

3. **`/root/mirza/vpnbot/Default/admin.php`**
   - Modify product listing queries (lines 431, 638)
   - Add create/edit/delete partner product functions
   - Add category management for partners

4. **Database**
   - Create `partner_product` table (SQL provided in Section 1)
   - Optionally: Add `category` field to `invoice` table

### Helper Function Recommendation

Create a helper function in `index.php` or `func.php`:

```php
function getProductTableInfo($userbot, $ApiToken) {
    if ($userbot['agent'] == 'n2') {
        return [
            'table' => 'partner_product',
            'filter' => "bot_token = '$ApiToken'",
            'filter_param' => [':bot_token' => $ApiToken]
        ];
    } else {
        return [
            'table' => 'product',
            'filter' => "agent = '{$userbot['agent']}'",
            'filter_param' => [':agent' => $userbot['agent']]
        ];
    }
}
```

---

## 10. Testing Considerations

### Test Scenarios

1. **n2 Partner Bot:**
   - Create partner product
   - View product list (should only show their products)
   - Purchase product
   - Extend service with partner product
   - Verify products don't appear in other partner bots

2. **Non-n2 Bot:**
   - Verify existing product queries still work
   - Verify no access to partner_product table

3. **Category Functionality:**
   - Create products with categories
   - Filter by category
   - Verify category keyboard shows only relevant categories

4. **Volume Calculation:**
   - Track volume sold per category
   - Verify calculation queries work correctly

---

## 11. Implementation Priority

### Phase 1: Core Functionality (Critical)
1. Create `partner_product` table
2. Modify product queries in index.php (11 points)
3. Update KeyboardProduct() and KeyboardCategory()
4. Test buy flow for n2 agents

### Phase 2: Admin Functions (High)
1. Add product listing for n2 in admin.php
2. Add create partner product function
3. Add edit partner product function
4. Add delete partner product function

### Phase 3: Advanced Features (Medium)
1. Add category field to invoice table
2. Implement volume calculation queries
3. Add volume-based pricing logic
4. Category management UI

---

## 12. Open Questions

1. **Category Sharing:**
   - Should categories be shared across all partner bots?
   - Or each partner has their own categories?
   - **Recommendation:** Shared categories (easier management)

2. **Product Migration:**
   - Should existing `product` entries with `agent='n2'` be migrated?
   - Or partners start fresh?
   - **User said:** "افزودن جدول دیتابیس هم با یک دستور ساده sql انجام میدیم و نیاز به مهاجرت و پیچیدگی نیست" - No migration needed

3. **Volume Calculation Frequency:**
   - Real-time calculation?
   - Cached/periodic calculation?
   - **Recommendation:** Real-time for accuracy, with optional caching

4. **Price Override Files:**
   - Should `product.json` and `product_name.json` work for partner products?
   - **Recommendation:** Yes, same mechanism, scoped by bot_token

---

## Conclusion

This analysis identifies **11 critical query modification points** in `index.php`, **2 keyboard functions** requiring updates, and **admin functions** that need to be added or modified. The system requires a new `partner_product` table with `bot_token` as the linking field, and all product queries must be routed based on the `$userbot['agent']` value.

The implementation is straightforward but requires careful attention to ensure backward compatibility for non-n2 agents and proper isolation between partner bots.

