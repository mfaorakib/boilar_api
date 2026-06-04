-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Dec 10, 2020 at 09:36 AM
-- Server version: 5.7.30
-- PHP Version: 7.4.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `runner`
--

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `division_id` bigint(20) UNSIGNED NOT NULL COMMENT 'division id',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'check, if active or inactive',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'district name',
  `bn_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'district name'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `division_id`, `status`, `name`, `bn_name`) VALUES
(1, 1, 'active', 'Comilla', 'কুমিল্লা'),
(2, 1, 'active', 'Feni', 'ফেনী'),
(3, 1, 'active', 'Brahmanbaria', 'ব্রাহ্মণবাড়িয়া'),
(4, 1, 'active', 'Rangamati', 'রাঙ্গামাটি'),
(5, 1, 'active', 'Noakhali', 'নোয়াখালী'),
(6, 1, 'active', 'Chandpur', 'চাঁদপুর'),
(7, 1, 'active', 'Lakshmipur', 'লক্ষ্মীপুর'),
(8, 1, 'active', 'Chattogram', 'চট্টগ্রাম'),
(9, 1, 'active', 'Coxsbazar', 'কক্সবাজার'),
(10, 1, 'active', 'Khagrachhari', 'খাগড়াছড়ি'),
(11, 1, 'active', 'Bandarban', 'বান্দরবান'),
(12, 2, 'active', 'Sirajganj', 'সিরাজগঞ্জ'),
(13, 2, 'active', 'Pabna', 'পাবনা'),
(14, 2, 'active', 'Bogura', 'বগুড়া'),
(15, 2, 'active', 'Rajshahi', 'রাজশাহী'),
(16, 2, 'active', 'Natore', 'নাটোর'),
(17, 2, 'active', 'Joypurhat', 'জয়পুরহাট'),
(18, 2, 'active', 'Chapainawabganj', 'চাঁপাইনবাবগঞ্জ'),
(19, 2, 'active', 'Naogaon', 'নওগাঁ'),
(20, 3, 'active', 'Jashore', 'যশোর'),
(21, 3, 'active', 'Satkhira', 'সাতক্ষীরা'),
(22, 3, 'active', 'Meherpur', 'মেহেরপুর'),
(23, 3, 'active', 'Narail', 'নড়াইল'),
(24, 3, 'active', 'Chuadanga', 'চুয়াডাঙ্গা'),
(25, 3, 'active', 'Kushtia', 'কুষ্টিয়া'),
(26, 3, 'active', 'Magura', 'মাগুরা'),
(27, 3, 'active', 'Khulna', 'খুলনা'),
(28, 3, 'active', 'Bagerhat', 'বাগেরহাট'),
(29, 3, 'active', 'Jhenaidah', 'ঝিনাইদহ'),
(30, 4, 'active', 'Jhalakathi', 'ঝালকাঠি'),
(31, 4, 'active', 'Patuakhali', 'পটুয়াখালী'),
(32, 4, 'active', 'Pirojpur', 'পিরোজপুর'),
(33, 4, 'active', 'Barisal', 'বরিশাল'),
(34, 4, 'active', 'Bhola', 'ভোলা'),
(35, 4, 'active', 'Barguna', 'বরগুনা'),
(36, 5, 'active', 'Sylhet', 'সিলেট'),
(37, 5, 'active', 'Moulvibazar', 'মৌলভীবাজার'),
(38, 5, 'active', 'Habiganj', 'হবিগঞ্জ'),
(39, 5, 'active', 'Sunamganj', 'সুনামগঞ্জ'),
(40, 6, 'active', 'Narsingdi', 'নরসিংদী'),
(41, 6, 'active', 'Gazipur', 'গাজীপুর'),
(42, 6, 'active', 'Shariatpur', 'শরীয়তপুর'),
(43, 6, 'active', 'Narayanganj', 'নারায়ণগঞ্জ'),
(44, 6, 'active', 'Tangail', 'টাঙ্গাইল'),
(45, 6, 'active', 'Kishoreganj', 'কিশোরগঞ্জ'),
(46, 6, 'active', 'Manikganj', 'মানিকগঞ্জ'),
(47, 6, 'active', 'Dhaka', 'ঢাকা'),
(48, 6, 'active', 'Munshiganj', 'মুন্সিগঞ্জ'),
(49, 6, 'active', 'Rajbari', 'রাজবাড়ী'),
(50, 6, 'active', 'Madaripur', 'মাদারীপুর'),
(51, 6, 'active', 'Gopalganj', 'গোপালগঞ্জ'),
(52, 6, 'active', 'Faridpur', 'ফরিদপুর'),
(53, 7, 'active', 'Panchagarh', 'পঞ্চগড়'),
(54, 7, 'active', 'Dinajpur', 'দিনাজপুর'),
(55, 7, 'active', 'Lalmonirhat', 'লালমনিরহাট'),
(56, 7, 'active', 'Nilphamari', 'নীলফামারী'),
(57, 7, 'active', 'Gaibandha', 'গাইবান্ধা'),
(58, 7, 'active', 'Thakurgaon', 'ঠাকুরগাঁও'),
(59, 7, 'active', 'Rangpur', 'রংপুর'),
(60, 7, 'active', 'Kurigram', 'কুড়িগ্রাম'),
(61, 8, 'active', 'Sherpur', 'শেরপুর'),
(62, 8, 'active', 'Mymensingh', 'ময়মনসিংহ'),
(63, 8, 'active', 'Jamalpur', 'জামালপুর'),
(64, 8, 'active', 'Netrokona', 'নেত্রকোণা');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `districts_division_id_foreign` (`division_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `districts_division_id_foreign` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
