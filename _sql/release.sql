-- Release.sql for Shopware 3.5.4

/*
 * @ticket 4847
 * @author h.lohaus 
 * @since 3.5.4 - 2011/03/22
 */

UPDATE `s_core_config` SET `description` = 'Method to send mail: ("mail", "smtp" or "file").' WHERE `name`='sMAILER_Mailer';

SET @parent = (SELECT `id` FROM `s_core_config_groups` WHERE `name` = 'Mailer');
INSERT IGNORE INTO `s_core_config` (`id`, `group`, `name`, `value`, `description`, `required`, `warning`, `detailtext`, `multilanguage`, `fieldtype`) VALUES
(NULL, @parent, 'sMAILER_Auth', '', 'Sets connection auth. Options are "", "plain",  "login" or "crammd5"', 0, 0, '', 1, '');

/*
 * @ticket 5258
 * @author h.lohaus 
 * @since 3.5.4 - 2011/03/30
 */
DELETE FROM `s_core_snippets` WHERE `namespace` LIKE '/%' OR `namespace` LIKE 'templates/%';
UPDATE `s_core_snippets` SET `shopID` = 1 WHERE `shopID` = 0;

INSERT IGNORE INTO `s_core_snippets` (`id`, `namespace`, `shopID`, `localeID`, `name`, `value`, `created`, `updated`) VALUES
(NULL, 'backend/index/menu', 1, 2, 'Alle schliessen', 'Close all', '2011-03-31 11:47:42', '2011-03-31 11:47:42'),
(NULL, 'backend/index/menu', 1, 2, 'Anlegen', 'New', '2011-03-31 11:48:05', '2011-03-31 11:48:56'),
(NULL, 'backend/index/menu', 1, 2, 'Artikel', 'Products', '2011-03-31 11:49:30', '2011-04-01 11:42:15'),
(NULL, 'backend/index/menu', 1, 2, 'Artikel + Kategorien', 'Products + Categories', '2011-03-31 11:50:05', '2011-03-31 11:50:05'),
(NULL, 'backend/index/menu', 1, 2, 'Einstellungen', 'Settings', '2011-03-31 11:50:26', '2011-03-31 11:50:26'),
(NULL, 'backend/snippet/skeleton', 1, 2, 'WindowTitle', 'Textbausteine', '2011-04-01 11:33:58', '2011-04-01 11:33:58'),
(NULL, 'backend/auth/login_panel', 1, 2, 'UserNameField', 'User', '2011-04-01 11:34:47', '2011-04-01 11:36:30'),
(NULL, 'backend/auth/login_panel', 1, 2, 'PasswordMessage', 'Please enter a password!', '2011-04-01 11:35:29', '2011-04-01 11:36:08'),
(NULL, 'backend/auth/login_panel', 1, 2, 'UserNameMessage', 'Please enter a user name!', '2011-04-01 11:35:57', '2011-04-01 11:36:28'),
(NULL, 'backend/index/index', 1, 2, 'SearchLabel', 'Search', '2011-04-01 11:37:50', '2011-04-01 11:39:30'),
(NULL, 'backend/index/index', 1, 2, 'AccountMissing', 'No account created!', '2011-04-01 11:38:03', '2011-04-01 11:39:25'),
(NULL, 'backend/index/index', 1, 2, 'UserLabel', 'User: {$UserName}', '2011-04-01 11:38:20', '2011-04-01 11:39:31'),
(NULL, 'backend/index/index', 1, 2, 'LiveViewLabel', 'Shop view', '2011-04-01 11:38:40', '2011-04-01 11:39:26'),
(NULL, 'backend/index/index', 1, 2, 'AccountBalance', 'Balance: {$Amount} SC', '2011-04-01 11:38:57', '2011-04-01 11:39:24'),
(NULL, 'backend/index/menu', 1, 2, 'Fenster', 'Window', '2011-04-01 11:39:53', '2011-04-01 11:40:07'),
(NULL, 'backend/index/menu', 1, 2, 'Inhalte', 'Content', '2011-04-01 11:40:43', '2011-04-01 11:40:47'),
(NULL, 'backend/index/menu', 1, 2, 'Hilfe', 'Help', '2011-04-01 11:41:03', '2011-04-01 11:41:08'),
(NULL, 'backend/index/menu', 1, 2, 'Kunden', 'Customers', '2011-04-01 11:41:58', '2011-04-01 11:42:04'),
(NULL, 'backend/auth/login_panel', 1, 2, 'LoginButton', 'Login', '2011-04-01 11:37:09', '2011-04-01 11:37:09'),
(NULL, 'backend/auth/login_panel', 1, 2, 'LocaleField', 'Language', '2011-04-01 11:37:32', '2011-04-01 11:37:32'),
(NULL, 'backend/auth/login_panel', 1, 2, 'PasswordField', 'Password', '2011-04-01 11:37:32', '2011-04-01 11:37:32');

/*
 * @ticket 4778
 * @author h.lohaus 
 * @since 3.5.4 - 2011/04/01
 */
ALTER TABLE `s_core_currencies` ADD `symbol_position` INT( 11 ) UNSIGNED NOT NULL AFTER `templatechar`;

/*
 * @ticket 5068
 * @author h.lohaus 
 * @since 3.5.4 - 2011/04/12
 */
UPDATE `s_core_menu` SET `style` = 'background-position: 5px 5px;' WHERE `name` = 'Textbausteine';
UPDATE `s_core_config` SET `value` = '3.5.4' WHERE `name` = 'sVERSION';

/*
 * @ticket 4836
 * @author st.hamann
 * @since 3.5.4 - 2011/05/18
 */
INSERT IGNORE INTO `s_core_snippets` (`id`, `namespace`, `shopID`, `localeID`, `name`, `value`, `created`, `updated`) VALUES
(NULL, 'frontend/account/password', 1, 1, 'PasswordSendAction', 'Passwort anfordern', '2011-05-17 11:47:42', '2011-05-17 11:47:42');

/*
 * @ticket 5124
 * @author h.lohaus 
 * @since 3.5.4 - 2011/04/29
 */
ALTER TABLE `s_core_paymentmeans` ADD `action` VARCHAR( 255 ) NULL ,
ADD `pluginID` INT( 11 ) UNSIGNED NULL 


/*
 * @ticket 5125
 * @author h.lohaus 
 * @since 3.5.4 - 2011/05/18
 */
UPDATE `s_core_config_mails` SET `name` = TRIM(`name`);