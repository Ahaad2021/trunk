<?php
/* vim: set expandtab softtabstop=2 shiftwidth=2 foldmethod=marker: */

/**
 * [125] Déboursement des fonds
 * Cette opération comprends les écrans :
 * - Dbd-1 : sélection d'un dossier de crédit
 * - Dbd-2 : déboursement d'un crédit
 * - Dbd-3 : échéancier réel
 * - Dbd-4 : transfert des garanties
 * - Dbd-5 : commission
 * - Dbd-6 : tranfert du montant des assurances
 * - Dbd-7 : transfort des fonds sur le compte de base
 * - Dbd-8 : confirmation
 * - Dbd-9 : impression de l'échéancier
 * - Dbd-10 : Perception des frais de dossier
 * - Dbd-11 : Mode de déboursement
 * @package Credit
 */

require_once 'lib/html/HTML_GEN2.php';
require_once 'lib/html/FILL_HTML_GEN2.php';
require_once 'lib/dbProcedures/credit.php';
require_once 'lib/dbProcedures/epargne.php';
require_once 'lib/dbProcedures/tarification.php';
require_once 'lib/misc/VariablesGlobales.php';
require_once 'lib/algo/ech_theorique.php';
require_once 'lib/html/echeancier.php';
require_once 'modules/rapports/xml_credits.php';

/*{{{ Dbd-1 : Sélection d'un dossier de crédit */
if ($global_nom_ecran == "Dbd-1") {
  unset($SESSION_VARS['infos_doss']);
  // Récupération des infos du client (ou du GS)
  $SESSION_VARS['infos_client'] = getClientDatas($global_id_client);

  $dossiers = array(); // tableau contenant les infos sur les dossiers réels (dans ad_dcr) et fictifs (ad_dcr_grp_sol)
  $liste = array(); // Liste box des dossiers à afficher
  $i = 1; // clé de la liste box

  //en fonction du choix du numéro de dossier, afficher les infos avec le onChange javascript
  $codejs = "\n\nfunction getInfoDossier() {";

  // Récupération des dossiers individuels réels (dans ad_dcr) à l'état accepté ou en déboursement progressif
  $whereCl = " AND is_ligne_credit='f' AND mode_calc_int!=5 AND (etat=2 OR etat=13)";
  $dossiers_reels = getIdDossier($global_id_client, $whereCl);
  if (is_array($dossiers_reels))
    foreach($dossiers_reels as $id_doss=>$value)
    if ($value['gs_cat'] != 2) { // les dossiers pris en groupe doivent être déboursés via le groupe
      $date = pg2phpDate($value["date_dem"]); //Fonction renvoie  des dates au format jj/mm/aaaa
      $liste[$i] ="n° $id_doss du $date"; //Construit la liste en affichant N° dossier + date
      $dossiers[$i] = $value;

      $codejs .= "\n\tif (document.ADForm.HTML_GEN_LSB_id_doss.value ==$i)\n\t";
      $codejs .= "{\n\t\tdocument.ADForm.id_prod.value =\"" . $value["libelle"] . "\";";
      $codejs .= "}\n";
      $i++;
    }

  // SI GS, récupérer les dossiers des membres dans le cas de dossiers multiples
  if ($SESSION_VARS['infos_client']['statut_juridique'] == 4) {
    // Récupération des dossiers fictifs du groupe avec dossiers multiples : cas 2
    $whereCl = " WHERE id_membre=$global_id_client and gs_cat=2";
    $dossiers_fictifs = getCreditFictif($whereCl);

    // Récupération des dossiers des membres
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);

    // Ajout dans la liste box, pour chaque dossier fictif du GS, les dossiers réels des membres du GS
    foreach($dossiers_fictifs as $id=>$value) {
      // Récupération, pour chaque dossier fictif, des dossiers réels associés : CS avec dossiers multiples
      $infos = '';
      foreach($dossiers_membre as $id_doss=>$val)
      if (($val['is_ligne_credit'] != 't') AND ($val['id_dcr_grp_sol'] == $id) AND (($val['etat'] == 2) OR ($val['etat'] == 13))) {
        $date_dem = $date = pg2phpDate($val['date_dem']);
        $infos .= "n° $id_doss "; // on affiche les numéros de dossiers réels sur une ligne
      }
      if ($infos != '') { // Si au moins on 1 dossier
        $infos .= "du $date_dem";
        $liste[$i] = $infos;
        $dossiers[$i] = $value; // on garde les infos du dossier fictif

        $codejs .= "\n\tif (document.ADForm.HTML_GEN_LSB_id_doss.value ==$i)\n\t";
        $codejs .= "{\n\t\tdocument.ADForm.id_prod.value ='" . $val["libelle"] . "';";
        $codejs .= "}\n";
        $i++;
      }
    }
  }

  $SESSION_VARS['dossiers'] = $dossiers;
  $codejs .= "\n\tif (document.ADForm.HTML_GEN_LSB_id_doss.value =='0') {";
  $codejs .= "\n\t\tdocument.ADForm.id_prod.value='';";
  $codejs .= "\n\t}\n";
  $codejs .= "}\ngetInfoDossier();";

  // Création du formulaire
  $Myform = new HTML_GEN2(_("Sélection d'un dossier de crédit"));
  $Myform->addField("id_doss",_("Dossier de crédit"), TYPC_LSB);
  $Myform->addField("id_prod",_("Type produit de crédit"), TYPC_TXT);

  $Myform->setFieldProperties("id_prod", FIELDP_IS_LABEL, true);
  $Myform->setFieldProperties("id_prod", FIELDP_IS_REQUIRED, false);
  $Myform->setFieldProperties("id_prod", FIELDP_WIDTH, 30);

  $Myform->setFieldProperties("id_doss",FIELDP_ADD_CHOICES,$liste);
  $Myform->setFieldProperties("id_doss", FIELDP_JS_EVENT, array("onChange"=>"getInfoDossier();"));
  $Myform->addJS(JSP_FORM, "JS3", $codejs);

  // Javascript : vérifie qu'un dossier est sélectionné
  $JS_1 = "";
  $JS_1.="\t\tif(document.ADForm.HTML_GEN_LSB_id_doss.options[document.ADForm.HTML_GEN_LSB_id_doss.selectedIndex].value==0){ msg+=' - "._("Aucun dossier sélectionné")." .\\n';ADFormValid=false;}\n";
  $Myform->addJS(JSP_BEGIN_CHECK,"testdos",$JS_1);

  // Ordre d'affichage des champs
  $order = array("id_doss","id_prod");

  // les boutons ajoutés
  $Myform->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $Myform->addFormButton(1,2,"annuler",_("Retour Menu"),TYPB_SUBMIT);

  // Propriétés des boutons
  $Myform->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $Myform->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-11");
  $Myform->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

  $Myform->setOrder(NULL,$order);
  $Myform->buildHTML();
  echo $Myform->getHTML();

}
/*}}}*/


/*{{{ Dbd-11 : Mode de déboursement */
else if  ($global_nom_ecran == 'Dbd-11') {
	 if (strstr($global_nom_ecran_prec,"Dbd-1")) {
	 	$en_debours_progressif = false;
    // Récupération des dossiers à approuver
    if ($SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['gs_cat'] != 2 ) { // dossier individuel
      // Les informations sur le dossier
      $id_doss = $SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['id_doss'];
      $id_prod = $SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['id_prod'];
      $SESSION_VARS['infos_doss'][$id_doss] = getDossierCrdtInfo($id_doss); // infos du dossier reel
      $SESSION_VARS['infos_doss'][$id_doss]['last_etat'] = $SESSION_VARS['infos_doss'][$id_doss]['etat'];
      $SESSION_VARS['infos_doss'][$id_doss]['cre_mnt_octr'] = $SESSION_VARS['infos_doss'][$id_doss]['cre_mnt_octr'];

      // Infos dossiers fictifs dans le cas de GS avec dossier unique
      if ($SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['gs_cat'] == 1) {
        $whereCond = " WHERE id_dcr_grp_sol = $id_doss";
        $SESSION_VARS['doss_fic'] = getCreditFictif($whereCond);
      }
      if($SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['etat'] == 13)
        $en_debours_progressif = true;
    }
    elseif($SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['gs_cat'] == 2 ) { // GS avec dossiers multiples

      // infos dossier fictif
      $id_doss_fic = $SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['id'];  // id du dossier fictif (dossier du groupe)
      $whereCond = " WHERE id = $id_doss_fic";
      $SESSION_VARS['doss_fic'] = getCreditFictif($whereCond);

      // dossiers réels des membre du GS
      $dossiers_membre = getDossiersMultiplesGS($global_id_client);
      foreach($dossiers_membre as $id_doss=>$val) {
        if ($val['id_dcr_grp_sol'] == $SESSION_VARS['dossiers'][$HTML_GEN_LSB_id_doss]['id'] and (($val['etat'] == 2) OR ($val['etat'] == 13))) {
          $SESSION_VARS['infos_doss'][$id_doss] = $val; // infos d'un dossier reel d'un membre
          $SESSION_VARS['infos_doss'][$id_doss]['last_etat'] = $SESSION_VARS['infos_doss'][$id_doss]['etat'];
          $SESSION_VARS['infos_doss'][$id_doss]['cre_mnt_octr'] = $SESSION_VARS['infos_doss'][$id_doss]['cre_mnt_octr'];
          $id_prod = $SESSION_VARS['infos_doss'][$id_doss]['id_prod']; // même produit pour tous les dossiers
        }
        if($SESSION_VARS['infos_doss'][$id_doss]['etat'] == 13)
        	$en_debours_progressif = true;
      }
    }
	 }

	// Les informations sur le produit de crédit
    $Produit = getProdInfo(" where id =".$id_prod, $id_doss);
    $SESSION_VARS['infos_prod'] = $Produit[0];
	// Création du formulaire
  $Myform = new HTML_GEN2(_("Sélection du mode de déboursement"));
  $Myform->addField("mode_debour",_("Mode de déboursement"), TYPC_LSB);
  if($en_debours_progressif){ // Si le dossier est en etat de deboursement progressif
  	$Myform->setFieldProperties("mode_debour",FIELDP_ADD_CHOICES,array(2=> _("Déboursement par tranche")));
  	$Myform->setFieldProperties("mode_debour", FIELDP_DEFAULT, 2);
	}
  else{
  	$Myform->setFieldProperties("mode_debour",FIELDP_ADD_CHOICES,array(1=> _("Déboursement complet"), 2=> _("Déboursement par tranche")));
  	$Myform->setFieldProperties("mode_debour", FIELDP_DEFAULT, 1);
  }
  $Myform->setFieldProperties("mode_debour", FIELDP_IS_REQUIRED, true);

	if (profil_has_guichet($global_id_profil)) {
      	$dest_debour = array(1 => _("Guichet"), 2 => _("Compte lié"), 3 => _("Par chèque"));
    } else {
	$dest_debour = array(2 => "Compte lié", 3 => _("Par chèque"));
	}
	$Myform->addField("dest_debour",_("Destination du déboursement"), TYPC_LSB);
	$Myform->setFieldProperties("dest_debour", FIELDP_ADD_CHOICES, $dest_debour);
	$Myform->setFieldProperties("dest_debour", FIELDP_DEFAULT, 2);
  $Myform->setFieldProperties("dest_debour", FIELDP_IS_REQUIRED, true);

	 //les boutons ajoutés
  $Myform->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $Myform->addFormButton(1,2,"annuler",_("Annuler"),TYPB_SUBMIT);

  // Propriétés des boutons
  $Myform->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $Myform->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-2");
  $Myform->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
  $Myform->buildHTML();
  echo $Myform->getHTML();
}
/*}}}*/


