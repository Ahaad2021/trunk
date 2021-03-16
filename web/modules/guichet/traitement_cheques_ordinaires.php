<?php

/**
 * [166] Traitement des chèques ordinaires (non certifiés)
 *
 * Cette opération comprends les écrans :
 * - Pco-1 : Traiter les chèques ordinaires
 * - Pco-2 : Confirmation traitement des chèques ordinaires
 *
 * @package Chèques certifiés
 *
 */

require_once 'lib/html/HTML_GEN2.php';
require_once 'lib/html/FILL_HTML_GEN2.php';
require_once 'lib/html/HTML_erreur.php';
require_once 'lib/misc/VariablesGlobales.php';
require_once 'lib/dbProcedures/agence.php';
require_once 'lib/dbProcedures/guichet.php';
require_once 'lib/dbProcedures/cheque_interne.php';
require_once 'lib/misc/divers.php';
require_once 'lib/misc/tableSys.php';

if ($global_nom_ecran == "Pco-1") {

    // Affichage de la confirmation
    $html_msg = new HTML_message("Traitement des chèques ordinaires (non certifiés)");

    $html_msg->setMessage("<br />Êtes-vous sûr de vouloir traiter des chèques ordinaires ?<br />");

    $html_msg->addCustomButton("btn_valider", _("Valider"), 'Pco-2');
    $html_msg->addCustomButton("btn_annuler", _("Annuler"), 'Tcc-1');

    $html_msg->buildHTML();

    echo $html_msg->HTML_code;

} elseif ($global_nom_ecran == "Pco-2") {

    $erreur = ChequeCertifie::processChequeCompensationOrdinaire();

    if ($erreur->errCode == NO_ERR) {

        $dbHandler->closeConnection(true);

        // Affichage de la confirmation
        $html_msg = new HTML_message("Confirmation traitement des chèques ordinaires");

        if ($erreur->param > 1) {
            $chq_msg = "chèque(s) ordinaire(s) a/ont été traité(s)";
        } elseif ($erreur->param == 1) {
            $chq_msg = "chèque ordinaire a été traité";
        } else {
            $erreur->param = "";
            $chq_msg = "Aucun chèque ordinaire traité";
        }

        $html_msg->setMessage(sprintf(" <br />%s %s !<br /> ", $erreur->param, $chq_msg));

        $html_msg->addButton("BUTTON_OK", 'Tcc-1');

        $html_msg->buildHTML();
        echo $html_msg->HTML_code;
    } else {
        $html_err = new HTML_erreur("Echec lors du traitement des chèques ordinaires.");

        $err_msg = $error[$erreur->errCode];

        $html_err->setMessage(sprintf("Erreur : %s !", $err_msg));

        $html_err->addButton("BUTTON_OK", 'Tcc-1');

        $html_err->buildHTML();
        echo $html_err->HTML_code;
    }

} else {
    signalErreur(__FILE__, __LINE__, __FUNCTION__);
    // _("L'écran $global_nom_ecran n'existe pas")
}