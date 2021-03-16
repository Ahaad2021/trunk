<?php

/* vim: set expandtab softtabstop=2 shiftwidth=2: */
/**
 * Génère le code XML pour les rapports agence
 * @package Rapports
 */

require_once 'lib/misc/xml_lib.php';
require_once 'lib/dbProcedures/credit.php';
require_once 'lib/misc/divers.php';
require_once 'lib/dbProcedures/rapports.php';
require_once 'modules/rapports/xslt.php';
require_once 'lib/dbProcedures/client.php';


// Impression attestation de déboursement
function print_attest_debours($infos_dossiers, $array_his, $dest_debour) {
  global $global_id_client, $global_id_agence;
  $formatA5 = false;
  $document = create_xml_doc("attest_debours", "attest_debours.dtd");

  //Element root
  $root = $document->root();

  //recuperation des données de l'agence'
  $AG = getAgenceDatas($global_id_agence);
  if($AG['imprimante_matricielle'] == 't'){
  	$format_A5 = true;
  }
  // Récupération des infos
  foreach ($infos_dossiers as $id_doss => $val_doss) {
    /* Récupération des garanties mobilisées */
    $gar_num_mob = 0;
    $gar_mat_mob = 0;
    $id_doss = $val_doss['id_doss'];
    $liste_gar = getListeGaranties($id_doss);
    foreach ($liste_gar as $key => $value) {
      if ($value['type_gar'] == 1) /* Garantie numéraire */
        $gar_num_mob += $value['montant_vente'];
      elseif ($value['type_gar'] == 2) /* garantie matérielle */
      $gar_mat_mob += $value['montant_vente'];
    }

    // Récupérer les données
    $CLI = getClientDatas($val_doss['id_client']);
    $ACC = getAccountDatas($val_doss['cpt_liaison']);

    //Corps
    $infos_doss = $root->new_child("infos_doss", "");

    //En-tête généraliste
    if($dest_debour == 1){ //destination = au guichet
    	$ref = gen_header($infos_doss, 'REC-DBG');
    }
    elseif($dest_debour == 2){ //par compte lié
    	$ref = gen_header($infos_doss, 'ATT-DBC');
    }
    else{ //par chèque
    	$ref = gen_header($infos_doss, 'REC-DBC');
    }

    $body = $infos_doss->new_child("body", "");
    $body->set_attribute("gs_cat", $val_doss['gs_cat']);
    if ($val_doss['gs_cat'] == 2) {
      $body->new_child("num_gs", $global_id_client);
      $body->new_child("nom_gs", getClientName($global_id_client));
    }

    $body->new_child("num_client", sprintf("%06d", $val_doss['id_client']));
    $body->new_child("nom_client", getClientName($val_doss['id_client']));
    $body->new_child("id_doss", $id_doss);
    if($val_doss["prelev_commission"] == 't' &&  $val_doss["prelev_mnt_frais"] == 2){
    	$body->new_child("mnt_debours", afficheMontant($val_doss["cre_mnt_a_deb"]-$val_doss['transfert_com']['mnt_commission']-$val_doss['transfert_com']['mnt_tax_commission']-$val_doss['transfert_com']['mnt_assurance']-$val_doss['transfert_frais']['mnt_frais']-$val_doss['transfert_frais']['mnt_tax_frais'], true));
    }else{
        $body->new_child("mnt_debours", afficheMontant($val_doss["cre_mnt_a_deb"], true));
    }
    $body->new_child("gar_mat", afficheMontant($val_doss['gar_mat'], true));
 	  $body->new_child("gar_num", afficheMontant($val_doss['gar_num'], true));
    $gar_tot_a_bloquer = recupMontant($val_doss['gar_num']) + recupMontant($val_doss['gar_mat']);
    if(($dest_debour == 3) && (isset($val_doss['data_chq']['num_piece'])))
    	$body->new_child("num_cheque", sprintf("%d", $val_doss['data_chq']['num_piece']));
    $body->new_child("gar_tot_a_bloquer", afficheMontant($gar_tot_a_bloquer, true));
    $gar_tot_mob = recupMontant($val_doss['gar_num_mob']) + recupMontant($val_doss['gar_mat_mob']);
    $body->new_child("gar_mat_mob", afficheMontant($gar_mat_mob, true));
 	  $body->new_child("gar_num_mob", afficheMontant($gar_num_mob, true));
 		$gar_tot_mob = recupMontant($gar_mat_mob) + recupMontant($gar_num_mob);
    $body->new_child("gar_tot_mob", afficheMontant($gar_tot_mob, true));
    $body->new_child("mnt_gar_encours", afficheMontant($val_doss['gar_num_encours'], true));
    if(isset($val_doss['transfert_com']['mnt_commission']))
     $body->new_child("mnt_com", afficheMontant($val_doss['transfert_com']['mnt_commission'], true));
    if(isset($val_doss['transfert_com']['mnt_tax_commission']))
     $body->new_child("mnt_tax_com", afficheMontant($val_doss['transfert_com']['mnt_tax_commission'], true));
    if(isset($val_doss['transfert_ass']['mnt_assurance']))
     $body->new_child("mnt_ass", afficheMontant($val_doss['transfert_ass']['mnt_assurance'], true));
    if(isset($val_doss['transfert_frais']['mnt_frais']))
     $body->new_child("mnt_frais", afficheMontant($val_doss['transfert_frais']['mnt_frais'], true));
    if(isset($val_doss['transfert_frais']['mnt_tax_frais']))
     $body->new_child("mnt_tax_frais", afficheMontant($val_doss['transfert_frais']['mnt_tax_frais'], true));
    if(($dest_debour == 2) && (isset($ACC["solde"])))
    	$body->new_child("solde", afficheMontant($ACC["solde"], true));
    $body->new_child("num_trans", sprintf("%09d", $array_his[$id_doss]));

  }
  $xml = $document->dump_mem(true);

    //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
    if($format_A5){
	  	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'attest_deboursA5.xslt');
	  } else {
	  	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'attest_debours.xslt');
	  }

    // Affichage du rapport dans une nouvelle fenêtre
    echo get_show_pdf_html(NULL, $fichier_pdf);

	return true;
}
          
 function print_recu_remboursement_lcr($infos_dossiers, $date_remb = NULL, $type_doc) {
  global $global_id_client, $global_id_agence;
  $format_A5 = false;

  // Génération des reçus pour chaque dossier remboursé
  $document = create_xml_doc("recu", "remboursement_lcr.dtd");
  //Element root
  $root = $document->root();

  //recuperation des données de l'agence'
  $AG = getAgenceDatas($global_id_agence);
  if($AG['imprimante_matricielle'] == 't'){
  	$format_A5 = true;
  }
  foreach ($infos_dossiers as $id_doss => $val_doss) {
    $infos_doss = $root->new_child("infos_doss", "");

    //En-tête généraliste
    gen_header($infos_doss, $type_doc);

    $solde_capital = getCapitalRestantDuLcr($id_doss, php2pg(date("d/m/Y")));
    $int_gar_pen = getSoldeInteretGarPen($id_doss);
    if ($solde_capital == "") {
      $solde_capital = 0;
    }
    $solde_int = $int_gar_pen['solde_int'];
    $solde_frais = getCalculFraisLcr($id_doss, php2pg((date("d/m/Y"))));
    $solde_gar = $int_gar_pen['solde_gar'];
    $solde_pen = $int_gar_pen['solde_pen'];
    if ($solde_int == "") {
      $solde_int = 0;
    }
    if ($solde_frais == "") {
      $solde_frais = 0;
    }
    if ($solde_gar == "") {
      $solde_gar = 0;
    }
    if ($solde_pen == "") {
      $solde_pen = 0;
    }

    $body = $infos_doss->new_child("body", "");
    $body->set_attribute("gs_cat", $val_doss['gs_cat']);
    if ($val_doss['gs_cat'] == 2) {
      $body->new_child("num_gs", $global_id_client);
      $body->new_child("nom_gs", getClientName($global_id_client));
    }

    $body->new_child("iddossier", $id_doss);
    if ($date_remb != NULL) {
      $body->new_child("date_remb", $date_remb);
    }
    $body->new_child("num_client", $val_doss['id_client']);
    $body->new_child("nom_client", getClientName($val_doss['id_client']));
    $body->new_child("mnt_rbt", afficheMontant($val_doss['mnt_remb'], true));
    $body->new_child("encours", afficheMontant($solde_capital, true));
    $body->new_child("interet", afficheMontant($solde_int, true));
    $body->new_child("frais", afficheMontant($solde_frais, true));
    $body->new_child("garantie", afficheMontant($solde_gar, true));
    $body->new_child("penalite", afficheMontant($solde_pen, true));
  }
  $xml = $document->dump_mem(true);

  //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
  if($format_A5){
  	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'remboursementA5_lcr.xslt');
  } else {
  	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'remboursement_lcr.xslt');
  }

  // Affichage du rapport dans une nouvelle fenêtre
  echo get_show_pdf_html(NULL, $fichier_pdf);
 }
 
 function print_recu_remboursement($infos_dossiers, $date_remb = NULL, $type_doc) {
  global $global_id_client, $global_id_agence;
  $format_A5 = false;

  // Génération des reçus pour chaque dossier remboursé
  $document = create_xml_doc("recu", "remboursement.dtd");
  //Element root
  $root = $document->root();

  //recuperation des données de l'agence'
  $AG = getAgenceDatas($global_id_agence);
  if($AG['imprimante_matricielle'] == 't'){
  	$format_A5 = true;
  }
  foreach ($infos_dossiers as $id_doss => $val_doss) {
    $infos_doss = $root->new_child("infos_doss", "");

    //En-tête généraliste
    gen_header($infos_doss, $type_doc);

    $solde_capital = getSoldeCapital($id_doss);
    $int_gar_pen = getSoldeInteretGarPen($id_doss);
    if ($solde_capital == "")
      $solde_capital = 0;
    $solde_int = $int_gar_pen['solde_int'];
    $solde_gar = $int_gar_pen['solde_gar'];
    $solde_pen = $int_gar_pen['solde_pen'];
    if ($solde_capital == "")
      $solde_int = 0;
    if ($solde_capital == "")
      $solde_gar = 0;
    if ($solde_capital == "")
      $solde_pen = 0;

    $body = $infos_doss->new_child("body", "");
    $body->set_attribute("gs_cat", $val_doss['gs_cat']);
    if ($val_doss['gs_cat'] == 2) {
      $body->new_child("num_gs", $global_id_client);
      $body->new_child("nom_gs", getClientName($global_id_client));
    }

    $body->new_child("iddossier", $id_doss);
    if ($date_remb != NULL) {
      $body->new_child("date_remb", $date_remb);
    }
    $body->new_child("num_client", $val_doss['id_client']);
    $body->new_child("nom_client", getClientName($val_doss['id_client']));
    $body->new_child("mnt_rbt", afficheMontant($val_doss['mnt_remb'], true));
    $body->new_child("encours", afficheMontant($solde_capital, true));
    $body->new_child("interet", afficheMontant($solde_int, true));
    $body->new_child("garantie", afficheMontant($solde_gar, true));
    $body->new_child("penalite", afficheMontant($solde_pen, true));
  }
  $xml = $document->dump_mem(true);

  //Génération du XSL-FO (grâce au XSLT) et du PDF (grâce à FOP)
  if($format_A5){
  	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'remboursementA5.xslt');
  } else {
  	$fichier_pdf = xml_2_xslfo_2_pdf($xml, 'remboursement.xslt');
  }

  // Affichage du rapport dans une nouvelle fenêtre
  echo get_show_pdf_html(NULL, $fichier_pdf);
}

function xml_credits_retard($gestionnaire = 0, $etat = 0, $export_csv = false) {
  /* Génère les données en XML concernant tous les crédits en retard */

  global $global_multidevise,$global_id_agence;
  global $global_monnaie;

  //Récupère les données
  $data = get_credits_retard($gestionnaire, $etat);
  $produits = getProdInfoByID();
  setMonnaieCourante($produits['devise']);

  //XML
  $document = create_xml_doc("credits_retard", "credits_retard.dtd");

  //Element root
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-RET');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  $gestionnaire = array (
                    _("Gestionnaire") => (getLibel("ad_uti", $_POST["gest"]) == "")? _("Tous"):getLibel("ad_uti", $_POST["gest"])
                  );
  gen_criteres_recherche($header_contextuel, $gestionnaire);
  $infos_synthetiques = $header_contextuel->new_child("infos_synthetiques", "");
  $infos_synthetiques->new_child("nbre_credits", $data['nbre_credits']);
  $infos_synthetiques->new_child("nbre_credits_retard", $data['nbre_credits_retard']);
  if ($data['nbre_credits'] > 0)
    $infos_synthetiques->new_child("prc_credits_retard", affichePourcentage($data['nbre_credits_retard'] / $data['nbre_credits'], 2));
  else
    $infos_synthetiques->new_child("prc_credits_retard", "");
  $infos_synthetiques->new_child("portefeuille", afficheMontant($data['portefeuille'], true));
  $infos_synthetiques->new_child("total_solde_cap", afficheMontant($data['total_solde_cap'], true));
  if ($data['portefeuille'] > 0)
    $infos_synthetiques->new_child("prc_portefeuille_retard", affichePourcentage($data['total_solde_cap'] / $data['portefeuille'], 2));
  else
    $infos_synthetiques->new_child("prc_portefeuille_retard", "");

  $infos_synthetiques->new_child("total_solde_int", afficheMontant($data['total_solde_int'], false, $export_csv));
  $infos_synthetiques->new_child("total_solde_pen", afficheMontant($data['total_solde_pen'], false, $export_csv));
  $infos_synthetiques->new_child("total_retard_cap", afficheMontant($data['total_retard_cap'], false, $export_csv));
  $infos_synthetiques->new_child("total_retard_int", afficheMontant($data['total_retard_int'], false, $export_csv));
  $infos_synthetiques->new_child("total_epargne_nantie", afficheMontant($data['total_gar_num'], false, $export_csv));
  $infos_synthetiques->new_child("total_prov_mnt", afficheMontant($data['total_prov_mnt'], false, $export_csv));

  //Pour chaque produit
  $total = array ();
  reset($produits);
  while (list ($key, $value) = each($produits)) {
    $produit[$key] = $root->new_child("produit", "");
    $produit[$key]->new_child("lib_prod", $value['libel'] . " (" . $value["devise"] . ")");

    $total[$value['id']]['tot_mnt_debloc'] = 0;
    $total[$value['id']]['tot_solde_cap'] = 0;
    $total[$value['id']]['tot_solde_int'] = 0;
    $total[$value['id']]['tot_solde_gar'] = 0;
    $total[$value['id']]['tot_solde_pen'] = 0;
    $total[$value['id']]['tot_retard_cap'] = 0;
    $total[$value['id']]['tot_retard_int'] = 0;
    $total[$value['id']]['tot_retard_gar'] = 0;
    $total[$value['id']]['tot_nbre_ech_retard'] = 0;
    $total[$value['id']]['tot_epargne_nantie'] = 0;
    $total[$value['id']]['tot_prov_mnt'] = 0;

  }

  //Pour chaque crédit en retard
  reset($data['detail']);
  while (list ($key, $value) = each($data['detail'])) {
    $credit_retard = $produit[$value['id_prod']]->new_child("credit_retard", "");
    if ((($value["gs_cat"] == 1) && ($value["membre"] == 0))||($value["gs_multiple"] == "OK")) {
    	$credit_retard->new_child("groupe_gs", "groupe");
    } elseif($value["membre"] == 1) {
    	$credit_retard->new_child("membre_gs", "membre");
    }
    $credit_retard->new_child("num_doss", sprintf("%06d", $key));
    $credit_retard->new_child("num_client", makeNumClient($value['id_client']));
    $credit_retard->new_child("nom_client", $value['nom_client']);
    $credit_retard->new_child("gestionnaire", $value['nom_gestionnaire']);
    $credit_retard->new_child("date_debloc", pg2phpDate($value['cre_date_debloc']));
    $credit_retard->new_child("mnt_debloc", afficheMontant($value['cre_mnt_octr'], false, $export_csv));
    $credit_retard->new_child("solde_cap", afficheMontant($value['solde_cap'], false, $export_csv));
    $credit_retard->new_child("solde_int", afficheMontant($value['solde_int'], false, $export_csv));
    $credit_retard->new_child("solde_gar", afficheMontant($value['solde_gar'], false, $export_csv));
    $credit_retard->new_child("solde_pen", afficheMontant($value['solde_pen'], false, $export_csv));
    $credit_retard->new_child("retard_cap", afficheMontant($value['retard_cap'], false, $export_csv));
    $credit_retard->new_child("retard_int", afficheMontant($value['retard_int'], false, $export_csv));
    $credit_retard->new_child("retard_gar", afficheMontant($value['retard_gar'], false, $export_csv));
    $credit_retard->new_child("nbre_ech_retard", $value['nbre_ech_retard']);
    $credit_retard->new_child("prov_mnt", afficheMontant($value['prov_mnt'], false, $export_csv));

	if (!isset($value["membre"]) || (($value["gs_cat"] == 1) && ($value["membre"] == 0)) || (($value["gs_cat"] == 2)))
    	$credit_retard->new_child("nbre_jours_retard", nbreDiffJours(pg2phpDate($value['date_min']), date("d/m/Y")));
    $credit_retard->new_child("epargne_nantie", afficheMontant($value['gar_num'], false));
    $credit_retard->new_child("etat", getLibel("adsys_etat_credits",$value['cre_etat']));

    $total[$value['id_prod']]['tot_mnt_debloc'] += $value['cre_mnt_octr'];
    $total[$value['id_prod']]['tot_solde_cap'] += $value['solde_cap'];
    $total[$value['id_prod']]['tot_solde_int'] += $value['solde_int'];
    $total[$value['id_prod']]['tot_solde_gar'] += $value['solde_gar'];
    $total[$value['id_prod']]['tot_solde_pen'] += $value['solde_pen'];
    $total[$value['id_prod']]['tot_retard_cap'] += $value['retard_cap'];
    $total[$value['id_prod']]['tot_retard_int'] += $value['retard_int'];
    $total[$value['id_prod']]['tot_retard_gar'] += $value['retard_gar'];
    $total[$value['id_prod']]['tot_nbre_ech_retard'] += $value['nbre_ech_retard'];
    $total[$value['id_prod']]['tot_epargne_nantie'] += $value['gar_num'];
    $total[$value['id_prod']]['tot_prov_mnt'] += $value['prov_mnt'];
  }

  reset($produits);
  while (list ($key, $value) = each($produits)) {
    $xml_total = $produit[$key]->new_child("xml_total", "");

    $xml_total->new_child("tot_mnt_debloc", afficheMontant($total[$value['id']]['tot_mnt_debloc'], false, $export_csv));
    $xml_total->new_child("tot_solde_cap", afficheMontant($total[$value['id']]['tot_solde_cap'], false, $export_csv));
    $xml_total->new_child("tot_solde_int", afficheMontant($total[$value['id']]['tot_solde_int'], false, $export_csv));
    $xml_total->new_child("tot_solde_gar", afficheMontant($total[$value['id']]['tot_solde_gar'], false, $export_csv));
    $xml_total->new_child("tot_solde_pen", afficheMontant($total[$value['id']]['tot_solde_pen'], false, $export_csv));
    $xml_total->new_child("tot_retard_cap", afficheMontant($total[$value['id']]['tot_retard_cap'], false, $export_csv));
    $xml_total->new_child("tot_retard_int", afficheMontant($total[$value['id']]['tot_retard_int'], false, $export_csv));
    $xml_total->new_child("tot_retard_gar", afficheMontant($total[$value['id']]['tot_retard_gar'], false, $export_csv));
    $xml_total->new_child("tot_nbre_ech_retard", afficheMontant($total[$value['id']]['tot_nbre_ech_retard'], false, $export_csv));
    $xml_total->new_child("tot_epargne_nantie", afficheMontant($total[$value['id']]['tot_epargne_nantie'], false, $export_csv));
    $xml_total->new_child("tot_prov_mnt", afficheMontant($total[$value['id']]['tot_prov_mnt'], false, $export_csv));

  }

  return $document->dump_mem(true);
}

function xml_risques_credits($gestionnaire = 0, $export_date, $date_debloc_inf, $date_debloc_sup, $id_prd, $export_csv = false) {
  /* Génère les données en XML concernant tous les risques de crédits */

  global $global_multidevise;
  global $global_monnaie, $global_id_agence;

  $produits = getProdInfoByID();
  $etat_credits = array();
  $etat_credits = getTousEtatCredit();
  if ($global_multidevise)
    setMonnaieCourante($global_monnaie);
  //XML
  $document = create_xml_doc("risques_credits", "risques_credits.dtd");

  //Element root
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-SRC');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");

  /*if (($gestionnaire == 0) || ($gestionnaire == "") || ($gestionnaire == null)) {
  	$criteres = array (
  			_("Gestionnaire") => _("Tous"),
  			_("Date") => date($export_date),//ticket 489
  			_("Date début du déboursement") => date($date_debloc_inf),
  			_("Date fin de déboursement") => date($date_debloc_sup)
  	);
  } else {
  	$criteres = array (
  			_("Gestionnaire") => getLibel("ad_uti", $_POST["gest"]),
  			_("Date") => date($export_date),//ticket 489
  			_("Date début du déboursement") => date($date_debloc_inf),
  			_("Date fin de déboursement") => date($date_debloc_sup)
  	);
  }*/
  //ticket 455
  if ((($gestionnaire == 0) || ($gestionnaire == "") || ($gestionnaire == null)) AND(($id_prd == 0) || ($id_prd == "") || ($id_prd == null))) {
  	$criteres = array (
  			_("Gestionnaire") => _("Tous"),
  			_("Date") => date($export_date),
  			_("Date début du déboursement") => date($date_debloc_inf),
  			_("Date fin de déboursement") => date($date_debloc_sup),
  			_("Type de produit de crédit") => _("Tous")
  	);
  } else if(($gestionnaire > 0)AND($id_prd > 0)) {
  	$criteres = array (
  			_("Gestionnaire") => getLibel("ad_uti", $_POST["gest"]),
  			_("Date") => date($export_date),
  			_("Date début du déboursement") => date($date_debloc_inf),
  			_("Date fin de déboursement") => date($date_debloc_sup),
  			_("Type de produit de crédit") => getLibelPrdt($id_prd, "adsys_produit_credit")
  	);
  }else if(($gestionnaire > 0)AND(($id_prd == 0) || ($id_prd == "") || ($id_prd == null)) ){
  	$criteres = array (
  			_("Gestionnaire") => getLibel("ad_uti", $_POST["gest"]),
  			_("Date") => date($export_date),
  			_("Date début du déboursement") => date($date_debloc_inf),
  			_("Date fin de déboursement") => date($date_debloc_sup),
  			_("Type de produit de crédit") => _("Tous")
  	);
  }else if((($gestionnaire == 0) || ($gestionnaire == "") || ($gestionnaire == null)) AND ($id_prd > 0) ){
  		$criteres = array (
  				_("Gestionnaire") => _("Tous"),
  				_("Date") => date($export_date),
  				_("Date début du déboursement") => date($date_debloc_inf),
  				_("Date fin de déboursement") => date($date_debloc_sup),
  				_("Type de produit de crédit") => getLibelPrdt($id_prd, "adsys_produit_credit")
  		);
  }


  gen_criteres_recherche($header_contextuel, $criteres);
  $infos_synthetiques = $header_contextuel->new_child("infos_synthetiques", "");
  if($export_date == NULL){
  	 $export_date = date("Y")."-".date("m")."-".date("d");
    }
    //get data for the report
  $data = get_risques_credits($gestionnaire, $export_date, $date_debloc_inf, $date_debloc_sup, $id_prd);
  
  $infos_synthetiques->new_child("nbre_credits", $data['nbre_credits']);
  $infos_synthetiques->new_child("nbre_credits_retard", $data['nbre_credits_retard']);
  if ($data['nbre_credits'] > 0)
    $infos_synthetiques->new_child("prc_credits_retard", affichePourcentage($data['nbre_credits_retard'] / $data['nbre_credits'], 2));
  else
    $infos_synthetiques->new_child("prc_credits_retard", "");
    $infos_synthetiques->new_child("portefeuille", afficheMontant($data['portefeuille'], true));
    $infos_synthetiques->new_child("portefeuille_retard", afficheMontant($data['portefeuille_retard'], true));
    if ($data['portefeuille'] > 0)
     $infos_synthetiques->new_child("prc_portefeuille_retard", affichePourcentage($data['portefeuille_retard'] / $data['portefeuille'], 2));
     else
     $infos_synthetiques->new_child("prc_portefeuille_retard", "");

     $infos_synthetiques->new_child("total_solde_int", afficheMontant($data['total_solde_int'], false, $export_csv));
     $infos_synthetiques->new_child("total_epargne_nantie", afficheMontant($data['total_solde_gar'], false, $export_csv));
     $infos_synthetiques->new_child("total_solde_pen", afficheMontant($data['total_solde_pen'], false, $export_csv));
     $infos_synthetiques->new_child("total_retard_cap", afficheMontant($data['total_retard_cap'], false, $export_csv));
     $infos_synthetiques->new_child("total_retard_int", afficheMontant($data['total_retard_int'], false, $export_csv));
     $infos_synthetiques->new_child("total_retard_gar", afficheMontant($data['total_retard_gar'], false, $export_csv));

     // Pour chaque produit
     $total = array ();
     reset($etat_credits);
    // changer l'ordre de l'état, le plus souffrant à l'état le plus sain
     $etat_credits = array_reverse($etat_credits, true);

     while (list ($key_etat_cre, $value_etat_cre) = each($etat_credits)) {
       $etat_credit[$key_etat_cre] = $root->new_child("etat_credit", "");
       $etat_credit[$key_etat_cre]->new_child("lib_etat_credit", $value_etat_cre['libel']);
       reset($produits);
       while (list ($key, $value) = each($produits)) {
       $produit[$key_etat_cre][$value['id']] = $etat_credit[$key_etat_cre]->new_child("produit", "");
       $produit[$key_etat_cre][$value['id']]->new_child("lib_prod", $value['libel'] . " (" . $value["devise"] . ")");
       $total[$key_etat_cre][$value['id']]['tot_mnt_debloc'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_solde_cap'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_solde_int'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_solde_gar'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_solde_pen'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_retard_cap'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_retard_int'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_retard_gar'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_nbre_ech_retard'] = 0;
       $total[$key_etat_cre][$value['id']]['tot_epargne_nantie'] = 0;

     }
   }

   //Pour chaque crédit
   reset($data['detail']);
   while (list ($key, $value) = each($data['detail'])) {
   	//if($value['cre_etat'] > 1){
     $risque_credit = $produit[$value['cre_etat']][$value['id_prod']]->new_child("risque_credit", "");
     $risque_credit->new_child("num_doss", sprintf("%06d", $key));
     $risque_credit->new_child("duree", $value['duree']);
     $risque_credit->new_child("num_client", makeNumClient($value['id_client']));
     $risque_credit->new_child("nom_client", $value['nom_client']);
     $risque_credit->new_child("statut_jur", $value['statut_jur']);
     $risque_credit->new_child("sexe", $value['sexe']);
     $risque_credit->new_child("gestionnaire", $value['nom_gestionnaire']);
     $risque_credit->new_child("date_debloc", pg2phpDate($value['cre_date_debloc']));
     $risque_credit->new_child("mnt_debloc", afficheMontant($value['cre_mnt_octr'], false, $export_csv));
     $risque_credit->new_child("date_dernier_remb", pg2phpDate($value['date_dernier_remb']));
     $risque_credit->new_child("date_dernier_ech_remb", pg2phpDate($value['date_dernier_ech_remb']));
     $risque_credit->new_child("solde_cap", afficheMontant($value['solde_cap'], false, $export_csv));
     $risque_credit->new_child("epargne_nantie", afficheMontant($value['solde_gar'], false, $export_csv));
     $risque_credit->new_child("solde_pen", afficheMontant($value['solde_pen'], false, $export_csv));
     $risque_credit->new_child("retard_cap", afficheMontant($value['retard_cap'], false, $export_csv));

     $risque_credit->new_child("nbre_ech_retard", $value['nbre_ech_retard']);
     $risque_credit->new_child("nbre_jours_retard", $value['nbr_jours_retard']);
     $risque_credit->new_child("prov_mnt", afficheMontant($value['prov_mnt'], false, $export_csv));
     $total[$value['cre_etat']][$value['id_prod']]['tot_mnt_debloc'] += $value['cre_mnt_octr'];
     $total[$value['cre_etat']][$value['id_prod']]['tot_solde_cap'] += $value['solde_cap'];
     $total['tot_solde_gar'] += $value['solde_gar'];
     $total[$value['cre_etat']][$value['id_prod']]['tot_solde_pen'] += $value['solde_pen'];
     $total[$value['cre_etat']][$value['id_prod']]['tot_retard_cap'] += $value['retard_cap'];
     $total['tot_retard_int'] += $value['retard_int'];
     $total[$value['cre_etat']][$value['id_prod']]['tot_nbre_ech_retard'] += $value['nbre_ech_retard'];
     $total[$value['cre_etat']][$value['id_prod']]['tot_epargne_nantie'] += $value['solde_gar'];
     $total[$value['cre_etat']][$value['id_prod']]['tot_prov_mnt'] += $value['prov_mnt'];
   	//}
   }

   reset($etat_credits);
   while (list ($key_etat_cre, $value_etat_cre) = each($etat_credits)) {
     reset($produits);
     while (list ($key, $value) = each($produits)) {
       if ($total[$key_etat_cre][$value['id']]['tot_mnt_debloc'] == 0)
        $etat_credit[$key_etat_cre]->remove_child($produit[$key_etat_cre][$key]);
       else{
        $xml_total = $produit[$key_etat_cre][$key]->new_child("xml_total", "");

        $xml_total->new_child("tot_mnt_debloc", afficheMontant($total[$key_etat_cre][$value['id']]['tot_mnt_debloc'], false, $export_csv));
        $xml_total->new_child("tot_solde_cap", afficheMontant($total[$key_etat_cre][$value['id']]['tot_solde_cap'], false, $export_csv));
        $xml_total->new_child("tot_solde_int", afficheMontant($total[$key_etat_cre][$value['id']]['tot_solde_int'], false, $export_csv));
        $xml_total->new_child("tot_solde_gar", afficheMontant($total[$key_etat_cre][$value['id']]['tot_solde_gar'], false, $export_csv));
        $xml_total->new_child("tot_solde_pen", afficheMontant($total[$key_etat_cre][$value['id']]['tot_solde_pen'], false, $export_csv));
        $xml_total->new_child("tot_retard_cap", afficheMontant($total[$key_etat_cre][$value['id']]['tot_retard_cap'], false, $export_csv));
        $xml_total->new_child("tot_retard_int", afficheMontant($total[$key_etat_cre][$value['id']]['tot_retard_int'], false, $export_csv));
        $xml_total->new_child("tot_retard_gar", afficheMontant($total[$key_etat_cre][$value['id']]['tot_retard_gar'], false, $export_csv));
        $xml_total->new_child("tot_nbre_ech_retard", afficheMontant($total[$key_etat_cre][$value['id']]['tot_nbre_ech_retard'], false, $export_csv));
        $xml_total->new_child("tot_epargne_nantie", afficheMontant($total[$key_etat_cre][$value['id']]['tot_epargne_nantie'], false, $export_csv));
        $xml_total->new_child("tot_prov_mnt", afficheMontant($total[$key_etat_cre][$value['id']]['tot_prov_mnt'], false, $export_csv));
       }
     }
   }
   
   return $document->dump_mem(true);
}

