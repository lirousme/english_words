CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE words (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  word VARCHAR(150) NOT NULL,
  UNIQUE KEY words_word_unique (word)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_word BIGINT UNSIGNED NOT NULL,
  portugues VARCHAR(255) NOT NULL,
  `type` INT NOT NULL,
  CONSTRAINT translations_word_fk FOREIGN KEY (id_word) REFERENCES words (id) ON DELETE CASCADE,
  CONSTRAINT translations_type_check CHECK (`type` BETWEEN 1 AND 6),
  UNIQUE KEY translations_word_portugues_type_unique (id_word, portugues, `type`),
  KEY translations_word_type_index (id_word, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE frases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_translation BIGINT UNSIGNED NOT NULL,
  frase_portugues TEXT NOT NULL,
  frase_ingles TEXT NOT NULL,
  audio_portugues MEDIUMTEXT NULL,
  audio_en_gb MEDIUMTEXT NULL,
  CONSTRAINT frases_translation_fk FOREIGN KEY (id_translation) REFERENCES translations (id) ON DELETE CASCADE,
  KEY frases_translation_index (id_translation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
