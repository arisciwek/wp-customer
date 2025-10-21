# TODO-2167: Branch Generator Runtime Sync

## Status
✅ **COMPLETED** - 2025-01-21

## Deskripsi

Revisi Generate Branch agar sinkron dengan runtime flow menggunakan Controller/Model/Validator, bukan direct database INSERT.

### Perubahan Utama

1. **Generate ONLY cabang branches** - Pusat branches sekarang auto-created via `wp_customer_created` HOOK
2. **Use BranchController::createDemoBranch()** - Semua branch creation via runtime controller
3. **Runtime location assignment** - Gunakan `BranchModel::getAgencyAndDivisionIds()` dan `BranchModel::getInspectorId()`
4. **Generate extra branches** - 20 branches dengan `inspector_id=NULL` untuk testing assign inspector feature
5. **Preserve HOOK-created branches** - Cleanup hanya hapus `type='cabang'`, preserve pusat branches

## File Changes

### 1. BranchDemoData.php

**Path**: `/wp-customer/src/Database/Demo/BranchDemoData.php`

#### 1.1 cleanup() - Lines 241-248

**Before**:
```php
// Delete ALL branches
$this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_branches WHERE id > 0");
```

**After**:
```php
// Delete only cabang branches (pusat branches are auto-created via HOOK)
$this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_branches WHERE type = 'cabang'");

$this->debug("Cleared existing cabang branch data (pusat branches preserved from HOOK)");
```

**Alasan**: Preserve pusat branches yang dibuat oleh AutoEntityCreator via HOOK.

---

#### 1.2 generate() - Lines 275-291

**Before**:
```php
// Generate pusat branch
$this->generatePusatBranch($customer, $branch_user_id);
```

**After**:
```php
// Skip pusat branch generation - now auto-created via wp_customer_created HOOK
// Pusat branch is created by AutoEntityCreator when customer is created
$this->debug("Pusat branch for customer {$customer_id} should be auto-created via HOOK");

// Check for existing cabang branches
$existing_cabang_count = $this->wpdb->get_var($this->wpdb->prepare(
    "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_customer_branches
     WHERE customer_id = %d AND type = 'cabang'",
    $customer_id
));

if ($existing_cabang_count > 0) {
    $this->debug("Cabang branches exist for customer {$customer_id}, skipping...");
} else {
    $this->generateCabangBranches($customer);
    $generated_count++;
}
```

**Alasan**: Pusat branch creation sekarang handled by HOOK system, tidak perlu manual generation.

---

#### 1.3 generateCabangBranches() - Lines 422-511

**Before**:
```php
// Custom branch code generation
$branch_code = $customer->code . ' ' . $cabang_num . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

// Direct database INSERT
$result = $this->wpdb->insert(
    $this->wpdb->prefix . 'app_customer_branches',
    [
        'customer_id' => $customer->id,
        'code' => $branch_code,
        'name' => sprintf('%s Cabang %s', $customer->name, $regency_name),
        // ...
        'agency_id' => $this->generateAgencyID($customer->provinsi_id),
        'division_id' => $this->generateDivisionID($customer->regency_id),
        'inspector_id' => $this->generateInspectorID($customer->provinsi_id),
    ],
    ['%d', '%s', '%s', ...]
);
```

**After**:
```php
// Use runtime methods from BranchModel (consistent with production)
$location_ids = $this->branchModel->getAgencyAndDivisionIds($provinsi_id, $regency_id);
$inspector_id = $this->branchModel->getInspectorId($provinsi_id, $location_ids['division_id']);

$this->debug("Generated for cabang branch - agency_id: {$location_ids['agency_id']}, division_id: {$location_ids['division_id']}, inspector_id: {$inspector_id} for provinsi_id: {$provinsi_id}, regency_id: {$regency_id}");

// Prepare branch data (BranchModel::create will auto-generate code)
$branch_data = [
    'customer_id' => $customer->id,
    'name' => sprintf('%s Cabang %s', $customer->name, $regency_name),
    'type' => 'cabang',
    // ... other fields ...
    'provinsi_id' => $provinsi_id,
    'agency_id' => $location_ids['agency_id'],
    'regency_id' => $regency_id,
    'division_id' => $location_ids['division_id'],
    'user_id' => $wp_user_id,
    'inspector_id' => $inspector_id,
    'created_by' => $customer->user_id,
    'status' => 'active'
];

// Use BranchController for consistent runtime behavior
$branchController = new \WPCustomer\Controllers\Branch\BranchController();
$branch_id = $branchController->createDemoBranch($branch_data);

if (!$branch_id) {
    throw new \Exception("Failed to create cabang branch for customer: {$customer->id}");
}

$this->branch_ids[] = $branch_id;
$this->debug("Created cabang branch (ID: {$branch_id}) for customer {$customer->name} in {$regency_name}");
```

