SELECT fn_create_table('social_media', 'user_connector_settings');

SELECT fn_add_foreign_column('social_media', 'user_connector_settings', 'user_id', 'core', 'users', 'id');
SELECT fn_add_foreign_column('social_media', 'user_connector_settings', 'publishing_connection_id', 'core', 'connector_connections', 'id');

CREATE UNIQUE INDEX IF NOT EXISTS user_connector_settings_user_uix
    ON social_media.user_connector_settings (user_id);

SELECT fn_add_created_columns('social_media', 'user_connector_settings');
SELECT fn_add_updated_columns('social_media', 'user_connector_settings');
