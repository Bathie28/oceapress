-- ============================================================
-- OceaPress — Script SQL MySQL
-- Base de données issue du MLD corrigé
-- Compatibilité : MySQL 8.0.16+ (CHECK appliqués)
-- ============================================================

CREATE DATABASE IF NOT EXISTS oceapress
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE oceapress;

-- ATTENTION : pour réinitialiser une base existante, supprimer les tables
-- manuellement dans l'ordre inverse des dépendances. Les DROP ne sont
-- volontairement pas exécutés par ce script afin de protéger les données.

CREATE TABLE UTILISATEUR (
    idUtilisateur INT AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telephone VARCHAR(30) NULL,
    motDePasse VARCHAR(255) NOT NULL,
    CONSTRAINT pk_utilisateur PRIMARY KEY (idUtilisateur),
    CONSTRAINT uq_utilisateur_email UNIQUE (email)
) ENGINE=InnoDB;

CREATE TABLE PRESSING (
    idPressing INT AUTO_INCREMENT,
    nom VARCHAR(150) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    telephone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    dateCreation DATE NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'ACTIF',
    modeTarification VARCHAR(10) NOT NULL DEFAULT 'PIECE',
    CONSTRAINT pk_pressing PRIMARY KEY (idPressing),
    CONSTRAINT chk_pressing_statut
        CHECK (statut IN ('ACTIF', 'INACTIF', 'FERME')),
    CONSTRAINT chk_pressing_mode
        CHECK (modeTarification IN ('PIECE', 'POIDS'))
) ENGINE=InnoDB;

CREATE TABLE TARIF (
    idTarif INT AUTO_INCREMENT,
    typeArticle VARCHAR(100) NULL,
    formule VARCHAR(30) NOT NULL,
    unite VARCHAR(10) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    idPressing INT NOT NULL,
    CONSTRAINT pk_tarif PRIMARY KEY (idTarif),
    CONSTRAINT chk_tarif_formule
        CHECK (formule IN ('LAVAGE', 'REPASSAGE', 'LAVAGE_REPASSAGE')),
    CONSTRAINT chk_tarif_unite
        CHECK (unite IN ('PIECE', 'KG')),
    CONSTRAINT chk_tarif_prix
        CHECK (prix >= 0),
    CONSTRAINT chk_tarif_coherence
        CHECK ((unite = 'PIECE' AND typeArticle IS NOT NULL)
            OR (unite = 'KG' AND typeArticle IS NULL)),
    CONSTRAINT fk_tarif_pressing
        FOREIGN KEY (idPressing) REFERENCES PRESSING(idPressing)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_tarif_pressing_formule (idPressing, formule, unite)
) ENGINE=InnoDB;

