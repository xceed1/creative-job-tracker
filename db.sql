/* =====================================================
   1. FIXED: JOB STATUS SEQUENCE (SAFE ORDER)
===================================================== */

START TRANSACTION;

ALTER TABLE job_status
  ADD COLUMN sequence INT NULL AFTER status_name;

UPDATE job_status SET sequence = 1 WHERE status_code = 'NEW';
UPDATE job_status SET sequence = 2 WHERE status_code = 'DISPATCHED';
UPDATE job_status SET sequence = 3 WHERE status_code = 'IN_PROGRESS';
UPDATE job_status SET sequence = 4 WHERE status_code = 'COMPLETED';
UPDATE job_status SET sequence = 5 WHERE status_code = 'REJECTED';
UPDATE job_status SET sequence = 6 WHERE status_code = 'APPROVED';
UPDATE job_status SET sequence = 7 WHERE status_code = 'RELEASED';

ALTER TABLE job_status
  MODIFY sequence INT NOT NULL;

ALTER TABLE job_status
  ADD UNIQUE KEY uq_job_status_sequence (sequence);

COMMIT;


/* =====================================================
   2. TIGHTEN job_orders (MATCH ACTUAL CODE RULES)
===================================================== */

START TRANSACTION;

ALTER TABLE job_orders
  MODIFY job_subject VARCHAR(255) NOT NULL,
  MODIFY client_name VARCHAR(255) NOT NULL,
  MODIFY received_format VARCHAR(255) NULL,
  MODIFY created_by INT(11) NOT NULL,
  MODIFY requesting_department_id INT(11) NOT NULL,
  MODIFY status_id INT(11) NOT NULL,
  MODIFY is_locked TINYINT(1) NOT NULL DEFAULT 0;

COMMIT;


/* =====================================================
   3. system_settings: ENFORCE TYPES CONSISTENCY
===================================================== */

ALTER TABLE system_settings
  MODIFY setting_type
    ENUM('string','int','boolean','email','url')
    NOT NULL DEFAULT 'string';


/* =====================================================
   4. FK BEHAVIOR HARDENING (NO SILENT DATA LOSS)
===================================================== */

-- users
ALTER TABLE users
  DROP FOREIGN KEY users_ibfk_1,
  DROP FOREIGN KEY users_ibfk_2;

ALTER TABLE users
  ADD CONSTRAINT fk_users_role
    FOREIGN KEY (role_id)
    REFERENCES roles(id)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  ADD CONSTRAINT fk_users_department
    FOREIGN KEY (department_id)
    REFERENCES departments(id)
    ON DELETE SET NULL
    ON UPDATE RESTRICT;

-- job_orders
ALTER TABLE job_orders
  DROP FOREIGN KEY job_orders_ibfk_1,
  DROP FOREIGN KEY job_orders_ibfk_2,
  DROP FOREIGN KEY job_orders_ibfk_3;

ALTER TABLE job_orders
  ADD CONSTRAINT fk_job_department
    FOREIGN KEY (requesting_department_id)
    REFERENCES departments(id)
    ON DELETE RESTRICT,
  ADD CONSTRAINT fk_job_creator
    FOREIGN KEY (created_by)
    REFERENCES users(id)
    ON DELETE RESTRICT,
  ADD CONSTRAINT fk_job_status
    FOREIGN KEY (status_id)
    REFERENCES job_status(id)
    ON DELETE RESTRICT;


/* =====================================================
   5. BUSINESS RULE TRIGGERS (MARIA DB)
===================================================== */

DELIMITER $$

CREATE TRIGGER trg_assignment_completed_requires_link
BEFORE UPDATE ON job_assignments
FOR EACH ROW
BEGIN
  IF NEW.assignment_status = 'COMPLETED'
     AND (NEW.completion_link IS NULL OR NEW.completion_link = '') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'COMPLETED assignment requires completion_link';
  END IF;
END$$


CREATE TRIGGER trg_job_locked_prevent_update
BEFORE UPDATE ON job_orders
FOR EACH ROW
BEGIN
  IF OLD.is_locked = 1 AND NEW.is_locked = 1 THEN
    IF (
      OLD.job_subject <> NEW.job_subject OR
      OLD.brief <> NEW.brief OR
      OLD.due_date <> NEW.due_date
    ) THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Locked job cannot be modified';
    END IF;
  END IF;
END$$

DELIMITER ;


/* =====================================================
   6. PERFORMANCE INDEXES (NO DUPLICATES)
===================================================== */

CREATE INDEX idx_job_orders_status_created
  ON job_orders(status_id, created_at);

CREATE INDEX idx_assignments_status
  ON job_assignments(assignment_status);

CREATE INDEX idx_activity_created
  ON job_activity_log(created_at);
