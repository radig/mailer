<?php
require_once(APP . 'Plugin' . DS . 'Mailer' . DS . 'Vendor' . DS . 'swiftmailer' . DS . 'lib' . DS . 'swift_required.php');

App::uses('View', 'View');
/**
 * Biblioteca que funciona como um Wrapper para uso
 * do SwiftMailer junto ao CakePHP.
 *
 * Suporta versão 2.x do CakePHP
 *
 * @copyright  Radig Soluções em Ti (http://radig.com.br)
 * @license  MIT License
 */
class Mailer extends Object
{
	/**
	 * Instância da classe SwiftMailer
	 *
	 * @var Swift_Mailer
	 */
	protected $sender = null;

	/**
	 * Instância da classe Swift_Message
	 *
	 * @var Swift_Message
	 */
	protected $message = null;

	/**
	 * Layout que será usado na renderização
	 * do corpo do email.
	 *
	 * @var string
	 */
	protected $layout = 'default';

	/**
	 * Template que será usado na renderização
	 * do corpo do email.
	 *
	 * @var string
	 */
	protected $template = null;

	/**
	 * Variáveis que ficaram disponíveis
	 * para a View.
	 *
	 * @var array
	 */
	protected $viewVars = array();

	/**
	 * Registro das falhas ocorridas no
	 * último envio de mensagens.
	 *
	 * @var array
	 */
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
	 * - contentType: html {text|html}
	 * - template: default {nome da pasta com template desejado}
	 * - layout: default {nome do layout desejado}
	 * - confirmReceipt: false {true|false}, pede confirmação de leitura dos destinatários
	 *
	 * @var array
	 */
	public $options = array();


	/**
	 * construtor default
	 */
	public function __construct($settings = array())
	{
		$this->setOptions($settings);
	}

	/**
	 * seta as configurações
	 *
	 * @param array $options As mesmas opções do métod Mailer::sendMessage()
	 * @return Mailer
	 */
	public function setOptions($options)
	{
		$this->options = Set::merge($this->options, $options);

		if($this->options['layout'])
			$this->layout = $this->options['layout'];

		if($this->options['template'])
			$this->template = $this->options['template'];

		return $this;
	}

	/*************** Begin utils funcions ***************/

	/**
	 * Envia uma mensagem.
	 * As opções 'to' e 'from' são obrigatórias para envio da mensagem.
	 *
	 * @param array $options índices válidos são:
	 * 	 - 'to': string ou array com endereços de email do destinatário - OBRIGATÓRIO
	 * 	 - 'from': string com email do remetente visível - OBRIGATÓRIO
	 *   - 'sender': string ou array com endereço do remetente real - OPCIONAL
	 * 	 - 'cc': string ou array com endereços de email das cópias - OPCIONAL
	 *   - 'bcc': string ou array com endereços de email das cópias ocultas - OPCIONAL
	 *   - 'subject': string com o assunto da mensagem - OPCIONAL
	 *   - 'body': string - OPCIONAL
	 *   - 'attachments': array
	 *     - 'path': string
	 *     - 'type': string
	 *
	 * @return bool
	 */
	public function sendMessage($options = array())
	{
		if(!$this->__configureTransport())
			throw new CakeException(__('Falha na configuração do transporte'));

		if(!$this->__setMessageOptions($options))
			throw new CakeException(__('Falha na definição da mensagem'));

		return  $this->sender->send($this->message, $this->failures);
	}

	/**************** End utils funcions ****************/


	/****************** Begin setters *******************/

	/**
	 * Define uma variável para uso na view
	 *
	 * @param string $one Nome da variável
	 * @param mixed $two Valor da variável
	 */
	public function set($one, $two = null)
	{
		if(is_array($one))
		{
			foreach($one as $k => $v)
				$this->viewVars[$k] = $v;

			return;
		}

		$this->viewVars[$one] = $two;

		return $this;
	}

	/**
	 * Habilita o plugin AntiFlood que permite interromper
	 * o envio de mensagens inserindo uma pausa quando
	 * for ultrapassado determinado limite.
	 *
	 * @param int $limit limite de envio em sequência
	 * @param int $pause tempo de pausa em segundos
	 */
	public function enableAntiFlood($limit = 100, $pause = 30)
	{
		$this->__configureTransport();

		$this->sender->registerPlugin(new Swift_Plugins_AntiFloodPlugin($limit, $pause));

		return $this;
	}

