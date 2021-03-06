<?php
/* vim: set expandtab softtabstop=2 shiftwidth=2 */

/**
 * Batch : traitements de l'épargne
 * @package Systeme
 **/

require_once 'lib/dbProcedures/historique.php';
require_once 'lib/dbProcedures/epargne.php';
require_once 'lib/dbProcedures/agence.php';
require_once 'lib/dbProcedures/tarification.php';
require_once 'lib/dbProcedures/compte.php';
require_once 'lib/misc/divers.php';
require_once 'lib/dbProcedures/bdlib.php';
require_once 'batch/batch_declarations.php';
require_once 'lib/php-amqplib/MouvementMSQPublisher.php';
require_once 'lib/dbProcedures/message_queue.php';

/**********
* Met à jour les soldes de calcul des intérêts
* @since 1.0
* @param date $date_work La date du batch
* @return void
*/
function update_solde_min($date_work) {
  global $dbHandler;

  affiche(_("Mise à jour des soldes min ..."));
  incLevel();

  $db = $dbHandler->openConnection();

  /* Mise à jour des soldes de calcul des intérêts */
  $sql = "SELECT miseAjourSoldeInteret(date('$date_work'));";
  $result = $db->query($sql);
  if (DB::isError($result))
    erreur("update_solde_min()", $result->getMessage());

  $dbHandler->closeConnection(true);

  affiche(_("OK"), true);
  decLevel();
  affiche(_("Mise à jour des soldes min terminée !"));
}


/**********
 * Calcul les interets a payer pour les comptes a termes (DAT,CAT) si la frequence de calcul coîncide avec la date du batch
 *
 *
 * @author B&d
 * @param date $date_work La date du batch
 * @return ErrorObj Les erreurs possibles sont <UL>
 */
function calcul_interets_a_payer ($date_work) {
  global $global_mouvements_epargne;
  global $global_id_agence, $global_monnaie;
  global $dbHandler;
  global $comptes_calc_int;

  $db = $dbHandler->openConnection();
  $global_id_agence = getNumAgence();

  /* Initialisation des compteurs de comptes avec interets a calculer */
  $count_cat = 0;
  $count_dat = 0;
  $count_tot = 0;
  $freq_calc_int = NULL;

  // Recuperation du parametrage des frequences des interets a payer
  $sql = "SELECT freq_calc_int_paye FROM adsys_calc_int_paye WHERE id_ag = $global_id_agence;";
  $result=$db->query($sql);

  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    erreur("calcul_interets_a_payer()", $result->getUserInfo());
  }

  $tmprow = $result->fetchrow();
  $freq_calc_int = $tmprow[0];

  affiche(_("Calcul des intérêts à payer sur comptes d’épargne ..."));
  incLevel();
  set_time_limit(0);

  // Si les interets sont parametrés pour etre calculés :
  if(!is_null($freq_calc_int) && $freq_calc_int > 0)
  {
    $sql = "SELECT * from calcul_interets_a_payer('$date_work')";
    $result = $db->query($sql);

    if (DB::isError($result)) {
      $dbHandler->closeConnection(false);
      erreur("calcul_interets_a_payer()", $result->getUserInfo());
    }

    $count = $result->numrows();

    while ($row = $result->fetchrow(DB_FETCHMODE_ASSOC)) {
      //array_push($comptes_calc_int, $row);

      $count_tot++;
      if ($row["classe_comptable"] == 2)
        ++$count_dat;
      elseif ($row["classe_comptable"] == 5)
        ++$count_cat;
    }
  }

  $dbHandler->closeConnection(true);

  affiche(sprintf(_("OK. %s DAT et %s CAT ont été traités"),$count_dat,$count_cat), true);
  decLevel();
  affiche(_("Calcul des intérêts à payer sur comptes d’épargne terminé !"));

  return new ErrorObj(NO_ERR);
}

/**********
* Met à jour les soldes de calcul des intérêts, appelé après la rémunératioin des comptes
* @since 3.1
* @author  ibou ndiaye
* @param date $date_work La date du batch
* @return void
*/
//function updateSoldeInteretEpargneSource($date_work) {
//  global $dbHandler;
//
//  affiche(_("Mise à jour des soldes de calcul des intérêts ..."));
//  incLevel();
//
//  $db = $dbHandler->openConnection();
//
//  /* Mise à jour des soldes de calcul des intérêts */
//  $sql = "SELECT miseAjourSoldeInteretEpargneSource(date('$date_work'));";
//  $result = $db->query($sql);
//  if (DB::isError($result))
//    erreur("miseAjourSoldeInteretEpargneSource()", $result->getMessage());
//
//  $dbHandler->closeConnection(true);
//
//  affiche(_("OK"), true);
//  decLevel();
//  affiche(_("Mise à jour des soldes de calcul intérêts pour épargne à la source terminée !"));
//}
/**********
* Rémunère tous les comptes rémunérables (DAV,DAT,CAT, Autres dépôts,Capital social) dont une échéance coîncide avec la date du batch
*
* Cette procédure est longue dans le cas où bcp de comptes doivent être rémunérés.  Un indicateur de progression sera affiché à l'écran
* dans le cas de l'éxécution du batch à l'interface utilisateur afin que l'opérateur sache que le batch tourne toujours.
* @author
* @since 1.0
* @param date $date_work La date du batch
* @return ErrorObj Les erreurs possibles sont <UL>
*   <LI> Celles renvoyées par {@link #PayeInteret payeInteret} </LI> </UL>
*/
function arrete_comptes ($date_work) {
  global $global_mouvements_epargne;
  global $global_id_agence, $global_monnaie;
  global $dbHandler;
  global $arrete_comptes;
  /* Initialisation des compteurs de comptes rémunérés */
  $count_ce = 0;
  $count_en = 0;
  $count_ps = 0;
  $count_cat = 0;
  $count_dat = 0;
  $count_es = 0;
  $count_tot = 0;

  $db = $dbHandler->openConnection();
  $global_id_agence = getNumAgence();
  affiche(_("Arrêté des comptes ..."));
  incLevel();
  set_time_limit(0);

  $sql = "SELECT * from Arrete_Comptes('$date_work')";
  $result = $db->query($sql);

  if (DB::isError($result)) {
  	$dbHandler->closeConnection(false);
  	 erreur("Arrete_Comptes()", $result->getUserInfo());

  }

  /* Construire les données destinées au rapport compte rendu batch */
  $count = $result->numrows();
  while ($row = $result->fetchrow(DB_FETCHMODE_ASSOC)) {
  	array_push($arrete_comptes, $row);
    $count_tot++;
    if ($row["classe_comptable"] == 1)
      ++$count_ce;
    elseif ($row["classe_comptable"] == 2)
      ++$count_dat;
    elseif ($row["classe_comptable"] == 3)
      ++$count_en;
    elseif ($row["classe_comptable"] == 4)
      ++$count_ps;
    elseif ($row["classe_comptable"] == 5)
      ++$count_cat;
    elseif ($row["classe_comptable"] == 6)
      ++$count_es;
  }
  
  preleveFraisTransactionnelSMSEpargne($arrete_comptes);

  if (isMSQEnabled()){
      envoiSMSMouvementEpargne($arrete_comptes);
  }

  $dbHandler->closeConnection(true);
  affiche(sprintf(_("OK. %s comptes d'épargne, %s comptes de parts sociales, %s comptes d'épargne nantie, %s DAT, %s CAT et %s épargnes à la source ont été arrêtés"),$count_ce,$count_ps,$count_en,$count_dat,$count_cat,$count_es), true);

  decLevel();
  affiche(_("Arrêté des comptes terminé !"));

  return new ErrorObj(NO_ERR);
}

