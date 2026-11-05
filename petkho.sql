DROP DATABASE IF EXISTS petkho;
CREATE DATABASE petkho CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE petkho;

CREATE TABLE users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  photo VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customer (
  customer_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE,
  fname VARCHAR(50), lname VARCHAR(50),
  addressline VARCHAR(100), town VARCHAR(50), zipcode VARCHAR(10), phone VARCHAR(20),
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE category (
  category_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE supplier (
  supplier_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  contact_person VARCHAR(100) NULL,
  phone VARCHAR(20) NULL,
  email VARCHAR(100) NULL,
  address VARCHAR(255) NULL
);

CREATE TABLE item (
  item_id INT PRIMARY KEY AUTO_INCREMENT,
  category_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  brand VARCHAR(50) NULL,
  description TEXT,
  sell_price DECIMAL(8,2) NOT NULL,
  img_path VARCHAR(255) NULL,
  status ENUM('active','retired') NOT NULL DEFAULT 'active',
  supplier_id INT NULL,
  FOREIGN KEY (category_id) REFERENCES category(category_id),
  CONSTRAINT fk_item_supplier FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE SET NULL
);

CREATE TABLE item_photo (
  item_photo_id INT PRIMARY KEY AUTO_INCREMENT,
  item_id INT NOT NULL,
  photo_path VARCHAR(255) NOT NULL,
  FOREIGN KEY (item_id) REFERENCES item(item_id) ON DELETE CASCADE
);

CREATE TABLE stock (
  item_id INT PRIMARY KEY,
  quantity INT NOT NULL DEFAULT 0,
  FOREIGN KEY (item_id) REFERENCES item(item_id)
);

CREATE TABLE orderinfo (
  orderinfo_id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NOT NULL,
  date_placed DATETIME NOT NULL,
  shipping DECIMAL(8,2) NOT NULL DEFAULT 0,
  status ENUM('Processing','Shipped','Delivered','Canceled') NOT NULL DEFAULT 'Processing',
  FOREIGN KEY (customer_id) REFERENCES customer(customer_id)
);

CREATE TABLE orderline (
  orderinfo_id INT NOT NULL,
  item_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  PRIMARY KEY (orderinfo_id, item_id),
  FOREIGN KEY (orderinfo_id) REFERENCES orderinfo(orderinfo_id),
  FOREIGN KEY (item_id) REFERENCES item(item_id)
);

CREATE VIEW ordertotals AS
SELECT o.orderinfo_id, SUM(ol.price * ol.quantity) + o.shipping AS total
FROM orderinfo o JOIN orderline ol USING (orderinfo_id)
GROUP BY o.orderinfo_id, o.shipping;

CREATE VIEW order_details AS
SELECT o.orderinfo_id, o.date_placed, o.status, o.shipping,
       c.customer_id, CONCAT(c.fname,' ',c.lname) AS customer, u.email,
       i.item_id, i.name AS item_name, ol.quantity, ol.price,
       (ol.price * ol.quantity) AS subtotal
FROM orderinfo o
JOIN customer c ON c.customer_id = o.customer_id
JOIN users u ON u.user_id = c.user_id
JOIN orderline ol ON ol.orderinfo_id = o.orderinfo_id
JOIN item i ON i.item_id = ol.item_id;

CREATE TABLE expense (
  expense_id INT PRIMARY KEY AUTO_INCREMENT,
  supplier_id INT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  expense_date DATE NOT NULL,
  FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE SET NULL
);

CREATE TABLE review (
  review_id INT PRIMARY KEY AUTO_INCREMENT,
  item_id INT NOT NULL,
  customer_id INT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES item(item_id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE SET NULL
);

CREATE TABLE review_photo (
  review_photo_id INT PRIMARY KEY AUTO_INCREMENT,
  review_id INT NOT NULL,
  photo_path VARCHAR(255) NOT NULL,
  FOREIGN KEY (review_id) REFERENCES review(review_id) ON DELETE CASCADE
);

CREATE TABLE inbox (
  inbox_id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NOT NULL,
  orderinfo_id INT NULL,
  subject VARCHAR(150) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE
);

-- =====================================================================
-- SAMPLE DATA
-- Import this whole file in phpMyAdmin (Import tab). WARNING: the first line
-- of this file drops the petkho database, so any data you added by hand is lost.
-- Sample customer accounts all use the password:  Customer123
-- The sample staff account is staff@petkho.example, password:  Staff123
-- The admin account (petkhoad@gmail.com) keeps its original password.
-- =====================================================================

-- ---------- categories ----------
INSERT INTO category (category_id, name) VALUES
(1, 'Pet Food'), (2, 'Treats'), (3, 'Toys'), (4, 'Grooming'), (5, 'Cages & Beds'), (6, 'Litter');

-- ---------- suppliers ----------
INSERT INTO supplier (supplier_id, name, contact_person, phone, email, address) VALUES
(1, 'Pet Supplies PH',        'Maria Santos',     '0917 555 0101', 'sales@petsuppliesph.example',   'Quezon City'),
(2, 'Furry Friends Trading',  'Ramon Dela Cruz',  '0918 555 0202', 'orders@furryfriends.example',   'Pasig City'),
(3, 'Happy Tails Wholesale',  'Angela Reyes',     '0919 555 0303', 'hello@happytails.example',      'Taguig City'),
(4, 'TravelPaws Distribution','Jose Mercado',     '0920 555 0404', 'sales@travelpaws.example',      'Makati City');

-- ---------- products (img_path is relative to the images/ folder) ----------
INSERT INTO item (item_id, category_id, name, brand, description, sell_price, img_path, status, supplier_id) VALUES
(1,  1, 'Pedigree Adult Dog Food 1.5kg',      'Pedigree',       'Complete and balanced dry food for adult dogs, with real chicken and vegetables.', 420.00, 'f99d72b50e0a0754.jpg', 'active',  1),
(2,  1, 'Whiskas Chicken Cat Food 1.9kg',     'Whiskas',        'Dry food for adult cats (1+ years) with chicken flavor. Supports healthy skin and coat.', 380.00, '228f75c7f07876ae.jpg', 'active',  1),
(3,  2, 'Milo''s Kitchen Chicken Jerky 80g',  'Milo''s Kitchen','Tender chicken jerky treats for dogs, made with real chicken.',                    195.00, '79de99a9a6964a07.jpg', 'active',  1),
(4,  2, 'Kindfull Freeze-Dried Beef Liver 50g','Kindfull',      'Single-ingredient freeze-dried beef liver treats, great for training.',            260.00, '50da7fc86650bc21.jpg', 'active',  3),
(5,  2, 'Catnip Cat Treats 60g',              'Purrfect',       'Crunchy cat treats with catnip. Cats go crazy for them.',                           85.00, '63246dad5e8570c5.jpg', 'active',  3),
(6,  3, 'Rope Knot Chew Toy Set',             'PawPro',         'Durable cotton rope toys that help clean your dog''s teeth while they play.',      150.00, '658e8300a8aa9b1a.jpg', 'active',  2),
(7,  3, 'Dog Chew Toy Set (10 pcs)',          'PawPro',         'Assorted rope, ball and squeaky toys for small and medium dogs.',                  320.00, 'items/0607b0080d454171.jpg', 'active', 2),
(8,  3, 'Cat Teaser Wand with Fish',          'Whisker Choice', 'Interactive teaser wand with a soft fish toy. Keeps your cat active.',              70.00, 'items/09fcce747b4dda62.jpg', 'active', 2),
(9,  4, 'Perox Dog Shampoo 500ml',            'Perox',          'Gentle, pH-balanced shampoo that leaves your dog''s coat clean and soft.',         240.00, 'ef51ad9c99ffa419.jpg', 'active',  3),
(10, 4, 'Self-Cleaning Slicker Brush',        'CleanPaws',      'Removes loose hair and tangles. Push the button to release the fur.',              290.00, 'items/3eb16f207e665379.jpg', 'active', 3),
(11, 5, 'Cozy Round Pet Bed (Black)',         'CozyPet',        'Soft, washable round bed with raised edges. Medium size.',                         680.00, '109ff73ce523e94e.jpg', 'active',  2),
(12, 5, 'Soft Cushion Pet Bed (Beige)',       'CozyPet',        'Plush cushion bed for cats and small dogs. Machine washable.',                     720.00, 'items/2bc7d84ab719ce16.jpg', 'active', 2),
(13, 5, 'Donut Pet Bed (Pink)',               'CozyPet',        'Calming donut-shaped bed, extra soft and warm.',                                   750.00, 'items/ba7f7473973e55c5.jpg', 'active', 2),
(14, 5, 'Pet Carrier Cage (Medium)',          'TravelPaws',     'Sturdy plastic carrier with metal door, airline-style. For cats and small dogs.', 1250.00, '2c4c47e6e06c3fff.jpg', 'active',  4),
(15, 6, 'Cat Litter 5kg',                     'CleanPaws',      'Clumping, low-dust cat litter with odor control.',                                 210.00, '876be66aa5b4776e.jpg', 'active',  3),
(16, 3, 'Classic Rope Toy (old design)',      'PawPro',         'Old design, replaced by the Rope Knot Chew Toy Set.',                              100.00, NULL, 'retired', 2);

-- extra gallery photos (path is relative to the images/ folder)
INSERT INTO item_photo (item_id, photo_path) VALUES
(1,  'items/30010a39cfd407f5.jpg'),
(3,  'items/c9ee3696c041065a.jpg'),
(6,  'items/0f681570ea49b0ce.jpg'),
(6,  'items/b8da67f7c498521e.jpg'),
(9,  'items/fb7f966b8f52fd28.jpg'),
(15, 'items/550ac3955e8c1aec.jpg');

-- ---------- stock ----------
-- Starting stock first. The UPDATE after the orders removes what was sold, so the
-- numbers always agree with the orders (cancelled orders do not reduce stock).
INSERT INTO stock (item_id, quantity) VALUES
(1, 40), (2, 30), (3, 25), (4, 12), (5, 35), (6, 18), (7, 8), (8, 20),
(9, 22), (10, 15), (11, 10), (12, 6), (13, 1), (14, 9), (15, 30), (16, 5);

-- ---------- users ----------
-- user_id 1 is the admin, 2-8 are customers (password: Customer123, user 8 is inactive), 9 is staff (password: Staff123).
INSERT INTO users (user_id, email, password, role, status, created_at) VALUES
(1, 'petkhoad@gmail.com',          '$2y$10$1LNIB5/726n69/VjIlWFZe3SiKDguzApmPvAZJ3mpJ4WgwigFN93.', 'admin',    'active',   '2026-08-20 09:00:00'),
(2, 'juan.delacruz@example.com',   '$2y$10$YXMg4UjiTl7YJThDLSI7v.bW08b0jA5dls8r9Dib0fIX/rhXIyc26', 'customer', 'active',   '2026-08-25 10:12:00'),
(3, 'maria.santos@example.com',    '$2y$10$YXMg4UjiTl7YJThDLSI7v.bW08b0jA5dls8r9Dib0fIX/rhXIyc26', 'customer', 'active',   '2026-08-28 14:40:00'),
(4, 'pedro.reyes@example.com',     '$2y$10$YXMg4UjiTl7YJThDLSI7v.bW08b0jA5dls8r9Dib0fIX/rhXIyc26', 'customer', 'active',   '2026-09-02 08:30:00'),
(5, 'ana.villanueva@example.com',  '$2y$10$YXMg4UjiTl7YJThDLSI7v.bW08b0jA5dls8r9Dib0fIX/rhXIyc26', 'customer', 'active',   '2026-09-10 16:05:00'),
(6, 'carlo.mendoza@example.com',   '$2y$10$YXMg4UjiTl7YJThDLSI7v.bW08b0jA5dls8r9Dib0fIX/rhXIyc26', 'customer', 'active',   '2026-09-18 11:20:00'),
(7, 'liza.garcia@example.com',     '$2y$10$YXMg4UjiTl7YJThDLSI7v.bW08b0jA5dls8r9Dib0fIX/rhXIyc26', 'customer', 'active',   '2026-10-05 13:45:00'),
(8, 'mark.bautista@example.com',   '$2y$10$YXMg4UjiTl7YJThDLSI7v.bW08b0jA5dls8r9Dib0fIX/rhXIyc26', 'customer', 'inactive', '2026-10-12 09:15:00'),
(9, 'staff@petkho.example',        '$2y$10$zld3k88CoBBNb2Olvpth1OJKmBkyx4E9w1fsKIg.pMeg6xuR6HDNe', 'staff',    'active',   '2026-08-22 09:30:00');

INSERT INTO customer (customer_id, user_id, fname, lname, addressline, town, zipcode, phone) VALUES
(1, 2, 'Juan',  'Dela Cruz',  '12 Mabini Street',        'Taguig City',   '1630', '0917 111 2233'),
(2, 3, 'Maria', 'Santos',     '45 Katipunan Avenue',     'Quezon City',   '1108', '0918 222 3344'),
(3, 4, 'Pedro', 'Reyes',      '8 Rizal Drive',           'Makati City',   '1200', '0919 333 4455'),
(4, 5, 'Ana',   'Villanueva', '27 Ortigas Extension',    'Pasig City',    '1600', '0920 444 5566'),
(5, 6, 'Carlo', 'Mendoza',    '99 Aguinaldo Highway',    'Cavite City',   '4100', '0921 555 6677'),
(6, 7, 'Liza',  'Garcia',     '3 Bonifacio Street',      'Mandaluyong',   '1550', '0922 666 7788'),
(7, 8, 'Mark',  'Bautista',   '71 Taft Avenue',          'Manila',        '1000', '0923 777 8899');

-- ---------- orders (shipping is a flat 50.00; totals are NOT stored, the ordertotals view adds them up) ----------
INSERT INTO orderinfo (orderinfo_id, customer_id, date_placed, shipping, status) VALUES
(1,  1, '2026-09-05 10:15:00', 50.00, 'Delivered'),
(2,  2, '2026-09-12 14:30:00', 50.00, 'Delivered'),
(3,  3, '2026-09-20 09:05:00', 50.00, 'Delivered'),
(4,  4, '2026-09-28 16:45:00', 50.00, 'Canceled'),
(5,  1, '2026-10-03 11:20:00', 50.00, 'Delivered'),
(6,  5, '2026-10-08 13:00:00', 50.00, 'Delivered'),
(7,  6, '2026-10-15 15:10:00', 50.00, 'Delivered'),
(8,  2, '2026-10-22 10:40:00', 50.00, 'Delivered'),
(9,  7, '2026-10-27 17:25:00', 50.00, 'Shipped'),
(10, 3, '2026-10-30 12:00:00', 50.00, 'Shipped'),
(11, 4, '2026-11-01 09:30:00', 50.00, 'Processing'),
(12, 5, '2026-11-02 14:15:00', 50.00, 'Processing'),
(13, 1, '2026-11-03 08:50:00', 50.00, 'Processing'),
(14, 6, '2026-11-02 19:05:00', 50.00, 'Canceled');

-- price = the product's price at the time of the order
INSERT INTO orderline (orderinfo_id, item_id, quantity, price) VALUES
(1, 1, 2, 420.00), (1, 3, 1, 195.00),
(2, 2, 1, 380.00), (2, 5, 2,  85.00), (2, 8, 1, 70.00),
(3, 11, 1, 680.00), (3, 16, 1, 100.00),
(4, 14, 1, 1250.00),
(5, 9, 1, 240.00), (5, 10, 1, 290.00),
(6, 4, 2, 260.00), (6, 3, 1, 195.00),
(7, 6, 1, 150.00), (7, 7, 1, 320.00),
(8, 15, 2, 210.00), (8, 2, 2, 380.00),
(9, 12, 1, 720.00),
(10, 1, 1, 420.00), (10, 4, 1, 260.00),
(11, 13, 1, 750.00), (11, 8, 2, 70.00),
(12, 2, 1, 380.00), (12, 15, 1, 210.00), (12, 5, 1, 85.00),
(13, 9, 1, 240.00),
(14, 7, 1, 320.00);

-- take the sold items out of stock (orders that were cancelled never took stock)
UPDATE stock s
SET s.quantity = s.quantity - COALESCE((
    SELECT SUM(ol.quantity)
    FROM orderline ol JOIN orderinfo o ON o.orderinfo_id = ol.orderinfo_id
    WHERE ol.item_id = s.item_id AND o.status <> 'Canceled'), 0);

-- ---------- reviews (only for items in Delivered orders) ----------
INSERT INTO review (review_id, item_id, customer_id, rating, comment, review_date) VALUES
(1, 1,  1, 5, 'My dog finishes his bowl every time. Good value for the price!',            '2026-09-10 18:20:00'),
(2, 3,  1, 4, 'Smells great and my dog loves it. A bit pricey but worth it.',              '2026-09-10 18:25:00'),
(3, 2,  2, 5, 'My two cats love this. Fast delivery too.',                                 '2026-09-18 20:10:00'),
(4, 5,  2, 4, 'The cats go crazy for these treats.',                                       '2026-09-18 20:12:00'),
(5, 11, 3, 5, 'Very soft and easy to wash. My dog sleeps in it all day.',                  '2026-09-26 09:30:00'),
(6, 10, 1, 4, 'Works well on long fur. The self-cleaning button is handy.',                '2026-10-09 15:00:00'),
(7, 4,  5, 5, 'Perfect for training. My puppy will do anything for these!',                '2026-10-14 19:45:00'),
(8, 7,  6, 3, 'Nice variety, but the squeaker on one toy stopped working after a week.',   '2026-10-21 12:05:00'),
(9, 15, 2, 4, 'Clumps well and has little dust.',                                          '2026-10-28 08:40:00');

-- review photos (file names only, saved in images/reviews/)
INSERT INTO review_photo (review_id, photo_path) VALUES
(7, '5cc97dde69fbcb35.jpg'),
(7, '82ad3895fa6bbe17.jpg');

-- ---------- expenses ----------
INSERT INTO expense (supplier_id, description, amount, expense_date) VALUES
(1,    'Restock - dog and cat food',      12500.00, '2026-09-01'),
(2,    'Restock - toys and pet beds',      8200.00, '2026-09-15'),
(NULL, 'Store rent - September',          15000.00, '2026-09-30'),
(1,    'Restock - treats',                 5400.00, '2026-10-02'),
(3,    'Restock - grooming and litter',    3600.00, '2026-10-10'),
(NULL, 'Store rent - October',            15000.00, '2026-10-31'),
(4,    'Restock - pet carriers',           1200.00, '2026-11-01'),
(NULL, 'Packaging materials',               450.00, '2026-11-02');

-- ---------- inbox (what the customer sees after a status email) ----------
INSERT INTO inbox (customer_id, orderinfo_id, subject, body, is_read, created_at) VALUES
(1, 1,  'Petkho order #000001 - Delivered',  '<p>Hi Juan,</p><p>The status of your order <strong>#000001</strong> is now <strong>Delivered</strong>. Thank you for shopping at Petkho!</p>', 1, '2026-09-08 15:00:00'),
(4, 4,  'Petkho order #000004 - Cancelled',  '<p>Hi Ana,</p><p>The status of your order <strong>#000004</strong> is now <strong>Cancelled</strong>. The items were returned to stock.</p>', 1, '2026-09-29 10:00:00'),
(7, 9,  'Petkho order #000009 - To Receive', '<p>Hi Mark,</p><p>The status of your order <strong>#000009</strong> is now <strong>To Receive</strong>. Your package is on its way!</p>', 0, '2026-10-28 09:10:00'),
(3, 10, 'Petkho order #000010 - To Receive', '<p>Hi Pedro,</p><p>The status of your order <strong>#000010</strong> is now <strong>To Receive</strong>. Your package is on its way!</p>', 0, '2026-10-31 10:30:00'),
(6, 14, 'Petkho order #000014 - Cancelled',  '<p>Hi Liza,</p><p>The status of your order <strong>#000014</strong> is now <strong>Cancelled</strong>. The items were returned to stock.</p>', 0, '2026-11-02 20:00:00');
