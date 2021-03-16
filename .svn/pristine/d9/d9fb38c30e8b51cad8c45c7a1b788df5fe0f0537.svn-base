<?php

    // Custom errors
    define("ERR_CPTE_AUTRE_AGC", 1100);
    define("ERR_CPTE_SRC_INEXISTANT", 1101);
    define("ERR_SOLDE_SRC_INSUFFISANT", 1102);
    define("ERR_NUM_COMPLET_CPTE_DEST_NOT_EXIST", 1103);
    define("ERR_NUM_COMPLET_CPTE_DEST", 1104);
    define("ERR_CPTE_DEST_INEXISTANT", 1105);
    define("ERR_NUM_CPTE_SRC_DEST", 1106);

    $error[ERR_CPTE_AUTRE_AGC] = "Un transfert entre comptes ne peut être effectué que dans une même agence"; // 1100
    $error[ERR_CPTE_SRC_INEXISTANT] = "Le compte source n'existe pas"; // 1101
    $error[ERR_SOLDE_SRC_INSUFFISANT] = _("Le solde compte source est insuffisant"); // 1102
    $error[ERR_NUM_COMPLET_CPTE_DEST_NOT_EXIST] = _("Le compte cible n'existe pas"); // 1103
    $error[ERR_NUM_COMPLET_CPTE_DEST] = _("Numéro de compte cible invalide"); // 1104
    $error[ERR_CPTE_DEST_INEXISTANT] = "Le compte cible n'existe pas"; // 1105
    $error[ERR_NUM_CPTE_SRC_DEST] = "Opération impossible sur le même compte"; // 1106

    function getCustomLoginInfo()
    {
        global $dbHandler, $global_id_agence;
        $db = $dbHandler->openConnection();

        // Recherche agence et login
        $retour['login'] = 'api';

        //Recherche info agence
        $retour["id_ag"] = getNumAgence();

        $dataAgence = getAgenceDatas($retour["id_ag"]);

        $retour['libel_ag'] = $dataAgence['libel_ag'];
        $retour['statut_ag'] = $dataAgence['statut'];
        $retour['institution'] = $dataAgence['libel_institution'];
        $retour['type_structure'] = $dataAgence['type_structure'];
        $retour['exercice'] = $dataAgence['exercice'];
        $retour['langue_systeme_dft'] = $dataAgence['langue_systeme_dft'];

        // Recherche infos devise de référence
        $sql = "SELECT code_devise, precision FROM devise WHERE id_ag = " . $retour["id_ag"] . " and code_devise = (SELECT code_devise_reference FROM ad_agc WHERE id_ag =" . $retour["id_ag"] . ")";
        $result = $db->query($sql);
        if (DB::isError($result)) {
            $dbHandler->closeConnection(false);
            signalErreur(__FILE__, __LINE__, __FUNCTION__);
        } elseif ($result->numrows() <> 1) {
            //   echo "<FONT COLOR=red> ATTENTION, un devise de référence doit être paramétrée</FONT>";
        }
        $row = $result->fetchrow();
        $retour['monnaie'] = $row[0];
        $retour['monnaie_prec'] = $row[1];

        // Sommes-nous en mode unidevise ou multidevise
        $sql = "SELECT count(*) FROM devise WHERE id_ag =" . $retour["id_ag"];

        $result = $db->query($sql);
        if (DB::isError($result)) {
            $dbHandler->closeConnection(false);
            signalErreur(__FILE__, __LINE__, __FUNCTION__);
        }

        $row = $result->fetchrow();

        if ($row[0] > 1) {
            $retour['multidevise'] = 1;
        } else {
            $retour['multidevise'] = 0;
        }

        $dbHandler->closeConnection(true);

        return $retour;
    }