/**
 * Prélève les frais de tenue des comptes lorsqu'ils sont dus
 * @return ErrorObj
 */
function prelevement_frais_tenue_cpt() {
  global $global_id_agence, $global_mouvements_epargne, $frais_tenue_cpte;
  global $date_total;
  global $dbHandler;

  affiche (_("Prelevement des frais de tenue de compte ..."));

  $db = $dbHandler->openConnection();
  $global_id_agence = getNumAgence();
  /* Est-ce la fin de l'année ? */
  $sql = "SELECT * FROM isFinAnnee('$date_total');";
  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__);
  }

  $row = $result->fetchrow();
  $is_fin_annee = $row[0];

  /* Est-ce la fin du semestre ? */
  $sql = "SELECT * FROM isFinSemestre('$date_total');";
  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__);
  }

  $row = $result->fetchrow();
  $is_fin_semestre = $row[0];

  /* Est-ce la fin du trimestre ? */
  $sql = "SELECT * FROM isFinTrimestre('$date_total');";
  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__);
  }

  $row = $result->fetchrow();
  $is_fin_trimestre = $row[0];

  /* Est-ce la fin du mois ? */
  $sql = "SELECT * FROM isFinMois('$date_total');";
  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__);
  }

  $row = $result->fetchrow();
  $is_fin_mois = $row[0];

  if ($is_fin_annee == 't')
    $freq = 4;
  elseif($is_fin_semestre == 't')
  $freq = 3;
  elseif($is_fin_trimestre == 't')
  $freq = 2;
  elseif($is_fin_mois == 't')
  $freq = 1;
  else
    $freq = 0;

  $count = 0;

  incLevel();

  if ($freq > 0) {
    /* Prélèvement des frais */
    $type_operation = 50;
    //$sql = "SELECT * FROM PreleveFraistenueCpt($freq, '$date_total', $type_operation, '".SENS_CREDIT."');";
    //AT-40 : recupere la valeaur champ preleve frais pour les groupes solidaires (oui = 't' ou non = 'f')
    $agc_data = getAgenceDatas($global_id_agence);
    $appli_frais_gs = $agc_data["applique_frais_tenue_cpte_gs"];
    $sql = "SELECT * FROM PreleveFraistenueCpt($freq, '$date_total', $type_operation, '".SENS_CREDIT."', '$appli_frais_gs');";
    $result = $db->query($sql);

    if (DB::isError($result)) {
      $dbHandler->closeConnection(false);
      signalErreur(__FILE__,__LINE__,__FUNCTION__);
    }

    /* Construction des infos pour le rapport batch et pour les extraits */
    $count = $result->numrows();
    while ($row = $result->fetchrow(DB_FETCHMODE_ASSOC)) {
      array_push($frais_tenue_cpte, $row);
    }
  }

  $dbHandler->closeConnection(true);
  affiche(sprintf(_("OK. Frais de tenue prélevés sur %s comptes"),$count), true);
  decLevel();
  affiche(_("Prélèvements des frais de tenue terminés !"));

  return new ErrorObj(NO_ERR);
}


/**
 * Prélève les frais d'abonnement aux services lorsqu'ils sont dus
 * @return ErrorObj
 */
function prelevement_frais_forfaitaires_mensuels_abonnement() {
  global $dbHandler, $global_id_agence, $date_total;

  affiche (_("Prelevement des frais d'abonnement aux services ..."));

  $db = $dbHandler->openConnection();
  $global_id_agence = getNumAgence();

  /* Est-ce la fin du mois ? */
  $sql = "SELECT * FROM isFinMois('".$date_total."');";
  $result = $db->query($sql);
  
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__);
  }

  $row = $result->fetchrow();
  $is_fin_mois = $row[0];

  $count = 0;

  incLevel();

  if ($is_fin_mois == 't') {

    //ticket AT-61 : query specifique pour le type services : SMS banking
    $sql_sms_banking = "SELECT * FROM ad_abonnement WHERE id_ag=".$global_id_agence." AND deleted='f' AND id_service = 1  ORDER BY date_creation ASC;";
    $result_sms_banking = $db->query($sql_sms_banking);

    if (DB::isError($result_sms_banking)) {
      $dbHandler->closeConnection(false);
      signalErreur(__FILE__,__LINE__,__FUNCTION__);
    }

    // Prélève frais forfaitaires mensuels SMS
    $type_operation_sms = 181;
    /* Construction des infos pour le rapport batch et pour les extraits */
    while ($row_sms_banking = $result_sms_banking->fetchrow(DB_FETCHMODE_ASSOC)) {
        $erreur_sms_banking = preleveFraisAbonnement('SMS_MTH', $row_sms_banking['id_client'], $type_operation_sms);

        if ($erreur_sms_banking->errCode == NO_ERR) {
            $count++;
        }
    }

    //ticket AT-61 : query specifique pour le type services : E-statement
    $sql_estatement = "SELECT * FROM ad_abonnement WHERE id_ag=".$global_id_agence." AND deleted='f' AND id_service = 2  ORDER BY date_creation ASC;";
    $result_estatement = $db->query($sql_estatement);

    if (DB::isError($result_estatement)) {
      $dbHandler->closeConnection(false);
      signalErreur(__FILE__,__LINE__,__FUNCTION__);
    }
    // Prélève frais forfaitaires mensuels ESTATEMENT
    $type_operation_estat = 186;
    /* Construction des infos pour le rapport batch et pour les extraits */
    while ($row_estatement = $result_estatement->fetchrow(DB_FETCHMODE_ASSOC)) {
        $erreur_estatement = preleveFraisAbonnement('ESTAT_MTH', $row_estatement['id_client'], $type_operation_estat);

        if ($erreur_estatement->errCode == NO_ERR) {
            $count++;
        }
    }
  }

  $dbHandler->closeConnection(true);
  affiche(sprintf(_("OK. Frais d'abonnement aux services prélevés sur %s comptes"), $count), true);
  decLevel();
  affiche(_("Prélèvements des frais d'abonnement aux services terminés !"));

  return new ErrorObj(NO_ERR);
}

