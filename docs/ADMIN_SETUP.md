# Admin Panel Setup and Usage Guide

## Initial Setup

### 1. Database Configuration
1. Create a MySQL database for your restaurant menu system
2. Edit `config/database.php` with your database credentials:
   ```php
   return [
       'host' => 'localhost',
       'dbname' => 'your_database_name',
       'username' => 'your_username',
       'password' => 'your_password'
   ];
   ```

### 2. Database Installation
1. Navigate to `/database/setup.php` in your browser
2. This will create all necessary tables and insert sample data
3. Default admin credentials will be created:
   - **Username**: `admin`
   - **Password**: `admin123`
   - **Important**: Change these credentials immediately after first login

### 3. Admin Panel Access
1. Navigate to `/admin/` on your website
2. Log in with the default credentials
3. Change your password in the admin panel

## Admin Panel Features

### Dashboard (`/admin/`)
- Overview of total menus, sections, and items
- Quick navigation to all management areas
- System status information

### Menu Management (`/admin/menus.php`)
- Create new menus (e.g., "Lunch Menu", "Dinner Menu")
- Edit menu names and descriptions
- Set menu display order
- Enable/disable menus

### Section Management (`/admin/sections.php`)
- **Add New Sections**: Create new menu sections with the form at the bottom
- **Inline Editing**: Click on any section name, description, or menu to edit
- **Visibility Control**: Show/hide sections from customer view
- **Menu Assignment**: Move sections between different menus

### Item Management (`/admin/items.php`)
- Add new menu items with full details
- Edit item names, descriptions, and prices
- Assign dietary icons (gluten-free, vegan, etc.)
- Set item availability
- Upload item photos (if enabled)

### Import Data (`/admin/import.php`)
- Bulk import menu items via CSV file
- Download CSV template for proper formatting
- Useful for migrating from existing menu systems

### User Management (`/admin/change_password.php`)
- Change admin password
- Secure password requirements
- Session management

## Dietary Icons System

### Available Icons
- **🌾 Gluten-Free**: For items without gluten
- **🌱 Vegan**: For plant-based items
- **🥬 Vegetarian**: For vegetarian items
- **🌶️ Spicy**: For spicy items
- **🧂 Low Sodium**: For low-sodium options
- **🥛 Dairy-Free**: For dairy-free items

### Adding Icons to Items
1. Go to Items Management
2. Edit or create an item
3. Select appropriate dietary icons
4. Icons will automatically appear on the customer-facing menu

## Best Practices

### Menu Organization
1. **Create logical menus**: Separate lunch, dinner, drinks, etc.
2. **Use sections wisely**: Group similar items (appetizers, mains, desserts)
3. **Set display orders**: Control how items appear to customers
4. **Use descriptions**: Help customers understand dishes

### Content Management
1. **Keep descriptions concise** but informative
2. **Update prices regularly** to reflect current costs
3. **Use dietary icons consistently** across all items
4. **Hide items** when temporarily unavailable instead of deleting

### Security
1. **Change default password** immediately
2. **Use strong passwords** with mixed characters
3. **Log out** when finished managing
4. **Regular backups** of your database

## Troubleshooting

### Common Issues
1. **Can't log in**: Check database connection and credentials
2. **Items not showing**: Verify item and section are enabled
3. **Icons not displaying**: Check database icon assignments
4. **Import fails**: Verify CSV format matches template

### Database Issues
- Check `config/database.php` settings
- Ensure database user has proper permissions
- Verify MySQL service is running

### Getting Help
- Check error messages in the admin panel
- Review database setup scripts if tables are missing
- Ensure PHP version is 7.4 or higher
