# ArbuzAPI


https://www.postman.com/solar-star-898881/workspace/apis/collection/23016425-c9b60649-1d88-4541-8111-14419c618dc9?action=share&creator=23016425



DATABASE STRUCTURE:

CREATE TABLE products (
id int(11) NOT NULL AUTO_INCREMENT,
name varchar(255) DEFAULT NULL,
weight decimal(10,2) DEFAULT NULL,
availability tinyint(1) DEFAULT NULL,
PRIMARY KEY (id)
);

CREATE TABLE subscriptions (
id int(11) NOT NULL AUTO_INCREMENT,
customer_name varchar(255) DEFAULT NULL,
delivery_address varchar(255) DEFAULT NULL,
phone varchar(20) DEFAULT NULL,
start_date date DEFAULT NULL,
end_date date DEFAULT NULL,
delivery_period varchar(255) DEFAULT NULL,
delivery_day varchar(20) DEFAULT NULL,
PRIMARY KEY (id)
);

CREATE TABLE basket (
id int(11) NOT NULL AUTO_INCREMENT,
subscription_id int(11) DEFAULT NULL,
product_id int(11) DEFAULT NULL,
quantity int(11) DEFAULT NULL,
PRIMARY KEY (id)
);
