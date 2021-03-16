<?php

/**
 * Contrôle des accès aux menus d'ADbanking
 *
 * @package Systeme
 */

require_once 'lib/misc/VariablesSession.php';
require_once 'lib/misc/tableSys.php';
require_once 'lib/dbProcedures/epargne.php';
require_once 'lib/dbProcedures/credit.php';
require_once 'lib/dbProcedures/credit_lcr.php';
require_once 'lib/dbProcedures/client.php';
require_once 'lib/dbProcedures/cheque_interne.php';
require_once 'lib/dbProcedures/annulation_retrait_depot.php';
require_once 'lib/dbProcedures/engrais_chimiques.php';
require_once 'lib/misc/divers.php';
require_once ('lib/dbProcedures/budget.php');
require_once 'lib/dbProcedures/agence.php';

function check_fonction_11() {
  global $global_id_client;

  $DATA = getClientDatas($global_id_client);

  if ($DATA["statut_juridique"] == 1) {
    return true;
  } else {
    return false;
  }
}

function check_fonction_12() {
  global $global_id_client;

  $DATA = getClientDatas($global_id_client);

  if ($DATA["etat"] == 2) {
    return true;
  } else {
    return false;
  }
}

function check_fonction_78() { // Prolongation DAT
  global $global_id_client;

  $listeDAT = clientHasCompteATerme($global_id_client);
  if (is_array($listeDAT))
    return TRUE;
  else
    return FALSE;
}

function check_fonction_81() { // Recharge Carte Ferlo
  global $global_etat_client, $global_cpt_base_ouvert;
  return (($global_etat_client == 2) && ($global_cpt_base_ouvert == true));
}

function check_fonction_85() { // Retrait express
  global $global_etat_client, $global_cpt_base_ouvert, $global_retrait_bloque;
  return (($global_etat_client == 2) && ($global_retrait_bloque == false));
}

function check_fonction_86() { // Dépôt express
  global $global_etat_client, $global_cpt_base_ouvert, $global_depot_bloque;
  return (($global_etat_client == 2 || $global_etat_client == 7) && ($global_depot_bloque == false));
}

function check_fonction_205() { // Ouverture d'agence
  global $global_statut_agence;
  return ($global_statut_agence == 2);
}

function check_fonction_206() { // Fermeture d'agence
  global $global_statut_agence;
  return ($global_statut_agence == 1);
}

function check_fonction_211() { // Consolidation de données : fonction accessible qu'au siège
  return isSiege();
}

function check_fonction_474() { // Annulation des mouvements réciproques : fonction accessible qu'au siège
  return isSiege();
}

function check_fonction_212() { // Traitements fin de journée
  global $global_statut_agence;
  return ($global_statut_agence == 2);
}

function check_fonction_147() { // Remboursement d'un crédit
  global $global_id_client;
  global $global_etat_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $DOSS = getDossierClient($global_id_client);
  if (is_array($DOSS))
    while (list(, $doss) = each($DOSS))
      if (($doss['is_ligne_credit'] == 'f' or $doss['is_ligne_credit'] == '') and ($doss['etat'] == 5 || $doss['etat'] == 9 || $doss['etat'] == 13) and $doss['gs_cat'] != 2) {
        return ($global_etat_client == 2 || $global_etat_client == 7);
      }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if (($doss['is_ligne_credit'] == 'f' or $doss['is_ligne_credit'] == '') AND ($value['etat'] == 5 OR $value['etat'] == 9 OR $value['etat'] == 13)) {
      return ($global_etat_client == 2 || $global_etat_client == 7);
    }
  }

  return false;
}

/**
 * [148] : Accès au menu réalisation garantie
 *
 * La réalisation d'une garantie est possible si :
 * - le client a un crédit en cours qui n'est ni sain ni en perte
 * - le crédit est couvert par au moins une garantie à l'état 'Mobilisé'
 *
 *  @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_148() {
  global $global_id_client, $global_id_agence;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  $AG = getAgenceDatas($global_id_agence);

  if($AG['realisation_garantie_sain'] == 't'){
  	/* recupère l'id de l'état en perte */
  	$idEtatPerte = getIDEtatPerte();
  	/* Recherche des dossiers individuels déboursés ou en attente de Rééch/Moratoire ni en perte(ici on ajoute crédits sains voir #1859) */
  	$whereCl = " AND is_ligne_credit='f' or is_ligne_credit is null AND ((etat=5) OR (etat=7) OR (etat=14) OR (etat=15)) AND cre_etat != $idEtatPerte AND (gs_cat != 2 OR gs_cat IS NULL)";
  } else {
  	/* Recherche des dossiers individuels déboursés ou en attente de Rééch/Moratoire ni sain ni en perte */
        $whereCl = " AND is_ligne_credit='f' or is_ligne_credit is null  AND ((etat=5) OR (etat=7) OR (etat=14) OR (etat=15)) AND cre_etat > 1 AND (gs_cat != 2 OR gs_cat IS NULL)";
  }
  
  $DOSS = getIdDossier($global_id_client,$whereCl);

  /* Vérifier qu'il existe un dossier avec une garantie mobilisée */
  if (sizeof($DOSS)> 0)
    foreach($DOSS as $key=>$value) {
    	$liste_gars = getListeGaranties($value['id_doss']);

        if(!empty($liste_gars)) { // check for null
            foreach($liste_gars as $cle=>$valeur) {
                if ($valeur['etat_gar'] == 3) { //garantie mobilisée
                    return true;
                } elseif($value['gar_num_encours']>0 AND $value['cpt_gar_encours']==$valeur['gar_num_id_cpte_nantie']) {
                    //garantie numeraire à constituer
                    $CPT_GAR = getAccountDatas($valeur['gar_num_id_cpte_nantie']);
                    /* Solde disponible sur le compte de garantie */
                    $solde_disp = $CPT_GAR['solde'];
                    if ($solde_disp>0)  // Le compte de garantie doit avoir un solde
                        return true;
                }
            }
        }
    }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);

    foreach($dossiers_membre as $id_doss=>$value)
    {
        // Les dossiers doivent etre en souffrance pour que le menu realisation de garanties s'affiche
        if ($value['is_ligne_credit'] == 'f' and ($value['etat'] == 5 OR $value['etat'] == 9) and (($AG['realisation_garantie_sain'] == 'f' and $value['cre_etat'] > 1) || ($AG['realisation_garantie_sain'] == 't' and $value['cre_etat'] != $idEtatPerte)))
        {
          $liste_gars = getListeGaranties($id_doss);
          foreach($liste_gars as $cle=>$valeur)
          if ($valeur['etat_gar'] == 3)
            return true;
        }
    }
  }

  return false;
}

