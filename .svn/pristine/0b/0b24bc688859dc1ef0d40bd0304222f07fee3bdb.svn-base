<?php

/**
 * Renvoie une liste de prestataires eWallet
 * 
 * @return array Tableau associatif avec les prestataires eWallet trouvés.
 */
function getListPrestataireEwallet() {
    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();
    $sql = sprintf("SELECT * FROM ad_ewallet WHERE id_ag=%d ORDER BY nom_prestataire ASC", $global_id_agence);

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);

    if ($result->numRows() == 0) {
        return NULL;
    }

    $tmp_arr = array();
    while ($prestataire = $result->fetchrow(DB_FETCHMODE_ASSOC)) {

        $tmp_arr[$prestataire["id_prestataire"]] = trim($prestataire["nom_prestataire"]);
    }

    return $tmp_arr;
}

/*
 * Cette l'id_prestataire a partir du code_prestataire
 */
function getPrestataire($code_prestaire) {

    global $dbHandler, $global_id_agence;

    $db = $dbHandler->openConnection();

    $sql = sprintf("SELECT p.* FROM adsys_prestataire p WHERE p.id_ag=%d AND code_prestataire = '%s' LIMIT 1", $global_id_agence, $code_prestaire);

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);
    if ($result->numRows() == 0) {
        return NULL;
    }

    $DATAS = $result->fetchrow(DB_FETCHMODE_ASSOC);

    return $DATAS;
}


/*
 * Cette fonction renvoie toutes les informations relatives à un client abonné
 */
function getClientAbonnementInfo($identifiant) {

    global $dbHandler, $global_id_agence;

    $db = $dbHandler->openConnection();

    $sql = sprintf("SELECT a.*,e.* FROM ad_abonnement a LEFT JOIN ad_ewallet e ON a.id_prestataire=e.id_prestataire WHERE a.id_ag=%d AND a.deleted='f' AND a.identifiant LIKE '%s' ORDER BY a.date_creation DESC LIMIT 1", $global_id_agence, $identifiant);

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);
    if ($result->numRows() == 0) {
        return NULL;
    }

    $DATAS = $result->fetchrow(DB_FETCHMODE_ASSOC);

    return $DATAS;
}

/**
 * Renvoie toutes les abonnements d'un client
 * 
 * @return array Tableau associatif avec les abonnements trouvés.
 */
function getListAbonnement() {
    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();
//    $sql = sprintf("SELECT * FROM ad_abonnement WHERE id_ag=%d AND deleted='f' AND id_client=%d ORDER BY id_abonnement ASC ", $global_id_agence, $global_id_client);
    $sql = sprintf(
        "SELECT A.id_abonnement, CASE WHEN A.id_prestataire IS NOT null THEN M.libelle || ': ' || E.nom_prestataire ELSE M.libelle END AS libelle, E.nom_prestataire " .
        "FROM ad_abonnement A " .
        "INNER JOIN adsys_mobile_service M ON M.id_service = A.id_service " .
        "LEFT JOIN ad_ewallet E on E.id_prestataire = A.id_prestataire AND E.id_ag = A.id_ag " .
        "WHERE A.id_ag=%d " .
        "AND A.deleted='f' " .
        "AND A.id_client=%d " .
        "ORDER BY A.id_service ASC, A.id_prestataire ASC, A.id_abonnement ASC",
        $global_id_agence, $global_id_client
    );

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);

    if ($result->numRows() == 0) {
        return NULL;
    }

    $tmp_arr = array();
    while ($abonnement = $result->fetchrow(DB_FETCHMODE_ASSOC)) {

        $tmp_arr[$abonnement["id_abonnement"]] = $abonnement["libelle"]; //$abonnement;
    }

    return $tmp_arr;
}

function getListMobileService($includeEstatement = true) {
    global $dbHandler, $global_id_agence;

    $db = $dbHandler->openConnection();
    $sql = sprintf("SELECT * FROM adsys_mobile_service WHERE id_ag=%d ", $global_id_agence);
    $sql .= $includeEstatement === false ? "AND id_service NOT IN (SELECT id_service from adsys_mobile_service WHERE code = 'ESTATEMENT') " : "";
    $sql .= "ORDER BY id_service ASC ";

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);

    if ($result->numRows() == 0) {
        return NULL;
    }

    $tmp_arr = array();
    while ($abonnement = $result->fetchrow(DB_FETCHMODE_ASSOC)) {

        $tmp_arr[$abonnement["id_service"]] = $abonnement["libelle"];
    }

    return $tmp_arr;
}

