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
