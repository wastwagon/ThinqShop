<?php
/**
 * Comprehensive Products Import Script
 * Imports all categories and products for ThinQShopping
 * Uses placeholder images that can be replaced later
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Placeholder image URL (single image for all products)
$placeholderImage = 'https://images.unsplash.com/photo-1560472355-536de3962603?w=800';

$db = new Database();
$conn = $db->getConnection();

// Comprehensive Categories and Products
$categoriesData = [
    'Electronics' => [
        'products' => [
            ['name' => 'Smartphone 128GB', 'price' => 899.99, 'stock' => 50],
            ['name' => 'Smartphone 256GB', 'price' => 1199.99, 'stock' => 30],
            ['name' => 'Tablet 10.1"', 'price' => 449.99, 'stock' => 40],
            ['name' => 'Laptop 15.6"', 'price' => 1299.99, 'stock' => 25],
            ['name' => 'Laptop 14"', 'price' => 999.99, 'stock' => 35],
            ['name' => 'Wireless Headphones', 'price' => 129.99, 'stock' => 75],
            ['name' => 'Earbuds', 'price' => 79.99, 'stock' => 100],
            ['name' => 'Bluetooth Speaker', 'price' => 79.99, 'stock' => 90],
            ['name' => 'Smart Watch', 'price' => 249.99, 'stock' => 60],
            ['name' => 'Fitness Tracker', 'price' => 89.99, 'stock' => 80],
            ['name' => 'Camera DSLR', 'price' => 899.99, 'stock' => 20],
            ['name' => 'Action Camera', 'price' => 199.99, 'stock' => 45],
            ['name' => 'Gaming Mouse', 'price' => 49.99, 'stock' => 100],
            ['name' => 'Gaming Keyboard', 'price' => 79.99, 'stock' => 85],
            ['name' => 'USB Flash Drive 64GB', 'price' => 19.99, 'stock' => 200],
            ['name' => 'External Hard Drive 1TB', 'price' => 89.99, 'stock' => 50],
            ['name' => 'Power Bank 20000mAh', 'price' => 39.99, 'stock' => 120],
            ['name' => 'Phone Case', 'price' => 14.99, 'stock' => 150],
            ['name' => 'Screen Protector', 'price' => 9.99, 'stock' => 200],
            ['name' => 'USB Cable', 'price' => 7.99, 'stock' => 300],
        ]
    ],
    'Fashion & Clothing' => [
        'products' => [
            ['name' => 'Leather Jacket', 'price' => 199.99, 'stock' => 35],
            ['name' => 'Denim Jeans', 'price' => 69.99, 'stock' => 85],
            ['name' => 'Casual T-Shirt', 'price' => 29.99, 'stock' => 120],
            ['name' => 'Polo Shirt', 'price' => 39.99, 'stock' => 100],
            ['name' => 'Dress Shirt', 'price' => 49.99, 'stock' => 95],
            ['name' => 'Hoodie', 'price' => 59.99, 'stock' => 70],
            ['name' => 'Sweater', 'price' => 54.99, 'stock' => 65],
            ['name' => 'Running Shoes', 'price' => 129.99, 'stock' => 60],
            ['name' => 'Casual Sneakers', 'price' => 89.99, 'stock' => 75],
            ['name' => 'Dress Shoes', 'price' => 149.99, 'stock' => 40],
            ['name' => 'Sandals', 'price' => 34.99, 'stock' => 90],
            ['name' => 'Designer Sunglasses', 'price' => 89.99, 'stock' => 80],
            ['name' => 'Wristwatch', 'price' => 159.99, 'stock' => 70],
            ['name' => 'Backpack', 'price' => 79.99, 'stock' => 55],
            ['name' => 'Handbag', 'price' => 99.99, 'stock' => 50],
            ['name' => 'Belt', 'price' => 34.99, 'stock' => 110],
            ['name' => 'Wallet', 'price' => 29.99, 'stock' => 95],
            ['name' => 'Baseball Cap', 'price' => 24.99, 'stock' => 130],
            ['name' => 'Scarf', 'price' => 19.99, 'stock' => 140],
            ['name' => 'Socks Pack', 'price' => 12.99, 'stock' => 200],
        ]
    ],
    'Home & Living' => [
        'products' => [
            ['name' => 'Coffee Maker', 'price' => 89.99, 'stock' => 45],
            ['name' => 'Throw Pillow Set', 'price' => 39.99, 'stock' => 60],
            ['name' => 'Desk Lamp', 'price' => 34.99, 'stock' => 75],
            ['name' => 'Plant Pot Set', 'price' => 24.99, 'stock' => 90],
            ['name' => 'Wall Clock', 'price' => 54.99, 'stock' => 50],
            ['name' => 'Dinnerware Set', 'price' => 79.99, 'stock' => 40],
            ['name' => 'Yoga Mat', 'price' => 29.99, 'stock' => 65],
            ['name' => 'Storage Boxes', 'price' => 19.99, 'stock' => 110],
            ['name' => 'Bed Sheets Set', 'price' => 49.99, 'stock' => 55],
            ['name' => 'Curtains', 'price' => 69.99, 'stock' => 35],
            ['name' => 'Rug 5x7', 'price' => 129.99, 'stock' => 30],
            ['name' => 'Mirror', 'price' => 44.99, 'stock' => 45],
            ['name' => 'Picture Frame Set', 'price' => 29.99, 'stock' => 80],
            ['name' => 'Candle Set', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Bath Towel Set', 'price' => 34.99, 'stock' => 70],
            ['name' => 'Kitchen Towels', 'price' => 14.99, 'stock' => 120],
            ['name' => 'Vacuum Cleaner', 'price' => 199.99, 'stock' => 25],
            ['name' => 'Floor Mop', 'price' => 24.99, 'stock' => 85],
            ['name' => 'Laundry Basket', 'price' => 19.99, 'stock' => 95],
            ['name' => 'Clothes Hanger Set', 'price' => 12.99, 'stock' => 150],
        ]
    ],
    'Beauty & Personal Care' => [
        'products' => [
            ['name' => 'Face Moisturizer', 'price' => 34.99, 'stock' => 85],
            ['name' => 'Perfume 50ml', 'price' => 79.99, 'stock' => 55],
            ['name' => 'Hair Dryer', 'price' => 49.99, 'stock' => 70],
            ['name' => 'Skincare Set', 'price' => 64.99, 'stock' => 45],
            ['name' => 'Makeup Brush Set', 'price' => 29.99, 'stock' => 95],
            ['name' => 'Shampoo & Conditioner', 'price' => 18.99, 'stock' => 100],
            ['name' => 'Body Lotion', 'price' => 24.99, 'stock' => 90],
            ['name' => 'Face Wash', 'price' => 19.99, 'stock' => 110],
            ['name' => 'Sunscreen SPF 50', 'price' => 29.99, 'stock' => 75],
            ['name' => 'Lipstick Set', 'price' => 39.99, 'stock' => 80],
            ['name' => 'Nail Polish Set', 'price' => 24.99, 'stock' => 95],
            ['name' => 'Hair Straightener', 'price' => 89.99, 'stock' => 40],
            ['name' => 'Electric Razor', 'price' => 79.99, 'stock' => 50],
            ['name' => 'Toothbrush Set', 'price' => 14.99, 'stock' => 150],
            ['name' => 'Deodorant', 'price' => 12.99, 'stock' => 200],
            ['name' => 'Hair Clipper', 'price' => 59.99, 'stock' => 60],
            ['name' => 'Beard Oil', 'price' => 24.99, 'stock' => 85],
            ['name' => 'Face Mask Pack', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Body Scrub', 'price' => 22.99, 'stock' => 90],
            ['name' => 'Hand Cream', 'price' => 16.99, 'stock' => 120],
        ]
    ],
    'Sports & Outdoors' => [
        'products' => [
            ['name' => 'Basketball', 'price' => 39.99, 'stock' => 60],
            ['name' => 'Soccer Ball', 'price' => 34.99, 'stock' => 80],
            ['name' => 'Tennis Racket', 'price' => 89.99, 'stock' => 35],
            ['name' => 'Dumbbells Set', 'price' => 149.99, 'stock' => 25],
            ['name' => 'Cycling Helmet', 'price' => 59.99, 'stock' => 50],
            ['name' => 'Tent 4-Person', 'price' => 199.99, 'stock' => 20],
            ['name' => 'Sleeping Bag', 'price' => 79.99, 'stock' => 40],
            ['name' => 'Water Bottle', 'price' => 14.99, 'stock' => 150],
            ['name' => 'Gym Bag', 'price' => 49.99, 'stock' => 65],
            ['name' => 'Resistance Bands Set', 'price' => 29.99, 'stock' => 85],
            ['name' => 'Jump Rope', 'price' => 12.99, 'stock' => 120],
            ['name' => 'Yoga Blocks', 'price' => 24.99, 'stock' => 70],
            ['name' => 'Exercise Mat', 'price' => 34.99, 'stock' => 80],
            ['name' => 'Running Belt', 'price' => 19.99, 'stock' => 95],
            ['name' => 'Sports Watch', 'price' => 119.99, 'stock' => 45],
            ['name' => 'Hiking Boots', 'price' => 159.99, 'stock' => 30],
            ['name' => 'Camping Lantern', 'price' => 29.99, 'stock' => 75],
            ['name' => 'Fishing Rod', 'price' => 89.99, 'stock' => 35],
            ['name' => 'Badminton Set', 'price' => 54.99, 'stock' => 50],
            ['name' => 'Volleyball', 'price' => 32.99, 'stock' => 65],
        ]
    ],
    'Books & Media' => [
        'products' => [
            ['name' => 'Best Seller Book', 'price' => 24.99, 'stock' => 100],
            ['name' => 'Notebook Set', 'price' => 14.99, 'stock' => 150],
            ['name' => 'Pen Set', 'price' => 12.99, 'stock' => 200],
            ['name' => 'Pencil Case', 'price' => 9.99, 'stock' => 180],
            ['name' => 'Backpack School', 'price' => 49.99, 'stock' => 70],
            ['name' => 'Calculator', 'price' => 19.99, 'stock' => 110],
            ['name' => 'Dictionary', 'price' => 34.99, 'stock' => 60],
            ['name' => 'Stapler', 'price' => 12.99, 'stock' => 140],
            ['name' => 'Highlighters Set', 'price' => 8.99, 'stock' => 200],
            ['name' => 'Folder Set', 'price' => 16.99, 'stock' => 120],
            ['name' => 'USB Drive 32GB', 'price' => 14.99, 'stock' => 180],
            ['name' => 'Book Stand', 'price' => 24.99, 'stock' => 80],
            ['name' => 'Reading Lamp', 'price' => 29.99, 'stock' => 75],
            ['name' => 'Bookmark Set', 'price' => 7.99, 'stock' => 220],
            ['name' => 'Whiteboard', 'price' => 44.99, 'stock' => 50],
            ['name' => 'Marker Set', 'price' => 11.99, 'stock' => 160],
            ['name' => 'Binder', 'price' => 18.99, 'stock' => 130],
            ['name' => 'Index Cards', 'price' => 9.99, 'stock' => 190],
            ['name' => 'Ruler Set', 'price' => 6.99, 'stock' => 200],
            ['name' => 'Compass Set', 'price' => 14.99, 'stock' => 90],
        ]
    ],
    'Toys & Games' => [
        'products' => [
            ['name' => 'Board Game', 'price' => 34.99, 'stock' => 55],
            ['name' => 'Action Figure', 'price' => 19.99, 'stock' => 90],
            ['name' => 'Puzzle 1000 Pieces', 'price' => 24.99, 'stock' => 65],
            ['name' => 'Remote Control Car', 'price' => 49.99, 'stock' => 50],
            ['name' => 'Building Blocks Set', 'price' => 39.99, 'stock' => 70],
            ['name' => 'Doll Set', 'price' => 29.99, 'stock' => 80],
            ['name' => 'Art Supplies Set', 'price' => 34.99, 'stock' => 60],
            ['name' => 'Telescope', 'price' => 149.99, 'stock' => 25],
            ['name' => 'Robot Toy', 'price' => 79.99, 'stock' => 40],
            ['name' => 'Water Gun', 'price' => 14.99, 'stock' => 100],
            ['name' => 'Frisbee', 'price' => 9.99, 'stock' => 130],
            ['name' => 'Yo-Yo', 'price' => 7.99, 'stock' => 150],
            ['name' => 'Rubik\'s Cube', 'price' => 12.99, 'stock' => 120],
            ['name' => 'Kite', 'price' => 19.99, 'stock' => 85],
            ['name' => 'Jigsaw Puzzle', 'price' => 18.99, 'stock' => 95],
            ['name' => 'Card Game', 'price' => 11.99, 'stock' => 140],
            ['name' => 'Chess Set', 'price' => 29.99, 'stock' => 75],
            ['name' => 'Checkers Set', 'price' => 19.99, 'stock' => 90],
            ['name' => 'Toy Car Collection', 'price' => 44.99, 'stock' => 55],
            ['name' => 'Play-Doh Set', 'price' => 24.99, 'stock' => 80],
        ]
    ],
    'Food & Beverages' => [
        'products' => [
            ['name' => 'Gourmet Coffee Beans', 'price' => 29.99, 'stock' => 75],
            ['name' => 'Organic Tea Set', 'price' => 22.99, 'stock' => 85],
            ['name' => 'Honey Jar', 'price' => 18.99, 'stock' => 70],
            ['name' => 'Olive Oil 500ml', 'price' => 24.99, 'stock' => 90],
            ['name' => 'Spice Set', 'price' => 34.99, 'stock' => 60],
            ['name' => 'Chocolate Gift Box', 'price' => 39.99, 'stock' => 80],
            ['name' => 'Nuts Mix 500g', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Dried Fruits', 'price' => 16.99, 'stock' => 110],
            ['name' => 'Granola Bars Pack', 'price' => 14.99, 'stock' => 120],
            ['name' => 'Protein Powder', 'price' => 49.99, 'stock' => 50],
            ['name' => 'Energy Drink Pack', 'price' => 22.99, 'stock' => 95],
            ['name' => 'Cereal Box', 'price' => 12.99, 'stock' => 140],
            ['name' => 'Rice 5kg', 'price' => 34.99, 'stock' => 80],
            ['name' => 'Pasta Pack', 'price' => 8.99, 'stock' => 180],
            ['name' => 'Cooking Oil 2L', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Sugar 2kg', 'price' => 9.99, 'stock' => 160],
            ['name' => 'Flour 2kg', 'price' => 11.99, 'stock' => 150],
            ['name' => 'Salt 1kg', 'price' => 4.99, 'stock' => 200],
            ['name' => 'Pepper 500g', 'price' => 14.99, 'stock' => 130],
            ['name' => 'Soup Mix', 'price' => 16.99, 'stock' => 120],
        ]
    ],
    'Automotive' => [
        'products' => [
            ['name' => 'Car Phone Mount', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Car Charger', 'price' => 14.99, 'stock' => 120],
            ['name' => 'Car Air Freshener', 'price' => 7.99, 'stock' => 200],
            ['name' => 'Steering Wheel Cover', 'price' => 24.99, 'stock' => 80],
            ['name' => 'Car Floor Mats', 'price' => 49.99, 'stock' => 60],
            ['name' => 'Car Seat Cover', 'price' => 79.99, 'stock' => 45],
            ['name' => 'Jump Starter', 'price' => 149.99, 'stock' => 30],
            ['name' => 'Tire Pressure Gauge', 'price' => 12.99, 'stock' => 140],
            ['name' => 'Car Wash Kit', 'price' => 34.99, 'stock' => 70],
            ['name' => 'Windshield Sunshade', 'price' => 19.99, 'stock' => 95],
            ['name' => 'Car Trunk Organizer', 'price' => 39.99, 'stock' => 55],
            ['name' => 'Car First Aid Kit', 'price' => 29.99, 'stock' => 75],
            ['name' => 'LED Car Lights', 'price' => 44.99, 'stock' => 50],
            ['name' => 'Car Dashboard Camera', 'price' => 89.99, 'stock' => 40],
            ['name' => 'Car Emergency Kit', 'price' => 54.99, 'stock' => 65],
            ['name' => 'Tire Repair Kit', 'price' => 24.99, 'stock' => 85],
            ['name' => 'Car Cleaning Wipes', 'price' => 9.99, 'stock' => 160],
            ['name' => 'Car Organizer Box', 'price' => 19.99, 'stock' => 100],
            ['name' => 'License Plate Frame', 'price' => 14.99, 'stock' => 120],
            ['name' => 'Car Vacuum Cleaner', 'price' => 59.99, 'stock' => 50],
        ]
    ],
    'Kitchen & Dining' => [
        'products' => [
            ['name' => 'Stainless Steel Cookware', 'price' => 149.99, 'stock' => 30],
            ['name' => 'Electric Kettle', 'price' => 39.99, 'stock' => 60],
            ['name' => 'Knife Set', 'price' => 89.99, 'stock' => 40],
            ['name' => 'Cutting Board Set', 'price' => 34.99, 'stock' => 70],
            ['name' => 'Mixing Bowl Set', 'price' => 29.99, 'stock' => 80],
            ['name' => 'Measuring Cups Set', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Spatula Set', 'price' => 24.99, 'stock' => 90],
            ['name' => 'Food Storage Containers', 'price' => 39.99, 'stock' => 65],
            ['name' => 'Water Bottle Set', 'price' => 29.99, 'stock' => 85],
            ['name' => 'Coffee Maker Drip', 'price' => 49.99, 'stock' => 55],
            ['name' => 'Blender', 'price' => 79.99, 'stock' => 45],
            ['name' => 'Toaster', 'price' => 59.99, 'stock' => 50],
            ['name' => 'Microwave Oven', 'price' => 199.99, 'stock' => 20],
            ['name' => 'Rice Cooker', 'price' => 69.99, 'stock' => 40],
            ['name' => 'Pressure Cooker', 'price' => 119.99, 'stock' => 30],
            ['name' => 'Dish Rack', 'price' => 24.99, 'stock' => 75],
            ['name' => 'Kitchen Scale', 'price' => 34.99, 'stock' => 60],
            ['name' => 'Can Opener', 'price' => 12.99, 'stock' => 130],
            ['name' => 'Peeler Set', 'price' => 9.99, 'stock' => 150],
            ['name' => 'Kitchen Timer', 'price' => 14.99, 'stock' => 110],
        ]
    ],
    'Health & Wellness' => [
        'products' => [
            ['name' => 'Digital Thermometer', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Blood Pressure Monitor', 'price' => 79.99, 'stock' => 45],
            ['name' => 'First Aid Kit', 'price' => 34.99, 'stock' => 70],
            ['name' => 'Vitamins Multivitamin', 'price' => 29.99, 'stock' => 90],
            ['name' => 'Massage Oil', 'price' => 24.99, 'stock' => 85],
            ['name' => 'Heating Pad', 'price' => 39.99, 'stock' => 60],
            ['name' => 'Ice Pack', 'price' => 12.99, 'stock' => 140],
            ['name' => 'Pill Organizer', 'price' => 9.99, 'stock' => 160],
            ['name' => 'Weight Scale', 'price' => 44.99, 'stock' => 55],
            ['name' => 'Hand Sanitizer 500ml', 'price' => 14.99, 'stock' => 120],
            ['name' => 'Face Mask Pack 50', 'price' => 19.99, 'stock' => 110],
            ['name' => 'Antiseptic Solution', 'price' => 11.99, 'stock' => 130],
            ['name' => 'Bandage Set', 'price' => 8.99, 'stock' => 180],
            ['name' => 'Gauze Pads', 'price' => 7.99, 'stock' => 190],
            ['name' => 'Pain Relief Cream', 'price' => 16.99, 'stock' => 100],
            ['name' => 'Eye Drops', 'price' => 12.99, 'stock' => 140],
            ['name' => 'Nasal Spray', 'price' => 11.99, 'stock' => 130],
            ['name' => 'Probiotics', 'price' => 39.99, 'stock' => 75],
            ['name' => 'Omega-3 Supplements', 'price' => 34.99, 'stock' => 80],
            ['name' => 'Vitamin D3', 'price' => 19.99, 'stock' => 95],
        ]
    ],
    'Baby & Kids' => [
        'products' => [
            ['name' => 'Baby Stroller', 'price' => 199.99, 'stock' => 25],
            ['name' => 'Baby Car Seat', 'price' => 149.99, 'stock' => 30],
            ['name' => 'Baby Bottle Set', 'price' => 29.99, 'stock' => 80],
            ['name' => 'Diaper Bag', 'price' => 49.99, 'stock' => 55],
            ['name' => 'Baby Monitor', 'price' => 119.99, 'stock' => 40],
            ['name' => 'Baby Crib', 'price' => 299.99, 'stock' => 15],
            ['name' => 'Baby Clothes Set', 'price' => 39.99, 'stock' => 70],
            ['name' => 'Pacifier Set', 'price' => 12.99, 'stock' => 150],
            ['name' => 'Baby Feeding Set', 'price' => 34.99, 'stock' => 65],
            ['name' => 'Baby Toys Set', 'price' => 44.99, 'stock' => 60],
            ['name' => 'Kids Backpack', 'price' => 39.99, 'stock' => 75],
            ['name' => 'Lunch Box', 'price' => 24.99, 'stock' => 90],
            ['name' => 'Water Bottle Kids', 'price' => 16.99, 'stock' => 110],
            ['name' => 'Kids Bike', 'price' => 149.99, 'stock' => 35],
            ['name' => 'Skateboard', 'price' => 79.99, 'stock' => 45],
            ['name' => 'Swimming Pool Inflatable', 'price' => 59.99, 'stock' => 50],
            ['name' => 'Kids Sunglasses', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Safety Helmet', 'price' => 29.99, 'stock' => 80],
            ['name' => 'Baby Carrier', 'price' => 89.99, 'stock' => 40],
            ['name' => 'Kids Umbrella', 'price' => 22.99, 'stock' => 85],
        ]
    ],
    'Pet Supplies' => [
        'products' => [
            ['name' => 'Dog Food 10kg', 'price' => 49.99, 'stock' => 60],
            ['name' => 'Cat Food 5kg', 'price' => 34.99, 'stock' => 75],
            ['name' => 'Dog Leash', 'price' => 19.99, 'stock' => 100],
            ['name' => 'Dog Collar', 'price' => 14.99, 'stock' => 120],
            ['name' => 'Pet Bed', 'price' => 59.99, 'stock' => 50],
            ['name' => 'Pet Food Bowl', 'price' => 12.99, 'stock' => 150],
            ['name' => 'Pet Toys Set', 'price' => 24.99, 'stock' => 90],
            ['name' => 'Pet Grooming Kit', 'price' => 39.99, 'stock' => 70],
            ['name' => 'Cat Litter Box', 'price' => 34.99, 'stock' => 65],
            ['name' => 'Cat Litter 10kg', 'price' => 29.99, 'stock' => 80],
            ['name' => 'Dog Shampoo', 'price' => 16.99, 'stock' => 110],
            ['name' => 'Pet Treats', 'price' => 14.99, 'stock' => 130],
            ['name' => 'Pet Carrier', 'price' => 49.99, 'stock' => 55],
            ['name' => 'Pet Brush', 'price' => 12.99, 'stock' => 140],
            ['name' => 'Pet Nail Clippers', 'price' => 9.99, 'stock' => 160],
            ['name' => 'Dog Training Pads', 'price' => 19.99, 'stock' => 95],
            ['name' => 'Pet Water Fountain', 'price' => 44.99, 'stock' => 60],
            ['name' => 'Pet ID Tag', 'price' => 7.99, 'stock' => 180],
            ['name' => 'Pet Waste Bags', 'price' => 11.99, 'stock' => 150],
            ['name' => 'Pet Blanket', 'price' => 29.99, 'stock' => 85],
        ]
    ],
];

// Function to download placeholder image
function downloadPlaceholderImage($url, $filename) {
    $imageDir = PRODUCT_IMAGE_PATH;
    if (!is_dir($imageDir)) {
        mkdir($imageDir, 0755, true);
    }
    
    $fullPath = $imageDir . $filename;
    
    // Only download once
    if (file_exists($fullPath)) {
        return 'assets/images/products/' . $filename;
    }
    
    $ch = curl_init($url);
    $fp = fopen($fullPath, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    
    return ($httpCode == 200 && file_exists($fullPath)) ? 'assets/images/products/' . $filename : null;
}

// Download placeholder image once
$placeholderPath = downloadPlaceholderImage($placeholderImage, 'placeholder-product.jpg');

// Import categories and products
$totalProducts = 0;
$totalCategories = 0;

foreach ($categoriesData as $categoryName => $categoryInfo) {
    try {
        // Create or get category
        $categorySlug = strtolower(str_replace([' ', '&'], ['-', 'and'], $categoryName));
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND parent_id IS NULL");
        $stmt->execute([$categoryName]);
        $category = $stmt->fetch();
        
        if (!$category) {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, is_active, sort_order) VALUES (?, ?, 1, ?)");
            $stmt->execute([$categoryName, $categorySlug, $totalCategories + 1]);
            $categoryId = $conn->lastInsertId();
            $totalCategories++;
            echo "✓ Created category: $categoryName\n";
        } else {
            $categoryId = $category['id'];
            echo "✓ Category exists: $categoryName\n";
        }
        
        // Import products for this category
        foreach ($categoryInfo['products'] as $product) {
            try {
                // Generate slug
                $slug = strtolower(str_replace(' ', '-', $product['name']));
                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
                
                // Check if product exists
                $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $slug = $slug . '-' . time() . '-' . rand(1000, 9999);
                }
                
                // Use placeholder image for all products
                $images = $placeholderPath ? json_encode([$placeholderPath]) : json_encode([]);
                
                // Insert product
                $stmt = $conn->prepare("
                    INSERT INTO products (
                        name, slug, description, price, 
                        category_id, stock_quantity, low_stock_threshold,
                        images, sku, is_featured, is_active, 
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $description = "High-quality {$product['name']}. Perfect for your needs. Fast delivery across Ghana.";
                $sku = 'SKU-' . strtoupper(substr($slug, 0, 8)) . '-' . rand(1000, 9999);
                $isFeatured = ($totalProducts < 12) ? 1 : 0; // First 12 are featured
                
                $stmt->execute([
                    $product['name'],
                    $slug,
                    $description,
                    $product['price'],
                    $categoryId,
                    $product['stock'],
                    max(10, intval($product['stock'] * 0.2)),
                    $images,
                    $sku,
                    $isFeatured,
                    1
                ]);
                
                $totalProducts++;
                
                if (($totalProducts % 50) == 0) {
                    echo "  Imported $totalProducts products...\n";
                }
                
            } catch (Exception $e) {
                echo "  ✗ Error importing {$product['name']}: " . $e->getMessage() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Error with category $categoryName: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "✅ IMPORT COMPLETE!\n";
echo "═══════════════════════════════════════\n";
echo "Total Categories: $totalCategories\n";
echo "Total Products: $totalProducts\n";
echo "Placeholder Image: $placeholderPath\n";
echo "═══════════════════════════════════════\n";
echo "\n";
echo "Note: All products use the same placeholder image.\n";
echo "You can replace images later in the admin panel.\n";
echo "\n";