function getAvailablePrestataire($distinct = true) {
    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();
    $sql = "SELECT e.id_prestataire, e.nom_prestataire FROM ad_ewallet e ";
    if ($distinct) {
    $sql .= " WHERE e.id_prestataire NOT IN (SELECT COALESCE(A.id_prestataire,0) FROM ad_abonnement A WHERE A.id_ag=".$global_id_agence." AND A.deleted='f' AND A.id_client=".$global_id_client." ) ";
    }
    $sql .= " ORDER BY e.id_prestataire ASC";

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);

    if ($result->numRows() == 0) {
        return NULL;
    }

    $tmp_arr = array();
    while ($abonnement = $result->fetchrow(DB_FETCHMODE_ASSOC)) {

        $tmp_arr[$abonnement["id_prestataire"]] = $abonnement["nom_prestataire"]; //$abonnement;
    }

    return $tmp_arr;
}

function getAvailableServices() {
    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();
    $sql = sprintf(
        "SELECT e.id_service from adsys_mobile_service e ".
        "WHERE e.id_service not in " .
        "(SELECT COALESCE(A.id_service,0) FROM ad_abonnement A WHERE A.id_ag=%d AND A.deleted='f' AND A.id_client=%d )",
        $global_id_agence, $global_id_client
    );

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);

    if ($result->numRows() == 0) {
        return NULL;
    }

    $tmp_arr = array();
    while ($service = $result->fetchrow(DB_FETCHMODE_ASSOC)) {

        $tmp_arr[$service["id_service"]] = $service["id_service"];
    }

    return $tmp_arr;
}

/*
 * Cette fonction renvoie toutes les informations relatives à un abonnement
 */
function getAbonnementData($id_abonnement) {

    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();

    $sql = sprintf("SELECT * FROM ad_abonnement A " .
        "LEFT JOIN ad_ewallet E on E.id_prestataire = A.id_prestataire AND E.id_ag = A.id_ag " .
        "WHERE A.id_ag=%d AND A.deleted='f' AND A.id_client=%d AND A.id_abonnement=%d", $global_id_agence, $global_id_client, $id_abonnement);

    $result = $db->query($sql);
    if (DB::isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);
    if ($result->numRows() == 0) {
        return NULL;
    }

    $DATAS = $result->fetchrow(DB_FETCHMODE_ASSOC);

    return $DATAS;
}

/*
 * Vérifié si l'email existe
 */
function isEmailExist($email) { // Renvoie true si un numéro sms existe

    global $dbHandler, $global_id_agence;

    $db = $dbHandler->openConnection();

    $sql = "SELECT count(*) FROM ad_abonnement WHERE id_ag=$global_id_agence AND deleted='f' AND estatement_email = '$email'";

    $result = $db->query($sql);
    if (DB :: isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }
    $row = $result->fetchrow();
    $dbHandler->closeConnection(true);

    return ($row[0] > 0);
}

/*
 * Vérifié si le numéro sms existe
 */
function isNumSmsExist($num_sms, $id_client=null) { // Renvoie true si un numéro sms existe

    global $dbHandler, $global_id_agence;

    $db = $dbHandler->openConnection();

    $sql = "SELECT count(*) FROM ad_abonnement WHERE id_ag=$global_id_agence AND deleted='f' AND num_sms = '$num_sms'";
    
    if ($id_client != null) {
        $sql .= " AND id_client<>".$id_client;
    }

    $result = $db->query($sql);
    if (DB :: isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }
    $row = $result->fetchrow();
    $dbHandler->closeConnection(true);

    return ($row[0] >= 1);
}

/*
 * Recup l'email d'un client
 */
function getEmailByClientId($id_client) {

    global $dbHandler, $global_id_agence;

    $db = $dbHandler->openConnection();

    $sql = "SELECT estatement_email FROM ad_abonnement WHERE id_ag=$global_id_agence AND deleted='f' AND id_client = $id_client AND estatement_email IS NOT NULL LIMIT 1";
    $result = $db->query($sql);

    if (DB :: isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }

    $dbHandler->closeConnection(true);
    if ($result->numRows() == 0) {
        return NULL;
    }

    $row = $result->fetchrow();
    return $row[0];
}

/*
 * Recup le numéro sms d'un client
 */
function getNumSmsByClientId($id_client) { 

    global $dbHandler, $global_id_agence;

    $db = $dbHandler->openConnection();

    $sql = "SELECT num_sms FROM ad_abonnement WHERE id_ag=$global_id_agence AND deleted='f' AND id_client = $id_client LIMIT 1";

    $result = $db->query($sql);
    if (DB :: isError($result)) {
        $dbHandler->closeConnection(false);
        signalErreur(__FILE__, __LINE__, __FUNCTION__);
    }
    
    $dbHandler->closeConnection(true);
    if ($result->numRows() == 0) {
        return NULL;
    }

    $row = $result->fetchrow();

    return $row[0];
}

