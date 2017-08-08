SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


-- -----------------------------------------------------
-- Table `represent_test`.`test3`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `represent_test`.`test3` ;

CREATE TABLE IF NOT EXISTS `represent_test`.`test3` (
  `id` INT NOT NULL,
  `col3` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `represent_test`.`test1`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `represent_test`.`test1` ;

CREATE TABLE IF NOT EXISTS `represent_test`.`test1` (
  `id` INT NOT NULL,
  `col1` VARCHAR(45) NULL,
  `test3_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_map1_map31_idx` (`test3_id` ASC),
  CONSTRAINT `fk_map1_map31`
  FOREIGN KEY (`test3_id`)
  REFERENCES `represent_test`.`test3` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `represent_test`.`test2`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `represent_test`.`test2` ;

CREATE TABLE IF NOT EXISTS `represent_test`.`test2` (
  `id` INT NOT NULL,
  `col2` VARCHAR(45) NULL,
  `test1_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_map2_map11_idx` (`test1_id` ASC),
  CONSTRAINT `fk_map2_map11`
  FOREIGN KEY (`test1_id`)
  REFERENCES `represent_test`.`test1` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `represent_test`.`test5`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `represent_test`.`test5` ;

CREATE TABLE IF NOT EXISTS `represent_test`.`test5` (
  `id` INT NOT NULL,
  `col` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `represent_test`.`test4`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `represent_test`.`test4` ;

CREATE TABLE IF NOT EXISTS `represent_test`.`test4` (
  `id` INT NOT NULL,
  `col4` VARCHAR(45) NULL,
  `test5_id` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test4_test51_idx` (`test5_id` ASC),
  CONSTRAINT `fk_test4_test51`
  FOREIGN KEY (`test5_id`)
  REFERENCES `represent_test`.`test5` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `represent_test`.`test2_has_test4`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `represent_test`.`test2_has_test4` ;

CREATE TABLE IF NOT EXISTS `represent_test`.`test2_has_test4` (
  `test2_id` INT NOT NULL,
  `test4_id` INT NOT NULL,
  PRIMARY KEY (`test2_id`, `test4_id`),
  INDEX `fk_map2_has_map4_map41_idx` (`test4_id` ASC),
  INDEX `fk_map2_has_map4_map21_idx` (`test2_id` ASC),
  CONSTRAINT `fk_map2_has_map4_map21`
  FOREIGN KEY (`test2_id`)
  REFERENCES `represent_test`.`test2` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_map2_has_map4_map41`
  FOREIGN KEY (`test4_id`)
  REFERENCES `represent_test`.`test4` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `represent_test`.`test6`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `represent_test`.`test6` ;

CREATE TABLE IF NOT EXISTS `represent_test`.`test6` (
  `id` INT NOT NULL,
  `col` VARCHAR(45) NULL,
  `test5_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test6_test51_idx` (`test5_id` ASC),
  CONSTRAINT `fk_test6_test51`
  FOREIGN KEY (`test5_id`)
  REFERENCES `represent_test`.`test5` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


# DELETE FROM `test3`;
# DELETE FROM `test1`;
# DELETE FROM `test2_has_test4`;
# DELETE FROM `test2`;
# DELETE FROM `test4`;
# DELETE FROM `test5`;
# DELETE FROM `test6`;
# DELETE FROM `test7`;


INSERT INTO `represent_test`.`test3` (`id`, `col3`) VALUES ('1', 'q');
INSERT INTO `represent_test`.`test3` (`id`, `col3`) VALUES ('2', 'w');
INSERT INTO `represent_test`.`test3` (`id`, `col3`) VALUES ('3', 'e');

INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('1', 'a', 1);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('2', 's', 1);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('3', 'd', 1);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('4', 'f', 2);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('5', 'g', 2);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('6', 'h', 2);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('7', 'j', 3);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('8', 'k', 3);
INSERT INTO `represent_test`.`test1` (`id`, `col1`, `test3_id`) VALUES ('9', 'l', 3);

INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('1', 'z', 1);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('2', 'x', 1);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('3', 'c', 1);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('4', 'v', 2);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('5', 'b', 2);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('6', 'n', 2);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('7', 'm', 3);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('8', ',', 3);
INSERT INTO `represent_test`.`test2` (`id`, `col2`, `test1_id`) VALUES ('9', '.', 3);


INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('1', '1');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('2', '2');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('3', '3');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('4', '4');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('5', '5');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('6', '6');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('7', '7');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('8', '8');
INSERT INTO `represent_test`.`test5` (`id`, `col`) VALUES ('9', '9');

INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('1', '1','1');
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('2', '2',NULL );
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('3', '3',NULL);
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('4', '4',NULL);
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('5', '5','2');
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('6', '6','2');
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('7', '7','3');
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('8', '8','3');
INSERT INTO `represent_test`.`test4` (`id`, `col4`, `test5_id`) VALUES ('9', '9','4');

INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('1', '1');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('1', '2');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('2', '3');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('2', '4');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('3', '5');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('3', '6');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('4', '7');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('4', '8');
INSERT INTO `represent_test`.`test2_has_test4` (`test2_id`, `test4_id`) VALUES ('5', '9');

INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('1', '1', '1');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('2', '2', '1');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('3', '3', '2');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('4', '4', '2');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('5', '5', '3');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('6', '6', '3');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('7', '7', '4');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('8', '8', '4');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('9', '9', '5');
INSERT INTO `represent_test`.`test6` (`id`, `col`, `test5_id`) VALUES ('10', '10', '9');

INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('1', '1', '1');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('2', '2', '1');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('3', '3', '2');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('4', '4', '2');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('5', '5', '3');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('6', '6', '3');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('7', '7', '4');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('8', '8', '4');
INSERT INTO `represent_test`.`test7` (`id`, `col`, `test6_id`) VALUES ('9', '9', '5');