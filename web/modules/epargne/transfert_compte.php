<?php
/*
  Transfert entre comptes d'épargnes clients

Description :
  Ce module crée 3 écrans :
  * Tcp-1 : Choix d'un compte source,  du type de transfert et du compte de destination
  * Tcp-2 : Mouvment des comptes

  HD - 19/02/2002
*/
require_once 'lib/dbProcedures/epargne.php';
require_once 'lib/dbProcedures/client.php';
require_once 'lib/dbProcedures/compte.php';
require_once 'lib/dbProcedures/credit_lcr.php';
require_once 'lib/dbProcedures/guichet.php';
require_once 'lib/dbProcedures/tireur_benef.php';
require_once 'lib/html/FILL_HTML_GEN2.php';
require_once 'modules/epargne/recu.php';
require_once 'lib/dbProcedures/cheque_interne.php';

//---------------------------------------------------------------------------------------------
//------------------ ECRAN Tcp-1 : choix des comptes et du type de transfert ------------------
//---------------------------------------------------------------------------------------------
if ($global_nom_ecran == "Tcp-1") {
  unset($SESSION_VARS['CpteMemeClient']); // le compte de destination si transfert vers le même client
  unset($SESSION_VARS['ERR_DUREE_MIN_RETRAIT']); // unset la variable de check minimum retrait entre transfert ticket Jira REL-76

  for ($i=1;$i<=40;$i++) {
    unset( $SESSION_VARS[$i]['num_client']);
    unset( $SESSION_VARS[$i]['mnt']);
    unset( $SESSION_VARS[$i]['frais']);
    $exist[$i]=0;
  }

  //afficher la liste des comptes du client puis le montant à retirer et ne pas oublier les frais d'opérations éventuels
  $html = new HTML_GEN2(_("Transfert entre comptes : Choix du compte"));

  //affichage de tous les comptes du client et s'il n'y a rien...ne rien faire
  $TempListeComptes = get_comptes_epargne($global_id_client);

  //retirer de la liste les comptes à retrait unique
  $choix = array();
  if (isset($TempListeComptes)) {
    //déterminer les comptes à partir desquels on peut retirer pour le transfert
    $ListeComptes = getComptesRetraitPossible($TempListeComptes);
    if (isset($ListeComptes)) {
      //index par id_cpte pour la listbox
      foreach($ListeComptes as $key=>$value) $choix[$key] = $value["num_complet_cpte"]." ".$value["intitule_compte"];
    }

    //si le client est débiteur, enlever le compte de base de la liste des comptes
    if ($global_client_debiteur) {
      $id_cpte_base = getBaseAccountID($global_id_client);
      unset($choix[$id_cpte_base]);
    }

    //si le client a des comptes sur lesquels on  peut faire un transfert, sinon ne pas lui permettre de poursuivre la procédure
    $ListeComptes2 = getComptesDepotPossible($TempListeComptes);

    //si y a qu'un compte dans liste comptes dépôts et retraits, il s'agit compte de base, donc pas de transfert possible
    if (isset($ListeComptes2) && (count($ListeComptes2) == 1) && (count($ListeComptes) == 1))
      unset($ListeComptes2);
  }

  $html->addField("NumCpteSource", _("Numéro de compte source"), TYPC_LSB);
  $html->setFieldProperties("NumCpteSource", FIELDP_ADD_CHOICES, $choix);
  $html->setFieldProperties("NumCpteSource", FIELDP_IS_REQUIRED, true);

  $html->addField("TypeTransfert", _("Type de transfert"), TYPC_LSB);
  //s'il n'y a pas de compte source pour le transfert, ne pas permettre le choix d'une destination
  if (count($choix)==0)
    $choix2 = array();
  else {
    if (isset($ListeComptes2))
      $choix2 = array(1=>_("Même client"), 2=>_("Virement interne"), 3=>_("Virement externe"), 4=>_("Transferts groupé"));
    else
      $choix2 = array(2=>_("Virement interne"), 3=>_("Virement externe"), 4=>_("Transferts groupé"));
  }

  $html->setFieldProperties("TypeTransfert", FIELDP_ADD_CHOICES, $choix2);
  $html->setFieldProperties("TypeTransfert", FIELDP_IS_REQUIRED, true);

  $html->addTable("ad_cpt", OPER_INCLUDE, array("etat_cpte", "devise"));
  $html->addTable("adsys_produit_epargne", OPER_INCLUDE, array("libel",  "duree_min_retrait_jour"));
  $html->setFieldProperties("libel", FIELDP_WIDTH, 30);

  //ordonner les champs
  $order_array = array("NumCpteSource","libel", "devise", "etat_cpte", "duree_min_retrait_jour","TypeTransfert");
  $html->setOrder(NULL, $order_array);
  //mettre les champs en label
  $fieldslabel = array_diff($order_array, array("NumCpteSource", "TypeTransfert"));
  foreach($fieldslabel as $value) {
    $html->setFieldProperties($value, FIELDP_IS_LABEL, true);
    $html->setFieldProperties($value, FIELDP_IS_REQUIRED, false);
  }

  // en fonction du choix du compte de départ, afficher les infos avec le onChange javascript
  $codejs = " function getInfoCompte() {";
  if (isset($ListeComptes)) {
    foreach($ListeComptes as $key=>$value) {
      $codejs .= "
                 if (document.ADForm.HTML_GEN_LSB_NumCpteSource.value == " . $key . ")
                 {
                 document.ADForm.HTML_GEN_LSB_etat_cpte.value = " . $value["etat_cpte"] . ";
                 document.ADForm.libel.value = \"" . $value["libel"] . "\";
                 document.ADForm.HTML_GEN_LSB_devise.value = '" . $value["devise"] . "';";
      if ($value["duree_min_retrait_jour"] > 0) {
        $codejs .= "
                   document.ADForm.duree_min_retrait_jour.value = " . $value["duree_min_retrait_jour"] . ";";
      } else {
      $codejs .= "
                   document.ADForm.duree_min_retrait_jour.value = '0';";
    }
       $codejs .="};";
    }
    $codejs .= "
               if (document.ADForm.HTML_GEN_LSB_NumCpteSource.value == '0')
             {
               document.ADForm.libel.value='';
               document.ADForm.mnt_min_cpte.value='';
               document.ADForm.HTML_GEN_LSB_etat_cpte.value='0';
               document.ADForm.duree_min_retrait_jour.value='';
             }";
  }
  $codejs .= "
           };getInfoCompte();";

  $html->setFieldProperties("NumCpteSource", FIELDP_JS_EVENT, array("onChange"=>"getInfoCompte();"));
  $html->addJS(JSP_FORM, "JS1", $codejs);

  $ChkJS = "
           if (document.ADForm.HTML_GEN_LSB_etat_cpte.value=='3')
         {
           msg += '-"._("Le compte source est bloqué")."\\n';
           ADFormValid=false;
         }";

  $html->addJS(JSP_BEGIN_CHECK, "JS2",$ChkJS);

  $html->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
  $html->addFormButton(1, 2, "cancel", _("Annuler"), TYPB_SUBMIT);
  $html->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Tcp-2');
  $html->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-10');
  $html->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);

  $html->buildHTML();
  echo $html->getHTML();
}

