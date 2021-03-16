<?php
/* vim: set expandtab softtabstop=2 shiftwidth=2 foldmethod=marker: */

/**
 * [340] Rapports chequiers.
 * Ces fonctions appellent les écrans suivants :
 * - Rcq-1 : Sélection du rapport à imprimer
 * - Rcq-10 : Personalisation du rapport Etat des chéquiers imprimés
 * - Rcq-11 et Rcq-12 : Impression PDF ou export CSV du rapport Etat des chequiers imprimés
 * - Rcq-20 : Personalisation du rapport Liste des commandes de chéquiers
 * - Rcq-21 et Rcq-22 : Impression PDF ou export CSV du rapport Liste des commandes de chéquiers
 * - Rcq-30 : Personalisation du rapport Liste des chéquiers envoyés à l'impression
 * - Rcq-31 et Rcq-32 : Impression PDF ou export CSV du rapport Liste des chéquiers envoyés à l'impression
 * - Rcq-40 : Personalisation du rapport Liste des chèques/chéquiers mise en opposition
 * - Rcq-41 et Rcq-42 : Impression PDF ou export CSV du rapport Liste des chèques/chéquiers misent en opposition
 *
 * @package Rapports
 **/
require_once 'lib/html/HTML_GEN2.php';
require_once 'lib/dbProcedures/rapports.php';
require_once 'modules/rapports/xml_chequiers.php';
require_once 'modules/rapports/xslt.php';
require_once 'lib/misc/tableSys.php';
require_once 'lib/misc/csv.php';
require_once 'lib/misc/excel.php';


/*{{{ Era-1 : Sélection du rapport à imprimer */
if ($global_nom_ecran == "Rcq-1")
{
    // Recherche de tous les rapports à afficher
    foreach ($adsys["adsys_rapport"] as $key => $name) {
        if (substr($key, 0, 3) == 'RCQ') {
            $rapports[$key] = _($name);
        }
    }

    $MyPage = new HTML_GEN2(_("Sélection type rapport chéquiers"));
    $MyPage->addField("type", _("Type de rapport chéquiers"), TYPC_LSB);
    $MyPage->setFieldProperties("type", FIELDP_IS_REQUIRED, true);
    $MyPage->setFieldProperties("type", FIELDP_ADD_CHOICES, $rapports);

    //Boutons
    $MyPage->addFormButton(1, 1, "valider", _("Sélectionner"), TYPB_SUBMIT);
    $MyPage->addFormButton(1, 2, "annuler", _("Annuler"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    // Tableau indiquant le prochain écran en fonction du code rapport
    $prochEc = array (
        "CCM" => "Rcq-20",
		"CEI" => "Rcq-30",
        "ECI" => "Rcq-10",
        "CMO" => "Rcq-40"
    );

    foreach ($prochEc as $code => $ecran) {
        $js .= "if (document.ADForm.HTML_GEN_LSB_type.value == 'RCQ-$code') assign('$ecran');";
    }

    $MyPage->addJS(JSP_BEGIN_CHECK, "js1", $js);

    //HTML
    $MyPage->buildHTML();
    echo $MyPage->getHTML();

}
/*{{{ Rcq-10 : Personalisation du rapport Etat des chéquiers imprimés */
elseif ($global_nom_ecran == "Rcq-10")
{
    global $adsys;

    $MyPage = new HTML_GEN2(_("Personnalisation du rapport État des chéquiers imprimés"));

    // Etats des chequiers
    $choix_etat_chequier = array();
    $choix_etat_chequier[0] = _("Tous");
    $choix_etat_chequier[1] = _($adsys["adsys_etat_chequier"][0]);
    $choix_etat_chequier[2] = _($adsys["adsys_etat_chequier"][1]);

    //Champs date début
    $MyPage->addField("date_debut", _("Date début"), TYPC_DTE);
    $MyPage->setFieldProperties("date_debut", FIELDP_DEFAULT, date("01/01/Y"));
    $MyPage->setFieldProperties("date_debut", FIELDP_IS_REQUIRED, true);
    //Champs date fin
    $MyPage->addField("date_fin", _("Date fin"), TYPC_DTE);
    $MyPage->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
    $MyPage->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);
    // Etat chequier
    $MyPage->addField("etat_chequier", _("État Chéquier"), TYPC_LSB);
    $MyPage->setFieldProperties("etat_chequier", FIELDP_ADD_CHOICES, $choix_etat_chequier);
    $MyPage->setFieldProperties("etat_chequier", FIELDP_IS_REQUIRED, false);
    $MyPage->setFieldProperties("etat_chequier", FIELDP_HAS_CHOICE_TOUS, false);
    $MyPage->setFieldProperties("etat_chequier", FIELDP_HAS_CHOICE_AUCUN, false);

    // Recherche client
    $js_chercheClient = "
            OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', '"._("Recherche")."');return false;
        ";

    $MyPage->addField("num_client", _("N° de client"), TYPC_INT);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_REQUIRED, false);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_LABEL, false);
    $MyPage->addLink("num_client", "rechercher", _("Rechercher"), "#");
    $MyPage->setLinkProperties("rechercher", LINKP_JS_EVENT, array("onclick" => $js_chercheClient));

    //Boutons
    $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Rcq-11");
    $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Rcq-12");
    $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Rcq-12");
    $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    // Code javascript pour la vérification des champs obligatoires
    $JSCheck = "
        if (document.ADForm.HTML_GEN_date_date_debut.value &&
            document.ADForm.HTML_GEN_date_date_fin.value &&
            !isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin.value)) {
                msg += '- La date de début doit être antérieure à la date de fin';
                ADFormValid = false;
        }
    ";

    $MyPage->addJS(JSP_BEGIN_CHECK, "JSCheck", $JSCheck);
    $MyPage->buildHTML();
    echo $MyPage->getHTML();
}
/*}}}*/

