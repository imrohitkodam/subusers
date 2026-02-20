# Joomla 6 Migration Rules for com_jgive

## OBJECTIVE
Analyze the entire com_jgive codebase and automatically refactor it from Joomla 5 to Joomla 6 compatibility. Follow these rules strictly and apply changes across all files.

---

## CRITICAL BREAKING CHANGES

### 1. CMSObject Removal
**Problem**: `CMSObject` class completely removed in Joomla 6

**Detection Pattern**:
- Any usage of `Joomla\CMS\Object\CMSObject`
- Calls to `->get('property')` or `->set('property', $value)`
- `getItem()` method returns that are type-hinted as `CMSObject`

**Fix Rules**:
```php
// OLD - Remove this pattern
use Joomla\CMS\Object\CMSObject;
$item = new CMSObject();
$title = $item->get('title');
$item->set('title', 'New Title');

// NEW - Replace with this
$item = new \stdClass();
$title = $item->title;
$item->title = 'New Title';

// OR use Registry for complex data
use Joomla\Registry\Registry;
$item = new Registry();
$title = $item->get('title');
$item->set('title', 'New Title');
```

**AdminModel getItem() Migration**:
```php
// OLD
$article = $model->getItem(1);
echo $article->get('title');
$article->set('published', 1);

// NEW
$article = $model->getItem(1); // Now returns stdClass
echo $article->title;
$article->published = 1;
```

**Action Required**:
- Search ALL files for `->get(` and `->set(` method calls
- Replace with direct property access: `$object->property`
- Update return type hints from `CMSObject` to `\stdClass`
- Remove `use Joomla\CMS\Object\CMSObject;` imports

---

### 2. getInstance() Method Deprecations
**Problem**: All `getInstance()` static methods removed in Joomla 6

#### Application getInstance()
```php
// OLD - REMOVE
$app = CMSApplication::getInstance('site');

// NEW - Use DI Container
use Joomla\CMS\Factory;
$app = Factory::getApplication();
// OR
$app = Factory::getContainer()->get(SiteApplication::class);
```

#### Controller getInstance()
```php
// OLD - REMOVE
$controller = BaseController::getInstance('MyComponent');

// NEW - Use MVCFactory
$controller = Factory::getApplication()
    ->bootComponent('com_jgive')
    ->getMVCFactory()
    ->createController($name, $prefix, $config, $app, $input);
```

#### Table getInstance()
```php
// OLD - REMOVE
$table = Table::getInstance('MyTable', 'MyPrefix');

// NEW - Use MVCFactory
$table = Factory::getApplication()
    ->bootComponent('com_jgive')
    ->getMVCFactory()
    ->createTable($name, $prefix, $config);
```

#### Cache getInstance()
```php
// OLD - REMOVE
$cache = Cache::getInstance('output', $options);

// NEW - Use CacheControllerFactory
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
$cacheFactory = Factory::getContainer()->get(CacheControllerFactoryInterface::class);
$cache = $cacheFactory->createCacheController('output', $options);
```

#### Document getInstance()
```php
// OLD - REMOVE
$doc = Document::getInstance('html');

// NEW
$doc = Factory::getApplication()->getDocument();
```

#### Categories getInstance()
```php
// OLD - REMOVE
$categories = Categories::getInstance('Content');

// NEW
$categories = Factory::getApplication()
    ->bootComponent('com_content')
    ->getCategory($options, $section);
```

**Action Required**:
- Search for ALL `::getInstance(` calls
- Replace with appropriate DI container or Factory method
- Update use statements

---

### 3. Filesystem Package Migration
**Problem**: `Joomla\CMS\Filesystem` completely replaced with `Joomla\Filesystem`

**Namespace Migration**:
```php
// OLD - REMOVE these namespaces
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filesystem\FilesystemHelper;
use Joomla\CMS\Filesystem\Stream;
use Joomla\CMS\Filesystem\Streams\StreamString;

// NEW - Replace with Framework namespaces
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Filesystem\Helper; // Note: FilesystemHelper → Helper
use Joomla\Filesystem\Stream;
use Joomla\Filesystem\Stream\StringWrapper; // Note: StreamString → StringWrapper
```

**Error Handling Changes**:
```php
// OLD - setError/getError pattern
if (!File::copy($src, $dest)) {
    $error = File::getError();
    // handle error
}

// NEW - Exception handling
use Joomla\Filesystem\Exception\FilesystemException;
try {
    File::copy($src, $dest);
} catch (FilesystemException $e) {
    // handle exception: $e->getMessage()
}
```

