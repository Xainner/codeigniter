-- Login attempts must store the full identity (emails allow 254 characters);
-- truncated values never matched, so lockout did not apply to long addresses.
-- Safe to run again.

ALTER TABLE `login_attempts` MODIFY `login` varchar(254) NOT NULL;
