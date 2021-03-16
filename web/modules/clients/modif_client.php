<?php
/* vim: set expandtab softtabstop=2 shiftwidth=2 foldmethod=marker: */

/* [10] Modification d'un client existant. Modification de ses relations
 * Cette opération comprends les écrans :
 * - Mcl-1 : Modification données d'un client
 * - Mcl-2 : Confirmation de la mise à jour du client
 * @since 06/12/2001
 * @package Clients
 **/

require_once('lib/dbProcedures/client.php');
require_once('lib/dbProcedures/agence.php');
require_once('lib/dbProcedures/compte.php');
require_once('lib/dbProcedures/epargne.php');
require_once('lib/misc/divers.php');
require_once('lib/misc/VariablesSession.php');
require_once('lib/html/HTML_GEN2.php');
require_once('lib/html/FILL_HTML_GEN2.php');
require_once 'lib/html/HTML_champs_extras.php';
require_once 'lib/dbProcedures/parametrage.php';


/*{{{ Mcl-1 : Modification données d'un client */
if ($global_nom_ecran == "Mcl-1") {
  //recuperation des données de l'agence'
  global $global_id_agence, $global_id_client;
  $AG = getAgenceDatas($global_id_agence);  
  // Récupération des valeurs envoyées
  if ($global_nom_ecran_prec == "Mcl-1" && isset($date_crea)) {
    foreach ($SESSION_VARS["POSTED_DATAS"] as $key => $fieldname)
      $SESSION_VARS[$fieldname] = $ {$fieldname};
    if ($SESSION_VARS["statut_juridique"] == 4)
      $SESSION_VARS["nbr_membres_encodes"] = $nbr_membres_encodes;
  } else {
    // Ou récupération des données actuelles du client
    $CLI = getClientDatas($global_id_client);
    $CLI['id_client']=makeNumClient($CLI['id_client']);
    $SESSION_VARS["statut_juridique"] = $CLI["statut_juridique"];
    $SESSION_VARS["pp_etat_civil"] = $CLI["pp_etat_civil"];
    $SESSION_VARS["qualite"] = $CLI["qualite"];
    $result = getListeMembresGrpSol($CLI["id_client"]);
    $SESSION_VARS["membres_grp_sol"] = $result->param;
    $SESSION_VARS["nbr_membres_encodes"] = sizeof($SESSION_VARS["membres_grp_sol"]);
  }
  // champsextras
  if ( !isset($SESSION_VARS['champsExtrasValues'])) {
    $SESSION_VARS['champsExtrasValues'] = getChampsExtrasCLIENTValues($global_id_client);
  }
  $AGC = getAgenceDatas($global_id_agence);

  // Création du formulaire
  $myForm = new HTML_GEN2(_("Modification du client"));
  $Order = array ("statut_juridique", "id_client", "anc_id_client","matricule", "date_adh", "date_crea", "langue_correspondance", "gestionnaire");
  $labels = array("id_client" => "", "statut_juridique" => "", "etat" => "");

  // Les champs à exclure pour tous les stautts juridiques
  if ($AGC['identification_client']==2){
    $exclude = array("raison_defection", "gi_date_dissol", "nbre_parts", "raison_defection", "date_defection", "classe","id_loc1","id_loc2","loc3","district","secteur","cellule","village");
  }else{
    $exclude = array("raison_defection", "gi_date_dissol", "nbre_parts", "raison_defection", "date_defection", "classe","id_loc2","province","district","secteur","cellule","village","classe_socio_economique","education");
  }

  array_push ($exclude, "id_cpte_base", "date_rupt", "dern_modif", "utilis_modif", "utilis_crea", "nbre_credits");

  if ($SESSION_VARS["statut_juridique"] == 1) {     // Personne physique
    global $global_photo_client, $global_signature_client;

    $myForm->addField("signature",_("Spécimen de signature"),TYPC_IMG);
    if ($global_signature_client != "")
      $myForm->setFieldProperties('signature', FIELDP_IMAGE_URL, $global_signature_client);
    $myForm->addField("photo",_("Photographie"),TYPC_IMG);
    if ($global_photo_client != "")
      $myForm->setFieldProperties('photo', FIELDP_IMAGE_URL, $global_photo_client);
    array_push($exclude, "pm_raison_sociale", "pm_abreviation", "gi_nom", "gi_date_agre", "gi_nbre_membr","pm_categorie", "pm_date_expiration", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_nature_juridique", "pm_tel2", "pm_tel3", "pm_email2", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date", "gs_responsable", "nbre_hommes_grp", "nbre_femmes_grp");
    array_push($Order, "pp_nom", "pp_prenom", "pp_date_naissance", "pp_lieu_naissance", "pp_nationalite", "pp_pays_naiss", "pp_sexe", "pp_type_piece_id", "pp_nm_piece_id", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_etat_civil", "pp_nbre_enfant");
    if ($AGC['identification_client'] == 2){
      array_push($Order, "adresse", "code_postal", "ville", "pays","num_tel", "num_fax", "num_port", "email","province","district","secteur","cellule","village", "education", "classe_socio_economique");
    }else{
      array_push($Order, "adresse", "code_postal", "ville", "pays","num_tel", "num_fax", "num_port", "email", "id_loc1", "id_loc2", "loc3");
    }
    array_push($Order, "sect_act", "pp_pm_activite_prof", "pp_fonction", "pp_employeur", "pp_partenaire","categorie","classe","langue", "pp_revenu", "pp_pm_patrimoine", "pp_casier_judiciaire", "pp_is_vip", "pp_id_gi", "nb_imf", "nb_bk", "etat","qualite", "commentaires_cli","mnt_quotite","id_card");
  } else if ($SESSION_VARS["statut_juridique"] == 2) { // Personne morale
    array_push($exclude, "pp_nom", "pp_prenom", "pp_date_naissance", "pp_lieu_naissance", "pp_pays_naiss", "pp_nationalite", "pp_sexe", "pp_type_piece_id",  "pp_nm_piece_id", "pp_etat_civil", "pp_nbre_enfant", "pp_casier_judiciaire", "pp_is_vip", "pp_revenu", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_employeur","pp_partenaire", "pp_fonction", "gi_nom", "gi_date_agre", "gi_nbre_membr", "pp_id_gi", "langue", "photo", "signature", "gs_responsable", "education", "classe_socio_economique");
    if ($AGC['identification_client'] == 2){
      array_push($Order, "pm_raison_sociale", "pm_abreviation", "adresse", "code_postal", "ville", "pays", "num_tel", "pm_tel2", "pm_tel3", "num_fax", "num_port", "email", "pm_email2", "province","district","secteur","cellule","village");
    }else{
      array_push($Order, "pm_raison_sociale", "pm_abreviation", "adresse", "code_postal", "ville", "pays", "num_tel", "pm_tel2", "pm_tel3", "num_fax", "num_port", "email", "pm_email2", "id_loc1", "id_loc2", "loc3");
    }
    array_push($Order, "pm_categorie", "pm_nature_juridique", "sect_act", "pp_pm_activite_prof", "pp_pm_patrimoine", "nb_imf", "nb_bk", "nbre_hommes_grp", "nbre_femmes_grp", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_date_expiration", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date", "etat", "qualite", "commentaires_cli");
  } else if ($SESSION_VARS["statut_juridique"] == 3) { // Groupe informel
    array_push($exclude, "pp_nom", "pp_prenom", "pp_date_naissance", "pp_lieu_naissance", "pp_sexe", "pp_type_piece_id",  "pp_nm_piece_id", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_fonction", "pp_employeur", "pp_partenaire", "pp_fonction", "pp_pays_naiss", "pp_nationalite", "pp_etat_civil", "pp_nbre_conjoint", "pp_nbre_enfant", "pp_casier_judiciaire", "pp_is_vip", "pp_revenu", "pp_pm_patrimoine", "pp_pm_activite_prof", "pm_raison_sociale", "pm_abreviation", "pp_id_gi","pm_categorie", "pm_date_expiration", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_nature_juridique", "pm_tel2", "pm_tel3", "pm_email2", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date" ,"photo", "signature", "gs_responsable", "education", "classe_socio_economique");
    if ($AGC['identification_client'] == 2){
      array_push($Order, "gi_nom", "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "province","district","secteur","cellule","village", "sect_act", "langue", "gi_nbre_membr", "nbre_hommes_grp", "nbre_femmes_grp", "nb_imf", "nb_bk", "gi_date_agre", "etat", "qualite", "commentaires_cli");
    }else{
      array_push($Order, "gi_nom", "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "id_loc1", "id_loc2", "loc3", "sect_act", "langue", "gi_nbre_membr", "nbre_hommes_grp", "nbre_femmes_grp", "nb_imf", "nb_bk", "gi_date_agre", "etat", "qualite", "commentaires_cli");
    }
  } else if ($SESSION_VARS["statut_juridique"] == 4) { // Groupe solidaire
    array_push($exclude, "pp_nom", "pp_prenom", "pp_date_naissance", "pp_lieu_naissance", "pp_sexe", "pp_type_piece_id",  "pp_nm_piece_id", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_fonction", "pp_employeur", "pp_partenaire", "pp_fonction", "pp_pays_naiss", "pp_nationalite", "pp_etat_civil", "pp_nbre_conjoint", "pp_nbre_enfant", "pp_casier_judiciaire", "pp_is_vip", "pp_revenu", "pp_pm_patrimoine", "pp_pm_activite_prof", "pm_raison_sociale", "pm_abreviation", "pp_id_gi","pm_categorie", "pm_date_expiration", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_nature_juridique", "pm_tel2", "pm_tel3", "pm_email2", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date" ,"photo", "signature", "gi_date_agre", "gi_nbre_membr", "nbre_hommes_grp", "nbre_femmes_grp", "education", "classe_socio_economique");
    if ($AGC['identification_client'] == 2){
      array_push($Order, "gi_nom", "gs_responsable","adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "province","district","secteur","cellule","village", "sect_act", "langue", "nb_imf", "nb_bk", "etat", "qualite", "commentaires_cli");
    }else{
      array_push($Order, "gi_nom", "gs_responsable","adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "id_loc1", "id_loc2", "loc3", "sect_act", "langue", "nb_imf", "nb_bk", "etat", "qualite", "commentaires_cli");
    }
  }
  $myForm->addTable("ad_cli", OPER_EXCLUDE, $exclude);

  if ($SESSION_VARS["statut_juridique"] == 1) {
    //Ajout d'un hidden Field pour la validation des piece d'identité
    $myForm->addHiddenType("char_length_hidden");

    //liste des pièces d'identité et leurs nombre de caractères
    $listPieceIdentLen = getListPieceIdentLength();

    $myForm->setFieldProperties("pp_type_piece_id", FIELDP_JS_EVENT, array("onchange" => "getCharLength()"));

    $js1 = "document.onload =getCharLength();\n";

    //Fonction JavaScript pour trouver le type de pièce d'identité choisie et le nombre de caractères correspondant
    $js1 .= "function lookup( name , arr)
            {
                for(var i = 0, len = arr.length; i < len; i++)
                {
                    if( arr[ i ].key == name )
                    {
                    return arr[ i ].value;
                    }
                }
                return false;
            };\n ";

    $js1 .= "function getCharLength(){ \n var myArray = [\n";

    //fonction qui construit un tableau en javascript contenant les pièces d'identité et leurs nombre de caractères respectifs.
    foreach ($listPieceIdentLen as $key => $value) {
      $js1 .= "{ key: $key, value: $value },";
    }

    $js1 .= "];\n";

    $js1 .= " document.ADForm.char_length_hidden.value='';\n";
    $js1 .= " if( lookup(document.ADForm.HTML_GEN_LSB_pp_type_piece_id.value, myArray ) != false ) { \n";
    $js1 .= "document.ADForm.char_length_hidden.value = lookup(document.ADForm.HTML_GEN_LSB_pp_type_piece_id.value, myArray );\n}";
    $js1 .= "}\n";

    $myForm->addJS(JSP_FORM, "js", $js1);

    //Validation du nombre de caractères des pièces d'identité
    $js2 = "";
    $js2 .= "if (document.ADForm.char_length_hidden.value != 0 && (document.ADForm.char_length_hidden.value != '' && document.ADForm.pp_nm_piece_id.value.length != document.ADForm.char_length_hidden.value))
                {
                    msg += '" . _("- Le no. de la pièce d\'identité ne correspond pas à ") . "';
                    msg += document.ADForm.char_length_hidden.value
                    msg += '" . _(" caractères ") . "\\n';
                    ADFormValid = false;
                    }";

    $myForm->addJS(JSP_BEGIN_CHECK, "js2", $js2);
  }
  if ($CLI["statut_juridique"] == 1) {
    // $myForm->setFieldProperties("pp_id_gi", FIELDP_IS_LABEL, true);
    //MAsquer qualite auxiliaire /ordinaire du drop down
    if($SESSION_VARS["qualite"] == 2){
      $myForm->setFieldProperties("qualite", FIELDP_EXCLUDE_CHOICES, array(1));//auxiliaire

    }else if($SESSION_VARS["qualite"] == 1){
      $myForm->setFieldProperties("qualite", FIELDP_EXCLUDE_CHOICES, array(2));//ordinaire
    }
    $myForm->addLink("pp_id_gi", "rechercher", _("Rechercher"), "#");
    $myForm->setLinkProperties("rechercher", LINKP_JS_EVENT, array("OnClick" => "OpenBrw('$SERVER_NAME/modules/clients/rech_client_gi.php', '"._("Recherche")."');return false; "));
    $myForm->addbutton("pp_id_gi","btn_clear",_("Effacer"),TYPB_BUTTON);
    $myForm->addHiddenType("pp_id_gi_lab");
    $myJSAAM = "document.ADForm.pp_id_gi.value='';";
    $myForm->setButtonProperties("btn_clear",BUTP_JS_EVENT,array ("OnClick" => $myJSAAM));
  }
  if ($CLI["statut_juridique"] == 3){ //AT-96: Groupe Informel - Controle JS sur les champs nombres d'hommes du groupe et nombres de femmes du groupe
    $msg_control_superieure = "Le totale des hommes et femmes ne peut pas etre superieure au nombre de membres du group";
    $msg_control_inferieure = "Le totale des hommes et femmes ne peut pas etre inferieure au nombre de membres du group";
    $jsGIControlNbreGroup .= "\t var nbre_hommes = eval(document.ADForm.nbre_hommes_grp.value);\n";
    $jsGIControlNbreGroup .= "\t var nbre_femmes = eval(document.ADForm.nbre_femmes_grp.value);\n";
    $jsGIControlNbreGroup .= "\t var nbre_membre = eval(document.ADForm.gi_nbre_membr.value);\n";
    $jsGIControlNbreGroup .= "\t var nbre_total_calcul = nbre_hommes + nbre_femmes;\n";
    $jsGIControlNbreGroup .= "\t if (nbre_hommes >= 0 || nbre_femmes >= 0){\n";
    $jsGIControlNbreGroup .= "\t\t if (nbre_total_calcul >= 0 && nbre_total_calcul > nbre_membre){\n";
    $jsGIControlNbreGroup .= "\t\t\t ADFormValid = false;\n";
    $jsGIControlNbreGroup .= "\t\t\t alert('".$msg_control_superieure." ('+nbre_membre+')');\n";
    $jsGIControlNbreGroup .= "\t\t }\n";
    $jsGIControlNbreGroup .= "\t\t if (nbre_total_calcul >= 0 && nbre_total_calcul < nbre_membre){\n";
    $jsGIControlNbreGroup .= "\t\t\t ADFormValid = false;\n";
    $jsGIControlNbreGroup .= "\t\t\t alert('".$msg_control_inferieure." ('+nbre_membre+')');\n";
    $jsGIControlNbreGroup .= "\t\t }\n";
    $jsGIControlNbreGroup .= "\t\t if (nbre_hommes >= 0 && nbre_hommes > nbre_membre && document.ADForm.nbre_femmes_grp.value ==''){\n";
    $jsGIControlNbreGroup .= "\t\t\t ADFormValid = false;\n";
    $jsGIControlNbreGroup .= "\t\t\t alert('".$msg_control_superieure." ('+nbre_membre+')');\n";
    $jsGIControlNbreGroup .= "\t\t }\n";
    $jsGIControlNbreGroup .= "\t\t if (nbre_hommes >= 0 && nbre_hommes < nbre_membre && document.ADForm.nbre_femmes_grp.value ==''){\n";
    $jsGIControlNbreGroup .= "\t\t\t ADFormValid = false;\n";
    $jsGIControlNbreGroup .= "\t\t\t alert('".$msg_control_inferieure." ('+nbre_membre+')');\n";
    $jsGIControlNbreGroup .= "\t\t }\n";
    $jsGIControlNbreGroup .= "\t }\n";
    $jsGIControlNbreGroup1 = "function nbreFemmesGroup(){\n";
    $jsGIControlNbreGroup1 .= "\t var nbre_hommes = eval(document.ADForm.nbre_hommes_grp.value);\n";
    $jsGIControlNbreGroup1 .= "\t var nbre_membre = eval(document.ADForm.gi_nbre_membr.value);\n";
    $jsGIControlNbreGroup1 .= "\t var nbre_calcule = nbre_membre - nbre_hommes;\n";
    $jsGIControlNbreGroup1 .= "\t if (nbre_hommes >= 0 && nbre_membre > 0){\n";
    $jsGIControlNbreGroup1 .= "\t\t if (nbre_calcule < 0){\n";
    $jsGIControlNbreGroup1 .= "\t\t\t alert('Le nombre totale hommes saisie ('+nbre_hommes+') ne peut pas etre superieure au nombre de membres du group ('+nbre_membre+')');\n";
    $jsGIControlNbreGroup1 .= "\t\t\t document.ADForm.nbre_hommes_grp.value = '';\n";
    $jsGIControlNbreGroup1 .= "\t\t }\n";
    $jsGIControlNbreGroup1 .= "\t\t document.ADForm.nbre_femmes_grp.value = nbre_calcule;\n";
    $jsGIControlNbreGroup1 .= "\t }\n";
    $jsGIControlNbreGroup1 .= "\t if (document.ADForm.nbre_hommes_grp.value ==''){\n";
    $jsGIControlNbreGroup1 .= "\t\t document.ADForm.nbre_femmes_grp.value = '';\n";
    $jsGIControlNbreGroup1 .= "\t }\n";
    $jsGIControlNbreGroup1 .= "}\n";
    $myForm->addJS(JSP_END_CHECK, "jsGIControlNbreGroup", $jsGIControlNbreGroup);
    $myForm->addJS(JSP_FORM, "jsGIControlNbreGroup1", $jsGIControlNbreGroup1);
    $myForm->addJS(JSP_FORM, "jsSetChampNbreFemmesReadOnly", "\ndocument.getElementsByName('nbre_femmes_grp').item(0).setAttribute('readOnly',true);");
    $myForm->setFieldProperties("nbre_hommes_grp",FIELDP_JS_EVENT,array ("OnBlur" => "nbreFemmesGroup();"));
  }

  if ($CLI["statut_juridique"] == 4) {
    //Ajout d'un lien pour rechercher le responsable du GS
    $myForm->addLink("gs_responsable", "recherche_cli", _("Rechercher"), "#");
    $myForm->setLinkProperties("recherche_cli", LINKP_JS_EVENT, array("onclick" => "OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=gs_responsable', '"._("Recherche")."');return false;"));
  }

  if ($AGC['identification_client'] == 2 ) {
    //Gestion localisation Rwanda
    // --> Construction le l'array des localisations.
    $locArrayRwanda = getLocRwandaArray();
    // --> Sélection des champs à afficher dans les localisations
    reset($locArrayRwanda);
    $includeChoicesRwanda = array();
    while (list(, $valueRwanda) = each($locArrayRwanda)) {
      if ($valueRwanda['parent'] == 0)
        array_push($includeChoicesRwanda, $valueRwanda['id']);
    }
    // --> Restriction des choix dans localisation rwanda
    $myForm->setFieldProperties("province", FIELDP_INCLUDE_CHOICES, $includeChoicesRwanda);
    // --> Construction de la fonction de mise à jour du district
    $jsCodeLocRwanda = "function displayLocsDistrict() {\n";
    $jsCodeLocRwanda .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_district.length; ++i) document.ADForm.HTML_GEN_LSB_district.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwanda .= "document.ADForm.HTML_GEN_LSB_district.length = 0;";
    $jsCodeLocRwanda .= "document.ADForm.HTML_GEN_LSB_district.options[document.ADForm.HTML_GEN_LSB_district.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwanda .= "document.ADForm.HTML_GEN_LSB_district.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_district.length = 1; \n";
    reset($locArrayRwanda);
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] != 0) {
        $jsCodeLocRwanda .= "\tif (document.ADForm.HTML_GEN_LSB_province.value == " . $value['parent'] . ")\n";
        $jsCodeLocRwanda .= "\t\tdocument.ADForm.HTML_GEN_LSB_district.options[document.ADForm.HTML_GEN_LSB_district.length] = new Option('" . $value['libelle_localisation'] . "', '" . $value['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwanda .= "\n}";
    // --> Constrution des choix disponibles pour le district sélectionné
    $choices = array();
    reset($locArrayRwanda);
    $parent = $CLI['province'];
    if (isset($province)){ //AT-150 page reloaded take value from posted data
      $parent = $province;
    }
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] == $parent)
        $choices[$value['id']] = $value['libelle_localisation'];
    }
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLocRwanda", $jsCodeLocRwanda);
    // --> ajout des champs
    $myForm->addField("district", _("Localisation district"), TYPC_LSB);
    $myForm->setFieldProperties("district", FIELDP_ADD_CHOICES, $choices);
    $myForm->setFieldProperties("district", FIELDP_DEFAULT, $CLI['district']);
    $myForm->setFieldProperties("district", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("province", FIELDP_JS_EVENT, array("onchange" => "displayLocsDistrict()"));

    // localisation des secteur
    // --> Construction de la fonction de mise à jour du secteur
    $jsCodeLocRwandaSecteur = "function displayLocsSecteur() {\n";
    $jsCodeLocRwandaSecteur .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_secteur.length; ++i) document.ADForm.HTML_GEN_LSB_secteur.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwandaSecteur .= "document.ADForm.HTML_GEN_LSB_secteur.length = 0;";
    $jsCodeLocRwandaSecteur .= "document.ADForm.HTML_GEN_LSB_secteur.options[document.ADForm.HTML_GEN_LSB_secteur.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwandaSecteur .= "document.ADForm.HTML_GEN_LSB_secteur.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_secteur.length = 1; \n";
    reset($locArrayRwanda);
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] != '') {
        $jsCodeLocRwandaSecteur .= "\tif (document.ADForm.HTML_GEN_LSB_district.value == " . $value['parent'] . ")\n";
        $jsCodeLocRwandaSecteur .= "\t\tdocument.ADForm.HTML_GEN_LSB_secteur.options[document.ADForm.HTML_GEN_LSB_secteur.length] = new Option('" . $value['libelle_localisation'] . "', '" . $value['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwandaSecteur .= "\n}";
    // --> Constrution des choix disponibles pour le id_loc1 sélectionné
    $choices = array();
    reset($locArrayRwanda);
    $parent = $CLI['district'];
    if (isset($district)){ //AT-150 page reloaded take value from posted data
      $parent = $district;
    }
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] == $parent)
        $choices[$value['id']] = $value['libelle_localisation'];
    }
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLocRwandaSecteur", $jsCodeLocRwandaSecteur);
    // --> ajout des champs
    $myForm->addField("secteur", _("Localisation secteur"), TYPC_LSB);
    $myForm->setFieldProperties("secteur", FIELDP_ADD_CHOICES, $choices);
    $myForm->setFieldProperties("secteur", FIELDP_DEFAULT, $CLI['secteur']);
    $myForm->setFieldProperties("secteur", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("district", FIELDP_JS_EVENT, array("onchange" => "displayLocsSecteur()"));

    // localisation des cellule
    // --> Construction de la fonction de mise à jour du cellule
    $jsCodeLocRwandaCellule = "function displayLocsCellule() {\n";
    $jsCodeLocRwandaCellule .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_cellule.length; ++i) document.ADForm.HTML_GEN_LSB_cellule.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwandaCellule .= "document.ADForm.HTML_GEN_LSB_cellule.length = 0;";
    $jsCodeLocRwandaCellule .= "document.ADForm.HTML_GEN_LSB_cellule.options[document.ADForm.HTML_GEN_LSB_cellule.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwandaCellule .= "document.ADForm.HTML_GEN_LSB_cellule.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_cellule.length = 1; \n";
    reset($locArrayRwanda);
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] != '') {
        $jsCodeLocRwandaCellule .= "\tif (document.ADForm.HTML_GEN_LSB_secteur.value == " . $value['parent'] . ")\n";
        $jsCodeLocRwandaCellule .= "\t\tdocument.ADForm.HTML_GEN_LSB_cellule.options[document.ADForm.HTML_GEN_LSB_cellule.length] = new Option('" . $value['libelle_localisation'] . "', '" . $value['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwandaCellule .= "\n}";
    // --> Constrution des choix disponibles pour le id_loc1 sélectionné
    $choices = array();
    reset($locArrayRwanda);
    $parent = $CLI['secteur'];
    if (isset($secteur)){ //AT-150 page reloaded take value from posted data
      $parent = $secteur;
    }
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] == $parent)
        $choices[$value['id']] = $value['libelle_localisation'];
    }
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "displayLocsCellule", $jsCodeLocRwandaCellule);
    // --> ajout des champs
    $myForm->addField("cellule", _("Localisation cellule"), TYPC_LSB);
    $myForm->setFieldProperties("cellule", FIELDP_ADD_CHOICES, $choices);
    $myForm->setFieldProperties("cellule", FIELDP_DEFAULT, $CLI['cellule']);
    $myForm->setFieldProperties("cellule", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("secteur", FIELDP_JS_EVENT, array("onchange" => "displayLocsCellule()"));

    // localisation des Village
    // --> Construction de la fonction de mise à jour du Village
    $jsCodeLocRwandaVillage = "function displayLocsVillage() {\n";
    $jsCodeLocRwandaVillage .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_village.length; ++i) document.ADForm.HTML_GEN_LSB_village.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwandaVillage .= "document.ADForm.HTML_GEN_LSB_village.length = 0;";
    $jsCodeLocRwandaVillage .= "document.ADForm.HTML_GEN_LSB_village.options[document.ADForm.HTML_GEN_LSB_village.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwandaVillage .= "document.ADForm.HTML_GEN_LSB_village.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_village.length = 1; \n";
    reset($locArrayRwanda);
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] != '') {
        $jsCodeLocRwandaVillage .= "\tif (document.ADForm.HTML_GEN_LSB_cellule.value == " . $value['parent'] . ")\n";
        $jsCodeLocRwandaVillage .= "\t\tdocument.ADForm.HTML_GEN_LSB_village.options[document.ADForm.HTML_GEN_LSB_village.length] = new Option('" . $value['libelle_localisation'] . "', '" . $value['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwandaVillage .= "\n}";
    // --> Constrution des choix disponibles pour le id_loc1 sélectionné
    $choices = array();
    reset($locArrayRwanda);
    $parent = $CLI['cellule'];
    if (isset($cellule)){ //AT-150 page reloaded take value from posted data
      $parent = $cellule;
    }
    while (list(, $value) = each($locArrayRwanda)) {
      if ($value['parent'] == $parent)
        $choices[$value['id']] = $value['libelle_localisation'];
    }
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "displayLocsVillage", $jsCodeLocRwandaVillage);
    // --> ajout des champs
    $myForm->addField("village", _("Localisation village"), TYPC_LSB);
    $myForm->setFieldProperties("village", FIELDP_ADD_CHOICES, $choices);
    $myForm->setFieldProperties("village", FIELDP_DEFAULT, $CLI['village']);
    $myForm->setFieldProperties("village", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("cellule", FIELDP_JS_EVENT, array("onchange" => "displayLocsVillage()"));

  }else {
    // Gestion de la localisation
    // --> Construction le l'array des localisations.
    $locArray = getLocArray();
    // --> Sélection des champs à afficher dans id_loc
    reset($locArray);
    $includeChoices = array();
    while (list(, $value) = each($locArray)) {
      if ($value['parent'] == '')
        array_push($includeChoices, $value['id']);
    }
    // --> Restriction des choix dans id_loc
    $myForm->setFieldProperties("id_loc1", FIELDP_INCLUDE_CHOICES, $includeChoices);
    // --> Construction de la fonction de mise à jour de id_loc2
    $jsCodeLoc = "function displayLocs() {\n";
    $jsCodeLoc .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_id_loc2.length; ++i) document.ADForm.HTML_GEN_LSB_id_loc2.options[i] = null;\n"; //Vide les choix
    $jsCodeLoc .= "document.ADForm.HTML_GEN_LSB_id_loc2.length = 0;";
    $jsCodeLoc .= "document.ADForm.HTML_GEN_LSB_id_loc2.options[document.ADForm.HTML_GEN_LSB_id_loc2.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLoc .= "document.ADForm.HTML_GEN_LSB_id_loc2.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_id_loc2.length = 1; \n";
    reset($locArray);
    while (list(, $value) = each($locArray)) {
      if ($value['parent'] != '') {
        $jsCodeLoc .= "\tif (document.ADForm.HTML_GEN_LSB_id_loc1.value == " . $value['parent'] . ")\n";
        $jsCodeLoc .= "\t\tdocument.ADForm.HTML_GEN_LSB_id_loc2.options[document.ADForm.HTML_GEN_LSB_id_loc2.length] = new Option('" . $value['libel'] . "', '" . $value['id'] . "', false, false);\n";
      }
    }
    $jsCodeLoc .= "\n}";
    // --> Constrution des choix disponibles pour le id_loc1 sélectionné
    $choices = array();
    reset($locArray);
    while (list(, $value) = each($locArray)) {
      if ($value['parent'] == $CLI['id_loc1'])
        $choices[$value['id']] = $value['libel'];
    }
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLoc", $jsCodeLoc);
    // --> ajout des champs
    $myForm->addField("id_loc2", _("Localisation 2"), TYPC_LSB);
    $myForm->setFieldProperties("id_loc2", FIELDP_ADD_CHOICES, $choices);
    $myForm->setFieldProperties("id_loc2", FIELDP_DEFAULT, $CLI['id_loc2']);
    $myForm->setFieldProperties("id_loc1", FIELDP_JS_EVENT, array("onchange" => "displayLocs()"));

  }
  // Gestion de la categorie employe
  // --> Construction le l'array des la categorie employe.
  $catArray = getCatEmpArray();
  // --> Sélection des champs à afficher dans la categorie
  reset($catArray);
  $includeChoices = array();
  while (list(,$value) = each($catArray)) {
    if ($value['parent'] == '')
      array_push($includeChoices, $value['id']);
  }
  // --> Restriction des choix dans categorie
  $myForm->setFieldProperties("categorie", FIELDP_INCLUDE_CHOICES, $includeChoices);
  // --> Construction de la fonction de mise à jour de la classe
  $jsCodeCat = "function displayCat() {\n";
  $jsCodeCat .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_classe.length; ++i) document.ADForm.HTML_GEN_LSB_classe.options[i] = null;\n"; //Vide les choix
  $jsCodeCat .= "document.ADForm.HTML_GEN_LSB_classe.length = 0;";
  $jsCodeCat .= "document.ADForm.HTML_GEN_LSB_classe.options[document.ADForm.HTML_GEN_LSB_classe.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
  $jsCodeCat .= "document.ADForm.HTML_GEN_LSB_classe.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_classe.length = 1; \n";
  reset($catArray);
  while (list (, $value) = each($catArray)) {
    if ($value['parent'] != '') {
      $jsCodeCat .= "\tif (document.ADForm.HTML_GEN_LSB_categorie.value == " . $value['parent'] . ")\n";
      $jsCodeCat .= "\t\tdocument.ADForm.HTML_GEN_LSB_classe.options[document.ADForm.HTML_GEN_LSB_classe.length] = new Option('" . $value['libel'] . "', '" . $value['id'] . "', false, false);\n";
    }
  }
  $jsCodeCat .= "\n}";
  // --> Constrution des choix disponibles pour le categorie sélectionné
  $choices = array();
  reset($catArray);
  while (list(,$value) = each($catArray)) {
    if ($value['parent'] == $CLI['categorie'])
      $choices[$value['id']] = $value['libel'];
  }
  // --> Ajout de la fonction dans le formulaire
  $myForm->addJS(JSP_FORM, "jsCodeCat", $jsCodeCat);
  // --> ajout des champs
  $myForm->addField ("classe", _("Classe"), TYPC_LSB);
  $myForm->setFieldProperties("classe", FIELDP_ADD_CHOICES, $choices);
  $myForm->setFieldProperties("classe", FIELDP_DEFAULT, $CLI['classe']);
  $myForm->setFieldProperties("categorie", FIELDP_JS_EVENT, array("onchange" => "displayCat()"));




  // Controle JS sur le champ num_tel + size champ num_tel
  $infoParamAbonnement = array();
  $infoParamAbonnement = getInfoParamAbonnement("NB_CARACTERES_TELEPHONE");
  $infoParamPrefixAbonnement = getInfoParamAbonnement("PREFIX_TELEPHONE");
  if ($infoParamAbonnement != null){
    // set num tel size via JS
    //$myForm->addJS(JSP_FORM, "jsSetNumTelSize", "document.getElementsByName('num_tel').item(0).maxlength = '".(intval($infoParamAbonnement['valeur'])+1)."';");
    $myForm->addJS(JSP_FORM, "jsSetNumTelSize", "\ndocument.getElementsByName('num_tel').item(0).setAttribute('maxlength',".(intval($infoParamAbonnement['valeur'])).");\ndocument.ADForm.num_tel.size = '".(intval($infoParamAbonnement['valeur']))."';\n");
    // function JS pour verifier le champ num tel
    $jsNumTel = "";
    $jsNumTel .= "\nfunction checkNumTel() {\n";
    $jsNumTel .= "\tif (document.ADForm.num_tel.value.length != 0 && document.ADForm.num_tel.value.length >
    ".(intval($infoParamAbonnement['valeur']))."){\n";
    $jsNumTel .= "\t\tADFormValid = false;\n";
    $jsNumTel .= "\t\talert('Numéro Téléphone ne peut pas etre supérieure à ".intval($infoParamAbonnement['valeur'])." chiffres!!');\n";
    $jsNumTel .= "\t\tdocument.ADForm.num_tel.focus();exit;\n";
    $jsNumTel .= "\t}\n";
    $jsNumTel .= "\tif (document.ADForm.num_tel.value.length != 0 && document.ADForm.num_tel.value.length <
    ".(intval($infoParamAbonnement['valeur']))."){\n";
    $jsNumTel .= "\t\tADFormValid = false;\n";
    $jsNumTel .= "\t\talert('Numéro Téléphone ne peut pas etre inférieure à ".intval($infoParamAbonnement['valeur'])." chiffres!!');\n";
    $jsNumTel .= "\t\tdocument.ADForm.num_tel.focus();exit;\n";
    $jsNumTel .= "\t}\n";

    if (isset($infoParamPrefixAbonnement['valeur'])) {
        $jsNumTel .= "\tif (document.ADForm.num_tel.value.length != 0 && document.ADForm.num_tel.value.substring(0," . strlen($infoParamPrefixAbonnement['valeur']) . ") !=
    " . ($infoParamPrefixAbonnement['valeur']) . "){\n";
        $jsNumTel .= "\t\tADFormValid = false;\n";
        $jsNumTel .= "\t\talert('" . sprintf(_('Numéro Téléphone doit commencer par les chiffres suivants: ')) . intval($infoParamPrefixAbonnement['valeur']) . "');\n";
        $jsNumTel .= "\t\tdocument.ADForm.num_tel.focus();exit;\n";
        $jsNumTel .= "\t}\n";
    }

    $jsNumTel .= "\tif (document.ADForm.num_tel.value.length == 0){\n";
    $jsNumTel .= "\t\tADFormValid = false;\n";
    $jsNumTel .= "\t\tvar proceed = confirm('Le numéro de téléphone du client à créer/modifier manque, voulez vous vraiement créer/modifier le client sans numéro de téléphone?');\n";
    $jsNumTel .= "\t\tif (!proceed){\n";
    $jsNumTel .= "\t\t\tdocument.ADForm.num_tel.focus();exit;\n";
    $jsNumTel .= "\t\t}\n";
    $jsNumTel .= "\t}";
    $jsNumTel .= "\n}";
    $myForm->addJS(JSP_FORM, "jsNumTel", $jsNumTel);
  }

  // Rendre les champs modifiables
  while (list($key,) = each ($labels))
    $myForm->setFieldProperties($key, FIELDP_IS_LABEL, true);

  // Ajout du bouton valider
  $myForm->addFormButton(1, 1, "valider", _("Valider"), TYPB_SUBMIT);
  $myForm->setFormButtonProperties("valider", BUTP_PROCHAIN_ECRAN, 'Mcl-2');

  // Ajout du control checkNumTel sur les types de clients
  $myForm->setFormButtonProperties("valider",BUTP_JS_EVENT,array ("OnClick" => "checkNumTel();"));

  if ($SESSION_VARS['statut_juridique'] == 1) {
    // Gestion de l'appartenance à un groupe informel
    $myJS = "document.ADForm.pp_id_gi_lab.value=document.ADForm.pp_id_gi.value;";
    $myForm->setFormButtonProperties("valider",BUTP_JS_EVENT,array ("OnClick" => $myJS));
  } else if ($SESSION_VARS['statut_juridique'] == 4) {
    // Groupe solidaire : encodage des membres
    $myForm->addHTMLExtraCode("espace","<br/>");
    $myForm->addHTMLExtraCode("membres","<table align=\"center\" valign=\"middle\" bgcolor=\"".$colb_tableau."\"><tr><td><b>"._("Membres du groupe")."</b></td></tr></table>\n");
    array_push($Order, "espace", "membres");
    $myForm->addHiddenType("nbr_membres_encodes", $SESSION_VARS["nbr_membres_encodes"]);
    for ($i=1; $i<=$SESSION_VARS["nbr_membres_encodes"]; ++$i) {
      $myForm->addField("num_client$i", _("Membre")." $i", TYPC_INT);
      $myForm->setFieldProperties("num_client$i", FIELDP_DEFAULT,$SESSION_VARS["membres_grp_sol"][$i-1]);
      $myForm->addLink("num_client$i", "rechercher_cli$i", _("Rechercher"), "#");
      $myForm->setLinkProperties("rechercher_cli$i", LINKP_JS_EVENT, array("onclick" => "OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client$i', '"._("Recherche")."');return false;"));
      array_push($Order, "num_client$i");
    }
  }

  // Remplissage des champs
  if ($global_nom_ecran_prec != "Mcl-1" || ($global_nom_ecran_prec == "Mcl-1" && !isset($date_crea))) {
    // On n'utilisera fill que si c'est le premier appel à cet écran
    $fill = new FILL_HTML_GEN2();
    $fill->addFillClause ("client", "ad_cli");
    $fill->addCondition("client", "id_client", $global_id_client);
    $fill->addManyFillFields("client", OPER_EXCLUDE, $exclude);
    $fill->fill($myForm);
  } else {
    // Sinon on utilise les valeurs déjà encodées
    foreach ($SESSION_VARS["POSTED_DATAS"] as $key => $fieldname) {
      if ($fieldname=='id_client') {
        $myForm->setFieldProperties($fieldname, FIELDP_DEFAULT,$ {$fieldname});
      } else {
        $myForm->setFieldProperties($fieldname, FIELDP_DEFAULT, $ {$fieldname});
      }
      array_push($exclude, $fieldname);
    }
    $fill = new FILL_HTML_GEN2();
    $fill->addFillClause ("client", "ad_cli");
    $fill->addCondition("client", "id_client", $global_id_client);
    $fill->addManyFillFields("client", OPER_EXCLUDE, $exclude);
    $fill->fill($myForm);
  }

  if ( $CLI["statut_juridique"] == 1) {       // Personne physique
    array_push($Order, "photo");
    array_push($Order, "signature");
  }
  // Mise en ordre
  $myForm->setOrder(NULL, $Order);
  if ( $CLI["statut_juridique"] == 1) {       // Personne physique
    array_pop($Order);
    array_pop($Order);
  }