	/**
	 * Habilita o plugin Throttler que permite controlar
	 * a velocidade de envio das mensagens
	 *
	 * @param int $limit Limite de mensagens ou bytes
	 * @param string $type Tipo de limite, valores válidos são: 'message' e 'bytes'
	 *
	 */
	public function enableThrottler($limit = 100, $type = 'message')
	{
		$this->__configureTransport();

		if($type === 'message')
			$this->sender->registerPlugin(new Swift_Plugins_ThrottlerPlugin($limit, Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE));
		else
			$this->sender->registerPlugin(new Swift_Plugins_ThrottlerPlugin($limit, Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE));

		return $this;
	}

	/**
	 *
	 * @param string $value
	 * @return bool
	 */
	public function setMessageSubject($value)
	{
		$this->__initMessage();

		$this->message->setSubject($value);

		return $this;
	}

	/**
	 *
	 * @param string $value
	 * @return bool
	 */
	public function setMessageBody($value, $replacements = array())
	{
		$this->__initMessage();

		$this->__configureTransport();

		$decorator = new Swift_Plugins_DecoratorPlugin($replacements);

		$this->sender->registerPlugin($decorator);

		if($this->options['contentType'] == 'html')
			$this->message->setBody($this->__render($value), 'text/html');
		else
			$this->message->setBody($this->__render($value), 'text/plain');

		return $this;
	}

	/**
	 *
	 * @param string $value
	 * @return bool
	 */
	public function setMessagePart($value)
	{
		if($this->message === null)
			$this->__initMessage();
		
		$this->message->addPart($value);

		return $this;
	}
	/******************* End setters *********************/

	/**
	 * Retorna dados da mensagem ou transporte
	 *
	 * @param string $attr
	 * @return mixed
	 */
	public function __get($attr)
	{
		$getterName = 'get' . strtoupper($attr);

		if(is_object($this->message) && method_exists($this->message, $getterName))
			return $this->message->{$getterName}();

		if(is_object($this->sender) && method_exists($this->sender, $getterName))
			return $this->sender{$getterName}();

		if(property_exists($this, $attr))
			return $this->{$attr};

		return null;
	}


	/*************** Begin internal utils ****************/

	/**
	 * Inicializar uma Mensagem
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

		return true;
	}

	/**
	 * Define todas as opções fornecidas como propriedades da mensagem
	 * Se uma propriedade é Obrigatório, mas não está definida em $options, o método
	 * aborta e retorna false, caso contrário, retorna true
	 *
	 * @param array $options - REQUIRED
	 * @return bool
	 */
	private function __setMessageOptions($options = array())
	{
		$status = true;

		if(!$this->__initMessage())
			throw new CakeException(__('Não é possível setar uma propriedade antes de criar uma mensagem'));

		// valida configuração, dispara uma exceção em caso de erro
		$this->requiredOptions($options);

		if(isset($options['to']))
			$this->message->setTo($options['to']);

		if(isset($options['sender']))
			$this->message->setSender($options['sender']);

		if(isset($options['from']))
		{
			$this->message->setFrom($options['from']);

			if(!empty($options['confirmReceipt']))
				$this->message->setReadReceiptTo($options['from']);
		}

		if(isset($options['cc']))
			$status = ($status && $this->message->setCc($options['cc']));

		if(isset($options['bcc']))
			$status = ($status && $this->message->setBcc($options['bcc']));

		if(isset($options['subject']))
			$status = ($status && $this->setMessageSubject($options['subject']));

		if(isset($options['body']))
		{
			if(isset($options['replacements']))
				$status = ($status && $this->setMessageBody($options['body'], $options['replacements']));

			else
				$status = ($status && $this->setMessageBody($options['body']));
		}

		switch($this->options['contentType'])
		{
			case 'html':
				$this->message->setContentType('text/html');
				break;
			case 'text':
				$this->message->setContentType('text/plain');
				break;
		}

		if(!empty($options['attachments']) && is_array($options['attachments']))
			$status = ($status && $this->__attachFiles($options['attachments']));

		return $status;
	}

	/**
	 * Adiciona anexos à mensagem
	 * @param array $attachments
	 * 		path: caminho para o arquivo
	 * 		type: mime type do arquivo
	 * 		content: conteudo do arquivo (no anexo de conteúdo dinâmico)
	 * 		filename: nome do arquivo de conteúdo dinâmico que será anexado â mensagem
	 */
	private function __attachFiles($attachments = array())
	{
		foreach($attachments as $attach)
		{
			if(isset($attach['path']) && isset($attach['type']))
				$this->message->attach(Swift_Attachment::fromPath($attach['path'], $attach['type']));

			else if(isset($attach['path']))
				$this->message->attach(Swift_Attachment::fromPath($attach['path']));

			else if(isset($attach['content']) && isset($attach['type']) && isset($attach['filename']))
				$this->message->attach(Swift_Attachment::newInstance($attach['content'], $attach['filename'], $attach['type']));

			else
				throw new CakeException(__('Algum anexo foi passado incorretamente.'));
		}
		return true;
	}