/*{{{ Rcq-11 et Rcq-12 : Impression PDF ou export csv Rapport etat chequiers */
elseif ($global_nom_ecran == "Rcq-11" || $global_nom_ecran == "Rcq-12")
{
    global $adsys;

    $num_client = $etat_chequier = $date_debut = $date_fin = NULL;

    if(!is_null($_POST['num_client']))
        $num_client = $_POST['num_client'];

    if(!is_null($_POST['date_debut']))
        $date_debut = $_POST['date_debut'];

    if(!is_null($_POST['date_fin']))
        $date_fin = $_POST['date_fin'];

    $etat_chequier = $_POST['etat_chequier'] - 1; // To offset the form value 'Tous'
    if($etat_chequier < 0) $etat_chequier = 10; // valeur temporaire pour critere 'TOUS' car etat_chequier = 0 veut dire 'En attente livraison' !!

    $criteres_recherche['num_client'] = $num_client;
    $criteres_recherche['date_debut'] = $date_debut;
    $criteres_recherche['date_fin'] = $date_fin;
    $criteres_recherche['etat_chequier'] = $etat_chequier;

    $id_client = _('Tous');
    $etat_chequier_affiche = _('Tous');

    if(!empty($num_client)) {
        $id_client = $num_client;
    }

    if($etat_chequier != 10) {
        $etat_chequier_affiche = _($adsys["adsys_etat_chequier"][$etat_chequier]);
    }

    $criteres = array (
        _("Date début") => date($date_debut),
        _("Date fin") => date($date_fin),
        _("État Chéquier") => $etat_chequier_affiche,
        _("Numéro client") => $id_client
    );

    $DATAS = getRapportEtatChequiersImprimesData($criteres_recherche);

    if($global_nom_ecran == "Rcq-11") { // Print PDF
        $xml = xml_etat_chequiers_imprime($DATAS, $criteres); //Génération du code XML
        $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'etat_chequiers_imprime.xslt'); //Génération du XSL-FO et du PDF
        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        $show_pdf = get_show_pdf_html("Gen-13", $fichier_pdf);
        echo $show_pdf;
    }
    else { // Export csv
        $xml = xml_etat_chequiers_imprime($DATAS, $criteres); //Génération du code XML
        $csv_file = xml_2_csv($xml, 'etat_chequiers_imprime.xslt');
        if (isset($excel) && $excel == 'Export EXCEL'){
            echo getShowEXCELHTML("Gen-13", $csv_file);
        }
        else{
            echo getShowCSVHTML("Gen-13", $csv_file);
        }
    }

    ajout_historique(340, NULL, NULL, $global_nom_login, date("r"), NULL);
}
/*}}}*/