/**
 * @desc Régularise les frais en attente pour les comptes dont les soldes sont devenus suffissants
 * @author  papa
 * @since 2.8
 * @return ErrorObj 0 si pas erreur sinon le code d'erreur rencontrée
 */
function traiteFraisAttente() {
  global $dbHandler, $global_monnaie;
  global $global_id_agence, $global_mouvements_epargne, $frais_tenue_cpte;
  global $date_total;

  $db = $dbHandler->openConnection();
  $global_id_agence = getNumAgence();
  affiche (_("Prélèvement des frais en attente ..."));
  incLevel();

  //le nombre de prélèvement
  $count = 0;

  // Récupération des fras en attente
  $sql = "SELECT * FROM ad_frais_attente;";
  $result = executeDirectQuery($sql);

  if ($result->errCode == NO_ERR) {
    // soldes disponibles des comptes traités
    $solde_dispo = array();

    // Liste des frais en attentes
    $liste_frais_attente = $result->param;
    foreach($liste_frais_attente as $key=>$frais_attente) {
      // Numéro du compte
      $num_compte = $frais_attente['id_cpte'];
      $date_frais = $frais_attente['date_frais'];
      $type_frais = $frais_attente['type_frais'];
      $ACC = getAccountDatas($num_compte);

      // Si le solde disponible du compte n'a pas encore été récupéré
      if (!isset($solde_dispo[$num_compte]))
        $solde_dispo[$num_compte]['solde_dispo'] = getSoldeDisponible($num_compte);

      // Calcul TVA sur frais attente si type_frais = 50 #804
      $mnt_tva = 0;
      if ($frais_attente['type_frais'] == 50){
        $typeOperation_tva = 50;
        $taxesOperation = getTaxesOperation($typeOperation_tva);
        $details_taxesOperation = $taxesOperation->param;
        if (sizeof($details_taxesOperation) > 0){
          $mnt_tva = $frais_attente['montant'] * $details_taxesOperation[1]['taux'];
        }
      }

      // Si le solde disponible du compte permet le remboursement intégral des frais + tva sur frais si necessaire
      if ($solde_dispo[$num_compte]['solde_dispo'] >= ($frais_attente['montant'] + $mnt_tva)) {
        $erreur = paieFraisAttente($num_compte, $frais_attente['type_frais'], $frais_attente['montant'], $global_mouvements_epargne);
        if ($erreur->errCode != NO_ERR)
          return $erreur;

        // diminution du solde disponible
        $solde_dispo[$num_compte]['solde_dispo'] -= ($frais_attente['montant'] + $mnt_tva);

        array_push($frais_tenue_cpte, array("num_cpte_frais"=>$ACC['num_complet_cpte'], "interet_frais"=>$frais_attente['montant'],
                                            "id_titulaire_frais"=>$ACC['id_titulaire'],"solde_initial_frais"=>$ACC['solde'],"devise_frais"=>$ACC['devise']));

        //Suppression dans la table des frais en attente
        $sql = "DELETE FROM ad_frais_attente WHERE id_cpte=$num_compte AND date(date_frais)=date('$date_frais') AND type_frais=$type_frais;";
        $result = executeDirectQuery($sql);
        if ($result->errCode != NO_ERR)
          return new ErrorObj($result->errCode);

        $count++;
      }
    }
  } else {
    $dbHandler->closeConnection(false);
    return new ErrorObj($result->errCode);
  }

  $dbHandler->closeConnection(true);
  affiche(sprintf(_("OK. Frais en attente prélevés sur %s comptes"),$count), true);
  decLevel();
  affiche(_("Prélèvement frais en attente terminé !"));
  return new ErrorObj(NO_ERR);
}

/**********
* Clôture les comptes à terme échus (DAT, CAT) actifs expirant à la date du batch
* Les intérêts ont été calculés par arrete_compte. La fonction ne s'occupe que de la clôture ou de la prolongation
* @author
* @since 2.3
* @param date $date_work la date du batch
* @return array tableau associatif de la liste des comptes à terme actifs expirant à la date $date_work
*/
function clotureComptesEchus($date_work) {
  global $global_mouvements_epargne, $global_mouvements_attente_epargne, $cat_arretes;
  global $dbHandler;

  $count_ferme = 0;
  $count_prolonge = 0;
  $count_reconduit = 0;
  $comptes_echus = array();

  affiche(_("Clôture des comptes à terme ..."));
  incLevel();

  /* liste des comptes à terme arrivés à expiration  */
  $comptes_echus = getComptesTermeEchus($date_work);
 // debug($comptes_echus);
  
  foreach($comptes_echus as $key=>$value) {
    $id_cpte = $value["id_cpte"];
    $dataCpteReconduit = Null ;

    if ($value["dat_prolongation"] == "f") {
      debug(_("On ne va pas prolonger le compte à terme n°")." $id_cpte ... ");
            
      $erreur = clotureCompteTerme($id_cpte, $global_mouvements_epargne, $global_mouvements_attente_epargne, $date_work);
      if ($erreur->errCode != NO_ERR) {
          return $erreur;
      }

      $RETCLOTURE = $erreur->param;

      // Fonction qui prélève les impot sur les DAT arrivant à échéance.
      $myErr1 = prelevementTaxDat($id_cpte,null,$global_mouvements_epargne);
      if ($myErr1->errCode != NO_ERR)
      {
        return $myErr1;
      }

      $ACC = getAccountDatas($id_cpte);
      // Extraits de compte relatifs à la clôture du compte à terme

      if ($RETCLOTURE['attente'] == false) {
        $count_ferme++;

        /* Compte de virement du solde à la clôture */
        $id_cpte_vir =  $RETCLOTURE['cpte_virement'];
        $InfoCpteVir = getAccountDatas($id_cpte_vir);
        
          /* Constructions des données du rapport compte rendu batch */
        $value['action'] = _("Clôturé");
        $value['solde_cloture'] = $RETCLOTURE['solde_cloture']; /* Solde de clôture du compte */
        $value['destination'] = $InfoCpteVir['num_complet_cpte'];
        $cat_arretes[$value["id_cpte"]] = $value;
      }

    } else if ($value["dat_prolongation"] == "t") {
      debug("On va prolonger le compte à terme n° $id_cpte ... ");
      $erreur = prolongeCompteTerme($id_cpte);
      if ($erreur->errCode != NO_ERR)
        return $erreur;
      $count_prolonge++;

      /* Constructions des données du rapport compte rendu batch */
      $value['action'] = _("Soldé");
      $value['solde_cloture'] = $erreur->param['solde_arrete'];

      $cat_arretes[$value["id_cpte"]] = $value;

      $id_cpte = $value['id_cpte'];
    }
  }
  affiche(sprintf(_("OK. %s comptes à terme ont été clôturés, %s comptes à terme ont été prolongés clôturées,%s comptes DAT ont été reconduits"),$count_ferme,$count_prolonge,$count_reconduit), true);

  decLevel();
  affiche(_("Clôture des comptes à terme terminé !"));

}