**Action Required**:
- Replace ALL `Joomla\CMS\Filesystem\*` with `Joomla\Filesystem\*`
- Wrap File/Folder operations in try-catch blocks
- Remove `getError()` and `setError()` calls
- Handle exceptions instead

---

### 4. HTTP Package Migration
**Problem**: `Joomla\CMS\Http` deprecated in J6, removed in J8

**Migration Pattern**:
```php
// OLD - REMOVE
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Http\Response;
$http = HttpFactory::getHttp();
$response = $http->get($url);
$body = $response->body; // Magic property access

// NEW - Use Framework HTTP
use Joomla\Http\HttpFactory;
use Psr\Http\Message\ResponseInterface;
$http = (new HttpFactory())->getHttp();
$response = $http->get($url);
$body = $response->getBody()->getContents(); // PSR-7 methods
```

**PSR-7 Response Methods**:
```php
// OLD magic properties - REMOVE
$response->body
$response->code
$response->headers

// NEW PSR-7 methods
$response->getBody()->getContents()
$response->getStatusCode()
$response->getHeaders()
$response->getHeader('Content-Type')
```

**Action Required**:
- Replace `Joomla\CMS\Http` namespace with `Joomla\Http`
- Update response object access to use PSR-7 methods
- Change `->body` to `->getBody()->getContents()`
- Change `->code` to `->getStatusCode()`

---

### 5. Application Input Property
**Problem**: Direct `$app->input` access deprecated

```php
// OLD - REMOVE
$app->input->get('foo');
Factory::getApplication()->input->get('bar');

// NEW
$app->getInput()->get('foo');
Factory::getApplication()->getInput()->get('bar');
```

**Action Required**:
- Search for `->input->get(` pattern
- Replace with `->getInput()->get(`

---

### 6. Component Path Constants
**Problem**: JPATH constants removed

```php
// OLD - REMOVE
JPATH_COMPONENT
JPATH_COMPONENT_SITE
JPATH_COMPONENT_ADMINISTRATOR

// NEW - Calculate dynamically
use Joomla\CMS\Component\ComponentHelper;
$componentPath = JPATH_ROOT . '/components/com_jgive';
$adminPath = JPATH_ADMINISTRATOR . '/components/com_jgive';
```

**Action Required**:
- Remove usage of deprecated JPATH_COMPONENT* constants
- Use explicit path construction

---

### 7. Error Handling Traits
**Problem**: LegacyErrorHandlingTrait removed

```php
// OLD - REMOVE
$this->setError('Error message');
$error = $this->getError();

// NEW - Throw exceptions
throw new \RuntimeException('Error message');

// OR in try-catch
try {
    // code
} catch (\Exception $e) {
    // handle error
}
```

**Action Required**:
- Remove all `setError()` and `getError()` calls
- Replace with exception throwing
- Add try-catch blocks where needed

---

### 8. Dependency Injection Pattern
**Problem**: Need to use DI Container for services

**Service Provider Pattern**:
```php
// In services/provider.php
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Jgive\\Component\\Jgive'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Jgive\\Component\\Jgive'));
    }
};
```

**Getting Services**:
```php
// Use Factory::getContainer() for transitional code
$container = Factory::getContainer();
$service = $container->get(ServiceInterface::class);
```

**Action Required**:
- Ensure services/provider.php exists and properly configured
- Use dependency injection for new code
- Migrate static factory calls to DI gradually

---

### 9. Database Query Changes
**Problem**: Some database methods deprecated

```php
// OLD - REMOVE
$db->getQuery(true)->clear();

// NEW - Use fresh query object
$query = $db->getQuery(true);

// OLD - REMOVE  
$db->loadResultArray()

// NEW
$db->loadColumn()
```

**Action Required**:
- Replace `loadResultArray()` with `loadColumn()`
- Don't reuse query objects with clear()

---

### 10. Session Changes
**Problem**: Direct session access deprecated

```php
// OLD - REMOVE
$session = Factory::getSession();
$session->set('key', 'value');

// NEW - Use application session
$app = Factory::getApplication();
$session = $app->getSession();
$session->set('key', 'value');
```

**Action Required**:
- Replace `Factory::getSession()` with `$app->getSession()`

---

### 11. Router Changes
**Problem**: Application getRouter() deprecated

```php
// OLD - REMOVE
$router = $app->getRouter();

// NEW - Use Factory or DI
use Joomla\CMS\Router\Router;
$router = Router::getInstance('site');

// OR via DI Container
$router = Factory::getContainer()->get(Router::class);
```

