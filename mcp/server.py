#!/usr/bin/env python3
"""Monica personal CRM MCP server — REST API wrapper."""

import json
import os
import sys
from mcp.server.fastmcp import FastMCP
from mcp.server.fastmcp.server import TransportSecuritySettings

MONICA_BASE = "http://localhost:8085/api"
BASE_URL = "https://monica-mcp.artaseyedian.com"

_TOKEN_FILE = os.path.expanduser(
    "~/.claude/projects/-home-arta-media-center-monica-fork/memory/monica-api-token.md"
)

_PUBLIC_PATHS = {
    "/.well-known/oauth-authorization-server",
    "/.well-known/oauth-protected-resource",
    "/.well-known/oauth-protected-resource/mcp",
    "/token",
    "/register",
    "/authorize",
    "/favicon.ico",
}

_auth_codes: dict = {}


def _monica_token() -> str:
    t = os.environ.get("MONICA_TOKEN", "")
    if t:
        return t
    with open(_TOKEN_FILE) as f:
        for line in f:
            line = line.strip().strip("`")
            if line.startswith("eyJ"):
                return line
    raise RuntimeError("Monica token not found")


def _api(method: str, path: str, body: dict | None = None) -> dict:
    import httpx
    headers = {
        "Authorization": f"Bearer {_monica_token()}",
        "Content-Type": "application/json",
    }
    url = f"{MONICA_BASE}/{path.lstrip('/')}"
    r = httpx.request(method, url, headers=headers, json=body, timeout=10)
    r.raise_for_status()
    if r.status_code == 204 or not r.content:
        return {}
    return r.json()


def _get(path: str) -> dict:   return _api("GET", path)
def _post(path: str, b: dict) -> dict: return _api("POST", path, b)
def _put(path: str, b: dict) -> dict:  return _api("PUT", path, b)
def _delete(path: str) -> dict:        return _api("DELETE", path)
def _fmt(data) -> str:                 return json.dumps(data, indent=2)


mcp = FastMCP(
    "monica",
    host="127.0.0.1",
    port=8766,
    stateless_http=True,
    transport_security=TransportSecuritySettings(
        allowed_hosts=["monica-mcp.artaseyedian.com", "localhost:8766", "127.0.0.1:8766"],
        allowed_origins=["https://monica-mcp.artaseyedian.com"],
    ),
)

# Tool index
# ─────────────────────────────────────────────────────────────────
# Contacts:       search_contacts, get_contact, create_contact, update_contact
# Notes:          get_notes, add_note
# Reminders:      get_reminders, add_reminder
# Tasks:          get_tasks, add_task
# Calls:          get_calls, log_call
# Activities:     get_activities, log_activity (contact_ids: list[int] — supports multiple attendees)
# Tags:           set_tags
# Contact fields: get_contact_fields, add_contact_field  (phone/email/social)
# Relationships:  get_relationships, relate_contacts
# Addresses:      get_addresses, add_address
# Journal:        list_journal, add_journal_entry
#
# ⚠ No delete operations are exposed. No way to mark a task complete.
# update_contact is a full PUT — omitting a field resets it to blank.
# ─────────────────────────────────────────────────────────────────


# ── Contacts ──────────────────────────────────────────────────────────────────

@mcp.tool()
def search_contacts(query: str) -> str:
    """
    Search contacts by name. Returns id and complete_name for each match.
    Use this first to get a contact_id before calling any other tool.
    """
    r = _get(f"contacts?query={query}&limit=20")
    contacts = r.get("data", [])
    if not contacts:
        return f"No contacts found for: {query}"
    return "\n".join(f"id={c['id']}  {c['complete_name']}" for c in contacts)


@mcp.tool()
def get_contact(contact_id: int) -> str:
    """
    Get full details for a contact by ID. Response includes: name, nickname,
    description, gender, birthday, occupation, tags, last_activity_date,
    stay_in_touch_frequency, notes/reminders/tasks counts, and more.
    """
    return _fmt(_get(f"contacts/{contact_id}"))