function key_match($val1, $val2, $val3, $val4, $operator) {
  // Fonction qui effectue une comparaison entre deux valeurs selon l'opérateur fourni en paramètre
  // La règle est la suivante
  // $operateur = '=' => TRUE si $val1 = $val2
  //              '<' => TRUE si $val1 < $val2
  //              '>' => TRUE si $val1 > $val2
  //              '><' => TRUE si $val1 >= $val2 et $val1 <= $val4
  //              '==' => TRUE si $val1 = $val2 et $val3 = $val4

  if (($operator == '=') && ($val1 == $val2))
    return true;
  if (($operator == '<') && ($val1 < $val2))
    return true;
  if (($operator == '>') && ($val1 > $val2))
    return true;
  if (($operator == '><') && ($val1 >= $val2) && ($val1 <= $val4))
    return true;
  if (($operator == '==') && ($val1 == $val2) && ($val3 == $val4))
    return true;
  return false;
}

function get_total($data, $export_date) {

  // Fonction appelée par xml_repartition_credit et qui calcule les totaux
  // IN : $data = array généré par get_repartition_credit dans rapports.php
  // OUT: un array à trois éléments :
  //        array ('nombre' => Nombre total de crédits
  //               'mnt'    => Portefeuille de crédit
  //               'retard' => Portefeuille en retard

	
  $retour['nbre'] = 0;
  $retour['mnt'] = 0;
  $retour['retard'] = 0;

  reset($data);
  while (list ($key1, $value1) = each($data)) { //Pour chaque agence
    while (list ($key, $value) = each($value1)) { //Pour chaque dossier
      ++ $retour['nbre'];
      //valeur mnt a une date anterieur _t413
            // $retour['mnt'] += $value['solde_cap'];
                 if ($value['is_ligne_credit'] == 't') {
                     $retour['mnt'] += getCapitalRestantDuLcr($value['id_doss'], $export_date);
                 } else {
                     $retour['mnt'] += $value["cre_mnt_octr"] - $value["mnt_cred_paye"];
                 }
                 $retour['mnt'] = calculeCV($devise, $global_monnaie,  $retour['mnt']);
                 
      if ($value["cre_etat"] > 1){
      	//valeur mnt a une date anterieur _t413
             //  $retour['retard'] += $value['solde_cap'];
        if ($value['is_ligne_credit'] == 't') {
            $retour['retard'] += getCapitalRestantDuLcr($value['id_doss'], $export_date);
        } else {
            $retour['retard'] += $value["cre_mnt_octr"] - $value["mnt_cred_paye"];
        }
      	$retour['retard']  = calculeCV($devise, $global_monnaie, $retour['retard']);
      }
      
    }
  }
  return $retour;
}

function get_tranche_credit($data, $total, $key1, $val1, $key2, $val2, $operator,$export_date) {
  /* Cette fonction va, à partir des données, renvoyer les stats nécessaires pour la tranche demandée */

  $retour['nbre'] = 0;
  $retour['nbre_prc'] = 0;
  $retour['mnt'] = 0;
  $retour['mnt_prc'] = 0;
  $retour['retard'] = 0;
  $retour['retard_prc'] = 0;
  $mnt_reech_ap_date_export = 0; // chercher le montant reechelonné aprs la date du rapport
  $test = array();
  
  if (isset ($data)) {
    reset($data);
    while (list (, $info_agence) = each($data)) { //Pour chaque agence
      while (list (, $info_doss) = each($info_agence)) { //Pour chaque dossier
        if (key_match($info_doss[$key1], $val1, $info_doss[$key2], $val2, $operator)) {
         #Si le crédit appartient à la tranche
          ++ $retour['nbre'];
          //$retour['mnt'] += $info_doss['solde_cap'];
          
          if ($info_doss["is_ligne_credit"] == 't') {
              $retour['mnt'] += getCapitalRestantDuLcr($info_doss["id_doss"], $export_date);
          } else {
              $retour['mnt'] += $info_doss["cre_mnt_octr"] - $info_doss["mnt_cred_paye"];
          }

          $retour['mnt'] = calculeCV($devise, $global_monnaie,  $retour['mnt']);
          
           //credit non sain:
          if ($info_doss["cre_etat"] > 1)
            //$retour['retard'] += $info_doss['solde_cap'];
          	$retour['retard'] += $info_doss["cre_mnt_octr"] - $info_doss["mnt_cred_paye"];
      	    $retour['retard']  = calculeCV($devise, $global_monnaie, $retour['retard']);
        
        }
      }
  
    }
  }

  $retour['nbre_prc'] = $retour['nbre'] / max($total['nbre'], 1);
  $retour['mnt_prc'] = $retour['mnt'] / max($total['mnt'], 1);
  $retour['retard_prc'] = $retour['retard'] / max($total['retard'], 1);

  return $retour;
}

function getDonneesRepartitionCredit ($list_agence, $val, $b1, $b2, $duree, $devise = NULL, $list_criteres) {
	global $adsys;
  global $global_multidevise,$global_id_agence;

  $tranche_data['total_data']['nbre'] = 0;
  $tranche_data['total_data']['mnt'] = 0;
  $tranche_data['total_data']['retard'] = 0;
  $tranche_data['liste_criteres'] = $list_criteres;
  foreach($list_agence as $key_id_ag =>$value) {
    setGlobalIdAgence($key_id_ag);
    $tranche_data['taille'] = 0;
    if ($key_id_ag != 0) {
  //Récupère les données
  if (($devise != NULL) && ($devise != '0') && ($global_multidevise)) {
    setMonnaieCourante($devise);
    // Récupération des infos sur les clients et leurs dossiers de crédit : Secteur d'activité, Localisation 1, Localisation 2, Stat Juridique, Sexe, Solde en capital, Solde en intérêt, ID du produit de crédit)
    //Liste critere date* 413
$result = get_repartition_credit($duree, $devise, $tranche_data['liste_criteres']["id_gest"] ,$tranche_data['liste_criteres']["Date"]);
    if ($result->errCode != NO_ERR) {
      return $result;
    } else {
      $tranche_data['rep'] = $result->param;
    }
  } else{
  	//Liste critere date* 413
  	$result = get_repartition_credit($duree, NULL, $tranche_data['liste_criteres']["id_gest"] ,$tranche_data['liste_criteres']["Date"]);
  	if ($result->errCode != NO_ERR) {
      return $result;
    } else {
      $tranche_data['rep'] = $result->param;
    }
  }

  if ($val == 1)
    $tranche_data['sect'] = get_secteurs_activite();
  else
    if ($val == 5) {
      //AT-33/AT-78 - Localisation Rwanda
      $Data_agence = getAgenceDatas($global_id_agence);
      if ($Data_agence['identification_client'] == 1){ //Localisation standard
        if ($b1 == 1)
          $tranche_data['loc'] = get_localisation(1);
        else
          $tranche_data['loc'] = get_localisation(2);
      }
      else{ //Localisation Rwanda
        $tranche_data['loc'] = get_localisation_rwanda($b1);
      }
    } else
      if ($val == 6) {
        if (($devise != NULL) && ($devise != '0') && ($global_multidevise))
          $tranche_data['pro_cre'] = get_produits_credit($devise);
        else
          $tranche_data['pro_cre'] = get_produits_credit();
      }
//Section totaliseur_ ligne final du rapport
  if ($tranche_data['rep'] != array ())
  	//get_total montant capital et capital retard à une date anterieur(fonction get_total modifier suite à t413)
    $tranche_donnees['total_data'][$key_id_ag] = get_total($tranche_data['rep'],$tranche_data['liste_criteres']["Date"]);
    $tranche_data['total_data']['nbre'] += $tranche_donnees['total_data'][$key_id_ag]['nbre'];
    $tranche_data['total_data']['mnt'] += $tranche_donnees['total_data'][$key_id_ag]['mnt'];
    $tranche_data['total_data']['retard'] += $tranche_donnees['total_data'][$key_id_ag]['retard'];
    
   

   //Pour chaque tranche
  $i = 0;
  while (1) {
    if ($val == 1) { //Si secteur d'activité
      if ($i < sizeof($tranche_data['sect'])) {
        $key_name = 'sect_act';
        $key_value = $tranche_data['sect'][$i]['id'];
        $operator = "=";
        $tranche_data['libel'][$i] = $tranche_data['sect'][$i]['libel'];
      } else
        break;
    } else
      if ($val == 2) { //Si montant octroyé
        if ($i < 3) {
          $key_name = 'cre_mnt_octr';
          switch ($i) {
          case 0 :
            $key_value = $b1;
            $operator = "<";
            $tranche_data['libel'][$i] = _("Moins de")." ". afficheMontant($b1, true);
            break;
          case 1 :
            $key_value = $b1;
            $key_value2 = $b2;
            $operator = "><";
            $tranche_data['libel'][$i] = sprintf(_("Compris entre %s et %s"),afficheMontant($b1, true),afficheMontant($b2, true));
            break;
          case 2 :
            $key_value = $b2;
            $operator = ">";
            $tranche_data['libel'][$i] = _("Supérieur à")." " . afficheMontant($b2, true);
            break;
          }
        } else
          break;
      }
    if ($val == 3) { //Si duree
      if (($duree == '') or ($duree == 1)) {
        if ($i < 3) {
          $key_name = 'duree_mois';
          switch ($i) {
          case 0 :
            $key_value = $b1;
            $operator = "<";
            $tranche_data['libel'][$i] = sprintf(_("Moins de %s mois"),$b1);
            break;
          case 1 :
            $key_value = $b1;
            $key_value2 = $b2;
            $operator = "><";
            $tranche_data['libel'][$i] = sprintf(_("Compris entre %s et %s mois"),$b1,$b2);
            break;
          case 2 :
            $key_value = $b2;
            $operator = ">";
            $tranche_data['libel'][$i] = sprintf(_("Supérieur à %s mois"),$b2);
            break;
          }
        } else
          break;
      }
      if ($duree == 2) {
        if ($i < 3) {
          $key_name = 'duree_mois';
          switch ($i) {
          case 0 :
            $key_value = $b1;
            $operator = "<";
            $tranche_data['libel'][$i] = sprintf(_("Moins de %s semaines"),$b1);
            break;
          case 1 :
            $key_value = $b1;
            $key_value2 = $b2;
            $operator = "><";
            $tranche_data['libel'][$i] = sprintf(_("Compris entre %s et %s semaines"),$b1,$b2);
            break;
          case 2 :
            $key_value = $b2;
            $operator = ">";
            $tranche_data['libel'][$i] = sprintf(_("Supérieur à %s semaines",$b2));
            break;
          }
        } else
          break;
      }
    }
    if ($val == 4) { //Si statut juridique
      if ($i < 5) {
        if ($i == 0) { // Si PP hommes
          $key_name = 'statut_juridique';
          $key_value = 1;
          $key_name2 = 'pp_sexe';
          $key_value2 = 1;
          $operator = "==";
          $tranche_data['libel'][$i] = _("Personnes physiques, hommes");
        } else
          if ($i == 1) { // Si PP femmes
            $key_name = 'statut_juridique';
            $key_value = 1;
            $key_name2 = 'pp_sexe';
            $key_value2 = 2;
            $operator = "==";
            $tranche_data['libel'][$i] = _("Personnes physiques, femmes");
          } else {
            $key_name = 'statut_juridique';
            $key_value = $i;
            $operator = "=";
            $tranche_data['libel'][$i] = adb_gettext($adsys["adsys_stat_jur"][$i]);
          }
      } else
        break;
    } else
      if ($val == 5) { //Si localisation 1 ou 2
        if ($i < sizeof($tranche_data['loc'])) {
          $key_name = 'id_loc' . $b1;
          //AT-33/AT-78
          $Data_agence = getAgenceDatas($global_id_agence);
          if ($Data_agence['identification_client'] == 2){ //Type localisaion Rwanda
            if ($b1 == 1){
              $key_name = 'province';
            }
            if ($b1 == 2){
              $key_name = 'district';
            }
            if ($b1 == 3){
              $key_name = 'secteur';
            }
            if ($b1 == 4){
              $key_name = 'cellule';
            }
            if ($b1 == 5){
              $key_name = 'village';
            }
          }
          $key_value = $tranche_data['loc'][$i]['id'];
          $operator = "=";
          $tranche_data['libel'][$i] = $tranche_data['loc'][$i]['libel'];
        } else
          break;
      } else
        if ($val == 6) { //Si produit de crédit
          if ($i < sizeof($tranche_data['pro_cre'])) {
            $key_name = 'id_prod';
            $key_value = $tranche_data['pro_cre'][$i]['id'];
            $operator = "=";
            $tranche_data['libel'][$i] = $tranche_data['pro_cre'][$i]['libel'];
          } else
            break;
        }
      //total par produit de credit
  $tranche = get_tranche_credit($tranche_data['rep'], $tranche_donnees['total_data'][$key_id_ag], $key_name, $key_value, $key_name2, $key_value2, $operator,$tranche_data['liste_criteres']["Date"]);
    ++$tranche_data['taille'];
  	$tranche_data['credit']['nbre'][$i] += $tranche['nbre'];
  	$tranche_data['credit']['nbre_prc'][$i] += $tranche['nbre_prc'];
  	$tranche_data['credit']['mnt'][$i] += $tranche['mnt'];
  	$tranche_data['credit']['mnt_prc'][$i] += $tranche['mnt_prc'];
  	$tranche_data['credit']['retard'][$i] += $tranche['retard'];
    $tranche_data['credit']['retard_prc'][$i] += $tranche['retard_prc'];
  	++$i;
  }//end while
 }
 }//end foreach
 
  // Ajout du nombre d'agence selectionné dans tableau contenant les statistiques de l'agence ou du réseau
  $tranche_data['a_nombreAgence'] = count($list_agence);
  if ($tranche_data['a_nombreAgence'] > 1) {
    resetGlobalIdAgence();
  }
  //return $tranche_data;
  return new ErrorObj(NO_ERR, $tranche_data);
}

/*
	Génération du XML pour le rapport Concentration du portefeuille de crédit

	Paramètres :
	  $val : 1 : critère secteur d'activité
	         2 : critère montant octroyé (b1 et b2 sont les bornes)
	         3 : critère durée (b1 et b2 sont les bornes)
	         4 : critère statut juridique
	         5 : localisation (b1 précise le niveau 1 ou 2)
	         6 : Produits de crédit
*/
function xml_repartition_credit($list_agence, $tranche_data, $devise = NULL, $export_csv = false) {
  global $adsys;
  global $global_multidevise,$global_id_agence;
	 //XML
  $document = create_xml_doc("repartition_credit", "repartition_credit.dtd");

  //Element root
  $root = $document->root();

  //En-tête généraliste
  if (($devise != NULL) && ($devise != '0') && ($global_multidevise))
    gen_header($root, 'CRD-CON', " (" . $devise . ")");
  else
    gen_header($root, 'CRD-CON');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  $header_contextuel->new_child("concentration_par", $tranche_data['liste_criteres']['Critère de répartition']);
  unset($tranche_data['liste_criteres']["id_gest"]);
  gen_criteres_recherche($header_contextuel, $tranche_data['liste_criteres']);

  $i = 0;
  while($i < $tranche_data['taille']) {
    $tranche = $root->new_child("tranche", "");
    $tranche->new_child("lib_tranche", $tranche_data['libel'][$i]);
    $tranche->new_child("nbre", $tranche_data['credit']['nbre'][$i]);
    $tranche->new_child("nbre_prc", affichePourcentage($tranche_data['credit']['nbre_prc'][$i], 2));
    $tranche->new_child("mnt", afficheMontant($tranche_data['credit']['mnt'][$i], false, $export_csv));
    $tranche->new_child("mnt_prc", affichePourcentage($tranche_data['credit']['mnt_prc'][$i], 2));
    $tranche->new_child("retard", afficheMontant($tranche_data['credit']['retard'][$i], false, $export_csv));
    $tranche->new_child("retard_prc", affichePourcentage($tranche_data['credit']['retard_prc'][$i], 2));
    ++$i;
  }
	$total = $root->new_child("total", "");
    $total->new_child("nbre", $tranche_data['total_data']['nbre']);
    $total->new_child("mnt", afficheMontant($tranche_data['total_data']['mnt'], false, $export_csv));
    $total->new_child("retard", afficheMontant($tranche_data['total_data']['retard'], false, $export_csv));

  return $document->dump_mem(true);
}


/**
 * Génère le code XML pour le rapport des clients ayant déjà eu un crédit soldé
 *
 * @param array $DATA les données concernant les clients
 * @param array $list_criteres la liste des critères de sélection
 * @return string le document XML
 */
function xml_cli_crd_soldes($DATA, $list_criteres, $export_csv = false) {

  global $adsys, $global_id_agence, $global_multidevise;

  $document = create_xml_doc("histo_credit", "his_crd_cli.dtd");

  //définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-SLD');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $list_criteres);

	//récupérer les produits de crédits
	$produits = getProdInfoByID();
  setMonnaieCourante($produits['devise']);
   reset($produits);
  $total_prod = array ();
  while (list ($key, $value) = each($produits)) {
    $produit[$key] = $root->new_child("ligne_produit", "");
    $produit[$key]->new_child("lib_prod", $value['libel'] . " (" . $value["devise"] . ")");
    $total_prod[$value['id']]['tot_mnt_octr'] = 0;
  }

  //body
  if (is_array($DATA)) {
    $tot_gen_credit = array();
    $affiche_devise=false;
    foreach ($DATA as $value) {
      if ($global_multidevise) {
      	setMonnaieCourante($value["devise"]);
        $affiche_devise=true;
      }

      if (($value["membre"] == 1) || (($value["gs_cat"] == 1) || ($value["gs_multiple"]) == "OK")) {
        $ligne_histo = $produit[$value["id_prod"]]->new_child("ligne_histo_credit_gs", "");
      	if($value["membre"] == 1 )$ligne_histo->new_child("membre_gs","true");
      }
      else $ligne_histo = $produit[$value["id_prod"]]->new_child("ligne_histo", "");

      $ligne_histo->new_child("num_client", $value["id_client"]);
      switch ($value["statut_juridique"]) {
      case 1 :
        $ligne_histo->new_child("nom_client", $value["pp_nom"] . " " . $value["pp_prenom"]);
        break;
      case 2 :
        $ligne_histo->new_child("nom_client", $value["pm_raison_sociale"]);
        break;
      case 3 :
      case 4 :
        $ligne_histo->new_child("nom_client", $value["gi_nom"]);
      }
      if (($value["gs_cat"] == 1) && ($value["membre"] == 1))
      	$ligne_histo->new_child("nom_client", mb_substr(getClientName($value["id_client"]), 0, 11, "UTF-8"));
      $ligne_histo->new_child("mnt_credit", afficheMontant($value["cre_mnt_octr"], $affiche_devise, $export_csv));
      $total_prod[$value['id_prod']]['tot_mnt_octr'] += $value['cre_mnt_octr'];
      $ligne_histo->new_child("date_reglt", pg2phpDAte($value["date_solde_credit"]));
      if (($value["gs_multiple"] == "OK") || (($value["gs_cat"] == 1) && ($value["membre"] == 1))) $pourcent = "";
      else $pourcent = 1 - ($value["nbre_echeances_en_retard"] / $value["nbre_echeances_totales"]);

      $ligne_histo->new_child("taux_retard", affichePourcentage($pourcent, 2, false));

      $ET = getTousEtatCredit();
      $ligne_histo->new_child("etat_credit", $ET[$value["etat_credit"]]["libel"]);
      $ligne_histo->new_child("cre_date_debloc", pg2phpDate($value["cre_date_debloc"] ));
     // FIXME: quel est la signification de $nb_jours=Jour sans prêt
      if (!empty ($value["date_solde_credit"]))
        $temp_date = pg2phpDate($value["date_solde_credit"]);
      else
        $temp_date = pg2phpDate($value["cre_date_debloc"]);
      $temp_date = explode("/", $temp_date);
      $temp_date = mktime(0, 0, 0, $temp_date[1], $temp_date[0], $temp_date[2]);
      $today = mktime(0, 0, 0, date("n"), date("d"), date("Y"));
	  $nb_jours = round(($today - $temp_date) / (3600 * 24), 0);
	  if (($value["gs_multiple"] == "OK") || (($value["gs_cat"] == 1) && ($value["membre"] == 1))) $nb_jours = "";
      $ligne_histo->new_child("jours_sans_pret", $nb_jours);
      $ligne_histo->new_child("prd_credit", $value["libel"]);
      if($value["membre"] != 1) $tot_gen_credit[$value["devise"]] += $value["cre_mnt_octr"];
    }
     reset($produits);
	  while (list ($key, $value) = each($produits)) {
		  $xml_prod_total = $produit[$key]->new_child("prod_total", "");
	  	$xml_prod_total->new_child("tot_mnt_octr", afficheMontant($total_prod[$value['id']]['tot_mnt_octr'], false, $export_csv));
		}

    //totaux par devise
    foreach ($tot_gen_credit as $devise=>$montant ) {
    	setMonnaieCourante($devise);
    	$total = $header_contextuel->new_child("total", "");
      $total->new_child("mnt_credit",afficheMontant($montant, $affiche_devise, $export_csv));
      $total->new_child("devise",$devise);
    }

  }

  return $document->dump_mem(true);

}

/**
 *
 * fonction qui génère le code XML pour le rapport de l'historique des demandes de crédit
 */