**Perubahan**:
1. ❌ Removed: `generateAgencyID()`, `generateDivisionID()`, `generateInspectorID()`
2. ✅ Added: `BranchModel::getAgencyAndDivisionIds()` (runtime method with fallback)
3. ✅ Added: `BranchModel::getInspectorId()` (runtime method with division-based lookup)
4. ❌ Removed: Manual `branch_code` generation
5. ❌ Removed: Direct `wpdb->insert()`
6. ✅ Added: `BranchController::createDemoBranch()` (runtime controller with cache invalidation)

**Alasan**:
- Gunakan runtime flow yang sama dengan production code
- BranchModel::create() sudah auto-generate code
- Cache invalidation handled by BranchController::createDemoBranch()
- Location assignment dengan fallback logic (provinsi → agency → division)

---

#### 1.4 generateExtraBranches() - Lines 616-658

**Before**:
```php
// Custom branch code generation
$branch_code = $customer->code . ' ' . $cabang_num . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

// Direct database INSERT
$result = $this->wpdb->insert(
    $this->wpdb->prefix . 'app_customer_branches',
    [
        'customer_id' => $customer->id,
        'code' => $branch_code,
        'name' => sprintf('%s Cabang %s', $customer->name, $regency_name),
        // ...
    ],
    ['%d', '%s', '%s', ...]
);
```

**After**:
```php
$regency_name = $this->getRegencyName($regency_id);
$location = $this->generateValidLocation();

// Explicitly set inspector_id to NULL for testing assign inspector feature
$inspector_id = null;

$this->debug("Generated extra branch - agency_id: {$agency_id}, division_id: {$division_id}, inspector_id: NULL for provinsi_id: {$provinsi_id}, regency_id: {$regency_id}");

// Prepare branch data (BranchModel::create will auto-generate code)
$branch_data = [
    'customer_id' => $customer->id,
    'name' => sprintf('%s Cabang %s', $customer->name, $regency_name),
    'type' => 'cabang',
    // ... other fields ...
    'inspector_id' => $inspector_id,  // NULL for testing assign inspector
    'created_by' => $customer->user_id,
    'status' => 'active'
];

// Use BranchController for consistent runtime behavior
$branchController = new \WPCustomer\Controllers\Branch\BranchController();
$branch_id = $branchController->createDemoBranch($branch_data);

if (!$branch_id) {
    $this->debug("Failed to create extra branch for customer {$customer->id}");
    continue;
}

$this->branch_ids[] = $branch_id;
$generated_extra++;

$this->debug("Created extra branch (ID: {$branch_id}) for customer {$customer->name} (inspector_id = NULL)");
```

**Perubahan**:
1. ✅ Set `inspector_id = NULL` explicitly (for testing assign inspector)
2. ❌ Removed: Manual `branch_code` generation
3. ❌ Removed: Direct `wpdb->insert()`
4. ✅ Added: `BranchController::createDemoBranch()` (runtime controller)

**Alasan**: Extra branches sengaja tanpa inspector untuk testing assign inspector feature di menu New Company.

---

## Runtime Flow

### 1. Customer Creation Flow

```
CustomerDemoData::generate()
  → CustomerModel::create()
    → fire HOOK: wp_customer_created
      → AutoEntityCreator::handleCustomerCreated()
        → BranchModel::getAgencyAndDivisionIds(provinsi_id, regency_id)
        → BranchModel::getInspectorId(provinsi_id, division_id)
        → BranchModel::create() → Create pusat branch
          → fire HOOK: wp_customer_branch_created
            → AutoEntityCreator::handleBranchCreated()
              → CustomerEmployeeModel::create() → Create employee
```

### 2. Cabang Branch Creation Flow (via Generator)

```
BranchDemoData::generateCabangBranches()
  → BranchModel::getAgencyAndDivisionIds(provinsi_id, regency_id)
    → Return: ['agency_id' => X, 'division_id' => Y]
    → Fallback: If regency not in jurisdiction, use provinsi → agency → random division
  → BranchModel::getInspectorId(provinsi_id, division_id)
    → Query: agency_employees WHERE division_id AND role=pengawas
    → Fallback: If no inspector in division, query by provinsi → agency
  → BranchController::createDemoBranch(branch_data)
    → BranchModel::create(branch_data)
      → Auto-generate branch code
      → INSERT into wp_app_customer_branches
      → fire HOOK: wp_customer_branch_created
        → AutoEntityCreator::handleBranchCreated()
          → Create employee for branch
    → Cache invalidation:
      → Delete: branch, branch_total_count, customer_branch, customer_branch_list
      → Invalidate: DataTable cache, Customer cache
```