/**
  * [136] : Accès au menu Modification de l'échéancier de crédit
  *
  * Vérifie l'accès au menu Modification de l'échéancier de crédit.  Les conditions sont :
  * - que le client ait au moins un dossier de crédit avec état : fonds déboursés (etat = 5), en attente modification de la date de remboursement (etat 14), en attente approbation raccourcissement durée (etat 15)
  * - et état crédit SAIN (cre_etat = 1)
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_136() {

    global $global_id_client;

    $return_val = false;

    if(check_fonction_110() || check_fonction_137() || check_fonction_138() ||  check_fonction_139() || check_fonction_141() || check_fonction_142() || check_fonction_143() || check_fonction_144() || check_fonction_145()) {
        $return_val = true;
    }

    return $return_val;
}

/**
  * [92] : Accès au menu Retrait en déplacé
  *
  * Vérifie l'accès au menu Retrait en déplacé.  Les conditions sont :
  * - mode : multi-agence
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_92() {

    require_once('lib/misc/divers.php');
    
    $return_val = false;

    // Vérification si Multi-Agence
    if(isMultiAgence()) {
        $return_val = true;
    }

    return $return_val;
}

/**
  * [92] : Accès au menu Dépôt en déplacé
  *
  * Vérifie l'accès au menu Dépôt en déplacé.  Les conditions sont :
  * - mode : multi-agence
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_93() {

    require_once('lib/misc/divers.php');
    
    $return_val = false;

    // Vérification si Multi-Agence
    if(isMultiAgence()) {
        $return_val = true;
    }

    return $return_val;
}

/**
  * [193] : Accès au menu Opération en déplacé
  *
  * Vérifie l'accès au menu Opération en déplacé.  Les conditions sont :
  * - mode : multi-agence
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_193() {

    require_once('lib/misc/divers.php');
    
    $return_val = false;

    // Vérification si Multi-Agence
    if(isMultiAgence()) {
        $return_val = true;
    }

    return $return_val;
}

/**
  * [194] : Accès au menu Visualisation des opérations en déplacé
  *
  * Vérifie l'accès au menu Visualisation des opérations en déplacé.  Les conditions sont :
  * - mode : multi-agence
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_194() {

    require_once('lib/misc/divers.php');
    
    $return_val = false;

    // Vérification si Multi-Agence
    if(isMultiAgence()) {
        $return_val = true;
    }

    return $return_val;
}

/**
  * [213] : Accès au menu Traitements de nuit Multi-Agence
  *
  * Vérifie l'accès au menu Traitements de nuit Multi-Agence.  Les conditions sont :
  * - mode : multi-agence
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_213() {

    require_once('lib/misc/divers.php');
    
    $return_val = false;

    // Vérification si Multi-Agence
    if(isMultiAgence()) {
        $return_val = true;
    }

    return $return_val;
}

/**
  * [214] : Accès au menu Traitement compensation au siège
  *
  * Vérifie l'accès au menu Traitement compensation au siège.  Les conditions sont :
  * - mode : compensation au siège
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_214() {

    require_once('lib/misc/divers.php');

    $return_val = false;
    $AGC = getAgenceDatas(getNumAgence());

    // Vérification si la compensation se fait dans l'agence siège
    if(isCompensationSiege() && isCurrentAgenceSiege()) {
      if ((isset($AGC['traite_compensation_automatique']) && $AGC['traite_compensation_automatique'] == 'f')) { //AT-32 si ce n'est pas compensation au siege automatique par cron
        $return_val = true;
      }
    }

    return $return_val;
}

/**
  * [141] : Accès au menu Demande modification de la date de remboursement
  *
  * Vérifie l'accès au menu Demande modification de la date de remboursement.  Les conditions sont :
  * - que le client ait au moins un dossier de crédit avec état : fonds déboursés (etat = 5)
  * - et état crédit SAIN (cre_etat = 1)
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */

function check_fonction_141() {

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // On prend tous les dossiers individuels déboursés du client
  // [5] - "Fonds déboursés"
  $whereCl =" AND is_ligne_credit='f' AND (etat=5) AND (cre_etat=1) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier = getIdDossier($global_id_client, $whereCl);
  
  if (is_array($elt_dossier) && count($elt_dossier) > 0) {
      return true;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
      if ($value['etat'] == 5 && $value['cre_etat'] == 1) {
        return true;
      }
    }
  }

  return false;
}

/**
 * [142] : Accès au menu Approbation modification de la date de remboursement
 * Conditions d'accès : Le dossier individuel ou de groupe  doit être en attente modification de la date de remboursement (etat 14)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */

function check_fonction_142() {

  require_once 'lib/dbProcedures/historisation.php';

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels du client
  $whereCl=" AND is_ligne_credit='f' AND (etat = 14) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);

  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  
  $nbre_dcr_date_remb = 0;
  if(count(Historisation::getListDossierHis(null, 1, 'f')) > 0) {
    $nbre_dcr_date_remb++;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if ($value['is_ligne_credit'] == 'f' && $value['etat'] == 14)
      $nbre_elt++;
  }

  if ($nbre_elt>0 && $nbre_dcr_date_remb>0) return true;
  else return false;
}

/**
 * [143] : Accès au menu Demande raccourcissement de la durée du crédit
 * Conditions d'accès : Le dossier individuel ou de groupe doit être fonds déboursés (etat = 5)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */

function check_fonction_143() {

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // On prend tous les dossiers individuels déboursés du client
  // [5] - "Fonds déboursés"
  $whereCl = " AND is_ligne_credit='f' AND (etat=5) AND (cre_etat=1) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier = getIdDossier($global_id_client, $whereCl);
  
  if (is_array($elt_dossier) && count($elt_dossier) > 0) {
      return true;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
      if ($value['is_ligne_credit'] == 'f' && $value['etat'] == 5 && $value['cre_etat'] == 1) {
        return true;
      }
    }
  }

  return false;
}

/**
 * [144] : Accès au menu Approbation raccourcissement de la durée du crédit
 * Conditions d'accès : Le dossier individuel ou de groupe doit être en attente approbation raccourcissement durée (etat 15)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */

function check_fonction_144() {

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels du client
  $whereCl=" AND is_ligne_credit='f' AND (etat = 15) AND (cre_etat=1) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);

  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if ($value['is_ligne_credit'] == 'f' && ($value['etat'] == 15 && $value['cre_etat'] == 1))
      $nbre_elt++;
  }

  if ($nbre_elt>0) return true;
  else return false;
}


function check_fonction_165()
{
    if (ChequeCertifie::getNbChequeCompensationCertifie(ChequeCertifie::ETAT_CHEQUE_COMPENSATION_ENREGISTRE) > 0) {
        return true;
    }

    return false;
}

function check_fonction_166()
{
    if (ChequeCertifie::getNbChequeCompensationOrdinaire(ChequeCertifie::ETAT_CHEQUE_COMPENSATION_ENREGISTRE) > 0) {
        return true;
    }

    return false;
}

function check_fonction_167()
{
    if (ChequeCertifie::getNbChequeCompensationOrdinaire(ChequeCertifie::ETAT_CHEQUE_COMPENSATION_MIS_EN_ATTENTE) > 0) {
        return true;
    }

    return false;
}

/**
 * [157] : Autorisation de retrait
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_157()
{
    if (count(getListeRetraitAttente()) > 0) {
        return true;
    }

    return false;
}

/**
 * [152] : Autorisation de transfert
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_152()
{
  if (count(getListeTransfertAttente()) > 0) {
    return true;
  }

  return false;
}

/** Ticket Jira AT-44
 * [198] : Autorisation de retrait en deplace
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_198()
{
  if (count(getListeRetraitDeplaceAttente()) > 0) {
    return true;
  }

  return false;
}

/** Ticket Jira AT-39
 * [804] : Autorisation appriovisionnement / delestage
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_804()
{
  if (count(getListeApprovisionnementDelestage()) > 0) {
    return true;
  }

  return false;
}
/** Ticket Jira AT-39
 * [177] : Effectuer appriovisionnement / delestage
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_177() { // Effectuer approvisionnement / delestage
  global $global_id_guichet;

  if (hasApproDelestageAutoriser($global_id_guichet)) {
    return true;
  }

  return false;
}


/**
 * [150] : Accès au menu Annulation raccourcissement de la durée du crédit
 * Conditions d'accès : Le dossier individuel ou de groupe doit être en attente approbation raccourcissement durée (etat 15)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */

function check_fonction_150() {
	
	global $global_id_client;

	// Informations sur le client
	$infos_client = getClientDatas($global_id_client);

	// Dossiers individuels du client
	$whereCl=" AND is_ligne_credit='f' AND (etat = 15) AND (cre_etat=1) AND (gs_cat != 2 OR gs_cat IS NULL)";
	$elt_dossier= getIdDossier($global_id_client,$whereCl);

	$nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client

	// Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
	if ($infos_client['statut_juridique'] == 4) {
		$dossiers_membre = getDossiersMultiplesGS($global_id_client);
		foreach($dossiers_membre as $id_doss=>$value)
		if ($value['is_ligne_credit'] == 'f' && ($value['etat'] == 15 && $value['cre_etat'] == 1))
			$nbre_elt++;
	}

	if ($nbre_elt>0) return true;
	else return false;
}