function xml_his_dde_crd($DATA, $list_criteres, $export_csv = false) {
  global $adsys;
  global $global_multidevise;
  $document = create_xml_doc("histo_demande_credit", "his_dde_crd.dtd");

  //définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-DEM');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $list_criteres);

	// recupérer les produis de crédit
	$produits = getProdInfoByID();
  setMonnaieCourante($produits['devise']);
   reset($produits);
  $total = array ();
  $total_devise = array();
  while (list ($key, $value) = each($produits)) {
    $produit[$key] = $root->new_child("ligneCredit", "");
    $produit[$key]->new_child("lib_prod", $value['libel'] . " (" . $value["devise"] . ")");
    $total[$value['id']]['tot_mnt_dem'] = 0;
    $total[$value['id']]['tot_mnt_octr'] = 0;
    $total_devise[$value['devise']]['tot_mnt_dem'] = 0;
    $total_devise[$value['devise']]['tot_mnt_octr'] = 0;

  }

//body
  if (is_array($DATA)) {
  foreach($DATA as $key=>$value) {debug($value,"values");

      if ($global_multidevise) {
        setMonnaieCourante($value["devise"]);
      }

      if($value["gs_cat"] == 1) { 
	     	$infosCreditSolidiaire =  $produit[$value["id_prod"]]->new_child("infosCreditSolidiaire", "");
      	$infosCreditSolidiaire->new_child("num_client", $value["id_client"]);
      	$nomClient = mb_substr(getClientName($value["id_client"]), 0, 11, "UTF-8");
				if($value["statut_juridique"] == 1){
 	        $infosCreditSolidiaire->new_child("nom_client", mb_substr($value["pp_nom"] . " " . $value["pp_prenom"], 0, 30, "UTF-8"));
 	      } else if($value["statut_juridique"] == 2){
 	        $infosCreditSolidiaire->new_child("nom_client", $value["pm_raison_sociale"]);
 	      } else if($value["statut_juridique"] == 3 || $value["statut_juridique"] == 4){
 	        $infosCreditSolidiaire->new_child("nom_client", $value["gi_nom"]);
 	      }
        $infosCreditSolidiaire->new_child("no_dossier", $value["id_doss"]);
      	$infosCreditSolidiaire->new_child("prd_credit", $value["id_prod"]);
      	$infosCreditSolidiaire->new_child("date_dde", pg2phpDate($value["date_dem"]));
      	$infosCreditSolidiaire->new_child("devise", $value["devise"]);
      	$infosCreditSolidiaire->new_child("montant_dde", afficheMontant($value["mnt_dem"], false, $export_csv));
      	$infosCreditSolidiaire->new_child("obj_dde", mb_substr($value["libel_obj"], 0, 10, "UTF-8"));
      	$infosCreditSolidiaire->new_child("eta_avance", mb_substr($value["etat_plus_avance"], 0, 20, "UTF-8"));
      	$infosCreditSolidiaire->new_child("nbr_ech_eta_avance", $value["nbr_ech_etat_plus_avance"]);
      	$infosCreditSolidiaire->new_child("duree", $value["duree_mois"]);
      	$infosCreditSolidiaire->new_child("agent_gest", mb_substr($value["nom"] . " " . $value["prenom"], 0, 10, "UTF-8"));
      	$infosCreditSolidiaire->new_child("etat", mb_substr(adb_gettext($adsys["adsys_etat_dossier_credit"][$value["etat"]]), 0, 10, "UTF-8"));
      	if ($value["etat"] > 1) {
	      	$infosCreditSolidiaire->new_child("date_decision", pg2phpDate($value["cre_date_approb"]));
	      	$infosCreditSolidiaire->new_child("montant_octr", afficheMontant($value["cre_mnt_octr"], false, $export_csv));
	      	if (!empty ($value["motif"]))
	        		$infosCreditSolidiaire->new_child("motif", mb_substr(adb_gettext($adsys["adsys_motif_etat_dossier_credit"][$value["motif"]]), 0, 10, "UTF-8"));
	    	}
    } else {
    		$detailCredit =  $produit[$value["id_prod"]]->new_child("detailCredit", "");
    		$detailCredit->new_child("num_client", $value["id_client"]);
        if($value["statut_juridique"] == 1){
 	        $nomClient = getClientNamePP($value["id_client"]);
 	        $detailCredit->new_child("nom_client", $nomClient);
 	      } else if($value["statut_juridique"] == 2){
 	        $nomClient = getClientNamePM($value["id_client"]);
 	        $detailCredit->new_child("nom_client", $nomClient);
 	      } else if($value["statut_juridique"] == 3 || $value["statut_juridique"] == 4){
 	        $nomClient = getClientNameGI($value["id_client"]);
 	        $detailCredit->new_child("nom_client", $nomClient);
 	      }
      	$detailCredit->new_child("no_dossier", $value["id_doss"]);
      	$detailCredit->new_child("prd_credit", $value["id_prod"]);
      	$detailCredit->new_child("date_dde", pg2phpDate($value["date_dem"]));
      	$detailCredit->new_child("devise", $value["devise"]);
      	$detailCredit->new_child("montant_dde", afficheMontant($value["mnt_dem"], false, $export_csv));
      	$detailCredit->new_child("obj_dde", mb_substr($value["libel_obj"], 0, 10, "UTF-8"));
      	$detailCredit->new_child("eta_avance", mb_substr($value["etat_plus_avance"], 0, 20, "UTF-8"));
      	$detailCredit->new_child("nbr_ech_eta_avance", $value["nbr_ech_etat_plus_avance"]);
      	$detailCredit->new_child("duree", $value["duree_mois"]);
      	$detailCredit->new_child("agent_gest", mb_substr($value["nom"] . " " . $value["prenom"], 0, 10, "UTF-8"));
      	$detailCredit->new_child("etat", mb_substr(adb_gettext($adsys["adsys_etat_dossier_credit"][$value["etat"]]), 0, 10, "UTF-8"));
      	$detailCredit->new_child("membre_gs", $value["membre_gs"]);
      	if ($value["etat"] > 1) {
      		$detailCredit->new_child("date_decision", pg2phpDate($value["cre_date_approb"]));
        	$detailCredit->new_child("montant_octr", afficheMontant($value["cre_mnt_octr"], false, $export_csv));
        	if (!empty ($value["motif"]))
          		$detailCredit->new_child("motif", mb_substr(adb_gettext($adsys["adsys_motif_etat_dossier_credit"][$value["motif"]]), 0, 10, "UTF-8"));
 	   		}
    }
    $total[$value['id_prod']]['tot_mnt_dem'] += $value['mnt_dem'];
    $total[$value['id_prod']]['tot_mnt_octr'] += $value['cre_mnt_octr'];
    $total_devise[$value['devise']]['tot_mnt_dem'] += $value['mnt_dem'];
    $total_devise[$value['devise']]['tot_mnt_octr'] += $value['cre_mnt_octr'];
  }
 	reset($produits);
  while (list ($key, $value) = each($produits)) {
  	//if produit has no related data remove product header		
  		if($total[$value['id']]['tot_mnt_dem']==0 && $total[$value['id']]['tot_mnt_octr']==0) {
  			$root->remove_child($produit[$key]);
  		}
  		else {
  			$xml_total = $produit[$key]->new_child("xml_total", "");
  			$xml_total->new_child("tot_mnt_dem", afficheMontant($total[$value['id']]['tot_mnt_dem'], false, $export_csv));
  			$xml_total->new_child("tot_mnt_octr", afficheMontant($total[$value['id']]['tot_mnt_octr'], false, $export_csv));
  		}
  	}
  	
	while (list ($key, $value) = each($total_devise)) {
		$infos_synthetiques[$key] = $header_contextuel->new_child("infos_synthetiques", "");
	  $infos_synthetiques[$key]->new_child("libel", mb_substr(_('Montant total demandé en devise  ').$key, 0, 50, "UTF-8"));
	  $infos_synthetiques[$key]->new_child("valeur",  afficheMontant($value['tot_mnt_dem'], false, $export_csv));
	  $infos_synthetiques[$key] = $header_contextuel->new_child("infos_synthetiques", "");
	  $infos_synthetiques[$key]->new_child("libel", mb_substr(_('Montant total octroyé en devise  ').$key, 0, 50, "UTF-8"));
	  $infos_synthetiques[$key]->new_child("valeur",  afficheMontant($value['tot_mnt_octr'], false, $export_csv));
	}
 }
 return $document->dump_mem(true);
 }

/**
 * fonction qui génère le code XML pour le rapport de l'historique des crédits octroyés
 */
function xml_his_crd_oct($DATA, $list_criteres, $export_csv = false)
{
    global $adsys;
    global $global_multidevise;
    $document = create_xml_doc("histo_credit_oct", "his_crd_oct.dtd");
    
    // définition de la racine
    $root = $document->root();
    
    // En-tête généraliste
    gen_header($root, 'CRD-OCT');
    
    // En-tête contextuel
    $header_contextuel = $root->new_child("header_contextuel", "");
    gen_criteres_recherche($header_contextuel, $list_criteres);
    
    $produits = getProdInfoByID();
     ksort($produits);
    setMonnaieCourante($produits['devise']);
    reset($produits);
    $total = array();
    $total_devise = array();
    
    while (list ($key, $value) = each($produits)) {
        $produit[$key] = $root->new_child("ligneCredit", "");
        $produit[$key]->new_child("lib_prod", $value['libel'] . " (" . $value["devise"] . ")");
        $total[$value['id']]['tot_mnt_dem'] = 0;
        $total[$value['id']]['tot_mnt_octr'] = 0;
        $total_devise[$value['devise']]['tot_mnt_dem'] = 0;
        $total_devise[$value['devise']]['tot_mnt_octr'] = 0;
    }
    /***************************************************************************************
     *Evolution groupes solidaires:T584
     * @author Kheshan A.G
     * BD-MU
     **************************************************************************************/
    //fonction  pour recuperer les info des gs dans la table ad_dcr_grp_sol
    $lesGrpSolidaire = getInfoGroupeSolidaire();
    /**
     *Eclatement des type de dossier de credits et insertion dan un array array_dcr
     *  Structure du tableaux $array_dcr :
     *        id_produit_credit
     *           ->id_grp_sol 0r 0 for unitaire
     *                ->id_dossier
     *                     ->details dossier
     * */
    $array_dcr = array();//array dossier unitaire+ solidaire+unique

  if ($DATA != NULL) {
    foreach ($DATA as $id_doss => $details) { // pour chaque dossier de credits
      foreach ($produits as $key_prod => $value_prod) { //pour chaque  produit de credits
        if ($key_prod == $details ['id_prod']) {

          if ($details["gs_cat"] == 0) {//dossier unitaire
            $array_dcr [$key_prod][0][$id_doss] = $details; //grouper par prod et les dossiers
             }else if ($details["gs_cat"]== 1){//array dossier gs unique
            $array_dcr [$key_prod][$id_doss][0] = $details; //le dossier principal+info client principal dossier unik
            //NOTE : ad_dcr_grp_sol.id_dcr_grp_sol = id_doss du dossier unique dans le cas dun gs a dossier unique
            //on recupere ces membres(id_membre) dans ad_dcr_grp_sol
            $info =recupMembreGsUnik($id_doss);
            foreach ($info as $id_membre => $details_membre) {
              $array_dcr [$key_prod][$id_doss][$id_membre] = $details_membre;
            }
          } else if ($details["gs_cat"] == 2) {//dossier gs multiple
            foreach ($lesGrpSolidaire as $id_sol => $value_sol) {
              if ($id_sol == $details ['id_dcr_grp_sol']) {
                $info=recupMembreGs($id_sol);
                $array_dcr [$key_prod][$id_sol][0] = $info[$id_sol];
                $array_dcr [$key_prod][$id_sol][$id_doss] = $details; //grouper par prod et les dossiers
              }
            }
          }
        }
      }
    }
  }
    /**
     * Xml builder _rapport historique de credits octroyés
     */
    if ($array_dcr != NULL) {
      if ($global_multidevise) {
        setMonnaieCourante($value["devise"]);
      }
      while ( list ( $id_produit, $info_grp ) = each ($array_dcr ) ) {
        //pour le produit concerné->recupere et traite les dossier unitaire
      if(sizeof($id_produit)==1 && key($info_grp)==0) {
        while ( list ( $key_doss, $details ) = each ($info_grp[0] ) ) {
                //create subroot infosCreditSolidiaire
                $infosCreditSolidiaire = $produit[$id_produit]->new_child("infosCreditSolidiaire", "");

              $infosCreditSolidiaire->new_child("no_dossier", $key_doss);
              $infosCreditSolidiaire->new_child("num_client", $details["id_client"]);
              //getnomClient
              $nomClient = getNomClient($details["id_client"]);
              $infosCreditSolidiaire->new_child("nom_client", $nomClient);
              $infosCreditSolidiaire->new_child("mnt_dem", afficheMontant($details["mnt_dem"], false, $export_csv));
              $infosCreditSolidiaire->new_child("mnt_octr", afficheMontant($details["cre_mnt_octr"], false, $export_csv));
              $infosCreditSolidiaire->new_child("devise", $details["devise"]);
              $infosCreditSolidiaire->new_child("date_oct", pg2phpDate($details["date_oct"]));
              $infosCreditSolidiaire->new_child("duree", $details["duree_mois"]);
              $infosCreditSolidiaire->new_child("type_duree", adb_gettext($adsys["adsys_type_duree_credit"][$details["type_duree"]]));
              $infosCreditSolidiaire->new_child("libel_prod", $details["libel_prod"]);
              $infosCreditSolidiaire->new_child("agent_gest", $details["nom"] . " " . $details["prenom"]);

             //calcul somme montant demandé et montant octroyé
              $total[$id_produit]['tot_mnt_dem'] +=$details["mnt_dem"];
              $total[$id_produit]['tot_mnt_octr'] += $details["cre_mnt_octr"];
              $total_devise[$details["devise"]]['tot_mnt_dem'] += $details["mnt_dem"];
              $total_devise[$details["devise"]]['tot_mnt_octr'] += $details["cre_mnt_octr"];

              }
        //sinon pour le produit concerné->recupere et traite les groupe solidaire
      }else{
        while (list ($key_group, $dossiers) = each($info_grp)) {// pour chaque id_group_solidaire

          while (list ($key_doss, $details) = each($dossiers)) {// pour chaque dossiers attaché a ce groupe
              //create subroot infosCreditSolidiaire
              $infosCreditSolidiaire = $produit[$id_produit]->new_child("infosCreditSolidiaire", "");
            //traiment pour  visualiser le membre principal du gs
            if ($key_doss ==0) {
              if (isset($dossiers[0]["num_doss"])) {//traitment du holder(gs) du  dossier multiple

              $infosCreditSolidiaire->new_child("no_dossier", $dossiers[0]["num_doss"]);
              $infosCreditSolidiaire->new_child("num_client", $dossiers[0]["id_client_grp"]);
              $infosCreditSolidiaire->new_child("nom_client", $dossiers[0]["gi_nom"]);
              $infosCreditSolidiaire->new_child("mnt_dem", afficheMontant($dossiers[0]["mnt_dem_grp"], false, $export_csv));
                //pas de calcul total: car cest sommer au niveau de ces membre multiple
            }else {//traitement du holder du dossier unique
                $infosCreditSolidiaire->new_child("no_dossier", $dossiers[0]["id_doss"]);
                $infosCreditSolidiaire->new_child("num_client", $dossiers[0]["id_client"]);
                $infosCreditSolidiaire->new_child("nom_client", $dossiers[0]["gi_nom"]);
                $infosCreditSolidiaire->new_child("mnt_dem", afficheMontant($dossiers[0]["mnt_dem"], false, $export_csv));
                //$infosCreditSolidiaire->new_child("nom_client", $nomClient);
                $infosCreditSolidiaire->new_child("mnt_dem", afficheMontant($dossiers[0]["mnt_dem"], false, $export_csv));
                $infosCreditSolidiaire->new_child("mnt_octr", afficheMontant($dossiers[0]["cre_mnt_octr"], false, $export_csv));
                $infosCreditSolidiaire->new_child("devise", $dossiers[0]["devise"]);
                $infosCreditSolidiaire->new_child("date_oct", pg2phpDate($dossiers[0]["date_oct"]));
                $infosCreditSolidiaire->new_child("duree", $dossiers[0]["duree_mois"]);
                $infosCreditSolidiaire->new_child("type_duree", adb_gettext($adsys["adsys_type_duree_credit"][$dossiers[0]["type_duree"]]));
                $infosCreditSolidiaire->new_child("libel_prod", $dossiers[0]["libel_prod"]);
                $infosCreditSolidiaire->new_child("agent_gest", $dossiers[0]["nom"] . " " . $dossiers[0]["prenom"]);

                //calcul somme montant demandé et montant octroyé au niveau du dossier unique
                $total[$id_produit]['tot_mnt_dem'] += $dossiers[0]["mnt_dem"];
                $total[$id_produit]['tot_mnt_octr'] += $dossiers[0]["cre_mnt_octr"];
                $total_devise[$details["devise"]]['tot_mnt_dem'] += $dossiers[0]["mnt_dem"];
                $total_devise[$details["devise"]]['tot_mnt_octr'] += $dossiers[0]["cre_mnt_octr"];

              }
            } else {
              if ( $details["gs_cat"]==1) {//traitement info pour les clients du dossier unique
                $infosCreditSolidiaire->new_child("no_dossier", $details["id_dcr_grp_sol"]);
                $infosCreditSolidiaire->new_child("num_client", $details["id_membre"]);
                //getnomClient
                 $nomClient = getNomClient($details["id_membre"]);
                $infosCreditSolidiaire->new_child("nom_client", $nomClient);
                $infosCreditSolidiaire->new_child("mnt_dem", afficheMontant($details["mnt_dem"], false, $export_csv));
                $infosCreditSolidiaire->new_child("mnt_octr", afficheMontant($details["cre_mnt_octr"], false, $export_csv));
                       //pas de calcul somme pour membre dossier unik: total deja considerer en haut

              } else{//traitement des dossier multiple des membres
                $infosCreditSolidiaire->new_child("no_dossier", $key_doss);
                $infosCreditSolidiaire->new_child("num_client", $details["id_client"]);
                //getnomClient
                 $nomClient = getNomClient($details["id_client"]);
                $infosCreditSolidiaire->new_child("nom_client", $nomClient);
                $infosCreditSolidiaire->new_child("mnt_dem", afficheMontant($details["mnt_dem"], false, $export_csv));
                $infosCreditSolidiaire->new_child("mnt_octr", afficheMontant($details["cre_mnt_octr"], false, $export_csv));
                $infosCreditSolidiaire->new_child("devise", $details["devise"]);
                $infosCreditSolidiaire->new_child("date_oct", pg2phpDate($details["date_oct"]));
                $infosCreditSolidiaire->new_child("duree", $details["duree_mois"]);
                $infosCreditSolidiaire->new_child("type_duree", adb_gettext($adsys["adsys_type_duree_credit"][$details["type_duree"]]));
                $infosCreditSolidiaire->new_child("libel_prod", $details["libel_prod"]);
                $infosCreditSolidiaire->new_child("agent_gest", $details["nom"] . " " . $details["prenom"]);

                //calcul somme montant demandé et montant octroyé
                $total[$id_produit]['tot_mnt_dem'] += $details["mnt_dem"];
                $total[$id_produit]['tot_mnt_octr'] += $details["cre_mnt_octr"];
                $total_devise[$details["devise"]]['tot_mnt_dem'] += $details["mnt_dem"];
                $total_devise[$details["devise"]]['tot_mnt_octr'] += $details["cre_mnt_octr"];
              }
            }
          }//end while dossiers
        }//end while id_grp_solidaire
      }//fin else

      }//end while navigateur des dossier


    }//end if _verificateur null
  reset($produits);
  while (list ($key, $value) = each($produits)) {
     //retire le produit qui nón pas de dossier a afficher dans le rapport
     if ($total[$value['id']]['tot_mnt_dem'] == 0 && $total[$value['id']]['tot_mnt_octr'] == 0) {
    $root->remove_child($produit[$value['id']]);
    } else {
      $xml_total = $produit[$key]->new_child("xml_total", "");
      $xml_total->new_child("tot_mnt_dem", afficheMontant($total[$value['id']]['tot_mnt_dem'], false, $export_csv));
      $xml_total->new_child("tot_mnt_octr", afficheMontant($total[$value['id']]['tot_mnt_octr'], false, $export_csv));
     }
  }
  // affichage des info au niveau synthetiques
  while (list ($key, $value) = each($total_devise)) {
    $infos_synthetiques[$key] = $header_contextuel->new_child("infos_synthetiques", "");
    $infos_synthetiques[$key]->new_child("libel", mb_substr(_('Montant total demandé en devise  ') . $key, 0, 50, "UTF-8"));
    $infos_synthetiques[$key]->new_child("valeur", afficheMontant($value['tot_mnt_dem'], false, $export_csv));
    $infos_synthetiques[$key] = $header_contextuel->new_child("infos_synthetiques", "");
    $infos_synthetiques[$key]->new_child("libel", mb_substr(_('Montant total octroyé en devise  ') . $key, 0, 50, "UTF-8"));
    $infos_synthetiques[$key]->new_child("valeur", afficheMontant($value['tot_mnt_octr'], false, $export_csv));
  }
    return $document->dump_mem(true);
}
 
/**
 * fonction qui génère le code XML pour le rapport des crédits rééchelonnés
 */
