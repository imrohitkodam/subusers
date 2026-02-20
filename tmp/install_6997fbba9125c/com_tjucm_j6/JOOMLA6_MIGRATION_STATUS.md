# Joomla 6 Migration Status for com_tjucm

## Migration Progress: Phase 1 Complete (Critical Infrastructure)

### ✅ COMPLETED CHANGES

#### 1. DI Infrastructure Setup
- ✅ Created `services/provider.php` with MVCFactory and ComponentDispatcherFactory registration
- ✅ Configured proper namespace: `Tjucm\Component\Tjucm`

#### 2. CMSObject Removal (CRITICAL)
- ✅ `site/includes/item.php` - Removed CMSObject extension, converted to plain class
- ✅ `site/includes/item.php` - Replaced `$table->get('id')` with `$table->id`
- ✅ `site/includes/item.php` - Replaced setError() with exception throwing
- ✅ `site/models/itemform.php` - Removed CMSObject import

#### 3. Filesystem Namespace Migration (CRITICAL)
- ✅ `site/controllers/items.php` - Changed to `Joomla\Filesystem\File`
- ✅ `site/models/itemform.php` - Changed to `Joomla\Filesystem\File`
- ✅ `site/views/document/view.html.php` - Changed to `Joomla\Filesystem\File`
- ✅ `admin/controllers/item.php` - Changed to `Joomla\Filesystem\File`
- ✅ `admin/controllers/types.php` - Changed to `Joomla\Filesystem\File`
- ✅ `admin/houseKeeping/1.2.3/updateClientName.php` - Changed to `Joomla\Filesystem\File`

#### 4. Table::getInstance() Replacements (CRITICAL)
- ✅ `site/includes/tjucm.php` - Replaced with MVCFactory->createTable()
- ✅ `site/helpers/tjucm.php` - Replaced with MVCFactory->createTable()
- ✅ `site/controllers/item.php` - Replaced with MVCFactory->createTable()

#### 5. BaseDatabaseModel::getInstance() Replacements (CRITICAL)
- ✅ `site/includes/tjucm.php` - Replaced with MVCFactory->createModel()
- ✅ `site/helpers/tjucm.php` - Replaced with MVCFactory->createModel()
- ✅ `site/controllers/item.php` - Replaced with MVCFactory->createModel()

#### 6. BaseController::getInstance() Replacement (CRITICAL)
- ✅ `admin/tjucm.php` - Replaced with MVCFactory->createController()

#### 7. Factory::getSession() Replacements (HIGH)
- ✅ `site/controllers/itemform.php` - Changed to `$app->getSession()`
- ✅ `site/controllers/itemform.json.php` - Changed to `$app->getSession()` (2 instances)
- ✅ `site/models/items.php` - Changed to `$app->getSession()`
- ✅ `admin/houseKeeping/1.2.3/updateClientName.php` - Changed to `$app->getSession()`

#### 8. Input Property Access Fixes (HIGH)
- ✅ `site/includes/tjucm.php` - Changed `->input->` to `->getInput()->`
- ✅ `site/helpers/tjucm.php` - Changed `->input->` to `->getInput()->`
- ✅ `site/controllers/item.php` - Changed `->input->` to `->getInput()->` (4 instances)
- ✅ `admin/tjucm.php` - Changed `->input->` to `->getInput()->`

---

## ⚠️ REMAINING WORK

### HIGH PRIORITY (Breaking Changes)

#### Table::getInstance() - Remaining Files (30+ instances)
- ⚠️ `site/controllers/items.php` (Lines 228, 237, 363)
- ⚠️ `site/controllers/itemform.php` (Line 65)
- ⚠️ `site/router.php` (Lines 114, 156)
- ⚠️ `site/includes/access.php` (15+ instances: 134, 206, 252, 288, 316, 322, 347, 383, 409, 413)
- ⚠️ `site/models/item.php` (Lines 75, 243)
- ⚠️ `site/models/document.php` (Lines 55, 178, 289)
- ⚠️ `site/views/itemform/view.html.php` (Lines 249, 291, 341)
- ⚠️ `site/views/item/view.html.php` (Lines 137, 164)
- ⚠️ `site/layouts/detail/fields.php` (Line 58)
- ⚠️ `site/layouts/list/list.php` (Line 51)
- ⚠️ `admin/controllers/types.php` (Multiple instances)

#### BaseDatabaseModel::getInstance() - Remaining Files (15+ instances)
- ⚠️ `site/controllers/items.php` (Lines 95, 122, 262, 372, 461)
- ⚠️ `site/controllers/itemform.php` (Line 74)
- ⚠️ `site/controllers/type.php` (Lines 63, 68, 118)
- ⚠️ `site/layouts/detail/fields.php` (Line 110)
- ⚠️ `site/layouts/list/list.php` (Lines 51, 190)

