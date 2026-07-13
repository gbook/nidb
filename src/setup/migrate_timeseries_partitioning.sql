-- Migration: optimize `timeseries` for high-volume, write-once time-series storage.
--
-- Changes:
--   1. Narrows the dedup key from (observation_id, time, value_int, value_double,
--      value_string) to (observation_id, time). Rows are write-once and are
--      deleted rather than updated, so a single value can exist per
--      observation_id+time and the wide 5-column unique key (including a
--      255-byte varchar) was pure write-path overhead.
--   2. Drops the now-redundant single-column `observation_id` key -- it was
--      already a prefix of the unique key above.
--   3. Removes ON UPDATE CURRENT_TIMESTAMP from `time` -- these rows are never
--      updated, so the auto-refresh was only a latent footgun.
--   4. Partitions by year of `time` so a full season's data can be dropped
--      instantly (ALTER TABLE ... DROP PARTITION) instead of a row-by-row
--      DELETE, and so time-range queries prune to a single partition.
--
-- Run this against the live nidb database. It rebuilds the table, so it should
-- be run now while `timeseries` only holds test data. Aria does not support
-- online DDL, so on a much larger table this would take a while and hold a
-- table lock for the duration -- re-run only after off-hours if the table has
-- since grown large.
--
-- Adjust the partition years below to match the actual project timeline
-- before running.

ALTER TABLE `timeseries`
  DROP PRIMARY KEY,
  DROP KEY `observation_id_2`,
  DROP KEY `observation_id`,
  MODIFY `time` timestamp NOT NULL DEFAULT current_timestamp(),
  ADD PRIMARY KEY (`timeseries_id`,`time`),
  ADD UNIQUE KEY `observation_id_time` (`observation_id`,`time`);

ALTER TABLE `timeseries`
  PARTITION BY RANGE (YEAR(`time`)) (
    PARTITION p2025    VALUES LESS THAN (2026),
    PARTITION p2026    VALUES LESS THAN (2027),
    PARTITION p2027    VALUES LESS THAN (2028),
    PARTITION p2028    VALUES LESS THAN (2029),
    PARTITION p2029    VALUES LESS THAN (2030),
    PARTITION p2030    VALUES LESS THAN (2031),
    PARTITION pfuture  VALUES LESS THAN MAXVALUE
  );

-- To retire a completed season once its data has been exported/archived:
--   ALTER TABLE `timeseries` DROP PARTITION p2025;
-- Add a new partition ahead of a new season (splits pfuture so future inserts
-- keep landing in a bounded partition):
--   ALTER TABLE `timeseries` REORGANIZE PARTITION pfuture INTO (
--     PARTITION p2031 VALUES LESS THAN (2032),
--     PARTITION pfuture VALUES LESS THAN MAXVALUE
--   );