function validateNumSms($num_sms) {

    $isNumSmsValid = FALSE;

    $num_sms_test = preg_replace('/[\t\n\r\s\+\-]+/i', '', trim($num_sms));

    if (substr($num_sms_test, 0, 2) == '07') {
        $num_sms_test = '25'.$num_sms_test;

        $isNumSmsValid = TRUE;
    }

    if (strlen($num_sms_test) < 12 || strlen($num_sms_test) > 12) {
        $isNumSmsValid = FALSE;
    }

    return $isNumSmsValid;
}

function nullToFalse($val) {

    if($val === null || $val == '' || $val == '0') {
        return 'f';
    }
    return 't';
}

/**
 * Insèrer/modifier un nouvel abonnement dans la table ad_abonnement
 *
 * @param String $mode : ajouter / modifier
 * @param String $num_sms : Le numéro sms d'un client
 * @param Integer $langue : Identifiant de la langue
 * @param Boolean $ewallet : Est abonné à eWallet? True/False
 * @param Integer $id_prestataire : Identifiant du prestataire
 * @param String $password : Le mot de passe
 * @param Integer $id_abonnement : Identifiant de d'abonnement
 * 
 * @return ErrorObj = NO_ERR si tout s'est bien passé, SignalErreur si pb de la BD
 */
function handleAbonnement($mode, $num_sms=null, $langue, $ewallet, $id_prestataire, $password=null, $id_abonnement=null, $id_service, $estatement_email=null, $estatement_journalier=null, $estatement_hebdo=null, $estatement_mensuel=null) {

    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();

    // Build insert string
    $id_client = $global_id_client;
    $id_ag = $global_id_agence;
    
    $sql = null;
    $sql_update_ad_cli = null;

    $tableName = "ad_abonnement";
    if ($mode == "modifier" && $id_abonnement !== null && $id_abonnement > 0) {
        if($id_service === "1") {
            $tableFields = array("langue" => $langue, "ewallet" => nullToFalse($ewallet), "id_prestataire" => $id_prestataire); // "num_sms" => trim($num_sms), 
            
            // Update field num_tel in table ad_cli
            //$sql_update_ad_cli = buildUpdateQuery("ad_cli", array("num_tel" => trim($num_sms)), array('id_client' => $id_client, 'id_ag' => $id_ag));

            if (trim($password) != '') {
                $salt = generateRandomString(generateIdentifiant());
                $motdepasse = encodePassword(trim($password), $salt);

                $tableFields["motdepasse"] = $motdepasse;
                $tableFields["salt"] = $salt;
            }
        } else {

            $tableFields = array(
                "langue" => $langue,
                //"id_service" => $id_service,
                //"estatement_email" => $estatement_email,
                "estatement_journalier" => nullToFalse($estatement_journalier),
                "estatement_hebdo" => nullToFalse($estatement_hebdo),
                "estatement_mensuel" => nullToFalse($estatement_mensuel),
            );
        }
        $sql = buildUpdateQuery($tableName, $tableFields, array('id_client' => $id_client, 'id_ag' => $id_ag, 'id_abonnement' => $id_abonnement));
    }
    elseif ($mode == "ajouter") {

        $identifiant = generateIdentifiant();

        if($id_service === "1") {
            //SMS_BANKING
            $tableFields = array(
                "id_client" => $id_client,
                "id_ag" => $id_ag,
                "identifiant" => $identifiant,
                "num_sms" => trim($num_sms),
                "langue" => $langue,
                "ewallet" => nullToFalse($ewallet),
                "id_prestataire" => $id_prestataire,
                "id_service" => $id_service,
            );
            
            // Update field num_tel in table ad_cli
            //$sql_update_ad_cli = buildUpdateQuery("ad_cli", array("num_tel" => trim($num_sms)), array('id_client' => $id_client, 'id_ag' => $id_ag));

            if (trim($password)!='') {
                $salt = generateRandomString(generateIdentifiant());
                $motdepasse = encodePassword(trim($password), $salt);

                $tableFields["motdepasse"] = $motdepasse;
                $tableFields["salt"] = $salt;
            }
        } else {
            //E-STATEMENT
            $tableFields = array(
                "id_client" => $id_client,
                "id_ag" => $id_ag,
                "langue" => $langue,
                "identifiant" => $identifiant,
                "id_service" => $id_service,
                "estatement_email" => $estatement_email,
                "estatement_journalier" => nullToFalse($estatement_journalier),
                "estatement_hebdo" => nullToFalse($estatement_hebdo),
                "estatement_mensuel" => nullToFalse($estatement_mensuel),
            );
            
            // Update field email in table ad_cli
            $sql_update_ad_cli = buildUpdateQuery("ad_cli", array("email" => trim($estatement_email)), array('id_client' => $id_client, 'id_ag' => $id_ag));
        }
        $sql = buildInsertQuery($tableName, $tableFields);
    }

    /*
    var_dump($sql);
    exit;
    */

    if ($sql != null) {
        $result = $db->query($sql);

        if (DB :: isError($result)) {
            $dbHandler->closeConnection(false);
            signalErreur(__FILE__, __LINE__, __FUNCTION__);
        } else {
            if ($sql_update_ad_cli != null) {
                $result2 = $db->query($sql_update_ad_cli);

                if (DB :: isError($result2)) {
                    $dbHandler->closeConnection(false);
                    signalErreur(__FILE__, __LINE__, __FUNCTION__);
                }
            }            
        }
        //$dbHandler->closeConnection(true);
    }

    return new ErrorObj(NO_ERR);
}