/**
  * [145] : Accès au menu Rééchelonnement / Moratoire
  *
  * Vérifie l'accès au menu rééchelonnement/moratoire.  Les conditions sont :
  * - que le client ait au moins un crédit avec fonds déboursés (etat = 5)
  * - que ce crédit puisse encore être accepté pour un rééchelonnement
  *
  * @return bool vrai si le menu doit être affiché, faux sinon.
  */
function check_fonction_145() {

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // On prend tous les dossiers individuels déboursés du client
  $whereCl =" AND is_ligne_credit='f' AND etat=5 AND periodicite != 6 AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier = getIdDossier($global_id_client, $whereCl);

  foreach($elt_dossier as $dcr) {
    $id_dossier = $dcr["id_doss"];
    if (allowed2Reech_Moratoire($id_dossier))
      return true;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if ($value['etat'] == 5)
      if (allowed2Reech_Moratoire($id_doss))
        return true;
  }

  return false;

}


/**
 * [137] : Accès au menu Rééchelonnement / Moratoire des credits 'En une fois'
 *
 * Vérifie l'accès au menu rééchelonnement/moratoire.  Les conditions sont :
 * - que le client ait au moins un crédit 'En une fois' (periodicite = 6) avec fonds déboursés (etat = 5) en souffrance
 *      ( 1 < cre_etat < 6)
 * - que ce crédit puisse encore être accepté pour un rééchelonnement
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_137() {

    global $global_id_client;

    // Informations sur le client
    $infos_client = getClientDatas($global_id_client);

    // On prend tous les dossiers individuels déboursés du client
    $whereCl =" AND is_ligne_credit='f' AND etat=5 AND (gs_cat != 2 OR gs_cat IS NULL) AND periodicite=6 ";
    $elt_dossier = getIdDossier($global_id_client, $whereCl);

    foreach($elt_dossier as $dcr) {
        $id_dossier = $dcr["id_doss"];
        if (allowed2Reech_Moratoire($id_dossier, true))
            return true;
    }

    // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
    if ($infos_client['statut_juridique'] == 4) {
        $dossiers_membre = getDossiersMultiplesGS($global_id_client);
        foreach($dossiers_membre as $id_doss=>$value)
            if ($value['etat'] == 5 && $value['periodicite'] == 6)
                if (allowed2Reech_Moratoire($id_doss, true))
                    return true;
    }
    return false;
}

/**
 * [138] : Accès à l'approbation du réechelonnement d'un dossier avec périodicite 'En une fois'
 * Conditions d'accès : Le dossier individuel ou de groupe doit être en attente de rééchelonement/moratoire (etat 7)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_138() {
    global $global_id_client;

    // Informations sur le client
    $infos_client = getClientDatas($global_id_client);

    // Dossiers individuels du client
    $whereCl=" AND is_ligne_credit='f' AND (etat = 7) AND (periodicite=6) AND (gs_cat != 2 OR gs_cat IS NULL)";
    $elt_dossier= getIdDossier($global_id_client,$whereCl);

    $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client

    // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
    if ($infos_client['statut_juridique'] == 4) {
        $dossiers_membre = getDossiersMultiplesGS($global_id_client);
        foreach($dossiers_membre as $id_doss=>$value)
            if (($value['etat'] == 7) && ($value['periodicite'] == 6))
                $nbre_elt++;
    }

    if ($nbre_elt>0) return true;
    else return false;
}


/**
 * [139] : Accès à l'annulation du réechelonnement d'un dossier avec périodicite 'En une fois'
 * Conditions d'accès : Le dossier individuel ou de groupe doit être en attente de rééchelonement/moratoire (etat 7)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_139() {
    return check_fonction_138();
}

function check_fonction_140() { // Consultation d'un dossier
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='f' AND (gs_cat != 2 OR gs_cat IS NULL)";
  $dossier = getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
  $nbre_elt = count($dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0)
    return true;

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    $nbre_elt = count($dossiers_membre);// récupère le nombre de dossiers des membres
    if ( $nbre_elt > 0)
      return true;
  }

  return false;
}

function check_fonction_130() { // Modification d'un dossier
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Les dossiers individuels modifiables  1: Attente de décision  2:accepté  5:Fonds déboursés  13:Fonds déboursés par tranche
  $whereCl=" AND is_ligne_credit='f' AND ((etat=1) OR (etat=2) OR (etat=5) OR (etat=13)) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0)
    return true;

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    $nbre_elt = count($dossiers_membre);// récupère le nombre de dossiers des membres
    if ( $nbre_elt > 0)
      return true;
  }

  return false;
}
function check_fonction_129() { // Correction d'un dossier
 		  global $global_id_client;

 		  // Informations sur le client
 		  $infos_client = getClientDatas($global_id_client);

 		  // Les dossiers individuels qu'on peut corriger  5: Fonds déboursés 7:en attente de réechelonnement 8:réechelonné 9:en perte 13:Fonds déboursés par tranche
 		  $whereCl=" AND is_ligne_credit='f' AND ((etat=5) OR (etat=7) OR (etat=8) OR (etat=9) OR (etat=13) OR (etat=14) OR (etat=15)) AND (gs_cat != 2 OR gs_cat IS NULL)";
 		  $elt_dossier= getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
 		  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
 		  if ( $nbre_elt > 0)
 		    return true;

 		  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
 		  if ($infos_client['statut_juridique'] == 4) {
 		    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
 		    $nbre_elt = count($dossiers_membre);// récupère le nombre de dossiers des membres
 		    if ( $nbre_elt > 0)
 		      return true;
 		  }

 		  return false;
}

/**
 * [131] : Accès à la gestion des pénalités
 *
 * Conditions d'accès : le client doit posséder un crédit déboursé en retard ou en souffrance (état 5 ou 7 et cre_etat > 1).
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_131 () {
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='f' AND ((etat=5) OR (etat=7) OR (etat=14) OR (etat=15)) AND cre_etat > 1 AND ( gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
  $nbre_elt=count($elt_dossier);// récupère le nombre de dossiers du client

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if (($value['etat'] == 5 OR $value['etat'] == 7) and $value['cre_etat'])
      $nbre_elt++;
  }

  if ($nbre_elt>0) return true;
  else return false;
}

/**
 * [132] : Accès à l'abattement des intérêts et des pénalités
 * Conditions d'accès : le client doit posséder un crédit déboursé, en attente de rééchel moratoire ou en perte (etat 5,7 ou 9)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_132 () {
  global $global_id_client;
  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='f' AND ((etat=5) OR (etat=7) OR (etat=9) OR (etat=13) OR (etat=14) OR (etat=15)) AND (gs_cat != 2 OR gs_cat IS NULL)";

  $elt_dossier= getIdDossier($global_id_client,$whereCl);
  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0)
    return true;

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if ($value['etat'] == 5 or $value['etat'] == 7 or $value['etat'] == 9 or $value['etat'] == 13)
      return true;
  }

  return false;
}

/**
 * [133] : Accès au traitement pour remboursement anticipe
 * Conditions d'accès : le client doit posséder un crédit déboursé, en attente de rééchel moratoire ou en perte (etat 5,7 ou 9)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_133 () {
  global $global_id_client,$global_id_agence;
  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='f' AND ((etat=5) OR (etat=7) OR (etat=9) OR (etat=13) OR (etat=14) OR (etat=15)) AND (gs_cat != 2 OR gs_cat IS NULL)";

  $elt_dossier= getIdDossier($global_id_client,$whereCl);
  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0)
    return true;

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
      if ($value['etat'] == 5 or $value['etat'] == 7 or $value['etat'] == 13)
        return true;
  }

  $agence_data = getAgenceDatas($global_id_agence);
  if ($agence_data['tx_remb_anticipe'] == 't'){
    return true;
  }

  return false;
}

/**
 * [125] : Accès au déboursement d'un dossier
 * Conditions d'accès : le client doit posséder un crédit accepté (etat 2)
 * ou être un groupe solidaire ayant un crédit de groupe accepté (etat 2)
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_125() {

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  $whereCl=" AND is_ligne_credit='f' AND (etat=2 OR etat=13) AND (gs_cat != 2 OR gs_cat IS NULL)"; // Dossier individuel à l'état accepté
  $elt_dossier= getIdDossier($global_id_client,$whereCl);
  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if (($value['etat'] == 2) || ($value['etat'] == 13))
      $nbre_elt++;
  }

  if ($nbre_elt>0) return true;
  else return false;
}

/**
 * [120] : Accès à l'annulation d'un dossier
 * Conditions d'accès : Le dossier doit être en attente de décision 1 ou à l'état accepté ou rééchelonné (etat 1, 2 ou 7)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_120() {
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='f' AND ((etat=1) OR (etat=2) OR (etat = 7 AND periodicite != 6) OR (etat = 14)) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);

  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0)
    return true;

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if ($value['etat'] == 1 or $value['etat'] == 2 or $value['etat'] == 14 or ($value['etat'] == 7 and $value['periodicite'] != 6))
      return true;
  }

  return false;

}

/**
 * [126] : Accès à l'annulation de déboursement progressif
 * Conditions d'accès : Le dossier doit être à l'état 'en déboursement progressif' (etat 13)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_126() {
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='f' AND ((etat=13) AND (gs_cat != 2 OR gs_cat IS NULL))";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);

  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0)
    return true;

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if ($value['etat'] == 13)
      return true;
  }

  return false;

}
/**
 * [115] : Accès au rejet d'un dossier
 * Conditions d'accès : Le dossier doit être en attente de décision ou en attente de rééchelonement/moratoire (etat 1 ou 7 ou 14)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_115() {
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='f' AND ((etat=1) OR ((etat=7 and periodicite != 6)) OR (etat=14)) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);
  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0)
    return true;

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if ($value['etat'] == 1 or ($value['etat'] == 7 AND $value['periodicite'] != 6)or $value['etat'] == 14)
      return true;
  }

  return false;

}

/**
 * [110] : Accès à l'aprobation d'un dossier
 * Conditions d'accès : Le dossier individuel ou de groupe  doit être en attente de décision ou en attente de rééchelonement/moratoire (etat 1 ou 7)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */

