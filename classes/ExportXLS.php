<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExportXLS
 *
 * @author massimiliano
 */
class ExportXLS {
    public function prepareXLSTable()
    {
        $data = $this->prepareData();
        $xls = array();
        
        $flag = false;
        $xls[] = "<table>";
        foreach($data as $row) 
        {
            if(!$flag) 
            {
                // display field/column names as first row
                $title = [
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
                    "LDVReso",
                ];
                $xls[] = "<tr>";
                foreach($title as $cell)
                {
                    $xls[] = "<td>" . $cell . "</td>";
                }
                $xls[] = "</tr>";
                $flag = true;
                
//                $xlsrow = array();
//                foreach($row as $key=>$value)
//                {    
//                    $field = $this->cleanData($key);
//                    $xls[] = "<td>" . $key . "</td>";
//                }
                //$xls[] =  implode($xlsrow) . "\n";
                
              
            }
            else
            {
                $xlsrow = array();
                $xls[] = "<tr>";
                foreach($row as $field)
                {
                    $field = $this->cleanData($field);
                    $xls[] = "<td>" . $field . "</td>";
                }
                //$xls[] =  implode($xlsrow) . "\n";
                $xls[] = "</tr>";
            }    
        }
        $xls[]="</table>";
        $filename = dirname(__FILE__) . DS . ".." . DS .  "xls" . DS .  "export_sogetras_" . date('YmdHis') . ".xls";
        $h = fopen($filename, 'a+');
        foreach($xls as $tag)
        {
            fwrite($h, $tag);
        }
        fclose($h);   
    }
    
    public function prepareData()
    {
        $db = Db::getInstance();
        $shop = $this->shop;
        $orderResult = [];
                
        if($this->go==0 || $this->tipo_exec==0){goto endtable;}
        $tables = [
            _DB_PREFIX_ . "orders ord",
            _DB_PREFIX_ . "address adr",
            _DB_PREFIX_ . "customer cli",
            _DB_PREFIX_ . "state sta",
        ];
        
        $fields = [
            "ord.id_order",
            "ord.id_lang",
            "ord.id_customer",
            "ord.id_address_delivery",
            "ord.payment",
            "ord.total_paid",
            "ord.date_add",
            "adr.id_state",
            "adr.company",
            "adr.lastname",
            "adr.firstname",
            "adr.address1",
            "adr.address2",
            "adr.postcode",
            "adr.city",
            "adr.phone",
            "adr.phone_mobile",
            "cli.email",
            "sta.name as prov",
        ];
        $where = [
            "adr.id_address = ord.id_address_delivery",
            "cli.id_customer = ord.id_customer",
            "sta.id_state = adr.id_state",
        ];
        if($this->da_data!="gg/mm/aaaa" && $this->a_data!="gg/mm/aaaa")
        {
            $a_dataObj = new DateTime($this->a_data);
            $a_dataObj->modify("+1 day");
            $this->a_data = $a_dataObj->format("Y-m-d");
            $where[] = "ord.date_add between '$this->da_data' and '$this->a_data'";
        }

        $query = "select " . implode(",", $fields) . " from " . implode(",", $tables) . " where " . implode(" and ", $where);
        $orders = $db->ExecuteS($query);
        if($orders)
        {   
            /**
                        *  PREPARE ARRAY
                        */
            foreach ($orders as $row)
            {   
                if($this->stato!=0)
                {
                    $sqlState = "select id_order_state from " . _DB_PREFIX_ . "order_history where id_order = " . $row["id_order"] . " order by date_add DESC";
                    $idState = $db->getValue($sqlState);
                    if($idState != $this->stato){continue;}
                }
                /**
                                            *  INFORMAZIONI DESTINATARIO
                                            */
                if(empty($row["company"]))
                {
                    $order["destragsoc"] = $row["lastname"] . " " . $row["firstname"];
                }
                else
                {
                    $order["destragsoc"] = $row["company"];
                }

                if(!empty($row["address2"]))
                {
                    $order["destaddress"] = $row["address1"] . " " . $row["address2"];
                }
                else
                {
                    $order["destaddress"] = $row["address1"];
                }

                $order["destcap"] = $row["postcode"];
                $order["destloc"] = $row["city"];
                $order["destprov"] = $row["prov"];

                /**
                                            *  INFORMAZIONI MITTENTE
                                            */
                $order["mittragsoc"] = $shop->name;
                $order["mittaddress"] = $shop->addr1;
                $order["mittcap"] = $shop->cap;
                $order["mittloc"] = $shop->city;
                $order["mittprov"] = $shop->prov;
                $order["mittcodcli"] = $this->codcli;

                /**
                                            *  INFORMAZIONI SPEDIZIONE
                                            */

                $order["tipospedizione"] = $this->spedizione;
                $order["pesokg"] = $this->peso;
                $order["colli"] = $this->colli;

                if(!strpos($row["payment"],"contrassegno")==FALSE)
                {
                    $order["contrassegno"] = $row["total_paid"];
                }
                else
                {
                    $order["contrassegno"] = "0";
                }

                $order["idplico"] = "";
                $order["rifmitt"] = "Ordine n. " . $row["id_order"];
                $order["rifdest"] = $row["lastname"] . " " . $row["firstname"];

                if(!empty($row["phone"]))
                {
                    $phone = "Tel: " . $row["phone"];
                }
                else
                {
                    $phone = "";
                }

                if(!empty($row["phone_mobile"]))
                {
                    $cell = "Cell: " . $row["phone_mobile"];
                }
                else
                {
                    $cell = "";
                }

                $order["note"] = $phone . " " . $cell;
                $order["cdc"] = "";
                $order["destemail"] = $row["email"];
                $order["reso"] = "";
                
                $orderResult[] = $order;
                
                $this->orders[] = ["id_order"=>$row["id_order"], "date_add"=>$row["date_add"]];
                
            } //End Parse Order ::foreach
        }
        endtable:
            return $orderResult;
    }
}