//Traitement pour les champs extras
  $objChampsExtras = new HTML_Champs_Extras ($myForm,'ad_cli',$id_cli);
  $objChampsExtras->buildChampsExtras($SESSION_VARS['champsExtrasValues']);
  $SESSION_VARS['champsExtras']= $objChampsExtras-> getChampsExtras();


  // Ajout des boutons
  if ($SESSION_VARS['statut_juridique'] == 4) {
    // Groupes solidaires : permet d'ajouter des membres supplementaires
    $myForm->addFormButton(1, 2, "ajout", _("Ajouter membre"), TYPB_SUBMIT);
    $myForm->setFormButtonProperties("ajout", BUTP_PROCHAIN_ECRAN, 'Mcl-1');
    $myForm->setFormButtonProperties("ajout", BUTP_CHECK_FORM, false);
    $js = "nbr_membres_encodes.value = ".$SESSION_VARS['nbr_membres_encodes']."+1;\n";
    $myForm->setFormButtonProperties("ajout", BUTP_JS_EVENT, array("onclick" => $js));
  }
  $myForm->addFormButton(1, 3, "annuler", _("Annuler"), TYPB_SUBMIT);
  $myForm->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
  $myForm->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, 'Gen-9');

  // Contrôles liés à la modificatin de la qualité
  if ($SESSION_VARS["statut_juridique"] != 1)
    $myForm->setFieldProperties("qualite", FIELDP_IS_LABEL, true);
  else
    $myForm->setFieldProperties("qualite", FIELDP_IS_LABEL, false);
  if ($SESSION_VARS["statut_juridique"] == 1 && $SESSION_VARS["qualite"] != 1)
    $myForm->setFieldProperties("qualite", FIELDP_EXCLUDE_CHOICES, array(1));
  $myForm->buildHTML();
  echo $myForm->getHTML();

  // Enregistrement des noms de champ qui seront postés
  // En effet tous les champs sont contenus dans le vecteur $Order sauf statut_juridique, etat et id_client
  $Order = array_diff($Order, array("statut_juridique", "etat", "id_client", "photo", "signature", "espace", "membres"));
  //if ($SESSION_VARS["qualite"] == 1)
  //  $Order = array_diff($Order, array("qualite"));
  $SESSION_VARS["POSTED_DATAS"] = $Order;
}
/*}}}*/

