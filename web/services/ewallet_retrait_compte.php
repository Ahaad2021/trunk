<?php
/*$return_data = array(
  'success' => true,
  'datas' => array(
  'identifiant_client' => $_POST['identifiant_client'],
  'id_agence_source' => $_POST['id_agence_source'],
  'id_compte_source' => $_POST['id_compte_source'],
  'num_compte_cible' => rawurldecode($_POST['num_compte_cible']),
  'montant' => $_POST['montant'],
  'libelle' => rawurldecode($_POST['libelle']),
  'type_action' => $_POST['type_action'],
  ),
);

echo json_encode($return_data, 1 | 4 | 2 | 8);
exit;*/


// Get posted data
$identifiant_client = trim($_REQUEST['identifiant_client']);
$id_agence_source = trim($_REQUEST['id_agence_source']);
$id_compte_source = trim($_REQUEST['id_compte_source']);
$type_action = trim($_REQUEST['type_action']);
$montant = trim($_REQUEST['montant']);
$libelle = trim($_REQUEST['libelle']);
$id_transaction_mtn = trim($_REQUEST['id_transaction_mtn']);

// Strip client id from identifiant and set global_id_client
$id_client = intval(substr($identifiant_client, -8));
//$id_agence_source = strstr($identifiant_client, substr($identifiant_client, -8), true);

// Load ini data for agence
$_REQUEST['m_agc'] = $id_agence_source;

$params = "identifiant_client: $identifiant_client, id_client : $id_client, id_agence_source: $id_agence_source, id_compte_source: $id_compte_source, type_action: $type_action, montant: $montant, libelle: $libelle";
$error_msg = "";

// On charge les variables globales
require_once 'lib/misc/VariablesGlobales.php';
require_once 'lib/misc/VariablesSession.php';
require_once 'lib/misc/Erreur.php';
require_once 'lib/dbProcedures/agence.php';
require_once 'lib/dbProcedures/abonnement.php';
require_once 'lib/dbProcedures/tarification.php';
require_once 'lib/dbProcedures/transfert.php';
require_once 'lib/misc/access.php';
require_once 'services/misc_api.php';

$valeurs = getCustomLoginInfo();

$global_agence = $valeurs['libel_ag'];
$global_id_agence = $valeurs['id_ag'];
$global_nom_login = $valeurs['login'];
$global_monnaie = $valeurs['monnaie'];
$global_monnaie_prec = $valeurs['monnaie_prec'];
$global_monnaie_courante_prec = $valeurs['monnaie_prec'];
$global_monnaie_courante = $valeurs['monnaie'];
$global_remote_monnaie = $valeurs['monnaie'];
$global_remote_monnaie_courante = $valeurs['monnaie'];
$global_multidevise = $valeurs['multidevise'];
$global_last_axs = time();
$global_institution = $valeurs['institution'];
$global_type_structure = $valeurs['type_structure'];
$global_id_exo = $valeurs['exercice'];
$global_langue_systeme_dft = $valeurs['langue_systeme_dft'];
$global_langue_utilisateur = 'fr_BE'; //$valeurs['langue'];
$global_id_guichet = 1; // To create

require_once 'lib/dbProcedures/main_func.php';
require_once 'lib/dbProcedures/historique.php';
require_once 'lib/dbProcedures/epargne.php';
require_once 'lib/dbProcedures/client.php';
require_once 'lib/dbProcedures/compte.php';
require_once 'lib/misc/divers.php';
include_once 'lib/misc/debug.php';

$appli = "main"; // On est dans l'application (et pas dans le batch)

// Strip client id from identifiant and set global_id_client
$global_id_client = $id_client;

// Fonctions syst??mes ~ Retrait eWallet
$fonc_transfert_api = 99;
$type_oper = 118; // Retrait eWallet
$type_oper_frais = 187; // Frais retrait ewallet

$MyErr = $erreur = null;
$bloqMontant = false;
$deBloqMontant = false;
$doTransfert = false;
$out = 0;

