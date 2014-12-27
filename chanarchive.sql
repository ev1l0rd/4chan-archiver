SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `Posts`
--

CREATE TABLE IF NOT EXISTS `Posts` (
  `ID` int(15) NOT NULL,
  `ThreadID` int(15) NOT NULL,
  `Board` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `PostTime` int(15) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Threads`
--

CREATE TABLE IF NOT EXISTS `Threads` (
  `ID` int(15) NOT NULL,
  `Board` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Status` tinyint(1) NOT NULL,
  `LastChecked` int(15) NOT NULL,
  `TimeAdded` int(15) NOT NULL,
  `Marked` tinyint(1) NOT NULL,
  `PostCount` int(15) NOT NULL,
  `FileCount` int(15) NOT NULL,
  `NewestPostTime` int(15) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
