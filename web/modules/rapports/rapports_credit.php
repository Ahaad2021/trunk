<?php


/* vim: set expandtab softtabstop=2 shiftwidth=2 foldmethod=marker: */

/**
 * [350] Rapports crédit
 *
 * Cette opération comprends les écrans :
 * - Kra-1 : Sélection du rapport à imprimer
 * - Kra-10 et Kra-11 : RET - Impression ou export csv Crédits en retard
 * - Kra-13   : Rapport liste des clients dÃ©biteurs en crÃ©dit et dÃ©couvert
 * - Kra-52 et Kra-54   : Impresion en pdf et csv de la liste des clients dÃ©biteurs en crÃ©dit et dÃ©couvert
 * - Kra-32 et Kra-34 : BAL - Impression ou export csv Balance âgée portefeuille à risque
 * - Kra-35 : BAL - Sélection type rapport Balance âgée portefeuille à risque
 * - Kra-20 : CON - Personnalisation concentration portefeuille de crédit
 * - Kra-21 et Kra-22 : CON - Impression ou export concentration portefeuille de crédit
 * - Kra-30 : SLD - Personnalisation historique crédits clients
 * - Kra-31 et Kra-33 : SLD - Impression ou export csv historique crédits clients
 * - Kra-40 : DEM - Personnalisation historique demandes de crédits clients
 * - Kra-41 et Kra-51 : DEM - Impression ou export csv historique demandes de crédits clients
 * - Kra-42 : MAX - Personnalisation débiteurs les plus importants
 * - Kra-43 et Kra-53 : MAX - Impression ou export csv des encours de crédit les plus importants
 * - Kra-44 : EMP - Crédits accordés aux employés
 * - Kra-45 : PRT - Personnalisation crédits passés en perte
 * - Kra-46 et Kra-56 : PRT - Impression ou export csv crédits passés en perte
 * - Kra-47 : REG - Personnalisation registre des prêts
 * - Kra-48 et Kra-58 : REG - Impression ou export csv registre des prêts
 * - Kra-49 : AEC - Personnalisation crédits arrivant à échéance
 * - Kra-50 et Kra-60 : AEC - Impression ou export csv crédits arrivant à échéance
 * - Kra-61  : CAA - Crédits actifs par agent de crédit
 * - Kra-62 et Kra-66  :Impression ou export csv Crédits actifs par agent de crédit
 * - Kra-70 Kra-72: SCR - Impression ou export csv situation des risques de crédits
 * - Kra-73 : REE - Personnalisation rapport des crédits réechelonnés
 * - Kra-74 et Kra-75 : REE - Impression ou export csv rapport des crédits réechelonnés
 * - Kra-76 : REE - Personnalisation historique des crédits octroyés
 * - Kra-77 et Kra-78 : REE - Impression ou export csv historique des crédits octroyés
 * - Kra-79 : EMP - Loans Granted to the Directors
 * - Kra-80 et Kra-81 : EMP - Impression ou export Loans Granted to the Directors
 * - Kra-82 : PGE - Personnalisation emprunteurs les plus grands
 * - Kra-83 et Kra-84 : PGE - Impression ou export csv emprunteurs les plus grands
 * - Kra-85 : CRA - Personnalisation situation des risques par secteur activité
 * - Kra-86 et Kra- 87 : CRA - Impression ou export csv situation des risques par secteur activité
 * - Kra-88 : RCR - Personnalisation Recouvrement de créances douteuses, litigieuses et contentieuses
 * - Kra-89 et Kra-90 : RCR - Impression ou export csv Recouvrement de créances douteuses, litigieuses et contentieuses
 * - Kra-91 : PCS - Personnalisation Provisions des crédits en souffrances
 * - Kra-92 et Kra-93 : RCR - Impression ou export csv Provisions des crédits en souffrances
 * - Kra-94 : REC - Personnalisation Recouvrement sur les crédits
 * - Kra-95 et Kra-96 : REC - Personnalisation Recouvrement sur les crédits
 * - Kra-97 : LCR - Rapport Suivi Ligne de crédit
 * - Kra-98 et Kra-99 : LCR - Impression ou export csv Rapport Suivi Ligne de crédit
 * - Kra-101 : ICT - Rapport Inventaire de Credits
 * - Kra-102 et Kra-103 : ICT - Impression ou export csv Rapport Inventaire crédit
 *
 * @package Rapports
 */
require_once 'lib/html/HTML_GEN2.php';
require_once 'lib/dbProcedures/rapports.php';
require_once 'modules/rapports/xml_credits.php';
require_once 'modules/rapports/xslt.php';
require_once 'lib/dbProcedures/credit.php';
require_once 'lib/dbProcedures/login_func.php';
require_once 'lib/misc/divers.php';
require_once 'lib/misc/csv.php';
require_once 'lib/html/echeancier.php';
require_once 'lib/algo/ech_theorique.php';
require_once 'modules/rapports/xml_echeancier.php';
require_once 'lib/dbProcedures/client.php';
require_once 'lib/misc/excel.php';

/*{{{ Kra-1 : Sélection du rapport à imprimer */
if ($global_nom_ecran == "Kra-1") {

//Recherche de tous les rapports à afficher
  foreach ($adsys["adsys_rapport"] as $key => $name) {
    if (substr($key, 0, 3) == 'CRD' && substr($key, 0, 7) != 'CRD-ECH' && substr($key, 0, 7) != 'CRD-EMY' && substr($key, 0, 7) != 'CRD-DIR' && substr($key, 0, 7) != 'CRD-PGE' && substr($key, 0, 7) != 'CRD-CRA' && substr($key, 0, 7) != 'CRD-RCR' && substr($key, 0, 7) != 'CRD-ICT')
    //if (substr($key, 0, 3) == 'CRD' && substr($key, 0, 7) != 'CRD-ECH' && substr($key, 0, 7) != 'CRD-EMY' && substr($key, 0, 7) != 'CRD-DIR' && substr($key, 0, 7) != 'CRD-PGE' && substr($key, 0, 7) != 'CRD-CRA' && substr($key, 0, 7) != 'CRD-RCR' && substr($key, 0, 7) != 'CRD-ICT' && substr($key, 0, 7) != 'CRD-REC')
      $rapports[$key] = _($name);
  }

  $MyPage = new HTML_GEN2(_("Sélection type rapport crédit"));
  $MyPage->addField("type", _("Type de rapport crédit"), TYPC_LSB);
  $MyPage->setFieldProperties("type", FIELDP_IS_REQUIRED, true);
  $MyPage->setFieldProperties("type", FIELDP_ADD_CHOICES, $rapports);

  //Boutons
  $MyPage->addFormButton(1, 1, "valider", _("Sélectionner"), TYPB_SUBMIT);
  $MyPage->addFormButton(1, 2, "annuler", _("Annuler"), TYPB_SUBMIT);
  $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
  $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

  // Tableau indiquant le prochain écran en fonction du code rapport
  $prochEc = array (
               "RET" => 11,
               "CON" => 20,
               "SLD" => 30,
               "BAL" => 35,
               "DEM" => 40,
               "MAX" => 42,
               "EMP" => 65,
               "PRT" => 45,
               "REG" => 47,
               "AEC" => 49,
               "CAA" => 61,
               "LCD" => 13,
               "REP" => 67,
               "SRC" => 70,
               "REE" => 73,
               "OCT" => 76,
               "PCS" => 91,
               "REC" => 94,
               "SLC" => 97,
                // Commenter trac#667 / pp#221
               "ICT" => 101
             );

  foreach ($prochEc as $code => $ecran)
  $js .= "if (document.ADForm.HTML_GEN_LSB_type.value == 'CRD-$code')
         assign('Kra-$ecran');";
  $MyPage->addJS(JSP_BEGIN_CHECK, "js1", $js);

  //HTML
  $MyPage->buildHTML();
  echo $MyPage->getHTML();
}
/*}}}*/

/*{{{ Kra-10 et Kra-12 : RET - Impression ou export csv Crédits en retard */
elseif ($global_nom_ecran == "Kra-10" || $global_nom_ecran == "Kra-12") { //Impression ou export rapport 'crédits en retard'
    setGlobalIdAgence($agence);
    if ($gest == "")
      $gest = 0;
    if ($etat == "")
      $etat = 0;

    if ($global_nom_ecran == 'Kra-10') {
      //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
      $xml = xml_credits_retard($gest, $etat);
      $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'credits_retard.xslt');

      //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
      echo get_show_pdf_html("Gen-13", $fichier_pdf);
    } else
      if ($global_nom_ecran == 'Kra-12') {
        //Génération du CSV grâce à XALAN
        $xml = xml_credits_retard($gest, $etat, true);
        $csv_file = xml_2_csv($xml, 'credits_retard.xslt');

        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        if (isset($excel) && $excel == 'Export EXCEL'){
          echo getShowEXCELHTML("Gen-13", $csv_file);
        }
        else{
          echo getShowCSVHTML("Gen-13", $csv_file);
        }
      }
  }
/*}}}*/

/*{{{ Kra-11 : RET - Crédits en retard */
elseif ($global_nom_ecran == "Kra-11") {
      $MyPage = new HTML_GEN2(_("Sélection type rapport crédit"));
      //Remettre $global_id_agence à l'identifiant de l'agence courante
      resetGlobalIdAgence();
      //Agence- Tri par agence
      $list_agence = getAllIdNomAgence();
      if (isSiege()) {
	      unset ($list_agence[$global_id_agence]);
	      $MyPage->addField("agence", _("Agence"), TYPC_LSB);
	      $MyPage->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
	      $MyPage->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
	      $MyPage->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
	    }
      $etats = getEtatCreditRetard();
      $MyPage->addField("etat", _("Etat crédit"), TYPC_LSB );
      $MyPage->setFieldProperties("etat", FIELDP_ADD_CHOICES, $etats);
      $infos_ag = getAgenceDatas($global_id_agence);
      $MyPage->setFieldProperties("etat", FIELDP_HAS_CHOICE_AUCUN, false);
      $MyPage->setFieldProperties("etat", FIELDP_HAS_CHOICE_TOUS, true);

      //Gestionnaire- Tri par agent gestionnaire
      $MyPage->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
      $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
      $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

      //Boutons
      $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
      $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-10");
      $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
      $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-12");
      $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
      $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-12");
      $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
      $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
      $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

      //HTML
      $MyPage->buildHTML();
      echo $MyPage->getHTML();

    }
/*}}}*/

/*{{{ Kra-32 et Kra-34 : BAL - Impression ou export csv Balance âgée portefeuille à risque */
elseif ($global_nom_ecran == "Kra-32" || $global_nom_ecran == "Kra-34") {
        setGlobalIdAgence($agence);
        if ($gest == "")
          $gest = 0;
        
        //Amelioration 455
        if (!empty ($date_debloc_inf)) {
        	$date_debloc_inf1 = $date_debloc_inf;
        }
        if (!empty ($date_debloc_sup)) {
        	$date_debloc_sup1 = $date_debloc_sup;
        }
         
        //get id produit
        if (!empty ($prd)) {
        	$SESSION_VARS['id_prod'] = $prd;
        }
        
          if ($global_nom_ecran == 'Kra-34') {
          	//Génération du CSV grâce à XALAN
          	$xml = xml_balanceportefeuille($gest, $export_date, $type_affich, $date_debloc_inf1, $date_debloc_sup1, $SESSION_VARS['id_prod'], true);
          	if($xml != NULL){
          		$csv_file = xml_2_csv($xml, 'balance_age_portefeuille_risque.xslt');

          		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
              if (isset($excel) && $excel == 'Export EXCEL'){
                echo getShowEXCELHTML("Gen-13", $csv_file);
              }
              else{
          		  echo getShowCSVHTML("Gen-13", $csv_file);
              }
          	}

          } elseif ($global_nom_ecran == 'Kra-32') {
          	//Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
          	
          	$xml = xml_balanceportefeuille($gest, $export_date, $type_affich, $date_debloc_inf1, $date_debloc_sup1, $SESSION_VARS['id_prod']);
        
          	if($xml != NULL){
          		$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'balance_age_portefeuille_risque.xslt');
          		
          		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
          		echo get_show_pdf_html("Gen-13", $fichier_pdf);
          	}
          }
          if($xml == NULL){
          	$html_msg = new HTML_message(_("Résultats de la requête"));
          	$html_msg->setMessage(_("Aucun crédit n'a été octroyé"));
          	$html_msg->addButton("BUTTON_OK", 'Gen-13');
          	$html_msg->buildHTML();
          	echo $html_msg->HTML_code;
          }
}
/*}}}* /

/*{{{ Kra-35 : BAL - Sélection type rapport Balance âgée portefeuille à risque */
elseif ($global_nom_ecran == "Kra-35") {

          $MyPage = new HTML_GEN2(_("Sélection type rapport Balance âgée portefeuille à risque"));

          //Remettre $global_id_agence à l'identifiant de l'agence courante
	      resetGlobalIdAgence();
	      //Agence- Tri par agence
	      $list_agence = getAllIdNomAgence();
	    if (isSiege()) {
	      unset ($list_agence[$global_id_agence]);
	      $MyPage->addField("agence", _("Agence"), TYPC_LSB);
	      $MyPage->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
	      $MyPage->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
	      $MyPage->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
	    }
	    unset ($SESSION_VARS['id_prod']);
	    
	    // Date Deboursement- Tri par date debut et date fin de deboursement
	      $MyPage->addField("date_debloc_inf", _("Date début du déboursement"), TYPC_DTE);
	      $MyPage->setFieldProperties("date_debloc_inf", FIELDP_DEFAULT, date("01/01/2000"));
	      $MyPage->setFieldProperties("date_debloc_inf", FIELDP_IS_REQUIRED, false);
	    
	      $MyPage->addField("date_debloc_sup", _("Date fin de déboursement"), TYPC_DTE);
	      $MyPage->setFieldProperties("date_debloc_sup", FIELDP_DEFAULT, date("d/m/Y"));
	      $MyPage->setFieldProperties("date_debloc_sup", FIELDP_IS_REQUIRED, false);

          //Gestionnaire- Tri par agent gestionnaire
          $MyPage->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
          $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
          $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);
          //date de l'export
          $MyPage->addField("export_date", _("Date"), TYPC_DTE);
          $MyPage->setFieldProperties("export_date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))));
          $MyPage->setFieldProperties("export_date", FIELDP_IS_REQUIRED, true);
          
          //produits
          $MyPage->addTableRefField("prd", _("Type de produit de crédit"), "adsys_produit_credit");
          $MyPage->setFieldProperties("prd", FIELDP_HAS_CHOICE_AUCUN, false);
          $MyPage->setFieldProperties("prd", FIELDP_HAS_CHOICE_TOUS, true);
          
          $list_affich = array(1 => _("Détaillé"), 2 => _("Synthétique"));
          $MyPage->addField("type_affich", _("Type affichage"), TYPC_LSB);
          $MyPage->setFieldProperties("type_affich", FIELDP_ADD_CHOICES, $list_affich);
          $MyPage->setFieldProperties("type_affich", FIELDP_HAS_CHOICE_AUCUN, false);
          $MyPage->setFieldProperties("type_affich", FIELDP_HAS_CHOICE_TOUS, false);
          //Boutons
          $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
          $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-32");
          $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
          $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-34");
          $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
          $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-34");
          $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
          $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
          $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

          //HTML
          $MyPage->buildHTML();
          echo $MyPage->getHTML();

        }
/*}}}*/

/*{{{ Kra-20 : CON - Personnalisation concentration portefeuille de crédit */
elseif ($global_nom_ecran == "Kra-20") {

            if ($global_multidevise)
              setMonnaieCourante("");

            $MyPage = new HTML_GEN2(_("Sélection du critère"));

            //Remettre $global_id_agence à l'identifiant de l'agence courante
	        resetGlobalIdAgence();
	        //Agence- Tri par agence
	        $list_agence = getAllIdNomAgence();
	        if (isSiege()) {
		      unset ($list_agence[$global_id_agence]);
		      $MyPage->addField("agence", _("Agence"), TYPC_LSB);
		      $MyPage->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
		      $MyPage->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, false);
		      $MyPage->setFieldProperties("agence", FIELDP_HAS_CHOICE_TOUS, true);
		    }
            //Champs 'Critère de répartition'
            $MyPage->addField("critere", _("Critère de répartition"), TYPC_LSB);
            $choix = array (
                       "prod_cred" => _("Produit de crédit"),
                       "sect_act" => _("Secteur d'activité"),
                       "mnt" => _("Montant octroyé"),
                       "duree" => _("Durée"),
                       "stat_jur" => _("Statut juridique"),
                       "loc" => _("Localisation")
                     );
            $MyPage->setFieldProperties("critere", FIELDP_ADD_CHOICES, $choix);
            $MyPage->setFieldProperties("critere", FIELDP_IS_REQUIRED, true);
            $js_fct = "function set_disabled(mnt_b1, mnt_b2, duree_b1, duree_b2, loc, type_duree){";
            $js_fct .= "  document.ADForm.mnt_borne1.disabled = mnt_b1; document.ADForm.mnt_borne2.disabled = mnt_b1;";
            $js_fct .= "  document.ADForm.duree_borne1.disabled = duree_b1; document.ADForm.duree_borne2.disabled = duree_b2; document.ADForm.HTML_GEN_LSB_type_duree.disabled = type_duree ;";
            $js_fct .= "  document.ADForm.HTML_GEN_LSB_loc.disabled = loc;";
            $js_fct .= "}";

            $js = "if (document.ADForm.HTML_GEN_LSB_critere.value == 'prod_cred') set_disabled(true, true, true, true, true, true);";
            $js .= "if (document.ADForm.HTML_GEN_LSB_critere.value == 'sect_act') set_disabled(true, true, true, true, true, true);";
            $js .= "if (document.ADForm.HTML_GEN_LSB_critere.value == 'mnt') set_disabled(false, false, true, true, true, true);";
            $js .= "if (document.ADForm.HTML_GEN_LSB_critere.value == 'duree') set_disabled(true, true, false, false, true, false);";
            $js .= "if (document.ADForm.HTML_GEN_LSB_critere.value == 'stat_jur') set_disabled(true, true, true, true, true, true);";
            $js .= "if (document.ADForm.HTML_GEN_LSB_critere.value == 'loc') set_disabled(true, true, true, true, false, true);";
            $MyPage->setFieldProperties("critere", FIELDP_JS_EVENT, array (
                                          "onchange" => $js
                                        ));
            $MyPage->addJS(JSP_FORM, "js1", $js_fct);
            //Champs mnt borne1
            $MyPage->addField("mnt_borne1", _("Montant borne 1"), TYPC_MNT);
            $MyPage->setFieldProperties("mnt_borne1", FIELDP_IS_REQUIRED, true);
            $MyPage->setFieldProperties("mnt_borne1", FIELDP_IS_LABEL, true);

            //Champs mnt borne2
            $MyPage->addField("mnt_borne2", _("Montant borne 2 (> borne 1)"), TYPC_MNT);
            $MyPage->setFieldProperties("mnt_borne2", FIELDP_IS_REQUIRED, true);
            $MyPage->setFieldProperties("mnt_borne2", FIELDP_IS_LABEL, true);

            //Champs type duree
            $MyPage->addTableRefField("type_duree", _("Type durée"), "adsys_type_duree_credit");
            $MyPage->setFieldProperties("type_duree", FIELDP_HAS_CHOICE_AUCUN, false);
            $MyPage->setFieldProperties("type_duree", FIELDP_IS_REQUIRED, true);
            $MyPage->setFieldProperties("type_duree", FIELDP_IS_LABEL, true);

            //Champs duree borne1
            $MyPage->addField("duree_borne1", _("Durée borne 1 "), TYPC_INT);
            $MyPage->setFieldProperties("duree_borne1", FIELDP_IS_REQUIRED, true);
            $MyPage->setFieldProperties("duree_borne1", FIELDP_IS_LABEL, true);

            //Champs duree borne2
            $MyPage->addField("duree_borne2", _("Durée borne 2 (> borne 1)"), TYPC_INT);
            $MyPage->setFieldProperties("duree_borne2", FIELDP_IS_REQUIRED, true);
            $MyPage->setFieldProperties("duree_borne2", FIELDP_IS_LABEL, true);

            //Champs localisation
            $MyPage->addField("loc", _("Localisation"), TYPC_LSB);
            $MyPage->setFieldProperties("loc", FIELDP_IS_REQUIRED, true);
            $MyPage->setFieldProperties("loc", FIELDP_IS_LABEL, true);
            //AT-33/AT-78 - Localisation Rwanda
            $Data_agence = getAgenceDatas($global_id_agence);
            if ($Data_agence['identification_client'] == 2){  //Type Localisation Rwanda
              $MyPage->setFieldProperties("loc", FIELDP_ADD_CHOICES, array (
                "1" => _("Province"),
                "2" => _("District"),
                "3" => _("Secteur"),
                "4" => _("Cellule"),
                "5" => _("Village")
              ));
              $MyPage->setFieldProperties("loc", FIELDP_HAS_CHOICE_AUCUN, false);
            }
            else{ //Type Localisation Standard
              $MyPage->setFieldProperties("loc", FIELDP_ADD_CHOICES, array (
                                            "1" => _("Localisation 1"),
                                            "2" => _("Localisation 2")
                                          ));
            }
            
            if (isSiege()) {
            	$js_check_agence="\nif ((document.ADForm.HTML_GEN_LSB_agence.value == 0)" .
            			"&&((document.ADForm.HTML_GEN_LSB_critere.value == 'prod_ep')" .
            			"||(document.ADForm.HTML_GEN_LSB_critere.value == 'sect_act')" .
            			"||(document.ADForm.HTML_GEN_LSB_critere.value == 'loc') )) " .
            			"{msg+='- "._("Tous ne peut pas être sélectionné pour ce critère !")."\\n';ADFormValid=false;\n} \n\t";
            } else {
                 $js_check_agence="\nif (((document.ADForm.HTML_GEN_LSB_critere.value == 'prod_ep')" .
                        "||(document.ADForm.HTML_GEN_LSB_critere.value == 'sect_act')" .
                        "||(document.ADForm.HTML_GEN_LSB_critere.value == 'loc') )) " .
                        "{msg+='- "._("Tous ne peut pas être sélectionné pour ce critère !")."\\n';ADFormValid=false;\n} \n\t";
            }
                 $MyPage->addJS(JSP_BEGIN_CHECK, "JS",$js_check_agence);

            // Champs devise
            if ($global_multidevise) {
              $MyPage->addTable("ad_cpt_comptable", OPER_INCLUDE, array (
                                  "devise"
                                ));
              $MyPage->setFieldProperties("devise", FIELDP_LONG_NAME, _("Devise"));
              $MyPage->setFieldProperties("devise", FIELDP_IS_REQUIRED, true);
            }
            //Gestionnaire- Tri par agent gestionnaire
            $MyPage->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
            $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
            $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);
            
         
            //date : filtre cre_date_etat
            $MyPage->addField("export_date", _("Date"), TYPC_DTE);
            $MyPage->setFieldProperties("export_date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))));
            $MyPage->setFieldProperties("export_date", FIELDP_IS_REQUIRED, true);
            
            //Boutons
            $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
            $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-21");
            $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
            $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-22");
            $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
            $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-22");
            $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
            $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
            $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

            //Javascript de contrôle
            //Check si champs obligatoires sont renseignés
            $js = "msg = ''; ADFormValid = true;";
            if (isSiege()) {
            	$js .= "if ((document.ADForm.HTML_GEN_LSB_agence.value == '0') && (document.ADForm.HTML_GEN_LSB_critere.value == 'prod_cred')) {msg += '"._("Tous ne peut pas être sélectionné pour ce critère !")."\\n'; ADFormValid = false;}";
            } //else {
            	//$js .= "if ((document.ADForm.HTML_GEN_LSB_critere.value == 'prod_cred')) {msg += '"._("Tous ne peut pas être sélectionné pour ce critère !")."\\n'; ADFormValid = false;}";
            //}
            $js .= "if (document.ADForm.HTML_GEN_LSB_critere.value == '0') {msg += '"._("Le champs critère doit être renseigné !")."\\n'; ADFormValid = false;}";
            $js .= "if (((document.ADForm.mnt_borne1.value == '') || (document.ADForm.mnt_borne2.value == '')) && (document.ADForm.HTML_GEN_LSB_critere.value == 'mnt')) {msg += '"._("Les champs bornes pour montants doivent être renseignés !")."\\n'; ADFormValid = false;}";
            $js .= "if (((document.ADForm.duree_borne1.value == '') || (document.ADForm.duree_borne2.value == '')) && (document.ADForm.HTML_GEN_LSB_critere.value == 'duree')) {msg += '"._("Les champs bornes pour durée doivent être renseignés !")."\\n'; ADFormValid = false;}";
            $js .= "if ((document.ADForm.HTML_GEN_LSB_type_duree.value == '')  && (document.ADForm.HTML_GEN_LSB_critere.value == 'duree')) {msg += '"._("Le champ Type  durée doivent être renseigné !")."\\n'; ADFormValid = false;}";

            //   $js .= "if (document.ADForm.type_duree.options[2].selected == true) {msg += 'Il n existe pas de crédits journaliers !\\n'; ADFormValid = false;}";
            $js .= "if ((document.ADForm.HTML_GEN_LSB_loc.value == '0') && (document.ADForm.HTML_GEN_LSB_critere.value == 'loc')) {msg += '"._("Le champs localisation doit être renseigné !")."\\n'; ADFormValid = false;}";
            if (isSiege()) {
            	$js .= "if ((document.ADForm.HTML_GEN_LSB_agence.value == '0') && (document.ADForm.HTML_GEN_LSB_critere.value == 'loc')) {msg += '"._("Tous ne peut pas être sélectionné pour ce critère !")."\\n'; ADFormValid = false;}";
            } //else {
            	//$js .= "if (document.ADForm.HTML_GEN_LSB_critere.value == 'loc') {msg += '"._("Tous ne peut pas être sélectionné pour ce critère !")."\\n'; ADFormValid = false;}";
           // }
            //Check si borne 2 > borne 1
            $js .= "if ((recupMontant(document.ADForm.mnt_borne1.value) >= recupMontant(document.ADForm.mnt_borne2.value)) && (document.ADForm.HTML_GEN_LSB_critere.value == 'mnt')){ msg += '"._("La borne montant 2 doit être strictement plus grande que la borne montant 1")."'; ADFormValid = false;}";
            $js .= "if ((recupMontant(document.ADForm.duree_borne1.value) >= recupMontant(document.ADForm.duree_borne2.value)) && (document.ADForm.HTML_GEN_LSB_critere.value == 'duree')) { msg += '"._("La borne durée 2 doit être strictement plus grande que la borne duree 1")."'; ADFormValid = false;}";

            $MyPage->addJS(JSP_BEGIN_CHECK, "js3", $js);

            //HTM

            $MyPage->buildHTML();
            echo $MyPage->getHTML();
          }