/*{{{ Dbd-2 : Déboursement d'un crédit */
else if ($global_nom_ecran == "Dbd-2") {
  global $adsys;
  // Si on vient de Apd-1, on récupère les infos de la BD
  if (strstr($global_nom_ecran_prec,"Dbd-11")) {

		$SESSION_VARS["mode_debour"] = $mode_debour;
		$SESSION_VARS["dest_debour"] = $dest_debour;
    /* Vérificateur de l'état des garanties  */
    $gar_pretes = true;
    /* Récupération des garanties déjà mobilisées pour ce dossier */
    foreach($SESSION_VARS['infos_doss'] as $id_doss=>$infos_doss) {
      $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'] = array();
      $liste_gar = getListeGaranties($id_doss);
      foreach($liste_gar as $key=>$value ) {
        $num = count($SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR']) + 1; // indice du tableau
        $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['id_gar'] = $value['id_gar'];
        $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['type'] = $value['type_gar'];
        $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['valeur'] = recupMontant($value['montant_vente']);
        $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['devise_vente'] = $value['devise_vente'];
        $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['etat'] = $value['etat_gar'];

        /* Les garanties doivent être à l'état 'Prête' ou mobilisé  au moment de l'approbation  */
        if ($value['etat_gar'] !=2  and $value['etat_gar'] != 3)
          $gar_pretes = false;

        /* Si c'est une garantie numéraire récupérer le numéro complet du compte de prélèvement */
        if ($value['type_gar'] == 1) /* Garantie numéraire */
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['descr_ou_compte'] = $value['gar_num_id_cpte_prelev'];
        elseif($value['type_gar'] == 2 and isset($value['gar_mat_id_bien'])) { /* garantie matérielle */
          $id_bien = $value['gar_mat_id_bien'];
          $infos_bien = getInfosBien($id_bien);
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['descr_ou_compte'] = $infos_bien['description'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['type_bien'] = $infos_bien['type_bien'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['piece_just'] = $infos_bien['piece_just'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['num_client'] = $infos_bien['id_client'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['remarq'] = $infos_bien['remarque'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$num]['gar_mat_id_bien'] = $id_bien;
        }
      } /* Fin foreach garantie */
    } /* Fin foreach infos dossiers */

    /* Les garanties doivent être à l'état 'Prête' ou 'Mobilisé' au moment du déboursement  */
    if ( $gar_pretes == false) {
      $erreur = new HTML_erreur(_("Déboursement des fonds"));
      $msg = _("Impossible de débourser les fonds. Les garanties mobilisées ne sont pas toutes prêtes");
      $erreur->setMessage($msg);
      $erreur->addButton(BUTTON_OK,"Gen-11");
      $erreur->buildHTML();
      echo $erreur->HTML_code;
      exit();
    }


    // Récupérations des utilisateurs. C'est à dire les agents gestionnaires
    $SESSION_VARS['utilisateurs'] = array();
    $utilisateurs = getUtilisateurs();
    foreach($utilisateurs as $id_uti=>$val_uti)
    $SESSION_VARS['utilisateurs'][$id_uti] = $val_uti['nom']." ".$val_uti['prenom'];
    //Tri par ordre alphabétique des utilisateurs
 	  natcasesort($SESSION_VARS['utilisateurs']);
    // Objet demande de crédit
    $SESSION_VARS['obj_dem'] = getObjetsCredit();
    // Source de financement
    $SESSION_VARS['id_bailleur'] = getListBailleur();
  } //fin si on vient de Apd-1

  // Gestion de la devise
  setMonnaieCourante($SESSION_VARS['infos_prod']['devise']);
  $id_prod  = $SESSION_VARS['infos_prod']['id'];

  // Création du formulaire

  $Myform = new HTML_GEN2(_("Déboursement des fonds d'un crédit"));

  // Récuperation des détails objet demande
  $det_dem = getDetailsObjCredit();
  $det_dem2 = getDetailsObjCredit2();

  foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss)
  {
    if (isDcrDetailObjCreditLsb()) {
      $val_doss['detail_obj_dem_bis'] = $det_dem[$val_doss['detail_obj_dem_bis']]['libel'];
    }
    $val_doss['detail_obj_dem_2'] = $det_dem2[$val_doss['detail_obj_dem_2']]['libelle'];
    $nom_cli = getClientName($val_doss['id_client']);
    $Myform->addHTMLExtraCode("espace".$id_doss,"<br /><b><p align=center><b>".sprintf(_("Déboursement du dossier N° %s de %s"),$id_doss,$nom_cli)."</b></p>");
    $Myform->addField("id_doss".$id_doss, _("Numéro de dossier"), TYPC_TXT);
    $Myform->setFieldProperties("id_doss".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("id_doss".$id_doss,FIELDP_DEFAULT,$val_doss['id_doss']);
    $Myform->addField("id_prod".$id_doss, _("Produit de crédit"), TYPC_LSB);
    $Myform->setFieldProperties("id_prod".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("id_prod".$id_doss,FIELDP_ADD_CHOICES, array("$id_prod"=>$SESSION_VARS['infos_prod']['libel']));
    $Myform->setFieldProperties("id_prod".$id_doss,FIELDP_DEFAULT, $id_prod);
    // Ajout de liens
    $Myform->addLink("id_prod".$id_doss, "produit".$id_doss,_("Détail produit"), "#");
    $Myform->setLinkProperties("produit".$id_doss,LINKP_JS_EVENT,array("onClick"=>"open_produit(".$id_prod.",".$id_doss.");"));
    $Myform->addField("cpt_liaison".$id_doss, _("Compte de liaison"), TYPC_TXT);
    $Myform->setFieldProperties("cpt_liaison".$id_doss,FIELDP_IS_LABEL,true);
    $cpt_lie = getAccountDatas($val_doss['cpt_liaison']);
    $Myform->setFieldProperties("cpt_liaison".$id_doss,FIELDP_DEFAULT,$cpt_lie["num_complet_cpte"]." ".$cpt_lie['intitule_compte']);

    $Myform->addField("id_bailleur".$id_doss, _("Source de financement"), TYPC_LSB);
    $Myform->setFieldProperties("id_bailleur".$id_doss, FIELDP_ADD_CHOICES, $SESSION_VARS['id_bailleur']);
    $Myform->setFieldProperties("id_bailleur".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("id_bailleur".$id_doss,FIELDP_DEFAULT,$val_doss['id_bailleur']);

    $Myform->addField("obj_dem".$id_doss, _("Objet de la demande"), TYPC_LSB);
    $Myform->setFieldProperties("obj_dem".$id_doss, FIELDP_ADD_CHOICES, $SESSION_VARS['obj_dem']);
    $Myform->setFieldProperties("obj_dem".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("obj_dem".$id_doss,FIELDP_DEFAULT,$val_doss['obj_dem']);
    if (isDcrDetailObjCreditLsb()) {
      $Myform->addField("detail_obj_dem_bis" . $id_doss, _("Détail objet demande 1"), TYPC_TXT);
      $Myform->setFieldProperties("detail_obj_dem_bis" . $id_doss, FIELDP_IS_LABEL, true);
      $Myform->setFieldProperties("detail_obj_dem_bis" . $id_doss, FIELDP_DEFAULT, $val_doss['detail_obj_dem_bis']);
    } else {
      $Myform->addField("detail_obj_dem" . $id_doss, _("Détail objet demande 1"), TYPC_TXT);
      $Myform->setFieldProperties("detail_obj_dem" . $id_doss, FIELDP_IS_LABEL, true);
      $Myform->setFieldProperties("detail_obj_dem" . $id_doss, FIELDP_DEFAULT, $val_doss['detail_obj_dem']);
    }
    //At-97
    $Myform->addField("detail_obj_dem_2" . $id_doss, _("Détail objet demande 2"), TYPC_TXT);
    $Myform->setFieldProperties("detail_obj_dem_2" . $id_doss, FIELDP_IS_LABEL, true);
    $Myform->setFieldProperties("detail_obj_dem_2" . $id_doss, FIELDP_DEFAULT, $val_doss['detail_obj_dem_2']);

    $Myform->addField("date_dem".$id_doss, _("Date demande"), TYPC_DTE);
    $Myform->setFieldProperties("date_dem".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("date_dem".$id_doss,FIELDP_DEFAULT,$val_doss['date_dem']);
    $Myform->addField("mnt_dem".$id_doss, _("Montant demandé"), TYPC_MNT);
    $Myform->setFieldProperties("mnt_dem".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("mnt_dem".$id_doss,FIELDP_DEFAULT, $val_doss['mnt_dem']);
    $Myform->addField("cre_mnt_octr".$id_doss, _("Montant octroyé"), TYPC_MNT);
    $Myform->setFieldProperties("cre_mnt_octr".$id_doss,FIELDP_IS_LABEL,false);
    $Myform->setFieldProperties("cre_mnt_octr".$id_doss,FIELDP_DEFAULT,$val_doss['cre_mnt_octr']);
    $mnt_rest_deb = $val_doss['cre_mnt_octr'] - $val_doss['cre_mnt_deb'];
    $Myform->setFieldProperties("cre_mnt_octr".$id_doss,FIELDP_IS_LABEL, true);
    $Myform->addField("cre_mnt_a_deb".$id_doss, _("Montant à débourser"), TYPC_MNT);
    $Myform->setFieldProperties("cre_mnt_a_deb".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("cre_mnt_a_deb".$id_doss,FIELDP_DEFAULT,$mnt_rest_deb);
    $Myform->setFieldProperties("cre_mnt_a_deb".$id_doss, FIELDP_IS_REQUIRED, true);
		if (($SESSION_VARS["mode_debour"] == 2)||($val_doss['etat'] == 13)){ // mode de déboursement par tranche
		  $Myform->addField("cre_mnt_rest_deb".$id_doss, _("Montant restant à débourser"), TYPC_MNT);
      $Myform->setFieldProperties("cre_mnt_rest_deb".$id_doss,FIELDP_IS_LABEL,true);
      $Myform->setFieldProperties("cre_mnt_rest_deb".$id_doss,FIELDP_DEFAULT, $mnt_rest_deb);
		  $Myform->setFieldProperties("cre_mnt_a_deb".$id_doss,FIELDP_IS_LABEL,false);
      $Myform->setFieldProperties("cre_mnt_a_deb".$id_doss,FIELDP_DEFAULT,0);
      // contrôle sur le montant à débourser: il doit être inférieur au montant qui reste à débourser
      $codejs = "";
      $codejs .="\t\tif(recupMontant(document.ADForm.cre_mnt_rest_deb".$id_doss.".value) < recupMontant(document.ADForm.cre_mnt_a_deb".$id_doss.".value))\n";
      $codejs .= "{ msg+=' - "._("Le montant à débourser doit être inférieur au montant restant à débourser pour le dossier")." ".$id_doss."\\n';ADFormValid=false;}\n";
      $codejs .="\t\tif(recupMontant(document.ADForm.cre_mnt_a_deb".$id_doss.".value) == 0)\n";
      $codejs .= "{ msg+=' - "._("Le montant à débourser doit doit être différent de zéro pour le dossier")." ".$id_doss."\\n';ADFormValid=false;}\n";
      $Myform->addJS(JSP_BEGIN_CHECK, "JSMnt_deb".$id_doss, $codejs);
		}

    //type de durée : en mois ou en semaine
    $type_duree =  $SESSION_VARS['infos_prod']['type_duree_credit'];
    $libelle_duree = mb_strtolower(adb_gettext($adsys['adsys_type_duree_credit'][$type_duree])); // libellé type durée en minuscules
    $Myform->addField("duree_mois".$id_doss, _("Durée en ".$libelle_duree), TYPC_INT);
    $Myform->setFieldProperties("duree_mois".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("duree_mois".$id_doss,FIELDP_DEFAULT,$val_doss['duree_mois']);
    $Myform->addField("differe_jours".$id_doss, _("Différé en jours"), TYPC_INN);
    $Myform->setFieldProperties("differe_jours".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("differe_jours".$id_doss,FIELDP_DEFAULT,$val_doss['differe_jours']);
    $Myform->addField("differe_ech".$id_doss, _("Différé en échéance"), TYPC_INT);
    $Myform->setFieldProperties("differe_ech".$id_doss,FIELDP_DEFAULT,$val_doss['differe_ech']);
    $Myform->setFieldProperties("differe_ech".$id_doss,FIELDP_IS_LABEL, true);
    $Myform->addField("delai_grac".$id_doss, _("Délai de grace"), TYPC_INT);
    $Myform->setFieldProperties("delai_grac".$id_doss,FIELDP_DEFAULT,$val_doss['delai_grac']);
    $Myform->setFieldProperties("delai_grac".$id_doss,FIELDP_IS_LABEL, true);

    $Myform->addField("mnt_commission".$id_doss, _("Montant commission"), TYPC_MNT);
    $comm_values = calculCommissionDeboursement($id_prod, $val_doss["cre_mnt_octr"],$id_doss);
    $Myform->setFieldProperties("mnt_commission".$id_doss,FIELDP_DEFAULT,$comm_values["mnt_comm"]);
    $Myform->setFieldProperties("mnt_commission".$id_doss,FIELDP_IS_LABEL,true);

    if (check_access(299)) {
      $Myform->setFieldProperties("mnt_commission".$id_doss, FIELDP_CAN_MODIFY, true);
    }

    if ($comm_values["mnt_tax_comm"] > 0){
    	$Myform->addField("mnt_tax_commission".$id_doss, _("Montant tva commission"), TYPC_MNT);
    	$Myform->setFieldProperties("mnt_tax_commission".$id_doss,FIELDP_IS_LABEL,false);
    	$Myform->setFieldProperties("mnt_tax_commission".$id_doss,FIELDP_DEFAULT,$comm_values["mnt_tax_comm"]);
    	$Myform->setFieldProperties("mnt_tax_commission".$id_doss,FIELDP_IS_LABEL,true);
    }

    // appliquer la tarification sur les assurances
    $prc_assurance = $SESSION_VARS['infos_prod']['prc_assurance'];
    $mnt_assurance = $SESSION_VARS['infos_prod']['mnt_assurance'];
    $assurance_init = getfraisTarification('CRED_ASSURANCE', $prc_assurance, $val_doss["cre_mnt_octr"], $mnt_assurance);

    $Myform->addField("mnt_assurance".$id_doss, _("Montant assurance"), TYPC_MNT);
    $Myform->setFieldProperties("mnt_assurance".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("mnt_assurance".$id_doss,FIELDP_DEFAULT, $assurance_init);

    $Myform->addField("etat".$id_doss, _("Etat du dossier"), TYPC_TXT);
    $Myform->setFieldProperties("etat".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("etat".$id_doss,FIELDP_DEFAULT,adb_gettext($adsys["adsys_etat_dossier_credit"][$val_doss['etat']]));
    $Myform->addField("date_etat".$id_doss, _("Date état du dossier"), TYPC_DTE);
    $Myform->setFieldProperties("date_etat".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("date_etat".$id_doss,FIELDP_DEFAULT,$val_doss['cre_date_etat']);
    $Myform->addField("cre_date_debloc".$id_doss, _("Date de déblocage"), TYPC_DTE);
    $Myform->setFieldProperties("cre_date_debloc".$id_doss,FIELDP_DEFAULT,$val_doss['cre_date_debloc']);
    $Myform->setFieldProperties("cre_date_debloc".$id_doss,FIELDP_IS_LABEL, true);
    $Myform->addField("cre_date_approb".$id_doss, _("Date approbation"), TYPC_DTE);
    $Myform->setFieldProperties("cre_date_approb".$id_doss,FIELDP_IS_LABEL, true);
    $Myform->setFieldProperties("cre_date_approb".$id_doss,FIELDP_DEFAULT,$val_doss['cre_date_approb']);
    $Myform->addField("cre_etat".$id_doss, _("Etat crédit"), TYPC_INT);
    $Myform->setFieldProperties("cre_etat".$id_doss,FIELDP_DEFAULT, getLibel("adsys_etat_credits",1));
    $Myform->setFieldProperties("cre_etat".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->addField("id_agent_gest".$id_doss, _("Agent gestionnaire"), TYPC_LSB);
    $Myform->setFieldProperties("id_agent_gest".$id_doss, FIELDP_ADD_CHOICES, $SESSION_VARS['utilisateurs']);
    $Myform->setFieldProperties("id_agent_gest".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("id_agent_gest".$id_doss,FIELDP_DEFAULT,$val_doss['id_agent_gest']);
    $Myform->addField("prelev_auto".$id_doss, _("Prélèvement automatique"), TYPC_BOL);
    $Myform->setFieldProperties("prelev_auto".$id_doss,FIELDP_IS_LABEL,true);
    $Myform->setFieldProperties("prelev_auto".$id_doss,FIELDP_DEFAULT,$val_doss['prelev_auto']);
    $Myform->addField("cpt_prelev_frais".$id_doss, _("Compte de prélévement des frais"), TYPC_TXT);
    $Myform->setFieldProperties("cpt_prelev_frais".$id_doss,FIELDP_IS_LABEL,true);
    $cpt_frais = getAccountDatas($val_doss['cpt_prelev_frais']);
    $Myform->setFieldProperties("cpt_prelev_frais".$id_doss,FIELDP_DEFAULT,$cpt_frais["num_complet_cpte"]." ".$cpt_frais['intitule_compte']);
    if ($SESSION_VARS['infos_doss'][$id_doss]["gar_num"] > 0) {
      $Myform->addField("gar_num".$id_doss, _("Garantie numéraire attendue"), TYPC_MNT);
      $Myform->setFieldProperties("gar_num".$id_doss,FIELDP_IS_LABEL,true);
      $Myform->setFieldProperties("gar_num".$id_doss,FIELDP_DEFAULT,$val_doss['gar_num']);
    }
    if ($SESSION_VARS['infos_doss'][$id_doss]["gar_mat"] > 0) {
      $Myform->addField("gar_mat".$id_doss, _("Garantie matérielle attendue"), TYPC_MNT);
      $Myform->setFieldProperties("gar_mat".$id_doss,FIELDP_IS_LABEL,true);
      $Myform->setFieldProperties("gar_mat".$id_doss,FIELDP_DEFAULT,$val_doss['gar_mat']);
    }
    if ($SESSION_VARS['infos_doss'][$id_doss]["gar_tot"] > 0) {
      $Myform->addField("gar_tot".$id_doss, _("Garantie totale attendue"), TYPC_MNT);
      $Myform->setFieldProperties("gar_tot".$id_doss,FIELDP_IS_LABEL,true);
      $Myform->setFieldProperties("gar_tot".$id_doss,FIELDP_DEFAULT,$val_doss['gar_tot']);
    }
    if ($SESSION_VARS['infos_doss'][$id_doss]["gar_num_encours"] > 0) {
      $Myform->addField("gar_num_encours".$id_doss, _("Garantie numéraire encours"), TYPC_MNT);
      $Myform->setFieldProperties("gar_num_encours".$id_doss,FIELDP_IS_LABEL,true);
      $Myform->setFieldProperties("gar_num_encours".$id_doss,FIELDP_DEFAULT,$val_doss['gar_num_encours']);
    }
    $SESSION_VARS['infos_doss'][$id_doss]['gar_num_mob'] = 0; // garanties numéraires totales mobilisées
    $SESSION_VARS['infos_doss'][$id_doss]['gar_mat_mob'] = 0; // garanties matérilles totales mobilisées
    if (is_array($SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR']))
      foreach($SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'] as $key=>$value ) {
      if ($value['type'] == 1)
        $SESSION_VARS['infos_doss'][$id_doss]['gar_num_mob'] += recupMontant($value['valeur']);
      elseif($value['type'] == 2)
      $SESSION_VARS['infos_doss'][$id_doss]['gar_mat_mob'] += recupMontant($value['valeur']);
    }
    if ($SESSION_VARS['infos_doss'][$id_doss]["gar_num_mob"] > 0) {
      $Myform->addField("gar_num_mob".$id_doss, _("Garantie numéraire mobilisée"), TYPC_MNT);
      $Myform->setFieldProperties("gar_num_mob".$id_doss,FIELDP_IS_LABEL,true);
      $Myform->setFieldProperties("gar_num_mob".$id_doss,FIELDP_DEFAULT, $SESSION_VARS['infos_doss'][$id_doss]['gar_num_mob']);
    }
    if ($SESSION_VARS['infos_doss'][$id_doss]["gar_mat_mob"] > 0) {
      $Myform->addField("gar_mat_mob".$id_doss, _("Garantie matérielle mobilisée"), TYPC_MNT);
      $Myform->setFieldProperties("gar_mat_mob".$id_doss,FIELDP_IS_LABEL,true);
      $Myform->setFieldProperties("gar_mat_mob".$id_doss,FIELDP_DEFAULT, $SESSION_VARS['infos_doss'][$id_doss]['gar_mat_mob']);
    }
     //Informations sur le chèque
  	if($SESSION_VARS["dest_debour"] == 3){
			$xtra3 = "<b>"._("Informations sur le chèque")."</b>";
	    $Myform->addHTMLExtraCode ("htm3".$id_doss, $xtra3);
	    $Myform->setHTMLExtraCodeProperties ("htm3".$id_doss,HTMP_IN_TABLE, true);
	    $Myform->addField("num_chq".$id_doss, _("Numéro du chèque"), TYPC_TXT);
	    $Myform->setFieldProperties("num_chq".$id_doss,FIELDP_DEFAULT, $SESSION_VARS['infos_doss'][$id_doss]['data_chq']['num_chq']);
	    $Myform->setFieldProperties("num_chq".$id_doss,  FIELDP_IS_REQUIRED, true);
	    $Myform->addField("date_chq", _("Date"), TYPC_DTE);
        $Myform->setFieldProperties("date_chq",  FIELDP_HAS_CALEND, true);
        $Myform->setFieldProperties("date_chq", FIELDP_DEFAULT, $SESSION_VARS['date_chq']);
        $Myform->setFieldProperties("date_chq", FIELDP_IS_REQUIRED, true);

	    //Données du bénéficiaire
	    $Myform->addHiddenType("id_ben".$id_doss);
	    $Myform->addField("nom_ben".$id_doss, _("Nom du bénéficiaire"), TYPC_TXT);
	    $Myform->addLink("nom_ben".$id_doss, "rechercher".$id_doss, _("Rechercher"), "#");
	    $Myform->setFieldProperties("nom_ben".$id_doss,FIELDP_DEFAULT, $SESSION_VARS['infos_doss'][$id_doss]['data_chq']['nom_ben']);
	    $Myform->setLinkProperties("rechercher".$id_doss, LINKP_JS_EVENT, array("onclick" => "OpenBrw('$SERVER_NAME/modules/externe/rech_benef.php?m_agc=".$_REQUEST['m_agc']."&field_name=nom_ben".$id_doss."&field_id=id_ben".$id_doss."&type=b', '"._("Recherche")."');return false;"));
	    //Correspondant bancaire
	    $libel_correspondant=getLibelCorrespondant();
	    $Myform->addField("correspondant".$id_doss, _("Correspondant bancaire"), TYPC_LSB);
	    $Myform->setFieldProperties("correspondant".$id_doss, FIELDP_DEFAULT, $SESSION_VARS['infos_doss'][$id_doss]['data_chq']['correspondant']);
	    $Myform->setFieldProperties("correspondant".$id_doss, FIELDP_IS_REQUIRED, true);
	    $Myform->setFieldProperties("correspondant".$id_doss, FIELDP_ADD_CHOICES, $libel_correspondant);
			//Communication
	  	$Myform->addField("communication".$id_doss, _("Communication"), TYPC_TXT);
	  	$Myform->setFieldProperties("communication".$id_doss,FIELDP_DEFAULT,$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['communication']);
	  	//Remarque
	 	 	$Myform->addField("remarque".$id_doss, _("Remarque"), TYPC_ARE);
	  	$Myform->setFieldProperties("remarque".$id_doss,FIELDP_DEFAULT,$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['remarque']);

  	}

  }

  // Affichage des dossiers fictifs dans le cas d'un GS avec dossier réel unique et plusieurs dossiers fictifs
  if (is_array($SESSION_VARS['doss_fic']))
    foreach($SESSION_VARS['doss_fic'] as $id_fic=>$val_fic) {

      $Myform->addHTMLExtraCode("espace_fic".$id_fic,"<br />");
      $Myform->addField("membre".$id_fic, _("Membre"), TYPC_TXT);
      $Myform->setFieldProperties("membre".$id_fic, FIELDP_IS_REQUIRED, true);
      $Myform->setFieldProperties("membre".$id_fic, FIELDP_IS_LABEL, true);
      $Myform->setFieldProperties("membre".$id_fic,FIELDP_DEFAULT,$val_fic['id_membre']." ".getClientName($val_fic['id_membre']));

      $Myform->addField("id_bailleur_fic".$id_fic, _("Source de financement"), TYPC_LSB);
      $Myform->setFieldProperties("id_bailleur_fic".$id_fic, FIELDP_ADD_CHOICES, $SESSION_VARS['id_bailleur']);
      $Myform->setFieldProperties("id_bailleur_fic".$id_fic, FIELDP_IS_LABEL, true);
      $Myform->setFieldProperties("id_bailleur_fic".$id_fic, FIELDP_DEFAULT, $val_fic['id_bailleur']);

      $Myform->addField("obj_dem_fic".$id_fic, _("Objet demande"), TYPC_LSB);
      $Myform->setFieldProperties("obj_dem_fic".$id_fic, FIELDP_ADD_CHOICES, $SESSION_VARS['obj_dem']);
      $Myform->setFieldProperties("obj_dem_fic".$id_fic, FIELDP_IS_LABEL, true);
      $Myform->setFieldProperties("obj_dem_fic".$id_fic, FIELDP_DEFAULT, $val_fic['obj_dem']);
      if (isDcrDetailObjCreditLsb()) {
        $val_fic['detail_obj_dem_bis'] = $det_dem[$val_fic['detail_obj_dem_bis']]['libel'];

        $Myform->addField("detail_obj_dem_bis_fic" . $id_fic, _("Détail demande 1"), TYPC_TXT);
        $Myform->setFieldProperties("detail_obj_dem_bis_fic" . $id_fic, FIELDP_IS_LABEL, true);
        $Myform->setFieldProperties("detail_obj_dem_bis_fic" . $id_fic, FIELDP_DEFAULT, $val_fic['detail_obj_dem_bis']);
      } else {
        $Myform->addField("detail_obj_dem_fic" . $id_fic, _("Détail demande 1"), TYPC_TXT);
        $Myform->setFieldProperties("detail_obj_dem_fic" . $id_fic, FIELDP_IS_LABEL, true);
        $Myform->setFieldProperties("detail_obj_dem_fic" . $id_fic, FIELDP_DEFAULT, $val_fic['detail_obj_dem']);
      }
      //AT-97
      $val_fic['detail_obj_dem_2'] = $det_dem2[$val_fic['detail_obj_dem_2']]['libelle'];
      $Myform->addField("detail_obj_dem_2_fic" . $id_fic, _("Détail demande 2 "), TYPC_TXT);
      $Myform->setFieldProperties("detail_obj_dem_2_fic" . $id_fic, FIELDP_IS_LABEL, true);
      $Myform->setFieldProperties("detail_obj_dem_2_fic" . $id_fic, FIELDP_DEFAULT, $val_fic['detail_obj_dem_2']);

      $Myform->addField("mnt_dem_fic".$id_fic, _("Montant demande"), TYPC_MNT);
      $Myform->setFieldProperties("mnt_dem_fic".$id_fic, FIELDP_IS_LABEL, true);
      $Myform->setFieldProperties("mnt_dem_fic".$id_fic,FIELDP_DEFAULT,$val_fic['mnt_dem']);
    }

   //les boutons ajoutés
  $Myform->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $Myform->addFormButton(1,2,"annuler",_("Annuler"),TYPB_SUBMIT);

  // Propriétés des boutons
  $Myform->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $Myform->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-3");
  $Myform->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
  $Myform->buildHTML();
  echo $Myform->getHTML();
}
/*}}}*/

/*{{{ Dbd-3 : Echéancier réel */
else if ($global_nom_ecran == "Dbd-3") {

  $SESSION_VARS["id_cpte_cli"] = $SESSION_VARS["cpt_liaison"];

  // id du produit de crédit
  $id_prod = $SESSION_VARS['infos_prod']['id'];

  // Déterminantion de l'écran suivant
  $is_gar_num = false;
  $is_commission = false;
  $is_assurance = false;
  $is_frais_doss = false;

  // Affiche des échéanciers
  $HTML_code = '';

  foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss)
  {
    $cre_mnt_octr = $SESSION_VARS['infos_doss'][$id_doss]["cre_mnt_octr"];
    if ($SESSION_VARS["mode_debour"] == 2)
    	$SESSION_VARS['infos_doss'][$id_doss]["cre_mnt_a_deb"] = recupMontant($ {'cre_mnt_a_deb'.$id_doss});
    else
    	$SESSION_VARS['infos_doss'][$id_doss]["cre_mnt_a_deb"] = $SESSION_VARS['infos_doss'][$id_doss]["cre_mnt_octr"];
    $duree_mois = $SESSION_VARS['infos_doss'][$id_doss]["duree_mois"];
    $differe_jours = $SESSION_VARS['infos_doss'][$id_doss]["differe_jours"];
    $differe_ech = $SESSION_VARS['infos_doss'][$id_doss]["differe_ech"];

    $SESSION_VARS['infos_doss'][$id_doss]["cre_date_debloc"] = date("d/m/Y");

    // commissions
    if ($ {'mnt_commission'.$id_doss} != NULL){
    	$SESSION_VARS['infos_doss'][$id_doss]["mnt_commission"] = recupMontant($ {'mnt_commission'.$id_doss});
    	$SESSION_VARS['infos_doss'][$id_doss]["mnt_tax_commission"] = recupMontant($ {'mnt_tax_commission'.$id_doss});
    }
    else{
    	$comm_values = calculCommissionDeboursement($id_prod, $cre_mnt_octr,$id_doss);
    	$SESSION_VARS['infos_doss'][$id_doss]["mnt_commission"] = $comm_values["mnt_comm"];
    	$SESSION_VARS['infos_doss'][$id_doss]["mnt_tax_commission"] = $comm_values["mnt_tax_comm"];
    }

    // assurances dans la session
    $prc_assurance = $SESSION_VARS['infos_prod']['prc_assurance'];
    $assurance_fix = $SESSION_VARS['infos_prod']['mnt_assurance'];
    $assurance = getfraisTarification('CRED_ASSURANCE', $prc_assurance, $val_doss['cre_mnt_octr'], $assurance_fix);
    $SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"] = $assurance;

    //recuperation des frais des des dossiers
    $SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] = $val_doss['mnt_frais_doss'];

    /*$SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] = $SESSION_VARS['infos_prod']["mnt_frais"] +
        ($SESSION_VARS['infos_doss'][$id_doss]["cre_mnt_octr"]* $SESSION_VARS['infos_prod']["prc_frais"]);*/

    // Informations cheque si deboursement par cheque
      if ($SESSION_VARS["dest_debour"] == 3){
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['num_piece'] = $ {'num_chq'.$id_doss};
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['date_piece'] = $ {'date_chq'.$id_doss};
      	$id_benef = $ {'id_ben'.$id_doss  };
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['id_ext_benef'] = $id_benef;
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['id_correspondant'] = $ {'correspondant'.$id_doss};
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['communication'] = $ {'communication'.$id_doss};
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['remarque'] = $ {'remarque'.$id_doss};
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['sens'] = 'out';
      	$SESSION_VARS['infos_doss'][$id_doss]['data_chq']['type_piece'] = 2; // chèque extérieur
      }


    // Appel de la fonction echéancier théorique
      $echeancier = calcul_echeancier_theorique($id_prod, $cre_mnt_octr, $duree_mois, $differe_jours, $differe_ech, NULL, 1, $id_doss);
    //}

    // Appel de l'affichage de l'échéancier
    $parametre["lib_date"]=_("Date de déboursement");
    $parametre["index"]= 0;
    $parametre["titre"]= _("Echéancier réel de remboursement");
    $parametre["nbre_jour_mois"]= 30;
    $parametre["montant"]= $cre_mnt_octr;
    $parametre["mnt_reech"]= '0';
    $parametre["mnt_octr"]= $cre_mnt_octr;
    $parametre["prelev_commission"]= $SESSION_VARS['infos_doss'][$id_doss]["prelev_commission"];
    $parametre["mnt_assurance"]= $SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"];
    $parametre["mnt_commission"]= $SESSION_VARS['infos_doss'][$id_doss]["mnt_commission"];
    $parametre["mnt_tax_commission"]= $SESSION_VARS['infos_doss'][$id_doss]["mnt_tax_commission"];
    $parametre["mnt_des_frais"]=$SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"]+$parametre["prelev_commission"]+$parametre["mnt_commission"]+$parametre["mnt_tax_commission"];
    //$parametre["mnt_des_frais"]= $SESSION_VARS['infos_prod']["mnt_frais"]+$parametre["prelev_commission"]+$parametre["mnt_commission"]+$parametre["mnt_tax_commission"];
    $parametre["debours"]= "true";
    $parametre["prelev_frais_doss"]= $SESSION_VARS['infos_prod']["prelev_frais_doss"];
    $parametre["garantie"]= $SESSION_VARS['infos_doss'][$id_doss]["gar_num"]+ $SESSION_VARS['infos_doss'][$id_doss]["gar_num_encours"]+$SESSION_VARS['infos_doss'][$id_doss]["gar_mat"];
    $parametre["duree"]= $duree_mois;
    $parametre["date"]= $SESSION_VARS['infos_doss'][$id_doss]['cre_date_debloc'];
    $parametre["id_prod"]= $id_prod;
    $parametre["id_doss"]= $id_doss;
    $parametre["differe_jours"]= $differe_jours;
    $parametre["differe_ech"]= $differe_ech;
    $parametre["EXIST"]=0; // Vaut 0 si l'échéancier n'est stocké dans la BD 1 sinon
    $parametre["id_client"]=  $SESSION_VARS['infos_doss'][$id_doss]["id_client"];
    // Pour l'impression de l'échéancier
    global $adsys;
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'] = array();
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Nom du Client")] = getClientName($parametre["id_client"]) ;  //inclu nom du client_513
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Montant")] = afficheMontant($parametre["montant"], true);
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Durée du crédit")] = $parametre["duree"];
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Date de déboursement")] = $parametre["date"];
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Produit de crédit")] = $SESSION_VARS['infos_prod']["libel"];
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Montant de la garantie numéraire")] = afficheMontant($parametre["garantie"], true);
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Différé")]=str_affichage_diff($parametre["differe_jours"],$parametre["differe_ech"]);
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Taux d'intérêt")] = affichePourcentage($SESSION_VARS['infos_prod']["tx_interet"]);
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Périodicité de remboursement")] = adb_gettext($adsys["adsys_type_periodicite"][$SESSION_VARS['infos_prod']["periodicite"]]);
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Mode de calcul des intérêts")] = adb_gettext($adsys["adsys_mode_calc_int_credit"][$SESSION_VARS['infos_prod']["mode_calc_int"]]);
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Délais de grâce")] = $SESSION_VARS['infos_prod']["delai_grace_jour"]." "._("jours");
    $SESSION_VARS['infos_doss'][$id_doss]['CRIT'][_("Etat")] = $SESSION_VARS['infos_doss'][$id_doss]["etat"];

    $SESSION_VARS['infos_doss'][$id_doss]["ECH"] = completeEcheancier($echeancier, $parametre);
    $SESSION_VARS['infos_doss'][$id_doss]['echeances'] = $SESSION_VARS['infos_doss'][$id_doss]["ECH"];
    reset($SESSION_VARS['infos_doss'][$id_doss]["ECH"]);
    $total_cap = 0;
    $total_int = 0;
    $total_gar = 0;
    while (list(,$ech) = each($SESSION_VARS['infos_doss'][$id_doss]["ECH"])) {
      $total_cap += $ech["mnt_cap"];
      $total_int += $ech["mnt_int"];
      $total_gar += $ech["mnt_gar"];
    }
    // Remplissage du champs du montant total
    reset($SESSION_VARS['infos_doss'][$id_doss]["ECH"]);
    $solde_cap = $total_cap;
    $solde_int = $total_int;
    $solde_gar = $total_gar;
    while (list($key,$ech) = each($SESSION_VARS['infos_doss'][$id_doss]["ECH"])) {
      $solde_cap -= $ech["mnt_cap"];
      $solde_int -= $ech["mnt_int"];
      $solde_gar -= $ech["mnt_gar"];
      $echeancierComplet[$key]["solde_cap"] = $solde_cap;
      $echeancierComplet[$key]["solde_int"] = $solde_int;
      $echeancierComplet[$key]["solde_gar"] = $solde_gar;
    }
    $SESSION_VARS['infos_doss'][$id_doss]["total_cap"] = $total_cap;
    $SESSION_VARS['infos_doss'][$id_doss]["total_int"] = $total_int;
    $SESSION_VARS['infos_doss'][$id_doss]["total_gar"] = $total_gar;

    $HTML_code .= HTML_echeancier($parametre,$echeancier,$id_doss);

    // Calcul du solde disponible sur le compte de base du client
    $SESSION_VARS['infos_doss'][$id_doss]["soldeB"] = getSoldeDisponible($SESSION_VARS['infos_doss'][$id_doss]['cpt_liaison']);

    // Ecran suivant
    if ($SESSION_VARS['infos_doss'][$id_doss]["gar_num_mob"] > 0)
      $is_gar_num = true ;
    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] > 0  && $SESSION_VARS['infos_doss'][$id_doss]["cre_prelev_frais_doss"] != 't') 
      $is_frais_doss = true;
    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_commission"] > 0 && $SESSION_VARS['infos_doss'][$id_doss]["prelev_commission"] != 't')
      $is_commission = true;
    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"]>0 && $SESSION_VARS['infos_doss'][$id_doss]["assurances_cre"] == 't' )
      $is_assurance = true;
    if ($SESSION_VARS['infos_doss'][$id_doss]["etat"] == 13) // En état de deboursement progressif
      $is_debour_prog = true;
  } // fin parcours des dossiers

  //Fin impression
  $formEcheancier = new HTML_GEN2();
	if($is_frais_doss==true ||$is_commission = true||$is_assurance==true  ) {
		$SESSION_VARS['cptes_prelev_frais']=array();
		foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss) {
			if(!(array_key_exists($val_doss['cpt_prelev_frais'],$SESSION_VARS['cptes_prelev_frais']))){
				$SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']]= getSoldeDisponible($val_doss['cpt_prelev_frais']);
			}
	  }
	}

  // les boutons ajoutés
  $formEcheancier->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $formEcheancier->addFormButton(1,2,"imprimer",_("Imprimer"),TYPB_SUBMIT);
  $formEcheancier->addFormButton(1,3,"annuler",_("Annuler"),TYPB_SUBMIT);

  // Propriétés des boutons
   if($SESSION_VARS['infos_prod']["percep_frais_com_ass"]==1){ // Perception de frais avant déboursement
		  if ($is_gar_num)
		    $formEcheancier->setFormButtonProperties("valider",BUTP_PROCHAIN_ECRAN,"Dbd-4"); //Transfert des garanties
		  elseif ($is_frais_doss)
		    $formEcheancier->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-10"); //Frais dossier
		  elseif ($is_commission)
		    $formEcheancier->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-5"); //Commission
		  elseif ($is_assurance == true)
		    $formEcheancier->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-6"); //Assurances
		  else
		    $formEcheancier->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-7"); // Transfert des fonds
   }
   else
   {
   	if ($is_gar_num) {
   		$formEcheancier->setFormButtonProperties("valider",BUTP_PROCHAIN_ECRAN,"Dbd-4"); //Transfert des garanties
   	} else{
   		$formEcheancier->setFormButtonProperties("valider",BUTP_PROCHAIN_ECRAN,"Dbd-7"); //Transfert des fonds
		 }
   }

   if ($is_debour_prog) // En état de deboursement progressif
   	$formEcheancier->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-7"); // Transfert des fonds

  $formEcheancier->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $formEcheancier->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
  $formEcheancier->setFormButtonProperties("imprimer", BUTP_PROCHAIN_ECRAN, "Dbd-9");
  $formEcheancier->setFormButtonProperties("imprimer", BUTP_CHECK_FORM, false);
  $formEcheancier->setFormButtonProperties("valider", BUTP_CHECK_FORM, true);
  $formEcheancier->buildHTML();
  echo $HTML_code;
  echo $formEcheancier->getHTML();
}
/*}}}*/

/*{{{ /*{{{ Dbd-10 : Perception des frais de dossier */
else if ($global_nom_ecran == "Dbd-10") {

  $id_prod = $SESSION_VARS['id_prod'];
  $formConf = new HTML_GEN2(_("Perception des frais de dossier"));


 foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss)
 {
    //calcul du montant des éventuelles taxes sur les frais
    $frais_values = getMntFraisDossierProd($SESSION_VARS['id_prod'],$SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"],0,$id_doss);
    $SESSION_VARS['infos_prod']["mnt_tax_frais"] = $frais_values['mnt_tax_frais'];
    //solde du compte de prélèvement de frais de dossiers
    $soldeB=$SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']];
   if (($SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] + $frais_values['mnt_tax_frais']) > $soldeB) { 
    $erreur = new HTML_erreur(_("Perception des frais de dossier"));
    $erreur->setMessage(_("Impossible de continuer cette opération, le solde du compte lié au crédit est insuffisant pour la perception des frais de dossier.")."<br /><ul><li>"._("Montant des frais")." : ".afficheMontant($SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] + $frais_values['mnt_tax_frais'], true)."</li><li>"._("Solde du compte lié")." : ".afficheMontant($soldeB, true)."</li></ul>");
    $erreur->addButton(BUTTON_OK,"Gen-11");
    $erreur->buildHTML();
    echo $erreur->HTML_code;
    exit();
  } else {
    // Ligne de séparation des frais
    $formConf->addHTMLExtraCode("frais".$id_doss,"<br /><table align=\"center\" valign=\"middle\" bgcolor=\"".$colb_tableau."\"><tr><td><b>".sprintf(_("Frais du dossier N° %s"),$id_doss)."</b></td></tr></table>\n");

    // Diminuer le solde du compte de prélévement des frais du montant des frais
    $soldeF = $soldeB-($SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] + $frais_values['mnt_tax_frais']);
    $formConf->addField("num_complet_cpte".$id_doss,_("Compte de prélèvement"),TYPC_TXT);
    $formConf->addField("solde".$id_doss,_("Solde du compte"),TYPC_MNT);
    $formConf->addField("mnt".$id_doss,_("Montant des frais"),TYPC_MNT);
    if($frais_values['mnt_tax_frais'] > 0)
    	$formConf->addField("mnt_tax_frais".$id_doss,_("Montant tva sur frais"),TYPC_MNT);
    $formConf->addField("soldeF".$id_doss,_("Nouveau solde"),TYPC_MNT);

    // Récupération des infos sur le compte de liaison
    $compte_prelev_frais = getAccountDatas($val_doss['cpt_prelev_frais']);

    // Les propriétés des champs
    $formConf->setFieldProperties("num_complet_cpte".$id_doss, FIELDP_IS_REQUIRED,false);
    $formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_DEFAULT, $compte_prelev_frais['num_complet_cpte']);
    $formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("solde".$id_doss, FIELDP_IS_REQUIRED,false);
    $formConf->setFieldProperties("solde".$id_doss, FIELDP_DEFAULT,$soldeB);
    $formConf->setFieldProperties("solde".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("soldeF".$id_doss, FIELDP_DEFAULT,$soldeB-($SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] + $frais_values['mnt_tax_frais']));
    $formConf->setFieldProperties("soldeF".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_DEFAULT,$SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"]);
    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_IS_LABEL,true);
    if($frais_values['mnt_tax_frais'] > 0){
    	$formConf->setFieldProperties("mnt_tax_frais".$id_doss,FIELDP_DEFAULT,$frais_values['mnt_tax_frais']);
    	$formConf->setFieldProperties("mnt_tax_frais".$id_doss,FIELDP_IS_LABEL,true);
    }

    // le nouveau solde de compte de prevelement de frais de dossier devient soldeF
    $SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']]= $soldeF;//$soldeB-$SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"];

    //Ecran suivant
    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_commission"] > 0 && $SESSION_VARS['infos_doss'][$id_doss]["prelev_commission"] != 't')
      $is_commission = true;
    elseif ($SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"]>0 && $SESSION_VARS['infos_doss'][$id_doss]["assurances_cre"] == 't')
      $is_assurance = true;
  }
 }
   //ajout des boutons
    $formConf->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
    $formConf->addFormButton(1,2,"annuler",_("Annuler"),TYPB_SUBMIT);

    // Propriétées des boutons
    if ($is_commission) {
      $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-5"); //Commission
    } elseif ($is_assurance) {
      $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-6"); //Assurances
    } else
    {
    	if($SESSION_VARS['infos_prod']["percep_frais_com_ass"]==1) {
      $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-7"); // Transfert des des fonds
    	}	else	{
       $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-8"); // confirmation Transfert  des fonds
    	}
    }

    $formConf->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
    $formConf->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

    $formConf->buildHTML();
    echo $formConf->getHTML();
}
/*}}}*/

/*{{{ Dbd-4 : Transfert des garanties */
else if ($global_nom_ecran == "Dbd-4") {
  $formConf = new HTML_GEN2(_("Transfert du montant des garanties"));

  // Déterminantion de l'écran suivant
  $is_commission = false;
  $is_assurance = false;

  foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss) {
    // Création d'un compte nantie pour chaque garantie numéraire
    foreach($val_doss['DATA_GAR'] as $key=>$value ) {
      if ($value['type'] == 1 ) {
        // Récupération des infos sur le compte de prélèvement des garanties
        $compte_gar = getAccountDatas($value['descr_ou_compte']);

        // Solde diponible du compte de prélèvement
        $soldeCompteGar = getSoldeDisponible($value['descr_ou_compte']) ;//- $SESSION_VARS['infos_prod']["mnt_frais"];

        // Ligne de séparation des garanties
        $formConf->addHTMLExtraCode("gar".$id_doss."_".$key,"<table align=\"center\" valign=\"middle\" bgcolor=\"".$colb_tableau."\"><tr><td><b>"._("Garantie")."</b></td></tr></table>\n");

        // Affichage du compte de prélèvement des garanties
        $solde_avt = $soldeCompteGar + $value['valeur']; // solde du compte avant prélévement garantie
        $formConf->addField("num_complet_cpte".$id_doss."_".$key,_("Compte de prélèvement")." ",TYPC_TXT);
        $formConf->addField("solde_avt".$id_doss."_".$key,_("Solde du compte"),TYPC_MNT);
        $formConf->addField("mnt_bloq".$id_doss."_".$key,_("Montant des garanties"),TYPC_MNT);
        $formConf->addField("solde".$id_doss."_".$key,_("Solde disponible"),TYPC_MNT);

        // Les propriétés des champs
        $formConf->setFieldProperties("num_complet_cpte".$id_doss."_".$key,FIELDP_DEFAULT,$compte_gar['num_complet_cpte']);
        $formConf->setFieldProperties("solde".$id_doss."_".$key,FIELDP_DEFAULT,$soldeCompteGar);
        $formConf->setFieldProperties("mnt_bloq".$id_doss."_".$key,FIELDP_DEFAULT, $value["valeur"]);
        $formConf->setFieldProperties("solde_avt".$id_doss."_".$key,FIELDP_DEFAULT, $solde_avt);
        $formConf->setFieldProperties("num_complet_cpte".$id_doss."_".$key,FIELDP_IS_LABEL,true);
        $formConf->setFieldProperties("solde".$id_doss."_".$key,FIELDP_IS_LABEL,true);
        $formConf->setFieldProperties("mnt_bloq".$id_doss."_".$key,FIELDP_IS_LABEL,true);
        $formConf->setFieldProperties("solde_avt".$id_doss."_".$key,FIELDP_IS_LABEL,true);
      }
      // Ecran suivant
      if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] > 0 && $SESSION_VARS['infos_doss'][$id_doss]["cre_prelev_frais_doss"] != 't')
        $is_frais_doss = true;
      if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_commission"] > 0 && $SESSION_VARS['infos_doss'][$id_doss]["prelev_commission"] != 't')
        $is_commission = true;
      elseif ($SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"]>0 && $SESSION_VARS['infos_doss'][$id_doss]["assurances_cre"] == 't')
        $is_assurance = true;

    } /* Fin foreach garantie */
  } /* Fin foreach dossiers */
  // les boutons ajoutés
  $formConf->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $formConf->addFormButton(1,2,"annuler",_("Annuler"),TYPB_SUBMIT);

  // Propriétés des boutons
  if($SESSION_VARS['infos_prod']["percep_frais_com_ass"]==1){ // avant déboursement
		  if ($is_frais_doss) {
		    $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-10"); //Frais de dossier
		  } elseif ($is_commission) {
		    $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-5"); //Commission
		  } elseif ($is_assurance) {
		    $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-6"); //Assurances
		  }else{
		  	$formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-7"); // Transfert des des fonds
		  }
  } else { //apres deboursement
			$formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-7"); // Transfert des des fonds
  }

  $formConf->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $formConf->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

  $formConf->buildHTML();
  echo $formConf->getHTML();
}
/*}}}*/

/*{{{ Dbd-5 : Commission */
else if ($global_nom_ecran == "Dbd-5") {
  $com_prele = array();  // tableau contenant les montants de commission par compte de liaison
  // Déterminantion de l'écran suivant
  $is_commission = false;
  $is_assurance = false;

  $formConf = new HTML_GEN2(_("Perception des commissions"));
  foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss) {
  	// solde du compte de prélèvement de frais de commissions
      $soldeB=$SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']];

    // Le solde du compte de liaison permet-il de payer les commisions
    if (($val_doss['mnt_commission'] + $val_doss['mnt_tax_commission']) > $soldeB) {
      $erreur = new HTML_erreur(_("Perception des commissions"));
      $erreur->setMessage(sprintf(_("Impossible de continuer cette opération, le solde du compte de prélévement des frais du dossier %s est insuffisant pour la perception des commissions."),$id_doss)."<br /><ul><li>"._("Montant de la commission")." : ".afficheMontant($val_doss['mnt_commission'], true)."</li><li>"._("Solde du compte de prélévement des frais")." : ".afficheMontant($soldeB, true)."</li></ul>");
      $erreur->addButton(BUTTON_OK,"Gen-11");
      $erreur->buildHTML();
      echo $erreur->HTML_code;
      exit();
    }

    // Ligne de séparation des commissions
    $formConf->addHTMLExtraCode("com".$id_doss,"<br /><table align=\"center\" valign=\"middle\" bgcolor=\"".$colb_tableau."\"><tr><td><b>".sprintf(_("Commission du dossier N° %s"),$id_doss)." </b></td></tr></table>\n");

    // Diminuer le solde du compte de laison du montant des commissions
    $soldeF = $soldeB - ($val_doss['mnt_commission'] + $val_doss['mnt_tax_commission']);
    // nouveau solde du compte de prélèvement de frais
    $SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']]=$soldeF;

    $formConf->addField("num_complet_cpte".$id_doss,_("Compte de prélèvement"),TYPC_TXT);
    $formConf->addField("solde".$id_doss,_("Solde du compte"),TYPC_MNT);
    $formConf->addField("mnt".$id_doss,_("Montant des commissions"),TYPC_MNT);
    $formConf->addField("mnt_tax_commission".$id_doss,_("Montant tva sur commissions"),TYPC_MNT);
    $formConf->addField("soldeF".$id_doss,_("Nouveau solde"),TYPC_MNT);

    // Récupération des infos sur le compte de liaison
    $compte_prelev_frais = getAccountDatas($val_doss['cpt_prelev_frais']);

    // Les propriétés des champs
    $formConf->setFieldProperties("num_complet_cpte".$id_doss, FIELDP_IS_REQUIRED,false);
    $formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_DEFAULT, $compte_prelev_frais['num_complet_cpte']);
    $formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("solde".$id_doss, FIELDP_IS_REQUIRED,false);
    $formConf->setFieldProperties("solde".$id_doss, FIELDP_DEFAULT,$soldeB);
    $formConf->setFieldProperties("solde".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("soldeF".$id_doss, FIELDP_DEFAULT,$soldeF);
    $formConf->setFieldProperties("soldeF".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_DEFAULT,$val_doss['mnt_commission']);
    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("mnt_tax_commission".$id_doss,FIELDP_DEFAULT,$val_doss['mnt_tax_commission']);
    $formConf->setFieldProperties("mnt_tax_commission".$id_doss,FIELDP_IS_LABEL,true);

    // Ecran suivant
    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"]>0 && $SESSION_VARS['infos_doss'][$id_doss]["assurances_cre"] == 't')
      $is_assurance = true;

  }

  // les boutons ajoutés
  $formConf->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $formConf->addFormButton(1,2,"annuler",_("Annuler"),TYPB_SUBMIT);

  if ($is_assurance) {
    $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-6"); //Assurances
  }
  else
  {
		if($SESSION_VARS['infos_prod']["percep_frais_com_ass"]==1) {
      $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-7"); // Transfert des des fonds
    	} else {
       $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-8"); // confirmation Transfert  des fonds
    	}
  }

  // Propriétés des boutons
  $formConf->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $formConf->buildHTML();
  echo $formConf->getHTML();

}
/*}}}*/

/*{{{ Dbd-6 : Transfert du montant des assurances */
else if ($global_nom_ecran == "Dbd-6") {
  $com_prele = array();  // tableau contenant les montants de assurances par compte de liaison

  $formConf = new HTML_GEN2(_("Transfert du montant des assurances"));

  foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss)
  {
    // Application des tarifications pour assurances
    $prc_assurance = $SESSION_VARS['infos_prod']['prc_assurance'];
    $mnt_assurance = $SESSION_VARS['infos_prod']['mnt_assurance'];
    $assurance_init = ($val_doss["cre_mnt_octr"]* $prc_assurance);
    $assurance_init = getfraisTarification('CRED_ASSURANCE', $prc_assurance, $val_doss["cre_mnt_octr"], $mnt_assurance);

    //solde du compte de prélèvement de frais d'assurance'
	$soldeB=$SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']];
    // Le solde du compte de laison permet-il de transferer les assurances
    if($assurance_init > $soldeB) {
      $erreur = new HTML_erreur(_("Transfert du montant des assurances"));
      $erreur->setMessage(_("Impossible de continuer cette opération, le solde du compte lié au crédit du client est insuffisant.")."<br /><ul><li>"._("Montant de l'assurance")." : ".afficheMontant($com_prele[$val_doss['cpt_liaison']], true)."</li><li>"._("Solde du compte lié")." : ".afficheMontant($soldeB, true)."</li></ul>");
      $erreur->addButton(BUTTON_OK,"Gen-11");
      $erreur->buildHTML();
      echo $erreur->HTML_code;
      exit();
    }

    // Diminuer le solde du compte de liaison du montant des assurances
    $soldeF = $soldeB - $assurance_init;
		$SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']]=$soldeF;
    // Ligne de séparation des commissions
    $formConf->addHTMLExtraCode("ass".$id_doss,"<br /><table align=\"center\" valign=\"middle\" bgcolor=\"".$colb_tableau."\"><tr><td><b>".sprintf(_("Assurance du dossier N° %s"),$id_doss)."</b></td></tr></table>\n");

    // Diminuer le solde du compte de prélévement des frais du montant des commissions
    //$soldeF = $soldeB - $com_prele[$val_doss['cpt_prelev_frais']];
    //nouveau solde du compte de prélèvement devient
    $SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']]=$soldeF;

    $formConf->addField("num_complet_cpte".$id_doss,_("Compte de prélèvement"),TYPC_TXT);
    $formConf->addField("solde".$id_doss,_("Solde du compte"),TYPC_MNT);
    $formConf->addField("mnt".$id_doss,_("Montant assurance"),TYPC_MNT);
    $formConf->addField("soldeF".$id_doss,_("Nouveau solde"),TYPC_MNT);

    // Récupération des infos sur le compte de liaison
    $compte_prelev_frais = getAccountDatas($val_doss['cpt_prelev_frais']);

    // Les propriétés des champs
    $formConf->setFieldProperties("num_complet_cpte".$id_doss, FIELDP_IS_REQUIRED,false);
    $formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_DEFAULT, $compte_prelev_frais['num_complet_cpte']);
    $formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("solde".$id_doss, FIELDP_IS_REQUIRED,false);
    $formConf->setFieldProperties("solde".$id_doss, FIELDP_DEFAULT,$soldeB);
    $formConf->setFieldProperties("solde".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("soldeF".$id_doss, FIELDP_DEFAULT,$soldeF);
    $formConf->setFieldProperties("soldeF".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_DEFAULT, $assurance_init);
    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_IS_LABEL,true);
  }

  // les boutons ajoutés
  $formConf->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $formConf->addFormButton(1,2,"annuler",_("Annuler"),TYPB_SUBMIT);

	if($SESSION_VARS['infos_prod']["percep_frais_com_ass"]==1) {
     $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-7"); // Transfert des des fonds
   }
   else
   {
     $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-8"); // confirmation Transfert  des fonds
    }

  $formConf->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $formConf->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

  $formConf->buildHTML();
  echo $formConf->getHTML();
}
/*}}}*/

/*{{{ Dbd-7 : Transfert des fonds sur le compte de destination (compte de liaison, compte guichet ou compte du corresponant bancaire) */
else if ($global_nom_ecran == "Dbd-7") {
  $formConf = new HTML_GEN2(_("Transfert des fonds du crédit vers compte de destination"));
  foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss) {
    // Ligne de séparation des commissions
    $formConf->addHTMLExtraCode("deb".$id_doss,"<br /><table align=\"center\" valign=\"middle\" bgcolor=\"".$colb_tableau."\"><tr><td><b>".sprintf(_("Transfert des fonds du dossier N° %s"),$id_doss)."</b></td></tr></table>\n");
		$formConf->addField("num_complet_cpte".$id_doss,_("Compte de transfert"),TYPC_TXT);
		if($SESSION_VARS["dest_debour"] == 1){ // au guichet
			$formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_DEFAULT, sprintf(_("Compte guichet")));
		}
		elseif ($SESSION_VARS["dest_debour"] == 2){ // compte de liaison
			// Récupération des infos sur le compte de liaison
    	$compte_liaison = getAccountDatas($val_doss['cpt_liaison']);
			if ($val_doss['cpt_liaison'] == $val_doss['cpt_prelev_frais']) {
    	if(isset($SESSION_VARS['cptes_prelev_frais'])){
    		$soldeB=$SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']];
     		$soldeF = $soldeB +  $val_doss['cre_mnt_a_deb'];
     		$SESSION_VARS['cptes_prelev_frais'][$val_doss['cpt_prelev_frais']]=$soldeF ;
    	}else{
        $solde=getSoldeDisponible($val_doss['cpt_prelev_frais']);
        $soldeF = $soldeB +  $val_doss['cre_mnt_a_deb'];
    	}

     }
     else {
		  $soldeB = $val_doss['soldeB'];
		  $soldeF = $soldeB +  $val_doss['cre_mnt_a_deb'];
		 }
		 	$formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_DEFAULT, $compte_liaison['num_complet_cpte']);
		  $formConf->addField("solde".$id_doss,_("Solde du compte"),TYPC_MNT);
		  $formConf->addField("soldeF".$id_doss,_("Nouveau solde"),TYPC_MNT);
		  $formConf->setFieldProperties("solde".$id_doss, FIELDP_IS_REQUIRED,false);
   	 	$formConf->setFieldProperties("solde".$id_doss, FIELDP_DEFAULT,$soldeB);
    	$formConf->setFieldProperties("solde".$id_doss,FIELDP_IS_LABEL,true);
    	$formConf->setFieldProperties("soldeF".$id_doss, FIELDP_DEFAULT,$soldeF);
    	$formConf->setFieldProperties("soldeF".$id_doss,FIELDP_IS_LABEL,true);
		}
		else{ // par chèque
			$formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_DEFAULT, sprintf(_("Compte du correspondant bancaire")));
		}


    $formConf->addField("mnt".$id_doss,_("Montant octroyé"),TYPC_MNT);
    $formConf->addField("mnt_a_deb".$id_doss,_("Montant à débourser"),TYPC_MNT);

    // Les propriétés des champs
    $formConf->setFieldProperties("num_complet_cpte".$id_doss, FIELDP_IS_REQUIRED,false);
    $formConf->setFieldProperties("num_complet_cpte".$id_doss,FIELDP_IS_LABEL,true);

    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_DEFAULT,$val_doss['cre_mnt_octr']);
    $formConf->setFieldProperties("mnt".$id_doss,FIELDP_IS_LABEL,true);
    $formConf->setFieldProperties("mnt_a_deb".$id_doss,FIELDP_DEFAULT,$val_doss['cre_mnt_a_deb']);
    $formConf->setFieldProperties("mnt_a_deb".$id_doss,FIELDP_IS_LABEL,true);

    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_frais"] > 0  && $SESSION_VARS['infos_doss'][$id_doss]["cre_prelev_frais_doss"] != 't')
      $is_frais_doss = true;
    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_commission"] > 0 && $SESSION_VARS['infos_doss'][$id_doss]["prelev_commission"] != 't')
      $is_commission = true;
    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"]>0 && $SESSION_VARS['infos_doss'][$id_doss]["assurances_cre"] == 't' )
      $is_assurance = true;

  }

  // les boutons ajoutés
  $formConf->addFormButton(1,1,"valider",_("Valider"),TYPB_SUBMIT);
  $formConf->addFormButton(1,2,"annuler",_("Annuler"),TYPB_SUBMIT);

  // Propriétés des boutons
  if($SESSION_VARS['infos_prod']["percep_frais_com_ass"]==1) {
     $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-8"); // confirmation Transfert des des fonds
   }
   else
   {
   	 if ($is_frais_doss){
		   	$formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-10"); //Frais dossier
   	 }elseif ($is_commission){
		    $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-5"); //Commission
   	 }elseif ($is_assurance == true){
		   $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-6"); //Assurances
   	 }else {
		    $formConf->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, "Dbd-8"); // confirmation Transfert des fonds
   	 }

    }


  $formConf->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, "Gen-11");
  $formConf->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);

  $formConf->buildHTML();
  echo $formConf->getHTML();
}
/*}}}*/

