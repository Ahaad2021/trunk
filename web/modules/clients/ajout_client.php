<?php


/* vim: set expandtab softtabstop=2 shiftwidth=2 foldmethod=marker: */

/**

 * [30] Ajout d'un client dans la base de données
 * Cette opération comprends les écrans :
 * Evolution 361 ended @ 01042015
 * - Acl-1 : Choix du statut juridique
 * - Acl-2 : Entrée infos sur le client
 * - Acl-3 : Confirmation des données
 * - Acl-4 : Perception versement initial
 * - Acl-5 : Enregistrement du versement initial
 * - Acl-6 : Traitement DB et confirmation
 * @since 6/12/2001
 * @package Clients
 **/

require_once ('lib/dbProcedures/client.php');
require_once ('lib/misc/tableSys.php');
require_once ('lib/dbProcedures/agence.php');
require_once ('lib/dbProcedures/compte.php');
require_once ('lib/dbProcedures/epargne.php');
require_once ('lib/html/HTML_GEN2.php');
require_once ('lib/html/FILL_HTML_GEN2.php');
require_once 'lib/html/HTML_champs_extras.php';
require_once ('modules/epargne/recu.php');
require_once 'lib/dbProcedures/parametrage.php';

/* Vérifié le nombre de clients autorisés */
checkLicenceNbClients();

/*{{{ Acl-1 : Choix du statut juridique */
if ($global_nom_ecran == "Acl-1") {
  // Au cas où on aurait annulé une précédente transaction, nettoyage de certaines variables.
  $SESSION_VARS['statut_juridique'] = NULL;
  $SESSION_VARS['POSTED_DATAS'] = NULL;
  $myForm = new HTML_GEN2();
  $myForm->setTitle(_("Renseignements sur le statut juridique"));
  $myForm->addTable("ad_cli", OPER_INCLUDE, array (
                      "statut_juridique"
                    ));
  $myForm->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
  $myForm->addFormButton(1, 2, "cancel", _("Annuler"), TYPB_SUBMIT);
  $myForm->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Acl-2');
  $myForm->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-3');
  $myForm->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);
  $myForm->buildHTML();
  echo $myForm->getHTML();
}
/*}}}*/

