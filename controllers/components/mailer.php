<?php
App::import('Vendor', 'Mailer.Mailer');

class MailerComponent extends Object
{
	protected $Controller = null;
	
	protected $_Mailer = null;
	
	// executado antes de Controller::beforeFilter()
	public function initialize(&$controller, $settings = array())
	{
		// salva referência do controlador para uso futuro
		$this->Controller =& $controller;
		
		// instância a lib de envio de mensagem
		$this->_Mailer = new Mailer($settings, $this->Controller);
	}
	
	/**
	 * @see Mailer.Vendor.Mailer
	 * 
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->_Mailer->setOptions($options);
	}

	/************** Begin callbacks section ***************/

	// executado após Controller::beforeFilter()
	public function startup(&$controller) {}

	// executado antes de Controller::beforeRender()
	public function beforeRender(&$controller) {}

	// executado após Controller::render()
	public function shutdown(&$controller)	{}

	// executado antes de Controller::redirect()
	public function beforeRedirect(&$controller, $url, $status=null, $exit=true) {}
	
	/************** End callbacks section ***************/
	
	
	/*************** Begin utils funcions ***************/ 
	
	/**
	 * Send one or more message
	 * 
	 * @param array $options índices válidos são:
	 * 	 - 'to': string ou array com endereços de email do destinatário - REQUIRED
	 * 	 - 'from': string com email do remetente - REQUIRED
	 * 	 - 'cc': string ou array com endereços de email das cópias - OPTIONAL
	 *   - 'bcc': string ou array com endereços de email das cópias ocultas - OPTIONAL
	 *   - 'body': string - OPTIONAL
	 *   - 'attachments': array
	 *     - 'path': string
	 *     - 'type': string
	 *  
	 * @return bool
	 */
	public function sendMessage($options = array())
	{
		return $this->_Mailer->sendMessage($options);
	}
	/**************** End utils funcions ****************/
}