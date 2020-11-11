# bSecure Universal Checkout

## 1. Documentation

- Visit our Site https://www.bsecure.pk/ to read the documentation and get support.


## 2. How to install

### ✓ Install via composer (recommend)

Run the following command in Magento 2 root folder:

```
composer require bsecure/universal-checkout
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

Run compile if your store in Product mode:

```
php bin/magento setup:di:compile
```