/*{{{ Rcq-20 : Personalisation du rapport Liste des commandes de chéquiers */
elseif ($global_nom_ecran == "Rcq-20")
{
    global $adsys;
    $MyPage = new HTML_GEN2(_("Personalisation du rapport Liste des commandes de chéquiers"));

    //Champs date début
    $MyPage->addField("date_debut", _("Date début"), TYPC_DTE);
    $MyPage->setFieldProperties("date_debut", FIELDP_DEFAULT, date("01/01/Y"));
    $MyPage->setFieldProperties("date_debut", FIELDP_IS_REQUIRED, true);
    //Champs date fin
    $MyPage->addField("date_fin", _("Date fin"), TYPC_DTE);
    $MyPage->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
    $MyPage->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);

    // Recherche client
    $js_chercheClient = "
            OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', '"._("Recherche")."');return false;
        ";

    $MyPage->addField("num_client", _("N° de client"), TYPC_INT);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_REQUIRED, false);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_LABEL, false);
    $MyPage->addLink("num_client", "rechercher", _("Rechercher"), "#");
    $MyPage->setLinkProperties("rechercher", LINKP_JS_EVENT, array("onclick" => $js_chercheClient));

    //Boutons
    $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Rcq-21");
    $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Rcq-22");
    $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Rcq-22");
    $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    // Code javascript pour la vérification des champs obligatoires
    $JSCheck = "
        if (document.ADForm.HTML_GEN_date_date_debut.value &&
            document.ADForm.HTML_GEN_date_date_fin.value &&
            !isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin.value)) {
                msg += '- La date de début doit être antérieure à la date de fin';
                ADFormValid = false;
        }
    ";

    $MyPage->addJS(JSP_BEGIN_CHECK, "JSCheck", $JSCheck);
    $MyPage->buildHTML();
    echo $MyPage->getHTML();
}
/*}}}*/

/*{{{ Rcq-21 et Rcq-22 : Impression PDF ou export CSV du rapport Liste des commandes de chéquiers */
elseif ($global_nom_ecran == "Rcq-21" || $global_nom_ecran == "Rcq-22")
{
    global $adsys;

    $num_client = $date_debut = $date_fin = NULL;

    if(!is_null($_POST['num_client']))
        $num_client = $_POST['num_client'];

    if(!is_null($_POST['date_debut']))
        $date_debut = $_POST['date_debut'];

    if(!is_null($_POST['date_fin']))
        $date_fin = $_POST['date_fin'];

    $criteres_recherche['num_client'] = $num_client;
    $criteres_recherche['date_debut'] = $date_debut;
    $criteres_recherche['date_fin'] = $date_fin;

    $id_client = _('Tous');

    if(!empty($num_client)) {
        $id_client = $num_client;
    }

    $criteres = array (
        _("Date début") => date($date_debut),
        _("Date fin") => date($date_fin),
        _("Numéro client") => $id_client
    );

    $DATAS = getRapportCheqCommandeOrImpressionData($criteres_recherche);

    if($global_nom_ecran == "Rcq-21") { // Print PDF
        $xml = xml_liste_chequiers_commande_envoye_impression($DATAS, $criteres); //Génération du code XML
        $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'chequiers_commande_envoye_impression.xslt'); //Génération du XSL-FO et du PDF
        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        $show_pdf = get_show_pdf_html("Gen-13", $fichier_pdf);
        echo $show_pdf;
    }
    else { // Export csv
        $xml = xml_liste_chequiers_commande_envoye_impression($DATAS, $criteres); //Génération du code XML
        $csv_file = xml_2_csv($xml, 'chequiers_commande_envoye_impression.xslt');
        if (isset($excel) && $excel == 'Export EXCEL'){
            echo getShowEXCELHTML("Gen-13", $csv_file);
        }
        else{
            echo getShowCSVHTML("Gen-13", $csv_file);
        }
    }

    ajout_historique(340, NULL, NULL, $global_nom_login, date("r"), NULL);
}
/*}}}*/

