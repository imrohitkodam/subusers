--
-- Table structure for table `#__tjsu_roles`
--

CREATE TABLE IF NOT EXISTS `#__tjsu_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'The name of the role',
  `client` varchar(255) NOT NULL COMMENT 'The client name Eg. com_tjlms, com_jlike',
  `state` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(11) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `#__tjsu_actions`
--

CREATE TABLE IF NOT EXISTS `#__tjsu_actions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'The unique string of the action Eg. core.view.course',
  `name` varchar(255) NOT NULL COMMENT 'Short intro of action',
  `client` varchar(255) NOT NULL COMMENT 'The client name Eg. com_tjlms, com_jlike',
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__tjsu_role_action_map`
--

CREATE TABLE IF NOT EXISTS `#__tjsu_role_action_map` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL COMMENT 'FK to roles table',
  `action_id` int(11) NOT NULL COMMENT 'FK to actions table',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__tjsu_users`
--

CREATE TABLE IF NOT EXISTS `#__tjsu_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'FK to users table',
  `role_id` int(11) NOT NULL COMMENT 'FK to roles table',
  `client` varchar(255) NOT NULL COMMENT 'The client name Eg. com_tjlms, com_jlike',
  `client_id` int(11) NOT NULL COMMENT 'The client content id.',
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(11) NOT NULL,
  `modified_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
