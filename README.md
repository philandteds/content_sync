content_sync
===========

eZ Publish extension for content synchronization between different eZ Publish instances.

Installation
===========

1. Enable extension on source and destination installations
2. Setup content sync workflow event and assign it to content_publish_post, content_addlocation and content_removelocation triggers
3. Run sql/mysql/schema.sql on source and destination installations
4. Create files/images/products and files/images/product_categories folders on destination installation
5. Done