function check_fonction_110() {

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels du client
  $whereCl=" AND is_ligne_credit='f' AND ((etat = 1) OR (etat = 7 AND periodicite <> 6)) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);

  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value)
    if (($value['etat'] == 1 OR ($value['etat'] == 7 && $value['periodicite'] != 6)))
      $nbre_elt++;
  }

  if ($nbre_elt>0) return true;
  else return false;
}

/**
 * [600] : Accès à la mise en place d'un dossier ligne de crédit
 * Conditions d'accès :<ul>
 * <li> Le délai minimum avant octroi d'un crédit est pas atteint
 * <li> Le client n'est pas auxiliaire ou la case 'Octroi d'un crédit à un non sociétaire' a été cochée dans le paramétrage agence
 * <li> Le client est pas actif
 * </ul>
 *
 * @return bool vrai si le menu doit être affiche, faux sinon.
 */
function check_fonction_600() {
  global $global_id_client;
  global $global_id_agence;
  global $global_etat_client;

  // Vérifie que le client est bien actif
  if ($global_etat_client != 2) {
    return false;
  }
    $DATA_AG = getAgenceDatas($global_id_agence);
    $CLI = getClientDatas($global_id_client);
    
    // Vérifie le délai avant octroi d'un crédit
    $result = checkDureeAvantCredit($global_id_agence, $global_id_client);
    if ($result->errCode != NO_ERR) { // En cas d'erreur, on n'autorise pas
      return false;
    }
    if (!$result->param[0]) {
      return false;
    }

    // Vérifie si les groupes solidaires doivent payer les parts sociales avant octroi de credit
    //ou si le client est auxiliaire
    if (($DATA_AG["paiement_parts_soc_gs"] != 1) || ($CLI["qualite"] == 1)) {
        return true;
    }

    // Vérife la qualité du client et le paramétrage de l'agence
    if ((getQualiteClient($global_id_client) == 1) && ($DATA_AG["octroi_credit_non_soc"] == 'f')) {
        return false;
    }

    return true;
}

/**
 * [601] : Accès à l'approbation d'un dossier ligne de crédit
 * Conditions d'accès : Le dossier individuel ou de groupe doit être en attente de décision (etat 1)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_601() {

  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels du client
  $whereCl = " AND is_ligne_credit='t' AND etat = 1 AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier = getIdDossier($global_id_client,$whereCl);

  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client

  // Si le client est un GS, vérifier s'il n'y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
      if ($value['etat'] == 1) {
        $nbre_elt++;
      }
    }
  }
  */

  if ($nbre_elt > 0){
      return true;
  } else {
      return false;
  }
}

/**
 * [602] : Accès au rejet d'un dossier ligne de crédit
 * Conditions d'accès : Le dossier doit être en attente de décision (etat 1)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_602() {
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl = " AND is_ligne_credit='t' AND (etat=1) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);
  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0) {
    return true;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
      if ($value['etat'] == 1) {
        return true;
      }
    }
  }
  */

  return false;
}

/**
 * [603] : Accès à l'annulation d'un dossier ligne de crédit
 * Conditions d'accès : Le dossier doit être en attente de décision (etat 1 ou 2)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_603() {
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl = " AND is_ligne_credit='t' AND (etat=1 OR etat=2) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl);

  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0) {
    return true;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
      if ($value['etat'] == 1 or $value['etat'] == 2) {
        return true;
      }
    }
  }
  */

  return false;
}

/**
 * [604] : Accès au déboursement d'un dossier ligne de crédit
 * Conditions d'accès : le client doit posséder un crédit accepté (etat 2)
 * ou être un groupe solidaire ayant un crédit de groupe accepté (etat 2)
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_604() {

    global $global_id_client;

    // Informations sur le client
    $infos_client = getClientDatas($global_id_client);

    $whereCl = " AND deboursement_autorisee_lcr='t' AND is_ligne_credit='t' AND (etat=2 OR etat=5) AND (gs_cat != 2 OR gs_cat IS NULL)"; // Dossier individuel à l'état accepté
    $elt_dossier = getIdDossier($global_id_client,$whereCl);
    //$nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client

    $date_due = php2pg(date("d/m/Y"));

    $nbre_elt = 0;
    foreach ($elt_dossier as $id_doss=>$value) {
        if (($value['etat'] == 2) || (getMontantRestantADebourserLcr($id_doss, $date_due) > 0 && !isPeriodeNettoyageLcr($id_doss, $value["duree_nettoyage_lcr"]))) {
            $nbre_elt++;
        }

    }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
      if (($value['etat'] == 2)) {
        $nbre_elt++;
      }
    }
  }
  */

  if ($nbre_elt>0) {
    return true;
  } else {
    return false;
  }
}

function check_fonction_605() { // Modification d'un dossier ligne de crédit
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Les dossiers individuels modifiables  1: Attente de décision  2:accepté  5:Fonds déboursés
  $whereCl=" AND is_ligne_credit='t' AND ((etat=1) OR (etat=2) OR (etat=5)) AND (gs_cat != 2 OR gs_cat IS NULL)";
  $elt_dossier= getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
  $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0) {
    return true;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    $nbre_elt = count($dossiers_membre);// récupère le nombre de dossiers des membres
    if ( $nbre_elt > 0) {
      return true;
    }
  }
  */

  return false;
}