/*}}}*/

/*{{{ Kra-21  et Kra-22 : CON - Impression ou export concentration portefeuille de crédit */
elseif ($global_nom_ecran == "Kra-21" || $global_nom_ecran == "Kra-22") { //Impression rapport 'concentration portefeuille de crédit"
	
	
      if (isSiege()) {
        if ($agence != '') {
          $list_agence[$agence] = $agence; //Sélection d'une agence au siège
          unset ($list_agence['-1']); //on remplace le -1 par 0 au siege
        } else {
          $list_agence = getAllIdNomAgence();
        }
      } else
        $list_agence[$global_id_agence] = $global_id_agence; //Dans une agence

              global $global_multidevise;
              //Construction de la liste des critères de recherche
              $list_criteres = array();
              if ($critere == "sect_act") {
                $list_criteres = array_merge($list_criteres, array (
                                               _("Critère de répartition") => _("Secteur d'activité")
                                             ));
                $val = 1;
                $b1 = -1;
                $b2 = -1;
              }
              elseif ($critere == "mnt") {
                $list_criteres = array_merge($list_criteres, array (
                                               _("Critère de répartition") => _("Montant octroyé")
                                             ));
                $val = 2;
                $b1 = recupMontant($mnt_borne1);
                $b2 = recupMontant($mnt_borne2);
              }
              elseif ($critere == "duree") {
                $list_criteres = array_merge($list_criteres, array (
                                               _("Critère de répartition") => _("Durée")
                                             ));
                $val = 3;
                $b1 = $duree_borne1;
                $b2 = $duree_borne2;
                $type_duree = $type_duree;
              }
              elseif ($critere == "stat_jur") {
                $list_criteres = array_merge($list_criteres, array (
                                               _("Critère de répartition") => _("Statut juridique")
                                             ));
                $val = 4;
                $b1 = -1;
                $b2 = -1;
              }
              elseif ($critere == "loc") {
                $list_criteres = array_merge($list_criteres, array (
                                               _("Critère de répartition") => _("Localisation")
                                             ));
                $val = 5;
                $b1 = $loc;
                $b2 = -1;
              }
              elseif ($critere == "prod_cred")  {
                $list_criteres = array_merge($list_criteres, array (
                                               _("Critère de répartition") => _("Produit de crédit")
                                             ));
                $val = 6;
                $b1 = -1;
                $b2 = -1;
              }

              if ($gest == "")
                $gest = 0;
              else
                $list_criteres = array_merge($list_criteres, array (
                                               _("Gestionnaire") => (getLibel("ad_uti", $gest) == "")?_("Tous"):getLibel("ad_uti", $gest), "id_gest" => $gest
                                             ));
                
               //Date filtre 
                if (!empty ($export_date)) {
                	$list_criteres = array_merge($list_criteres, array (
                			_("Date") => $export_date
                	));
                }
                

              if ((!$global_multidevise) || ($devise == '0'))
                $devise = NULL;

              if ($global_nom_ecran == 'Kra-22') {
                $erreur = getDonneesRepartitionCredit ($list_agence, $val, $b1, $b2, $duree, $devise, $list_criteres, true);
                if ($erreur->errCode != NO_ERR) {
                	$html_err = new HTML_erreur(_("Concentration portefeuille de crédit"));
                	$html_err->setMessage(_("Echec : ") . $erreur->param);
                	$html_err->addButton(BUTTON_OK, 'Gen-13');
                	$html_err->buildHTML();
                  echo $html_err->HTML_code;
                  exit ();
                }
                $DATA = $erreur->param;
                $xml = xml_repartition_credit($list_agence, $DATA, $devise, true);
                //Génération du CSV grâce à XALAN
                $csv_file = xml_2_csv($xml, 'repartition_credit.xslt');

                //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
                if (isset($excel) && $excel == 'Export EXCEL'){
                  echo getShowEXCELHTML("Gen-13", $csv_file);
                }
                else{
                  echo getShowCSVHTML("Gen-13", $csv_file);
                }
              } else
                if ($global_nom_ecran == 'Kra-21') {
                  $erreur = getDonneesRepartitionCredit ($list_agence, $val, $b1, $b2, $duree, $devise, $list_criteres);
                  if ($erreur->errCode != NO_ERR) {
                		$html_err = new HTML_erreur(_("Concentration portefeuille de crédit"));
                		$html_err->setMessage(_("Echec : ") . $erreur->param);
                		$html_err->addButton(BUTTON_OK, 'Gen-13');
                		$html_err->buildHTML();
                  	echo $html_err->HTML_code;
                  	exit ();
                	}
                	$DATA = $erreur->param;
                  $xml = xml_repartition_credit($list_agence, $DATA, $devise, false);
                  //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                  $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'repartition_credit.xslt');

                  //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
                  echo get_show_pdf_html("Gen-13", $fichier_pdf);
                }

            }
/*}}}*/

/*{{{ Kra-30 : SLD - Personnalisation historique crédits clients */
elseif ($global_nom_ecran == "Kra-30") {
                $html = new HTML_GEN2(_("Personnalisation du rapport"));
                //Remettre $global_id_agence à l'identifiant de l'agence courante
		        resetGlobalIdAgence();
		        //Agence- Tri par agence
		        $list_agence = getAllIdNomAgence();
		        if (isSiege()) {
			      unset ($list_agence[$global_id_agence]);
			      $html->addField("agence", _("Agence"), TYPC_LSB);
			      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
			      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
			      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
			    }
                $html->addField("date", _("Crédits soldés depuis la date du"), TYPC_DTE);
                $html->setFieldProperties("date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));

                $html->addField("num_client", _("Client"), TYPC_INT);
                $html->addLink("num_client", "rechercher", _("Rechercher"), "#");
                $html->setLinkProperties("rechercher", LINKP_JS_EVENT, array (
                                           "onclick" => "OpenBrw('../modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', '"._("Recherche")."');"
                                         ));

                $html->addTableRefField("prd", _("Type de produit de crédit"), "adsys_produit_credit");

                $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_AUCUN, false);
                $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_TOUS, true);

                $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
                $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-31");
                $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
                $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-33");
                $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
                $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-33");

                $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
                $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
                $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
                $html->buildHTML();
                echo $html->getHTML();
              }
/*}}}*/

/*{{{ Kra-31 et  Kra-33 : SLD - Impression ou export csv historique crédits clients */
elseif ($global_nom_ecran == "Kra-31" || $global_nom_ecran == "Kra-33") {
                  setGlobalIdAgence($agence);
                  $DATA = array ();
                  $liste_criteres = array ();

                  if (!empty ($date)) {
                    $DATA["date"] = $date;
                    $liste_criteres = array (
                                        _("Liste des crédits soldés depuis le") => $date
                                      );
                  }

                  if (!empty ($num_client)) {
                    $DATA["client"] = $num_client;
                    $InfosCli = getClientDatas($num_client);
                    switch ($InfosCli["statut_juridique"]) {
                    case 1 :
                      $nom_cli = $InfosCli["pp_nom"] . " " . $InfosCli["pp_prenom"];
                      break;
                    case 2 :
                      $nom_cli = $InfosCli["pm_raison_sociale"];
                      break;
                    case 3 :
                      $nom_cli = $InfosCli["gi_nom"];
                    }

                    $liste_criteres[_("Numéro Client")] = $num_client;
                    $liste_criteres[_("Nom client")] = $nom_cli;
                  }

                  if (!empty ($prd)) {
                    $DATA["produit"] = $prd;
                    $libel_prd = getLibelPrdt($prd, "adsys_produit_credit");
                    $liste_criteres[_("Produit")] = $libel_prd;
                  }

                  $lignes = getHisCrdSolde($DATA);
                  if ($lignes != NULL) {

                    //$xml = xml_cli_crd_soldes($lignes, $liste_criteres);
                    if ($global_nom_ecran == 'Kra-33') {
                      //Génération du CSV grâce à XALAN
                      $xml = xml_cli_crd_soldes($lignes, $liste_criteres, true);
                      $csv_file = xml_2_csv($xml, 'his_crd_cli.xslt');

                      //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
                      if (isset($excel) && $excel == 'Export EXCEL'){
                        echo getShowEXCELHTML("Gen-13", $csv_file);
                      }
                      else{
                        echo getShowCSVHTML("Gen-13", $csv_file);
                      }
                    } else
                      if ($global_nom_ecran == 'Kra-31') {
                        //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                        $xml = xml_cli_crd_soldes($lignes, $liste_criteres);
                        $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'his_crd_cli.xslt');

                        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
                        echo get_show_pdf_html("Gen-13", $fichier_pdf);
                      }
                    ajout_historique(350, NULL, NULL, $global_nom_login, date("r"), NULL);

                  } else {
                    $html_msg = new HTML_message(_("Résultats de la requête"));
                    $html_msg->setMessage(_("Aucun crédit n'a été trouvé"));
                    $html_msg->addButton("BUTTON_OK", 'Gen-13');
                    $html_msg->buildHTML();
                    echo $html_msg->HTML_code;
                  }
                }
/*}}}*/

/*{{{ Kra-40 : DEM - Personnalisation historique demandes de crédits clients */
elseif ($global_nom_ecran == "Kra-40") {
  if ($global_nom_ecran_prec == "Kra-1"){
 	   $html = new HTML_GEN2(_("Personnalisation du rapport"));
 	   //Remettre $global_id_agence à l'identifiant de l'agence courante
 	   resetGlobalIdAgence();
 	   //suppression variable session
 	   unset($SESSION_VARS['liste_criteres']);
 	   unset( $SESSION_VARS['dem_client']);
 	   //Agence- Tri par agence
 	   $list_agence = getAllIdNomAgence();
 	   if (isSiege()) {
 	      unset ($list_agence[$global_id_agence]);
 	      $html->addField("agence", _("Agence"), TYPC_LSB);
 	      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
 	      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
 	      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
 	   }

 	   $html->addField("date_deb", _("Date de début"), TYPC_DTE);
 	   $html->setFieldProperties("date_deb", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
 	   $html->setFieldProperties("date_deb", FIELDP_IS_REQUIRED, true);
 	   $html->addField("date_fin", _("Date de fin"), TYPC_DTE);
 	   $html->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
 	   $html->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);
 	   $html->addField("num_client", _("Client"), TYPC_INT);
 	   $html->addLink("num_client", "rechercher", _("Rechercher"), "#");
 	   $html->setLinkProperties("rechercher", LINKP_JS_EVENT, array ("onclick" => "OpenBrw('../modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', 'Recherche');"));
 	   foreach ($adsys["adsys_etat_dossier_credit"] as $key => $name) {
 	          $rapports[$key] = adb_gettext($name);
 	   }
 	   $html->addField("etat_dossier", _("Choix de l'état du dossier"), TYPC_LSB);
 	   $html->setFieldProperties("etat_dossier", FIELDP_IS_REQUIRED, false);
 	   $html->setFieldProperties("etat_dossier", FIELDP_ADD_CHOICES, $rapports);
 	   $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_AUCUN, false);
 	   $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_TOUS, true);
 	   $html->addTableRefField("prd", _("Type de produit de crédit"), "adsys_produit_credit");
 	   $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_AUCUN, false);
 	   $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_TOUS, true);

       $html->addField("limite", _("Limite "), TYPC_INT);
       $html->setFieldProperties("limite", FIELDP_DEFAULT, 5000);

 	   $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
 	   $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-41");
     //$html->setFormButtonProperties("valider", BUTP_CHECK_FORM, false);
     $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
     $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-51");
 	   $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
 	   $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-51");
     //$html->setFormButtonProperties("csv", BUTP_CHECK_FORM, false);
 	   $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
 	   $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
 	   $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
 	   $html->buildHTML();
 	   echo $html->getHTML();
 	} else {
 	   $html = new HTML_GEN2(_("Rapport Suivant"));

 	   $html->addFormButton(1, 1, "valider", _("Rapport PDF Suivant"), TYPB_SUBMIT);
 	   $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-41");
     //$html->setFormButtonProperties("valider", BUTP_CHECK_FORM, false);
     $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
     $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-51");
 	   $html->addFormButton(1, 3, "csv", _("Export CSV Suivant"), TYPB_SUBMIT);
 	   $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-51");
 	   $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
 	   $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
 	   $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
 	   $html->buildHTML();
 	   echo $html->getHTML();
 	}
}
/*}}}*/

/*{{{ Kra-41 et Kra- 51 : DEM - Impression ou export csv historique demandes de crédits clients */
elseif ($global_nom_ecran == "Kra-41" || $global_nom_ecran == "Kra-51") {
  setGlobalIdAgence($agence);
  $DATA = array ();
  $nombre_limit=4000; // nombre  limite de client à imprimer
  if ( !isset($SESSION_VARS['liste_criteres']))
  	$SESSION_VARS['liste_criteres'] = array ();
  if (!empty ($date_deb)) {
     $DATA["date_deb"] = $date_deb;
     $SESSION_VARS['liste_criteres'][_("Date de début")] = $date_deb;
     $SESSION_VARS['dem_client']['date_deb'] = $date_deb;
  }
  if (!empty ($date_fin)) {
     $DATA["date_fin"] = $date_fin;
     $SESSION_VARS['liste_criteres'][_("Date de fin")] = $date_fin;
     $SESSION_VARS['dem_client']['date_fin'] = $date_fin;
  }
  if (!empty ($num_client)) {
     $DATA["num_client"] = $num_client;
     $SESSION_VARS['dem_client']['num_client'] = $num_client;
     $statut_juridique = getStatutJuridiqueClient($num_client);
     if($statut_juridique == 1){
       $nom_cli = getClientNamePP($num_client);
     } else
     if($statut_juridique == 2){
       $nom_cli = getClientNamePM($num_client);
     } else
     if($statut_juridique == 3 || $statut_juridique == 4){
       $nom_cli = getClientNameGI($num_client);
     }
     $SESSION_VARS['liste_criteres'][_("Numéro Client")] = $num_client;
     $SESSION_VARS['liste_criteres'][_("Nom client")] = $nom_cli;
  }
  if ($etat_dossier > 0) {
     $DATA["etat"] = $etat_dossier;
     $SESSION_VARS['liste_criteres'][_("Etat du dossier")] = adb_gettext($adsys["adsys_etat_dossier_credit"][$etat_dossier]);
     $SESSION_VARS['dem_client']['etat'] = $etat_dossier;
  }
  if (!empty ($prd)) {
     $DATA["produit"] = $prd;
     $libel_prd = getLibelPrdt($prd, "adsys_produit_credit");
     $SESSION_VARS['liste_criteres'][_("Produit")] = $libel_prd;
     $SESSION_VARS['dem_client']['id_prod'] = $prd;

  }

    // Validate limit
    if ($limite <=0 || $limite > 10000) {
        $limite = 5000;
    }

  $count = 0;
  if (!isset($SESSION_VARS['dem_client']['sequence'])) {
     $SESSION_VARS['dem_client']['sequence'] = 0;
     $i = 0;
  }
  $i = $SESSION_VARS['dem_client']['sequence'];
  //Si un client n'est pas choisi, générer par palier
  if ($SESSION_VARS['dem_client']['num_client'] == NULL || $SESSION_VARS['dem_client']['num_client'] == '') {
     //optimisation par palier de 4000
     if (!isset($nombre)) {
        $num_req = 1;
 	      $nombre = getNbreClients($SESSION_VARS['dem_client']['date_deb'], $SESSION_VARS['dem_client']['date_fin'], $num_req);
 	   }
 	   //Formation du tableau contenant les données à afficher
 	   $lignes = getHisDdeCrd($SESSION_VARS['dem_client'], 0, $i);
 	   //On recupère le nombre de lignes générées par la requête pour la condition d'arret
 	   $count = sizeof($lignes);
 	   //recupération du dernier client
 	   $tab = end($lignes);
 	   $dernier_id_client = $tab['id_client'];
 	   $SESSION_VARS['liste_criteres'][_("Du Client N°")] = $i;
 	   $SESSION_VARS['liste_criteres'][_("Au Client N°")] = $dernier_id_client;
 	   //Génération du xml
 	   //$xml = xml_his_dde_crd($lignes, $SESSION_VARS['liste_criteres']);
 	} else {
 	   $lignes = getHisDdeCrd($SESSION_VARS['dem_client']);
 	   //$xml = xml_his_dde_crd($lignes, $SESSION_VARS['liste_criteres']);
 	}

 	if ($lignes != NULL) {
 	   //$xml = xml_his_dde_crd($lignes, $liste_criteres);
 	   if ($global_nom_ecran == "Kra-51") {

           $xml = xml_his_dde_crd($lignes, $SESSION_VARS['liste_criteres'], true);

 	      //Génération du CSV grâce à XALAN
 	      $csv_file = xml_2_csv($xml, 'his_dde_cli.xslt');

 	     //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
 	     $i = $i + $nombre_limit; // nombre de clients à imprimer
 	     if ($i > $nombre){
 	        $i = $nombre;
 	     }
 	     $SESSION_VARS['dem_client']['sequence']=$i;
 	     if ($i != $dernier_id_client) {
 	        $i = $dernier_id_client;
 	        $SESSION_VARS['dem_client']['sequence'] = $i;
 	     } 
 	     if (($count < $nombre_limit || $nombre == $nombre_limit) && ($count >= 0)){
         if (isset($excel) && $excel == 'Export EXCEL'){
           echo getShowEXCELHTML("Gen-13", $csv_file);
         }
         else{
 	        echo getShowCSVHTML("Gen-13", $csv_file);//get_show_pdf_html("Gen-13", $csv_file);//getShowCSVHTML
         }
 	     } else {
         if (isset($excel) && $excel == 'Export EXCEL'){
           echo getShowEXCELHTML("Gen-13", $csv_file);
         }
         else{
 	        echo getShowCSVHTML("Kra-51", $csv_file);
         }
 	        $SESSION_VARS['dem_client']['sequence'] = $i;
 	     }
 	} else
 	  if ($global_nom_ecran == "Kra-41") {

          $dataCount = 0;
          $indexCount = 0;
          $lignes_bis = array();
          $fichier_pdf_arr = array();

          foreach($lignes as $key=>$data){
              $lignes_bis[$key] = $data;

              $dataCount++;

              if ($dataCount%$limite == 0 || $count == $dataCount ) {
                  $indexCount++;

                  //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                  $xml = xml_his_dde_crd($lignes_bis, $SESSION_VARS['liste_criteres']);

                  $fichier_pdf_arr[] = xml_2_xslfo_2_pdf($xml, 'his_dde_crd.xslt',false,$indexCount);
                  $lignes_bis = array();
              }
          }

          $fileCount = 1;
          $js="";
          foreach($fichier_pdf_arr as $fichier_pdf) {
              // Compilation des rapports pdf générés
              $js .= get_show_pdf_html(NULL, $fichier_pdf, NULL, "Rapport Historique demande de crédits Clients no. $fileCount", $fileCount,(200+($fileCount*50)));
              $fileCount++;
          }

          if ($js!="") {
              $MyPage = new HTML_message(_("Génération rapport"));
              $MyPage->setMessage(_("Le rapport a été généré avec succès !"));
              $MyPage->addButton(BUTTON_OK, "Gen-13");
              $MyPage->buildHTML();
              echo $MyPage->HTML_code." ".$js;
          } else {
              $erreur = new HTML_erreur(_("Echec lors de la génération du rapport"));
              $erreur->setMessage(_("Aucun rapport n'a été trouvé."));
              $erreur->addButton(BUTTON_OK, "Gen-13");
              $erreur->buildHTML();
              return $erreur->HTML_code;
          }

 	     //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
 	     //$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'his_dde_crd.xslt');
 	     //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
         /*
 	     $i = $i + $nombre_limit; // nombre de clients à imprimer
 	     if ($i > $nombre){
 	        $i = $nombre;
 	     }
 	     $SESSION_VARS['dem_client']['sequence']=$i;
 	     if ($i != $dernier_id_client) {
 	        $i = $dernier_id_client;
 	        $SESSION_VARS['dem_client']['sequence'] = $i;
 	     } 
 	        if (($count < $nombre_limit || $nombre == $nombre_limit) && ($count >= 0)){
 	        echo get_show_pdf_html("Gen-13", $fichier_pdf);
 	     } else {
 	        echo get_show_pdf_html("Kra-40", $fichier_pdf);
 	        $SESSION_VARS['dem_client']['sequence'] = $i;
 	     }
         */
 	  }
 	  ajout_historique(350, NULL, NULL, $global_nom_login, date("r"), NULL);

 	} else {
 	    $html_msg = new HTML_message(_("Résultats de la requête"));
 	    $html_msg->setMessage(_("Aucune donnée sélectionnée"));
 	    $html_msg->addButton("BUTTON_OK", 'Gen-13');
 	    $html_msg->buildHTML();
 	    echo $html_msg->HTML_code;
 	}
}
/*}}}*/

