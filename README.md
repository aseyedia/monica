# Phoebe

A personal CRM built for the LLM era — a fork of [Monica](https://github.com/monicahq/monica) (v4.x) with a first-class MCP server so AI assistants can read and write your relationship data directly.

---

## What makes this different

Monica is already the best open-source personal CRM. Phoebe adds one thing: an [MCP](https://modelcontextprotocol.io/) server that exposes your contacts, notes, reminders, activities, and calls as tools any LLM can use.

In practice, this means you can tell Claude:

> "Log that I had lunch with Hunter and Annabel at Falafel Time today"

> "Add a reminder to reach out to Sarah in two weeks"

> "Who have I not talked to in over a month?"

...and it just works, against your real data, with an audit trail.

---

## MCP Server

The MCP server lives in `mcp/` and runs alongside the Monica app.

```
mcp/
├── server.py        # FastMCP HTTP server (port 8766)
├── requirements.txt # Python deps
└── README.md        # Setup, auth, tool reference
```

See [`mcp/README.md`](mcp/README.md) for the full tool reference and setup instructions.

### Quick start

```bash
python3 -m venv ~/.venvs/phoebe-mcp
~/.venvs/phoebe-mcp/bin/pip install -r mcp/requirements.txt

cd mcp && ~/.venvs/phoebe-mcp/bin/python server.py --http
```

The server exposes an OAuth-gated MCP endpoint. Point claude.ai's custom MCP integration at `https://your-domain.com/mcp`.

### Available tools

| Group | Tools |
|---|---|
| Contacts | `search_contacts`, `get_contact`, `create_contact`, `update_contact` |
| Notes | `get_notes`, `add_note` |
| Reminders | `get_reminders`, `add_reminder` |
| Tasks | `get_tasks`, `add_task` |
| Calls & texts | `get_calls`, `log_call` (`is_text=True` for SMS) |
| Activities | `get_activities`, `log_activity` (multi-attendee: `contact_ids: list[int]`) |
| Tags | `set_tags` |
| Contact fields | `get_contact_fields`, `add_contact_field` |
| Relationships | `get_relationships`, `relate_contacts` |
| Addresses | `get_addresses`, `add_address` |
| Journal | `list_journal`, `add_journal_entry` |

---

## Fork changes from upstream Monica

See [`FORK_CHANGES.md`](FORK_CHANGES.md) for the full changelog. Summary:

- **MCP server** — first-class LLM integration via Model Context Protocol
- **Text exchange logging** — calls section extended to also log SMS/text exchanges
- **ntfy push notifications** — replaces Twilio SMS with self-hosted [ntfy](https://ntfy.sh/)
- **CalDAV reminders** — exports Monica reminders as `VTODO` so they appear in iOS Reminders
- **Reminder reliability** — overdue reminders fire on next scheduler run instead of being dropped
- **Mobile refresh button** — fixed PWA refresh button for iOS standalone mode
- **Contact intake form** — public form for collecting new contact submissions

---

## Deployment

Requires Docker and Docker Compose.

```bash
# 1. Clone
git clone https://github.com/aseyedia/phoebe.git
cd phoebe

# 2. Configure
cp .env.example .env
# Edit .env — set APP_KEY, DB credentials, mail settings

# 3. Build and start
docker compose build monica
docker compose up -d monica

# 4. Run migrations
docker exec monica php artisan migrate --force

# 5. Start the MCP server
cd mcp
python3 -m venv ~/.venvs/phoebe-mcp
~/.venvs/phoebe-mcp/bin/pip install -r requirements.txt
~/.venvs/phoebe-mcp/bin/python server.py --http
```

For production: run the MCP server as a systemd user service (see `mcp/README.md`).

---

## Philosophy

A personal CRM is only useful if your AI assistant can use it. The MCP server is designed to be:

- **Complete** — every major Monica resource is exposed as a tool
- **Documented** — tool docstrings include type IDs, field semantics, and usage notes so the LLM doesn't have to guess
- **Safe** — no delete operations exposed; destructive actions require the Monica UI

---

## Upstream

This is a personal fork. For the canonical Monica project, see [monicahq/monica](https://github.com/monicahq/monica).