/**
 * Ré-initialiser un mot de passe
 *
 * @param Integer $id_abonnement : Identifiant de d'abonnement
 * 
 * @return ErrorObj = NO_ERR si tout s'est bien passé, SignalErreur si pb de la BD
 */
function resetMotDePasse($id_abonnement) {
    
    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();

    // Build insert string
    $id_client = $global_id_client;
    $id_ag = $global_id_agence;
    
    $sql = null;

    $tableName = "ad_abonnement";

    $tableFields = array(
        "date_mdp" => 'NOW()'
    );

    $sql = buildUpdateQuery($tableName, $tableFields, array('id_client' => $id_client, 'id_ag' => $id_ag, 'id_abonnement' => $id_abonnement));

    if ($sql != null) {
        $result = $db->query($sql);
        if (DB :: isError($result)) {
            $dbHandler->closeConnection(false);
            signalErreur(__FILE__, __LINE__, __FUNCTION__);
        }
        $dbHandler->closeConnection(true);
    }

    return new ErrorObj(NO_ERR);
}

/**
 * Delete an abonnement
 *
 * @param Integer $id_abonnement : Identifiant de d'abonnement
 * 
 * @return ErrorObj = NO_ERR si tout s'est bien passé, SignalErreur si pb de la BD
 */
function deleteAbonnement($id_abonnement) {
    
    global $dbHandler, $global_id_agence, $global_id_client;

    $db = $dbHandler->openConnection();

    // Build insert string
    $id_client = $global_id_client;
    $id_ag = $global_id_agence;
    
    $sql = null;

    $tableName = "ad_abonnement";

    $tableFields = array(
        "deleted" => 't'
    );

    $sql = buildUpdateQuery($tableName, $tableFields, array('id_client' => $id_client, 'id_ag' => $id_ag, 'id_abonnement' => $id_abonnement));

    if ($sql != null) {
        $result = $db->query($sql);
        if (DB :: isError($result)) {
            $dbHandler->closeConnection(false);
            signalErreur(__FILE__, __LINE__, __FUNCTION__);
        }
        $dbHandler->closeConnection(true);
    }

    return new ErrorObj(NO_ERR);
}

/* Générer Identifiant automatiquement à partir de l'id_agence et l'id_client de la base ADBanking */
function generateIdentifiant() {

    global $global_id_agence, $global_id_client;

    return sprintf("%s%s", trim($global_id_agence), str_pad(trim($global_id_client), 8, "0", STR_PAD_LEFT));
}

/* Encoder un mot de passe avec l'algorithme sha512 */
function encodePassword($plain_password, $salt) {
    
    $algorithm = 'sha512'; // Encryption algorithm
    $iterations = 5000; // Number of iterations to use to stretch the password hash
    
    $salted = $plain_password.'{'.$salt.'}';
    $digest = hash($algorithm, $salted, true);

    // "stretch" hash
    for ($i = 1; $i < $iterations; $i++) {
        $digest = hash($algorithm, $digest.$salted, true);
    }

    return base64_encode($digest);
}

/* Générer une chaine de texte aléatoirement */
function generateRandomString($str) {
    return md5(uniqid(mt_rand(0, 99999)) . $str);
}



/*
 * Check if the id_cpte is related to a client subscribed to SMS service
*/
function checkClientAbonnement($cpte_interne_cli){
  global $dbHandler, $global_id_agence;

  $db = $dbHandler->openConnection();

  $sql = sprintf(
    "SELECT cli.id_client
            FROM ad_cpt cpt
            JOIN ad_cli cli ON cpt.id_titulaire = cli.id_client
            JOIN ad_abonnement a ON cli.id_client = a.id_client
            WHERE a.id_ag = %d
            AND cpt.id_cpte = %s
            AND a.id_service = 1
            AND a.deleted = FALSE",
    $global_id_agence, $cpte_interne_cli
  );

  $result = $db->query($sql);
  if (DB::isError($result)) {
    $dbHandler->closeConnection(false);
    signalErreur(__FILE__, __LINE__, __FUNCTION__);
  }

  $dbHandler->closeConnection(true);
  if ($result->numRows() == 0) {
    return NULL;
  }

  $DATAS = $result->fetchrow(DB_FETCHMODE_ASSOC);

  return $DATAS;
}