/*{{{ Mcl-2 : Confirmation de la mise à jour du client */
else if ($prochain_ecran == "Mcl-2") {
  $DATA = array();

  $POSTED_DATAS = $SESSION_VARS["POSTED_DATAS"];
  foreach ($POSTED_DATAS as $key => $fieldname) {
    $DATA[$fieldname] = $ {$fieldname};
  }

  // Traitement spécial pour le casier judiciaire
  if (!isset($pp_casier_judiciaire))
    $DATA["pp_casier_judiciaire"] = "f";

  // Traitement spécial pour le VIP
  if (!isset($pp_is_vip)) {
    $DATA["pp_is_vip"] = "f";
  }

  //traitement recup montant
  if (isset($mnt_quotite)){
    $DATA["mnt_quotite"] = recupMontant($mnt_quotite);
  }

  // Traitement spécial pour le revenu
  if (isset($pp_revenu))
    $DATA["pp_revenu"] = recupMontant($pp_revenu);
  // Vérifie si un client a dépassé le nombre de groupe autorisé dans l'agence
  $ag_data = getAgenceDatas($global_id_agence);
  if(($SESSION_VARS["statut_juridique"] == 4)&&($ag_data["nb_group_for_cust"] > 0)){

    for ($i = 1; $i <= $nbr_membres_encodes; ++ $i) {
      if (($DATA["num_client$i"] != NULL) && (strlen($DATA["num_client$i"]) > 0)) {
        $num_client = $DATA["num_client$i"];
        $list_grp_memb = getGroupSol($num_client, $global_id_client);
        if(sizeof($list_grp_memb->param) >= $ag_data["nb_group_for_cust"]){
          $html_err = new HTML_erreur(_("Echec de la modification du client."));
          $html_err->setMessage(sprintf(_("Erreur: le client '%s' ne peut pas appartenir à plus de '%s' groupes, nombre maximum autorisé dans l'agence"),$num_client,$ag_data["nb_group_for_cust"]));
          $html_err->addButton("BUTTON_OK", 'Mcl-1');
          $html_err->buildHTML();
          echo $html_err->HTML_code;
          exit();
        }
      }
    }
  }

  //Traitement pour les champs extras
  if(sizeof($SESSION_VARS['champsExtras'])> 0 ) {
    $DATA['champsExtras'] = HTML_Champs_Extras::buildDataChampsEXtrasValues($SESSION_VARS['champsExtras'],$_POST);
  }

  //Sanitise données avant d'enregistrer la modification
  // personne physique
  if (array_key_exists('pp_nom', $DATA) && $DATA["pp_nom"] !== '' ) {
      $DATA["pp_nom"] = trim(preg_replace('/\s+/',' ', $DATA["pp_nom"]));
  }
  if (array_key_exists('pp_prenom', $DATA) && $DATA["pp_prenom"] !== '' ) {
      $DATA["pp_prenom"] = trim(preg_replace('/\s+/',' ', $DATA["pp_prenom"]));
  }

  // personne morale
  if (array_key_exists('pm_raison_sociale', $DATA) && $DATA["pm_raison_sociale"] !== '' ) {
      $DATA["pm_raison_sociale"] = trim(preg_replace('/\s+/',' ', $DATA["pm_raison_sociale"]));
  }

  // groupe informelle or groupe solidaire
  if (array_key_exists('gi_nom', $DATA) && $DATA["gi_nom"] !== '' ) {
      $DATA["gi_nom"] = trim(preg_replace('/\s+/',' ', $DATA["gi_nom"]));
  }

  $myErr = updateClient($global_id_client, $DATA, $IMAGES, $nbr_membres_encodes);
  if ($myErr->errCode != NO_ERR) {
    $html_err = new HTML_erreur(_("Echec de la création du client."));
    $html_err->setMessage(_("Erreur")." : ".$error[$myErr->errCode]."<br/>"._("Paramètre")." : ".$myErr->param);
    $html_err->addButton("BUTTON_OK", 'Mcl-1');
    $html_err->buildHTML();
    echo $html_err->HTML_code;
  } else {
    // Mise à jour des variables globales pour les nom/prenom/raison sociale
    $global_client = getClientName($global_id_client);

    ajout_historique (10, $global_id_client, "", $global_nom_login, date("r"), NULL);

    // Mise à jour des variables concernant la photo et la signature du client
    $IMGS = getImagesClient($global_id_client);
    $global_photo_client = $IMGS["photo"];
    $global_signature_client = $IMGS["signature"];

    $myMsg = new HTML_message(_("Confirmation"));
    $myMsg->setMessage(_("La mise à jour des données du client a été effectuée avec succès"));
    $myMsg->addButton(BUTTON_OK, 'Gen-9');
    $myMsg->buildHTML();
    echo $myMsg->HTML_code;
  }
}
/*}}}*/

else signalErreur(__FILE__,__LINE__,__FUNCTION__, sprintf(_("L'écran %s n'a pas pu être trouvé"), $global_nom_ecran));

?>