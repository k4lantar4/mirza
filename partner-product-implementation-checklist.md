# Partner Product Implementation Checklist

## Quick Reference for Implementation

### Database Setup

- [ ] Create `partner_product` table using SQL from analysis document (Section 1)
- [ ] (Optional) Add `category` field to `invoice` table for volume tracking

### Code Modifications Required

#### File: `/root/mirza/vpnbot/Default/index.php`

**11 Query Modification Points:**
- [ ] Line 594: Buy flow - single panel query
- [ ] Line 639: Category check count
- [ ] Line 697: Location selection query
- [ ] Line 717: Category check count
- [ ] Line 744: Category products query
- [ ] Line 852: Product lookup by code (buy confirmation)
- [ ] Line 942: Product lookup by code (payment processing)
- [ ] Line 1551: Extend flow query
- [ ] Line 1562: Extend products query
- [ ] Line 1651: Product lookup by code (extend confirmation)
- [ ] Line 1699: Product lookup by code (extend processing)

**Helper Function:**
- [ ] Create `getProductTableInfo()` helper function
- [ ] Use helper in all 11 modification points

**Invoice Creation:**
- [ ] Line 437: Add category to test account invoice creation
- [ ] Line 1011: Add category to purchase invoice creation

#### File: `/root/mirza/vpnbot/Default/keyboard.php`

- [ ] Modify `KeyboardProduct()` function to support partner products
- [ ] Modify `KeyboardCategory()` function to support partner products
- [ ] Update 4 function call sites (lines 635, 714, 756, 1563)
- [ ] Update 2 KeyboardCategory call sites (lines 644, 722)

#### File: `/root/mirza/vpnbot/Default/admin.php`

**Existing Functions to Modify:**
- [ ] Line 431: Product listing for price customization
- [ ] Line 454: Product selection for price customization
- [ ] Line 469: Product lookup for price save
- [ ] Line 638: Product listing for name customization
- [ ] Line 661: Product selection for name customization
- [ ] Line 671: Product lookup for name save

**New Functions to Add:**
- [ ] Create partner product function
- [ ] Edit partner product function
- [ ] Delete partner product function
- [ ] Partner product management menu (only for n2 agents)
- [ ] Category management for partners

### Testing Checklist

- [ ] n2 partner bot can create products
- [ ] n2 partner bot sees only their products
- [ ] n2 partner bot can purchase their products
- [ ] n2 partner bot can extend services with their products
- [ ] Products don't appear in other partner bots
- [ ] Non-n2 bots still work with existing product table
- [ ] Category filtering works for partner products
- [ ] Volume calculation queries work correctly
- [ ] Admin functions work for partner products

### Key Implementation Pattern

```php
// Detection pattern for all queries
if ($userbot['agent'] == 'n2') {
    $table = 'partner_product';
    $filter = "bot_token = '$ApiToken'";
} else {
    $table = 'product';
    $filter = "agent = '{$userbot['agent']}'";
}
```

### Critical Notes

1. **Code Product Uniqueness:** Scoped by bot_token, not globally unique
2. **Backward Compatibility:** Non-n2 agents must continue using `product` table
3. **Category Field:** Invoice table needs category field for volume tracking
4. **Product.json Files:** Same mechanism works, keys are unique per bot

### Files Modified Count

- **index.php:** 11 query points + 2 invoice creation points = 13 modifications
- **keyboard.php:** 2 functions + 6 call sites = 8 modifications
- **admin.php:** 6 existing functions + 5 new functions = 11 modifications
- **Total:** ~32 code modification points

---

See `partner-product-analysis.md` for detailed specifications of each modification.

