-- Seed data for sysfile table
-- Admin user (TYPE='A') and regular user (TYPE='U')

INSERT INTO `sysfile` (`ID`, `USER1`, `USER2`, `USER_NAME`, `LEVEL`, `TYPE`, `STATUS`, `OUTLET`, `DEPT`, `PUSHID`) VALUES
(1, 'admin', 'smpos123', 'Administrator', 99.00, 'A', 'Y', 'HQ', 'Management', ''),
(2, 'user', 'user123', 'Staff User', 1.00, 'U', 'Y', 'HQ', 'Sales', '');
