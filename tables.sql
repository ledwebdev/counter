CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE departures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    depart TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE opening_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dow tinyint(1) unsigned,
    cap INT,
    late BOOLEAN,
    open DATETIME,
    closed DATETIME
);

INSERT INTO opening_hours
    (
        dow, cap, late, open, closed
    )
VALUES
    (0, 350, false, '2021-01-01 07:00:00', '2021-01-01 23:59:00'),
    (1, 350, false, '2021-01-01 07:00:00', '2021-01-01 23:59:00'),
    (2, 350, false, '2021-01-01 07:00:00', '2021-01-01 23:59:00'),
    (3, 350, false, '2021-01-01 07:00:00', '2021-01-01 23:59:00'),
    (4, 350, false, '2021-01-01 07:00:00', '2021-01-01 23:59:00'),
    (5, 350, false, '2021-01-01 07:00:00', '2021-01-01 23:59:00'),
    (6, 350, false, '2021-01-01 07:00:00', '2021-01-01 23:59:00');

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    space_id INT,
    booking_type char(25),
    capacity INT
);

INSERT INTO bookings
    (
       space_id, booking_type, capacity
    )
VALUES
    (MODIIFY, 'PCs', 10),
    (MODIFY, 'Rooms', 5),
    (MODIFY, 'Desks', 20);

