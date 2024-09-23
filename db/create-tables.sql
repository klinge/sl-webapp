PRAGMA foreign_keys = OFF;

DROP TABLE IF EXISTS "Medlem_Roll";
DROP TABLE IF EXISTS "Medlem";
DROP TABLE IF EXISTS "Betalning";
DROP TABLE IF EXISTS "Roll";
DROP TABLE IF EXISTS "Segling";
DROP TABLE IF EXISTS "Segling_Medlem_Roll";
DROP TABLE IF EXISTS "AuthToken";

DROP TRIGGER IF EXISTS besattning_after_update;
DROP TRIGGER IF EXISTS betalning_after_update;
DROP TRIGGER IF EXISTS segling_after_update;
DROP TRIGGER IF EXISTS roll_after_update;

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

--
--INSERT SOME TEST DATA
--

INSERT INTO Medlem (fodelsedatum, fornamn, efternamn, email, mobil, godkant_gdpr, pref_kommunikation, password, isAdmin) 
VALUES 
  ('1965-04-19', 'Johan', 'Klinge', 'johan@dev.null', '070-123456', '1', '1', '$2y$10$cPJspNy1ar8ARNYlgUehvuJ6Z1P.Jq.iqGjz97k7aUwW4d6zVkN4S', '1'),
  ('1212-12-12', 'Måns', 'Klinge', 'mans@dev.null', '', '0', '1', '', '0'),
  ('1212-12-12', 'Emma', 'Klinge', 'emma@dev.null', '', '1', '0', '', '0'),
  ('1212-12-12', 'Anders', 'Jansson', 'anders@dev.null', '074-654321', '1', '1', '$2y$10$WJrBbzgtnfwBXwlTznc2yegfDXWxHw7ReWmyVQK9DO0W4o4IEoHlS', '1'),
  ('1212-12-12', 'Medlem', 'Medlemsson', 'medlem@dev.null', '', '1', '1', '', '0');

INSERT INTO Betalning (medlem_id, belopp, datum, avser_ar, kommentar) 
VALUES 
    (1, 300, '2024-06-30', 2024, "Här är en kommentar"),
    (1, 300, '2024-12-24', 2023, "Försenad inbetalning för 2023"),
    (2, 300, '2024-05-01', 2024, ""),
    (4, 400, '2024-01-21', 2024, "Medlemsavgift och 100 kr donation"),
    (5, 300, '2024-09-09', 2024, "Medlemsavgift");

INSERT INTO Roll (roll_namn, kommentar) 
VALUES 
    ('Skeppare', NULL),
    ('Båtsman', NULL),
    ('Kock', 'Är magens bästa vän'),
    ('Styrman', NULL),
    ('Underhåll', NULL);


INSERT INTO Medlem_Roll (medlem_id, roll_id)
VALUES
    (1, 2),
    (2, 2),
    (2, 3),
    (3, 2), 
    (3, 5),
    (4, 1),
    (4, 4);

INSERT INTO Segling (startdatum, slutdatum, skeppslag, kommentar) 
VALUES 
    ('2024-05-01', '2024-05-03', 'Grundkännarna', "Jag har en kommentar"),
    ('2024-06-21', '2024-06-25', 'Slöseglarna', NULL),
    ('2024-05-15', '2024-05-18', 'Medvindarna', NULL);

INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id)
VALUES
    (1, 1, 1),
    (1, 2, 2),
    (1, 3, 3),
    (1, 5, null),
    (2, 1, 1), 
    (2, 2, 2),
    (2, 3, 4),
    (3, 1, 3),
    (3, 2, 2),
    (3, 3, 1);

PRAGMA foreign_keys = ON;