# Remove Our-Od-Stock Items

The customer's carts can have some abandoned items that has an updated stock status from In Stock to Out-Of-Stock.
This magento 2 module lets you remove them automatically, from the customer's cart quotes.
They will be removed at once as the customers will log in to their account.

## Installation

### For developers

```bash
cd <magento_root>
composer config repositories.swissup composer https://docs.swissuplabs.com/packages/
composer require swissup/module-roofstock-items --prefer-source
bin/magento setup:upgrade
