-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql211.infinityfree.com
-- Generation Time: Mar 12, 2026 at 10:58 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_39689452_newjan2026`
--

-- --------------------------------------------------------

--
-- Table structure for table `2infosys_feedback`
--

CREATE TABLE `2infosys_feedback` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `coding_accuracy` int(11) DEFAULT NULL,
  `problem_solving` int(11) DEFAULT NULL,
  `time_management` int(11) DEFAULT NULL,
  `conceptual_clarity` int(11) DEFAULT NULL,
  `application_training` int(11) DEFAULT NULL,
  `comments_a` text DEFAULT NULL,
  `prog_fund_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `prog_fund_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `dsa_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `dsa_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `aptitude_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `aptitude_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `mock_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `mock_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `confidence_level` enum('Very Confident','Somewhat Confident','Not Confident') DEFAULT NULL,
  `best_module` text DEFAULT NULL,
  `training_gaps` text DEFAULT NULL,
  `lag_topic` text DEFAULT NULL,
  `overall_readiness` enum('Ready','Almost Ready','Needs Further Preparation') DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `3FEEDBACK`
--

CREATE TABLE `3FEEDBACK` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) DEFAULT NULL,
  `usefulness` int(11) DEFAULT NULL,
  `speed` varchar(20) DEFAULT NULL,
  `confidence` varchar(20) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `difficulty` varchar(20) DEFAULT NULL,
  `strategies` text DEFAULT NULL,
  `trainers_knowledge` int(11) DEFAULT NULL,
  `trainers_explain` int(11) DEFAULT NULL,
  `trainers_helpful` int(11) DEFAULT NULL,
  `guidance` varchar(20) DEFAULT NULL,
  `trainer_like` text DEFAULT NULL,
  `trainer_improve` text DEFAULT NULL,
  `overall_exp` int(11) DEFAULT NULL,
  `format_engage` int(11) DEFAULT NULL,
  `like_most` text DEFAULT NULL,
  `improve_next` text DEFAULT NULL,
  `more_hackathons` varchar(20) DEFAULT NULL,
  `suggestions` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_incentives`
--

