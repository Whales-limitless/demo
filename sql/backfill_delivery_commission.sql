-- Backfill DISTANT and RETAIL for existing delivery orders from location master
-- This updates orders where DISTANT/RETAIL is 0, empty, or NULL
-- Run this ONCE to fix historical data

-- Preview what will be updated (run this first to verify)
SELECT o.ID, o.ORDNO, o.LOCATION,
       o.DISTANT AS current_distance, l.DISTANT AS location_distance,
       o.RETAIL AS current_commission, l.RETAIL AS location_commission
FROM `del_orderlist` o
INNER JOIN `del_location` l ON o.LOCATION = l.NAME
WHERE (o.DISTANT IS NULL OR o.DISTANT = '' OR o.DISTANT = '0' OR o.DISTANT = '0.00')
   OR (o.RETAIL IS NULL OR o.RETAIL = '' OR o.RETAIL = '0' OR o.RETAIL = '0.00');

-- Update DISTANT from location master where empty/zero
UPDATE `del_orderlist` o
INNER JOIN `del_location` l ON o.LOCATION = l.NAME
SET o.DISTANT = l.DISTANT
WHERE o.DISTANT IS NULL OR o.DISTANT = '' OR o.DISTANT = '0' OR o.DISTANT = '0.00';

-- Update RETAIL (commission) from location master where empty/zero
UPDATE `del_orderlist` o
INNER JOIN `del_location` l ON o.LOCATION = l.NAME
SET o.RETAIL = l.RETAIL
WHERE o.RETAIL IS NULL OR o.RETAIL = '' OR o.RETAIL = '0' OR o.RETAIL = '0.00';
