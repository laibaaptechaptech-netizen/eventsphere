-- EventSphere Complete Database Schema & Seed Data
-- Character Set: utf8mb4

CREATE DATABASE IF NOT EXISTS `eventsphere_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `eventsphere_db`;

-- 1. Users Table
DROP TABLE IF EXISTS `saved_media`;
DROP TABLE IF EXISTS `event_bookmarks`;
DROP TABLE IF EXISTS `feedback_reviews`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `media_gallery`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `venues`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `contact_inquiries`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `contact` VARCHAR(20) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT 'General',
  `enrolment_no` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('admin', 'organizer', 'student') NOT NULL DEFAULT 'student',
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `two_factor_code` VARCHAR(10) DEFAULT '123456',
  `avatar` VARCHAR(255) DEFAULT 'assets/images/default_avatar.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-calendar',
  `badge_color` VARCHAR(50) DEFAULT 'cyan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Venues Table
CREATE TABLE `venues` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `building` VARCHAR(100) DEFAULT NULL,
  `location_details` TEXT DEFAULT NULL,
  `max_capacity` INT NOT NULL DEFAULT 100,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Events Table
CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) UNIQUE NOT NULL,
  `category_id` INT NOT NULL,
  `organizer_id` INT NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `venue_id` INT DEFAULT NULL,
  `custom_venue_name` VARCHAR(150) DEFAULT NULL,
  `event_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `registration_cutoff` DATETIME NOT NULL,
  `max_capacity` INT NOT NULL DEFAULT 50,
  `fee_amount` DECIMAL(10,2) DEFAULT 0.00,
  `certificate_fee` DECIMAL(10,2) DEFAULT 150.00,
  `banner_image` VARCHAR(255) DEFAULT NULL,
  `rulebook_file` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Registrations Table
CREATE TABLE `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `registration_code` VARCHAR(50) UNIQUE NOT NULL,
  `status` ENUM('confirmed', 'waitlisted', 'cancelled', 'attended') NOT NULL DEFAULT 'confirmed',
  `qr_token` VARCHAR(100) UNIQUE NOT NULL,
  `checked_in_at` DATETIME DEFAULT NULL,
  `certificate_issued` TINYINT(1) NOT NULL DEFAULT 0,
  `certificate_fee_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `certificate_fee_txn` VARCHAR(100) DEFAULT NULL,
  `certificate_code` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_event` (`event_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Feedback Reviews Table
CREATE TABLE `feedback_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `user_type` ENUM('Participant', 'Volunteer', 'Guest Student', 'Faculty') NOT NULL DEFAULT 'Participant',
  `overall_rating` INT NOT NULL CHECK (`overall_rating` BETWEEN 1 AND 5),
  `rating_venue` INT NOT NULL DEFAULT 5,
  `rating_coordination` INT NOT NULL DEFAULT 5,
  `rating_technical` INT NOT NULL DEFAULT 5,
  `rating_hospitality` INT NOT NULL DEFAULT 5,
  `comments` TEXT DEFAULT NULL,
  `suggestions` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Media Gallery Table
CREATE TABLE `media_gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT DEFAULT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `media_type` ENUM('image', 'video') NOT NULL DEFAULT 'image',
  `media_url` VARCHAR(255) NOT NULL,
  `thumbnail_url` VARCHAR(255) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT 'General',
  `academic_year` VARCHAR(20) DEFAULT '2025-2026',
  `uploaded_by` INT NOT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Saved Media Table