-- Les identifiants des sous-types sont aussi des FK vers UTILISATEUR.
-- Ils reprennent l'identifiant parent : pas d'AUTO_INCREMENT ici.
CREATE TABLE CLIENT (
    idClient INT,
    dateInscription DATE NOT NULL,
    CONSTRAINT pk_client PRIMARY KEY (idClient),
    CONSTRAINT fk_client_utilisateur
        FOREIGN KEY (idClient) REFERENCES UTILISATEUR(idUtilisateur)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE EMPLOYE (
    idEmploye INT,
    fonction VARCHAR(100) NOT NULL,
    dateEmbauche DATE NULL,
    idPressing INT NOT NULL,
    CONSTRAINT pk_employe PRIMARY KEY (idEmploye),
    CONSTRAINT fk_employe_utilisateur
        FOREIGN KEY (idEmploye) REFERENCES UTILISATEUR(idUtilisateur)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_employe_pressing
        FOREIGN KEY (idPressing) REFERENCES PRESSING(idPressing)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE ADMINISTRATEUR (
    idAdministrateur INT,
    CONSTRAINT pk_administrateur PRIMARY KEY (idAdministrateur),
    CONSTRAINT fk_administrateur_utilisateur
        FOREIGN KEY (idAdministrateur) REFERENCES UTILISATEUR(idUtilisateur)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE COMMANDE (
    idCommande INT AUTO_INCREMENT,
    referenceCommande VARCHAR(100) NOT NULL,
    dateCreation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datePrevue DATE NULL,
    dateRecuperation DATETIME NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'EN_ATTENTE',
    montantTotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    formule VARCHAR(30) NOT NULL DEFAULT 'LAVAGE',
    poids DECIMAL(8,3) NULL,
    idPressing INT NOT NULL,
    idClient INT NOT NULL,
    CONSTRAINT pk_commande PRIMARY KEY (idCommande),
    CONSTRAINT uq_commande_reference UNIQUE (referenceCommande),
    CONSTRAINT chk_commande_formule
        CHECK (formule IN ('LAVAGE', 'REPASSAGE', 'LAVAGE_REPASSAGE')),
    CONSTRAINT chk_commande_poids
        CHECK (poids IS NULL OR poids > 0),
    CONSTRAINT chk_commande_statut
        CHECK (statut IN (
            'EN_ATTENTE', 'EN_TRAITEMENT', 'PRETE', 'RECUPEREE', 'ANNULEE'
        )),
    CONSTRAINT fk_commande_pressing
        FOREIGN KEY (idPressing) REFERENCES PRESSING(idPressing)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_commande_client
        FOREIGN KEY (idClient) REFERENCES CLIENT(idClient)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_commande_pressing_statut (idPressing, statut)
) ENGINE=InnoDB;

CREATE TABLE HISTORIQUE_COMMANDE (
    idHistorique INT AUTO_INCREMENT,
    ancienStatut VARCHAR(40) NULL,
    nouveauStatut VARCHAR(40) NOT NULL,
    dateChangement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT NULL,
    idCommande INT NOT NULL,
    idEmploye INT NOT NULL,
    CONSTRAINT pk_historique_commande PRIMARY KEY (idHistorique),
    CONSTRAINT chk_historique_ancien_statut
        CHECK (
            ancienStatut IS NULL OR ancienStatut IN (
                'EN_ATTENTE', 'EN_TRAITEMENT', 'PRETE', 'RECUPEREE', 'ANNULEE'
            )
        ),
    CONSTRAINT chk_historique_nouveau_statut
        CHECK (nouveauStatut IN (
            'EN_ATTENTE', 'EN_TRAITEMENT', 'PRETE', 'RECUPEREE', 'ANNULEE'
        )),
    CONSTRAINT fk_historique_commande
        FOREIGN KEY (idCommande) REFERENCES COMMANDE(idCommande)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historique_employe
        FOREIGN KEY (idEmploye) REFERENCES EMPLOYE(idEmploye)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_historique_commande_date (idCommande, dateChangement)
) ENGINE=InnoDB;

CREATE TABLE ARTICLE (
    idArticle INT AUTO_INCREMENT,
    typeArticle VARCHAR(100) NOT NULL,
    description TEXT NULL,
    couleur VARCHAR(60) NULL,
    etatInitial VARCHAR(100) NULL,
    etatFinal VARCHAR(100) NULL,
    prixUnitaire DECIMAL(10,2) NULL,
    quantite INT NOT NULL DEFAULT 1,
    idCommande INT NOT NULL,
    CONSTRAINT pk_article PRIMARY KEY (idArticle),
    CONSTRAINT chk_article_quantite
        CHECK (quantite >= 1),
    CONSTRAINT fk_article_commande
        FOREIGN KEY (idCommande) REFERENCES COMMANDE(idCommande)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE QR_CODE (
    idQRCode INT AUTO_INCREMENT,
    token VARCHAR(255) NOT NULL,
    dateCreation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    idCommande INT NOT NULL,
    -- NULL pour un QR inactif ; idCommande pour un QR actif.
    activeCommandeId INT
        GENERATED ALWAYS AS (
            CASE WHEN actif = TRUE THEN idCommande ELSE NULL END
        ) STORED,
    CONSTRAINT pk_qr_code PRIMARY KEY (idQRCode),
    CONSTRAINT uq_qr_code_token UNIQUE (token),
    CONSTRAINT uq_qr_code_commande_active UNIQUE (activeCommandeId),
    CONSTRAINT fk_qr_code_commande
        FOREIGN KEY (idCommande) REFERENCES COMMANDE(idCommande)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE FACTURE (
    idFacture INT AUTO_INCREMENT,
    numeroFacture VARCHAR(100) NOT NULL,
    dateEmission DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montantTotal DECIMAL(10,2) NOT NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'EN_ATTENTE',
    idCommande INT NOT NULL,
    CONSTRAINT pk_facture PRIMARY KEY (idFacture),
    CONSTRAINT uq_facture_numero UNIQUE (numeroFacture),
    CONSTRAINT uq_facture_commande UNIQUE (idCommande),
    CONSTRAINT chk_facture_statut
        CHECK (statut IN ('EN_ATTENTE', 'PAYEE', 'ANNULEE')),
    CONSTRAINT fk_facture_commande
        FOREIGN KEY (idCommande) REFERENCES COMMANDE(idCommande)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE PAIEMENT (
    idPaiement INT AUTO_INCREMENT,
    referencePaiement VARCHAR(190) NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    datePaiement DATETIME NULL,
    methode VARCHAR(40) NOT NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'EN_ATTENTE',
    idFacture INT NOT NULL,
    CONSTRAINT pk_paiement PRIMARY KEY (idPaiement),
    CONSTRAINT uq_paiement_reference UNIQUE (referencePaiement),
    CONSTRAINT chk_paiement_methode
        CHECK (methode IN ('WAVE', 'ORANGE_MONEY')),
    CONSTRAINT chk_paiement_statut
        CHECK (statut IN ('EN_ATTENTE', 'CONFIRME', 'ECHOUE')),
    CONSTRAINT fk_paiement_facture
        FOREIGN KEY (idFacture) REFERENCES FACTURE(idFacture)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE NOTIFICATION (
    idNotification INT AUTO_INCREMENT,
    titre VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    canal VARCHAR(30) NOT NULL DEFAULT 'IN_APP',
    dateEnvoi DATETIME NULL,
    lu BOOLEAN NOT NULL DEFAULT FALSE,
    idClient INT NOT NULL,
    CONSTRAINT pk_notification PRIMARY KEY (idNotification),
    CONSTRAINT chk_notification_canal
        CHECK (canal IN ('EMAIL', 'IN_APP')),
    CONSTRAINT fk_notification_client
        FOREIGN KEY (idClient) REFERENCES CLIENT(idClient)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE ANOMALIE (
    idAnomalie INT AUTO_INCREMENT,
    type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    dateSignalement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    idArticle INT NOT NULL,
    CONSTRAINT pk_anomalie PRIMARY KEY (idAnomalie),
    CONSTRAINT fk_anomalie_article
        FOREIGN KEY (idArticle) REFERENCES ARTICLE(idArticle)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE PHOTO (
    idPhoto INT AUTO_INCREMENT,
    urlPhoto VARCHAR(500) NOT NULL,
    datePrise DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    type VARCHAR(60) NOT NULL,
    idArticle INT NOT NULL,
    CONSTRAINT pk_photo PRIMARY KEY (idPhoto),
    CONSTRAINT fk_photo_article
        FOREIGN KEY (idArticle) REFERENCES ARTICLE(idArticle)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE RESTITUTION (
    idRestitution INT AUTO_INCREMENT,
    dateRestitution DATETIME NULL,
    statut VARCHAR(40) NOT NULL DEFAULT 'EN_ATTENTE',
    observation TEXT NULL,
    idCommande INT NOT NULL,
    idEmploye INT NOT NULL,
    CONSTRAINT pk_restitution PRIMARY KEY (idRestitution),
    CONSTRAINT uq_restitution_commande UNIQUE (idCommande),
    CONSTRAINT chk_restitution_statut
        CHECK (statut IN ('CONFORME', 'NON_CONFORME', 'EN_ATTENTE')),
    CONSTRAINT fk_restitution_commande
        FOREIGN KEY (idCommande) REFERENCES COMMANDE(idCommande)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_restitution_employe
        FOREIGN KEY (idEmploye) REFERENCES EMPLOYE(idEmploye)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Fin du script. Vérifier l'exécution sur MySQL 8.0.16+.