@mcp.tool()
def create_contact(
    first_name: str,
    last_name: str = "",
    gender_id: int = 1,
    description: str = "",
    nickname: str = "",
) -> str:
    """
    Create a new contact.

    gender_id: 1=Man, 2=Woman, 3=Rather not say
    """
    body: dict = {
        "first_name": first_name,
        "last_name": last_name,
        "gender_id": gender_id,
        "is_birthdate_known": False,
        "is_deceased": False,
        "is_deceased_date_known": False,
    }
    if description: body["description"] = description
    if nickname:    body["nickname"] = nickname
    r = _post("contacts", body)
    c = r.get("data", {})
    return f"Created contact: id={c.get('id')}  {c.get('complete_name')}"


@mcp.tool()
def update_contact(
    contact_id: int,
    first_name: str,
    last_name: str = "",
    gender_id: int = 1,
    description: str = "",
    nickname: str = "",
) -> str:
    """
    Update an existing contact's core fields.

    ⚠ This is a full PUT — any field you omit will be reset to blank.
    Call get_contact first if you need to preserve existing values.

    gender_id: 1=Man, 2=Woman, 3=Rather not say
    """
    body: dict = {
        "first_name": first_name,
        "last_name": last_name,
        "gender_id": gender_id,
        "is_birthdate_known": False,
        "is_deceased": False,
        "is_deceased_date_known": False,
    }
    if description: body["description"] = description
    if nickname:    body["nickname"] = nickname
    r = _put(f"contacts/{contact_id}", body)
    c = r.get("data", {})
    return f"Updated contact: id={c.get('id')}  {c.get('complete_name')}"


# ── Notes ─────────────────────────────────────────────────────────────────────

@mcp.tool()
def get_notes(contact_id: int) -> str:
    """List all notes for a contact."""
    return _fmt(_get(f"contacts/{contact_id}/notes"))


@mcp.tool()
def add_note(contact_id: int, body: str, favorited: bool = False) -> str:
    """Add a note to a contact. Set favorited=True to pin it."""
    r = _post("notes", {"contact_id": contact_id, "body": body, "is_favorited": favorited})
    n = r.get("data", {})
    return f"Note added: id={n.get('id')} to contact {contact_id}"


# ── Reminders ─────────────────────────────────────────────────────────────────

@mcp.tool()
def get_reminders(contact_id: int) -> str:
    """List reminders for a contact."""
    return _fmt(_get(f"contacts/{contact_id}/reminders"))


@mcp.tool()
def add_reminder(
    contact_id: int,
    title: str,
    initial_date: str,
    frequency_type: str = "one_time",
) -> str:
    """
    Add a reminder for a contact.

    initial_date: YYYY-MM-DD — when the reminder first fires
    frequency_type: one_time | week | month | year
      Recurring reminders repeat every 1 unit (frequency_number is fixed at 1).
      For "every 2 weeks" or similar intervals, use Monica's UI instead.
    """
    r = _post("reminders", {
        "contact_id": contact_id,
        "title": title,
        "initial_date": initial_date,
        "frequency_type": frequency_type,
        "frequency_number": 1,
    })
    rem = r.get("data", {})
    return f"Reminder added: id={rem.get('id')} '{title}' on {initial_date}"


# ── Tasks ─────────────────────────────────────────────────────────────────────

@mcp.tool()
def get_tasks(contact_id: int) -> str:
    """List tasks for a contact."""
    return _fmt(_get(f"contacts/{contact_id}/tasks"))


@mcp.tool()
def add_task(contact_id: int, title: str) -> str:
    """
    Add a task for a contact. Tasks are created incomplete.
    There is no MCP tool to mark a task complete — use Monica's UI for that.
    """
    r = _post("tasks", {"contact_id": contact_id, "title": title, "completed": False})
    t = r.get("data", {})
    return f"Task added: id={t.get('id')} '{title}'"


# ── Calls ─────────────────────────────────────────────────────────────────────

@mcp.tool()
def get_calls(contact_id: int) -> str:
    """List logged calls for a contact."""
    return _fmt(_get(f"contacts/{contact_id}/calls"))


@mcp.tool()
def log_call(contact_id: int, description: str, called_at: str, is_text: bool = False) -> str:
    """
    Log a phone call or text exchange with a contact.

    description: free-text notes about the conversation
    called_at:   YYYY-MM-DD
    is_text:     True for a text/SMS exchange, False (default) for a phone call
    """
    r = _post("calls", {"contact_id": contact_id, "content": description, "called_at": called_at, "is_text": is_text})
    c = r.get("data", {})
    return f"Call logged: id={c.get('id')} on {called_at}"


