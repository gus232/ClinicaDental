-- HMS Database Backup
-- Fecha: 2025-10-12 01:50:41
-- Base de datos: hms

SET FOREIGN_KEY_CHECKS=0;

-- 
-- 1. Tabla: admin
-- 
DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updationDate` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de admin (2 registros)
INSERT INTO `admin` (`id`, `username`, `password`, `updationDate`) VALUES ('1', 'admin', 'Test@12345', '28-12-2016 11:42:05 AM');
INSERT INTO `admin` (`id`, `username`, `password`, `updationDate`) VALUES ('2', 'nuevoadmin', 'admin12345', '2024-01-15 10:30:00 AM');

-- 
-- 2. Tabla: appointment
-- 
DROP TABLE IF EXISTS `appointment`;

CREATE TABLE `appointment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctorSpecialization` varchar(255) DEFAULT NULL,
  `doctorId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `consultancyFees` int(11) DEFAULT NULL,
  `appointmentDate` varchar(255) DEFAULT NULL,
  `appointmentTime` varchar(255) DEFAULT NULL,
  `postingDate` timestamp NULL DEFAULT current_timestamp(),
  `userStatus` int(11) DEFAULT NULL,
  `doctorStatus` int(11) DEFAULT NULL,
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de appointment (4 registros)
INSERT INTO `appointment` (`id`, `doctorSpecialization`, `doctorId`, `userId`, `consultancyFees`, `appointmentDate`, `appointmentTime`, `postingDate`, `userStatus`, `doctorStatus`, `updationDate`) VALUES ('3', 'Demo test', '7', '6', '600', '2019-06-29', '9:15 AM', '2019-06-23 13:31:28', '1', '0', '0000-00-00 00:00:00');
INSERT INTO `appointment` (`id`, `doctorSpecialization`, `doctorId`, `userId`, `consultancyFees`, `appointmentDate`, `appointmentTime`, `postingDate`, `userStatus`, `doctorStatus`, `updationDate`) VALUES ('4', 'Ayurveda', '5', '5', '8050', '2019-11-08', '1:00 PM', '2019-11-05 05:28:54', '1', '1', '0000-00-00 00:00:00');
INSERT INTO `appointment` (`id`, `doctorSpecialization`, `doctorId`, `userId`, `consultancyFees`, `appointmentDate`, `appointmentTime`, `postingDate`, `userStatus`, `doctorStatus`, `updationDate`) VALUES ('5', 'Dermatologist', '9', '7', '500', '2019-11-30', '5:30 PM', '2019-11-10 13:41:34', '1', '0', '2019-11-10 13:48:30');
INSERT INTO `appointment` (`id`, `doctorSpecialization`, `doctorId`, `userId`, `consultancyFees`, `appointmentDate`, `appointmentTime`, `postingDate`, `userStatus`, `doctorStatus`, `updationDate`) VALUES ('6', 'General Physician', '6', '2', '2500', '2022-07-22', '6:30 PM', '2022-07-15 16:24:38', '1', '1', NULL);