function check_fonction_606() { // Consultation d'un dossier ligne de crédit
  global $global_id_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $whereCl=" AND is_ligne_credit='t' AND (gs_cat != 2 OR gs_cat IS NULL)";
  $dossier = getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
  $nbre_elt = count($dossier);// récupère le nombre de dossiers du client
  if ( $nbre_elt > 0) {
    return true;
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    $nbre_elt = count($dossiers_membre);// récupère le nombre de dossiers des membres
    if ( $nbre_elt > 0) {
      return true;
    }
  }
  */

  return false;
}


function check_fonction_607() { // Remboursement d'un crédit ligne de crédit
  global $global_id_client;
  global $global_etat_client;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  // Dossiers individuels
  $DOSS = getDossierClient($global_id_client);
  if (is_array($DOSS))
    while (list(, $doss) = each($DOSS)) {
      if ($doss['is_ligne_credit'] == 't' and ($doss['etat'] == 5 || $doss['etat'] == 9) and $doss['gs_cat'] != 2 and (getCapitalRestantDuLcr($doss['id_doss'], date('d/m/Y')) > 0 or getCalculInteretsLcr($doss['id_doss'], php2pg((date("d/m/Y")))) > 0 or getCalculFraisLcr($doss['id_doss'], php2pg((date("d/m/Y")))) > 0 or getSoldePenEcheanceLcr($doss['id_doss']) > 0)) {
        return ($global_etat_client == 2 || $global_etat_client == 7);
      }
    }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
      if ($value['etat'] == 5 OR $value['etat'] == 9) {
        return ($global_etat_client == 2 || $global_etat_client == 7);
      }
    }
  }
  */

  return false;
}

/**
 * [608] : Accès au menu réalisation garantie ligne de crédit
 *
 * La réalisation d'une garantie est possible si :
 * - le client a un crédit en cours qui n'est ni sain ni en perte
 * - le crédit est couvert par au moins une garantie à l'état 'Mobilisé'
 *
 *  @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_608() {
  global $global_id_client, $global_id_agence;

  // Informations sur le client
  $infos_client = getClientDatas($global_id_client);

  $AG = getAgenceDatas($global_id_agence);
  if($AG['realisation_garantie_sain'] == 't') {
  	/* recupère l'id de l'état en perte */
  	$idEtatPerte = getIDEtatPerte();
  	/* Recherche des dossiers individuels déboursés ou en attente de Rééch/Moratoire ni en perte(ici on ajoute crédits sains voir #1859) */
  	$whereCl = " AND is_ligne_credit='t' AND ((etat=5)) AND cre_etat != $idEtatPerte AND (gs_cat != 2 OR gs_cat IS NULL)";
  } else {
  	/* Recherche des dossiers individuels déboursés ou en attente de Rééch/Moratoire ni sain ni en perte */
        $whereCl = " AND is_ligne_credit='t' AND ((etat=5)) AND cre_etat > 1 AND (gs_cat != 2 OR gs_cat IS NULL)";
  }
  
  $DOSS = getIdDossier($global_id_client,$whereCl);

  /* Vérifier qu'il existe un dossier avec une garantie mobilisée */
  if (sizeof($DOSS)> 0) {
    foreach($DOSS as $key=>$value) {
    	$liste_gars = getListeGaranties($value['id_doss']);
    	foreach($liste_gars as $cle=>$valeur) {
            if ($valeur['etat_gar'] == 3) { //garantie mobilisée
                return true;
            }
            elseif($value['gar_num_encours']>0 AND $value['cpt_gar_encours']==$valeur['gar_num_id_cpte_nantie']) {
                //garantie numeraire à constituer
                $CPT_GAR = getAccountDatas($valeur['gar_num_id_cpte_nantie']);
                /* Solde disponible sur le compte de garantie */
                $solde_disp = $CPT_GAR['solde'];
                if ($solde_disp>0) {
                    return true;
                }
            }
    	}
    }
  }

  // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
  /*
  if ($infos_client['statut_juridique'] == 4) {
    $dossiers_membre = getDossiersMultiplesGS($global_id_client);
    foreach($dossiers_membre as $id_doss=>$value) {
        if (($value['etat'] == 5 OR $value['etat'] == 9) and $value['cre_etat'] > 1) {
          $liste_gars = getListeGaranties($id_doss);
          foreach($liste_gars as $cle=>$valeur) {
            if ($valeur['etat_gar'] == 3) {
              return true;
            }
          }
        }
    }
  }
  */

  return false;
}

function check_fonction_609() { // Correction d'un dossier
    global $global_id_client;

    // Informations sur le client
    $infos_client = getClientDatas($global_id_client);

    // Les dossiers individuels qu'on peut corriger  5: Fonds déboursés 9:en perte
    $whereCl=" AND is_ligne_credit='t' AND ((etat=5) OR (etat=9)) AND (gs_cat != 2 OR gs_cat IS NULL)";
    $elt_dossier= getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
    $nbre_elt = count($elt_dossier);// récupère le nombre de dossiers du client
    if ( $nbre_elt > 0) {
      return true;
    }

    // Si le client est un GS, vérifier s'il n y a pas de crédit de groupe avec dossiers réels pour les membres (GS cas 2)
    /*
    if ($infos_client['statut_juridique'] == 4) {
      $dossiers_membre = getDossiersMultiplesGS($global_id_client);
      $nbre_elt = count($dossiers_membre);// récupère le nombre de dossiers des membres
      if ( $nbre_elt > 0) {
        return true;
      }
    }
    */

    return false;
}


/**
 * [610] : Accès au menu clôturer d'un dossier ligne de crédit
 * Conditions d'accès : le client doit posséder un crédit déboursé (etat 5)
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_610() {

    global $global_id_client;
    global $global_monnaie_courante_prec;

    $whereCl = " AND is_ligne_credit='t' AND etat=5 AND (gs_cat != 2 OR gs_cat IS NULL)"; // Dossier individuel à l'état déboursé
    $elt_dossier = getIdDossier($global_id_client, $whereCl);

    $nbre_elt = 0;
    foreach ($elt_dossier as $id_doss=>$value) {

        if (!isPeriodeNettoyageLcr($id_doss, $value["duree_nettoyage_lcr"])) {
            $nbre_elt++;
        }
    }

    if ($nbre_elt>0) {
        return true;
    } else {
        return false;
    }
}


/**
 * [105] : Accès à la mise en place d'un dossier de crédit
 * Conditions d'accès :<ul>
 * <li> Le délai minimum avant octroi d'un crédit est pas atteint
 * <li> Le client n'est pas auxiliaire ou la case 'Octroi d'un crédit à un non sociétaire' a été cochée dans le paramétrage agence
 * <li> Le client est pas actif
 * </ul>
 *
 * @return bool vrai si le menu doit être affiche, faux sinon.
 */
function check_fonction_105() {
  global $global_id_client;
  global $global_id_agence;
  global $global_etat_client;

  // Vérifie que le client est bien actif
  if ($global_etat_client != 2)
    return false;
    $DATA_AG = getAgenceDatas($global_id_agence);
  $CLI = getClientDatas($global_id_client);

  // Vérifie le délai avant octroi d'un crédit
  $result = checkDureeAvantCredit($global_id_agence, $global_id_client);
  if ($result->errCode != NO_ERR)
    // En cas d'erreur, on n'autorise pas
    return false;
  if (!$result->param[0])
    return false;

   //Vérifie si les groupes solidaires doivent payer les parts sociales avant octroi de credit
   //ou si le client est auxiliaire
  if (($DATA_AG["paiement_parts_soc_gs"] != 1) || ($CLI["qualite"] == 1))
    return true;

  // Vérife la qualité du client et le paramétrage de l'agence
  $agence = getAgenceDatas($global_id_agence);
  if ((getQualiteClient($global_id_client) == 1) && ($agence["octroi_credit_non_soc"] == 'f'))
    return false;

  return true;
}

function check_fonction_53() { // ouverture d'un compte
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if ($global_etat_client != 2) { //client doit être actif
      if ($global_etat_client == 1 && check_fonction_59()){ //client peut etre en attente de validation et que le profil a l'option Autoriser ouverture des comptes et ordres permanents sans frais
        return true;
      }
      return false;
    }
  }

  return true;

}