//---------------------------------------------------------------------
//----------- Tcp-2 Choix du compte destination -----------------------
//---------------------------------------------------------------------
else if ($global_nom_ecran == "Tcp-2") {
  unset($SESSION_VARS["frais_transfert"]); // tableau contenant les informations pour les frais de transfert

  // Le type de transfert : 1=>'même client', 2=>'Virement interne', 3=>'Virement externe',4=>'Transfert groupé'
  if (isset($TypeTransfert))
    $SESSION_VARS["TypeTransfert"] = $TypeTransfert;

  // Compte source du transfert
  if (isset($NumCpteSource))
    $SESSION_VARS["NumCpteSource"] = $NumCpteSource;

  // Affichage des informations compte source
  if ($SESSION_VARS['TypeTransfert'] >=1 && $SESSION_VARS['TypeTransfert'] <= 4) {
    switch ($SESSION_VARS['TypeTransfert']) {
    case 1:
      $html = new HTML_GEN2(_("Choix du compte destination"));
      break;
    case 2:
      $html = new HTML_GEN2(_("Virement sur le compte d'un autre client"));
      break;
    case 3:
      $html = new HTML_GEN2(_("Virement sur un compte externe"));
      break;
    case 4:
      $html = new HTML_GEN2(_("Transfert interne groupé"));
      break;
    }

    $ValueCompte = getAccountDatas($SESSION_VARS["NumCpteSource"]);
    setMonnaieCourante($ValueCompte['devise']);
    $soldeDispo = getSoldeDisponible($SESSION_VARS['NumCpteSource']);
    //Contrôle sur l'accès au solde
    $access_solde = get_profil_acces_solde($global_id_profil, $ValueCompte["id_prod"]);
    $access_solde_vip = get_profil_acces_solde_vip($global_id_profil, $global_id_client);
    if(manage_display_solde_access($access_solde, $access_solde_vip)){
    	$html->addField("soldeDispo", _("Solde disponible"), TYPC_MNT);
    	$html->setFieldProperties("soldeDispo",FIELDP_DEFAULT,$soldeDispo);
    	$html->setFieldProperties("soldeDispo", FIELDP_IS_LABEL, true);
    }else{
    	$html->addHiddenType("soldeDispo", $soldeDispo);
    }
    if(manage_display_solde_access($access_solde, $access_solde_vip))
    	$champsCpte = array ("mnt_min_cpte", "etat_cpte", "solde", "num_complet_cpte", "intitule_compte", "mnt_bloq", "mnt_bloq_cre");
    else
    	$champsCpte = array ("mnt_min_cpte", "etat_cpte", "num_complet_cpte", "intitule_compte", "mnt_bloq", "mnt_bloq_cre");
    $html->addTable("ad_cpt",OPER_INCLUDE, $champsCpte);
    $fill=new FILL_HTML_GEN2();
    $fill->addFillClause("cpteSource", "ad_cpt");
    $fill->addCondition("cpteSource", "id_cpte", $SESSION_VARS["NumCpteSource"]);
    $fill->addManyFillFields("cpteSource", OPER_INCLUDE, $champsCpte);
    $fill->fill($html);
    if(manage_display_solde_access($access_solde, $access_solde_vip))
    	$fieldslabel = array("etat_cpte", "solde", "num_complet_cpte", "intitule_compte", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "soldeDispo");
    else
    	$fieldslabel = array("etat_cpte", "num_complet_cpte", "intitule_compte", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte");
    foreach($fieldslabel as $value) {
      $html->setFieldProperties($value, FIELDP_IS_LABEL, true);
      $html->setFieldProperties($value, FIELDP_IS_REQUIRED, false);
    }
    $xtra1 = "<b>"._("Compte source")."</b>";
    $html->addHTMLExtraCode ("htm1", $xtra1);
    $html->setHTMLExtraCodeProperties ("htm1",HTMP_IN_TABLE, true);
    if ($SESSION_VARS["TypeTransfert"] < 4) { // Dans un transfert groupé, on ne précise pas encore le compte destination
      $xtra2 = "<b>"._("Compte destination")."</b>";
      $html->addHTMLExtraCode ("htm2", $xtra2);
      $html->setHTMLExtraCodeProperties ("htm2",HTMP_IN_TABLE, true);
    }

    $xtrafrais = "<br/><b>"._("Frais de transfert")."</b>";
    $html->addHTMLExtraCode ("htmlfrais", $xtrafrais);
    $html->setHTMLExtraCodeProperties ("htmlfrais",HTMP_IN_TABLE, true);
    $type_cpte_preleve = array(1=>_("Compte source uniquement"), 2=>_("Chaque compte de destination"));
    $html->addField("type_cpte_preleve",_("Compte de prélèvement"),TYPC_LSB); //compte source ou compte de destination
    $html->setFieldProperties("type_cpte_preleve", FIELDP_ADD_CHOICES, $type_cpte_preleve);
    $html->setFieldProperties("type_cpte_preleve",FIELDP_DEFAULT,1); // compte source par défaut
    $html->setFieldProperties("type_cpte_preleve", FIELDP_HAS_CHOICE_AUCUN, false);
    $type_mnt_frais = array(1=>_("Frais produit compte source"), 2=>_("Frais produit compte de destination"));
    $html->addField("type_mnt_frais",_("Montant des frais"),TYPC_LSB); //compte source ou compte de destination
    $html->setFieldProperties("type_mnt_frais", FIELDP_ADD_CHOICES, $type_mnt_frais);
    $html->setFieldProperties("type_mnt_frais",FIELDP_DEFAULT,1); // frais du produit du ompte source par défaut
    $html->setFieldProperties("type_mnt_frais", FIELDP_HAS_CHOICE_AUCUN, false);
  }

  // Si c'est un transfert sur le même client
  if ($SESSION_VARS["TypeTransfert"] == 1) {
    // Récupération des comptes sur lesquels on peut faire un dépôt, sauf le compte source
    $choix3 = array();
    $TempListeComptes = get_comptes_epargne($global_id_client);
    if (isset($TempListeComptes)) {
      $ListeComptes2 = getComptesDepotPossible($TempListeComptes);
      if (isset($ListeComptes2)) {
        foreach($ListeComptes2 as $key=>$value) {
          if ($value["id_cpte"] != $SESSION_VARS["NumCpteSource"])
            $choix3[$key] = $value["num_complet_cpte"]." ".$value["intitule_compte"];
        }
      }
    }

    $html->addField("CpteMemeClient",_("Compte de destination"),TYPC_LSB);
    $html->setFieldProperties("CpteMemeClient", FIELDP_ADD_CHOICES, $choix3);
    $html->setFieldProperties("CpteMemeClient",FIELDP_DEFAULT, $SESSION_VARS['CpteMemeClient']);

    $html->addField("libel2",_("Libellé du produit d'épargne"),TYPC_TXT);
    $html->setFieldProperties("libel2", FIELDP_IS_LABEL, true);
    $html->setFieldProperties("libel2", FIELDP_WIDTH, 40);

    // JS pour afficher le libellé du produit associé au compte de destination
    $codejs = "\n\nfunction getInfoCompte2() {";
    $codejs .= "\n\tif(document.ADForm.HTML_GEN_LSB_CpteMemeClient.value == 0)";
    $codejs .= "\n\tdocument.ADForm.libel2.value = '';\n";
    if (isset($ListeComptes2)) {
      foreach($ListeComptes2 as $key=>$value) {
        $codejs .= "\n\tif (document.ADForm.HTML_GEN_LSB_CpteMemeClient.value == ".$key.")";
        $codejs .= "\n\tdocument.ADForm.libel2.value = '".$value["libel"]."';";
      }
    }
    $codejs .= "}\n";

    $html->setFieldProperties("CpteMemeClient", FIELDP_JS_EVENT, array("onChange"=>"getInfoCompte2();"));
    $html->addJS(JSP_FORM, "JS3", $codejs);

    // JS pour rendre obligatoire la saisie du compte de destination
    $ChkJS = "\n\tif (document.ADForm.HTML_GEN_LSB_CpteMemeClient.value == '0')";
    $ChkJS .= "{msg += '-"._("Vous devez saisir une valeur pour le compte client")."\\n'; ADFormValid=false;};\n";
    $html->addJS(JSP_BEGIN_CHECK, "JS4",$ChkJS);

    $html->addHTMLExtraCode("ligne_sep","<br />");
    if(manage_display_solde_access($access_solde, $access_solde_vip))
    	$order = array("htm1", "num_complet_cpte", "intitule_compte", "solde", "etat_cpte", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "soldeDispo", "ligne_sep", "htm2", "CpteMemeClient", "libel2", "htmlfrais", "type_cpte_preleve");
    else
    	$order = array("htm1", "num_complet_cpte", "intitule_compte", "etat_cpte", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "ligne_sep", "htm2", "CpteMemeClient", "libel2", "htmlfrais", "type_cpte_preleve");
    $html->setOrder(NULL,$order);

  }
  elseif ($SESSION_VARS["TypeTransfert"] == 2) { // transfert vers un autre client
    $html->addField("cpt_dest",_("Compte destinataire"), TYPC_TXT);
    $html->setFieldProperties("cpt_dest", FIELDP_IS_REQUIRED, true);
    $html->addLink("cpt_dest", "rechercher", _("Rechercher"), "#");
    $str = "if (document.ADForm.cpt_dest.disabled == false) OpenBrw('../modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&choixCompte=1&cpt_dest=cpt_dest&id_cpt_dest=id_cpt_dest', '"._("Recherche")."');return false;";
    $html->setLinkProperties("rechercher", LINKP_JS_EVENT, array("onclick" => $str));
    $html->addHiddenType("id_cpt_dest", "");
    $html->addHTMLExtraCode("ligne_sep","<br />");
    if(manage_display_solde_access($access_solde, $access_solde_vip))
    	$order=array("htm1", "num_complet_cpte", "intitule_compte", "etat_cpte", "solde", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "soldeDispo", "ligne_sep", "htm2", "cpt_dest", "htmlfrais", "type_cpte_preleve");
    else
    	$order=array("htm1", "num_complet_cpte", "intitule_compte", "etat_cpte", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "ligne_sep", "htm2", "cpt_dest", "htmlfrais", "type_cpte_preleve");
    $html->setOrder(NULL,$order);
  }
  else if ($SESSION_VARS["TypeTransfert"] == 3 ) { // transfert vers un compte externe
    // Compte de prélèvement des frais est d'office le compte source du transfert
    $SESSION_VARS["frais_transfert"]['type_cpte_preleve'] = 1;
    $html->setFieldProperties("type_cpte_preleve",FIELDP_DEFAULT, 1); // frais du produit du ompte source par défaut
    $html->setFieldProperties("type_cpte_preleve", FIELDP_IS_LABEL, true);

    // les frais de transfert sont paramétrés dans le produit du compte source
    $html->setFieldProperties("type_mnt_frais",FIELDP_DEFAULT, 1);
    $html->setFieldProperties("type_mnt_frais", FIELDP_IS_LABEL, true);
    $SESSION_VARS["frais_transfert"]['type_mnt_frais'] = 1;

    $html->addField("nom_ben",_("Bénéficiaire"), TYPC_TXT);
    $html->setFieldProperties("nom_ben", FIELDP_IS_LABEL, true);
    $html->setFieldProperties("nom_ben", FIELDP_WIDTH, 40);
    $html->addLink("nom_ben", "rechercher", _("Rechercher"), "#");
    $html->setLinkProperties("rechercher", LINKP_JS_EVENT, array("onclick" => "OpenBrw('$SERVER_NAME/modules/externe/rech_benef.php?m_agc=".$_REQUEST['m_agc']."&field_name=nom_ben&field_id=id_ben&type=b', '"._("Recherche")."');return false;"));
    $html->addHiddenType("id_ben", "");
    $checkJS = "if (document.ADForm.id_ben.value == '')
             {
               msg += '- "._("le Bénéficiaire n\'est pas renseigné")."\\n';
               ADFormValid=false;
             }";

    $listeCorrespondant = getLibelCorrespondant();
    $html->addField("id_cor", _("Correspondant bancaire"), TYPC_LSB);
    $html->setFieldProperties("id_cor", FIELDP_ADD_CHOICES , $listeCorrespondant);
    $html->setFieldProperties("id_cor", FIELDP_IS_REQUIRED, true);

    $html->addHTMLExtraCode("ligne_sep","<br />");
    if(manage_display_solde_access($access_solde, $access_solde_vip))
    	$order=array("htm1", "num_complet_cpte", "intitule_compte", "etat_cpte", "solde", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "soldeDispo", "ligne_sep","htm2", "nom_ben", "id_cor", "htmlfrais", "type_cpte_preleve");
    else
    	$order=array("htm1", "num_complet_cpte", "intitule_compte", "etat_cpte", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "ligne_sep","htm2", "nom_ben", "id_cor", "htmlfrais", "type_cpte_preleve");
    $html->setOrder(NULL,$order);
  } else if ($SESSION_VARS["TypeTransfert"] == 4) { //Transfert groupé
  	if(manage_display_solde_access($access_solde, $access_solde_vip))
    	$order=array("htm1", "num_complet_cpte", "intitule_compte", "etat_cpte", "solde", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "soldeDispo", "htmlfrais", "type_cpte_preleve");
    else
    	$order=array("htm1", "num_complet_cpte", "intitule_compte", "etat_cpte", "mnt_bloq", "mnt_bloq_cre", "mnt_min_cpte", "htmlfrais", "type_cpte_preleve");
    $html->setOrder(NULL,$order);
  }

  $html->addJS(JSP_BEGIN_CHECK, "JS1",$checkJS);

  $html->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
  $html->addFormButton(1, 2, "retour", _("Précédent"), TYPB_SUBMIT);
  $html->addFormButton(1, 3, "cancel", _("Annuler"), TYPB_SUBMIT);
  $html->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Tcp-3');
  $html->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN,'Tcp-1');
  $html->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-10');
  $html->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);
  $html->setFormButtonProperties("retour", BUTP_CHECK_FORM, false);
  $html->buildHTML();
  echo $html->getHTML();
}
//---------------------------------------------------------------------
//----------- Tcp-3 Introduction du montant à transférer --------------
//---------------------------------------------------------------------
else if ($global_nom_ecran == "Tcp-3") {
  $html = new HTML_GEN2(_("Montant à transférer"));

  if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
    $SESSION_VARS['id_dem'] = $_GET['id_dem'];
    $infoRetraitAttente = getTransfertAttenteAutorise($SESSION_VARS['id_dem'], $global_id_client);
    $id_agence = $infoRetraitAttente['id_ag'];
    $autorise_id_client_src = $infoRetraitAttente['id_client_src'];
    $autorise_id_cpte_client_src = $infoRetraitAttente['id_cpte_client_src'];
    $autorise_montant_transfert = recupMontant($infoRetraitAttente["montant_transfert"]);
    $autorise_etat_transfert = $infoRetraitAttente["etat_transfert"];
    $autorise_type_transfert = $infoRetraitAttente["type_transfert"];
    $autorise_id_client_dest =$infoRetraitAttente["id_client_dest"];
    $autorise_id_cpte_client_dest =$infoRetraitAttente["id_cpte_client_dest"];
    $autorise_id_beneficiaire = $infoRetraitAttente["id_beneficiaire"];
    $autorise_id_cpte_ben = $infoRetraitAttente["id_cpte_ben"];
    $autorise_id_correspondant = $infoRetraitAttente["id_correspondant"];
    $autorise_groupe_client = $infoRetraitAttente["groupe_clients"];
    $autorise_type_frais_prelev = $infoRetraitAttente["type_frais_prelev"];
    $autorise_type_frais_montant= $infoRetraitAttente["mnt_frais_type"];
    $autorise_id_cpte_frais_transfert_prelev = $infoRetraitAttente["id_cpte_frais_transfert_prelev"];
    $autorise_devise_cpte_frais = $infoRetraitAttente["devise_cpte_frais"];
    $autorise_mnt_frais = recupMontant($infoRetraitAttente["mnt_frais"]);
    $autorise_devise_mnt_frais = $infoRetraitAttente["devise_frais"];
    $autorise_type_piece_justif = $infoRetraitAttente["type_piece_justificatif"];
    $autorise_num_chq = $infoRetraitAttente["num_chq_virement"];
    $autorise_date_chq = pg2phpDate($infoRetraitAttente["date_chq_virement"]);
    $autorise_type_retrait = $infoRetraitAttente["type_retrait"];
    if ($infoRetraitAttente["mandat"] != 'CONJ' && $infoRetraitAttente["mandat"] !=null){
      $MANDATS_AUTORISATION = getListeMandatairesActifsV2($infoRetraitAttente['id_cpte_client_src'],null,true);
      $autorise_id_mandat = $infoRetraitAttente["mandat"];
    } else{
      $autorise_id_mandat = $infoRetraitAttente["mandat"];
    }
    $autorise_communication = $infoRetraitAttente["communication"];
    $autorise_remarque = $infoRetraitAttente["remarque"];

    $jsPreLoadData = "
                    // Default values
                    if (document.ADForm.mnt) {
                        document.ADForm.mnt.value = '$autorise_montant_transfert';
                        document.ADForm.mnt.readOnly = true;
                    }
                    ";
    if ($autorise_id_mandat != null) {
      $jsPreLoadData .= " if (document.ADForm.HTML_GEN_LSB_mandat) {
                        document.ADForm.HTML_GEN_LSB_mandat.value = '$autorise_id_mandat';
                        document.ADForm.HTML_GEN_LSB_mandat.readOnly = true;
                    }";
    }
     $jsPreLoadData .=" if (document.ADForm.frais_transfert){
                      document.ADForm.frais_transfert.value = '$autorise_mnt_frais';
                      document.ADForm.mnt.readOnly = true;
                    }
                    if (document.ADForm.HTML_GEN_LSB_type_piece){
                      document.ADForm.HTML_GEN_LSB_type_piece.value = '$autorise_type_piece_justif';
                      document.ADForm.HTML_GEN_LSB_type_piece.readOnly = true;
                    }
                    if (document.ADForm.num_chq){
                      document.ADForm.num_chq.value = '$autorise_num_chq';
                      document.ADForm.num_chq.readOnly = true;
                    }
                    if (document.ADForm.HTML_GEN_date_date_chq){
                      document.ADForm.HTML_GEN_date_date_chq.value = '$autorise_date_chq';
                      document.ADForm.HTML_GEN_date_date_chq.readOnly = true;
                    }
                    if (document.ADForm.communication){
                      document.ADForm.communication.value = '$autorise_communication';
                      document.ADForm.communication.readOnly = true;
                    }
                    if (document.ADForm.remarque){
                      document.ADForm.remarque.value = '$autorise_remarque';
                      document.ADForm.remarque.readOnly = true;
                    }


                     ";

    $html->addJS(JSP_FORM, "JS_PRELOAD_DATA", $jsPreLoadData);
    if ($autorise_id_beneficiaire !=null){
      $id_ben = $autorise_id_beneficiaire;
    }
    if ($autorise_id_correspondant !=null){
      $id_cor = $autorise_id_correspondant;
    }
    if ($autorise_type_frais_prelev !=null){
      $type_cpte_preleve = $autorise_type_frais_prelev;
    }
    if ($autorise_type_frais_montant !=null){
      $type_mnt_frais = $autorise_type_frais_montant;
    }
    if($autorise_id_cpte_client_dest != null){
      if($autorise_type_transfert == 1){
        $CpteMemeClient = $autorise_id_cpte_client_dest;
      }else {
        $cpt_dest = $autorise_id_cpte_client_dest;
      }
    }
    $SESSION_VARS['TypeTransfert'] = $autorise_type_transfert;
  }


  if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
    $SESSION_VARS["NumCpteSource"] = $autorise_id_cpte_client_src;
  }
  if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
    $SESSION_VARS["type_piece"] = $autorise_type_piece_justif;
  }
  $cpteSrc = getAccountDatas($SESSION_VARS["NumCpteSource"]);
  $SESSION_VARS['devise'] = $cpteSrc['devise'];

  //$SESSION_VARS['TypeTransfert'] = $autorise_type_transfert;

  // ajouter
  /*if ($autorise_id_beneficiaire !=null){
    $id_ben = $autorise_id_beneficiaire;
  }
  if ($autorise_id_correspondant !=null){
    $id_cor = $autorise_id_correspondant;
  }*/
  // fin ajout
  if (isset($id_ben))
    $SESSION_VARS['id_ben'] = $id_ben;
  if (isset($id_cor))
    $SESSION_VARS['id_correspondant'] = $id_cor;

  //Mode de paiement des frais
  // ajouter
  /*if ($autorise_type_frais_prelev !=null){
    $type_cpte_preleve = $autorise_type_frais_prelev;
  }*/
  // fin ajout
  if (isset($type_cpte_preleve)) {
    $SESSION_VARS["frais_transfert"]['type_cpte_preleve'] = $type_cpte_preleve;
  }

  //ajouter
  /*if ($autorise_type_frais_montant !=null){
    $type_mnt_frais = $autorise_type_frais_montant;
  }*/
  //fin ajout
  if (isset($type_mnt_frais)) {
    $SESSION_VARS["frais_transfert"]['type_mnt_frais'] = $type_mnt_frais;
  }
  /*if($autorise_id_cpte_client_dest != null){
    $cpt_dest=$autorise_id_cpte_client_dest;
  }*/
  //On alimente les données pour l'affichage des informations du Bénéficiaire
  $cpteDest = NULL;
  if (isset($CpteMemeClient))
    $SESSION_VARS['CpteMemeClient'] = $CpteMemeClient;
  //Transfert sur un compte du client
  if ($SESSION_VARS['TypeTransfert'] == 1 && isset($CpteMemeClient))
  {
    if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
      $cpteDest = getAccountDatas($autorise_id_cpte_client_dest);
      $id_cpt_dest = $autorise_id_cpte_client_dest;
    }else{
      $cpteDest = getAccountDatas($SESSION_VARS["CpteMemeClient"]);
      $id_cpt_dest = get_id_compte($CpteMemeClient);
    }

    if (isset($id_cpt_dest)) {
      $cpteDest=getAccountDatas($id_cpt_dest);
      $SESSION_VARS['cpt_dest']=$id_cpt_dest;
      $SESSION_VARS['num_client']=$cpteDest['id_titulaire'];
      $SESSION_VARS["CpteMemeClient"] = $id_cpt_dest;
    }
  }

  elseif ($SESSION_VARS['TypeTransfert'] == 2 && isset($cpt_dest)) { //Transfert sur le compte d'un client de la banque
    if ($id_cpt_dest=='' || !isset($id_cpt_dest)) {

      if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
        $id_cpt_dest = $autorise_id_cpte_client_dest;
      }else{
        $id_cpt_dest = get_id_compte($cpt_dest);
      }

    }
    if (isset($id_cpt_dest)) {
      $cpteDest=getAccountDatas($id_cpt_dest);
      $SESSION_VARS['cpt_dest']=$id_cpt_dest;
      $SESSION_VARS['num_client']=$cpteDest['id_titulaire'];
    }
  } else if ($SESSION_VARS['TypeTransfert'] == 3) { // Transfert sur le compte du client d'une autre banque
    $benef = getTireurBenefDatas($SESSION_VARS['id_ben']);
    if ($benef['num_cpte'] != '')
      $cpteDest['num_complet_cpte'] = $benef['num_cpte'];
    else
      $cpteDest['num_complet_cpte'] = "IBAN ".$benef['iban_cpte'];

    //Récupérer la devise du compte
    $infosCorrespondant = getInfosCorrespondant($SESSION_VARS['id_correspondant']);
    $cpteDest['devise'] = $infosCorrespondant['devise'];
    $nomClientDest = $benef['denomination'];
  }
	$SESSION_VARS['cpteDest']=$cpteDest;
  /*********************************************************/
  // transfert grouper
  /*********************************************************/
  // On a identifié le client cible ou on a affaire à un transfert groupé
  if (isset($cpteDest) || $SESSION_VARS["TypeTransfert"] == 4) {
    // Recherche le nom du titulaire du compte destination (seulement si compte interne)
    if ($SESSION_VARS['TypeTransfert'] <= 2)
      $nomClientDest = getClientName($cpteDest['id_titulaire']);

    setMonnaieCourante($cpteSrc['devise']);

    $MANDATS = getListeMandatairesActifsV2($SESSION_VARS['NumCpteSource'],null,true);
    if ($MANDATS != NULL) {
      foreach($MANDATS as $key=>$value) {
        if ($value['limitation'] != NULL) {
          $JS_check .= "if (document.ADForm.HTML_GEN_LSB_mandat.value == $key
                       && recupMontant(document.ADForm.mnt.value) > ".$value['limitation'].")
                     {
                       msg += ' - "._("Le montant est supérieur à la limitation du donneur d ordre")."\\n';
                       ADFormValid=false;
                     }";
        }
        $MANDATS_LSB[$key] = $value['libelle'];
        if ($key == 'CONJ_id'){
          $MANDATS_LSB[$key] = $value['id'];
        }
        elseif ($key == 'CONJ') {
          $JS_open .= "if (document.ADForm.HTML_GEN_LSB_mandat.value == '$key')
                    {
                      OpenBrw('$SERVER_NAME/modules/externe/info_mandat.php?m_agc=".$_REQUEST['m_agc']."&id_cpte=".$SESSION_VARS['NumCpteSource']."');
                      return false;
                    }";
        } else {
          $JS_open .=  "if (document.ADForm.HTML_GEN_LSB_mandat.value == $key)
                     {
                       OpenBrw('$SERVER_NAME/modules/externe/info_mandat.php?m_agc=".$_REQUEST['m_agc']."&id_mandat=$key');
                       return false;
                     }";
        }
      }
    }

    $html->addField("mandat", _("Donneur d'ordre"), TYPC_LSB);
    $html->setFieldProperties("mandat", FIELDP_ADD_CHOICES, array("0" => _("Titulaire")));
    if ($MANDATS_LSB != NULL) {
      $MANDATS_LSB = array_flip($MANDATS_LSB); // array(valeur = >cle) au lieu de array(cle => valeur)
      //unset($MANDATS_LSB[getClientName($global_id_client)]); //on supprime le nom du titulaire dans la liste déroulante
      $MANDATS_LSB = array_flip($MANDATS_LSB); // on remet le array(cle => valeur)
      $LSB_MANDATS = $MANDATS_LSB;
      unset($LSB_MANDATS['CONJ_id']);
      $html->setFieldProperties("mandat", FIELDP_ADD_CHOICES, $LSB_MANDATS);
    }
    $html->setFieldProperties("mandat", FIELDP_HAS_CHOICE_AUCUN, false);
    $html->setFieldProperties("mandat", FIELDP_HAS_CHOICE_TOUS, false);
    $html->setFieldProperties("mandat", FIELDP_DEFAULT, $SESSION_VARS['id_mandat']);
    if (isset($_GET['id_dem'])){
      $html->setFieldProperties("mandat", FIELDP_IS_LABEL, true);
    }
    $html->addJS(JSP_BEGIN_CHECK, "limitation_check", $JS_check);
    $html->addLink("mandat", "afficher", _("Afficher"), "#");
    $html->setLinkProperties("afficher", LINKP_JS_EVENT, array("onclick" => $JS_open));

    $html->addHTMLExtraCode("mandat_sep","<br/>");

   /* if ($MANDATS_LSB != NULL) {
      $MANDATS_LSB = array_flip($MANDATS_LSB); // array(valeur = >cle) au lieu de array(cle => valeur)
      unset($MANDATS_LSB[getClientName($global_id_client)]); //on supprime le nom du titulaire dans la liste déroulante
      $MANDATS_LSB = array_flip($MANDATS_LSB); // on remet le array(cle => valeur)
      $LSB_MANDATS = $MANDATS_LSB;
      unset($LSB_MANDATS['CONJ_id']);
      $html->setFieldProperties("mandat", FIELDP_ADD_CHOICES, $LSB_MANDATS);
    }*/

    $SESSION_VARS['mandat'] = $MANDATS_LSB;

    $soldeDispo = getSoldeDisponible($SESSION_VARS['NumCpteSource']);
    $SESSION_VARS["soldeDispo"] = $soldeDispo;
    //Contrôle sur l'accès au solde
    $access_solde = get_profil_acces_solde($global_id_profil, $cpteSrc["id_prod"]);
    $access_solde_vip = get_profil_acces_solde_vip($global_id_profil, $global_id_client);
    if(manage_display_solde_access($access_solde, $access_solde_vip)){
    	$html->addField("soldeDispo", _("Solde disponible"), TYPC_MNT);
    	$html->setFieldProperties("soldeDispo",FIELDP_DEFAULT,$soldeDispo);
    }else{
    	$html->addHiddenType("soldeDispo", $soldeDispo);
    }

    $html->addField("num_complet_cpte", _("Numéro du compte source"), TYPC_TXT);
    $html->setFieldProperties("num_complet_cpte",FIELDP_DEFAULT,$cpteSrc['num_complet_cpte']);

    $html->addField("intitule_compte", _("Intitulé du compte"), TYPC_TXT);
    $html->setFieldProperties("intitule_compte",FIELDP_DEFAULT,$cpteSrc['intitule_compte']);

    $html->addHTMLExtraCode("ligne_sep","<br />");

    if(manage_display_solde_access($access_solde, $access_solde_vip))
    	$order = array("mandat", "mandat_sep", "num_complet_cpte", "intitule_compte", "soldeDispo");
    else
    	$order = array("mandat", "mandat_sep", "num_complet_cpte", "intitule_compte");

    // si c'est un transfert groupé
    if ($SESSION_VARS["TypeTransfert"] == 4) { // On va préciser l'ensemble des destinataires
      $SESSION_VARS['devise_source'] = $cpteSrc['devise'];
      // Si le compte source est le compte de prélèvement des frais de transfert
      if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 1) { // frais à prélever sur le compte source du transfert
        $html->addField("cptefrais", _("Compte de prélèvement frais"), TYPC_TXT);
        $html->setFieldProperties("cptefrais", FIELDP_IS_LABEL, true);
        $html->setFieldProperties("cptefrais",FIELDP_DEFAULT,$cpteSrc["num_complet_cpte"]);
        $devise_cpte_frais = $cpteSrc['devise']; //devise du compte de prélèvement des frais

        // On rend les frais paramétrés dans le produit du compte source
        $html->addTable("adsys_produit_epargne",OPER_INCLUDE, array("frais_transfert"));
        // montant par défaut des frais de transfert
        if (!isset($SESSION_VARS['frais_transfert']['mnt_frais']))
          $SESSION_VARS['frais_transfert']['mnt_frais'] = $cpteSrc["frais_transfert"];

        $html->setFieldProperties("frais_transfert",FIELDP_DEFAULT, $SESSION_VARS['frais_transfert']['mnt_frais']);
        $html->setFieldProperties("frais_transfert", FIELDP_DEVISE,$cpteSrc['devise']);
        $devise_frais = $cpteSrc['devise']; // devise des frais de transfert
        $html->setFieldProperties("frais_transfert", FIELDP_IS_REQUIRED, true);
        $html->setFieldProperties("frais_transfert", FIELDP_IS_LABEL, true);
        if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
          $html->setFieldProperties("frais_transfert", FIELDP_CAN_MODIFY, false); // frais modifiables
        }
        array_push($order,"cptefrais");
        array_push($order,"frais_transfert");
      }
