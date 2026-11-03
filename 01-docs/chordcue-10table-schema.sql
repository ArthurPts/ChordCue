-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema chordcue
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema chordcue
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `chordcue` DEFAULT CHARACTER SET utf8mb4 ;
USE `chordcue` ;

-- -----------------------------------------------------
-- Table `chordcue`.`artists`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`artists` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 9
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`categories`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`categories` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `type` ENUM('mood', 'location', 'genre') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `categories_name_unique` (`name` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 19
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`users` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(120) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `song_limit` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `remember_token` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `users_username_unique` (`username` ASC),
  UNIQUE INDEX `users_email_unique` (`email` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 12
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`songs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`songs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `artist_id` BIGINT(20) UNSIGNED NOT NULL,
  `default_key` VARCHAR(45) NULL DEFAULT NULL,
  `bpm` SMALLINT(6) UNSIGNED NOT NULL DEFAULT 80,
  `time_signature_numerator` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `time_signature_denominator` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `backing_track_path` VARCHAR(255) NULL DEFAULT NULL,
  `backing_track_offset` DECIMAL(6,3) NULL,
  `visibility` ENUM('public', 'private') NOT NULL DEFAULT 'private',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `songs_user_id_foreign` (`user_id` ASC),
  INDEX `songs_artist_id_foreign` (`artist_id` ASC),
  CONSTRAINT `songs_artist_id_foreign`
    FOREIGN KEY (`artist_id`)
    REFERENCES `chordcue`.`artists` (`id`),
  CONSTRAINT `songs_user_id_foreign`
    FOREIGN KEY (`user_id`)
    REFERENCES `chordcue`.`users` (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`category_song`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`category_song` (
  `category_id` BIGINT(20) UNSIGNED NOT NULL,
  `song_id` BIGINT(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`category_id`, `song_id`),
  INDEX `category_song_song_id_foreign` (`song_id` ASC),
  CONSTRAINT `category_song_category_id_foreign`
    FOREIGN KEY (`category_id`)
    REFERENCES `chordcue`.`categories` (`id`),
  CONSTRAINT `category_song_song_id_foreign`
    FOREIGN KEY (`song_id`)
    REFERENCES `chordcue`.`songs` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`chords`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`chords` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `pronunciation` VARCHAR(120) NOT NULL,
  `file_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `chords_name_unique` (`name` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 31
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`song_sections`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`song_sections` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `song_id` BIGINT(20) UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sequence` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `song_sections_song_id_foreign` (`song_id` ASC),
  CONSTRAINT `song_sections_song_id_foreign`
    FOREIGN KEY (`song_id`)
    REFERENCES `chordcue`.`songs` (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 15
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`lyric_lines`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`lyric_lines` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `song_section_id` BIGINT(20) UNSIGNED NOT NULL,
  `line_number` INT NOT NULL,
  `content` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `lyric_lines_section_id_foreign` (`song_section_id` ASC),
  CONSTRAINT `lyric_lines_section_id_foreign`
    FOREIGN KEY (`song_section_id`)
    REFERENCES `chordcue`.`song_sections` (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 52
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`chord_placements`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`chord_placements` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lyric_line_id` BIGINT(20) UNSIGNED NOT NULL,
  `chord_id` BIGINT(20) UNSIGNED NOT NULL,
  `position` INT(11) NULL,
  `start_beat` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `chord_placements_lyric_line_id_foreign` (`lyric_line_id` ASC),
  INDEX `chord_placements_chord_id_foreign` (`chord_id` ASC),
  CONSTRAINT `chord_placements_chord_id_foreign`
    FOREIGN KEY (`chord_id`)
    REFERENCES `chordcue`.`chords` (`id`),
  CONSTRAINT `chord_placements_lyric_line_id_foreign`
    FOREIGN KEY (`lyric_line_id`)
    REFERENCES `chordcue`.`lyric_lines` (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 107
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `chordcue`.`chord_diagrams`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chordcue`.`chord_diagrams` (
  `id` BIGINT(20) NOT NULL,
  `chord_id` BIGINT(20) UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_chord_diagrams_chords1_idx` (`chord_id` ASC),
  CONSTRAINT `fk_chord_diagrams_chords1`
    FOREIGN KEY (`chord_id`)
    REFERENCES `chordcue`.`chords` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