function xml_crd_reech($DATA, $list_criteres, $id_prod, $export_csv = false) {
  global $adsys;
  global $global_multidevise;
   global $global_monnaie;
  $document = create_xml_doc("credit_reech", "credit_reech.dtd");

  //définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-REE');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $list_criteres);

	//récupère les différents produits de crédit
  $produits = getProdInfoByID($id_prod);
  setMonnaieCourante($produits['devise']);
   reset($produits);
  $total = array ();
  $total_devise = array();
  while (list ($key, $value) = each($produits)) {
    $produit[$key] = $root->new_child("ligneCredit", "");
    $produit[$key]->new_child("lib_prod", $value['libel'] . " (" . $value["devise"] . ")");
    $total[$value['id']]['tot_mnt_octr'] = 0;
    $total[$value['id']]['tot_cap_att'] = 0;
    $total[$value['id']]['tot_cap_rest'] = 0;
    $total[$value['id']]['tot_mnt_reech'] = 0;
    $total_devise[$value['devise']]['tot_mnt_octr'] = 0;
    $total_devise[$value['devise']]['tot_cap_att'] = 0;
    $total_devise[$value['devise']]['tot_cap_rest'] = 0;
    $total_devise[$value['devise']]['tot_mnt_reech'] = 0;
  }

//body
  if (is_array($DATA)) {
  	//$globalInfos = $root->new_child("globalInfos", "");
		$mnt_tot_oct = 0;
		$mnt_tot_crd = 0;
		$encours_tot_crd = 0;
    foreach ($DATA as $value) {
      if ($global_multidevise) {
        setMonnaieCourante($value["devise"]);
      }
   		//	$ligneCredit = $produit[$value["id_prod"]]->new_child("ligneCredit", "");

    		$detailCredit = $produit[$value["id_prod"]]->new_child("detailCredit", "");

				$detailCredit->new_child("no_dossier", $value["id_doss"]);
    		$detailCredit->new_child("num_client", $value["id_client"]);
      	$nomClient = mb_substr(getClientName($value["id_client"]), 0, 30, "UTF-8");
				if($value["statut_juridique"] == 1){
 	        $infosCreditSolidiaire->new_child("nom_client", mb_substr($value["pp_nom"] . " " . $value["pp_prenom"], 0, 30, "UTF-8"));
 	      } else if($value["statut_juridique"] == 2){
 	        $infosCreditSolidiaire->new_child("nom_client", $value["pm_raison_sociale"]);
 	      } else if($value["statut_juridique"] == 3 || $value["statut_juridique"] == 4){
 	        $infosCreditSolidiaire->new_child("nom_client", $value["gi_nom"]);
 	      }
				$detailCredit->new_child("mnt_octr", afficheMontant($value["cre_mnt_octr"], false, $export_csv));
				$detailCredit->new_child("cap_att", afficheMontant($value["cap_att"], false, $export_csv));
				$detailCredit->new_child("cap_rest", afficheMontant($value["cap_rest"], false, $export_csv));
				$detailCredit->new_child("lib_prod", mb_substr($value["lib_prod"], 0, 10, "UTF-8"));
      	$detailCredit->new_child("devise", $value["devise"]);
      	$detailCredit->new_child("lib_etat", mb_substr($value["lib_etat"], 0, 10, "UTF-8"));
      	$detailCredit->new_child("cre_nbre_reech", mb_substr($value["cre_nbre_reech"], 0, 10, "UTF-8"));

				$data_reech = $value["reech"];
				$mnt_reech = 0;
      	foreach($data_reech as $key=>$value_reech){
      		$reechelonnement_mnt = $detailCredit->new_child("list_mnt_reech", "");
      		$reechelonnement_mnt->new_child("mnt_reech", afficheMontant($value_reech["montant"], false, $export_csv));
      		$mnt_reech += $value_reech["montant"];
      		$reechelonnement_date = $detailCredit->new_child("list_date_reech", "");
      		$reechelonnement_date->new_child("date_reech",pg2phpDate($value_reech["date"]));
      	}
		    $total[$value['id_prod']]['tot_mnt_octr'] += $value['cre_mnt_octr'];
		    $total[$value['id_prod']]['tot_cap_att'] += $value['cap_att'];
		    $total[$value['id_prod']]['tot_cap_rest'] += $value['cap_rest'];
		    $total[$value['id_prod']]['tot_mnt_reech'] += $mnt_reech;
		    $total_devise[$value['devise']]['tot_mnt_octr'] += $value['cre_mnt_octr'];
		    $total_devise[$value['devise']]['tot_cap_att'] += $value['cap_att'];
		    $total_devise[$value['devise']]['tot_cap_rest'] += $value['cap_rest'];
		    $total_devise[$value['devise']]['tot_mnt_reech'] += $mnt_reech;

				if ($global_multidevise) {
         $mnt_tot_oct += calculeCV($value["devise"], $global_monnaie, $value["cre_mnt_octr"]);
         $mnt_tot_crd += calculeCV($value["devise"], $global_monnaie, $value["cap_att"]);
         $encours_tot_crd += calculeCV($value["devise"], $global_monnaie, $value["cap_rest"]);
      	}
      	else{
      		$mnt_tot_oct += $value["cre_mnt_octr"];
					$mnt_tot_crd += $value["cap_att"];
					$encours_tot_crd += $value["cap_rest"];
      	}
  }
   reset($produits);
  while (list ($key, $value) = each($produits)) {
	  $xml_total = $produit[$key]->new_child("xml_total", "");
  	$xml_total->new_child("tot_mnt_octr", afficheMontant($total[$value['id']]['tot_mnt_octr'], false, $export_csv));
  	$xml_total->new_child("tot_cap_att", afficheMontant($total[$value['id']]['tot_cap_att'], false, $export_csv));
  	$xml_total->new_child("tot_cap_rest", afficheMontant($total[$value['id']]['tot_cap_rest'], false, $export_csv));
  	$xml_total->new_child("tot_mnt_reech", afficheMontant($total[$value['id']]['tot_mnt_reech'], false, $export_csv));
	}

	while (list ($key, $value) = each($total_devise)) {
		$infos_synthetiques[$key] = $header_contextuel->new_child("infos_synthetiques", "");
	  $infos_synthetiques[$key]->new_child("libel", mb_substr(_('Montant total octroyé en devise  ').$key, 0, 50, "UTF-8"));
	  $infos_synthetiques[$key]->new_child("valeur",  afficheMontant($value['tot_mnt_octr'], false, $export_csv));
	  $infos_synthetiques[$key] = $header_contextuel->new_child("infos_synthetiques", "");
	  $infos_synthetiques[$key]->new_child("libel", mb_substr(_('Montant total du capital attendu en devise  ').$key, 0, 50, "UTF-8"));
	  $infos_synthetiques[$key]->new_child("valeur",  afficheMontant($value['tot_cap_att'], false, $export_csv));
	   $infos_synthetiques[$key] = $header_contextuel->new_child("infos_synthetiques", "");
	  $infos_synthetiques[$key]->new_child("libel", mb_substr(_('Montant total du capital restant en devise  ').$key, 0, 50, "UTF-8"));
	  $infos_synthetiques[$key]->new_child("valeur",  afficheMontant($value['tot_cap_rest'], false, $export_csv));
	}

  $globalInfos = $header_contextuel->new_child("globalInfos", "");
  $globalInfos->new_child("mnt_tot_oct", afficheMontant($mnt_tot_oct, false, $export_csv));
  $globalInfos->new_child("mnt_tot_crd_reech", afficheMontant($mnt_tot_crd, false, $export_csv));
  $globalInfos->new_child("encours_crd_reech", afficheMontant($encours_tot_crd, false, $export_csv));
 }

 return $document->dump_mem(true);
 }

 function xml_balanceportefeuille($gestionnaire = 0, $export_date = NULL, $type_affich = 1, $date_debloc_inf, $date_debloc_sup, $prd, $export_csv = false) {
  global $global_multidevise;
  global $global_monnaie;

  // Création racine
  global $global_id_agence;
  $document = create_xml_doc("balanceportefeuillerisque", "balance_age_portefeuille.dtd");
  
  $DATAS = balanceportefeuillerisque($gestionnaire, $export_date, $type_affich, $date_debloc_inf, $date_debloc_sup,$prd);
  
  $id_etat_perte = getIDEtatPerte();
  /* Récupération des états de crédit  */
  $etat_credit = array ();
  $etat_credit = getTousEtatCredit();
  $produit_credits = get_produits_credit_balance_agee( $global_monnaie);
  
  /*
   * Filtrage de DATA par etat_credit->produits_credits->dossier_credits
   * 
   */
  $array_categorique = array();
  $total_prod = array();
	if ($DATAS ["pretsretard"] != NULL) {
		while ( list ( $cle, $details ) = each ( $DATAS ["pretsretard"] ) ) {
			foreach ( $etat_credit as $key_etat => $value_etat ) {
				if ($value_etat ["id"] >= 2 and $value_etat ["id"] != $id_etat_perte) {
					if ($value_etat ["id"] == $details ['cre_etat']) {
						
						foreach ( $produit_credits as $key_prod => $value_prod ) {
							if ($value_prod ["id"] == $details ['id_prod']) {
								$array_categorique [$key_etat] [$key_prod] [$cle] = $details;
								$total_prod [$key_etat] [$key_prod] ["montant_pret"] += $details['montantpret'];
								$total_prod [$key_etat] [$key_prod] ["solde"] += $details['solde'];
								$total_prod [$key_etat] [$key_prod] ["principalretard"] += $details['principal'];
								$total_prod [$key_etat] [$key_prod] ["interetretard"] += $details['interets'];
								$total_prod [$key_etat] [$key_prod] ["garantieretard"] += $details['garantie'];
								$total_prod [$key_etat] [$key_prod] ["penaliteretard"] += $details['penalite'];
								$total_prod [$key_etat] [$key_prod] ["prov_mnt"] += $details['prov_mnt'];
								
							}
						 	
						}
					}
				}
			}
		}
	}
	
  if ($DATAS == NULL)
  return NULL;

  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-BAL');
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  if (($gestionnaire == 0) || ($gestionnaire == "") || ($gestionnaire == null)) {
  	$criteres = array (
                      _("Gestionnaire") => _("Tous"),
                      _("Date") => date($export_date),//ticket 489
  			          _("Date début du déboursement") => date($date_debloc_inf),
  			          _("Date fin de déboursement") => date($date_debloc_sup)
                      );
  } else {
  	$criteres = array (
                      _("Gestionnaire") => getLibel("ad_uti", $_POST["gest"]),
                      _("Date") => date($export_date),//ticket 489
  			          _("Date début du déboursement") => date($date_debloc_inf),
  			          _("Date fin de déboursement") => date($date_debloc_sup)
  	);
  }
		
		// filtre par produit credits
	 if (($prd == 0) || ($prd == "") || ($prd == null)) {
		$criteres = array_merge ( $criteres, array (
				_ ( "Type de produits de credits" ) => _ ( "Tous" ) 
		) );
	} else {
		$criteres = array_merge ( $criteres, array (
				_ ( "Type de produits de credits" ) => getLibelPrdt ( $prd, "adsys_produit_credit" ) 
		) );
	} 
  
  gen_criteres_recherche($header_contextuel, $criteres);

  /* Récupération des états de crédit  */
  $etat_credit = array ();
  $etat_credit = getTousEtatCredit();
  $produit_credits = get_produits_credit( $global_monnaie);
  // Récupération de l'état en perte
  $id_etat_perte = getIDEtatPerte();

  if ($global_multidevise) {
  	setMonnaieCourante($global_monnaie);
  }

  $totalprcentage = $root->new_child("totalprcentage", "");
  $totalprcentage->new_child("totalenretard", afficheMontant($DATAS["totaux"]['totalretard'], true));
  $totalprcentage->new_child("totalsain", afficheMontant($DATAS["totaux"]['portefeuillsain'], true));
  $totalprcentage->new_child("portefeuilltotal", afficheMontant($DATAS["totaux"]['portefeuilletotal'], true));
  $totalprcentage->new_child("totalprincipal", afficheMontant($DATAS["totaux"]['totalprincipalretard'], true));
  $totalprcentage->new_child("pourcentagerisque", affichePourcentage($DATAS["pourcentage"]['pourcentagerisque'], 1, true));

  /* Récapitulatif */
  $recapilatif = $root->new_child("recapilatif", "");
  $recapilatif->new_child("entete_recap", _("Récapitulatif"));

  foreach ($etat_credit as $key => $value)
  if ($value["id"] >= 2 and $value["id"] != $id_etat_perte) { // Tous les états sauf 'Sain' et 'Perte'
  	$detail_recap = $recapilatif->new_child("detail_recap", "");
  	$detail_recap->new_child("lib_etat", $value["libel"]);
  	$detail_recap->new_child("nombre_tot", $DATAS[$global_id_agence]["nb"][$value["id"]]);
  	$detail_recap->new_child("montant_tot", afficheMontant($DATAS[$global_id_agence]["mnt"][$value["id"]], true));
  	$detail_recap->new_child("prcentagerisque", affichePourcentage($DATAS["pourcentage"]["prcentagerisque"][$value["id"]], 1, true));
  }
  
  if($type_affich == 1){// affichage détaillé
  foreach ($array_categorique as $key_etat =>$value_etat){//pour chaque etat credit
  	$detailsretard[$key_etat] = $root->new_child("detailsretard", "");
  	$detailsretard[$key_etat]->new_child("lib_detail",getLibel("adsys_etat_credits", $key_etat));
  foreach($value_etat as $keyprod =>$value_prod){// pour chaque produit credits
   	$produits[$keyprod] = $detailsretard[$key_etat]->new_child("produits", "");
    $produits[$keyprod]->new_child("lib_prod", getLibel("adsys_produit_credit", $keyprod));
    
    //info total produit credits
    $produits[$keyprod]->new_child("montant_pret_prod",  afficheMontant($total_prod [$key_etat] [$keyprod] ["montant_pret"], false, $export_csv));
    $produits[$keyprod]->new_child("solde_prod",  afficheMontant($total_prod [$key_etat] [$keyprod] ["solde"] , false, $export_csv));
    $produits[$keyprod]->new_child("principalretard_prod",   afficheMontant($total_prod [$key_etat] [$keyprod] ["principalretard"], false, $export_csv));
    $produits[$keyprod]->new_child("interetretard_prod", afficheMontant( $total_prod [$key_etat] [$keyprod] ["interetretard"], false, $export_csv));
    $produits[$keyprod]->new_child("garantieretard_prod",  afficheMontant($total_prod [$key_etat] [$keyprod] ["garantieretard"], false, $export_csv));
    $produits[$keyprod]->new_child("penaliteretard_prod",  afficheMontant($total_prod [$key_etat] [$keyprod] ["penaliteretard"], false, $export_csv));
    $produits[$keyprod]->new_child("prov_mnt_prod",  afficheMontant($total_prod [$key_etat] [$keyprod] ["prov_mnt"], false, $export_csv));


   	 foreach( $value_prod as $keydoss => $value_doss ){// pour chaque dossier qui exists
  	 	if ($global_multidevise)
  	 		setMonnaieCourante( $value_doss['devise'] );
  	 		$dossiersretard =$produits[$keyprod]->new_child("dossiersretard", "");
  	 		if ((($value_doss["gs_cat"] == 1) && $value_doss["membre"] == 0) || ($value_doss["gs_multiple"] == "OK")) {
  	 			$dossiersretard->new_child("groupe_gs", "groupe");
  	 		}
  	 		elseif ($value_doss["membre"] == 1)
  	 		$dossiersretard->new_child("membre_gs", "membre");
  	 		$dossiersretard->new_child("numpret", $value_doss['id_doss']);
  	 		$dossiersretard->new_child("montantpret", afficheMontant($value_doss['montantpret'], false, $export_csv));
  	 		$dossiersretard->new_child("solde", afficheMontant($value_doss['solde'], false, $export_csv));
  	 		$dossiersretard->new_child("principalretard", afficheMontant($value_doss['principal'], false, $export_csv));
  	 		$dossiersretard->new_child("interetretard", afficheMontant($value_doss['interets'], false, $export_csv));
  	 		$dossiersretard->new_child("garantieretard", afficheMontant($value_doss['garantie'], false, $export_csv));
  	 		$dossiersretard->new_child("penaliteretard", afficheMontant($value_doss['penalite'], false, $export_csv));
  	 		$dossiersretard->new_child("gest", $value_doss['gest']);
  	 		$dossiersretard->new_child("idclient", $value_doss['idclient']);
  	 		$dossiersretard->new_child("nomclient", $value_doss['nom']);
  	 		$dossiersretard->new_child("devise", $value_doss['devise']);
  	 		$dossiersretard->new_child("impayesprcentage", affichePourcentage($value_doss['impayes'], 1, false));
  	 		$dossiersretard->new_child("prov_mnt", afficheMontant($value_doss['prov_mnt'], false, $export_csv));
  	 	}
  	 }
    }
  }

  return $document->dump_mem(true);

 }

function xml_liste_clients_deb($DATA, $nombre, $list_criteres, $export_csv = false) {
  // Génération du XML pour le rapport Liste des plus gros débiteurs de l'institution

  global $adsys;
  global $global_monnaie, $global_monnaie_courante;
  global $global_multidevise,$global_id_agence;

  $document = create_xml_doc("liste_clients_deb", "liste_clients_deb.dtd");

  //définition de la racine
  $root = $document->root();
  //En-tête généraliste
  gen_header($root, 'CRD-MAX', " : $nombre "._("dossiers"));
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $list_criteres);

  $total = $root->new_child("total", "");
  $TOTAL = $DATA["TOTAL"];
  $TOTAL_SAIN = $DATA["TOTAL_SAIN"];
  if ($global_multidevise)
    setMonnaieCourante($global_monnaie);
  $total->new_child("encours_total", afficheMontant($TOTAL["portefeuille"], true));
  $total->new_child("encours_clients_deb", afficheMontant($TOTAL["portefeuille_cli"], true));
  if ($TOTAL["portefeuille"] > 0)
    $total->new_child("ratio", affichePourcentage($TOTAL["portefeuille_cli"] / $TOTAL["portefeuille"], true));
  else
    $total->new_child("ratio", _("Non disponible"));
  $total->new_child("encours_retard_deb", afficheMontant($TOTAL["portefeuille_retard_cli"], true));
  if ($TOTAL["portefeuille_retard_cli"] > 0)
    $total->new_child("ratio_retard", affichePourcentage($TOTAL["portefeuille_retard_cli"] / $TOTAL["portefeuille_cli"], true));
  else
    $total->new_child("ratio_retard", _("Non disponible"));
  $total->new_child("total_sain", afficheMontant($TOTAL_SAIN["total_sain"], true));

  $etat_courant= 0;
  $detail = $root->new_child("details", "");
  $index = 1;
  while (list (, $INFOS) = each($DATA["DETAILS"])) {
    setMonnaieCourante($INFOS["devise"]);
    $ET = getTousEtatCredit();
    if($etat_courant != $INFOS["cre_etat"]){//Trie par état de crédit
    	$etat_courant = $INFOS["cre_etat"];
    	$etat = $detail->new_child("libel_etat", $etat_courant);
      $etat_credit = $etat->new_child("etat", _("Etat crédit").": " .getLibel("adsys_etat_credits", $etat_courant));
    }
    // Détail par client
    $client = $etat->new_child("client", "");
    if((($INFOS["gs_cat"] == 1) && ($INFOS["membre"] == 0)) || ($INFOS["gs_multiple"] == "OK")){
    	$client->new_child("groupe_gs", "groupe");}
    elseif($INFOS["membre"] == 1)
    	$client->new_child("membre_gs", "membre");
    $client->new_child("index", $index);
    $client->new_child("id_client", makeNumClient($INFOS["id_client"]));
    $client->new_child("id_doss", $INFOS["id_doss"]);
    $client->new_child("nom", getClientName($INFOS["id_client"]));
    $client->new_child("encours_client", afficheMontant($INFOS["solde_cap"], $global_monnaie_courante, $export_csv));
    setMonnaieCourante($global_monnaie);
    $client->new_child("cv_encours_client", afficheMontant($INFOS["contre_valeur"], $global_monnaie_courante, $export_csv));
    setMonnaieCourante($INFOS["devise"]);
    $client->new_child("cre_etat", $ET[$INFOS["cre_etat"]]["libel"]);
    $client->new_child("mnt_pen", afficheMontant($INFOS["mnt_pen"], $global_monnaie_courante, $export_csv));
    $index++;
  }

 return $document->dump_mem(true);
}

function xml_liste_clients_deb_crediteur($a_DATA, $a_list_criteres, $a_export_csv = false,$devise) {
  // Génération du XML pour le rapport Liste des plus gros débiteurs de l'institution

  global $adsys;
  global $global_monnaie, $global_monnaie_courante;
  global $global_multidevise,$global_id_agence;

  $document = create_xml_doc("liste_clients_deb_cred", "liste_client_debiteur_credit.dtd");

  //définition de la racine
  $root = $document->root();
  $nombre=sizeof($a_DATA["DETAILS"]);
  //En-tête généraliste
  gen_header($root, 'CRD-LCD', " : $nombre "._("dossiers"));
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $a_list_criteres);

  $total = $root->new_child("total", "");
  $TOTAL = $a_DATA["TOTAL"];
  if ($global_multidevise)
    setMonnaieCourante($devise);
  $total->new_child("encours_total", afficheMontant($TOTAL["portefeuille"], true));
  $total->new_child("encours_clients_deb", afficheMontant($TOTAL["total_credit"], true));
    // Détail par client
  $details = $root->new_child("details", "");
  $index = 1;

  foreach( $a_DATA["DETAILS"] as $keycredit =>$INFOS) {
 	  debug($INFOS);
    $client = $details->new_child("client", "");
    if ((($INFOS["gs_cat"] == 1) && $INFOS["membre"] == 0) || ($INFOS["gs_multiple"] == "OK") ||count($INFOS["groupe"]) >0 )
      	 $client->new_child("groupe_gs", "groupe");
    elseif ($INFOS["membre"] == 1) $client->new_child("membre_gs", "membre");
    $client->new_child("index", $index);
    $client->new_child("id_client", makeNumClient($INFOS["id_client"]));
    $client->new_child("id_doss", $INFOS["id_doss"]);
    $client->new_child("nom", $INFOS["nom_utilisateur"]);
 	  $client->new_child("encours_client", afficheMontant($INFOS["solde"], $global_monnaie_courante, $a_export_csv));    $client->new_child("num_cpte", $INFOS["num_cpte"]);
    $client->new_child("cv_encours_client", afficheMontant($INFOS["contre_valeur"], $global_monnaie_courante, $a_export_csv));
    $ET = getTousEtatCredit();
    $client->new_child("mnt_pen", afficheMontant($INFOS["mnt_pen"], $global_monnaie_courante, $a_export_csv));
    foreach ($INFOS["groupe"]  as $key_m =>$valeur_id_client) {
 	      $client = $details->new_child("client", "");
 	      $client->new_child("membre_gs", "membre");
 	      $client->new_child("id_client", makeNumClient($valeur_id_client));
 	      $client->new_child("nom", getClientName($valeur_id_client) );
 	  }
    $index++;
  }
  return $document->dump_mem(true);
}


function xml_liste_plus_grds_emp($a_DATA, $a_list_criteres, $a_export_csv = false) {
	  // Génération du XML pour le rapport Liste des plus grands emprunteurs de l'institution

  global $adsys;
  global $global_monnaie, $global_monnaie_courante;
  global $global_multidevise,$global_id_agence;

  $document = create_xml_doc("liste_plus_grds_emp", "liste_plus_grds_emp.dtd");

  //définition de la racine
  $root = $document->root();

	$tot_mnt_pret = $a_DATA["tot_mnt_pret"];
  unset($a_DATA["tot_mnt_pret"]);
  $tot_solde = $a_DATA["tot_solde"];
  unset($a_DATA["tot_solde"]);
  $tot_mnt_retard = $a_DATA["tot_mnt_retard"];
  unset($a_DATA["tot_mnt_retard"]);
  $tot_mnt_prov = $a_DATA["tot_mnt_prov"];
  unset($a_DATA["tot_mnt_prov"]);

  $nombre=sizeof($a_DATA);
  //En-tête généraliste
  gen_header($root, 'CRD-PGE');
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_research_criteria($header_contextuel, $a_list_criteres);

  $globals = $root->new_child("globals", "");

  if ($global_multidevise)
    setMonnaieCourante($global_monnaie);
  $globals->new_child("devise", $global_monnaie);
  // Détail par client
  $details = $root->new_child("details", "");
  $index = 1;

  while (list (, $INFOS) = each($a_DATA)) {

    $client = $details->new_child("client", "");
    $client->new_child("index", $index);
    $client->new_child("nom", $INFOS["nom"]);
    $client->new_child("date_pret", pg2phpDate($INFOS["date_pret"]));
    $client->new_child("mnt_pret", afficheMontant($INFOS["mnt_pret"], false, $a_export_csv));
    $client->new_child("echeances", $INFOS["echeance"]);
    $client->new_child("solde", afficheMontant($INFOS["solde"], false, $a_export_csv));
    $client->new_child("mnt_retard", afficheMontant($INFOS["mnt_retard"], false, $a_export_csv));
    $client->new_child("garanties", $INFOS["garantie"]);
    $client->new_child("mnt_prov", afficheMontant($INFOS["mnt_prov"], false, $a_export_csv));
    $index++;
  }
  $total = $details->new_child("total", "");
  $total->new_child("tot_mnt_pret", afficheMontant($tot_mnt_pret, false));
  $total->new_child("tot_solde", afficheMontant($tot_solde, false));
  $total->new_child("tot_mnt_retard", afficheMontant($tot_mnt_retard, false));
  $total->new_child("tot_mnt_prov", afficheMontant($tot_mnt_prov, false));
  return $document->dump_mem(true);

}

function xml_risque_credit_secteur($a_DATA, $a_list_criteres, $a_export_csv = false) {
	  // Génération du XML pour le rapport Liste des plus grands emprunteurs de l'institution

  global $adsys;
  global $global_monnaie, $global_monnaie_courante;
  global $global_multidevise,$global_id_agence;

  $document = create_xml_doc("risque_credit_activite", "risque_par_activite.dtd");

  //définition de la racine
  $root = $document->root();

	$tot_mnt_cred = $a_DATA["tot_mnt_cred"];
  unset($a_DATA["tot_mnt_cred"]);
  $tot_ind_deb = $a_DATA["tot_ind_deb"];
  unset($a_DATA["tot_ind_deb"]);
  $tot_grp_deb = $a_DATA["tot_grp_deb"];
  unset($a_DATA["tot_grp_deb"]);
  $tot_grp_benef_pret = $a_DATA["tot_grp_benef_pret"];
  unset($a_DATA["tot_grp_benef_pret"]);

  //En-tête généraliste
  gen_header($root, 'CRD-CRA');
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_research_criteria($header_contextuel, $a_list_criteres);

  $globals = $root->new_child("globals", "");
	$globals->new_child("devise", $global_monnaie);

  if ($global_multidevise)
    setMonnaieCourante($global_monnaie);

  // Détail par client
  $details = $root->new_child("details", "");
  $index = 1;

  while (list (, $INFOS) = each($a_DATA)) {

    $activ = $details->new_child("activ", "");
    $activ->new_child("index", "M.RSP.".$index);
    $activ->new_child("libel_act", $INFOS["libel_act"]);
    $activ->new_child("mnt_cred", afficheMontant($INFOS["mnt_cred"], false));
    $activ->new_child("nbr_ind_deb", $INFOS["ind_deb"]);
    $activ->new_child("nbr_grp_deb", $INFOS["grp_deb"]);
    $activ->new_child("nbr_grp_benef_pret", $INFOS["grp_benef_pret"]);
    $nbr_benef_act = $INFOS["ind_deb"] + $INFOS["grp_benef_pret"];
    $activ->new_child("nbr_benef_act", $nbr_benef_act);
    $index++;
  }
  $total = $details->new_child("total", "");
  $total->new_child("tot_mnt_cred", afficheMontant($tot_mnt_cred, false));
  $total->new_child("tot_ind_deb", $tot_ind_deb);
  $total->new_child("tot_grp_deb", $tot_grp_deb);
  $total->new_child("tot_grp_benef_pret", $tot_grp_benef_pret);
  $tot__benef_pret = $tot_ind_deb + $tot_grp_benef_pret;
  $total->new_child("tot__benef_pret", $tot__benef_pret);
  return $document->dump_mem(true);

}
function xml_liste_credits_emp_dir($DATA, $list_criteres, $export_csv = false) {
  // Génération du XML pour le rapport Liste des crédits accordés aux employés et dirigeants

  global $adsys;
  global $global_multidevise;
  global $global_monnaie,$global_id_agence;

  $document = create_xml_doc("liste_credits_emp_dir", "liste_credits_emp_dir.dtd");

  //définition de la racine
  $root = $document->root();
  //En-tête généraliste
  gen_header($root, 'CRD-EMP');
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $list_criteres);
  $total = $root->new_child("total", "");
  $TOTAL = $DATA["TOTAL"];

  if ($global_multidevise)
    setMonnaieCourante($global_monnaie);
  $portefeuille_emp_dir = $TOTAL["portefeuille_emp"] + $TOTAL["portefeuille_dir"];
  $total->new_child("encours_total", afficheMontant($TOTAL["portefeuille"], true, $export_csv));
  $total->new_child("encours_dir", afficheMontant($TOTAL["portefeuille_dir"], true, $export_csv));
  $total->new_child("encours_emp", afficheMontant($TOTAL["portefeuille_emp"], true, $export_csv));
  $total->new_child("encours_emp_dir", afficheMontant($portefeuille_emp_dir, true, $export_csv));

  if ($TOTAL["portefeuille"] > 0) {
    $total->new_child("ratio_dir", affichePourcentage($TOTAL["portefeuille_dir"] / $TOTAL["portefeuille"], true));
    $total->new_child("ratio_emp", affichePourcentage($TOTAL["portefeuille_emp"] / $TOTAL["portefeuille"], true));
    $total->new_child("ratio_emp_dir", affichePourcentage($portefeuille_emp_dir / $TOTAL["portefeuille"], true));
  } else {
    $total->new_child("ratio_dir", _("Non disponible"));
    $total->new_child("ratio_emp", _("Non disponible"));
    $total->new_child("ratio_emp_dir", _("Non disponible"));
  }

  $portefeuille_retard_emp_dir = $TOTAL["portefeuille_retard_emp"] + $TOTAL["portefeuille_retard_dir"];

  $total->new_child("encours_retard_dir", afficheMontant($TOTAL["portefeuille_retard_dir"], true, $export_csv));
  $total->new_child("encours_retard_emp", afficheMontant($TOTAL["portefeuille_retard_emp"], true, $export_csv));
  $total->new_child("encours_retard_emp_dir", afficheMontant($portefeuille_retard_emp_dir, true, $export_csv));

  if ($TOTAL["portefeuille_dir"] > 0)
    $total->new_child("ratio_retard_dir", affichePourcentage($TOTAL["portefeuille_retard_dir"] / $TOTAL["portefeuille_dir"], true));
  else
    $total->new_child("ratio_retard_dir", _("Non disponible"));
  if ($TOTAL["portefeuille_emp"] > 0)
    $total->new_child("ratio_retard_emp", affichePourcentage($TOTAL["portefeuille_retard_emp"] / $TOTAL["portefeuille_emp"], true));
  else
    $total->new_child("ratio_retard_emp", _("Non disponible"));
  if ($portefeuille_emp_dir > 0)
    $total->new_child("ratio_retard_emp_dir", affichePourcentage($portefeuille_retard_emp_dir / $portefeuille_emp_dir, true));
  else
    $total->new_child("ratio_retard_emp_dir", _("Non disponible"));

  // Encours d'épargne

  $epargne = $root->new_child("epargne", "");
  $encours_epargne = $TOTAL["epargne"];
  if ($encours_epargne > 0) {
    $epargne->new_child("ratio_epar_dir", affichePourcentage($TOTAL["portefeuille_dir"] / $encours_epargne, true));
    $epargne->new_child("ratio_epar_emp", affichePourcentage($TOTAL["portefeuille_emp"] / $encours_epargne, true));
    $epargne->new_child("ratio_epar_emp_dir", affichePourcentage($portefeuille_emp_dir / $encours_epargne, true));
  } else {
    $epargne->new_child("ratio_epar_dir", _("Non disponible"));
    $epargne->new_child("ratio_epar_emp", _("Non disponible"));
    $epargne->new_child("ratio_epar_emp_dir", _("Non disponible"));
  }

  // Détail par client employé
  $details_emp = $root->new_child("details_emp", "");
  $index = 1;
  while (list (, $INFOS) = each($DATA["DETAILS_EMP"])) {
    setMonnaieCourante($INFOS["devise"]);
    $client = $details_emp->new_child("client", "");
    if((($INFOS["gs_cat"] == 1) && $INFOS["membre"] == 0) || ($INFOS["gs_multiple"] == "OK"))
      	 $client->new_child("groupe_gs", "groupe");
    elseif($INFOS["membre"] == 1) $client->new_child("membre_gs", "membre");
    $client->new_child("index", $index);
    $client->new_child("id_client", makeNumClient($INFOS["id_client"]));
    $client->new_child("id_doss", $INFOS["id_doss"]);
    $client->new_child("nom", getClientName($INFOS["id_client"]));
    $client->new_child("encours_client", afficheMontant($INFOS["solde_cap"], true, $export_csv));
    setMonnaieCourante($global_monnaie);
    $client->new_child("cv_encours_client", afficheMontant($INFOS["cv_solde_cap"], true, $export_csv));
    setMonnaieCourante($INFOS["devise"]);
    $ET = getTousEtatCredit();
    $client->new_child("cre_etat", $ET[$INFOS["cre_etat"]]["libel"]);
    $client->new_child("mnt_pen", afficheMontant($INFOS["mnt_pen"], true, $export_csv));
    $index++;
  }

  // Détail par client dirigeant
  $details_dir = $root->new_child("details_dir", "");
  $index = 1;
  while (list (, $INFOS) = each($DATA["DETAILS_DIR"])) {

    setMonnaieCourante($INFOS["devise"]);
    $client = $details_dir->new_child("client", "");
    if ((($INFOS["gs_cat"] == 1) && $INFOS["membre"] == 0) || ($INFOS["gs_multiple"] == "OK"))
      	 $client->new_child("groupe_gs", "groupe");
    elseif ($INFOS["membre"] == 1) $client->new_child("membre_gs", "membre");
    $client->new_child("index", $index);
    $client->new_child("id_client", makeNumClient($INFOS["id_client"]));
    $client->new_child("id_doss", $INFOS["id_doss"]);
    $client->new_child("nom", getClientName($INFOS["id_client"]));
    $client->new_child("encours_client", afficheMontant($INFOS["solde_cap"], true, $export_csv));
    setMonnaieCourante($global_monnaie);
    $client->new_child("cv_encours_client", afficheMontant($INFOS["cv_solde_cap"], true, $export_csv));
    setMonnaieCourante($INFOS["devise"]);
    $ET = getTousEtatCredit();
    $client->new_child("cre_etat", $ET[$INFOS["cre_etat"]]["libel"]);
    $client->new_child("mnt_pen", afficheMontant($INFOS["mnt_pen"], true, $export_csv));
    $index++;
  }

  return $document->dump_mem(true);
}