/*{{{ Kra-42 : MAX - Personnalisation débiteurs les plus importants */
elseif ($global_nom_ecran == 'Kra-42') {
                        $html = new HTML_GEN2(_("Personnalisation du rapport"));

                        //Remettre $global_id_agence à l'identifiant de l'agence courante
				        resetGlobalIdAgence();
				        //Agence- Tri par agence
				        $list_agence = getAllIdNomAgence();
				        if (isSiege()) {
					      unset ($list_agence[$global_id_agence]);
					      $html->addField("agence", _("Agence"), TYPC_LSB);
					      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
					      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
					      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
					    }

                        $html->addField("nombre_cli", _("Nombre de clients à afficher"), TYPC_INT);
                        $html->setFieldProperties("nombre_cli", FIELDP_DEFAULT, 10);
                        $html->setFieldProperties("nombre_cli", FIELDP_IS_REQUIRED, true);

                        // encours minimum
                        $html->addField("mnt_min", _("Encours minimum"), TYPC_MNT);

                        //Gestionnaire- Tri par agent gestionnaire
                        $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
                        $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
                        $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

                        $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
                        $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-43");
                        $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
                        $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-53");
                        $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
                        $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-53");

                        $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
                        $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
                        $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
                        $html->buildHTML();
                        echo $html->getHTML();
                      }
/*}}}*/

/*{{{ Kra-43 et Kra-53 : MAX - Impression ou export csv des encours de crédit les plus importants */
elseif ($global_nom_ecran == 'Kra-43' || $global_nom_ecran == 'Kra-53') {
  setGlobalIdAgence($agence);
  // récupération de la saisie
  if ($gest == '') // le gestionnaire
    $gest = NULL;
  if ($gest > 0) //Gestionnaire
   $list_criteres = array ();
  $list_criteres = array (_("Gestionnaire") => (getLibel("ad_uti", $gest) == "")?_("Tous"):getLibel("ad_uti", $gest));
  if ($mnt_min == '') // l'encours minimum
    $mnt_min = NULL;
  else
    $mnt_min = recupMontant($mnt_min);

  // récupération des encours les plus grands
  $DATA = getListeClientsDebiteurs($nombre_cli, $mnt_min, $gest); 
  $xml = xml_liste_clients_deb($DATA, $nombre_cli, $list_criteres);

  if ($global_nom_ecran == 'Kra-53') {
    //Génération du CSV grâce à XALAN
    $xml = xml_liste_clients_deb($DATA, $nombre_cli,$list_criteres, true);
    $csv_file = xml_2_csv($xml, 'liste_clients_deb.xslt');

    //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
    if (isset($excel) && $excel == 'Export EXCEL'){
      echo getShowEXCELHTML("Gen-13", $csv_file);
    }
    else{
      echo getShowCSVHTML("Gen-13", $csv_file);
    }
  }
  elseif ($global_nom_ecran == 'Kra-43') {
    //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
    $xml = xml_liste_clients_deb($DATA, $nombre_cli, $list_criteres);
    $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'liste_clients_deb.xslt');

    //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
    echo get_show_pdf_html("Gen-13", $fichier_pdf);
  }
}
/*}}}*/

/*{{{ Kra-44 et Kra-64 : EMP - Impression ou export csv Crédits accordés aux employés */
elseif ($global_nom_ecran == 'Kra-44' || $global_nom_ecran == 'Kra-64') {
  setGlobalIdAgence($agence);
  if ($gest == "")
    $gest = 0;
  elseif ($gest > 0)
  $list_criteres = array ();
  $list_criteres = array (
                     _("Gestionnaire") => (getLibel("ad_uti", $gest) == "")?_("Tous"):getLibel("ad_uti", $gest)
                   );
  $DATA = getCreditsEmployesDirigeants($gest);

  if ($global_nom_ecran == 'Kra-64') {
    //Génération du CSV grâce à XALAN
    $xml = xml_liste_credits_emp_dir($DATA, $list_criteres, true);
    $csv_file = xml_2_csv($xml, 'liste_credits_emp_dir.xslt');

    //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
    if (isset($excel) && $excel == 'Export EXCEL'){
      echo getShowEXCELHTML("Gen-13", $csv_file);
    }
    else{
      echo getShowCSVHTML("Gen-13", $csv_file);
    }
  } else
    if ($global_nom_ecran == 'Kra-44') {
      //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
      $xml = xml_liste_credits_emp_dir($DATA, $list_criteres);
      $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'liste_credits_emp_dir.xslt');

      //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
      echo get_show_pdf_html("Gen-13", $fichier_pdf);
    }
}
/*}}}*/

/*{{{ Kra-13 : MAX - Personnalisation débiteurs en crédit et découvert */
elseif ($global_nom_ecran == 'Kra-13') {
		$html = new HTML_GEN2(_("Personnalisation du rapport"));

		//Remettre $global_id_agence à l'identifiant de l'agence courante
        resetGlobalIdAgence();
        //Agence- Tri par agence
        $list_agence = getAllIdNomAgence();
        if (isSiege()) {
	      unset ($list_agence[$global_id_agence]);
	      $html->addField("agence", _("Agence"), TYPC_LSB);
	      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
	      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
	      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
	    }
		if ($global_multidevise) {
       $html->addTable("ad_cpt_comptable", OPER_INCLUDE, array ("devise"));
 	     $html->setFieldProperties("devise", FIELDP_LONG_NAME, _("Devise"));
 	     $html->setFieldProperties("devise", FIELDP_HAS_CHOICE_AUCUN, true);
 	     $html->setFieldProperties("devise", FIELDP_IS_REQUIRED, true);
 	  }
		$html->addField("debiteur", _("Débiteur"), TYPC_LSB);
		$html->setFieldProperties("debiteur", FIELDP_ADD_CHOICES, array (
			"cre" => _("Crédit"),
			"dec" => _("Découvert"),
			"credec" => _("Crédit et Découvert")
		));
		$html->setFieldProperties("debiteur", FIELDP_HAS_CHOICE_AUCUN, true);
		$html->setFieldProperties("debiteur", FIELDP_HAS_CHOICE_TOUS, false);
		$html->setFieldProperties("debiteur", FIELDP_IS_REQUIRED, true);
		$html->addField("clien_cpte", _("Tri"), TYPC_LSB);
 	  $html->setFieldProperties("clien_cpte", FIELDP_ADD_CHOICES, array ("cli" => _("Client"),"cpte" => _("Compte")));
 	  $html->setFieldProperties("clien_cpte", FIELDP_HAS_CHOICE_AUCUN, true);
 	  $html->setFieldProperties("clien_cpte", FIELDP_HAS_CHOICE_TOUS, false);
 	  $html->setFieldProperties("clien_cpte", FIELDP_IS_REQUIRED, true);
		$html->addField("selection", _("Sélection"), TYPC_LSB);
		$html->setFieldProperties("selection", FIELDP_ADD_CHOICES, array (
			"nbre" => _("Par nombre"),
			"mnt" => _("Par montant")
		));
		$html->setFieldProperties("selection", FIELDP_HAS_CHOICE_AUCUN, true);
		$html->setFieldProperties("selection", FIELDP_HAS_CHOICE_TOUS, false);
		$html->setFieldProperties("selection", FIELDP_IS_REQUIRED, true);
		// encours minimum
		$js_fct = " function set_disabled(mnt_p1){";
		$js_fct .= "document.ADForm.palier1_nombre.disabled = mnt_p1;";
		$js_fct .= "}";
		$js_fct .= " function set_disabled2(mnt_p1, mnt_p2){";
		$js_fct .= " document.ADForm.palier1_mnt.disabled = mnt_p1;";
		$js_fct .= " document.ADForm.palier2_mnt.disabled = mnt_p2;";
		$js_fct .= "}";
		$js = "if (document.ADForm.HTML_GEN_LSB_selection.value == 'nbre'){";
		$js .= " set_disabled(false);";
		$js .= " set_disabled2(true, true);";
		$js .= "}else{";
		$js .= " set_disabled(true);";
		$js .= " set_disabled2(false, false);";
		$js .= "}";

		//Javascript de contrôle
        //Check si champs obligatoires sont renseignés
        $_js = "msg = ''; ADFormValid = true;";
        $_js .= "if (((document.ADForm.palier1_nombre.value == '')) && (document.ADForm.HTML_GEN_LSB_selection.value == 'nbre')) {msg += '"._("Le champs  Nombre doit être renseigné !")."\\n'; ADFormValid = false;}";
        $_js .= "if (((document.ADForm.palier1_mnt.value == '') || (document.ADForm.palier2_mnt.value == '')) && (document.ADForm.HTML_GEN_LSB_selection.value == 'mnt')) {msg += '"._("Les champs Palier 1 montant et Palier 2 montant doivent être renseignés !")."\\n'; ADFormValid = false;}";
        //$_js .= "if ((document.ADForm.palier1_mnt.value > document.ADForm.palier2_mnt.value) && (document.ADForm.HTML_GEN_LSB_selection.value == 'mnt'))  {msg += 'Les champs Palier 1 montant ne doit pas être plus grand que Palier 2 montant !\\n'; ADFormValid = false;}";
        $html->addJS(JSP_BEGIN_CHECK, "js3", $_js);
		$html->setFieldProperties("selection", FIELDP_JS_EVENT, array (
			"onchange" => $js
		));
		//Palier nombre 1
		$html->addField("palier1_nombre", _("Nombre"), TYPC_INT);
		$html->setFieldProperties("palier1_nombre", FIELDP_IS_REQUIRED, true);
		$html->setFieldProperties("palier1_nombre", FIELDP_IS_LABEL, true);
		//Palier montant 1
		$html->addField("palier1_mnt", _("Palier 1 Montant"), TYPC_MNT);
		$html->setFieldProperties("palier1_mnt", FIELDP_IS_REQUIRED, true);
		$html->setFieldProperties("palier1_mnt", FIELDP_IS_LABEL, true);
		//Palier montant 2
		$html->addField("palier2_mnt", _("Palier 2 Montant"), TYPC_MNT);
		$html->setFieldProperties("palier2_mnt", FIELDP_IS_REQUIRED, true);
		$html->setFieldProperties("palier2_mnt", FIELDP_IS_LABEL, true);
		$html->addJS(JSP_FORM, "js0", $js_fct);
		//Gestionnaire- Tri par agent gestionnaire
		$html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
		$html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
		$html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

		$html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
		$html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-52");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-54");
		$html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
		$html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-54");

		$html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
		$html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
		$html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
		$html->buildHTML();
		echo $html->getHTML();
	}
/*}}}*/

/*{{{ Kra-52 et Kra-54 : MAX - Impression ou export csv de la liste des clients les plus débiteurs en crédits */
elseif ($global_nom_ecran == 'Kra-52' || $global_nom_ecran == 'Kra-54') {
	global $global_monnaie;
	setGlobalIdAgence($agence);
	// récupération de la saisie
	$list_criteres = array ();
	if ($gest == '') // le gestionnaire
		$gest = NULL;
	if($gest > 0)
	  $list_criteres = array (
		  "Gestionnaire" => getLibel("ad_uti",
		  $gest
	  ));
	if($clien_cpte == 'cli'){
	  $list_criteres[_('Client')] = _("Tri par client") ;
	}else{
		$list_criteres[_('Client')] = _("Tri par compte ou dossier de crédit") ;
	}
	if($debiteur == 'cre'){
	  $list_criteres[_('Débiteur')] = _("Crédit") ;
	}elseif($debiteur == 'dec'){
		$list_criteres[_('Débiteur')] = _("Compte") ;
	}else{
		$list_criteres[_('Débiteur')] = _("Crédit et Compte");
	}

	if ($mnt_min == '') // l'encours minimum
		$mnt_min = NULL;
	else
		$mnt_min = recupMontant($mnt_min);
 	// récupération des encours les plus grands
 	if ($devise == NULL) {
     $devise = $global_monnaie;
  }

 	$DATA = getListeClientsDebiteursCredit($clien_cpte, $debiteur, $selection, $palier1_nombre, recupMontant($palier1_mnt), recupMontant($palier2_mnt), $gest,$devise);
 	if ($global_nom_ecran == 'Kra-54') {
		//Génération du CSV grâce à XALAN
		$xml = xml_liste_clients_deb_crediteur($DATA, $list_criteres, true,$devise);
		$csv_file = xml_2_csv($xml, 'liste_client_deb_cred.xslt');

		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
    if (isset($excel) && $excel == 'Export EXCEL'){
      echo getShowEXCELHTML("Gen-13", $csv_file);
    }
    else{
		  echo getShowCSVHTML("Gen-13", $csv_file);
    }
	}
	elseif ($global_nom_ecran == 'Kra-52') {
		//Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
		$xml = xml_liste_clients_deb_crediteur($DATA, $list_criteres,false,$devise);
		$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'liste_client_deb_cred.xslt');

		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo get_show_pdf_html("Gen-13", $fichier_pdf);
	}
}
/*}}}*/

/*{{{ Kra-65 : EMP - Crédits accordés aux employés */
elseif ($global_nom_ecran == 'Kra-65') {
    $html = new HTML_GEN2(_("Selection type rapport Crédits accordés aux employés"));
     //Remettre $global_id_agence à l'identifiant de l'agence courante
    resetGlobalIdAgence();
    //Agence- Tri par agence
    $list_agence = getAllIdNomAgence();
    if (isSiege()) {
      unset ($list_agence[$global_id_agence]);
      $html->addField("agence", _("Agence"), TYPC_LSB);
      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
    }

    //Gestionnaire- Tri par agent gestionnaire
    $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
    $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

    $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-44");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-64");
    $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-64");

    $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
    $html->buildHTML();
    echo $html->getHTML();
  }
/*}}}*/

/*{{{ Kra-45 : PRT - Personnalisation crédits passés en perte */
elseif ($global_nom_ecran == 'Kra-45') {
      $html = new HTML_GEN2(_("Personnalisation du rapport"));

    //Remettre $global_id_agence à l'identifiant de l'agence courante
    resetGlobalIdAgence();
    //Agence- Tri par agence
    $list_agence = getAllIdNomAgence();
    if (isSiege()) {
      unset ($list_agence[$global_id_agence]);
      $html->addField("agence", _("Agence"), TYPC_LSB);
      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
    }

      $html->addField("date_deb", _("Date de début"), TYPC_DTE);
      $html->setFieldProperties("date_deb", FIELDP_DEFAULT, date("01/01/Y"));
      $html->setFieldProperties("date_deb", FIELDP_IS_REQUIRED, true);

      $html->addField("date_fin", _("Date de fin"), TYPC_DTE);
      $html->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
      $html->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);
      //Gestionnaire- Tri par agent gestionnaire
      $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
      $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
      $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

      //ajout du filtre etat : ticket trac 682
      $html->addField("etat", _("Etat Dossier"), TYPC_LSB);
      $choix = array (
        "1" => _("Passé en perte"),
        "2" => _("Soldé")
      );
      $html->setFieldProperties("etat", FIELDP_HAS_CHOICE_AUCUN, false);
      $html->setFieldProperties("etat", FIELDP_HAS_CHOICE_TOUS, true);
      $html->setFieldProperties("etat", FIELDP_ADD_CHOICES, $choix);

      $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
      $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-46");
      $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
      $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-56");
      $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
      $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-56");

      $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
      $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
      $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
      $html->buildHTML();
      echo $html->getHTML();
    }
/*}}}*/

/*{{{ Kra-46 et Kra-56 : PRT - Impression ou export csv crédits passés en perte */
elseif ($global_nom_ecran == 'Kra-46' || $global_nom_ecran == 'Kra-56') {
        $etat_dossier = 0; //ticket trac 682 : par defaut 0 pour filtre etat dossier tous
        setGlobalIdAgence($agence);
        if ($gest == "") {
          $gest = 0;
          $list_criteres = array(_("Gestionnaire") => _("Tous"));
        }	else	{
          if ($gest > 0) {
            $list_criteres = array(_("Gestionnaire") => getLibel("ad_uti", $gest));
          }
        }
        //ticket trac 682
        if ($_POST["etat"]==1){
          $etat_dossier =9; //passe en perte
          $list_criteres[_("Etat Dossier")] = "Passé en Perte";
        }
        if ($_POST["etat"]==2){
          $etat_dossier =6; //passe en perte et puis solde au meme periode
          $list_criteres[_("Etat Dossier")] = "Soldé";
        }
        if ($_POST["etat"]==null){
          $list_criteres[_("Etat Dossier")] = "Tous";
        }

        $DATA = getCreditsPerte($date_deb, $date_fin, $gest, $etat_dossier);

        if ($global_nom_ecran == 'Kra-56') {
          //Génération du CSV grâce à XALAN
          $xml = xml_credits_perte($DATA, $list_criteres, $date_deb, $date_fin, true);
          $csv_file = xml_2_csv($xml, 'credits_perte.xslt');

          //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
          if (isset($excel) && $excel == 'Export EXCEL'){
            echo getShowEXCELHTML("Gen-13", $csv_file);
          }
          else{
            echo getShowCSVHTML("Gen-13", $csv_file);
          }
        } else
          if ($global_nom_ecran == "Kra-46") {
            //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)

            $xml = xml_credits_perte($DATA, $list_criteres, $date_deb, $date_fin);
            $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'credits_perte.xslt');

            echo get_show_pdf_html("Gen-13", $fichier_pdf);
          }
      }
/*}}}*/

