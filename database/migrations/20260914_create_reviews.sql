-- Execute once in databases created before the spaced-repetition feature.
CREATE TABLE reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_user BIGINT UNSIGNED NOT NULL,
  id_translation BIGINT UNSIGNED NOT NULL,
  amount INT UNSIGNED NOT NULL DEFAULT 1,
  next_review DATETIME NOT NULL,
  CONSTRAINT reviews_user_fk FOREIGN KEY (id_user) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT reviews_translation_fk FOREIGN KEY (id_translation) REFERENCES translations (id) ON DELETE CASCADE,
  CONSTRAINT reviews_amount_check CHECK (amount >= 1),
  UNIQUE KEY reviews_user_translation_unique (id_user, id_translation),
  KEY reviews_due_index (id_user, next_review)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
