<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__.'/../../../vendor/autoload.php';

use Core\Connectors\ConnectorAppService;
use Core\Connectors\ConnectorConnectionLifecycleService;
use Core\Connectors\ConnectorConnectionService;
use Core\Connectors\ConnectorDriverManager;
use Core\Connectors\Fields\ConnectorFields;
use Core\Connectors\PlatformCatalogService;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Modules\SocialMedia\Http\Controllers\AccountController;
use Modules\SocialMedia\Services\PublishingConnectorService;

function assert_social_media_true(bool $condition, string $message): void
{
    if (! $condition) {
        throw new Exception($message);
    }
}

function expect_social_media_failure(callable $callback, string $message_fragment): Throwable
{
    try {
        $callback();
        throw new Exception('Expected SocialMedia connector operation to fail.');
    } catch (Throwable $throwable) {
        $failure_text = $throwable instanceof ValidationException
            ? json_encode($throwable->errors())
            : $throwable->getMessage();

        assert_social_media_true(
            str_contains((string) $failure_text, $message_fragment),
            "Expected failure containing '{$message_fragment}', received '{$failure_text}'.",
        );

        return $throwable;
    }
}

function social_media_request(string $method, array $payload, string $user_id): Request
{
    $request = Request::create('/social-media/webapi/account/connector', $method, $payload);
    $request->setUserResolver(fn () => (object) ['id' => $user_id]);

    return $request;
}

function insert_social_media_app(string $id, bool $enabled = true, string $status = 'verified'): void
{
    DB::table('core.connector_apps')->insert([
        'id' => $id,
        'platform_key' => 'linkedin',
        'driver_key' => 'oauth2',
        'connection_type' => 'oauth2_authorization_code',
        'name' => $id,
        'enabled' => $enabled,
        'config' => json_encode([
            'client_id' => 'linkedin-client',
            'redirect_uri' => 'https://tenant.example/linkedin/callback',
            'scopes' => ['openid', 'profile', 'email', 'w_member_social'],
        ]),
        'secrets_ciphertext' => encrypt(json_encode(['client_secret' => 'linkedin-secret'])),
        'capabilities' => json_encode(['account.identity', 'post.publish']),
        'profile_metadata' => '{}',
        'status' => $status,
        'updated_at' => now(),
    ]);
}

function insert_social_media_connection(
    string $id,
    string $user_id,
    string $app_id = 'linkedin-verified',
    array $capabilities = ['account.identity', 'post.publish'],
    string $state = 'connected',
): void {
    DB::table('core.connector_connections')->insert([
        'id' => $id,
        'user_id' => $user_id,
        'connector_app_id' => $app_id,
        'platform_key' => 'linkedin',
        'driver_key' => 'oauth2',
        'connection_type' => 'oauth2_authorization_code',
        'name' => $id,
        'state' => $state,
        'current_step' => $state === 'connected' ? 'connected' : 'authorize',
        'capabilities' => json_encode($capabilities),
        'external_account_id' => "member-{$id}",
        'external_account_label' => "Member {$id}",
        'profile_metadata' => '{}',
        'tokens_ciphertext' => encrypt(json_encode([
            'access_token' => "token-{$id}",
            'scopes' => ['openid', 'profile', 'email', 'w_member_social'],
        ])),
        'token_expires_at' => now()->addHour(),
        'last_connected_at' => now(),
        'updated_at' => now(),
    ]);
}

