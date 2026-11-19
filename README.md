# Webcode_Wishlist

Wishlist without page reloads: products are added to and removed from the wishlist via AJAX and the header updates at once. After logging in, the customer is no longer sent back to a wishlist action that only accepts POST.

## How

- The *Add to Wish List* buttons (`[data-action="add-to-wishlist"]`) and the remove buttons of the wishlist sidebar post via AJAX: the button reads *Adding...*, then *Added to Wish List*, and the wishlist block is refreshed in place.
- Products already in the customer's wishlist get the class `added` on their *Add to Wish List* button on every page, from the customer's `wishlist` section data — style it as a filled heart.
- For a guest, the AJAX add/remove/update answers JSON `{"success": false, "error": "not_logged_in"}` instead of a redirect; the button gets its own text back and the guest is sent to the login page, as without AJAX. After logging in, Magento completes the add it kept in the session.
- After logging in, Magento sends the customer back to the URL they came from. When that is a wishlist action that only accepts POST (`remove`, `update`, `cart`, `allcart`, `fromcart`, `send`, `updateitemoptions`), the return was a GET and ended in a 404; the module drops such URLs and the usual login redirect applies.
- Luma-based themes (jQuery and RequireJS).

## Configuration

None.

## Installation

Webcode's free modules are installed from our own Composer repository — they are not published on Packagist.

```bash
composer config repositories.webcode composer https://repo.webcode.bg/public
composer require webcode/module-wishlist
bin/magento module:enable Webcode_Wishlist
bin/magento setup:upgrade
```

## Compatibility

Magento Open Source and Mage-OS 2.4.7–2.4.9, PHP 8.3–8.5.

## License

[OSL-3.0](LICENSE.txt) and [AFL-3.0](LICENSE_AFL.txt), like Magento itself.

## About

Made by [Webcode](https://webcode.bg/), a Magento development and support studio from Bulgaria. Module page: <https://webcode.bg/en/wishlist>.
