# CHANGELOG

## [1.0.0] - 2026-03-24

### ✨ Features

#### Available Commands

| ID | Name | Type | Subtype | Description |
|---|---|---|---|---|
| power | Power On/Off | action | other | Turn TV on or off |
| mute | Mute | action | other | Mute/unmute |
| vol_up | Volume + | action | other | Increase volume |
| vol_down | Volume - | action | other | Decrease volume |
| ch_up | Channel + | action | other | Next channel |
| ch_down | Channel - | action | other | Previous channel |
| source | Source | action | other | Show source menu |
| zap | Zap | action | slider | Jump to channel (1-999) |
| sendkey | Key | action | message | Send custom key |
| state | State | info | binary | TV state (online/offline) |

#### Configuration Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| ip | string | - | TV IP address |
| port | integer | 8002 | WebSocket port |
| ssl | boolean | true | Use WSS (secure) |
| tokenAuth | string | - | Auth token (auto-generated) |
| keyDelay | integer | 300 | Delay between keys (ms) |

**Version 1.0.0** - March 24, 2026