/*{{{ Rcq-30 : Personalisation du rapport Liste des chéquiers envoyés à l'impression */
elseif ($global_nom_ecran == "Rcq-30")
{
    global $adsys;
    $MyPage = new HTML_GEN2(_("Personalisation du rapport Liste des chéquiers envoyés à l'impression"));

    //Champs date début
    $MyPage->addField("date_debut", _("Date début"), TYPC_DTE);
    $MyPage->setFieldProperties("date_debut", FIELDP_DEFAULT, date("01/01/Y"));
    $MyPage->setFieldProperties("date_debut", FIELDP_IS_REQUIRED, true);
    //Champs date fin
    $MyPage->addField("date_fin", _("Date fin"), TYPC_DTE);
    $MyPage->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
    $MyPage->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);

    // Recherche client
    $js_chercheClient = "
            OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', '"._("Recherche")."');return false;
        ";

    $MyPage->addField("num_client", _("N° de client"), TYPC_INT);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_REQUIRED, false);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_LABEL, false);
    $MyPage->addLink("num_client", "rechercher", _("Rechercher"), "#");
    $MyPage->setLinkProperties("rechercher", LINKP_JS_EVENT, array("onclick" => $js_chercheClient));

    //Boutons
    $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Rcq-31");
    $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Rcq-32");
    $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Rcq-32");
    $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    // Code javascript pour la vérification des champs obligatoires
    $JSCheck = "
        if (document.ADForm.HTML_GEN_date_date_debut.value &&
            document.ADForm.HTML_GEN_date_date_fin.value &&
            !isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin.value)) {
                msg += '- La date de début doit être antérieure à la date de fin';
                ADFormValid = false;
        }
    ";

    $MyPage->addJS(JSP_BEGIN_CHECK, "JSCheck", $JSCheck);
    $MyPage->buildHTML();
    echo $MyPage->getHTML();
}
/*}}}*/

/*{{{ Rcq-31 et Rcq-32 : Impression PDF ou export CSV du rapport Liste des chéquiers envoyés à l'impression */
elseif ($global_nom_ecran == "Rcq-31" || $global_nom_ecran == "Rcq-32")
{
    global $adsys;

    $num_client = $date_debut = $date_fin = NULL;

    if(!is_null($_POST['num_client']))
        $num_client = $_POST['num_client'];

    if(!is_null($_POST['date_debut']))
        $date_debut = $_POST['date_debut'];

    if(!is_null($_POST['date_fin']))
        $date_fin = $_POST['date_fin'];

    $criteres_recherche['num_client'] = $num_client;
    $criteres_recherche['date_debut'] = $date_debut;
    $criteres_recherche['date_fin'] = $date_fin;

    $id_client = _('Tous');

    if(!empty($num_client)) {
        $id_client = $num_client;
    }

    $criteres = array (
        _("Date début") => date($date_debut),
        _("Date fin") => date($date_fin),
        _("Numéro client") => $id_client
    );

    $DATAS = getRapportCheqCommandeOrImpressionData($criteres_recherche, false);

    if($global_nom_ecran == "Rcq-31") { // Print PDF
        $xml = xml_liste_chequiers_commande_envoye_impression($DATAS, $criteres, false); //Génération du code XML
        $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'chequiers_commande_envoye_impression.xslt'); //Génération du XSL-FO et du PDF
        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        $show_pdf = get_show_pdf_html("Gen-13", $fichier_pdf);
        echo $show_pdf;
    }
    else { // Export csv
        $xml = xml_liste_chequiers_commande_envoye_impression($DATAS, $criteres, false); //Génération du code XML
        $csv_file = xml_2_csv($xml, 'chequiers_commande_envoye_impression.xslt');
        if (isset($excel) && $excel == 'Export EXCEL'){
            echo getShowEXCELHTML("Gen-13", $csv_file);
        }
        else{
            echo getShowCSVHTML("Gen-13", $csv_file);
        }
    }

    ajout_historique(340, NULL, NULL, $global_nom_login, date("r"), NULL);
}
/*}}}*/
/*{{{ Rcq-40 : Personalisation du rapport Liste des chèques/chéquiers mise en opposition */
elseif ($global_nom_ecran == "Rcq-40")
{
    global $adsys;

    $MyPage = new HTML_GEN2(_("Personnalisation du rapport Liste des cheques / chequiers mis en opposition"));

    // Etats des chequiers
    $choix_etat_chequier = array();
    $choix_etat_chequier[0] = _("Tous");
    $choix_etat_chequier[1] = _($adsys["adsys_etat_chequier"][0]);
    $choix_etat_chequier[2] = _($adsys["adsys_etat_chequier"][1]);

    //Champs date début
    $MyPage->addField("date_debut", _("Date début"), TYPC_DTE);
    $MyPage->setFieldProperties("date_debut", FIELDP_DEFAULT, date("01/01/Y"));
    $MyPage->setFieldProperties("date_debut", FIELDP_IS_REQUIRED, true);
    //Champs date fin
    $MyPage->addField("date_fin", _("Date fin"), TYPC_DTE);
    $MyPage->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
    $MyPage->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);
