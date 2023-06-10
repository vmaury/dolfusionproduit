<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       fusionproduit/fusionproduitindex.php
 *	\ingroup    fusionproduit
 *	\brief      Home page of fusionproduit top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once './lib/fusionproduit.lib.php';

//global $bc, $conf, $db, $langs, $user; // sert manifestement à rien

// Load translation files required by the page
$langs->loadLangs(array("fusionproduit@fusionproduit",'products','stocks'));
// Security check
if (! $user->rights->produit->supprimer) accessforbidden();
$pidtokeep = (int)GETPOST("pidtokeep",'int');
$pidtodel = (int)GETPOST("pidtodel",'int');

/*
 * View
 */
$form = new Form($db);

$step = (int)GETPOST("step",'int');
llxHeader("", $langs->trans("FusionProduitArea"));

print load_fiche_titre($langs->trans("FusionProduitArea"), '', 'fusionproduit.png@fusionproduit');

print '<div class="fichecenter tabBar tabBarWithBottom">'; // <div class="fichethirdleft tabBar tabBarWithBottom">
print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] .'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '	<div class="refid">'. $langs->trans("stepXonY", $step + 1).'</div>';

if ($pidtokeep ==0 || $pidtodel ==0 || $step ==0 ) {
	/* * * * * * * * * * * 
	 * selection produits
	 * * * * * * * * * * */
	print '<h4>'.$langs->trans("selProdsToMerge").'</h4>';
	// cf /htdocs/core/class/html.form.class.php arround ligne 1870 
	//select_produits($selected='', $htmlname='productid', $filtertype='', $limit=20, $price_level=0, $status=1, $finished=2, $selected_input_value='', $hidelabel=0, $ajaxoptions=array(), $socid=0, $showempty='1', $forcecombo=0, $morecss='', $hidepriceinlabel=0, $warehouseStatus='', $selected_combinations = array())
	//ce qui est passé dans la sel de produit pr la création de commande
	//selected=, htmlname='idprod', , 0, 1, 1, 2, '', 1, array(), , '1', 0, 'maxwidth300', 0, '', GETPOST('combinations', 'array') 	
	print '<div>'.$langs->trans("SelProdToKeep");
	print $form->select_produits($pidtokeep, $htmlname='pidtokeep', $filtertype='', $limit=0, $price_level=0, $status=-1, $finished=2,'', 0, array(), '', '1', 0, 'nocss');
	print '</div><br/><br/><div>'.$langs->trans("SelProdToDel");
	print $form->select_produits($pidtodel, $htmlname='pidtodel', $filtertype='', $limit=0, $price_level=0, $status=-1, $finished=2,'', 0, array(), '', '1', 0, 'nocss');
	print '<br/><br/></div><div class="">'
	. '<input type="hidden" name="step" value="1">'
	. '<input type="submit" class="butActionDelete" value="'.$langs->trans("ButMerge").'"/>';
	print '</div>';
	echo '<script>console.log(select2arrayoflanguage)</script>';
	// Todo : nouvelle action pour supprimer les produits ni en vente ni en achat 
	
} elseif( $step == 1) {
	/* * * * * * * * * * * 
	 * check des produits
	 * * * * * * * * * * *

	 * TODO :
	 * - pas si produit(s) ni en vente ni en achat  /
	 */
	print '<h4>'.$langs->trans("checkProdsToMerge").'</h4>';
	if ($pidtokeep != $pidtodel) {
		$prodToKeep = new Product($db);
		$prodToKeep->fetch($pidtokeep);
		$prodToDel = new Product($db);
		$prodToDel->fetch($pidtodel);
		$fusOk = true;

		print '<input type="hidden" name="pidtokeep" value="'.$pidtokeep.'">';
		print '<input type="hidden" name="pidtodel" value="'.$pidtodel.'">';

		print  "<div class='refidno'>Ids : $pidtokeep <= $pidtodel</div>";
		print  "<div>".$langs->trans("MergeExplaination")."</div>";
		print "<div>".img_picto('','statut4.png')." ".$langs->trans("PropertiesOK")."</div>";
		print "<div>".img_picto('','statut8.png')." ".$langs->trans("PropertiesKO")."</div>";
		print "<div>".img_picto('','statut1.png')." ".$langs->trans("PropertiesBof")."</div>";
		print  "</div>";

		//print '<div class="fichehalfleft">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%"><thead>
			<tr class="liste_titre bold"><th>Prop.</th><th>'.$langs->trans("ProdToKeep").' : '.$prodToKeep->getNomUrl(1).'</th><th>Diff.'
				. '</th><th>'.$langs->trans("ProdToDel").' : '.$prodToDel->getNomUrl(1).'</th></tr><thead><tbody>';
		// description
		print '<tr><td class="titlefield">'.$langs->trans("Description").'</td><td>'.$prodToKeep->description.'</td><td>&nbsp;</td><td>'.$prodToDel->description.'</td></tr>';
		// En vente
		print '<tr><td class="titlefield">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>'.($prodToKeep->status ? $langs->trans("OnSell") : $langs->trans("NotOnSell"));
		print '<td>'.img_picto('',$prodToKeep->status == $prodToDel->status ? 'statut4.png' : 'statut8.png').'</td>';
		print '<td>'.($prodToDel->status ? $langs->trans("OnSell") : $langs->trans("NotOnSell")).'</td></tr>';
		if ($prodToKeep->status != $prodToDel->status) $fusOk = false;
		// En achat
		print '<tr><td class="titlefield">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>'.($prodToKeep->status_buy ? $langs->trans("ProductStatusOnBuy") : $langs->trans("ProductStatusNotOnBuy")).'</td>';
		print '<td>'.img_picto('',$prodToKeep->status_buy == $prodToDel->status_buy ? 'statut4.png' : 'statut8.png').'</td>';
		print '<td>'.($prodToDel->status_buy ? $langs->trans("ProductStatusOnBuy") : $langs->trans("ProductStatusNotOnBuy")).'</td></tr>';
		if ($prodToKeep->status_buy != $prodToDel->status_buy) $fusOk = false;
		// Impossible de fusionner 2 produits qui ne sont ni en achat ni en vente
		if ($prodToKeep->status_buy == 0 && $prodToKeep->status == 0 && $fusOk) {
			print '<tr><td class="titlefield">&nbsp;</td><td>'.$langs->trans("CannotMergeDisabledProducts").'</td>';
			print '<td>'.img_picto('','statut8.png').'</td><td>&nbsp;</td></tr>';
			$fusOk = false;
		}
		
		// Nature
		$statutarray = array('-1'=>'-', '1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
		if (trim($prodToKeep->finished) == '') $prodToKeep->finished = -1;
		if (trim($prodToDel->finished) == '') $prodToDel->finished = -1;
		print '<tr><td class="titlefield">'.$langs->trans("Nature").'</td><td>'.$statutarray[$prodToKeep->finished].'</td>';
		print '<td>'.img_picto('',$prodToKeep->finished == $prodToDel->finished ? 'statut4.png' : 'statut8.png').'</td>';
		print '<td>'.$statutarray[$prodToDel->finished].'</td></tr>';
		//if ($prodToKeep->finished != $prodToDel->finished) $fusOk = false;
		// UNité
		if($conf->global->PRODUCT_USE_UNITS) {
			print '<tr><td class="titlefield">'.$langs->trans('DefaultUnitToShow').'</td>';
			print '<td>'.$langs->trans($prodToKeep->getLabelOfUnit()).'</td>';
			print '<td>'.img_picto('',$prodToKeep->fk_unit == $prodToDel->fk_unit ? 'statut4.png' : 'statut8.png').'</td>';
			print '<td>'.$langs->trans($prodToDel->getLabelOfUnit()).'</td></tr>';
			//if ($prodToKeep->fk_unit != $prodToDel->fk_unit) $fusOk = false;
		}
		// PMP
		print '<tr><td class="titlefield">' . $langs->trans("AverageUnitPricePMP") . '</td>';
		print '<td>'.price($prodToKeep->pmp) . ' ' . $langs->trans("HT").'</td>';
		print '<td>'.img_picto('',($prodToKeep->pmp >  $prodToDel->pmp *3 || $prodToKeep->pmp <  $prodToDel->pmp /3 ) ? 'statut1.png' : 'statut4.png').'</td>';
		print '<td>'.price($prodToDel->pmp) . ' ' . $langs->trans("HT").'</td>';
		print '</tr>';
		//TVA
		print '<tr><td class="titlefield">' . $langs->trans("TVA") . '</td>';
		print '<td>'.$prodToKeep->tva_tx . ' %</td>';
		print '<td>'.img_picto('',$prodToKeep->tva_tx == $prodToDel->tva_tx ? 'statut4.png' : 'statut8.png').'</td>';
		print '<td>'.$prodToDel->tva_tx . ' %</td>';
		print '</tr>';
		//if ($prodToKeep->tva_tx != $prodToDel->tva_tx) $fusOk = false;
		
		print '</tbody></table></div>';
		
	} else {
		$fusOk = false;
		print  '<div class="error">'.$langs->trans("ProductsMustBeDifferent").'</div>';
	}
	print  '<input type="hidden" name="step" id="fstep" value="0">'
		. '<input type="submit" id="btCancel" class="butAction" value="'.$langs->trans("Cancel").'"/>';
	
	if ($fusOk) {
		print '<br/><br/><input type ="checkbox" value="1" name="confirmrun" class="button"> '.$langs->trans('CheckToApplyMerges').'<br/><br/>';
		print  '<input type="submit" class="butActionDelete" value="'.$langs->trans("ButMerge").'" onclick="document.getElementById(\'fstep\').value = 2; return true;"/>';
	} 
	print '</div>';

	
} elseif( $step == 2) {
	/* * * * * * * * * * * 
	 * merge des produits
	 * * * * * * * * * * */
	print '<h4>'.$langs->trans("MergingProds").'</h4>';
	
	print '<input type="hidden" name="pidtokeep" value="'.$pidtokeep.'">';
	print '<input type="hidden" name="pidtodel" value="'.$pidtodel.'">';
	
	$started = $nberror = 0;
	$errors = array();
	$txltables = $conf->global->FUSIONPRODUIT_PRODUCTTABLELIST;
	$txltables = str_replace(chr(13), chr(10), $txltables);
	$tbltables = explode(chr(10),$txltables);
	//print_r($tbltables);
	$tbBddTables = $db->DDLListTables('`'.$db->database_name.'`');
			//print_r($tbBddTables);
	foreach($tbltables as $table) {
		$table = trim($table);
		if ($table != '' && substr($table, 0, 1) != '#') {
			if ($started == 0) {
				$db->begin();
				$started = 1;
			}
			
			print $langs->trans("LineTreatmment", $table);
			$tbFieldsToReplace = explode(',',$table);
			$table = str_replace("llx_", MAIN_DB_PREFIX, trim($tbFieldsToReplace[0]));
			if (strstr($table, tblIncRazPfx)) { // si la table contient le préfixe '+=0|'
				$ModAct = 'IncRaz';
				$table = str_replace(tblIncRazPfx, '', trim($tbFieldsToReplace[0]));
				print "<br/>&nbsp;&nbsp;=&gt;&nbsp;<b>table $table mod Inc/Raz </b>: ";
			} elseif (strstr($table, tblDelOldPfx)) { // si la table contient le préfixe 'DO|'
				$ModAct = 'DelOld';
				$table = str_replace(tblDelOldPfx, '', trim($tbFieldsToReplace[0]));
				print "<br/>&nbsp;&nbsp;=&gt;&nbsp;<b>table $table mod Del Old </b>: ";
			} else {
				$ModAct = 'Updt';
				print "<br/>&nbsp;&nbsp;=&gt;&nbsp;<b>table $table mod Update </b>: ";
			}
			if (trim($tbFieldsToReplace[1]) == '') $tbFieldsToReplace[1] = 'fk_product';
			unset($tbFieldsToReplace[0]);
			
			if (in_array($table, $tbBddTables)) { // test si table existe
				$tbTbFields = $db->DDLInfoTable('`'.$table.'`');
				/*
				print_r($tbTbFields);
				 (
    [0] => Array
        (
            [0] => rowid
            [1] => int(11)
            [2] => 
            [3] => NO
            [4] => PRI
            [5] => 
            [6] => auto_increment
            [7] => select,insert,update,references
            [8] => 
        )
				 * 
    [xx] => Array
        (
            [0] => fk_product
            [1] => int(11)
            [2] => 
            [3] => NO
            [4] => MUL
            [5] => 0
            [6] => 
            [7] => select,insert,update,references
            [8] => 
        )*/
				// Test existence des champs à remplacer / updater
				$testtbFieldsToReplace = array();
				foreach ($tbTbFields as $tbF) {
					if (in_array($tbF[0], $tbFieldsToReplace)) $testtbFieldsToReplace[] = $tbF[0];
				}
				if (count($testtbFieldsToReplace) == count($tbFieldsToReplace)) {
					//print_r($tbFieldsToReplace);
					if ($ModAct == 'Updt' || $ModAct == 'DelOld') { // Maj de fk_product ancien -> nouveau ou effacement ancien
						foreach ($tbFieldsToReplace as $field) {
							if ($ModAct == 'Updt') {
								$query = "update $table set $field=$pidtokeep where $field=$pidtodel";
							} else $query = "delete from $table where $field=$pidtodel";
							print " $field -> $query";

							$res = $db->query($query);
							if ($res===false) {
								$nberror++;
								$errors[] = "$table : ".$db->lasterror();
								print "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:red'><b>Error</b> $table : KO :-( ".$db->lasterror()."</span><br/>";
							} else {
								print " OK ! (".$db->affected_rows($res)." affected rows)<br/>";
							}	
						}
					} elseif ($ModAct == 'IncRaz') { // incremente nouveau, reset ancien 
						updateTableIncRaz($table, $tbFieldsToReplace[1], $tbFieldsToReplace[2]);
					} else {
						$nberror++;
						$error = "$table : ModAct='$ModAct' inconnu";
						$errors[] = $error;
						print "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:red'><b>Error</b> $error</span><br/>";
						
					}
					print '<br/>';
				} else {
					print "<span style='color:orange'>Warning : ".str_replace("###", implode(',', $tbFieldsToReplace), $langs->trans("FieldsNotFoundInTable")).'</span><br/>';
				}
			} else {
				// $nberror ++;
				// $errors[] = str_replace("###", $table, $langs->trans("TableNotFoundInDb"));
				print "<span style='color:orange'>Warning : ".str_replace("###", $table, $langs->trans("TableNotFoundInDb")).'</span><br/>';
			}
		} // fin si pas commentaire
	} // fin boucle tables
	if ($nberror == 0) {
		$custcode = trim($conf->global->FUSIONPRODUIT_CUSTOMCODE);
		if (!empty($custcode)) {
			print "<h4>".$langs->trans('CustomCodeExec')." :</h4>";
			print '<pre>'.$conf->global->FUSIONPRODUIT_CUSTOMCODE.'</pre>';
			print "<h5>".$langs->trans('Result')." :</h5>";
			try {
				eval($custcode);
			} catch (Exception $e) {
				print "Custom Code Error ".$e->getMessage().'<br/>';
			}
//			updateTableIncRaz(MAIN_DB_PREFIX."product", "stock");
//			updateTableIncRaz(MAIN_DB_PREFIX."product_stock", "reel", "fk_product");
//			updateTableIncRaz(MAIN_DB_PREFIX."projet_taskdet", "qty_planned", "fk_product");
//			updateTableIncRaz(MAIN_DB_PREFIX."projet_taskdet", "qty_used", "fk_product");
//			updateTableIncRaz(MAIN_DB_PREFIX."projet_taskdet", "qty_deleted", "fk_product");
		} else print "<h4>".$langs->trans('NoCustomCode')."</h4>";
	}
	print '<div class="fichecenter tabBar tabBarWithBottom">';
	
	if ($nberror == 0 && GETPOST('confirmrun', 'int') == 1) {
		$db->commit();
		$prodToKeep = new Product($db);
		$prodToKeep->fetch($pidtokeep);		
		$prodToDel = new Product($db);
		$prodToDel->fetch($pidtodel);
		$prodToDel->ref = $prodToDel->ref.$langs->trans("ProductDisactCode");
		$prodToDel->label = $langs->trans("ProductDisactCode")." ".$prodToDel->label;
		$prodToDel->status = $prodToDel->status_buy = 0;
		$prodToDel->description .= "\n".$langs->trans("ProductMergeWithNote", $prodToKeep->ref." (".$prodToKeep->label.") ", date("Y-m-d"), $user->login);
		//$prodToDel->hidden INTROUVABLE
		$prodToDel->update($pidtodel, $user);
		print "<h3>".$langs->trans("ChangesCommited")."</h3>";
		print '<div><input type="hidden" name="step" value="0">'
			. '<input type="submit" class="butAction" value="'.$langs->trans("Back").'"/></div>';
	} else {
		$db->rollback();
		if (GETPOST('confirmrun', 'int') != 1) {
			print "<h3>".$langs->trans("TestModeChangesNotCommited")."</h3>";
		}
		print '<div><input type="hidden" name="step" value="1">'
			. '<input type="submit" class="butAction" value="'.$langs->trans("Back").'"/></div>';
		if ($nberror > 0) {
			print '<h3>'.$langs->trans("Errors").'</h3><pre>';
			print_r($errors);
			print "</pre>";
		}
	}
	print '</div>';
}
print '</form>';
print '<div class="fichetwothirdright"><div class="ficheaddleft">';

print '</div></div></div>';

// End of page
llxFooter();
$db->close();


/** update table field increment / rese
 * 
 * @global type $pidtodel
 * @global type $pidtokeep
 * @global type $db
 * @global type $nberror
 * @global type $errors
 * @param type $table
 * @param type $field
 * @param type $key
 */
function updateTableIncRaz($table, $field,$key='rowid') {
	global $pidtodel,$pidtokeep,$db,$nberror,$errors;
	$query = "select $field from $table where $key = $pidtodel";
	$res = $db->query($query);
	$row = $db->fetch_row($res);
	$val = $row[0] + 0;
	$query = "update $table set $field=$field + $val where $key = $pidtokeep";
	print "Update 1 $table/$field -> $query ";
	$res = $db->query($query);
	if ($res===false) {
		$nberror++;
		$errors[] = "$table : ".$db->lasterror();
		print "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:red'><b>Error</b>  $table KO :-( ".$db->lasterror().'</span><br/>';
	} else {
		print " OK ! (".$db->affected_rows($res)." affected rows)";
	}
	print '<br/>';
	$query = "update $table set $field=0 where $key = $pidtodel";
	print "Update 2 $table/$field -> $query ";
	$res = $db->query($query);
	if ($res===false) {
		$nberror++;
		$errors[] = "$table : ".$db->lasterror();
		print "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='color:red'><b>Error</b>  $table KO :-( ".$db->lasterror().'</span><br/>';
	} else {
		print " OK ! (".$db->affected_rows($res)." affected rows)";
	}
	print '<br/>';
}