/*{{{ Kr a-47 : REG - Personnalisation registre des prêts */
elseif ($global_nom_ecran == 'Kra-47') {
      if($global_nom_ecran_prec == 'Kra-1' || isset($localisation_main)){
      	
      	// if(isset($SESSION_VARS['date_deb'],$SESSION_VARS['date_fin'])){
      	unset($SESSION_VARS['sequence'],$SESSION_VARS['date_deb'],$SESSION_VARS['date_fin'],$SESSION_VARS['num_client'],$SESSION_VARS['nom_client'],$SESSION_VARS['id_prod'],$SESSION_VARS['etat_dossier']);
      	
      	// }
      	
      	$html = new HTML_GEN2(_("Sélection des critères"));
          //Remettre $global_id_agence à l'identifiant de l'agence courante
		    resetGlobalIdAgence();
        $agence_data = getAgenceDatas($global_id_agence);
		    //Agence- Tri par agence
		    $list_agence = getAllIdNomAgence();
		    if (isSiege()) {
		      unset ($list_agence[$global_id_agence]);
		      $html->addField("agence", _("Agence"), TYPC_LSB);
		      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
		      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
		      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
		    }

          if ($global_multidevise) {
            $html->addTable("ad_cpt_comptable", OPER_INCLUDE, array (
                              "devise"
                            ));
            $html->setFieldProperties("devise", FIELDP_LONG_NAME, _("Devise"));
            $html->setFieldProperties("devise", FIELDP_HAS_CHOICE_AUCUN, false);
            $html->setFieldProperties("devise", FIELDP_HAS_CHOICE_TOUS, false);
          }

          $html->addTableRefField("statut", _("Statut juridique"), "adsys_stat_jur");
          $html->setFieldProperties("statut", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("statut", FIELDP_HAS_CHOICE_TOUS, true);

          $js = "if(document.ADForm.HTML_GEN_LSB_statut.value == 1)  document.ADForm.HTML_GEN_LSB_pp_sexe.disabled = false ;";
          $js .= " else document.ADForm.HTML_GEN_LSB_pp_sexe.disabled = true ;";

          $html->setFieldProperties("statut", FIELDP_JS_EVENT, array (
                                      "onchange" => $js
                                    ));
          //AT-33/AT-79 - Localisation Rwanda
          if ($agence_data['identification_client'] == 2 ){
            $array_localisation_rwanda = array(
              1 => "Province",
              2 => "District",
              3 => "Secteur",
              4 => "Cellule",
              5 => "Village"
            );
            $html->addField("localisation_main", _("Niveau de Localisation"), TYPC_LSB);
            $html->setFieldProperties("localisation_main", FIELDP_ADD_CHOICES, $array_localisation_rwanda);
            $html->setFieldProperties("localisation_main", FIELDP_HAS_CHOICE_AUCUN, true);
            $html->setFieldProperties("localisation_main", FIELDP_HAS_CHOICE_TOUS, false);
            $html->setFieldProperties("localisation_main", FIELDP_JS_EVENT, array("onChange"=>"assign('Kra-47'); this.form.submit();"));

            /***********************************************************************************/
            //tableau des écritures diverses
            $ExtraHtml = "<link rel=\"stylesheet\" href=\"/lib/misc/js/chosen/css/chosen.css\">";
            $ExtraHtml .= "<script src=\"/lib/misc/js/lib/jquery.min.js\" type=\"text/javascript\"></script>";
            $ExtraHtml .= "<script src=\"/lib/misc/js/chosen/chosen.jquery.js\" type=\"text/javascript\"></script>";

            $ExtraHtml .= "<TABLE align=\"left\" >\n";

            //En-tête du tableau
            $ExtraHtml .= "<TR bgcolor=$color>\n";


            $ExtraHtml .= "<TD width='203px'>\n";
            $ExtraHtml .= "<label>Critère de localisation  </label>";
            $ExtraHtml .= "</TD>\n";
            $ExtraHtml .= "<TD>\n";
            $ExtraHtml .= "<select required class=\"chosen-select\" NAME=\"crit_loc\" ID=\"localisation_crit\"  style=\"width:160px\" "  ;
            $ExtraHtml .= "\">\n";
            $ExtraHtml .= "<option value=\"0\">["._("Tous")."]</option>\n";
            if (isset($localisation_main)){//Page Reloaded
              $locArrayRwanda = getLocRwandaSelectedArray();
              reset($locArrayRwanda);
              while (list (, $value_rwanda) = each($locArrayRwanda)) {
                if ($value_rwanda['type_localisation'] == $localisation_main){
                  $ExtraHtml .= "<option value=".$value_rwanda['id'].">".$value_rwanda['libelle_localisation']."</option>\n";
                }
              }
            }
            $ExtraHtml .= "</select>\n";
            $ExtraHtml .= "</TD>";


            $ExtraHtml .= "</TR>";
            $ExtraHtml .= "</TABLE>\n";


            $ExtraHtml .= "<script type=\"text/javascript\">\n";
            $ExtraHtml .= "var config = { '.chosen-select' : {} }\n";
            $ExtraHtml .= "for (var selector in config) {\n";
            $ExtraHtml .= "$(selector).chosen(config[selector]); }\n";

            $ExtraHtml .= "</script>\n";

            $html->addHTMLExtraCode("html2",$ExtraHtml);
            $html->setHTMLExtraCodeProperties("html2", HTMP_IN_TABLE, true);
            /***********************************************************************************/

            //AT-79 : Evolution rapport Registre des prets après AT-33
            $locArrayRwanda = getLocRwandaSelectedArray();
            // --> Sélection des champs à afficher dans id_loc
            reset($locArrayRwanda);
            $includeChoicesRwanda = array();
            while (list (, $value_rwanda) = each($locArrayRwanda)) {
              if ($value_rwanda['parent'] == 0)
                array_push($includeChoicesRwanda, $value_rwanda['id']);

            }
            $jsCodeLocRwanda = "function displayLocsRwanda() {\n";
            $jsCodeLocRwanda .= "\t for (i=0; i < document.getElementById('localisation_crit').options.length; i++) {\n\t\t document.getElementById('localisation_crit').options[i] = null;}\n"; //Vide les choixdocument.getElementById('localisation_crit').options[i] = null;
            $jsCodeLocRwanda .= "\t document.getElementById('localisation_crit').length = 0;\n";
            $jsCodeLocRwanda .= "\t document.getElementById('localisation_crit').options[document.getElementById('localisation_crit').options.length] = new Option('[Tous]', 0, true, true);\n"; //[Aucun]
            $jsCodeLocRwanda .= "\t document.getElementById('localisation_crit').selectedIndex = 0; document.getElementById('localisation_crit').length = 1; \n";
            reset($locArrayRwanda);
            while (list (, $value_rwanda) = each($locArrayRwanda)) {
              if ($value_rwanda['type_localisation'] != '') {
                $jsCodeLocRwanda .= "\t if (document.ADForm.HTML_GEN_LSB_localisation_main.value == " . $value_rwanda['type_localisation'] . "){\n";
                $jsCodeLocRwanda .= "\t\t document.getElementById('localisation_crit').options[document.getElementById('localisation_crit').options.length] = new Option('" . $value_rwanda['libelle_localisation'] . "', " . $value_rwanda['id'] . ", false, true);}\n";
               }
            }
            $jsCodeLocRwanda .= "\n}";
            //$html->setFieldProperties("localisation_main", FIELDP_JS_EVENT, array("onchange" => "displayLocsRwanda()"));


          }else {
            $html->addTableRefField("localisation", _("Localisation"), "adsys_localisation");
            $html->setFieldProperties("localisation", FIELDP_HAS_CHOICE_AUCUN, false);
            $html->setFieldProperties("localisation", FIELDP_HAS_CHOICE_TOUS, true);
          }

          $html->addTableRefField("pp_sexe", _("Sexe"), "adsys_sexe");
          $html->setFieldProperties("pp_sexe", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("pp_sexe", FIELDP_HAS_CHOICE_TOUS, true);
          $html->setFieldProperties("pp_sexe", FIELDP_IS_LABEL, true);

          $html->addTableRefField("sect_act", _("Secteur d'activité"), "adsys_sect_activite");
          $html->setFieldProperties("sect_act", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("sect_act", FIELDP_HAS_CHOICE_TOUS, true);

          $html->addTableRefField("prod", _("Produit de crédit"), "adsys_produit_credit");
          $html->setFieldProperties("prod", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("prod", FIELDP_HAS_CHOICE_TOUS, true);

          $html->addTableRefField("objet", _("Objet de crédit"), "adsys_objets_credits");
          $html->setFieldProperties("objet", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("objet", FIELDP_HAS_CHOICE_TOUS, true);

          $html->addField("date_debloc_inf", _("Date début du déboursement"), TYPC_DTE);
          $html->setFieldProperties("date_debloc_inf", FIELDP_DEFAULT, date("01/01/Y"));
          $html->setFieldProperties("date_debloc_inf", FIELDP_IS_REQUIRED, false);

          $html->addField("date_debloc_sup", _("Date fin de déboursement"), TYPC_DTE);
          $html->setFieldProperties("date_debloc_sup", FIELDP_DEFAULT, date("d/m/Y"));
          $html->setFieldProperties("date_debloc_sup", FIELDP_IS_REQUIRED, false);

          $js="
                function parseDate(str) {
                    var mdy = str.split('/');
                    return new Date(mdy[2], mdy[1]-1, mdy[0]);
                                        };

                function daydiff(deb, fin) {
                    return Math.round((fin-deb)/(1000*60*60*24));
                    };

                if (document.ADForm.HTML_GEN_LSB_type_affich.value == 1) {
                    var deb = parseDate(document.ADForm.HTML_GEN_date_date_debloc_inf.value);
                    var fin = parseDate(document.ADForm.HTML_GEN_date_date_debloc_sup.value);

                    var diff= daydiff(deb,fin);

                    if (diff > 366){
                        alert('La durée entre date début et date fin du rapport ne doit pas dépasser 1 an');
                        ADFormValid=false;
                    }
                }
               ";

          $html->addJS (JSP_BEGIN_CHECK, "JS",$js );


          $html->addTableRefField("type_duree", _("Type durée"), "adsys_type_duree_credit");
          $html->setFieldProperties("type_duree", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("type_duree", FIELDP_HAS_CHOICE_TOUS, true);

          $html->addField("duree_mois", _("Durée du crédit"), TYPC_INT);

          $html->addField("nb_reech", _("Nombre de rééchelonnement"), TYPC_INT);

          $html->addField("etat_dossier", _("Etat dossier"), TYPC_LSB);
          // On ne permet que les états suivants, les autres n'ont pas bcp de sens pour ce rapport
          $etats_dcr_dispos[3] = adb_gettext($adsys["adsys_etat_dossier_credit"][3]);//rejetés
          $etats_dcr_dispos[5] = adb_gettext($adsys["adsys_etat_dossier_credit"][5]);//déboursés
          $etats_dcr_dispos[6] = adb_gettext($adsys["adsys_etat_dossier_credit"][6]);//soldés
          $etats_dcr_dispos[7] = adb_gettext($adsys["adsys_etat_dossier_credit"][7]);//attente rééchelonnement
          $etats_dcr_dispos[9] = adb_gettext($adsys["adsys_etat_dossier_credit"][9]);//en perte
          $etats_dcr_dispos[13] = adb_gettext($adsys["adsys_etat_dossier_credit"][13]);//En déboursement progressif
          $etats_dcr_dispos[14] = adb_gettext($adsys["adsys_etat_dossier_credit"][14]);//En attente approbation modification date
          $etats_dcr_dispos[15] = adb_gettext($adsys["adsys_etat_dossier_credit"][15]);//En attente approbation raccourcissement durée
          $html->setFieldProperties("etat_dossier", FIELDP_ADD_CHOICES, $etats_dcr_dispos);
          $html->setFieldProperties("etat_dossier", FIELDP_DEFAULT, 5);
          $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_TOUS, true);
          //Gestionnaire- Tri par agent gestionnaire
          $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
          $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
          $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);
          	// Type afficahge
					$html->addField("type_affich", _("Affichage"), TYPC_LSB);
					$html->setFieldProperties("type_affich", FIELDP_ADD_CHOICES, array(1 => _("Détaillé"), 2 => _("Synthétique")));
					$html->setFieldProperties("type_affich", FIELDP_HAS_CHOICE_AUCUN, false);

          $html->addField("limite", _("Limite "), TYPC_INT);
          $html->setFieldProperties("limite", FIELDP_DEFAULT, 5000);

        //AT-33/AT-79 - Reload page with pre-selected data
        if ($agence_data['identification_client'] == 2 && isset($localisation_main)) {
          $html->setFieldProperties("localisation_main", FIELDP_DEFAULT, $localisation_main);
          if (isset($duree_mois)){
            $html->setFieldProperties("duree_mois", FIELDP_DEFAULT, $duree_mois);
          }
          if (isset($nb_reech)){
            $html->setFieldProperties("nb_reech", FIELDP_DEFAULT, $nb_reech);
          }
          if (isset($limite)){
            $html->setFieldProperties("limite", FIELDP_DEFAULT, $limite);
          }
          if (isset($date_debloc_inf) && $date_debloc_inf != null){
            $html->setFieldProperties("date_debloc_inf", FIELDP_DEFAULT, $date_debloc_inf);
          }
          if (isset($date_debloc_sup) && $date_debloc_sup != null){
            $html->setFieldProperties("date_debloc_sup", FIELDP_DEFAULT, $date_debloc_sup);
          }
          if (isset($statut)){
            $html->setFieldProperties("statut", FIELDP_DEFAULT, $statut);
          }
          if (isset($sect_act)){
            $html->setFieldProperties("sect_act", FIELDP_DEFAULT, $sect_act);
          }
          if (isset($prod)){
            $html->setFieldProperties("prod", FIELDP_DEFAULT, $prod);
          }
          if (isset($objet)){
            $html->setFieldProperties("objet", FIELDP_DEFAULT, $objet);
          }
          if (isset($type_duree)){
            $html->setFieldProperties("type_duree", FIELDP_DEFAULT, $type_duree);
          }
          if (isset($etat_dossier)){
            $html->setFieldProperties("etat_dossier", FIELDP_DEFAULT, $etat_dossier);
          }
          if (isset($gest)){
            $html->setFieldProperties("gest", FIELDP_DEFAULT, $gest);
          }
          if (isset($type_affich)){
            $html->setFieldProperties("type_affich", FIELDP_DEFAULT, $type_affich);
          }
        }




          $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
          $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-48");
          $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
          $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-58");
          $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
          $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-58");

          $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
          $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
          $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
          $html->buildHTML();
          echo $html->getHTML();
      } else {
      	  $html = new HTML_GEN2(_("Rapport Suivant"));

      	  $html->addFormButton(1, 1, "valider", _("Rapport PDF Suivant"), TYPB_SUBMIT);
          $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-48");
          $html->addFormButton(1, 2, "excel", _("Export EXCEL Suivant"), TYPB_SUBMIT);
          $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-58");
          $html->addFormButton(1, 3, "csv", _("Export CSV Suivant"), TYPB_SUBMIT);
          $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-58");

          $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
          $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
          $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
          $html->buildHTML();
          echo $html->getHTML();
      }

        }
/*}}}*/

/*{{{ Kra-48 et Kra-58 : REG - Impression ou export csv registre des prêts */
elseif ($global_nom_ecran == 'Kra-48' || $global_nom_ecran == 'Kra-58') {
            global $dbHandler;
            $db = $dbHandler->openConnection();

            global $adsys;
            $DATA = array ();
            $agence_data = getAgenceDatas($global_id_agence);
            setGlobalIdAgence($agence);
            if($SESSION_VARS['palier'] == false || $SESSION_VARS['palier'] == NULL){
            	if (!empty ($statut)) {
	              $DATA["statut"] = $statut;
	              $SESSION_VARS['statut'] = $statut;
	              $liste_criteres[_("Statut juridique")] = adb_gettext($adsys["adsys_stat_jur"][$statut]);
	              $SESSION_VARS['liste_criteres'][_("Statut juridique")] = adb_gettext($adsys["adsys_stat_jur"][$statut]);
	            }
              if ($agence_data['identification_client'] == 2){
                if (!empty ($localisation_main)) {
                  $DATA["localisation"] = $localisation_main;
                  $DATA["crit_loc"] = $crit_loc;
                  $SESSION_VARS['localisation_main'] = $localisation_main;
                  $SESSION_VARS['crit_loc'] = $crit_loc;
                  $liste_criteres[_("Niveau de localisation")] = adb_gettext($adsys["type_localisation_rwanda"][$localisation_main]);
                  $SESSION_VARS['liste_criteres'][_("Niveau de localisation")] = adb_gettext($adsys["type_localisation_rwanda"][$localisation_main]);
                  if (!empty($SESSION_VARS['crit_loc']) || $SESSION_VARS['crit_loc'] != 0) {
                    $sql = "select libelle_localisation from adsys_localisation_rwanda where id_ag=$global_id_agence and id = $crit_loc";


                    $result = $db->query($sql);
                    if (DB:: isError($result)) {
                      $dbHandler->closeConnection(false);
                      signalErreur(__FILE__, __LINE__, __FUNCTION__);
                    }
                    $row = $result->fetchrow();
                    $liste_criteres[_("Localisation")] = $row[0];
                    $SESSION_VARS['liste_criteres'][_("Localisation")] = $row[0];
                  }else {
                    $liste_criteres[_("Localisation")] = "Tous";
                    $SESSION_VARS['liste_criteres'][_("Localisation")] = "Tous";
                  }
                }

              }else {
                if (!empty ($localisation)) {
                  $DATA["localisation"] = $localisation;
                  $SESSION_VARS['localisation'] = $localisation;
                  $sql = "select libel from adsys_localisation where id_ag=$global_id_agence and id = $localisation";

                  $result = $db->query($sql);
                  if (DB:: isError($result)) {
                    $dbHandler->closeConnection(false);
                    signalErreur(__FILE__, __LINE__, __FUNCTION__);
                  }
                  $row = $result->fetchrow();
                  $liste_criteres[_("Localisation")] = $row[0];
                  $SESSION_VARS['liste_criteres'][_("Localisation")] = $row[0];
                }
              }

	            if (!empty ($pp_sexe)) {
	              $DATA["pp_sexe"] = $pp_sexe;
	              $liste_criteres[_("Sexe")] = adb_gettext($adsys["adsys_sexe"][$pp_sexe]);
	              $SESSION_VARS['pp_sexe'] = $pp_sexe;
	              $SESSION_VARS['liste_criteres'][_("Sexe")] = adb_gettext($adsys["adsys_sexe"][$pp_sexe]);
	            }

	            if (!empty ($sect_act)) {
	              $DATA["sect_act"] = $sect_act;
	              $sql = "select libel from adsys_sect_activite where id_ag=$global_id_agence and id = $sect_act";

	              $result = $db->query($sql);
	              if (DB :: isError($result)) {
	                $dbHandler->closeConnection(false);
	                signalErreur(__FILE__, __LINE__, __FUNCTION__);
	              }
	              $row = $result->fetchrow();
	              $liste_criteres[_("Secteur d'activite")] = $row[0];
	              $SESSION_VARS['sect_act'] = $sect_act;
	              $SESSION_VARS['liste_criteres'][_("Secteur d'activite")] = $row[0];
	            }

	            if (!empty ($prod)) {
	              $DATA["produit"] = $prod;
	              $libel_prd = getLibelPrdt($prod, "adsys_produit_credit");
	              $liste_criteres[_("Produit")] = $libel_prd;
	              $SESSION_VARS['produit'] = $prod;
	              $SESSION_VARS['liste_criteres'][_("Produit")] = $libel_prd;
	            }

	            if (!empty ($objet)) {
	              $DATA["objet"] = $objet;
	              $sql = "select libel from adsys_objets_credits where id_ag=$global_id_agence and id = $objet";
	              $result = $db->query($sql);
	              if (DB :: isError($result)) {
	                $dbHandler->closeConnection(false);
	                signalErreur(__FILE__, __LINE__, __FUNCTION__);
	              }
	              $row = $result->fetchrow();
	              $liste_criteres[_("Objet credit")] = $row[0];
	              $SESSION_VARS['objet'] = $objet;
	              $SESSION_VARS['liste_criteres'][_("Objet credit")] = $row[0];
	            }

	            if (!empty ($type_duree)) {
	              $DATA["type_duree"] = $type_duree;
	              $SESSION_VARS['type_duree'] = $type_duree;
	              if ($adsys["adsys_type_duree_credit"][$type_duree] == '')
	                $liste_criteres["Type duree"] = _("Mensuelle");
	              else
	                $liste_criteres[_("Type duree")] = adb_gettext($adsys["adsys_type_duree_credit"][$type_duree]);
	              $SESSION_VARS['liste_criteres'][_("Type duree")] = $liste_criteres["type duree"];
	            }

	            if (!empty ($duree_mois)) {
	              $DATA["duree_mois"] = $duree_mois;
	              $liste_criteres[_("Duree ")] = $duree_mois;
	              $SESSION_VARS['duree_mois'] = $duree_mois;
	              $SESSION_VARS['liste_criteres'][_("Duree")] = $duree_mois;
	            }

	            if (!empty ($date_debloc_inf)) {
	              $DATA["date_debloc_inf"] = $date_debloc_inf;
	              $liste_criteres[_("Date de deblocage inferieure")] = $date_debloc_inf;
	              $SESSION_VARS['date_debloc_inf'] = $date_debloc_inf;
	              $SESSION_VARS['liste_criteres'][_("Date de deblocage inferieure")] = $date_debloc_inf;
	            }

	            if (!empty ($date_debloc_sup)) {
	              $DATA["date_debloc_sup"] = $date_debloc_sup;
	              $liste_criteres[_("Date de deblocage superieure")] = $date_debloc_sup;
	              $SESSION_VARS['date_debloc_sup'] = $date_debloc_sup;
	              $SESSION_VARS['liste_criteres'][_("Date de deblocage superieure")] = $date_debloc_sup;
	            }

	            if (!empty ($nb_reech)) {
	              $DATA["nb_reech"] = $nb_reech;
	              $liste_criteres[_("Nombre reechelonnement")] = $nb_reech;
	              $SESSION_VARS['nb_reech'] = $nb_reech;
	              $SESSION_VARS['liste_criteres'][_("Nombre reechelonnement")] = $nb_reech;
	            }

	            if ($etat_dossier > 0) {
	              $DATA["etat_dossier"] = $etat_dossier;
	              $liste_criteres[_("Etat dossier")] = adb_gettext($adsys["adsys_etat_dossier_credit"][$etat_dossier]);
	            } else {//dossiers à l'état : Rejeté, Déboursés, Soldés, En attente rééchelonnement, Perte, Déboursement progressif
	              $DATA["etat_dossier"] = "3,5,6,7,9,13";
	              $liste_criteres[_("Etat dossier")] = adb_gettext($adsys["adsys_etat_dossier_credit"][3]) . ", " .adb_gettext($adsys["adsys_etat_dossier_credit"][5]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][6]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][7]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][9]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][13]);
	            }
	            $SESSION_VARS['etat_dossier'] = $DATA["etat_dossier"];
	            $SESSION_VARS['liste_criteres'][_("Etat dossier")] = $liste_criteres["Etat dossier"];
	            if (!empty ($gest)) {
	              $DATA["id_agent_gest"] = $gest;
	              $liste_criteres[_("Gestionnaire")] = getLibel("ad_uti", $gest);
	              $SESSION_VARS['id_agent_gest'] = $gest;
	            	$SESSION_VARS['liste_criteres'][_("Gestionnaire")] = $liste_criteres["Gestionnaire"];
	            }
	            if (!empty ($type_affich)) {
	              $DATA["type_affichage"] = $type_affich;
	              if ($type_affich == 1)
		              $liste_criteres["type_affichage"] = _("Détailé");
	              else
					  $liste_criteres["type_affichage"] = _("Synthétique");
	              $SESSION_VARS['type_affichage'] = $type_affich;
	            	$SESSION_VARS['liste_criteres'][_("Type affichage")] = $liste_criteres["type_affichage"];
	            }
            }

            // Validate limit
            if ($limite <=0 || $limite > 10000) {
                $limite = 5000;
            }

            //Formation du tableau contenant les données à afficher
            if ($SESSION_VARS['type_affichage'] == 1) //affichage détaillé
			{
				//pour gérer un client qui s'affiche sur deux palier, on ajoute $dernier_id_doss
				$lignes = getRegistreCredit($SESSION_VARS, $devise, $gest, false);
			}
            else
            {
                //afficage synthétique
                $lignes = getRegistreCreditInfoSynth($SESSION_VARS, $devise, $gest, false);
            }

            //On recupère le nombre de lignes générées par la requête pour la condition d'arret
            $count = sizeof($lignes);

    if ($lignes != NULL) {
              if ($global_nom_ecran == 'Kra-48') {

                  $dataCount = 0;
                  $indexCount = 0;
                  $lignes_bis = array();
                  $fichier_pdf_arr = array();

                  foreach($lignes as $id_doss=>$data){
                      $lignes_bis[$id_doss] = $data;

                      $dataCount++;

                      if ($dataCount%$limite == 0 || $count == $dataCount ) {
                          $indexCount++;

                          //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                          if ($SESSION_VARS['type_affichage'] == 1) //affichage détaillé
                          {
                              $xml = xml_registrecredit($lignes_bis, $SESSION_VARS['liste_criteres'], $devise);
                          } else {
                              //affichage synthétique
                              $xml = xml_registrecredit_info_synth($lignes_bis, $SESSION_VARS['liste_criteres'], $devise);
                          }

                          $fichier_pdf_arr[] = xml_2_xslfo_2_pdf($xml, 'registrecredit.xslt',false,$indexCount);
                          $lignes_bis = array();
                      }
                  }

                  //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                  /*
                  if ($SESSION_VARS['type_affichage'] == 1) //affichage détaillé
                  {
                      $xml = xml_registrecredit($lignes, $SESSION_VARS['liste_criteres'], $devise);
                  } else {
                      //affichage synthétique
                      $xml = xml_registrecredit_info_synth($lignes, $SESSION_VARS['liste_criteres'], $devise);
                  }
                  $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'registrecredit.xslt');

                  echo get_show_pdf_html("Gen-13", $fichier_pdf);
                  */

                  $fileCount = 1;
                  $js="";
                  foreach($fichier_pdf_arr as $fichier_pdf) {
                      // Compilation des rapports pdf générés
                      $js .= get_show_pdf_html(NULL, $fichier_pdf, NULL, "Rapport Registres des prêts no. $fileCount", $fileCount,(200+($fileCount*50)));
                      $fileCount++;
                  }

                  if ($js!="") {
                      $MyPage = new HTML_message(_("Génération rapport"));
                      $MyPage->setMessage(_("Le rapport a été généré avec succès !"));
                      $MyPage->addButton(BUTTON_OK, "Gen-13");
                      $MyPage->buildHTML();
                      echo $MyPage->HTML_code." ".$js;
                  } else {
                      $erreur = new HTML_erreur(_("Echec lors de la génération du rapport"));
                      $erreur->setMessage(_("Aucun rapport n'a été trouvé."));
                      $erreur->addButton(BUTTON_OK, "Gen-13");
                      $erreur->buildHTML();
                      return $erreur->HTML_code;
                  }

                  //Message de confirmation + affichage du rapport dans une nouvelle fenêtre

              } else
                if ($global_nom_ecran == 'Kra-58') {
                  //Génération du CSV grâce à XALAN
                    if ($SESSION_VARS['type_affichage'] == 1) //affichage détaillé
                    {
                        $xml = xml_registrecredit($lignes, $SESSION_VARS['liste_criteres'], $devise, true);

                    } else
                        $xml = xml_registrecredit_info_synth($lignes, $SESSION_VARS['liste_criteres'], $devise, true);

                    $csv_file = xml_2_csv($xml, 'registrecredit.xslt');

                    if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL suivant')){
                      echo getShowEXCELHTML ( "Gen-13", $csv_file );
                    }
                    else{
                      echo getShowCSVHTML ( "Gen-13", $csv_file );
                    }
                }
            } else {
              $html_msg = new HTML_message(_("Résultats de la requête"));
              $html_msg->setMessage(_("Aucun crédit n'a été trouvé"));
              $html_msg->addButton("BUTTON_OK", 'Gen-13');
              $html_msg->buildHTML();
              echo $html_msg->HTML_code;
            }
          }
/*}}}*/

