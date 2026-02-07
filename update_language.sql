-- 1. Add language column to questions (default 'en')
ALTER TABLE quiz_questions ADD COLUMN language VARCHAR(10) DEFAULT 'en';

-- 2. Create table for result texts (so we can translate them too)
CREATE TABLE IF NOT EXISTS quiz_results_content (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT,
  result_key VARCHAR(50),
  language VARCHAR(10),
  title VARCHAR(255),
  description TEXT,
  cta VARCHAR(255),
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

-- 3. Seed Result Content (English)
INSERT INTO quiz_results_content (quiz_id, result_key, language, title, description, cta) VALUES
(1, 'original', 'en', '🧊 You’re Smirnoff Ice Original', 'You’re the vibe everyone feels comfortable around. Sip smooth, stay cool.', 'Sip smooth. Stay cool.'),
(1, 'pineapple', 'en', '🍍 Smirnoff Ice Pineapple', 'Bring the flavor. Be the moment. You are the life of the party.', 'Bring the flavor. Be the moment.'),
(1, 'guarana', 'en', '⚡ Smirnoff Ice Guarana', 'Turn it up. Own the vibe. You bring the energy that keeps the night going.', 'Turn it up. Own the vibe.');

-- 4. Seed Result Content (Swahili)
INSERT INTO quiz_results_content (quiz_id, result_key, language, title, description, cta) VALUES
(1, 'original', 'sw', '🧊 Wewe ni Smirnoff Ice Original', 'Wewe ndiye mshikaji ambaye kila mtu anajiskia huru naye. Tulia na unywe taratibu.', 'Poa Kabisa.'),
(1, 'pineapple', 'sw', '🍍 Smirnoff Ice Pineapple', 'Leta ladha. Kamata moment. Wewe ndiye uhai wa sherehe.', 'Leta Ladha.'),
(1, 'guarana', 'sw', '⚡ Smirnoff Ice Guarana', 'Amsha vibe. Miliki usiku. Wewe ndiye unayeleta mzuka unaoendeleza sherehe.', 'Amsha Vibe.');


-- 5. Seed Swahili Questions
-- We insert new questions linked to the same quiz, but with language='sw'.
-- We need to capture the IDs to insert options.

-- Q1 Swahili
INSERT INTO quiz_questions (quiz_id, question, language) VALUES (1, 'Umefika kiwanja. Vibe yako ikoje?', 'sw');
SET @q1_id = LAST_INSERT_ID();

INSERT INTO quiz_options (question_id, option_text, result_key) VALUES
(@q1_id, 'Naingia taratibu, nasoma mazingira kwanza', 'original'),
(@q1_id, 'Niko tayari kucheka — leo ni furaha tu', 'pineapple'),
(@q1_id, 'Nimejipanga. Tuamshe hapa sasa hivi', 'guarana');

-- Q2 Swahili
INSERT INTO quiz_questions (quiz_id, question, language) VALUES (1, 'Marafiki zako wanakuelezeaje?', 'sw');
SET @q2_id = LAST_INSERT_ID();

INSERT INTO quiz_options (question_id, option_text, result_key) VALUES
(@q2_id, 'Mtu mtulivu anayeunganisha wote', 'original'),
(@q2_id, 'Mcheshi — vibes, utani, na nishati nzuri', 'pineapple'),
(@q2_id, 'Jasiri — nikifika, mambo hubadilika', 'guarana');

-- Q3 Swahili
INSERT INTO quiz_questions (quiz_id, question, language) VALUES (1, 'Mpango wako bora wa usiku ukoje?', 'sw');
SET @q3_id = LAST_INSERT_ID();

INSERT INTO quiz_options (question_id, option_text, result_key) VALUES
(@q3_id, 'Muziki mzuri, watu wazuri, bila presha', 'original'),
(@q3_id, 'Kucheza, picha, kumbukumbu sitasahau', 'pineapple'),
(@q3_id, 'Muziki wa sauti ya juu, hekaheka, nishati usiku kucha', 'guarana');

-- Q4 Swahili
INSERT INTO quiz_questions (quiz_id, question, language) VALUES (1, 'Kitu gani hufanya uchague kinywaji?', 'sw');
SET @q4_id = LAST_INSERT_ID();

INSERT INTO quiz_options (question_id, option_text, result_key) VALUES
(@q4_id, 'Rahisi kufurahia, laini, bila kufikiria sana', 'original'),
(@q4_id, 'Ladha inayoleta furaha na kusisimua', 'pineapple'),
(@q4_id, 'Kitu chenye kiki — nataka nikisikie', 'guarana');

-- Q5 Swahili
INSERT INTO quiz_questions (quiz_id, question, language) VALUES (1, 'Vibe yako kwenye kundi ikoje?', 'sw');
SET @q5_id = LAST_INSERT_ID();

INSERT INTO quiz_options (question_id, option_text, result_key) VALUES
(@q5_id, 'Nahakikisha mambo yanaenda sawa, kila mtu yuko poa', 'original'),
(@q5_id, 'Naleta furaha — mood inategemea mimi', 'pineapple'),
(@q5_id, 'Napandisha nishati — naanzisha momentum', 'guarana');

-- Q6 Swahili
INSERT INTO quiz_questions (quiz_id, question, language) VALUES (1, 'Neno moja linaloelezea usiku wa leo', 'sw');
SET @q6_id = LAST_INSERT_ID();

INSERT INTO quiz_options (question_id, option_text, result_key) VALUES
(@q6_id, 'Chill', 'original'),
(@q6_id, 'Moto', 'pineapple'),
(@q6_id, 'Umeme', 'guarana');