/**********
* Cloture et reconduit les comptes d'épargne à la source actifs expirant à la date du batch
* Les intérêts ont été calculés par arrete_compte(). La fonction ne s'occupe que de la reconduction
* @author Ibou Ndiaye
* @since 3.2
* @param date $date_work la date du batch
* @return array tableau associatif de la liste des comptes à terme actifs expirant à la date $date_work
*/
function reconduireComptesEpSource($date_work) {
  global $global_mouvements_epargne, $global_mouvements_attente_epargne, $es_arretes;
  global $dbHandler, $global_id_agence;

  $count_es_ferme = 0;
  $comptes_echus = array();
	$db = $dbHandler->openConnection();
  affiche(_("Reconduction des épargnes à la source ..."));
  incLevel();
  /* liste des comptes d'épargne à la source arrivés à expiration  */
  $comptes_echus = getComptesEpSourceEchus($date_work);
  foreach($comptes_echus as $key=>$value) {
  $id_cpte = $value["id_cpte"];
  	$erreur = clotureCompteTerme($id_cpte, $global_mouvements_epargne, $global_mouvements_attente_epargne, $date_work);
  	if ($erreur->errCode != NO_ERR)
  		return $erreur;
  	$RETCLOTURE = $erreur->param;
  	$ACC = getAccountDatas($id_cpte);
  	// Extraits de compte relatifs à la clôture du compte d'épargne à la source
  	if ($RETCLOTURE['attente'] == false) {
  		$count_es_ferme++;
  		/* Compte de virement du solde à la clôture */
  		$id_cpte_vir =  $RETCLOTURE['cpte_virement'];
  		$InfoCpteVir = getAccountDatas($id_cpte_vir);
  		/* Constructions des données du rapport compte rendu batch */
  		$value['action'] = _("Reconduit");
  		$value['solde_cloture'] = $RETCLOTURE['solde_cloture']; /* Solde de clôture du compte */
 		$value['destination'] = $InfoCpteVir['num_complet_cpte'];
  		$es_arretes[$id_cpte] = $value;
  	}
  }
  /* recalculer les dates de fin cycle pour les produits d'épargne arrivant en terme*/
	 $sql="SELECT id, ep_source_date_fin, terme from adsys_produit_epargne WHERE id_ag = $global_id_agence AND classe_comptable = 6 AND  ep_source_date_fin = '$date_work' ";
      $result = $db->query($sql);
		  if (DB::isError($result))
		    erreur("reconduireComptesEpSource()", $result->getMessage());
	 while ($row = $result->fetchrow(DB_FETCHMODE_ASSOC)) {
  	 $terme = $row["terme"];
	   $id_prod_ep = $row["id"];
	   $nouvDateFinEpSource = calculDateDureeMois($date_work, $terme);
	   $sql1="UPDATE adsys_produit_epargne SET ep_source_date_fin = '$nouvDateFinEpSource' WHERE id_ag = $global_id_agence AND id = $id_prod_ep ";
     $result1 = $db->query($sql1);
		 if (DB::isError($result1))
		    erreur("reconduireComptesEpSource()", $result1->getMessage());
		 /* Réouvrir le compte pour la reconduction et mettre à jour les dates de fin cycle pour les comptes d'épargne */
		 $sql2="UPDATE ad_cpt SET etat_cpte = 1, dat_date_fin = '$nouvDateFinEpSource', solde_calcul_interets = 0, interet_annuel = 0 WHERE id_ag = $global_id_agence AND id_prod = $id_prod_ep ";
     $result2 = $db->query($sql2);
		 if (DB::isError($result2))
		    erreur("reconduireComptesEpSource()", $result2->getMessage());
	 }

	$dbHandler->closeConnection(true);

  affiche(sprintf(_("OK. %s épargnes à la source reconduites"),$count_es_ferme), true);
  decLevel();
  affiche(_("Reconduction des comptes d'épargnes à la source terminée!"));
  return new ErrorObj(NO_ERR);

}

/**
 * Prélève les intérets débiteurs sur tous les comptes clients
 * Cette fonction appelle une fonction compilée dans la base de données
 */
function preleveInteretsDebiteurs() {
  global $dbHandler;
  global $date_total;
  global $frais_int_debiteurs;
  global $global_id_agence;

  affiche(_("Prélèvement des intérets débiteurs ..."));
  incLevel();

  // On récupère le numéro du compte au crédit de l'opération 471
  $myErr = getDetailsOperation(471);
  if ($myErr->errCode != NO_ERR)
    return $myErr;
  $OP = $myErr->param;
  $cpte_credit = $OP['credit']["compte"];

  /* Prélèvement des intérêts et Construction des infos pour le rapport compte rendu batch  */
  $db = $dbHandler->openConnection();
  $sql = "SELECT * from preleveinteretsdebiteurs('$date_total', '$cpte_credit')";

  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__);
  }

  /* Construction des infos pour les extraits  */
  $nb_preleve = $result->numrows();
  while ($row = $result->fetchrow(DB_FETCHMODE_ASSOC)) {
    array_push($frais_int_debiteurs, $row);
  }

  affiche(_("OK"), true);
  decLevel();
  affiche(" ".sprintf(_("Prélèvement des intérets débiteurs terminé sur %s comptes"),$nb_preleve));
  $dbHandler->closeConnection(true);
  return new ErrorObj(NO_ERR);

}

/**
 * Vérifie la validités des mandats accordés
 * @return ErrorObj
 */
