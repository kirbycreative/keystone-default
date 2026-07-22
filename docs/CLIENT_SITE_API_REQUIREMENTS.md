# Keystone Client Site API Requirements

This document is the implementation requirement and source of truth for server-to-server
communication between a provisioned Keystone Client installation and `kirbycreative.co`. It is
written so the Kirby Creative API can be implemented without inspecting client UI code. Client
installations must not contain OpenRouter credentials or call an AI provider directly.

## Required outcome

When every requirement in this file is implemented:

1. Kirby Creative creates the customer, user, site, and scoped API token before provisioning.
2. The provisioned client uses only that token for Kirby Creative services and sends no identity IDs.
3. Draft onboarding steps and the initial business-material collection happen before generation starts.
4. The final onboarding submission creates one idempotent Kirby Creative generation job.
5. The style guide is generated and approved before page-tree generation begins.
6. The page tree is generated and approved before the Content workspace unlocks.
7. The dashboard exposes only the current review stage and polls normalized job status.
8. AI feedback returns to Kirby Creative, which owns model selection and quality history.

## Ownership

Keystone Client owns:

- the onboarding and content-upload user interfaces;
- local draft/progress persistence;
- validating user-entered data before transmission;
- presenting Kirby Creative job status and results;
- retrying an idempotent request after a transport failure.

Kirby Creative owns:

- API authentication and resolving the requested site within the authenticated user's memberships;
- AI prompts, models, provider credentials, retries, and model feedback;
- validating the API payload independently of client validation;
- inspiration-site research, style-guide generation, and site-layout generation;
- private processing artifacts and the canonical generated result;
- asynchronous job orchestration and status.

The customer journey creates the canonical Kirby Creative `User` and `Site` before provisioning
starts. Kirby Creative therefore already knows both IDs. The bearer token authenticates that user,
and the required `X-Keystone-Site-Url` header selects the site when the user owns more than one. No
client-to-Kirby-Creative API payload sends a user, site, or customer ID. Kirby Creative resolves the
site by matching the normalized URL within the authenticated user's site memberships.

## Transport contract

| Setting | Contract |
| --- | --- |
| Base URL | `KEYSTONE_API_URL=https://kirbycreative.co/api` |
| Authentication | `Authorization: Bearer <KEYSTONE_API_TOKEN>` |
| Site selection | `X-Keystone-Site-Url: <canonical APP_URL>` on every request |
| Request/response type | JSON unless an endpoint explicitly accepts a file |
| Required headers | `Accept: application/json`, `Content-Type: application/json` |
| Mutating job header | `Idempotency-Key: <stable UUID>` |
| Token storage | Server-side environment only; never rendered into HTML or JavaScript |
| Timeout | 30 seconds for normal calls; asynchronous work returns `202` rather than holding the request open |

The provisioner must place `KEYSTONE_API_URL` and `KEYSTONE_API_TOKEN` into the client environment
and set `APP_URL` to the client's canonical website origin. The token should have only the abilities
required by these endpoints and authenticate the pre-existing Kirby Creative user.

### Site URL rules

`X-Keystone-Site-Url` is required on every authenticated client request. Its value comes from the
deployed client's `APP_URL` and must be an absolute `http` or `https` URL without a query or
fragment. The client removes a trailing slash before sending it.

Kirby Creative must normalize the received origin by lowercasing the host, applying IDN handling,
removing the default port, and ignoring a trailing slash. It then finds a `Site` with that canonical
primary domain among the authenticated user's memberships. Missing, malformed, unknown, or
unauthorized site URLs fail closed with `422` or `404`; Kirby Creative must never fall back to the
user's first site.

### Standard errors

