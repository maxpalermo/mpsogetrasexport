<?php
/**
 * 2017 mpSOFT
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
 *  @author    mpSOFT <info@mpsoft.it>
 *  @copyright 2017 mpSOFT Massimiliano Palermo
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of mpSOFT
 */

if (!class_exists('PHPExcel')) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ".."
        . DIRECTORY_SEPARATOR . ".."
        . DIRECTORY_SEPARATOR . "classes"
        . DIRECTORY_SEPARATOR . "PHPExcel.php";
}

class AdminMpSogetrasExportController extends ModuleAdminController 
{
    private $selStateOrderOptions;
    private $selDeliverTypeOptions;
    private $debug;
    private $messages;
    private $_lang;
    private $excel_filename;
    
    public function __construct()
    {
            $this->bootstrap = true;
            $this->context = Context::getContext();
            $this->name = 'mpsogetrasexport';
            $this->displayName = 'MP Sogetras Export';

            parent::__construct();

            $this->debug=true;
            $this->messages = [];
            $this->smarty = Context::getContext()->smarty;
            $this->_lang = Context::getContext()->language->id;
            $this->excel_filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' 
                .DIRECTORY_SEPARATOR . '..'
                .DIRECTORY_SEPARATOR . 'export.xlt';
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

    public function initContent() 
    {    
        parent::initContent();
        //Get Database connection
        $db = Db::getInstance();
        //Export flag
        $export = FALSE;
        //Get state list and fill $options array
        $this->selStateOrderOptions = $this->getOrderStateList();
        $this->selDeliverTypeOptions = $this->getDeliveryOptionList();
        

        //Get fields
        if (Tools::isSubmit('submitBulkexport')) {                
                $exportXML = $this->createTableList($this->processBulkExport());
                $this->createXML($exportXML);
        } elseif (Tools::isSubmit('submit_form')) {
            $export=TRUE;
            $startDate  = Tools::getValue("startDate",'');
            $endDate    = Tools::getValue("endDate",'');
            $orderState = Tools::getValue("selOrderState",0);
            //Set query
            $queryExport = new DbQueryCore();
            $queryExport
                    ->select("id_order")
                    ->from("orders")
                    ->where("current_state = $orderState");
            if(!empty($startDate)) {
                $queryExport->where("date_add >= '$startDate'");
            }
            if(!empty($endDate)) {
                $endDate = date("Y-m-d", strtotime($endDate. ' + 1 day'));
                $queryExport->where("date_add < '$endDate'");
            }
            
            $resultQuery = $db->executeS($queryExport);
            $this->messages[]['initContent'] = [
            'on' => true,
            'call' => debug_backtrace()[1]['function'],
            'submit_form' => true,
            'list' => count($resultQuery) . ' elements',
            'query' => (string)$queryExport
        ];
            //Create TableList
            //Create list
            $list  = $this->createTableList($resultQuery);
        } else {
            //Empty list
            $list = [];
        }
        
        
        $form = $this->createForm();
        if (!empty($list)) {
            $table = $this->createTable($list); 
        } else {
            $table = "";
        }

        $smarty = $this->context->smarty;
        $smarty->assign(['export' => $export]);
        $content = $form . $table; // . $smarty->fetch(_PS_MODULE_DIR_ . 'mpsogetrasexport/views/templates/admin/export_page.tpl');
        
        $this->debug_messages();
        
        $this->context->smarty->assign(array('content'=>$content . $this->messages));
    } 
        
    public function renderHelperForm($lang_id,$export,$orderState,$startDate,$endDate,$deliver)
    {
        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [      
                'title' => $this->l('MP Sogetras Export'),       
                'image' => '../modules/mpsogetrasexport/logo.png'   
            ],   
            'input' => [
                [
                    'type' => 'select',                             
                    'label' => $this->l('Select an order state:'), 
                    'desc' => $this->l('Choose an order state to export'), 
                    'name' => 'selOrderState', 
                    'required' => true, 
                    'options' => [
                        'query' => $this->selStateOrderOptions, 
                        'id' => 'id', 
                        'name' => 'value'  
                    ]
                ],
                [
                    'type' => 'date',
                    'label' => $this->l('Start date:'),
                    'desc' => $this->l('Choose start date to begin export'),
                    'name' => 'startDate',
                    'required' => true,                        
                    ],
                [
                    'type' => 'date',
                    'label' => $this->l('End date:'),
                    'desc' => $this->l('Choose end date to begin export'),
                    'name' => 'endDate',
                    'required' => true,                        
                    ],
                [
                    'type' => 'select',
                    'label' => $this->l('Deliver type:'), 
                    'desc' => $this->l('Choose an deliver type to export'), 
                    'name' => 'selDeliverType', 
                    'required' => true,
                    'options' => [
                        'query' => $this->selDeliverTypeOptions, 
                        'id' => 'id', 
                        'name' => 'value' 
                    ]
                ],
            ],
            'submit' => array(
                'title' => $this->l('GO'),       
                'class' => 'btn btn-default pull-right',
                'name'  => 'submit_form',
                'icon'  => 'icon-mail-forward'
            )
        ];
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
                'title' => $this->l('Name'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'dest_addr' => [
                'title' => $this->l('Address'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'dest_postcode' => [
                'title' => $this->l('Postcode'),
                'width' => 80,
                'type' => 'text',
            ],
            'dest_city' => [
                'title' => $this->l('City'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'dest_state' => [
                'title' => $this->l('State'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'send_name' => [
                'title' => $this->l('Name'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_addr' => [
                'title' => $this->l('Address'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_postcode' => [
                'title' => $this->l('Postcode'),
                'width' => 80,
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_city' => [
                'title' => $this->l('City'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_state' => [
                'title' => $this->l('State'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_code' => [
                'title' => $this->l('Code'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'ship_type' => [
                'title' => $this->l('Type'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_weight' => [
                'title' => $this->l('Weight'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_package' => [
                'title' => $this->l('Package'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_cash' => [
                'title' => $this->l('Cash'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_id_pack' => [
                'title' => $this->l('Id Pack'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_send' => [
                'title' => $this->l('Sender'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_dest' => [
                'title' => $this->l('Receiver'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_note' => [
                'title' => $this->l('Notes'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_cdc' => [
                'title' => $this->l('Customer CDC'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_mail' => [
                'title' => $this->l('Customer Email'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_return' => [
                'title' => $this->l('LDV Return'),
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

    private function debug_messages()
    {
        if ($this->debug) {
            $msg_display = '';
            foreach ($this->messages as $message) {
                foreach ($message as $key=>$msg)
                {
                    if ($msg['on']) {
                        unset($msg['on']);
                        $msg_display .= 'FUNCTION: ' 
                                .$key 
                                .PHP_EOL 
                                .print_r($msg, 1)
                                .PHP_EOL
                                .'===================================================================================='
                                .PHP_EOL
                                .'===================================================================================='
                                .PHP_EOL
                                .PHP_EOL;
                    }
                }
            }
            $this->messages = "<pre>" . $msg_display . "</pre>";
        } else {
            $this->messages = '';
        }
    }
    
    /**
     * Create list for HelperList
     * @param array $resultset array of order_id
     * @return array list for Helperlist
     */
    private function createTableList($resultset)
    {
        $deliver    = Tools::getValue("selDeliverType",0);
        $package    = Configuration::get('MP_SOGETRAS_EXPORT_ID_PACKAGE');
        $weight     = Configuration::get('MP_SOGETRAS_EXPORT_ID_WEIGHT');

        $shop = new stdClass();
        $shop->name         = Configuration::get('PS_SHOP_NAME');
        $shop->address      = Configuration::get('PS_SHOP_ADDR1');
        $shop->postcode     = Configuration::get('PS_SHOP_CODE');
        $shop->city         = Configuration::get('PS_SHOP_CITY');
        $shop->state        = Configuration::get('PS_SHOP_STATE');
        $shop->id_customer  = Configuration::get('MP_SOGETRAS_EXPORT_ID_CUSTOMER');

        $tableRows = [];
        foreach($resultset as $row)
        {
            $order      = new OrderCore($row['id_order']);
            $customer   = new CustomerCore($order->id_customer);
            $address    = new AddressCore($order->id_address_delivery);

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
                $address->company = strtoupper($address->company) . ', ' . strtoupper($address->firstname . " " . $address->lastname);
            }

            $customer->company = strtoupper($customer->firstname . " " . $customer->lastname);

            if(!empty($address->other))
            {
                $address->other = "NOTE: " . $address->other;
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
            }
            else
            {
                $address->phone = "TEL: " . $address->phone;
                $address->phone_mobile = "";
            }
            $state = new StateCore($address->id_state);

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
        
        return $tableRows;
    }
    
     /**
     * 
     * @return string HTML Table
     */
    private function createTable($tableRows)
    {
        $token = Tools::getAdminTokenLite('AdminMpSogetrasExport');
        $fields_list = [
            'dest_name' => [
                'title' => $this->l('Name'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'dest_addr' => [
                'title' => $this->l('Address'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'dest_postcode' => [
                'title' => $this->l('Postcode'),
                'width' => 80,
                'type' => 'text',
            ],
            'dest_city' => [
                'title' => $this->l('City'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'dest_state' => [
                'title' => $this->l('State'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'send_name' => [
                'title' => $this->l('Name'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_addr' => [
                'title' => $this->l('Address'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_postcode' => [
                'title' => $this->l('Postcode'),
                'width' => 80,
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_city' => [
                'title' => $this->l('City'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_state' => [
                'title' => $this->l('State'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'send_code' => [
                'title' => $this->l('Code'),
                'width' => 'auto',
                'type' => 'text',
                'class' => 'hidden'
            ],
            'ship_type' => [
                'title' => $this->l('Type'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_weight' => [
                'title' => $this->l('Weight'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_package' => [
                'title' => $this->l('Package'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_cash' => [
                'title' => $this->l('Cash'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_id_pack' => [
                'title' => $this->l('Id Pack'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_send' => [
                'title' => $this->l('Sender'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_dest' => [
                'title' => $this->l('Receiver'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_note' => [
                'title' => $this->l('Notes'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_cdc' => [
                'title' => $this->l('Customer CDC'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_mail' => [
                'title' => $this->l('Customer Email'),
                'width' => 'auto',
                'type' => 'text',
            ],
            'ship_return' => [
                'title' => $this->l('LDV Return'),
                'width' => 'auto',
                'type' => 'text',
            ],
        ];
        
        $helper = new HelperListCore();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'ship_send';
        $helper->title = $this->l('Export list');
        $helper->table = '';
        $helper->token = $token;
        $helper->currentIndex = AdminControllerCore::$currentIndex;
        $helper->show_toolbar = true;
        $helper->no_link=true; // Row is not clicable
        $helper->actions = [];//['edit','view','delete'];
        $helper->bulk_actions = [
            'export' => [
                'text' => $this->l('Export selected'),
                'confirm' => $this->l('This action will delete selected items. Are you sure?'),
                'icon' => 'icon-upload'
            ]            
        ];
        
        $table = $helper->generateList($tableRows, $fields_list);
        
        $this->messages[]['createTable'] = [
            'on' => true,
            'call' => debug_backtrace()[1]['function'],
            'list' => count($tableRows) . ' elements'
        ];
        
        $this->smarty->assign(['currentindex' => $helper->currentIndex]);
        $this->smarty->assign(['token' => '&token=' . $helper->token]);
        $this->smarty->assign(['pagelink' => $helper->currentIndex . '&token=' . $helper->token . '&page=']);
        
        return $table;
    }
    
    /**
     * 
     * @return string HTML Form
     */
    public function createForm()
    {   
        $token = Tools::getAdminTokenLite('AdminMpSogetrasExport');
        $fields_form = [];
        $fields_form[0]['form'] = [
            'legend' => [      
                'title' => $this->l('MP Sogetras Export'),       
                'image' => '../modules/mpsogetrasexport/logo.png'   
            ],   
            'input' => [
                [
                    'type' => 'select',                             
                    'label' => $this->l('Select an order state:'), 
                    'desc' => $this->l('Choose an order state to export'), 
                    'name' => 'selOrderState', 
                    'required' => true, 
                    'options' => [
                        'query' => $this->selStateOrderOptions, 
                        'id' => 'id', 
                        'name' => 'value'  
                    ]
                ],
                [
                    'type' => 'date',
                    'label' => $this->l('Start date:'),
                    'desc' => $this->l('Choose start date to begin export'),
                    'name' => 'startDate',
                    'required' => true,                        
                    ],
                [
                    'type' => 'date',
                    'label' => $this->l('End date:'),
                    'desc' => $this->l('Choose end date to begin export'),
                    'name' => 'endDate',
                    'required' => true,                        
                    ],
                [
                    'type' => 'select',
                    'label' => $this->l('Deliver type:'), 
                    'desc' => $this->l('Choose an deliver type to export'), 
                    'name' => 'selDeliverType', 
                    'required' => true,
                    'options' => [
                        'query' => $this->selDeliverTypeOptions, 
                        'id' => 'id', 
                        'name' => 'value' 
                    ]
                ],
            ],
            'submit' => array(
                'title' => $this->l('GO'),       
                'class' => 'btn btn-default pull-right',
                'name'  => 'submit_form',
                'icon'  => 'process-icon-mail-reply'
            )
        ];
        
        $helper = new HelperFormCore();
        $helper->default_form_language = (int) ConfigurationCore::get('PS_LANG_DEFAULT');
        $helper->table = 'mp_isacco_import';
        $helper->allow_employee_form_lang = (int) ConfigurationCore::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminMpSogetrasExport',false);
        $helper->submit_action = 'submit_form';
        $helper->token = $token;
        $helper->fields_value['selOrderState'] = Tools::getValue('selOrderState');
        $helper->fields_value['selDeliverType'] = Tools::getValue('selDeliverType');
        $helper->fields_value['startDate'] = Tools::getValue('startDate');
        $helper->fields_value['endDate'] = Tools::getValue('endDate');
        $helper->fields_value['tableExport'] = "";
        
        $this->messages[]['createForm'] = [
            'on' => true,
            'call' => debug_backtrace()[1]['function'],
            'return' => count($helper->fields_value) . ' elements',
            ];
        
        $form =  $helper->generateForm($fields_form);
        return $form;
    }
    
    private function getOrderStateList()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $query
                ->select("osl.id_order_state")
                ->select("osl.name")
                ->from("order_state_lang","osl")
                ->innerJoin("order_state","os","os.id_order_state=osl.id_order_state")
                ->where("os.deleted=0")
                ->where("osl.id_lang=" . $this->_lang)
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
      
        return $options;
    }
    
    private function getDeliveryOptionList()
    {
        //Deliver type options
        return [
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
    }
    
    private function processBulkExport()
    {
        $boxes = Tools::getValue('Box');
        $db = Db::getInstance();
        $query = new DbQueryCore();
        $query->select("id_order")
                ->from('orders')
                ->where('reference in (' . implode(",", $boxes) . ')');
        $result = $db->executeS($query);
        return $result;
    }
    
    private function createXML($list)
    {
        $header = [
            "destragsoc",
            "destindirizzo",
            "destcap",
            "destlocalita",
            "destprovincia",
            "mittragsoc",
            "mittindirizzo",
            "mittcap",
            "mittlocalita",
            "mittprovincia",
            "codicecliente",
            "tipospedizione",
            "pesokg",
            "colli",
            "contrassegno",
            "Id Plico",
            "Rif Mittente",
            "Rif Destinatario",
            "notebolletta",
            "CdcCliente",
            "DestEmail",
            "LDVReso"
        ];
        
        /**********************
        * CREATE EXCEL SHEET *
        **********************/
        $excel = new PHPExcel();
        $sheet = $excel->getSheet();
        $sheet->getStyle()->getFont()->setSize(8);
        
        $i_row = 2;
        $i_col = 0;
        
        //Set sheet column titles
        foreach($header as $title)
        {
            $sheet->setCellValueExplicitByColumnAndRow($i_col, 1, $title);
            $i_col++;
        }
        //Fill rows
        foreach($list as $row)
        {
            $i_col = 0;
            foreach($row as $col)
            {
                $sheet->setCellValueExplicitByColumnAndRow($i_col, $i_row, $col);
                $i_col++;
            }
            $i_row++;
        }
        
        $excel->removeSheetByIndex();
        $excel->addSheet($sheet);
                
        $objWriter = new PHPExcel_Writer_Excel5($excel);
        try {
            if (file_exists($this->excel_filename)) {
                unlink($this->excel_filename);
            }
            $objWriter->save($this->excel_filename); 
            chmod($this->excel_filename, 0775);
            
            header("Location: ../modules/mpsogetrasexport/download.php?file=export.xlt");
            
            $this->messages[]['Excel Writer'] = [
                'on' => true,
                'call' => debug_backtrace()[1]['function'],
                'create' => 'success',
                'filename' => $this->excel_filename,
            ];
        } catch (Exception $exc) {
            Context::getContext()->smarty->assign('download_xls','');
            $this->messages[]['Excel Writer'] = [
                'on' => true,
                'call' => debug_backtrace()[1]['function'],
                'error' => $exc->getMessage(),
                'filename' => $this->excel_filename
            ];
        }
        
    }
}