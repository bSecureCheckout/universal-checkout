## 1. Documentation

- Visit our Site https://www.bsecure.pk/ to read the documentation and get support.


## 2. How to install

### ✓ Install via composer (recommend)

Run the following command in Magento 2 root folder:

```
composer require b-secure-checkout/universal-checkout
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

Run compile if your store in Product mode:

```
php bin/magento setup:di:compile
```

## 2. FAQs

#### Q: Can I receive payment on mobile?
A: Yes. bSecure  is optimized for mobile and automatically shows all payment method if enabled

#### Q: What is maximum limit of amount that can be received per order?
A: There is no payment limitation against the order