/*{{{ Acl-2 : Entrée infos sur le client */
else
  if ($global_nom_ecran == "Acl-2") {
    //recuperation des données de l'agence'
    global $global_id_agence;
    $AG = getAgenceDatas($global_id_agence);    
    // Récupération des valeurs envoyées
    if ($global_nom_ecran_prec == "Acl-1") {
      $SESSION_VARS["nbr_membres_encodes"] = 3;
      $SESSION_VARS["statut_juridique"] = $statut_juridique;
    } else
      if ($global_nom_ecran_prec == "Acl-2") {
        foreach ($SESSION_VARS["POSTED_DATAS"] as $key => $fieldname)
        $SESSION_VARS[$fieldname] = $ {$fieldname};
        if ($SESSION_VARS["statut_juridique"] == 4)
          $SESSION_VARS["nbr_membres_encodes"] = $nbr_membres_encodes;
      } else
        if ($global_nom_ecran_prec == "Acl-3") {
          foreach ($SESSION_VARS["POSTED_DATAS"] as $key => $fieldname)
          $SESSION_VARS[$fieldname] = $SESSION_VARS["DATACLI"][$fieldname];
        }

    $data_agc = getAgenceDatas($global_id_agence);

    // Création du formulaire
    $myForm = new HTML_GEN2(_("Renseignement des données personnelles"));

    // Champs à exclure d'office, quel que soit le statut juridique
    $exclude = array (
                 "raison_defection",
                 "gi_date_dissol",
                 "nbre_parts",
    		     "nbre_parts_lib",
                 "raison_defection",
                 "date_defection"
               );
    if($data_agc['identification_client'] == 2){
      array_push($exclude, "id_cpte_base", "date_rupt", "dern_modif", "utilis_modif", "utilis_crea", "gestionnaire", "nbre_credits", "classe","id_loc1","id_loc2","loc3","district","secteur","cellule","village");
    }else{
      array_push($exclude, "id_cpte_base", "date_rupt", "dern_modif", "utilis_modif", "utilis_crea", "gestionnaire", "nbre_credits", "classe","id_loc2","province","district","secteur","cellule","village","classe_socio_economique");
    }
    $Order = array (
               "statut_juridique",
               "id_client",
               "anc_id_client",
               "date_adh",
               "date_crea",
               "langue_correspondance"
             );
    //AT-41 : si standard est selectionnà pour l'identification client dans l'agence, retire le champ Education dans la liste
    if ($AG["identification_client"] == 1){
      array_push($exclude, "education");
    }

    if ($SESSION_VARS['statut_juridique'] == 1) { // Personne physique
      // Champs à exclure
      array_push($exclude, "pm_raison_sociale", "pm_abreviation", "gi_nom", "gi_date_agre", "gi_nbre_membr", "pm_categorie", "pm_date_expiration", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_nature_juridique", "pm_tel2", "pm_tel3", "pm_email2", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date", "gs_responsable", "nbre_hommes_grp", "nbre_femmes_grp");

      // Ajout des champs du formulaire à partir de la table ad_cli
      $myForm->addTable("ad_cli", OPER_EXCLUDE, $exclude);

      // Gestion spécifique du champ 'Appartenance à un groupe informel'
      $myForm->addLink("pp_id_gi", "rechercher", _("Rechercher"), "#");
      $myForm->setLinkProperties("rechercher", LINKP_JS_EVENT, array (
                                   "OnClick" => "OpenBrw('$SERVER_NAME/modules/clients/rech_client_gi.php', '"._("Recherche")."');return false; "
                                 ));
      $myForm->setFieldProperties("pp_id_gi", FIELDP_DEFAULT, "");
      // $myForm->setFieldProperties("pp_id_gi", FIELDP_IS_LABEL, true);
      $myForm->addbutton("pp_id_gi", "btn_clear", _("Effacer"), TYPB_BUTTON);
      $myJSAAM = "document.ADForm.pp_id_gi.value='';";
      $myForm->setbuttonProperties("btn_clear", BUTP_JS_EVENT, array (
                                     "OnClick" => $myJSAAM
                                   ));
      // $myForm->setFieldProperties("pp_id_gi", FIELDP_IS_LABEL, true);
      $myForm->addHiddenType("pp_id_gi_lab"); // Nécessaire pour contenir la valeur de pp_id_gi
      $url = "/adbanking/images/travaux.gif";
      $myForm->addField("photo",_("Photographie"),TYPC_IMG);
      $myForm->setFieldProperties("photo", FIELDP_IMAGE_URL, $url);
      $myForm->addField("signature",_("Spécimen de signature"),TYPC_IMG);
      $myForm->setFieldProperties("signature", FIELDP_IMAGE_URL, $url);

      //Ajout d'un hidden Field pour la validation des piece d'identité
      $myForm->addHiddenType("char_length_hidden");

      //liste des pièces d'identité et leurs nombre de caractères
      $listPieceIdentLen=getListPieceIdentLength();

      $myForm->setFieldProperties("pp_type_piece_id", FIELDP_JS_EVENT, array("onchange"=>"getCharLength()"));

      //Fonction JavaScript pour trouver le type de pièce d'identité choisie et le nombre de caractères correspondant
      $js1 = "function lookup( name , arr)
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
      foreach($listPieceIdentLen as $key=>$value)
      {
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
                    msg += '"._("- Le no. de la pièce d\'identité ne correspond pas à ")."';
                    msg += document.ADForm.char_length_hidden.value
                    msg += '"._(" caractères ")."\\n';
                    ADFormValid = false;
                    }";

      $myForm->addJS(JSP_BEGIN_CHECK, "js2",$js2);

        // Définition de l'ordre des champs
      array_push($Order, "matricule","pp_nom", "pp_prenom", "pp_date_naissance");
      array_push($Order, "pp_lieu_naissance", "pp_nationalite", "pp_pays_naiss", "pp_sexe", "pp_type_piece_id", "pp_nm_piece_id", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_etat_civil", "pp_nbre_enfant");
      if($data_agc['identification_client'] == 2){
        array_push($Order, "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email","province","district","secteur","cellule","village", "education" ,"classe_socio_economique");
      }else{
        array_push($Order, "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "id_loc1", "id_loc2", "loc3");
      }      
      
      array_push($Order, "sect_act", "pp_pm_activite_prof", "pp_fonction", "pp_partenaire","pp_employeur","categorie","classe", "langue", "pp_revenu", "pp_pm_patrimoine", "pp_casier_judiciaire", "pp_is_vip", "pp_id_gi", "nb_imf", "nb_bk", "etat", "photo", "signature");

    } else
      if ($SESSION_VARS['statut_juridique'] == 2) { // Personne morale
        // Champs à exclure de l'affichage
        array_push($exclude, "matricule","pp_nom", "pp_prenom", "pp_date_naissance", "pp_lieu_naissance", "pp_pays_naiss", "pp_nationalite", "pp_sexe", "pp_type_piece_id", "pp_nm_piece_id", "pp_etat_civil", "pp_nbre_enfant", "pp_casier_judiciaire", "pp_is_vip", "pp_revenu", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_partenaire","pp_employeur", "pp_fonction", "gi_nom", "gi_date_agre", "gi_nbre_membr", "pp_id_gi", "langue", "photo", "signature", "gs_responsable", "education", "classe_socio_economique");

        // Ajout des champs à partir de ad_cli
        $myForm->addTable("ad_cli", OPER_EXCLUDE, $exclude);

        // Ordre d'affichage
        if($data_agc['identification_client'] == 2){
          array_push($Order, "pm_raison_sociale", "pm_abreviation", "adresse", "code_postal", "ville", "pays", "num_tel", "pm_tel2", "pm_tel3", "num_fax", "num_port", "email", "pm_email2", "province","district","secteur","cellule","village");
        }else{
          array_push($Order, "pm_raison_sociale", "pm_abreviation", "adresse", "code_postal", "ville", "pays", "num_tel", "pm_tel2", "pm_tel3", "num_fax", "num_port", "email", "pm_email2", "id_loc1", "id_loc2", "loc3");
        }        
	
	array_push($Order, "pm_categorie", "pm_nature_juridique", "sect_act", "pp_pm_activite_prof", "pp_pm_patrimoine", "nb_imf", "nb_bk", "nbre_hommes_grp", "nbre_femmes_grp", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_date_expiration", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date", "etat");
      } else
        if ($SESSION_VARS['statut_juridique'] == 3) { // Groupe informel
          // Champs à exclure
          array_push($exclude,  "matricule","pp_nom", "pp_prenom", "pp_date_naissance", "pp_lieu_naissance", "pp_sexe", "pp_type_piece_id", "pp_nm_piece_id", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_fonction", "pp_partenaire", "pp_employeur", "pp_fonction", "pp_pays_naiss", "pp_nationalite", "pp_etat_civil", "pp_nbre_conjoint", "pp_nbre_enfant", "pp_casier_judiciaire", "pp_is_vip", "pp_revenu", "pp_pm_patrimoine", "pp_pm_activite_prof", "pm_raison_sociale", "pm_abreviation", "pp_id_gi", "pm_categorie", "pm_date_expiration", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_nature_juridique", "pm_tel2", "pm_tel3", "pm_email2", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date", "photo", "signature", "gs_responsable", "education", "classe_socio_economique");

          // Ajout des champs à partir de la table ad_cli
          $myForm->addTable("ad_cli", OPER_EXCLUDE, $exclude);

          // Ordre d'affichage
          if($data_agc['identification_client'] == 2){
            array_push($Order, "gi_nom", "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "province","district","secteur","cellule","village", "sect_act", "langue", "gi_nbre_membr", "nbre_hommes_grp", "nbre_femmes_grp", "nb_imf", "nb_bk", "gi_date_agre", "etat");
          }else{
            array_push($Order, "gi_nom", "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "id_loc1", "id_loc2", "loc3", "sect_act", "langue", "gi_nbre_membr", "nbre_hommes_grp", "nbre_femmes_grp", "nb_imf", "nb_bk", "gi_date_agre", "etat");
          }        
	} else
          if ($SESSION_VARS['statut_juridique'] == 4) { // Groupe solidaire
            // Champs à exclure
            array_push($exclude,  "matricule","pp_nom", "pp_prenom", "pp_date_naissance", "pp_lieu_naissance", "pp_sexe", "pp_type_piece_id", "pp_nm_piece_id", "pp_date_piece_id", "pp_lieu_delivrance_id", "pp_date_exp_id", "pp_fonction", "pp_partanaire", "pp_employeur", "pp_fonction", "pp_pays_naiss", "pp_nationalite", "pp_etat_civil", "pp_nbre_conjoint", "pp_nbre_enfant", "pp_casier_judiciaire", "pp_is_vip", "pp_revenu", "pp_pm_patrimoine", "pp_pm_activite_prof", "pm_raison_sociale", "pm_abreviation", "pp_id_gi", "pm_categorie", "pm_date_expiration", "pm_date_notaire", "pm_date_depot_greffe", "pm_lieu_depot_greffe", "pm_numero_reg_nat", "pm_numero_nric", "pm_lieu_nric", "pm_nature_juridique", "pm_tel2", "pm_tel3", "pm_email2", "pm_date_constitution", "pm_agrement_nature", "pm_agrement_autorite", "pm_agrement_numero", "pm_agrement_date", "photo", "signature", "gi_date_agre", "gi_nbre_membr", "nbre_hommes_grp", "nbre_femmes_grp", "education", "classe_socio_economique");

            // Ajout des champs à partir de la table ad_cli
            $myForm->addTable("ad_cli", OPER_EXCLUDE, $exclude);

            //Ajout d'un lien pour rechercher le responsable du GS
            $myForm->addLink("gs_responsable", "recherche_cli", _("Rechercher"), "#");
            $myForm->setLinkProperties("recherche_cli", LINKP_JS_EVENT, array (
                                         "onclick" => "OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=gs_responsable', '"._("Recherche")."');return false;"
                                       ));

            // Ordre d'affichage
            if($data_agc['identification_client'] == 2){
              array_push($Order, "gi_nom", "gs_responsable", "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "province","district","secteur","cellule","village","province","district","secteur","cellule","village", "sect_act", "langue", "nb_imf", "nb_bk", "etat");
            }else{
              array_push($Order, "gi_nom", "gs_responsable", "adresse", "code_postal", "ville", "pays", "num_tel", "num_fax", "num_port", "email", "id_loc1", "id_loc2", "loc3", "sect_act", "langue", "nb_imf", "nb_bk", "etat");
            }          
	  }

    // Numéro du client
    $myForm->setFieldProperties("id_client", FIELDP_IS_REQUIRED, false);
    // Traitement spécial pour la qualité du client
    $myForm->setFieldProperties("qualite", FIELDP_DEFAULT, 1);
    $myForm->setFieldProperties("qualite", FIELDP_IS_LABEL, true);

    array_push($Order, "qualite");

    // Date de création du client est la date système
    $myForm->setFieldProperties("date_crea", FIELDP_DEFAULT, date("r"));
    $myForm->setFieldProperties("date_crea", FIELDP_IS_LABEL, true);

  if($data_agc['identification_client'] == 2) {
    // Gestion de la localisation Rwanda
    // --> Construction de l'array des localisations.
    $locArrayRwanda = getLocRwandaArray();
    // --> Sélection des champs à afficher dans id_loc
    reset($locArrayRwanda);
    $includeChoicesRwanda = array();
    while (list (, $value_rwanda) = each($locArrayRwanda)) {
      if ($value_rwanda['parent'] == 0)
        array_push($includeChoicesRwanda, $value_rwanda['id']);
      //$arrayDisplay[$value_rwanda['id'] ] =$value_rwanda['libelle_localisation'];

    }

    //$myForm->addField("province",_("Province"), TYPC_LSB);
    $myForm->addField("district", _("Localisation district"), TYPC_LSB);
    $myForm->addField("secteur", _("Localisation secteur"), TYPC_LSB);
    $myForm->addField("cellule", _("Localisation cellule"), TYPC_LSB);
    $myForm->addField("village", _("Localisation village"), TYPC_LSB);

    //$myForm->setFieldProperties("province", FIELDP_HAS_CHOICE_TOUS, false);
    // $myForm->setFieldProperties("province", FIELDP_HAS_CHOICE_AUCUN, true);
    $myForm->setFieldProperties("province", FIELDP_INCLUDE_CHOICES, $includeChoicesRwanda);;
    $myForm->setFieldProperties("province", FIELDP_IS_REQUIRED, true);

    $jsCodeLocRwanda = "function displayLocsRwanda() {\n";
    $jsCodeLocRwanda .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_district.length; ++i) document.ADForm.HTML_GEN_LSB_district.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwanda .= "document.ADForm.HTML_GEN_LSB_district.length = 0;";
    $jsCodeLocRwanda .= "document.ADForm.HTML_GEN_LSB_district.options[document.ADForm.HTML_GEN_LSB_district.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwanda .= "document.ADForm.HTML_GEN_LSB_district.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_district.length = 1; \n";
    reset($locArrayRwanda);
    while (list (, $value_rwanda) = each($locArrayRwanda)) {
      if ($value_rwanda['parent'] != '') {
        $jsCodeLocRwanda .= "\tif (document.ADForm.HTML_GEN_LSB_province.value == " . $value_rwanda['parent'] . ")\n";
        $jsCodeLocRwanda .= "\t\tdocument.ADForm.HTML_GEN_LSB_district.options[document.ADForm.HTML_GEN_LSB_district.length] = new Option('" . $value_rwanda['libelle_localisation'] . "', '" . $value_rwanda['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwanda .= "\n}";
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLocRwanda", $jsCodeLocRwanda);
    // --> ajout des champs

    //AT-150
    // --> Page reloaded Constrution des choix disponibles pour le district
    if (isset($province) && isset($district)){ //AT-150 page reloaded take value from posted data
      $choices = array();
      reset($locArrayRwanda);
      while (list(, $value) = each($locArrayRwanda)) {
        if ($value['parent'] == $province)
          $choices[$value['id']] = $value['libelle_localisation'];
      }
      $myForm->setFieldProperties("district", FIELDP_ADD_CHOICES, $choices);
      $myForm->setFieldProperties("district", FIELDP_DEFAULT, $district);
    }
    else{
      $myForm->setFieldProperties("district", FIELDP_ADD_CHOICES, array(
          "0" => "[Aucun]"
      ));
    }
    $myForm->setFieldProperties("district", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("province", FIELDP_JS_EVENT, array("onchange" => "displayLocsRwanda()"));

    $jsCodeLocRwandaSecteur = "function displayLocsRwandaSecteur() {\n";
    $jsCodeLocRwandaSecteur .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_secteur.length; ++i) document.ADForm.HTML_GEN_LSB_secteur.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwandaSecteur .= "document.ADForm.HTML_GEN_LSB_secteur.length = 0;";
    $jsCodeLocRwandaSecteur .= "document.ADForm.HTML_GEN_LSB_secteur.options[document.ADForm.HTML_GEN_LSB_secteur.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwandaSecteur .= "document.ADForm.HTML_GEN_LSB_secteur.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_secteur.length = 1; \n";
    reset($locArrayRwanda);
    while (list (, $value_rwanda) = each($locArrayRwanda)) {
      if ($value_rwanda['parent'] != '') {
        $jsCodeLocRwandaSecteur .= "\tif (document.ADForm.HTML_GEN_LSB_district.value == " . $value_rwanda['parent'] . ")\n";
        $jsCodeLocRwandaSecteur .= "\t\tdocument.ADForm.HTML_GEN_LSB_secteur.options[document.ADForm.HTML_GEN_LSB_secteur.length] = new Option('" . $value_rwanda['libelle_localisation'] . "', '" . $value_rwanda['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwandaSecteur .= "\n}";
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLocRwandaSecteur", $jsCodeLocRwandaSecteur);

    //AT-150
    // --> Page reloaded Constrution des choix disponibles pour le secteur
    if (isset($district) && isset($secteur)){ //AT-150 page reloaded take value from posted data
      $choices = array();
      reset($locArrayRwanda);
      while (list(, $value) = each($locArrayRwanda)) {
        if ($value['parent'] == $district)
          $choices[$value['id']] = $value['libelle_localisation'];
      }
      $myForm->setFieldProperties("secteur", FIELDP_ADD_CHOICES, $choices);
      $myForm->setFieldProperties("secteur", FIELDP_DEFAULT, $secteur);
    }
    else{
      $myForm->setFieldProperties("secteur", FIELDP_ADD_CHOICES, array(
          "0" => "[Aucun]"
      ));
    }
    $myForm->setFieldProperties("secteur", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("district", FIELDP_JS_EVENT, array("onchange" => "displayLocsRwandaSecteur()"));

    $jsCodeLocRwandaCellule = "function displayLocsRwandaCellule() {\n";
    $jsCodeLocRwandaCellule .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_cellule.length; ++i) document.ADForm.HTML_GEN_LSB_cellule.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwandaCellule .= "document.ADForm.HTML_GEN_LSB_cellule.length = 0;";
    $jsCodeLocRwandaCellule .= "document.ADForm.HTML_GEN_LSB_cellule.options[document.ADForm.HTML_GEN_LSB_cellule.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwandaCellule .= "document.ADForm.HTML_GEN_LSB_cellule.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_cellule.length = 1; \n";
    reset($locArrayRwanda);
    while (list (, $value_rwanda) = each($locArrayRwanda)) {
      if ($value_rwanda['parent'] != '') {
        $jsCodeLocRwandaCellule .= "\tif (document.ADForm.HTML_GEN_LSB_secteur.value == " . $value_rwanda['parent'] . ")\n";
        $jsCodeLocRwandaCellule .= "\t\tdocument.ADForm.HTML_GEN_LSB_cellule.options[document.ADForm.HTML_GEN_LSB_cellule.length] = new Option('" . $value_rwanda['libelle_localisation'] . "', '" . $value_rwanda['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwandaCellule .= "\n}";
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLocRwandaCellule", $jsCodeLocRwandaCellule);

    //AT-150
    // --> Page reloaded Constrution des choix disponibles pour le cellule
    if (isset($secteur) && isset($cellule)){ //AT-150 page reloaded take value from posted data
      $choices = array();
      reset($locArrayRwanda);
      while (list(, $value) = each($locArrayRwanda)) {
        if ($value['parent'] == $secteur)
          $choices[$value['id']] = $value['libelle_localisation'];
      }
      $myForm->setFieldProperties("cellule", FIELDP_ADD_CHOICES, $choices);
      $myForm->setFieldProperties("cellule", FIELDP_DEFAULT, $cellule);
    }
    else{
      $myForm->setFieldProperties("cellule", FIELDP_ADD_CHOICES, array(
          "0" => "[Aucun]"
      ));
    }
    $myForm->setFieldProperties("cellule", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("secteur", FIELDP_JS_EVENT, array("onchange" => "displayLocsRwandaCellule()"));


    $jsCodeLocRwandaVillage = "function displayLocsRwandaVillage() {\n";
    $jsCodeLocRwandaVillage .= "for (i=0; i < document.ADForm.HTML_GEN_LSB_village.length; ++i) document.ADForm.HTML_GEN_LSB_village.options[i] = null;\n"; //Vide les choix
    $jsCodeLocRwandaVillage .= "document.ADForm.HTML_GEN_LSB_village.length = 0;";
    $jsCodeLocRwandaVillage .= "document.ADForm.HTML_GEN_LSB_village.options[document.ADForm.HTML_GEN_LSB_village.length] = new Option('[Aucun]', 0, true, true);\n"; //[Aucun]
    $jsCodeLocRwandaVillage .= "document.ADForm.HTML_GEN_LSB_village.selectedIndex = 0; document.ADForm.HTML_GEN_LSB_village.length = 1; \n";
    reset($locArrayRwanda);
    while (list (, $value_rwanda) = each($locArrayRwanda)) {
      if ($value_rwanda['parent'] != '') {
        $jsCodeLocRwandaVillage .= "\tif (document.ADForm.HTML_GEN_LSB_cellule.value == " . $value_rwanda['parent'] . ")\n";
        $jsCodeLocRwandaVillage .= "\t\tdocument.ADForm.HTML_GEN_LSB_village.options[document.ADForm.HTML_GEN_LSB_village.length] = new Option('" . $value_rwanda['libelle_localisation'] . "', '" . $value_rwanda['id'] . "', false, false);\n";
      }
    }
    $jsCodeLocRwandaVillage .= "\n}";
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLocRwandaVillage", $jsCodeLocRwandaVillage);

    //AT-150
    // --> Page reloaded Constrution des choix disponibles pour le village
    if (isset($cellule) && isset($village)){ //AT-150 page reloaded take value from posted data
      $choices = array();
      reset($locArrayRwanda);
      while (list(, $value) = each($locArrayRwanda)) {
        if ($value['parent'] == $cellule)
          $choices[$value['id']] = $value['libelle_localisation'];
      }
      $myForm->setFieldProperties("village", FIELDP_ADD_CHOICES, $choices);
      $myForm->setFieldProperties("village", FIELDP_DEFAULT, $village);
    }
    else{
      $myForm->setFieldProperties("village", FIELDP_ADD_CHOICES, array(
          "0" => "[Aucun]"
      ));
    }
    $myForm->setFieldProperties("village", FIELDP_IS_REQUIRED, true);
    $myForm->setFieldProperties("cellule", FIELDP_JS_EVENT, array("onchange" => "displayLocsRwandaVillage()"));
  }else {
    // Gestion de la localisation
    // --> Construction de l'array des localisations.
    $locArray = getLocArray();
    // --> Sélection des champs à afficher dans id_loc
    reset($locArray);
    $includeChoices = array();
    while (list (, $value) = each($locArray)) {
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
    while (list (, $value) = each($locArray)) {
      if ($value['parent'] != '') {
        $jsCodeLoc .= "\tif (document.ADForm.HTML_GEN_LSB_id_loc1.value == " . $value['parent'] . ")\n";
        $jsCodeLoc .= "\t\tdocument.ADForm.HTML_GEN_LSB_id_loc2.options[document.ADForm.HTML_GEN_LSB_id_loc2.length] = new Option('" . $value['libel'] . "', '" . $value['id'] . "', false, false);\n";
      }
    }
    $jsCodeLoc .= "\n}";
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeLoc", $jsCodeLoc);
    // --> ajout des champs
    $myForm->addField("id_loc2", _("Localisation 2"), TYPC_LSB);
    $myForm->setFieldProperties("id_loc2", FIELDP_ADD_CHOICES, array(
      "0" => "[Aucun]"
    ));
    $myForm->setFieldProperties("id_loc1", FIELDP_JS_EVENT, array(
      "onchange" => "displayLocs()"
    ));

    // *** Fin de la gestion de la localisation ***
  }

    // Gestion de la categorie employe
    // --> Construction de l'array des employe.
    $catArray = getCatEmpArray();
    // --> Sélection des champs à afficher dans categorie
    reset($catArray);
    $includeChoices = array ();
    while (list (, $value) = each($catArray)) {
      if ($value['parent'] == '')
        array_push($includeChoices, $value['id']);
    }
    // --> Restriction des choix dans id_cat
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
    // --> Ajout de la fonction dans le formulaire
    $myForm->addJS(JSP_FORM, "jsCodeCat", $jsCodeCat);
    // --> ajout des champs
    $myForm->addField("classe", _("Classe"), TYPC_LSB);
    $myForm->setFieldProperties("classe", FIELDP_ADD_CHOICES, array (
      "0" => "[Aucun]"
    ));
    $myForm->setFieldProperties("categorie", FIELDP_JS_EVENT, array (
      "onchange" => "displayCat()"
    ));

    // *** Fin de la gestion de la categorie employe ***

    // Valeurs par défaut pour les dates de création et d'adhésion
    $myForm->setFieldProperties("date_adh", FIELDP_DEFAULT, date("d/m/Y"));
    $myForm->setFieldProperties("date_crea", FIELDP_DEFAULT, date("d/m/Y"));

    // Valeur par défaut pour le statut juridique
    $myForm->setFieldProperties("statut_juridique", FIELDP_DEFAULT, $SESSION_VARS['statut_juridique']);
    $myForm->setFieldProperties("statut_juridique", FIELDP_IS_LABEL, true);

    // Valeur par défaut pour l'état du client : 1 (en attente de validation)
    $myForm->setFieldProperties("etat", FIELDP_DEFAULT, 1);
    $myForm->setFieldProperties("etat", FIELDP_IS_LABEL, true);

    // Taille du textarea contenant l'adresse
    $myForm->setFieldProperties("adresse", FIELDP_WIDTH, 40);

    // Controle JS sur le champ num_tel
    $infoParamAbonnement = array();
    $infoParamAbonnement = getInfoParamAbonnement("NB_CARACTERES_TELEPHONE");
    $infoParamPrefixAbonnement = getInfoParamAbonnement("PREFIX_TELEPHONE");
    if ($infoParamAbonnement != null){
      // set num tel size via JS
      //$myForm->addJS(JSP_FORM, "jsSetNumTelSize", "document.ADForm.num_tel.size = '".(intval($infoParamAbonnement['valeur'])+1)."';");
      $myForm->addJS(JSP_FORM, "jsSetNumTelSize", "\ndocument.getElementsByName('num_tel').item(0).setAttribute('maxlength',".(intval($infoParamAbonnement['valeur'])).");\ndocument.ADForm.num_tel.size = '".(intval($infoParamAbonnement['valeur']))."';\n");
      // function JS pour verifier le champ num tel
      $jsNumTel = "";
      $jsNumTel .= "\nfunction checkNumTel() {\n";
      $jsNumTel .= "\tif (document.ADForm.num_tel.value.length != 0 && document.ADForm.num_tel.value.length >
    ".(intval($infoParamAbonnement['valeur']))."){\n";
      $jsNumTel .= "\t\tADFormValid = false;\n";
      $jsNumTel .= "\t\talert('Numéro Téléphone ne peut etre supérieure  à ".intval($infoParamAbonnement['valeur'])." chiffres!!');\n";
      $jsNumTel .= "\t\tdocument.ADForm.num_tel.focus();exit;\n";
      $jsNumTel .= "\t}\n";
      $jsNumTel .= "\tif (document.ADForm.num_tel.value.length != 0 && document.ADForm.num_tel.value.length <
    ".(intval($infoParamAbonnement['valeur']))."){\n";
      $jsNumTel .= "\t\tADFormValid = false;\n";
      $jsNumTel .= "\t\talert('Numéro Téléphone ne peut etre inférieure  à ".intval($infoParamAbonnement['valeur'])." chiffres!!');\n";
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

    // Ajout des boutons
    $myForm->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
    $myForm->setFormButtonProperties("ok",BUTP_JS_EVENT,array ("OnClick" => "checkNumTel();"));

    if ($SESSION_VARS['statut_juridique'] == 1) {
      // Personne Physique
      // Lors du clic sur OK mettre dans le champ hidden la valeur du champ disabled
      $myJS_AA = "document.ADForm.pp_id_gi_lab.value=document.ADForm.pp_id_gi.value;";
      $myForm->setFormButtonProperties("ok", BUTP_JS_EVENT, array (
                                         "OnClick" => $myJS_AA
                                       ));
    } else
      if ($SESSION_VARS['statut_juridique'] == 3) {
        // Groupe informel
        // Vérifier le nombre de membres
        $JS_check = "if (document.ADForm.gi_nbre_membr.value > 32767)
                  {
                    msg += '- "._("Le nombre de membres d\'un groupe informel ne peut pas être plus grand que")." 32767.\\n';
                    ADFormValid = false;
                  }
                    ";
        //AT-96: Groupe Informel - Controle JS sur les champs nombres d'hommes du groupe et nombres de femmes du groupe
        $msg_control_superieure = "Le totale des hommes et femmes saisie ne peut pas etre superieure au nombre de membres du group";
        $msg_control_inferieure = "Le totale des hommes et femmes saisie ne peut pas etre inferieure au nombre de membres du group";
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
      } else
        if ($SESSION_VARS['statut_juridique'] == 4) {
          // Groupe solidaire : encodage des membres
          $myForm->addHTMLExtraCode("espace", "<BR>");
          $myForm->addHTMLExtraCode("membres", "<table align=\"center\" valign=\"middle\" bgcolor=\"" . $colb_tableau . "\"><tr><td><b>"._("Membres du groupe")."</b></td></tr></table>\n");
          array_push($Order, "espace", "membres");
          $myForm->addHiddenType("nbr_membres_encodes", $SESSION_VARS["nbr_membres_encodes"]);
          for ($i = 1; $i <= $SESSION_VARS["nbr_membres_encodes"]; ++ $i) {
            $myForm->addField("num_client$i", _("Membre $i"), TYPC_INT);
            $myForm->addLink("num_client$i", "rechercher_cli$i", _("Rechercher"), "#");
            $myForm->setLinkProperties("rechercher_cli$i", LINKP_JS_EVENT, array (
                                         "onclick" => "OpenBrw('$SERVER_NAME/modules/clients/rech_client.php?m_agc=".$_REQUEST['m_agc']."&field_name=num_client$i', '"._("Recherche")."');return false;"
                                       ));
            array_push($Order, "num_client$i");
          }
        }
    if ( $CLI["statut_juridique"] == 1) {       // Personne physique
      array_push($Order, "photo");
      array_push($Order, "signature");
      array_push($Order, "province","district","secteur","cellule","village");
    }
    // Mise en ordre
    $myForm->setOrder(NULL, $Order);
    if ( $CLI["statut_juridique"] == 1) {       // Personne physique
      array_pop($Order);
      array_pop($Order);
      array_pop($Order);
    }
     //gestion champs extras
     $objChampsExtras = new HTML_Champs_Extras ($myForm,'ad_cli',$id_cli);
   	 $objChampsExtras->buildChampsExtras($SESSION_VARS['champsExtrasValues']);
   	 $SESSION_VARS['champsExtras']= $objChampsExtras-> getChampsExtras();

   	// Remplissage avec les valeurs deja encodees
    if ($global_nom_ecran_prec == "Acl-2" || $global_nom_ecran_prec == "Acl-3") {
      foreach ($SESSION_VARS['POSTED_DATAS'] as $key => $fieldname) {
        if ($fieldname != "date_crea") {
          $myForm->setFieldProperties($fieldname, FIELDP_DEFAULT, $SESSION_VARS[$fieldname]);
        }
      }
    }

    $myForm->addJS(JSP_BEGIN_CHECK, "test", $JS_check);
    $myForm->addFormButton(1, 2, "retour", _("Précédent"), TYPB_SUBMIT);
    if ($SESSION_VARS['statut_juridique'] == 4) {
      // Groupes solidaires : permet d'ajouter des membres supplementaires
      $myForm->addFormButton(1, 3, "ajout", _("Ajouter membre"), TYPB_SUBMIT);
      $myForm->setFormButtonProperties("ajout", BUTP_PROCHAIN_ECRAN, 'Acl-2');
      $myForm->setFormButtonProperties("ajout", BUTP_CHECK_FORM, false);
      $js = "nbr_membres_encodes.value = " . $SESSION_VARS['nbr_membres_encodes'] . "+1;\n";
      $myForm->setFormButtonProperties("ajout", BUTP_JS_EVENT, array (
                                         "onclick" => $js
                                       ));
    }
    $myForm->addFormButton(1, 4, "cancel", _("Annuler"), TYPB_SUBMIT);
    $myForm->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Acl-3');
    $myForm->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN, 'Acl-1');
    $myForm->setFormButtonProperties("retour", BUTP_CHECK_FORM, false);
    $myForm->setFormButtonProperties("cancel", BUTP_PROCHAIN_ECRAN, 'Gen-3');
    $myForm->setFormButtonProperties("cancel", BUTP_CHECK_FORM, false);
    $myForm->buildHTML();
    echo $myForm->getHTML();

    // Enregistrement des noms de champ qui seront postés
    // En effet tous les champs sont contenu dans le vecteur $Order sauf statut_juridique
    $Order = array_diff($Order, array (
                          "statut_juridique",
                          "espace",
                          "membres",
    	                  "espace_champs_extras",
                          "champs_extras"
                        ));
    $SESSION_VARS["POSTED_DATAS"] = $Order;

  }
/*}}}*/

/*{{{ Acl-3 : Confirmation des données */
  else
    if ($global_nom_ecran == "Acl-3") {
      global $double_affiliation;
      // Création du tableau $DATACLI qui va contenir les données utiles à l'insertion du client
      $DATACLI = array ();
      // Alimentation avec le statut juridique
      $DATACLI["statut_juridique"] = $SESSION_VARS["statut_juridique"];

      // Ajout des données sur l'utilisateur créateur
      $DATACLI["utilis_crea"] = $global_id_utilisateur;

      // Remplissage de $DATACLI avec les données postées au serveur.
      $POSTED_DATAS = $SESSION_VARS["POSTED_DATAS"];

      foreach ($POSTED_DATAS as $key => $fieldname) {
        if ($fieldname == 'photo')
          $DATACLI[$fieldname] = $SESSION_VARS['DATACLI']['photo'];
        elseif ($fieldname == 'signature') $DATACLI[$fieldname] = $SESSION_VARS['DATACLI']['signature'];
        else
          $DATACLI[$fieldname] = $ {$fieldname};
      }
      //Contrôle sur la double affiliation
      if(($SESSION_VARS["statut_juridique"] == 1) && (!$double_affiliation)){
      	$msg = "";
  		  $Num_piece = getNumPieceId($DATACLI['pp_type_piece_id']);
			  // Vérifier que le numéro de la pièce d'identité n'est pas déjà utilisé
			  for($i = 0;$i < count($Num_piece);$i++){
			  	if($DATACLI['pp_nm_piece_id'] == $Num_piece[$i]["pp_nm_piece_id"]){
			  		$msg = _("Double affiliation interdite : Le numéro de la pièce d'identité est déjà utilisé.");
			  	}
			  }
		    if ($msg != "") {
		      $colb_tableau = '#e0e0ff';
		      $MyPage = new HTML_erreur(_("Erreur dans la saisie des infos du client "));
		      $MyPage->setMessage($msg);
		      $MyPage->addButton(BUTTON_OK, "Acl-2");
		      $MyPage->buildHTML();
		      echo $MyPage->HTML_code;
		      exit();
		    }
      }

      // Date de création du client est la date système
      $DATACLI["date_crea"] = date("d/m/Y");

      // Traitement spécial pour le revenu
      if (isset ($pp_revenu))
        $DATACLI["pp_revenu"] = recupMontant($pp_revenu);

      // Traitement spécial pour le casier judiciaire
      if (!isset($pp_casier_judiciaire))
        $DATACLI['pp_casier_judiciaire'] = 'f';
      
      // Traitement spécial pour le VIP
      if (!isset($pp_is_vip)) {
        $DATACLI['pp_is_vip'] = 'f';
      }

      // Gestion des images
      if (isset ($IMAGES))
        foreach ($IMAGES as $imageName => $imagePath)
        $DATACLI[$imageName] = $imagePath;

      if ($DATACLI['id_client'] != NULL && client_exist($DATACLI['id_client'])) {
        $myHtmlErr = new HTML_Erreur(_("Erreur sur le numéro de client"));
        $myHtmlErr->setMessage(sprintf(_("Le numéro de client %s est déjà utilisé par un autre client"), $DATACLI['id_client']));
        $myHtmlErr->addButton("BUTTON_OK", 'Acl-2');
        $myHtmlErr->buildHTML();
        echo $myHtmlErr->HTML_code;
      } else {
        $Title = _("Confirmation d'insertion du nouveau client");
        $DATACLI["qualite"] = 1;

        // Création du formulaire : écran de confirmation d'insertion
        $myForm = new HTML_GEN2(_("Confirmation de l'insertion du client"));

        $include = array_keys($DATACLI);
        for ($i = 1; $i <= $SESSION_VARS["nbr_membres_encodes"]; ++ $i)
          $include = array_diff($include, array ("num_client$i"));
      //Enlever les champs extras
       /* foreach($SESSION_VARS["champs_extras"] AS $key => $valeur) {
        	$include = array_diff($include, array ($valeur["code"]));
        }*/
        $myForm->addTable("ad_cli", OPER_INCLUDE, $include);
        if (isset($IMAGES))
          foreach ($IMAGES as $imageName => $url)
          $myForm->addField($imageName,_($imageName),TYPC_IMG);
        // Aucun champ ne doit etre modifiable
        $myForm->setFieldProperties("*", FIELDP_IS_LABEL, true);
        $myForm->setOrder(NULL, $include);

        // Groupe solidaire : afficher la liste des membres du groupe
        if ($SESSION_VARS['statut_juridique'] == 4) {
          // Groupe solidaire : affichage des membres
          $myForm->addHTMLExtraCode("espace", "<br/>");
          $myForm->addHTMLExtraCode("membres", "<table align=\"center\" valign=\"middle\" bgcolor=\"" . $colb_tableau . "\"><tr><td><b>"._("Membres du groupe")."</b></td></tr></table>\n");
          for ($i = 1; $i <= $SESSION_VARS["nbr_membres_encodes"]; ++ $i) {
            if (($DATACLI["num_client$i"] != NULL) && (strlen($DATACLI["num_client$i"]) > 0)) {
              // vérification de l'etat des membres
              $etat = getEtatClient($DATACLI["num_client$i"]);
              if ($etat != 2) {
                $myHtmlErr = new HTML_Erreur(_("Erreur dans l'encodage des membres"));
                if ($etat == NULL) {
                  $myHtmlErr->setMessage(sprintf(_("Attention, le client %s n'existe pas !"), $DATACLI["num_client$i"]));
                } else {
                  $myHtmlErr->setMessage(sprintf(_("Attention, le client %s n'est pas actif, son état actuel est %s."), $DATACLI["num_client$i"], adb_gettext($adsys["adsys_etat_client"][$etat])));
                }
                $myHtmlErr->addButton("BUTTON_OK", 'Acl-2');
                $myHtmlErr->buildHTML();
                echo $myHtmlErr->HTML_code;
                break;
              }
              // Vérifie si un client a dépassé le nombre de groupe autorisé dans l'agence
						  $ag_data = getAgenceDatas($global_id_agence);
						  if($ag_data["nb_group_for_cust"] > 0){
									$num_client = $DATACLI["num_client$i"];
									$list_grp_memb = getGroupSol($num_client);
									if(sizeof($list_grp_memb->param) >= $ag_data["nb_group_for_cust"]){
										$myHtmlErr = new HTML_erreur(_("Echec lors de l'ajout du client."));
								    $myHtmlErr->setMessage(sprintf(_("Erreur: le client '%s' ne peut pas appartenir à plus de '%s' groupes, nombre maximum autorisé dans l'agence"),$num_client,$ag_data["nb_group_for_cust"]));
								    $myHtmlErr->addButton("BUTTON_OK", 'Acl-2');
								    $myHtmlErr->buildHTML();
								    echo $myHtmlErr->HTML_code;
								    exit();
									}

						  }
            }
            $myForm->addField("num_client$i", _("Membre $i"), TYPC_INT);
            $myForm->setFieldProperties("num_client$i", FIELDP_DEFAULT, $DATACLI["num_client$i"]);
            $myForm->setFieldProperties("num_client$i", FIELDP_IS_LABEL, true);
          }
        }

        // Alimentation avec les valeurs saisies à l'écran précédent
        foreach ($include as $key => $value) {
          if ($value == "photo" || $value == "signature") { // Champs image ==> on ne reprend que le nom du fichier
            global $http_prefix;
            if (is_file($DATACLI[$value])) {
            	$url = $http_prefix."/images_tmp/".basename($DATACLI[$value]);
            } else {
            	$url ="/adbanking/images/travaux.gif";
            }
            $myForm->setFieldProperties($value, FIELDP_IMAGE_URL, $url);
          } else
            $myForm->setFieldProperties($value, FIELDP_DEFAULT, $DATACLI[$value]);
        }
        // Traitement spécial pour le casier judiciaire
        if ($DATACLI['pp_casier_judiciaire'] == 'f')
          $myForm->setFieldProperties("pp_casier_judiciaire", FIELDP_DEFAULT, false);

        // Traitement spécial pour le VIP
        if ($DATACLI['pp_is_vip'] == 'f') {
          $myForm->setFieldProperties("pp_is_vip", FIELDP_DEFAULT, false);
        }



        //Traitement pour les champs extras
        if(sizeof($SESSION_VARS['champsExtras'])> 0 ) {
        	$SESSION_VARS['champsExtrasValues'] = 
        		HTML_Champs_Extras::buildDataChampsEXtrasValues($SESSION_VARS['champsExtras'],$_POST);
        	$objChampsExtras = new HTML_Champs_Extras ($myForm,'ad_cli',$id_cli);
   	 		$objChampsExtras->buildChampsExtras($SESSION_VARS['champsExtrasValues'],TRUE);
        	$DATACLI["champsExtras"] = $SESSION_VARS['champsExtrasValues'];
        }
        /*if(isset($SESSION_VARS["champs_extras"])){
        	$champs_extras = array();
        	$myForm->addHTMLExtraCode("espace_champs_extras", "<BR>");
    			$myForm->addHTMLExtraCode("champs_extras", "<table align=\"center\" valign=\"middle\" bgcolor=\"" . $colb_tableau . "\"><tr><td><b>"._("Informations supplémentaires")."</b></td></tr></table>\n");
        	foreach($SESSION_VARS["champs_extras"] AS $key => $valeur) {
        		$myForm->addField($valeur['code'], $valeur['libel'],trim( $valeur['type']));
          	$myForm->setFieldProperties($valeur['code'], FIELDP_DEFAULT, $DATACLI[$valeur['code']]);
          	$myForm->setFieldProperties($valeur['code'], FIELDP_IS_LABEL, true);
          	$champs_extras[$key] = $DATACLI[$valeur['code']];
          	unset($DATACLI[$valeur['code']]);
        	}
        	$DATACLI["serial_champs_extras"] = serialize($champs_extras);
        }*/

        // Ajout des boutons
        if (!isset($myHtmlErr)) {
          // Il n'y a pas eu de problème à la confirmation
          $myForm->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
          $myForm->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Acl-4');
          $myForm->addFormButton(1, 2, "precedent", _("Précédent"), TYPB_SUBMIT);
          $myForm->setFormButtonProperties("precedent", BUTP_PROCHAIN_ECRAN, 'Acl-2');
          $myForm->addFormButton(1, 3, "annuler", _("Annuler"), TYPB_SUBMIT);
          $myForm->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, 'Gen-3');
          $myForm->buildHTML();
          echo $myForm->getHTML();
        }
      }
      // Enregistrement dans SESSION_VARS
      $SESSION_VARS["DATACLI"] = $DATACLI;
    }
/*}}}*/

/*{{{ Acl-4 : Perception versement initial */
    else
      if ($global_nom_ecran == "Acl-4") {
      	global $global_monnaie;
      	setMonnaieCourante($global_monnaie);
          
          global $global_id_client;
          //control_souscription agence
          $souscription_ouvert = checkSouscription();
        //Pour le cas du transfert d'un client, il n'y a pas de perception des frais d'adhésion
        //FIXME : il faut changer le transfert de clients
        $AGC = getAgenceDatas($global_id_agence);
        $SESSION_VARS["montant_frais_adhesion"]= getMontantDroitsAdhesion($SESSION_VARS["DATACLI"]["statut_juridique"]);
        $PROD = getProdEpargne(getBaseProductID($global_id_agence));
        $mnt_min = $PROD['mnt_min'];
        $SESSION_VARS["mnt_min"] = $mnt_min;debug($AGC["paiement_parts_soc_gs"]);
        $mnt_dpt_min =   $PROD['mnt_dpt_min'];
        $SESSION_VARS["mnt_dpt_min"] = $mnt_dpt_min;
        // L'instutution est une mutuelle avec PS
        if( $global_type_structure == 1) {//groupe solidaire ne paye pas les part sociales
        	if( $SESSION_VARS["DATACLI"]["statut_juridique"] == 4 AND $AGC["paiement_parts_soc_gs"]=='f')
        	  $is_mutuel_ps =false;
        	else // groupe solidaire paiement les parts sociales
        	  $is_mutuel_ps=true;
        }
        // Le script suivant permet la mise à jour automatique du label Montant
        echo "<script type=\"text/javascript\">
        function update()
      {
        montant = (" . $AGC["val_nominale_part_sociale"] . " * recupMontant(document.ADForm.nbre_parts.value));
        document.ADForm.somme.value = formateMontant(montant);
      }
        function setToZero()
      {
        montant = 0;
        document.ADForm.somme.value = '';
      }
        function setPaye(valeur)
      {
        document.ADForm.paye.value = valeur;
      }
        montant = (" . $AGC["val_nominale_part_sociale"] . ");

        </script>\n";

        $codejs = "\nfunction setTransfert(valeur) {\n";
        $codejs .= "document.ADForm.paye.value = valeur;\n";
        $codejs .= "document.ADForm.transfert_client.value = true\n}\n";

        $SESSION_VARS['setPaye'] = false; //permet de savoir par la suite ce qui est payé : rien, frais d'adhésion, adhésion + parts sociales

        $myForm = new HTML_GEN2();
        $myForm->setTitle(_("Options client"));

        //Code HTML pour la présentation à l'écran
        $xtra1 = "<b>" . _("Dénomination du client") . "</b>";
        $myForm->addHTMLExtraCode("denomination", $xtra1);
        $myForm->setHTMLExtraCodeProperties("denomination", HTMP_IN_TABLE, true);

        $Order = array (
                   "denomination"
                 );

        // Construction du tableau contenant les champs à mettre en tant que labels
        $Label = array ();
        if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 1) {
          $cli_include = array (
                           "pp_nom",
                           "pp_prenom"
                         );
          array_push($Order, "pp_nom", "pp_prenom");
          array_push($Label, "pp_nom", "pp_prenom");
        } else
          if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 3 || $SESSION_VARS["DATACLI"]["statut_juridique"] == 4) {
            $cli_include = array (
                             "gi_nom"
                           );
            array_push($Order, "gi_nom");
            array_push($Label, "gi_nom");
          } else
            if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 2) {
              $cli_include = array (
                               "pm_raison_sociale"
                             );
              array_push($Order, "pm_raison_sociale");
              array_push($Label, "pm_raison_sociale");
            }

        if ($is_mutuel_ps) { // L'instutution est une mutuelle avec PS
         // array_push($cli_include, "nbre_parts");
        	
          $ag_include = array ("val_nominale_part_sociale");
        }

        //ajout table client
        $myForm->addTable("ad_cli", OPER_INCLUDE, $cli_include);

        // Droits d'adhésion
        $xtra1 = "<b>" . _("Droits d'adhésion") . "</b>";
        $myForm->addHTMLExtraCode("droits_adh_lab", $xtra1);
        $myForm->setHTMLExtraCodeProperties("droits_adh_lab", HTMP_IN_TABLE, true);
        array_push($Order, "droits_adh_lab");
        $myForm->addField("montant_droits_adhesion", _("Montant des droits d'adhésion"), TYPC_MNT);
        $myForm->setFieldProperties("montant_droits_adhesion", FIELDP_DEFAULT, $SESSION_VARS["montant_frais_adhesion"] );
        $myForm->setFieldProperties("montant_droits_adhesion", FIELDP_CAN_MODIFY, true);
        $myForm->setFieldProperties("montant_droits_adhesion", FIELDP_IS_LABEL, true);
        array_push($Order, "montant_droits_adhesion");

          if ($is_mutuel_ps) { // L'instutution est une mutuelle avec PS
          // Parts sociales
          $xtra1 = "<b>" . _("Parts sociales") . "</b>";
          $myForm->addHTMLExtraCode("ps_lab", $xtra1);
          $myForm->setHTMLExtraCodeProperties("ps_lab", HTMP_IN_TABLE, true);
          $myForm->addTable("ad_agc", OPER_INCLUDE, $ag_include);
            //Controle part tranche part sociale
         if( $AGC["tranche_part_sociale"] == "f"){ 
          $myForm->addField("nbre_parts", (_("Nombre de PS à souscrire") ), TYPC_INT);
          $myForm->setFieldProperties("nbre_parts", FIELDP_JS_EVENT, array ("onchange" => "verifPS();"));
          $myForm->addField("nbre_parts_libs", (_("Nombre de PS à libérer") ), TYPC_INT);
          $myForm->setFieldProperties("nbre_parts_libs", FIELDP_IS_LABEL, true);
          $myForm->setFieldProperties("nbre_parts_libs", FIELDP_DEFAULT, 0);
          $myForm->addHiddenType("nbre_parts_lib", 0 );
         }else{//tranche PS true 
         	$myForm->addField("nbre_parts", (_("Nombre de PS à souscrire") ), TYPC_INT);
            $myForm->setFieldProperties("nbre_parts", FIELDP_JS_EVENT, array ("onchange" => "verifSous_tranche();"));
            $myForm->addField("tranche_nbr_parts", _("Nombre de PS à libérer"), TYPC_INT);
	        $myForm->setFieldProperties("tranche_nbr_parts", FIELDP_IS_LABEL, true);
	        $myForm->addHiddenType("hid_tranche_nbr_parts", 0 );
         
         }

    $codejs124 = "function verifPS()
	{
         
      var nbre_ps_sous = parseInt(document.ADForm.nbre_parts.value) ;
      var montant = (" . $AGC["val_nominale_part_sociale"] . " * nbre_ps_sous);
         		
        if(nbre_ps_sous > 0){
         		document.ADForm.nbre_parts_libs.value = nbre_ps_sous ;
      		    document.ADForm.nbre_parts_lib.value = nbre_ps_sous ;
      			document.ADForm.somme_g.value =  formateMontant(montant);
		        document.ADForm.somme.value = formateMontant(montant);
         		}
         	else{
      		    document.ADForm.nbre_parts_libs.value = 0 ;
      		    document.ADForm.nbre_parts_lib.value = 0 ;
      			document.ADForm.somme_g.value = 0;
		        document.ADForm.somme.value = 0;
      		}	

         
      }";
         
		
		$codejs125 = "
				function setValues()
	{
		    var nbre_ps_sous = parseInt(document.ADForm.nbre_parts.value) ;
            var nbre_ps_lib = parseInt(document.ADForm.hid_tranche_nbr_parts.value) ;
		    var versement = recupMontant(document.ADForm.somme.value);
				
			var versement_max = nbre_ps_sous * " . $AGC["val_nominale_part_sociale"] . ";
		    
           if  ( (nbre_ps_lib > nbre_ps_sous) ){
			alert('Nombre de parts sociale liberer ne peut pas depasser le nombre souscrites : ' + nbre_ps_sous );
          
      		document.ADForm.nbre_parts_lib.value = 0;
			document.ADForm.tranche_nbr_parts.value = 0;
      		document.ADForm.hid_tranche_nbr_parts.value = 0;
			}
			 if ( versement < " . $AGC["val_nominale_part_sociale"] . " ){
            var update_liber = parseInt(Math.floor(versement /  " . $AGC["val_nominale_part_sociale"] . "));
      		document.ADForm.tranche_nbr_parts.value = update_liber;
      		document.ADForm.hid_tranche_nbr_parts.value = update_liber;
		    		}	
				
		    if ( versement >= " . $AGC["val_nominale_part_sociale"] . " ){
            var update_liber = parseInt(Math.floor(versement /  " . $AGC["val_nominale_part_sociale"] . "));
      		document.ADForm.tranche_nbr_parts.value = update_liber;
      		document.ADForm.hid_tranche_nbr_parts.value = update_liber;
		    		}
            		
            if ( versement > 0 ){
              if((document.ADForm.nbre_parts.value =='' )||(isNaN(nbre_ps_sous)== true)){
              alert('Veuillez renseigner le champ Nombre de PS à souscrire');
			    document.ADForm.tranche_nbr_parts.value = 0;
      		     document.ADForm.hid_tranche_nbr_parts.value = 0;
                 document.ADForm.somme.value = 0;
            		}
		    		}	
	
            if ( versement > versement_max ){
            	alert('Le montant de libération ne peut pas depasser la valeur de souscription ' + versement_max +' ".$global_monnaie."');
      		document.ADForm.tranche_nbr_parts.value = 0;
      		document.ADForm.hid_tranche_nbr_parts.value = 0;
            document.ADForm.somme.value = 0;
		    		}
		
	}";
		
		
		 $codejs127 = "function verifSous_tranche()
	{
            var nbre_ps_sous = parseInt(document.ADForm.nbre_parts.value) ;
            var nbre_ps_lib = parseInt(document.ADForm.hid_tranche_nbr_parts.value) ;
		    var versement = recupMontant(document.ADForm.somme.value);
		
         		 if ( versement > 0 ){
            		if((document.ADForm.nbre_parts.value =='' )||(isNaN(nbre_ps_sous)== true)){
            		alert('Veuillez renseigner le champ Nombre de PS à souscrire d\'abord ');
			     document.ADForm.tranche_nbr_parts.value = 0;
      		     document.ADForm.hid_tranche_nbr_parts.value = 0;
                 document.ADForm.somme.value = 0;
            		}
		    	}
		
      }"; 
		 
		 $codejs128 = "function verifSous()
	{
            
		 		var nbre_ps_sous = parseInt(document.ADForm.nbre_parts.value) ;
                var nbre_ps_lib = parseInt(document.ADForm.nbre_parts_lib.value) ;
		 		
		  
            if ( nbre_ps_lib > 0 ){
            		if((document.ADForm.nbre_parts.value =='' )||(isNaN(nbre_ps_sous)== true)){
            		alert('Veuillez renseigner le champ Nombre de PS à souscrire d\'abord ');
			    document.ADForm.nbre_parts.value = 0;
      		    document.ADForm.nbre_parts_lib.value = 0;
		 		document.ADForm.somme_g.value = 0;
		        document.ADForm.somme.value = 0;
            		}else if(nbre_ps_lib> nbre_ps_sous){
		 		alert('Nombre de parts sociale liberer ne peut pas depasser le nombre souscrites : ' + nbre_ps_sous);
      		    document.ADForm.nbre_parts_lib.value = 0;
		 	    document.ADForm.somme_g.value = 0;
		        document.ADForm.somme.value = 0;
		 		}
		    	}
	
		 
      }";
		 

		if( $AGC["tranche_part_sociale"] == "f"){
          $myForm->addJS ( JSP_FORM, "JS124", $codejs124 );
          $myForm->addJS ( JSP_FORM, "JS128", $codejs128 );
          
		}else{
			
			$myForm->addJS ( JSP_FORM, "JS125", $codejs125 );
			$myForm->addJS ( JSP_FORM, "JS127", $codejs127 );
			
		}
          /**
           *************************************************************
           * LES JS qui suive a adapter 20032015_ a completer by 23032015
           *************************************************************
           */
           
          $nbre_part_max = $AGC['nbre_part_social_max_cli'];
          
          //jS control souscription au niveau de l'agence
          if (($souscription_ouvert == x)){ //false
          	$souscription_ouvert = 0;
          	$ExtraJS .= "\n\t  if((parseFloat(document.ADForm.nbre_parts.value) >  " . $souscription_ouvert ." )||(parseFloat(document.ADForm.somme.value)>0 ))" ;
          	//control specifique par tranche PS
           	if( $AGC["tranche_part_sociale"] == "t"){ 
          	$ExtraJS .= "\n\t { ADFormValid = false; msg+='" . sprintf ( _ ( " %s !\\n Souscription non-autorisé  %s" ), $error [ERR_MAX_PS_SOUSCRIT_AGC],null) . "';document.ADForm.nbre_parts.value = 0 ; document.ADForm.tranche_nbr_parts.value = 0 ;document.ADForm.somme.value = 0 ;   \n\t}";
          	 }else{
          	$ExtraJS .= "\n\t {ADFormValid = false;document.ADForm.nbre_parts.value = 0 ;document.ADForm.nbre_parts_libs.value = 0; document.ADForm.nbre_parts_lib.value = 0 ;document.ADForm.somme.value = 0;document.ADForm.somme_g.value = 0 ; ADFormValid = false;  msg+='" . sprintf ( _ ( " %s !\\n Souscription non-autorisé  %s" ), $error [ERR_MAX_PS_SOUSCRIT_AGC],null) . "';\n\t}"; 
          		
          	 }
          }
          //souscription limité
          if (($souscription_ouvert > 0) ){
          	$ExtraJS .= "\n\t  if((parseFloat(document.ADForm.nbre_parts.value) >  " . $souscription_ouvert ." )||((parseFloat(document.ADForm.somme.value))>(" . $souscription_ouvert ." * " . $AGC["val_nominale_part_sociale"] . ")))" ;
          	//control specifique par tranche PS
          	if( $AGC["tranche_part_sociale"] == "t"){
          	$ExtraJS .= "\n\t { ADFormValid = false; msg+='" . sprintf ( _ ( " %s !\\n PS Restant à souscrire dans l\'agence : %s" ), $error [ERR_MAX_PS_SOUSCRIT_AGC], $souscription_ouvert ) . "';document.ADForm.nbre_parts.value = $souscription_ouvert ; document.ADForm.tranche_nbr_parts.value = 0 ;document.ADForm.somme.value = 0 ;   \n\t}";
			} else {
			$ExtraJS .= "\n\t { ADFormValid = false; msg+='" . sprintf ( _ ( " %s !\\n PS Restant à souscrire dans l\'agence : %s" ), $error [ERR_MAX_PS_SOUSCRIT_AGC], $souscription_ouvert ) . "';document.ADForm.nbre_parts.value = $souscription_ouvert  ;document.ADForm.nbre_parts_libs.value = $souscription_ouvert ; document.ADForm.nbre_parts_lib.value = $souscription_ouvert ;document.ADForm.somme.value = formateMontant($souscription_ouvert * ". $AGC["val_nominale_part_sociale"] .") ;document.ADForm.somme_g.value = formateMontant($souscription_ouvert * ". $AGC["val_nominale_part_sociale"] .") ;  \n\t}";
				
			}
		}

          $myForm->addJS(JSP_BEGIN_CHECK, "extrajs", $ExtraJS);
   
          //Fin d'evol 361
          array_push($Label, "val_nominale_part_sociale", "montant_droits_adhesion");
          if( $AGC["tranche_part_sociale"] == "f"){
          $myForm->addField("somme_g", _("Montant de la libération"), TYPC_MNT);
          $myForm->setFieldProperties("somme_g", FIELDP_IS_LABEL, true);
          $myForm->setFieldProperties("somme_g", FIELDP_DEFAULT, 0);//onload page valeur par defaut
          
          $myForm->addHiddenType("somme",0);
          
          }else{
          		$myForm->addField("somme", _("Montant de la libération"), TYPC_MNT);
          		$myForm->setFieldProperties("somme", FIELDP_DEFAULT, 0);//onload page valeur par defaut
          		$myForm->setFieldProperties ( "somme", FIELDP_JS_EVENT, array (
          				"onChange" => "setValues();"));				
          }
          
          // verifier le nbre de part sociale max souscripte autorisé pour un client
 		      $nbre_part_max=$AGC['nbre_part_social_max_cli'];
 		      if(($nbre_part_max>0 )) {
 		              $js_nbre_ps .= "\n\t if (".$nbre_part_max." < ( document.ADForm.nbre_parts.value) ) \n\t {ADFormValid = false;msg+='".$error[ERR_NBRE_MAX_PS]." !\\n "._("nombre max de parts sociales")." : ".$nbre_part_max."';\n\t}";
 		      }
 		      $myForm->addJS (JSP_BEGIN_CHECK, "JS",$js_nbre_ps );

 		      //control specifique par tranche PS
 		      if( $AGC["tranche_part_sociale"] == "f"){
 		      	array_push($Order, "ps_lab", "nbre_parts","nbre_parts_libs" ,"val_nominale_part_sociale","somme_g");
 		      }else{
 		      	  array_push($Order, "ps_lab", "nbre_parts","tranche_nbr_parts" ,"val_nominale_part_sociale", "somme");
 		      }  
        

          // Valeurs par défaut
          $fill = new FILL_HTML_GEN2();
          $fill->addFillClause("agence", "ad_agc");
          $fill->addCondition("agence", "id_ag", $global_id_agence);
          $fill->addManyFillFields("agence", OPER_INCLUDE, $ag_include);
          $fill->fill($myForm);
		if ($AGC ["tranche_part_sociale"] == "f") {
			$myForm->setFieldProperties ( "nbre_parts", FIELDP_DEFAULT, 0 ); // valeur par defaut pour la souscription
			$myForm->setFieldProperties ( "nbre_parts_libs", FIELDP_DEFAULT, 0 );
			
		} else {
			$myForm->setFieldProperties ( "nbre_parts", FIELDP_DEFAULT, 0 ); // valeur par defaut pour la souscription
			$myForm->setFieldProperties ( "tranche_nbr_parts", FIELDP_DEFAULT, 0 ); // valeur par defaut pour la liberation
			
		}
	}

        // Utilisation du compte de bases
        $xtra1 = "<b>" . _("Compte de base") . "</b>";
        $myForm->addHTMLExtraCode("cb_lab", $xtra1);
        $myForm->setHTMLExtraCodeProperties("cb_lab", HTMP_IN_TABLE, true);
        array_push($Order, "cb_lab");

        // On pose la question
        if ($global_type_structure == 1) { // MEC
          $choix = array (1 => _("Oui"));
        } else
          if ($global_type_structure == 2) { // Crédit direct
            $choix = array (0 => _("Non"));
          } else
            if ($global_type_structure == 3) { // Banque
              $choix = array (1 => _("Oui"), 0 => _("Non"));
            }

        $myForm->addField("ouvre_cpt_base", (_("Le client ouvre un compte de base en ") . $global_monnaie), TYPC_LSB);
        $myForm->setFieldProperties("ouvre_cpt_base", FIELDP_ADD_CHOICES, $choix);
        $myForm->setFieldProperties("ouvre_cpt_base", FIELDP_HAS_CHOICE_AUCUN, false);
        if (isset ($SESSION_VARS["ouvre_cpt_base"])) // On vient de l'écran Acl-5
          $myForm->setFieldProperties("ouvre_cpt_base", FIELDP_DEFAULT, $SESSION_VARS["ouvre_cpt_base"]);
        else
          if ($global_type_structure != 2)
            $myForm->setFieldProperties("ouvre_cpt_base", FIELDP_DEFAULT, 1);
          else {
            $myForm->setFieldProperties("ouvre_cpt_base", FIELDP_DEFAULT, 0);
          }
        $myForm->setFieldProperties("ouvre_cpt_base", FIELDP_JS_EVENT, array ("onchange" => "if document.ADForm.HTML_GEN_LSB_ouvre_cpt_base.value == 0) document.ADForm.intitule_compte.disabled=true; else document.ADForm.intitule_compte.disabled=false;"
                                                                             ));
        $myForm->addField("depot_min", _("Montant minimum à verser sur le compte"), TYPC_MNT);
        $myForm->setFieldProperties("depot_min", FIELDP_IS_LABEL, true);

        // Intitulé du compte
        $myForm->addTable("ad_cpt", OPER_INCLUDE, array ("intitule_compte"));
        if (isset ($SESSION_VARS["DATACPT"]["intitule_compte"])) {
          $myForm->setFieldProperties("intitule_compte", FIELDP_DEFAULT, $SESSION_VARS["DATACPT"]["intitule_compte"]);
        } else {
          $myForm->setFieldProperties("intitule_compte", FIELDP_DEFAULT, _("Compte de base"));
        }

        /****** DDL Comptes epargne ******/
        //affichage de tous les produits d'épargne DAV
        $prod_epargne = getListProdEpargneDAV();

        $choix_epargne = array();
        if (isset($prod_epargne)) {
            foreach ($prod_epargne as $key => $value) {
                $choix_epargne[$value["id"]] = trim($value["libel"]);
            }
        }

        $myForm->addField("id_prod_epg", _("Produit épargne"), TYPC_LSB);
        $myForm->setFieldProperties("id_prod_epg", FIELDP_ADD_CHOICES, $choix_epargne);
        
        $codejs0 = "
                    function getInfoEpargne()
                  {
                    if (document.ADForm.HTML_GEN_LSB_id_prod_epg.value == 0){
                        document.ADForm.depot_min.value = formateMontant(0);
                        document.ADForm.depot_min_val.value = formateMontant(0);
                    }
                    ";
        if (isset($prod_epargne) && count($prod_epargne)>0) {
            $myForm->setFieldProperties("id_prod_epg", FIELDP_HAS_CHOICE_AUCUN, false);
            foreach ($prod_epargne as $key => $value) {
                if ($value["mnt_dpt_min"] > 0 && $value["mnt_dpt_min"] > $value["mnt_min"]){
                    $epg_mnt_min = trim($value["mnt_dpt_min"]);
                }
                else{
                    $epg_mnt_min = trim($value["mnt_min"]);
                }

                $codejs0 .= "
                     if (document.ADForm.HTML_GEN_LSB_id_prod_epg.value == ".$value["id"].")
                     {
                        document.ADForm.depot_min.value = formateMontant(" . $epg_mnt_min . ");
                        document.ADForm.depot_min_val.value = " . $epg_mnt_min . ";";
                $codejs0 .= "
                   };";
            }
        }else{
            $myForm->setFieldProperties("id_prod_epg", FIELDP_HAS_CHOICE_AUCUN, true);
        }

        $codejs0 .= "
               }
                 getInfoEpargne();";

        $myForm->setFieldProperties("id_prod_epg", FIELDP_JS_EVENT, array("onChange" => "getInfoEpargne();"));
        $myForm->addJS(JSP_FORM, "JS0", $codejs0);
        /****** DDL Comptes epargne ******/

        // Si la val par défaut de ouvre_cpt_base à 0, alors il faut désactiver ce champ
        // On le fait en JS car c'est plus facile pour accéder à la valeur d'u item
        $jsIntit = "if (document.ADForm.HTML_GEN_LSB_ouvre_cpt_base.value == 0)
                   document.ADForm.intitule_compte.disabled = true;";
        $myForm->addJS(JSP_FORM, "jsIntit", $jsIntit);

        array_push($Order, "ouvre_cpt_base", "intitule_compte", "depot_min"); // , "id_prod_epg"

        if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 1) {
          $myForm->setFieldProperties("pp_nom", FIELDP_DEFAULT, $SESSION_VARS["DATACLI"]["pp_nom"]);
          $myForm->setFieldProperties("pp_prenom", FIELDP_DEFAULT, $SESSION_VARS["DATACLI"]["pp_prenom"]);
        } else
          if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 3 || $SESSION_VARS["DATACLI"]["statut_juridique"] == 4) {
            $myForm->setFieldProperties("gi_nom", FIELDP_DEFAULT, $SESSION_VARS["DATACLI"]["gi_nom"]);
          } else
            if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 2) {
              $myForm->setFieldProperties("pm_raison_sociale", FIELDP_DEFAULT, $SESSION_VARS["DATACLI"]["pm_raison_sociale"]);
            }

        while (list ($key, $value) = each($Label)) {
          $myForm->setFieldProperties($value, FIELDP_IS_LABEL, true);
        }

        //boutons pour la gestion du paiement
        $myForm->addFormButton(1, 1, "rien", "      "._("Payera plus tard")."       ", TYPB_SUBMIT);
        $bouton_post = false;
        if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 1) {//Personne physique
          if ($AGC['pp_montant_droits_adhesion']!=0) {
            $myForm->addFormButton(1, 2, "post", "  "._("Paye les frais d'adhésion")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          } else {
            $myForm->addFormButton(1, 2, "post", "  "._("Versement initial")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          }
        }
        if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 2) {    // Personne morale
          if ($AGC['pm_montant_droits_adhesion']!=0) {
            $myForm->addFormButton(1, 2, "post", "  "._("Paye les frais d'adhésion")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          } else {
            $myForm->addFormButton(1, 2, "post", "  "._("Versement initial")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          }
        }
        if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 3) { // Groupe informel
          if ($AGC['gi_montant_droits_adhesion'] != 0) {
            $myForm->addFormButton(1, 2, "post", "  "._("Paye les frais d'adhésion")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          } else {
            $myForm->addFormButton(1, 2, "post", "  "._("Versement initial")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          }
        }
        if ($SESSION_VARS["DATACLI"]["statut_juridique"] == 4) { // Groupe solidaire
          if ($AGC['gs_montant_droits_adhesion']!=0) {
            $myForm->addFormButton(1, 2, "post", "  "._("Paye les frais d'adhésion")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          } else {
            $myForm->addFormButton(1, 2, "post", "  "._("Versement initial")."  ", TYPB_SUBMIT);
            $bouton_post = true;
          }
        }

        $myForm->addFormButton(2, 1, "annuler", "         "._("Annuler")."         ", TYPB_SUBMIT);

        //initialisation des écrans suivants
        if ($is_mutuel_ps) {//pour une MEC -ps

          $myForm->addFormButton(1, 3, "tout", _("Paye les frais d'adhésion et les PS"), TYPB_SUBMIT);
          $myForm->setFormButtonProperties("tout", BUTP_PROCHAIN_ECRAN, 'Acl-5');
          $myForm->setFormButtonProperties("tout", BUTP_JS_EVENT, array (
                                             "onclick" => "setPaye(2);"
                                           ));
        }

        $myForm->setFormButtonProperties("annuler", BUTP_PROCHAIN_ECRAN, 'Gen-3');
        if ($bouton_post==true)
          $myForm->setFormButtonProperties("post", BUTP_PROCHAIN_ECRAN, 'Acl-5');

        $myForm->setFormButtonProperties("rien", BUTP_PROCHAIN_ECRAN, 'Acl-6');
        //javascript boutons
        $myForm->setFormButtonProperties("rien", BUTP_CHECK_FORM, false);
        if ($bouton_post==true)
          $myForm->setFormButtonProperties("post", BUTP_CHECK_FORM, false);
        $myForm->setFormButtonProperties("annuler", BUTP_CHECK_FORM, false);
        if ($bouton_post==true)
          $myForm->setFormButtonProperties("post", BUTP_JS_EVENT, array ("onclick" => "setPaye(1);"));
        $myForm->setFormButtonProperties("rien", BUTP_JS_EVENT, array ("onclick" => "setPaye(0);"));

        //les champs invisibles à transmettre aux autres pages
        $myForm->addHiddenType("paye");
        $myForm->addHiddenType("transfert_client");

        $myForm->addJS(JSP_FORM, "JS1", $codejs);

        $myForm->setOrder(NULL, $Order);

        if ((recupMontant($mnt_dpt_min)) > 0){
            $mnt_min=$mnt_dpt_min;
        }

        $myForm->setFieldProperties("depot_min", FIELDP_DEFAULT, $mnt_min);
        
        // Hidden value
        $myForm->addHiddenType("depot_min_val", $mnt_min);
        
        $myForm->buildHTML();
        echo $myForm->getHTML();
      }
/*}}}*/

/*{{{ Acl-5 : Enregistrement du versement initial */
      else
        if ($global_nom_ecran == 'Acl-5') {
        	 global $global_monnaie;
          setMonnaieCourante($global_monnaie);
          	
        	//GESTION SOUSCRIPTION PS
        	$AGC = getAgenceDatas($global_id_agence);
        	
        	if ($AGC["tranche_part_sociale"] == "t"){
        		
        		$SESSION_VARS["nbre_parts"] =$nbre_parts;
        		$SESSION_VARS["nbre_parts_lib"]=$hid_tranche_nbr_parts;
        		$SESSION_VARS["somme"] = $somme;
      
        	}
        	if ($AGC["tranche_part_sociale"] == "f"){
        		$SESSION_VARS["nbre_parts"] = $nbre_parts;
        		$SESSION_VARS["nbre_parts_lib"]= $nbre_parts_lib;
        		$SESSION_VARS["somme"] = $somme;
        	}
       
          // Enregistrement des valeurs postées
          if ($transfert_client == true) {
            $SESSION_VARS["transfert_client"] = true;
          } else {
            $SESSION_VARS["transfert_client"] = false;
            $SESSION_VARS['setPaye'] = true;
            $SESSION_VARS["ouvre_cpt_base"] = ($ouvre_cpt_base == '' ? 0 : 1);
          }
          $SESSION_VARS["paye"] = $paye; //recuperation du bouton 0-payera plutard,1-versement init,2-pay tout

          // Get chosen produit epargne
          $SESSION_VARS["DATACPT"]["id_prod_epg"] = $id_prod_epg;
          $SESSION_VARS["mnt_min"] = $depot_min_val;

          $AG_DATA = getAgenceDatas($global_id_agence);
   
          if ($ouvre_cpt_base == 0) {
            $SESSION_VARS["DATACPT"]["intitule_compte"] = _("Compte de base inactif");
          } else {
            $SESSION_VARS["DATACPT"]["intitule_compte"] = $intitule_compte;
          }

          $myForm = new HTML_GEN2(_("Encaissement"));
          if(isset($montant_droits_adhesion)) {
          	$SESSION_VARS["montant_frais_adhesion"] =recupMontant($montant_droits_adhesion);
          }

          $montant_part_sociale = $AG_DATA["val_nominale_part_sociale"];

          // montant mini cpte base
          if ($ouvre_cpt_base == 1) {
            $Montant_frais = $SESSION_VARS["montant_frais_adhesion"];
            $myForm->addField("droits_adh", _("Montant des droits d'adhésion"), TYPC_MNT);
            $myForm->setFieldProperties("droits_adh", FIELDP_IS_LABEL, true);
            $myForm->setFieldProperties("droits_adh", FIELDP_DEFAULT, $Montant_frais);

            $myForm->addField("mnt_min", _("Solde minimum du compte de base"), TYPC_MNT);
            $myForm->setFieldProperties("mnt_min", FIELDP_IS_LABEL, true);
            $myForm->setFieldProperties("mnt_min", FIELDP_DEFAULT, $SESSION_VARS["mnt_min"]);
            $Montant += $SESSION_VARS["mnt_min"];

            // affichage de l'intitulé du compte
            $myForm->addTable("ad_cpt", OPER_INCLUDE, array ("intitule_compte"));
            $myForm->setFieldProperties("intitule_compte", FIELDP_DEFAULT, $SESSION_VARS["DATACPT"]["intitule_compte"]);
            $myForm->setFieldProperties("intitule_compte", FIELDP_IS_LABEL, true);

            if ($SESSION_VARS["paye"] == 2) {//tout
            	/**
            	 * *************************************************
            	 * EVOLUTION SOUSCRIPTION & LIBERATION
            	 * champs a ramener
            	 * recuperation des valeur
            	 * *************************************************
            	 */
            	
            	$montant_ps = $montant_part_sociale * $SESSION_VARS["nbre_parts"];
              $Montant += $montant_ps;
              $myForm->addField("mnt_souscr", _("Valeur de la souscription de parts sociales"), TYPC_MNT);
              $myForm->setFieldProperties("mnt_souscr", FIELDP_IS_LABEL, true);
              $myForm->setFieldProperties("mnt_souscr", FIELDP_DEFAULT, $montant_ps); //faut calculer que pour laffichage
              //EVOL :361 @ 23032015
              $myForm->addField("mnt_lib", _("Montant de la libération de parts sociales"), TYPC_MNT);
              $myForm->setFieldProperties("mnt_lib", FIELDP_IS_LABEL, true);
              $myForm->setFieldProperties("mnt_lib", FIELDP_DEFAULT, recupMontant($somme));
            }
            // champs montant mini à verser, versement et confirmation
            $myForm->addField("apayer", _("Montant minimum à verser"), TYPC_MNT);
          } else
            $myForm->addField("apayer", _("Montant des droits d'adhésion"), TYPC_MNT);
          

          //Choix d'une agence-banque source du transfert client
          if ($SESSION_VARS["transfert_client"] == TRUE) {
            $myForm->addTableRefField("bqe", "Banque", "adsys_banques");
            $myForm->setFieldProperties("bqe", FIELDP_HAS_CHOICE_AUCUN, true);
            $myForm->setFieldProperties("bqe", FIELDP_IS_REQUIRED, true);
          }
          
      //ON vas ouvert un compte de base
          if ($ouvre_cpt_base == 1) {
            $AG_DATA = getAgenceDatas($global_id_agence);
            debug($AG_DATA);
            debug($AG_DATA["tranche_frais_adhesion"], _("tranche frais adhésion"));
            
            /**
             * *************************************************
             * EVOLUTION SOUSCRIPTION & LIBERATION
             * Option :Paye frais adhesion et PS 
             * Tranche parts sociale FALSE
             * *************************************************
             */
            if ($SESSION_VARS["paye"] == 2) {
            	if ($AG_DATA["tranche_part_sociale"] == "f"){
            		$montant_frais = $SESSION_VARS["montant_frais_adhesion"];
            		// montant min +PS +Frais adhesion
            		$Mont = $SESSION_VARS["mnt_min"] + recupMontant($somme) + $Montant_frais;
            		$myForm->setFieldProperties("apayer", FIELDP_IS_LABEL, true);
            		$myForm->setFieldProperties("apayer", FIELDP_DEFAULT, $Mont);
            		$myForm->addField("versement", _("Montant du versement"), TYPC_MNT);
            		$myForm->setFieldProperties("versement", FIELDP_IS_REQUIRED, true);
            		$myForm->addField("conf_versement", _("Confirmation du montant"), TYPC_MNT);
            		$myForm->setFieldProperties("conf_versement", FIELDP_IS_REQUIRED, true);
            		// Au cas où  il faudra saisir le billetage ticket325
            		global $global_billet_req;
            		if ($global_billet_req) {
            			$myForm->setFieldProperties("conf_versement", FIELDP_HAS_BILLET, true);
            			$myForm->setFieldProperties("conf_versement", FIELDP_SENS_BIL, SENS_BIL_OUT);
            		}
            		$myForm->addJS(JSP_BEGIN_CHECK, "js16", "if (recupMontant(document.ADForm.versement.value) != recupMontant(document.ADForm.conf_versement.value)) { msg += '- "._("Les montants entrés ne correspondent pas.")."\\n';ADFormValid = false;}");
            		$myForm->addJS(JSP_BEGIN_CHECK, "js17", "if (recupMontant(document.ADForm.versement.value) < recupMontant(document.ADForm.apayer.value)) { msg += '- "._("Le montant du versement doit être supérieur ou égal au montant minimum.")."\\n';ADFormValid = false;}");
            		
            	}
            	/**
            	 * *************************************************
            	 * EVOLUTION SOUSCRIPTION & LIBERATION
            	 * Option :Paye frais adhesion et PS
            	 * Tranche parts sociale TRUE
            	 * *************************************************
            	 */
            	
            	if ($AG_DATA["tranche_part_sociale"] == "t"){
            		$montant_frais = $SESSION_VARS["montant_frais_adhesion"];
            		// montant min +PS + Frais adhesion
            		$Mont = $SESSION_VARS["mnt_min"] + recupMontant($somme) + $Montant_frais;
            		$myForm->setFieldProperties("apayer", FIELDP_IS_LABEL, true);
            		$myForm->setFieldProperties("apayer", FIELDP_DEFAULT, $Mont);
            		$myForm->addField("versement", _("Montant du versement"), TYPC_MNT);
            		$myForm->setFieldProperties("versement", FIELDP_IS_REQUIRED, true);
            		$myForm->addField("conf_versement", _("Confirmation du montant"), TYPC_MNT);
            		$myForm->setFieldProperties("conf_versement", FIELDP_IS_REQUIRED, true);
            		// Au cas où  il faudra saisir le billetage ticket325
            		global $global_billet_req;
            		if ($global_billet_req) {
            			$myForm->setFieldProperties("conf_versement", FIELDP_HAS_BILLET, true);
            			$myForm->setFieldProperties("conf_versement", FIELDP_SENS_BIL, SENS_BIL_OUT);
            		}
            		$myForm->addJS(JSP_BEGIN_CHECK, "js16", "if (recupMontant(document.ADForm.versement.value) != recupMontant(document.ADForm.conf_versement.value)) { msg += '- "._("Les montants entrés ne correspondent pas.")."\\n';ADFormValid = false;}");
            		$myForm->addJS(JSP_BEGIN_CHECK, "js17", "if (recupMontant(document.ADForm.versement.value) < recupMontant(document.ADForm.apayer.value)) { msg += '- "._("Le montant du versement doit être supérieur ou égal au montant minimum.")."\\n';ADFormValid = false;}");
            		
            	}
            }
            /**
             * *************************************************
             * EVOLUTION SOUSCRIPTION & LIBERATION
             * Option :Versement Initial
             * *************************************************
             */

            if ($SESSION_VARS["paye"] == 1) { //versement initial
              	$Mont = recupMontant($SESSION_VARS["montant_frais_adhesion"]) + recupMontant($SESSION_VARS["mnt_min"]);
                $myForm->addField("versement", _("Montant du versement"), TYPC_MNT);
                $myForm->setFieldProperties("versement", FIELDP_IS_REQUIRED, true);
                $myForm->addField("conf_versement", _("Confirmation du montant"), TYPC_MNT);
                $myForm->setFieldProperties("conf_versement", FIELDP_IS_REQUIRED, true);
                $myForm->setFieldProperties("conf_versement", FIELDP_HAS_BILLET, true);
                // Au cas où  il faudra saisir le billetage ticket325
                global $global_billet_req;
                if ($global_billet_req) {
                	$myForm->setFieldProperties("conf_versement", FIELDP_HAS_BILLET, true);
                	$myForm->setFieldProperties("conf_versement", FIELDP_SENS_BIL, SENS_BIL_OUT);
                }
                $myForm->setFieldProperties("apayer", FIELDP_IS_LABEL, true);
                $myForm->setFieldProperties("apayer", FIELDP_DEFAULT, $Mont);
                $myForm->addJS(JSP_BEGIN_CHECK, "js4", "if (recupMontant(document.ADForm.versement.value) != recupMontant(document.ADForm.conf_versement.value)) { msg += '- "._("Les montants entrés ne correspondent pas.")."\\n';ADFormValid = false;}");
                $myForm->addJS(JSP_BEGIN_CHECK, "js5", "if (recupMontant(document.ADForm.versement.value) < recupMontant(document.ADForm.apayer.value)) { msg += '- "._("Le montant du versement doit être supérieur ou égal au montant minimum.")."\\n';ADFormValid = false;}");
            }//fin paye 1 versement init

          }

          $myForm->addField("communication", _("Communication"), TYPC_TXT);
          $myForm->setFieldProperties("communication", FIELDP_DEFAULT, $SESSION_VARS['communication']);
          $myForm->addField("remarque", _("Remarque"), TYPC_ARE);
          $myForm->setFieldProperties("remarque", FIELDP_DEFAULT, $SESSION_VARS['remarque']);

          $myForm->addFormButton(1, 1, "ok", _("Valider"), TYPB_SUBMIT);
          $myForm->setFormButtonProperties("ok", BUTP_PROCHAIN_ECRAN, 'Acl-6');
          $myForm->addFormButton(1, 2, "retour", _("Précédent"), TYPB_SUBMIT);
          $myForm->setFormButtonProperties("retour", BUTP_PROCHAIN_ECRAN, 'Acl-4');
          $myForm->setFormButtonProperties("retour", BUTP_CHECK_FORM, false);
          $myForm->buildHTML();
          echo $myForm->getHTML();

        }
/*}}}*/

/*{{{ Acl-6 : Traitement DB et confirmation */
        else
          if ($global_nom_ecran == "Acl-6") {
            global $global_id_client;
            global $global_monnaie;
            setMonnaieCourante($global_monnaie);
            // si on vient directement de Acl-4
            if ($global_nom_ecran_prec == "Acl-4") {
              $SESSION_VARS["DATACPT"]["intitule_compte"] = $intitule_compte;
              $SESSION_VARS["ouvre_cpt_base"] = $ouvre_cpt_base;
              $SESSION_VARS['paye'] = $paye;
              
              // Get chosen produit epargne
              $SESSION_VARS["DATACPT"]["id_prod_epg"] = $id_prod_epg;
              $SESSION_VARS["mnt_min"] = $depot_min_val;
            }

            if ($SESSION_VARS["ouvre_cpt_base"] == 1) {
              $versement = recupMontant($versement);
            }

            if ($SESSION_VARS["DATACLI"]["id_client"] == NULL) {
              $global_id_client = getNewClientID();
            } else {
              $global_id_client = $SESSION_VARS["DATACLI"]["id_client"];
            }
            $global_etat_client = 1; // A ce stade, le client est en cours de validation
            $global_langue_rapport = $SESSION_VARS["DATACLI"]['langue_correspondance'];

            $SESSION_VARS["DATACLI"]["id_client"] = $global_id_client;
            $SESSION_VARS["DATACLI"]["etat"] = $global_etat_client;
            $SESSION_VARS["DATACLI"]["nbre_parts"] = 0; // Nécessaire pour pouvoir additionner ensuite le nombre de parts souscrites
            $SESSION_VARS["DATACLI"]["nbre_parts_lib"] = 0;
            
            $id_agc = getNumAgence();
            $AGD = getAgenceDatas($id_agc);
            $type_num_cpte = $AGD['type_numerotation_compte'];
            //$SESSION_VARS["montant_frais_adhesion"] = getMontantDroitsAdhesion($SESSION_VARS["DATACLI"]["statut_juridique"]);
            //$DATACLI = getClientDatas($global_id_client);
            $AGC = getAgenceDatas($global_id_agence);
            $mnt_min_cpt_base = recupMontant($SESSION_VARS["mnt_min"]);
            if ($AGD["tranche_frais_adhesion"] == "t") {
              $SESSION_VARS["DATACLI"]["solde_frais_adhesion_restant"] = $SESSION_VARS["montant_frais_adhesion"];
            }

            if ($type_num_cpte == 1) {
              $global_id_client_formate = sprintf("%06d", $global_id_client);
            } else
              if ($type_num_cpte == 2) {
                $global_id_client_formate = sprintf("%05d", $global_id_client);
              } else
                if ($type_num_cpte == 3) {
                  $global_id_client_formate = sprintf("%07d", $global_id_client);
                }

            if ($SESSION_VARS['DATACLI']['statut_juridique'] == 1)
              $global_client = $SESSION_VARS['DATACLI']['pp_prenom'] . ' ' . $SESSION_VARS['DATACLI']['pp_nom'];
            else
              if ($SESSION_VARS['DATACLI']['statut_juridique'] == 2)
                $global_client = $SESSION_VARS['DATACLI']['pm_raison_sociale'];
              else // statut_juridique 3 ou 4
                $global_client = $SESSION_VARS['DATACLI']['gi_nom'];

            // Récupération de la banque dans le cas d'un transfert client
            if (isset ($bqe))
              $SESSION_VARS["banque"] = $bqe;

            $data_ext['communication'] = $communication;
            $data_ext['remarque'] = $remarque;
            $data_ext['sens'] = "in ";

            /**
             * *************************************************
             * CREATION CLIENT
             * *************************************************
             */

            //Sanitise données avant d'enregistrer la creation du client
            // personne physique
            if (array_key_exists('pp_nom', $SESSION_VARS["DATACLI"]) && $SESSION_VARS["DATACLI"]["pp_nom"] !== '' ) {
                $SESSION_VARS["DATACLI"]["pp_nom"] = trim(preg_replace('/\s+/',' ', $SESSION_VARS["DATACLI"]["pp_nom"]));
            }
            if (array_key_exists('pp_prenom', $SESSION_VARS["DATACLI"]) && $SESSION_VARS["DATACLI"]["pp_prenom"] !== '' ) {
                $SESSION_VARS["DATACLI"]["pp_prenom"] = trim(preg_replace('/\s+/',' ', $SESSION_VARS["DATACLI"]["pp_prenom"]));
            }

            // personne morale
            if (array_key_exists('pm_raison_sociale', $SESSION_VARS["DATACLI"]) && $SESSION_VARS["DATACLI"]["pm_raison_sociale"] !== '' ) {
                $SESSION_VARS["DATACLI"]["pm_raison_sociale"] = trim(preg_replace('/\s+/',' ', $SESSION_VARS["DATACLI"]["pm_raison_sociale"]));
            }

            // groupe informelle or groupe solidaire
            if (array_key_exists('gi_nom', $SESSION_VARS["DATACLI"]) && $SESSION_VARS["DATACLI"]["gi_nom"] !== '' ) {
                $SESSION_VARS["DATACLI"]["gi_nom"] = trim(preg_replace('/\s+/',' ', $SESSION_VARS["DATACLI"]["gi_nom"]));
            }

            $myErr = creationClient($SESSION_VARS["DATACLI"], $SESSION_VARS["DATACPT"], $SESSION_VARS["paye"], $SESSION_VARS["ouvre_cpt_base"], $versement, $SESSION_VARS['nbre_parts'],$SESSION_VARS['nbre_parts_lib'],$SESSION_VARS['somme'] , $global_id_guichet, $SESSION_VARS["transfert_client"], $data_ext, $SESSION_VARS["banque"], $SESSION_VARS["montant_frais_adhesion"], $SESSION_VARS["nbr_membres_encodes"]);
            debug($myErr,"my error");
            $DATACLI = getClientDatas($global_id_client);
            
            //Mise à jour du solde restant des frais d'adhésion
            if ($SESSION_VARS["paye"] == 1) {
            	$fraisVerse = recupMontant($versement - $mnt_min_cpt_base);
            	$mnt_droits_adhesion = recupMontant($versement - $mnt_min_cpt_base);
            	$soldeFraisAdh = getSoldeFraisAdhesion($global_id_client);
            	$soldeRecup = $soldeFraisAdh->param[0]['solde_frais_adhesion_restant'] - $fraisVerse;
            	if ($soldeRecup < 0) {
            		$soldeRecup = 0;
            	}
            	$err_update = updateSodeRestantFraisAdhesion($global_id_client, $soldeRecup);
            	if ($err_update->errCode != NO_ERR) {
            		$html_err = new HTML_erreur(_("Echec de la mise à jour du solde."));
            		$html_err->setMessage(_("Erreur")." :" . $error[$myErr->errCode] . "<br/>"._("Paramètre")." : " . $myErr->param);
            		$html_err->addButton("BUTTON_OK", 'Gen-3');
            		$html_err->buildHTML();
            		echo $html_err->HTML_code;
            	}
            
            }
            

            if ($myErr->errCode != NO_ERR) {
              $html_err = new HTML_erreur(_("Echec de la création du client."));
              $html_err->setMessage(_("Erreur")." : " . $error[$myErr->errCode] . "<br/>"._("Paramètre")." : " . $myErr->param);
              $html_err->addButton("BUTTON_OK", 'Gen-3');
              $html_err->buildHTML();
              echo $html_err->HTML_code;
            } else {
              $global_etat_client = getEtatClient($global_id_client);
                
              //get info adhesion
              if($SESSION_VARS["montant_frais_adhesion"]==NULL){
              	$montant_frais_adhesion = getMontantDroitsAdhesion($DATA_CLI["statut_juridique"]);
              }else{
              	$montant_frais_adhesion=$SESSION_VARS["montant_frais_adhesion"];
              }
              
              /**
               * **********************************************************************************************
               * Prise en compte SOUSCRIPTION & LIBERATION s'il y a eu lieu
               * Option :Paye frais adhesion et PS
               * Generation des infos pour la souscription et liberation
               * *********************************************************************************************
               */
              if ($SESSION_VARS["ouvre_cpt_base"] == 1) {
              	if ($SESSION_VARS["paye"] == 2) {
              		if ($AGD ["tranche_part_sociale"] == "t") {
              			$mnt_droits_adhesion = $montant_frais_adhesion ;
              				
              			$nbre_ps_sous =$SESSION_VARS["nbre_parts"] ;
              			$montant_souscription = $nbre_ps_sous * $AGD["val_nominale_part_sociale"];
              			$nbre_ps_lib =$SESSION_VARS['nbre_parts_lib'] ;
              			$montant_liberation_tranche = recupMontant($SESSION_VARS['somme']); //montant par tranche
              			$montant_part_soc_restant = $montant_souscription - $montant_liberation_tranche;	
              			
              			$versement_min= $mnt_min_cpt_base + $montant_frais_adhesion + $montant_liberation_tranche;
              				
              		}else{
              			$mnt_droits_adhesion = $montant_frais_adhesion ;
              
              			$nbre_ps_sous =$SESSION_VARS["nbre_parts"] ;
              			$montant_souscription = $nbre_ps_sous * $AGD["val_nominale_part_sociale"];
              			$nbre_ps_lib =$SESSION_VARS['nbre_parts_lib'] ; 
              			$montant_liberation = recupMontant($SESSION_VARS['somme']); //montant complete
              			$montant_part_soc_restant = $montant_souscription - $montant_liberation;
              			
              			$versement_min= $mnt_min_cpt_base + $montant_frais_adhesion + $montant_liberation;
              		}
              	}else  if ($SESSION_VARS["paye"] == 1) {
              		$mnt_droits_adhesion = $montant_frais_adhesion ;
              		$versement_min = $mnt_min_cpt_base + $montant_frais_adhesion ;
              	}
              
              }//fin ouvre compte de base =1
              
              
              /**
               * *******************************************************************************
               * Traitement pour Souscription & Liberation SI la creation du client a ete succes
               * ******************************************************************************
               */
              //$global_etat_client = 2;
              if ($SESSION_VARS["ouvre_cpt_base"] == 1) {
              if ($SESSION_VARS["paye"] == 2) {
              	/**
              	 * **************
              	 * Souscription
              	 * **************
              	 */
              	if ($AGD["tranche_part_sociale"] == "t") {

              		if ($SESSION_VARS["nbre_parts"] > 0) {
              			$err = souscriptionPartsSocialesInt ( $SESSION_VARS["DATACLI"]["id_client"],$SESSION_VARS["nbre_parts"] , $global_id_utilisateur );
              			if ($err->errCode == NO_ERR) {
              				$err_update = updateSodeRestantPartSoc ( $SESSION_VARS["DATACLI"]["id_client"], $montant_souscription );
              				if ($err_update->errCode != NO_ERR) {
              					$html_err = new HTML_erreur ( _ ( "Echec de la mise à jour du solde restant." ) );
              					$html_err->setMessage ( _ ( "Erreur" ) . " : " . $error [$err_update->errCode] . "<br/>" . _ ( "Paramètre" ) . " : " . $err_update->param );
              					$html_err->addButton ( "BUTTON_OK", 'Gen-3' );
              					$html_err->buildHTML ();
              					echo $html_err->HTML_code;
              				} else { // historique ps
              					$id_his = $err->param;
              					$err_h = historique_mouvementPs (  $SESSION_VARS["DATACLI"]["id_client"], $id_his, 20 );
              					if ($err_h->errCode != NO_ERR) {
              						return $err_h;
              					}
              				}
              			}
              		}
              	} else {// tranche PS = false
              		if ($SESSION_VARS["nbre_parts"] > 0) {
              		$err = souscriptionPartsSocialesInt ( $SESSION_VARS["DATACLI"]["id_client"],$SESSION_VARS["nbre_parts"], $global_id_utilisateur );
              		
              		if ($err->errCode == NO_ERR) {
              				$err_update = updateSodeRestantPartSoc ( $SESSION_VARS["DATACLI"]["id_client"], $montant_souscription );
              				if ($err_update->errCode != NO_ERR) {
              					$html_err = new HTML_erreur ( _ ( "Echec de la mise à jour du solde restant." ) );
              					$html_err->setMessage ( _ ( "Erreur" ) . " : " . $error [$err_update->errCode] . "<br/>" . _ ( "Paramètre" ) . " : " . $err_update->param );
              					$html_err->addButton ( "BUTTON_OK", 'Gen-3' );
              					$html_err->buildHTML ();
              					echo $html_err->HTML_code;
              				} else { // historique ps
              					$id_his = $err->param;
              					$err_h = historique_mouvementPs (  $SESSION_VARS["DATACLI"]["id_client"], $id_his, 20 );
              					if ($err_h->errCode != NO_ERR) {
              						return $err_h;
              					}
              				}
              			}
              	}
              		
              	}
              	/**
              	 * **************
              	 * Libération
              	 * **************
              	 */
              	if ($AGD ["tranche_part_sociale"] == "t") {
              		if( recupMontant($SESSION_VARS['somme'])> 0){ 
              		$err = liberationPartsSocialesInt (  $SESSION_VARS["DATACLI"]["id_client"], $SESSION_VARS["nbre_parts_lib"], $global_id_utilisateur, recupMontant($SESSION_VARS['somme']) );
              		
              		// MAJ solde restant
              		if ($err->errCode == NO_ERR) {
              			$err_update = updateSodeRestantPartSoc ( $SESSION_VARS["DATACLI"]["id_client"], $montant_part_soc_restant );
              			if ($err_update->errCode != NO_ERR) {
              				$html_err = new HTML_erreur ( _ ( "Echec de la mise à jour du solde restant." ) );
              				$html_err->setMessage ( _ ( "Erreur" ) . " : " . $error [$err_update->errCode] . "<br/>" . _ ( "Paramètre" ) . " : " . $err_update->param );
              				$html_err->addButton ( "BUTTON_OK", 'Gen-3' );
              				$html_err->buildHTML ();
              				echo $html_err->HTML_code;
              			} else { // historique ps
              				$id_his = $err->param;
              				$err_h = historique_mouvementPs (  $SESSION_VARS["DATACLI"]["id_client"], $id_his, 28 );
              				if ($err_h->errCode != NO_ERR) {
              					return $err_h;
              				}
              			}
              		}
              	}
              	} else {
              
              		if ($SESSION_VARS['nbre_parts_lib'] > 0) {
              			$err = liberationPartsSocialesInt (  $SESSION_VARS["DATACLI"]["id_client"], $SESSION_VARS['nbre_parts_lib'], $global_id_utilisateur );
              
              			// MAJ solde restant
              			if ($err->errCode == NO_ERR) {
              				$err_update = updateSodeRestantPartSoc ($SESSION_VARS["DATACLI"]["id_client"], $montant_part_soc_restant );
              				if ($err_update->errCode != NO_ERR) {
              					$html_err = new HTML_erreur ( _ ( "Echec de la mise à jour du solde restant." ) );
              					$html_err->setMessage ( _ ( "Erreur" ) . " : " . $error [$err_update->errCode] . "<br/>" . _ ( "Paramètre" ) . " : " . $err_update->param );
              					$html_err->addButton ( "BUTTON_OK", 'Gen-3' );
              					$html_err->buildHTML ();
              					echo $html_err->HTML_code;
              				} else { // historique ps
              					$id_his = $err->param;
              					$err_h = historique_mouvementPs (  $SESSION_VARS["DATACLI"]["id_client"], $id_his, 28 );
              					if ($err_h->errCode != NO_ERR) {
              						return $err_h;
              					}
              				}
              			}
              		}
              		 
              	}
              
              }
              }//ouvre cpt base
              /**
               * *******************************************************************************
               * FIN Traitement pour Souscription & Liberation 
               * ******************************************************************************
               */
              // Génération du reçu d'adhésion si le client a payé quelque chose
              /* if ($SESSION_VARS["paye"] > 0) {	
                print_recu_adhesion($global_id_client, $versement, $myErr->param, $SESSION_VARS["transfert_client"], $SESSION_VARS["montant_frais_adhesion"]);
              }
               */
              //recu versement initial
               if ($SESSION_VARS["paye"] > 0) {
               	if ($SESSION_VARS["paye"] == 2){//paye adhesion et (PS)
               print_recu_adhesion($global_id_client, $versement, $myErr->param, $SESSION_VARS["transfert_client"], $SESSION_VARS["montant_frais_adhesion"]);
               
               }
               else{// tout autre cas paye >0
               	print_recu_adhesion($global_id_client, $versement, $myErr->param, $SESSION_VARS["transfert_client"], $SESSION_VARS["montant_frais_adhesion"]);
               	
               }
            }
                
             //$SESSION_VARS["DATACLI"]["statut_juridique"]
              // Affichage de la photo et la signature du client dans le frame du haut
              $IMGS = getImagesClient($global_id_client);
              $global_photo_client = $IMGS["photo"];
              $global_signature_client = $IMGS["signature"];

              // Message de confirmation

              $myForm = new HTML_message(_("Confirmation de la création du client"));

              $msg = sprintf(_("Le client '%s' a été créé avec succès"), getClientName($global_id_client));
              $msg .= "<br/><br/>" . _("Il porte le numéro ") . "<font size=+2 color=red>" . $global_id_client_formate . "</font>";
              debug($SESSION_VARS["ouvre_cpt_base"]);
              if ($SESSION_VARS["ouvre_cpt_base"] == 1) {
                $ACC = getAccountDatas(getBaseAccountID($global_id_client));
                $msg .= "<br/><br/>" . sprintf(_("Le compte numéro '%s' a été ouvert et son solde est de '%s'"), $ACC["num_complet_cpte"], afficheMontant($ACC["solde"], true));
              }

              if (check_access(90))
              {
                $msg .= "<br/><br/>" . _("Vous pouvez à présent définir les mandataires et les relations pour ce nouveau client");
              }

              $msg .= "<br/><br/>" . _("N° de transaction") . " : <b><code>" . sprintf("%09d", $myErr->param) . "</code></b>";

              $myForm->setMessage($msg);

              // Set session nb_clients_actifs
              $_SESSION['nb_clients_actifs'] = updateNbClientActif();

             if (check_access(11)) {
                $myForm->addCustomButton("rel", _("Créer les relations"), 'Rel-1');
             }
              if (check_access(90)) {
                $myForm->addCustomButton("man", _("Créer les mandataires"), 'Man-1');
              }

              $myForm->addCustomButton("fich", _("Imprimer la fiche client"), 'Ccl-4');

              $myForm->addCustomButton("menu", _("Aller au menu clientèle"), 'Gen-9');
              $myForm->buildHTML();
              echo $myForm->HTML_code;
            }
          }
/*}}}*/

          else
            signalErreur(__FILE__, __LINE__, __FUNCTION__, sprintf(_("L'écran %s n'a pas pu être trouvé"), $global_nom_ecran));
?>