function xml_liste_credits_dir($DATA_DIR, $list_criteres, $export_csv = false) {
  // Génération du XML pour le rapport Liste des crédits accordés aux employés et dirigeants

  global $adsys;
  global $global_multidevise;
  global $global_monnaie,$global_id_agence;

  $document = create_xml_doc("liste_credits_dirs", "liste_credits_dirs.dtd");

  //définition de la racine
  $root = $document->root();
  //En-tête généraliste
  gen_header($root, 'CRD-DIR');
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_research_criteria($header_contextuel, $list_criteres);

  //totaux
  $total_mnt_octr = 0;
  $total_solde_cap = 0;
  $total_retard = 0;
  // Détail par client dirigeant
  $details_dir = $root->new_child("details_dir", "");
  $index = 1;
  while (list (, $INFOS) = each($DATA_DIR)) {
    setMonnaieCourante($INFOS["devise"]);
    $client = $details_dir->new_child("client", "");
    if ((($INFOS["gs_cat"] == 1) && $INFOS["membre"] == 0) || ($INFOS["gs_multiple"] == "OK"))
      	 $client->new_child("groupe_gs", "groupe");
    elseif ($INFOS["membre"] == 1) $client->new_child("membre_gs", "membre");
    $client->new_child("index", $index);
    $client->new_child("nom", getClientName($INFOS["id_client"]));
    $client->new_child("date_dem", pg2phpDate($INFOS["date_dem"]));
    $client->new_child("cre_mnt_octr", afficheMontant($INFOS["cre_mnt_oct"], false, $export_csv));
    $client->new_child("nbre_ech", afficheMontant($INFOS["nbre_ech"], false, $export_csv));
    $client->new_child("solde_cap", afficheMontant($INFOS["solde_cap"], false, $export_csv));
    $INFOS["cre_retard_etat_max_jour"] = getMontantRetardDossier($INFOS["id_doss"], $list_criteres[_("Date d'édition")]);
    $client->new_child("cre_retard_etat_max_jour", afficheMontant($INFOS["cre_retard_etat_max_jour"], false, $export_csv));
    $client->new_child("gar_tot", afficheMontant($INFOS["gar_tot"], false, $export_csv));
    $client->new_child("provision", "Non disponible");
    $total_mnt_octr += $INFOS["cre_mnt_oct"];
    $total_solde_cap += $INFOS["solde_cap"];
    $total_retard +=  $INFOS["cre_retard_etat_max_jour"];
    $index++;
  }
  $total = $details_dir->new_child("total", "");
  $total->new_child("total_mnt_octr", $total_mnt_octr);
  $total->new_child("total_solde_cap", $total_solde_cap);
  $total->new_child("total_retard", $total_retard);

  return $document->dump_mem(true);
}

function xml_liste_credits_emp($DATA_EMP, $list_criteres, $export_csv = false) {
  // Génération du XML pour le rapport Liste des crédits accordés aux employés et EMPigeants

  global $adsys;
  global $global_multidevise;
  global $global_monnaie,$global_id_agence;

  $document = create_xml_doc("liste_credits_emps", "liste_credits_dirs.dtd");

  //définition de la racine
  $root = $document->root();
  //En-tête généraliste
  gen_header($root, 'CRD-EMY');
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_research_criteria($header_contextuel, $list_criteres);

  //totaux
  $total_mnt_octr = 0;
  $total_solde_cap = 0;
  // Détail par client employé
  $details_dir = $root->new_child("details_dir", "");
  $index = 1;
  while (list (, $INFOS) = each($DATA_EMP)) {
    setMonnaieCourante($INFOS["devise"]);
    $client = $details_dir->new_child("client", "");
    if ((($INFOS["gs_cat"] == 1) && $INFOS["membre"] == 0) || ($INFOS["gs_multiple"] == "OK"))
      	 $client->new_child("groupe_gs", "groupe");
    elseif ($INFOS["membre"] == 1) $client->new_child("membre_gs", "membre");
    $client->new_child("index", $index);
    $client->new_child("nom", getClientName($INFOS["id_client"]));
    $client->new_child("date_dem", pg2phpDate($INFOS["date_dem"]));
    $client->new_child("cre_mnt_octr", afficheMontant($INFOS["cre_mnt_oct"], false, $export_csv));
    $client->new_child("nbre_ech", afficheMontant($INFOS["nbre_ech"], false, $export_csv));
    $client->new_child("solde_cap", afficheMontant($INFOS["solde_cap"], false, $export_csv));
    $INFOS["cre_retard_etat_max_jour"] = getMontantRetardDossier($INFOS["id_doss"], $list_criteres[_('Date d\'édition[[Date of edition]]')]);
    $client->new_child("cre_retard_etat_max_jour", $INFOS["cre_retard_etat_max_jour"]);
    $client->new_child("gar_tot", afficheMontant($INFOS["gar_tot"], false, $export_csv));
    $client->new_child("provision", _("Non disponible"));
    $total_mnt_octr += $INFOS["cre_mnt_oct"];
    $total_solde_cap += $INFOS["solde_cap"];
    $total_retard +=  $INFOS["cre_retard_etat_max_jour"];
    $index++;
  }
  $total = $details_dir->new_child("total", "");
  $total->new_child("total_mnt_octr", $total_mnt_octr);
  $total->new_child("total_solde_cap", $total_solde_cap);
  $total->new_child("total_retard", $total_retard);

  return $document->dump_mem(true);
}

function xml_recouvrement_creance_BNR($DATA, $list_criteres, $export_csv = false) {
	global $global_monnaie;
	global $global_multidevise;
	//Produits d'epargne financiers'
	$produits= getProdInfo();
	$document = create_xml_doc("recouvrement_creance_bnr", "recouvrement_creance.dtd");

	//définition de la racine
	$root = $document->root();

	//En-tête généraliste
	gen_header($root, 'CRD-RCR');

	//En-tête contextuel
	$header_contextuel = $root->new_child("header_contextuel", "");
	gen_research_criteria($header_contextuel, $list_criteres);
	$globals = $root->new_child("globals", "");
	$globals->new_child("devise", $global_monnaie);

	$tot_mnt_risque = 0;
	$tot_trim1 = 0;
	$tot_trim2 = 0;
	$tot_trim3 = 0;
	$tot_trim4 = 0;
	$tot_trim = 0;
	$index = 11;
  //element
  foreach ($DATA as $cle=>$valeur) {
  	$eta_credit=$root->new_child("creance", "");
  	$eta_credit->new_child("index", $index);
  	$eta_credit->new_child("libel_etat", $valeur['libel_etat']);
  	$eta_credit->new_child("annee_ecoulee", afficheMontant($valeur['mnt_risque'], false, $export_csv));
  	$tot_mnt_risque += $valeur['mnt_risque'];
  	$eta_credit->new_child("trim1", afficheMontant($valeur['trim_1'], false, $export_csv));
  	$tot_trim1 += $valeur['trim_1'];
  	$eta_credit->new_child("trim2", afficheMontant($valeur['trim_2'], false, $export_csv));
  	$tot_trim2 += $valeur['trim_2'];
  	$eta_credit->new_child("trim3", afficheMontant($valeur['trim_3'], false, $export_csv));
  	$tot_trim3 += $valeur['trim_3'];
  	$eta_credit->new_child("trim4", afficheMontant($valeur['trim_4'], false, $export_csv));
  	$tot_trim4 += $valeur['trim_4'];
  	$eta_credit->new_child("total_creance", afficheMontant($valeur['tot_trim'], false, $export_csv));
  	$tot_trim += $valeur['tot_trim'];
  	$index++;
  }
	$total=$root->new_child("total", "");
	$total->new_child("libel_etat", _("TOTAL"));
  $total->new_child("tot_annee_ecoulee", afficheMontant($tot_mnt_risque, false, $export_csv));
  $total->new_child("tot_trim1", afficheMontant($tot_trim1, false, $export_csv));
  $total->new_child("tot_trim2", afficheMontant($tot_trim2, false, $export_csv));
  $total->new_child("tot_trim3", afficheMontant($tot_trim3, false, $export_csv));
  $total->new_child("tot_trim4", afficheMontant($tot_trim4, false, $export_csv));
  $total->new_child("total_trim", afficheMontant($tot_trim, false, $export_csv));

return $document->dump_mem(true);
}

function xml_credits_perte($DATA, $list_criteres,  $date_deb, $date_fin, $export_csv = false) {
	
  // Fonction générant le XML utilisé pour le rapport 39 - Liste des crédits en perte

  global $adsys;
  global $global_monnaie;
  global $global_multidevise;

  $document = create_xml_doc("credits_perte", "credits_perte.dtd");

  //définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-PRT', " du $date_deb au $date_fin");
  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $list_criteres);

  $total = $root->new_child("total", "");
  $total->new_child("total_perte", afficheMontant($DATA["TOTAL"], true, $export_csv));
  $total->new_child("total_perte_rec", afficheMontant($DATA["TOTAL_rec"], true, $export_csv));
  //ticket_412
  //$total->new_child("total_prov_mnt", afficheMontant($DATA["TOTAL_rec"], true, $export_csv)); 
  $total->new_child("total_perte_rec", afficheMontant($DATA["TOTAL_int_rec"], true, $export_csv));
  $total->new_child("total_prov_mnt", afficheMontant($DATA["TOTAL_prov_mnt"], true, $export_csv));
  //AT-51 : total capital, interet et penalite
  $total->new_child("total_cap_recupere", afficheMontant($DATA["TOTAL_cap_rec"], true, $export_csv));
  $total->new_child("total_int_recupere", afficheMontant($DATA["TOTAL_int_rec"], true, $export_csv));
  $total->new_child("total_pen_recupere", afficheMontant($DATA["TOTAL_pen_rec"], true, $export_csv));
  

  $DET = $DATA["DETAILS"];
  $details = $root->new_child("details", "");
  $i = 0;
  while (list (, $DOSS) = each($DET)) {
    setMonnaieCourante($DOSS["devise"]);
    $i++;
	if (($DOSS["membre"] == 1) || (($DOSS["gs_cat"] == 1) || ($DOSS["gs_multiple"] == "OK"))) {
			 $credit = $details->new_child("credit_gs", "");
			 if($DOSS["membre"] == 1) $credit->new_child("membre_gs","true");
		}
    else $credit = $details->new_child("credit", "");
    $credit->new_child("index", $i);
    $credit->new_child("id_doss", $DOSS["id_doss"]);
    $credit->new_child("id_client", makeNumClient($DOSS["id_client"]));
    $credit->new_child("nom", getClientName($DOSS["id_client"]));
    $PROD = getProdInfo("WHERE id = " . $DOSS["id_prod"], $DOSS["id_doss"]);
    $credit->new_child("produit", $PROD[0]["libel"]);
    $credit->new_child("obj_dem", $DOSS["detail_obj_dem"]);
		if ($DOSS ["etat"] == 6) {
			// $credit->new_child ( "mnt_perte", afficheMontant ( $DOSS ["mnt_rec"], true, $export_csv ) );
			if (($DOSS ["mnt_rec"]) == null) {
				$zero = "0";
				$credit->new_child ( "mnt_perte", afficheMontant ( recupMontant ( $zero ), true, $export_csv ) );
			} else {
				$credit->new_child ( "mnt_perte", afficheMontant ( recupMontant ( $DOSS ["mnt_rec"] ), true, $export_csv ) );
			}
		} else {
			$credit->new_child ( "mnt_perte", afficheMontant ( recupMontant ( $DOSS ["mnt_perte"] ), true, $export_csv ) );
		}
    $credit->new_child("date", $DOSS["date"]);
    $credit->new_child("prov_date", $DOSS["prov_date"]);
    $credit->new_child("prov_mnt", afficheMontant($DOSS["prov_mnt"], true, $export_csv));
    setMonnaieCourante($global_monnaie);
    $credit->new_child("mnt_rec", afficheMontant($DOSS["mnt_rec"], true, $export_csv));
    //ticket_412_integration 322 separation-interet
    $credit->new_child("int_rec", afficheMontant($DOSS["int_rec"], true, $export_csv));
    //ticket_412 penalité
    $credit->new_child("pen_rec", afficheMontant($DOSS["pen_rec"], true, $export_csv));
    
    setMonnaieCourante($DOSS["devise"]);
  }

  return $document->dump_mem(true);
}


/**
 * Fonction générant le XML utilisé pour le rapport du registre des credits
 *
 * @param array $DATA Tableau contenant les infos globales sur les DCR
 * @param array $liste_criteres Les critères ayant été utilisés pour sélectionner les DCR
 * @param int $devise La devise des DCR si multidevise, NULL sinon.
 * @return str le document XML
 */
function xml_registrecredit_info_synth($DATA, $liste_criteres, $devise = NULL, $export_csv = false) {
  global $adsys;
  global $global_multidevise;
  global $global_monnaie;

  $document = create_xml_doc("registrecredit", "registrecredit.dtd");
  //Définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-REG');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $liste_criteres, $devise);

	$tot_credit = $DATA["nbr_credit"];
	$tot_montant = 0 + $DATA["mnt_octr"];
	$tot_montant_deb = 0 + $DATA["mnt_deb"];
	$tot_capital = 0 + $DATA["tot_mnt_remb_cap"];
	$tot_interet = 0 + $DATA["tot_mnt_remb_int"];
	$tot_interet_du = 0 + $DATA["int_du"];
	$tot_prov_mnt = 0 + $DATA["prov_mnt"];
	$tot_garantie = 0 + $DATA["tot_mnt_remb_gar"];
	$tot_penalite = 0 + $DATA["tot_mnt_remb_pen"];
	$tot_cap_perte = $DATA["capital_perte"];
	$tot_cap_restant = $DATA["capital_rest_du"];
	$total = $root->new_child("total", "");
  $total->new_child("nombre", $tot_credit);
  $total->new_child("montant", afficheMontant($tot_montant, false, $export_csv));
  $total->new_child("montant_deb", afficheMontant($tot_montant_deb, false, $export_csv));
  $total->new_child("capital", afficheMontant($tot_capital, false, $export_csv));
  $total->new_child("interet", afficheMontant($tot_interet, false, $export_csv));
  $total->new_child("int_du", afficheMontant($tot_interet_du, false, $export_csv));
  $total->new_child("prov_mnt", afficheMontant($tot_prov_mnt, false, $export_csv));
  $total->new_child("garantie", afficheMontant($tot_garantie, false, $export_csv));
  $total->new_child("penalite", afficheMontant($tot_penalite, false, $export_csv));
  $total->new_child("total_remb", afficheMontant($tot_capital + $tot_interet + $tot_garantie + $tot_penalite, false, $export_csv));
  $total->new_child("capital_perte", afficheMontant($tot_cap_perte, false, $export_csv));
  $total->new_child("capital_du", afficheMontant($tot_cap_restant, false, $export_csv));


  return $document->dump_mem(true);

}
/**
 * Fonction générant le XML utilisé pour le rapport du registre des credits
 *
 * @param array $DATA Tableau contenant les infos des DCR à placer dans le rapport
 * @param array $liste_criteres Les critères ayant été utilisés pour sélectionner les DCR
 * @param int $devise La devise des DCR si multidevise, NULL sinon.
 * @return str le document XML
 */
