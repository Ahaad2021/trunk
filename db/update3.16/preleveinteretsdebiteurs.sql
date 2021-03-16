-- Function: preleveinteretsdebiteurs(date, text)

-- DROP FUNCTION preleveinteretsdebiteurs(date, text);

CREATE OR REPLACE FUNCTION preleveinteretsdebiteurs(date, text)
  RETURNS SETOF cpte_deb AS
$BODY$

DECLARE
  date_courante ALIAS FOR $1;		-- La date pour laquelle on travaille
  cpte_cpta_produit ALIAS FOR $2;       -- Compte de versement des intérets

  jour INTEGER; 			-- Jour actuel
  mois INTEGER; 			-- Mois actuel
  annee INTEGER; 			-- Année actuelle

  id_exo_courant INTEGER; 		-- ID de l'exercice courant

  date_dern_jour_mois_traite DATE;      -- Date du dernier jour du mois traité
  dern_jour_mois_traite INTEGER;        -- Dernier jour du mois traité

  liste_comptes refcursor;               -- Liste des comptes à traiter

  ligne RECORD;                         -- Accueille une ligne du curseur

  jour_traite INTEGER;                   -- Jour traité
  mois_traite INTEGER;                   -- Mois traité
  annee_traite INTEGER;                  -- Année traitée
  date_traite DATE;                      -- Date traitée

  prec SMALLINT;			 -- Précision de la devise du comtpe traité
  code_dev_ref CHAR(3);			 -- Code de la devise de référence

  cpt_pos_ch TEXT; 			 -- Compte de position de change de la devise du compte traité
  cpt_cv_pos_ch TEXT;		         -- Compte de C/V de la Pos de Ch de la devise du compte traité

  cv_total_interet NUMERIC(30,6);	 -- C/V du total des intérets dus
  cv_interet_cpte NUMERIC(30,6);         -- C/V des intérêts du compte traté

  num_cpte_debit TEXT;		       -- Compte comptable à débiter
  dev_cpte_cpta_prod_ep CHAR(3);       -- Devise du compte comptable à débiter (compte associé au produit d'épargne)

  compte_deb cpte_deb;                  -- array contenant l'id, le solde et les intérêts du compte traité
  infos_cpte RECORD;                    -- array contenant quelques informations du compte traité

BEGIN

  -- Vérifier que le compte de crédit passé en paramètre est correct avant de commencer tout traitement
  IF cpte_cpta_produit is NULL OR cpte_cpta_produit = '' THEN
    RAISE EXCEPTION 'Le compte de produit au crédit n est pas paramétré';
  END IF;

  -- REMARQUES DIVERSES
    -- Attention à l'arrondi des montants
    -- Prendre la valeur absolue des intérêts à payer
    -- Prendre le taux d'intérêt journalier = taux mensuel / 30 jours

  SELECT INTO jour extract(day from date_courante); -- Jour actuel du mois;
  SELECT INTO mois extract(month from date_courante); -- Mois actuel
  SELECT INTO annee extract(year from date_courante); -- Année actuelle
  SELECT INTO id_exo_courant max(id_exo_compta) FROM ad_exercices_compta WHERE id_ag=NumAgc() AND etat_exo = 1;
  SELECT INTO code_dev_ref code_devise_reference FROM ad_agc;

  IF (SELECT estdernjourmois(date_courante)) THEN
    jour := 0;
  END IF;

  RAISE NOTICE 'jour = %, mois = %, annee = %, id_exo_courant = %, code_dev_ref = %',jour,mois,annee,id_exo_courant,code_dev_ref;

  -- liste des comptes à prélever
  CREATE TEMP TABLE cptes_a_prelever AS
    SELECT cpt.id_cpte, cpt.solde as current_solde, cpt.devise, dev.precision, prod.tx_interet_debiteur,
           cpt.etat_cpte, prod.cpte_cpta_prod_ep
    FROM ad_cpt cpt, adsys_produit_epargne prod, devise dev
    WHERE cpt.id_ag=prod.id_ag AND prod.id_ag=dev.id_ag AND dev.id_ag=NumAgc() AND cpt.id_prod = prod.id AND prod.devise = dev.code_devise AND cpt.etat_cpte = 1
    AND prod.nbre_jours_report_debit = jour AND prod.tx_interet_debiteur > 0 order by id_cpte;

  -- est-ce qu'il y a quelque chose ?
  IF ((SELECT count(*) FROM cptes_a_prelever) > 0) THEN
    RAISE NOTICE 'Il y a des comptes sur lesquels on peut éventuellement prélever';

    -- Tables de travail pour les variations de solde
    CREATE TEMP TABLE var_soldes_debit
      ( cpte_cli_debit int4,
        date_traite_debit date,
        debit numeric(30,6)
        );
    CREATE TEMP TABLE var_soldes_credit
      ( cpte_cli_credit int4,
        date_traite_credit date,
        credit numeric(30,6)
        );
    CREATE TEMP TABLE var_soldes_total
      ( cpte_cli int4,
        date_traite_total date,
        debit numeric(30,6),
        credit numeric(30,6),
        var_solde numeric(30,6)
        );
    CREATE TEMP TABLE som_mvt_debit
      ( cpte_int_cli int4,
        somme_mvt numeric(30,6)
        );
    CREATE TEMP TABLE som_mvt_credit
      ( cpte_int_cli int4,
        somme_mvt numeric(30,6)
        );

    -- Si on est au dernier jour du mois à traiter
    -- il faut ajouter 1 jour pour démarrer le lendemain de la fin du mois : nécessaire pour avoir le solde en fin de journée
    IF jour = 0 THEN
      SELECT INTO date_dern_jour_mois_traite date(date_courante);
    ELSE 	-- Premier jour du mois suivant
      SELECT INTO date_dern_jour_mois_traite date('01/' || mois || '/' || annee) - interval '1 day';
    END IF;

    SELECT INTO dern_jour_mois_traite extract(day FROM date_dern_jour_mois_traite);
    SELECT INTO mois_traite extract(month FROM date_dern_jour_mois_traite);
    SELECT INTO annee_traite extract(year FROM date_dern_jour_mois_traite);

    RAISE NOTICE 'date_dern_jour_mois_traite = %, dern_jour_traite = %, mois_traite = %, annee_traite = %',date_dern_jour_mois_traite, dern_jour_mois_traite, mois_traite, annee_traite;

    -- On ne considère que les mouvements dont la date valeur est dans la période à traiter
    CREATE TEMP TABLE mvt_a_considerer
      ( id_mouvement integer,
        cpte_interne_cli integer,
        sens text,
        montant numeric(30,6),
        date_valeur date
        );
    INSERT INTO mvt_a_considerer
      SELECT id_mouvement, cpte_interne_cli, sens, montant, date_valeur FROM ad_mouvement
      WHERE id_ag=NumAgc() AND date_valeur > date_dern_jour_mois_traite - interval '1 month' AND cpte_interne_cli > 0;

    -- Pour chaque jour à traiter :
    -- 	- on va récupérer pour chaque jour du mois, la somme des débits et des crédits.
    -- 	- ensuite, on va calculer le solde en date de valeur
    --	- on va en déduire les intérêts quotidiens à payer
    -- 	- ensuite, on va sommer les intérêts pour ne produire qu'une écriture de débit par client

    -- on va jusqu'à la veille du premier jour pour déduire les mouvements au débit et au crédit
    FOR jour_traite IN REVERSE dern_jour_mois_traite .. 1 LOOP

      -- Détermine la date pour laquelle on traite
      SELECT INTO date_traite date(jour_traite || '/' || mois_traite || '/' || annee_traite);

      RAISE NOTICE 'date__traite = %', date_traite;

      -- La somme des mouvements au débit jusqu'à la date traitée
      INSERT INTO som_mvt_debit
      SELECT cpte_interne_cli as cpte_int_cli, sum(montant) as somme_mvt
        FROM mvt_a_considerer
        WHERE (date(date_valeur) > date(date_traite)) AND sens ='d' GROUP BY cpte_interne_cli;

      INSERT INTO var_soldes_debit
        SELECT a.id_cpte as cpte_cli_debit, date(date_traite), b.somme_mvt as debit
        FROM cptes_a_prelever a LEFT JOIN som_mvt_debit b ON a.id_cpte = b.cpte_int_cli ORDER BY a.id_cpte;

      -- La somme des mouvements au crédit jusqu'à la date traitée
      INSERT INTO som_mvt_credit
      SELECT cpte_interne_cli as cpte_int_cli, sum(montant) as somme_mvt
        FROM mvt_a_considerer
        WHERE (date(date_valeur) > date(date_traite)) AND sens ='c' GROUP BY cpte_interne_cli;

      INSERT INTO var_soldes_credit
        SELECT a.id_cpte as cpte_cli_credit,date(date_traite), b.somme_mvt as credit
        FROM cptes_a_prelever a LEFT JOIN som_mvt_credit b ON a.id_cpte = b.cpte_int_cli ORDER BY a.id_cpte;

      -- On vide les tables temporaires
      DELETE FROM som_mvt_debit;
      DELETE FROM som_mvt_credit;
    END LOOP;
    DROP TABLE mvt_a_considerer;
    DROP TABLE som_mvt_debit;
    DROP TABLE som_mvt_credit;

    -- calculer la variation du solde
    INSERT INTO var_soldes_total
      SELECT vsd.cpte_cli_debit, vsd.date_traite_debit, vsd.debit, vsc.credit
      FROM var_soldes_debit vsd FULL OUTER JOIN var_soldes_credit vsc
      ON vsd.cpte_cli_debit = vsc.cpte_cli_credit AND vsd.date_traite_debit = vsc.date_traite_credit;
    UPDATE var_soldes_total SET debit=0 WHERE debit is null;
    UPDATE var_soldes_total SET credit=0 WHERE credit is null;
    UPDATE var_soldes_total SET var_solde = abs(credit) - abs(debit);

    -- compiler les comptes à prélever et leur solde en date valeur, jour par jour
    CREATE TEMP TABLE cpte_a_prelever_soldes
      ( id_cpte int4,
        date_valeur date,
        tx_interet_debiteur double precision,
        solde_valeur numeric(30,6),
        int_deb numeric(30,6)
        );
    INSERT INTO cpte_a_prelever_soldes
      SELECT a.cpte_cli, a.date_traite_total, b.tx_interet_debiteur, (b.current_solde - a.var_solde)
      FROM var_soldes_total a, cptes_a_prelever b
      WHERE a.cpte_cli = b.id_cpte;

    -- supprimer les comptes qui n'ont pas un solde débiteur à une date traitée
    DELETE FROM cpte_a_prelever_soldes where solde_valeur >= 0;

    -- calculer les intérêts débiteurs pour chaque jour
    UPDATE cpte_a_prelever_soldes SET int_deb = abs((solde_valeur * tx_interet_debiteur / 30)::numeric(30,6));

    -- faire la somme des intérets à payer par compte pour passer une seule écriture
    CREATE TEMP TABLE liste_debits
      ( id_cpte int4,
        devise char(3),
        cpte_cpta_prod_ep text,
        interets_deb_a_payer numeric(30,6)
        );
    INSERT INTO liste_debits
      SELECT a.id_cpte, b.devise, b.cpte_cpta_prod_ep, round(sum(a.int_deb)::numeric(30,6), b.precision)
      FROM cpte_a_prelever_soldes a, cptes_a_prelever b
      WHERE a.id_cpte = b.id_cpte
      GROUP BY a.id_cpte, b.cpte_cpta_prod_ep, b.precision, b.devise
      ORDER BY id_cpte;

    -- supprimer les débits qui ont été arrondis à 0
    DELETE FROM liste_debits where interets_deb_a_payer = 0;

    OPEN liste_comptes FOR SELECT * FROM liste_debits;

    FETCH liste_comptes INTO ligne;

    IF FOUND THEN
      INSERT INTO ad_his (type_fonction,id_ag, infos,  date)
      VALUES (212,NumAgc(),'Prelevement des intérets débiteurs', now());

      WHILE FOUND LOOP
        -- Même si solde insuffisant, on effectue le prélèvement au lieu de mettre les frais en attente

        -- Récupération de quelques infos sur le compte traité
        SELECT INTO infos_cpte num_complet_cpte, devise, id_titulaire, solde from ad_cpt WHERE (id_ag=NumAgc() AND id_cpte = ligne.id_cpte);

        -- Récupération de la devise du compte associé au produit
        SELECT INTO dev_cpte_cpta_prod_ep devise FROM ad_cpt_comptable WHERE id_ag=NumAgc() AND num_cpte_comptable = ligne.cpte_cpta_prod_ep;

        -- Construction du numéro de compte à débiter
        IF dev_cpte_cpta_prod_ep IS NULL THEN
          num_cpte_debit := ligne.cpte_cpta_prod_ep || '.' || ligne.devise;
        ELSE
          num_cpte_debit := ligne.cpte_cpta_prod_ep;
        END IF;

        -- PASSAGE DES ECRITURES COMPTABLES
        INSERT INTO ad_ecriture (id_his,id_ag, date_comptable, libel_ecriture, id_jou,id_exo, ref_ecriture,info_ecriture) VALUES
          ((SELECT currval('ad_his_id_his_seq')),NumAgc(), date(now()), makeTraductionLangSyst('Prélèvement intérets débiteurs'), 1, id_exo_courant,makeNumEcriture(1, id_exo_courant),ligne.id_cpte);

        -- DEBIT DU COMPTE D'EPARGNE
        INSERT INTO ad_mouvement (id_ecriture,id_ag, compte, cpte_interne_cli, sens, montant, date_valeur, devise) VALUES
          ((SELECT currval('ad_ecriture_seq')),NumAgc(), num_cpte_debit, ligne.id_cpte, 'd', ligne.interets_deb_a_payer, date(now()), ligne.devise);

        UPDATE ad_cpt SET solde = solde - ligne.interets_deb_a_payer WHERE (id_cpte = ligne.id_cpte and id_ag=NumAgc());
        UPDATE ad_cpt_comptable set solde = solde - ligne.interets_deb_a_payer WHERE num_cpte_comptable = num_cpte_debit and id_ag=NumAgc() ;

        IF ligne.devise = code_dev_ref THEN
          -- Pas de change à effectuer
          cv_interet_cpte = ligne.interets_deb_a_payer;
        ELSE
          -- Il faut mouvementer la position de change

          -- Montant des intérêts dans la devise de référence
          SELECT INTO cv_interet_cpte CalculeCV(ligne.interets_deb_a_payer, ligne.devise, code_dev_ref);

          -- CREDIT DE LA POSITION DE CHANGE
          SELECT INTO cpt_pos_ch cpte_position_change || '.' || ligne.devise FROM ad_agc WHERE id_ag=NumAgc();
          INSERT INTO ad_mouvement (id_ecriture,id_ag, compte, cpte_interne_cli, sens, montant, date_valeur, devise) VALUES
    ( (SELECT currval('ad_ecriture_seq')),NumAgc(),cpt_pos_ch,NULL,'c',ligne.interets_deb_a_payer, date(now()), ligne.devise);
          UPDATE ad_cpt_comptable set solde = solde + ligne.interets_deb_a_payer WHERE id_ag=NumAgc() AND num_cpte_comptable = cpt_pos_ch;

          -- DEBIT COMPTE DE CONTRE VALEUR
          SELECT INTO cpt_cv_pos_ch cpte_contreval_position_change || '.' || ligne.devise FROM ad_agc WHERE id_ag=NumAgc();
          INSERT INTO ad_mouvement (id_ecriture,id_ag, compte, cpte_interne_cli, sens, montant, date_valeur, devise) VALUES
    ((SELECT currval('ad_ecriture_seq')),NumAgc(), cpt_cv_pos_ch, NULL, 'd', cv_interet_cpte, date(now()), code_dev_ref);
          UPDATE ad_cpt_comptable set solde = solde - cv_interet_cpte WHERE id_ag=NumAgc() AND num_cpte_comptable = cpt_cv_pos_ch;

        END IF;

        -- Crédit du compte de versement des intérêts ( dans la devise de référence )
        INSERT INTO ad_mouvement (id_ecriture,id_ag, compte, cpte_interne_cli, sens, montant, date_valeur, devise) VALUES
          ((SELECT currval('ad_ecriture_seq')),NumAgc(), cpte_cpta_produit, NULL, 'c', cv_interet_cpte,date(now()), code_dev_ref);
        UPDATE ad_cpt_comptable set solde = solde + cv_interet_cpte WHERE id_ag=NumAgc() AND num_cpte_comptable = cpte_cpta_produit;

        -- Construction des infos à renvoyer
        SELECT INTO compte_deb infos_cpte.num_complet_cpte, infos_cpte.devise, infos_cpte.id_titulaire, infos_cpte.solde, ligne.interets_deb_a_payer;
        RETURN NEXT compte_deb;

        FETCH liste_comptes INTO ligne;

      END LOOP;
    END IF;

  ELSE
    RAISE NOTICE 'Aucun compte à traiter';
  END IF;

  RETURN;
END;
 $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION preleveinteretsdebiteurs(date, text)
  OWNER TO postgres;