	/**
	 * Recupera parâmetros relacionados ao transporte em Mailer::options
	 * e inicia a class Swift_Transport
	 *
	 * @return bool
	 */
	private function __configureTransport($override = false)
	{
		if(!$override && $this->sender !== null)
			return $this->sender;

		if($this->options['transport'] === 'smtp')
		{
			if(!empty($this->options['smtp']['encryption']))
				$transport = Swift_SmtpTransport::newInstance($this->options['smtp']['host'], $this->options['smtp']['port'], $this->options['smtp']['encryption']);

			else
				$transport = Swift_SmtpTransport::newInstance($this->options['smtp']['host'], $this->options['smtp']['port']);
			
			if(isset($this->options['smtp']['username']))
				$transport->setUsername($this->options['smtp']['username']);

			if(isset($this->options['smtp']['password']))
				$transport->setPassword($this->options['smtp']['password']);
		}
		else if($this->options['transport'] == 'sendmail')
			$transport = Swift_SendmailTransport::newInstance($this->options['sendmail']['path'] . ' ' . $this->options['sendmail']['params']);

		else if($this->options['transport'] == 'php')
			$transport = Swift_MailTransport::newInstance();

		else
			throw new CakeException(__('Camada de transporte inválida'));

		$this->sender = new Swift_Mailer($transport);

		return true;
	}

	/**
	 * Renderiza uma view baseada no template e elemento configurado.
	 * Baseado no CakeEmail, parte do Framework CakePHP.
	 *
	 * @copyright CakeEmail: CakePHP Foundation
	 * @link http://cakephp.org
	 * @subpackage cake.libs.controllers.components.email
	 * @license MIT
	 *
	 * @param string $content Conteúdo que será renderizaçãoizado
	 *
	 * @return string Email ready to be sent
	 */
	private function __render($content)
	{
		$body = '';

		$View = new View(null);

		$View->viewVars = $this->viewVars;

		list($templatePlugin, $template) = pluginSplit($this->template);
		list($layoutPlugin, $layout) = pluginSplit($this->layout);
		
		if ($templatePlugin)
			$View->plugin = $templatePlugin;

		elseif ($layoutPlugin)
			$View->plugin = $layoutPlugin;

		if (is_array($content))
			$content = implode("\n", $content) . "\n";

		$View->set('content', $content);
		$View->hasRendered = false;
		$View->viewPath = $View->layoutPath = 'Emails' . DS . $this->options['contentType'];

		$render = $View->render($template, $layout);

		$body = str_replace(array("\r\n", "\r"), "\n", $render);
		
		if($this->options['contentType'] === 'html')
			$body = $this->__embedImages($body);

		return $body;
	}

	/**
	 * Recebe uma string com o conteúdo da mensagem e retorna
	 * a string com as imagens encontradas por equivalentes na forma
	 * de 'anexo' inline
	 *
	 * @param string $content
	 *
	 * @return string $content
	 */
	private function __embedImages($content)
	{
		$dom = new DOMDocument();

		$dom->loadHtml($content);

		$imgs = $dom->getElementsByTagName('img');
		
		foreach($imgs as $img)
		{
			$src = Swift_Image::fromPath($img->getAttribute('src'));
			
			$img->setAttribute('src', $this->message->embed($src));
		}

		$content = $dom->saveHTML();

		return $content;
	}

	/**
	 * Valida as configurações obrigatórias, disparando
	 * uma exceção quando alguma não é válida.
	 *
	 * @param array $options Configurações que serão validadas
	 * @throws CakeException
	 */
	private function requiredOptions($options)
	{
		if(!isset($options['from']) && !isset($options['sender']))
		{
			$sender = $this->message->getSender() ?: $this->message->getFrom();

			if(is_object($this->message) && empty($sender))
				throw new CakeException(__('É preciso definir o remetente da mensagem'));
		}

		if(!isset($options['to']))
		{
			$to = $this->message->getTo();
			if(is_object($this->message) && empty($to))
				throw new CakeException(__('É preciso definir o destinatário da mensagem'));
		}
	}
}
