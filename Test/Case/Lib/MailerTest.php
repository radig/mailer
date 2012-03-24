<?php
App::uses('Mailer', 'Mailer.Lib');
class TestMailer extends Mailer
{
	public function getSender()
	{
		return $this->sender;
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function getController()
	{
		return $this->Controller;
	}

	public function getLayout()
	{
		return $this->layout;
	}

	public function getTemplate()
	{
		return $this->template;
	}

	public function getViewVars()
	{
		return $this->viewVars;
	}
}

class MailerTest extends CakeTestCase
{
	public $Mailer;

	public function setUp()
	{
		parent::setUp();

		$this->_resetMailer();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	protected function _resetMailer()
	{
		$settings = array(
			'transport' => 'php',
			'contentType' => 'html',
			'template' => 'Mailer.default',
			'layout' => 'default',
			'confirmReceipt' => true
		);

		$this->Mailer = new TestMailer($settings);
	}

	public function testTemplate()
	{
		$this->assertSame($this->Mailer->getTemplate(), 'Mailer.default');
		$this->assertSame($this->Mailer->getLayout(), 'default');

		$settings = array(
			'transport' => 'smtp',
			'contentType' => 'html',
			'template' => 'account_create',
			'layout' => 'default',
			'confirmReceipt' => true
		);

		$_Mailer = new TestMailer($settings);

		$this->assertEquals($settings['template'], $_Mailer->getTemplate());
		$this->assertEquals($settings['layout'], $_Mailer->getLayout());
	}

	public function testViewVars()
	{
		$this->assertSame($this->Mailer->getViewVars(), array());

		$this->Mailer->set(array('value' => 12345));
		$this->assertSame($this->Mailer->getViewVars(), array('value' => 12345));

		$this->Mailer->set('value', 12345);
		$this->assertSame($this->Mailer->getViewVars(), array('value' => 12345));
	}

	/**
	 *
	 * @expectedException CakeException
	 */
	public function testSendMessageWithoutOptions()
	{
		$this->Mailer->sendMessage();
	}

	public function testGetter()
	{
		$this->assertSame($this->Mailer->to, null);
		$this->assertSame($this->Mailer->from, null);
		$this->assertSame($this->Mailer->template, 'Mailer.default');
		$this->assertSame($this->Mailer->sender, null);
	}

	public function testResetMessage()
	{
		$options = array(
			'to' => 'test@example.com',
			'from' => 'test@example.org'
		);

		$this->assertSame($this->Mailer->sendMessage($options), 1);
		$this->assertSame($this->Mailer->to, array('test@example.com' => null));
		$this->assertSame($this->Mailer->from, array('test@example.org' => null));

		$this->_resetMailer();
		$this->assertSame($this->Mailer->to, null);
		$this->assertSame($this->Mailer->from, null);
	}

	/**
	 *
	 */
	public function testSendMessageBasicOptions()
	{
		$options = array(
			'to' => 'test@example.com',
			'from' => 'test@example.org'
		);

		$this->assertSame($this->Mailer->sendMessage($options), 1);

		$options = array(
			'to' => array('test@example.com' => 'Test User'),
			'from' => 'test@example.org'
		);

		$this->assertSame($this->Mailer->sendMessage($options), 1);

		$options = array(
			'to' => array(
					'test@example.com' => 'Test User',
					'test2@example.com' => 'Test User2',
				),
			'from' => 'test@example.org'
		);

		$this->assertSame($this->Mailer->sendMessage($options), 2);

		$options = array(
			'to' => 'test@example.com',
			'cc' => array(
					'test@example.com' => 'Test User',
					'test2@example.com' => 'Test User2',
				),
			'from' => 'test@example.org'
		);

		$this->_resetMailer();
		$this->assertSame($this->Mailer->sendMessage($options), 3);
	}

	public function testBasicSets()
	{
		$this->_resetMailer();
		$this->Mailer->setMessageSubject('Test subject')
					->setMessageBody('Uhull');

		$this->assertSame($this->Mailer->subject, 'Test subject');

		$options = array(
			'to' => 'test@example.com',
			'from' => 'test@example.org'
		);

		$this->assertSame($this->Mailer->sendMessage($options), 1);
	}

	/**
	 * @expectedException CakeException
	 */
	public function testOptionWithoutTo()
	{
		$this->_resetMailer();

		$options = array(
			'from' => 'test@example.org'
		);

		$this->assertSame($this->Mailer->sendMessage($options), 1);
	}

	public function testEnablePlugins()
	{
		$this->_resetMailer();

		$this->assertSame($this->Mailer->enableAntiFlood(), $this->Mailer);
		$this->assertSame($this->Mailer->enableThrottler(), $this->Mailer);

		$this->_resetMailer();

		$this->assertSame($this->Mailer->enableAntiFlood(50, 10), $this->Mailer);
		$this->assertSame($this->Mailer->enableThrottler(50, 'whatever'), $this->Mailer);
	}
}