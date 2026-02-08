# Installing Entities in Packages

This document provides examples of how to install various Concrete CMS entities programmatically within a package's `install()` or `upgrade()` methods.

## Install Block Types

```php
use Concrete\Core\Block\BlockType\BlockType;

...

    public function install()
    {
        $pkg = parent::install();

        $bt = BlockType::getByHandle('hello_world');
        if (!$bt) {
            $bt = BlockType::installBlockType('hello_world', $pkg);
        }

        $set = Set::getByHandle('hello_world');
        if (!$set) {
            $set = Set::add('hello_world', 'Hello World', $pkg);
        }
        $set->addBlockType($bt);

        return $pkg;
    }
```

## Install Themes

```php
use Concrete\Core\Page\Theme\Theme;

...

    public function install()
    {
        $pkg = parent::install();

        $theme = Theme::getByHandle('hello_world');
        if (!$theme) {
            Theme::add('theme_boilerplate', $pkg);
        }

        return $pkg;
    }
```

## Install Single Pages

```php
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single;

...

    public function install()
    {
        $pkg = parent::install();
        
        $single = Page::getByPath('/hello_world');
        if (!is_object($single) || $single->isError()) {
            Single::add('/hello_world', $pkg);
        }
    }
```

## Install Containers

Containers should be registered programmatically by persisting a `Concrete\Core\Entity\Page\Container` entity.

```php
use Concrete\Core\Entity\Page\Container;
use Doctrine\ORM\EntityManagerInterface;

...

    protected function installContainers($pkg)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->app->make(EntityManagerInterface::class);
        $container = $em->getRepository(Container::class)->findOneByContainerHandle('my_container_handle');
        if (!$container) {
            $container = new Container();
            $container->setContainerHandle('my_container_handle');
            $container->setContainerName('My Container Name');
            $container->setContainerIcon('full.png');
            $container->setPackage($pkg);
            $em->persist($container);
            $em->flush();
        }
    }
```

Icon name should be one of the png files in `concrete/images/icons/containers` directory.

You can refer the php files in the `concrete/themes/atomik/elements/containers` directory for creating custom container layouts.

## Install Attribute Keys

This is an example code to install attribute keys in Concrete CMS.

```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\PageKey;
use Concrete\Core\Entity\Attribute\Key\Settings\TextSettings;

...

    public function install()
    {
        $pkg = parent::install();
        
        $service = $this->app->make(CategoryService::class);
        // Get page attribute category
        $category = $service->getByHandle('collection')?->getController();
        if ($category) {
            // Check if the attribute key already exists
            $ak = $category->getAttributeKeyByHandle('my_attribute_key_handle');
            if (!$ak) {
                // Create a new page attribute key
                $ak = new PageKey();
                $ak->setAttributeKeyHandle('my_attribute_key_handle');
                $ak->setAttributeKeyName('My Attribute Key Name');
                $ak->setIsAttributeKeySearchable(true);
                $ak->setIsAttributeKeyContentIndexed(true);
                $settings = new TextSettings();
                $settings->setPlaceholder('Please input here...');
                $category->add('text', $ak, $settings, $pkg);
            }
        }
}
```

You need to use valid settings classes for attribute types.

### Textarea

```php
$settings = new TextareaSettings();
$settings->setPlaceholder('Please input here...');
$settings->setMode('text');
$category->add('textarea', $ak, $settings, $pkg);
```

### Topic

```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\PageKey;
use Concrete\Core\Entity\Attribute\Key\Settings\TopicsSettings;
use Concrete\Core\Tree\Node\Type\Topic as TopicNode;
use Concrete\Core\Tree\Type\Topic as TopicTree;

$topicTree = TopicTree::getByName('Example Topic');
if (!$topicTree) {
    $topicTree = TopicTree::add('Example Topic');
    $rootNode = $topicTree->getRootTreeNodeObject();
    $topics = ['Topic 1', 'Topic 2', 'Topic 3'];
    foreach ($topics as $topic) {
        TopicNode::add($topic, $rootNode);
    }
}

$service = $this->app->make(CategoryService::class);
$category = $service->getByHandle('collection')?->getController();
if ($category) {
    $ak = $category->getAttributeKeyByHandle('topics_example');
    if (!$ak) {
        $ak = new PageKey();
        $ak->setAttributeKeyHandle('topics_example');
        $ak->setAttributeKeyName('Topics Example');
        $ak->setIsAttributeKeySearchable(true);
        $ak->setIsAttributeKeyContentIndexed(true);
        $settings = new TopicsSettings();
        $rootNode = $rootNode ?? $topicTree->getRootTreeNodeObject();
        $settings->setTopicTreeID($topicTree->getTreeID());
        $settings->setParentNodeID($rootNode->getTreeNodeID());
        $settings->setAllowMultipleValues(true);
        $category->add('topics', $ak, $settings, $pkg);
    }
}
```