// Check if clients from same agence
if ($id_agence_source == null || $id_agence_source == '') {
  $MyErr = new ErrorObj('ERR_CPTE_AUTRE_AGC');
  $out = 1;
} elseif ($montant <= 0) {
  $MyErr = new ErrorObj('ERR_MONTANT');
  $out = 1;
} else {
  // Check cpte source if exist for client source
  $cpte_src_arr = get_comptes_epargne($global_id_client);
  if (is_array($cpte_src_arr) && count($cpte_src_arr) > 0) {
    if ($cpte_src_arr[$id_compte_source]) {
      if ($cpte_src_arr[$id_compte_source]['id_titulaire'] != $global_id_client) {
        $MyErr = new ErrorObj('ERR_CPTE_SRC_INEXISTANT');
        $out = 4;
      } else {
        // Bloque montant
        if ($type_action == 1) {
          if ($cpte_src_arr[$id_compte_source]["soldeDispo"] < $montant) {
            $MyErr = new ErrorObj('ERR_SOLDE_SRC_INSUFFISANT');
            $out = 5;
          } else {
            $cpteSrc = getAccountDatas($id_compte_source);
            $prodSrc = getProdEpargne($cpteSrc['id_prod']);

            $MyErr = new ErrorObj(NO_ERR);

            $bloqMontant = true;
          }
        } elseif ($type_action == 2 || $type_action == 3) { // D??bloque montant & transfert eWallet
          if (($cpte_src_arr[$id_compte_source]["mnt_bloq"] - $montant) < 0) {
            $MyErr = new ErrorObj('ERR_SOLDE_SRC_INSUFFISANT');
            $out = 6;
          } else {
            $cpteSrc = getAccountDatas($id_compte_source);
            $prodSrc = getProdEpargne($cpteSrc['id_prod']);

            $MyErr = new ErrorObj(NO_ERR);

            $deBloqMontant = true;
          }
        }
      }
    } else {
      $MyErr = new ErrorObj('ERR_CPTE_SRC_INEXISTANT');
      $out = 3;
    }
  } else {
    $MyErr = new ErrorObj('ERR_CPTE_SRC_INEXISTANT');
    $out = 2;
  }
}

