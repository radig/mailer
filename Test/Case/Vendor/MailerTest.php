<?php
//require_once(APP . 'Plugin' . DS . 'Mailer' . DS . 'Vendor' . DS . 'swiftmailer' . DS . 'lib' . DS . 'swift_required.php');

App::uses('Mailer', 'Mailer.Vendor');

class TestMailer extends Mailer{
	
	public function getSender(){
		return $this->sender;
	}

	public function getMessage(){
		return $this->message;
	}

	public function getController(){
		return $this->Controller;
	}

	public function getLayout(){
		return $this->layout;
	}

	public function getTemplate(){
		return $this->template;	
	}

}

class MailerTest extends CakeTestCase{

	public $Mailer;

/**
 * @expectedException PHPUnit_Framework_Warning
 */
	public function setUp() {
		parent::setUp();
	}

	// /**
 //     * @expectedException PHPUnit_Framework_Warning
 //     */
	public function tearDown() {
		parent::tearDown();
	}

	public function testSendMessage() {
		$settings = array(
			'transport' => 'php',
			'contentType' => 'html',
			'template' => 'default',
			'layout' => 'default',
			'confirmReceipt' => true
		);

		$this->Mailer = new TestMailer($settings);

		$this->assertEquals($settings['template'], $this->Mailer->getTemplate());
		$this->assertEquals($settings['layout'], $this->Mailer->getLayout());
	}

}