/*{{{ Dbd-8 : Confirmation */
else if ($global_nom_ecran == "Dbd-8") {
  foreach($SESSION_VARS['infos_doss'] as $id_doss=>$val_doss) {

    //Remplissage de $DATA avec les données concernant la mise à jour du dossier de crédit

    // Données concernant le compte de crédit
    $SESSION_VARS['infos_doss'][$id_doss]['data_cpt_cre'] = array();
    $SESSION_VARS['infos_doss'][$id_doss]['data_cpt_cre']['utilis_crea'] = $global_id_utilisateur;
    $SESSION_VARS['infos_doss'][$id_doss]['data_cpt_cre']['id_titulaire'] = $SESSION_VARS['infos_doss'][$id_doss]['id_client'];
    $SESSION_VARS['infos_doss'][$id_doss]['data_cpt_cre']['etat_cpte'] = 1; // Etat du compte de crédit (ouvert)
    $SESSION_VARS['infos_doss'][$id_doss]['data_cpt_cre']['date_ouvert'] = date("d/m/Y"); // Date d'ouverture du cpte de crédit
    $AG = getAgenceDatas($global_id_agence);
    $id_prod_cre = $AG["id_prod_cpte_credit"];
    $SESSION_VARS['infos_doss'][$id_doss]['data_cpt_cre']["id_prod"] = $id_prod_cre;
    $SESSION_VARS['infos_doss'][$id_doss]['data_cpt_cre']["devise"] = $global_monnaie_courante;

    // S'il y a une garantie numéraire à bloquer au début
    if ($SESSION_VARS['infos_doss'][$id_doss]['gar_num_mob'] > 0) {
      // Créer un compte de garantie pour chaque garantie numéraire bloquée
      foreach($val_doss['DATA_GAR'] as $key=>$value ) {
        if ($value['type'] == 1 ) {
          // Les infos sur le compte de prélèvement des garanties
          $compte = getAccountDatas($value['descr_ou_compte']);

          // Données concernant le compte d'épargne nantie
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['devise'] = $global_monnaie_courante;
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['utilis_crea'] = $global_id_utilisateur;
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['etat_cpte'] = 3; /* Etat du compte (bloqué) */
          // Date d'ouverture du cpte
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['date_ouvert'] = $val_doss['cre_date_debloc'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['num_cpte'] = $rang;
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['mnt_bloq'] = 0;
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['descr_ou_compte'] = $value['descr_ou_compte'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['mnt_preleve'] = $value['valeur'];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['id_gar'] = $value['id_gar'];
          // Les intérets sotn versés sur le compte lui-meme
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['type_cpt_vers_int'] = 1;
          // Par défaut l'intitulé est garantie
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['intitule_compte'] = _("GARANTIE");

          //$AG = getAgenceDatas($global_id_agence);
          $id_prod_en = $AG["id_prod_cpte_epargne_nantie"];
          $SESSION_VARS['infos_doss'][$id_doss]['DATA_GAR'][$key]['id_prod'] = $id_prod_en;
        }
      }
    }

    // S'il y a une garantie à constituer au fil des remboursements, créer le compte nantie pour le client  */
    if ($val_doss["gar_num_encours"] > 0) {
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours'] = array();
      // Données concernant le compte d'épargne nantie
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['devise'] = $global_monnaie_courante;
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['utilis_crea'] = $global_id_utilisateur;
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['etat_cpte'] = 3; /* Etat du compte (ouvert) */
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['id_titulaire'] = $SESSION_VARS['infos_doss'][$id_doss]['id_client'];
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['date_ouvert'] = $val_doss["cre_date_debloc"]; /* Date d'ouverture du cpte */
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['mnt_bloq'] = 0;
      $AG = getAgenceDatas($global_id_agence);
      $id_prod_en = $AG["id_prod_cpte_epargne_nantie"];
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['id_prod'] = $id_prod_en;
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['type_cpt_vers_int'] = 1; /* Les intérets sotn versés sur le compte lui-meme */
      $SESSION_VARS['infos_doss'][$id_doss]['data_gar_encours']['intitule_compte'] = _("GARANTIE"); // Par défaut l'intitulé est garantie
    }

    // Les fonds sont virés sur le compte lié du client ou au guichet

    $SESSION_VARS['infos_doss'][$id_doss]['transfert_fond']['id_cpte_cli'] = $val_doss['cpt_liaison'];
    if($SESSION_VARS["mode_debour"] == 1)
    	$SESSION_VARS['infos_doss'][$id_doss]['transfert_fond']['montant'] = $val_doss['cre_mnt_octr'];      //Montant octroyé
    else // déboursement par tranche
    	$SESSION_VARS['infos_doss'][$id_doss]['transfert_fond']['montant'] = $val_doss['cre_mnt_a_deb'];      //Montant à débourser

    if ($SESSION_VARS['infos_doss'][$id_doss]["mnt_assurance"]>0 && $SESSION_VARS['infos_doss'][$id_doss]["assurances_cre"] == 't') { // S'il y a une assurance à payer
      //Transfert du montant des assurances
      $SESSION_VARS['infos_doss'][$id_doss]['transfert_ass']["id_cpte_cli"] =  $val_doss['cpt_prelev_frais']; //Compte de prélévement des frais
      $SESSION_VARS['infos_doss'][$id_doss]['transfert_ass']['id_agence'] = $global_id_agence;  //Compte de l'agence
      $SESSION_VARS['infos_doss'][$id_doss]['transfert_ass']['mnt_assurance'] = $val_doss["mnt_assurance"];
      //$SESSION_VARS['infos_doss'][$id_doss]['transfert_ass']['mnt_assurance'] =(($val_doss['cre_mnt_octr']*$SESSION_VARS['infos_prod']['prc_assurance'])+($SESSION_VARS['infos_prod']['mnt_assurance']));
    }

    if ($val_doss['mnt_commission'] > 0  && $val_doss["prelev_commission"] != 't') { // S'il y a une commission à payer
      //Commission
      $SESSION_VARS['infos_doss'][$id_doss]['transfert_com']['id_cpte_cli'] = $val_doss['cpt_prelev_frais']; //Compte de prelevement frais
      $SESSION_VARS['infos_doss'][$id_doss]['transfert_com']['id_agence'] = $global_id_agence;
      $SESSION_VARS['infos_doss'][$id_doss]['transfert_com']['mnt_commission'] = $val_doss['mnt_commission']; //Montant des commissions
        if ($val_doss['mnt_tax_commission'] > 0)
      	  $SESSION_VARS['infos_doss'][$id_doss]['transfert_com']['mnt_tax_commission'] = $val_doss['mnt_tax_commission']; //Montant des taxes sur la commission
    }
    if ($SESSION_VARS['infos_doss'][$id_doss]['mnt_frais'] > 0 && $val_doss['cre_prelev_frais_doss'] != "t"){
    	//frais de dossier
    	$SESSION_VARS['infos_doss'][$id_doss]['transfert_frais']['id_cpte_cli'] = $val_doss['cpt_prelev_frais'];
    	$SESSION_VARS['infos_doss'][$id_doss]['transfert_frais']['id_agence'] = $global_id_agence;
        $SESSION_VARS['infos_doss'][$id_doss]['transfert_frais']['mnt_frais'] = $SESSION_VARS['infos_doss'][$id_doss]['mnt_frais']; //Montant des frais

      if ($SESSION_VARS['infos_prod']['mnt_tax_frais'] > 0)
      	  $SESSION_VARS['infos_doss'][$id_doss]['transfert_frais']['mnt_tax_frais'] = $SESSION_VARS['infos_prod']['mnt_tax_frais'];
    }
  }


  $data_agc = getAgenceDatas($global_id_agence);
  if ($data_agc['quotite'] == 't'){
    $data_cli = getClientDatas($global_id_client);
    if($data_cli['mnt_quotite'] > 0){
      $quotite_dispo = $data_cli['mnt_quotite'];
      $mnt_prem_ech = $SESSION_VARS['infos_doss'][$id_doss]['ECH'][1]['mnt_cap'] + $SESSION_VARS['infos_doss'][$id_doss]['ECH'][1]['mnt_int'];
      if($mnt_prem_ech > $quotite_dispo){
        $html_err = new HTML_erreur(_("Echec du deboursement"));
        $msg = _("Erreur : Votre solde de quotite est insuffisant!");
        $html_err->setMessage($msg);
        $html_err->addButton(BUTTON_OK, 'Gen-11');
        $html_err->buildHTML();
        echo $html_err->HTML_code;
        exit();
      }
    }
  }

  // Déboursement des fonds
  $myErr = deboursementCredit($SESSION_VARS['infos_doss'], $SESSION_VARS['mode_debour'], $SESSION_VARS['dest_debour'], $global_id_guichet);
  unset($SESSION_VARS['cptes_prelev_frais']);

  if ($myErr->errCode == NO_ERR) {
    $array_his = $myErr->param;
    if ($data_agc['quotite'] == 't'){
      $data_cli = getClientDatas($global_id_client);
      if($data_cli['mnt_quotite'] > 0) {
        $quotite_dispo_apres = $data_cli['mnt_quotite'] - $mnt_prem_ech;
        $DATA_QUOTITE = array();
        $DATA_QUOTITE["id_client"] = $global_id_client;
        $DATA_QUOTITE["quotite_avant"] = $quotite_dispo['mnt_quotite'];
        $DATA_QUOTITE["quotite_apres"] = $quotite_dispo_apres;
        $DATA_QUOTITE["mnt_quotite"] = $quotite_dispo_apres;
        $DATA_QUOTITE["date_modif"] = date('r');
        $DATA_QUOTITE["raison_modif"] = 'Déboursement de fonds';
        $ajout_quotite = ajouterQuotite($DATA_QUOTITE);

        $DATA_QUOTITE_UPDATE = array();
        $DATA_QUOTITE_WHERE = array();
        $DATA_QUOTITE_UPDATE["mnt_quotite"] = $quotite_dispo_apres;
        $DATA_QUOTITE_WHERE['id_client'] = $global_id_client;
        $update_client = update_quotite_client($DATA_QUOTITE_UPDATE,$DATA_QUOTITE_WHERE);
      }
    }

    //$SESSION_VARS["infos_doss"]['prelev_mnt_frais']=$SESSION_VARS['infos_prod']["prelev_frais_doss"];
    print_attest_debours($SESSION_VARS["infos_doss"], $array_his, $SESSION_VARS['dest_debour']);
    $msg = new HTML_message(_("Confirmation déboursement des fonds du crédit"));
    $message = _("Crédit déboursé avec succès !");
    $message .= "<br /><br />N° de transactions :";
    foreach($array_his as $id_doss=>$id_his )
			$message .="<br /> <B><code>".sprintf("%09d", $id_his)."</code></B>";
    $msg->setMessage($message);
    $msg->addButton(BUTTON_OK,"Gen-11");
    $msg->buildHTML();
    echo $msg->HTML_code;

    if (!isClientDebiteur($global_id_client))
      $global_client_debiteur = false;
  } else {
    $html_err = new HTML_erreur(_("Echec du déboursement des fonds."));
    $html_err->setMessage(_("Erreur")." : ".$error[$myErr->errCode]."<br />"._("Paramètre")." : ".$myErr->param);
    $html_err->addButton("BUTTON_OK", 'Gen-11');
    $html_err->buildHTML();
    echo $html_err->HTML_code;
  }
}
/*}}}*/

/*{{{ Dbd-9 : Impression de l'échéancier */
else if  ($global_nom_ecran == 'Dbd-9') {
  require_once 'modules/rapports/xml_echeancier.php';
  require_once 'modules/rapports/xslt.php';
                                                 
  $xml = xml_echeancier_theorique($SESSION_VARS['infos_doss']);

  $fichier_pdf = xml_2_xslfo_2_pdf($xml, 'simulation_echeancier.xslt');

  echo get_show_pdf_html("Dbd-3", $fichier_pdf);

  ajout_historique(350, NULL, NULL, $global_nom_login, date("r"), NULL);

}
/*}}}*/

else signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("L'écran %s n'a pas pu être trouvé"), $global_nom_ecran));

?>