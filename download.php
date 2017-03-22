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

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

$file = Tools::getValue('file','');
if (empty($file)) {
    $filename = '';
} else {
    $filename = $file;
}

// define error message
$err = '<p style="color:#990000">Sorry, the file you are requesting ' . $filename . ' is unavailable.</p>';

if (empty($filename)) {
	// if variable $filename is NULL or false display the message
	echo $err;
} else {
	// define the path to your download folder plus assign the file name
	$path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $filename;
	// check that file exists and is readable
	if (file_exists($path) && is_readable($path)) {
		// get the file size and send the http headers
		$size = filesize($path);
		header('Content-Type: application/octet-stream');
		header('Content-Length: '.$size);
		header('Content-Disposition: attachment; filename='.$filename);
		header('Content-Transfer-Encoding: binary');
		// open the file in binary read-only mode
		// display the error message if file can't be opened
		$file = @ fopen($path, 'rb');
		if ($file) {
			// stream the file and exit the script when complete
			fpassthru($file);
			exit;
		} else {
			echo $err;
		}
	} else {
		echo $err;
                echo "<p> file not found </p>";
	}
}