**Action Required**:
- Replace `$app->getRouter()` calls
- Use Router::getInstance() or DI container

---

### 12. Language Loading
**Problem**: Component language loading changes

```php
// OLD - May still work but not recommended
Factory::getLanguage()->load('com_jgive', JPATH_ADMINISTRATOR);

// NEW - Better approach
$lang = Factory::getApplication()->getLanguage();
$lang->load('com_jgive', JPATH_ADMINISTRATOR . '/components/com_jgive');
```

**Action Required**:
- Use explicit component path for language files
- Get language from application object

---

## ANALYSIS CHECKLIST

Cursor AI must scan EVERY file in com_jgive and check for:

### PHP Files (.php)
- [ ] `CMSObject` usage → Replace with `stdClass` or `Registry`
- [ ] `->get()` and `->set()` method calls → Direct property access
- [ ] `::getInstance()` static calls → DI Container or Factory methods
- [ ] `use Joomla\CMS\Filesystem\*` → `use Joomla\Filesystem\*`
- [ ] `use Joomla\CMS\Http\*` → `use Joomla\Http\*`
- [ ] `->input->get(` → `->getInput()->get(`
- [ ] `JPATH_COMPONENT*` constants → Dynamic path construction
- [ ] `setError()` / `getError()` → Exception handling
- [ ] Magic property access on HTTP responses → PSR-7 methods
- [ ] File/Folder operations without try-catch → Add exception handling
- [ ] `loadResultArray()` → `loadColumn()`
- [ ] `Factory::getSession()` → `$app->getSession()`
- [ ] `$app->getRouter()` → `Router::getInstance()`

### XML Files (.xml)
- [ ] Update minimum Joomla version to 6.0
- [ ] Update namespace declarations if needed
- [ ] Check service provider registration

### Namespace Files
- [ ] Ensure proper namespace structure: `Jgive\Component\Jgive\*`
- [ ] Update use statements for all changed classes

---

## MIGRATION STRATEGY

### Step 1: Automated Search & Replace
Run these regex patterns across codebase:

1. **CMSObject imports**:
   - Find: `use Joomla\\CMS\\Object\\CMSObject;`
   - Action: Remove or replace with `use Joomla\\Registry\\Registry;`

2. **Filesystem namespace**:
   - Find: `use Joomla\\CMS\\Filesystem\\`
   - Replace: `use Joomla\\Filesystem\\`

3. **HTTP namespace**:
   - Find: `use Joomla\\CMS\\Http\\`
   - Replace: `use Joomla\\Http\\`

4. **Input property**:
   - Find: `->input->get\(`
   - Replace: `->getInput()->get(`

5. **Session Factory**:
   - Find: `Factory::getSession()`
   - Context check needed - replace appropriately

### Step 2: Complex Refactoring
For each file:

1. Identify `->get()` and `->set()` method calls
2. Determine if they're CMSObject methods or legitimate getter/setter
3. Replace CMSObject methods with property access
4. Update type hints in function signatures
5. Add exception handling for File/Folder operations
6. Replace `::getInstance()` with appropriate DI/Factory patterns
7. Update database query methods
8. Fix session and router access

### Step 3: Testing
After refactoring:
- Ensure no syntax errors
- Check all namespace imports are correct
- Verify exception handling is in place
- Test critical workflows
- Check Joomla error logs for deprecation warnings

---

## CODE QUALITY RULES

1. **Maintain PSR-12 coding standards**
2. **Add type hints** where missing (PHP 7.4+ features)
3. **Use strict types**: Add `declare(strict_types=1);` to files where appropriate
4. **Remove dead code**: Delete unused imports and variables
5. **Consistent naming**: Follow Joomla naming conventions
6. **Add docblocks**: Ensure all public methods have documentation
7. **Use null coalescing**: Replace isset() with ?? operator where appropriate
8. **Arrow functions**: Use fn() for simple callbacks (PHP 7.4+)

---

## COMMON PATTERNS TO FIX

### Pattern 1: Model getItem()
```php
// Search for this pattern
public function getItem($pk = null): CMSObject
{
    $item = parent::getItem($pk);
    // Custom logic
    return $item;
}

// Replace with
public function getItem($pk = null): \stdClass
{
    $item = parent::getItem($pk);
    // Custom logic
    return $item;
}
```