// REL-76 : ajout ligne si le frais de non respect entre 2 retraits existent
      if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0){
        if ($infoRetraitAttente['commission_duree_2retrait'] > 0){
          $html->addField("comm_duree_2retrait", _("Commission sur non respect de la durée minimum entre deux retraits"), TYPC_MNT);
          $html->setFieldProperties("comm_duree_2retrait", FIELDP_IS_LABEL, true);
          $html->setFieldProperties("comm_duree_2retrait",FIELDP_DEFAULT,$infoRetraitAttente['commission_duree_2retrait']);
          array_push($order,"comm_duree_2retrait");
        }
      }

      //transfert groupé
      if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
        $data_client=getTransfertAttente($SESSION_VARS['id_dem'],$autorise_id_client_src,2);
        $groupe_client = $data_client['groupe_clients'];



        $xthtml = "";
        $xthtml .= "<TABLE align=\"center\" bgcolor=$colb_tableau border=$tableau_border cellspacing=$tableau_cellspacing cellpadding=$tableau_cellpadding>\n";

        // En-tête tableau
        $xthtml .= "<TR bgcolor=$colb_tableau><TD><b>n</b></TD><TD align=\"center\"><b>" . _("N client") . "</b></TD>"; //  Numéro client
        $xthtml .= "<TD align=\"center\"><b>" . _("Montant") . "</b></TD>";  // Montant
        if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 2) // si les frais sont prélevés dans les comptes de destination
          if ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) // si le monatnt des frais est celui paramétré dans le produit du compte source
            $xthtml .= "<TD align=\"center\"><b>" . _("Frais de transfert") . "</b></TD>";  // Montant des frais de transfert
        $xthtml .= "</TR>\n";

        $one_client = '';
        $one_trans = explode(",",$groupe_client);
        $i=1;
        foreach($one_trans as $one) {
          $one = trim($one);
          $one_client[$i] = $one;


          $one_data='';
          $one_data_explode = explode("-",$one_client[$i]);

          if (($one_data_explode[0] ) && $one_data_explode[1]) {
            $xthtml .= "<TR>";
            $xthtml .= "<TD><b>$i</b></TD>";
            $xthtml .= "<TD><INPUT TYPE=\"text\" NAME=\"num_client$i\" size=10 value=\"" . $one_data_explode[0] . "\" readonly >\n";
            $xthtml .= "<TD><INPUT NAME=\"mnt$i\" TYPE=\"text\" onchange=\"value = formateMontant(value);\" size=15 value=\"$one_data_explode[1]\" readonly >" . $SESSION_VARS['devise_source'] . "</TD>";
            // si les frais sont prélevés dans les comptes de destination
            if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 2)
              if ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) { // si monatnt frais est celui du produit du compte source
                // devise des frais est la devise du compte source du transfert
                $SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['devise_frais'] = $SESSION_VARS['devise_source'];

                // Si les frais non pas encore été affichés, prendre par défaut les frais du produit du compte source
                if (!isset($SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['mnt_frais']))
                  $SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['mnt_frais'] = recupMontant($cpteSrc["frais_transfert"]);

                // Montant des frais de transfert
                $mnt_frais = $SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['mnt_frais'];
                if (check_access(299)) // si frais modifiable
                  $xthtml .= "<TD><INPUT NAME=\"frais$i\" TYPE=\"text\" size=15 value=\"$mnt_frais\"
                         onchange=\"value = formateMontant(value);\">" . $SESSION_VARS['devise_source'] . "</TD>";
                else // frais non modifiable
                  $xthtml .= "<TD><INPUT NAME=\"frais$i\" TYPE=\"text\" size=15 value=\"$mnt_frais\"
                         onchange=\"value = formateMontant(value);\" disabled=true>" . $SESSION_VARS['devise_source'] . "</TD>";
              } elseif ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) { // frais sont issus des produits des compte destination
                // On ne connait pas les montant car les comptes ne sont pas encore choisis
              }
            $xthtml .= "</TR>";
          }

          $i++;
        }


      //$xthtml .= "<TR bgcolor=$colb_tableau><TD colspan=5 align=\"center\">\n";
      $xthtml .= "</TABLE>\n";
      $html->addHTMLExtraCode("code_table_new", $xthtml);


    }

      else {
        $xthtml = "";
        $xthtml .= "<TABLE align=\"center\" bgcolor=$colb_tableau border=$tableau_border cellspacing=$tableau_cellspacing cellpadding=$tableau_cellpadding>\n";

        // En-tête tableau
        $xthtml .= "<TR bgcolor=$colb_tableau><TD><b>n</b></TD><TD align=\"center\"><b>" . _("N client") . "</b></TD>"; //  Numéro client
        $xthtml .= "<TD align=\"center\"><b>" . _("Montant") . "</b></TD>";  // Montant
        if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 2) // si les frais sont prélevés dans les comptes de destination
          if ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) // si le monatnt des frais est celui paramétré dans le produit du compte source
            $xthtml .= "<TD align=\"center\"><b>" . _("Frais de transfert") . "</b></TD>";  // Montant des frais de transfert
        $xthtml .= "</TR>\n";

        $nb = 40; // nombre de lignes
        for ($i = 1; $i <= $nb; ++$i) {
          //On alterne la couleur de fond
          if ($i % 2)
            $color = $colb_tableau;
          else
            $color = $colb_tableau_altern;

          $xthtml .= "<TR bgcolor=$color>\n";
          $xthtml .= "<TD><b>$i</b></TD>";  //numéro ligne

          // Compte de destination
          $num_client = $SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['num_client'];
          $mnt = afficheMontant($SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['mnt']);

          $xthtml .= "<TD><INPUT TYPE=\"text\" NAME=\"num_client$i\" size=10 value=\"" . $num_client . "\">\n";
          $xthtml .= "<FONT size=\"2\"><A href=# onclick=\"OpenBrw('../modules/clients/rech_client.php?m_agc=" . $_REQUEST['m_agc'] . "&field_name=num_client$i', '" . _("Recherche") . "');return false;\">" . _("Recherche") . "</A></FONT></TD>\n";

          //Montant du transfert dans la devise du compte source
          $xthtml .= "<TD><INPUT NAME=\"mnt$i\" TYPE=\"text\" onchange=\"value = formateMontant(value);\" size=15 value=\"$mnt\">" . $SESSION_VARS['devise_source'] . "</TD>";

          // si les frais sont prélevés dans les comptes de destination
          if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 2)
            if ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) { // si monatnt frais est celui du produit du compte source
              // devise des frais est la devise du compte source du transfert
              $SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['devise_frais'] = $SESSION_VARS['devise_source'];

              // Si les frais non pas encore été affichés, prendre par défaut les frais du produit du compte source
              if (!isset($SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['mnt_frais']))
                $SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['mnt_frais'] = recupMontant($cpteSrc["frais_transfert"]);

              // Montant des frais de transfert
              $mnt_frais = $SESSION_VARS['frais_transfert']['transfert_groupe'][$i]['mnt_frais'];
              if (check_access(299)) // si frais modifiable
                $xthtml .= "<TD><INPUT NAME=\"frais$i\" TYPE=\"text\" size=15 value=\"$mnt_frais\"
                         onchange=\"value = formateMontant(value);\">" . $SESSION_VARS['devise_source'] . "</TD>";
              else // frais non modifiable
                $xthtml .= "<TD><INPUT NAME=\"frais$i\" TYPE=\"text\" size=15 value=\"$mnt_frais\"
                         onchange=\"value = formateMontant(value);\" disabled=true>" . $SESSION_VARS['devise_source'] . "</TD>";
            } elseif ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) { // frais sont issus des produits des compte destination
              // On ne connait pas les montant car les comptes ne sont pas encore choisis
            }

          $xthtml .= "</TR>\n";

        }

        //$xthtml .= "<TR bgcolor=$colb_tableau><TD colspan=5 align=\"center\">\n";
        $xthtml .= "</TABLE>\n";

        //Script check
        $xthtml .= "<script type=\"text/javascript\">\n";
        $xthtml .= "function checkForm(){";
        $xthtml .= "msg = '';\n";

        for ($i = 1; $i <= $nb; ++$i) {
          //$xthtml .= "  if ((! isDate(document.ADForm.date$i.value)) && (document.ADForm.date$i.value != '')){ ADFormValid = false; msg += 'La date de la ligne n$i est incorrecte !\\n';}";
          $xthtml .= "  if ((! isIntPos(document.ADForm.num_client$i.value)) && (document.ADForm.num_client$i.value != '')){ ADFormValid = false; msg += '" . sprintf(_("Le format du numéro client de la ligne n°%s est incorrecte !"), $i) . "\\n';}";
        }

        $xthtml .= "if (msg != '') alert(msg);";
        $xthtml .= "}";
        $xthtml .= "</script>\n";

        $html->addHTMLExtraCode("code_table", $xthtml);

        $extraline = "<br />";
        $html->addHTMLExtraCode("extraline", $extraline);

        array_push($order, "extraline");
      }
      if(manage_display_solde_access($access_solde, $access_solde_vip))
      	$label = array("num_complet_cpte", "intitule_compte", "soldeDispo");
      else
      	$label = array("num_complet_cpte", "intitule_compte");
      foreach($label as $value)
      $html->setFieldProperties($value, FIELDP_IS_LABEL, true);

    } else { // Il n'y a qu'un seul destinataire
      $html->addField("num_cpte_dest", _("Numéro du compte destination"), TYPC_TXT);
      $html->setFieldProperties("num_cpte_dest",FIELDP_DEFAULT,$cpteDest['num_complet_cpte']);

			if(manage_display_solde_access($access_solde, $access_solde_vip)){
				$fieldslabel = array("num_complet_cpte", "intitule_compte", "soldeDispo","num_cpte_dest");
      	$order = array("mandat", "mandat_sep", "num_complet_cpte", "intitule_compte", "soldeDispo", "ligne_sep", "num_cpte_dest");
			}else{
				$fieldslabel = array("num_complet_cpte", "intitule_compte", "num_cpte_dest");
      	$order = array("mandat", "mandat_sep", "num_complet_cpte", "intitule_compte", "ligne_sep", "num_cpte_dest");
			}


      if ($SESSION_VARS['TypeTransfert'] !=3) { // Si c'est pas un transfert externe
        $html->addField("intitule_cpte_dest", _("Intitulé du compte"), TYPC_TXT);
        $html->setFieldProperties("intitule_cpte_dest",FIELDP_DEFAULT,$cpteDest['intitule_compte']);
        array_push($fieldslabel, "intitule_cpte_dest");
        array_push($order, "intitule_cpte_dest");
      }

      $html->addField("titulaire_cpte_dest", _("Titulaire du compte"), TYPC_TXT);
      $html->setFieldProperties("titulaire_cpte_dest",FIELDP_DEFAULT,$nomClientDest);
      array_push($fieldslabel, "titulaire_cpte_dest");

      $html->addHTMLExtraCode("ligne_sep2","<br />");
      array_push($order, "titulaire_cpte_dest",  "ligne_sep2");

      foreach($fieldslabel as $value) {
        $html->setFieldProperties($value, FIELDP_IS_LABEL, true);
        $html->setFieldProperties($value, FIELDP_IS_REQUIRED, false);
      }

      $html->addField("mnt",_("Montant à transférer"),TYPC_MNT);
      $html->setFieldProperties("mnt", FIELDP_IS_REQUIRED, true);
      $ChkJS = "if (recupMontant(document.ADForm.mnt.value) > recupMontant(document.ADForm.soldeDispo.value))
             {
               msg += '- "._("Le montant est supérieur au solde disponible")."\\n';
               ADFormValid=false;
             };";

      $html->addJS(JSP_BEGIN_CHECK, "JS2",$ChkJS);
      array_push($order, "mnt");

      if ($cpteSrc['devise']!=$cpteDest['devise']) {
        $html->addField("mntDevise", _("Montant à transférer dans la devise du compte destination"), TYPC_MNT);
        $html->setFieldProperties("mntDevise", FIELDP_DEVISE,$cpteDest['devise']);
        $html->setFieldProperties("mntDevise", FIELDP_IS_REQUIRED, true);
        $html->addLink("mntDevise","changeDevise",_("changer"),"#");
        $html->setLinkProperties("changeDevise",LINKP_JS_EVENT,array("onclick"=>"MONTANT_LIE_mntDevise_popup();"));
        $html->setFieldProperties("mnt", FIELDP_JS_EVENT, array("onfocus"=>"document.ADForm.mntDevise.value='';document.ADForm.MONTANT_LIE_mntDevise_comm_nette.value='';document.ADForm.MONTANT_LIE_mntDevise_taux.value='';document.ADForm.MONTANT_LIE_mntDevise_dest_reste.value='';","onchange"=>"document.ADForm.mnt.value=formateMontant(document.ADForm.mnt.value);"));
        $html->setFieldProperties("mntDevise", FIELDP_JS_EVENT, array("onfocus"=>"document.ADForm.mnt.value='';document.ADForm.MONTANT_LIE_mntDevise_comm_nette.value='';document.ADForm.MONTANT_LIE_mntDevise_taux.value='';document.ADForm.MONTANT_LIE_mntDevise_dest_reste.value='';","onchange"=>"document.ADForm.mntDevise.value=formateMontant(document.ADForm.mntDevise.value);"));
        $ChkJSChange = "
                       function MONTANT_LIE_mntDevise_popup()
                     {
                       if (document.ADForm.mnt.value!='' || document.ADForm.mntDevise.value!='')
                       open_change(document.ADForm.mnt.value,'".$cpteSrc['devise']."',document.ADForm.mntDevise.value,'".$cpteDest['devise']."','mnt','mntDevise','MONTANT_LIE_mntDevise_comm_nette','MONTANT_LIE_mntDevise_taux','','MONTANT_LIE_mntDevise_dest_reste','vente', 2);
                     };
                       ";
        $html->addJS(JSP_FORM, "JS3", $ChkJSChange);
        $html->addHiddenType("MONTANT_LIE_mntDevise",$cpteDest['devise']);
        $html->addHiddenType("MONTANT_LIE_mntDevise_comm_nette");
        $html->addHiddenType("MONTANT_LIE_mntDevise_taux");
        $html->addHiddenType("MONTANT_LIE_mntDevise_dest_reste");
        array_push($order, "mntDevise");
      }

      // Compte de prélèvement des frais de transfert
      $html->addField("cptefrais", _("Compte de prélèvement frais"), TYPC_TXT);
      $html->setFieldProperties("cptefrais", FIELDP_IS_LABEL, true);
      if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 1) { // frais à prélever sur le compte source du transfert
        $html->setFieldProperties("cptefrais",FIELDP_DEFAULT, $cpteSrc["num_complet_cpte"]);
        $SESSION_VARS['frais_transfert']['cpte_preleve'] = $cpteSrc["num_complet_cpte"]; // compte de prélèvement
        $SESSION_VARS['frais_transfert']['devise_cpte_frais'] = $cpteSrc['devise']; //devise du compte de prélèvement des frais
      }
      if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 2) { // frais à prélever sur le compte de destination du transfert
        $html->setFieldProperties("cptefrais",FIELDP_DEFAULT,$cpteDest["num_complet_cpte"]);
        $SESSION_VARS['frais_transfert']['cpte_preleve'] = $cpteDest["num_complet_cpte"]; // compte de prélèvement
        $SESSION_VARS['frais_transfert']['devise_cpte_frais'] = $cpteDest['devise']; //devise du compte de prélèvement des frais
      }
      array_push($order,"cptefrais");

      $html->addTable("adsys_produit_epargne",OPER_INCLUDE, array("frais_transfert"));
      // Récupération du montant des frais de transfert
      if ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) { // frais paramétrés dans produit du compte source
        if (!isset($SESSION_VARS['frais_transfert']['mnt_frais']))
          $SESSION_VARS['frais_transfert']['mnt_frais'] = $cpteSrc["frais_transfert"]; // initialisation frais de transfert

        $html->setFieldProperties("frais_transfert",FIELDP_DEFAULT, $SESSION_VARS['frais_transfert']['mnt_frais']);
        $html->setFieldProperties("frais_transfert", FIELDP_DEVISE,$cpteSrc['devise']);
        $SESSION_VARS['frais_transfert']['devise_frais'] = $cpteSrc['devise']; // devise des frais de transfert
      }
      elseif($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 2) { // frais paramétrés dans produit du compte de destination
        if (!isset($SESSION_VARS['frais_transfert']['mnt_frais']))
          $SESSION_VARS['frais_transfert']['mnt_frais'] = $cpteDest["frais_transfert"]; // initialisation frais de transfert

        $html->setFieldProperties("frais_transfert",FIELDP_DEFAULT, $SESSION_VARS['frais_transfert']['mnt_frais']);
        $html->setFieldProperties("frais_transfert", FIELDP_DEVISE, $cpteDest['devise']);
        $SESSION_VARS['frais_transfert']['devise_frais'] = $cpteDest['devise']; // devise des frais de transfert
      }

      $html->setFieldProperties("frais_transfert", FIELDP_IS_REQUIRED, true);
      $html->setFieldProperties("frais_transfert", FIELDP_IS_LABEL, true);
      if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
        $html->setFieldProperties("frais_transfert", FIELDP_CAN_MODIFY, false); // frais modifiables
      }else {
        $html->setFieldProperties("frais_transfert", FIELDP_CAN_MODIFY, true); // frais modifiables
      }
      array_push($order,"frais_transfert");

      // REL-76 : ajout ligne si le frais de non respect entre 2 retraits existent
      if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0){
        if ($infoRetraitAttente['commission_duree_2retrait'] > 0){
          $html->addField("comm_duree_2retrait", _("Commission sur non respect de la durée minimum entre deux retraits"), TYPC_MNT);
          $html->setFieldProperties("comm_duree_2retrait", FIELDP_IS_LABEL, true);
          $html->setFieldProperties("comm_duree_2retrait",FIELDP_DEFAULT,$infoRetraitAttente['commission_duree_2retrait']);
          array_push($order,"comm_duree_2retrait");
        }
      }

      //Si la devise des frais n'est pas la même que la devise du compte de prélèvement, calcule frais dans devise compte de prélève
      if ($SESSION_VARS['frais_transfert']['devise_frais'] != $SESSION_VARS['frais_transfert']['devise_cpte_frais']) {
        $html->addField("frais_transfert_cv", _("Montant frais dans la devise du compte de prélèvement"), TYPC_MNT);
        $html->setFieldProperties("frais_transfert_cv", FIELDP_DEVISE, $SESSION_VARS['frais_transfert']['devise_cpte_frais']);

        $tmp = calculeCV($SESSION_VARS['frais_transfert']['devise_frais'],
                         $SESSION_VARS['frais_transfert']['devise_cpte_frais'],$SESSION_VARS['frais_transfert']['mnt_frais']);

        $SESSION_VARS['frais_transfert']['mnt_frais_cv'] = $tmp;
        $html->setFieldProperties("frais_transfert_cv",FIELDP_DEFAULT, $tmp);
        if (!check_access(299))
          $html->setFieldProperties("frais_transfert_cv", FIELDP_IS_LABEL, true);
        $html->setFieldProperties("frais_transfert_cv", FIELDP_IS_REQUIRED, true);

        if (check_access(299)) {
          $html->addLink("frais_transfert_cv","changeFrais",_("changer"),"#");
          $html->setLinkProperties("changeFrais",LINKP_JS_EVENT,array("onclick"=>"calculCvFrais();"));
        }

        $html->setFieldProperties("frais_transfert_cv",FIELDP_JS_EVENT,array("onfocus"=>"document.ADForm.frais_transfert.value=''"));
        array_push($order,"frais_transfert_cv");
        $html->setFieldProperties("frais_transfert",FIELDP_JS_EVENT,array("onfocus"=>"document.ADForm.frais_transfert_cv.value=''"));

        $html->addHiddenType("comm_nette");
        $html->addHiddenType("taux");
        $html->addHiddenType("reste");
        $html->addHiddenType("dest_reste");

        $ChkJSChange = "
                       function calculCvFrais()
                     {
                       if (document.ADForm.frais_transfert.value !='' || document.ADForm.frais_transfert_cv.value !='') {
                       var mnt_frais;
                       mnt_frais = recupMontant(document.ADForm.frais_transfert.value);
                       if(recupMontant(document.ADForm.frais_transfert.value) > 0 || recupMontant(document.ADForm.frais_transfert_cv.value))
                       open_change(document.ADForm.frais_transfert.value,'". $SESSION_VARS['frais_transfert']['devise_frais']."',document.ADForm.frais_transfert_cv.value,'". $SESSION_VARS['frais_transfert']['devise_cpte_frais']."','frais_transfert','frais_transfert_cv','comm_nette','taux','reste','dest_reste','vente', 2); }
                     };
                       ";
        $html->addJS(JSP_FORM, "frais_cv", $ChkJSChange);
      } // fin si devse frais != devise compte de prélèvement des frais

    } // fin else Il n'y a qu'un seul destinataire

    //Coordonnées de la pièce justificative en cas de virement vers un compte différent du sien (interne/externe)
    if ($SESSION_VARS['TypeTransfert'] <= 3) {

      $listeChequeInterne = ChequeCertifie::getChequeCertifieClient($SESSION_VARS['NumCpteSource'],ChequeCertifie::ETAT_CHEQUE_CERTIFIE_NON_TRAITE);
      $chqValidite = getValidityChequeDate();
      $chqOrdVal = $chqValidite['validite_chq_ord'];
      $chqCertVal = $chqValidite['validite_chq_cert'];
      $ordpayVal = $chqValidite['validite_ord_pay'];

      $html->addHTMLExtraCode("ligne_sep3","<br />");

      $html->addTable("ad_his_ext", OPER_INCLUDE, array("type_piece"));
      if (isset($_GET['id_dem'])){
        $html->setFieldProperties("type_piece", FIELDP_INCLUDE_CHOICES, array($autorise_type_piece_justif));
        //$html->setFieldProperties("type_piece", FIELDP_JS_EVENT, array("onChange"=>"chequeValidation();"));
        $html->setFieldProperties("type_piece", FIELDP_IS_LABEL, true);
      }else{
        $html->setFieldProperties("type_piece", FIELDP_INCLUDE_CHOICES, array(3,15));
        $html->setFieldProperties("type_piece", FIELDP_JS_EVENT, array("onChange"=>"chequeValidation();"));
        $html->setFieldProperties("type_piece", FIELDP_IS_REQUIRED, true);

      }


      $html->addField("num_chq", _("Numéro du chèque / virement <span id='ValidnumChq' style='display:none'><font color='#FF0000' face='HELVETICA' size='4'><b>*</b></font></span>"), TYPC_TXT);

      $html->addField("date_chq", _("Date du chèque / virement"), TYPC_DTE);

      //Ajout du libelle obligatoire(*) si c'est une transaction de chèque
      $chkValid = "\n
                    function findAndReplace(searchText, replacement, searchNode) {
                        if (!searchText || typeof replacement === 'undefined') {
                            // Throw error here if you want...
                            return;
                        }
                        var regex = typeof searchText === 'string' ?
                                    new RegExp(searchText, 'g') : searchText,
                            childNodes = (searchNode || document.body).childNodes,
                            cnLength = childNodes.length,
                            excludes = 'html,head,style,title,link,meta,script,object,iframe';
                        while (cnLength--) {
                            var currentNode = childNodes[cnLength];
                            if (currentNode.nodeType === 1 &&
                                (excludes + ',').indexOf(currentNode.nodeName.toLowerCase() + ',') === -1) {
                                arguments.callee(searchText, replacement, currentNode);
                            }
                            if (currentNode.nodeType !== 3 || !regex.test(currentNode.data) ) {
                                continue;
                            }
                            var parent = currentNode.parentNode,
                                frag = (function(){
                                    var html = currentNode.data.replace(regex, replacement),
                                        wrap = document.createElement('div'),
                                        frag = document.createDocumentFragment();
                                    wrap.innerHTML = html;
                                    while (wrap.firstChild) {
                                        frag.appendChild(wrap.firstChild);
                                    }
                                    return frag;
                                })();
                            parent.insertBefore(frag, currentNode);
                            parent.removeChild(currentNode);
                        }
                    }
                    findAndReplace('Date du chèque / virement', 'Date du chèque / virement <span id=\'ValiddateChq\' style=\'display:none\'><font color=\'#FF0000\' face=\'HELVETICA\' size=\'4\'><b>*</b></font></span>');

                    function chequeValidation()
                    {
                      var numvalidchq = document.getElementById('ValidnumChq');
                      var datevalidchq = document.getElementById('ValiddateChq');

                      numvalidchq.style.display = 'none';
                      datevalidchq.style.display = 'none';

                      if(document.ADForm.HTML_GEN_LSB_type_piece.value == 15 )
                        {
                         numvalidchq.style.display = 'inline';
                         datevalidchq.style.display = 'inline';

                        }

                    }\n";

      $html->addJS(JSP_FORM, "JSvalChq", $chkValid);

      //Verification si le numéro et la date du chèque sont renseigné
      $ChkJS .="\n";
      $ChkJS .="
                var num_chq = document.ADForm.num_chq;
                var date_chq = document.ADForm.HTML_GEN_date_date_chq;
                var type_piece = document.ADForm.HTML_GEN_LSB_type_piece;

                if(type_piece.value == 15 )
                {

                   if (date_chq.value == '')
                    {
                      msg += '- "._("La date du chèque n\'est pas renseignée")."\\n';
                      ADFormValid=false;
                    }

                    if (num_chq.value == '')
                    {
                      msg += '- "._("La numéro du chèque n\'est pas renseignée")."\\n';
                      ADFormValid=false;
                    }
                }\n";

      // Validation date validité chèques
      $ChkJS .= "\n var etatChqArray = [\n";

      foreach($listeChequeInterne as $key=>$value)
      {
        $ChkJS .= "{ num_chq: $key, date_chq: '". pg2phpDate($value['date_cheque']) ."', mnt_chq: ". recupMontant($value['montant']) ." },\n";
      }
      $ChkJS .= "];\n ";

      $ChkJS .= "function lookupChqArray( index , arr, output)
                 {
                     for(var i = 0, len = arr.length; i < len; i++)
                     {
                         if( arr[ i ].num_chq == index )
                         {
                            if (output == 'date')
                            {
                                return arr[ i ].date_chq;
                            }
                            else if (output == 'mnt')
                            {
                                return arr[ i ].mnt_chq;
                            }
                            else
                            {
                                return true;
                            }
                         }
                     }
                     return false;
                 };\n ";

      $ChkJS .= "
                  function validChqCertifie(){
                    var num_chq = document.ADForm.num_chq;
                    var mnt_chq = document.ADForm.mnt;
                    var date_chq = document.ADForm.HTML_GEN_date_date_chq;
                    var type_piece = document.ADForm.HTML_GEN_LSB_type_piece;

                      if(type_piece.value == 15 && num_chq.value != '' && date_chq.value != '' )
                      {
                          var now = \"" . date("d/m/Y") . "\"
                          var isChqCert = lookupChqArray(num_chq.value, etatChqArray, 0);
                          var isValid = checkDateRange(isChqCert?$chqCertVal:$chqOrdVal, date_chq.value, now);

                          if(!isValid)
                          {
                            msg += ' - La validité du chèque dépasse le nombre de jours autorisé !\\n';
                            ADFormValid=false;
                          }
                          else
                          {
                            if(isChqCert)
                            {
                              if(lookupChqArray(num_chq.value, etatChqArray, 'date') != date_chq.value)
                              {
                                msg += ' - La Date du chèque saisie ne correspond pas à la date chèque certifié !\\n';
                                ADFormValid=false;
                              }
                              else if(mnt_chq.value != '' && lookupChqArray(num_chq.value, etatChqArray, 'mnt') != recupMontant(mnt_chq.value))
                              {
                                msg += ' - Le Montant du chèque saisie ne correspond pas au montant du chèque certifié !\\n';
                                ADFormValid=false;
                              }
                            }
                          }
                      }

                     if (type_piece.value == 3 && num_chq.value != '' && date_chq.value != '')
                      {
                        var now = \"" . date("d/m/Y") . "\";
                        var isValid = checkDateRange($ordpayVal, date_chq.value, now);

                          if(!isValid)
                          {
                            msg += ' - La validité de l\'ordre de paiement dépasse le nombre de jours autorisé !\\n';
                            ADFormValid=false;
                          }
                      }
                  }";

      $ChkJS .= "\n validChqCertifie(); \n";

      $html->addJS(JSP_BEGIN_CHECK, "jscq", $ChkJS);

      array_push($order, "ligne_sep3", "type_piece", "num_chq", "date_chq");
    }

    $html->addField("communication", _("Communication"), TYPC_TXT);
    if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
      $html->setFieldProperties("communication", FIELDP_DEFAULT, $autorise_communication);
      $html->setFieldProperties("communication", FIELDP_IS_LABEL, true);
    }

    $html->addField("remarque", _("Remarque"), TYPC_ARE);
    if (isset($_GET['id_dem']) && $_GET['id_dem'] > 0) {
      $html->setFieldProperties("remarque", FIELDP_DEFAULT,$autorise_remarque);
      $html->setFieldProperties("remarque", FIELDP_IS_LABEL, true);
    }

    $html->addHTMLExtraCode("ligne_sep4","<br />");

    array_push($order, "communication", "remarque", "ligne_sep4");

    $html->setOrder(NULL,$order);

    $html->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
    $html->addFormButton(1, 2, "retour", _("Précédent"), TYPB_SUBMIT);
    $html->addFormButton(1, 3, "cancel", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Tcp-4');
    if ($id_dem !=null){
      $html->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN, 'Pdt-1');
    }else {
      $html->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN, 'Tcp-2');
    }
    $html->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-10');
    $html->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);
    $html->setFormButtonProperties("retour", BUTP_CHECK_FORM, false);

    $html->buildHTML();
    echo $html->getHTML();
  } else {
    $html_err = new HTML_erreur(_("Echec de la recherche"));
    $html_err->setMessage(_("Erreur : le numéro de compte ne correspond à aucun client"));
    $html_err->addButton("BUTTON_OK", 'Tcp-2');
    $html_err->buildHTML();
    echo $html_err->HTML_code;
  }
}
//---------------------------------------------------------------------
//----------- Tcp-4 Confirmation du montant ---------------------------
//---------------------------------------------------------------------
else if ($global_nom_ecran == "Tcp-4") {
  // Récupération des frais s'ils ont été modifiés
  if (isset($frais_transfert)) // frais dans la devise du compte source du transfert
    $SESSION_VARS['frais_transfert']['mnt_frais'] = recupMontant($frais_transfert);
  if (isset($frais_transfert_cv)) // éventuels frais dans la devise du compte de prélèvment des frais
    $SESSION_VARS['frais_transfert']['mnt_frais_cv'] = recupMontant($frais_transfert_cv);

  // Récupération mandat, communication et remarque
  if ($mandat != 0 and $mandat != 'CONJ') {
    $SESSION_VARS['id_mandat'] = $mandat;
  }
  else {
    $SESSION_VARS['id_mandat'] = $mandat;
  }
  if (isset($communication))
    $SESSION_VARS['communication'] = $communication;
  if (isset($remarque))
    $SESSION_VARS['remarque'] = $remarque;

  // REL-85: Ajout verification limitation retrait pour le mandataire choisit
  $Liste_mandataires = getListeMandatairesActifsV2($SESSION_VARS["NumCpteSource"],null,true);
  $retrait_impossible = false;
  if (isset($mandat)){
    if ($mandat > 0){//Type seule
      if (isset($Liste_mandataires[$mandat]) && $Liste_mandataires[$mandat] != null && $Liste_mandataires[$mandat]['limitation'] > 0 && $Liste_mandataires[$mandat]['limitation'] !=null){
        if (recupMontant($Liste_mandataires[$mandat]['limitation']) < recupMontant($mnt)){ //si le montant à retirer est superieure au limit de retrait pour ce mandataire
          $retrait_impossible = true;
          $titre = "Transfert impossible pour le mandataire (".$Liste_mandataires[$mandat]['libelle'].") de type seule";
        }
      }
    }
    if ($mandat == 'CONJ'){ //Type conjointe
      if (isset($Liste_mandataires[$mandat]) && $Liste_mandataires[$mandat] != null){
        $liste_mandats = getMandats($SESSION_VARS['NumCpteSource']);
        $liste_CONJ_id = explode('-',$SESSION_VARS['mandat']['CONJ_id']);
        $mnt_limite = 0;
        $limitation = 0;
        foreach ($liste_CONJ_id as $conj_id => $value) {
          if ($value != null){
            $mnt_limite = recupMontant($liste_mandats[$value]['limitation']);
            if ($mnt_limite != null && $mnt_limite != 0) {
              if ($limitation == 0) {
                $limitation = $mnt_limite;
              }
              $limitation = min($limitation, $mnt_limite); // Si on a plusieurs mandataires conjointe on prend le minimum des montants limitation
            }
          }
        }
        if ($limitation != null && $limitation != 0) {
          if ($limitation < recupMontant($mnt)) { //si le montant à retirer est superieure au limit de retrait pour ce mandataire
            $retrait_impossible = true;
            $titre = "Transfert impossible pour le(s) mandataire(s) (" . $Liste_mandataires[$mandat]['libelle'] . ") de type conjointe";
            $mnt_conj_limite = "(" . number_format($limitation, 0, '.', ' ') . ")";
          }
        }
      }
    }
    if ($retrait_impossible){
      $msg = "Le montant ($mnt) à retirer est supérieure au limite $mnt_conj_limite de transfert!! Veuillez cliquer le bouton OK pour re-saisir le montant sur l'ecran precedent!";
      $html_err = new HTML_erreur($titre);
      $html_err->setMessage($msg);
      $html_err->addButton("BUTTON_OK", "Gen-10");
      $html_err->buildHTML();
      echo $html_err->HTML_code;
      exit;
    }
  }



  /*******************Verification si la duree minimum entre 2 retraits > 0 et si respect ou pas du delai*******************/
  if ($SESSION_VARS['TypeTransfert'] == 4 || $SESSION_VARS['TypeTransfert'] == 3){
    $data_cpt = getAccountDatas($SESSION_VARS['NumCpteSource']);
    $dure_minimum_retrait = $data_cpt["duree_min_retrait_jour"];
  }else{
    $dure_minimum_retrait = $SESSION_VARS["cpteDest"]["duree_min_retrait_jour"];
  }

  /*********************************Type transfert = 4**************************************************************************/
  if ($SESSION_VARS["TypeTransfert"] == 4) {
    $j = 1;
    $mnt_total = 0;
    $erreurMsg = "";
    $SESSION_VARS["DATA"] = array();

    // Contenu du tableau
    while ($j <= 40) {
      if ($ {"num_client$j"} != '') { // si au moins le numéro du client est renseigné
        $id_client = $ {"num_client$j"}; // numéro du client
        if (client_exist($id_client) and client_actif($id_client)) { // si le client existe et est actif
          $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['num_client'] = $id_client;

          $nom_client = getClientName($id_client);
          // Récupération des infos du compte de base et du produit associé
          $id_cpte = getBaseAccountID($id_client);
          $ACC = getAccountDatas($id_cpte);
          if ($ACC["etat_cpte"] != 1)
            $erreurMsg .= sprintf(_("Le client %s n'a pas de compte de base ouvert !"),$id_client)."<br/>";
          $num_complet_cpte = $ACC["num_complet_cpte"];

          // Montant à transferer dans la devise du compte source
          $mnt = recupMontant($ {"mnt$j"});
          $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['mnt'] = $mnt;
          if ($mnt > 0) {
            $mnt_src = $mnt; // montant prélevé dans le compte source dans sa devise
            $mnt_dest = $mnt; // montant déposé dans le compte de destination dans sa devise
            // Si la devise du compte destination n'est pas la même que celle du compte source, calculer la cv
            if ($ACC['devise'] != $SESSION_VARS['devise_source'])
              $mnt_dest = calculeCV($SESSION_VARS['devise_source'], $ACC['devise'], $mnt); // montant déposé

            $mnt_total += $mnt; // Total montant prélevé dans le compte source dans sa devise

            // si les frais sont prélevés dans les comptes de destination
            if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 2) {
              if ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) { // si les frais sont du produit du compte source
                // récupération des frais éventuellement modifiés
                if (isset($ {"frais$j"})) {
                  $mnt_frais = recupMontant($ {"frais$j"});
                  $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['mnt_frais'] = $mnt_frais;
                }
                else
                  $mnt_frais = $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['mnt_frais'];

                // si la devise des frais n'est pas la même que celle du compte de destination
                if ($ACC['devise'] != $SESSION_VARS['devise_source'])
                  $mnt_frais = calculeCV($SESSION_VARS['devise_source'], $ACC['devise'], $mnt_frais);

                // Vérifier que le solde disponible du compte et le montant du tranfert lui permet de payer les frais
                $solde_dispo = getSoldeDisponible($id_cpte);
                if (($mnt_dest + $solde_dispo) < $mnt_frais)
                  $erreurMsg .= sprintf(_("Le solde disponible et le montant du transfert pour le client %s ne peut pas payer les frais !"),$id_client)."<br/>";
              }
              elseif($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 2) // frais sont du produit du compte destination
                $mnt_frais = $ACC['frais_transfert'];
            }

          } else
            $erreurMsg .= sprintf(_("Le montant pour le client %s doit etre positif"),$id_client)."<br />";
        } // fin si client existe et actif
        else
          $erreurMsg .= sprintf(_("Le client %s n'existe pas ou n'est pas actif"),$id_client)."<br/>";
      } // fin si numéro client saisi

      $j++;
    } // fin boucle while


    // Enregistrement du montant total dans la devise du compte source
    $SESSION_VARS["mnt_total"] = $mnt_total;


    // Si le compte source paie les frais, ajouter les frais dans le montant à prélever dans le compte source
    if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 1) {
      if ($SESSION_VARS['frais_transfert']['mnt_frais_cv'] > 0) // si les frais n'étaient de la même devise que le compte source
        $mnt_total += $SESSION_VARS['frais_transfert']['mnt_frais_cv'];
      elseif($SESSION_VARS['frais_transfert']['mnt_frais'] > 0)
        $mnt_total += $SESSION_VARS['frais_transfert']['mnt_frais'];
    }

    // Vérifier que le solde disponible du compte source permet le transfert
    if ($mnt_total > $SESSION_VARS["soldeDispo"])
      $erreurMsg .= sprintf(_("Le solde disponible dans le compte ne permet pas un transfert de %s"),afficheMontant($mnt_total, true));

    // Si une erreur a été rencontrée dans le traitement du transfert groupé, annuler le traitement
    if ($erreurMsg != '') {
      $html_err = new HTML_erreur(_("Erreur"));
      $html_err->setMessage($erreurMsg);
      $html_err->addButton("BUTTON_OK", 'Tcp-3');
      $html_err->buildHTML();
      echo $html_err->HTML_code;
      die();
    }
  }
  if (!isset($SESSION_VARS["mnt_total1"])){
    //Variable pour sauvegarder le mnt total apres reload de la page si il y a non respect du delai minimum entre 2 retrait = REL-76
    $SESSION_VARS["mnt_total1"]= $mnt_total;
  }

  // REL-85: Ajout verification limitation retrait pour le mandataire choisit
  $Liste_mandataires = getListeMandatairesActifsV2($SESSION_VARS["NumCpteSource"],null,true);
  $retrait_impossible = false;
  if (isset($mandat)){
    if ($mandat > 0){ //Type seule
      if (isset($Liste_mandataires[$mandat]) && $Liste_mandataires[$mandat] != null && $Liste_mandataires[$mandat]['limitation'] > 0){
        if (recupMontant($Liste_mandataires[$mandat]['limitation']) < recupMontant($SESSION_VARS["mnt_total1"])){ //si le montant à retirer est superieure au limit de retrait pour ce mandataire
          $retrait_impossible = true;
          $titre = "Transfert impossible pour le mandataire (".$Liste_mandataires[$mandat]['libelle'].") de type seule";
        }
      }
    }
    if ($mandat == 'CONJ'){ //Type conjointe
      if (isset($Liste_mandataires[$mandat]) && $Liste_mandataires[$mandat] != null){
        $liste_mandats = getMandats($SESSION_VARS['NumCpteSource']);
        $liste_CONJ_id = explode('-',$SESSION_VARS['mandat']['CONJ_id']);
        $mnt_limite = 0;
        $limitation = 0;
        foreach ($liste_CONJ_id as $conj_id => $value) {
          if ($value != null){
            $mnt_limite = recupMontant($liste_mandats[$value]['limitation']);
            if ($mnt_limite != null && $mnt_limite != 0) {
              if ($limitation == 0) {
                $limitation = $mnt_limite;
              }
              $limitation = min($limitation, $mnt_limite); // Si on a plusieurs mandataires conjointe on prend le minimum des montants limitation
            }
          }
        }
        if ($limitation != null && $limitation != 0) {
          if ($limitation < recupMontant($SESSION_VARS["mnt_total1"])) { //si le montant à retirer est superieure au limit de retrait pour ce mandataire
            $retrait_impossible = true;
            $titre = "Transfert impossible pour le(s) mandataire(s) (" . $Liste_mandataires[$mandat]['libelle'] . ") de type conjointe";
            $mnt_conj_limite = "(" . number_format($limitation, 0, '.', ' ') . ")";
          }
        }
      }
    }
    if ($retrait_impossible){
      $msg = "Le montant (".$SESSION_VARS["mnt_total1"].") à retirer est supérieure au limite $mnt_conj_limite de transfert!! Veuillez cliquer le bouton OK pour re-saisir le montant sur l'ecran precedent!";
      $html_err = new HTML_erreur($titre);
      $html_err->setMessage($msg);
      $html_err->addButton("BUTTON_OK", "Gen-10");
      $html_err->buildHTML();
      echo $html_err->HTML_code;
      exit;
    }
  }
  /******************************************ticket #695***********************************************************************/
  $data_agc = getAgenceDatas($global_id_agence);
  if ($data_agc['autorisation_transfert'] == 't') {
    global $global_nom_login, $global_id_agence, $colb_tableau;
    $info_login = get_login_full_info($global_nom_login);
    $info_agence = getAgenceDatas($global_id_agence);
    if (!isset($SESSION_VARS['id_dem'])) {
    /***Ticket REL-76***/
    if ($dure_minimum_retrait > 0 && !isset($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'])) {
      //if (!isset($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'])) {
        if ($SESSION_VARS['TypeTransfert'] == 4 || $SESSION_VARS['TypeTransfert'] == 3){
          $erreur = CheckDureeMinRetrait($SESSION_VARS['NumCpteSource'], $data_cpt["duree_min_retrait_jour"],$data_cpt['$data_cpt']);
        }else {
          $erreur = CheckDureeMinRetrait($SESSION_VARS['NumCpteSource'], $SESSION_VARS["cpteDest"]["duree_min_retrait_jour"]);
        }
        debug($erreur->param);
        if ($erreur->errCode == ERR_DUREE_MIN_RETRAIT) {
          $InfoCpteSource = getAccountDatas($SESSION_VARS['NumCpteSource']);
          $InfoProduitSource = getProdEpargne($InfoCpteSource["id_prod"]);
          $SESSION_VARS['ecran_prec'] = 'Tcp-4';
          $SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] = 't';
          $SESSION_VARS['mnt']= $mnt;
          $SESSION_VARS['num_chq']=$num_chq;
          $SESSION_VARS['communication']=$communication;
          $SESSION_VARS['remarque']=$remarque;
          $SESSION_VARS['date_chq']=$date_chq;
          $SESSION_VARS['mandat'] = $mandat;
          $SESSION_VARS['com_mini_2retrait']= $InfoProduitSource['frais_duree_min2retrait'];
          $SESSION_VARS['type_piece']= $type_piece;
          $html_err = new HTML_erreur(_("Transfert entre compte.") . " ");
          $html_err->setMessage(_("ATTENTION") . " : " . $error[$erreur->errCode] . " <br /> Mais si vous voulez continuer le transfert entre compte, sachez que les frais de non respect de la durée minimum entre deux retraits seront prelevés sur le compte du client; alors veuillez cliquer sur le bouton 'OK' pour continuer sinon le bouton 'annuler'!");
          $html_err->addButton("BUTTON_CANCEL", 'Tcp-1');
          $html_err->addButton("BUTTON_OK", 'Tcp-4');
          $html_err->buildHTML();
          echo $html_err->HTML_code;
          $SESSION_VARS['mnt_reel'] = $mnt_reel;
          exit();
        }

      //}
    }
    /****Fin ticket REL-76**************/

    $msg = "";
      if (!isset($SESSION_VARS['type_piece'])) {
        $SESSION_VARS['type_piece'] = $type_piece;
      }

      // Affichage de la confirmation
      $html_msg = new HTML_message("Demande autorisation de transfert");
      if (isset($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] )){
        if ($SESSION_VARS['TypeTransfert'] == 4) {
          $html_msg->setMessage("<center><span style='color: #FF0000;'><br />Le montant a transferé doit être autoriser.</span>
<br /><br />Montant a transferé = <span style='color: #FF0000;font-weight: bold;'>" . afficheMontant(recupMontant($SESSION_VARS["mnt_total1"]), true) . "</span>
<br />Veuillez choisir une option ci-dessous ?<br />
<br/></center><input type=\"hidden\" name=\"montant_transfert\" value=\"" . recupMontant($SESSION_VARS["mnt_total1"]) . "\" /><input type=\"hidden\" name=\"frais_retrait_cpt\" value=\"" . recupMontant($frais_retrait_cpt) . "\" /><input type=\"hidden\" name=\"type_retrait\" value=\"1\" /><input type=\"hidden\" name=\"choix_retrait\" value=\"" . $SESSION_VARS['type_retrait'] . "\" /><input type=\"hidden\" name=\"num_chq\" value=\"" . trim($SESSION_VARS['num_chq']) . "\" /><input type=\"hidden\" name=\"communication\" value=\"" . trim($SESSION_VARS['communication']) . "\" /><input type=\"hidden\" name=\"remarque\" value=\"" . trim($SESSION_VARS['remarque']) . "\" /><input type=\"hidden\" name=\"id_pers_ext\" value=\"" . trim($id_pers_ext) . "\" /><input type=\"hidden\" name=\"id_ben\" value=\"" . trim($id_ben) . "\" /><input type=\"hidden\" name=\"date_chq\" value=\"" . trim($SESSION_VARS['date_chq']) . "\" /><input type=\"hidden\" name=\"mandat\" value=\"" . trim($SESSION_VARS['mandat']) . "\" /><input type=\"hidden\" name=\"beneficiaire\" value=\"" . trim($beneficiaire) . "\" /><input type=\"hidden\" name=\"nom_ben\" value=\"" . trim($nom_ben) . "\" /><input type=\"hidden\" name=\"denomination\" value=\"" . trim($denomination) . "\" />");
        }
        else{
          $html_msg->setMessage("<center><span style='color: #FF0000;'><br />Le montant a transferé doit être autoriser.</span>
<br /><br />Montant a transferé = <span style='color: #FF0000;font-weight: bold;'>" . afficheMontant(recupMontant($SESSION_VARS['mnt']), true) . "</span>
<br />Veuillez choisir une option ci-dessous ?<br />
<br/></center><input type=\"hidden\" name=\"montant_transfert\" value=\"" . recupMontant($SESSION_VARS['mnt']) . "\" /><input type=\"hidden\" name=\"frais_retrait_cpt\" value=\"" . recupMontant($frais_retrait_cpt) . "\" /><input type=\"hidden\" name=\"type_retrait\" value=\"1\" /><input type=\"hidden\" name=\"choix_retrait\" value=\"" . $SESSION_VARS['type_retrait'] . "\" /><input type=\"hidden\" name=\"num_chq\" value=\"" . trim($SESSION_VARS['num_chq']) . "\" /><input type=\"hidden\" name=\"communication\" value=\"" . trim($SESSION_VARS['communication']) . "\" /><input type=\"hidden\" name=\"remarque\" value=\"" . trim($SESSION_VARS['remarque']) . "\" /><input type=\"hidden\" name=\"id_pers_ext\" value=\"" . trim($id_pers_ext) . "\" /><input type=\"hidden\" name=\"id_ben\" value=\"" . trim($id_ben) . "\" /><input type=\"hidden\" name=\"date_chq\" value=\"" . trim($SESSION_VARS['date_chq']) . "\" /><input type=\"hidden\" name=\"mandat\" value=\"" . trim($SESSION_VARS['mandat']) . "\" /><input type=\"hidden\" name=\"beneficiaire\" value=\"" . trim($beneficiaire) . "\" /><input type=\"hidden\" name=\"nom_ben\" value=\"" . trim($nom_ben) . "\" /><input type=\"hidden\" name=\"denomination\" value=\"" . trim($denomination) . "\" />");
        }
      }
      else {
        if ($SESSION_VARS['TypeTransfert'] == 4) {
          $html_msg->setMessage("<center><span style='color: #FF0000;'><br />Le montant a transferé doit être autoriser.</span>
<br /><br />Montant a transferé = <span style='color: #FF0000;font-weight: bold;'>" . afficheMontant(recupMontant($SESSION_VARS["mnt_total"]), true) . "</span>
<br />Veuillez choisir une option ci-dessous ?<br />
<br/></center><input type=\"hidden\" name=\"montant_transfert\" value=\"" . recupMontant($SESSION_VARS["mnt_total"]) . "\" /><input type=\"hidden\" name=\"frais_retrait_cpt\" value=\"" . recupMontant($frais_retrait_cpt) . "\" /><input type=\"hidden\" name=\"type_retrait\" value=\"1\" /><input type=\"hidden\" name=\"choix_retrait\" value=\"" . $SESSION_VARS['type_retrait'] . "\" /><input type=\"hidden\" name=\"num_chq\" value=\"" . trim($num_chq) . "\" /><input type=\"hidden\" name=\"communication\" value=\"" . trim($communication) . "\" /><input type=\"hidden\" name=\"remarque\" value=\"" . trim($remarque) . "\" /><input type=\"hidden\" name=\"id_pers_ext\" value=\"" . trim($id_pers_ext) . "\" /><input type=\"hidden\" name=\"id_ben\" value=\"" . trim($id_ben) . "\" /><input type=\"hidden\" name=\"date_chq\" value=\"" . trim($date_chq) . "\" /><input type=\"hidden\" name=\"mandat\" value=\"" . trim($mandat) . "\" /><input type=\"hidden\" name=\"beneficiaire\" value=\"" . trim($beneficiaire) . "\" /><input type=\"hidden\" name=\"nom_ben\" value=\"" . trim($nom_ben) . "\" /><input type=\"hidden\" name=\"denomination\" value=\"" . trim($denomination) . "\" />");
        } else {
          $html_msg->setMessage("<center><span style='color: #FF0000;'><br />Le montant a transferé doit être autoriser.</span>
<br /><br />Montant a transferé = <span style='color: #FF0000;font-weight: bold;'>" . afficheMontant(recupMontant($mnt), true) . "</span>
<br />Veuillez choisir une option ci-dessous ?<br />
<br/></center><input type=\"hidden\" name=\"montant_transfert\" value=\"" . recupMontant($mnt) . "\" /><input type=\"hidden\" name=\"frais_retrait_cpt\" value=\"" . recupMontant($frais_retrait_cpt) . "\" /><input type=\"hidden\" name=\"type_retrait\" value=\"1\" /><input type=\"hidden\" name=\"choix_retrait\" value=\"" . $SESSION_VARS['type_retrait'] . "\" /><input type=\"hidden\" name=\"num_chq\" value=\"" . trim($num_chq) . "\" /><input type=\"hidden\" name=\"communication\" value=\"" . trim($communication) . "\" /><input type=\"hidden\" name=\"remarque\" value=\"" . trim($remarque) . "\" /><input type=\"hidden\" name=\"id_pers_ext\" value=\"" . trim($id_pers_ext) . "\" /><input type=\"hidden\" name=\"id_ben\" value=\"" . trim($id_ben) . "\" /><input type=\"hidden\" name=\"date_chq\" value=\"" . trim($date_chq) . "\" /><input type=\"hidden\" name=\"mandat\" value=\"" . trim($mandat) . "\" /><input type=\"hidden\" name=\"beneficiaire\" value=\"" . trim($beneficiaire) . "\" /><input type=\"hidden\" name=\"nom_ben\" value=\"" . trim($nom_ben) . "\" /><input type=\"hidden\" name=\"denomination\" value=\"" . trim($denomination) . "\" />");
        }
      }
        $html_msg->addCustomButton("btn_demande_autorisation_retrait", "Demande d’autorisation", 'Tcp-6');
        $html_msg->addCustomButton("btn_annuler", "Annuler", 'Gen-10');

        $html_msg->buildHTML();

        echo $html_msg->HTML_code;
        die();

    }
  }
  /************************************************************************************************/
  // Si c'est un transfert groupé dans les comptes de bases des clients
  if ($SESSION_VARS["TypeTransfert"] == 4) {
    $j = 1;
    $mnt_total = 0;
    $erreurMsg = "";
    $SESSION_VARS["DATA"] = array();


    // Création du tableau : on travaille avec des lots de 40 clients
    $xtHTML .= "<TABLE align=\"center\" bgcolor=$colb_tableau border=$tableau_border cellspacing=$tableau_cellspacing cellpadding=$tableau_cellpadding>\n";

    // En-tête tableau
    $xtHTML .= "<TR bgcolor=$colb_tableau><TD><b>"._("N°")."</b></TD>";
    $xtHTML .= "<TD align=\"center\"><b>"._("N° client")."</b></TD>";
    $xtHTML .= "<TD align=\"center\"><b>"._("Nom client")."</b></TD>";
    $xtHTML .= "<TD align=\"center\"><b>"._("Montant à prélever")."</b></TD>";
    $xtHTML .= "<TD align=\"center\"><b>"._("Montant à transférer")." </b></TD>";
    // ajout colonne frais, si les comptes de destination paient les frais de transfert
    if ($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 2) {
      $xtHTML .= "<TD align=\"center\"><b>"._("Frais transfert")."</b></TD>";
      $xtHTML .= "<TD align=\"center\"><b>"._("Devive Frais")."</b></TD>";
    }
    $xtHTML .= "</TR>\n";

    // Contenu du tableau
    while ($j <= 40) {
      if ($ {"num_client$j"} != '') { // si au moins le numéro du client est renseigné
        $id_client = $ {"num_client$j"}; // numéro du client
        if (client_exist($id_client) and client_actif($id_client)) { // si le client existe et est actif
          $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['num_client'] = $id_client;

          $nom_client = getClientName($id_client);
          // Récupération des infos du compte de base et du produit associé
          $id_cpte = getBaseAccountID($id_client);
          $ACC = getAccountDatas($id_cpte);
          if ($ACC["etat_cpte"] != 1)
            $erreurMsg .= sprintf(_("Le client %s n'a pas de compte de base ouvert !"),$id_client)."<br/>";
          $num_complet_cpte = $ACC["num_complet_cpte"];

          // Montant à transferer dans la devise du compte source
          $mnt = recupMontant($ {"mnt$j"});
          $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['mnt'] = $mnt;
          if ($mnt > 0) {
            $mnt_src = $mnt; // montant prélevé dans le compte source dans sa devise
            $mnt_dest = $mnt; // montant déposé dans le compte de destination dans sa devise
            // Si la devise du compte destination n'est pas la même que celle du compte source, calculer la cv
            if ($ACC['devise'] != $SESSION_VARS['devise_source'])
              $mnt_dest = calculeCV($SESSION_VARS['devise_source'], $ACC['devise'], $mnt); // montant déposé

            $mnt_total += $mnt; // Total montant prélevé dans le compte source dans sa devise

            // si les frais sont prélevés dans les comptes de destination
            if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 2) {
              if ($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 1) { // si les frais sont du produit du compte source
                // récupération des frais éventuellement modifiés
                if (isset($ {"frais$j"})) {
                  $mnt_frais = recupMontant($ {"frais$j"});
                  $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['mnt_frais'] = $mnt_frais;
                }
                else
                  $mnt_frais = $SESSION_VARS["frais_transfert"]['transfert_groupe'][$j]['mnt_frais'];

                // si la devise des frais n'est pas la même que celle du compte de destination
                if ($ACC['devise'] != $SESSION_VARS['devise_source'])
                  $mnt_frais = calculeCV($SESSION_VARS['devise_source'], $ACC['devise'], $mnt_frais);

                // Vérifier que le solde disponible du compte et le montant du tranfert lui permet de payer les frais
                $solde_dispo = getSoldeDisponible($id_cpte);
                if (($mnt_dest + $solde_dispo) < $mnt_frais)
                  $erreurMsg .= sprintf(_("Le solde disponible et le montant du transfert pour le client %s ne peut pas payer les frais !"),$id_client)."<br/>";
              }
              elseif($SESSION_VARS["frais_transfert"]['type_mnt_frais'] == 2) // frais sont du produit du compte destination
              $mnt_frais = $ACC['frais_transfert'];
            }

            $SESSION_VARS["DATA"][$j] = array ("id_client"=>$id_client, "num_complet_cpte"=>$num_complet_cpte,
                                               "id_cpte"=>$id_cpte, "devise"=>$ACC['devise'],"mnt_src" => $mnt_src,
                                               "mnt_dest"=>$mnt_dest,"mnt_frais"=>$mnt_frais,"nom_client"=>$nom_client );

            //On alterne la couleur de fond
            $color = ($color==$colb_tableau? $colb_tableau_altern : $colb_tableau);

            $xtHTML .= "<TR bgcolor=$color>\n<TD>".$j."</TD>";
            $xtHTML .= "<TD>".sprintf("%06d", $id_client)."</TD>";
            $xtHTML .= "<TD>".getClientName($id_client)."</TD>";
            setMonnaieCourante($SESSION_VARS["devise_source"]); // devise du compte source du transfert
            $xtHTML .= "<TD align=\"right\">".afficheMontant($mnt_src, true)."</TD>";
            setMonnaieCourante($ACC['devise']); // devise du compte de destination
            $xtHTML .= "<TD align=\"right\">".afficheMontant($mnt_dest, true)."</TD>";
            if ($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 2) {
              if (check_access(299)) {
                $xtHTML .= "<TD align=\"center\"><input type=\"text\" name=\"frais$j\" onchange=\"value = formateMontant(value);\" size=8\" value=".afficheMontant($mnt_frais)."></TD>";

                $xtHTML .= "<TD align=\"center\">".$ACC['devise']."</TD>";
              } else
                $xtHTML .= "<TD align=\"center\">".afficheMontant($mnt_frais,true)."</TD>";
            }

            $xtHTML .= "</TR>";
          } else
            $erreurMsg .= sprintf(_("Le montant pour le client %s doit etre positif"),$id_client)."<br />";
        } // fin si client existe et actif
        else
          $erreurMsg .= sprintf(_("Le client %s n'existe pas ou n'est pas actif"),$id_client)."<br/>";
      } // fin si numéro client saisi

      $j++;
    } // fin boucle while

    // Fin tableau
    $xtHTML .= "</TABLE><br />";

    // Enregistrement du montant total dans la devise du compte source
    $SESSION_VARS["mnt_total"] = $mnt_total;

    // Si le compte source paie les frais, ajouter les frais dans le montant à prélever dans le compte source
    if ($SESSION_VARS["frais_transfert"]['type_cpte_preleve'] == 1) {
      if ($SESSION_VARS['frais_transfert']['mnt_frais_cv'] > 0) // si les frais n'étaient de la même devise que le compte source
        $mnt_total += $SESSION_VARS['frais_transfert']['mnt_frais_cv'];
      elseif($SESSION_VARS['frais_transfert']['mnt_frais'] > 0)
      $mnt_total += $SESSION_VARS['frais_transfert']['mnt_frais'];
    }

    // Vérifier que le solde disponible du compte source permet le transfert
    if ($mnt_total > $SESSION_VARS["soldeDispo"])
      $erreurMsg .= sprintf(_("Le solde disponible dans le compte ne permet pas un transfert de %s"),afficheMontant($mnt_total, true));

    // Si une erreur a été rencontrée dans le traitement du transfert groupé, annuler le traitement
    if ($erreurMsg != '') {
      $html_err = new HTML_erreur(_("Erreur"));
      $html_err->setMessage($erreurMsg);
      $html_err->addButton("BUTTON_OK", 'Tcp-3');
      $html_err->buildHTML();
      echo $html_err->HTML_code;
    } else {
      $html = new HTML_GEN2(_("Confirmation du transfert groupé"));
      $html->addHTMLExtraCode("htm1", $xtHTML);

      $html->addField("mnt_total", _("Montant total du transfert"), TYPC_MNT);
      $html->setFieldProperties("mnt_total", FIELDP_DEVISE, $SESSION_VARS["devise_source"]);
      $html->setFieldProperties("mnt_total",FIELDP_DEFAULT, $mnt_total);
      $html->setFieldProperties("mnt_total", FIELDP_IS_LABEL, true);

      $html->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
      $html->addFormButton(1, 2, "retour", _("Précédent"), TYPB_SUBMIT);
      $html->addFormButton(1, 3, "cancel", _("Annuler"), TYPB_SUBMIT);
      $html->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Tcp-5');
      $html->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN,'Tcp-3');
      $html->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-10');
      $html->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);
      $html->setFormButtonProperties("retour", BUTP_CHECK_FORM, false);

      $html->buildHTML();
      echo $html->getHTML();
    }
  }

  else { // Type transfert entre 1 et 3 (virement interne (client différent et identique) et externe)

    if (isset($mnt))
      $SESSION_VARS["mnt"] = recupMontant($mnt);
    if (isset($_POST['mntDevise']['cv']))
      $SESSION_VARS['change']= $_POST['mntDevise'];
    if (isset($type_piece))
      $SESSION_VARS['type_piece'] = $type_piece;
    if (isset($num_chq))
      $SESSION_VARS['num_piece'] = $num_chq;
    if (isset($date_chq))
      $SESSION_VARS['date_piece'] = $date_chq;

    $html = new HTML_GEN2(_("Confirmation du montant à transférer"));
    setMonnaieCourante($SESSION_VARS['devise']);
    if (isset($SESSION_VARS['change'])) {
      $html->addField("mntDevise", _("Montant versé sur le compte destination"), TYPC_MNT);
      $html->setFieldProperties("mntDevise", FIELDP_DEFAULT, $SESSION_VARS['change']['cv']);
      $html->setFieldProperties("mntDevise", FIELDP_DEVISE, $SESSION_VARS['change']['devise']);
      $html->setFieldProperties("mntDevise", FIELDP_IS_LABEL,true);
    }

    $html->addField("mnt",_("Montant prélevé sur le compte source"),TYPC_MNT);
    $html->setFieldProperties("mnt", FIELDP_DEFAULT, $SESSION_VARS["mnt"]);
    $html->setFieldProperties("mnt", FIELDP_IS_LABEL, true);

    $html->addField("mnt_reel",_("Montant transféré"),TYPC_MNT);
    $html->setFieldProperties("mnt_reel", FIELDP_IS_REQUIRED, true);
    $montantAff=afficheMontant($SESSION_VARS["mnt"], true);
    $ChkJS = "
             if (recupMontant(document.ADForm.mnt_reel.value) != recupMontant(document.ADForm.mnt.value))
           {
             msg += '- "._("Le montant saisi ne correspond pas au montant à transférer")." (".$montantAff.")\\n';
             ADFormValid=false;
           };";
    $html->addJS(JSP_BEGIN_CHECK, "JS6",$ChkJS);

    // Frais de transfert
    if (isset($SESSION_VARS['frais_transfert']['cpte_preleve'])) {
      $html->addField("cpte_preleve",_("Compte de prélèvement des frais"), TYPC_TXT);
      $html->setFieldProperties("cpte_preleve", FIELDP_DEFAULT, $SESSION_VARS['frais_transfert']['cpte_preleve']);
      $html->setFieldProperties("cpte_preleve", FIELDP_IS_LABEL, true);
      $html->addField("frais_transfert",_("Montant des frais"),TYPC_MNT);
      if ($SESSION_VARS['frais_transfert']['mnt_frais_cv'] > 0)
        $html->setFieldProperties("frais_transfert", FIELDP_DEFAULT, $SESSION_VARS['frais_transfert']['mnt_frais_cv']);
      elseif($SESSION_VARS['frais_transfert']['mnt_frais'] > 0)
      $html->setFieldProperties("frais_transfert", FIELDP_DEFAULT, $SESSION_VARS['frais_transfert']['mnt_frais']);
      $html->setFieldProperties("frais_transfert", FIELDP_DEVISE, $SESSION_VARS['frais_transfert']['devise_cpte_frais']);
      $html->setFieldProperties("frais_transfert", FIELDP_IS_LABEL, true);
    }

    $html->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
    if (!isset($SESSION_VARS['id_dem'])){
      $html->addFormButton(1, 2, "retour", _("Précédent"), TYPB_SUBMIT);
      $html->setFormButtonProperties("retour", BUTP_CHECK_FORM, false);
      $html->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN,'Tcp-3');
    }
    $html->addFormButton(1, 3, "cancel", _("Annuler"), TYPB_SUBMIT);
    $html->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Tcp-5');
    $html->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-10');
    $html->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);

    $html->buildHTML();
    echo $html->getHTML();
  }
}
//---------------------------------------------------------------------
//----------- Tcp-5 Confirmation du transfert -------------------------
//---------------------------------------------------------------------
else if ($global_nom_ecran == "Tcp-5") {

  if ($SESSION_VARS['TypeTransfert'] == 4 || $SESSION_VARS['TypeTransfert'] == 3){
    $data_cpt = getAccountDatas($SESSION_VARS['NumCpteSource']);
    $dure_minimum_retrait = $data_cpt["duree_min_retrait_jour"];
  }else{
    $dure_minimum_retrait = $SESSION_VARS["cpteDest"]["duree_min_retrait_jour"];
  }


  if ($dure_minimum_retrait > 0 && !isset($SESSION_VARS['id_dem'])) {
    if (!isset($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'])) {
      if ($SESSION_VARS['TypeTransfert'] == 4 || $SESSION_VARS['TypeTransfert'] == 3){
        $erreur = CheckDureeMinRetrait($SESSION_VARS['NumCpteSource'], $data_cpt["duree_min_retrait_jour"],$data_cpt['type_duree_min2retrait']);
      }else {
        $erreur = CheckDureeMinRetrait($SESSION_VARS['NumCpteSource'], $SESSION_VARS["cpteDest"]["duree_min_retrait_jour"],$SESSION_VARS["cpteDest"]["type_duree_min2retrait"] );
      }
      debug($erreur->param);
      if ($erreur->errCode == ERR_DUREE_MIN_RETRAIT) {
        $SESSION_VARS['ecran_prec'] = 'Tcp-5';
        $SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] = 't';
        $html_err = new HTML_erreur(_("Transfert entre compte.") . " ");
        $html_err->setMessage(_("ATTENTION") . " : " . $error[$erreur->errCode] . " <br /> Mais si vous voulez continuer le transfert entre compte, sachez que les frais de non respect de la durée minimum entre deux retraits seront prelevés sur le compte du client; alors veuillez cliquer sur le bouton 'OK' pour continuer sinon le bouton 'annuler'!");
        $html_err->addButton("BUTTON_CANCEL", 'Tcp-1');
        $html_err->addButton("BUTTON_OK", 'Tcp-5');
        $html_err->buildHTML();
        echo $html_err->HTML_code;
        $SESSION_VARS['mnt_reel'] = $mnt_reel;
        exit();
      }

    }
  }

  // Récupération du montant réel
  if (isset($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'])){
    $mnt_reel = recupMontant($SESSION_VARS['mnt_reel']);
  }else {
    $mnt_reel = recupMontant($mnt_reel);
  }


  // Création du tableau contenant les données de la pièce justificative
  $data_virement=array();
  $data_virement['communication']    = $SESSION_VARS['communication'];
  $data_virement['id_tireur_benef']  = NULL;
  $data_virement['type_piece']       = $SESSION_VARS['type_piece'];
  $data_virement['remarque']         = $SESSION_VARS['remarque'];
  $data_virement['communication']    = $SESSION_VARS['communication'];
  $data_virement['sens']             = '---';//il s'agit d'un transfert interne (aucun mouvement de ou vers l'ext.)
  $data_virement['num_piece']        = $SESSION_VARS['num_piece'];
  $data_virement['date_piece']       = $SESSION_VARS['date_piece'];


  //transfert entre comptes d'un même client
  if ($SESSION_VARS["TypeTransfert"] == 1) {
    // Montant des frais de transfert
    if ($SESSION_VARS['frais_transfert']['mnt_frais_cv'] > 0) // frais n'étaient pas dans la devise du compte de prélèvement
      $frais_transfert = $SESSION_VARS['frais_transfert']['mnt_frais_cv'];
    elseif($SESSION_VARS['frais_transfert']['mnt_frais'] > 0) // frais étaient dans la devise du compte de prélèvement
    $frais_transfert = $SESSION_VARS['frais_transfert']['mnt_frais'];
    else
      $frais_transfert = 0;

    // Compte de prélèvement des frais
    if ($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 1)
      $cpte_preleve = $SESSION_VARS["NumCpteSource"];
    elseif($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 2)
    $cpte_preleve = $SESSION_VARS['CpteMemeClient'];
    else
      $cpte_preleve = NULL;

    if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't'){
      $erreur = transfertCpteClient($SESSION_VARS["NumCpteSource"], $SESSION_VARS['CpteMemeClient'], $mnt_reel, $SESSION_VARS['id_mandat'], $frais_transfert, $SESSION_VARS['change'], $data_virement, NULL, $cpte_preleve,$a_his_compta,true);
    }else {
      $erreur = transfertCpteClient($SESSION_VARS["NumCpteSource"], $SESSION_VARS['CpteMemeClient'], $mnt_reel, $SESSION_VARS['id_mandat'], $frais_transfert, $SESSION_VARS['change'], $data_virement, NULL, $cpte_preleve);
    }
    //prélèvement des frais en attente si solde_disponible > montant_frais
    $prelevement_frais = false;
    $num_compte = $SESSION_VARS["CpteMemeClient"];
    $InfoCpte = getAccountDatas($num_compte);
    $mnt_frais_attente = 0;
    //Y a t-il des frais en attente sur le compte ?
    if(hasFraisAttenteCompte($num_compte)){
    	$result = getFraisAttenteCompte($num_compte);
     	$liste_frais_attente = $result->param;
     	//Pour chaque frais en attente
     	foreach($liste_frais_attente as $key=>$frais_attente) {
     		//Recupération du solde disponible sur le compte
     		$solde_disponible = getSoldeDisponible($num_compte);
     		$montant_frais = $frais_attente['montant'];
     		$type_frais = $frais_attente['type_frais'];
     		$date_frais = $frais_attente['date_frais'];
     		$comptable = array();//pour passage ecritures
     		//vois si le solde disponible est suffisant pour prélever les frais
	     	if($solde_disponible >= $montant_frais){
	     		$erreurs = paieFraisAttente($num_compte, $type_frais, $montant_frais, $comptable);
		      if ($erreurs->errCode != NO_ERR){
		       	return $erreurs;
		      }
		      //Suppression dans la table des frais en attente
		      $sql = "DELETE FROM ad_frais_attente WHERE id_cpte = $num_compte AND date(date_frais) = date('$date_frais') AND type_frais = $type_frais;";
		      $result = executeDirectQuery($sql);
		      if ($result->errCode != NO_ERR){
		       	return new ErrorObj($result->errCode);
		      }
		      $prelevement_frais = true;
		      //memoriser montant des frais prélevés
	     		$mnt_frais_attente += $montant_frais;
	     		//Historiser le prelevement
		      $myErr = ajout_historique(87, $InfoCpte["id_titulaire"],'', $global_nom_login, date("r"), $comptable);
					if ($myErr->errCode != NO_ERR) {
					   $dbHandler->closeConnection(false);
					   return $myErr;
					}
	   	  }
      }
    }

    if ($erreur->errCode == NO_ERR) {

      $remboursement_cap_lcr = false;
      $total_mnt_cap_lcr = 0;

      // [Ligne de crédit] : Remboursement Capital
      // $lcrErr = rembourse_cap_lcr(date("d/m/Y"), $num_compte);
      $lcrErr = rembourse_cap_lcr(date("d/m/Y"), $num_compte, $mnt_reel, $erreur ->param);

      if ($lcrErr->errCode == NO_ERR) {
        $total_mnt_cap_lcr = $lcrErr->param[1];

        if ($total_mnt_cap_lcr > 0) {
          $remboursement_cap_lcr = true;
        }
      }

      $compteSource = getAccountDatas($SESSION_VARS["NumCpteSource"]);
      $compteDestination = getAccountDatas($SESSION_VARS['CpteMemeClient']);

      setMonnaieCourante($compteSource['devise']);
      $mntPreleve = afficheMontant($mnt_reel,TRUE);

      if (isset($SESSION_VARS['change']['cv'])) {
        setMonnaieCourante($SESSION_VARS['change']['devise']);
        $mnt_reel = afficheMontant($SESSION_VARS['change']['cv'], true);
      } else
        $mnt_reel=afficheMontant($mnt_reel,TRUE);

      $mntFraisTransfert = afficheMontant($frais_transfert,TRUE);
       // Message de confirmation
      $html_msg =new HTML_message(_("Confirmation de transfert sur un compte"));
      $messageFinal=_("Montant prélevé sur le compte source"). $mntPreleve ."<br />";
      $messageFinal.=_("Montant déposé sur le compte destination"). $mnt_reel ."<br />";

      if ($cpte_preleve == $SESSION_VARS["NumCpteSource"]) {
        setMonnaieCourante($compteSource['devise']);
        $fraisCpteSource=afficheMontant($frais_transfert,TRUE);
        $messageFinal.=_("Frais de transfert prélevés sur le compte source")." ".$fraisCpteSource;
      }
      elseif($cpte_preleve == $SESSION_VARS['CpteMemeClient']) {
        setMonnaieCourante($compteDestination['devise']);
        $fraisCpteDest=afficheMontant($frais_transfert,TRUE);
        $messageFinal.=_("Frais de transfert prélevés sur le compte de destination")." ". $fraisCpteDest;
      }
      if ($prelevement_frais) {
        $messageFinal .= "<br>"._("Des frais en attente ont été débités sur le compte destination:".$compteDestination['num_complet_cpte']." pour un montant de")." :<br>";
        $messageFinal .= afficheMontant($mnt_frais_attente, true);
      }
      if ($remboursement_cap_lcr) {
        $messageFinal .= "<br>"._("Ligne de crédit : Le capital restant dû a été débité de votre compte destination:".$compteDestination['num_complet_cpte']." pour un montant de")." :<br>";
        $messageFinal .= afficheMontant($total_mnt_cap_lcr, true);
      }
       //impression du recu de transfert
	   $data_donneur_or=array();
	   $data_donneur_or['nom_client']=getNomClient($global_id_client);
  /*    if ($SESSION_VARS['id_mandat'] == 'CONJ'){
        $data_donneur_or['donneur_ordre']=$SESSION_VARS['mandat']['CONJ'];
      }else{
        $info_mandataire = getInfosMandat($SESSION_VARS['id_mandat']);print_rn($info_mandataire);
        $data_donneur_or['donneur_ordre']=getNomClient($global_id_client);
      }*/

	   $data_donneur_or['num_cpte']=$compteSource['num_complet_cpte'];
	   $data_donneur_or['id_prod'] = $compteSource["id_prod"];
	   $data_donneur_or['mnt']=$mntPreleve;
	   $data_donneur_or['solde']="";
	   //8$data_donneur_or['devise']="";

	   $dateBenef[0]['id_client']=$SESSION_VARS['cpteDest']['id_titulaire'];
	   $dateBenef[0]['nom_client']=getNomClient($SESSION_VARS['cpteDest']['id_titulaire']);
	   $dateBenef[0]['num_complet_cpte']=$SESSION_VARS['cpteDest']['num_complet_cpte'];
	   $dateBenef[0]['mnt_dest']=$mnt_reel;
	   $dateBenef[0]['mnt_src']= $mntPreleve;
	   $dateBenef[0]['devise']= $SESSION_VARS['cpteDest']['devise'];
	   $dateBenef[0]['frais']=$fraisCpteDest;


	   $data_transfert=$data_virement;
	   $data_transfert['frais_transfert']=$fraisCpteSource;
	   $data_transfert['id_his']=$erreur->param;
	   print_recu_transfert_comptes($data_donneur_or,$dateBenef,$data_transfert, $mnt_frais_attente);
	  //
      if (isset($SESSION_VARS['id_dem'])) {
        $erreur2 = updateTransfertAttenteEtat($SESSION_VARS['id_dem'], 3, "Demande autorisation de transfert: Payé", $erreur->param);

        if ($erreur2->errCode == NO_ERR) {
          // Commit
          $dbHandler->closeConnection(true);
          unset($SESSION_VARS['id_dem']);
        }
      }


      $messageFinal .= "<br /><br />"._("N° de transaction : ")."<B><CODE>".sprintf("%09d", $erreur->param)."</CODE></B>";
      $html_msg->setMessage($messageFinal);
      $html_msg->addButton("BUTTON_OK", 'Gen-10');
      $html_msg->buildHTML();
      echo $html_msg->HTML_code;
    } else {
      $html_err = new HTML_erreur(_("Echec de transfert sur un compte.")." ");
      $html_err->setMessage("Erreur : ".$error[$erreur->errCode]);
      $html_err->addButton("BUTTON_OK", 'Tcp-1');
      $html_err->buildHTML();
      echo $html_err->HTML_code;
    }
  } // Fin si transfert pour le même client
  elseif ($SESSION_VARS["TypeTransfert"] == 2) { // transfert sur le compte d'un autre client de la banque
    $InfoClientDestination = getClientDatas($SESSION_VARS["num_client"]);

    //Si le client n'existe pas
    if ($InfoClientDestination == NULL) {
      $erreur = new HTML_erreur(_("Client inexistant"));
      $erreur->setMessage(_("Le numéro de client entré ne correspond à aucun client valide"));
      $erreur->addButton(BUTTON_OK, "Tcp-2");
      $erreur->buildHTML();
      echo $erreur->HTML_code;
      $SESSION_VARS["FromErrorScreen"] = TRUE;
    } else if ($InfoClientDestination["id_client"] == $global_id_client) { // si les comptes appartiennent au même client
      $html_err = new HTML_erreur(_("Erreur"));
      $html_err->setMessage(_("Transfert sur le même client impossible"));
      $html_err->addButton("BUTTON_OK", 'Tcp-2');
      $html_err->buildHTML();
      echo $html_err->HTML_code;
      $SESSION_VARS["FromErrorScreen"] = TRUE;
    } else {
      $NumCpteDestination = $SESSION_VARS['cpt_dest'];
      $mnt_reel = recupMontant($mnt_reel);

      // Montant des frais de transfert
      if ($SESSION_VARS['frais_transfert']['mnt_frais_cv'] > 0) // frais n'étaient pas dans la devise du compte de prélèvement
        $frais_transfert = $SESSION_VARS['frais_transfert']['mnt_frais_cv'];
      elseif($SESSION_VARS['frais_transfert']['mnt_frais'] > 0) // frais étaient dans la devise du compte de prélèvement
      $frais_transfert = $SESSION_VARS['frais_transfert']['mnt_frais'];
      else
        $frais_transfert = 0;

      // Compte de prélèvement des frais
      if ($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 1)
        $cpte_preleve = $SESSION_VARS["NumCpteSource"];
      elseif($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 2)
      $cpte_preleve = $NumCpteDestination;
      else
        $cpte_preleve = NULL;
       // gestion de chéque interne 
       if($SESSION_VARS['type_piece'] == 15 ) {
       	$data_benef = array();
       	$data_benef['denomination'] = getClientName($SESSION_VARS['cpteDest']['id_titulaire']);
       	$data_benef['tireur'] = 'f';
       	$data_benef['beneficiaire'] = 't';
       	
       } else {
       	$data_benef = NULL;
       }

      $his = NULL;

      if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || isset($SESSION_VARS['id_dem'])){
        $infoRetraitAttenteVerif = getTransfertAttenteAutorise($SESSION_VARS['id_dem'], $global_id_client);
        if (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] == null){
          $erreur = transfertCpteClient($SESSION_VARS["NumCpteSource"], $NumCpteDestination, $mnt_reel, $SESSION_VARS['id_mandat'], $frais_transfert, $SESSION_VARS['change'], $data_virement, NULL, $cpte_preleve, $his, true, $data_benef);
        }else{
          $erreur = transfertCpteClient($SESSION_VARS["NumCpteSource"], $NumCpteDestination, $mnt_reel, $SESSION_VARS['id_mandat'], $frais_transfert, $SESSION_VARS['change'], $data_virement, NULL, $cpte_preleve, $his, true, $data_benef,true);
        }
      }else {
        $erreur = transfertCpteClient($SESSION_VARS["NumCpteSource"], $NumCpteDestination, $mnt_reel, $SESSION_VARS['id_mandat'], $frais_transfert, $SESSION_VARS['change'], $data_virement, NULL, $cpte_preleve, $his, NULL, $data_benef);
      }
      if ($erreur->errCode == NO_ERR) {

        $num_compte = $NumCpteDestination;

        $remboursement_cap_lcr = false;
        $total_mnt_cap_lcr = 0;

        // [Ligne de crédit] : Remboursement Capital
        $lcrErr = rembourse_cap_lcr(date("d/m/Y"), $num_compte, $mnt_reel, $erreur->param);

        if ($lcrErr->errCode == NO_ERR) {
          $total_mnt_cap_lcr = $lcrErr->param[1];

          if ($total_mnt_cap_lcr > 0) {
            $remboursement_cap_lcr = true;
          }
        }

        //prélèvement des frais en attente si solde_disponible > montant_frais
        $prelevement_frais = false;
        $InfoCpte = getAccountDatas($num_compte);
        $mnt_frais_attente = 0;
        //Y a t-il des frais en attente sur le compte ?
        if(hasFraisAttenteCompte($num_compte)){
          $result = getFraisAttenteCompte($num_compte);
          $liste_frais_attente = $result->param;
          //Pour chaque frais en attente
          foreach($liste_frais_attente as $key=>$frais_attente) {
            //Recupération du solde disponible sur le compte
            $solde_disponible = getSoldeDisponible($num_compte);
            $montant_frais = $frais_attente['montant'];
            $type_frais = $frais_attente['type_frais'];
            $date_frais = $frais_attente['date_frais'];
            $comptable = array();//pour passage ecritures
            //vois si le solde disponible est suffisant pour prélever les frais
            if($solde_disponible >= $montant_frais){
              $erreurs = paieFraisAttente($num_compte, $type_frais, $montant_frais, $comptable);
              if ($erreurs->errCode != NO_ERR){
                return $erreurs;
              }
              //Suppression dans la table des frais en attente
              $sql = "DELETE FROM ad_frais_attente WHERE id_cpte = $num_compte AND date(date_frais) = date('$date_frais') AND type_frais = $type_frais;";
              $result = executeDirectQuery($sql);
              if ($result->errCode != NO_ERR){
                return new ErrorObj($result->errCode);
              }
              $prelevement_frais = true;
              //memoriser montant des frais prélevés
              $mnt_frais_attente += $montant_frais;
              //Historiser le prelevement
              $myErr = ajout_historique(87, $InfoCpte["id_titulaire"],'', $global_nom_login, date("r"), $comptable, null, $erreur->param);
              if ($myErr->errCode != NO_ERR) {
                $dbHandler->closeConnection(false);
                return $myErr;
              }
            }
          }
        }

        // montant prélevé dans le compte source dans sa devise
        $compteSource = getAccountDatas($SESSION_VARS["NumCpteSource"]);
        setMonnaieCourante($compteSource['devise']);
        $mntPreleve = afficheMontant($mnt_reel,TRUE);

        $mntFraisTransfert = afficheMontant($compteSource["frais_transfert"],TRUE);

        // montant déposé dans le compte de destination dans sa devise
        if (isset($SESSION_VARS['change']['cv'])) {
          setMonnaieCourante($SESSION_VARS['change']['devise']);
          $mnt_reel=afficheMontant($SESSION_VARS['change']['cv'], true);
        } else
          $mnt_reel=afficheMontant($mnt_reel,TRUE);

        // Message de confirmation
        $html_msg = new HTML_message(_("Confirmation de transfert sur un compte"));
        $messageFinal = _("Montant prélevé sur le compte source")." ".$mntPreleve."<br />";
        $messageFinal .= _("Montant déposé sur le compte destination") ." ". $mnt_reel ."<br />";

        if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] != null)){
          $messageFinal .= _("Frais de la duré minimum entre deux retraits :") ." ". afficheMontant($SESSION_VARS['cpteDest']['frais_duree_min2retrait'],TRUE) ."<br />";
        }

        if ($cpte_preleve == $SESSION_VARS["NumCpteSource"]) {
          setMonnaieCourante($compteSource['devise']);
          $fraisCpteSource=afficheMontant($frais_transfert,TRUE);
          $messageFinal.=_("Frais de transfert prélevés sur le compte source")." ".$fraisCpteSource;
        }
        elseif($cpte_preleve == $NumCpteDestination) {
          $compteDestination = getAccountDatas($NumCpteDestination);
          setMonnaieCourante($compteDestination['devise']);
          $fraisCpteDest=afficheMontant($frais_transfert,TRUE);
          $messageFinal.=_("Frais de transfert prélevés sur le compte de destination")." ".$fraisCpteDest;
        }

        if ($remboursement_cap_lcr) {
          $messageFinal .= "<br>"._("Ligne de crédit : Des frais et intérêts en attente ont été débités sur le compte destination:".$SESSION_VARS['cpteDest']['num_complet_cpte']." pour un montant de")." :<br>";
          $messageFinal .= afficheMontant($total_mnt_cap_lcr, true);
        }

        $messageFinal .= "<br /><br />"._("N° de transaction")." : <B><CODE>".sprintf("%09d", $erreur->param)."</CODE></B>";
        //impression
	     $data_donneur_or=array();
		   $data_donneur_or['nom_client']=getNomClient($global_id_client);
        if ($SESSION_VARS['id_mandat'] != null && $SESSION_VARS['id_mandat'] == 'CONJ'){
          $data_donneur_or['donneur_ordre']=$SESSION_VARS['mandat']['CONJ'];
        }
        if($SESSION_VARS['id_mandat'] != null && $SESSION_VARS['id_mandat'] != 'CONJ'){
          $info_mandataire = getInfosMandat($SESSION_VARS['id_mandat']);
          $data_donneur_or['donneur_ordre']=$info_mandataire['denomination'];
        }
		   $data_donneur_or['num_cpte']=$compteSource['num_complet_cpte'];
		   $data_donneur_or['id_prod'] = $compteSource["id_prod"];
		   $data_donneur_or['mnt']=$mntPreleve;
		   $data_donneur_or['solde']="";
       // Ticket REL-85

       //$data_donneur_or['id_mandat'] = $SESSION_VARS['id_mandat'];

		   $dateBenef[0]['id_client']=$InfoClientDestination["id_client"];
		   $dateBenef[0]['nom_client']=getNomClient( $InfoClientDestination["id_client"]);
		   $dateBenef[0]['num_complet_cpte']=$SESSION_VARS['cpteDest']['num_complet_cpte'];
		   $dateBenef[0]['mnt_dest']=$mnt_reel;
	   	 $dateBenef[0]['mnt_src']= $mntPreleve;
	     $dateBenef[0]['devise']= $SESSION_VARS['cpteDest']['devise'];
       $dateBenef[0]['frais']=$fraisCpteDest;

		   $data_transfert=$data_virement;
		   $data_transfert['frais_transfert']=$fraisCpteSource;
        if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] != null) ){
          $data_transfert['frais_minimum2retrait']=afficheMontant($SESSION_VARS['cpteDest']['frais_duree_min2retrait'],TRUE);
        }

		   $data_transfert['id_his']=$erreur->param;
		   print_recu_transfert_comptes($data_donneur_or,$dateBenef,$data_transfert);

        if (isset($SESSION_VARS['id_dem'])) {
          $erreur2 = updateTransfertAttenteEtat($SESSION_VARS['id_dem'], 3, "Demande autorisation de transfert: Payé", $erreur->param);

          if ($erreur2->errCode == NO_ERR) {
            // Commit
            $dbHandler->closeConnection(true);
            unset($SESSION_VARS['id_dem']);
          }
        }


	     //
        $html_msg->setMessage($messageFinal);
        $html_msg->addButton("BUTTON_OK", 'Gen-10');
        $html_msg->buildHTML();
        echo $html_msg->HTML_code;
      } else {
      	 sendMsgErreur (_("Echec de transfert sur un compte.")." ",$erreur,'Tcp-1' );
       
      }
    }
  } // fin si transfert pour un autre client interne
  else if ($SESSION_VARS["TypeTransfert"] == 3) { // Transfert par chèque ou par virement
    $infosCorrespondant = getInfosCorrespondant($SESSION_VARS['id_correspondant']);
    $data = array();
    $data['id_correspondant'] = $SESSION_VARS['id_correspondant'];
    $data['id_ext_benef']     = $SESSION_VARS['id_ben'];
    $data['id_cpt_ordre']     = $SESSION_VARS['NumCpteSource'];
    $data['sens']             = 'out';
    $data['type_piece']       = $SESSION_VARS['type_piece'];
    $data['num_piece']        = $SESSION_VARS['num_piece'];
    $data['date_piece']       = date("d/m/Y");
    $data['date']             = date("d/m/Y");
    $data['montant']          = $SESSION_VARS['mnt'];
    $data['devise']           = $SESSION_VARS['devise'];
    $data['etat']             = 1;
    $data['communication']    = $SESSION_VARS['communication'];
    $data['remarque']         = $SESSION_VARS['remarque'];
    $data['id_banque']        = $infosCorrespondant['id_banque'];
    if (isset($SESSION_VARS['change']['cv'])) {
      $data['montant']       = $SESSION_VARS['change']['cv'];
      $data['devise']        = $SESSION_VARS['change']['devise'];
    }

    $benef = getTireurBenefDatas($SESSION_VARS['id_ben']);
    $infosCorrespondant = getInfosCorrespondant($SESSION_VARS['id_correspondant']);
    $cptSource = getAccountDatas($SESSION_VARS["NumCpteSource"]);
    $prodSource = getProdEpargne($cptSource['id_prod']);
    $frais_duree_minimum2retrait = afficheMontant($cptSource['frais_duree_min2retrait'],true);

    //FIXME Bernard : visiblement getAccountDatas renvoie aussi les infos concernant le produit. Or, il arrive souvent que l'on passe et les informations du compte et celles du produit, en paramètre. Ne serait-il pas plus judicieux de ne passer plus que les informations "complètes" du compte ?
    $cptSource['frais_transfert'] = $SESSION_VARS['frais_transfert']['mnt_frais'];
    $prodSource['frais_transfert'] = $SESSION_VARS['frais_transfert']['mnt_frais'];

    if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || isset($SESSION_VARS['id_dem'])){
      $infoRetraitAttenteVerif = getTransfertAttenteAutorise($SESSION_VARS['id_dem'], $global_id_client);
      if (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] == null){
        $erreur = retrait_cpte(null, $SESSION_VARS['NumCpteSource'], $prodSource, $cptSource, $SESSION_VARS['mnt'], $SESSION_VARS['type_piece'], $SESSION_VARS['id_mandat'], $data, $SESSION_VARS['change'],null);
      }else{
        $erreur = retrait_cpte(null, $SESSION_VARS['NumCpteSource'], $prodSource, $cptSource, $SESSION_VARS['mnt'], $SESSION_VARS['type_piece'], $SESSION_VARS['id_mandat'], $data, $SESSION_VARS['change'],null,true);
      }

    }else{
      $erreur = retrait_cpte(null, $SESSION_VARS['NumCpteSource'], $prodSource, $cptSource, $SESSION_VARS['mnt'], $SESSION_VARS['type_piece'], $SESSION_VARS['id_mandat'], $data, $SESSION_VARS['change'],null);
    }

    if ($erreur->errCode == NO_ERR) {
      $compteSource = getAccountDatas($SESSION_VARS["NumCpteSource"]);
      setMonnaieCourante($compteSource['devise']);
      if (check_access(299) && isset($SESSION_VARS['frais_transfert']['mnt_frais']))
        $compteSource["frais_transfert"] = $SESSION_VARS['frais_transfert']['mnt_frais'];
      $mntPreleve = afficheMontant($mnt_reel,TRUE);
      $mntFraisTransfert = afficheMontant($compteSource["frais_transfert"],TRUE);
      if (isset($SESSION_VARS['change']['cv'])) {
        setMonnaieCourante($SESSION_VARS['change']['devise']);
        $mnt_reel=afficheMontant($SESSION_VARS['change']['cv'], true);
      } else {
        $mnt_reel=afficheMontant($mnt_reel,TRUE);
      }
         //Impression
         //
      $data_donneur_or=array();
		  $data_donneur_or['nom_client']=getNomClient($global_id_client);
      if ($SESSION_VARS['id_mandat'] != null && $SESSION_VARS['id_mandat'] == 'CONJ'){
        $data_donneur_or['donneur_ordre']=$SESSION_VARS['mandat']['CONJ'];
      }
      if($SESSION_VARS['id_mandat'] != null && $SESSION_VARS['id_mandat'] != 'CONJ'){
        $info_mandataire = getInfosMandat($SESSION_VARS['id_mandat']);
        $data_donneur_or['donneur_ordre']=$info_mandataire['denomination'];
      }
		  $data_donneur_or['num_cpte']=$compteSource['num_complet_cpte'];
		  $data_donneur_or['id_prod'] = $compteSource["id_prod"];
		  $data_donneur_or['mnt']=$mntPreleve;
		  $data_donneur_or['solde']="";

		  $dateBenef[0]['id_client']=$SESSION_VARS['cpteDest']['id_titulaire'];
		  $dateBenef[0]['nom_client']=$benef['denomination'];
		  $dateBenef[0]['num_complet_cpte']=$benef['iban_cpte'];;
		  $dateBenef[0]['mnt_dest']=$mnt_reel;
		  $dateBenef[0]['mnt_src']=$mntPreleve;
		  $dateBenef[0]['frais']= NULL;

		  $data_transfert=$data_virement;
		  $data_transfert['frais_transfert']=$mntFraisTransfert;
      if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] != null)){
        $data_transfert['frais_minimum2retrait']=$frais_duree_minimum2retrait;
      }
		  $data_transfert['id_his']=
		  $erreur->param['id'];
      print_recu_transfert_comptes($data_donneur_or,$dateBenef,$data_transfert);


      if (isset($SESSION_VARS['id_dem'])) {
        $erreur2 = updateTransfertAttenteEtat($SESSION_VARS['id_dem'], 3, "Demande autorisation de transfert: Payé", $erreur->param);

        if ($erreur2->errCode == NO_ERR) {
          // Commit
          $dbHandler->closeConnection(true);
          //unset($SESSION_VARS['id_dem']);
        }
      }
         //

      // Message de confirmation
      $html_msg =new HTML_message(_("Confirmation de transfert sur un compte"));
      $messageFinal=_("Montant prélevé sur le compte source")." ".$mntPreleve." <br />";
      $messageFinal.=_("Montant déposé sur le compte destination")." ". $mnt_reel." <br />";
      if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] != null)){
        $messageFinal.=_("Frais de la duré minimum entre deux retraits : ")." ". $frais_duree_minimum2retrait." <br />";
      }

      $messageFinal.=_("Frais de transfert prélevés sur le compte source")." ". $mntFraisTransfert;
      $messageFinal .= "<br /><br />".("N° de transaction")." : <B><CODE>".sprintf("%09d", $erreur->param['id'])."</CODE></B>";
      $html_msg->setMessage($messageFinal);
      $html_msg->addButton("BUTTON_OK", 'Gen-10');
      $html_msg->buildHTML();
      echo $html_msg->HTML_code;
    } else {
    	sendMsgErreur (_("Echec de transfert sur un compte.")." ",$erreur,'Tcp-1' );
    }

  } // fin si transfert externe
  else if ($SESSION_VARS["TypeTransfert"] == 4) { // Si transfert groupé
    $data_transfert['sens'] = "---";
    $data_transfert['remarque'] = $SESSION_VARS['remarque'];
    $data_transfert['communication'] = $SESSION_VARS['communication'];

    // Si le compte source paie les frais de transfet
    if ($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 1)
      $frais_transfert = $SESSION_VARS['frais_transfert']['mnt_frais'];
    elseif($SESSION_VARS['frais_transfert']['type_cpte_preleve'] == 2) { //frais payés par les comptes de destination
      $frais_transfert = 0; // pas de frais payés par le compte source
      // récupération des frais des comptes de destination éventuellement modifiés
      if (check_access(299))
        foreach($SESSION_VARS['DATA'] as $key=>$value) {
        if (isset($ {'frais'.$key}))
          $SESSION_VARS['DATA'][$key]['mnt_frais'] = recupMontant($ {'frais'.$key});
      }
    }

    // Transfert des montants
    if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || isset($SESSION_VARS['id_dem'])) {
      $infoRetraitAttenteVerif = getTransfertAttenteAutorise($SESSION_VARS['id_dem'], $global_id_client);
      if (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] == null){
        $erreur = transfertCpteGroupe($SESSION_VARS["NumCpteSource"], $SESSION_VARS["DATA"], $SESSION_VARS['id_mandat'], $data_transfert, $frais_transfert);
      }else{
        $erreur = transfertCpteGroupe($SESSION_VARS["NumCpteSource"], $SESSION_VARS["DATA"], $SESSION_VARS['id_mandat'], $data_transfert, $frais_transfert,true);
      }

    }else{
      $erreur = transfertCpteGroupe($SESSION_VARS["NumCpteSource"], $SESSION_VARS["DATA"], $SESSION_VARS['id_mandat'], $data_transfert,$frais_transfert);
    }

    //prélèvement des frais en attente s'il ya lieu
    foreach($SESSION_VARS["DATA"] as $key => $value) {
	    //prélèvement des frais en attente si solde_disponible > montant_frais
	    $prelevement_frais = false;
	    $num_compte = $value["id_cpte"];
	    $InfoCpte = getAccountDatas($num_compte);
	    $mnt_frais_attente = 0;
	    //Y a t-il des frais en attente sur le compte ?
	    if(hasFraisAttenteCompte($num_compte)){
	    	$result = getFraisAttenteCompte($num_compte);
	     	$liste_frais_attente = $result->param;
	     	//Pour chaque frais en attente
	     	foreach($liste_frais_attente as $key=>$frais_attente) {
	     		//Recupération du solde disponible sur le compte
	     		$solde_disponible = getSoldeDisponible($num_compte);
	     		$montant_frais = $frais_attente['montant'];
	     		$type_frais = $frais_attente['type_frais'];
	     		$date_frais = $frais_attente['date_frais'];
	     		$comptable = array();//pour passage ecritures
	     		//vois si le solde disponible est suffisant pour prélever les frais
		     	if($solde_disponible >= $montant_frais){
		     		$erreurs = paieFraisAttente($num_compte, $type_frais, $montant_frais, $comptable);
			      if ($erreurs->errCode != NO_ERR){
			       	return $erreurs;
			      }
			      //Suppression dans la table des frais en attente
			      $sql = "DELETE FROM ad_frais_attente WHERE id_cpte = $num_compte AND date(date_frais) = date('$date_frais') AND type_frais = $type_frais;";
			      $result = executeDirectQuery($sql);
			      if ($result->errCode != NO_ERR){
			       	return new ErrorObj($result->errCode);
			      }
			      $prelevement_frais = true;
			      //memoriser montant des frais prélevés
		     		$mnt_frais_attente += $montant_frais;
		     		//Historiser le prelevement
			      $myErr = ajout_historique(87, $InfoCpte["id_titulaire"],'', $global_nom_login, date("r"), $comptable);
						if ($myErr->errCode != NO_ERR) {
						   $dbHandler->closeConnection(false);
						   return $myErr;
						}
		   	  }
	      }
	    }
    }

    if ($erreur->errCode == NO_ERR) {
      setMonnaieCourante($SESSION_VARS['devise_source']); // affichage dans la devise du compte source
      $mntPreleve = afficheMontant($SESSION_VARS["mnt_total"],TRUE);
      $mntFraisTransfert = afficheMontant($frais_transfert,TRUE);
      $compteSource = getAccountDatas($SESSION_VARS["NumCpteSource"]);
      $frais_duree_minimum2retrait = afficheMontant($compteSource['frais_duree_min2retrait'],true);

      // Message de confirmation
      $html_msg = new HTML_message(_("Confirmation de transfert sur un compte"));
      $messageFinal="<ul><li>".sprintf(_("Montant du transfert prélevé sur le compte source : %s, frais de transfert prélevés : %s"), $mntPreleve, $mntFraisTransfert)."</li><br />";
      if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] != null)) {
        $messageFinal .= "<li>" . sprintf(_("Frais de la duré minimum entre deux retraits : %s "), $frais_duree_minimum2retrait) . "</li>";
      }
      $i=0;
      foreach ($SESSION_VARS["DATA"] as $key => $TRANS) {
        setMonnaieCourante($TRANS['devise']); // affichage dans la devise du compte source
        $montant = afficheMontant($TRANS["mnt_dest"], TRUE);
        $frais = afficheMontant($TRANS["mnt_frais"], TRUE);
        $messageFinal.="<li>"._("Montant déposé sur le compte")." ".$TRANS["num_complet_cpte"]." : $montant, "._("frais prélevés")." : $frais </li><br/>";
        //pr le recu
        $dateBenef[$i]['id_client']=$TRANS['id_client'];
		  	$dateBenef[$i]['nom_client']=$TRANS['nom_client'];
		  	$dateBenef[$i]['num_complet_cpte']=$TRANS["num_complet_cpte"];
		  	$dateBenef[$i]['mnt_dest']=$montant;
		  	setMonnaieCourante($SESSION_VARS['devise_source']);
		  	$dateBenef[$i]['mnt_src']=afficheMontant($TRANS["mnt_src"], TRUE);
		  	$dateBenef[$i]['frais']=$frais;
		  	$i++;
      }

      $messageFinal .= "</ul>";
      $messageFinal .= "<br /><br />"._("N° de transaction")." : <B><CODE>".sprintf("%09d", $erreur->param)."</CODE></B>";

      $data_donneur_or=array();
      $data_donneur_or['nom_client']=getNomClient($global_id_client);
      if ($SESSION_VARS['id_mandat'] != null && $SESSION_VARS['id_mandat'] == 'CONJ'){
        $data_donneur_or['donneur_ordre']=$SESSION_VARS['mandat']['CONJ'];
      }
      if($SESSION_VARS['id_mandat'] != null && $SESSION_VARS['id_mandat'] != 'CONJ'){
        $info_mandataire = getInfosMandat($SESSION_VARS['id_mandat']);
        $data_donneur_or['donneur_ordre']=$info_mandataire['denomination'];
      }
      $data_donneur_or['num_cpte']=$compteSource['num_complet_cpte'];
      $data_donneur_or['id_prod'] = $compteSource["id_prod"];
      setMonnaieCourante($SESSION_VARS['devise_source']);
      $data_donneur_or['mnt']=afficheMontant($SESSION_VARS["mnt_total"],TRUE);
      //$data_donneur_or['solde']=
      if ($SESSION_VARS['ERR_DUREE_MIN_RETRAIT'] == 't' || (isset($SESSION_VARS['id_dem']) && $infoRetraitAttenteVerif['commission_duree_2retrait'] != null)){
        $data_transfert['frais_minimum2retrait']=$frais_duree_minimum2retrait;
      }
      $data_transfert['frais_transfert']=$mntFraisTransfert;
      $data_transfert['id_his']=$erreur->param;
      $data_transfert['date_virement']=$data_virement['date_piece'];
      if( $frais_transfert>0){
      	$data_transfert['TypeTransfert']=afficheMontant( $frais_transfert ,TRUE);
      }
      print_recu_transfert_comptes($data_donneur_or,$dateBenef,$data_transfert);

      if (isset($SESSION_VARS['id_dem'])) {
        $erreur2 = updateTransfertAttenteEtat($SESSION_VARS['id_dem'], 3, "Demande autorisation de transfert: Payé", $erreur->param);

        if ($erreur2->errCode == NO_ERR) {
          // Commit
          $dbHandler->closeConnection(true);
          unset($SESSION_VARS['id_dem']);
        }
      }

      $html_msg->setMessage($messageFinal);
      $html_msg->addButton("BUTTON_OK", 'Gen-10');
      $html_msg->buildHTML();
      echo $html_msg->HTML_code;
    } else {
      $html_err = new HTML_erreur(_("Echec de transfert sur un compte."));
      $html_err->setMessage("Erreur : ".$error[$erreur->errCode]." - ".$erreur->param);
      $html_err->addButton("BUTTON_OK", 'Tcp-1');
      $html_err->buildHTML();
      echo $html_err->HTML_code;
    }
  }

  // On vérifie si le client n'est plus débiteur
  if (!isClientDebiteur($global_id_client))
    $global_client_debiteur = false;


}
//---------------------------------------------------------------------
//----------- Tcp-6 Insertion du blocage par autorisation -------------
//---------------------------------------------------------------------
else if ($global_nom_ecran == "Tcp-6") {
  global $global_id_agence,$global_id_client;

  $transfert_id_ag = $m_agc;
  $transfert_id_client_src = $global_id_client;
  $transfert_id_cpte_client_src = $SESSION_VARS['NumCpteSource'];
  $transfert_montant_transfert = $montant_transfert;
  $transfert_etat_transfert = 1;
  $transfert_type_transfert = $SESSION_VARS['TypeTransfert'];

  if ($transfert_type_transfert == 1){
    $transfert_id_client_dest = $global_id_client;
    $transfert_id_cpte_client_dest = $SESSION_VARS['CpteMemeClient'];
  }
  elseif ($transfert_type_transfert == 2){
    $transfert_id_client_dest = $SESSION_VARS['num_client'];
    $transfert_id_cpte_client_dest = $SESSION_VARS['cpt_dest'];
  }elseif ($transfert_type_transfert == 3){
    $transfert_id_ben = $SESSION_VARS['id_ben'];
    $transfert_id_ben_cpte = $SESSION_VARS['cpteDest']['num_complet_cpte'];
    $transfert_id_correspondant = $SESSION_VARS['id_correspondant'];
  }
  if (isset($SESSION_VARS['com_mini_2retrait'])){
    $commission_duree_2retrait = $SESSION_VARS['com_mini_2retrait'];
  }else{
    $commission_duree_2retrait = null;
  }
  $transfert_type_frais_prelev = $SESSION_VARS['frais_transfert']['type_cpte_preleve'];
  $transfert_mnt_frais_type = $SESSION_VARS['frais_transfert']['type_mnt_frais'];
  $transfert_id_cpte_frais_transfert_prelev = $SESSION_VARS['frais_transfert']['cpte_preleve'];
  $transfert_devise_cpte_frais = $SESSION_VARS['frais_transfert']['devise_cpte_frais'];
  $transfert_mnt_frais = $SESSION_VARS['frais_transfert']['mnt_frais'];
  $transfert_devise_frais = $SESSION_VARS['frais_transfert']['devise_frais'];
  $transfert_num_chq_virement = $num_chq;
  $transfert_date_chq_virement = $date_chq;
  $transfert_type_retrait = $type_retrait;
  $transfert_id_mandat = $mandat;
  $transfert_communication = $communication;
  $transfert_remarque = $remarque;
  $transfert_type_piece = $SESSION_VARS['type_piece'];
  if (isset($SESSION_VARS['mnt_total1'])){
    $transfert_mnt_total = $SESSION_VARS['mnt_total1'];
  }
  else {
    $transfert_mnt_total = $SESSION_VARS['mnt_total'];
  }

  if ($transfert_type_transfert == 2 || $transfert_type_transfert == 1){
    if ($transfert_type_piece == 3){
      $erreur = insertTransfertAttente($global_id_agence,$transfert_id_client_src,	$transfert_id_cpte_client_src,	$transfert_montant_transfert,	$transfert_etat_transfert,	$transfert_type_transfert,	$transfert_id_client_dest,	$transfert_id_cpte_client_dest,null,null,null,null,$transfert_type_frais_prelev,	$transfert_mnt_frais_type,	$transfert_id_cpte_frais_transfert_prelev,	$transfert_devise_cpte_frais,	$transfert_mnt_frais,	$transfert_devise_frais,	$transfert_type_piece,null,null,	$transfert_type_retrait,$transfert_id_mandat,	$transfert_communication,	$transfert_remarque,null,$global_nom_login,null,null,null,$commission_duree_2retrait);
    }
    else {
      $erreur = insertTransfertAttente($global_id_agence,$transfert_id_client_src,	$transfert_id_cpte_client_src,	$transfert_montant_transfert,	$transfert_etat_transfert,	$transfert_type_transfert,	$transfert_id_client_dest,	$transfert_id_cpte_client_dest,null,null,null,null,$transfert_type_frais_prelev,	$transfert_mnt_frais_type,	$transfert_id_cpte_frais_transfert_prelev,	$transfert_devise_cpte_frais,	$transfert_mnt_frais,	$transfert_devise_frais,	$transfert_type_piece,$transfert_num_chq_virement,$transfert_date_chq_virement,	$transfert_type_retrait,$transfert_id_mandat,	$transfert_communication,	$transfert_remarque,null,$global_nom_login,null,null,null,$commission_duree_2retrait);
    }

  }
  elseif ($transfert_type_transfert == 3) {
    if ($transfert_type_piece == 3){
      $erreur = insertTransfertAttente($global_id_agence,$transfert_id_client_src,	$transfert_id_cpte_client_src,	$transfert_montant_transfert,	$transfert_etat_transfert,	$transfert_type_transfert,	null,	null,$transfert_id_ben,$transfert_id_ben_cpte,$transfert_id_correspondant,null,$transfert_type_frais_prelev,	$transfert_mnt_frais_type,	$transfert_id_cpte_frais_transfert_prelev,	$transfert_devise_cpte_frais,	$transfert_mnt_frais,	$transfert_devise_frais,	$transfert_type_piece,null,null,	$transfert_type_retrait,$transfert_id_mandat,	$transfert_communication,	$transfert_remarque,null,$global_nom_login,null,null,null,$commission_duree_2retrait);
    }
    else{
      $erreur = insertTransfertAttente($global_id_agence,$transfert_id_client_src,	$transfert_id_cpte_client_src,	$transfert_montant_transfert,	$transfert_etat_transfert,	$transfert_type_transfert,	null,	null,$transfert_id_ben,$transfert_id_ben_cpte,$transfert_id_correspondant,null,$transfert_type_frais_prelev,	$transfert_mnt_frais_type,	$transfert_id_cpte_frais_transfert_prelev,	$transfert_devise_cpte_frais,	$transfert_mnt_frais,	$transfert_devise_frais,	$transfert_type_piece,$transfert_num_chq_virement,$transfert_date_chq_virement,	$transfert_type_retrait,$transfert_id_mandat,	$transfert_communication,	$transfert_remarque,null,$global_nom_login,null,null,null,$commission_duree_2retrait);
    }
  }
  else {
    $i = 1 ;
    $groupe_cli = '';
    foreach($SESSION_VARS['frais_transfert']['transfert_groupe'] as $value){
      $groupe_cli .= $value['num_client'].'-'.$value['mnt'].',';
      $i++;
    }
    $erreur = insertTransfertAttente($global_id_agence,$transfert_id_client_src,	$transfert_id_cpte_client_src,	$transfert_mnt_total,	$transfert_etat_transfert,	$transfert_type_transfert,	null,	null,null,null,null,$groupe_cli,$transfert_type_frais_prelev,	$transfert_mnt_frais_type,	null,	null,	$transfert_mnt_frais,	null,	null,null,null,	$transfert_type_retrait,$transfert_id_mandat,	$transfert_communication,	$transfert_remarque,null,$global_nom_login,null,null,null,$commission_duree_2retrait);
  }

  //Historiser la demande de transfert entre compte #695 REL-32
  $id_dmde_transfert = getDataTransfertAttente($global_id_client,$transfert_id_cpte_client_src,1);
  $id_dem = $id_dmde_transfert['max_id'];
  $myErr = ajout_historique(76, $transfert_id_client_src,'Demande Autorisation de Transfert No.'.$id_dem." Mise en attente", $global_nom_login, date("r"), null, null, null);
  if ($myErr->errCode != NO_ERR) {
    $html_err = new HTML_erreur("Echec lors de la demande autorisation de transfert.");

    $err_msg = $error[$myErr->errCode];

    $html_err->setMessage(sprintf("Erreur : %s !", $err_msg));

    $html_err->addButton("BUTTON_OK", 'Gen-8');

    $html_err->buildHTML();
    echo $html_err->HTML_code;
  }

  if ($erreur->errCode == NO_ERR) {

    $html_msg = new HTML_message("Confirmation demande autorisation de transfert");

    $html_msg->setMessage("La demande d'autorisation de transfert a été envoyée.");

    $html_msg->addButton("BUTTON_OK", 'Gen-8');

    $html_msg->buildHTML();
    echo $html_msg->HTML_code;


    /*$data_client_recu = getClientDatas($global_id_client);
    switch ($data_client_recu['statut_juridique']){
      case 1 :
        $nom = $data_client_recu["pp_nom"] . " " . $data_client_recu["pp_prenom"];
        break;
      case 2 :
        $nom = $data_client_recu["pm_raison_sociale"];
        break;
      case 3 :
        $nom = $data_client_recu["gi_nom"];
        break;
      case 4:
        $nom = $data_client_recu["gi_nom"];
        break;
    }

    $now = date("Y-m-d");
    $id_dmde_transfert = getDataTransfertAttente($global_id_client,$transfert_id_cpte_client_src,1);
    $id_dem = $id_dmde_transfert['max_id'];

    if ($transfert_type_transfert == 2){
      $data_client_recu = getClientDatas($transfert_id_client_dest);
      switch ($data_client_recu['statut_juridique']) {
        case 1 :
          $nom = $data_client_recu["pp_nom"] . " " . $data_client_recu["pp_prenom"];
          break;
        case 2 :
          $nom = $data_client_recu["pm_raison_sociale"];
          break;
        case 3 :
          $nom = $data_client_recu["gi_nom"];
          break;
        case 4:
          $nom = $data_client_recu["gi_nom"];
          break;
      }
      $nomClientDest = $nom;
      $num_cpte_client = $transfert_id_client_dest;
    }
    elseif ($transfert_type_transfert == 3) {
      $getDenomination = getTireurBenef($transfert_id_ben);
      $nomClientDest = $getDenomination['denomination'];
      $num_cpte_client = $transfert_id_ben;
    }
    else if ($transfert_type_transfert == 1){
      $num_cpte_client = $transfert_id_client_dest;
    }
    else {
      $num_cpte_client = '';
    }


    if ($transfert_type_transfert == 4) {
      $montant_transfert = $transfert_mnt_total;
    }
    else {
      $montant_transfert = $transfert_montant_transfert;
    }


    $data_client_emetteur_recu = getClientDatas($transfert_id_client_src);
    switch ($data_client_emetteur_recu['statut_juridique']) {
    case 1 :
    $nom_emetteur = $data_client_emetteur_recu["pp_nom"] . " " . $data_client_emetteur_recu["pp_prenom"];
    break;
    case 2 :
    $nom_emetteur = $data_client_emetteur_recu["pm_raison_sociale"];
    break;
    case 3 :
    $nom_emetteur = $data_client_emetteur_recu["gi_nom"];
    break;
    case 4:
    $nom_emetteur = $data_client_emetteur_recu["gi_nom"];
    break;
    }
    $nomClientEmetteur = $nom_emetteur;
    if ($transfert_type_transfert == 1){
      $nomClientDest = $nomClientEmetteur;
    }*/

    /*elseif ($transfert_type_transfert == 3) {
      $getDenomination = getTireurBenef($transfert_id_ben);
      $nomClientDest = $getDenomination['denomination'];
      $num_cpte_client = $id_dmde_transfert['id_cpte_ben'];
    }else {
      $num_cpte_client = '';
    }*/


    //print_recu_demande_autorisation_transfert($transfert_type_transfert,$nomClientEmetteur,$transfert_id_client_src,$nomClientDest,$num_cpte_client,$montant_transfert, $now , $global_nom_login, sprintf("%09d", $myErr->param)); // Prendre le numero de transaction au lieu de id demande #695 REL-32
  }else {
    $html_err = new HTML_erreur("Echec lors de la demande autorisation de transfert.");

    $err_msg = $error[$erreur->errCode];

    $html_err->setMessage(sprintf("Erreur : %s !", $err_msg));

    $html_err->addButton("BUTTON_OK", 'Gen-8');

    $html_err->buildHTML();
    echo $html_err->HTML_code;
  }

}


else signalErreur(__FILE__,__LINE__,__FUNCTION__); // "L'écran $global_nom_ecran n'a pas été trouvïé"
?>