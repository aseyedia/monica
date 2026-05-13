# monica-mcp

FastMCP HTTP server that exposes Monica CRM as an MCP tool to claude.ai.

**Port:** 8766  
**Tunnel:** `monica-mcp.artaseyedian.com` → `localhost:8766` (Cloudflare Tunnel)  
**Venv:** `~/.venvs/obsidian-mcp/` (shared with obsidian-mcp)

---

## Process management

Managed by **systemd user service** — starts on boot automatically.

```bash
# Status / logs
systemctl --user status monica-mcp
journalctl --user -u monica-mcp -f

# Restart after code changes
systemctl --user restart monica-mcp

# Stop / start
systemctl --user stop monica-mcp
systemctl --user start monica-mcp
```

---

## Re-authorizing in claude.ai

Auth codes are in-memory and lost on restart. After a restart, if claude.ai asks you to re-authorize:

1. Visit `https://monica-mcp.artaseyedian.com/authorize` (claude.ai will redirect you automatically)
2. Enter your `MCP_TOKEN` (set in `~/.config/systemd/user/monica-mcp.service` env, or blank if not configured)
3. Approve — claude.ai gets a fresh token

---

## Tokens

| Token | Where | Notes |
|---|---|---|
| `MONICA_TOKEN` | `~/media-center/.env.mcp` or auto-read from memory file | Monica API personal access token |
| `MCP_TOKEN` | `~/media-center/.env.mcp` | Auth gate password; blank = no auth (safe behind Cloudflare Tunnel) |

Monica API token fallback path:  
`~/.claude/projects/-home-arta-media-center-monica-fork/memory/monica-api-token.md`

Generate a new Monica token at: `https://monica.artaseyedian.com/settings/api`

---

## Setup from scratch

```bash
# Create venv (shared with obsidian-mcp)
python3 -m venv ~/.venvs/obsidian-mcp

# Install deps
~/.venvs/obsidian-mcp/bin/pip install -r requirements.txt

# Enable and start service
systemctl --user daemon-reload
systemctl --user enable --now monica-mcp
```

---

## Tools

| Group | Tools |
|---|---|
| Contacts | `search_contacts`, `get_contact`, `create_contact`, `update_contact` |
| Notes | `get_notes`, `add_note` |
| Reminders | `get_reminders`, `add_reminder` |
| Tasks | `get_tasks`, `add_task` |
| Calls | `get_calls`, `log_call` |
| Activities | `get_activities`, `log_activity` (multi-attendee: `contact_ids: list[int]`) |
| Tags | `set_tags` |
| Contact fields | `get_contact_fields`, `add_contact_field` |
| Relationships | `get_relationships`, `relate_contacts` |
| Addresses | `get_addresses`, `add_address` |
| Journal | `list_journal`, `add_journal_entry` |

> No delete operations. `update_contact` is a full PUT — omitting a field clears it.