CREATE TABLE `saved_media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `media_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`media_id`) REFERENCES `media_gallery`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_saved_media` (`user_id`, `media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Event Bookmarks Table
CREATE TABLE `event_bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_event_bookmark` (`user_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Announcements Table
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `target_role` ENUM('all', 'student', 'organizer', 'event_registrants') NOT NULL DEFAULT 'all',
  `event_id` INT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT '#',
  `type` ENUM('info', 'alert', 'success', 'event_update') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Contact Inquiries Table
CREATE TABLE `contact_inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) DEFAULT 'General Inquiry',
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'replied') NOT NULL DEFAULT 'new',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. System Settings Table
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) UNIQUE NOT NULL,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Seed Users (Passwords: Admin@123, Organizer@123, Student@123)
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password`, `contact`, `department`, `enrolment_no`, `role`, `status`, `two_factor_code`, `avatar`) VALUES
(1, 'System Administrator', 'admin@eventsphere.edu', 'admin', '$2y$10$6FEPeLvh8tnJr5y4h3iJH.IrG4Vl4j1XUVdh3rxI2PFK8vU5aqcsu', '+1-555-0199', 'Central Administration', 'ADM-001', 'admin', 'active', '123456', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'),
(2, 'Prof. Alexander Wright', 'cs.organizer@eventsphere.edu', 'alexander_w', '$2y$10$o0MjlVAyRznXYPlJnYeVruOun9yueL5r/C9hFTu6XUBb5FYfNLPXi', '+1-555-0142', 'Computer Science & Engineering', 'FAC-CS-104', 'organizer', 'active', '123456', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80'),
(3, 'Dr. Elena Rostova', 'cultural.organizer@eventsphere.edu', 'elena_r', '$2y$10$o0MjlVAyRznXYPlJnYeVruOun9yueL5r/C9hFTu6XUBb5FYfNLPXi', '+1-555-0188', 'Arts & Humanities', 'FAC-ART-209', 'organizer', 'active', '123456', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=200&q=80'),
(4, 'John Doe', 'john.doe@eventsphere.edu', 'johndoe', '$2y$10$FZQ39b6aKIx5/EAzI/gE6OgjcRkR/1pAzOWwJVZ0s3xc9WCxoQaa2', '+1-555-0111', 'Computer Science & Engineering', 'EN2024-CS-042', 'student', 'active', '123456', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=200&q=80'),
(5, 'Jane Smith', 'jane.smith@eventsphere.edu', 'janesmith', '$2y$10$FZQ39b6aKIx5/EAzI/gE6OgjcRkR/1pAzOWwJVZ0s3xc9WCxoQaa2', '+1-555-0222', 'Information Technology', 'EN2024-IT-108', 'student', 'active', '123456', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=200&q=80'),
(6, 'Michael Chang', 'michael.chang@eventsphere.edu', 'mchang', '$2y$10$FZQ39b6aKIx5/EAzI/gE6OgjcRkR/1pAzOWwJVZ0s3xc9WCxoQaa2', '+1-555-0333', 'Mechanical Engineering', 'EN2024-ME-021', 'student', 'active', '123456', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=200&q=80');

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `badge_color`) VALUES
(1, 'Technical Fests', 'technical-fests', 'Coding hackathons, AI showcases, robotics, and cyber defense tournaments.', 'fa-laptop-code', 'cyan'),
(2, 'Cultural Events', 'cultural-events', 'Music fests, drama showcases, fashion runways, and dance spectacles.', 'fa-masks-theater', 'purple'),
(3, 'Sports Meets', 'sports-meets', 'Track and field, esports leagues, basketball, and football tournaments.', 'fa-volleyball', 'emerald'),
(4, 'Annual Day Functions', 'annual-day', 'College convocation, excellence awards gala, and presidential ceremonies.', 'fa-award', 'amber'),
(5, 'Workshops & Seminars', 'workshops-seminars', 'Hands-on technical bootcamps, executive talks, and faculty masterclasses.', 'fa-chalkboard-user', 'blue'),
(6, 'Intercollegiate Competitions', 'intercollegiate', 'Pan-university battle of minds, debates, and grand innovation challenges.', 'fa-trophy', 'rose');

-- Seed Venues
INSERT INTO `venues` (`id`, `name`, `building`, `location_details`, `max_capacity`, `is_active`) VALUES
(1, 'Cyberdome Quantum Auditorium', 'Tech Nexus Tower', 'Ground Floor, North Atrium with 8K Holographic Displays', 250, 1),
(2, 'Grand Amphitheatre & Quad', 'Arts & Culture Complex', 'Central Campus Courtyard, Open-air acoustic stage', 600, 1),
(3, 'Innovation AI Research Lab', 'Computing Sciences Wing', '4th Floor, High Performance Cluster Lab', 40, 1),
(4, 'Olympic Indoor Sports Arena', 'Sports Complex', 'Building C, Multi-court hardwood arena', 450, 1),
(5, 'Executive Seminar Hall Alpha', 'Chancellor Pavilion', 'Level 2, Dual Dolby Atmos surround sound', 120, 1);

-- Seed Events
INSERT INTO `events` (`id`, `title`, `slug`, `category_id`, `organizer_id`, `department`, `description`, `venue_id`, `custom_venue_name`, `event_date`, `start_time`, `end_time`, `registration_cutoff`, `max_capacity`, `fee_amount`, `certificate_fee`, `banner_image`, `rulebook_file`, `status`, `admin_notes`, `featured`, `created_at`) VALUES
(1, 'HackNova 2026: 36-Hour AI & Quantum Hackathon', 'hacknova-2026-ai-quantum-hackathon', 1, 2, 'Computer Science & Engineering', 'Join over 250 top developers, researchers, and innovators for an intense 36-hour sprint into Generative AI, Quantum Computing algorithms, and Autonomous Systems. Compete for $15,000 in cash prizes, direct venture capital mentorship, and exclusive industry sponsorships. Food, swag, 24/7 Red Bull bar, and computing clusters provided.', 1, 'Cyberdome Quantum Auditorium', '2026-09-15', '09:00:00', '21:00:00', '2026-09-14 23:59:59', 5, 0.00, 150.00, 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80', 'uploads/rules/hacknova_rules.pdf', 'approved', 'Verified by Admin. High priority flagship event.', 1, '2026-08-01 10:00:00'),
(2, 'Symphonia 2026: Inter-University Cultural Carnival', 'symphonia-2026-cultural-carnival', 2, 3, 'Arts & Humanities', 'The biggest cultural extravaganza of the academic calendar! Three days of non-stop electrifying musical concerts, battle of the bands, cinematic drama, couture fashion walks, and culinary street stalls. Featuring celebrity headliners and performers from across 40 universities nationwide.', 2, 'Grand Amphitheatre & Quad', '2026-09-22', '16:00:00', '23:30:00', '2026-09-21 18:00:00', 500, 0.00, 100.00, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1200&q=80', 'uploads/rules/symphonia_guidelines.pdf', 'approved', 'Approved. Sound permits verified.', 1, '2026-08-05 11:30:00'),
(3, 'RoboClash: Autonomous Combat & Drone Racing Championship', 'roboclash-autonomous-combat-championship', 6, 2, 'Robotics & Mechanical', 'Witness raw sparks and adrenaline as 30lb combat robots clash inside the bulletproof steel arena, alongside high-speed FPV autonomous drone racing obstacles. Open to all engineering disciplines. Strict safety inspection guidelines apply.', 3, 'Innovation AI Research Lab', '2026-10-05', '10:00:00', '18:00:00', '2026-10-04 18:00:00', 35, 200.00, 150.00, 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1200&q=80', 'uploads/rules/roboclash_specs.pdf', 'approved', 'Safety cage inspection completed.', 1, '2026-08-10 14:00:00'),
(4, 'Deep Learning & Cloud Native Architecture Masterclass', 'deep-learning-cloud-native-masterclass', 5, 2, 'Computer Science & Engineering', 'An intensive technical workshop hosted by Senior Cloud Architects from Google Cloud & NVIDIA. Learn state-of-the-art model deployment, Kubernetes cluster orchestration, TensorRT acceleration, and scalable vector databases.', 5, 'Executive Seminar Hall Alpha', '2026-09-02', '13:00:00', '17:30:00', '2026-09-01 12:00:00', 80, 0.00, 200.00, 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&q=80', 'uploads/rules/workshop_syllabus.pdf', 'approved', 'Approved by Dean of Academics.', 0, '2026-08-12 09:00:00'),
(5, 'Campus Olympic Games 2026: Track, Field & Esports', 'campus-olympic-games-2026', 3, 3, 'Physical Education', 'Annual Inter-Departmental Sports Olympiad featuring 100m sprint, relay, basketball championship, badminton singles, and official Valorant & FIFA 2026 esports tournaments.', 4, 'Olympic Indoor Sports Arena', '2026-10-18', '08:00:00', '20:00:00', '2026-10-16 23:59:59', 300, 0.00, 100.00, 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1200&q=80', 'uploads/rules/olympics_rulebook.pdf', 'approved', 'Sports committee approved.', 0, '2026-08-15 15:00:00'),
(6, 'TechWizz Legacy Gala & Academic Excellence Honors 2025', 'techwizz-legacy-gala-2025', 4, 2, 'Central Administration', 'The prestigious annual convocation and awards night celebrating student achievements, research patent publications, and alumni milestones. Formal black-tie gala banquet with live orchestra.', 1, 'Cyberdome Quantum Auditorium', '2026-05-20', '18:00:00', '22:00:00', '2026-05-19 18:00:00', 250, 0.00, 0.00, 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1200&q=80', 'uploads/rules/gala_agenda.pdf', 'completed', 'Past annual showcase.', 0, '2026-05-01 10:00:00'),
(7, 'CyberGuard: Defensive Security & Red Team Warfare', 'cyberguard-defensive-security-warfare', 1, 2, 'Cyber Security', 'Real-time live Capture The Flag (CTF) tournament pitting blue team defenders against red team offensive cyber specialists. Live scoreboards and exploit challenges.', 3, 'Innovation AI Research Lab', '2026-11-10', '10:00:00', '19:00:00', '2026-11-09 18:00:00', 40, 0.00, 150.00, 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=1200&q=80', 'uploads/rules/ctf_rules.pdf', 'pending', 'Submitted for Admin Approval. Please verify lab connectivity.', 0, '2026-08-20 16:20:00');

-- Seed Registrations
INSERT INTO `registrations` (`id`, `event_id`, `user_id`, `registration_code`, `status`, `qr_token`, `checked_in_at`, `certificate_issued`, `certificate_fee_paid`, `certificate_fee_txn`, `certificate_code`, `created_at`) VALUES
(1, 1, 4, 'REG-HN26-4091', 'confirmed', 'QR_ES_HN26_USER4_A918B2', NULL, 0, 0, NULL, NULL, '2026-08-20 11:00:00'),
(2, 2, 4, 'REG-SYM26-7721', 'confirmed', 'QR_ES_SYM26_USER4_D419C8', NULL, 0, 0, NULL, NULL, '2026-08-21 09:30:00'),
(3, 6, 4, 'REG-TWG25-1022', 'attended', 'QR_ES_TWG25_USER4_E812F9', '2026-05-20 18:15:00', 1, 1, 'TXN_UPI_99281726351', 'CERT-ESP-2025-04291', '2026-05-10 14:00:00'),
(4, 1, 5, 'REG-HN26-5082', 'confirmed', 'QR_ES_HN26_USER5_C714E3', NULL, 0, 0, NULL, NULL, '2026-08-22 14:15:00'),
(5, 6, 5, 'REG-TWG25-1023', 'attended', 'QR_ES_TWG25_USER5_F934A1', '2026-05-20 18:20:00', 1, 1, 'TXN_CARD_8817263910', 'CERT-ESP-2025-04292', '2026-05-11 16:00:00'),
(6, 1, 6, 'REG-HN26-6019', 'confirmed', 'QR_ES_HN26_USER6_B390D5', NULL, 0, 0, NULL, NULL, '2026-08-23 10:45:00');

-- Seed Feedback Reviews
INSERT INTO `feedback_reviews` (`id`, `event_id`, `user_id`, `user_type`, `overall_rating`, `rating_venue`, `rating_coordination`, `rating_technical`, `rating_hospitality`, `comments`, `suggestions`, `is_approved`, `created_at`) VALUES
(1, 6, 4, 'Participant', 5, 5, 5, 5, 5, 'An outstanding, flawlessly organized Gala! The keynote presentations, live holographic visuals, and networking sessions were world-class.', 'Would love more interactive breakout sessions next year!', 1, '2026-05-21 10:30:00'),
(2, 6, 5, 'Volunteer', 5, 5, 4, 5, 5, 'Incredible atmosphere and flawless execution by the student council & faculty coordinators.', 'Extend the evening networking dinner by 30 minutes.', 1, '2026-05-21 11:45:00'),
(3, 4, 4, 'Participant', 5, 5, 5, 5, 4, 'The practical hands-on labs with Kubernetes and GPU computing were exceptionally insightful.', 'Provide downloadable Docker images prior to the session.', 1, '2026-08-23 18:00:00');

-- Seed Media Gallery
INSERT INTO `media_gallery` (`id`, `event_id`, `category_id`, `title`, `media_type`, `media_url`, `thumbnail_url`, `department`, `academic_year`, `uploaded_by`, `is_approved`, `created_at`) VALUES
(1, 6, 4, 'Grand Presidential Keynote & Holographic Visuals', 'image', 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1200&q=80', 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=600&q=80', 'Central Administration', '2025-2026', 1, 1, '2026-05-21 12:00:00'),
(2, 1, 1, 'HackNova Midnight Sprint & Live Mentorship Round', 'image', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80', 'Computer Science & Engineering', '2025-2026', 2, 1, '2026-06-10 14:30:00'),
(3, 2, 2, 'Symphonia Live Orchestra & Rock Band Showdown', 'image', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1200&q=80', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=600&q=80', 'Arts & Humanities', '2025-2026', 3, 1, '2026-06-15 17:00:00'),
(4, 3, 6, 'RoboClash Heavyweight Cage Match Finals', 'image', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1200&q=80', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=600&q=80', 'Robotics & Mechanical', '2025-2026', 2, 1, '2026-07-01 11:20:00'),
(5, 5, 3, 'Inter-College Basketball Championship Final Seconds', 'image', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1200&q=80', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=600&q=80', 'Physical Education', '2025-2026', 3, 1, '2026-07-15 16:45:00'),
(6, 4, 5, 'AI Deep Learning Lab Hands-on Coding Session', 'image', 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&q=80', 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=600&q=80', 'Computer Science & Engineering', '2025-2026', 2, 1, '2026-07-20 18:10:00');

-- Seed Saved Media
INSERT INTO `saved_media` (`id`, `user_id`, `media_id`, `created_at`) VALUES
(1, 4, 1, '2026-08-20 15:00:00'),
(2, 4, 3, '2026-08-21 16:30:00');

-- Seed Event Bookmarks
INSERT INTO `event_bookmarks` (`id`, `user_id`, `event_id`, `created_at`) VALUES
(1, 4, 3, '2026-08-22 10:00:00'),
(2, 5, 2, '2026-08-22 11:15:00');

-- Seed Announcements
INSERT INTO `announcements` (`id`, `title`, `content`, `target_role`, `event_id`, `created_by`, `is_active`, `created_at`) VALUES
(1, '🔥 HackNova 2026 Registration Nearing Full Capacity!', 'Only 2 general developer slots remain for HackNova 2026. Waitlist will automatically open once slots are filled. Bring your student IDs for swift check-in.', 'all', 1, 1, 1, '2026-08-23 09:00:00'),
(2, '⚡ Symphonia 2026 Main Stage Soundcheck & Rehearsals', 'All registered participants and cultural performers are requested to report to the Grand Amphitheatre at 2:00 PM for acoustic calibration.', 'student', 2, 3, 1, '2026-08-23 14:00:00'),
(3, '🏆 TechWizz E-Certificates Available for Download', 'Participants of TechWizz Legacy Gala 2025 can now verify and download their official digital tamper-proof certificates from their Student Hub.', 'student', 6, 1, 1, '2026-08-24 08:30:00');

-- Seed Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `type`, `is_read`, `created_at`) VALUES
(1, 4, 'Registration Confirmed: HackNova 2026', 'Your seat for HackNova 2026 is confirmed! Pass code: REG-HN26-4091.', 'student/my_events.php', 'success', 0, '2026-08-20 11:00:05'),
(2, 4, 'Certificate Available!', 'Your certificate for TechWizz Legacy Gala 2025 is ready for download.', 'student/certificates.php', 'info', 0, '2026-08-24 08:35:00'),
(3, 5, 'Registration Confirmed: HackNova 2026', 'Your seat for HackNova 2026 is confirmed! Pass code: REG-HN26-5082.', 'student/my_events.php', 'success', 1, '2026-08-22 14:15:05');

-- Seed Contact Inquiries
INSERT INTO `contact_inquiries` (`id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Dr. Robert Vance', 'vance.r@partner-uni.edu', 'Intercollegiate Sponsorship & Judge Panel', 'We would like to send a contingent of 15 students for the HackNova AI hackathon and sponsor a $2,000 track prize. Please share coordinator details.', 'new', '2026-08-23 11:20:00');

-- Seed System Settings
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'site_name', 'EventSphere 3D'),
(2, 'site_tagline', 'The Ultimate Next-Gen Campus Event Experience'),
(3, 'default_hashtags', '#EventSphere #CampusLife #InnovateCreate #TechFest2026 #Symphonia2026'),
(4, 'system_timezone', 'Asia/Kolkata'),
(5, '2fa_enabled', '1'),
(6, 'maintenance_mode', '0'),
(7, 'contact_email', 'support@eventsphere.edu'),
(8, 'contact_phone', '+1 (800) 555-SPHERE');