/*{{{ Kra-61 : CAA - Crédits actifs par agent de crédit */
elseif ($global_nom_ecran == 'Kra-61') {
       if($global_nom_ecran_prec == 'Kra-1'){
       	$html = new HTML_GEN2(_("Sélection des critères"));
        $SESSION_VARS['palier'] == false;
              //Remettre $global_id_agence à l'identifiant de l'agence courante
				    resetGlobalIdAgence();
				    //Agence- Tri par agence
				    $list_agence = getAllIdNomAgence();
				    if (isSiege()) {
				      unset ($list_agence[$global_id_agence]);
				      $html->addField("agence", _("Agence"), TYPC_LSB);
				      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
				      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
				      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
				    }

              if ($global_multidevise) {
                $html->addTable("ad_cpt_comptable", OPER_INCLUDE, array (
                                  "devise"
                                ));
                $html->setFieldProperties("devise", FIELDP_LONG_NAME, _("Devise"));
                $html->setFieldProperties("devise", FIELDP_HAS_CHOICE_AUCUN, false);
                //Le tous n'est pas géré pour les devises
                //$html->setFieldProperties("devise", FIELDP_HAS_CHOICE_TOUS, true);
              }

              $html->addTableRefField("prod", _("Produit de crédit"), "adsys_produit_credit");
              $html->setFieldProperties("prod", FIELDP_HAS_CHOICE_AUCUN, false);
              $html->setFieldProperties("prod", FIELDP_HAS_CHOICE_TOUS, true);

              $html->addField("date_debloc_inf", _("Date début du déboursement"), TYPC_DTE);
              $html->setFieldProperties("date_debloc_inf", FIELDP_DEFAULT, date("01/01/Y"));
              $html->setFieldProperties("date_debloc_inf", FIELDP_IS_REQUIRED, false);

              $html->addField("date_debloc_sup", _("Date fin de déboursement"), TYPC_DTE);
              $html->setFieldProperties("date_debloc_sup", FIELDP_DEFAULT, date("d/m/Y"));
              $html->setFieldProperties("date_debloc_sup", FIELDP_IS_REQUIRED, false);

              $html->addField("etat_dossier", _("Etat dossier"), TYPC_LSB);
              // On ne permet que les états suivants, les autres n'ont pas bcp de sens pour ce rapport
              $etats_dcr_dispos[5] = adb_gettext($adsys["adsys_etat_dossier_credit"][5]);
              $etats_dcr_dispos[6] = adb_gettext($adsys["adsys_etat_dossier_credit"][6]);
              $etats_dcr_dispos[7] = adb_gettext($adsys["adsys_etat_dossier_credit"][7]);
              $etats_dcr_dispos[9] = adb_gettext($adsys["adsys_etat_dossier_credit"][9]);
              $etats_dcr_dispos[13] = adb_gettext($adsys["adsys_etat_dossier_credit"][13]);
              $etats_dcr_dispos[14] = adb_gettext($adsys["adsys_etat_dossier_credit"][14]);
              $etats_dcr_dispos[15] = adb_gettext($adsys["adsys_etat_dossier_credit"][15]);
              $html->setFieldProperties("etat_dossier", FIELDP_ADD_CHOICES, $etats_dcr_dispos);
              $html->setFieldProperties("etat_dossier", FIELDP_DEFAULT, 5);
              $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_AUCUN, false);
              $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_TOUS, true);
              //Gestionnaire- Tri par agent gestionnaire
              $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
              $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
              $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

              $html->addField("limite", _("Limite "), TYPC_INT);
              $html->setFieldProperties("limite", FIELDP_DEFAULT, 5000);

              $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
              $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-62");
              $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
              $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-66");
              $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
              $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-66");

              $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
              $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
              $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
              $html->buildHTML();
              echo $html->getHTML();
       } else {
       		$html = new HTML_GEN2(_("Rapport Suivant"));

      	  $SESSION_VARS['palier'] == true;
      	  $html->addFormButton(1, 1, "valider", _("Rapport PDF Suivant"), TYPB_SUBMIT);
          $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-62");
          $html->addFormButton(1, 2, "excel", _("Export EXCEL Suivant"), TYPB_SUBMIT);
          $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-66");
          $html->addFormButton(1, 3, "csv", _("Export CSV Suivant"), TYPB_SUBMIT);
          $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-66");

          $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
          $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
          $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
          $html->buildHTML();
          echo $html->getHTML();
       }

}
/*}}}*/

/*{{{ Kra-62 et Kra-66 : CAA - Impression ou export csv Crédits actifs par agent de crédits */
elseif ($global_nom_ecran == 'Kra-62' || $global_nom_ecran == 'Kra-66') {
                global $dbHandler;
                $db = $dbHandler->openConnection();

                global $adsys;
                $DATA = array ();
                setGlobalIdAgence($agence);
                if($SESSION_VARS['palier'] == false || $SESSION_VARS['palier'] == NULL){
	                if (!empty ($prod)) {
	                  $DATA["produit"] = $prod;
	                  $libel_prd = getLibelPrdt($prod, "adsys_produit_credit");
	                  $liste_criteres["Produit"] = $libel_prd;
	                  $SESSION_VARS['produit'] = $prod;
	              		$SESSION_VARS['liste_criteres']["Produit"] = $libel_prd;
	                }

	                if (!empty ($date_debloc_inf)) {
	                  $DATA["date_debloc_inf"] = $date_debloc_inf;
	                  $liste_criteres["Date de deblocage inferieure"] = $date_debloc_inf;
	                  $SESSION_VARS['date_debloc_inf'] = $date_debloc_inf;
	              		$SESSION_VARS['liste_criteres'][_("Date de deblocage inferieure")] = $date_debloc_inf;
	                }

	                if (!empty ($date_debloc_sup)) {
	                  $DATA["date_debloc_sup"] = $date_debloc_sup;
	                  $liste_criteres[_("Date de deblocage superieure")] = $date_debloc_sup;
	                  $SESSION_VARS['date_debloc_sup'] = $date_debloc_sup;
	              		$SESSION_VARS['liste_criteres'][_("Date de deblocage superieure")] = $date_debloc_sup;
	                }

	                if ($etat_dossier > 0) {
	                  $DATA["etat_dossier"] = $etat_dossier;
	                  $liste_criteres[_("Etat dossier")] = adb_gettext($adsys["adsys_etat_dossier_credit"][$etat_dossier]);
	                } else {
	                  $DATA["etat_dossier"] = "5,6,7,9,13,14,15";
	                  $liste_criteres[_("Etat dossier")] = adb_gettext($adsys["adsys_etat_dossier_credit"][5]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][6]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][7]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][9]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][13]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][14]) . ", " . adb_gettext($adsys["adsys_etat_dossier_credit"][15]);
	                }
	                $SESSION_VARS['etat_dossier'] = $DATA["etat_dossier"];
	           		  $SESSION_VARS['liste_criteres'][_("Etat dossier")] = $liste_criteres[_("Etat dossier")];
	                if (!empty ($gest)) {
	                  $DATA["id_agent_gest"] = $gest;
	                  $liste_criteres[_("Gestionnaire")] = (getLibel("ad_uti", $gest) == "")?_("Tous"):getLibel("ad_uti", $gest);
	                  $SESSION_VARS['id_agent_gest'] = $gest;
	            			$SESSION_VARS['liste_criteres'][_("Gestionnaire")] = $liste_criteres[_("Gestionnaire")];
	                }
             }

            // Validate limit
            if ($limite <=0 || $limite > 10000) {
                $limite = 5000;
            }

            $count = 0;
			      if (!isset($SESSION_VARS['sequence'])) {
						    $SESSION_VARS['sequence'] = 0;
						    $i = 0;
						}
						$i = $SESSION_VARS['sequence'];
			      //optimisation par palier de 4000 : on recupere les credits actifs répondant aux critéres choisis
			      if (!isset($nombre)) {
			      	$nombre = getNbreClientsRegistrePret($SESSION_VARS['date_debloc_inf'], $SESSION_VARS['date_debloc_sup']);
			      }
            // Recherche des crédits sélectionnés
            if (($global_multidevise) && ($devise != '0'))
               $lignes = getCreditActif($SESSION_VARS, $devise, $gest, $i);
            else
               $lignes = getCreditActif($SESSION_VARS, NULL, $gest, $i);

            //On recupère le nombre de lignes générées par la requête pour la condition d'arret
            $count = sizeof($lignes);
            //recupération du dernier client
            $tab = end($lignes);
            $dernier_id_client = $tab['id_client'];
            $SESSION_VARS['liste_criteres'][_("Du Client N°")] = $i;
            $SESSION_VARS['liste_criteres'][_("Au Client N°")] = $dernier_id_client;

    $rowCount = 0;
    // Count rows
    foreach($lignes as $key=>$val)  {
        $rowCount++;
    }

    /*require_once ('lib/misc/debug.php');
    print_rn($count);
    print_rn($rowCount);
    print_rn($lignes);*/

    if ($lignes != NULL) {
	             if (($global_multidevise) && ($devise != '0')) {
	              if ($global_nom_ecran == 'Kra-62') {

                      $dataCount = 0;
                      $indexCount = 0;
                      $lignes_bis = array();
                      $fichier_pdf_arr = array();

                      foreach($lignes as $key=>$data){
                          $lignes_bis[$key] = $data;

                          $dataCount++;

                          if ($dataCount%$limite == 0 || $rowCount == $dataCount ) {
                              $indexCount++;

                              //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                              $xml = xml_creditactif($lignes_bis, $SESSION_VARS['liste_criteres'], $devise);

                              $fichier_pdf_arr[] = xml_2_xslfo_2_pdf($xml, 'creditactif.xslt',false,$indexCount);
                              $lignes_bis = array();
                          }
                      }

                      $fileCount = 1;
                      $js="";
                      foreach($fichier_pdf_arr as $fichier_pdf) {
                          // Compilation des rapports pdf générés
                          $js .= get_show_pdf_html(NULL, $fichier_pdf, NULL, "Rapport Crédits actifs par agent de crédit no. $fileCount", $fileCount,(200+($fileCount*50)));
                          $fileCount++;
                      }

                      if ($js!="") {
                          $MyPage = new HTML_message(_("Génération rapport"));
                          $MyPage->setMessage(_("Le rapport a été généré avec succès !"));
                          $MyPage->addButton(BUTTON_OK, "Gen-13");
                          $MyPage->buildHTML();
                          echo $MyPage->HTML_code." ".$js;
                      } else {
                          $erreur = new HTML_erreur(_("Echec lors de la génération du rapport"));
                          $erreur->setMessage(_("Aucun rapport n'a été trouvé."));
                          $erreur->addButton(BUTTON_OK, "Gen-13");
                          $erreur->buildHTML();
                          return $erreur->HTML_code;
                      }

	                //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
	                //$xml = xml_creditactif($lignes, $SESSION_VARS['liste_criteres'], $devise);
	                //$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'creditactif.xslt');

	                //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
                    /*
                    $i = $i + 2000; // nombre de clients à imprimer
                    if ($i > $nombre){
                        $i = $nombre;
                    }
                    $SESSION_VARS['sequence']=$i;
                    if ($i != $dernier_id_client) {
                       $i = $dernier_id_client;
                       $SESSION_VARS['sequence'] = $i;
                    }
                    if (($count < 2000 || $nombre == 2000) && ($count >= 0)){
                       echo get_show_pdf_html("Gen-13", $fichier_pdf);
                    } else {
                       echo get_show_pdf_html("Kra-61", $fichier_pdf);
                       $SESSION_VARS['sequence'] = $i;
                       $SESSION_VARS['palier'] = true;
                    }
                    */
	              } else
	                if ($global_nom_ecran == 'Kra-66') {
	                  //Génération du CSV grâce à XALAN
	                  $xml = xml_creditactif($lignes, $SESSION_VARS['liste_criteres'], $devise, true);
	                  $csv_file = xml_2_csv($xml, 'creditactif.xslt');

	                  //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
										$i = $i + 2000; // nombre de clients à imprimer
										if ($i > $nombre){
											$i = $nombre;
										}
										$SESSION_VARS['sequence']=$i;
										if ($i != $dernier_id_client) {
										   $i = $dernier_id_client;
										   $SESSION_VARS['sequence'] = $i;
										}
										if (($count < 2000 || $nombre == 2000) && ($count >= 0)){
										  // echo get_show_pdf_html("Gen-13", $fichier_pdf);
                       if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL Suivant')){
                         echo getShowEXCELHTML("Gen-13", $csv_file);
                       }
                       else{
                         echo getShowCSVHTML("Gen-13", $csv_file);
                       }
										} else {
										  // echo get_show_pdf_html("Kra-61", $fichier_pdf);
                       if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL Suivant')){
                         echo getShowEXCElHTML("Kra-61", $csv_file);
                       }
                       else{
                         echo getShowCSVHTML("Kra-61", $csv_file);
                       }
										   $SESSION_VARS['sequence'] = $i;
										   $SESSION_VARS['palier'] = true;
										}
	                }
	               } else {
	               	if ($global_nom_ecran == 'Kra-62') {

                        $dataCount = 0;
                        $indexCount = 0;
                        $lignes_bis = array();
                        $fichier_pdf_arr = array();

                        foreach($lignes as $key=>$data){
                            $lignes_bis[$key] = $data;

                            $dataCount++;

                            if ($dataCount%$limite == 0 || $rowCount == $dataCount ) {
                                $indexCount++;

                                //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                                $xml = xml_creditactif($lignes_bis, $SESSION_VARS['liste_criteres'], $devise);

                                $fichier_pdf_arr[] = xml_2_xslfo_2_pdf($xml, 'creditactif.xslt',false,$indexCount);
                                $lignes_bis = array();
                            }
                        }

                        $fileCount = 1;
                        $js="";
                        foreach($fichier_pdf_arr as $fichier_pdf) {
                            // Compilation des rapports pdf générés
                            $js .= get_show_pdf_html(NULL, $fichier_pdf, NULL, "Rapport Crédits actifs par agent de crédit no. $fileCount", $fileCount,(200+($fileCount*50)));
                            $fileCount++;
                        }

                        if ($js!="") {
                            $MyPage = new HTML_message(_("Génération rapport"));
                            $MyPage->setMessage(_("Le rapport a été généré avec succès !"));
                            $MyPage->addButton(BUTTON_OK, "Gen-13");
                            $MyPage->buildHTML();
                            echo $MyPage->HTML_code." ".$js;
                        } else {
                            $erreur = new HTML_erreur(_("Echec lors de la génération du rapport"));
                            $erreur->setMessage(_("Aucun rapport n'a été trouvé."));
                            $erreur->addButton(BUTTON_OK, "Gen-13");
                            $erreur->buildHTML();
                            return $erreur->HTML_code;
                        }

	                //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
	                //$xml = xml_creditactif($lignes, $SESSION_VARS['liste_criteres']);
	                //$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'creditactif.xslt');

	                //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
                        /*
                    $i = $i + 2000; // nombre de clients à imprimer
                    if ($i > $nombre){
                        $i = $nombre;
                    }
                    $SESSION_VARS['sequence']=$i;
                    if ($i != $dernier_id_client) {
                       $i = $dernier_id_client;
                       $SESSION_VARS['sequence'] = $i;
                    }
                    if (($count < 2000 || $nombre == 2000) && ($count >= 0)){
                       echo get_show_pdf_html("Gen-13", $fichier_pdf);
                    } else {
                       echo get_show_pdf_html("Kra-61", $fichier_pdf);
                       $SESSION_VARS['sequence'] = $i;
                       $SESSION_VARS['palier'] = true;
                    }
                        */
	              } else
	                if ($global_nom_ecran == 'Kra-66') {
	                  //Génération du CSV grâce à XALAN
	                  $xml = xml_creditactif($lignes, $SESSION_VARS['liste_criteres'], NULL, true);
	                  $csv_file = xml_2_csv($xml, 'creditactif.xslt');

	                  //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
										$i = $i + 2000; // nombre de clients à imprimer
										if ($i > $nombre){
											$i = $nombre;
										}
										$SESSION_VARS['sequence']=$i;
										if ($i != $dernier_id_client) {
										   $i = $dernier_id_client;
										   $SESSION_VARS['sequence'] = $i;
										} 
										if (($count < 2000 || $nombre == 2000) && ($count >= 0)){
										  // echo get_show_pdf_html("Gen-13", $fichier_pdf);
                       if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL Suivant')){
                         echo getShowEXCELHTML("Gen-13", $csv_file);
                       }
                       else{
                         echo getShowCSVHTML("Gen-13", $csv_file);
                       }
										} else {
										  // echo get_show_pdf_html("Kra-61", $fichier_pdf);
                      if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL Suivant')){
                        echo getShowEXCELHTML("Kra-61", $csv_file);
                      }
                      else{
										    echo getShowCSVHTML("Kra-61", $csv_file);
                      }
										   $SESSION_VARS['sequence'] = $i;
										   $SESSION_VARS['palier'] = true;
										}
	                }
	               }
             } else {
                  $html_msg = new HTML_message(_("Résultats de la requête"));
                  $html_msg->setMessage(_("Aucun crédit n'a été trouvé"));
                  $html_msg->addButton("BUTTON_OK", 'Gen-13');
                  $html_msg->buildHTML();
                  echo $html_msg->HTML_code;
             }
}

/*}}}*/

/*{{{ Kra-49 : AEC - Personnalisation crédits arrivant à échéance */
elseif ($global_nom_ecran == 'Kra-49') {
     if($global_nom_ecran_prec == "Kra-1"){
     	$html = new HTML_GEN2(_("Personnalisation du rapport"));
                  /*$choix_periode_echeance = array (
                                              1 => _("Aujourd'hui"),
                                              2 => _("Sur une semaine"),
                                              3 => _("Sur deux semaines"),
                                              4 => _("Sur trois semaines"),
                                              5 => _("Sur 1 mois"),
                                              6 => _("Sur 3 mois"),
                                              7 => _("Sur 6 mois"),
                                              8 => _("Sur 12 mois")
                                            );
                  $SESSION_VARS["periode_rapport_credit_echeance"] = $choix_periode_echeance;*/

                  //Remettre $global_id_agence à l'identifiant de l'agence courante
				    resetGlobalIdAgence();
				    //Agence- Tri par agence
				    $list_agence = getAllIdNomAgence();
				    if (isSiege()) {
				      unset ($list_agence[$global_id_agence]);
				      $html->addField("agence", _("Agence"), TYPC_LSB);
				      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
				      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
				      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
				    }
                  //AT-126 : Ajout parametre date debut et date fin au lieu du choix de la periode
                  $html->addField("date_debut", _("Date Début"), TYPC_DTG);
                  $html->setFieldProperties("date_debut", FIELDP_IS_REQUIRED, true);
                  $html->addField("date_fin", _("Date Fin"), TYPC_DTG);
                  $html->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);
                  //Fonction javascript pour verfier si date fin est inferieure de la date debut
                  $JScode_date = "";
                  $JScode_date .= "\nfunction checkDate()\n";
                  $JScode_date .= "{\n";
                  $JScode_date .= "\t var valide='t';\n";
                  $JScode_date .= "\t var date1 = document.getElementsByName('HTML_GEN_date_date_debut').item(0).value;\n";
                  $JScode_date .= "\t var date2 = document.getElementsByName('HTML_GEN_date_date_fin').item(0).value;\n";
                  $JScode_date .= "\t if (isBefore(date2,date1) == true) {\n";
                  $JScode_date .= "\t\t isSubmit=false;\n";
                  $JScode_date .= "\t\t ADFormValid=false;\n";
                  $JScode_date .= "\t\t valide='f';\n";
                  $JScode_date .= "\t }\n";
                  $JScode_date .= "\t if (valide=='f') {\n";
                  $JScode_date .= "\t\t alert('La Date Fin devrait etre superieure au Date Début!!');exit;\n";
                  $JScode_date .= "\t }\n";
                  $JScode_date .= "}\n";
                  $html->addJS(JSP_FORM,"comparaisonDateDebut_DateFin",$JScode_date);
                  /*$html->addField("periode", _("Choix de la période"), TYPC_LSB);
                  $html->setFieldProperties("periode", FIELDP_IS_REQUIRED, true);
                  $html->setFieldProperties("periode", FIELDP_ADD_CHOICES, $choix_periode_echeance);*/
                  $SESSION_VARS['ecran_precedent'] = 1;
                  //$html->addField("exclusif", _("Sélectionner exclusivement la période"), TYPC_BOL);

                  $html->addTable("ad_cpt_comptable", OPER_INCLUDE, array (
                                    "devise"
                                  ));
                  $html->setFieldProperties("devise", FIELDP_LONG_NAME, _("Devise"));
                  $html->setFieldProperties("devise", FIELDP_IS_REQUIRED, true);
                  //Gestionnaire- Tri par agent gestionnaire
                  $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
                  $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
                  $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

                  $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
                  $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-50");
                  $html->setFormButtonProperties("valider", BUTP_JS_EVENT, array("onClick"=>"checkDate();"));
                  $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
                  $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-60");
                  $html->setFormButtonProperties("excel", BUTP_JS_EVENT, array("onClick"=>"checkDate();"));
                  $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
                  $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-60");
                  $html->setFormButtonProperties("csv", BUTP_JS_EVENT, array("onClick"=>"checkDate();"));

                  $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
                  $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
                  $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
                  $html->buildHTML();
                  echo $html->getHTML();
     } else {
     	$html = new HTML_GEN2(_("Rapport Suivant"));

 		                  $html->addFormButton(1, 1, "valider", _("Rapport PDF Suivant"), TYPB_SUBMIT);
 		                  $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-50");
                      $html->addFormButton(1, 2, "excel", _("Export EXCEL Suivant"), TYPB_SUBMIT);
                      $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-60");
 		                  $html->addFormButton(1, 3, "csv", _("Export CSV Suivant"), TYPB_SUBMIT);
 		                  $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-60");
 		                  $SESSION_VARS['ecran_precedent'] = 2; 
 		                  $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
 		                  $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
 		                  $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
 		                  $html->buildHTML();
 		                  echo $html->getHTML();
     }

}
/*}}}*/

