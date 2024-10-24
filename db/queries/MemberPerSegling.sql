-- SQLite

SELECT smr.medlem_id, s.id as segling_id, r.roll_namn, s.skeppslag, s.startdatum
FROM Segling_Medlem_Roll smr
INNER JOIN Segling s ON s.id = smr.segling_id
LEFT JOIN Roll r ON r.id = smr.roll_id
WHERE smr.medlem_id = 5
ORDER BY s.startdatum DESC
LIMIT 10;