-- 
-- 3. Tabla: doctors
-- 
DROP TABLE IF EXISTS `doctors`;

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `specilization` varchar(255) DEFAULT NULL,
  `doctorName` varchar(255) DEFAULT NULL,
  `address` longtext DEFAULT NULL,
  `docFees` varchar(255) DEFAULT NULL,
  `contactno` bigint(11) DEFAULT NULL,
  `docEmail` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `creationDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de doctors (9 registros)
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('1', 'Dentist', 'Anuj', 'New Delhi', '500', '8285703354', 'anuj.lpu1@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2016-12-29 01:25:37', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('2', 'Homeopath', 'Sarita Pandey', 'Varanasi', '600', '2147483647', 'sarita@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2016-12-29 01:51:51', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('3', 'General Physician', 'Nitesh Kumar', 'Ghaziabad', '1200', '8523699999', 'nitesh@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2017-01-07 02:43:35', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('4', 'Homeopath', 'Vijay Verma', 'New Delhi', '700', '25668888', 'vijay@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2017-01-07 02:45:09', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('5', 'Ayurveda', 'Sanjeev', 'Gurugram', '8050', '442166644646', 'sanjeev@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2017-01-07 02:47:07', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('6', 'General Physician', 'Amrita', 'New Delhi India', '2500', '45497964', 'amrita@test.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2017-01-07 02:52:50', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('7', 'Demo test', 'abc ', 'New Delhi India', '200', '852888888', 'test@demo.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2017-01-07 03:08:58', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('8', 'Ayurveda', 'Test Doctor', 'Xyz Abc New Delhi', '600', '1234567890', 'test@test.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2019-06-23 12:57:43', '2025-10-10 22:03:13');
INSERT INTO `doctors` (`id`, `specilization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`, `creationDate`, `updationDate`) VALUES ('9', 'Dermatologist', 'Anuj kumar', 'New Delhi India 110001', '500', '1234567890', 'anujk@test.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', '2019-11-10 13:37:47', '2025-10-10 22:03:13');

-- 
-- 4. Tabla: doctorslog
-- 
DROP TABLE IF EXISTS `doctorslog`;

CREATE TABLE `doctorslog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` binary(16) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de doctorslog (2 registros)
INSERT INTO `doctorslog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('20', '7', 'test@demo.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2022-07-15 15:59:57', '16-07-2022 02:30:39 AM', '1');
INSERT INTO `doctorslog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('21', '7', 'test@demo.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2022-07-15 16:25:47', '16-07-2022 02:56:57 AM', '1');

-- 
-- 5. Tabla: doctorspecilization
-- 
DROP TABLE IF EXISTS `doctorspecilization`;

CREATE TABLE `doctorspecilization` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `specilization` varchar(255) DEFAULT NULL,
  `creationDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de doctorspecilization (11 registros)
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('1', 'Gynecologist/Obstetrician', '2016-12-28 01:37:25', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('2', 'General Physician', '2016-12-28 01:38:12', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('3', 'Dermatologist', '2016-12-28 01:38:48', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('4', 'Homeopath', '2016-12-28 01:39:26', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('5', 'Ayurveda', '2016-12-28 01:39:51', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('6', 'Dentist', '2016-12-28 01:40:08', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('7', 'Ear-Nose-Throat (Ent) Specialist', '2016-12-28 01:41:18', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('9', 'Demo test', '2016-12-28 02:37:39', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('10', 'Bones Specialist demo', '2017-01-07 03:07:53', '0000-00-00 00:00:00');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('11', 'Test', '2019-06-23 12:51:06', '2019-06-23 12:55:06');
INSERT INTO `doctorspecilization` (`id`, `specilization`, `creationDate`, `updationDate`) VALUES ('12', 'Dermatologist', '2019-11-10 13:36:36', '2019-11-10 13:36:50');

-- 
-- 6. Tabla: tblcontactus
-- 
DROP TABLE IF EXISTS `tblcontactus`;

CREATE TABLE `tblcontactus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contactno` bigint(12) DEFAULT NULL,
  `message` mediumtext DEFAULT NULL,
  `PostingDate` timestamp NULL DEFAULT current_timestamp(),
  `AdminRemark` mediumtext DEFAULT NULL,
  `LastupdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `IsRead` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de tblcontactus (3 registros)
INSERT INTO `tblcontactus` (`id`, `fullname`, `email`, `contactno`, `message`, `PostingDate`, `AdminRemark`, `LastupdationDate`, `IsRead`) VALUES ('1', 'test user', 'test@gmail.com', '2523523522523523', ' This is sample text for the test.', '2019-06-29 14:03:08', 'Test Admin Remark', '2019-06-30 07:55:23', '1');
INSERT INTO `tblcontactus` (`id`, `fullname`, `email`, `contactno`, `message`, `PostingDate`, `AdminRemark`, `LastupdationDate`, `IsRead`) VALUES ('2', 'Anuj kumar', 'test123@gmail.com', '1111111111111111', ' This is sample text for testing.  This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing. This is sample text for testing.', '2019-06-30 08:06:50', NULL, NULL, NULL);
INSERT INTO `tblcontactus` (`id`, `fullname`, `email`, `contactno`, `message`, `PostingDate`, `AdminRemark`, `LastupdationDate`, `IsRead`) VALUES ('3', 'fdsfsdf', 'fsdfsd@ghashhgs.com', '3264826346', 'sample text   sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  sample text  ', '2019-11-10 13:53:48', 'vfdsfgfd', '2019-11-10 13:54:04', '1');

-- 
-- 7. Tabla: tblmedicalhistory
-- 
DROP TABLE IF EXISTS `tblmedicalhistory`;

CREATE TABLE `tblmedicalhistory` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `PatientID` int(10) DEFAULT NULL,
  `BloodPressure` varchar(200) DEFAULT NULL,
  `BloodSugar` varchar(200) NOT NULL,
  `Weight` varchar(100) DEFAULT NULL,
  `Temperature` varchar(200) DEFAULT NULL,
  `MedicalPres` mediumtext DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de tblmedicalhistory (6 registros)
INSERT INTO `tblmedicalhistory` (`ID`, `PatientID`, `BloodPressure`, `BloodSugar`, `Weight`, `Temperature`, `MedicalPres`, `CreationDate`) VALUES ('2', '3', '120/185', '80/120', '85 Kg', '101 degree', '#Fever, #BP high\r\n1.Paracetamol\r\n2.jocib tab\r\n', '2019-11-05 23:20:07');
INSERT INTO `tblmedicalhistory` (`ID`, `PatientID`, `BloodPressure`, `BloodSugar`, `Weight`, `Temperature`, `MedicalPres`, `CreationDate`) VALUES ('3', '2', '90/120', '92/190', '86 kg', '99 deg', '#Sugar High\r\n1.Petz 30', '2019-11-05 23:31:24');
INSERT INTO `tblmedicalhistory` (`ID`, `PatientID`, `BloodPressure`, `BloodSugar`, `Weight`, `Temperature`, `MedicalPres`, `CreationDate`) VALUES ('4', '1', '125/200', '86/120', '56 kg', '98 deg', '# blood pressure is high\r\n1.koil cipla', '2019-11-05 23:52:42');
INSERT INTO `tblmedicalhistory` (`ID`, `PatientID`, `BloodPressure`, `BloodSugar`, `Weight`, `Temperature`, `MedicalPres`, `CreationDate`) VALUES ('5', '1', '96/120', '98/120', '57 kg', '102 deg', '#Viral\r\n1.gjgjh-1Ml\r\n2.kjhuiy-2M', '2019-11-05 23:56:55');
INSERT INTO `tblmedicalhistory` (`ID`, `PatientID`, `BloodPressure`, `BloodSugar`, `Weight`, `Temperature`, `MedicalPres`, `CreationDate`) VALUES ('6', '4', '90/120', '120', '56', '98 F', '#blood sugar high\r\n#Asthma problem', '2019-11-06 09:38:33');
INSERT INTO `tblmedicalhistory` (`ID`, `PatientID`, `BloodPressure`, `BloodSugar`, `Weight`, `Temperature`, `MedicalPres`, `CreationDate`) VALUES ('7', '5', '80/120', '120', '85', '98.6', 'Rx\r\n\r\nAbc tab\r\nxyz Syrup', '2019-11-10 13:50:23');

-- 
-- 8. Tabla: tblpatient
-- 
DROP TABLE IF EXISTS `tblpatient`;

CREATE TABLE `tblpatient` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Docid` int(10) DEFAULT NULL,
  `PatientName` varchar(200) DEFAULT NULL,
  `PatientContno` bigint(10) DEFAULT NULL,
  `PatientEmail` varchar(200) DEFAULT NULL,
  `PatientGender` varchar(50) DEFAULT NULL,
  `PatientAdd` mediumtext DEFAULT NULL,
  `PatientAge` int(10) DEFAULT NULL,
  `PatientMedhis` mediumtext DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de tblpatient (5 registros)
INSERT INTO `tblpatient` (`ID`, `Docid`, `PatientName`, `PatientContno`, `PatientEmail`, `PatientGender`, `PatientAdd`, `PatientAge`, `PatientMedhis`, `CreationDate`, `UpdationDate`) VALUES ('1', '1', 'Manisha Jha', '4558968789', 'test@gmail.com', 'Female', '\"\"J&K Block J-127, Laxmi Nagar New Delhi', '26', 'She is diabetic patient', '2019-11-04 16:38:06', '2019-11-06 01:48:05');
INSERT INTO `tblpatient` (`ID`, `Docid`, `PatientName`, `PatientContno`, `PatientEmail`, `PatientGender`, `PatientAdd`, `PatientAge`, `PatientMedhis`, `CreationDate`, `UpdationDate`) VALUES ('2', '5', 'Raghu Yadav', '9797977979', 'raghu@gmail.com', 'Male', 'ABC Apartment Mayur Vihar Ph-1 New Delhi', '39', 'No', '2019-11-05 05:40:13', '2019-11-05 06:53:45');
INSERT INTO `tblpatient` (`ID`, `Docid`, `PatientName`, `PatientContno`, `PatientEmail`, `PatientGender`, `PatientAdd`, `PatientAge`, `PatientMedhis`, `CreationDate`, `UpdationDate`) VALUES ('3', '7', 'Mansi', '9878978798', 'jk@gmail.com', 'Female', '\"fdghyj', '46', 'No', '2019-11-05 05:49:41', '2019-11-05 06:58:59');
INSERT INTO `tblpatient` (`ID`, `Docid`, `PatientName`, `PatientContno`, `PatientEmail`, `PatientGender`, `PatientAdd`, `PatientAge`, `PatientMedhis`, `CreationDate`, `UpdationDate`) VALUES ('4', '7', 'Manav Sharma', '9888988989', 'sharma@gmail.com', 'Male', 'L-56,Ashok Nagar New Delhi-110096', '45', 'He is long suffered by asthma', '2019-11-06 09:33:54', '2019-11-06 09:34:31');
INSERT INTO `tblpatient` (`ID`, `Docid`, `PatientName`, `PatientContno`, `PatientEmail`, `PatientGender`, `PatientAdd`, `PatientAge`, `PatientMedhis`, `CreationDate`, `UpdationDate`) VALUES ('5', '9', 'John', '1234567890', 'john@test.com', 'male', 'Test ', '25', 'THis is sample text for testing.', '2019-11-10 13:49:24', NULL);

-- 
-- 9. Tabla: userlog
-- 
DROP TABLE IF EXISTS `userlog`;

CREATE TABLE `userlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` binary(16) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de userlog (9 registros)
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('24', NULL, 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2022-07-15 15:57:20', NULL, '0');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('25', '2', 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2022-07-15 15:57:57', '16-07-2022 02:29:28 AM', '1');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('26', '2', 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2022-07-15 16:11:12', '16-07-2022 02:55:17 AM', '1');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('27', NULL, 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2025-10-10 21:11:50', NULL, '0');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('28', '2', 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2025-10-10 21:11:51', NULL, '1');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('29', '2', 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2025-10-10 21:11:52', NULL, '1');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('30', NULL, 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2025-10-10 21:18:02', NULL, '0');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('31', '2', 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2025-10-10 21:18:03', NULL, '1');
INSERT INTO `userlog` (`id`, `uid`, `username`, `userip`, `loginTime`, `logout`, `status`) VALUES ('32', '2', 'test@gmail.com', '::1\0\0\0\0\0\0\0\0\0\0\0\0\0', '2025-10-10 21:18:04', NULL, '1');

-- 
-- 10. Tabla: users
-- 
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullName` varchar(255) DEFAULT NULL,
  `address` longtext DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'patient',
  `regDate` timestamp NULL DEFAULT current_timestamp(),
  `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Datos de users (5 registros)
INSERT INTO `users` (`id`, `fullName`, `address`, `city`, `gender`, `email`, `password`, `role`, `regDate`, `updationDate`) VALUES ('2', 'Sarita pandey', 'New Delhi India', 'Delhi', 'female', 'test@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', 'patient', '2016-12-30 00:34:39', '2025-10-10 22:03:13');
INSERT INTO `users` (`id`, `fullName`, `address`, `city`, `gender`, `email`, `password`, `role`, `regDate`, `updationDate`) VALUES ('4', 'Rahul Singh', 'New Delhi', 'New delhi', 'male', 'rahul@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', 'patient', '2017-01-07 02:41:14', '2025-10-10 22:03:13');
INSERT INTO `users` (`id`, `fullName`, `address`, `city`, `gender`, `email`, `password`, `role`, `regDate`, `updationDate`) VALUES ('5', 'Amit kumar', 'New Delhi India', 'Delhi', 'male', 'amit12@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', 'patient', '2017-01-07 03:00:26', '2025-10-10 22:03:13');
INSERT INTO `users` (`id`, `fullName`, `address`, `city`, `gender`, `email`, `password`, `role`, `regDate`, `updationDate`) VALUES ('6', 'Test user', 'New Delhi', 'Delhi', 'male', 'tetuser@gmail.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', 'patient', '2019-06-23 13:24:53', '2025-10-10 22:03:13');
INSERT INTO `users` (`id`, `fullName`, `address`, `city`, `gender`, `email`, `password`, `role`, `regDate`, `updationDate`) VALUES ('7', 'John', 'USA', 'Newyork', 'male', 'john@test.com', '$2y$10$8DGAgtl7sSfZ9KcHXHpO4.BHOERIL4P3qtWlJepn1ecCsWdl052X2', 'patient', '2019-11-10 13:40:21', '2025-10-10 22:03:13');

SET FOREIGN_KEY_CHECKS=1;