function xml_registrecredit($DATA, $liste_criteres, $devise = NULL, $export_csv = false)
{
    global $adsys;
    global $global_multidevise;
    global $global_monnaie;   
    
    $document = create_xml_doc("registrecredit", "registrecredit.dtd");
     
    // Définition de la racine
    $root = $document->root();
    
    // En-tête généraliste
    gen_header($root, 'CRD-REG');
    
    // En-tête contextuel
    $header_contextuel = $root->new_child("header_contextuel", "");
    gen_criteres_recherche($header_contextuel, $liste_criteres, $devise);
     $produits = getProdInfoByID();
     ksort($produits);
     $total = array();
    while (list ($key, $value) = each($produits)) {
    $produit[$key] = $root->new_child("ligneCredit", "");
    $produit[$key]->new_child("lib_prod", $value['libel'] . " (" . $value["devise"] . ")");

      $total[$value['id']]['prod_nombre'] = 0;
      $total[$value['id']]['prod_montant'] = 0;
      $total[$value['id']]['prod_montant_deb'] = 0;
      $total[$value['id']]['prod_capital'] = 0;
      $total[$value['id']]['prod_interet'] = 0;
      $total[$value['id']]['prod_garantie'] = 0;
      $total[$value['id']]['prod_penalite'] = 0;
      $total[$value['id']]['prod_total_remb'] = 0;
      $total[$value['id']]['prod_capital_du'] = 0;
      $total[$value['id']]['prod_int_du'] = 0;
      $total[$value['id']]['prod_prov_mnt'] = 0;

  }
  //Total Final init
  $tot_nombre =0 ;
  $tot_montant =0 ;
  $tot_montant_deb =0 ;
  $tot_capital =0 ;
  $tot_interet =0 ;
  $tot_garantie =0 ;
  $tot_penalite =0 ;
  $tot_total_remb =0 ;
  $tot_capital_du =0 ;
  $tot_int_du =0 ;
  $tot_prv_mnt =0 ;

  /***************************************************************************************
   *Evolution groupes solidaires:T615
   * @author Kheshan A.G
   * BD-MU
   **************************************************************************************/
  //fonction  pour recuperer les info des gs dans la table ad_dcr_grp_sol
  $lesGrpSolidaire = getInfoGroupeSolidaire();
  /**
   *Eclatement de type de dossier de credits et insertion dan un array array_dcr
   *  Structure du tableaux $array_dcr :
   *        id_produit_credit
   *           ->id_grp_sol 0r 0 for unitaire
   *                ->id_dossier
   *                     ->details dossier
   * */
  $array_dcr = array();//array dossier unitaire+ solidaire+unique

  if ($DATA != NULL) {
    foreach ($DATA as $id_doss => $details) { // pour chaque dossier de credits
      foreach ($produits as $key_prod => $value_prod) { //pour chaque  produit de credits
        if ($key_prod == $details ['id_prod']) {

          if ($details["gs_cat"] == 0) {//dossier unitaire
            $array_dcr [$key_prod][0][$id_doss] = $details; //grouper par prod et les dossiers
          }else if ($details["gs_cat"]== 1){//array dossier gs unique
            $array_dcr [$key_prod][$id_doss][0] = $details; //le dossier principal+info client principal dossier unik
            //NOTE : ad_dcr_grp_sol.id_dcr_grp_sol = id_doss du dossier unique dans le cas dun gs a dossier unique
            //on recupere ces membres(id_membre) dans ad_dcr_grp_sol
            $info =recupMembreGsUnik($id_doss);
            foreach ($info as $id_membre => $details_membre) {
              $array_dcr [$key_prod][$id_doss][$id_membre] = $details_membre;
            }
          } else if ($details["gs_cat"] == 2) {//dossier gs multiple
            foreach ($lesGrpSolidaire as $id_sol => $value_sol) {
              if ($id_sol == $details ['id_dcr_grp_sol']) {
                $info=recupMembreGs($id_sol);
                $array_dcr [$key_prod][$id_sol][0] = $info[$id_sol];
                $array_dcr [$key_prod][$id_sol][$id_doss] = $details; //grouper par prod et les dossiers
              }
            }
          }
        }
      }
    }
  }
  /**
   * Xml builder _rapport registre de prets 09/2015
   *
   */
  if ($array_dcr != NULL) {
    if ($global_multidevise) {
      setMonnaieCourante($value["devise"]);
    }
    while ( list ( $id_produit, $info_grp ) = each ($array_dcr ) ) {
      //pour le produit concerné->recupere et traite les dossier unitaire
      if(sizeof($id_produit)==1 && key($info_grp)==0) {
        while ( list ( $key_doss, $details ) = each ($info_grp[0] ) ) {
          //create subroot infosCreditSolidiaire
          $infosCreditSolidiaire = $produit[$id_produit]->new_child("infosCreditSolidiaire", "");

          $infosCreditSolidiaire->new_child("no_dossier", $key_doss);
          $infosCreditSolidiaire->new_child("num_client", $details["id_client"]);
          $nomClient = getNomClient($details["id_client"]);
          $infosCreditSolidiaire->new_child("nom_client", $nomClient);
          $infosCreditSolidiaire->new_child("cre_mnt_deb", afficheMontant($details["cre_mnt_deb"], false, $export_csv));
          $infosCreditSolidiaire->new_child("cre_mnt_octr", afficheMontant($details["cre_mnt_octr"], false, $export_csv));
          $infosCreditSolidiaire->new_child("cre_date_debloc", pg2phpDate($details["cre_date_debloc"]));
          $infosCreditSolidiaire->new_child("duree_mois", $details["duree_mois"]);
          $infosCreditSolidiaire->new_child("cre_etat", $details["cre_etat"]);
          $zero = 0;
          if ($details["mnt_remb_cap"] > 0) {
            $infosCreditSolidiaire->new_child("mnt_remb_cap", afficheMontant($details["mnt_remb_cap"], false, $export_csv));
          } else {
            $infosCreditSolidiaire->new_child("mnt_remb_cap", $zero);
          }
          if ($details["mnt_remb_int"] > 0) {
            $infosCreditSolidiaire->new_child("mnt_remb_int", afficheMontant($details["mnt_remb_int"], false, $export_csv));
          } else {
            $infosCreditSolidiaire->new_child("mnt_remb_int", $zero);
          }
          if ($details["mnt_remb_pen"] > 0) {
            $infosCreditSolidiaire->new_child("mnt_remb_pen", afficheMontant($details["mnt_remb_pen"], false, $export_csv));
          } else {
            $infosCreditSolidiaire->new_child("mnt_remb_pen", $zero);
          }
          if ($details["mnt_remb_pen"] > 0) {
            $infosCreditSolidiaire->new_child("mnt_remb_gar", afficheMontant($details["mnt_remb_gar"], false, $export_csv));
          } else {
            $infosCreditSolidiaire->new_child("mnt_remb_gar", $zero);
          }
          if($details["prov_mnt"]>0) {
            $infosCreditSolidiaire->new_child("mnt_prov", afficheMontant($details["prov_mnt"], false, $export_csv));
          }else{
            $infosCreditSolidiaire->new_child("mnt_prov", $zero);
          }

          //CALCUL MONTANT REMB TOTAL
          if ($details["is_ligne_credit"] == 't') {
            $rem_tot =$details["mnt_remb_int"] + $details["mnt_remb_gar"] + $details["mnt_remb_pen"];
            $infosCreditSolidiaire->new_child("mnt_remb_total", afficheMontant($rem_tot, false, $export_csv));
          } else {
            $rem_tot =$details["mnt_remb_cap"] + $details["mnt_remb_int"] + $details["mnt_remb_gar"] + $details["mnt_remb_pen"];
            $infosCreditSolidiaire->new_child("mnt_remb_total", afficheMontant($rem_tot, false, $export_csv));
          }

          //CALCUL CAPITAL RESTANT
          if ($details["is_ligne_credit"] == 't') {
            $capital_restant = getCapitalRestantDuLcr($key_doss, date('d/m/Y'));
          } else {
            $capital_restant = getSoldeCapital($key_doss);
          }
          if ($capital_restant == "")
            $capital_restant = 0;

            $infosCreditSolidiaire->new_child("capital_du", afficheMontant($capital_restant, false, $export_csv));

          //CALCUL INTERET RESTANT
          $interet_restant = getSoldeInteretGarPen($key_doss);
          $interet_restant = $interet_restant['solde_int'];
          if ($interet_restant == "")
            $interet_restant = 0;

            $infosCreditSolidiaire->new_child("int_du", afficheMontant($interet_restant, false, $export_csv));

          //CALCUL SOMME AU NIVEAU DE PRODUITS
          $total[$id_produit]['prod_nombre']++;
          $total[$id_produit]['prod_montant'] += $details["cre_mnt_octr"];
          $total[$id_produit]['prod_montant_deb'] += $details["cre_mnt_deb"];
          $total[$id_produit]['prod_capital'] += $details["mnt_remb_cap"];
          $total[$id_produit]['prod_interet'] += $details["mnt_remb_int"];
          $total[$id_produit]['prod_garantie'] += $details["mnt_remb_gar"];
          $total[$id_produit]['prod_penalite'] += $details["mnt_remb_pen"];
          $total[$id_produit]['prod_total_remb'] += $rem_tot;
          $total[$id_produit]['prod_capital_du'] += $capital_restant;
          $total[$id_produit]['prod_int_du'] += $interet_restant;
          $total[$id_produit]['prod_prov_mnt'] += $details["prov_mnt"];

          //CALCUL TOTAL FIN
          $tot_nombre ++ ;
          $tot_montant += $details["cre_mnt_octr"];
          $tot_montant_deb += $details["cre_mnt_deb"];
          $tot_capital += $details["mnt_remb_cap"];
          $tot_interet += $details["mnt_remb_int"];
          $tot_garantie += $details["mnt_remb_gar"];
          $tot_penalite += $details["mnt_remb_pen"];
          $tot_total_remb += $rem_tot;
          $tot_capital_du += $capital_restant;
          $tot_int_du  += $interet_restant;
          $tot_prv_mnt += $details["prov_mnt"];
        }
        //sinon pour le produit concerné->recupere et traite les groupe solidaire
      }else{
        while (list ($key_group, $dossiers) = each($info_grp)) {// pour chaque id_group_solidaire

          while (list ($key_doss, $details) = each($dossiers)) {// pour chaque dossiers attaché a ce groupe
            //create subroot infosCreditSolidiaire
            $infosCreditSolidiaire = $produit[$id_produit]->new_child("infosCreditSolidiaire", "");
            //traiment pour  visualiser le membre principal du gs
            if ($key_doss ==0) {
              if (isset($dossiers[0]["num_doss"])) {//traitment du holder(gs) du  dossier multiple

                $infosCreditSolidiaire->new_child("no_dossier", $dossiers[0]["num_doss"]);
                $infosCreditSolidiaire->new_child("num_client", $dossiers[0]["id_client_grp"]);
                $infosCreditSolidiaire->new_child("nom_client", $dossiers[0]["gi_nom"]);
                $infosCreditSolidiaire->new_child("cre_mnt_octr", afficheMontant($dossiers[0]["mnt_dem_grp"], false, $export_csv));
                //pas de calcul total: car cest sommer au niveau de ces membre multiple
              }else {//traitement du holder du dossier unique
                $infosCreditSolidiaire->new_child("no_dossier", $dossiers[0]["id_doss"]);
                $infosCreditSolidiaire->new_child("num_client", $dossiers[0]["id_client"]);
                $infosCreditSolidiaire->new_child("nom_client", $dossiers[0]["nom"]);
                $infosCreditSolidiaire->new_child("cre_mnt_deb", afficheMontant($dossiers[0]["cre_mnt_deb"], false, $export_csv));
                $infosCreditSolidiaire->new_child("cre_mnt_octr", afficheMontant($dossiers[0]["cre_mnt_octr"], false, $export_csv));
                $infosCreditSolidiaire->new_child("date_oct", pg2phpDate($dossiers[0]["date_oct"]));
                $infosCreditSolidiaire->new_child("cre_date_debloc", pg2phpDate($dossiers[0]["cre_date_debloc"]));
                $infosCreditSolidiaire->new_child("duree_mois", $dossiers[0]["duree_mois"]);
                $infosCreditSolidiaire->new_child("cre_etat", $dossiers[0]["cre_etat"]);

                $zero = 0;
                if($dossiers[0]["mnt_remb_cap"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_cap", afficheMontant($dossiers[0]["mnt_remb_cap"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_cap", $zero);
                }
                if($dossiers[0]["mnt_remb_int"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_int", afficheMontant($dossiers[0]["mnt_remb_int"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_int", $zero);
                }
                if($dossiers[0]["mnt_remb_pen"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_pen", afficheMontant($dossiers[0]["mnt_remb_pen"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_pen", $zero);
                }
                if($dossiers[0]["mnt_remb_gar"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_gar", afficheMontant($dossiers[0]["mnt_remb_gar"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_gar", $zero);
                }
                if($dossiers[0]["prov_mnt"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_prov", afficheMontant($dossiers[0]["prov_mnt"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_prov", $zero);
                }

                //CALCUL MONTANT REMB TOTAL
                if ($details["is_ligne_credit"] == 't') {
                  $rem_tot =$dossiers[0]["mnt_remb_int"] + $dossiers[0]["mnt_remb_gar"] + $dossiers[0]["mnt_remb_pen"];
                  $infosCreditSolidiaire->new_child("mnt_remb_total", afficheMontant($rem_tot, false, $export_csv));
                } else {
                  $rem_tot =$dossiers[0]["mnt_remb_cap"] + $dossiers[0]["mnt_remb_int"] + $dossiers[0]["mnt_remb_gar"] + $dossiers[0]["mnt_remb_pen"];
                  $infosCreditSolidiaire->new_child("mnt_remb_total", afficheMontant($rem_tot, false, $export_csv));
                }

                //CALCUL CAPITAL RESTANT
                if ($dossiers[0]["is_ligne_credit"] == 't') {
                  $capital_restant = getCapitalRestantDuLcr($dossiers[0]["id_doss"], date('d/m/Y'));
                } else {
                  $capital_restant = getSoldeCapital($dossiers[0]["id_doss"]);
                }
                if ($capital_restant == "")
                  $capital_restant = 0;

                  $infosCreditSolidiaire->new_child("capital_du", afficheMontant($capital_restant, false, $export_csv));

                //CALCUL INTERET RESTANT
                $interet_restant = getSoldeInteretGarPen($dossiers[0]["id_doss"]);
                $interet_restant = $interet_restant['solde_int'];
                if ($interet_restant == "")
                  $interet_restant = 0;

                  $infosCreditSolidiaire->new_child("int_du", afficheMontant($interet_restant, false, $export_csv));

                //CALCUL SOMME AU NIVEAU DE PRODUITS
                $total[$id_produit]['prod_nombre'] ++;
                $total[$id_produit]['prod_montant'] += $dossiers[0]["cre_mnt_octr"];
                $total[$id_produit]['prod_montant_deb'] += $dossiers[0]["cre_mnt_deb"];
                $total[$id_produit]['prod_capital'] += $dossiers[0]["mnt_remb_cap"];
                $total[$id_produit]['prod_interet'] += $dossiers[0]["mnt_remb_int"];
                $total[$id_produit]['prod_garantie'] += $dossiers[0]["mnt_remb_gar"];
                $total[$id_produit]['prod_penalite'] += $dossiers[0]["mnt_remb_pen"];
                $total[$id_produit]['prod_total_remb'] += $rem_tot;
                $total[$id_produit]['prod_capital_du'] += $capital_restant;
                $total[$id_produit]['prod_int_du'] += $interet_restant;
                $total[$id_produit]['prod_prov_mnt'] += $details["prov_mnt"];

                //CALCUL TOTAL FIN
                $tot_nombre ++ ;
                $tot_montant += $dossiers[0]["cre_mnt_octr"];
                $tot_montant_deb += $dossiers[0]["cre_mnt_deb"];
                $tot_capital += $dossiers[0]["mnt_remb_cap"];
                $tot_interet += $dossiers[0]["mnt_remb_int"];
                $tot_garantie += $dossiers[0]["mnt_remb_gar"];
                $tot_penalite += $dossiers[0]["mnt_remb_pen"];
                $tot_total_remb += $rem_tot;
                $tot_capital_du += $capital_restant;
                $tot_int_du  += $interet_restant;
                $tot_prv_mnt += $details["prov_mnt"];
              }
            } else {
              if ( $details["gs_cat"]==1) {//traitement info pour les clients du dossier unique
                $infosCreditSolidiaire->new_child("no_dossier", $details["id_dcr_grp_sol"]);
                $infosCreditSolidiaire->new_child("num_client", $details["id_membre"]);
                //getnomClient
                $nomClient = getNomClient($details["id_membre"]);
                $infosCreditSolidiaire->new_child("nom_client", $nomClient);
                $infosCreditSolidiaire->new_child("cre_mnt_octr", afficheMontant($details["mnt_dem"], false, $export_csv));

              } else{//traitement des dossier multiple des membres

                $infosCreditSolidiaire->new_child("no_dossier", $key_doss);
                $infosCreditSolidiaire->new_child("num_client", $details["id_client"]);
                //getnomClient
                $nomClient = getNomClient($details["id_client"]);
                $infosCreditSolidiaire->new_child("nom_client", $nomClient);
                $infosCreditSolidiaire->new_child("cre_mnt_deb", afficheMontant($details["cre_mnt_deb"], false, $export_csv));
                $infosCreditSolidiaire->new_child("cre_mnt_octr", afficheMontant($details["cre_mnt_octr"], false, $export_csv));
                $infosCreditSolidiaire->new_child("cre_date_debloc", pg2phpDate($details["cre_date_debloc"]));
                $infosCreditSolidiaire->new_child("duree_mois", $details["duree_mois"]);
                $infosCreditSolidiaire->new_child("cre_etat", $details["cre_etat"]);
                $zero = 0;
                if($details["mnt_remb_cap"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_cap", afficheMontant($details["mnt_remb_cap"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_cap", $zero);
                }

                if($details["mnt_remb_int"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_int", afficheMontant($details["mnt_remb_int"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_int", $zero);
                }
                if($details["mnt_remb_pen"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_pen", afficheMontant($details["mnt_remb_pen"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_pen", $zero);
                }
                if($details["mnt_remb_pen"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_remb_gar", afficheMontant($details["mnt_remb_gar"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_remb_gar", $zero);
                }
                if($details["prov_mnt"]>0) {
                  $infosCreditSolidiaire->new_child("mnt_prov", afficheMontant($details["prov_mnt"], false, $export_csv));
                }else{
                  $infosCreditSolidiaire->new_child("mnt_prov", $zero);
                }

                //CALCUL MONTANT REMB TOTAL
                if ($details["is_ligne_credit"] == 't') {
                  $rem_tot =$details["mnt_remb_int"] + $details["mnt_remb_gar"] + $details["mnt_remb_pen"];
                  $infosCreditSolidiaire->new_child("mnt_remb_total", afficheMontant($rem_tot, false, $export_csv));
                } else {
                  $rem_tot =$details["mnt_remb_cap"] + $details["mnt_remb_int"] + $details["mnt_remb_gar"] + $details["mnt_remb_pen"];
                  $infosCreditSolidiaire->new_child("mnt_remb_total", afficheMontant($rem_tot, false, $export_csv));
                }

                //CALCUL CAPITAL RESTANT
                if ($details["is_ligne_credit"] == 't') {
                  $capital_restant = getCapitalRestantDuLcr($key_doss, date('d/m/Y'));
                } else {
                  $capital_restant = getSoldeCapital($key_doss);
                }
                if ($capital_restant == "")
                  $capital_restant = 0;

                  $infosCreditSolidiaire->new_child("capital_du", afficheMontant($capital_restant, false, $export_csv));

                //CALCUL INTERET RESTANT
                $interet_restant = getSoldeInteretGarPen($key_doss);
                $interet_restant = $interet_restant['solde_int'];
                if ($interet_restant == "")
                  $interet_restant = 0;

                  $infosCreditSolidiaire->new_child("int_du", afficheMontant($interet_restant, false, $export_csv));

                //CALCUL SOMME AU NIVEAU DE PRODUIT
                $total[$id_produit]['prod_nombre'] ++;
                $total[$id_produit]['prod_montant'] += $details["cre_mnt_octr"];
                $total[$id_produit]['prod_montant_deb'] += $details["cre_mnt_deb"];
                $total[$id_produit]['prod_capital'] += $details["mnt_remb_cap"];
                $total[$id_produit]['prod_interet'] += $details["mnt_remb_int"];
                $total[$id_produit]['prod_garantie'] += $details["mnt_remb_gar"];
                $total[$id_produit]['prod_penalite'] += $details["mnt_remb_pen"];
                $total[$id_produit]['prod_total_remb'] += $rem_tot;
                $total[$id_produit]['prod_capital_du'] += $capital_restant;
                $total[$id_produit]['prod_int_du'] += $interet_restant;
                $total[$id_produit]['prod_prov_mnt'] += $details["prov_mnt"];

                //CALCUL TOTAL FIN
                $tot_nombre ++ ;
                $tot_montant += $details["cre_mnt_octr"];
                $tot_montant_deb += $details["cre_mnt_deb"];
                $tot_capital += $details["mnt_remb_cap"];
                $tot_interet += $details["mnt_remb_int"];
                $tot_garantie += $details["mnt_remb_gar"];
                $tot_penalite += $details["mnt_remb_pen"];
                $tot_total_remb += $rem_tot;
                $tot_capital_du += $capital_restant;
                $tot_int_du  += $interet_restant;
                $tot_prv_mnt += $details["prov_mnt"];
              }
            }
          }//end while dossiers
        }//end while id_grp_solidaire
      }//fin else
    }//end while navigateur des dossier
  }//end if _verificateur null
  reset($produits);
  while (list ($key, $value) = each($produits)) {
    //exclu le produit qui nón pas de dossier a afficher dans le rapport
    if ($total[$value['id']]['prod_montant'] == 0 && $total[$value['id']]['prod_montant_deb'] == 0) {
      $root->remove_child($produit[$value['id']]);
    } else {
      $xml_total = $produit[$key]->new_child("xml_total", "");

      //Creation obj xml  pour les somme au niveau de produit
      $xml_total->new_child("prod_nombre", afficheMontant($total[$value['id']]['prod_nombre'], false, $export_csv));
      $xml_total->new_child("prod_montant", afficheMontant($total[$value['id']]['prod_montant'], false, $export_csv));
      $xml_total->new_child("prod_montant_deb", afficheMontant($total[$value['id']]['prod_montant_deb'], false, $export_csv));
      $xml_total->new_child("prod_capital", afficheMontant($total[$value['id']]['prod_capital'], false, $export_csv));
      $xml_total->new_child("prod_interet", afficheMontant($total[$value['id']]['prod_interet'], false, $export_csv));
      $xml_total->new_child("prod_garantie", afficheMontant($total[$value['id']]['prod_garantie'], false, $export_csv));
      $xml_total->new_child("prod_penalite", afficheMontant($total[$value['id']]['prod_penalite'], false, $export_csv));
      $xml_total->new_child("prod_total_remb", afficheMontant($total[$value['id']]['prod_total_remb'], false, $export_csv));
      $xml_total->new_child("prod_capital_du", afficheMontant($total[$value['id']]['prod_capital_du'], false, $export_csv));
      $xml_total->new_child("prod_int_du", afficheMontant($total[$value['id']]['prod_int_du'], false, $export_csv));
      $xml_total->new_child("prod_prov_mnt", afficheMontant($total[$value['id']]['prod_prov_mnt'], false, $export_csv));
    }
  }
  //TOTAL SUMMARY FIN RAPPORT
      $total = $root->new_child("total", "");
      $total->new_child("nombre", $tot_nombre);
      $total->new_child("montant", afficheMontant($tot_montant, false, $export_csv));
      $total->new_child("montant_deb", afficheMontant($tot_montant_deb, false, $export_csv));
      $total->new_child("capital", afficheMontant($tot_capital, false, $export_csv));
      $total->new_child("interet", afficheMontant($tot_interet, false, $export_csv));
      $total->new_child("garantie", afficheMontant($tot_garantie, false, $export_csv));
      $total->new_child("penalite", afficheMontant($tot_penalite, false, $export_csv));
      $total->new_child("total_remb", afficheMontant($tot_total_remb, false, $export_csv));
      $total->new_child("capital_du", afficheMontant($tot_capital_du, false, $export_csv));
      $total->new_child("int_du", afficheMontant($tot_int_du, false, $export_csv));
      $total->new_child("prov_mnt", afficheMontant($tot_prv_mnt, false, $export_csv));

	return $document->dump_mem(true);
}

/**
  * Fonction générant le XML utilisé pour le rapport du registre des credits
  *
  * @param array $DATA Tableau contenant les infos des DCR à placer dans le rapport
  * @param array $liste_criteres Les critères ayant été utilisés pour sélectionner les DCR
  * @param int $devise La devise des DCR si multidevise, NULL sinon.
  * @return str le document XML
  */
function xml_creditactif($DATA, $liste_criteres, $devise = NULL, $export_csv = false) {
  global $adsys;
  global $global_multidevise;
  global $global_monnaie;
  $document = create_xml_doc("creditactif", "creditactif.dtd");

  //définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-CAA');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $liste_criteres, $devise);

  //body
  if ( is_array($DATA) ) {
    $tot_credit = $tot_prod_credit = 0;
    $tot_montant = $tot_prod_montant = 0;
    $tot_interet = $tot_prod_interet = 0;
    $tot_garantie = $tot_prod_garantie = 0;
    $tot_capital = $tot_prod_capital = 0;
    $tot_penalite =  $tot_prod_penalite = 0;
    $tot_cap_restant = $tot_prod_cap_restant = 0;
    $total_cap_restant = $sous_total_cap_restant = 0;

    set_time_limit(0);
    $gestionnaire_courant = "";
    $hier = hier(date("d/m/Y"));
    foreach ( $DATA as $value ) {
//      debug($value);
      if ($gestionnaire_courant != $value['gestionnaire']) {
        // On a un autre produit de crédit, on regroupe tous les DCR du même produit
        // Si le produit courant existe et qu'on passe à un autre produit, il faut d'abord stocker la somme
        if ( $gestionnaire_courant != "") {
          $sous_total = $gestionnaire->new_child("sous_total", "");
          $sous_total->new_child("prod_nombre", $tot_prod_credit);
          $sous_total->new_child("prod_montant", afficheMontant($tot_prod_montant, false, $export_csv));
          $sous_total->new_child("prod_capital", afficheMontant($tot_prod_capital, false, $export_csv));
          $sous_total->new_child("prod_interet", afficheMontant($tot_prod_interet, false, $export_csv));
          $sous_total->new_child("prod_garantie", afficheMontant($tot_prod_garantie, false, $export_csv));
          $sous_total->new_child("prod_penalite", afficheMontant($tot_prod_penalite, false, $export_csv));
          $sous_total->new_child("prod_total_remb", afficheMontant($tot_prod_capital + $tot_prod_interet + $tot_prod_garantie + $tot_prod_penalite, false, $export_csv));
	  $total_cap_restant += $tot_prod_cap_restant;
          $sous_total->new_child("prod_capital_du", afficheMontant($tot_prod_cap_restant, false, $export_csv));
          if ( $value["devise"] ) {
            $sous_total->new_child("prod_devise", $value["devise"]);
            setMonnaieCourante($value["devise"]);
          } else {
            $sous_total->new_child("prod_devise", $global_monnaie);
            setMonnaieCourante($global_monnaie);
          }
        }

        if ( $_POST["gest"] == NULL ) {
          $gestionnaire = $root->new_child("gestionnaire", "");
          $gestionnaire->new_child("nom_gestionnaire", _("Gestionnaire").": " . $value["gestionnaire"]);
        } else {
          $gestionnaire = $root->new_child("gestionnaire", "");
          $gestionnaire->new_child("nom_gestionnaire", _("Gestionnaire").": " . $value["gestionnaire"]);
        }
        $gestionnaire_courant = $value['gestionnaire'];

        $tot_prod_credit=0;
        $tot_prod_montant = 0;
        $tot_prod_interet = 0;
        $tot_prod_garantie = 0;
        $tot_prod_capital = 0;
        $tot_prod_penalite = 0;
        $tot_prod_cap_restant = 0;
        $sous_total_cap_restant = 0;
      }

      $client = $gestionnaire->new_child("client", "");
      if((($value["gs_cat"] == 1) && $value["membre"] == 0) || ($value["gs_multiple"] == "OK"))
      	 $client->new_child("groupe_gs", "groupe");
      elseif($value["membre"] == 1) $client->new_child("membre_gs", "membre");
      $client->new_child("nom_client", $value["nom"]);
      $client->new_child("num_client", $value["id_client"]);
	  $client->new_child("libel_prod", $value["libel"]);
      $client->new_child("num_dossier", $value["id_doss"]);
      $client->new_child("cre_mnt_octr", afficheMontant($value["cre_mnt_octr"]));
      $client->new_child("cre_date_approb", pg2phpDate($value["cre_date_approb"]));
      $client->new_child("cre_etat", $value["cre_etat"]);
      $client->new_child("localite", $value["localite"]);
      $client->new_child("mnt_remb_cap", afficheMontant($value["mnt_remb_cap"], false, $export_csv));
      $client->new_child("mnt_remb_int", afficheMontant($value["mnt_remb_int"], false, $export_csv));
      $client->new_child("mnt_remb_gar", afficheMontant($value["mnt_remb_gar"], false, $export_csv));
      $client->new_child("mnt_remb_pen", afficheMontant($value["mnt_remb_pen"], false, $export_csv));
      $client->new_child("mnt_remb_total", afficheMontant($value["mnt_remb_cap"] + $value["mnt_remb_int"] + $value["mnt_remb_gar"] + $value["mnt_remb_pen"], false, $export_csv));

      if ($value["is_ligne_credit"]=='t') {
          $capital_restant = getCapitalRestantDuLcr($value["id_doss"], php2pg($hier));
      } else {
          $capital_restant = getSoldeCapital($value["id_doss"]);
      }

      $client->new_child("capital_du", afficheMontant($capital_restant, false, $export_csv));
      if ($value["is_ligne_credit"]=='t') {
          
          $client->new_child("solde_int", afficheMontant(getCalculInteretsLcr($value["id_doss"], php2pg($hier)), false, $export_csv));
      } else {
          $client->new_child("solde_int", afficheMontant($value["mnt_int"] - $value["mnt_remb_int"], false, $export_csv));
      }
      $client->new_child("adresse", $value["adresse"]);
      $client->new_child("localite", $value["localite"]);
      $client->new_child("mnt_dem", afficheMontant($value["mnt_dem"]));
      $client->new_child("delai", $value["delai"]);
      $tot_prod_credit++;
      $tot_credit++;
      if (($value["gs_multiple"] == "OK") || (($value["gs_cat"] == 1) && ($value["membre"] == 1))) {
        	 --$tot_prod_credit;
        	 --$tot_credit;
       }

      $mnt = $capital_restant;
      if ( ($global_multidevise) && ($devise == NULL) ) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      $tot_prod_cap_restant += $mnt;
      $tot_cap_restant += $mnt;

      $mnt = $value['cre_mnt_octr'];
      if ( ($global_multidevise) && ($devise == NULL) ) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      $tot_prod_montant += $mnt;
      $tot_montant += $mnt;
       if (($value["gs_multiple"] == "OK") || (($value["gs_cat"] == 1) && ($value["membre"] == 0))) {
        	 $tot_prod_montant -= $mnt;
        	 $tot_montant -= $mnt;
       }
      $mnt = $value['mnt_remb_int'];
      if ( ($global_multidevise) && ($devise == NULL) ) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      
      if ($value["is_ligne_credit"]=='t') {
        $total_mnt_int = getCalculInteretsLcr($value["id_doss"], php2pg($hier), 0);
        $mnt_int_du = getCalculInteretsLcr($value["id_doss"], php2pg($hier));
        $tot_prod_interet += $total_mnt_int - $mnt_int_du;
        $tot_interet += $total_mnt_int - $mnt_int_du;
      } else {
        $tot_prod_interet += $mnt;
        $tot_interet += $mnt;
      }

      $mnt = $value['mnt_remb_gar'];
      if ( ($global_multidevise) && ($devise == NULL) ) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      $tot_prod_garantie += $mnt;
      $tot_garantie += $mnt;

      $mnt = $value['mnt_remb_cap'];
      if ( ($global_multidevise) && ($devise == NULL) ) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      if ($value["is_ligne_credit"]=='f') {
        $tot_prod_capital += $mnt;
        $tot_capital += $mnt;
      } else {
        $tot_prod_capital += 0; //getMontantRestantADebourserLcr($value["id_doss"], php2pg($hier));
        $tot_capital += 0; //getMontantRestantADebourserLcr($value["id_doss"], php2pg($hier));
      }
      $mnt = $value['mnt_remb_pen'];

      if ( ($global_multidevise) && ($devise == NULL) ) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      $tot_prod_penalite += $mnt;
      $tot_penalite += $mnt;

    }
# Il reste à ajouter le total du dernier produit traité
    $sous_total = $gestionnaire->new_child("sous_total", "");
    $sous_total->new_child("prod_nombre", $tot_prod_credit);
    $sous_total->new_child("prod_montant", afficheMontant($tot_prod_montant, false, $export_csv));
    $sous_total->new_child("prod_capital", afficheMontant($tot_prod_capital, false, $export_csv));
    $sous_total->new_child("prod_interet", afficheMontant($tot_prod_interet, false, $export_csv));
    $sous_total->new_child("prod_garantie", afficheMontant($tot_prod_garantie, false, $export_csv));
    $sous_total->new_child("prod_penalite", afficheMontant($tot_prod_penalite, false, $export_csv));
    $sous_total->new_child("prod_total_remb", afficheMontant($tot_prod_capital + $tot_prod_interet + $tot_prod_garantie + $tot_prod_penalite, false, $export_csv));
    
	$total_cap_restant += $tot_prod_cap_restant;
    $sous_total->new_child("prod_capital_du", afficheMontant($tot_prod_cap_restant, false, $export_csv));

    if ( $value["devise"] ) {
      $sous_total->new_child("prod_devise", $value["devise"]);
      setMonnaieCourante($value["devise"]);
    } else {
      $sous_total->new_child("prod_devise", $global_monnaie);
      setMonnaieCourante($global_monnaie);
    }

    // Et le total global
    if ( ($global_multidevise) && ($devise == NULL) ) {
      // On veut le total en devise de référence
      setMonnaieCourante($global_monnaie);
    }
    // Dans les autres cas, on est déjà dans la devise du dernier produit, qui est bonne pour le total
    $total = $root->new_child("total", "");
    $total->new_child("nombre", $tot_credit);
    $total->new_child("montant", afficheMontant($tot_montant, false, $export_csv));
    $total->new_child("capital", afficheMontant($tot_capital, false, $export_csv));
    $total->new_child("interet", afficheMontant($tot_interet, false, $export_csv));
    $total->new_child("garantie", afficheMontant($tot_garantie, false, $export_csv));
    $total->new_child("penalite", afficheMontant($tot_penalite, false, $export_csv));
    $total->new_child("total_remb", afficheMontant($tot_capital + $tot_interet + $tot_garantie + $tot_penalite, false, $export_csv));
    $total->new_child("capital_du", afficheMontant($total_cap_restant, false, $export_csv)); // $tot_cap_restant - Ticket #276
  }
  return $document->dump_mem(true);
}

