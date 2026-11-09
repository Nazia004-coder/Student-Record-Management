CREATE DATABASE IF NOT EXISTS student_management;
USE student_management;

CREATE TABLE IF NOT EXISTS Departments (
    DepartmentID INT AUTO_INCREMENT PRIMARY KEY,
    DepartmentName VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS Students (
    StudentID INT AUTO_INCREMENT PRIMARY KEY,
    StudentName VARCHAR(100) NOT NULL,
    Email VARCHAR(120) NOT NULL UNIQUE,
    Phone VARCHAR(20),
    DepartmentID INT,
    Semester INT,
    CGPA DECIMAL(3,2),
    FOREIGN KEY (DepartmentID) REFERENCES Departments(DepartmentID)
);

INSERT IGNORE INTO Departments (DepartmentName) VALUES
('Computer Science'), ('Business Administration'),
('English'), ('Economics');

INSERT IGNORE INTO Students
(StudentName, Email, Phone, DepartmentID, Semester, CGPA) VALUES
('Ayesha Rahman', 'ayesha@example.com', '01700000001', 1, 3, 3.75),
('Rahim Ahmed', 'rahim@example.com', '01700000002', 2, 2, 3.40),
('Nusrat Jahan', 'nusrat@example.com', '01700000003', 1, 4, 3.90),
('Sakib Hasan', 'sakib@example.com', '01700000004', 3, 1, 3.20),
('Mim Akter', 'mim@example.com', '01700000005', 4, 5, 3.65),
('Tanvir Islam', 'tanvir@example.com', '01700000006', 1, 2, 3.50),
('Sumaiya Khan', 'sumaiya@example.com', '01700000007', 2, 6, 3.80),
('Fahim Chowdhury', 'fahim@example.com', '01700000008', 3, 3, 3.30),
('Jannat Ara', 'jannat@example.com', '01700000009', 4, 4, 3.95),
('Mehedi Hasan', 'mehedi@example.com', '01700000010', 1, 1, 3.10);