Kirby Creative should return errors in this shape:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The request could not be accepted.",
    "request_id": "01J...",
    "details": {
      "business_category": ["The business category field is required."]
    }
  }
}
```

Expected status codes:

| Status | Meaning |
| --- | --- |
| `200` | Synchronous request completed |
| `201` | Resource created |
| `202` | Asynchronous job accepted |
| `401` | Token absent or invalid |
| `403` | Token valid but lacks the required ability or site access |
| `404` | Resource/action does not exist for the authenticated site |
| `409` | Conflicting state or idempotency key reused with a different payload |
| `422` | Payload validation failed |
| `429` | Rate limit reached; include `Retry-After` |
| `502` or `503` | Kirby Creative cannot currently complete an upstream/provider operation |

The client must not translate an API failure into a valid empty result.

## Call summary

| Call | Timing | Mode | Current state |
| --- | --- | --- | --- |
| `POST /ai/top_sites` | Opening onboarding inspiration step | Synchronous | Client implemented; Kirby Creative action must exist |
| `POST /onboarding/completions` | Final onboarding submission only | Asynchronous | Required on both sides |
| `GET /onboarding/completions/{id}` | While generation is active and when showing results | Polling | Required on both sides |
| `POST /onboarding/completions/{id}/style-guide-decision` | Style-guide concept review | Synchronous | Required on both sides |
| `POST /onboarding/completions/{id}/page-tree-decision` | Page-tree concept review | Synchronous | Required on both sides |
| `POST /assets` | After a private business asset is uploaded | Asynchronous ingestion | Required on both sides |
| `GET /assets/{id}` | While an asset is being ingested | Polling | Required on both sides |
| `POST /site-layouts` | After the user selects processed assets for a revised layout | Asynchronous | Required on both sides |
| `GET /site-layouts/{id}` | While layout generation is active and when showing results | Polling | Required on both sides |
| `POST /ai-feedback` | User accepts/rejects an AI-produced suggestion | Synchronous | Required on both sides |

## 1. Inspiration-site suggestions

`POST /ai/top_sites`

This uses Kirby Creative's existing authenticated AI-action route. The active action path in Kirby
Creative must be exactly `top_sites`. Kirby Creative owns its prompt and output validation.

Request:

```json
{
  "business_category": "Specialty coffee shop",
  "primary_location": "Austin, TX",
  "audience_reach": "regional",
  "limit": 5
}
```

Validation:

- `business_category`: required string, maximum 255 characters;
- `primary_location`: required string, maximum 255 characters;
- `audience_reach`: `regional` or `national`;
- `limit`: integer from 1 through 5.

Successful response:

```json
{
  "action": "top_sites",
  "result": {
    "sites": [
      {
        "name": "Example Coffee",
        "domain": "example.com",
        "reason": "Strong product storytelling and a clear local conversion path."
      }
    ]
  }
}
```

`domain` must be a bare hostname without a protocol or path. A successful empty result is
`{"sites":[]}`. Provider/configuration failures must use an error status instead.

## 2. Final onboarding submission and generation

`POST /onboarding/completions`

This call happens only when the user submits the final onboarding step. Saving the company,
branding, customer, or inspiration steps must only persist draft progress locally.

Required header:

```text
Idempotency-Key: <submission_id>
```

Request:

```json
{
  "schema_version": 1,
  "submission_id": "63fc9beb-09e6-4b84-a5d5-6c38db0e58ca",
  "company": {
    "name": "Northstar Coffee",
    "description": "Small-batch coffee with neighborhood hospitality.",
    "business_category": "Specialty coffee shop",
    "primary_location": "Austin, TX"
  },
  "brand": {
    "personality_voice": "Warm, confident, and refined.",
    "styles_to_avoid": "Corporate language and generic stock photography.",
    "existing_assets": "Use the licensed Sora font and packaging photography.",
    "slogans": "Better mornings start here.",
    "logo_url": "https://signed-or-public.example/logo.svg",
    "colors": {
      "primary": "#123456",
      "secondary": "#abcdef",
      "palette": ["#123456", "#abcdef", "#f4e8d0"]
    }
  },
  "audience": {
    "ideal_customer": "Local professionals who care about quality and craft.",
    "reach": "regional"
  },
  "inspiration": {
    "selected_domains": ["example.com"],
    "suggested_sites_shown": [
      {
        "name": "Example Coffee",
        "domain": "example.com",
        "reason": "Strong product storytelling and a clear local conversion path."
      }
    ]
  },
  "materials": {
    "asset_ids": ["01JASSET1", "01JASSET2"]
  }
}
```

Every material must already belong to the site resolved from `X-Keystone-Site-Url`. Kirby Creative
persists an immutable input snapshot and enqueues one orchestration job. Style-guide generation runs
first and then pauses at `style_guide_review`. Page-tree generation cannot begin until the style
guide is approved. Content cannot unlock until the generated page tree is approved.

Accepted response (`202`):

```json
{
  "submission": {
    "id": "01J...",
    "submission_id": "63fc9beb-09e6-4b84-a5d5-6c38db0e58ca",
    "status": "queued",
    "style_guide_status": "queued",
    "site_layout_status": "waiting",
    "submitted_at": "2026-07-22T18:00:00Z",
    "status_url": "https://kirbycreative.co/api/onboarding/completions/01J..."
  }
}
```

Reusing the same idempotency key with the same payload must return the original submission.
Reusing it with a different payload must return `409`.

## 3. Onboarding-generation status and result

`GET /onboarding/completions/{id}`

The authenticated token may read only submissions belonging to its resolved site.

Active response:

```json
{
  "submission": {
    "id": "01J...",
    "status": "processing",
    "stage": "style_guide_review",
    "progress": 55,
    "style_guide_status": "completed",
    "site_layout_status": "processing",
    "error": null,
    "result": null
  }
}
```

Completed response:

```json
{
  "submission": {
    "id": "01J...",
    "status": "completed",
    "progress": 100,
    "style_guide_status": "completed",
    "site_layout_status": "completed",
    "error": null,
    "result": {
      "style_guide": {
        "summary": "A warm, refined neighborhood coffee brand.",
        "variables": {
          "root": {},
          "dark": {}
        },
        "typography": {},
        "imagery": {},
        "voice": {},
        "usage": {
          "do": [],
          "avoid": []
        }
      },
      "site_layout": {
        "pages": [
          {
            "key": "home",
            "title": "Home",
            "slug": "/",
            "goal": "Introduce the brand and drive an in-store visit.",
            "sections": [
              {
                "key": "hero",
                "type": "hero",
                "purpose": "Lead with the primary value proposition.",
                "content_requirements": ["headline", "supporting copy", "primary action"]
              }
            ]
          }
        ]
      }
    }
  }
}
```

`variables` must match Kirby Creative's canonical `StyleGuideVariableContract`; the client must not
maintain a duplicate list of allowed variables. Terminal statuses are `completed` and `failed`.
Active statuses are `queued` and `processing`.

Generation stages are sequential:

1. `style_guide_generation`
2. `style_guide_review`
3. `page_tree_generation`
4. `page_tree_review`
5. `content_ready`
6. `site_build`
7. `completed`

The API must reject attempts to skip a review stage with `409`.

Failed response remains a `200` because the status resource was retrieved successfully:

```json
{
  "submission": {
    "id": "01J...",
    "status": "failed",
    "progress": 70,
    "style_guide_status": "completed",
    "site_layout_status": "failed",
    "error": {
      "code": "site_layout_generation_failed",
      "message": "Site layout generation could not be completed."
    },
    "result": null
  }
}
```

## 4. Sequential concept decisions

`POST /onboarding/completions/{id}/style-guide-decision`

`POST /onboarding/completions/{id}/page-tree-decision`

Request:

```json
{
  "decision": "approve",
  "feedback": null
}
```

`decision` is `approve` or `deny`. Feedback is required when denying and is limited to 2000
characters. Approving the style guide queues page-tree generation. Approving the page tree changes
the stage to `content_ready`. Denial keeps the same review gate active and queues a revised concept
using the feedback. The authenticated user and `X-Keystone-Site-Url` must own the submission.

Successful response:

```json
{
  "submission": {
    "id": "01J...",
    "status": "processing",
    "stage": "page_tree_generation"
  }
}
```

## 5. Private asset ingestion

`POST /assets`

The client sends the selected file to Kirby Creative as `multipart/form-data`. It must not expose a
permanent public URL for a private business document.

Fields:

| Field | Type | Rules |
| --- | --- | --- |
| `client_asset_id` | integer | Required local correlation value; never used for tenancy |
| `title` | string | Optional, maximum 255 characters |
| `type` | string | `menu`, `promotion`, `advertisement`, `brand`, `photo`, `document`, or `other` |
| `notes` | string | Optional, maximum 2000 characters |
| `file` | file | Required, maximum 25 MB; use the client allow-list |

Accepted response (`202`):

```json
{
  "asset": {
    "id": "01J...",
    "client_asset_id": 42,
    "status": "queued",
    "progress": 0,
    "status_url": "https://kirbycreative.co/api/assets/01J..."
  }
}
```

Kirby Creative owns private storage, extraction, and AI ingestion after accepting the upload.

## 6. Asset-ingestion status

`GET /assets/{id}`

Completed response:

```json
{
  "asset": {
    "id": "01J...",
    "client_asset_id": 42,
    "status": "completed",
    "progress": 100,
    "result": {
      "document_type": "menu",
      "summary": "Seasonal drinks and food offerings.",
      "facts": [],
      "content": {}
    },
    "error": null
  }
}
```

The client stores the remote `id`, status, and returned normalized result. It does not store or
expose Kirby Creative's internal provider response.

## 7. Revised site layout from reviewed assets

`POST /site-layouts`

This is separate from initial onboarding generation. It runs only after the user reviews uploaded
assets and explicitly asks to generate or refresh the site layout.

Required header:

```text
Idempotency-Key: <stable layout request UUID>
```

Request:

```json
{
  "schema_version": 1,
  "request_id": "548554f8-6278-4361-a88a-4a715546dace",
  "asset_ids": ["01JASSET1", "01JASSET2"],
  "base_submission_id": "01JONBOARDING"
}
```

Kirby Creative must verify that every asset and the base onboarding submission belong to the site
resolved from the bearer token.

Accepted response (`202`):

```json
{
  "site_layout": {
    "id": "01JLAYOUT",
    "status": "queued",
    "progress": 0,
    "status_url": "https://kirbycreative.co/api/site-layouts/01JLAYOUT"
  }
}
```

`GET /site-layouts/{id}` uses the same status vocabulary and returns the same `site_layout.pages`
shape defined by the completed onboarding response.

## 8. AI feedback

`POST /ai-feedback`

Feedback belongs to Kirby Creative because it owns model selection and strike history.

Request:

```json
{
  "resource_type": "site_layout_page",
  "resource_id": "01JLAYOUT:menu",
  "helpful": false,
  "reason": "This should focus on catering rather than discounts."
}
```

Validation:

- `resource_type`: an allow-listed Kirby Creative resource type;
- `resource_id`: required and must belong to the authenticated site;
- `helpful`: required boolean;
- `reason`: optional for positive feedback and required for negative feedback, maximum 2000 characters.

Successful response:

```json
{
  "feedback": {
    "recorded": true
  }
}
```

The client must not receive model credentials or directly mutate a provider/model strike ledger.

## Implementation order

### Kirby Creative repository

1. Define and activate the `top_sites` AI action with the exact input/output contract above.
2. Issue Sanctum tokens during provisioning and expose the token to the provisioner only once.
3. Add token-plus-`X-Keystone-Site-Url` resolution middleware and token abilities for onboarding, assets, layouts, and feedback.
4. Implement onboarding completion persistence, idempotency, orchestration, and status resources.
5. Implement private asset upload/ingestion and status resources.
6. Implement revised site-layout generation and status resources.
7. Implement feedback ownership and model-attribution recording.
8. Add feature tests proving cross-site access returns `404` or `403` and idempotent retries do not duplicate jobs.

### Provisioner repository

1. Receive the one-time site API token in Kirby Creative's signed provisioning request.
2. Write `KEYSTONE_API_URL` and `KEYSTONE_API_TOKEN` into the deployed client's environment;
3. never log or return the plaintext token after installation;
4. fail provisioning if the API credential handoff is absent.

### Keystone Client repository

1. Keep all Kirby Creative traffic in `KeystoneApiService`.
2. Submit onboarding only from the final completion action.
3. Persist stable idempotency keys and remote job/resource IDs before retrying.
4. Upload private assets to Kirby Creative and poll ingestion status.
5. Replace the remaining direct `OpenRouterService` sitemap and feedback calls.
6. Remove `OPENROUTER_*` client configuration after the final direct dependency is removed.
7. Test request schemas, authentication headers, error propagation, retries, and result normalization.
