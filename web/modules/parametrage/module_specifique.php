<?php

/* vim: set expandtab softtabstop=2 shiftwidth=2 foldmethod=marker: */

/**
 * Gestion des tables de paramétrage
 * @package Parametrage
 */

require_once 'lib/dbProcedures/parametrage.php';
require_once 'lib/html/FILL_HTML_GEN2.php';
require_once 'lib/html/HTML_message.php';
require_once 'lib/misc/divers.php';
require_once 'lib/dbProcedures/compte.php';
require_once 'lib/dbProcedures/epargne.php';
require_once 'lib/dbProcedures/agence.php';
require_once 'lib/dbProcedures/abonnement.php';
require_once 'lib/dbProcedures/tarification.php';
require_once 'lib/dbProcedures/transfert.php';
require_once 'lib/dbProcedures/cheque_interne.php';
require_once 'lib/dbProcedures/compta.php';
require_once 'lib/dbProcedures/net_bank.php';
require_once 'lib/html/HTML_erreur.php';
require_once 'lib/misc/Erreur.php';
require_once 'lib/dbProcedures/engrais_chimiques.php';
require_once 'lib/misc/VariablesGlobales.php';

/* Les tables de paramétrage */
/*$tables = array("fen_pns" => _("FENACOBU - PNSEB"),
                "mod_aut" => _("Automatisme module spécifique")
  );*/
$tables = array();

if (isEngraisChimiques() && check_access(253)) {
  $tables["fen_pns"] = _("FENACOBU - PNSEB");
}
if (check_access(254)) {
  $tables["mod_aut"] = _("Automatisme module spécifique");
}

asort($tables);
$SESSION_VARS['tables'] = $tables;



if ($global_nom_ecran == "Gfp-1") {
  unset($SESSION_VARS["select_agence"]);
  unset($SESSION_VARS["table"]);
  resetGlobalIdAgence();
  $MyPage = new HTML_GEN2(_("Gestion des Modules Spécifiques"));
  //Liste des agence
  if (isSiege()) { //Si on est au siège
    $MyPage->addField("list_agence", "Liste des agences", TYPC_LSB);
    $MyPage->setFieldProperties("list_agence", FIELDP_ADD_CHOICES, $liste_agences);
    $MyPage->setFieldProperties("list_agence", FIELDP_HAS_CHOICE_AUCUN, false);
    $MyPage->setFieldProperties("list_agence", FIELDP_HAS_CHOICE_TOUS, false);
    $MyPage->setFieldProperties("list_agence", FIELDP_DEFAULT,getNumAgence());
  }

  $MyPage->addField("table", _("Liste des modules spécifiques"), TYPC_LSB);

  $MyPage->setFieldProperties("table", FIELDP_ADD_CHOICES, $tables);
  $MyPage->setFieldProperties("table", FIELDP_IS_REQUIRED, true);
  $MyPage->setFieldProperties("table", FIELDP_HAS_CHOICE_AUCUN, true);

  $MyPage->addButton("table", "param", _("Paramétrer"), TYPB_SUBMIT);
  $MyPage->setButtonProperties("param", BUTP_JS_EVENT, array("onclick"=>"setProchainEcran();"));

  //Bouton formulaire
  $MyPage->addFormButton(1,1, "butret", _("Retour"), TYPB_SUBMIT);
  $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
  $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gen-12");

  //Javascript
  $js  = "function setProchainEcran(){\n";
  $js .= "if (document.ADForm.HTML_GEN_LSB_table.value == 'fen_pns') {assign('Gfp-2');}\n";
  $js .= "if (document.ADForm.HTML_GEN_LSB_table.value == 'mod_aut') {assign('Gmd-1');}\n";
  $js .= "}\n";
  $MyPage->addJS(JSP_FORM, "js1", $js);

  //HTML
  $MyPage->buildHTML();
  echo $MyPage->getHTML();

}

else if ($global_nom_ecran == "Gfp-2") {
  if (!isset($SESSION_VARS['table']) || $SESSION_VARS['table'] == '') {
    $SESSION_VARS['table'] = $table;
  }

  $MyPage = new HTML_GEN2(_("Liste des tables du module :")." '".$SESSION_VARS['tables'][$SESSION_VARS['table']]."'");
  if($SESSION_VARS['table'] == 'fen_pns') {
    $array_menu_engrais = array(
      'ec_annee_agricole'=>_("Année Agricole"),
      'ec_saison_culturale'=>_("Saison"),
      'ec_produit'=>_("Produits"),
      'ec_localisation'=>_("Localisations")
    );
    $MyPage->addField("contenu", _("Liste Des Tables"), TYPC_LSB);
    $MyPage->setFieldProperties("contenu", FIELDP_HAS_CHOICE_AUCUN,true);
    $MyPage->setFieldProperties("contenu", FIELDP_ADD_CHOICES, $array_menu_engrais);
    $MyPage->setFieldProperties("contenu", FIELDP_IS_REQUIRED, true);
  }

  //Bouton formulaire
  $MyPage->addButton("contenu", "butparam", _("Parametrer"), TYPB_SUBMIT);
  $MyPage->setButtonProperties("butparam", BUTP_PROCHAIN_ECRAN, "Gfp-3");
  $MyPage->setButtonProperties("butparam", BUTP_AXS, 252);


  $MyPage->addFormButton(1,1, "butret", _("Retour"), TYPB_SUBMIT);
  $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
  $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-1");

  $MyPage->buildHTML();
  echo $MyPage->getHTML();
}