/**
 * Génère le code XML pour le rapports des crédits arrivant à échéance
 *
 * @param array $DATA Les données des crédits arrivant à échéance, classées par la fonction getCreditsEcheance
 * @param array $list_criteres Les critères de sélection des crédits
 * @param boolean $export_csv Flag disant si le XML est pour générer un PDF ou un CSV.
 * @return string Le code XML généré.
 */
function xml_credit_echeance($DATA, $list_criteres, $export_csv = false) {
  global $global_monnaie;
  global $global_multidevise;
  $document = create_xml_doc("credit_echeance", "credit_echeance.dtd");

  //définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-AEC');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $list_criteres);

  $devise = $list_criteres[_("Devise")];
  setMonnaieCourante($devise);

  $capital = 0;
  $tot_capital = 0;

  //body
  if (is_array($DATA)) {
    $tot_gen_credit = 0;
    $tot_gen_montant = 0;
    $tot_gen_interet = 0;
    $tot_gen_garantie = 0;
    $tot_gen_reech = 0;
    $tot_gen_solde = 0;
    foreach ($DATA as $value) {
      // Pour chaque dossier de crédit
      if ($value["total"]["nombre"] > 0) {
        $ligne_credit = $root->new_child("ligne_credit", "");
        $ligne_credit->new_child("echeance", $value["libelle_echeance"]);
        if (is_array($value["credit"])) {
          foreach ($value["credit"] as $dcr) {
            if ($global_multidevise)
              setMonnaieCourante($dcr["devise"]);
            $ligne = $ligne_credit->new_child("ligne", "");
            if ((($dcr["gs_cat"] == 1) && $dcr["membre"] == 0) || ($dcr["gs_multiple"] == "OK"))
      	 		 $ligne->new_child("groupe_gs", "groupe");
      		elseif ($dcr["membre"] == 1)  $ligne->new_child("membre_gs", "membre");
            $ligne->new_child("num_doss", sprintf("%06d", $dcr["id_doss"]));
            $ligne->new_child("num_client", makeNumClient($dcr["id_client"]));
            $statut_juridique = getStatutJuridiqueClient($dcr["id_client"]);
 	          if($statut_juridique == 1){
 	            $nom_client = getClientNamePP($dcr["id_client"]);
 	          } else if($statut_juridique == 2){
 	            $nom_client = getClientNamePM($dcr["id_client"]);
 	          } else if($statut_juridique == 3 || $statut_juridique == 4){
 	            $nom_client = getClientNameGI($dcr["id_client"]);
 	          }
            $ligne->new_child("nom_client", $nom_client);
            $ligne->new_child("date_ech", pg2phpDate($dcr["date_ech"]));
            $ligne->new_child("mnt_cap", afficheMontant($dcr["mnt_cap"], false, $export_csv));
            $ligne->new_child("mnt_int", afficheMontant($dcr["mnt_int"], false, $export_csv));
            $ligne->new_child("mnt_gar", afficheMontant($dcr["mnt_gar"], false, $export_csv));
            $ligne->new_child("mnt_reech", afficheMontant($dcr["mnt_reech"], false, $export_csv));
            $ligne->new_child("solde_cap", afficheMontant($dcr["solde_cap"], false, $export_csv));
            $ligne->new_child("capital_du", afficheMontant($dcr["capital_du"], false, $export_csv));
          }
        }

        $sous_total = $ligne_credit->new_child("sous_total", "");
        $sous_total->new_child("nombre", $value["total"]["nombre"]);
        $tot_gen_credit += $value["total"]["nombre"];
        $sous_total->new_child("montant", afficheMontant($value["total"]["tot_mnt"], false, $export_csv));
        $tot_gen_montant += $value["total"]["tot_mnt"];
        $sous_total->new_child("interet", afficheMontant($value["total"]["tot_int"], false, $export_csv));
        $tot_gen_interet += $value["total"]["tot_int"];
        $sous_total->new_child("garantie", afficheMontant($value["total"]["tot_gar"], false, $export_csv));
        $tot_gen_garantie += $value["total"]["tot_gar"];
        $sous_total->new_child("reech", afficheMontant($value["total"]["tot_reech"], false, $export_csv));
        $tot_gen_reech += $value["total"]["tot_reech"];
        $sous_total->new_child("solde", afficheMontant($value["total"]["tot_solde"], false, $export_csv));
        $tot_gen_solde += $value["total"]["tot_solde"];
        $sous_total->new_child("capital_du", afficheMontant($value["total"]["tot_capital_du"], false, $export_csv));
        $tot_capital_du += $value["total"]["tot_capital_du"];

      }
    }

    $total_general = $root->new_child("total_general", "");
    $total_general->new_child("total_nombre", $tot_gen_credit);
    $total_general->new_child("total_montant", afficheMontant($tot_gen_montant, false, $export_csv));
    $total_general->new_child("total_interet", afficheMontant($tot_gen_interet, false, $export_csv));
    $total_general->new_child("total_garantie", afficheMontant($tot_gen_garantie, false, $export_csv));
    $total_general->new_child("total_reech", afficheMontant($tot_gen_reech, false, $export_csv));
    $total_general->new_child("total_solde", afficheMontant($tot_gen_solde, false, $export_csv));
    $total_general->new_child("total_capital", afficheMontant($tot_capital_du, false, $export_csv));

  }

  return $document->dump_mem(true);
}
/**
 * Génère le code XML pour le rapports des crédits repris
 *
 * @param array $DATA Les données des crédits repris
 * @param array $list_criteres Les critères de sélection des crédits repris
 * @param boolean $export_csv Flag disant si le XML est pour générer un PDF ou un CSV.
 * @return string Le code XML généré.
 */
function xml_credit_repris($DATA, $list_criteres, $export_csv = false) {
	global $global_monnaie;
	global $global_multidevise;
	//Produits d'epargne financiers'
	$produits= getProdInfo();
	$document = create_xml_doc("credits_repris", "credits_repris.dtd");

	//définition de la racine
	$root = $document->root();

	//En-tête généraliste
	gen_header($root, 'CRD-REP');

	//En-tête contextuel
	$header_contextuel = $root->new_child("header_contextuel", "");
	gen_criteres_recherche($header_contextuel, $list_criteres);
  $infos_synthetiques= $header_contextuel->new_child("infos_synthetiques", "");
  $nbre_total= $infos_synthetiques->new_child("nbre_total", "10");
  //element produit
	foreach ($produits as  $valeur) {
		$eltsProduit[$valeur['id']]=$root->new_child("produit", "");
		$eltsProduit[$valeur['id']]->new_child("libel", $valeur['libel'] . " (" . $valeur["devise"] . ")");

	}
  //detail
   $tab_etat=getListeEtatCredit();
	foreach ($DATA as $value) {
	  $credit_repris=$eltsProduit[$value['id_prod']]->new_child("credit_repris", "");
		$credit_repris->new_child("num_doss", $value["id_doss"]);
		$credit_repris->new_child("num_client", $value["id_client"]);
		$credit_repris->new_child("ancien_num_client",$value['anc_id_client']);
		$credit_repris->new_child("nom_client", getClientNameByArray($value));
		$credit_repris->new_child("mnt_repris", afficheMontant($value["mnt_repris"]));
		$credit_repris->new_child("etat",getLibel("adsys_etat_credits",$value["cre_etat"]));
		$credit_repris->new_child("date_reprise", pg2phpDate($value["date_reprise"]));
	}

return $document->dump_mem(true);
}

function xml_DossierProvisionne($DATA, $liste_criteres, $devise = NULL, $export_csv = false) {
  global $adsys;
  global $global_multidevise;
  global $global_monnaie;
  global $date_fin_provision;
  
  // Get current date
  $export_date = php2pg(date('d/m/Y'));

  if(!is_null($date_fin_provision) && $date_fin_provision != '') {
    $export_date = php2pg($date_fin_provision);
  }

  $document = create_xml_doc("provisioncredit", "provisioncredit.dtd");

  //Définition de la racine
  $root = $document->root();

  //En-tête généraliste
  gen_header($root, 'CRD-PCS');

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  gen_criteres_recherche($header_contextuel, $liste_criteres, $devise);

  //Body
  if (is_array($DATA)) {
    $tot_credit = $tot_prod_credit = 0;
    $tot_cap_restant = $tot_prod_cap_restant = 0;
    $tot_prov_mnt = 0;
    $tot_gar_num = 0;


    $produit_courant = "";
    foreach ($DATA as $value) {
    	if(!isset($produit[$value['id_prod']]) ) {
    		$produit[$value['id_prod']] = $root->new_child("produit", "");
    		if ($value["devise"]) {
          $produit[$value['id_prod']]->new_child("libel_prod", $value["libel"] . " (" . $value["devise"] . ")");
          $produit[$value['id_prod']]->new_child("prod_devise", $value["devise"]);
          setMonnaieCourante($value["devise"]);
        } else {
          $produit[$value['id_prod']]->new_child("libel_prod", $value["libel"] . " (" . $global_monnaie . ")");
          $produit[$value['id_prod']]->new_child("prod_devise", $global_monnaie);
          setMonnaieCourante($global_monnaie);
        }

        $tot_prod[$value['id_prod']]['credit'] = 0;
        $tot_prod[$value['id_prod']]['prov_mnt'] = 0;
        $tot_prod[$value['id_prod']]['cap_restant']=0;
        $tot_prod[$value['id_prod']]['gar_num'] = 0;
    	}
    	//traitement dossier de credit
    	 if (($value["membre"] == 1) || (($value["gs_cat"] == 1) || ($value["gs_multiple"]) == "OK")) {
         $client = $produit[$value['id_prod']]->new_child("client_credit_gs", "");
      	 if ($value["membre"] == 1 ) $client->new_child("membre_gs","true");
      }
      else $client = $produit[$value['id_prod']]->new_child("client", "");
      if($value["prov_mnt"]=="")
         $value["prov_mnt"]=0;
      if($value["gar_num"]=="")
         $value["gar_num"]=0;

      $client->new_child("num_client", makeNumClient($value["id_client"]));
      $client->new_child("nom_client", $value["nom"]);
      $client->new_child("num_dossier", $value["id_doss"]);
      $client->new_child("cre_etat", $value["cre_etat"]);
      $client->new_child("prov_date", pg2phpDate($value["prov_date"]));
      $client->new_child("cre_etat_date", pg2phpDate($value["cre_date_etat"]));
      
      if ($value["is_ligne_credit"] == 't') {
          $capital_restant = getCapitalRestantDuLcr($value["id_doss"], $export_date);
      } else {
          $capital_restant = $value["capital_restant"];
      }

      if($capital_restant=="")
         $capital_restant=0;
      $client->new_child("capital_du", afficheMontant($capital_restant, false, $export_csv));

      $tot_prod[$value['id_prod']]['credit']++;
      $tot_credit++;
      if (($value["gs_multiple"] == "OK") || (($value["gs_cat"] == 1) && ($value["membre"] == 1))) {
        	 --$tot_prod[$value['id_prod']]['credit'];
        	 --$tot_credit;
        }

      $mnt = $capital_restant;
      $tot_prod[$value['id_prod']]['cap_restant'] +=$mnt;
      if (($global_multidevise) && ($devise == NULL)) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      $tot_cap_restant +=$mnt;
      $client->new_child("prov_mnt", afficheMontant($value["prov_mnt"], false, $export_csv));

      $mnt =$value["prov_mnt"];
      $tot_prod[$value['id_prod']]['prov_mnt'] += $mnt;
      if (($global_multidevise) && ($devise == NULL)) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      $tot_prov_mnt += $mnt;

      $mnt =$value["gar_num"];
      $tot_prod[$value['id_prod']]['gar_num'] += $mnt;
      if (($global_multidevise) && ($devise == NULL)) {
        $mnt = calculeCV($value["devise"], $global_monnaie, $mnt);
      }
      $tot_gar_num += $mnt;
      $client->new_child("gar_num", afficheMontant($value["gar_num"], false, $export_csv));


    }//fin parcours dossiers

    //  ajouter le total des produits traités
    foreach ($tot_prod as $id_prod =>$value_prod) {
      $produit[$id_prod]->new_child("prod_nombre",$value_prod['credit']);
      $produit[$id_prod]->new_child("prod_total_provision", afficheMontant($value_prod['prov_mnt'], false, $export_csv));
      $produit[$id_prod]->new_child("prod_capital_du", afficheMontant($value_prod['cap_restant'], false, $export_csv));
      $produit[$id_prod]->new_child("prod_gar_num", afficheMontant($value_prod['gar_num'], false, $export_csv));

    }

    // Et le total global
    if (($global_multidevise) && ($devise == NULL)) {
      setMonnaieCourante($global_monnaie);
    }

    $total = $root->new_child("total", "");
    $total->new_child("nombre", $tot_credit);
    $total->new_child("total_provision", afficheMontant($tot_prov_mnt, false, $export_csv));
    $total->new_child("total_gar_num", afficheMontant($tot_gar_num, false, $export_csv));
    $total->new_child("capital_du", afficheMontant($tot_cap_restant, false, $export_csv));
  }

  return $document->dump_mem(true);
}


/**
 *
 * Renvoie le xml pour la generation du rapport de recouvrement sur le credit
 *
 * @param number $gestionnaire
 * @param string $export_date
 * @param number $type_affich
 * @param number $etat
 * @param string $export_csv
 * @return NULL|array
 */
function xml_recouvrement_credit($gestionnaire = 0, $export_date = NULL, $etat = 0, $prd ,$type_affich = 1, $export_csv = false, $export_date_debut = NULL)
{
    global $global_multidevise;
    global $global_monnaie;

    // Création racine
    global $global_id_agence;
    $document = create_xml_doc("recouvrement_credit", "recouvrement_credit.dtd");

    $DATAS = get_rapport_recouvrement_credit_data($gestionnaire, $export_date, $etat, $prd, $export_date_debut);

    if ($DATAS == NULL)
        return NULL;

    $root = $document->root();

    //En-tête généraliste
    gen_header($root, 'CRD-REC');

    //En-tête contextuel
    $header_contextuel = $root->new_child("header_contextuel", "");

    if(empty($gestionnaire))  $gestionnaire = _("Tous");
    else $gestionnaire = getLibel("ad_uti", $_POST["gest"]);

    // Les libelles des etats
    $etats_credit = getIDLibelTousEtatCredit();

    // le critere 'etat'
    if(empty($etat)){ //trac#720 : Commentaire no.5
      $etat_criteres = _("Tous");
    }
    else {
      if ($etat=='CA') {
        $etat_criteres = _("Crédits Actifs");
      }
      elseif ($etat=='SOLDE'){ //REL-30 : Gestion Crédits Soldé
        $etat_criteres = _("Crédits Soldé");
      }
      else {
        $etat_criteres =  $etats_credit[$etat];
      }
    }


    if(empty($prd))
        $prd = _("Tous");
    else $prd = getLibelPrdt ( $prd, "adsys_produit_credit" );

    // Affichage critere de recherches
    $criteres = array (
        _("Gestionnaire") => $gestionnaire,
        _("Date Debut") => date($export_date_debut), //ticket 720
        _("Date Fin") => date($export_date), //ticket 720
        _("Etat crédit") => _($etat_criteres),
        _("Produit de crédit") => _($prd),
        _("Devise") => _($global_monnaie) //ticket 720 : ajout devise pour simplifier l'affichage des montants
    );
    gen_criteres_recherche($header_contextuel, $criteres);

    // Recup des sections du rapport
    $grand_recap_array = $DATAS['grand_recap'];
    $recap_par_classe_array = $DATAS['recap_par_classe'];
    $details_recouvrement_array = $DATAS['details_recouvrement'];

    // Donnees Infos Synthetique
    $cap_restant_tot = $grand_recap_array['cap_restant_tot'];

    //ticket 720 : synthetique grand total
    $capital_attendu_total = $grand_recap_array['capital_attendu_total'];
    $interet_attendu_total = $grand_recap_array['interet_attendu_total'];
    $penalite_attendu_total = $grand_recap_array['penalite_attendu_total'];
    $capital_rembourse_total = $grand_recap_array['capital_rembourse_total'];
    $interet_rembourse_total = $grand_recap_array['interet_rembourse_total'];
    $penalite_rembourse_total = $grand_recap_array['penalite_rembourse_total'];
    //Gestion montants impayés REL-30
    $capital_impaye_total = $grand_recap_array['capital_impaye_total'];
    if ($capital_impaye_total < 0){ //si le montant remboursé est superieure au montant attendu alors dans ce cas il y a des remboursements anticipés
      $capital_impaye_total = 0;
    }
    $interet_impaye_total = $grand_recap_array['interet_impaye_total'];
    if ($interet_impaye_total < 0){ //si le montant remboursé est superieure au montant attendu alors dans ce cas il y a des remboursements anticipés
      $interet_impaye_total = 0;
    }
    $penalite_impaye_total = $grand_recap_array['penalite_impaye_total'];
    if ($penalite_impaye_total < 0){ //si le montant remboursé est superieure au montant attendu alors dans ce cas il y a des remboursements anticipés
      $penalite_impaye_total = 0;
    }

    $total_rembourse_total = $grand_recap_array['total_rembourse_total'];
    //ticket 720 : synthetique grand total
    $montant_tot = $grand_recap_array['montant_tot'];
    $coeff_tot = $grand_recap_array['coeff_tot'];

    // Xml Infos synthetique
    $infos_synthetique = $root->new_child("infos_synthetique", "");
    $infos_synthetique->new_child("cap_restant_tot", afficheMontant($cap_restant_tot, false, $export_csv));

    //ticket 720 : synthetique grand total
    $infos_synthetique->new_child("capital_attendu_total", afficheMontant($capital_attendu_total, false, $export_csv));
    $infos_synthetique->new_child("interet_attendu_total", afficheMontant($interet_attendu_total, false, $export_csv));
    $infos_synthetique->new_child("penalite_attendu_total", afficheMontant($penalite_attendu_total, false, $export_csv));
    $infos_synthetique->new_child("capital_rembourse_total", afficheMontant($capital_rembourse_total, false, $export_csv));
    $infos_synthetique->new_child("interet_rembourse_total", afficheMontant($interet_rembourse_total, false, $export_csv));
    $infos_synthetique->new_child("penalite_rembourse_total", afficheMontant($penalite_rembourse_total, false, $export_csv));
    $infos_synthetique->new_child("capital_impaye_total", afficheMontant($capital_impaye_total, false, $export_csv));
    $infos_synthetique->new_child("interet_impaye_total", afficheMontant($interet_impaye_total, false, $export_csv));
    $infos_synthetique->new_child("penalite_impaye_total", afficheMontant($penalite_impaye_total, false, $export_csv));
    $infos_synthetique->new_child("total_rembourse_total", afficheMontant($total_rembourse_total, false, $export_csv));
    //ticket 720 : synthetique grand total
    $infos_synthetique->new_child("montant_tot", afficheMontant($montant_tot, false, $export_csv));
    $infos_synthetique->new_child("coeff_tot", affichePourcentage($coeff_tot/100, 2, true));

    /* Récap par classe de credit */
    $recap_par_classe = $root->new_child("recap_par_classe", "");
    $recap_par_classe->new_child("entete_recap", _("Récapitulatif"));
    $details_recap = $recap_par_classe->new_child("details_recap", "");

    // xml recap par classe
    foreach($recap_par_classe_array as $recap)
    {
        $entete_recap =  $recap['entete_recap'];
        $capital_restant_du = $recap['details_recap']['cap_restant_recap'];

        $montant_total_retard = $recap['details_recap']['montant_recap'];
        $coeff = $recap['details_recap']['coeff_recap'];

        //ticket 720 : recap
        $capital_attendu_recap = $recap['details_recap']['capital_attendu_recap'];
        $interet_attendu_recap = $recap['details_recap']['interet_attendu_recap'];
        $penalite_attendu_recap = $recap['details_recap']['penalite_attendu_recap'];
        $capital_rembourse_recap = $recap['details_recap']['capital_rembourse_recap'];
        $interet_rembourse_recap = $recap['details_recap']['interet_rembourse_recap'];
        $penalite_rembourse_recap = $recap['details_recap']['penalite_rembourse_recap'];
        $total_rembourse_recap = $recap['details_recap']['total_rembourse_recap'];
        //REL-30 Gestion penalité impayé
        $capital_impaye_recap = $recap['details_recap']['capital_impaye_recap'];
        if ($capital_impaye_recap < 0){ //si le montant remboursé est superieure au montant attendu alors dans ce cas il y a des remboursements anticipés
          $capital_impaye_recap = 0;
        }
        $interet_impaye_recap = $recap['details_recap']['interet_impaye_recap'];
        if ($interet_impaye_recap < 0){ //si le montant remboursé est superieure au montant attendu alors dans ce cas il y a des remboursements anticipés
          $interet_impaye_recap = 0;
        }
        $penalite_impaye_recap = $recap['details_recap']['penalite_impaye_recap'];
        if ($penalite_impaye_recap < 0){ //si le montant remboursé est superieure au montant attendu alors dans ce cas il y a des remboursements anticipés
          $penalite_impaye_recap = 0;
        }

        $ligne_recap = $details_recap->new_child("ligne_recap", "");
        $ligne_recap->new_child("etat", $entete_recap);
        $ligne_recap->new_child("cap_restant_recap", afficheMontant($capital_restant_du, false, $export_csv));

        //ticket 720 : recap
        $ligne_recap->new_child("capital_attendu_recap", afficheMontant($capital_attendu_recap, false, $export_csv));
        $ligne_recap->new_child("interet_attendu_recap", afficheMontant($interet_attendu_recap, false, $export_csv));
        $ligne_recap->new_child("penalite_attendu_recap", afficheMontant($penalite_attendu_recap, false, $export_csv));
        $ligne_recap->new_child("capital_rembourse_recap", afficheMontant($capital_rembourse_recap, false, $export_csv));
        $ligne_recap->new_child("interet_rembourse_recap", afficheMontant($interet_rembourse_recap, false, $export_csv));
        $ligne_recap->new_child("penalite_rembourse_recap", afficheMontant($penalite_rembourse_recap, false, $export_csv));
        $ligne_recap->new_child("total_rembourse_recap", afficheMontant($total_rembourse_recap, false, $export_csv));
        $ligne_recap->new_child("capital_impaye_recap", afficheMontant($capital_impaye_recap, false, $export_csv));
        $ligne_recap->new_child("interet_impaye_recap", afficheMontant($interet_impaye_recap, false, $export_csv));
        $ligne_recap->new_child("penalite_impaye_recap", afficheMontant($penalite_impaye_recap, false, $export_csv));
        //ticket 720 : recap
        $ligne_recap->new_child("montant_recap", afficheMontant($montant_total_retard, false, $export_csv));
        $ligne_recap->new_child("coeff_recap", affichePourcentage($coeff/100, 2, true));
    }

    if($type_affich == 1) // affichage détaillé
    {
        $details_recouvrement = $root->new_child("details_recouvrement", "");

        foreach($details_recouvrement_array as $regroupement_dossiers)//regroupement par classe credit
        {
            $recouvrements_par_classe =  $details_recouvrement->new_child("recouvrements_par_classe", ""); //groupement par classe credit
            $classe_credit =  $recouvrements_par_classe->new_child("classe_credit", $regroupement_dossiers["classe_credit"]);  //recuperation de libel classecredit

            foreach( $regroupement_dossiers as $regroupement_produits) //regroupement par produit credit
            {

                if (is_array($regroupement_produits['tot'])){
                    $recouvrements_par_produits =  $recouvrements_par_classe->new_child("recouvrements_par_produits", ""); //groupement par classe credit
                    $libel_prod =  $recouvrements_par_produits->new_child("libel_prod", $regroupement_produits['tot']["libel_prod"]);
                    $dossiers_recouvrement =   $recouvrements_par_produits->new_child("dossiers_recouvrement", "");
                    $cap_restant_tot =  $recouvrements_par_produits->new_child("cap_restant_tot", afficheMontant($regroupement_produits['tot']["capital_restant_du_tot"], false, $export_csv));
                    $montant_retard_tot =  $recouvrements_par_produits->new_child("montant_retard_tot", afficheMontant($regroupement_produits['tot']["montant_tot_retard_tot"], false, $export_csv));
                    //ticket 720 : les montants attendus et remboursés
                  $capital_attendu_tot =  $recouvrements_par_produits->new_child("capital_attendu_tot", afficheMontant($regroupement_produits['tot']["capital_attendu_tot"], false, $export_csv));
                  $interet_attendu_tot =  $recouvrements_par_produits->new_child("interet_attendu_tot", afficheMontant($regroupement_produits['tot']["interet_attendu_tot"], false, $export_csv));
                  $penalite_attendu_tot =  $recouvrements_par_produits->new_child("penalite_attendu_tot", afficheMontant($regroupement_produits['tot']["penalite_attendu_tot"], false, $export_csv));
                  $capital_rembourse_tot =  $recouvrements_par_produits->new_child("capital_rembourse_tot", afficheMontant($regroupement_produits['tot']["capital_rembourse_tot"], false, $export_csv));
                  $interet_rembourse_tot =  $recouvrements_par_produits->new_child("interet_rembourse_tot", afficheMontant($regroupement_produits['tot']["interet_rembourse_tot"], false, $export_csv));
                  $penalite_rembourse_tot =  $recouvrements_par_produits->new_child("penalite_rembourse_tot", afficheMontant($regroupement_produits['tot']["penalite_rembourse_tot"], false, $export_csv));
                  $penalite_impaye_tot =  $recouvrements_par_produits->new_child("penalite_impaye_tot", afficheMontant($regroupement_produits['tot']["penalite_impaye_tot"], false, $export_csv));
                  $total_rembourse_tot =  $recouvrements_par_produits->new_child("total_rembourse_tot", afficheMontant($regroupement_produits['tot']["total_rembourse_tot"], false, $export_csv));
                }


                //Affichages de dossiers de credit du type produit
                if (is_array( $regroupement_produits['detail'])){
                    foreach ($regroupement_produits['detail'] as $ligne ){
                        if(is_array($ligne)){
                            // Donnees lignes recouvrements
                            $num_pret = $ligne['num_pret'];
                            $num_client = $ligne['num_client'];
                            $nom_client = $ligne['nom_client'];
                            $libel_gestionnaire = $ligne['gestionnaire'];
                            $libel_etat_credit = $ligne['etat_credit'];
                            $capital_restant_du = $ligne['cap_restant'];
                            $montant_total_retard = $ligne['montant_retard'];
                            $coeff = $ligne['coeff'];
                            //ticket 720 : les montants attendus et remboursés
                            $capital_attendu = $ligne['capital_attendu'];
                            $interet_attendu = $ligne['interet_attendu'];
                            $penalite_attendu = $ligne['penalite_attendu'];
                            $capital_rembourse = $ligne['capital_rembourse'];
                            $interet_rembourse = $ligne['interet_rembourse'];
                            $penalite_rembourse = $ligne['penalite_rembourse'];
                            $penalite_impaye = $ligne['penalite_impaye'];
                            if ($penalite_impaye < 0){ //si le montant remboursé est superieure au montant attendu alors dans ce cas il y avait des remboursements anticipé
                              $penalite_impaye = 0;
                            }
                            $total_rembourse = $ligne['total_rembourse'];

                            // xml lignes recouvrements
                            $ligne_recouvrement = $dossiers_recouvrement->new_child("ligne_recouvrement", "");
                            $ligne_recouvrement->new_child("num_pret", $num_pret);
                            $ligne_recouvrement->new_child("num_client", $num_client);
                            $ligne_recouvrement->new_child("nom_client", $nom_client);
                            $ligne_recouvrement->new_child("gestionnaire", $libel_gestionnaire);
                            $ligne_recouvrement->new_child("cap_restant", afficheMontant($capital_restant_du, false, $export_csv));
                            $ligne_recouvrement->new_child("montant_retard", afficheMontant($montant_total_retard, false, $export_csv));
                            $ligne_recouvrement->new_child("coeff", affichePourcentage($coeff/100, 2, true));
                            $ligne_recouvrement->new_child("etat_credit", $libel_etat_credit);
                            //ticket 720 : les montants attendus et remboursés
                            $ligne_recouvrement->new_child("capital_attendu", afficheMontant($capital_attendu, false, $export_csv));
                            $ligne_recouvrement->new_child("interet_attendu", afficheMontant($interet_attendu, false, $export_csv));
                            $ligne_recouvrement->new_child("penalite_attendu", afficheMontant($penalite_attendu, false, $export_csv));
                            $ligne_recouvrement->new_child("capital_rembourse", afficheMontant($capital_rembourse, false, $export_csv));
                            $ligne_recouvrement->new_child("interet_rembourse", afficheMontant($interet_rembourse, false, $export_csv));
                            $ligne_recouvrement->new_child("penalite_rembourse", afficheMontant($penalite_rembourse, false, $export_csv));
                            $ligne_recouvrement->new_child("penalite_impaye", afficheMontant($penalite_impaye, false, $export_csv));
                            $ligne_recouvrement->new_child("total_rembourse", afficheMontant($total_rembourse, false, $export_csv));
                        }
                    }
                }
            }

        }

    }


    return $document->dump_mem(true);
}

