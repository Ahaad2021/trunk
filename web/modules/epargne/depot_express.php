<?php
/*

Nom :
      depot_compte.php=>Dépôt sur un compte d'épargne

Description :
  Ce module crée 2 écrans :
  * Dcp-1 : Choix d'un compte sur lequel déposer
  * Dcp-2 : Traitement du dépôt et confirmation ou non de la création du compte

Auteur :
   HD-Créé le 04/02/2002
   Modifié par OL le 13/12/2004
*/

require_once 'lib/dbProcedures/epargne.php';
require_once 'lib/dbProcedures/credit_lcr.php';
require_once 'modules/epargne/recu.php';
require_once 'lib/dbProcedures/parametrage.php';
require_once 'lib/misc/divers.php';
require_once 'lib/html/FILL_HTML_GEN2.php';
require_once 'lib/misc/VariablesSession.php';
require_once 'lib/dbProcedures/billetage.php';

if ($global_nom_ecran == "Dex-1") {
	global $global_id_profil, $global_id_client;
  //----------------------------------- ECRAN Dex-1 : choix du compte et du type de dépôt --------------------
  //afficher la liste des comptes du client puis le montant à déposer et ne pas oublier les frais d'opérations sur compte éventuels

  $html = new HTML_GEN2(_("Dépôt express")); //Dépôt express

  //affichage de tous les comptes du client
  $compte = get_comptes_epargne($global_id_client);
  $compte = $compte[getBaseAccountID($global_id_client)];

  $html->addField("NumCpte", _("Numéro de compte"), TYPC_TXT);
  $html->setFieldProperties("NumCpte", FIELDP_DEFAULT, $compte["num_complet_cpte"]." ".$compte["intitule_compte"]);
  $html->setFieldProperties("NumCpte", FIELDP_IS_LABEL, true);

	$solde_disponible = getSoldeDisponible($compte["id_cpte"]);
	$access_solde = get_profil_acces_solde($global_id_profil, $compte["id_prod"]);
	$access_solde_vip = get_profil_acces_solde_vip($global_id_profil, $global_id_client);
  if(manage_display_solde_access($access_solde, $access_solde_vip)){
	  $html->addField("solde_dispo", _("Solde disponible"), TYPC_MNT);
	  $html->setFieldProperties("solde_dispo", FIELDP_DEFAULT, $solde_disponible);
	  $html->setFieldProperties("solde_dispo", FIELDP_IS_LABEL, true);

	  $html->addField("solde_reel", _("Solde réel"), TYPC_MNT);
	  $html->setFieldProperties("solde_reel", FIELDP_DEFAULT,$compte["solde"]);
	  $html->setFieldProperties("solde_reel", FIELDP_IS_LABEL, true);
  }

  $html->addField("type_depot", _("Type de dépôt"), TYPC_TXT);
  $html->setFieldProperties("type_depot", FIELDP_DEFAULT, "En espèces");
  $html->setFieldProperties("type_depot", FIELDP_IS_LABEL, true);

  $html->addField("mnt",_("Montant à déposer"),TYPC_MNT);
  $html->setFieldProperties("mnt", FIELDP_IS_REQUIRED, true);

  $html->addTable("adsys_produit_epargne", OPER_INCLUDE,array("frais_depot_cpt"));
  $html->setFieldProperties("frais_depot_cpt", FIELDP_IS_LABEL, true);
  $html->setFieldProperties("frais_depot_cpt", FIELDP_DEFAULT, $compte["frais_depot_cpt"]);
  $html->setFieldProperties("frais_depot_cpt", FIELDP_CAN_MODIFY, true);

  $ChkJS = "if (recupMontant(document.ADForm.frais_depot_cpt.value)-recupMontant(document.ADForm.mnt.value)>".$solde_disponible.")\n";
  $ChkJS.= "{msg+= '- "._("Le solde disponible du compte n\'est pas suffisant")."\\n'; ADFormValid = false;}\n";
  $html->addJS(JSP_BEGIN_CHECK, "JS",$ChkJS);

  $html->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
  $html->addFormButton(1, 2, "cancel", _("Annuler"), TYPB_SUBMIT);
  $html->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);
  $html->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Dex-2');
  $html->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-8');

  $html->buildHTML();
  echo $html->getHTML();
}