function traite_mandats() {
  global $dbHandler,$global_id_agence;
  $db = $dbHandler->openConnection();
  $global_id_agence = getNumAgence();
  affiche(_("Traitement des mandats..."));
  incLevel();

  $WHERE = array('date_exp' => date("Y/m/d"),'id_ag'=>$global_id_agence);

  $sql = buildSelectQuery('ad_mandat', $WHERE);

  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__);
  }

  $count = 0;

  while ($row = $result->fetchrow(DB_FETCHMODE_ASSOC)) {
    $DATA = array('valide' => 'f');
    $WHERE = array('id_mandat' => $row['id_mandat'],'id_ag' =>$global_id_agence);
    $sql = buildUpdateQuery('ad_mandat', $DATA, $WHERE);

    $result1 = $db->query($sql);
    if (DB::isError($result1)) {
      $dbHandler->closeConnection(false);
      signalErreur(__FILE__,__LINE__,__FUNCTION__);
    }
    $count++;
  }

  affiche(sprintf(_("OK (%s mandats ont expirés)"),$count), true);
  decLevel();
  affiche(_("Traitements des mandats terminés !"));

  $dbHandler->closeConnection(true);
  return new ErrorObj(NO_ERR);
}

/**
  * Vérifie la validité des découvert autorisés.
  *
  * Si une période de validité du découvert est configurée au niveau du produit d'épargne
  * et que cette période est dépassée pour un découvert octroyé à un client,
  * son découvert autorisé est alors automatiquement annulé.
  *
  * @author Antoine Delvaux
  * @since 2.6
  * @param date $a_date_work La date à laquelle la fonction est appelée (date d'exécution du batch)
  * @return ErrorObj
  */
function invalideDecouverts($a_date_work) {
  global $dbHandler,$global_id_agence;

  affiche(_("Vérification validité des découverts ..."));
  incLevel();

  $db = $dbHandler->openConnection();
  $global_id_agence=getNumAgence();
  // Annulation des découverts octroyés si la période d'utilisation est plus longue qu'autorisée.
  $sql = "UPDATE ad_cpt SET decouvert_date_util = NULL, decouvert_max = 0
         FROM adsys_produit_epargne AS prod
         WHERE ad_cpt.id_ag=$global_id_agence AND ad_cpt.id_prod = prod.id
         AND prod.decouvert_validite > 0
         AND ad_cpt.decouvert_date_util < date(date('".$a_date_work."') - (prod.decouvert_validite::text || ' month')::interval);";

  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__,__LINE__,__FUNCTION__, $result->getMessage());
  }
  $nb_decou_annules = $db->affectedRows();

  if ($nb_decou_annules > 1) {
    affiche(_("OK").", $nb_decou_annules "._("découverts annulés."), true);
  } else if ($nb_decou_annules == 1) {
    affiche(_("OK").", $nb_decou_annules "._("découvert annulé."), true);
  } else {
    affiche(_("OK, tous les découverts restent valables."), true);
  }

  decLevel();
  affiche(_("Vérification découverts terminée !"));
  $dbHandler->closeConnection(true);
  return new ErrorObj(NO_ERR);
}

/**
 * Traite les ordres permanents arrivant à échéance
 * @since 3.0
 * @param date $a_date_work La date à laquelle la fonction est appelée (date d'exécution du batch)
 * @author Pierre Timmermans - Antoine Delvaux
 */
function traite_ordres_permanents($a_date_work) {
  global $dbHandler, $error, $adsys, $ordres_traites;
  global $global_mouvements_epargne, $global_mouvements_attente_epargne;
  $count_OK = 0;
  $count_pasOK = 0;
  $arr_solde_cpte = array();

  affiche(_("Execution des ordres permanents"));
  incLevel();
  $db = $dbHandler->openConnection();
  $sql = "SELECT * FROM ad_ord_perm WHERE date_proch_exe <= '".$a_date_work."' AND actif = true ";
  $sql .= "AND (date_fin >= '".$a_date_work."' OR date_fin is null)";
  $result = executeDirectQuery($sql);
  if ($result->errCode != NO_ERR) {
    $dbHandler->closeConnection(false);
    erreur("traite_ordres_permanents", $result->getMessage().' - '.$result->param);
  } else {
  	foreach ($result->param as $ordre) {
      //tester si le compte n'est pas bloqué et que son solde disponible est suffisant pour prélever le montant de l'odre
 	    //le test sur le solde disponible seulement suffit car la fonction getSoldeDisponible teste si le compte est bloqué
 	    $etat_cpte = getEtatCpte($ordre['cpt_to']);
 	    $etat_cpte_emetteur = getEtatCpte($ordre['cpt_from']);
 	    //ticket 472
 	    if(!isset($arr_solde_cpte[$ordre['cpt_from']])) {
 	    	$arr_solde_cpte[$ordre['cpt_from']] = getSoldeDisponible($ordre['cpt_from']);
 	    }
 	    
 	    //if((getSoldeDisponible($ordre['cpt_from']) > $ordre['montant']) && ($etat_cpte != 3) && ($etat_cpte != 2)){
 	    if(($arr_solde_cpte[$ordre['cpt_from']] > $ordre['montant']) && ($etat_cpte != 3) && ($etat_cpte != 2) && ($etat_cpte_emetteur != 7)){
 	        $result = executeOrdPermanent($ordre['id_ord'], $a_date_work, $global_mouvements_epargne, true);
 	       if ($result->errCode != NO_ERR) {
 	          $dbHandler->closeConnection(false);
 	          erreur("traite_ordres_permanents", $error[$result->errCode].' - '.$result->param);
 	       }
 	       if ($result->param) {
 	          $count_OK++;
 	          $ordre_traite['statut'] = _("Ok");
 	          //ticket 472
 	          $arr_solde_cpte[$ordre['cpt_from']] = ($arr_solde_cpte[$ordre['cpt_from']] - $ordre['montant']);
         
 	       } else {
 	          $count_pasOK++;
 	          $ordre_traite['statut'] = _("Erreur");
 	       }
         
 	       // Enregistrement des infos pour compte rendu du batch  
 	       $ordre_traite['num_cpte_src'] = $ordre['cpt_from'];
 	       $ordre_traite['num_cpte_dest'] = $ordre['cpt_to'];
 	       $ordre_traite['montant'] = $ordre['montant'];
 	       $ordre_traite['frais'] = $ordre['frais_transfert'];
 	       $ordre_traite['periodicite'] = $adsys['adsys_periodicite_ordre_perm'][$ordre['periodicite']];
 	       $ordre_traite['intervale'] = $ordre['interv'];
 	       $ordres_traites[$ordre['id_ord']] = $ordre_traite;
 	    } // dans le else il sera question de mettre l'ordre en attente avec le #1670 
    }

    $dbHandler->closeConnection(true);
  }
  affiche(sprintf(_("OK. %s ordres permanent executés avec succès, %s executés avec erreur"),$count_OK,$count_pasOK), true);
  return new ErrorObj(NO_ERR);
}


