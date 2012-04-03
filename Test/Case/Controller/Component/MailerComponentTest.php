<?php
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('ComponentCollection', 'Controller');

App::uses('MailerComponent', 'Mailer.Controller/Component');

class EmailTestController extends Controller 
{
	public $name = 'EmailTest';
	public $uses = null;
}

class TestMailerComponent extends MailerComponent
{
	public function getMailer()
	{
		return $this->_Mailer;
	}
}

class MailerComponentTest extends CakeTestCase
{
	public $Controller;

	public function setUp() 
	{
		parent::setUp();

		$Collection = new ComponentCollection();
		$this->Mailer = new TestMailerComponent($Collection);

		$CakeRequest = new CakeRequest();
		$CakeResponse = new CakeResponse();
		$this->Controller = new EmailTestController($CakeRequest, $CakeResponse);

		$this->Mailer->initialize($this->Controller);
	}

	public function tearDown() 
	{
		parent::tearDown();

		unset($this->Controller);
		unset($this->Mailer);
	}

	public function testDefaultOptions()
	{
		$default = array(
			'transport' => 'php',
			'contentType' => 'html',
			'template' => 'Mailer.default',
			'layout' => 'default',
			'confirmReceipt' => false
		);

		$result = $this->Mailer->getMailer()->options;

		$this->assertEquals($result, $default);
	}

	public function testSetOptions()
	{
		$config = array(
			'contentType' => 'text',
			'template' => 'default',
			'confirmReceipt' => true
		);

		$expected = array(
			'transport' => 'php',
			'contentType' => 'text',
			'template' => 'default',
			'layout' => 'default',
			'confirmReceipt' => true
		);

		$this->Mailer->setOptions($config);
		$result = $this->Mailer->getMailer()->options;

		$this->assertEquals($result, $expected);
	}
}