// Output error
if ($MyErr->errCode === NO_ERR) {

  // R??cup??ration du montant r??el
  $mnt_reel = recupMontant($montant);

  // Infos compte destination
  $InfoCpte = getAccountDatas($id_compte_source);
  $id_prod = $InfoCpte['id_prod'];

  if ($type_action == 1 && $bloqMontant == true) {
      // Ensuite v??rifier qu'on peut d??poser sur le compte destination
      $erreur = CheckRetraitEwalletMobile($InfoCpte, $mnt_reel);
  }
  else{
      $erreur = new ErrorObj(NO_ERR);
  }

  if ($erreur->errCode != NO_ERR) { // Erreur check retrait
    $error_msg = "ERREUR dans CheckRetraitEwallet: " . $params . ", mnt_reel: $mnt_reel";

    $dbHandler->closeConnection(false);
    $return_data = array(
      'success' => false,
      'datas' => array(
        'msg' => "errCode:" . $erreur->errCode . ", " . $error_msg,
      ),
    );
  }
  else // Check retrait OK
  {
    if ($type_action == 1 && $bloqMontant == true) {
      $erreur = bloqMontantCpte($id_compte_source, $mnt_reel);
      if ($erreur->errCode !== NO_ERR) {
        $error_msg = "ERREUR dans bloqMontantCpte: " . $params . ", mnt_reel: $mnt_reel";
      }
    } elseif (($type_action == 2 || $type_action == 3) && $deBloqMontant == true) {

      $erreur = debloqMontantCpte($id_compte_source, $mnt_reel);
      if ($erreur->errCode !== NO_ERR) {
        $error_msg = "ERREUR dans debloqMontantCpte: " . $params . ", mnt_reel: $mnt_reel";
      }
      if ($type_action == 2) {
        if ($erreur->errCode === NO_ERR) {
          $doTransfert = true;
        }

        if ($doTransfert == true) {
          // Passage des ??critures comptables : d??bit client / cr??dit client
          $comptable = array();
          $cptes_substitue = array();
          $cptes_substitue["cpta"] = array();
          $cptes_substitue["int"] = array();

          $type_fonction = $fonc_transfert_api; // Transfert eWallet

          // D??bit d'un compte client
          $cptes_substitue["cpta"]["debit"] = getCompteCptaProdEp($id_compte_source);
          if ($cptes_substitue["cpta"]["debit"] == NULL) {
            $dbHandler->closeConnection(false);
            $erreur = new ErrorObj('ERR_CPTE_NON_PARAM', _("compte comptable associ?? au produit d'??pargne"));
          }
          $cptes_substitue["int"]["debit"] = $id_compte_source;

          // Get prestatiare eWallet
          $client_info = getClientAbonnementInfo($identifiant_client);

          if (isset($client_info) && trim($client_info['compte_comptable']) != NULL) {

            // Cr??dit d'un compte produit
            $cptes_substitue["cpta"]["credit"] = trim($client_info['compte_comptable']);
            if ($cptes_substitue["cpta"]["credit"] == NULL) {
              $dbHandler->closeConnection(false);
              $erreur = new ErrorObj('ERR_CPTE_NON_PARAM', _("compte comptable associ?? au prestataire eWallet"));
            }

            $erreur = passageEcrituresComptablesAuto($type_oper, $mnt_reel, $comptable, $cptes_substitue, $cpteSrc['devise'], NULL, $id_transaction_mtn);
            if ($erreur->errCode !== NO_ERR) {
              $error_msg = "ERREUR dans passageEcrituresComptablesAuto: " . $params . ", mnt_reel: $mnt_reel, type_oper: $type_oper";
            }

            if ($erreur->errCode === NO_ERR) {
              $erreur = ajout_historique($type_fonction, $cpteSrc["id_titulaire"], 'Retrait eWallet', $global_nom_login, date("r"), $comptable);
              if ($erreur->errCode !== NO_ERR) {
                $error_msg = "ERREUR dans ajout_historique: " . $params . ", type_fonction: $type_fonction, cpteSrc: " . $cpteSrc["id_titulaire"] . ", global_nom_login: $global_nom_login, comptable: " . serialize($comptable);
              }
            }

          } else {
            $erreur = new ErrorObj('ERR_CPTE_NON_PARAM', _("compte comptable associ?? au prestataire eWallet"));
          }
        }
      }
    } else {
      $erreur = new ErrorObj('ERR_EWALLET');
    }

    if ($erreur->errCode === NO_ERR) {

      if ($bloqMontant == true) {
        $dbHandler->closeConnection(true);
        $return_data = array(
          'success' => true,
          'datas' => array(
            'msg' => sprintf("Le montant %s %s a ??t?? bloqu?? sur le compte : %s ", $mnt_reel, $cpteSrc['devise'], $cpteSrc['num_complet_cpte']),
            'id_his' => sprintf("%09d", $erreur->param),
          ),
        );
      } elseif ($deBloqMontant == true) {
        $dbHandler->closeConnection(true);

        if ($type_action == 2 && $doTransfert == true) {

          // Pr??l??ve frais transfert E-wallet
          $err = preleveFraisAbonnement('SMS_EWT', $global_id_client, $type_oper_frais, $mnt_reel, $type_fonction);

          if ($err->errCode !== NO_ERR) {

            $dbHandler->closeConnection(false);

            $return_data = array(
              'success' => false,
              'datas' => array(
                'msg' => 'ERROR in preleveFraisAbonnement:' . $err->param,
              ),
            );
          } else {

            //recuperation solde compte
            $accountDatas = getAccountDatas($id_compte_source);
            $solde = $accountDatas['solde'];
            $solde = arrondiMonnaiePrecision($solde);

            $return_data = array(
              'success' => true,
              'datas' => array(
                'msg' => "N?? de transaction : " . sprintf("%09d", $erreur->param),
                'id_his' => sprintf("%09d", $erreur->param),
                'solde' => $solde,
              ),
            );
          }
        } elseif ($type_action == 3) {
          $return_data = array(
            'success' => true,
            'datas' => array(
              'msg' => sprintf("Le montant %s %s a ??t?? d??bloqu?? sur le compte : %s ", $mnt_reel, $cpteSrc['devise'], $cpteSrc['num_complet_cpte']),
            ),
          );
        }
      } else {
        $dbHandler->closeConnection(false);
        $return_data = array(
          'success' => false,
          'datas' => array(
            'msg' => "ERR_EWALLET",
          ),
        );
      }
    } else {

      $dbHandler->closeConnection(false);

      $return_data = array(
        'success' => false,
        'datas' => array(
          'msg' => $error_msg . ", errCode:" . $erreur->errCode,
          'param' => $erreur->param,
        ),
      );
    }
  }
} else {

  $dbHandler->closeConnection(false);
  $return_data = array(
    'success' => false,
    'datas' => array(
      'msg' => $MyErr->errCode,
    ),
  );
}

echo json_encode($return_data, 1 | 4 | 2 | 8);
exit;
