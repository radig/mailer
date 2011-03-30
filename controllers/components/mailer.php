<?php
/**
 * @FIXME utilizar a classe App para importar a lib externa
 */
require_once('plugins' . DS . 'mailer' . DS . 'vendors' . DS . 'swiftmailer' . DS . 'lib' . DS . 'swift_required.php');

class MailerComponent extends Object
{
	protected $controller = null;
	
	protected $sender = null;
	
	protected $message = null;
	
	public $failures = array();

	/**
	 * Array com opções de configuração para o Component, possuindo os seguintes índices:
	 * 
	 * - transport: php {php|sendmail|smtp}
	 * - smtp: array {configuração do SMTP, caso seja o transport utilizado}
	 *   - port: 25 {defina a porta usada para conexão SMTP}
	 *   - host: localhost {define o host do servidor SMTP}
	 *   - encryptation: false {false|tls|ssl}
	 * - sendmail: array {configuração do Sendmail, caso seja o transport utilizado}
	 *   - path: /usr/sbin/sendmail {local com o binário do sendmail}
	 *   - params: '' {parâmetros que serão passados ao sendmail}
	 * - batch: true {true|false}
	 * - contentType: html {text|html}
	 * - template: default {nome da pasta com template desejado}
	 * - layout: default {nome do layout desejado}
	 * - confirmReceipt: false {true|false}, pede confirmação de leitura dos destinatários
	 *
	 * @var array
	 */
	protected $options = array(
		'transport' => 'php',
		'smtp' => array(
			'port' => 25,
			'host' => 'localhost',
			'encryption' => false
		),
		'sendmail' => array(
			'path' => '/usr/sbin/sendmail',
			'params' => ''
		),
		'batch' => true,
		'contentType' => 'html',
		'template' => 'default',
		'layout' => 'default',
		'confirmReceipt' => false
	);
	
	// executado antes de Controller::beforeFilter()
	function initialize(&$controller, $settings = array())
	{
		// salva referência do controlador para uso futuro
		$this->controller =& $controller;
		
		$this->options = Set::merge($this->options, $settings);
		
		if($this->options['layout'])
			$this->layout = $this->options['layout'];
			
		if($this->options['template'])
			$this->template = $this->options['template'];
			
		$this->message = Swift_Message::newInstance();
	}

	/************** Begin callbacks section ***************/

	// executado após Controller::beforeFilter()
	function startup(&$controller) {}

	// executado antes de Controller::beforeRender()
	function beforeRender(&$controller) {}

	// executado após Controller::render()
	function shutdown(&$controller)	{}

	// executado antes de Controller::redirect()
	function beforeRedirect(&$controller, $url, $status=null, $exit=true) {}
	
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
		if(empty($options))
		{
			trigger_error(__('$options não pode estar vazio', TRUE), E_USER_ERROR);
			
			return FALSE;
		}
		
		if(!$this->__configureTransport())
		{
			trigger_error(__('Falha na configuração do transporte', TRUE), E_USER_WARNING);
			return FALSE;
		}
		
		if(!$this->__setMessageOptions($options))
		{
			trigger_error(__('Falha na definição da mensagem', TRUE), E_USER_WARNING);
			return FALSE;
		}
		
