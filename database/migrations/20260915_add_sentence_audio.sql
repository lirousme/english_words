-- Execute once in databases created before sentence audio support.
ALTER TABLE frases
  ADD COLUMN audio_portugues MEDIUMTEXT NULL AFTER frase_ingles,
  ADD COLUMN audio_en_gb MEDIUMTEXT NULL AFTER audio_portugues;
