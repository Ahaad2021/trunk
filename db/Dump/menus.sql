--Description des tables menus et ecrans pour la génération de l'affichage du cadre gauche

-- *** TABLE menus ***

--ID du menu, libellé du menu, nom du menu père, niveau hierarchique, position parmis ses frères, menu (ou terminal)?, fonction associée (doit être renseigné pour tous les champs de type menu), cliquable (dans le frame de gauche)

INSERT INTO menus VALUES ('Gen-3', maketraductionlangsyst('Menu principal'), NULL, 1, 1, true, 0, true);
INSERT INTO menus VALUES ('Gen-5', maketraductionlangsyst('Selection client'), 'Gen-3', 2, 1, true, 3, false);
INSERT INTO menus VALUES ('Acl', maketraductionlangsyst('Création client'), 'Gen-3', 2, 2, true, 30, true);
INSERT INTO menus VALUES ('Gen-6', maketraductionlangsyst('Guichet'), 'Gen-3', 2, 3, true, 151, true);
INSERT INTO menus VALUES ('Gen-7', maketraductionlangsyst('Système'), 'Gen-3', 2, 4, true, 201, true);
INSERT INTO menus VALUES ('Gen-12', maketraductionlangsyst('Paramétrage'), 'Gen-3', 2, 5, true, 251, true);
INSERT INTO menus VALUES ('Gen-13', maketraductionlangsyst('Rapports'), 'Gen-3', 2, 6, true, 301, true);
INSERT INTO menus VALUES ('Gen-14', maketraductionlangsyst('Comptabilité'), 'Gen-3', 2, 7, true, 401, true);
INSERT INTO menus VALUES ('Out', maketraductionlangsyst('Quitter'), 'Gen-3', 2, 7, true, 0, true);
INSERT INTO menus VALUES ('Gen-8', maketraductionlangsyst('Menu clientèle'), 'Gen-5', 3, 1, true, 3, true);
INSERT INTO menus VALUES ('Agu', maketraductionlangsyst('Approvisionnement'), 'Gen-6', 3, 1, true, 155, true);
INSERT INTO menus VALUES ('Dgu', maketraductionlangsyst('Délestage'), 'Gen-6', 3, 2, true, 156, true);
INSERT INTO menus VALUES ('Jgu', maketraductionlangsyst('Ajustement encaisse'), 'Gen-6', 3, 3, true, 170, true);
INSERT INTO menus VALUES ('Pdc', maketraductionlangsyst('Opérations div de caisse/compte'), 'Gen-6', 3, 4, true, 189, true);
INSERT INTO menus VALUES ('Sgu', maketraductionlangsyst('Dépôt par lot'), 'Gen-6', 3, 5, true, 158, true);
INSERT INTO menus VALUES ('Dlf', maketraductionlangsyst('Dépôt par lot via fichier'), 'Gen-6', 3, 6, true, 159, true);
INSERT INTO menus VALUES ('Att', maketraductionlangsyst('Traitement des attentes de crédit'), 'Gen-6', 3, 7, true, 188, true);
INSERT INTO menus VALUES ('Cca', maketraductionlangsyst('Change cash'), 'Gen-6', 3, 8, true, 186, true);
INSERT INTO menus VALUES ('Swi', maketraductionlangsyst('Paiement Net Bank'), 'Gen-6', 3, 9, true, 187, true);
INSERT INTO menus VALUES ('Psf', maketraductionlangsyst('Souscription parts sociales par lot '), 'Gen-6', 3, 10, true, 190, true);
INSERT INTO menus VALUES ('Rfv', maketraductionlangsyst('Recharge Ferlo par Versement'), 'Gen-6', 3, 10, true, 160, true);
INSERT INTO menus VALUES ('Vgu', maketraductionlangsyst('Visualisation transactions'), 'Gen-6', 3, 11, true, 180, true);
INSERT INTO menus VALUES ('Vtg', maketraductionlangsyst('Visualisation toutes transactions'), 'Gen-6', 3, 12, true, 181, true);
INSERT INTO menus VALUES ('Ich', maketraductionlangsyst('Chéquiers imprimés'), 'Gen-6', 3, 13, true, 191, true);
INSERT INTO menus VALUES ('Oag', maketraductionlangsyst('Ouverture'), 'Gen-7', 3, 1, true, 205, true);
INSERT INTO menus VALUES ('Fag', maketraductionlangsyst('Fermeture'), 'Gen-7', 3, 2, true, 206, true);
INSERT INTO menus VALUES ('Map', maketraductionlangsyst('Modification autre mot de passe'), 'Gen-7', 3, 3, true, 215, true);
INSERT INTO menus VALUES ('Fjr', maketraductionlangsyst('Traitement fin journée'), 'Gen-7', 3, 4, true, 212, true);
INSERT INTO menus VALUES ('Dat', maketraductionlangsyst('Sauvegarde données'), 'Gen-7', 3, 5, true, 0, true);
INSERT INTO menus VALUES ('Flo', maketraductionlangsyst('Déconnexion autre code utilisateur'), 'Gen-7', 3, 6, true, 230, true);
INSERT INTO menus VALUES ('Acp', maketraductionlangsyst('Ajustement solde compte'), 'Gen-7', 3, 7, true, 235, true);
INSERT INTO menus VALUES ('Gli', maketraductionlangsyst('Gestion de la licence'), 'Gen-7', 3, 8, true, 240, true);
INSERT INTO menus VALUES ('Cdb', maketraductionlangsyst('Consolidation de données'), 'Gen-7', 3, 9, true, 211, true);
INSERT INTO menus VALUES ('Out-1', maketraductionlangsyst('Saisie encaisse'), 'Out', 3, 1, false, 0, false);
INSERT INTO menus VALUES ('Out-2', maketraductionlangsyst('Confirmation déconnexion'), 'Out', 3, 2, false, 0, false);
INSERT INTO menus VALUES ('Gpf', maketraductionlangsyst('Gestion des profils'), 'Gen-12', 3, 1, true, 255, true);
INSERT INTO menus VALUES ('Mdp', maketraductionlangsyst('Modification mot de passe'), 'Gen-12', 3, 2, true, 260, true);
INSERT INTO menus VALUES ('Gus', maketraductionlangsyst('Gestion des utilisateurs'), 'Gen-12', 3, 3, true, 265, true);
INSERT INTO menus VALUES ('Glo', maketraductionlangsyst('Gestion des codes utilisateurs'), 'Gen-12', 3, 4, true, 287, true);
INSERT INTO menus VALUES ('Gta', maketraductionlangsyst('Gestion des tables de paramétrage'), 'Gen-12', 3, 5, true, 292, true);
INSERT INTO menus VALUES ('Vjf', maketraductionlangsyst('Visualisation jours ouvrables'), 'Gen-12', 3, 6, true, 296, true);
INSERT INTO menus VALUES ('Cra', maketraductionlangsyst('Rapports client'), 'Gen-13', 3, 1, true, 310, true);
INSERT INTO menus VALUES ('Era', maketraductionlangsyst('Rapports épargne'), 'Gen-13', 3, 2, true, 330, true);
INSERT INTO menus VALUES ('Kra', maketraductionlangsyst('Rapports crédit'), 'Gen-13', 3, 3, true, 350, true);
INSERT INTO menus VALUES ('Ara', maketraductionlangsyst('Rapports agence'), 'Gen-13', 3, 4, true, 370, true);
INSERT INTO menus VALUES ('Sra', maketraductionlangsyst('Simulation échéancier'), 'Gen-13', 3, 6, true, 390, true);
INSERT INTO menus VALUES ('Dra-1', maketraductionlangsyst('Visualisation dernier rapport'), 'Gen-13', 3, 7, false, 399, true);
INSERT INTO menus VALUES ('Ppc', maketraductionlangsyst('Gestion plan comptable'), 'Gen-14', 3, 1, true, 410, true);
INSERT INTO menus VALUES ('Gop', maketraductionlangsyst('Gestion opérations'), 'Gen-14', 3, 2, true, 420, true);
INSERT INTO menus VALUES ('Odc', maketraductionlangsyst('Gestion opérations div caisse/compte'), 'Gen-14', 3, 3, true, 472, true);
INSERT INTO menus VALUES ('Osa', maketraductionlangsyst('Passage opérations siège/agence'), 'Gen-14', 3, 4, true, 473, true);
INSERT INTO menus VALUES ('Ano', maketraductionlangsyst('Annulation mouvements réciproques'), 'Gen-14', 3, 5, true, 474, true);
INSERT INTO menus VALUES ('Ecr', maketraductionlangsyst('Passage d''écritures libres'), 'Gen-14', 3, 6, true, 470, true);
INSERT INTO menus VALUES ('Gex', maketraductionlangsyst('Gestion des exercices'), 'Gen-14', 3, 7, true, 440, true);
INSERT INTO menus VALUES ('Jou', maketraductionlangsyst('Gestion des journaux'), 'Gen-14', 3, 8, true, 450, true);
INSERT INTO menus VALUES ('Tra', maketraductionlangsyst('Rapports comptabilité'), 'Gen-14', 3, 9, true, 430, true);
INSERT INTO menus VALUES ('Rad', maketraductionlangsyst('Radiation crédit'), 'Gen-14', 3, 10, true, 475, true);
INSERT INTO menus VALUES ('Tva', maketraductionlangsyst('Déclarations de tva'), 'Gen-14', 3, 11, true, 476, true);
INSERT INTO menus VALUES ('Trs-1', maketraductionlangsyst('Traitement des transactions Ferlo'), 'Gen-14', 3, 11, true, 431, true);
INSERT INTO menus VALUES ('Pcs', maketraductionlangsyst('Provision crédits '), 'Gen-14', 3, 12, true, 432, true);
INSERT INTO menus VALUES ('Gel', maketraductionlangsyst('Gestion des écritures libres'), 'Gen-14', 3, 14, true, 478, true);
INSERT INTO menus VALUES ('Acl-1', maketraductionlangsyst('Saisie statut juridique'), 'Acl', 3, 1, false, NULL, false);
INSERT INTO menus VALUES ('Acl-2', maketraductionlangsyst('Saisie détails'), 'Acl', 3, 2, false, NULL, false);
INSERT INTO menus VALUES ('Acl-3', maketraductionlangsyst('Confirmation'), 'Acl', 3, 3, false, NULL, false);
INSERT INTO menus VALUES ('Acl-4', maketraductionlangsyst('Insertion'), 'Acl', 3, 4, false, NULL, false);
INSERT INTO menus VALUES ('Acl-5', maketraductionlangsyst('Perception Versement Initial'), 'Acl', 3, 5, false, NULL, false);
INSERT INTO menus VALUES ('Acl-6', maketraductionlangsyst('Confirmation creation compte'), 'Acl', 3, 6, false, NULL, false);
INSERT INTO menus VALUES ('Gen-9', maketraductionlangsyst('Gestion Client'), 'Gen-8', 4, 1, true, 5, true);
INSERT INTO menus VALUES ('Gen-10', maketraductionlangsyst('Menu Epargne'), 'Gen-8', 4, 2, true, 51, true);
INSERT INTO menus VALUES ('Gen-11', maketraductionlangsyst('Menu Crédit'), 'Gen-8', 4, 3, true, 101, true);
INSERT INTO menus VALUES ('Gen-4', maketraductionlangsyst('Menu Documents'), 'Gen-8', 4, 4, true, 40, true);
INSERT INTO menus VALUES ('Rex', maketraductionlangsyst('Retrait express'), 'Gen-8', 4, 5, true, 85, true);
INSERT INTO menus VALUES ('Dex', maketraductionlangsyst('Dépôt express'), 'Gen-8', 4, 6, true, 86, true);
INSERT INTO menus VALUES ('Aut', maketraductionlangsyst('Recharge Ferlo'), 'Gen-8', 4, 7, true, 81, true);
INSERT INTO menus VALUES ('Apf', maketraductionlangsyst('Ajout profil'), 'Gpf', 4, 1, true, 256, false);
INSERT INTO menus VALUES ('Cpf', maketraductionlangsyst('Consultation profil'), 'Gpf', 4, 2, true, 257, false);
INSERT INTO menus VALUES ('Mpf', maketraductionlangsyst('Modification profil'), 'Gpf', 4, 3, true, 258, false);
INSERT INTO menus VALUES ('Spf', maketraductionlangsyst('Suppression profil'), 'Gpf', 4, 4, true, 259, false);
INSERT INTO menus VALUES ('Mdp-0', maketraductionlangsyst('Information'), 'Mdp', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mdp-1', maketraductionlangsyst('Saisie'), 'Mdp', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mdp-2', maketraductionlangsyst('Confirmation'), 'Mdp', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Aus', maketraductionlangsyst('Ajout utilisateur'), 'Gus', 4, 1, true, 270, false);
INSERT INTO menus VALUES ('Cus', maketraductionlangsyst('Consultation utilisateur'), 'Gus', 4, 2, true, 271, false);
INSERT INTO menus VALUES ('Mus', maketraductionlangsyst('Modification utilisateur'), 'Gus', 4, 3, true, 272, false);
INSERT INTO menus VALUES ('Sus', maketraductionlangsyst('Suppression utilisateur'), 'Gus', 4, 4, true, 273, false);
INSERT INTO menus VALUES ('Alo', maketraductionlangsyst('Ajout code'), 'Glo', 4, 1, true, 288, false);
INSERT INTO menus VALUES ('Clo', maketraductionlangsyst('Consultation code'), 'Glo', 4, 2, true, 289, false);
INSERT INTO menus VALUES ('Mlo', maketraductionlangsyst('Modification code'), 'Glo', 4, 3, true, 290, false);
INSERT INTO menus VALUES ('Mlp', maketraductionlangsyst('Modification association profil'), 'Glo', 4, 4, true, 297, false);
INSERT INTO menus VALUES ('Slo', maketraductionlangsyst('Suppression code'), 'Glo', 4, 5, true, 291, false);
INSERT INTO menus VALUES ('Sta', maketraductionlangsyst('Sélection table'), 'Gta', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Eta', maketraductionlangsyst('Sélection entrée'), 'Gta', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Pta', maketraductionlangsyst('Paramétrage table'), 'Gta', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Cex', maketraductionlangsyst('Gestion des champs extras'), 'Gen-12', 3, 9, true, 281, true);
INSERT INTO menus VALUES ('Jgu-1', maketraductionlangsyst('Saisie encaisse'), 'Jgu', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Jgu-2', maketraductionlangsyst('Saisie encaisse'), 'Jgu', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Jgu-3', maketraductionlangsyst('Confirmation'), 'Jgu', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Cca-1', maketraductionlangsyst('Saisie'), 'Cca', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Cca-2', maketraductionlangsyst('Confirmation'), 'Cca', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Swi-1', maketraductionlangsyst('Critères affissage'), 'Swi', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Swi-2', maketraductionlangsyst('Listes des messages SWIFT'), 'Swi', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Swi-3', maketraductionlangsyst('Détail message SWIFT'), 'Swi', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Swi-4', maketraductionlangsyst('Confirmation saisie'), 'Swi', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Swi-5', maketraductionlangsyst('Modification message SWIFT'), 'Swi', 4, 5, false, NULL, false);
INSERT INTO menus VALUES ('Swi-6', maketraductionlangsyst('Traitement des message SWIFT'), 'Swi', 4, 6, false, NULL, false);
INSERT INTO menus VALUES ('Vgu-1', maketraductionlangsyst('Critères de recherche'), 'Vgu', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Vgu-2', maketraductionlangsyst('Visualisation'), 'Vgu', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Vgu-3', maketraductionlangsyst('Rapport transactions'), 'Vgu', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Vtg-1', maketraductionlangsyst('Critères de recherche'), 'Vtg', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Vtg-2', maketraductionlangsyst('Visualisation'), 'Vtg', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Vtg-3', maketraductionlangsyst('Rapport transactions'), 'Vtg', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Att-1', maketraductionlangsyst('Saisie critères'), 'Att', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Att-2', maketraductionlangsyst('Affichage attente crédit'), 'Att', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Att-3', maketraductionlangsyst('Validation'), 'Att', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Att-4', maketraductionlangsyst('Rejet'), 'Att', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Att-5', maketraductionlangsyst('Nouveau'), 'Att', 4, 5, false, NULL, false);
INSERT INTO menus VALUES ('Att-6', maketraductionlangsyst('Confirmation'), 'Att', 4, 6, false, NULL, false);
INSERT INTO menus VALUES ('Oag-1', maketraductionlangsyst('Demande confirmation'), 'Oag', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Oag-2', maketraductionlangsyst('Résultat'), 'Oag', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Fag-1', maketraductionlangsyst('Demande confirmation'), 'Fag', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Fag-2', maketraductionlangsyst('Confirmation'), 'Fag', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Flo-1', maketraductionlangsyst('Saisie code utilisateur'), 'Flo', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Flo-2', maketraductionlangsyst('Saisie encaisse'), 'Flo', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Flo-3', maketraductionlangsyst('Confirmation'), 'Flo', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Acp-1', maketraductionlangsyst('Enrer nouveau solde'), 'Acp', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Acp-2', maketraductionlangsyst('Confirmation'), 'Acp', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Gli-1', maketraductionlangsyst('Etat'), 'Gli', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Gli-2', maketraductionlangsyst('Modification'), 'Gli', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Gli-3', maketraductionlangsyst('Confirmation'), 'Gli', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Fjr-1', maketraductionlangsyst('Demande confirmation'), 'Fjr', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Map-1', maketraductionlangsyst('Saisie informations'), 'Map', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Map-2', maketraductionlangsyst('Confirmation'), 'Map', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Agu-1', maketraductionlangsyst('Saisie informations'), 'Agu', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Agu-2', maketraductionlangsyst('Confirmation'), 'Agu', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dgu-1', maketraductionlangsyst('Saisie informations'), 'Dgu', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Dgu-2', maketraductionlangsyst('Confirmation'), 'Dgu', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Pdc-1', maketraductionlangsyst('Choix opération'), 'Pdc', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Pdc-2', maketraductionlangsyst('Saisie opération'), 'Pdc', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Pdc-3', maketraductionlangsyst('Enregistrement opération'), 'Pdc', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Sgu-1', maketraductionlangsyst('Saisie informations'), 'Sgu', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Sgu-2', maketraductionlangsyst('Demande confirmation'), 'Sgu', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Sgu-3', maketraductionlangsyst('Confirmation'), 'Sgu', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dlf-1', maketraductionlangsyst('Saisie informations'), 'Dlf', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Dlf-2', maketraductionlangsyst('Demande confirmation'), 'Dlf', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dlf-3', maketraductionlangsyst('Confirmation'), 'Dlf', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Psf-1', maketraductionlangsyst('Saisie informations'), 'Psf', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Psf-2', maketraductionlangsyst('Demande confirmation'), 'Psf', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Psf-3', maketraductionlangsyst('Confirmation'), 'Psf', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Vjf-1', maketraductionlangsyst('Visualisation'), 'Vjf', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Cra-1', maketraductionlangsyst('Sélection type'), 'Cra', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Cra-2', maketraductionlangsyst('Sélection contenu'), 'Cra', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Cra-3', maketraductionlangsyst('Génération rapport'), 'Cra', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Cra-4', maketraductionlangsyst('Export données'), 'Cra', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Kra-1', maketraductionlangsyst('Sélection type'), 'Kra', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Kra-2', maketraductionlangsyst('Personalisation du rapport'), 'Kra', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Kra-3', maketraductionlangsyst('Impression'), 'Kra', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Kra-5', maketraductionlangsyst('Export données'), 'Kra', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Era-1', maketraductionlangsyst('Sélection type'), 'Era', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Era-2', maketraductionlangsyst('Personnalisation rapport'), 'Era', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Era-3', maketraductionlangsyst('Impression rapport'), 'Era', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Era-4', maketraductionlangsyst('Export données'), 'Era', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ara-1', maketraductionlangsyst('Sélection type'), 'Ara', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ara-2', maketraductionlangsyst('Saisie informations'), 'Ara', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ara-3', maketraductionlangsyst('Génération rapport'), 'Ara', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Pcs-1', maketraductionlangsyst('Sélection type'), 'Pcs', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Pcs-2', maketraductionlangsyst('Calcul provision'), 'Pcs', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Pcs-3', maketraductionlangsyst('Confirmation'), 'Pcs', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Pcs-4', maketraductionlangsyst('Modification'), 'Pcs', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Pcs-8', maketraductionlangsyst('Affichage'), 'Pcs', 4, 5, false, NULL, false);
INSERT INTO menus VALUES ('Tra-1', maketraductionlangsyst('Sélection type'), 'Tra', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Tra-2', maketraductionlangsyst('Saisie informations'), 'Tra', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Tra-3', maketraductionlangsyst('Génération rapport'), 'Tra', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rad-1', maketraductionlangsyst('Choix date'), 'Rad', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rad-2', maketraductionlangsyst('Passage en perte'), 'Rad', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Tva-1', maketraductionlangsyst('Choix période'), 'Tva', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Tva-2', maketraductionlangsyst('Déclaration tva'), 'Tva', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Tva-3', maketraductionlangsyst('Confirmation déclaration tva'), 'Tva', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Tva-4', maketraductionlangsyst('Edition de déclaration tva'), 'Tva', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Tva-5', maketraductionlangsyst('Confirmation édition'), 'Tva', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Sra-1', maketraductionlangsyst('Choix du produit'), 'Sra', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Sra-4', maketraductionlangsyst('Saisie proposition'), 'Sra', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Sra-2', maketraductionlangsyst('Affichage proposition'), 'Sra', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Sra-3', maketraductionlangsyst('Impression proposition'), 'Sra', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Dat-1', maketraductionlangsyst('Sélection type de sauvegarde'), 'Dat', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Dat-2', maketraductionlangsyst('Confirmation'), 'Dat', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dat-3', maketraductionlangsyst('Choix du nom'), 'Dat', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Cdb-1', maketraductionlangsyst('Sélection données agence'), 'Cdb', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Cdb-2', maketraductionlangsyst('Consolidation des données'), 'Cdb', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ppc-1', maketraductionlangsyst('Plan comptable'), 'Ppc', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ppc-2', maketraductionlangsyst('Choix du compte centralisateur'), 'Ppc', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ppc-3', maketraductionlangsyst('Création/Modification'), 'Ppc', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ppc-5', maketraductionlangsyst('Confirmation'), 'Ppc', 4, 5, false, NULL, false);
INSERT INTO menus VALUES ('Gop-1', maketraductionlangsyst('Choix opération'), 'Gop', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Gop-2', maketraductionlangsyst('Modification'), 'Gop', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Gop-5', maketraductionlangsyst('Association taxe'), 'Gop', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Gop-6', maketraductionlangsyst('Ajout taxe'), 'Gop-5', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Gop-7', maketraductionlangsyst('Suppression taxe'), 'Gop-5', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Gop-3', maketraductionlangsyst('Confirmation'), 'Gop', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Odc-1', maketraductionlangsyst('Choix opération diverse'), 'Odc', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Odc-2', maketraductionlangsyst('Saisie opération diverse'), 'Odc', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Odc-4', maketraductionlangsyst('Modification opération diverse'), 'Odc', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Odc-8', maketraductionlangsyst('Association taxe'), 'Odc', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Odc-9', maketraductionlangsyst('Ajout taxe'), 'Odc-8',5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Odc-10', maketraductionlangsyst('Suppression taxe'), 'Odc-8', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Odc-6', maketraductionlangsyst('Suppression opération diverse'), 'Odc', 4, 5, false, NULL, false);
INSERT INTO menus VALUES ('Odc-3', maketraductionlangsyst('Confirmation'), 'Odc', 4, 6, false, NULL, false);


INSERT INTO menus VALUES ('Gel-1', maketraductionlangsyst('Choix d''une écriture libre'), 'Gel', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Gel-2', maketraductionlangsyst('Saisie  écriture libre'), 'Gel', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Gel-4', maketraductionlangsyst('Modification écriture libre'), 'Gel', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Gel-6', maketraductionlangsyst('Suppression écriture libre'), 'Gel', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Gel-3', maketraductionlangsyst('Confirmation'), 'Gel', 4, 5, false, NULL, false);

INSERT INTO menus VALUES ('Osa-1', maketraductionlangsyst('Saisie opératon'), 'Osa', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Osa-2', maketraductionlangsyst('Confirmation saisie'), 'Osa', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Osa-3', maketraductionlangsyst('Passage opératon'), 'Osa', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ano-1', maketraductionlangsyst('Sélection de la période'), 'Ano', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ano-2', maketraductionlangsyst('Affichage mouvements réciproques'), 'Ano', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ano-3', maketraductionlangsyst('Saisie écriture annulation'), 'Ano', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ano-4', maketraductionlangsyst('Passage écriture annulation'), 'Ano', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Ecr-1', maketraductionlangsyst('Choix écriture'), 'Ecr', 4, 1, true, NULL, false);
INSERT INTO menus VALUES ('Opd-1', maketraductionlangsyst('Ajout Opérations diverses'), 'Ecr-1', 5, 1, true, NULL, false);
INSERT INTO menus VALUES ('Opd-2', maketraductionlangsyst('Confirmation'), 'Opd-1', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Opd-3', maketraductionlangsyst('Enregistrement'), 'Opd-1', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Opd-4', maketraductionlangsyst('Validation'), 'Opd-1', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Opd-5', maketraductionlangsyst('Modification opérations diverses'), 'Ecr-1', 5, 2, true, NULL, false);
INSERT INTO menus VALUES ('Opd-6', maketraductionlangsyst('Saisie modification'), 'Opd-5', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Opd-7', maketraductionlangsyst('Confirmation'), 'Opd-5', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Opd-8', maketraductionlangsyst('Enregistrement'), 'Opd-5', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Opd-9', maketraductionlangsyst('Validation'), 'Opd-5', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Opa-1', maketraductionlangsyst('Ajout Opérations auxiliaires'), 'Ecr-1', 5, 3, true, NULL, false);
INSERT INTO menus VALUES ('Opa-2', maketraductionlangsyst('Confirmation'), 'Opa-1', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Opa-3', maketraductionlangsyst('Enregistrement'), 'Opa-1', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Opa-4', maketraductionlangsyst('Validation'), 'Opa-1', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Opa-5', maketraductionlangsyst('Modification opé auxiliaires'), 'Ecr-1', 5, 4, true, NULL, false);
INSERT INTO menus VALUES ('Opa-6', maketraductionlangsyst('Saisie modification'), 'Opa-5', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Opa-7', maketraductionlangsyst('Confirmation'), 'Opa-5', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Opa-8', maketraductionlangsyst('Enregistrement'), 'Opa-5', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Opa-9', maketraductionlangsyst('Validation'), 'Opa-5', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Opa-10', maketraductionlangsyst('Suppression'), 'Opa-5', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Gex-1', maketraductionlangsyst('Visualisation exercice'), 'Gex', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Gex-2', maketraductionlangsyst('Confirmation Clôture'), 'Gex', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Gex-3', maketraductionlangsyst('Clôture exercice'), 'Gex', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Gex-4', maketraductionlangsyst('Clôture périodique'), 'Gex', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Gex-5', maketraductionlangsyst('Confirmation Clôture'), 'Gex', 4, 5, false, NULL, false);
INSERT INTO menus VALUES ('Rfv-1', maketraductionlangsyst('Demande Autorisation'), 'Rfv', 4, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rfv-2', maketraductionlangsyst('Confirmation Autorisation'), 'Rfv', 4, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rfv-3', maketraductionlangsyst('Attente de Recharge'), 'Rfv', 4, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rfv-4', maketraductionlangsyst('Confirmation de Recharge'), 'Rfv', 4, 4, false, NULL, false);
INSERT INTO menus VALUES ('Jou-1', maketraductionlangsyst('Traitement des journaux'), 'Jou', 4, 1, true, NULL, false);
INSERT INTO menus VALUES ('Jou-2', maketraductionlangsyst('Ajout de journaux'), 'Jou-1', 5, 1, true, NULL, false);
INSERT INTO menus VALUES ('Jou-3', maketraductionlangsyst('Modification des journaux'), 'Jou-1', 5, 2, true, NULL, false);
INSERT INTO menus VALUES ('Jou-4', maketraductionlangsyst('Consultation des journaux'), 'Jou-1', 5, 3, false, NULL, false);
INSERT INTO menus VALUES ('Jou-5', maketraductionlangsyst('Suppression des journaux'), 'Jou-1', 5, 4, true, NULL, false);
INSERT INTO menus VALUES ('Jou-6', maketraductionlangsyst('Gestion contrepartie'), 'Jou-1', 5, 5, false, NULL, false);
INSERT INTO menus VALUES ('Jou-7', maketraductionlangsyst('Confirmation ajout'), 'Jou-2', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Jou-8', maketraductionlangsyst('Confirmation modification'), 'Jou-3', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Jou-9', maketraductionlangsyst('Confirmation suppression'), 'Jou-5', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Jou-10', maketraductionlangsyst('Ajout contrepartie'), 'Jou-6', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Jou-11', maketraductionlangsyst('Confirmation suppression'), 'Jou-6', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Jou-12', maketraductionlangsyst('Suppression contrepartie'), 'Jou-6', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Jou-13', maketraductionlangsyst('Comptes de liaison'), 'Jou-1', 5, 6, false, NULL, false);
INSERT INTO menus VALUES ('Jou-14', maketraductionlangsyst('Ajout de liaison'), 'Jou-13', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Jou-15', maketraductionlangsyst('Modification de liason'), 'Jou-13', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Jou-16', maketraductionlangsyst('Suppressions de liaison'), 'Jou-13', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ccl', maketraductionlangsyst('Consultation client'), 'Gen-9', 5, 1, true, 25, true);
INSERT INTO menus VALUES ('Mcl', maketraductionlangsyst('Modification client'), 'Gen-9', 5, 2, true, 10, true);
INSERT INTO menus VALUES ('Rel', maketraductionlangsyst('Gestion des relations'), 'Gen-9', 5, 3, true, 11, true);
INSERT INTO menus VALUES ('Pfh', maketraductionlangsyst('Perception frais adhésion'), 'Gen-9', 5, 4, true, 31, true);
INSERT INTO menus VALUES ('Sps', maketraductionlangsyst('Souscription parts sociales'), 'Gen-9', 5, 5, true, 20, true);
INSERT INTO menus VALUES ('Sdc', maketraductionlangsyst('Simulation défection'), 'Gen-9', 5, 6, true, 17, true);
INSERT INTO menus VALUES ('Dcl', maketraductionlangsyst('Défection client'), 'Gen-9', 5, 7, true, 15, true);
INSERT INTO menus VALUES ('Ass', maketraductionlangsyst('Appel à l''assurance'), 'Gen-9', 5, 8, true, 19, true);
INSERT INTO menus VALUES ('Fcl', maketraductionlangsyst('Finalisation défection client'), 'Gen-9', 5, 9, true, 16, true);
INSERT INTO menus VALUES ('Vcp', maketraductionlangsyst('Consultation compte'), 'Gen-10', 5, 1, true, 80, true);
INSERT INTO menus VALUES ('Ocp', maketraductionlangsyst('Ouverture compte'), 'Gen-10', 5, 2, true, 53, true);
INSERT INTO menus VALUES ('Tcp', maketraductionlangsyst('Transfert'), 'Gen-10', 5, 3, true, 76, true);
INSERT INTO menus VALUES ('Rcp', maketraductionlangsyst('Retrait'), 'Gen-10', 5, 4, true, 70, true);
INSERT INTO menus VALUES ('Dcp', maketraductionlangsyst('Dépôt'), 'Gen-10', 5, 5, true, 75, true);
INSERT INTO menus VALUES ('Tch', maketraductionlangsyst('Chèques'), 'Gen-10', 5, 6, true, 77, true);
INSERT INTO menus VALUES ('Pda', maketraductionlangsyst('Prolongation de DAT'), 'Gen-10', 5, 7, true, 78, true);
INSERT INTO menus VALUES ('Scp', maketraductionlangsyst('Simulation arrêté compte'), 'Gen-10', 5, 8, true, 55, true);
INSERT INTO menus VALUES ('Ccp', maketraductionlangsyst('Clôture compte'), 'Gen-10', 5, 9, true, 54, true);
INSERT INTO menus VALUES ('Ope', maketraductionlangsyst('Ordres Permanents'), 'Gen-10', 5, 10, true, 79, true);
INSERT INTO menus VALUES ('Mdc', maketraductionlangsyst('Modification de compte'), 'Gen-10', 5, 11, true, 88, true);
INSERT INTO menus VALUES ('Bdc', maketraductionlangsyst('Bloquer / débloquer compte'), 'Gen-10', 5, 12, true, 89, true);
INSERT INTO menus VALUES ('Man', maketraductionlangsyst('Gestion des mandats'), 'Gen-10', 5, 13, true, 90, true);
INSERT INTO menus VALUES ('Ced', maketraductionlangsyst('Comptes d''épargne dormant'), 'Gen-10', 5, 14, true, 91, true);
INSERT INTO menus VALUES ('Ado', maketraductionlangsyst('Mise en place dossier de crédit'), 'Gen-11', 5, 1, true, 105, true);
INSERT INTO menus VALUES ('Apd', maketraductionlangsyst('Approbation dossier de crédit'), 'Gen-11', 5, 2, true, 110, true);
INSERT INTO menus VALUES ('Dbd', maketraductionlangsyst('Déboursement des fonds '), 'Gen-11', 5, 3, true, 125, true);
INSERT INTO menus VALUES ('Rcr', maketraductionlangsyst('Remboursement crédit'), 'Gen-11', 5, 4, true, 147, true);
INSERT INTO menus VALUES ('Rga', maketraductionlangsyst('Réalisation garanties'), 'Gen-11', 5, 5, true, 148, true);
INSERT INTO menus VALUES ('Rdo', maketraductionlangsyst('Rééch./Moratoire'), 'Gen-11', 5, 6, true, 145, true);
INSERT INTO menus VALUES ('Pen', maketraductionlangsyst('Susp / ajust pénalités'), 'Gen-11', 5, 7, true, 131, true);
INSERT INTO menus VALUES ('Cdo', maketraductionlangsyst('Consultation dossier'), 'Gen-11', 5, 8, true, 140, true);
INSERT INTO menus VALUES ('Mdd', maketraductionlangsyst('Modification dossier'), 'Gen-11', 5, 9, true, 130, true);
INSERT INTO menus VALUES ('Cdd', maketraductionlangsyst('Correction d''un dossier de crédit'), 'Gen-11', 5, 10, true, 129, true);
INSERT INTO menus VALUES ('And', maketraductionlangsyst('Annulation du dossier de crédit'), 'Gen-11', 5, 11, true, 120, true);
INSERT INTO menus VALUES ('Rfd', maketraductionlangsyst('Rejet d''un dossier de crédit'), 'Gen-11', 5, 12, true, 115, true);
INSERT INTO menus VALUES ('Abi', maketraductionlangsyst('Abattement intérêts / pénalités'), 'Gen-11', 5, 13, true, 132, true);
INSERT INTO menus VALUES ('Sdo', maketraductionlangsyst('Simulation échéancier'), 'Gen-11', 5, 14, true, 135, true);
INSERT INTO menus VALUES ('Adp', maketraductionlangsyst('Annulation déboursement progressif'), 'Gen-11', 5, 15, true, 126, true);
INSERT INTO menus VALUES ('Chq', maketraductionlangsyst('Commande chèquier'), 'Gen-4', 5, 1, true, 41, true);
INSERT INTO menus VALUES ('Rch', maketraductionlangsyst('Retrait chèquier'), 'Gen-4', 5, 2, true, 42, true);
INSERT INTO menus VALUES ('Ext', maketraductionlangsyst('Extraits de compte'), 'Gen-4', 5, 3, true, 43, true);
INSERT INTO menus VALUES ('Rap', maketraductionlangsyst('Situation globale client'), 'Gen-4', 5, 4, true, 44, true);
INSERT INTO menus VALUES ('Och', maketraductionlangsyst('Mise en opposition'), 'Gen-4', 5, 5, true, 45, true); 
INSERT INTO menus VALUES ('Aus-1', maketraductionlangsyst('Informations personnelles'), 'Aus', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Aus-2', maketraductionlangsyst('Informations code utilisateur'), 'Aus', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Aus-3', maketraductionlangsyst('Confirmation'), 'Aus', 5, 3, false, NULL, false);
INSERT INTO menus VALUES ('Cus-1', maketraductionlangsyst('Consultation utilisateur'), 'Cus', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Cus-2', maketraductionlangsyst('Consultation code utilisateur'), 'Cus', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Mus-1', maketraductionlangsyst('Modification utilisateur'), 'Mus', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mus-2', maketraductionlangsyst('Confirmation'), 'Mus', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Sus-1', maketraductionlangsyst('Demande confirmation'), 'Sus', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Sus-2', maketraductionlangsyst('Confirmation'), 'Sus', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Apf-1', maketraductionlangsyst('Saisie informations'), 'Apf', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Apf-2', maketraductionlangsyst('Confirmation'), 'Apf', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Cpf-1', maketraductionlangsyst('Consultation'), 'Cpf', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mpf-1', maketraductionlangsyst('Saisie modifications'), 'Mpf', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mpf-2', maketraductionlangsyst('Confirmation'), 'Mpf', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Spf-1', maketraductionlangsyst('Demande confirmation'), 'Spf', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Spf-2', maketraductionlangsyst('Confirmation'), 'Spf', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Alo-1', maketraductionlangsyst('Informations code'), 'Alo', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Alo-2', maketraductionlangsyst('Confirmation'), 'Alo', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Clo-1', maketraductionlangsyst('Consultation'), 'Clo', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mlo-1', maketraductionlangsyst('Saisie modifications'), 'Mlo', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mlo-2', maketraductionlangsyst('Confirmation'), 'Mlo', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Mlp-1', maketraductionlangsyst('Saisie profil'), 'Mlp', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mlp-2', maketraductionlangsyst('Confirmation'), 'Mlp', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Slo-1', maketraductionlangsyst('Demande confirmation'), 'Slo', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Slo-2', maketraductionlangsyst('Confirmation'), 'Slo', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rex-1', maketraductionlangsyst('Saisie montant'), 'Rex', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rex-2', maketraductionlangsyst('Confirmation montant'), 'Rex', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rex-3', maketraductionlangsyst('Confirmation retrait'), 'Rex', 5, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dex-1', maketraductionlangsyst('Saisie montant'), 'Dex', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Dex-2', maketraductionlangsyst('Confirmation montant'), 'Dex', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dex-3', maketraductionlangsyst('Confirmation dépôt'), 'Dex', 5, 3, false, NULL, false);
INSERT INTO menus VALUES ('Aut-1', maketraductionlangsyst('Demande Autorisation'), 'Aut', 5, 1, false, NULL, false);
INSERT INTO menus VALUES ('Aut-2', maketraductionlangsyst('Confirmation Autorisation'), 'Aut', 5, 2, false, NULL, false);
INSERT INTO menus VALUES ('Aut-3', maketraductionlangsyst('Attente de Recharge'), 'Aut', 5, 3, false, NULL, false);
INSERT INTO menus VALUES ('Aut-4', maketraductionlangsyst('Confirmation de Recharge'), 'Aut', 5, 4, false, NULL, false);
INSERT INTO menus VALUES ('Ccl-1', maketraductionlangsyst('Données personnelles'), 'Ccl', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ccl-2', maketraductionlangsyst('Informations financières'), 'Ccl', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ccl-3', maketraductionlangsyst('Frais en attente'), 'Ccl', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ccl-4', maketraductionlangsyst('Impression fiche client'), 'Ccl', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Ccl-5', maketraductionlangsyst('Situation analytique client'), 'Ccl', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Sps-1', maketraductionlangsyst('Saisie'), 'Sps', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Sps-2', maketraductionlangsyst('Demande Confirmation'), 'Sps', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Sps-3', maketraductionlangsyst('Confirmation'), 'Sps', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Mcl-1', maketraductionlangsyst('Données personnelles'), 'Mcl', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mcl-2', maketraductionlangsyst('Confirmation'), 'Mcl', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rel-1', maketraductionlangsyst('Choix relation'), 'Rel', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rel-2', maketraductionlangsyst('Saisie données'), 'Rel', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rel-3', maketraductionlangsyst('Confirmation'), 'Rel', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Fcl-1', maketraductionlangsyst('Solde des comptes'), 'Fcl', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Fcl-2', maketraductionlangsyst('Confirmation défection'), 'Fcl', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dcp-1', maketraductionlangsyst('Choix du compte'), 'Dcp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Dcp-2', maketraductionlangsyst('Saisie montant'), 'Dcp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dcp-3', maketraductionlangsyst('Confirmation montant'), 'Dcp', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dcp-4', maketraductionlangsyst('Traitement'), 'Dcp', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Tch-1', maketraductionlangsyst('Saisie des informations'), 'Tch', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Tch-2', maketraductionlangsyst('Traitement'), 'Tch', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Vcp-1', maketraductionlangsyst('Choix du compte'), 'Vcp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Vcp-2', maketraductionlangsyst('Consultation compte'), 'Vcp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Vcp-3', maketraductionlangsyst('Rapport PDF'), 'Vcp', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Vcp-4', maketraductionlangsyst('Export CSV'), 'Vcp', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Ocp-1', maketraductionlangsyst('Saisie informations compte'), 'Ocp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ocp-2', maketraductionlangsyst('Intitulé du compte'), 'Ocp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ocp-3', maketraductionlangsyst('Choix compte source'), 'Ocp', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ocp-4', maketraductionlangsyst('Versement sur compte'), 'Ocp', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Ocp-5', maketraductionlangsyst('Confirmation versement'), 'Ocp', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Ocp-6', maketraductionlangsyst('Confirmation création compte'), 'Ocp', 6, 6, false, NULL, false);
INSERT INTO menus VALUES ('Ado-1', maketraductionlangsyst('Saisie informations'), 'Ado', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ado-2', maketraductionlangsyst('Echéancier théorique'), 'Ado', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ado-3', maketraductionlangsyst('Perception frais dossier'), 'Ado', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ado-4', maketraductionlangsyst('Blocage des garanties'), 'Ado', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Ado-5', maketraductionlangsyst('Confirmation'), 'Ado', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Ado-7', maketraductionlangsyst('Mobilisation des garanties'), 'Ado', 6, 7, false, NULL, false);
INSERT INTO menus VALUES ('Ado-8', maketraductionlangsyst('Ajout de garantie'), 'Ado-7', 7, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ado-9', maketraductionlangsyst('Confirmation ajout garantie'), 'Ado-7', 7, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ado-10', maketraductionlangsyst('Modification garantie'), 'Ado-7', 7, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ado-11', maketraductionlangsyst('Suppression garantie'), 'Ado-7', 7, 4, false, NULL, false);
INSERT INTO menus VALUES ('Apd-1', maketraductionlangsyst('Sélection dossier de crédit'), 'Apd', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Apd-2', maketraductionlangsyst('Approbation dossier de crédit'), 'Apd', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Apd-3', maketraductionlangsyst('Echéancier théorique'), 'Apd', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Apd-4', maketraductionlangsyst('Blocage des garanties'), 'Apd', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Apd-5', maketraductionlangsyst('Confirmation'), 'Apd', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Apd-6', maketraductionlangsyst('Gestion des garanties'), 'Apd', 6, 6, true, NULL, false);
INSERT INTO menus VALUES ('Apd-7', maketraductionlangsyst('Ajout de garanties'), 'Apd-6', 7, 1, false, NULL, false);
INSERT INTO menus VALUES ('Apd-8', maketraductionlangsyst('Modification de garantie'), 'Apd-6', 7, 2, false, NULL, false);
INSERT INTO menus VALUES ('Apd-9', maketraductionlangsyst('Suppression de garantie'), 'Apd-6', 7, 3, false, NULL, false);
INSERT INTO menus VALUES ('Apd-10', maketraductionlangsyst('Confirmation garantie'), 'Apd-6', 7, 4, false, NULL, false);
INSERT INTO menus VALUES ('Rfd-1', maketraductionlangsyst('Sélection dossier crédit'), 'Rfd', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rfd-2', maketraductionlangsyst('Rejet dossier de crédit'), 'Rfd', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rfd-3', maketraductionlangsyst('Confirmation'), 'Rfd', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('And-1', maketraductionlangsyst('Sélection dossier de crédit'), 'And', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('And-2', maketraductionlangsyst('Annulation dossier de crédit'), 'And', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('And-3', maketraductionlangsyst('Confirmation'), 'And', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-1', maketraductionlangsyst('Sélection dossier de crédit'), 'Dbd', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-11', maketraductionlangsyst('Mode de déboursement'), 'Dbd', 6, 11, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-2', maketraductionlangsyst('Déboursement des fonds'), 'Dbd', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-3', maketraductionlangsyst('Echéancier réel'), 'Dbd', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-10', maketraductionlangsyst('Perception frais dossier'), 'Dbd', 6, 10, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-4', maketraductionlangsyst('Transfert des garanties'), 'Dbd', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-5', maketraductionlangsyst('Perception des commissions'), 'Dbd', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-6', maketraductionlangsyst('Transfert des assurances'), 'Dbd', 6, 6, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-7', maketraductionlangsyst('Transfert des fonds du crédit'), 'Dbd', 6, 7, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-8', maketraductionlangsyst('Confirmation'), 'Dbd', 6, 8, false, NULL, false);
INSERT INTO menus VALUES ('Dbd-9', maketraductionlangsyst('Impression échéancier'), 'Dbd', 6, 9, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-1', maketraductionlangsyst('Sélection dossier de crédit'), 'Mdd', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-2', maketraductionlangsyst('Modification dossier de crédit'), 'Mdd', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-3', maketraductionlangsyst('Blocage des garanties'), 'Mdd', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-4', maketraductionlangsyst('Confirmation'), 'Mdd', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-5', maketraductionlangsyst('Echéancier théorique'), 'Mdd', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-6', maketraductionlangsyst('Affichage des garanties'), 'Mdd', 6, 6, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-7', maketraductionlangsyst('Ajout de garantie'), 'Mdd-6', 7, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-8', maketraductionlangsyst('Modification de garantie'), 'Mdd-6', 7, 2, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-9', maketraductionlangsyst('Suppression de garantie'), 'Mdd-6', 7, 3, false, NULL, false);
INSERT INTO menus VALUES ('Mdd-10', maketraductionlangsyst('Confirmation'), 'Mdd-6', 7, 4, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-1', maketraductionlangsyst('Type de correction'), 'Cdd', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-2', maketraductionlangsyst('Sélection dossier de crédit'), 'Cdd', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-3', maketraductionlangsyst('Affichage des échéances'), 'Cdd', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-4', maketraductionlangsyst('Choix des remboursements'), 'Cdd', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-5', maketraductionlangsyst('Rembousements à annuler'), 'Cdd', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-6', maketraductionlangsyst('Confirmation annulation'), 'Cdd', 6, 6, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-7', maketraductionlangsyst('Suppression dossier'), 'Cdd', 6, 6, false, NULL, false);
INSERT INTO menus VALUES ('Cdd-8', maketraductionlangsyst('Confirmation suppression'), 'Cdd', 6, 7, false, NULL, false);
INSERT INTO menus VALUES ('Cdo-1', maketraductionlangsyst('Sélection dossier de crédit'), 'Cdo', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Cdo-2', maketraductionlangsyst('Consultation dossier de crédit'), 'Cdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Cdo-3', maketraductionlangsyst('Consultation échéancier'), 'Cdo', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Cdo-4', maketraductionlangsyst('Suivi du crédit'), 'Cdo', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Cdo-5', maketraductionlangsyst('Consultation des garanties'), 'Cdo', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Cdo-6', maketraductionlangsyst('Impression Suivi crédit'), 'Cdo', 6, 6, false, NULL, false);
INSERT INTO menus VALUES ('Cdo-7', maketraductionlangsyst('Export CSV'), 'Cdo', 6, 7, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-1', maketraductionlangsyst('Sélection dossier de crédit'), 'Rdo', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-4', maketraductionlangsyst('Rééchelonnement crédit'), 'Rdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-6', maketraductionlangsyst('Gestion des garanties'), 'Rdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-7', maketraductionlangsyst('Ajout d''une garantie'), 'Rdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-8', maketraductionlangsyst('Modification d''une garantie'), 'Rdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-9', maketraductionlangsyst('Suppression d''une garantie'), 'Rdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-10', maketraductionlangsyst('Confirmation garantie'), 'Rdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-2', maketraductionlangsyst('Echéancier théorique'), 'Rdo', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-5', maketraductionlangsyst('Blocage des garanties'), 'Rdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdo-3', maketraductionlangsyst('Confirmation'), 'Rdo', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Sdo-1', maketraductionlangsyst('Choix du produit'), 'Sdo', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Sdo-4', maketraductionlangsyst('Saisie proposition'), 'Sdo', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Sdo-2', maketraductionlangsyst('Affichage proposition'), 'Sdo', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Sdo-3', maketraductionlangsyst('Impression proposition'), 'Sdo', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Adp-1', maketraductionlangsyst('Sélection dossier de crédit'), 'Adp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Adp-2', maketraductionlangsyst('Annulation déboursement progressif'), 'Adp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Adp-3', maketraductionlangsyst('Confirmation'), 'Adp', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rcp-1', maketraductionlangsyst('Choix du compte'), 'Rcp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rcp-2', maketraductionlangsyst('Saisie montant'), 'Rcp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rcp-3', maketraductionlangsyst('Confirmation montant'), 'Rcp', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rcp-4', maketraductionlangsyst('Traitement'), 'Rcp', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Tcp-1', maketraductionlangsyst('Choix du compte source'), 'Tcp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Tcp-2', maketraductionlangsyst('Saisie informations'), 'Tcp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Tcp-3', maketraductionlangsyst('Confirmation transfert'), 'Tcp', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dcl-1', maketraductionlangsyst('Raison de la défection'), 'Dcl', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Dcl-2', maketraductionlangsyst('Informations sur les comptes'), 'Dcl', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dcl-3', maketraductionlangsyst('Arrêté des comptes'), 'Dcl', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Dcl-4', maketraductionlangsyst('Arrêté compte de base'), 'Dcl', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Dcl-5', maketraductionlangsyst('Confirmation défection'), 'Dcl', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Sdc-1', maketraductionlangsyst('Situation finale'), 'Sdc', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Fdc-1', maketraductionlangsyst('Calcul de la balance'), 'Fdc', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ccp-1', maketraductionlangsyst('Sélection compte'), 'Ccp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ccp-2', maketraductionlangsyst('Traitement clôture'), 'Ccp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ccp-3', maketraductionlangsyst('Confirmation clôture'), 'Ccp', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Mdc-1', maketraductionlangsyst('Choix du compte'), 'Mdc', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mdc-3', maketraductionlangsyst('Frais de dossier découvert'), 'Mdc', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Mdc-4', maketraductionlangsyst('Confirmation modification'), 'Mdc', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Scp-1', maketraductionlangsyst('Sélection compte'), 'Scp', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Scp-2', maketraductionlangsyst('Informations d''arrêté'), 'Scp', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rcr-1', maketraductionlangsyst('Selection dossier de crédit'), 'Rcr', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rcr-2', maketraductionlangsyst('Mode de remboursement'), 'Rcr', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rcr-3', maketraductionlangsyst('Saisie informations'), 'Rcr', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rcr-4', maketraductionlangsyst('Confirmation saisie'), 'Rcr', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Rcr-5', maketraductionlangsyst('Confirmation'), 'Rcr', 6, 5, false, NULL, false);
INSERT INTO menus VALUES ('Rcr-6', maketraductionlangsyst('Recouvrement crédit en perte'), 'Rcr', 6, 6, false, NULL, false);
INSERT INTO menus VALUES ('Pen-1', maketraductionlangsyst('Sélection dossier de crédit'), 'Pen', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rga-1', maketraductionlangsyst('Selection du crédit'), 'Rga', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rga-2', maketraductionlangsyst('Selection de la garantie'), 'Rga', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rga-3', maketraductionlangsyst('Réalisation de la garantie'), 'Rga', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rga-4', maketraductionlangsyst('Confirmation de la réalisation'), 'Rga', 6, 4, false, NULL, false);
INSERT INTO menus VALUES ('Pen-2', maketraductionlangsyst('Saisie information'), 'Pen', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Pen-3', maketraductionlangsyst('Confirmation'), 'Pen', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Pfh-1', maketraductionlangsyst('Perception'), 'Pfh', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Pfh-2', maketraductionlangsyst('Confirmation'), 'Pfh', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ass-1', maketraductionlangsyst('Sélection Dossier de crédit'), 'Ass', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ass-2', maketraductionlangsyst('Saisie montant'), 'Ass', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ass-3', maketraductionlangsyst('Confirmation'), 'Ass', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Pda-1', maketraductionlangsyst('Choix des comptes'), 'Pda', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Pda-2', maketraductionlangsyst('Confirmation'), 'Pda', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Dev-1', maketraductionlangsyst('Gestion des devises'), 'Gen-12', 3, 7, true, 274, true);
INSERT INTO menus VALUES ('Bdc-1', maketraductionlangsyst('Choix du compte'), 'Bdc', 6, 1, false, 89, false);
INSERT INTO menus VALUES ('Bdc-2', maketraductionlangsyst('Confirmation'), 'Bdc', 6, 2, false, 89, false);
INSERT INTO menus VALUES ('Man-1', maketraductionlangsyst('Choix mandat'), 'Man', 6, 1, false, 90, false);
INSERT INTO menus VALUES ('Man-2', maketraductionlangsyst('Saisie données'), 'Man', 6, 2, false, 90, false);
INSERT INTO menus VALUES ('Man-3', maketraductionlangsyst('Confirmation'), 'Man', 6, 3, false, 90, false);
INSERT INTO menus VALUES ('Ced-1', maketraductionlangsyst('Choix du compte'), 'Ced', 6, 1, false, 91, false);
INSERT INTO menus VALUES ('Ced-2', maketraductionlangsyst('Confirmation'), 'Ced', 6, 2, false, 91, false);
INSERT INTO menus VALUES ('Ope-1', maketraductionlangsyst('Sélection ordre permanent'), 'Ope', 6, 1, false, 79, false);
INSERT INTO menus VALUES ('Ope-2', maketraductionlangsyst('Saisie Information'), 'Ope', 6, 2, false, 79, false);
INSERT INTO menus VALUES ('Ope-3', maketraductionlangsyst('Confirmation'), 'Ope', 6, 3, false, 79, false);
INSERT INTO menus VALUES ('Abi-1', maketraductionlangsyst('Sélection dossier'), 'Abi', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Abi-2', maketraductionlangsyst('Modification de l''échéancier'), 'Abi', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Abi-3', maketraductionlangsyst('Confirmation de l''échéancier'), 'Abi', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Chq-1', maketraductionlangsyst('Sélection compte'), 'Chq', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Chq-2', maketraductionlangsyst('Frais commande'), 'Chq', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Chq-3', maketraductionlangsyst('Confirmation'), 'Chq', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rch-1', maketraductionlangsyst('Sélection chèquier'), 'Rch', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rch-2', maketraductionlangsyst('Confirmation remise'), 'Rch', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ext-1', maketraductionlangsyst('Sélection compte'), 'Ext', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ext-2', maketraductionlangsyst('Impression'), 'Ext', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ext-3', maketraductionlangsyst('Export CSV'), 'Ext', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Rap-1', maketraductionlangsyst('Choix des informations'), 'Rap', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Rap-2', maketraductionlangsyst('Génération rapport'), 'Rap', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rae', maketraductionlangsyst('Rapports externe'), 'Gen-13', 3, 4, true, 370, true); 
INSERT INTO menus VALUES ('Rae-1', maketraductionlangsyst('Sélection type'), 'Rae', 4, 1, false, NULL, false); 
INSERT INTO menus VALUES ('Rae-2', maketraductionlangsyst('Saisie informations'), 'Rae', 4, 2, false, NULL, false); 
INSERT INTO menus VALUES ('Gjr-1', maketraductionlangsyst('Gestion de jasper report'), 'Gen-12', 3, 8, true, 300, true); 
INSERT INTO menus VALUES ('Och-1', maketraductionlangsyst('Sélection chèquier'), 'Och', 6, 1, false, NULL, false); 
INSERT INTO menus VALUES ('Och-2', maketraductionlangsyst('Mise en opposition'), 'Och', 6, 2, false, NULL, false); 
INSERT INTO menus VALUES ('Ich-1', maketraductionlangsyst('Selection fichier'), 'Ich', 3, 1, false, NULL, false);
INSERT INTO menus VALUES ('Ich-2', maketraductionlangsyst('Confirmation fichier'), 'Ich', 3, 2, false, NULL, false);
INSERT INTO menus VALUES ('Ich-3', maketraductionlangsyst('Selection compte'), 'Ich', 3, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ich-4', maketraductionlangsyst('Confirmation compte'), 'Ich', 3, 4, false, NULL, false);

INSERT INTO menus VALUES ('Mec', maketraductionlangsyst('Modification de l''échéancier crédit'), 'Gen-11', 5, 6, true, 136, true);
INSERT INTO menus VALUES ('Mdr-1', maketraductionlangsyst('Demande modification date'), 'Mec', 6, 1, false, NULL, false);
INSERT INTO menus VALUES ('Amd-1', maketraductionlangsyst('Approbation modification date'), 'Mec', 6, 2, false, NULL, false);
INSERT INTO menus VALUES ('Rdc-1', maketraductionlangsyst('Demande raccourcissement'), 'Mec', 6, 3, false, NULL, false);
INSERT INTO menus VALUES ('Ard-1', maketraductionlangsyst('Approbation raccourcissement'), 'Mec',  6, 4, false, NULL, false);

--ID du menu, libellé du menu, nom du menu père, niveau hierarchique, position parmis ses frères, menu (ou terminal)?, fonction associée (doit être renseigné pour tous les champs de type menu), cliquable (dans le frame de gauche)


-- *** TABLE ecrans ***


--ID de l'écran, fichier contenant l'écran, ID du menu associé, fonction d'accès associée
COPY "ecrans" FROM stdin;
Acl-1	modules/clients/ajout_client.php	Acl-1	30
Acl-2	modules/clients/ajout_client.php	Acl-2	30
Acl-3	modules/clients/ajout_client.php	Acl-3	30
Acl-4	modules/clients/ajout_client.php	Acl-4	30
Acl-5	modules/clients/ajout_client.php	Acl-5	30
Acl-6	modules/clients/ajout_client.php	Acl-6	30
Acl-7	modules/clients/ajout_client.php	Acl-2	30
Gen-3	modules/menus/menu.php	Gen-3	0
Gen-4	modules/menus/menu.php	Gen-4	40
Gen-6	modules/menus/menu.php	Gen-6	151
Gen-7	modules/menus/menu.php	Gen-7	201
Gen-8	modules/menus/menu.php	Gen-8	3
Gen-9	modules/menus/menu.php	Gen-9	5
Gen-12	modules/menus/menu.php	Gen-12	251
Ccl-1	modules/clients/consult_client.php	Ccl-1	25
Ccl-2	modules/clients/consult_client.php	Ccl-2	25
Ccl-3	modules/clients/consult_client.php	Ccl-3	25
Ccl-4	modules/clients/consult_client.php	Ccl-4	25
Ccl-5	modules/clients/consult_client.php	Ccl-5	25
Out-1	modules/misc/logout.php	Out-1	0
Out-2	modules/misc/logout.php	Out-2	0
Out-3	modules/misc/logout.php	Out-2	0
Dcp-1	modules/epargne/depot_compte.php	Dcp-1	75
Dcp-2	modules/epargne/depot_compte.php	Dcp-2	75
Dcp-3	modules/epargne/depot_compte.php	Dcp-3	75
Dcp-4	modules/epargne/depot_compte.php	Dcp-4	75
Gen-10	modules/menus/menu.php	Gen-10	51
Gen-11	modules/menus/menu.php	Gen-11	101
Ado-1	modules/credit/dossier.php	Ado-1	105
Ado-2	modules/credit/dossier.php	Ado-1	105
Ado-3	modules/credit/dossier.php	Ado-2	105
Ado-4	modules/credit/dossier.php	Ado-3	105
Ado-5	modules/credit/dossier.php	Ado-4	105
Ado-6	modules/credit/dossier.php	Ado-5	105
Ado-7	modules/credit/dossier.php	Ado-7	105
Ado-8	modules/credit/dossier.php	Ado-8	105
Ado-9	modules/credit/dossier.php	Ado-9	105
Ado-10	modules/credit/dossier.php	Ado-10	105
Ado-11	modules/credit/dossier.php	Ado-11	105
Apd-1	modules/credit/approbation.php	Apd-1	110
Apd-2	modules/credit/approbation.php	Apd-2	110
Apd-3	modules/credit/approbation.php	Apd-3	110
Apd-4	modules/credit/approbation.php	Apd-4	110
Apd-5	modules/credit/approbation.php	Apd-5	110
Apd-6	modules/credit/approbation.php	Apd-6	110
Apd-7	modules/credit/approbation.php	Apd-7	110
Apd-8	modules/credit/approbation.php	Apd-8	110
Apd-9	modules/credit/approbation.php	Apd-9	110
Apd-10	modules/credit/approbation.php	Apd-10	110
Rfd-1	modules/credit/rejetdossier.php	Rfd-1	115
Rfd-2	modules/credit/rejetdossier.php	Rfd-2	115
Rfd-3	modules/credit/rejetdossier.php	Rfd-3	115
And-1	modules/credit/annul_dossier.php	And-1	120
And-2	modules/credit/annul_dossier.php	And-2	120
And-3	modules/credit/annul_dossier.php	And-3	120
Dbd-1	modules/credit/debourdossier.php	Dbd-1	125
Dbd-2	modules/credit/debourdossier.php	Dbd-2	125
Dbd-3	modules/credit/debourdossier.php	Dbd-3	125
Dbd-4	modules/credit/debourdossier.php	Dbd-4	125
Dbd-5	modules/credit/debourdossier.php	Dbd-10	125
Dbd-6	modules/credit/debourdossier.php	Dbd-6	125
Dbd-7	modules/credit/debourdossier.php	Dbd-7	125
Dbd-8	modules/credit/debourdossier.php	Dbd-8	125
Dbd-9	modules/credit/debourdossier.php	Dbd-9	125
Dbd-10	modules/credit/debourdossier.php	Dbd-3	125
Dbd-11	modules/credit/debourdossier.php	Dbd-11	125
Adp-1	modules/credit/annul_deboursement.php	Adp-1	126
Adp-2	modules/credit/annul_deboursement.php	Adp-2	126
Adp-3	modules/credit/annul_deboursement.php	Adp-3	126
Mdd-1	modules/credit/modifdossier.php	Mdd-1	130
Mdd-2	modules/credit/modifdossier.php	Mdd-2	130
Mdd-3	modules/credit/modifdossier.php	Mdd-3	130
Mdd-4	modules/credit/modifdossier.php	Mdd-4	130
Mdd-5	modules/credit/modifdossier.php	Mdd-5	130
Mdd-6	modules/credit/modifdossier.php	Mdd-6	130
Mdd-7	modules/credit/modifdossier.php	Mdd-7	130
Mdd-8	modules/credit/modifdossier.php	Mdd-8	130
Mdd-9	modules/credit/modifdossier.php	Mdd-9	130
Mdd-10	modules/credit/modifdossier.php	Mdd-10	130
Cdd-1	modules/credit/correctdossier.php	Cdd-1	129
Cdd-2	modules/credit/correctdossier.php	Cdd-2	129
Cdd-3	modules/credit/correctdossier.php	Cdd-3	129
Cdd-4	modules/credit/correctdossier.php	Cdd-4	129
Cdd-5	modules/credit/correctdossier.php	Cdd-5	129
Cdd-6	modules/credit/correctdossier.php	Cdd-6	129
Cdd-7	modules/credit/correctdossier.php	Cdd-7	129
Cdd-8	modules/credit/correctdossier.php	Cdd-8	129
Sdo-1	modules/credit/simulecheancier.php	Sdo-1	135
Sdo-4	modules/credit/simulecheancier.php	Sdo-4	135
Sdo-2	modules/credit/simulecheancier.php	Sdo-2	135
Sdo-3	modules/credit/simulecheancier.php	Sdo-3	135
Cdo-1	modules/credit/consultdossier.php	Cdo-1	140
Cdo-2	modules/credit/consultdossier.php	Cdo-2	140
Cdo-3	modules/credit/consultdossier.php	Cdo-3	140
Cdo-4	modules/credit/consultdossier.php	Cdo-4	140
Cdo-5	modules/credit/consultdossier.php	Cdo-5	140
Cdo-6	modules/credit/consultdossier.php	Cdo-6	140
Cdo-7	modules/credit/consultdossier.php	Cdo-7	140
Rdo-1	modules/credit/reecheldossier.php	Rdo-1	145
Rdo-2	modules/credit/reecheldossier.php	Rdo-2	145
Rdo-3	modules/credit/reecheldossier.php	Rdo-3	145
Rdo-4	modules/credit/reecheldossier.php	Rdo-4	145
Rdo-5	modules/credit/reecheldossier.php	Rdo-5	145
Rdo-6	modules/credit/reecheldossier.php	Rdo-6	145
Rdo-7	modules/credit/reecheldossier.php	Rdo-7	145
Rdo-8	modules/credit/reecheldossier.php	Rdo-8	145
Rdo-9	modules/credit/reecheldossier.php	Rdo-9	145
Rdo-10	modules/credit/reecheldossier.php	Rdo-10	145
Vcp-1	modules/epargne/consult_compte.php	Vcp-1	80
Vcp-2	modules/epargne/consult_compte.php	Vcp-2	80
Vcp-3	modules/epargne/consult_compte.php	Vcp-3	80
Vcp-4	modules/epargne/consult_compte.php	Vcp-4	80
Mcl-1	modules/clients/modif_client.php	Mcl-1	10
Mcl-2	modules/clients/modif_client.php	Mcl-2	10
Rel-1	modules/clients/relations.php	Rel-1	11
Rel-2	modules/clients/relations.php	Rel-2	11
Rel-3	modules/clients/relations.php	Rel-3	11
Rel-4	modules/clients/relations.php	Rel-2	11
Rel-5	modules/clients/relations.php	Rel-3	11
Rel-6	modules/clients/relations.php	Rel-2	11
Rel-7	modules/clients/relations.php	Rel-3	11
Gpf-1	modules/parametrage/profils.php	Gpf	255
Cpf-1	modules/parametrage/profils.php	Cpf-1	257
Apf-1	modules/parametrage/profils.php	Apf-1	256
Apf-2	modules/parametrage/profils.php	Apf-2	256
Mpf-1	modules/parametrage/profils.php	Mpf-1	258
Mpf-2	modules/parametrage/profils.php	Mpf-2	258
Spf-1	modules/parametrage/profils.php	Spf-1	259
Spf-2	modules/parametrage/profils.php	Spf-2	259
Mdp-0	modules/parametrage/motdepasse.php	Mdp-0	260
Mdp-1	modules/parametrage/motdepasse.php	Mdp-1	260
Mdp-2	modules/parametrage/motdepasse.php	Mdp-2	260
Sps-1	modules/clients/souscription_parts.php	Sps-1	20
Sps-2	modules/clients/souscription_parts.php	Sps-2	20
Sps-3	modules/clients/souscription_parts.php	Sps-3	20
Gus-1	modules/parametrage/utilisateurs.php	Gus	265
Aus-1	modules/parametrage/utilisateurs.php	Aus-1	270
Aus-2	modules/parametrage/utilisateurs.php	Aus-2	270
Aus-3	modules/parametrage/utilisateurs.php	Aus-3	270
Cus-1	modules/parametrage/utilisateurs.php	Cus-1	271
Cus-2	modules/parametrage/utilisateurs.php	Cus-2	271
Mus-1	modules/parametrage/utilisateurs.php	Mus-1	272
Mus-2	modules/parametrage/utilisateurs.php	Mus-2	272
Sus-1	modules/parametrage/utilisateurs.php	Sus-1	273
Sus-2	modules/parametrage/utilisateurs.php	Sus-2	273
Ocp-1	modules/epargne/ouvert_compte.php	Ocp-1	53
Ocp-2	modules/epargne/ouvert_compte.php	Ocp-2	53
Ocp-3	modules/epargne/ouvert_compte.php	Ocp-3	53
Ocp-4	modules/epargne/ouvert_compte.php	Ocp-4	53
Ocp-5	modules/epargne/ouvert_compte.php	Ocp-5	53
Ocp-6	modules/epargne/ouvert_compte.php	Ocp-6	53
Glo-1	modules/parametrage/logins.php	Glo	287
Alo-1	modules/parametrage/logins.php	Alo-1	288
Alo-2	modules/parametrage/logins.php	Alo-2	288
Clo-1	modules/parametrage/logins.php	Clo-1	289
Mlo-1	modules/parametrage/logins.php	Mlo-1	290
Mlo-2	modules/parametrage/logins.php	Mlo-2	290
Mlp-1	modules/parametrage/logins.php	Mlp-1	297
Mlp-2	modules/parametrage/logins.php	Mlp-2	297
Slo-1	modules/parametrage/logins.php	Slo-1	291
Slo-2	modules/parametrage/logins.php	Slo-2	291
Rcp-1	modules/epargne/retrait_compte.php	Rcp-1	70
Rcp-2	modules/epargne/retrait_compte.php	Rcp-2	70
Rcp-3	modules/epargne/retrait_compte.php	Rcp-3	70
Rcp-4	modules/epargne/retrait_compte.php	Rcp-4	70
Gta-1	modules/parametrage/tables.php	Sta	292
Gta-2	modules/parametrage/tables.php	Eta	292
Cta-1	modules/parametrage/tables.php	Pta	292
Mta-1	modules/parametrage/tables.php	Pta	292
Mta-2	modules/parametrage/tables.php	Pta	292
Ata-1	modules/parametrage/tables.php	Pta	292
Ata-2	modules/parametrage/tables.php	Pta	292
Loc-1	modules/parametrage/tables.php	Pta	292
Loc-2	modules/parametrage/tables.php	Pta	292
Bil-1	modules/parametrage/tables.php	Pta	292
Bil-2	modules/parametrage/tables.php	Pta	292
Bil-3	modules/parametrage/tables.php	Pta	292
Bil-4	modules/parametrage/tables.php	Pta	292
Loc-3	modules/parametrage/tables.php	Pta	292
Loc-4	modules/parametrage/tables.php	Pta	292
Loc-5	modules/parametrage/tables.php	Pta	292
Cpc-1	modules/parametrage/tables.php	Pta	292
Cpc-2	modules/parametrage/tables.php	Pta	292
Cpc-3	modules/parametrage/tables.php	Pta	292
Cpc-4	modules/parametrage/tables.php	Pta	292
Dta-1	modules/parametrage/tables.php	Pta	298
Dta-2	modules/parametrage/tables.php	Pta	298
Frc-1	modules/parametrage/tables.php	Pta	292
Frc-2	modules/parametrage/tables.php	Pta	292
Frc-3	modules/parametrage/tables.php	Pta	292
Frc-4	modules/parametrage/tables.php	Pta	292
Tcp-1	modules/epargne/transfert_compte.php	Tcp-1	76
Tcp-2	modules/epargne/transfert_compte.php	Tcp-2	76
Tcp-3	modules/epargne/transfert_compte.php	Tcp-3	76
Tcp-4	modules/epargne/transfert_compte.php	Tcp-3	76
Tcp-5	modules/epargne/transfert_compte.php	Tcp-2	76
Dcl-1	modules/clients/defection_client.php	Dcl-1	15
Dcl-2	modules/clients/defection_client.php	Dcl-2	15
Dcl-3	modules/clients/defection_client.php	Dcl-3	15
Dcl-4	modules/clients/defection_client.php	Dcl-3	15
Dcl-5	modules/clients/defection_client.php	Dcl-4	15
Dcl-6	modules/clients/defection_client.php	Dcl-4	15
Dcl-7	modules/clients/defection_client.php	Dcl-5	15
Sdc-1	modules/clients/simul_defection_client.php	Sdc-1	17
Fcl-1	modules/clients/final_defection_client.php	Fcl-1	16
Fcl-2	modules/clients/final_defection_client.php	Fcl-1	16
Fcl-3	modules/clients/final_defection_client.php	Fcl-2	16
Fcl-4	modules/clients/final_defection_client.php	Fcl-2	16
Fcl-5	modules/clients/final_defection_client.php	Fcl-2	16
Ccp-1	modules/epargne/cloture_compte.php	Ccp-1	54
Ccp-2	modules/epargne/cloture_compte.php	Ccp-2	54
Ccp-3	modules/epargne/cloture_compte.php	Ccp-3	54
Ccp-4	modules/epargne/cloture_compte.php	Ccp-3	54
Jgu-1	modules/guichet/ajustement.php	Jgu-1	170
Jgu-2	modules/guichet/ajustement.php	Jgu-2	170
Jgu-3	modules/guichet/ajustement.php	Jgu-3	170
Rfv-1	modules/epargne/recharge_carte.php	Rfv-1	160
Rfv-2	modules/epargne/recharge_carte.php	Rfv-2	160
Rfv-3	modules/epargne/recharge_carte.php	Rfv-3	160
Rfv-4	modules/epargne/recharge_carte.php	Rfv-4	160
Cca-1	modules/guichet/change_cash.php	Cca-1	186
Cca-2	modules/guichet/change_cash.php	Cca-1	186
Cca-3	modules/guichet/change_cash.php	Cca-1	186
Cca-4	modules/guichet/change_cash.php	Cca-2	186
Swi-1	modules/guichet/gestion_net_bank.php	Swi-1	187
Swi-2	modules/guichet/gestion_net_bank.php	Swi-2	187
Swi-3	modules/guichet/gestion_net_bank.php	Swi-3	187
Swi-4	modules/guichet/gestion_net_bank.php	Swi-4	187
Swi-5	modules/guichet/gestion_net_bank.php	Swi-5	187
Swi-6	modules/guichet/gestion_net_bank.php	Swi-6	187
Rcr-1	modules/credit/remboursement.php	Rcr-1	147
Rcr-2	modules/credit/remboursement.php	Rcr-2	147
Rcr-3	modules/credit/remboursement.php	Rcr-3	147
Rcr-4	modules/credit/remboursement.php	Rcr-4	147
Rcr-5	modules/credit/remboursement.php	Rcr-5	147
Rcr-6	modules/credit/remboursement.php	Rcr-6	147
Rga-1	modules/credit/realisationgarantie.php	Rga-1	148
Rga-2	modules/credit/realisationgarantie.php	Rga-2	148
Rga-3	modules/credit/realisationgarantie.php	Rga-3	148
Rga-4	modules/credit/realisationgarantie.php	Rga-4	148
Pen-1	modules/credit/penalites.php	Pen-1	131
Pen-2	modules/credit/penalites.php	Pen-2	131
Pen-3	modules/credit/penalites.php	Pen-3	131
Pen-4	modules/credit/penalites.php	Pen-3	131
Oag-1	modules/systeme/ouverture_agence.php	Oag-1	205
Oag-2	modules/systeme/ouverture_agence.php	Oag-2	205
Oag-3	modules/systeme/ouverture_agence.php	Oag-2	205
Oag-4	modules/systeme/ouverture_agence.php	Oag-1	205
Fag-1	modules/systeme/fermeture_agence.php	Fag-1	206
Fag-2	modules/systeme/fermeture_agence.php	Fag-2	206
Flo-1	modules/systeme/force_logout.php	Flo-1	230
Flo-2	modules/systeme/force_logout.php	Flo-2	230
Flo-3	modules/systeme/force_logout.php	Flo-3	230
Tot-1	modules/misc/timeout.php	Gen-3	0
Pse-1	modules/misc/perte_session.php	Gen-3	0
Scp-1	modules/epargne/simu_arret_cpt.php	Scp-1	55
Scp-2	modules/epargne/simu_arret_cpt.php	Scp-2	55
Fjr-1	modules/systeme/traitements.php	Fjr-1	212
Fjr-2	modules/systeme/traitements.php	Fjr-1	212
Tch-1	modules/epargne/traitement_cheques.php	Tch-1	77
Tch-2	modules/epargne/traitement_cheques.php	Tch-2	77
Tch-3	modules/epargne/traitement_cheques.php	Tch-2	77
Map-1	modules/systeme/modif_autre_pwd.php	Map-1	215
Map-2	modules/systeme/modif_autre_pwd.php	Map-2	215
Agu-1	modules/guichet/approvisionnement_delestage.php	Agu-1	155
Agu-2	modules/guichet/approvisionnement_delestage.php	Agu-2	155
Dgu-1	modules/guichet/approvisionnement_delestage.php	Dgu-1	156
Dgu-2	modules/guichet/approvisionnement_delestage.php	Dgu-2	156
Pdc-1	modules/guichet/operations_div_caisse.php	Pdc-1	189
Pdc-2	modules/guichet/operations_div_caisse.php	Pdc-2	189
Pdc-3	modules/guichet/operations_div_caisse.php	Pdc-3	189
Sgu-1	modules/guichet/saisie_lot.php	Sgu-1	158
Sgu-2	modules/guichet/saisie_lot.php	Sgu-1	158
Sgu-3	modules/guichet/saisie_lot.php	Sgu-2	158
Sgu-4	modules/guichet/saisie_lot.php	Sgu-3	158
Dlf-1	modules/guichet/saisie_fichier_lot.php	Dlf-1	159
Dlf-2	modules/guichet/saisie_fichier_lot.php	Dlf-1	159
Dlf-3	modules/guichet/saisie_fichier_lot.php	Dlf-2	159
Dlf-4	modules/guichet/saisie_fichier_lot.php	Dlf-3	159
Att-1	modules/guichet/attente_credit.php	Att-1	188
Att-2	modules/guichet/attente_credit.php	Att-2	188
Att-3	modules/guichet/attente_credit.php	Att-3	188
Att-4	modules/guichet/attente_credit.php	Att-4	188
Att-5	modules/guichet/attente_credit.php	Att-6	188
Att-6	modules/guichet/attente_credit.php	Att-6	188
Att-7	modules/guichet/attente_credit.php	Att-5	188
Att-8	modules/guichet/attente_credit.php	Att-5	188
Att-9	modules/guichet/attente_credit.php	Att-6	188
Psf-1	modules/guichet/part_social_fichier.php	Psf-1	190
Psf-2	modules/guichet/part_social_fichier.php	Psf-1	190
Psf-3	modules/guichet/part_social_fichier.php	Psf-2	190
Psf-4	modules/guichet/part_social_fichier.php	Psf-3	190
Vjf-1	modules/parametrage/visu_jour_ferie.php	Vjf-1	296
Gen-13	modules/menus/menu.php	Gen-13	301
Gen-14	modules/menus/menu.php	Gen-14	401
Cra-1	modules/rapports/rapports_client.php	Cra-1	310
Cra-2	modules/rapports/rapports_client.php	Cra-2	310
Cra-3	modules/rapports/rapports_client.php	Cra-3	310
Cra-4	modules/rapports/rapports_client.php	Cra-4	310
Cra-7	modules/rapports/rapports_client.php	Cra-3	310
Cra-8	modules/rapports/rapports_client.php	Cra-4	310
Cra-9	modules/rapports/rapports_client.php	Cra-4	310
Cra-10	modules/rapports/rapports_client.php	Cra-2	310
Cra-11	modules/rapports/rapports_client.php	Cra-2	310
Cra-12	modules/rapports/rapports_client.php	Cra-3	310
Cra-13	modules/rapports/rapports_client.php	Cra-4	310
Cra-14	modules/rapports/rapports_client.php	Cra-4	310
Cra-15	modules/rapports/rapports_client.php	Cra-4	310
Cra-16	modules/rapports/rapports_client.php	Cra-2	310
Cra-17	modules/rapports/rapports_client.php	Cra-3	310
Cra-18	modules/rapports/rapports_client.php	Cra-4	310
Cra-19	modules/rapports/rapports_client.php	Cra-1	310
Cra-20	modules/rapports/rapports_client.php	Cra-3	310
Cra-21	modules/rapports/rapports_client.php	Cra-4	310
Kra-1	modules/rapports/rapports_credit.php	Kra-1	350
Kra-10	modules/rapports/rapports_credit.php	Kra-3	350
Kra-11	modules/rapports/rapports_credit.php	Kra-5	350
Kra-12	modules/rapports/rapports_credit.php	Kra-5	350
Kra-13	modules/rapports/rapports_credit.php	Kra-5	350
Kra-20	modules/rapports/rapports_credit.php	Kra-2	350
Kra-21	modules/rapports/rapports_credit.php	Kra-3	350
Kra-22	modules/rapports/rapports_credit.php	Kra-5	350
Kra-30	modules/rapports/rapports_credit.php	Kra-2	350
Kra-31	modules/rapports/rapports_credit.php	Kra-3	350
Kra-32	modules/rapports/rapports_credit.php	Kra-3	350
Kra-33	modules/rapports/rapports_credit.php	Kra-5	350
Kra-34	modules/rapports/rapports_credit.php	Kra-5	350
Kra-35	modules/rapports/rapports_credit.php	Kra-5	350
Kra-40	modules/rapports/rapports_credit.php	Kra-2	350
Kra-41	modules/rapports/rapports_credit.php	Kra-3	350
Kra-42	modules/rapports/rapports_credit.php	Kra-2	350
Kra-43	modules/rapports/rapports_credit.php	Kra-3	350
Kra-44	modules/rapports/rapports_credit.php	Kra-3	350
Kra-45	modules/rapports/rapports_credit.php	Kra-2	350
Kra-46	modules/rapports/rapports_credit.php	Kra-3	350
Kra-47	modules/rapports/rapports_credit.php	Kra-2	350
Kra-48	modules/rapports/rapports_credit.php	Kra-3	350
Kra-49	modules/rapports/rapports_credit.php	Kra-2	350
Kra-50	modules/rapports/rapports_credit.php	Kra-3	350
Kra-51	modules/rapports/rapports_credit.php	Kra-5	350
Kra-53	modules/rapports/rapports_credit.php	Kra-5	350
Kra-52	modules/rapports/rapports_credit.php	Kra-5	350
Kra-54	modules/rapports/rapports_credit.php	Kra-5	350
Kra-56	modules/rapports/rapports_credit.php	Kra-5	350
Kra-58	modules/rapports/rapports_credit.php	Kra-5	350
Kra-60	modules/rapports/rapports_credit.php	Kra-5	350
Kra-61	modules/rapports/rapports_credit.php	Kra-5	350
Kra-62	modules/rapports/rapports_credit.php	Kra-5	350
Kra-65	modules/rapports/rapports_credit.php	Kra-5	350
Kra-64	modules/rapports/rapports_credit.php	Kra-5	350
Kra-66	modules/rapports/rapports_credit.php	Kra-5	350
Kra-67	modules/rapports/rapports_credit.php	Kra-2	350
Kra-68	modules/rapports/rapports_credit.php	Kra-3	350
Kra-69	modules/rapports/rapports_credit.php	Kra-5	350
Kra-70	modules/rapports/rapports_credit.php	Kra-5	350
Kra-71	modules/rapports/rapports_credit.php	Kra-5	350
Kra-72	modules/rapports/rapports_credit.php	Kra-5	350
Kra-73	modules/rapports/rapports_credit.php	Kra-2	350
Kra-74	modules/rapports/rapports_credit.php	Kra-3	350
Kra-75	modules/rapports/rapports_credit.php	Kra-5	350
Kra-76	modules/rapports/rapports_credit.php	Kra-2	350
Kra-77	modules/rapports/rapports_credit.php	Kra-3	350
Kra-78	modules/rapports/rapports_credit.php	Kra-5	350
Kra-79	modules/rapports/rapports_credit.php	Kra-2	350
Kra-80	modules/rapports/rapports_credit.php	Kra-3	350
Kra-81	modules/rapports/rapports_credit.php	Kra-5	350
Kra-82	modules/rapports/rapports_credit.php	Kra-2	350
Kra-83	modules/rapports/rapports_credit.php	Kra-3	350
Kra-84	modules/rapports/rapports_credit.php	Kra-5	350
Kra-85	modules/rapports/rapports_credit.php	Kra-2	350
Kra-86	modules/rapports/rapports_credit.php	Kra-3	350
Kra-87	modules/rapports/rapports_credit.php	Kra-5	350
Kra-88	modules/rapports/rapports_credit.php	Kra-2	350
Kra-89	modules/rapports/rapports_credit.php	Kra-3	350
Kra-90	modules/rapports/rapports_credit.php	Kra-5	350
Kra-91	modules/rapports/rapports_credit.php	Kra-2	350
Kra-92	modules/rapports/rapports_credit.php	Kra-3	350
Kra-93	modules/rapports/rapports_credit.php	Kra-5	350
Era-1	modules/rapports/rapports_epargne.php	Era-1	330
Era-2	modules/rapports/rapports_epargne.php	Era-2	330
Era-3	modules/rapports/rapports_epargne.php	Era-2	330
Era-4	modules/rapports/rapports_epargne.php	Era-3	330
Era-5	modules/rapports/rapports_epargne.php	Era-2	330
Era-6	modules/rapports/rapports_epargne.php	Era-3	330
Era-7	modules/rapports/rapports_epargne.php	Era-2	330
Era-8	modules/rapports/rapports_epargne.php	Era-3	330
Era-9	modules/rapports/rapports_epargne.php	Era-2	330
Era-10	modules/rapports/rapports_epargne.php	Era-3	330
Era-11	modules/rapports/rapports_epargne.php	Era-2	330
Era-12	modules/rapports/rapports_epargne.php	Era-3	330
Era-13	modules/rapports/rapports_epargne.php	Era-2	330
Era-14	modules/rapports/rapports_epargne.php	Era-3	330
Era-15	modules/rapports/rapports_epargne.php	Era-2	330
Era-16	modules/rapports/rapports_epargne.php	Era-3	330
Era-17	modules/rapports/rapports_epargne.php	Era-2	330
Era-18	modules/rapports/rapports_epargne.php	Era-3	330
Era-22	modules/rapports/rapports_epargne.php	Era-2	330
Era-23	modules/rapports/rapports_epargne.php	Era-3	330
Era-24	modules/rapports/rapports_epargne.php	Era-2	330
Era-25	modules/rapports/rapports_epargne.php	Era-3	330
Era-26	modules/rapports/rapports_epargne.php	Era-4	330
Era-27	modules/rapports/rapports_epargne.php	Era-4	330
Era-28	modules/rapports/rapports_epargne.php	Era-4	330
Era-29	modules/rapports/rapports_epargne.php	Era-4	330
Era-30	modules/rapports/rapports_epargne.php	Era-4	330
Era-31	modules/rapports/rapports_epargne.php	Era-4	330
Era-32	modules/rapports/rapports_epargne.php	Era-4	330
Era-33	modules/rapports/rapports_epargne.php	Era-4	330
Era-35	modules/rapports/rapports_epargne.php	Era-2	330
Era-36	modules/rapports/rapports_epargne.php	Era-3	330
Era-37	modules/rapports/rapports_epargne.php	Era-4	330
Era-40	modules/rapports/rapports_epargne.php	Era-2	330
Era-41	modules/rapports/rapports_epargne.php	Era-3	330
Era-42	modules/rapports/rapports_epargne.php	Era-4	330
Era-43	modules/rapports/rapports_epargne.php	Era-2	330
Era-44	modules/rapports/rapports_epargne.php	Era-3	330
Era-45	modules/rapports/rapports_epargne.php	Era-4	330
Era-46	modules/rapports/rapports_epargne.php	Era-2	330
Era-47	modules/rapports/rapports_epargne.php	Era-3	330
Era-48	modules/rapports/rapports_epargne.php	Era-4	330
Era-49	modules/rapports/rapports_epargne.php	Era-2	330
Era-50	modules/rapports/rapports_epargne.php	Era-4	330
Era-51	modules/rapports/rapports_epargne.php	Era-2	330
Era-52	modules/rapports/rapports_epargne.php	Era-3	330
Era-53	modules/rapports/rapports_epargne.php	Era-4	330
Pfh-1	modules/clients/perception_frais.php	Pfh-1	31
Pfh-2	modules/clients/perception_frais.php	Pfh-2	31
Ass-1	modules/clients/assurances.php	Ass-1	19
Ass-2	modules/clients/assurances.php	Ass-2	19
Ass-3	modules/clients/assurances.php	Ass-3	19
Ara-1	modules/rapports/rapports_agence.php	Ara-1	370
Ara-2	modules/rapports/rapports_agence.php	Ara-2	370
Ara-3	modules/rapports/rapports_agence.php	Ara-3	370
Ara-4	modules/rapports/rapports_agence.php	Ara-2	370
Ara-5	modules/rapports/rapports_agence.php	Ara-3	370
Ara-6	modules/rapports/rapports_agence.php	Ara-2	370
Ara-7	modules/rapports/rapports_agence.php	Ara-3	370
Ara-8	modules/rapports/rapports_agence.php	Ara-3	370
Ara-15	modules/rapports/rapports_agence.php	Ara-2	370
Ara-16	modules/rapports/rapports_agence.php	Ara-2	370
Ara-22	modules/rapports/rapports_agence.php	Ara-2	370
Ara-23	modules/rapports/rapports_agence.php	Ara-3	370
Ara-28	modules/rapports/rapports_agence.php	Ara-2	370
Ara-29	modules/rapports/rapports_agence.php	Ara-3	370
Ara-35	modules/rapports/rapports_agence.php	Ara-2	370
Ara-36	modules/rapports/rapports_agence.php	Ara-2	370
Ara-37	modules/rapports/rapports_agence.php	Ara-3	370
Ara-30	modules/rapports/rapports_agence.php	Ara-2	370
Ara-31	modules/rapports/rapports_agence.php	Ara-3	370
Ara-32	modules/rapports/rapports_agence.php	Ara-2	370
Ara-33	modules/rapports/rapports_agence.php	Ara-3	370
Ara-43	modules/rapports/rapports_agence.php	Ara-3	370
Ara-45	modules/rapports/rapports_agence.php	Ara-3	370
Ara-46	modules/rapports/rapports_agence.php	Ara-3	370
Ara-49	modules/rapports/rapports_agence.php	Ara-3	370
Ara-50	modules/rapports/rapports_agence.php	Ara-3	370
Ara-51	modules/rapports/rapports_agence.php	Ara-2	370
Ara-52	modules/rapports/rapports_agence.php	Ara-3	370
Ara-53	modules/rapports/rapports_agence.php	Ara-3	370
Ara-54	modules/rapports/rapports_agence.php	Ara-2	370
Ara-55	modules/rapports/rapports_agence.php	Ara-3	370
Ara-56	modules/rapports/rapports_agence.php	Ara-3	370
Ara-57	modules/rapports/rapports_agence.php	Ara-3	370
Ara-58	modules/rapports/rapports_agence.php	Ara-2	370
Ara-59	modules/rapports/rapports_agence.php	Ara-3	370
Ara-60	modules/rapports/rapports_agence.php	Ara-3	370
Tra-1	modules/compta/rapports_compta.php	Tra-1	430
Tra-2	modules/compta/rapports_compta.php	Tra-2	430
Tra-3	modules/compta/rapports_compta.php	Tra-3	430
Tra-4	modules/compta/rapports_compta.php	Tra-2	430
Tra-5	modules/compta/rapports_compta.php	Tra-3	430
Tra-6	modules/compta/rapports_compta.php	Tra-2	430
Tra-7	modules/compta/rapports_compta.php	Tra-3	430
Tra-8	modules/compta/rapports_compta.php	Tra-2	430
Tra-9	modules/compta/rapports_compta.php	Tra-3	430
Tra-10	modules/compta/rapports_compta.php	Tra-2	430
Tra-11	modules/compta/rapports_compta.php	Tra-3	430
Tra-12	modules/compta/rapports_compta.php	Tra-2	430
Tra-13	modules/compta/rapports_compta.php	Tra-3	430
Tra-14	modules/compta/rapports_compta.php	Tra-2	430
Tra-15	modules/compta/rapports_compta.php	Tra-3	430
Tra-16	modules/compta/rapports_compta.php	Tra-3	430
Tra-17	modules/compta/rapports_compta.php	Tra-3	430
Tra-18	modules/compta/rapports_compta.php	Tra-3	430
Tra-19	modules/compta/rapports_compta.php	Tra-3	430
Tra-20	modules/compta/rapports_compta.php	Tra-3	430
Tra-21	modules/compta/rapports_compta.php	Tra-3	430
Tra-22	modules/compta/rapports_compta.php	Tra-3	430
Tra-23	modules/compta/rapports_compta.php	Tra-3	430
Tra-24	modules/compta/rapports_compta.php	Tra-3	430
Tra-25	modules/compta/rapports_compta.php	Tra-3	430
Tra-26	modules/compta/rapports_compta.php	Tra-3	430
Tra-27	modules/compta/rapports_compta.php	Tra-3	430
Tra-28	modules/compta/rapports_compta.php	Tra-3	430
Tra-29	modules/compta/rapports_compta.php	Tra-3	430
Tra-30	modules/compta/rapports_compta.php	Tra-3	430
Tra-31	modules/compta/rapports_compta.php	Tra-3	430
Tra-32	modules/compta/rapports_compta.php	Tra-3	430
Rad-1	modules/compta/radier_credits.php	Rad-1	475
Rad-2	modules/compta/radier_credits.php	Rad-1	475
Rad-3	modules/compta/radier_credits.php	Rad-2	475
Tva-1	modules/compta/declaration_tva.php	Tva-1	476
Tva-2	modules/compta/declaration_tva.php	Tva-2	476
Tva-3	modules/compta/declaration_tva.php	Tva-3	476
Tva-4	modules/compta/declaration_tva.php	Tva-4	476
Tva-5	modules/compta/declaration_tva.php	Tva-5	476
Trs-1	modules/compta/transaction_ferlo.php	Trs-1	431
Trs-2	modules/compta/transaction_ferlo.php	Trs-1	431
Dra-1	modules/rapports/dernier_rapport.php	Dra-1	399
Rex-1	modules/epargne/retrait_express.php	Rex-1	85
Rex-2	modules/epargne/retrait_express.php	Rex-2	85
Rex-3	modules/epargne/retrait_express.php	Rex-3	85
Dex-1	modules/epargne/depot_express.php	Dex-1	86
Dex-2	modules/epargne/depot_express.php	Dex-2	86
Dex-3	modules/epargne/depot_express.php	Dex-3	86
Aut-1	modules/epargne/recharge_carte.php	Aut-1	81
Aut-2	modules/epargne/recharge_carte.php	Aut-2	81
Aut-3	modules/epargne/recharge_carte.php	Aut-3	81
Aut-4	modules/epargne/recharge_carte.php	Aut-4	81
Sra-1	modules/credit/simulecheancier.php	Sra-1	390
Sra-4	modules/credit/simulecheancier.php	Sra-4	390
Sra-2	modules/credit/simulecheancier.php	Sra-2	390
Sra-3	modules/credit/simulecheancier.php	Sra-3	390
Vgu-1	modules/guichet/transactions.php	Vgu-1	180
Vgu-2	modules/guichet/transactions.php	Vgu-2	180
Vgu-3	modules/guichet/transactions.php	Vgu-3	180
Vtg-1	modules/guichet/transactions.php	Vtg-1	181
Vtg-2	modules/guichet/transactions.php	Vtg-2	181
Vtg-3	modules/guichet/transactions.php	Vtg-3	181
Pda-1	modules/epargne/prolong_dat.php	Pda-1	78
Pda-2	modules/epargne/prolong_dat.php	Pda-2	78
Pda-3	modules/epargne/prolong_dat.php	Pda-2	78
Mdc-1	modules/epargne/modification_comptes.php	Mdc-1	88
Mdc-2	modules/epargne/modification_comptes.php	Mdc-1	88
Mdc-3	modules/epargne/modification_comptes.php	Mdc-3	88
Mdc-4	modules/epargne/modification_comptes.php	Mdc-4	88
Dat-1	modules/systeme/sauvegarde.php	Dat-1	210
Dat-2	modules/systeme/sauvegarde.php	Dat-2	210
Dat-3	modules/systeme/sauvegarde.php	Dat-3	210
Dat-4	modules/systeme/sauvegarde.php	Dat-3	210
Dat-5	modules/systeme/sauvegarde.php	Dat-3	210
Acp-1	modules/systeme/ajustement_solde.php	Acp-1	235
Acp-2	modules/systeme/ajustement_solde.php	Acp-1	235
Acp-3	modules/systeme/ajustement_solde.php	Acp-2	235
Acp-4	modules/systeme/ajustement_solde.php	Acp-2	235
Gli-1	modules/systeme/gestion_licence.php	Gli-1	240
Gli-2	modules/systeme/gestion_licence.php	Gli-2	240
Gli-3	modules/systeme/gestion_licence.php	Gli-3	240
Gli-4	modules/systeme/gestion_licence.php	Gli-2	240
Gli-5	modules/systeme/gestion_licence.php	Gli-3	240
Cdb-1	modules/systeme/consolidation_db.php	Cdb-1	211
Cdb-2	modules/systeme/consolidation_db.php	Cdb-2	211
Ppc-1	modules/compta/plan_comptable.php	Ppc-1	410
Ppc-2	modules/compta/plan_comptable.php	Ppc-2	410
Ppc-3	modules/compta/plan_comptable.php	Ppc-3	410
Ppc-4	modules/compta/plan_comptable.php	Ppc-3	410
Ppc-5	modules/compta/plan_comptable.php	Ppc-5	410
Ppc-6	modules/compta/plan_comptable.php	Ppc-5	410
Ppc-7	modules/compta/plan_comptable.php	Ppc-3	410
Ppc-8	modules/compta/plan_comptable.php	Ppc-3	410
Ppc-9	modules/compta/plan_comptable.php	Ppc-5	410
Ppc-10	modules/compta/plan_comptable.php	Ppc-1	410
Ppc-11	modules/compta/plan_comptable.php	Ppc-1	410
Ppc-12	modules/compta/plan_comptable.php	Ppc-5	410
Gop-1	modules/compta/gestion_operations.php	Gop-1	420
Gop-2	modules/compta/gestion_operations.php	Gop-2	420
Gop-3	modules/compta/gestion_operations.php	Gop-3	420
Gop-4	modules/compta/gestion_operations.php	Gop-2	420
Gop-5	modules/compta/gestion_operations.php	Gop-5	420
Gop-6	modules/compta/gestion_operations.php	Gop-6	420
Gop-7	modules/compta/gestion_operations.php	Gop-7	420
Odc-1	modules/compta/gestion_ODC.php	Odc-1	472
Odc-2	modules/compta/gestion_ODC.php	Odc-2	472
Odc-3	modules/compta/gestion_ODC.php	Odc-3	472
Odc-4	modules/compta/gestion_ODC.php	Odc-4	472
Odc-8	modules/compta/gestion_ODC.php	Odc-8	472
Odc-9	modules/compta/gestion_ODC.php	Odc-9	472
Odc-10	modules/compta/gestion_ODC.php	Odc-10	472
Odc-5	modules/compta/gestion_ODC.php	Odc-3	472
Odc-6	modules/compta/gestion_ODC.php	Odc-6	472
Odc-7	modules/compta/gestion_ODC.php	Odc-3	472
Osa-1	modules/compta/op_siege_agence.php	Osa-1	473
Osa-2	modules/compta/op_siege_agence.php	Osa-2	473
Osa-3	modules/compta/op_siege_agence.php	Osa-3	473
Ano-1	modules/compta/annulation_op_reciproques.php	Ano-1	474
Ano-2	modules/compta/annulation_op_reciproques.php	Ano-2	474
Ano-3	modules/compta/annulation_op_reciproques.php	Ano-3	474
Ano-4	modules/compta/annulation_op_reciproques.php	Ano-4	474
Gel-1	modules/compta/gestion_ecriture_libre.php	Gel-1	478
Gel-2	modules/compta/gestion_ecriture_libre.php	Gel-2	478
Gel-3	modules/compta/gestion_ecriture_libre.php	Gel-3	478
Gel-4	modules/compta/gestion_ecriture_libre.php	Gel-4	478
Gel-5	modules/compta/gestion_ecriture_libre.php	Gel-3	478
Gel-6	modules/compta/gestion_ecriture_libre.php	Gel-6	478
Gel-7	modules/compta/gestion_ecriture_libre.php	Gel-3	478
Ecr-1	modules/compta/operations_div.php	Ecr-1	470
Opd-1	modules/compta/operations_div.php	Opd-1	470
Opd-2	modules/compta/operations_div.php	Opd-2	470
Opd-3	modules/compta/operations_div.php	Opd-3	470
Opd-4	modules/compta/operations_div.php	Opd-4	470
Opd-5	modules/compta/operations_div.php	Opd-5	470
Opd-6	modules/compta/operations_div.php	Opd-6	470
Opd-7	modules/compta/operations_div.php	Opd-7	470
Opd-8	modules/compta/operations_div.php	Opd-8	470
Opd-9	modules/compta/operations_div.php	Opd-9	470
Opa-1	modules/compta/operations_div.php	Opa-1	470
Opa-2	modules/compta/operations_div.php	Opa-2	470
Opa-3	modules/compta/operations_div.php	Opa-3	470
Opa-4	modules/compta/operations_div.php	Opa-4	470
Opa-5	modules/compta/operations_div.php	Opa-5	470
Opa-6	modules/compta/operations_div.php	Opa-6	470
Opa-7	modules/compta/operations_div.php	Opa-7	470
Opa-8	modules/compta/operations_div.php	Opa-8	470
Opa-9	modules/compta/operations_div.php	Opa-9	470
Opa-10	modules/compta/operations_div.php	Opa-10	470
Gex-1	modules/compta/gestion_exercices.php	Gex-1	440
Gex-2	modules/compta/gestion_exercices.php	Gex-2	443
Gex-3	modules/compta/gestion_exercices.php	Gex-3	442
Gex-4	modules/compta/gestion_exercices.php	Gex-4	444
Gex-5	modules/compta/gestion_exercices.php	Gex-5	444
Gex-6	modules/compta/gestion_exercices.php	Gex-4	444
Jou-1	modules/compta/gestion_journaux.php	Jou-1	450
Jou-2	modules/compta/gestion_journaux.php	Jou-2	451
Jou-3	modules/compta/gestion_journaux.php	Jou-3	452
Jou-4	modules/compta/gestion_journaux.php	Jou-4	453
Jou-5	modules/compta/gestion_journaux.php	Jou-5	454
Jou-6	modules/compta/gestion_journaux.php	Jou-6	456
Jou-7	modules/compta/gestion_journaux.php	Jou-2	454
Jou-8	modules/compta/gestion_journaux.php	Jou-3	452
Jou-9	modules/compta/gestion_journaux.php	Jou-5	453
Jou-10	modules/compta/gestion_journaux.php	Jou-10	456
Jou-11	modules/compta/gestion_journaux.php	Jou-11	456
Jou-12	modules/compta/gestion_journaux.php	Jou-12	456
Jou-13	modules/compta/gestion_journaux.php	Jou-13	453
Jou-14	modules/compta/gestion_journaux.php	Jou-13	453
Jou-15	modules/compta/gestion_journaux.php	Jou-13	453
Jou-16	modules/compta/gestion_journaux.php	Jou-13	453
Dev-1	modules/parametrage/devises.php	Dev-1	274
Dev-2	modules/parametrage/devises.php	Dev-1	275
Dev-3	modules/parametrage/devises.php	Dev-1	276
Dev-4	modules/parametrage/devises.php	Dev-1	277
Dev-5	modules/parametrage/devises.php	Dev-1	275
Dev-6	modules/parametrage/devises.php	Dev-1	276
Dev-7	modules/parametrage/devises.php	Dev-1	276
Bdc-1	modules/epargne/bloquer_debloquer_compte.php	Bdc-1	89
Bdc-2	modules/epargne/bloquer_debloquer_compte.php	Bdc-2	89
Bdc-3	modules/epargne/bloquer_debloquer_compte.php	Bdc-2	89
Man-1	modules/epargne/mandats.php	Man-1	90
Man-2	modules/epargne/mandats.php	Man-2	90
Man-3	modules/epargne/mandats.php	Man-3	90
Man-4	modules/epargne/mandats.php	Man-2	90
Man-5	modules/epargne/mandats.php	Man-3	90
Man-6	modules/epargne/mandats.php	Man-2	90
Man-7	modules/epargne/mandats.php	Man-3	90
Ced-1	modules/epargne/activer_compte_dormant.php	Ced-1	91
Ced-2	modules/epargne/activer_compte_dormant.php	Ced-2	91
Ced-3	modules/epargne/activer_compte_dormant.php	Ced-2	91
Abi-1	modules/credit/abattement_interets.php	Abi-1	132
Abi-2	modules/credit/abattement_interets.php	Abi-2	132
Abi-3	modules/credit/abattement_interets.php	Abi-3	132
Abi-4	modules/credit/abattement_interets.php	Abi-3	132
Chq-1	modules/clients/chequiers.php	Chq-1	41
Chq-2	modules/clients/chequiers.php	Chq-2	41
Chq-3	modules/clients/chequiers.php	Chq-3	41
Rch-1	modules/clients/chequiers.php	Rch-1	42
Rch-2	modules/clients/chequiers.php	Rch-2	42
Rch-3	modules/clients/chequiers.php	Rch-2	42
Ext-1	modules/clients/extraits.php	Ext-1	43
Ext-2	modules/clients/extraits.php	Ext-2	43
Ext-3	modules/clients/extraits.php	Ext-1	43
Ext-4	modules/clients/extraits.php	Ext-1	43
Ope-1	modules/epargne/ordres_permanents.php	Ope-1	79
Ope-2	modules/epargne/ordres_permanents.php	Ope-2	79
Ope-3	modules/epargne/ordres_permanents.php	Ope-2	79
Ope-4	modules/epargne/ordres_permanents.php	Ope-2	79
Ope-5	modules/epargne/ordres_permanents.php	Ope-2	79
Ope-6	modules/epargne/ordres_permanents.php	Ope-1	79
Ope-7	modules/epargne/ordres_permanents.php	Ope-1	79
Ope-8	modules/epargne/ordres_permanents.php	Ope-3	79
Rap-1	modules/clients/situation_client.php	Rap-1	44
Rap-2	modules/clients/situation_client.php	Rap-2	44
Rap-3	modules/clients/situation_client.php	Rap-2	44
Pcs-1	modules/compta/provision_credit.php	Pcs-1	432
Pcs-2	modules/compta/provision_credit.php	Pcs-2	432
Pcs-3	modules/compta/provision_credit.php	Pcs-3	432
Pcs-4	modules/compta/provision_credit.php	Pcs-4	433
Pcs-5	modules/compta/provision_credit.php	Pcs-4	433
Pcs-6	modules/compta/provision_credit.php	Pcs-4	433
Pcs-7	modules/compta/provision_credit.php	Pcs-3	433
Pcs-8	modules/compta/provision_credit.php	Pcs-8	432
Rae-1	modules/rapports/jasperreport.php	Rae-1	370 
Rae-2	modules/rapports/jasperreport.php	Rae-2	370 
Rae-3	modules/rapports/jasperreport.php	Rae-2	370 
Gjr-1	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-2	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-3	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-4	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-5	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-6	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-7	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-8	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-9	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-10	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-11	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-12	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-13	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-14	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-15	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-16	modules/parametrage/gestion_jasper.php	Gjr-1	300 
Gjr-17	modules/parametrage/gestion_jasper.php	Gjr-1	300
Gjr-18	modules/parametrage/gestion_jasper.php	Gjr-1	300
Gjr-19	modules/parametrage/gestion_jasper.php	Gjr-1	300
Gjr-20	modules/parametrage/gestion_jasper.php	Gjr-1	300
Gjr-21	modules/parametrage/gestion_jasper.php	Gjr-1	300
Och-1	modules/clients/chequiers.php	Och-1	45 
Och-2	modules/clients/chequiers.php	Och-1	45 
Cex-1	modules/parametrage/champs_extras.php	Cex	281
Cex-2	modules/parametrage/champs_extras.php	Cex	281
Cex-3	modules/parametrage/champs_extras.php	Cex	281
Cex-4	modules/parametrage/champs_extras.php	Cex	281
Cex-5	modules/parametrage/champs_extras.php	Cex	281
Cex-6	modules/parametrage/champs_extras.php	Cex	281
Cex-7	modules/parametrage/champs_extras.php	Cex	281
Ich-1	modules/guichet/chequier_imprimer.php	Ich	191
Ich-2	modules/guichet/chequier_imprimer.php	Ich	191
Ich-3	modules/guichet/chequier_imprimer.php	Ich	191
Ich-4	modules/guichet/chequier_imprimer.php	Ich	191
Kra-94	modules/rapports/rapports_credit.php	Kra-2	350
Kra-95	modules/rapports/rapports_credit.php	Kra-3	350
Kra-96	modules/rapports/rapports_credit.php	Kra-5	350
Mec-1	modules/credit/modechdcr.php	Mec	136
Mdr-1	modules/credit/moddateremb.php	Mdr-1	141
Mdr-2	modules/credit/moddateremb.php	Mdr-1	141
Mdr-3	modules/credit/moddateremb.php	Mdr-1	141
Mdr-4	modules/credit/moddateremb.php	Mdr-1	141
Amd-1	modules/credit/approbdateremb.php	Amd-1	142
Amd-2	modules/credit/approbdateremb.php	Amd-1	142
Amd-3	modules/credit/approbdateremb.php	Amd-1	142
Amd-4	modules/credit/approbdateremb.php	Amd-1	142
Rdc-1	modules/credit/raccourciduree.php	Rdc-1	143
Rdc-2	modules/credit/raccourciduree.php	Rdc-1	143
Rdc-3	modules/credit/raccourciduree.php	Rdc-1	143
Rdc-4	modules/credit/raccourciduree.php	Rdc-1	143
Ard-1	modules/credit/approbraccourciduree.php	Ard-1	144
Ard-2	modules/credit/approbraccourciduree.php	Ard-1	144
Ard-3	modules/credit/approbraccourciduree.php	Ard-1	144
Ard-4	modules/credit/approbraccourciduree.php	Ard-1	144
\.