# ── Activities ────────────────────────────────────────────────────────────────

@mcp.tool()
def get_activities(contact_id: int) -> str:
    """List activities logged with a contact."""
    return _fmt(_get(f"contacts/{contact_id}/activities"))


@mcp.tool()
def log_activity(
    contact_ids: list[int] | None = None,
    activity_type_id: int = 9,
    summary: str = "",
    happened_at: str = "",
    description: str = "",
    contact_id: int | None = None,
) -> str:
    """
    Log an activity with one or more contacts.

    contact_ids  List of contact IDs. Pass multiple for a shared/group activity —
                 e.g. [723, 653] creates ONE activity with both as attendees.
    contact_id   Accepted as a fallback alias for contact_ids (single contact).

    activity_type_id  Pick the closest match:
      General:      1  just hung out
                    2  watched a movie together
                    3  talked at home
      Sport:        4  did sport / physical activity together
      Food/drinks:  5  ate at their place
                    6  went to a bar / drinks out
                    7  ate at home (Arta cooked or they cooked)
                    8  picnic
                    9  ate at a restaurant
      Culture:     10  went to the theater
                   11  went to a concert
                   12  went to a play
                   13  went to a museum

    summary      Short title shown in the activity feed — one line, e.g.
                 "Lunch at Falafel Time on South St". Keep it under ~80 chars.

    happened_at  Date of the activity: YYYY-MM-DD

    description  Optional long-form notes shown in the activity detail view.
                 Use this for conversation topics, context, anything worth
                 remembering. Supports plain text. Leave empty if nothing extra.
                 Example: "Talked about Hunter's wedding recap, wedding drama,
                 table politics at work. Arta paid. Annabel mentioned she wants
                 to visit Europe next year."
    """
    ids = contact_ids or ([contact_id] if contact_id else [])
    if not ids:
        return "Error: provide contact_ids (list) or contact_id (int)"
    if not summary:
        return "Error: summary is required"
    if not happened_at:
        return "Error: happened_at is required (YYYY-MM-DD)"
    payload: dict = {
        "contacts": ids,
        "activity_type_id": activity_type_id,
        "summary": summary,
        "happened_at": happened_at,
    }
    if description:
        payload["description"] = description
    r = _post("activities", payload)
    a = r.get("data", {})
    names = [c.get("complete_name", str(c.get("id"))) for c in a.get("attendees", {}).get("contacts", [])]
    return f"Activity logged: id={a.get('id')} on {happened_at} with {names}"


# ── Tags ──────────────────────────────────────────────────────────────────────

@mcp.tool()
def set_tags(contact_id: int, tags: list[str]) -> str:
    """
    Replace all tags on a contact. This is not additive — existing tags not
    in the list are removed. Pass an empty list [] to clear all tags.
    Call get_contact first if you need to preserve existing tags.
    """
    r = _post(f"contacts/{contact_id}/setTags", {"tags": tags})
    names = [t["name"] for t in r.get("data", {}).get("tags", [])]
    return f"Tags set on contact {contact_id}: {names}"


# ── Contact fields (phone, email, social) ─────────────────────────────────────

@mcp.tool()
def get_contact_fields(contact_id: int) -> str:
    """List contact fields (phone, email, social handles) for a contact."""
    return _fmt(_get(f"contacts/{contact_id}/contactfields"))


@mcp.tool()
def add_contact_field(contact_id: int, field_type_id: int, value: str) -> str:
    """
    Add a phone, email, or social handle to a contact.

    field_type_id — verified against live DB:
      1=Email, 2=Phone, 3=Facebook, 4=Twitter,
      5=Whatsapp, 6=Telegram, 7=LinkedIn

    value: the raw value (e.g. "+1 555 000 0000", "user@example.com", "@handle")
    Multiple fields of the same type are allowed (e.g. two phone numbers).
    """
    r = _post("contactfields", {
        "contact_id": contact_id,
        "contact_field_type_id": field_type_id,
        "data": value,
    })
    f = r.get("data", {})
    return f"Field added: id={f.get('id')} to contact {contact_id}"


# ── Relationships ─────────────────────────────────────────────────────────────

@mcp.tool()
def get_relationships(contact_id: int) -> str:
    """List all relationships for a contact, including type and linked contact ID."""
    return _fmt(_get(f"contacts/{contact_id}/relationships"))


