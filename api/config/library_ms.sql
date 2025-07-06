-- Create database
CREATE DATABASE IF NOT EXISTS library_ms;
USE library_ms;

-- Users table for both Admin and Patron roles
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'patron')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table for catalog
CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(100) NOT NULL,
  isbn VARCHAR(13) UNIQUE NOT NULL,
  total_copies INT NOT NULL DEFAULT 1,
  available_copies INT NOT NULL DEFAULT 1,
  quantity INT NOT NULL DEFAULT 1,
  shelf VARCHAR(50),
  category VARCHAR(100),
  description TEXT,
  published_year INT,
  publisher VARCHAR(100),
  cover_image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Circulation table for tracking borrows, returns, reservations
CREATE TABLE IF NOT EXISTS circulation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  book_id INT,
  action VARCHAR(20) NOT NULL CHECK (action IN ('borrow', 'return', 'reserve')),
  action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  due_date TIMESTAMP NULL,
  fine_amount DECIMAL(10,2) DEFAULT 0.00,
  returned BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index for faster searches
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_books_author ON books(author);
CREATE INDEX idx_books_isbn ON books(isbn);
CREATE INDEX idx_circulation_user_id ON circulation(user_id);
CREATE INDEX idx_circulation_book_id ON circulation(book_id);

-- Insert default admin user (password: password)
INSERT INTO users (username, password_hash, email, role) VALUES 
('admin', '$2y$10$rrm7gFHPxZWi59B1RaEcR.fnNtlvmj6Oq0AkuYpFwOJvZVByJbiWi', 'admin@example.com', 'admin');

-- Insert sample books
INSERT INTO books (title, author, isbn, total_copies, available_copies, quantity, shelf, category, description, published_year, publisher, cover_image) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 5, 4, 5, 'A1', 'Fiction', 'A novel by F. Scott Fitzgerald that follows a cast of characters living in the fictional town of West Egg.', 1925, 'Charles Scribner\'s Sons', 'https://example.com/great-gatsby.jpg'),
('To Kill a Mockingbird', 'Harper Lee', '9780061120084', 3, 3, 3, 'B2', 'Classic', 'The story of racial injustice and the destruction of innocence in a small Southern town.', 1960, 'J. B. Lippincott & Co.', 'https://example.com/mockingbird.jpg');
