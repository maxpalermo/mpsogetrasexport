<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('_PS_VERSION_')){exit;}
 
class MpSogetrasExport extends Module
{
  public function __construct()
  {
    $this->name = 'mpsogetrasexport';
    $this->tab = 'administration';
    $this->version = '1.0.0';
    $this->author = 'mpsoft';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
    $this->bootstrap = true;
 
    parent::__construct();
 
    $this->displayName = $this->l('Order Export for carrier Sogetras');
    $this->description = $this->l('With this module, you are able to export orders details in excel format for Sogetras Web Page.');
 
    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
  }
  
  public function install()
    {
      if (Shop::isFeatureActive())
      {
        Shop::setContext(Shop::CONTEXT_ALL);
      }

      if (!parent::install() ||
        !Configuration::updateValue('MP_SOGETRAS_EXPORT_ID_CUSTOMER', '0') ||
        !Configuration::updateValue('MP_SOGETRAS_EXPORT_PACKAGE', '0') ||
        !Configuration::updateValue('MP_SOGETRAS_EXPORT_WEIGHT', '0') ||
        !$this->registerHook('displayBackOfficeHeader') ||
	!$this->installTab()
      )
      {
        return false;
      }

      return true;
    }
    
    public function uninstall()
    {
      if (!parent::uninstall() 
          || !Configuration::deleteByName('MP_SOGETRAS_EXPORT_ID_CUSTOMER') 
          || !Configuration::deleteByName('MP_SOGETRAS_EXPORT_PACKAGE') 
          || !Configuration::deleteByName('MP_SOGETRAS_EXPORT_WEIGHT') )
      {
        return false;
      }
      return true;
    }
    
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit_form'))
        {
            $id_customer    = strval(Tools::getValue('MP_SOGETRAS_EXPORT_ID_CUSTOMER'));
            $package        = strval(Tools::getValue('MP_SOGETRAS_EXPORT_ID_PACKAGE'));
            $weight         = strval(Tools::getValue('MP_SOGETRAS_EXPORT_ID_WEIGHT'));
            
            Configuration::updateValue('MP_SOGETRAS_EXPORT_ID_CUSTOMER', $id_customer);
            Configuration::updateValue('MP_SOGETRAS_EXPORT_ID_PACKAGE', $package);
            Configuration::updateValue('MP_SOGETRAS_EXPORT_ID_WEIGHT', $weight);
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        return $output.$this->displayForm();
    }
    
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form = [];
                
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Id customer','mpsogetrasexport'),
                    'name' => 'MP_SOGETRAS_EXPORT_ID_CUSTOMER',
                    'size' => 20,
                    'required' => true,
                    'class' => 'fixed-width-md align-right'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Package','mpsogetrasexport'),
                    'name' => 'MP_SOGETRAS_EXPORT_ID_PACKAGE',
                    'size' => 20,
                    'required' => true,
                    'class' => 'fixed-width-md align-right'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Weight','mpsogetrasexport'),
                    'name' => 'MP_SOGETRAS_EXPORT_ID_WEIGHT',
                    'size' => 20,
                    'required' => true,
                    'class' => 'fixed-width-md align-right'
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name'  => 'submit_form'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

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
        $helper->fields_value['MP_SOGETRAS_EXPORT_ID_CUSTOMER'] = Configuration::get('MP_SOGETRAS_EXPORT_ID_CUSTOMER');
        $helper->fields_value['MP_SOGETRAS_EXPORT_ID_PACKAGE'] = Configuration::get('MP_SOGETRAS_EXPORT_ID_PACKAGE');
        $helper->fields_value['MP_SOGETRAS_EXPORT_ID_WEIGHT'] = Configuration::get('MP_SOGETRAS_EXPORT_ID_WEIGHT');
        
        $html = $helper->generateForm($fields_form);
        
        return  $html;
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/admin.css');
    }
    
    public function installTab()
    {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminMpSogetrasExport';
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang)
            {
                    $tab->name[$lang['id_lang']] = 'MP Sogetras Export';
            }
            $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
            $tab->module = $this->name;
            return $tab->add();
    }

    public function uninstallTab()
    {
            $id_tab = (int)Tab::getIdFromClassName('AdminMpSogetrasExport');
            if ($id_tab)
            {
                    $tab = new Tab($id_tab);
                    return $tab->delete();
            }
            else
            {
                    return false;
            }
    }
}