### 3. Extra Branch Creation Flow (inspector_id = NULL)

```
BranchDemoData::generateExtraBranches()
  → Set: inspector_id = NULL (explicitly for testing)
  → BranchController::createDemoBranch(branch_data)
    → Same flow as cabang branch
    → Result: Branch WITHOUT inspector (for manual assignment testing)
```

## Location Assignment Logic

### BranchModel::getAgencyAndDivisionIds($provinsi_id, $regency_id)

**Path**: `src/Models/Branch/BranchModel.php:803-864`

**Flow**:
```php
1. Get province code from provinsi_id
   SELECT code FROM wp_wi_provinces WHERE id = $provinsi_id

2. Get agency_id from province code
   SELECT id FROM wp_app_agencies WHERE provinsi_code = $province_code LIMIT 1

3. If agency_id found:
   a. Try to get division from jurisdiction (regency-based)
      SELECT division_id FROM wp_app_agency_jurisdictions WHERE jurisdiction_code = $regency_code LIMIT 1

   b. If no division found, get random division from agency
      SELECT id FROM wp_app_agency_divisions WHERE agency_id = $agency_id ORDER BY RAND() LIMIT 1

4. Return: ['agency_id' => X, 'division_id' => Y]
```

**Fallback Strategy**:
- Regency tidak punya jurisdiction → Ambil random division dari agency
- Agency tidak punya division → Return NULL untuk division_id
- Province tidak punya agency → Return NULL untuk agency_id dan division_id

---

### BranchModel::getInspectorId($provinsi_id, $division_id = null)

**Path**: `src/Models/Branch/BranchModel.php:866-956`

**Flow**:
```php
1. If division_id provided:
   a. Try to get inspector from division
      SELECT ae.user_id FROM wp_app_agency_employees ae
      WHERE ae.division_id = $division_id
      AND ae.status = 'active'
      AND um.meta_value LIKE '%"agency_pengawas"%' OR um.meta_value LIKE '%"agency_pengawas_spesialis"%'
      ORDER BY RAND() LIMIT 1

   b. If found, return user_id

2. Fallback to province-based lookup:
   a. Get province code
      SELECT code FROM wp_wi_provinces WHERE id = $provinsi_id

   b. Get agency_id from province code
      SELECT id FROM wp_app_agencies WHERE provinsi_code = $province_code LIMIT 1

   c. Get inspector from agency
      SELECT ae.user_id FROM wp_app_agency_employees ae
      WHERE ae.agency_id = $agency_id
      AND ae.status = 'active'
      AND um.meta_value LIKE '%"agency_pengawas"%' OR um.meta_value LIKE '%"agency_pengawas_spesialis"%'
      ORDER BY RAND() LIMIT 1

3. Return: user_id OR NULL
```

**Fallback Strategy**:
- Division tidak punya pengawas → Fallback ke agency-level lookup
- Agency tidak punya pengawas → Return NULL
- Province tidak punya agency → Return NULL

---

## Cache Invalidation

### BranchController::createDemoBranch()

**Path**: `src/Controllers/Branch/BranchController.php:742-774`

**Cache Keys Invalidated**:
```php
1. Branch cache:
   - delete('branch', $branch_id)
   - delete('branch_total_count', $user_id)

2. Customer-Branch relationship cache:
   - delete('customer_branch', $customer_id)
   - delete('customer_branch_list', $customer_id)

3. DataTable cache:
   - invalidateDataTableCache('branch_list', ['customer_id' => $customer_id])

4. Customer cache:
   - invalidateCustomerCache($customer_id)
```

**Alasan**: Semua cache yang terkait dengan branch dan customer harus di-invalidate agar Company menu dan statistik customer menampilkan data terbaru.

---

## Test Results

### Generate Statistics

```bash
wp eval '$generator = new WPCustomer\Database\Demo\BranchDemoData(); $generator->run(); echo "✓ Branch generation completed";'
```

