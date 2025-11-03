-- ===================================================



-- FIXED seed_full.sql (customers, drivers, waybills, items)



-- Adds required shipping_directions and deliveries (id=1) to satisfy FKs



-- ===================================================







-- Create shipping direction ZA->TZ if missing (only if both countries exist)



INSERT IGNORE INTO `{PREFIX}kit_shipping_directions` (origin_country_id, destination_country_id, description, is_active)



SELECT oc1.id, oc2.id, 'ZA->TZ', 1 FROM `{PREFIX}kit_operating_countries` oc1, `{PREFIX}kit_operating_countries` oc2



WHERE oc1.country_code='ZA' AND oc2.country_code='TZ'



AND NOT EXISTS (SELECT 1 FROM `{PREFIX}kit_shipping_directions` sd WHERE sd.origin_country_id=oc1.id AND sd.destination_country_id=oc2.id)



AND oc1.id IS NOT NULL AND oc2.id IS NOT NULL;







-- Ensure delivery_id=1 exists (only if shipping direction exists)



INSERT INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, status, created_by)



SELECT 1, 'warehouse',



  COALESCE(COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)),



  COALESCE((SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1),



           (SELECT id FROM `{PREFIX}kit_operating_cities` ORDER BY id ASC LIMIT 1)) ,



  'scheduled', {CREATED_BY}



WHERE NOT EXISTS (SELECT 1 FROM `{PREFIX}kit_deliveries` WHERE id=1)



AND EXISTS (SELECT 1 FROM `{PREFIX}kit_shipping_directions` LIMIT 1);







-- Insert Customers



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1001, 'ALIASGHER', NULL, '+27 63 673  6258', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1002, 'Alex', 'Olifiser', '+255 758 818 058', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1003, 'Alexandra', 'Soine', '+255 682 082 976', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1004, 'Aliasgher', NULL, '+27 63 673  6258', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1005, 'Altitude', 'Hotel', '+255 221 130 32', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1006, 'Andre', 'Bonjour', '+255 762 537 321', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1007, 'Andrew', 'Kulola', '+255 713 755 051', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1008, 'Anne North ( Yatch Club', ')', '+255 785 724 872', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1009, 'Annette', 'Simonson', '+255 784 305 797', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1010, 'Ashley', 'Calavarius', '+255 746 993 333', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1011, 'Ashley Terra', 'Tools', '+255 746 993 333', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1012, 'Asilia Lodges And', 'Camps', NULL, NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1013, 'Ben', 'Pelser', '+255 767 780 319', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1014, 'Bianca', 'Thielke', '+27 76 832 6376', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1015, 'Branden', 'Simonson', '+255 784 734 449', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1016, 'Bubbles', 'Laundry', '+255 776 516 411', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1017, 'Burhanuddin', 'Morbiwalla', '+255 620 285 462', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1018, 'Caleb', 'Simonson', '+255 685 215 915', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1019, 'Cedrick Motte Quin', 'Zanziber', '+255 784 311 104', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1020, 'Central Aviation', 'Services', '+255 686 020 582', NULL, 7);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1021, 'Charles', 'Green', '+255 767 699 764', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1022, 'Chintu', 'Patel', '+255 744 144 144', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1023, 'Chloe Sheidan', 'Johnson', '+255 763 598 027', NULL, 11);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1024, 'Chris', 'Green', '+27 82 389 5566', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1025, 'Chris', 'Joubert', '+255 699 796 869', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1026, 'Chris', 'Rodgers', '+255 757 830 825', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1027, 'Conley', 'Cooke', '+27 82 652 5789', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1028, 'Danieal', 'Longanetti', '+255 682 359 713', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1029, 'David', 'Bega', '+255 777 960 747', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1030, 'David', 'Legeant', '+255 745 999 437', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1031, 'David', 'Scott', '+255 767 366 146', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1032, 'Dean', 'Peterson', '+255 784 824 639', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1033, 'Debra', 'Woolley', '+44 7969 365 223', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1034, 'Dekker', 'Chrysanten', NULL, NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1035, 'Derek', 'Cooper', '+27 715 780 015', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1036, 'Dipen', 'Shah', '+255 784 800 999', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1037, 'Dominique And', 'Clint', '+255 777 893 220', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1038, 'Dr Sabine', 'Marten', '+49 1511 423 7536', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1039, 'Dunstan Kipande', 'Mua', '+255 623 819 428', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1040, 'Elena de', 'Villiers', '+27 66 221 9015', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1041, 'Elliot', 'Kinsey', '+255 767 404 200', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1042, 'Emma', 'Wilson', '+255 699 084 010', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1043, 'Ethan', 'Awawa', '+255 787 146 058', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1044, 'Fes Agric', 'Services', '+263 77 285 0579', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1045, 'Fransie', 'Calitz', '+255 765 850 001', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1046, 'Fredrick', 'Lymo', '+255 754 446 677', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1047, 'Gabriella', 'Kortland', '+255 767 699 764', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1048, 'Goretty Dos', 'Ramos', '+27 78 266 4563', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1049, 'Heikki', 'Niskala', '+255 754 483 430', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1050, 'Hendrick', 'Lombard', '+255 767 114 451', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1051, 'Ian', 'Lombard', '+277 321 044 99', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1052, 'Imran', 'Dedhar', '+255 762 550 141', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1053, 'Ina', 'Walter', '+255 744 385 824', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1054, 'Jack', 'Dower', '+27 72 274 9807', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1055, 'Jackie', 'Bosselaar', '+27 79 048 4046', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1056, 'James', 'Redfern', '+255 658 344 044', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1057, 'Jan', 'Griesl', '+255 756 310 667', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1058, 'Jan van', 'Blommestein', '+255 681 253 041', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1059, 'Jesse', NULL, NULL, NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1060, 'Joe', 'Vipond', '+27 82 570 4556', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1061, 'John', 'Power', '+255 756 937 836', NULL, 13);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1062, 'Johnathan', NULL, '+255 716 382 112', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1063, 'Jonz', 'Express', '+255 683 041 777', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1064, 'Jp De', 'Villiers', '+27 6623 95661', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1065, 'Julia', 'Altezza', '+255 699 373 358', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1066, 'Julia', 'Redfern', '+255 658 344 044', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1067, 'Justin', 'Trappe', '+255 765 007 007', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1068, 'Kat', 'West', '+255 686 573 423', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1069, 'Kayen', 'Investments', '+255 693 301 686', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1070, 'Kiwango', '(Johnathan)', '+255 713 213 060', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1071, 'Kuki', 'Nijiro', '+255 784 540 055', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1072, 'Laba', 'Laba', '+255 683 382 038', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1073, 'Lali', 'Heath', '+255 789 114 400', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1074, 'Lauren', 'Mcfarlane', '084 693 1423', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1075, 'Leanne', 'Haigh', '+255 683 937 462', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1076, 'Lesley De', 'Kock', '+255 754 262 969', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1077, 'Linet', 'Ongoro', '+254 711 273 781', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1078, 'Lodge', 'Creations', '+255 683 38 038', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1079, 'Mandy', 'Stein', '+255 712 015 821', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1080, 'Manmeet', 'Singh', '+255 762 900 800', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1081, 'Marlies', 'Gabriel', '+255 754 510 195', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1082, 'Martie', 'Botha', '+255 767 704 909', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1083, 'Micheal', 'Coudrey', '+1(424) 330 4090', NULL, 14);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1084, 'Micheal', 'Dewart', '+255 767 106 605', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1085, 'Micheal', 'Munro', '+255 743 273 267', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1086, 'Mirriam', NULL, '+255 757 165 514', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1087, 'Nabaki', NULL, '+255 766 591 170', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1088, 'Nabil', 'Haroon', '+255 745 101 90', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1089, 'Nangini', 'Lukumay', '+255 684 500 007', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1090, 'Nassir', 'Mawji', '+255 754 266 077', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1091, 'Ngoro Ngoro Safari', 'Lodge', '+255 757 160 610', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1092, 'Nicolas', 'Gant', '+255 677 094 681', NULL, 13);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1093, 'Nina', 'Gray', '+27 71 538 9819', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1094, 'Oliver', 'Fox', '+44 7491 111 445', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1095, 'Paresh', 'Patel', '+255 754 206 294', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1096, 'Patrick', 'Roberts', '+255 22 260 0137', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1097, 'Praveena', 'Selvarajah', '+41 78 670 77 08', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1098, 'Quinton', NULL, '+255 783 207 334', NULL, 13);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1099, 'Richard', 'Lauenstein', '+255 762 666 363', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1100, 'Rita', 'Mcluckie', '+27 64 655 9458', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1101, 'Rob', 'Chekwaze', '+255 743 911 186', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1102, 'Robanda', 'Serengeti', '+49 157 339 00 122', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1103, 'Rodger', 'Farren', '+255 784 235 987', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1104, 'Ron', 'Barnes-Webb', '+255 683 382 038', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1105, 'Rudolf', 'Greiling', '+27 82 371 2916', NULL, 13);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1106, 'Samika Salim', 'Mauly', '+255 767 251 177', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1107, 'Sarah', 'Khanbhai', '+255 764 871 714', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1108, 'Sebastian', 'Marquet', '+255 784 589 075', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1109, 'Sharmaine', 'Broodryk', '+255 783 960 177', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1110, 'Shelomith', 'Technologies', '+255 788 997 742', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1111, 'Six Rivers', 'Africa', '+255 756 241 89', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1112, 'Skyler', 'Russell', '+1 ( 480 ) 620 0916', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1113, 'Solomon Jeremiah', 'Sembosi', '+255 764 280 770', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1114, 'Stephen', 'Berson', '+255 695 528 782', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1115, 'Steve', 'Luka', '+255 767 081 885', NULL, 13);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1116, 'Ted', 'Rabenhold', '+255 768 206 942', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1117, 'Tim', 'Leach', '+255 766 330 040', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1118, 'Tom ( Sable Hills', 'Farm)', '+358 503 690 904', NULL, 11);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1119, 'Warren', NULL, '+255 765 155 388', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1120, 'Wendy', 'Erasmus', '+255 787 600 076', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1121, 'Wesley', 'Gold', '+44 787 668 1480', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1122, 'Wiandra', 'Wolmarans', '+27 67 256 6102', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1123, 'Wildernes', 'Destinations', '+272 170 7500', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1124, 'Wolfram', 'Reiners', '+49 173 3673345', NULL, NULL);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1125, 'Yusuf', NULL, '+255 784 355 606', NULL, 6);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1126, 'Yusuff', NULL, '+255 784 355 606', NULL, 8);



INSERT IGNORE INTO `{PREFIX}kit_customers` (cust_id, name, surname, cell, country_id, city_id) VALUES (1127, 'Zander', 'Englebrecht', '+255 788 131 450', NULL, 8);







-- Insert Drivers



INSERT IGNORE INTO `{PREFIX}kit_drivers` (name, phone, email, license_number, is_active) VALUES ('James', NULL, NULL, NULL, 1);



INSERT IGNORE INTO `{PREFIX}kit_drivers` (name, phone, email, license_number, is_active) VALUES ('LUCKSON', NULL, NULL, NULL, 1);



INSERT IGNORE INTO `{PREFIX}kit_drivers` (name, phone, email, license_number, is_active) VALUES ('Oscar', NULL, NULL, NULL, 1);







-- Insert Waybills and Items (1 row => 1 main + 1 item)





-- Deliveries (grouped by Driver + Truck Dispatch Date)

INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (1, 'DEL-20250905-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-05', '9979', 'completed', {CREATED_BY}, '2025-09-05');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (2, 'DEL-20250909-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-09', '9859', 'completed', {CREATED_BY}, '2025-09-09');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (3, 'DEL-20250923-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Moshi' ORDER BY id ASC LIMIT 1), '2025-09-23', '2252', 'completed', {CREATED_BY}, '2025-09-23');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (4, 'DEL-20251001-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-01', '1900', 'completed', {CREATED_BY}, '2025-10-01');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (5, 'DEL-20251006-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-06', '1798', 'completed', {CREATED_BY}, '2025-10-06');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (6, 'DEL-20251007-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-07', '4571', 'completed', {CREATED_BY}, '2025-10-07');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (7, 'DEL-20251008-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Iringa' ORDER BY id ASC LIMIT 1), '2025-10-08', '1540', 'completed', {CREATED_BY}, '2025-10-08');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (8, 'DEL-20251009-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-09', '5753', 'completed', {CREATED_BY}, '2025-10-09');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (9, 'DEL-20251010-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-10', '5012', 'completed', {CREATED_BY}, '2025-10-10');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (10, 'DEL-20251013-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-13', '2568', 'completed', {CREATED_BY}, '2025-10-13');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (11, 'DEL-20251014-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Iringa' ORDER BY id ASC LIMIT 1), '2025-10-14', '5240', 'completed', {CREATED_BY}, '2025-10-14');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (12, 'DEL-20251015-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Mafinga' ORDER BY id ASC LIMIT 1), '2025-10-15', '3988', 'completed', {CREATED_BY}, '2025-10-15');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (13, 'DEL-20251016-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-16', '9871', 'completed', {CREATED_BY}, '2025-10-16');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (14, 'DEL-20251017-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-17', '3094', 'completed', {CREATED_BY}, '2025-10-17');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (15, 'DEL-20251020-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-20', '8381', 'completed', {CREATED_BY}, '2025-10-20');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (16, 'DEL-20251021-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-21', '2687', 'completed', {CREATED_BY}, '2025-10-21');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (17, 'DEL-20251022-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-22', '3381', 'completed', {CREATED_BY}, '2025-10-22');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (18, 'DEL-20251023-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-23', '1458', 'completed', {CREATED_BY}, '2025-10-23');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (19, 'DEL-20251024-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Zanzibar' ORDER BY id ASC LIMIT 1), '2025-10-24', '8547', 'completed', {CREATED_BY}, '2025-10-24');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (20, 'DEL-20251027-JAMES', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-27', '5696', 'completed', {CREATED_BY}, '2025-10-27');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (21, 'DEL-20251014-LUCKSON', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Mafinga' ORDER BY id ASC LIMIT 1), '2025-10-14', '5289', 'completed', {CREATED_BY}, '2025-10-14');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (22, 'DEL-20250903-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Makambako' ORDER BY id ASC LIMIT 1), '2025-09-03', '2656', 'completed', {CREATED_BY}, '2025-09-03');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (23, 'DEL-20250904-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-04', '1391', 'completed', {CREATED_BY}, '2025-09-04');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (24, 'DEL-20250905-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-05', '6411', 'completed', {CREATED_BY}, '2025-09-05');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (25, 'DEL-20250908-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-08', '8731', 'completed', {CREATED_BY}, '2025-09-08');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (26, 'DEL-20250909-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-09', '6149', 'completed', {CREATED_BY}, '2025-09-09');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (27, 'DEL-20250910-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-10', '9530', 'completed', {CREATED_BY}, '2025-09-10');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (28, 'DEL-20250911-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Zanzibar' ORDER BY id ASC LIMIT 1), '2025-09-11', '5306', 'completed', {CREATED_BY}, '2025-09-11');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (29, 'DEL-20250912-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-12', '3398', 'completed', {CREATED_BY}, '2025-09-12');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (30, 'DEL-20250915-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-15', '1608', 'completed', {CREATED_BY}, '2025-09-15');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (31, 'DEL-20250916-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Iringa' ORDER BY id ASC LIMIT 1), '2025-09-16', '7777', 'completed', {CREATED_BY}, '2025-09-16');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (32, 'DEL-20250917-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-17', '7107', 'completed', {CREATED_BY}, '2025-09-17');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (33, 'DEL-20250918-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-18', '1046', 'completed', {CREATED_BY}, '2025-09-18');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (34, 'DEL-20250919-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-19', '6285', 'completed', {CREATED_BY}, '2025-09-19');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (35, 'DEL-20250922-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-22', '6196', 'completed', {CREATED_BY}, '2025-09-22');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (36, 'DEL-20250923-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-23', '2282', 'completed', {CREATED_BY}, '2025-09-23');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (37, 'DEL-20250925-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-09-25', '3439', 'completed', {CREATED_BY}, '2025-09-25');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (38, 'DEL-20250929-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-29', '5990', 'completed', {CREATED_BY}, '2025-09-29');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (39, 'DEL-20250930-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-09-30', '7360', 'completed', {CREATED_BY}, '2025-09-30');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (40, 'DEL-20251001-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-01', '4993', 'completed', {CREATED_BY}, '2025-10-01');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (41, 'DEL-20251002-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Arusha' ORDER BY id ASC LIMIT 1), '2025-10-02', '7049', 'completed', {CREATED_BY}, '2025-10-02');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (42, 'DEL-20251003-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Iringa' ORDER BY id ASC LIMIT 1), '2025-10-03', '7946', 'completed', {CREATED_BY}, '2025-10-03');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (43, 'DEL-20251006-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-06', '7311', 'completed', {CREATED_BY}, '2025-10-06');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (44, 'DEL-20251007-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-07', '4964', 'completed', {CREATED_BY}, '2025-10-07');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (45, 'DEL-20251010-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-10', '6751', 'completed', {CREATED_BY}, '2025-10-10');
INSERT IGNORE INTO `{PREFIX}kit_deliveries` (id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_by, created_at) VALUES (46, 'DEL-20251030-OSCAR', (COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1))), (SELECT id FROM `{PREFIX}kit_operating_cities` WHERE city_name='Dar Es Salaam' ORDER BY id ASC LIMIT 1), '2025-10-30', '8448', 'completed', {CREATED_BY}, '2025-10-30');

INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('FORMULA 4 X 4 SPRINGS+ SHOCKS + KNIFE BLADES WB- 4314 Supplier: FORMULAR 4 X 4 Date: 2025/07/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ashley' AND surname='Calavarius' ORDER BY id ASC LIMIT 1), 'pending', 4000, 'INV-20250716-00001', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-C640Z5K5', 246.0, 149.64, 1890.2370000000003, 27.0, 0.04, 1080.0, 320.31, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4000, 'FORMULA 4 X 4 SPRINGS', 2, 0.0, 4.5, 0.007627, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4000, 'SHOCKS', 2, 0.0, 4.5, 0.007627, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4000, 'KNIFE BLADES', 2, 0.0, 4.5, 0.007627, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('MONTAGUE UMBRELLAS + PVC COVERS + DECK MOUNTS/ BRUSSHED   4426  MAXIM DECOR & DESIGN  2025/08/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Robanda' AND surname='Serengeti' ORDER BY id ASC LIMIT 1), 'pending', 4001, 'INV-20251031-00001', 82000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-W9TY4M17', 44.99, 44.99, 44.99, 100.0, 0.75, 4000.0, 5150.47, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4001, 'MONTAGUE UMBRELLAS', 5, 0.0, 6.67, 0.049052, 0.0, '3018');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4001, 'PVC COVERS', 5, 0.0, 6.67, 0.049052, 0.0, '3018');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4001, 'DECK MOUNTS/ BRUSSHED', 5, 0.0, 6.67, 0.049052, 0.0, '3018');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('KINGSLIN PEDESTAL + MAXIMUS MIRROR + DINING CHAIR + SIDE TABLE  4450  WHECO  2025/08/11', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dipen' AND surname='Shah' ORDER BY id ASC LIMIT 1), 'pending', 4002, 'INV-20251031-00002', 42033.06, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-QL27VIM4', 89.0, 45.0, 109.0, 210.0, 2.1, 8400.0, 14741.43, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4002, 'KINGSLIN PEDESTAL', 10, 0.0, 5.25, 0.052648, 0.0, '01H-SO499706');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4002, 'MAXIMUS MIRROR', 10, 0.0, 5.25, 0.052648, 0.0, '01H-SO499706');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4002, 'DINING CHAIR', 10, 0.0, 5.25, 0.052648, 0.0, '01H-SO499706');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4002, 'SIDE TABLE', 10, 0.0, 5.25, 0.052648, 0.0, '01H-SO499706');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('VEST HOOD + RASH VESTS + STANDARD DIVE BOOT  4480  REEF WET SUITS   2025/08/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=2 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='David' AND surname='Bega' ORDER BY id ASC LIMIT 1), 'pending', 4003, 'INV-20251031-00003', 39180.00, 0, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-VEKJRJCC', 142.57560776853134, 142.57560776853134, 188.57560776853134, 48.989999999999995, 0.27, 1960.0, 1912.35, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4003, 'VEST HOOD', 1, 0.0, 16.33, 0.091064, 0.0, '7808');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4003, 'RASH VESTS', 1, 0.0, 16.33, 0.091064, 0.0, '7808');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4003, 'STANDARD DIVE BOOT', 1, 0.0, 16.33, 0.091064, 0.0, '7808');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('SAFE + STORAGE BIN  4481  SCOTS MAN ICE SYSTEMS  2025/08/18', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ngoro Ngoro Safari' AND surname='Lodge' ORDER BY id ASC LIMIT 1), 'pending', 4004, 'INV-20251031-00004', 98909.98, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-D4WYWRTN', 47.0, 38.0, 117.53771044695134, 178.0, 2.28, 7120.0, 15961.2, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4004, 'SAFE', 1, 0.0, 89.0, 1.140086, 0.0, 'IN288398');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4004, 'STORAGE BIN', 1, 0.0, 89.0, 1.140086, 0.0, 'IN288398');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('50AMP PLUG + FUSE HOLDER + BATTREY  CABLE + CRIMP LUG  4484  NEIL WOOLRIDGE  2025/08/18', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Fes Agric' AND surname='Services' ORDER BY id ASC LIMIT 1), 'pending', 4005, 'INV-20251031-00005', 55101.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-MANPFIW6', 220.0, 100.0, 140.34699641074286, 120.0, 1.08, 4800.0, 7545.94, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4005, '50AMP PLUG', 2, 0.0, 15.0, 0.134749, 0.0, '74385');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4005, 'FUSE HOLDER', 2, 0.0, 15.0, 0.134749, 0.0, '74385');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4005, 'BATTREY  CABLE', 2, 0.0, 15.0, 0.134749, 0.0, '74385');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4005, 'CRIMP LUG', 2, 0.0, 15.0, 0.134749, 0.0, '74385');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)   VALUES ('FIRE LIGHTERS  4485  ISLAND SUPPLY  2025/08/18', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Asilia Lodges And' AND surname='Camps' ORDER BY id ASC LIMIT 1), 'pending', 4006, 'INV-20251031-00006', 0.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:0:{}}', 'TRK-W59YYE9X', 53.55644664991417, 53.55644664991417, 53.55644664991417, 858.0, 1.53, 34320.0, 10715.75, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price) VALUES (4006, 'FIRE LIGHTERS', 3, 0.0, 286.0, 0.510274, 0.0);










INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('STEEL BOND +LEATHER DYE + LEATHER DYE SAMPLES  4488  STEELBOND  2025/08/19', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nabaki' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4007, 'INV-20251031-00007', 3735.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-77U2K2LV', 101.0, 73.0, 158.70976132109496, 25.0, 0.04, 1000.0, 346.27, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4007, 'STEEL BOND', 2, 0.0, 4.17, 0.008245, 0.0, 'IN100741');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4007, 'LEATHER DYE', 2, 0.0, 4.17, 0.008245, 0.0, 'IN100741');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4007, 'LEATHER DYE SAMPLES', 2, 0.0, 4.17, 0.008245, 0.0, 'IN100741');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('SIZING TOOL   4489  OLI ELECTRICAL VIBRATORS  2025/08/19', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Kayen' AND surname='Investments' ORDER BY id ASC LIMIT 1), 'pending', 4008, 'INV-20251031-00008', 12248.02, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-8FWO1CCD', 99.46918125163134, 99.46918125163134, 356.8315806734927, 92.0, 0.17, 3680.0, 1204.28, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4008, 'SIZING TOOL', 1, 0.0, 92.0, 0.17204, 0.0, '300035');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('RODS + TUBE ACCESSORIES  4497  WOODMEND  2025/08/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ashley' AND surname='Calavarius' ORDER BY id ASC LIMIT 1), 'pending', 4009, 'INV-20251031-00009', 1000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-TP79IUKO', 110.41084932759509, 110.41084932759509, 709.2708912732385, 9.0, 0.1, 360.0, 634.85, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4009, 'RODS', 1, 0.0, 4.5, 0.045346, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4009, 'TUBE ACCESSORIES', 1, 0.0, 4.5, 0.045346, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('BLACK CONTAINER   4499  THE COURIER GUY  2025/08/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jan van' AND surname='Blommestein' ORDER BY id ASC LIMIT 1), 'pending', 4010, 'INV-20251031-00010', 2000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-WNATX8HS', 169.3963851583657, 169.3963851583657, 281.08985279643866, 4.0, 0.03, 160.0, 223.15, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4010, 'BLACK CONTAINER', 1, 0.0, 4.0, 0.031878, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('ORANGE BUOY CYLINDER + YELLOW TRIANGLE BUOY CONICAL  4504  TEXWISE  2025/08/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Patrick' AND surname='Roberts' ORDER BY id ASC LIMIT 1), 'pending', 4011, 'INV-20251031-00011', 24036.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-FYXO3FXH', 100.79996679998067, 100.79996679998067, 255.21204561562735, 29.0, 0.17, 1160.0, 1161.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4011, 'ORANGE BUOY CYLINDER', 1, 0.0, 14.5, 0.082944, 0.0, 'IN098141');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4011, 'YELLOW TRIANGLE BUOY CONICAL', 1, 0.0, 14.5, 0.082944, 0.0, 'IN098141');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('COCONUT FLAKES + PROTEIN FRUIT SHOT + BAKED GRANOLA   4505  COURIER GUY  2025/08/25', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Wendy' AND surname='Erasmus' ORDER BY id ASC LIMIT 1), 'pending', 4012, 'INV-20251031-00012', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-AQVUB7PR', 180.7859836380336, 180.7859836380336, 430.36598363803364, 4.0, 0.01, 160.0, 89.18, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4012, 'COCONUT FLAKES', 1, 0.0, 1.33, 0.004247, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4012, 'PROTEIN FRUIT SHOT', 1, 0.0, 1.33, 0.004247, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4012, 'BAKED GRANOLA', 1, 0.0, 1.33, 0.004247, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('PEACH TEA + CREAM WHIPPER + POPPING BOBAS  4506  SARAH KHANBHAI  2025/08/25', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sarah' AND surname='Khanbhai' ORDER BY id ASC LIMIT 1), 'pending', 4013, 'INV-20251031-00013', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-JZ66IC19', 193.0, 120.0, 510.7249415855931, 9.0, 0.03, 360.0, 206.08, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4013, 'PEACH TEA', 1, 0.0, 3.0, 0.009813, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4013, 'CREAM WHIPPER', 1, 0.0, 3.0, 0.009813, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4013, 'POPPING BOBAS', 1, 0.0, 3.0, 0.009813, 0.0, 'P');




















INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('TP 201 SEMI AUTO STRAP + TRANSPAK PRESS + TRANSPAK  REAR BAR ASSY``  4510  BIDVEST AFCON  2025/08/26', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dekker' AND surname='Chrysanten' ORDER BY id ASC LIMIT 1), 'pending', 4016, 'INV-20251031-00016', 200491.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-H6Z9TICV', 57.0, 46.4, 159.4, 338.01, 1.8599999999999999, 13520.0, 12919.1, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4016, 'TP 201 SEMI AUTO STRAP', 1, 0.0, 112.67, 0.615195, 0.0, 'ME166336');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4016, 'TRANSPAK PRESS', 1, 0.0, 112.67, 0.615195, 0.0, 'ME166336');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4016, 'TRANSPAK  REAR BAR ASSY``', 1, 0.0, 112.67, 0.615195, 0.0, 'ME166336');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('CALLEBAUTS + SPRINKLE NUTS + PUMPKIN SEEDS + ALMOND FLAKES +QUICHE PAN + GLUCOSE SYRUP  4511  CAB FOODS  2025/08/26', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sarah' AND surname='Khanbhai' ORDER BY id ASC LIMIT 1), 'pending', 4017, 'INV-20251031-00017', 4000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-PZZQUQ52', 66.4, 66.4, 142.4, 224.0, 0.68, 8960.0, 4766.61, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4017, 'CALLEBAUTS', 1, 0.0, 37.33, 0.113491, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4017, 'SPRINKLE NUTS', 1, 0.0, 37.33, 0.113491, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4017, 'PUMPKIN SEEDS', 1, 0.0, 37.33, 0.113491, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4017, 'ALMOND FLAKES', 1, 0.0, 37.33, 0.113491, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4017, 'QUICHE PAN', 1, 0.0, 37.33, 0.113491, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4017, 'GLUCOSE SYRUP', 1, 0.0, 37.33, 0.113491, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('VELVET PILLOW COVER +TEA TOWEL + FRIDGE MAGNET   4512  PRIME TIME EXPRESS  2025/08/26', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chloe Sheidan' AND surname='Johnson' ORDER BY id ASC LIMIT 1), 'pending', 4018, 'INV-20251031-00018', 21160.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-S9A21K3Y', 60.0, 38.34, 84.34, 11.0, 0.1, 440.0, 706.02, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4018, 'VELVET PILLOW COVER', 1, 0.0, 3.67, 0.03362, 0.0, 'INV-5982');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4018, 'TEA TOWEL', 1, 0.0, 3.67, 0.03362, 0.0, 'INV-5982');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4018, 'FRIDGE MAGNET', 1, 0.0, 3.67, 0.03362, 0.0, 'INV-5982');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('BLUE BELT + GOLF BALLS + CABLE TIES + BATTERY PACK   4513  COURIER GUY  2025/08/26', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 13, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Steve' AND surname='Luka' ORDER BY id ASC LIMIT 1), 'pending', 4019, 'INV-20251031-00019', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-CIXVO6RM', 66.0, 44.0, 197.0, 15.0, 0.05, 600.0, 360.36, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4019, 'BLUE BELT', 1, 0.0, 3.75, 0.01287, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4019, 'GOLF BALLS', 1, 0.0, 3.75, 0.01287, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4019, 'CABLE TIES', 1, 0.0, 3.75, 0.01287, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4019, 'BATTERY PACK', 1, 0.0, 3.75, 0.01287, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3 ROLLS MATERIALS   4515  VELVET PRODUCTS  2025/08/26', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='David' AND surname='Bega' ORDER BY id ASC LIMIT 1), 'pending', 4020, 'INV-20251031-00020', 680.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-SFD9P8F6', 393.07952664144835, 393.07952664144835, 393.07952664144835, 54.989999999999995, 0.03, 2200.0, 210.0, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4020, '3 ROLLS MATERIALS', 3, 0.0, 18.33, 0.01, 0.0, 'INV199084`');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('JERSEY CHACOAL + JERSERY FATIGUES +   4516  WORK WEAR DEPOT  2025/08/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Kat' AND surname='West' ORDER BY id ASC LIMIT 1), 'pending', 4021, 'INV-20251031-00021', 17627.07, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-GHLUWOVJ', 242.74245557039958, 242.74245557039958, 1653.3944715701527, 10.0, 0.08, 400.0, 533.82, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4021, 'JERSEY CHACOAL', 1, 0.0, 5.0, 0.03813, 0.0, 'INV-EDV0191781');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4021, 'JERSERY FATIGUES', 1, 0.0, 5.0, 0.03813, 0.0, 'INV-EDV0191781');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('FLUSH PLATES  4517  YOURSPACE BATHROOMS  2025/09/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4022, 'INV-20251031-00022', 705476.64, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-SS5RKUJH', 155.0, 60.99025818331102, 121.99025818331103, 35.0, 0.12, 1400.0, 823.37, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4022, 'FLUSH PLATES', 1, 0.0, 35.0, 0.117624, 0.0, '5596');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('PAIR SHOES + CLOTHES + TRAVEL BOTTLE SET + LUNCH BOX  4518  COURIER GUY  2025/09/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='ALIASGHER' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4023, 'INV-20251031-00023', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-N159HZI2', 32.0, 15.0, 24.0, 9.0, 0.03, 360.0, 238.57, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4023, 'PAIR SHOES', 3, 0.0, 0.75, 0.00284, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4023, 'CLOTHES', 3, 0.0, 0.75, 0.00284, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4023, 'TRAVEL BOTTLE SET', 3, 0.0, 0.75, 0.00284, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4023, 'LUNCH BOX', 3, 0.0, 0.75, 0.00284, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('ICE MAKER  4519  THE COURIER GUY  2025/09/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Bianca' AND surname='Thielke' ORDER BY id ASC LIMIT 1), 'pending', 4024, 'INV-20251031-00024', 4000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-Q6PUM4MY', 158.0, 101.0, 159.0, 16.0, 0.09, 640.0, 647.15, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4024, 'ICE MAKER', 1, 0.0, 16.0, 0.09245, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('STATIONARY  4520  AMAZON  2025/09/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Manmeet' AND surname='Singh' ORDER BY id ASC LIMIT 1), 'pending', 4025, 'INV-20251031-00025', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-8R1TXCKA', 94.0, 44.63, 244.63, 1.0, 0.0, 40.0, 23.52, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4025, 'STATIONARY', 1, 0.0, 1.0, 0.00336, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('GYM QUIPMENT  4521    2025/09/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Elliot' AND surname='Kinsey' ORDER BY id ASC LIMIT 1), 'pending', 4026, 'INV-20251031-00026', 3273.93, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-KABS250I', 63.026306222433746, 63.026306222433746, 193.358854186413, 15.0, 0.09, 600.0, 618.09, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4026, 'GYM QUIPMENT', 1, 0.0, 15.0, 0.088298, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('ANGLED WEARSTRIP + CLIP ON Z PROFILE   4522  ASPEN DISTRIBUTORS  2025/09/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Conley' AND surname='Cooke' ORDER BY id ASC LIMIT 1), 'pending', 4027, 'INV-20251031-00027', 26225.68, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-NP9EIESO', 117.33896721880934, 117.33896721880934, 413.6265971081995, 39.0, 0.15000000000000002, 1560.0, 1061.82, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4027, 'ANGLED WEARSTRIP', 3, 0.0, 6.5, 0.025281, 0.0, 'Q-001262');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4027, 'CLIP ON Z PROFILE', 3, 0.0, 6.5, 0.025281, 0.0, 'Q-001262');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('PERSONAL GOODIES  4523  VUKANI AFRIKA  2025/08/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jesse' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4028, 'INV-20251031-00028', 2000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-ZK0IWACJ', 109.0, 80.9507397481297, 288.9507397481297, 113.0, 0.2, 4520.0, 1415.76, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4028, 'PERSONAL GOODIES', 4, 0.0, 28.25, 0.050563, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('STEROIDS  4524  IMRAN COURIER  2025/09/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Imran' AND surname='Dedhar' ORDER BY id ASC LIMIT 1), 'pending', 4029, 'INV-20251031-00029', 2000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-CQOTALXD', 120.0, 89.43, 246.8066110233906, 1.0, 0.01, 40.0, 54.9, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4029, 'STEROIDS', 1, 0.0, 1.0, 0.007843, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('ARGUS MOTORING + SHIELD  CAR WASH   4525  TAKEALOT  2025/09/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Justin' AND surname='Trappe' ORDER BY id ASC LIMIT 1), 'pending', 4030, 'INV-20251031-00030', 2000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-X0YUK0W2', 80.0, 20.0, 31.0, 52.019999999999996, 1.3800000000000001, 2080.0, 9506.78, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4030, 'ARGUS MOTORING', 3, 0.0, 8.67, 0.226352, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4030, 'SHIELD  CAR WASH', 3, 0.0, 8.67, 0.226352, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)   VALUES ('GROCERIES  4526    2025/08/28', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=3 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Elena de' AND surname='Villiers' ORDER BY id ASC LIMIT 1), 'pending', 4031, 'INV-20251031-00031', 0.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:0:{}}', 'TRK-ARMX5HE9', 307.0, 121.0, 516.649816092445, 39.0, 0.24, 1560.0, 1682.1, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price) VALUES (4031, 'GROCERIES', 1, 0.0, 39.0, 0.2403, 0.0);










INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X1 CARPET  4527  HEERKAT CARPETS  2025/08/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=3 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Heikki' AND surname='Niskala' ORDER BY id ASC LIMIT 1), 'pending', 4032, 'INV-20251031-00032', 6900.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-12338C4M', 120.27068524965689, 120.27068524965689, 593.5010050054079, 10.0, 0.03, 400.0, 205.8, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4032, 'X1 CARPET', 1, 0.0, 10.0, 0.0294, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('PATCHWORK KILIM + KAZAK + WOOL SILK RUG + TEXTURED RUGS  4528  TIRMAH EXCLUSIVE  2025/08/28', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dipen' AND surname='Shah' ORDER BY id ASC LIMIT 1), 'pending', 4033, 'INV-20251031-00033', 57400.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-DX5US9X0', 63.0, 56.88953430077572, 104.88953430077572, 67.0, 0.26, 2680.0, 1821.95, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4033, 'PATCHWORK KILIM', 2, 0.0, 8.38, 0.032535, 0.0, '04-AUGT');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4033, 'KAZAK', 2, 0.0, 8.38, 0.032535, 0.0, '04-AUGT');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4033, 'WOOL SILK RUG', 2, 0.0, 8.38, 0.032535, 0.0, '04-AUGT');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4033, 'TEXTURED RUGS', 2, 0.0, 8.38, 0.032535, 0.0, '04-AUGT');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('BOOKS  4529  ACCELERATED EDUCATION  2025/08/28', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='David' AND surname='Scott' ORDER BY id ASC LIMIT 1), 'pending', 4034, 'INV-20251031-00034', 78293.25, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-NV1KRQC8', 211.0, 102.4543044381351, 432.37337305173367, 106.02000000000001, 0.18, 4240.0, 1265.2, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4034, 'BOOKS', 6, 0.0, 17.67, 0.030124, 0.0, 'INV87011');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X1 WHITE ALL STAR   4530  TRU WORTHS  2025/08/28', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Goretty Dos' AND surname='Ramos' ORDER BY id ASC LIMIT 1), 'pending', 4035, 'INV-20251031-00035', 1000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-ZCGBYHPR', 59.0, 31.0, 49.0, 1.0, 0.02, 40.0, 164.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4035, 'X1 WHITE ALL STAR', 1, 0.0, 1.0, 0.02346, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X3 RUGS  4531  TRITON EXPRESS   2025/08/28', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dipen' AND surname='Shah' ORDER BY id ASC LIMIT 1), 'pending', 4036, 'INV-20251031-00036', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-9JH3GRMN', 72.3183562234068, 72.3183562234068, 257.54207901151136, 87.99, 0.12, 3520.0, 905.48, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4036, 'X3 RUGS', 3, 0.0, 29.33, 0.043118, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('ASGARD + POSTER BED BASE KING XL  4533  WHECO   2025/08/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dipen' AND surname='Shah' ORDER BY id ASC LIMIT 1), 'pending', 4037, 'INV-20251031-00037', 24449.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-JSUK6W0U', 65.0, 41.0, 191.0, 244.0, 3.4000000000000004, 9760.0, 23803.58, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4037, 'ASGARD', 5, 0.0, 24.4, 0.340051, 0.0, '01H-INV051429');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4037, 'POSTER BED BASE KING XL', 5, 0.0, 24.4, 0.340051, 0.0, '01H-INV051429');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('SURFBOARDS  4535  DOWER SURFBOARDS  2025/08/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jack' AND surname='Dower' ORDER BY id ASC LIMIT 1), 'pending', 4039, 'INV-20251031-00039', 10350.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-FDLEWWDF', 77.6058352858539, 77.6058352858539, 198.6058352858539, 21.0, 0.39, 840.0, 2727.65, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4039, 'SURFBOARDS', 1, 0.0, 21.0, 0.389664, 0.0, '00000006');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('BRAKE PADS + AIR FILTER + BRAKE PADSFRONT KTM  4536  SPEEDHUT MOTORSHOP  2025/08/30', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Joe' AND surname='Vipond' ORDER BY id ASC LIMIT 1), 'pending', 4040, 'INV-20251031-00040', 6006.42, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-T8UN1WPC', 112.825742326643, 112.825742326643, 192.825742326643, 9.0, 0.03, 360.0, 195.52, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4040, 'BRAKE PADS', 1, 0.0, 3.0, 0.00931, 0.0, '048422');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4040, 'AIR FILTER', 1, 0.0, 3.0, 0.00931, 0.0, '048422');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4040, 'BRAKE PADSFRONT KTM', 1, 0.0, 3.0, 0.00931, 0.0, '048422');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('BABY PRAM +BABY MATRAS   4537  WESLEY GOLD  2025/09/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Wesley' AND surname='Gold' ORDER BY id ASC LIMIT 1), 'pending', 4041, 'INV-20251031-00041', 2000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-SQRIBBD7', 166.76374255776153, 166.76374255776153, 465.69941121094604, 28.0, 0.16, 1120.0, 1204.52, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4041, 'BABY PRAM', 2, 0.0, 7.0, 0.043019, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4041, 'BABY MATRAS', 2, 0.0, 7.0, 0.043019, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('SIENNA ARM CHAIRS  4538  DP FURNITURE  2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Mirriam' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4042, 'INV-20251031-00042', 31211.86, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-KN4R1U90', 136.0, 96.607, 560.9773478118944, 265.0, 3.8, 10600.0, 26503.68, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4042, 'SIENNA ARM CHAIRS', 5, 0.0, 53.0, 0.757248, 0.0, 'P46624');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('CLOTHES  4539  TRUWORTHS  2025/09/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Goretty Dos' AND surname='Ramos' ORDER BY id ASC LIMIT 1), 'pending', 4043, 'INV-20251031-00043', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-RQJMT16T', 178.86779170952477, 178.86779170952477, 574.0749413651913, 1.0, 0.01, 40.0, 41.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4043, 'CLOTHES', 1, 0.0, 1.0, 0.005888, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('SIDEBOARD MEZZE + SIDE TABLE + COFFE TABLE + TABLE LAMP + DESK + FLOOR STAND + SHADE TWILL  4540  CECILE & BOYD   2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dipen' AND surname='Shah' ORDER BY id ASC LIMIT 1), 'pending', 4044, 'INV-20251031-00044', 122283.47, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-OQRFOEJO', 46.0, 32.0, 66.0, 388.05, 4.35, 15520.0, 30252.99, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4044, 'SIDEBOARD MEZZE', 15, 0.0, 3.7, 0.041161, 0.0, 'IAE07331');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4044, 'SIDE TABLE', 15, 0.0, 3.7, 0.041161, 0.0, 'IAE07331');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4044, 'COFFE TABLE', 15, 0.0, 3.7, 0.041161, 0.0, 'IAE07331');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4044, 'TABLE LAMP', 15, 0.0, 3.7, 0.041161, 0.0, 'IAE07331');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4044, 'DESK', 15, 0.0, 3.7, 0.041161, 0.0, 'IAE07331');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4044, 'FLOOR STAND', 15, 0.0, 3.7, 0.041161, 0.0, 'IAE07331');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4044, 'SHADE TWILL', 15, 0.0, 3.7, 0.041161, 0.0, 'IAE07331');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('BOOK SAMPLES  4541  RHENUS LOGISTICS  2025/09/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ashley' AND surname='Calavarius' ORDER BY id ASC LIMIT 1), 'pending', 4045, 'INV-20251031-00045', 1500.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-5BMWR2WM', 24.0, 31.0, 15.0, 29.0, 0.12, 1160.0, 869.78, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4045, 'BOOK SAMPLES', 1, 0.0, 29.0, 0.124254, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('MINT PEACH BLOOM CORES + GLOW CORES + PEPPERMINT CORES + LIME MINT RUSH CORES  4543  MARELI BOTHA  2025/09/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jan' AND surname='Griesl' ORDER BY id ASC LIMIT 1), 'pending', 4046, 'INV-20251031-00046', 2000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-535FE1ZX', 61.0, 31.0, 46.0, 1.0, 0.01, 40.0, 41.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4046, 'MINT PEACH BLOOM CORES', 1, 0.0, 0.25, 0.001472, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4046, 'GLOW CORES', 1, 0.0, 0.25, 0.001472, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4046, 'PEPPERMINT CORES', 1, 0.0, 0.25, 0.001472, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4046, 'LIME MINT RUSH CORES', 1, 0.0, 0.25, 0.001472, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('TOILET SEAT  4544  YOUR SPACE BATHROOM  2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=1 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4047, 'INV-20251031-00047', 16463.40, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-RSQTCCSG', 106.0, 33.0, 52.0, 28.0, 0.1, 1120.0, 677.32, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4047, 'TOILET SEAT', 1, 0.0, 28.0, 0.09676, 0.0, '5550');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('6 stockholm chairs + scatters + candle holder  WB: 4545 Supplier: Maxim Decor Date: 2025/09/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Robanda' AND surname='Serengeti' ORDER BY id ASC LIMIT 1), 'pending', 4048, 'INV-20251031-00048', 5000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-A7SPWBIH', 167.2466105524851, 167.2466105524851, 450.9766105524851, 234.0, 4.68, 9360.0, 32746.89, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4048, '6 stockholm chairs', 1, 0.0, 78.0, 1.559376, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4048, 'scatters', 1, 0.0, 78.0, 1.559376, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4048, 'candle holder', 1, 0.0, 78.0, 1.559376, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Solar pumps WB: 4546 Supplier: The Sun Pays  Date: 2025/09/03', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 14, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Micheal' AND surname='Coudrey' ORDER BY id ASC LIMIT 1), 'pending', 4049, 'INV-20251031-00049', 1796.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-4MXXM8K0', 0, 0, 0, 11.0, 0.45, 440.0, 3123.12, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4049, 'Solar pumps', 1, 0.0, 11.0, 0.44616, 0.0, '55199');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X51 cranbery WB: 4547 Supplier: Pn Door Date: 2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=25 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Derek' AND surname='Cooper' ORDER BY id ASC LIMIT 1), 'pending', 4050, 'INV-20251031-00050', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-X7QA5OY6', 0, 0, 0, 5.0, 0.03, 200.0, 222.43, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4050, 'X51 cranbery', 1, 0.0, 5.0, 0.031775, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Saddles + m/sheet + laser univ + rope towing WB: 4548 Supplier: Girand Racing Date: 2025/09/03', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Anne North ( Yatch Club' AND surname=')' ORDER BY id ASC LIMIT 1), 'pending', 4051, 'INV-20251031-00051', 97057.88, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-IL1DYV23', 0, 0, 0, 48.0, 0.27, 1920.0, 1909.02, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4051, 'Saddles', 1, 0.0, 12.0, 0.068179, 0.0, 'IN18604');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4051, 'm/sheet', 1, 0.0, 12.0, 0.068179, 0.0, 'IN18604');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4051, 'laser univ', 1, 0.0, 12.0, 0.068179, 0.0, 'IN18604');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4051, 'rope towing', 1, 0.0, 12.0, 0.068179, 0.0, 'IN18604');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Brooms + books + forks WB: 4549 Supplier: Take Alot Date: 2025/09/03', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 14, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=22 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Micheal' AND surname='Coudrey' ORDER BY id ASC LIMIT 1), 'pending', 4052, 'INV-20251031-00052', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-TSTWE1P7', 0, 0, 0, 18.0, 0.25, 720.0, 1720.81, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4052, 'Brooms', 1, 0.0, 6.0, 0.081943, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4052, 'books', 1, 0.0, 6.0, 0.081943, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4052, 'forks', 1, 0.0, 6.0, 0.081943, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Safari bow tent + 1l water bottle with canvas cover WB: 4550 Supplier: Campmor Outdoor  Date: 2025/09/03', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=22 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tom ( Sable Hills' AND surname='Farm)' ORDER BY id ASC LIMIT 1), 'pending', 4053, 'INV-20251031-00053', 6898.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-P4JI0QMK', 0, 0, 0, 42.0, 0.23, 1680.0, 1588.1, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4053, 'Safari bow tent', 1, 0.0, 21.0, 0.113436, 0.0, 'IN90066');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4053, '1l water bottle with canvas cover', 1, 0.0, 21.0, 0.113436, 0.0, 'IN90066');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Lasterine mint + body wash + polishing sugar + roll on WB: 4551 Supplier: Clicks Date: 2025/09/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=27 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Goretty Dos' AND surname='Ramos' ORDER BY id ASC LIMIT 1), 'pending', 4054, 'INV-20251031-00054', 1000.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-B1HQHRI9', 0, 0, 0, 6.0, 0.02, 240.0, 161.45, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4054, 'Lasterine mint', 1, 0.0, 1.5, 0.005766, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4054, 'body wash', 1, 0.0, 1.5, 0.005766, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4054, 'polishing sugar', 1, 0.0, 1.5, 0.005766, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4054, 'roll on', 1, 0.0, 1.5, 0.005766, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Sample box + ring book + bedding ringbook + throws ringbook WB: 4552 Supplier: Hertex Fabrics Date: 2025/09/04', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ashley' AND surname='Calavarius' ORDER BY id ASC LIMIT 1), 'pending', 4055, 'INV-20251031-00055', 0.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-VT452FJ3', 0, 0, 0, 71.0, 0.61, 2840.0, 4252.37, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4055, 'Sample box', 1, 0.0, 17.75, 0.15187, 0.0, 'INV00735877/10');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4055, 'ring book', 1, 0.0, 17.75, 0.15187, 0.0, 'INV00735877/10');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4055, 'bedding ringbook', 1, 0.0, 17.75, 0.15187, 0.0, 'INV00735877/10');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4055, 'throws ringbook', 1, 0.0, 17.75, 0.15187, 0.0, 'INV00735877/10');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Rechargable pain massager + infinity -8 model dual channel WB: 4553 Supplier: Takealot Date: 2025/09/04', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=23 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Zander' AND surname='Englebrecht' ORDER BY id ASC LIMIT 1), 'pending', 4056, 'INV-20251031-00056', 500.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-98UW1LZV', 0, 0, 0, 1.0, 0.01, 40.0, 80.64, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4056, 'Rechargable pain massager', 1, 0.0, 0.5, 0.00576, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4056, 'infinity -8 model dual channel', 1, 0.0, 0.5, 0.00576, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Kaufmann secateur sunflower + kaufmann secateur spring sunflower WB: 4554 Supplier: Agrinet  Date: 2025/09/05', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=24 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dean' AND surname='Peterson' ORDER BY id ASC LIMIT 1), 'pending', 4057, 'INV-20251031-00057', 6913.60, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-SLS6MA7M', 0, 0, 0, 31.0, 0.03, 1240.0, 203.48, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4057, 'Kaufmann secateur sunflower', 1, 0.0, 15.5, 0.014534, 0.0, '1508995');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4057, 'kaufmann secateur spring sunflower', 1, 0.0, 15.5, 0.014534, 0.0, '1508995');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Kitchen sink WB: 4555 Supplier: Take Alot Date: 2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=25 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Martie' AND surname='Botha' ORDER BY id ASC LIMIT 1), 'pending', 4058, 'INV-20251031-00058', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-W91PDCZT', 0, 0, 0, 75.0, 0.2, 3000.0, 1394.96, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4058, 'Kitchen sink', 1, 0.0, 75.0, 0.19928, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Weet bix + omo + jungle oats + bokomo crunch WB: 4556 Supplier: Julia Redfern Date: 2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=25 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Julia' AND surname='Redfern' ORDER BY id ASC LIMIT 1), 'pending', 4059, 'INV-20251031-00059', 1000.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-QSBEMFWG', 0, 0, 0, 22.0, 0.09, 880.0, 658.81, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4059, 'Weet bix', 1, 0.0, 5.5, 0.023529, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4059, 'omo', 1, 0.0, 5.5, 0.023529, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4059, 'jungle oats', 1, 0.0, 5.5, 0.023529, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4059, 'bokomo crunch', 1, 0.0, 5.5, 0.023529, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('5 x jacobs + x3 tabasco WB: 4557 Supplier: Pick N Pay Date: 2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=25 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Julia' AND surname='Redfern' ORDER BY id ASC LIMIT 1), 'pending', 4060, 'INV-20251031-00060', 1000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-P9W8EV98', 0, 0, 0, 3.0, 0.01, 120.0, 86.02, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4060, '5 x jacobs', 1, 0.0, 1.5, 0.006144, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4060, 'x3 tabasco', 1, 0.0, 1.5, 0.006144, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Basin taps + basin mixer + bath tapes + stand pipes + shower mixer WB: 4558 Supplier: Victorian Bathrooms Date: 2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=25 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4061, 'INV-20251031-00061', 25699.98, 0, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-EVOWB8XS', 0, 0, 0, 18.0, 0.09, 720.0, 622.59, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4061, 'Basin taps', 1, 0.0, 3.6, 0.017788, 0.0, 'INV879');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4061, 'basin mixer', 1, 0.0, 3.6, 0.017788, 0.0, 'INV879');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4061, 'bath tapes', 1, 0.0, 3.6, 0.017788, 0.0, 'INV879');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4061, 'stand pipes', 1, 0.0, 3.6, 0.017788, 0.0, 'INV879');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4061, 'shower mixer', 1, 0.0, 3.6, 0.017788, 0.0, 'INV879');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Radius dune + boracay kelp + marsali romance (22 rugs WB: 4559 Supplier: Hertex Fabrics Date: 2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Altitude' AND surname='Hotel' ORDER BY id ASC LIMIT 1), 'pending', 4062, 'INV-20251031-00062', 74260.76, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-QISMM4MR', 0, 0, 0, 109.0, 0.42, 4360.0, 2921.75, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4062, 'Radius dune', 1, 0.0, 36.33, 0.139131, 0.0, 'SO2454972');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4062, 'boracay kelp', 1, 0.0, 36.33, 0.139131, 0.0, 'SO2454972');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4062, 'marsali romance (22 rugs', 1, 0.0, 36.33, 0.139131, 0.0, 'SO2454972');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Antique brass wall mounted shower WB: 4560 Supplier: Tic Home & Bathroom Pty Ltd Date: 2025/09/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=25 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Annette' AND surname='Simonson' ORDER BY id ASC LIMIT 1), 'pending', 4063, 'INV-20251031-00063', 6290.00, 0, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-W9QZYS9E', 0, 0, 0, 26.0, 0.09, 1040.0, 608.9, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4063, 'Antique brass wall mounted shower', 1, 0.0, 26.0, 0.086986, 0.0, 'P');



























INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Caps WB: 4564 Supplier: Headwear 24 Durban Date: 2025/09/09', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=26 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Rob' AND surname='Chekwaze' ORDER BY id ASC LIMIT 1), 'pending', 4067, 'INV-20251031-00067', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-8R2QEG8U', 0, 0, 0, 38.0, 0.25, 1520.0, 1752.52, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4067, 'Caps', 1, 0.0, 38.0, 0.25036, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Hydrogen water  bottle + pizza oven + gas powerd pizza oven WB: 4566 Supplier: Aramex Date: 2025/09/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=27 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Marlies' AND surname='Gabriel' ORDER BY id ASC LIMIT 1), 'pending', 4069, 'INV-20251031-00069', 2000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-94C65GML', 0, 0, 0, 30.0, 0.25, 1200.0, 1753.47, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4069, 'Hydrogen water  bottle', 1, 0.0, 10.0, 0.083498, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4069, 'pizza oven', 1, 0.0, 10.0, 0.083498, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4069, 'gas powerd pizza oven', 1, 0.0, 10.0, 0.083498, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Heavy duty 550gsm springbok tent body 8.4 x 5m  WB: 4567 Supplier: Canvas And Tent Date: 2025/09/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=27 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tom ( Sable Hills' AND surname='Farm)' ORDER BY id ASC LIMIT 1), 'pending', 4070, 'INV-20251031-00070', 62780.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-OHES0QO1', 0, 0, 0, 220.0, 1.62, 8800.0, 11309.02, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4070, 'Heavy duty 550gsm springbok tent body 8.4 x 5m', 1, 0.0, 220.0, 1.615574, 0.0, 'INV-99800');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X26 tobbaco products WB: 4568 Supplier: Praveen Date: 2025/09/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=27 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Praveena' AND surname='Selvarajah' ORDER BY id ASC LIMIT 1), 'pending', 4071, 'INV-20251031-00071', 2000.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-WB5ZYGI6', 0, 0, 0, 5.0, 0.02, 200.0, 154.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4071, 'X26 tobbaco products', 1, 0.0, 5.0, 0.022032, 0.0, 'P');




















INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Sunflower spray + dog tablets + cat litter + sunlight + sprinklers WB: 4571 Supplier: Take Alot + Checkers Hyper Date: 2025/09/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=27 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4074, 'INV-20251031-00074', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-J31W8OV5', 0, 0, 0, 5.0, 0.04, 200.0, 309.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4074, 'Sunflower spray', 1, 0.0, 1.0, 0.008841, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4074, 'dog tablets', 1, 0.0, 1.0, 0.008841, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4074, 'cat litter', 1, 0.0, 1.0, 0.008841, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4074, 'sunlight', 1, 0.0, 1.0, 0.008841, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4074, 'sprinklers', 1, 0.0, 1.0, 0.008841, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Motor pump + contact spray + switch  WB: 4573 Supplier: Dr Wolf Reiners Date: 2025/09/11', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=28 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Wolfram' AND surname='Reiners' ORDER BY id ASC LIMIT 1), 'pending', 4076, 'INV-20251031-00076', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-S1UZTJS1', 0, 0, 0, 7.0, 0.03, 280.0, 195.8, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4076, 'Motor pump', 1, 0.0, 2.33, 0.009324, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4076, 'contact spray', 1, 0.0, 2.33, 0.009324, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4076, 'switch', 1, 0.0, 2.33, 0.009324, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Salvadore back bar cooler WB: 4574 Supplier: Alpaco Catering & Equipment Date: 2025/09/11', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=28 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chloe Sheidan' AND surname='Johnson' ORDER BY id ASC LIMIT 1), 'pending', 4077, 'INV-20251031-00077', 16986.62, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-UALHUF1R', 0, 0, 0, 58.0, 0.7, 2320.0, 4883.2, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4077, 'Salvadore back bar cooler', 1, 0.0, 58.0, 0.6976, 0.0, 'INV0002463');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Folding table + telescoping fishing gaff hook` WB: 4575 Supplier: Chris Green"S Mom Date: 2025/09/12', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=29 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chris' AND surname='Green' ORDER BY id ASC LIMIT 1), 'pending', 4078, 'INV-20251031-00078', 100.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-3HWPE4AE', 0, 0, 0, 32.0, 0.19, 1280.0, 1322.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4078, 'Folding table', 1, 0.0, 16.0, 0.094444, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4078, 'telescoping fishing gaff hook`', 1, 0.0, 16.0, 0.094444, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Megamaster replacement burners WB: 4577 Supplier: Mega Master Date: 2025/09/11', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=28 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='James' AND surname='Redfern' ORDER BY id ASC LIMIT 1), 'pending', 4080, 'INV-20251031-00080', 966.99, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-D8DXPAY3', 0, 0, 0, 2.0, 0.04, 80.0, 309.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4080, 'Megamaster replacement burners', 1, 0.0, 2.0, 0.044206, 0.0, 'CIN0035962');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Cargo pants + safari shirts + nylon cargo pants WB: 4578 Supplier: Marions Reed Design Date: 2025/09/11', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=28 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ethan' AND surname='Awawa' ORDER BY id ASC LIMIT 1), 'pending', 4081, 'INV-20251031-00081', 164337.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-C1J008X9', 0, 0, 0, 164.0, 0.53, 6560.0, 3713.3, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4081, 'Cargo pants', 1, 0.0, 54.67, 0.176824, 0.0, 'ENT040325');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4081, 'safari shirts', 1, 0.0, 54.67, 0.176824, 0.0, 'ENT040325');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4081, 'nylon cargo pants', 1, 0.0, 54.67, 0.176824, 0.0, 'ENT040325');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Non alcoholic beer ( 166 bottles )  crated WB: 4579 Supplier: Mother City  Date: 2025/09/12', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=29 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4082, 'INV-20251031-00082', 5009.72, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-NA518BEF', 0, 0, 0, 98.0, 0.57, 3920.0, 3974.12, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4082, 'Non alcoholic beer ( 166 bottles )  crated', 1, 0.0, 98.0, 0.567731, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Oil aeroshel w100 + oil filter + spayon matt black ( crated  WB: 4580 Supplier: Absolute Air Craft Parts Date: 2025/09/12 )', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 7, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=29 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Central Aviation' AND surname='Services' ORDER BY id ASC LIMIT 1), 'pending', 4083, 'INV-20251031-00083', 117085.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-P2DUOM6Z', 0, 0, 0, 440.0, 0.96, 17600.0, 6720.0, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4083, 'Oil aeroshel w100', 1, 0.0, 146.67, 0.32, 0.0, '1127132');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4083, 'oil filter', 1, 0.0, 146.67, 0.32, 0.0, '1127132');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4083, 'spayon matt black ( crated', 1, 0.0, 146.67, 0.32, 0.0, '1127132');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Antonio lighting silver WB: 4581 Supplier: The Lighting Warehouse Date: 2025/09/15', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Gabriella' AND surname='Kortland' ORDER BY id ASC LIMIT 1), 'pending', 4084, 'INV-20251031-00084', 2043.39, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-1NBRBKX4', 0, 0, 0, 6.0, 0.04, 240.0, 303.8, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4084, 'Antonio lighting silver', 1, 0.0, 6.0, 0.0434, 0.0, '31561');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Mirrors WB: 4582 Supplier: Kelsey Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=32 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Linet' AND surname='Ongoro' ORDER BY id ASC LIMIT 1), 'pending', 4085, 'INV-20251031-00085', 3000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-97AGIYAQ', 0, 0, 0, 197.0, 1.03, 7880.0, 7220.84, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4085, 'Mirrors', 1, 0.0, 197.0, 1.031549, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Tripod fans  WB: 4583 Supplier: Live Stainable Date: 2025/09/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=31 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4086, 'INV-20251031-00086', 43139.88, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-DT67UJSY', 0, 0, 0, 177.0, 1.43, 7080.0, 10043.04, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4086, 'Tripod fans', 1, 0.0, 177.0, 1.43472, 0.0, '55467');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Sparlux dryer black + sparlux  dryer silver WB: 4584 Supplier: Hair Health & Beauty Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=32 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ngoro Ngoro Safari' AND surname='Lodge' ORDER BY id ASC LIMIT 1), 'pending', 4087, 'INV-20251031-00087', 47111.94, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-HELNO7S9', 0, 0, 0, 21.0, 0.15, 840.0, 1069.04, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4087, 'Sparlux dryer black', 1, 0.0, 10.5, 0.07636, 0.0, 'HHBT1031764');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4087, 'sparlux  dryer silver', 1, 0.0, 10.5, 0.07636, 0.0, 'HHBT1031764');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Brass modern floor tap + standing bath mixer + wall mounted basin WB: 4585 Supplier: Tic Home & Bathrooms Date: 2025/09/15', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=30 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Annette' AND surname='Simonson' ORDER BY id ASC LIMIT 1), 'pending', 4088, 'INV-20251031-00088', 26170.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-XC5YSPTP', 0, 0, 0, 16.0, 0.05, 640.0, 347.2, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4088, 'Brass modern floor tap', 1, 0.0, 5.33, 0.016533, 0.0, '1678');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4088, 'standing bath mixer', 1, 0.0, 5.33, 0.016533, 0.0, '1678');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4088, 'wall mounted basin', 1, 0.0, 5.33, 0.016533, 0.0, '1678');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X2 pair of shoes + clothes WB: 4587 Supplier: Shein Date: 2025/09/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=31 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Rita' AND surname='Mcluckie' ORDER BY id ASC LIMIT 1), 'pending', 4090, 'INV-20251031-00090', 1000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-6V3RZ4FU', 0, 0, 0, 3.0, 0.04, 120.0, 309.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4090, 'X2 pair of shoes', 1, 0.0, 1.5, 0.022103, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4090, 'clothes', 1, 0.0, 1.5, 0.022103, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Lampshhades + basket + spoon holder  WB: 4588 Supplier: Fast Way  Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=32 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nina' AND surname='Gray' ORDER BY id ASC LIMIT 1), 'pending', 4091, 'INV-20251031-00091', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-HNUX4J2X', 0, 0, 0, 2.0, 0.04, 80.0, 309.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4091, 'Lampshhades', 1, 0.0, 0.67, 0.014735, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4091, 'basket', 1, 0.0, 0.67, 0.014735, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4091, 'spoon holder', 1, 0.0, 0.67, 0.014735, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Zoe 2 group competition + water filtration + fibre cloths + angel brush + milk jug WB: 4589 Supplier: Global Coffee   Date: 2025/09/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=31 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ngoro Ngoro Safari' AND surname='Lodge' ORDER BY id ASC LIMIT 1), 'pending', 4092, 'INV-20251031-00092', 221191.01, 0, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-3MCMNADW', 0, 0, 0, 166.0, 1.04, 6640.0, 7289.86, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4092, 'Zoe 2 group competition', 1, 0.0, 33.2, 0.208282, 0.0, 'INV015412');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4092, 'water filtration', 1, 0.0, 33.2, 0.208282, 0.0, 'INV015412');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4092, 'fibre cloths', 1, 0.0, 33.2, 0.208282, 0.0, 'INV015412');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4092, 'angel brush', 1, 0.0, 33.2, 0.208282, 0.0, 'INV015412');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4092, 'milk jug', 1, 0.0, 33.2, 0.208282, 0.0, 'INV015412');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Cash trays  WB: 4591 Supplier: T.A.C Products Date: 2025/09/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=31 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Andrew' AND surname='Kulola' ORDER BY id ASC LIMIT 1), 'pending', 4094, 'INV-20251031-00094', 2000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-JVQ2I07V', 0, 0, 0, 80.0, 0.34, 3200.0, 2387.0, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4094, 'Cash trays', 1, 0.0, 80.0, 0.341, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Chairs WB: 4592 Supplier: Weylandts Remaining Order Date: 2025/09/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Robanda' AND surname='Serengeti' ORDER BY id ASC LIMIT 1), 'pending', 4095, 'INV-20251031-00095', 10000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-BBPAN83D', 0, 0, 0, 348.0, 4.83, 13920.0, 33780.7, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4095, 'Chairs', 1, 0.0, 348.0, 4.825814, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Rain poncho with fleece + knitted beanie  WB: 4594 Supplier: Marion Reed Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=32 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ethan' AND surname='Awawa' ORDER BY id ASC LIMIT 1), 'pending', 4097, 'INV-20251031-00097', 112990.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-CP6PI111', 0, 0, 0, 178.0, 1.74, 7120.0, 12178.04, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4097, 'Rain poncho with fleece', 1, 0.0, 89.0, 0.86986, 0.0, 'INV0009620');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4097, 'knitted beanie', 1, 0.0, 89.0, 0.86986, 0.0, 'INV0009620');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Coc chair with sleigh base + flip top table + mobile storage unit + boardroom table WB: 4595 Supplier: Angel Shack  Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ashley' AND surname='Calavarius' ORDER BY id ASC LIMIT 1), 'pending', 4098, 'INV-20251031-00098', 211037.50, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-YI90XHHM', 0, 0, 0, 1149.0, 7.31, 45960.0, 51136.68, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4098, 'Coc chair with sleigh base', 1, 0.0, 287.25, 1.82631, 0.0, 'INV00101');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4098, 'flip top table', 1, 0.0, 287.25, 1.82631, 0.0, 'INV00101');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4098, 'mobile storage unit', 1, 0.0, 287.25, 1.82631, 0.0, 'INV00101');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4098, 'boardroom table', 1, 0.0, 287.25, 1.82631, 0.0, 'INV00101');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Protein milkshake + protein pudding + protein coffe mixed tray  WB: 4596 Supplier: The Protein Factory Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=32 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4099, 'INV-20251031-00099', 1160.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-P8WB3TX3', 0, 0, 0, 8.0, 0.03, 320.0, 203.84, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4099, 'Protein milkshake', 1, 0.0, 2.67, 0.009707, 0.0, '1624');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4099, 'protein pudding', 1, 0.0, 2.67, 0.009707, 0.0, '1624');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4099, 'protein coffe mixed tray', 1, 0.0, 2.67, 0.009707, 0.0, '1624');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Service parts WB: 4597 Supplier: Goscor Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=32 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ben' AND surname='Pelser' ORDER BY id ASC LIMIT 1), 'pending', 4100, 'INV-20251031-00100', 52452.63, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-8TMXYW8N', 0, 0, 0, 48.0, 0.15, 1920.0, 1077.3, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4100, 'Service parts', 1, 0.0, 48.0, 0.1539, 0.0, '15435194');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Anti pilling fleece + anti pilling fleece 150cm WB: 4598 Supplier: Mhc Date: 2025/09/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=32 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ethan' AND surname='Awawa' ORDER BY id ASC LIMIT 1), 'pending', 4101, 'INV-20251031-00101', 4193.00, 0, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-PZG042K7', 0, 0, 0, 42.0, 0.54, 1680.0, 3765.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4101, 'Anti pilling fleece', 1, 0.0, 21.0, 0.26896, 0.0, '13125739');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4101, 'anti pilling fleece 150cm', 1, 0.0, 21.0, 0.26896, 0.0, '13125739');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Cosmetics WB: 4599 Supplier: Dischem Date: 2025/09/18', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=33 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Fredrick' AND surname='Lymo' ORDER BY id ASC LIMIT 1), 'pending', 4102, 'INV-20251031-00102', 3000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-W8D83ULT', 0, 0, 0, 111.0, 0.18, 4440.0, 1288.83, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4102, 'Cosmetics', 1, 0.0, 111.0, 0.184118, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Clothes WB: 4600 Supplier: Wendy Calitz Date: 2025/09/18', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=33 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Alex' AND surname='Olifiser' ORDER BY id ASC LIMIT 1), 'pending', 4103, 'INV-20251031-00103', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-WMB08LEA', 0, 0, 0, 24.0, 0.1, 960.0, 719.71, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4103, 'Clothes', 1, 0.0, 24.0, 0.102816, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Net tari blue lagoon flavoured syrup WB: 4601 Supplier: Take Alot Date: 2025/09/18', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=34 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Alexandra' AND surname='Soine' ORDER BY id ASC LIMIT 1), 'pending', 4104, 'INV-20251031-00104', 2000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-TM5ZBWA2', 0, 0, 0, 1.0, 0.01, 40.0, 55.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4104, 'Net tari blue lagoon flavoured syrup', 1, 0.0, 1.0, 0.00792, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Rolls of liner material WB: 4602 Supplier: Canvas & Tents Date: 2025/09/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=31 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Lodge' AND surname='Creations' ORDER BY id ASC LIMIT 1), 'pending', 4105, 'INV-20251031-00105', 6550.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-STGS1AN2', 0, 0, 0, 970.0, 2.41, 38800.0, 16884.69, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4105, 'Rolls of liner material', 1, 0.0, 970.0, 2.412099, 0.0, '160925');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Liquid brun 100ml edp by french avenue WB: 4603 Supplier: Take Alot Date: 2025/09/19', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=34 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4106, 'INV-20251031-00106', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-W7ZH9RN3', 0, 0, 0, 1.0, 0.0, 40.0, 24.15, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4106, 'Liquid brun 100ml edp by french avenue', 1, 0.0, 1.0, 0.00345, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('6 person crockery box WB: 4604 Supplier: Outdoor Warehouse Date: 2025/09/19', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=34 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4107, 'INV-20251031-00107', 11994.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-VRFMYOIF', 0, 0, 0, 44.0, 0.66, 1760.0, 4596.67, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4107, '6 person crockery box', 1, 0.0, 44.0, 0.656667, 0.0, '1000841');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X1 chair WB: 4605 Supplier: Shein Date: 2025/09/19', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=34 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nina' AND surname='Gray' ORDER BY id ASC LIMIT 1), 'pending', 4108, 'INV-20251031-00108', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-U1TOB0P5', 0, 0, 0, 12.0, 0.18, 480.0, 1256.85, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4108, 'X1 chair', 1, 0.0, 12.0, 0.17955, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Water filters WB: 4607 Supplier: Ram Couriers  Date: 2025/09/22', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Gabriella' AND surname='Kortland' ORDER BY id ASC LIMIT 1), 'pending', 4110, 'INV-20251031-00110', 1000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-DUTYSVUP', 0, 0, 0, 1.0, 0.0, 40.0, 25.2, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4110, 'Water filters', 1, 0.0, 1.0, 0.0036, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Glassware + towels + plates WB: 4609 Supplier: Woolies Date: 2025/09/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=36 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dipen' AND surname='Shah' ORDER BY id ASC LIMIT 1), 'pending', 4112, 'INV-20251031-00112', 4000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-6UV0FPVP', 0, 0, 0, 50.0, 0.38, 2000.0, 2647.55, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4112, 'Glassware', 1, 0.0, 16.67, 0.126074, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4112, 'towels', 1, 0.0, 16.67, 0.126074, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4112, 'plates', 1, 0.0, 16.67, 0.126074, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Boots + coffee + spices WB: 4610 Supplier: Tkae Alot Date: 2025/09/25', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=37 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jackie' AND surname='Bosselaar' ORDER BY id ASC LIMIT 1), 'pending', 4113, 'INV-20251031-00113', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-CHQSGJWE', 0, 0, 0, 10.0, 0.04, 400.0, 301.35, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4113, 'Boots', 1, 0.0, 3.33, 0.01435, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4113, 'coffee', 1, 0.0, 3.33, 0.01435, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4113, 'spices', 1, 0.0, 3.33, 0.01435, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Wetsuits + pineapple mat + dog food  WB: 4611 Supplier: Takealot Date: 2025/09/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=36 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ina' AND surname='Walter' ORDER BY id ASC LIMIT 1), 'pending', 4114, 'INV-20251031-00114', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-850ZZT6G', 0, 0, 0, 6.0, 0.04, 240.0, 258.72, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4114, 'Wetsuits', 1, 0.0, 2.0, 0.01232, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4114, 'pineapple mat', 1, 0.0, 2.0, 0.01232, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4114, 'dog food', 1, 0.0, 2.0, 0.01232, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Bangles WB: 4612 Supplier: Takealot Date: 2025/09/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=36 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Danieal' AND surname='Longanetti' ORDER BY id ASC LIMIT 1), 'pending', 4115, 'INV-20251031-00115', 1000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-KXNQ02ZT', 0, 0, 0, 6.0, 0.04, 240.0, 309.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4115, 'Bangles', 1, 0.0, 6.0, 0.044206, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Specktacles + water bottle + clothing  WB: 4613 Supplier: Fastway Couriers Date: 2025/09/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=36 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4116, 'INV-20251031-00116', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-U68HJ2LD', 0, 0, 0, 3.0, 0.03, 120.0, 207.69, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4116, 'Specktacles', 1, 0.0, 1.0, 0.00989, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4116, 'water bottle', 1, 0.0, 1.0, 0.00989, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4116, 'clothing', 1, 0.0, 1.0, 0.00989, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Hats WB: 4614 Supplier: Rogue Sa Date: 2025/09/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 14, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=36 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Micheal' AND surname='Coudrey' ORDER BY id ASC LIMIT 1), 'pending', 4117, 'INV-20251031-00117', 2142.61, 0, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-MYXRE5TV', 0, 0, 0, 1.0, 0.01, 40.0, 65.52, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4117, 'Hats', 1, 0.0, 1.0, 0.00936, 0.0, 'IN012373');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Dead Shaver + Plastic Holders + Key Holders + Socks + Braai Thongs Wb- 4615 Supplier: Date: Dpd Laser 2025/09/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=36 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Lauren' AND surname='Mcfarlane' ORDER BY id ASC LIMIT 1), 'pending', 4118, 'INV-20251031-00118', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-I235LST7', 0, 0, 0, 4.0, 0.03, 160.0, 199.92, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4118, 'DEAD SHAVER', 1, 0.0, 0.8, 0.005712, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4118, 'PLASTIC HOLDERS', 1, 0.0, 0.8, 0.005712, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4118, 'KEY HOLDERS', 1, 0.0, 0.8, 0.005712, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4118, 'SOCKS', 1, 0.0, 0.8, 0.005712, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4118, 'BRAAI THONGS', 1, 0.0, 0.8, 0.005712, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Toiletries + Pillows + Personal Goods Wb- 4616 Supplier: Date: Windra 2025/09/25', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=37 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Wiandra' AND surname='Wolmarans' ORDER BY id ASC LIMIT 1), 'pending', 4119, 'INV-20251031-00119', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-42KMVF8O', 0, 0, 0, 13.0, 0.14, 520.0, 990.99, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4119, 'TOILETRIES', 1, 0.0, 4.33, 0.04719, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4119, 'PILLOWS', 1, 0.0, 4.33, 0.04719, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4119, 'PERSONAL GOODS', 1, 0.0, 4.33, 0.04719, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('5 Pack Of Papers  Wb- 4617 Supplier: Date: Paper Smith & Son  2025/09/19', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=34 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Yusuff' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4120, 'INV-20251031-00120', 8530.50, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-PB6KDR0K', 0, 0, 0, 90.0, 0.1, 3600.0, 742.35, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4120, '5 PACK OF PAPERS', 5, 0.0, 18.0, 0.02121, 0.0, '1319902');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Flavoured syrup + lemonade + slush syrup WB: 4618 Supplier: Take Alot Date: 2025/09/25', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=36 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Alexandra' AND surname='Soine' ORDER BY id ASC LIMIT 1), 'pending', 4121, 'INV-20251031-00121', 1000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-0QA17WD1', 0, 0, 0, 45.0, 0.09, 1800.0, 605.43, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4121, 'Flavoured syrup', 1, 0.0, 15.0, 0.02883, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4121, 'lemonade', 1, 0.0, 15.0, 0.02883, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4121, 'slush syrup', 1, 0.0, 15.0, 0.02883, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Legos + snickers + coffees + insect repelent  WB: 4619 Supplier: Takealot Date: 2025/09/25', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=37 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Danieal' AND surname='Longanetti' ORDER BY id ASC LIMIT 1), 'pending', 4122, 'INV-20251031-00122', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-IURZQ26J', 0, 0, 0, 9.0, 0.1, 360.0, 690.46, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4122, 'Legos', 1, 0.0, 2.25, 0.024659, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4122, 'snickers', 1, 0.0, 2.25, 0.024659, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4122, 'coffees', 1, 0.0, 2.25, 0.024659, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4122, 'insect repelent', 1, 0.0, 2.25, 0.024659, 0.0, 'P');




















INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('2x motor poer WB: 4622 Supplier: Water Sports Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Martie' AND surname='Botha' ORDER BY id ASC LIMIT 1), 'pending', 4125, 'INV-20251031-00125', 3000.00, 0, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-TZARN1T1', 0, 0, 0, 19.0, 0.18, 760.0, 1273.27, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4125, '2x motor poer', 1, 0.0, 19.0, 0.181896, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Head sail WB: 4624 Supplier: Ullman Sails Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Paresh' AND surname='Patel' ORDER BY id ASC LIMIT 1), 'pending', 4127, 'INV-20251031-00127', 40249.76, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-TH5B7HCY', 0, 0, 0, 25.0, 0.14, 1000.0, 1006.74, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4127, 'Head sail', 1, 0.0, 25.0, 0.14382, 0.0, 'SOO4827');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Crisps flings WB: 4625 Supplier: Take Alot Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Danieal' AND surname='Longanetti' ORDER BY id ASC LIMIT 1), 'pending', 4128, 'INV-20251031-00128', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-RLJ7YFZH', 0, 0, 0, 2.0, 0.06, 80.0, 420.0, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4128, 'Crisps flings', 1, 0.0, 2.0, 0.06, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Skin tint + room spray + shampoo + conditioner + hair accessories WB: 4626 Supplier: Aramex Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4129, 'INV-20251031-00129', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-OEP9PH11', 0, 0, 0, 5.0, 0.02, 200.0, 171.36, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4129, 'Skin tint', 1, 0.0, 1.0, 0.004896, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4129, 'room spray', 1, 0.0, 1.0, 0.004896, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4129, 'shampoo', 1, 0.0, 1.0, 0.004896, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4129, 'conditioner', 1, 0.0, 1.0, 0.004896, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4129, 'hair accessories', 1, 0.0, 1.0, 0.004896, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Harnes + cables WB: 4627 Supplier: Hino Genuine Parts Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Fransie' AND surname='Calitz' ORDER BY id ASC LIMIT 1), 'pending', 4130, 'INV-20251031-00130', 1000.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-WRNR5C99', 0, 0, 0, 5.0, 0.04, 200.0, 283.02, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4130, 'Harnes', 1, 0.0, 2.5, 0.020216, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4130, 'cables', 1, 0.0, 2.5, 0.020216, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Twist tie + kitchen knife + bilton cutter WB: 4628 Supplier: Takealot Date: 2025/10/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 14, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Micheal' AND surname='Coudrey' ORDER BY id ASC LIMIT 1), 'pending', 4131, 'INV-20251031-00131', 1000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-7REWM1T1', 0, 0, 0, 2.0, 0.02, 80.0, 161.45, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4131, 'Twist tie', 1, 0.0, 0.67, 0.007688, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4131, 'kitchen knife', 1, 0.0, 0.67, 0.007688, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4131, 'bilton cutter', 1, 0.0, 0.67, 0.007688, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Shower set + brass finish double bowl + blacl extandable mirror + tower rail shelf WB: 4629 Supplier: Trendy Taps Date: 2025/10/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dr Sabine' AND surname='Marten' ORDER BY id ASC LIMIT 1), 'pending', 4132, 'INV-20251031-00132', 43808.68, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-VLUD6FQA', 0, 0, 0, 44.0, 0.39, 1760.0, 2716.61, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4132, 'Shower set', 1, 0.0, 11.0, 0.097022, 0.0, 'INV-00008521');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4132, 'brass finish double bowl', 1, 0.0, 11.0, 0.097022, 0.0, 'INV-00008521');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4132, 'blacl extandable mirror', 1, 0.0, 11.0, 0.097022, 0.0, 'INV-00008521');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4132, 'tower rail shelf', 1, 0.0, 11.0, 0.097022, 0.0, 'INV-00008521');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Roll cable + electronic equipment WB: 4630 Supplier: The Exporter Cc Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Six Rivers' AND surname='Africa' ORDER BY id ASC LIMIT 1), 'pending', 4133, 'INV-20251031-00133', 198788.70, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-9FDNEY6B', 0, 0, 0, 56.0, 0.21, 2240.0, 1446.06, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4133, 'Roll cable', 1, 0.0, 28.0, 0.10329, 0.0, 'INV18065');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4133, 'electronic equipment', 1, 0.0, 28.0, 0.10329, 0.0, 'INV18065');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X4 camera mount WB: 4631 Supplier:  Date: 2025/10/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=40 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Hendrick' AND surname='Lombard' ORDER BY id ASC LIMIT 1), 'pending', 4134, 'INV-20251031-00134', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-YVBUZ4UB', 0, 0, 0, 9.0, 0.11, 360.0, 780.19, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4134, 'X4 camera mount', 1, 0.0, 9.0, 0.111456, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Garmin WB: 4632 Supplier: Takealot  Date: 2025/10/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Solomon Jeremiah' AND surname='Sembosi' ORDER BY id ASC LIMIT 1), 'pending', 4135, 'INV-20251031-00135', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-P5CJG714', 0, 0, 0, 1.0, 0.0, 40.0, 25.76, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4135, 'Garmin', 1, 0.0, 1.0, 0.00368, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Picture frames WB: 4633 Supplier: Blue Water Components Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='David' AND surname='Legeant' ORDER BY id ASC LIMIT 1), 'pending', 4136, 'INV-20251031-00136', 4000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-EHR68JUM', 0, 0, 0, 9.0, 0.08, 360.0, 549.78, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4136, 'Picture frames', 1, 0.0, 9.0, 0.07854, 0.0, '327');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Trendy taps brass basin mixer WB: 4634 Supplier: Holler Trade Date: 2025/10/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=40 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Wildernes' AND surname='Destinations' ORDER BY id ASC LIMIT 1), 'pending', 4137, 'INV-20251031-00137', 20476.75, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-H3MV48BW', 0, 0, 0, 14.0, 0.06, 560.0, 421.34, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4137, 'Trendy taps brass basin mixer', 1, 0.0, 14.0, 0.060192, 0.0, '14595');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Safety 4 horses fence bracket + horses fence rail + coach screws + tek screws  WB: 4635 Supplier: Klips N Things Cc Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=4 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dr Sabine' AND surname='Marten' ORDER BY id ASC LIMIT 1), 'pending', 4138, 'INV-20251031-00138', 271646.33, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-VYQYUDK0', 0, 0, 0, 1254.0, 2.9, 50160.0, 20287.74, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4138, 'Safety 4 horses fence bracket', 1, 0.0, 313.5, 0.724562, 0.0, 'IN022137');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4138, 'horses fence rail', 1, 0.0, 313.5, 0.724562, 0.0, 'IN022137');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4138, 'coach screws', 1, 0.0, 313.5, 0.724562, 0.0, 'IN022137');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4138, 'tek screws', 1, 0.0, 313.5, 0.724562, 0.0, 'IN022137');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X8 dado baths gloss finish verde WB: 4636 Supplier: Gaia Ltd Laba Laba ( Rhino Lodge ) Date: 2025/09/29', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4139, 'INV-20251031-00139', 98000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-P3RM0KIV', 0, 0, 0, 586.0, 5.83, 23440.0, 40776.79, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4139, 'X8 dado baths gloss finish verde', 1, 0.0, 586.0, 5.825256, 0.0, '6302061');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Rectagrid saddle clamp + bolts ,nuts WB: 4638 Supplier: Mentis Afric Date: 2025/10/30', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jan' AND surname='Griesl' ORDER BY id ASC LIMIT 1), 'pending', 4141, 'INV-20251031-00141', 12316.40, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-BOSG07YR', 0, 0, 0, 3186.0, 0.03, 127440.0, 178.99, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4141, 'Rectagrid saddle clamp', 1, 0.0, 1593.0, 0.012785, 0.0, 'IEA2600231');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4141, 'bolts ,nuts', 1, 0.0, 1593.0, 0.012785, 0.0, 'IEA2600231');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X1 black bag WB: 4639 Supplier: Stephen  Date: 2025/09/30', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Stephen' AND surname='Berson' ORDER BY id ASC LIMIT 1), 'pending', 4142, 'INV-20251031-00142', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-ON9TJF1D', 0, 0, 0, 28.0, 0.16, 1120.0, 1120.56, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4142, 'X1 black bag', 1, 0.0, 28.0, 0.16008, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Truck spares WB: 4641 Supplier: Goscor Lift Trucks Date: 2025/09/30', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=46 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ben' AND surname='Pelser' ORDER BY id ASC LIMIT 1), 'pending', 4144, 'INV-20251031-00144', 38333.62, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-6H5CE7TH', 0, 0, 0, 38.0, 0.09, 1520.0, 608.9, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4144, 'Truck spares', 1, 0.0, 38.0, 0.086986, 0.0, '15437372');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Mkiii amf controller WB: 4642 Supplier: Wagar Distribution Date: 2025/09/30', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=39 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Branden' AND surname='Simonson' ORDER BY id ASC LIMIT 1), 'pending', 4145, 'INV-20251031-00145', 7111.40, 0, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-O4OH7FV4', 0, 0, 0, 1.0, 0.01, 40.0, 51.41, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4145, 'Mkiii amf controller', 1, 0.0, 1.0, 0.007344, 0.0, 'INA70372');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X2 wonder bags WB: 4643 Supplier: Wonder Bag Date: 2025/10/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=41 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ngoro Ngoro Safari' AND surname='Lodge' ORDER BY id ASC LIMIT 1), 'pending', 4146, 'INV-20251031-00146', 1404.49, 0, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-KFZKV09C', 0, 0, 0, 2.0, 0.03, 80.0, 182.5, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4146, 'X2 wonder bags', 1, 0.0, 2.0, 0.026071, 0.0, 'INA27570');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Biscuit crunch WB: 4644 Supplier: Dpd Laser  Date: 2025/09/30', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=39 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4147, 'INV-20251031-00147', 1000.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-OM9TZLBV', 0, 0, 0, 1.0, 0.01, 40.0, 43.01, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4147, 'Biscuit crunch', 1, 0.0, 1.0, 0.006144, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X2 kids bikes WB: 4645 Supplier: Ashley Calavarius Date: 2025/10/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=39 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ashley' AND surname='Calavarius' ORDER BY id ASC LIMIT 1), 'pending', 4148, 'INV-20251031-00148', 10000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-CUBD6TSW', 0, 0, 0, 12.0, 0.16, 480.0, 1140.52, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4148, 'X2 kids bikes', 1, 0.0, 12.0, 0.162932, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Ht8 belts WB: 4646 Supplier: Turner Morris Date: 2025/10/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=41 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ron' AND surname='Barnes-Webb' ORDER BY id ASC LIMIT 1), 'pending', 4149, 'INV-20251031-00149', 3252.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-N8LQ9BBD', 0, 0, 0, 1.0, 0.01, 40.0, 75.6, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4149, 'Ht8 belts', 1, 0.0, 1.0, 0.0108, 0.0, '32003172');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Lights WB: 4647 Supplier: Cobin Light Date: 2025/10/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=39 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4150, 'INV-20251031-00150', 247424.96, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-AVBKVGFN', 0, 0, 0, 86.0, 0.47, 3440.0, 3271.76, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4150, 'Lights', 1, 0.0, 86.0, 0.467394, 0.0, 'IN103829');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Copper coat base + copper coat + filter oil + fuel filter + zinc anode + washer copper  WB: 4649 Supplier: Signature Power Systems Date: 2025/10/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=41 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Debra' AND surname='Woolley' ORDER BY id ASC LIMIT 1), 'pending', 4152, 'INV-20251031-00152', 61891.26, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-HPNQXNWQ', 0, 0, 0, 51.0, 0.08, 2040.0, 542.5, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4152, 'Copper coat base', 1, 0.0, 8.5, 0.012917, 0.0, 'QU103286');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4152, 'copper coat', 1, 0.0, 8.5, 0.012917, 0.0, 'QU103286');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4152, 'filter oil', 1, 0.0, 8.5, 0.012917, 0.0, 'QU103286');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4152, 'fuel filter', 1, 0.0, 8.5, 0.012917, 0.0, 'QU103286');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4152, 'zinc anode', 1, 0.0, 8.5, 0.012917, 0.0, 'QU103286');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4152, 'washer copper', 1, 0.0, 8.5, 0.012917, 0.0, 'QU103286');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Automatic pool cleaner WB: 4650 Supplier: Verimak Date: 2025/10/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=41 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Martie' AND surname='Botha' ORDER BY id ASC LIMIT 1), 'pending', 4153, 'INV-20251031-00153', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-6BL7XZG3', 0, 0, 0, 8.0, 0.11, 320.0, 774.05, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4153, 'Automatic pool cleaner', 1, 0.0, 8.0, 0.110578, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X4 protein pudding WB: 4651 Supplier: Lean Living Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=38 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4154, 'INV-20251031-00154', 1000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-2E0ZBPO5', 0, 0, 0, 2.0, 0.03, 80.0, 236.6, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4154, 'X4 protein pudding', 1, 0.0, 2.0, 0.0338, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Authentis decanter 1.5l + authentis casual red wine + style champagne 240 WB: 4652 Supplier: Greystone Trading 603cc Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=41 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ngoro Ngoro Safari' AND surname='Lodge' ORDER BY id ASC LIMIT 1), 'pending', 4155, 'INV-20251031-00155', 33728.92, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-XI1EJFFA', 0, 0, 0, 70.0, 0.72, 2800.0, 5040.0, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4155, 'Authentis decanter 1.5l', 1, 0.0, 23.33, 0.24, 0.0, 'NSL00149');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4155, 'authentis casual red wine', 1, 0.0, 23.33, 0.24, 0.0, 'NSL00149');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4155, 'style champagne 240', 1, 0.0, 23.33, 0.24, 0.0, 'NSL00149');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)   VALUES ('X6 bath tubs ( crated )  WB: 4653 Supplier: Gaia Ltd Laba Laba ( Rhino Lodge ) Date: 2025/10/02', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=41 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4156, 'INV-20251031-00156', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:0:{}}', 'TRK-W1TQAPRW', 0, 0, 0, 452.0, 4.64, 18080.0, 32464.07, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price) VALUES (4156, 'X6 bath tubs ( crated )', 1, 0.0, 452.0, 4.637724, 0.0);










INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Memo board + make up mirror + brush cover WB: 4654 Supplier: Mr Price Date: 2025/10/03', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=43 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Goretty Dos' AND surname='Ramos' ORDER BY id ASC LIMIT 1), 'pending', 4157, 'INV-20251031-00157', 1000.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-Q6L94ZEP', 0, 0, 0, 1.0, 0.04, 40.0, 295.99, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4157, 'Memo board', 1, 0.0, 0.33, 0.014095, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4157, 'make up mirror', 1, 0.0, 0.33, 0.014095, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4157, 'brush cover', 1, 0.0, 0.33, 0.014095, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Kids quad  biike +  motorcycle WB: 4655 Supplier: Puzey Motor Corporation Pty Ltd  Date: 2025/10/07', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=43 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nabil' AND surname='Haroon' ORDER BY id ASC LIMIT 1), 'pending', 4158, 'INV-20251031-00158', 44679.70, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-FIVJWBVW', 0, 0, 0, 208.0, 2.1, 8320.0, 14677.17, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4158, 'Kids quad  biike', 1, 0.0, 104.0, 1.048369, 0.0, 'IN11068');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4158, 'motorcycle', 1, 0.0, 104.0, 1.048369, 0.0, 'IN11068');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Pro pet shampoo WB: 4656 Supplier: Dpd Laser Date: 2025/10/07', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=41 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Martie' AND surname='Botha' ORDER BY id ASC LIMIT 1), 'pending', 4159, 'INV-20251031-00159', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-TSRKI4HO', 0, 0, 0, 8.0, 0.08, 320.0, 570.75, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4159, 'Pro pet shampoo', 1, 0.0, 8.0, 0.081536, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Pillows WB: 4657 Supplier: Mr Price Home  Date: 2025/10/03', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Leanne' AND surname='Haigh' ORDER BY id ASC LIMIT 1), 'pending', 4160, 'INV-20251031-00160', 2365.17, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-OXLGG4ZK', 0, 0, 0, 8.0, 0.2, 320.0, 1394.58, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4160, 'Pillows', 1, 0.0, 8.0, 0.199226, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Car spares WB: 4658 Supplier: B M W Eastrand Date: 2025/10/07', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=44 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Richard' AND surname='Lauenstein' ORDER BY id ASC LIMIT 1), 'pending', 4161, 'INV-20251031-00161', 25581.27, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-Y3P2OPOZ', 0, 0, 0, 5.0, 0.02, 200.0, 168.18, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4161, 'Car spares', 1, 0.0, 5.0, 0.024025, 0.0, '24085107');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('4x lithium batteries WB: 4659 Supplier: Take A Lot Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=6 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Mandy' AND surname='Stein' ORDER BY id ASC LIMIT 1), 'pending', 4162, 'INV-20251031-00162', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-MA5VKBHR', 0, 0, 0, 84.0, 0.24, 3360.0, 1696.46, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4162, '4x lithium batteries', 1, 0.0, 84.0, 0.242352, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x butler sink ribbed bowl + 1 space saving sink trap WB: 4660 Supplier: Ctm Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=42 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Skyler' AND surname='Russell' ORDER BY id ASC LIMIT 1), 'pending', 4163, 'INV-20251031-00163', 8698.80, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-AR2ESPYL', 0, 0, 0, 43.0, 0.24, 1720.0, 1656.2, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4163, '1x butler sink ribbed bowl', 1, 0.0, 21.5, 0.1183, 0.0, '2134361170');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4163, '1 space saving sink trap', 1, 0.0, 21.5, 0.1183, 0.0, '2134361170');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('2x dryness ion batteries + 1x hybrid inverter WB: 4661 Supplier: Solarway Suppliers Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Praveena' AND surname='Selvarajah' ORDER BY id ASC LIMIT 1), 'pending', 4164, 'INV-20251031-00164', 72519.14, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-Q2ATPZKS', 0, 0, 0, 182.0, 0.39, 7280.0, 2751.4, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4164, '2x dryness ion batteries', 1, 0.0, 91.0, 0.196528, 0.0, '0005906');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4164, '1x hybrid inverter', 1, 0.0, 91.0, 0.196528, 0.0, '0005906');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Kids ride on car WB: 4662 Supplier: Kids A Lot Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Julia' AND surname='Altezza' ORDER BY id ASC LIMIT 1), 'pending', 4165, 'INV-20251031-00165', 3000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-3LLZU3FX', 0, 0, 0, 16.0, 0.23, 640.0, 1586.75, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4165, 'Kids ride on car', 1, 0.0, 16.0, 0.226678, 0.0, '119181');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Jeeves sienna matt black heated towel rail  WB: 4663 Supplier: Italtile Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=43 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dr Sabine' AND surname='Marten' ORDER BY id ASC LIMIT 1), 'pending', 4166, 'INV-20251031-00166', 12260.87, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-DSRPIF4N', 0, 0, 0, 18.0, 0.22, 720.0, 1538.21, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4166, 'Jeeves sienna matt black heated towel rail', 1, 0.0, 18.0, 0.219744, 0.0, '2134345421');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Denim shirt WB: 4664 Supplier: Bash Couriers Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=43 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Goretty Dos' AND surname='Ramos' ORDER BY id ASC LIMIT 1), 'pending', 4167, 'INV-20251031-00167', 600.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-Y8P2522U', 0, 0, 0, 1.0, 0.0, 40.0, 25.2, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4167, 'Denim shirt', 1, 0.0, 1.0, 0.0036, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3x aerosol wound spray for animals + 1x kikuyu lawn grass seed + 2x car jump starter WB: 4665 Supplier: Take A Lot Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=43 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chris' AND surname='Joubert' ORDER BY id ASC LIMIT 1), 'pending', 4168, 'INV-20251031-00168', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-NNIVVC90', 0, 0, 0, 2.0, 0.02, 80.0, 166.66, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4168, '3x aerosol wound spray for animals', 1, 0.0, 0.67, 0.007936, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4168, '1x kikuyu lawn grass seed', 1, 0.0, 0.67, 0.007936, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4168, '2x car jump starter', 1, 0.0, 0.67, 0.007936, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Fence adjustments and railing + socket wrench and ruler + milling spindles + clamping pin  WB: 4666 Supplier: Nukor Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=5 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Laba' AND surname='Laba' ORDER BY id ASC LIMIT 1), 'pending', 4169, 'INV-20251031-00169', 113996.26, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-6TUC646Y', 0, 0, 0, 53.0, 0.06, 2120.0, 430.92, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4169, 'Fence adjustments and railing', 1, 0.0, 13.25, 0.01539, 0.0, '421170/421377/209763');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4169, 'socket wrench and ruler', 1, 0.0, 13.25, 0.01539, 0.0, '421170/421377/209763');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4169, 'milling spindles', 1, 0.0, 13.25, 0.01539, 0.0, '421170/421377/209763');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4169, 'clamping pin', 1, 0.0, 13.25, 0.01539, 0.0, '421170/421377/209763');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Assorted artificial plants WB: 4667 Supplier: Leaf And Living Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dominique And' AND surname='Clint' ORDER BY id ASC LIMIT 1), 'pending', 4170, 'INV-20251031-00170', 248892.96, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-P5Z38QVY', 0, 0, 0, 374.0, 3.21, 14960.0, 22495.2, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4170, 'Assorted artificial plants', 1, 0.0, 374.0, 3.2136, 0.0, '3478/3479');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Blachoe cat + tlb uni tip + plow belt + nuts + washers + cutting edge and bucket teeth + h WB: 4669 Supplier: Allied Wear Parts Date: 2025/10/06', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=43 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ashley' AND surname='Calavarius' ORDER BY id ASC LIMIT 1), 'pending', 4172, 'INV-20251031-00172', 85703.08, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-F73QCDCU', 0, 0, 0, 933.0, 0.64, 37320.0, 4468.41, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4172, 'Blachoe cat', 1, 0.0, 133.29, 0.091192, 0.0, 'A137650');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4172, 'tlb uni tip', 1, 0.0, 133.29, 0.091192, 0.0, 'A137650');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4172, 'plow belt', 1, 0.0, 133.29, 0.091192, 0.0, 'A137650');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4172, 'nuts', 1, 0.0, 133.29, 0.091192, 0.0, 'A137650');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4172, 'washers', 1, 0.0, 133.29, 0.091192, 0.0, 'A137650');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4172, 'cutting edge and bucket teeth', 1, 0.0, 133.29, 0.091192, 0.0, 'A137650');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4172, 'h', 1, 0.0, 133.29, 0.091192, 0.0, 'A137650');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('2x trays cool drinks + niknaks WB: 4670 Supplier: Mel Date: 2025/10/07', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=43 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nabil' AND surname='Haroon' ORDER BY id ASC LIMIT 1), 'pending', 4173, 'INV-20251031-00173', 900.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-DU7B7OHQ', 0, 0, 0, 19.0, 0.09, 760.0, 608.9, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4173, '2x trays cool drinks', 1, 0.0, 9.5, 0.043493, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4173, 'niknaks', 1, 0.0, 9.5, 0.043493, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1 pair skates WB: 4671 Supplier: Game Date: 2025/10/07', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=5 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chloe Sheidan' AND surname='Johnson' ORDER BY id ASC LIMIT 1), 'pending', 4174, 'INV-20251031-00174', 2000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-9NMOPA2V', 0, 0, 0, 3.0, 0.04, 120.0, 310.91, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4174, '1 pair skates', 1, 0.0, 3.0, 0.044415, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3x sugar-free electrolytes (assorted) + 4x non sslip rug grips + 1x lan network cable WB: 4672 Supplier: Take A Lot Date: 2025/10/07', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=5 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chris' AND surname='Joubert' ORDER BY id ASC LIMIT 1), 'pending', 4175, 'INV-20251031-00175', 1200.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-FVN5WYAA', 0, 0, 0, 10.0, 0.05, 400.0, 341.45, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4175, '3x sugar-free electrolytes (assorted)', 1, 0.0, 3.33, 0.016259, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4175, '4x non sslip rug grips', 1, 0.0, 3.33, 0.016259, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4175, '1x lan network cable', 1, 0.0, 3.33, 0.016259, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Tents + sleeping bags WB: 4673 Supplier: Campmor Outdoor Date: 2025/10/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Burhanuddin' AND surname='Morbiwalla' ORDER BY id ASC LIMIT 1), 'pending', 4176, 'INV-20251031-00176', 479705.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-RBSLOFPJ', 0, 0, 0, 2113.0, 14.3, 84520.0, 100123.32, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4176, 'Tents', 1, 0.0, 1056.5, 7.151666, 0.0, '89914');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4176, 'sleeping bags', 1, 0.0, 1056.5, 7.151666, 0.0, '89914');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x couch WB: 4674 Supplier: Colleen Boshoff Date: 2025/10/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=6 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Wesley' AND surname='Gold' ORDER BY id ASC LIMIT 1), 'pending', 4177, 'INV-20251031-00177', 1500.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-95EEH63E', 0, 0, 0, 128.0, 1.72, 5120.0, 12012.0, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4177, '1x couch', 1, 0.0, 128.0, 1.716, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('2x oil + 2x filter kit + 1x belt + 1x dayco kit + 1x tensioner + 1x roller WB: 4675 Supplier: Goldwagen Date: 2025/10/01', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=44 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Alex' AND surname='Olifiser' ORDER BY id ASC LIMIT 1), 'pending', 4178, 'INV-20251031-00178', 5078.27, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-U9EFA8DT', 0, 0, 0, 14.0, 0.05, 560.0, 360.64, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4178, '2x oil', 1, 0.0, 2.33, 0.008587, 0.0, '10358933');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4178, '2x filter kit', 1, 0.0, 2.33, 0.008587, 0.0, '10358933');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4178, '1x belt', 1, 0.0, 2.33, 0.008587, 0.0, '10358933');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4178, '1x dayco kit', 1, 0.0, 2.33, 0.008587, 0.0, '10358933');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4178, '1x tensioner', 1, 0.0, 2.33, 0.008587, 0.0, '10358933');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4178, '1x roller', 1, 0.0, 2.33, 0.008587, 0.0, '10358933');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Signo reader WB: 4676 Supplier: Uber Collection Date: 2025/10/08', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=45 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Aliasgher' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4179, 'INV-20251031-00179', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-TASY0W79', 0, 0, 0, 1.0, 0.01, 40.0, 78.12, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4179, 'Signo reader', 1, 0.0, 1.0, 0.01116, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3x cat litter + 2x toilet rolls +nescafe + body wash + pronutro + spices xylitor + jerky WB: 4678 Supplier: Checkers Date: 2025/10/09', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=4 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4181, 'INV-20251031-00181', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-W34PNBSR', 0, 0, 0, 16.0, 0.09, 640.0, 628.54, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4181, '3x cat litter', 1, 0.0, 2.29, 0.012827, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4181, '2x toilet rolls', 1, 0.0, 2.29, 0.012827, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4181, 'nescafe', 1, 0.0, 2.29, 0.012827, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4181, 'body wash', 1, 0.0, 2.29, 0.012827, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4181, 'pronutro', 1, 0.0, 2.29, 0.012827, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4181, 'spices xylitor', 1, 0.0, 2.29, 0.012827, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4181, 'jerky', 1, 0.0, 2.29, 0.012827, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Pushup bra + protective skate set + 4x pedestal fans WB: 4679 Supplier: Game Rosebank Date: 2025/10/09', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=7 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chloe Sheidan' AND surname='Johnson' ORDER BY id ASC LIMIT 1), 'pending', 4182, 'INV-20251031-00182', 1200.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-RGSUS82X', 0, 0, 0, 26.0, 0.15, 1040.0, 1075.31, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4182, 'Pushup bra', 1, 0.0, 8.67, 0.051205, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4182, 'protective skate set', 1, 0.0, 8.67, 0.051205, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4182, '4x pedestal fans', 1, 0.0, 8.67, 0.051205, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Soaps and 3x door rails WB: 4680 Supplier: Takealot Date: 2025/10/09', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=8 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chris' AND surname='Joubert' ORDER BY id ASC LIMIT 1), 'pending', 4183, 'INV-20251031-00183', 1000.00, 0, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-X11WT199', 0, 0, 0, 9.0, 0.03, 360.0, 177.78, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4183, 'Soaps and 3x door rails', 1, 0.0, 9.0, 0.025397, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Power transmission belts WB: 4682 Supplier: Habasit Date: 2025/10/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=8 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4185, 'INV-20251031-00185', 9053.78, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-ASSJ9L8G', 0, 0, 0, 2.0, 0.01, 80.0, 94.08, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4185, 'Power transmission belts', 1, 0.0, 2.0, 0.01344, 0.0, '960293030');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Duffel bag + cricket helmets + tshirt + nicotine patches + pop up net and stumps WB: 4683 Supplier: Takealot Date: 2025/10/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=8 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Justin' AND surname='Trappe' ORDER BY id ASC LIMIT 1), 'pending', 4186, 'INV-20251031-00186', 1000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-N3RHZAJ0', 0, 0, 0, 8.0, 0.11, 320.0, 792.11, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4186, 'Duffel bag', 1, 0.0, 1.6, 0.022632, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4186, 'cricket helmets', 1, 0.0, 1.6, 0.022632, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4186, 'tshirt', 1, 0.0, 1.6, 0.022632, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4186, 'nicotine patches', 1, 0.0, 1.6, 0.022632, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4186, 'pop up net and stumps', 1, 0.0, 1.6, 0.022632, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('30 rolls of carpet WB: 4684 Supplier: Rugs Original Date: 2025/10/09', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=9 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4187, 'INV-20251031-00187', 51965.32, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-EP5GK7PM', 0, 0, 0, 88.0, 0.2, 3520.0, 1428.08, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4187, '30 rolls of carpet', 1, 0.0, 88.0, 0.204011, 0.0, 'SOQ2087');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x air fryer + 1x hand blender + 1x magnetic building tiles + 1x folding potty seat  WB: 4685 Supplier: Takealot Date: 2025/10/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=9 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ian' AND surname='Lombard' ORDER BY id ASC LIMIT 1), 'pending', 4188, 'INV-20251031-00188', 2200.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-BFWDY7PY', 0, 0, 0, 11.0, 0.08, 440.0, 528.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4188, '1x air fryer', 1, 0.0, 2.75, 0.018865, 0.0, '224160727');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4188, '1x hand blender', 1, 0.0, 2.75, 0.018865, 0.0, '224160727');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4188, '1x magnetic building tiles', 1, 0.0, 2.75, 0.018865, 0.0, '224160727');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4188, '1x folding potty seat', 1, 0.0, 2.75, 0.018865, 0.0, '224160727');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Band saws + lathes and special cutters WB: 4687 Supplier: Asax Agencies Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=8 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ron' AND surname='Barnes-Webb' ORDER BY id ASC LIMIT 1), 'pending', 4190, 'INV-20251031-00190', 49452.73, 0, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-ORL3RBKS', 0, 0, 0, 16.0, 0.07, 640.0, 480.59, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4190, 'Band saws', 1, 0.0, 8.0, 0.034328, 0.0, '162279/176078');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4190, 'lathes and special cutters', 1, 0.0, 8.0, 0.034328, 0.0, '162279/176078');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x backpack + 4x t-shirts + 1x pickle bat + 5x cool towels + 3x earings + 1x bracelet WB: 4688 Supplier: Shein Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=9 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Rita' AND surname='Mcluckie' ORDER BY id ASC LIMIT 1), 'pending', 4191, 'INV-20251031-00191', 1200.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-8H210E77', 0, 0, 0, 3.0, 0.04, 120.0, 252.0, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4191, '1x backpack', 1, 0.0, 0.5, 0.006, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4191, '4x t-shirts', 1, 0.0, 0.5, 0.006, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4191, '1x pickle bat', 1, 0.0, 0.5, 0.006, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4191, '5x cool towels', 1, 0.0, 0.5, 0.006, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4191, '3x earings', 1, 0.0, 0.5, 0.006, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4191, '1x bracelet', 1, 0.0, 0.5, 0.006, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3x chocolate + 1x biscuits + 1x coffee + 1x blind date + 5x t-shirts WB: 4689 Supplier: Marushka Maree Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=9 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Lali' AND surname='Heath' ORDER BY id ASC LIMIT 1), 'pending', 4192, 'INV-20251031-00192', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-GDMQ6LPB', 0, 0, 0, 1.0, 0.01, 40.0, 50.82, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4192, '3x chocolate', 1, 0.0, 0.2, 0.001452, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4192, '1x biscuits', 1, 0.0, 0.2, 0.001452, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4192, '1x coffee', 1, 0.0, 0.2, 0.001452, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4192, '1x blind date', 1, 0.0, 0.2, 0.001452, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4192, '5x t-shirts', 1, 0.0, 0.2, 0.001452, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Liquid smokes WB: 4691 Supplier: Smoked Flavours Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Gabriella' AND surname='Kortland' ORDER BY id ASC LIMIT 1), 'pending', 4194, 'INV-20251031-00194', 800.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-5F2SH0P1', 0, 0, 0, 1.0, 0.0, 40.0, 22.54, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4194, 'Liquid smokes', 1, 0.0, 1.0, 0.00322, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3x 20g vegan protein powder WB: 4692 Supplier: In Space Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4195, 'INV-20251031-00195', 600.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-94C3TXI8', 0, 0, 0, 3.0, 0.02, 120.0, 149.73, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4195, '3x 20g vegan protein powder', 1, 0.0, 3.0, 0.02139, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Garmin WB: 4693 Supplier: Take A Lot Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Solomon Jeremiah' AND surname='Sembosi' ORDER BY id ASC LIMIT 1), 'pending', 4196, 'INV-20251031-00196', 3000.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-UBVEOEP9', 0, 0, 0, 1.0, 0.0, 40.0, 24.15, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4196, 'Garmin', 1, 0.0, 1.0, 0.00345, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Car shocks WB: 4694 Supplier: Total Control Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Caleb' AND surname='Simonson' ORDER BY id ASC LIMIT 1), 'pending', 4197, 'INV-20251031-00197', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-YQVFH0N5', 0, 0, 0, 4.0, 0.01, 160.0, 62.12, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4197, 'Car shocks', 1, 0.0, 4.0, 0.008874, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('8x office chairs WB: 4695 Supplier: Jubilate Lema Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dunstan Kipande' AND surname='Mua' ORDER BY id ASC LIMIT 1), 'pending', 4198, 'INV-20251031-00198', 31283.26, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-ID0UCJUS', 0, 0, 0, 121.0, 1.06, 4840.0, 7425.6, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4198, '8x office chairs', 1, 0.0, 121.0, 1.0608, 0.0, '01H-INV0525208');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('2x 2 piece toilet WB: 4696 Supplier: Trendy Taps Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dr Sabine' AND surname='Marten' ORDER BY id ASC LIMIT 1), 'pending', 4199, 'INV-20251031-00199', 9365.05, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-NK8OPW74', 0, 0, 0, 102.0, 0.3, 4080.0, 2131.28, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4199, '2x 2 piece toilet', 1, 0.0, 102.0, 0.304469, 0.0, '10127041');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('T-shirts + laundry bags + washing machine parts WB: 4697 Supplier: Bernhard Buizer Date: 2025/10/14', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Bubbles' AND surname='Laundry' ORDER BY id ASC LIMIT 1), 'pending', 4200, 'INV-20251031-00200', 1000.00, 0, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-4UXUX5ES', 0, 0, 0, 4.0, 0.03, 160.0, 244.13, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4200, 'T-shirts', 1, 0.0, 1.33, 0.011625, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4200, 'laundry bags', 1, 0.0, 1.33, 0.011625, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4200, 'washing machine parts', 1, 0.0, 1.33, 0.011625, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Cold meat pouches x 44 WB: 4698 Supplier: Peninsula Packaging Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Lesley De' AND surname='Kock' ORDER BY id ASC LIMIT 1), 'pending', 4201, 'INV-20251031-00201', 80861.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-1347E7EU', 0, 0, 0, 396.0, 0.62, 15840.0, 4323.09, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4201, 'Cold meat pouches x 44', 1, 0.0, 396.0, 0.617584, 0.0, '200227');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Pallet of hydraulics elements WB: 4699 Supplier: A R Africa Date: 2025/10/10', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Kayen' AND surname='Investments' ORDER BY id ASC LIMIT 1), 'pending', 4202, 'INV-20251031-00202', 247060.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-YS5WZXVD', 0, 0, 0, 336.0, 1.38, 13440.0, 9684.36, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4202, 'Pallet of hydraulics elements', 1, 0.0, 336.0, 1.38348, 0.0, '7099');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('5x bundles polyester rolls WB: 4700 Supplier: Chamdor Date: 2025/10/13', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=11 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4203, 'INV-20251031-00203', 22455.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-MMSEZ7GV', 0, 0, 0, 190.0, 1.08, 7600.0, 7581.0, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4203, '5x bundles polyester rolls', 1, 0.0, 190.0, 1.083, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Nitrogen flusher + thermal shrinking packaging machine + shrinking machine + film holder WB: 4701 Supplier: Go Glenpak Date: 2025/10/14', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 13, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=10 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Quinton' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4204, 'INV-20251031-00204', 57450.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-AH9J2Q1X', 0, 0, 0, 300.0, 1.35, 12000.0, 9421.79, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4204, 'Nitrogen flusher', 1, 0.0, 75.0, 0.336493, 0.0, '152239');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4204, 'thermal shrinking packaging machine', 1, 0.0, 75.0, 0.336493, 0.0, '152239');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4204, 'shrinking machine', 1, 0.0, 75.0, 0.336493, 0.0, '152239');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4204, 'film holder', 1, 0.0, 75.0, 0.336493, 0.0, '152239');




















INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x leather bag WB: 4704 Supplier: The Courier Guy Date: 2025/10/15', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=11 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Rodger' AND surname='Farren' ORDER BY id ASC LIMIT 1), 'pending', 4207, 'INV-20251031-00207', 1000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-LNOYT4V1', 0, 0, 0, 2.0, 0.02, 80.0, 158.76, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4207, '1x leather bag', 1, 0.0, 2.0, 0.02268, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Agricultural machine and spares WB: 4705 Supplier: Jedediah Equipment Date: 2025/10/14', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 13, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=21 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='John' AND surname='Power' ORDER BY id ASC LIMIT 1), 'pending', 4208, 'INV-20251031-00208', 1585499.94, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-4MNJFIRV', 0, 0, 0, 6000.0, 60.74, 240000.0, 425147.19, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4208, 'Agricultural machine and spares', 1, 0.0, 6000.0, 60.735313, 0.0, 'INV04178');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Perlite course grade + open trays of seeds + pot labels + vermiculite WB: 4706 Supplier: Grow Rite Date: 2025/10/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 13, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=12 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Quinton' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4209, 'INV-20251031-00209', 6770.40, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-N9IQTPIC', 0, 0, 0, 68.0, 1.02, 2720.0, 7169.34, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4209, 'Perlite course grade', 1, 0.0, 17.0, 0.256048, 0.0, 'INV33779');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4209, 'open trays of seeds', 1, 0.0, 17.0, 0.256048, 0.0, 'INV33779');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4209, 'pot labels', 1, 0.0, 17.0, 0.256048, 0.0, 'INV33779');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4209, 'vermiculite', 1, 0.0, 17.0, 0.256048, 0.0, 'INV33779');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Carestream industrex lo fixer and developer WB: 4707 Supplier: Gammatec Date: 2025/10/16', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=12 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nabaki' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4210, 'INV-20251031-00210', 10349.36, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-BD01BHHR', 0, 0, 0, 113.0, 0.23, 520.0, 1617.28, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4210, 'Carestream industrex lo fixer and developer', 1, 0.0, 113.0, 0.23104, 0.0, '224403/25');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Asi lite combo WB: 4709 Supplier: African Snake Bite Date: 2025/10/15', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=13 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ngoro Ngoro Safari' AND surname='Lodge' ORDER BY id ASC LIMIT 1), 'pending', 4212, 'INV-20251031-00212', 1856.53, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-O3HQN29Q', 0, 0, 0, 2.0, 0.02, 80.0, 139.86, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4212, 'Asi lite combo', 1, 0.0, 2.0, 0.01998, 0.0, '24061');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Plastic mats WB: 4710 Supplier: K & H Freight Date: 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=13 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4213, 'INV-20251031-00213', 2000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-E5Z8Z291', 0, 0, 0, 192.0, 2.9, 7680.0, 20289.62, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4213, 'Plastic mats', 1, 0.0, 192.0, 2.898517, 0.0, 'P');


































INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Oil seal indrives + cup bearings WB: 4715 Supplier: Goscor Date: 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=13 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ben' AND surname='Pelser' ORDER BY id ASC LIMIT 1), 'pending', 4218, 'INV-20251031-00218', 589.34, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-W4G9SNUV', 0, 0, 0, 4.0, 0.01, 160.0, 43.22, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4218, 'Oil seal indrives', 1, 0.0, 2.0, 0.003087, 0.0, '15438713/965');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4218, 'cup bearings', 1, 0.0, 2.0, 0.003087, 0.0, '15438713/965');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Bingo + cleaning brush + massager + cleaning brush + socks, bracelet, t-shirts +rings WB: 4716 Supplier: Shein Date: 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=13 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Rita' AND surname='Mcluckie' ORDER BY id ASC LIMIT 1), 'pending', 4219, 'INV-20251031-00219', 1000.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-REXZVA9D', 0, 0, 0, 4.0, 0.04, 160.0, 309.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4219, 'Bingo', 1, 0.0, 0.67, 0.007368, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4219, 'cleaning brush', 1, 0.0, 0.67, 0.007368, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4219, 'massager', 1, 0.0, 0.67, 0.007368, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4219, 'cleaning brush', 1, 0.0, 0.67, 0.007368, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4219, 'socks, bracelet, t-shirts', 1, 0.0, 0.67, 0.007368, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4219, 'rings', 1, 0.0, 0.67, 0.007368, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X2 dahua access cameras WB: 4717 Supplier: Communica Date: 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=13 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Shelomith' AND surname='Technologies' ORDER BY id ASC LIMIT 1), 'pending', 4220, 'INV-20251031-00220', 14937.89, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-AC2HEO07', 0, 0, 0, 8.0, 0.09, 320.0, 598.92, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4220, 'X2 dahua access cameras', 1, 0.0, 8.0, 0.08556, 0.0, '1000MD8H');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Fsk 832 lte gdsp full wireless kit WB: 4718 Supplier: Amecor Date: 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=14 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Johnathan' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4221, 'INV-20251031-00221', 197880.00, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-CGRH2VED', 0, 0, 0, 312.0, 1.36, 12480.0, 9534.32, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4221, 'Fsk 832 lte gdsp full wireless kit', 1, 0.0, 312.0, 1.362045, 0.0, 'INV57411');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('6x wood silicone vacuums WB: 4719 Supplier: Takealot Date: 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=14 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chris' AND surname='Joubert' ORDER BY id ASC LIMIT 1), 'pending', 4222, 'INV-20251031-00222', 1000.00, 0, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-VR0T73B5', 0, 0, 0, 15.0, 0.09, 600.0, 623.7, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4222, '6x wood silicone vacuums', 1, 0.0, 15.0, 0.0891, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x drawer tray for kitchen WB: 4721 Supplier: Takealot Date: 2025/10/20', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=14 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chris' AND surname='Joubert' ORDER BY id ASC LIMIT 1), 'pending', 4224, 'INV-20251031-00224', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-JB2QLR11', 0, 0, 0, 2.0, 0.03, 80.0, 191.1, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4224, '1x drawer tray for kitchen', 1, 0.0, 2.0, 0.0273, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1 Pallet Bike/Parts WB- 4723 Supplier:- BIDVEST MCCARTHY TOYOTA Date:- 2025/10/20', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=15 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Kiwango' AND surname='(Johnathan)' ORDER BY id ASC LIMIT 1), 'pending', 4226, 'INV-20251031-00226', 46707.92, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-C1T5W3IX', 0, 0, 0, 212.0, 0.02, 480.0, 154.56, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4226, '1 Pallet Bike/Parts', 1, 0.0, 212.0, 0.02208, 0.0, '0282CEPAAA6493');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Spares For Toyota WB- 4724 Supplier:- LIGHTING WAREHOUSE Date:- 2025/10/20', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=15 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4227, 'INV-20251031-00227', 38544.75, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-13OMUYIZ', 0, 0, 0, 12.0, 0.53, 3320.0, 3709.08, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4227, 'Spares For Toyota', 1, 0.0, 12.0, 0.529868, 0.0, '31945');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('C Lnt Dudrone Lights WB- 4725 Supplier:- LIVE STAINABLE Date:- 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4228, 'INV-20251031-00228', 27130.43, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-A1K2U1QE', 0, 0, 0, 83.04, 1.12, 5280.0, 7673.12, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4228, 'C Lnt Dudrone Lights', 8, 0.0, 10.38, 0.13702, 0.0, 'P');













INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('2x Art Gift Set - Paint With Canvas And Easel WB- 4727 Supplier:- ARAMEX Date:- 2025/10/20', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Chris' AND surname='Joubert' ORDER BY id ASC LIMIT 1), 'pending', 4230, 'INV-20251031-00230', 2000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-XO5P9DU1', 0, 0, 0, 20.0, 0.06, 400.0, 385.43, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4230, '2x Art Gift Set - Paint With Canvas And Easel', 2, 0.0, 10.0, 0.027531, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('24x Doritos WB- 4728 Supplier:- PICK N PAY Date:- 2025/10/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Alexandra' AND surname='Soine' ORDER BY id ASC LIMIT 1), 'pending', 4231, 'INV-20251031-00231', 500.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-P30UR41H', 0, 0, 0, 4.0, 0.07, 160.0, 509.08, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4231, '24x Doritos', 1, 0.0, 4.0, 0.072726, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('50 Off M24 Nut And Bolt Sample WB- 4729 Supplier:- MJH GLOBAL Date:- 2025/10/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jan' AND surname='Griesl' ORDER BY id ASC LIMIT 1), 'pending', 4232, 'INV-20251031-00232', 3600.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-NOMAKNG3', 0, 0, 0, 17.0, 0.02, 680.0, 107.1, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4232, '50 Off M24 Nut And Bolt Sample', 1, 0.0, 17.0, 0.0153, 0.0, '1471');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)   VALUES ('Blue Jeans + 90''s T-Shirt + Wallet + Card Pack + Logitech Mouse + Eyebrow Lifting + Eyesha WB- 4730 Supplier:- TRUWORTHS/TAKEALOT Date:- 2025/10/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=16 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Goretty Dos' AND surname='Ramos' ORDER BY id ASC LIMIT 1), 'pending', 4233, 'INV-20251031-00233', 2000.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:0:{}}', 'TRK-OVUAGUAB', 0, 0, 0, 2.0, 0.01, 80.0, 78.12, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4233, 'Blue Jeans', 1, 0.0, 0.29, 0.001594, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4233, '90''s T-Shirt', 1, 0.0, 0.29, 0.001594, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4233, 'Wallet', 1, 0.0, 0.29, 0.001594, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4233, 'Card Pack', 1, 0.0, 0.29, 0.001594, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4233, 'Logitech Mouse', 1, 0.0, 0.29, 0.001594, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4233, 'Eyebrow Lifting', 1, 0.0, 0.29, 0.001594, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4233, 'Eyesha', 1, 0.0, 0.29, 0.001594, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Desk + Pencil Box + Fishing Lures + Perfumes + Cooler + Scks + Hoverboard + Shorts + Goggl WB- 4731 Supplier:- TAKEALOT Date:- 2025/10/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=16 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nangini' AND surname='Lukumay' ORDER BY id ASC LIMIT 1), 'pending', 4234, 'INV-20251031-00234', 5000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-S3IC9OUV', 0, 0, 0, 10.0, 0.06, 400.0, 471.78, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Desk', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Pencil Box', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Fishing Lures', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Perfumes', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Cooler', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Scks', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Hoverboard', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Shorts', 2, 0.0, 0.56, 0.003744, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4234, 'Goggl', 2, 0.0, 0.56, 0.003744, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x Sander And Sandpaper WB- 4732 Supplier:- TURNER MORRIS Date:- 2025/10/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=16 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4235, 'INV-20251031-00235', 104573.50, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-WHC85VJB', 0, 0, 0, 104.01, 0.27, 4160.0, 1856.79, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4235, '1x Sander And Sandpaper', 3, 0.0, 34.67, 0.088418, 0.0, '90000455');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Rice Cakes + Cat Litter + Milk Frother + Ocinno + Longlife Milk WB- 4733 Supplier:- CHECKERS Date:- 2025/10/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=16 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4236, 'INV-20251031-00236', 1000.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-1QM1WTGW', 0, 0, 0, 13.0, 0.09, 520.0, 608.9, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4236, 'Rice Cakes', 1, 0.0, 2.6, 0.017397, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4236, 'Cat Litter', 1, 0.0, 2.6, 0.017397, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4236, 'Milk Frother', 1, 0.0, 2.6, 0.017397, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4236, 'Ocinno', 1, 0.0, 2.6, 0.017397, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4236, 'Longlife Milk', 1, 0.0, 2.6, 0.017397, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Floor Box + Sa Plug + International Plug WB- 4734 Supplier:- NRD LIGHTING CONCEPTS Date:- 2025/10/22', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=17 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Tim' AND surname='Leach' ORDER BY id ASC LIMIT 1), 'pending', 4237, 'INV-20251031-00237', 20700.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-7C23ASDK', 0, 0, 0, 5.0, 0.02, 200.0, 116.03, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4237, 'Floor Box', 1, 0.0, 1.67, 0.005525, 0.0, '16181');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4237, 'Sa Plug', 1, 0.0, 1.67, 0.005525, 0.0, '16181');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4237, 'International Plug', 1, 0.0, 1.67, 0.005525, 0.0, '16181');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x Crated Pizza Stone Set + Goggles + Cozy Coupe + Microscope Set + Various Other Items WB- 4735 Supplier:- TAKEALOT Date:- 2025/10/21', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=16 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sebastian' AND surname='Marquet' ORDER BY id ASC LIMIT 1), 'pending', 4238, 'INV-20251031-00238', 32357.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-GRFALISD', 0, 0, 0, 207.0, 1.74, 8280.0, 12128.77, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4238, '1x Crated Pizza Stone Set', 2, 0.0, 20.7, 0.173268, 0.0, '225988274');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4238, 'Goggles', 2, 0.0, 20.7, 0.173268, 0.0, '225988274');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4238, 'Cozy Coupe', 2, 0.0, 20.7, 0.173268, 0.0, '225988274');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4238, 'Microscope Set', 2, 0.0, 20.7, 0.173268, 0.0, '225988274');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4238, 'Various Other Items', 2, 0.0, 20.7, 0.173268, 0.0, '225988274');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Assortment Of Umbrellas + Yoga Mats WB- 4736 Supplier:- BRANDABILITY Date:- 2025/10/22', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 11, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=17 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Oliver' AND surname='Fox' ORDER BY id ASC LIMIT 1), 'pending', 4239, 'INV-20251031-00239', 36593.00, 1, 1, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:4:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-SGG1IPIZ', 0, 0, 0, 127.04, 0.8, 5080.0, 5594.25, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4239, 'Assortment Of Umbrellas', 4, 0.0, 15.88, 0.099897, 0.0, '65505');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4239, 'Yoga Mats', 4, 0.0, 15.88, 0.099897, 0.0, '65505');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Refurbished Blades WB- 4737 Supplier:- SAW SPECIALISTS Date:- 2025/10/22', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 13, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=17 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Nicolas' AND surname='Gant' ORDER BY id ASC LIMIT 1), 'pending', 4240, 'INV-20251031-00240', 18633.32, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-6LMGFB4C', 0, 0, 0, 107.0, 0.12, 4280.0, 909.72, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4240, 'Refurbished Blades', 2, 0.0, 53.5, 0.06498, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Clothes + Hair Oil + Glasses + Boat Flooring + Sun Hat + Welstik Tape + Key Holder + Bible WB- 4738 Supplier:- SHEIN Date:- 2025/10/22', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=17 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Sharmaine' AND surname='Broodryk' ORDER BY id ASC LIMIT 1), 'pending', 4241, 'INV-20251031-00241', 1000.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-8Y3PUSAS', 0, 0, 0, 4.0, 0.04, 160.0, 309.44, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Clothes', 1, 0.0, 0.5, 0.005526, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Hair Oil', 1, 0.0, 0.5, 0.005526, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Glasses', 1, 0.0, 0.5, 0.005526, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Boat Flooring', 1, 0.0, 0.5, 0.005526, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Sun Hat', 1, 0.0, 0.5, 0.005526, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Welstik Tape', 1, 0.0, 0.5, 0.005526, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Key Holder', 1, 0.0, 0.5, 0.005526, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4241, 'Bible', 1, 0.0, 0.5, 0.005526, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x Delonghi Electric Kettle WB- 4739 Supplier:- COURIER GUY Date:- 2025/10/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=18 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Martie' AND surname='Botha' ORDER BY id ASC LIMIT 1), 'pending', 4242, 'INV-20251031-00242', 500.00, 0, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-EP707UPA', 0, 0, 0, 6.0, 0.07, 240.0, 480.45, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4242, '1x Delonghi Electric Kettle', 1, 0.0, 6.0, 0.068635, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X780 Swing Top Glass Water Bottles + 210 Swing Top Glass Water Bottles WB- 4740 Supplier:- SACHIEL HOLDINGS Date:- 2025/10/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=18 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Jonz' AND surname='Express' ORDER BY id ASC LIMIT 1), 'pending', 4243, 'INV-20251031-00243', 34259.86, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-U2YDVFT6', 0, 0, 0, 495.0, 1.98, 19800.0, 13378.37, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4243, 'X780 Swing Top Glass Water Bottles', 33, 0.0, 7.5, 0.028958, 0.0, '4486');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4243, '210 Swing Top Glass Water Bottles', 33, 0.0, 7.5, 0.028958, 0.0, '4486');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('8x Dining Chairs + 2x Dining Chairs + 2 Piece Sofa + 3x Nest Of Coffee Tables WB- 4741 Supplier:- CRM LOGISTICS Date:- 2025/10/23', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=18 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dipen' AND surname='Shah' ORDER BY id ASC LIMIT 1), 'pending', 4244, 'INV-20251031-00244', 200126.00, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-35FM1T8S', 0, 0, 0, 514.0500000000001, 4.35, 20560.0, 30739.27, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4244, '8x Dining Chairs', 15, 0.0, 8.57, 0.073189, 0.0, 'INV 2861');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4244, '2x Dining Chairs', 15, 0.0, 8.57, 0.073189, 0.0, 'INV 2861');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4244, '2 Piece Sofa', 15, 0.0, 8.57, 0.073189, 0.0, 'INV 2861');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4244, '3x Nest Of Coffee Tables', 15, 0.0, 8.57, 0.073189, 0.0, 'INV 2861');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Aeroshell Sport Plus 4 Oil/1.O Ltr WB- 4742 Supplier:- COMET AVIATION SUPPLIES Date:- 2025/10/24', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=19 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Cedrick Motte Quin' AND surname='Zanziber' ORDER BY id ASC LIMIT 1), 'pending', 4245, 'INV-20251031-00245', 18583.32, 1, 0, 1, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:12:"include_sadc";d:1000.0;}}', 'TRK-06D8THTS', 0, 0, 0, 58.0, 0.18, 2320.0, 1197.84, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4245, 'Aeroshell Sport Plus 4 Oil/1.O Ltr', 2, 0.0, 29.0, 0.08556, 0.0, '098191');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('7x Pallets Of Body Lotions + Oils + Masks + Toners + Peels + Body Brushes WB- 4743 Supplier:- HEALING EARTH Date:- 2025/10/24', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, 1, (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Kuki' AND surname='Nijiro' ORDER BY id ASC LIMIT 1), 'pending', 4246, 'INV-20251031-00246', 133392.72, 1, 1, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:3:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;s:14:"include_sad500";d:5000.0;}}', 'TRK-FVYTNOM5', 0, 0, 0, 6687.03, 13.719999999999999, 267480.0, 95841.0, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4246, '7x Pallets Of Body Lotions', 7, 0.0, 159.21, 0.32599, 0.0, '29759/29761');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4246, 'Oils', 7, 0.0, 159.21, 0.32599, 0.0, '29759/29761');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4246, 'Masks', 7, 0.0, 159.21, 0.32599, 0.0, '29759/29761');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4246, 'Toners', 7, 0.0, 159.21, 0.32599, 0.0, '29759/29761');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4246, 'Peels', 7, 0.0, 159.21, 0.32599, 0.0, '29759/29761');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4246, 'Body Brushes', 7, 0.0, 159.21, 0.32599, 0.0, '29759/29761');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Bostik Glue + Heat Gun + Silicone Roller + Scissors + Masking Tape + Seam Sealing Tape WB- 4744 Supplier:- TENTICKLE Date:- 2025/10/24', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=19 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Praveena' AND surname='Selvarajah' ORDER BY id ASC LIMIT 1), 'pending', 4247, 'INV-20251031-00247', 12500.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-KQWHW3WF', 0, 0, 0, 51.0, 0.18, 2040.0, 1183.52, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4247, 'Bostik Glue', 3, 0.0, 2.83, 0.009393, 0.0, 'LT25.02806');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4247, 'Heat Gun', 3, 0.0, 2.83, 0.009393, 0.0, 'LT25.02806');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4247, 'Silicone Roller', 3, 0.0, 2.83, 0.009393, 0.0, 'LT25.02806');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4247, 'Scissors', 3, 0.0, 2.83, 0.009393, 0.0, 'LT25.02806');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4247, 'Masking Tape', 3, 0.0, 2.83, 0.009393, 0.0, 'LT25.02806');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4247, 'Seam Sealing Tape', 3, 0.0, 2.83, 0.009393, 0.0, 'LT25.02806');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('Betty Basin - Black WB- 4745 Supplier:- LUX CRETE Date:- 2025/10/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=20 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Dr Sabine' AND surname='Marten' ORDER BY id ASC LIMIT 1), 'pending', 4248, 'INV-20251031-00248', 7250.00, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-WN9F1V7L', 0, 0, 0, 16.0, 0.1, 640.0, 667.13, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4248, 'Betty Basin - Black', 2, 0.0, 8.0, 0.047652, 0.0, '4616');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3x Firming Gel + Tweezers + 3x Afri True Hair Bonnet + Satin Bonnet + Mascara WB- 4746 Supplier:- CLICKS Date:- 2025/10/17', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=14 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Goretty Dos' AND surname='Ramos' ORDER BY id ASC LIMIT 1), 'pending', 4249, 'INV-20251031-00249', 600.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-2U0XCB0C', 0, 0, 0, 1.0, 0.01, 40.0, 72.8, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4249, '3x Firming Gel', 1, 0.0, 0.2, 0.00208, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4249, 'Tweezers', 1, 0.0, 0.2, 0.00208, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4249, '3x Afri True Hair Bonnet', 1, 0.0, 0.2, 0.00208, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4249, 'Satin Bonnet', 1, 0.0, 0.2, 0.00208, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4249, 'Mascara', 1, 0.0, 0.2, 0.00208, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('X1 Domestic 35 Mobile Compressor + 34l Cooler WB- 4747 Supplier:- TAKEALOT Date:- 2025/10/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), NULL, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=20 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Ted' AND surname='Rabenhold' ORDER BY id ASC LIMIT 1), 'pending', 4250, 'INV-20251031-00250', 2500.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-M23PC7EC', 0, 0, 0, 20.0, 0.12, 800.0, 840.22, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4250, 'X1 Domestic 35 Mobile Compressor', 1, 0.0, 10.0, 0.060016, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4250, '34l Cooler', 1, 0.0, 10.0, 0.060016, 0.0, 'P');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('3x Blocking Pads + 5x Large Round Cut White + 30x Large Round Cut White WB- 4748 Supplier:- OPTIC TRADE LINKS Date:- 2025/10/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=20 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Yusuf' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4251, 'INV-20251031-00251', 25723.25, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-OG3R16J2', 0, 0, 0, 26.0, 0.07, 1040.0, 515.2, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4251, '3x Blocking Pads', 1, 0.0, 8.67, 0.024533, 0.0, '98616/98617/17293');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4251, '5x Large Round Cut White', 1, 0.0, 8.67, 0.024533, 0.0, '98616/98617/17293');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4251, '30x Large Round Cut White', 1, 0.0, 8.67, 0.024533, 0.0, '98616/98617/17293');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x Bolon Eyewear + 1x Ripple Eye Wear X3 Sizes WB- 4749 Supplier:- DAMAR OPTICAL Date:- 2025/10/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 6, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=20 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Yusuf' AND surname=NULL ORDER BY id ASC LIMIT 1), 'pending', 4252, 'INV-20251031-00252', 1316.14, 0, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:1850.0;s:9:"vat_total";d:0.0;}}', 'TRK-QJLINPOM', 0, 0, 0, 1.0, 0.01, 40.0, 78.12, 'volume');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4252, '1x Bolon Eyewear', 1, 0.0, 0.5, 0.00558, 0.0, 'INV420746');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4252, '1x Ripple Eye Wear X3 Sizes', 1, 0.0, 0.5, 0.00558, 0.0, 'INV420746');






INSERT IGNORE INTO `{PREFIX}kit_waybills` (description, direction_id, city_id, delivery_id, customer_id, approval, waybill_no, product_invoice_number, product_invoice_amount, vat_include, include_sad500, include_sadc, miscellaneous, tracking_number, item_length, item_width, item_height, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis)  VALUES ('1x Carbon Steel Wok + 1x Tramontina Knife + 1x Grill Pan + Kitchen Scale + Frying Pan + Bo WB- 4750 Supplier:- ARAMEX Date:- 2025/10/27', COALESCE((SELECT id FROM `{PREFIX}kit_shipping_directions` WHERE description='ZA->TZ' ORDER BY id ASC LIMIT 1), (SELECT id FROM `{PREFIX}kit_shipping_directions` ORDER BY id ASC LIMIT 1)), 8, (SELECT id FROM `{PREFIX}kit_deliveries` WHERE id=20 LIMIT 1), (SELECT cust_id FROM `{PREFIX}kit_customers` WHERE name='Emma' AND surname='Wilson' ORDER BY id ASC LIMIT 1), 'pending', 4253, 'INV-20251031-00253', 1200.00, 1, 0, 0, 'a:3:{s:10:"misc_items";a:0:{}s:10:"misc_total";s:4:"0.00";s:6:"others";a:2:{s:25:"international_price_rands";d:0.0;s:9:"vat_total";d:0.0;}}', 'TRK-PKL0IJD6', 0, 0, 0, 14.0, 0.07, 560.0, 476.28, 'mass');

INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4253, '1x Carbon Steel Wok', 1, 0.0, 2.33, 0.01134, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4253, '1x Tramontina Knife', 1, 0.0, 2.33, 0.01134, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4253, '1x Grill Pan', 1, 0.0, 2.33, 0.01134, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4253, 'Kitchen Scale', 1, 0.0, 2.33, 0.01134, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4253, 'Frying Pan', 1, 0.0, 2.33, 0.01134, 0.0, 'P');
INSERT IGNORE INTO `{PREFIX}kit_waybill_items` (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice) VALUES (4253, 'Bo', 1, 0.0, 2.33, 0.01134, 0.0, 'P');






-- ===============================



-- PATCH: Populate missing descriptions from items



-- Fills {PREFIX}kit_waybills.description using the first item's name



-- Run automatically when this seed executes



-- ===============================



/* Ensure we only touch empty descriptions and prefer the earliest item per waybill */



-- UPDATE `{PREFIX}kit_waybills` w
-- INNER JOIN (
--   SELECT `waybillno`, MIN(`id`) AS `min_item_id`
--   FROM `{PREFIX}kit_waybill_items`
--   GROUP BY `waybillno`
-- ) s ON s.`waybillno` = w.`waybill_no`
-- INNER JOIN `{PREFIX}kit_waybill_items` i ON i.`id` = s.`min_item_id`
-- SET w.`description` = LEFT(TRIM(i.`item_name`), 255)
-- WHERE (w.`description` IS NULL OR w.`description` = '')
--   AND COALESCE(TRIM(i.`item_name`), '') <> '';









-- Optional: if a waybill still has no items, put a generic description



-- UPDATE `{PREFIX}kit_waybills` w
-- LEFT JOIN (
--   SELECT DISTINCT `waybillno`
--   FROM `{PREFIX}kit_waybill_items`
-- ) has_items ON has_items.`waybillno` = w.`waybill_no`
-- SET w.`description` = CONCAT('Waybill ', w.`waybill_no`)
-- WHERE (w.`description` IS NULL OR w.`description` = '')
--   AND has_items.`waybillno` IS NULL;





-- ===============================



-- PATCH: Force item_name to use Item description (not Waybill description)



-- Applies to rows where item_name is NULL/blank OR exactly equals the waybill description



-- ===============================



/* 1) Replace obvious copies of waybill description with a cleaned item description */



-- UPDATE `{PREFIX}kit_waybill_items` i
-- 
-- 
-- 
-- JOIN `{PREFIX}kit_waybills` w ON w.`waybill_no` = i.`waybillno`
-- 
-- 
-- 
-- SET i.`item_name` = LEFT(TRIM(
-- 
-- 
-- 
--                     TRIM(BOTH '-' FROM
-- 
-- 
-- 
--                       TRIM(BOTH ',' FROM
-- 
-- 
-- 
--                         REGEXP_REPLACE(
-- 
-- 
-- 
--                           REGEXP_REPLACE(
-- 
-- 
-- 
--                             REGEXP_REPLACE(
-- 
-- 
-- 
--                               COALESCE(w.`description`, ''),
-- 
-- 
-- 
--                               'Supplier:.*$', ''),      -- drop supplier tail
-- 
-- 
-- 
--                             'Date:.*$', ''),            -- drop date tail
-- 
-- 
-- 
--                           'WB[- ]?[0-9]+', ''           -- drop WB- 1234 markers
-- 
-- 
-- 
--                         )
-- 
-- 
-- 
--                       )
-- 
-- 
-- 
--                     )
-- 
-- 
-- 
--                   ), 255)
-- 
-- 
-- 
-- WHERE (i.`item_name` IS NULL OR i.`item_name` = '' OR i.`item_name` = w.`description`);








/* 2) Final safeguard: if cleaning yields empty, give a neutral per-waybill label */



-- UPDATE `{PREFIX}kit_waybill_items` i
-- 
-- 
-- 
-- JOIN `{PREFIX}kit_waybills` w ON w.`waybill_no` = i.`waybillno`
-- 
-- 
-- 
-- SET i.`item_name` = CONCAT('Item for WB ', w.`waybill_no`)
-- 
-- 
-- 
-- WHERE (i.`item_name` IS NULL OR i.`item_name` = '');