function cloture_ordre_permanent($date_batch){
  global $dbHandler, $error;
  global $global_id_agence;
  global $global_id_client;

  affiche ( _ ( "Démarre le traitement de cloture des ordres permanents..." ) );
  incLevel ();
  $db = $dbHandler->openConnection ();
  $sql = "SELECT c.id_client,p.*
from ad_ord_perm p,ad_cpt t, ad_cli c
where p.cpt_from = t.id_cpte and t.id_titulaire = c.id_client and date_fin = date('$date_batch') and etat_clos = 'f' and actif = 't' ;";
  $result = $db->query ( $sql );

  if (DB::isError ( $result )) {
    $dbHandler->closeConnection ( false );
    erreur ( "cloture_ordre_permanent", $result->getUserInfo () );
  }
  $count = $result->numrows ();
  while ( $row = $result->fetchrow ( DB_FETCHMODE_ASSOC ) ) {
    $id_ord = $row['id_ord'];
    $mnt_ord = $row['montant'];
    $id_client = $row['id_client'];
    $sql_update = "UPDATE ad_ord_perm set etat_clos = 't', date_clos=date(now()) where id_ord = $id_ord";
    $result_update = $db->query ( $sql_update );
    if (DB::isError ( $result_update )) {
      $dbHandler->closeConnection ( false );
      erreur ( "update_etat_cloture", $result_update->getUserInfo () );
    }
    $data_agc = getAgenceDatas($global_id_agence);
    $data_cli = getClientDatas($id_client);
    if ($data_agc['quotite'] == 't'){
      $mnt_qutotite_dispo = $data_cli['mnt_quotite'];
      $quotite_dispo_apres = $mnt_qutotite_dispo + $mnt_ord;
      $DATA_QUOTITE = array();
      $DATA_QUOTITE["id_client"] = $row['id_client'];
      $DATA_QUOTITE["quotite_avant"] = $mnt_qutotite_dispo;
      $DATA_QUOTITE["quotite_apres"] = $quotite_dispo_apres;
      $DATA_QUOTITE["mnt_quotite"] = $quotite_dispo_apres;
      $DATA_QUOTITE["date_modif"] = date('r');
      $DATA_QUOTITE["raison_modif"] = 'Ordres permanents';
      $ajout_quotite =ajouterQuotite($DATA_QUOTITE);


      $DATA_QUOTITE_UPDATE = array();
      $DATA_QUOTITE_WHERE = array();
      $DATA_QUOTITE_UPDATE["mnt_quotite"] = $quotite_dispo_apres;
      $DATA_QUOTITE_WHERE['id_client'] = $row['id_client'];
      $update_client = update_quotite_client($DATA_QUOTITE_UPDATE,$DATA_QUOTITE_WHERE);
    }
  }
  // $count = $result->param[0]['traitecomptesdormants'];
  $dbHandler->closeConnection ( true );
  affiche ( sprintf ( _ ( "OK (%s Ordres permanents ont été fermé )" ), $count ), true );
  decLevel ();
  affiche ( _ ( "Traitement des ordres permanents terminé" ) );
  return $result;
}


/**
 * Fonction de gestion des comptes dormants ( inactifs)
 * 
 * @param DATE $_date_batch        	
 * @return $objError
 */
function traiteComptesDormants($_date_batch) {
	global $dbHandler, $error;
	global $global_id_agence;
	global $global_mouvements_epargne;
	
	affiche ( _ ( "Démarre le traitement des comptes dormants..." ) );
	incLevel ();
	$db = $dbHandler->openConnection ();
	$sql = " SELECT * FROM traiteComptesDormants('$_date_batch',$global_id_agence);";
	$result = $db->query ( $sql );
	
	if (DB::isError ( $result )) {
		$dbHandler->closeConnection ( false );
		erreur ( "traiteComptesDormants", $result->getUserInfo () );
	}
	
	$count = $result->numrows ();	
	while ( $row = $result->fetchrow ( DB_FETCHMODE_ASSOC ) ) {
		$myErr = deactiverCompteDormant ( $row, $global_mouvements_epargne );
		if ($myErr->errCode != NO_ERR) {
			$dbHandler->closeConnection ( false );
			return $myErr;
		}	
		// #357 : set num_cpte comptable in ad_cpt pour les comptes a etat dormant :
		$id_cpte = $row['id_cpte'];		
		$myErr = setNumCpteComptableForCompte($id_cpte, $db);
	}
	// $count = $result->param[0]['traitecomptesdormants'];
	$dbHandler->closeConnection ( true );
	affiche ( sprintf ( _ ( "OK (%s Comptes dormants ont été bloqués )" ), $count ), true );
	decLevel ();
	affiche ( _ ( "Traitement des comptes dormants terminé" ) );
	return $result;
}

/**
 * Function qui preleve le frais transactionnel SMS si le compte appartient à un client qui est abonné au service SMS
 * Cette function est une adaption du function preleveFraisTransactionnelSMS qui se trouve dans tarification.php
 * @param $array_compte
 */
function preleveFraisTransactionnelSMSEpargne($array_compte){

  $type_frais = 'SMS_FRAIS';
  $type_operation = 188;
  $montant_transaction = 0;
  $type_function = 212;
  $arr_type_operation = array();

  $opt_versement_int_compte_epargne = 40;

  $type_opt = getListeTypeOptPourPreleveFraisSMS(true);
  foreach ($type_opt as $key => $value) {
      foreach ($value as $opt => $valeur) {
            $arr_type_operation[] = $valeur;
      }
  }

  $getFrais = getTarificationDatas($type_frais);
  if(empty($getFrais)){
    return true;
  }

  if(!empty($array_compte)){
    foreach ($array_compte as $k => $val){
      if (isset($val['num_cpte'])){
        if (in_array($opt_versement_int_compte_epargne, $arr_type_operation) && is_array($client = checkClientAbonnement($val['num_cpte'])) && $cpt_epargne = get_comptes_epargne($client["id_client"]) ){
          foreach ($cpt_epargne as $id_cpte => $values){
            if ($val['num_cpte'] == $id_cpte) {

              $myErr = checkTypeOperationFraisSMSsensCredit($type_operation);
              if($myErr->errCode != NO_ERR){
                affiche ( _ ( "ERREUR: le type opération 'Frais forfaitaires du service SMS' n'a pas été paramétrer !" ) );
              }

              preleveFraisAbonnement($type_frais, $client["id_client"], $type_operation, $montant_transaction,  $type_function);

            }
          }
        }
      }
    }
  }

  return true;
}