**Output**:
```
Starting branch data generation process...
==================================================
Generating ONLY cabang branches (pusat branches created via HOOK)
==================================================
Generating extra branches with inspector_id=NULL for testing assign inspector...
Successfully generated 20 extra branches (inspector_id=NULL)
==================================================
Branch generation completed successfully
Total branches created: 40
Total extra branches (inspector_id=NULL): 20
==================================================
✓ Branch generation completed
```

### Verification Queries

#### 1. Branch Count by Type

```sql
SELECT type, COUNT(*) as total FROM wp_app_customer_branches GROUP BY type;
```

**Result**:
```
type    | total
--------|------
cabang  | 40
pusat   | 10
```

✅ **Expected**: 10 pusat (from HOOK), 40 cabang (20 regular + 20 extra)

---

#### 2. Inspector Assignment Distribution

```sql
SELECT
    type,
    COUNT(*) as total,
    SUM(CASE WHEN inspector_id IS NOT NULL THEN 1 ELSE 0 END) as with_inspector,
    SUM(CASE WHEN inspector_id IS NULL THEN 1 ELSE 0 END) as without_inspector
FROM wp_app_customer_branches
GROUP BY type;
```

**Result**:
```
type   | total | with_inspector | without_inspector
-------|-------|----------------|------------------
cabang | 40    | 20             | 20
pusat  | 10    | 10             | 0
```

✅ **Expected**:
- Pusat: All have inspector (created via HOOK)
- Cabang: 20 with inspector, 20 without (for testing)

---

#### 3. Agency and Division Assignment

```sql
SELECT
    COUNT(*) as total_branches,
    SUM(CASE WHEN agency_id IS NOT NULL THEN 1 ELSE 0 END) as with_agency,
    SUM(CASE WHEN division_id IS NOT NULL THEN 1 ELSE 0 END) as with_division
FROM wp_app_customer_branches;
```

**Result**:
```
total_branches | with_agency | with_division
---------------|-------------|---------------
50             | 50          | 50
```

✅ **Expected**: All branches have agency_id and division_id filled (via runtime methods with fallback)

---

#### 4. Company Menu Query Test

```sql
SELECT
    b.id,
    c.name as perusahaan,
    b.name as cabang,
    b.type,
    a.name as dinas,
    d.name as unit_kerja,
    u.display_name as pengawas,
    b.status
FROM wp_app_customer_branches b
LEFT JOIN wp_app_customers c ON b.customer_id = c.id
LEFT JOIN wp_app_agencies a ON b.agency_id = a.id
LEFT JOIN wp_app_agency_divisions d ON b.division_id = d.id
LEFT JOIN wp_users u ON b.inspector_id = u.ID
WHERE c.reg_type = 'generate'
ORDER BY b.type, b.id
LIMIT 10;
```

**Result**:
```
id | perusahaan           | cabang                              | type   | dinas                    | unit_kerja            | pengawas       | status
---|---------------------|-------------------------------------|--------|--------------------------|----------------------|----------------|-------
1  | PT Maju Bersama     | PT Maju Bersama Cabang Pandeglang   | cabang | Disnaker Provinsi Banten | UPT Kabupaten Pandeglang | Ilham Jasmine | active
2  | PT Maju Bersama     | PT Maju Bersama Cabang Kota Tual    | cabang | Disnaker Provinsi Maluku | UPT Kota Tual        | Tari Yusuf    | active
...
```

✅ **Expected**: All JOIN relationships working, no NULL values for dinas/unit_kerja

---

#### 5. Extra Branches (inspector_id = NULL)

```sql
SELECT
    b.id,
    c.name as perusahaan,
    b.name as cabang,
    a.name as dinas,
    d.name as unit_kerja,
    u.display_name as pengawas
FROM wp_app_customer_branches b
LEFT JOIN wp_app_customers c ON b.customer_id = c.id
LEFT JOIN wp_app_agencies a ON b.agency_id = a.id
LEFT JOIN wp_app_agency_divisions d ON b.division_id = d.id
LEFT JOIN wp_users u ON b.inspector_id = u.ID
WHERE c.reg_type = 'generate' AND b.inspector_id IS NULL
LIMIT 5;
```

**Result**:
```
id | perusahaan           | cabang                                | dinas                    | unit_kerja              | pengawas
---|---------------------|---------------------------------------|--------------------------|------------------------|----------
21 | PT Maju Bersama     | PT Maju Bersama Cabang Jakarta Utara  | Disnaker Provinsi DKI    | UPT Kota Jakarta Utara | NULL
22 | CV Cipta Kreasi     | CV Cipta Kreasi Cabang Purbalingga    | Disnaker Provinsi Jateng | UPT Kabupaten Purbalingga | NULL
...
```

