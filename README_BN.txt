STUDENT RECORD MANAGEMENT SYSTEM
=================================

প্রয়োজনীয় সফটওয়্যার:
1. XAMPP (Apache ও MySQL)
2. Web browser
3. Code editor (ঐচ্ছিক: VS Code)

চালানোর নিয়ম:
1. ZIP Extract করুন।
2. student_record_management_system ফোল্ডারটি C:\xampp\htdocs\student_record_management_system নামে রাখুন।
3. XAMPP Control Panel থেকে Apache ও MySQL Start করুন।
4. Browser-এ http://localhost/phpmyadmin খুলুন।
5. Import ট্যাব থেকে database.sql ফাইলটি Import করুন।
6. Browser-এ http://localhost/student_record_management_system/ খুলুন।

Database:
- Database name: student_management
- Tables: Departments, Students

ফিচার:
- Student record add, view, search, update, delete
- Department-wise student information
- Total students ও CGPA summary

নোট:
config.php-তে local MySQL username/password দেওয়া আছে (XAMPP default: root এবং blank password)।
নিজের কম্পিউটারের MySQL সেটিংস অনুযায়ী প্রয়োজন হলে পরিবর্তন করুন।
এটি একটি শিক্ষামূলক demo project; sample data ব্যবহার করা হয়েছে।