else if ($global_nom_ecran == "Dex-2") {	//--------------------------- ECRAN Dex-2 : dépôt express  -----------------------------------------

  //sauvegarder le type de dépôt et le n° de compte pour l'écran Dex-3
  $SESSION_VARS["NumCpte"] 	= getBaseAccountID($global_id_client); //Si retrait express

  // récupérer le infos sur le produit associé au compte sélectionné
  $InfoCpte = getAccountDatas($SESSION_VARS["NumCpte"]);
  $InfoProduit = getProdEpargne($InfoCpte["id_prod"]);
  $SESSION_VARS['type_depot'] 	= 1; // Dépot express
  $SESSION_VARS["mnt"] = recupMontant($mnt);
  if (isset($frais_depot_cpt))
    $SESSION_VARS["frais_depot_cpt"] = recupMontant($frais_depot_cpt);
  else
    $SESSION_VARS["frais_depot_cpt"] = $InfoProduit["frais_depot_cpt"];

  $html = new HTML_GEN2(_("Confirmation du montant à déposer"));

  $html->addField("mnt",_("Montant à déposer"),TYPC_MNT);
  $html->setFieldProperties("mnt", FIELDP_DEFAULT, $SESSION_VARS["mnt"]);
  $html->setFieldProperties("mnt", FIELDP_IS_LABEL, true);

  //Crontôler si le montant à déposer ne dépasse pas le montant plafond de depot autorisé s'il y a lieu
	  global $global_nom_login, $global_id_agence, $colb_tableau;
	  $info_login = get_login_full_info($global_nom_login);
	  $info_agence = getAgenceDatas($global_id_agence);
	  $msg = "";
	  if ($info_agence['plafond_depot_guichet'] == 't'){
	    if($info_login['depasse_plafond_depot'] == 'f' && $SESSION_VARS["mnt"] > $info_agence['montant_plafond_depot']){
	   		$msg = "<center>"._("Le montant dépasse le montant plafond de dépôt autorisé. Ce login n'est pas habilité à le faire.");
	   		$msg .= " "._("Veuillez contacter votre administrateur")."</center>";
			}
	  }
		if ($msg != "") {
			 $html = new HTML_erreur(_("Dépôt impossible "));
			 $html->setMessage($msg);
			 $html->addButton(BUTTON_OK, "Dcp-2");
			 $html->buildHTML();
			 echo $html->HTML_code;
			 exit();
		}

  $html->addField("mnt_reel",_("Montant déposé"),TYPC_MNT);
  $html->setFieldProperties("mnt_reel", FIELDP_IS_REQUIRED, true);
  $html->setFieldProperties("mnt_reel", FIELDP_HAS_BILLET, true);

  $ChkJS = "\t\tif (recupMontant(document.ADForm.mnt_reel.value) != recupMontant(document.ADForm.mnt.value))";
  $ChkJS.= "{\nmsg += '-"._("Le montant saisi ne correspond pas au montant à déposer")."\\n'; ADFormValid=false;};\n";
  $html->addJS(JSP_BEGIN_CHECK, "JS3",$ChkJS);

  $html->addField("frais_depot", _("Frais de dépot"), TYPC_MNT);
  $html->setFieldProperties("frais_depot", FIELDP_DEFAULT, $SESSION_VARS["frais_depot_cpt"]);
  $html->setFieldProperties("frais_depot", FIELDP_IS_LABEL, true);

  $html->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
  $html->addFormButton(1, 2, "retour", _("Retour"), TYPB_SUBMIT);
  $html->addFormButton(1, 3, "cancel", _("Annuler"), TYPB_SUBMIT);
  $html->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);
  $html->setFormButtonProperties("retour", BUTP_CHECK_FORM, false);
  
  $SESSION_VARS['envoi'] = 0;
  $html->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Dex-3');
  $html->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN,'Dex-1');
  $html->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-8');

  $html->buildHTML();
  echo $html->getHTML();
} else if ($global_nom_ecran == "Dex-3") {
	
// capturer des types de billets de la bd et nombre de billets saisie par l'utilisateur
	$valeurBilletArr = array();
	$InfoCpte = getAccountDatas($SESSION_VARS["NumCpte"]);
    $dev = ($InfoCpte["devise"]);
	$listTypesBilletArr = buildBilletsVect($dev);
	$total_billetArr = array();
	
	//insert nombre billet into array
	for($x = 0; $x < 20; $x++) {
		if(isset($_POST['mnt_reel_billet_'.$x]) && trim($_POST['mnt_reel_billet_'.$x])!='') {
			$valeurBilletArr[] = trim($_POST['mnt_reel_billet_'.$x]);
		}
		else{
			if(isset($listTypesBilletArr[$x]['libel']) && trim($listTypesBilletArr[$x]['libel'])!='') {
				$valeurBilletArr[] = 'XXXX';
			}
		}
	}
		// calcul total pour chaque billets
	for($x = 0; $x < 20; $x ++) {
		
		if ($valeurBilletArr [$x] == 'XXXX') {
			$total_billetArr [] = 'XXXX';
		} else {
			if (isset ( $listTypesBilletArr [$x] ['libel'] ) && trim ( $listTypesBilletArr [$x] ['libel'] ) != '' && isset ( $valeurBilletArr [$x] ['libel'] ) && trim ( $valeurBilletArr [$x] ['libel'] ) != '') {
				$total_billetArr [] = ( int ) ($valeurBilletArr [$x]) * ( int ) ($listTypesBilletArr [$x] ['libel']);
			}
		}
	}
	
	//controle d'envoie du formulaire
	$SESSION_VARS['envoi']++;
	if( $SESSION_VARS['envoi'] != 1 ) {
		$html_err = new HTML_erreur(_("Confirmation"));
	    $html_err->setMessage(_("Donnée dèjà envoyée"));
	    $html_err->addButton("BUTTON_OK", 'Gen-8');
	    $html_err->buildHTML();
	    echo $html_err->HTML_code;
	    exit();
	} //fin controle envoie formulaire
	
  //confirmation du versement


  //--------------------------------- ECRAN Dcp-4 : mouvement des comptes ------------------------------------

  //mouvement des comptes avec gestion des frais d'opérations sur compte s'il y lieu
  //$NumCpte et $mnt ont été postés de l'écran précédent; $mnt est le montant net à verser non compris les frais d'opération
  //Vérification si le client n'est pas "débiteur"

  // recupére les information sur le compte

  $InfoCpte = getAccountDatas($SESSION_VARS["NumCpte"]);
  $InfoProduit = getProdEpargne($InfoCpte["id_prod"]);
  $SESSION_VARS["mnt"] = recupMontant($mnt_reel);
  // remplacer les frais de dépot par la valeur saisie s'il y'a possibilité de modification de frais
  if ( check_access(299) )
    $InfoProduit["frais_depot_cpt"] = $SESSION_VARS["frais_depot_cpt"];

  $type_depot=1; //Pour dépôt express

  $erreur = depot_cpte($global_id_guichet, $SESSION_VARS["NumCpte"], $SESSION_VARS["mnt"],$InfoProduit, $InfoCpte, NULL, $type_depot); //mnt = montant net à déposer

  $isbilletage = getParamAffichageBilletage();

  if ($erreur->errCode == NO_ERR) {
    $id_his = $erreur->param['id'];

    $num_compte = $SESSION_VARS["NumCpte"]; debug($num_compte,"num cpte");

    $remboursement_cap_lcr = false;
    $total_mnt_cap_lcr = 0;
    //Ticket pp178
    // [Ligne de crédit] : Remboursement Capital
    // $lcrErr = rembourse_cap_lcr(date("d/m/Y"), $num_compte);
    $lcrErr = rembourse_cap_lcr(date("d/m/Y"), $num_compte, $SESSION_VARS['mnt'], $id_his);

    if ($lcrErr->errCode == NO_ERR) {
      $total_mnt_cap_lcr = $lcrErr->param[1];

      if ($total_mnt_cap_lcr > 0) {
        $remboursement_cap_lcr = true;
      }
    }

    //prélèvement des frais en attente si solde_disponible > montant_frais
    $prelevement_frais = false;
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
          debug($solde_disponible,"solde_disponible");debug($frais_attente," frais");
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
          $myErr = ajout_historique(86, $InfoCpte["id_titulaire"],'', $global_nom_login, date("r"), $comptable, null, $id_his);
          if ($myErr->errCode != NO_ERR) {
            $dbHandler->closeConnection(false);
            return $myErr;
          }
        }
      }
    }

    $infos = get_compte_epargne_info($SESSION_VARS['NumCpte']);
    //print_recu_depot($global_id_client, $global_client, $SESSION_VARS['mnt'], $InfoProduit, $infos, $erreur->param['id'], NULL, NULL, NULL, $mnt_frais_attente);
    print_recu_depot($global_id_client, $global_client, $SESSION_VARS['mnt'], $InfoProduit, $infos, $id_his, $data['id_pers_ext'],$SESSION_VARS["remarque"],$SESSION_VARS["communication"], $mnt_frais_attente, $SESSION_VARS['id_mandat'], $listTypesBilletArr, $valeurBilletArr, $global_langue_rapport, $total_billetArr, true,$isbilletage);
    
    $html_msg =new HTML_message(_("Confirmation de dépôt sur un compte"));