✅ **Expected**:
- Dinas (agency) filled
- Unit Kerja (division) filled
- Pengawas NULL (for testing assign inspector)

---

## Kesimpulan

### ✅ Completed Tasks

1. ✅ **BranchDemoData sync with runtime flow**
   - Replaced direct wpdb->insert() with BranchController::createDemoBranch()
   - Replaced generate-specific methods with BranchModel runtime methods
   - Removed generatePusatBranch() (now via HOOK)

2. ✅ **Location assignment with fallback**
   - BranchModel::getAgencyAndDivisionIds() dengan fallback logic
   - BranchModel::getInspectorId() dengan division → agency fallback

3. ✅ **Inspector assignment**
   - 20 cabang branches dengan inspector_id filled (division-based)
   - 20 extra branches dengan inspector_id=NULL (for testing)
   - 10 pusat branches dengan inspector_id filled (via HOOK)

4. ✅ **Cache invalidation**
   - BranchController::createDemoBranch() handle semua cache invalidation
   - Company menu query returns correct data
   - No stale cache issues

5. ✅ **Cleanup logic**
   - Preserve pusat branches created by HOOK
   - Only delete type='cabang' branches

### 📊 Test Results Summary

- **Total Branches**: 50 (10 pusat + 40 cabang)
- **Inspector Assignment**: 30/50 with inspector, 20/50 without (for testing)
- **Agency Assignment**: 50/50 (100%)
- **Division Assignment**: 50/50 (100%)
- **Cache Status**: ✅ Working (Company menu displays correct data)

### 🎯 Key Achievement

**Branch generation sekarang menggunakan runtime flow yang sama dengan production:**
- ✅ Controller → Model → Validator
- ✅ Cache invalidation handled by Controller
- ✅ Location assignment dengan fallback logic
- ✅ Inspector assignment dengan division → agency fallback
- ✅ HOOK system untuk auto-create pusat branch dan employee

---

## Related Files

### Controllers
- `/wp-customer/src/Controllers/Branch/BranchController.php` - createDemoBranch() method

### Models
- `/wp-customer/src/Models/Branch/BranchModel.php` - getAgencyAndDivisionIds(), getInspectorId(), create()
- `/wp-customer/src/Models/Company/CompanyModel.php` - Company list query

### Validators
- `/wp-customer/src/Validators/Branch/BranchValidator.php` - Branch validation rules
- `/wp-customer/src/Validators/Company/CompanyValidator.php` - Company validation

### Handlers
- `/wp-customer/src/Handlers/AutoEntityCreator.php` - HOOK handlers for auto-create

### Database Tables
- `/wp-customer/src/Database/Tables/BranchesDB.php` - Branch schema
- `/wp-customer/src/Database/Tables/CustomerEmployeesDB.php` - Employee schema
- `/wp-customer/src/Database/Tables/CustomersDB.php` - Customer schema

### Demo Data
- `/wp-customer/src/Database/Demo/BranchDemoData.php` - **MODIFIED**
- `/wp-customer/src/Database/Demo/Data/BranchUsersData.php` - Branch user data

### Agency Plugin (Referenced)
- `/wp-agency/src/Database/Tables/AgencyEmployeesDB.php` - Employee schema
- `/wp-agency/src/Database/Tables/AgencysDB.php` - Agency schema
- `/wp-agency/src/Database/Tables/DivisionsDB.php` - Division schema
- `/wp-agency/src/Database/Tables/JurisdictionDB.php` - Jurisdiction schema

---

## Git Commit Message

```
feat(demo): sync BranchDemoData with runtime Controller/Model flow (Task-2167)

- Replace wpdb->insert() with BranchController::createDemoBranch()
- Use BranchModel::getAgencyAndDivisionIds() for location assignment with fallback
- Use BranchModel::getInspectorId() for inspector assignment with division fallback
- Remove generatePusatBranch() - now handled by wp_customer_created HOOK
- Generate 20 cabang branches with inspector_id filled
- Generate 20 extra branches with inspector_id=NULL for testing assign inspector
- Preserve pusat branches in cleanup (only delete type='cabang')
- Cache invalidation handled by BranchController::createDemoBranch()

Test results:
- 50 total branches (10 pusat via HOOK + 40 cabang via generator)
- 30 branches with inspector, 20 without (for testing)
- All branches have agency_id and division_id filled
- Company menu displays correct data (cache working)

Related: Task-2165 (AutoEntityCreator), Task-2166 (CustomerDemoData sync)
```