function check_fonction_54() { // clôture d'un compte
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if ($global_etat_client != 2)  //client doit être actif
      return false;

    $ACCS = getComptesCloturePossible($global_id_client);
    if (sizeof($ACCS) > 0)
      return true;
    else
      return false;
  }

  return true;
}

function check_fonction_55() { // simulation arrêté d'un compte
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if ($global_etat_client != 2)  //client doit être actif
      return false;
    $ListeComptes = get_comptes_epargne($global_id_client);
    $nbre_cptes = count($ListeComptes);
    if ($nbre_cptes < 2) return false;//le client n'a qu'un compte de base pour lequel on ne peut pas faire de simulation d'arrêté
  }

  return true;

}

function check_fonction_60() { // Gestion des annulations retrait et dépôt
    global $global_id_client;

    if (
        AnnulationRetraitDepot::hasOperationEpargne($global_id_client)
        || AnnulationRetraitDepot::hasDemandeAnnulationEnregistre($global_id_client)
        || AnnulationRetraitDepot::hasDemandeAnnulationAutorise($global_id_client)
    )
    {
        return true;
    }

    return false;
}

function check_fonction_61() { // Demande annulation retrait / dépôt
    global $global_id_client;

    if (AnnulationRetraitDepot::hasOperationEpargne($global_id_client)) {
        return true;
    }

    return false;
}

function check_fonction_62() { // Approbation demande annulation retrait / dépôt
    global $global_id_client;

    if (AnnulationRetraitDepot::hasDemandeAnnulationEnregistre($global_id_client)) {
        return true;
    }

    return false;
}

function check_fonction_63() { // Effectuer annulation retrait / dépôt
    global $global_id_client;

    if (AnnulationRetraitDepot::hasDemandeAnnulationAutorise($global_id_client)) {
        return true;
    }

    return false;
}

function check_fonction_70() { // retrait sur un compte
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if ($global_etat_client != 2)  //client doit être actif
            return false;
    }

    return true;

}

function check_fonction_72() { // Autorisation retrait

    if (hasRetraitAttenteDemande()) {
        return true;
    }

    return false;
}

function check_fonction_74() { // Paiement retrait
    global $global_id_client;

    if (hasRetraitAttenteAutorise($global_id_client)) {
        return true;
    }

    return false;
}

function check_fonction_64() { // Paiement retrait en déplacé = Jira Ticket AT-44
  global $global_remote_id_client;

  if (hasRetraitDeplaceAttenteAutorise($global_remote_id_client)) {
    return true;
  }

  return false;
}

function check_fonction_100() { // Paiement retrait
  global $global_id_client;

  if (hasTransfertAttenteAutorise($global_id_client)) {
    return true;
  }

  return false;
}

function check_fonction_75() { // dépôt sur un compte
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if (($global_etat_client != 2) && ($global_etat_client != 7))  //client doit être actif ou en attente enregistrement décès
      return false;
  }

  return true;

}

function check_fonction_76() { // transfert sur un compte
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if ($global_etat_client != 2)  //client doit être actif
      return false;
  }

  return true;

}

function check_fonction_77() { // traitement de chèques
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) { //si on a choisi un client
    if (($global_etat_client != 2) && ($global_etat_client != 7)) { //client doit être actif ou en attente enregistrement décès
      return false;
    }
    //vérifier qu'il y a des chèques en attente pour ce client
    // $ListeChqe = getListeChequesNonEncaisses($global_id_client);
    if (empty($ListeChqe)) {
      return false;
    }
  }

  return true;

}

function check_fonction_80() { // consultation d'un compte
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if (($global_etat_client != 2) && ($global_etat_client != 7) && !is_client_radie())  //client doit être actif ou en attente enregistrement décès
      return false;
  }

  return true;

}

function check_fonction_88() { // Modification d'un compte
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if (($global_etat_client != 2) && ($global_etat_client != 7))  //client doit être actif ou en attente enregistrement décès
      return false;
  }

  return true;

}

/**
 * [40] : Accès au menu Documents
 *
 * Vérifie l'accès au menu Documents.
 * Pour cela l'etat du client doit être égal à 2
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_40() {
  global $global_etat_client;

  if ($global_etat_client == 2 || $global_etat_client == 5 || $global_etat_client == 6 || is_client_radie())  //si le client est actif
    return true;
  else
    return false;
}


// Souscription parts sociales
function check_fonction_20 () { // Souscription de parts sociales
  global $global_id_client;
  global $global_id_agence;
  global $global_etat_client;

  $global_etat_client = getEtatClient($global_id_client);

  // donnée agence
  $AG = getAgenceDatas($global_id_agence);

  // Autoris uniquement pour clients actifs et si la val nominale d'une PS > 0
  if ($global_etat_client == 2 && $AG["val_nominale_part_sociale"] > 0) {
      return true;
  } else {
      return false;
  }
}

// Transfert parts sociales
function check_fonction_21()
{
    global $global_id_client;
    global $global_id_agence;
    global $global_etat_client;

    $global_etat_client = getEtatClient($global_id_client);

    // donnée agence
    $AG = getAgenceDatas($global_id_agence);

    if (in_array($global_etat_client, array(3,5,6)) && $AG["val_nominale_part_sociale"] > 0) {
        return false;
    }

    return true;
}

// Libération parts sociales
function check_fonction_28()
{
    global $global_id_client;
    global $global_id_agence;
    global $global_etat_client;

    $global_etat_client = getEtatClient($global_id_client);

    // donnée agence
    $AG = getAgenceDatas($global_id_agence);

    if (in_array($global_etat_client, array(3,5,6)) && $AG["val_nominale_part_sociale"] > 0) {
        return false;
    }

    return true;
}

function check_fonction_15 () { // Défection d'un client
  global $global_id_client;
  global $global_etat_client;
  $global_etat_client = getEtatClient($global_id_client);

  // Autoris uniquement pour clients en attente de validation ou actifs
  if (($global_etat_client == 1) || ($global_etat_client == 2)) return true;
  else return false;
}

function check_fonction_17 () { // Simulation défection d'un client
  global $global_id_client;
  global $global_etat_client;
  $global_etat_client = getEtatClient($global_id_client);

  // Autoris uniquement pour clients en attente de validation ou actifs
  if (($global_etat_client == 1) || ($global_etat_client == 2)) return true;
  else return false;
}

function check_fonction_10 () { // Modification d'un client
  global $global_id_client;
  global $global_etat_client;

  // Uniquement pour clients en attente de validation, en attente d'enregistrement de décès et actifs
  if (($global_etat_client == 1) || ($global_etat_client == 2) || ($global_etat_client == 7)) return true;
  else return false;
}

function check_fonction_16 () { // Finaliser la défection d'un client décédé
  global $global_id_client;
  global $global_etat_client;
  $global_etat_client = getEtatClient($global_id_client);
  // Autoris uniquement pour clients en attente d'enregistrement de décès
  if ($global_etat_client != 7) return false;
  else return true;
}

function check_fonction_101 () { // Acès au menu crédit
  //FIXME : vérifier les variables contextuelles qui peuvent ne pas etre set à un moment donné pour éviter
  //des appels avec des arguments vides à SQL
  require_once 'lib/dbProcedures/client.php';
  require_once 'lib/dbProcedures/agence.php';
  global $global_id_client;
  global $global_type_structure;
  global $global_id_agence;
  // Pas d'accès pour les clients autres que actifs
  //FIXME : si le client n'existe pas, on ne peut pas afficher le menu crédit dans le frame de gauche
  if ($global_id_client != "")
    $CLI = getClientDatas($global_id_client);

  if ($CLI["etat"] != 2 && $CLI["etat"] != 7 && !is_client_radie())
    return false;

  if (($CLI["statut_juridique"] == 4) and ($CLI["qualite"] == 1))
    return true;

  $AGC = getAgenceDatas($global_id_agence);
  if ($global_type_structure == 1)
    if (($AGC["octroi_credit_non_soc"] == 'f') and ($CLI["qualite"] == 1))
      return false;

  return true;
}

function check_fonction_51 () { // Acès au menu épargne
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client;
  global $global_etat_client;
  // Seuls les clients actifs y ont accès
  if ($global_etat_client != 2 && $global_etat_client != 7 && !is_client_radie()){
    if ($global_etat_client == 1 && check_fonction_59()){ // si etat client 1
      return true;
    }
    return false;
  }
  else return true;
}

function check_fonction_31 () { // Perception des frais d'adhésion
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client;
  global $global_etat_client;

  // Uniquement pour les clients en cours de validation
  //if ($global_etat_client != 1) return false;
  //else return true;
  $global_etat_client = getEtatClient($global_id_client);
  //si payement par tranche frais adhesion est activé
  $id_agc = getNumAgence();
  $AGD = getAgenceDatas($id_agc);
  $soldeFraisAdh = getSoldeFraisAdhesion($global_id_client);
  $soldeRestFraisAdhesion = $soldeFraisAdh->param[0]['solde_frais_adhesion_restant'];
  if ($AGD["tranche_frais_adhesion"] == "t") { //Si on paye par tranche les frais d'adhésion
    if ($soldeRestFraisAdhesion > 0) {
      return true;
    } else return false;
  } else {

    if ($global_etat_client == 1) //Si y'a  des frais d'adhésions à payer
      return true;
    else return false;
  }

}

/**
 * [19] : Faire jouer l'assurance
 * Conditions d'accès :
 * - Client en attente d'enregistrement de décès
 * - Dossier doit être déboursé ou en attente de rééchelonement/moratoire (etat 5 ou 7)
 * - L'assurance n'a pas encore été utilisée sur ce dossier
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */
function check_fonction_19 () { // Faire jouer l'assurance
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client;
  $CLI = getClientDatas($global_id_client);
  $whereCl=" AND ((etat=7) OR (etat=5) OR (etat=14) OR (etat=15)) AND assurances_cre='f'";
  $elt_dossier= getIdDossier($global_id_client,$whereCl); // Identifiant du dossier de crédit
  $nbre_elt= count($elt_dossier);// récupère le nombre de dossiers du client

  if ($nbre_elt>0 && $CLI['etat'] == 7)
    return true;
  else
    return false;
}

