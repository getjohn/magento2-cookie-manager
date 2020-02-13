Magento2 Cookie Manager Replacement
===================================

This is a temporary way of cleanly adding the 'SameSite' attribute to your Magento session cookies while
we wait for [this issue](https://github.com/magento/magento2/issues/26377) to be fixed.

It provides store-level setting for the SameSite attribute, defaulting to Lax.

This also allows for systems which may host the site in an iframe (eg. corporate purchasing systems like Ariba, Coupa, Oracle Supplier Network).

Please excuse any poor style, lack of unit testing, etc...

john at getjohn.co.uk

