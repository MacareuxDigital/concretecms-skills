# Package Structure and Creation

Concrete CMS packages are modular components that extend the functionality of the platform.

## Package Structure

Packages include:

- An outer directory
- Optional icon.png
- controller.php file defining:
  - Package version
  - Installation and uninstallation routines
  - Custom startup code for Concrete

## Package Creation

### Directory Name

For package creation in Concrete CMS:

- Use lowercase, underscore-separated names for the outer directory.
- Example: "Hello World" becomes "hello_world".

### Icon

- Include an icon.png at the root of the package.
- Any square size works, but typically 97px by 97px. Larger than 200px is unnecessary.

### Controller

Every package must have a controller.php in its base directory, handling package identification, installation, and uninstallation. Place additional logic elsewhere. Example structure:

```php
<?php

namespace Concrete\Package\HelloWorld;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Package\Package;

class Controller extends Package
{
    /**
     * The minimum Concrete version compatible with the package.
     * 
     * @var string
     */
    protected $appVersionRequired = '9.0.0';
    
    /**
     * The minimum PHP version compatible with the package.
     *
     * @var string
     */
    protected $phpVersionRequired = '8.2';

    /**
     * @var string
     */
    protected $pkgHandle = 'hello_world';

    /**
     * @var string
     */
    protected $pkgVersion = '1.0.0';
    
    /**
     * The custom autoloader prefixes to be automatically added to the class loader.
     *
     * @var array
     */
    protected $pkgAutoloaderRegistries = [
        'src' => '\HelloWorld',
    ];

    /**
     * @return string
     */
    public function getPackageName()
    {
        return t('Hello World');
    }

    /**
     * @return string
     */
    public function getPackageDescription()
    {
        return t('An example package for Concrete CMS.');
    }
    
    /**
     * @return \Concrete\Core\Entity\Package
     */
    public function install()
    {
        $pkg = parent::install();

        // Write installation logic here

        return $pkg;
    }
    
    public function upgrade()
    {
        parent::upgrade();
        
        $pkg = $this->getPackageEntity();

        // Write upgrade logic here
    }
    
    public function uninstall()
    {
        // Write uninstall logic here

        parent::uninstall();
    }
    
    public function on_start()
    {
        // Autoload 3rd party libraries
//        $this->registerAutoload();
    }

//    private function registerAutoload()
//    {
//        if (file_exists($this->getPackagePath() . '/vendor/autoload.php')) {
//            require $this->getPackagePath() . '/vendor/autoload.php';
//        }
//    }
}
```

- Namespace should match the package directory name, camelCased (e.g., `hello_world` → `Concrete\Package\HelloWorld` ).
- Add `defined('C5_EXECUTE') or die('Access Denied.');` for security.
- Import classes outside the package namespace as needed.
- Controller class named `Controller`, extending `Concrete\Core\Package\Package`.
- Include `$pkgHandle`, `$appVersionRequired`, and `$pkgVersion`. 
- Implement `getPackageDescription()` and `getPackageName()` for Dashboard display.
- Define `$pkgAutoloaderRegistries` property if you register custom namespaces for autoloading.
- Define `install()` method for installation actions.
- Define `upgrade()` method for adding or removing additional functionality. You need to update `$pkgVersion` and push the Update button to apply changes.
- Define `uninstall()` method for removing package data.
- Define `on_start()` method if you need custom startup code for the package (e.g., autoloading third-party libraries).
- Important: Namespace and directory name must align; mismatches result in 'Unknown Package' and 'Broken Package' errors.