@mcp.tool()
def relate_contacts(contact_id_a: int, contact_id_b: int, relationship_type_id: int) -> str:
    """
    Create a relationship between two contacts.

    relationship_type_id — verified against live DB:
      Love:   1=partner, 2=spouse, 3=date, 4=lover,
              5=inlovewith, 6=lovedby, 7=ex, 25=ex_husband
      Family: 8=parent, 9=child, 10=sibling,
              11=grandparent, 12=grandchild,
              13=uncle, 14=nephew, 15=cousin,
              16=godfather, 17=godson,
              26=stepparent, 27=stepchild
      Friend: 18=friend, 19=bestfriend
      Work:   20=colleague, 21=boss, 22=subordinate,
              23=mentor, 24=protege
    """
    r = _post("relationships", {
        "contact_is": contact_id_a,
        "of_contact": contact_id_b,
        "relationship_type_id": relationship_type_id,
    })
    return f"Relationship created: id={r.get('data', {}).get('id')}"


# ── Addresses ─────────────────────────────────────────────────────────────────

@mcp.tool()
def get_addresses(contact_id: int) -> str:
    """List addresses for a contact."""
    return _fmt(_get(f"contacts/{contact_id}/addresses"))


@mcp.tool()
def add_address(
    contact_id: int,
    name: str = "Home",
    city: str = "",
    province: str = "",
    postal_code: str = "",
    country: str = "",
    street: str = "",
) -> str:
    """
    Add an address to a contact. All fields except contact_id and name are optional.

    name: label for the address (e.g. "Home", "Work")
    province: state or province
    country: two-letter ISO country code (e.g. "US", "CA")
    """
    body: dict = {"contact_id": contact_id, "name": name}
    for k, v in [("city", city), ("province", province), ("postal_code", postal_code),
                 ("country", country), ("street", street)]:
        if v:
            body[k] = v
    r = _post("addresses", body)
    a = r.get("data", {})
    return f"Address added: id={a.get('id')} to contact {contact_id}"


# ── Journal ───────────────────────────────────────────────────────────────────

@mcp.tool()
def list_journal(page: int = 1) -> str:
    """List journal entries, 20 per page. page=1 returns the most recent."""
    return _fmt(_get(f"journal?page={page}&limit=20"))


@mcp.tool()
def add_journal_entry(title: str, body: str) -> str:
    """Add a journal entry. title is the headline; body is the full text."""
    r = _post("journal", {"title": title, "post": body})
    e = r.get("data", {})
    return f"Journal entry added: id={e.get('id')} '{title}'"


# ── HTTP app with OAuth ───────────────────────────────────────────────────────

