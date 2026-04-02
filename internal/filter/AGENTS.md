<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# filter

## Purpose
Traffic filtering and detection - determines which clicks/visitors to allow, block, or flag.

## Key Files
| File | Description |
|------|-------------|
| `detection.go` | Bot and fraud detection |
| `device.go` | Device type filtering |
| `filter.go` | Main filter logic |
| `geo.go` | Geographic filtering |
| `network.go` | Network-based filtering |
| `params.go` | Parameter validation |
| `schedule.go` | Schedule-based filtering |
| `tracking.go` | Tracking-specific filters |
| `traffic.go` | Traffic pattern analysis |

## For AI Agents

### Working In This Directory
- Multi-layer filtering system
- Filters based on device, geo, network, behavior
- Returns filter decision (allow/block/flag)

<!-- MANUAL: -->