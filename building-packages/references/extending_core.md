# Extending Core Classes

Core Concrete objects can be extended in packages. For example, a package with the handle 'spam_stopper' that installs an 'akismet' anti-spam plugin would have a class:

`Concrete\Package\SpamStopper\Antispam\AkismetController`

This class would automatically be sourced from:

`packages/spam_stopper/src/Concrete/Antispam/AkismetController`

To know which items can be extended this way, look for `overrideable_core_class()` in the core.
