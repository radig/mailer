<?php
App::uses('Component', 'Controller');
App::uses('Mailer', 'Mailer.Lib');
/**
 * Componente que constrói um Menu baseado nas permissões
 * do usuário.
 *
 * @package			radig.Menu.Controller.Component
 * @copyright		Radig Soluções em TI
 * @author			Radig Dev Team - suporte@radig.com.br
 * @version			2.0
 * @license			Vide arquivo LICENCA incluído no pacote
 * @link			http://radig.com.br
 */
class MailerComponent extends Component
{
	protected $Controller = null;

	protected $_Mailer = null;

	/**
	 * Construtor padrão
	 *
	 * @param ComponentCollection $collection
	 * @param array $settings
	 */
	public function __construct(ComponentCollection $collection, $settings = array())
	{
		parent::__construct($collection, $settings);

		$this->settings = $settings;
	}

	/**
	 * Inicialização do componente
	 *
	 * @param Controller $controller
	 * @param array $settings
	 */
	public function initialize(&$controller)
	{
		// salva referência do controlador para uso futuro
		$this->Controller =& $controller;

		// instância a lib de envio de mensagem
		$this->_Mailer = new Mailer($this->settings);
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