### Pattern 2: Table Binding
```php
// Search for this pattern
$table = Table::getInstance('Donation', 'JgiveTable');
if ($table->bind($data)) {
    $table->store();
}

// Replace with
$mvcFactory = Factory::getApplication()
    ->bootComponent('com_jgive')
    ->getMVCFactory();
$table = $mvcFactory->createTable('Donation', 'Administrator');
if ($table->bind($data)) {
    $table->store();
}
```

### Pattern 3: File Upload Handling
```php
// Search for this pattern
if (File::upload($src, $dest)) {
    // success
} else {
    $error = File::getError();
}

// Replace with
use Joomla\Filesystem\Exception\FilesystemException;
try {
    File::upload($src, $dest);
    // success
} catch (FilesystemException $e) {
    $error = $e->getMessage();
}
```

### Pattern 4: HTTP Requests
```php
// Search for this pattern
$http = HttpFactory::getHttp();
$response = $http->get($url);
$data = json_decode($response->body);

// Replace with
$http = (new \Joomla\Http\HttpFactory())->getHttp();
$response = $http->get($url);
$data = json_decode($response->getBody()->getContents());
```

### Pattern 5: Error Messages in Models
```php
// Search for this pattern
if (!$result) {
    $this->setError('Operation failed');
    return false;
}

// Replace with
if (!$result) {
    throw new \RuntimeException('Operation failed');
}
```

---

## VALIDATION CHECKLIST

Before marking migration complete, verify:

- [ ] No references to `CMSObject` remain
- [ ] No `::getInstance()` calls remain (except Router::getInstance)
- [ ] All `Joomla\CMS\Filesystem\*` changed to `Joomla\Filesystem\*`
- [ ] All `Joomla\CMS\Http\*` changed to `Joomla\Http\*`
- [ ] All File/Folder operations wrapped in try-catch
- [ ] No `setError()` or `getError()` calls remain
- [ ] All `->input->` changed to `->getInput()->`
- [ ] No JPATH_COMPONENT* constants used
- [ ] services/provider.php properly configured
- [ ] XML manifest shows version 6.0 minimum
- [ ] All files have proper namespace declarations
- [ ] Code passes syntax validation
- [ ] No deprecated warnings in error log
- [ ] `loadResultArray()` replaced with `loadColumn()`
- [ ] Session access via application object
- [ ] Router access properly updated

---

## FILE-SPECIFIC MIGRATIONS

### Component Manifest (jgive.xml)
```xml
<!-- Update minimum version -->
<minimumPhpVersion>7.4</minimumPhpVersion>
<minimumJoomlaVersion>6.0</minimumJoomlaVersion>

<!-- Ensure namespace is declared -->
<namespace path="src">Jgive\Component\Jgive</namespace>
```

### Service Provider (services/provider.php)
Ensure this file exists with proper structure:
```php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Jgive\\Component\\Jgive'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Jgive\\Component\\Jgive'));
    }
};
```

### Extension Class (Extension/JgiveComponent.php)
Ensure proper implementation:
```php
<?php
namespace Jgive\Component\Jgive\Administrator\Extension;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Psr\Container\ContainerInterface;

class JgiveComponent extends MVCComponent implements BootableExtensionInterface
{
    public function boot(ContainerInterface $container): void
    {
        // Boot logic here
    }
}
```

---

## BACKWARD COMPATIBILITY

**Note**: Joomla 6 includes "Backward Compatibility 6" plugin that provides limited compatibility layer for:
- Some deprecated methods (temporary)
- Old namespace references (limited)

**However**: DON'T rely on this plugin - make proper changes for long-term maintainability. The BC plugin will be removed in future versions.

---

## PRIORITY ORDER

### 1. HIGH PRIORITY (Breaking changes - won't work without fix):
- CMSObject → stdClass
- getInstance() replacements
- Filesystem namespace changes
- File/Folder error handling

### 2. MEDIUM PRIORITY (Deprecated but may work with BC plugin):
- HTTP package migration
- Error handling trait removal
- Session access changes
- Router access changes

### 3. LOW PRIORITY (Code quality improvements):
- Type hints
- Docblocks
- Dead code removal
- PSR-12 formatting

---

## TESTING CHECKLIST

After migration, test:

### Functionality Tests
- [ ] Component installs successfully
- [ ] Frontend views load correctly
- [ ] Backend views load correctly
- [ ] Forms submit successfully
- [ ] Database operations work (save, edit, delete)
- [ ] File uploads work (if applicable)
- [ ] API calls work (if applicable)
- [ ] Permissions and ACL work correctly

### Error Checking
- [ ] No PHP errors in error log
- [ ] No deprecated warnings in Joomla System - Debug plugin
- [ ] No JavaScript console errors
- [ ] All AJAX calls return proper responses

