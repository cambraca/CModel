CREATE TABLE `author` (
	`id` CHAR(6),
	`name` VARCHAR(255) NOT NULL,
	`email` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id`)
);

CREATE TABLE `post` (
	`id` CHAR(6),
	`author_id` CHAR(6),
	`title` VARCHAR(255) NOT NULL,
	`content` TEXT NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_post_author` FOREIGN KEY (`author_id`) REFERENCES `author` (`id`) ON DELETE CASCADE
);

INSERT INTO `author` (`id`, `name`, `email`)
VALUES
('abc123', 'John Doe', 'john@email.com'),
('qwe456', 'Caroline Doe', 'caroline@email.com');

INSERT INTO `post` (`id`, `author_id`, `title`, `content`)
VALUES
('post01', 'abc123', 'My First Post', 'Hi there!'),
('post02', 'abc123', 'My Second Post', 'This is John\'s second post.'),
('post03', 'qwe456', 'Hello world', 'Hi, my name is Caroline and this is my only post.');