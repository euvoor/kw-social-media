<?php

namespace Modules\SocialMedia\Http\Controllers;

use Core\Connectors\Fields\ConnectorFields;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\SocialMedia\Services\PublishingConnectorService;

final class AccountController
{
    public function __construct(
        private readonly ConnectorFields $connector_fields,
        private readonly PublishingConnectorService $publishing_connector_service,
    ) {}

    /**
     * Show the current user's saved reference and eligible connector choices.
     */
    public function show(string $_subdomain, Request $request): JsonResponse
    {
        return new JsonResponse($this->_payload_for_user(
            $this->_authenticated_user_id_or_fail($request),
        ));
    }

    /**
     * Persist the connector reference already normalized by Core connector middleware.
     */
    public function update(string $_subdomain, Request $request): JsonResponse
    {
        $this->_ensure_only_publishing_connection_or_fail($request);
        $user_id = $this->_authenticated_user_id_or_fail($request);
        $field = $this->connector_fields->from_request_or_fail(
            $request,
            PublishingConnectorService::FIELD_NAME,
        );

        DB::table('social_media.user_connector_settings')->updateOrInsert(
            ['user_id' => $user_id],
            ['publishing_connection_id' => $field->connection_id()],
        );

        return new JsonResponse($this->_payload_for_user($user_id));
    }

    private function _payload_for_user(string $user_id): array
    {
        $catalog = $this->connector_fields->frontend_payload_for_field_or_fail(
            PublishingConnectorService::FIELD_DEFINITION,
            $user_id,
        );
        $saved_connection_id = $this->publishing_connector_service->saved_connection_id_for_user($user_id);
        $eligible_ids = array_column($catalog['connections'], 'id');
        $selected = $saved_connection_id && in_array($saved_connection_id, $eligible_ids, true)
            ? ['connection_id' => $saved_connection_id]
            : null;

        return [
            'data' => [
                PublishingConnectorService::FIELD_NAME => $selected,
            ],
            'catalog' => $catalog,
        ];
    }

    private function _ensure_only_publishing_connection_or_fail(Request $request): void
    {
        foreach (array_keys($request->all()) as $field) {
            if ($field === PublishingConnectorService::FIELD_NAME) {
                continue;
            }

            $field = is_string($field) ? $field : (string) $field;

            throw ValidationException::withMessages([
                PublishingConnectorService::FIELD_NAME => ["Unexpected field '{$field}'."],
            ]);
        }
    }

    private function _authenticated_user_id_or_fail(Request $request): string
    {
        $user_id = trim((string) ($request->user()?->id ?? ''));

        if ($user_id === '') {
            throw new AuthenticationException('Authentication required.');
        }

        return $user_id;
    }
}