### Option List

```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\PageKey;
use Concrete\Core\Entity\Attribute\Key\Settings\SelectSettings;
use Concrete\Core\Entity\Attribute\Value\Value\SelectValueOption;
use Concrete\Core\Entity\Attribute\Value\Value\SelectValueOptionList;

$service = $this->app->make(CategoryService::class);
$category = $service->getByHandle('collection')?->getController();
if ($category) {
    $ak = $category->getAttributeKeyByHandle('select_example');
    if (!$ak) {
        $ak = new PageKey();
        $ak->setAttributeKeyHandle('select_example');
        $ak->setAttributeKeyName('Select Example');
        $ak->setIsAttributeKeySearchable(true);
        $ak->setIsAttributeKeyContentIndexed(true);
        $settings = new SelectSettings();
        $list = new SelectValueOptionList();
        $optionValues = ['Option 1', 'Option 2', 'Option 3'];
        foreach ($optionValues as $displayOrder => $optionValue) {
            $opt = new SelectValueOption();
            $opt->setSelectAttributeOptionValue($optionValue);
            $opt->setIsEndUserAdded(false);
            $opt->setDisplayOrder($displayOrder);
            $opt->setOptionList($list);
            $list->getOptions()->add($opt);
        }
        $settings->setOptionList($list);
        $settings->setAllowMultipleValues(true);
        $settings->setAllowOtherValues(false);
        $settings->setDisplayMultipleValuesOnSelect(false);
        $settings->setHideNoneOption(false);
        $settings->setDisplayOrder('display_asc');
        $category->add('select', $ak, $settings, $pkg);
    }
}
```

### Image/File

```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\PageKey;
use Concrete\Core\Entity\Attribute\Key\Settings\ImageFileSettings;

$service = $this->app->make(CategoryService::class);
$category = $service->getByHandle('collection')?->getController();
if ($category) {
    $ak = $category->getAttributeKeyByHandle('file_example');
    if (!$ak) {
        $ak = new PageKey();
        $ak->setAttributeKeyHandle('file_example');
        $ak->setAttributeKeyName('File Example');
        $ak->setIsAttributeKeySearchable(false);
        $ak->setIsAttributeKeyContentIndexed(false);
        $settings = new ImageFileSettings();
        $settings->setModeToFileManager();
        // $settings->setModeToHtmlInput();
        $category->add('image_file', $ak, $settings, $pkg);
    }
}
```

## Install Attribute Sets

It's better to create a new attribute set and add custom attribute keys into it if your package has multiple attribute keys.

```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Attribute\SetFactory;

/** @var CategoryService $service */
$service = $this->app->make(CategoryService::class);
$category = $service->getByHandle('collection')?->getController();
if ($category) {
    /** @var SetFactory $setFactory */
    $setFactory = $this->app->make(SetFactory::class);
    $set = $setFactory->getByHandle('hello_world');
    if (!$set) {
        $set = $category->getSetManager()->addSet('hello_world', 'Hello World', $pkg);
    }

    $ak = $category->getAttributeKeyByHandle('number_example');
    if (!$ak) {
        $ak = new PageKey();
        $ak->setAttributeKeyHandle('number_example');
        $ak->setAttributeKeyName('Number Example');
        $category->add('number', $ak, null, $pkg);
        $category->getSetManager()->addKey($set, $ak);
    }
}
```

## Installing Attribute Types

```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Attribute\TypeFactory;

...

/** @var TypeFactory $factory */
$factory = $this->app->make(TypeFactory::class);
$type = $factory->getByHandle('personal_name');
if (!is_object($type)) {
    $type = $factory->add('personal_name', 'Personal Name', $pkg);
    /** @var CategoryService $service */
    $service = $this->app->make(CategoryService::class);
    $userCategory = $service->getByHandle('user')->getController();
    $userCategory->associateAttributeKeyType($type);
}
```