CREATE TABLE `admission_incentives` (
  `id` int(11) NOT NULL,
  `empid` int(11) NOT NULL,
  `cets` enum('eapcet','polycet','icet-mba','icet-mca','pgcet','ecet') NOT NULL,
  `admission_year` year(4) NOT NULL,
  `incentive_amount` decimal(10,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_incentives_given`
--

CREATE TABLE `admission_incentives_given` (
  `id` int(11) NOT NULL,
  `empid` int(11) NOT NULL,
  `given_incentives` decimal(10,2) NOT NULL,
  `given_date` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `classid` varchar(100) NOT NULL,
  `att_date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL,
  `ph_no` varchar(50) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_time`
--

CREATE TABLE `attendance_time` (
  `id` int(11) NOT NULL,
  `classid` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BUSREG`
--

CREATE TABLE `BUSREG` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `busname` varchar(100) NOT NULL,
  `ticketno` varchar(50) NOT NULL,
  `seatno` varchar(20) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `CLASS_CALENDAR`
--

CREATE TABLE `CLASS_CALENDAR` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `prog` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `classids` text NOT NULL,
  `TYPE` enum('ONLINE','OFFLINE') DEFAULT NULL,
  `classtype` enum('GATE EXPERT','CAMPUS EXPERT','PLACEMENT EXPERT','KIOT','HACKATHON','PROJECT') NOT NULL,
  `expert_id` int(11) NOT NULL,
  `faculty_coordinator` varchar(100) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `topic` varchar(400) NOT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `topic_covered` varchar(5000) DEFAULT NULL,
  `yt_link` varchar(1000) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `CLASS_FEEDBACK`
--

CREATE TABLE `CLASS_FEEDBACK` (
  `ID` int(11) NOT NULL,
  `HTNO` varchar(50) NOT NULL,
  `CLASS_CALENDAR_ID` int(11) NOT NULL,
  `Q1_RATING` int(11) NOT NULL,
  `Q2_RATING` int(11) NOT NULL,
  `Q3_RATING` int(11) NOT NULL,
  `COMMENTS` text NOT NULL,
  `CREATED_AT` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_test`
--

CREATE TABLE `class_test` (
  `test_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `classid` varchar(500) NOT NULL,
  `total_marks` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `test_time` time DEFAULT NULL,
  `year` int(11) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_test_marks`
--

CREATE TABLE `class_test_marks` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `marks_obtained` varchar(10) NOT NULL,
  `entered_by` varchar(50) NOT NULL,
  `entered_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `correct_answers`
--

CREATE TABLE `correct_answers` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `question_no` int(11) NOT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL,
  `marks` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ctpo_queries`
--

CREATE TABLE `ctpo_queries` (
  `id` int(11) NOT NULL,
  `classid` varchar(50) NOT NULL,
  `ctpo_query` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `replied_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `DBUSES`
--

CREATE TABLE `DBUSES` (
  `ID` int(11) NOT NULL,
  `BUSNAME` varchar(1000) NOT NULL,
  `PASSWORD` varchar(1000) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dip25_feedback`
--

CREATE TABLE `dip25_feedback` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `classid` varchar(50) NOT NULL,
  `english_rating` int(11) NOT NULL,
  `english_remarks` varchar(500) NOT NULL,
  `maths_rating` int(11) NOT NULL,
  `maths_remarks` varchar(500) NOT NULL,
  `physics_rating` int(11) NOT NULL,
  `physics_remarks` varchar(500) NOT NULL,
  `chemistry_rating` int(11) NOT NULL,
  `chemistry_remarks` varchar(500) NOT NULL,
  `bce_rating` int(11) NOT NULL,
  `bce_remarks` varchar(500) NOT NULL,
  `clang_rating` int(11) NOT NULL,
  `clang_remarks` varchar(500) NOT NULL,
  `eg_rating` int(11) NOT NULL,
  `eg_remarks` varchar(500) NOT NULL,
  `other_remark` varchar(500) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_feedback`
--

CREATE TABLE `exam_feedback` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `classid` varchar(50) DEFAULT NULL,
  `branch` varchar(10) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `sem` varchar(10) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `exam_name` varchar(255) DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `remark` varchar(500) DEFAULT NULL,
  `feedback_date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedule`
--

CREATE TABLE `exam_schedule` (
  `id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `branches` varchar(50) NOT NULL,
  `exam_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `sem` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `EXPERTS`
--

CREATE TABLE `EXPERTS` (
  `expert_id` int(11) NOT NULL,
  `expert_name` varchar(200) NOT NULL,
  `expert_qualification` varchar(300) DEFAULT NULL,
  `expert_experience` varchar(300) DEFAULT NULL,
  `expert_from` varchar(300) DEFAULT NULL,
  `expert_phone` varchar(20) DEFAULT NULL,
  `expert_photo` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `FEEDBACK`
--

CREATE TABLE `FEEDBACK` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) DEFAULT NULL,
  `usefulness` int(11) DEFAULT NULL,
  `speed` varchar(20) DEFAULT NULL,
  `confidence` varchar(20) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `difficulty` varchar(20) DEFAULT NULL,
  `strategies` text DEFAULT NULL,
  `trainers_knowledge` int(11) DEFAULT NULL,
  `trainers_explain` int(11) DEFAULT NULL,
  `trainers_helpful` int(11) DEFAULT NULL,
  `guidance` varchar(20) DEFAULT NULL,
  `trainer_like` text DEFAULT NULL,
  `trainer_improve` text DEFAULT NULL,
  `overall_exp` int(11) DEFAULT NULL,
  `format_engage` int(11) DEFAULT NULL,
  `like_most` text DEFAULT NULL,
  `improve_next` text DEFAULT NULL,
  `more_hackathons` varchar(20) DEFAULT NULL,
  `suggestions` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_details_25_02`
--

CREATE TABLE `fee_details_25_02` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `total_due` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_credited` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_fee_due` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fee_return_to_student` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guest_feedback`
--

CREATE TABLE `guest_feedback` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `classid` varchar(50) DEFAULT NULL,
  `session_name` varchar(200) DEFAULT NULL,
  `topic` varchar(300) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `remark` varchar(500) DEFAULT NULL,
  `feedback_date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hackathon_march`
--

CREATE TABLE `hackathon_march` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `interested` enum('yes','no') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `infosys_feedback`
--

CREATE TABLE `infosys_feedback` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `coding_accuracy` int(11) DEFAULT NULL,
  `problem_solving` int(11) DEFAULT NULL,
  `time_management` int(11) DEFAULT NULL,
  `conceptual_clarity` int(11) DEFAULT NULL,
  `application_training` int(11) DEFAULT NULL,
  `comments_a` text DEFAULT NULL,
  `prog_fund_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `prog_fund_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `dsa_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `dsa_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `aptitude_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `aptitude_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `mock_relevance` enum('High','Medium','Low') DEFAULT NULL,
  `mock_preparedness` enum('Excellent','Good','Needs Improvement') DEFAULT NULL,
  `confidence_level` enum('Very Confident','Somewhat Confident','Not Confident') DEFAULT NULL,
  `best_module` text DEFAULT NULL,
  `training_gaps` text DEFAULT NULL,
  `lag_topic` text DEFAULT NULL,
  `overall_readiness` enum('Ready','Almost Ready','Needs Further Preparation') DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kiet_staff`
--

CREATE TABLE `kiet_staff` (
  `EMPID` int(100) NOT NULL,
  `NAME` varchar(1000) NOT NULL,
  `PHNO` varchar(10000) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `ACC_NAME` varchar(1000) DEFAULT NULL,
  `ACCNO` varchar(1000) DEFAULT NULL,
  `IFSC` varchar(1000) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kiot_tour`
--

CREATE TABLE `kiot_tour` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `interested` enum('yes','no') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messfee`
--

CREATE TABLE `messfee` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `ttamt` decimal(10,2) DEFAULT NULL,
  `due` decimal(10,2) DEFAULT NULL,
  `month_year` date NOT NULL,
  `permission` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `MIDS`
--

CREATE TABLE `MIDS` (
  `id` int(11) NOT NULL,
  `prog` varchar(20) NOT NULL,
  `year` varchar(10) NOT NULL,
  `college` set('KIET','KIEK','KIEW') NOT NULL,
  `sem` enum('1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2') NOT NULL,
  `branch` set('AID','CAI','CSD','CSM','CSC','CME') NOT NULL,
  `subject` varchar(100) NOT NULL,
  `mid` tinyint(1) NOT NULL,
  `exam_date` date NOT NULL,
  `marks` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `MID_MARKS`
--

CREATE TABLE `MID_MARKS` (
  `id` int(11) NOT NULL,
  `roll` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `mids_id` int(11) NOT NULL,
  `marks_obtained` varchar(11) NOT NULL,
  `prog` varchar(20) NOT NULL,
  `year` varchar(10) NOT NULL,
  `classid` varchar(20) NOT NULL,
  `college` set('KIET','KIEK','KIEW') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PAYMENTS`
--

CREATE TABLE `PAYMENTS` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `teamid` varchar(50) DEFAULT NULL,
  `paid_tf` decimal(10,2) DEFAULT 0.00,
  `paid_ot` decimal(10,2) DEFAULT 0.00,
  `paid_bus` decimal(10,2) DEFAULT 0.00,
  `paid_hos` decimal(10,2) DEFAULT 0.00,
  `paid_old` decimal(10,2) DEFAULT 0.00,
  `paid_mess` decimal(10,2) DEFAULT 0.00,
  `pay_date` date NOT NULL,
  `receiptno` varchar(50) DEFAULT NULL,
  `method` enum('ONLINE','COUNTER') NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `periods`
--

CREATE TABLE `periods` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `classid` varchar(50) NOT NULL,
  `period_no` tinyint(4) NOT NULL,
  `class` enum('regularclass','expertclass') DEFAULT NULL,
  `classtype` enum('regularclass','test','revision','others') DEFAULT NULL,
  `faculty_name` varchar(100) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `topic_covered` text NOT NULL,
  `classpic` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `period_feedback`
--

CREATE TABLE `period_feedback` (
  `id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review` text DEFAULT NULL,
  `feedback_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `placements`
--

CREATE TABLE `placements` (
  `htno` varchar(20) NOT NULL,
  `tcs_codevita_reg` enum('Yes','No') DEFAULT 'No',
  `tcs_ctdt_id` varchar(50) DEFAULT NULL,
  `infosys_verified` tinyint(1) DEFAULT 0,
  `gate_applied` enum('yes','no') DEFAULT NULL,
  `gate_app_id` varchar(1000) DEFAULT NULL,
  `emailverifys` enum('correct','wrong') DEFAULT NULL,
  `SDET` tinyint(4) DEFAULT NULL,
  `infoedge_selected` enum('Yes','No') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PLACEMENT_COMPANIES`
--

CREATE TABLE `PLACEMENT_COMPANIES` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_location` varchar(255) DEFAULT NULL,
  `company_logo` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PLACEMENT_DETAILS`
--

CREATE TABLE `PLACEMENT_DETAILS` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `placed_company` varchar(255) NOT NULL,
  `placed_company_location` varchar(255) DEFAULT NULL,
  `company_logo` varchar(500) DEFAULT NULL,
  `package` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `REFUND_BANK_ACCOUNTS`
--

CREATE TABLE `REFUND_BANK_ACCOUNTS` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `account_holder_name` varchar(150) DEFAULT NULL,
  `account_number` varchar(30) DEFAULT NULL,
  `ifsc_code` varchar(15) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `REMARKS`
--

CREATE TABLE `REMARKS` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `remark` text NOT NULL,
  `called_no` varchar(20) DEFAULT NULL,
  `remark_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semi_residencial`
--

CREATE TABLE `semi_residencial` (
  `id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `stayinhostel` enum('YES','NO') DEFAULT 'NO'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SEM_SUBJECTS`
--

CREATE TABLE `SEM_SUBJECTS` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `subject_name` varchar(255) NOT NULL,
  `sem` varchar(10) NOT NULL,
  `branch` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `start`
--

CREATE TABLE `start` (
  `id` int(11) NOT NULL,
  `categoryname` varchar(100) NOT NULL,
  `start_stop` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `STUDENTS`
--

CREATE TABLE `STUDENTS` (
  `id` int(11) NOT NULL,
  `prog` varchar(50) DEFAULT NULL,
  `classid` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `branch` varchar(50) DEFAULT NULL,
  `htno` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `firstname` varchar(1000) DEFAULT NULL,
  `middlename` varchar(1000) DEFAULT NULL,
  `lastname` varchar(1000) DEFAULT NULL,
  `gen` enum('M','F') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `teamid` varchar(50) DEFAULT NULL,
  `teamleadno` varchar(50) DEFAULT NULL,
  `spoc` varchar(50) DEFAULT NULL,
  `tfdue_12_9` decimal(10,2) DEFAULT NULL,
  `tfdue_today` decimal(10,2) DEFAULT NULL,
  `otdues_12_9` decimal(10,2) DEFAULT NULL,
  `otdues_today` decimal(10,2) DEFAULT NULL,
  `busdue_12_9` decimal(10,2) DEFAULT NULL,
  `busdue_today` decimal(10,2) DEFAULT NULL,
  `hosdue_12_9` decimal(10,2) DEFAULT NULL,
  `hosdue_today` decimal(10,2) DEFAULT NULL,
  `olddue_12_9` decimal(10,2) DEFAULT NULL,
  `olddue_today` decimal(10,2) DEFAULT NULL,
  `issued_application` tinyint(1) DEFAULT 0,
  `issued_at` datetime DEFAULT NULL,
  `debarred` tinyint(1) DEFAULT 0,
  `EAPCET_NO` varchar(12) DEFAULT NULL,
  `st_phone` varchar(15) DEFAULT NULL,
  `f_phone` varchar(15) DEFAULT NULL,
  `m_phone` varchar(15) DEFAULT NULL,
  `admission_type` enum('EAPCET','SPOT','BCAT','COUNSELLING') DEFAULT NULL,
  `PHASE` enum('1','2','3','SPOT','BCAT') DEFAULT NULL,
  `CLG_TYPE` enum('D','H') DEFAULT NULL,
  `ZONE` varchar(50) DEFAULT NULL,
  `ref` varchar(200) DEFAULT NULL,
  `REFEMPID` int(11) DEFAULT NULL,
  `inter_clg` varchar(300) DEFAULT NULL,
  `inter_clg_loc` varchar(300) DEFAULT NULL,
  `inter_marks` varchar(50) DEFAULT NULL,
  `aadhar` varchar(20) DEFAULT NULL,
  `BUS_CODE` varchar(500) DEFAULT NULL,
  `BUS_STAGE` varchar(500) DEFAULT NULL,
  `HOSTELBLOCK` varchar(150) DEFAULT NULL,
  `HOSTELROOM` varchar(150) DEFAULT NULL,
  `concession_letter` varchar(255) DEFAULT NULL,
  `concession_reason` varchar(500) DEFAULT NULL,
  `concession_remark` varchar(500) DEFAULT NULL,
  `village` varchar(150) DEFAULT NULL,
  `mandal` varchar(150) DEFAULT NULL,
  `dist` varchar(150) DEFAULT NULL,
  `state` varchar(150) DEFAULT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `stpo` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `test_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `question_no` int(11) NOT NULL,
  `selected_option` enum('A','B','C','D') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_marks`
--

CREATE TABLE `student_marks` (
  `id` int(11) NOT NULL,
  `htno` varchar(20) NOT NULL,
  `test_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `prog` varchar(50) NOT NULL,
  `year` varchar(20) NOT NULL,
  `college` set('KIET','KIEK','KIEW') DEFAULT NULL,
  `classid` varchar(50) NOT NULL,
  `faculty_empid` varchar(50) DEFAULT NULL,
  `faculty_name` varchar(100) NOT NULL,
  `faculty_experience` varchar(50) DEFAULT NULL,
  `faculty_phone` varchar(20) DEFAULT NULL,
  `faculty_photo` varchar(500) DEFAULT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `test_id` int(11) NOT NULL,
  `test_name` varchar(100) NOT NULL,
  `total_marks` int(11) NOT NULL,
  `question_paper_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_marks`
--

CREATE TABLE `test_marks` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `htno` varchar(50) NOT NULL,
  `marks_obtained` varchar(10) NOT NULL,
  `entered_by` varchar(50) NOT NULL,
  `entered_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_sections`
--

CREATE TABLE `test_sections` (
  `section_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `section_marks` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `classid` varchar(50) NOT NULL,
  `prog` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `period_no` tinyint(4) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `faculty_name` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `USERS`
--

CREATE TABLE `USERS` (
  `id` int(11) NOT NULL,
  `EMP_ID` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','PR','HTPO','CPTO','TEAM','TPO','ZONE','CALENDAR') DEFAULT NULL,
  `college` set('KIET','KIEK','KIEW') DEFAULT NULL,
  `prog` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `ZONE` varchar(50) DEFAULT NULL,
  `classid` varchar(20) DEFAULT NULL,
  `teamid` varchar(50) DEFAULT NULL,
  `name` varchar(500) DEFAULT NULL,
  `ph_no` varchar(20) DEFAULT NULL,
  `htno` varchar(50) DEFAULT NULL,
  `spoc_name` varchar(50) DEFAULT NULL,
  `spoc_no` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verifys`
--

CREATE TABLE `verifys` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `2infosys_feedback`
--
ALTER TABLE `2infosys_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `3FEEDBACK`
--
ALTER TABLE `3FEEDBACK`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admission_incentives`
--
ALTER TABLE `admission_incentives`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admission_incentives_given`
--
ALTER TABLE `admission_incentives_given`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_htno_date` (`htno`,`att_date`),
  ADD KEY `idx_htno` (`htno`),
  ADD KEY `idx_att_date` (`att_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_classid` (`classid`);

--
-- Indexes for table `attendance_time`
--
ALTER TABLE `attendance_time`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_classid` (`classid`);

--
-- Indexes for table `BUSREG`
--
ALTER TABLE `BUSREG`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `CLASS_CALENDAR`
--
ALTER TABLE `CLASS_CALENDAR`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_calendar_expert` (`expert_id`);

--
-- Indexes for table `CLASS_FEEDBACK`
--
ALTER TABLE `CLASS_FEEDBACK`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UNIQUE_FEEDBACK` (`HTNO`,`CLASS_CALENDAR_ID`);

--
-- Indexes for table `class_test`
--
ALTER TABLE `class_test`
  ADD PRIMARY KEY (`test_id`);

--
-- Indexes for table `class_test_marks`
--
ALTER TABLE `class_test_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_test_student` (`test_id`,`htno`);

--
-- Indexes for table `correct_answers`
--
ALTER TABLE `correct_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_test_section_question` (`test_id`,`section_id`,`question_no`),
  ADD KEY `fk_correct_answers_section` (`section_id`);

--
-- Indexes for table `ctpo_queries`
--
ALTER TABLE `ctpo_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_classid` (`classid`);

--
-- Indexes for table `DBUSES`
--
ALTER TABLE `DBUSES`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `dip25_feedback`
--
ALTER TABLE `dip25_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_feedback` (`htno`);

--
-- Indexes for table `exam_feedback`
--
ALTER TABLE `exam_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `EXPERTS`
--
ALTER TABLE `EXPERTS`
  ADD PRIMARY KEY (`expert_id`);

--
-- Indexes for table `FEEDBACK`
--
ALTER TABLE `FEEDBACK`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_details_25_02`
--
ALTER TABLE `fee_details_25_02`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guest_feedback`
--
ALTER TABLE `guest_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hackathon_march`
--
ALTER TABLE `hackathon_march`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `infosys_feedback`
--
ALTER TABLE `infosys_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kiet_staff`
--
ALTER TABLE `kiet_staff`
  ADD PRIMARY KEY (`EMPID`);

--
-- Indexes for table `kiot_tour`
--
ALTER TABLE `kiot_tour`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messfee`
--
ALTER TABLE `messfee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `MIDS`
--
ALTER TABLE `MIDS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_mid` (`prog`,`year`,`college`,`sem`,`branch`,`subject`,`mid`);

--
-- Indexes for table `MID_MARKS`
--
ALTER TABLE `MID_MARKS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `roll` (`roll`),
  ADD KEY `subject` (`subject`),
  ADD KEY `classid` (`classid`);

--
-- Indexes for table `PAYMENTS`
--
ALTER TABLE `PAYMENTS`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `periods`
--
ALTER TABLE `periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`,`classid`,`period_no`);

--
-- Indexes for table `period_feedback`
--
ALTER TABLE `period_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `placements`
--
ALTER TABLE `placements`
  ADD PRIMARY KEY (`htno`);

--
-- Indexes for table `PLACEMENT_COMPANIES`
--
ALTER TABLE `PLACEMENT_COMPANIES`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `PLACEMENT_DETAILS`
--
ALTER TABLE `PLACEMENT_DETAILS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_htno` (`htno`);

--
-- Indexes for table `REFUND_BANK_ACCOUNTS`
--
ALTER TABLE `REFUND_BANK_ACCOUNTS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_htno` (`htno`);

--
-- Indexes for table `REMARKS`
--
ALTER TABLE `REMARKS`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `semi_residencial`
--
ALTER TABLE `semi_residencial`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `SEM_SUBJECTS`
--
ALTER TABLE `SEM_SUBJECTS`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `start`
--
ALTER TABLE `start`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `STUDENTS`
--
ALTER TABLE `STUDENTS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_htno` (`htno`),
  ADD KEY `idx_classid` (`classid`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_prog` (`prog`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_answer` (`htno`,`test_id`,`section_id`,`question_no`);

--
-- Indexes for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_test_section` (`htno`,`test_id`,`section_id`),
  ADD KEY `fk_student_marks_test` (`test_id`),
  ADD KEY `fk_student_marks_section` (`section_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`test_id`);

--
-- Indexes for table `test_marks`
--
ALTER TABLE `test_marks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `test_sections`
--
ALTER TABLE `test_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `fk_test` (`test_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_slot` (`classid`,`day`,`period_no`),
  ADD UNIQUE KEY `unique_faculty_slot` (`faculty_name`,`day`,`period_no`);

--
-- Indexes for table `USERS`
--
ALTER TABLE `USERS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `verifys`
--
ALTER TABLE `verifys`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `2infosys_feedback`
--
ALTER TABLE `2infosys_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `3FEEDBACK`
--
ALTER TABLE `3FEEDBACK`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_incentives`
--
ALTER TABLE `admission_incentives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_incentives_given`
--
ALTER TABLE `admission_incentives_given`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_time`
--
ALTER TABLE `attendance_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BUSREG`
--
ALTER TABLE `BUSREG`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `CLASS_CALENDAR`
--
ALTER TABLE `CLASS_CALENDAR`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `CLASS_FEEDBACK`
--
ALTER TABLE `CLASS_FEEDBACK`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_test`
--
ALTER TABLE `class_test`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_test_marks`
--
ALTER TABLE `class_test_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `correct_answers`
--
ALTER TABLE `correct_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ctpo_queries`
--
ALTER TABLE `ctpo_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `DBUSES`
--
ALTER TABLE `DBUSES`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dip25_feedback`
--
ALTER TABLE `dip25_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_feedback`
--
ALTER TABLE `exam_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_schedule`
--
ALTER TABLE `exam_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `EXPERTS`
--
ALTER TABLE `EXPERTS`
  MODIFY `expert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `FEEDBACK`
--
ALTER TABLE `FEEDBACK`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_details_25_02`
--
ALTER TABLE `fee_details_25_02`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guest_feedback`
--
ALTER TABLE `guest_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hackathon_march`
--
ALTER TABLE `hackathon_march`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `infosys_feedback`
--
ALTER TABLE `infosys_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kiet_staff`
--
ALTER TABLE `kiet_staff`
  MODIFY `EMPID` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kiot_tour`
--
ALTER TABLE `kiot_tour`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messfee`
--
ALTER TABLE `messfee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `MIDS`
--
ALTER TABLE `MIDS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `MID_MARKS`
--
ALTER TABLE `MID_MARKS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PAYMENTS`
--
ALTER TABLE `PAYMENTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `periods`
--
ALTER TABLE `periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `period_feedback`
--
ALTER TABLE `period_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PLACEMENT_COMPANIES`
--
ALTER TABLE `PLACEMENT_COMPANIES`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PLACEMENT_DETAILS`
--
ALTER TABLE `PLACEMENT_DETAILS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `REFUND_BANK_ACCOUNTS`
--
ALTER TABLE `REFUND_BANK_ACCOUNTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `REMARKS`
--
ALTER TABLE `REMARKS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semi_residencial`
--
ALTER TABLE `semi_residencial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `SEM_SUBJECTS`
--
ALTER TABLE `SEM_SUBJECTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `start`
--
ALTER TABLE `start`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `STUDENTS`
--
ALTER TABLE `STUDENTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_marks`
--
ALTER TABLE `test_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_sections`
--
ALTER TABLE `test_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `USERS`
--
ALTER TABLE `USERS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verifys`
--
ALTER TABLE `verifys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `correct_answers`
--
ALTER TABLE `correct_answers`
  ADD CONSTRAINT `fk_correct_answers_section` FOREIGN KEY (`section_id`) REFERENCES `test_sections` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_correct_answers_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD CONSTRAINT `fk_student_marks_section` FOREIGN KEY (`section_id`) REFERENCES `test_sections` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student_marks_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE;

--
-- Constraints for table `test_sections`
--
ALTER TABLE `test_sections`
  ADD CONSTRAINT `fk_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
