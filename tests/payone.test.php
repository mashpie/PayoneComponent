<?php
/**
 * Just some easy unit-tests.
 *
 * @package         Payone
 * @subpackage      test
 * @version         0.2
 * @author          Created by Marcus Spiegel on 2010-02-08. Last Editor: $Author$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class PayoneTest extends CakeTestCase {
    function startCase() { 
        App::import('Component', 'Payone');
        $this->Payone = new PayoneComponent();
        $this->Payone->setTest();
    }
    
    function startTest($method) { 
        echo '<h4>...running test for ' . preg_replace('/^test/', '', $method) . '()</h4>'; 
    }
    
    function _setvalidCC(){
        $this->Payone->setCC('4111111111111111', 'V', '1112', '123');
    }
    
    function _setinvalidCC(){
        $this->Payone->setCC('4111111111111122', 'V', '1112', '123');
    }
    
    function testInitialize(){
        $this->Payone->initialize(null);
        $this->Payone->setDebug();
    }
    
    function testCreditcardcheck(){
        $this->_setvalidCC();
        $res = $this->Payone->creditcardcheck();
        $this->assertEqual($res, true, 'This should return a valid state.');
        
        $this->_setinvalidCC();
        $res = $this->Payone->creditcardcheck();
        $this->assertEqual($res, false, 'This should return an invalid state.');
    }
    
    function testAuthorization(){
        $this->_setvalidCC();
        $this->Payone->setPerson(array(
                'firstname' => 'Marcus', 
                'lastname'  => 'Spiegel', 
                'country'   => 'DE', 
            ));
        $ref = time();
        $this->Payone->setInvoice('RG-TEST-'.$ref);
        $this->Payone->addArticle('ec2', 990, 'Some Software Activation', 1, 19);
        $res = $this->Payone->authorization('TEST-'.$ref, 990, "USD", 'Lorem ipsum', 'cc');
        $this->assertEqual($res, true, 'This should return a valid state.');

        $this->Payone->setLive();
        $this->_setinvalidCC();
        $this->Payone->setPerson(array(
                'firstname' => 'Marcus', 
                'lastname'  => 'Spiegel', 
                'country'   => 'DE', 
            ));
        $ref = time();
        $this->Payone->setInvoice('RG-TEST-'.$ref);
        $this->Payone->addArticle('ec2', 990, 'Some Software Activation', 1, 19);
        $res = $this->Payone->authorization('TEST-'.$ref, 990, "USD", 'Lorem ipsum', 'cc');
        $this->assertEqual($res, false, 'This should return an invalid state.');
        $this->Payone->setTest();
    }
    
    function testSubscription(){
        $this->Payone->setTest();
        $this->Payone->setProduct(1);  
        $res = $this->Payone->createaccess();
        $this->assertEqual($res, false, 'This should return false.');
    }
    
    function testErrors(){
        $res = $this->Payone->testerrors();
        $this->assertTrue(!empty($res['errormessage']), 'Errors should be reported properly');
    }
}
?>