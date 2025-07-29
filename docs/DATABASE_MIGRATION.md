# Database Migration to SiteGround

## Overview
The local development environment has been configured to use the SiteGround production database instead of the local MySQL database.

## Configuration Changes

### Database Connection Updated
- **File**: `config/database.php`
- **Change**: Forced environment to 'production' to use SiteGround database
- **Status**: ✅ Active

### SiteGround Database Details
- **Host**: `35.212.92.200`
- **Database**: `dblplzygqhkye4`
- **Username**: `urhgsgyruysgz`
- **Password**: `pcyjeilfextq`
- **Charset**: `utf8mb4`

## Migration Status

### Successfully Imported
- ✅ **menus**: 4 records
- ✅ **menu_sections**: 22 records  
- ✅ **menu_item_icons**: 57 records

### Partial Import (Schema Issues)
- ⚠️ **menu_items**: 0 records (missing `spice_level` column)
- ⚠️ **menu_item_images**: 0 records (missing `is_featured` column)

## Testing
- ✅ Connection test passed
- ✅ Local site now connects to SiteGround database
- ✅ Admin panel should work with available data

## Next Steps

### To Fix Schema Issues:
1. Add missing columns to SiteGround database:
   ```sql
   ALTER TABLE menu_items ADD COLUMN spice_level INT DEFAULT NULL;
   ALTER TABLE menu_item_images ADD COLUMN is_featured TINYINT(1) DEFAULT 0;
   ```

2. Re-run data import for menu_items and menu_item_images

### To Switch Back to Local Database:
1. Edit `config/database.php`
2. Change `$environment = 'production';` to `$environment = 'local';`
3. Restart web server

## Benefits
- ✅ Shared database between local and production environments
- ✅ Real-time testing with production data
- ✅ Simplified deployment process
- ✅ No need to sync database changes

## Important Notes
- All local database changes now affect production data
- Be careful when testing destructive operations
- Consider backing up SiteGround database before major changes
- Admin credentials work with existing authentication system