try {
    $container = new Container;
    Container::setInstance($container);
    Facade::setFacadeApplication($container);

    $capsule = new Capsule($container);
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    $container->instance('db', $capsule->getDatabaseManager());
    $container->instance('encrypter', new Encrypter(random_bytes(32), 'AES-256-CBC'));
    $container->instance('validator', new ValidationFactory(new Translator(new ArrayLoader, 'en'), $container));

    DB::statement("ATTACH DATABASE 'file:social_media_connector_core?mode=memory&cache=shared' AS core");
    DB::statement("ATTACH DATABASE 'file:social_media_connector_module?mode=memory&cache=shared' AS social_media");
    DB::statement('
        CREATE TABLE core.connector_apps (
            id TEXT PRIMARY KEY,
            platform_key TEXT NOT NULL,
            driver_key TEXT NOT NULL,
            connection_type TEXT NOT NULL,
            name TEXT NOT NULL,
            enabled INTEGER NOT NULL DEFAULT 1,
            config TEXT NOT NULL DEFAULT \'{}\',
            secrets_ciphertext TEXT NOT NULL DEFAULT \'\',
            capabilities TEXT NOT NULL DEFAULT \'[]\',
            external_account_id TEXT,
            external_account_label TEXT,
            external_account_url TEXT,
            profile_metadata TEXT NOT NULL DEFAULT \'{}\',
            status TEXT NOT NULL DEFAULT \'unverified\',
            status_message TEXT,
            verified_at TEXT,
            token_expires_at TEXT,
            last_connected_at TEXT,
            last_refresh_at TEXT,
            revoked_at TEXT,
            last_error_at TEXT,
            last_error_message TEXT,
            updated_at TEXT
        )
    ');
    DB::statement('
        CREATE TABLE core.connector_connections (
            id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL,
            connector_app_id TEXT NOT NULL,
            platform_key TEXT NOT NULL,
            driver_key TEXT NOT NULL,
            connection_type TEXT NOT NULL,
            name TEXT NOT NULL,
            state TEXT NOT NULL DEFAULT \'draft\',
            current_step TEXT NOT NULL DEFAULT \'select_app\',
            capabilities TEXT NOT NULL DEFAULT \'[]\',
            external_account_id TEXT,
            external_account_label TEXT,
            external_account_url TEXT,
            profile_metadata TEXT NOT NULL DEFAULT \'{}\',
            tokens_ciphertext TEXT NOT NULL DEFAULT \'\',
            token_expires_at TEXT,
            last_connected_at TEXT,
            last_refresh_at TEXT,
            revoked_at TEXT,
            last_error_at TEXT,
            last_error_message TEXT,
            updated_at TEXT
        )
    ');
    DB::statement('
        CREATE TABLE social_media.user_connector_settings (
            id TEXT PRIMARY KEY DEFAULT (lower(hex(randomblob(16)))),
            user_id TEXT NOT NULL UNIQUE,
            publishing_connection_id TEXT NOT NULL,
            created_at TEXT,
            updated_at TEXT
        )
    ');

    insert_social_media_app('linkedin-verified');
    insert_social_media_app('linkedin-disabled', false);
    insert_social_media_app('linkedin-unverified', true, 'unverified');
    insert_social_media_connection('user-a-publishing', 'user-a');
    insert_social_media_connection('user-a-identity-only', 'user-a', 'linkedin-verified', ['account.identity']);
    insert_social_media_connection('user-a-disabled-app', 'user-a', 'linkedin-disabled');
    insert_social_media_connection('user-a-unverified-app', 'user-a', 'linkedin-unverified');
    insert_social_media_connection('user-a-revoked', 'user-a', 'linkedin-verified', ['post.publish'], 'revoked');
    insert_social_media_connection('user-b-publishing', 'user-b');

    $catalog = new PlatformCatalogService;
    $driver_manager = new ConnectorDriverManager;
    $app_service = new ConnectorAppService($catalog, $driver_manager);
    $connection_service = new ConnectorConnectionService($catalog, $app_service);
    $fields = new ConnectorFields($catalog, $connection_service);
    $lifecycle = new ConnectorConnectionLifecycleService(
        $catalog,
        $driver_manager,
        $app_service,
        $connection_service,
    );
    $publishing = new PublishingConnectorService($lifecycle);
    $controller = new AccountController($fields, $publishing);

    $definition = PublishingConnectorService::field_definition();
    assert_social_media_true(
        $definition['connector']['capabilities'] === ['post.publish'],
        'SocialMedia must request only the future publishing capability.',
    );
    assert_social_media_true(
        $definition['connector']['platform_keys'] === ['linkedin'],
        'The first SocialMedia consumer must be constrained to LinkedIn.',
    );

    $initial = json_decode((string) $controller->show('tenant', social_media_request('GET', [], 'user-a'))->getContent(), true);
    assert_social_media_true(
        array_column($initial['catalog']['connections'], 'id') === ['user-a-publishing'],
        'Eligible options must include only connected, capable, verified connections owned by the current user.',
    );
    assert_social_media_true(
        $initial['data']['publishing_connection'] === null,
        'A user without a saved reference should reopen with an empty selection.',
    );

    expect_social_media_failure(
        fn () => $fields->parse_or_fail(
            ['connection_id' => 'user-b-publishing'],
            $definition,
            'user-a',
        ),
        'was not found for this user',
    );

    $request = social_media_request('PUT', [
        'publishing_connection' => ['connection_id' => 'user-a-publishing'],
    ], 'user-a');
    $field = $fields->parse_or_fail($request->input('publishing_connection'), $definition, 'user-a');
    $fields->attach_to_request($request, ['publishing_connection' => $field]);
    $request->merge(['publishing_connection' => $field->connection_id()]);
    $saved = json_decode((string) $controller->update('tenant', $request)->getContent(), true);

    assert_social_media_true(
        $saved['data']['publishing_connection']['connection_id'] === 'user-a-publishing',
        'The saved response must expose the opaque connector reference.',
    );
    $stored = DB::table('social_media.user_connector_settings')->where('user_id', 'user-a')->first();
    assert_social_media_true(
        (string) $stored->publishing_connection_id === 'user-a-publishing',
        'Persistence must store only the connector connection id.',
    );
    assert_social_media_true(
        ! str_contains(json_encode($stored), 'token-user-a-publishing'),
        'SocialMedia storage must never contain connector credentials.',
    );

    $reopened = json_decode((string) $controller->show('tenant', social_media_request('GET', [], 'user-a'))->getContent(), true);
    assert_social_media_true(
        $reopened['data']['publishing_connection']['connection_id'] === 'user-a-publishing',
        'Reopening the account page must restore the saved connector reference.',
    );

    $runtime = $publishing->resolve_for_user_or_fail('user-a');
    assert_social_media_true(
        $runtime['connection']['id'] === 'user-a-publishing'
        && $runtime['tokens']['access_token'] === 'token-user-a-publishing',
        'Runtime resolution must delegate the stored reference to Core lifecycle services.',
    );

    $unexpected = social_media_request('PUT', [
        'publishing_connection' => 'user-a-publishing',
        'tokens' => 'caller-supplied-secret',
    ], 'user-a');
    $fields->attach_to_request($unexpected, ['publishing_connection' => $field]);
    expect_social_media_failure(
        fn () => $controller->update('tenant', $unexpected),
        "Unexpected field 'tokens'",
    );

    echo "SocialMedia connector consumer tests passed.\n";
} catch (Throwable $throwable) {
    fwrite(STDERR, "SocialMedia connector consumer tests failed: {$throwable->getMessage()}\n");
    exit(1);
}
