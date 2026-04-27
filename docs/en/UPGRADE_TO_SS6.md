# Upgrade to SilverStripe 6

## Requirements

⚠️ **Update composer dependencies:**
- `dnadesign/silverstripe-elemental`: now requires `^6.0` (was `^5 || ^6`)
- `symbiote/silverstripe-gridfieldextensions`: now requires `^5.2` (was `^4`)

## API Changes

### Method Signature Changes

⚠️ **Add `#[Override]` attributes to overridden methods** (PHP 8.3+ requirement)

In classes extending framework classes, add the `#[Override]` attribute:

```php
use Override;

#[Override]
public function getList() { ... }

#[Override]
public function getManagedModels() { ... }

#[Override]
public function getEditForm($id = null, $fields = null) { ... }
```

### Deprecated Method Replacements

⚠️ **Replace `CMSEditLink()` with `getCMSEditLink()`**

The `CMSEditLink()` method has been removed. Update all calls:

```php
// Before
$sibling->CMSEditLink(true)

// After
$sibling->getCMSEditLink(true)
```

**Files affected:** `src/Extensions/ElementSearchExtension.php:122`
