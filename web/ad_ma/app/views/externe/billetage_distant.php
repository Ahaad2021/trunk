<?php

// error_reporting(E_ALL);
// ini_set("display_errors", "on");

/**
 * Billetage
 *
 * Cet écran doit recevoir en POST :<ul>
 * <li> $shortName : Le nom du champ du formulaire à partir duquel on vient
 * <li> $direction : <ul>
 * 	<li> in si l'argent entre
 * 	<li> out si l'argent sort
 * 	<li> in_cc si l'argent entre dans la caisse centrale
 * 	<li> out_cc si l'argent sort de la caisse centrale 
 * 	<li> caisse_seule si il s'agit d'un comptage de billet d'une caisse
 *      </ul>
 * <li> $devise    : La devise dans laquelle on récupère billetage
 * </ul>
 * @todo On ne reçoit plus une série de valeurs, on va directement les chercher dans le formulaire opener
 * @package Guichet
 */
require_once 'lib/html/HTML_GEN2.php';
require_once 'lib/misc/VariablesSession.php';
require_once 'lib/html/HtmlHeader.php';

// Multi agence includes
require_once 'ad_ma/app/controllers/misc/VariablesSessionRemote.php';
require_once 'ad_ma/app/models/Devise.php';
require_once 'ad_ma/app/models/Divers.php';
require_once 'ad_ma/app/models/Parametrage.php';


//var_dump($_POST);
//exit;


global $global_remote_id_agence;

Divers::setMonnaieCourante($pdo_conn, $global_remote_id_agence, $devise);
setMonnaieCourante($devise);

// Init class
$ParametrageObj = new Parametrage($pdo_conn, $global_remote_id_agence);

$valeurs = $ParametrageObj->recupeBillet($devise);

$myForm = new HTML_GEN2(sprintf(_("Billettage en '%s'"), $devise));

// Selon que l'argent entre ou sort, les libellés des deux colonnes seront différents
if ($direction == "in") {
    $libel1 = _("Reçu du client");
    $libel2 = _("Rendu au client");
} else if ($direction == "out") {
    $libel1 = _("Remis au client");
    $libel2 = _("Rendu par le client");
} else if ($direction == "in_cc") {
    $libel1 = _("Reçu de la caisse centrale");
    $libel2 = _("Rendu à la caisse centrale");
} else if ($direction == "out_cc") {
    $libel1 = _("Remis à la caisse centrale");
    $libel2 = _("Rendu par la caisse centrale");
} else if ($direction == "caisse_seule") {
    $libel1 = _("Contenu de la caisse");
    $libel2 = "***";
}
else
    signalErreur(__FILE__, __LINE__, __FUNCTION__); // "La valeur de 'Direction' n'est pas reconnue ($direction)"

    
// Construction du tableau de billettage
$xtHTML = "<TABLE align=\"center\" width=\"40%\">";
$xtHTML .= "\n\t<tr bgcolor=\"$colb_tableau\"><td></td><td><b>$libel1</b></td><td><b>$libel2</b></td></tr>";

$disable = 'disabled';  // Petite astuce pour disabler la cellule 'rendu' du billet le plus élevé
while (list($key, $value) = each($valeurs)) {

    $xtHTML .= "\n\t<tr bgcolor=\"$colb_tableau\"><td>" . Divers::afficheMontant($value) . "</td><td><INPUT type=\"text\" name=\"bil_$key\" value=\"\" size=\"5\"></td><td><INPUT type=\"text\" name=\"rend_bil_$key\" value=\"\" size=\"5\" $disable></td></tr>";
    if ($direction != "caisse_seule") // Si c'est la caisse seule, tous les champs rendu doivent être disabled
        $disable = '';
}
$xtHTML .= "\n</TABLE>";

$myForm->addHTMLExtraCode("tableau", $xtHTML);
$myForm->addFormButton(1, 1, "ok", _("OK"), TYPB_SUBMIT);
$myForm->addFormButton(1, 2, "ann", _("Annuler"), TYPB_BUTTON);
$myForm->setFormButtonProperties("ann", BUTP_JS_EVENT, array("onclick" => "window.close();"));
$validateCode = "somme = 0;";

$valeurs = $ParametrageObj->recupeBillet($devise);

while (list($key, $value) = each($valeurs)) {
    $validateCode .= "somme += document.ADForm.bil_" . $key . ".value * " . $value . "; ";
    $validateCode .= "somme -= document.ADForm.rend_bil_" . $key . ".value * " . $value . "; ";
    $validateCode .= "opener.document.ADForm." . $shortName . "_billet_" . $key . ".value = document.ADForm.bil_" . $key . ".value;";
    $validateCode .= "opener.document.ADForm." . $shortName . "_billet_rendu_" . $key . ".value = document.ADForm.rend_bil_" . $key . ".value;";
}
$validateCode .= "if (somme < 0) {alert('" . _("Le montant est négatif") . "');return false;} checkForm(); if (ADFormValid == false) return false; opener.document.ADForm." . $shortName . ".focus();opener.document.ADForm." . $shortName . ".value = formateMontant(somme);opener.document.ADForm." . $shortName . ".blur();window.close();";
$myForm->setFormButtonProperties("ok", BUTP_JS_EVENT, array("onclick" => $validateCode));
// Initialisation des champs en fonction des champs hidden de l'appelant
$checkJS = "";  // Script de vérification du formulaire
$initJS = "if (opener.document.ADForm.$shortName.value != '') {";  // Script d'initialisation des valeurs

$valeurs = $ParametrageObj->recupeBillet($devise);

while (list($key, $value) = each($valeurs)) {
    $initJS .= "document.ADForm.bil_" . $key . ".value = opener.document.ADForm." . $shortName . "_billet_" . $key . ".value;\n";
    $initJS .= "document.ADForm.rend_bil_" . $key . ".value = opener.document.ADForm." . $shortName . "_billet_rendu_" . $key . ".value;\n";

    $checkJS .= "if (!isIntPos(document.ADForm.bil_$key.value) || !isIntPos(document.ADForm.rend_bil_$key.value)) {msg += '- " . sprintf(_("Le nombre de billet de %s doit être un entier positif"), $value) . "\\n';ADFormValid=false;}\n";
}
$initJS .= "}";
$myForm->addJS(JSP_FORM, "initJS", $initJS);
$myForm->addJS(JSP_BEGIN_CHECK, "checkJS", $checkJS);

// Destroy object
unset($ParametrageObj);

$myForm->buildHTML();

echo $myForm->getHTML();
?>