### Performance
- [ ] Page load times acceptable
- [ ] Database queries optimized
- [ ] No memory issues

---

## EXECUTION INSTRUCTION FOR CURSOR AI

### Phase 1: Analysis Mode
1. Scan entire com_jgive directory recursively
2. Generate comprehensive report of all issues found
3. Categorize by priority (High/Medium/Low)
4. List all files requiring changes with specific line numbers
5. Identify any custom code that needs manual review

### Phase 2: Fix Mode - High Priority
1. Start with HIGH PRIORITY issues
2. Process files in this order:
   - Service provider files
   - Extension class
   - Models (admin & site)
   - Controllers
   - Views
   - Helpers
   - Tables
3. Apply all relevant rules from this document
4. Verify syntax after each file
5. Move to next file only after current is valid

### Phase 3: Fix Mode - Medium Priority
1. Process MEDIUM PRIORITY issues
2. Same file order as Phase 2
3. Verify no regressions

### Phase 4: Fix Mode - Low Priority
1. Code quality improvements
2. Add missing docblocks
3. Clean up unused code
4. Format per PSR-12

### Phase 5: Validation
1. Run all validation checklist items
2. Generate final migration report
3. List any items requiring manual review

---

## REPORT FORMAT

For each file changed, document:

```markdown
### File: /path/to/file.php

**Priority**: High/Medium/Low

**Issues Found**:
1. CMSObject usage on lines: 45, 67, 89
2. getInstance() call on line: 123
3. Filesystem namespace on lines: 34, 56

**Changes Applied**:
1. Replaced CMSObject with stdClass
2. Replaced Table::getInstance() with MVCFactory
3. Updated namespace from Joomla\CMS\Filesystem to Joomla\Filesystem
4. Added try-catch for File operations

**Lines Modified**: 34, 45, 56, 67, 89, 123, 145

**Manual Review Needed**: 
- Line 234: Complex logic in get() method - verify replacement

**Status**: ✅ Complete / ⚠️ Needs Review / ❌ Error
```

---

## CURSOR AI PROMPT

Use this exact prompt in Cursor Composer:

```
@joomla6-migration-rules.md 

Task: Migrate com_jgive component from Joomla 5 to Joomla 6 compatibility.

Phase 1 - Analysis:
Scan the entire com_jgive codebase and generate a detailed report of all Joomla 6 compatibility issues following the priority system in the rules file. List every file that needs changes with specific line numbers and issue types.

Phase 2 - Migration:
Systematically refactor all files following the migration rules, starting with HIGH PRIORITY issues. Process one file at a time, verify syntax, then move to next. Document all changes made.

Phase 3 - Validation:
Run through the validation checklist and confirm all deprecated code has been removed or updated. Generate final migration summary report.

Follow all patterns, rules, and best practices exactly as specified in the rules document.
```

---

## ADDITIONAL RESOURCES

### Official Documentation
- Joomla 6 Developer Documentation: https://manual.joomla.org
- API Reference: https://api.joomla.org/cms-6/

### Key Changes Documentation
- Removed and Backward Incompatibility: Check Joomla manual
- New Deprecations: Check Joomla developer docs
- PSR-7 HTTP Message Interface: https://www.php-fig.org/psr/psr-7/

---

## TROUBLESHOOTING

### Common Issues After Migration

**Issue**: Class not found errors
**Solution**: Check namespace declarations and use statements

**Issue**: Method not found on stdClass
**Solution**: Verify all ->get() and ->set() calls replaced with property access

**Issue**: Filesystem operations fail silently
**Solution**: Ensure try-catch blocks are in place with proper exception handling

**Issue**: HTTP responses empty
**Solution**: Check PSR-7 method usage - use getBody()->getContents()

**Issue**: Tables not loading
**Solution**: Verify MVCFactory implementation in service provider

**Issue**: Session data lost
**Solution**: Check session access via application object

---

## SUCCESS CRITERIA

Migration is complete when:

1. ✅ All validation checklist items pass
2. ✅ No PHP errors in logs
3. ✅ No deprecated warnings
4. ✅ All functionality tests pass
5. ✅ Component installs on Joomla 6
6. ✅ Frontend and backend work correctly
7. ✅ No legacy code patterns remain
8. ✅ Code follows Joomla 6 best practices

---

**END OF MIGRATION RULES**

---

## VERSION HISTORY

- v1.0 (2025-11-18): Initial comprehensive migration ruleset for com_jgive Joomla 5 → Joomla 6
