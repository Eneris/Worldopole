Use the following SQL-Statements to create the new table:
=========================================================

--
-- Table structure for table `gymhistory`
--
CREATE TABLE IF NOT EXISTS `gymhistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gym_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `team_id` smallint(6) NOT NULL,
  `guard_pokemon_id` smallint(6) NOT NULL,
  `gym_points` int(11) NOT NULL DEFAULT '0',
  `last_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pokemon_uids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gym_id` (`gym_id`),
  KEY `gym_points` (`gym_points`),
  KEY `last_modified` (`last_modified`),
  KEY `team_id` (`team_id`),
  KEY `last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Add inital dataset for table `gymhistory`
--
INSERT INTO `gymhistory`
  (
    SELECT NULL, g.gym_id, g.team_id, g.guard_pokemon_id, g.gym_points, g.last_modified, g.last_modified as last_updated,
    (
      SELECT GROUP_CONCAT(DISTINCT pokemon_uid SEPARATOR ',')
      FROM gymmember AS gm
      WHERE gm.gym_id = g.gym_id GROUP BY gym_id
    ) AS pokemon_uids
    FROM gym AS g
  );


Use the following SQL-Statements to create the event to update the new table:
=============================================================================

--
-- Create event `gymhistory_update`
--
DELIMITER //
CREATE EVENT IF NOT EXISTS `gymhistory_update`
ON SCHEDULE EVERY 20 SECOND
DO BEGIN
  INSERT INTO gymhistory (SELECT NULL, g.gym_id, g.team_id, g.guard_pokemon_id, g.gym_points, g.last_modified, g.last_modified as last_updated, (SELECT GROUP_CONCAT(DISTINCT pokemon_uid SEPARATOR ',') FROM gymmember AS gm WHERE gm.gym_id = g.gym_id GROUP BY gym_id) AS pokemon_uids FROM gym AS g WHERE g.last_modified > (SELECT MAX(last_modified) FROM gymhistory));
  UPDATE gymhistory AS gh
  JOIN (SELECT gym_id, MAX(last_modified) as max_last_modified FROM gymhistory GROUP BY gym_id)
  AS gg ON gh.gym_id = gg.gym_id AND gh.last_modified = gg.max_last_modified
  JOIN (SELECT gym_id, last_scanned, GROUP_CONCAT(DISTINCT pokemon_uid SEPARATOR ',') AS pokemon_uids FROM gymmember AS gm GROUP BY gym_id)
  AS gm ON gh.gym_id = gm.gym_id
  SET gh.last_updated = gm.last_scanned, gh.pokemon_uids = gm.pokemon_uids
  WHERE gh.last_updated < gm.last_scanned;
END
//
DELIMITER ;

--
-- Enable MySQL event scheduler
--
SET GLOBAL event_scheduler = ON;



Use the following SQL-Statement to create the new table for gymshaving:
========================================================================

--
-- Create and fill table `gymshaving`
--

DROP TABLE IF EXISTS gymshaving;
CREATE TABLE `gymshaving` AS (SELECT gym_id, name, team_id, MAX(last_modified_end) as last_modified_end, MAX(gym_points_end) AS gym_points_end, MIN(last_modified_start) AS last_modified_start, MAX(gym_points_start) AS gym_points_start, pokemon_uids_end, pokemon_uids_start FROM (SELECT gym_after.gym_id, gym_details.name, gym_after.team_id, MAX(gym_after.last_modified) AS last_modified_end, MAX(gym_after.gym_points) AS gym_points_end, MIN(gym_before.last_modified) AS last_modified_start, MAX(gym_before.gym_points) AS gym_points_start, gym_after.pokemon_uids AS pokemon_uids_end, gym_before.pokemon_uids AS pokemon_uids_start
FROM (SELECT * FROM gymhistory WHERE gym_points > 4000 AND team_id > 0) AS gym_middle
JOIN gymhistory AS gym_before
ON gym_middle.gym_id = gym_before.gym_id AND gym_middle.team_id = gym_before.team_id AND (gym_before.gym_points-gym_middle.gym_points) >= 1000 AND gym_middle.last_modified > gym_before.last_modified AND gym_middle.last_modified < (gym_before.last_modified + INTERVAL 5 MINUTE) AND LENGTH(gym_middle.pokemon_uids) < LENGTH(gym_before.pokemon_uids) AND LENGTH(gym_middle.pokemon_uids) > LENGTH(gym_before.pokemon_uids)-24
JOIN gymhistory AS gym_after
ON gym_middle.gym_id = gym_after.gym_id AND gym_middle.team_id = gym_after.team_id AND (gym_after.gym_points-gym_middle.gym_points) >= 1000 AND gym_middle.last_modified < gym_after.last_modified AND gym_middle.last_modified > (gym_after.last_modified - INTERVAL 8 MINUTE) AND LENGTH(gym_middle.pokemon_uids) < LENGTH(gym_after.pokemon_uids) AND LENGTH(gym_middle.pokemon_uids) > LENGTH(gym_after.pokemon_uids)-24 AND LENGTH(gym_before.pokemon_uids) > LENGTH(gym_after.pokemon_uids)-5 AND LENGTH(gym_before.pokemon_uids) < LENGTH(gym_after.pokemon_uids)+5
JOIN gymdetails AS gym_details
ON gym_after.gym_id = gym_details.gym_id
GROUP BY gym_after.gym_id, gym_after.last_modified, gym_after.pokemon_uids, gym_before.pokemon_uids)
AS gym_shaving GROUP BY last_modified_start);


