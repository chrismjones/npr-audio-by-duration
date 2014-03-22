SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `Topic`;
CREATE TABLE IF NOT EXISTS `Topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

DROP TABLE IF EXISTS `Track`;
CREATE TABLE IF NOT EXISTS `Track` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `duration` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file` (`file`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=773 ;

DROP TABLE IF EXISTS `TrackTopic`;
CREATE TABLE IF NOT EXISTS `TrackTopic` (
  `trackId` int(11) NOT NULL,
  `topicId` int(11) NOT NULL,
  KEY `trackId` (`trackId`),
  KEY `topicId` (`topicId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `TrackTopic`
  ADD CONSTRAINT `TrackTopic_ibfk_2` FOREIGN KEY (`topicId`) REFERENCES `Topic` (`id`),
  ADD CONSTRAINT `TrackTopic_ibfk_1` FOREIGN KEY (`trackId`) REFERENCES `Track` (`id`);
