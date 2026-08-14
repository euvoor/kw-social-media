import assert from "node:assert/strict"
import { readFileSync } from "node:fs"
import test from "node:test"

const read = relative => readFileSync(new URL(`../${relative}`, import.meta.url), "utf8")

test("SocialMedia declares a connector-backed account page", () => {
  const schema = read("config/schema.php")
  const routes = read("routes/webapi.php")

  assert.match(schema, /"name"\s*=>\s*"Account"/)
  assert.match(schema, /"id"\s*=>\s*"Account"/)
  assert.match(routes, /prefix\("\/account"\)/)
  assert.match(routes, /kwerio\.connector_fields/)
  assert.match(routes, /PublishingConnectorService::FIELD_DEFINITION/)

  const runtime = read("Services/PublishingConnectorService.php")
  assert.match(runtime, /'capabilities'\s*=>\s*\[\s*'post\.publish'\s*\]/)
  assert.match(runtime, /'platform_keys'\s*=>\s*\[\s*'linkedin'\s*\]/)
})

test("account UI hydrates and persists the shared ConnectorField value", () => {
  const entry = read("resources/js/index.jsx")
  const pages = read("resources/js/pages/index.js")
  const account = read("resources/js/pages/Account/index.jsx")
  const service = read("resources/js/services/account.js")

  assert.match(entry, /accountApi\.reducer/)
  assert.match(entry, /accountApi\.middleware/)
  assert.match(pages, /"Account"/)
  assert.match(account, /import ConnectorField from "~\/components\/ConnectorField"/)
  assert.match(account, /publishing_connection/)
  assert.match(account, /data-testid="social-media-save-connection"/)
  assert.match(service, /method:\s*"GET"/)
  assert.match(service, /method:\s*"PUT"/)
})

test("module contains no provider client, publishing, or analytics behavior", () => {
  const controller = read("Http/Controllers/AccountController.php")
  const runtime = read("Services/PublishingConnectorService.php")

  for (const source of [controller, runtime]) {
    assert.doesNotMatch(source, /api\.linkedin\.com|Http::|accessToken|ugcPosts|\/rest\/posts/)
    assert.doesNotMatch(source, /function\s+(publish|schedule|analytics)\b/i)
  }

  assert.match(runtime, /resolve_runtime_connection_for_user_or_fail/)
})