/*{{{ Kra-50 et Kra-60 : AEC - Impression ou export csv crédits arrivant à échéance */
elseif ($global_nom_ecran == 'Kra-50' || $global_nom_ecran == 'Kra-60') {
                    setGlobalIdAgence($agence);
                    if ($gest == "")
                      $gest = 0;


 		                    if($SESSION_VARS['ecran_precedent'] == 1){
                              //AT-55 : calcule annee, semaines, jours et mois entre la date debut et la date du jour et stocké dans la session
                              $date_debut_explode = explode('/',$date_debut);
                              $date1 = "$date_debut_explode[2]-$date_debut_explode[1]-$date_debut_explode[0]";
                              $dateDebut = "$date_debut_explode[0]/$date_debut_explode[1]/$date_debut_explode[2]";
                              $date2 = date('Y-m-d');
                              $date_now = date('d/m/Y');
                              $checkDate = isBefore($dateDebut,$date_now);
                              $intervalleMsgInf = "";
                              $intervalleMsgSup = "";
                              if ($checkDate === true){
                                $intervallePeriode = getIntervalleEntreDeuxDates($date1,$date2);
                                $intervalleMsgInf = " avant la date du jour";
                                $intervalleMsgSup = "A partir ";
                              }
                              else{
                                $intervallePeriode = getIntervalleEntreDeuxDates($date2,$date1);
                                $intervalleMsgSup = "Dans ";
                                if ($intervallePeriode['en_annee'] == 0 && $intervallePeriode['en_mois'] == 0 && $intervallePeriode['en_jours'] == 0 && $intervallePeriode['en_semaine'] == 0){
                                  $intervalleMsgSup = "Aujourd'hui";
                                }
                              }
                              $SESSION_VARS['periode']['en_annee'] = $intervallePeriode['en_annee'];
                              $SESSION_VARS['periode']['en_mois'] = $intervallePeriode['en_mois'];
                              $SESSION_VARS['periode']['en_jours'] = $intervallePeriode['en_jours'];
                              $SESSION_VARS['periode']['en_semaine'] = $intervallePeriode['en_semaine'];
                              $SESSION_VARS['periode']['date_inf'] = $date_debut;
                              $SESSION_VARS['periode']['date_sup'] = $date_fin;
                              $SESSION_VARS['periode']['intervalleMsgInf'] = $intervalleMsgInf;
                              $SESSION_VARS['periode']['intervalleMsgSup'] = $intervalleMsgSup;

 		                      $SESSION_VARS['gest'] = $gest;
 		                      $SESSION_VARS['devise'] = $devise;
 		                      //$SESSION_VARS['periode'] = $periode;
 		                      $intervalle = getIntervallePeriode($SESSION_VARS['periode']);
 		                      $SESSION_VARS['date_inf'] = $intervalle['date_inf'];
 		                      $SESSION_VARS['date_sup'] = $intervalle['date_sup'] ;
 		                      $SESSION_VARS['indice'] = $intervalle['indice'] ;
 		                      $SESSION_VARS['libelle'] = $intervalle['libelle'] ;
 		                    }
 		                      $count = 0;
 		                      if (!isset($SESSION_VARS['sequence'])) {
 		                          $SESSION_VARS['sequence'] = 0;
 		                          $i = 0;
 		                      }
 		                      $i = $SESSION_VARS['sequence'];
 		                      $nombre = getNbreClientsEcheance($SESSION_VARS['date_inf'], $SESSION_VARS['date_sup'], $SESSION_VARS['devise']);
 		                      $lignescredit = getCreditsEcheance($SESSION_VARS['periode'], $exclusif, $SESSION_VARS['devise'], $SESSION_VARS['gest'], $i);
 		                      //On recupère le nombre de lignes générées par la requête pour la condition d'arret
 		                      $count = sizeof($lignescredit[$SESSION_VARS['indice']]['credit']);
 		                      //recupération du dernier client
 		                      $tab = end($lignescredit[$SESSION_VARS['indice']]['credit']);
 		                      $dernier_id_client = $tab['id_client'];

                    if ($lignescredit != NULL) {
                      if ($global_nom_ecran == 'Kra-60') {
                        //Génération du CSV grâce à XALAN
                        $xml = xml_credit_echeance($lignescredit, array (
                                                     _("Periode") => $SESSION_VARS['periode']['date_inf']." au ".$SESSION_VARS['periode']['date_sup'],//$SESSION_VARS["periode_rapport_credit_echeance"][$SESSION_VARS['periode']],
 	                                                   _("Devise") => $SESSION_VARS['devise'],
 	                                                   _("Gestionnaire") => getLibel("ad_uti", $SESSION_VARS['gest']),
 	                                                   _("Du client N°") => $i,
 	                                                   _("Au client N°") => $dernier_id_client
                                                   ), true);
                        $csv_file = xml_2_csv($xml, 'credit_echeance.xslt');

                        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
                        $i = $i + 4000; // nombre de clients à imprimer
 	                      if ($i > $nombre){
 	                         $i = $nombre;
 	                      }
 	                      $SESSION_VARS['sequence']=$i;
 	                      if ($i != $dernier_id_client) {
 	                         $i = $dernier_id_client;
 	                         $SESSION_VARS['sequence'] = $i;
 	                      }
 	                      if (($count < 4000 || $nombre == 4000) && ($count >= 0)){
                           if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL Suivant')){
                             echo getShowEXCELHTML("Gen-13", $csv_file);
                           }
                           else{
 	                           echo getShowCSVHTML("Gen-13", $csv_file);
                           }
 	                      } else {
                           if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL Suivant')){
                             echo getShowEXCELHTML("Kra-76", $csv_file);
                           }
                           else{
 	                           echo getShowCSVHTML("Kra-76", $csv_file);
                           }
 	                         $SESSION_VARS['sequence'] = $i;
 	                      }
                      } else
                        if ($global_nom_ecran == 'Kra-50') {
                          //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                          $xml = xml_credit_echeance($lignescredit, array (
                                                         _("Periode") =>$SESSION_VARS['periode']['date_inf']." au ".$SESSION_VARS['periode']['date_sup'],//$SESSION_VARS["periode_rapport_credit_echeance"][$SESSION_VARS['periode']],
 		                                                     _("Devise") => $SESSION_VARS['devise'],
 		                                                     _("Gestionnaire") => getLibel("ad_uti", $SESSION_VARS['gest']),
 		                                                     _("Du client N°") => $i,
 		                                                     _("Au client N°") => $dernier_id_client
 		                                                   ));
                        $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'credit_echeance.xslt');

                          //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
													$i = $i + 4000; // nombre de clients à imprimer
													if ($i > $nombre){
														$i = $nombre;
													}
													$SESSION_VARS['sequence']=$i;
													if ($i != $dernier_id_client) {
													   $i = $dernier_id_client;
													   $SESSION_VARS['sequence'] = $i;
													}
													if (($count < 4000 || $nombre == 4000) && ($count >= 0)){
													   echo get_show_pdf_html("Gen-13", $fichier_pdf);
													} else {
													 	echo get_show_pdf_html("Kra-49", $fichier_pdf);
													 	$SESSION_VARS['sequence'] = $i;
													}
                        }
                    }
                    else {
 		                      $my_page = new HTML_message(_("Aucune donnée"));
 		                      $my_page->setMessage(_("Aucune donnée à imprimer,pour le(s) critère(s) selectioné(s)."), true);
 		                      $my_page->addButton("BUTTON_OK", 'Gen-13');
 		                      $my_page->show();

 	                   }
                    // TODO: faire le else et afficher un message lorsqu'aucune échéance n'a été trouvée
}
/*}}}*/
/*{{{ Kra-67 : REP -  Personnalisation rapport crédits repris */
else
	if ($global_nom_ecran == 'Kra-67' ) {
		$html = new HTML_GEN2(_("Personnalisation du rapport"));
		//Remettre $global_id_agence à l'identifiant de l'agence courante
		resetGlobalIdAgence();
		//Agence- Tri par agence
		 $list_agence = getAllIdNomAgence();
		 if (isSiege()) {
		      unset ($list_agence[$global_id_agence]);
		      $html->addField("agence", _("Agence"), TYPC_LSB);
		      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
		      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
		      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
		  }

		$html->addField("date_deb", _("Date de début"), TYPC_DTE);
		$html->setFieldProperties("date_deb", FIELDP_DEFAULT, date("01/01/Y"));
		$html->setFieldProperties("date_deb", FIELDP_IS_REQUIRED, true);

		$html->addField("date_fin", _("Date de fin"), TYPC_DTE);
		$html->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
		$html->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);

		$html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
		$html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-68");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-69");
		$html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
		$html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-69");

		$html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
		$html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
		$html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
		$html->buildHTML();
		echo $html->getHTML();

	}

/*{{{ Kra-68 : REP - Impression ou export csv crédits repris */
else
	if ($global_nom_ecran == 'Kra-68' || $global_nom_ecran == 'Kra-69') {
    if (isSiege()) {
     	setGlobalIdAgence($agence);

     }
     $myDATA=getCreditRepris($date_deb, $date_fin);
     if ($myDATA->errCode != NO_ERR) {
        $html_err = new HTML_erreur(_("Crédits repris"));
        $html_err->setMessage(_("Echec : ") . $myDATA->param);
        $html_err->addButton(BUTTON_OK, 'Gen-13');
        $html_err->buildHTML();
        echo $html_err->HTML_code;
        exit ();
     }
	if( count($myDATA)>0) {
      $list_criteres = array (_("Date du ")=>$date_deb,_("Au ")=>$date_fin);
			if($global_nom_ecran == 'Kra-68') {
				$xml=xml_credit_repris($myDATA->param,$list_criteres);
				$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'credits_repris.xslt');

				//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
				echo get_show_pdf_html("Gen-13", $fichier_pdf);

			}elseif($global_nom_ecran == 'Kra-69'){
				//Génération du fichier CSV
				$xml =xml_credit_repris($myDATA->param,$list_criteres,true);
				$csv_file = xml_2_csv($xml, 'credits_repris.xslt');

				//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        if (isset($excel) && $excel == 'Export EXCEL'){
          echo getShowEXCELHTML("Gen-13", $csv_file);
        }
        else{
				  echo getShowCSVHTML("Gen-13", $csv_file);
        }

			}
		}else {
			$my_page = new HTML_message(_("Aucune donnée"));
			$my_page->setMessage(_("Aucune donnée à imprimer,pour le(s) critère(s) selectioné(s)."), true);
			$my_page->addButton("BUTTON_OK", 'Gen-13');
			$my_page->show();

		}


	}
/*{{{ Kra-70 : SRC - Situation des risques de crédits */
 else
   if ($global_nom_ecran == "Kra-70") { //export csv  rapport 'Situation des risques de crédits'

 		 if ($global_multidevise)
       setMonnaieCourante("");
     $MyPage = new HTML_GEN2(_("Sélection type rapport crédit"));
		 //Remettre $global_id_agence à l'identifiant de l'agence courante
	   resetGlobalIdAgence();
	   //Agence- Tri par agence
	   $list_agence = getAllIdNomAgence();
		 if (isSiege()) {
		      unset ($list_agence[$global_id_agence]);
		      $MyPage->addField("agence", _("Agence"), TYPC_LSB);
		      $MyPage->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
		      $MyPage->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
		      $MyPage->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
		  }
		  unset ($SESSION_VARS['id_prod']);
	
	//ticket 489_Date Deboursement- Tri par date debut et date fin de deboursement
	 $MyPage->addField("date_debloc_inf", _("Date début du déboursement"), TYPC_DTE);
	 $MyPage->setFieldProperties("date_debloc_inf", FIELDP_DEFAULT, date("01/01/2000"));
     $MyPage->setFieldProperties("date_debloc_inf", FIELDP_IS_REQUIRED, false);
		   
	 $MyPage->addField("date_debloc_sup", _("Date fin de déboursement"), TYPC_DTE);
     $MyPage->setFieldProperties("date_debloc_sup", FIELDP_DEFAULT, date("d/m/Y"));
     $MyPage->setFieldProperties("date_debloc_sup", FIELDP_IS_REQUIRED, false);
		  
     //Gestionnaire- Tri par agent gestionnaire
     $MyPage->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
     $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
     $MyPage->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);
     //date de l'export
     $MyPage->addField("export_date", _("Date"), TYPC_DTE);
     $MyPage->setFieldProperties("export_date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))));
     $MyPage->setFieldProperties("export_date", FIELDP_IS_REQUIRED, true);
    
     //produits
     $MyPage->addTableRefField("prd", _("Type de produit de crédit"), "adsys_produit_credit");
     $MyPage->setFieldProperties("prd", FIELDP_HAS_CHOICE_AUCUN, false);
     $MyPage->setFieldProperties("prd", FIELDP_HAS_CHOICE_TOUS, true);
      
     //Boutons
     $MyPage->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
     $MyPage->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-71");
     $MyPage->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
     $MyPage->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-72");
     $MyPage->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
     $MyPage->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-72");
     $MyPage->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
     $MyPage->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
     $MyPage->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

     //HTML
     $MyPage->buildHTML();
     echo $MyPage->getHTML();

   }
/*}}}*/


/*{{{ Kra-71 et Kra-72 : SRC - Impression ou export csv Situation des risques de crédits */
 else
   if ($global_nom_ecran == "Kra-71" || $global_nom_ecran == "Kra-72") { //Impression ou export rapport 'risques de crédits'

 		  if (isSiege()) {
     	setGlobalIdAgence($agence);
     }

     if ($gest == "")
       $gest = 0;

//		 //Récupère les données
//
//		 $result = get_risques_credits($gestionnaire);
//		 if ($result->errCode != NO_ERR) {
//		 	$html_err = new HTML_erreur(_("Situation des risques de crédits"));
//      $html_err->setMessage(_("Echec : " . $result->param));
//      $html_err->addButton(BUTTON_OK, 'Gen-13');
//      $html_err->buildHTML();
//      echo $html_err->HTML_code;
//      exit ();
//		 }else {
//		    $data = $result->param;
//		 }


  
     //455bg2
     if (!empty ($date_debloc_inf)) {
     	$date_debloc_inf1 = $date_debloc_inf;
     }
     if (!empty ($date_debloc_sup)) {
     	$date_debloc_sup1 = $date_debloc_sup;
     }

     //get id produit
     if (!empty ($prd)) {
     	$SESSION_VARS['id_prod'] = $prd;
     	//$SESSION_VARS['libelPrd'] = getLibelPrdt($SESSION_VARS['id_prod'], "adsys_produit_credit");
     }
     if ($global_nom_ecran == 'Kra-71') {
       //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
       $xml = xml_risques_credits($gest, $export_date, $date_debloc_inf1, $date_debloc_sup1, $SESSION_VARS['id_prod'] );
       $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'risques_credits.xslt');

       //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
       echo get_show_pdf_html("Gen-13", $fichier_pdf);
     } else
       if ($global_nom_ecran == 'Kra-72') {
         //Génération du CSV grâce à XALAN
         $xml = xml_risques_credits($gest, $export_date, $date_debloc_inf1, $date_debloc_sup1, $SESSION_VARS['id_prod'], true);
         $csv_file = xml_2_csv($xml, 'risques_credits.xslt');

         //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
         if (isset($excel) && $excel == 'Export EXCEL'){
           echo getShowEXCELHTML("Gen-13", $csv_file);
         }
         else{
           echo getShowCSVHTML("Gen-13", $csv_file);
         }
       }
  
   }
/*}}}*/

/*{{{ Kra-73 : REE - Personnalisation rapport des crédits réechelonnés */
elseif ($global_nom_ecran == "Kra-73") {
	$html = new HTML_GEN2(_("Personnalisation du rapport"));

        //Remettre $global_id_agence à l'identifiant de l'agence courante
  resetGlobalIdAgence();
  //Agence- Tri par agence
  $list_agence = getAllIdNomAgence();
	  if (isSiege()) {
		  unset ($list_agence[$global_id_agence]);
		  $html->addField("agence", _("Agence"), TYPC_LSB);
		  $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
		  $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
		  $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
		}

//    $html->addField("date_deb", _("Date de début"), TYPC_DTE);
//    $html->setFieldProperties("date_deb", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
//
//    $html->addField("date_fin", _("Date de fin"), TYPC_DTE);
//    $html->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));

    $html->addField("num_client", _("Client"), TYPC_INT);
    $html->addLink("num_client", "rechercher", _("Rechercher"), "#");
    $html->setLinkProperties("rechercher", LINKP_JS_EVENT, array (
                               "onclick" => "OpenBrw('../modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', '"._("Recherche")."');"
                             ));
//    foreach ($adsys["adsys_etat_dossier_credit"] as $key => $name) {
//			$rapports[$key] = $name;
//		}
//
//		$html->addField("etat_dossier", _("Choix de l'état du dossier"), TYPC_LSB);
//		$html->setFieldProperties("etat_dossier", FIELDP_IS_REQUIRED, false);
//		$html->setFieldProperties("etat_dossier", FIELDP_ADD_CHOICES, $rapports);
//		$html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_AUCUN, false);
//		$html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_TOUS, true);


    $html->addTableRefField("prd", _("Type de produit de crédit"), "adsys_produit_credit");

    $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_TOUS, true);

    $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-74");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-75");
    $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-75");

    $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
    $html->buildHTML();
    echo $html->getHTML();
}
/*}}}*/

/*{{{ Kra-74 et Kra- 75 : REE - Impression ou export csv rapport des crédits réechelonnés */
elseif ($global_nom_ecran == "Kra-74" || $global_nom_ecran == "Kra-75") {
    setGlobalIdAgence($agence);
    $data_crit = array ();
    $liste_criteres = array ();

    if (!empty ($num_client)) {
      $data_crit["client"] = $num_client;
      $InfosCli = getClientDatas($num_client);
      switch ($InfosCli["statut_juridique"]) {
      case 1 :
        $nom_cli = $InfosCli["pp_nom"] . " " . $InfosCli["pp_prenom"];
        break;
      case 2 :
        $nom_cli = $InfosCli["pm_raison_sociale"];
        break;
      case 3 :
        $nom_cli = $InfosCli["gi_nom"];
      }

      $liste_criteres[_("Numéro Client")] = $num_client;
      $liste_criteres[_("Nom client")] = $nom_cli;
    }

    $id_prod = null;
    if (!empty ($prd)) {
      $id_prod = $prd;
      $data_crit["produit"] = $prd;
      $libel_prd = getLibelPrdt($prd, "adsys_produit_credit");
      $liste_criteres[_("Produit")] = $libel_prd;
    }

    $DATA = getCrdReech($data_crit);

    if ($DATA != NULL) {

      if ($global_nom_ecran == "Kra-75") {
        //Génération du CSV grâce à XALAN
        $xml = xml_crd_reech($DATA, $liste_criteres, $id_prod, true);
        $csv_file = xml_2_csv($xml, 'credit_reech.xslt');

        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        if (isset($excel) && $excel == 'Export EXCEL'){
          echo getShowEXCELHTML("Gen-13", $csv_file);
        }
        else{
          echo getShowCSVHTML("Gen-13", $csv_file);
        }
      } else
        if ($global_nom_ecran == "Kra-74") {
          //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
          $xml = xml_crd_reech($DATA, $liste_criteres, $id_prod);
          $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'credit_reech.xslt');

          //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
          echo get_show_pdf_html("Gen-13", $fichier_pdf);
        }
      ajout_historique(350, NULL, NULL, $global_nom_login, date("r"), NULL);

    } else {
      $html_msg = new HTML_message(_("Résultats de la requête"));
      $html_msg->setMessage(_("Aucune donnée sélectionnée"));
      $html_msg->addButton("BUTTON_OK", 'Gen-13');
      $html_msg->buildHTML();
      echo $html_msg->HTML_code;
    }
}
/*}}}*/
/*{{{ Kra-76 : OCT - Personnalisation historique des crédits octroyés */
elseif ($global_nom_ecran == "Kra-76") {
	if($global_nom_ecran_prec == "Kra-1") {

        // if(isset($SESSION_VARS['date_deb'],$SESSION_VARS['date_fin'])){
        unset($SESSION_VARS['sequence'], $SESSION_VARS['date_deb'], $SESSION_VARS['date_fin'], $SESSION_VARS['num_client'], $SESSION_VARS['nom_client'], $SESSION_VARS['id_prod'], $SESSION_VARS['etat_dossier']);

        // }
        $html = new HTML_GEN2(_("Personnalisation du rapport"));

        //Remettre $global_id_agence à l'identifiant de l'agence courante
        resetGlobalIdAgence();
        //Agence- Tri par agence
        $list_agence = getAllIdNomAgence();
        if (isSiege()) {
            unset ($list_agence[$global_id_agence]);
            $html->addField("agence", _("Agence"), TYPC_LSB);
            $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
            $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
            $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
        }

        $html->addField("date_deb", _("Date de début"), TYPC_DTE);
        $html->setFieldProperties("date_deb", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));

        $html->addField("date_fin", _("Date de fin"), TYPC_DTE);
        $html->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));

        $html->addField("num_client", _("Client"), TYPC_INT);
        $html->addLink("num_client", "rechercher", _("Rechercher"), "#");
        $html->setLinkProperties("rechercher", LINKP_JS_EVENT, array(
            "onclick" => "OpenBrw('../modules/clients/rech_client.php?m_agc=" . $_REQUEST['m_agc'] . "&field_name=num_client', '" . _("Recherche") . "');"
        ));
        foreach ($adsys["adsys_etat_dossier_credit"] as $key => $name) {
            if ($key > 3)// prendre en compte les crédits dont l'état depasse les états "accepté" et "annulé"
                $rapports[$key] = adb_gettext($name);
        }

        $html->addField("etat_dossier", _("Choix de l'état du dossier"), TYPC_LSB);
        $html->setFieldProperties("etat_dossier", FIELDP_IS_REQUIRED, false);
        $html->setFieldProperties("etat_dossier", FIELDP_ADD_CHOICES, $rapports);
        $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_AUCUN, false);
        $html->setFieldProperties("etat_dossier", FIELDP_HAS_CHOICE_TOUS, true);

        $html->addTableRefField("prd", _("Type de produit de crédit"), "adsys_produit_credit");

        $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_AUCUN, false);
        $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_TOUS, true);

        $js="
                function parseDate(str) {
                    var mdy = str.split('/');
                    return new Date(mdy[2], mdy[1]-1, mdy[0]);
                                        };

                function daydiff(deb, fin) {
                    return Math.round((fin-deb)/(1000*60*60*24));
                    };

                var deb = parseDate(document.ADForm.HTML_GEN_date_date_deb.value);
                var fin = parseDate(document.ADForm.HTML_GEN_date_date_fin.value);

               var diff= daydiff(deb,fin);

                if (diff > 366){
                alert('La durée entre date début et date fin du rapport ne doit pas dépasser 1 an');
                ADFormValid=false;
                };
               ";

        $html->addJS (JSP_BEGIN_CHECK, "JS",$js );

        $html->addField("limite", _("Limite "), TYPC_INT);
        $html->setFieldProperties("limite", FIELDP_DEFAULT, 5000);

	    $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
	    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-77");
      $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
      $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-78");
	    $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
	    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-78");

	    $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
	    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
	    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
	    $html->buildHTML();
	    echo $html->getHTML();
	} else {
		$html = new HTML_GEN2(_("Rapport Suivant"));

    $html->addFormButton(1, 1, "valider", _("Rapport PDF Suivant"), TYPB_SUBMIT);
    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-77");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL Suivant"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-78");
    $html->addFormButton(1, 3, "csv", _("Export CSV Suivant"), TYPB_SUBMIT);
    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-78");
    $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
    $html->buildHTML();
    echo $html->getHTML();
	}

}
/*}}}*/