/**
 *
 * Renvoie le xml pour la generation du rapport de suivi ligne de crédit
 *
 * @param string $date_deb
 * @param string $date_fin
 * @param number $gest
 * @param number $num_client
 * @param number $prd_lcr
 * @param string $export_csv
 * @return NULL|array
 */
function xml_suivi_ligne_credit($date_deb, $date_fin, $gest, $num_client, $prd_lcr, $export_csv = false)
{
    global $global_multidevise;
    global $global_monnaie;
    global $adsys;

    // Création racine
    global $global_id_agence;

    $document = create_xml_doc("suivi_ligne_credit", "suivi_ligne_credit.dtd");

    $DATAS = get_rapport_suivi_ligne_credit_data($date_deb, $date_fin, $gest, $num_client, $prd_lcr);

    if ($DATAS == NULL) {
        return NULL;
    }

    $root = $document->root();

    //En-tête généraliste
    gen_header($root, 'CRD-SLC');

    //En-tête contextuel
    $header_contextuel = $root->new_child("header_contextuel", "");

    $client_name = '';
    if (!empty($num_client)) {
        $client_name = getClientName($num_client);
    }

    if(empty($gest)) {
        $gest = _("Tous");
    } else {
        $gest = getNomUtilisateur($gest);
    }

    if(empty($prd_lcr)) {
        $prd_lcr = _("Tous");
    } else {
        $prd_lcr = getLibelPrdt ( $prd_lcr, "adsys_produit_credit" );
    }

    // Affichage critere de recherches
    $criteres = array (
        _("Date de début") => date($date_deb),
        _("Date de fin") => date($date_fin),
        _("Gestionnaire") => $gest,
        _("Numéro Client") => $num_client,
        _("Nom Client") => $client_name,
        _("Produit ligne de crédit") => _($prd_lcr)
    );

    gen_criteres_recherche($header_contextuel, $criteres);

    // Recup des sections du rapport
    $grand_recap_arr = $DATAS['grand_recap'];
    $details_suivi_credit_arr = $DATAS['details_suivi_credit'];

    // Donnees Infos Synthetique
    $montant_octoye_total = $grand_recap_arr['montant_octoye_total'];
    $cap_debourse_total = $grand_recap_arr['cap_debourse_total'];
    $cap_restant_du_total = $grand_recap_arr['cap_restant_du_total'];
    $montant_dispo_total = $grand_recap_arr['montant_dispo_total'];
    $interets_restant_du_total = $grand_recap_arr['interets_restant_du_total'];
    $interets_payes_total= $grand_recap_arr['interets_payes_total'];
    $frais_restant_du_total = $grand_recap_arr['frais_restant_du_total'];
    $frais_payes_total = $grand_recap_arr['frais_payes_total'];

    // Xml Infos synthetique
    $infos_synthetique = $root->new_child("infos_synthetiques", "");
    $infos_synthetique->new_child("montant_octoye_total", afficheMontant($montant_octoye_total, true));
    $infos_synthetique->new_child("cap_debourse_total", afficheMontant($cap_debourse_total, true));
    $infos_synthetique->new_child("cap_restant_du_total", afficheMontant($cap_restant_du_total, true));
    $infos_synthetique->new_child("montant_dispo_total", afficheMontant($montant_dispo_total, true));
    $infos_synthetique->new_child("interets_restant_du_total", afficheMontant($interets_restant_du_total, true));
    $infos_synthetique->new_child("interets_payes_total", afficheMontant($interets_payes_total, true));
    $infos_synthetique->new_child("frais_restant_du_total", afficheMontant($frais_restant_du_total, true));
    $infos_synthetique->new_child("frais_payes_total", afficheMontant($frais_payes_total, true));

    // recupérer les produis de crédit
    $produit_credits_lcr = getListeProduitCredit('mode_calc_int=5');

    $produit = $total = array ();
    while (list ($key, $value) = each($produit_credits_lcr)) {
        $produit[$key] = $root->new_child("ligneCredit", "");
        $produit[$key]->new_child("lib_prod", $value);
        $total[$key]['tot_mnt_octr'] = 0;
    }

    // body
    if (is_array($details_suivi_credit_arr)) {
        foreach ($details_suivi_credit_arr as $id_doss=>$value) {

            $infosCredit = $produit[$value["id_prod"]]->new_child("infosCredit", "");
            $infosCredit->new_child("id_doss", $value["id_doss"]);
            $infosCredit->new_child("libel_prod", getLibelPrdt($value["id_prod"], "adsys_produit_credit" ));
            $infosCredit->new_child("num_client", $value["num_client"]);
            $infosCredit->new_child("nom_client", $value["nom_client"]);
            $infosCredit->new_child("libel_gestionnaire", $value["libel_gestionnaire"]);
            $infosCredit->new_child("montant_octroye", afficheMontant($value["montant_octroye"], false, $export_csv));
            $infosCredit->new_child("devise", $value["devise"]);
            $infosCredit->new_child("date_octroi", pg2phpDate($value["date_octroi"]));
            $infosCredit->new_child("duree", $value["duree"]);
            $infosCredit->new_child("etat", $adsys["adsys_etat_dossier_credit"][$value['etat']]);
            $infosCredit->new_child("montant_dispo", afficheMontant($value["montant_dispo"], false, $export_csv));
            $infosCredit->new_child("capital_restant_du", afficheMontant($value["capital_restant_du"], false, $export_csv));
            $infosCredit->new_child("interets_restant_du", afficheMontant($value["interets_restant_du"], false, $export_csv));
            $infosCredit->new_child("interets_payes", afficheMontant($value["interets_payes"], false, $export_csv));
            $infosCredit->new_child("frais_restant_du", afficheMontant($value["frais_restant_du"], false, $export_csv));
            $infosCredit->new_child("frais_payes", afficheMontant($value["frais_payes"], false, $export_csv));
            $infosCredit->new_child("date_dernier_deb", pg2phpDate($value["date_dernier_deb"]));
            $infosCredit->new_child("date_dernier_remb", pg2phpDate($value["date_dernier_remb"]));
            $infosCredit->new_child("date_fin_echeance", pg2phpDate($value["date_fin_echeance"]));

            $total[$value['id_prod']]['tot_mnt_octr'] += $value['montant_octroye'];
        }

        reset($produit_credits_lcr);

        while (list ($key, $value) = each($produit_credits_lcr)) {
            if ($total[$key]['tot_mnt_octr'] == 0) {
                $root->remove_child($produit[$key]);
            } else {
                $xml_total = $produit[$key]->new_child("xml_total", "");
                $xml_total->new_child("tot_mnt_octr", afficheMontant($total[$key]['tot_mnt_octr'], false, $export_csv));
            }
        }
    }

    return $document->dump_mem(true);
}

function xml_historisation_ligne_credit($infos_doss, $export_csv = false)
{
    global $global_multidevise;
    global $global_monnaie;
    global $adsys;

    // Création racine
    global $global_id_agence;

    $document = create_xml_doc("his_ligne_credit", "his_ligne_credit.dtd");

    $DATAS = get_rapport_his_ligne_credit_data($infos_doss);

    if ($DATAS == NULL) {
        return NULL;
    }

    $root = $document->root();

    //En-tête généraliste
    gen_header($root, 'REC-HLC');

    //En-tête contextuel
    //$header_contextuel = $root->new_child("header_contextuel", "");

    // Recup des sections du rapport
    $infos_syn_arr = $DATAS['infos_syn'];
    $details_his_credit_arr = $DATAS['details_his_credit'];
    $total_result_arr = $DATAS['total_result'];

    // Données Infos Synthetique
    $num_client = $infos_syn_arr['num_client'];
    $nom_client = $infos_syn_arr['nom_client'];
    $id_doss = $infos_syn_arr['id_doss'];
    $etat = $infos_syn_arr['etat'];
    $date_dem = $infos_syn_arr['date_dem'];
    $date_approb = $infos_syn_arr['date_approb'];
    $libel_prod = $infos_syn_arr['libel_prod'];
    $montant_octroye = $infos_syn_arr['montant_octroye'];
    $devise = $infos_syn_arr['devise'];
    $taux_interet = $infos_syn_arr['taux_interet'];
    $taux_frais = $infos_syn_arr['taux_frais'];
    $date_fin_ech = $infos_syn_arr['date_fin_ech'];


    // Xml Infos infos_synthetiques
    $infos_synthetique = $root->new_child("infos_synthetiques", "");
    $infos_synthetique->new_child("num_client", $num_client);
    $infos_synthetique->new_child("nom_client", $nom_client);
    $infos_synthetique->new_child("num_doss", $id_doss);
    $infos_synthetique->new_child("etat", $etat);
    $infos_synthetique->new_child("date_dem", $date_dem);
    $infos_synthetique->new_child("date_approb", $date_approb);
    $infos_synthetique->new_child("libel_prod", $libel_prod);
    $infos_synthetique->new_child("montant_octroye", afficheMontant($montant_octroye, false, $export_csv));
    $infos_synthetique->new_child("devise", $devise);
    $infos_synthetique->new_child("taux_interet", $taux_interet);
    $infos_synthetique->new_child("taux_frais", $taux_frais);
    $infos_synthetique->new_child("date_fin_ech", $date_fin_ech);

    $produit = array();
    $produit[$infos_doss['id_prod']] = $root->new_child("ligneCredit", "");

    // body
    $cap_restant_du_tmp = 0;
    if (is_array($details_his_credit_arr)) {
        foreach ($details_his_credit_arr as $date_evnt=>$his_credit) {
            foreach ($his_credit as $key=>$value) {
                $infosCredit = $produit[$infos_doss['id_prod']]->new_child("infosCredit", "");
                $infosCredit->new_child("date_evnt", pg2phpDate($value["date_evnt"]));
                $infosCredit->new_child("mnt_deb", afficheMontant($value["mnt_deb"], false, $export_csv));
                $infosCredit->new_child("cap_remb", afficheMontant($value["cap_remb"], false, $export_csv));
                $infosCredit->new_child("int_remb", afficheMontant($value["int_remb"], false, $export_csv));
                $infosCredit->new_child("frais_remb", afficheMontant($value["frais_remb"], false, $export_csv));
                $infosCredit->new_child("pen_remb", afficheMontant($value["pen_remb"], false, $export_csv));

                if ($value["cap_restant_du"] != -1) {
                    $cap_restant_du_tmp = $value["cap_restant_du"];
                }

                $infosCredit->new_child("cap_restant_du", afficheMontant($cap_restant_du_tmp, false, $export_csv));
            }
        }
    }

    $xml_total = $produit[$infos_doss['id_prod']]->new_child("xml_total", "");
    $xml_total->new_child("mnt_deb_tot", afficheMontant($total_result_arr['mnt_deb_total'], false, $export_csv));
    $xml_total->new_child("cap_remb_tot", afficheMontant($total_result_arr['cap_remb_total'], false, $export_csv));
    $xml_total->new_child("int_remb_tot", afficheMontant($total_result_arr['int_remb_total'], false, $export_csv));
    $xml_total->new_child("frais_remb_tot", afficheMontant($total_result_arr['frais_remb_total'], false, $export_csv));
    $xml_total->new_child("pen_remb_tot", afficheMontant($total_result_arr['pen_remb_total'], false, $export_csv));

    return $document->dump_mem(true);
}

function xml_list_inventaire_credits($DATA, $list_criteres,$date_deb,$date_fin,&$linenum, $isCsv = false, $isInfoSynt=false, $etat_tous=false, $etat_radie=false,$hasDevise=false) {

  global $global_id_agence, $global_id_profil, $adsys;
  $etatDossier = '';
  if($isCsv) {
    $document = create_xml_doc("inventaire_credit", "inventaire_credit_csv.dtd");
  } else {
    $document = create_xml_doc("inventaire_credit", "inventaire_credit.dtd");
  }
  //Element root
  $root = $document->root();

  //recuperation des données de l'agence'
  $AG = getAgenceDatas($global_id_agence);

  //En-tête généraliste
  gen_header($root, 'CRD-ICT');

  $header_contextuel = $root->new_child("header_contextuel", "");

  if ($isInfoSynt) {
    gen_informations_synthetiques($header_contextuel, $list_criteres);
  }

  //Corps
  $body = $root->new_child("body", "");

  //$linenum=0;
  foreach($DATA as $id_prod =>$value) {

    $tot_cap_deb =0;
    $tot_cap_deb_prd=0;
    $tot_cap_remb_en_cours_period=0;
    $tot_interet_ord_remb_en_cours_period=0;
    $tot_interet_ret_remb_en_cours_period=0;
    $tot_mnt_remb_period=0;
    $tot_cap_rest_du_fin_period=0;

    $produit_credit = $body -> new_child("produit_credit","");
    $produit_credit -> new_child("credit",getLibelProdCr($id_prod));

    if ($etat_tous === true){
      $produit_credit -> new_child("etat_tous","true");
    }
    if ($etat_radie === true){
      $produit_credit -> new_child("etat_radie","true");
    }
    else{
      $produit_credit -> new_child("etat_radie","false");
    }

    foreach($value as $id_doss => $value2){

      $mnt_deb=$value2['cre_mnt_deb_per'];
      $mnt_cap_deb_period=$value2['mnt_cap_debut'];
      $cap_remb=$value2['mnt_remb_cap'];
      $int_ordi_remb=$value2['tot_mnt_remb_int'];
      $mnt_ret_remb=$value2['mnt_remb_pen'];
      $mnt_tot_remb=$value2['mnt_tot'];
      $perte_capital=$value2['perte_capital'];
      $mnt_du = $value2['mnt_restant_du'];
      if ($etat_radie === true || $etat_tous === true) {
        if (($value2["type_rapport"] == '2-SOLDE' || $value2["type_rapport"] == '4-RADIE') && $value2["perte_capital"] > 0) {
          $mnt_du = $value2['perte_capital'];
        }
      }

      $ligne_credit = $produit_credit->new_child("ligne_credit", "");
      $ligne_credit->new_child("num_client", $value2['id_client']);
      $ligne_credit->new_child("num_dossier", $value2['id_doss']);
      $ligne_credit->new_child("nom_client",$value2['nom_cli']);
      $ligne_credit->new_child("cap_deb", afficheMontant($mnt_deb,$hasDevise,$isCsv));
      $ligne_credit->new_child("cap_deb_prd", afficheMontant($mnt_cap_deb_period,$hasDevise,$isCsv));
      $ligne_credit->new_child("cap_remb_en_cours_period", afficheMontant($cap_remb,$hasDevise,$isCsv));
      $ligne_credit->new_child("interet_ord_remb_en_cours_period", afficheMontant($int_ordi_remb,$hasDevise,$isCsv));
      $ligne_credit->new_child("interet_ret_remb_en_cours_period", afficheMontant($mnt_ret_remb,$hasDevise,$isCsv));
      $ligne_credit->new_child("mnt_total_remb_en_cours_period", afficheMontant($mnt_tot_remb,$hasDevise,$isCsv));
      if ($etat_radie === true){
        $ligne_credit->new_child("cap_rest_du_fin_period", afficheMontant($perte_capital,$hasDevise,$isCsv));
      }
      if ($etat_radie === false){
        $ligne_credit->new_child("cap_rest_du_fin_period", afficheMontant($mnt_du,$hasDevise,$isCsv));
      }

      if ($etat_tous === true){
        if ($mnt_du > 0 && $value2['type_rapport'] == '1-ENCOURS'){
          $etatDossier = 'Encours';
        }
        else if ($value2['type_rapport'] == '4-RADIE'){
          $etatDossier = 'Radié';
        }
        else if ($value2['type_rapport'] == '2-SOLDE' || $value2['type_rapport'] == '3-RADIE-SOLDE'){
          $etatDossier = 'Soldé';
        }
        $ligne_credit->new_child("etat_dossier", $etatDossier);
      }

      $tot_cap_deb += $mnt_deb;
      $tot_cap_deb_prd += $mnt_cap_deb_period;
      $tot_cap_remb_en_cours_period += $cap_remb;
      $tot_interet_ord_remb_en_cours_period += $int_ordi_remb;
      $tot_interet_ret_remb_en_cours_period +=$mnt_ret_remb;
      $tot_mnt_remb_period +=$mnt_tot_remb;
      $tot_cap_rest_du_fin_period +=$mnt_du;

    }
    //exit;
    $totals = $produit_credit -> new_child("totals","");
    $totals -> new_child("tot_cap_deb",afficheMontant($tot_cap_deb,$hasDevise,$isCsv));
    $totals -> new_child("tot_cap_deb_prd",afficheMontant($tot_cap_deb_prd,$hasDevise,$isCsv));
    $totals -> new_child("tot_cap_remb_en_cours_period",afficheMontant($tot_cap_remb_en_cours_period,$hasDevise,$isCsv));
    $totals -> new_child("tot_interet_ord_remb_en_cours_period",afficheMontant($tot_interet_ord_remb_en_cours_period,$hasDevise,$isCsv));
    $totals -> new_child("tot_interet_ret_remb_en_cours_period",afficheMontant($tot_interet_ret_remb_en_cours_period,$hasDevise,$isCsv));
    $totals -> new_child("tot_mnt_total_remb_en_cours_period",afficheMontant($tot_mnt_remb_period,$hasDevise,$isCsv));
    $totals -> new_child("tot_cap_rest_du_fin_period",afficheMontant($tot_cap_rest_du_fin_period,$hasDevise,$isCsv));
  }

  $xml = $document->dump_mem(true);

  return $xml;

}

/*******************************************************************************************************************************/
//TODO Changer les fichier de publication xslt et dtd
/**
 * Genere le xml pour le rapport des intérêts à recevoir
 *
 * @param $DATAS
 * @param $criteres
 * @return string
 */
function xml_calc_int_recevoir($DATAS, $criteres, $export_csv = false)
{

  global $adsys, $global_monnaie_courante, $global_langue_rapport, $global_langue_utilisateur;

  if(is_null($global_langue_rapport))
    $global_langue_rapport = $global_langue_utilisateur;

  $document = create_xml_doc("calc_int_recevoir", "calc_int_recevoir.dtd");
  $code_rapport = 'CPT-IAR';
  $total_int = 0;
  $devise = $global_monnaie_courante;

  $count_data = count($DATAS);
  if (is_null($count_data)) $count_data = "0";

  //Element root
  $root = $document->root();

  //En-tête généraliste
  $ref = gen_header($root, $code_rapport);

  //En-tête contextuel
  $header_contextuel = $root->new_child("header_contextuel", "");
  $criteres_header = $criteres;
  gen_criteres_recherche($header_contextuel, $criteres_header);

  // Infos synthetiques
  $infos_synthetique = $root->new_child("infos_synthetique", "");
  $total_int_recevoir = $infos_synthetique->new_child("total_int_recevoir", afficheMontant(recupMontant($DATAS['total_int_recevoir']), $devise, $export_csv));


  //Corps du rapport - donnees detaillés
  $calc_int_recevoir_data = $root->new_child("calc_int_recevoir_data", "");

  if($count_data > 0) {
    $prod = $calc_int_recevoir_data->new_child("prod", "");
    foreach ($DATAS['details'] as $id_prod => $donnees_prod)
    {

      $libel = $prod->new_child("libel", $donnees_prod['libel']);

      foreach($donnees_prod as $cpte_infos) {
        if(is_array($cpte_infos))
        {
          $ligne_int_recevoir = $prod->new_child("ligne_int_recevoir", "");
          $devise = $cpte_infos['devise'];
          $nom_client = $ligne_int_recevoir->new_child("nom_client", $cpte_infos['nom_cli']);
          $num_dossier = $ligne_int_recevoir->new_child("num_dossier", $cpte_infos['id_doss']);
          $num_client = $ligne_int_recevoir->new_child("num_client", $cpte_infos['id_client']);
          $capital = $ligne_int_recevoir->new_child("capital", afficheMontant(recupMontant($cpte_infos['cap_restant_du']), $devise, $export_csv));
          $date_debloc = $ligne_int_recevoir->new_child("date_debloc", localiser_date_rpt($cpte_infos["cre_date_debloc"]));
          $int_att_ech = $ligne_int_recevoir->new_child("int_att_ech", afficheMontant(recupMontant($cpte_infos['solde_int_ech'])));
          $date_th = $ligne_int_recevoir->new_child("date_th", localiser_date_rpt($cpte_infos['date_debut_theorique']));
          $nb_jours = $ligne_int_recevoir->new_child("nb_jours", $cpte_infos['nb_jours']);
          $montant_int = $ligne_int_recevoir->new_child("montant_int", afficheMontant(recupMontant($cpte_infos['montant']), $cpte_infos['devise'], $export_csv));
          $int_non_paye = $ligne_int_recevoir->new_child("int_non_paye", afficheMontant(recupMontant($cpte_infos['montant_prec'])));
          $iar_cumule = $ligne_int_recevoir->new_child("iar_cumule", afficheMontant(recupMontant($cpte_infos['montant_cumul'])));
        }
      }
      /*$total_cap_restant_du = afficheMontant(recupMontant($DATAS['total_cap_restant_du']), $devise, $export_csv);print_rn($donnees_prod['total_cap_restant_du']);
      $total_int_attendu_ech = afficheMontant(recupMontant($donnees_prod['total_int_attendu_ech']), $devise, $export_csv);
      $total_iar_echeance = afficheMontant(recupMontant($donnees_prod['total_iar_echeance']), $devise, $export_csv);
      $total_int_non_paye = afficheMontant(recupMontant($donnees_prod['total_int_non_paye']), $devise, $export_csv);
      $total_int_recevoir =  afficheMontant(recupMontant($donnees_prod['total_int_recevoir']), $devise, $export_csv);*/
    }
    $totals = $prod->new_child("totals", "");
    $total_cap_restant_du = $totals->new_child("total_cap_restant_du",afficheMontant(recupMontant($DATAS['total_cap_restant_du']), $devise, $export_csv));
    $total_int_attendu_ech = $totals->new_child("total_int_attendu_ech", afficheMontant(recupMontant($DATAS['total_int_attendu_ech']), $devise, $export_csv));
    $total_iar_echeance = $totals->new_child("total_iar_echeance", afficheMontant(recupMontant($DATAS['total_iar_echeance']), $devise, $export_csv));
    $total_int_non_paye = $totals->new_child("total_int_non_paye", afficheMontant(recupMontant($DATAS['total_int_non_paye']), $devise, $export_csv));
    $total_int_recevoir = $totals->new_child("total_int_recevoir", afficheMontant(recupMontant($DATAS['total_int_recevoir']), $devise, $export_csv));
  }
  $output = $document->dump_mem(true);
  return($output);
}
/*****************************************************************************************************************************************************/




?>