#### Input Property Access - Remaining Files (40+ instances)
- ⚠️ `site/controllers/items.php` (Lines 64, 65, 353, 555)
- ⚠️ `site/controllers/itemform.php` (Lines 39, 40, 45, 110, 136, 183, 231, 234, 321, 322)
- ⚠️ `site/controllers/items.json.php` (Lines 49, 62, 63, 158, 169, 170, 269, 278, 279, 280, 365, 374, 375)
- ⚠️ `site/controllers/document.php` (Lines 36, 37, 38)
- ⚠️ `site/controllers/document.json.php` (Lines 40, 41, 42)
- ⚠️ `site/controllers/itemform.json.php` (Lines 61, 770, 771, 772, 828, 829, 833, 834, 835)

#### InputFilter::getInstance() (CRITICAL)
- ⚠️ `site/controllers/items.php` (Line 455)
- ⚠️ `site/controllers/items.json.php` (Line 68)

#### JTable::getInstance() (CRITICAL)
- ⚠️ `site/controllers/items.php` (Line 517)

---

### MEDIUM PRIORITY (Deprecated but may work with BC plugin)

#### ->get() and ->set() Method Calls on Objects (30+ instances)
- ⚠️ `site/helpers/tjucm.php` (Line 122)
- ⚠️ `site/controllers/items.php` (Lines 85, 180, 194, 217)
- ⚠️ `site/controllers/items.json.php` (Line 66)
- ⚠️ `site/includes/access.php` (Lines 50, 168, 215, 258, 296)
- ⚠️ `site/models/itemform.php` (Line 243)

#### JLoader Usage Review
- ⚠️ Multiple files using JLoader::import() and JLoader::register()
- Should be reviewed for Joomla 6 best practices

#### jimport() Function
- ⚠️ `admin/controllers/item.php` (Line 330)

#### JError Usage
- ⚠️ `admin/controllers/item.php` (Line 330)

---

### LOW PRIORITY (Code Quality)

#### Missing Type Hints
- ⚠️ Many functions lack proper PHP 7.4+ type hints

#### Missing Docblocks
- ⚠️ Some methods lack proper documentation

#### Dead Code
- ⚠️ `site/controllers/document.php` (Lines 263-269) - Commented code

---

## NEXT STEPS

### Immediate Actions Required:

1. **Complete Input Property Access Fixes**
   - Process all remaining controller files
   - Batch replace `->input->` with `->getInput()->`

2. **Complete Table::getInstance() Replacements**
   - Focus on high-usage files: `site/includes/access.php` (15+ instances)
   - Update all model and view files

3. **Complete BaseDatabaseModel::getInstance() Replacements**
   - Process remaining controller files
   - Update layout files

4. **Fix InputFilter and JTable getInstance() Calls**
   - Critical for component functionality

5. **Add Exception Handling for File Operations**
   - Wrap all File/Folder operations in try-catch blocks
   - Replace error checking with exception handling

6. **Update Component Manifest**
   - Update `admin/tjucm.xml` with Joomla 6 minimum version
   - Ensure namespace declarations are correct

7. **Create Extension Class**
   - Create `src/Extension/TjucmComponent.php` if needed
   - Implement BootableExtensionInterface

---

## TESTING CHECKLIST

After completing remaining work:

- [ ] Component installs on Joomla 6
- [ ] No PHP fatal errors
- [ ] No deprecated warnings in logs
- [ ] Frontend views load correctly
- [ ] Backend views load correctly
- [ ] Forms submit successfully
- [ ] Database operations work (save, edit, delete)
- [ ] File uploads work (if applicable)
- [ ] Permissions and ACL work correctly

---

## ESTIMATED REMAINING EFFORT

- **HIGH Priority**: 20-25 hours
- **MEDIUM Priority**: 10-15 hours
- **LOW Priority**: 3-5 hours
- **Total Remaining**: 33-45 hours

---

## FILES MODIFIED SO FAR (11 files)

1. services/provider.php (NEW)
2. site/includes/item.php
3. site/includes/tjucm.php
4. site/helpers/tjucm.php
5. site/controllers/item.php
6. site/controllers/items.php
7. site/controllers/itemform.php
8. site/controllers/itemform.json.php
9. site/models/items.php
10. site/models/itemform.php
11. site/views/document/view.html.php
12. admin/tjucm.php
13. admin/controllers/item.php
14. admin/controllers/types.php
15. admin/houseKeeping/1.2.3/updateClientName.php

---

## CRITICAL NOTES

1. **MVCFactory Pattern**: All getInstance() calls must use the MVCFactory pattern:
   ```php
   $mvcFactory = Factory::getApplication()
       ->bootComponent('com_tjucm')
       ->getMVCFactory();
   $table = $mvcFactory->createTable($name, 'Administrator', $config);
   $model = $mvcFactory->createModel($name, 'Site', $config);
   ```

2. **Input Access Pattern**: All input access must use getInput():
   ```php
   $app->getInput()->get('param')
   ```

3. **Session Access Pattern**: All session access must use application:
   ```php
   Factory::getApplication()->getSession()
   ```

4. **File Operations**: Must wrap in try-catch with FilesystemException:
   ```php
   use Joomla\Filesystem\Exception\FilesystemException;
   try {
       File::copy($src, $dest);
   } catch (FilesystemException $e) {
       // handle exception
   }
   ```

---

**Last Updated**: Migration Phase 1 Complete
**Status**: 15 files migrated, ~30 files remaining
**Progress**: ~30% complete
