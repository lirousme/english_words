-- Execute once in databases created with the previous schema.
ALTER TABLE frases DROP INDEX frases_translation_unique;
ALTER TABLE frases ADD KEY frases_translation_index (id_translation);
