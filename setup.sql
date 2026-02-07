CREATE DATABASE IF NOT EXISTS bramex_Quizzify;
USE bramex_Quizzify;

-- Create User (Run this as root if you haven't already)
CREATE USER IF NOT EXISTS 'bramex_Quizzify'@'localhost';
GRANT ALL PRIVILEGES ON bramex_Quizzify.* TO 'bramex_Quizzify'@'localhost';
FLUSH PRIVILEGES;

-- 1. Brands
DROP TABLE IF EXISTS brands;
CREATE TABLE brands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100)
);

-- 2. Quizzes
DROP TABLE IF EXISTS quizzes;
CREATE TABLE quizzes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  brand_id INT,
  title VARCHAR(100),
  FOREIGN KEY (brand_id) REFERENCES brands(id)
);

-- 3. Questions
DROP TABLE IF EXISTS quiz_questions;
CREATE TABLE quiz_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT,
  question TEXT,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

-- 4. Options
DROP TABLE IF EXISTS quiz_options;
CREATE TABLE quiz_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT,
  option_text VARCHAR(255),
  result_key VARCHAR(50),
  FOREIGN KEY (question_id) REFERENCES quiz_questions(id)
);

-- 5. Sessions
DROP TABLE IF EXISTS quiz_sessions;
CREATE TABLE quiz_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT,
  token VARCHAR(100),
  result_key VARCHAR(50),
  influencer VARCHAR(50),
  device VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Answers
DROP TABLE IF EXISTS quiz_answers;
CREATE TABLE quiz_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_token VARCHAR(100),
  question_id INT,
  option_id INT,
  result_key VARCHAR(50)
);

-- SEED DATA
INSERT INTO brands (name) VALUES ('Smirnoff');
INSERT INTO quizzes (brand_id,title) VALUES (1,'Vibe Yako, Flavor Yako');

-- Questions for Quiz 1
INSERT INTO quiz_questions (quiz_id,question) VALUES
(1,'You just arrived at the spot. What’s your energy?'),
(1,'Your friends would describe you as…'),
(1,'Pick your ideal night plan'),
(1,'What usually makes you choose a drink?'),
(1,'Your vibe in a group setting is…'),
(1,'One word that best describes tonight');

-- Options
-- Q1
INSERT INTO quiz_options (question_id,option_text,result_key) VALUES
(1,'I’m easing in, feeling the vibe first','original'),
(1,'I’m already laughing — tonight is for fun','pineapple'),
(1,'I’m ready. Let’s turn this place up','guarana');

-- Q2
INSERT INTO quiz_options (question_id,option_text,result_key) VALUES
(2,'The calm one who connects everyone','original'),
(2,'The fun one — vibes, jokes, and good energy','pineapple'),
(2,'The bold one — when I arrive, things change','guarana');

-- Q3
INSERT INTO quiz_options (question_id,option_text,result_key) VALUES
(3,'Good music, good people, no pressure','original'),
(3,'Dancing, pictures, memories I won’t forget','pineapple'),
(3,'Loud music, movement, energy all night','guarana');

-- Q4
INSERT INTO quiz_options (question_id,option_text,result_key) VALUES
(4,'Easy to enjoy, smooth, no thinking','original'),
(4,'Flavor that feels fun and exciting','pineapple'),
(4,'Something with a kick — I want to feel it','guarana');

-- Q5
INSERT INTO quiz_options (question_id,option_text,result_key) VALUES
(5,'I keep things flowing, everyone’s comfortable','original'),
(5,'I bring the fun — the mood depends on me','pineapple'),
(5,'I raise the energy — I start the momentum','guarana');

-- Q6
INSERT INTO quiz_options (question_id,option_text,result_key) VALUES
(6,'Chill','original'),
(6,'Lit','pineapple'),
(6,'Electric','guarana');
