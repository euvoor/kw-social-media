<?php

namespace Modules\SocialMedia\Services;

use Core\Connectors\ConnectorConnectionLifecycleService;
use Core\Connectors\Exceptions\ConnectorConnectionValidationException;
use Illuminate\Support\Facades\DB;

final class PublishingConnectorService
{
    public const FIELD_NAME = 'publishing_connection';

    public const FIELD_DEFINITION = [
        'name' => self::FIELD_NAME,
        'type' => 'connector',
        'required' => true,
        'connector' => [
            'capabilities' => ['post.publish'],
            'platform_keys' => ['linkedin'],
            'allow_unverified_app' => false,
        ],
    ];

    public function __construct(
        private readonly ConnectorConnectionLifecycleService $connector_connection_lifecycle_service,
    ) {}

    public static function field_definition(): array
    {
        return self::FIELD_DEFINITION;
    }

    /**
     * Return the opaque saved reference without resolving or exposing credentials.
     */
    public function saved_connection_id_for_user(string $user_id): ?string
    {
        $user_id = $this->_required_id($user_id, 'user_id');
        $row = DB::table('social_media.user_connector_settings')
            ->where('user_id', $user_id)
            ->first();

        if (! $row) {
            return null;
        }

        $connection_id = trim((string) $row->publishing_connection_id);

        return $connection_id === '' ? null : $connection_id;
    }

    /**
     * Resolve the selected connector through Core for backend-only future publishing use.
     */
    public function resolve_for_user_or_fail(string $user_id): array
    {
        $user_id = $this->_required_id($user_id, 'user_id');
        $connection_id = $this->saved_connection_id_for_user($user_id);

        if (! $connection_id) {
            throw new ConnectorConnectionValidationException('A SocialMedia publishing connection is not configured.');
        }

        return $this->connector_connection_lifecycle_service->resolve_runtime_connection_for_user_or_fail(
            $user_id,
            $connection_id,
            self::FIELD_DEFINITION['connector']['capabilities'],
            self::FIELD_DEFINITION['connector']['platform_keys'],
            self::FIELD_DEFINITION['connector']['allow_unverified_app'],
        );
    }

    private function _required_id(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new ConnectorConnectionValidationException("{$label} is required.");
        }

        return $value;
    }
}