else if ($global_nom_ecran == "Gfp-3") {
  $ary1 = array('ec_annee_agricole','ec_saison_culturale','ec_produit','ec_localisation');
  $ary2 = array('ec_annee_agricole'=>_("Année Agricole"),
    'ec_saison_culturale'=>_("Saison"),
    'ec_produit'=>_("Produits"),
    'ec_localisation'=>_("Localisations"));
  //Si table générique
  if ((isset($contenu) && in_array($contenu, $ary1)) || in_array($SESSION_VARS['ajout_table'], $ary1) || in_array($SESSION_VARS['consult_table'], $ary1) || in_array($SESSION_VARS['modif_table'], $ary1)) {
    if (isset($contenu) && $contenu != ''){
      $SESSION_VARS['ajout_table'] = $contenu;
      $SESSION_VARS['consult_table'] = $contenu;
      $SESSION_VARS['modif_table'] = $contenu;
    }

    $MyPage = new HTML_GEN2(_("Gestion de la table de paramétrage")." '".$ary2[$SESSION_VARS['ajout_table']]."'");

    if($contenu == 'ec_annee_agricole' || $SESSION_VARS['ajout_table'] == 'ec_annee_agricole') {
      $MyPage->addField("contenu", _("Contenu"), TYPC_LSB);
      $MyPage->setFieldProperties("contenu", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties("contenu", FIELDP_IS_REQUIRED, true);
      $liste_AnneeAgricolePNSEB=getListeAnneeAgricolePNSEB();
      //Trier par ordre alphabétique
      natcasesort($liste_AnneeAgricolePNSEB);
      if (sizeof($liste_AnneeAgricolePNSEB)>0)
        $MyPage->setFieldProperties("contenu", FIELDP_ADD_CHOICES, $liste_AnneeAgricolePNSEB);
    }

    if($contenu == 'ec_saison_culturale' || $SESSION_VARS['ajout_table'] == 'ec_saison_culturale') {
      $MyPage->addField("contenu", _("Contenu"), TYPC_LSB);
      $MyPage->setFieldProperties("contenu", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties("contenu", FIELDP_IS_REQUIRED, true);
      $liste_SaisonPNSEB=getListeSaisonPNSEB();
      //Trier par ordre alphabétique
      natcasesort($liste_SaisonPNSEB);
      if (sizeof($liste_SaisonPNSEB)>0)
        $MyPage->setFieldProperties("contenu", FIELDP_ADD_CHOICES, $liste_SaisonPNSEB);
    }

    if($contenu == 'ec_produit' || $SESSION_VARS['ajout_table'] == 'ec_produit') {
      $MyPage->addField("contenu", _("Contenu"), TYPC_LSB);
      $MyPage->setFieldProperties("contenu", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties("contenu", FIELDP_IS_REQUIRED, true);
      $liste_produitPNSEB=getListeProduitPNSEB();
      //Trier par ordre alphabétique
      natcasesort($liste_produitPNSEB);
      if (sizeof($liste_produitPNSEB)>0)
        $MyPage->setFieldProperties("contenu", FIELDP_ADD_CHOICES, $liste_produitPNSEB);
    }

    if($contenu == 'ec_localisation' || $SESSION_VARS['ajout_table'] == 'ec_localisation') {
      $MyPage->addField("contenu", _("Contenu"), TYPC_LSB);
      $MyPage->setFieldProperties("contenu", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties("contenu", FIELDP_IS_REQUIRED, true);
      $liste_localisationPNSEB=getListelocalisationPNSEB();
      //Trier par ordre alphabétique
      natcasesort($liste_localisationPNSEB);
      if (sizeof($liste_localisationPNSEB)>0)
        $MyPage->setFieldProperties("contenu", FIELDP_ADD_CHOICES, $liste_localisationPNSEB);
    }

    $MyPage->addButton("contenu", "butcons", _("Consulter"), TYPB_SUBMIT);
    $MyPage->setButtonProperties("butcons", BUTP_PROCHAIN_ECRAN, "Gcf-1");
    //$MyPage->setButtonProperties("butcons", BUTP_AXS, 252);
    $MyPage->addButton("contenu", "butmodif", _("Modifier"), TYPB_SUBMIT);
    $MyPage->setButtonProperties("butmodif", BUTP_PROCHAIN_ECRAN, "Gmf-1");

    //Bouton formulaire
    $MyPage->addFormButton(1,1, "butajou", _("Ajouter"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butajou", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butajou", BUTP_PROCHAIN_ECRAN, "Gaf-1");
    //$MyPage->setFormButtonProperties("butajou", BUTP_AXS, 252);

    $MyPage->addFormButton(1,2, "butret", _("Retour"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-2");

    $MyPage->buildHTML();
    echo $MyPage->getHTML();
  } else signalErreur(__FILE__,__LINE__,__FUNCTION__);
}

else if ($global_nom_ecran == "Gaf-1") {
  global $global_id_agence, $global_monnaie;
  $ary_exclude = array();

  $ary1 = array("ec_annee_agricole", "ec_saison_culturale", "ec_produit", "ec_localisation");
  $ary2 = array('ec_annee_agricole'=>_("Année Agricole"),
    'ec_saison_culturale'=>_("Saison"),
    'ec_produit'=>_("Produits"),
    'ec_localisation'=>_("Localisations"));
  //Si table générique
  if (in_array($SESSION_VARS['ajout_table'], $ary1)) {
    //Ajout
    $MyPage = new HTML_GEN2(_("Ajout d'une entrée "));
    $checkDateSaison = '';

    //Nom table
    $MyPage->addField("ntable", _("Table de paramétrage"), TYPC_TXT);
    $MyPage->setFieldProperties("ntable", FIELDP_IS_LABEL, true);
    $MyPage->setFieldProperties("ntable", FIELDP_DEFAULT, $ary2[$SESSION_VARS['ajout_table']]);

    // Récupération des infos sur l'entrée de la table
    $info = get_tablefield_info($SESSION_VARS['ajout_table'], NULL);
    if (isset($SESSION_VARS['info'])){
      unset($SESSION_VARS['info']);
    }
    $SESSION_VARS['info'] = $info;

    while (list($key, $value) = each($info)) { //Pour chaque champs de la table
      if (($key != "pkey") && //On n'insère pas les clés primaires
        (!in_array($key, $ary_exclude))
      ) { //On n'insère pas certains champs en fonction du contexte
        if (!$value['ref_field']) { //Si champs ordinaire
          $type = $value['type'];
          if ($value['traduit'])
            $type = TYPC_TTR;
          $fill = 0;
          if ((substr($type, 0, 2) == "in") && ($type != "int")) { //Si int avec fill zero
            $fill = substr($type, 2, 1);
            $type = "int";
          }

          $MyPage->addField($key, $value['nom_long'], $type);
          if ($fill != 0) $MyPage->setFieldProperties($key, FIELDP_FILL_ZERO, $fill);
        } else { //Si champs qui référence
          $MyPage->addField($key, $value['nom_long'], TYPC_LSB);
        }
        $MyPage->setFieldProperties($key, FIELDP_IS_REQUIRED, $value['requis']);
      }
    }

    if ($SESSION_VARS['ajout_table'] == 'ec_annee_agricole'){
      $MyPage->setFieldProperties("etat",FIELDP_TYPE,TYPC_LSB);
      //$MyPage->setFieldProperties("etat",FIELDP_ADD_CHOICES,($adsys["adsys_etat_annee_agricole"]));
      $annee_agri_en_cours = getAnneeAgricoleActif();
      //$date_fin_annee_encours = pg2phpDatebis($annee_agri_en_cours['date_fin']);
      $etat_annee_encours = $annee_agri_en_cours['etat'];
      if($etat_annee_encours == NULL){
        $etat_annee_encours= "''";
      }
      $etatAnneeArr["adsys_etat_annee_agricole"];
      if ($etat_annee_encours != '' && $etat_annee_encours == 1){
        $etatAnneeArr["adsys_etat_annee_agricole"][2]=_("Fermé");
        $MyPage->setFieldProperties("etat",FIELDP_ADD_CHOICES,($etatAnneeArr["adsys_etat_annee_agricole"]));
      }
      else{
        $etatAnneeArr["adsys_etat_annee_agricole"][1]=_("Ouvert");
        $MyPage->setFieldProperties("etat",FIELDP_ADD_CHOICES,($etatAnneeArr["adsys_etat_annee_agricole"]));
      }
      $MyPage->setFieldProperties("etat",FIELDP_HAS_CHOICE_AUCUN,false);
      // Validation sur les ajout d'annees agricoles
      //$checkDateSaison = "if(! isBefore('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_annee_encours[0], $date_fin_annee_encours[1], $date_fin_annee_encours[2])) . "', document.ADForm.HTML_GEN_date_date_debut.value)) { alert('- " . _("La date précisée dans le champ \'Date début\' doit être postérieure à la date de fin de l année agricole précédent (".$date_fin_annee_encours[1]."/".$date_fin_annee_encours[0]."/".$date_fin_annee_encours[2].")")."'); document.ADForm.HTML_GEN_date_date_debut.focus(); return false; }";
      $checkDateSaison = "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin.value)) { alert('- " . _("La date précisée dans le champ \'Date fin\' doit être postérieure à la date de debut")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
      $checkDateSaison .="
  if( ".$etat_annee_encours." != '' && ".$etat_annee_encours." !=2 && document.ADForm.HTML_GEN_LSB_etat.value == 1 ){ alert('- " . _("il y a deja une année ouverte")."');document.ADForm.HTML_GEN_LSB_etat.focus(); return false;}";
    }

    if ($SESSION_VARS['ajout_table'] == 'ec_annee_agricole'){
      $ordre1 = array("ntable","libel","date_debut","date_fin", "etat");
      $MyPage->setOrder(NULL, $ordre1);

    }

    if ($SESSION_VARS['ajout_table'] == 'ec_saison_culturale'){
      $annee_agri_en_cours = getListeAnneeAgricoleActif(' etat = 1 ');
      $MyPage->setFieldProperties("etat_saison",FIELDP_TYPE,TYPC_LSB);
      $MyPage->setFieldProperties("etat_saison",FIELDP_ADD_CHOICES,($adsys["adsys_etat_saison"]));
      $MyPage->setFieldProperties("id_annee",FIELDP_TYPE,TYPC_LSB);
      $MyPage->setFieldProperties("id_annee",FIELDP_ADD_CHOICES,$annee_agri_en_cours);
      $MyPage->setFieldProperties("id_annee", FIELDP_HAS_CHOICE_AUCUN, false);
      //$MyPage->setFieldProperties("id_annee", FIELDP_IS_LABEL, false);
      $MyPage->addHTMLExtraCode("general", "<b>"._("General")."</b>");
      $MyPage->setHTMLExtraCodeProperties("general", HTMP_IN_TABLE, true);
      $MyPage->addHTMLExtraCode("separation_period_avance", "<b>"._("Periode de paiement des avances")."</b>");
      $MyPage->setHTMLExtraCodeProperties("separation_period_avance", HTMP_IN_TABLE, true);
      $MyPage->addHTMLExtraCode("separation_period_solde", "<b>"._("Periode de paiement des soldes")."</b>");
      $MyPage->setHTMLExtraCodeProperties("separation_period_solde", HTMP_IN_TABLE, true);
      $MyPage->addHTMLExtraCode("separation_period_fin", "<b>"._("Periode de fin de saison")."</b>");
      $MyPage->setHTMLExtraCodeProperties("separation_period_fin", HTMP_IN_TABLE, true);

      $ordre = array("general","ntable", "id_annee","nom_saison", "plafond_engrais", "plafond_amendement", "etat_saison","separation_period_avance", "date_debut", "date_fin_avance","separation_period_solde", "date_debut_solde", "date_fin_solde", "separation_period_fin","date_fin");
      $MyPage->setOrder(NULL, $ordre);

      //Controle Javascript sur les dates
      $check_saison_exist = getListeSaisonPNSEBlatest($param);

      $date_fin_saison_exist_arr = pg2phpDatebis($check_saison_exist['date_fin']);
      $etat_saison_exist = $check_saison_exist['etat_saison'];
      if($etat_saison_exist == NULL){
        $etat_saison_exist= "''";
      }

      // date debut-fin de l'annee agricole
      $data_annee = getDateAnneeAgricoleActif();
      $date_debut_annee_arr =pg2phpDatebis($data_annee['date_debut']);
      $date_fin_annee_arr = pg2phpDatebis($data_annee['date_fin']);

      // check si la date de debut est superieur a la date debut annee agricole
      $checkDateSaison .= "if(! isBeforeOrEqualTo('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_debut_annee_arr[0], $date_debut_annee_arr[1], $date_debut_annee_arr[2])) . "', document.ADForm.HTML_GEN_date_date_debut.value)) { alert('- " . _("La date précisée dans le champ \'Date début de la saison\' doit être postérieure à la date de debut de l année agricole (".$date_debut_annee_arr[1]."/".$date_debut_annee_arr[0]."/".$date_debut_annee_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_debut.focus(); return false; }";
      // check si la date est superieur a la date de fin de la derniere saison culturale
      $checkDateSaison .= "if(! isBefore('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_saison_exist_arr[0], $date_fin_saison_exist_arr[1], $date_fin_saison_exist_arr[2])) . "', document.ADForm.HTML_GEN_date_date_debut.value)) { alert('- " . _("La date précisée dans le champ \'Date début de la saison\' doit être postérieure à la date de fin de la dernière saison (".$date_fin_saison_exist_arr[1]."/".$date_fin_saison_exist_arr[0]."/".$date_fin_saison_exist_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_debut.focus(); return false; }";
      // check si la date fin des avances est superieur a la date debut saison culturale
      $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin_avance.value) && document.ADForm.HTML_GEN_date_date_debut.value != ''  && document.ADForm.HTML_GEN_date_date_fin_avance.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin des avances\' doit être postérieure à la Date début de la saison ")."'); document.ADForm.HTML_GEN_date_date_fin_avance.focus(); return false; }";
      // check si la date début des soldes est superieur a la date fin des avances
      $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_fin_avance.value, document.ADForm.HTML_GEN_date_date_debut_solde.value) && document.ADForm.HTML_GEN_date_date_fin_avance.value != ''  && document.ADForm.HTML_GEN_date_date_debut_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date début des soldes\' doit être postérieure à la Date fin des avances")."'); document.ADForm.HTML_GEN_date_date_debut_solde.focus(); return false; }";
      // check si la date Date fin des soldes est superieur a la Date début des soldes
      $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut_solde.value, document.ADForm.HTML_GEN_date_date_fin_solde.value) && document.ADForm.HTML_GEN_date_date_debut_solde.value != ''  && document.ADForm.HTML_GEN_date_date_fin_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin des soldes\' doit être postérieure à la Date début des soldes")."'); document.ADForm.HTML_GEN_date_date_fin_solde.focus(); return false; }";
      // check si la date Date fin de la saison est superieur a la Date fin des soldes
      $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_fin_solde.value, document.ADForm.HTML_GEN_date_date_fin.value) && document.ADForm.HTML_GEN_date_date_fin_solde.value != '' && document.ADForm.HTML_GEN_date_date_fin.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin de la saison\' doit être postérieure à la Date fin des soldes")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
      // check si la date de fin solde est anterieur a la date de fin de l'annee agricole
      $checkDateSaison .= "if(! isBeforeOrEqualTo(document.ADForm.HTML_GEN_date_date_fin_solde.value, '" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_annee_arr[0], $date_fin_annee_arr[1], $date_fin_annee_arr[2])) . "') && document.ADForm.HTML_GEN_date_date_fin_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin solde de la saison\' doit être antérieur à la date de fin de l année agricole(".$date_fin_annee_arr[1]."/".$date_fin_annee_arr[0]."/".$date_fin_annee_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_fin_solde.focus(); return false; }";
      // check si la date de fin est anterieur a la date de fin de l'annee agricole
      $checkDateSaison .= "if( isBefore('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_annee_arr[0], $date_fin_annee_arr[1], $date_fin_annee_arr[2])) . "', document.ADForm.HTML_GEN_date_date_fin.value) && document.ADForm.HTML_GEN_date_date_fin.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin de la saison\' doit être antérieur à la date de fin de l année agricole(".$date_fin_annee_arr[1]."/".$date_fin_annee_arr[0]."/".$date_fin_annee_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
      // check si un etat en cours existe deja

      $checkDateSaison .="
  if( ".$etat_saison_exist." != '' && ".$etat_saison_exist." !=2 && document.ADForm.HTML_GEN_LSB_etat_saison.value == 1 ){ alert('- " . _("il y a deja une saison ouverte")."');document.ADForm.HTML_GEN_LSB_etat_saison.focus(); return false;}";
    }

    if ($SESSION_VARS['ajout_table'] == 'ec_produit'){
      $MyPage->setFieldProperties('type_produit', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties("type_produit", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties('type_produit', FIELDP_ADD_CHOICES, $adsys["adsys_type_produit"]);
      $MyPage->setFieldProperties("prix_unitaire", FIELDP_TYPE, TYPC_MNT);
      $setPrixUnitaireModifiable = setPrixUnitaireModifiable();
      if ($setPrixUnitaireModifiable == FALSE) {
        $MyPage->setFieldProperties("prix_unitaire", FIELDP_DEFAULT, 0);
        $MyPage->setFieldProperties("prix_unitaire", FIELDP_IS_LABEL, true);
      }
      $MyPage->setFieldProperties('etat_produit', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties("etat_produit", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties('etat_produit', FIELDP_ADD_CHOICES, $adsys["adsys_etat_produit"]);
      $MyPage->setFieldProperties("montant_minimum", FIELDP_TYPE, TYPC_MNT);
      $MyPage->setFieldProperties("montant_minimum", FIELDP_IS_REQUIRED, true);
      $MyPage->setFieldProperties('compte_produit', FIELDP_ADD_CHOICES, $SESSION_VARS['info']['compte_produit']['choices']);
      $MyPage->setFieldProperties('compte_produit', FIELDP_IS_REQUIRED, true);
    }

    if ($SESSION_VARS['ajout_table'] == 'ec_localisation'){
      $codejs = " function populateParent()
      {
        if (document.ADForm.HTML_GEN_LSB_type_localisation.value > 1) {
            var _cQueue = [];
            var valueToPush = {};
            if (document.ADForm.HTML_GEN_LSB_type_localisation.value == 2){
              ";
      $where = "type_localisation = 1";
      $valueToPush = getListelocalisationPNSEB($where);
      while (list($key, $value) = each($valueToPush)) {
        $codejs.=" valueToPush['".$key."'] = '".$value."';";
      }
      $codejs.="} if (document.ADForm.HTML_GEN_LSB_type_localisation.value == 3){
              ";
      $where = "type_localisation = 2";
      $valueToPush = getListelocalisationPNSEB($where);
      while (list($key, $value) = each($valueToPush)) {
        $codejs.=" valueToPush['".$key."'] = '".$value."';";
      }
      $codejs.="} if (document.ADForm.HTML_GEN_LSB_type_localisation.value == 4){
              ";
      $where = "type_localisation = 3";
      $valueToPush = getListelocalisationPNSEB($where);
      while (list($key, $value) = each($valueToPush)) {
        $codejs.=" valueToPush['".$key."'] = '".$value."';";
      }
      $codejs.="}";
      $codejs.="
            _cQueue.push(valueToPush);

            var slt = document.ADForm.HTML_GEN_LSB_parent;
            // Reset select
            slt.options.length = 0;
            // Set default value
            slt.options[0] = new Option(\"[Aucun]\", \"0\", true, true);
            for (var i=0; i<_cQueue.length; i++) { // iterate on the array
              var obj = _cQueue[i];
              for (var key in obj) { // iterate on object properties
                var value = obj[key];
                //console.log(value);
                 opt = document.createElement('option');
                 opt.value = key;
                 opt.text = value;
                 slt.appendChild(opt);
              }
            }
        } else {
            var slt = document.ADForm.HTML_GEN_LSB_parent;
            // Reset select
            slt.options.length = 0;
            // Set default value
            slt.options[0] = new Option(\"[Aucun]\", \"0\", true, true);
        }
      }";
      $MyPage->addJS(JSP_FORM, "JS1", $codejs);
      $MyPage->setFieldProperties("libel", FIELDP_IS_REQUIRED, true);
      $MyPage->setFieldProperties('type_localisation', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties("type_localisation", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties('type_localisation', FIELDP_ADD_CHOICES, $adsys["adsys_type_localisation"]);
      $MyPage->setFieldProperties("type_localisation", FIELDP_JS_EVENT, array("onChange" => "populateParent();"));
      $MyPage->setFieldProperties("type_localisation", FIELDP_IS_REQUIRED, true);
      $MyPage->setFieldProperties('parent', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties("parent", FIELDP_HAS_CHOICE_AUCUN,true);
    }

    //Bouton
    $MyPage->addFormButton(1, 1, "butval", _("Valider"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butval", BUTP_PROCHAIN_ECRAN, "Gaf-2");
    $MyPage->setFormButtonProperties("butval", BUTP_JS_EVENT, array("onclick" => $checkDateSaison));
    $MyPage->addFormButton(1, 2, "butret", _("Annuler"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-3");

    $MyPage->buildHTML();
    echo $MyPage->getHTML();
  } else signalErreur(__FILE__,__LINE__,__FUNCTION__);
}

else if ($global_nom_ecran == "Gaf-2") {
  global $global_id_agence, $global_monnaie;
  $ary_exclude = array();

  if (isset($butval) && $butval == "Valider"){

    $MyPage = new HTML_GEN2(_("Ajout d'une entrée "));

    reset($SESSION_VARS['info']);
    while (list($key, $value) = each($SESSION_VARS['info'])) {
      if (($key != "pkey") && (! in_array($key, $ary_exclude))) { //On n'insére pas certains champs en fonction du contexte
        if ($value['type'] == TYPC_MNT) $DATA[$key] = recupMontant(${$key});
        else if ($value['type'] == TYPC_BOL) {
          if (isset(${$key}))
            $DATA[$key] = "t";
          else $DATA[$key] = "f";
        } else if ($value['type'] == TYPC_PRC)
          $DATA[$key] = "".((${$key}) / 100)."";
        //else if (($value['type'] == TYPC_TXT) && (${$key} == "0") && ($value['ref_field'] == 1400)) // il faut accepter les valeurs 0
        //$DATA[$key] = "NULL";//FIXME:je sais,ce n'est vraiment pas propre.Probléme d'intégrité référentielle sur les comptes comptables
        else if (($value['type'] == TYPC_TXT) && ($value['ref_field'] == 1400)) {
          // On considère que la valeur 0 pour les list box est le choix [Aucun]
          if ($ {"HTML_GEN_LSB_".$key}=="0")
            $DATA[$key] = "NULL";
          else
            $DATA[$key]= $ {"HTML_GEN_LSB_".$key
            };

        } else $DATA[$key] = ${
        $key
        };


        if ((($value['type'] == TYPC_MNT) || ($value['type'] == TYPC_INT) || ($value['type'] == TYPC_PRC)) && ($ {$key} == NULL || ${$key} == "")) {
          $DATA[$key] = '0'; //NULL correspond à la valeur zéro pour les chiffres.  Ah bon ?  Ca limite l'usage des valeurs par défaut de PSQL... dommage. :(
        }
        if ($key == "id_etat_prec") {
          $DATA[$key]  = array_pop(array_keys($value['choices']));
        }
      }
    }

    //appel DB
    $myErr=ajout_table($SESSION_VARS['ajout_table'], $DATA);

    //HTML
    if ($myErr->errCode==NO_ERR) {
      $MyPage = new HTML_message(_("Confirmation ajout"));
      $message = sprintf(_("L'entrée été ajoutée avec succès"));
      $MyPage->setMessage($message);
      $MyPage->addButton(BUTTON_OK, "Gfp-2");
      $MyPage->buildHTML();
      echo $MyPage->HTML_code;
    }
    else{
      $MyPage = new HTML_erreur(_("Echec de l'insertion"));
      $MyPage->setMessage($error[$myErr->errCode]);
      $MyPage->addButton(BUTTON_OK, "Gaf-1");
      $MyPage->buildHTML();
      echo $MyPage->HTML_code;
    }
  }
}

else if ($global_nom_ecran == "Gcf-1") {
  $ary_exclude = array();

  $ary1 = array("ec_annee_agricole", "ec_saison_culturale", "ec_produit", "ec_localisation");
  $ary2 = array('ec_annee_agricole'=>_("Année Agricole"),
    'ec_saison_culturale'=>_("Saison"),
    'ec_produit'=>_("Produits"),
    'ec_localisation'=>_("Localisations"));
  //Si table générique
  if (in_array($SESSION_VARS['consult_table'], $ary1)) {

    ajout_historique(293, NULL, $SESSION_VARS['consult_table'], $global_nom_login, date("r"), NULL);

    //Consultation
    $MyPage = new HTML_GEN2(_("Consultation d'une entrée "));

    //Nom table
    $MyPage->addField("ntable", _("Table de paramétrage"), TYPC_TXT);
    $MyPage->setFieldProperties("ntable", FIELDP_IS_LABEL, true);
    $MyPage->setFieldProperties("ntable", FIELDP_DEFAULT, $ary2[$SESSION_VARS['consult_table']]);

    // Récupération des infos sur l'entrée de la table
    $info = get_tablefield_info($SESSION_VARS['consult_table'], $contenu);

    foreach($info AS $key => $value) { //Pour chaque champs de la table
      if (($key != "pkey") && //On n'insére pas les clés primaires
        (!in_array($key, $ary_exclude))
      ) { //On n'insére pas certains champs en fonction du contexte

        if (!$value['ref_field']) { //Si champs ordinaire
          $type = $value['type'];
          if ($value['traduit'])
            $type = TYPC_TTR;
          if ($type == TYPC_PRC) $value['val'] *= 100;
          if ($type == TYPC_BOL) $value['val'] = ($value['val'] == 't');
          $fill = 0;
          if ((substr($type, 0, 2) == "in") && ($type != "int")) { //Si int avec fill zero
            $fill = substr($type, 2, 1);
            $type = "int";
          }

          $MyPage->addField($key, $value['nom_long'], $type);
          if ($fill != 0) $MyPage->setFieldProperties($key, FIELDP_FILL_ZERO, $fill);
        } else { //Si champs qui référence
          $MyPage->addField($key, $value['nom_long'], TYPC_LSB);
          $MyPage->setFieldProperties($key, FIELDP_ADD_CHOICES, $value['choices']);
        }
        $MyPage->setFieldProperties($key, FIELDP_IS_REQUIRED, $value['requis']);
        $MyPage->setFieldProperties($key, FIELDP_IS_LABEL, true);
        $MyPage->setFieldProperties($key, FIELDP_DEFAULT, $value['val']);
        if ($key == "etat") {
          $etat_annee_value = $value['val'];
        }
        if ($key == "etat_saison") {
          $etat_saison_value = $value['val'];
        }
        if ($key == "id_annee") {
          $id_annee_agri = $value['val'];
        }

        if ($SESSION_VARS['consult_table'] == 'ec_produit' ){
          if ($key=='type_produit' || $key=='etat_produit'){
            $MyPage->setFieldProperties($key, FIELDP_DEFAULT, $adsys["adsys_".$key][$value['val']]);
          }
        }

        if ($SESSION_VARS['consult_table'] == 'ec_localisation' ){
          if ($key=='type_localisation'){
            $MyPage->setFieldProperties($key, FIELDP_DEFAULT, $adsys["adsys_".$key][$value['val']]);
          }
          if ($key=='parent'){
            $where = "id = ".$value['val'];
            $valueParent = getListelocalisationPNSEB($where);
            $MyPage->setFieldProperties($key, FIELDP_DEFAULT, $valueParent[$value['val']]);
          }
        }
      }
    }

    if ($SESSION_VARS['consult_table'] == "ec_annee_agricole"){
      $MyPage->setFieldProperties("etat",FIELDP_DEFAULT, $adsys["adsys_etat_annee_agricole"][$etat_annee_value]);
    }
    if ($SESSION_VARS['consult_table'] == "ec_saison_culturale"){
      $get_annee_agri_actif_label =getAnneeAgricoleActif($id_annee_agri);

      $MyPage->setFieldProperties("id_annee",FIELDP_DEFAULT, $get_annee_agri_actif_label['libel']);

      $MyPage->setFieldProperties("etat_saison",FIELDP_DEFAULT, $adsys["adsys_etat_saison"][$etat_saison_value]);
      $ordre = array("ntable", "id_annee","nom_saison", "date_debut", "date_fin_avance", "date_debut_solde", "date_fin_solde", "date_fin", "plafond_engrais", "plafond_amendement", "etat_saison");
      $MyPage->setOrder(NULL, $ordre);
    }

    $MyPage->addFormButton(1,1, "butret", _("Retour"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-3");

    $MyPage->buildHTML();
    echo $MyPage->getHTML();
  } else signalErreur(__FILE__,__LINE__,__FUNCTION__);
}

else if ($global_nom_ecran == "Gmf-1") {
  global $global_id_agence, $global_monnaie;
  $ary_exclude = array("id_annee");
  $isSuivante = false;
  $date = date("Y/m/d");

  $ary1 = array("ec_annee_agricole", "ec_saison_culturale", "ec_produit", "ec_localisation");
  $ary2 = array('ec_annee_agricole'=>_("Année Agricole"),
    'ec_saison_culturale'=>_("Saison"),
    'ec_produit'=>_("Produits"),
    'ec_localisation'=>_("Localisations"));
  //Si table générique
  if (in_array($SESSION_VARS['modif_table'], $ary1)) {
    //Modification
    $MyPage = new HTML_GEN2(_("Modification d'une entrée "));

    //Nom table
    $MyPage->addField("ntable", _("Table de paramétrage"), TYPC_TXT);
    $MyPage->setFieldProperties("ntable", FIELDP_IS_LABEL, true);
    $MyPage->setFieldProperties("ntable", FIELDP_DEFAULT, $ary2[$SESSION_VARS['modif_table']]);

    // Récupération des infos sur l'entrée de la table
    $info = get_tablefield_info($SESSION_VARS['modif_table'], $contenu);
    $SESSION_VARS['info'] = $info;
    $SESSION_VARS['info']['modif_pkeyid'] = $contenu;
    $logo=0;

    foreach ($info as $key => $value) {
      //Pour chaque champs de la table
      if (($key != "pkey") && (! in_array($key, $ary_exclude))) //On n'insère pas les clés primaires
      {	//On n'insère pas certains champs en fonction du contexte
        if (! $value['ref_field']) {	//Si champs ordinaire
          $type = $value['type'];
          if ($value['traduit'])
            $type = TYPC_TTR;
          if ($type == TYPC_PRC) $value['val'] *= 100;
          if ($type == TYPC_BOL) $value['val'] = ($value['val'] == 't');

          $fill = 0;
          if ((substr($type, 0, 2) == "in") && ($type != "int")) {	//Si int avec fill zero
            $fill = substr($type, 2, 1);
            $type = "int";
          }

          $MyPage->addField($key, $value['nom_long'], $type);

          if ($fill != 0)
            $MyPage->setFieldProperties($key, FIELDP_FILL_ZERO, $fill);
        } else {	//Si Ref field
          $MyPage->addField($key, $value['nom_long'], TYPC_LSB);

          $MyPage->setFieldProperties($key, FIELDP_ADD_CHOICES, $value['choices']);
        }

        $MyPage->setFieldProperties($key, FIELDP_IS_REQUIRED, $value['requis']);
        $MyPage->setFieldProperties($key, FIELDP_DEFAULT, $value['val']);
      }

      if ($SESSION_VARS['modif_table'] == 'ec_localisation'){
        if ($key=='parent'){
          $where = "id = ".$value['val'];
          $valueParent = getListelocalisationPNSEB($where);
          $MyPage->setFieldProperties($key, FIELDP_DEFAULT, $value['val']);
        }
      }

      if ($SESSION_VARS['modif_table'] == "ec_annee_agricole" && ($key == 'etat')) {
        $MyPage->setFieldProperties($key,FIELDP_TYPE,TYPC_LSB);
        $MyPage->setFieldProperties($key, FIELDP_ADD_CHOICES, $adsys["adsys_etat_annee_agricole"]);
        $etat_annee = $value['val'];
      }

      if ($SESSION_VARS['modif_table'] == "ec_saison_culturale" && ($key == 'etat_saison')) {
        $MyPage->setFieldProperties($key,FIELDP_TYPE,TYPC_LSB);
        $MyPage->setFieldProperties($key, FIELDP_ADD_CHOICES, $adsys["adsys_etat_saison"]);
      }

      if (($SESSION_VARS['modif_table'] == "ec_saison_culturale"  || $SESSION_VARS['modif_table'] == "ec_annee_agricole") && ($key == 'date_debut')) {
        $date = date("Y/m/d");
        $date_debut_base = pg2phpDatebis($value['val']);
        $date_debut_base_bis = date("Y/m/d",mktime(0, 0, 0, (int)$date_debut_base[0], $date_debut_base[1], $date_debut_base[2]));
        if ($date > $date_debut_base_bis && $value['val'] != ''){
          $MyPage->setFieldProperties($key, FIELDP_HAS_CALEND, false);
        }
      }
      if ($SESSION_VARS['modif_table'] == "ec_saison_culturale" && ($key == 'date_fin_avance')) {
        $date = date("Y/m/d");
        $date_fin_avance_base = pg2phpDatebis($value['val']);
        $date_fin_avance_base_bis = date("Y/m/d",mktime(0, 0, 0, (int)$date_fin_avance_base[0], $date_fin_avance_base[1], $date_fin_avance_base[2]));
        if ($date > $date_fin_avance_base_bis && $value['val'] != ''){
          $MyPage->setFieldProperties($key, FIELDP_HAS_CALEND, false);
        }
      }
      if ($SESSION_VARS['modif_table'] == "ec_saison_culturale" && ($key == 'date_debut_solde')) {
        $date = date("Y/m/d");
        $date_debut_solde_base = pg2phpDatebis($value['val']);
        $date_debut_solde_base_bis = date("Y/m/d",mktime(0, 0, 0, (int)$date_debut_solde_base[0], $date_debut_solde_base[1], $date_debut_solde_base[2]));
        if ($date > $date_debut_solde_base_bis && $value['val'] != ''){
          $MyPage->setFieldProperties($key, FIELDP_HAS_CALEND, false);
        }
      }
      if ($SESSION_VARS['modif_table'] == "ec_saison_culturale" && ($key == 'date_fin_solde')) {
        $date = date("Y/m/d");
        $date_fin_solde_base = pg2phpDatebis($value['val']);
        $date_fin_solde_base_bis = date("Y/m/d",mktime(0, 0, 0, (int)$date_fin_solde_base[0], $date_fin_solde_base[1], $date_fin_solde_base[2]));
        if ($date > $date_fin_solde_base_bis && $value['val'] != ''){
          $MyPage->setFieldProperties($key, FIELDP_HAS_CALEND, false);
        }
      }
      if (($SESSION_VARS['modif_table'] == "ec_saison_culturale" || $SESSION_VARS['modif_table'] == "ec_annee_agricole") && ($key == 'date_fin')) {
        $date = date("Y/m/d");
        $date_fin_base = pg2phpDatebis($value['val']);
        $date_fin_base_bis = date("Y/m/d",mktime(0, 0, 0, (int)$date_fin_base[0], $date_fin_base[1], $date_fin_base[2]));
        if ($date > $date_fin_base_bis && $value['val'] != ''){
          $MyPage->setFieldProperties($key, FIELDP_HAS_CALEND, false);
        }
      }
    }

    if($SESSION_VARS['modif_table'] == "ec_annee_agricole"){
      $saison_ouverte =getListeSaisonPNSEB("id_annee=".$contenu."and etat_saison = 1");

      $date = date("d/m/Y");
      $checkDateAvailable = "";
      $checkDateAvailable .= "if(! isBefore('" . $date . "', document.ADForm.HTML_GEN_date_date_debut.value)) { document.ADForm.HTML_GEN_date_date_debut.readOnly = true; }";
      $checkDateAvailable .= "if(! isBefore('" . $date . "', document.ADForm.HTML_GEN_date_date_fin.value)) { document.ADForm.HTML_GEN_date_date_fin.readOnly = true; }";
      $checkDateAvailable .= "if(document.ADForm.HTML_GEN_LSB_etat.value == 2) { document.ADForm.HTML_GEN_LSB_etat.disabled = true; document.ADForm.HTML_GEN_date_date_debut.readOnly = true; document.ADForm.HTML_GEN_date_date_fin.readOnly = true; document.ADForm.butval.hidden = true; document.ADForm.libel.readOnly = true; }";

      if ($etat_annee == 2){
        $MyPage->setFieldProperties('date_debut', FIELDP_HAS_CALEND, false);
        $MyPage->setFieldProperties('date_fin', FIELDP_HAS_CALEND, false);
      }

      $MyPage->addJS(JSP_FORM, "funct_check_date_annee", $checkDateAvailable);

      $annee_agri_en_cours =  getAnneeAgricole("id_annee !=".$contenu);
      //$date_fin_annee_encours = pg2phpDatebis($annee_agri_en_cours['date_fin']);
      $etat_annee_encours = $annee_agri_en_cours['etat'];
      if($etat_annee_encours == NULL){
        $etat_annee_encours= "''";
      }
      // Validation sur les ajout d'annees agricoles
      //$checkDateSaison = "if(! isBefore('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_annee_encours[0], $date_fin_annee_encours[1], $date_fin_annee_encours[2])) . "', document.ADForm.HTML_GEN_date_date_debut.value)) { alert('- " . _("La date précisée dans le champ \'Date début\' doit être postérieure à la date de fin de l année agricole précédent (".$date_fin_annee_encours[1]."/".$date_fin_annee_encours[0]."/".$date_fin_annee_encours[2].")")."'); document.ADForm.HTML_GEN_date_date_debut.focus(); return false; }";
      $checkDateSaison = "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin.value)) { alert('- " . _("La date précisée dans le champ \'Date fin\' doit être postérieure à la date de debut")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
      $checkDateSaison .="
  if( ".$etat_annee_encours." != '' && ".$etat_annee_encours." !=2 && document.ADForm.HTML_GEN_LSB_etat.value == 1 ){ alert('- " . _("il y a deja une année ouverte")."');document.ADForm.HTML_GEN_LSB_etat.focus(); return false;}";
    }

    if ($SESSION_VARS['modif_table'] == 'ec_saison_culturale'){
      //Controle Javascript
      $date = date("d/m/Y");
      $checkDateAvailable = "";

      // check si la date est superieur a la date du jour
      $checkDateAvailable .= "if(! isBefore('" . $date . "', document.ADForm.HTML_GEN_date_date_debut.value) && document.ADForm.HTML_GEN_date_date_debut.value != '') { document.ADForm.HTML_GEN_date_date_debut.readOnly = true; }";
      $checkDateAvailable .= "if(! isBefore('" . $date . "', document.ADForm.HTML_GEN_date_date_fin_avance.value) && document.ADForm.HTML_GEN_date_date_fin_avance.value != '') { document.ADForm.HTML_GEN_date_date_fin_avance.readOnly = true; }";
      $checkDateAvailable .= "if(! isBefore('" . $date . "', document.ADForm.HTML_GEN_date_date_debut_solde.value) && document.ADForm.HTML_GEN_date_date_debut_solde.value != '') { document.ADForm.HTML_GEN_date_date_debut_solde.readOnly = true; }";
      $checkDateAvailable .= "if(! isBefore('" . $date . "', document.ADForm.HTML_GEN_date_date_fin_solde.value) && document.ADForm.HTML_GEN_date_date_fin_solde.value != '') { document.ADForm.HTML_GEN_date_date_fin_solde.readOnly = true; }";
      $checkDateAvailable .= "if(! isBefore('" . $date . "', document.ADForm.HTML_GEN_date_date_fin.value) && document.ADForm.HTML_GEN_date_date_fin.value != '') { document.ADForm.HTML_GEN_date_date_fin.readOnly = true; }";

      //$js_hide = "document.ADForm.id_annee.readOnly = true;";

      $param = "id_saison = ".$contenu;
      $check_saison_exist = getListeSaisonPNSEBlatest($param);
      $date_debut_saison_exist_arr = pg2phpDatebis($check_saison_exist['date_debut']);
      $date_debut_saison_exist_bis = date("Y/m/d",mktime(0, 0, 0, (int)$date_debut_saison_exist_arr[0], $date_debut_saison_exist_arr[1], $date_debut_saison_exist_arr[2]));
      $date_debut_saison_exist = date("d/m/Y",mktime(0, 0, 0, (int)$date_debut_saison_exist_arr[0], $date_debut_saison_exist_arr[1], $date_debut_saison_exist_arr[2]));
      $date_fin_saison_exist_arr = pg2phpDatebis($check_saison_exist['date_fin']);

      $check_autre_saison_exist =CheckAutreSaisonExist(" id_saison <> ".$contenu);
      $date_debut_autre_saison_arr = pg2phpDatebis($check_autre_saison_exist['date_debut']);
      $date_fin_autre_saison_arr = pg2phpDatebis($check_autre_saison_exist['date_fin']);
      $date_debut_autre_saison_bis = date("Y/m/d",mktime(0, 0, 0, (int)$date_debut_autre_saison_arr[0], $date_debut_autre_saison_arr[1], $date_debut_autre_saison_arr[2]));
      $date_debut_autre_saison = date("d/m/Y",mktime(0, 0, 0, (int)$date_debut_autre_saison_arr[0], $date_debut_autre_saison_arr[1], $date_debut_autre_saison_arr[2]));
      $etat_saison_exist = $check_autre_saison_exist['etat_saison'];
      if($etat_saison_exist == NULL){
        $etat_saison_exist= "''";
      }

      // date debut-fin de l'annee agricole
      $data_annee = getDateAnneeAgricoleActif();
      $date_debut_annee_arr =pg2phpDatebis($data_annee['date_debut']);
      $date_fin_annee_arr = pg2phpDatebis($data_annee['date_fin']);

      $date_form = pg2phpDate($info['date_debut']['val']);
      if ($date_debut_saison_exist_bis > $date_debut_autre_saison_bis ){
        $isSuivante = true;
      }

      if ($isSuivante == true){
        // check si la date de debut est superieur a la date debut annee agricole
        $checkDateSaison = "if(! isBeforeOrEqualTo('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_debut_annee_arr[0], $date_debut_annee_arr[1], $date_debut_annee_arr[2])) . "', document.ADForm.HTML_GEN_date_date_debut.value)) { alert('- " . _("La date précisée dans le champ \'Date début de la saison\' doit être postérieure à la date de debut de l année agricole (".$date_debut_annee_arr[1]."/".$date_debut_annee_arr[0]."/".$date_debut_annee_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_debut.focus(); return false; }";
        // check si la date est superieur a la date de fin de la derniere saison culturale
        $checkDateSaison .= "if(! isBefore('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_autre_saison_arr[0], $date_fin_autre_saison_arr[1], $date_fin_autre_saison_arr[2])) . "', document.ADForm.HTML_GEN_date_date_debut.value)) { alert('- " . _("La date précisée dans le champ \'Date début de la saison\' doit être postérieure à la date de fin de la dernière saison (".$date_fin_autre_saison_arr[1]."/".$date_fin_autre_saison_arr[0]."/".$date_fin_autre_saison_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_debut.focus(); return false; }";
        // check si la date fin des avances est superieur a la date debut saison culturale
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin_avance.value) && document.ADForm.HTML_GEN_date_date_debut.value != '' && document.ADForm.HTML_GEN_date_date_fin_avance.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin des avances\' doit être postérieure à la Date début de la saison ")."'); document.ADForm.HTML_GEN_date_date_fin_avance.focus(); return false; }";
        // check si la date début des soldes est superieur a la date fin des avances
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_fin_avance.value, document.ADForm.HTML_GEN_date_date_debut_solde.value) && document.ADForm.HTML_GEN_date_date_fin_avance.value != '' && document.ADForm.HTML_GEN_date_date_debut_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date début des soldes\' doit être postérieure à la Date fin des avances")."'); document.ADForm.HTML_GEN_date_date_debut_solde.focus(); return false; }";
        // check si la date Date fin des soldes est superieur a la Date début des soldes
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut_solde.value, document.ADForm.HTML_GEN_date_date_fin_solde.value) && document.ADForm.HTML_GEN_date_date_debut_solde.value != '' && document.ADForm.HTML_GEN_date_date_fin_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin des soldes\' doit être postérieure à la Date début des soldes")."'); document.ADForm.HTML_GEN_date_date_fin_solde.focus(); return false; }";
        // check si la date Date fin de la saison est superieur a la Date fin des soldes
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_fin_solde.value, document.ADForm.HTML_GEN_date_date_fin.value) && document.ADForm.HTML_GEN_date_date_fin_solde.value != '' && document.ADForm.HTML_GEN_date_date_fin.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin de la saison\' doit être postérieure à la Date fin des soldes")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
        // check si la date de fin solde est anterieur a la date de fin de l'annee agricole
        $checkDateSaison .= "if(! isBeforeOrEqualTo(document.ADForm.HTML_GEN_date_date_fin_solde.value, '" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_annee_arr[0], $date_fin_annee_arr[1], $date_fin_annee_arr[2])) . "') && document.ADForm.HTML_GEN_date_date_fin_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin solde de la saison\' doit être antérieur à la date de fin de l année agricole(".$date_fin_annee_arr[1]."/".$date_fin_annee_arr[0]."/".$date_fin_annee_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_fin_solde.focus(); return false; }";
        // check si la date de fin est anterieur a la date de fin de l'annee agricole
        $checkDateSaison .= "if(! isBeforeOrEqualTo(document.ADForm.HTML_GEN_date_date_fin.value, '" . date("d/m/Y", mktime(0, 0, 0, (int)$date_fin_annee_arr[0], $date_fin_annee_arr[1], $date_fin_annee_arr[2])) . "') && document.ADForm.HTML_GEN_date_date_fin.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin de la saison\' doit être antérieur à la date de fin de l année agricole(".$date_fin_annee_arr[1]."/".$date_fin_annee_arr[0]."/".$date_fin_annee_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
        // check si un etat en cours existe deja

        $checkDateSaison .="
     if( ".$etat_saison_exist." != '' && ".$etat_saison_exist." !=2 && document.ADForm.HTML_GEN_LSB_etat_saison.value == 1 ){ alert('- " . _("il y a deja une saison ouverte")."');document.ADForm.HTML_GEN_LSB_etat_saison.focus(); return false;}";
      }
      else {
        // check si la date de debut est superieur a la date debut annee agricole
        $checkDateSaison .= "if(! isBeforeOrEqualTo('" . date("d/m/Y", mktime(0, 0, 0, (int)$date_debut_annee_arr[0], $date_debut_annee_arr[1], $date_debut_annee_arr[2])) . "', document.ADForm.HTML_GEN_date_date_debut.value)) { alert('- " . _("La date précisée dans le champ \'Date début de la saison\' doit être postérieure à la date de debut de l année agricole (".$date_debut_annee_arr[1]."/".$date_debut_annee_arr[0]."/".$date_debut_annee_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_debut.focus(); return false; }";
        // check si la date fin des avances est superieur a la date debut saison culturale
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut.value, document.ADForm.HTML_GEN_date_date_fin_avance.value) && document.ADForm.HTML_GEN_date_date_debut.value != '' && document.ADForm.HTML_GEN_date_date_fin_avance.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin des avances\' doit être postérieure à la Date début de la saison ")."'); document.ADForm.HTML_GEN_date_date_fin_avance.focus(); return false; }";
        // check si la date début des soldes est superieur a la date fin des avances
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_fin_avance.value, document.ADForm.HTML_GEN_date_date_debut_solde.value) && document.ADForm.HTML_GEN_date_date_fin_avance.value != '' && document.ADForm.HTML_GEN_date_date_debut_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date début des soldes\' doit être postérieure à la Date fin des avances")."'); document.ADForm.HTML_GEN_date_date_debut_solde.focus(); return false; }";
        // check si la date Date fin des soldes est superieur a la Date début des soldes
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_debut_solde.value, document.ADForm.HTML_GEN_date_date_fin_solde.value) && document.ADForm.HTML_GEN_date_date_debut_solde.value != '' && document.ADForm.HTML_GEN_date_date_fin_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin des soldes\' doit être postérieure à la Date début des soldes")."'); document.ADForm.HTML_GEN_date_date_fin_solde.focus(); return false; }";
        // check si la date Date fin de la saison est superieur a la Date fin des soldes
        $checkDateSaison .= "if(! isBefore(document.ADForm.HTML_GEN_date_date_fin_solde.value, document.ADForm.HTML_GEN_date_date_fin.value) && document.ADForm.HTML_GEN_date_date_fin_solde.value != '' && document.ADForm.HTML_GEN_date_date_fin.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin de la saison\' doit être postérieure à la Date fin des soldes")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
        // check si la date de fin solde est anterieur a la date de fin de l'annee agricole
        $checkDateSaison .= "if(! isBeforeOrEqualTo(document.ADForm.HTML_GEN_date_date_fin_solde.value, '" . date("d/m/Y", mktime(0, 0, 0, (int)$date_debut_autre_saison_arr[0], $date_debut_autre_saison_arr[1], $date_debut_autre_saison_arr[2])) . "') && document.ADForm.HTML_GEN_date_date_solde.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin solde de la saison\' doit être antérieur à la date de fin de la saison suivante(".$date_debut_autre_saison_arr[1]."/".$date_debut_autre_saison_arr[0]."/".$date_debut_autre_saison_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_fin_solde.focus(); return false; }";
        // check si la date de fin est anterieur a la date de fin de l'annee agricole
        $checkDateSaison .= "if(! isBeforeOrEqualTo(document.ADForm.HTML_GEN_date_date_fin.value, '" . date("d/m/Y", mktime(0, 0, 0, (int)$date_debut_autre_saison_arr[0], $date_debut_autre_saison_arr[1], $date_debut_autre_saison_arr[2])) . "') && document.ADForm.HTML_GEN_date_date_fin.value != '') { alert('- " . _("La date précisée dans le champ \'Date fin de la saison\' doit être antérieur à la date de fin de la saison suivante(".$date_debut_autre_saison_arr[1]."/".$date_debut_autre_saison_arr[0]."/".$date_debut_autre_saison_arr[2].")")."'); document.ADForm.HTML_GEN_date_date_fin.focus(); return false; }";
        // check si un etat en cours existe deja

        $checkDateSaison .="
   if( ".$etat_saison_exist." != '' && ".$etat_saison_exist." !=2 && document.ADForm.HTML_GEN_LSB_etat_saison.value == 1 ){ alert('- " . _("il y a deja une saison ouverte")."');document.ADForm.HTML_GEN_LSB_etat_saison.focus(); return false;}";
      }
      //$MyPage->addJS(JSP_FORM, "funct_hide", $js_hide);
      $MyPage->addJS(JSP_FORM, "funct_check_date", $checkDateAvailable);
    }

    if ($SESSION_VARS['modif_table'] == 'ec_produit'){
      $MyPage->setFieldProperties('type_produit', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties("type_produit", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties('type_produit', FIELDP_ADD_CHOICES, $adsys["adsys_type_produit"]);
      $MyPage->setFieldProperties("prix_unitaire", FIELDP_TYPE, TYPC_MNT);
      $MyPage->setFieldProperties("prix_unitaire", FIELDP_JS_EVENT, array("onChange" => "check_mnt_unitaire();"));
      $setPrixUnitaireModifiable = setPrixUnitaireModifiable();
      if ($setPrixUnitaireModifiable == FALSE) {
        //$MyPage->setFieldProperties("prix_unitaire", FIELDP_DEFAULT, 0);
        $MyPage->setFieldProperties("prix_unitaire", FIELDP_IS_LABEL, true);
      }
      $MyPage->setFieldProperties('etat_produit', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties("etat_produit", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties('etat_produit', FIELDP_ADD_CHOICES, $adsys["adsys_etat_produit"]);
      $MyPage->setFieldProperties("montant_minimum", FIELDP_TYPE, TYPC_MNT);
      $MyPage->setFieldProperties("montant_minimum", FIELDP_IS_REQUIRED, true);
      $MyPage->setFieldProperties('compte_produit', FIELDP_ADD_CHOICES, $SESSION_VARS['info']['compte_produit']['choices']);
      $MyPage->setFieldProperties('compte_produit', FIELDP_IS_REQUIRED, true);

      $checkMnt_unitaire = "
      function check_mnt_unitaire() {
      mnt_unitaire = recupMontant(document.ADForm.prix_unitaire.value);
      mnt_mini = recupMontant(document.ADForm.montant_minimum.value);
      
      if(parseInt(mnt_unitaire) < parseInt(mnt_mini)){
       alert('- " . _("le prix unitaire doit être supérieur au prix minimum!")."');document.ADForm.prix_unitaire.focus();
       document.getElementsByName('prix_unitaire').item(0).value = 0;
       return false;
      }
      }
      ";
      $MyPage->addJS(JSP_FORM, "JS1", $checkMnt_unitaire);

    }

    if ($SESSION_VARS['modif_table'] == 'ec_localisation'){
      $codejs = " function populateParent()
      {
        if (document.ADForm.HTML_GEN_LSB_type_localisation.value > 1) {
            var _cQueue = [];
            var valueToPush = {};
            if (document.ADForm.HTML_GEN_LSB_type_localisation.value == 2){
              ";
      $where = "type_localisation = 1";
      $valueToPush = getListelocalisationPNSEB($where);
      while (list($key, $value) = each($valueToPush)) {
        $codejs.=" valueToPush['".$key."'] = '".$value."';";
      }
      $codejs.="} if (document.ADForm.HTML_GEN_LSB_type_localisation.value == 3){
              ";
      $where = "type_localisation = 2";
      $valueToPush = getListelocalisationPNSEB($where);
      while (list($key, $value) = each($valueToPush)) {
        $codejs.=" valueToPush['".$key."'] = '".$value."';";
      }
      $codejs.="} if (document.ADForm.HTML_GEN_LSB_type_localisation.value == 4){
              ";
      $where = "type_localisation = 3";
      $valueToPush = getListelocalisationPNSEB($where);
      while (list($key, $value) = each($valueToPush)) {
        $codejs.=" valueToPush['".$key."'] = '".$value."';";
      }
      $codejs.="}";
      $codejs.="
            _cQueue.push(valueToPush);

            var slt = document.ADForm.HTML_GEN_LSB_parent;
            // Reset select
            slt.options.length = 0;
            // Set default value
            slt.options[0] = new Option(\"[Aucun]\", \"0\", true, true);
            for (var i=0; i<_cQueue.length; i++) { // iterate on the array
              var obj = _cQueue[i];
              for (var key in obj) { // iterate on object properties
                var value = obj[key];
                //console.log(value);
                 opt = document.createElement('option');
                 opt.value = key;
                 opt.text = value;
                 slt.appendChild(opt);
              }
            }
        } else {
            var slt = document.ADForm.HTML_GEN_LSB_parent;
            // Reset select
            slt.options.length = 0;
            // Set default value
            slt.options[0] = new Option(\"[Aucun]\", \"0\", true, true);
        }
      }";
      $MyPage->addJS(JSP_FORM, "JS1", $codejs);
      $MyPage->setFieldProperties('type_localisation', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties("type_localisation", FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties('type_localisation', FIELDP_ADD_CHOICES, $adsys["adsys_type_localisation"]);
      $MyPage->setFieldProperties("type_localisation", FIELDP_JS_EVENT, array("onChange" => "populateParent();"));

      $where = "type_localisation = ".($contenu-1);
      $valueParentList = getListelocalisationPNSEB($where);
      $MyPage->setFieldProperties('parent', FIELDP_TYPE, TYPC_LSB);
      $MyPage->setFieldProperties('parent', FIELDP_HAS_CHOICE_AUCUN,true);
      $MyPage->setFieldProperties('parent', FIELDP_ADD_CHOICES, $valueParentList);
    }

    //Bouton
    $MyPage->addFormButton(1, 1, "butval", _("Valider"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butval", BUTP_PROCHAIN_ECRAN, "Gmf-2");
    $MyPage->setFormButtonProperties("butval", BUTP_JS_EVENT, array("onclick" => $checkDateSaison));
    if($SESSION_VARS['modif_table'] == "ec_annee_agricole") {
      if($saison_ouverte !=null){
        $MyPage->setFormButtonProperties("butval", BUTP_JS_EVENT, array("onclick" =>
          " if (document.ADForm.HTML_GEN_LSB_etat.value == 2){
        if (!confirm('" . _("ATTENTION") . "\\n " . _("Cette operation permet la fermeture de l\' année agricole. \\nPar conséquent, tous les commandes enregistrées, en cours ou en attentes de derogation seront passées en état non-soldé. \\n Une saison culturale pour cette anneé agricole est ouverte. Elle sera mis en etat fermé si vous continuez.\\nEtes-vous sur de vouloir continuer ? ") . "')) return false;
        }"));
      }else{
        $MyPage->setFormButtonProperties("butval", BUTP_JS_EVENT, array("onclick" =>
          " if (document.ADForm.HTML_GEN_LSB_etat.value == 2){
        if (!confirm('" . _("ATTENTION") . "\\n " . _("Cette operation permet la fermeture de l\' année agricole. \\nPar conséquent, tous les commandes enregistrées, en cours ou en attentes de derogation seront passées en état non-soldé.\\nEtes-vous sur de vouloir continuer ? ") . "')) return false;
        }"));
      }

    }
    /*if($SESSION_VARS['modif_table'] == "ec_saison_culturale") {
      $MyPage->setFormButtonProperties("butval", BUTP_JS_EVENT, array("onclick" =>
        " if (document.ADForm.HTML_GEN_LSB_etat_saison.value == 2){
        if (!confirm('" . _("ATTENTION") . "\\n " . _("Cette operation permet la fermeture de la saison culturale. \\nPar conséquent, tous les prix unitaires des produits seront ré-initialiser.\\nEtes-vous sur de vouloir continuer ? ") . "')) return false;
        }"));
    }*/


    $MyPage->addFormButton(1, 2, "butret", _("Annuler"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-3");

    $MyPage->buildHTML();
    echo $MyPage->getHTML();
  }else signalErreur(__FILE__,__LINE__,__FUNCTION__);
}

else if ($global_nom_ecran == "Gmf-2") {
  global $dbHandler,$global_id_agence;
  $ary_exclude = array("id_annee");

  //création DATA à mettre à jour
  reset($SESSION_VARS['info']);

  while (list($key, $value) = each($SESSION_VARS['info']))
  {
    if (($key != "pkey") && (!in_array($key, $ary_exclude))) { //On n'insére pas les clés primaires

      //On n'insére pas certains champs en fonction du contexte
      if ((($value['type'] == TYPC_MNT) || ($value['type'] == TYPC_INT) || ($value['type'] == TYPC_PRC)) && (${$key} == NULL))
      {
        ${$key} = "0"; //NULL correspond à la valeur zéro pour les chiffres
      }

      if ($value['type'] == TYPC_DTG && (${$key} == "")) {
        ${$key} = "NULL"; //reset les dates
      }

      //FIXME : je sais, ce n'est vraiment pas propre...
      //if (($value['type'] == TYPC_TXT) && (${$key} == 0) && ($value['ref_field'] == 1400))
      // ${$key} = "NULL";

      if (($value['type'] == TYPC_TXT) && ($value['ref_field'] == 1400)) {
        // On consodère que la valeur 0 pour les list box est le choix [Aucun]
        if (${"HTML_GEN_LSB_" . $key} == "0")
          ${$key} = "NULL";
        else
          $DATA[$key] = ${"HTML_GEN_LSB_" . $key
          };
      }

      if ($value['type'] == TYPC_MNT)
        $DATA[$key] = recupMontant(${$key});
      else if ($value['type'] == TYPC_BOL) {
        if (isset(${$key}))
          $DATA[$key] = "t";
        else
          $DATA[$key] = "f";
      }
      else if ($value['type'] == TYPC_PRC)
        $DATA[$key] = "" . ((${$key}) / 100) . "";
      else
        $DATA[$key] = ${$key};
    }
  }
  if ($SESSION_VARS['modif_table'] == 'ec_annee_agricole' && $etat == 2){
    $id_annee_param =$SESSION_VARS['info']['modif_pkeyid'];
    $db = $dbHandler->openConnection();
    $sql="select * from fermeture_annee_agricole($id_annee_param)";
    $result= $db->query($sql);
    if (DB::isError($result)) {
      $dbHandler->closeConnection(false);
      signalErreur(__FILE__,__LINE__,__FUNCTION__,$result->getMessage());
    }
    $saison_ouverte_valider=getListeSaisonPNSEB("id_annee=".$id_annee_param."and etat_saison = 1");
    if ($saison_ouverte_valider !=null){
      $sql_update = "update ec_saison_culturale set etat_saison = 2 where id_annee = $id_annee_param ";
      $result_update= $db->query($sql_update);
      if (DB::isError($result_update)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__,__LINE__,__FUNCTION__,$result_update->getMessage());
      }
    }
    $dbHandler->closeConnection(true);
  }

  if ($SESSION_VARS['modif_table'] == 'ec_saison_culturale' && $etat_saison == 2){
    $id_annee_param =$SESSION_VARS['info']['modif_pkeyid'];
    $countStock = CheckCountAgentStock();
    if ($countStock["count"] > 0){
      $MyPage = new HTML_erreur(_("Confirmation modification"));
      $MyPage->setMessage(sprintf(_("Veuillez verifier que les delestages des agents soit affectués avant de fermer la saison !")));
      $MyPage->addButton(BUTTON_OK, "Gfp-2");
      $MyPage->buildHTML();
      echo $MyPage->HTML_code;
      exit();
    }
  }

  //Mise à jour de la table : appel dbProcedure
  $myErr =  modif_table($SESSION_VARS['modif_table'], $SESSION_VARS['info']['pkey'], $SESSION_VARS['info']['modif_pkeyid'], $DATA);

  //HTML
  if ($myErr->errCode==NO_ERR) {
    $MyPage = new HTML_message(_("Confirmation modification"));
    $MyPage->setMessage(sprintf(_("L'entrée a été modifiée avec succès !")));
    $MyPage->addButton(BUTTON_OK, "Gfp-2");
    $MyPage->buildHTML();
    echo $MyPage->HTML_code;
  }
  else{
    $MyPage = new HTML_erreur(_("Echec de la modification"));
    $MyPage->setMessage($error[$myErr->errCode]);
    $MyPage->addButton(BUTTON_OK, "Gfp-2");
    $MyPage->buildHTML();
    echo $MyPage->HTML_code;
  }
}

else if ($global_nom_ecran == "Gmd-1") {
  if (!isset($SESSION_VARS['table']) || $SESSION_VARS['table'] == '') {
    $SESSION_VARS['table'] = $table;
  }

  $MyPage = new HTML_GEN2(_("Liste des automatismes "));
  if($SESSION_VARS['table'] == 'mod_aut') {
    $array_menu_engrais = array();
    if (isEngraisChimiques() && check_access(253)) {
      $array_menu_engrais["mod_pnseb"] = _("PNSEB-FENACOBU");
    }
    /*$array_menu_engrais = array(
      'mod_pnseb'=>_("PNSEB-FENACOBU")
    );*/
    $MyPage->addField("contenu", _("Liste des modules"), TYPC_LSB);
    $MyPage->setFieldProperties("contenu", FIELDP_HAS_CHOICE_AUCUN,true);
    $MyPage->setFieldProperties("contenu", FIELDP_ADD_CHOICES, $array_menu_engrais);
    $MyPage->setFieldProperties("contenu", FIELDP_IS_REQUIRED, true);
  }

  //Bouton formulaire
  $MyPage->addButton("contenu", "butparam", _("Parametrer"), TYPB_SUBMIT);
  $MyPage->setButtonProperties("butparam", BUTP_PROCHAIN_ECRAN, "Gmd-2");
  $MyPage->setButtonProperties("butparam", BUTP_AXS, 252);


  $MyPage->addFormButton(1,1, "butret", _("Retour"), TYPB_SUBMIT);
  $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
  $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-1");

  $MyPage->buildHTML();
  echo $MyPage->getHTML();
}

else if ($global_nom_ecran == "Gmd-2") {
  if (!isset($SESSION_VARS['table']) || $SESSION_VARS['table'] == '') {
    $SESSION_VARS['table'] = $table;
  }


  $MyPage = new HTML_GEN2(_("Automatisme PNSEB-FENACOBU"));
  if($SESSION_VARS['table'] == 'mod_aut') {
    $array_menu_automatisme = array(
      'mod_update_mnt'=>_("Mise à jour des montants commandes"),
      'raz_prix_produit'=>_("RAZ des prix unitaires des produits")
    );
    $MyPage->addField("contenu", _("Automatisme PNSEB-FENACOBU"), TYPC_LSB);
    $MyPage->setFieldProperties("contenu", FIELDP_HAS_CHOICE_AUCUN,true);
    $MyPage->setFieldProperties("contenu", FIELDP_ADD_CHOICES, $array_menu_automatisme);
    $MyPage->setFieldProperties("contenu", FIELDP_IS_REQUIRED, true);
  }

  //Bouton formulaire
  $MyPage->addButton("contenu", "butparam", _("Parametrer"), TYPB_SUBMIT);
  $MyPage->setButtonProperties("butparam", BUTP_PROCHAIN_ECRAN, "Gmd-3");
  $MyPage->setButtonProperties("butparam", BUTP_AXS, 252);


  $MyPage->addFormButton(1,1, "butret", _("Retour"), TYPB_SUBMIT);
  $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
  $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-1");

  $MyPage->buildHTML();
  echo $MyPage->getHTML();
}
else if ($global_nom_ecran == "Gmd-3") {
  if($contenu == 'mod_update_mnt') {
    $SESSION_VARS['auto_selected'] = $contenu;
    $MyPage = new HTML_GEN2(_("Mise à jour automatique des montants des commandes"));
    $alert_message = "";
    $alert_message = sprintf("<font color='red'>Cet automatisme permet de mettre à jour les montants pour toutes les commandes valides</font>");
    $msg_annulation = "<table align=\"center\" cellpadding=\"5\" width=\"65% \" border=0 cellspacing=$tableau_cellspacing cellpadding=$tableau_cellpadding >
    <tr align=\"center\" ><th></th><th></th><th></th><th></th><th></th><th></th></tr><tr><td align=\"center\"  colspan='6'>".$alert_message."</td></tr></table></br>";
    $MyPage->addHTMLExtraCode("msg_annulation", $msg_annulation);


    $MyPage->addFormButton(1,1, "butmaj", _("Mise à jour automatique des montants des commandes"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butmaj", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butmaj", BUTP_PROCHAIN_ECRAN, "Gmd-4");

    $MyPage->addFormButton(1,2, "butret", _("Retour"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-1");


    $MyPage->buildHTML();
    echo $MyPage->getHTML();
  }

  if($contenu == 'raz_prix_produit') {
    $SESSION_VARS['auto_selected'] = $contenu;
    $MyPage = new HTML_GEN2(_("Mise à jour automatique des prix unitaires des produits"));
    $alert_message = "";
    $alert_message = sprintf("<font color='red'>Cet automatisme permet de mettre à jour les prix unitaires des produits</font>");
    $msg_annulation = "<table align=\"center\" cellpadding=\"5\" width=\"65% \" border=0 cellspacing=$tableau_cellspacing cellpadding=$tableau_cellpadding >
    <tr align=\"center\" ><th></th><th></th><th></th><th></th><th></th><th></th></tr><tr><td align=\"center\"  colspan='6'>".$alert_message."</td></tr></table></br>";
    $MyPage->addHTMLExtraCode("msg_annulation", $msg_annulation);


    $MyPage->addFormButton(1,1, "butmaj", _("Mise a jour des prix untaires des produits"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butmaj", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butmaj", BUTP_PROCHAIN_ECRAN, "Gmd-4");

    $MyPage->addFormButton(1,2, "butret", _("Retour"), TYPB_SUBMIT);
    $MyPage->setFormButtonProperties("butret", BUTP_CHECK_FORM, false);
    $MyPage->setFormButtonProperties("butret", BUTP_PROCHAIN_ECRAN, "Gfp-1");


    $MyPage->buildHTML();
    echo $MyPage->getHTML();
  }
}
else if ($global_nom_ecran == "Gmd-4") {

  if($SESSION_VARS['auto_selected'] == 'mod_update_mnt') {
    global $dbHandler,$global_id_agence;

    $condition_annee_agri = "etat =1";
    $annee_agri_actuelle =getRangeDateAnneeAgri($condition_annee_agri);

    $condition_saison_cultu = "id_annee = ".$annee_agri_actuelle['id_annee']." and etat_saison = 1";
    $saison_cultu_acutelle = getDetailSaisonCultu($condition_saison_cultu);
    $id_annee= $annee_agri_actuelle['id_annee'];
    $id_saison = $saison_cultu_acutelle['id_saison'];


    $condi1="etat_produit = 1";
    $verif_prix_prod =getListeProduitPNSEB($condi1,true);

    while (list($key, $DET) = each($verif_prix_prod)) {
      if ($DET['prix_unitaire'] == 0){
        $html_err = new HTML_erreur(_("Mise a jour des montants des commandes"));
        $html_err->setMessage(_("Le prix unitaire du produit : ".$DET['libel']." n'a pas été renseigné"));
        $html_err->addButton("BUTTON_OK", 'Gfp-1');
        $html_err->buildHTML();
        echo $html_err->HTML_code;
        exit();
      }
    }


    $db = $dbHandler->openConnection();
    $sql="select * from update_montant_commande($id_annee,$id_saison)";
    $result= $db->query($sql);
    if (DB::isError($result)) {
      $dbHandler->closeConnection(false);
      signalErreur(__FILE__,__LINE__,__FUNCTION__,$result->getMessage());
    }

    $condi_hist="etat_produit = 1";
    $produits_actif =getListeProduitPNSEB($condi_hist,true);
    while (list($key1, $DET1) = each($produits_actif)) {
      $DATA_PROD_HIST = array(
        'id_produit'=>$DET1['id_produit'],
        'id_saison' => $id_saison,
        'prix_unitaire' => $DET1['prix_unitaire'],
        'date_creation' =>date('r'),
        'id_ag' =>$global_id_agence
      );
      $result1 = executeQuery($db, buildInsertQuery("ec_produit_hist", $DATA_PROD_HIST));
      if (DB::isError($result1)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__,__LINE__,__FUNCTION__,$result1->getMessage());
      }
    }



    $dbHandler->closeConnection(true);

    $html_msg = new HTML_message("Confirmation de la mise à jour des montants des commandes");

    $demande_msg = "Votre automatisme de mise à jour est reussi!";


    $html_msg->setMessage(sprintf(" <br />%s  !<br /> ", $demande_msg));

    $html_msg->addButton("BUTTON_OK", 'Gfp-1');
    $html_msg->buildHTML();
    echo $html_msg->HTML_code;

  }

  if($SESSION_VARS['auto_selected'] == 'raz_prix_produit') {
    global $dbHandler, $global_id_agence;
    $db = $dbHandler->openConnection();
    $sql="update ec_produit set prix_unitaire = 0";
    $result= $db->query($sql);
    if (DB::isError($result)) {
      $dbHandler->closeConnection(false);
      signalErreur(__FILE__,__LINE__,__FUNCTION__,$result->getMessage());
    } else{
      $dbHandler->closeConnection(true);
      $html_msg = new HTML_message("Confirmation de la mise à jour des prix des produits");

      $demande_msg = "Votre automatisme de mise à jour est reussi!";


      $html_msg->setMessage(sprintf(" <br />%s  !<br /> ", $demande_msg));

      $html_msg->addButton("BUTTON_OK", 'Gfp-1');
      $html_msg->buildHTML();
      echo $html_msg->HTML_code;
    }


  }
}

?>