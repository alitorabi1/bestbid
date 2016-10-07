-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2016 at 09:15 PM
-- Server version: 10.1.16-MariaDB
-- PHP Version: 5.5.38

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bestbid`
--

-- --------------------------------------------------------

--
-- Table structure for table `bids`
--

CREATE TABLE `bids` (
  `ID` int(11) NOT NULL,
  `itemID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `bidAmount` decimal(10,2) NOT NULL,
  `bidDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `bids`
--

INSERT INTO `bids` (`ID`, `itemID`, `userID`, `bidAmount`, `bidDate`) VALUES
(1, 1, 2, '2000.00', '2016-10-07 19:15:24'),
(2, 2, 2, '20.00', '2016-10-07 19:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `ID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mainCategoryID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`ID`, `name`, `mainCategoryID`) VALUES
(1, 'Cars', 1),
(2, 'Boats', 1),
(3, 'Motor cyclets', 1),
(4, 'Laptops', 2),
(5, 'Cellphones', 2),
(6, 'TVs', 2),
(7, 'Apartment', 3),
(8, 'Garden', 3),
(9, 'Skin Care', 4),
(10, 'Hair Care & Styles', 4),
(11, 'Make Up', 4);

-- --------------------------------------------------------

--
-- Table structure for table `itemsforsell`
--

CREATE TABLE `itemsforsell` (
  `ID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `categoryID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(500) NOT NULL,
  `itemPic` mediumblob NOT NULL,
  `bidType` varchar(30) NOT NULL,
  `minimumBid` decimal(10,2) NOT NULL,
  `bidStartTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `bidEndTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `shippingFee` decimal(10,2) NOT NULL,
  `shippingCondition` varchar(50) NOT NULL,
  `paymentMethod` varchar(50) NOT NULL,
  `status` enum('open','sold','notReachedTo') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `itemsforsell`
--

INSERT INTO `itemsforsell` (`ID`, `userID`, `categoryID`, `name`, `description`, `itemPic`, `bidType`, `minimumBid`, `bidStartTime`, `bidEndTime`, `shippingFee`, `shippingCondition`, `paymentMethod`, `status`) VALUES
(1, 1, 1, 'Honda civic', 'sadfsdg rdsdg rsd', '', 'wer', '2345.00', '2016-10-07 19:07:14', '2016-10-24 04:00:00', '50.00', '', '', 'open'),
(2, 1, 2, 'Samsung galaxy 7', 'fucked out', '', 'kju', '250.00', '2016-10-07 19:09:53', '2016-10-31 04:00:00', '0.00', '20', '', 'open');

-- --------------------------------------------------------

--
-- Table structure for table `maincategory`
--

CREATE TABLE `maincategory` (
  `ID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `maincategory`
--

INSERT INTO `maincategory` (`ID`, `name`) VALUES
(1, 'Motors'),
(2, 'Electronics'),
(3, 'Home & Garden'),
(4, 'Fashion');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `ID` int(11) NOT NULL,
  `itemID` int(11) NOT NULL,
  `buyerID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `buyDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`ID`, `itemID`, `buyerID`, `amount`, `buyDate`) VALUES
(1, 1, 2, '1500.00', '2016-10-07 19:14:35'),
(2, 2, 2, '20.00', '2016-10-07 19:14:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(25) NOT NULL,
  `address` varchar(60) NOT NULL,
  `codepostal` varchar(25) NOT NULL,
  `state` varchar(20) NOT NULL,
  `country` varchar(30) NOT NULL,
  `fbUID` enum('no','yes') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`ID`, `username`, `email`, `password`, `address`, `codepostal`, `state`, `country`, `fbUID`) VALUES
(1, 'aa', 'aa@aa.com', '$2y$10$0L5cOArUdRhzRywNCA', 'safwer', 'rweqr', 'werwqr', 'Canada', 'no'),
(2, 'bb', 'bb@bb.com', '$2y$10$0L5cOArUdRhzRywNCA', '31 charron', 'H9W1V1', 'AL', 'CA', 'no');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bids`
--
ALTER TABLE `bids`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `itemID` (`itemID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `mainCategoryID` (`mainCategoryID`);

--
-- Indexes for table `itemsforsell`
--
ALTER TABLE `itemsforsell`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `userID` (`userID`),
  ADD KEY `categoryID` (`categoryID`),
  ADD KEY `description` (`description`(255)),
  ADD KEY `description_2` (`description`(255)),
  ADD KEY `name` (`name`);
ALTER TABLE `itemsforsell` ADD FULLTEXT KEY `description_3` (`description`);
ALTER TABLE `itemsforsell` ADD FULLTEXT KEY `description_4` (`description`);

--
-- Indexes for table `maincategory`
--
ALTER TABLE `maincategory`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `itemID` (`itemID`,`buyerID`),
  ADD KEY `buyerID` (`buyerID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `itemsforsell`
--
ALTER TABLE `itemsforsell`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `maincategory`
--
ALTER TABLE `maincategory`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `bids`
--
ALTER TABLE `bids`
  ADD CONSTRAINT `bids_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`ID`),
  ADD CONSTRAINT `bids_ibfk_2` FOREIGN KEY (`itemID`) REFERENCES `itemsforsell` (`ID`);

--
-- Constraints for table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `category_ibfk_1` FOREIGN KEY (`mainCategoryID`) REFERENCES `maincategory` (`ID`);

--
-- Constraints for table `itemsforsell`
--
ALTER TABLE `itemsforsell`
  ADD CONSTRAINT `itemsforsell_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`ID`),
  ADD CONSTRAINT `itemsforsell_ibfk_2` FOREIGN KEY (`categoryID`) REFERENCES `category` (`ID`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`itemID`) REFERENCES `itemsforsell` (`ID`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`buyerID`) REFERENCES `users` (`ID`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
