########################
What is this repository?
########################

|tests| |php| |version| |downloads|

.. |tests| image:: https://github.com/pocketarc/codeigniter/actions/workflows/test-phpunit.yml/badge.svg?branch=develop
   :target: https://github.com/pocketarc/codeigniter/actions/workflows/test-phpunit.yml
   :alt: PHPUnit Tests

.. |php| image:: https://img.shields.io/badge/PHP-5.4%20--%208.5-8892BF?logo=php
   :alt: PHP 5.4 - 8.5

.. |version| image:: https://img.shields.io/packagist/v/pocketarc/codeigniter
   :target: https://packagist.org/packages/pocketarc/codeigniter
   :alt: Packagist Version

.. |downloads| image:: https://img.shields.io/packagist/dt/pocketarc/codeigniter
   :alt: Packagist Downloads

This is a fork of CodeIgniter 3, with the goal of keeping it up to date with modern PHP versions. There is no intention to add new features or change the way CI3 works. This is purely a maintenance fork.

**PHP Compatibility:**

- ✅ PHP 5.4 - 8.1 (as per original CI3 support)
- ✅ PHP 8.2
- ✅ PHP 8.3
- ✅ PHP 8.4
- ✅ PHP 8.5 (and beyond as they are released)

The original CodeIgniter 3.x branch is no longer maintained, and has not been updated to work with PHP 8.2, or any newer version. This fork is intended to fill that gap.

If the original CodeIgniter 3.x branch is updated to work with PHP 8.2+, and starts to be maintained again, this fork might be retired.

********************
Maintenance Policy
********************

This fork commits to:

- Maintaining compatibility with each new PHP version while still supporting PHP 5.4+
- Applying critical security fixes
- Keeping changes minimal to preserve CI3 behavior
- Reverting breaking changes in CodeIgniter 3.2.0-dev to maintain backward compatibility (e.g. restoring the Cart library, Email helper, and other deprecated-but-removed functionality)
- Running the full CI3 test suite on PHP 8.2+

If you find something that was removed in CI 3.2.0-dev and breaks backward compatibility for your application, please open an issue. We're happy to restore it.

This fork does NOT:

- Add new features
- Change existing CI3 behavior
- Provide commercial support
- Make migration to CI4 any harder (or easier)

****************
Issues and Pulls
****************

Issues and Pull Requests are welcome, but please note that this is a maintenance fork. New features will not be accepted. If you have a new feature you would like to see in CodeIgniter, please submit it to the original CodeIgniter 3.x branch.

*******************
Server Requirements
*******************

PHP version 5.4 or newer, same as the original CI3 requirements.

************
Installation
************

You can install this fork using Composer:

.. code-block:: bash

	composer require pocketarc/codeigniter

After installation, you need to point CodeIgniter to the new system directory. In your `index.php` file, update the `$system_path` variable:

.. code-block:: php

	$system_path = 'vendor/pocketarc/codeigniter/system';

**Alternative Installation (Manual)**

If you prefer the traditional approach of replacing the system directory:

1. Download this repository
2. Replace your existing `system/` directory with the one from this fork
3. No changes to `index.php` are needed with this method

**Note:** The Composer method makes future updates easier with `composer update`, while the manual method requires downloading and replacing the system directory each time.

**Upgrading from Original CI3**

⚠️ **Important:** This fork is based on the unreleased CodeIgniter 3.2.0-dev version, not the stable 3.1.13. If you're upgrading from CI 3.1.x, please read the upgrade guide for any changes that may affect your application.

**Please review the upgrade guide:** `upgrade_320.rst <user_guide_src/source/installation/upgrade_320.rst>`_

Note: The upgrade guide has been updated to reflect functionality we've restored for backward compatibility (Cart library, Email helper, etc.).

Steps to upgrade:

1. Review the upgrade guide for breaking changes between 3.1.x and 3.2.0
2. Install via Composer as shown above
3. Update the `$system_path` in your `index.php`
4. Apply any necessary changes from the upgrade guide to your application
5. Your existing `application/` directory remains mostly unchanged (except for items noted in the upgrade guide)
6. Test thoroughly with your PHP version (especially if using PHP 8.2+)