/**
 * Fonction qui envoie le message vers le broker si le compte client appartient à un client Mobile et si l'operation comptable se trouve dans la table adsys_param_mouvement
 * @param $array_compte
 */
function envoiSMSMouvementEpargne($array_compte) {

    $array_opt_arret_comptes = array(40); // 40 -> versement_int_compte_epargne

    if(!empty($array_compte)){
        foreach ($array_compte as $k => $val){
            if (isset($val['num_cpte'])){
                if (is_array($client = checkClientAbonnement($val['num_cpte'])) && $cpt_epargne = get_comptes_epargne($client["id_client"]) ){
                    foreach ($cpt_epargne as $id_cpte => $values){
                        if ($val['num_cpte'] == $id_cpte) {
                            $listeOptEnvoiSMS = array();
                            $typeOptEnvoiSMS = getListeTypeOptPourPreleveFraisSMS();
                            foreach ($typeOptEnvoiSMS as $key => $value) {
                                foreach ($value as $item => $typeOpt) {
                                    array_push($listeOptEnvoiSMS, $typeOpt);
                                }
                            }

//                            if (in_array($opt_versement_int_compte_epargne, $listeOptEnvoiSMS)) {
                            if (count(array_intersect($array_opt_arret_comptes, $listeOptEnvoiSMS)) > 0 ) {

                                global $code_imf,$MSQ_HOST, $MSQ_PORT, $MSQ_USERNAME, $MSQ_PASSWORD, $MSQ_VHOST;
                                global $MSQ_EXCHANGE_NAME, $MSQ_EXCHANGE_TYPE, $MSQ_QUEUE_NAME_MOUVEMENT, $MSQ_ROUTING_KEY_MOUVEMENT;

                                $datas = MouvementMSQPublisher::getMouvementDataArreteCompte($val['id_mouvement'], $val['solde'], $val['date_transaction']);

                                $rawMessage = array(
                                    'telephone' => $datas['telephone'],
                                    'langue' =>$datas['langue'],
                                    'date_transaction' => $datas['date_transaction'],
                                    'type_opt' => $datas['type_opt'],
                                    'sens' => $datas['sens'],
                                    'num_complet_compte' => $datas['num_complet_cpte'],
                                    'id_mouvement' => $datas['id_mouvement'],
                                    'id_ag' => $datas['id_ag'],
                                    'code_imf' => $code_imf,
                                    'libelle_ecriture' => $datas['libelle_ecriture'],
                                    'montant' => $datas['montant'],
                                    'devise' => $datas['devise'],
                                    'nom' => $datas['nom'],
                                    'prenom' => $datas['prenom'],
                                    'solde' => $datas['solde'],
                                    'intitule_compte' => $datas['intitule_compte'],
                                    'libelle_produit' => $datas['libelle_produit'],
                                    'date_ouvert' => $datas['date_ouvert'],
                                    'statut_juridique' => $datas['statut_juridique'],
                                    'id_client' => $datas['id_client'],
                                    'id_transaction' => $datas['id_transaction'],
                                    'ref_ecriture' => $datas['ref_ecriture'],
                                );

                                //--------- Instantiates the publisher ---------------//
                                $mouvementPublisher = new MouvementMSQPublisher(
                                    $MSQ_HOST,
                                    (int)$MSQ_PORT,
                                    $MSQ_USERNAME,
                                    $MSQ_PASSWORD,
                                    $MSQ_QUEUE_NAME_MOUVEMENT,
                                    $MSQ_ROUTING_KEY_MOUVEMENT,
                                    $MSQ_EXCHANGE_NAME,
                                    $MSQ_VHOST
                                );

                                $mouvementPublisher->executePublisher($rawMessage);
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Fonction qui envoi de sms pour l'array $global_mouvements_epargne
 * @param $array_epargne
 */
function envoiSMSMouvementClotureCompte($array_epargne){
    if (!empty($array_epargne)) {
        foreach ($array_epargne as $k => $val){
            if (isset($val['cpte_interne_cli'])){
                if (is_array($client = checkClientAbonnement($val['cpte_interne_cli'])) && $cpt_epargne = get_comptes_epargne($client["id_client"]) ) {

                    $listeOptEnvoiSMS = array();
                    $typeOptEnvoiSMS = getListeTypeOptPourPreleveFraisSMS();
                    foreach ($typeOptEnvoiSMS as $key => $value) {
                        foreach ($value as $item => $typeOpt) {
                            array_push($listeOptEnvoiSMS, $typeOpt);
                        }
                    }

                    if (in_array($val['type_operation'], $listeOptEnvoiSMS)) {

                        global $code_imf,$MSQ_HOST, $MSQ_PORT, $MSQ_USERNAME, $MSQ_PASSWORD, $MSQ_VHOST;
                        global $MSQ_EXCHANGE_NAME, $MSQ_EXCHANGE_TYPE, $MSQ_QUEUE_NAME_MOUVEMENT, $MSQ_ROUTING_KEY_MOUVEMENT;

                        // get the necessary data to send as message to the broker
                        $cpte_interne_cli = $val['cpte_interne_cli'];
                        $montant = $val['montant'];
                        $date_valeur = date('Y-m-d', strtotime(str_replace('/', '-', $val['date_valeur'])));

                        $datas = MouvementMSQPublisher::getMouvementDataClotureCompte($cpte_interne_cli, $montant, $date_valeur);

                        $rawMessage = array(
                            'telephone' => $datas['telephone'],
                            'langue' =>$datas['langue'],
                            'date_transaction' => $datas['date_transaction'],
                            'type_opt' => $datas['type_opt'],
                            'sens' => $datas['sens'],
                            'num_complet_compte' => $datas['num_complet_cpte'],
                            'id_mouvement' => $datas['id_mouvement'],
                            'id_ag' => $datas['id_ag'],
                            'code_imf' => $code_imf,
                            'libelle_ecriture' => $datas['libelle_ecriture'],
                            'montant' => $datas['montant'],
                            'devise' => $datas['devise'],
                            'nom' => $datas['nom'],
                            'prenom' => $datas['prenom'],
                            'solde' => $datas['solde'],
                            'intitule_compte' => $datas['intitule_compte'],
                            'libelle_produit' => $datas['libelle_produit'],
                            'communication' => $datas['communication'],
                            'tireur' => $datas['tireur'],
                            'donneur' => $datas['donneur'],
                            'numero_cheque' => $datas['numero_cheque'],
                            'date_ouvert' => $datas['date_ouvert'],
                            'statut_juridique' => $datas['statut_juridique'],
                            'id_client' => $datas['id_client'],
                            'id_transaction' => $datas['id_transaction'],
                            'ref_ecriture' => $datas['ref_ecriture'],
                        );

                        //--------- Instantiates the publisher ---------------//
                        $mouvementPublisher = new MouvementMSQPublisher(
                            $MSQ_HOST,
                            (int)$MSQ_PORT,
                            $MSQ_USERNAME,
                            $MSQ_PASSWORD,
                            $MSQ_QUEUE_NAME_MOUVEMENT,
                            $MSQ_ROUTING_KEY_MOUVEMENT,
                            $MSQ_EXCHANGE_NAME,
                            $MSQ_VHOST
                        );

                        // Executes publish process
                        $mouvementPublisher->executePublisher($rawMessage);
                    }
                }
            }
        }
    }
}

/**
 * Fonction principale traitant les opérations sur les comptes épargne dans le batch
 */
function traite_epargne() {

  global $date_jour, $date_mois, $date_annee;
  global $date_total;
  global $dbHandler,$error;
  global $global_id_his;
  global $global_mouvements_epargne,$global_mouvements_attente_epargne;


  affiche(_("Démarre le traitement de l'épargne ..."));
  incLevel();

  $db = $dbHandler->openConnection();

  //détermination des dates de travail
  $dates = array();
  $dates['today'] = $date_total;

  $date_fin_mois = date("d/m/Y", mktime(0, 0, 0, $date_mois + 1, 0, $date_annee));
  $dates['fin_mois'] = $date_fin_mois;

  //voir si on est à la fin d'un trimestre
  $trimestre = ceil($date_mois / 3);
  $date_fin_trimestre = date("d/m/Y", mktime(0, 0, 0, ($trimestre * 3) +1, 0, $date_annee));
  $dates['fin_trimestre'] = $date_fin_trimestre;

  //voir si on est à la fin d'un semestre
  $semestre = ceil($date_mois / 6);
  $date_fin_semestre = date("d/m/Y", mktime(0, 0, 0, ($semestre * 6) +1, 0, $date_annee));
  $dates['fin_semestre'] = $date_fin_semestre;

  //est-on à la fin de l'année ?
  $date_fin_annee = date("d/m/Y", mktime(0, 0, 0, 12, 31, $date_annee));
  $dates['fin_annee'] = $date_fin_annee;

  //ordres permanents
  $myErr = traite_ordres_permanents($date_total);
  if ($myErr->errCode != NO_ERR)
    erreur("batch.php",sprintf(_("Erreur lors du traitement des ordres permanents , code : %s, param : '%s'"), $myErr->errCode, $myErr->param));

  cloture_ordre_permanent($date_total);

  /* Mise à jour du solde de calcul des intérêts */
  update_solde_min($date_total);

  /* Calcul des intérêts a payer pour les depots/comptes à terme (DAT, CAT) */
  $myErr = calcul_interets_a_payer($date_total);
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors des calculs d’intérêts sur comptes d’épargne, code %s, param '%s'"), $myErr->errCode, $myErr->param));

  /* Calcul des intérêts pour tous les comptes à rémunérer (DAV, DAT, CAT, Epargne à la source et Autres dépôts) */
  $myErr = arrete_comptes($date_total);
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors de l'arrêté de comptes, code %s, param '%s'"), $myErr->errCode, $myErr->param));

  /* Mise à jour du solde de calcul des intérêts pour les épargnes à la source*/
 //updateSoldeInteretEpargneSource($date_total); -- commenté, désormais la fonction update_solde_min est utilisée pour faire le mm travail


  /* Clôture ou prolongation des comptes (DAT, CAT et épargnes à la source ) à terme arrivés en fin de période */
  $myErr = clotureComptesEchus($date_total);
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors de la clôture du compte à terme, code %s, param '%s'"), $myErr->errCode, $myErr->param));

	/* Reconduire les comptes d'épargnes à la source arrivés en fin de cycle */
  $myErr = reconduireComptesEpSource($date_total);
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors de la reconduction des comptes d'épargne à la source', code %s, param '%s'"), $myErr->errCode, $myErr->param));


  // Vérification validité des découverts
  $myErr = invalideDecouverts($date_total);
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors de la vérification de la validité des découverts, code %s, param '%s'"), $myErr->errCode, $myErr->param));

  // Prélèvement des intérets débiteurs
  $myErr = preleveInteretsDebiteurs();
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors du prélèvement des intérets débiteurs, code %s, param '%s'"), $myErr->errCode, $myErr->param));

  // Traitements sur les mandats
  $myErr = traite_mandats();
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors des traitements sur les mandats, code %s, param '%s'"), $myErr->errCode, $myErr->param));

  //Voir si un compte n'a pas des frais en attente et que son solde est devenu suffisant
  $myErr = traiteFraisAttente();
  if ($myErr->errCode != NO_ERR)
    erreur("batch.php", sprintf(_("Erreur lors des traitements des frais en attente, code : %s, param : '%s'"), $myErr->errCode, $myErr->param));
    
 $myErr = traiteComptesDormants($date_total);
  if ($myErr->errCode != NO_ERR)
    signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("Erreur lors des traitements COMPTES DORMANTS, code %s, param '%s'"), $myErr->errCode, $myErr->param));
  
  // Fix date comptable et date valeur
  overwrite_date_compta($global_mouvements_epargne);
  overwrite_date_compta($global_mouvements_attente_epargne);

  // On inscrit tous les mouvements dans la table historique
  $myErr = ajout_historique(212, NULL, NULL, NULL, date("r"), $global_mouvements_epargne, $global_mouvements_attente_epargne,$global_id_his);
  if ($myErr->errCode != NO_ERR) {
    $dbHandler->closeConnection(false);
    erreur(_("appel à")." ajout_historique", $error[$myErr->errCode].$myErr->param);
  }


  if(isMSQEnabled()) {
      envoiSMSMouvementClotureCompte($global_mouvements_epargne);
  }

  //si tout c'est bien passé libere la memoire
  unset($global_mouvements_epargne);
  unset($global_mouvements_attente_epargne);
  decLevel();

  $dbHandler->closeConnection(true);

  affiche(_("Traitement de l'épargne terminé !"));
}
?>