/*{{{ Kra-77 et Kra- 78 : REE - Impression ou export csv historique des crédits octroyés */
elseif ($global_nom_ecran == "Kra-77" || $global_nom_ecran == "Kra-78") {
    setGlobalIdAgence($agence);
		$data_crit = array ();
		$liste_criteres = array ();

		if (!empty ($date_deb)) {
 	     $DATA["date_deb"] = $date_deb;
 	     $liste_criteres[_("Date de début")] = $date_deb;
 	     $SESSION_VARS['date_deb'] = $date_deb;
    }
  if (!empty ($date_fin)) {
 	   $DATA["date_fin"] = $date_fin;
 	   $liste_criteres[_("Date de fin")] = $date_fin;
 	   $SESSION_VARS['date_fin'] = $date_fin;
 	}
 	if (!empty ($num_client)) {
 	   $DATA["num_client"] = $num_client;
 	   $SESSION_VARS['num_client'] = $num_client;
 	   $statut_juridique = getStatutJuridiqueClient($num_client);
 	   if($statut_juridique == 1){
 	      $nom_cli = getClientNamePP($num_client);
 	   } else
 	     if($statut_juridique == 2){
 	       $nom_cli = getClientNamePM($num_client);
 	     } else
 	     if($statut_juridique == 3 || $statut_juridique == 4){
 	       $nom_cli = getClientNameGI($num_client);
 	     }
 	     $liste_criteres[_("Numéro Client")] = $num_client;
 	     $liste_criteres[_("Nom client")] = $nom_cli;
 	}
 	if ($etat_dossier > 0) {
 	   $DATA["etat"] = $etat_dossier;
 	   $liste_criteres[_("Etat du dossier")] = adb_gettext($adsys["adsys_etat_dossier_credit"][$etat_dossier]);
 	   $SESSION_VARS['etat_dossier'] = $etat_dossier;
 	}
 	if (!empty ($prd)) {
 	   $DATA["produit"] = $prd;
 	   $libel_prd = getLibelPrdt($prd, "adsys_produit_credit");
 	   $liste_criteres[_("Produit")] = $libel_prd;
 	}

 	$SESSION_VARS['liste_criteres'] = array ();
 	$SESSION_VARS['liste_criteres'][_("Date de début")] = $SESSION_VARS['date_deb'];
 	$SESSION_VARS['liste_criteres'][_("Date de fin")] = $SESSION_VARS['date_fin'];

 	if (!empty ($prd)) {
 	   $SESSION_VARS['id_prod'] = $prd;
 	   $SESSION_VARS['liste_criteres'][_("Produit")] = getLibelPrdt($SESSION_VARS['id_prod'], "adsys_produit_credit");
 	}

    // Validate limit
    if ($limite <=0 || $limite > 10000) {
        $limite = 5000;
    }

    //gestion de sequence counter de dossier
    $count = 0;
    if (!isset($SESSION_VARS['sequence'])) {
        $SESSION_VARS['sequence'] = 0;
        $i = 0;
    }
    $i = $SESSION_VARS['sequence'];
    //si id client est saisi
    if(!empty ($SESSION_VARS['num_client'])){
        //Evolution : ticket 584: groupe solidaires
        $DATA = getDoneesRapOctr($SESSION_VARS, 0, $SESSION_VARS['num_client']);
        //gestions de criteres
        $SESSION_VARS['liste_criteres'][_("Numéro Client")] = $SESSION_VARS['num_client'];

    }else{ //tous les clients
        //Evolution : ticket 584: groupe solidaires
        $DATA = getDoneesRapOctr($SESSION_VARS, 0, NULL);

        //recupération du dernier client
        $tab = end($DATA);
        $dernier_id_client = $tab['id_client'];

        //gestions de critere premier client et dernier client
        $SESSION_VARS['liste_criteres'][_("Du Client N°")] = $i;
        $SESSION_VARS['liste_criteres'][_("Au Client N°")] = $dernier_id_client;
    }

    //On recupère le nombre de lignes générées par la requête pour la condition d'arret
    $count = sizeof($DATA);

    if ($DATA != NULL) {
			if ($global_nom_ecran == "Kra-78") {
				// Génération du CSV grâce à XALAN
				$xml = xml_his_crd_oct ( $DATA, $SESSION_VARS ['liste_criteres'], true );
				if ($xml != NULL) {
					$csv_file = xml_2_csv ( $xml, 'his_crd_oct.xslt' );
          if (isset($excel) && ($excel == 'Export EXCEL'||$excel == 'Export EXCEL suivant')){
            echo getShowEXCELHTML ( "Gen-13", $csv_file );
          }
          else{
					  echo getShowCSVHTML ( "Gen-13", $csv_file );
          }
				}
				
			} elseif ($global_nom_ecran == "Kra-77") {

                $dataCount = 0;
                $indexCount = 0;
                $DATA_BIS = array();
                $fichier_pdf_arr = array();

                foreach($DATA as $id_doss=>$data){
                    $DATA_BIS[$id_doss] = $data;

                    $dataCount++;

                    if ($dataCount%$limite == 0 || $count == $dataCount ) {
                        $indexCount++;

                        //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                        $xml = xml_his_crd_oct($DATA_BIS, $SESSION_VARS ['liste_criteres']);

                        $fichier_pdf_arr[] = xml_2_xslfo_2_pdf($xml, 'his_crd_oct.xslt',false,$indexCount);
                        $DATA_BIS = array();
                    }
                }

                $fileCount = 1;
                $js="";
                foreach($fichier_pdf_arr as $fichier_pdf) {
                    // Compilation des rapports pdf générés
                    $js .= get_show_pdf_html(NULL, $fichier_pdf, NULL, "Rapport Historique des crédits octroyés no. $fileCount", $fileCount,(200+($fileCount*50)));
                    $fileCount++;
                }

                if ($js!="") {
                    $MyPage = new HTML_message(_("Génération rapport"));
                    $MyPage->setMessage(_("Le rapport a été généré avec succès !"));
                    $MyPage->addButton(BUTTON_OK, "Gen-13");
                    $MyPage->buildHTML();
                    echo $MyPage->HTML_code." ".$js;
                } else {
                    $erreur = new HTML_erreur(_("Echec lors de la génération du rapport"));
                    $erreur->setMessage(_("Aucun rapport n'a été trouvé."));
                    $erreur->addButton(BUTTON_OK, "Gen-13");
                    $erreur->buildHTML();
                    return $erreur->HTML_code;
                }

				// Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                /*
				$xml = xml_his_crd_oct ( $DATA, $SESSION_VARS ['liste_criteres'] );
				if ($xml != NULL) {
					$fichier_pdf = xml_2_xslfo_2_pdf ( $xml, 'his_crd_oct.xslt' );
					echo get_show_pdf_html ( "Gen-13", $fichier_pdf );
				}
                */
			}
			ajout_historique ( 350, NULL, NULL, $global_nom_login, date ( "r" ), NULL );
		} else {
			$html_msg = new HTML_message ( _ ( "Résultats de la requête" ) );
			$html_msg->setMessage ( _ ( "Aucune donnée sélectionnée" ) );
			$html_msg->addButton ( "BUTTON_OK", 'Gen-13' );
			$html_msg->buildHTML ();
			echo $html_msg->HTML_code;
		}
	}

/*}}}*/
/*{{{ Kra-79 : EMP - Loans Granted to the Directors */
elseif ($global_nom_ecran == 'Kra-79') {
   $html = new HTML_GEN2(_("Loans Granted to the Directors"));
   //Remettre $global_id_agence à l'identifiant de l'agence courante
   resetGlobalIdAgence();
   //Postez le type de rapport : directeurs ou emplyés
   $SESSION_VARS['type_rapport'] = $_POST['type_rapport'];
    //Agence- Tri par agence
    $list_agence = getAllIdNomAgence();
    if (isSiege()) {
      unset ($list_agence[$global_id_agence]);
      $html->addField("agence", _("Agence"), TYPC_LSB);
      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
    }
    //Date d'édition du rapport
    $html->addField("date", _("Date of edition"), TYPC_DTE);
    $html->setFieldProperties("date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
    $html->setFieldProperties("date", FIELDP_IS_REQUIRED, true);

    $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-80");
    $html->addFormButton(1, 2, "csv", _("Export CSV"), TYPB_SUBMIT);
    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-81");

    $html->addFormButton(1, 3, "annuler", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
    $html->buildHTML();
    echo $html->getHTML();
  }
/*}}}*/

/*{{{ Kra-80 et Kra-81 : EMP - Impression ou export Loans Granted to the Directors */
elseif ($global_nom_ecran == 'Kra-80' || $global_nom_ecran == 'Kra-81') {
  setGlobalIdAgence($agence);

  $list_criteres = array ();
  $list_criteres = array (_("Date d'édition") => $date, _("Fréqence") => _("Trimestrielle"));
  //Recupération des données pour les directeurs et employés en même temps
  $DATA = getCreditsEmployesDirigeants($gest, $date);

  if ($global_nom_ecran == 'Kra-80') {
  	 //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
     //On utilise le meme XML pour employés et dirigeants
     if($SESSION_VARS['type_rapport'] == 3){//Pour les directeurs
     	//Pour les directeurs on utilise le tableau $DATA['DETAILS_DIR']
     	$xml = xml_liste_credits_dir($DATA['DETAILS_DIR'], $list_criteres);
     	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'liste_credits_dirs.xslt');
     } else
     if($SESSION_VARS['type_rapport'] == 4){//Pour les employés
     	//Pour les employés on utilise le tableau $DATA['DETAILS_EMP']
     	$xml = xml_liste_credits_emp($DATA['DETAILS_EMP'], $list_criteres);
     	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'liste_credits_emps.xslt');
     }

     //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
     echo get_show_pdf_html("Gen-13", $fichier_pdf);

  } else if ($global_nom_ecran == 'Kra-81') {
    //Génération du CSV grâce à XALAN
    if($SESSION_VARS['type_rapport'] == 3){//Pour les directeurs
     	//Pour les directeurs on utilise le tableau $DATA['DETAILS_DIR']
     	$xml = xml_liste_credits_dir($DATA['DETAILS_DIR'], $list_criteres);
     	$csv_file = xml_2_csv($xml, 'liste_credits_dirs.xslt');
     } else
     if($SESSION_VARS['type_rapport'] == 4){//Pour les employés
     	//Pour les employés on utilise le tableau $DATA['DETAILS_EMP']
     	$xml = xml_liste_credits_emp($DATA['DETAILS_EMP'], $list_criteres);
     	$csv_file = xml_2_csv($xml, 'liste_credits_emps.xslt');
     }

    //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
    echo getShowCSVHTML("Gen-13", $csv_file);
 }
}
/*}}}*/
/*{{{ Kra-82 : PGE - Personnalisation emprunteurs les plus grands */
elseif ($global_nom_ecran == "Kra-82") {
		$html = new HTML_GEN2(_("Personnalisation du rapport"));

		//Remettre $global_id_agence à l'identifiant de l'agence courante
        resetGlobalIdAgence();
        //Agence- Tri par agence
        $list_agence = getAllIdNomAgence();
        if (isSiege()) {
	      unset ($list_agence[$global_id_agence]);
	      $html->addField("agence", _("Agence"), TYPC_LSB);
	      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
	      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
	      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
	    }


	    //Date d'édition du rapport
    $html->addField("date", _("Date d'édition"), TYPC_DTE);
    $html->setFieldProperties("date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
    $html->setFieldProperties("date", FIELDP_IS_REQUIRED, true);

		$html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
		$html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-83");
		$html->addFormButton(1, 2, "csv", _("Export CSV"), TYPB_SUBMIT);
		$html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-84");

		$html->addFormButton(1, 3, "annuler", _("Annuler"), TYPB_SUBMIT);
		$html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
		$html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
		$html->buildHTML();
		echo $html->getHTML();
	}
/*}}}*/
/*{{{ Kra-83 et Kra- 84 : PGE - Impression ou export csv emprunteurs les plus grands */
elseif ($global_nom_ecran == "Kra-83" || $global_nom_ecran == "Kra-84") {
	setGlobalIdAgence($agence);
	// récupération de la saisie
	$list_criteres = array ();
	$list_criteres = array ("Date d'édition" => $date,
		  _("Frequence") => "Trimestrielle");

	if ($gest == '') // le gestionnaire
		$gest = NULL;
	if($gest > 0)
	  $list_criteres = array (_("Gestionnaire") => getLibel("ad_uti", $gest));

 	// récupération des encours les plus grands
 	$DATA = getListePlusGrandsEmp($limit, $gest, $date);
 	if ($global_nom_ecran == 'Kra-84') {
		//Génération du CSV grâce à XALAN
		$xml = xml_liste_plus_grds_emp($DATA, $list_criteres, true);
		$csv_file = xml_2_csv($xml, 'liste_plus_grds_emp.xslt');

		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo getShowCSVHTML("Gen-13", $csv_file);
	}
	elseif ($global_nom_ecran == 'Kra-83') {
		//Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
		$xml = xml_liste_plus_grds_emp($DATA, $list_criteres);
		$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'liste_plus_grds_emp.xslt');
		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo get_show_pdf_html("Gen-13", $fichier_pdf);
	}
}
/*}}}*/
/*{{{ Kra-85 : CRA - Personnalisation situation des risques par secteur activité */
elseif ($global_nom_ecran == "Kra-85") {
		$html = new HTML_GEN2(_("Personnalisation du rapport"));

		//Remettre $global_id_agence à l'identifiant de l'agence courante
        resetGlobalIdAgence();
        //Agence- Tri par agence
        $list_agence = getAllIdNomAgence();
        if (isSiege()) {
	      unset ($list_agence[$global_id_agence]);
	      $html->addField("agence", _("Agence"), TYPC_LSB);
	      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
	      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
	      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
	    }

	    //Date d'édition du rapport
    $html->addField("date", _("Date d'édition"), TYPC_DTE);
    $html->setFieldProperties("date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
    $html->setFieldProperties("date", FIELDP_IS_REQUIRED, true);

		$html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
		$html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-86");
		$html->addFormButton(1, 2, "csv", _("Export CSV"), TYPB_SUBMIT);
		$html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-87");

		$html->addFormButton(1, 3, "annuler", _("Annuler"), TYPB_SUBMIT);
		$html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
		$html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
		$html->buildHTML();
		echo $html->getHTML();
	}
/*}}}*/
/*{{{ Kra-86 et Kra- 87 : CRA - Impression ou export csv situation des risques par secteur activité */
elseif ($global_nom_ecran == "Kra-86" || $global_nom_ecran == "Kra-87") {
	setGlobalIdAgence($agence);
	// récupération de la saisie
	$list_criteres = array ();
	$list_criteres = array (
		  _("Rapport") => _("Situation des risques par secteur"),
		  _("Date d'édition") => $date,
		  _("Fréquence") => _("Trimestrielle"));

 	// récupération des données par secteur
 	$DATA = getRisqueCreditSecteur($gest, $date);
 	if ($global_nom_ecran == 'Kra-87') {
		//Génération du CSV grâce à XALAN
		$xml = xml_risque_credit_secteur($DATA, $list_criteres, true);
		$csv_file = xml_2_csv($xml, 'risque_par_activite.xslt');

		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo getShowCSVHTML("Gen-13", $csv_file);
	}
	elseif ($global_nom_ecran == 'Kra-86') {
		//Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
		$xml = xml_risque_credit_secteur($DATA, $list_criteres);
		$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'risque_par_activite.xslt');
		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo get_show_pdf_html("Gen-13", $fichier_pdf);
	}
}
/*}}}*/
/*{{{ Kra-88 : RCR - Personnalisation Recouvrement de créances douteuses, litigieuses et contentieuses */
elseif ($global_nom_ecran == "Kra-88") {
		$html = new HTML_GEN2(_("Personnalisation du rapport"));

		//Remettre $global_id_agence à l'identifiant de l'agence courante
        resetGlobalIdAgence();
        //Agence- Tri par agence
        $list_agence = getAllIdNomAgence();
        if (isSiege()) {
	      unset ($list_agence[$global_id_agence]);
	      $html->addField("agence", _("Agence"), TYPC_LSB);
	      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
	      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
	      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
	    }

	    //Date d'édition du rapport
    $html->addField("date", _("Date d'édition"), TYPC_DTE);
    $html->setFieldProperties("date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
    $html->setFieldProperties("date", FIELDP_IS_REQUIRED, true);

		$html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
		$html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-89");
		$html->addFormButton(1, 2, "csv", _("Export CSV"), TYPB_SUBMIT);
		$html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-90");

		$html->addFormButton(1, 3, "annuler", _("Annuler"), TYPB_SUBMIT);
		$html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
		$html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
		$html->buildHTML();
		echo $html->getHTML();
	}
/*}}}*/
/*{{{ Kra-89 et Kra-90 : RCR - Impression ou export csv Recouvrement de créances douteuses, litigieuses et contentieuses */
elseif ($global_nom_ecran == "Kra-89" || $global_nom_ecran == "Kra-90") {
	setGlobalIdAgence($agence);
	// récupération de la saisie
	$list_criteres = array ();
	$list_criteres = array (
		  _("Rapport") => "Recouvrement de créances douteuses, litigieuses et contentieuses",
		  _("Date d'édition") => $date,
		  _("Frequence") => _("Trimestrielle"));

 	// récupération des données par secteur
 	$DATA = getRecouvrementCredit($gest, $date);
 	if ($global_nom_ecran == 'Kra-90') {
		//Génération du CSV grâce à XALAN
		$xml = xml_recouvrement_creance_BNR($DATA, $list_criteres, true);
		$csv_file = xml_2_csv($xml, 'recouvrement_creance_bnr.xslt');

		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo getShowCSVHTML("Gen-13", $csv_file);
	}
	elseif ($global_nom_ecran == 'Kra-89') {
		//Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
		$xml = xml_recouvrement_creance_BNR($DATA, $list_criteres);
		$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'recouvrement_creance_bnr.xslt');
		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo get_show_pdf_html("Gen-13", $fichier_pdf);
	}
}
/*}}}*/
/*{{{ Kra-91 : PCS - Personnalisation Provisions des crédits en souffrances */
elseif ($global_nom_ecran == "Kra-91") {
		$html = new HTML_GEN2(_("Personnalisation du rapport"));

		//Remettre $global_id_agence à l'identifiant de l'agence courante
        resetGlobalIdAgence();
        //Agence- Tri par agence
        $list_agence = getAllIdNomAgence();
        if (isSiege()) {
	      unset ($list_agence[$global_id_agence]);
	      $html->addField("agence", _("Agence"), TYPC_LSB);
	      $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
	      $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
	      $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
	    }

	  //Date d'édition du rapport
    //$html->addField("date_debut_provision", _(" Date début de provision "), TYPC_DTE);
    $html->addField("date_fin_provision", _(" Date "), TYPC_DTE);
    $html->setFieldProperties("date_fin_provision", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))));
    $html->setFieldProperties("date_fin_provision", FIELDP_IS_REQUIRED, true);
    
    
    //$html->setFieldProperties("date_provision", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))));
    //$html->setFieldProperties("date_provision", FIELDP_IS_REQUIRED, true);
    $SESSION_VARS['etat_credit']=getEtatCreditprovision();
    $html->addField("etat_credit", _("Etat crédit"), TYPC_LSB);
    $html->setFieldProperties("etat_credit", FIELDP_ADD_CHOICES,  $SESSION_VARS['etat_credit']);
    $html->addField("prov_non_null", _("Solde provision non nul?"), TYPC_BOL);
    $html->setFieldProperties("prov_non_null", FIELDP_DEFAULT, TRUE);
		$html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
		$html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-92");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-93");
		$html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
		$html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-93");

		$html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
		$html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
		$html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
		$html->buildHTML();
		echo $html->getHTML();
	}
/*}}}*/
/*{{{ Kra-92 et Kra-93 : RCR - Impression ou export csv Provisions des crédits en souffrances */
elseif ($global_nom_ecran == "Kra-92" || $global_nom_ecran == "Kra-93") {
	setGlobalIdAgence($agence);
	// récupération de la saisie
  /*
  if($date_debut_provision!= '' and $date_fin_provision!= '') {
  	$list_criteres[_("Date début Provision")]=$date_debut_provision;
  	$list_criteres[_("Date fin Provision")]=$date_fin_provision;
  } elseif($date_fin_provision!= '') {
  	$list_criteres[_("Date Provision")]=$date_fin_provision;
  } elseif($date_debut_provision!= ''){
  	$list_criteres[_("Date Provision")]=$date_debut_provision;
  }
  */
  
  if($date_fin_provision!= '') {
  	$list_criteres[_("Date Provision")]=$date_fin_provision;
  }
  
  if(! is_null( $etat_credit)) {
  	$list_criteres[_("Etat crédit")]= $SESSION_VARS['etat_credit'][$etat_credit];
  }
  if($prov_non_null) {
  	$list_criteres[_("Solde provision non nul")]='';
  }
  unset( $SESSION_VARS['etat_credit']);
 	// récupération des données
 	$DATA=getDossierProvisionne(null,$date_fin_provision,$etat_credit,$prov_non_null);
 	if ($global_nom_ecran == 'Kra-92') {
 		//Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
		$xml = xml_DossierProvisionne($DATA, $list_criteres);
		$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'provisioncredit.xslt');
		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
		echo get_show_pdf_html("Gen-13", $fichier_pdf);
 	}	elseif ($global_nom_ecran == 'Kra-93') {
		//Génération du CSV grâce à XALAN
		$xml = xml_DossierProvisionne($DATA, $list_criteres,NULL,true);
		$csv_file = xml_2_csv($xml, 'provisioncredit.xslt');

		//Message de confirmation + affichage du rapport dans une nouvelle fenêtre
    if (isset($excel) && $excel == 'Export EXCEL'){
      echo getShowEXCELHTML("Gen-13", $csv_file);
    }
    else{
		  echo getShowCSVHTML("Gen-13", $csv_file);
    }
 	}

}
/*}}}*/

/*{{{ Kra-94 : REC -  Personnalisation Recouvrement sur les crédits */
elseif ($global_nom_ecran == "Kra-94") 
{
    $html = new HTML_GEN2(_("Personnalisation du rapport Recouvrement sur les crédits"));

    //Remettre $global_id_agence à l'identifiant de l'agence courante
    resetGlobalIdAgence();
        
    //Agence- Tri par agence
    $list_agence = getAllIdNomAgence();
    
    if (isSiege()) {
        unset ($list_agence[$global_id_agence]);
        $html->addField("agence", _("Agence"), TYPC_LSB);
        $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
        $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
        $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
    }
    unset ($SESSION_VARS['id_prod']);
    //Gestionnaire- Tri par agent gestionnaire
    $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
    $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);
    
    //date de l'export : periodic
    //ticket 720 -> ajout du parametre date debut
    $html->addField("export_date_debut", _("Date Debut"), TYPC_DTE);
    $html->setFieldProperties("export_date_debut", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, 1, 1, date("Y"))));
    $html->setFieldProperties("export_date_debut", FIELDP_IS_REQUIRED, true);
    $html->addField("export_date", _("Date Fin"), TYPC_DTE); //ticket 720 -> modification libel
    $html->setFieldProperties("export_date", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m"), date("d"), date("Y"))));
    $html->setFieldProperties("export_date", FIELDP_IS_REQUIRED, true);

    // Etats des credits
    $etats = getIDLibelTousEtatCredit();
    $etats['CA'] = "Crédits Actifs"; //trac#720 : Commentaire no.5
    $etats['SOLDE'] = "Crédits Soldé"; //REL-30 : Etat soldé (6)
    $html->addField("etat", _("Etat crédit"), TYPC_LSB );
    $html->setFieldProperties("etat", FIELDP_ADD_CHOICES, $etats);
    $html->setFieldProperties("etat", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("etat", FIELDP_HAS_CHOICE_TOUS, true);
    
    //produits
     $html->addTableRefField("prd", _("Type de produit de crédit"), "adsys_produit_credit");
     $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_AUCUN, false);
     $html->setFieldProperties("prd", FIELDP_HAS_CHOICE_TOUS, true);
    
    // Type affichage
    $list_affich = array(1 => _("Détaillé"), 2 => _("Synthétique"));
    $html->addField("type_affich", _("Type affichage"), TYPC_LSB);
    $html->setFieldProperties("type_affich", FIELDP_ADD_CHOICES, $list_affich);
    $html->setFieldProperties("type_affich", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("type_affich", FIELDP_HAS_CHOICE_TOUS, false);
     
    //Boutons
    $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-95");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-96");
    $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-96");
    $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    //HTML
    $html->buildHTML();
    echo $html->getHTML();

}
/*}}}*/

