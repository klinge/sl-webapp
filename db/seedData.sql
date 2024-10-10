
--
--INSERT SOME TEST DATA
--

INSERT INTO Medlem (fodelsedatum, fornamn, efternamn, email, mobil, godkant_gdpr, pref_kommunikation, foretag, standig_medlem, skickat_valkomstbrev, password, isAdmin) 
VALUES 
  ('1965-04-19', 'Johan', 'Klinge', 'johan@dev.null', '070-123456', '1', '1', 0, 0, 1, '$2y$10$cPJspNy1ar8ARNYlgUehvuJ6Z1P.Jq.iqGjz97k7aUwW4d6zVkN4S', '1'),
  ('1212-12-12', 'Måns', 'Klinge', 'mans@dev.null', '', '0', '1', 0, 0, 0, '', '0'),
  ('1212-12-12', 'Emma', 'Klinge', 'emma@dev.null', '', '1', '0', 0, 0, 0, '', '0'),
  ('1212-12-12', 'Anders', 'Jansson', 'anders@dev.null', '074-654321', '1', '1', 0, 1, 0, '$2y$10$WJrBbzgtnfwBXwlTznc2yegfDXWxHw7ReWmyVQK9DO0W4o4IEoHlS', '1'),
  ('1212-12-12', 'Medlem', 'Medlemsson', 'medlem@dev.null', '', '1', '1', 1, 0, 0, '', '0');

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

INSERT INTO Aktie (medlem_id, aktie_nummer, kommentar)
VALUES
    (4, 100, "Anders aktie"),
    (4, 121, "Anders andra aktie");
    