//    $message ="Le compte a été crédité de : ".afficheMontant($SESSION_VARS["mnt"] - $InfoProduit["frais_depot_cpt"], true);
    setMonnaieCourante($InfoCpte['devise']);
    $message =_("Montant déposé sur le compte : ").afficheMontant($SESSION_VARS['mnt'], true);
    if (isset($CHANGE)) {
      // Impression du bordereau de change
      $cpteSource = getAccountDatas($SESSION_VARS['NumCpte']);
      $cpteGuichet = getCompteCptaGui($global_id_guichet);
      $cpteDevise = $cpteGuichet.".".$SESSION_VARS['mnt_cv']['devise'];
      $SESSION_VARS["mnt_cv"]["source_achat"] = $cpteSource["num_complet_cpte"];//." ".$cpteSource["intitule_compte"];
      $SESSION_VARS["mnt_cv"]["dest_vente"] = $global_guichet;
      printRecuChange($id_his, $SESSION_VARS["mnt_cv"]["cv"],$SESSION_VARS["mnt_cv"]["devise"],$SESSION_VARS["mnt_cv"]["source_achat"],$SESSION_VARS["mnt"],$global_monnaie_courante,$SESSION_VARS["mnt_cv"]["comm_nette"],$SESSION_VARS["mnt_cv"]["taux"],$SESSION_VARS["mnt_cv"]["reste"],$SESSION_VARS["mnt_cv"]["dest_vente"]);
      setMonnaieCourante($CHANGE['devise']);
      $message .= "<br>"._("Montant déposé au guichet : ").afficheMontant($CHANGE['cv'], true);
    }
    if ($SESSION_VARS['frais_depot_cpt']>0) {
       setMonnaieCourante($InfoCpte['devise']);
       $message .="<br>"._("Frais de dépôt : ").afficheMontant($SESSION_VARS['frais_depot_cpt'], true);
    }
    if ($prelevement_frais) {
        $message .= "<br>"._("Des frais en attente ont été débités de votre compte de base pour un montant de")." :<br>";
        $message .= afficheMontant($mnt_frais_attente, true);
    }
    if ($remboursement_cap_lcr) {
        $message .= "<br>"._("Ligne de crédit : Le capital restant dû a été débité de votre compte de base pour un montant de")." :<br>";
        $message .= afficheMontant($total_mnt_cap_lcr, true);
    }
    // On vérifie si le client n'est plus débiteur
    if (!isClientDebiteur($global_id_client)){
    	$global_client_debiteur = false;
    }
    $message .= "<br /><br />"._("N° de transaction")." : <B><code>".sprintf("%09d", $erreur->param['id'])."</code></B>";
    $html_msg->setMessage($message);
    $html_msg->addButton("BUTTON_OK", 'Gen-8');
    $html_msg->buildHTML();
    echo $html_msg->HTML_code;
  } else {
    $html_err = new HTML_erreur(_("Echec du dépôt sur un compte"));
    $html_err->setMessage(_("Erreur")." : ".$error[$erreur->errCode]."<br />"._("Paramètre")." : ".$erreur->param);
    $html_err->addButton("BUTTON_OK", 'Dex-2');
    $html_err->buildHTML();
    echo $html_err->HTML_code;
  }
} else signalErreur(__FILE__,__LINE__,__FUNCTION__); // "L'écran $global_nom_ecran n'a pas été trouvé"
?>