//    // Etat chequier
//    $MyPage->addField("etat_chequier", _("État Chéquier"), TYPC_LSB);
//    $MyPage->setFieldProperties("etat_chequier", FIELDP_ADD_CHOICES, $choix_etat_chequier);
//    $MyPage->setFieldProperties("etat_chequier", FIELDP_IS_REQUIRED, false);
//    $MyPage->setFieldProperties("etat_chequier", FIELDP_HAS_CHOICE_TOUS, false);
//    $MyPage->setFieldProperties("etat_chequier", FIELDP_HAS_CHOICE_AUCUN, false);

    // Recherche client
    $js_chercheClient = "
            OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', '"._("Recherche")."');return false;
        ";

    $MyPage->addField("num_client", _("N° de client"), TYPC_INT);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_REQUIRED, false);
    $MyPage->setFieldProperties("num_client", FIELDP_IS_LABEL, false);
    $MyPage->addLink("num_client", "rechercher", _("Rechercher"), "#");
    $MyPage->setLinkProperties("rechercher", LINKP_JS_EVENT, array("onclick" => $js_chercheClient));

    //Boutons
    $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Rcq-41");
    $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Rcq-42");
    $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Rcq-42");
    $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    // Code javascript pour la vérification des champs obligatoires
    $JSCheck = "
        if (document.ADForm.HTML_GEN_date_date_debut.value &&
            document.ADForm.HTML_GEN_date_date_fin.value &&
            !isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin.value)) {
                msg += '- La date de début doit être antérieure à la date de fin';
                ADFormValid = false;
        }
    ";

    $MyPage->addJS(JSP_BEGIN_CHECK, "JSCheck", $JSCheck);
    $MyPage->buildHTML();
    echo $MyPage->getHTML();
}
/*}}}*/

/*{{{ Rcq-41 et Rcq-42 : Impression PDF ou export CSV du rapport Liste des chèques/chéquiers misent en opposition */
elseif ($global_nom_ecran == "Rcq-41" || $global_nom_ecran == "Rcq-42")
{
    global $adsys;

    $num_client = $etat_chequier = $date_debut = $date_fin = NULL;

    if(!is_null($_POST['num_client']))
        $num_client = $_POST['num_client'];

    if(!is_null($_POST['date_debut']))
        $date_debut = $_POST['date_debut'];

    if(!is_null($_POST['date_fin']))
        $date_fin = $_POST['date_fin'];

    $etat_chequier = $_POST['etat_chequier'] - 1; // To offset the form value 'Tous'
    if($etat_chequier < 0) $etat_chequier = 10; // valeur temporaire pour critere 'TOUS' car etat_chequier = 0 veut dire 'En attente livraison' !!

    $criteres_recherche['num_client'] = $num_client;
    $criteres_recherche['date_debut'] = $date_debut;
    $criteres_recherche['date_fin'] = $date_fin;
//    $criteres_recherche['etat_chequier'] = $etat_chequier;

    $id_client = _('Tous');
//    $etat_chequier_affiche = _('Tous');

    if(!empty($num_client)) {
        $id_client = $num_client;
    }

//    if($etat_chequier != 10) {
//        $etat_chequier_affiche = _($adsys["adsys_etat_chequier"][$etat_chequier]);
//    }

    $criteres = array (
        _("Date début") => date($date_debut),
        _("Date fin") => date($date_fin),
//        _("État Chéquier") => $etat_chequier_affiche,
        _("Numéro client") => $id_client
    );

    $DATAS = getRapportChequiersEnOppositionData($criteres_recherche);

    if($global_nom_ecran == "Rcq-41") { // Print PDF
        $xml = xml_chequiers_en_opposition($DATAS, $criteres); //Génération du code XML
        $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'chequiers_opposition.xslt'); //Génération du XSL-FO et du PDF
        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        $show_pdf = get_show_pdf_html("Gen-13", $fichier_pdf);
        echo $show_pdf;
    }
    else { // Export csv
        $xml = xml_chequiers_en_opposition($DATAS, $criteres); //Génération du code XML
        $csv_file = xml_2_csv($xml, 'chequiers_opposition.xslt');
        if (isset($excel) && $excel == 'Export EXCEL'){
            echo getShowEXCElHTML("Gen-13", $csv_file);
        }
        else{
            echo getShowCSVHTML("Gen-13", $csv_file);
        }
    }

    ajout_historique(340, NULL, NULL, $global_nom_login, date("r"), NULL);
}
/*}}}*/
else
    signalErreur(__FILE__, __LINE__, __FUNCTION__, sprintf(_("L'écran %s n'a pas pu être trouvé"), $global_nom_ecran));
?>