def _build_http_app():
    import base64
    import hashlib
    import secrets
    import time
    from starlette.applications import Starlette
    from starlette.requests import Request
    from starlette.responses import HTMLResponse, JSONResponse, PlainTextResponse, RedirectResponse
    from starlette.routing import Route

    mcp_token = os.environ.get("MCP_TOKEN", "")

    async def protected_resource(request: Request):
        return JSONResponse({"resource": BASE_URL, "authorization_servers": [BASE_URL]})

    async def oauth_discovery(request: Request):
        return JSONResponse({
            "issuer": BASE_URL,
            "authorization_endpoint": f"{BASE_URL}/authorize",
            "token_endpoint": f"{BASE_URL}/token",
            "registration_endpoint": f"{BASE_URL}/register",
            "response_types_supported": ["code"],
            "grant_types_supported": ["authorization_code"],
            "code_challenge_methods_supported": ["S256"],
            "token_endpoint_auth_methods_supported": ["none"],
        })

    async def oauth_register(request: Request):
        try:
            body = await request.json()
        except Exception:
            body = {}
        return JSONResponse({
            **body,
            "client_id": body.get("client_id") or secrets.token_urlsafe(16),
            "client_secret": secrets.token_urlsafe(32),
            "client_secret_expires_at": 0,
        }, status_code=201)

    async def oauth_authorize_get(request: Request):
        p = dict(request.query_params)
        redirect_uri = p.get("redirect_uri", "")
        state = p.get("state", "")
        code = secrets.token_urlsafe(24)
        _auth_codes[code] = {
            "redirect_uri": redirect_uri,
            "code_challenge": p.get("code_challenge"),
            "expires": time.time() + 300,
        }
        html = f"""<!DOCTYPE html>
<html><head><title>Monica CRM Access</title>
<style>
body{{font-family:sans-serif;max-width:420px;margin:80px auto;padding:0 16px;text-align:center}}
input[type=password]{{width:100%;padding:10px;font-size:15px;margin:12px 0;box-sizing:border-box;border:1px solid #ccc;border-radius:6px}}
button{{padding:12px 32px;font-size:16px;background:#e05c5c;color:#fff;border:none;border-radius:8px;cursor:pointer;width:100%}}
</style></head><body>
<h2>Monica CRM</h2>
<p>Claude.ai is requesting access to your contacts.<br>Enter your MCP token to approve.</p>
<form method="post">
  <input type="hidden" name="code" value="{code}">
  <input type="hidden" name="redirect_uri" value="{redirect_uri}">
  <input type="hidden" name="state" value="{state}">
  <input type="password" name="password" placeholder="MCP token" autofocus>
  <button type="submit">Approve</button>
</form>
</body></html>"""
        return HTMLResponse(html)

    async def oauth_authorize_post(request: Request):
        form = await request.form()
        code = form.get("code", "")
        redirect_uri = form.get("redirect_uri", "")
        state = form.get("state", "")
        password = form.get("password", "")
        if password != mcp_token:
            return HTMLResponse(
                "<p style='color:red;font-family:sans-serif;text-align:center;margin-top:80px'>"
                "Wrong token. Go back and try again.</p>",
                status_code=401,
            )
        if code not in _auth_codes:
            return PlainTextResponse("Expired — restart the auth flow.", status_code=400)
        return RedirectResponse(f"{redirect_uri}?code={code}&state={state}", status_code=302)

    async def oauth_token(request: Request):
        form = await request.form()
        code = form.get("code", "")
        code_verifier = form.get("code_verifier", "")
        entry = _auth_codes.pop(code, None)
        if not entry or time.time() > entry["expires"]:
            return JSONResponse({"error": "invalid_grant"}, status_code=400)
        challenge = entry.get("code_challenge")
        if challenge and code_verifier:
            digest = hashlib.sha256(code_verifier.encode()).digest()
            computed = base64.urlsafe_b64encode(digest).rstrip(b"=").decode()
            if computed != challenge:
                return JSONResponse({"error": "invalid_grant"}, status_code=400)
        return JSONResponse({"access_token": mcp_token, "token_type": "bearer", "expires_in": 86400})

    oauth_app = Starlette(routes=[
        Route("/.well-known/oauth-protected-resource", protected_resource),
        Route("/.well-known/oauth-protected-resource/mcp", protected_resource),
        Route("/.well-known/oauth-authorization-server", oauth_discovery),
        Route("/register", oauth_register, methods=["POST"]),
        Route("/authorize", oauth_authorize_get, methods=["GET"]),
        Route("/authorize", oauth_authorize_post, methods=["POST"]),
        Route("/token", oauth_token, methods=["POST"]),
    ])

    mcp_asgi = mcp.streamable_http_app()

    class RootApp:
        async def __call__(self, scope, receive, send):
            if scope["type"] == "lifespan":
                await mcp_asgi(scope, receive, send)
                return
            path = scope.get("path", "")
            if scope["type"] == "http" and path not in _PUBLIC_PATHS:
                headers = dict(scope.get("headers", []))
                auth = headers.get(b"authorization", b"").decode()
                if mcp_token and auth != f"Bearer {mcp_token}":
                    resp = PlainTextResponse("Unauthorized", status_code=401)
                    await resp(scope, receive, send)
                    return
            if path in _PUBLIC_PATHS or path.startswith("/.well-known/"):
                await oauth_app(scope, receive, send)
            else:
                # FastMCP mounts at /mcp — rewrite bare / so claude.ai can hit either path
                if path == "/" or path == "":
                    scope = {**scope, "path": "/mcp", "raw_path": b"/mcp"}
                await mcp_asgi(scope, receive, send)

    return RootApp()


if __name__ == "__main__":
    if "--http" in sys.argv:
        import uvicorn
        uvicorn.run(_build_http_app(), host="127.0.0.1", port=8766)
    else:
        mcp.run()
