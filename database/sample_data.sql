-- Sample Menu Items Data
-- This will populate the database with some sample items for testing

USE tapas_menu;

-- Sample items for Food > Sushi Rolls section
INSERT INTO menu_items (section_id, name, description, price, dietary_info, display_order, is_featured) VALUES
((SELECT id FROM menu_sections WHERE name = 'Sushi Rolls' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'California Roll', 'Crab, avocado, cucumber with sesame seeds', 12.00, '', 1, 1),
((SELECT id FROM menu_sections WHERE name = 'Sushi Rolls' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Spicy Tuna Roll', 'Fresh tuna, spicy mayo, cucumber, scallions', 14.00, 'Spicy', 2, 1),
((SELECT id FROM menu_sections WHERE name = 'Sushi Rolls' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Dragon Roll', 'Eel, cucumber topped with avocado and eel sauce', 18.00, '', 3, 0),
((SELECT id FROM menu_sections WHERE name = 'Sushi Rolls' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Philadelphia Roll', 'Salmon, cream cheese, cucumber', 13.00, '', 4, 0);

-- Sample items for Food > Appetizers section
INSERT INTO menu_items (section_id, name, description, price, dietary_info, display_order, is_featured) VALUES
((SELECT id FROM menu_sections WHERE name = 'Appetizers' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Crispy Crab Bites', 'Delicate crab meat with golden crispy coating', 14.00, '', 1, 1),
((SELECT id FROM menu_sections WHERE name = 'Appetizers' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Chicken Satay', 'Grilled chicken skewers with peanut dipping sauce', 12.00, '', 2, 1),
((SELECT id FROM menu_sections WHERE name = 'Appetizers' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Edamame', 'Steamed young soybeans with sea salt', 6.00, 'Vegan, Gluten-Free', 3, 0),
((SELECT id FROM menu_sections WHERE name = 'Appetizers' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Gyoza', 'Pan-fried pork dumplings with dipping sauce', 8.00, '', 4, 0);

-- Sample items for Food > Small Plates section
INSERT INTO menu_items (section_id, name, description, price, dietary_info, display_order, is_featured) VALUES
((SELECT id FROM menu_sections WHERE name = 'Small Plates' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'BBQ Baby Back Ribs', 'Tender ribs glazed with our signature BBQ sauce', 18.00, '', 1, 0),
((SELECT id FROM menu_sections WHERE name = 'Small Plates' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Pork Belly Bao', 'Steamed buns with braised pork belly and pickled vegetables', 16.00, '', 2, 0),
((SELECT id FROM menu_sections WHERE name = 'Small Plates' AND menu_id = (SELECT id FROM menus WHERE name = 'Food')), 
 'Laab Beef Taco', 'Thai-style beef salad in soft taco shell', 15.00, 'Spicy', 3, 0);

-- Sample items for Drinks menu
INSERT INTO menu_items (section_id, name, description, price, dietary_info, display_order, is_featured) VALUES
((SELECT id FROM menu_sections WHERE name = 'Cocktails' AND menu_id = (SELECT id FROM menus WHERE name = 'Drinks')), 
 'Sake Martini', 'Premium sake with a twist of citrus', 12.00, '', 1, 0),
((SELECT id FROM menu_sections WHERE name = 'Cocktails' AND menu_id = (SELECT id FROM menus WHERE name = 'Drinks')), 
 'Asian Pear Mojito', 'Fresh Asian pear, mint, lime, and rum', 11.00, '', 2, 0),
((SELECT id FROM menu_sections WHERE name = 'Beer' AND menu_id = (SELECT id FROM menus WHERE name = 'Drinks')), 
 'Sapporo', 'Japanese premium lager', 6.00, '', 1, 0),
((SELECT id FROM menu_sections WHERE name = 'Beer' AND menu_id = (SELECT id FROM menus WHERE name = 'Drinks')), 
 'Asahi Super Dry', 'Crisp and clean Japanese beer', 6.00, '', 2, 0),
((SELECT id FROM menu_sections WHERE name = 'Non-Alcoholic' AND menu_id = (SELECT id FROM menus WHERE name = 'Drinks')), 
 'Japanese Green Tea', 'Hot or iced traditional green tea', 4.00, 'Vegan', 1, 0);

-- Sample items for Wine/Sake menu
INSERT INTO menu_items (section_id, name, description, price, dietary_info, display_order, is_featured) VALUES
((SELECT id FROM menu_sections WHERE name = 'Sake' AND menu_id = (SELECT id FROM menus WHERE name = 'Wine')), 
 'Junmai Daiginjo', 'Premium sake with delicate floral notes', 15.00, '', 1, 0),
((SELECT id FROM menu_sections WHERE name = 'Sake' AND menu_id = (SELECT id FROM menus WHERE name = 'Wine')), 
 'Nigori Sake', 'Unfiltered sake with creamy texture', 12.00, '', 2, 0);

-- Sample items for Special menu
INSERT INTO menu_items (section_id, name, description, price, dietary_info, display_order, is_featured) VALUES
((SELECT id FROM menu_sections WHERE name = 'Chef''s Specials' AND menu_id = (SELECT id FROM menus WHERE name = 'Special')), 
 'Omakase Platter', 'Chef''s selection of premium sushi and sashimi', 45.00, '', 1, 0),
((SELECT id FROM menu_sections WHERE name = 'Seasonal Items' AND menu_id = (SELECT id FROM menus WHERE name = 'Special')), 
 'Seasonal Fish Special', 'Market price based on daily catch', NULL, '', 1, 0);

-- Sample images (placeholder paths - you'd replace these with actual image paths)
INSERT INTO menu_item_images (item_id, image_path, alt_text, is_primary, display_order) VALUES
((SELECT id FROM menu_items WHERE name = 'California Roll'), 'images/food/california-roll.jpg', 'California Roll', 1, 1),
((SELECT id FROM menu_items WHERE name = 'Spicy Tuna Roll'), 'images/food/spicy-tuna-roll.jpg', 'Spicy Tuna Roll', 1, 1),
((SELECT id FROM menu_items WHERE name = 'Crispy Crab Bites'), 'images/food/Crispy Crab Bites.jpg', 'Crispy Crab Bites', 1, 1),
((SELECT id FROM menu_items WHERE name = 'Chicken Satay'), 'images/food/Chicken Satay.jpg', 'Chicken Satay', 1, 1),
((SELECT id FROM menu_items WHERE name = 'BBQ Baby Back Ribs'), 'images/food/BBQ Baby Back Ribs.jpg', 'BBQ Baby Back Ribs', 1, 1),
((SELECT id FROM menu_items WHERE name = 'Laab Beef Taco'), 'images/food/Laab Beef Taco.jpg', 'Laab Beef Taco', 1, 1);
