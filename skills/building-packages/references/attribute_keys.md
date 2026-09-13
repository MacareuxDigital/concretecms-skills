# Creating Attribute Keys in Concrete CMS (Modern Approach)

This document provides examples of how to create various types of attribute keys using the modern, non-deprecated API in Concrete CMS 9.0+.

## General Workflow

1. Get the `CategoryService`.
2. Get the specific attribute category (e.g., `collection`, `user`, `file`, `site`).
3. Check if the attribute key already exists.
4. If it doesn't exist:
    - Instantiate the correct Key class (e.g., `CollectionKey`, `UserKey`, `FileKey`, `SiteKey` entities).
    - Set the handle, name, and search settings.
    - Create a settings object specific to the attribute type.
    - Use the category's `add()` method to register the key.

## Text
```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\UserKey; // Use the correct entity for the category
use Concrete\Core\Entity\Attribute\Key\Settings\TextSettings;

// Get Attribute Category Service
$service = $this->app->make(CategoryService::class);
// Get the category (e.g., 'user')
$category = $service->getByHandle('user')?->getController();
if ($category) {
    // Check if key already exists
    $ak = $category->getAttributeKeyByHandle('my_attribute_key_handle');
    if (!$ak) {
        // Create new key entity
        $ak = new UserKey();
        $ak->setAttributeKeyHandle('my_attribute_key_handle');
        $ak->setAttributeKeyName('My Attribute Key Name');
        $ak->setIsAttributeKeySearchable(true);
        $ak->setIsAttributeKeyContentIndexed(true);
        
        // Create settings object
        $settings = new TextSettings();
        $settings->setPlaceholder('Please input here...');
        
        // Register the attribute key
        $category->add('text', $ak, $settings, $pkg);
    }
}
```

## Textarea
```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Key\Settings\TextareaSettings;

$service = $this->app->make(CategoryService::class);
$category = $service->getByHandle('user')?->getController();
if ($category) {
    $ak = $category->getAttributeKeyByHandle('textarea_example');
    if (!$ak) {
        $ak = new UserKey();
        $ak->setAttributeKeyHandle('textarea_example');
        $ak->setAttributeKeyName('Textarea Example');
        $ak->setIsAttributeKeySearchable(true);
        $ak->setIsAttributeKeyContentIndexed(true);
        
        $settings = new TextareaSettings();
        $settings->setMode('text');
        
        $category->add('textarea', $ak, $settings, $pkg);
    }
}
```

## Topic
```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Key\Settings\TopicsSettings;
use Concrete\Core\Tree\Node\Type\Topic as TopicNode;
use Concrete\Core\Tree\Type\Topic as TopicTree;

// Get or Create Topic Tree
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
$category = $service->getByHandle('user')?->getController();
if ($category) {
    $ak = $category->getAttributeKeyByHandle('topics_example');
    if (!$ak) {
        $ak = new UserKey();
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

## Option List (Select)
```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Key\Settings\SelectSettings;
use Concrete\Core\Entity\Attribute\Value\Value\SelectValueOption;
use Concrete\Core\Entity\Attribute\Value\Value\SelectValueOptionList;

$service = $this->app->make(CategoryService::class);
$category = $service->getByHandle('user')?->getController();
if ($category) {
    $ak = $category->getAttributeKeyByHandle('select_example');
    if (!$ak) {
        $ak = new UserKey();
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

## File
```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Key\Settings\ImageFileSettings;

$service = $this->app->make(CategoryService::class);
$category = $service->getByHandle('user')?->getController();
if ($category) {
    $ak = $category->getAttributeKeyByHandle('file_example');
    if (!$ak) {
        $ak = new UserKey();
        $ak->setAttributeKeyHandle('file_example');
        $ak->setAttributeKeyName('File Example');
        $ak->setIsAttributeKeySearchable(false);
        $ak->setIsAttributeKeyContentIndexed(false);
        
        $settings = new ImageFileSettings();
        $settings->setModeToFileManager();
        
        $category->add('image_file', $ak, $settings, $pkg);
    }
}
```

## Attribute Sets

You can organize attribute keys into sets.

```php
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Attribute\SetFactory;
use Concrete\Core\Entity\Attribute\Key\FileKey; // Use the correct entity for the category

// Get Attribute Category Service
/** @var CategoryService $service */
$service = $this->app->make(CategoryService::class);
// Get the category (e.g., 'file')
$category = $service->getByHandle('file')?->getController();
if ($category) {
    // Get Set Factory
    /** @var SetFactory $setFactory */
    $setFactory = $this->app->make(SetFactory::class);
    // Get attribute set by handle
    $set = $setFactory->getByHandle('hello_world');
    if (!$set) {
        // Create the set if it doesn't exist
        $set = $category->getSetManager()->addSet('hello_world', 'Hello World', $pkg);
    }

    $ak = $category->getAttributeKeyByHandle('number_example');
    if (!$ak) {
        $ak = new FileKey();
        $ak->setAttributeKeyHandle('number_example');
        $ak->setAttributeKeyName('Number Example');
        $category->add('number', $ak, null, $pkg);
        
        // Add the attribute key to the set
        $category->getSetManager()->addKey($set, $ak);
    }
}
```