function check_fonction_186() { // Accessible uniquement en mode multidevise
  global $global_multidevise;
  return $global_multidevise;
}

function check_fonction_475() { // radiation d'un crédit
  global $global_id_agence;

	$infos_ag = getAgenceDatas($global_id_agence);
  if ($infos_ag['passage_perte_automatique'] == "t")
  	return false;
  else
  return true;
}
function check_fonction_91() { // Compte dormant 
  global $global_id_client;
  $ListeComptes = getComptesDormants($global_id_client);
  if(count($ListeComptes) > 0 ) {
  	return  TRUE;
  } ELSE {
  	return  FALSE ;
  }
}



function check_access($fonction)
{
  /* Cette fonction renvoie un booléen indiquant si la fonction peut être utilisée ou non en fonction du contexte :
     - Droit d'accès de l'utilisateur courant
     - Nécessite agence ouverte ?
     - Nécessite guichet ?
     - Contrôle individuel (définit dans la fonction "check_fonction_i" où "i" correspond au numéro de la fonction)
  */
  global $global_statut_agence;
  global $global_id_guichet;
  global $global_profil_axs;
  global $global_type_structure;
  global $adsys;
  global $global_nom_ecran_prec,$global_nom_ecran;

  //Contrôles globaux

  // Tout le monde a accès à la fonction 0
  if ($fonction == 0) return true;
  if ($fonction == NULL) return true;

  // Vérifications liées au profil
  if (!in_array($fonction, $global_profil_axs)) {
    if( ($fonction == 194 and $global_nom_ecran_prec == 'Rma-1') ||($fonction == 194 and $global_nom_ecran_prec == 'Ama-1') ||($fonction == 194 and $global_nom_ecran_prec == 'Ama-2')){
      return true;
    }
    // AT-125 : Acees à la saisie eriture libre (type fonction 470) et à la validation ecriture libre (type fonction 471)
    // en passant par le premier ecran 'Ecr-1'
    // Saisie et validation ont été separés (il y a un nouveau menu pour la validation dans le menu comptabilité)
    if (($fonction == 470 && $global_nom_ecran == 'Ecr-1')){
      return true;
    }
    if (($fonction == 471 && $global_nom_ecran == 'Ecr-1')){
        return true;
    }
    return false;
  }

  // Fonctions qui nécessitent que l'agence soit ouverte
  if (($global_statut_agence != 1) && (in_array($fonction, $adsys["adsys_fonction_systeme_ouvert"])))
    return false;

  // Fonctions qui nécessitent un guichet
  if ((! $global_id_guichet) && (in_array($fonction, $adsys["adsys_fonction_systeme_guichet"])))
    return false;

  // Fonction non accessible selon le type de structure
  switch ($global_type_structure) {
  case 2:     // ICD
    if (in_array($fonction, $adsys["adsys_fonctions_non_icd"]))
      return false;
    break;
  case 3:    // Banque
    if (in_array($fonction, $adsys["adsys_fonctions_non_bq"]))
      return false;
    break;
  }

  //Contrôle individuel
  $name = "check_fonction_".$fonction;
  if (function_exists($name)) {
    return $name();
  } else return true;
}

//Fonction qui determine si un client est radié
function is_client_radie(){
    global $global_etat_client;
    if(in_array($global_etat_client, array(3,5,6))) {
        return true;
        }
    else {
        return false;
    }
}

function check_fonction_79() { // Ordre permanents
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7)) {  //client doit être actif ou en attente enregistrement décès
          if ($global_etat_client == 1 && check_fonction_59()){ //client peut etre en attente de validation et que le profil a l'option Autoriser ouverture des comptes et ordres permanents sans frais
            return true;
          }
          return false;
        }
    }
    return true;
}

function check_fonction_89() { // Bloquer/debloquer un compte
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}

function check_fonction_135() { // simulation echéancier
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}

function check_fonction_68() { // simulation echéancier pour produit epargne
  global $global_id_client;
  global $global_etat_client;

  if (! empty($global_id_client)) {
    if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
      return false;
  }
  return true;

}

function check_fonction_90() { // Gestion des mandats
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}

function check_fonction_41() { // Commande chèquier
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}

function check_fonction_42() { // Retrait chèquier
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}

function check_fonction_45() { // Mise en opposition chèque
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}

function check_fonction_43() { // Extraits de compte
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7)  && !is_client_radie() )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}


function check_fonction_44() { //Situation globale client
    global $global_id_client;
    global $global_etat_client;

    if (! empty($global_id_client)) {
        if (($global_etat_client != 2) && ($global_etat_client != 7) )  //client doit être actif ou en attente enregistrement décès
            return false;
    }
    return true;

}
function check_fonction_171(){
  if (isEngraisChimiques()){
    return true;
  }
}

function check_fonction_172(){
  global $global_id_guichet;
  require_once 'lib/dbProcedures/client.php';
  $id_annee = getAnneeAgricoleActif();
  if (($id_annee != null) && ($global_id_guichet == null)){
    $whereSaison = "id_annee = " . $id_annee['id_annee'] . " and etat_saison = 1 and date(now()) between date_debut and coalesce(date_fin_avance,'" . $id_annee['date_fin'] . "') ";
    $id_saison = getListeSaisonPNSEB($whereSaison);
    if ($id_saison != null) {
      return true;
    } else {
      return false;
    }
  }else {
    return false;
  }
}

