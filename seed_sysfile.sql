-- Seed data for sysfile table
-- TYPE: A = Admin, S = Staff, D = Delivery
-- USERNAME = auto-generated unique code

INSERT INTO `sysfile` (`ID`, `USER1`, `USER2`, `USER_NAME`, `USERNAME`, `TYPE`, `STATUS`, `OUTLET`, `PUSHID`) VALUES
(1, 'admin', 'smpos123', 'Administrator', 'USR0001', 'A', 'Y', 'MAIN', ''),
(2, 'staff', 'staff123', 'Staff User', 'USR0002', 'S', 'Y', 'MAIN', ''),
(3, 'delivery', 'delivery123', 'Delivery Driver', 'USR0003', 'D', 'Y', 'MAIN', '');