		if($this->options['batch'])
			return $this->sender->batchSend($this->message, $this->failures);
		else
			return $this->sender->send($this->message, $this->failures);
	}
	/**************** End utils funcions ****************/
	
	
	/****************** Begin setters *******************/

	/**
	 *
	 * @param string $value
	 * @return bool
	 */
	public function setMessageSubject($value)
	{
		if($this->message === null)
		{
			trigger_error(__('Não é possível setar uma propriedade antes de criar uma mensagem', TRUE), E_USER_ERROR);
			
			return FALSE;
		}
		
		$this->message->setSubject($value);
		
		return TRUE;
	}

	/**
	 *
	 * @param string $value
	 * @return bool
	 */
	public function setMessageBody($value)
	{
		if($this->message === null)
		{
			trigger_error(__('Não é possível setar uma propriedade antes de criar uma mensagem', TRUE), E_USER_ERROR);
			
			return FALSE;
		}
		
		if($this->options['contentType'] == 'html')
		{
			$this->message->setBody($this->__render($value), 'text/html');
		}
		else
		{
			$this->message->setBody($this->__render($value), 'text/plain');
		}
		
		return TRUE;
	}

	/**
	 *
	 * @param string $value
	 * @return bool
	 */
	public function setMessagePart($value)
	{
		if($this->message === null)
		{
			trigger_error(__('Não é possível setar uma propriedade antes de criar uma mensagem', TRUE), E_USER_ERROR);
			
			return FALSE;
		}
		
		$this->message->setPart($value);
		
		return TRUE;
	}
	/******************* End setters *********************/


	/*************** Begin internal utils ****************/

	/**
	 *
	 * @return bool
	 */
	private function __initMessage()
	{
		if($this->message === null)
		{
			$this->message = Swift_Message::newInstance();
			
			return ($this->message != null);
		}
		
		return TRUE;
	}
	
	/**
	 * Define todas as opções fornecidas como propriedades da mensagem
	 * Se uma propriedade é Obrigatório, mas não está definida em $options, o método
	 * aborta e retorna FALSE, caso contrário, retorna TRUE
	 * 
	 * @param array $options - REQUIRED
	 * @return bool
	 */
	private function __setMessageOptions($options = array())
	{
		$status = TRUE;
		
		if(!$this->__initMessage())
		{	
			trigger_error(__('Não é possível setar uma propriedade antes de criar uma mensagem', TRUE), E_USER_ERROR);
			
			return FALSE;
		}
		
		// define destinatário do email
		if(isset($options['to']))
		{
			$this->message->setTo($options['to']);
		}
		else
		{
			trigger_error(__('É preciso definir o destinatário da mensagem', TRUE), E_USER_ERROR);
			
			return FALSE;
		}
		
		// define origem do email
		if(isset($options['from']))
		{
			$this->message->setFrom($options['from']);

			// define se será solicitado um email confirmando leitura
			if($this->options['confirmReceipt'])
			{
				$this->message->setReadReceiptTo($options['from']);
			}
		}
		else
		{
			trigger_error(__('É preciso definir o remetente da mensagem', TRUE), E_USER_ERROR);
			
			return FALSE;
		}
		
		// define email's que receberam cópia-carbono
		if(isset($options['cc']))
		{
			$status = ($status && $this->message->setCc($options['cc']));
		}
		
		// define email's que receberam cópia-carbono oculta
		if(isset($options['bcc']))
		{
			$status = ($status && $this->message->setBcc($options['bcc']));
		}
		
		// define o assunto
		if(isset($options['subject']))
		{
			$status = ($status && $this->setMessageSubject($options['subject']));
		}
		
		// define conteúdo da mensagem
		if(isset($options['body']))
		{
			$status = ($status && $this->setMessageBody($options['body']));
		}
		
		// define tipo do conteúdo da mensagem
		switch($this->options['contentType'])
		{
			case 'html':
				$this->message->setContentType('text/html');
				break;
			case 'text':
				$this->message->setContentType('text/plain');
				break;
		}

		// adiciona anexos a mensagem, se houver algum
		if(is_array($options['attachments']) && !empty($options['attachments']))
		{
			$status = ($status && $this->__attachFiles($options['attachments']));
		}
		
		return $status;
	}

	/**
	 *
	 * @param array $attachments
	 */
	private function __attachFiles($attachments = array())
	{
		foreach($attachments as $attach)
		{
			if(isset($attach['path']) && isset($attach['type']))
			{
				$this->message->attach(Swift_Attachment::fromPath($attach['path'], $attach['type']));
			}
			else
			{
				trigger_error(__('Algum anexo foi passado incorretamente.', true), E_USER_ERROR);
				
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Recupera parâmetros relacionados ao transporte em Mailer::options
	 * e inicia a class Swift_Transport
	 * 
	 * @return bool
	 */
	private function __configureTransport()
	{
		if($this->options['transport'] == 'smtp')
		{
			$transport =
				Swift_SmtpTransport::newInstance($this->options['smtp']['host'], $this->options['smtp']['port']);
				
			if(isset($this->options['smtp']['username']))
				$transport->setUsername($this->options['smtp']['username']);
				
			if(isset($this->options['smtp']['password']))
				$transport->setPassword($this->options['smtp']['password']);
				
			if($this->options['smtp']['encryption'])
				$transport->setEncryption($this->options['smtp']['encryption']);
		}
		else if($this->options['transport'] == 'sendmail')
		{
			$transport =
				Swift_SendmailTransport::newInstance($this->options['sendmail']['path'] . $this->options['sendmail']['params']);
		}
		else if($this->options['transport'] == 'php')
		{
			$transport = Swift_MailTransport::newInstance();
		}
		else
		{
			trigger_error(__('Camada de transporte inválida', TRUE), E_USER_ERROR);
			return FALSE;
		}
		
		// Define a sender based on transport
		$this->sender = new Swift_Mailer($transport);
		
		return TRUE;
	}
	
	/**
	 * Render the contents using the current layout and template.
	 * Based on EmailComponent, part of CakePHP Framework
	 * 
	 * @copyright EmailComponent: CakePHP Foundation
	 * @link http://cakephp.org
	 * @subpackage cake.libs.controllers.components.email
	 * @license MIT
	 * 
	 * @param string $content Content to render
	 * @return array Email ready to be sent
	 * @access private
	 */
	private function __render($content)
	{
		$body = '';
		
		$viewClass = $this->controller->view;
		
		if ($viewClass != 'View')
		{
			list($plugin, $viewClass) = pluginSplit($viewClass);
			$viewClass = $viewClass . 'View';
			App::import('View', $this->controller->view);
		}

		$View = new $viewClass($this->controller, true);
		$View->layout = $this->layout;
		
		if (is_array($content))
		{
			$content = implode("\n", $content) . "\n";
		}
				
		if ($this->options['contentType'] === 'html') 
		{
			$View->layoutPath = 'email' . DS . 'html';
			
			$body = $View->element('email' . DS . 'html' . DS . $this->template, array('content' => $content), true);
			
			$body = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($body));
		}
		else if ($this->options['contentType'] === 'text')
		{
			$View->layoutPath = 'email' . DS . 'text';
			
			$body = $View->element('email' . DS . 'text' . DS . $this->template, array('content' => $content), true);

			$body = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content));
		}
		
		return $body;
	}
}
?>