/*{{{ Kra-95 et Kra-96 : REC - Impression ou export csv Recouvrement sur les crédits */
elseif ($global_nom_ecran == "Kra-95" || $global_nom_ecran == "Kra-96") 
{ 
    
   //setGlobalIdAgence($agence);
    
    // default values
    if(empty($gest)) $gest = 0;
    if(empty($etat)) $etat = 0;   
    
    //get id produit
    if (!empty ($prd)) {
    	$SESSION_VARS['id_prod'] = $prd;
    }
    

    if ($global_nom_ecran == 'Kra-96') {
        //Génération du CSV grâce à XALAN       ;
        $xml = xml_recouvrement_credit($gest, $export_date, $etat, $SESSION_VARS['id_prod'], $type_affich, true, $export_date_debut) ;
                
        if($xml != NULL){
            $csv_file = xml_2_csv($xml, 'recouvrement_credit.xslt');

            //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
            if (isset($excel) && $excel == 'Export EXCEL'){
              echo getShowEXCELHTML("Gen-13", $csv_file);
            }
            else{
              echo getShowCSVHTML("Gen-13", $csv_file);
            }
        }

    } elseif ($global_nom_ecran == 'Kra-95') {
        //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
        $xml = xml_recouvrement_credit($gest, $export_date, $etat, $SESSION_VARS['id_prod'], $type_affich, false, $export_date_debut) ;
            
        if($xml != NULL){
            $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'recouvrement_credit.xslt');
            //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
            echo get_show_pdf_html("Gen-13", $fichier_pdf);
        }
    }
    if($xml == NULL){
        $html_msg = new HTML_message(_("Résultats de la requête"));
        $html_msg->setMessage(_("Aucun crédit n'a été octroyé"));
        $html_msg->addButton("BUTTON_OK", 'Gen-13');
        $html_msg->buildHTML();
        echo $html_msg->HTML_code;
    }
}
/*}}}*/

/*{{{ Kra-97 : LCR - Rapport Suivi Ligne de crédit */
elseif ($global_nom_ecran == "Kra-97")
{
    $html = new HTML_GEN2(_("Rapport Suivi Ligne de crédit"));

    // Remettre $global_id_agence à l'identifiant de l'agence courante
    resetGlobalIdAgence();

    // Agence- Tri par agence
    $list_agence = getAllIdNomAgence();

    if (isSiege()) {
        unset ($list_agence[$global_id_agence]);
        $html->addField("agence", _("Agence"), TYPC_LSB);
        $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
        $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
        $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
    }

    // Date de début
    $html->addField("date_deb", _("Date de début"), TYPC_DTE);
    $html->setFieldProperties("date_deb", FIELDP_DEFAULT, date("d/m/Y", mktime(0, 0, 0, date("m") - 6, date("d"), date("Y"))));
    $html->setFieldProperties("date_deb", FIELDP_IS_REQUIRED, true);

    // Date de fin
    $html->addField("date_fin", _("Date de fin"), TYPC_DTE);
    $html->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
    $html->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);

    // Gestionnaire- Tri par agent gestionnaire
    $html->addTableRefField("gest", _("Gestionnaire"), "ad_uti");
    $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("gest", FIELDP_HAS_CHOICE_TOUS, true);

    // Client
    $html->addField("num_client", _("Client"), TYPC_INT);
    $html->addLink("num_client", "rechercher", _("Rechercher"), "#");
    $html->setLinkProperties("rechercher", LINKP_JS_EVENT, array ("onclick" => "OpenBrw('../modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client', 'Recherche');"));

    // Produit ligne de crédit
    $prd_lcr = getListeProduitCredit('mode_calc_int=5');
    $html->addField("prd_lcr", _("Produit ligne de crédit"), TYPC_LSB );
    $html->setFieldProperties("prd_lcr", FIELDP_ADD_CHOICES, $prd_lcr);
    $html->setFieldProperties("prd_lcr", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("prd_lcr", FIELDP_HAS_CHOICE_TOUS, true);

    //Boutons
    $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-98");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-99");
    $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-99");
    $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    //HTML
    $html->buildHTML();
    echo $html->getHTML();
}
/*}}}*/

/*{{{ Kra-98 et Kra-99 : LCR - Impression ou export csv Suivi Ligne de crédit */
elseif ($global_nom_ecran == "Kra-98" || $global_nom_ecran == "Kra-99")
{

    setGlobalIdAgence($agence);

    if ($gest == "") {
        $gest = 0;
    }

    //get id produit
    if (!empty ($prd_lcr)) {
        $SESSION_VARS['id_prod'] = $prd_lcr;
    }

    if ($global_nom_ecran == 'Kra-99') {
        // Génération du CSV grâce à XALAN       ;
        $xml = xml_suivi_ligne_credit($date_deb, $date_fin, $gest, $num_client, $SESSION_VARS['id_prod'], true);

        if($xml != NULL){
            $csv_file = xml_2_csv($xml, 'suivi_ligne_credit.xslt');

            //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
            if (isset($excel) && $excel == 'Export EXCEL'){
              echo getShowEXCELHTML("Gen-13", $csv_file);
            }
            else{
              echo getShowCSVHTML("Gen-13", $csv_file);
            }
        }

    } elseif ($global_nom_ecran == 'Kra-98') {
        //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
        $xml = xml_suivi_ligne_credit($date_deb, $date_fin, $gest, $num_client, $SESSION_VARS['id_prod'], false);

        if($xml != NULL){
            $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'suivi_ligne_credit.xslt');
            //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
            echo get_show_pdf_html("Gen-13", $fichier_pdf);
        }
    }
    if($xml == NULL){
        $html_msg = new HTML_message(_("Résultats de la requête"));
        $html_msg->setMessage(_("Aucun crédit n'a été trouvé"));
        $html_msg->addButton("BUTTON_OK", 'Gen-13');
        $html_msg->buildHTML();
        echo $html_msg->HTML_code;
    }
}
   /*{{{ Kra-101 : ICT - Rapport Inventaire de Credits */
elseif ($global_nom_ecran == "Kra-101") {
    global $global_id_exo;

    $html = new HTML_GEN2(_("Rapport inventaire de crédits"));

    $exo = getExercicesComptables($global_id_exo);//Détails exercise courante

    resetGlobalIdAgence();
    //Agence- Tri par agence
    $list_agence = getAllIdNomAgence();
    if (isSiege()) {
        unset ($list_agence[$global_id_agence]);
        $html->addField("agence", _("Agence"), TYPC_LSB);
        $html->setFieldProperties("agence", FIELDP_ADD_CHOICES, $list_agence);
        $html->setFieldProperties("agence", FIELDP_HAS_CHOICE_AUCUN, true);
        $html->setFieldProperties("agence", FIELDP_IS_REQUIRED, true);
    }

    // Date de début
    $html->addField("date_deb", _("Date de début"), TYPC_DTE);
    $html->setFieldProperties("date_deb", FIELDP_DEFAULT, $exo[0]["date_deb_exo"]);//date("d/m/Y", mktime(0, 0, 0, date("m") - 6, date("d"), date("Y"))));
    $html->setFieldProperties("date_deb", FIELDP_IS_REQUIRED, true);

    // Date de fin
    $html->addField("date_fin", _("Date de fin"), TYPC_DTE);
    $html->setFieldProperties("date_fin", FIELDP_DEFAULT, date("d/m/Y"));
    $html->setFieldProperties("date_fin", FIELDP_IS_REQUIRED, true);

    // Produit de crédit
    $html->addTableRefField("prd_credit", _("Produit de crédit"), "adsys_produit_credit");
    $html->setFieldProperties("prd_credit", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("prd_credit", FIELDP_HAS_CHOICE_TOUS, true);
    $html->setFieldProperties("prd_credit", FIELDP_JS_EVENT, array("onChange"=>"SwitchCredit();"));

    $Jchck= "   function SwitchCredit()
                {
                        var produit = document.ADForm.HTML_GEN_LSB_prd_credit.value;
                        var objet = document.ADForm.HTML_GEN_LSB_obj_credit.value;
                        document.ADForm.HTML_GEN_LSB_prd_credit.disabled = false;
                        document.ADForm.HTML_GEN_LSB_obj_credit.disabled = false;
                        if (produit != 0){
                           document.ADForm.HTML_GEN_LSB_obj_credit.disabled = true;
                        }
                         else if (objet != 0){
                            document.ADForm.HTML_GEN_LSB_prd_credit.disabled = true;
                        }
                }
            ";
    $html->addJS(JSP_FORM, "JS_switch", $Jchck);

    // Objet de crédit
    $html->addTableRefField("obj_credit", _("Objet de crédit"), "adsys_objets_credits");
    $html->setFieldProperties("obj_credit", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("obj_credit", FIELDP_HAS_CHOICE_TOUS, true);
    $html->setFieldProperties("obj_credit", FIELDP_JS_EVENT, array("onChange"=>"SwitchCredit();"));

    $html->addField("etat", _("Etat de credits"), TYPC_LSB);
    $choix = array (
        "1" => _("Encours"),
        "3" => _("Soldé"),
        "2" => _("Passé en perte")
    );
    $html->setFieldProperties("etat", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("etat", FIELDP_HAS_CHOICE_TOUS, true);
    $html->setFieldProperties("etat", FIELDP_ADD_CHOICES, $choix);


    //Boutons
    $html->addFormButton(1, 1, "valider", _("Rapport PDF"), TYPB_SUBMIT);
    $html->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Kra-103");
    $html->addFormButton(1, 2, "excel", _("Export EXCEL"), TYPB_SUBMIT);
    $html->setFormButtonProperties("excel", BUTP_PROCHAIN_ECRAN, "Kra-102");
    $html->addFormButton(1, 3, "csv", _("Export CSV"), TYPB_SUBMIT);
    $html->setFormButtonProperties("csv", BUTP_PROCHAIN_ECRAN, "Kra-102");
    $html->addFormButton(1, 4, "annuler", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-13");
    $html->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    //HTML
    $html->buildHTML();
    echo $html->getHTML();
}

/*{{{ Kra-102 et Kra-103 : LCR - Impression ou export csv Inventaire de crédit */
elseif ($global_nom_ecran == "Kra-102" || $global_nom_ecran == "Kra-103") {
    $v_etat_tous = false; //is etat critere tous ou non : affichage etat dossier
    $v_etat_radie = false; //is etat critere radie ou non : changer libel capital restant du -> capital passer en perte
    if (isSiege()) {
        if ($agence != '') {

            //Sélection d'une agence au siège
            $list_agence[$agence] = $agence;
        } else {
            $list_agence = getAllIdNomAgence();
            //Pas d'impression au siège
            unset ($list_agence[$global_id_agence]);
        }
    } else {
        //Dans une agence
        $list_agence[$global_id_agence] = $global_id_agence;
    }

    $critere = array (_("Date début ") => $date_deb, _("Date fin ") => $date_fin);

    if(isset($prd_credit)){
        $where['id_prod']=$prd_credit;
        $prod_crd = getLibelPrdCredit("adsys_produit_credit",$prd_credit);
        $prod_crd2=$prod_crd->param[0];
        $critere[_("Produit de crédits")] = $prod_crd2;
    }
    else if (isset($obj_credit)){
        $where['obj_dem']=$obj_credit;
        $obj_crd = getLibelPrdCredit("adsys_objets_credits",$obj_credit);
        $obj_crd2=$obj_crd->param[0];
        $critere[_("Objet de crédits")]=$obj_crd2;
    }
    else{
        $critere[_("Produit de crédits")]="Tous";
        $critere[_("Objet de crédits")]="Tous";
    }

    if ($_POST["etat"]==1){
        $critere[_("Etat de credits")]="Encours";
    }else if ($_POST["etat"]==2){
        $critere[_("Etat de credits")]="Passé en perte";
        $v_etat_radie = true;
    }
    else if ($_POST["etat"]==3){
         $critere[_("Etat de credits")]="Soldé";
    }
    else{
        $critere[_("Etat de credits")]="Tous";
        $v_etat_tous = true;
    }
    unset($SESSION_VARS['prodCre']);
    unset($SESSION_VARS['objCre']);
    $etat_dossier_inventaire = $_POST["etat"];


    //recupération des données à afficher
    $DATA = get_rapport_inventaire_credit($where,$date_deb,$date_fin,$etat_dossier_inventaire);

    if ($global_nom_ecran == 'Kra-103') {
        $DATA_BIS = array();

        if (isset($_POST["prd_credit"])) {
            $Crd = getListeProduitCredit($condition);
            $id_prod = $_POST["prd_credit"];

        } else if (isset($_POST["obj_credit"])) {
            $Crd = getListeObjetCredit($condition);
            $id_prod = $_POST["obj_credit"];
        } else {
            $Crd = getListeDoss($condition);
        }



    $dataCount = 0;
    $indexCount = 0;
    $linenum=0;
    $fichier_pdf_arr = array();

    $totalCapitalDebut = 0;
    $totalCapitalDebutPeriod = 0;
    $totalCapitalRembPeriod = 0;
    $totalIntOrdiRembPeriod = 0;
    $totalIntRetRembPeriod = 0;
    $totalmntTotalRemPeriod = 0;
    $totalCapitalRestDuNonRadie = 0;
    $totalCapitalRestDuRadie = 0;
    $rowCount = 0;
    $idEtatPerte = getIDEtatPerte();

    foreach($Crd as $id_prod=>$libel) {
        if (isset($DATA[$id_prod])) {
            foreach($DATA[$id_prod] as $id_doss=>$rowData) {
                if ($rowData["type_rapport"]!='3-RADIE-SOLDE') {
                  $totalCapitalDebut += floatval($rowData["cre_mnt_deb_per"]);
                  $totalCapitalDebutPeriod += floatval($rowData["mnt_cap_debut"]);
                  $totalCapitalRembPeriod += floatval($rowData["tot_mnt_remb_cap"]);
                  /*$totalIntOrdiRembPeriod += floatval($rowData["tot_mnt_remb_int"]);
                  $totalIntRetRembPeriod += floatval($rowData["mnt_remb_pen"]);
                  $totalmntTotalRemPeriod += floatval($rowData["montant_tot"]);*/
                }
                $totalIntOrdiRembPeriod += floatval($rowData["tot_mnt_remb_int"]);
                $totalIntRetRembPeriod += floatval($rowData["mnt_remb_pen"]);
                $totalmntTotalRemPeriod += floatval($rowData["montant_tot"]);
                if (($rowData["type_rapport"]=='1-ENCOURS') && $rowData["perte_capital"]==0 && $rowData["mnt_restant_du"] > 0) {
                  $totalCapitalRestDuNonRadie += floatval($rowData["mnt_restant_du"]);
                }
                if (($rowData["type_rapport"]=='4-RADIE' || $rowData["type_rapport"]=='2-SOLDE') && $rowData["perte_capital"] > 0){
                  //if ($v_etat_radie === true || $v_etat_tous === true){
                    $totalCapitalRestDuRadie += floatval($rowData["perte_capital"]);
                  //}
                }

                $rowCount ++;
            }
        }
    }

    $totalPages=0;
    foreach($Crd as $id_prod=>$libel) {
        if (isset($DATA[$id_prod])) {
            foreach($DATA[$id_prod] as $id_doss=>$rowData) {
                $DATA_BIS[$id_prod][$id_doss] = $rowData;

                $dataCount++;

                if ($dataCount%10000 == 0 || $rowCount == $dataCount ){
                    $indexCount++;
                    //xml pr la génération du pdf
                    $xml = xml_list_inventaire_credits($DATA_BIS, $critere,$date_deb,$date_fin,$linenum,false,false,$v_etat_tous,$v_etat_radie,false);
                    //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
                    //on garde une liste des données de chaque lot du rapport
                    $Data_arr[]=$DATA_BIS;
                    $pdf_out = xml_2_xslfo_2_pdf($xml, 'inventaire_credit_csv.xslt',false,$indexCount);
                    $fichier_pdf_arr[] = $pdf_out;
                    $pgNum = getPDFPages("$pdf_out");
                    $totalPages += $pgNum;
                    $DATA_BIS = array();
                }
            }
        }
    }

    $critere[_("Nombre total de page du rapport")] = $totalPages;
    $critere[_("Solde capital début période")] = afficheMontant($totalCapitalDebutPeriod);
    $critere[_("Solde capital déboursé au cours de la période")] = afficheMontant($totalCapitalDebut);
    $critere[_("Capital remboursé au cours de la période")] = afficheMontant($totalCapitalRembPeriod);
    $critere[_("Intérêts ordinaires remboursés au cours de la période")] = afficheMontant($totalIntOrdiRembPeriod);
    $critere[_("Intérêts de retard remboursés au cours de la période")] = afficheMontant($totalIntRetRembPeriod);
    $critere[_("Montant total remboursé au cours de la période")] = afficheMontant($totalmntTotalRemPeriod);
    $critere[_("Encours de crédits à la fin de période")] = afficheMontant($totalCapitalRestDuNonRadie);
    $critere[_("Solde Capital passé en perte au cours de la periode")] = afficheMontant($totalCapitalRestDuRadie);

    //régén§ration de la première page du rapport pdf avec les infos-synthetique.
    $linenum = 0;
    $xml_1  = xml_list_inventaire_credits($Data_arr[0], $critere,$date_deb,$date_fin,$linenum,false,true,$v_etat_tous,$v_etat_radie,false);
    $pdf_1 = xml_2_xslfo_2_pdf($xml_1, 'inventaire_credit_csv.xslt',false,$indexCount+1);

    $fichier_pdf_arr[0]=$pdf_1;

    $fileCount = 1;
    $js="";
    foreach($fichier_pdf_arr as $fichier_pdf) {
        // Compilation des rapports pdf générés
        $js .= get_show_pdf_html(NULL, $fichier_pdf, NULL, "Rapport inventaire de crédits no. $fileCount", $fileCount,(200+($fileCount*50)));
        $fileCount++;
    }

    if ($js!="") {
        $MyPage = new HTML_message(_("Génération rapport"));
        $MyPage->setMessage(_("Le rapport a été généré avec succès !"));
        $MyPage->addButton(BUTTON_OK, "Gen-13");
        $MyPage->buildHTML();
        echo $MyPage->HTML_code." ".$js;
    } else {
        $erreur = new HTML_erreur(_("Echec lors de la génération du rapport"));
        $erreur->setMessage(_("Aucun rapport n'a été trouvé."));
        $erreur->addButton(BUTTON_OK, "Gen-13");
        $erreur->buildHTML();
        return $erreur->HTML_code;
    }

}
    elseif($global_nom_ecran == 'Kra-102') {
        $linenum=0;

        // REL-40 : Affichage Informations Synthetiques pour l'export CSV et EXCEL
        if (isset($_POST["prd_credit"])) {
          $Crd = getListeProduitCredit($condition);
          $id_prod = $_POST["prd_credit"];

        } else if (isset($_POST["obj_credit"])) {
          $Crd = getListeObjetCredit($condition);
          $id_prod = $_POST["obj_credit"];
        } else {
          $Crd = getListeDoss($condition);
        }
        $dataCount = 0;
        $indexCount = 0;
        $linenum=0;
        $fichier_pdf_arr = array();

        $totalCapitalDebut = 0;
        $totalCapitalDebutPeriod = 0;
        $totalCapitalRembPeriod = 0;
        $totalIntOrdiRembPeriod = 0;
        $totalIntRetRembPeriod = 0;
        $totalmntTotalRemPeriod = 0;
        $totalCapitalRestDuNonRadie = 0;
        $totalCapitalRestDuRadie = 0;
        $rowCount = 0;
        $idEtatPerte = getIDEtatPerte();

        foreach($Crd as $id_prod=>$libel) {
          if (isset($DATA[$id_prod])) {
            foreach($DATA[$id_prod] as $id_doss=>$rowData) {
              if ($rowData["type_rapport"]!='3-RADIE-SOLDE') {
                $totalCapitalDebut += floatval($rowData["cre_mnt_deb_per"]);
                $totalCapitalDebutPeriod += floatval($rowData["mnt_cap_debut"]);
                $totalCapitalRembPeriod += floatval($rowData["tot_mnt_remb_cap"]);
                /*$totalIntOrdiRembPeriod += floatval($rowData["tot_mnt_remb_int"]);
                $totalIntRetRembPeriod += floatval($rowData["mnt_remb_pen"]);
                $totalmntTotalRemPeriod += floatval($rowData["montant_tot"]);*/
              }
              $totalIntOrdiRembPeriod += floatval($rowData["tot_mnt_remb_int"]);
              $totalIntRetRembPeriod += floatval($rowData["mnt_remb_pen"]);
              $totalmntTotalRemPeriod += floatval($rowData["montant_tot"]);
              if (($rowData["type_rapport"]=='1-ENCOURS') && $rowData["perte_capital"]==0 && $rowData["mnt_restant_du"] > 0) {
                $totalCapitalRestDuNonRadie += floatval($rowData["mnt_restant_du"]);
              }
              if (($rowData["type_rapport"]=='4-RADIE' || $rowData["type_rapport"]=='2-SOLDE') && $rowData["perte_capital"] > 0){
                //if ($v_etat_radie === true || $v_etat_tous === true){
                $totalCapitalRestDuRadie += floatval($rowData["perte_capital"]);
                //}
              }

              $rowCount ++;
            }
          }
        }
        //$critere[_("Nombre total de page du rapport")] = $totalPages;
        $critere[_("Solde capital début période")] = afficheMontant($totalCapitalDebutPeriod,false,true);
        $critere[_("Solde capital déboursé au cours de la période")] = afficheMontant($totalCapitalDebut,false,true);
        $critere[_("Capital remboursé au cours de la période")] = afficheMontant($totalCapitalRembPeriod,false,true);
        $critere[_("Intérêts ordinaires remboursés au cours de la période")] = afficheMontant($totalIntOrdiRembPeriod,false,true);
        $critere[_("Intérêts de retard remboursés au cours de la période")] = afficheMontant($totalIntRetRembPeriod,false,true);
        $critere[_("Montant total remboursé au cours de la période")] = afficheMontant($totalmntTotalRemPeriod,false,true);
        $critere[_("Encours de crédits à la fin de période")] = afficheMontant($totalCapitalRestDuNonRadie,false,true);
        $critere[_("Solde Capital passé en perte au cours de la periode")] = afficheMontant($totalCapitalRestDuRadie,false,true);
        //xml pr la génération du pdf
        $xml = xml_list_inventaire_credits($DATA, $critere,$date_deb,$date_fin,$linenum, true,true,$v_etat_tous,$v_etat_radie,false);
        $csv_file = xml_2_csv($xml, 'inventaire_credit_csv.xslt');
        //Message de confirmation + affichage du rapport dans une nouvelle fenêtre
        if (isset($excel) && $excel == 'Export EXCEL'){
          echo getShowEXCELHTML("Gen-13", $csv_file);
        }
        else{
          echo getShowCSVHTML("Gen-13", $csv_file);
        }
    }

    ajout_historique(330, NULL, NULL, $global_nom_login, date("r"), NULL);

}
/*}}}*/

else signalErreur(__FILE__, __LINE__, __FUNCTION__, sprintf(_("L'écran %s n'a pas pu être trouvé"), $global_nom_ecran));

?>
