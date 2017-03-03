<?php
/**
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once(_PS_TOOL_DIR_.'tcpdf/config/lang/eng.php');
require_once(_PS_TOOL_DIR_.'tcpdf/tcpdf.php');

class AdminMpSogetrasExportController extends ModuleAdminController 
{
    private $selStateOrderOptions;
    private $selDeliverTypeOptions;
    
	public function __construct()
	{
		$this->bootstrap = true;
		$this->context = Context::getContext();
                $this->name = 'mpsogetrasexport';
                $this->displayName = 'MP Sogetras Export';

		parent::__construct();
	}

	public function initToolbar()
	{
		parent::initToolbar();
		unset($this->toolbar_btn['new']);
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submit_form'))
		{
			
		}
	}

	public function setMedia()
	{
		parent::setMedia();
		//add CSS and JS
                $this->addJqueryUI('ui.datepicker');
                $this->addJS(_PS_MODULE_DIR_ . 'mpsogetrasexport/views/js/datepicker_IT.js');
	}
        
        public function initContent() 
        {    

            parent::initContent();
            //Export flag
            $export = FALSE;
            //Get lang
            $lang_id = $this->context->language->id;
            //Get state list and fill $options array
            $db = Db::getInstance();
            $query = new DbQuery();
            $query
                    ->select("osl.id_order_state")
                    ->select("osl.name")
                    ->from("order_state_lang","osl")
                    ->innerJoin("order_state","os","os.id_order_state=osl.id_order_state")
                    ->where("os.deleted=0")
                    ->where("osl.id_lang=$lang_id")
                    ->orderBy("osl.name");
            $sqlStates = $db->executeS($query);
            $options = [];
            foreach($sqlStates as $orderState)
            {
                $opt = [
                    'id'=> $orderState['id_order_state'],
                    'value'=> $orderState['name']
                ];
                $options[] = $opt;
            }
            $this->selStateOrderOptions = $options;
            
            //Deliver type options
            $this->selDeliverTypeOptions = [
                [
                    'id'=>0,
                    'value'=>$this->l('National Carrier','mpsogetrasexport')
                ],
                [
                    'id'=>1,
                    'value'=>$this->l('Urban Carrier','mpsogetrasexport')
                ],
                [
                    'id'=>2,
                    'value'=>$this->l('International Carrier','mpsogetrasexport')
                ],
                [
                    'id'=>3,
                    'value'=>$this->l('Standard service 30','mpsogetrasexport')
                ],
                
            ];
            
            //Get fields
            if (Tools::isSubmit('submit_form'))
            {
                $export=TRUE;
                $orderState = Tools::getValue("selOrderState");
                $startDate  = Tools::getValue("startDate");
                $endDate    = Tools::getValue("endDate");
                $deliver    = Tools::getValue("selDeliverType");
                $package    = Configuration::get('MP_SOGETRAS_EXPORT_ID_PACKAGE');
                $weight     = Configuration::get('MP_SOGETRAS_EXPORT_ID_WEIGHT');
                
                $shop = new stdClass();
                $shop->name         = Configuration::get('PS_SHOP_NAME');
                $shop->address      = Configuration::get('PS_SHOP_ADDR1');
                $shop->postcode     = Configuration::get('PS_SHOP_CODE');
                $shop->city         = Configuration::get('PS_SHOP_CITY');
                $shop->state        = Configuration::get('PS_SHOP_STATE');
                $shop->id_customer  = Configuration::get('MP_SOGETRAS_EXPORT_ID_CUSTOMER');
                
                /*
                print "<pre>n\n\n\n\n\n\n\n\n\n\nVALUES:\n"
                    . "orderState : $orderState\n"
                    . "startDate  : $startDate\n"
                    . "endDate  : $endDate\n"
                    . "deliver  : $deliver\n"
                    . "</pre>";
                 * 
                 */
                
                $queryExport = new DbQueryCore();
                $queryExport
                        ->select("id_order")
                        ->from("orders")
                        ->where("current_state = $orderState")
                        ->where("date_add >= STR_TO_DATE('$startDate', '%d/%m/%Y')")
                        ->where("date_add <= STR_TO_DATE('$endDate', '%d/%m/%Y')");
                //print "<pre>\n\nQUERY: \n $queryExport</pre>";
                $resultQuery = $db->executeS($queryExport);
                $tableRows = [];
                foreach($resultQuery as $row)
                {
                    $order      = new OrderCore($row['id_order']);
                    $customer   = new CustomerCore($order->id_customer);
                    $address = new AddressCore($order->id_address_delivery);
                    if(strstr($order->payment,"cash")==FALSE)
                    {
                        $order->total_discounts_tax_incl=0;
                    }
                    
                    if(empty($address->company))
                    {
                        $address->company = strtoupper($address->firstname . " " . $address->lastname);
                    }
                    else
                    {
                        $address->company = strtoupper($address->company);
                    }
                    
                    if($address->phone!=$address->phone_mobile)
                    {
                        if(!empty($address->phone))
                        {
                            $address->phone = "TEL: " . $address->phone;
                        }
                        if(!empty($address->phone_mobile))
                        {
                            $address->phone_mobile = "CELL: " . $address->phone_mobile;
                        }
                        if(!empty($address->other))
                        {
                            $address->other = "NOTE: " . $address->other;
                        }
                    }
                    else
                    {
                        $address->phone = "TEL: " . $address->phone;
                        $address->phone_mobile = "";
                    }
                    $state      = new StateCore($address->id_state);
                    
                    $tableRows [] = [
                        'dest_name' => $address->company,
                        'dest_addr' => $address->address1,
                        'dest_postcode'=> $address->postcode,
                        'dest_city' => strtoupper($address->city . " (" . $state->iso_code .")"),
                        'dest_state' => $state->name,
                        'send_name' => $shop->name,
                        'send_addr' => $shop->address,
                        'send_postcode'=> $shop->postcode,
                        'send_city' => strtoupper($shop->city),
                        'send_state' => $shop->state,
                        'send_code' => $shop->id_customer,
                        'ship_type' => $this->selDeliverTypeOptions[$deliver]['value'],
                        'ship_weight' => $weight,
                        'ship_package' => $package,
                        'ship_cash' => number_format($order->total_paid_tax_incl,2),
                        'ship_id_pack' => '',
                        'ship_send' => $order->reference,
                        'ship_dest' => $customer->company,
                        'ship_note' => $address->phone . " " . $address->phone_mobile . " " . $address->other,
                        'ship_cdc' => '',
                        'ship_mail' => strtolower($customer->email),
                        'ship_return' => '',
                    ];                    
                }
            }
            if(empty($startDate)){$startDate='';}
            if(empty($endDate)){$endDate='';}
            if(empty($deliver)){$deliver='';}
            $form = $this->renderHelperForm($lang_id,$export,$orderState,$startDate,$endDate,$deliver);
            if($export)
            {
                $list = $this->renderHelperList($lang_id,$tableRows);
            }
            else
            {
                $list = "";
            }
            
            $smarty = $this->context->smarty;
            $smarty->assign(['export' => $export]);
            $content = $form . $list . $smarty->fetch(_PS_MODULE_DIR_ . 'mpsogetrasexport/views/templates/admin/export_page.tpl');
            print_r($this->content);
            $this->context->smarty->assign(array('content' => $content));
        } 
        
        public function renderHelperForm($lang_id,$export,$orderState,$startDate,$endDate,$deliver)
        {
            $fields_form = [];
            $fields_form[0]['form'] = array(
                'legend' => array(       
                  'title' => $this->l('MP Sogetras Export','mpsogetrasexport'),       
                  'image' => '../modules/mpsogetrasexport/logo.png'   
                ),   
                'input' => array(
                    array(
                        'type' => 'select',                              // This is a <select> tag.
                        'label' => $this->l('Select an order state:','mpsogetrasexport'),         // The <label> for this <select> tag.
                        'desc' => $this->l('Choose an order state to export','mpsogetrasexport'),  // A help text, displayed right next to the <select> tag.
                        'name' => 'selOrderState',                     // The content of the 'id' attribute of the <select> tag.
                        'required' => true,                              // If set to true, this option must be set.
                        'options' => array(
                            'query' => $this->selStateOrderOptions,                           // $options contains the data itself.
                            'id' => 'id',                           // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                            'name' => 'value'                               // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                        )
                    ),
                    array(
                        'type' => 'date',
                        'label' => $this->l('Start date:','mpsogetrasexport'),
                        'desc' => $this->l('Choose start date to begin export','mpsogetrasexport'),
                        'name' => 'startDate',
                        'required' => true,                        
                        ),
                    array(
                        'type' => 'date',
                        'label' => $this->l('End date:','mpsogetrasexport'),
                        'desc' => $this->l('Choose end date to begin export','mpsogetrasexport'),
                        'name' => 'endDate',
                        'required' => true,                        
                        ),
                    array(
                        'type' => 'select',                              // This is a <select> tag.
                        'label' => $this->l('Deliver type:','mpsogetrasexport'),         // The <label> for this <select> tag.
                        'desc' => $this->l('Choose an deliver type to export','mpsogetrasexport'),  // A help text, displayed right next to the <select> tag.
                        'name' => 'selDeliverType',                     // The content of the 'id' attribute of the <select> tag.
                        'required' => true,                              // If set to true, this option must be set.
                        'options' => array(
                            'query' => $this->selDeliverTypeOptions,                           // $options contains the data itself.
                            'id' => 'id',                           // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                            'name' => 'value'                               // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('GO','mpsogetrasexport'),       
                    'class' => 'btn btn-default pull-right',
                    'name'  => 'submit_form',
                    'icon'  => 'icon-mail-forward'
                )
            );
            $helper = new HelperForm();

            // Module, token and currentIndex
            $helper->module = $this;
            $helper->name_controller = $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminMpSogetrasExport');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

            // Language
            $helper->default_form_language = $lang_id;
            $helper->allow_employee_form_lang = $lang_id;

            // Title and toolbar
            $helper->title = $this->displayName;
            $helper->show_toolbar = true;        // false -> remove toolbar
            $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
            $helper->submit_action = 'submit_form';
            $helper->toolbar_btn = array(
                'back' => array(
                    'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
            );

            // Load current value
            /*
            $helper->fields_value[MP_PRINTLABELS_WIDTH] = Configuration::get(MP_PRINTLABELS_WIDTH);
            $helper->fields_value[MP_PRINTLABELS_HEIGHT] = Configuration::get(MP_PRINTLABELS_HEIGHT);
            $helper->fields_value[MP_PRINTLABELS_LOGO] = Configuration::get(MP_PRINTLABELS_LOGO);
            $helper->fields_value[MP_PRINTLABELS_PHONE] = Configuration::get(MP_PRINTLABELS_PHONE);
             * 
             */
            $helper->fields_value['selOrderState'] = $orderState;
            $helper->fields_value['selDeliverType'] = $deliver;
            $helper->fields_value['startDate'] = $startDate;
            $helper->fields_value['endDate'] = $endDate;
            $helper->fields_value['tableExport'] = "";
            
            $html = $helper->generateForm($fields_form);
            
            return  $html;
        }
        
        private function renderHelperList($lang_id,$list)
        {
            $fields_list = array(
                'dest_name' => [
                    'title' => $this->l('Name', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'dest_addr' => [
                    'title' => $this->l('Address', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'dest_postcode' => [
                    'title' => $this->l('Postcode', 'mpsogetrasexport'),
                    'width' => 80,
                    'type' => 'text',
                ],
                'dest_city' => [
                    'title' => $this->l('City', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'dest_state' => [
                    'title' => $this->l('State', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'send_name' => [
                    'title' => $this->l('Name', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                    'class' => 'hidden'
                ],
                'send_addr' => [
                    'title' => $this->l('Address', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                    'class' => 'hidden'
                ],
                'send_postcode' => [
                    'title' => $this->l('Postcode', 'mpsogetrasexport'),
                    'width' => 80,
                    'type' => 'text',
                    'class' => 'hidden'
                ],
                'send_city' => [
                    'title' => $this->l('City', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                    'class' => 'hidden'
                ],
                'send_state' => [
                    'title' => $this->l('State', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                    'class' => 'hidden'
                ],
                'send_code' => [
                    'title' => $this->l('Code', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                    'class' => 'hidden'
                ],
                'ship_type' => [
                    'title' => $this->l('Type', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_weight' => [
                    'title' => $this->l('Weight', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_package' => [
                    'title' => $this->l('Package', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_cash' => [
                    'title' => $this->l('Cash', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_id_pack' => [
                    'title' => $this->l('Id Pack', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_send' => [
                    'title' => $this->l('Sender', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_dest' => [
                    'title' => $this->l('Receiver', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_note' => [
                    'title' => $this->l('Notes', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_cdc' => [
                    'title' => $this->l('Customer CDC', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_mail' => [
                    'title' => $this->l('Customer Email', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
                'ship_return' => [
                    'title' => $this->l('LDV Return', 'mpsogetrasexport'),
                    'width' => 'auto',
                    'type' => 'text',
                ],
            );
            $helper = new HelperListCore();

            $helper->table_id = 'export';
            $helper->shopLinkType = '';

            $helper->simple_header = true;

            // Actions to be displayed in the "Actions" column
            $helper->actions = [];//array('edit', 'delete', 'view');

            $helper->identifier = ''; //'id_category';
            $helper->show_toolbar = true;
            $helper->title = $this->l('Export List', 'mpsogetrasexport');
            $helper->table = ''; // $this->name.'_categories';

            $helper->token = Tools::getAdminTokenLite('AdminMpSogetrasExport');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
                     
            $html = $helper->generateList($list, $fields_list);
            
            return $html;
        }
}