Use the following SQL-Statement to create the event to update the gymshaving table:
====================================================================================

--
-- Create event `gymshaving_update`
--

DELIMITER //
CREATE EVENT IF NOT EXISTS `gymshaving_update`
ON SCHEDULE EVERY 30 MINUTE
DO BEGIN
  INSERT INTO gymshaving (SELECT gym_id, name, team_id, MAX(last_modified_end) as last_modified_end, MAX(gym_points_end) AS gym_points_end, MIN(last_modified_start) AS last_modified_start, MAX(gym_points_start) AS gym_points_start, pokemon_uids_end, pokemon_uids_start FROM (SELECT gym_after.gym_id, gym_details.name, gym_after.team_id, MAX(gym_after.last_modified) AS last_modified_end, MAX(gym_after.gym_points) AS gym_points_end, MIN(gym_before.last_modified) AS last_modified_start, MAX(gym_before.gym_points) AS gym_points_start, gym_after.pokemon_uids AS pokemon_uids_end, gym_before.pokemon_uids AS pokemon_uids_start
  FROM (SELECT * FROM gymhistory WHERE gym_points > 4000 AND team_id > 0 AND last_modified > (SELECT MAX(last_modified_end) FROM gymshaving)) AS gym_middle
  JOIN gymhistory AS gym_before
  ON gym_middle.gym_id = gym_before.gym_id AND gym_middle.team_id = gym_before.team_id AND (gym_before.gym_points-gym_middle.gym_points) >= 1000 AND gym_middle.last_modified > gym_before.last_modified AND gym_middle.last_modified < (gym_before.last_modified + INTERVAL 5 MINUTE) AND LENGTH(gym_middle.pokemon_uids) < LENGTH(gym_before.pokemon_uids) AND LENGTH(gym_middle.pokemon_uids) > LENGTH(gym_before.pokemon_uids)-24
  JOIN gymhistory AS gym_after
  ON gym_middle.gym_id = gym_after.gym_id AND gym_middle.team_id = gym_after.team_id AND (gym_after.gym_points-gym_middle.gym_points) >= 1000 AND gym_middle.last_modified < gym_after.last_modified AND gym_middle.last_modified > (gym_after.last_modified - INTERVAL 8 MINUTE) AND LENGTH(gym_middle.pokemon_uids) < LENGTH(gym_after.pokemon_uids) AND LENGTH(gym_middle.pokemon_uids) > LENGTH(gym_after.pokemon_uids)-24 AND LENGTH(gym_before.pokemon_uids) > LENGTH(gym_after.pokemon_uids)-5 AND LENGTH(gym_before.pokemon_uids) < LENGTH(gym_after.pokemon_uids)+5
  JOIN gymdetails AS gym_details
  ON gym_after.gym_id = gym_details.gym_id
  GROUP BY gym_after.gym_id, gym_after.last_modified, gym_after.pokemon_uids, gym_before.pokemon_uids)
  AS gym_shaving GROUP BY last_modified_start);
END
//
DELIMITER ;