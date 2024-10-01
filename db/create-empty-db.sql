PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS "Medlem_Roll";
DROP TABLE IF EXISTS "Medlem";
DROP TABLE IF EXISTS "Betalning";
DROP TABLE IF EXISTS "Roll";
DROP TABLE IF EXISTS "Segling";
DROP TABLE IF EXISTS "Aktie";
DROP TABLE IF EXISTS "Segling_Medlem_Roll";
DROP TABLE IF EXISTS "AuthToken";

DROP TRIGGER IF EXISTS besattning_after_update;
DROP TRIGGER IF EXISTS betalning_after_update;
DROP TRIGGER IF EXISTS segling_after_update;
DROP TRIGGER IF EXISTS roll_after_update;
DROP TRIGGER IF EXISTS aktie_after_update;

DROP INDEX IF EXISTS idx_skeppar_id;
DROP INDEX IF EXISTS idx_batsman_id;
DROP INDEX IF EXISTS idx_kock_id;

CREATE TABLE Medlem (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "fodelsedatum" VARCHAR(10), 
	"fornamn" VARCHAR(50),
	"efternamn" VARCHAR(100) NOT NULL,
	"gatuadress" VARCHAR(100),
	"postnummer" VARCHAR(10),
	"postort" VARCHAR(50),
	"mobil" VARCHAR(20),
	"telefon" VARCHAR(20),
	"email" VARCHAR(50) UNIQUE,
	"kommentar" VARCHAR(500),
  "godkant_gdpr" BOOLEAN,
  "pref_kommunikation" BOOLEAN,
  "password" VARCHAR(50),
  "isAdmin" BOOLEAN DEFAULT 0, 
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Betalning (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "medlem_id" INTEGER, 
	"belopp" DECIMAL NOT NULL,
  "datum" DATE, 
  "avser_ar" INT NOT NULL,
	"kommentar" VARCHAR(200),
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (medlem_id) REFERENCES Medlem(id) ON DELETE CASCADE
);

CREATE TABLE Aktie (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "medlem_id" INTEGER, 
    "aktie_nummer" INTEGER NOT NULL,
	"kommentar" VARCHAR(200),
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medlem_id) REFERENCES Medlem(id) ON DELETE SET NULL
);

CREATE TABLE Roll (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"roll_namn" VARCHAR(50) NOT NULL,
	"kommentar" VARCHAR(100),
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Medlem_Roll (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "medlem_id" INTEGER REFERENCES Medlem(id) ON DELETE CASCADE,
  "roll_id" INTEGER REFERENCES Roll(id) ON DELETE CASCADE,  
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(medlem_id, roll_id)
);

CREATE TABLE Segling (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "startdatum" DATE NOT NULL,
    "slutdatum" DATE NOT NULL, 
    "skeppslag" VARCHAR(100) NOT NULL,
    "kommentar" VARCHAR(500),
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Segling_Medlem_Roll (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "segling_id" INTEGER REFERENCES Segling(id) ON DELETE CASCADE,
    "medlem_id" INTEGER REFERENCES Medlem(id) ON DELETE SET NULL,
    "roll_id" INTEGER REFERENCES Roll(id) ON DELETE SET NULL,
    "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for faster queries
CREATE INDEX idx_smr_segling_id ON Segling_Medlem_Roll(segling_id);
CREATE INDEX idx_smr_medlem_id ON Segling_Medlem_Roll(medlem_id);
CREATE INDEX idx_smr_roll_id ON Segling_Medlem_Roll(roll_id);


CREATE TABLE AuthToken (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    token_type VARCHAR(16) NOT NULL,
    password_hash VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

--TRIGGERS TO AUTO-UPDATE updated_at
CREATE TRIGGER medlem_after_update 
AFTER UPDATE ON Medlem
FOR EACH ROW
BEGIN
  UPDATE Medlem SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TRIGGER betalning_after_update 
AFTER UPDATE ON Betalning
FOR EACH ROW
BEGIN
  UPDATE Betalning SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TRIGGER segling_after_update 
AFTER UPDATE ON Segling
FOR EACH ROW
BEGIN
  UPDATE Segling SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TRIGGER roll_after_update 
AFTER UPDATE ON Roll
FOR EACH ROW
BEGIN
  UPDATE Roll SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TRIGGER aktie_after_update 
AFTER UPDATE ON Aktie
FOR EACH ROW
BEGIN
  UPDATE Aktie SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

INSERT INTO Roll (roll_namn, kommentar) 
VALUES 
    ('Skeppare', NULL),
    ('Båtsman', NULL),
    ('Kock', 'Är magens bästa vän'),
    ('Styrman', NULL),
    ('Underhåll', NULL);

PRAGMA foreign_keys = ON;