function check_fonction_174 () { // approbation derogation sur commande PNSEB
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client, $global_id_benef;
  $id_annee = getAnneeAgricoleActif();
  if ($id_annee != null) {
    $condi = "etat = 1 and id_benef =" . $global_id_client;
    $derogationEnCours = getDerogationenCours($condi);
    if ($derogationEnCours != null)
      return true;
    else
      return false;
  }else {
    return false;
  }
}
function check_fonction_175 () { // effectuer derogation sur commande PNSEB
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client, $global_id_benef;
  $id_annee = getAnneeAgricoleActif();
  if ($id_annee != null) {
    $condi = "etat = 2 and id_benef =" . $global_id_client;
    $derogationEnCours = getDerogationenCours($condi);
    if ($derogationEnCours != null) {
      return true;
    } else {
      return false;
    }
  }else {
    return false;
  }
}
function check_fonction_176 () { // annulation commande
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client, $global_id_benef,$global_id_guichet;
  $id_annee = getAnneeAgricoleActif();
  if ($id_annee != null) {
    if ($global_id_guichet != null){
      $condition_annulation_commande = "etat_commande in (1) and (date_creation between date(now()) and date(now()) + interval '1 day') and id_benef =" . $global_id_client;
    }else{
      $condition_annulation_commande = "etat_commande in (6,7) and (date_creation between date(now()) and date(now()) + interval '1 day') and id_benef =" . $global_id_client;
    }
    $commande_annul = getCommande($condition_annulation_commande);
    if ($commande_annul != null) {
      return true;
    } else {
      return false;
    }
  }else {
    return false;
  }
}
function check_fonction_173(){
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client, $global_id_benef,$global_id_guichet;
  $id_annee_data = getAnneeAgricoleActif();
  if($id_annee_data != null) {
    $whereSaison = "id_annee = " . $id_annee_data['id_annee'] . " and etat_saison = 1 and date(now()) between date_debut_solde and coalesce(date_fin_solde,'" . $id_annee_data['date_fin'] . "') ";
    $id_saison = getListeSaisonPNSEB($whereSaison);
    $condition_annulation = "etat_commande in (8) and id_benef =" . $global_id_client;
    $commande_annul = getCommande($condition_annulation);
    if ($id_saison != null && $commande_annul != null) {
      return true;
    } else {
      return false;
    }
  } else {
    return false;
  }
}

function check_fonction_169(){
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client, $global_id_benef,$global_id_guichet;
  $id_annee_data = getAnneeAgricoleActif();
  if($id_annee_data != null) {
    $whereSaison = "id_annee = " . $id_annee_data['id_annee'] . " and etat_saison = 1 and date(now()) between date_debut_solde and coalesce(date_fin_solde,'" . $id_annee_data['date_fin'] . "') ";
    $id_saison = getListeSaisonPNSEB($whereSaison);
    $condition_annulation = "etat_commande in (2,8) and id_benef =" . $global_id_client;
    $commande_annul = getCommande($condition_annulation);
    if ($id_saison != null && $commande_annul != null && $global_id_guichet == null) {
      return true;
    } else {
      return false;
    }
  } else {
    return false;
  }
}


function check_fonction_183(){
  require_once 'lib/dbProcedures/client.php';
  global $global_id_client, $global_id_benef;
  $id_annee_attente = getAnneeAgricoleActif();
  if ($id_annee_attente != null) {
    $whereSaison = "id_annee = " . $id_annee_attente['id_annee'] . " and etat_saison = 1 AND  date(now()) between date_debut and date_fin_avance";
    $saison = getListeSaisonPNSEB($whereSaison);
    if ($saison != null) {
      $condi = "etat_commande = 7 and id_benef =" . $global_id_client;
      $CommandeAttente = getCommande($condi);
      if ($CommandeAttente != null) {
        return true;
      } else {
        return false;
      }
    }
  }else {
    return false;
  }
}

function check_fonction_184(){
  global $global_id_guichet;
  require_once 'lib/dbProcedures/client.php';
  $id_annee = getAnneeAgricoleActif();
  if (($id_annee != null)){
    $whereSaison = "id_annee = " . $id_annee['id_annee'] . " and etat_saison = 1 and date(now()) between date(date_fin_avance) and coalesce(date(date_fin_solde),'" . $id_annee['date_fin'] . "') ";
    $id_saison = getListeSaisonPNSEB($whereSaison);
    if ($id_saison != null) {
      return true;
    } else {
      return false;
    }
  }else {
    return false;
  }
}

function check_fonction_801(){
  global $global_id_client,$global_id_guichet;
  require_once 'lib/dbProcedures/client.php';
  $id_annee = getAnneeAgricoleActif();
  if (($id_annee != null) && ($global_id_guichet == null)){
    $whereSaison = "id_annee = " . $id_annee['id_annee'] . " and etat_saison = 1 and date(now()) between date_debut_solde and coalesce(date_fin_solde,'" . $id_annee['date_fin'] . "') ";
    $id_saison = getListeSaisonPNSEB($whereSaison);
    if ($id_saison != null) {
      $condi = "etat_commande IN (3,8) and id_benef =" . $global_id_client;
      $CommandeAttente= getCommande($condi);
      if ($CommandeAttente != null) {
        return true;
      }
    } else {
      return false;
    }
  }else {
    return false;
  }
}


function check_fonction_182(){
  return true;
}

function check_fonction_705(){ //Module Budget : Mise en Place Budget
  $liste_type_budget = getTypBudgetFromTabBudget("= 6",true);
  if ($liste_type_budget == null || ($liste_type_budget != null && sizeof($liste_type_budget)<=3)){
    return true;
  }
  else{
    return false;
  }
}

function check_fonction_706(){ //Module Budget : Raffiner le Budget
  $liste_type_budget = getTypBudgetFromTabBudget("<= 2",true);
  if ($liste_type_budget != null){
    return true;
  }
  else{
    return false;
  }
}

function check_fonction_707(){ //Module Budget : Reviser le Budget
  $liste_type_budget = getTypBudgetFromTabBudget(">= 3",true);
  if ($liste_type_budget != null){
    return true;
  }
  else{
    return false;
  }
}

function check_fonction_708(){ //Module Budget : Valider le Budget
  $liste_type_budget = getTypBudgetFromTabBudget("in (2,4)",true);
  if ($liste_type_budget != null){
    return true;
  }
  else{
    return false;
  }
}

function check_fonction_712(){ //Module Budget : Visualisation du Budget
  $liste_type_budget = getTypBudgetFromTabBudget(null,true);
  if ($liste_type_budget != null){
    return true;
  }
  else{
    return false;
  }
}

function check_fonction_716(){ //Module Budget : Mise en place nouveau ligne budgetaire
  $countLigne = countNouvelleLigneBudgetaire(1);
  if ($countLigne > 0){
    return true;
  }
  else{
    return false;
  }
}

function check_fonction_717(){ //Module Budget : validation nouveau ligne budgetaire
  $countLigne = countNouvelleLigneBudgetaire(2);
  if ($countLigne > 0){
    return true;
  }
  else{
    return false;
  }
}

function check_fonction_59() { //Autoriser ouverture des comptes et ordres permanents sans frais?
  global $global_id_profil;
  // on verifie dans la base pour le profil s'il y access au fonction 59
  if (checkAcessFunc(59,$global_id_profil)){
    return true;
  }
  return false;
}

/**
 * [242] : Accès au menu Rapport Etat de la compensation des operations en deplace
 *
 * Vérifie l'accès au menu Rapport Etat de la compensation des operations en deplace.  Les conditions sont :
 * - mode : compensation au siège
 *
 * @return bool vrai si le menu doit être affiché, faux sinon.
 */

function check_fonction_242() {

  require_once('lib/misc/divers.php');

  $return_val = false;

  // Vérification si la compensation se fait dans l'agence siège
  if(isCompensationSiege() && isCurrentAgenceSiege()) {
    $return_val = true;
  }

  return $return_val;
}

?>
