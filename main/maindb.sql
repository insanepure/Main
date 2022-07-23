-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 23. Jul 2022 um 20:08
-- Server-Version: 10.5.15-MariaDB-0+deb11u1
-- PHP-Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `main_db1`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `activations`
--

CREATE TABLE `activations` (
  `ID` int(11) NOT NULL,
  `login` varchar(30) NOT NULL,
  `password` varchar(256) NOT NULL,
  `email` varchar(30) NOT NULL,
  `activationcode` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `chatmessages`
--

CREATE TABLE `chatmessages` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `acc` int(11) NOT NULL,
  `main` int(11) NOT NULL,
  `game` varchar(10) NOT NULL,
  `text` longtext NOT NULL,
  `time` datetime NOT NULL,
  `channel` varchar(30) NOT NULL,
  `admin` int(1) NOT NULL,
  `type` int(1) NOT NULL,
  `titel` varchar(50) NOT NULL,
  `titelcolor` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `chatusers`
--

CREATE TABLE `chatusers` (
  `id` int(11) NOT NULL,
  `acc` int(11) NOT NULL,
  `main` int(11) NOT NULL,
  `game` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin` int(1) NOT NULL,
  `channel` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastaction` datetime NOT NULL,
  `sessionid` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titelcolor` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `dbupdates`
--

CREATE TABLE `dbupdates` (
  `id` int(11) NOT NULL,
  `sqlquery` longtext NOT NULL,
  `ip` varchar(256) NOT NULL,
  `time` datetime NOT NULL,
  `callstack` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `interactions`
--

CREATE TABLE `interactions` (
  `id` int(11) NOT NULL,
  `time` datetime NOT NULL,
  `charaids` longtext NOT NULL,
  `game` varchar(10) NOT NULL,
  `action` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `logins`
--

CREATE TABLE `logins` (
  `id` int(30) NOT NULL,
  `time` datetime NOT NULL,
  `user` int(11) NOT NULL,
  `chara` varchar(90) NOT NULL,
  `game` varchar(10) NOT NULL,
  `password` varchar(256) NOT NULL,
  `email` varchar(90) NOT NULL,
  `sessionid` varchar(256) NOT NULL,
  `ip` varchar(256) NOT NULL,
  `cookie` varchar(90) NOT NULL,
  `browser` varchar(30) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `version` varchar(30) NOT NULL,
  `city` varchar(90) NOT NULL,
  `region` varchar(90) NOT NULL,
  `country` varchar(90) NOT NULL,
  `latitude` varchar(20) NOT NULL,
  `longitude` varchar(20) NOT NULL,
  `accuracy` int(10) NOT NULL,
  `referer` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `survey`
--

CREATE TABLE `survey` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `description` longtext NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `survey_option`
--

CREATE TABLE `survey_option` (
  `id` int(11) NOT NULL,
  `survey` int(11) NOT NULL,
  `name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `survey_vote`
--

CREATE TABLE `survey_vote` (
  `id` int(11) NOT NULL,
  `survey` int(11) NOT NULL,
  `acc` int(11) NOT NULL,
  `vote` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `login` varchar(30) NOT NULL,
  `admin` int(2) NOT NULL DEFAULT 0,
  `titel` varchar(50) NOT NULL,
  `titelcolor` varchar(6) NOT NULL,
  `password` varchar(256) NOT NULL,
  `sessionid` varchar(256) NOT NULL,
  `email` varchar(90) NOT NULL,
  `ip` varchar(128) NOT NULL,
  `lastAction` datetime NOT NULL,
  `multis` int(4) NOT NULL DEFAULT 1,
  `multiaccounts` varchar(250) NOT NULL,
  `banned` tinyint(1) NOT NULL,
  `bannedgames` longtext NOT NULL,
  `banreason` varchar(500) NOT NULL,
  `recaptchascore` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `activations`
--
ALTER TABLE `activations`
  ADD PRIMARY KEY (`ID`);

--
-- Indizes für die Tabelle `chatmessages`
--
ALTER TABLE `chatmessages`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `chatusers`
--
ALTER TABLE `chatusers`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `dbupdates`
--
ALTER TABLE `dbupdates`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `interactions`
--
ALTER TABLE `interactions`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `logins`
--
ALTER TABLE `logins`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `survey`
--
ALTER TABLE `survey`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `survey_option`
--
ALTER TABLE `survey_option`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `survey_vote`
--
ALTER TABLE `survey_vote`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessionindex` (`sessionid`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `activations`
--
ALTER TABLE `activations`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `chatmessages`
--
ALTER TABLE `chatmessages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `chatusers`
--
ALTER TABLE `chatusers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `dbupdates`
--
ALTER TABLE `dbupdates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `interactions`
--
ALTER TABLE `interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `logins`
--
ALTER TABLE `logins`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `survey`
--
ALTER TABLE `survey`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `survey_option`
--
ALTER TABLE `survey_option`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `survey_vote`
--
ALTER TABLE `survey_vote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
