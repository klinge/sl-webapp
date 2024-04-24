DROP TABLE IF EXISTS "Medlem_Roll";
DROP TABLE IF EXISTS "Medlem";
DROP TABLE IF EXISTS "Roll";
DROP TABLE IF EXISTS "Segling";
DROP TABLE IF EXISTS "Segling_Medlem_Roll";

DROP TRIGGER IF EXISTS besattning_after_update;
DROP TRIGGER IF EXISTS segling_after_update;
DROP TRIGGER IF EXISTS roll_after_update;

DROP INDEX IF EXISTS idx_skeppar_id;
DROP INDEX IF EXISTS idx_batsman_id;
DROP INDEX IF EXISTS idx_kock_id;

CREATE TABLE Medlem (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"fornamn" VARCHAR(50) NOT NULL,
	"efternamn" VARCHAR(100) NOT NULL,
	"gatuadress" VARCHAR(100) NULL,
	"postnummer" VARCHAR(10) NULL,
	"postort" VARCHAR(50) NULL,
	"mobil" VARCHAR(20) NULL,
	"telefon" VARCHAR(20) NULL,
	"email" VARCHAR(50) NULL,
--	"skeppare" TINYINT NULL DEFAULT "0",
--	"kock" TINYINT NULL DEFAULT "0",
--	"batsman" TINYINT NULL DEFAULT "0",
--	"styrman" TINYINT NULL DEFAULT "0",
	"kommentar" VARCHAR(500) NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Roll (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"roll_namn" VARCHAR(50) NOT NULL,
	"kommentar" VARCHAR(100) NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Medlem_Roll (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "medlem_id" INTEGER REFERENCES Medlem(id) ON DELETE CASCADE,
  "roll_id" INTEGER REFERENCES Roll(id) ON DELETE CASCADE,  
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Segling (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "startdatum" DATE NOT NULL,
    "slutdatum" DATE NOT NULL, 
    "skeppslag" VARCHAR(100) NOT NULL,
    "skeppar_id" INTEGER REFERENCES Medlem(id) ON DELETE SET NULL,
    "styrman_id" INTEGER REFERENCES Medlem(id) ON DELETE SET NULL,
    "batsman_id" INTEGER REFERENCES Medlem(id) ON DELETE SET NULL,
    "batsman_extra_id" INTEGER REFERENCES Medlem(id) ON DELETE SET NULL,
    "kock_id" INTEGER REFERENCES Medlem(id) ON DELETE SET NULL,
    "kock_extra_id" INTEGER REFERENCES Medlem(id)ON DELETE SET NULL,
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

--INDEXES ON FOREIGN KEYS
CREATE INDEX idx_skeppar_id ON Segling(skeppar_id);
CREATE INDEX idx_batsman_id ON Segling(batsman_id);
CREATE INDEX idx_kock_id ON Segling(kock_id);

--TRIGGERS TO AUTO-UPDATE updated_at
CREATE TRIGGER medlem_after_update 
AFTER UPDATE ON Medlem
FOR EACH ROW
BEGIN
  UPDATE Medlem SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
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

--INSERT SOME TEST DATA

INSERT INTO Medlem (fornamn, efternamn, email) 
VALUES 
    ('Johan', 'Klinge', 'johan@dev.null'),
    ('Måns', 'Klinge', 'mans@dev.null'),
    ('Emma', 'Klinge', 'emma@dev.null'),
    ('Anders', 'Jansson', 'anders@test.nu');

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

INSERT INTO Segling (startdatum, slutdatum, skeppslag, skeppar_id, batsman_id, kock_id, kommentar) 
VALUES 
    ('2024-05-01', '2024-05-03', 'Grundkännarna', 4, 1, NULL, "Jag har en kommentar"),
    ('2024-06-21', '2024-06-25', 'Slöseglarna', 4, 2, 3, NULL),
    ('2024-05-15', '2024-05-18', 'Medvindarna', 3, 1, 2, NULL);

INSERT INTO Segling_Medlem_Roll (segling_id, medlem_id, roll_id)
VALUES
    (1, 1, 1),
    (1, 2, 2),
    (1, 3, 3),
    (2, 1, 1), 
    (2, 2, 2),
    (2, 3, 4),
    (3, 1, 3),
    (3, 2, 2),
    (3, 3, 1);
