{*
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
*}

<script type="text/javascript">
    $(document).ready(function() 
    {
        $('.datepicker').datepicker("option", "showAnim", "slideDown");
        $('.datepicker').datepicker("option", "dateFormat", "dd/mm/yy");
        $('.datepicker').datepicker("option", "showButtonPanel", true);
        $('.datepicker').datepicker("option", "changeMonth", true);
        $('.datepicker').datepicker("option", "changeYear", true);   
        
        $('#table-export thead').prepend(
                "<tr class=\'nodrag nodrop\'>" +
                "<th colspan=\'5\'>{l s='RECEIVER' mod='mpsogetrasexport'}</th>" +
                //"<th colspan=\'6\'>{l s='SENDER' mod='mpsogetrasexport'}</th>" +
                "<th colspan=\'11\'>{l s='SHIPPING' mod='mpsogetrasexport'}</th>"
                );
        
        $("#btn-export").on("click",function()
        {
            var header = $("#table-export thead tr:nth-child(2) th");
            var tblRows = $("#table-export tbody tr");
            var head = "";
            var rows = "";
            var cols = "";
            
            
            //create header
            var title = new Array(
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
                );
            var cols = "";
            for(var c=0; c<title.length; c++)
            {
                var text = String(title[c]).trim();
                cols += "<th>" + text + "</th>";
            }
            head += "<tr>" + cols + "</tr>";
            
            //create rows
            rows = new Array();
            for(var i=0; i<tblRows.length; i++)
            {
                var row = $(tblRows[i]).find("td");
                var cols = "";
                for(var c=0; c<row.length; c++)
                {
                    var text = String($(row[c]).text()).trim();
                    cols += "<td>" + text + "</td>";
                }
                rows += "<tr>" + cols + "</tr>";
            }
            
            //filename
            var filename = 'export_sogetras_' + Date.now() + ".xls";
            fnExportExcel(head,rows,filename);
        });
    });
</script>