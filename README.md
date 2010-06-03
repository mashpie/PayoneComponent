cakePHP Component for Payone-API Access
=======================================
[Payone](http://www.payone.de/) is a german payment system with several product options and clearings. To use Payone you'll need to signup with them first of course. To get started try the (somewhat old) payone demos first. They seam to run on PHP < 5.3 right out of the box.

This Component started by implementing the Payone API just from scratch. Thus it doesn't feature the whole API yet. You should be able to process checkouts on creditcards for both the "Shop" and "Access" based solutions.

To process a payment you'd need to follow these simple steps:

a) Process an access based payment:
------------------------------------

1. Add creditcard data
2. Add personal data
3. Add product code
4. process payment (validation included)

b) Process a shop based payment:
--------------------------------- 

1. Add creditcard data
2. Add personal data
3. Add invoicing data
4. Add one or more article
5. process payment (validation included)

You'll find a more detailed example documented in the component itself.

Contents and setup
-------------------
I'll provide 3 files:

* components/payone.php (the component itself)
* config/payone.php (a sample configuration file)
* tests/payone.php (a unit-test file)
 
These files need to placed in the appropriate locations inside your cake project. If don't know about components yet, that's the time to [read the docs](http://book.cakephp.org/view/62/Components). The testfile, of course depends on installed simpletest, and isn't necessary for any productive use.

Be advised
----------
Read the documentation provided by Payone carefully and setup the config... test and start with a simple payment.
